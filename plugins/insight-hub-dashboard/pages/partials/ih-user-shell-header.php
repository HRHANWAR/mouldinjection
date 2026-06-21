<?php defined( 'ABSPATH' ) || exit;

if ( ! isset( $user ) || ! $user instanceof WP_User ) {
    $user = wp_get_current_user();
}
$user_id      = get_current_user_id();
$email_hash   = md5( strtolower( trim( $user->user_email ) ) );
$gravatar_url = function_exists( 'ih_get_user_avatar_url' )
    ? ih_get_user_avatar_url( $user_id, 72 )
    : 'https://www.gravatar.com/avatar/' . $email_hash . '?s=72&d=404';
$fallback_url = 'https://ui-avatars.com/api/?name=' . rawurlencode( $user->display_name ?: 'U' ) . '&background=164b3f&color=c8ff00&size=72&bold=true&rounded=true&length=2';
$search_ph    = isset( $ih_header_search_placeholder ) ? (string) $ih_header_search_placeholder : __( 'Search listings, keywords…', 'insight-hub-dashboard' );
$notifs       = $notifs ?? array();
$notif_count  = isset( $notif_count ) ? (int) $notif_count : count( (array) $notifs );
?>
<header class="ih-header ih-header--desktop">
  <button class="ih-hamburger" type="button" onclick="ihToggleSidebar()" aria-label="<?php esc_attr_e( 'Menu', 'insight-hub-dashboard' ); ?>" aria-controls="ihSidebar" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>
  <a class="ih-header-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="" onerror="this.style.display='none'">
    <span><?php esc_html_e( 'Injection Moulding', 'insight-hub-dashboard' ); ?></span>
  </a>
  <div class="ih-header-search" id="ihSearchOuter">
    <div class="ih-search-wrap">
      <span class="ih-search-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span>
      <input type="text" id="ihSearchInput" class="ih-search-input" placeholder="<?php echo esc_attr( $search_ph ); ?>" autocomplete="off">
      <button type="button" class="ih-search-clear" id="ihSearchClear" aria-label="<?php esc_attr_e( 'Clear', 'insight-hub-dashboard' ); ?>">✕</button>
      <div class="ih-search-kbd" id="ihSearchKbd"><kbd>⌘</kbd><kbd>K</kbd></div>
    </div>
    <div class="ih-search-dropdown" id="ihSearchDropdown"><div class="ih-search-dd-list" id="ihDdList"></div></div>
  </div>
  <div class="ih-header-right">
    <div class="ih-notif-wrap" id="ihNotifWrap">
      <button type="button" class="ih-header-bell" id="ihNotifBtn" aria-label="<?php esc_attr_e( 'Open notifications', 'insight-hub-dashboard' ); ?>" aria-controls="ihNotifBox" aria-expanded="false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <?php if ( $notif_count > 0 ) : ?><span class="ih-notif-dot" id="ihNotifDot"></span><?php endif; ?>
      </button>
      <div class="ih-notif-dropdown" id="ihNotifBox">
        <div class="ih-notif-head"><?php esc_html_e( 'Notifications', 'insight-hub-dashboard' ); ?>
          <?php if ( $notif_count > 0 ) : ?><span class="ih-notif-count"><?php echo (int) $notif_count; ?></span><?php endif; ?>
        </div>
        <?php if ( $notifs ) : foreach ( $notifs as $n ) : ?>
          <div class="ih-notif-item">
            <div class="ih-notif-icon">✅</div>
            <div class="ih-notif-copy">
              <div class="ih-notif-title"><?php printf( esc_html__( 'Your %s was approved', 'insight-hub-dashboard' ), esc_html( $n['listing_type'] ?? '' ) ); ?></div>
              <div class="ih-notif-time"><?php echo esc_html( $n['request_date'] ?? '' ); ?></div>
            </div>
            <button type="button" class="ih-notif-remove" onclick="this.closest('.ih-notif-item').remove()" aria-label="<?php esc_attr_e( 'Dismiss notification', 'insight-hub-dashboard' ); ?>">✕</button>
          </div>
        <?php endforeach; else : ?>
          <div class="ih-notif-empty"><?php esc_html_e( 'No notifications', 'insight-hub-dashboard' ); ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="ih-avatar-wrap" id="ihAccountWrap">
      <button type="button" class="ih-header-avatar-btn" id="ihAccountBtn" aria-label="<?php esc_attr_e( 'Open account menu', 'insight-hub-dashboard' ); ?>" aria-controls="ihAccountBox" aria-expanded="false" aria-haspopup="true">
        <img class="ih-header-avatar" src="<?php echo esc_url( $gravatar_url ); ?>" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <span class="ih-header-avatar-fallback" aria-hidden="true"><?php echo esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ); ?></span>
      </button>
      <div class="ih-account-dropdown" id="ihAccountBox" role="menu" aria-label="<?php esc_attr_e( 'Account', 'insight-hub-dashboard' ); ?>">
        <div class="ih-account-user">
          <div class="ih-account-avatar"><?php echo esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ); ?></div>
          <div class="ih-account-copy">
            <div class="ih-account-name"><?php echo esc_html( $user->display_name ); ?></div>
            <div class="ih-account-email"><?php echo esc_html( $user->user_email ); ?></div>
          </div>
        </div>
        <a class="ih-account-link" role="menuitem" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Site Home', 'insight-hub-dashboard' ); ?></a>
        <a class="ih-account-link" role="menuitem" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-dashboard' ) ); ?>"><?php esc_html_e( 'Dashboard', 'insight-hub-dashboard' ); ?></a>
        <a class="ih-account-link" role="menuitem" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-edit-profile' ) ); ?>"><?php esc_html_e( 'Edit Profile', 'insight-hub-dashboard' ); ?></a>
        <div class="ih-account-divider"></div>
        <a class="ih-account-link danger" role="menuitem" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"><?php esc_html_e( 'Logout', 'insight-hub-dashboard' ); ?></a>
      </div>
    </div>
  </div>
</header>
