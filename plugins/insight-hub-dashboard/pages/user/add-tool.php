<?php defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( admin_url( 'admin.php?page=ih-user-add-tool' ) ) );
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
$draft = isset( $_GET['draft'] ) && $_GET['draft'] == '1';
$error = isset( $_GET['ih_error'] ) ? sanitize_text_field( urldecode( $_GET['ih_error'] ) ) : '';

if ( $saved && function_exists( 'ih_add_notification' ) ) {
	$latest_tool = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, title FROM {$wpdb->prefix}ih_tools WHERE owner_id = %d ORDER BY id DESC LIMIT 1",
		$user_id
	), ARRAY_A );
	if ( $latest_tool ) {
		$owner_name = $user->display_name ?: 'A user';
		ih_add_notification(
			'tool',
			'🔧 New Tool Listed',
			$owner_name . ' added: ' . ( $latest_tool['title'] ?: 'Tool Listing' ),
			admin_url( 'admin.php?page=ih-tool-detail&tool_id=' . (int) $latest_tool['id'] )
		);
	}
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add Tool — Injection Moulding</title>
<?php wp_head(); ?>
</head>
<body>
<?php
$ih_shell_class = 'ih-shell ih-figma-dashboard is-user ih-shell--float-nav ih-rd ih-user ih-add-tool-page';
$ih_shell_extra = 'data-ih-figma-screen="user-add-tool-v20260614"';
include IH_DIR . 'pages/partials/ih-user-shell-start.php';
include IH_DIR . 'pages/partials/ih-user-shell-header.php';
?>

<div class="ih-body">
	<main class="ih-main">
		<div class="ih-content">

		<?php if ( $saved ) : ?>
			<div class="ih-at-success">
				<div style="width:72px;height:72px;background:var(--ih-figma-accent-soft,#ece9ff);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
					<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--ih-figma-brand,#5347ce)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
				</div>
				<h2 style="font-size:22px;font-weight:700;margin:0 0 8px;">Tool submitted!</h2>
				<p style="color:var(--ih-figma-text-muted);margin:0 0 24px;font-size:14px;">Admin will review and approve your listing shortly.</p>
				<div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-add-tool' ) ); ?>" class="ih-at-btn ih-at-btn--primary">+ Add another tool</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-dashboard' ) ); ?>" class="ih-at-btn ih-at-btn--ghost">My listings</a>
				</div>
			</div>
		<?php else : ?>

			<?php if ( $error ) : ?>
				<div class="ih-at-error"><?php echo esc_html( $error ); ?></div>
			<?php endif; ?>

			<?php if ( $draft ) : ?>
				<div class="ih-at-draft-banner" role="status">Draft saved. It stays private until you publish it for approval.</div>
			<?php endif; ?>

			<?php
			$ih_at_mode = 'user';
			$ih_at_user = $user;
			include IH_DIR . 'pages/partials/ih-add-tool-form.php';
			?>

		<?php endif; ?>

		</div>
	</main>
</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
