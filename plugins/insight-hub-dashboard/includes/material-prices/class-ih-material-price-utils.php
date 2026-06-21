<?php
/**
 * class-ih-material-price-utils.php — shared, side-effect-free helpers:
 *   • currency conversion to GBP        (fx_to_gbp)
 *   • unit conversion to per-kg         (unit_factor / to_gbp_per_kg)
 *   • price badge mapping               (get_price_badge)  ← mirrors js badge()
 *   • a thin wp_remote_get wrapper       (http_get)        ← mockable in tests
 *
 * These extend the existing IH_Material_Pricing::to_gbp_per_kg()/fx_to_gbp()
 * logic but make it PUBLIC + reusable across providers and add the spec's
 * "never guess a conversion silently" rule: when no FX rate is known we return
 * null (conversion unavailable) instead of silently assuming 1.0.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Material_Price_Utils {

	/* ============================================================
	   CURRENCY → GBP
	   ============================================================ */

	/**
	 * Returns the multiplier to convert 1 unit of $currency into GBP, or null
	 * when the rate is unknown. GBP → 1.0. Rates come from the ih_fx_rates
	 * option (refresh that via your own job); we NEVER guess an unknown rate.
	 *
	 * @return float|null
	 */
	public static function fx_to_gbp( $currency ) {
		$cur = strtoupper( trim( (string) $currency ) );
		if ( '' === $cur || 'GBP' === $cur ) {
			return 1.0;
		}
		$defaults = array(
			'EUR' => 0.85,
			'USD' => 0.79,
			'CNY' => 0.11,
			'JPY' => 0.0053,
		);
		$rates = function_exists( 'get_option' ) ? get_option( 'ih_fx_rates', $defaults ) : $defaults;
		if ( ! is_array( $rates ) ) {
			$rates = $defaults;
		}
		// Merge so a partial option still covers the common currencies.
		$rates = array_merge( $defaults, $rates );
		return isset( $rates[ $cur ] ) ? (float) $rates[ $cur ] : null;
	}

	/* ============================================================
	   UNIT → per-kg PRICE factor
	   ============================================================
	   We store everything as a PRICE PER KG. Converting a price quoted per
	   some other unit into a price-per-kg means multiplying by (units per kg):
	     • per tonne  → per kg: ÷ 1000            (1 tonne = 1000 kg)
	     • per gram   → per kg: × 1000            (1 kg = 1000 g)
	     • per lb     → per kg: ÷ 0.45359237      (1 kg = 2.2046 lb)
	   Returns null for an unrecognised unit (so callers can refuse to guess). */
	public static function unit_to_kg_price_factor( $unit ) {
		$u = strtolower( trim( (string) $unit ) );
		switch ( $u ) {
			case '':
			case 'kg':
			case 'kgs':
			case 'kilogram':
			case 'kilograms':
				return 1.0;
			case 't':
			case 'mt':
			case 'tonne':
			case 'tonnes':
			case 'ton':
			case 'metric ton':
				return 1.0 / 1000.0;
			case 'g':
			case 'gram':
			case 'grams':
				return 1000.0;
			case 'lb':
			case 'lbs':
			case 'pound':
			case 'pounds':
				return 1.0 / 0.45359237;
			default:
				return null;
		}
	}

	/**
	 * Convert a price quoted in ($currency per $unit) into GBP per kg.
	 * Returns null if EITHER the currency rate or the unit is unknown — the
	 * caller must then store the original and mark the normalized value
	 * unavailable, never substituting a guessed figure.
	 *
	 * @return float|null
	 */
	public static function to_gbp_per_kg( $price, $currency, $unit ) {
		if ( ! is_numeric( $price ) ) {
			return null;
		}
		$factor = self::unit_to_kg_price_factor( $unit );
		$rate   = self::fx_to_gbp( $currency );
		if ( null === $factor || null === $rate ) {
			return null; // refuse to guess
		}
		return (float) $price * $factor * $rate;
	}

	/* ============================================================
	   PRICE BADGE — single source of truth, mirrored in material-pricing.js
	   ============================================================
	   CRITICAL: "Live" is returned ONLY for a verified licensed live feed
	   (source_type === live_feed AND is_verified_live === true). Every public
	   reference is explicitly NOT labelled "Live" because it is a delayed /
	   indicative market figure, not a contractual supplier quote. */
	public static function get_price_badge( $source_type, $is_verified_live = false ) {
		$src = (string) $source_type;

		if ( IH_Material_Price_Config::SRC_LIVE_FEED === $src && $is_verified_live ) {
			return array( 'state' => 'live', 'label' => 'Live' );
		}

		switch ( $src ) {
			case IH_Material_Price_Config::SRC_LIVE_FEED:
				// Configured-but-unverified live feed must NOT show as Live.
				return array( 'state' => 'manual', 'label' => 'Unverified feed' );
			case IH_Material_Price_Config::SRC_CSV_IMPORTED:
				return array( 'state' => 'csv', 'label' => 'CSV imported' );
			case IH_Material_Price_Config::SRC_MANUAL_OVERRIDE:
				return array( 'state' => 'override', 'label' => 'Manual override' );
			case IH_Material_Price_Config::SRC_PUBLIC_MARKET_REFERENCE:
				return array( 'state' => 'public', 'label' => 'Public reference' );
			case IH_Material_Price_Config::SRC_MONTHLY_INDEX:
				return array( 'state' => 'index', 'label' => 'Monthly index' );
			case IH_Material_Price_Config::SRC_DELAYED_PUBLIC_REFERENCE:
				return array( 'state' => 'delayed', 'label' => 'Delayed reference' );
			case IH_Material_Price_Config::SRC_NEWS_REFERENCE:
				return array( 'state' => 'news', 'label' => 'News reference' );
			case IH_Material_Price_Config::SRC_DEFAULT_ESTIMATE:
				return array( 'state' => 'estimate', 'label' => 'Default estimate' );
			case IH_Material_Price_Config::SRC_MANUAL_REQUIRED:
				return array( 'state' => 'manual', 'label' => 'Manual required' );
			default:
				return array( 'state' => 'manual', 'label' => 'Manual' );
		}
	}

	/* ============================================================
	   STALE WARNINGS
	   ============================================================ */
	public static function stale_warning( $source_type, $price_age_days ) {
		$age = (float) $price_age_days;
		if ( IH_Material_Price_Config::SRC_DELAYED_PUBLIC_REFERENCE === $source_type && $age > 45 ) {
			return 'Delayed public reference may be out of date.';
		}
		if ( $age > 7 && IH_Material_Price_Config::SRC_MONTHLY_INDEX !== $source_type ) {
			return 'Material reference price is older than 7 days.';
		}
		return '';
	}

	public static function age_days( $iso_or_mysql ) {
		if ( empty( $iso_or_mysql ) ) {
			return null;
		}
		$ts = strtotime( (string) $iso_or_mysql );
		if ( ! $ts ) {
			return null;
		}
		return ( time() - $ts ) / DAY_IN_SECONDS;
	}

	/* ============================================================
	   HTTP — single choke point so tests can mock external calls and so
	   every external request gets a timeout (security/robustness).
	   ============================================================ */
	public static function http_get( $url, $args = array() ) {
		$defaults = array(
			'timeout'    => 15,
			'user-agent' => 'InsightHub-MaterialPriceCheck/1.0 (+respects-robots)',
			'headers'    => array( 'Accept' => 'application/json' ),
		);
		$args = array_merge( $defaults, $args );
		$res  = wp_remote_get( $url, $args );
		if ( is_wp_error( $res ) ) {
			return array( 'ok' => false, 'code' => 0, 'body' => '', 'error' => $res->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = (string) wp_remote_retrieve_body( $res );
		return array( 'ok' => ( $code >= 200 && $code < 300 ), 'code' => $code, 'body' => $body, 'error' => '' );
	}

	/** Clamp a stored summary so we never persist huge external payloads. */
	public static function summary( $text, $max = 500 ) {
		$text = trim( (string) $text );
		if ( strlen( $text ) <= $max ) {
			return $text;
		}
		return substr( $text, 0, $max - 1 ) . '…';
	}
}
