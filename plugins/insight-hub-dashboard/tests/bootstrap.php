<?php
/**
 * tests/bootstrap.php — minimal WordPress shim so the PURE material-price
 * classes can be unit-tested with plain `php` (no WordPress, no database, no
 * real network). External HTTP is fully mocked via $GLOBALS['ih_http_handler'].
 *
 * Run:  php tests/test-material-pricing.php
 */

error_reporting( E_ALL & ~E_DEPRECATED );

define( 'ABSPATH', __DIR__ . '/' );
if ( ! defined( 'DAY_IN_SECONDS' ) )    define( 'DAY_IN_SECONDS', 86400 );
if ( ! defined( 'HOUR_IN_SECONDS' ) )   define( 'HOUR_IN_SECONDS', 3600 );
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) define( 'MINUTE_IN_SECONDS', 60 );

// Configure a licensed feed URL so the live-feed selection branch is testable.
// (live_feed is still only "Live" when an item is verified — see assertions.)
if ( ! defined( 'IH_PRICE_FEED_URL' ) ) define( 'IH_PRICE_FEED_URL', 'https://example.test/licensed-feed.json' );

/* ---- option store ---- */
$GLOBALS['ih_test_options'] = array();
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['ih_test_options'] ) ? $GLOBALS['ih_test_options'][ $key ] : $default;
}
function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['ih_test_options'][ $key ] = $value;
	return true;
}

/* ---- sanitisation / misc WP helpers ---- */
function sanitize_text_field( $s ) { return is_string( $s ) ? trim( preg_replace( '/<[^>]*>/', '', $s ) ) : $s; }
function esc_url_raw( $u ) { return $u; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function apply_filters( $tag, $value ) { return $value; }
function current_time( $type = 'mysql', $gmt = 0 ) { return gmdate( 'Y-m-d H:i:s' ); }
function get_current_user_id() { return 0; }

/* ---- mockable HTTP layer ---- */
class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
/**
 * $GLOBALS['ih_http_handler'] is a callable(string $url, array $args) that
 * returns either a WP_Error or ['code'=>int,'body'=>string].
 */
function wp_remote_get( $url, $args = array() ) {
	$handler = isset( $GLOBALS['ih_http_handler'] ) ? $GLOBALS['ih_http_handler'] : null;
	if ( is_callable( $handler ) ) {
		return call_user_func( $handler, $url, $args );
	}
	return new WP_Error( 'no_handler', 'No mock HTTP handler set' );
}
function wp_remote_retrieve_response_code( $res ) { return is_array( $res ) ? (int) ( $res['code'] ?? 0 ) : 0; }
function wp_remote_retrieve_body( $res ) { return is_array( $res ) ? (string) ( $res['body'] ?? '' ) : ''; }

/* ---- load the classes under test (pure + providers, no DB) ---- */
$base = dirname( __DIR__ ) . '/includes/material-prices';
require_once $base . '/class-ih-material-price-config.php';
require_once $base . '/class-ih-material-price-utils.php';
require_once $base . '/class-ih-material-price-result.php';
require_once $base . '/interface-ih-material-price-provider.php';
require_once $base . '/services/class-ih-material-price-normalisation.php';
require_once $base . '/services/class-ih-material-price-selection.php';
require_once $base . '/providers/class-ih-licensed-live-feed-provider.php';
require_once $base . '/providers/class-ih-trading-economics-provider.php';
require_once $base . '/providers/class-ih-fred-provider.php';
