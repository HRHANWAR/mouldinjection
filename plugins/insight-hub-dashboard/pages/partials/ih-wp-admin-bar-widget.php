<?php defined( 'ABSPATH' ) || exit; ?>
<button
  type="button"
  class="ih-wp-admin-bar-tab"
  id="ihWpAdminBarToggle"
  aria-label="<?php esc_attr_e( 'Show WordPress toolbar', 'insight-hub-dashboard' ); ?>"
  aria-expanded="false"
  aria-controls="wpadminbar"
>
  <span class="ih-wp-admin-bar-tab__icon" aria-hidden="true">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
      <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 1.5c4.694 0 8.5 3.806 8.5 8.5s-3.806 8.5-8.5 8.5S3.5 16.694 3.5 12 7.306 3.5 12 3.5zM11.25 7v5.25H7v1.5h4.25V18h1.5v-5.25H17v-1.5h-4.25V7h-1.5z"/>
    </svg>
  </span>
  <span class="ih-wp-admin-bar-tab__text"><?php esc_html_e( 'WP', 'insight-hub-dashboard' ); ?></span>
</button>
