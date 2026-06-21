<?php
/**
 * Template Name: Terms and Conditions
 * Template Post Type: page
 */
get_header();
?>

<main class="im-page-main">

<!-- ========= HERO SECTION ========= -->
<section class="relative min-h-[265px] w-full flex flex-col items-center bg-center bg-cover"
    style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/Services/Injection Moulding.png');">
</section>

<!-- ========= TERMS SECTION ========= -->
<section class="reveal py-16 bg-[#F5F6FA]">
  <div class="max-w-4xl mx-auto px-6 lg:px-10">

    <!-- Badge -->
    <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#D7FF80] rounded-full mb-5">
      <span class="w-2 h-2 rounded-full bg-[#19191A]"></span>
      <span class="text-[14px] font-medium font-[Poppins] text-[#19191A]">Legal</span>
    </div>

    <h1 class="text-[32px] lg:text-[40px] font-bold font-[Helvetica] text-[#19191A] leading-tight mb-3">
      Terms &amp; Conditions
    </h1>
    <p class="text-[#767C8C] text-[14px] font-[Montserrat] mb-10">Last updated: June 2026</p>

    <!-- Content Card -->
    <div class="bg-white border border-[#E8EAEF] rounded-3xl p-8 lg:p-12 shadow-sm space-y-10">

      <!-- 1 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">1. Introduction</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed">
          Welcome to Mould Injection (<strong>mouldinjection.co.uk</strong>). By accessing or using our platform, you agree to be bound by these Terms and Conditions. Please read them carefully before using our services. If you do not agree with any part of these terms, you must not use our platform.
        </p>
      </div>

      <!-- 2 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">2. About the Platform</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed">
          Mould Injection is an online platform that connects tool owners with injection moulding manufacturers across the United Kingdom and overseas. We act as an intermediary and do not directly manufacture, own, or guarantee the quality of any tools, products, or services exchanged between parties on this platform.
        </p>
      </div>

      <!-- 3 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">3. Eligibility</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed mb-3">
          To use our platform, you must:
        </p>
        <ul class="space-y-2">
          <?php foreach ([
            'Be at least 18 years of age.',
            'Be a registered business or act on behalf of a registered business.',
            'Provide accurate and complete information when registering or submitting enquiries.',
            'Comply with all applicable laws and regulations in your jurisdiction.',
          ] as $item) : ?>
          <li class="flex items-start gap-3">
            <div class="w-5 h-5 rounded-full bg-[#142F32] flex items-center justify-center flex-shrink-0 mt-0.5">
              <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            <span class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed"><?php echo $item; ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- 4 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">4. Use of the Platform</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed mb-3">You agree not to:</p>
        <ul class="space-y-2">
          <?php foreach ([
            'Use the platform for any unlawful, fraudulent, or harmful purpose.',
            'Submit false, misleading, or inaccurate information.',
            'Infringe the intellectual property rights of any third party.',
            'Attempt to gain unauthorised access to any part of the platform or its systems.',
            'Upload or transmit any viruses, malware, or other harmful software.',
            'Use the platform to send unsolicited commercial communications (spam).',
          ] as $item) : ?>
          <li class="flex items-start gap-3">
            <div class="w-1.5 h-1.5 rounded-full bg-[#142F32] flex-shrink-0 mt-2.5"></div>
            <span class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed"><?php echo $item; ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- 5 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">5. Enquiries and Introductions</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed">
          When you submit an enquiry through our platform, you agree that the information provided may be shared with relevant manufacturing partners or tool owners for the purpose of facilitating a business introduction. Mould Injection does not guarantee that any introduction will result in a business arrangement. All commercial agreements are made directly between the parties involved, and we accept no liability for the outcome of any such arrangements.
        </p>
      </div>

      <!-- 6 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">6. Intellectual Property</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed">
          All content on this platform — including but not limited to text, graphics, logos, images, and software — is the property of Mould Injection or its content suppliers and is protected by applicable intellectual property laws. You may not reproduce, distribute, or create derivative works from any content on this platform without our express written permission.
        </p>
      </div>

      <!-- 7 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">7. Privacy and Data Protection</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed">
          We are committed to protecting your personal data in accordance with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018. By using this platform, you consent to the collection and processing of your personal data as described in our Privacy Policy. We will never sell your personal data to third parties.
        </p>
      </div>

      <!-- 8 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">8. Disclaimer of Warranties</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed">
          The platform is provided on an "as is" and "as available" basis. Mould Injection makes no warranties, express or implied, regarding the availability, accuracy, reliability, or suitability of the platform or its content. We do not warrant that the platform will be uninterrupted, error-free, or free from viruses or other harmful components.
        </p>
      </div>

      <!-- 9 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">9. Limitation of Liability</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed">
          To the fullest extent permitted by law, Mould Injection shall not be liable for any direct, indirect, incidental, special, or consequential damages arising from your use of, or inability to use, this platform or any services connected to it. This includes, without limitation, loss of revenue, loss of profits, loss of data, or business interruption.
        </p>
      </div>

      <!-- 10 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">10. Third-Party Links</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed">
          Our platform may contain links to third-party websites. These links are provided for your convenience only. We have no control over the content of those sites and accept no responsibility for them or for any loss or damage that may arise from your use of them.
        </p>
      </div>

      <!-- 11 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">11. Changes to These Terms</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed">
          We reserve the right to update or modify these Terms and Conditions at any time without prior notice. Changes will be effective immediately upon posting to the platform. Your continued use of the platform following any changes constitutes your acceptance of the revised terms. We encourage you to review this page periodically.
        </p>
      </div>

      <!-- 12 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">12. Governing Law</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed">
          These Terms and Conditions are governed by and construed in accordance with the laws of England and Wales. Any disputes arising out of or in connection with these terms shall be subject to the exclusive jurisdiction of the courts of England and Wales.
        </p>
      </div>

      <!-- 13 -->
      <div>
        <h2 class="text-[20px] font-bold font-[Helvetica] text-[#19191A] mb-3">13. Contact Us</h2>
        <p class="text-[#474C59] text-[15px] font-[Montserrat] leading-relaxed mb-4">
          If you have any questions about these Terms and Conditions, please contact us:
        </p>
        <div class="bg-[#F5F6FA] rounded-2xl p-6 space-y-3">
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-[#142F32] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span class="text-[#474C59] text-[15px] font-[Montserrat]">Info@mouldinjection.co.uk</span>
          </div>
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-[#142F32] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="text-[#474C59] text-[15px] font-[Montserrat]">United Kingdom</span>
          </div>
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-[#142F32] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
            </svg>
            <span class="text-[#474C59] text-[15px] font-[Montserrat]">mouldinjection.co.uk</span>
          </div>
        </div>
      </div>

    </div>

  </div>
</section>

</main>

<?php get_footer(); ?>