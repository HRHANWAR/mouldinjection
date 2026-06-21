<?php defined( 'ABSPATH' ) || exit; ?>
<div class="ih-wrap ih-figma-nav is-admin ih-theme-light" id="ihAdminWrap">

  <?php ih_render_site_nav_header( array( 'mode' => 'admin' ) ); ?>

  <!-- ── Overlay (dim layer — drawer sits above via z-index) ── -->
  <div class="ih-overlay" id="ihOverlay"></div>

  <?php include IH_DIR . 'pages/partials/ih-nav-clip-tab.php'; ?>

  <div class="ih-wrap-grid">
  <?php include IH_DIR . 'pages/partials/ih-admin-sidebar.php'; ?>
  <?php include IH_DIR . 'pages/partials/ih-admin-sidebar-scripts.php'; ?>

  <!-- ── Main ── -->
  <div class="ih-main">

    <!-- Topbar — page title only; search/bell/profile live in ih-site-nav-header -->
    <header class="ih-topbar ih-topbar--legacy">
      <div class="ih-topbar-left">
        <h1 class="ih-topbar-title"><?php echo esc_html( $title ?? 'Dashboard' ); ?></h1>
      </div>
    </header>

    <!-- Page content -->
    <div class="ih-content">
      <?php echo $content; ?>
    </div>
  </div>
  </div><!-- /.ih-wrap-grid -->
</div>