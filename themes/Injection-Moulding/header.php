<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?php bloginfo( 'description' ); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
// ═══════════════════════════════════════════════════════════════
// HELPER: Get display label for a user
//   - Admin sees:  "John Smith (JOHLAH001)"
//   - Others see:  "JOHLAH001"
//   - Fallback:    "XXXXXXX001" if ID not yet set
// ═══════════════════════════════════════════════════════════════
if ( ! function_exists('ih_get_display_label') ) {
    function ih_get_display_label( $user_id, $fallback_name = '' ) {
        $unique_id = get_user_meta( $user_id, 'ih_unique_id', true );
        if ( empty($unique_id) ) {
            $unique_id = 'ID#' . str_pad( $user_id, 6, '0', STR_PAD_LEFT );
        }
        // Admin sees real name alongside ID
        if ( current_user_can('manage_options') ) {
            $name = $fallback_name ?: get_userdata($user_id)->display_name;
            return esc_html($name) . ' <span style="font-size:10px;opacity:.7;font-family:monospace;background:#f3f4f6;padding:1px 5px;border-radius:4px;">' . esc_html($unique_id) . '</span>';
        }
        // Everyone else sees only the unique ID
        return '<span style="font-family:monospace;font-weight:700;letter-spacing:1px;">' . esc_html($unique_id) . '</span>';
    }
}

// ═══════════════════════════════════════════════════════════════
// HELPER: Plain text unique ID (for attributes / JS)
// ═══════════════════════════════════════════════════════════════
if ( ! function_exists('ih_get_plain_id') ) {
    function ih_get_plain_id( $user_id ) {
        $unique_id = get_user_meta( $user_id, 'ih_unique_id', true );
        return $unique_id ?: 'ID#' . str_pad( $user_id, 6, '0', STR_PAD_LEFT );
    }
}

// ═══════════════════════════════════════════════════════════════
// HELPER: Generate unique ID (shared with register handler)
// ═══════════════════════════════════════════════════════════════
if ( ! function_exists('ih_generate_unique_id') ) {
    function ih_generate_unique_id( $contact_name, $city = '' ) {
        $name_clean = preg_replace( '/[^a-zA-Z]/', '', $contact_name );
        $name_part  = str_pad( strtoupper( substr( $name_clean, 0, 3 ) ), 3, 'X' );
        $city_clean = preg_replace( '/[^a-zA-Z]/', '', $city );
        $city_part  = str_pad( strtoupper( substr( $city_clean, 0, 3 ) ), 3, 'X' );
        $base       = $name_part . $city_part;
        $counter    = 1;
        do {
            $candidate = $base . str_pad( $counter, 3, '0', STR_PAD_LEFT );
            $existing  = get_users([
                'meta_key'   => 'ih_unique_id',
                'meta_value' => $candidate,
                'number'     => 1,
                'fields'     => 'ids',
            ]);
            $counter++;
        } while ( ! empty($existing) && $counter < 9999 );
        return $candidate;
    }
}

$add_tool_url    = '';
$add_machine_url = '';
if ( is_user_logged_in() ) {
    if ( current_user_can('manage_options') ) {
        $add_tool_url    = admin_url('admin.php?page=ih-add-tool');
        $add_machine_url = admin_url('admin.php?page=ih-add-machine');
    } else {
        $add_tool_url    = admin_url('admin.php?page=ih-user-add-tool');
        $add_machine_url = admin_url('admin.php?page=ih-user-add-machine');
    }
} else {
    $add_tool_url    = '#';
    $add_machine_url = '#';
}

$ih_user_logged_in  = is_user_logged_in();
$ih_unread_msgs     = 0;
$ih_notif_count     = 0;
$ih_notifs          = [];
$ih_wishlist_count  = 0;
$ih_user_name       = '';
$ih_user_email      = '';
$ih_gravatar_url    = '';
$ih_fallback_avatar = '';
$ih_profile_url     = '';
$ih_messages_url    = '';
$ih_logout_url      = '';
$ih_dashboard_url   = '';
$ih_current_unique_id = ''; // ← logged-in user's own unique ID

if ( $ih_user_logged_in ) {
    global $wpdb;
    $current_user         = wp_get_current_user();
    $user_id              = get_current_user_id();
    $ih_user_name         = $current_user->display_name;
    $ih_user_email        = $current_user->user_email;
    if ( function_exists( 'ih_get_user_avatar_url' ) ) {
        $ih_gravatar_url    = ih_get_user_avatar_url( $user_id, 72 );
        $ih_fallback_avatar = ih_get_user_avatar_fallback_url( $user_id, 72 );
    } else {
        $email_hash         = md5( strtolower( trim( $ih_user_email ) ) );
        $ih_gravatar_url    = 'https://www.gravatar.com/avatar/' . $email_hash . '?s=72&d=404';
        $ih_fallback_avatar = 'https://ui-avatars.com/api/?name=' . rawurlencode( $ih_user_name ?: 'U' ) . '&background=1f3d2e&color=c8e88e&size=72&bold=true&rounded=true&length=2';
    }
    $ih_profile_url       = current_user_can('manage_options')
        ? get_edit_profile_url()
        : admin_url('admin.php?page=ih-user-edit-profile');
    $ih_messages_url      = admin_url('admin.php?page=ih-user-messages');
    $ih_logout_url        = wp_logout_url( home_url() );
    $ih_dashboard_url     = current_user_can('manage_options')
        ? admin_url('admin.php?page=ih-dashboard')
        : admin_url('admin.php?page=ih-user-dashboard');

    // Fetch or auto-generate unique ID for current user
    $ih_current_unique_id = get_user_meta( $user_id, 'ih_unique_id', true );
    if ( empty($ih_current_unique_id) ) {
        // Legacy users: generate now on first visit
        $city_meta            = get_user_meta( $user_id, 'city', true );
        $ih_current_unique_id = ih_generate_unique_id( $ih_user_name, $city_meta );
        update_user_meta( $user_id, 'ih_unique_id', $ih_current_unique_id );
    }

    $ih_unread_msgs = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(unread) FROM {$wpdb->prefix}ih_threads WHERE user_id=%d", $user_id
    ) );
    $ih_notifs = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND status='Approved' ORDER BY id DESC LIMIT 5",
        $user_id
    ), ARRAY_A ) ?: [];
    $ih_notif_count    = count( $ih_notifs );
    $ih_wishlist       = get_user_meta( $user_id, 'ih_wishlist', true );
    $ih_wishlist_count = is_array( $ih_wishlist ) ? count( $ih_wishlist ) : 0;
}

// Auth URLs
$ih_lost_pass_url = wp_lostpassword_url();
?>

<!-- ════════════════════════════════════════════════
     IH AUTH MODAL — Login / Register Popup
════════════════════════════════════════════════ -->
<style>
#ih-auth-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.48);
    -webkit-backdrop-filter: blur(3px);
    backdrop-filter: blur(3px);
    z-index: 999990;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
#ih-auth-overlay.active { display: flex; }

#ih-auth-modal {
    background: #fff;
    border-radius: 20px;
    width: 100%;
    max-width: 780px;
    max-height: 92vh;
    overflow-y: auto;
    padding: 36px 40px 40px;
    position: relative;
    animation: ihSlideUp .22s cubic-bezier(.34,1.4,.64,1);
    box-shadow: 0 24px 80px rgba(0,0,0,.2);
    scrollbar-width: thin;
    scrollbar-color: #d1d5db transparent;
}
@media(max-width:600px){
    #ih-auth-modal { padding: 28px 18px 32px; border-radius: 16px; }
}
@keyframes ihSlideUp {
    from { opacity:0; transform:translateY(24px) scale(.97); }
    to   { opacity:1; transform:translateY(0)    scale(1);   }
}

#ih-modal-close {
    position: absolute; top: 16px; right: 18px;
    width: 32px; height: 32px;
    background: #f3f4f6; border: none; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 17px; color: #6b7280;
    transition: background .15s, color .15s;
    z-index: 2;
}
#ih-modal-close:hover { background: #e5e7eb; color: #111; }

.ih-modal-tabs {
    display: flex; background: #f3f4f6;
    border-radius: 999px; padding: 5px;
    width: fit-content; margin: 0 auto 32px; gap: 2px;
}
.ih-modal-tab {
    padding: 10px 44px; border-radius: 999px;
    font-size: 15px; font-weight: 600;
    border: none; background: transparent;
    cursor: pointer; color: #6b7280;
    font-family: 'Poppins', 'Montserrat', sans-serif;
    transition: background .2s, color .2s; white-space: nowrap;
}
.ih-modal-tab.active { background: #153F45; color: #fff; }
@media(max-width:400px){ .ih-modal-tab { padding: 10px 24px; font-size: 14px; } }

.ih-modal-panel { display: none; }
.ih-modal-panel.active { display: block; }

.ih-login-wrap { max-width: 500px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px; }

.ih-reg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 24px; }
@media(max-width:580px){ .ih-reg-grid { grid-template-columns: 1fr; } }

.ihm-field { display: flex; flex-direction: column; gap: 6px; }
.ihm-label { font-size: 13px; font-weight: 600; color: #1a1a1a; font-family: 'Poppins', sans-serif; }
.ihm-label span { color: #dc2626; }
.ihm-input, .ihm-select {
    padding: 11px 14px; border: 1.5px solid #e5e7eb;
    border-radius: 10px; font-size: 14px; color: #333;
    font-family: 'Poppins', sans-serif; outline: none;
    background: #fff; width: 100%; box-sizing: border-box;
    transition: border-color .18s, box-shadow .18s;
}
.ihm-input:focus, .ihm-select:focus {
    border-color: #153F45;
    box-shadow: 0 0 0 3px rgba(21,63,69,.08);
}
.ihm-input::placeholder { color: #c0c0c0; }

.ihm-pass-wrap { position: relative; }
.ihm-pass-wrap .ihm-input { padding-right: 46px; }
.ihm-pass-toggle {
    position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: #aaa;
    padding: 4px; display: flex; align-items: center; transition: color .15s;
}
.ihm-pass-toggle:hover { color: #374151; }
.ihm-pass-toggle svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2; }

.ihm-select-wrap { position: relative; }
.ihm-select-wrap::after {
    content: ''; position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    width: 0; height: 0; border-left: 4px solid transparent;
    border-right: 4px solid transparent; border-top: 5px solid #6b7280; pointer-events: none;
}
.ihm-select { appearance: none; padding-right: 38px; cursor: pointer; }

.ihm-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.ihm-check-label {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: #555; font-family: 'Poppins', sans-serif; cursor: pointer;
}
.ihm-check-label input[type=checkbox] { width: 16px; height: 16px; accent-color: #153F45; cursor: pointer; flex-shrink: 0; }
.ihm-forgot { font-size: 13px; color: #dc2626; font-weight: 600; text-decoration: none; font-family: 'Poppins', sans-serif; white-space: nowrap; }
.ihm-forgot:hover { text-decoration: underline; }

.ihm-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: #153F45; color: #fff;
    padding: 13px 44px; border-radius: 999px;
    font-size: 15px; font-weight: 600; border: none; cursor: pointer;
    font-family: 'Poppins', sans-serif; transition: background .2s, transform .15s;
}
.ihm-btn:hover { background: #0f2c30; }
.ihm-btn:active { transform: scale(.97); }
.ihm-btn.loading { opacity: .65; pointer-events: none; }
.ihm-btn-wrap { text-align: center; margin-top: 8px; }

.ihm-switch { text-align: center; font-size: 13px; color: #888; font-family: 'Poppins', sans-serif; margin-top: 4px; }
.ihm-switch a { color: #153F45; font-weight: 600; text-decoration: none; }
.ihm-switch a:hover { text-decoration: underline; }

.ihm-errors {
    background: #fef2f2; border: 1px solid #fecaca;
    border-radius: 10px; padding: 12px 16px; margin-bottom: 18px;
}
.ihm-errors p { font-size: 13px; color: #dc2626; font-family: 'Poppins', sans-serif; margin-bottom: 4px; }
.ihm-errors p:last-child { margin-bottom: 0; }
#ihm-pass-msg { font-size: 12px; margin-top: 4px; }

.ihm-gdpr-row {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 14px 16px; background: #f9fafb;
    border-radius: 12px; border: 1px solid #e5e7eb; margin-top: 4px;
}
.ihm-gdpr-row img { width: 52px; height: 52px; object-fit: contain; flex-shrink: 0; }
.ihm-gdpr-checks { display: flex; flex-direction: column; gap: 8px; }
.ihm-gdpr-check {
    display: flex; align-items: flex-start; gap: 8px;
    font-size: 12px; color: #555; font-family: 'Poppins', sans-serif;
    line-height: 1.5; cursor: pointer;
}
.ihm-gdpr-check input { margin-top: 2px; accent-color: #153F45; flex-shrink: 0; }
.ihm-gdpr-check a { color: #153F45; font-weight: 600; }

/* ── Unique ID preview strip (inside modal register) ── */
.ihm-id-preview {
    background: #f0fdf4; border: 1.5px dashed #86efac;
    border-radius: 10px; padding: 10px 14px;
    display: none; align-items: center; gap: 10px; margin-bottom: 4px;
}
.ihm-id-preview.visible { display: flex; }
.ihm-id-preview-val { font-family: monospace; font-size: 18px; font-weight: 800; color: #153F45; letter-spacing: 2px; }

/* ── Dropdown styles ── */
.ih-nav-dropdown {
    position: absolute; top: calc(100% + 10px); right: 0;
    background: #fff; border: 1px solid #e5ede8; border-radius: 18px;
    box-shadow: 0 16px 48px rgba(0,0,0,.13); z-index: 9999;
    display: none; min-width: 300px; overflow: hidden;
    animation: ihDropIn .18s cubic-bezier(.34,1.56,.64,1);
}
@keyframes ihDropIn {
    from { opacity:0; transform:translateY(-8px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
.ih-nav-icon-wrap { position: relative; display: flex; align-items: center; }
.ih-icon-badge {
    position: absolute; top:2px; right:2px; min-width:18px; height:18px;
    background:#ef4444; color:#fff; font-size:10px; font-weight:700;
    border-radius:50px; border:2px solid #E8FBC4;
    display:flex; align-items:center; justify-content:center; padding:0 4px; line-height:1;
}
.ih-icon-btn {
    position:relative; width:38px; height:38px; background:#fff; border:none; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 1px 4px rgba(0,0,0,.08); cursor:pointer;
    transition:background .15s, transform .15s; flex-shrink:0;
}
.ih-icon-btn:hover { background:#f0fdf4; transform:scale(1.05); }
.ih-notif-head, .ih-msg-head, .ih-wish-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 18px; border-bottom:1px solid #f3f4f6; font-size:14px; font-weight:700; color:#1f3d2e;
}
.ih-notif-count-badge { background:#ef4444; color:#fff; font-size:10px; font-weight:700; padding:2px 8px; border-radius:50px; }
.ih-notif-item { display:flex; align-items:flex-start; gap:12px; padding:12px 18px; border-bottom:1px solid #f9fafb; transition:background .12s; }
.ih-notif-item:hover { background:#f7faf8; }
.ih-notif-icon { width:36px; height:36px; border-radius:10px; background:#dcfce7; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.ih-notif-title { font-size:13px; font-weight:600; color:#1f3d2e; margin-bottom:2px; }
.ih-notif-time  { font-size:11px; color:#9ca3af; }
.ih-notif-dismiss { margin-left:auto; background:none; border:none; cursor:pointer; font-size:14px; color:#d1d5db; padding:2px; border-radius:4px; flex-shrink:0; transition:color .12s; }
.ih-notif-dismiss:hover { color:#ef4444; }
.ih-notif-empty { padding:32px 20px; text-align:center; color:#9ca3af; font-size:13px; }
.ih-notif-footer, .ih-msg-footer { padding:12px 18px; border-top:1px solid #f3f4f6; text-align:center; }
.ih-notif-footer a, .ih-msg-footer a { font-size:12px; font-weight:600; color:#1f3d2e; text-decoration:none; }

/* ── Message item: ID pill ── */
.ih-msg-item { display:flex; align-items:center; gap:12px; padding:13px 18px; border-bottom:1px solid #f9fafb; text-decoration:none; transition:background .12s; }
.ih-msg-item:hover { background:#f7faf8; }
.ih-msg-avatar { width:38px; height:38px; border-radius:50%; background:#1f3d2e; color:#c8e88e; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0; }
.ih-msg-name    { font-size:13px; font-weight:700; color:#1a1a1a; font-family:monospace; letter-spacing:1px; }
.ih-msg-name-admin { font-size:13px; font-weight:600; color:#1a1a1a; }
.ih-msg-preview { font-size:12px; color:#9ca3af; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:180px; }
.ih-msg-id-pill { font-size:10px; font-family:monospace; background:#f0fdf4; color:#153F45; border:1px solid #bbf7d0; border-radius:4px; padding:1px 5px; margin-top:2px; display:inline-block; }
.ih-msg-unread-dot { width:8px; height:8px; border-radius:50%; background:#22c55e; margin-left:auto; flex-shrink:0; }

.ih-wish-item { display:flex; align-items:center; gap:12px; padding:12px 18px; border-bottom:1px solid #f9fafb; text-decoration:none; transition:background .12s; }
.ih-wish-item:hover { background:#f7faf8; }
.ih-wish-thumb { width:44px; height:44px; border-radius:10px; object-fit:cover; flex-shrink:0; background:#f3f4f6; }
.ih-wish-title { font-size:13px; font-weight:600; color:#1a1a1a; }
.ih-wish-type  { font-size:11px; color:#9ca3af; margin-top:2px; }
.ih-wish-remove { margin-left:auto; background:none; border:none; cursor:pointer; color:#d1d5db; font-size:14px; flex-shrink:0; transition:color .12s; padding:2px; }
.ih-wish-remove:hover { color:#ef4444; }

/* ── Profile dropdown ── */
.ih-profile-drop { min-width:220px; }
.ih-profile-user { display:flex; align-items:center; gap:12px; padding:16px 18px; border-bottom:1px solid #f3f4f6; }
.ih-profile-avatar { width:42px; height:42px; border-radius:50%; background:#1f3d2e; color:#c8e88e; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; flex-shrink:0; overflow:hidden; }
.ih-profile-avatar img { width:100%; height:100%; object-fit:cover; }
.ih-profile-name  { font-size:13px; font-weight:700; color:#1f3d2e; }
.ih-profile-uid   { font-size:11px; font-family:monospace; color:#153F45; font-weight:700; letter-spacing:1px; background:#f0fdf4; padding:1px 6px; border-radius:4px; border:1px solid #bbf7d0; display:inline-block; margin-top:3px; }
.ih-profile-email { font-size:11px; color:#9ca3af; margin-top:1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:140px; }
.ih-profile-link { display:flex; align-items:center; gap:10px; padding:11px 18px; font-size:13px; font-weight:500; color:#374151; text-decoration:none; transition:background .12s, color .12s; }
.ih-profile-link:hover { background:#f7faf8; color:#1f3d2e; }
.ih-profile-link svg { width:15px; height:15px; flex-shrink:0; color:#9ca3af; }
.ih-profile-link .badge { margin-left:auto; background:#ef4444; color:#fff; font-size:10px; font-weight:700; padding:1px 6px; border-radius:50px; }
.ih-profile-divider { height:1px; background:#f3f4f6; margin:4px 0; }
.ih-profile-link.danger { color:#ef4444; }
.ih-profile-link.danger svg { color:#ef4444; }
.ih-profile-link.danger:hover { background:#fef2f2; }
</style>

<!-- AUTH MODAL OVERLAY -->
<div id="ih-auth-overlay" onclick="ihModalOutsideClick(event)" role="dialog" aria-modal="true">
  <div id="ih-auth-modal">

    <button id="ih-modal-close" onclick="ihCloseModal()" title="Close">✕</button>

    <div class="ih-modal-tabs">
      <button class="ih-modal-tab active" id="ihm-tab-login"    onclick="ihmSwitchTab('login')">Login</button>
      <button class="ih-modal-tab"        id="ihm-tab-register" onclick="ihmSwitchTab('register')">Register</button>
    </div>

    <!-- ═══════════════════════ LOGIN PANEL ═══════════════════════ -->
    <div class="ih-modal-panel active" id="ihm-panel-login">
      <div class="ih-login-wrap">

        <div class="ihm-field">
          <label class="ihm-label" for="ihm-login-email">Username or E-mail</label>
          <input type="email" id="ihm-login-email" class="ihm-input" placeholder="Enter username or email address" autocomplete="email">
        </div>

        <div class="ihm-field">
          <div class="ihm-row" style="margin-bottom:6px;">
            <span class="ihm-label" style="margin:0;">Password</span>
            <a href="<?php echo esc_url($ih_lost_pass_url); ?>" class="ihm-forgot">Forgot Password</a>
          </div>
          <div class="ihm-pass-wrap">
            <input type="password" id="ihm-login-pass" class="ihm-input" placeholder="Enter password" autocomplete="current-password">
            <button type="button" class="ihm-pass-toggle" onclick="ihmTogglePass('ihm-login-pass','ihm-eye-lp')">
              <svg id="ihm-eye-lp" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <label class="ihm-check-label">
          <input type="checkbox" id="ihm-remember"> Keep me signed in
        </label>

        <div id="ihm-login-error" class="ihm-errors" style="display:none;"></div>

        <div class="ihm-btn-wrap">
          <button type="button" class="ihm-btn" id="ihm-login-btn" onclick="ihmSubmitLogin()">
            Login
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          </button>
        </div>

        <p class="ihm-switch">Don't have an account? <a href="#" onclick="ihmSwitchTab('register');return false;">Register here</a></p>
      </div>
    </div>

    <!-- ═══════════════════════ REGISTER PANEL ═══════════════════════ -->
    <div class="ih-modal-panel" id="ihm-panel-register">
      <div id="ihm-reg-errors" class="ihm-errors" style="display:none;"></div>

      <!-- Live ID preview (shown as user types name + city) -->
      <div class="ihm-id-preview" id="ihm-id-preview">
        <span style="font-size:20px;">🪪</span>
        <div>
          <div style="font-size:11px;color:#555;font-family:'Poppins',sans-serif;">Your Platform ID (preview):</div>
          <div class="ihm-id-preview-val" id="ihm-id-preview-val">??????</div>
          <div style="font-size:10px;color:#888;font-family:'Poppins',sans-serif;">Exact ID assigned on registration</div>
        </div>
      </div>

      <div class="ih-reg-grid">

        <div class="ihm-field">
          <label class="ihm-label">Choose Business Role <span>*</span></label>
          <div class="ihm-select-wrap">
            <select id="ihm-biz-role" class="ihm-select" required>
              <option value="" disabled selected>Select role</option>
              <?php foreach(['Manufacturer','Tool Owner','Product Developer','Startup','Overseas Buyer','Other'] as $role): ?>
                <option value="<?php echo esc_attr($role); ?>"><?php echo esc_html($role); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Company Name <span>*</span></label>
          <input type="text" id="ihm-company" class="ihm-input" placeholder="Enter company name" required>
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Contact Name <span>*</span></label>
          <input type="text" id="ihm-contact" class="ihm-input" placeholder="Enter contact name" required>
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Job Title</label>
          <input type="text" id="ihm-jobtitle" class="ihm-input" placeholder="Enter job title">
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Address</label>
          <input type="text" id="ihm-address" class="ihm-input" placeholder="Enter home address">
        </div>

        <div class="ihm-field">
          <label class="ihm-label">
            Town / City
            <span style="color:#153F45;font-weight:400;font-size:10px;margin-left:4px;">(used for your ID)</span>
          </label>
          <input type="text" id="ihm-city" class="ihm-input" placeholder="Enter town/city">
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Postcode</label>
          <input type="text" id="ihm-postcode" class="ihm-input" placeholder="Enter postcode">
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Office Number</label>
          <input type="text" id="ihm-office" class="ihm-input" placeholder="Enter office number">
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Website URL</label>
          <input type="url" id="ihm-website" class="ihm-input" placeholder="https://www.example.com">
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Phone Number</label>
          <input type="tel" id="ihm-phone" class="ihm-input" placeholder="Enter phone number">
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Email Address <span>*</span></label>
          <input type="email" id="ihm-email" class="ihm-input" placeholder="Enter email address" required autocomplete="email">
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Confirm Email <span>*</span></label>
          <input type="email" id="ihm-email-conf" class="ihm-input" placeholder="Enter confirm email address" required>
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Password <span>*</span></label>
          <div class="ihm-pass-wrap">
            <input type="password" id="ihm-password" class="ihm-input" placeholder="Enter password" required minlength="8" autocomplete="new-password">
            <button type="button" class="ihm-pass-toggle" onclick="ihmTogglePass('ihm-password','ihm-eye-rp')">
              <svg id="ihm-eye-rp" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <div class="ihm-field">
          <label class="ihm-label">Confirm Password <span>*</span></label>
          <div class="ihm-pass-wrap">
            <input type="password" id="ihm-password-conf" class="ihm-input" placeholder="Enter confirm password" required autocomplete="new-password">
            <button type="button" class="ihm-pass-toggle" onclick="ihmTogglePass('ihm-password-conf','ihm-eye-rpc')">
              <svg id="ihm-eye-rpc" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div id="ihm-pass-msg"></div>
        </div>

      </div><!-- /ih-reg-grid -->

      <div class="ihm-gdpr-row" style="margin-top:20px;">
        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/GDPR.png" alt="GDPR">
        <div class="ihm-gdpr-checks">
          <label class="ihm-gdpr-check">
            <input type="checkbox" id="ihm-agree-terms" required>
            I confirm I have read and agree to the <a href="/terms-conditions" target="_blank">Terms &amp; Conditions</a> and <a href="/privacy-policy" target="_blank">Privacy Policy</a>.
          </label>
          <label class="ihm-gdpr-check">
            <input type="checkbox" id="ihm-agree-fees" required>
            I understand fees may apply for introductions, successful projects, or premium services.
          </label>
        </div>
      </div>

      <div class="ihm-btn-wrap" style="margin-top:24px;">
        <button type="button" class="ihm-btn" id="ihm-register-btn" onclick="ihmSubmitRegister()">
          Register
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
        </button>
      </div>

      <p class="ihm-switch" style="margin-top:16px;">Already have an account? <a href="#" onclick="ihmSwitchTab('login');return false;">Login here</a></p>
    </div>

  </div>
</div>
<!-- /AUTH MODAL -->

<!-- ==================== NAVBAR ==================== -->
<header class="fixed lg:top-[0.5px] left-1/2 -translate-x-1/2 w-full max-w-7xl z-50 lg:pt-4 top-0 pt-0">
  <div class="bg-white rounded-[12px] backdrop-blur-md shadow-sm shadow-[#CDF7B6]">

    <div class="im-public-header-top bg-[#E8FBC4] px-4 md:px-6 xl:px-8 py-3 flex items-center justify-between rounded-t-[12px]">
      <div class="flex items-center gap-3">
        <a href="<?php echo esc_url( home_url('/') ); ?>">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.png" class="h-[44px]" alt="Logo">
        </a>
        <span class="font-bold text-[#153F45] leading-tight font-[Montserrat] text-[15.03px]">Injection<br>Moulding</span>
      </div>

      <div class="im-public-header-actions flex items-center gap-2 md:gap-3 min-w-0">
        <div class="hidden sm:flex items-center gap-2 mr-2">

          <!-- ── HEART / Wishlist ── -->
          <div class="ih-nav-icon-wrap">
            <button class="ih-icon-btn" onclick="ihIconClick('ihWishDrop',event)" title="Wishlist">
              <i class="fa-regular fa-heart" style="font-size:15px;color:#374151;"></i>
              <?php if ( $ih_user_logged_in && $ih_wishlist_count > 0 ) : ?>
              <span class="ih-icon-badge"><?php echo $ih_wishlist_count; ?></span>
              <?php endif; ?>
            </button>
            <?php if ( $ih_user_logged_in ) : ?>
            <div class="ih-nav-dropdown" id="ihWishDrop">
              <div class="ih-wish-head">
                <span>Wishlist</span>
                <?php if ( $ih_wishlist_count > 0 ) : ?><span class="ih-notif-count-badge"><?php echo $ih_wishlist_count; ?></span><?php endif; ?>
              </div>
              <?php if ( $ih_wishlist_count > 0 ) :
                $wish_items = get_user_meta( get_current_user_id(), 'ih_wishlist', true );
                if ( is_array( $wish_items ) ) :
                  $shown = 0;
                  foreach ( $wish_items as $wi ) :
                    if ( $shown >= 4 ) break;
                    $wi_id    = isset($wi['id'])    ? (int)$wi['id']  : 0;
                    $wi_type  = isset($wi['type'])  ? $wi['type']     : 'machine';
                    $wi_title = isset($wi['title']) ? $wi['title']    : 'Listing';
                    $wi_img   = isset($wi['image']) ? $wi['image']    : get_template_directory_uri().'/assets/images/add-machine.jpeg';
                    $wi_url   = $wi_type==='tool' ? home_url('/tool/?id='.$wi_id) : home_url('/machine/?id='.$wi_id);
                    $shown++;
              ?>
              <a class="ih-wish-item" href="<?php echo esc_url($wi_url); ?>">
                <img class="ih-wish-thumb" src="<?php echo esc_url($wi_img); ?>" alt="">
                <div>
                  <div class="ih-wish-title"><?php echo esc_html($wi_title); ?></div>
                  <div class="ih-wish-type"><?php echo ucfirst(esc_html($wi_type)); ?></div>
                </div>
                <button class="ih-wish-remove" onclick="ihRemoveWishlist(event,<?php echo $wi_id; ?>,'<?php echo esc_js($wi_type); ?>')">✕</button>
              </a>
              <?php endforeach; endif; ?>
              <div class="ih-notif-footer"><a href="<?php echo esc_url($ih_dashboard_url); ?>">View Dashboard →</a></div>
              <?php else : ?>
              <div class="ih-notif-empty"><div style="font-size:32px;margin-bottom:8px;">🤍</div>No saved listings yet.<br><small>Tap ❤ on any machine or tool.</small></div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- ── ENVELOPE / Messages ── -->
          <div class="ih-nav-icon-wrap">
            <button class="ih-icon-btn" onclick="ihIconClick('ihMsgDrop',event)" title="Messages">
              <i class="fa-regular fa-envelope" style="font-size:15px;color:#374151;"></i>
              <?php if ( $ih_user_logged_in && $ih_unread_msgs > 0 ) : ?>
              <span class="ih-icon-badge"><?php echo $ih_unread_msgs; ?></span>
              <?php endif; ?>
            </button>
            <?php if ( $ih_user_logged_in ) : ?>
            <div class="ih-nav-dropdown" id="ihMsgDrop">
              <div class="ih-msg-head">
                <span>Messages</span>
                <?php if ( $ih_unread_msgs > 0 ) : ?><span class="ih-notif-count-badge"><?php echo $ih_unread_msgs; ?> unread</span><?php endif; ?>
              </div>
              <?php
                global $wpdb;
                $threads = $wpdb->get_results( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ih_threads WHERE user_id=%d ORDER BY last_time DESC LIMIT 4",
                    get_current_user_id()
                ), ARRAY_A ) ?: [];

                if ( $threads ) :
                  foreach ( $threads as $th ) :
                    $th_other_user_id = $th['other_party_id']   ?? 0;
                    $th_real_name     = $th['other_party_name'] ?? 'User';
                    $th_preview       = $th['last_message']     ?? 'New message';
                    $th_unread        = ! empty($th['unread']);

                    // ── Display: admin sees name + ID, others see only ID ──
                    if ( current_user_can('manage_options') && $th_other_user_id ) {
                        $th_display_uid   = ih_get_plain_id( $th_other_user_id );
                        $th_display_label = $th_real_name;   // admin sees name
                        $th_show_uid_pill = true;
                    } elseif ( $th_other_user_id ) {
                        $th_display_uid   = ih_get_plain_id( $th_other_user_id );
                        $th_display_label = $th_display_uid;  // others see only ID
                        $th_show_uid_pill = false;
                    } else {
                        $th_display_uid   = 'ADMIN';
                        $th_display_label = current_user_can('manage_options') ? $th_real_name : 'ADMIN';
                        $th_show_uid_pill = false;
                    }

                    // Avatar initial: use first char of ID (not name) for non-admins
                    $th_avatar_char = current_user_can('manage_options')
                        ? strtoupper( substr($th_real_name, 0, 1) )
                        : strtoupper( substr($th_display_uid, 0, 1) );
              ?>
              <a class="ih-msg-item" href="<?php echo esc_url($ih_messages_url); ?>" style="text-decoration:none;">
                <div class="ih-msg-avatar"><?php echo esc_html($th_avatar_char); ?></div>
                <div style="min-width:0;flex:1;">
                  <div class="<?php echo current_user_can('manage_options') ? 'ih-msg-name-admin' : 'ih-msg-name'; ?>">
                    <?php echo esc_html($th_display_label); ?>
                  </div>
                  <?php if ( $th_show_uid_pill ) : ?>
                  <div class="ih-msg-id-pill"><?php echo esc_html($th_display_uid); ?></div>
                  <?php endif; ?>
                  <div class="ih-msg-preview"><?php echo esc_html($th_preview); ?></div>
                </div>
                <?php if ($th_unread) : ?><span class="ih-msg-unread-dot"></span><?php endif; ?>
              </a>
              <?php endforeach;
                else : ?>
              <div class="ih-notif-empty"><div style="font-size:32px;margin-bottom:8px;">✉️</div>No messages yet.</div>
              <?php endif; ?>
              <div class="ih-msg-footer"><a href="<?php echo esc_url($ih_messages_url); ?>">Open Inbox →</a></div>
            </div>
            <?php endif; ?>
          </div>

          <!-- ── BELL / Notifications ── -->
          <div class="ih-nav-icon-wrap">
            <button class="ih-icon-btn" onclick="ihIconClick('ihNotifDrop',event)" title="Notifications">
              <i class="fa-regular fa-bell" style="font-size:15px;color:#374151;"></i>
              <?php if ( $ih_user_logged_in && $ih_notif_count > 0 ) : ?>
              <span class="ih-icon-badge"><?php echo $ih_notif_count; ?></span>
              <?php endif; ?>
            </button>
            <?php if ( $ih_user_logged_in ) : ?>
            <div class="ih-nav-dropdown" id="ihNotifDrop">
              <div class="ih-notif-head">
                <span>Notifications</span>
                <?php if ( $ih_notif_count > 0 ) : ?><span class="ih-notif-count-badge"><?php echo $ih_notif_count; ?> new</span><?php endif; ?>
              </div>
              <?php if ( $ih_notifs ) :
                foreach ( $ih_notifs as $n ) :
                  $n_type = $n['listing_type'] ?? 'listing';
                  $n_date = !empty($n['request_date']) ? date('d M',strtotime($n['request_date'])) : '';
              ?>
              <div class="ih-notif-item" id="ihNotif<?php echo (int)$n['id']; ?>">
                <div class="ih-notif-icon">✅</div>
                <div style="flex:1;min-width:0;">
                  <div class="ih-notif-title">Your <?php echo esc_html($n_type); ?> was approved!</div>
                  <div class="ih-notif-time"><?php echo esc_html($n_date); ?></div>
                </div>
                <button class="ih-notif-dismiss" onclick="ihDismissNotif(<?php echo (int)$n['id']; ?>)">✕</button>
              </div>
              <?php endforeach; ?>
              <div class="ih-notif-footer"><a href="<?php echo esc_url($ih_dashboard_url); ?>">Go to Dashboard →</a></div>
              <?php else : ?>
              <div class="ih-notif-empty"><div style="font-size:32px;margin-bottom:8px;">🔔</div>You're all caught up!</div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- ── AVATAR / Profile ── -->
          <div class="ih-nav-icon-wrap" style="margin-left:4px;">
            <?php if ( $ih_user_logged_in ) : ?>
            <button class="ih-icon-btn" style="padding:0;overflow:hidden;" onclick="ihIconClick('ihProfileDrop',event)" title="Account">
              <img src="<?php echo esc_url($ih_gravatar_url); ?>"
                   onerror="this.onerror=null;this.src='<?php echo esc_js($ih_fallback_avatar); ?>'"
                   style="width:38px;height:38px;object-fit:cover;border-radius:50%;"
                   alt="<?php echo esc_attr($ih_user_name); ?>">
            </button>
            <div class="ih-nav-dropdown ih-profile-drop" id="ihProfileDrop">
              <div class="ih-profile-user">
                <div class="ih-profile-avatar">
                  <img src="<?php echo esc_url($ih_gravatar_url); ?>" onerror="this.onerror=null;this.src='<?php echo esc_js($ih_fallback_avatar); ?>'" alt="">
                </div>
                <div style="min-width:0;">
                  <?php if ( current_user_can('manage_options') ) : ?>
                    <!-- Admin sees full name -->
                    <div class="ih-profile-name"><?php echo esc_html($ih_user_name); ?></div>
                  <?php else : ?>
                    <!-- Regular user: show their own ID as name, email below -->
                    <div class="ih-profile-name"><?php echo esc_html($ih_user_name); ?></div>
                  <?php endif; ?>
                  <!-- Always show unique ID badge -->
                  <div class="ih-profile-uid">🪪 <?php echo esc_html($ih_current_unique_id); ?></div>
                  <div class="ih-profile-email"><?php echo esc_html($ih_user_email); ?></div>
                </div>
              </div>
              <a class="ih-profile-link" href="<?php echo esc_url($ih_dashboard_url); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</a>
              <a class="ih-profile-link" href="<?php echo esc_url($ih_profile_url); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Edit Profile</a>
              <a class="ih-profile-link" href="<?php echo esc_url($ih_messages_url); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Messages<?php if ($ih_unread_msgs>0) : ?><span class="badge"><?php echo $ih_unread_msgs; ?></span><?php endif; ?></a>
              <a class="ih-profile-link" href="<?php echo esc_url($ih_dashboard_url); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 10c-.83 0-1.5-.67-1.5-1.5v-5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5z"/><path d="M20.5 10H19V8.5c0-.83.67-1.5 1.5-1.5s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/><path d="M9.5 14c.83 0 1.5.67 1.5 1.5v5c0 .83-.67 1.5-1.5 1.5S8 21.33 8 20.5v-5c0-.83.67-1.5 1.5-1.5z"/><path d="M3.5 14H5v1.5c0 .83-.67 1.5-1.5 1.5S2 16.33 2 15.5 2.67 14 3.5 14z"/><path d="M14 14.5c0-.83.67-1.5 1.5-1.5h5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-5c-.83 0-1.5-.67-1.5-1.5z"/><path d="M15.5 19H14v1.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5-.67-1.5-1.5-1.5z"/><path d="M10 9.5C10 8.67 9.33 8 8.5 8h-5C2.67 8 2 8.67 2 9.5S2.67 11 3.5 11h5c.83 0 1.5-.67 1.5-1.5z"/><path d="M8.5 5H10V3.5C10 2.67 9.33 2 8.5 2S7 2.67 7 3.5 7.67 5 8.5 5z"/></svg>My Listings</a>
              <div class="ih-profile-divider"></div>
              <a class="ih-profile-link danger" href="<?php echo esc_url($ih_logout_url); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a>
            </div>
            <?php else : ?>
            <button class="ih-icon-btn" style="padding:0;overflow:hidden;"
              onclick="ihShowModal('login');ihCloseAllDrops();if(event)event.stopPropagation();" title="Login">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/user.png"
                   style="width:38px;height:38px;border-radius:50%;" alt="Login">
            </button>
            <?php endif; ?>
          </div>

        </div>

        <div class="hidden lg:flex gap-2 shrink-0">
          <a href="<?php echo esc_url($add_tool_url); ?>"
             onclick="<?php echo !$ih_user_logged_in ? 'ihShowModal(\'login\');return false;' : ''; ?>"
             class="im-header-cta flex items-center bg-white px-4 py-2 rounded-full text-sm xl:text-[15px] font-semibold shadow-sm font-[Montserrat] gap-2 text-[#19191A] hover:bg-[#E8FBC4] transition-all duration-200 no-underline whitespace-nowrap">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/user-tools.png" alt="Tools" style="width:18px;height:18px;"> Add Tools
          </a>
          <a href="<?php echo esc_url($add_machine_url); ?>"
             onclick="<?php echo !$ih_user_logged_in ? 'ihShowModal(\'login\');return false;' : ''; ?>"
             class="im-header-cta flex items-center bg-white px-4 py-2 rounded-full text-sm xl:text-[15px] font-semibold shadow-sm font-[Montserrat] gap-2 text-[#19191A] hover:bg-[#E8FBC4] transition-all duration-200 no-underline whitespace-nowrap">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Machine-user.png" alt="Machine-user" style="width:18px;height:18px;"> Add Machines
          </a>
        </div>

        <button id="mobile-toggle" class="lg:hidden p-2 text-[#1D3C34]">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- BOTTOM NAV -->
    <div id="nav-menu" style="display:none;" class="bg-white px-8 py-4 rounded-b-[12px] items-center justify-between gap-4">
      <nav class="flex flex-col lg:flex-row items-start lg:items-center gap-4 text-[17px] text-[#19191A] font-[Poppins] font-medium w-full lg:w-auto">
        <a href="<?php echo esc_url( home_url('/') ); ?>" class="px-5 py-2 rounded-full hover:bg-[#E9FFB2] hover:text-[#052328] transition-all duration-300 <?php echo is_front_page() ? 'bg-[#E9FFB2]' : ''; ?>">Home</a>
        <a href="<?php echo esc_url( home_url('/about') ); ?>" class="px-5 py-2 rounded-full hover:bg-[#E9FFB2] hover:text-[#052328] transition-all duration-300 <?php echo is_page('about') ? 'bg-[#E9FFB2]' : ''; ?>">About</a>

        <div class="relative w-full lg:w-auto" id="services-wrap">
          <button id="services-btn" onclick="ihToggleServicesDrop(event)"
            class="px-4 py-1.5 rounded-full flex items-center gap-1 text-[#19191A] outline-none hover:bg-[#E9FFB2] hover:text-[#052328] transition-all duration-300">
            Services <span class="text-[10px] pointer-events-none"><i class="fa-solid fa-angle-down" id="services-arrow"></i></span>
          </button>
          <div id="services-dropdown" style="display:none;"
               class="lg:absolute top-full left-0 mt-2 w-full lg:w-64 bg-white rounded-2xl p-2 shadow-xl border border-gray-100 z-[100]">
            <?php
            wp_nav_menu(array(
              'theme_location' => 'services',
              'container'      => false,
              'menu_class'     => '',
              'fallback_cb'    => false,
              'items_wrap'     => '%3$s',
              'walker'         => new class extends Walker_Nav_Menu {
                function start_el(&$output,$item,$depth=0,$args=null,$id=0) {
                  $active = (get_permalink()==$item->url) ? 'bg-[#E9FFB2]' : '';
                  $output .= '<a href="'.esc_url($item->url).'" class="block px-4 py-2 text-sm hover:bg-[#E9FFB2] rounded-xl transition-colors font-medium font-[Poppins] text-[18px] mb-2 '.$active.'">'.esc_html($item->title).'</a>';
                }
              }
            ));
            ?>
          </div>
        </div>

        <a href="/how-it-work" class="flex items-center gap-1 px-5 py-2 rounded-full hover:bg-[#E9FFB2] hover:text-[#052328] transition-all duration-300">
          How It Works 
          <!-- <span class="text-[10px]"><i class="fa-solid fa-angle-down"></i></span> -->
        </a>
      </nav>

      <div id="mobile-extras" style="display:none;" class="flex-col w-full gap-6 pt-4">
        <div class="flex items-center justify-center gap-4">

          <div class="ih-nav-icon-wrap">
            <button class="ih-icon-btn" onclick="ihIconClick('ihWishDropMob',event)">
              <i class="fa-regular fa-heart" style="font-size:16px;color:#374151;"></i>
              <?php if ($ih_user_logged_in && $ih_wishlist_count>0) : ?><span class="ih-icon-badge" style="border-color:#fff;"><?php echo $ih_wishlist_count; ?></span><?php endif; ?>
            </button>
            <?php if ($ih_user_logged_in) : ?>
            <div class="ih-nav-dropdown" id="ihWishDropMob" style="right:auto;left:0;">
              <div class="ih-wish-head"><span>Wishlist</span></div>
              <div class="ih-notif-empty" style="font-size:12px;">
                <?php echo $ih_wishlist_count>0 ? $ih_wishlist_count.' saved listing(s)' : 'No saved listings.'; ?>
                <br><a href="<?php echo esc_url($ih_dashboard_url); ?>" style="color:#1f3d2e;font-weight:600;">View Dashboard →</a>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <div class="ih-nav-icon-wrap">
            <button class="ih-icon-btn" onclick="ihIconClick('ihMsgDropMob',event)">
              <i class="fa-regular fa-envelope" style="font-size:16px;color:#374151;"></i>
              <?php if ($ih_user_logged_in && $ih_unread_msgs>0) : ?><span class="ih-icon-badge" style="border-color:#fff;"><?php echo $ih_unread_msgs; ?></span><?php endif; ?>
            </button>
            <?php if ($ih_user_logged_in) : ?>
            <div class="ih-nav-dropdown" id="ihMsgDropMob" style="right:auto;left:0;">
              <div class="ih-msg-head"><span>Messages</span></div>
              <div class="ih-notif-empty" style="font-size:12px;">
                <?php echo $ih_unread_msgs>0 ? $ih_unread_msgs.' unread message(s)' : 'No new messages.'; ?>
                <br><a href="<?php echo esc_url($ih_messages_url); ?>" style="color:#1f3d2e;font-weight:600;">Open Inbox →</a>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <div class="ih-nav-icon-wrap">
            <button class="ih-icon-btn" onclick="ihIconClick('ihNotifDropMob',event)">
              <i class="fa-regular fa-bell" style="font-size:16px;color:#374151;"></i>
              <?php if ($ih_user_logged_in && $ih_notif_count>0) : ?><span class="ih-icon-badge" style="border-color:#fff;"><?php echo $ih_notif_count; ?></span><?php endif; ?>
            </button>
            <?php if ($ih_user_logged_in) : ?>
            <div class="ih-nav-dropdown" id="ihNotifDropMob" style="right:auto;left:0;">
              <div class="ih-notif-head"><span>Notifications</span></div>
              <div class="ih-notif-empty" style="font-size:12px;">
                <?php echo $ih_notif_count>0 ? $ih_notif_count.' new notification(s)' : 'No new notifications.'; ?>
                <br><a href="<?php echo esc_url($ih_dashboard_url); ?>" style="color:#1f3d2e;font-weight:600;">Dashboard →</a>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <div class="ih-nav-icon-wrap">
            <?php if ($ih_user_logged_in) : ?>
            <button class="ih-icon-btn" style="padding:0;overflow:hidden;" onclick="ihIconClick('ihProfileDropMob',event)">
              <img src="<?php echo esc_url($ih_gravatar_url); ?>"
                   onerror="this.onerror=null;this.src='<?php echo esc_js($ih_fallback_avatar); ?>'"
                   style="width:38px;height:38px;object-fit:cover;border-radius:50%;" alt="">
            </button>
            <div class="ih-nav-dropdown ih-profile-drop" id="ihProfileDropMob" style="right:auto;left:0;">
              <div class="ih-profile-user">
                <div class="ih-profile-avatar">
                  <img src="<?php echo esc_url($ih_gravatar_url); ?>" onerror="this.onerror=null;this.src='<?php echo esc_js($ih_fallback_avatar); ?>'" alt="">
                </div>
                <div style="min-width:0;">
                  <div class="ih-profile-name"><?php echo esc_html($ih_user_name); ?></div>
                  <div class="ih-profile-uid">🪪 <?php echo esc_html($ih_current_unique_id); ?></div>
                  <div class="ih-profile-email"><?php echo esc_html($ih_user_email); ?></div>
                </div>
              </div>
              <a class="ih-profile-link" href="<?php echo esc_url($ih_dashboard_url); ?>">Dashboard</a>
              <a class="ih-profile-link" href="<?php echo esc_url($ih_profile_url); ?>">Edit Profile</a>
              <a class="ih-profile-link" href="<?php echo esc_url($ih_messages_url); ?>">Messages<?php if($ih_unread_msgs>0): ?><span class="badge"><?php echo $ih_unread_msgs; ?></span><?php endif; ?></a>
              <div class="ih-profile-divider"></div>
              <a class="ih-profile-link danger" href="<?php echo esc_url($ih_logout_url); ?>">Logout</a>
            </div>
            <?php else : ?>
            <button class="ih-icon-btn" style="padding:0;overflow:hidden;"
              onclick="ihShowModal('login');if(event)event.stopPropagation();" title="Login">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/user.png"
                   style="width:38px;height:38px;border-radius:50%;" alt="Login">
            </button>
            <?php endif; ?>
          </div>
        </div>

        <div class="flex md:hidden flex-col gap-3">
          <a href="<?php echo esc_url($add_tool_url); ?>"
             onclick="<?php echo !$ih_user_logged_in ? 'ihShowModal(\'login\');return false;' : ''; ?>"
             class="flex items-center justify-center bg-[#F3F4F6] py-4 rounded-xl gap-3 active:scale-95 transition-transform text-[18px] font-bold font-[Montserrat] font-medium text-[#19191A] no-underline">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/user-tools.png" alt="Tools" style="width:18px;height:18px;"> Add Tools
          </a>
          <a href="<?php echo esc_url($add_machine_url); ?>"
             onclick="<?php echo !$ih_user_logged_in ? 'ihShowModal(\'login\');return false;' : ''; ?>"
             class="flex items-center justify-center bg-[#F3F4F6] py-4 rounded-xl gap-3 active:scale-95 transition-transform text-[18px] font-bold font-[Montserrat] font-medium text-[#19191A] no-underline">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Machine-user.png" alt="Machine" style="width:18px;height:18px;"> Add Machines
          </a>
        </div>
      </div>

     <button
    onclick="window.location.href='<?php echo site_url('/contact-us/'); ?>'"
    class="w-full lg:w-auto bg-[#0C3131] text-white px-8 py-3 rounded-full font-bold flex items-center justify-center gap-2 hover:opacity-90 transition-all text-[18px] font-[Montserrat] mt-4">
    Contact us
    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right.png" alt="" class="w-[20px] h-[20px]">
</button>
    </div>

  </div>
</header>

<script>
var ihUserLoggedIn = <?php echo $ih_user_logged_in ? 'true' : 'false'; ?>;
var ihmAjaxUrl    = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
var ihmDashUser   = <?php echo json_encode(admin_url('admin.php?page=ih-user-dashboard')); ?>;
var ihmDashAdmin  = <?php echo json_encode(admin_url('admin.php?page=ih-dashboard')); ?>;
var ihmRegNonce   = <?php echo json_encode(wp_create_nonce('im_register')); ?>;
var ihmLoginNonce = <?php echo json_encode(wp_create_nonce('im_login')); ?>;

/* ── Modal open/close ── */
function ihShowModal(tab) {
    tab = tab || 'login';
    ihmSwitchTab(tab);
    document.getElementById('ih-auth-overlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function ihCloseModal() {
    document.getElementById('ih-auth-overlay').classList.remove('active');
    document.body.style.overflow = '';
}
function ihModalOutsideClick(e) {
    if (e.target === document.getElementById('ih-auth-overlay')) ihCloseModal();
}

/* ── Tab switch ── */
function ihmSwitchTab(tab) {
    ['login','register'].forEach(function(t){
        document.getElementById('ihm-tab-'+t).classList.toggle('active', t===tab);
        document.getElementById('ihm-panel-'+t).classList.toggle('active', t===tab);
    });
}

/* ── Password toggle ── */
function ihmTogglePass(fieldId, eyeId) {
    var inp = document.getElementById(fieldId);
    var eye = document.getElementById(eyeId);
    if (!inp) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        if(eye) eye.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        inp.type = 'password';
        if(eye) eye.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}

/* ── Password match check ── */
document.addEventListener('DOMContentLoaded', function(){
    function chk(){
        var p1=document.getElementById('ihm-password');
        var p2=document.getElementById('ihm-password-conf');
        var msg=document.getElementById('ihm-pass-msg');
        if(!p1||!p2||!msg) return;
        if(!p2.value){msg.innerHTML='';p2.style.borderColor='';return;}
        if(p1.value===p2.value){p2.style.borderColor='#16a34a';msg.style.color='#16a34a';msg.innerHTML='✔ Passwords match';}
        else{p2.style.borderColor='#dc2626';msg.style.color='#dc2626';msg.innerHTML='✖ Passwords do not match';}
    }
    var p1=document.getElementById('ihm-password');
    var p2=document.getElementById('ihm-password-conf');
    if(p1) p1.addEventListener('input',chk);
    if(p2) p2.addEventListener('input',chk);

    /* ── Live ID preview (modal) ── */
    var mContact  = document.getElementById('ihm-contact');
    var mCity     = document.getElementById('ihm-city');
    var mPreview  = document.getElementById('ihm-id-preview');
    var mPreviewV = document.getElementById('ihm-id-preview-val');

    function updateModalIdPreview() {
        var name = (mContact ? mContact.value : '').replace(/[^a-zA-Z]/g,'');
        var city = (mCity    ? mCity.value    : '').replace(/[^a-zA-Z]/g,'');
        var np   = name.substr(0,3).toUpperCase().padEnd(3,'X');
        var cp   = city.substr(0,3).toUpperCase().padEnd(3,'X');
        if (name.length >= 1) {
            mPreviewV.textContent = np + cp + '###';
            mPreview.classList.add('visible');
        } else {
            mPreview.classList.remove('visible');
        }
    }
    if (mContact) mContact.addEventListener('input', updateModalIdPreview);
    if (mCity)    mCity.addEventListener('input',    updateModalIdPreview);
});

/* ── Show errors ── */
function ihmShowErrors(id, errs) {
    var el=document.getElementById(id); if(!el) return;
    if(!errs||!errs.length){el.style.display='none';el.innerHTML='';return;}
    el.innerHTML=errs.map(function(e){return '<p>⚠️ '+e+'</p>';}).join('');
    el.style.display='block';
    el.scrollIntoView({behavior:'smooth',block:'nearest'});
}

/* ── LOGIN submit ── */
function ihmSubmitLogin() {
    var email=(document.getElementById('ihm-login-email')||{}).value||'';
    var pass=(document.getElementById('ihm-login-pass')||{}).value||'';
    var rem=(document.getElementById('ihm-remember')||{}).checked||false;
    var errs=[];
    if(!email) errs.push('Please enter your email address.');
    if(!pass)  errs.push('Please enter your password.');
    if(errs.length){ihmShowErrors('ihm-login-error',errs);return;}
    var btn=document.getElementById('ihm-login-btn');
    if(btn){btn.classList.add('loading');btn.textContent='Logging in…';}
    var fd=new FormData();
    fd.append('action','ih_modal_login');
    fd.append('nonce',ihmLoginNonce);
    fd.append('login_email',email);
    fd.append('login_password',pass);
    fd.append('remember',rem?'1':'');
    fetch(ihmAjaxUrl,{method:'POST',body:fd})
      .then(function(r){return r.json();})
      .then(function(data){
          if(data.success){window.location.href=data.data.redirect||ihmDashUser;}
          else{
              ihmShowErrors('ihm-login-error',[data.data||'Login failed.']);
              if(btn){btn.classList.remove('loading');btn.innerHTML='Login <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>';}
          }
      })
      .catch(function(){ihmShowErrors('ihm-login-error',['Something went wrong.']);if(btn){btn.classList.remove('loading');btn.textContent='Login';}});
}

/* ── REGISTER submit ── */
function ihmSubmitRegister() {
    var g=function(id){return(document.getElementById(id)||{}).value||'';};
    var cb=function(id){return(document.getElementById(id)||{}).checked||false;};
    var errs=[];
    if(!g('ihm-biz-role'))     errs.push('Please select a business role.');
    if(!g('ihm-company'))      errs.push('Company name is required.');
    if(!g('ihm-contact'))      errs.push('Contact name is required.');
    if(!g('ihm-email'))        errs.push('Email address is required.');
    if(g('ihm-email')!==g('ihm-email-conf')) errs.push('Email addresses do not match.');
    if(!g('ihm-password'))     errs.push('Password is required.');
    if(g('ihm-password').length<8) errs.push('Password must be at least 8 characters.');
    if(g('ihm-password')!==g('ihm-password-conf')) errs.push('Passwords do not match.');
    if(!cb('ihm-agree-terms')) errs.push('You must agree to the Terms & Conditions.');
    if(!cb('ihm-agree-fees'))  errs.push('You must understand that fees may apply.');
    if(errs.length){ihmShowErrors('ihm-reg-errors',errs);return;}
    var btn=document.getElementById('ihm-register-btn');
    if(btn){btn.classList.add('loading');btn.textContent='Registering…';}
    var fd=new FormData();
    fd.append('action','ih_modal_register');
    fd.append('nonce',ihmRegNonce);
    fd.append('biz_role',g('ihm-biz-role'));
    fd.append('company_name',g('ihm-company'));
    fd.append('contact_name',g('ihm-contact'));
    fd.append('job_title',g('ihm-jobtitle'));
    fd.append('address',g('ihm-address'));
    fd.append('city',g('ihm-city'));
    fd.append('postcode',g('ihm-postcode'));
    fd.append('office_number',g('ihm-office'));
    fd.append('website',g('ihm-website'));
    fd.append('phone',g('ihm-phone'));
    fd.append('email',g('ihm-email'));
    fd.append('email_confirm',g('ihm-email-conf'));
    fd.append('password',g('ihm-password'));
    fd.append('password_confirm',g('ihm-password-conf'));
    fd.append('agree_terms',cb('ihm-agree-terms')?'1':'');
    fd.append('agree_fees',cb('ihm-agree-fees')?'1':'');
    fetch(ihmAjaxUrl,{method:'POST',body:fd})
      .then(function(r){return r.json();})
      .then(function(data){
          if(data.success){window.location.href=data.data.redirect||ihmDashUser;}
          else{
              var msg=Array.isArray(data.data)?data.data:[data.data||'Registration failed.'];
              ihmShowErrors('ihm-reg-errors',msg);
              if(btn){btn.classList.remove('loading');btn.textContent='Register';}
          }
      })
      .catch(function(){ihmShowErrors('ihm-reg-errors',['Something went wrong.']);if(btn){btn.classList.remove('loading');btn.textContent='Register';}});
}

/* ── Dropdowns ── */
var _ihOpenDrop=null;
function ihCloseAllDrops(){
    if(_ihOpenDrop){_ihOpenDrop.style.display='none';_ihOpenDrop=null;}
    document.querySelectorAll('.ih-nav-dropdown').forEach(function(el){el.style.display='none';});
}
function ihToggleDrop(id,e){
    if(e) e.stopPropagation();
    var el=document.getElementById(id); if(!el) return;
    if(_ihOpenDrop&&_ihOpenDrop!==el){_ihOpenDrop.style.display='none';_ihOpenDrop=null;}
    if(el.style.display==='block'){el.style.display='none';_ihOpenDrop=null;}
    else{el.style.display='block';_ihOpenDrop=el;}
}
function ihToggleServicesDrop(e){
    if(e) e.stopPropagation();
    var drop=document.getElementById('services-dropdown'), arrow=document.getElementById('services-arrow');
    if(!drop) return;
    ihCloseModal();
    if(drop.style.display==='block'){drop.style.display='none';if(arrow) arrow.style.transform='rotate(0deg)';}
    else{ihCloseAllDrops();drop.style.display='block';if(arrow){arrow.style.transform='rotate(180deg)';arrow.style.transition='transform .2s';}}
}
function ihIconClick(dropId,e){
    if(e) e.stopPropagation();
    var sd=document.getElementById('services-dropdown');
    if(sd) sd.style.display='none';
    if(!ihUserLoggedIn){ihShowModal('login');return;}
    if(!dropId) return;
    ihToggleDrop(dropId,e);
}

/* ── Outside click closes everything ── */
document.addEventListener('click',function(e){
    var sd=document.getElementById('services-dropdown');
    if(sd&&sd.style.display==='block'&&!e.target.closest('#services-wrap')){
        sd.style.display='none';
        var arrow=document.getElementById('services-arrow');
        if(arrow) arrow.style.transform='rotate(0deg)';
    }
    if(_ihOpenDrop&&!e.target.closest('.ih-nav-icon-wrap')) ihCloseAllDrops();
});

/* ── ESC closes modal ── */
document.addEventListener('keydown',function(e){if(e.key==='Escape') ihCloseModal();});

/* ── Enter to login ── */
document.addEventListener('keydown',function(e){
    if(e.key!=='Enter') return;
    if(!document.getElementById('ih-auth-overlay').classList.contains('active')) return;
    if(document.getElementById('ihm-panel-login').classList.contains('active')) ihmSubmitLogin();
});

/* ── Notifications & Wishlist ── */
/* Front-end fallback: ihAjax is only localized on admin (ih-*) screens, so on the
   public site provide a url + ih_nonce so wishlist/notification calls carry a valid nonce. */
window.ihAjax = window.ihAjax || { url: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, nonce: <?php echo wp_json_encode( wp_create_nonce( 'ih_nonce' ) ); ?> };
function ihDismissNotif(id){
    var el=document.getElementById('ihNotif'+id);
    if(el){el.style.opacity='0';el.style.transition='opacity .2s';setTimeout(function(){el.remove();},200);}
    if(window.ihAjax){var fd=new FormData();fd.append('action','ih_delete_notification');fd.append('nonce',window.ihAjax.nonce);fd.append('id',id);fetch(window.ihAjax.url,{method:'POST',body:fd}).catch(function(){});}
}
function ihRemoveWishlist(e,id,type){
    e.preventDefault();e.stopPropagation();
    var item=e.currentTarget.closest('.ih-wish-item');
    if(item){item.style.opacity='0';item.style.transition='opacity .2s';setTimeout(function(){item.remove();},200);}
    if(window.ihAjax){var fd=new FormData();fd.append('action','ih_toggle_wishlist');fd.append('nonce',window.ihAjax.nonce);fd.append('listing_id',id);fd.append('listing_type',type);fetch(window.ihAjax.url,{method:'POST',body:fd}).catch(function(){});}
}

/* ── Mobile nav ── */
(function(){
    var menu=document.getElementById('nav-menu'), toggle=document.getElementById('mobile-toggle'), extras=document.getElementById('mobile-extras');
    function updateExtras(){if(!extras)return;extras.style.display=(window.innerWidth<640)?'flex':'none';extras.style.flexDirection='column';}
    function initMenu(){if(window.innerWidth>=1024){menu.style.display='flex';menu.style.flexDirection='row';menu.style.alignItems='center';}else menu.style.display='none';updateExtras();}
    toggle.addEventListener('click',function(){if(menu.style.display==='none'||menu.style.display===''){menu.style.display='flex';menu.style.flexDirection='column';menu.style.alignItems='flex-start';updateExtras();}else menu.style.display='none';});
    window.addEventListener('resize',function(){if(window.innerWidth>=1024){menu.style.display='flex';menu.style.flexDirection='row';menu.style.alignItems='center';}else menu.style.display='none';updateExtras();});
    initMenu();
})();
</script>