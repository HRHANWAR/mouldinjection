<?php
/**
 * class-ih-plasticker-provider.php — PLACEHOLDER provider, DISABLED by default.
 *
 * Plasticker / Recybase publish marketplace (often recyclate) price references.
 * There is no clean licensed public API we consume and the pages may not be
 * scraped, so this is a disabled-by-default placeholder (IH_PLASTICKER_ENABLED
 * defaults to false). When enabled with a licensed endpoint (ih_plasticker_endpoint
 * filter) it emits public_market_reference items; otherwise it cleanly skips.
 *
 * Never live_feed — marketplace references are indicative, not contractual.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Plasticker_Provider implements IH_Material_Price_Provider {

	public function key()         { return 'plasticker'; }
	public function name()        { return 'Plasticker / Recybase (marketplace reference)'; }
	public function source_type() { return IH_Material_Price_Config::SRC_PUBLIC_MARKET_REFERENCE; }
	public function enabled()     { return IH_Material_Price_Config::plasticker_enabled(); }

	public function check() {
		if ( ! $this->enabled() ) {
			return IH_Material_Price_Result::skipped( $this->key(), $this->source_type(), 'Plasticker provider disabled by default (IH_PLASTICKER_ENABLED=false).' );
		}

		$endpoint = apply_filters( 'ih_plasticker_endpoint', '' );
		if ( '' === $endpoint ) {
			return IH_Material_Price_Result::skipped(
				$this->key(),
				$this->source_type(),
				'Plasticker has no clean public API and may not be scraped — provide a licensed endpoint via the ih_plasticker_endpoint filter, or use manual/CSV import.'
			);
		}

		$res = IH_Material_Price_Utils::http_get( $endpoint, array( 'timeout' => 15 ) );
		if ( ! $res['ok'] ) {
			return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'Plasticker endpoint failed (HTTP ' . $res['code'] . ').' );
		}
		$data = json_decode( $res['body'], true );
		if ( ! is_array( $data ) ) {
			return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'Plasticker endpoint returned non-JSON.' );
		}

		$items  = array();
		$errors = array();
		foreach ( $data as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['price'] ) ) {
				continue;
			}
			$currency = strtoupper( (string) ( $row['currency'] ?? 'EUR' ) );
			$unit     = strtolower( (string) ( $row['unit'] ?? 'tonne' ) );
			$gbp      = IH_Material_Price_Utils::to_gbp_per_kg( (float) $row['price'], $currency, $unit );
			if ( null === $gbp ) {
				$errors[] = 'No conversion for ' . ( $row['polymer'] ?? '?' ) . '.';
			}
			$items[] = IH_Material_Price_Result::item( array(
				'material_family'     => sanitize_text_field( (string) ( $row['material_family'] ?? $row['polymer'] ?? '' ) ),
				'polymer'             => sanitize_text_field( (string) ( $row['polymer'] ?? '' ) ),
				'source_type'         => $this->source_type(),
				'source_name'         => $this->name(),
				'source_reference'    => 'plasticker',
				'original_price'      => (float) $row['price'],
				'original_currency'   => $currency,
				'original_unit'       => $unit,
				'price_per_kg_gbp'    => $gbp,
				'normalized'          => ( null !== $gbp ),
				'is_public_reference' => true,
				'is_verified_live'    => false,
			) );
		}

		$status = empty( $items ) ? IH_Material_Price_Result::STATUS_FAILED
			: ( empty( $errors ) ? IH_Material_Price_Result::STATUS_SUCCESS : IH_Material_Price_Result::STATUS_PARTIAL );
		return IH_Material_Price_Result::make( $this->key(), $this->source_type(), $status, $items, $errors, 'Plasticker marketplace reference: ' . count( $items ) . ' rows.' );
	}
}
