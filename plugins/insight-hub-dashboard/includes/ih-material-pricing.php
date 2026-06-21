<?php
/**
 * ih-material-pricing.php — live resin price backend for the cost calculator
 * Part of the insight-hub-dashboard plugin. Drop in /includes and require it
 * from the main plugin file.
 *
 * Architecture:  live market feed (CSV/API/ERP)  ──cron──▶  ih_materials table
 *                ih_materials  ──REST /ih/v1/material-price──▶  calculator
 *
 * The frontend NEVER touches a pricing website. It only reads our DB via REST,
 * which gives caching, audit history, manual overrides and outage protection.
 *
 * Security model:
 *   • anonymous / buyer  → gets ONLY price_used_per_kg + source + updated_at
 *                          (never the supplier cost or margin — that's our P&L)
 *   • staff (ih_manage_pricing) → gets the full breakdown and may override
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IH_Material_Pricing {

	const NS         = 'ih/v1';
	const CACHE_TTL  = 900;                 // 15 min REST response cache (transient)
	const CRON_HOOK  = 'ih_import_resin_prices';

	public static function init() {
		// IH_PLUGIN_FILE is defined in the main plugin file. Guard so a missing
		// constant can never fatal — table creation is also wired into the
		// plugin's IH_DB_VERSION migration path (the plugin is already active,
		// so register_activation_hook would otherwise never fire here).
		if ( defined( 'IH_PLUGIN_FILE' ) ) {
			register_activation_hook( IH_PLUGIN_FILE, [ __CLASS__, 'install' ] );
		}
		add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
		// The licensed-feed import is still callable on this hook, but scheduling
		// is now owned by the unified 24-hour job (IH_Material_Price_Check::run()
		// via includes/material-prices/bootstrap.php), which runs the licensed
		// feed as one provider. We therefore no longer self-schedule a separate
		// daily event here (the bootstrap retires any legacy ih_import_resin_prices
		// event to keep a single coherent daily run).
		add_action( self::CRON_HOOK, [ __CLASS__, 'import_market_prices' ] );
	}

	/* ============================================================
	   1. SCHEMA
	   ============================================================ */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$m  = $wpdb->prefix . 'ih_materials';
		$h  = $wpdb->prefix . 'ih_material_price_history';

		dbDelta( "CREATE TABLE $m (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			material_family       VARCHAR(60)  NOT NULL DEFAULT '',
			material_name         VARCHAR(120) NOT NULL DEFAULT '',
			grade                 VARCHAR(120) NOT NULL DEFAULT '',
			grade_code            VARCHAR(60)  NOT NULL DEFAULT '',
			manufacturer          VARCHAR(120) NOT NULL DEFAULT '',
			supplier              VARCHAR(120) NOT NULL DEFAULT '',
			region                VARCHAR(40)  NOT NULL DEFAULT '',
			currency              CHAR(3)      NOT NULL DEFAULT 'GBP',
			unit                  VARCHAR(12)  NOT NULL DEFAULT 'kg',
			market_price_per_kg   DECIMAL(10,4) NULL,
			supplier_price_per_kg DECIMAL(10,4) NULL,
			quote_price_per_kg    DECIMAL(10,4) NULL,
			delivery_cost_per_kg  DECIMAL(10,4) NOT NULL DEFAULT 0,
			handling_cost_per_kg  DECIMAL(10,4) NOT NULL DEFAULT 0,
			masterbatch_cost_per_kg DECIMAL(10,4) NOT NULL DEFAULT 0,
			drying_cost_per_kg    DECIMAL(10,4) NOT NULL DEFAULT 0,
			margin_percent        DECIMAL(6,3) NOT NULL DEFAULT 0,
			price_source          VARCHAR(120) NOT NULL DEFAULT '',
			source_reference      VARCHAR(255) NOT NULL DEFAULT '',
			last_updated          DATETIME NULL,
			is_live_price         TINYINT(1) NOT NULL DEFAULT 0,
			is_manual_override    TINYINT(1) NOT NULL DEFAULT 0,
			manual_override_price DECIMAL(10,4) NULL,
			override_reason       VARCHAR(255) NOT NULL DEFAULT '',
			active                TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			KEY lookup (grade_code, region, supplier, active)
		) $charset;" );

		dbDelta( "CREATE TABLE $h (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			material_id     BIGINT UNSIGNED NOT NULL,
			field           VARCHAR(40) NOT NULL DEFAULT 'quote_price_per_kg',
			old_price_per_kg DECIMAL(10,4) NULL,
			new_price_per_kg DECIMAL(10,4) NULL,
			currency        CHAR(3) NOT NULL DEFAULT 'GBP',
			source          VARCHAR(120) NOT NULL DEFAULT '',
			changed_by      BIGINT UNSIGNED NULL,
			changed_at      DATETIME NOT NULL,
			reason          VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			KEY material_id (material_id)
		) $charset;" );

		self::seed();   // baseline rows so v1 works before any live feed exists
	}

	/* ============================================================
	   2. ROUTES
	   ============================================================ */
	public static function routes() {
		register_rest_route( self::NS, '/material-price', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'get_price' ],
			'permission_callback' => '__return_true',          // public read, but redacted (see below)
			'args'                => [
				'material' => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
				'grade'    => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
				'region'   => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
				'supplier' => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
			],
		] );

		register_rest_route( self::NS, '/material-price/override', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'set_override' ],
			'permission_callback' => function () { return current_user_can( 'ih_manage_pricing' ); },
		] );

		register_rest_route( self::NS, '/material-price/history', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'get_history' ],
			'permission_callback' => function () { return current_user_can( 'ih_manage_pricing' ); },
		] );

		register_rest_route( self::NS, '/materials', [          // for the selectors
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'list_materials' ],
			'permission_callback' => '__return_true',
		] );
	}

	/* ============================================================
	   3. GET PRICE  (the endpoint the calculator calls)
	   ============================================================ */
	public static function get_price( WP_REST_Request $req ) {
		$material = $req->get_param( 'material' );
		$grade    = $req->get_param( 'grade' );
		$region   = $req->get_param( 'region' );
		$supplier = $req->get_param( 'supplier' );

		$cache_key = 'ih_price_' . md5( "$material|$grade|$region|$supplier" );
		$cached    = get_transient( $cache_key );
		$staff     = current_user_can( 'ih_manage_pricing' );

		if ( false === $cached ) {
			$row = self::find_material( $material, $grade, $region, $supplier );
			if ( ! $row ) {
				// Fallback: never 500 the calculator. Return a flagged default if we have one.
				$row = self::fallback_row( $material );
				if ( ! $row ) {
					return new WP_REST_Response( [ 'error' => 'no_price', 'message' => 'No price on record — enter manually.' ], 404 );
				}
			}
			$quote      = self::compute_quote( $row );
			$price_used = ( $row->is_manual_override && $row->manual_override_price > 0 )
				? (float) $row->manual_override_price : $quote;

			// 24h-reference-system enrichment: run the documented selection logic
			// to derive the source_type, badge, calculation type and any warning.
			// "Live" is only ever emitted for a verified licensed feed. Public
			// references are used as a quote price ONLY when the admin option is on.
			$selection = null;
			if ( class_exists( 'IH_Material_Price_Selection' ) ) {
				$selection = IH_Material_Price_Selection::select(
					IH_Material_Price_Selection::context_from_row( $row )
				);
			}

			$cached = [
				'material'           => $row->material_name,
				'grade'              => $row->grade,
				'region'             => $row->region,
				'supplier'           => $row->supplier,
				'currency'           => 'GBP',
				'quote_price_per_kg' => round( $quote, 4 ),
				'price_used_per_kg'  => round( $price_used, 4 ),
				'source'             => $row->price_source ?: 'Price list',
				'updated_at'         => self::iso( $row->last_updated ),
				'is_manual_override' => (bool) $row->is_manual_override,
				// reference-system fields (safe for all viewers — no costs/margins)
				'source_type'        => $selection ? $selection['source_type'] : ( ( $row->is_manual_override && $row->manual_override_price > 0 ) ? 'manual_override' : 'manual_required' ),
				'source_name'        => $selection ? ( $selection['source_name'] ?: ( $row->price_source ?: 'Price list' ) ) : ( $row->price_source ?: 'Price list' ),
				'badge'              => $selection ? $selection['badge'] : null,
				'calculation_type'   => $selection ? $selection['calculation_type'] : null,
				'warning'            => $selection ? $selection['warning'] : '',
				'is_verified_live'   => $selection ? (bool) $selection['is_verified_live'] : false,
				'last_checked'       => isset( $row->last_checked_at ) ? self::iso( $row->last_checked_at ) : null,
				// staff-only sensitive fields, merged below
				'_staff'             => [
					'market_price_per_kg'   => self::f( $row->market_price_per_kg ),
					'supplier_price_per_kg' => self::f( $row->supplier_price_per_kg ),
					'delivery_cost_per_kg'  => self::f( $row->delivery_cost_per_kg ),
					'handling_cost_per_kg'  => self::f( $row->handling_cost_per_kg ),
					'masterbatch_cost_per_kg'=> self::f( $row->masterbatch_cost_per_kg ),
					'drying_cost_per_kg'    => self::f( $row->drying_cost_per_kg ),
					'margin_percent'        => self::f( $row->margin_percent ),
					'source_reference'      => $row->source_reference,
				],
			];
			set_transient( $cache_key, $cached, self::CACHE_TTL );
		}

		$out = $cached;
		$staff_block = $out['_staff'];
		unset( $out['_staff'] );
		if ( $staff ) {                       // only authenticated staff see cost/margin
			$out = array_merge( $out, $staff_block );
		}
		return new WP_REST_Response( $out, 200 );
	}

	/* quote = (base + delivery + handling + masterbatch + drying) × (1 + margin%)   */
	/* base prefers what we actually pay (supplier) and falls back to market index.  */
	public static function compute_quote( $row ) {
		$base = ( $row->supplier_price_per_kg > 0 )
			? (float) $row->supplier_price_per_kg
			: (float) $row->market_price_per_kg;
		$base += (float) $row->delivery_cost_per_kg
			+  (float) $row->handling_cost_per_kg
			+  (float) $row->masterbatch_cost_per_kg
			+  (float) $row->drying_cost_per_kg;
		return $base * ( 1 + (float) $row->margin_percent / 100 );
	}

	/* ============================================================
	   4. MANUAL OVERRIDE  (staff only) + audit
	   ============================================================ */
	public static function set_override( WP_REST_Request $req ) {
		global $wpdb;
		$p = $req->get_json_params();
		$price  = isset( $p['override_price_per_kg'] ) ? (float) $p['override_price_per_kg'] : 0;
		$reason = isset( $p['reason'] ) ? sanitize_text_field( $p['reason'] ) : '';

		// validation
		if ( $price <= 0 || $price > 500 )      return new WP_Error( 'bad_price',  'Override price out of range.', [ 'status' => 422 ] );
		if ( mb_strlen( $reason ) < 5 )         return new WP_Error( 'no_reason',  'A reason (audit) is required.', [ 'status' => 422 ] );

		$row = self::find_material( $p['material'] ?? '', $p['grade'] ?? '', $p['region'] ?? '', $p['supplier'] ?? '' );
		if ( ! $row ) return new WP_Error( 'not_found', 'Material not found.', [ 'status' => 404 ] );

		// sanity guard: warn (but allow) large deviations from the computed quote
		$quote = self::compute_quote( $row );
		$deviation = $quote > 0 ? abs( $price - $quote ) / $quote : 0;

		$old = ( $row->is_manual_override && $row->manual_override_price > 0 ) ? $row->manual_override_price : $quote;
		$tbl = $wpdb->prefix . 'ih_materials';
		$wpdb->update( $tbl, [
			'is_manual_override'    => 1,
			'manual_override_price' => $price,
			'override_reason'       => $reason,
			'last_updated'          => current_time( 'mysql', true ),
		], [ 'id' => $row->id ] );

		self::log( $row->id, 'manual_override_price', $old, $price, 'manual', $reason );
		self::bust_cache();

		return new WP_REST_Response( [
			'ok' => true, 'price_used_per_kg' => round( $price, 4 ),
			'deviation_from_quote' => round( $deviation, 3 ),
			'warning' => $deviation > 0.5 ? 'Override is >50% from the computed quote.' : null,
		], 200 );
	}

	public static function get_history( WP_REST_Request $req ) {
		global $wpdb;
		$id  = (int) $req->get_param( 'material_id' );
		$tbl = $wpdb->prefix . 'ih_material_price_history';
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $tbl WHERE material_id = %d ORDER BY changed_at DESC LIMIT 200", $id
		) );
		return new WP_REST_Response( $rows, 200 );
	}

	private static function log( $material_id, $field, $old, $new, $source, $reason ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'ih_material_price_history', [
			'material_id'      => $material_id,
			'field'            => $field,
			'old_price_per_kg' => $old,
			'new_price_per_kg' => $new,
			'currency'         => 'GBP',
			'source'           => $source,
			'changed_by'       => get_current_user_id() ?: null,
			'changed_at'       => current_time( 'mysql', true ),
			'reason'           => $reason,
		] );
	}

	/* ============================================================
	   5. LIVE MARKET IMPORT  (WP-Cron, daily) — swap the stub for your feed
	   ============================================================
	   Legal note: ChemOrbis / Polymerupdate / The Plastics Exchange are
	   subscription services — ingest via your LICENSED CSV/API export (or a
	   supplier feed / ERP), not by scraping their pages. Keep credentials in
	   wp-config (IH_PRICE_FEED_URL / IH_PRICE_FEED_KEY), never in the repo. */
	public static function import_market_prices() {
		$rows = self::get_latest_resin_prices();    // [ ['grade_code','price','currency','unit','source','reference'], ... ]
		if ( empty( $rows ) ) return;
		global $wpdb;
		$tbl = $wpdb->prefix . 'ih_materials';
		foreach ( $rows as $r ) {
			$market = self::to_gbp_per_kg( (float) $r['price'], $r['currency'], $r['unit'] );
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $tbl WHERE grade_code = %s AND region = %s AND supplier = %s LIMIT 1",
				$r['grade_code'], $r['region'] ?? '', $r['supplier'] ?? ''
			) );
			if ( ! $existing ) continue;
			$old = $existing->market_price_per_kg;
			$wpdb->update( $tbl, [
				'market_price_per_kg' => $market,
				'is_live_price'       => 1,
				'price_source'        => $r['source'] ?? 'Market feed',
				'source_reference'    => $r['reference'] ?? '',
				'last_updated'        => current_time( 'mysql', true ),
			], [ 'id' => $existing->id ] );
			// recompute & store the quote so reads are cheap
			$existing->market_price_per_kg = $market;
			$quote = self::compute_quote( $existing );
			$wpdb->update( $tbl, [ 'quote_price_per_kg' => $quote ], [ 'id' => $existing->id ] );
			if ( (float) $old !== (float) $market ) self::log( $existing->id, 'market_price_per_kg', $old, $market, $r['source'] ?? 'feed', 'scheduled import' );
		}
		self::bust_cache();
	}

	/* STUB — replace with a licensed feed/CSV/API/ERP read. Return [] on failure
	   (the calculator keeps using the last stored prices). */
	private static function get_latest_resin_prices() {
		$url = defined( 'IH_PRICE_FEED_URL' ) ? IH_PRICE_FEED_URL : '';
		if ( ! $url ) return [];
		$res = wp_remote_get( $url, [ 'timeout' => 20, 'headers' => [ 'Authorization' => 'Bearer ' . ( defined( 'IH_PRICE_FEED_KEY' ) ? IH_PRICE_FEED_KEY : '' ) ] ] );
		if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) return [];
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		return is_array( $data ) ? $data : [];
	}

	/* currency + unit normalisation → GBP/kg */
	private static function to_gbp_per_kg( $price, $currency, $unit ) {
		$per_kg = $price;
		$unit = strtolower( (string) $unit );
		if ( 'tonne' === $unit || 't' === $unit || 'mt' === $unit ) $per_kg = $price / 1000;
		elseif ( 'lb' === $unit ) $per_kg = $price / 0.45359237;
		$rate = self::fx_to_gbp( strtoupper( (string) $currency ) );
		return $per_kg * $rate;
	}
	private static function fx_to_gbp( $cur ) {
		if ( 'GBP' === $cur || ! $cur ) return 1.0;
		$rates = get_option( 'ih_fx_rates', [ 'EUR' => 0.85, 'USD' => 0.79 ] ); // refresh via your own job
		return isset( $rates[ $cur ] ) ? (float) $rates[ $cur ] : 1.0;
	}

	/* ============================================================
	   6. lookups, fallback, seed, helpers
	   ============================================================ */
	private static function find_material( $material, $grade, $region, $supplier ) {
		global $wpdb;
		$tbl = $wpdb->prefix . 'ih_materials';
		// match on grade_code first (e.g. PP-HOMO-INJ), then loosen
		$sql = "SELECT * FROM $tbl WHERE active = 1 AND ( grade_code = %s OR material_name = %s )";
		$params = [ $material, $material ];
		if ( $region )   { $sql .= " AND region = %s";   $params[] = $region; }
		if ( $supplier ) { $sql .= " AND supplier = %s"; $params[] = $supplier; }
		$sql .= " ORDER BY is_manual_override DESC, last_updated DESC LIMIT 1";
		return $wpdb->get_row( $wpdb->prepare( $sql, $params ) );
	}

	private static function fallback_row( $material ) {
		// last-resort baseline so a quote can still be produced
		$defaults = [ 'PP-HOMO-INJ' => 1.50, 'PE-HD-INJ' => 1.45, 'ABS-INJ' => 1.80, 'PC-INJ' => 3.00, 'PA66-INJ' => 3.20, 'POM-INJ' => 2.60 ];
		if ( ! isset( $defaults[ $material ] ) ) return null;
		return (object) [
			'id' => 0, 'material_name' => $material, 'grade' => '', 'region' => '', 'supplier' => '',
			'market_price_per_kg' => $defaults[ $material ], 'supplier_price_per_kg' => 0,
			'delivery_cost_per_kg' => 0, 'handling_cost_per_kg' => 0, 'masterbatch_cost_per_kg' => 0,
			'drying_cost_per_kg' => 0, 'margin_percent' => 0,
			'price_source' => 'Fallback default', 'source_reference' => '',
			'last_updated' => null, 'is_manual_override' => 0, 'manual_override_price' => 0,
		];
	}

	public static function list_materials() {
		global $wpdb; $tbl = $wpdb->prefix . 'ih_materials';
		$rows = $wpdb->get_results( "SELECT DISTINCT material_family, material_name, grade, grade_code, region, supplier FROM $tbl WHERE active = 1 ORDER BY material_family, material_name" );
		return new WP_REST_Response( $rows, 200 );
	}

	private static function seed() {
		global $wpdb; $tbl = $wpdb->prefix . 'ih_materials';
		if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM $tbl" ) > 0 ) return;
		$now = current_time( 'mysql', true );
		$seed = [
			[ 'Polypropylene', 'PP Homopolymer Injection Grade', 'Injection moulding', 'PP-HOMO-INJ', 'UK / Europe', 'Supplier A', 1.35, 1.45, 0.04, 0.02, 0, 0.02, 5 ],
			[ 'Polyethylene', 'HDPE Injection Grade', 'Injection moulding', 'PE-HD-INJ', 'UK / Europe', 'Supplier A', 1.30, 1.40, 0.04, 0.02, 0, 0, 5 ],
			[ 'ABS', 'ABS Injection Grade', 'Injection moulding', 'ABS-INJ', 'UK / Europe', 'Supplier B', 1.70, 1.80, 0.05, 0.02, 0, 0.03, 6 ],
			[ 'Polycarbonate', 'PC Injection Grade', 'Injection moulding', 'PC-INJ', 'UK / Europe', 'Supplier B', 2.80, 3.00, 0.05, 0.03, 0, 0.05, 6 ],
		];
		foreach ( $seed as $s ) {
			$row = [
				'material_family' => $s[0], 'material_name' => $s[1], 'grade' => $s[2], 'grade_code' => $s[3],
				'region' => $s[4], 'supplier' => $s[5], 'currency' => 'GBP', 'unit' => 'kg',
				'market_price_per_kg' => $s[6], 'supplier_price_per_kg' => $s[7],
				'delivery_cost_per_kg' => $s[8], 'handling_cost_per_kg' => $s[9],
				'masterbatch_cost_per_kg' => $s[10], 'drying_cost_per_kg' => $s[11], 'margin_percent' => $s[12],
				'price_source' => 'Seed price list', 'last_updated' => $now, 'is_live_price' => 0, 'active' => 1,
			];
			$wpdb->insert( $tbl, $row );
			$id = $wpdb->insert_id;
			$obj = (object) $row;
			$wpdb->update( $tbl, [ 'quote_price_per_kg' => self::compute_quote( $obj ) ], [ 'id' => $id ] );
		}
	}

	private static function bust_cache() { global $wpdb; $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ih_price_%' OR option_name LIKE '_transient_timeout_ih_price_%'" ); }
	private static function iso( $mysql ) { return $mysql ? gmdate( 'c', strtotime( $mysql . ' UTC' ) ) : null; }
	private static function f( $v ) { return is_null( $v ) ? null : round( (float) $v, 4 ); }
}

IH_Material_Pricing::init();

/* Grant the pricing capability to admins on load (do this once, e.g. on activation):
   add_action('admin_init', function(){ if($r=get_role('administrator')) $r->add_cap('ih_manage_pricing'); });
*/
