<?php defined( 'ABSPATH' ) || exit;



$nav_mode   = isset( $args['mode'] ) ? sanitize_key( $args['mode'] ) : ih_site_nav_mode();

$user_id    = get_current_user_id();

$user       = wp_get_current_user();

$name_parts = preg_split( '/\s+/', trim( $user->display_name ?: $user->user_login ?: 'User' ) );

$initials   = strtoupper( substr( $name_parts[0] ?? 'U', 0, 1 ) . substr( $name_parts[1] ?? '', 0, 1 ) );

$avatar_url = function_exists( 'ih_get_user_avatar_url' )

    ? ih_get_user_avatar_url( $user_id, 64 )

    : get_avatar_url( $user_id, array( 'size' => 64 ) );

$profile_page = $nav_mode === 'admin' ? 'ih-dashboard' : 'ih-user-edit-profile';

?>

<header class="ih-site-nav-header is-<?php echo esc_attr( $nav_mode ); ?>" role="banner" data-figma-node="33:781">



  <?php if ( $nav_mode === 'admin' ) : ?>

  <div class="ih-site-nav-logo ih-site-nav-logo-brand" id="ihSiteNavLogoBrand">

    <span class="ih-site-nav-logo-text ih-site-nav-logo-text--stack">

      <strong>Injection</strong>

      <em>Moulding</em>

    </span>

  </div>



  <?php include IH_DIR . 'pages/partials/ih-admin-global-search.php'; ?>

  <?php include IH_DIR . 'pages/partials/ih-admin-header-datetime.php'; ?>

  <div class="ih-site-nav-header-actions">

    <?php include IH_DIR . 'pages/partials/ih-admin-bell-panel.php'; ?>

    <?php
    $nav_mode = 'admin';
    include IH_DIR . 'pages/partials/ih-site-nav-account-menu.php';
    ?>

  </div>

  <?php else : ?>

  <button type="button" class="ih-site-nav-logo ih-site-nav-logo-trigger" id="ihSiteNavLogoBtn" aria-label="<?php esc_attr_e( 'Open navigation menu', 'insight-hub-dashboard' ); ?>" aria-expanded="false">

    <span class="ih-site-nav-logo-text ih-site-nav-logo-text--stack">

      <strong>Injection</strong>

      <em>Moulding</em>

    </span>

  </button>



  <div class="ih-site-nav-header-actions">

    <?php
    $user_badges        = function_exists( 'ih_site_nav_badges' ) ? ih_site_nav_badges() : array( 'enq_pending' => 0 );
    $user_pending_alert = (int) ( $user_badges['enq_pending'] ?? 0 );
    ?>

    <a class="ih-site-nav-icon-btn ih-site-nav-bell-btn" id="ihSiteNavBellBtn" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-dashboard#ihEnquiriesSection' ) ); ?>" aria-label="<?php echo esc_attr( $user_pending_alert > 0 ? sprintf( _n( '%d pending request notification', '%d pending request notifications', $user_pending_alert, 'insight-hub-dashboard' ), $user_pending_alert ) : __( 'Notifications', 'insight-hub-dashboard' ) ); ?>">

      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>

      <?php if ( $user_pending_alert > 0 ) : ?>
        <span class="ih-site-nav-bell-dot" aria-hidden="true"><?php echo $user_pending_alert > 9 ? '9+' : (int) $user_pending_alert; ?></span>
      <?php endif; ?>

    </a>

    <?php
    $nav_mode = 'user';
    include IH_DIR . 'pages/partials/ih-site-nav-account-menu.php';
    ?>

  </div>

  <?php endif; ?>



</header>


