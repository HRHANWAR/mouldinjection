<?php
/**
 * class-ih-plasticportal-provider.php — PLACEHOLDER provider.
 *
 * PlasticPortal publishes DELAYED EU polymer price references. There is no clean,
 * documented public API we are licensed to consume, and scraping their pages is
 * not permitted (respect robots/terms). So this is implemented as a placeholder
 * that conforms to the provider interface and DEGRADES GRACEFULLY: unless a
 * licensed/whitelisted endpoint is wired in via the `ih_plasticportal_endpoint`
 * filter, it records a 'skipped' check and the operator uses manual/CSV import.
 *
 * WHY source_type = delayed_public_reference (never live_feed):
 *   These figures are published with a lag and are indicative market references,
 *   not contractual quotes. They get the longer 45-day staleness window.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_PlasticPortal_Provider implements IH_Material_Price_Provider {

	public function key()         { return 'plasticportal'; }
	public function name()        { return 'PlasticPortal (delayed EU reference)'; }
	public function source_type() { return IH_Material_Price_Config::SRC_DELAYED_PUBLIC_REFERENCE; }
	public function enabled()     { return IH_Material_Price_Config::plasticportal_enabled(); }

	public function check() {
		if ( ! $this->enabled() ) {
			return IH_Material_Price_Result::skipped( $this->key(), $this->source_type(), 'PlasticPortal provider disabled.' );
		}

		// Operators with a licensed/whitelisted JSON endpoint can supply it here.
		$endpoint = apply_filters( 'ih_plasticportal_endpoint', '' );
		if ( '' === $endpoint ) {
			return IH_Material_Price_Result::skipped(
				$this->key(),
				$this->source_type(),
				'PlasticPortal has no clean public API and may not be scraped — provide a licensed endpoint via the ih_plasticportal_endpoint filter, or use manual/CSV import.'
			);
		}

		$res = IH_Material_Price_Utils::http_get( $endpoint, array( 'timeout' => 15 ) );
		if ( ! $res['ok'] ) {
			return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'PlasticPortal endpoint failed (HTTP ' . $res['code'] . ').' );
		}
		$data = json_decode( $res['body'], true );
		if ( ! is_array( $data ) ) {
			return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'PlasticPortal endpoint returned non-JSON.' );
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
				$errors[] = 'No conversion for ' . ( $row['polymer'] ?? '?' ) . ' (' . $currency . '/' . $unit . ').';
			}
			$items[] = IH_Material_Price_Result::item( array(
				'material_family'     => sanitize_text_field( (string) ( $row['material_family'] ?? $row['polymer'] ?? '' ) ),
				'polymer'             => sanitize_text_field( (string) ( $row['polymer'] ?? '' ) ),
				'region'              => sanitize_text_field( (string) ( $row['region'] ?? 'EU' ) ),
				'source_type'         => $this->source_type(),
				'source_name'         => $this->name(),
				'source_reference'    => 'plasticportal',
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
		return IH_Material_Price_Result::make( $this->key(), $this->source_type(), $status, $items, $errors, 'PlasticPortal delayed reference: ' . count( $items ) . ' rows.' );
	}
}
