<?php
/**
 * class-ih-material-price-csv.php — supplier CSV importer.
 *
 * CSV-imported prices rank ABOVE public references in the selection order and,
 * when the operator marks them as supplier prices, are treated as supplier
 * quotes. Every change is written to the price history.
 *
 * Expected CSV columns (header row, case-insensitive):
 *   grade_code, price_per_kg, currency, unit, supplier, region, source_reference
 * `price_per_kg` may instead be `price` with currency/unit for conversion.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IH_Material_Price_CSV {

	/**
	 * Import rows from a raw CSV string.
	 *
	 * @param string $csv
	 * @param bool   $as_supplier  treat imported prices as supplier (quote) prices
	 * @return array { imported, skipped, errors[] }
	 */
	public static function import_string( $csv, $as_supplier = true ) {
		$out = array( 'imported' => 0, 'skipped' => 0, 'errors' => array() );
		$csv = (string) $csv;
		if ( '' === trim( $csv ) ) {
			$out['errors'][] = 'Empty CSV.';
			return $out;
		}

		$lines = preg_split( '/\r\n|\r|\n/', trim( $csv ) );
		if ( count( $lines ) < 2 ) {
			$out['errors'][] = 'CSV needs a header row and at least one data row.';
			return $out;
		}

		$header = array_map( function ( $h ) { return strtolower( trim( $h ) ); }, str_getcsv( array_shift( $lines ) ) );
		$idx    = array_flip( $header );

		global $wpdb;
		$tbl = $wpdb->prefix . 'ih_materials';

		foreach ( $lines as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}
			$cols = str_getcsv( $line );
			$get  = function ( $name ) use ( $cols, $idx ) {
				return isset( $idx[ $name ] ) && isset( $cols[ $idx[ $name ] ] ) ? trim( $cols[ $idx[ $name ] ] ) : '';
			};

			$grade_code = sanitize_text_field( $get( 'grade_code' ) );
			if ( '' === $grade_code ) {
				$out['skipped']++;
				$out['errors'][] = 'Row skipped: missing grade_code.';
				continue;
			}

			$currency = strtoupper( $get( 'currency' ) ?: IH_Material_Price_Config::default_currency() );
			$unit     = strtolower( $get( 'unit' ) ?: 'kg' );

			$per_kg = null;
			if ( '' !== $get( 'price_per_kg' ) && is_numeric( $get( 'price_per_kg' ) ) ) {
				$per_kg = ( 'GBP' === $currency )
					? (float) $get( 'price_per_kg' )
					: IH_Material_Price_Utils::to_gbp_per_kg( (float) $get( 'price_per_kg' ), $currency, 'kg' );
			} elseif ( '' !== $get( 'price' ) && is_numeric( $get( 'price' ) ) ) {
				$per_kg = IH_Material_Price_Utils::to_gbp_per_kg( (float) $get( 'price' ), $currency, $unit );
			}

			if ( null === $per_kg || $per_kg <= 0 || $per_kg > 500 ) {
				$out['skipped']++;
				$out['errors'][] = "Row '{$grade_code}' skipped: price could not be normalised to a plausible GBP/kg.";
				continue;
			}
			$per_kg = round( $per_kg, 4 );

			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tbl} WHERE grade_code = %s LIMIT 1", $grade_code ) );
			$now      = current_time( 'mysql', true );
			$supplier = sanitize_text_field( $get( 'supplier' ) );
			$region   = sanitize_text_field( $get( 'region' ) );
			$ref      = sanitize_text_field( $get( 'source_reference' ) ?: 'CSV import' );

			$field = $as_supplier ? 'supplier_price_per_kg' : 'market_price_per_kg';

			if ( $existing ) {
				$old = $existing->{$field};
				$data = array(
					$field             => $per_kg,
					'source_type'      => IH_Material_Price_Config::SRC_CSV_IMPORTED,
					'source_name'      => $supplier ?: 'CSV import',
					'source_reference' => $ref,
					'price_status'     => 'csv',
					'is_public_reference' => 0,
					'last_imported_at' => $now,
					'last_updated'     => $now,
				);
				if ( $supplier ) { $data['supplier'] = $supplier; }
				if ( $region )   { $data['region'] = $region; }
				$wpdb->update( $tbl, $data, array( 'id' => (int) $existing->id ) );
				$existing->{$field} = $per_kg;
				if ( class_exists( 'IH_Material_Pricing' ) ) {
					$wpdb->update( $tbl, array( 'quote_price_per_kg' => IH_Material_Pricing::compute_quote( $existing ) ), array( 'id' => (int) $existing->id ) );
				}
				IH_Material_Price_History::log( (int) $existing->id, $field, $old, $per_kg, 'CSV import', 'Supplier CSV import', array( 'source_type' => IH_Material_Price_Config::SRC_CSV_IMPORTED, 'reference' => $ref ) );
			} else {
				$wpdb->insert( $tbl, array(
					'material_family'  => '',
					'material_name'    => $grade_code,
					'grade_code'       => $grade_code,
					'supplier'         => $supplier,
					'region'           => $region,
					'currency'         => 'GBP',
					'unit'             => 'kg',
					$field             => $per_kg,
					'source_type'      => IH_Material_Price_Config::SRC_CSV_IMPORTED,
					'source_name'      => $supplier ?: 'CSV import',
					'source_reference' => $ref,
					'price_source'     => $supplier ?: 'CSV import',
					'price_status'     => 'csv',
					'last_imported_at' => $now,
					'last_updated'     => $now,
					'active'           => 1,
				) );
				$id = (int) $wpdb->insert_id;
				if ( $id ) {
					IH_Material_Price_History::log( $id, $field, null, $per_kg, 'CSV import', 'Supplier CSV import (new row)', array( 'source_type' => IH_Material_Price_Config::SRC_CSV_IMPORTED, 'reference' => $ref ) );
				}
			}
			$out['imported']++;
		}

		// Imported prices change derived quotes — clear the REST cache.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ih_price_%' OR option_name LIKE '_transient_timeout_ih_price_%'" );

		return $out;
	}
}
