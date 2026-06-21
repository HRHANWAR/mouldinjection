<?php
/**
 * class-ih-material-price-config.php — central configuration + source-type registry
 * for the 24-hour material price CHECKING / REFERENCE system.
 *
 * WHY THIS EXISTS
 *   The user spec describes a Node/TS service that reads `process.env.*`. In
 *   WordPress the equivalent of an env var is a wp-config CONSTANT. This class
 *   reads every documented constant with a safe default via defined()/constant(),
 *   so a clean install behaves predictably even when nothing is configured.
 *
 *   One toggle — IH_ALLOW_PUBLIC_REFERENCE_FOR_QUOTES — must ALSO be settable as
 *   an admin OPTION, and the OPTION wins over the constant. That lets a site
 *   owner flip "use public references for estimates" from wp-admin without
 *   editing wp-config. Default is false (public references are NOT used as quote
 *   prices unless an admin explicitly opts in).
 *
 * ──────────────────────────────────────────────────────────────────────────
 * CONFIG REFERENCE — define any of these in wp-config.php to override defaults.
 * API KEYS ARE READ SERVER-SIDE ONLY and are NEVER localized to the frontend.
 * ──────────────────────────────────────────────────────────────────────────
 *   IH_PRICE_CHECK_INTERVAL_HOURS   (int)   24    How often the scheduled check runs.
 *   IH_PRICE_FEED_URL               (string)''    Licensed live feed URL. ONLY when
 *                                                  set can a row ever be "Live".
 *   IH_PRICE_FEED_SOURCE            (string)''    Human label for the licensed feed.
 *   IH_PRICE_FEED_API_KEY           (string)''    Bearer key for the licensed feed.
 *   IH_TRADING_ECONOMICS_ENABLED    (bool)  true  Trading Economics public reference.
 *   IH_TRADING_ECONOMICS_KEY        (string)'guest:guest'  TE API key (public guest by default).
 *   IH_TRADING_ECONOMICS_BASE_URL   (string)'https://api.tradingeconomics.com'
 *   IH_FRED_ENABLED                 (bool)  true  FRED monthly index/trend source.
 *   IH_FRED_API_KEY                 (string)''    Required for FRED; skipped if empty.
 *   IH_FRED_BASE_URL                (string)'https://api.stlouisfed.org/fred'
 *   IH_PLASTICPORTAL_ENABLED        (bool)  true  Delayed EU polymer reference (placeholder).
 *   IH_PLASTICKER_ENABLED           (bool)  false Plasticker/Recybase marketplace (placeholder).
 *   IH_RSS_MARKET_NEWS_ENABLED      (bool)  true  RSS/news alerts only (no quote prices).
 *   IH_RSS_MARKET_NEWS_URL          (string)''    Optional RSS feed URL for news alerts.
 *   IH_ALLOW_PUBLIC_REFERENCE_FOR_QUOTES (bool) false  Constant default; the admin
 *                                                  OPTION `ih_allow_public_reference_for_quotes`
 *                                                  overrides it when set.
 *   IH_DEFAULT_CURRENCY             (string)'GBP'
 *   IH_DEFAULT_REGION               (string)'UK'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Material_Price_Config {

	/* ============================================================
	   SOURCE TYPES — the canonical `source_type` vocabulary.
	   Stored as plain strings in the DB so they are stable + greppable.
	   ============================================================ */
	const SRC_MANUAL_OVERRIDE          = 'manual_override';
	const SRC_CSV_IMPORTED             = 'csv_imported';
	const SRC_PUBLIC_MARKET_REFERENCE  = 'public_market_reference';
	const SRC_MONTHLY_INDEX            = 'monthly_index';
	const SRC_DELAYED_PUBLIC_REFERENCE = 'delayed_public_reference';
	const SRC_NEWS_REFERENCE           = 'news_reference';
	const SRC_DEFAULT_ESTIMATE         = 'default_estimate';
	const SRC_MANUAL_REQUIRED          = 'manual_required';
	const SRC_LIVE_FEED                = 'live_feed'; // ONLY when IH_PRICE_FEED_URL configured + verified.

	/** The admin option that mirrors IH_ALLOW_PUBLIC_REFERENCE_FOR_QUOTES (option wins). */
	const OPTION_ALLOW_PUBLIC_REF = 'ih_allow_public_reference_for_quotes';

	/** Option storing the last full check-run summary (for the admin panel). */
	const OPTION_LAST_RUN = 'ih_material_price_last_run';

	/** Transient lock name used to rate-limit the manual "Check now" button. */
	const CHECK_NOW_LOCK = 'ih_material_price_check_now_lock';

	/* ---- generic constant readers (defined()/constant() with default) ---- */

	private static function const_str( $name, $default = '' ) {
		return defined( $name ) ? (string) constant( $name ) : $default;
	}

	private static function const_bool( $name, $default = false ) {
		return defined( $name ) ? (bool) constant( $name ) : $default;
	}

	private static function const_int( $name, $default = 0 ) {
		return defined( $name ) ? (int) constant( $name ) : $default;
	}

	/* ============================================================
	   TYPED ACCESSORS
	   ============================================================ */

	public static function interval_hours() {
		$h = self::const_int( 'IH_PRICE_CHECK_INTERVAL_HOURS', 24 );
		return $h > 0 ? $h : 24;
	}

	public static function default_currency() {
		$c = strtoupper( self::const_str( 'IH_DEFAULT_CURRENCY', 'GBP' ) );
		return $c ?: 'GBP';
	}

	public static function default_region() {
		$r = self::const_str( 'IH_DEFAULT_REGION', 'UK' );
		return $r ?: 'UK';
	}

	/* ---- licensed live feed (the ONLY source that may be "Live") ---- */
	public static function feed_url()    { return self::const_str( 'IH_PRICE_FEED_URL' ); }
	public static function feed_source() { return self::const_str( 'IH_PRICE_FEED_SOURCE', 'Licensed feed' ); }
	/** Server-side only. Never localize this to the browser. */
	public static function feed_api_key() {
		// Backwards-compat: the original module read IH_PRICE_FEED_KEY.
		$k = self::const_str( 'IH_PRICE_FEED_API_KEY' );
		return '' !== $k ? $k : self::const_str( 'IH_PRICE_FEED_KEY' );
	}
	public static function live_feed_configured() { return '' !== self::feed_url(); }

	/* ---- Trading Economics (public market reference) ---- */
	public static function trading_economics_enabled()  { return self::const_bool( 'IH_TRADING_ECONOMICS_ENABLED', true ); }
	public static function trading_economics_key()      { return self::const_str( 'IH_TRADING_ECONOMICS_KEY', 'guest:guest' ); }
	public static function trading_economics_base_url() { return self::const_str( 'IH_TRADING_ECONOMICS_BASE_URL', 'https://api.tradingeconomics.com' ); }

	/* ---- FRED (monthly index/trend) ---- */
	public static function fred_enabled()  { return self::const_bool( 'IH_FRED_ENABLED', true ); }
	/** Server-side only. */
	public static function fred_api_key()  { return self::const_str( 'IH_FRED_API_KEY' ); }
	public static function fred_base_url() { return self::const_str( 'IH_FRED_BASE_URL', 'https://api.stlouisfed.org/fred' ); }

	/* ---- placeholder providers ---- */
	public static function plasticportal_enabled() { return self::const_bool( 'IH_PLASTICPORTAL_ENABLED', true ); }
	public static function plasticker_enabled()    { return self::const_bool( 'IH_PLASTICKER_ENABLED', false ); }

	/* ---- RSS / news (alerts only) ---- */
	public static function rss_news_enabled() { return self::const_bool( 'IH_RSS_MARKET_NEWS_ENABLED', true ); }
	public static function rss_news_url()     { return self::const_str( 'IH_RSS_MARKET_NEWS_URL' ); }

	/* ============================================================
	   PUBLIC-REFERENCE-FOR-QUOTES TOGGLE (OPTION wins over constant)
	   ============================================================ */
	public static function allow_public_reference_for_quotes() {
		$opt = get_option( self::OPTION_ALLOW_PUBLIC_REF, null );
		if ( null !== $opt && '' !== $opt ) {
			return (bool) $opt;
		}
		return self::const_bool( 'IH_ALLOW_PUBLIC_REFERENCE_FOR_QUOTES', false );
	}

	/**
	 * A human-readable, SAFE-FOR-FRONTEND config snapshot. Deliberately excludes
	 * every API key so this can be exposed to staff UIs without leaking secrets.
	 */
	public static function public_snapshot() {
		return array(
			'interval_hours'            => self::interval_hours(),
			'default_currency'          => self::default_currency(),
			'default_region'            => self::default_region(),
			'live_feed_configured'      => self::live_feed_configured(),
			'trading_economics_enabled' => self::trading_economics_enabled(),
			'fred_enabled'              => self::fred_enabled() && '' !== self::fred_api_key(),
			'plasticportal_enabled'     => self::plasticportal_enabled(),
			'plasticker_enabled'        => self::plasticker_enabled(),
			'rss_news_enabled'          => self::rss_news_enabled(),
			'allow_public_reference'    => self::allow_public_reference_for_quotes(),
		);
	}
}
