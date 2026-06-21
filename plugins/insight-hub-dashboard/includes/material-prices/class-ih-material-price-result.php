<?php
/**
 * class-ih-material-price-result.php — builders for the normalised result/item
 * shapes returned by providers. Keeps every provider emitting the SAME array
 * structure so the orchestrator, storage and admin UI can rely on it.
 *
 * Result (≈ TS MaterialPriceProviderResult):
 *   provider, source_type, status (success|partial|failed|skipped),
 *   checked_at (ISO-8601), items[], errors[], raw_summary
 *
 * Item (≈ TS MaterialPriceReferenceItem):
 *   material_family, polymer, grade_code, region,
 *   source_type, source_name, source_reference,
 *   original_price, original_currency, original_unit,
 *   price_per_kg_gbp (null if not normalisable), normalized (bool),
 *   is_index (bool), index_value, index_series_id,
 *   is_verified_live (bool), is_public_reference (bool),
 *   checked_at, metadata (array)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Material_Price_Result {

	const STATUS_SUCCESS = 'success';
	const STATUS_PARTIAL = 'partial';
	const STATUS_FAILED  = 'failed';
	const STATUS_SKIPPED = 'skipped';

	public static function now_iso() {
		return gmdate( 'c' );
	}

	/**
	 * @param string $provider     provider key
	 * @param string $source_type  IH_Material_Price_Config::SRC_*
	 * @param string $status       one of STATUS_*
	 * @param array  $items        list of items from item()
	 * @param array  $errors       list of error strings
	 * @param string $raw_summary  short human summary (NOT a huge payload)
	 */
	public static function make( $provider, $source_type, $status, $items = array(), $errors = array(), $raw_summary = '' ) {
		return array(
			'provider'    => (string) $provider,
			'source_type' => (string) $source_type,
			'status'      => (string) $status,
			'checked_at'  => self::now_iso(),
			'items'       => array_values( (array) $items ),
			'errors'      => array_values( (array) $errors ),
			'raw_summary' => IH_Material_Price_Utils::summary( $raw_summary ),
		);
	}

	public static function skipped( $provider, $source_type, $reason ) {
		return self::make( $provider, $source_type, self::STATUS_SKIPPED, array(), array( $reason ), $reason );
	}

	public static function failed( $provider, $source_type, $reason ) {
		return self::make( $provider, $source_type, self::STATUS_FAILED, array(), array( $reason ), $reason );
	}

	/**
	 * Build a single normalised reference item. Pass overrides via $args.
	 */
	public static function item( array $args ) {
		$defaults = array(
			'material_family'   => '',
			'polymer'           => '',
			'grade_code'        => '',
			'region'            => IH_Material_Price_Config::default_region(),
			'source_type'       => '',
			'source_name'       => '',
			'source_reference'  => '',
			'original_price'    => null,
			'original_currency' => '',
			'original_unit'     => '',
			'price_per_kg_gbp'  => null,
			'normalized'        => false,
			'is_index'          => false,
			'index_value'       => null,
			'index_series_id'   => '',
			'is_verified_live'  => false,
			'is_public_reference' => false,
			'checked_at'        => self::now_iso(),
			'metadata'          => array(),
		);
		return array_merge( $defaults, $args );
	}
}
