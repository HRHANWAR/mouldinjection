<?php defined( 'ABSPATH' ) || exit;

$nav_mode     = isset( $args['mode'] ) ? sanitize_key( $args['mode'] ) : ih_site_nav_mode();
$items        = ih_site_nav_bottom_items( $nav_mode );
$current_page = ih_site_nav_current_page();
$now_ts       = (int) current_time( 'timestamp' );
$date_label   = date_i18n( 'D, j M', $now_ts );
$time_label   = date_i18n( 'h:i A', $now_ts );
$has_fab      = false;
foreach ( $items as $item ) {
    if ( ! empty( $item['fab'] ) ) {
        $has_fab = true;
        break;
    }
}
?>
<nav class="ih-site-bottom-nav is-<?php echo esc_attr( $nav_mode ); ?><?php echo $has_fab ? ' has-fab' : ''; ?>" aria-label="<?php esc_attr_e( 'Primary navigation', 'insight-hub-dashboard' ); ?>" data-ih-nav-mode="<?php echo esc_attr( $nav_mode ); ?>">
  <div class="ih-site-bottom-nav-inner">
    <?php if ( $nav_mode === 'user' ) : ?>
      <div class="ih-site-nav-datetime-pill" aria-live="polite">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <circle cx="12" cy="12" r="9"></circle>
          <path d="M12 7v5l3 2"></path>
        </svg>
        <span class="ih-site-nav-datetime-copy">
          <time class="ih-site-nav-datetime-date" id="ihSiteBottomDate" datetime="<?php echo esc_attr( date_i18n( 'Y-m-d', $now_ts ) ); ?>"><?php echo esc_html( $date_label ); ?></time>
          <time class="ih-site-nav-datetime-time" id="ihSiteBottomTime" datetime="<?php echo esc_attr( date_i18n( 'H:i', $now_ts ) ); ?>"><?php echo esc_html( $time_label ); ?></time>
        </span>
      </div>
    <?php endif; ?>
    <?php foreach ( $items as $item ) :
        $is_fab   = ! empty( $item['fab'] );
        $is_active = ih_site_nav_item_active( $item, $current_page );
        $badge    = isset( $item['badge'] ) ? (int) $item['badge'] : 0;
        if ( $is_fab ) : ?>
      <div class="ih-site-nav-fab-wrap">
        <button type="button" class="ih-site-nav-fab" id="ihSiteNavFab" aria-label="<?php esc_attr_e( 'Add listing', 'insight-hub-dashboard' ); ?>" aria-expanded="false" aria-haspopup="true">
          <?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </button>
        <?php if ( ! empty( $item['actions'] ) ) : ?>
          <div class="ih-site-nav-fab-menu hidden" id="ihSiteNavFabMenu" role="menu">
            <?php foreach ( $item['actions'] as $action ) : ?>
              <a href="<?php echo esc_url( $action['url'] ); ?>" class="ih-site-nav-fab-action" role="menuitem">
                <span class="ih-site-nav-fab-action-icon"><?php echo $action['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <span><?php echo esc_html( $action['label'] ); ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
        <?php else : ?>
      <a href="<?php echo esc_url( $item['url'] ); ?>"
         class="ih-site-nav-item<?php echo $is_active ? ' is-active' : ''; ?>"
         data-nav-key="<?php echo esc_attr( $item['key'] ?? '' ); ?>"
         <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
        <?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <span><?php echo esc_html( $item['label'] ); ?></span>
        <?php if ( $badge > 0 ) : ?>
          <span class="ih-nav-badge"><?php echo $badge > 99 ? '99+' : (int) $badge; ?></span>
        <?php endif; ?>
      </a>
        <?php endif; ?>
    <?php endforeach; ?>
  </div>
</nav>
