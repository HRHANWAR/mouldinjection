<?php
/**
 * Template Name: Contact Us
 * Template Post Type: page
 */

// ─── FORM PROCESSING ───────────────────────────────────────────────
$form_status  = '';
$form_message = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['contact_form_nonce'] ) ) {

    if ( ! wp_verify_nonce( $_POST['contact_form_nonce'], 'contact_form_submit' ) ) {
        $form_status  = 'error';
        $form_message = 'Security check failed. Please try again.';

    } else {

        $full_name     = sanitize_text_field( $_POST['full_name']     ?? '' );
        $company_name  = sanitize_text_field( $_POST['company_name']  ?? '' );
        $email         = sanitize_email(      $_POST['email']         ?? '' );
        $phone         = sanitize_text_field( $_POST['phone']         ?? '' );
        $business_type = sanitize_text_field( $_POST['business_type'] ?? '' );
        $subject       = sanitize_text_field( $_POST['subject']       ?? 'Contact Form Enquiry' );
        $message       = sanitize_textarea_field( $_POST['message']   ?? '' );

        if ( empty( $full_name ) || empty( $email ) || empty( $message ) ) {
            $form_status  = 'error';
            $form_message = 'Please fill in all required fields.';

        } elseif ( ! is_email( $email ) ) {
            $form_status  = 'error';
            $form_message = 'Please enter a valid email address.';

        } else {

            // ── FILE UPLOAD ──────────────────────────────────────────
            $attachment_path      = '';
            $attachment_real_name = '';
            $named_path           = '';

            if ( ! empty( $_FILES['file_upload']['name'] ) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK ) {

                $file    = $_FILES['file_upload'];
                $allowed = [ 'pdf', 'dwg', 'dxf', 'step', 'stp', 'jpg', 'jpeg', 'png' ];
                $ext     = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

                if ( ! in_array( $ext, $allowed ) ) {
                    $form_status  = 'error';
                    $form_message = 'Invalid file type. Allowed: PDF, DWG, DXF, STEP, JPG, PNG.';

                } elseif ( $file['size'] > 10 * 1024 * 1024 ) {
                    $form_status  = 'error';
                    $form_message = 'File too large. Maximum size is 10MB.';

                } else {
                    $attachment_path      = $file['tmp_name'];
                    $attachment_real_name = basename( $file['name'] );
                }
            }
            // ── END FILE UPLOAD ──────────────────────────────────────

            if ( $form_status !== 'error' ) {

                $to      = 'rukhsanaghafoor42@gmail.com';
                $subject = $subject ?: 'New Contact Form Submission — Mould Injection';

                $business_labels = [
                    'tool_owner'       => 'Tool Owner',
                    'manufacturer'     => 'Injection Moulding Manufacturer',
                    'product_business' => 'Product Business',
                    'other'            => 'Other',
                ];
                $business_label = $business_labels[ $business_type ] ?? ucfirst( $business_type );

                $attachment_row = '';
                if ( $attachment_real_name ) {
                    $attachment_row = '
                    <tr>
                      <td style="padding:0 32px 20px;">
                        <div style="background:#F5F6FA;border-radius:8px;padding:12px 14px;">
                          <span style="font-size:18px;">📎</span>
                          <span style="font-size:13px;color:#474C59;margin-left:8px;">' . esc_html( $attachment_real_name ) . '</span>
                        </div>
                      </td>
                    </tr>';
                }

                $business_badge = $business_label
                    ? '<span style="display:inline-block;background:#D7FF80;color:#142F32;font-size:12px;font-weight:600;padding:3px 12px;border-radius:20px;">' . esc_html( $business_label ) . '</span>'
                    : '<span style="color:#B0B5C0;font-size:14px;">—</span>';

                $body = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F5F6FA;font-family:Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F5F6FA;padding:32px 16px;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #E8EAEF;max-width:560px;width:100%;">
      <tr>
        <td style="background:#142F32;padding:28px 32px;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td style="width:44px;vertical-align:middle;">
                <div style="width:40px;height:40px;background:#C8FF00;border-radius:8px;text-align:center;line-height:40px;font-size:20px;">✉</div>
              </td>
              <td style="padding-left:14px;vertical-align:middle;">
                <p style="margin:0;color:#C8FF00;font-size:12px;font-weight:600;letter-spacing:0.5px;">MOULDINJECTION.CO.UK</p>
                <p style="margin:4px 0 0;color:#ffffff;font-size:18px;font-weight:700;">New Contact Form Submission</p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td style="padding:24px 32px 0;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td width="50%" style="padding-right:6px;padding-bottom:12px;vertical-align:top;">
                <div style="background:#F5F6FA;border-radius:8px;padding:12px 14px;">
                  <p style="margin:0 0 3px;font-size:10px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.6px;">Full Name</p>
                  <p style="margin:0;font-size:14px;font-weight:600;color:#19191A;">' . esc_html( $full_name ) . '</p>
                </div>
              </td>
              <td width="50%" style="padding-left:6px;padding-bottom:12px;vertical-align:top;">
                <div style="background:#F5F6FA;border-radius:8px;padding:12px 14px;">
                  <p style="margin:0 0 3px;font-size:10px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.6px;">Company</p>
                  <p style="margin:0;font-size:14px;font-weight:600;color:#19191A;">' . ( $company_name ? esc_html( $company_name ) : '<span style="color:#B0B5C0;">—</span>' ) . '</p>
                </div>
              </td>
            </tr>
            <tr>
              <td width="50%" style="padding-right:6px;padding-bottom:12px;vertical-align:top;">
                <div style="background:#F5F6FA;border-radius:8px;padding:12px 14px;">
                  <p style="margin:0 0 3px;font-size:10px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.6px;">Email</p>
                  <p style="margin:0;font-size:14px;font-weight:600;color:#142F32;">' . esc_html( $email ) . '</p>
                </div>
              </td>
              <td width="50%" style="padding-left:6px;padding-bottom:12px;vertical-align:top;">
                <div style="background:#F5F6FA;border-radius:8px;padding:12px 14px;">
                  <p style="margin:0 0 3px;font-size:10px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.6px;">Phone</p>
                  <p style="margin:0;font-size:14px;font-weight:600;color:#19191A;">' . ( $phone ? esc_html( $phone ) : '<span style="color:#B0B5C0;">—</span>' ) . '</p>
                </div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td style="padding:0 32px 12px;">
          <div style="background:#F5F6FA;border-radius:8px;padding:12px 14px;">
            <p style="margin:0 0 6px;font-size:10px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.6px;">Business Type</p>
            ' . $business_badge . '
          </div>
        </td>
      </tr>
      <tr>
        <td style="padding:0 32px 12px;">
          <div style="background:#F5F6FA;border-radius:8px;padding:12px 14px;">
            <p style="margin:0 0 8px;font-size:10px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.6px;">Message</p>
            <p style="margin:0;font-size:14px;color:#474C59;line-height:1.7;">' . nl2br( esc_html( $message ) ) . '</p>
          </div>
        </td>
      </tr>
      ' . $attachment_row . '
      <tr>
        <td style="border-top:1px solid #E8EAEF;padding:14px 32px;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td style="font-size:12px;color:#B0B5C0;">mouldinjection.co.uk</td>
              <td align="right" style="font-size:12px;color:#B0B5C0;">Contact Form</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>';

                $headers = [
                    'Content-Type: text/html; charset=UTF-8',
                    "Reply-To: {$full_name} <{$email}>",
                ];

                $attachments = [];
                if ( $attachment_path && file_exists( $attachment_path ) ) {
                    $named_path = sys_get_temp_dir() . '/' . $attachment_real_name;
                    if ( copy( $attachment_path, $named_path ) ) {
                        $attachments[] = $named_path;
                    }
                }

                $sent = wp_mail( $to, $subject, $body, $headers, $attachments );

                if ( $named_path && file_exists( $named_path ) ) {
                    @unlink( $named_path );
                }

                if ( $sent ) {
                    $form_status  = 'success';
                    $form_message = "Thank you! Your message has been sent. We'll respond within 24 hours.";
                } else {
                    $form_status  = 'error';
                    $form_message = 'Sorry, there was a problem sending your message. Please try again or email us directly.';
                }
            }
        }
    }
}
get_header();
?>

<!-- ========= TERMS & CONDITIONS MODAL ========= -->
<div id="terms-modal" style="display:none;" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
  <!-- Backdrop -->
  <div id="terms-backdrop" class="absolute inset-0 bg-[#00191C]/70 backdrop-blur-sm"></div>

  <!-- Modal Box -->
  <div class="relative w-full max-w-2xl max-h-[85vh] flex flex-col bg-white rounded-3xl shadow-2xl overflow-hidden z-10">

    <!-- Modal Header -->
    <div class="flex items-center justify-between px-7 py-5 bg-[#142F32] flex-shrink-0">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-[#C8FF00] rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5 text-[#142F32]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <div>
          <p class="text-[#C8FF00] text-[11px] font-semibold font-[Montserrat] tracking-wider uppercase">mouldinjection.co.uk</p>
          <h3 class="text-white text-[18px] font-bold font-[Helvetica] leading-tight">Terms &amp; Conditions</h3>
        </div>
      </div>
      <button id="terms-close" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Last Updated Badge -->
    <div class="px-7 py-3 bg-[#F5F6FA] border-b border-[#E8EAEF] flex-shrink-0">
      <span class="inline-flex items-center gap-1.5 text-[12px] text-[#767C8C] font-[Montserrat]">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Last updated: June 2026
      </span>
    </div>

    <!-- Modal Content — Scrollable -->
    <div class="overflow-y-auto flex-1 px-7 py-6 space-y-6">

      <?php
      $terms_sections = [
        ['title' => '1. Introduction', 'content' => 'Welcome to Mould Injection (mouldinjection.co.uk). By accessing or using our platform, you agree to be bound by these Terms and Conditions. Please read them carefully before using our services. If you do not agree with any part of these terms, you must not use our platform.'],
        ['title' => '2. About the Platform', 'content' => 'Mould Injection is an online platform that connects tool owners with injection moulding manufacturers across the United Kingdom and overseas. We act as an intermediary and do not directly manufacture, own, or guarantee the quality of any tools, products, or services exchanged between parties on this platform.'],
        ['title' => '3. Eligibility', 'content' => 'To use our platform, you must be at least 18 years of age, be a registered business or act on behalf of a registered business, provide accurate and complete information when registering or submitting enquiries, and comply with all applicable laws and regulations in your jurisdiction.'],
        ['title' => '4. Use of the Platform', 'content' => 'You agree not to use the platform for any unlawful, fraudulent, or harmful purpose, submit false or misleading information, infringe intellectual property rights of any third party, attempt to gain unauthorised access to any part of the platform, or use the platform to send unsolicited commercial communications.'],
        ['title' => '5. Enquiries and Introductions', 'content' => 'When you submit an enquiry through our platform, the information provided may be shared with relevant manufacturing partners or tool owners to facilitate a business introduction. Mould Injection does not guarantee that any introduction will result in a business arrangement. All commercial agreements are made directly between the parties involved.'],
        ['title' => '6. Intellectual Property', 'content' => 'All content on this platform — including text, graphics, logos, images, and software — is the property of Mould Injection or its content suppliers and is protected by applicable intellectual property laws. You may not reproduce, distribute, or create derivative works without our express written permission.'],
        ['title' => '7. Privacy & Data Protection', 'content' => 'We are committed to protecting your personal data in accordance with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018. By using this platform, you consent to the collection and processing of your personal data. We will never sell your personal data to third parties.'],
        ['title' => '8. Disclaimer of Warranties', 'content' => 'The platform is provided on an "as is" and "as available" basis. Mould Injection makes no warranties regarding the availability, accuracy, or reliability of the platform. We do not warrant that the platform will be uninterrupted or error-free.'],
        ['title' => '9. Limitation of Liability', 'content' => 'To the fullest extent permitted by law, Mould Injection shall not be liable for any direct, indirect, incidental, or consequential damages arising from your use of this platform. This includes loss of revenue, profits, data, or business interruption.'],
        ['title' => '10. Governing Law', 'content' => 'These Terms and Conditions are governed by the laws of England and Wales. Any disputes arising out of or in connection with these terms shall be subject to the exclusive jurisdiction of the courts of England and Wales.'],
        ['title' => '11. Changes to These Terms', 'content' => 'We reserve the right to update or modify these Terms and Conditions at any time. Changes will be effective immediately upon posting. Your continued use of the platform constitutes acceptance of the revised terms.'],
        ['title' => '12. Contact Us', 'content' => 'If you have any questions about these Terms and Conditions, please contact us at Info@mouldinjection.co.uk.'],
      ];
      foreach ( $terms_sections as $section ) : ?>
      <div class="border-b border-[#F0F1F5] pb-5 last:border-0 last:pb-0">
        <h4 class="text-[15px] font-bold font-[Helvetica] text-[#19191A] mb-2"><?php echo esc_html( $section['title'] ); ?></h4>
        <p class="text-[#474C59] text-[14px] font-[Montserrat] leading-relaxed"><?php echo esc_html( $section['content'] ); ?></p>
      </div>
      <?php endforeach; ?>

    </div>

    <!-- Modal Footer -->
    <div class="flex items-center justify-between px-7 py-4 bg-[#F5F6FA] border-t border-[#E8EAEF] flex-shrink-0">
      <p class="text-[12px] text-[#B0B5C0] font-[Montserrat]">mouldinjection.co.uk</p>
      <button id="terms-accept" class="inline-flex items-center gap-2 bg-[#142F32] text-white px-6 py-2.5 rounded-full text-[14px] font-semibold font-[Montserrat] hover:bg-[#0f2326] transition-colors">
        I Understand
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
      </button>
    </div>

  </div>
</div>



<main class="im-page-main">

<!-- ========= HERO SECTION ========= -->
<section class="relative min-h-[265px] w-full flex flex-col items-center bg-center bg-cover"
    style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/Services/Injection Moulding.png');">
</section>

<!-- ========= MAIN CONTACT SECTION ========= -->
<section class="reveal py-16 bg-[#F5F6FA]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">

    <div class="mb-10">
      <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#D7FF80] rounded-full mb-5">
        <span class="w-2 h-2 rounded-full bg-[#19191A]"></span>
        <span class="text-[14px] font-medium font-[Poppins] text-[#19191A]">Contact Us</span>
      </div>
      <h1 class="text-[32px] lg:text-[40px] font-bold font-[Helvetica] text-[#19191A] leading-tight mb-4 max-w-2xl">
        Let's Discuss Your Injection Moulding Requirements
      </h1>
      <p class="text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed max-w-2xl mb-8">
        Whether you own mould tools, need available machine capacity, require production support, or want to register as a manufacturing partner — our team is here to help.
      </p>

      <p class="text-[#19191A] text-[16px] font-bold font-[Helvetica] mb-5">We welcome enquiries from:</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $enquiries = [
            [ 'image' => get_template_directory_uri() . '/assets/images/toool.png',             'text' => 'Tool owners seeking production partners' ],
            [ 'image' => get_template_directory_uri() . '/assets/images/injectionmoulding.png', 'text' => 'Injection moulding companies with spare capacity' ],
            [ 'image' => get_template_directory_uri() . '/assets/images/businesslooking.png',   'text' => 'Businesses looking for new tooling or moulding support' ],
            [ 'image' => get_template_directory_uri() . '/assets/images/global (2).png',        'text' => 'Companies requiring UK or overseas manufacturing solutions' ],
        ];
        foreach ( $enquiries as $e ) : ?>
        <div class="bg-white border border-[#E8EAEF] rounded-2xl p-5 shadow-sm">
          <div class="w-10 h-10 bg-[#F0F1F5] rounded-lg flex items-center justify-center mb-4">
            <img src="<?php echo esc_url( $e['image'] ); ?>" alt="" class="w-6 h-6 object-contain">
          </div>
          <p class="text-[#474C59] text-[14px] font-[Montserrat] leading-relaxed"><?php echo esc_html( $e['text'] ); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="bg-white border border-[#E8EAEF] rounded-3xl p-4 shadow-sm">
      <div class="flex flex-col lg:flex-row gap-4">

        <!-- LEFT: lime green panel -->
        <div class="w-full lg:w-[38%] rounded-2xl p-7 flex flex-col" style="background:#D7FF80;">
          <div class="mb-8">
            <h3 class="text-[#19191A] text-[22px] font-bold font-[Helvetica] mb-3">General Enquiries</h3>
            <p class="text-[#2d4a2d] text-[14px] font-[Montserrat] leading-relaxed mb-6">For all general questions, platform support, or business enquiries.</p>
            <div class="space-y-4">
              <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm">
                  <svg class="w-5 h-5 text-[#19191A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-[#2d4a2d] text-[12px] font-[Montserrat] mb-0.5">Email</p>
                  <p class="text-[#474C59] text-[14px] font-bold font-[Montserrat]">Info@mouldinjection.co.uk</p>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm">
                  <svg class="w-5 h-5 text-[#19191A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-[#2d4a2d] text-[12px] font-[Montserrat] mb-0.5">Location</p>
                  <p class="text-[#474C59] text-[14px] font-bold font-[Montserrat]">United Kingdom</p>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm">
                  <svg class="w-5 h-5 text-[#19191A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-[#2d4a2d] text-[12px] font-[Montserrat] mb-0.5">Response Time</p>
                  <p class="text-[#474C59] text-[14px] font-bold font-[Montserrat]">Within 24 hours</p>
                </div>
              </div>
            </div>
          </div>

          <div>
            <h3 class="text-[#19191A] text-[22px] font-bold font-[Helvetica] mb-3">Why Contact Us?</h3>
            <p class="text-[#2d4a2d] text-[14px] font-[Montserrat] leading-relaxed mb-5">For all general questions, platform support, or business enquiries.</p>
            <ul class="space-y-3">
              <?php foreach ( ['Available machines', 'Looking to move an existing mould tool', 'Need a UK manufacturing partner', 'Looking for new moulding work', 'Need help sourcing production'] as $item ) : ?>
              <li class="flex items-center gap-3">
                <div class="w-6 h-6 rounded-full bg-[#19191A] flex items-center justify-center flex-shrink-0">
                  <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <span class="text-[#19191A] text-[14px] font-medium font-[Montserrat]"><?php echo esc_html( $item ); ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <!-- RIGHT: form panel -->
        <div class="w-full lg:w-[62%] py-4 px-2 lg:px-4">
          <h2 class="text-[#19191A] text-[26px] font-bold font-[Helvetica] mb-1.5">Send Us A Message</h2>
          <p class="text-[#767C8C] text-[14px] font-[Montserrat] mb-7">Fill out the form and we'll respond within 24 hours.</p>

          <?php if ( $form_status === 'success' ) : ?>
          <div class="bg-[#D7FF80] border border-[#a8d400] rounded-xl px-5 py-4 mb-6 flex items-center gap-3">
            <svg class="w-5 h-5 text-[#153F45] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-[#153F45] font-bold font-[Montserrat] text-[14px]"><?php echo esc_html( $form_message ); ?></p>
          </div>
          <?php elseif ( $form_status === 'error' ) : ?>
          <div class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 mb-6 flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-red-600 font-medium font-[Montserrat] text-[14px]"><?php echo esc_html( $form_message ); ?></p>
          </div>
          <?php endif; ?>

          <?php if ( $form_status !== 'success' ) : ?>
          <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <?php wp_nonce_field( 'contact_form_submit', 'contact_form_nonce' ); ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-[#19191A] text-[13px] font-semibold font-[Montserrat] mb-2">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="full_name" required placeholder="Enter full name"
                  class="w-full px-4 py-3 rounded-xl border border-[#E8EAEF] bg-white text-[#19191A] text-[14px] font-[Montserrat] placeholder-[#B0B5C0] focus:outline-none focus:border-[#142F32] transition-colors" />
              </div>
              <div>
                <label class="block text-[#19191A] text-[13px] font-semibold font-[Montserrat] mb-2">Company Name</label>
                <input type="text" name="company_name" placeholder="Enter company name"
                  class="w-full px-4 py-3 rounded-xl border border-[#E8EAEF] bg-white text-[#19191A] text-[14px] font-[Montserrat] placeholder-[#B0B5C0] focus:outline-none focus:border-[#142F32] transition-colors" />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-[#19191A] text-[13px] font-semibold font-[Montserrat] mb-2">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" required placeholder="Enter email address"
                  class="w-full px-4 py-3 rounded-xl border border-[#E8EAEF] bg-white text-[#19191A] text-[14px] font-[Montserrat] placeholder-[#B0B5C0] focus:outline-none focus:border-[#142F32] transition-colors" />
              </div>
              <div>
                <label class="block text-[#19191A] text-[13px] font-semibold font-[Montserrat] mb-2">Phone Number</label>
                <input type="tel" name="phone" placeholder="+44"
                  class="w-full px-4 py-3 rounded-xl border border-[#E8EAEF] bg-white text-[#19191A] text-[14px] font-[Montserrat] placeholder-[#B0B5C0] focus:outline-none focus:border-[#142F32] transition-colors" />
              </div>
            </div>

            <div>
              <label class="block text-[#19191A] text-[13px] font-semibold font-[Montserrat] mb-2">Business Type</label>
              <div class="relative">
                <select name="business_type"
                  class="w-full px-4 py-3 rounded-xl border border-[#E8EAEF] bg-white text-[#767C8C] text-[14px] font-[Montserrat] focus:outline-none focus:border-[#142F32] transition-colors appearance-none">
                  <option value="" disabled selected></option>
                  <option value="tool_owner">Tool Owner</option>
                  <option value="manufacturer">Injection Moulding Manufacturer</option>
                  <option value="product_business">Product Business</option>
                  <option value="other">Other</option>
                </select>
                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                  <svg class="w-4 h-4 text-[#767C8C]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-[#19191A] text-[13px] font-semibold font-[Montserrat] mb-2">Subject</label>
              <input type="text" name="subject"
                class="w-full px-4 py-3 rounded-xl border border-[#E8EAEF] bg-white text-[#19191A] text-[14px] font-[Montserrat] focus:outline-none focus:border-[#142F32] transition-colors" />
            </div>

            <div>
              <label class="block text-[#19191A] text-[13px] font-semibold font-[Montserrat] mb-2">Message <span class="text-red-500">*</span></label>
              <textarea name="message" required rows="5"
                class="w-full px-4 py-3 rounded-xl border border-[#E8EAEF] bg-white text-[#19191A] text-[14px] font-[Montserrat] focus:outline-none focus:border-[#142F32] transition-colors resize-none"></textarea>
            </div>

            <div>
              <label class="block text-[#19191A] text-[13px] font-semibold font-[Montserrat] mb-2">File Upload</label>
              <label class="flex items-center gap-3 w-full px-4 py-3.5 rounded-xl border border-[#E8EAEF] bg-white cursor-pointer hover:border-[#142F32] transition-colors">
                <svg class="w-5 h-5 text-[#767C8C] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <span id="file-label-text" class="text-[#767C8C] text-[14px] font-[Montserrat]">Attach drawings, CAD files, or photos</span>
                <input type="file" name="file_upload" class="hidden" accept=".pdf,.dwg,.dxf,.step,.stp,.jpg,.jpeg,.png"
                  onchange="document.getElementById('file-label-text').textContent = this.files[0] ? this.files[0].name : 'Attach drawings, CAD files, or photos'" />
              </label>
            </div>

            <!-- Terms Checkbox -->
            <div class="flex items-center gap-3">
              <div class="w-5 h-5 rounded-full border-2 border-[#142F32] flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-[#142F32]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              <label class="text-[#474C59] text-[14px] font-[Montserrat] cursor-pointer">
                I agree to the <a href="#terms" class="text-[#142F32] font-semibold underline hover:no-underline">Terms &amp; Privacy Policy</a>
              </label>
              <input type="checkbox" name="agree_terms" required class="hidden" checked />
            </div>

            <div class="pt-1">
              <button type="submit"
                class="inline-flex items-center gap-2 bg-[#142F32] text-white px-8 py-3.5 rounded-full text-[16px] font-semibold font-[Montserrat] hover:bg-[#0f2326] transition-colors duration-300">
                Send Message
                <svg class="w-4 h-4 rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
              </button>
            </div>

          </form>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</section>

</main>

<!-- ========= MODAL SCRIPT ========= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  var modal     = document.getElementById('terms-modal');
  var backdrop  = document.getElementById('terms-backdrop');
  var closeBtn  = document.getElementById('terms-close');
  var acceptBtn = document.getElementById('terms-accept');

  if ( ! modal ) return;

  function openModal(e) {
    e.preventDefault();
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }

  document.querySelectorAll('a[href="#terms"]').forEach(function(el) {
    el.addEventListener('click', openModal);
  });

  if (backdrop)  backdrop.addEventListener('click', closeModal);
  if (closeBtn)  closeBtn.addEventListener('click', closeModal);
  if (acceptBtn) acceptBtn.addEventListener('click', closeModal);

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
  });
});
</script>

<?php get_footer(); ?>