<?php
/**
 * Template Name: Machine Detail
 *
 * PUBLIC machine DETAIL page — anonymised "premium spec sheet".
 * Figma source of truth: VfzCieeZ8ebjwm6vPiGajl page 4:2
 *   frames "Machine Detail / Desktop / 1440", "Machine Detail / Mobile / 390",
 *   "Machine — Access tiers & request flow".
 *
 * Route: /machine/?id={ID}  (WP page slug "machine" assigned this template).
 *        The browse cards (page-machines.php) link here via home_url('/machine/?id='.$id).
 *
 * ── Access tiers (resolved 100% server-side; gated data never reaches public DOM) ──
 *   1. Anonymous            → browse public spec sheet; any action prompts login.
 *   2. Logged-in (requester)→ can Request details; owner contact stays hidden until approved.
 *   3. Owner                → sees own contact panel unlocked.
 *   4. Admin                → sees contact panel unlocked.
 *   Owner identity / exact contact / internal notes / admin actions are NEVER printed
 *   for tiers 1–2: the markup that contains them is wrapped in `if ( $imd_unlocked )`.
 *   Admin approve/deny + internal notes live in the admin dashboard, not in this template.
 *
 * Data: wp_ih_machines (reuses ih-redesign-helpers.php + plugin query/status/owner helpers).
 * Assets: enqueued from the theme functions.php, scoped to this template (filemtime busting):
 *   assets/css/im-machine-detail.css + assets/js/im-listing-detail.js (localized as IMD_DATA).
 */

get_header();

global $wpdb;

$imd_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$m = $imd_id ? $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}ih_machines WHERE id = %d",
	$imd_id
), ARRAY_A ) : null;

if ( ! $m ) {
	wp_safe_redirect( home_url( '/machines/' ) );
	exit;
}

/* ── Viewer tier resolution (server-side) ─────────────────────────────────── */
$imd_owner_id  = (int) ( $m['owner_id'] ?? 0 );
$imd_viewer_id = get_current_user_id();
$imd_is_admin  = current_user_can( 'manage_options' );
$imd_is_owner  = ( $imd_viewer_id && $imd_viewer_id === $imd_owner_id );

$imd_status = function_exists( 'ih_listing_contact_status' )
	? ih_listing_contact_status( $imd_viewer_id, $imd_id, 'machine' )
	: 'None';
$imd_approved = ( strtolower( (string) $imd_status ) === 'approved' );
$imd_unlocked = ( $imd_is_admin || $imd_is_owner || $imd_approved );

/* Request-messaging entry point — a thread exists only for an APPROVED request.
 * Resolves the relevant thread for this viewer (their approved request, or the
 * owner's most-recent approved request on this listing). */
$imd_rmsg = ( $imd_viewer_id && function_exists( 'ih_rmsg_detail_thread_for_viewer' ) )
	? ih_rmsg_detail_thread_for_viewer( 'machine', $imd_id, $imd_viewer_id )
	: null;

/* ── Public visibility gate — mirror the browse query (approved + non-expired).
 *    Hide pending/expired listings from the public; owner/admin may always view. */
$imd_is_expired = function_exists( 'ih_listing_is_expired' ) ? ih_listing_is_expired( $m ) : false;
$imd_has_approved_request = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$wpdb->prefix}ih_requests
	 WHERE listing_id = %d AND listing_type IN ('machine','ih_contact_machine','machine_contact')
	 AND LOWER(TRIM(status)) = 'approved'",
	$imd_id
) );
$imd_public_visible = ( ( (int) ( $m['available'] ?? 0 ) === 1 || $imd_has_approved_request ) && ! $imd_is_expired );

if ( ! $imd_public_visible && ! $imd_is_owner && ! $imd_is_admin ) {
	wp_safe_redirect( home_url( '/machines/' ) );
	exit;
}

/* ── Helpers (scoped to this template; guarded) ───────────────────────────── */
if ( ! function_exists( 'imd_num' ) ) {
	/** First number out of a free-text spec value, e.g. "120 T" -> 120.0 */
	function imd_num( $v ) {
		if ( preg_match( '/-?\d+(?:\.\d+)?/', (string) $v, $mm ) ) {
			return (float) $mm[0];
		}
		return 0.0;
	}
}
if ( ! function_exists( 'imd_two_nums' ) ) {
	/** Two numbers out of "470 x 470" / "470x470mm" -> [470,470]; single -> [n,n]. */
	function imd_two_nums( $v ) {
		preg_match_all( '/-?\d+(?:\.\d+)?/', (string) $v, $mm );
		$n = array_map( 'floatval', $mm[0] ?? array() );
		if ( count( $n ) >= 2 ) {
			return array( $n[0], $n[1] );
		}
		if ( count( $n ) === 1 ) {
			return array( $n[0], $n[0] );
		}
		return array( 0.0, 0.0 );
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
	/** Section / inline icon set (2px stroke, rounded) — §12 iconography. */
	function imd_icon( $name ) {
		$p = array(
			'core'        => '<circle cx="12" cy="12" r="9"/><path d="M12 12l4-2.5"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/>',
			'part'        => '<path d="M21 7.5 12 12 3 7.5 12 3z"/><path d="M3 7.5v9L12 21V12"/><path d="M21 7.5v9L12 21"/>',
			'production'  => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
			'materials'   => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
			'automation'  => '<rect x="5" y="9" width="14" height="10" rx="2"/><path d="M12 9V5M9 5h6"/><circle cx="9" cy="14" r="1.2"/><circle cx="15" cy="14" r="1.2"/>',
			'quality'     => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
			'clamp'       => '<path d="M4 6v12M20 6v12M4 9h5v6H4zM20 9h-5v6h5z"/>',
			'shot'        => '<path d="M12 2s5 6 5 11a5 5 0 0 1-10 0c0-5 5-11 5-11z"/>',
			'screw'       => '<path d="M6 4h12M6 8h12M6 12h12M6 16h12M6 20h12"/>',
			'pin'         => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
			'calendar'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
			'cert'        => '<path d="M12 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M8.5 13.5 7 22l5-3 5 3-1.5-8.5"/>',
			'gauge'       => '<path d="M12 13l4-3"/><path d="M4 18a8 8 0 1 1 16 0"/><circle cx="12" cy="18" r="1.4"/>',
			'fit'         => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/>',
			'cycle'       => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/>',
			'match'       => '<path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/>',
			'lock'        => '<rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
			'send'        => '<path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7z"/>',
			'check'       => '<path d="m5 12 5 5 9-9"/>',
			'cross'       => '<path d="M18 6 6 18M6 6l12 12"/>',
			'user'        => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'mail'        => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
			'phone'       => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1A19.5 19.5 0 0 1 4.7 13 19.8 19.8 0 0 1 1.6 4.4 2 2 0 0 1 3.6 2.2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L7.9 11a16 16 0 0 0 6 6l1.2-1.4a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
			'building'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 22V12h6v10M9 7h.01M15 7h.01M9 10h.01M15 10h.01"/>',
			'star'        => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
			'compare'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
		);
		$d = $p[ $name ] ?? $p['core'];
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
	}
}
if ( ! function_exists( 'imd_section_head' ) ) {
	function imd_section_head( $icon, $title ) {
		echo '<div class="imd-sec__head"><span class="imd-sec__icon">' . imd_icon( $icon ) . '</span><h2 class="imd-sec__title">' . esc_html( $title ) . '</h2></div>';
	}
}

/* ── Derived display values ───────────────────────────────────────────────── */
$imd_ref   = function_exists( 'ih_listing_ref' ) ? ih_listing_ref( $m, 'machine' ) : ( 'MCH-' . str_pad( (string) $imd_id, 5, '0', STR_PAD_LEFT ) );
$imd_title = trim( (string) ( $m['title'] ?? '' ) ) ?: ( 'Machine · ' . $imd_ref );
$imd_type  = trim( (string) ( $m['machine_type'] ?? '' ) );
$imd_year  = trim( (string) ( $m['year_manufacture'] ?? '' ) );
$imd_loc   = trim( (string) ( $m['location'] ?? '' ) );

$imd_status_meta = function_exists( 'ih_listing_status_meta' )
	? ih_listing_status_meta( $m )
	: array( 'key' => 'available', 'label' => 'Available' );
$imd_status_key   = $imd_status_meta['key'] ?? 'available';
$imd_status_label = ( $imd_status_key === 'available' ) ? 'Available' : ( $imd_status_meta['label'] ?? 'Pending' );

$imd_materials = function_exists( 'ih_machine_materials' ) ? ih_machine_materials( $m ) : array();
$imd_materials = array_values( array_filter( array_map( 'trim', (array) $imd_materials ) ) );

/* Numeric specs (for gauges + match/fit) */
$imd_clamp    = imd_num( $m['clamping_force'] ?? '' );
$imd_shot     = imd_num( $m['shot_size'] ?? '' );
$imd_screw    = imd_num( $m['screw_diameter'] ?? '' );
$imd_pressure = imd_num( $m['max_injection_pressure'] ?? '' );
$imd_util     = imd_num( $m['utilization'] ?? '' );
$imd_cycle    = imd_num( $m['avg_cycle_time'] ?? '' );
$imd_ophours  = imd_num( $m['operating_hours'] ?? '' );
$imd_monthly  = imd_num( $m['max_monthly_output'] ?? '' );
$imd_tiebar   = imd_two_nums( $m['tie_bar_spacing'] ?? '' );
$imd_mh_max   = imd_num( $m['max_mould_height'] ?? '' );
$imd_mh_min   = imd_num( $m['min_mould_height'] ?? '' );
$imd_partwt   = imd_num( $m['max_part_weight'] ?? '' );

/* Gallery — render every non-empty image slot (image_1..image_5). */
$imd_imgs = array();
for ( $imd_ii = 1; $imd_ii <= 5; $imd_ii++ ) {
	$imd_src = trim( (string) ( $m[ "image_{$imd_ii}" ] ?? '' ) );
	if ( $imd_src !== '' ) {
		$imd_imgs[] = $imd_src;
	}
}
$imd_imgs = array_values( $imd_imgs );

/* Spec-completeness meter — share of key public fields that are filled. */
$imd_completeness_fields = array(
	'clamping_force', 'shot_size', 'screw_diameter', 'max_injection_pressure', 'tie_bar_spacing',
	'max_mould_height', 'min_mould_height', 'opening_stroke', 'clamp_drive_type', 'toggle_clamp_type',
	'max_part_weight', 'max_part_dimensions', 'tolerance', 'batch_size', 'min_order_qty',
	'max_monthly_output', 'avg_cycle_time', 'operating_hours', 'utilization', 'automation_level',
	'certifications', 'qc_tools', 'tolerance_consistency',
);
$imd_filled = 0;
foreach ( $imd_completeness_fields as $f ) {
	if ( imd_has( $m[ $f ] ?? '' ) ) {
		$imd_filled++;
	}
}
if ( ! empty( $imd_materials ) ) {
	$imd_filled++;
}
$imd_completeness = (int) round( $imd_filled / ( count( $imd_completeness_fields ) + 1 ) * 100 );

/* Dates */
$imd_listed = ( ! empty( $m['listing_date'] ) && $m['listing_date'] !== '0000-00-00' ) ? date_i18n( 'j M Y', strtotime( (string) $m['listing_date'] ) ) : '';
$imd_expiry = ( ! empty( $m['expiry_date'] ) && $m['expiry_date'] !== '0000-00-00' ) ? date_i18n( 'j M Y', strtotime( (string) $m['expiry_date'] ) ) : '';

/* Owner contact data — ONLY fetched when unlocked, so it can never leak to public DOM. */
$imd_owner = ( $imd_unlocked && function_exists( 'ih_listing_owner_data' ) ) ? ih_listing_owner_data( $imd_owner_id ) : array();

/* Login URL preserves return-to-detail. */
$imd_login_url = home_url( '/register/?tab=login&redirect_to=' . rawurlencode( home_url( '/machine/?id=' . $imd_id ) ) );
if ( function_exists( 'im_auth_url' ) ) {
	$imd_login_url = add_query_arg( 'redirect_to', rawurlencode( home_url( '/machine/?id=' . $imd_id ) ), im_auth_url( 'login' ) );
}

/* Saved (favourite) state */
$imd_saved   = false;
$imd_wishlist = $imd_viewer_id ? get_user_meta( $imd_viewer_id, 'ih_wishlist', true ) : array();
if ( is_array( $imd_wishlist ) ) {
	foreach ( $imd_wishlist as $w ) {
		if ( (int) ( $w['id'] ?? 0 ) === $imd_id && ( $w['type'] ?? '' ) === 'machine' ) {
			$imd_saved = true;
			break;
		}
	}
}

/* ── Similar machines: material overlap + clamp ±40% + same region, public-safe ── */
$imd_similar = array();
$imd_not_expired = function_exists( 'ih_listing_not_expired_sql' ) ? ih_listing_not_expired_sql( 's.expiry_date' ) : '1=1';
$imd_candidates = $wpdb->get_results( $wpdb->prepare(
	"SELECT s.* FROM {$wpdb->prefix}ih_machines s
	 WHERE s.id <> %d AND s.available = 1 AND {$imd_not_expired}
	 ORDER BY s.id DESC LIMIT 24",
	$imd_id
), ARRAY_A ) ?: array();
$imd_region_key = strtolower( trim( (string) ( preg_split( '/[,\/]/', $imd_loc )[0] ?? '' ) ) );
foreach ( $imd_candidates as $c ) {
	$score = 0;
	$cmats = function_exists( 'ih_machine_materials' ) ? ih_machine_materials( $c ) : array();
	if ( array_intersect( array_map( 'strtoupper', $imd_materials ), array_map( 'strtoupper', (array) $cmats ) ) ) {
		$score += 2;
	}
	$cclamp = imd_num( $c['clamping_force'] ?? '' );
	if ( $imd_clamp > 0 && $cclamp > 0 && abs( $cclamp - $imd_clamp ) <= $imd_clamp * 0.4 ) {
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

/* Small renderers ---------------------------------------------------------- */
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
if ( ! function_exists( 'imd_flag_row' ) ) {
	function imd_flag_row( $label, $on ) {
		$cls = $on ? 'is-yes' : 'is-no';
		$ic  = $on ? imd_icon( 'check' ) : imd_icon( 'cross' );
		echo '<div class="imd-flag ' . $cls . '"><span>' . esc_html( $label ) . '</span><span class="imd-flag__i" aria-label="' . esc_attr( $on ? 'Yes' : 'No' ) . '">' . $ic . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
if ( ! function_exists( 'imd_gauge' ) ) {
	/** Chart.js doughnut gauge: the canvas is painted client-side from data-* attrs. */
	function imd_gauge( $label, $value, $max, $unit, $key ) {
		$value = max( 0, (float) $value );
		$max   = max( 1, (float) $max );
		$pct   = min( 100, round( $value / $max * 100, 1 ) );
		$disp  = ( $value == (int) $value ) ? (string) (int) $value : (string) round( $value, 1 );
		$range = '0–' . ( ( $max == (int) $max ) ? (string) (int) $max : (string) round( $max, 1 ) ) . ( $unit ? ' ' . $unit : '' );
		?>
		<figure class="imd-gauge" data-gauge="<?php echo esc_attr( $key ); ?>" data-value="<?php echo esc_attr( (string) $value ); ?>" data-max="<?php echo esc_attr( (string) $max ); ?>" data-pct="<?php echo esc_attr( (string) $pct ); ?>" data-unit="<?php echo esc_attr( $unit ); ?>" data-label="<?php echo esc_attr( $label ); ?>">
			<span class="imd-gauge__canvas"><canvas aria-hidden="true"></canvas>
				<span class="imd-gauge__inner">
					<span class="imd-gauge__val"><span class="imd-count" data-to="<?php echo esc_attr( $disp ); ?>">0</span><small><?php echo esc_html( $unit ); ?></small></span>
					<span class="imd-gauge__range"><?php echo esc_html( $range ); ?></span>
				</span>
			</span>
			<figcaption class="imd-gauge__cap">
				<span class="imd-gauge__lbl"><?php echo esc_html( $label ); ?></span>
			</figcaption>
		</figure>
		<?php
	}
}
if ( ! function_exists( 'imd_similar_card' ) ) {
	function imd_similar_card( $c ) {
		$cid  = (int) ( $c['id'] ?? 0 );
		$cref = function_exists( 'ih_listing_ref' ) ? ih_listing_ref( $c, 'machine' ) : ( 'MCH-' . str_pad( (string) $cid, 5, '0', STR_PAD_LEFT ) );
		$ctit = trim( (string) ( $c['title'] ?? '' ) ) ?: ( 'Machine · ' . $cref );
		$cimg = trim( (string) ( $c['image_1'] ?? '' ) );
		$cclamp = imd_num( $c['clamping_force'] ?? '' );
		$cloc   = trim( (string) ( $c['location'] ?? '' ) );
		?>
		<a class="imd-sim" href="<?php echo esc_url( home_url( '/machine/?id=' . $cid ) ); ?>">
			<span class="imd-sim__media">
				<?php if ( $cimg ) : ?>
					<img src="<?php echo esc_url( $cimg ); ?>" alt="<?php echo esc_attr( $cref ); ?>" loading="lazy">
				<?php endif; ?>
			</span>
			<span class="imd-sim__body">
				<span class="imd-sim__ref"><?php echo esc_html( $cref ); ?></span>
				<span class="imd-sim__title"><?php echo esc_html( $ctit ); ?></span>
			</span>
			<?php if ( $cclamp ) : ?>
			<span class="imd-sim__ton"><?php echo esc_html( (string) (int) $cclamp ); ?> T</span>
			<?php endif; ?>
		</a>
		<?php
	}
}

$imd_default_img = get_template_directory_uri() . '/assets/images/Services/Injection-Moulding.png';

/* ── Owner / admin gated data (computed ONLY when unlocked as owner/admin) ───
 * None of this is localized to JS or printed unless $imd_show_admin is true, so
 * it can never reach the anonymous / pending DOM. */
$imd_show_admin   = ( $imd_is_admin || $imd_is_owner );
$imd_user_ref     = function_exists( 'ih_user_ref' ) ? ih_user_ref( $imd_owner_id ) : ( 'USR-' . str_pad( (string) $imd_owner_id, 5, '0', STR_PAD_LEFT ) );
$imd_edit_url     = $imd_is_admin
	? admin_url( 'admin.php?page=ih-edit-machine&machine_id=' . $imd_id )
	: admin_url( 'admin.php?page=ih-user-edit-machine&machine_id=' . $imd_id );
$imd_pending_reqs = array();
if ( $imd_show_admin ) {
	$imd_pending_rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT id, user_id, request_date FROM {$wpdb->prefix}ih_requests
		 WHERE listing_id = %d
		 AND listing_type IN ('machine','ih_contact_machine','machine_contact')
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
/* Listing-strength score reuses the public spec-completeness signal. */
$imd_strength = $imd_completeness;

/* ── Interactive-spec popover copy (plain mould-injection meaning) ──────────
 * Explanatory text only — the value + percentile come from captured data. The
 * "vs typical" bar + percentile badge are filled client-side from IMD_DATA.specStats. */
$imd_spec_meaning = array(
	'clamping_force'         => 'The force the press holds the mould shut with. It must beat the part’s projected area × cavity pressure, or you get flash.',
	'shot_size'              => 'The screw’s injection / metering stroke — how far it travels to push melt into the mould each shot.',
	'screw_diameter'         => 'Screw bore. A larger Ø plasticises more material and lifts output, but trades away peak injection pressure.',
	'max_injection_pressure' => 'Peak pressure available to fill the cavity. Thin walls and long flow paths need more of it.',
	'max_monthly_output'     => 'Sustained output the owner rates this press for, across typical operating hours.',
	'max_part_weight'        => 'The heaviest single part this press is rated to mould in one shot.',
	'avg_cycle_time'         => 'Typical door-to-door moulding cycle. A lower number means more parts per hour.',
	'utilization'            => 'Share of available time the press is actually running production rather than idle.',
);
$imd_spec_unitword = array(
	'clamping_force'         => 'tonnes clamp',
	'shot_size'              => 'mm stroke',
	'screw_diameter'         => 'mm bore',
	'max_injection_pressure' => 'bar peak',
	'max_monthly_output'     => 'units / month',
	'max_part_weight'        => 'g per shot',
	'avg_cycle_time'         => 'second cycle',
	'utilization'            => '% utilised',
);

if ( ! function_exists( 'imd_spec_cell' ) ) { /* (defined above) */ }
/* Interactive variant of a spec cell: renders a focusable button wired for the
 * popover when we have meaning copy + the field is filled; otherwise a plain cell. */
if ( ! function_exists( 'imd_spec_cell_x' ) ) {
	function imd_spec_cell_x( $label, $value, $spec_key, $meaning, $unitword ) {
		$value = trim( (string) $value );
		if ( ! imd_has( $value ) ) {
			return;
		}
		if ( $meaning === '' ) {
			imd_spec_cell( $label, $value );
			return;
		}
		$num      = imd_num( $value );
		$numdisp  = ( $num == (int) $num ) ? number_format( (int) $num ) : number_format( $num, 1 );
		echo '<button type="button" class="imd-cell imd-spec" data-spec="' . esc_attr( $spec_key ) . '"'
			. ' data-pop-title="' . esc_attr( $label ) . '"'
			. ' data-pop-text="' . esc_attr( $meaning ) . '"'
			. ' data-pop-value="' . esc_attr( $numdisp . ' ' . $unitword ) . '"'
			. ' aria-haspopup="dialog">'
			. '<span class="imd-cell__k">' . esc_html( $label ) . ' <span class="imd-spec__i" aria-hidden="true">' . imd_icon( 'gauge' ) . '</span></span>'
			. '<span class="imd-cell__v">' . esc_html( $value ) . '</span>'
			. '</button>';
	}
}
?>

<main class="imd-page" id="imdPage">
<div class="imd-shell">

	<!-- Breadcrumb -->
	<nav class="imd-crumb" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( home_url( '/machines/' ) ); ?>">Browse</a>
		<span class="sep" aria-hidden="true">/</span>
		<a class="imd-crumb__cat" href="<?php echo esc_url( home_url( '/machines/' ) ); ?>">Machines</a>
		<span class="sep" aria-hidden="true">/</span>
		<span aria-current="page"><?php echo esc_html( $imd_ref ); ?></span>
	</nav>

	<div class="imd-layout">

		<!-- ════════════ LEFT (≈60%) ════════════ -->
		<div class="imd-main">

			<!-- Gallery -->
			<section class="imd-gallery" aria-label="Machine images">
				<div class="imd-gallery__stage">
					<?php if ( ! empty( $imd_imgs ) ) : ?>
						<button type="button" class="imd-gallery__zoom" id="imdCoverBtn" data-index="0" aria-label="<?php echo esc_attr( 'Open larger view of ' . $imd_title ); ?>" aria-haspopup="dialog">
							<img id="imdCover" class="imd-gallery__cover" src="<?php echo esc_url( $imd_imgs[0] ); ?>" alt="<?php echo esc_attr( $imd_title ); ?>" onerror="this.onerror=null;this.src='<?php echo esc_js( $imd_default_img ); ?>'">
							<span class="imd-gallery__zoomhint" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/></svg></span>
						</button>
					<?php else : ?>
						<span class="imd-gallery__placeholder" aria-hidden="true"><?php echo imd_icon( 'core' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
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
				<?php endif; ?>
			</section>

			<!-- ═══ Match for your job — weighted score donut + checklist + inline requirement ═══ -->
			<?php
			/* Collapsible requirement panel default: collapsed once the viewer has a saved
			   profile, expanded for first-time viewers so they discover the inputs. */
			$imd_has_req = false;
			if ( is_user_logged_in() ) {
				$imd_saved_req = get_user_meta( get_current_user_id(), 'ih_requirement_profile', true );
				$imd_has_req   = ( is_array( $imd_saved_req ) && ! empty( $imd_saved_req ) );
			}
			?>
			<section class="imd-sec imd-match" id="imdMatch">
				<div class="imd-sec__head imd-match__head">
					<span class="imd-sec__icon"><?php echo imd_icon( 'match' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<div class="imd-match__headtxt">
						<h2 class="imd-sec__title">Match for your job</h2>
						<p class="imd-match__sub">Scored against your saved requirement profile</p>
					</div>
					<button type="button" class="imd-match__edit" id="imdEditReq" aria-expanded="<?php echo $imd_has_req ? 'false' : 'true'; ?>" aria-controls="imdReqPanel"><?php echo imd_icon( 'gauge' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Edit requirement</span><svg class="imd-match__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg></button>
				</div>
				<div class="imd-match__body">
					<div class="imd-match__score">
						<div class="imd-match__donut">
							<canvas id="imdMatchChart" aria-hidden="true"></canvas>
							<div class="imd-match__center">
								<span class="imd-match__pct"><span id="imdMatchPct">—</span><small>%</small></span>
								<span class="imd-match__clbl">match</span>
							</div>
						</div>
						<span class="imd-match__pill" id="imdMatchPill" data-state="none">Set your job to score</span>
					</div>
					<ul class="imd-match__checks" id="imdMatchChecks" aria-live="polite">
						<li class="imd-empty-hint">Add your requirement below to score this machine against your job.</li>
					</ul>
				</div>
				<div class="imd-match__req<?php echo $imd_has_req ? '' : ' is-open'; ?>" id="imdReqPanel"<?php echo $imd_has_req ? ' hidden' : ''; ?> aria-hidden="<?php echo $imd_has_req ? 'true' : 'false'; ?>">
					<div class="imd-match__reqinner">
						<div class="imd-match__reqpad">
					<p class="imd-match__reqlabel">Your requirement — edit to recalculate the match</p>
					<form class="imd-match__grid" id="imdReqInlineForm" onsubmit="return false;">
						<div class="imd-field"><label for="imdRiMould">Mould L × W × H (mm)</label><input type="text" id="imdRiMould" placeholder="e.g. 420 × 380 × 300"></div>
						<div class="imd-field"><label for="imdRiPartWt">Part weight (g)</label><input type="number" id="imdRiPartWt" min="0" step="1" inputmode="numeric" placeholder="e.g. 180"></div>
						<div class="imd-field"><label for="imdRiMaterial">Material</label>
							<select id="imdRiMaterial">
								<option value="">Select…</option>
								<?php foreach ( array( 'ABS', 'PP', 'PE', 'PA6', 'PA66', 'PC', 'POM', 'PMMA', 'PEEK', 'TPU', 'TPE', 'PS', 'SAN', 'Other' ) as $opt ) : ?>
									<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="imd-field"><label for="imdRiTonnage">Required tonnage (T)</label><input type="number" id="imdRiTonnage" min="0" step="1" inputmode="numeric" placeholder="e.g. 100"></div>
						<div class="imd-field"><label for="imdRiProjArea">Projected area (cm²)</label><input type="number" id="imdRiProjArea" min="0" step="0.1" inputmode="decimal" placeholder="e.g. 250"><small class="imd-field__hint">Total projected area of your part. With cavity pressure it computes a precise required tonnage (overrides the figure above).</small></div>
						<div class="imd-field"><label for="imdRiCavPress">Cavity pressure (bar)</label><input type="number" id="imdRiCavPress" min="0" step="1" inputmode="numeric" placeholder="e.g. 350"></div>
						<div class="imd-field"><label for="imdRiVolume">Monthly volume</label><input type="number" id="imdRiVolume" min="0" step="100" inputmode="numeric" placeholder="e.g. 200,000"></div>
						<div class="imd-match__gridcta"><button type="button" class="imd-btn imd-btn--primary" id="imdReqUpdate"><?php echo imd_icon( 'cycle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Update match</span></button></div>
					</form>
					<div class="imd-match__fb" id="imdReqInlineFb" role="status"></div>
						</div>
					</div>
				</div>
			</section>

			<!-- ═══ Capability fingerprint — draggable radar vs requirement ═══ -->
			<section class="imd-sec imd-radar" id="imdRadar">
				<div class="imd-sec__head imd-sec__head--badge">
					<span class="imd-sec__icon"><?php echo imd_icon( 'match' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<div class="imd-radar__headtxt">
						<h2 class="imd-sec__title">Capability fingerprint</h2>
						<p class="imd-radar__subt">This machine vs your requirement</p>
					</div>
					<span class="imd-est-badge" title="The fit assessment is estimated against your inputs">Estimated</span>
				</div>
				<div class="imd-radar__grid">
					<div class="imd-radar__chartwrap">
						<div class="imd-radar__canvas"><canvas id="imdRadarChart" aria-label="Capability radar: machine vs your requirement" role="img"></canvas></div>
						<span class="imd-radar__drag"><?php echo imd_icon( 'core' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Drag any point to set your needs</span>
					</div>
					<div class="imd-radar__side">
						<div class="imd-radar__legend">
							<span class="imd-radar__leg"><i class="imd-radar__legsw imd-radar__legsw--machine"></i>This machine</span>
							<span class="imd-radar__leg"><i class="imd-radar__legsw imd-radar__legsw--req"></i>Your requirement</span>
						</div>

						<!-- Meets box (green) — flips to gaps (red) when a need exceeds the machine -->
						<div class="imd-outcome is-meets" id="imdOutcome" aria-live="polite">
							<p class="imd-outcome__head" id="imdOutcomeHead">Exceeds your needs on</p>
							<ul class="imd-outcome__list" id="imdOutcomeList"></ul>
							<p class="imd-outcome__still" id="imdOutcomeStill" hidden></p>
						</div>

						<!-- Per-axis accessible steppers (keyboard equivalent to dragging) -->
						<details class="imd-axisctl" id="imdAxisCtl">
							<summary>Set needs with number inputs</summary>
							<div class="imd-axisctl__grid" id="imdAxisCtlGrid"><!-- filled by JS --></div>
							<button type="button" class="imd-btn imd-btn--ghost imd-axisctl__save" id="imdReqSaveInline"><?php echo imd_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Save my job profile</span></button>
							<div class="imd-axisctl__fb" id="imdAxisCtlFb" role="status"></div>
						</details>
					</div>
				</div>
			</section>

			<!-- Capacity at a glance (4 Chart.js gauges) -->
			<section class="imd-sec">
				<?php imd_section_head( 'gauge', 'Capacity at a glance' ); ?>
				<div class="imd-gauges">
					<?php
					imd_gauge( 'Clamp force', $imd_clamp, 500, 'T', 'clamp' );
					imd_gauge( 'Shot size', $imd_shot, 250, 'mm', 'shot' );
					imd_gauge( 'Screw Ø', $imd_screw, 120, 'mm', 'screw' );
					imd_gauge( 'Utilisation', $imd_util, 100, '%', 'util' );
					?>
				</div>
			</section>

			<!-- Will it fit your mould? -->
			<?php
			$imd_tiebar_txt = ( $imd_tiebar[0] > 0 ) ? ( ( $imd_tiebar[0] == (int) $imd_tiebar[0] ? (int) $imd_tiebar[0] : $imd_tiebar[0] ) . ' × ' . ( $imd_tiebar[1] == (int) $imd_tiebar[1] ? (int) $imd_tiebar[1] : $imd_tiebar[1] ) . ' mm' ) : '';
			$imd_mh_min_txt = $imd_mh_min > 0 ? (string) (int) $imd_mh_min : '';
			$imd_mh_max_txt = $imd_mh_max > 0 ? (string) (int) $imd_mh_max : '';
			?>
			<section class="imd-sec imd-fit" id="imdFit">
				<?php imd_section_head( 'fit', 'Will your mould fit this machine?' ); ?>
				<div class="imd-fit__top">
					<div class="imd-fit__mould">
						<div class="imd-fit__mouldbox"><span>Your mould</span></div>
						<?php if ( $imd_tiebar_txt ) : ?>
						<span class="imd-fit__mouldcap">Tie-bar window · <?php echo esc_html( $imd_tiebar_txt ); ?></span>
						<?php endif; ?>
					</div>
					<div class="imd-fit__range">
						<span class="imd-fit__rangelbl">Shut height range</span>
						<div class="imd-fit__track" aria-hidden="true"><span class="imd-fit__fill"></span><span class="imd-fit__knob imd-fit__knob--min"></span><span class="imd-fit__knob imd-fit__knob--max"></span></div>
						<div class="imd-fit__ends"><span>Min <?php echo $imd_mh_min_txt ? esc_html( $imd_mh_min_txt ) . ' mm' : '—'; ?></span><span>Max <?php echo $imd_mh_max_txt ? esc_html( $imd_mh_max_txt ) . ' mm' : '—'; ?></span></div>
						<p class="imd-fit__help">Your mould fits if its footprint is ≤ <?php echo $imd_tiebar_txt ? esc_html( $imd_tiebar_txt ) : 'the tie-bar window'; ?> and shut height is <?php echo ( $imd_mh_min_txt && $imd_mh_max_txt ) ? esc_html( $imd_mh_min_txt . '–' . $imd_mh_max_txt ) . ' mm' : 'within the machine range'; ?>.</p>
						<button type="button" class="imd-btn imd-btn--ghost imd-fit__check" id="imdFitCheck" aria-expanded="false" aria-controls="imdFitPanel"><?php echo imd_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Check your mould</span></button>
					</div>
				</div>
				<div class="imd-fit__panel" id="imdFitPanel" hidden>
					<form class="imd-fit__form" id="imdFitForm" onsubmit="return false;">
						<div class="imd-field"><label for="imdFitL">Mould length (mm)</label><input type="number" id="imdFitL" min="0" step="1" inputmode="numeric" placeholder="e.g. 400"></div>
						<div class="imd-field"><label for="imdFitW">Mould width (mm)</label><input type="number" id="imdFitW" min="0" step="1" inputmode="numeric" placeholder="e.g. 400"></div>
						<div class="imd-field"><label for="imdFitH">Shut height (mm)</label><input type="number" id="imdFitH" min="0" step="1" inputmode="numeric" placeholder="e.g. 300"></div>
						<div class="imd-field"><label for="imdFitWt">Part weight (g)</label><input type="number" id="imdFitWt" min="0" step="1" inputmode="numeric" placeholder="e.g. 60"></div>
						<div class="imd-field"><label for="imdFitProjArea">Projected area (cm²)</label><input type="number" id="imdFitProjArea" min="0" step="0.1" inputmode="decimal" placeholder="e.g. 250"></div>
						<div class="imd-field"><label for="imdFitCavPress">Cavity pressure (bar)</label><input type="number" id="imdFitCavPress" min="0" step="1" inputmode="numeric" placeholder="e.g. 350"></div>
					</form>
					<ul class="imd-fit__rows" id="imdFitRows" aria-live="polite">
						<li class="imd-empty-hint">Enter your mould footprint, shut height and part weight to check fit against this machine's tie-bar spacing, mould-height range and shot capacity.</li>
					</ul>
				</div>
			</section>

			<!-- Production cycle & throughput (Chart.js doughnut) -->
			<section class="imd-sec imd-cycle" id="imdCycle">
				<div class="imd-sec__head imd-sec__head--badge">
					<span class="imd-sec__icon"><?php echo imd_icon( 'cycle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h2 class="imd-sec__title">Production cycle &amp; throughput</h2>
					<span class="imd-est-badge" title="Calculated from entered figures, not measured">Estimated</span>
				</div>
				<div class="imd-cycle__grid">
					<div class="imd-cycle__ring">
						<div class="imd-cycle__canvas"><canvas id="imdCycleChart" aria-label="Cycle phase breakdown" role="img"></canvas></div>
						<div class="imd-cycle__center">
							<span class="imd-cycle__t"><span class="imd-count" data-to="<?php echo esc_attr( (string) ( $imd_cycle ?: 0 ) ); ?>">0</span><small>s</small></span>
							<span class="imd-cycle__sub">cycle</span>
						</div>
					</div>
					<div class="imd-cycle__side">
						<div class="imd-cycle__stats">
							<div class="imd-cstat"><span class="imd-cstat__v">≈ <span class="imd-count" id="imdPerDay" data-to="0">0</span></span><span class="imd-cstat__k">Parts / day</span></div>
							<div class="imd-cstat"><span class="imd-cstat__v" id="imdPerYear">—</span><span class="imd-cstat__k">Parts / year</span></div>
							<div class="imd-cstat"><span class="imd-cstat__v"><?php echo $imd_ophours ? esc_html( (string) (int) $imd_ophours ) . ' <small>h</small>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="imd-cstat__k">Per day</span></div>
							<div class="imd-cstat"><span class="imd-cstat__v"><?php echo $imd_util ? esc_html( (string) (int) $imd_util ) . '<small>%</small>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="imd-cstat__k">Utilisation</span></div>
						</div>
						<ul class="imd-cycle__legend">
							<li><span class="imd-dot" data-seg="0"></span>Clamp</li>
							<li><span class="imd-dot" data-seg="1"></span>Inject</li>
							<li><span class="imd-dot" data-seg="2"></span>Cool</li>
							<li><span class="imd-dot" data-seg="3"></span>Eject</li>
						</ul>
						<p class="imd-cycle__note">Estimated from your entered monthly output, cycle time and operating hours — calculated, not measured.</p>
					</div>
				</div>
			</section>

			<!-- ───── Spec sections (values open the interactive popover) ───── -->

			<!-- 1. Core processing specs -->
			<section class="imd-sec">
				<?php imd_section_head( 'core', 'Core processing specs' ); ?>
				<div class="imd-cells imd-cells--3">
					<?php
					imd_spec_cell_x( 'Clamping force', $m['clamping_force'] ?? '', 'clamping_force', $imd_spec_meaning['clamping_force'], $imd_spec_unitword['clamping_force'] );
					imd_spec_cell_x( 'Shot size', $m['shot_size'] ?? '', 'shot_size', $imd_spec_meaning['shot_size'], $imd_spec_unitword['shot_size'] );
					imd_spec_cell_x( 'Screw diameter', $m['screw_diameter'] ?? '', 'screw_diameter', $imd_spec_meaning['screw_diameter'], $imd_spec_unitword['screw_diameter'] );
					imd_spec_cell_x( 'Max injection pressure', $m['max_injection_pressure'] ?? '', 'max_injection_pressure', $imd_spec_meaning['max_injection_pressure'], $imd_spec_unitword['max_injection_pressure'] );
					imd_spec_cell( 'Tie-bar spacing', $m['tie_bar_spacing'] ?? '', '', 'tiebar' );
					imd_spec_cell( 'Max mould height', $m['max_mould_height'] ?? '', '', 'daylight' );
					imd_spec_cell( 'Min mould height', $m['min_mould_height'] ?? '' );
					/* opening_stroke IS captured by Add Machine (ih-add-machine-form.php) + stored — safe to display. */
					imd_spec_cell( 'Opening stroke', $m['opening_stroke'] ?? '', '', 'daylight' );
					imd_spec_cell( 'Clamp & drive type', $m['clamp_drive_type'] ?? '', '', 'clampdrive' );
					imd_spec_cell( 'Toggle clamp type', $m['toggle_clamp_type'] ?? '', '', 'toggletype' );
					?>
				</div>
			</section>

			<!-- 2. Part capability -->
			<section class="imd-sec">
				<?php imd_section_head( 'part', 'Part capability' ); ?>
				<div class="imd-cells imd-cells--3">
					<?php
					imd_spec_cell_x( 'Max part weight', $m['max_part_weight'] ?? '', 'max_part_weight', $imd_spec_meaning['max_part_weight'], $imd_spec_unitword['max_part_weight'] );
					imd_spec_cell( 'Max part dimensions', $m['max_part_dimensions'] ?? '' );
					imd_spec_cell( 'Tolerance', $m['tolerance'] ?? '' );
					?>
				</div>
			</section>

			<!-- 3. Production -->
			<section class="imd-sec">
				<?php imd_section_head( 'production', 'Production' ); ?>
				<div class="imd-cells imd-cells--3">
					<?php
					imd_spec_cell( 'Batch size', $m['batch_size'] ?? '' );
					imd_spec_cell( 'Min order qty', $m['min_order_qty'] ?? '' );
					imd_spec_cell_x( 'Max monthly output', $m['max_monthly_output'] ?? '', 'max_monthly_output', $imd_spec_meaning['max_monthly_output'], $imd_spec_unitword['max_monthly_output'] );
					imd_spec_cell_x( 'Avg cycle time', $m['avg_cycle_time'] ?? '', 'avg_cycle_time', $imd_spec_meaning['avg_cycle_time'], $imd_spec_unitword['avg_cycle_time'] );
					imd_spec_cell( 'Operating hours', $m['operating_hours'] ?? '' );
					imd_spec_cell_x( 'Utilisation', $m['utilization'] ?? '', 'utilization', $imd_spec_meaning['utilization'], $imd_spec_unitword['utilization'] );
					?>
				</div>
			</section>

			<!-- 4. Materials supported -->
			<?php if ( $imd_materials || imd_has( $m['engineering_grade'] ?? '' ) || imd_has( $m['recycled_materials'] ?? '' ) ) : ?>
			<section class="imd-sec">
				<?php imd_section_head( 'materials', 'Materials supported' ); ?>
				<?php if ( $imd_materials ) : ?>
				<div class="imd-matgroup">
					<span class="imd-microlabel" data-tip="material">Processed polymers</span>
					<div class="imd-mats">
						<?php foreach ( $imd_materials as $mat ) : ?>
							<span class="imd-mat"><?php echo esc_html( $mat ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
				<div class="imd-cells imd-cells--2">
					<div class="imd-cell"><span class="imd-cell__k">Engineering grade</span><span class="imd-cell__v"><?php echo imd_yes( $m['engineering_grade'] ?? '' ) ? 'Yes' : 'No'; ?></span></div>
					<div class="imd-cell"><span class="imd-cell__k">Recycled materials</span><span class="imd-cell__v"><?php echo imd_yes( $m['recycled_materials'] ?? '' ) ? 'Yes' : 'No'; ?></span></div>
				</div>
			</section>
			<?php endif; ?>

			<!-- 5. Automation & advanced -->
			<section class="imd-sec">
				<?php imd_section_head( 'automation', 'Automation & advanced capabilities' ); ?>
				<div class="imd-cells imd-cells--3">
					<div class="imd-cell"><span class="imd-cell__k">Automation level</span><span class="imd-cell__v"><?php echo imd_has( $m['automation_level'] ?? '' ) ? esc_html( trim( (string) $m['automation_level'] ) ) : '—'; ?></span></div>
					<div class="imd-cell"><span class="imd-cell__k">Robot integration</span><span class="imd-cell__v"><?php echo imd_yes( $m['robot_integration'] ?? '' ) ? 'Yes' : 'No'; ?></span></div>
					<div class="imd-cell"><span class="imd-cell__k">Multi-cavity</span><span class="imd-cell__v"><?php echo imd_yes( $m['multi_cavity'] ?? '' ) ? 'Yes' : 'No'; ?></span></div>
				</div>
				<?php
				$imd_specialist = array(
					'Overmoulding'       => imd_yes( $m['overmoulding'] ?? '' ),
					'Insert moulding'    => imd_yes( $m['insert_moulding'] ?? '' ),
					'In-mould labelling' => imd_yes( $m['iml'] ?? '' ),
					'Gas-assisted'       => imd_yes( $m['gas_assisted'] ?? '' ),
					'Thin-wall'          => imd_yes( $m['thin_wall'] ?? '' ),
				);
				$imd_specialist_on = array_keys( array_filter( $imd_specialist ) );
				?>
				<?php if ( $imd_specialist_on ) : ?>
				<div class="imd-matgroup imd-matgroup--mt">
					<span class="imd-microlabel">Specialist processes</span>
					<div class="imd-chips">
						<?php foreach ( $imd_specialist_on as $sp ) : ?>
							<span class="imd-chip imd-chip--check"><?php echo imd_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $sp ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			</section>

			<!-- 6. Quality & compliance -->
			<?php if ( imd_has( $m['certifications'] ?? '' ) || imd_has( $m['qc_tools'] ?? '' ) || imd_has( $m['tolerance_consistency'] ?? '' ) ) : ?>
			<section class="imd-sec">
				<?php imd_section_head( 'quality', 'Quality & compliance' ); ?>
				<?php
				$imd_certs = preg_split( '/[,\/|]+/', (string) ( $m['certifications'] ?? '' ) );
				$imd_certs = array_values( array_filter( array_map( 'trim', $imd_certs ) ) );
				?>
				<?php if ( $imd_certs ) : ?>
				<div class="imd-matgroup">
					<span class="imd-microlabel">Certifications</span>
					<div class="imd-chips imd-chips--cert">
						<?php foreach ( $imd_certs as $cert ) : ?>
							<span class="imd-chip imd-chip--cert"><?php echo imd_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $cert ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
				<?php if ( imd_has( $m['qc_tools'] ?? '' ) || imd_has( $m['tolerance_consistency'] ?? '' ) ) : ?>
				<div class="imd-cells imd-cells--2 imd-matgroup--mt">
					<?php
					imd_spec_cell( 'QC tools', $m['qc_tools'] ?? '' );
					imd_spec_cell( 'Tolerance consistency', $m['tolerance_consistency'] ?? '' );
					?>
				</div>
				<?php endif; ?>
			</section>
			<?php endif; ?>

		</div><!-- /.imd-main -->

		<!-- ════════════ RIGHT RAIL (≈40%, sticky) ════════════ -->
		<aside class="imd-rail" aria-label="Summary and request">
			<div class="imd-rail__sticky">

				<!-- Summary / access-tier card (matches Figma "Access tiers" card) -->
				<div class="imd-summary">
					<div class="imd-summary__top">
						<span class="imd-summary__ref"><?php echo esc_html( $imd_ref ); ?></span>
						<span class="imd-summary__badge imd-summary__badge--<?php echo esc_attr( $imd_status_key ); ?>"><span class="dot" aria-hidden="true"></span><?php echo esc_html( $imd_status_label ); ?></span>
					</div>
					<h1 class="imd-summary__title"><?php echo esc_html( $imd_title ); ?></h1>
					<p class="imd-summary__sub">
						<?php
						$bits = array();
						if ( $imd_type !== '' ) {
							$bits[] = $imd_type . ' press';
						}
						if ( imd_has( $m['clamp_drive_type'] ?? '' ) ) {
							$bits[] = trim( (string) $m['clamp_drive_type'] );
						} elseif ( imd_has( $m['toggle_clamp_type'] ?? '' ) ) {
							$bits[] = trim( (string) $m['toggle_clamp_type'] );
						}
						if ( $imd_year !== '' ) {
							$bits[] = $imd_year;
						}
						echo esc_html( implode( ' · ', $bits ) );
						?>
					</p>

					<div class="imd-summary__stats imd-summary__stats--2">
						<div class="imd-mini"><span class="imd-mini__k">Clamp force</span><span class="imd-mini__v"><?php echo $imd_clamp ? '<span class="imd-count" data-to="' . esc_attr( (string) (int) $imd_clamp ) . '">0</span> <small>T</small>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></span></div>
						<div class="imd-mini"><span class="imd-mini__k">Shot size</span><span class="imd-mini__v"><?php echo $imd_shot ? '<span class="imd-count" data-to="' . esc_attr( (string) (int) $imd_shot ) . '">0</span> <small>mm</small>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></span></div>
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
							<?php /* Owner/admin — self-contact-request is nonsensical, so the primary
							        action opens the requirement/match editor instead of a dead contact button. */ ?>
							<button type="button" class="imd-btn imd-btn--primary" id="imdReqOpenBtn"><?php echo imd_icon( 'gauge' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Edit job profile</span></button>
						<?php elseif ( $imd_unlocked ) : ?>
							<?php /* Approved requester — contact unlocked for them on this listing. */ ?>
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
								data-url="<?php echo esc_attr( home_url( '/machine/?id=' . $imd_id ) ); ?>"
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
				<!-- Approved requester — owner contact unlocked (no admin actions). Rendered only when approved. -->
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
				<!-- ───── Owner & admin only panel (rendered ONLY for owner/admin; never in public DOM) ───── -->
				<div class="imd-owner" id="imdOwnerPanel">
					<div class="imd-owner__head"><?php echo imd_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Owner &amp; admin only</span></div>

					<div class="imd-owner__grid">
						<div class="imd-orow"><span class="imd-orow__k">Owner</span><span class="imd-orow__v"><?php echo esc_html( $imd_owner['company'] ?: ( $imd_owner['name'] ?? '—' ) ); ?></span></div>
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
						<?php else : ?>
						<button type="button" class="imd-btn imd-btn--danger-soft" id="imdRemoveBtn" data-id="<?php echo esc_attr( (string) $imd_id ); ?>" data-owner="1"><?php echo imd_icon( 'cross' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Remove</span></button>
						<?php endif; ?>
					</div>

					<div class="imd-owner__meta">
						<div class="imd-orow"><span class="imd-orow__k">Status</span><span class="imd-orow__v imd-orow__v--<?php echo esc_attr( $imd_status_key ); ?>"><?php echo esc_html( ucfirst( $imd_status_key ) ); ?></span></div>
						<div class="imd-orow"><span class="imd-orow__k">Listing strength</span><span class="imd-orow__v imd-orow__v--strength"><?php echo (int) $imd_strength; ?>%</span></div>
					</div>

					<?php
					/* Internal notes — owner/admin only, never in public DOM. Real column
					 * (wp_ih_machines.internal_notes) read server-side inside this gated block. */
					$imd_internal_notes = trim( (string) ( $m['internal_notes'] ?? '' ) );
					?>
					<div class="imd-owner__notes">
						<span class="imd-orow__k">Internal notes</span>
						<?php if ( $imd_internal_notes !== '' ) : ?>
						<p class="imd-owner__notestxt"><?php echo nl2br( esc_html( $imd_internal_notes ) ); ?></p>
						<?php else : ?>
						<p class="imd-owner__notestxt">No internal notes recorded for this listing.</p>
						<?php endif; ?>
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

				<!-- Similar machines -->
				<?php if ( ! empty( $imd_similar ) ) : ?>
				<div class="imd-similar">
					<h2 class="imd-similar__title"><?php echo imd_icon( 'compare' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Similar machines</h2>
					<div class="imd-similar__list">
						<?php foreach ( $imd_similar as $c ) {
							imd_similar_card( $c );
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
		<span class="imd-stickybar__clamp"><?php echo $imd_clamp ? esc_html( (string) (int) $imd_clamp ) . ' T' : ''; ?></span>
	</div>
	<?php if ( $imd_unlocked ) : ?>
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
<!-- Request listing details modal — logged-in NON-owner who hasn't requested yet only.
     Owner/admin never get a self-contact modal (they shouldn't request their own listing). -->
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
					<?php echo imd_icon( 'core' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
			</span>
			<span class="imd-modal__chiptxt">
				<span class="imd-modal__chipref"><?php echo esc_html( $imd_ref ); ?></span>
				<span class="imd-modal__chiptitle"><?php echo esc_html( $imd_title ); ?></span>
			</span>
		</div>
		<div class="imd-field imd-field--wide imd-modal__msg">
			<label for="imdReqMessage">Your message</label>
			<textarea id="imdReqMessage" rows="3" maxlength="600" placeholder="We need 120T capacity for an ABS housing, ~50k/month…"></textarea>
		</div>
		<div class="imd-modal__actions">
			<button type="button" class="imd-btn imd-btn--ghost" data-close="1">Cancel</button>
			<button type="button" class="imd-btn imd-btn--primary" id="imdModalConfirm"><?php echo imd_icon( 'send' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <span>Send request</span></button>
		</div>
		<div class="imd-modal__feedback" id="imdModalFeedback" role="status"></div>
	</div>
</div>

<?php endif; ?>

<?php if ( $imd_viewer_id ) : ?>
<!-- Requirement (job profile) modal — available to ALL logged-in viewers (owner/admin
     included) so the match/fit editor is never a dead button. No gated data here. -->
<div class="imd-modal" id="imdReqModal" hidden>
	<div class="imd-modal__scrim" data-close="1"></div>
	<div class="imd-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="imdReqModalTitle">
		<button type="button" class="imd-modal__x" data-close="1" aria-label="Close"><?php echo imd_icon( 'cross' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<h2 class="imd-modal__title" id="imdReqModalTitle">Your job profile</h2>
		<p class="imd-modal__body">Saved to your account so every machine can be scored against it.</p>
		<form class="imd-reqform" id="imdReqForm" onsubmit="return false;">
			<div class="imd-field"><label for="imdReqTonnage">Required clamp (T)</label><input type="number" id="imdReqTonnage" min="0" step="1" inputmode="numeric"></div>
			<div class="imd-field"><label for="imdReqShot">Shot weight (g)</label><input type="number" id="imdReqShot" min="0" step="1" inputmode="numeric"></div>
			<div class="imd-field"><label for="imdReqMaterial">Material</label><input type="text" id="imdReqMaterial" placeholder="e.g. ABS"></div>
			<div class="imd-field"><label for="imdReqVolume">Monthly volume</label><input type="number" id="imdReqVolume" min="0" step="100" inputmode="numeric"></div>
			<div class="imd-field"><label for="imdReqMouldL">Mould L (mm)</label><input type="number" id="imdReqMouldL" min="0" step="1" inputmode="numeric"></div>
			<div class="imd-field"><label for="imdReqMouldW">Mould W (mm)</label><input type="number" id="imdReqMouldW" min="0" step="1" inputmode="numeric"></div>
			<div class="imd-field"><label for="imdReqMouldH">Mould H (mm)</label><input type="number" id="imdReqMouldH" min="0" step="1" inputmode="numeric"></div>
			<div class="imd-field"><label for="imdReqPartWt">Part weight (g)</label><input type="number" id="imdReqPartWt" min="0" step="1" inputmode="numeric"></div>
			<div class="imd-field"><label for="imdReqProjArea">Projected area (cm²)</label><input type="number" id="imdReqProjArea" min="0" step="0.1" inputmode="decimal"></div>
			<div class="imd-field"><label for="imdReqCavPress">Cavity pressure (bar)</label><input type="number" id="imdReqCavPress" min="0" step="1" inputmode="numeric"></div>
			<div class="imd-field imd-field--wide"><label for="imdReqLocation">Location</label><input type="text" id="imdReqLocation" placeholder="City or region"></div>
		</form>
		<div class="imd-modal__actions">
			<button type="button" class="imd-btn imd-btn--ghost" data-close="1">Cancel</button>
			<button type="button" class="imd-btn imd-btn--primary" id="imdReqSave"><span>Save &amp; match</span></button>
		</div>
		<div class="imd-modal__feedback" id="imdReqSaveFeedback" role="status"></div>
	</div>
</div>
<?php endif; ?>

<!-- Shared spec popover (filled by JS; positioned near the trigger) -->
<div class="imd-pop" id="imdPop" role="tooltip" hidden>
	<span class="imd-pop__label" id="imdPopLabel"></span>
	<b class="imd-pop__value" id="imdPopValue"></b>
	<p class="imd-pop__text" id="imdPopText"></p>
	<div class="imd-pop__vs" id="imdPopVs">
		<span class="imd-pop__vslabel">vs typical listed presses</span>
		<div class="imd-pop__bar"><span class="imd-pop__fill" id="imdPopFill"></span><span class="imd-pop__dot" id="imdPopDot"></span></div>
		<div class="imd-pop__ends"><span id="imdPopMin"></span><span id="imdPopMax"></span></div>
	</div>
	<span class="imd-pop__tag" id="imdPopTag"></span>
</div>

<?php if ( ! empty( $imd_imgs ) ) : ?>
<!-- Image lightbox (keyboard-accessible, Esc to close, focus-trapped). Sources read from the gallery. -->
<div class="imd-lightbox" id="imdLightbox" hidden>
	<div class="imd-lightbox__scrim" data-lbclose="1"></div>
	<div class="imd-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Machine image viewer">
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
