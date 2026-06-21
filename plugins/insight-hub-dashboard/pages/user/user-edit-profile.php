<?php
defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( admin_url('admin.php?page=ih-user-dashboard') ) );
    exit;
}
if ( current_user_can('administrator') ) {
    wp_redirect( admin_url('admin.php?page=ih-dashboard') );
    exit;
}

global $wpdb;
$user_id      = get_current_user_id();
$current_user = wp_get_current_user();
$nonce_key    = 'ih_edit_profile_' . $user_id;

/* ── Unread messages & notifs for header badges ── */
$unread_msgs = (int)$wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(unread) FROM {$wpdb->prefix}ih_threads WHERE user_id=%d", $user_id
));
$notifs = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND status='Approved' ORDER BY id DESC LIMIT 10",
    $user_id
), ARRAY_A ) ?: [];
$notif_count  = count($notifs);

$email_hash   = md5( strtolower( trim( $current_user->user_email ) ) );
$gravatar_url = function_exists( 'ih_get_user_avatar_url' ) ? ih_get_user_avatar_url( $user_id, 72 ) : 'https://www.gravatar.com/avatar/' . $email_hash . '?s=72&d=404';
$fallback_url = function_exists( 'ih_get_user_avatar_fallback_url' ) ? ih_get_user_avatar_fallback_url( $user_id, 72 ) : 'https://ui-avatars.com/api/?name=' . rawurlencode($current_user->display_name ?: 'U') . '&background=1f3d2e&color=c8e88e&size=72&bold=true&rounded=true&length=2';

/* ══════════════════════════════════════════════════
   SAVE HANDLER
══════════════════════════════════════════════════ */
$ih_ep_success = '';
$ih_ep_errors  = array();

/* ── DELETE ACCOUNT HANDLER (GDPR right to erasure) ── */
$del_nonce_key = 'ih_delete_account_' . $user_id;
if ( isset( $_POST['ih_del_nonce'] ) && wp_verify_nonce( $_POST['ih_del_nonce'], $del_nonce_key ) ) {
    $del_pass    = isset($_POST['delete_password']) ? $_POST['delete_password'] : '';
    $del_confirm = strtoupper( trim( sanitize_text_field( isset($_POST['delete_confirm']) ? $_POST['delete_confirm'] : '' ) ) );

    if ( $del_confirm !== 'DELETE' ) {
        $ih_ep_errors[] = 'Account deletion: type DELETE in the confirmation box to proceed.';
    } elseif ( empty($del_pass) || ! wp_check_password( $del_pass, $current_user->user_pass, $user_id ) ) {
        $ih_ep_errors[] = 'Account deletion: your password is incorrect.';
    } else {
        /* Erase platform data: chats, threads, requests, listings, profile row. */
        $thread_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ih_threads WHERE user_id=%d", $user_id
        ) );
        if ( $thread_ids ) {
            $in = implode( ',', array_map( 'intval', $thread_ids ) );
            $wpdb->query( "DELETE FROM {$wpdb->prefix}ih_chats WHERE thread_id IN ({$in})" );
        }
        $wpdb->delete( $wpdb->prefix . 'ih_threads',   array( 'user_id'  => $user_id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'ih_requests',  array( 'user_id'  => $user_id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'ih_machines',  array( 'owner_id' => $user_id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'ih_tools',     array( 'owner_id' => $user_id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'ih_user_meta', array( 'user_id'  => $user_id ), array( '%d' ) );

        /* Notify admin for the audit trail. */
        wp_mail(
            get_option( 'admin_email' ),
            'Account deleted — ' . $current_user->user_email,
            "User {$current_user->display_name} ({$current_user->user_email}, ID {$user_id}) deleted their account on " . current_time( 'mysql' ) . ".\nAll listings, messages and requests were erased."
        );

        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $user_id );
        wp_logout();
        wp_safe_redirect( home_url( '/?account_deleted=1' ) );
        exit;
    }
}

if ( isset( $_POST['ih_ep_nonce'] ) && wp_verify_nonce( $_POST['ih_ep_nonce'], $nonce_key ) ) {

    $contact_name  = sanitize_text_field( isset($_POST['contact_name'])  ? $_POST['contact_name']  : '' );
    $company_name  = sanitize_text_field( isset($_POST['company_name'])  ? $_POST['company_name']  : '' );
    $job_title     = sanitize_text_field( isset($_POST['job_title'])     ? $_POST['job_title']     : '' );
    $biz_role      = sanitize_text_field( isset($_POST['biz_role'])      ? $_POST['biz_role']      : '' );
    $bio           = sanitize_textarea_field( isset($_POST['bio'])       ? $_POST['bio']           : '' );
    $address       = sanitize_text_field( isset($_POST['address'])       ? $_POST['address']       : '' );
    $city          = sanitize_text_field( isset($_POST['city'])          ? $_POST['city']          : '' );
    $postcode      = sanitize_text_field( isset($_POST['postcode'])      ? $_POST['postcode']      : '' );
    $phone         = sanitize_text_field( isset($_POST['phone'])         ? $_POST['phone']         : '' );
    $office_number = sanitize_text_field( isset($_POST['office_number']) ? $_POST['office_number'] : '' );
    $website       = esc_url_raw( isset($_POST['website'])               ? $_POST['website']       : '' );
    $profile_image = '';
    $cur_pass      = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_pass      = isset($_POST['new_password'])     ? $_POST['new_password']     : '';
    $conf_pass     = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $new_email     = sanitize_email( isset($_POST['new_email'])         ? $_POST['new_email']         : '' );
    $new_email2    = sanitize_email( isset($_POST['new_email_confirm']) ? $_POST['new_email_confirm'] : '' );

     $agree_terms = isset($_POST['agree_terms']) ? 1 : 0;

      $agree_fees = isset($_POST['agree_fees']) ? 1 : 0;
    if ( empty($contact_name) ) $ih_ep_errors[] = 'Contact name is required.';
    if ( empty($company_name) ) $ih_ep_errors[] = 'Company name is required.';

if ( ! $agree_terms )
    $ih_ep_errors[] = 'You must agree to the Terms & Conditions.';

if ( ! $agree_fees )
    $ih_ep_errors[] = 'You must understand that fees may apply.';

    /* Email / password changes both require the current password. */
    $wants_email_change = ( $new_email !== '' && strtolower($new_email) !== strtolower($current_user->user_email) );
    $wants_pass_change  = ( $new_pass !== '' );

    if ( $wants_email_change || $wants_pass_change ) {
        if ( empty($cur_pass) )
            $ih_ep_errors[] = 'Please enter your current password to change your email or password.';
        elseif ( ! wp_check_password( $cur_pass, $current_user->user_pass, $user_id ) )
            $ih_ep_errors[] = 'Current password is incorrect.';
    }
    if ( $wants_email_change ) {
        if ( ! is_email($new_email) )
            $ih_ep_errors[] = 'Please enter a valid new email address.';
        elseif ( $new_email !== $new_email2 )
            $ih_ep_errors[] = 'New email addresses do not match.';
        elseif ( email_exists($new_email) )
            $ih_ep_errors[] = 'That email address is already registered to another account.';
    }
    if ( $wants_pass_change ) {
        if ( strlen($new_pass) < 8 )
            $ih_ep_errors[] = 'New password must be at least 8 characters.';
        elseif ( $new_pass !== $conf_pass )
            $ih_ep_errors[] = 'New passwords do not match.';
    }

    if ( empty($ih_ep_errors) ) {
      /* PROFILE IMAGE UPLOAD */
if ( ! empty( $_FILES['profile_image']['name'] ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attachment_id = media_handle_upload( 'profile_image', 0 );
    if ( is_wp_error( $attachment_id ) ) {
        $ih_ep_errors[] = 'Profile image upload failed: ' . $attachment_id->get_error_message();
    } else {
        $url = wp_get_attachment_url( $attachment_id );
        if ( $url ) {
            update_user_meta( $user_id, 'ih_profile_image', esc_url_raw( $url ) );
            update_user_meta( $user_id, 'ih_profile_image_id', (int) $attachment_id );
        }
    }
}
        wp_update_user( array( 'ID' => $user_id, 'display_name' => $contact_name ) );

        if ( $wants_email_change ) {
            $old_email = $current_user->user_email;
            wp_update_user( array( 'ID' => $user_id, 'user_email' => $new_email ) );
            /* Security notice to the old address so a hijacked change can't go unnoticed. */
            wp_mail(
                $old_email,
                'Your email address was changed',
                "Hi {$contact_name},\n\nThe email address on your Injection Moulding account was changed to {$new_email} on " . current_time('mysql') . ".\n\nIf this wasn't you, contact us immediately at " . get_option('admin_email') . "."
            );
        }
        if ( $wants_pass_change ) {
            wp_set_password( $new_pass, $user_id );
            wp_set_auth_cookie( $user_id );
        }
        update_user_meta( $user_id, 'ih_company_name',  $company_name );
        update_user_meta( $user_id, 'ih_job_title',     $job_title );
        update_user_meta( $user_id, 'ih_biz_role',      $biz_role );
        update_user_meta( $user_id, 'ih_bio',           $bio );
        update_user_meta( $user_id, 'ih_address',       $address );
        update_user_meta( $user_id, 'ih_city',          $city );
        update_user_meta( $user_id, 'ih_postcode',      $postcode );
        update_user_meta( $user_id, 'ih_phone',         $phone );
        update_user_meta( $user_id, 'ih_office_number', $office_number );
        update_user_meta( $user_id, 'ih_website',       $website );

        /* Keep the unprefixed registration keys in sync — the theme header,
           messages console and registration flow read these. */
        update_user_meta( $user_id, 'company_name',  $company_name );
        update_user_meta( $user_id, 'job_title',     $job_title );
        update_user_meta( $user_id, 'business_role', $biz_role );
        update_user_meta( $user_id, 'address',       $address );
        update_user_meta( $user_id, 'city',          $city );
        update_user_meta( $user_id, 'postcode',      $postcode );
        update_user_meta( $user_id, 'phone',         $phone );
        update_user_meta( $user_id, 'office_number', $office_number );
        update_user_meta( $user_id, 'website',       $website );
        if ( function_exists('ih_sync_user_meta') ) {
            ih_sync_user_meta( $user_id, array(
                'phone'    => $phone, 'company'  => $company_name,
                'address'  => $address, 'city'   => $city,
                'postcode' => $postcode, 'job_title' => $job_title,
                'website'  => $website,
            ));
        }
        $ih_ep_success = 'Profile updated successfully!';
        if ( $wants_email_change ) $ih_ep_success = 'Profile updated — your login email is now ' . $new_email . '.';
        if ( $wants_pass_change )  $ih_ep_success .= ' Your password has been changed.';
        $current_user  = wp_get_current_user();
    }
}

/* ── Load values ── */
$ep_name    = $current_user->display_name;
$ep_email   = $current_user->user_email;

/* Read prefixed key first, fall back to the unprefixed key written at
   registration — new users previously saw an empty profile form because
   registration saves `company_name` etc. while this page only read `ih_*`. */
if ( ! function_exists('ih_ep_meta') ) {
    function ih_ep_meta( $uid, $key, $fallback_key = '' ) {
        $v = get_user_meta( $uid, 'ih_' . $key, true );
        if ( $v === '' || $v === false ) {
            $v = get_user_meta( $uid, $fallback_key !== '' ? $fallback_key : $key, true );
        }
        return ( $v !== '' && $v !== false ) ? $v : '';
    }
}
$ep_company = ih_ep_meta($user_id,'company_name');
$ep_job     = ih_ep_meta($user_id,'job_title');
$ep_role    = ih_ep_meta($user_id,'biz_role','business_role');
$ep_bio     = ih_ep_meta($user_id,'bio');
$ep_address = ih_ep_meta($user_id,'address');
$ep_city    = ih_ep_meta($user_id,'city');
$ep_post    = ih_ep_meta($user_id,'postcode');
$ep_phone   = ih_ep_meta($user_id,'phone');
$ep_office  = ih_ep_meta($user_id,'office_number');
$ep_web     = ih_ep_meta($user_id,'website');
$_v = get_user_meta($user_id,'ih_profile_image',true);
$ep_profile = ($_v!==''&&$_v!==false)?$_v:'';

$ep_hash     = md5(strtolower(trim($ep_email)));
$ep_gravatar = function_exists( 'ih_get_user_avatar_url' ) ? ih_get_user_avatar_url( $user_id, 120 ) : 'https://www.gravatar.com/avatar/'.$ep_hash.'?s=120&d=404';
$ep_fallback = function_exists( 'ih_get_user_avatar_fallback_url' ) ? ih_get_user_avatar_fallback_url( $user_id, 120 ) : 'https://ui-avatars.com/api/?name='.rawurlencode($ep_name?$ep_name:'U').'&background=1f3d2e&color=c8e88e&size=120&bold=true&rounded=true&length=2';
$ep_action   = admin_url('admin.php?page=ih-user-edit-profile');
$ep_dash_url = admin_url('admin.php?page=ih-user-dashboard');
$biz_roles   = array('Manufacturer','Tool Owner','Product Developer','Startup','Overseas Buyer','Other');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Profile — Injection Moulding</title>
<?php wp_head(); ?>
<style>
/* ══════════════════
   EDIT PROFILE STYLES
══════════════════ */
.ep-wrap{max-width:860px;}
.ep-hero{background:linear-gradient(135deg,#153F45 0%,#0c2c30 100%);border-radius:16px;padding:26px 28px;display:flex;align-items:center;gap:20px;margin-bottom:20px;position:relative;overflow:hidden;}
.ep-av-wrap{position:relative;flex-shrink:0;z-index:1;}
.ep-av{width:76px;height:76px;border-radius:50%;border:3px solid rgba(200,232,142,.35);object-fit:cover;display:block;}
.ep-cam{position:absolute;bottom:0;right:0;width:24px;height:24px;border-radius:50%;background:#c8e88e;border:2px solid #153F45;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#153F45;font-size:9px;}
#ep-file{display:none;}
.ep-hi{flex:1;min-width:0;z-index:1;}
.ep-hn{font-size:18px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ep-he{font-size:12px;color:rgba(255,255,255,.5);margin:3px 0 10px;}
.ep-pills{display:flex;gap:8px;flex-wrap:wrap;}
.ep-pill{display:inline-flex;align-items:center;gap:5px;background:rgba(200,232,142,.12);color:#c8e88e;font-size:11px;font-weight:600;padding:3px 11px;border-radius:999px;border:1px solid rgba(200,232,142,.22);}

.ep-alert{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;font-weight:500;display:flex;align-items:flex-start;gap:10px;}
.ep-alert.ok{background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;}
.ep-alert.err{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;}

.ep-card{background:#fff;border-radius:14px;border:1px solid #e5ede8;box-shadow:0 1px 6px rgba(21,63,69,.05);padding:22px 26px;margin-bottom:16px;}
.ep-card-title{font-size:13px;font-weight:700;color:#1f3d2e;display:flex;align-items:center;gap:8px;margin-bottom:16px;padding-bottom:12px;border-bottom:1.5px solid #f0f5f0;}
.ep-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 20px;}
@media(max-width:540px){.ep-grid{grid-template-columns:1fr;}}
.ep-full{grid-column:1/-1;}
.ep-field{display:flex;flex-direction:column;gap:5px;}
.ep-label{font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.5px;}
.ep-label .req{color:#dc2626;margin-left:2px;}
.ep-input,.ep-select,.ep-ta{width:100%;padding:10px 13px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;color:#1a1a1a;font-family:inherit;outline:none;background:#fff;transition:border-color .18s,box-shadow .18s;}
.ep-input:focus,.ep-select:focus,.ep-ta:focus{border-color:#1f3d2e;box-shadow:0 0 0 3px rgba(31,61,46,.07);}
.ep-input[readonly]{background:#f9fafb;color:#9ca3af;cursor:default;}
.ep-input::placeholder,.ep-ta::placeholder{color:#c0c0c0;}
.ep-ta{resize:vertical;min-height:80px;}
.ep-hint{font-size:11px;color:#9ca3af;}
.ep-sw{position:relative;}
.ep-sw::after{content:'';position:absolute;right:13px;top:50%;transform:translateY(-50%);pointer-events:none;border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid #6b7280;width:0;height:0;}
.ep-select{-webkit-appearance:none;appearance:none;padding-right:36px;cursor:pointer;}
.ep-pw{position:relative;}
.ep-pw .ep-input{padding-right:42px;}
.ep-eye{position:absolute;right:11px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#aaa;padding:4px;display:flex;align-items:center;}
.ep-eye:hover{color:#374151;}
.ep-eye svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;}
.ep-sbar{height:3px;background:#e5e7eb;border-radius:2px;margin-top:6px;overflow:hidden;}
.ep-sfill{height:100%;border-radius:2px;width:0;transition:width .3s,background .3s;}
.ep-stxt{font-size:11px;font-weight:600;margin-top:3px;}

.ep-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap;margin-top:6px;}
.ep-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 26px;border-radius:999px;font-size:13px;font-weight:600;border:none;cursor:pointer;font-family:inherit;transition:background .2s,transform .12s;text-decoration:none!important;}
.ep-btn.save{background:#1f3d2e;color:#fff;box-shadow:0 3px 10px rgba(31,61,46,.22);}
.ep-btn.save:hover{background:#163028;}
.ep-btn.cancel{background:#fff;color:#374151;border:1.5px solid #d1d5db;}
.ep-btn.cancel:hover{background:#f9fafb;}

.ep-danger{border:1.5px solid #fecaca;border-radius:14px;padding:20px 24px;background:#fff;margin-top:6px;}
.ep-danger .ep-card-title{color:#dc2626;border-color:#fee2e2;}
.ep-danger p{font-size:13px;color:#6b7280;margin:0 0 14px;line-height:1.6;}
.ep-btn.dng{background:#fff;color:#dc2626;border:1.5px solid #fecaca;}
.ep-btn.dng:hover{background:#fef2f2;}

/* ── Responsive ── */
@media(max-width:768px){
    .ih-content{padding:12px 12px 40px;}
    .ep-hero{flex-direction:column;text-align:center;padding:20px 16px;}
    .ep-pills{justify-content:center;}
}
@media(max-width:480px){
    .ep-card{padding:16px 14px;}
    .ep-actions{justify-content:stretch;}
    .ep-btn{flex:1;justify-content:center;}
}
</style>
</head>
<body>

<?php include IH_DIR . 'pages/partials/ih-user-shell-start.php'; ?>

  <!-- ══ HEADER (desktop) ══ -->
  <?php include IH_DIR . 'pages/partials/ih-user-shell-header.php'; ?>

  <!-- ══ BODY ══ -->
  <div class="ih-body">

    <!-- ══ MAIN CONTENT ══ -->
    <main class="ih-main">
      <div class="ih-page-header">
        <div>
          <div class="ih-page-title">Edit Profile</div>
          <div class="ih-page-sub">Update your personal and company information</div>
        </div>
        <a href="<?php echo esc_url($ep_dash_url); ?>"
           style="display:inline-flex;align-items:center;gap:5px;background:#f3f4f6;color:#374151;padding:8px 14px;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;">
          &#8592; Back to Dashboard
        </a>
      </div>

      <div class="ih-content">
      <div class="ep-wrap">

        <!-- Hero -->
        <div class="ep-hero">
          <div class="ep-av-wrap">
            <img id="ep-avatar"
                 src="<?php echo esc_url($ep_profile ? $ep_profile : $ep_gravatar); ?>"
                 onerror="this.onerror=null;this.src='<?php echo esc_js($ep_fallback); ?>'"
                 class="ep-av" alt="Avatar">
            <label for="ep-file" class="ep-cam" title="Change photo">
              <i class="fa-solid fa-camera"></i>
            </label>
            <input type="file"
       id="ep-file"
       name="profile_image"
       accept="image/*"
       onchange="epPrev(this)">
          </div>
          <div class="ep-hi">
            <div class="ep-hn" id="ep-ln"><?php echo esc_html($ep_name ? $ep_name : 'Your Name'); ?></div>
            <div class="ep-he"><?php echo esc_html($ep_email); ?></div>
            <div class="ep-pills">
              <?php if ($ep_role)    echo '<div class="ep-pill">&#128188; '.esc_html($ep_role).'</div>'; ?>
              <?php if ($ep_company) echo '<div class="ep-pill" id="ep-lc">&#127970; '.esc_html($ep_company).'</div>'; ?>
              <?php if ($ep_city)    echo '<div class="ep-pill" id="ep-ll">&#128205; '.esc_html($ep_city).'</div>'; ?>
            </div>
          </div>
        </div>

        <!-- Alerts -->
        <?php if ($ih_ep_success): ?>
        <div class="ep-alert ok">&#10004; <?php echo esc_html($ih_ep_success); ?></div>
        <?php endif; ?>
        <?php if ($ih_ep_errors): ?>
        <div class="ep-alert err">
          <div><?php foreach ($ih_ep_errors as $e) echo '<div>&#9888; '.esc_html($e).'</div>'; ?></div>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="post" action="<?php echo esc_url($ep_action); ?>" enctype="multipart/form-data">
          <?php wp_nonce_field($nonce_key, 'ih_ep_nonce'); ?>

          <!-- Personal Info -->
<div class="ep-card">

    <div class="ep-card-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        Personal Information
    </div>

    <div class="ep-grid">

        <div class="ep-field">

            <label class="ep-label">
                Contact Name <span class="req">*</span>
            </label>

            <input type="text"
                   name="contact_name"
                   class="ep-input"
                   placeholder="Your full name"
                   value="<?php echo esc_attr($ep_name); ?>"
                   required
                   oninput="document.getElementById('ep-ln').textContent=this.value||'Your Name'">

        </div>

        <div class="ep-field">

            <label class="ep-label">
                Business Role
            </label>

            <div class="ep-sw">

                <select name="biz_role" class="ep-select">

                    <option value="">Select role</option>

                    <?php foreach ($biz_roles as $r): ?>

                        <option value="<?php echo esc_attr($r); ?>"
                            <?php selected($ep_role,$r); ?>>

                            <?php echo esc_html($r); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>

        <div class="ep-field ep-full">

            <label class="ep-label">
                Email Address
            </label>

            <input type="email"
                   class="ep-input"
                   value="<?php echo esc_attr($ep_email); ?>"
                   readonly>

            <span class="ep-hint">
                This is your login email. Change it in the Account Security section below.
            </span>

        </div>

        <div class="ep-field">

            <label class="ep-label">
                Job Title
            </label>

            <input type="text"
                   name="job_title"
                   class="ep-input"
                   placeholder="e.g. Production Manager"
                   value="<?php echo esc_attr($ep_job); ?>">

        </div>

        <div class="ep-field">

            <label class="ep-label">
                WhatsApp Number
            </label>

            <input type="tel"
                   name="phone"
                   class="ep-input"
                   placeholder="WhatsApp number"
                   value="<?php echo esc_attr($ep_phone); ?>">

        </div>

        <div class="ep-field ep-full">

            <label class="ep-label">
                About / Bio
            </label>

            <textarea name="bio"
                      class="ep-ta"
                      placeholder="Tell others about yourself..."><?php echo esc_textarea($ep_bio); ?></textarea>

        </div>

    </div>

</div>

          <!-- Company -->
          <div class="ep-card">
            <div class="ep-card-title">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
              Company & Contact Details
            </div>
            <div class="ep-grid">
              <div class="ep-field">
                <label class="ep-label">Company Name <span class="req">*</span></label>
                <input type="text" name="company_name" class="ep-input" placeholder="Enter company name"
                       value="<?php echo esc_attr($ep_company); ?>" required
                       oninput="epPill('ep-lc','&#127970;',this.value)">
              </div>
              <div class="ep-field">
                <label class="ep-label">Website URL</label>
                <input type="url" name="website" class="ep-input" placeholder="https://www.example.com"
                       value="<?php echo esc_attr($ep_web); ?>">
              </div>
              <div class="ep-field">
                <label class="ep-label">Phone Number</label>
                <input type="tel" name="phone" class="ep-input" placeholder="Enter phone number"
                       value="<?php echo esc_attr($ep_phone); ?>">
              </div>
              <div class="ep-field">
                <label class="ep-label">Office Number</label>
                <input type="text" name="office_number" class="ep-input" placeholder="Enter office number"
                       value="<?php echo esc_attr($ep_office); ?>">
              </div>
            </div>
          </div>

          <!-- Address -->
          <div class="ep-card">
            <div class="ep-card-title">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              Address
            </div>
            <div class="ep-grid">
              <div class="ep-field ep-full">
                <label class="ep-label">Street Address</label>
                <input type="text" name="address" class="ep-input" placeholder="Enter street address"
                       value="<?php echo esc_attr($ep_address); ?>">
              </div>
              <div class="ep-field">
                <label class="ep-label">Town / City</label>
                <input type="text" name="city" class="ep-input" placeholder="Enter town or city"
                       value="<?php echo esc_attr($ep_city); ?>"
                       oninput="epPill('ep-ll','&#128205;',this.value)">
              </div>
              <div class="ep-field">
                <label class="ep-label">Postcode</label>
                <input type="text" name="postcode" class="ep-input" placeholder="Enter postcode"
                       value="<?php echo esc_attr($ep_post); ?>">
              </div>
            </div>
          </div>

  <div class="ep-card">

    <div class="ep-card-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        Account Security &mdash; Change Email or Password
    </div>

    <div class="ep-grid">

        <!-- Current Password -->
        <div class="ep-field ep-full">

            <label class="ep-label">
                Current Password
            </label>

            <div class="ep-pw">

                <input type="password"
                       name="current_password"
                       id="ep-cp"
                       class="ep-input"
                       placeholder="Enter current password">

                <button type="button"
                        class="ep-eye"
                        onclick="epEye('ep-cp',this)">

                    <svg viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>

                </button>

            </div>

            <span class="ep-hint">
                Required when changing your email address or password.
            </span>

        </div>

        <!-- New Email -->
        <div class="ep-field">

            <label class="ep-label">
                New Email Address
            </label>

            <input type="email"
                   name="new_email"
                   id="ep-ne"
                   class="ep-input"
                   placeholder="Leave blank to keep current email"
                   autocomplete="email"
                   oninput="epEmailMatch()">

        </div>

        <!-- Confirm New Email -->
        <div class="ep-field">

            <label class="ep-label">
                Confirm New Email
            </label>

            <input type="email"
                   name="new_email_confirm"
                   id="ep-nec"
                   class="ep-input"
                   placeholder="Re-enter new email"
                   autocomplete="email"
                   oninput="epEmailMatch()">

            <div id="ep-em"
                 style="font-size:11px;font-weight:600;margin-top:4px;">
            </div>

        </div>

        <!-- New Password -->
        <div class="ep-field">

            <label class="ep-label">
                New Password
            </label>

            <div class="ep-pw">

                <input type="password"
                       name="new_password"
                       id="ep-np"
                       class="ep-input"
                       placeholder="Minimum 8 characters"
                       oninput="epMatch()">

                <button type="button"
                        class="ep-eye"
                        onclick="epEye('ep-np',this)">

                    <svg viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>

                </button>

            </div>

        </div>

        <!-- Confirm Password -->
        <div class="ep-field">

            <label class="ep-label">
                Confirm New Password
            </label>

            <div class="ep-pw">

                <input type="password"
                       name="confirm_password"
                       id="ep-cfp"
                       class="ep-input"
                       placeholder="Confirm new password"
                       oninput="epMatch()">

                <button type="button"
                        class="ep-eye"
                        onclick="epEye('ep-cfp',this)">

                    <svg viewBox="0 0 24 24">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>

                </button>

            </div>

            <div id="ep-pm"
                 style="font-size:11px;font-weight:600;margin-top:4px;">
            </div>

        </div>

    </div>

</div>
<div class="ep-card">

    <div class="ep-card-title">
        GDPR & Terms
    </div>

    <div style="display:flex;align-items:flex-start;gap:12px;">

        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/GDPR.png"
             alt="GDPR"
             style="width:64px;height:64px;object-fit:contain;flex-shrink:0;">

        <div style="flex:1;">

            <label style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:#374151;line-height:1.6;margin-bottom:14px;">

                <input type="checkbox"
                       name="agree_terms"
                       required
                       checked
                       style="margin-top:3px;accent-color:#153F45;">

                <span>

                    I confirm I have read and agree to the

                    <a href="/terms-conditions" target="_blank">
                        Terms & Conditions
                    </a>

                    and

                    <a href="/privacy-policy" target="_blank">
                        Privacy Policy
                    </a>.

                </span>

            </label>

            <label style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:#374151;line-height:1.6;">

                <input type="checkbox"
                       name="agree_fees"
                       required
                       checked
                       style="margin-top:3px;accent-color:#153F45;">

                <span>

                    I understand fees may apply for introductions,
                    successful projects, or premium services.

                </span>

            </label>

        </div>

    </div>

</div>
          <!-- Actions -->
          <div class="ep-actions">
            <a href="<?php echo esc_url($ep_dash_url); ?>" class="ep-btn cancel">&#215; Cancel</a>
            <button type="submit" class="ep-btn save">&#128190; Save Changes</button>
          </div>
        </form>

        <!-- Danger Zone -->
        <div class="ep-danger" style="margin-top:16px;">
          <div class="ep-card-title" style="color:#dc2626;border-color:#fee2e2;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Danger Zone
          </div>
          <p>Deleting your account is <strong>permanent</strong>. Your listings, messages, requests and profile data will be erased immediately (GDPR right to erasure). This cannot be undone.</p>

          <button type="button" class="ep-btn dng" id="epDelToggle" onclick="epToggleDelete()">
            &#128465; Delete My Account
          </button>

          <form method="post" action="<?php echo esc_url($ep_action); ?>" id="epDelForm" style="display:none;margin-top:16px;border-top:1.5px solid #fee2e2;padding-top:16px;"
                onsubmit="return confirm('Final confirmation: permanently delete your account and all data? This cannot be undone.');">
            <?php wp_nonce_field($del_nonce_key, 'ih_del_nonce'); ?>
            <div class="ep-grid">
              <div class="ep-field">
                <label class="ep-label" style="color:#dc2626;">Type DELETE to confirm <span class="req">*</span></label>
                <input type="text" name="delete_confirm" id="ep-delc" class="ep-input" placeholder="DELETE"
                       autocomplete="off" required oninput="epDelCheck()">
              </div>
              <div class="ep-field">
                <label class="ep-label" style="color:#dc2626;">Your Password <span class="req">*</span></label>
                <div class="ep-pw">
                  <input type="password" name="delete_password" id="ep-delp" class="ep-input"
                         placeholder="Enter your password" autocomplete="current-password" required oninput="epDelCheck()">
                  <button type="button" class="ep-eye" onclick="epEye('ep-delp',this)">
                    <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:14px;flex-wrap:wrap;">
              <button type="button" class="ep-btn cancel" onclick="epToggleDelete()">Cancel</button>
              <button type="submit" class="ep-btn" id="epDelSubmit" disabled
                      style="background:#dc2626;color:#fff;opacity:.5;cursor:not-allowed;">
                Permanently Delete Account
              </button>
            </div>
          </form>
        </div>

      </div>
      </div><!-- /.ih-content -->
    </main>

  </div><!-- /.ih-body -->
</div><!-- /.ih-shell -->

<script>
/* ── Header dropdowns ── */
function ihToggleNotif(e){
    e.stopPropagation();
    var b=document.getElementById('ihNotifBox');
    b.style.display=b.style.display==='block'?'none':'block';
    var d=document.getElementById('ihNotifDot');if(d)d.remove();
}
function ihToggleAccount(e){
    e.stopPropagation();
    var b=document.getElementById('ihAccountBox');
    b.style.display=b.style.display==='block'?'none':'block';
}
document.addEventListener('click',function(e){
    if(!e.target.closest('#ihNotifWrap')){var b=document.getElementById('ihNotifBox');if(b)b.style.display='none';}
    if(!e.target.closest('#ihAccountWrap')){var b=document.getElementById('ihAccountBox');if(b)b.style.display='none';}
});

/* ── Edit Profile JS ── */
function epPrev(i){if(!i.files||!i.files[0])return;var r=new FileReader();r.onload=function(e){document.getElementById('ep-avatar').src=e.target.result;};r.readAsDataURL(i.files[0]);}
function epPill(id,icon,v){var el=document.getElementById(id);if(!el)return;if(v){el.innerHTML=icon+' '+v;el.style.display='';}else{el.style.display='none';}}
function epEye(id,btn){var i=document.getElementById(id);var s=btn.querySelector('svg');if(!i)return;if(i.type==='password'){i.type='text';s.innerHTML='<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';}else{i.type='password';s.innerHTML='<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';}}
function epStr(v){var f=document.getElementById('ep-sf'),t=document.getElementById('ep-st');if(!f||!t)return;if(!v){f.style.width='0';t.textContent='';return;}var s=0;if(v.length>=8)s++;if(v.length>=12)s++;if(/[A-Z]/.test(v)&&/[a-z]/.test(v))s++;if(/\d/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;var l=[{w:'20%',c:'#ef4444',t:'Very Weak'},{w:'40%',c:'#f97316',t:'Weak'},{w:'60%',c:'#eab308',t:'Fair'},{w:'80%',c:'#22c55e',t:'Strong'},{w:'100%',c:'#15803d',t:'Very Strong'}][Math.min(s,4)];f.style.width=l.w;f.style.background=l.c;t.style.color=l.c;t.textContent=l.t;}
function epEmailMatch(){
    var n = document.getElementById('ep-ne');
    var c = document.getElementById('ep-nec');
    var m = document.getElementById('ep-em');
    if(!n || !c || !m) return;
    if(!n.value && !c.value){ m.textContent=''; c.style.borderColor=''; return; }
    if(n.value && c.value && n.value.toLowerCase() === c.value.toLowerCase()){
        m.style.color='#16a34a';
        m.innerHTML='\u2714 Email addresses match';
        c.style.borderColor='#16a34a';
    } else {
        m.style.color='#dc2626';
        m.innerHTML='\u2716 Email addresses do not match';
        c.style.borderColor='#dc2626';
    }
}
function epToggleDelete(){
    var f = document.getElementById('epDelForm');
    var b = document.getElementById('epDelToggle');
    if(!f || !b) return;
    var open = f.style.display !== 'none';
    f.style.display = open ? 'none' : 'block';
    b.style.display = open ? '' : 'none';
    if(!open){ var c=document.getElementById('ep-delc'); if(c) c.focus(); }
}
function epDelCheck(){
    var c = document.getElementById('ep-delc');
    var p = document.getElementById('ep-delp');
    var s = document.getElementById('epDelSubmit');
    if(!c || !p || !s) return;
    var ok = c.value.trim().toUpperCase() === 'DELETE' && p.value.length > 0;
    s.disabled = !ok;
    s.style.opacity = ok ? '1' : '.5';
    s.style.cursor = ok ? 'pointer' : 'not-allowed';
}
function epMatch(){

    var p = document.getElementById('ep-np').value;

    var c = document.getElementById('ep-cfp');

    var m = document.getElementById('ep-pm');

    if(!c || !m) return;

    if(!c.value){

        m.textContent='';

        c.style.borderColor='';

        return;
    }

    if(p === c.value){

        m.style.color='#16a34a';

        m.innerHTML='✔ Passwords match';

        c.style.borderColor='#16a34a';

    } else {

        m.style.color='#dc2626';

        m.innerHTML='✖ Passwords do not match';

        c.style.borderColor='#dc2626';
    }
}
</script>
<?php wp_footer(); ?>
</body>
</html>