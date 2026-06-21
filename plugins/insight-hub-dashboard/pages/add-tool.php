<?php defined( 'ABSPATH' ) || exit;
$saved = isset( $_GET['saved'] );
ob_start();
?>

<?php if ( $saved ) : ?>
<div id="successModal" class="ih-modal-overlay" style="display:flex">
  <div class="ih-modal" style="text-align:center;padding:40px 32px;max-width:420px">
    <div style="width:120px;height:120px;background:#f0f7f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
      <svg viewBox="0 0 80 80" fill="none" width="80" height="80">
        <rect x="10" y="20" width="52" height="48" rx="4" fill="#2d4a3e" opacity=".9"/>
        <path d="M30 44l6 6 14-14" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="56" cy="56" r="14" fill="#22c55e"/>
        <path d="M50 56l4 4 8-8" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <h2 style="font-size:20px;font-weight:700;margin:0 0 24px">Tool Added Successfully</h2>
    <a href="<?php echo admin_url( 'admin.php?page=ih-tools' ); ?>" class="ih-btn ih-btn-primary" style="padding:12px 32px;font-size:14px">
      ✓ Done
    </a>
  </div>
</div>
<?php endif; ?>

<div class="ih-rd ih-add-tool-page">
<?php
$ih_at_mode = 'admin';
$ih_at_user = wp_get_current_user();
$ih_at_dashboard = admin_url( 'admin.php?page=ih-dashboard' );
include IH_DIR . 'pages/partials/ih-add-tool-form.php';
?>
</div>

<?php
$content = ob_get_clean();
$title   = 'Add Tool';
include IH_DIR . 'pages/layout.php';
