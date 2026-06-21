<?php
/**
 * bootstrap.php — wiring for the 24-hour material price CHECKING / REFERENCE
 * system. Required once from the main plugin file AFTER ih-material-pricing.php.
 *
 * Responsibilities:
 *   • require every class in includes/material-prices/
 *   • register a custom cron schedule honouring IH_PRICE_CHECK_INTERVAL_HOURS
 *   • schedule ih_material_price_check (first fire at the next local 02:00)
 *   • reconcile the legacy daily ih_import_resin_prices event (the licensed feed
 *     is now ONE provider inside IH_Material_Price_Check::run())
 *   • register REST routes + the admin panel
 *   • expose ih_material_prices_install_tables() for the migration runner
 *
 * WHY public references are never "Live": see class-ih-material-price-utils.php
 * (get_price_badge) and class-ih-material-price-selection.php. A licensed feed
 * (IH_PRICE_FEED_URL) verified at import time is the ONLY "Live" source.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---- requires (order matters: config + utils + result first) ---- */
require_once __DIR__ . '/class-ih-material-price-config.php';
require_once __DIR__ . '/class-ih-material-price-utils.php';
require_once __DIR__ . '/class-ih-material-price-result.php';
require_once __DIR__ . '/interface-ih-material-price-provider.php';
require_once __DIR__ . '/services/class-ih-material-price-normalisation.php';
require_once __DIR__ . '/services/class-ih-material-price-history.php';
require_once __DIR__ . '/services/class-ih-material-price-selection.php';
require_once __DIR__ . '/services/class-ih-material-price-csv.php';
require_once __DIR__ . '/providers/class-ih-licensed-live-feed-provider.php';
require_once __DIR__ . '/providers/class-ih-trading-economics-provider.php';
require_once __DIR__ . '/providers/class-ih-fred-provider.php';
require_once __DIR__ . '/providers/class-ih-plasticportal-provider.php';
require_once __DIR__ . '/providers/class-ih-plasticker-provider.php';
require_once __DIR__ . '/providers/class-ih-rss-news-provider.php';
require_once __DIR__ . '/services/class-ih-material-price-check.php';
require_once __DIR__ . '/routes/class-ih-material-price-routes.php';
require_once __DIR__ . '/class-ih-material-price-admin.php';

/* ============================================================
   CRON: custom interval + scheduling
   ============================================================ */
add_filter( 'cron_schedules', function ( $schedules ) {
	$h = IH_Material_Price_Config::interval_hours();
	$schedules['ih_price_check_interval'] = array(
		'interval' => $h * HOUR_IN_SECONDS,
		'display'  => sprintf( 'Every %d hours (Insight Hub material price check)', $h ),
	);
	return $schedules;
} );

add_action( 'ih_material_price_check', array( 'IH_Material_Price_Check', 'run' ) );

/** Next local 02:00 as a UTC timestamp (quiet hours = low API contention). */
function ih_material_price_next_2am() {
	$tz   = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
	$now  = new DateTime( 'now', $tz );
	$next = new DateTime( 'today 02:00', $tz );
	if ( $next <= $now ) {
		$next->modify( '+1 day' );
	}
	return $next->getTimestamp();
}

function ih_material_prices_setup_cron() {
	// Reconcile: retire the legacy daily licensed-feed import. It is now the
	// IH_Licensed_Live_Feed_Provider inside the single coherent daily run().
	$legacy = wp_next_scheduled( 'ih_import_resin_prices' );
	if ( $legacy ) {
		wp_unschedule_event( $legacy, 'ih_import_resin_prices' );
	}
	if ( ! wp_next_scheduled( 'ih_material_price_check' ) ) {
		wp_schedule_event( ih_material_price_next_2am(), 'ih_price_check_interval', 'ih_material_price_check' );
	}
}
add_action( 'init', 'ih_material_prices_setup_cron' );

/* ---- REST + admin ---- */
IH_Material_Price_Routes::init();
if ( is_admin() ) {
	IH_Material_Price_Admin::init();
}

/* ============================================================
   SCHEMA — additive + idempotent. Called from ih_run_migrations().
   ============================================================ */
function ih_material_prices_install_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	$p       = $wpdb->prefix;

	$sources    = $p . 'ih_material_price_sources';
	$checks      = $p . 'ih_material_price_checks';
	$references  = $p . 'ih_material_reference_prices';
	$materials   = $p . 'ih_materials';
	$history     = $p . 'ih_material_price_history';

	dbDelta( "CREATE TABLE {$sources} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		source_key      VARCHAR(60)  NOT NULL DEFAULT '',
		source_name     VARCHAR(120) NOT NULL DEFAULT '',
		source_type     VARCHAR(60)  NOT NULL DEFAULT '',
		enabled         TINYINT(1)   NOT NULL DEFAULT 0,
		base_url        VARCHAR(255) NOT NULL DEFAULT '',
		last_status     VARCHAR(20)  NOT NULL DEFAULT '',
		last_error      TEXT NULL,
		last_checked_at DATETIME NULL,
		last_success_at DATETIME NULL,
		reference_count INT UNSIGNED NOT NULL DEFAULT 0,
		created_at      DATETIME NULL,
		updated_at      DATETIME NULL,
		PRIMARY KEY (id),
		UNIQUE KEY source_key (source_key)
	) {$charset};" );

	dbDelta( "CREATE TABLE {$checks} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		source_key   VARCHAR(60) NOT NULL DEFAULT '',
		source_type  VARCHAR(60) NOT NULL DEFAULT '',
		status       VARCHAR(20) NOT NULL DEFAULT '',
		items_count  INT UNSIGNED NOT NULL DEFAULT 0,
		errors_count INT UNSIGNED NOT NULL DEFAULT 0,
		message      TEXT NULL,
		raw_summary  TEXT NULL,
		started_at   DATETIME NULL,
		finished_at  DATETIME NULL,
		created_at   DATETIME NULL,
		PRIMARY KEY (id),
		KEY source_key (source_key),
		KEY created_at (created_at)
	) {$charset};" );

	dbDelta( "CREATE TABLE {$references} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		source_key        VARCHAR(60)  NOT NULL DEFAULT '',
		source_type       VARCHAR(60)  NOT NULL DEFAULT '',
		source_name       VARCHAR(120) NOT NULL DEFAULT '',
		source_reference  VARCHAR(255) NOT NULL DEFAULT '',
		material_family   VARCHAR(60)  NOT NULL DEFAULT '',
		polymer           VARCHAR(40)  NOT NULL DEFAULT '',
		grade_code        VARCHAR(60)  NOT NULL DEFAULT '',
		region            VARCHAR(40)  NOT NULL DEFAULT '',
		original_price    DECIMAL(14,4) NULL,
		original_currency CHAR(3) NOT NULL DEFAULT '',
		original_unit     VARCHAR(12) NOT NULL DEFAULT '',
		price_per_kg_gbp  DECIMAL(10,4) NULL,
		is_normalized     TINYINT(1) NOT NULL DEFAULT 0,
		is_index          TINYINT(1) NOT NULL DEFAULT 0,
		index_value       DECIMAL(14,4) NULL,
		index_series_id   VARCHAR(60) NOT NULL DEFAULT '',
		is_verified_live  TINYINT(1) NOT NULL DEFAULT 0,
		is_public_reference TINYINT(1) NOT NULL DEFAULT 0,
		metadata_json     LONGTEXT NULL,
		checked_at        DATETIME NULL,
		created_at        DATETIME NULL,
		PRIMARY KEY (id),
		KEY source_type (source_type),
		KEY grade_code (grade_code),
		KEY checked_at (checked_at)
	) {$charset};" );

	/* ---- extend ih_materials additively ---- */
	$has_col = function ( $table, $col ) use ( $wpdb ) {
		return (bool) $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `{$table}` LIKE %s", $col ) );
	};
	$table_exists = function ( $table ) use ( $wpdb ) {
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	};

	if ( $table_exists( $materials ) ) {
		$cols = array(
			'source_type'        => "VARCHAR(60) NOT NULL DEFAULT ''",
			'source_name'        => "VARCHAR(120) NOT NULL DEFAULT ''",
			'last_checked_at'    => 'DATETIME NULL',
			'last_imported_at'   => 'DATETIME NULL',
			'price_status'       => "VARCHAR(30) NOT NULL DEFAULT ''",
			'is_verified_live'   => 'TINYINT(1) NOT NULL DEFAULT 0',
			'is_public_reference'=> 'TINYINT(1) NOT NULL DEFAULT 0',
			// is_manual_override + override_reason + source_reference already exist
			// in the base schema; guarded here in case of older installs.
			'is_manual_override' => 'TINYINT(1) NOT NULL DEFAULT 0',
			'override_reason'    => "VARCHAR(255) NOT NULL DEFAULT ''",
		);
		foreach ( $cols as $col => $def ) {
			if ( ! $has_col( $materials, $col ) ) {
				$wpdb->query( "ALTER TABLE `{$materials}` ADD COLUMN `{$col}` {$def}" );
			}
		}
	}

	/* ---- reconcile history: add metadata_json additively ---- */
	if ( $table_exists( $history ) && ! $has_col( $history, 'metadata_json' ) ) {
		$wpdb->query( "ALTER TABLE `{$history}` ADD COLUMN `metadata_json` LONGTEXT NULL" );
	}
}
