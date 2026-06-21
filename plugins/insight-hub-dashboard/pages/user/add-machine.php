<?php defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( admin_url( 'admin.php?page=ih-user-add-machine' ) ) );
	exit;
}
if ( current_user_can( 'administrator' ) ) {
	wp_redirect( admin_url( 'admin.php?page=ih-dashboard' ) );
	exit;
}

global $wpdb;
$user_id = get_current_user_id();
$user    = wp_get_current_user();

$saved = isset( $_GET['saved'] ) && $_GET['saved'] == '1';
$error = isset( $_GET['ih_error'] ) ? sanitize_text_field( urldecode( $_GET['ih_error'] ) ) : '';

if ( $saved && function_exists( 'ih_add_notification' ) ) {
	$latest_machine = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, title FROM {$wpdb->prefix}ih_machines WHERE owner_id = %d ORDER BY id DESC LIMIT 1",
		$user_id
	), ARRAY_A );
	if ( $latest_machine ) {
		$owner_name = $user->display_name ?: 'A user';
		ih_add_notification(
			'machine',
			'⚙️ New Machine Listed',
			$owner_name . ' added: ' . ( $latest_machine['title'] ?: 'Machine Listing' ),
			admin_url( 'admin.php?page=ih-machine-detail&machine_id=' . (int) $latest_machine['id'] )
		);
	}
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add Machine — Injection Moulding</title>
<?php wp_head(); ?>
</head>
<body>
<?php
$ih_shell_class = 'ih-shell ih-figma-dashboard is-user ih-shell--float-nav ih-rd ih-user ih-add-machine-page';
$ih_shell_extra = 'data-ih-figma-screen="user-add-machine-v20260614"';
include IH_DIR . 'pages/partials/ih-user-shell-start.php';
include IH_DIR . 'pages/partials/ih-user-shell-header.php';
?>

<div class="ih-body">
	<main class="ih-main">
		<div class="ih-content">

		<?php if ( $saved ) : ?>
			<div class="ih-am-success">
				<div style="width:72px;height:72px;background:var(--ih-figma-accent-soft,#ece9ff);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
					<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--ih-figma-brand,#5347ce)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
				</div>
				<h2 style="font-size:22px;font-weight:700;margin:0 0 8px;">Machine submitted!</h2>
				<p style="color:var(--ih-figma-text-muted);margin:0 0 24px;font-size:14px;">Admin will review and approve your listing shortly.</p>
				<div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-add-machine' ) ); ?>" class="ih-am-btn ih-am-btn--primary">+ Add another machine</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-dashboard' ) ); ?>" class="ih-am-btn ih-am-btn--ghost">My listings</a>
				</div>
			</div>
		<?php else : ?>

			<?php if ( $error ) : ?>
				<div class="ih-am-error"><?php echo esc_html( $error ); ?></div>
			<?php endif; ?>

			<?php
			$ih_am_mode = 'user';
			$ih_am_user = $user;
			include IH_DIR . 'pages/partials/ih-add-machine-form.php';
			?>

		<?php endif; ?>

		</div>
	</main>
</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
