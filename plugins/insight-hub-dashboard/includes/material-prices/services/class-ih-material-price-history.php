<?php
/**
 * class-ih-material-price-history.php — append-only audit writer for the
 * EXISTING {prefix}ih_material_price_history table (extended with metadata_json).
 *
 * Every price-affecting change (scheduled import, manual override, CSV import,
 * reference application) is logged here so the admin history view shows a full
 * trail. metadata_json carries structured context (source_type, provider, etc.)
 * without changing the existing column contract.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Material_Price_History {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'ih_material_price_history';
	}

	/**
	 * Log a price change.
	 *
	 * @param int        $material_id
	 * @param string     $field
	 * @param float|null $old
	 * @param float|null $new
	 * @param string     $source
	 * @param string     $reason
	 * @param array      $metadata  structured extras → metadata_json
	 */
	public static function log( $material_id, $field, $old, $new, $source, $reason = '', array $metadata = array() ) {
		global $wpdb;
		$data = array(
			'material_id'      => (int) $material_id,
			'field'            => substr( (string) $field, 0, 40 ),
			'old_price_per_kg' => is_null( $old ) ? null : (float) $old,
			'new_price_per_kg' => is_null( $new ) ? null : (float) $new,
			'currency'         => 'GBP',
			'source'           => substr( (string) $source, 0, 120 ),
			'changed_by'       => function_exists( 'get_current_user_id' ) ? ( get_current_user_id() ?: null ) : null,
			'changed_at'       => current_time( 'mysql', true ),
			'reason'           => substr( (string) $reason, 0, 255 ),
		);
		// metadata_json is added additively by migration; only write it if present.
		if ( self::has_metadata_column() ) {
			$data['metadata_json'] = wp_json_encode( $metadata );
		}
		$wpdb->insert( self::table(), $data );
		return (int) $wpdb->insert_id;
	}

	private static function has_metadata_column() {
		static $has = null;
		if ( null !== $has ) {
			return $has;
		}
		global $wpdb;
		$tbl = self::table();
		$has = (bool) $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `{$tbl}` LIKE %s", 'metadata_json' ) );
		return $has;
	}

	public static function recent( $material_id = 0, $limit = 200 ) {
		global $wpdb;
		$tbl   = self::table();
		$limit = max( 1, min( 500, (int) $limit ) );
		if ( $material_id > 0 ) {
			return $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$tbl} WHERE material_id = %d ORDER BY changed_at DESC LIMIT %d",
				(int) $material_id, $limit
			) );
		}
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tbl} ORDER BY changed_at DESC LIMIT %d", $limit ) );
	}
}
