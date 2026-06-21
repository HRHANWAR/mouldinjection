<?php
/**
 * Injection Moulding Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// =============================================
// THEME SETUP
// =============================================
function im_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );
    register_nav_menus( [
        'primary'  => __( 'Primary Navigation', 'injection-moulding' ),
        'footer'   => __( 'Footer Navigation', 'injection-moulding' ),
        'services' => __( 'Services Dropdown', 'injection-moulding' ),
    ] );
    add_theme_support( 'custom-logo', [ 'height' => 44, 'width' => 44, 'flex-height' => true, 'flex-width' => true ] );
    if ( ! isset( $content_width ) ) $content_width = 1280;
}
add_action( 'after_setup_theme', 'im_theme_setup' );


// =============================================
// ENQUEUE SCRIPTS & STYLES
// =============================================
function im_enqueue_assets() {
    wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap', [], null );
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [], '4.7.0' );
    wp_enqueue_style( 'injection-moulding-style', get_stylesheet_uri(), [ 'google-fonts', 'font-awesome' ], wp_get_theme()->get( 'Version' ) );
    wp_enqueue_style( 'im-custom', get_template_directory_uri() . '/assets/css/custom.css', [], '1.0.0' );
    wp_enqueue_style( 'im-responsive', get_template_directory_uri() . '/assets/css/responsive.css', [ 'injection-moulding-style', 'im-custom' ], '1.0.3' );
    wp_enqueue_script( 'im-main-js', get_template_directory_uri() . '/assets/js/main.js', [], wp_get_theme()->get( 'Version' ), true );
    wp_localize_script( 'im-main-js', 'imDashboard', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonces'  => [
            'machine' => wp_create_nonce( 'machine_action' ),
            'user'    => wp_create_nonce( 'user_action' ),
            'message' => wp_create_nonce( 'message_action' ),
            'ih'      => wp_create_nonce( 'ih_nonce' ),
        ],
    ] );
    wp_localize_script( 'im-main-js', 'ihAjax', [
        'url'   => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'ih_nonce' ),
    ] );
}
add_action( 'wp_enqueue_scripts', 'im_enqueue_assets' );

/**
 * Public /machines BROWSE redesign assets ("Find injection moulding capacity").
 * Figma: VfzCieeZ8ebjwm6vPiGajl node 419:2154. Scoped to the page-machines template.
 */
function im_enqueue_machines_listing_assets() {
    if ( ! is_page_template( 'page-machines.php' ) ) {
        return;
    }

    $deps = [];

    // Reuse the plugin's Figma design tokens when the plugin is active.
    if ( defined( 'IH_URL' ) && defined( 'IH_VERSION' ) ) {
        wp_enqueue_style( 'ih-figma-tokens', IH_URL . 'css/ih-figma-tokens.css', [], IH_VERSION );
        $deps[] = 'ih-figma-tokens';
    }

    wp_enqueue_style(
        'google-fonts-ibm-plex-mono',
        'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap',
        [],
        null
    );
    $deps[] = 'google-fonts-ibm-plex-mono';

    $browse_css  = get_template_directory_uri() . '/assets/css/im-machines-browse.css';
    $browse_path = get_template_directory() . '/assets/css/im-machines-browse.css';
    $browse_ver  = file_exists( $browse_path ) ? (string) filemtime( $browse_path ) : '2.0.0';

    wp_enqueue_style( 'im-machines-browse', $browse_css, $deps, $browse_ver );
}
add_action( 'wp_enqueue_scripts', 'im_enqueue_machines_listing_assets', 20 );

/**
 * Public Machine DETAIL assets (page-machine-detail.php).
 * Figma: VfzCieeZ8ebjwm6vPiGajl page 4:2 (Machine Detail Desktop/Mobile + Access tiers).
 * Scoped to the detail template; reuses the plugin's Figma tokens. JS is localized
 * with IMD_DATA (listing specs + the viewer's saved job profile) for match/fit.
 */
function im_enqueue_machine_detail_assets() {
	if ( ! is_page_template( 'page-machine-detail.php' ) ) {
		return;
	}

	$deps = array();
	if ( defined( 'IH_URL' ) && defined( 'IH_VERSION' ) ) {
		wp_enqueue_style( 'ih-figma-tokens', IH_URL . 'css/ih-figma-tokens.css', array(), IH_VERSION );
		$deps[] = 'ih-figma-tokens';
	}
	wp_enqueue_style(
		'google-fonts-ibm-plex-mono',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap',
		array(),
		null
	);
	$deps[] = 'google-fonts-ibm-plex-mono';

	$css_path = get_template_directory() . '/assets/css/im-machine-detail.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1.0.0';
	wp_enqueue_style( 'im-machine-detail', get_template_directory_uri() . '/assets/css/im-machine-detail.css', $deps, $css_ver );

	/* ── Chart.js + chartjs-plugin-dragdata, bundled locally (no CDN) ──────────
	 * Server-ready vendor copies in /assets/js/vendor; filemtime cache-busting.
	 * dragdata auto-registers against the global Chart on load; im-listing-detail
	 * depends on both so it always boots after Chart.js is ready. */
	$tpl_uri   = get_template_directory_uri();
	$tpl_dir   = get_template_directory();
	$chart_path = $tpl_dir . '/assets/js/vendor/chart.umd.min.js';
	$drag_path  = $tpl_dir . '/assets/js/vendor/chartjs-plugin-dragdata.min.js';
	$chart_ver  = file_exists( $chart_path ) ? (string) filemtime( $chart_path ) : '4.4.4';
	$drag_ver   = file_exists( $drag_path ) ? (string) filemtime( $drag_path ) : '2.2.5';
	wp_enqueue_script( 'chartjs', $tpl_uri . '/assets/js/vendor/chart.umd.min.js', array(), $chart_ver, true );
	wp_enqueue_script( 'chartjs-dragdata', $tpl_uri . '/assets/js/vendor/chartjs-plugin-dragdata.min.js', array( 'chartjs' ), $drag_ver, true );

	$js_path = get_template_directory() . '/assets/js/im-listing-detail.js';
	$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1.0.0';
	wp_enqueue_script( 'im-listing-detail', $tpl_uri . '/assets/js/im-listing-detail.js', array( 'chartjs', 'chartjs-dragdata' ), $js_ver, true );

	wp_localize_script( 'im-listing-detail', 'IMD_DATA', im_build_machine_detail_payload() );

	/* Field info-popovers (plugin handles, pre-registered). Attribute-driven via
	 * data-tip on the spec labels; additive, no re-registration. */
	if ( wp_style_is( 'ih-infotips', 'registered' ) ) {
		wp_enqueue_style( 'ih-infotips' );
	}
	if ( wp_script_is( 'ih-infotips', 'registered' ) ) {
		wp_enqueue_script( 'ih-infotips' );
	}

	im_enqueue_request_messaging_assets();
}
add_action( 'wp_enqueue_scripts', 'im_enqueue_machine_detail_assets', 20 );

/**
 * Enqueue the requester ↔ owner listing-messaging panel assets on the public
 * detail pages. The thread panel only renders server-side for an APPROVED
 * relationship, so we load the (small, self-contained) styles + polling JS for
 * any logged-in viewer. Assets live in the plugin (IH_URL); IH_RMSG carries the
 * AJAX endpoint + a fresh ih_nonce.
 */
function im_enqueue_request_messaging_assets() {
	if ( ! is_user_logged_in() || ! defined( 'IH_URL' ) || ! defined( 'IH_VERSION' ) ) {
		return;
	}
	wp_enqueue_style( 'ih-request-messaging', IH_URL . 'css/ih-request-messaging.css', array(), IH_VERSION );
	wp_enqueue_script( 'ih-request-messaging', IH_URL . 'js/ih-request-messaging.js', array(), IH_VERSION, true );
	wp_localize_script( 'ih-request-messaging', 'IH_RMSG', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'ih_nonce' ),
		'meId'    => (int) get_current_user_id(),
		'isAdmin' => current_user_can( 'manage_options' ),
		'pollMs'  => 12000,
	) );
}

/**
 * Public /tools BROWSE assets (page-tools.php). Reuses the Machine browse design
 * system (im-machines-browse.css, scoped .mh-browse) and layers the tool-specific
 * variant (im-tools-browse.css, scoped .mh-browse--tools) on top. Client-side
 * filter/sort + compare bar are inline in the template; only CSS is enqueued here.
 */
function im_enqueue_tools_listing_assets() {
	if ( ! is_page_template( 'page-tools.php' ) ) {
		return;
	}

	$deps = array();
	if ( defined( 'IH_URL' ) && defined( 'IH_VERSION' ) ) {
		wp_enqueue_style( 'ih-figma-tokens', IH_URL . 'css/ih-figma-tokens.css', array(), IH_VERSION );
		$deps[] = 'ih-figma-tokens';
	}
	wp_enqueue_style(
		'google-fonts-ibm-plex-mono',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap',
		array(),
		null
	);
	$deps[] = 'google-fonts-ibm-plex-mono';

	$browse_path = get_template_directory() . '/assets/css/im-machines-browse.css';
	$browse_ver  = file_exists( $browse_path ) ? (string) filemtime( $browse_path ) : '2.0.0';
	wp_enqueue_style( 'im-machines-browse', get_template_directory_uri() . '/assets/css/im-machines-browse.css', $deps, $browse_ver );

	$tools_path = get_template_directory() . '/assets/css/im-tools-browse.css';
	$tools_ver  = file_exists( $tools_path ) ? (string) filemtime( $tools_path ) : '1.0.0';
	wp_enqueue_style( 'im-tools-browse', get_template_directory_uri() . '/assets/css/im-tools-browse.css', array( 'im-machines-browse' ), $tools_ver );
}
add_action( 'wp_enqueue_scripts', 'im_enqueue_tools_listing_assets', 20 );

/**
 * Public Tool DETAIL assets (page-tool-detail.php). Reuses the Machine detail
 * stylesheet (im-machine-detail.css, scoped .imd-*) — tools share the spec-sheet
 * chrome — but ships a lean no-Chart.js script (im-tool-detail.js) localized as
 * TLD_DATA. Reuses the tool-capable theme AJAX handlers (request / owner-action /
 * wishlist / delete-tool).
 */
function im_enqueue_tool_detail_assets() {
	if ( ! is_page_template( 'page-tool-detail.php' ) ) {
		return;
	}

	$deps = array();
	if ( defined( 'IH_URL' ) && defined( 'IH_VERSION' ) ) {
		wp_enqueue_style( 'ih-figma-tokens', IH_URL . 'css/ih-figma-tokens.css', array(), IH_VERSION );
		$deps[] = 'ih-figma-tokens';
	}
	wp_enqueue_style(
		'google-fonts-ibm-plex-mono',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap',
		array(),
		null
	);
	$deps[] = 'google-fonts-ibm-plex-mono';

	$css_path = get_template_directory() . '/assets/css/im-machine-detail.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1.0.0';
	wp_enqueue_style( 'im-machine-detail', get_template_directory_uri() . '/assets/css/im-machine-detail.css', $deps, $css_ver );

	/* Tool-detail technical re-skin — scoped to .imd-page--tools, layered on the
	   shared machine-detail chrome (no leak to the Machine detail page). */
	$tld_css_path = get_template_directory() . '/assets/css/im-tool-detail.css';
	$tld_css_ver  = file_exists( $tld_css_path ) ? (string) filemtime( $tld_css_path ) : '1.0.0';
	wp_enqueue_style( 'im-tool-detail', get_template_directory_uri() . '/assets/css/im-tool-detail.css', array( 'im-machine-detail' ), $tld_css_ver );

	$tpl_uri = get_template_directory_uri();
	$js_path = get_template_directory() . '/assets/js/im-tool-detail.js';
	$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1.0.0';
	wp_enqueue_script( 'im-tool-detail', $tpl_uri . '/assets/js/im-tool-detail.js', array(), $js_ver, true );

	$tool_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	wp_localize_script( 'im-tool-detail', 'TLD_DATA', array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'ih_nonce' ),
		'loggedIn'  => is_user_logged_in(),
		'loginUrl'  => function_exists( 'im_auth_url' )
			? add_query_arg( 'redirect_to', rawurlencode( home_url( '/tool/?id=' . $tool_id ) ), im_auth_url( 'login' ) )
			: home_url( '/register/?tab=login&redirect_to=' . rawurlencode( home_url( '/tool/?id=' . $tool_id ) ) ),
		'listingId' => $tool_id,
		'browseUrl' => home_url( '/tools/' ),
		/* Owner self-delete redirect target (mirrors the machine detail payload). */
		'dashboardUrl' => function_exists( 'im_get_dashboard_url' ) ? im_get_dashboard_url() : admin_url( 'admin.php?page=ih-user-dashboard' ),
	) );

	/* Cost & order calculator client (plugin) — binds to [data-ih-cost-calc] on
	 * this page, prices off the REST resin-pricing backend and adds the platform
	 * service charge + transaction fee → grand total. The helper enqueues the
	 * script handle and injects the IH_PRICING_API / IH_NONCE / fee-pct globals. */
	if ( function_exists( 'ih_enqueue_material_pricing_calculator' ) ) {
		ih_enqueue_material_pricing_calculator();
	}
	/* Field info-popovers (plugin handles, pre-registered). The price-badge
	 * styles (.ih-price-badge) live in this stylesheet too. */
	if ( wp_style_is( 'ih-infotips', 'registered' ) ) {
		wp_enqueue_style( 'ih-infotips' );
	}
	if ( wp_script_is( 'ih-infotips', 'registered' ) ) {
		wp_enqueue_script( 'ih-infotips' );
	}

	im_enqueue_request_messaging_assets();
}
add_action( 'wp_enqueue_scripts', 'im_enqueue_tool_detail_assets', 20 );

/**
 * Public Compare assets (page-compare.php). Self-contained scoped stylesheet
 * (im-compare.css, scoped .imc-page). No JS — remove/request are plain links.
 */
function im_enqueue_compare_assets() {
	if ( ! is_page_template( 'page-compare.php' ) ) {
		return;
	}

	$deps = array();
	if ( defined( 'IH_URL' ) && defined( 'IH_VERSION' ) ) {
		wp_enqueue_style( 'ih-figma-tokens', IH_URL . 'css/ih-figma-tokens.css', array(), IH_VERSION );
		$deps[] = 'ih-figma-tokens';
	}
	wp_enqueue_style(
		'google-fonts-ibm-plex-mono',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&display=swap',
		array(),
		null
	);
	$deps[] = 'google-fonts-ibm-plex-mono';

	$css_path = get_template_directory() . '/assets/css/im-compare.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1.0.0';
	wp_enqueue_style( 'im-compare', get_template_directory_uri() . '/assets/css/im-compare.css', $deps, $css_ver );
}
add_action( 'wp_enqueue_scripts', 'im_enqueue_compare_assets', 20 );

/**
 * Map a free-text automation level to a normalised 0..1 score for the radar axis.
 * Mirrors the Add Machine select (Manual / Semi-automated / Fully automated).
 */
function im_automation_score( $raw ) {
	$v = strtolower( trim( (string) $raw ) );
	if ( $v === '' ) {
		return 0.0;
	}
	if ( strpos( $v, 'lights' ) !== false ) {
		return 1.0;
	}
	if ( strpos( $v, 'full' ) !== false ) {
		return 0.85;
	}
	if ( strpos( $v, 'semi' ) !== false ) {
		return 0.5;
	}
	if ( strpos( $v, 'manual' ) !== false ) {
		return 0.0;
	}
	return 0.4;
}

/**
 * Build "vs typical listed presses" distributions from the REAL approved,
 * non-expired machine listings. For each spec we return min / max / median,
 * this machine's value, its percentile (0..100) and a tier label. Computed
 * server-side so the popovers are data-driven (never hard-coded ranges).
 * Degrades gracefully: fewer than IM_SPEC_MIN_SAMPLE comparable listings →
 * stats are null and the client hides the comparison ("limited data").
 *
 * @param array $m The current machine row (ARRAY_A).
 * @return array  Keyed by spec column → stats array|null.
 */
function im_machine_spec_stats( $m ) {
	global $wpdb;

	$specs = array(
		'clamping_force'         => 1,
		'shot_size'              => 1,
		'screw_diameter'         => 1,
		'max_injection_pressure' => 1,
		'max_monthly_output'     => 1,
		'max_part_weight'        => 1,
		'avg_cycle_time'         => 1, // lower is better
		'utilization'            => 1,
	);
	$lower_is_better = array( 'avg_cycle_time' => true );
	$min_sample      = 4;

	$first_num = static function ( $v ) {
		return preg_match( '/-?\d+(?:\.\d+)?/', (string) $v, $mm ) ? (float) $mm[0] : 0.0;
	};

	$not_expired = function_exists( 'ih_listing_not_expired_sql' ) ? ih_listing_not_expired_sql( 'expiry_date' ) : '1=1';
	$cols        = '`' . implode( '`,`', array_keys( $specs ) ) . '`';
	$rows        = $wpdb->get_results(
		"SELECT {$cols} FROM {$wpdb->prefix}ih_machines WHERE available = 1 AND {$not_expired}",
		ARRAY_A
	) ?: array();

	$out = array();
	foreach ( $specs as $col => $_ ) {
		$mine = $first_num( $m[ $col ] ?? '' );
		if ( $mine <= 0 ) {
			$out[ $col ] = null;
			continue;
		}
		$vals = array();
		foreach ( $rows as $r ) {
			$v = $first_num( $r[ $col ] ?? '' );
			if ( $v > 0 ) {
				$vals[] = $v;
			}
		}
		if ( count( $vals ) < $min_sample ) {
			$out[ $col ] = null;
			continue;
		}
		sort( $vals );
		$n        = count( $vals );
		$min      = $vals[0];
		$max      = $vals[ $n - 1 ];
		$median   = ( $n % 2 ) ? $vals[ (int) ( $n / 2 ) ] : ( ( $vals[ $n / 2 - 1 ] + $vals[ $n / 2 ] ) / 2 );
		$le       = 0;
		foreach ( $vals as $v ) {
			if ( $v <= $mine ) {
				$le++;
			}
		}
		$pct = (int) round( $le / $n * 100 );
		if ( ! empty( $lower_is_better[ $col ] ) ) {
			$pct = 100 - $pct; // for cycle time, faster (lower) ranks higher
		}
		$out[ $col ] = array(
			'value'  => $mine,
			'min'    => $min,
			'max'    => $max,
			'median' => $median,
			'pct'    => $pct,
			'count'  => $n,
		);
	}
	return $out;
}

/**
 * Build the JS payload for the machine detail page: anonymised, public-safe specs
 * + the current viewer's saved requirement profile. Never includes owner identity.
 */
function im_build_machine_detail_payload() {
	global $wpdb;

	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$m  = $id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ih_machines WHERE id = %d", $id ), ARRAY_A ) : null;

	$first_num = static function ( $v ) {
		return preg_match( '/-?\d+(?:\.\d+)?/', (string) $v, $mm ) ? (float) $mm[0] : 0.0;
	};
	$two_num = static function ( $v ) {
		preg_match_all( '/-?\d+(?:\.\d+)?/', (string) $v, $mm );
		$n = array_map( 'floatval', $mm[0] ?? array() );
		if ( count( $n ) >= 2 ) {
			return array( $n[0], $n[1] );
		}
		if ( count( $n ) === 1 ) {
			return array( $n[0], $n[0] );
		}
		return array( 0.0, 0.0 );
	};

	$listing   = array();
	$axes      = array();
	$specStats = array();
	if ( $m ) {
		$tie = $two_num( $m['tie_bar_spacing'] ?? '' );
		$mats = function_exists( 'ih_machine_materials' ) ? array_values( array_filter( array_map( 'trim', (array) ih_machine_materials( $m ) ) ) ) : array();
		$auto = function_exists( 'im_automation_score' ) ? im_automation_score( $m['automation_level'] ?? '' ) : 0.0;
		$listing = array(
			'clamp'      => $first_num( $m['clamping_force'] ?? '' ),
			'shot'       => $first_num( $m['shot_size'] ?? '' ),
			'screw'      => $first_num( $m['screw_diameter'] ?? '' ),
			'pressure'   => $first_num( $m['max_injection_pressure'] ?? '' ),
			'util'       => $first_num( $m['utilization'] ?? '' ),
			'cycle'      => $first_num( $m['avg_cycle_time'] ?? '' ),
			'ophours'    => $first_num( $m['operating_hours'] ?? '' ),
			'monthly'    => $first_num( $m['max_monthly_output'] ?? '' ),
			'automation' => $auto,
			'automationLabel' => trim( (string) ( $m['automation_level'] ?? '' ) ),
			'tieBarX'    => $tie[0],
			'tieBarY'    => $tie[1],
			'mhMax'      => $first_num( $m['max_mould_height'] ?? '' ),
			'mhMin'      => $first_num( $m['min_mould_height'] ?? '' ),
			'partWeight' => $first_num( $m['max_part_weight'] ?? '' ),
			'materials'  => $mats,
			'location'   => trim( (string) ( $m['location'] ?? '' ) ),
			/* Mould cavity count — drives parts/day & shot math (falls back to 1 in JS when 0). */
			'cavities'   => isset( $m['cavities'] ) ? max( 0, (int) $m['cavities'] ) : 0,
			/* Clamp-tonnage inputs the press is specced around (projected area cm² /
			 * cavity pressure bar). Exposed for the spec sheet + as a documented data
			 * point; the match calc derives required tonnage from the viewer's job. */
			'projectedArea'  => $first_num( $m['projected_area'] ?? '' ),
			'cavityPressure' => $first_num( $m['cavity_pressure'] ?? '' ),
		);

		/* Six radar axes → captured columns. AXIS_MAX are display constants (§21b).
		 * throughput uses max_monthly_output (units/mo); automation is normalised. */
		$axes = array(
			array( 'key' => 'clamp',      'label' => 'Clamp force',   'unit' => 'T',    'machine' => $listing['clamp'],      'max' => 500,    'req' => 'tonnage' ),
			array( 'key' => 'shot',       'label' => 'Shot size',     'unit' => 'mm',   'machine' => $listing['shot'],       'max' => 250,    'req' => 'shot' ),
			array( 'key' => 'screw',      'label' => 'Screw Ø',       'unit' => 'mm',   'machine' => $listing['screw'],      'max' => 120,    'req' => 'screw' ),
			array( 'key' => 'pressure',   'label' => 'Inj. pressure', 'unit' => 'bar',  'machine' => $listing['pressure'],   'max' => 2500,   'req' => 'pressure' ),
			array( 'key' => 'throughput', 'label' => 'Throughput',    'unit' => '/mo',  'machine' => $listing['monthly'],    'max' => 600000, 'req' => 'volume' ),
			array( 'key' => 'automation', 'label' => 'Automation',    'unit' => '',     'machine' => $auto,                  'max' => 1,      'req' => 'automation' ),
		);

		$specStats = function_exists( 'im_machine_spec_stats' ) ? im_machine_spec_stats( $m ) : array();
	}

	$req = null;
	$uid = get_current_user_id();
	if ( $uid ) {
		$saved = get_user_meta( $uid, 'ih_requirement_profile', true );
		if ( is_array( $saved ) && $saved ) {
			$req = $saved;
		}
	}

	$detail_return = home_url( '/machine/?id=' . $id );
	$login_url     = function_exists( 'im_auth_url' )
		? add_query_arg( 'redirect_to', rawurlencode( $detail_return ), im_auth_url( 'login' ) )
		: wp_login_url( $detail_return );

	/* Canonical automation levels — mirrors the Add Machine form select
	 * (pages/partials/ih-add-machine-form.php) and maps each label to the same
	 * normalised 0..1 score used for the radar axis (im_automation_score). */
	$automation_options = array();
	foreach ( array( 'Manual', 'Semi-automated', 'Fully automated' ) as $opt ) {
		$automation_options[] = array(
			'label' => $opt,
			'value' => function_exists( 'im_automation_score' ) ? im_automation_score( $opt ) : 0.0,
		);
	}

	return array(
		'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
		'nonce'       => wp_create_nonce( 'ih_nonce' ),
		'loggedIn'    => (bool) $uid,
		'listingId'   => $id,
		'loginUrl'    => $login_url,
		'dashboardUrl' => function_exists( 'im_get_dashboard_url' ) ? im_get_dashboard_url() : admin_url( 'admin.php?page=ih-user-dashboard' ),
		'listing'     => $listing,
		'axes'        => $axes,
		'specStats'   => $specStats,
		'requirement' => $req,
		'automationOptions' => $automation_options,
		'reducedHint' => false,
	);
}


// =============================================
// REGISTER WIDGET AREAS
// =============================================
function im_register_sidebars() {
    register_sidebar( [ 'name' => __( 'Homepage Hero', 'injection-moulding' ), 'id' => 'hero-sidebar', 'before_widget' => '<div class="hero-widget">', 'after_widget' => '</div>', 'before_title' => '<h3 class="widget-title">', 'after_title' => '</h3>' ] );
    register_sidebar( [ 'name' => __( 'Footer Column 1', 'injection-moulding' ), 'id' => 'footer-col-1', 'before_widget' => '<div class="footer-widget">', 'after_widget' => '</div>', 'before_title' => '<h4 class="footer-heading">', 'after_title' => '</h4>' ] );
    register_sidebar( [ 'name' => __( 'Footer Column 2', 'injection-moulding' ), 'id' => 'footer-col-2', 'before_widget' => '<div class="footer-widget">', 'after_widget' => '</div>', 'before_title' => '<h4 class="footer-heading">', 'after_title' => '</h4>' ] );
}
add_action( 'widgets_init', 'im_register_sidebars' );


// =============================================
// CUSTOM POST TYPES
// =============================================
function im_register_services_cpt() {
    register_post_type( 'service', [ 'labels' => [ 'name' => __( 'Services', 'injection-moulding' ), 'singular_name' => __( 'Service', 'injection-moulding' ), 'menu_name' => __( 'Services', 'injection-moulding' ), 'add_new' => __( 'Add New Service', 'injection-moulding' ), 'add_new_item' => __( 'Add New Service', 'injection-moulding' ), 'edit_item' => __( 'Edit Service', 'injection-moulding' ), 'new_item' => __( 'New Service', 'injection-moulding' ), 'view_item' => __( 'View Service', 'injection-moulding' ), 'search_items' => __( 'Search Services', 'injection-moulding' ), 'not_found' => __( 'No services found', 'injection-moulding' ), 'not_found_in_trash' => __( 'No services found in trash', 'injection-moulding' ) ], 'public' => true, 'publicly_queryable' => true, 'show_ui' => true, 'show_in_menu' => true, 'query_var' => true, 'rewrite' => [ 'slug' => 'services-list' ], 'capability_type' => 'post', 'has_archive' => true, 'hierarchical' => false, 'menu_position' => 5, 'menu_icon' => 'dashicons-hammer', 'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt' ], 'show_in_rest' => true ] );
}
add_action( 'init', 'im_register_services_cpt' );

function im_register_testimonials_cpt() {
    register_post_type( 'testimonial', [ 'labels' => [ 'name' => __( 'Testimonials', 'injection-moulding' ), 'singular_name' => __( 'Testimonial', 'injection-moulding' ), 'menu_name' => __( 'Testimonials', 'injection-moulding' ), 'add_new_item' => __( 'Add New Testimonial', 'injection-moulding' ), 'edit_item' => __( 'Edit Testimonial', 'injection-moulding' ) ], 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-format-quote', 'supports' => [ 'title', 'editor', 'thumbnail' ], 'show_in_rest' => true ] );
}
add_action( 'init', 'im_register_testimonials_cpt' );

function im_register_machines() {
    register_post_type( 'machine', [ 'labels' => [ 'name' => 'Machines', 'singular_name' => 'Machine' ], 'public' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-admin-tools', 'supports' => [ 'title', 'editor', 'thumbnail', 'custom-fields' ], 'show_in_rest' => true ] );
}
add_action( 'init', 'im_register_machines' );

function im_register_tools() {
    register_post_type( 'tool', [ 'labels' => [ 'name' => 'Tools', 'singular_name' => 'Tool' ], 'public' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-hammer', 'supports' => [ 'title', 'editor', 'thumbnail', 'custom-fields' ], 'show_in_rest' => true ] );
}
add_action( 'init', 'im_register_tools' );

function im_register_messages() {
    register_post_type( 'im_message', [ 'labels' => [ 'name' => 'Messages', 'singular_name' => 'Message' ], 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-email', 'supports' => [ 'title', 'editor', 'custom-fields' ] ] );
}
add_action( 'init', 'im_register_messages' );

function im_register_conversations() {
    register_post_type( 'conversation', [ 'labels' => [ 'name' => 'Conversations', 'singular_name' => 'Conversation' ], 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-format-chat', 'supports' => [ 'title', 'custom-fields' ] ] );
}
add_action( 'init', 'im_register_conversations' );

function im_register_contact_requests() {
    register_post_type( 'contact_request', [ 'labels' => [ 'name' => 'Contact Requests', 'singular_name' => 'Contact Request' ], 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-networking', 'supports' => [ 'title', 'custom-fields' ] ] );
}
add_action( 'init', 'im_register_contact_requests' );


// =============================================
// USER ROLES
// =============================================
function im_add_user_roles() {
    if ( ! get_role( 'manufacturer' ) ) add_role( 'manufacturer', 'Manufacturer', [ 'read' => true, 'edit_posts' => true, 'upload_files' => true ] );
    if ( ! get_role( 'tool_owner' ) )   add_role( 'tool_owner',   'Tool Owner',   [ 'read' => true, 'edit_posts' => true, 'upload_files' => true ] );
}
add_action( 'init', 'im_add_user_roles' );


// =============================================
// THEME CUSTOMIZER
// =============================================
function im_customize_register( $wp_customize ) {
    $wp_customize->add_panel( 'im_homepage_panel', [ 'title' => __( 'Homepage Settings', 'injection-moulding' ), 'description' => __( 'Configure homepage sections', 'injection-moulding' ), 'priority' => 30 ] );
    $wp_customize->add_section( 'im_hero_section', [ 'title' => __( 'Hero Section', 'injection-moulding' ), 'panel' => 'im_homepage_panel', 'priority' => 10 ] );
    $hero_fields = [
        'im_hero_badge_text'     => [ 'Badge Text',            "UK's Leading Injection Moulding Platform", 'text' ],
        'im_hero_title'          => [ 'Hero Title',            "Connecting Tool Owners\nwith Reliable Manufacturers", 'textarea' ],
        'im_hero_description'    => [ 'Hero Description',      'A dedicated platform for businesses who already have mould tools and are searching for trusted injection moulding manufacturing partners.', 'textarea' ],
        'im_hero_stats_clients'  => [ 'Clients Stat',          '100k+', 'text' ],
        'im_hero_stats_projects' => [ 'Projects Stat',         '1574+', 'text' ],
        'im_hero_stats_years'    => [ 'Years of Service Stat', '15+',   'text' ],
    ];
    foreach ( $hero_fields as $key => [ $label, $default, $type ] ) {
        $wp_customize->add_setting( $key, [ 'default' => $default, 'sanitize_callback' => $type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field' ] );
        $wp_customize->add_control( $key, [ 'label' => __( $label, 'injection-moulding' ), 'section' => 'im_hero_section', 'type' => $type ] );
    }
    $wp_customize->add_section( 'im_about_section', [ 'title' => __( 'About Section', 'injection-moulding' ), 'panel' => 'im_homepage_panel', 'priority' => 20 ] );
    $wp_customize->add_setting( 'im_about_title', [ 'default' => 'We connect product owners with trusted injection moulding manufacturers', 'sanitize_callback' => 'sanitize_textarea_field' ] );
    $wp_customize->add_control( 'im_about_title', [ 'label' => __( 'About Title', 'injection-moulding' ), 'section' => 'im_about_section', 'type' => 'textarea' ] );
    $wp_customize->add_setting( 'im_about_text', [ 'default' => 'Our platform bridges the gap between product owners and specialist manufacturers, making the process of finding the right moulding partner simple and reliable.', 'sanitize_callback' => 'sanitize_textarea_field' ] );
    $wp_customize->add_control( 'im_about_text', [ 'label' => __( 'About Text', 'injection-moulding' ), 'section' => 'im_about_section', 'type' => 'textarea' ] );
    $wp_customize->add_section( 'im_contact_section', [ 'title' => __( 'Contact Details', 'injection-moulding' ), 'panel' => 'im_homepage_panel', 'priority' => 30 ] );
    $wp_customize->add_setting( 'im_contact_email', [ 'default' => 'info@mouldinjection.co.uk', 'sanitize_callback' => 'sanitize_email' ] );
    $wp_customize->add_control( 'im_contact_email', [ 'label' => __( 'Email Address', 'injection-moulding' ), 'section' => 'im_contact_section', 'type' => 'email' ] );
    $wp_customize->add_setting( 'im_contact_phone', [ 'default' => '+44 (0) 1234 567890', 'sanitize_callback' => 'sanitize_text_field' ] );
    $wp_customize->add_control( 'im_contact_phone', [ 'label' => __( 'Phone Number', 'injection-moulding' ), 'section' => 'im_contact_section', 'type' => 'text' ] );
    $wp_customize->add_setting( 'im_contact_address', [ 'default' => 'United Kingdom', 'sanitize_callback' => 'sanitize_textarea_field' ] );
    $wp_customize->add_control( 'im_contact_address', [ 'label' => __( 'Address', 'injection-moulding' ), 'section' => 'im_contact_section', 'type' => 'textarea' ] );
}
add_action( 'customize_register', 'im_customize_register' );


// =============================================
// LOGIN / REGISTER REDIRECT
// =============================================

function im_get_dashboard_url( $user = null ) {
    if ( ! $user ) $user = wp_get_current_user();
    if ( in_array( 'administrator', (array) $user->roles ) ) {
        return admin_url( 'admin.php?page=ih-dashboard' );
    }
    return admin_url( 'admin.php?page=ih-user-dashboard' );
}

/* 1. Standard WP login page */
add_filter( 'login_redirect', function( $redirect_to, $request, $user ) {
    if ( is_wp_error( $user ) || empty( $user->roles ) ) return $redirect_to;
    return im_get_dashboard_url( $user );
}, 999, 3 );

/* 2. admin_init */
add_action( 'admin_init', function() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
    if ( ! is_user_logged_in() ) return;
    $screen = get_current_screen();
    if ( ! $screen || $screen->id !== 'dashboard' ) return;
    wp_safe_redirect( im_get_dashboard_url() );
    exit;
} );

/* 3. wp_login — AJAX skip */
add_action( 'wp_login', function( $user_login, $user ) {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
    if ( ! $user || empty( $user->roles ) ) return;
    $url = im_get_dashboard_url( $user );
    if ( ! headers_sent() ) { wp_safe_redirect( $url ); exit; }
    echo '<script>window.location="' . esc_js( $url ) . '";</script>';
    exit;
}, 10, 2 );

/* 4. user_register — AJAX skip */
add_action( 'user_register', function( $user_id ) {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
    $url = admin_url( 'admin.php?page=ih-user-dashboard' );
    if ( ! headers_sent() ) { wp_safe_redirect( $url ); exit; }
    echo '<script>window.location="' . esc_js( $url ) . '";</script>';
    exit;
} );

/* 5. Logout — always return to the homepage */
add_filter( 'logout_redirect', fn() => home_url( '/' ), 999 );

/* 6. Front-end auth page replaces wp-login.php.
   Visitors hitting wp-login.php are sent to /register/.
   Pass-through cases: logout, password reset flows, form POSTs,
   logged-in admins, and the ?ih_admin=1 emergency escape hatch. */
add_action( 'login_init', function() {
    $action  = sanitize_key( $_REQUEST['action'] ?? 'login' );
    $allowed = [ 'logout', 'lostpassword', 'retrievepassword', 'rp', 'resetpass', 'postpass', 'confirm_admin_email', 'confirmaction' ];
    if ( in_array( $action, $allowed, true ) ) return;
    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) return;
    if ( isset( $_GET['ih_admin'] ) ) return;
    if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) return;
    $dest = home_url( '/register/?tab=login' );
    if ( ! empty( $_GET['redirect_to'] ) ) {
        $redirect_to = wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), false );
        if ( $redirect_to ) {
            $dest = add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $dest );
        }
    }
    wp_safe_redirect( $dest );
    exit;
} );

/* 7. Front-end auth URL helper (used in emails and templates) */
function im_auth_url( $tab = 'login' ) {
    return home_url( '/register/?tab=' . $tab );
}


// =============================================
// ★ ONE-TIME LOGIN TOKEN HANDLER
//   Modal AJAX → token → server-side login → dashboard
//   Completes on admin-ajax.php (never page-cached by Breeze).
// =============================================
function ih_login_token_completion_url( $token ) {
    return add_query_arg(
        array(
            'action'   => 'ih_complete_login',
            'ih_token' => $token,
        ),
        admin_url( 'admin-ajax.php' )
    );
}

function ih_process_login_token( $token ) {
    $token = sanitize_text_field( (string) $token );
    if ( $token === '' ) {
        return false;
    }

    $data = get_transient( 'ih_login_' . $token );
    if ( ! $data || ! is_array( $data ) ) {
        return false;
    }

    delete_transient( 'ih_login_' . $token );

    $user_id  = (int) ( $data['user_id'] ?? 0 );
    $redirect = $data['redirect'] ?? admin_url( 'admin.php?page=ih-user-dashboard' );
    if ( ! $user_id ) {
        return false;
    }

    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return false;
    }

    remove_all_actions( 'wp_login' );
    wp_clear_auth_cookie();
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true, is_ssl() );
    do_action( 'wp_login', $user->user_login, $user );

    return $redirect;
}

function ih_finish_login_token_request() {
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }
    nocache_headers();

    $token    = isset( $_GET['ih_token'] ) ? sanitize_text_field( wp_unslash( $_GET['ih_token'] ) ) : '';
    $redirect = $token ? ih_process_login_token( $token ) : false;
    if ( $redirect ) {
        wp_safe_redirect( $redirect );
        exit;
    }

    wp_safe_redirect( add_query_arg( 'ih_login_error', 'expired', im_auth_url( 'login' ) ) );
    exit;
}

add_action( 'wp_ajax_nopriv_ih_complete_login', 'ih_finish_login_token_request' );
add_action( 'wp_ajax_ih_complete_login', 'ih_finish_login_token_request' );

// Legacy homepage ?ih_token= URLs — bypass full-page cache, then redirect to dashboard.
add_action( 'init', function() {
    if ( empty( $_GET['ih_token'] ) ) {
        return;
    }
    ih_finish_login_token_request();
}, 1 );

add_action( 'init', function() {
    if ( empty( $_GET['ih_token'] ) && empty( $_GET['ih_complete_login'] ) ) {
        return;
    }
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }
}, 0 );


// =============================================
// EXCERPT
// =============================================
add_filter( 'excerpt_length', fn() => 25 );
add_filter( 'excerpt_more',   fn() => '...' );


// =============================================
// SECURITY
// =============================================
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'login_errors', fn() => __( 'Invalid login credentials.', 'injection-moulding' ) );

/* Hide the WP version from the page source */
remove_action( 'wp_head', 'wp_generator' );

/* Block username enumeration via ?author=N scans and author archives */
add_action( 'template_redirect', function() {
    if ( is_admin() ) return;
    if ( is_author() || ( isset( $_GET['author'] ) && ! is_user_logged_in() ) ) {
        wp_safe_redirect( home_url( '/' ) );
        exit;
    }
} );

/* Hide the REST users endpoint from logged-out visitors */
add_filter( 'rest_endpoints', function( $endpoints ) {
    if ( ! is_user_logged_in() ) {
        unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    return $endpoints;
} );


// =============================================
// HELPER FUNCTIONS
// =============================================
function im_get_option( $key, $default = '' ) { return get_theme_mod( $key, $default ); }

function im_site_logo() {
    if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) the_custom_logo();
    else echo '<p class="nav-brand-text">Injection<br/>Moulding</p>';
}

function im_get_hero_image() { return get_template_directory_uri() . '/assets/images/hero-main.png'; }

function im_can_client_message_owner( $client_id, $machine_id ) {
    $requests = get_posts( [ 'post_type' => 'contact_request', 'post_status' => 'publish', 'meta_query' => [ 'relation' => 'AND', [ 'key' => 'client_id', 'value' => $client_id ], [ 'key' => 'machine_id', 'value' => $machine_id ], [ 'key' => 'status', 'value' => 'approved' ] ], 'numberposts' => 1 ] );
    return ! empty( $requests );
}

function im_get_request_status( $client_id, $machine_id ) {
    $requests = get_posts( [ 'post_type' => 'contact_request', 'post_status' => 'publish', 'meta_query' => [ [ 'key' => 'client_id', 'value' => $client_id ], [ 'key' => 'machine_id', 'value' => $machine_id ] ], 'numberposts' => 1 ] );
    if ( empty( $requests ) ) return null;
    return get_post_meta( $requests[0]->ID, 'status', true );
}

function im_status_badge( $status ) {
    $classes = [ 'approved' => 'bg-green-100 text-green-700', 'pending' => 'bg-yellow-100 text-yellow-700', 'rejected' => 'bg-red-100 text-red-600', 'blocked' => 'bg-rose-100 text-rose-600' ];
    $class = $classes[ $status ] ?? 'bg-gray-100 text-gray-600';
    return sprintf( '<span class="%s text-xs font-semibold px-3 py-1 rounded-full capitalize">%s</span>', esc_attr( $class ), esc_html( ucfirst( $status ) ) );
}

function im_dashboard_url( $page = 'dashboard', $extra_params = [] ) {
    $base_page_id = get_option( 'im_dashboard_page_id', 0 );
    $url = $base_page_id ? get_permalink( $base_page_id ) : home_url( '/dashboard/' );
    return add_query_arg( array_merge( [ 'page' => $page ], $extra_params ), $url );
}

function im_get_listing_status( $post_id ) {
    $expiry = get_post_meta( $post_id, 'expiry_date', true );
    return ( $expiry && strtotime( $expiry ) < time() ) ? 'expired' : 'available';
}


// =============================================
// AJAX HANDLERS
// =============================================
add_action( 'wp_ajax_remove_machine_listing', function () {
    if ( ! check_ajax_referer( 'machine_action', 'nonce', false ) ) wp_send_json_error( [ 'message' => 'Security check failed' ] );
    if ( ! current_user_can( 'administrator' ) ) wp_send_json_error( [ 'message' => 'Permission denied' ] );
    $machine_id = intval( $_POST['machine_id'] ?? 0 );
    if ( ! $machine_id ) wp_send_json_error( [ 'message' => 'Invalid machine ID' ] );
    wp_trash_post( $machine_id ) ? wp_send_json_success( [ 'message' => 'Machine listing removed' ] ) : wp_send_json_error( [ 'message' => 'Failed to remove listing' ] );
} );

add_action( 'wp_ajax_block_machine_owner', function () {
    if ( ! check_ajax_referer( 'machine_action', 'nonce', false ) ) wp_send_json_error( [ 'message' => 'Security check failed' ] );
    if ( ! current_user_can( 'administrator' ) ) wp_send_json_error( [ 'message' => 'Permission denied' ] );
    $machine_id = intval( $_POST['machine_id'] ?? 0 );
    $owner_user_id = get_post_meta( $machine_id, 'owner_user_id', true );
    if ( $owner_user_id ) { update_user_meta( $owner_user_id, 'account_status', 'blocked' ); wp_send_json_success( [ 'message' => 'Owner blocked successfully' ] ); }
    else wp_send_json_error( [ 'message' => 'Owner not found' ] );
} );

add_action( 'wp_ajax_update_message_status', function () {
    if ( ! check_ajax_referer( 'message_action', 'nonce', false ) ) wp_send_json_error( [ 'message' => 'Security check failed' ] );
    if ( ! current_user_can( 'administrator' ) ) wp_send_json_error( [ 'message' => 'Permission denied' ] );
    $new_status = sanitize_text_field( $_POST['status'] ?? '' );
    if ( ! in_array( $new_status, [ 'approved', 'rejected', 'pending' ] ) ) wp_send_json_error( [ 'message' => 'Invalid status' ] );
    wp_send_json_success( [ 'message' => 'Status updated to ' . $new_status ] );
} );

add_action( 'wp_ajax_toggle_user_block', function () {
    if ( ! check_ajax_referer( 'user_action', 'nonce', false ) ) wp_send_json_error( [ 'message' => 'Security check failed' ] );
    if ( ! current_user_can( 'administrator' ) ) wp_send_json_error( [ 'message' => 'Permission denied' ] );
    $user_id = intval( $_POST['user_id'] ?? 0 );
    $action  = sanitize_text_field( $_POST['block_action'] ?? '' );
    if ( ! $user_id || ! in_array( $action, [ 'block', 'unblock' ] ) ) wp_send_json_error( [ 'message' => 'Invalid parameters' ] );
    $new_status = $action === 'block' ? 'blocked' : 'active';
    update_user_meta( $user_id, 'account_status', $new_status );
    wp_send_json_success( [ 'message' => 'User ' . $new_status . ' successfully', 'new_status' => $new_status ] );
} );

add_action( 'wp_ajax_im_delete_listing', function () {
    check_ajax_referer( 'machine_action', 'nonce' );
    if ( ! current_user_can( 'administrator' ) ) wp_send_json_error( 'Unauthorized' );
    $id   = intval( $_POST['item_id'] );
    $type = sanitize_text_field( $_POST['item_type'] );
    if ( $id > 0 && in_array( $type, [ 'machine', 'tool' ] ) ) { wp_trash_post( $id ); wp_send_json_success(); }
    wp_send_json_error( 'Invalid Request' );
} );

// [ih audit] Duplicate 'ih_get_request_analytics' handler removed. The authoritative
// handler lives in the plugin (insight-hub-dashboard.php) and queries the ih_requests
// table; this theme copy used the obsolete contact_request CPT and never executed
// (the plugin registers earlier and sends the JSON response first).

add_action( 'wp_ajax_send_admin_message', function () {
    if ( ! check_ajax_referer( 'message_action', 'nonce', false ) ) wp_send_json_error( [ 'message' => 'Security check failed' ] );
    if ( ! current_user_can( 'administrator' ) ) wp_send_json_error( [ 'message' => 'Permission denied' ] );
    global $wpdb;
    $receiver_id = intval( $_POST['receiver_id'] ?? 0 );
    $message     = sanitize_textarea_field( $_POST['message'] ?? '' );
    if ( ! $receiver_id || empty( $message ) ) wp_send_json_error( [ 'message' => 'Invalid data' ] );
    $result = $wpdb->insert( $wpdb->prefix . 'im_messages', [ 'sender_id' => get_current_user_id(), 'receiver_id' => $receiver_id, 'message' => $message, 'status' => 'sent', 'created_at' => current_time( 'mysql' ) ], [ '%d', '%d', '%s', '%s', '%s' ] );
    $result ? wp_send_json_success( [ 'message_id' => $wpdb->insert_id, 'message' => $message, 'time' => date( 'g:i A' ) ] ) : wp_send_json_error( [ 'message' => 'Failed to save message' ] );
} );

add_action( 'wp_ajax_get_new_messages', function () {
    if ( ! is_user_logged_in() ) wp_send_json_error();
    global $wpdb;
    $conv_id  = intval( $_GET['conv_id'] ?? 0 );
    $after_id = intval( $_GET['after_id'] ?? 0 );
    $messages = $wpdb->get_results( $wpdb->prepare( "SELECT id, sender_id, message, created_at FROM {$wpdb->prefix}im_messages WHERE ((sender_id=%d AND receiver_id=%d) OR (sender_id=%d AND receiver_id=%d)) AND id > %d ORDER BY id ASC LIMIT 20", $conv_id, get_current_user_id(), get_current_user_id(), $conv_id, $after_id ) );
    wp_send_json_success( [ 'messages' => $messages ] );
} );

require_once get_template_directory() . '/ih-listing-notify.php';

add_action( 'wp_ajax_ih_public_machine_enquiry',        'ih_handle_public_machine_enquiry' );
add_action( 'wp_ajax_nopriv_ih_public_machine_enquiry', 'ih_handle_public_machine_enquiry' );

function ih_handle_public_machine_enquiry() {
    if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ?? '' ), 'ih_public_enquiry' ) ) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }
    $machine_id  = intval( $_POST['machine_id'] ?? 0 );
    $machine_ref = sanitize_text_field( $_POST['machine_ref'] ?? '' );
    $name        = sanitize_text_field( $_POST['enquiry_name'] ?? '' );
    $email       = sanitize_email( $_POST['enquiry_email'] ?? '' );
    $phone       = sanitize_text_field( $_POST['enquiry_phone'] ?? '' );
    $message     = sanitize_textarea_field( $_POST['enquiry_message'] ?? '' );
    if ( ! $name || ! $email || ! $message ) { wp_send_json_error(['message' => 'Please fill in all required fields.']); }
    $admin_email = get_option('admin_email');
    $subject     = "New Machine Enquiry: {$machine_ref}";
    $body        = "New enquiry received.\n\nMachine: {$machine_ref}\nID: {$machine_id}\n\nFrom: {$name}\nEmail: {$email}\nPhone: {$phone}\n\nMessage:\n{$message}";
    wp_mail( $admin_email, $subject, $body, [ 'Content-Type: text/plain; charset=UTF-8', "Reply-To: {$name} <{$email}>" ] );
    if ( function_exists( 'ih_create_request_and_notify' ) ) {
        ih_create_request_and_notify( [
            'name'         => $name,
            'email'        => $email,
            'phone'        => $phone,
            'listing_id'   => $machine_id,
            'listing_type' => 'machine',
            'message'      => $message,
        ] );
    } elseif ( is_user_logged_in() ) {
        global $wpdb;
        $user_id   = get_current_user_id();
        $thread_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}ih_threads WHERE user_id=%d AND listing_id=%d AND listing_type='machine' ORDER BY id DESC LIMIT 1", $user_id, $machine_id ) );
        if ( ! $thread_id ) { $wpdb->insert( $wpdb->prefix . 'ih_threads', [ 'user_id' => $user_id, 'listing_id' => $machine_id, 'listing_type' => 'machine', 'last_message' => $message, 'last_time' => current_time('mysql'), 'unread' => 0 ] ); $thread_id = $wpdb->insert_id; }
        if ( $thread_id ) { $wpdb->insert( $wpdb->prefix . 'ih_chats', [ 'thread_id' => $thread_id, 'from_me' => 0, 'message' => "Enquiry:\n\n{$message}\n\nPhone: {$phone}", 'sent_at' => current_time('mysql') ] ); $wpdb->update( $wpdb->prefix . 'ih_threads', ['last_message' => $message, 'last_time' => current_time('mysql'), 'unread' => 1], ['id' => $thread_id] ); }
    }
    wp_send_json_success(['message' => 'Enquiry sent successfully.']);
}

add_action( 'wp_ajax_ih_approve_listing', 'ih_approve_listing_handler' );
function ih_approve_listing_handler() {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'ih_nonce' ) || ! current_user_can('administrator') ) { wp_send_json_error(['message' => 'Unauthorized']); }
    global $wpdb;
    $listing_id = intval( $_POST['listing_id'] ?? 0 ); $listing_type = sanitize_text_field( $_POST['listing_type'] ?? '' ); $thread_id = intval( $_POST['thread_id'] ?? 0 );
    if ( ! $listing_id || ! $listing_type ) { wp_send_json_error(['message' => 'Missing data']); }
    $wpdb->update( $wpdb->prefix . 'ih_requests', ['status' => 'Approved'], ['listing_id' => $listing_id, 'listing_type' => $listing_type], ['%s'], ['%d','%s'] );
    if ( function_exists( 'ih_update_listing_status' ) ) {
        ih_update_listing_status( $listing_type, $listing_id, 'available' );
    } elseif ( $listing_type === 'machine' ) {
        $wpdb->update( $wpdb->prefix . 'ih_machines', ['available' => 1, 'listing_status' => 'available'], ['id' => $listing_id], ['%d','%s'], ['%d'] );
    } elseif ( $listing_type === 'tool' ) {
        $wpdb->update( $wpdb->prefix . 'ih_tools', ['available' => 1, 'listing_status' => 'available'], ['id' => $listing_id], ['%d','%s'], ['%d'] );
    }
    if ( function_exists( 'delete_transient' ) ) {
        delete_transient( 'ih_admin_notifications_v1' );
    }
    if ( $thread_id ) { $wpdb->insert( $wpdb->prefix . 'ih_chats', [ 'thread_id' => $thread_id, 'from_me' => 1, 'message' => '✅ Your listing has been approved.', 'sent_at' => current_time('mysql') ] ); $wpdb->update( $wpdb->prefix . 'ih_threads', ['last_message' => 'Approved.', 'last_time' => current_time('mysql'), 'unread' => 1], ['id' => $thread_id] ); }
    wp_send_json_success(['message' => 'Approved.']);
}

add_action( 'wp_ajax_ih_reject_listing', 'ih_reject_listing_handler' );
function ih_reject_listing_handler() {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'ih_nonce' ) || ! current_user_can('administrator') ) { wp_send_json_error(['message' => 'Unauthorized']); }
    global $wpdb;
    $listing_id = intval( $_POST['listing_id'] ?? 0 ); $listing_type = sanitize_text_field( $_POST['listing_type'] ?? '' ); $thread_id = intval( $_POST['thread_id'] ?? 0 );
    $wpdb->update( $wpdb->prefix . 'ih_requests', ['status' => 'Rejected'], ['listing_id' => $listing_id, 'listing_type' => $listing_type], ['%s'], ['%d','%s'] );
    if ( function_exists( 'ih_update_listing_status' ) ) {
        ih_update_listing_status( $listing_type, $listing_id, 'rejected' );
    } elseif ( $listing_type === 'machine' ) {
        $wpdb->update( $wpdb->prefix . 'ih_machines', ['available' => 0, 'listing_status' => 'rejected'], ['id' => $listing_id], ['%d','%s'], ['%d'] );
    } elseif ( $listing_type === 'tool' ) {
        $wpdb->update( $wpdb->prefix . 'ih_tools', ['available' => 0, 'listing_status' => 'rejected'], ['id' => $listing_id], ['%d','%s'], ['%d'] );
    }
    if ( function_exists( 'delete_transient' ) ) {
        delete_transient( 'ih_admin_notifications_v1' );
    }
    if ( $thread_id ) { $wpdb->insert( $wpdb->prefix . 'ih_chats', [ 'thread_id' => $thread_id, 'from_me' => 1, 'message' => '❌ Your listing has been rejected.', 'sent_at' => current_time('mysql') ] ); }
    wp_send_json_success(['message' => 'Rejected.']);
}


// =============================================
// MODAL LOGIN AJAX
// =============================================
add_action( 'wp_ajax_nopriv_ih_modal_login', 'ih_handle_modal_login' );
add_action( 'wp_ajax_ih_modal_login',        'ih_handle_modal_login' );

function ih_handle_modal_login() {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'im_login' ) ) {
        wp_send_json_error( 'Security check failed.' );
    }

    $email = sanitize_email( $_POST['login_email'] ?? '' );
    $pass  = $_POST['login_password'] ?? '';
    $rem   = ! empty( $_POST['remember'] );

    if ( empty( $email ) || empty( $pass ) ) {
        wp_send_json_error( 'Please enter your email and password.' );
    }

    $user = get_user_by( 'email', $email );

    // Generic message for both unknown email and wrong password —
    // prevents account enumeration through the login form.
    if ( ! $user || ! wp_check_password( $pass, $user->user_pass, $user->ID ) ) {
        wp_send_json_error( 'Invalid email or password. Please try again.' );
    }

    // Dashboard URL
    $dashboard = in_array( 'administrator', (array) $user->roles )
        ? admin_url( 'admin.php?page=ih-dashboard' )
        : admin_url( 'admin.php?page=ih-user-dashboard' );

    // ★ ONE-TIME TOKEN — cookie issue permanently fix
    $token = wp_generate_password( 32, false );
    set_transient( 'ih_login_' . $token, [
        'user_id'  => $user->ID,
        'redirect' => $dashboard,
    ], 60 ); // 60 seconds valid

    wp_send_json_success( [
        'redirect' => ih_login_token_completion_url( $token ),
    ] );
}


// =============================================
// MODAL REGISTER AJAX
// =============================================
add_action( 'wp_ajax_nopriv_ih_modal_register', 'ih_handle_modal_register' );
add_action( 'wp_ajax_ih_modal_register',        'ih_handle_modal_register' );

function ih_handle_modal_register() {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'im_register' ) ) {
        wp_send_json_error( [ 'Security check failed. Please try again.' ] );
    }

    $biz_role   = sanitize_text_field( $_POST['biz_role']         ?? '' );
    $company    = sanitize_text_field( $_POST['company_name']     ?? '' );
    $contact    = sanitize_text_field( $_POST['contact_name']     ?? '' );
    $job_title  = sanitize_text_field( $_POST['job_title']        ?? '' );
    $address    = sanitize_text_field( $_POST['address']          ?? '' );
    $city       = sanitize_text_field( $_POST['city']             ?? '' );
    $postcode   = sanitize_text_field( $_POST['postcode']         ?? '' );
    $office_num = sanitize_text_field( $_POST['office_number']    ?? '' );
    $website    = esc_url_raw(         $_POST['website']          ?? '' );
    $phone      = sanitize_text_field( $_POST['phone']            ?? '' );
    $email      = sanitize_email(      $_POST['email']            ?? '' );
    $email_conf = sanitize_email(      $_POST['email_confirm']    ?? '' );
    $password   =                      $_POST['password']         ?? '';
    $pass_conf  =                      $_POST['password_confirm'] ?? '';

    $errors = [];
    if ( empty( $biz_role ) )             $errors[] = 'Please select a business role.';
    if ( empty( $company ) )              $errors[] = 'Company name is required.';
    if ( empty( $contact ) )              $errors[] = 'Contact name is required.';
    if ( empty( $email ) )                $errors[] = 'Email address is required.';
    if ( ! is_email( $email ) )           $errors[] = 'Please enter a valid email address.';
    if ( $email !== $email_conf )         $errors[] = 'Email addresses do not match.';
    if ( empty( $password ) )             $errors[] = 'Password is required.';
    if ( strlen( $password ) < 8 )        $errors[] = 'Password must be at least 8 characters.';
    if ( $password !== $pass_conf )       $errors[] = 'Passwords do not match.';
    if ( email_exists( $email ) )         $errors[] = 'This email address is already registered.';
    if ( empty( $_POST['agree_terms'] ) ) $errors[] = 'You must agree to the Terms & Conditions.';
    if ( empty( $_POST['agree_fees'] ) )  $errors[] = 'You must understand that fees may apply.';
    if ( ! empty( $errors ) ) { wp_send_json_error( $errors ); }

    $username      = sanitize_user( strtolower( str_replace( ' ', '.', $contact ) ) );
    $base_username = $username; $suffix = 1;
    while ( username_exists( $username ) ) { $username = $base_username . $suffix; $suffix++; }

    // user_register hook hatao
    remove_all_actions( 'user_register' );

    $user_id = wp_create_user( $username, $password, $email );
    if ( is_wp_error( $user_id ) ) { wp_send_json_error( [ $user_id->get_error_message() ] ); }

    wp_update_user( [ 'ID' => $user_id, 'display_name' => $contact, 'first_name' => explode( ' ', $contact )[0] ?? $contact, 'last_name' => explode( ' ', $contact )[1] ?? '' ] );

    $user_obj = new WP_User( $user_id );
    $role_map = [ 'Manufacturer' => 'manufacturer', 'Tool Owner' => 'tool_owner', 'Product Developer' => 'tool_owner', 'Startup' => 'subscriber', 'Overseas Buyer' => 'subscriber', 'Other' => 'subscriber' ];
    $user_obj->set_role( $role_map[ $biz_role ] ?? 'subscriber' );

    update_user_meta( $user_id, 'business_role', $biz_role );
    update_user_meta( $user_id, 'company_name',  $company );
    update_user_meta( $user_id, 'job_title',     $job_title );
    update_user_meta( $user_id, 'address',       $address );
    update_user_meta( $user_id, 'city',          $city );
    update_user_meta( $user_id, 'postcode',      $postcode );
    update_user_meta( $user_id, 'office_number', $office_num );
    update_user_meta( $user_id, 'website',       $website );
    update_user_meta( $user_id, 'phone',         $phone );

    global $wpdb;
    $wpdb->insert( "{$wpdb->prefix}ih_profiles", [ 'user_id' => $user_id, 'business_role' => $biz_role, 'company_name' => $company, 'job_title' => $job_title, 'phone' => $phone, 'address' => $address, 'city' => $city, 'postcode' => $postcode, 'website' => $website, 'whatsapp' => $phone, 'account_status' => 'active' ] );

    wp_mail( $email, 'Welcome to Injection Moulding Platform!', "Hi {$contact},\n\nYour account has been created.\n\nLogin: " . home_url('/register/?tab=login') . "\n\nRegards,\nInjection Moulding Team" );

    // ★ ONE-TIME TOKEN for register too
    $token = wp_generate_password( 32, false );
    set_transient( 'ih_login_' . $token, [
        'user_id'  => $user_id,
        'redirect' => admin_url( 'admin.php?page=ih-user-dashboard' ),
    ], 60 );

    wp_send_json_success( [
        'redirect' => ih_login_token_completion_url( $token ),
    ] );
}
add_action('wp_ajax_ih_toggle_wishlist','ih_toggle_wishlist');

function ih_toggle_wishlist(){

    if( ! is_user_logged_in() ){

        wp_send_json_error();
    }

    check_ajax_referer( 'ih_nonce', 'nonce' );

    $user_id = get_current_user_id();

    $id    = intval($_POST['listing_id']);

    $type  = sanitize_text_field($_POST['listing_type']);

    $title = sanitize_text_field($_POST['listing_title']);

    $image = esc_url_raw($_POST['listing_image']);

    $wishlist = get_user_meta(
        $user_id,
        'ih_wishlist',
        true
    );

    if( ! is_array($wishlist) ){

        $wishlist = [];
    }

    $exists = false;

    foreach($wishlist as $k => $item){

        if(
            $item['id'] == $id &&
            $item['type'] == $type
        ){

            unset($wishlist[$k]);

            $exists = true;

            break;
        }
    }

    if( ! $exists ){

        $wishlist[] = [

            'id'    => $id,
            'type'  => $type,
            'title' => $title,
            'image' => $image,
        ];
    }

    update_user_meta(
        $user_id,
        'ih_wishlist',
        array_values($wishlist)
    );

    wp_send_json_success([
        'saved' => ! $exists
    ]);
}
if ( ! function_exists('ih_generate_unique_id') ) {
    function ih_generate_unique_id( $contact_name, $city = '' ) {
        $name_clean = preg_replace( '/[^a-zA-Z]/', '', $contact_name );
        $name_part  = str_pad( strtoupper( substr( $name_clean, 0, 3 ) ), 3, 'X' );
 
        $city_clean = preg_replace( '/[^a-zA-Z]/', '', $city );
        $city_part  = str_pad( strtoupper( substr( $city_clean, 0, 3 ) ), 3, 'X' );
 
        $base    = $name_part . $city_part;  // e.g. JOHLAH
        $counter = 1;
 
        do {
            $candidate = $base . str_pad( $counter, 3, '0', STR_PAD_LEFT ); // JOHLAH001
            $existing  = get_users( [
                'meta_key'   => 'ih_unique_id',
                'meta_value' => $candidate,
                'number'     => 1,
                'fields'     => 'ids',
            ] );
            $counter++;
        } while ( ! empty( $existing ) && $counter < 9999 );
 
        return $candidate;
    }
}
 
// ────────────────────────────────────────────────────────────────
// HELPER: Get display label for a user in messages/threads
// ────────────────────────────────────────────────────────────────
if ( ! function_exists('ih_get_display_label') ) {
    function ih_get_display_label( $user_id, $fallback_name = '' ) {
        $unique_id = get_user_meta( $user_id, 'ih_unique_id', true );
        if ( empty($unique_id) ) {
            $unique_id = 'ID#' . str_pad( $user_id, 6, '0', STR_PAD_LEFT );
        }
        if ( current_user_can('manage_options') ) {
            $name = $fallback_name ?: get_userdata($user_id)->display_name;
            return $name . ' (' . $unique_id . ')';
        }
        return $unique_id;
    }
}
 
if ( ! function_exists('ih_get_plain_id') ) {
    function ih_get_plain_id( $user_id ) {
        $uid = get_user_meta( $user_id, 'ih_unique_id', true );
        return $uid ?: ( 'ID#' . str_pad( $user_id, 6, '0', STR_PAD_LEFT ) );
    }
}
 
// ────────────────────────────────────────────────────────────────
// AJAX: Modal Register  (both logged-in and logged-out users)
// ────────────────────────────────────────────────────────────────
// [ih audit] Duplicate registration removed — ih_handle_modal_register() (registered
// earlier) is the authoritative ih_modal_register handler. The ih_modal_register_handler
// function below is now unhooked/dead and will be deleted in the refactor phase.
 
function ih_modal_register_handler() {
    // Verify nonce
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'im_register' ) ) {
        wp_send_json_error( 'Security check failed.' );
    }
 
    // Sanitize inputs
    $biz_role   = sanitize_text_field( $_POST['biz_role']       ?? '' );
    $company    = sanitize_text_field( $_POST['company_name']   ?? '' );
    $contact    = sanitize_text_field( $_POST['contact_name']   ?? '' );
    $job_title  = sanitize_text_field( $_POST['job_title']      ?? '' );
    $address    = sanitize_text_field( $_POST['address']        ?? '' );
    $city       = sanitize_text_field( $_POST['city']           ?? '' );
    $postcode   = sanitize_text_field( $_POST['postcode']       ?? '' );
    $office_num = sanitize_text_field( $_POST['office_number']  ?? '' );
    $website    = esc_url_raw(          $_POST['website']        ?? '' );
    $phone      = sanitize_text_field( $_POST['phone']          ?? '' );
    $email      = sanitize_email(      $_POST['email']          ?? '' );
    $email_conf = sanitize_email(      $_POST['email_confirm']  ?? '' );
    $password   =                      $_POST['password']        ?? '';
    $pass_conf  =                      $_POST['password_confirm'] ?? '';
 
    // Validate
    $errors = [];
    if ( empty($biz_role) )         $errors[] = 'Please select a business role.';
    if ( empty($company) )          $errors[] = 'Company name is required.';
    if ( empty($contact) )          $errors[] = 'Contact name is required.';
    if ( empty($email) )            $errors[] = 'Email address is required.';
    if ( ! is_email($email) )       $errors[] = 'Please enter a valid email address.';
    if ( $email !== $email_conf )   $errors[] = 'Email addresses do not match.';
    if ( empty($password) )         $errors[] = 'Password is required.';
    if ( strlen($password) < 8 )    $errors[] = 'Password must be at least 8 characters.';
    if ( $password !== $pass_conf ) $errors[] = 'Passwords do not match.';
    if ( email_exists($email) )     $errors[] = 'This email address is already registered.';
    if ( empty($_POST['agree_terms']) ) $errors[] = 'You must agree to the Terms & Conditions.';
    if ( empty($_POST['agree_fees']) )  $errors[] = 'You must understand that fees may apply.';
 
    if ( ! empty($errors) ) {
        wp_send_json_error( $errors );
    }
 
    // Create username from contact name
    $username      = sanitize_user( strtolower( str_replace(' ', '.', $contact) ) );
    $base_username = $username;
    $suffix        = 1;
    while ( username_exists($username) ) { $username = $base_username . $suffix; $suffix++; }
 
    $user_id = wp_create_user( $username, $password, $email );
    if ( is_wp_error($user_id) ) {
        wp_send_json_error( [ $user_id->get_error_message() ] );
    }
 
    // Avatar
    $user_info      = get_userdata($user_id);
    $default_avatar = get_avatar_url( $user_info->user_email, [ 'size' => 300 ] );
    update_user_meta( $user_id, 'ih_profile_image', esc_url_raw($default_avatar) );
 
    // Display name
    wp_update_user([
        'ID'           => $user_id,
        'display_name' => $contact,
        'first_name'   => explode(' ', $contact)[0] ?? $contact,
        'last_name'    => explode(' ', $contact)[1] ?? '',
    ]);
 
    // Role
    $user_obj = new WP_User($user_id);
    $role_map = [
        'Manufacturer'      => 'manufacturer',
        'Tool Owner'        => 'tool_owner',
        'Product Developer' => 'tool_owner',
        'Startup'           => 'subscriber',
        'Overseas Buyer'    => 'subscriber',
        'Other'             => 'subscriber',
    ];
    $user_obj->set_role( $role_map[$biz_role] ?? 'subscriber' );
 
    // Standard meta
    update_user_meta($user_id, 'business_role', $biz_role);
    update_user_meta($user_id, 'company_name',  $company);
    update_user_meta($user_id, 'job_title',     $job_title);
    update_user_meta($user_id, 'address',       $address);
    update_user_meta($user_id, 'city',          $city);
    update_user_meta($user_id, 'postcode',      $postcode);
    update_user_meta($user_id, 'office_number', $office_num);
    update_user_meta($user_id, 'website',       $website);
    update_user_meta($user_id, 'phone',         $phone);
 
    // ── Generate & save UNIQUE ID ──────────────────────────────
    $ih_unique_id = ih_generate_unique_id( $contact, $city );
    update_user_meta( $user_id, 'ih_unique_id', $ih_unique_id );
 
    // Profile table
    global $wpdb;
    $wpdb->insert( "{$wpdb->prefix}ih_profiles", [
        'user_id'        => $user_id,
        'business_role'  => $biz_role,
        'company_name'   => $company,
        'job_title'      => $job_title,
        'phone'          => $phone,
        'address'        => $address,
        'city'           => $city,
        'postcode'       => $postcode,
        'website'        => $website,
        'whatsapp'       => $phone,
        'account_status' => 'active',
        'unique_id'      => $ih_unique_id,   // ← store ID in profile row too
    ]);
 
    // Auth cookie
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);
 
    // Welcome email — includes their new unique ID
    wp_mail(
        $email,
        'Welcome to Injection Moulding Platform!',
        "Hi {$contact},\n\n" .
        "Your account has been created successfully!\n\n" .
        "=== YOUR PLATFORM ID ===\n" .
        "  {$ih_unique_id}\n" .
        "========================\n\n" .
        "Keep this ID safe — others on the platform will use it to identify and message you.\n\n" .
        "Login: " . wp_login_url() . "\n\n" .
        "Regards,\nInjection Moulding Team"
    );
 
    // Determine redirect
    $redirect = in_array('administrator', (array)(new WP_User($user_id))->roles)
        ? admin_url('admin.php?page=ih-dashboard')
        : admin_url('admin.php?page=ih-user-dashboard');
 
    wp_send_json_success( [ 'redirect' => $redirect, 'unique_id' => $ih_unique_id ] );
}

/**
 * ═══════════════════════════════════════════════════
 * CONTACT REQUEST — PASTE INTO functions.php
 * Includes OWNER ID in message + proper listing_type
 * ═══════════════════════════════════════════════════
 */
 
// ── Status check (both old + new type formats) ──────────────────
if ( ! function_exists('ih_listing_contact_status') ) {
    function ih_listing_contact_status( $viewer_id, $listing_id, $type ) {
        if ( ! $viewer_id || ! $listing_id ) return 'None';
        global $wpdb;
        foreach ( [ $type.'_contact', 'ih_contact_'.$type ] as $lt ) {
            $s = $wpdb->get_var( $wpdb->prepare(
                "SELECT status FROM {$wpdb->prefix}ih_requests
                 WHERE user_id=%d AND listing_id=%d AND listing_type=%s
                 ORDER BY id DESC LIMIT 1",
                $viewer_id, $listing_id, $lt
            ));
            if ($s) return $s;
        }
        // Fallback: any approved request for owner's listings
        if ( $listing_id ) {
            $tbl  = $type==='machine' ? $wpdb->prefix.'ih_machines' : $wpdb->prefix.'ih_tools';
            $oid  = (int)$wpdb->get_var($wpdb->prepare("SELECT owner_id FROM {$tbl} WHERE id=%d",$listing_id));
            if ($oid) {
                $fb = $wpdb->get_var($wpdb->prepare(
                    "SELECT r.status FROM {$wpdb->prefix}ih_requests r
                     INNER JOIN {$tbl} t ON t.id=r.listing_id AND t.owner_id=%d
                     WHERE r.user_id=%d AND r.listing_type IN (%s,%s) AND r.status='Approved'
                     LIMIT 1",
                    $oid, $viewer_id,
                    $type.'_contact', 'ih_contact_'.$type
                ));
                if ($fb) return $fb;
            }
        }
        return 'None';
    }
}
 
// ── Owner contact data ───────────────────────────────────────────
if ( ! function_exists('ih_listing_owner_data') ) {
    function ih_listing_owner_data( $owner_id ) {
        if ( ! $owner_id ) return [];
        $user = get_userdata($owner_id);
        if ( ! $user ) return [];
        $get = function($keys) use ($owner_id) {
            foreach ($keys as $k) {
                $v = get_user_meta($owner_id,$k,true);
                if ($v!==''&&$v!==null) return (string)$v;
            }
            return '';
        };
        return [
            'uid'      => get_user_meta($owner_id,'ih_unique_id',true)?:'ID#'.str_pad($owner_id,6,'0',STR_PAD_LEFT),
            'name'     => $user->display_name,
            'email'    => $user->user_email,
            'phone'    => $get(['phone','office_number']),
            'whatsapp' => $get(['whatsapp','whatsapp_number','phone']),
            'company'  => $get(['company_name','company']),
            'website'  => $get(['website']),
            'city'     => $get(['city','location']),
        ];
    }
}
 
// ── AJAX: User sends contact request ────────────────────────────
add_action('wp_ajax_ih_listing_contact_request', 'ih_listing_contact_request_handler');
 
function ih_listing_contact_request_handler() {
    if (!is_user_logged_in())
        wp_send_json_error('You must be logged in.');
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ih_contact_request'))
        wp_send_json_error('Security check failed.');

    $requester_id = get_current_user_id();
    $listing_id   = (int)($_POST['listing_id']   ?? 0);
    $listing_type = sanitize_text_field($_POST['listing_type'] ?? ''); // 'machine' or 'tool'
    $owner_id     = (int)($_POST['owner_id']      ?? 0);

    $res = ih_persist_listing_contact_request($requester_id, $listing_id, $listing_type, $owner_id);
    if (is_wp_error($res)) {
        wp_send_json_error($res->get_error_message());
    }
    wp_send_json_success($res);
}

/**
 * Shared core for the "request owner contact" two-gate flow.
 * Inserts a Pending row into ih_requests (type ih_contact_{machine|tool}), opens/
 * updates the admin thread + notification — i.e. the EXISTING approval pipeline.
 * Reused by both the legacy ih_listing_contact_request endpoint and the new
 * ih_request_listing_details endpoint so the approval flow has one source of truth.
 *
 * @return array|WP_Error success payload (passed to wp_send_json_success), or error.
 */
function ih_persist_listing_contact_request($requester_id, $listing_id, $listing_type, $owner_id = 0, $note = '') {
    global $wpdb;

    $requester_id = (int) $requester_id;
    $listing_id   = (int) $listing_id;
    $listing_type = sanitize_key($listing_type);
    if (!in_array($listing_type, array('machine', 'tool'), true)) {
        return new WP_Error('ih_bad_type', 'Invalid listing type.');
    }
    if (!$listing_id) {
        return new WP_Error('ih_missing', 'Missing required data.');
    }

    // Resolve owner server-side when not supplied / untrusted.
    $owner_tbl = $listing_type === 'machine' ? $wpdb->prefix . 'ih_machines' : $wpdb->prefix . 'ih_tools';
    if (!$owner_id) {
        $owner_id = (int) $wpdb->get_var($wpdb->prepare("SELECT owner_id FROM {$owner_tbl} WHERE id=%d", $listing_id));
    }
    $owner_id = (int) $owner_id;

    if ($requester_id && $owner_id && $requester_id === $owner_id) {
        return new WP_Error('ih_own', 'This is your own listing.');
    }

    // Prefixed type for ih_requests (prevents bulk-approve by existing system)
    $request_type = 'ih_contact_' . $listing_type; // ih_contact_machine / ih_contact_tool
 
    // Check existing non-rejected request
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ih_requests
         WHERE user_id=%d AND listing_id=%d AND listing_type IN (%s,%s)
         ORDER BY id DESC LIMIT 1",
        $requester_id, $listing_id,
        $listing_type . '_contact', // old format compat
        $request_type               // new format
    ), ARRAY_A);
 
    if ($existing && strtolower($existing['status']) !== 'rejected') {
        return [
            'status'   => $existing['status'],
            'message'  => 'Request already ' . strtolower($existing['status']) . '.',
            'existing' => true,
        ];
    }
 
    // Listing info
    $tbl           = $listing_type === 'machine' ? $wpdb->prefix . 'ih_machines' : $wpdb->prefix . 'ih_tools';
    $listing       = $wpdb->get_row($wpdb->prepare("SELECT title FROM {$tbl} WHERE id=%d", $listing_id), ARRAY_A);
    $listing_title = $listing['title'] ?? "Listing #{$listing_id}";
    $listing_ref   = ($listing_type === 'machine' ? 'MCH-' : 'TL-') . str_pad($listing_id, 5, '0', STR_PAD_LEFT);
    $requester     = get_userdata($requester_id);
    $requester_uid = get_user_meta($requester_id, 'ih_unique_id', true)
                     ?: 'ID#' . str_pad($requester_id, 6, '0', STR_PAD_LEFT);
 
    // Owner info
    $owner      = get_userdata($owner_id);
    $owner_uid  = get_user_meta($owner_id, 'ih_unique_id', true)
                  ?: 'ID#' . str_pad($owner_id, 6, '0', STR_PAD_LEFT);
    $owner_name = $owner ? $owner->display_name : 'Unknown';
 
    // Insert request row (uses prefixed type)
    $wpdb->insert($wpdb->prefix . 'ih_requests', [
        'user_id'      => $requester_id,
        'listing_id'   => $listing_id,
        'listing_type' => $request_type,       // ← prefixed: ih_contact_machine
        'request_date' => current_time('Y-m-d'),
        'status'       => 'Pending',
    ], ['%d', '%d', '%s', '%s', '%s']);
 
    $request_id = (int)$wpdb->insert_id;
    if (function_exists('ih_add_notification')) {
    $requester_data = get_userdata($requester_id);
    $req_name = $requester_data ? $requester_data->display_name : 'A user';
    ih_add_notification(
        'request',
        '📋 New Contact Request',
        $req_name . ' wants access to: ' . $listing_title,
        admin_url('admin.php?page=ih-messages')
    );
}
 
    // Optional free-text note from the requester (sanitised). Additive — legacy callers pass ''.
    $note = trim( wp_strip_all_tags( (string) $note ) );
    $note = mb_substr( $note, 0, 600 );

    // Professional message body
    $message = "📋 Contact Access Request\n"
        . "Listing  :  {$listing_title} ({$listing_ref})\n"
        . "Type     :  " . ucfirst($listing_type) . "\n"
        . "Requester    :  {$requester->display_name}\n"
        . "Requester ID :  {$requester_uid}\n"
        . "Email        :  {$requester->user_email}\n"
        . "Owner        :  {$owner_name}\n"
        . "Owner ID     :  {$owner_uid}\n"
        . ( $note !== '' ? "Message      :  {$note}\n" : '' )
        . "Use the Approve or Reject button to respond.\n"
        . "<!-- IH_REQUEST_DATA:" . json_encode([
            'id'           => $request_id,
            'requester_id' => $requester_id,
            'listing_id'   => $listing_id,
            'type'         => $request_type,
            'uid'          => $requester_uid,
            'owner_id'     => $owner_id,
            'owner_uid'    => $owner_uid,
            'owner_name'   => $owner_name,
        ]) . " -->";
 
    // ── FIX: Thread uses SIMPLE listing_type ('machine'/'tool') ──
    // so ih_db_threads() can find it in the admin Messages panel
    $thread_listing_type = $listing_type; // ← 'machine' or 'tool', NOT 'ih_contact_machine'
 
    $thread_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ih_threads WHERE user_id=%d ORDER BY id DESC LIMIT 1",
        $requester_id
    ));
 
    if (!$thread_id) {
        $wpdb->insert($wpdb->prefix . 'ih_threads', [
            'user_id'      => $requester_id,
            'listing_id'   => $listing_id,
            'listing_type' => $thread_listing_type,   // ← simple type
            'last_message' => $message,
            'last_time'    => current_time('mysql', true),
            'unread'       => 1,
        ], ['%d', '%d', '%s', '%s', '%s', '%d']);
        $thread_id = (int)$wpdb->insert_id;
    } else {
        $wpdb->update($wpdb->prefix . 'ih_threads', [
            'listing_id'   => $listing_id,
            'listing_type' => $thread_listing_type,   // ← simple type
            'last_message' => $message,
            'last_time'    => current_time('mysql', true),
            'unread'       => 1,
        ], ['id' => $thread_id],
           ['%d', '%s', '%s', '%s', '%d'],
           ['%d']);
    }
 
    if ($thread_id) {
        $wpdb->insert($wpdb->prefix . 'ih_chats', [
            'thread_id' => $thread_id,
            'from_me'   => 0,
            'message'   => $message,
            'sent_at'   => current_time('mysql', true),
        ], ['%d', '%d', '%s', '%s']);
    }
 
    return [
        'status'     => 'Pending',
        'request_id' => $request_id,
        'message'    => 'Request sent · awaiting approval.',
    ];
}

/**
 * ───────────────────────────────────────────────────────────────────
 * NEW (public detail page): Request listing details  + Save requirement
 * Both use check_ajax_referer('ih_nonce','nonce') + capability checks and
 * REUSE the existing contact-request/approval pipeline (ih_persist_listing_contact_request).
 * ───────────────────────────────────────────────────────────────────
 */
add_action('wp_ajax_ih_request_listing_details',        'ih_request_listing_details_handler');
add_action('wp_ajax_nopriv_ih_request_listing_details', 'ih_request_listing_details_nopriv');

function ih_request_listing_details_nopriv() {
    // Anonymous: don't block browsing — just prompt to log in (tier 1 → action).
    $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;
    wp_send_json_error(array(
        'message'        => 'Please log in to request details.',
        'login_required' => true,
        'login_url'      => function_exists('im_auth_url')
            ? add_query_arg('redirect_to', rawurlencode(home_url('/machine/?id=' . $listing_id)), im_auth_url('login'))
            : wp_login_url(home_url('/machine/?id=' . $listing_id)),
    ));
}

function ih_request_listing_details_handler() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in to request details.', 'login_required' => true));
    }
    check_ajax_referer('ih_nonce', 'nonce');
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    $requester_id = get_current_user_id();
    $listing_id   = absint($_POST['listing_id'] ?? 0);
    $listing_type = sanitize_key($_POST['listing_type'] ?? 'machine');
    if (!in_array($listing_type, array('machine', 'tool'), true)) {
        $listing_type = 'machine';
    }

    // Optional requester message (the modal "Your message" field).
    $note = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

    // Owner is resolved server-side inside the core (client value ignored).
    $res = ih_persist_listing_contact_request($requester_id, $listing_id, $listing_type, 0, $note);
    if (is_wp_error($res)) {
        wp_send_json_error(array('message' => $res->get_error_message()));
    }
    wp_send_json_success($res);
}

/**
 * Save the viewer's job/requirement profile to user meta (ih_requirement_profile).
 * Consumed client-side by matchScore()/fits() — no listing data changes.
 */
add_action('wp_ajax_ih_save_requirement', 'ih_save_requirement_handler');
function ih_save_requirement_handler() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in to save your job profile.', 'login_required' => true));
    }
    check_ajax_referer('ih_nonce', 'nonce');
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    // Original profile fields + additive radar axes (screw/pressure/throughput/automation).
    // New keys are optional and don't change the existing contract for legacy callers.
    $numeric = array('tonnage', 'shot', 'volume', 'mouldL', 'mouldW', 'mouldH', 'partWeight', 'screw', 'pressure', 'throughput', 'automation',
        // Clamp-tonnage job inputs: projected area (cm²) + cavity pressure (bar).
        'projectedArea', 'cavityPressure');
    $text    = array('material', 'location');
    $profile = array();
    foreach ($numeric as $f) {
        $v = isset($_POST[$f]) ? preg_replace('/[^0-9.\-]/', '', wp_unslash($_POST[$f])) : '';
        $profile[$f] = ($v === '') ? '' : (string) floatval($v);
    }
    foreach ($text as $f) {
        $profile[$f] = isset($_POST[$f]) ? sanitize_text_field(wp_unslash($_POST[$f])) : '';
    }

    update_user_meta(get_current_user_id(), 'ih_requirement_profile', $profile);
    wp_send_json_success(array('requirement' => $profile));
}

/**
 * ADMIN-ONLY approve or deny a contact request FROM THE PUBLIC DETAIL PAGE.
 * Policy: only administrators may accept/deny contact requests. Listing owners can
 * SEE that a request is pending (for verification) but cannot act on it — the
 * approve/deny buttons are hidden for owners AND this endpoint enforces the
 * `manage_options` capability server-side so a non-admin (including the listing
 * owner) cannot approve/deny via a forged request. Reuses the same status sync +
 * thread/approval helpers so there is one approval pipeline.
 */
add_action('wp_ajax_ih_owner_request_action', 'ih_owner_request_action_handler');
function ih_owner_request_action_handler() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in.'));
    }
    check_ajax_referer('ih_nonce', 'nonce');

    // Admin-only: owners may verify (see) pending requests but never approve/deny.
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Only an administrator can approve or deny requests.'), 403);
    }

    global $wpdb;
    $request_id = absint($_POST['request_id'] ?? 0);
    $do         = sanitize_key($_POST['do'] ?? '');
    if (!$request_id || !in_array($do, array('approve', 'deny'), true)) {
        wp_send_json_error(array('message' => 'Invalid request.'));
    }

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id, user_id, listing_id, listing_type, status FROM {$wpdb->prefix}ih_requests WHERE id=%d",
        $request_id
    ), ARRAY_A);
    if (!$row) {
        wp_send_json_error(array('message' => 'Request not found.'));
    }

    // Resolve the simple listing type (strip ih_contact_ / _contact prefixes/suffixes).
    $lt = strtolower((string) $row['listing_type']);
    $simple_type = (strpos($lt, 'tool') !== false) ? 'tool' : 'machine';

    $status = ($do === 'approve') ? 'Approved' : 'Rejected';
    $wpdb->update($wpdb->prefix . 'ih_requests', array('status' => $status), array('id' => $request_id), array('%s'), array('%d'));

    if (function_exists('ih_sync_listing_status_from_request')) {
        $sync = $row;
        $sync['status'] = $status;
        ih_sync_listing_status_from_request($sync);
    }
    if ($status === 'Approved') {
        $req_user = (int) $row['user_id'];
        if ($req_user) {
            if (function_exists('ih_ensure_thread_for_request')) {
                ih_ensure_thread_for_request($req_user, (int) $row['listing_id'], $simple_type);
            }
            if (function_exists('ih_send_user_approval_whatsapp_email')) {
                ih_send_user_approval_whatsapp_email($req_user);
            }
        }
    }
    if (function_exists('ih_log_activity')) {
        ih_log_activity('request', 'Request #' . $request_id . ' ' . $status . ' (owner/admin detail page)', array('request_id' => $request_id, 'status' => $status));
    }
    delete_transient('ih_admin_notifications_v1');

    wp_send_json_success(array('status' => $status, 'request_id' => $request_id));
}

/**
 * ═══════════════════════════════════════════════════════
 * ONE-TIME DATABASE FIX
 * Run this ONCE to fix existing threads that have wrong listing_type.
 * Add to functions.php temporarily, visit any page, then remove.
 * ═══════════════════════════════════════════════════════
 */
function ih_fix_existing_contact_threads() {
    // Run only once
    if (get_option('ih_contact_thread_fix_done')) return;
 
    global $wpdb;
 
    // Fix threads that have listing_type = 'ih_contact_machine' → 'machine'
    $wpdb->query("
        UPDATE {$wpdb->prefix}ih_threads
        SET listing_type = 'machine'
        WHERE listing_type = 'ih_contact_machine'
    ");
 
    // Fix threads that have listing_type = 'ih_contact_tool' → 'tool'
    $wpdb->query("
        UPDATE {$wpdb->prefix}ih_threads
        SET listing_type = 'tool'
        WHERE listing_type = 'ih_contact_tool'
    ");
 
    // Fix old 'machine_contact' / 'tool_contact' as well
    $wpdb->query("
        UPDATE {$wpdb->prefix}ih_threads
        SET listing_type = 'machine'
        WHERE listing_type = 'machine_contact'
    ");
 
    $wpdb->query("
        UPDATE {$wpdb->prefix}ih_threads
        SET listing_type = 'tool'
        WHERE listing_type = 'tool_contact'
    ");
 
    update_option('ih_contact_thread_fix_done', 1);
}
add_action('init', 'ih_fix_existing_contact_threads');
add_action('wp_ajax_ih_get_unified_stats', 'ih_get_unified_stats_handler');
function ih_get_unified_stats_handler() {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ih_nonce')) wp_send_json_error('Nonce failed');
 
    global $wpdb;
    $tbl = $wpdb->prefix . 'ih_requests';
 
    // Count by status — all request types (matches dashboard query)
    $rows = $wpdb->get_results(
        "SELECT LOWER(status) as status, COUNT(*) as cnt
         FROM {$tbl}
         GROUP BY LOWER(status)",
        ARRAY_A
    );
 
    $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'completed' => 0];
    foreach ($rows as $r) {
        $s = strtolower(trim($r['status']));
        if (isset($stats[$s])) $stats[$s] = (int)$r['cnt'];
    }
    $stats['total'] = array_sum($stats);
 
    wp_send_json_success($stats);
}
// [ih audit] Duplicate registration removed — the plugin's ih_delete_machine handler
// (admin-gated) registers first and is authoritative. The ih_delete_machine_handler()
// below is now unhooked; its owner-delete + related-row cleanup logic is preserved here
// as reference and should be merged into the plugin handler during Phase 2.
function ih_delete_machine_handler() {
    // Accepts BOTH nonces for compatibility
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    $valid = wp_verify_nonce($nonce, 'machine_action')
          || wp_verify_nonce($nonce, 'ih_nonce');
 
    if (!$valid) wp_send_json_error(['message' => 'Security check failed. Nonce: ' . $nonce]);
    if (!is_user_logged_in()) wp_send_json_error(['message' => 'Not logged in.']);
 
    global $wpdb;
    $machine_id = intval($_POST['id'] ?? 0);
    if (!$machine_id) wp_send_json_error(['message' => 'Invalid ID.']);
 
    $is_admin    = current_user_can('administrator');
    $current_uid = get_current_user_id();
 
    if (!$is_admin) {
        $owner_id = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT owner_id FROM {$wpdb->prefix}ih_machines WHERE id=%d", $machine_id
        ));
        if ($owner_id !== $current_uid) wp_send_json_error(['message' => 'Permission denied.']);
    }
 
    $deleted = $wpdb->delete($wpdb->prefix . 'ih_machines', ['id' => $machine_id], ['%d']);
 
    if ($deleted) {
        // Clean up related data
        $wpdb->delete($wpdb->prefix . 'ih_requests', ['listing_id' => $machine_id, 'listing_type' => 'machine'],         ['%d','%s']);
        $wpdb->delete($wpdb->prefix . 'ih_requests', ['listing_id' => $machine_id, 'listing_type' => 'ih_contact_machine'],['%d','%s']);
        $wpdb->delete($wpdb->prefix . 'ih_threads',  ['listing_id' => $machine_id, 'listing_type' => 'machine'],         ['%d','%s']);
        wp_send_json_success(['message' => 'Deleted successfully.']);
    } else {
        wp_send_json_error(['message' => 'Could not delete. DB error: ' . $wpdb->last_error]);
    }
}

if ( ! defined( 'IH_VERSION' ) ) {
add_action('wp_ajax_ih_global_search', 'ih_global_search_handler');
}
function ih_global_search_handler() {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    check_ajax_referer('ih_nonce', 'nonce');
 
    global $wpdb;
    $q       = sanitize_text_field(trim($_POST['q'] ?? ''));
    $results = [];
 
    if (strlen($q) < 2) wp_send_json_success([]);
 
    $like = '%' . $wpdb->esc_like($q) . '%';
 
    // ── Users ──────────────────────────────────────────
    $users = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name, u.user_email,
                m.meta_value AS unique_id
         FROM {$wpdb->users} u
         LEFT JOIN {$wpdb->usermeta} m ON m.user_id=u.ID AND m.meta_key='ih_unique_id'
         WHERE u.display_name LIKE %s
            OR u.user_email   LIKE %s
            OR m.meta_value   LIKE %s
         LIMIT 5",
        $like, $like, $like
    ), ARRAY_A);
 
    foreach ($users as $u) {
        $results[] = [
            'type'    => 'user',
            'icon'    => '👤',
            'title'   => $u['display_name'],
            'sub'     => ($u['unique_id'] ?: 'ID#'.$u['ID']) . ' · ' . $u['user_email'],
            'url'     => admin_url('admin.php?page=ih-users&view=' . $u['ID']),
        ];
    }
 
    // ── Machines ───────────────────────────────────────
    $machines = $wpdb->get_results($wpdb->prepare(
        "SELECT id, title, machine_type, location, owner_id
         FROM {$wpdb->prefix}ih_machines
         WHERE title        LIKE %s
            OR machine_type LIKE %s
            OR location     LIKE %s
            OR brand        LIKE %s
         LIMIT 5",
        $like, $like, $like, $like
    ), ARRAY_A);
 
    foreach ($machines as $m) {
        $ref = 'MCH-' . str_pad($m['id'], 5, '0', STR_PAD_LEFT);
        $results[] = [
            'type'  => 'machine',
            'icon'  => '⚙️',
            'title' => $m['title'],
            'sub'   => $ref . ($m['machine_type'] ? ' · '.$m['machine_type'] : '') . ($m['location'] ? ' · '.$m['location'] : ''),
            'url'   => admin_url('admin.php?page=ih-machine-detail&machine_id=' . $m['id']),
        ];
    }
 
    // ── Tools ──────────────────────────────────────────
    $tools = $wpdb->get_results($wpdb->prepare(
        "SELECT id, title, mould_type, location
         FROM {$wpdb->prefix}ih_tools
         WHERE title      LIKE %s
            OR mould_type LIKE %s
            OR location   LIKE %s
         LIMIT 5",
        $like, $like, $like
    ), ARRAY_A);
 
    foreach ($tools as $t) {
        $ref = 'TL-' . str_pad($t['id'], 5, '0', STR_PAD_LEFT);
        $results[] = [
            'type'  => 'tool',
            'icon'  => '🔧',
            'title' => $t['title'],
            'sub'   => $ref . ($t['mould_type'] ? ' · '.$t['mould_type'] : '') . ($t['location'] ? ' · '.$t['location'] : ''),
            'url'   => admin_url('admin.php?page=ih-tool-detail&tool_id=' . $t['id']),
        ];
    }
 
    // ── Requests / Messages ────────────────────────────
    $reqs = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, email, listing_type, status
         FROM {$wpdb->prefix}ih_requests
         WHERE name  LIKE %s
            OR email LIKE %s
         LIMIT 4",
        $like, $like
    ), ARRAY_A);
 
    foreach ($reqs as $r) {
        $results[] = [
            'type'  => 'request',
            'icon'  => '📋',
            'title' => $r['name'] ?: 'Request #'.$r['id'],
            'sub'   => 'Request #'.$r['id'].' · '.($r['listing_type'] ?: '').' · '.($r['status'] ?: 'Pending'),
            'url'   => admin_url('admin.php?page=ih-messages'),
        ];
    }
 
    wp_send_json_success($results);
}

add_action('wp_ajax_ih_search_requests', 'ih_search_requests_handler');
function ih_search_requests_handler() {
    $nonce = sanitize_text_field($_POST['nonce'] ?? '');
    $valid = wp_verify_nonce($nonce, 'ih_nonce')
          || wp_verify_nonce($nonce, 'machine_action');
    if (!$valid) wp_send_json_error(['msg' => 'Nonce failed']);
    if (!current_user_can('manage_options')) wp_send_json_error(['msg' => 'Unauthorized']);
 
    global $wpdb;
    $q      = sanitize_text_field(trim($_POST['q']      ?? ''));
    $status = sanitize_text_field(trim($_POST['status'] ?? 'all'));
    $like   = '%' . $wpdb->esc_like($q) . '%';
 
    // Base query — JOIN users table to get display_name + email
    $sql = "SELECT r.id, r.user_id, r.name, r.email, r.phone,
                   r.location, r.listing_type, r.status, r.request_date,
                   u.display_name, u.user_email AS user_email_actual
            FROM {$wpdb->prefix}ih_requests r
            LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id
            WHERE 1=1";
 
    $params = [];
 
    if ($q !== '') {
        $sql .= " AND (
            r.name         LIKE %s OR
            r.email        LIKE %s OR
            r.phone        LIKE %s OR
            r.location     LIKE %s OR
            r.listing_type LIKE %s OR
            u.display_name LIKE %s OR
            u.user_email   LIKE %s
        )";
        $params = [$like, $like, $like, $like, $like, $like, $like];
    }
 
    if ($status !== 'all' && $status !== '') {
        $sql    .= " AND LOWER(TRIM(r.status)) = %s";
        $params[] = strtolower($status);
    }
 
    $sql .= " ORDER BY r.id DESC LIMIT 100";
 
    $rows = empty($params)
        ? $wpdb->get_results($sql, ARRAY_A)
        : $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
 
    // Name/email fallback from users table
    if ($rows) {
        foreach ($rows as &$row) {
            if (empty($row['name'])  && !empty($row['display_name']))      $row['name']  = $row['display_name'];
            if (empty($row['email']) && !empty($row['user_email_actual'])) $row['email'] = $row['user_email_actual'];
        }
        unset($row);
    }
 
    wp_send_json_success($rows ?: []);
}
add_action('wp_ajax_ih_get_thread_meta', 'ih_get_thread_meta_handler');
function ih_get_thread_meta_handler() {
    check_ajax_referer('ih_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
 
    global $wpdb;
    $uid = intval($_POST['user_id'] ?? 0);
    if (!$uid) wp_send_json_error();
 
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT r.status, r.listing_type
         FROM {$wpdb->prefix}ih_requests r
         WHERE r.user_id = %d
         ORDER BY r.id DESC LIMIT 1",
        $uid
    ), ARRAY_A);
 
    wp_send_json_success($row ?: ['status' => '', 'listing_type' => '']);
}
function ih_create_notifications_table() {
    global $wpdb;
    if (get_option('ih_notifications_table_done')) return;
    $table = $wpdb->prefix . 'ih_notifications';
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$table} (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        type        VARCHAR(50)  NOT NULL DEFAULT 'info',
        title       VARCHAR(200) NOT NULL DEFAULT '',
        body        VARCHAR(500) NOT NULL DEFAULT '',
        link        VARCHAR(500) NOT NULL DEFAULT '',
        is_read     TINYINT(1)   NOT NULL DEFAULT 0,
        created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    update_option('ih_notifications_table_done', 1);
}
add_action('init', 'ih_create_notifications_table');

/* ─────────────────────────────────────────
   ih_tools — extended columns migration (idempotent)
   The base {$wpdb->prefix}ih_tools table is created by the Add-Tool plugin
   (admin slug `ih-user-add-tool`), which is NOT part of this theme. This
   routine adds the technical-listing columns used by add-tool-form.php
   (input) and tool-detail-technical.php (display) to BOTH fresh and
   existing databases. It checks each column via SHOW COLUMNS and only
   ALTERs the missing ones, guarded by a version option, so it runs once
   and is safe to re-run / re-deploy.
───────────────────────────────────────── */
function ih_tools_upgrade_schema() {
    $target_ver = 'tools-ext-2026-06-1'; // bump this if more columns are added later
    if ( get_option( 'ih_tools_schema_ver' ) === $target_ver ) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ih_tools';

    // Base table is created by the ih-user-add-tool plugin. If it doesn't
    // exist yet, bail WITHOUT marking done so we retry on a later admin load.
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
        return;
    }

    $existing = array_map( 'strtolower', (array) $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" ) );

    // name => column definition (matches the SQL types specced for the new fields)
    $columns = array(
        'surface_finish'        => 'VARCHAR(120) NULL',
        'draft_angle'           => 'VARCHAR(60) NULL',
        'tool_life'             => 'VARCHAR(60) NULL',
        'runner_type'           => 'VARCHAR(60) NULL',
        'gate_type'             => 'VARCHAR(60) NULL',
        'construction'          => 'VARCHAR(80) NULL',
        'mould_weight'          => 'VARCHAR(60) NULL',
        'mould_dimensions'      => 'VARCHAR(120) NULL',
        'required_qty'          => 'VARCHAR(60) NULL',
        'packaging'             => 'VARCHAR(120) NULL',
        'material_supplied'     => 'VARCHAR(60) NULL',
        'clamp_force'           => 'VARCHAR(60) NULL',
        'shot_weight'           => 'VARCHAR(60) NULL',
        'tie_bar'               => 'VARCHAR(60) NULL',
        'opening_stroke'        => 'VARCHAR(60) NULL',
        'hot_runner_controller' => 'VARCHAR(60) NULL',
        'hot_runner_zones'      => 'VARCHAR(30) NULL',
        'iml'                   => 'VARCHAR(8) NULL',
        'automation'            => 'VARCHAR(8) NULL',
        'materials'             => 'TEXT NULL',
    );

    foreach ( $columns as $name => $definition ) {
        if ( ! in_array( strtolower( $name ), $existing, true ) ) {
            // $name and $definition are hard-coded constants (never user input),
            // so direct interpolation here is safe; identifiers can't be bound.
            $wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `{$name}` {$definition}" );
        }
    }

    update_option( 'ih_tools_schema_ver', $target_ver );
}
add_action( 'admin_init', 'ih_tools_upgrade_schema' );
 
/* ─────────────────────────────────────────
   Helper: notification insert karo
───────────────────────────────────────── */
function ih_add_notification($type, $title, $body, $link = '') {
    global $wpdb;
    $wpdb->insert($wpdb->prefix . 'ih_notifications', [
        'type'  => sanitize_text_field($type),
        'title' => sanitize_text_field($title),
        'body'  => sanitize_text_field($body),
        'link'  => esc_url_raw($link),
    ], ['%s','%s','%s','%s']);
}
 
/* ─────────────────────────────────────────
   Triggers — existing hooks pe latch karo
───────────────────────────────────────── */
 
// 1. New contact request
// [ih audit] Removed empty placeholder handler for ih_listing_contact_request (it did
// nothing). The authoritative handler is ih_listing_contact_request_handler() above.
add_filter('ih_after_contact_request_insert', function($data) {
    ih_add_notification(
        'request',
        '📋 New Contact Request',
        ($data['requester_name'] ?? 'A user') . ' wants access to ' . ($data['listing_title'] ?? 'a listing'),
        admin_url('admin.php?page=ih-messages')
    );
    return $data;
});
 
// 2. New machine added — hook onto ih_machines insert
add_action('ih_after_machine_insert', function($machine_id, $title, $owner_name) {
    ih_add_notification(
        'machine',
        '⚙️ New Machine Listed',
        $owner_name . ' added: ' . $title,
        admin_url('admin.php?page=ih-machine-detail&machine_id=' . $machine_id)
    );
}, 10, 3);
 
// 3. New tool added
add_action('ih_after_tool_insert', function($tool_id, $title, $owner_name) {
    ih_add_notification(
        'tool',
        '🔧 New Tool Listed',
        $owner_name . ' added: ' . $title,
        admin_url('admin.php?page=ih-tool-detail&tool_id=' . $tool_id)
    );
}, 10, 3);
 
// 4. New chat message — hook onto ih_send_message
add_action('ih_after_chat_insert', function($thread_id, $user_id, $message) {
    $user = get_userdata($user_id);
    $name = $user ? $user->display_name : 'A user';
    $uid  = get_user_meta($user_id, 'ih_unique_id', true) ?: ('UID-' . $user_id);
    if (function_exists('ih_add_notification')) {
    $sender = get_userdata($user_id ?? get_current_user_id());
    $uid    = $sender ? (get_user_meta($sender->ID, 'ih_unique_id', true) ?: $sender->display_name) : 'User';
    ih_add_notification(
        'message',
        '💬 New Message',
        $uid . ': ' . mb_substr(strip_tags($message), 0, 80),
        admin_url('admin.php?page=ih-messages&user_id=' . $user_id)
    );}
}, 10, 3);
 
// 5. New user registered
add_action('user_register', function($user_id) {
    if (defined('DOING_AJAX') && DOING_AJAX) {
        $user = get_userdata($user_id);
        if ($user) {
            ih_add_notification(
                'user',
                '👤 New User Registered',
                $user->display_name . ' (' . $user->user_email . ')',
                admin_url('admin.php?page=ih-users&view=' . $user_id)
            );
        }
    }
}, 20);

// Fire admin notification when a non-admin sends a chat message (plugin saves at priority 10).
add_action( 'wp_ajax_ih_send_message', function () {
    $user_id = get_current_user_id();
    $message = sanitize_textarea_field( $_POST['message'] ?? '' );
    if ( $user_id && $message && ! current_user_can( 'administrator' ) ) {
        global $wpdb;
        $thread_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ih_threads WHERE user_id=%d ORDER BY id DESC LIMIT 1",
            $user_id
        ) );
        do_action( 'ih_after_chat_insert', $thread_id, $user_id, $message );
    }
}, 5 );

/* ─────────────────────────────────────────
   AJAX: admin notification endpoints (theme fallback only when plugin inactive)
───────────────────────────────────────── */
if ( ! defined( 'IH_VERSION' ) ) {
add_action('wp_ajax_ih_get_notifications', 'ih_get_notifications_handler');
function ih_get_notifications_handler() {
    check_ajax_referer('ih_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
 
    global $wpdb;
    $table = $wpdb->prefix . 'ih_notifications';
 
    $rows = $wpdb->get_results(
        "SELECT * FROM {$table} ORDER BY id DESC LIMIT 30",
        ARRAY_A
    );
    $unread = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_read=0");
 
    wp_send_json_success(['notifications' => $rows ?: [], 'unread' => $unread]);
}
 
/* ─────────────────────────────────────────
   AJAX: mark all as read
───────────────────────────────────────── */
add_action('wp_ajax_ih_mark_notifications_read', 'ih_mark_notifications_read_handler');
function ih_mark_notifications_read_handler() {
    check_ajax_referer('ih_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    global $wpdb;
    $wpdb->query("UPDATE {$wpdb->prefix}ih_notifications SET is_read=1 WHERE is_read=0");
    wp_send_json_success();
}
 
/* ─────────────────────────────────────────
   AJAX: delete single notification
───────────────────────────────────────── */
add_action('wp_ajax_ih_delete_notification', 'ih_delete_notification_handler');
function ih_delete_notification_handler() {
    check_ajax_referer('ih_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'ih_notifications', ['id' => $id], ['%d']);
    }
    wp_send_json_success();
}

// [ih audit] ih_send_message notification hook registered above (always active when plugin loads).

// [ih audit] Duplicate 'ih_mark_all_threads_read' closure removed — see the single
// authoritative ih_mark_all_threads_read_handler() registered just below.
add_action('wp_ajax_ih_get_sidebar_counts', 'ih_get_sidebar_counts_handler');
function ih_get_sidebar_counts_handler() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ih_nonce')) wp_send_json_error('nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('unauthorized');
 
    global $wpdb;
 
    // Unread chat threads
    $unread_msgs = (int)$wpdb->get_var(
        "SELECT COALESCE(SUM(unread),0) FROM {$wpdb->prefix}ih_threads WHERE unread > 0"
    );
 
    // Unread notifications
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ih_notifications'");
    $unread_notifs = 0;
    if ($table_exists) {
        $unread_notifs = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ih_notifications WHERE is_read = 0"
        );
    }
 
    // Pending requests
    $pending = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ih_requests WHERE LOWER(TRIM(status))='pending'"
    );
 
    $total = $unread_msgs + $unread_notifs;
 
    wp_send_json_success([
        'messages'      => $unread_msgs,
        'notifications' => $unread_notifs,
        'pending'       => $pending,
        'total'         => $total,
    ]);
}
 
// Mark all threads as read
add_action('wp_ajax_ih_mark_all_threads_read', 'ih_mark_all_threads_read_handler');
function ih_mark_all_threads_read_handler() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ih_nonce')) wp_send_json_error();
    if (!current_user_can('manage_options')) wp_send_json_error();
    global $wpdb;
    $wpdb->query("UPDATE {$wpdb->prefix}ih_threads SET unread=0 WHERE unread>0");
    wp_send_json_success();
}
add_action('wp_ajax_ih_mark_single_notification_read', 'ih_mark_single_notification_read_handler');
function ih_mark_single_notification_read_handler() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ih_nonce')) wp_send_json_error();
    if (!current_user_can('manage_options')) wp_send_json_error();
    $id = intval($_POST['id'] ?? 0);
    if (!$id) wp_send_json_error();
    global $wpdb;
    $wpdb->update(
        $wpdb->prefix . 'ih_notifications',
        ['is_read' => 1],
        ['id'      => $id],
        ['%d'],
        ['%d']
    );
    // Return updated unread count
    $unread = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ih_notifications WHERE is_read=0");
    wp_send_json_success(['unread' => $unread]);
}
}
