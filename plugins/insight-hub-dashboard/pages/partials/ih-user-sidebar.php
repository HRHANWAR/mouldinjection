<?php defined( 'ABSPATH' ) || exit;

$current_page = ih_site_nav_current_page();
$items        = ih_site_nav_user_sidebar_items();
$user         = wp_get_current_user();
$gravatar_url = function_exists( 'ih_get_user_avatar_url' )
    ? ih_get_user_avatar_url( get_current_user_id(), 72 )
    : get_avatar_url( get_current_user_id(), array( 'size' => 72 ) );
$fallback_url = 'https://ui-avatars.com/api/?name=' . rawurlencode( $user->display_name ?: 'U' ) . '&background=164b3f&color=c8ff00&size=72&bold=true&rounded=true&length=2';
?>
<aside class="ih-sidebar" id="ihSidebar">
  <div class="ih-sb-brand">
    <span class="ih-sb-logo">M</span>
    <span class="ih-sb-brand-txt"><b><?php esc_html_e( 'MOULD INJECTION', 'insight-hub-dashboard' ); ?></b><i><?php esc_html_e( 'SUPPLIER PORTAL', 'insight-hub-dashboard' ); ?></i></span>
  </div>
  <div class="ih-sb-menu-label"><?php esc_html_e( 'Menu', 'insight-hub-dashboard' ); ?></div>

  <?php foreach ( $items as $item ) :
      $is_active = ih_site_nav_item_active( $item, $current_page );
      $badge     = isset( $item['badge'] ) ? (int) $item['badge'] : 0;
      $icon      = ( $is_active && ! empty( $item['icon_active'] ) ) ? $item['icon_active'] : $item['icon'];
      $onclick   = ! empty( $item['hash'] ) ? ' onclick="var el=document.querySelector(\'' . esc_js( $item['hash'] ) . '\');if(el){el.scrollIntoView({behavior:\'smooth\'});}return false;"' : '';
      ?>
  <a class="ih-nav-item<?php echo $is_active ? ' active' : ''; ?>"
     href="<?php echo esc_url( $item['url'] ); ?>"
     data-tip="<?php echo esc_attr( $item['label'] ); ?>"<?php echo $onclick; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <span class="ih-nav-label"><?php echo esc_html( $item['label'] ); ?></span>
    <?php if ( $badge > 0 ) : ?>
      <span class="ih-nav-badge <?php echo esc_attr( $item['badge_class'] ?? 'is-warn' ); ?>"><?php echo $badge > 99 ? '99+' : (int) $badge; ?></span>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>

  <details class="ih-profile-dropdown">
    <summary class="ih-nav-item" data-tip="<?php esc_attr_e( 'Account', 'insight-hub-dashboard' ); ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      <span class="ih-nav-label"><?php esc_html_e( 'Account', 'insight-hub-dashboard' ); ?></span>
    </summary>
    <div class="ih-dropdown-menu">
      <div class="ih-dropdown-user">
        <img class="ih-header-avatar" src="<?php echo esc_url( $gravatar_url ); ?>" alt="" onerror="this.onerror=null;this.src='<?php echo esc_js( $fallback_url ); ?>'">
        <div>
          <div style="font-size:13px;font-weight:700;color:var(--ih-figma-text);"><?php echo esc_html( $user->display_name ); ?></div>
          <div style="font-size:11px;color:var(--ih-figma-text-secondary);"><?php echo esc_html( $user->user_email ); ?></div>
        </div>
      </div>
      <a class="ih-dropdown-link" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"><?php esc_html_e( 'Logout', 'insight-hub-dashboard' ); ?></a>
    </div>
  </details>
</aside>
