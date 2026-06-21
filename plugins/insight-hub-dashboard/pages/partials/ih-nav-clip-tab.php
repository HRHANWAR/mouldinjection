<?php defined( 'ABSPATH' ) || exit; ?>
<button
  type="button"
  class="ih-smart-menu-tab"
  id="ihNavClipTab"
  aria-label="<?php esc_attr_e( 'Open navigation menu', 'insight-hub-dashboard' ); ?>"
  aria-expanded="false"
  aria-controls="ihSidebar"
>
  <span class="ih-smart-menu-tab__glow" aria-hidden="true"></span>
  <span class="ih-smart-menu-tab__panel" aria-hidden="true">
    <svg class="ih-smart-menu-tab__icon ih-smart-menu-tab__icon--open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20">
      <rect x="3" y="3" width="7" height="7" rx="1.5"/>
      <rect x="14" y="3" width="7" height="7" rx="1.5"/>
      <rect x="3" y="14" width="7" height="7" rx="1.5"/>
      <rect x="14" y="14" width="7" height="7" rx="1.5"/>
    </svg>
    <svg class="ih-smart-menu-tab__icon ih-smart-menu-tab__icon--close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="18" height="18">
      <path d="M18 6 6 18M6 6l12 12"/>
    </svg>
  </span>
  <span class="ih-smart-menu-tab__label"><?php esc_html_e( 'Menu', 'insight-hub-dashboard' ); ?></span>
</button>
