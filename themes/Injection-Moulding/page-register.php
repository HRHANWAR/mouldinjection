<?php
/**
 * Template Name: Register / Login
 */

$tab     = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'register';
$errors  = [];
$success = false;

// ═══════════════════════════════════════════════════════════════
// HELPER: Generate Unique User ID
// Format: [Name 3 chars][City 3 chars][3-digit counter]
// Example: John + Lahore = JOHLAH001
// ═══════════════════════════════════════════════════════════════
if ( ! function_exists('ih_generate_unique_id') ) {
    function ih_generate_unique_id( $contact_name, $city = '' ) {
        // Clean and extract first 3 letters from name
        $name_clean = preg_replace( '/[^a-zA-Z]/', '', $contact_name );
        $name_part  = strtoupper( substr( $name_clean, 0, 3 ) );
        $name_part  = str_pad( $name_part, 3, 'X' ); // pad if name too short

        // Clean and extract first 3 letters from city/location
        $city_clean = preg_replace( '/[^a-zA-Z]/', '', $city );
        $city_part  = strtoupper( substr( $city_clean, 0, 3 ) );
        $city_part  = str_pad( $city_part, 3, 'X' ); // pad if city too short

        $base    = $name_part . $city_part; // e.g. JOHLAH
        $counter = 1;

        // Keep incrementing until we find a unique ID
        do {
            $candidate = $base . str_pad( $counter, 3, '0', STR_PAD_LEFT ); // JOHLAH001
            $existing  = get_users( [
                'meta_key'   => 'ih_unique_id',
                'meta_value' => $candidate,
                'number'     => 1,
                'fields'     => 'ids',
            ] );
            $counter++;
        } while ( ! empty( $existing ) && $counter < 9999 );

        return $candidate; // e.g. JOHLAH001
    }
}

// ═══════════════════════════════════════
// REGISTER HANDLE
// ═══════════════════════════════════════
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_nonce']) ) {
    if ( ! wp_verify_nonce($_POST['register_nonce'], 'im_register') ) {
        $errors[] = 'Security check failed. Please try again.';
    } elseif ( ! empty( $_POST['ih_hp'] ) ) {
        // Honeypot filled — silent bot rejection
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $biz_role   = sanitize_text_field($_POST['biz_role']      ?? '');
        $company    = sanitize_text_field($_POST['company_name']  ?? '');
        $contact    = sanitize_text_field($_POST['contact_name']  ?? '');
        $job_title  = sanitize_text_field($_POST['job_title']     ?? '');
        $address    = sanitize_text_field($_POST['address']       ?? '');
        $city       = sanitize_text_field($_POST['city']          ?? '');
        $postcode   = sanitize_text_field($_POST['postcode']      ?? '');
        $office_num = sanitize_text_field($_POST['office_number'] ?? '');
        $website    = esc_url_raw($_POST['website']               ?? '');
        $phone      = sanitize_text_field($_POST['phone']         ?? '');
        $email      = sanitize_email($_POST['email']              ?? '');
        $email_conf = sanitize_email($_POST['email_confirm']      ?? '');
        $password   = $_POST['password']          ?? '';
        $pass_conf  = $_POST['password_confirm']  ?? '';

        if ( empty($biz_role) )         $errors[] = 'Please select a business role.';
        if ( empty($company) )          $errors[] = 'Company name is required.';
        if ( empty($contact) )          $errors[] = 'Contact name is required.';
        if ( empty($email) )            $errors[] = 'Email address is required.';
        if ( ! is_email($email) )       $errors[] = 'Please enter a valid email address.';
        if ( $email !== $email_conf )   $errors[] = 'Email addresses do not match.';
        if ( empty($password) )         $errors[] = 'Password is required.';
        if ( strlen($password) < 8 )    $errors[] = 'Password must be at least 8 characters.';
        if ( $password !== $pass_conf ) $errors[] = 'Passwords do not match.';
        if ( email_exists($email) )     $errors[] = 'This email address is already registered.';
        if ( empty($_POST['agree_terms']) ) $errors[] = 'You must agree to the Terms & Conditions.';
        if ( empty($_POST['agree_fees']) )  $errors[] = 'You must understand that fees may apply.';

        if ( empty($errors) ) {
            $username      = sanitize_user( strtolower( str_replace(' ', '.', $contact) ) );
            $base_username = $username;
            $suffix        = 1;
            while ( username_exists($username) ) { $username = $base_username . $suffix; $suffix++; }

            $user_id = wp_create_user( $username, $password, $email );

            if ( is_wp_error($user_id) ) {
                $errors[] = $user_id->get_error_message();
            } else {

                // ── Set Gravatar / Avatar ──────────────────────────────
                // ── Fetch & Save Profile Image from Gravatar ──────────────────
$email_hash     = md5( strtolower( trim( $email ) ) );
$gravatar_url   = 'https://www.gravatar.com/avatar/' . $email_hash . '?s=300&d=404';

// Check if a real Gravatar exists (d=404 returns 404 if no image)
$gravatar_check = wp_remote_head( $gravatar_url );
$gravatar_code  = wp_remote_retrieve_response_code( $gravatar_check );

if ( $gravatar_code === 200 ) {
    // Real Gravatar exists — download and save to media library
    $upload_dir  = wp_upload_dir();
    $image_data  = wp_remote_retrieve_body( wp_remote_get( $gravatar_url ) );
    $filename    = 'profile-' . $user_id . '-' . time() . '.jpg';
    $file_path   = $upload_dir['path'] . '/' . $filename;

    file_put_contents( $file_path, $image_data );

    $attachment_id = wp_insert_attachment([
        'guid'           => $upload_dir['url'] . '/' . $filename,
        'post_mime_type' => 'image/jpeg',
        'post_title'     => sanitize_file_name( $filename ),
        'post_status'    => 'inherit',
    ], $file_path, 0 );

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
    wp_update_attachment_metadata( $attachment_id, $attach_data );

    // Save attachment ID and URL
    update_user_meta( $user_id, 'ih_profile_image_id',  $attachment_id );
    update_user_meta( $user_id, 'ih_profile_image',     wp_get_attachment_url( $attachment_id ) );

} else {
    // No Gravatar — save a generated avatar (initials-based fallback via UI Avatars)
    $initials_name   = urlencode( $contact );
    $fallback_avatar = 'https://ui-avatars.com/api/?name=' . $initials_name . '&size=300&background=153F45&color=ffffff&bold=true';
    update_user_meta( $user_id, 'ih_profile_image', $fallback_avatar );
}

                // ── Display name & roles ───────────────────────────────
                wp_update_user([
                    'ID'           => $user_id,
                    'display_name' => $contact,
                    'first_name'   => explode(' ', $contact)[0] ?? $contact,
                    'last_name'    => explode(' ', $contact)[1] ?? '',
                ]);

                $user_obj = new WP_User($user_id);
                $role_map = [
                    'Manufacturer'      => 'manufacturer',
                    'Tool Owner'        => 'tool_owner',
                    'Product Developer' => 'tool_owner',
                    'Startup'           => 'subscriber',
                    'Overseas Buyer'    => 'subscriber',
                    'Other'             => 'subscriber',
                ];
                $user_obj->set_role( $role_map[$biz_role] ?? 'subscriber' );

                // ── Save standard meta ─────────────────────────────────
                update_user_meta($user_id, 'business_role', $biz_role);
                update_user_meta($user_id, 'company_name',  $company);
                update_user_meta($user_id, 'job_title',     $job_title);
                update_user_meta($user_id, 'address',       $address);
                update_user_meta($user_id, 'city',          $city);
                update_user_meta($user_id, 'postcode',      $postcode);
                update_user_meta($user_id, 'office_number', $office_num);
                update_user_meta($user_id, 'website',       $website);
                update_user_meta($user_id, 'phone',         $phone);

                // ── GDPR Art. 7 consent audit record ───────────────────
                // Proof of consent: who, when, what wording version, how.
                update_user_meta($user_id, 'ih_consent_record', [
                    'agree_terms' => 1,
                    'agree_fees'  => 1,
                    'policy_ver'  => 'privacy-policy-v1',
                    'timestamp'   => current_time('mysql'),
                    'ip'          => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
                    'method'      => 'register-form-checkbox',
                ]);
                update_user_meta($user_id, 'terms_accepted', 1);
                update_user_meta($user_id, 'fees_accepted',  1);

                // ── Generate & save UNIQUE USER ID ────────────────────
                // Format: [Name 3][City 3][Counter 3]  e.g.  JOHLAH001
                $ih_unique_id = ih_generate_unique_id( $contact, $city );
                update_user_meta( $user_id, 'ih_unique_id', $ih_unique_id );

                // ── Insert profile row ─────────────────────────────────
                global $wpdb;
                $wpdb->insert( "{$wpdb->prefix}ih_profiles", [
                    'user_id'        => $user_id,
                    'business_role'  => $biz_role,
                    'company_name'   => $company,
                    'job_title'      => $job_title,
                    'phone'          => $phone,
                    'address'        => $address,
                    'city'           => $city,
                    'postcode'       => $postcode,
                    'website'        => $website,
                    'whatsapp'       => $phone,
                    'account_status' => 'active',
                    'unique_id'      => $ih_unique_id,  // also store in profile table
                ]);

                // ── Auth cookie ───────────────────────────────────────
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id, true);

                // ── Welcome email (includes their unique ID) ──────────
                wp_mail(
                    $email,
                    'Welcome to Injection Moulding Platform!',
                    "Hi {$contact},\n\n" .
                    "Your account has been created successfully.\n\n" .
                    "Your Unique Platform ID: {$ih_unique_id}\n" .
                    "(Keep this ID safe — others will use it to find and message you.)\n\n" .
                    "Login: " . home_url('/register/?tab=login') . "\n\n" .
                    "Regards,\nInjection Moulding Team"
                );

                wp_redirect( admin_url('admin.php?page=ih-user-dashboard') );
                exit;
            }
        }
    }
}

// ═══════════════════════════════════════
// LOGIN HANDLE
// ═══════════════════════════════════════
$login_error = '';
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_nonce']) ) {
    if ( ! wp_verify_nonce($_POST['login_nonce'], 'im_login') || ! empty( $_POST['ih_hp'] ) ) {
        $login_error = 'Security check failed.';
    } else {
        $login_email = sanitize_email($_POST['login_email']   ?? '');
        $login_pass  = $_POST['login_password'] ?? '';

        if ( empty($login_email) || empty($login_pass) ) {
            $login_error = 'Please enter your email and password.';
        } else {
            $user = get_user_by('email', $login_email);
            if ( ! $user ) {
                // Generic message — prevents account enumeration
                $login_error = 'Invalid email or password. Please try again.';
            } else {
                $result = wp_signon([
                    'user_login'    => $user->user_login,
                    'user_password' => $login_pass,
                    'remember'      => isset($_POST['remember']),
                ], false);

                if ( is_wp_error($result) ) {
                    $login_error = 'Invalid email or password. Please try again.';
                } else {
                    if ( in_array('administrator', (array) $result->roles) ) {
                        wp_redirect( admin_url('admin.php?page=ih-dashboard') );
                    } else {
                        wp_redirect( admin_url('admin.php?page=ih-user-dashboard') );
                    }
                    exit;
                }
            }
        }
    }
}

get_header(); ?>

<style>
.auth-page { background: linear-gradient(180deg, #00191C 0%, #00191C 260px, #f5f6fa 260px); padding: 60px 16px 80px; margin-top: 90px; font-family: 'Inter', 'Poppins', sans-serif; }
.auth-container { max-width: 800px; margin: 0 auto; }
.auth-head { text-align: center; margin-bottom: 24px; }
.auth-head-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(200,255,0,.08); border: 1px solid rgba(200,255,0,.25); color: #C8FF00; font-size: 11.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; border-radius: 999px; padding: 6px 16px; margin-bottom: 14px; font-family: 'Poppins', sans-serif; }
.auth-head-badge::before { content: ''; width: 7px; height: 7px; border-radius: 99px; background: #C8FF00; }
.auth-head h1 { color: #fff; font-size: clamp(26px, 4vw, 38px); font-weight: 700; margin: 0 0 8px; }
.auth-head p { color: #C8CCD9; font-size: 14px; margin: 0; }
.auth-tabs { display: flex; justify-content: center; margin-bottom: 28px; background: white; border-radius: 999px; padding: 4px; width: fit-content; margin-left: auto; margin-right: auto; box-shadow: 0 6px 24px rgba(0,25,28,.25); }
.auth-tab { padding: 10px 40px; border-radius: 999px; font-size: 15px; font-weight: 600; text-decoration: none; color: #666; transition: all 0.2s; border: none; background: transparent; cursor: pointer; font-family: 'Poppins', sans-serif; display: inline-flex; align-items: center; gap: 8px; }
.auth-tab.active { background: #153F45; color: #C8FF00; }
.auth-tab svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; }
.auth-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 18px 50px -18px rgba(0,25,28,.35); border: 1px solid #e9edf2; }
.ih-hp-field { position: absolute !important; left: -9999px !important; width: 1px; height: 1px; overflow: hidden; opacity: 0; }
.auth-strength { display: flex; gap: 5px; margin-top: 7px; }
.auth-strength span { flex: 1; height: 4px; border-radius: 99px; background: #e5e7eb; transition: background .25s; }
.auth-strength.s1 span:nth-child(1) { background: #dc2626; }
.auth-strength.s2 span:nth-child(-n+2) { background: #f59e0b; }
.auth-strength.s3 span:nth-child(-n+3) { background: #84cc16; }
.auth-strength.s4 span { background: #16a34a; }
.auth-strength-label { font-size: 11.5px; color: #888; margin-top: 4px; font-family: 'Poppins', sans-serif; }
.auth-btn[disabled] { opacity: .65; cursor: wait; }
.auth-btn .btn-spinner { display: none; width: 15px; height: 15px; border: 2px solid rgba(255,255,255,.35); border-top-color: #fff; border-radius: 50%; animation: authspin .7s linear infinite; }
.auth-btn.loading .btn-spinner { display: inline-block; }
@keyframes authspin { to { transform: rotate(360deg); } }
.auth-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
@media(max-width:600px){ .auth-grid { grid-template-columns: 1fr; } .auth-card { padding: 24px 16px; } }
.auth-full { grid-column: 1 / -1; }
.auth-field { display: flex; flex-direction: column; gap: 6px; }
.auth-label { font-size: 13px; font-weight: 600; color: #333; font-family: 'Poppins', sans-serif; }
.auth-label span { color: #dc2626; }
.auth-input, .auth-select { padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; color: #333; outline: none; font-family: 'Poppins', sans-serif; transition: border-color 0.2s; background: white; width: 100%; }
.auth-input:focus, .auth-select:focus { border-color: #153F45; }
.auth-input::placeholder { color: #c0c0c0; }
.pass-wrap { position: relative; }
.pass-wrap .auth-input { padding-right: 44px; }
.pass-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #aaa; padding: 4px; display: flex; align-items: center; }
.pass-toggle svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2; }
.select-wrap { position: relative; }
.select-wrap::after { content: ''; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 0; height: 0; border-left: 4px solid transparent; border-right: 4px solid transparent; border-top: 5px solid #666; pointer-events: none; }
.auth-select { appearance: none; padding-right: 36px; }
.auth-btn { background: #153F45; color: white; padding: 12px 36px; border-radius: 106px; font-size: 15px; font-weight: 600; border: none; cursor: pointer; font-family: 'Poppins', sans-serif; transition: background 0.2s; margin-top: 8px; display: inline-flex; align-items: center; gap: 8px; }
.auth-btn:hover { background: #0f2e2e; }
.auth-errors { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 16px; margin-bottom: 20px; }
.auth-errors p { font-size: 13px; color: #dc2626; font-family: 'Poppins', sans-serif; margin-bottom: 4px; }
.auth-errors p:last-child { margin-bottom: 0; }
.login-form { max-width: 480px; margin: 0 auto; display: flex; flex-direction: column; gap: 16px; align-items: stretch; }
.login-form .auth-field, .login-form .auth-input { width: 100%; }
.login-form .auth-btn { width: fit-content; align-self: flex-start; }
.forgot-link { font-size: 13px; color: #153F45; text-decoration: none; text-align: right; font-family: 'Poppins', sans-serif; }
.forgot-link:hover { text-decoration: underline; }
.auth-divider { text-align: center; font-size: 13px; color: #888; margin-top: 8px; font-family: 'Poppins', sans-serif; }
.auth-divider a { color: #153F45; font-weight: 600; text-decoration: none; }

/* ── Unique ID badge shown after registration ── */
.ih-id-banner {
    background: linear-gradient(135deg, #153F45, #1f6b56);
    color: #fff; border-radius: 12px; padding: 18px 24px;
    margin-bottom: 24px; display: flex; align-items: center; gap: 16px;
}
.ih-id-banner .badge-icon { font-size: 32px; }
.ih-id-banner h4 { margin: 0 0 4px; font-size: 14px; font-weight: 600; opacity: .85; font-family:'Poppins',sans-serif; }
.ih-id-badge-value { font-size: 26px; font-weight: 800; letter-spacing: 3px; font-family: monospace; color: #c8e88e; }
.ih-id-banner p { margin: 6px 0 0; font-size: 12px; opacity: .75; font-family:'Poppins',sans-serif; }

/* ── Data Protection / GDPR panel ── */
.auth-gdpr { margin-top: 18px; border: 1px solid #dbe7e2; border-radius: 14px; background: #f6faf8; padding: 16px; font-family: 'Poppins', sans-serif; }
.auth-gdpr-head { display: flex; align-items: flex-start; gap: 14px; }
.auth-gdpr-logo { width: 64px; height: 64px; object-fit: contain; flex-shrink: 0; }
.auth-gdpr-title { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 14px; font-weight: 700; color: #153F45; }
.auth-gdpr-badge { display: inline-flex; align-items: center; gap: 4px; border-radius: 999px; background: #153F45; color: #c8e88e; padding: 3px 9px; font-size: 10px; font-weight: 700; letter-spacing: .04em; }
.auth-gdpr-sub { margin: 5px 0 0; font-size: 12px; color: #5b6f6a; line-height: 1.55; }
.auth-gdpr-sub a { color: #153F45; font-weight: 600; }
.auth-gdpr-stats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 9px; margin-top: 14px; }
.auth-gdpr-stat { display: flex; flex-direction: column; align-items: flex-start; gap: 3px; border: 1px solid #e2ece7; border-radius: 11px; background: #fff; padding: 10px 11px; }
.auth-gdpr-ico { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; background: #ecf5f0; color: #153F45; margin-bottom: 3px; }
.auth-gdpr-ico svg { width: 15px; height: 15px; }
.auth-gdpr-stat strong { font-size: 15px; font-weight: 800; color: #153F45; font-family: 'Roboto Mono', monospace, 'Poppins', sans-serif; }
.auth-gdpr-stat small { font-size: 10.5px; color: #6b7f7a; line-height: 1.35; }
.auth-gdpr-consents { display: flex; flex-direction: column; gap: 9px; margin-top: 14px; }
.auth-gdpr-consent { display: flex; align-items: flex-start; gap: 9px; font-size: 12.5px; color: #333; line-height: 1.5; cursor: pointer; }
.auth-gdpr-consent input { width: 16px; height: 16px; margin-top: 2px; flex-shrink: 0; accent-color: #153F45; }
.auth-gdpr-consent a { color: #153F45; font-weight: 600; }
@media (max-width: 600px) {
  .auth-gdpr-stats { grid-template-columns: repeat(2, minmax(0,1fr)); }
  .auth-gdpr-head { flex-direction: column; }
}
</style>

<div class="auth-page">
  <div class="auth-container">

    <div class="auth-head">
      <span class="auth-head-badge">Member Access</span>
      <?php if ( $tab === 'register' ) : ?>
        <h1>Create your account</h1>
        <p>Join the UK's injection moulding marketplace — list machines &amp; tools, message manufacturers.</p>
      <?php else : ?>
        <h1>Welcome back</h1>
        <p>Log in to manage your listings, messages and contact requests.</p>
      <?php endif; ?>
    </div>

    <div class="auth-tabs">
      <a href="?tab=login" class="auth-tab <?php echo $tab==='login' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Login
      </a>
      <a href="?tab=register" class="auth-tab <?php echo $tab==='register' ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
        Register
      </a>
    </div>

    <?php if ( $tab === 'register' ) : ?>
    <div class="auth-card">

      <?php if ( ! empty($errors) ) : ?>
        <div class="auth-errors">
          <?php foreach ( $errors as $e ) : ?><p>⚠️ <?php echo esc_html($e); ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="registerForm">
        <?php wp_nonce_field('im_register', 'register_nonce'); ?>
        <div class="ih-hp-field" aria-hidden="true">
          <label>Leave this field empty<input type="text" name="ih_hp" tabindex="-1" autocomplete="off"></label>
        </div>
        <div class="auth-grid">

          <div class="auth-field">
            <label class="auth-label">Choose Business Role <span>*</span></label>
            <div class="select-wrap">
              <select name="biz_role" class="auth-select" required>
                <option value="" disabled <?php selected($_POST['biz_role'] ?? '', ''); ?>>Select role</option>
                <?php foreach(['Manufacturer','Tool Owner','Product Developer','Startup','Overseas Buyer','Other'] as $role) : ?>
                  <option value="<?php echo esc_attr($role); ?>" <?php selected($_POST['biz_role'] ?? '', $role); ?>><?php echo esc_html($role); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="auth-field">
            <label class="auth-label">Company Name <span>*</span></label>
            <input type="text" name="company_name" class="auth-input" placeholder="Company name" required value="<?php echo esc_attr($_POST['company_name'] ?? ''); ?>">
          </div>

          <div class="auth-field">
            <label class="auth-label">Contact Name <span>*</span></label>
            <input type="text" name="contact_name" id="reg-contact-name" class="auth-input" placeholder="Contact name" required value="<?php echo esc_attr($_POST['contact_name'] ?? ''); ?>">
          </div>

          <div class="auth-field">
            <label class="auth-label">Job Title</label>
            <input type="text" name="job_title" class="auth-input" placeholder="Job title" value="<?php echo esc_attr($_POST['job_title'] ?? ''); ?>">
          </div>

          <div class="auth-field">
            <label class="auth-label">Address</label>
            <input type="text" name="address" class="auth-input" placeholder="Address/Location" value="<?php echo esc_attr($_POST['address'] ?? ''); ?>">
          </div>

          <div class="auth-field">
            <label class="auth-label">Town / City <span style="color:#153F45;font-weight:400;font-size:11px;">(used for your ID)</span></label>
            <input type="text" name="city" id="reg-city" class="auth-input" placeholder="Town/city" value="<?php echo esc_attr($_POST['city'] ?? ''); ?>">
          </div>

          <div class="auth-field">
            <label class="auth-label">Postcode</label>
            <input type="text" name="postcode" class="auth-input" placeholder="Postcode" value="<?php echo esc_attr($_POST['postcode'] ?? ''); ?>">
          </div>

          <div class="auth-field">
            <label class="auth-label">Office Number</label>
            <input type="text" name="office_number" class="auth-input" placeholder="Office number" value="<?php echo esc_attr($_POST['office_number'] ?? ''); ?>">
          </div>

          <div class="auth-field">
            <label class="auth-label">Website URL</label>
            <input type="url" name="website" class="auth-input" placeholder="https://www.example.com" value="<?php echo esc_attr($_POST['website'] ?? ''); ?>">
          </div>

          <div class="auth-field">
            <label class="auth-label">WhatsApp Number</label>
            <input type="tel" name="phone" class="auth-input" placeholder="WhatsApp number" value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>">
          </div>

          <div class="auth-field">
            <label class="auth-label">Email Address <span>*</span></label>
            <input type="email" name="email" class="auth-input" placeholder="Email address" required value="<?php echo esc_attr($_POST['email'] ?? ''); ?>">
          </div>

          <div class="auth-field">
            <label class="auth-label">Confirm Email <span>*</span></label>
            <input type="email" name="email_confirm" class="auth-input" placeholder="Confirm email address" required>
          </div>

          <div class="auth-field">
            <label class="auth-label">Password <span>*</span></label>
            <div class="pass-wrap">
              <input type="password" name="password" id="password" class="auth-input" placeholder="Password" required minlength="8" autocomplete="new-password">
              <button type="button" class="pass-toggle" onclick="togglePass('password')">
                <svg id="eye-password" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="auth-strength" id="pass-strength" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <div class="auth-strength-label" id="pass-strength-label"></div>
          </div>

          <div class="auth-field">
            <label class="auth-label">Confirm Password <span>*</span></label>
            <div class="pass-wrap">
              <input type="password" name="password_confirm" id="password_confirm" class="auth-input" placeholder="Confirm password" required>
              <button type="button" class="pass-toggle" onclick="togglePass('password_confirm')">
                <svg id="eye-password_confirm" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div id="pass-message" style="font-size:13px;margin-top:5px;"></div>
          </div>

        </div>

        <!-- ID Preview (live, JS-generated) -->
        <div id="id-preview-wrap" style="margin-bottom:18px;display:none;">
          <div style="background:#f0fdf4;border:1.5px dashed #86efac;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:12px;">
            <span style="font-size:22px;">🪪</span>
            <div>
              <div style="font-size:11px;color:#666;font-family:'Poppins',sans-serif;margin-bottom:2px;">Your Platform ID will be (approx.):</div>
              <div id="id-preview-value" style="font-size:20px;font-weight:800;letter-spacing:2px;color:#153F45;font-family:monospace;"></div>
              <div style="font-size:11px;color:#888;font-family:'Poppins',sans-serif;margin-top:2px;">Final ID assigned on registration</div>
            </div>
          </div>
        </div>

        <!-- ── Data Protection / GDPR panel ── -->
        <div class="auth-gdpr" id="authGdprPanel">
          <div class="auth-gdpr-head">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/GDPR.png" alt="GDPR compliant" class="auth-gdpr-logo">
            <div>
              <div class="auth-gdpr-title">
                Data Protection &middot; GDPR Compliant
                <span class="auth-gdpr-badge">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="11" height="11"><polyline points="20 6 9 17 4 12"/></svg>
                  UK &amp; EU
                </span>
              </div>
              <p class="auth-gdpr-sub">Your details are processed under our <a href="/privacy-policy" target="_blank">Data Protection Policy</a>. You stay in control &mdash; consent is logged, and you can withdraw or request erasure at any time.</p>
            </div>
          </div>

          <div class="auth-gdpr-stats" id="authGdprStats">
            <div class="auth-gdpr-stat">
              <span class="auth-gdpr-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </span>
              <strong><span data-count="256">0</span>-bit</strong>
              <small>SSL/TLS encryption</small>
            </div>
            <div class="auth-gdpr-stat">
              <span class="auth-gdpr-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              </span>
              <strong><span data-count="0">0</span> resale</strong>
              <small>Data never sold on</small>
            </div>
            <div class="auth-gdpr-stat">
              <span class="auth-gdpr-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              </span>
              <strong><span data-count="30">0</span> days</strong>
              <small>Erasure on request</small>
            </div>
            <div class="auth-gdpr-stat">
              <span class="auth-gdpr-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="m9 15 2 2 4-4"/></svg>
              </span>
              <strong><span data-count="100">0</span>%</strong>
              <small>Consent logged &amp; timestamped</small>
            </div>
          </div>

          <div class="auth-gdpr-consents">
            <label class="auth-gdpr-consent">
              <input type="checkbox" name="agree_terms" required>
              <span>I confirm I have read and agree to the <a href="/terms-conditions" target="_blank">Terms &amp; Conditions</a> and <a href="/privacy-policy" target="_blank">Privacy Policy</a>.</span>
            </label>
            <label class="auth-gdpr-consent">
              <input type="checkbox" name="agree_fees" required>
              <span>I understand fees may apply for introductions, successful projects, or premium services.</span>
            </label>
          </div>
        </div>

        <button type="submit" class="auth-btn" id="registerBtn" style="margin-top:20px;">
          <span class="btn-spinner"></span>
          Register
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
        </button>
      </form>
    </div>

    <?php else : ?>
    <div class="auth-card">
      <?php if ( $login_error ) : ?>
        <div class="auth-errors"><p>⚠️ <?php echo esc_html($login_error); ?></p></div>
      <?php endif; ?>

      <form method="POST" class="login-form" id="loginForm">
        <?php wp_nonce_field('im_login', 'login_nonce'); ?>
        <div class="ih-hp-field" aria-hidden="true">
          <label>Leave this field empty<input type="text" name="ih_hp" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="auth-field">
          <label class="auth-label">Email Address <span>*</span></label>
          <input type="email" name="login_email" class="auth-input" placeholder="Enter your email" required autocomplete="email" value="<?php echo esc_attr($_POST['login_email'] ?? ''); ?>">
        </div>

        <div class="auth-field">
          <label class="auth-label">Password <span>*</span></label>
          <div class="pass-wrap">
            <input type="password" name="login_password" id="login_password" class="auth-input" placeholder="Enter your password" required>
            <button type="button" class="pass-toggle" onclick="togglePass('login_password')">
              <svg id="eye-login_password" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#555;cursor:pointer;font-family:'Poppins',sans-serif;">
            <input type="checkbox" name="remember" style="width:14px;height:14px;accent-color:#153F45;"> Remember me
          </label>
          <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="forgot-link">Forgot password?</a>
        </div>

        <button type="submit" class="auth-btn" id="loginBtn"><span class="btn-spinner"></span>Login</button>
        <p class="auth-divider">Don't have an account? <a href="?tab=register">Register here</a></p>
      </form>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
/* ── Password confirm check ── */
document.addEventListener("DOMContentLoaded", function() {
    var passField = document.getElementById('password');
    var confField = document.getElementById('password_confirm');
    var msg       = document.getElementById('pass-message');

    function checkMatch() {
        if (!passField || !confField || !msg) return;
        if (confField.value === '') { msg.innerHTML = ''; confField.style.borderColor = ''; return; }
        if (passField.value === confField.value) {
            confField.style.borderColor = 'green';
            msg.style.color = 'green';
            msg.innerHTML = '✔ Password matched';
        } else {
            confField.style.borderColor = '#dc2626';
            msg.style.color = '#dc2626';
            msg.innerHTML = '✖ Password does not match';
        }
    }

    if (passField) passField.addEventListener('input', checkMatch);
    if (confField) confField.addEventListener('input', checkMatch);

    /* ── Password strength meter ── */
    var strengthBar   = document.getElementById('pass-strength');
    var strengthLabel = document.getElementById('pass-strength-label');
    function scorePassword(v) {
        var s = 0;
        if (v.length >= 8)  s++;
        if (v.length >= 12) s++;
        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
        if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) s++;
        return s;
    }
    if (passField && strengthBar) {
        passField.addEventListener('input', function () {
            var v = passField.value;
            var s = v ? scorePassword(v) : 0;
            strengthBar.className = 'auth-strength' + (s ? ' s' + s : '');
            var labels = ['', 'Weak — add more characters', 'Fair — mix upper/lower case', 'Good — add a number or symbol', 'Strong password'];
            strengthLabel.textContent = v ? labels[s] : '';
        });
    }

    /* ── Submit loading states ── */
    function wireLoading(formId, btnId) {
        var form = document.getElementById(formId);
        var btn  = document.getElementById(btnId);
        if (!form || !btn) return;
        form.addEventListener('submit', function () {
            btn.classList.add('loading');
            btn.disabled = true;
            // re-enable if validation bounces the submit back (e.g. browser stays on page)
            setTimeout(function () { btn.classList.remove('loading'); btn.disabled = false; }, 8000);
        });
    }
    wireLoading('registerForm', 'registerBtn');
    wireLoading('loginForm', 'loginBtn');

    /* ── Live ID preview ── */
    var nameInput = document.getElementById('reg-contact-name');
    var cityInput = document.getElementById('reg-city');
    var previewWrap  = document.getElementById('id-preview-wrap');
    var previewValue = document.getElementById('id-preview-value');

    function updateIdPreview() {
        var name = (nameInput ? nameInput.value : '').replace(/[^a-zA-Z]/g, '');
        var city = (cityInput ? cityInput.value : '').replace(/[^a-zA-Z]/g, '');
        var np   = name.substr(0, 3).toUpperCase().padEnd(3, 'X');
        var cp   = city.substr(0, 3).toUpperCase().padEnd(3, 'X');
        if (name.length >= 1) {
            previewWrap.style.display  = 'block';
            previewValue.textContent   = np + cp + '###';
        } else {
            previewWrap.style.display  = 'none';
        }
    }

    if (nameInput) nameInput.addEventListener('input', updateIdPreview);
    if (cityInput) cityInput.addEventListener('input', updateIdPreview);
});

/* ── GDPR infographic count-up ── */
document.addEventListener('DOMContentLoaded', function () {
    var stats = document.getElementById('authGdprStats');
    if (!stats || !('IntersectionObserver' in window)) {
        if (stats) stats.querySelectorAll('[data-count]').forEach(function (el) { el.textContent = el.getAttribute('data-count'); });
        return;
    }
    var done = false;
    var io = new IntersectionObserver(function (entries) {
        if (done || !entries.some(function (e) { return e.isIntersecting; })) return;
        done = true;
        io.disconnect();
        stats.querySelectorAll('[data-count]').forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            var start = null, dur = 900;
            function step(ts) {
                if (start === null) start = ts;
                var p = Math.min(1, (ts - start) / dur);
                el.textContent = String(Math.round(target * (1 - Math.pow(1 - p, 3))));
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    }, { threshold: 0.4 });
    io.observe(stats);
});

/* ── Password eye toggle ── */
function togglePass(fieldId) {
    var input = document.getElementById(fieldId);
    var eye   = document.getElementById('eye-' + fieldId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        if (eye) eye.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        if (eye) eye.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}
</script>

<?php get_footer(); ?>