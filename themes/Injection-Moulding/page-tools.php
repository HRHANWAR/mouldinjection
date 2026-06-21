<?php
/**
 * Template Name: All Tools Listing
 *
 * Public tool/mould BROWSE page — "Find injection tooling & moulds".
 * Aligned to the tool-specific Figma (node 519:2154). Reuses the shared
 * Machine-browse design system (im-machines-browse.css, scoped .mh-browse)
 * and layers the tool-specific touches in the scoped .mh-browse--tools variant
 * (assets/css/im-tools-browse.css). Tool facets/cards are tool-specific.
 *
 * Page URL: /tools  (create a WP page titled "Tools", assign this template).
 *
 * Data: wp_ih_tools (anonymised — owner identity never exposed; refs are TL-#####).
 * Visibility: only approved + non-expired listings are shown publicly. Reuses the
 * plugin's expiry/status helpers (ih_listing_not_expired_sql / ih_listing_status_meta).
 */

get_header();

global $wpdb;

/* ─────────────────────────────────────────────────────────────────────────
 * 1. Query — approved + NON-EXPIRED only (public-safe, anonymised)
 * ──────────────────────────────────────────────────────────────────────── */
$tl_not_expired = function_exists( 'ih_listing_not_expired_sql' )
	? ih_listing_not_expired_sql( 't.expiry_date' )
	: $wpdb->prepare(
		"(t.expiry_date IS NULL OR t.expiry_date = '0000-00-00' OR t.expiry_date >= %s)",
		current_time( 'Y-m-d' )
	);

$tl_tools = $wpdb->get_results(
	"SELECT DISTINCT t.* FROM {$wpdb->prefix}ih_tools t
	 WHERE {$tl_not_expired}
	 AND (
	     t.available = 1
	     OR EXISTS (
	         SELECT 1 FROM {$wpdb->prefix}ih_requests r
	         WHERE r.listing_id = t.id
	         AND r.listing_type = 'tool'
	         AND LOWER(TRIM(r.status)) = 'approved'
	     )
	 )
	 ORDER BY t.id DESC",
	ARRAY_A
) ?: array();

$tl_total = count( $tl_tools );

/* ─────────────────────────────────────────────────────────────────────────
 * 2. Helpers + facet aggregation (scoped to this template; guarded)
 * ──────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'tl_num' ) ) {
	/** Pull an integer out of a free-text spec value e.g. "180 g" -> 180. */
	function tl_num( $v ) {
		return (int) preg_replace( '/[^0-9]/', '', (string) $v );
	}
}
if ( ! function_exists( 'tl_yes' ) ) {
	function tl_yes( $v ) {
		$v = strtolower( trim( (string) $v ) );
		return ( $v === 'yes' || $v === '1' || $v === 'true' );
	}
}
if ( ! function_exists( 'tl_materials' ) ) {
	/** Normalised list of polymer codes for a tool row (materials JSON + tolerance flags + single material). */
	function tl_materials( $t ) {
		$out = array();
		$raw = trim( (string) ( $t['materials'] ?? '' ) );
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
		$single = trim( (string) ( $t['material'] ?? '' ) );
		if ( $single !== '' ) {
			$out[] = $single;
		}
		if ( tl_yes( $t['tolerance_pp'] ?? '' ) )  { $out[] = 'PP'; }
		if ( tl_yes( $t['tolerance_abs'] ?? '' ) ) { $out[] = 'ABS'; }
		if ( tl_yes( $t['tolerance_pe'] ?? '' ) )  { $out[] = 'PE'; }
		return array_values( array_unique( array_filter( array_map( 'trim', $out ) ) ) );
	}
}
if ( ! function_exists( 'tl_mat_label' ) ) {
	/** Friendly label for a polymer code (falls back to the raw code). */
	function tl_mat_label( $code ) {
		$map = array(
			'pp'    => 'Polypropylene (PP)',
			'abs'   => 'ABS',
			'pe'    => 'Polyethylene (PE)',
			'hdpe'  => 'HDPE',
			'ldpe'  => 'LDPE',
			'pa'    => 'Nylon (PA)',
			'pa6'   => 'Nylon (PA6)',
			'pa66'  => 'Nylon (PA66)',
			'nylon' => 'Nylon (PA66)',
			'pc'    => 'Polycarbonate (PC)',
			'peek'  => 'PEEK',
			'pom'   => 'Acetal (POM)',
			'tpe'   => 'TPE',
			'tpu'   => 'TPU',
			'pmma'  => 'Acrylic (PMMA)',
			'ps'    => 'Polystyrene (PS)',
			'pet'   => 'PET',
			'pvc'   => 'PVC',
			'san'   => 'SAN',
			'pbt'   => 'PBT',
			'asa'   => 'ASA',
		);
		$k = strtolower( trim( (string) $code ) );
		return $map[ $k ] ?? $code;
	}
}
if ( ! function_exists( 'tl_tonnage' ) ) {
	/** Required clamp tonnage (T): prefer stored clamp_force, else compute
	 *  projected_area (cm²) × cavity_pressure (bar) ÷ 981 (graceful 0 fallback). */
	function tl_tonnage( $t ) {
		$cf = tl_num( $t['clamp_force'] ?? '' );
		if ( $cf > 0 ) {
			return $cf;
		}
		$pa = (float) preg_replace( '/[^0-9.]/', '', (string) ( $t['projected_area'] ?? '' ) );
		$cp = (float) preg_replace( '/[^0-9.]/', '', (string) ( $t['cavity_pressure'] ?? '' ) );
		if ( $pa > 0 && $cp > 0 ) {
			return (int) round( $pa * $cp / 981 );
		}
		return 0;
	}
}
if ( ! function_exists( 'tl_condition_slug' ) ) {
	/** Map a free-text tool condition onto the Figma facet slugs. */
	function tl_condition_slug( $raw ) {
		$s = strtolower( trim( (string) $raw ) );
		if ( $s === '' ) { return ''; }
		if ( strpos( $s, 'trial' ) !== false || strpos( $s, 'sample' ) !== false || strpos( $s, 'unproven' ) !== false ) {
			return 'requires-trial';
		}
		if ( strpos( $s, 'refurb' ) !== false || strpos( $s, 'service' ) !== false || strpos( $s, 'repair' ) !== false || strpos( $s, 'parts' ) !== false || strpos( $s, 'scrap' ) !== false ) {
			return 'needs-service';
		}
		if ( strpos( $s, 'production' ) !== false || strpos( $s, 'ready' ) !== false || strpos( $s, 'new' ) !== false ) {
			return 'production-ready';
		}
		return '';
	}
}
if ( ! function_exists( 'tl_rungate' ) ) {
	/** Runner/Gate facet tokens derived from runner_type + gate_type. */
	function tl_rungate( $t ) {
		$tokens = array();
		$r = strtolower( (string) ( $t['runner_type'] ?? '' ) );
		if ( strpos( $r, 'cold' ) !== false ) { $tokens[] = 'cold-runner'; }
		if ( strpos( $r, 'hot' ) !== false )  { $tokens[] = 'hot-runner'; } // Hot Runner + Semi-Hot Runner
		$g = strtolower( (string) ( $t['gate_type'] ?? '' ) );
		if ( strpos( $g, 'valve' ) !== false ) { $tokens[] = 'valve-gate'; }
		if ( strpos( $g, 'hot tip' ) !== false || strpos( $g, 'hot-tip' ) !== false ) { $tokens[] = 'hot-tip-gate'; }
		if ( strpos( $g, 'edge' ) !== false ) { $tokens[] = 'edge-gate'; }
		return array_values( array_unique( $tokens ) );
	}
}

/* Aggregate facet availability so empty controls hide gracefully. */
$tl_mat_counts  = array();   // material code => count
$tl_cond_counts = array();   // condition slug => count
$tl_rg_counts   = array();   // runner/gate token => count
$tl_cert_counts = array( 'medical' => 0, 'food' => 0 );

foreach ( $tl_tools as $t ) {
	foreach ( tl_materials( $t ) as $code ) {
		$tl_mat_counts[ $code ] = ( $tl_mat_counts[ $code ] ?? 0 ) + 1;
	}
	$cs = tl_condition_slug( $t['mould_condition'] ?? ( $t['tool_condition'] ?? '' ) );
	if ( $cs !== '' ) {
		$tl_cond_counts[ $cs ] = ( $tl_cond_counts[ $cs ] ?? 0 ) + 1;
	}
	foreach ( tl_rungate( $t ) as $tok ) {
		$tl_rg_counts[ $tok ] = ( $tl_rg_counts[ $tok ] ?? 0 ) + 1;
	}
	if ( tl_yes( $t['medical_grade'] ?? '' ) ) { $tl_cert_counts['medical']++; }
	if ( tl_yes( $t['food_grade'] ?? '' ) )    { $tl_cert_counts['food']++; }
}
arsort( $tl_mat_counts );
$tl_materials_ordered = array_keys( $tl_mat_counts );

/* Tool-condition facet (label keyed by slug) — render only slugs with data. */
$tl_cond_defs = array(
	'production-ready' => 'Production ready',
	'needs-service'    => 'Needs service',
	'requires-trial'   => 'Requires trial',
);
/* Runner / Gate facet (label keyed by token) — render only tokens with data. */
$tl_rg_defs = array(
	'cold-runner'  => 'Cold runner',
	'hot-runner'   => 'Hot runner',
	'valve-gate'   => 'Valve gate',
	'hot-tip-gate' => 'Hot-tip gate',
	'edge-gate'    => 'Edge gate',
);
/* Certification facet — only Medical/Food grade have backing ih_tools columns.
 * NOTE: Figma also lists "ISO 9001" and "ISO 13485" but ih_tools has no column
 * for either, so those controls are intentionally omitted (deferred). */
$tl_cert_defs = array(
	'medical' => 'Medical grade',
	'food'    => 'Food grade',
);

$tl_user_id  = get_current_user_id();
$tl_wishlist = $tl_user_id ? get_user_meta( $tl_user_id, 'ih_wishlist', true ) : array();
$tl_saved_ids = array();
if ( is_array( $tl_wishlist ) ) {
	foreach ( $tl_wishlist as $w ) {
		if ( isset( $w['id'] ) ) {
			$tl_saved_ids[ (int) $w['id'] ] = true;
		}
	}
}

/* ─────────────────────────────────────────────────────────────────────────
 * 3. Single-card renderer (real data → Figma card). Scoped to this template.
 * ──────────────────────────────────────────────────────────────────────── */
if ( ! function_exists( 'tl_render_card' ) ) {
	function tl_render_card( $t, $saved_ids ) {
		$id  = (int) ( $t['id'] ?? 0 );
		$ref = function_exists( 'ih_listing_ref' )
			? ih_listing_ref( $t, 'tool' )
			: 'TL-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );

		$status = function_exists( 'ih_listing_status_meta' )
			? ih_listing_status_meta( $t )
			: array( 'key' => ! empty( $t['available'] ) ? 'available' : 'pending', 'label' => ! empty( $t['available'] ) ? 'Available' : 'Pending' );
		$status_key = $status['key'] ?? 'available';
		$pill_class = ( $status_key === 'available' ) ? '' : ( $status_key === 'expired' ? ' is-expired' : ' is-pending' );
		$pill_label = ( $status_key === 'available' ) ? 'Available' : ( $status['label'] ?? 'Pending' );

		$title = trim( (string) ( $t['title'] ?? '' ) ) ?: ( 'Tool · ' . $ref );
		$type  = trim( (string) ( $t['mould_type'] ?? '' ) );
		$cond  = trim( (string) ( $t['mould_condition'] ?? ( $t['tool_condition'] ?? '' ) ) );
		$loc   = trim( (string) ( $t['location'] ?? '' ) );

		/* numeric specs */
		$cav     = tl_num( $t['num_cavities_spec'] ?? ( $t['num_cavities'] ?? '' ) );
		$tonnage = tl_tonnage( $t );
		$shot    = tl_num( $t['shot_weight'] ?? '' );

		/* materials */
		$materials = tl_materials( $t );
		$mat_main  = $materials ? $materials[0] : '';

		/* subtitle: "Single-cavity · Used — production ready" */
		$cav_word = ( $cav === 1 ) ? 'Single-cavity' : ( $cav > 1 ? $cav . '-cavity' : '' );
		$lead     = $cav_word !== '' ? $cav_word : $type;
		$sub_bits = array_filter( array( $lead, $cond ) );
		$subtitle = $sub_bits ? implode( ' · ', $sub_bits ) : 'Injection mould tool';

		/* tags = runner/gate + grade trust signals (ISO has no column) */
		$rungate = tl_rungate( $t );
		$tags    = array();
		foreach ( $rungate as $tok ) {
			$labels = array(
				'cold-runner'  => 'Cold runner',
				'hot-runner'   => 'Hot runner',
				'valve-gate'   => 'Valve gate',
				'hot-tip-gate' => 'Hot-tip gate',
				'edge-gate'    => 'Edge gate',
			);
			if ( isset( $labels[ $tok ] ) ) { $tags[] = $labels[ $tok ]; }
		}
		if ( tl_yes( $t['medical_grade'] ?? '' ) ) { $tags[] = 'Medical grade'; }
		if ( tl_yes( $t['food_grade'] ?? '' ) )    { $tags[] = 'Food grade'; }
		$tags = array_values( array_unique( $tags ) );

		/* certification keys for filtering */
		$cert_keys = array();
		if ( tl_yes( $t['medical_grade'] ?? '' ) ) { $cert_keys[] = 'medical'; }
		if ( tl_yes( $t['food_grade'] ?? '' ) )    { $cert_keys[] = 'food'; }

		/* condition slug for filtering */
		$cond_slug = tl_condition_slug( $cond );

		/* expiry "Until 13 Sep" */
		$until = '';
		if ( ! empty( $t['expiry_date'] ) && $t['expiry_date'] !== '0000-00-00' ) {
			$ts = strtotime( (string) $t['expiry_date'] );
			if ( $ts ) {
				$until = 'Until ' . date_i18n( 'j M', $ts );
			}
		}

		$img        = trim( (string) ( $t['image_1'] ?? '' ) );
		$detail_url = home_url( '/tool/?id=' . $id );
		$is_saved   = isset( $saved_ids[ $id ] );

		$listed_raw = ! empty( $t['listing_date'] ) ? $t['listing_date'] : ( $t['created_at'] ?? '' );
		$ts_sort    = $listed_raw ? strtotime( (string) $listed_raw ) : $id;

		$mat_codes  = strtolower( implode( ',', $materials ) );
		$search_hay = strtolower( implode( ' ', array_filter( array( $ref, $title, $subtitle, $type, $loc, implode( ' ', $materials ), implode( ' ', $tags ) ) ) ) );
		?>
		<article class="mh-card"
			data-id="<?php echo esc_attr( $id ); ?>"
			data-ref="<?php echo esc_attr( $ref ); ?>"
			data-title="<?php echo esc_attr( strtolower( $title ) ); ?>"
			data-search="<?php echo esc_attr( $search_hay ); ?>"
			data-status="<?php echo esc_attr( $status_key ); ?>"
			data-location="<?php echo esc_attr( strtolower( $loc ) ); ?>"
			data-materials="<?php echo esc_attr( $mat_codes ); ?>"
			data-certs="<?php echo esc_attr( implode( ',', $cert_keys ) ); ?>"
			data-condition="<?php echo esc_attr( $cond_slug ); ?>"
			data-rungate="<?php echo esc_attr( implode( ',', $rungate ) ); ?>"
			data-cav="<?php echo esc_attr( $cav ); ?>"
			data-tonnage="<?php echo esc_attr( $tonnage ); ?>"
			data-shot="<?php echo esc_attr( $shot ); ?>"
			data-ts="<?php echo esc_attr( (string) $ts_sort ); ?>">

			<div class="mh-card__media">
				<?php if ( $img ) : ?>
					<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $ref ); ?>" loading="lazy">
				<?php else : ?>
					<span class="mh-card__glyph" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
					</span>
				<?php endif; ?>

				<span class="mh-pill<?php echo esc_attr( $pill_class ); ?>">
					<span class="dot" aria-hidden="true"></span><?php echo esc_html( $pill_label ); ?>
				</span>

				<button type="button"
					class="mh-fav<?php echo $is_saved ? ' is-favourite' : ''; ?>"
					aria-pressed="<?php echo $is_saved ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr( $is_saved ? 'Remove from wishlist' : 'Save to wishlist' ); ?>"
					onclick="tlToggleWishlist(<?php echo (int) $id; ?>,'tool',<?php echo wp_json_encode( $title ); ?>,<?php echo wp_json_encode( $img ); ?>,this)">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
				</button>
			</div>

			<div class="mh-card__body">
				<div class="mh-card__topline">
					<span class="mh-card__ref"><?php echo esc_html( $ref ); ?></span>
				</div>

				<h3 class="mh-card__title"><a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $title ); ?></a></h3>
				<p class="mh-card__sub"><?php echo esc_html( $subtitle ); ?></p>

				<div class="mh-spec">
					<div>
						<p class="mh-spec__k">Cavities</p>
						<p class="mh-spec__v"><?php echo $cav ? esc_html( (string) $cav ) : '—'; ?></p>
					</div>
					<div>
						<p class="mh-spec__k">Material</p>
						<p class="mh-spec__v"><?php echo $mat_main !== '' ? esc_html( $mat_main ) : '—'; ?></p>
					</div>
					<div>
						<p class="mh-spec__k">Tonnage</p>
						<p class="mh-spec__v"><?php echo $tonnage ? esc_html( (string) $tonnage ) . ' <small>T</small>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
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
					<?php foreach ( array_slice( $tags, 0, 4 ) as $tg ) : ?>
						<span class="mh-tag"><?php echo esc_html( $tg ); ?></span>
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

<main class="mh-browse mh-browse--tools" id="mhBrowse">
<div class="mh-shell">

	<!-- Breadcrumb -->
	<nav class="mh-crumb" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( home_url( '/tools/' ) ); ?>">Browse</a>
		<span class="sep" aria-hidden="true">·</span>
		<span aria-current="page">Tools &amp; moulds</span>
	</nav>

	<!-- Hero -->
	<header class="mh-hero">
		<h1>Find injection tooling &amp; moulds</h1>
		<p>Search anonymised tool &amp; mould listings by mould type, material, cavities and required tonnage. Owner identity stays private until a request is approved.</p>
	</header>

	<!-- Big search -->
	<div class="mh-search" role="search">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
		<label class="screen-reader-text" for="mhSearch" style="position:absolute;left:-9999px;">Search tools</label>
		<input type="text" id="mhSearch" placeholder="e.g. 4-cavity ABS housing, hot runner, Preston" autocomplete="off">
		<button type="button" class="mh-search__btn" id="mhSearchBtn">Search</button>
	</div>

	<!-- Quick filter chips -->
	<div class="mh-chips" role="group" aria-label="Quick filters">
		<button type="button" class="mh-chip is-active" data-quick="available" aria-pressed="true">Available now</button>
		<button type="button" class="mh-chip" data-quick="near" aria-pressed="false">Near me</button>
		<button type="button" class="mh-chip" data-quick="multicav" aria-pressed="false">Multi-cavity</button>
		<?php /* NOTE: "ISO 9001" quick chip from Figma omitted — no ih_tools column backs it. */ ?>
		<?php if ( ! empty( $tl_rg_counts['hot-runner'] ) ) : ?>
		<button type="button" class="mh-chip" data-quick="rungate:hot-runner" aria-pressed="false">Hot runner</button>
		<?php endif; ?>
		<?php if ( ! empty( $tl_cert_counts['food'] ) ) : ?>
		<button type="button" class="mh-chip" data-quick="cert:food" aria-pressed="false">Food grade</button>
		<?php endif; ?>
		<?php if ( ! empty( $tl_rg_counts['edge-gate'] ) ) : ?>
		<button type="button" class="mh-chip" data-quick="rungate:edge-gate" aria-pressed="false">Edge gate</button>
		<?php endif; ?>
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

				<!-- Required tonnage (T) -->
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h12l3 9-9 9-9-9 3-9z"/><path d="M6 3l6 9 6-9"/></svg>Required tonnage (T)</p>
					<div class="mh-range">
						<input type="number" min="0" step="10" inputmode="numeric" placeholder="Min" data-filter="tonMin" aria-label="Minimum required tonnage">
						<span>–</span>
						<input type="number" min="0" step="10" inputmode="numeric" placeholder="Max" data-filter="tonMax" aria-label="Maximum required tonnage">
					</div>
				</section>

				<!-- Shot size (backed by shot_weight; stored in grams) -->
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M5 7h14M5 17h14"/></svg>Shot size (g)</p>
					<div class="mh-range">
						<input type="number" min="0" step="5" inputmode="numeric" placeholder="Min" data-filter="shotMin" aria-label="Minimum shot size">
						<span>–</span>
						<input type="number" min="0" step="5" inputmode="numeric" placeholder="Max" data-filter="shotMax" aria-label="Maximum shot size">
					</div>
				</section>

				<!-- Materials -->
				<?php if ( $tl_materials_ordered ) : ?>
				<section class="mh-fsec" id="mhMatSec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>Materials</p>
					<?php foreach ( $tl_materials_ordered as $i => $code ) : ?>
						<label class="mh-opt<?php echo $i >= 6 ? ' is-extra' : ''; ?>">
							<input type="checkbox" data-filter="material" value="<?php echo esc_attr( strtolower( $code ) ); ?>">
							<?php echo esc_html( tl_mat_label( $code ) ); ?>
							<span class="mh-opt__count"><?php echo (int) $tl_mat_counts[ $code ]; ?></span>
						</label>
					<?php endforeach; ?>
					<?php if ( count( $tl_materials_ordered ) > 6 ) : ?>
						<button type="button" class="mh-more" data-toggle-extra="mhMatSec">+ Show all <?php echo (int) count( $tl_materials_ordered ); ?> materials</button>
					<?php endif; ?>
				</section>
				<?php endif; ?>

				<!-- Location -->
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Location</p>
					<input type="text" class="mh-loc-input" id="mhLocInput" data-filter="location" placeholder="City or region…" autocomplete="off">
				</section>

				<!-- Certifications (only Medical/Food grade have backing columns) -->
				<?php if ( array_filter( $tl_cert_counts ) ) : ?>
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Certifications</p>
					<?php foreach ( $tl_cert_defs as $key => $label ) : ?>
						<?php if ( empty( $tl_cert_counts[ $key ] ) ) { continue; } ?>
						<label class="mh-opt">
							<input type="checkbox" data-filter="cert" value="<?php echo esc_attr( $key ); ?>"> <?php echo esc_html( $label ); ?>
							<span class="mh-opt__count"><?php echo (int) $tl_cert_counts[ $key ]; ?></span>
						</label>
					<?php endforeach; ?>
				</section>
				<?php endif; ?>

				<!-- Tool condition -->
				<?php if ( array_filter( $tl_cond_counts ) ) : ?>
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>Tool condition</p>
					<?php foreach ( $tl_cond_defs as $slug => $label ) : ?>
						<?php if ( empty( $tl_cond_counts[ $slug ] ) ) { continue; } ?>
						<label class="mh-opt">
							<input type="checkbox" data-filter="condition" value="<?php echo esc_attr( $slug ); ?>"> <?php echo esc_html( $label ); ?>
							<span class="mh-opt__count"><?php echo (int) $tl_cond_counts[ $slug ]; ?></span>
						</label>
					<?php endforeach; ?>
				</section>
				<?php endif; ?>

				<!-- Runner / Gate -->
				<?php if ( array_filter( $tl_rg_counts ) ) : ?>
				<section class="mh-fsec">
					<p class="mh-fsec__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3 8 4-16 3 8h4"/></svg>Runner / Gate</p>
					<?php foreach ( $tl_rg_defs as $token => $label ) : ?>
						<?php if ( empty( $tl_rg_counts[ $token ] ) ) { continue; } ?>
						<label class="mh-opt">
							<input type="checkbox" data-filter="rungate" value="<?php echo esc_attr( $token ); ?>"> <?php echo esc_html( $label ); ?>
							<span class="mh-opt__count"><?php echo (int) $tl_rg_counts[ $token ]; ?></span>
						</label>
					<?php endforeach; ?>
				</section>
				<?php endif; ?>

				<button type="button" class="mh-apply" id="mhApply">Apply filters</button>
			</form>
		</aside>
		<div class="mh-rail-overlay" id="mhRailOverlay" hidden></div>

		<!-- Results -->
		<section class="mh-results" aria-label="Tool results">
			<div class="mh-results__head">
				<button type="button" class="mh-filter-toggle" id="mhFilterToggle" aria-expanded="false" aria-controls="mhRail">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
					Filters
					<span class="badge" id="mhFilterCount" hidden>0</span>
				</button>
				<div class="mh-results__count"><span id="mhVisibleCount"><?php echo (int) $tl_total; ?></span> tools <span id="mhAvailNote">available now</span></div>
				<label class="screen-reader-text" for="mhSort" style="position:absolute;left:-9999px;">Sort results</label>
				<select class="mh-sort" id="mhSort">
					<option value="newest">Sort: Newest</option>
					<option value="oldest">Sort: Oldest</option>
					<option value="ton-desc">Tonnage ↓</option>
					<option value="ton-asc">Tonnage ↑</option>
					<option value="cav-desc">Cavities ↓</option>
					<option value="cav-asc">Cavities ↑</option>
					<option value="name">Name A–Z</option>
				</select>
			</div>

			<div class="mh-grid" id="mhGrid">
				<?php if ( empty( $tl_tools ) ) : ?>
					<div class="mh-empty">
						<div class="mh-empty__icon" aria-hidden="true">🔍</div>
						<strong>No tools listed yet</strong>
						<span>Check back soon for available mould tooling.</span>
					</div>
				<?php else : ?>
					<?php foreach ( $tl_tools as $t ) {
						tl_render_card( $t, $tl_saved_ids );
					} ?>
				<?php endif; ?>
			</div>

			<div class="mh-empty" id="mhNoResults" hidden>
				<div class="mh-empty__icon" aria-hidden="true">🔍</div>
				<strong>No tools match your filters</strong>
				<span>Try widening your search or clearing some filters.</span>
			</div>
		</section>

	</div><!-- /.mh-layout -->
</div><!-- /.mh-shell -->

<!-- Sticky compare bar -->
<div class="mh-comparebar" id="mhCompareBar" role="region" aria-label="Compare tools" aria-live="polite">
	<div class="mh-comparebar__inner">
		<span class="mh-comparebar__label">Compare <b id="mhCompareCount">0</b> selected</span>
		<div class="mh-comparebar__chips" id="mhCompareChips"></div>
		<button type="button" class="mh-comparebar__clear" id="mhCompareClear">Clear</button>
		<button type="button" class="mh-comparebar__go" id="mhCompareGo" disabled>Compare tools</button>
	</div>
</div>

<!-- Mobile public bottom nav (≤640 only). -->
<nav class="mh-botnav" aria-label="Primary">
	<a class="mh-botnav__item" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
		<span>Home</span>
	</a>
	<a class="mh-botnav__item is-active" href="<?php echo esc_url( home_url( '/tools/' ) ); ?>" aria-current="page">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
		<span>Browse</span>
	</a>
	<a class="mh-botnav__item" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-dashboard' ) ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
		<span>Saved</span>
	</a>
	<a class="mh-botnav__item" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-messages' ) ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
		<span>Messages</span>
	</a>
	<a class="mh-botnav__item" href="<?php echo esc_url( admin_url( 'admin.php?page=ih-user-dashboard' ) ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
		<span>Account</span>
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
	var nearTokens = [];   // [{ t: 'preston', w: 3 }, ...] weighted by specificity
	var nearLabel  = '';

	var cards    = Array.prototype.slice.call(grid.querySelectorAll('.mh-card'));
	var search   = document.getElementById('mhSearch');
	var searchBtn= document.getElementById('mhSearchBtn');
	var sortSel  = document.getElementById('mhSort');
	var countEl  = document.getElementById('mhVisibleCount');
	var availNote= document.getElementById('mhAvailNote');
	var noRes    = document.getElementById('mhNoResults');
	var form     = document.getElementById('mhFilterForm');

	function multicavOn() {
		var chip = document.querySelector('.mh-chip[data-quick="multicav"]');
		return !!(chip && chip.getAttribute('aria-pressed') === 'true');
	}

	/* ── Filtering ───────────────────────────────────────────── */
	function readFilters() {
		var f = { q:'', status:[], material:[], cert:[], condition:[], rungate:[], location:'',
			tonMin:null, tonMax:null, shotMin:null, shotMax:null, multicav:false };
		f.q = (search ? search.value : '').toLowerCase().trim();
		f.multicav = multicavOn();
		if (form) {
			form.querySelectorAll('input[data-filter]').forEach(function (el) {
				var key = el.getAttribute('data-filter');
				if (el.type === 'checkbox') {
					if (el.checked && f[key]) { f[key].push(el.value); }
				} else if (key === 'location') {
					f.location = (el.value || '').toLowerCase().trim();
				} else if (['tonMin','tonMax','shotMin','shotMax'].indexOf(key) !== -1) {
					var v = parseInt(el.value, 10);
					f[key] = isNaN(v) ? null : v;
				}
			});
		}
		return f;
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
		var ton  = parseInt(card.getAttribute('data-tonnage') || '0', 10);
		var shot = parseInt(card.getAttribute('data-shot') || '0', 10);
		var cav  = parseInt(card.getAttribute('data-cav') || '0', 10);
		if (f.multicav && cav < 2) { return false; }
		if (f.tonMin  !== null && ton  < f.tonMin)  { return false; }
		if (f.tonMax  !== null && ton  > f.tonMax)  { return false; }
		if (f.shotMin !== null && shot < f.shotMin) { return false; }
		if (f.shotMax !== null && shot > f.shotMax) { return false; }
		if (f.location && (card.getAttribute('data-location') || '').indexOf(f.location) === -1) { return false; }
		if (!listHasAny(card.getAttribute('data-materials'), f.material)) { return false; }
		if (!listHasAny(card.getAttribute('data-certs'), f.cert)) { return false; }
		if (!listHasAny(card.getAttribute('data-condition'), f.condition)) { return false; }
		if (!listHasAny(card.getAttribute('data-rungate'), f.rungate)) { return false; }
		return true;
	}

	function sortCards() {
		var mode = sortSel ? sortSel.value : 'newest';
		var sorted = cards.slice().sort(function (a, b) {
			if (nearActive) {
				var pa = proximityScore(a), pb = proximityScore(b);
				if (pb !== pa) { return pb - pa; }
			}
			if (mode === 'name')     { return (a.getAttribute('data-title') || '').localeCompare(b.getAttribute('data-title') || ''); }
			if (mode === 'ton-desc') { return (+b.getAttribute('data-tonnage')) - (+a.getAttribute('data-tonnage')); }
			if (mode === 'ton-asc')  { return (+a.getAttribute('data-tonnage')) - (+b.getAttribute('data-tonnage')); }
			if (mode === 'cav-desc') { return (+b.getAttribute('data-cav')) - (+a.getAttribute('data-cav')); }
			if (mode === 'cav-asc')  { return (+a.getAttribute('data-cav')) - (+b.getAttribute('data-cav')); }
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
		var anyFilter = f.q || f.material.length || f.cert.length || f.condition.length || f.rungate.length ||
			f.location || f.multicav || nearActive ||
			f.tonMin !== null || f.tonMax !== null || f.shotMin !== null || f.shotMax !== null ||
			(f.status.length && !(f.status.length === 1 && f.status[0] === 'available'));
		if (availNote && !nearActive) { availNote.textContent = anyFilter ? 'match your filters' : 'available now'; }
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
		if (multicavOn()) { n++; }
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
			deactivateNearMe();
			document.querySelectorAll('.mh-chip[data-quick]').forEach(function (c) {
				var on = c.getAttribute('data-quick') === 'available';
				c.classList.toggle('is-active', on);
				c.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			apply();
		});
	}

	/* "Show all" toggles */
	document.querySelectorAll('[data-toggle-extra]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var sec = document.getElementById(btn.getAttribute('data-toggle-extra'));
			if (sec) {
				var expanded = sec.classList.toggle('is-expanded');
				if (!btn.getAttribute('data-orig')) { btn.setAttribute('data-orig', btn.textContent); }
				btn.textContent = expanded ? 'Show fewer' : btn.getAttribute('data-orig');
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

			if (spec === 'available' || spec === 'multicav') { apply(); return; }
			if (spec === 'near') {
				if (next) { activateNearMe(chip); }
				else { deactivateNearMe(); apply(); }
				return;
			}
			var parts = spec.split(':');
			if (parts[0] === 'cert' || parts[0] === 'rungate') {
				var input = form.querySelector('input[data-filter="' + parts[0] + '"][value="' + parts[1] + '"]');
				if (input) { input.checked = next; }
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
		if (availNote && nearActive) { availNote.textContent = nearLabel; }
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
					var region = (d && (d.principalSubdivision || (d.localityInfo && d.localityInfo.administrative && d.localityInfo.administrative[0] && d.localityInfo.administrative[0].name))) || '';
					var country = (d && d.countryName) || '';
					if (!city && !region && !country) { throw new Error('no place'); }
					applyNearTokens(city, region, country);
				})
				.catch(function () {
					setNearChip(false, 'Near me');
					var loc1 = document.getElementById('mhLocInput');
					if (loc1) { loc1.focus(); }
					if (availNote) { availNote.textContent = 'available now'; }
					window.alert('Could not look up your area. Type a city or region to filter by location.');
				});
		}, function (err) {
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
	var selected = [];
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
			window.location.href = COMPARE_URL + '?type=tool&ids=' + encodeURIComponent(ids);
		});
	}

	/* initial paint */
	apply();
	renderCompare();
})();

/* Wishlist toggle (AJAX) — public, reuses the existing ih_toggle_wishlist handler */
function tlToggleWishlist(id, type, title, image, btn) {
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
