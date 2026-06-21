<?php
/**
 * class-ih-trading-economics-provider.php
 *
 * Daily PUBLIC market reference for Polypropylene, Polyethylene and PVC from
 * Trading Economics. Commodity figures may be quoted in CNY/tonne (etc.) and are
 * converted to GBP/kg before storing.
 *
 * WHY source_type = public_market_reference, NOT live_feed:
 *   Trading Economics commodity data is an INDICATIVE / delayed public market
 *   reference, not a contractual supplier quote and not a licensed real-time
 *   feed. Labelling it "Live" would mislead users into trusting it as a firm
 *   price. Only the licensed live feed (IH_PRICE_FEED_URL) may be "Live".
 *
 * The free/guest tier (guest:guest) is heavily rate-limited and may not expose
 * these series cleanly. This provider therefore DEGRADES GRACEFULLY: any
 * non-2xx, parse error or empty payload yields a failed/skipped result (never an
 * exception), and the calculator keeps using manual/CSV/last-known prices.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Trading_Economics_Provider implements IH_Material_Price_Provider {

	/** Commodity name → polymer family mapping we care about. */
	private static function commodities() {
		return array(
			'polypropylene' => array( 'polymer' => 'PP',  'family' => 'Polypropylene' ),
			'polyethylene'  => array( 'polymer' => 'PE',  'family' => 'Polyethylene' ),
			'pvc'           => array( 'polymer' => 'PVC', 'family' => 'PVC' ),
		);
	}

	public function key()         { return 'trading_economics'; }
	public function name()        { return 'Trading Economics'; }
	public function source_type() { return IH_Material_Price_Config::SRC_PUBLIC_MARKET_REFERENCE; }
	public function enabled()     { return IH_Material_Price_Config::trading_economics_enabled(); }

	public function check() {
		if ( ! $this->enabled() ) {
			return IH_Material_Price_Result::skipped( $this->key(), $this->source_type(), 'Trading Economics provider disabled.' );
		}

		$base = rtrim( IH_Material_Price_Config::trading_economics_base_url(), '/' );
		$key  = IH_Material_Price_Config::trading_economics_key();
		// Public commodities endpoint. guest:guest is rate-limited & may 401/409.
		$url  = $base . '/markets/commodities?c=' . rawurlencode( $key ) . '&f=json';

		$res = IH_Material_Price_Utils::http_get( $url, array( 'timeout' => 15 ) );
		if ( ! $res['ok'] ) {
			// Graceful degradation: record the failure, do not break the run.
			return IH_Material_Price_Result::failed(
				$this->key(),
				$this->source_type(),
				'Trading Economics not cleanly accessible (HTTP ' . $res['code'] . '). Falling back to manual/CSV.'
			);
		}

		$data = json_decode( $res['body'], true );
		if ( ! is_array( $data ) ) {
			return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'Trading Economics returned non-JSON payload.' );
		}

		$wanted = self::commodities();
		$items  = array();
		$errors = array();

		foreach ( $data as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$name = strtolower( (string) ( $row['Category'] ?? $row['Symbol'] ?? $row['Name'] ?? '' ) );
			$match = null;
			foreach ( $wanted as $needle => $info ) {
				if ( false !== strpos( $name, $needle ) ) {
					$match = $info;
					break;
				}
			}
			if ( ! $match ) {
				continue;
			}
			$price    = isset( $row['Last'] ) ? (float) $row['Last'] : ( isset( $row['Price'] ) ? (float) $row['Price'] : 0 );
			$currency = strtoupper( (string) ( $row['Currency'] ?? 'CNY' ) );
			$unit     = strtolower( (string) ( $row['unit'] ?? $row['Unit'] ?? 'tonne' ) );
			if ( $price <= 0 ) {
				continue;
			}
			$gbp = IH_Material_Price_Utils::to_gbp_per_kg( $price, $currency, $unit );
			if ( null === $gbp ) {
				$errors[] = 'No FX/unit conversion for ' . $match['polymer'] . ' (' . $currency . '/' . $unit . ') — stored as reference only.';
			}
			$items[] = IH_Material_Price_Result::item( array(
				'material_family'    => $match['family'],
				'polymer'            => $match['polymer'],
				'region'             => IH_Material_Price_Config::default_region(),
				'source_type'        => $this->source_type(),
				'source_name'        => $this->name(),
				'source_reference'   => 'TE:' . ( $row['Symbol'] ?? $match['polymer'] ),
				'original_price'     => $price,
				'original_currency'  => $currency,
				'original_unit'      => $unit,
				'price_per_kg_gbp'   => $gbp,
				'normalized'         => ( null !== $gbp ),
				'is_public_reference' => true,
				'is_verified_live'   => false, // never live — see header comment
			) );
		}

		if ( empty( $items ) ) {
			return IH_Material_Price_Result::failed( $this->key(), $this->source_type(), 'No matching polymer commodities in Trading Economics response.' );
		}

		$status = empty( $errors ) ? IH_Material_Price_Result::STATUS_SUCCESS : IH_Material_Price_Result::STATUS_PARTIAL;
		return IH_Material_Price_Result::make(
			$this->key(),
			$this->source_type(),
			$status,
			$items,
			$errors,
			'Trading Economics public reference: ' . count( $items ) . ' polymers.'
		);
	}
}
