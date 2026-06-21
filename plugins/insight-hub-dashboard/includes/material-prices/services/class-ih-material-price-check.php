<?php
/**
 * class-ih-material-price-check.php — the 24-hour market-check ORCHESTRATOR.
 *
 * PHP reproduction of the spec's runMaterialPriceCheck(). It walks the providers
 * in the documented source order, runs each behind a try/catch so one failure
 * can never break the run, normalises every returned item, and persists:
 *   • a per-source check record  → {prefix}ih_material_price_checks
 *   • each reference item        → {prefix}ih_material_reference_prices
 *   • the source registry row    → {prefix}ih_material_price_sources
 *
 * It NEVER auto-overwrites supplier-CSV or manual prices. Only VERIFIED licensed
 * live_feed items are applied to the active {prefix}ih_materials quote price;
 * public references are stored for display/estimate-only use (gated by the admin
 * option in the selection logic). Previous valid prices stay active on failure.
 *
 * 24-HOUR MARKET CHECK SOURCE ORDER:
 *   1. Trading Economics (public reference)
 *   2. FRED (monthly index)
 *   3. PlasticPortal (delayed public reference)
 *   4. Plasticker/Recybase (marketplace reference)
 *   5. RSS/news (alerts only)
 *   + the licensed live feed (the one source that can be "Live")
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Material_Price_Check {

	const RUN_LOCK = 'ih_material_price_run_lock';

	/* ---- table accessors ---- */
	public static function t_sources()    { global $wpdb; return $wpdb->prefix . 'ih_material_price_sources'; }
	public static function t_checks()     { global $wpdb; return $wpdb->prefix . 'ih_material_price_checks'; }
	public static function t_references() { global $wpdb; return $wpdb->prefix . 'ih_material_reference_prices'; }
	public static function t_materials()  { global $wpdb; return $wpdb->prefix . 'ih_materials'; }

	/**
	 * Instantiate the providers in source order. Filterable so deployments can
	 * add their own provider implementing IH_Material_Price_Provider.
	 *
	 * @return IH_Material_Price_Provider[]
	 */
	public static function providers() {
		$providers = array(
			new IH_Licensed_Live_Feed_Provider(),  // the only one that can be "Live"
			new IH_Trading_Economics_Provider(),
			new IH_FRED_Provider(),
			new IH_PlasticPortal_Provider(),
			new IH_Plasticker_Provider(),
			new IH_RSS_News_Provider(),
		);
		$filtered = apply_filters( 'ih_material_price_providers', $providers );
		return is_array( $filtered ) ? $filtered : $providers;
	}

	/**
	 * Run the full check. Returns a summary array and stores it for the admin UI.
	 * Never throws.
	 *
	 * @param string $trigger 'cron' | 'manual' | 'cli'
	 */
	public static function run( $trigger = 'cron' ) {
		// Prevent overlapping runs (cron + a manual Check-now firing together).
		if ( get_transient( self::RUN_LOCK ) ) {
			return array( 'ok' => false, 'message' => 'A price check is already running.' );
		}
		set_transient( self::RUN_LOCK, 1, 5 * MINUTE_IN_SECONDS );

		$started = current_time( 'mysql', true );
		$results = array();
		$summary = array(
			'trigger'           => $trigger,
			'started_at'        => $started,
			'sources'           => array(),
			'reference_count'   => 0,
			'live_applied'      => 0,
			'errors'            => array(),
		);

		try {
			foreach ( self::providers() as $provider ) {
				if ( ! $provider instanceof IH_Material_Price_Provider ) {
					continue;
				}
				$key = $provider->key();
				try {
					if ( ! $provider->enabled() ) {
						$result = IH_Material_Price_Result::skipped( $key, $provider->source_type(), $provider->name() . ' is disabled.' );
					} else {
						$result = $provider->check();
						if ( ! is_array( $result ) || ! isset( $result['status'] ) ) {
							$result = IH_Material_Price_Result::failed( $key, $provider->source_type(), 'Provider returned an invalid result.' );
						}
					}
				} catch ( \Throwable $e ) {
					// FALLBACK: a thrown provider becomes a failed check, never a fatal.
					$result = IH_Material_Price_Result::failed( $key, $provider->source_type(), 'Exception: ' . $e->getMessage() );
				}

				// Normalise items defensively (storage authority).
				$result['items'] = IH_Material_Price_Normalisation::normalise_all( $result['items'] );

				self::persist_result( $provider, $result, $started );

				$ref_count = count( $result['items'] );
				$summary['reference_count'] += $ref_count;
				$summary['sources'][ $key ]  = array(
					'name'        => $provider->name(),
					'source_type' => $provider->source_type(),
					'status'      => $result['status'],
					'items'       => $ref_count,
					'errors'      => $result['errors'],
					'summary'     => $result['raw_summary'],
				);
				if ( ! empty( $result['errors'] ) ) {
					foreach ( $result['errors'] as $err ) {
						$summary['errors'][] = $provider->name() . ': ' . $err;
					}
				}

				// Apply ONLY verified live-feed items to the active quote prices.
				if ( IH_Material_Price_Config::SRC_LIVE_FEED === $result['source_type'] ) {
					$summary['live_applied'] += self::apply_live_feed_items( $result['items'] );
				} else {
					// For reference sources, just stamp last_checked_at on matches.
					self::touch_last_checked( $result['items'] );
				}

				$results[] = $result;
			}

			$summary['finished_at'] = current_time( 'mysql', true );
			$summary['ok']          = true;
			update_option( IH_Material_Price_Config::OPTION_LAST_RUN, $summary, false );

			// The licensed feed application + reference touches change derived prices.
			self::bust_price_cache();

		} finally {
			delete_transient( self::RUN_LOCK );
		}

		return $summary;
	}

	/* ============================================================
	   PERSISTENCE
	   ============================================================ */
	private static function persist_result( IH_Material_Price_Provider $provider, array $result, $started ) {
		global $wpdb;

		// 1) check record
		$wpdb->insert( self::t_checks(), array(
			'source_key'   => $provider->key(),
			'source_type'  => $result['source_type'],
			'status'       => $result['status'],
			'items_count'  => count( $result['items'] ),
			'errors_count' => count( $result['errors'] ),
			'message'      => IH_Material_Price_Utils::summary( implode( ' | ', $result['errors'] ), 1000 ),
			'raw_summary'  => $result['raw_summary'],
			'started_at'   => $started,
			'finished_at'  => current_time( 'mysql', true ),
			'created_at'   => current_time( 'mysql', true ),
		) );

		// 2) reference items
		foreach ( $result['items'] as $item ) {
			self::store_reference_item( $item );
		}

		// 3) source registry upsert
		self::upsert_source( $provider, $result );
	}

	private static function store_reference_item( array $item ) {
		global $wpdb;
		$wpdb->insert( self::t_references(), array(
			'source_key'        => substr( (string) ( $item['source_type'] ?? '' ), 0, 60 ),
			'source_type'       => substr( (string) ( $item['source_type'] ?? '' ), 0, 60 ),
			'source_name'       => substr( (string) ( $item['source_name'] ?? '' ), 0, 120 ),
			'source_reference'  => substr( (string) ( $item['source_reference'] ?? '' ), 0, 255 ),
			'material_family'   => substr( (string) ( $item['material_family'] ?? '' ), 0, 60 ),
			'polymer'           => substr( (string) ( $item['polymer'] ?? '' ), 0, 40 ),
			'grade_code'        => substr( (string) ( $item['grade_code'] ?? '' ), 0, 60 ),
			'region'            => substr( (string) ( $item['region'] ?? '' ), 0, 40 ),
			'original_price'    => is_numeric( $item['original_price'] ?? null ) ? (float) $item['original_price'] : null,
			'original_currency' => substr( (string) ( $item['original_currency'] ?? '' ), 0, 3 ),
			'original_unit'     => substr( (string) ( $item['original_unit'] ?? '' ), 0, 12 ),
			'price_per_kg_gbp'  => is_numeric( $item['price_per_kg_gbp'] ?? null ) ? (float) $item['price_per_kg_gbp'] : null,
			'is_normalized'     => ! empty( $item['normalized'] ) ? 1 : 0,
			'is_index'          => ! empty( $item['is_index'] ) ? 1 : 0,
			'index_value'       => is_numeric( $item['index_value'] ?? null ) ? (float) $item['index_value'] : null,
			'index_series_id'   => substr( (string) ( $item['index_series_id'] ?? '' ), 0, 60 ),
			'is_verified_live'  => ! empty( $item['is_verified_live'] ) ? 1 : 0,
			'is_public_reference' => ! empty( $item['is_public_reference'] ) ? 1 : 0,
			'metadata_json'     => wp_json_encode( $item['metadata'] ?? array() ),
			'checked_at'        => current_time( 'mysql', true ),
			'created_at'        => current_time( 'mysql', true ),
		) );
	}

	private static function upsert_source( IH_Material_Price_Provider $provider, array $result ) {
		global $wpdb;
		$tbl  = self::t_sources();
		$key  = $provider->key();
		$now  = current_time( 'mysql', true );
		$ok   = ( IH_Material_Price_Result::STATUS_SUCCESS === $result['status'] || IH_Material_Price_Result::STATUS_PARTIAL === $result['status'] );
		$row  = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$tbl} WHERE source_key = %s LIMIT 1", $key ) );
		$data = array(
			'source_key'       => $key,
			'source_name'      => substr( $provider->name(), 0, 120 ),
			'source_type'      => $provider->source_type(),
			'enabled'          => $provider->enabled() ? 1 : 0,
			'last_status'      => $result['status'],
			'last_error'       => IH_Material_Price_Utils::summary( implode( ' | ', $result['errors'] ), 1000 ),
			'last_checked_at'  => $now,
			'reference_count'  => count( $result['items'] ),
			'updated_at'       => $now,
		);
		if ( $ok ) {
			$data['last_success_at'] = $now;
		}
		if ( $row ) {
			$wpdb->update( $tbl, $data, array( 'id' => (int) $row->id ) );
		} else {
			$data['created_at'] = $now;
			$wpdb->insert( $tbl, $data );
		}
	}

	/* ============================================================
	   APPLY TO ACTIVE MATERIALS
	   ============================================================ */

	/** Apply verified licensed-feed items to ih_materials (recompute quote, log). */
	private static function apply_live_feed_items( array $items ) {
		global $wpdb;
		$tbl     = self::t_materials();
		$applied = 0;
		foreach ( $items as $item ) {
			if ( empty( $item['normalized'] ) || empty( $item['is_verified_live'] ) ) {
				continue;
			}
			$gbp = (float) $item['price_per_kg_gbp'];
			if ( $gbp <= 0 ) {
				continue;
			}
			$existing = self::find_material_for_item( $item );
			if ( ! $existing ) {
				continue;
			}
			$old   = $existing->market_price_per_kg;
			$now   = current_time( 'mysql', true );
			$wpdb->update( $tbl, array(
				'market_price_per_kg' => $gbp,
				'is_live_price'       => 1,
				'is_verified_live'    => 1,
				'is_public_reference' => 0,
				'source_type'         => IH_Material_Price_Config::SRC_LIVE_FEED,
				'source_name'         => substr( (string) $item['source_name'], 0, 120 ),
				'price_source'        => substr( (string) $item['source_name'], 0, 120 ),
				'source_reference'    => substr( (string) $item['source_reference'], 0, 255 ),
				'price_status'        => 'live',
				'last_checked_at'     => $now,
				'last_imported_at'    => $now,
				'last_updated'        => $now,
			), array( 'id' => (int) $existing->id ) );

			// recompute & persist the quote so reads stay cheap
			$existing->market_price_per_kg = $gbp;
			if ( class_exists( 'IH_Material_Pricing' ) ) {
				$quote = IH_Material_Pricing::compute_quote( $existing );
				$wpdb->update( $tbl, array( 'quote_price_per_kg' => $quote ), array( 'id' => (int) $existing->id ) );
			}
			if ( (float) $old !== $gbp ) {
				IH_Material_Price_History::log( (int) $existing->id, 'market_price_per_kg', $old, $gbp, $item['source_name'], 'Verified live feed import', array(
					'source_type' => IH_Material_Price_Config::SRC_LIVE_FEED,
					'reference'   => $item['source_reference'],
				) );
			}
			$applied++;
		}
		return $applied;
	}

	/** Stamp last_checked_at on materials matching a reference item (no price change). */
	private static function touch_last_checked( array $items ) {
		global $wpdb;
		$tbl = self::t_materials();
		$now = current_time( 'mysql', true );
		foreach ( $items as $item ) {
			$existing = self::find_material_for_item( $item );
			if ( $existing ) {
				$wpdb->update( $tbl, array( 'last_checked_at' => $now ), array( 'id' => (int) $existing->id ) );
			}
		}
	}

	private static function find_material_for_item( array $item ) {
		global $wpdb;
		$tbl = self::t_materials();
		if ( ! empty( $item['grade_code'] ) ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE grade_code = %s AND active = 1 LIMIT 1", $item['grade_code'] ) );
			if ( $row ) {
				return $row;
			}
		}
		if ( ! empty( $item['material_family'] ) ) {
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE material_family = %s AND active = 1 ORDER BY id LIMIT 1", $item['material_family'] ) );
		}
		return null;
	}

	private static function bust_price_cache() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ih_price_%' OR option_name LIKE '_transient_timeout_ih_price_%'" );
	}

	/* ============================================================
	   READ HELPERS for the admin UI / REST
	   ============================================================ */
	public static function last_run() {
		return get_option( IH_Material_Price_Config::OPTION_LAST_RUN, array() );
	}

	public static function sources() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM " . self::t_sources() . " ORDER BY source_type, source_key" );
	}

	public static function recent_checks( $limit = 50 ) {
		global $wpdb;
		$limit = max( 1, min( 200, (int) $limit ) );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . self::t_checks() . " ORDER BY id DESC LIMIT %d", $limit ) );
	}

	public static function recent_references( $limit = 50, $include_news = true ) {
		global $wpdb;
		$tbl   = self::t_references();
		$limit = max( 1, min( 200, (int) $limit ) );
		if ( $include_news ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} ORDER BY id DESC LIMIT %d", $limit ) );
		}
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE source_type != %s ORDER BY id DESC LIMIT %d", IH_Material_Price_Config::SRC_NEWS_REFERENCE, $limit ) );
	}

	public static function recent_news( $limit = 15 ) {
		global $wpdb;
		$tbl   = self::t_references();
		$limit = max( 1, min( 50, (int) $limit ) );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE source_type = %s ORDER BY id DESC LIMIT %d", IH_Material_Price_Config::SRC_NEWS_REFERENCE, $limit ) );
	}
}
