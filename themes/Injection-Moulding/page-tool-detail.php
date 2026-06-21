<?php
/**
 * Template Name: Tool Detail
 *
 * PUBLIC tool/mould DETAIL page — anonymised spec sheet. Mirrors the Machine
 * Detail design system (page-machine-detail.php + assets/css/im-machine-detail.css,
 * scoped `.imd-*`) but for TOOL fields and WITHOUT the Chart.js match/radar/cycle
 * widgets (tools are spec sheets, not presses). Interactivity comes from the lean
 * assets/js/im-tool-detail.js (localized as TLD_DATA).
 *
 * Route: /tool/?id={ID}  (WP page slug "tool" assigned this template).
 *        Browse cards (page-tools.php) link here via home_url('/tool/?id='.$id).
 *
 * ── Access tiers (resolved server-side; gated data never reaches public DOM) ──
 *   1. Anonymous            → public spec sheet; any action prompts login.
 *   2. Logged-in (requester)→ can Request details; owner contact hidden until approved.
 *   3. Owner / 4. Admin     → contact panel + manage panel unlocked.
 *
 * Data: wp_ih_tools (reuses ih-redesign-helpers.php + plugin status/owner helpers).
 * Tools are anonymised as TL-#####. Reuses the tool-capable theme AJAX handlers
 * (ih_request_listing_details / ih_owner_request_action / ih_toggle_wishlist).
 */

get_header();

global $wpdb;

$imd_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$t = $imd_id ? $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}ih_tools WHERE id = %d",
	$imd_id
), ARRAY_A ) : null;

if ( ! $t ) {
	wp_safe_redirect( home_url( '/tools/' ) );
	exit;
}

/* ── Viewer tier resolution (server-side) ─────────────────────────────────── */
$imd_owner_id  = (int) ( $t['owner_id'] ?? 0 );
$imd_viewer_id = get_current_user_id();
$imd_is_admin  = current_user_can( 'manage_options' );
$imd_is_owner  = ( $imd_viewer_id && $imd_viewer_id === $imd_owner_id );

$imd_status = function_exists( 'ih_listing_contact_status' )
	? ih_listing_contact_status( $imd_viewer_id, $imd_id, 'tool' )
	: 'None';
$imd_approved = ( strtolower( (string) $imd_status ) === 'approved' );
$imd_unlocked = ( $imd_is_admin || $imd_is_owner || $imd_approved );

/* Request-messaging entry point — a thread exists only for an APPROVED request. */
$imd_rmsg = ( $imd_viewer_id && function_exists( 'ih_rmsg_detail_thread_for_viewer' ) )
	? ih_rmsg_detail_thread_for_viewer( 'tool', $imd_id, $imd_viewer_id )
	: null;

/* ── Public visibility gate — mirror the browse query (approved + non-expired). */
$imd_is_expired = function_exists( 'ih_listing_is_expired' ) ? ih_listing_is_expired( $t ) : false;
$imd_has_approved_request = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$wpdb->prefix}ih_requests
	 WHERE listing_id = %d AND listing_type IN ('tool','ih_contact_tool','tool_contact')
	 AND LOWER(TRIM(status)) = 'approved'",
	$imd_id
) );
$imd_public_visible = ( ( (int) ( $t['available'] ?? 0 ) === 1 || $imd_has_approved_request ) && ! $imd_is_expired );

if ( ! $imd_public_visible && ! $imd_is_owner && ! $imd_is_admin ) {
	wp_safe_redirect( home_url( '/tools/' ) );
	exit;
}

/* ── Helpers (scoped to this template; guarded so they never clash) ───────── */
if ( ! function_exists( 'imd_num' ) ) {
	function imd_num( $v ) {
		if ( preg_match( '/-?\d+(?:\.\d+)?/', (string) $v, $mm ) ) {
			return (float) $mm[0];
		}
		return 0.0;
	}
}
if ( ! function_exists( 'imd_yes' ) ) {
	function imd_yes( $v ) {
		$v = strtolower( trim( (string) $v ) );
		return ( $v === 'yes' || $v === '1' || $v === 'true' );
	}
}
if ( ! function_exists( 'imd_has' ) ) {
	function imd_has( $v ) {
		$v = trim( (string) $v );
		return ( $v !== '' && $v !== '0' && strtolower( $v ) !== 'n/a' && $v !== '—' );
	}
}
if ( ! function_exists( 'imd_icon' ) ) {
	function imd_icon( $name ) {
		$p = array(
			'core'        => '<circle cx="12" cy="12" r="9"/><path d="M12 12l4-2.5"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/>',
			'part'        => '<path d="M21 7.5 12 12 3 7.5 12 3z"/><path d="M3 7.5v9L12 21V12"/><path d="M21 7.5v9L12 21"/>',
			'production'  => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
			'materials'   => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
			'mould'       => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
			'automation'  => '<rect x="5" y="9" width="14" height="10" rx="2"/><path d="M12 9V5M9 5h6"/><circle cx="9" cy="14" r="1.2"/><circle cx="15" cy="14" r="1.2"/>',
			'quality'     => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
			'pin'         => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
			'cert'        => '<path d="M12 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M8.5 13.5 7 22l5-3 5 3-1.5-8.5"/>',
			'cycle'       => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/>',
			'gauge'       => '<path d="M12 13l4-3"/><path d="M4 18a8 8 0 1 1 16 0"/><circle cx="12" cy="18" r="1.4"/>',
			'lock'        => '<rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
			'send'        => '<path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7z"/>',
			'check'       => '<path d="m5 12 5 5 9-9"/>',
			'cross'       => '<path d="M18 6 6 18M6 6l12 12"/>',
			'user'        => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'mail'        => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
			'phone'       => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1A19.5 19.5 0 0 1 4.7 13 19.8 19.8 0 0 1 1.6 4.4 2 2 0 0 1 3.6 2.2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L7.9 11a16 16 0 0 0 6 6l1.2-1.4a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
			'building'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 22V12h6v10M9 7h.01M15 7h.01M9 10h.01M15 10h.01"/>',
			'compare'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
			'calc'        => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14v4M8 18h4"/>',
			'map'         => '<path d="M3 21V5l6-2 6 2 6-2v16l-6 2-6-2-6 2z"/><path d="M9 3v16M15 5v16"/>',
			'cost'        => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
			'spec'        => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
			'printer'     => '<path d="M6 9V2h12v7"/><rect x="6" y="13" width="12" height="8"/><path d="M6 17H4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2"/>',
			'layers'      => '<path d="m12 2 9 5-9 5-9-5 9-5z"/><path d="m3 12 9 5 9-5M3 17l9 5 9-5"/>',
			'wrench'      => '<path d="M14.7 6.3a4 4 0 0 0 5 5l-9 9a2.8 2.8 0 0 1-4-4l9-9z"/><path d="M14.7 6.3 19 2l3 3-4.3 4.3"/>',
		);
		$d = $p[ $name ] ?? $p['core'];
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
	}
}
if ( ! function_exists( 'imd_section_head' ) ) {
	function imd_section_head( $icon, $title ) {
		echo '<div class="imd-sec__head"><span class="imd-sec__icon">' . imd_icon( $icon ) . '</span><h2 class="imd-sec__title">' . esc_html( $title ) . '</h2></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
/** Return $unit only when the stored value is a bare number (no unit letters
 *  already typed by the owner), so we never double-label e.g. "850 kg". */
if ( ! function_exists( 'imd_bare_unit' ) ) {
	function imd_bare_unit( $value, $unit ) {
		$value = trim( (string) $value );
		if ( $value === '' ) {
			return '';
		}
		return preg_match( '/[a-zA-Z]/', $value ) ? '' : $unit;
	}
}
if ( ! function_exists( 'imd_spec_cell' ) ) {
	function imd_spec_cell( $label, $value, $unit = '', $tip = '' ) {
		$value = trim( (string) $value );
		if ( ! imd_has( $value ) ) {
			return;
		}
		$tip_attr = $tip !== '' ? ' data-tip="' . esc_attr( $tip ) . '"' : '';
		echo '<div class="imd-cell"><span class="imd-cell__k"' . $tip_attr . '>' . esc_html( $label ) . '</span><span class="imd-cell__v">' . esc_html( $value ) . ( $unit ? ' <small>' . esc_html( $unit ) . '</small>' : '' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
/** Tool material list = materials JSON ∪ single `material` ∪ polymer tolerance flags. */
if ( ! function_exists( 'imt_materials' ) ) {
	function imt_materials( $row ) {
		$out = array();
		$raw = trim( (string) ( $row['materials'] ?? '' ) );
		if ( $raw !== '' ) {
			$arr = json_decode( $raw, true );
			if ( ! is_array( $arr ) ) {
				$arr = preg_split( '/\s*,\s*/', $raw );
			}
			foreach ( (array) $arr as $m ) {
				$m = trim( (string) $m );
				if ( $m !== '' ) {
					$out[] = $m;
				}
			}
		}
		$single = trim( (string) ( $row['material'] ?? '' ) );
		if ( $single !== '' ) {
			$out[] = $single;
		}
		$flags = array( 'tolerance_pp' => 'PP', 'tolerance_abs' => 'ABS', 'tolerance_pe' => 'PE' );
		foreach ( $flags as $col => $code ) {
			$v = strtolower( trim( (string) ( $row[ $col ] ?? '' ) ) );
			if ( $v === 'yes' || $v === '1' || $v === 'true' ) {
				$out[] = $code;
			}
		}
		return array_values( array_unique( array_filter( array_map( 'trim', $out ) ) ) );
	}
}

/**
 * Polymer reference table for the client-side ENGINEERING / COST estimators.
 * kp      = clamp factor (tonnes of clamp force per cm² of projected area) — a
 *           well-known moulding rule-of-thumb band (0.2–0.6 t/cm²).
 * density = g/cm³ (for shot-weight estimates).
 * price   = indicative £/kg — a *default* only (there is NO live price feed or
 *           supplier database behind this page; see the COST module note).
 * Mirrored 1:1 in im-tool-detail.js. Codes are matched case-insensitively
 * against the listing's material grade / materials list.
 */
if ( ! function_exists( 'imt_material_table' ) ) {
	function imt_material_table() {
		return array(
			'PP'   => array( 'label' => 'PP',   'kp' => 0.26, 'density' => 0.905, 'price' => 1.50 ),
			'ABS'  => array( 'label' => 'ABS',  'kp' => 0.30, 'density' => 1.05,  'price' => 2.10 ),
			'PE'   => array( 'label' => 'PE',   'kp' => 0.24, 'density' => 0.945, 'price' => 1.35 ),
			'HDPE' => array( 'label' => 'HDPE', 'kp' => 0.24, 'density' => 0.95,  'price' => 1.40 ),
			'PA6'  => array( 'label' => 'PA6',  'kp' => 0.42, 'density' => 1.13,  'price' => 3.00 ),
			'PA66' => array( 'label' => 'PA66', 'kp' => 0.45, 'density' => 1.14,  'price' => 3.20 ),
			'PC'   => array( 'label' => 'PC',   'kp' => 0.40, 'density' => 1.20,  'price' => 3.40 ),
			'POM'  => array( 'label' => 'POM',  'kp' => 0.38, 'density' => 1.41,  'price' => 2.60 ),
			'PMMA' => array( 'label' => 'PMMA', 'kp' => 0.34, 'density' => 1.18,  'price' => 2.40 ),
			'PET'  => array( 'label' => 'PET',  'kp' => 0.32, 'density' => 1.38,  'price' => 1.60 ),
			'PS'   => array( 'label' => 'PS',   'kp' => 0.28, 'density' => 1.05,  'price' => 1.70 ),
			'SAN'  => array( 'label' => 'SAN',  'kp' => 0.30, 'density' => 1.08,  'price' => 2.30 ),
			'TPU'  => array( 'label' => 'TPU',  'kp' => 0.30, 'density' => 1.20,  'price' => 4.50 ),
			'TPE'  => array( 'label' => 'TPE',  'kp' => 0.28, 'density' => 1.10,  'price' => 3.80 ),
			'PVC'  => array( 'label' => 'PVC',  'kp' => 0.30, 'density' => 1.40,  'price' => 1.30 ),
			'PEEK' => array( 'label' => 'PEEK', 'kp' => 0.55, 'density' => 1.30,  'price' => 90.00 ),
			'PEI'  => array( 'label' => 'PEI',  'kp' => 0.50, 'density' => 1.27,  'price' => 60.00 ),
		);
	}
}
/** Pick the closest known polymer code for a free-text grade / materials list. */
if ( ! function_exists( 'imt_material_code' ) ) {
	function imt_material_code( $hints ) {
		$hay = strtoupper( ' ' . implode( ' ', array_map( 'strval', (array) $hints ) ) . ' ' );
		/* Longest codes first so PA66 wins over PA6, etc. */
		foreach ( array( 'PEEK', 'PA66', 'HDPE', 'PMMA', 'PEI', 'PET', 'PVC', 'POM', 'TPU', 'TPE', 'SAN', 'PA6', 'ABS', 'PC', 'PS', 'PP', 'PE' ) as $code ) {
			if ( strpos( $hay, $code ) !== false ) {
				return $code;
			}
		}
		return '';
	}
}

/* ── Derived display values ───────────────────────────────────────────────── */
$imd_ref   = function_exists( 'ih_listing_ref' ) ? ih_listing_ref( $t, 'tool' ) : ( 'TL-' . str_pad( (string) $imd_id, 5, '0', STR_PAD_LEFT ) );
$imd_title = trim( (string) ( $t['title'] ?? '' ) ) ?: ( 'Tool · ' . $imd_ref );
$imd_type  = trim( (string) ( $t['mould_type'] ?? '' ) );
$imd_cond  = trim( (string) ( $t['mould_condition'] ?? ( $t['tool_condition'] ?? '' ) ) );
$imd_loc   = trim( (string) ( $t['location'] ?? '' ) );

$imd_status_meta = function_exists( 'ih_listing_status_meta' )
	? ih_listing_status_meta( $t )
	: array( 'key' => ! empty( $t['available'] ) ? 'available' : 'pending', 'label' => ! empty( $t['available'] ) ? 'Available' : 'Pending' );
$imd_status_key   = $imd_status_meta['key'] ?? 'available';
$imd_status_label = ( $imd_status_key === 'available' ) ? 'Available' : ( $imd_status_meta['label'] ?? 'Pending' );

$imd_materials = imt_materials( $t );

/* Numeric specs for the summary mini-stats. */
$imd_cav    = (int) imd_num( $t['num_cavities_spec'] ?? ( $t['num_cavities'] ?? '' ) );
$imd_partwt = imd_num( $t['part_weight'] ?? '' );

/* Compliance flags (the tool equivalent of certifications). */
$imd_compliance = array(
	'Medical grade'      => imd_yes( $t['medical_grade'] ?? '' ),
	'Food grade'         => imd_yes( $t['food_grade'] ?? '' ),
	'In-mould labelling' => imd_yes( $t['iml'] ?? '' ),
	'Automation ready'   => imd_yes( $t['automation'] ?? '' ),
);
$imd_compliance_on = array_keys( array_filter( $imd_compliance ) );

/* Gallery — every non-empty image slot (image_1..image_5). */
$imd_imgs = array();
for ( $imd_ii = 1; $imd_ii <= 5; $imd_ii++ ) {
	$imd_src = trim( (string) ( $t[ "image_{$imd_ii}" ] ?? '' ) );
	if ( $imd_src !== '' ) {
		$imd_imgs[] = $imd_src;
	}
}
$imd_imgs = array_values( $imd_imgs );

/* Spec-completeness meter — share of key public tool fields that are filled. */
$imd_completeness_fields = array(
	'part_name', 'part_dimensions', 'part_weight', 'num_cavities_spec', 'mould_type',
	'mould_material', 'mould_condition', 'mould_dimensions', 'mould_weight', 'tool_life',
	'runner_type', 'gate_type', 'ejector_type', 'nozzle_type', 'surface_finish',
	'tolerance', 'draft_angle', 'cycle_time', 'annual_volume', 'min_order_qty',
	'colour', 'location',
);
$imd_filled = 0;
foreach ( $imd_completeness_fields as $f ) {
	if ( imd_has( $t[ $f ] ?? '' ) ) {
		$imd_filled++;
	}
}
if ( ! empty( $imd_materials ) ) {
	$imd_filled++;
}
$imd_completeness = (int) round( $imd_filled / ( count( $imd_completeness_fields ) + 1 ) * 100 );

/* Dates */
$imd_listed = ( ! empty( $t['listing_date'] ) && $t['listing_date'] !== '0000-00-00' ) ? date_i18n( 'j M Y', strtotime( (string) $t['listing_date'] ) ) : '';
$imd_expiry = ( ! empty( $t['expiry_date'] ) && $t['expiry_date'] !== '0000-00-00' ) ? date_i18n( 'j M Y', strtotime( (string) $t['expiry_date'] ) ) : '';

/* Owner contact data — ONLY fetched when unlocked, so it can never leak. */
$imd_owner = ( $imd_unlocked && function_exists( 'ih_listing_owner_data' ) ) ? ih_listing_owner_data( $imd_owner_id ) : array();

/* Login URL preserves return-to-detail. */
$imd_login_url = home_url( '/register/?tab=login&redirect_to=' . rawurlencode( home_url( '/tool/?id=' . $imd_id ) ) );
if ( function_exists( 'im_auth_url' ) ) {
	$imd_login_url = add_query_arg( 'redirect_to', rawurlencode( home_url( '/tool/?id=' . $imd_id ) ), im_auth_url( 'login' ) );
}

/* Saved (favourite) state */
$imd_saved   = false;
$imd_wishlist = $imd_viewer_id ? get_user_meta( $imd_viewer_id, 'ih_wishlist', true ) : array();
if ( is_array( $imd_wishlist ) ) {
	foreach ( $imd_wishlist as $w ) {
		if ( (int) ( $w['id'] ?? 0 ) === $imd_id && ( $w['type'] ?? '' ) === 'tool' ) {
			$imd_saved = true;
			break;
		}
	}
}

/* ── Similar tools: material overlap + same mould type + same region ──────── */
$imd_similar = array();
$imd_not_expired = function_exists( 'ih_listing_not_expired_sql' ) ? ih_listing_not_expired_sql( 's.expiry_date' ) : '1=1';
$imd_candidates = $wpdb->get_results( $wpdb->prepare(
	"SELECT s.* FROM {$wpdb->prefix}ih_tools s
	 WHERE s.id <> %d AND s.available = 1 AND {$imd_not_expired}
	 ORDER BY s.id DESC LIMIT 24",
	$imd_id
), ARRAY_A ) ?: array();
$imd_region_key = strtolower( trim( (string) ( preg_split( '/[,\/]/', $imd_loc )[0] ?? '' ) ) );
foreach ( $imd_candidates as $c ) {
	$score = 0;
	$cmats = imt_materials( $c );
	if ( array_intersect( array_map( 'strtoupper', $imd_materials ), array_map( 'strtoupper', $cmats ) ) ) {
		$score += 2;
	}
	if ( $imd_type !== '' && strcasecmp( $imd_type, trim( (string) ( $c['mould_type'] ?? '' ) ) ) === 0 ) {
		$score += 2;
	}
	$cregion = strtolower( trim( (string) ( preg_split( '/[,\/]/', (string) ( $c['location'] ?? '' ) )[0] ?? '' ) ) );
	if ( $imd_region_key !== '' && $cregion === $imd_region_key ) {
		$score += 1;
	}
	if ( $score > 0 ) {
		$c['_score'] = $score;
		$imd_similar[] = $c;
	}
}
usort( $imd_similar, static function ( $a, $b ) {
	return ( $b['_score'] <=> $a['_score'] ) ?: ( (int) $b['id'] <=> (int) $a['id'] );
} );
$imd_similar = array_slice( $imd_similar, 0, 4 );

if ( ! function_exists( 'imt_similar_card' ) ) {
	function imt_similar_card( $c ) {
		$cid  = (int) ( $c['id'] ?? 0 );
		$cref = function_exists( 'ih_listing_ref' ) ? ih_listing_ref( $c, 'tool' ) : ( 'TL-' . str_pad( (string) $cid, 5, '0', STR_PAD_LEFT ) );
		$ctit = trim( (string) ( $c['title'] ?? '' ) ) ?: ( 'Tool · ' . $cref );
		$cimg = trim( (string) ( $c['image_1'] ?? '' ) );
		$ccav = (int) imd_num( $c['num_cavities_spec'] ?? ( $c['num_cavities'] ?? '' ) );
		$cton = (int) imd_num( $c['clamp_force'] ?? '' );
		?>
		<a class="imd-sim" href="<?php echo esc_url( home_url( '/tool/?id=' . $cid ) ); ?>">
			<span class="imd-sim__media">
				<?php if ( $cimg ) : ?>
					<img src="<?php echo esc_url( $cimg ); ?>" alt="<?php echo esc_attr( $cref ); ?>" loading="lazy">
				<?php endif; ?>
			</span>
			<span class="imd-sim__body">
				<span class="imd-sim__ref"><?php echo esc_html( $cref ); ?></span>
				<span class="imd-sim__title"><?php echo esc_html( $ctit ); ?></span>
			</span>
			<?php if ( $cton ) : ?>
			<span class="imd-sim__ton"><?php echo esc_html( (string) $cton ); ?> T</span>
			<?php elseif ( $ccav ) : ?>
			<span class="imd-sim__ton"><?php echo esc_html( (string) $ccav ); ?> cav</span>
			<?php endif; ?>
		</a>
		<?php
	}
}

$imd_default_img = get_template_directory_uri() . '/assets/images/Services/Injection-Moulding.png';

/* ── Owner / admin gated data (computed ONLY when unlocked as owner/admin) ─── */
$imd_show_admin   = ( $imd_is_admin || $imd_is_owner );
$imd_user_ref     = function_exists( 'ih_user_ref' ) ? ih_user_ref( $imd_owner_id ) : ( 'USR-' . str_pad( (string) $imd_owner_id, 5, '0', STR_PAD_LEFT ) );
$imd_edit_url     = $imd_is_admin
	? admin_url( 'admin.php?page=ih-edit-tool&tool_id=' . $imd_id )
	: admin_url( 'admin.php?page=ih-user-edit-tool&tool_id=' . $imd_id );
$imd_pending_reqs = array();
if ( $imd_show_admin ) {
	$imd_pending_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, user_id, request_date FROM {$wpdb->prefix}ih_requests
		 WHERE listing_id = %d
		 AND listing_type IN ('tool','ih_contact_tool','tool_contact')
		 AND LOWER(TRIM(status)) = 'pending'
		 ORDER BY id DESC",
		$imd_id
	), ARRAY_A ) ?: array();
	foreach ( $imd_pending_rows as $pr ) {
		$ru = (int) ( $pr['user_id'] ?? 0 );
		$ud = $ru ? get_userdata( $ru ) : null;
		$imd_pending_reqs[] = array(
			'id'   => (int) ( $pr['id'] ?? 0 ),
			'name' => $ud ? $ud->display_name : ( function_exists( 'ih_user_ref' ) ? ih_user_ref( $ru ) : ( 'USR-' . str_pad( (string) $ru, 5, '0', STR_PAD_LEFT ) ) ),
		);
	}
}
$imd_strength = $imd_completeness;

/* ── Engineering-estimate seeds (real specs → calculator defaults) ─────────────
 * Every value below is a REAL column off this listing where one exists; the
 * client-side calculators (im-tool-detail.js) seed from these and recompute
 * "what-if" outputs. Empty values fall back to sensible defaults in JS. */
$imd_dims = array( 0.0, 0.0, 0.0 );
if ( preg_match_all( '/\d+(?:\.\d+)?/', (string) ( $t['part_dimensions'] ?? '' ), $imd_dm ) ) {
	$imd_dims[0] = isset( $imd_dm[0][0] ) ? (float) $imd_dm[0][0] : 0.0;
	$imd_dims[1] = isset( $imd_dm[0][1] ) ? (float) $imd_dm[0][1] : 0.0;
	$imd_dims[2] = isset( $imd_dm[0][2] ) ? (float) $imd_dm[0][2] : 0.0;
}
$imd_seed = array(
	'partL'      => $imd_dims[0],
	'partW'      => $imd_dims[1],
	'partD'      => $imd_dims[2],
	'cavities'   => $imd_cav ?: 1,
	'partWeight' => $imd_partwt,
	'projArea'   => imd_num( $t['projected_area'] ?? '' ),
	'cavPress'   => imd_num( $t['cavity_pressure'] ?? '' ),
	'clampForce' => imd_num( $t['clamp_force'] ?? '' ),
	'shotWeight' => imd_num( $t['shot_weight'] ?? '' ),
	'cycle'      => imd_num( $t['cycle_time'] ?? '' ),
	'annual'     => imd_num( $t['annual_volume'] ?? '' ),
	'minOrder'   => imd_num( $t['min_order_qty'] ?? '' ),
	'tieBar'     => trim( (string) ( $t['tie_bar'] ?? '' ) ),
	'opening'    => imd_num( $t['opening_stroke'] ?? '' ),
);
$imd_mat_table = imt_material_table();
$imd_mat_code  = imt_material_code( array_merge( array( $t['material_grade'] ?? '', $t['material'] ?? '' ), $imd_materials ) );
if ( $imd_mat_code === '' || ! isset( $imd_mat_table[ $imd_mat_code ] ) ) {
	$imd_mat_code = 'PP';
}
$imd_mat_primary = $imd_mat_code;
$imd_mat_grade   = trim( (string) ( $t['material_grade'] ?? '' ) );
$imd_mould_loc   = trim( (string) ( $t['mould_location'] ?? '' ) ) ?: $imd_loc;
$imd_seed_json   = wp_json_encode( $imd_seed );
?>

<main class="imd-page imd-page--tools" id="imdPage">
<div class="imd-shell">

	<!-- Breadcrumb + print -->
	<div class="imt-crumbrow">
		<nav class="imd-crumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/tools/' ) ); ?>">Browse</a>
			<span class="sep" aria-hidden="true">/</span>
			<a class="imd-crumb__cat" href="<?php echo esc_url( home_url( '/tools/' ) ); ?>">Tools</a>
			<span class="sep" aria-hidden="true">/</span>
			<span aria-current="page"><?php echo esc_html( $imd_ref ); ?></span>
		</nav>
		<button type="button" class="imt-crumbprint" id="imdPrintTop" aria-label="Print this spec sheet"><?php echo imd_icon( 'printer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</div>

	<div class="imd-layout">

		<!-- ════════════ LEFT (≈60%) ════════════ -->
		<div class="imd-main">

			<!-- Gallery -->
			<section class="imd-gallery" aria-label="Tool images">
				<div class="imd-gallery__stage">
					<?php if ( ! empty( $imd_imgs ) ) : ?>
						<button type="button" class="imd-gallery__zoom" id="imdCoverBtn" data-index="0" aria-label="<?php echo esc_attr( 'Open larger view of ' . $imd_title ); ?>" aria-haspopup="dialog">
							<img id="imdCover" class="imd-gallery__cover" src="<?php echo esc_url( $imd_imgs[0] ); ?>" alt="<?php echo esc_attr( $imd_title ); ?>" onerror="this.onerror=null;this.src='<?php echo esc_js( $imd_default_img ); ?>'">
							<span class="imd-gallery__zoomhint" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/></svg></span>
						</button>
					<?php else : ?>
						<span class="imd-gallery__placeholder" aria-hidden="true"><?php echo imd_icon( 'mould' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<?php endif; ?>
					<span class="imd-pill imd-pill--<?php echo esc_attr( $imd_status_key ); ?>"><span class="dot" aria-hidden="true"></span><?php echo esc_html( $imd_status_label ); ?></span>
				</div>
				<?php if ( count( $imd_imgs ) > 1 ) : ?>
				<div class="imd-gallery__thumbs">
					<?php foreach ( $imd_imgs as $i => $img ) : ?>
						<button type="button" class="imd-thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" data-src="<?php echo esc_url( $img ); ?>" aria-label="<?php echo esc_attr( 'View image ' . ( $i + 1 ) ); ?>"<?php echo $i === 0 ? ' aria-pressed="true"' : ' aria-pressed="false"'; ?>>
							<img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy">
						</button>
					<?php endforeach; ?>
				</div>
				<?php else : ?>
				<div class="imt-thumbs--ph" aria-hidden="true">
					<?php for ( $imd_ph = 0; $imd_ph < 4; $imd_ph++ ) : ?>
						<span class="imt-thumb-ph"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></span>
					<?php endfor; ?>
				</div>
				<?php endif; ?>
			</section>

			<!-- ═══ Tooling calculator (engineering estimate) ═══ -->
			<section class="imd-sec imt-mod" id="imtCalc" data-seed='<?php echo esc_attr( $imd_seed_json ); ?>'>
				<div class="imd-sec__head imt-head">
					<span class="imd-sec__icon"><?php echo imd_icon( 'calc' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<div class="imt-head__txt">
						<h2 class="imd-sec__title">Tooling calculator</h2>
						<p class="imt-head__sub">Size the mould &amp; press for YOUR part — live engineering estimate</p>
					</div>
					<span class="imd-est-badge">Engineering estimate</span>
				</div>

				<p class="imt-grouplbl">Your part — edit any value to recalculate</p>
				<div class="imt-grid imt-grid--3">
					<div class="imd-field"><label for="imtPartL">Part length (mm)</label><input type="number" id="imtPartL" min="0" step="0.1" inputmode="decimal" value="<?php echo $imd_seed['partL'] ? esc_attr( (string) $imd_seed['partL'] ) : ''; ?>" placeholder="313"></div>
					<div class="imd-field"><label for="imtPartW">Part width (mm)</label><input type="number" id="imtPartW" min="0" step="0.1" inputmode="decimal" value="<?php echo $imd_seed['partW'] ? esc_attr( (string) $imd_seed['partW'] ) : ''; ?>" placeholder="313"></div>
					<div class="imd-field"><label for="imtPartD">Part depth (mm)</label><input type="number" id="imtPartD" min="0" step="0.1" inputmode="decimal" value="<?php echo $imd_seed['partD'] ? esc_attr( (string) $imd_seed['partD'] ) : ''; ?>" placeholder="42"></div>
					<div class="imd-field"><label for="imtWall">Wall thickness (mm)</label><input type="number" id="imtWall" min="0" step="0.1" inputmode="decimal" value="" placeholder="2.0"></div>
					<div class="imd-field"><label for="imtCav">Cavities</label><input type="number" id="imtCav" min="1" step="1" inputmode="numeric" value="<?php echo esc_attr( (string) $imd_seed['cavities'] ); ?>" placeholder="1"></div>
					<div class="imd-field"><label for="imtMat">Material</label>
						<select id="imtMat" class="imt-select">
							<?php foreach ( $imd_mat_table as $code => $m ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" data-kp="<?php echo esc_attr( (string) $m['kp'] ); ?>" data-density="<?php echo esc_attr( (string) $m['density'] ); ?>" data-price="<?php echo esc_attr( (string) $m['price'] ); ?>"<?php selected( $code, $imd_mat_primary ); ?>><?php echo esc_html( $m['label'] . ' (Kp ' . $m['kp'] . ')' ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<p class="imt-grouplbl imt-grouplbl--mt">Calculated requirement</p>
				<div class="imt-tiles imt-tiles--4">
					<div class="imt-tile imt-tile--brand"><span class="imt-tile__v"><span id="imtProjArea">0</span> <small>cm²</small></span><span class="imt-tile__k">Projected area</span></div>
					<div class="imt-tile imt-tile--brand"><span class="imt-tile__v"><span id="imtReqClamp">0</span> <small>T</small></span><span class="imt-tile__k">Required clamp</span></div>
					<div class="imt-tile imt-tile--teal"><span class="imt-tile__v"><span id="imtShotCav">0</span> <small>g</small></span><span class="imt-tile__k">Shot wt / cavity</span></div>
					<div class="imt-tile imt-tile--green"><span class="imt-tile__v"><span id="imtTotalShot">0</span> <small>g</small></span><span class="imt-tile__k">Total shot</span></div>
					<div class="imt-tile"><span class="imt-tile__v">≥ <span id="imtMinOpen">0</span> <small>mm</small></span><span class="imt-tile__k">Min mould open</span></div>
					<div class="imt-tile"><span class="imt-tile__v imt-tile__v--sm">≤ ⅓ daylight</span><span class="imt-tile__k">Shut-height guide</span></div>
					<div class="imt-tile imt-tile--brand"><span class="imt-tile__v"><span id="imtPressLo">0</span>–<span id="imtPressHi">0</span> <small>T</small></span><span class="imt-tile__k">Recommended press</span></div>
					<div class="imt-tile imt-tile--amber"><span class="imt-tile__v"><span id="imtShotUse">0</span> <small id="imtShotUseRef">% of 150g</small></span><span class="imt-tile__k">Shot-size use</span></div>
				</div>

				<div class="imt-modfoot">
					<p class="imt-foot">Tonnage = projected area × material clamp factor × 1.2 safety. Shot = (part + runner vol) × density × 1.05 cushion.</p>
					<button type="button" class="imd-btn imd-btn--primary imt-recalc" id="imtCalcBtn"><?php echo imd_icon( 'cycle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Calculate</span></button>
				</div>
			</section>

			<!-- ═══ Machine-fit map (estimated) ═══ -->
			<section class="imd-sec imt-mod" id="imtFit">
				<div class="imd-sec__head imt-head">
					<span class="imd-sec__icon"><?php echo imd_icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<div class="imt-head__txt">
						<h2 class="imd-sec__title">Machine-fit map</h2>
						<p class="imt-head__sub">Does your part sit inside the press's envelope? Projected area vs clamp tonnage</p>
					</div>
					<span class="imd-est-badge">Estimated</span>
				</div>
				<div class="imt-fitmap" id="imtFitMap" role="img" aria-label="Scatter plot of projected area versus clamp tonnage showing this tool relative to your press envelope"></div>
				<div class="imt-fitlegend">
					<span class="imt-fitleg"><span class="imt-fitleg__sw imt-fitleg__sw--tool"></span> This tool</span>
					<span class="imt-fitleg"><span class="imt-fitleg__sw imt-fitleg__sw--env"></span> Your press envelope</span>
				</div>
				<div class="imt-note imt-note--ok" id="imtFitNote"></div>
				<div class="imt-note imt-note--hint" id="imtFitHint">Change cavities or material in the calculator — the point moves live.</div>
			</section>

			<!-- ═══ Cost & order calculator (live resin price → REST backend) ═══
			 * Root `data-ih-cost-calc` + `data-pf` hooks are the DOM contract that the
			 * plugin's js/material-pricing.js binds to. The selectors auto-populate from
			 * the REST /materials endpoint; price comes from /material-price (with a
			 * manual-entry fallback for un-seeded polymers). Staff can POST an override. -->
			<section class="imd-sec imt-mod" id="imtCost" data-ih-cost-calc>
				<div class="imd-sec__head imt-head">
					<span class="imd-sec__icon"><?php echo imd_icon( 'cost' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<div class="imt-head__txt">
						<h2 class="imd-sec__title">Cost &amp; order calculator</h2>
						<p class="imt-head__sub">What this tool costs to run per part and per order — live resin price + your rates, with platform &amp; handling costs folded into the grand cost</p>
					</div>
					<span class="imd-est-badge">Live price</span>
				</div>

				<!-- Material price (live £/kg from the pricing backend) -->
				<div class="imt-pricecard">
					<div class="imt-pricecard__head">
						<span class="imt-pricecard__title"><?php echo imd_icon( 'materials' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Material price</span>
						<span class="ih-price-badge" data-pf="price-badge">—</span>
					</div>
					<div class="imt-grid imt-grid--3">
						<div class="imd-field"><label for="imtCostMat">Material</label>
							<select id="imtCostMat" class="imt-select" data-pf="material">
								<option value=""><?php echo esc_html( $imd_mat_primary ? $imd_mat_primary : 'Select material…' ); ?></option>
							</select>
						</div>
						<div class="imd-field"><label for="imtCostGrade">Grade</label>
							<select id="imtCostGrade" class="imt-select" data-pf="grade">
								<option value="<?php echo esc_attr( $imd_mat_grade ); ?>"><?php echo esc_html( $imd_mat_grade ? $imd_mat_grade : 'Any grade' ); ?></option>
							</select>
						</div>
						<div class="imd-field"><label for="imtCostRegion">Region</label>
							<select id="imtCostRegion" class="imt-select" data-pf="region">
								<option value="">Any region</option>
							</select>
						</div>
						<div class="imd-field"><label for="imtCostSupplier">Supplier</label>
							<select id="imtCostSupplier" class="imt-select" data-pf="supplier">
								<option value="">Any supplier</option>
							</select>
						</div>
						<div class="imd-field"><label>Price source</label>
							<span class="imt-pricesource" data-pf="price-source">Fetching price…</span>
						</div>
						<div class="imd-field"><label for="imtPrice">Price / kg</label>
							<div class="imt-inputaffix"><input type="number" id="imtPrice" min="0" step="0.01" inputmode="decimal" data-pf="price-per-kg"><span class="imt-inputaffix__u">£/kg</span></div>
						</div>
						<div class="imd-field"><label>Source type</label>
							<span class="imt-pricesource" data-pf="price-source-type">—</span>
						</div>
						<div class="imd-field"><label>Last checked</label>
							<span class="imt-pricesource" data-pf="price-last-checked">—</span>
						</div>
					</div>
					<p class="imt-priceupdated" data-pf="price-updated"></p>
					<!-- Estimate / staleness / public-reference warning surfaced by material-pricing.js -->
					<p class="imt-pricewarn" data-pf="price-warning" role="alert" style="display:none"></p>
					<p class="imt-pricecard__note">£/kg comes from the resin-pricing backend (seeded PP/PE/ABS/PC). Public market references are indicative — only a verified licensed feed shows as “Live”. If a polymer has no listed price, type one in manually — the figure won't be saved.</p>
					<?php
					// "Use public reference for estimate" control — rendered ONLY when an
					// admin has enabled the option. When off, public references are never
					// used as a quote price, so the control is intentionally hidden.
					if ( class_exists( 'IH_Material_Price_Config' ) && IH_Material_Price_Config::allow_public_reference_for_quotes() ) :
						?>
						<label class="imt-usepublicref"><input type="checkbox" data-pf="use-public-ref" checked> Use public market references for estimates when no supplier/manual price exists (shown as an <em>Estimate</em>, never “Live”).</label>
					<?php endif; ?>
					<div class="imt-override">
						<label class="imt-override__switch"><input type="checkbox" data-pf="override-toggle"><span class="imt-override__lbl">Manual override (staff)</span></label>
						<span class="imt-override__hint">Staff with pricing permission can set a £/kg with an audit reason — saved to the pricing backend.</span>
						<div class="imt-override__wrap" data-pf="override-wrap" hidden>
							<div class="imd-field"><label for="imtOverridePrice">Override £/kg</label><input type="number" id="imtOverridePrice" min="0" step="0.01" inputmode="decimal" data-pf="override-price" placeholder="1.50"></div>
							<div class="imd-field"><label for="imtOverrideReason">Reason (audit)</label><input type="text" id="imtOverrideReason" data-pf="override-reason" placeholder="e.g. contracted supplier price"></div>
							<button type="button" class="imd-btn imd-btn--primary imt-override__save" data-pf="override-save"><?php echo imd_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Save override</span></button>
						</div>
					</div>
				</div>

				<p class="imt-grouplbl imt-grouplbl--mt">Your job — edit rates &amp; quantity to recost (updates live)</p>
				<div class="imt-grid imt-grid--3">
					<div class="imd-field"><label for="imtPartWt">Part weight (g)</label><input type="number" id="imtPartWt" min="0" step="0.01" inputmode="decimal" data-pf="part-weight" value="<?php echo $imd_seed['partWeight'] ? esc_attr( (string) $imd_seed['partWeight'] ) : ''; ?>" placeholder="20"></div>
					<div class="imd-field"><label for="imtRunnerWt">Runner weight (g)</label><input type="number" id="imtRunnerWt" min="0" step="0.01" inputmode="decimal" data-pf="runner-weight" value="" placeholder="2"></div>
					<div class="imd-field"><label for="imtCostCav">Cavities</label><input type="number" id="imtCostCav" min="1" step="1" inputmode="numeric" data-pf="cavities" value="<?php echo esc_attr( (string) $imd_seed['cavities'] ); ?>" placeholder="1"></div>
					<div class="imd-field"><label for="imtCostCycle">Cycle time (s)</label><input type="number" id="imtCostCycle" min="0" step="0.1" inputmode="decimal" data-pf="cycle-time" value="<?php echo $imd_seed['cycle'] ? esc_attr( (string) $imd_seed['cycle'] ) : ''; ?>" placeholder="32"></div>
					<div class="imd-field"><label for="imtRate">Machine rate (£/h)</label><input type="number" id="imtRate" min="0" step="1" inputmode="decimal" data-pf="machine-rate" value="45" placeholder="45"></div>
					<div class="imd-field"><label for="imtScrap">Scrap %</label><input type="number" id="imtScrap" min="0" max="100" step="0.5" inputmode="decimal" data-pf="scrap" value="3" placeholder="3"></div>
					<div class="imd-field"><label for="imtQty">Order quantity</label><input type="number" id="imtQty" min="0" step="1" inputmode="numeric" data-pf="order-qty" value="<?php echo esc_attr( (string) (int) ( $imd_seed['annual'] ?: ( $imd_seed['minOrder'] ?: 50000 ) ) ); ?>" placeholder="50000"></div>
					<div class="imd-field"><label for="imtTooling">Tooling (£, optional)</label><input type="number" id="imtTooling" min="0" step="1" inputmode="numeric" data-pf="tooling" value="" placeholder="—"></div>
				</div>

				<div class="imt-errors" data-pf="calc-errors" role="alert" hidden></div>

				<p class="imt-grouplbl imt-grouplbl--mt">Calculated cost</p>
				<div class="imt-tiles imt-tiles--4">
					<div class="imt-tile imt-tile--green"><span class="imt-tile__v" data-pf="out-material">£0.00</span><span class="imt-tile__k">Material / part</span></div>
					<div class="imt-tile imt-tile--teal"><span class="imt-tile__v" data-pf="out-processing">£0.00</span><span class="imt-tile__k">Processing / part</span></div>
					<div class="imt-tile imt-tile--brand"><span class="imt-tile__v"><span data-pf="out-unit">£0.00</span> <small>/part</small></span><span class="imt-tile__k">Unit cost</span></div>
					<div class="imt-tile"><span class="imt-tile__v" data-pf="out-pph">0</span><span class="imt-tile__k">Parts / hour</span></div>
					<div class="imt-tile"><span class="imt-tile__v"><span data-pf="out-total">£0.00</span></span><span class="imt-tile__k">Order total</span></div>
					<div class="imt-tile"><span class="imt-tile__v"><span data-pf="out-hours">0</span> <small>h</small></span><span class="imt-tile__k">Machine hours</span></div>
					<div class="imt-tile"><span class="imt-tile__v"><span data-pf="out-days">0</span> <small>days @24/7</small></span><span class="imt-tile__k">Run time</span></div>
					<div class="imt-tile imt-tile--green"><span class="imt-tile__v" data-pf="out-per1000">£0.00</span><span class="imt-tile__k">Material / 1000</span></div>
				</div>

				<?php
				/* Server-side staff gate. The full platform-fee breakdown (service
				 * charge, transaction fee, platform revenue) is the marketplace's
				 * internal cut and must NEVER reach a public buyer's DOM — it is
				 * emitted only when the viewer holds the pricing capability the
				 * plugin grants to admins (falling back to manage_options). This is
				 * resolved in PHP, never hidden via CSS/JS. */
				$ih_is_pricing_staff = current_user_can( 'ih_manage_pricing' ) || current_user_can( 'manage_options' );
				?>
				<?php if ( $ih_is_pricing_staff ) : ?>
				<?php
					/* STAFF/ADMIN ONLY — full "Order total & platform fees" breakdown.
					 * Hooks (out-subtotal, out-charge, out-fee, out-grand, out-revenue)
					 * are populated live by material-pricing.js. The public "Grand cost"
					 * tile is intentionally NOT rendered in this path so that out-grand
					 * remains unique on the page (the JS querySelector targets this row). */
				?>
				<p class="imt-grouplbl imt-grouplbl--mt">Order total &amp; platform fees (staff)</p>
				<div class="imt-feebreak" role="group" aria-label="Order total and platform fees">
					<p class="imt-feebreak__title">Order total &amp; platform fees</p>
					<dl class="imt-feebreak__list">
						<div class="imt-feebreak__row">
							<dt class="imt-feebreak__lbl">Order subtotal (manufacturing)</dt>
							<dd class="imt-feebreak__amt" data-pf="out-subtotal">£0.00</dd>
						</div>
						<div class="imt-feebreak__row">
							<dt class="imt-feebreak__lbl">Service charge (5%)</dt>
							<dd class="imt-feebreak__amt imt-feebreak__amt--add" data-pf="out-charge">£0.00</dd>
						</div>
						<div class="imt-feebreak__row">
							<dt class="imt-feebreak__lbl">Transaction fee (2%)</dt>
							<dd class="imt-feebreak__amt imt-feebreak__amt--add" data-pf="out-fee">£0.00</dd>
						</div>
						<div class="imt-feebreak__row imt-feebreak__row--grand">
							<dt class="imt-feebreak__lbl">Grand total — buyer pays</dt>
							<dd class="imt-feebreak__amt" data-pf="out-grand">£0.00</dd>
						</div>
					</dl>
					<div class="imt-feebreak__callout">
						<span class="imt-feebreak__ico"><?php echo imd_icon( 'cost' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<p class="imt-feebreak__msg">
							<span class="imt-feebreak__earn"><strong>Platform earns <span data-pf="out-revenue">£0.00</span> on this order</strong></span>
							<span class="imt-feebreak__sub">7% of subtotal (5% service + 2% fee). At scale this is the site&rsquo;s core revenue stream — every quote converted earns the platform automatically.</span>
						</p>
					</div>
				</div>
				<?php else : ?>
				<p class="imt-grouplbl imt-grouplbl--mt">What the buyer pays</p>
				<?php
				/* PUBLIC / NON-STAFF — single headline "Grand cost" tile. The buyer
				 * never sees the marketplace's internal cut — the platform service
				 * charge, transaction fee and platform-revenue markup are not emitted
				 * at all. material-pricing.js still computes grandTotal = subtotal +
				 * platform fees and writes it to out-grand via set(), so the fees stay
				 * baked into Grand cost without ever being displayed. out-grand is the
				 * only such hook in this path. */
				?>
				<div class="imt-tiles imt-tiles--1">
					<div class="imt-tile imt-tile--brand imt-tile--grand">
						<span class="imt-tile__v" data-pf="out-grand">£0.00</span>
						<span class="imt-tile__k">
							Grand cost
							<span class="imt-tile__info" tabindex="0" role="img" aria-label="Grand cost includes additional platform and handling costs that are built into your total." title="Grand cost includes additional platform and handling costs that are built into your total."><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></span>
						</span>
						<span class="imt-tile__cap">Includes platform &amp; handling costs</span>
					</div>
				</div>
				<?php endif; ?>

				<div class="imt-modfoot">
					<p class="imt-foot">Unit = material + processing (+ tooling ÷ qty). Order total = unit × order qty. Grand cost folds in additional platform &amp; handling costs on top of your order total. Recomputes live as you edit.</p>
				</div>
			</section>

			<!-- ═══ Production cycle & throughput (estimated) ═══ -->
			<section class="imd-sec imt-mod" id="imtCycle">
				<div class="imd-sec__head imt-head">
					<button type="button" class="imt-cyclereset" id="imtCycleReset" aria-label="Recalculate cycle"><?php echo imd_icon( 'cycle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					<div class="imt-head__txt imt-head__txt--center">
						<h2 class="imd-sec__title">Production cycle &amp; throughput</h2>
					</div>
					<span class="imd-est-badge"><?php echo imd_icon( 'cycle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Estimated</span>
				</div>
				<div class="imt-cycle">
					<div class="imt-cycle__ring">
						<svg viewBox="0 0 120 120" class="imt-cycle__svg" id="imtCycleSvg" aria-hidden="true"></svg>
						<div class="imt-cycle__center">
							<span class="imt-cycle__t"><span id="imtCycleSecs">0</span> <small>s</small></span>
							<span class="imt-cycle__sub">cycle</span>
						</div>
					</div>
					<div class="imt-cycle__side">
						<div class="imt-cycle__stats">
							<div class="imt-cstat"><span class="imt-cstat__v">≈ <span id="imtPerDay">0</span></span><span class="imt-cstat__k">Parts / day</span></div>
							<div class="imt-cstat"><span class="imt-cstat__v"><span id="imtPerYear">0</span></span><span class="imt-cstat__k">Parts / year</span></div>
							<div class="imt-cstat"><span class="imt-cstat__v"><span id="imtHours">16</span> <small>h</small></span><span class="imt-cstat__k">Per day</span></div>
							<div class="imt-cstat"><span class="imt-cstat__v"><span id="imtUtil">72</span><small>%</small></span><span class="imt-cstat__k">Utilisation</span></div>
						</div>
						<ul class="imt-cycle__legend">
							<li><span class="imt-dot" data-seg="0"></span> Clamp</li>
							<li><span class="imt-dot" data-seg="1"></span> Inject</li>
							<li><span class="imt-dot" data-seg="2"></span> Cool</li>
							<li><span class="imt-dot" data-seg="3"></span> Eject</li>
						</ul>
						<p class="imt-cycle__note">Estimated from cycle time at an assumed 16 h/day, 72% utilisation — calculated, not measured.</p>
					</div>
				</div>
			</section>

			<!-- ═══ Mould specifications ═══ -->
			<section class="imd-sec">
				<?php imd_section_head( 'mould', 'Mould specifications' ); ?>
				<div class="imd-cells imd-cells--3">
					<?php
					imd_spec_cell( 'Mould type', $t['mould_type'] ?? '', '', 'mouldtype' );
					imd_spec_cell( 'No. of cavities', $imd_cav ? (string) $imd_cav : '', '', 'cavities' );
					imd_spec_cell( 'Runner type', $t['runner_type'] ?? '', '', 'runner' );
					imd_spec_cell( 'Ejector type', $t['ejector_type'] ?? '', '', 'ejector' );
					imd_spec_cell( 'Gate type', $t['gate_type'] ?? '', '', 'gate' );
					imd_spec_cell( 'Nozzle type', $t['nozzle_type'] ?? '', '', 'nozzle' );
					imd_spec_cell( 'Clamp & drive type', $t['clamp_drive_type'] ?? '', '', 'clampdrive' );
					imd_spec_cell( 'Toggle clamp type', $t['toggle_clamp_type'] ?? '', '', 'toggletype' );
					imd_spec_cell( 'Tool condition', $imd_cond, '', 'condition' );
					imd_spec_cell( 'Mould material', $t['mould_material'] ?? '', '', 'mouldmaterial' );
					imd_spec_cell( 'Construction', $t['construction'] ?? '' );
					imd_spec_cell( 'Mould weight', $t['mould_weight'] ?? '', imd_bare_unit( $t['mould_weight'] ?? '', 'kg' ) );
					imd_spec_cell( 'Mould dimensions', $t['mould_dimensions'] ?? '', imd_bare_unit( $t['mould_dimensions'] ?? '', 'mm' ) );
					imd_spec_cell( 'Tool life', $t['tool_life'] ?? '' );
					imd_spec_cell( 'Surface finish', $t['surface_finish'] ?? '' );
					imd_spec_cell( 'Hot-runner zones', $t['hot_runner_zones'] ?? '' );
					imd_spec_cell( 'Mould location', $imd_mould_loc );
					?>
				</div>
			</section>

			<!-- ═══ Part information ═══ -->
			<?php if ( imd_has( $t['part_name'] ?? '' ) || imd_has( $t['part_dimensions'] ?? '' ) || imd_has( $t['part_weight'] ?? '' ) || imd_has( $t['part_description'] ?? '' ) || imd_has( $t['colour'] ?? '' ) || imd_has( $t['tolerance'] ?? '' ) ) : ?>
			<section class="imd-sec">
				<?php imd_section_head( 'part', 'Part information' ); ?>
				<div class="imd-cells imd-cells--3">
					<?php
					imd_spec_cell( 'Part name', $t['part_name'] ?? '' );
					imd_spec_cell( 'Part weight', $t['part_weight'] ?? '', 'g', 'partweight' );
					imd_spec_cell( 'Part dimensions', $t['part_dimensions'] ?? '' );
					imd_spec_cell( 'Tolerance', $t['tolerance'] ?? '' );
					imd_spec_cell( 'Colour', $t['colour'] ?? '' );
					imd_spec_cell( 'Draft angle', $t['draft_angle'] ?? '' );
					?>
				</div>
				<?php if ( imd_has( $t['part_description'] ?? '' ) ) : ?>
				<div class="imd-matgroup imd-matgroup--mt">
					<span class="imd-microlabel">Description</span>
					<p class="imd-cell__v" style="font-weight:500;line-height:1.6;"><?php echo esc_html( trim( (string) $t['part_description'] ) ); ?></p>
				</div>
				<?php endif; ?>
			</section>
			<?php endif; ?>

			<!-- ═══ Production ═══ -->
			<?php if ( imd_has( $t['cycle_time'] ?? '' ) || imd_has( $t['annual_volume'] ?? '' ) || imd_has( $t['min_order_qty'] ?? '' ) || imd_has( $t['required_qty'] ?? '' ) || imd_has( $t['injection_stages'] ?? '' ) || imd_has( $t['packaging'] ?? '' ) || imd_has( $t['material_supplied'] ?? '' ) ) : ?>
			<section class="imd-sec">
				<?php imd_section_head( 'production', 'Production' ); ?>
				<div class="imd-cells imd-cells--3">
					<?php
					imd_spec_cell( 'Required qty', $t['required_qty'] ?? '' );
					imd_spec_cell( 'Min order', $t['min_order_qty'] ?? '' );
					imd_spec_cell( 'Annual volume', $t['annual_volume'] ?? '', '', 'annualvolume' );
					imd_spec_cell( 'Target cycle', $t['cycle_time'] ?? '', '', 'cycle' );
					imd_spec_cell( 'Packaging', $t['packaging'] ?? '' );
					imd_spec_cell( 'Material supplied', $t['material_supplied'] ?? '' );
					imd_spec_cell( 'Injection stages', $t['injection_stages'] ?? '' );
					?>
				</div>
			</section>
			<?php endif; ?>

			<!-- ═══ Materials supported ═══ -->
			<?php if ( $imd_materials ) : ?>
			<?php
			/* Engineering grade derived from presence of engineering polymers. No
			   "recycled" column exists, so it is rendered as a graceful "—". */
			$imd_eng_codes = array( 'PA6', 'PA66', 'PC', 'POM', 'PEEK', 'PEI', 'PET', 'PMMA' );
			$imd_is_eng    = (bool) array_intersect( array_map( 'strtoupper', $imd_materials ), $imd_eng_codes );
			?>
			<section class="imd-sec">
				<?php imd_section_head( 'layers', 'Materials supported' ); ?>
				<div class="imd-matgroup">
					<span class="imd-microlabel" data-tip="material">Processed polymers</span>
					<div class="imd-mats">
						<?php foreach ( $imd_materials as $mat ) : ?>
							<span class="imd-mat"><?php echo esc_html( $mat ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="imd-cells imd-cells--2 imd-matgroup--mt">
					<div class="imd-cell"><span class="imd-cell__k">Engineering grade</span><span class="imd-cell__v"><?php echo $imd_is_eng ? 'Yes' : 'No'; ?></span></div>
					<div class="imd-cell"><span class="imd-cell__k">Recycled materials</span><span class="imd-cell__v"><?php echo imd_yes( $t['recycled'] ?? '' ) ? 'Yes' : '—'; ?></span></div>
				</div>
			</section>
			<?php endif; ?>

			<!-- ═══ Tool features & requirements ═══ -->
			<?php
			$imd_features = array(
				'Water cooled'    => imd_yes( $t['water_cooled'] ?? '' ),
				'Suck-back pump'  => imd_yes( $t['suck_pump'] ?? '' ),
				'Hot-runner ctrl' => imd_has( $t['hot_runner_controller'] ?? '' ),
			);
			$imd_features_on = array_keys( array_filter( $imd_features ) );
			$imd_has_req_specs = imd_has( $t['clamp_force'] ?? '' ) || imd_has( $t['shot_weight'] ?? '' ) || imd_has( $t['tie_bar'] ?? '' ) || imd_has( $t['opening_stroke'] ?? '' ) || imd_has( $t['compatible_specs'] ?? '' );
			?>
			<?php if ( $imd_has_req_specs || $imd_features_on ) : ?>
			<section class="imd-sec">
				<?php imd_section_head( 'wrench', 'Tool features & requirements' ); ?>
				<div class="imd-cells imd-cells--3">
					<?php
					imd_spec_cell( 'Required clamp force', $t['clamp_force'] ?? '', 'T', 'clamp' );
					imd_spec_cell( 'Shot weight', $t['shot_weight'] ?? '', 'g', 'shot' );
					imd_spec_cell( 'Tie-bar (L × W)', $t['tie_bar'] ?? '', 'mm', 'tiebar' );
					imd_spec_cell( 'Opening stroke', $t['opening_stroke'] ?? '', 'mm', 'daylight' );
					imd_spec_cell( 'Compatible machine', $t['compatible_specs'] ?? '' );
					imd_spec_cell( 'Hot-runner controller', $t['hot_runner_controller'] ?? '' );
					?>
				</div>
				<div class="imd-matgroup imd-matgroup--mt">
					<span class="imd-microlabel">Machine requirements</span>
					<div class="imd-chips" id="imtMachineReq"></div>
				</div>
				<?php if ( $imd_features_on ) : ?>
				<div class="imd-matgroup imd-matgroup--mt">
					<span class="imd-microlabel">Tool features</span>
					<div class="imd-chips">
						<?php foreach ( $imd_features_on as $ff ) : ?>
							<span class="imd-chip imd-chip--check"><?php echo imd_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $ff ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			</section>
			<?php endif; ?>

			<!-- ═══ Quality & compliance ═══ -->
			<?php
			$imd_qc      = trim( (string) ( $t['qc_tools'] ?? '' ) );
			$imd_tol_con = trim( (string) ( $t['tolerance_consistency'] ?? '' ) );
			?>
			<?php if ( $imd_compliance_on || imd_has( $imd_qc ) || imd_has( $t['tolerance'] ?? '' ) || imd_has( $imd_tol_con ) ) : ?>
			<section class="imd-sec">
				<?php imd_section_head( 'quality', 'Quality & compliance' ); ?>
				<?php if ( $imd_compliance_on ) : ?>
				<div class="imd-matgroup">
					<span class="imd-microlabel">Certifications</span>
					<div class="imd-chips imd-chips--cert">
						<?php foreach ( $imd_compliance_on as $cc ) : ?>
							<span class="imd-chip imd-chip--cert"><?php echo imd_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $cc ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
				<?php if ( imd_has( $imd_qc ) || imd_has( $t['tolerance'] ?? '' ) || imd_has( $imd_tol_con ) ) : ?>
				<div class="imd-cells imd-cells--2 imd-matgroup--mt">
					<?php
					imd_spec_cell( 'QC tools', $imd_qc );
					imd_spec_cell( 'Tolerance', $t['tolerance'] ?? '' );
					imd_spec_cell( 'Tolerance consistency', $imd_tol_con );
					?>
				</div>
				<?php endif; ?>
			</section>
			<?php endif; ?>

		</div><!-- /.imd-main -->

		<!-- ════════════ RIGHT RAIL (≈40%, sticky) ════════════ -->
		<aside class="imd-rail" aria-label="Summary and request">
			<div class="imd-rail__sticky">

				<!-- Summary / access-tier card -->
				<div class="imd-summary">
					<div class="imd-summary__top">
						<span class="imd-summary__ref"><?php echo esc_html( $imd_ref ); ?></span>
						<span class="imd-summary__badge imd-summary__badge--<?php echo esc_attr( $imd_status_key ); ?>"><span class="dot" aria-hidden="true"></span><?php echo esc_html( $imd_status_label ); ?></span>
					</div>
					<h1 class="imd-summary__title"><?php echo esc_html( $imd_title ); ?></h1>
					<p class="imd-summary__sub">
						<?php
						$bits = array_filter( array( $imd_type, $imd_cond, trim( (string) ( $t['mould_material'] ?? '' ) ) ) );
						echo esc_html( $bits ? implode( ' · ', $bits ) : 'Injection mould tool' );
						?>
					</p>

					<div class="imd-summary__stats imd-summary__stats--2">
						<div class="imd-mini"><span class="imd-mini__k">Cavities</span><span class="imd-mini__v"><?php echo $imd_cav ? '<span class="imd-count" data-to="' . esc_attr( (string) $imd_cav ) . '">0</span>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></span></div>
						<div class="imd-mini"><span class="imd-mini__k">Material</span><span class="imd-mini__v"><?php echo esc_html( $imd_mat_primary ?: ( $imd_materials[0] ?? '—' ) ); ?></span></div>
					</div>

					<?php if ( $imd_loc !== '' || $imd_expiry !== '' ) : ?>
					<p class="imd-summary__loc"><?php echo imd_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php
							$locbits = array();
							if ( $imd_loc !== '' ) {
								$locbits[] = $imd_loc;
							}
							if ( $imd_expiry !== '' ) {
								$locbits[] = 'Available until ' . $imd_expiry;
							}
							echo esc_html( implode( ' · ', $locbits ) );
						?></span></p>
					<?php endif; ?>

					<div class="imd-meter" role="group" aria-label="Spec completeness">
						<div class="imd-meter__head"><span>Spec completeness</span><b><span class="imd-count" data-to="<?php echo esc_attr( (string) $imd_completeness ); ?>">0</span>%</b></div>
						<div class="imd-meter__bar"><span class="imd-meter__fill" style="--imd-meter:<?php echo (int) $imd_completeness; ?>%"></span></div>
					</div>

					<!-- ── Primary CTA (access-tier gated) ── -->
					<div class="imd-cta" id="imdContact">
						<?php if ( $imd_show_admin ) : ?>
							<a class="imd-btn imd-btn--primary" href="<?php echo esc_url( $imd_edit_url ); ?>"><?php echo imd_icon( 'gauge' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Edit listing</span></a>
						<?php elseif ( $imd_unlocked ) : ?>
							<div class="imd-cta__ok"><?php echo imd_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Contact unlocked</span></div>
						<?php elseif ( ! $imd_viewer_id ) : ?>
							<a class="imd-btn imd-btn--primary" href="<?php echo esc_url( $imd_login_url ); ?>"><?php echo imd_icon( 'send' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Log in to request details</span></a>
						<?php elseif ( strtolower( (string) $imd_status ) === 'pending' ) : ?>
							<div class="imd-cta__pending" id="imdReqState"><?php echo imd_icon( 'cycle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Request sent · awaiting approval</span></div>
						<?php else : ?>
							<button type="button" class="imd-btn imd-btn--primary" id="imdRequestBtn"><?php echo imd_icon( 'send' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Request details</span></button>
							<div class="imd-contact__feedback" id="imdReqFeedback" role="status"></div>
						<?php endif; ?>

						<div class="imd-cta__row">
							<button type="button" class="imd-btn imd-btn--ghost imd-fav<?php echo $imd_saved ? ' is-on' : ''; ?>" id="imdFav"
								aria-pressed="<?php echo $imd_saved ? 'true' : 'false'; ?>"
								data-id="<?php echo esc_attr( (string) $imd_id ); ?>"
								data-title="<?php echo esc_attr( $imd_title ); ?>"
								data-image="<?php echo esc_attr( $imd_imgs[0] ?? '' ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
								<span><?php echo $imd_saved ? 'Saved' : 'Save'; ?></span>
							</button>
							<button type="button" class="imd-btn imd-btn--ghost" id="imdShare"
								data-url="<?php echo esc_attr( home_url( '/tool/?id=' . $imd_id ) ); ?>"
								data-title="<?php echo esc_attr( $imd_ref . ' · ' . $imd_title ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
								<span>Share</span>
							</button>
						</div>
						<button type="button" class="imd-btn imd-btn--soft imd-cta__pdf" id="imdPrint">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><rect x="6" y="13" width="12" height="8"/><path d="M6 17H4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2"/></svg>
							<span>Download spec sheet (PDF)</span>
						</button>
					</div>
				</div>

				<?php if ( $imd_unlocked && ! $imd_show_admin ) : ?>
				<!-- Approved requester — owner contact unlocked. -->
				<div class="imd-contact imd-contact--unlocked">
					<div class="imd-contact__status is-ok"><?php echo imd_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Contact unlocked for you</span></div>
					<div class="imd-contact__rows">
						<?php if ( ! empty( $imd_owner['company'] ) ) : ?>
						<div class="imd-crow"><?php echo imd_icon( 'building' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="imd-crow__k">Company</span><span class="imd-crow__v"><?php echo esc_html( $imd_owner['company'] ); ?></span></div>
						<?php endif; ?>
						<?php if ( ! empty( $imd_owner['name'] ) ) : ?>
						<div class="imd-crow"><?php echo imd_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="imd-crow__k">Owner</span><span class="imd-crow__v"><?php echo esc_html( $imd_owner['name'] ); ?></span></div>
						<?php endif; ?>
						<?php if ( ! empty( $imd_owner['email'] ) ) : ?>
						<div class="imd-crow"><?php echo imd_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="imd-crow__k">Email</span><span class="imd-crow__v"><a href="mailto:<?php echo esc_attr( $imd_owner['email'] ); ?>"><?php echo esc_html( $imd_owner['email'] ); ?></a></span></div>
						<?php endif; ?>
						<?php if ( ! empty( $imd_owner['phone'] ) ) : ?>
						<div class="imd-crow"><?php echo imd_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="imd-crow__k">Phone</span><span class="imd-crow__v"><a href="tel:<?php echo esc_attr( preg_replace( '/\s/', '', $imd_owner['phone'] ) ); ?>"><?php echo esc_html( $imd_owner['phone'] ); ?></a></span></div>
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $imd_show_admin ) : ?>
				<!-- ───── Owner & admin only panel (rendered ONLY for owner/admin) ───── -->
				<div class="imd-owner" id="imdOwnerPanel">
					<div class="imd-owner__head"><?php echo imd_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Owner &amp; admin only</span></div>

					<div class="imd-owner__grid">
						<div class="imd-orow"><span class="imd-orow__k">Owner</span><span class="imd-orow__v"><?php echo esc_html( ( $imd_owner['company'] ?? '' ) ?: ( $imd_owner['name'] ?? '—' ) ); ?></span></div>
						<div class="imd-orow"><span class="imd-orow__k">User ref</span><span class="imd-orow__v imd-mono"><?php echo esc_html( $imd_user_ref ); ?></span></div>
						<?php if ( ! empty( $imd_owner['email'] ) ) : ?>
						<div class="imd-orow imd-orow--wide"><span class="imd-orow__k">Contact</span><span class="imd-orow__v"><a href="mailto:<?php echo esc_attr( $imd_owner['email'] ); ?>"><?php echo esc_html( $imd_owner['email'] ); ?></a></span></div>
						<?php endif; ?>
						<?php if ( ! empty( $imd_owner['phone'] ) ) : ?>
						<div class="imd-orow imd-orow--wide"><span class="imd-orow__k">Phone</span><span class="imd-orow__v"><a href="tel:<?php echo esc_attr( preg_replace( '/\s/', '', $imd_owner['phone'] ) ); ?>"><?php echo esc_html( $imd_owner['phone'] ); ?></a></span></div>
						<?php endif; ?>
					</div>

					<div class="imd-owner__requests" id="imdOwnerReqs">
						<?php if ( ! empty( $imd_pending_reqs ) ) : ?>
							<?php foreach ( $imd_pending_reqs as $pr ) : ?>
							<div class="imd-oreq" data-req="<?php echo esc_attr( (string) $pr['id'] ); ?>">
								<p class="imd-oreq__lbl"><b><?php echo esc_html( (string) count( $imd_pending_reqs ) ); ?> request<?php echo count( $imd_pending_reqs ) === 1 ? '' : 's'; ?> pending</b> — <?php echo esc_html( $pr['name'] ); ?></p>
								<?php if ( $imd_is_admin ) : ?>
								<div class="imd-oreq__btns">
									<button type="button" class="imd-btn imd-btn--ok imd-oreq__act" data-do="approve" data-req="<?php echo esc_attr( (string) $pr['id'] ); ?>">Approve</button>
									<button type="button" class="imd-btn imd-btn--soft imd-oreq__act" data-do="deny" data-req="<?php echo esc_attr( (string) $pr['id'] ); ?>">Deny</button>
								</div>
								<?php else : ?>
								<p class="imd-oreq__await"><?php echo imd_icon( 'cycle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Awaiting admin approval</span></p>
								<?php endif; ?>
							</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p class="imd-owner__norq">No pending requests.</p>
						<?php endif; ?>
					</div>

					<div class="imd-owner__manage">
						<a class="imd-btn imd-btn--ghost" href="<?php echo esc_url( $imd_edit_url ); ?>"><?php echo imd_icon( 'gauge' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Edit listing</span></a>
						<?php if ( $imd_is_admin ) : ?>
						<button type="button" class="imd-btn imd-btn--danger-soft" id="imdRemoveBtn" data-id="<?php echo esc_attr( (string) $imd_id ); ?>"><?php echo imd_icon( 'cross' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Remove</span></button>
						<?php elseif ( $imd_is_owner ) : ?>
						<button type="button" class="imd-btn imd-btn--danger-soft" id="imdRemoveBtn" data-id="<?php echo esc_attr( (string) $imd_id ); ?>" data-owner="1"><?php echo imd_icon( 'cross' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Remove</span></button>
						<?php endif; ?>
					</div>

					<div class="imd-owner__meta">
						<div class="imd-orow"><span class="imd-orow__k">Status</span><span class="imd-orow__v imd-orow__v--<?php echo esc_attr( $imd_status_key ); ?>"><?php echo esc_html( ucfirst( $imd_status_key ) ); ?></span></div>
						<div class="imd-orow"><span class="imd-orow__k">Listing strength</span><span class="imd-orow__v imd-orow__v--strength"><?php echo (int) $imd_strength; ?>%</span></div>
					</div>
				</div>
				<?php endif; ?>

				<?php
				/* ── Messaging thread panel — owner/admin only. ──────────────────
				 * Policy: a public/requester user can never see a direct line to
				 * the listing owner. The owner thread box is rendered ONLY for the
				 * listing owner and admins; requesters use the admin-gated
				 * "Request details" flow instead (resolver returns null for them,
				 * and this role guard is belt-and-braces). */
				if ( $imd_rmsg && in_array( ( $imd_rmsg['role'] ?? '' ), array( 'owner', 'admin' ), true ) && function_exists( 'ih_rmsg_render_detail_panel' ) ) {
					$imd_rmsg_inbox = $imd_is_admin
						? admin_url( 'admin.php?page=ih-request-messages' )
						: admin_url( 'admin.php?page=ih-user-request-messages' );
					echo ih_rmsg_render_detail_panel( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within renderer
						(int) $imd_rmsg['request_id'],
						$imd_viewer_id,
						array(
							'inbox_url'   => $imd_rmsg_inbox,
							'extra_count' => (int) ( $imd_rmsg['count'] ?? 1 ),
						)
					);
				}
				?>

				<?php if ( ! $imd_unlocked ) : ?>
				<!-- Owner identity private — public-safe copy, no owner data -->
				<div class="imd-private">
					<span class="imd-private__icon"><?php echo imd_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<div class="imd-private__txt">
						<span class="imd-private__title">Owner identity is private</span>
						<span class="imd-private__sub">Request details to connect — the owner or an admin can approve and unlock contact. Listings are anonymised by <b><?php echo esc_html( $imd_ref ); ?></b>.</span>
					</div>
				</div>
				<?php endif; ?>

				<!-- Similar tools -->
				<?php if ( ! empty( $imd_similar ) ) : ?>
				<div class="imd-similar">
					<h2 class="imd-similar__title"><?php echo imd_icon( 'compare' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Similar tools</h2>
					<div class="imd-similar__list">
						<?php foreach ( $imd_similar as $c ) {
							imt_similar_card( $c );
						} ?>
					</div>
				</div>
				<?php endif; ?>

			</div><!-- /.imd-rail__sticky -->
		</aside>

	</div><!-- /.imd-layout -->
</div><!-- /.imd-shell -->

<!-- Mobile sticky request bar -->
<div class="imd-stickybar" id="imdStickyBar" aria-hidden="false">
	<div class="imd-stickybar__info">
		<span class="imd-stickybar__ref"><?php echo esc_html( $imd_ref ); ?></span>
		<span class="imd-stickybar__clamp"><?php echo $imd_cav ? esc_html( (string) $imd_cav ) . ' cav' : ''; ?></span>
	</div>
	<?php if ( $imd_show_admin ) : ?>
		<a class="imd-btn imd-btn--primary" href="<?php echo esc_url( $imd_edit_url ); ?>"><span>Edit listing</span></a>
	<?php elseif ( $imd_unlocked ) : ?>
		<span class="imd-btn imd-btn--primary is-static"><?php echo imd_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Contact unlocked</span></span>
	<?php elseif ( ! $imd_viewer_id ) : ?>
		<a class="imd-btn imd-btn--primary" href="<?php echo esc_url( $imd_login_url ); ?>"><span>Log in to request</span></a>
	<?php elseif ( strtolower( (string) $imd_status ) === 'pending' ) : ?>
		<span class="imd-btn imd-btn--primary is-static">Awaiting approval</span>
	<?php else : ?>
		<button type="button" class="imd-btn imd-btn--primary" id="imdRequestBtnMobile"><span>Request details</span></button>
	<?php endif; ?>
</div>

<?php if ( ! $imd_unlocked && $imd_viewer_id && strtolower( (string) $imd_status ) !== 'pending' ) : ?>
<!-- Request listing details modal — logged-in NON-owner who hasn't requested yet only. -->
<div class="imd-modal" id="imdModal" hidden>
	<div class="imd-modal__scrim" data-close="1"></div>
	<div class="imd-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="imdModalTitle">
		<button type="button" class="imd-modal__x" data-close="1" aria-label="Close"><?php echo imd_icon( 'cross' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<h2 class="imd-modal__title" id="imdModalTitle">Request listing details</h2>
		<p class="imd-modal__body">The owner sees your <b>company name and message</b>. Their identity and contact stay private until the request is approved.</p>
		<div class="imd-modal__chip">
			<span class="imd-modal__chipimg" aria-hidden="true">
				<?php if ( ! empty( $imd_imgs[0] ) ) : ?>
					<img src="<?php echo esc_url( $imd_imgs[0] ); ?>" alt="">
				<?php else : ?>
					<?php echo imd_icon( 'mould' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
			</span>
			<span class="imd-modal__chiptxt">
				<span class="imd-modal__chipref"><?php echo esc_html( $imd_ref ); ?></span>
				<span class="imd-modal__chiptitle"><?php echo esc_html( $imd_title ); ?></span>
			</span>
		</div>
		<div class="imd-field imd-field--wide imd-modal__msg">
			<label for="imdReqMessage">Your message</label>
			<textarea id="imdReqMessage" rows="3" maxlength="600" placeholder="We're interested in this 4-cavity PP tool for a ~50k/month run…"></textarea>
		</div>
		<div class="imd-modal__actions">
			<button type="button" class="imd-btn imd-btn--ghost" data-close="1">Cancel</button>
			<button type="button" class="imd-btn imd-btn--primary" id="imdModalConfirm"><?php echo imd_icon( 'send' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Send request</span></button>
		</div>
		<div class="imd-modal__feedback" id="imdModalFeedback" role="status"></div>
	</div>
</div>
<?php endif; ?>

<?php if ( ! empty( $imd_imgs ) ) : ?>
<!-- Image lightbox (keyboard-accessible, Esc to close, focus-trapped). -->
<div class="imd-lightbox" id="imdLightbox" hidden>
	<div class="imd-lightbox__scrim" data-lbclose="1"></div>
	<div class="imd-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Tool image viewer">
		<button type="button" class="imd-lightbox__x" data-lbclose="1" aria-label="Close image viewer"><?php echo imd_icon( 'cross' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<?php if ( count( $imd_imgs ) > 1 ) : ?>
		<button type="button" class="imd-lightbox__nav imd-lightbox__nav--prev" id="imdLbPrev" aria-label="Previous image"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
		<button type="button" class="imd-lightbox__nav imd-lightbox__nav--next" id="imdLbNext" aria-label="Next image"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
		<?php endif; ?>
		<figure class="imd-lightbox__fig">
			<img id="imdLightboxImg" class="imd-lightbox__img" src="" alt="<?php echo esc_attr( $imd_title ); ?>">
			<?php if ( count( $imd_imgs ) > 1 ) : ?>
			<figcaption class="imd-lightbox__count" id="imdLbCount" aria-live="polite"></figcaption>
			<?php endif; ?>
		</figure>
	</div>
</div>
<?php endif; ?>

</main>

<?php get_footer(); ?>
