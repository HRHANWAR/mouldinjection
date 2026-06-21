<?php defined( 'ABSPATH' ) || exit;

$current_page = ih_site_nav_current_page();
$items        = ih_site_nav_admin_sidebar_items();
$user         = wp_get_current_user();
$name_parts   = preg_split( '/\s+/', trim( $user->display_name ?: 'Admin' ) );
$initials     = strtoupper( substr( $name_parts[0] ?? 'A', 0, 1 ) . substr( $name_parts[1] ?? '', 0, 1 ) );
$ih_nav_pending = (int) ( ih_site_nav_badges()['pending'] ?? 0 );
$avatar_url   = function_exists( 'ih_get_user_avatar_url' )
    ? ih_get_user_avatar_url( $user->ID, 64 )
    : get_avatar_url( $user->ID, array( 'size' => 64 ) );
?>
<aside class="ih-sidebar ih-sidebar--smart-menu" id="ihSidebar" aria-label="<?php esc_attr_e( 'Admin navigation', 'insight-hub-dashboard' ); ?>">
  <div class="ih-sidebar-glass">
    <header class="ih-sidebar-glass__head">
      <div class="ih-sidebar-glass__brand">
        <span class="ih-sidebar-glass__title"><?php esc_html_e( 'Menu', 'insight-hub-dashboard' ); ?></span>
      </div>
      <button class="ih-sidebar-close ih-sidebar-glass__close" id="ihSidebarClose" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'insight-hub-dashboard' ); ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </header>

    <div class="ih-sidebar-label ih-sidebar-label--top ih-sidebar-glass__section"><?php esc_html_e( 'Control Centre', 'insight-hub-dashboard' ); ?></div>

    <nav class="ih-nav ih-sidebar-glass__nav">
      <?php foreach ( $items as $item ) :
          $is_active = ih_site_nav_item_active( $item, $current_page );
          $badge     = isset( $item['badge'] ) ? (int) $item['badge'] : 0;
          $is_messages = ( $item['key'] ?? '' ) === 'messages';
          $is_queue    = ( $item['key'] ?? '' ) === 'queue';
          ?>
      <a href="<?php echo esc_url( $item['url'] ); ?>"
         class="ih-nav-item<?php echo $is_active ? ' active' : ''; ?>"
         data-tip="<?php echo esc_attr( $item['label'] ); ?>"
         <?php if ( $is_messages ) : ?>id="ihSidebarMsgLink" onclick="ihClearSidebarBadge()"<?php endif; ?>>
        <?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <span><?php echo esc_html( $item['label'] ); ?></span>
        <?php if ( $is_queue && $ih_nav_pending > 0 ) : ?>
          <span class="ih-nav-req-badge"><?php echo $ih_nav_pending > 99 ? '99+' : (int) $ih_nav_pending; ?></span>
        <?php elseif ( $is_messages ) : ?>
          <span id="ihSidebarBadge" style="display:none;"></span>
        <?php elseif ( $badge > 0 ) : ?>
          <span class="ih-nav-badge <?php echo esc_attr( $item['badge_class'] ?? '' ); ?>"><?php echo $badge > 99 ? '99+' : (int) $badge; ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>

      <div class="ih-sidebar-label ih-sidebar-glass__section"><?php esc_html_e( 'Website', 'insight-hub-dashboard' ); ?></div>
      <?php
      $site_links = array(
          array( __( 'Homepage', 'insight-hub-dashboard' ), home_url( '/' ), '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>' ),
          array( __( 'Machine Marketplace', 'insight-hub-dashboard' ), home_url( '/machines/' ), '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="6" width="18" height="12" rx="2"/></svg>' ),
          array( __( 'Tool Marketplace', 'insight-hub-dashboard' ), home_url( '/tools/' ), '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M14.7 6.3a4 4 0 0 0-5.66 5.66l-5.3 5.3a2 2 0 0 0 2.83 2.83l5.3-5.3a4 4 0 0 0 5.66-5.66l-2.12 2.12-2.83-2.83 2.12-2.12Z"/></svg>' ),
      );
      foreach ( $site_links as $sl ) :
          ?>
      <a href="<?php echo esc_url( $sl[1] ); ?>" target="_blank" rel="noopener" class="ih-nav-item ih-site-link" data-tip="<?php echo esc_attr( $sl[0] ); ?>">
        <?php echo $sl[2]; // phpcs:ignore ?>
        <span><?php echo esc_html( $sl[0] ); ?></span>
        <svg class="ih-ext-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      </a>
      <?php endforeach; ?>
    </nav>

    <footer class="ih-admin-sb-user ih-sidebar-glass__user">
      <img class="ih-admin-sb-avatar-img" src="<?php echo esc_url( $avatar_url ); ?>" alt="" width="38" height="38" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
      <div class="ih-admin-sb-avatar ih-admin-sb-avatar--fallback"><?php echo esc_html( $initials ); ?></div>
      <div class="ih-admin-sb-user-meta">
        <div class="ih-admin-sb-user-name"><?php echo esc_html( $user->display_name ); ?></div>
        <div class="ih-admin-sb-user-role"><?php echo esc_html( $user->user_email ); ?></div>
      </div>
    </footer>
  </div>
</aside>
