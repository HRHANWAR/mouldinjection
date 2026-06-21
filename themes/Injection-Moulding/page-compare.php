<?php
/**
 * Template Name: Compare Listings
 *
 * PUBLIC side-by-side comparison page — destination for the browse compare bar
 * (`/compare/?type=machine|tool&ids=1,2,3`). Renders a spec matrix: one column
 * per selected listing, one row per spec. Per-column "remove from compare" and
 * "request details". Anonymised (MCH-##### / TL-#####); only public-safe
 * (approved + non-expired) listings are shown. Scoped under `.imc-` (no global
 * CSS bleed); styles in assets/css/im-compare.css.
 *
 * Route: WP page slug "compare" assigned this template.
 *   - page-machines.php compare bar  → ?type=machine&ids=…
 *   - page-tools.php compare bar      → ?type=tool&ids=…
 */

get_header();

global $wpdb;

/* ── Inputs ───────────────────────────────────────────────────────────────── */
$imc_type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'machine';
if ( ! in_array( $imc_type, array( 'machine', 'tool' ), true ) ) {
	$imc_type = 'machine';
}
$imc_is_tool   = ( $imc_type === 'tool' );
$imc_table     = $imc_is_tool ? $wpdb->prefix . 'ih_tools' : $wpdb->prefix . 'ih_machines';
$imc_browse    = home_url( $imc_is_tool ? '/tools/' : '/machines/' );
$imc_detail    = $imc_is_tool ? '/tool/?id=' : '/machine/?id=';

$imc_ids_raw = isset( $_GET['ids'] ) ? (string) wp_unslash( $_GET['ids'] ) : '';
$imc_ids = array();
foreach ( preg_split( '/[,\s]+/', $imc_ids_raw ) as $piece ) {
	$n = absint( $piece );
	if ( $n > 0 && ! in_array( $n, $imc_ids, true ) ) {
		$imc_ids[] = $n;
	}
}
$imc_ids = array_slice( $imc_ids, 0, 4 ); // cap at 4 columns

/* ── Helpers (guarded) ─────────────────────────────────────────────────────── */
if ( ! function_exists( 'imc_yes' ) ) {
	function imc_yes( $v ) {
		$v = strtolower( trim( (string) $v ) );
		return ( $v === 'yes' || $v === '1' || $v === 'true' );
	}
}
if ( ! function_exists( 'imc_v' ) ) {
	function imc_v( $row, $key ) {
		$val = trim( (string) ( $row[ $key ] ?? '' ) );
		return ( $val !== '' && $val !== '0' && strtolower( $val ) !== 'n/a' ) ? $val : '';
	}
}
if ( ! function_exists( 'imc_tool_materials' ) ) {
	function imc_tool_materials( $row ) {
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
			if ( imc_yes( $row[ $col ] ?? '' ) ) {
				$out[] = $code;
			}
		}
		return array_values( array_unique( array_filter( array_map( 'trim', $out ) ) ) );
	}
}
if ( ! function_exists( 'imc_compare_url' ) ) {
	function imc_compare_url( $type, $ids ) {
		if ( empty( $ids ) ) {
			return home_url( '/compare/?type=' . $type );
		}
		return home_url( '/compare/?type=' . $type . '&ids=' . implode( ',', array_map( 'absint', $ids ) ) );
	}
}

/* ── Fetch + public-visibility gate (mirror browse) ───────────────────────── */
$imc_rows = array();
if ( $imc_ids ) {
	$placeholders = implode( ',', array_fill( 0, count( $imc_ids ), '%d' ) );
	$fetched = $wpdb->get_results(
		$wpdb->prepare( "SELECT * FROM {$imc_table} WHERE id IN ({$placeholders})", $imc_ids ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		ARRAY_A
	) ?: array();
	$by_id = array();
	foreach ( $fetched as $r ) {
		$by_id[ (int) $r['id'] ] = $r;
	}
	/* preserve the user's selection order; drop non-public listings */
	foreach ( $imc_ids as $id ) {
		if ( ! isset( $by_id[ $id ] ) ) {
			continue;
		}
		$r = $by_id[ $id ];
		$expired = function_exists( 'ih_listing_is_expired' ) ? ih_listing_is_expired( $r ) : false;
		$has_approved = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}ih_requests
			 WHERE listing_id = %d AND listing_type LIKE %s AND LOWER(TRIM(status)) = 'approved'",
			$id,
			'%' . $wpdb->esc_like( $imc_type ) . '%'
		) );
		$visible = ( ( (int) ( $r['available'] ?? 0 ) === 1 || $has_approved ) && ! $expired );
		if ( $visible ) {
			$imc_rows[] = $r;
		}
	}
}

/* ── Build the spec matrix (label => closure(row) => printable value) ─────── */
$imc_specs = array();
if ( $imc_is_tool ) {
	$imc_specs = array(
		'Mould type'        => function ( $r ) { return imc_v( $r, 'mould_type' ); },
		'Condition'         => function ( $r ) { return imc_v( $r, 'mould_condition' ) ?: imc_v( $r, 'tool_condition' ); },
		'Cavities'          => function ( $r ) { $c = imc_v( $r, 'num_cavities_spec' ) ?: imc_v( $r, 'num_cavities' ); return $c; },
		'Part weight'       => function ( $r ) { $v = imc_v( $r, 'part_weight' ); return $v !== '' ? $v . ' g' : ''; },
		'Part dimensions'   => function ( $r ) { return imc_v( $r, 'part_dimensions' ); },
		'Mould material'    => function ( $r ) { return imc_v( $r, 'mould_material' ); },
		'Mould dimensions'  => function ( $r ) { return imc_v( $r, 'mould_dimensions' ); },
		'Tool life'         => function ( $r ) { return imc_v( $r, 'tool_life' ); },
		'Runner / gate'     => function ( $r ) { return trim( implode( ' / ', array_filter( array( imc_v( $r, 'runner_type' ), imc_v( $r, 'gate_type' ) ) ) ) ); },
		'Cycle time'        => function ( $r ) { return imc_v( $r, 'cycle_time' ); },
		'Min order qty'     => function ( $r ) { return imc_v( $r, 'min_order_qty' ); },
		'Materials'         => function ( $r ) { return imc_tool_materials( $r ); },
		'Compliance'        => function ( $r ) {
			$c = array();
			if ( imc_yes( $r['medical_grade'] ?? '' ) ) { $c[] = 'Medical'; }
			if ( imc_yes( $r['food_grade'] ?? '' ) )    { $c[] = 'Food grade'; }
			if ( imc_yes( $r['iml'] ?? '' ) )           { $c[] = 'IML'; }
			if ( imc_yes( $r['automation'] ?? '' ) )    { $c[] = 'Automation'; }
			return $c;
		},
		'Location'          => function ( $r ) { return imc_v( $r, 'location' ); },
	);
} else {
	$imc_specs = array(
		'Type'                 => function ( $r ) { return imc_v( $r, 'machine_type' ); },
		'Year'                 => function ( $r ) { return imc_v( $r, 'year_manufacture' ); },
		'Clamp force'          => function ( $r ) { $v = imc_v( $r, 'clamping_force' ); return $v; },
		'Shot size'            => function ( $r ) { return imc_v( $r, 'shot_size' ); },
		'Screw Ø'              => function ( $r ) { return imc_v( $r, 'screw_diameter' ); },
		'Max injection pressure' => function ( $r ) { return imc_v( $r, 'max_injection_pressure' ); },
		'Tie-bar spacing'      => function ( $r ) { return imc_v( $r, 'tie_bar_spacing' ); },
		'Mould height (min–max)' => function ( $r ) {
			$mn = imc_v( $r, 'min_mould_height' ); $mx = imc_v( $r, 'max_mould_height' );
			if ( $mn === '' && $mx === '' ) { return ''; }
			return trim( ( $mn ?: '—' ) . ' – ' . ( $mx ?: '—' ) );
		},
		'Max part weight'      => function ( $r ) { return imc_v( $r, 'max_part_weight' ); },
		'Avg cycle time'       => function ( $r ) { return imc_v( $r, 'avg_cycle_time' ); },
		'Materials'            => function ( $r ) {
			$m = function_exists( 'ih_machine_materials' ) ? ih_machine_materials( $r ) : array();
			return array_values( array_filter( array_map( 'trim', (array) $m ) ) );
		},
		'Certifications'       => function ( $r ) {
			$c = preg_split( '/[,\/|]+/', (string) ( $r['certifications'] ?? '' ) );
			return array_values( array_filter( array_map( 'trim', (array) $c ) ) );
		},
		'Location'             => function ( $r ) { return imc_v( $r, 'location' ); },
	);
}

/* Resolved per-column meta (ref, title, image, status, detail link, remove link). */
$imc_cols = array();
foreach ( $imc_rows as $r ) {
	$id  = (int) $r['id'];
	$ref = function_exists( 'ih_listing_ref' ) ? ih_listing_ref( $r, $imc_type ) : ( ( $imc_is_tool ? 'TL-' : 'MCH-' ) . str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) );
	$status = function_exists( 'ih_listing_status_meta' ) ? ih_listing_status_meta( $r ) : array( 'key' => 'available', 'label' => 'Available' );
	$remaining = array_values( array_diff( wp_list_pluck( $imc_rows, 'id' ), array( $id ) ) );
	$imc_cols[] = array(
		'id'        => $id,
		'ref'       => $ref,
		'title'     => trim( (string) ( $r['title'] ?? '' ) ) ?: ( ( $imc_is_tool ? 'Tool · ' : 'Machine · ' ) . $ref ),
		'image'     => trim( (string) ( $r['image_1'] ?? '' ) ),
		'status_key'=> $status['key'] ?? 'available',
		'status_lbl'=> ( ( $status['key'] ?? 'available' ) === 'available' ) ? 'Available' : ( $status['label'] ?? 'Pending' ),
		'detail'    => home_url( $imc_detail . $id ),
		'remove'    => imc_compare_url( $imc_type, $remaining ),
		'row'       => $r,
	);
}
$imc_count = count( $imc_cols );
?>

<main class="imc-page<?php echo $imc_is_tool ? ' imc-page--tools' : ''; ?>" id="imcPage">
<div class="imc-shell">

	<!-- Breadcrumb -->
	<nav class="imc-crumb" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( $imc_browse ); ?>">Browse</a>
		<span class="sep" aria-hidden="true">/</span>
		<a href="<?php echo esc_url( $imc_browse ); ?>"><?php echo $imc_is_tool ? 'Tools &amp; moulds' : 'Machines'; ?></a>
		<span class="sep" aria-hidden="true">/</span>
		<span aria-current="page">Compare</span>
	</nav>

	<header class="imc-hero">
		<h1>Compare <?php echo $imc_is_tool ? 'tools' : 'machines'; ?></h1>
		<p><?php echo (int) $imc_count; ?> listing<?php echo $imc_count === 1 ? '' : 's'; ?> side by side. Listings are anonymised — request details to unlock owner contact.</p>
		<a class="imc-back" href="<?php echo esc_url( $imc_browse ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
			Back to browse
		</a>
	</header>

	<?php if ( $imc_count < 1 ) : ?>
		<div class="imc-empty">
			<div class="imc-empty__icon" aria-hidden="true">⚖️</div>
			<strong>Nothing to compare yet</strong>
			<span>Pick two or more listings from the browse page to compare them here.</span>
			<a class="imc-btn imc-btn--primary" href="<?php echo esc_url( $imc_browse ); ?>">Browse <?php echo $imc_is_tool ? 'tools' : 'machines'; ?></a>
		</div>
	<?php else : ?>

	<div class="imc-tablewrap" role="region" aria-label="Comparison table" tabindex="0">
		<table class="imc-table" data-cols="<?php echo (int) $imc_count; ?>">
			<caption class="screen-reader-text" style="position:absolute;left:-9999px;">Side-by-side comparison of selected <?php echo $imc_is_tool ? 'tools' : 'machines'; ?></caption>
			<thead>
				<tr>
					<th scope="col" class="imc-th imc-th--spec">Spec</th>
					<?php foreach ( $imc_cols as $col ) : ?>
					<th scope="col" class="imc-th imc-col">
						<div class="imc-card">
							<div class="imc-card__media">
								<?php if ( $col['image'] ) : ?>
									<img src="<?php echo esc_url( $col['image'] ); ?>" alt="<?php echo esc_attr( $col['ref'] ); ?>" loading="lazy">
								<?php else : ?>
									<span class="imc-card__glyph" aria-hidden="true">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
									</span>
								<?php endif; ?>
								<a class="imc-card__remove" href="<?php echo esc_url( $col['remove'] ); ?>" aria-label="<?php echo esc_attr( 'Remove ' . $col['ref'] . ' from compare' ); ?>" title="Remove from compare">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
								</a>
							</div>
							<span class="imc-card__ref"><?php echo esc_html( $col['ref'] ); ?></span>
							<span class="imc-card__title"><?php echo esc_html( $col['title'] ); ?></span>
							<span class="imc-pill imc-pill--<?php echo esc_attr( $col['status_key'] ); ?>"><span class="dot" aria-hidden="true"></span><?php echo esc_html( $col['status_lbl'] ); ?></span>
							<a class="imc-btn imc-btn--primary imc-card__cta" href="<?php echo esc_url( $col['detail'] ); ?>">Request details</a>
						</div>
					</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $imc_specs as $label => $fn ) : ?>
				<tr class="imc-row">
					<th scope="row" class="imc-rowlabel"><?php echo esc_html( $label ); ?></th>
					<?php foreach ( $imc_cols as $col ) :
						$val = $fn( $col['row'] );
						?>
						<td class="imc-cell" data-label="<?php echo esc_attr( $label ); ?>">
							<?php if ( is_array( $val ) ) : ?>
								<?php if ( ! empty( $val ) ) : ?>
									<span class="imc-tags">
										<?php foreach ( $val as $tag ) : ?>
											<span class="imc-tag"><?php echo esc_html( $tag ); ?></span>
										<?php endforeach; ?>
									</span>
								<?php else : ?>
									<span class="imc-empty-cell">—</span>
								<?php endif; ?>
							<?php else : ?>
								<?php echo ( $val !== '' ) ? esc_html( $val ) : '<span class="imc-empty-cell">—</span>'; ?>
							<?php endif; ?>
						</td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<p class="imc-foot">Showing public, anonymised listings only. Specs are as entered by owners. Empty cells mean the spec wasn’t provided.</p>

	<?php endif; ?>

</div><!-- /.imc-shell -->
</main>

<?php get_footer(); ?>
