<?php defined( 'ABSPATH' ) || exit;

$nav_mode     = isset( $nav_mode ) ? sanitize_key( $nav_mode ) : 'user';
$user         = isset( $user ) && $user instanceof WP_User ? $user : wp_get_current_user();
$avatar_url   = isset( $avatar_url ) ? (string) $avatar_url : '';
$initials     = isset( $initials ) ? (string) $initials : 'U';
$profile_page = isset( $profile_page ) ? sanitize_key( $profile_page ) : ( $nav_mode === 'admin' ? 'ih-dashboard' : 'ih-user-edit-profile' );
$logout_url   = wp_logout_url( home_url() );
$dash_url     = admin_url( 'admin.php' );
$home_url     = home_url( '/' );
?>
<div class="ih-site-nav-account" id="ihSiteNavAccount">
	<button
		type="button"
		class="ih-site-nav-avatar ih-site-nav-account-trigger"
		id="ihSiteNavAccountBtn"
		aria-label="<?php esc_attr_e( 'Account menu', 'insight-hub-dashboard' ); ?>"
		aria-expanded="false"
		aria-haspopup="true"
		aria-controls="ihSiteNavAccountMenu"
	>
		<img class="ih-site-nav-avatar-img" src="<?php echo esc_url( $avatar_url ); ?>" alt="" width="38" height="38" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
		<span class="ih-site-nav-avatar-fallback"><?php echo esc_html( $initials ); ?></span>
	</button>
	<div class="ih-site-nav-account-menu hidden" id="ihSiteNavAccountMenu" role="menu" aria-label="<?php esc_attr_e( 'Account', 'insight-hub-dashboard' ); ?>">
		<div class="ih-site-nav-account-user">
			<span class="ih-site-nav-account-avatar" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
			<div class="ih-site-nav-account-meta">
				<div class="ih-site-nav-account-name"><?php echo esc_html( $user->display_name ?: $user->user_login ); ?></div>
				<div class="ih-site-nav-account-email"><?php echo esc_html( $user->user_email ); ?></div>
			</div>
		</div>
		<a class="ih-site-nav-account-link" role="menuitem" href="<?php echo esc_url( $home_url ); ?>"><?php esc_html_e( 'Site Home', 'insight-hub-dashboard' ); ?></a>
		<?php if ( $nav_mode === 'user' ) : ?>
			<a class="ih-site-nav-account-link" role="menuitem" href="<?php echo esc_url( $dash_url . '?page=ih-user-dashboard' ); ?>"><?php esc_html_e( 'Dashboard', 'insight-hub-dashboard' ); ?></a>
			<a class="ih-site-nav-account-link" role="menuitem" href="<?php echo esc_url( $dash_url . '?page=ih-user-edit-profile' ); ?>"><?php esc_html_e( 'Edit Profile', 'insight-hub-dashboard' ); ?></a>
		<?php else : ?>
			<a class="ih-site-nav-account-link" role="menuitem" href="<?php echo esc_url( $dash_url . '?page=' . $profile_page ); ?>"><?php esc_html_e( 'Dashboard', 'insight-hub-dashboard' ); ?></a>
		<?php endif; ?>
		<div class="ih-site-nav-account-divider" role="separator"></div>
		<a class="ih-site-nav-account-link is-danger" role="menuitem" href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Logout', 'insight-hub-dashboard' ); ?></a>
	</div>
</div>
