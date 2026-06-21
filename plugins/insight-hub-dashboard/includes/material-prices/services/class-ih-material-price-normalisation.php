<?php
/**
 * class-ih-material-price-normalisation.php — enforces the spec's normalisation
 * rules on a provider item before it is stored / used:
 *
 *   • everything stored as GBP/kg
 *   • tonne→kg ÷1000, gram→kg ×1000 (price per g → per kg), lb→kg ÷0.45359237
 *   • convert all currencies to GBP
 *   • if no FX rate / unknown unit → keep the original, mark normalized=false,
 *     and DO NOT expose a price_per_kg_gbp (never guess silently)
 *   • index-only sources (monthly_index) are kept as an index, never a £/kg
 *   • reject implausible normalised prices (≤0 or > £500/kg) as un-normalised
 *
 * Providers already attempt conversion; this is the single authority that
 * re-checks the result so storage can trust the item.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Material_Price_Normalisation {

	const MAX_PLAUSIBLE_PER_KG = 500.0;

	/**
	 * Normalise a single item in place and return it.
	 *
	 * @param array $item from IH_Material_Price_Result::item()
	 * @return array
	 */
	public static function normalise( array $item ) {
		// Index sources are never a £/kg price.
		if ( ! empty( $item['is_index'] ) || IH_Material_Price_Config::SRC_MONTHLY_INDEX === ( $item['source_type'] ?? '' ) ) {
			$item['price_per_kg_gbp'] = null;
			$item['normalized']       = false;
			$item['is_index']         = true;
			return $item;
		}

		// News alerts carry no price.
		if ( IH_Material_Price_Config::SRC_NEWS_REFERENCE === ( $item['source_type'] ?? '' ) ) {
			$item['price_per_kg_gbp'] = null;
			$item['normalized']       = false;
			return $item;
		}

		$gbp = isset( $item['price_per_kg_gbp'] ) ? $item['price_per_kg_gbp'] : null;

		// Recompute from the original when we have one, so storage is authoritative.
		if ( isset( $item['original_price'] ) && is_numeric( $item['original_price'] ) ) {
			$recomputed = IH_Material_Price_Utils::to_gbp_per_kg(
				(float) $item['original_price'],
				$item['original_currency'] ?? '',
				$item['original_unit'] ?? ''
			);
			if ( null !== $recomputed ) {
				$gbp = $recomputed;
			} elseif ( null === $gbp ) {
				$gbp = null; // could not convert and provider had none either
			}
		}

		if ( null === $gbp || ! is_finite( (float) $gbp ) || (float) $gbp <= 0 || (float) $gbp > self::MAX_PLAUSIBLE_PER_KG ) {
			// Keep the original for the record, but mark the normalised value
			// unavailable so it is NEVER used as a calculator price.
			$item['price_per_kg_gbp'] = null;
			$item['normalized']       = false;
			$item['is_verified_live'] = false; // an unconverted figure can't be "live"
			return $item;
		}

		$item['price_per_kg_gbp'] = round( (float) $gbp, 4 );
		$item['normalized']       = true;
		return $item;
	}

	/** Normalise a list of items. */
	public static function normalise_all( array $items ) {
		return array_map( array( __CLASS__, 'normalise' ), $items );
	}
}
