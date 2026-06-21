<?php defined( 'ABSPATH' ) || exit;

$ih_shell_class = isset( $ih_shell_class ) ? (string) $ih_shell_class : 'ih-shell ih-figma-dashboard is-user ih-shell--float-nav';
$ih_shell_extra = isset( $ih_shell_extra ) ? (string) $ih_shell_extra : '';
?>
<div class="<?php echo esc_attr( $ih_shell_class ); ?>"<?php echo $ih_shell_extra ? ' ' . $ih_shell_extra : ''; ?>>
<?php ih_render_site_nav_header( array( 'mode' => 'user' ) ); ?>
<div class="ih-overlay" id="ihOverlay" aria-hidden="true"></div>
<?php ih_render_user_float_nav(); ?>
<style id="ih-user-shell-mobile-guard">
/* Firefox: flex-basis:0 collapses scroll region inside fixed shell — force auto + min-height */
.ih-shell.ih-figma-dashboard.is-user {
  display: flex !important;
  flex-direction: column !important;
  min-height: 100vh !important;
  height: 100vh !important;
}
.ih-shell.ih-figma-dashboard.is-user .ih-body,
.ih-shell.ih-figma-dashboard.is-user .ih-main,
.ih-shell.ih-figma-dashboard.is-user .ih-content {
  flex: 1 1 auto !important;
  min-height: 1px !important;
}
@media (max-width: 768px) {
  body.ih-user-portal,
  body.ih-user-portal #wpbody,
  body.ih-user-portal #wpcontent,
  body.ih-user-portal #wpbody-content {
    background: var(--ih-figma-canvas) !important;
  }
  .ih-shell.ih-figma-dashboard.is-user {
    background: var(--ih-figma-canvas) !important;
    background-image: none !important;
    background-size: auto !important;
  }
  .ih-shell .ih-header--desktop {
    display: none !important;
  }
}
</style>
