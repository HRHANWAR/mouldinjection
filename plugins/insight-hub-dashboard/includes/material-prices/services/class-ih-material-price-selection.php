<?php
/**
 * class-ih-material-price-selection.php — the quote-price selection logic.
 *
 * PHP reproduction of the spec's selectMaterialPriceForQuote(). Given everything
 * we know about a material, it decides WHICH price the calculator should use and
 * returns the badge, the calculationType (Quote | Estimate | Incomplete) and any
 * warning text.
 *
 * SELECTION ORDER (highest priority first):
 *   1. Manual override            → Quote
 *   2. Supplier CSV imported      → Quote
 *   3. Licensed live feed         → Quote   (ONLY if IH_PRICE_FEED_URL set & verified live)
 *   4. Public market reference    → Estimate (ONLY if the admin option allows it)
 *   5. Per-polymer default        → Estimate
 *   6. Manual entry required      → Incomplete
 *
 * CRITICAL: a public reference NEVER auto-replaces a supplier CSV or manual price,
 * and is only used at all when allow_public_reference_for_quotes() is true. The
 * "Live" badge is reserved for a verified licensed feed (see IH_Material_Price_Utils).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Material_Price_Selection {

	const CALC_QUOTE      = 'Quote';
	const CALC_ESTIMATE   = 'Estimate';
	const CALC_INCOMPLETE = 'Incomplete';

	/**
	 * Pure selection over a normalised context array. Keys (all optional):
	 *   manual_override_price (float|null)
	 *   csv_price (float|null), csv_source (string)
	 *   live_feed_price (float|null), live_feed_verified (bool)
	 *   public_reference_price (float|null), public_source (string)
	 *   default_estimate_price (float|null)
	 *   source_name (string), last_checked (string), last_updated (string)
	 *   price_age_days (float|null)
	 *   allow_public_reference (bool)
	 *
	 * @return array
	 */
	public static function select( array $ctx ) {
		$allow_public = ! empty( $ctx['allow_public_reference'] );

		$base = array(
			'price_per_kg'     => null,
			'source_type'      => IH_Material_Price_Config::SRC_MANUAL_REQUIRED,
			'source_name'      => isset( $ctx['source_name'] ) ? $ctx['source_name'] : '',
			'calculation_type' => self::CALC_INCOMPLETE,
			'is_verified_live' => false,
			'warning'          => '',
			'last_checked'     => isset( $ctx['last_checked'] ) ? $ctx['last_checked'] : null,
			'last_updated'     => isset( $ctx['last_updated'] ) ? $ctx['last_updated'] : null,
		);
		$age = isset( $ctx['price_age_days'] ) ? $ctx['price_age_days'] : null;

		// 1) Manual override — always wins.
		if ( isset( $ctx['manual_override_price'] ) && (float) $ctx['manual_override_price'] > 0 ) {
			return self::finish( $base, array(
				'price_per_kg'     => (float) $ctx['manual_override_price'],
				'source_type'      => IH_Material_Price_Config::SRC_MANUAL_OVERRIDE,
				'source_name'      => $base['source_name'] ?: 'Manual override',
				'calculation_type' => self::CALC_QUOTE,
			), $age );
		}

		// 2) Supplier CSV imported.
		if ( isset( $ctx['csv_price'] ) && (float) $ctx['csv_price'] > 0 ) {
			return self::finish( $base, array(
				'price_per_kg'     => (float) $ctx['csv_price'],
				'source_type'      => IH_Material_Price_Config::SRC_CSV_IMPORTED,
				'source_name'      => ! empty( $ctx['csv_source'] ) ? $ctx['csv_source'] : ( $base['source_name'] ?: 'Supplier CSV' ),
				'calculation_type' => self::CALC_QUOTE,
			), $age );
		}

		// 3) Licensed live feed — only when configured AND verified live.
		if ( IH_Material_Price_Config::live_feed_configured()
			&& ! empty( $ctx['live_feed_verified'] )
			&& isset( $ctx['live_feed_price'] ) && (float) $ctx['live_feed_price'] > 0 ) {
			return self::finish( $base, array(
				'price_per_kg'     => (float) $ctx['live_feed_price'],
				'source_type'      => IH_Material_Price_Config::SRC_LIVE_FEED,
				'source_name'      => $base['source_name'] ?: IH_Material_Price_Config::feed_source(),
				'calculation_type' => self::CALC_QUOTE,
				'is_verified_live' => true,
			), $age );
		}

		// 4) Public market reference — ONLY if the admin option allows it.
		if ( $allow_public && isset( $ctx['public_reference_price'] ) && (float) $ctx['public_reference_price'] > 0 ) {
			$res = self::finish( $base, array(
				'price_per_kg'     => (float) $ctx['public_reference_price'],
				'source_type'      => IH_Material_Price_Config::SRC_PUBLIC_MARKET_REFERENCE,
				'source_name'      => ! empty( $ctx['public_source'] ) ? $ctx['public_source'] : 'Public market reference',
				'calculation_type' => self::CALC_ESTIMATE,
			), $age );
			$res['warning'] = self::join_warning( 'Estimate uses a public market reference, not a supplier quote.', $res['warning'] );
			return $res;
		}

		// 5) Per-polymer default estimate.
		if ( isset( $ctx['default_estimate_price'] ) && (float) $ctx['default_estimate_price'] > 0 ) {
			$res = self::finish( $base, array(
				'price_per_kg'     => (float) $ctx['default_estimate_price'],
				'source_type'      => IH_Material_Price_Config::SRC_DEFAULT_ESTIMATE,
				'source_name'      => $base['source_name'] ?: 'Default estimate',
				'calculation_type' => self::CALC_ESTIMATE,
			), $age );
			$res['warning'] = self::join_warning( 'Using a default per-polymer estimate — enter a supplier or manual price for an accurate quote.', $res['warning'] );
			return $res;
		}

		// 6) Manual entry required.
		$base['warning'] = 'No price on record — manual entry required.';
		$base['badge']   = IH_Material_Price_Utils::get_price_badge( $base['source_type'], false );
		return $base;
	}

	private static function finish( array $base, array $override, $age_days ) {
		$res          = array_merge( $base, $override );
		$res['badge'] = IH_Material_Price_Utils::get_price_badge( $res['source_type'], ! empty( $res['is_verified_live'] ) );
		$stale        = IH_Material_Price_Utils::stale_warning( $res['source_type'], $age_days );
		if ( '' !== $stale ) {
			$res['warning'] = self::join_warning( $res['warning'], $stale );
		}
		return $res;
	}

	private static function join_warning( $a, $b ) {
		$a = trim( (string) $a );
		$b = trim( (string) $b );
		if ( '' === $a ) { return $b; }
		if ( '' === $b ) { return $a; }
		return $a . ' ' . $b;
	}

	/**
	 * Build a selection context from an ih_materials DB row (object or array).
	 * Maps the existing columns onto the context the pure selector expects.
	 */
	public static function context_from_row( $row ) {
		$row = (object) $row;
		$src = isset( $row->source_type ) ? (string) $row->source_type : '';

		$manual = ( ! empty( $row->is_manual_override ) && (float) ( $row->manual_override_price ?? 0 ) > 0 )
			? (float) $row->manual_override_price : null;

		// Supplier CSV price: a supplier price tagged as csv_imported, or any
		// supplier price (the calculator's historical "what we pay" base).
		$csv = null;
		if ( IH_Material_Price_Config::SRC_CSV_IMPORTED === $src && (float) ( $row->supplier_price_per_kg ?? 0 ) > 0 ) {
			$csv = (float) $row->supplier_price_per_kg;
		} elseif ( (float) ( $row->supplier_price_per_kg ?? 0 ) > 0 ) {
			$csv = (float) $row->supplier_price_per_kg;
		}

		$live_verified = ! empty( $row->is_verified_live );
		$live_price    = ( IH_Material_Price_Config::SRC_LIVE_FEED === $src && (float) ( $row->market_price_per_kg ?? 0 ) > 0 )
			? (float) $row->market_price_per_kg : null;

		$public_price = null;
		if ( ! empty( $row->is_public_reference ) && (float) ( $row->market_price_per_kg ?? 0 ) > 0 ) {
			$public_price = (float) $row->market_price_per_kg;
		}

		$last = isset( $row->last_checked_at ) && $row->last_checked_at ? $row->last_checked_at : ( $row->last_updated ?? null );

		return array(
			'manual_override_price'  => $manual,
			'csv_price'              => $csv,
			'csv_source'             => $row->source_name ?? ( $row->price_source ?? '' ),
			'live_feed_price'        => $live_price,
			'live_feed_verified'     => $live_verified,
			'public_reference_price' => $public_price,
			'public_source'          => $row->source_name ?? '',
			'default_estimate_price' => null,
			'source_name'            => $row->source_name ?? ( $row->price_source ?? '' ),
			'last_checked'           => $row->last_checked_at ?? null,
			'last_updated'           => $row->last_updated ?? null,
			'price_age_days'         => IH_Material_Price_Utils::age_days( $last ),
			'allow_public_reference' => IH_Material_Price_Config::allow_public_reference_for_quotes(),
		);
	}
}
