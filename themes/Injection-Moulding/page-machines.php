<?php
/**
 * Template Name: All Machines Listing
 *
 * Public machine BROWSE page — "Find injection moulding capacity".
 * Figma source of truth: VfzCieeZ8ebjwm6vPiGajl node 419:2154.
 *
 * Page URL: /machines  (create a WP page titled "Machines", assign this template).
 *
 * Data: wp_ih_machines (anonymised — owner identity never exposed; refs are MCH-#####).
 * Visibility: only approved + non-expired listings are shown publicly. Reuses the
 * plugin's expiry/status helpers (ih_listing_not_expired_sql / ih_listing_status_meta).
 */

get_header();

global $wpdb;

/* ─────────────────────────────────────────────────────────────────────────
 * 1. Query — approved + NON-EXPIRED only (public-safe, anonymised)
 * ──────────────────────────────────────────────────────────────────────── */
$ih_not_expired = function_exists( 'ih_listing_not_expired_sql' )
	? ih_listing_not_expired_sql( 'm.expiry_date' )
	: $wpdb->prepare(
		"(m.expiry_date IS NULL OR m.expiry_date = '0000-00-00' OR m.expiry_date >= %s)",
		current_time( 'Y-m-d' )
	);

$mh_machines = $wpdb->get_results(
	"SELECT DISTINCT m.* FROM {$wpdb->prefix}ih_machines m
	 WHERE {$ih_not_expired}
	 AND (
	     m.available = 1
	     OR EXISTS (
	         SELECT 1 FROM {$wpdb->prefix}ih_requests r
	         WHERE r.listing_id = m.id
	         AND r.listing_type = 'machine'
	         AND LOWER(TRIM(r.status)) = 'approved'
	     )
	 )
	 ORDER BY m.id DESC",
	ARRAY_A
) ?: array();

$mh_total = count( $mh_machines );

/* ─────────────────────────────────────────────────────────────────────────
 * 2. Helpers + facet aggregation
 * ──────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'mh_num' ) ) {
	/** Pull an integer out of a free-text spec value e.g. "120 T" -> 120. */
	function mh_num( $v ) {
		$n = (int) preg_replace( '/[^0-9]/', '', (string) $v );
		return $n;
	}
}
if ( ! function_exists( 'mh_yes' ) ) {
	function mh_yes( $v ) {
		$v = strtolower( trim( (string) $v ) );
		return ( $v === 'yes' || $v === '1' || $v === 'true' );
	}
}

$mh_material_counts = array();   // code => count
$mh_cert_set        = array();   // lowercase => label

foreach ( $mh_machines as $m ) {
	$mats = function_exists( 'ih_machine_materials' ) ? ih_machine_materials( $m ) : array();
	foreach ( (array) $mats as $code ) {
		$code = trim( (string) $code );
		if ( $code === '' ) {
			continue;
		}
		$mh_material_counts[ $code ] = ( $mh_material_counts[ $code ] ?? 0 ) + 1;
	}
	foreach ( preg_split( '/\s*,\s*/', (string) ( $m['certifications'] ?? '' ) ) as $cert ) {
		$cert = trim( $cert );
		if ( $cert !== '' ) {
			$mh_cert_set[ strtolower( $cert ) ] = $cert;
		}
	}
}
arsort( $mh_material_counts );
$mh_materials_ordered = array_keys( $mh_material_counts );

/* Advanced-capability + automation filter definitions (column => label) */
$mh_automation_defs = array(
	'robot_integration' => 'Robot integration',
	'multi_cavity'      => 'Multi-cavity',
	'lights_out'        => 'Lights-out capable',
);
$mh_advanced_defs = array(
	'overmoulding'    => 'Overmoulding',
	'insert_moulding' => 'Insert moulding',
	'iml'             => 'In-mould labelling',
	'gas_assisted'    => 'Gas-assisted',
	'thin_wall'       => 'Thin-wall',
);
$mh_cert_defs = array( 'ISO 9001', 'ISO 13485', 'Medical', 'Food grade' );

$mh_user_id   = get_current_user_id();
$mh_wishlist  = $mh_user_id ? get_user_meta( $mh_user_id, 'ih_wishlist', true ) : array();
$mh_saved_ids = array();
if ( is_array( $mh_wishlist ) ) {
	foreach ( $mh_wishlist as $w ) {
		if ( isset( $w['id'] ) ) {
			$mh_saved_ids[ (int) $w['id'] ] = true;
		}
	}
}

/* ─────────────────────────────────────────────────────────────────────────
 * 3. Single-card renderer (real data → Figma card). Scoped to this template.
 * ──────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'mh_render_card' ) ) {
	function mh_render_card( $m, $saved_ids ) {
		$id  = (int) ( $m['id'] ?? 0 );
		$ref = function_exists( 'ih_listing_ref' )
			? ih_listing_ref( $m, 'machine' )
			: 'MCH-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );

		$status = function_exists( 'ih_listing_status_meta' )
			? ih_listing_status_meta( $m )
			: array( 'key' => ! empty( $m['available'] ) ? 'available' : 'pending', 'label' => ! empty( $m['available'] ) ? 'Available' : 'Pending' );
		$status_key = $status['key'] ?? 'available';
		$pill_class = ( $status_key === 'available' ) ? '' : ( $status_key === 'expired' ? ' is-expired' : ' is-pending' );
		$pill_label = ( $status_key === 'available' ) ? 'Available' : ( $status['label'] ?? 'Pending' );

		$title = trim( (string) ( $m['title'] ?? '' ) ) ?: ( 'Machine · ' . $ref );
		$type  = trim( (string) ( $m['machine_type'] ?? '' ) );
		$year  = trim( (string) ( $m['year_manufacture'] ?? '' ) );
		$loc   = trim( (string) ( $m['location'] ?? '' ) );

		/* subtitle: "Hydraulic press · 5-point toggle" */
		$sub_bits = array();
		if ( $type !== '' ) {
			$sub_bits[] = ( stripos( $type, 'electric' ) !== false ) ? 'All-electric press' : ( ucfirst( strtolower( $type ) ) . ' press' );
		} else {
			$sub_bits[] = 'Injection press';
		}
		$clamp_setup = trim( (string) ( $m['toggle_clamp_type'] ?? '' ) ) ?: trim( (string) ( $m['clamp_drive_type'] ?? '' ) );
		if ( $clamp_setup !== '' ) {
			$sub_bits[] = $clamp_setup;
		}
		$subtitle = implode( ' · ', $sub_bits );

		/* specs */
		$clamp_n = mh_num( $m['clamping_force'] ?? '' );
		$shot_n  = mh_num( $m['shot_size'] ?? '' );
		$screw_n = mh_num( $m['screw_diameter'] ?? '' );

		/* materials */
		$materials = function_exists( 'ih_machine_materials' ) ? ih_machine_materials( $m ) : array();
		$materials = array_values( array_filter( array_map( 'trim', (array) $materials ) ) );

		/* tags = certs + capability badges */
		$tags = array();
		foreach ( preg_split( '/\s*,\s*/', (string) ( $m['certifications'] ?? '' ) ) as $c ) {
			$c = trim( $c );
			if ( $c !== '' ) {
				$tags[] = $c;
			}
		}
		if ( mh_yes( $m['robot_integration'] ?? '' ) ) { $tags[] = 'Robot'; }
		if ( mh_yes( $m['thin_wall'] ?? '' ) )        { $tags[] = 'Thin-wall'; }
		if ( mh_yes( $m['overmoulding'] ?? '' ) )     { $tags[] = 'Overmoulding'; }
		$tags = array_values( array_unique( $tags ) );

		/* expiry "Until 13 Sep" */
		$until = '';
		if ( ! empty( $m['expiry_date'] ) && $m['expiry_date'] !== '0000-00-00' ) {
			$ts = strtotime( (string) $m['expiry_date'] );
			if ( $ts ) {
				$until = 'Until ' . date_i18n( 'j M', $ts );
			}
		}

		$img        = trim( (string) ( $m['image_1'] ?? '' ) );
		$detail_url = home_url( '/machine/?id=' . $id );
		$is_saved   = isset( $saved_ids[ $id ] );

		/* automation / advanced flags for filtering */
		$auto_flags = array();
		if ( mh_yes( $m['robot_integration'] ?? '' ) ) { $auto_flags[] = 'robot_integration'; }
		if ( mh_yes( $m['multi_cavity'] ?? '' ) )      { $auto_flags[] = 'multi_cavity'; }
		if ( stripos( (string) ( $m['automation_level'] ?? '' ), 'fully' ) !== false ) { $auto_flags[] = 'lights_out'; }

		$adv_flags = array();
		foreach ( array( 'overmoulding', 'insert_moulding', 'iml', 'gas_assisted', 'thin_wall' ) as $f ) {
			if ( mh_yes( $m[ $f ] ?? '' ) ) {
				$adv_flags[] = $f;
			}
		}
		if ( mh_yes( $m['recycled_materials'] ?? '' ) ) { $adv_flags[] = 'recycled'; }

		$cert_keys = array();
		foreach ( $tags as $t ) {
			$cert_keys[] = strtolower( $t );
		}

		$ts_sort   = ! empty( $m['listing_date'] ) ? strtotime( (string) $m['listing_date'] ) : $id;
		$mat_codes = strtolower( implode( ',', $materials ) );

		$search_hay = strtolower( implode( ' ', array_filter( array( $ref, $title, $subtitle, $type, $loc, implode( ' ', $materials ), implode( ' ', $tags ) ) ) ) );
		?>
		<article class="mh-card"
			data-id="<?php echo esc_attr( $id ); ?>"
			data-ref="<?php echo esc_attr( $ref ); ?>"
			data-title="<?php echo esc_attr( strtolower( $title ) ); ?>"
			data-search="<?php echo esc_attr( $search_hay ); ?>"
			data-status="<?php echo esc_attr( $status_key ); ?>"
			data-location="<?php echo esc_attr( strtolower( $loc ) ); ?>"
			data-clamp="<?php echo esc_attr( $clamp_n ); ?>"
			data-shot="<?php echo esc_attr( $shot_n ); ?>"
			data-materials="<?php echo esc_attr( $mat_codes ); ?>"
			data-certs="<?php echo esc_attr( implode( ',', $cert_keys ) ); ?>"
			data-auto="<?php echo esc_attr( implode( ',', $auto_flags ) ); ?>"
			data-adv="<?php echo esc_attr( implode( ',', $adv_flags ) ); ?>"
			data-year="<?php echo esc_attr( $year ); ?>"
			data-ts="<?php echo esc_attr( (string) $ts_sort ); ?>">

			<div class="mh-card__media">
				<?php if ( $img ) : ?>
					<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $ref ); ?>" loading="lazy">
				<?php else : ?>
					<span class="mh-card__glyph" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="8" width="7" height="9" rx="1"/><path d="M9 12.5h3.5"/><rect x="12.5" y="9.5" width="5.5" height="6" rx="1"/><path d="M18 12.5h4"/><path d="M15 9.5V6h2.5"/></svg>
					</span>
				<?php endif; ?>

				<span class="mh-pill<?php echo esc_attr( $pill_class ); ?>">
					<span class="dot" aria-hidden="true"></span><?php echo esc_html( $pill_label ); ?>
				</span>

				<button type="button"
					class="mh-fav<?php echo $is_saved ? ' is-favourite' : ''; ?>"
					aria-pressed="<?php echo $is_saved ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr( $is_saved ? 'Remove from wishlist' : 'Save to wishlist' ); ?>"
					onclick="mhToggleWishlist(<?php echo (int) $id; ?>,'machine',<?php echo wp_json_encode( $title ); ?>,<?php echo wp_json_encode( $img ); ?>,this)">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
				</button>
			</div>

			<div class="mh-card__body">
				<div class="mh-card__topline">
					<span class="mh-card__ref"><?php echo esc_html( $ref ); ?></span>
					<?php if ( $year !== '' ) : ?><span class="mh-card__year"><?php echo esc_html( $year ); ?></span><?php endif; ?>
				</div>

				<h3 class="mh-card__title"><?php echo esc_html( $title ); ?></h3>
				<p class="mh-card__sub"><?php echo esc_html( $subtitle ); ?></p>

				<div class="mh-spec">
					<div>
						<p class="mh-spec__k">Clamp</p>
						<p class="mh-spec__v"><?php echo $clamp_n ? esc_html( $clamp_n ) . ' <small>T</small>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					</div>
					<div>
						<p class="mh-spec__k">Shot</p>
						<p class="mh-spec__v"><?php echo $shot_n ? esc_html( $shot_n ) . ' <small>g</small>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					</div>
					<div>
						<p class="mh-spec__k">Screw</p>
						<p class="mh-spec__v"><?php echo $screw_n ? esc_html( $screw_n ) . ' <small>mm</small>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					</div>
				</div>

				<?php if ( $materials ) : ?>
				<div class="mh-mats">
					<?php
					$shown = array_slice( $materials, 0, 3 );
					$extra = count( $materials ) - count( $shown );
					foreach ( $shown as $mat ) : ?>
						<span class="mh-mat"><?php echo esc_html( $mat ); ?></span>
					<?php endforeach; ?>
					<?php if ( $extra > 0 ) : ?><span class="mh-mat">+<?php echo (int) $extra; ?></span><?php endif; ?>
				</div>
				<?php endif; ?>

				<div class="mh-metarow">
					<?php if ( $loc !== '' ) : ?>
					<span class="loc">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
						<span><?php echo esc_html( $loc ); ?></span>
					</span>
					<?php else : ?><span class="loc"></span><?php endif; ?>
					<?php if ( $until !== '' ) : ?><span class="until"><?php echo esc_html( $until ); ?></span><?php endif; ?>
				</div>

				<?php if ( $tags ) : ?>
				<div class="mh-tags">
					<?php foreach ( array_slice( $tags, 0, 4 ) as $t ) : ?>
						<span class="mh-tag"><?php echo esc_html( $t ); ?></span>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<div class="mh-card__actions">
					<a class="mh-card__cta" href="<?php echo esc_url( $detail_url ); ?>">Request details</a>
					<button type="button" class="mh-card__compare"
						data-id="<?php echo esc_attr( $id ); ?>"
						data-ref="<?php echo esc_attr( $ref ); ?>"
						aria-pressed="false"
						aria-label="<?php echo esc_attr( 'Add ' . $ref . ' to compare' ); ?>"
						title="Add to compare">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
					</button>
				</div>
			</div>
		</article>
		<?php
	}
}
?>

<main class="mh-browse" id="mhBrowse">
<div class="mh-shell">

	<!-- Breadcrumb -->
	<nav class="mh-crumb" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( home_url( '/machines/' ) ); ?>">Browse</a>
		<span class="sep" aria-hidden="true">·</span>
		<span aria-current="page">Machine capacity</span>
	</nav>

	<!-- Hero -->
	<header class="mh-hero">
		<h1>Find injection moulding capacity</h1>
		<p>Search anonymised machine listings by clamp force, materials, location and certifications. Owner identity stays private until a request is approved.</p>
	</header>

	<!-- Big search -->
	<div class="mh-search" role="search">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
		<label class="screen-reader-text" for="mhSearch" style="position:absolute;left:-9999px;">Search machines</label>
		<input type="text" id="mhSearch" placeholder="e.g. 120T hydraulic, ABS, Birmingham" autocomplete="off">
		<button type="button" class="mh-search__btn" id="mhSearchBtn">Search</button>
	</div>

	<!-- Quick filter chips -->
	<div class="mh-chips" role="group" aria-label="Quick filters">
		<button type="button" class="mh-chip is-active" data-quick="available" aria-pressed="true">Available now</button>
		<button type="button" class="mh-chip" data-quick="near" aria-pressed="false">Near me</button>
		<button type="button" class="mh-chip" data-quick="clamp:100:300" aria-pressed="false">100–300 T</button>
		<button type="button" class="mh-chip" data-quick="cert:iso 9001" aria-pressed="false">ISO 9001</button>
		<button type="button" class="mh-chip" data-quick="auto:robot_integration" aria-pressed="false">Robot</button>
		<button type="button" class="mh-chip" data-quick="adv:recycled" aria-pressed="false">Recycled</button>
		<button type="button" class="mh-chip" data-quick="adv:thin_wall" aria-pressed="false">Thin-wall</button>
	</div>

	<div class="mh-layout">

		<!-- Filter rail -->
		<aside class="mh-rail" id="mhRail" aria-label="Filters">
			<div class="mh-rail__head">
				<h2>Filters</h2>
				<div style="display:flex;align-items:center;gap:8px;">
					<button type="button" class="mh-reset" id="mhReset">Reset</button>
					<button type="button" class="mh-rail__close" id="mhRailClose" aria-label="Close filters">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
					</button>
				</div>
			</div>

			<form id="mhFilterForm" onsubmit="return false;">

				<!-- Availability -->
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>Availability</p>
					<label class="mh-opt"><input type="checkbox" data-filter="status" value="available" checked> Available now</label>
					<label class="mh-opt"><input type="checkbox" data-filter="status" value="pending"> Pending approval</label>
					<label class="mh-opt"><input type="checkbox" data-filter="status" value="expired"> Expired</label>
				</section>

				<!-- Clamp force -->
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10M18 20V4M6 20v-4"/></svg>Clamp force (tons)</p>
					<div class="mh-range">
						<input type="number" min="0" step="10" inputmode="numeric" placeholder="Min" data-filter="clampMin" aria-label="Minimum clamp force">
						<span>–</span>
						<input type="number" min="0" step="10" inputmode="numeric" placeholder="Max" data-filter="clampMax" aria-label="Maximum clamp force">
					</div>
				</section>

				<!-- Shot size -->
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Shot size (g)</p>
					<div class="mh-range">
						<input type="number" min="0" step="10" inputmode="numeric" placeholder="Min" data-filter="shotMin" aria-label="Minimum shot size">
						<span>–</span>
						<input type="number" min="0" step="10" inputmode="numeric" placeholder="Max" data-filter="shotMax" aria-label="Maximum shot size">
					</div>
				</section>

				<!-- Materials -->
				<?php if ( $mh_materials_ordered ) : ?>
				<section class="mh-fsec" id="mhMatSec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M5 7h14M5 17h14"/></svg>Materials</p>
					<?php foreach ( $mh_materials_ordered as $i => $code ) : ?>
						<label class="mh-opt<?php echo $i >= 6 ? ' is-extra' : ''; ?>">
							<input type="checkbox" data-filter="material" value="<?php echo esc_attr( strtolower( $code ) ); ?>">
							<?php echo esc_html( $code ); ?>
							<span class="mh-opt__count"><?php echo (int) $mh_material_counts[ $code ]; ?></span>
						</label>
					<?php endforeach; ?>
					<?php if ( count( $mh_materials_ordered ) > 6 ) : ?>
						<button type="button" class="mh-more" data-toggle-extra="mhMatSec">+ Show all <?php echo (int) count( $mh_materials_ordered ); ?> materials</button>
					<?php endif; ?>
				</section>
				<?php endif; ?>

				<!-- Location -->
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Location</p>
					<input type="text" class="mh-loc-input" id="mhLocInput" data-filter="location" placeholder="City or region…" autocomplete="off">
				</section>

				<!-- Certifications -->
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Certifications</p>
					<?php foreach ( $mh_cert_defs as $cert ) : ?>
						<label class="mh-opt"><input type="checkbox" data-filter="cert" value="<?php echo esc_attr( strtolower( $cert ) ); ?>"> <?php echo esc_html( $cert === 'Medical' ? 'Medical grade' : $cert ); ?></label>
					<?php endforeach; ?>
				</section>

				<!-- Automation -->
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 17l6-6 4 4 6-8M14 7h6v6"/></svg>Automation</p>
					<?php foreach ( $mh_automation_defs as $key => $label ) : ?>
						<label class="mh-opt"><input type="checkbox" data-filter="auto" value="<?php echo esc_attr( $key ); ?>"> <?php echo esc_html( $label ); ?></label>
					<?php endforeach; ?>
				</section>

				<!-- Advanced capabilities -->
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M2 12h3M19 12h3"/></svg>Advanced capabilities</p>
					<?php foreach ( $mh_advanced_defs as $key => $label ) : ?>
						<label class="mh-opt"><input type="checkbox" data-filter="adv" value="<?php echo esc_attr( $key ); ?>"> <?php echo esc_html( $label ); ?></label>
					<?php endforeach; ?>
				</section>

				<button type="button" class="mh-apply" id="mhApply">Apply filters</button>
			</form>
		</aside>
		<div class="mh-rail-overlay" id="mhRailOverlay" hidden></div>

		<!-- Results -->
		<section class="mh-results" aria-label="Machine results">
			<div class="mh-results__head">
				<button type="button" class="mh-filter-toggle" id="mhFilterToggle" aria-expanded="false" aria-controls="mhRail">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
					Filters
					<span class="badge" id="mhFilterCount" hidden>0</span>
				</button>
				<div class="mh-results__count"><span id="mhVisibleCount"><?php echo (int) $mh_total; ?></span> machines <span id="mhAvailNote">available now</span></div>
				<label class="screen-reader-text" for="mhSort" style="position:absolute;left:-9999px;">Sort results</label>
				<select class="mh-sort" id="mhSort">
					<option value="newest">Sort: Newest</option>
					<option value="oldest">Sort: Oldest</option>
					<option value="clamp-desc">Clamp force ↓</option>
					<option value="clamp-asc">Clamp force ↑</option>
					<option value="name">Name A–Z</option>
				</select>
			</div>

			<div class="mh-grid" id="mhGrid">
				<?php if ( empty( $mh_machines ) ) : ?>
					<div class="mh-empty">
						<div class="mh-empty__icon" aria-hidden="true">🔍</div>
						<strong>No machines listed yet</strong>
						<span>Check back soon for available injection moulding capacity.</span>
					</div>
				<?php else : ?>
					<?php foreach ( $mh_machines as $m ) {
						mh_render_card( $m, $mh_saved_ids );
					} ?>
				<?php endif; ?>
			</div>

			<div class="mh-empty" id="mhNoResults" hidden>
				<div class="mh-empty__icon" aria-hidden="true">🔍</div>
				<strong>No machines match your filters</strong>
				<span>Try widening your search or clearing some filters.</span>
			</div>
		</section>

	</div><!-- /.mh-layout -->
</div><!-- /.mh-shell -->

<!-- Sticky compare bar -->
<div class="mh-comparebar" id="mhCompareBar" role="region" aria-label="Compare machines" aria-live="polite">
	<div class="mh-comparebar__inner">
		<span class="mh-comparebar__label">Compare <b id="mhCompareCount">0</b> selected</span>
		<div class="mh-comparebar__chips" id="mhCompareChips"></div>
		<button type="button" class="mh-comparebar__clear" id="mhCompareClear">Clear</button>
		<button type="button" class="mh-comparebar__go" id="mhCompareGo">Compare machines</button>
	</div>
</div>

<!-- Mobile public bottom nav (≤640 only). Routes limited to ones that exist:
     Home · Browse (active) · Messages (same destination as the global header). -->
<nav class="mh-botnav" aria-label="Primary">
	<a class="mh-botnav__item" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
		<span>Home</span>
	</a>
	<a class="mh-botnav__item is-active" href="<?php echo esc_url( home_url( '/machines/' ) ); ?>" aria-current="page">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><polygon points="15.5 8.5 13.5 13.5 8.5 15.5 10.5 10.5 15.5 8.5"/></svg>
		<span>Browse</span>
	</a>
	<a class="mh-botnav__item" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-messages' ) ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
		<span>Messages</span>
	</a>
</nav>

</main>

<script>
(function () {
	'use strict';

	var MAX_COMPARE = 4;
	var COMPARE_URL = <?php echo wp_json_encode( esc_url_raw( home_url( '/compare/' ) ) ); ?>;
	var grid     = document.getElementById('mhGrid');
	if (!grid) { return; }

	/* ── Near-me geolocation state ───────────────────────────────
	 * Listings store `location` as free text only (no lat/lng column), so true
	 * great-circle distance isn't computable. Best-available approach: resolve the
	 * visitor's coords with the Geolocation API, reverse-geocode them to a
	 * city / region / country, then match + rank cards on that place text. */
	var nearActive = false;
	var nearTokens = [];   // [{ t: 'birmingham', w: 3 }, ...] weighted by specificity
	var nearLabel  = '';

	var cards    = Array.prototype.slice.call(grid.querySelectorAll('.mh-card'));
	var search   = document.getElementById('mhSearch');
	var searchBtn= document.getElementById('mhSearchBtn');
	var sortSel  = document.getElementById('mhSort');
	var countEl  = document.getElementById('mhVisibleCount');
	var availNote= document.getElementById('mhAvailNote');
	var noRes    = document.getElementById('mhNoResults');
	var form     = document.getElementById('mhFilterForm');

	/* ── Filtering ───────────────────────────────────────────── */
	function readFilters() {
		var f = { q:'', status:[], material:[], cert:[], auto:[], adv:[], location:'', clampMin:null, clampMax:null, shotMin:null, shotMax:null };
		f.q = (search ? search.value : '').toLowerCase().trim();
		if (form) {
			form.querySelectorAll('input[data-filter]').forEach(function (el) {
				var key = el.getAttribute('data-filter');
				if (el.type === 'checkbox') {
					if (el.checked && f[key]) { f[key].push(el.value); }
				} else if (key === 'location') {
					f.location = (el.value || '').toLowerCase().trim();
				} else if (['clampMin','clampMax','shotMin','shotMax'].indexOf(key) !== -1) {
					var v = parseInt(el.value, 10);
					f[key] = isNaN(v) ? null : v;
				}
			});
		}
		return f;
	}

	function listHas(attr, values) {
		if (!values.length) { return true; }
		var have = (attr || '').split(',').filter(Boolean);
		return values.every(function (v) { return have.indexOf(v) !== -1; });
	}
	function listHasAny(attr, values) {
		if (!values.length) { return true; }
		var have = (attr || '').split(',').filter(Boolean);
		return values.some(function (v) { return have.indexOf(v) !== -1; });
	}

	/* Weighted place-text match for Near-me: 3=city, 2=region, 1=country, 0=no overlap. */
	function proximityScore(card) {
		if (!nearActive || !nearTokens.length) { return 0; }
		var loc = (card.getAttribute('data-location') || '');
		var best = 0;
		for (var i = 0; i < nearTokens.length; i++) {
			var tok = nearTokens[i];
			if (tok.t && loc.indexOf(tok.t) !== -1 && tok.w > best) { best = tok.w; }
		}
		return best;
	}

	function cardMatches(card, f) {
		if (f.q && (card.getAttribute('data-search') || '').indexOf(f.q) === -1) { return false; }
		if (nearActive && proximityScore(card) === 0) { return false; }
		/* Status: every card returned by the server is already approved + non-expired.
		   "Available now" therefore matches the whole public set; narrowing to pending/
		   expired only happens when the user unchecks "available" and picks another box. */
		if (f.status.length) {
			var sOk = f.status.some(function (s) {
				return s === 'available' ? true : card.getAttribute('data-status') === s;
			});
			if (!sOk) { return false; }
		}
		var clamp = parseInt(card.getAttribute('data-clamp') || '0', 10);
		var shot  = parseInt(card.getAttribute('data-shot') || '0', 10);
		if (f.clampMin !== null && clamp < f.clampMin) { return false; }
		if (f.clampMax !== null && clamp > f.clampMax) { return false; }
		if (f.shotMin  !== null && shot  < f.shotMin)  { return false; }
		if (f.shotMax  !== null && shot  > f.shotMax)  { return false; }
		if (f.location && (card.getAttribute('data-location') || '').indexOf(f.location) === -1) { return false; }
		if (!listHas(card.getAttribute('data-materials'), f.material)) { return false; }
		if (!listHasAny(card.getAttribute('data-certs'), f.cert)) { return false; }
		if (!listHas(card.getAttribute('data-auto'), f.auto)) { return false; }
		if (!listHas(card.getAttribute('data-adv'), f.adv)) { return false; }
		return true;
	}

	function sortCards() {
		var mode = sortSel ? sortSel.value : 'newest';
		var sorted = cards.slice().sort(function (a, b) {
			if (nearActive) {
				var pa = proximityScore(a), pb = proximityScore(b);
				if (pb !== pa) { return pb - pa; }
			}
			if (mode === 'name')       { return (a.getAttribute('data-title') || '').localeCompare(b.getAttribute('data-title') || ''); }
			if (mode === 'clamp-desc') { return (+b.getAttribute('data-clamp')) - (+a.getAttribute('data-clamp')); }
			if (mode === 'clamp-asc')  { return (+a.getAttribute('data-clamp')) - (+b.getAttribute('data-clamp')); }
			var at = +a.getAttribute('data-ts'), bt = +b.getAttribute('data-ts');
			return mode === 'oldest' ? at - bt : bt - at;
		});
		sorted.forEach(function (c) { grid.appendChild(c); });
	}

	function apply() {
		var f = readFilters();
		var visible = 0;
		cards.forEach(function (card) {
			var ok = cardMatches(card, f);
			card.style.display = ok ? '' : 'none';
			if (ok) { visible++; }
		});
		sortCards();
		if (countEl) { countEl.textContent = visible; }
		var anyFilter = f.q || f.material.length || f.cert.length || f.auto.length || f.adv.length || f.location ||
			f.clampMin !== null || f.clampMax !== null || f.shotMin !== null || f.shotMax !== null ||
			(f.status.length && !(f.status.length === 1 && f.status[0] === 'available'));
		if (availNote) { availNote.textContent = nearActive ? nearLabel : ( anyFilter ? 'match your filters' : 'available now' ); }
		if (noRes) { noRes.hidden = visible !== 0; }
		updateFilterCount();
	}

	function updateFilterCount() {
		if (!form) { return; }
		var n = 0;
		form.querySelectorAll('input[data-filter]').forEach(function (el) {
			if (el.type === 'checkbox') {
				if (el.checked && !(el.getAttribute('data-filter') === 'status' && el.value === 'available')) { n++; }
			} else if (el.value && el.value.trim()) { n++; }
		});
		var badge = document.getElementById('mhFilterCount');
		if (badge) { badge.textContent = n; badge.hidden = n === 0; }
	}

	/* events */
	if (search)    { search.addEventListener('input', apply); }
	if (searchBtn) { searchBtn.addEventListener('click', apply); }
	if (sortSel)   { sortSel.addEventListener('change', apply); }
	if (form) {
		form.addEventListener('change', apply);
		form.addEventListener('input', function (e) {
			if (e.target && (e.target.id === 'mhLocInput' || e.target.type === 'number')) { apply(); }
		});
	}
	var applyBtn = document.getElementById('mhApply');
	if (applyBtn) { applyBtn.addEventListener('click', function () { apply(); closeRail(); }); }

	var resetBtn = document.getElementById('mhReset');
	if (resetBtn) {
		resetBtn.addEventListener('click', function () {
			if (form) { form.reset(); }
			if (search) { search.value = ''; }
			nearActive = false; nearTokens = []; nearLabel = '';
			document.querySelectorAll('.mh-chip[data-quick]').forEach(function (c) {
				var on = c.getAttribute('data-quick') === 'available';
				c.classList.toggle('is-active', on);
				c.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			apply();
		});
	}

	/* "Show all materials" */
	document.querySelectorAll('[data-toggle-extra]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var sec = document.getElementById(btn.getAttribute('data-toggle-extra'));
			if (sec) {
				var expanded = sec.classList.toggle('is-expanded');
				btn.textContent = expanded ? 'Show fewer materials' : btn.getAttribute('data-orig') || btn.textContent;
				if (!btn.getAttribute('data-orig')) { btn.setAttribute('data-orig', '+ Show all materials'); }
			}
		});
	});

	/* ── Quick chips ─────────────────────────────────────────── */
	document.querySelectorAll('.mh-chip[data-quick]').forEach(function (chip) {
		chip.addEventListener('click', function () {
			var spec = chip.getAttribute('data-quick');
			var pressed = chip.getAttribute('aria-pressed') === 'true';
			var next = !pressed;
			chip.setAttribute('aria-pressed', next ? 'true' : 'false');
			chip.classList.toggle('is-active', next);

			if (spec === 'available') { apply(); return; }
			if (spec === 'near') {
				if (next) { activateNearMe(chip); }
				else { deactivateNearMe(); apply(); }
				return;
			}
			var parts = spec.split(':');
			if (parts[0] === 'clamp') {
				var minEl = form.querySelector('[data-filter="clampMin"]');
				var maxEl = form.querySelector('[data-filter="clampMax"]');
				if (minEl) { minEl.value = next ? parts[1] : ''; }
				if (maxEl) { maxEl.value = next ? parts[2] : ''; }
			} else {
				var map = { cert: 'cert', auto: 'auto', adv: 'adv' };
				var fkey = map[parts[0]];
				if (fkey) {
					var input = form.querySelector('input[data-filter="' + fkey + '"][value="' + parts[1] + '"]');
					if (input) { input.checked = next; }
				}
			}
			apply();
		});
	});

	/* ── Near-me: Geolocation API → reverse-geocode → place-text ranking ──────
	 * Limitation (noted in the report): listings have no stored coordinates, so we
	 * can't compute true distance. We resolve the visitor's coords, reverse-geocode
	 * them to city/region/country (BigDataCloud's free client endpoint, no key),
	 * then filter + rank cards on that place text. Graceful fallbacks throughout. */
	function nearChip() { return document.querySelector('.mh-chip[data-quick="near"]'); }

	function setNearChip(on, label) {
		var chip = nearChip();
		if (!chip) { return; }
		chip.setAttribute('aria-pressed', on ? 'true' : 'false');
		chip.classList.toggle('is-active', on);
		if (label) { chip.setAttribute('title', label); }
	}

	function deactivateNearMe() {
		nearActive = false;
		nearTokens = [];
		nearLabel = '';
		setNearChip(false, 'Near me');
	}

	function tokenize(s) { return (s || '').toString().toLowerCase().trim(); }

	function applyNearTokens(city, region, country) {
		nearTokens = [];
		if (city)    { nearTokens.push({ t: tokenize(city), w: 3 }); }
		if (region)  { nearTokens.push({ t: tokenize(region), w: 2 }); }
		if (country) { nearTokens.push({ t: tokenize(country), w: 1 }); }
		nearTokens = nearTokens.filter(function (x) { return x.t; });
		nearActive = nearTokens.length > 0;
		nearLabel  = 'near ' + (city || region || country || 'you');
		setNearChip(nearActive, nearLabel);
		apply();
		if (nearActive) {
			var anyVisible = cards.some(function (c) { return c.style.display !== 'none'; });
			if (!anyVisible && availNote) { availNote.textContent = 'near ' + (city || region) + ' — none yet, showing all'; deactivateNearMe(); apply(); }
		}
	}

	function activateNearMe(chip) {
		if (!('geolocation' in navigator)) {
			setNearChip(false, 'Near me');
			var loc0 = document.getElementById('mhLocInput');
			if (loc0) { loc0.focus(); }
			window.alert('Your browser does not support location. Type a city or region instead.');
			return;
		}
		setNearChip(true, 'Locating…');
		if (availNote) { availNote.textContent = 'finding your location…'; }
		navigator.geolocation.getCurrentPosition(function (pos) {
			var lat = pos.coords.latitude, lon = pos.coords.longitude;
			var url = 'https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=' +
				encodeURIComponent(lat) + '&longitude=' + encodeURIComponent(lon) + '&localityLanguage=en';
			fetch(url)
				.then(function (r) { return r.json(); })
				.then(function (d) {
					var city = (d && (d.city || d.locality)) || '';
					var region = (d && (d.principalSubdivision || d.localityInfo && d.localityInfo.administrative && d.localityInfo.administrative[0] && d.localityInfo.administrative[0].name)) || '';
					var country = (d && d.countryName) || '';
					if (!city && !region && !country) { throw new Error('no place'); }
					applyNearTokens(city, region, country);
				})
				.catch(function () {
					/* Reverse-geocode failed — fall back to the manual location field. */
					setNearChip(false, 'Near me');
					var loc1 = document.getElementById('mhLocInput');
					if (loc1) { loc1.focus(); }
					if (availNote) { availNote.textContent = 'available now'; }
					window.alert('Could not look up your area. Type a city or region to filter by location.');
				});
		}, function (err) {
			/* Permission denied / unavailable / timeout — graceful fallback. */
			setNearChip(false, 'Near me');
			if (availNote) { availNote.textContent = 'available now'; }
			var loc2 = document.getElementById('mhLocInput');
			if (loc2) { loc2.focus(); }
			if (err && err.code === 1) {
				window.alert('Location permission denied. Type a city or region to filter by location instead.');
			}
		}, { enableHighAccuracy: false, timeout: 8000, maximumAge: 600000 });
	}

	/* ── Mobile rail off-canvas ──────────────────────────────── */
	var rail    = document.getElementById('mhRail');
	var overlay = document.getElementById('mhRailOverlay');
	var toggle  = document.getElementById('mhFilterToggle');
	var closeBt = document.getElementById('mhRailClose');
	function openRail() {
		if (!rail) { return; }
		rail.classList.add('is-open');
		if (overlay) { overlay.hidden = false; overlay.classList.add('is-open'); }
		document.body.classList.add('mh-rail-locked');
		if (toggle) { toggle.setAttribute('aria-expanded', 'true'); }
	}
	function closeRail() {
		if (!rail) { return; }
		rail.classList.remove('is-open');
		if (overlay) { overlay.classList.remove('is-open'); setTimeout(function () { overlay.hidden = true; }, 220); }
		document.body.classList.remove('mh-rail-locked');
		if (toggle) { toggle.setAttribute('aria-expanded', 'false'); }
	}
	if (toggle)  { toggle.addEventListener('click', openRail); }
	if (closeBt) { closeBt.addEventListener('click', closeRail); }
	if (overlay) { overlay.addEventListener('click', closeRail); }
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeRail(); } });

	/* ── Compare selection ───────────────────────────────────── */
	var selected = [];   // { id, ref }
	var bar      = document.getElementById('mhCompareBar');
	var chipWrap = document.getElementById('mhCompareChips');
	var cntEl    = document.getElementById('mhCompareCount');
	var clearBt  = document.getElementById('mhCompareClear');
	var goBt     = document.getElementById('mhCompareGo');

	function renderCompare() {
		if (cntEl) { cntEl.textContent = selected.length; }
		if (chipWrap) {
			chipWrap.innerHTML = '';
			selected.forEach(function (s) {
				var chip = document.createElement('span');
				chip.className = 'mh-cchip';
				var label = document.createElement('span');
				label.textContent = s.ref;
				var rm = document.createElement('button');
				rm.type = 'button';
				rm.setAttribute('aria-label', 'Remove ' + s.ref + ' from compare');
				rm.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
				rm.addEventListener('click', function () { toggleCompare(s.id, s.ref); });
				chip.appendChild(label); chip.appendChild(rm);
				chipWrap.appendChild(chip);
			});
		}
		if (bar) { bar.classList.toggle('is-visible', selected.length > 0); }
		if (goBt) { goBt.disabled = selected.length < 2; }
		cards.forEach(function (card) {
			var id = card.getAttribute('data-id');
			var on = selected.some(function (s) { return String(s.id) === String(id); });
			card.classList.toggle('is-selected', on);
			var btn = card.querySelector('.mh-card__compare');
			if (btn) { btn.classList.toggle('is-on', on); btn.setAttribute('aria-pressed', on ? 'true' : 'false'); }
		});
	}

	function toggleCompare(id, ref) {
		var idx = selected.findIndex(function (s) { return String(s.id) === String(id); });
		if (idx !== -1) {
			selected.splice(idx, 1);
		} else {
			if (selected.length >= MAX_COMPARE) { return; }
			selected.push({ id: id, ref: ref });
		}
		renderCompare();
	}

	grid.addEventListener('click', function (e) {
		var btn = e.target.closest('.mh-card__compare');
		if (!btn) { return; }
		toggleCompare(btn.getAttribute('data-id'), btn.getAttribute('data-ref'));
	});
	if (clearBt) { clearBt.addEventListener('click', function () { selected = []; renderCompare(); }); }
	if (goBt) {
		goBt.addEventListener('click', function () {
			if (selected.length < 2) { return; }
			var ids = selected.map(function (s) { return s.id; }).join(',');
			window.location.href = COMPARE_URL + '?type=machine&ids=' + encodeURIComponent(ids);
		});
	}

	/* initial paint */
	apply();
	renderCompare();
})();

/* Wishlist toggle (AJAX) — public, no nonce required by existing handler */
function mhToggleWishlist(id, type, title, image, btn) {
	if (typeof ihUserLoggedIn !== 'undefined' && !ihUserLoggedIn && typeof ihShowModal === 'function') {
		ihShowModal('login');
		return;
	}
	var fd = new FormData();
	fd.append('action', 'ih_toggle_wishlist');
	fd.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'ih_nonce' ) ); ?>);
	fd.append('listing_id', id);
	fd.append('listing_type', type);
	fd.append('listing_title', title);
	fd.append('listing_image', image);
	fetch(<?php echo wp_json_encode( esc_url_raw( admin_url( 'admin-ajax.php' ) ) ); ?>, { method: 'POST', body: fd })
		.then(function (r) { return r.json(); })
		.then(function (data) {
			if (data && data.success) {
				var on = !!(data.data && data.data.saved);
				btn.classList.toggle('is-favourite', on);
				btn.setAttribute('aria-pressed', on ? 'true' : 'false');
				btn.setAttribute('aria-label', on ? 'Remove from wishlist' : 'Save to wishlist');
			}
		})
		.catch(function () {});
}
</script>

<?php get_footer(); ?>
