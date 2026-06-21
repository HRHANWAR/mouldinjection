<?php
/**
 * Plugin Name: Insight Hub Dashboard
 * Description: Complete Admin Dashboard — Users, Machines, Tools, Messages with full DB backend.
 * Version:     2.7.27
 * Author:      Naveed
 */
defined( 'ABSPATH' ) || exit;

/* ── Increase upload limits for chat file sharing ── */
@ini_set( 'upload_max_filesize', '20M' );
@ini_set( 'post_max_size',       '25M' );
@ini_set( 'memory_limit',        '256M' );

define( 'IH_VERSION', '2.7.27' );
define( 'IH_DIR',     plugin_dir_path( __FILE__ ) );
define( 'IH_URL',     plugin_dir_url( __FILE__ ) );
// Absolute path to this main plugin file. Used by register_activation_hook()
// callers in includes/ (e.g. IH_Material_Pricing). Defined before those requires.
define( 'IH_PLUGIN_FILE', __FILE__ );
require_once IH_DIR . 'includes/ih-site-nav.php';
require_once IH_DIR . 'includes/ih-cache-compat.php';
require_once IH_DIR . 'includes/chat-endpoints.php';
require_once IH_DIR . 'includes/ih-request-messaging.php';
// Material pricing backend + admin screen. Pricing FIRST: the admin screen
// reuses IH_Material_Pricing::compute_quote(). Table creation is wired into
// ih_run_migrations() below (the plugin is already active, so the activation
// hook will not re-fire on this install).
require_once IH_DIR . 'includes/ih-material-pricing.php';
require_once IH_DIR . 'includes/pricing-admin.php';
// 24-hour material price CHECKING / REFERENCE system (providers, services,
// REST routes, admin panel, cron). Loads AFTER ih-material-pricing.php because
// it reuses IH_Material_Pricing::compute_quote() and extends its tables.
require_once IH_DIR . 'includes/material-prices/bootstrap.php';
require_once IH_DIR . 'ih-redesign-helpers.php';
// Schema version. Bump this string to trigger ih_run_migrations() on the next load.
define( 'IH_DB_VERSION', '2026062003' );

/* ═══════════════════════════════════════
   DATABASE SETUP
═══════════════════════════════════════ */
register_activation_hook( __FILE__, 'ih_install' );

function ih_install() {
    global $wpdb;
    $c = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE {$wpdb->prefix}ih_user_meta (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        location varchar(255) DEFAULT '',
        phone varchar(60) DEFAULT '',
        company varchar(255) DEFAULT '',
        job_title varchar(255) DEFAULT '',
        address text DEFAULT '',
        website varchar(255) DEFAULT '',
        blocked tinyint(1) DEFAULT 0,
        status varchar(20) DEFAULT 'Approved',
        joined date DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY  user_id (user_id)
    ) $c;");

    dbDelta("CREATE TABLE {$wpdb->prefix}ih_machines (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        owner_id bigint(20) DEFAULT 0,
        title varchar(255) NOT NULL,
        brand varchar(255) DEFAULT '',
        machine_type varchar(60) DEFAULT 'Hydraulic',
        year_manufacture varchar(10) DEFAULT '',
        identical_count int DEFAULT 1,
        clamping_force varchar(60) DEFAULT '',
        shot_size varchar(60) DEFAULT '',
        screw_diameter varchar(60) DEFAULT '',
        max_injection_pressure varchar(60) DEFAULT '',
        tie_bar_spacing varchar(60) DEFAULT '',
        max_mould_height varchar(60) DEFAULT '',
        min_mould_height varchar(60) DEFAULT '',
        clamp_drive_type varchar(80) DEFAULT '',
        toggle_clamp_type varchar(80) DEFAULT '',
        max_part_weight varchar(60) DEFAULT '',
        max_part_dimensions varchar(120) DEFAULT '',
        tolerance varchar(60) DEFAULT '',
        material_grade varchar(255) DEFAULT '',
        materials text NULL,
        materials_abs tinyint(1) DEFAULT 0,
        materials_pp tinyint(1) DEFAULT 0,
        materials_pe tinyint(1) DEFAULT 0,
        materials_pa tinyint(1) DEFAULT 0,
        materials_pc tinyint(1) DEFAULT 0,
        materials_peek tinyint(1) DEFAULT 0,
        engineering_grade varchar(20) DEFAULT 'Yes',
        recycled_materials varchar(20) DEFAULT 'Yes',
        batch_size varchar(80) DEFAULT '',
        min_order_qty varchar(60) DEFAULT '',
        max_monthly_output varchar(80) DEFAULT '',
        avg_cycle_time varchar(60) DEFAULT '',
        operating_hours varchar(60) DEFAULT '',
        utilization varchar(60) DEFAULT '',
        location varchar(255) DEFAULT '',
        automation_level varchar(80) DEFAULT '',
        robot_integration varchar(20) DEFAULT 'No',
        multi_cavity varchar(20) DEFAULT 'No',
        certifications varchar(255) DEFAULT '',
        qc_tools varchar(255) DEFAULT '',
        tolerance_consistency varchar(60) DEFAULT '',
        overmoulding varchar(20) DEFAULT 'No',
        insert_moulding varchar(20) DEFAULT 'No',
        iml varchar(20) DEFAULT 'No',
        gas_assisted varchar(20) DEFAULT 'No',
        thin_wall varchar(20) DEFAULT 'No',
        listing_date date DEFAULT NULL,
        expiry_date date DEFAULT NULL,
        image_1 varchar(500) DEFAULT '',
        image_2 varchar(500) DEFAULT '',
        image_3 varchar(500) DEFAULT '',
        available tinyint(1) DEFAULT 1,
        listing_status varchar(20) DEFAULT 'available',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $c;");

    dbDelta("CREATE TABLE {$wpdb->prefix}ih_tools (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        owner_id bigint(20) DEFAULT 0,
        title varchar(255) NOT NULL,
        part_name varchar(255) DEFAULT '',
        part_dimensions varchar(120) DEFAULT '',
        part_description text DEFAULT '',
        part_weight varchar(60) DEFAULT '',
        num_cavities varchar(60) DEFAULT '',
        owner_name varchar(255) DEFAULT '',
        location varchar(255) DEFAULT '',
        material_grade varchar(120) DEFAULT '',
        colour varchar(120) DEFAULT '',
        cad_file varchar(500) DEFAULT '',
        mould_type varchar(80) DEFAULT 'Multi-Cavity',
        mould_material varchar(80) DEFAULT 'H13 Steel',
        mould_condition varchar(60) DEFAULT 'New',
        num_cavities_spec int DEFAULT 4,
        ejector_type varchar(80) DEFAULT '',
        nozzle_type varchar(80) DEFAULT '',
        annual_volume varchar(80) DEFAULT '',
        cycle_time varchar(60) DEFAULT '',
        min_order_qty varchar(60) DEFAULT '',
        material varchar(80) DEFAULT 'PC',
        clamping_required varchar(60) DEFAULT '',
        compatible_specs varchar(120) DEFAULT '',
        tolerance_abs tinyint(1) DEFAULT 0,
        tolerance_pp tinyint(1) DEFAULT 0,
        tolerance_pe tinyint(1) DEFAULT 0,
        water_cooled varchar(20) DEFAULT 'Yes',
        suck_pump varchar(20) DEFAULT 'No',
        food_grade varchar(20) DEFAULT 'No',
        medical_grade varchar(20) DEFAULT 'Yes',
        listing_date date DEFAULT NULL,
        expiry_date date DEFAULT NULL,
        image_1 varchar(500) DEFAULT '',
        image_2 varchar(500) DEFAULT '',
        image_3 varchar(500) DEFAULT '',
        available tinyint(1) DEFAULT 1,
        listing_status varchar(20) DEFAULT 'available',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $c;");

    dbDelta("CREATE TABLE {$wpdb->prefix}ih_threads (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        listing_id bigint(20) DEFAULT 0,
        listing_type varchar(20) DEFAULT 'machine',
        last_message text DEFAULT '',
        last_time datetime DEFAULT CURRENT_TIMESTAMP,
        unread int DEFAULT 0,
        blocked tinyint(1) DEFAULT 0,
        PRIMARY KEY  (id)
    ) $c;");

    dbDelta("CREATE TABLE {$wpdb->prefix}ih_chats (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        thread_id bigint(20) NOT NULL,
        from_me tinyint(1) DEFAULT 0,
        message text NOT NULL,
        sent_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY  thread_id (thread_id)
    ) $c;");

    dbDelta("CREATE TABLE {$wpdb->prefix}ih_requests (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        listing_id bigint(20) DEFAULT 0,
        listing_type varchar(20) DEFAULT 'machine',
        request_date date DEFAULT NULL,
        status varchar(20) DEFAULT 'Pending',
        PRIMARY KEY  (id)
    ) $c;");

    dbDelta("CREATE TABLE {$wpdb->prefix}ih_activity_log (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        actor_id bigint(20) DEFAULT 0,
        type varchar(30) DEFAULT 'system',
        message text NOT NULL,
        meta text NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY type (type),
        KEY created_at (created_at)
    ) $c;");

    // Requester ↔ owner direct messages (gated on an APPROVED ih_requests row).
    if ( function_exists( 'ih_rmsg_install_schema' ) ) {
        ih_rmsg_install_schema();
    }

    ih_seed_data();
    ih_run_migrations();
}

/* ═══════════════════════════════════════
   SCHEMA MIGRATIONS  (versioned, non-destructive)
   The plugin is already active in production, so the activation hook will not
   re-fire. Migrations therefore run on `plugins_loaded` (front-end + admin),
   gated by the ih_schema_version option so they are a cheap no-op once applied.
   Only additive, guarded ALTER TABLE statements are used — never DROP/destructive.
═══════════════════════════════════════ */
function ih_run_migrations() {
    if ( get_option( 'ih_schema_version' ) === IH_DB_VERSION ) {
        return; // already up to date
    }

    // Prevent concurrent migration runs from stacking ALTER TABLE work (502 risk on Cloudways).
    if ( get_transient( 'ih_migration_running' ) ) {
        return;
    }
    set_transient( 'ih_migration_running', 1, 120 );

    try {

    global $wpdb;
    $p = $wpdb->prefix;

    $table_exists = function ( $table ) use ( $wpdb ) {
        return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
    };
    $has_col = function ( $table, $col ) use ( $wpdb ) {
        return (bool) $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `{$table}` LIKE %s", $col ) );
    };
    $has_index = function ( $table, $index ) use ( $wpdb ) {
        return (bool) $wpdb->get_var( $wpdb->prepare( "SHOW INDEX FROM `{$table}` WHERE Key_name=%s", $index ) );
    };

    // ── ih_threads: add created_at + updated_at (+ user_id index) ──
    $threads = $p . 'ih_threads';
    if ( $table_exists( $threads ) ) {
        if ( ! $has_col( $threads, 'created_at' ) ) {
            $wpdb->query( "ALTER TABLE `{$threads}` ADD COLUMN `created_at` datetime DEFAULT CURRENT_TIMESTAMP" );
        }
        if ( ! $has_col( $threads, 'updated_at' ) ) {
            $wpdb->query( "ALTER TABLE `{$threads}` ADD COLUMN `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" );
            // Backfill from the existing last_time column so ordering stays meaningful.
            if ( $has_col( $threads, 'last_time' ) ) {
                $wpdb->query( "UPDATE `{$threads}` SET `updated_at`=`last_time`, `created_at`=`last_time` WHERE `last_time` IS NOT NULL" );
            }
        }
        if ( ! $has_index( $threads, 'user_id' ) ) {
            $wpdb->query( "ALTER TABLE `{$threads}` ADD INDEX `user_id` (`user_id`)" );
        }
    }

    // ── ih_requests: add created_at + updated_at (+ indexes) ──
    $requests = $p . 'ih_requests';
    if ( $table_exists( $requests ) ) {
        if ( ! $has_col( $requests, 'created_at' ) ) {
            $wpdb->query( "ALTER TABLE `{$requests}` ADD COLUMN `created_at` datetime DEFAULT CURRENT_TIMESTAMP" );
            if ( $has_col( $requests, 'request_date' ) ) {
                $wpdb->query( "UPDATE `{$requests}` SET `created_at`=`request_date` WHERE `request_date` IS NOT NULL" );
            }
        }
        if ( ! $has_col( $requests, 'updated_at' ) ) {
            $wpdb->query( "ALTER TABLE `{$requests}` ADD COLUMN `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" );
        }
        foreach ( array( 'user_id', 'listing_id', 'status' ) as $idx ) {
            if ( $has_col( $requests, $idx ) && ! $has_index( $requests, $idx ) ) {
                $wpdb->query( "ALTER TABLE `{$requests}` ADD INDEX `{$idx}` (`{$idx}`)" );
            }
        }
    }

    // ── Listing tables: lifecycle status + owner index for fast "my listings" lookups ──
    foreach ( array( 'ih_machines', 'ih_tools' ) as $lt ) {
        $tb = $p . $lt;
        if ( $table_exists( $tb ) ) {
            if ( ! $has_col( $tb, 'listing_status' ) ) {
                $wpdb->query( "ALTER TABLE `{$tb}` ADD COLUMN `listing_status` varchar(20) DEFAULT 'available' AFTER `available`" );
                if ( $has_col( $tb, 'available' ) ) {
                    $wpdb->query( "UPDATE `{$tb}` SET `listing_status`=CASE WHEN `available`=1 THEN 'available' ELSE 'pending' END" );
                }
            }
            if ( $has_col( $tb, 'owner_id' ) && ! $has_index( $tb, 'owner_id' ) ) {
                $wpdb->query( "ALTER TABLE `{$tb}` ADD INDEX `owner_id` (`owner_id`)" );
            }
            if ( $has_col( $tb, 'listing_status' ) && ! $has_index( $tb, 'listing_status' ) ) {
                $wpdb->query( "ALTER TABLE `{$tb}` ADD INDEX `listing_status` (`listing_status`)" );
            }
        }
    }

    // Backfill listing lifecycle from the latest owner-submitted listing approval
    // requests. Contact/access requests from non-owners are intentionally ignored.
    if ( $table_exists( $requests ) ) {
        $machines = $p . 'ih_machines';
        if ( $table_exists( $machines ) && $has_col( $machines, 'listing_status' ) ) {
            $owner_machine_requests = $wpdb->get_results(
                "SELECT r.user_id, r.listing_id, r.listing_type, r.status
                 FROM `{$requests}` r
                 INNER JOIN (
                    SELECT r2.listing_id, MAX(r2.id) AS latest_id
                    FROM `{$requests}` r2
                    INNER JOIN `{$machines}` m2 ON m2.id = r2.listing_id AND m2.owner_id = r2.user_id
                    WHERE r2.listing_type='machine' AND r2.listing_id > 0
                    GROUP BY r2.listing_id
                 ) latest ON latest.latest_id = r.id",
                ARRAY_A
            ) ?: array();
            foreach ( $owner_machine_requests as $owner_request ) {
                ih_sync_listing_status_from_request( $owner_request );
            }
        }

        $tools = $p . 'ih_tools';
        if ( $table_exists( $tools ) && $has_col( $tools, 'listing_status' ) ) {
            $owner_tool_requests = $wpdb->get_results(
                "SELECT r.user_id, r.listing_id, r.listing_type, r.status
                 FROM `{$requests}` r
                 INNER JOIN (
                    SELECT r2.listing_id, MAX(r2.id) AS latest_id
                    FROM `{$requests}` r2
                    INNER JOIN `{$tools}` t2 ON t2.id = r2.listing_id AND t2.owner_id = r2.user_id
                    WHERE r2.listing_type='tool' AND r2.listing_id > 0
                    GROUP BY r2.listing_id
                 ) latest ON latest.latest_id = r.id",
                ARRAY_A
            ) ?: array();
            foreach ( $owner_tool_requests as $owner_request ) {
                ih_sync_listing_status_from_request( $owner_request );
            }
        }
    }

    // ── ih_tools: add the extra technical columns used by the Figma "Add Tool"
    //    form (node 80:2). Purely additive + guarded; no DROP/destructive change.
    $tools = $p . 'ih_tools';
    if ( $table_exists( $tools ) ) {
        $tool_cols = array(
            'tolerance'             => "varchar(60) DEFAULT ''",
            'surface_finish'        => "varchar(120) DEFAULT ''",
            'draft_angle'           => "varchar(60) DEFAULT ''",
            'runner_type'           => "varchar(80) DEFAULT ''",
            'gate_type'             => "varchar(80) DEFAULT ''",
            'construction'          => "varchar(80) DEFAULT ''",
            'mould_weight'          => "varchar(60) DEFAULT ''",
            'mould_dimensions'      => "varchar(120) DEFAULT ''",
            'tool_life'             => "varchar(80) DEFAULT ''",
            'required_qty'          => "varchar(80) DEFAULT ''",
            'packaging'             => "varchar(120) DEFAULT ''",
            'material_supplied'     => "varchar(80) DEFAULT ''",
            'clamp_force'           => "varchar(60) DEFAULT ''",
            'shot_weight'           => "varchar(60) DEFAULT ''",
            'tie_bar'               => "varchar(60) DEFAULT ''",
            'opening_stroke'        => "varchar(60) DEFAULT ''",
            'hot_runner_controller' => "varchar(80) DEFAULT ''",
            'hot_runner_zones'      => "varchar(60) DEFAULT ''",
            'iml'                   => "varchar(20) DEFAULT 'No'",
            'automation'            => "varchar(20) DEFAULT 'No'",
            'materials'             => "text NULL",
            'mould_location'        => "varchar(255) DEFAULT ''",
            'clamp_drive_type'      => "varchar(80) DEFAULT ''",
            'toggle_clamp_type'     => "varchar(80) DEFAULT ''",
            'injection_stages'      => "varchar(160) DEFAULT ''",
            'tool_condition'        => "varchar(60) DEFAULT ''",
            'image_4'               => "varchar(500) DEFAULT ''",
            'image_5'               => "varchar(500) DEFAULT ''",
            // Clamp-tonnage inputs (v2026061902): total projected area in cm² and
            // cavity (melt) pressure in bar. Used to compute required clamp tonnage
            // = projected_area × cavity_pressure ÷ 981 (graceful fallback when empty).
            'projected_area'        => "varchar(60) DEFAULT ''",
            'cavity_pressure'       => "varchar(60) DEFAULT ''",
        );
        foreach ( $tool_cols as $col => $def ) {
            if ( ! $has_col( $tools, $col ) ) {
                $wpdb->query( "ALTER TABLE `{$tools}` ADD COLUMN `{$col}` {$def}" );
            }
        }
        if ( ! $has_col( $tools, 'updated_at' ) ) {
            $wpdb->query( "ALTER TABLE `{$tools}` ADD COLUMN `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" );
        }
    }

    $machines = $p . 'ih_machines';
    if ( $table_exists( $machines ) ) {
        $machine_cols = array(
            'opening_stroke'     => "varchar(60) DEFAULT ''",
            'material_grade'     => "varchar(255) DEFAULT ''",
            'materials'          => "text NULL",
			'clamp_drive_type'   => "varchar(80) DEFAULT ''",
			'toggle_clamp_type'  => "varchar(80) DEFAULT ''",
			'notes'              => "text NULL",
			'internal_notes'     => "text NULL",
			'image_4'            => "varchar(500) DEFAULT ''",
			'image_5'            => "varchar(500) DEFAULT ''",
			'cavities'           => "int unsigned DEFAULT 0",
			// Clamp-tonnage inputs (v2026061902): reference projected area in cm² and
			// cavity (melt) pressure in bar the press is specced around. Mirrors the
			// ih_tools columns so the fit/match calc can use a physics-based required
			// tonnage (projected_area × cavity_pressure ÷ 981) with heuristic fallback.
			'projected_area'     => "varchar(60) DEFAULT ''",
			'cavity_pressure'    => "varchar(60) DEFAULT ''",
        );
        foreach ( $machine_cols as $col => $def ) {
            if ( ! $has_col( $machines, $col ) ) {
                $wpdb->query( "ALTER TABLE `{$machines}` ADD COLUMN `{$col}` {$def}" );
            }
        }
        if ( ! $has_col( $machines, 'updated_at' ) ) {
            $wpdb->query( "ALTER TABLE `{$machines}` ADD COLUMN `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" );
        }
    }

    // ── ih_activity_log: admin audit trail (created via migration because the
    //    activation hook will not re-fire on the live install) ──
    $activity = $p . 'ih_activity_log';
    if ( ! $table_exists( $activity ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$activity} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            actor_id bigint(20) DEFAULT 0,
            type varchar(30) DEFAULT 'system',
            message text NOT NULL,
            meta text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY created_at (created_at)
        ) {$charset};");
    }

    // ── ih_messages: requester ↔ owner direct messaging (created via migration
    //    because the activation hook will not re-fire on the live install) ──
    if ( function_exists( 'ih_rmsg_table' ) && function_exists( 'ih_rmsg_install_schema' ) ) {
        $rmsg_tbl = ih_rmsg_table();
        if ( ! $table_exists( $rmsg_tbl ) ) {
            ih_rmsg_install_schema();
        } elseif ( ! $has_col( $rmsg_tbl, 'delivered_at' ) ) {
            // Delivery receipts (two-grey-ticks "Delivered" distinct from Read).
            // Additive + guarded; set when the recipient's unread poller runs.
            $wpdb->query( "ALTER TABLE `{$rmsg_tbl}` ADD COLUMN `delivered_at` datetime DEFAULT NULL AFTER `created_at`" );
        }
        // ── v2026062003: attachment columns on ih_messages (image / file / video
        //    bubbles + the right-rail "Shared files" grid). Additive + guarded. ──
        if ( $table_exists( $rmsg_tbl ) ) {
            $rmsg_attach_cols = array(
                'attachment_url'  => "varchar(500) DEFAULT NULL",
                'attachment_type' => "varchar(20) DEFAULT NULL",
                'attachment_name' => "varchar(190) DEFAULT NULL",
                'attachment_size' => "bigint(20) DEFAULT NULL",
            );
            foreach ( $rmsg_attach_cols as $col => $def ) {
                if ( ! $has_col( $rmsg_tbl, $col ) ) {
                    $wpdb->query( "ALTER TABLE `{$rmsg_tbl}` ADD COLUMN `{$col}` {$def}" );
                }
            }
        }
    }

    // ── v2026062003: ih_thread_settings — per-request access/listing/attachment
    //    controls surfaced as the admin "Access & controls" green switches. One
    //    row per approved request thread; defaults to all-granted. ──
    $rmsg_settings = $p . 'ih_thread_settings';
    if ( ! $table_exists( $rmsg_settings ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        dbDelta( "CREATE TABLE {$rmsg_settings} (
            request_id bigint(20) NOT NULL,
            profile_access tinyint(1) NOT NULL DEFAULT 1,
            listing_access tinyint(1) NOT NULL DEFAULT 1,
            allow_attachments tinyint(1) NOT NULL DEFAULT 1,
            updated_by bigint(20) DEFAULT 0,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (request_id)
        ) {$charset};" );
    }

    // ── v2026062003: ih_request_participants — guests invited into a request
    //    thread (group chat). owner + requester are implicit; this stores extras
    //    added by an admin via the "Invite a user to this chat" flow. ──
    $rmsg_participants = $p . 'ih_request_participants';
    if ( ! $table_exists( $rmsg_participants ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        dbDelta( "CREATE TABLE {$rmsg_participants} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            request_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            role varchar(20) DEFAULT 'guest',
            added_by bigint(20) DEFAULT 0,
            added_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY request_user (request_id, user_id),
            KEY request_id (request_id),
            KEY user_id (user_id)
        ) {$charset};" );
    }

    // ── ih_materials + ih_material_price_history: live resin pricing backend
    //    (created + seeded via migration, since register_activation_hook will
    //    not re-fire on this already-active install). install() is idempotent:
    //    dbDelta is additive and seed() is guarded on an empty-table check. ──
    if ( class_exists( 'IH_Material_Pricing' ) ) {
        IH_Material_Pricing::install();
    }

    // ── 24-hour price-check tables (sources / checks / reference prices) plus
    //    additive columns on ih_materials and metadata_json on the history
    //    table. dbDelta + guarded ALTER, so this is idempotent. ──
    if ( function_exists( 'ih_material_prices_install_tables' ) ) {
        ih_material_prices_install_tables();
    }

    update_option( 'ih_schema_version', IH_DB_VERSION );

    } finally {
        delete_transient( 'ih_migration_running' );
    }
}

// Run additive schema checks from admin/CLI only. Running ALTER TABLE checks on
// public page loads can tie up PHP-FPM workers and surface as nginx 502s.
add_action( 'admin_init', 'ih_run_migrations' );
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    add_action( 'init', 'ih_run_migrations' );
}

/* Grant the material-pricing capability to administrators idempotently. The
 * plugin is already active so the activation hook will not re-fire; add the cap
 * on admin_init only when missing (a cheap no-op once applied). */
add_action( 'admin_init', function () {
    $role = get_role( 'administrator' );
    if ( $role && ! $role->has_cap( 'ih_manage_pricing' ) ) {
        $role->add_cap( 'ih_manage_pricing' );
    }
} );

/* ═══════════════════════════════════════
   ACTIVITY / AUDIT LOG
   ih_log_activity() records every admin-relevant platform event so the
   Activity page (admin.php?page=ih-activity) shows a full audit trail.
═══════════════════════════════════════ */
function ih_log_activity( $type, $message, array $meta = [] ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ih_activity_log';

    static $exists = null;
    if ( $exists === null ) {
        $exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table );
    }
    if ( ! $exists ) return false;

    return (bool) $wpdb->insert( $table, [
        'actor_id'   => get_current_user_id(),
        'type'       => sanitize_key( $type ),
        'message'    => sanitize_text_field( $message ),
        'meta'       => $meta ? wp_json_encode( $meta ) : null,
        'created_at' => current_time( 'mysql' ),
    ], [ '%d', '%s', '%s', '%s', '%s' ] );
}

/* New registrations land in the audit trail automatically */
add_action( 'user_register', function ( $user_id ) {
    $u = get_userdata( $user_id );
    ih_log_activity( 'user', 'New user registered: ' . ( $u ? $u->display_name : ( '#' . $user_id ) ), [ 'user_id' => $user_id ] );
} );

/* Settings changes are audited too */
add_action( 'updated_option', function ( $option, $old, $new ) {
    $watched = [ 'ih_whatsapp_number', 'ih_ws_url', 'ih_admin_notify_email', 'ih_default_machine_image', 'ih_default_tool_image' ];
    if ( in_array( $option, $watched, true ) ) {
        ih_log_activity( 'settings', 'Setting updated: ' . $option, [ 'option' => $option ] );
    }
}, 10, 3 );

function ih_seed_data() {
    global $wpdb;
    if ( $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ih_machines") > 0 ) return;

    $machine_img = '';
    $tool_img    = '';

    $machines = [
        ['Engel Victory 150','Engel','Hydraulic','150T','30g-120g','PE,ABS,PP','Manchester, UK','16h/day','65%'],
        ['Arburg Allrounder 470','Arburg','Electric','70T','10g-50g','PA,POM,PC','Birmingham, UK','24h/day','40%'],
        ['KraussMaffei CX 200','KraussMaffei','Hybrid','200T','50g-180g','PP,HDPE,TPE','Leeds, UK','24h/day','55%'],
        ['Sumitomo SE 75','Sumitomo','Electric','75T','5g-30g','ABS,PC','Sheffield, UK','16h/day','70%'],
        ['Husky H300','Husky','Hydraulic','300T','100g-500g','PE,PP','Manchester, UK','24h/day','80%'],
        ['Milacron Roboshot 110','Milacron','Electric','110T','20g-80g','PA,ABS,PC','Leeds, UK','16h/day','60%'],
    ];

    foreach ( $machines as $idx => $m ) {
        // $wpdb->insert ke UPAR ye add karo:

        $wpdb->insert( $wpdb->prefix . 'ih_machines', [
            'title'           => $m[0], 'brand' => $m[1], 'machine_type' => $m[2],
            'clamping_force'  => $m[3], 'shot_size' => $m[4], 'location' => $m[7],
            'operating_hours' => $m[8], 'utilization' => $m[9],
            'listing_date'    => '2026-03-12', 'expiry_date' => '2026-09-12',
            'image_1'         => $machine_img, 'available' => 1, 'listing_status' => 'available',
            'min_order_qty'   => '500', 'avg_cycle_time' => '18 seconds',
            'batch_size'      => 'Medium (5,000-50,000)', 'certifications' => 'ISO 9001',
        ]);
    }

    $tools = ['Medical Device Housing Mould','Bottle Cap Mould','Connector Pin Mould','Automotive Clip Mould','Cosmetic Cap Mould','Electronic Housing Mould'];
    foreach ( $tools as $t ) {
        $wpdb->insert( $wpdb->prefix . 'ih_tools', [
            'title' => $t, 'part_description' => 'High-precision housing for a handheld medical diagnostic device.',
            'mould_type' => 'Multi-Cavity', 'mould_material' => 'H13 Steel', 'mould_condition' => 'New',
            'num_cavities_spec' => 4, 'material' => 'PC', 'location' => 'Stored at supplier',
            'owner_name' => 'Precision Mould Co.', 'listing_date' => '2026-03-12', 'expiry_date' => '2026-09-12',
            'image_1' => $tool_img, 'available' => 1, 'listing_status' => 'available', 'medical_grade' => 'Yes', 'food_grade' => 'Yes',
            'part_weight' => '28', 'annual_volume' => '50,000+', 'cycle_time' => '24 seconds',
            'min_order_qty' => '5,000', 'clamping_required' => '70T',
        ]);
    }

    $wpu    = get_users(['number'=>10]);
    $locs   = ['Behria Town Lahore','Toledo','Poland','Austin','Pembroke Pines','Naperville','Manchester','Leeds','London','Birmingham'];
    $phones = ['+92 3006209341','+92 3016209342','+92 3026209343','+92 3036209344','+92 3046209345','+44 7911 123456','+44 7922 234567','+44 7933 345678','+44 7944 456789','+44 7955 567890'];
    foreach ( $wpu as $i => $u ) {
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ih_user_meta WHERE user_id=%d",$u->ID));
        if (!$exists) {
            $wpdb->insert($wpdb->prefix.'ih_user_meta',[
                'user_id'  => $u->ID, 'location' => $locs[$i % count($locs)],
                'phone'    => $phones[$i % count($phones)], 'company' => 'Alpha Production',
                'job_title'=> 'Collector', 'address' => 'Al Kareem Market',
                'blocked'  => ($i===3) ? 1 : 0,
                'status'   => ['Approved','Pending','Rejected'][$i%3], 'joined' => '2026-03-12',
            ]);
            /* ── Seed data ko WordPress user meta mein bhi save karo ── */
            ih_sync_user_meta( $u->ID, [
                'phone'        => $phones[$i % count($phones)],
                'company'      => 'Alpha Production',
                'job_title'    => 'Collector',
                'address'      => 'Al Kareem Market',
                'city'         => $locs[$i % count($locs)],
                'location'     => $locs[$i % count($locs)],
                'business_role'=> 'Marketing',
            ]);
        }
    }

    $users = get_users(['number'=>8]);
    $msgs  = ['Hi, can I know the price details of this machine?','Can you help me with this machine for branding?','Can you confirm my machine for tomorrow?','Is this machine still available?','What are the production timelines?','Can you share the technical specs?','I need the production for some plastic chairs.','What is the minimum order quantity?'];
    foreach ( $users as $i => $u ) {
        $wpdb->insert($wpdb->prefix.'ih_threads',[
            'user_id'      => $u->ID, 'listing_id' => $i + 1, 'listing_type' => 'machine',
            'last_message' => $msgs[$i], 'last_time' => date('Y-m-d H:i:s', strtotime("-{$i} hours")),
            'unread'       => ($i===0) ? 0 : ($i % 4),
        ]);
        $tid = $wpdb->insert_id;
        if ($i === 0) {
            $chats = [
                [0,'Hi, can I know the price details of this machine?','9:15 AM'],
                [1,'Yes, this machine is available for production.','9:22 AM'],
                [0,'What will be the charges and timeline if I need 1 million productions?','9:23 AM'],
                [1,'What will be the charges and timeline if I need 1 million productions?','9:24 AM'],
                [0,'For whom do you need the production?','9:23 AM'],
                [1,'I need the production for some plastic chairs.','9:33 AM'],
            ];
            foreach ($chats as $c) {
                $wpdb->insert($wpdb->prefix.'ih_chats',['thread_id'=>$tid,'from_me'=>$c[0],'message'=>$c[1],'sent_at'=>date('Y-m-d').' '.$c[2]]);
            }
        }
        $wpdb->insert($wpdb->prefix.'ih_requests',[
            'user_id'=>$u->ID,'listing_id'=>$i+1,'listing_type'=>'machine',
            'request_date'=>'2026-03-12','status'=>['Approved','Pending','Rejected'][$i%3],
        ]);
    }
}

/* ═══════════════════════════════════════
   DB QUERY HELPERS
═══════════════════════════════════════ */
function ih_db_table_exists( $table ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
}

function ih_db_machines( $limit = 0 ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ih_machines';
    if ( ! ih_db_table_exists( $table ) ) {
        return array();
    }
    $sql = "SELECT * FROM {$table} ORDER BY id DESC";
    if ( $limit ) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $rows = $wpdb->get_results( $sql, ARRAY_A );
    return is_array( $rows ) ? $rows : array();
}
function ih_db_machine( $id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ih_machines';
    if ( ! ih_db_table_exists( $table ) ) {
        return null;
    }
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", (int) $id ), ARRAY_A );
}

function ih_listing_current_date() {
    return wp_date( 'Y-m-d', current_time( 'timestamp' ) );
}

function ih_listing_is_expired( $listing ) {
    $expiry = trim( (string) ( is_array( $listing ) ? ( $listing['expiry_date'] ?? '' ) : ( $listing->expiry_date ?? '' ) ) );
    if ( $expiry === '' || $expiry === '0000-00-00' || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $expiry ) ) {
        return false;
    }
    return $expiry < ih_listing_current_date();
}

function ih_listing_not_expired_sql( $column = 'expiry_date' ) {
    global $wpdb;
    $column = preg_replace( '/[^A-Za-z0-9_`.]/', '', (string) $column );
    if ( $column === '' ) {
        $column = 'expiry_date';
    }
    return $wpdb->prepare(
        "({$column} IS NULL OR {$column} = '0000-00-00' OR {$column} >= %s)",
        ih_listing_current_date()
    );
}

function ih_db_tools( $limit = 0 ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ih_tools';
    if ( ! ih_db_table_exists( $table ) ) {
        return array();
    }
    $sql = "SELECT * FROM {$table} ORDER BY id DESC";
    if ( $limit ) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $rows = $wpdb->get_results( $sql, ARRAY_A );
    return is_array( $rows ) ? $rows : array();
}
function ih_db_tool( $id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ih_tools';
    if ( ! ih_db_table_exists( $table ) ) {
        return null;
    }
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", (int) $id ), ARRAY_A );
}

function ih_listing_statuses() {
    return array(
        'pending'   => array( 'label' => 'Pending Review', 'available' => 0, 'class' => 'is-pending' ),
        'available' => array( 'label' => 'Available Now',   'available' => 1, 'class' => 'is-available' ),
        'completed' => array( 'label' => 'Completed',       'available' => 0, 'class' => 'is-completed' ),
        'rejected'  => array( 'label' => 'Rejected',        'available' => 0, 'class' => 'is-rejected' ),
    );
}

function ih_normalize_listing_status( $status, $available = null ) {
    $key = strtolower( trim( sanitize_key( (string) $status ) ) );
    if ( isset( ih_listing_statuses()[ $key ] ) ) {
        return $key;
    }
    return ( (int) $available === 1 ) ? 'available' : 'pending';
}

function ih_listing_status_meta( $listing ) {
    if ( ih_listing_is_expired( $listing ) ) {
        return array(
            'key'       => 'expired',
            'label'     => 'Expired',
            'available' => 0,
            'class'     => 'is-rejected',
        );
    }
    $statuses = ih_listing_statuses();
    $key      = ih_normalize_listing_status( $listing['listing_status'] ?? '', $listing['available'] ?? 0 );
    return array_merge( array( 'key' => $key ), $statuses[ $key ] );
}

function ih_listing_status_badge( $listing ) {
    $meta = ih_listing_status_meta( $listing );
    return '<span class="ih-listing-status-badge ' . esc_attr( $meta['class'] ) . '">' . esc_html( $meta['label'] ) . '</span>';
}

function ih_update_listing_status( $listing_type, $listing_id, $status ) {
    global $wpdb;
    $type = sanitize_key( $listing_type );
    if ( ! in_array( $type, array( 'machine', 'tool' ), true ) ) {
        return new WP_Error( 'invalid_listing_type', 'Invalid listing type.' );
    }

    $statuses = ih_listing_statuses();
    $key      = ih_normalize_listing_status( $status );
    if ( ! isset( $statuses[ $key ] ) ) {
        return new WP_Error( 'invalid_listing_status', 'Invalid listing status.' );
    }

    $table = $type === 'tool' ? $wpdb->prefix . 'ih_tools' : $wpdb->prefix . 'ih_machines';
    $data  = array(
        'listing_status' => $key,
        'available'      => (int) $statuses[ $key ]['available'],
    );

    return $wpdb->update( $table, $data, array( 'id' => (int) $listing_id ), array( '%s', '%d' ), array( '%d' ) );
}

function ih_listing_status_from_request_status( $request_status ) {
    $key = strtolower( trim( (string) $request_status ) );
    if ( $key === 'approved' ) {
        return 'available';
    }
    if ( $key === 'rejected' ) {
        return 'rejected';
    }
    if ( $key === 'completed' ) {
        return 'completed';
    }
    return 'pending';
}

function ih_normalize_request_listing_type( $listing_type ) {
    $type = sanitize_key( (string) $listing_type );
    if ( $type === 'machine_contact' ) {
        return 'machine';
    }
    if ( $type === 'tool_contact' ) {
        return 'tool';
    }
    return $type;
}

function ih_request_is_owner_listing_approval( $request ) {
    $type       = ih_normalize_request_listing_type( $request['listing_type'] ?? '' );
    $listing_id = (int) ( $request['listing_id'] ?? 0 );
    $user_id    = (int) ( $request['user_id'] ?? 0 );
    if ( ! in_array( $type, array( 'machine', 'tool' ), true ) || ! $listing_id || ! $user_id ) {
        return false;
    }
    global $wpdb;
    $table    = $type === 'tool' ? $wpdb->prefix . 'ih_tools' : $wpdb->prefix . 'ih_machines';
    $owner_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT owner_id FROM {$table} WHERE id=%d", $listing_id ) );
    return $owner_id === $user_id;
}

function ih_create_listing_approval_request( $user_id, $listing_id, $listing_type, $reset_pending = false ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ih_requests';
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
        return 0;
    }
    $type = sanitize_key( $listing_type );
    if ( ! in_array( $type, array( 'machine', 'tool' ), true ) || ! $listing_id || ! $user_id ) {
        return 0;
    }
    $existing_row = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, status FROM {$table} WHERE user_id=%d AND listing_id=%d AND listing_type=%s ORDER BY id DESC LIMIT 1",
        (int) $user_id,
        (int) $listing_id,
        $type
    ), ARRAY_A );
    if ( $existing_row ) {
        $existing_id = (int) ( $existing_row['id'] ?? 0 );
        $status_lc   = strtolower( trim( (string) ( $existing_row['status'] ?? '' ) ) );
        if ( $status_lc === 'pending' ) {
            return $existing_id;
        }
        if ( $reset_pending || $status_lc === 'rejected' ) {
            $wpdb->update(
                $table,
                array(
                    'status'       => 'Pending',
                    'request_date' => current_time( 'Y-m-d' ),
                ),
                array( 'id' => $existing_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
            delete_transient( 'ih_admin_notifications_v1' );
            return $existing_id;
        }
        return $existing_id;
    }
    $ok = $wpdb->insert(
        $table,
        array(
            'user_id'      => (int) $user_id,
            'listing_id'   => (int) $listing_id,
            'listing_type' => $type,
            'request_date' => current_time( 'Y-m-d' ),
            'status'       => 'Pending',
        ),
        array( '%d', '%d', '%s', '%s', '%s' )
    );
    if ( $ok ) {
        delete_transient( 'ih_admin_notifications_v1' );
        return (int) $wpdb->insert_id;
    }
    return 0;
}

function ih_mark_listing_pending_review( $user_id, $listing_id, $listing_type ) {
    $type = sanitize_key( (string) $listing_type );
    if ( ! in_array( $type, array( 'machine', 'tool' ), true ) || ! $listing_id || ! $user_id ) {
        return 0;
    }
    ih_update_listing_status( $type, (int) $listing_id, 'pending' );
    return ih_create_listing_approval_request( (int) $user_id, (int) $listing_id, $type, true );
}

function ih_sync_listing_status_from_request( $request ) {
    if ( empty( $request['listing_id'] ) || empty( $request['listing_type'] ) || empty( $request['user_id'] ) ) {
        return false;
    }

    $listing_type = ih_normalize_request_listing_type( $request['listing_type'] );
    if ( ! in_array( $listing_type, array( 'machine', 'tool' ), true ) ) {
        return false;
    }

    global $wpdb;
    $table    = $listing_type === 'tool' ? $wpdb->prefix . 'ih_tools' : $wpdb->prefix . 'ih_machines';
    $owner_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT owner_id FROM {$table} WHERE id=%d", (int) $request['listing_id'] ) );
    if ( $owner_id !== (int) $request['user_id'] ) {
        return false;
    }

    $listing_status = ih_listing_status_from_request_status( $request['status'] ?? 'Pending' );
    return ih_update_listing_status( $listing_type, (int) $request['listing_id'], $listing_status );
}

/* ═══════════════════════════════════════
   SHARED MATERIALS (single source of truth)
   Mirrors the Add Tool listing material set so machine + tool
   listing pages always render the same material data/labels.
═══════════════════════════════════════ */
function ih_machine_materials_map() {
    return [
        'materials_abs'  => [ 'code' => 'ABS',  'label' => 'ABS',                'aliases' => [ 'PC/ABS' ] ],
        'materials_pp'   => [ 'code' => 'PP',   'label' => 'Polypropylene (PP)', 'aliases' => [] ],
        'materials_pe'   => [ 'code' => 'PE',   'label' => 'Polyethylene (PE)',  'aliases' => [ 'HDPE', 'LDPE', 'LLDPE' ] ],
        'materials_pa'   => [ 'code' => 'PA',   'label' => 'Nylon (PA)',         'aliases' => [ 'Nylon PA6', 'Nylon PA66', 'PA12' ] ],
        'materials_pc'   => [ 'code' => 'PC',   'label' => 'Polycarbonate (PC)', 'aliases' => [ 'PC/ABS' ] ],
        'materials_peek' => [ 'code' => 'PEEK', 'label' => 'PEEK',               'aliases' => [] ],
    ];
}

function ih_machine_material_groups() {
    return array(
        'Commodity plastics' => array(
            'PP', 'PE', 'HDPE', 'LDPE', 'LLDPE', 'PS', 'HIPS', 'PVC', 'PETG',
        ),
        'Engineering plastics' => array(
            'ABS', 'PC', 'PC/ABS', 'ASA', 'SAN', 'PMMA', 'Nylon PA6', 'Nylon PA66', 'PA12',
            'PA+GF', 'POM', 'Acetal', 'POM-C', 'POM-H', 'PBT', 'PBT+GF', 'PET', 'PPO/PPE', 'PP+GF',
        ),
        'Elastomers / flexible' => array(
            'TPE', 'TPU',
        ),
        'High-performance plastics' => array(
            'PEEK', 'PPS', 'PEI', 'LCP', 'PPA', 'PSU', 'PPSU',
        ),
        'Sustainable / recycled / bio' => array(
            'PCR/Recycled resin', 'PLA/Bio-based',
        ),
    );
}

function ih_machine_material_options() {
    $materials = array();
    foreach ( ih_machine_material_groups() as $group ) {
        foreach ( $group as $material ) {
            $materials[] = $material;
        }
    }
    return array_values( array_unique( $materials ) );
}

/** Selected material codes for one machine row, e.g. ['PP','ABS'] */
function ih_machine_materials( $machine ) {
    $out = [];
    if ( ! empty( $machine['materials'] ) ) {
        $decoded = json_decode( (string) $machine['materials'], true );
        if ( is_array( $decoded ) ) {
            foreach ( $decoded as $mat ) {
                $mat = trim( (string) $mat );
                if ( $mat !== '' ) {
                    $out[] = $mat;
                }
            }
        }
    }
    foreach ( ih_machine_materials_map() as $col => $mat ) {
        if ( ! empty( $machine[ $col ] ) ) $out[] = $mat['code'];
    }
    return array_values( array_unique( $out ) );
}

/* ── Live marketplace stats (machines table + tools split) ── */
function ih_marketplace_stats() {
    $machines = ih_db_machines();
    $tools    = ih_db_tools();

    $locations = []; $types = []; $mat_counts = [];
    $clamp_min = null; $clamp_max = null; $available = 0;

    foreach ( ih_machine_materials_map() as $col => $mat ) $mat_counts[ $mat['code'] ] = 0;

    foreach ( $machines as $m ) {
        if ( ! empty( $m['available'] ) ) $available++;
        $loc = trim( (string) ( $m['location'] ?? '' ) );
        if ( $loc !== '' ) $locations[ strtolower( $loc ) ] = true;
        $type = trim( (string) ( $m['machine_type'] ?? '' ) );
        if ( $type !== '' ) $types[ $type ] = ( $types[ $type ] ?? 0 ) + 1;
        foreach ( ih_machine_materials_map() as $col => $mat ) {
            if ( ! empty( $m[ $col ] ) ) $mat_counts[ $mat['code'] ]++;
        }
        $tons = intval( preg_replace( '/\D/', '', (string) ( $m['clamping_force'] ?? '' ) ) );
        if ( $tons > 0 ) {
            $clamp_min = $clamp_min === null ? $tons : min( $clamp_min, $tons );
            $clamp_max = $clamp_max === null ? $tons : max( $clamp_max, $tons );
        }
    }

    return [
        'machines'   => count( $machines ),
        'tools'      => count( $tools ),
        'available'  => $available,
        'locations'  => count( $locations ),
        'types'      => $types,
        'materials'  => $mat_counts,
        'clamp_min'  => $clamp_min,
        'clamp_max'  => $clamp_max,
    ];
}

function ih_db_threads() {
    global $wpdb;
    $rows = $wpdb->get_results("
        SELECT t.*, u.display_name
        FROM {$wpdb->prefix}ih_threads t
        LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
        ORDER BY t.last_time DESC LIMIT 50", ARRAY_A);
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'thread_id'    => (int)$r['id'],
            'user_id'      => (int)$r['user_id'],
            'name'         => $r['display_name'] ?: 'User #' . $r['user_id'],
            'last_message' => $r['last_message'],
            'last_time'    => get_date_from_gmt($r['last_time'], 'g:i A'),
            'unread'       => (int)$r['unread'],
            'blocked'      => (bool)$r['blocked'],
        ];
    }
    return $out;
}

add_action('admin_init', function() {
    if ( ! isset( $_GET['ih_seed_chat'] ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized', 403 );
    }
    global $wpdb;
    $user_id = get_current_user_id();
    $wpdb->query( "DELETE FROM {$wpdb->prefix}ih_threads" );
    $wpdb->query( "DELETE FROM {$wpdb->prefix}ih_chats" );
    $wpdb->insert( $wpdb->prefix . 'ih_threads', [
        'user_id'      => $user_id,
        'last_message' => 'Hello Admin, test message!',
        'last_time'    => current_time( 'mysql', true ),
        'unread'       => 0,
    ] );
    $tid = (int) $wpdb->insert_id;
    $wpdb->insert( $wpdb->prefix . 'ih_chats', [ 'thread_id' => $tid, 'from_me' => 0, 'message' => 'Hi, is this machine available?', 'sent_at' => current_time( 'mysql', true ) ] );
    $wpdb->insert( $wpdb->prefix . 'ih_chats', [ 'thread_id' => $tid, 'from_me' => 1, 'message' => 'Yes, it is!', 'sent_at' => current_time( 'mysql', true ) ] );
    wp_die( 'Test data seeded! Go back to Messages page.' );
});

function ih_db_users() {
    global $wpdb;
    $users = get_users(['number'=>50]);
    $out = [];
    foreach ($users as $u) {
        $meta = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ih_user_meta WHERE user_id=%d",$u->ID), ARRAY_A);
        $out[] = array_merge([
            'id'=>$u->ID,'name'=>$u->display_name,'email'=>$u->user_email,
            'avatar'=>ih_get_user_avatar_url( $u->ID, 80 ),
            'date'=>date('d/m/Y',strtotime($u->user_registered)),
        ], $meta ?: ['blocked'=>0,'status'=>'Approved','location'=>'','phone'=>'']);
    }
    return $out;
}
function ih_db_requests($limit = 20) {
    global $wpdb;
    $limit = max( 1, min( 500, (int) $limit ) );
    $rows = $wpdb->get_results(
        "SELECT r.*, u.display_name AS name, u.user_email AS email,
                um.location, um.phone,
                m.title AS machine_title, t.title AS tool_title
         FROM {$wpdb->prefix}ih_requests r
         INNER JOIN {$wpdb->users} u ON u.ID = r.user_id
         LEFT JOIN {$wpdb->prefix}ih_user_meta um ON um.user_id = r.user_id
         LEFT JOIN {$wpdb->prefix}ih_machines m ON (r.listing_type IN ('machine','machine_contact') AND m.id=r.listing_id)
         LEFT JOIN {$wpdb->prefix}ih_tools    t ON (r.listing_type IN ('tool','tool_contact')    AND t.id=r.listing_id)
         ORDER BY r.id DESC
         LIMIT {$limit}",
        ARRAY_A
    );
    $out = [];
    foreach ( (array) $rows as $r ) {
        $out[] = array_merge( $r, [
            'location' => $r['location'] ?? '',
            'phone'    => $r['phone'] ?? '',
        ] );
    }
    return $out;
}
function ih_db_chats($thread_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ih_chats WHERE thread_id=%d ORDER BY id ASC",$thread_id), ARRAY_A) ?: [];
}
function ih_db_user_meta($user_id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ih_user_meta WHERE user_id=%d",$user_id), ARRAY_A);
}
function ih_db_stats() {
    global $wpdb;
    static $cached = null;
    if ( is_array( $cached ) ) {
        return $cached;
    }

    $tbl = $wpdb->prefix . 'ih_requests';
    $status_counts = [
        'approved'  => 0,
        'pending'   => 0,
        'rejected'  => 0,
        'completed' => 0,
    ];
    $status_rows = $wpdb->get_results(
        "SELECT LOWER(TRIM(status)) AS status, COUNT(*) AS cnt FROM {$tbl} GROUP BY LOWER(TRIM(status))",
        ARRAY_A
    ) ?: [];
    $total_requests = 0;
    foreach ( $status_rows as $row ) {
        $key = strtolower( trim( (string) ( $row['status'] ?? '' ) ) );
        $cnt = (int) ( $row['cnt'] ?? 0 );
        $total_requests += $cnt;
        if ( isset( $status_counts[ $key ] ) ) {
            $status_counts[ $key ] = $cnt;
        }
    }

    $cached = [
        'machines'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ih_machines WHERE available=1" ),
        'tools'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ih_tools WHERE available=1" ),
        'requests'  => $total_requests,
        'approved'  => $status_counts['approved'],
        'pending'   => $status_counts['pending'],
        'rejected'  => $status_counts['rejected'],
        'completed' => $status_counts['completed'],
    ];
    return $cached;
}

function ih_clean_phone( $raw ) {
    return preg_replace('/\D/', '', $raw);
}

function ih_resolve_user_name( $uid ) {
    if ( ! $uid ) return 'Guest';
    $user = get_userdata( $uid );
    if ( $user && trim($user->display_name) && $user->display_name !== $user->user_login ) return $user->display_name;
    $first = get_user_meta($uid,'first_name',true);
    $last  = get_user_meta($uid,'last_name', true);
    $full  = trim("$first $last");
    if ( $full ) return $full;
    if ( $user && $user->user_login ) return $user->user_login;
    return "User #{$uid}";
}

function ih_format_thread_time( $raw ) {
    if ( ! $raw ) return '';
    // DB mein UTC stored hai - WP timezone mein convert karo
    $local = get_date_from_gmt($raw);           // UTC → WP timezone datetime string
    $ts    = strtotime($local);
    if ( ! $ts ) return $raw;
    $now   = current_time('timestamp');          // WP timezone current timestamp
    $diff  = $now - $ts;
    if ( $diff < 60 )     return 'Just now';
    if ( $diff < 3600 )   return round($diff/60).'m ago';
    if ( date_i18n('Y-m-d', $ts) === date_i18n('Y-m-d', $now) ) return date_i18n('g:i A', $ts);
    if ( $diff < 172800 ) return 'Yesterday';
    if ( $diff < 604800 ) return date_i18n('l', $ts);
    return date_i18n('d/m/Y', $ts);
}

/* ═══════════════════════════════════════
   ★ CHANGE 1: ih_sync_user_meta() — NEW FUNCTION
   User ki saari details WordPress standard meta mein save karta hai.
   user-detail.php inhi keys se data read karta hai.
═══════════════════════════════════════ */
function ih_sync_user_meta( $user_id, $data ) {
    if ( ! $user_id ) return;

    // Input key => [WordPress meta keys jo save hongi]
    $map = [
        'phone'         => [ 'phone', 'ih_phone', 'billing_phone' ],
        'company'       => [ 'company_name', 'ih_company', 'billing_company' ],
        'address'       => [ 'address', 'ih_address', 'billing_address_1' ],
        'postcode'      => [ 'postcode', 'ih_postcode', 'billing_postcode' ],
        'city'          => [ 'city', 'ih_city', 'billing_city' ],
        'location'      => [ 'location', 'ih_location' ],
        'business_role' => [ 'business_role', 'ih_business_role' ],
        'job_title'     => [ 'job_title', 'ih_job_title' ],
        'website'       => [ 'website', 'ih_website' ],
    ];

    foreach ( $map as $input_key => $meta_keys ) {
        $value = '';
        // Input array mein se value dhundo (multiple possible key names)
        foreach ( array_merge( [ $input_key ], $meta_keys ) as $k ) {
            if ( ! empty( $data[ $k ] ) ) { $value = sanitize_text_field( $data[ $k ] ); break; }
        }
        if ( $value === '' ) continue;
        // Saari mapped keys pe save karo
        foreach ( $meta_keys as $mk ) {
            update_user_meta( $user_id, $mk, $value );
        }
    }

    // Website ke liye user_url bhi update karo
    if ( ! empty( $data['website'] ) ) {
        wp_update_user( [ 'ID' => $user_id, 'user_url' => esc_url_raw( $data['website'] ) ] );
    }
}

/* ═══════════════════════════════════════
   ADMIN MENU
═══════════════════════════════════════ */
/* ══════════════════════════════════════════════════════════════
   CUSTOM CAPABILITY: ih_user_access
   'read' kuch configurations mein block hoti hai. Isliye apni
   custom cap use karo aur sab logged-in users ko de do.
══════════════════════════════════════════════════════════════ */
add_filter('user_has_cap', function($allcaps, $caps, $args, $user) {
    if ( $user && $user->ID ) {
        $allcaps['ih_user_access'] = true; // Har logged-in user ko milegi
    }
    return $allcaps;
}, 1, 4);

add_action( 'admin_menu', function () {
    // ── Admin pages ──
    add_menu_page('Insight Hub','Insight Hub','manage_options','ih-dashboard','ih_page_dashboard','dashicons-layout',3);
    add_submenu_page('ih-dashboard','Dashboard','Dashboard','manage_options','ih-dashboard','ih_page_dashboard');
    add_submenu_page('ih-dashboard','Users','Users','manage_options','ih-users','ih_page_users');
    add_submenu_page('ih-dashboard','Machines','Machines','manage_options','ih-machines','ih_page_machines');
    add_submenu_page('ih-dashboard','Add Machine','Add Machine','manage_options','ih-add-machine','ih_page_add_machine');
    add_submenu_page('ih-dashboard','Tools','Tools','manage_options','ih-tools','ih_page_tools');
    add_submenu_page('ih-dashboard','Add Tool','Add Tool','manage_options','ih-add-tool','ih_page_add_tool');
    add_submenu_page(null,'Tool Detail','Tool Detail','manage_options','ih-tool-detail','ih_page_tool_detail');
    add_submenu_page(null,'Machine Detail','Machine Detail','manage_options','ih-machine-detail','ih_page_machine_detail');
    add_submenu_page('ih-dashboard','Messages','Messages','manage_options','ih-messages','ih_page_messages');
    add_submenu_page('ih-dashboard','Listing Messages','Listing Messages','manage_options','ih-request-messages','ih_page_request_messages');
    add_submenu_page('ih-dashboard','Requests','Requests','manage_options','ih-requests','ih_page_requests');
    add_submenu_page('ih-dashboard','Activity','Activity','manage_options','ih-activity','ih_page_activity');
    add_submenu_page(null,'Edit Tool','Edit Tool','manage_options','ih-edit-tool','ih_page_edit_tool');
    /* Admin "Edit Machine" page. Registered under the real ih-dashboard parent (then
       hidden via remove_submenu_page below) instead of a null parent, so the page hook
       is guaranteed to register and admin.php?page=ih-edit-machine is always reachable
       for manage_options admins — this is the canonical hidden-but-loadable admin page
       idiom and removes the null-parent fragility behind the dead-slug report. */
    add_submenu_page('ih-dashboard','Edit Machine','Edit Machine','manage_options','ih-edit-machine','ih_page_edit_machine');

    // ── User pages — custom cap (NOT 'read') ──
    add_menu_page('My Dashboard','My Dashboard','ih_user_access','ih-user-dashboard','ih_page_user_dashboard','dashicons-home',2);
    add_submenu_page('ih-user-dashboard','My Listings','My Listings','ih_user_access','ih-user-dashboard','ih_page_user_dashboard');
    add_submenu_page('ih-user-dashboard','Add Machine','Add Machine','ih_user_access','ih-user-add-machine','ih_page_user_add_machine');
    add_submenu_page('ih-user-dashboard','Add Tool','Add Tool','ih_user_access','ih-user-add-tool','ih_page_user_add_tool');
    add_submenu_page('ih-user-dashboard','Messages','Messages','ih_user_access','ih-user-messages','ih_page_user_messages');
    add_submenu_page('ih-user-dashboard','Listing Messages','Listing Messages','ih_user_access','ih-user-request-messages','ih_page_request_messages');
    add_submenu_page(null,'Edit Machine','Edit Machine','ih_user_access','ih-user-edit-machine','ih_page_user_edit_machine');
    add_submenu_page(null,'Edit Tool','Edit Tool','ih_user_access','ih-user-edit-tool','ih_page_user_edit_tool');
    add_submenu_page(null,'View Tool','View Tool','ih_user_access','ih-user-view-tool','ih_page_user_view_tool');
    add_submenu_page(null,'View Machine','View Machine','ih_user_access','ih-user-view-machine','ih_page_user_view_machine');
    // Admin menu mein register karein
   add_submenu_page(
    'ih-user-dashboard',
    'Edit Profile',
    'Edit Profile',
    'ih_user_access',
    'ih-user-edit-profile',
    'ih_page_user_edit_profile'
);

    /* Keep the admin Edit Machine page registered & loadable, but hidden from the
       Insight Hub submenu (it is reached only via the per-listing "Edit listing" link). */
    remove_submenu_page( 'ih-dashboard', 'ih-edit-machine' );
});
/* ── Non-admin users ko redirect karo ── */
add_action('admin_init', function() {
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (!is_user_logged_in()) return;
    if (current_user_can('manage_options')) return;

  $allowed = ['ih-user-dashboard','ih-user-add-machine','ih-user-add-tool',
            'ih-user-messages','ih-user-request-messages','ih-user-edit-machine','ih-user-edit-tool',
            'ih-user-view-tool','ih-user-view-machine','ih-user-edit-profile']; 
    $page    = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

    if (in_array($page, $allowed)) return; // Allowed page — chalte rahne do

    wp_safe_redirect(admin_url('admin.php?page=ih-user-dashboard'));
    exit;
}, 1);

/* ── "Not allowed" page pe bhi redirect karo ── */
add_action('admin_page_access_denied', function() {
    if (!is_user_logged_in() || current_user_can('manage_options')) return;
    $page    = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    $allowed = ['ih-user-dashboard','ih-user-add-machine','ih-user-add-tool','ih-user-messages','ih-user-request-messages','ih-user-edit-machine','ih-user-edit-tool','ih-user-view-tool','ih-user-view-machine','ih-user-edit-profile'];
    if (in_array($page, $allowed)) return; // Page sahi hai — WordPress handle karega
    wp_safe_redirect(admin_url('admin.php?page=ih-user-dashboard'));
    exit;
}, 1);

/* ── Admin bar aur menu hide karo non-admin ke liye ── */
add_action('admin_head', function() {
    if (current_user_can('manage_options')) return;
    echo '<style>
        #adminmenuback,#adminmenuwrap{display:none !important;}
        #wpcontent,#wpfooter{margin-left:0 !important;}
        #wpadminbar{display:none !important;}
        html.wp-toolbar{padding-top:0 !important;}
    </style>';
});

/* ── Login redirect ── */
add_filter('login_redirect', function($redirect_to, $request, $user) {
    if (!is_wp_error($user) && isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
            return admin_url('admin.php?page=ih-dashboard');
        }
        return admin_url('admin.php?page=ih-user-dashboard');
    }
    return $redirect_to;
}, 999, 3);

/**
 * WP admin bar — floating on-demand widget for Insight Hub admin pages.
 */
function ih_wp_admin_bar_widget_should_render() {
    return ih_site_nav_should_render() && current_user_can( 'manage_options' );
}

add_action( 'admin_head', function () {
    if ( ! ih_wp_admin_bar_widget_should_render() ) {
        return;
    }
    echo '<style>html.wp-toolbar{padding-top:0!important;margin-top:0!important;}</style>';
}, 99 );

add_action( 'admin_footer', function () {
    if ( ! ih_wp_admin_bar_widget_should_render() ) {
        return;
    }
    include IH_DIR . 'pages/partials/ih-wp-admin-bar-widget.php';
}, 5 );

/* ═══════════════════════════════════════
   ASSETS
═══════════════════════════════════════ */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( strpos( $hook, 'ih-' ) === false ) {
        return;
    }
    wp_enqueue_style( 'ih-style', IH_URL . 'css/style.css', [], IH_VERSION );
    wp_enqueue_style( 'ih-admin-layout-legacy', IH_URL . 'css/ih-admin-layout-legacy.css', [ 'ih-style' ], IH_VERSION );
    $deps = array( 'jquery' );
    wp_enqueue_script( 'ih-main', IH_URL . 'js/main.js', $deps, IH_VERSION, true );
    wp_localize_script( 'ih-main', 'ihAjax', [ 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'ih_nonce' ) ] );
    wp_enqueue_style( 'ih-resizable-ui', IH_URL . 'css/ih-resizable-ui.css', [ 'ih-style' ], IH_VERSION );
    wp_enqueue_script( 'ih-resizable-ui', IH_URL . 'js/ih-resizable-ui.js', [ 'ih-main' ], IH_VERSION, true );
    wp_enqueue_style( 'ih-dynamic-menu', IH_URL . 'css/ih-dynamic-menu.css', [ 'ih-style' ], IH_VERSION );
    wp_enqueue_script( 'ih-dynamic-menu', IH_URL . 'js/ih-dynamic-menu.js', [ 'ih-main' ], IH_VERSION, true );
    wp_enqueue_style(
        'ih-figma-fonts',
        'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@500;600;700;800;900&display=swap',
        [],
        null
    );
    wp_enqueue_style( 'ih-figma-tokens', IH_URL . 'css/ih-figma-tokens.css', [ 'ih-style' ], IH_VERSION );
    wp_enqueue_style( 'ih-site-nav', IH_URL . 'css/ih-site-nav.css', [ 'ih-style', 'ih-figma-fonts', 'ih-figma-tokens' ], IH_VERSION );
    wp_enqueue_script( 'ih-site-nav', IH_URL . 'js/ih-site-nav.js', [ 'ih-main' ], IH_VERSION, true );
    $ih_page = function_exists( 'ih_site_nav_current_page' ) ? ih_site_nav_current_page() : '';
    if ( ih_wp_admin_bar_widget_should_render() ) {
        wp_enqueue_style( 'ih-wp-admin-bar-widget', IH_URL . 'css/ih-wp-admin-bar-widget.css', [ 'ih-site-nav' ], IH_VERSION );
        wp_enqueue_script( 'ih-wp-admin-bar-widget', IH_URL . 'js/ih-wp-admin-bar-widget.js', [], IH_VERSION, true );
    }
    // Figma 289:2 — Messages Console layout + contact-requests rail (admin messages only).
    if ( $hook === 'insight-hub_page_ih-requests' ) {
        $rd_deps = array( 'ih-figma-fonts', 'ih-figma-tokens', 'ih-site-nav' );
        wp_enqueue_style( 'ih-redesign', IH_URL . 'css/ih-redesign.css', $rd_deps, IH_VERSION );
        wp_enqueue_style( 'ih-requests-redesign', IH_URL . 'css/ih-requests-redesign.css', array( 'ih-redesign' ), IH_VERSION );
        wp_enqueue_script( 'ih-requests-redesign', IH_URL . 'js/ih-requests-redesign.js', array( 'ih-main' ), IH_VERSION, true );
        wp_localize_script( 'ih-requests-redesign', 'ihRequestsRedesign', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ih_nonce' ),
        ] );
        wp_enqueue_script( 'ih-requests-menu', IH_URL . 'js/ih-requests-menu.js', array( 'ih-main', 'ih-requests-redesign' ), IH_VERSION, true );
    }
    if ( $hook === 'insight-hub_page_ih-messages' || $ih_page === 'ih-messages' ) {
        $rd_deps = array( 'ih-figma-fonts', 'ih-figma-tokens', 'ih-site-nav' );
        wp_enqueue_style( 'ih-redesign', IH_URL . 'css/ih-redesign.css', $rd_deps, IH_VERSION );
        wp_enqueue_style( 'ih-admin-messages', IH_URL . 'css/ih-admin-messages.css', array( 'ih-redesign', 'ih-figma-tokens' ), IH_VERSION );
        wp_enqueue_script( 'ih-chat-states', IH_URL . 'js/ih-chat-states.js', array(), IH_VERSION, true );
        wp_enqueue_script( 'ih-chat-new-messages', IH_URL . 'js/ih-chat-new-messages.js', array(), IH_VERSION, true );
        wp_enqueue_script( 'ih-message-reactions', IH_URL . 'js/ih-message-reactions.js', array(), IH_VERSION, true );
        wp_enqueue_script( 'ih-admin-messages', IH_URL . 'js/ih-admin-messages.js', array( 'ih-chat-states', 'ih-message-reactions', 'ih-chat-new-messages' ), IH_VERSION, true );
    }
    if ( $hook === 'toplevel_page_ih-dashboard' ) {
        wp_enqueue_style( 'ih-dashboard-light', IH_URL . 'css/ih-dashboard-light.css', [ 'ih-style' ], IH_VERSION );
    }
    // WP submenu hooks use sanitize_title( menu_title ) — "My Dashboard" → my-dashboard_page_*.
    $ih_user_corp_dash_hooks = array(
        'toplevel_page_ih-user-dashboard',
        'my-dashboard_page_ih-user-dashboard',
        'ih-user-dashboard_page_ih-user-dashboard',
    );
    $is_admin_figma_dash   = ( $hook === 'toplevel_page_ih-dashboard' );
    $is_user_corp_dash     = in_array( $hook, $ih_user_corp_dash_hooks, true )
        || $ih_page === 'ih-user-dashboard'
        || strpos( $hook, 'ih-user-dashboard' ) !== false;
    $figma_dash_hooks      = array( 'toplevel_page_ih-dashboard', 'toplevel_page_ih-user-dashboard' );
    if ( $is_admin_figma_dash || $is_user_corp_dash || in_array( $hook, $figma_dash_hooks, true ) ) {
        wp_enqueue_style( 'ih-dashboard-figma', IH_URL . 'css/ih-dashboard-figma.css', [ 'ih-style', 'ih-figma-fonts', 'ih-figma-tokens', 'ih-site-nav' ], IH_VERSION );
        wp_enqueue_script( 'ih-dashboard-figma', IH_URL . 'js/ih-dashboard-figma.js', [ 'ih-main', 'ih-site-nav' ], IH_VERSION, true );

        // ── v2.5 redesign theme — must load LAST so it wins over legacy dashboard CSS.
        //    The old Chart.js admin bundle (ih-dashboard-admin + chart-js) is no longer
        //    enqueued: the v2.5 admin dashboard uses inline SVG/CSS charts instead. ──
        $rd_deps = array( 'ih-figma-fonts', 'ih-figma-tokens', 'ih-site-nav', 'ih-dashboard-figma' );
        if ( $is_admin_figma_dash ) {
            $rd_deps[] = 'ih-dashboard-light';
        }
        wp_enqueue_style( 'ih-redesign', IH_URL . 'css/ih-redesign.css', $rd_deps, IH_VERSION );
        wp_enqueue_script( 'ih-redesign', IH_URL . 'js/ih-redesign.js', array( 'ih-main' ), IH_VERSION, true );
        if ( $is_admin_figma_dash ) {
            wp_enqueue_style( 'ih-admin-dashboard-mobile', IH_URL . 'css/ih-admin-dashboard-mobile.css', array( 'ih-redesign' ), IH_VERSION );
        }
        if ( $is_user_corp_dash ) {
            wp_enqueue_style( 'ih-corp-dashboard', IH_URL . 'css/ih-corp-dashboard.css', array( 'ih-redesign' ), IH_VERSION );
            wp_enqueue_style( 'ih-corp-dashboard-mobile', IH_URL . 'css/ih-corp-dashboard-mobile.css', array( 'ih-corp-dashboard' ), IH_VERSION );
            wp_enqueue_script( 'ih-corp-dashboard', IH_URL . 'js/ih-corp-dashboard.js', array( 'ih-main', 'ih-redesign' ), IH_VERSION, true );
        }
    }
    // User portal hooks use the ih-user-* slug prefix (ih-user-dashboard, ih-user-messages, …).
    // Do not match admin ih-users — that substring falsely contains "ih-user".
    if ( strpos( $hook, 'ih-user-' ) !== false ) {
        wp_enqueue_style( 'ih-user-shell', IH_URL . 'css/ih-user-shell.css', [ 'ih-style', 'ih-figma-tokens', 'ih-site-nav' ], IH_VERSION );
        wp_enqueue_script( 'ih-user-shell', IH_URL . 'js/ih-user-shell.js', [ 'ih-main', 'ih-site-nav' ], IH_VERSION, true );
        wp_enqueue_style( 'ih-user-float-nav', IH_URL . 'css/ih-user-float-nav.css', [ 'ih-user-shell', 'ih-figma-tokens' ], IH_VERSION );
        wp_enqueue_script( 'ih-user-float-nav', IH_URL . 'js/ih-user-float-nav.js', [ 'ih-main', 'ih-site-nav' ], IH_VERSION, true );
    }
    // User submenu hooks use sanitize_title( 'My Dashboard' ) → my-dashboard_page_{slug}.
    $ih_user_messages_hooks = array(
        'my-dashboard_page_ih-user-messages',
        'ih-user-dashboard_page_ih-user-messages',
    );
    if ( in_array( $hook, $ih_user_messages_hooks, true ) || $ih_page === 'ih-user-messages' ) {
        wp_enqueue_style( 'ih-figma-mobile', IH_URL . 'css/ih-figma-mobile.css', [ 'ih-style', 'ih-user-shell' ], IH_VERSION );
        wp_enqueue_style( 'ih-user-messages', IH_URL . 'css/ih-user-messages.css', [ 'ih-style', 'ih-figma-tokens', 'ih-user-shell' ], IH_VERSION );
        wp_enqueue_script( 'ih-chat-states', IH_URL . 'js/ih-chat-states.js', array(), IH_VERSION, true );
        wp_enqueue_script( 'ih-chat-new-messages', IH_URL . 'js/ih-chat-new-messages.js', array(), IH_VERSION, true );
        wp_enqueue_script( 'ih-message-reactions', IH_URL . 'js/ih-message-reactions.js', array(), IH_VERSION, true );
        wp_enqueue_script( 'ih-user-messages', IH_URL . 'js/ih-user-messages.js', array( 'ih-chat-states', 'ih-message-reactions', 'ih-chat-new-messages' ), IH_VERSION, true );
    }
    // ── Requester ↔ owner listing-messaging inbox (user + admin consoles).
    //    Shares the console look (reuses ih-user-messages.css / ih-admin-messages.css)
    //    with a small supplemental stylesheet, and one polling JS bundle. ──
    $ih_rmsg_user_hooks = array(
        'my-dashboard_page_ih-user-request-messages',
        'ih-user-dashboard_page_ih-user-request-messages',
    );
    $ih_rmsg_is_user  = in_array( $hook, $ih_rmsg_user_hooks, true ) || $ih_page === 'ih-user-request-messages';
    $ih_rmsg_is_admin = ( $hook === 'insight-hub_page_ih-request-messages' ) || $ih_page === 'ih-request-messages';
    if ( $ih_rmsg_is_user || $ih_rmsg_is_admin ) {
        if ( $ih_rmsg_is_admin ) {
            $rd_deps = array( 'ih-figma-fonts', 'ih-figma-tokens', 'ih-site-nav' );
            wp_enqueue_style( 'ih-redesign', IH_URL . 'css/ih-redesign.css', $rd_deps, IH_VERSION );
            wp_enqueue_style( 'ih-admin-messages', IH_URL . 'css/ih-admin-messages.css', array( 'ih-redesign', 'ih-figma-tokens' ), IH_VERSION );
            $rmsg_base = 'ih-admin-messages';
        } else {
            wp_enqueue_style( 'ih-figma-mobile', IH_URL . 'css/ih-figma-mobile.css', [ 'ih-style', 'ih-user-shell' ], IH_VERSION );
            wp_enqueue_style( 'ih-user-messages', IH_URL . 'css/ih-user-messages.css', [ 'ih-style', 'ih-figma-tokens', 'ih-user-shell' ], IH_VERSION );
            $rmsg_base = 'ih-user-messages';
        }
        wp_enqueue_style( 'ih-request-messaging', IH_URL . 'css/ih-request-messaging.css', array( $rmsg_base ), IH_VERSION );
        wp_enqueue_script( 'ih-request-messaging', IH_URL . 'js/ih-request-messaging.js', array(), IH_VERSION, true );
        wp_localize_script( 'ih-request-messaging', 'IH_RMSG', array(
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'ih_nonce' ),
            'meId'        => (int) get_current_user_id(),
            'isAdmin'     => current_user_can( 'manage_options' ),
            'pollMs'      => 12000,
            'maxUploadMb' => 25,
        ) );
    }
    if ( strpos( $hook, 'ih-' ) !== false && strpos( $hook, 'ih-user-' ) === false ) {
        wp_enqueue_style( 'ih-admin-sidebar-figma', IH_URL . 'css/ih-admin-sidebar-figma.css', [ 'ih-figma-tokens', 'ih-site-nav' ], IH_VERSION );
        wp_enqueue_style( 'ih-nav-clip-tab', IH_URL . 'css/ih-nav-clip-tab.css', [ 'ih-admin-sidebar-figma' ], IH_VERSION );
        wp_enqueue_style( 'ih-admin-mobile', IH_URL . 'css/ih-admin-mobile.css', [ 'ih-admin-sidebar-figma', 'ih-figma-tokens', 'ih-nav-clip-tab' ], IH_VERSION );
    }
    if ( $hook === 'insight-hub_page_ih-users' ) {
        wp_enqueue_style( 'ih-users-redesign', IH_URL . 'css/ih-users-redesign.css', array( 'ih-redesign' ), IH_VERSION );
        wp_enqueue_script( 'ih-users-redesign', IH_URL . 'js/ih-users-redesign.js', array( 'jquery', 'ih-main' ), IH_VERSION, true );
        wp_localize_script( 'ih-users-redesign', 'ihUsersRedesign', [
            'wsUrl'   => get_option( 'ih_users_ws_url', get_option( 'insidehub_ws_url', '' ) ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ih_nonce' ),
        ] );
    }
    // v2.5 dark theme tokens on remaining admin management screens.
    $ih_admin_rd_hooks = array(
        'insight-hub_page_ih-machines',
        'insight-hub_page_ih-tools',
        'insight-hub_page_ih-users',
        'insight-hub_page_ih-activity',
        'insight-hub_page_ih-settings',
    );
    if ( in_array( $hook, $ih_admin_rd_hooks, true ) ) {
        $rd_deps = array( 'ih-figma-fonts', 'ih-figma-tokens', 'ih-site-nav' );
        wp_enqueue_style( 'ih-redesign', IH_URL . 'css/ih-redesign.css', $rd_deps, IH_VERSION );
    }
    if ( $hook === 'insight-hub_page_ih-machines' ) {
        wp_enqueue_style( 'ih-machines-card', IH_URL . 'css/ih-machines-card.css', array( 'ih-redesign', 'ih-figma-tokens' ), IH_VERSION );
    }
    if ( $hook === 'insight-hub_page_ih-tools' ) {
        wp_enqueue_style( 'ih-tools-card', IH_URL . 'css/ih-tools-card.css', array( 'ih-redesign', 'ih-figma-tokens' ), IH_VERSION );
    }
    // WP builds submenu hooks from sanitize_title( menu_title ), not menu_slug:
    //   admin  parent "Insight Hub"  → insight-hub_page_ih-add-tool
    //   user   parent "My Dashboard" → my-dashboard_page_ih-user-add-tool
    $ih_add_tool_hooks = array(
        'my-dashboard_page_ih-user-add-tool',
        'insight-hub_page_ih-add-tool',
    );
    if ( in_array( $hook, $ih_add_tool_hooks, true ) || in_array( $ih_page, array( 'ih-add-tool', 'ih-user-add-tool' ), true ) ) {
        wp_enqueue_style( 'ih-add-tool', IH_URL . 'css/ih-add-tool.css', array( 'ih-style', 'ih-figma-tokens' ), IH_VERSION );
        wp_enqueue_style( 'ih-add-tool-mobile', IH_URL . 'css/ih-add-tool-mobile.css', array( 'ih-add-tool' ), IH_VERSION );
        wp_enqueue_script( 'ih-add-tool', IH_URL . 'js/ih-add-tool.js', array(), IH_VERSION, true );
    }
    $ih_add_machine_hooks = array(
        'my-dashboard_page_ih-user-add-machine',
        'insight-hub_page_ih-add-machine',
    );
    if ( in_array( $hook, $ih_add_machine_hooks, true ) || in_array( $ih_page, array( 'ih-add-machine', 'ih-user-add-machine' ), true ) ) {
        wp_enqueue_style( 'ih-add-machine', IH_URL . 'css/ih-add-machine.css', array( 'ih-style', 'ih-figma-tokens' ), IH_VERSION );
        wp_enqueue_style( 'ih-add-machine-mobile', IH_URL . 'css/ih-add-machine-mobile.css', array( 'ih-add-machine' ), IH_VERSION );
        wp_enqueue_script( 'ih-add-machine', IH_URL . 'js/ih-add-machine.js', array(), IH_VERSION, true );
    }

    /* Stale-nonce recovery for every nonce-protected add/edit listing form (machine
     * + tool, admin + owner). Enqueued LAST so its submit listener registers after
     * each form's own validation handler (and therefore runs after it). */
    $ih_nonce_pages = array(
        'ih-add-machine', 'ih-user-add-machine',
        'ih-add-tool', 'ih-user-add-tool',
        'ih-edit-machine', 'ih-user-edit-machine',
        'ih-edit-tool', 'ih-user-edit-tool',
    );
    if ( in_array( $ih_page, $ih_nonce_pages, true ) ) {
        wp_enqueue_script( 'ih-nonce-refresh', IH_URL . 'js/ih-nonce-refresh.js', array(), IH_VERSION, true );
        wp_localize_script( 'ih-nonce-refresh', 'IH_NONCE_REFRESH', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        ) );
    }

    /* Field info-popovers (ih-infotips). Loaded on the plugin's own add/edit
     * machine + tool forms (admin + owner). The module auto-wires by input
     * `name`, so simply enqueueing it on the form pages is enough; CSS first. */
    $ih_infotip_pages = array(
        'ih-add-machine', 'ih-user-add-machine',
        'ih-add-tool', 'ih-user-add-tool',
        'ih-edit-machine', 'ih-user-edit-machine',
        'ih-edit-tool', 'ih-user-edit-tool',
    );
    if ( in_array( $ih_page, $ih_infotip_pages, true ) ) {
        wp_enqueue_style( 'ih-infotips', IH_URL . 'css/ih-infotips.css', array( 'ih-figma-tokens' ), IH_VERSION );
        wp_enqueue_script( 'ih-infotips', IH_URL . 'js/ih-infotips.js', array(), IH_VERSION, true );
    }
});

/* ═══════════════════════════════════════
   COST-CALCULATOR ASSET HELPER (material-pricing.js)
   Reusable enqueue + localization for the live-resin cost calculator. The
   calculator markup lives on the THEME tool-detail page (owned by another
   worker), so material-pricing.js is intentionally NOT auto-enqueued here.
   The theme calls ih_enqueue_material_pricing_calculator() on its page to get
   a turnkey wiring: the script plus the globals the module reads —
     window.IH_PRICING_API  = REST base  ih/v1
     window.IH_NONCE         = wp_rest nonce (for the staff override POST)
     window.IH_PLATFORM_CHARGE_PCT / window.IH_TRANSACTION_FEE_PCT (filterable)
═══════════════════════════════════════ */
function ih_pricing_inline_globals() {
    return sprintf(
        'window.IH_PRICING_API=%s;window.IH_NONCE=%s;window.IH_PLATFORM_CHARGE_PCT=%s;window.IH_TRANSACTION_FEE_PCT=%s;',
        wp_json_encode( esc_url_raw( rest_url( 'ih/v1' ) ) ),
        wp_json_encode( wp_create_nonce( 'wp_rest' ) ),
        wp_json_encode( (float) apply_filters( 'ih_platform_charge_pct', 5 ) ),
        wp_json_encode( (float) apply_filters( 'ih_transaction_fee_pct', 2 ) )
    );
}

/**
 * Enqueue the cost calculator client (material-pricing.js) and expose the
 * globals it relies on. Safe to call from any page that renders the
 * [data-ih-cost-calc] markup (e.g. the theme tool-detail template).
 *
 * @param array $deps Optional script dependencies to attach.
 */
function ih_enqueue_material_pricing_calculator( $deps = array() ) {
    if ( ! wp_script_is( 'ih-material-pricing', 'registered' ) ) {
        wp_register_script( 'ih-material-pricing', IH_URL . 'js/material-pricing.js', $deps, IH_VERSION, true );
    }
    wp_enqueue_script( 'ih-material-pricing' );
    wp_add_inline_script( 'ih-material-pricing', ih_pricing_inline_globals(), 'before' );
}

/* Register the calculator script early (front-end + admin) so the handle is
 * always available for ih_enqueue_material_pricing_calculator() and so inline
 * globals can attach even if the script is enqueued by another component. */
function ih_pricing_register_assets() {
    if ( ! wp_script_is( 'ih-material-pricing', 'registered' ) ) {
        wp_register_script( 'ih-material-pricing', IH_URL . 'js/material-pricing.js', array(), IH_VERSION, true );
    }
    // Make the info-popover assets available to the theme tool-detail page too
    // (detail/browse usage is attribute-driven via data-tip); enqueue remains
    // the caller's responsibility.
    if ( ! wp_style_is( 'ih-infotips', 'registered' ) ) {
        wp_register_style( 'ih-infotips', IH_URL . 'css/ih-infotips.css', array(), IH_VERSION );
    }
    if ( ! wp_script_is( 'ih-infotips', 'registered' ) ) {
        wp_register_script( 'ih-infotips', IH_URL . 'js/ih-infotips.js', array(), IH_VERSION, true );
    }
}
add_action( 'wp_enqueue_scripts', 'ih_pricing_register_assets' );
add_action( 'admin_enqueue_scripts', 'ih_pricing_register_assets' );

/* Site-wide Figma bottom navigation */
add_filter( 'admin_body_class', function ( $classes ) {
    if ( ! ih_site_nav_should_render() ) {
        return $classes;
    }
    $classes .= ' ih-has-site-nav ih-site-nav-active ih-site-nav-' . ih_site_nav_mode();
    $page = ih_site_nav_current_page();
    if ( $page && strpos( $page, 'ih-user-' ) === 0 && ! current_user_can( 'manage_options' ) ) {
        $classes .= ' ih-user-portal ih-user-page';
        if ( $page === 'ih-user-dashboard' ) {
            $classes .= ' ih-corp-dash-active';
        }
    }
    return $classes;
} );

add_action( 'admin_footer', function () {
    if ( ! ih_site_nav_should_render() ) {
        return;
    }
    if ( ! empty( $GLOBALS['ih_site_nav_rendered'] ) ) {
        return;
    }
    ih_render_site_bottom_nav();
}, 99 );

/* ═══════════════════════════════════════
   USER AVATAR (uploaded profile image site-wide)
═══════════════════════════════════════ */
function ih_get_user_avatar_url( $user_id, $size = 96 ) {
    $user_id = (int) $user_id;
    if ( ! $user_id ) {
        return '';
    }

    /* Reentrancy guard: the 'get_avatar_url' filter below calls this function,
       so it must never be allowed to re-enter itself for the same user. */
    static $resolving = array();
    if ( isset( $resolving[ $user_id ] ) ) {
        return '';
    }
    $resolving[ $user_id ] = true;

    $url = trim( (string) get_user_meta( $user_id, 'ih_profile_image', true ) );
    if ( $url && filter_var( $url, FILTER_VALIDATE_URL ) ) {
        unset( $resolving[ $user_id ] );
        return esc_url_raw( $url );
    }

    $img_id = (int) get_user_meta( $user_id, 'ih_profile_image_id', true );
    if ( $img_id ) {
        $attached = wp_get_attachment_image_url( $img_id, array( $size, $size ) );
        if ( $attached ) {
            unset( $resolving[ $user_id ] );
            return esc_url_raw( $attached );
        }
    }

    /* Build the gravatar URL manually instead of calling get_avatar_url():
       core's get_avatar_url() applies the 'get_avatar_url' filter, which is
       hooked below and calls back into this function (infinite recursion →
       PHP-FPM crash → 502). Manual construction never touches the filter chain. */
    $gravatar = '';
    $user     = get_userdata( $user_id );
    if ( $user && ! empty( $user->user_email ) ) {
        $hash     = md5( strtolower( trim( $user->user_email ) ) );
        $gravatar = 'https://www.gravatar.com/avatar/' . $hash . '?s=' . (int) $size . '&d=404';
    }

    unset( $resolving[ $user_id ] );
    return $gravatar ? esc_url_raw( $gravatar ) : '';
}

function ih_get_user_avatar_fallback_url( $user_id, $size = 96 ) {
    $user = get_userdata( $user_id );
    $name = $user ? ( $user->display_name ?: $user->user_login ?: 'U' ) : 'U';
    return 'https://ui-avatars.com/api/?name=' . rawurlencode( $name ) . '&size=' . (int) $size . '&background=1f3d2e&color=c8e88e&bold=true&rounded=true';
}

/* Prefer uploaded profile image everywhere WordPress avatars are used. */
add_filter( 'get_avatar_url', function ( $url, $id_or_email, $args ) {
    $user_id = 0;
    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;
    } elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) {
        $user_id = (int) $id_or_email->user_id;
    } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
        $u = get_user_by( 'email', $id_or_email );
        $user_id = $u ? (int) $u->ID : 0;
    }
    if ( ! $user_id ) {
        return $url;
    }
    $custom = ih_get_user_avatar_url( $user_id, isset( $args['size'] ) ? (int) $args['size'] : 96 );
    if ( $custom && strpos( $custom, 'gravatar.com' ) === false && strpos( $custom, 'ui-avatars.com' ) === false ) {
        return $custom;
    }
    $stored = trim( (string) get_user_meta( $user_id, 'ih_profile_image', true ) );
    return $stored ? esc_url_raw( $stored ) : $url;
}, 10, 3 );

/* ═══════════════════════════════════════
   AJAX HELPERS
═══════════════════════════════════════ */
function ih_require_admin_ajax() {
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
}

function ih_get_thread_by_user_id( $user_id ) {
    global $wpdb;
    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ih_threads WHERE user_id=%d ORDER BY id DESC LIMIT 1", $user_id
    ) );
}

function ih_set_user_block_state( $user_id, $blocked ) {
    global $wpdb;
    $user_id = (int) $user_id; $blocked = $blocked ? 1 : 0;
    if ( ! $user_id ) return false;
    $status = $blocked ? 'Rejected' : 'Approved';
    $table  = $wpdb->prefix . 'ih_user_meta';
    $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id=%d", $user_id));
    if ( $exists ) return $wpdb->update($table, ['blocked'=>$blocked,'status'=>$status], ['user_id'=>$user_id], ['%d','%s'], ['%d']);
    return $wpdb->insert($table, ['user_id'=>$user_id,'blocked'=>$blocked,'status'=>$status,'joined'=>current_time('Y-m-d')], ['%d','%d','%s','%s']);
}

function ih_get_admin_whatsapp_number() {
    return preg_replace( '/\D/', '', (string) get_option( 'ih_whatsapp_number', '' ) );
}

function ih_build_admin_whatsapp_link( $user_id = 0 ) {
    $number = ih_get_admin_whatsapp_number();
    if ( ! $number ) return '';
    $user = $user_id ? get_userdata( $user_id ) : null;
    $user_name = $user && $user->display_name ? $user->display_name : 'there';
    $text = sprintf('Hi Admin, this is %s. My listing/request was approved. Please share next steps.', $user_name);
    return 'https://wa.me/' . $number . '?text=' . rawurlencode( $text );
}

function ih_send_user_approval_whatsapp_email( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user || empty( $user->user_email ) ) return false;
    $wa_link = ih_build_admin_whatsapp_link( $user_id );
    if ( ! $wa_link ) return false;
    $subject = 'Your listing is approved - Contact Admin on WhatsApp';
    $message = "Hi {$user->display_name},\n\nGreat news! Your listing/request has been approved.\nUse this WhatsApp link to contact admin directly:\n{$wa_link}\n\nThanks,\nInjection Moulding Team";
    return wp_mail( $user->user_email, $subject, $message );
}

function ih_get_listing_message_action_url( $listing_id, $listing_type = 'machine' ) {
    return add_query_arg(['action'=>'ih_listing_message_click','listing_id'=>intval($listing_id),'listing_type'=>sanitize_key($listing_type)], admin_url('admin-post.php'));
}

function ih_ensure_thread_for_request( $user_id, $listing_id, $listing_type ) {
    global $wpdb;
    $user_id = intval( $user_id );
    if ( ! $user_id ) return 0;
    $thread_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ih_threads WHERE user_id=%d ORDER BY id DESC LIMIT 1",$user_id));
    if ( $thread_id ) return $thread_id;
    $wpdb->insert($wpdb->prefix.'ih_threads',['user_id'=>$user_id,'listing_id'=>intval($listing_id),'listing_type'=>in_array($listing_type,['machine','tool'],true)?$listing_type:'machine','last_message'=>'','last_time'=>current_time('mysql', true),'unread'=>0],['%d','%d','%s','%s','%s','%d']);
    return (int) $wpdb->insert_id;
}

function ih_get_or_create_request_user( $name, $email, $phone = '' ) {
    global $wpdb;
    $email = sanitize_email( $email );
    if ( ! $email || ! is_email( $email ) ) return new WP_Error( 'invalid_email', 'Valid email is required.' );
    $existing = get_user_by( 'email', $email );
    if ( $existing ) {
        $user_id = (int) $existing->ID;
    } else {
        $base = sanitize_user( current( explode( '@', $email ) ), true ) ?: 'ih_user';
        $username = $base; $i = 1;
        while ( username_exists( $username ) ) { $username = $base . $i; $i++; }
        $user_id = wp_create_user( $username, wp_generate_password( 20, true, true ), $email );
        if ( is_wp_error( $user_id ) ) return $user_id;
    }
    if ( $name ) wp_update_user( [ 'ID' => $user_id, 'display_name' => sanitize_text_field( $name ) ] );
    $meta_table = $wpdb->prefix . 'ih_user_meta';
    $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$meta_table} WHERE user_id=%d", $user_id));
    if ( $exists ) {
        $wpdb->update($meta_table, ['phone'=>sanitize_text_field($phone),'status'=>'Pending'], ['user_id'=>$user_id], ['%s','%s'], ['%d']);
    } else {
        $wpdb->insert($meta_table, ['user_id'=>$user_id,'phone'=>sanitize_text_field($phone),'status'=>'Pending','joined'=>current_time('Y-m-d')], ['%d','%s','%s','%s']);
    }
    return (int) $user_id;
}

function ih_send_admin_new_request_email( $request_id, array $payload ) {
    $admin_email = get_option( 'ih_admin_notify_email' ) ?: get_option( 'admin_email' );
    if ( ! $admin_email || ! is_email( $admin_email ) ) return false;
    $messages_url = admin_url( 'admin.php?page=ih-messages' );
    $body = "A new request was submitted.\n\nRequest ID: {$request_id}\nName: ".($payload['name']??'')."\nEmail: ".($payload['email']??'')."\nPhone: ".($payload['phone']??'')."\nListing Type: ".($payload['listing_type']??'machine')."\nListing ID: ".($payload['listing_id']??0)."\n\nOpen dashboard:\n{$messages_url}\n";
    return wp_mail( $admin_email, 'New listing request submitted', $body );
}

function ih_send_user_request_received_email( $email, $name, $request_id ) {
    $email = sanitize_email( $email );
    if ( ! $email || ! is_email( $email ) ) return false;
    $name = $name ? sanitize_text_field( $name ) : 'there';
    $body = "Hi {$name},\n\nWe have received your request (ID: {$request_id}).\nOur admin will review it shortly.\nOnce approved, we will share the admin WhatsApp link with you.\n\nThanks,\nInjection Moulding Team";
    return wp_mail( $email, 'Request received - pending admin approval', $body );
}

/* ═══════════════════════════════════════
   ★ CHANGE 2: ih_create_request_and_notify()
   ih_sync_user_meta() call add kiya — request
   aane pe saari details user meta mein save hongi
═══════════════════════════════════════ */
function ih_create_request_and_notify( array $input ) {
    global $wpdb;

    $name         = sanitize_text_field( $input['name'] ?? '' );
    $email        = sanitize_email( $input['email'] ?? '' );
    $phone        = sanitize_text_field( $input['phone'] ?? '' );
    $listing_id   = intval( $input['listing_id'] ?? 0 );
    $listing_type = sanitize_key( $input['listing_type'] ?? 'machine' );
    $message      = sanitize_textarea_field( $input['message'] ?? '' );

    if ( ! in_array( $listing_type, [ 'machine', 'tool' ], true ) ) $listing_type = 'machine';
    if ( ! $email || ! is_email( $email ) ) return new WP_Error( 'invalid_email', 'Please provide a valid email.' );

    $user_id = is_user_logged_in() ? get_current_user_id() : 0;
    if ( ! $user_id ) {
        $user_id = ih_get_or_create_request_user( $name, $email, $phone );
        if ( is_wp_error( $user_id ) ) return $user_id;
    }

    if ( $listing_id && in_array( $listing_type, array( 'machine', 'tool' ), true ) && function_exists( 'ih_listing_is_expired' ) ) {
        $listing_table = $listing_type === 'tool' ? $wpdb->prefix . 'ih_tools' : $wpdb->prefix . 'ih_machines';
        $listing_row   = $wpdb->get_row( $wpdb->prepare( "SELECT id, owner_id, expiry_date FROM {$listing_table} WHERE id=%d", $listing_id ), ARRAY_A );
        $is_owner      = $listing_row && (int) ( $listing_row['owner_id'] ?? 0 ) === (int) $user_id;
        if ( $listing_row && ih_listing_is_expired( $listing_row ) && ! $is_owner && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'listing_expired', 'This listing has expired and is no longer publicly available.' );
        }
    }

    /* ── Request ke saath aaye saare fields user meta mein sync karo ──
       Ab user-detail.php mein phone, city, company, address sab dikhengi */
    ih_sync_user_meta( $user_id, array_merge( $input, [
        'phone'   => $phone,
        'company' => $input['company_name'] ?? $input['company'] ?? '',
        'city'    => $input['city'] ?? $input['location'] ?? '',
    ] ) );

    if ( $user_id && $listing_id && in_array( $listing_type, array( 'machine', 'tool' ), true ) ) {
        $dup_pending = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND listing_id=%d AND listing_type=%s AND LOWER(TRIM(status))='pending' ORDER BY id DESC LIMIT 1",
            (int) $user_id,
            (int) $listing_id,
            $listing_type
        ) );
        if ( $dup_pending ) {
            return array( 'request_id' => $dup_pending, 'user_id' => (int) $user_id, 'status' => 'Pending', 'existing' => true );
        }
    }

    $ok = $wpdb->insert( $wpdb->prefix . 'ih_requests', [
        'user_id'      => (int) $user_id,
        'listing_id'   => $listing_id,
        'listing_type' => $listing_type,
        'request_date' => current_time( 'Y-m-d' ),
        'status'       => 'Pending',
    ], [ '%d', '%d', '%s', '%s', '%s' ] );

    if ( $ok === false ) return new WP_Error( 'db_insert_failed', 'Could not save your request.' );

    $request_id = (int) $wpdb->insert_id;
    ih_log_activity( 'request', 'New ' . $listing_type . ' request #' . $request_id . ' from ' . $name, [ 'request_id' => $request_id, 'user_id' => (int) $user_id, 'listing_id' => $listing_id, 'listing_type' => $listing_type ] );
    ih_send_admin_new_request_email( $request_id, ['name'=>$name,'email'=>$email,'phone'=>$phone,'listing_type'=>$listing_type,'listing_id'=>$listing_id] );
    ih_send_user_request_received_email( $email, $name, $request_id );

    if ( $message ) {
        $thread_id = ih_get_thread_by_user_id( $user_id );
        if ( ! $thread_id ) {
            $wpdb->insert($wpdb->prefix.'ih_threads',['user_id'=>(int)$user_id,'listing_id'=>$listing_id,'listing_type'=>$listing_type,'last_message'=>$message,'last_time'=>current_time('mysql', true),'unread'=>1]);
            $thread_id = (int) $wpdb->insert_id;
        }
        if ( $thread_id ) {
            $wpdb->insert($wpdb->prefix.'ih_chats',['thread_id'=>$thread_id,'from_me'=>0,'message'=>$message,'sent_at'=>current_time('mysql', true)]);
            $wpdb->update($wpdb->prefix.'ih_threads',['last_message'=>$message,'last_time'=>current_time('mysql', true),'unread'=>1],['id'=>$thread_id]);
        }
    }

    return ['request_id'=>$request_id,'user_id'=>(int)$user_id,'status'=>'Pending'];
}

/* ── AJAX: Request submit ── */
add_action( 'wp_ajax_ih_submit_request', function() {
    $nonce_ok = check_ajax_referer( 'ih_request_submit', 'nonce', false );
    if ( ! $nonce_ok ) wp_send_json_error( [ 'message' => 'Security validation failed.' ], 403 );
    $result = ih_create_request_and_notify( $_POST );
    if ( is_wp_error( $result ) ) wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
    wp_send_json_success( $result );
} );
add_action( 'wp_ajax_nopriv_ih_submit_request', function() {
    $nonce_ok = check_ajax_referer( 'ih_request_submit', 'nonce', false );
    if ( ! $nonce_ok ) wp_send_json_error( [ 'message' => 'Security validation failed.' ], 403 );
    $result = ih_create_request_and_notify( $_POST );
    if ( is_wp_error( $result ) ) wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
    wp_send_json_success( $result );
} );

add_action( 'admin_post_ih_submit_request', function() {
    if ( ! isset( $_POST['ih_request_nonce'] ) || ! wp_verify_nonce( $_POST['ih_request_nonce'], 'ih_request_submit' ) ) wp_die( 'Security check failed' );
    $result = ih_create_request_and_notify( $_POST );
    $back = wp_get_referer() ?: home_url( '/' );
    if ( is_wp_error( $result ) ) { wp_safe_redirect( add_query_arg( [ 'ih_req'=>'error','ih_msg'=>rawurlencode($result->get_error_message()) ], $back ) ); exit; }
    wp_safe_redirect( add_query_arg( [ 'ih_req'=>'ok','ih_request_id'=>$result['request_id'] ], $back ) );
    exit;
} );
add_action( 'admin_post_nopriv_ih_submit_request', function() {
    if ( ! isset( $_POST['ih_request_nonce'] ) || ! wp_verify_nonce( $_POST['ih_request_nonce'], 'ih_request_submit' ) ) wp_die( 'Security check failed' );
    $result = ih_create_request_and_notify( $_POST );
    $back = wp_get_referer() ?: home_url( '/' );
    if ( is_wp_error( $result ) ) { wp_safe_redirect( add_query_arg( [ 'ih_req'=>'error','ih_msg'=>rawurlencode($result->get_error_message()) ], $back ) ); exit; }
    wp_safe_redirect( add_query_arg( [ 'ih_req'=>'ok','ih_request_id'=>$result['request_id'] ], $back ) );
    exit;
} );

add_action( 'admin_post_ih_listing_message_click', function() {
    global $wpdb;
    $listing_id   = intval( $_GET['listing_id'] ?? 0 );
    $listing_type = sanitize_key( $_GET['listing_type'] ?? 'machine' );
    if ( ! in_array( $listing_type, [ 'machine', 'tool' ], true ) ) $listing_type = 'machine';
    $back = wp_get_referer() ?: home_url( '/' );
    if ( ! is_user_logged_in() ) { wp_safe_redirect( wp_login_url( add_query_arg(['action'=>'ih_listing_message_click','listing_id'=>$listing_id,'listing_type'=>$listing_type], admin_url('admin-post.php')) ) ); exit; }
    $user_id = get_current_user_id();
    $approved_req = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND listing_id=%d AND listing_type=%s AND status='Approved' ORDER BY id DESC LIMIT 1",$user_id,$listing_id,$listing_type));
    if ( $approved_req ) {
        ih_ensure_thread_for_request($user_id,$listing_id,$listing_type);
        $wa_link = ih_build_admin_whatsapp_link($user_id);
        wp_safe_redirect( $wa_link ?: add_query_arg(['ih_req'=>'approved','ih_request_id'=>$approved_req],$back) ); exit;
    }
    $pending_req = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND listing_id=%d AND listing_type=%s AND status IN ('Pending','Rejected') ORDER BY id DESC LIMIT 1",$user_id,$listing_id,$listing_type));
    if ( $pending_req ) { wp_safe_redirect( add_query_arg(['ih_req'=>'pending','ih_request_id'=>$pending_req],$back) ); exit; }
    $user  = get_userdata($user_id);
    $phone = get_user_meta($user_id,'phone',true) ?: get_user_meta($user_id,'ih_phone',true);
    $result = ih_create_request_and_notify(['name'=>$user?$user->display_name:'','email'=>$user?$user->user_email:'','phone'=>$phone,'listing_id'=>$listing_id,'listing_type'=>$listing_type,'message'=>'']);
    if ( is_wp_error($result) ) { wp_safe_redirect( add_query_arg(['ih_req'=>'error','ih_msg'=>rawurlencode($result->get_error_message())],$back) ); exit; }
    wp_safe_redirect( add_query_arg(['ih_req'=>'pending','ih_request_id'=>$result['request_id']],$back) ); exit;
} );
add_action( 'admin_post_nopriv_ih_listing_message_click', function() {
    $listing_id   = intval( $_GET['listing_id'] ?? 0 );
    $listing_type = sanitize_key( $_GET['listing_type'] ?? 'machine' );
    if ( ! in_array( $listing_type, [ 'machine', 'tool' ], true ) ) $listing_type = 'machine';
    wp_safe_redirect( wp_login_url( add_query_arg(['action'=>'ih_listing_message_click','listing_id'=>$listing_id,'listing_type'=>$listing_type], admin_url('admin-post.php')) ) ); exit;
} );

add_shortcode( 'ih_request_form', function( $atts ) {
    $atts = shortcode_atts(['listing_id'=>0,'listing_type'=>'machine'], $atts, 'ih_request_form');
    ob_start(); ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ih-public-request-form">
      <input type="hidden" name="action" value="ih_submit_request">
      <input type="hidden" name="listing_id" value="<?php echo esc_attr(intval($atts['listing_id'])); ?>">
      <input type="hidden" name="listing_type" value="<?php echo esc_attr(sanitize_key($atts['listing_type'])); ?>">
      <?php wp_nonce_field('ih_request_submit','ih_request_nonce'); ?>
      <p><label>Name<br><input type="text" name="name" required></label></p>
      <p><label>Email<br><input type="email" name="email" required></label></p>
      <p><label>Phone<br><input type="text" name="phone"></label></p>
      <p><label>Company<br><input type="text" name="company"></label></p>
      <p><label>City/Town<br><input type="text" name="city"></label></p>
      <p><label>Message<br><textarea name="message" rows="4" required></textarea></label></p>
      <p><button type="submit">Submit Request</button></p>
    </form>
    <?php return ob_get_clean();
} );

/* ── AJAX: Messages ── */
add_action('wp_ajax_ih_send_message', function() {
    ih_require_admin_ajax();
    check_ajax_referer('ih_nonce','nonce');
    global $wpdb;
    $thread_id    = intval($_POST['thread_id'] ?? 0);
    $user_id_post = intval($_POST['user_id'] ?? 0);
    $msg          = sanitize_textarea_field($_POST['message'] ?? $_POST['text'] ?? '');
    if ( !$thread_id && $user_id_post ) $thread_id = ih_get_thread_by_user_id($user_id_post);
    if ( !$thread_id || empty($msg) ) wp_send_json_error('Message or Thread ID missing');
    $insert = ih_chat_delivery_insert_defaults([
        'thread_id'=>$thread_id,'from_me'=>1,'message'=>$msg,'sent_at'=>current_time('mysql', true)
    ]);
    $chat_cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ih_chats");
    $insert = array_intersect_key($insert, array_flip($chat_cols));
    $inserted = $wpdb->insert($wpdb->prefix.'ih_chats', $insert);
    if ( $inserted === false ) wp_send_json_error('Database error: Message not saved');
    $wpdb->update($wpdb->prefix.'ih_threads',['last_message'=>$msg,'last_time'=>current_time('mysql', true),'unread'=>0],['id'=>$thread_id]);
    wp_send_json_success(['id'=>$wpdb->insert_id,'message'=>$msg,'time'=>date_i18n('g:i A'),'delivery_status'=>'sent']);
});

add_action('wp_ajax_ih_block_user', function() {
    ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce');
    ih_set_user_block_state(intval($_POST['user_id']??0), intval($_POST['block']??0));
    wp_send_json_success();
});

add_action('wp_ajax_ih_get_chat_messages', function() {
    ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce');
    global $wpdb;
    $thread_id = intval($_GET['thread_id']??0);
    if (!$thread_id) wp_send_json_error('Invalid Thread ID');
    $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ih_chats WHERE thread_id=%d ORDER BY id ASC",$thread_id), ARRAY_A);
    $wpdb->update($wpdb->prefix.'ih_threads',['unread'=>0],['id'=>$thread_id]);
    wp_send_json_success($messages ?: []);
});

add_action('wp_ajax_ih_get_messages', function() {
    ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce');
    global $wpdb;
    $user_id   = intval($_GET['user_id']??0);
    $after_id  = intval($_GET['after_id']??0);
    $thread_id = ih_get_thread_by_user_id($user_id);
    if (!$thread_id) {
        wp_send_json_success([
            'messages'     => [],
            'thread_id'    => 0,
            'is_group'     => false,
            'participants' => [],
            'online_count' => 0,
            'group_title'  => '',
        ]);
    }
    $chat_cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ih_chats");
    $select    = 'id,from_me,message,sent_at';
    if (in_array('sender_id', $chat_cols, true)) {
        $select .= ',sender_id';
    }
    foreach ( array( 'attachment_url', 'attachment_type', 'attachment_name', 'attachment_size', 'reaction' ) as $ac ) {
        if ( in_array( $ac, $chat_cols, true ) ) {
            $select .= ',' . $ac;
        }
    }
    $rows = $wpdb->get_results($wpdb->prepare("SELECT {$select} FROM {$wpdb->prefix}ih_chats WHERE thread_id=%d AND id>%d ORDER BY id ASC",$thread_id,$after_id), ARRAY_A);
    $messages = [];
    foreach ($rows as $row) {
        $text = (string) $row['message'];
        $type = function_exists('ih_chat_detect_message_type') ? ih_chat_detect_message_type($text) : 'chat';
        $sender = isset($row['sender_id']) && $row['sender_id'] ? (int) $row['sender_id'] : ( (int) $row['from_me'] === 1 ? (int) get_current_user_id() : $user_id );
        $msg = [
            'id'        => (int) $row['id'],
            'sender_id' => $sender,
            'from_me'   => (int) $row['from_me'],
            'text'      => $text,
            'message'   => $text,
            'type'      => $type,
            'time'      => get_date_from_gmt($row['sent_at'], 'g:i A'),
            'is_read'   => 1,
        ];
        foreach ( array( 'attachment_url', 'attachment_type', 'attachment_name', 'attachment_size', 'reaction' ) as $ac ) {
            if ( isset( $row[ $ac ] ) && $row[ $ac ] !== '' && $row[ $ac ] !== null ) {
                $msg[ $ac ] = $row[ $ac ];
            }
        }
        if ($type === 'system' && function_exists('ih_chat_system_message_label')) {
            $msg['system_label'] = ih_chat_system_message_label($text);
        }
        if ( function_exists( 'ih_chat_outbound_delivery_status' ) ) {
            $out_status = ih_chat_outbound_delivery_status( $row, true );
            if ( $out_status ) {
                $msg['delivery_status'] = $out_status;
            }
        }
        if ( ! empty( $row['read_at'] ) ) {
            $msg['read_at'] = $row['read_at'];
        }
        $messages[] = $msg;
    }
    if ( function_exists( 'ih_chat_attach_reactions' ) ) {
        ih_chat_attach_reactions( $messages, get_current_user_id() );
    }
    $typing_uid = (int) get_transient( 'ih_typing_' . $thread_id );
    $typing     = $typing_uid > 0 && $typing_uid !== get_current_user_id();
    $wpdb->update($wpdb->prefix.'ih_threads',['unread'=>0],['id'=>$thread_id]);
    $conversation = function_exists('ih_chat_conversation_payload')
        ? ih_chat_conversation_payload($thread_id)
        : ['thread_id'=>$thread_id,'is_group'=>false,'participants'=>[],'online_count'=>0,'group_title'=>''];
    wp_send_json_success(array_merge($conversation, [
        'messages'          => $messages,
        'typing'            => $typing,
        'outbound_statuses' => function_exists( 'ih_chat_outbound_status_map' ) ? ih_chat_outbound_status_map( $thread_id, true ) : array(),
    ]));
});

add_action('wp_ajax_ih_delete_machine', function() { ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce'); global $wpdb; $mid=intval($_POST['id']??0); $mt=$wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}ih_machines WHERE id=%d",$mid)); $wpdb->delete($wpdb->prefix.'ih_machines',['id'=>$mid]); ih_log_activity('listing','Machine deleted: '.($mt?:('#'.$mid)),['machine_id'=>$mid]); wp_send_json_success(); });
add_action('wp_ajax_ih_delete_tool',    function() { ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce'); global $wpdb; $tid=intval($_POST['id']??0); $tt=$wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}ih_tools WHERE id=%d",$tid)); $wpdb->delete($wpdb->prefix.'ih_tools',['id'=>$tid]); ih_log_activity('listing','Tool deleted: '.($tt?:('#'.$tid)),['tool_id'=>$tid]); wp_send_json_success(); });

/* Owner-gated machine delete — an owner may delete ONLY their own listing.
 * Mirrors the admin ih_delete_machine handler but enforces ownership instead of
 * manage_options. Nonce: ih_nonce (same nonce IMD_DATA localises on the detail page). */
add_action('wp_ajax_ih_owner_delete_machine', function() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => 'Not logged in.' ], 403 );
    }
    check_ajax_referer( 'ih_nonce', 'nonce' );
    global $wpdb;
    $mid     = intval( $_POST['id'] ?? 0 );
    $user_id = get_current_user_id();
    if ( ! $mid ) {
        wp_send_json_error( [ 'message' => 'Invalid listing.' ] );
    }
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT owner_id, title FROM {$wpdb->prefix}ih_machines WHERE id=%d", $mid
    ), ARRAY_A );
    if ( ! $row ) {
        wp_send_json_error( [ 'message' => 'Listing not found.' ] );
    }
    if ( (int) $row['owner_id'] !== $user_id ) {
        wp_send_json_error( [ 'message' => 'You can only remove your own listing.' ], 403 );
    }
    $wpdb->delete( $wpdb->prefix . 'ih_machines', [ 'id' => $mid, 'owner_id' => $user_id ] );
    ih_log_activity( 'listing', 'Machine deleted by owner: ' . ( $row['title'] ?: ( '#' . $mid ) ), [ 'machine_id' => $mid, 'owner_id' => $user_id ] );
    wp_send_json_success();
});

/* Owner-gated tool delete — an owner may delete ONLY their own tool listing.
 * Mirrors ih_owner_delete_machine (ownership-gated instead of manage_options) and
 * the admin ih_delete_tool cleanup behaviour (delete the row + log). Nonce: ih_nonce
 * (the same nonce TLD_DATA localises on the tool detail page). */
add_action('wp_ajax_ih_owner_delete_tool', function() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => 'Not logged in.' ], 403 );
    }
    check_ajax_referer( 'ih_nonce', 'nonce' );
    global $wpdb;
    $tid     = intval( $_POST['id'] ?? 0 );
    $user_id = get_current_user_id();
    if ( ! $tid ) {
        wp_send_json_error( [ 'message' => 'Invalid listing.' ] );
    }
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT owner_id, title FROM {$wpdb->prefix}ih_tools WHERE id=%d", $tid
    ), ARRAY_A );
    if ( ! $row ) {
        wp_send_json_error( [ 'message' => 'Listing not found.' ] );
    }
    if ( (int) $row['owner_id'] !== $user_id ) {
        wp_send_json_error( [ 'message' => 'You can only remove your own listing.' ], 403 );
    }
    $wpdb->delete( $wpdb->prefix . 'ih_tools', [ 'id' => $tid, 'owner_id' => $user_id ] );
    ih_log_activity( 'listing', 'Tool deleted by owner: ' . ( $row['title'] ?: ( '#' . $tid ) ), [ 'tool_id' => $tid, 'owner_id' => $user_id ] );
    wp_send_json_success();
});

/* Lightweight nonce refresh for long-open add/edit forms.
 *
 * A form left open past a nonce tick (≈12h) would otherwise fail its save with a
 * silent "Security check failed". This endpoint mints a FRESH nonce for an
 * already-authenticated user so the client can transparently update the in-page
 * nonce field and retry the save once.
 *
 * Security: authenticated via the logged-in cookie + capability ('read'); the
 * actual save handlers still enforce their own manage_options/ownership checks,
 * so minting a fresh nonce here grants no new privilege. Deliberately NOT
 * check_ajax_referer-gated — this is the stale-nonce RECOVERY path, so requiring a
 * (possibly-expired) nonce would defeat its purpose. This is the same trust model
 * WordPress core's Heartbeat uses to refresh nonces. The response cannot be read
 * cross-origin, so a CSRF-triggered call cannot exfiltrate the token. Only a fixed
 * whitelist of IH form nonce actions may be requested. */
add_action('wp_ajax_ih_refresh_nonce', function() {
    if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
        wp_send_json_error( [ 'message' => 'Your session expired — please refresh the page or log in again.' ], 403 );
    }
    $requested = isset( $_POST['nonce_action'] ) ? sanitize_key( wp_unslash( $_POST['nonce_action'] ) ) : 'ih_nonce';
    $allowed   = array(
        'ih_nonce',
        'ih_add_machine', 'ih_edit_machine',
        'ih_add_tool', 'ih_edit_tool',
        'ih_user_add_machine', 'ih_user_edit_machine',
        'ih_user_add_tool', 'ih_user_edit_tool',
    );
    if ( ! in_array( $requested, $allowed, true ) ) {
        $requested = 'ih_nonce';
    }
    wp_send_json_success( [
        'nonce'        => wp_create_nonce( $requested ),
        'nonce_action' => $requested,
    ] );
});

add_action('wp_ajax_ih_update_listing_status', function() {
    ih_require_admin_ajax();
    check_ajax_referer('ih_nonce','nonce');

    $listing_id   = intval( $_POST['listing_id'] ?? 0 );
    $listing_type = sanitize_key( $_POST['listing_type'] ?? '' );
    $status       = sanitize_key( $_POST['status'] ?? '' );

    if ( ! $listing_id ) {
        wp_send_json_error( array( 'message' => 'Invalid listing.' ) );
    }

    $updated = ih_update_listing_status( $listing_type, $listing_id, $status );
    if ( is_wp_error( $updated ) ) {
        wp_send_json_error( array( 'message' => $updated->get_error_message() ) );
    }

    $meta = ih_listing_statuses()[ ih_normalize_listing_status( $status ) ];
    ih_log_activity(
        'listing',
        ucfirst( $listing_type ) . ' #' . $listing_id . ' status changed to ' . $meta['label'],
        array( 'listing_id' => $listing_id, 'listing_type' => $listing_type, 'listing_status' => $status )
    );

    wp_send_json_success( array(
        'status'    => ih_normalize_listing_status( $status ),
        'label'     => $meta['label'],
        'className' => $meta['class'],
        'available' => (int) $meta['available'],
    ) );
});

add_action('wp_ajax_ih_update_request_status', function() {
    ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce');
    global $wpdb;
    $request_id = intval($_POST['id']??$_POST['request_id']??0);
    if ( ! $request_id ) {
        wp_send_json_error( array( 'message' => 'Invalid request' ) );
    }
    $status_key = strtolower(trim(sanitize_text_field($_POST['status']??'Pending')));
    $status     = in_array($status_key,['approved','pending','rejected','completed'],true) ? ucfirst($status_key) : 'Pending';
    $req_row = $wpdb->get_row( $wpdb->prepare( "SELECT user_id,listing_id,listing_type,status FROM {$wpdb->prefix}ih_requests WHERE id=%d", $request_id ), ARRAY_A );
    if ( ! $req_row ) {
        wp_send_json_error( array( 'message' => 'Request not found' ) );
    }
    $wpdb->update($wpdb->prefix.'ih_requests',['status'=>$status],['id'=>$request_id],['%s'],['%d']);
    delete_transient( 'ih_admin_notifications_v1' );
    ih_log_activity('request','Request #'.$request_id.' marked '.$status,['request_id'=>$request_id,'status'=>$status]);
    if ( $req_row && in_array( sanitize_key( $req_row['listing_type'] ?? '' ), array( 'machine', 'tool' ), true ) ) {
        $req_row['status'] = $status;
        ih_sync_listing_status_from_request( $req_row );
    }
    $user_id = $req_row ? (int)($req_row['user_id'] ?? 0) : 0;
    $wa_link = '';
    if ($status==='Approved' && $user_id) {
        ih_ensure_thread_for_request($user_id,intval($req_row['listing_id']??0),sanitize_key($req_row['listing_type']??'machine'));
        ih_send_user_approval_whatsapp_email($user_id);
        $wa_link = ih_build_admin_whatsapp_link($user_id);
    }
    wp_send_json_success(['status'=>$status,'whatsapp_link'=>$wa_link]);
});

add_action('wp_ajax_ih_delete_request', function() {
    ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce');
    global $wpdb;
    $request_id = intval($_POST['request_id']??$_POST['id']??0);
    if (!$request_id) wp_send_json_error(['message'=>'Invalid request']);
    $wpdb->delete($wpdb->prefix.'ih_requests',['id'=>$request_id],['%d']);
    ih_log_activity('request','Request #'.$request_id.' deleted',['request_id'=>$request_id]);
    wp_send_json_success(['deleted'=>$request_id]);
});

add_action('wp_ajax_ih_get_request_analytics', function() {
    ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce');
    global $wpdb;
    $year  = intval($_POST['year']??0);
    $month = intval($_POST['month']??0);
    $where = "1=1"; $args = [];
    if ($year>0)              { $where .= " AND YEAR(request_date)=%d";  $args[] = $year; }
    if ($month>=1&&$month<=12){ $where .= " AND MONTH(request_date)=%d"; $args[] = $month; }
    $tbl = "{$wpdb->prefix}ih_requests";
    if ($args) {
        $total    = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tbl} WHERE {$where}",$args));
        $approved = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tbl} WHERE {$where} AND LOWER(TRIM(status))='approved'",$args));
        $pending  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tbl} WHERE {$where} AND LOWER(TRIM(status))='pending'",$args));
        $rejected = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tbl} WHERE {$where} AND LOWER(TRIM(status))='rejected'",$args));
        $completed = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tbl} WHERE {$where} AND LOWER(TRIM(status)) IN ('completed','complete')",$args));
    } else {
        $total    = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tbl}");
        $approved = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tbl} WHERE LOWER(TRIM(status))='approved'");
        $pending  = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tbl} WHERE LOWER(TRIM(status))='pending'");
        $rejected = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tbl} WHERE LOWER(TRIM(status))='rejected'");
        $completed = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tbl} WHERE LOWER(TRIM(status)) IN ('completed','complete')");
    }
    if ($total<1) $total = $approved+$pending+$rejected+$completed;
    if ($total<1) { $apct=0;$ppct=0;$rpct=0;$cpct=0; } else {
        $apct=(int)round($approved/$total*100); $ppct=(int)round($pending/$total*100); $rpct=(int)round($rejected/$total*100); $cpct=(int)round($completed/$total*100);
    }
    wp_send_json_success(['approved'=>$approved,'pending'=>$pending,'rejected'=>$rejected,'completed'=>$completed,'total'=>$total,'approved_pct'=>$apct,'pending_pct'=>$ppct,'rejected_pct'=>$rpct,'completed_pct'=>$cpct,'year'=>$year?:null,'month'=>($month>=1&&$month<=12)?$month:null]);
});

add_action('wp_ajax_ih_mark_thread_read',    function() { ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce'); global $wpdb; $wpdb->update($wpdb->prefix.'ih_threads',['unread'=>0],['id'=>intval($_POST['thread_id']??0)]); wp_send_json_success(); });
add_action('wp_ajax_ih_mark_messages_read',  function() { ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce'); global $wpdb; $thread_id=ih_get_thread_by_user_id(intval($_POST['user_id']??$_GET['user_id']??0)); if($thread_id){ $wpdb->update($wpdb->prefix.'ih_threads',['unread'=>0],['id'=>$thread_id]); if(function_exists('ih_chat_mark_sender_messages_read')){ ih_chat_mark_sender_messages_read($thread_id, 0); } } wp_send_json_success(); });
add_action('wp_ajax_ih_block_thread',        function() { ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce'); global $wpdb; $tid=intval($_POST['thread_id']??0); $cur=$wpdb->get_var($wpdb->prepare("SELECT blocked FROM {$wpdb->prefix}ih_threads WHERE id=%d",$tid)); $new=$cur?0:1; $wpdb->update($wpdb->prefix.'ih_threads',['blocked'=>$new],['id'=>$tid]); wp_send_json_success(['blocked'=>$new]); });
add_action('wp_ajax_ih_toggle_block',        function() { ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce'); $user_id=intval($_POST['user_id']??0); if(!$user_id)wp_send_json_error(['message'=>'Invalid user']); $blocked=sanitize_text_field($_POST['block_action']??'block')==='block'?1:0; ih_set_user_block_state($user_id,$blocked); ih_log_activity('user','User #'.$user_id.($blocked?' blocked':' unblocked'),['user_id'=>$user_id,'blocked'=>$blocked]); wp_send_json_success(['blocked'=>$blocked]); });
add_action('wp_ajax_ih_delete_chat',         function() { ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce'); global $wpdb; $user_id=intval($_POST['user_id']??0); $thread_id=ih_get_thread_by_user_id($user_id); if(!$thread_id)wp_send_json_error(['message'=>'Thread not found']); $wpdb->delete($wpdb->prefix.'ih_chats',['thread_id'=>$thread_id]); $wpdb->delete($wpdb->prefix.'ih_threads',['id'=>$thread_id]); ih_log_activity('message','Chat thread deleted for user #'.$user_id,['user_id'=>$user_id,'thread_id'=>$thread_id]); wp_send_json_success(); });

/* ── AJAX: clear the audit trail (admin only, from the Activity page) ── */
add_action('wp_ajax_ih_clear_activity_log', function() {
    ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce');
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ih_activity_log");
    ih_log_activity('system','Activity log cleared by admin');
    wp_send_json_success();
});

/* ── Admin bell notifications (built from live platform data) ── */
function ih_admin_notif_meta( $user_id, $key ) {
    $val = get_user_meta( (int) $user_id, $key, true );
    return is_array( $val ) ? $val : array();
}

function ih_admin_build_notifications() {
    $cached = get_transient( 'ih_admin_notifications_v1' );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    global $wpdb;
    $p = $wpdb->prefix;
    $out = array();

    $pending = $wpdb->get_results(
        "SELECT r.id, r.user_id, r.listing_type, r.listing_id, r.request_date
         FROM {$p}ih_requests r
         WHERE LOWER(TRIM(r.status))='pending'
         ORDER BY r.id DESC LIMIT 20",
        ARRAY_A
    ) ?: array();
    foreach ( $pending as $r ) {
        $uid  = (int) $r['user_id'];
        $u    = get_userdata( $uid );
        $name = $u ? $u->display_name : ( 'User #' . $uid );
        $out[] = array(
            'id'         => 1000000 + (int) $r['id'],
            'type'       => 'request',
            'title'      => 'Pending request from ' . $name,
            'body'       => ucfirst( sanitize_key( $r['listing_type'] ?? 'machine' ) ) . ' · REQ-' . (int) $r['id'],
            'link'       => admin_url( 'admin.php?page=ih-requests' ),
            'created_at' => $r['request_date'] ? $r['request_date'] . ' 12:00:00' : current_time( 'mysql' ),
            'is_read'    => 0,
        );
    }

    $threads = $wpdb->get_results(
        "SELECT th.id, th.user_id, th.last_message, th.last_time, th.unread
         FROM {$p}ih_threads th
         WHERE th.unread > 0
         ORDER BY th.last_time DESC LIMIT 15",
        ARRAY_A
    ) ?: array();
    foreach ( $threads as $t ) {
        $uid  = (int) $t['user_id'];
        $u    = get_userdata( $uid );
        $name = $u ? $u->display_name : ( 'User #' . $uid );
        $out[] = array(
            'id'         => 2000000 + (int) $t['id'],
            'type'       => 'message',
            'title'      => 'Message from ' . $name,
            'body'       => wp_trim_words( (string) ( $t['last_message'] ?? '' ), 12, '…' ),
            'link'       => admin_url( 'admin.php?page=ih-messages&user_id=' . $uid ),
            'created_at' => $t['last_time'] ?: current_time( 'mysql' ),
            'is_read'    => 0,
        );
    }

    $recent_users = $wpdb->get_results(
        "SELECT ID, display_name, user_registered FROM {$wpdb->users}
         WHERE user_registered >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY user_registered DESC LIMIT 5",
        ARRAY_A
    ) ?: array();
    foreach ( $recent_users as $ru ) {
        $out[] = array(
            'id'         => 3000000 + (int) $ru['ID'],
            'type'       => 'user',
            'title'      => 'New registration',
            'body'       => (string) $ru['display_name'],
            'link'       => admin_url( 'admin.php?page=ih-users&view=' . (int) $ru['ID'] ),
            'created_at' => $ru['user_registered'],
            'is_read'    => 0,
        );
    }

    set_transient( 'ih_admin_notifications_v1', $out, 60 );
    return $out;
}

add_action( 'wp_ajax_ih_get_notifications', function() {
    ih_require_admin_ajax();
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $uid       = get_current_user_id();
    $read      = ih_admin_notif_meta( $uid, 'ih_notif_read' );
    $dismissed = ih_admin_notif_meta( $uid, 'ih_notif_dismissed' );
    $items     = ih_admin_build_notifications();
    $filtered  = array();
    $unread    = 0;
    foreach ( $items as $n ) {
        if ( in_array( (int) $n['id'], $dismissed, true ) ) {
            continue;
        }
        $n['is_read'] = in_array( (int) $n['id'], $read, true ) ? 1 : 0;
        if ( ! $n['is_read'] ) {
            $unread++;
        }
        $filtered[] = $n;
    }
    wp_send_json_success( array( 'notifications' => $filtered, 'unread' => $unread ) );
} );

add_action( 'wp_ajax_ih_mark_notifications_read', function() {
    ih_require_admin_ajax();
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $uid  = get_current_user_id();
    $ids  = array();
    foreach ( ih_admin_build_notifications() as $n ) {
        $ids[] = (int) $n['id'];
    }
    update_user_meta( $uid, 'ih_notif_read', $ids );
    delete_transient( 'ih_admin_notifications_v1' );
    wp_send_json_success( array( 'unread' => 0 ) );
} );

add_action( 'wp_ajax_ih_mark_single_notification_read', function() {
    ih_require_admin_ajax();
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $uid = get_current_user_id();
    $id  = (int) ( $_POST['id'] ?? 0 );
    $read = ih_admin_notif_meta( $uid, 'ih_notif_read' );
    if ( $id && ! in_array( $id, $read, true ) ) {
        $read[] = $id;
        update_user_meta( $uid, 'ih_notif_read', $read );
    }
    $unread = 0;
    $dismissed = ih_admin_notif_meta( $uid, 'ih_notif_dismissed' );
    foreach ( ih_admin_build_notifications() as $n ) {
        if ( in_array( (int) $n['id'], $dismissed, true ) ) {
            continue;
        }
        if ( ! in_array( (int) $n['id'], $read, true ) ) {
            $unread++;
        }
    }
    wp_send_json_success( array( 'unread' => $unread ) );
} );

add_action( 'wp_ajax_ih_delete_notification', function() {
    ih_require_admin_ajax();
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $uid = get_current_user_id();
    $id  = (int) ( $_POST['id'] ?? 0 );
    $dismissed = ih_admin_notif_meta( $uid, 'ih_notif_dismissed' );
    if ( $id && ! in_array( $id, $dismissed, true ) ) {
        $dismissed[] = $id;
        update_user_meta( $uid, 'ih_notif_dismissed', $dismissed );
    }
    delete_transient( 'ih_admin_notifications_v1' );
    wp_send_json_success();
} );

add_action( 'wp_ajax_ih_get_sidebar_counts', function() {
    ih_require_admin_ajax();
    check_ajax_referer( 'ih_nonce', 'nonce' );
    global $wpdb;
    $p = $wpdb->prefix;
    $msg_unread = (int) $wpdb->get_var( "SELECT COALESCE(SUM(unread),0) FROM {$p}ih_threads WHERE unread > 0" );
    $pending_req = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}ih_requests WHERE LOWER(TRIM(status))='pending'" );
    $recent_users = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    $unread_threads = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}ih_threads WHERE unread > 0" );
    // Lightweight badge estimate — avoids rebuilding full notification payloads every 20s.
    $notif_unread = min( 40, $pending_req + $unread_threads + min( 5, $recent_users ) );
    wp_send_json_success( array(
        'total'         => $msg_unread + $notif_unread,
        'notifications' => $notif_unread,
        'messages'      => $msg_unread,
        'pending'       => $pending_req,
    ) );
} );

add_action( 'wp_ajax_ih_mark_all_threads_read', function() {
    ih_require_admin_ajax();
    check_ajax_referer( 'ih_nonce', 'nonce' );
    global $wpdb;
    $wpdb->query( "UPDATE {$wpdb->prefix}ih_threads SET unread=0" );
    wp_send_json_success();
} );

add_action( 'wp_ajax_ih_global_search', function() {
    ih_require_admin_ajax();
    check_ajax_referer( 'ih_nonce', 'nonce' );
    global $wpdb;
    $q = trim( sanitize_text_field( $_POST['q'] ?? '' ) );
    if ( strlen( $q ) < 2 ) {
        wp_send_json_success( array() );
    }
    $like = '%' . $wpdb->esc_like( $q ) . '%';
    $out  = array();

    $users = $wpdb->get_results( $wpdb->prepare(
        "SELECT ID, display_name, user_email FROM {$wpdb->users}
         WHERE display_name LIKE %s OR user_email LIKE %s OR ID = %d
         LIMIT 6",
        $like, $like, is_numeric( $q ) ? (int) $q : 0
    ), ARRAY_A ) ?: array();
    foreach ( $users as $u ) {
        $out[] = array(
            'type'  => 'User',
            'icon'  => '👤',
            'title' => $u['display_name'],
            'sub'   => $u['user_email'],
            'url'   => admin_url( 'admin.php?page=ih-users&view=' . (int) $u['ID'] ),
        );
    }

    $machines = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, title, location FROM {$wpdb->prefix}ih_machines
         WHERE title LIKE %s OR location LIKE %s OR id = %d LIMIT 6",
        $like, $like, is_numeric( $q ) ? (int) $q : 0
    ), ARRAY_A ) ?: array();
    foreach ( $machines as $m ) {
        $out[] = array(
            'type'  => 'Machine',
            'icon'  => '⚙️',
            'title' => $m['title'],
            'sub'   => $m['location'] ?: 'Machine #' . $m['id'],
            'url'   => admin_url( 'admin.php?page=ih-machine-detail&id=' . (int) $m['id'] ),
        );
    }

    $tools = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, title, location FROM {$wpdb->prefix}ih_tools
         WHERE title LIKE %s OR location LIKE %s OR id = %d LIMIT 6",
        $like, $like, is_numeric( $q ) ? (int) $q : 0
    ), ARRAY_A ) ?: array();
    foreach ( $tools as $t ) {
        $out[] = array(
            'type'  => 'Tool',
            'icon'  => '🔧',
            'title' => $t['title'],
            'sub'   => $t['location'] ?: 'Tool #' . $t['id'],
            'url'   => admin_url( 'admin.php?page=ih-tool-detail&id=' . (int) $t['id'] ),
        );
    }

    $reqs = $wpdb->get_results( $wpdb->prepare(
        "SELECT r.id, r.status, r.listing_type, u.display_name
         FROM {$wpdb->prefix}ih_requests r
         LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id
         WHERE r.id = %d OR u.display_name LIKE %s OR u.user_email LIKE %s
         LIMIT 6",
        is_numeric( $q ) ? (int) $q : 0, $like, $like
    ), ARRAY_A ) ?: array();
    foreach ( $reqs as $r ) {
        $out[] = array(
            'type'  => 'Request',
            'icon'  => '📋',
            'title' => 'REQ-' . $r['id'] . ' · ' . ( $r['display_name'] ?: 'User' ),
            'sub'   => ucfirst( $r['status'] ?? 'Pending' ) . ' · ' . ucfirst( $r['listing_type'] ?? 'machine' ),
            'url'   => admin_url( 'admin.php?page=ih-requests' ),
        );
    }

    wp_send_json_success( array_slice( $out, 0, 12 ) );
} );

add_action('wp_ajax_ih_approve_reject', function() {
    ih_require_admin_ajax(); check_ajax_referer('ih_nonce','nonce');
    global $wpdb;
    $user_id    = intval($_POST['user_id']??0);
    $request_id = intval($_POST['request_id']??$_POST['id']??0);
    $status     = intval($_POST['approve']??0) ? 'Approved' : 'Rejected';
    if (!$user_id) wp_send_json_error(['message'=>'Invalid user']);
    $req_id = 0;
    if ( $request_id ) {
        $owned = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ih_requests WHERE id=%d AND user_id=%d",
            $request_id,
            $user_id
        ) );
        if ( $owned ) {
            $req_id = $owned;
            $wpdb->update($wpdb->prefix.'ih_requests',['status'=>$status],['id'=>$req_id],['%s'],['%d']);
        }
    }
    if ( ! $req_id ) {
        $thread = $wpdb->get_row($wpdb->prepare("SELECT listing_id,listing_type FROM {$wpdb->prefix}ih_threads WHERE user_id=%d ORDER BY id DESC LIMIT 1",$user_id),ARRAY_A);
        $listing_id   = $thread ? intval($thread['listing_id']??0) : 0;
        $listing_type = $thread ? sanitize_key($thread['listing_type']??'machine') : 'machine';
        if ( ! in_array( $listing_type, array( 'machine', 'tool', 'machine_contact', 'tool_contact', 'profile_access' ), true ) ) {
            $listing_type = 'machine';
        }
        if ( $listing_id ) {
            $req_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND listing_id=%d AND listing_type=%s AND LOWER(TRIM(status))='pending' ORDER BY id DESC LIMIT 1",
                $user_id,
                $listing_id,
                $listing_type
            ) );
            if ( ! $req_id && in_array( $listing_type, array( 'machine_contact', 'tool_contact' ), true ) ) {
                $base_type = str_replace( '_contact', '', $listing_type );
                $req_id = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND listing_id=%d AND listing_type=%s AND LOWER(TRIM(status))='pending' ORDER BY id DESC LIMIT 1",
                    $user_id,
                    $listing_id,
                    $base_type
                ) );
            }
        }
        if ( $req_id ) {
            $wpdb->update($wpdb->prefix.'ih_requests',['status'=>$status],['id'=>$req_id],['%s'],['%d']);
        } else {
            $req_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ih_requests WHERE user_id=%d ORDER BY id DESC LIMIT 1",$user_id));
            if ($req_id) {
                $wpdb->update($wpdb->prefix.'ih_requests',['status'=>$status],['id'=>$req_id],['%s'],['%d']);
            } else {
                if (!in_array($listing_type,['machine','tool'],true)) $listing_type='machine';
                $ok = $wpdb->insert($wpdb->prefix.'ih_requests',['user_id'=>$user_id,'listing_id'=>$listing_id,'listing_type'=>$listing_type,'request_date'=>current_time('Y-m-d'),'status'=>$status],['%d','%d','%s','%s','%s']);
                if ($ok) $req_id = (int)$wpdb->insert_id;
            }
        }
    }
    delete_transient( 'ih_admin_notifications_v1' );
    $row = $req_id ? $wpdb->get_row($wpdb->prepare("SELECT user_id,listing_id,listing_type,status FROM {$wpdb->prefix}ih_requests WHERE id=%d",$req_id),ARRAY_A) : null;
    if ( $row ) {
        $row['status'] = $status;
        ih_sync_listing_status_from_request( $row );
    }
    $wa_link = '';
    if ($status==='Approved' && $user_id) {
        ih_ensure_thread_for_request($user_id,intval($row['listing_id']??0),sanitize_key($row['listing_type']??'machine'));
        ih_send_user_approval_whatsapp_email($user_id);
        $wa_link = ih_build_admin_whatsapp_link($user_id);
    }
    wp_send_json_success(['status'=>$status,'whatsapp_link'=>$wa_link,'request_id'=>$req_id]);
});

/**
 * Extend allowed upload MIME types for chat file sharing.
 */
function ih_allowed_upload_mimes( $mimes ) {
    return array_merge($mimes, [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt'  => 'text/plain',
        'csv'  => 'text/csv',
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed',
        'mp3'  => 'audio/mpeg',
        'mp4'  => 'video/mp4',
        'mov'  => 'video/quicktime',
    ]);
}

/**
 * Completely bypass WP filetype security check for chat uploads.
 * Works on WordPress 5.x+ which uses finfo for real MIME detection.
 */
function ih_bypass_filetype_check( $data, $file, $filename, $mimes ) {
    $ext  = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    $all  = ih_allowed_upload_mimes([]);
    if ( isset( $all[$ext] ) ) {
        $data['ext']             = $ext;
        $data['type']            = $all[$ext];
        $data['proper_filename'] = $filename;
    }
    return $data;
}

/**
 * Shared upload handler — bypasses WordPress MIME type restrictions completely.
 * Compatible with PHP 7.4+
 */
function ih_handle_chat_upload( $field = 'file' ) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    /* Check file exists */
    if ( empty( $_FILES[$field] ) || empty( $_FILES[$field]['tmp_name'] ) ) {
        return new WP_Error( 'no_file', 'No file uploaded' );
    }

    $file_data = $_FILES[$field];

    /* Determine extension and MIME */
    $original_name = sanitize_file_name( $file_data['name'] );
    $ext           = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );
    $allowed       = ih_allowed_upload_mimes( array() );
    $mime          = isset( $allowed[$ext] ) ? $allowed[$ext] : 'application/octet-stream';

    /* Override WP filetype detection — add filters BEFORE wp_handle_upload */
    $mime_filter = function( $mimes ) use ( $allowed ) {
        return array_merge( $mimes, $allowed );
    };
    $type_filter = function( $data, $file, $filename, $mimes ) use ( $ext, $allowed ) {
        if ( isset( $allowed[$ext] ) ) {
            $data['ext']             = $ext;
            $data['type']            = $allowed[$ext];
            $data['proper_filename'] = $filename;
        }
        return $data;
    };

    add_filter( 'upload_mimes',                $mime_filter, 999 );
    add_filter( 'wp_check_filetype_and_ext',   $type_filter, 999, 4 );

    /* Move file to uploads dir */
    $upload = wp_handle_upload( $file_data, array(
        'test_form' => false,
        'test_type' => false,
    ) );

    remove_filter( 'upload_mimes',              $mime_filter, 999 );
    remove_filter( 'wp_check_filetype_and_ext', $type_filter, 999 );

    if ( ! empty( $upload['error'] ) ) {
        return new WP_Error( 'upload_error', $upload['error'] );
    }
    if ( empty( $upload['file'] ) || empty( $upload['url'] ) ) {
        return new WP_Error( 'upload_failed', 'Upload returned no file path' );
    }

    /* Detect MIME from upload result or fallback */
    $final_mime = ! empty( $upload['type'] ) ? $upload['type'] : $mime;

    /* Insert as WP attachment */
    $file_path = $upload['file'];
    $file_url  = $upload['url'];
    $file_name = basename( $file_path );
    $post_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );

    $attach_id = wp_insert_attachment( array(
        'post_title'     => $post_title,
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_mime_type' => $final_mime,
        'guid'           => $file_url,
    ), $file_path, 0, true );

    if ( is_wp_error( $attach_id ) ) {
        return $attach_id;
    }

    /* Generate thumbnails for images */
    $is_img = in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ) );
    if ( $is_img ) {
        $attach_meta = wp_generate_attachment_metadata( $attach_id, $file_path );
        wp_update_attachment_metadata( $attach_id, $attach_meta );
    }

    return array(
        'aid'    => (int) $attach_id,
        'url'    => $file_url,
        'name'   => $file_name,
        'ext'    => $ext,
        'is_img' => $is_img,
        'time'   => get_date_from_gmt( current_time( 'mysql', true ), 'g:i A' ),
    );
}

add_action('wp_ajax_ih_upload_file','ih_ajax_upload_file_handler');
function ih_ajax_upload_file_handler() {
    ih_require_admin_ajax();
    check_ajax_referer('ih_nonce','nonce');
    global $wpdb;

    $user_id   = intval($_POST['user_id'] ?? 0);
    $thread_id = ih_get_thread_by_user_id($user_id);
    if (!$thread_id) wp_send_json_error(['message'=>'Thread not found']);

    /* Detailed PHP upload error check */
    if ( ! isset($_FILES['file']) ) {
        wp_send_json_error(['message'=>'No file field in request. Check server upload settings.']);
    }
    $php_err = $_FILES['file']['error'];
    if ( $php_err !== UPLOAD_ERR_OK ) {
        $err_map = [
            UPLOAD_ERR_INI_SIZE   => 'File too large (server limit: '.ini_get('upload_max_filesize').'). Ask your host to increase upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE  => 'File too large (form limit).',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was sent.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
        ];
        wp_send_json_error(['message' => isset($err_map[$php_err]) ? $err_map[$php_err] : 'PHP upload error code: '.$php_err]);
    }
    if ( empty($_FILES['file']['tmp_name']) ) {
        wp_send_json_error(['message'=>'Temp file missing. post_max_size='.ini_get('post_max_size').', upload_max_filesize='.ini_get('upload_max_filesize')]);
    }

    $result = ih_handle_chat_upload('file');
    if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message(), 'code' => $result->get_error_code()]);

    $msg = $result['is_img']
        ? "🖼 {$result['name']} ({$result['url']})"
        : "📎 {$result['name']} ({$result['url']})";

    $wpdb->insert($wpdb->prefix.'ih_chats', [
        'thread_id' => $thread_id,
        'from_me'   => 1,
        'message'   => $msg,
        'sent_at'   => current_time('mysql', true),
    ]);
    $wpdb->update($wpdb->prefix.'ih_threads', [
        'last_message' => $result['name'],
        'last_time'    => current_time('mysql', true),
    ], ['id' => $thread_id]);

    wp_send_json_success([
        'id'       => (int)$wpdb->insert_id,
        'filename' => $result['name'],
        'url'      => $result['url'],
        'is_image' => $result['is_img'],
        'time'     => $result['time'],
    ]);
}

/* ═══════════════════════════════════════
   FORM HANDLERS (Add Machine / Tool)
═══════════════════════════════════════ */
if ( ! function_exists( 'ih_post_scalar_value' ) ) {
    function ih_post_scalar_value( $key ) {
        $value = $_POST[ $key ] ?? '';
        if ( is_array( $value ) ) {
            $value = end( $value );
        }
        return sanitize_text_field( wp_unslash( (string) $value ) );
    }
}

if ( ! function_exists( 'ih_yes_no_post_value' ) ) {
    function ih_yes_no_post_value( $key ) {
        $value = ih_post_scalar_value( $key );
        return in_array( $value, array( 'Yes', '1', 'on', 'true' ), true ) ? 'Yes' : 'No';
    }
}

if ( ! function_exists( 'ih_machine_certifications_from_post' ) ) {
    function ih_machine_certifications_from_post() {
        $certs = array();
        if ( ! empty( $_POST['certifications_list'] ) && is_array( $_POST['certifications_list'] ) ) {
            foreach ( $_POST['certifications_list'] as $c ) {
                $c = sanitize_text_field( wp_unslash( (string) $c ) );
                if ( $c !== '' ) {
                    $certs[] = $c;
                }
            }
        }
        $manual = ih_post_scalar_value( 'certifications' );
        if ( $manual !== '' ) {
            foreach ( preg_split( '/\s*,\s*/', $manual ) as $part ) {
                $part = trim( $part );
                if ( $part !== '' ) {
                    $certs[] = $part;
                }
            }
        }
        return $certs ? implode( ', ', array_values( array_unique( $certs ) ) ) : '';
    }
}

if ( ! function_exists( 'ih_machine_form_data_from_post' ) ) {
    function ih_machine_form_data_from_post( $existing_cols, $defaults = array() ) {
        $data   = $defaults;
        $fields = array(
            'title', 'brand', 'machine_type', 'year_manufacture', 'identical_count', 'clamping_force', 'shot_size',
            'screw_diameter', 'max_injection_pressure', 'tie_bar_spacing', 'max_mould_height', 'min_mould_height',
            'opening_stroke', 'clamp_drive_type', 'toggle_clamp_type', 'max_part_weight', 'max_part_dimensions',
            'tolerance', 'material_grade', 'engineering_grade',
            'recycled_materials', 'batch_size', 'min_order_qty', 'max_monthly_output', 'avg_cycle_time',
            'operating_hours', 'utilization', 'location', 'automation_level', 'robot_integration', 'multi_cavity',
            'qc_tools', 'tolerance_consistency', 'overmoulding', 'insert_moulding', 'iml', 'gas_assisted', 'thin_wall',
            'listing_date', 'expiry_date', 'cavities',
            // Clamp-tonnage inputs — projected area (cm²) + cavity pressure (bar).
            'projected_area', 'cavity_pressure',
        );
        $toggle_fields = array(
            'engineering_grade', 'recycled_materials', 'robot_integration', 'multi_cavity',
            'overmoulding', 'insert_moulding', 'iml', 'gas_assisted', 'thin_wall',
        );
        foreach ( $fields as $f ) {
            if ( ! in_array( $f, $existing_cols, true ) ) {
                continue;
            }
            if ( $f === 'identical_count' ) {
                $data[ $f ] = ( isset( $_POST[ $f ] ) && $_POST[ $f ] !== '' ) ? max( 1, absint( $_POST[ $f ] ) ) : 1;
                continue;
            }
            if ( $f === 'cavities' ) {
                // Integer mould-cavity count; empty/unset => 0 (calculators fall back to 1).
                $data[ $f ] = ( isset( $_POST[ $f ] ) && $_POST[ $f ] !== '' ) ? max( 0, absint( $_POST[ $f ] ) ) : 0;
                continue;
            }
            $data[ $f ] = in_array( $f, $toggle_fields, true ) ? ih_yes_no_post_value( $f ) : ih_post_scalar_value( $f );
        }
        $posted_materials = array();
        if ( ! empty( $_POST['materials'] ) && is_array( $_POST['materials'] ) ) {
            foreach ( wp_unslash( $_POST['materials'] ) as $mat ) {
                $mat = sanitize_text_field( (string) $mat );
                if ( $mat !== '' ) {
                    $posted_materials[] = $mat;
                }
            }
        }
        $posted_materials = array_values( array_unique( $posted_materials ) );
        if ( in_array( 'materials', $existing_cols, true ) ) {
            $data['materials'] = $posted_materials ? wp_json_encode( $posted_materials ) : '';
        }
        foreach ( ih_machine_materials_map() as $cb => $mat_def ) {
            if ( in_array( $cb, $existing_cols, true ) ) {
                $legacy_codes = array_merge( array( $mat_def['code'] ), $mat_def['aliases'] ?? array() );
                $data[ $cb ] = ( ! empty( array_intersect( $legacy_codes, $posted_materials ) ) || isset( $_POST[ $cb ] ) ) ? 1 : 0;
            }
        }
        if ( in_array( 'certifications', $existing_cols, true ) ) {
            $data['certifications'] = ih_machine_certifications_from_post();
        }
        if ( in_array( 'notes', $existing_cols, true ) ) {
            $data['notes'] = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
        }
        $ih_valid_date = static function( $d ) {
            $d = sanitize_text_field( (string) $d );
            return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ? $d : '';
        };
        if ( in_array( 'listing_date', $existing_cols, true ) ) {
            $data['listing_date'] = $ih_valid_date( $_POST['listing_date'] ?? '' ) ?: current_time( 'Y-m-d' );
        }
        if ( in_array( 'expiry_date', $existing_cols, true ) ) {
            $data['expiry_date'] = $ih_valid_date( $_POST['expiry_date'] ?? '' ) ?: wp_date( 'Y-m-d', strtotime( '+3 months', current_time( 'timestamp' ) ) );
        }
        return $data;
    }
}

if ( ! function_exists( 'ih_machine_upload_images_from_post' ) ) {
    function ih_machine_upload_images_from_post( $existing_cols, $data ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $allowed_mimes = array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif' );
        $max_bytes     = 8 * 1024 * 1024;
        for ( $i = 1; $i <= 5; $i++ ) {
            $key = "image_{$i}";
            if ( ! in_array( $key, $existing_cols, true ) ) {
                continue;
            }
            if ( empty( $_FILES[ $key ]['name'] ) || ! empty( $_FILES[ $key ]['error'] ) ) {
                continue;
            }
            if ( (int) ( $_FILES[ $key ]['size'] ?? 0 ) > $max_bytes ) {
                continue;
            }
            $check = wp_check_filetype_and_ext( $_FILES[ $key ]['tmp_name'], $_FILES[ $key ]['name'], $allowed_mimes );
            if ( empty( $check['type'] ) || strpos( $check['type'], 'image/' ) !== 0 ) {
                continue;
            }
            $_FILES['ih_machine_upload_img'] = $_FILES[ $key ];
            $aid = media_handle_upload( 'ih_machine_upload_img', 0, array(), array( 'test_form' => false, 'mimes' => $allowed_mimes ) );
            unset( $_FILES['ih_machine_upload_img'] );
            if ( ! is_wp_error( $aid ) ) {
                $url = wp_get_attachment_url( $aid );
                if ( $url ) {
                    $data[ $key ] = esc_url_raw( $url );
                }
            }
        }
        return $data;
    }
}

/* Admin Edit Machine save — processes the ih_machine_edit_submit POST from
 * pages/edit-machine.php. Lets administrators edit any listing's data (incl. data
 * submitted by owners). Reuses the same sanitizer the Add/save flow uses.
 *
 * Re-review decision: an ADMIN edit must NOT bounce the listing into the owner
 * "pending re-review" queue, so this path deliberately does NOT call
 * ih_mark_listing_pending_review and never touches listing_status/approval. The
 * owner edit path (ih_user_machine_edit_submit) keeps its existing re-review behavior. */
add_action('admin_init', function() {
    if ( ! isset( $_POST['ih_machine_edit_submit'] ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Permission denied.' );
    }
    check_admin_referer( 'ih_edit_machine', 'ih_nonce_field' );

    global $wpdb;
    $machine_id = absint( $_POST['machine_id'] ?? 0 );
    if ( ! $machine_id ) {
        wp_safe_redirect( admin_url( 'admin.php?page=ih-machines' ) );
        exit;
    }

    $table         = $wpdb->prefix . 'ih_machines';
    $existing_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" );

    /* Reuse the Add/save sanitizer: same field set, materials CSV + legacy boolean
       sync, certifications, toggles, date validation. No $defaults so it never sets
       owner_id / listing_status / available. */
    $data = ih_machine_form_data_from_post( $existing_cols );

    /* Persist only the columns the admin edit form actually renders, so any column it
       does NOT manage is preserved untouched: brand, notes, internal_notes (own admin
       handler), owner_id, listing_status, available, slug, view counts, etc. */
    $editable = array(
        'title', 'year_manufacture', 'machine_type', 'identical_count',
        'clamping_force', 'shot_size', 'screw_diameter', 'max_injection_pressure',
        'tie_bar_spacing', 'max_mould_height', 'min_mould_height',
        'opening_stroke', 'clamp_drive_type', 'toggle_clamp_type',
        'max_part_weight', 'max_part_dimensions', 'tolerance', 'material_grade',
        'engineering_grade', 'recycled_materials',
        'batch_size', 'min_order_qty', 'max_monthly_output', 'avg_cycle_time',
        'operating_hours', 'utilization', 'location',
        'automation_level', 'robot_integration', 'multi_cavity',
        'certifications', 'qc_tools', 'tolerance_consistency',
        'overmoulding', 'insert_moulding', 'iml', 'gas_assisted', 'thin_wall',
        'listing_date', 'expiry_date', 'materials', 'cavities',
        'projected_area', 'cavity_pressure',
    );
    foreach ( array_keys( ih_machine_materials_map() ) as $mat_col ) {
        $editable[] = $mat_col;
    }
    $update = array_intersect_key( $data, array_flip( $editable ) );

    /* Images: only added when a new file is uploaded; existing images preserved. */
    $update = ih_machine_upload_images_from_post( $existing_cols, $update );

    if ( ! empty( $update ) ) {
        $wpdb->update( $table, $update, array( 'id' => $machine_id ) );
    }

    wp_safe_redirect( admin_url( 'admin.php?page=ih-edit-machine&machine_id=' . $machine_id . '&saved=1' ) );
    exit;
});

/* Admin-only: save a machine's internal notes. Deliberately separate from the listing
 * edit/save flow so editing a note NEVER calls ih_mark_listing_pending_review (no
 * re-review). Strictly admin-gated; owners and the public can never write this column. */
add_action('admin_init', function() {
    if ( ! isset( $_POST['ih_machine_internal_notes_submit'] ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Permission denied.' );
    }
    if ( ! isset( $_POST['ih_internal_notes_nonce'] ) || ! wp_verify_nonce( $_POST['ih_internal_notes_nonce'], 'ih_machine_internal_notes' ) ) {
        wp_die( 'Security check failed.' );
    }
    global $wpdb;
    $machine_id = absint( $_POST['machine_id'] ?? 0 );
    if ( ! $machine_id ) {
        wp_safe_redirect( admin_url( 'admin.php?page=ih-machines' ) );
        exit;
    }
    $table         = $wpdb->prefix . 'ih_machines';
    $existing_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" );
    if ( in_array( 'internal_notes', $existing_cols, true ) ) {
        $notes = sanitize_textarea_field( wp_unslash( $_POST['internal_notes'] ?? '' ) );
        $wpdb->update( $table, array( 'internal_notes' => $notes ), array( 'id' => $machine_id ), array( '%s' ), array( '%d' ) );
    }
    wp_safe_redirect( admin_url( 'admin.php?page=ih-edit-machine&machine_id=' . $machine_id . '&notes_saved=1' ) );
    exit;
});

add_action('admin_init', function() {
    if (isset($_POST['ih_machine_submit'])) {
        if (!check_admin_referer('ih_add_machine','ih_nonce_field')) wp_die('Security check failed');
        global $wpdb;
        $table = $wpdb->prefix . 'ih_machines';
        $existing_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" );
        $data = ih_machine_form_data_from_post( $existing_cols, array(
            'owner_id' => get_current_user_id(),
            'available' => 1,
            'listing_status' => 'available',
        ) );
        $data = ih_machine_upload_images_from_post( $existing_cols, $data );
        $wpdb->insert( $table, $data );
        ih_notify_new_listing($wpdb->insert_id,'Machine');
        wp_redirect(admin_url('admin.php?page=ih-add-machine&saved=1')); exit;
    }
    if (isset($_POST['ih_tool_submit'])) {
        if (!check_admin_referer('ih_add_tool','ih_nonce_field')) wp_die('Security check failed');
        global $wpdb;
        $table = $wpdb->prefix . 'ih_tools';
        $existing_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" );
        $data = array( 'owner_id' => get_current_user_id(), 'available' => 1, 'listing_status' => 'available' );
        $fields = array(
            'title','part_name','part_dimensions','part_weight','num_cavities','owner_name','location',
            'material_grade','colour','material','tolerance','surface_finish','draft_angle',
            'mould_type','mould_material','mould_condition','tool_condition',
            'runner_type','gate_type','ejector_type','nozzle_type','clamp_drive_type','toggle_clamp_type',
            'injection_stages','compatible_specs','mould_weight','mould_dimensions','mould_location',
            'required_qty','annual_volume','cycle_time','min_order_qty','packaging','material_supplied',
            'clamp_force','shot_weight','tie_bar','opening_stroke','hot_runner_controller','hot_runner_zones',
            'water_cooled','suck_pump','food_grade','medical_grade','iml','automation',
            'listing_date','expiry_date',
            // Clamp-tonnage inputs — projected area (cm²) + cavity pressure (bar).
            'projected_area','cavity_pressure',
        );
        $toggle_fields = array( 'water_cooled','suck_pump','food_grade','medical_grade','iml','automation' );
        foreach ( $fields as $f ) {
            if ( ! in_array( $f, $existing_cols, true ) ) {
                continue;
            }
            $data[ $f ] = in_array( $f, $toggle_fields, true ) ? ih_yes_no_post_value( $f ) : ih_post_scalar_value( $f );
        }
        if ( in_array( 'tool_condition', $existing_cols, true ) && empty( $data['tool_condition'] ) && ! empty( $data['mould_condition'] ) ) {
            $data['tool_condition'] = $data['mould_condition'];
        }
        if ( in_array( 'part_description', $existing_cols, true ) ) {
            $data['part_description'] = sanitize_textarea_field( wp_unslash( $_POST['part_description'] ?? '' ) );
        }
        if ( in_array( 'num_cavities_spec', $existing_cols, true ) ) {
            $data['num_cavities_spec'] = ( isset( $_POST['num_cavities_spec'] ) && $_POST['num_cavities_spec'] !== '' )
                ? absint( $_POST['num_cavities_spec'] ) : 0;
        }
        foreach ( array( 'tolerance_abs','tolerance_pp','tolerance_pe' ) as $cb ) {
            if ( in_array( $cb, $existing_cols, true ) ) {
                $data[ $cb ] = isset( $_POST[ $cb ] ) ? 1 : 0;
            }
        }
        if ( in_array( 'materials', $existing_cols, true ) ) {
            $mats = array();
            if ( ! empty( $_POST['materials'] ) && is_array( $_POST['materials'] ) ) {
                foreach ( $_POST['materials'] as $m ) {
                    $m = sanitize_text_field( wp_unslash( $m ) );
                    if ( $m !== '' ) {
                        $mats[] = $m;
                    }
                }
            }
            // Materials now come from the §1 grade picker (hidden materials[] inputs).
            // Fall back to the material_grade CSV so the buyer-filterable column is never
            // left empty (e.g. if the page posts without the JS-injected inputs).
            if ( ! $mats && ! empty( $data['material_grade'] ) ) {
                foreach ( explode( ',', $data['material_grade'] ) as $g ) {
                    $g = trim( $g );
                    if ( $g !== '' ) {
                        $mats[] = $g;
                    }
                }
            }
            $data['materials'] = $mats ? wp_json_encode( array_values( array_unique( $mats ) ) ) : '';
        }
        if ( in_array( 'material', $existing_cols, true ) && empty( $data['material'] ) ) {
            $first_mat = '';
            if ( ! empty( $_POST['materials'] ) && is_array( $_POST['materials'] ) ) {
                $first_mat = sanitize_text_field( wp_unslash( (string) reset( $_POST['materials'] ) ) );
            }
            if ( $first_mat === '' && ! empty( $data['material_grade'] ) ) {
                $grade_parts = explode( ',', $data['material_grade'] );
                $first_mat   = trim( (string) reset( $grade_parts ) );
            }
            if ( $first_mat !== '' ) {
                $data['material'] = $first_mat;
            }
        }
        if ( isset( $data['material_supplied'] ) && $data['material_supplied'] === '' ) {
            $data['material_supplied'] = 'No — customer supplies';
        }
        if ( isset( $data['hot_runner_controller'] ) && $data['hot_runner_controller'] === '' ) {
            $data['hot_runner_controller'] = 'Not Required';
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        for ( $i = 1; $i <= 5; $i++ ) {
            $key = "image_{$i}";
            if ( ! in_array( $key, $existing_cols, true ) ) {
                continue;
            }
            if ( empty( $_FILES[ $key ]['tmp_name'] ) ) {
                continue;
            }
            $_FILES['upload_img'] = $_FILES[ $key ];
            $aid = media_handle_upload( 'upload_img', 0 );
            if ( ! is_wp_error( $aid ) ) {
                $data[ $key ] = wp_get_attachment_url( $aid );
            }
        }
        $wpdb->insert( $table, $data );
        ih_notify_new_listing( $wpdb->insert_id, 'Tool' );
        wp_redirect( admin_url( 'admin.php?page=ih-add-tool&saved=1' ) );
        exit;
    }

    /* ── Existing users ka meta sync karo (one-time) ── */
    if ( isset($_GET['ih_sync_meta']) && current_user_can('administrator') ) {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT m.user_id, m.phone, m.location, m.company, m.job_title, m.address, m.website FROM {$wpdb->prefix}ih_user_meta m WHERE m.user_id > 0", ARRAY_A);
        foreach ($rows as $row) {
            ih_sync_user_meta($row['user_id'], ['phone'=>$row['phone'],'location'=>$row['location'],'company'=>$row['company'],'job_title'=>$row['job_title'],'address'=>$row['address'],'website'=>$row['website'],'city'=>$row['location']]);
        }
        wp_redirect(admin_url('admin.php?page=ih-users&ih_sync_done='.count($rows))); exit;
    }
    
});

function ih_notify_new_listing($id, $type) {
    $admin_email = get_option('admin_email');
    $user = wp_get_current_user();
    wp_mail($admin_email, "New $type Listing Pending", "A new $type listing ($id) has been submitted by {$user->display_name} and is waiting for approval.");
    wp_mail($user->user_email, "Listing Received", "Hi {$user->display_name}, your $type listing has been received. Admin will approve it shortly.");
}

/* ═══════════════════════════════════════
   HELPER: STATUS BADGE
═══════════════════════════════════════ */
function ih_badge( $status ) {
    $map = ['Approved'=>'badge-approved','Pending'=>'badge-pending','Rejected'=>'badge-rejected','Blocked'=>'badge-rejected','Unblocked'=>'badge-approved','Active'=>'badge-approved'];
    $cls = $map[$status] ?? 'badge-pending';
    return "<span class=\"ih-badge {$cls}\">".esc_html($status)."</span>";
}

/** Uploaded files / links for admin user drawer. */
function ih_user_files( $uid ) {
    $uid = (int) $uid;
    if ( ! $uid || ! current_user_can( 'manage_options' ) ) {
        return array();
    }
    $out = array();
    $file_map = array(
        'avatar'      => array( 'Profile photo', array( 'ih_profile_image_id', 'ih_profile_image', 'ihur_avatar', 'avatar' ) ),
        'id_document' => array( 'ID verification', array( 'ih_id_document', 'ihur_id_document', 'id_document' ) ),
        'certificate' => array( 'Company certificate', array( 'ih_certificate', 'ihur_certificate', 'certificate' ) ),
        'insurance'   => array( 'Insurance document', array( 'ih_insurance', 'ihur_insurance', 'insurance' ) ),
    );
    foreach ( $file_map as $label_key => $cfg ) {
        list( $label, $keys ) = $cfg;
        $att = '';
        foreach ( $keys as $key ) {
            $att = get_user_meta( $uid, $key, true );
            if ( $att !== '' && $att !== null ) {
                break;
            }
        }
        if ( ! $att ) {
            continue;
        }
        $url  = is_numeric( $att ) ? wp_get_attachment_url( (int) $att ) : $att;
        if ( ! $url ) {
            continue;
        }
        $path = is_numeric( $att ) ? get_attached_file( (int) $att ) : null;
        $mime = 'file';
        $size = '';
        if ( $path && file_exists( $path ) ) {
            $mime = mime_content_type( $path ) ?: 'file';
            $size = size_format( filesize( $path ) );
        } elseif ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
            $mime = 'link';
        }
        $out[] = array(
            'label' => $label,
            'url'   => $url,
            'mime'  => $mime,
            'size'  => $size,
        );
    }
    global $wpdb;
    $machines = $wpdb->get_results( $wpdb->prepare(
        "SELECT image_1, image_2, image_3, title FROM {$wpdb->prefix}ih_machines WHERE owner_id = %d",
        $uid
    ), ARRAY_A );
    foreach ( (array) $machines as $m ) {
        foreach ( array( 'image_1', 'image_2', 'image_3' ) as $col ) {
            if ( empty( $m[ $col ] ) ) {
                continue;
            }
            $img_url = is_numeric( $m[ $col ] ) ? wp_get_attachment_url( (int) $m[ $col ] ) : $m[ $col ];
            if ( $img_url ) {
                $out[] = array(
                    'label' => 'Listing image — ' . ( $m['title'] ?: 'Machine' ),
                    'url'   => $img_url,
                    'mime'  => 'image',
                    'size'  => '',
                );
            }
        }
    }
    $tools = $wpdb->get_results( $wpdb->prepare(
        "SELECT image_1, image_2, image_3, title FROM {$wpdb->prefix}ih_tools WHERE owner_id = %d",
        $uid
    ), ARRAY_A );
    foreach ( (array) $tools as $t ) {
        foreach ( array( 'image_1', 'image_2', 'image_3' ) as $col ) {
            if ( empty( $t[ $col ] ) ) {
                continue;
            }
            $img_url = is_numeric( $t[ $col ] ) ? wp_get_attachment_url( (int) $t[ $col ] ) : $t[ $col ];
            if ( $img_url ) {
                $out[] = array(
                    'label' => 'Listing image — ' . ( $t['title'] ?: 'Tool' ),
                    'url'   => $img_url,
                    'mime'  => 'image',
                    'size'  => '',
                );
            }
        }
    }
    $web = get_user_meta( $uid, 'ih_website', true );
    if ( ! $web ) {
        $web = get_user_meta( $uid, 'website', true );
    }
    if ( $web && filter_var( $web, FILTER_VALIDATE_URL ) ) {
        $out[] = array( 'label' => $web, 'url' => $web, 'mime' => 'link', 'size' => '' );
    }
    return $out;
}

/** Render AJAX drawer body for admin user record. */
function ih_render_user_drawer( $uid ) {
    $uid = (int) $uid;
    if ( ! $uid || ! current_user_can( 'manage_options' ) ) {
        return '';
    }
    $user = get_userdata( $uid );
    if ( ! $user ) {
        return '<p class="ih-u-empty">User not found.</p>';
    }
  global $wpdb;
    $ih_meta = function_exists( 'ih_db_user_meta' ) ? ih_db_user_meta( $uid ) : array();
    $ih_meta = $ih_meta ?: array();
    $blocked = ! empty( $ih_meta['blocked'] );
    $city    = '';
    foreach ( array( 'city', 'ih_city', 'location', 'billing_city' ) as $k ) {
        $v = get_user_meta( $uid, $k, true );
        if ( $v ) { $city = $v; break; }
    }
    $unique_id = function_exists( 'ihur_saved_uid' )
        ? ihur_saved_uid( $uid, $user->display_name, $city )
        : ( get_user_meta( $uid, 'ih_unique_id', true ) ?: 'USR-' . $uid );
    $company = get_user_meta( $uid, 'ih_company_name', true ) ?: get_user_meta( $uid, 'company_name', true );
    $role    = get_user_meta( $uid, 'ih_biz_role', true ) ?: get_user_meta( $uid, 'business_role', true );
    $job     = get_user_meta( $uid, 'ih_job_title', true ) ?: get_user_meta( $uid, 'job_title', true );
    $phone   = get_user_meta( $uid, 'ih_office_number', true ) ?: get_user_meta( $uid, 'ih_phone', true );
    $whatsapp = get_user_meta( $uid, 'ih_whatsapp', true ) ?: $phone;
    $address  = get_user_meta( $uid, 'ih_address', true ) ?: get_user_meta( $uid, 'address', true );
    $postcode = get_user_meta( $uid, 'ih_postcode', true ) ?: get_user_meta( $uid, 'postcode', true );
    $website  = get_user_meta( $uid, 'ih_website', true ) ?: $user->user_url;
    $email    = $user->user_email;
    $confirmed = (bool) $email;
    $profile = array(
        'businessRole'   => $role,
        'companyName'    => $company,
        'contactName'    => $user->display_name,
        'jobTitle'       => $job,
        'address'        => $address,
        'townCity'       => $city,
        'postcode'       => $postcode,
        'officeNumber'   => $phone,
        'whatsappNumber' => $whatsapp,
        'websiteUrl'     => $website,
        'email'          => $email,
        'confirmedEmail' => $email,
    );
    $completion = 0;
    if ( function_exists( 'ihur_completion' ) ) {
        $completion = ihur_completion( $profile );
    } else {
        $keys = array( 'businessRole', 'companyName', 'contactName', 'jobTitle', 'address', 'townCity', 'postcode', 'officeNumber', 'whatsappNumber', 'websiteUrl', 'email' );
        $filled = 0;
        foreach ( $keys as $k ) {
            if ( ! empty( $profile[ $k ] ) && $profile[ $k ] !== 'Not provided' ) {
                $filled++;
            }
        }
        $completion = (int) round( ( $filled / max( 1, count( $keys ) ) ) * 100 );
    }
    $missing = array();
    foreach ( array(
        'businessRole' => 'Business role', 'companyName' => 'Company', 'contactName' => 'Contact name',
        'jobTitle' => 'Job title', 'address' => 'Address', 'townCity' => 'Town / city',
        'postcode' => 'Postcode', 'officeNumber' => 'Office phone', 'whatsappNumber' => 'WhatsApp',
        'websiteUrl' => 'Website', 'email' => 'Email',
    ) as $k => $lab ) {
        $v = isset( $profile[ $k ] ) ? trim( (string) $profile[ $k ] ) : '';
        if ( $v === '' || $v === 'Not provided' ) {
            $missing[] = $lab;
        }
    }
    $listings = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ih_machines WHERE owner_id = %d", $uid ) )
        + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ih_tools WHERE owner_id = %d", $uid ) );
    $req_row = $wpdb->get_row( $wpdb->prepare(
        "SELECT COUNT(*) AS total, SUM(CASE WHEN LOWER(TRIM(status))='pending' THEN 1 ELSE 0 END) AS pending FROM {$wpdb->prefix}ih_requests WHERE user_id = %d",
        $uid
    ) );
    $requests = $req_row ? (int) $req_row->total : 0;
    $pending  = $req_row ? (int) $req_row->pending : 0;
    $files    = ih_user_files( $uid );
    $registered = date_i18n( 'd M Y', strtotime( $user->user_registered ) );
    $last_raw   = get_user_meta( $uid, 'ih_last_active', true );
    if ( $last_raw ) {
        $last_active = human_time_diff( strtotime( $last_raw ), current_time( 'timestamp' ) ) . ' ago';
    } else {
        $last_active = '—';
    }
    $message_url = admin_url( 'admin.php?page=ih-messages&user_id=' . $uid );
    $edit_url    = admin_url( 'admin.php?page=ih-users&view=' . $uid );
    $drawer_uid  = $uid;
    $uid_label   = function_exists( 'ih_user_uid_label' )
        ? ih_user_uid_label( $unique_id, $uid )
        : ( strtoupper( $unique_id ) . ' · USR-' . $uid );
    $is_verified = $completion >= 70 && $confirmed;
    $file_count  = count( $files );
    ob_start();
    include IH_DIR . 'pages/partials/ih-user-drawer-record.php';
    return ob_get_clean();
}

add_action( 'wp_ajax_ih_user_record', function () {
    ih_require_admin_ajax();
    check_ajax_referer( 'ih_nonce', 'nonce' );
    $uid = (int) ( $_POST['uid'] ?? 0 );
    echo ih_render_user_drawer( $uid );
    wp_die();
} );

/** Avatar + name cell for admin request tables (messages + dashboard). */
function ih_request_user_cell( $user_id, $name = '' ) {
    $uid     = (int) $user_id;
    $name    = trim( (string) $name );
    $avatar  = '';
    if ( $uid ) {
        $avatar = trim( (string) get_user_meta( $uid, 'ih_profile_image', true ) );
        if ( $avatar === '' ) {
            $avatar = get_avatar_url( $uid, array( 'size' => 64 ) );
        }
        if ( $name === '' ) {
            $name = ih_resolve_user_name( $uid );
        }
    }
    if ( $name === '' ) {
        $name = 'Unknown user';
    }
    $initial = strtoupper( substr( $name, 0, 1 ) ) ?: '?';
    $tag     = $uid ? 'a' : 'span';
    $href    = $uid ? ' href="' . esc_url( admin_url( 'admin.php?page=ih-users&view=' . $uid ) ) . '"' : '';
    $title   = $uid ? ' title="Open user profile"' : '';
    $html    = '<' . $tag . $href . $title . ' class="ih-req-user-cell">';
    $html   .= '<span class="ih-req-avatar" aria-hidden="true">';
    if ( $avatar ) {
        $html .= '<img src="' . esc_url( $avatar ) . '" alt="" loading="lazy" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';">';
        $html .= '<i style="display:none;">' . esc_html( $initial ) . '</i>';
    } else {
        $html .= '<i>' . esc_html( $initial ) . '</i>';
    }
    $html .= '</span>';
    $html .= '<span class="ih-req-name-text">' . esc_html( $name ) . '</span>';
    $html .= '</' . $tag . '>';
    return $html;
}

/** Human-readable IDs and intent labels for admin request tables. */
function ih_request_listing_ref( $listing_type, $listing_id ) {
    $type = sanitize_key( (string) $listing_type );
    $id   = (int) $listing_id;

    if ( $type === 'tool' ) {
        return 'TL-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
    }
    if ( $type === 'profile_access' || $type === 'user' || $type === 'profile' ) {
        return $id > 0 ? 'USER-' . $id : 'USER-INFO';
    }
    if ( $id <= 0 ) {
        return 'ADMIN-CHAT';
    }
    return 'MCH-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
}

function ih_request_intent_meta( $request ) {
    $type       = sanitize_key( (string) ( $request['listing_type'] ?? $request['type'] ?? '' ) );
    $listing_id = (int) ( $request['listing_id'] ?? 0 );

    if ( $type === 'profile_access' || $type === 'user' || $type === 'profile' ) {
        return [
            'key'   => 'profile',
            'label' => 'User information request',
            'help'  => 'User wants protected profile/contact information. Keep admin in the approval flow.',
        ];
    }

    if ( in_array( $type, [ 'machine', 'tool', 'machine_contact', 'tool_contact' ], true ) && $listing_id > 0 ) {
        if ( function_exists( 'ih_request_is_owner_listing_approval' ) && ih_request_is_owner_listing_approval( $request ) ) {
            return [
                'key'   => 'listing_approval',
                'label' => ( $type === 'tool' || $type === 'tool_contact' ) ? 'Tool listing approval' : 'Machine listing approval',
                'help'  => 'Owner submitted a listing for admin approval before it appears on the marketplace.',
            ];
        }
        $label = ( $type === 'tool' || $type === 'tool_contact' ) ? 'Tool contact request' : 'Machine contact request';
        return [
            'key'   => $type,
            'label' => $label,
            'help'  => 'User is requesting approval to contact or message about this listing.',
        ];
    }

    return [
        'key'   => 'admin',
        'label' => 'Message admin',
        'help'  => 'User wants to message admin directly. Admin remains in the chat.',
    ];
}

function ih_request_listing_detail_url( $listing_type, $listing_id ) {
    $type = sanitize_key( (string) $listing_type );
    $id   = (int) $listing_id;
    if ( $id <= 0 ) {
        return '';
    }
    if ( $type === 'tool' ) {
        return admin_url( 'admin.php?page=ih-tool-detail&tool_id=' . $id );
    }
    if ( $type === 'machine' ) {
        return admin_url( 'admin.php?page=ih-machine-detail&machine_id=' . $id );
    }
    return '';
}

function ih_request_contact_actions( $email = '', $phone = '' ) {
    $email       = sanitize_email( (string) $email );
    $phone_label = trim( (string) $phone );
    $phone_href  = preg_replace( '/[^\d\+]/', '', $phone_label );
    $wa_number   = preg_replace( '/\D/', '', $phone_label );
    $gmail_url   = $email ? 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode( $email ) : '';
    $html        = '<span class="ih-contact-actions">';

    if ( $email ) {
        $html .= '<span class="ih-contact-line"><span class="ih-contact-text">' . esc_html( $email ) . '</span><a class="ih-contact-icon is-email" href="' . esc_url( $gmail_url ) . '" target="_blank" rel="noopener" title="Open Gmail">Email</a></span>';
    }

    if ( $phone_href ) {
        $html .= '<span class="ih-contact-line"><span class="ih-contact-text">' . esc_html( $phone_label ) . '</span>';
        $html .= '<a class="ih-contact-icon is-call" href="' . esc_url( 'tel:' . $phone_href ) . '" title="Call user">Call</a>';
        $html .= '<a class="ih-contact-icon is-sms" href="' . esc_url( 'sms:' . $phone_href ) . '" title="Text user">SMS</a>';
        if ( strlen( $wa_number ) >= 7 ) {
            $html .= '<a class="ih-contact-icon is-wa" href="' . esc_url( 'https://wa.me/' . $wa_number ) . '" target="_blank" rel="noopener" title="Open WhatsApp">WA</a>';
        }
        $html .= '</span>';
    }

    if ( ! $email && ! $phone_href ) {
        $html .= '<span class="ih-contact-text">No contact saved</span>';
    }

    $html .= '</span>';
    return $html;
}

function ih_tag( $text ) { return '<span class="ih-tag-pill">'.esc_html($text).'</span>'; }

/* ═══════════════════════════════════════
   PAGE CALLBACKS
═══════════════════════════════════════ */
function ih_page_dashboard()      { include IH_DIR.'pages/dashboard.php'; }
function ih_page_users()          { include IH_DIR.'pages/users.php'; }
function ih_page_machines()       { include IH_DIR.'pages/machines.php'; }
function ih_page_add_machine()    { include IH_DIR.'pages/add-machine.php'; }
function ih_page_tools()          { include IH_DIR.'pages/tools.php'; }
function ih_page_add_tool()       { include IH_DIR.'pages/add-tool.php'; }
function ih_page_messages()       { include IH_DIR.'pages/messages.php'; }
function ih_page_requests()       { include IH_DIR.'pages/requests.php'; }
function ih_page_activity()       { include IH_DIR.'pages/activity.php'; }
function ih_page_tool_detail()      { include IH_DIR.'pages/tool-detail.php'; }
function ih_page_machine_detail()   { include IH_DIR.'pages/machine-detail.php'; }
function ih_page_edit_tool()        { include IH_DIR.'pages/edit-tool.php'; }
function ih_page_edit_machine()     { include IH_DIR.'pages/edit-machine.php'; }
function ih_page_user_dashboard()   { include IH_DIR.'pages/user/dashboard.php'; }
function ih_page_user_add_machine() { include IH_DIR.'pages/user/add-machine.php'; }
function ih_page_user_add_tool()    { include IH_DIR.'pages/user/add-tool.php'; }
function ih_page_user_messages()    { include IH_DIR.'pages/user/user-messages.php'; }
function ih_page_request_messages() { include IH_DIR.'pages/user/user-request-messages.php'; }
function ih_page_user_edit_machine(){ include IH_DIR.'pages/user/user-edit-machine.php'; }
function ih_page_user_edit_tool(){ include IH_DIR.'pages/user/user-edit-tool.php'; }
function ih_page_user_view_tool(){ include IH_DIR.'pages/user/user-view-tool.php'; }
function ih_page_user_view_machine(){ include IH_DIR.'pages/user/user-view-machine.php'; }
function ih_page_user_edit_profile(){ include IH_DIR.'pages/user/user-edit-profile.php'; }


function ih_is_blocked($user_id) {
    if (!$user_id) return false;
    global $wpdb;
    $table_blocked = $wpdb->get_var($wpdb->prepare("SELECT blocked FROM {$wpdb->prefix}ih_user_meta WHERE user_id=%d",$user_id));
    if ($table_blocked !== null) return (int)$table_blocked === 1;
    $status     = get_user_meta($user_id,'ih_status',true);
    $is_blocked = get_user_meta($user_id,'ih_blocked',true);
    return ($status==='blocked' || (int)$is_blocked===1);
}

/* ═══════════════════════════════════════
   AJAX: User Profile for Messages Panel
═══════════════════════════════════════ */
add_action('wp_ajax_ih_get_user_profile', function() {
    ih_require_admin_ajax();
    check_ajax_referer('ih_nonce','nonce');
    global $wpdb;

    $uid = intval($_POST['user_id'] ?? 0);
    if (!$uid) wp_send_json_error(['message'=>'Invalid user']);

    $wp_user = get_userdata($uid);
    if (!$wp_user) wp_send_json_error(['message'=>'User not found']);

    /* ── Helper: multi-key meta lookup ── */
    $gm = function($uid, ...$keys) {
        foreach ($keys as $k) {
            $v = get_user_meta($uid, $k, true);
            if ($v !== '' && $v !== false) return $v;
        }
        return '';
    };

    /* ── User info ── */
    $meta = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ih_user_meta WHERE user_id=%d", $uid
    ), ARRAY_A);

    $user = [
        'id'            => $uid,
        'name'          => $wp_user->display_name,
        'email'         => $wp_user->user_email,
        'avatar'        => get_avatar_url($uid, ['size'=>80]),
        'joined'        => date_i18n('d/m/Y', strtotime($wp_user->user_registered)),
        'phone'         => $meta['phone'] ?? $gm($uid,'phone','ih_phone','billing_phone'),
        'company'       => $meta['company'] ?? $gm($uid,'company_name','ih_company','billing_company'),
        'job_title'     => $meta['job_title'] ?? $gm($uid,'job_title','ih_job_title'),
        'address'       => $meta['address'] ?? $gm($uid,'address','ih_address','billing_address_1'),
        'city'          => $gm($uid,'city','ih_city','billing_city') ?: ($meta['location'] ?? ''),
        'postcode'      => $gm($uid,'postcode','ih_postcode','billing_postcode'),
        'business_role' => $gm($uid,'business_role','ih_business_role'),
        'website'       => $gm($uid,'website','ih_website') ?: $wp_user->user_url,
    ];

    /* ── Latest listing (machine first, then tool) ── */
    $listing = null;
    $m = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ih_machines WHERE owner_id=%d ORDER BY id DESC LIMIT 1", $uid
    ), ARRAY_A);
    if (!$m) {
        // fallback: check threads for listing
        $thread = $wpdb->get_row($wpdb->prepare(
            "SELECT listing_id, listing_type FROM {$wpdb->prefix}ih_threads WHERE user_id=%d ORDER BY id DESC LIMIT 1", $uid
        ), ARRAY_A);
        if ($thread && $thread['listing_id']) {
            if ($thread['listing_type'] === 'tool') {
                $m = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ih_tools WHERE id=%d", $thread['listing_id']), ARRAY_A);
                if ($m) $m['_ltype'] = 'tool';
            } else {
                $m = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ih_machines WHERE id=%d", $thread['listing_id']), ARRAY_A);
                if ($m) $m['_ltype'] = 'machine';
            }
        }
    } else {
        $m['_ltype'] = 'machine';
    }

    if ($m) {
        $ltype = $m['_ltype'] ?? 'machine';
        $mats  = [];
        foreach (['abs','pp','pe','pa','pc','peek'] as $mat) {
            if (!empty($m['materials_'.$mat])) $mats[] = strtoupper($mat);
        }
        $listing = [
            'id'            => (int)$m['id'],
            'listing_type'  => $ltype,
            'title'         => $m['title'] ?? '',
            'company'       => $m['owner_name'] ?? ($wp_user->display_name ?? ''),
            'type'          => $m['machine_type'] ?? $m['mould_type'] ?? '',
            'image'         => $m['image_1'] ?? '',
            'status'        => !empty($m['available']) ? 'Available Now' : 'Unavailable',
            'listing_date'  => !empty($m['listing_date']) ? date_i18n('d M Y', strtotime($m['listing_date'])) : '—',
            'expiry_date'   => !empty($m['expiry_date'])  ? date_i18n('d M Y', strtotime($m['expiry_date']))  : '—',
            'clamping_force'=> $m['clamping_force'] ?? '',
            'shot_size'     => $m['shot_size']      ?? '',
            'spec1_label'   => $ltype === 'tool' ? 'Mould Type'  : 'Clamping Force',
            'spec2_label'   => $ltype === 'tool' ? 'Material'    : 'Shot Size',
            'spec1'         => $ltype === 'tool' ? ($m['mould_type']??'') : ($m['clamping_force']??''),
            'spec2'         => $ltype === 'tool' ? ($m['material']??'')   : ($m['shot_size']??''),
            'materials'     => implode(',', $mats),
            'location'      => $m['location'] ?? '',
            'operating_hours'=> $m['operating_hours'] ?? '',
            'utilization'   => $m['utilization'] ?? '',
        ];
    }

    /* ── Media & Docs from chat attachments — all formats ── */
    $thread_id = ih_get_thread_by_user_id($uid);
    $media = []; $docs = [];
    if ($thread_id) {
        /* Fetch all file messages — old format: "📎 File:", new: "🖼 " or "📎 " */
        $chat_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT message FROM {$wpdb->prefix}ih_chats
             WHERE thread_id=%d
               AND (message LIKE '📎%' OR message LIKE '🖼%')",
            $thread_id
        ), ARRAY_A);

        foreach ($chat_rows as $row) {
            $msg = $row['message'];
            $fname = ''; $furl = '';

            // New image format: 🖼 filename (url)
            if ( preg_match( '/🖼\s+(.+?)\s+\((https?:\/\/[^\)]+)\)/', $msg, $m ) ) {
                $fname = trim($m[1]);
                $furl  = trim($m[2]);
                $media[] = ['name'=>$fname, 'url'=>$furl, 'size'=>0];
                continue;
            }
            // New file format: 📎 filename (url)  [NOT "📎 File:"]
            if ( preg_match( '/📎\s+(?!File:)(.+?)\s+\((https?:\/\/[^\)]+)\)/', $msg, $m ) ) {
                $fname = trim($m[1]);
                $furl  = trim($m[2]);
                $item  = ['name'=>$fname, 'url'=>$furl, 'size'=>0];
                if ( preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $fname) ) $media[] = $item;
                else $docs[] = $item;
                continue;
            }
            // Legacy format: 📎 File: filename (url)
            if ( preg_match( '/📎 File:\s+(.+?)\s+\((https?:\/\/[^\)]+)\)/', $msg, $m ) ) {
                $fname = trim($m[1]);
                $furl  = trim($m[2]);
                $item  = ['name'=>$fname, 'url'=>$furl, 'size'=>0];
                if ( preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $fname) ) $media[] = $item;
                else $docs[] = $item;
                continue;
            }
        }
    }

    wp_send_json_success([
        'user'    => $user,
        'listing' => $listing,
        'media'   => $media,
        'docs'    => $docs,
        'links'   => $user['website'] ? [['url'=>$user['website'],'title'=>$user['website']]] : [],
    ]);
});
add_action('admin_init', function() {
    if ( isset($_POST['ih_user_tool_edit_submit']) ) {
        if ( ! is_user_logged_in() ) wp_die('Please login first.');
        if ( ! wp_verify_nonce($_POST['ih_user_nonce'] ?? '', 'ih_user_edit_tool') ) wp_die('Security check failed.');

        global $wpdb;
        $user_id = get_current_user_id();
        $tool_id = intval($_POST['tool_id'] ?? 0);

        $owner = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT owner_id FROM {$wpdb->prefix}ih_tools WHERE id=%d", $tool_id
        ));
        if ($owner === 0) {
            $wpdb->update($wpdb->prefix.'ih_tools', ['owner_id'=>$user_id], ['id'=>$tool_id]);
            $owner = $user_id;
        }
        if ($owner !== $user_id) wp_die('Unauthorized.');

        $existing_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ih_tools" );

        $fields = ['title','part_name','part_dimensions','part_description','part_weight',
                   'num_cavities','owner_name','location','material_grade','colour',
                   'material','tolerance','surface_finish','draft_angle',
                   'mould_type','mould_material','mould_condition','num_cavities_spec',
                   'runner_type','gate_type','ejector_type','nozzle_type','clamp_drive_type',
                   'toggle_clamp_type','injection_stages','mould_weight','mould_dimensions','mould_location',
                   'required_qty','annual_volume','cycle_time','min_order_qty','packaging','material_supplied',
                   'clamping_required','compatible_specs','clamp_force','shot_weight','tie_bar','opening_stroke',
                   'hot_runner_controller','hot_runner_zones',
                   'water_cooled','suck_pump','food_grade','medical_grade','iml','automation',
                   'listing_date','expiry_date',
                   // Clamp-tonnage inputs — projected area (cm²) + cavity pressure (bar).
                   'projected_area','cavity_pressure'];
        $data = [];
        $toggle_fields = ['water_cooled','suck_pump','food_grade','medical_grade','iml','automation'];
        $ih_post_scalar = static function( $key ) {
            $value = $_POST[$key] ?? '';
            if ( is_array( $value ) ) {
                $value = end( $value );
            }
            return sanitize_text_field( wp_unslash( (string) $value ) );
        };
        foreach ($fields as $f) {
            // Only write columns that actually exist + only update posted keys.
            if ( ! in_array( $f, $existing_cols, true ) ) {
                continue;
            }
            if ( $f === 'num_cavities_spec' ) {
                $data[$f] = ( isset($_POST['num_cavities_spec']) && $_POST['num_cavities_spec'] !== '' )
                    ? absint($_POST['num_cavities_spec']) : 0;
                continue;
            }
            if ( in_array( $f, $toggle_fields, true ) ) {
                $raw = $ih_post_scalar( $f );
                $data[$f] = in_array( $raw, ['Yes','1','on','true'], true ) ? 'Yes' : 'No';
                continue;
            }
            $data[$f] = $ih_post_scalar( $f );
        }
        foreach (['tolerance_abs','tolerance_pp','tolerance_pe'] as $cb) {
            if ( in_array( $cb, $existing_cols, true ) ) {
                $data[$cb] = isset($_POST[$cb]) ? 1 : 0;
            }
        }

        if (!empty($_FILES['image_1']['tmp_name'])) {
            require_once ABSPATH.'wp-admin/includes/image.php';
            require_once ABSPATH.'wp-admin/includes/file.php';
            require_once ABSPATH.'wp-admin/includes/media.php';
            for ($i=1;$i<=3;$i++) {
                if (!empty($_FILES["image_{$i}"]['tmp_name'])) {
                    $_FILES['upload_img'] = $_FILES["image_{$i}"];
                    $aid = media_handle_upload('upload_img', 0);
                    if (!is_wp_error($aid)) $data["image_{$i}"] = wp_get_attachment_url($aid);
                }
            }
        }

        $wpdb->update($wpdb->prefix.'ih_tools', $data, ['id'=>$tool_id, 'owner_id'=>$user_id]);

        if ( function_exists( 'ih_mark_listing_pending_review' ) ) {
            ih_mark_listing_pending_review( $user_id, $tool_id, 'tool' );
        }

        wp_redirect(admin_url('admin.php?page=ih-user-edit-tool&tool_id='.$tool_id.'&saved=1'));
        exit;
    }
}, 10);

/* ── Admin tool edit save (mirrors the owner ih_user_tool_edit_submit handler,
   but gated on the admin capability and able to edit ANY listing). Persists the
   full Add-Tool field set; only writes columns that exist and only updates
   posted keys, so it is safe before migrations and never nulls out untouched
   columns. ── */
add_action('admin_init', function() {
    if ( ! isset($_POST['ih_tool_edit_submit']) ) {
        return;
    }
    if ( ! current_user_can('manage_options') ) {
        wp_die('You do not have permission to edit this tool.');
    }
    if ( ! check_admin_referer('ih_edit_tool','ih_nonce_field') ) {
        wp_die('Security check failed.');
    }

    global $wpdb;
    $table   = $wpdb->prefix . 'ih_tools';
    $tool_id = intval($_POST['tool_id'] ?? 0);
    if ( ! $tool_id ) {
        wp_redirect(admin_url('admin.php?page=ih-tools')); exit;
    }

    // Confirm the target listing exists before attempting any write.
    $tool_exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE id = %d", $tool_id ) );
    if ( ! $tool_exists ) {
        wp_redirect(admin_url('admin.php?page=ih-tools')); exit;
    }

    $existing_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" );

    $fields = array(
        'title','part_name','part_dimensions','part_weight','num_cavities','owner_name','location',
        'material_grade','colour','material','tolerance','surface_finish','draft_angle',
        'mould_type','mould_material','mould_condition','tool_condition',
        'runner_type','gate_type','ejector_type','nozzle_type','clamp_drive_type','toggle_clamp_type',
        'injection_stages','compatible_specs','clamping_required','mould_weight','mould_dimensions','mould_location',
        'required_qty','annual_volume','cycle_time','min_order_qty','packaging','material_supplied',
        'clamp_force','shot_weight','tie_bar','opening_stroke','hot_runner_controller','hot_runner_zones',
        'water_cooled','suck_pump','food_grade','medical_grade','iml','automation',
        'listing_date','expiry_date',
        // Clamp-tonnage inputs — projected area (cm²) + cavity pressure (bar).
        'projected_area','cavity_pressure',
    );
    $toggle_fields = array( 'water_cooled','suck_pump','food_grade','medical_grade','iml','automation' );

    $data = array();
    foreach ( $fields as $f ) {
        if ( ! in_array( $f, $existing_cols, true ) ) {
            continue;
        }
        $data[ $f ] = in_array( $f, $toggle_fields, true ) ? ih_yes_no_post_value( $f ) : ih_post_scalar_value( $f );
    }
    if ( in_array( 'tool_condition', $existing_cols, true ) && empty( $data['tool_condition'] ) && ! empty( $data['mould_condition'] ) ) {
        $data['tool_condition'] = $data['mould_condition'];
    }
    if ( in_array( 'part_description', $existing_cols, true ) ) {
        $data['part_description'] = sanitize_textarea_field( wp_unslash( $_POST['part_description'] ?? '' ) );
    }
    if ( in_array( 'num_cavities_spec', $existing_cols, true ) ) {
        $data['num_cavities_spec'] = ( isset( $_POST['num_cavities_spec'] ) && $_POST['num_cavities_spec'] !== '' )
            ? absint( $_POST['num_cavities_spec'] ) : 0;
    }
    foreach ( array( 'tolerance_abs','tolerance_pp','tolerance_pe' ) as $cb ) {
        if ( in_array( $cb, $existing_cols, true ) ) {
            $data[ $cb ] = isset( $_POST[ $cb ] ) ? 1 : 0;
        }
    }

    if ( ! empty($_FILES['image_1']['tmp_name']) || ! empty($_FILES['image_2']['tmp_name']) || ! empty($_FILES['image_3']['tmp_name']) ) {
        require_once ABSPATH.'wp-admin/includes/image.php';
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        for ( $i = 1; $i <= 3; $i++ ) {
            $key = "image_{$i}";
            if ( ! in_array( $key, $existing_cols, true ) || empty( $_FILES[ $key ]['tmp_name'] ) ) {
                continue;
            }
            $_FILES['upload_img'] = $_FILES[ $key ];
            $aid = media_handle_upload( 'upload_img', 0 );
            if ( ! is_wp_error( $aid ) ) {
                $data[ $key ] = wp_get_attachment_url( $aid );
            }
        }
    }

    $wpdb->update( $table, $data, array( 'id' => $tool_id ) );
    ih_log_activity( 'listing', 'Tool updated: #' . $tool_id, array( 'tool_id' => $tool_id ) );

    wp_redirect( admin_url( 'admin.php?page=ih-edit-tool&tool_id=' . $tool_id . '&saved=1' ) );
    exit;
}, 10);

/* ═══════════════════════════════════════
   FRONTEND SHORTCODES
   Add Machine: [ih_frontend_add_machine]
   Add Tool:    [ih_frontend_add_tool]
═══════════════════════════════════════ */

/* Auto-create frontend pages — visit once:
   /wp-admin/admin.php?page=ih-dashboard&ih_create_pages=1 */
add_action('admin_init', function(){
    if( !isset($_GET['ih_create_pages']) || !current_user_can('administrator') ) return;
    $pages = [
        ['title'=>'Add Machine','slug'=>'add-machine','content'=>'[ih_frontend_add_machine]'],
        ['title'=>'Add Tool',   'slug'=>'add-tool',   'content'=>'[ih_frontend_add_tool]'],
    ];
    foreach($pages as $p){
        if(!get_page_by_path($p['slug'])){
            wp_insert_post(['post_title'=>$p['title'],'post_name'=>$p['slug'],
                'post_content'=>$p['content'],'post_status'=>'publish','post_type'=>'page']);
        }
    }
    flush_rewrite_rules();
    wp_redirect(admin_url('admin.php?page=ih-dashboard&pages_created=1')); exit;
});

function ih_frontend_login_check(){
    if(!is_user_logged_in()){
        return '<div style="text-align:center;padding:60px 20px;">
            <div style="width:64px;height:64px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <h3 style="font-size:18px;font-weight:700;margin-bottom:8px;color:#111827;">Login Required</h3>
            <p style="color:#6b7280;margin-bottom:20px;">You need to be logged in to add a listing.</p>
            <a href="'.wp_login_url(get_permalink()).'" style="background:#1f3d2e;color:#fff;padding:11px 28px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;">Login / Register</a>
        </div>';
    }
    return null;
}

/* ── Add Machine Shortcode ── */
add_shortcode('ih_frontend_add_machine', function(){
    $check = ih_frontend_login_check(); if($check) return $check;
    $saved = false; $error = '';
    if(isset($_POST['ih_machine_submit_frontend'])){
        if(!wp_verify_nonce($_POST['ih_frontend_nonce']??'','ih_frontend_machine')){
            $error = 'Security check failed.';
        } else {
            global $wpdb;
            $data = ['owner_id'=>get_current_user_id(),'available'=>0,'listing_status'=>'pending'];
            $fields = ['title','brand','machine_type','year_manufacture','identical_count','clamping_force','shot_size',
                'screw_diameter','max_injection_pressure','tie_bar_spacing','max_mould_height','min_mould_height',
                'max_part_weight','max_part_dimensions','tolerance','engineering_grade','recycled_materials',
                'batch_size','min_order_qty','max_monthly_output','avg_cycle_time','operating_hours','utilization',
                'location','automation_level','robot_integration','multi_cavity','certifications','qc_tools',
                'tolerance_consistency','overmoulding','insert_moulding','iml','gas_assisted','thin_wall',
                'listing_date','expiry_date'];
            $toggle_fields = ['engineering_grade','recycled_materials','robot_integration','multi_cavity','overmoulding','insert_moulding','iml','gas_assisted','thin_wall'];
            foreach($fields as $f) {
                $data[$f] = in_array($f, $toggle_fields, true) ? ih_yes_no_post_value($f) : ih_post_scalar_value($f);
            }
            foreach(['materials_abs','materials_pp','materials_pe','materials_pa','materials_pc','materials_peek'] as $cb)
                $data[$cb] = isset($_POST[$cb]) ? 1 : 0;
            if(!empty($_FILES['image_1']['tmp_name'])){
                require_once ABSPATH.'wp-admin/includes/image.php';
                require_once ABSPATH.'wp-admin/includes/file.php';
                require_once ABSPATH.'wp-admin/includes/media.php';
                for($i=1;$i<=3;$i++){
                    if(!empty($_FILES["image_{$i}"]['tmp_name'])){
                        $_FILES['upload_img']=$_FILES["image_{$i}"];
                        $aid=media_handle_upload('upload_img',0);
                        if(!is_wp_error($aid)) $data["image_{$i}"]=wp_get_attachment_url($aid);
                    }
                }
            }
            $wpdb->insert($wpdb->prefix.'ih_machines',$data);
            $machine_id = (int) $wpdb->insert_id;
            if ( $machine_id && function_exists( 'ih_create_listing_approval_request' ) ) {
                ih_create_listing_approval_request( get_current_user_id(), $machine_id, 'machine' );
            }
            if(function_exists('ih_notify_new_listing')) ih_notify_new_listing($machine_id,'Machine');
            /* sync user meta */
            if(function_exists('ih_sync_user_meta')){
                $u = get_userdata(get_current_user_id());
                ih_sync_user_meta(get_current_user_id(),['location'=>$data['location']??'']);
            }
            $saved = true;
        }
    }
    ob_start();
    ?>
    <style>
    .ih-front-tool {
        max-width: 980px !important;
        margin: 0 auto !important;
        padding: 24px 18px 48px !important;
        font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #102625;
    }
    .ih-front-tool::before {
        content: "Tool posting workflow";
        display: inline-flex;
        margin: 0 0 12px;
        padding: 6px 10px;
        border: 1px solid rgba(200,255,0,.30);
        border-radius: 999px;
        color: #c8ff00;
        background: #102625;
        font: 700 11px/1 "DM Mono", monospace;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .ih-front-tool h2 {
        margin: 0 0 20px !important;
        padding: 30px 32px !important;
        border: 1px solid rgba(200,255,0,.16);
        border-radius: 22px;
        color: #fff !important;
        background: linear-gradient(135deg, #102625, #173936) !important;
        box-shadow: 0 18px 50px rgba(16,38,37,.12);
        font-size: 30px !important;
        letter-spacing: -.04em;
    }
    .ih-front-tool h2::after {
        content: "Add part data, mould specifications, production requirements and images. Admin approval keeps listings controlled.";
        display: block;
        max-width: 680px;
        margin-top: 8px;
        color: rgba(255,255,255,.76);
        font-size: 13px;
        font-weight: 500;
        letter-spacing: 0;
        line-height: 1.65;
    }
    .ih-front-tool > div:not(.ih-front-tool__ignore) {
        border-color: rgba(16,38,37,.12) !important;
        border-radius: 22px !important;
        box-shadow: 0 10px 32px rgba(16,38,37,.07) !important;
    }
    .ih-front-tool h3 {
        color: #102625 !important;
        font-size: 13px !important;
        font-weight: 800 !important;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .ih-front-tool label {
        color: #668277 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-size: 11px !important;
    }
    .ih-front-tool input,
    .ih-front-tool textarea {
        border-color: #cdded5 !important;
        border-radius: 14px !important;
        background: #fbfdfc !important;
        color: #102625 !important;
    }
    .ih-front-tool input:focus,
    .ih-front-tool textarea:focus {
        outline: 3px solid rgba(200,255,0,.35) !important;
        border-color: #102625 !important;
    }
    .ih-front-tool button[type="submit"],
    .ih-front-tool a[href*="add-tool"] {
        border-radius: 16px !important;
        background: #102625 !important;
        color: #c8ff00 !important;
        text-transform: uppercase;
        letter-spacing: .06em;
        box-shadow: 0 14px 30px rgba(16,38,37,.18);
    }
    @media (max-width: 720px) {
        .ih-front-tool h2 {
            padding: 24px 22px !important;
            font-size: 24px !important;
        }
        .ih-front-tool [style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
    <?php
    if($saved): ?>
    <div class="ih-front-tool ih-front-tool-success" style="text-align:center;padding:60px 20px;">
        <div style="width:80px;height:80px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h3 style="font-size:22px;font-weight:700;margin-bottom:8px;color:#111827;">Machine Added Successfully!</h3>
        <p style="color:#6b7280;margin-bottom:24px;">Admin will review and approve your listing shortly.</p>
        <a href="<?php echo esc_url(get_permalink()); ?>" style="background:#1f3d2e;color:#fff;padding:12px 32px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;">+ Add Another Machine</a>
    </div>
    <?php else: ?>
    <?php if($error): ?>
    <div style="background:#fee2e2;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;"><?php echo esc_html($error); ?></div>
    <?php endif; ?>
    <form class="ih-front-tool ih-front-tool-form" method="POST" enctype="multipart/form-data" style="max-width:860px;margin:0 auto;padding:8px 0 40px;">
        <?php wp_nonce_field('ih_frontend_machine','ih_frontend_nonce'); ?>
        <input type="hidden" name="ih_machine_submit_frontend" value="1">

        <h2 style="font-size:24px;font-weight:700;margin-bottom:24px;color:#111827;">Add New Machine</h2>

        <?php
        $sec_style = 'background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;margin-bottom:20px;';
        $grid2 = 'display:grid;grid-template-columns:1fr 1fr;gap:16px;';
        $lbl = 'display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:500;color:#374151;';
        $inp = 'padding:10px 13px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;width:100%;box-sizing:border-box;';
        $sel = $inp;
        ?>

        <div style="<?php echo $sec_style; ?>">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 16px;color:#111827;">Machine Identity</h3>
            <div style="<?php echo $grid2; ?>">
                <label style="<?php echo $lbl; ?>">Machine Brand / Title <span style="color:#ef4444;">*</span>
                    <input type="text" name="title" required placeholder="e.g. Engel Victory 150" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Year of Manufacture
                    <input type="text" name="year_manufacture" placeholder="e.g. 2019" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Machine Type
                    <select name="machine_type" style="<?php echo $sel; ?>">
                        <option value="Hydraulic">Hydraulic</option>
                        <option value="Electric">Electric</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                </label>
                <label style="<?php echo $lbl; ?>">Number of Identical Machines
                    <input type="number" name="identical_count" value="1" min="1" style="<?php echo $inp; ?>">
                </label>
            </div>
        </div>

        <div style="<?php echo $sec_style; ?>">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 16px;color:#111827;">Processing Specs</h3>
            <div style="<?php echo $grid2; ?>">
                <label style="<?php echo $lbl; ?>">Clamping Force (Tons)
                    <input type="text" name="clamping_force" placeholder="e.g. 150T" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Shot Size (grams)
                    <input type="text" name="shot_size" placeholder="e.g. 30g–120g" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Screw Diameter (mm)
                    <input type="text" name="screw_diameter" placeholder="mm" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Max Injection Pressure (bar)
                    <input type="text" name="max_injection_pressure" placeholder="bar" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Tie Bar Spacing (mm)
                    <input type="text" name="tie_bar_spacing" placeholder="mm" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Max Mould Height (mm)
                    <input type="text" name="max_mould_height" placeholder="mm" style="<?php echo $inp; ?>">
                </label>
            </div>
        </div>

        <div style="<?php echo $sec_style; ?>">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 16px;color:#111827;">Production & Location</h3>
            <div style="<?php echo $grid2; ?>">
                <label style="<?php echo $lbl; ?>">Location <span style="color:#ef4444;">*</span>
                    <input type="text" name="location" required placeholder="e.g. Manchester, UK" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Operating Hours/day
                    <input type="text" name="operating_hours" placeholder="e.g. 16h/day" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Utilization
                    <input type="text" name="utilization" placeholder="e.g. 65%" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Min Order Qty
                    <input type="text" name="min_order_qty" placeholder="e.g. 500" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Avg Cycle Time
                    <input type="text" name="avg_cycle_time" placeholder="e.g. 18 seconds" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Certifications
                    <input type="text" name="certifications" placeholder="e.g. ISO 9001" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Listing Date
                    <input type="date" name="listing_date" value="<?php echo date('Y-m-d'); ?>" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Expiry Date
                    <input type="date" name="expiry_date" value="<?php echo date('Y-m-d',strtotime('+6 months')); ?>" style="<?php echo $inp; ?>">
                </label>
            </div>
        </div>

        <div style="<?php echo $sec_style; ?>">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 14px;color:#111827;">Materials Supported</h3>
            <div style="display:flex;flex-wrap:wrap;gap:14px;">
                <?php foreach(['abs'=>'ABS','pp'=>'Polypropylene (PP)','pe'=>'Polyethylene (PE)','pa'=>'Nylon (PA)','pc'=>'Polycarbonate (PC)','peek'=>'PEEK'] as $k=>$v): ?>
                <label style="display:flex;align-items:center;gap:7px;font-size:13px;cursor:pointer;color:#374151;">
                    <input type="checkbox" name="materials_<?php echo $k; ?>" value="1" style="width:15px;height:15px;accent-color:#1f3d2e;"> <?php echo $v; ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="<?php echo $sec_style; ?>">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 16px;color:#111827;">Upload Images (Max 3)</h3>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                <?php for($i=1;$i<=3;$i++): ?>
                <label id="m-img-<?php echo $i; ?>" style="border:2px dashed #d1d5db;border-radius:10px;height:130px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;gap:6px;font-size:12px;color:#9ca3af;background:#f9fafb;position:relative;overflow:hidden;transition:border-color .2s;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Image <?php echo $i; ?></span>
                    <input type="file" name="image_<?php echo $i; ?>" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer;"
                        onchange="ihPrevImg(event,'m-img-<?php echo $i; ?>')">
                </label>
                <?php endfor; ?>
            </div>
        </div>

        <button type="submit" style="width:100%;background:#1f3d2e;color:#fff;padding:15px;border-radius:50px;border:none;font-size:16px;font-weight:700;cursor:pointer;margin-top:8px;">
            Upload Machine ↑
        </button>
    </form>
    <script>
    function ihPrevImg(e,id){
        var l=document.getElementById(id); if(!l||!e.target.files[0]) return;
        var r=new FileReader();
        r.onload=function(ev){
            l.style.backgroundImage='url('+ev.target.result+')';
            l.style.backgroundSize='cover'; l.style.backgroundPosition='center';
            l.style.borderColor='#22c55e'; l.innerHTML='';
        };
        r.readAsDataURL(e.target.files[0]);
    }
    </script>
    <?php endif;
    return ob_get_clean();
});

/* ── Add Tool Shortcode ── */
add_shortcode('ih_frontend_add_tool', function(){
    $check = ih_frontend_login_check(); if($check) return $check;
    $saved = false; $error = '';
    if(isset($_POST['ih_tool_submit_frontend'])){
        if(!wp_verify_nonce($_POST['ih_frontend_nonce']??'','ih_frontend_tool')){
            $error = 'Security check failed.';
        } else {
            global $wpdb;
            $data = ['owner_id'=>get_current_user_id(),'available'=>1,'listing_status'=>'available'];
            $fields = ['title','part_name','part_dimensions','part_description','part_weight','num_cavities',
                'owner_name','location','material_grade','colour','mould_type','mould_material','mould_condition',
                'num_cavities_spec','ejector_type','nozzle_type','annual_volume','cycle_time','min_order_qty',
                'material','clamping_required','compatible_specs','water_cooled','suck_pump','food_grade',
                'medical_grade','listing_date','expiry_date'];
            $toggle_fields = ['water_cooled','suck_pump','food_grade','medical_grade'];
            $ih_post_scalar = static function( $key ) {
                $value = $_POST[$key] ?? '';
                if ( is_array( $value ) ) {
                    $value = end( $value );
                }
                return sanitize_text_field( wp_unslash( (string) $value ) );
            };
            foreach($fields as $f) {
                if ( in_array( $f, $toggle_fields, true ) ) {
                    $raw = $ih_post_scalar( $f );
                    $data[$f] = in_array( $raw, ['Yes','1','on','true'], true ) ? 'Yes' : 'No';
                    continue;
                }
                $data[$f] = $ih_post_scalar( $f );
            }
            foreach(['tolerance_abs','tolerance_pp','tolerance_pe'] as $cb) $data[$cb] = isset($_POST[$cb]) ? 1 : 0;
            if(!empty($_FILES['image_1']['tmp_name'])){
                require_once ABSPATH.'wp-admin/includes/image.php';
                require_once ABSPATH.'wp-admin/includes/file.php';
                require_once ABSPATH.'wp-admin/includes/media.php';
                for($i=1;$i<=3;$i++){
                    if(!empty($_FILES["image_{$i}"]['tmp_name'])){
                        $_FILES['upload_img']=$_FILES["image_{$i}"];
                        $aid=media_handle_upload('upload_img',0);
                        if(!is_wp_error($aid)) $data["image_{$i}"]=wp_get_attachment_url($aid);
                    }
                }
            }
          if (empty($data['image_1'])) {
    $data['image_1'] = get_option('ih_default_tool_image', get_template_directory_uri() . '/assets/images/add-tool.jpeg');
}
            $wpdb->insert($wpdb->prefix.'ih_tools',$data);
            if(function_exists('ih_notify_new_listing')) ih_notify_new_listing($wpdb->insert_id,'Tool');
            $saved = true;
        }
    }
    ob_start();
    if($saved): ?>
    <div style="text-align:center;padding:60px 20px;">
        <div style="width:80px;height:80px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h3 style="font-size:22px;font-weight:700;margin-bottom:8px;color:#111827;">Tool Added Successfully!</h3>
        <p style="color:#6b7280;margin-bottom:24px;">Admin will review and approve your listing shortly.</p>
        <a href="<?php echo esc_url(get_permalink()); ?>" style="background:#1f3d2e;color:#fff;padding:12px 32px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px;">+ Add Another Tool</a>
    </div>
    <?php else: ?>
    <?php if($error): ?>
    <div style="background:#fee2e2;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;"><?php echo esc_html($error); ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" style="max-width:860px;margin:0 auto;padding:8px 0 40px;">
        <?php wp_nonce_field('ih_frontend_tool','ih_frontend_nonce'); ?>
        <input type="hidden" name="ih_tool_submit_frontend" value="1">

        <h2 style="font-size:24px;font-weight:700;margin-bottom:24px;color:#111827;">Add New Tool</h2>

        <?php
        $sec_style = 'background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;margin-bottom:20px;';
        $grid2 = 'display:grid;grid-template-columns:1fr 1fr;gap:16px;';
        $lbl = 'display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:500;color:#374151;';
        $inp = 'padding:10px 13px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;width:100%;box-sizing:border-box;';
        ?>

        <div style="<?php echo $sec_style; ?>">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 16px;color:#111827;">Tool Details</h3>
            <div style="<?php echo $grid2; ?>">
                <label style="<?php echo $lbl; ?>">Tool Title <span style="color:#ef4444;">*</span>
                    <input type="text" name="title" required placeholder="e.g. Medical Device Housing Mould" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Part Name
                    <input type="text" name="part_name" placeholder="e.g. Housing Cover" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Mould Type
                    <input type="text" name="mould_type" placeholder="Multi-Cavity" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Mould Material
                    <input type="text" name="mould_material" placeholder="H13 Steel" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Material
                    <input type="text" name="material" placeholder="PC" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Location <span style="color:#ef4444;">*</span>
                    <input type="text" name="location" required placeholder="e.g. Manchester, UK" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Min Order Qty
                    <input type="text" name="min_order_qty" placeholder="5,000" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Clamping Required
                    <input type="text" name="clamping_required" placeholder="70T" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Annual Volume
                    <input type="text" name="annual_volume" placeholder="50,000+" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Cycle Time
                    <input type="text" name="cycle_time" placeholder="24 seconds" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Listing Date
                    <input type="date" name="listing_date" value="<?php echo date('Y-m-d'); ?>" style="<?php echo $inp; ?>">
                </label>
                <label style="<?php echo $lbl; ?>">Expiry Date
                    <input type="date" name="expiry_date" value="<?php echo date('Y-m-d',strtotime('+6 months')); ?>" style="<?php echo $inp; ?>">
                </label>
            </div>
            <label style="<?php echo $lbl; ?> margin-top:14px;">Description
                <textarea name="part_description" rows="3" placeholder="Brief description of the tool" style="<?php echo $inp; ?> resize:vertical;"></textarea>
            </label>
        </div>

        <div style="<?php echo $sec_style; ?>">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 16px;color:#111827;">Upload Images (Max 3)</h3>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                <?php for($i=1;$i<=3;$i++): ?>
                <label id="t-img-<?php echo $i; ?>" style="border:2px dashed #d1d5db;border-radius:10px;height:130px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;gap:6px;font-size:12px;color:#9ca3af;background:#f9fafb;position:relative;overflow:hidden;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Image <?php echo $i; ?></span>
                    <input type="file" name="image_<?php echo $i; ?>" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer;"
                        onchange="ihPrevImg(event,'t-img-<?php echo $i; ?>')">
                </label>
                <?php endfor; ?>
            </div>
        </div>

        <button type="submit" style="width:100%;background:#1f3d2e;color:#fff;padding:15px;border-radius:50px;border:none;font-size:16px;font-weight:700;cursor:pointer;margin-top:8px;">
            Upload Tool ↑
        </button>
    </form>
    <script>
    function ihPrevImg(e,id){
        var l=document.getElementById(id); if(!l||!e.target.files[0]) return;
        var r=new FileReader();
        r.onload=function(ev){
            l.style.backgroundImage='url('+ev.target.result+')';
            l.style.backgroundSize='cover'; l.style.backgroundPosition='center';
            l.style.borderColor='#22c55e'; l.innerHTML='';
        };
        r.readAsDataURL(e.target.files[0]);
    }
    </script>
    <?php endif;
    return ob_get_clean();
});

/* ═══════════════════════════════════════
   USER FRONTEND: Add Machine / Tool
   Regular users ke liye — manage_options
   check nahi, sirf login check hai
═══════════════════════════════════════ */
add_action('admin_init', function() {

    /* ── User Add Machine ── */
    if ( isset($_POST['ih_user_machine_submit']) ) {
        $redirect_base = admin_url('admin.php?page=ih-user-add-machine');

        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url( $redirect_base ) );
            exit;
        }
        if ( ! isset($_POST['ih_user_nonce']) || ! wp_verify_nonce( $_POST['ih_user_nonce'], 'ih_user_add_machine' ) ) {
            wp_safe_redirect( add_query_arg( 'ih_error', rawurlencode('Security check failed. Please reload and try again.'), $redirect_base ) );
            exit;
        }

        $is_draft = ! empty($_POST['save_draft']);
        $title    = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        $location = sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) );
        if ( ! $is_draft && ( $title === '' || $location === '' ) ) {
            wp_safe_redirect( add_query_arg( 'ih_error', rawurlencode('Please complete the required fields: Machine brand / title and Location.'), $redirect_base ) );
            exit;
        }

        global $wpdb;
        $table         = $wpdb->prefix . 'ih_machines';
        $existing_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" );
        $data = ih_machine_form_data_from_post( $existing_cols, array(
            'owner_id' => get_current_user_id(),
            'available' => 0,
            'listing_status' => 'pending',
        ) );
        $data = ih_machine_upload_images_from_post( $existing_cols, $data );

        $ok         = $wpdb->insert( $table, $data );
        $machine_id = $ok ? (int) $wpdb->insert_id : 0;
        if ( ! $machine_id ) {
            wp_safe_redirect( add_query_arg( 'ih_error', rawurlencode('Could not save the machine. Please try again.'), $redirect_base ) );
            exit;
        }

        if ( function_exists( 'ih_create_listing_approval_request' ) ) {
            ih_create_listing_approval_request( get_current_user_id(), $machine_id, 'machine' );
        }

        if ( function_exists('ih_notify_admin_new_listing') ) {
            ih_notify_admin_new_listing(
                get_current_user_id(),
                $machine_id,
                'machine',
                $data['title'] ?: 'New Machine'
            );
        }

        $user = wp_get_current_user();
        wp_mail(
            get_option('admin_email'),
            'New Machine Listing Pending Approval',
            "User {$user->display_name} ({$user->user_email}) has submitted a new machine listing (ID: {$machine_id}) for approval.\n\nPlease review: " . admin_url('admin.php?page=ih-machines')
        );

        wp_safe_redirect( admin_url('admin.php?page=ih-user-add-machine&saved=1') );
        exit;
    }

    /* ── User Add Tool ──  Authoritative, hardened handler for the Figma (node 80:2) form.
       Security: logged-in + nonce + capability + forced owner_id ownership +
       sanitisation + dynamic column whitelist + validated uploads + safe redirects. */
    if ( isset($_POST['ih_user_tool_submit']) ) {

        $redirect_base = admin_url('admin.php?page=ih-user-add-tool');

        // 1) Authentication
        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url( $redirect_base ) );
            exit;
        }
        // 2) Nonce
        if ( ! isset($_POST['ih_user_nonce']) || ! wp_verify_nonce( $_POST['ih_user_nonce'], 'ih_user_add_tool' ) ) {
            wp_safe_redirect( add_query_arg( 'ih_error', rawurlencode('Security check failed. Please reload and try again.'), $redirect_base ) );
            exit;
        }
        // 3) Capability check (ownership is forced below; filterable for custom roles)
        $user_id = get_current_user_id();
        $can_add = (bool) apply_filters( 'ih_user_can_add_tool', current_user_can('read'), $user_id );
        if ( ! $can_add ) {
            wp_safe_redirect( add_query_arg( 'ih_error', rawurlencode('You do not have permission to add a tool.'), $redirect_base ) );
            exit;
        }

        global $wpdb;
        $table    = $wpdb->prefix . 'ih_tools';
        $is_draft = ! empty($_POST['save_draft']);

        // 4) Server-side validation (skipped for explicit "Save draft")
        $title    = sanitize_text_field( wp_unslash( $_POST['title']    ?? '' ) );
        $location = sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) );
        if ( ! $is_draft && ( $title === '' || $location === '' ) ) {
            wp_safe_redirect( add_query_arg( 'ih_error', rawurlencode('Please complete the required fields: Tool Title and Location.'), $redirect_base ) );
            exit;
        }

        // 5) Only write columns that actually exist (safe even before migration runs)
        $existing_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" );
        // "Save draft" parks the listing as a draft; "Publish" submits for approval (pending).
        $data = array( 'owner_id' => $user_id, 'available' => 0, 'listing_status' => $is_draft ? 'draft' : 'pending' );

        $text_fields = array(
            'title','part_name','part_dimensions','part_weight','num_cavities','owner_name','location',
            'material_grade','colour','material','tolerance','surface_finish','draft_angle',
            'mould_type','mould_material','mould_condition','tool_condition','runner_type','gate_type','ejector_type',
            'nozzle_type','clamp_drive_type','toggle_clamp_type','injection_stages','construction','mould_weight',
            'mould_dimensions','mould_location','tool_life',
            'required_qty','annual_volume','min_order_qty','cycle_time','packaging','material_supplied',
            'compatible_specs','clamping_required','clamp_force','shot_weight','tie_bar','opening_stroke',
            'hot_runner_controller','hot_runner_zones',
            'water_cooled','suck_pump','food_grade','medical_grade','iml','automation',
            // Clamp-tonnage inputs — projected area (cm²) + cavity pressure (bar).
            'projected_area','cavity_pressure',
        );
        $toggle_fields = array( 'water_cooled','suck_pump','food_grade','medical_grade','iml','automation' );
        $ih_post_scalar = static function( $key ) {
            $value = $_POST[$key] ?? '';
            if ( is_array( $value ) ) {
                $value = end( $value );
            }
            return sanitize_text_field( wp_unslash( (string) $value ) );
        };
        foreach ( $text_fields as $f ) {
            if ( in_array($f, $existing_cols, true) ) {
                if ( in_array( $f, $toggle_fields, true ) ) {
                    $raw = $ih_post_scalar( $f );
                    $data[$f] = in_array( $raw, array( 'Yes','1','on','true' ), true ) ? 'Yes' : 'No';
                    continue;
                }
                $data[$f] = $ih_post_scalar( $f );
            }
        }
        if ( isset( $data['material_supplied'] ) && $data['material_supplied'] === '' ) {
            $data['material_supplied'] = 'No — customer supplies';
        }
        if ( isset( $data['hot_runner_controller'] ) && $data['hot_runner_controller'] === '' ) {
            $data['hot_runner_controller'] = 'Not Required';
        }
        // The form labels the condition select "Tool condition" but posts mould_condition;
        // mirror it into tool_condition so both stay in sync for display/filtering.
        if ( in_array( 'tool_condition', $existing_cols, true ) && empty( $data['tool_condition'] ) && ! empty( $data['mould_condition'] ) ) {
            $data['tool_condition'] = $data['mould_condition'];
        }

        // Description = textarea (preserve line breaks)
        if ( in_array('part_description', $existing_cols, true) ) {
            $data['part_description'] = sanitize_textarea_field( wp_unslash( $_POST['part_description'] ?? '' ) );
        }
        // Numeric cavity spec
        if ( in_array('num_cavities_spec', $existing_cols, true) ) {
            $data['num_cavities_spec'] = ( isset($_POST['num_cavities_spec']) && $_POST['num_cavities_spec'] !== '' )
                ? absint($_POST['num_cavities_spec']) : 0;
        }
        // Material flag checkboxes (PP/ABS/PE map to existing filter columns)
        foreach ( array('tolerance_abs','tolerance_pp','tolerance_pe') as $cb ) {
            if ( in_array($cb, $existing_cols, true) ) $data[$cb] = isset($_POST[$cb]) ? 1 : 0;
        }
        // Multi-select materials[] -> JSON. These now come from the §1 grade picker
        // (hidden materials[] inputs); fall back to the material_grade CSV so the
        // buyer-filterable `materials` column is never left empty without the JS inputs.
        if ( in_array('materials', $existing_cols, true) ) {
            $mats = array();
            if ( ! empty($_POST['materials']) && is_array($_POST['materials']) ) {
                foreach ( $_POST['materials'] as $m ) {
                    $m = sanitize_text_field( wp_unslash( $m ) );
                    if ( $m !== '' ) $mats[] = $m;
                }
            }
            if ( ! $mats && ! empty($data['material_grade']) ) {
                foreach ( explode( ',', $data['material_grade'] ) as $g ) {
                    $g = trim( $g );
                    if ( $g !== '' ) $mats[] = $g;
                }
            }
            $data['materials'] = $mats ? wp_json_encode( array_values( array_unique($mats) ) ) : '';
        }
        // Keep the legacy single `material` column populated (cards/detail read it):
        // first selected polymer, else first material grade.
        if ( in_array('material', $existing_cols, true) && empty($data['material']) ) {
            $first_mat = '';
            if ( ! empty($_POST['materials']) && is_array($_POST['materials']) ) {
                $first_mat = sanitize_text_field( wp_unslash( (string) reset($_POST['materials']) ) );
            }
            if ( $first_mat === '' && ! empty($data['material_grade']) ) {
                $grade_parts = explode( ',', $data['material_grade'] );
                $first_mat   = trim( (string) reset($grade_parts) );
            }
            if ( $first_mat !== '' ) {
                $data['material'] = $first_mat;
            }
        }
        // Owner name fallback
        if ( in_array('owner_name', $existing_cols, true) && empty($data['owner_name']) ) {
            $data['owner_name'] = wp_get_current_user()->display_name;
        }
        // Dates (validate YYYY-MM-DD, else sensible defaults)
        $ih_valid_date = static function( $d ) {
            $d = sanitize_text_field( (string) $d );
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : '';
        };
        if ( in_array('listing_date', $existing_cols, true) ) {
            $data['listing_date'] = $ih_valid_date( $_POST['listing_date'] ?? '' ) ?: date('Y-m-d');
        }
        if ( in_array('expiry_date', $existing_cols, true) ) {
            $data['expiry_date'] = $ih_valid_date( $_POST['expiry_date'] ?? '' ) ?: date('Y-m-d', strtotime('+30 days'));
        }

        // 6) Safe image uploads — validate type + size, check each slot independently
        require_once ABSPATH.'wp-admin/includes/image.php';
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/media.php';
        $allowed_mimes = array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif' );
        $max_bytes     = 8 * 1024 * 1024; // 8 MB per image
        for ( $i = 1; $i <= 5; $i++ ) {
            $key = "image_{$i}";
            if ( ! in_array($key, $existing_cols, true) ) continue;
            if ( empty($_FILES[$key]['name']) || ! empty($_FILES[$key]['error']) ) continue;
            if ( (int) ( $_FILES[$key]['size'] ?? 0 ) > $max_bytes ) continue;
            $check = wp_check_filetype_and_ext( $_FILES[$key]['tmp_name'], $_FILES[$key]['name'], $allowed_mimes );
            if ( empty($check['type']) || strpos($check['type'], 'image/') !== 0 ) continue;
            $_FILES['ih_upload_img'] = $_FILES[$key];
            $aid = media_handle_upload( 'ih_upload_img', 0, array(), array( 'test_form' => false, 'mimes' => $allowed_mimes ) );
            unset( $_FILES['ih_upload_img'] );
            if ( ! is_wp_error($aid) ) {
                $url = wp_get_attachment_url( $aid );
                if ( $url ) $data[$key] = esc_url_raw( $url );
            }
        }

        // 7) Insert (wpdb::insert prepares every value)
        $ok      = $wpdb->insert( $table, $data );
        $tool_id = $ok ? (int) $wpdb->insert_id : 0;
        if ( ! $tool_id ) {
            wp_safe_redirect( add_query_arg( 'ih_error', rawurlencode('Could not save the tool. Please try again.'), $redirect_base ) );
            exit;
        }

        // Drafts stay private to the owner — no approval request, no admin notification.
        if ( ! $is_draft ) {
            if ( function_exists( 'ih_create_listing_approval_request' ) ) {
                ih_create_listing_approval_request( $user_id, $tool_id, 'tool' );
            }

            // 8) Notify admin
            if ( function_exists('ih_notify_admin_new_listing') ) {
                ih_notify_admin_new_listing( $user_id, $tool_id, 'tool', $data['title'] ?: 'New Tool' );
            }
            $u = wp_get_current_user();
            wp_mail(
                get_option('admin_email'),
                'New Tool Listing Pending Approval',
                "User {$u->display_name} ({$u->user_email}) submitted a new tool listing (ID: {$tool_id}) for approval.\n\nReview: " . admin_url('admin.php?page=ih-tools')
            );
        }

        wp_safe_redirect( admin_url('admin.php?page=ih-user-add-tool&' . ( $is_draft ? 'draft=1' : 'saved=1' )) );
        exit;
    }
});

/* ═══════════════════════════════════════
   DUPLICATE TOOL LISTING (owner self-service)
   Clones an ih_tools row owned by the current user into a fresh draft:
   new TL id/ref, listing_status=draft, cleared start/expiry dates, all
   spec + material columns copied. Returns the new draft's edit URL.
═══════════════════════════════════════ */
add_action( 'wp_ajax_ih_duplicate_tool', function () {
    // 1) Authentication
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Please sign in and try again.' ), 403 );
    }
    // 2) Nonce
    check_ajax_referer( 'ih_nonce', 'nonce' );

    // 3) Capability (ownership enforced below)
    $user_id = get_current_user_id();
    if ( ! current_user_can( 'read' ) ) {
        wp_send_json_error( array( 'message' => 'You do not have permission to duplicate listings.' ), 403 );
    }

    global $wpdb;
    $table   = $wpdb->prefix . 'ih_tools';
    $tool_id = absint( $_POST['tool_id'] ?? 0 );
    if ( ! $tool_id ) {
        wp_send_json_error( array( 'message' => 'Invalid listing reference.' ), 400 );
    }

    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $tool_id ), ARRAY_A );
    if ( ! $row ) {
        wp_send_json_error( array( 'message' => 'That listing no longer exists.' ), 404 );
    }

    // 4) Ownership check
    if ( (int) $row['owner_id'] !== $user_id ) {
        wp_send_json_error( array( 'message' => 'You can only duplicate your own listings.' ), 403 );
    }

    // 5) Build the clone — copy every spec/material column, reset identity + lifecycle.
    unset( $row['id'], $row['created_at'] );
    $row['owner_id']       = $user_id;                 // force ownership to the current user
    $row['available']      = 0;
    $row['listing_status'] = 'draft';                  // fresh draft, never auto-published
    $row['listing_date']   = null;                     // cleared — owner re-sets on edit
    $row['expiry_date']    = null;
    if ( ! empty( $row['title'] ) ) {
        $row['title'] = sanitize_text_field( $row['title'] . ' (Copy)' );
    }
    if ( isset( $row['owner_name'] ) ) {
        $row['owner_name'] = sanitize_text_field( $row['owner_name'] );
    }

    // Only write columns that actually exist (robust against schema drift).
    $existing_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" );
    $row           = array_intersect_key( $row, array_flip( $existing_cols ) );

    // 6) Insert (wpdb::insert prepares every value) — new auto-increment id => fresh TL ref.
    $ok     = $wpdb->insert( $table, $row );
    $new_id = $ok ? (int) $wpdb->insert_id : 0;
    if ( ! $new_id ) {
        wp_send_json_error( array( 'message' => 'Could not duplicate the listing. Please try again.' ), 500 );
    }

    if ( function_exists( 'ih_log_activity' ) ) {
        ih_log_activity( 'listing', 'Tool listing duplicated: #' . $tool_id . ' → #' . $new_id, array( 'tool_id' => $new_id, 'source_id' => $tool_id ) );
    }

    wp_send_json_success( array(
        'new_id'   => $new_id,
        'new_ref'  => 'TL-' . str_pad( (string) $new_id, 5, '0', STR_PAD_LEFT ),
        'edit_url' => admin_url( 'admin.php?page=ih-user-edit-tool&tool_id=' . $new_id . '&duplicated=1' ),
    ) );
} );

add_action('admin_init', function() {
 
    if ( isset($_POST['ih_user_machine_edit_submit']) ) {
        if ( ! is_user_logged_in() ) wp_die('Please login first.');
        if ( ! wp_verify_nonce($_POST['ih_user_nonce'] ?? '', 'ih_user_edit_machine') ) wp_die('Security check failed.');
 
        global $wpdb;
        $user_id    = get_current_user_id();
        $machine_id = intval($_POST['machine_id'] ?? 0);
 
        
       /* ── Owner check ── */
$owner = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT owner_id FROM {$wpdb->prefix}ih_machines WHERE id=%d", $machine_id
));

// owner_id 0 hai toh current user ko assign karo
if ( $owner === 0 ) {
    $wpdb->update(
        $wpdb->prefix . 'ih_machines',
        ['owner_id' => $user_id],
        ['id' => $machine_id]
    );
    $owner = $user_id;
}

if ( $owner !== $user_id ) wp_die('Unauthorized.');
 
        /* ── Fields update ──
         * Reuse the SAME sanitizer as the admin/add flow so the owner edit round-trips
         * fully: tolerance, materials (CSV column + legacy materials_* booleans), images
         * 1–5, cavities, toggles, certifications and dates all persist on save. */
        $existing_cols = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}ih_machines`" );
        $data          = ih_machine_form_data_from_post( $existing_cols );

        /* Persist ONLY the columns the owner edit form actually renders. Any column it does
         * NOT manage (opening_stroke, clamp_drive_type, toggle_clamp_type, material_grade,
         * brand, notes, owner_id, listing_status, …) is preserved untouched — this prevents
         * a no-op owner edit from blanking unrendered fields and dropping completeness (M1). */
        $editable = array(
            'title','year_manufacture','machine_type','identical_count',
            'clamping_force','shot_size','screw_diameter','max_injection_pressure',
            'tie_bar_spacing','max_mould_height','min_mould_height',
            'max_part_weight','max_part_dimensions','tolerance',
            'engineering_grade','recycled_materials',
            'batch_size','min_order_qty','max_monthly_output','avg_cycle_time',
            'operating_hours','utilization','location','automation_level',
            'robot_integration','multi_cavity','certifications','qc_tools',
            'tolerance_consistency','overmoulding','insert_moulding','iml',
            'gas_assisted','thin_wall','listing_date','expiry_date',
            'materials','cavities',
            'projected_area','cavity_pressure',
        );
        foreach ( array_keys( ih_machine_materials_map() ) as $mat_col ) {
            $editable[] = $mat_col;
        }
        $data = array_intersect_key( $data, array_flip( $editable ) );

        /* ── Image upload (only slots with a new file; existing images preserved) ── */
        $data = ih_machine_upload_images_from_post( $existing_cols, $data );

        /* ── DB update ── */
        if ( ! empty( $data ) ) {
            $wpdb->update(
                $wpdb->prefix . 'ih_machines',
                $data,
                ['id' => $machine_id, 'owner_id' => $user_id]
            );
        }

        if ( function_exists( 'ih_mark_listing_pending_review' ) ) {
            ih_mark_listing_pending_review( $user_id, $machine_id, 'machine' );
        }
 
        wp_redirect( admin_url('admin.php?page=ih-user-edit-machine&machine_id=' . $machine_id . '&saved=1') );
        exit;
    }
 
}, 10);

/* ═══════════════════════════════════════
   USER MESSAGE AJAX HANDLERS (single, ih_nonce)
═══════════════════════════════════════ */

/* User send message — uses ih_nonce */
add_action('wp_ajax_ih_user_send_message', function() {
    if ( ! is_user_logged_in() ) wp_send_json_error(['message'=>'Not logged in'], 403);
    check_ajax_referer('ih_nonce', 'nonce');

    global $wpdb;
    $user_id   = get_current_user_id();
    $message   = sanitize_textarea_field($_POST['message'] ?? '');
    $thread_id = intval($_POST['thread_id'] ?? 0);

    if ( ! $user_id || ! $message ) wp_send_json_error(['message'=>'Invalid data']);

    /* Thread not found? Create one */
    if ( $thread_id ) {
        /* Security: thread must belong to this user */
        $owner = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}ih_threads WHERE id=%d", $thread_id
        ));
        if ( $owner !== $user_id ) wp_send_json_error(['message'=>'Unauthorized'], 403);
    } else {
        $wpdb->insert( $wpdb->prefix.'ih_threads', [
            'user_id'      => $user_id,
            'listing_id'   => 0,
            'listing_type' => 'machine',
            'last_message' => $message,
            'last_time'    => current_time('mysql', true),
            'unread'       => 1,
        ]);
        $thread_id = (int) $wpdb->insert_id;
    }

    $insert = ih_chat_delivery_insert_defaults([
        'thread_id' => $thread_id,
        'from_me'   => 0,
        'message'   => $message,
        'sent_at'   => current_time('mysql', true),
    ]);
    $chat_cols = $wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}ih_chats");
    $insert = array_intersect_key($insert, array_flip($chat_cols));
    $wpdb->insert( $wpdb->prefix.'ih_chats', $insert );
    $msg_id = (int) $wpdb->insert_id;

    $wpdb->update( $wpdb->prefix.'ih_threads', [
        'last_message' => $message,
        'last_time'    => current_time('mysql', true),
        'unread'       => 1,
    ], ['id' => $thread_id]);

    wp_send_json_success([
        'id'              => $msg_id,
        'thread_id'       => $thread_id,
        'message'         => $message,
        'time'            => date_i18n('g:i A'),
        'delivery_status' => 'sent',
    ]);
});

/* User get new messages from admin — uses ih_nonce */
add_action('wp_ajax_ih_user_get_messages', function() {
    if ( ! is_user_logged_in() ) wp_send_json_error(['message'=>'Not logged in'], 403);
    check_ajax_referer('ih_nonce', 'nonce');

    global $wpdb;
    $user_id   = get_current_user_id();
    $thread_id = intval($_GET['thread_id'] ?? 0);
    $after_id  = intval($_GET['after_id']  ?? 0);

    if ( ! $thread_id ) wp_send_json_success(['messages'=>[]]);

    /* Security: thread must belong to this user */
    $owner = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT user_id FROM {$wpdb->prefix}ih_threads WHERE id=%d", $thread_id
    ));
    if ( $owner !== $user_id ) wp_send_json_error(['message'=>'Unauthorized'], 403);

    $chat_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}ih_chats" );
    $select    = 'id, from_me, message, sent_at';
    foreach ( [ 'attachment_url', 'attachment_type', 'attachment_name', 'attachment_size' ] as $col ) {
        if ( in_array( $col, $chat_cols, true ) ) {
            $select .= ', ' . $col;
        }
    }
    if ( in_array( 'delivery_status', $chat_cols, true ) ) {
        $select .= ', delivery_status, read_at';
    }
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT {$select} FROM {$wpdb->prefix}ih_chats WHERE thread_id=%d AND id>%d ORDER BY id ASC",
        $thread_id, $after_id
    ), ARRAY_A) ?: [];

    if ( function_exists( 'ih_chat_mark_sender_messages_delivered' ) ) {
        ih_chat_mark_sender_messages_delivered( $thread_id, 1 );
    }
    if ( function_exists( 'ih_chat_mark_sender_messages_read' ) ) {
        ih_chat_mark_sender_messages_read( $thread_id, 1 );
    }

    $messages = [];
    foreach ( $rows as $row ) {
        $msg = [
            'id'      => (int) $row['id'],
            'from_me' => (int) $row['from_me'],
            'message' => $row['message'],
            'text'    => $row['message'],
            'type'    => function_exists( 'ih_chat_detect_message_type' ) ? ih_chat_detect_message_type( $row['message'] ) : 'chat',
            'time'    => get_date_from_gmt( $row['sent_at'], 'g:i A' ),
        ];
        foreach ( [ 'attachment_url', 'attachment_type', 'attachment_name', 'attachment_size' ] as $col ) {
            if ( isset( $row[ $col ] ) && $row[ $col ] !== '' && $row[ $col ] !== null ) {
                $msg[ $col ] = $col === 'attachment_size' ? (int) $row[ $col ] : $row[ $col ];
            }
        }
        if ( function_exists( 'ih_chat_outbound_delivery_status' ) ) {
            $out_status = ih_chat_outbound_delivery_status( $row, false );
            if ( $out_status ) {
                $msg['delivery_status'] = $out_status;
            }
        }
        if ( ! empty( $row['read_at'] ) ) {
            $msg['read_at'] = $row['read_at'];
        }
        $messages[] = $msg;
    }

    if ( function_exists( 'ih_chat_attach_reactions' ) ) {
        ih_chat_attach_reactions( $messages, $user_id );
    }

    $typing_uid = get_transient( 'ih_typing_' . $thread_id );
    $typing     = $typing_uid && (int) $typing_uid !== $user_id;

    wp_send_json_success( [
        'messages'          => $messages,
        'typing'            => $typing,
        'outbound_statuses' => function_exists( 'ih_chat_outbound_status_map' ) ? ih_chat_outbound_status_map( $thread_id, false ) : array(),
    ] );
});

/* User delete own listing */
add_action('wp_ajax_ih_user_delete_listing', function() {
    if ( ! is_user_logged_in() ) wp_send_json_error(['message'=>'Not logged in'], 403);
    check_ajax_referer('ih_nonce', 'nonce');
    global $wpdb;
    $user_id = get_current_user_id();
    $id      = intval($_POST['id'] ?? 0);
    $type    = sanitize_key($_POST['type'] ?? 'machine');
    if ( ! $id ) wp_send_json_error(['message'=>'Invalid ID']);
    $tbl = ($type === 'tool') ? $wpdb->prefix.'ih_tools' : $wpdb->prefix.'ih_machines';
    $owner = (int) $wpdb->get_var( $wpdb->prepare("SELECT owner_id FROM {$tbl} WHERE id=%d", $id));
    if ( $owner !== $user_id ) wp_send_json_error(['message'=>'Unauthorized'], 403);
    $wpdb->delete($tbl, ['id'=>$id]);
    wp_send_json_success(['deleted'=>$id]);
});

/* ── User file upload ── */
add_action('wp_ajax_ih_user_upload_file', function() {
    if ( ! is_user_logged_in() ) wp_send_json_error(['message'=>'Not logged in'], 403);
    check_ajax_referer('ih_nonce', 'nonce');
    global $wpdb;

    $user_id   = get_current_user_id();
    $thread_id = intval($_POST['thread_id'] ?? 0);
    if ( ! $thread_id ) wp_send_json_error(['message'=>'No thread']);

    $owner = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT user_id FROM {$wpdb->prefix}ih_threads WHERE id=%d", $thread_id
    ));
    if ( $owner !== $user_id ) wp_send_json_error(['message'=>'Unauthorized'], 403);
    if ( ! isset($_FILES['file']) ) {
        wp_send_json_error(['message'=>'No file field received.']);
    }
    $php_err = $_FILES['file']['error'];
    if ( $php_err !== UPLOAD_ERR_OK ) {
        $err_map = [
            UPLOAD_ERR_INI_SIZE   => 'File too large. Server limit: '.ini_get('upload_max_filesize').'. Please use a smaller file.',
            UPLOAD_ERR_FORM_SIZE  => 'File too large (form limit).',
            UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was sent.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server.',
        ];
        wp_send_json_error(['message' => isset($err_map[$php_err]) ? $err_map[$php_err] : 'PHP upload error: '.$php_err]);
    }
    if ( empty($_FILES['file']['tmp_name']) ) {
        wp_send_json_error(['message'=>'File missing on server. Limits: post_max_size='.ini_get('post_max_size').', upload_max_filesize='.ini_get('upload_max_filesize')]);
    }

    $result = ih_handle_chat_upload('file');
    if ( is_wp_error($result) ) wp_send_json_error(['message' => $result->get_error_message(), 'code' => $result->get_error_code()]);

    $msg = $result['is_img']
        ? "🖼 {$result['name']} ({$result['url']})"
        : "📎 {$result['name']} ({$result['url']})";

    $wpdb->insert($wpdb->prefix.'ih_chats', [
        'thread_id' => $thread_id,
        'from_me'   => 0,
        'message'   => $msg,
        'sent_at'   => current_time('mysql', true),
    ]);
    $msg_id = (int) $wpdb->insert_id;

    $wpdb->update($wpdb->prefix.'ih_threads', [
        'last_message' => $result['name'],
        'last_time'    => current_time('mysql', true),
        'unread'       => 1,
    ], ['id' => $thread_id]);

    wp_send_json_success([
        'id'       => $msg_id,
        'url'      => $result['url'],
        'filename' => $result['name'],
        'is_image' => $result['is_img'],
        'time'     => $result['time'],
    ]);
});

/* ── User delete own chat thread ── */
add_action('wp_ajax_ih_user_delete_chat', function() {
    if (!is_user_logged_in()) wp_send_json_error(['message'=>'Not logged in'], 403);
    check_ajax_referer('ih_nonce', 'nonce');
    global $wpdb;
    $user_id   = get_current_user_id();
    $thread_id = intval($_POST['thread_id'] ?? 0);
    if (!$thread_id) wp_send_json_error(['message'=>'Invalid thread']);
    /* Security: must own the thread */
    $owner = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->prefix}ih_threads WHERE id=%d", $thread_id
    ));
    if ($owner !== $user_id) wp_send_json_error(['message'=>'Unauthorized'], 403);
    $wpdb->delete($wpdb->prefix.'ih_chats',   ['thread_id' => $thread_id]);
    $wpdb->delete($wpdb->prefix.'ih_threads', ['id'        => $thread_id]);
    wp_send_json_success(['deleted' => $thread_id]);
});

/* Legacy alias — forwards to ih_toggle_message_reaction */
add_action('wp_ajax_ih_user_react_message', function() {
    if (!is_user_logged_in()) wp_send_json_error(['message'=>'Not logged in'], 403);
    check_ajax_referer('ih_nonce', 'nonce');
    $message_id = intval($_POST['msg_id'] ?? $_POST['message_id'] ?? 0);
    $emoji      = sanitize_text_field(wp_unslash($_POST['emoji'] ?? $_POST['reaction'] ?? ''));
    $result     = ih_chat_toggle_message_reaction($message_id, get_current_user_id(), $emoji);
    if (is_wp_error($result)) {
        $status = (int) ($result->get_error_data()['status'] ?? 400);
        wp_send_json_error(['message' => $result->get_error_message()], $status);
    }
    wp_send_json_success($result);
});

/**
 * Render message content — images inline, files as links, plain text as text.
 * Handles legacy "📎 File: name (url)" and new "🖼 name (url)" / "📎 name (url)" formats.
 */
function ih_get_file_icon( $ext ) {
    $icons = ['pdf'=>'📄','doc'=>'📝','docx'=>'📝','xls'=>'📊','xlsx'=>'📊',
              'ppt'=>'📋','pptx'=>'📋','zip'=>'🗜️','rar'=>'🗜️','txt'=>'📃',
              'csv'=>'📊','mp4'=>'🎬','mp3'=>'🎵','mov'=>'🎬'];
    return $icons[$ext] ?? '📎';
}

function ih_linkify( $text ) {
    return preg_replace(
        '/(https?:\/\/[^\s<>"]+)/i',
        '<a href="$1" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;word-break:break-all;">$1</a>',
        esc_html($text)
    );
}

function ih_render_message( $msg ) {
    $img_exts = ['jpg','jpeg','png','gif','webp'];

    /* 🖼 Image format */
    if ( preg_match( '/🖼\s+(.+?)\s+\((https?:\/\/[^\)]+)\)/', $msg, $m ) ) {
        $url  = esc_url($m[2]); $name = esc_html($m[1]);
        return '<a href="'.$url.'" target="_blank" rel="noopener" style="display:block;">'
             . '<img src="'.$url.'" alt="'.$name.'" style="max-width:240px;max-height:200px;border-radius:10px;display:block;cursor:pointer;">'
             . '</a><div style="font-size:11px;opacity:.65;margin-top:4px;">'.$name.'</div>';
    }

    /* 📎 File format */
    if ( preg_match( '/📎\s+(.+?)\s+\((https?:\/\/[^\)]+)\)/', $msg, $m ) ) {
        $url  = esc_url($m[2]); $name = esc_html($m[1]);
        $ext  = strtolower(pathinfo($m[1], PATHINFO_EXTENSION));
        if ( in_array($ext, $img_exts) ) {
            return '<a href="'.$url.'" target="_blank" style="display:block;">'
                 . '<img src="'.$url.'" alt="'.$name.'" style="max-width:240px;max-height:200px;border-radius:10px;display:block;cursor:pointer;">'
                 . '</a><div style="font-size:11px;opacity:.65;margin-top:4px;">'.$name.'</div>';
        }
        $icon = ih_get_file_icon($ext);
        return '<a href="'.$url.'" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.15);border-radius:10px;padding:10px 12px;text-decoration:none;color:inherit;max-width:240px;">'
             . '<span style="font-size:28px;flex-shrink:0;">'.$icon.'</span>'
             . '<div style="min-width:0;"><div style="font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'.$name.'</div>'
             . '<div style="font-size:10px;opacity:.65;text-transform:uppercase;">'.strtoupper($ext).' File</div></div></a>';
    }

    /* Legacy: 📎 File: name (url) */
    if ( preg_match( '/📎 File:\s+(.+?)\s+\((https?:\/\/[^\)]+)\)/', $msg, $m ) ) {
        $url  = esc_url($m[2]); $name = esc_html($m[1]);
        $ext  = strtolower(pathinfo($m[1], PATHINFO_EXTENSION));
        if ( in_array($ext, $img_exts) ) {
            return '<a href="'.$url.'" target="_blank" style="display:block;">'
                 . '<img src="'.$url.'" alt="'.$name.'" style="max-width:240px;max-height:200px;border-radius:10px;display:block;cursor:pointer;">'
                 . '</a><div style="font-size:11px;opacity:.65;margin-top:4px;">'.$name.'</div>';
        }
        $icon = ih_get_file_icon($ext);
        return '<a href="'.$url.'" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.15);border-radius:10px;padding:10px 12px;text-decoration:none;color:inherit;max-width:240px;">'
             . '<span style="font-size:28px;flex-shrink:0;">'.$icon.'</span>'
             . '<div style="min-width:0;"><div style="font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'.$name.'</div>'
             . '<div style="font-size:10px;opacity:.65;text-transform:uppercase;">'.strtoupper($ext).' File</div></div></a>';
    }

    /* Plain text — auto-link URLs, preserve newlines */
    return nl2br( ih_linkify($msg) );
}
/* ── Admin menu mein Settings page add karo ── */
add_action('admin_menu', function() {
    add_submenu_page(
        'ih-dashboard',
        'IH Settings',
        'Settings',
        'manage_options',
        'ih-settings',
        'ih_page_settings'
    );
}, 20);
 
/* ── Settings register karo ── */
add_action('admin_init', function() {
    register_setting('ih_settings_group', 'ih_whatsapp_number',        ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('ih_settings_group', 'ih_admin_notify_email',     ['sanitize_callback' => 'sanitize_email']);
    register_setting('ih_settings_group', 'ih_ws_url',                 ['sanitize_callback' => 'esc_url_raw']);
    register_setting('ih_settings_group', 'ih_default_machine_image',  ['sanitize_callback' => 'esc_url_raw']);
    register_setting('ih_settings_group', 'ih_default_tool_image',     ['sanitize_callback' => 'esc_url_raw']);
});

/* ── Settings page render — full IH shell page ── */
function ih_page_settings() {
    include IH_DIR . 'pages/settings.php';
}
 
/* ── WordPress Media scripts load karo settings page pe ── */
add_action('admin_enqueue_scripts', function($hook) {
    if ( $hook !== 'insight-hub_page_ih-settings' ) return;
    wp_enqueue_media();
});
add_action( 'wp_ajax_ih_get_listing_context', 'ih_ajax_get_listing_context' );

function ih_ajax_get_listing_context() {

    if ( ! check_ajax_referer('ih_nonce', 'nonce', false) ) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    global $wpdb;

    $user_id      = intval($_POST['user_id'] ?? 0);
    $listing_id   = intval($_POST['listing_id'] ?? 0);
    $listing_type = sanitize_key($_POST['listing_type'] ?? '');

    if ( ! $user_id ) {
        wp_send_json_error(['message' => 'No user']);
    }

    $all_listings = [];

    /* =========================
       MACHINES
    ========================= */

    $machines = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, title, image_1, machine_type, location, available
             FROM {$wpdb->prefix}ih_machines
             WHERE owner_id = %d
             ORDER BY id DESC",
            $user_id
        ),
        ARRAY_A
    ) ?: [];

    foreach ( $machines as $m ) {

        $lid = (int) $m['id'];

        $req = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, status
                 FROM {$wpdb->prefix}ih_requests
                 WHERE user_id = %d
                 AND listing_id = %d
                 AND listing_type = 'machine'
                 ORDER BY id DESC
                 LIMIT 1",
                $user_id,
                $lid
            ),
            ARRAY_A
        );

        $all_listings[] = [
            'listing_id'   => $lid,
            'listing_type' => 'machine',
            'title'        => $m['title'] ?: 'Machine',
            'tag'          => $m['machine_type'] ?: 'Machine',
            'sub'          => $m['location'] ?: '—',
            'img'          => $m['image_1']
                ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=120&q=80',
            'ref'          => 'MCH-' . str_pad($lid, 5, '0', STR_PAD_LEFT),
            'status'       => $req['status'] ?? 'Pending',
            'req_id'       => $req ? intval($req['id']) : 0,

            // ✅ MACHINE DETAIL URL
            'detail_url'   => admin_url(
                'admin.php?page=ih-machine-detail&machine_id=' . $lid
            ),
        ];
    }

    /* =========================
       TOOLS
    ========================= */

    $tools = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, title, image_1, mould_type, location, available
             FROM {$wpdb->prefix}ih_tools
             WHERE owner_id = %d
             ORDER BY id DESC",
            $user_id
        ),
        ARRAY_A
    ) ?: [];

    foreach ( $tools as $t ) {

        $lid = (int) $t['id'];

        $req = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, status
                 FROM {$wpdb->prefix}ih_requests
                 WHERE user_id = %d
                 AND listing_id = %d
                 AND listing_type = 'tool'
                 ORDER BY id DESC
                 LIMIT 1",
                $user_id,
                $lid
            ),
            ARRAY_A
        );

        $all_listings[] = [
            'listing_id'   => $lid,
            'listing_type' => 'tool',
            'title'        => $t['title'] ?: 'Tool',
            'tag'          => $t['mould_type'] ?: 'Tool',
            'sub'          => $t['location'] ?: '—',
            'img'          => $t['image_1']
                ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=120&q=80',
            'ref'          => 'TL-' . str_pad($lid, 5, '0', STR_PAD_LEFT),
            'status'       => $req['status'] ?? 'Pending',
            'req_id'       => $req ? intval($req['id']) : 0,

            // ✅ TOOL DETAIL URL
            'detail_url'   => admin_url(
                'admin.php?page=ih-tool-detail&tool_id=' . $lid
            ),
        ];
    }

    /* =========================
       FALLBACK FROM MESSAGES
    ========================= */

    if ( empty($all_listings) ) {

        $msg_tbl = $wpdb->get_var(
            "SHOW TABLES LIKE '{$wpdb->prefix}ih_messages'"
        )
            ? $wpdb->prefix . 'ih_messages'
            : $wpdb->prefix . 'ih_chats';

        $thread = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id
                 FROM {$wpdb->prefix}ih_threads
                 WHERE user_id = %d
                 LIMIT 1",
                $user_id
            )
        );

        if ( $thread ) {

            $cl  = $wpdb->get_col("SHOW COLUMNS FROM {$msg_tbl}");
            $col = in_array('message', $cl, true) ? 'message' : 'text';

            $msgs = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT {$col}
                     FROM {$msg_tbl}
                     WHERE thread_id = %d
                     ORDER BY id DESC
                     LIMIT 50",
                    $thread
                )
            );

            $found = [];

            foreach ( $msgs as $mt ) {

                /* MACHINE */

                if (
                    preg_match('/\bMCH-0*(\d+)\b/', $mt, $mx)
                    && ! isset($found['m' . $mx[1]])
                ) {

                    $pid = (int) $mx[1];
                    $found['m' . $pid] = 1;

                    $fb = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT *
                             FROM {$wpdb->prefix}ih_machines
                             WHERE id = %d",
                            $pid
                        ),
                        ARRAY_A
                    );

                    if ( $fb ) {

                        $req = $wpdb->get_row(
                            $wpdb->prepare(
                                "SELECT id, status
                                 FROM {$wpdb->prefix}ih_requests
                                 WHERE user_id = %d
                                 AND listing_id = %d
                                 AND listing_type = 'machine'
                                 LIMIT 1",
                                $user_id,
                                $pid
                            ),
                            ARRAY_A
                        );

                        $all_listings[] = [
                            'listing_id'   => $pid,
                            'listing_type' => 'machine',
                            'title'        => $fb['title'] ?: 'Machine',
                            'tag'          => $fb['machine_type'] ?? 'Machine',
                            'sub'          => $fb['location'] ?? '—',
                            'img'          => $fb['image_1'] ?? '',
                            'ref'          => 'MCH-' . str_pad($pid, 5, '0', STR_PAD_LEFT),
                            'status'       => $req['status'] ?? 'Pending',
                            'req_id'       => $req ? intval($req['id']) : 0,

                            // ✅ MACHINE DETAIL URL
                            'detail_url'   => admin_url(
                                'admin.php?page=ih-machine-detail&machine_id=' . $pid
                            ),
                        ];
                    }
                }

                /* TOOL */

                if (
                    preg_match('/\bTL-0*(\d+)\b/', $mt, $mx)
                    && ! isset($found['t' . $mx[1]])
                ) {

                    $pid = (int) $mx[1];
                    $found['t' . $pid] = 1;

                    $fb = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT *
                             FROM {$wpdb->prefix}ih_tools
                             WHERE id = %d",
                            $pid
                        ),
                        ARRAY_A
                    );

                    if ( $fb ) {

                        $req = $wpdb->get_row(
                            $wpdb->prepare(
                                "SELECT id, status
                                 FROM {$wpdb->prefix}ih_requests
                                 WHERE user_id = %d
                                 AND listing_id = %d
                                 AND listing_type = 'tool'
                                 LIMIT 1",
                                $user_id,
                                $pid
                            ),
                            ARRAY_A
                        );

                        $all_listings[] = [
                            'listing_id'   => $pid,
                            'listing_type' => 'tool',
                            'title'        => $fb['title'] ?: 'Tool',
                            'tag'          => $fb['mould_type'] ?? 'Tool',
                            'sub'          => $fb['location'] ?? '—',
                            'img'          => $fb['image_1'] ?? '',
                            'ref'          => 'TL-' . str_pad($pid, 5, '0', STR_PAD_LEFT),
                            'status'       => $req['status'] ?? 'Pending',
                            'req_id'       => $req ? intval($req['id']) : 0,

                            // ✅ TOOL DETAIL URL
                            'detail_url'   => admin_url(
                                'admin.php?page=ih-tool-detail&tool_id=' . $pid
                            ),
                        ];
                    }
                }
            }
        }
    }

    /* =========================
       NO LISTINGS
    ========================= */

    if ( empty($all_listings) ) {

        wp_send_json_success([
            'found'    => false,
            'listings' => [],
            'active'   => null,
        ]);
    }

    /* =========================
       ACTIVE LISTING
    ========================= */

    $active = $all_listings[0];

    if ( $listing_id && $listing_type ) {

        foreach ( $all_listings as $l ) {

            if (
                $l['listing_id'] === $listing_id
                && $l['listing_type'] === $listing_type
            ) {
                $active = $l;
                break;
            }
        }
    }

    wp_send_json_success([
        'found'    => true,
        'listings' => $all_listings,
        'active'   => $active,
    ]);
}
