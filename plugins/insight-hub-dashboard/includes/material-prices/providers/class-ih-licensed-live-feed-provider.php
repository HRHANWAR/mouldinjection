<?php
/**
 * class-ih-licensed-live-feed-provider.php
 *
 * The ONLY provider that may emit source_type=live_feed with is_verified_live.
 * It is active ONLY when IH_PRICE_FEED_URL is configured — i.e. the operator has
 * a LICENSED data feed (ChemOrbis / Polymerupdate / The Plastics Exchange export,
 * a supplier API, or an ERP push). We never scrape subscription sites; we ingest
 * a feed the operator is licensed to use, with credentials kept in wp-config.
 *
 * Expected feed payload: a JSON array of rows, each:
 *   { grade_code, price, currency, unit, region?, supplier?, source?, reference? }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Licensed_Live_Feed_Provider implements IH_Material_Price_Provider {

	public function key()         { return 'licensed_live_feed'; }
	public function name()        { return IH_Material_Price_Config::feed_source() ?: 'Licensed live feed'; }
	public function source_type() { return IH_Material_Price_Config::SRC_LIVE_FEED; }
	public function enabled()     { return IH_Material_Price_Config::live_feed_configured(); }

	public function check() {
		if ( ! $this->enabled() ) {
			return IH_Material_Price_Result::skipped(
				$this->key(),
				$this->source_type(),
				'No licensed feed configured (IH_PRICE_FEED_URL is empty) — using manual/CSV prices instead.'
			);
		}

		$url     = IH_Material_Price_Config::feed_url();
		$api_key = IH_Material_Price_Config::feed_api_key();
		$args    = array( 'timeout' => 20 );
		if ( '' !== $api_key ) {
			// Key stays server-side; sent only in the outbound request header.
			$args['headers'] = array( 'Authorization' => 'Bearer ' . $api_key, 'Accept' => 'application/json' );
		}

		$res = IH_Material_Price_Utils::http_get( $url, $args );
		if ( ! $res['ok'] ) {
			return IH_Material_Price_Result::failed(
				$this->key(),
				$this->source_type(),
				'Licensed feed request failed (HTTP ' . $res['code'] . '): ' . $res['error']
			);
		}

		$data = json_decode( $res['body'], true );
		if ( ! is_array( $data ) ) {
			return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'Licensed feed returned non-JSON or empty payload.' );
		}

		$items  = array();
		$errors = array();
		foreach ( $data as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['price'] ) ) {
				continue;
			}
			$currency = isset( $row['currency'] ) ? $row['currency'] : IH_Material_Price_Config::default_currency();
			$unit     = isset( $row['unit'] ) ? $row['unit'] : 'kg';
			$gbp      = IH_Material_Price_Utils::to_gbp_per_kg( (float) $row['price'], $currency, $unit );
			if ( null === $gbp ) {
				$errors[] = 'Could not normalise ' . ( $row['grade_code'] ?? '?' ) . ' (' . $currency . '/' . $unit . ') — stored as reference only.';
			}
			$items[] = IH_Material_Price_Result::item( array(
				'grade_code'        => isset( $row['grade_code'] ) ? sanitize_text_field( (string) $row['grade_code'] ) : '',
				'material_family'   => isset( $row['material_family'] ) ? sanitize_text_field( (string) $row['material_family'] ) : '',
				'region'            => isset( $row['region'] ) ? sanitize_text_field( (string) $row['region'] ) : IH_Material_Price_Config::default_region(),
				'source_type'       => $this->source_type(),
				'source_name'       => isset( $row['source'] ) ? sanitize_text_field( (string) $row['source'] ) : $this->name(),
				'source_reference'  => isset( $row['reference'] ) ? sanitize_text_field( (string) $row['reference'] ) : '',
				'original_price'    => (float) $row['price'],
				'original_currency' => strtoupper( (string) $currency ),
				'original_unit'     => strtolower( (string) $unit ),
				'price_per_kg_gbp'  => $gbp,
				'normalized'        => ( null !== $gbp ),
				// A licensed feed is the only verified-live source.
				'is_verified_live'  => ( null !== $gbp ),
				'metadata'          => array( 'supplier' => isset( $row['supplier'] ) ? sanitize_text_field( (string) $row['supplier'] ) : '' ),
			) );
		}

		$status = empty( $items ) ? IH_Material_Price_Result::STATUS_FAILED
			: ( empty( $errors ) ? IH_Material_Price_Result::STATUS_SUCCESS : IH_Material_Price_Result::STATUS_PARTIAL );

		return IH_Material_Price_Result::make(
			$this->key(),
			$this->source_type(),
			$status,
			$items,
			$errors,
			'Licensed feed: ' . count( $items ) . ' rows from ' . $this->name() . '.'
		);
	}
}
