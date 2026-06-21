<?php
/**
 * class-ih-fred-provider.php
 *
 * FRED (Federal Reserve Economic Data) — a MONTHLY INDEX / TREND source, NOT a
 * £/kg price. We store its observations as source_type=monthly_index with an
 * index_value + index_series_id. These describe how plastics/resin producer
 * prices are TRENDING; they must NOT be used directly as a £/kg calculator price
 * (only as a clearly-labelled trend adjustment applied on top of an existing
 * base price — which the selection logic deliberately never does automatically).
 *
 * Requires IH_FRED_API_KEY. If absent we SKIP (no key → no call).
 *
 * Series:
 *   PCU325211325211 — PPI: Plastics Material & Resins Manufacturing
 *   WPS0662          — PPI: Plastic Resins & Materials
 *   PCU3252132521    — PPI: Synthetic Rubber & Resin (segment)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_FRED_Provider implements IH_Material_Price_Provider {

	private static function series() {
		return array(
			'PCU325211325211' => 'PPI: Plastics Material & Resins Manufacturing',
			'WPS0662'         => 'PPI: Plastic Resins & Materials',
			'PCU3252132521'   => 'PPI: Synthetic Rubber & Resin',
		);
	}

	public function key()         { return 'fred'; }
	public function name()        { return 'FRED (St. Louis Fed)'; }
	public function source_type() { return IH_Material_Price_Config::SRC_MONTHLY_INDEX; }
	public function enabled()     { return IH_Material_Price_Config::fred_enabled(); }

	public function check() {
		if ( ! $this->enabled() ) {
			return IH_Material_Price_Result::skipped( $this->key(), $this->source_type(), 'FRED provider disabled.' );
		}
		$api_key = IH_Material_Price_Config::fred_api_key();
		if ( '' === $api_key ) {
			return IH_Material_Price_Result::skipped(
				$this->key(),
				$this->source_type(),
				'FRED skipped: IH_FRED_API_KEY not configured.'
			);
		}

		$base   = rtrim( IH_Material_Price_Config::fred_base_url(), '/' );
		$items  = array();
		$errors = array();

		foreach ( self::series() as $series_id => $label ) {
			$url = $base . '/series/observations?series_id=' . rawurlencode( $series_id )
				. '&api_key=' . rawurlencode( $api_key )
				. '&file_type=json&sort_order=desc&limit=1';
			$res = IH_Material_Price_Utils::http_get( $url, array( 'timeout' => 15 ) );
			if ( ! $res['ok'] ) {
				$errors[] = $series_id . ': HTTP ' . $res['code'] . ' ' . $res['error'];
				continue;
			}
			$data = json_decode( $res['body'], true );
			$obs  = ( is_array( $data ) && ! empty( $data['observations'][0] ) ) ? $data['observations'][0] : null;
			if ( ! $obs || ! isset( $obs['value'] ) || '.' === $obs['value'] ) {
				$errors[] = $series_id . ': no usable observation.';
				continue;
			}
			$items[] = IH_Material_Price_Result::item( array(
				'material_family'   => 'Plastics & resins (index)',
				'polymer'           => '',
				'source_type'       => $this->source_type(),
				'source_name'       => $this->name(),
				'source_reference'  => $series_id,
				// Index, NOT a price: price_per_kg_gbp stays null on purpose.
				'price_per_kg_gbp'  => null,
				'normalized'        => false,
				'is_index'          => true,
				'index_value'       => (float) $obs['value'],
				'index_series_id'   => $series_id,
				'is_public_reference' => true,
				'is_verified_live'  => false,
				'metadata'          => array( 'label' => $label, 'observation_date' => $obs['date'] ?? '' ),
			) );
		}

		if ( empty( $items ) ) {
			return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'FRED returned no usable series. ' . implode( ' ', $errors ) );
		}

		$status = empty( $errors ) ? IH_Material_Price_Result::STATUS_SUCCESS : IH_Material_Price_Result::STATUS_PARTIAL;
		return IH_Material_Price_Result::make(
			$this->key(),
			$this->source_type(),
			$status,
			$items,
			$errors,
			'FRED monthly index: ' . count( $items ) . ' series.'
		);
	}
}
