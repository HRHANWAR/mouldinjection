<?php
/**
 * Breeze / full-page-cache compatibility for Insight Hub.
 *
 * Programmatic exclusions complement Breeze UI settings (see audit report).
 */
defined( 'ABSPATH' ) || exit;

/**
 * Cache-bust version: IH_VERSION plus filemtime when the asset exists.
 *
 * @param string $relative_path Path relative to plugin root, e.g. 'js/main.js'.
 */
function ih_asset_version( $relative_path ) {
	$path = IH_DIR . ltrim( $relative_path, '/' );
	if ( is_readable( $path ) ) {
		$mtime = filemtime( $path );
		if ( $mtime ) {
			return IH_VERSION . '.' . $mtime;
		}
	}
	return IH_VERSION;
}

/* ── Never cache these URL fragments ── */
add_filter( 'breeze_exclude_urls', function ( $urls ) {
	if ( ! is_array( $urls ) ) {
		$urls = array();
	}
	$exclude = array(
		'admin-ajax.php',
		'wp-admin',
		'wp-content/plugins/insight-hub-dashboard',
		'ih_token',
		'ih_complete_login',
		'register',
	);
	return array_values( array_unique( array_merge( $urls, $exclude ) ) );
} );

/* ── JS: nonce/order-sensitive handles — do not combine/minify ── */
add_filter( 'breeze_minify_exclude_js', function ( $handles ) {
	if ( ! is_array( $handles ) ) {
		$handles = array();
	}
	$ih_handles = array(
		'ih-main',
		'ih-site-nav',
		'ih-user-shell',
		'ih-user-float-nav',
		'ih-corp-dashboard',
		'ih-dashboard-figma',
		'ih-wp-admin-bar-widget',
		'ih-requests-redesign',
		'ih-requests-menu',
		'ih-messages-console',
		'ih-redesign',
		'ih-users-redesign',
		'ih-resizable-ui',
		'ih-dynamic-menu',
		'ih-add-tool',
		'ih-add-machine',
		'im-main-js',
		'jquery',
		'jquery-core',
		'jquery-migrate',
	);
	return array_values( array_unique( array_merge( $handles, $ih_handles ) ) );
} );

/* ── CSS: :has() heavy sheets — exclude from minify ── */
add_filter( 'breeze_minify_exclude_css', function ( $handles ) {
	if ( ! is_array( $handles ) ) {
		$handles = array();
	}
	$ih_css = array(
		'ih-add-machine',
		'ih-add-machine-mobile',
		'ih-add-tool',
		'ih-add-tool-mobile',
		'ih-machines-card',
		'ih-users-redesign',
		'ih-requests-redesign',
		'ih-site-nav',
		'ih-redesign',
		'ih-user-shell',
		'ih-user-float-nav',
		'ih-corp-dashboard',
		'ih-corp-dashboard-mobile',
		'ih-dashboard-figma',
		'ih-admin-sidebar-figma',
		'ih-admin-mobile',
	);
	return array_values( array_unique( array_merge( $handles, $ih_css ) ) );
} );

/* Logged-in visitors must not receive anonymous full-page cache. */
add_action(
	'template_redirect',
	function () {
		if ( is_user_logged_in() && ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	},
	0
);

/* IH wp-admin screens — belt-and-suspenders for page-cache plugins. */
add_action(
	'init',
	function () {
		if ( ! is_admin() ) {
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( $page !== '' && strpos( $page, 'ih-' ) === 0 && ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	},
	0
);

/* Login-token completion must never be stored in a page cache. */
add_action(
	'init',
	function () {
		if ( empty( $_GET['ih_token'] ) && empty( $_GET['ih_complete_login'] ) ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	},
	0
);
