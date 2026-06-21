<?php
/**
 * class-ih-material-price-routes.php — REST routes for the price-check system.
 *
 * All under the existing `ih/v1` namespace. Admin routes are capability-gated
 * (ih_manage_pricing) and rely on the standard WP REST nonce (X-WP-Nonce) for
 * CSRF protection. "Check now" is additionally rate-limited via a short transient
 * lock so it cannot be hammered. API keys are NEVER returned.
 *
 *   POST /material-prices/check-now        run the check immediately (rate-limited)
 *   GET  /material-prices/check-status     last run summary + source registry
 *   GET  /material-prices/reference-prices recent stored reference prices
 *   GET  /material-prices/history          price change history
 *   POST /material-prices/import-csv       supplier CSV import
 *   POST /material-prices/manual-override  alias of the existing override endpoint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Material_Price_Routes {

	const NS = 'ih/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register' ) );
	}

	public static function can_manage() {
		return current_user_can( 'ih_manage_pricing' );
	}

	public static function register() {
		register_rest_route( self::NS, '/material-prices/check-now', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'check_now' ),
			'permission_callback' => array( __CLASS__, 'can_manage' ),
		) );

		register_rest_route( self::NS, '/material-prices/check-status', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'check_status' ),
			'permission_callback' => array( __CLASS__, 'can_manage' ),
		) );

		register_rest_route( self::NS, '/material-prices/reference-prices', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'reference_prices' ),
			'permission_callback' => array( __CLASS__, 'can_manage' ),
		) );

		register_rest_route( self::NS, '/material-prices/history', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'history' ),
			'permission_callback' => array( __CLASS__, 'can_manage' ),
		) );

		register_rest_route( self::NS, '/material-prices/import-csv', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'import_csv' ),
			'permission_callback' => array( __CLASS__, 'can_manage' ),
		) );

		// Alias of the existing /material-price/override for the new path scheme.
		register_rest_route( self::NS, '/material-prices/manual-override', array(
			'methods'             => 'POST',
			'callback'            => array( 'IH_Material_Pricing', 'set_override' ),
			'permission_callback' => array( __CLASS__, 'can_manage' ),
		) );
	}

	/* ============================================================ */

	public static function check_now( WP_REST_Request $req ) {
		// Rate-limit: one manual run per 60s.
		if ( get_transient( IH_Material_Price_Config::CHECK_NOW_LOCK ) ) {
			return new WP_REST_Response( array(
				'ok'      => false,
				'message' => 'A check was run very recently. Please wait a minute before trying again.',
			), 429 );
		}
		set_transient( IH_Material_Price_Config::CHECK_NOW_LOCK, 1, MINUTE_IN_SECONDS );

		$summary = IH_Material_Price_Check::run( 'manual' );
		return new WP_REST_Response( array( 'ok' => ! empty( $summary['ok'] ), 'summary' => $summary ), 200 );
	}

	public static function check_status( WP_REST_Request $req ) {
		return new WP_REST_Response( array(
			'config'        => IH_Material_Price_Config::public_snapshot(), // NO api keys
			'last_run'      => IH_Material_Price_Check::last_run(),
			'sources'       => IH_Material_Price_Check::sources(),
			'recent_checks' => IH_Material_Price_Check::recent_checks( 20 ),
			'next_scheduled'=> self::next_scheduled_iso(),
		), 200 );
	}

	public static function reference_prices( WP_REST_Request $req ) {
		$limit = (int) $req->get_param( 'limit' );
		$limit = $limit > 0 ? $limit : 50;
		return new WP_REST_Response( array(
			'references' => IH_Material_Price_Check::recent_references( $limit, false ),
			'news'       => IH_Material_Price_Check::recent_news( 15 ),
		), 200 );
	}

	public static function history( WP_REST_Request $req ) {
		$material_id = (int) $req->get_param( 'material_id' );
		$limit       = (int) $req->get_param( 'limit' );
		return new WP_REST_Response( IH_Material_Price_History::recent( $material_id, $limit ?: 200 ), 200 );
	}

	public static function import_csv( WP_REST_Request $req ) {
		$params      = $req->get_json_params();
		$csv         = isset( $params['csv'] ) ? (string) $params['csv'] : (string) $req->get_param( 'csv' );
		$as_supplier = isset( $params['as_supplier'] ) ? (bool) $params['as_supplier'] : true;
		if ( '' === trim( $csv ) ) {
			return new WP_Error( 'no_csv', 'No CSV content provided.', array( 'status' => 422 ) );
		}
		$result = IH_Material_Price_CSV::import_string( $csv, $as_supplier );
		return new WP_REST_Response( array( 'ok' => true, 'result' => $result ), 200 );
	}

	private static function next_scheduled_iso() {
		$ts = wp_next_scheduled( 'ih_material_price_check' );
		return $ts ? gmdate( 'c', $ts ) : null;
	}
}
