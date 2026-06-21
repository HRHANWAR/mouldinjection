<?php
/**
 * Tool Listing — Detail (Technical, New)  — responsive partial
 * ---------------------------------------------------------------------------
 * Included from page-tool-detail.php (legacy markup preserved-but-disabled),
 * mirroring the hero-technical.php pattern in front-page.php.
 *
 * Expects these variables already in scope (set up in page-tool-detail.php):
 *   $t (ih_tools row, ARRAY_A), $tool_id, $tool_ref, $imgs[], $default_img,
 *   $fmt_listing, $fmt_expiry, $wa_link, $contact_nonce,
 *   $owner_id, $oc_status, $oc_approved, $oc_is_owner, $oc_is_admin,
 *   $oc_owner[], $oc_uid, $oc_nonce, $oc_login_url, $listing_id, $listing_type.
 *
 * All classes are prefixed `imt-td` and the CSS is scoped under `.imt-td`.
 * Responsive: 2-col layout collapses to 1-col < 980px (sidebar drops below),
 * spec key/value grids go 1-col < 600px, hero pills & material chips wrap.
 * ---------------------------------------------------------------------------
 */
if ( ! isset( $t ) || ! is_array( $t ) ) { return; }

if ( ! function_exists( 'imt_td_v' ) ) {
	function imt_td_v( $t, $k ) { return ( isset( $t[ $k ] ) && $t[ $k ] !== '' && $t[ $k ] !== null ) ? $t[ $k ] : '—'; }
}
if ( ! function_exists( 'imt_td_row' ) ) {
	function imt_td_row( $label, $value, $full = false ) {
		$value = ( $value === '' || $value === null ) ? '—' : $value;
		echo '<div class="imt-td-kv' . ( $full ? ' full' : '' ) . '"><span class="imt-td-kv__l">' . esc_html( $label ) . '</span><span class="imt-td-kv__v">' . esc_html( $value ) . '</span></div>';
	}
}
if ( ! function_exists( 'imt_td_comp' ) ) {
	function imt_td_comp( $label, $yes ) {
		$cls = $yes ? 'is-yes' : 'is-no';
		$txt = $yes ? 'Yes' : 'No';
		echo '<div class="imt-td-comp"><span class="imt-td-comp__l">' . esc_html( $label ) . '</span><span class="imt-td-badge ' . $cls . '">' . esc_html( $txt ) . '</span></div>';
	}
}
if ( ! function_exists( 'imt_td_svg' ) ) {
	function imt_td_svg( $p ) { return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>'; }
}

/* ── Selected materials (only what the owner saved) grouped by category ── */
$imt_mat_cat = array(
	'PP' => 'Commodity', 'PE' => 'Commodity', 'HDPE' => 'Commodity', 'LDPE' => 'Commodity', 'PS' => 'Commodity', 'HIPS' => 'Commodity', 'PVC' => 'Commodity', 'PET' => 'Commodity', 'SAN' => 'Commodity',
	'ABS' => 'Engineering', 'PC' => 'Engineering', 'PC/ABS' => 'Engineering', 'PA6' => 'Engineering', 'PA66' => 'Engineering', 'PA11' => 'Engineering', 'PA12' => 'Engineering', 'POM' => 'Engineering', 'PMMA' => 'Engineering', 'PBT' => 'Engineering', 'ASA' => 'Engineering', 'PPO/PPE' => 'Engineering', 'PPO' => 'Engineering',
	'TPE' => 'Elastomers / Flexible', 'TPU' => 'Elastomers / Flexible', 'TPV' => 'Elastomers / Flexible', 'TPO' => 'Elastomers / Flexible', 'LSR' => 'Elastomers / Flexible',
	'PEEK' => 'High-Performance', 'PEI' => 'High-Performance', 'PPS' => 'High-Performance', 'PSU' => 'High-Performance', 'PPSU' => 'High-Performance', 'LCP' => 'High-Performance', 'PTFE' => 'High-Performance',
	'EPOXY' => 'Thermosets', 'PHENOLIC' => 'Thermosets', 'BMC' => 'Thermosets',
	'GLASS-FILLED (GF)' => 'Grade Modifiers', 'CARBON-FIBRE (CF)' => 'Grade Modifiers', 'MINERAL-FILLED' => 'Grade Modifiers', 'FLAME-RETARDANT (FR)' => 'Grade Modifiers', 'UV-STABILISED' => 'Grade Modifiers', 'FOOD-CONTACT' => 'Grade Modifiers', 'MEDICAL-GRADE' => 'Grade Modifiers', 'ANTI-STATIC / CONDUCTIVE' => 'Grade Modifiers',
);
$imt_mat_names = array( 'PP' => 'PP · Polypropylene', 'PE' => 'PE · Polyethylene', 'ABS' => 'ABS', 'POM' => 'POM (Acetal)', 'PMMA' => 'PMMA (Acrylic)', 'PEI' => 'PEI (Ultem)', 'LSR' => 'LSR (Silicone)' );

$imt_selected = array();
if ( ! empty( $t['tolerance_pp'] ) )  { $imt_selected[] = 'PP'; }
if ( ! empty( $t['tolerance_abs'] ) ) { $imt_selected[] = 'ABS'; }
if ( ! empty( $t['tolerance_pe'] ) )  { $imt_selected[] = 'PE'; }
$imt_raw = isset( $t['materials'] ) ? $t['materials'] : '';
if ( $imt_raw ) {
	$imt_arr = json_decode( $imt_raw, true );
	if ( ! is_array( $imt_arr ) ) { $imt_arr = array_filter( array_map( 'trim', explode( ',', $imt_raw ) ) ); }
	foreach ( $imt_arr as $m ) { if ( $m !== '' ) { $imt_selected[] = $m; } }
}
$imt_selected = array_values( array_unique( $imt_selected ) );
$imt_grouped = array();
foreach ( $imt_selected as $m ) {
	$cat = isset( $imt_mat_cat[ $m ] ) ? $imt_mat_cat[ $m ] : ( isset( $imt_mat_cat[ strtoupper( $m ) ] ) ? $imt_mat_cat[ strtoupper( $m ) ] : 'Other' );
	$imt_grouped[ $cat ][] = $m;
}
$imt_cat_order = array( 'Commodity', 'Engineering', 'Elastomers / Flexible', 'High-Performance', 'Thermosets', 'Grade Modifiers', 'Other' );

/* ── Hero pill helpers ── */
$imt_mat_pill = ! empty( $tol_pills ) ? implode( ' / ', $tol_pills ) : ( isset( $t['material'] ) ? $t['material'] : '' );
$imt_cav      = ! empty( $t['num_cavities_spec'] ) ? $t['num_cavities_spec'] : ( ! empty( $t['num_cavities'] ) ? $t['num_cavities'] : '' );
$imt_avatar   = strtoupper( substr( (string) ( $oc_uid ?? $tool_ref ), 0, 1 ) );
?>

<div class="imt-td">

  <!-- ===== HERO ===== -->
  <header class="imt-td-hero">
    <div class="imt-td-hero__grid" aria-hidden="true"></div>
    <div class="imt-td-hero__glow" aria-hidden="true"></div>
    <div class="imt-td-hero__inner">
      <a class="imt-td-back" href="<?php echo esc_url( home_url( '/tools' ) ); ?>"><?php echo imt_td_svg( '<path d="M19 12H5M11 18l-6-6 6-6"/>' ); ?> All Tools</a>
      <span class="imt-td-tag"><span class="imt-td-tag__dot"></span> <?php echo ! empty( $t['available'] ) || ! isset( $t['available'] ) ? 'AVAILABLE NOW' : 'LISTED'; ?></span>
      <h1 class="imt-td-title"><?php echo esc_html( imt_td_v( $t, 'title' ) ); ?></h1>
      <div class="imt-td-subrow">
        <span class="imt-td-ref"><?php echo esc_html( $tool_ref ); ?></span>
        <span class="imt-td-subtxt"><?php echo esc_html( trim( implode( ' · ', array_filter( array( $t['mould_type'] ?? '', $t['mould_condition'] ?? '' ) ) ) ) ?: 'Injection Mould Tool' ); ?></span>
      </div>
      <div class="imt-td-pills">
        <?php if ( ! empty( $t['mould_type'] ) ) : ?><span class="imt-td-pill"><?php echo imt_td_svg( '<path d="M12 3 2 9l10 6 10-6z"/><path d="M2 15l10 6 10-6"/>' ); ?> <?php echo esc_html( $t['mould_type'] ); ?></span><?php endif; ?>
        <?php if ( $imt_mat_pill ) : ?><span class="imt-td-pill"><?php echo imt_td_svg( '<path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3"/>' ); ?> <?php echo esc_html( $imt_mat_pill ); ?></span><?php endif; ?>
        <?php if ( ! empty( $t['location'] ) ) : ?><span class="imt-td-pill"><?php echo imt_td_svg( '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>' ); ?> <?php echo esc_html( $t['location'] ); ?></span><?php endif; ?>
        <?php if ( $imt_cav ) : ?><span class="imt-td-pill"><?php echo imt_td_svg( '<path d="M5 9h14M5 15h14M9 4 7 20M17 4l-2 16"/>' ); ?> <?php echo esc_html( $imt_cav ); ?> Cavities</span><?php endif; ?>
        <?php if ( isset( $t['medical_grade'] ) && $t['medical_grade'] === 'Yes' ) : ?><span class="imt-td-pill imt-td-pill--lime"><?php echo imt_td_svg( '<path d="M12 3 5 6v6c0 4 3 6.5 7 9 4-2.5 7-5 7-9V6z"/><path d="m9 12 2 2 4-4"/>' ); ?> Medical Grade</span><?php endif; ?>
      </div>
    </div>
  </header>

  <!-- ===== BODY ===== -->
  <div class="imt-td-body">

    <!-- MAIN COLUMN -->
    <main class="imt-td-main">

      <!-- Gallery -->
      <section class="imt-td-card imt-td-gallery">
        <div class="imt-td-mainimg" id="imtTdMain" style="background-image:url('<?php echo esc_url( $imgs[0] ); ?>');" onclick="imtTdLb('<?php echo esc_js( $imgs[0] ); ?>')"></div>
        <?php if ( count( $imgs ) > 1 ) : ?>
        <div class="imt-td-thumbs">
          <?php foreach ( $imgs as $i => $img ) : ?>
          <div class="imt-td-thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" style="background-image:url('<?php echo esc_url( $img ); ?>');" onclick="imtTdSetMain(this,'<?php echo esc_js( $img ); ?>')"></div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </section>

      <!-- About -->
      <?php if ( ! empty( $t['part_description'] ) ) : ?>
      <section class="imt-td-card">
        <div class="imt-td-head"><span class="imt-td-ic"><?php echo imt_td_svg( '<path d="M4 6h16M4 12h16M4 18h10"/>' ); ?></span><h2>About This Tool</h2></div>
        <p class="imt-td-about"><?php echo esc_html( $t['part_description'] ); ?></p>
      </section>
      <?php endif; ?>

      <!-- Part Information -->
      <section class="imt-td-card">
        <div class="imt-td-head"><span class="imt-td-ic"><?php echo imt_td_svg( '<path d="M12 2 3 7v10l9 5 9-5V7z"/><path d="M3 7l9 5 9-5"/><path d="M12 12v10"/>' ); ?></span><h2>Part Information</h2></div>
        <div class="imt-td-kvgrid">
          <?php
          imt_td_row( 'Tool Name', imt_td_v( $t, 'title' ) );
          imt_td_row( 'Part Name', imt_td_v( $t, 'part_name' ) );
          imt_td_row( 'Part Dimensions (L×W×H)', imt_td_v( $t, 'part_dimensions' ) );
          imt_td_row( 'Part Weight', ! empty( $t['part_weight'] ) ? $t['part_weight'] . ' g' : '—' );
          imt_td_row( 'Material', imt_td_v( $t, 'material' ) );
          imt_td_row( 'Material Grade', imt_td_v( $t, 'material_grade' ) );
          imt_td_row( 'Colour', imt_td_v( $t, 'colour' ) );
          imt_td_row( 'Tolerance', imt_td_v( $t, 'tolerance' ) );
          imt_td_row( 'Surface Finish', imt_td_v( $t, 'surface_finish' ) );
          imt_td_row( 'Draft Angle', imt_td_v( $t, 'draft_angle' ) );
          ?>
        </div>
      </section>

      <!-- Mould Specifications -->
      <section class="imt-td-card">
        <div class="imt-td-head"><span class="imt-td-ic"><?php echo imt_td_svg( '<path d="M12 3 2 9l10 6 10-6z"/><path d="M2 15l10 6 10-6"/>' ); ?></span><h2>Mould Specifications</h2></div>
        <div class="imt-td-kvgrid">
          <?php
          imt_td_row( 'Mould Weight', imt_td_v( $t, 'mould_weight' ) );
          imt_td_row( 'Mould Dimensions', imt_td_v( $t, 'mould_dimensions' ) );
          imt_td_row( 'No. of Cavities', $imt_cav ?: '—' );
          imt_td_row( 'Mould Type', imt_td_v( $t, 'mould_type' ) );
          imt_td_row( 'Mould Material / Steel', imt_td_v( $t, 'mould_material' ) );
          imt_td_row( 'Runner Type', imt_td_v( $t, 'runner_type' ) );
          imt_td_row( 'Ejection Type', imt_td_v( $t, 'ejector_type' ) );
          imt_td_row( 'Gate Type', imt_td_v( $t, 'gate_type' ) );
          imt_td_row( 'Mould Construction', imt_td_v( $t, 'construction' ) );
          imt_td_row( 'Nozzle Type', imt_td_v( $t, 'nozzle_type' ) );
          imt_td_row( 'Tool Condition', imt_td_v( $t, 'mould_condition' ) );
          imt_td_row( 'Tool Life (Guaranteed)', imt_td_v( $t, 'tool_life' ) );
          imt_td_row( 'Mould Location', imt_td_v( $t, 'location' ), true );
          ?>
        </div>
      </section>

      <!-- Production Information -->
      <section class="imt-td-card">
        <div class="imt-td-head"><span class="imt-td-ic"><?php echo imt_td_svg( '<path d="M3 18a9 9 0 1 1 18 0"/><path d="M12 13l4-3"/><circle cx="12" cy="13" r="1.4"/>' ); ?></span><h2>Production Information</h2></div>
        <div class="imt-td-kvgrid">
          <?php
          imt_td_row( 'Required Quantity', imt_td_v( $t, 'required_qty' ) );
          imt_td_row( 'Annual Volume', imt_td_v( $t, 'annual_volume' ) );
          imt_td_row( 'Target Cycle', imt_td_v( $t, 'cycle_time' ) );
          imt_td_row( 'Min Order', imt_td_v( $t, 'min_order_qty' ) );
          imt_td_row( 'Packaging Requirements', imt_td_v( $t, 'packaging' ) );
          imt_td_row( 'Material Supplied', imt_td_v( $t, 'material_supplied' ) );
          imt_td_row( 'Compatible Machine Specs', imt_td_v( $t, 'compatible_specs' ), true );
          ?>
        </div>
      </section>

      <!-- Tool Features & Requirements -->
      <section class="imt-td-card">
        <div class="imt-td-head"><span class="imt-td-ic"><?php echo imt_td_svg( '<path d="M4 6h9"/><path d="M17 6h3"/><circle cx="15" cy="6" r="2"/><path d="M4 18h3"/><path d="M11 18h9"/><circle cx="9" cy="18" r="2"/><path d="M4 12h5"/><path d="M13 12h7"/><circle cx="11" cy="12" r="2"/>' ); ?></span><h2>Tool Features &amp; Requirements</h2></div>
        <div class="imt-td-kvgrid">
          <?php
          imt_td_row( 'Required Clamp Force', imt_td_v( $t, 'clamp_force' ) );
          imt_td_row( 'Shot Weight', imt_td_v( $t, 'shot_weight' ) );
          imt_td_row( 'Tie-Bar Spacing (L×W)', imt_td_v( $t, 'tie_bar' ) );
          imt_td_row( 'Opening Stroke / Daylight', imt_td_v( $t, 'opening_stroke' ) );
          imt_td_row( 'Hot Runner Controller', imt_td_v( $t, 'hot_runner_controller' ) );
          imt_td_row( 'No. of Hot Runner Zones', imt_td_v( $t, 'hot_runner_zones' ) );
          ?>
        </div>
        <div class="imt-td-complist">
          <?php
          imt_td_comp( 'Water Cooled Chiller', ( ( $t['water_cooled'] ?? '' ) === 'Yes' ) );
          imt_td_comp( 'Suck Pump', ( ( $t['suck_pump'] ?? '' ) === 'Yes' ) );
          imt_td_comp( 'Food Grade', ( ( $t['food_grade'] ?? '' ) === 'Yes' ) );
          imt_td_comp( 'Medical Grade', ( ( $t['medical_grade'] ?? '' ) === 'Yes' ) );
          imt_td_comp( 'In-Mould Labelling (IML)', ( ( $t['iml'] ?? '' ) === 'Yes' ) );
          imt_td_comp( 'Automation / Robot Cell', ( ( $t['automation'] ?? '' ) === 'Yes' ) );
          ?>
        </div>
      </section>

      <!-- Materials (ONLY the grades the owner selected) -->
      <section class="imt-td-card">
        <div class="imt-td-head"><span class="imt-td-ic"><?php echo imt_td_svg( '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/>' ); ?></span><h2>Materials</h2></div>
        <?php if ( ! empty( $imt_selected ) ) : ?>
          <div class="imt-td-matcap"><span class="imt-td-matbar"></span> SELECTED FOR THIS TOOL <span class="imt-td-matcount"><?php echo count( $imt_selected ); ?></span></div>
          <div class="imt-td-matgroups">
            <?php foreach ( $imt_cat_order as $cat ) : if ( empty( $imt_grouped[ $cat ] ) ) { continue; } ?>
            <div class="imt-td-matgrp">
              <div class="imt-td-matlbl"><?php echo esc_html( strtoupper( $cat ) ); ?></div>
              <div class="imt-td-chips">
                <?php foreach ( $imt_grouped[ $cat ] as $m ) :
                  $label = isset( $imt_mat_names[ $m ] ) ? $imt_mat_names[ $m ] : $m; ?>
                <span class="imt-td-chip"><span class="imt-td-chip__bx"><?php echo imt_td_svg( '<path d="m5 12 5 5 9-11"/>' ); ?></span><?php echo esc_html( $label ); ?></span>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <p class="imt-td-note"><?php echo imt_td_svg( '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>' ); ?> Polymers confirmed by the tool owner for this listing.</p>
        <?php else : ?>
          <p class="imt-td-note"><?php echo imt_td_svg( '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>' ); ?> No materials specified for this tool yet.</p>
        <?php endif; ?>
      </section>

    </main>

    <!-- SIDEBAR -->
    <aside class="imt-td-side">

      <!-- Summary card -->
      <section class="imt-td-card imt-td-summary">
        <div class="imt-td-sum-ref"><?php echo esc_html( $tool_ref ); ?></div>
        <div class="imt-td-sum-title"><?php echo esc_html( imt_td_v( $t, 'title' ) ); ?></div>
        <div class="imt-td-sum-sub"><?php echo esc_html( trim( implode( ' · ', array_filter( array( $t['mould_type'] ?? '', $t['mould_condition'] ?? '' ) ) ) ) ?: 'Production Ready' ); ?></div>
        <div class="imt-td-statgrid">
          <div class="imt-td-stat"><span class="imt-td-stat__l">Cavities</span><span class="imt-td-stat__v"><?php echo esc_html( $imt_cav ?: '—' ); ?></span></div>
          <div class="imt-td-stat"><span class="imt-td-stat__l">Material</span><span class="imt-td-stat__v"><?php echo esc_html( $imt_mat_pill ?: '—' ); ?></span></div>
          <div class="imt-td-stat"><span class="imt-td-stat__l">Cycle Time</span><span class="imt-td-stat__v"><?php echo esc_html( imt_td_v( $t, 'cycle_time' ) ); ?></span></div>
          <div class="imt-td-stat"><span class="imt-td-stat__l">Min Order</span><span class="imt-td-stat__v"><?php echo esc_html( imt_td_v( $t, 'min_order_qty' ) ); ?></span></div>
        </div>
        <a href="#imtTdEnq" class="imt-td-btn imt-td-btn--lime"><?php echo imt_td_svg( '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>' ); ?> Send Enquiry</a>
        <a href="<?php echo esc_url( home_url( '/tools' ) ); ?>" class="imt-td-btn imt-td-btn--ghost"><?php echo imt_td_svg( '<path d="M19 12H5M11 18l-6-6 6-6"/>' ); ?> Browse All Tools</a>
        <?php if ( ! empty( $wa_link ) ) : ?><a href="<?php echo esc_url( $wa_link ); ?>" target="_blank" rel="noopener" class="imt-td-btn imt-td-btn--ghost">WhatsApp the Team</a><?php endif; ?>
      </section>

      <!-- Quick Enquiry -->
      <section class="imt-td-card" id="imtTdEnq">
        <div class="imt-td-head"><span class="imt-td-ic"><?php echo imt_td_svg( '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>' ); ?></span><h2>Quick Enquiry</h2></div>
        <p class="imt-td-sub2">Interested in this tool? Send your details and we'll be in touch.</p>
        <form id="ihtEnqForm" class="imt-td-form">
          <input type="hidden" name="tool_id"  value="<?php echo (int) $tool_id; ?>">
          <input type="hidden" name="tool_ref" value="<?php echo esc_attr( $tool_ref ); ?>">
          <input type="hidden" name="nonce"    value="<?php echo esc_attr( $contact_nonce ); ?>">
          <input type="hidden" name="type"     value="tool">
          <label class="imt-td-flbl">Your Name *</label>
          <input class="imt-td-in" type="text" name="enquiry_name" placeholder="John Smith" required value="<?php echo is_user_logged_in() ? esc_attr( wp_get_current_user()->display_name ) : ''; ?>">
          <label class="imt-td-flbl">Email Address *</label>
          <input class="imt-td-in" type="email" name="enquiry_email" placeholder="john@company.com" required value="<?php echo is_user_logged_in() ? esc_attr( wp_get_current_user()->user_email ) : ''; ?>">
          <label class="imt-td-flbl">Phone *</label>
          <input class="imt-td-in" type="tel" name="enquiry_phone" placeholder="+44 7000 000000" required>
          <label class="imt-td-flbl">Message *</label>
          <textarea class="imt-td-in" name="enquiry_message" rows="3" required placeholder="Production volume, material, timeline…">I'm interested in <?php echo esc_attr( $tool_ref ); ?> — <?php echo esc_attr( imt_td_v( $t, 'title' ) ); ?>. </textarea>
          <button type="submit" class="imt-td-btn imt-td-btn--lime" id="ihtSubmitBtn"><?php echo imt_td_svg( '<path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7z"/>' ); ?> Send Enquiry</button>
          <div class="imt-td-msg imt-td-msg--ok" id="ihtOk">✅ Enquiry sent! We'll contact you within 24 hours.</div>
          <div class="imt-td-msg imt-td-msg--err" id="ihtErr">⚠ Something went wrong. Please try WhatsApp instead.</div>
        </form>
      </section>

      <!-- Listing Details -->
      <section class="imt-td-card">
        <div class="imt-td-head"><span class="imt-td-ic"><?php echo imt_td_svg( '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>' ); ?></span><h2>Listing Details</h2></div>
        <div class="imt-td-complist">
          <div class="imt-td-comp"><span class="imt-td-comp__l">Listed On</span><span class="imt-td-comp__r"><?php echo esc_html( $fmt_listing ); ?></span></div>
          <div class="imt-td-comp"><span class="imt-td-comp__l">Expires On</span><span class="imt-td-comp__r"><?php echo esc_html( $fmt_expiry ); ?></span></div>
          <div class="imt-td-comp"><span class="imt-td-comp__l">Tool Ref</span><span class="imt-td-comp__r"><?php echo esc_html( $tool_ref ); ?></span></div>
        </div>
      </section>

      <!-- Owner Contact -->
      <section class="imt-td-card" id="ih-owner-contact-card">
        <div class="imt-td-head"><span class="imt-td-ic"><?php echo imt_td_svg( '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>' ); ?></span><h2>Owner Contact</h2></div>

        <?php if ( $oc_approved ) : ?>
          <div class="imt-td-ownerbar is-open">
            <span class="imt-td-avatar"><?php echo esc_html( $imt_avatar ); ?></span>
            <div class="imt-td-ownermeta"><span class="imt-td-uid"><?php echo esc_html( $oc_uid ); ?></span><span class="imt-td-uidsub">Contact unlocked</span></div>
          </div>
          <div class="imt-td-complist">
            <?php if ( ! empty( $oc_owner['name'] ) ) : ?><div class="imt-td-comp"><span class="imt-td-comp__l">Name</span><span class="imt-td-comp__r"><?php echo esc_html( $oc_owner['name'] ); ?></span></div><?php endif; ?>
            <?php if ( ! empty( $oc_owner['company'] ) ) : ?><div class="imt-td-comp"><span class="imt-td-comp__l">Company</span><span class="imt-td-comp__r"><?php echo esc_html( $oc_owner['company'] ); ?></span></div><?php endif; ?>
            <?php if ( ! empty( $oc_owner['email'] ) ) : ?><div class="imt-td-comp"><span class="imt-td-comp__l">Email</span><span class="imt-td-comp__r"><a href="mailto:<?php echo esc_attr( $oc_owner['email'] ); ?>"><?php echo esc_html( $oc_owner['email'] ); ?></a></span></div><?php endif; ?>
            <?php if ( ! empty( $oc_owner['phone'] ) ) : ?><div class="imt-td-comp"><span class="imt-td-comp__l">Phone</span><span class="imt-td-comp__r"><a href="tel:<?php echo esc_attr( preg_replace( '/\s/', '', $oc_owner['phone'] ) ); ?>"><?php echo esc_html( $oc_owner['phone'] ); ?></a></span></div><?php endif; ?>
          </div>
        <?php elseif ( $oc_status === 'Guest' ) : ?>
          <div class="imt-td-locked">
            <span class="imt-td-lockic"><?php echo imt_td_svg( '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>' ); ?></span>
            <p>Log in to request the owner's contact details for this listing.</p>
            <a href="<?php echo esc_url( $oc_login_url ); ?>" class="imt-td-btn imt-td-btn--teal">Login to Request</a>
          </div>
        <?php elseif ( $oc_status === 'Pending' ) : ?>
          <div class="imt-td-locked">
            <span class="imt-td-lockic">⏳</span>
            <div class="imt-td-pillstatus is-pending">Request Pending</div>
            <p>Your request has been sent to admin. You'll be notified in Messages once reviewed.</p>
          </div>
        <?php elseif ( $oc_status === 'Rejected' ) : ?>
          <div class="imt-td-locked">
            <span class="imt-td-lockic">❌</span>
            <div class="imt-td-pillstatus is-rejected">Request Rejected</div>
            <button class="imt-td-btn imt-td-btn--teal" id="ihocReqBtn" onclick="ihocSendRequest()">Re-request Access</button>
          </div>
        <?php else : ?>
          <div class="imt-td-ownerbar">
            <span class="imt-td-avatar"><?php echo esc_html( $imt_avatar ); ?></span>
            <div class="imt-td-ownermeta"><span class="imt-td-uid"><?php echo esc_html( $oc_uid ); ?></span><span class="imt-td-uidsub">Hidden until you request access</span></div>
            <span class="imt-td-lockmini"><?php echo imt_td_svg( '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>' ); ?></span>
          </div>
          <p class="imt-td-note2">Owner's name, email, phone &amp; company details are protected until your request is approved.</p>
          <button class="imt-td-btn imt-td-btn--teal" id="ihocReqBtn" onclick="ihocSendRequest()"><?php echo imt_td_svg( '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>' ); ?> Request Contact Details</button>
        <?php endif; ?>

        <div class="imt-td-msg imt-td-msg--ok" id="ihocMsgOk"></div>
        <div class="imt-td-msg imt-td-msg--err" id="ihocMsgErr"></div>

        <?php if ( $oc_is_admin && ! $oc_is_owner ) :
          global $wpdb;
          $imt_pending = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.*, u.display_name, u.user_email FROM {$wpdb->prefix}ih_requests r LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id WHERE r.listing_id = %d AND r.listing_type = %s AND r.status = 'Pending' ORDER BY r.id DESC",
            $listing_id, $listing_type . '_contact'
          ), ARRAY_A );
          if ( ! empty( $imt_pending ) ) : ?>
          <div class="imt-td-admin">
            <div class="imt-td-admin__hd">Pending Requests (<?php echo count( $imt_pending ); ?>)</div>
            <?php foreach ( $imt_pending as $pr ) : ?>
            <div class="imt-td-admin__row">
              <div><div class="imt-td-admin__nm"><?php echo esc_html( $pr['display_name'] ); ?></div><div class="imt-td-admin__em"><?php echo esc_html( $pr['user_email'] ); ?></div></div>
              <div class="imt-td-admin__act">
                <button class="imt-td-ap" onclick="ihocAdminDecide(<?php echo (int) $pr['id']; ?>,'Approved',this)">✓</button>
                <button class="imt-td-rj" onclick="ihocAdminDecide(<?php echo (int) $pr['id']; ?>,'Rejected',this)">✕</button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      </section>

    </aside>
  </div>

  <!-- Lightbox -->
  <div class="imt-td-lb" id="imtTdLb" onclick="imtTdCloseLb()"><span class="imt-td-lb__x">✕</span><img id="imtTdLbImg" src="" alt=""></div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Montserrat:wght@500;600;700;800&family=Poppins:wght@400;500;600&display=swap');
.imt-td{ --bg:#F5F6FA; --card:#fff; --line:#E4E8EF; --line2:#EDF0F5; --ink:#16242B; --slate:#4F6068; --muted:#8593A0; --micro:#7C8A95; --teal:#0F3D43; --teal2:#12343A; --lime:#C8FF00; --val:#153F45; --mono:'JetBrains Mono',ui-monospace,monospace; --sans:'Montserrat',system-ui,sans-serif; --body:'Poppins',system-ui,sans-serif; background:var(--bg); color:var(--ink); font-family:var(--body); }
.imt-td *{ box-sizing:border-box; }
.imt-td svg{ width:16px; height:16px; flex:none; }
/* hero */
.imt-td-hero{ position:relative; overflow:hidden; isolation:isolate; background:linear-gradient(120deg,#001A1E 0%,#0C2A2E 52%,#12343A 100%); color:#fff; padding:34px 24px 30px; }
.imt-td-hero__grid{ position:absolute; inset:0; z-index:0; background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px); background-size:104px 104px; -webkit-mask-image:linear-gradient(180deg,#000,rgba(0,0,0,.35)); mask-image:linear-gradient(180deg,#000,rgba(0,0,0,.35)); }
.imt-td-hero__glow{ position:absolute; top:-200px; right:-120px; width:620px; height:460px; z-index:0; background:radial-gradient(closest-side,rgba(200,255,0,.16),rgba(200,255,0,0)); filter:blur(20px); }
.imt-td-hero__inner{ position:relative; z-index:1; max-width:1180px; margin:0 auto; }
.imt-td-back{ display:inline-flex; align-items:center; gap:6px; font-family:var(--mono); font-size:11.5px; color:rgba(255,255,255,.55); text-decoration:none; margin-bottom:16px; }
.imt-td-back:hover{ color:var(--lime); } .imt-td-back svg{ width:13px; height:13px; }
.imt-td-tag{ display:inline-flex; align-items:center; gap:7px; font-family:var(--mono); font-size:10.5px; letter-spacing:.8px; color:#DCEBC4; background:rgba(200,255,0,.12); border:1px solid rgba(200,255,0,.3); padding:5px 12px; border-radius:999px; }
.imt-td-tag__dot{ width:7px; height:7px; border-radius:50%; background:var(--lime); box-shadow:0 0 7px rgba(200,255,0,.85); }
.imt-td-title{ font-family:var(--sans); font-weight:800; font-size:clamp(24px,3.6vw,38px); letter-spacing:-.01em; margin:12px 0 10px; color:#fff; }
.imt-td-subrow{ display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:16px; }
.imt-td-ref{ font-family:var(--mono); font-size:11px; letter-spacing:.5px; color:var(--lime); background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); padding:3px 9px; border-radius:6px; }
.imt-td-subtxt{ font-size:13px; color:#AEBEC0; }
.imt-td-pills{ display:flex; flex-wrap:wrap; gap:9px; }
.imt-td-pill{ display:inline-flex; align-items:center; gap:6px; font-family:var(--mono); font-size:10.5px; color:#C7D2D2; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.14); padding:7px 12px; border-radius:999px; }
.imt-td-pill svg{ width:13px; height:13px; color:var(--lime); }
.imt-td-pill--lime{ color:#DCEBC4; background:rgba(200,255,0,.12); border-color:rgba(200,255,0,.3); }
/* body */
.imt-td-body{ max-width:1180px; margin:0 auto; padding:28px 24px 64px; display:grid; grid-template-columns:1fr 360px; gap:28px; align-items:start; }
.imt-td-main{ display:flex; flex-direction:column; gap:18px; min-width:0; }
.imt-td-side{ display:flex; flex-direction:column; gap:18px; position:sticky; top:18px; }
.imt-td-card{ background:var(--card); border:1px solid var(--line); border-radius:16px; padding:20px; box-shadow:0 1px 2px rgba(16,40,50,.04); }
.imt-td-head{ display:flex; align-items:center; gap:12px; margin-bottom:16px; }
.imt-td-ic{ width:38px; height:38px; border-radius:11px; display:grid; place-items:center; background:linear-gradient(140deg,var(--teal),var(--teal2)); color:var(--lime); flex:none; }
.imt-td-ic svg{ width:18px; height:18px; }
.imt-td-head h2{ font-family:var(--sans); font-weight:700; font-size:16px; margin:0; color:var(--ink); letter-spacing:-.01em; }
/* gallery */
.imt-td-gallery{ padding:14px; }
.imt-td-mainimg{ width:100%; height:340px; border-radius:12px; background:#E6ECF0 center/cover no-repeat; cursor:zoom-in; }
.imt-td-thumbs{ display:flex; gap:10px; margin-top:10px; }
.imt-td-thumb{ flex:1; height:72px; border-radius:9px; background:#E6ECF0 center/cover no-repeat; cursor:pointer; border:2px solid transparent; }
.imt-td-thumb.is-active{ border-color:var(--teal); }
.imt-td-about{ font-size:13.5px; line-height:1.6; color:var(--slate); margin:0; }
/* kv grid */
.imt-td-kvgrid{ display:grid; grid-template-columns:1fr 1fr; gap:1px; background:var(--line2); border-radius:12px; overflow:hidden; }
.imt-td-kv{ background:#fff; padding:11px 14px; display:flex; flex-direction:column; gap:3px; }
.imt-td-kv.full{ grid-column:1 / -1; }
.imt-td-kv__l{ font-family:var(--sans); font-weight:600; font-size:10px; letter-spacing:.5px; text-transform:uppercase; color:#9CA3AF; }
.imt-td-kv__v{ font-family:var(--sans); font-weight:700; font-size:13.5px; color:var(--val); }
/* compliance / rows */
.imt-td-complist{ display:flex; flex-direction:column; gap:1px; background:var(--line2); border-radius:12px; overflow:hidden; margin-top:12px; }
.imt-td-card > .imt-td-complist:first-of-type{ margin-top:0; }
.imt-td-comp{ background:#fff; padding:11px 14px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.imt-td-comp__l{ font-family:var(--sans); font-weight:600; font-size:12.5px; color:var(--ink); }
.imt-td-comp__r{ font-family:var(--sans); font-weight:700; font-size:12.5px; color:var(--val); }
.imt-td-badge{ display:inline-flex; align-items:center; font-family:var(--sans); font-weight:600; font-size:11px; padding:4px 10px; border-radius:999px; }
.imt-td-badge.is-yes{ background:var(--lime); color:#0A2A2E; }
.imt-td-badge.is-no{ background:#EEF1F5; color:var(--muted); }
/* materials selected */
.imt-td-matcap{ display:flex; align-items:center; gap:8px; font-family:var(--mono); font-size:10.8px; letter-spacing:.8px; color:var(--micro); margin-bottom:14px; }
.imt-td-matbar{ width:3px; height:11px; border-radius:2px; background:var(--lime); }
.imt-td-matcount{ font-size:10px; color:#AAB4BE; }
.imt-td-matgroups{ display:flex; flex-direction:column; gap:13px; }
.imt-td-matlbl{ font-family:var(--mono); font-size:10px; letter-spacing:.6px; color:var(--muted); margin-bottom:8px; }
.imt-td-chips{ display:flex; flex-wrap:wrap; gap:8px; }
.imt-td-chip{ display:inline-flex; align-items:center; gap:7px; padding:9px 14px 9px 10px; border-radius:999px; background:var(--teal); color:#fff; font-family:var(--body); font-weight:500; font-size:13px; }
.imt-td-chip__bx{ width:18px; height:18px; border-radius:6px; background:var(--lime); color:#0A2A2E; display:grid; place-items:center; }
.imt-td-chip__bx svg{ width:12px; height:12px; stroke-width:2.6; }
.imt-td-note,.imt-td-note2{ display:flex; align-items:center; gap:6px; font-size:11.6px; color:var(--muted); margin:14px 0 0; }
.imt-td-note svg{ width:13px; height:13px; color:#B6C0C9; }
.imt-td-note2{ display:block; line-height:1.5; }
/* summary */
.imt-td-summary{ display:flex; flex-direction:column; gap:0; }
.imt-td-sum-ref{ font-family:var(--mono); font-size:10.5px; color:var(--teal); background:#EEF4F2; align-self:flex-start; padding:3px 9px; border-radius:6px; margin-bottom:10px; }
.imt-td-sum-title{ font-family:var(--sans); font-weight:800; font-size:18px; color:var(--ink); line-height:1.25; }
.imt-td-sum-sub{ font-size:12.5px; color:var(--muted); margin:4px 0 14px; }
.imt-td-statgrid{ display:grid; grid-template-columns:1fr 1fr; gap:1px; background:var(--line2); border-radius:12px; overflow:hidden; margin-bottom:14px; }
.imt-td-stat{ background:#fff; padding:12px 14px; display:flex; flex-direction:column; gap:4px; }
.imt-td-stat__l{ font-family:var(--mono); font-size:9.5px; letter-spacing:.4px; color:var(--muted); text-transform:uppercase; }
.imt-td-stat__v{ font-family:var(--sans); font-weight:700; font-size:15px; color:var(--val); }
/* buttons */
.imt-td-btn{ display:inline-flex; align-items:center; justify-content:center; gap:8px; font-family:var(--sans); font-weight:600; font-size:14px; padding:13px 18px; border-radius:11px; border:none; cursor:pointer; text-decoration:none; width:100%; margin-top:8px; transition:transform .12s,box-shadow .2s,background .2s; }
.imt-td-btn svg{ width:16px; height:16px; }
.imt-td-btn--lime{ background:var(--lime); color:#0A2A2E; box-shadow:0 8px 20px -6px rgba(200,255,0,.5); }
.imt-td-btn--lime:hover{ transform:translateY(-1px); }
.imt-td-btn--ghost{ background:#fff; color:var(--teal); border:1.5px solid #D5DCE4; }
.imt-td-btn--ghost:hover{ background:#F4F6F9; }
.imt-td-btn--teal{ background:var(--teal); color:#fff; }
.imt-td-btn--teal svg{ color:var(--lime); }
/* form */
.imt-td-sub2{ font-size:12.5px; color:var(--muted); margin:-6px 0 14px; }
.imt-td-form{ display:flex; flex-direction:column; }
.imt-td-flbl{ font-family:var(--mono); font-size:10px; letter-spacing:.5px; text-transform:uppercase; color:var(--micro); margin:10px 0 5px; }
.imt-td-in{ width:100%; font-family:var(--body); font-size:14px; color:var(--ink); background:#F8FAFB; border:1.5px solid #D9DFE8; border-radius:10px; padding:11px 13px; outline:none; }
.imt-td-in:focus{ border-color:var(--teal); box-shadow:0 0 0 4px rgba(15,61,67,.1); background:#fff; }
textarea.imt-td-in{ resize:vertical; min-height:80px; }
.imt-td-msg{ display:none; font-size:12px; margin-top:10px; padding:9px 12px; border-radius:9px; }
.imt-td-msg--ok{ background:#ecfdf3; color:#15803d; } .imt-td-msg--err{ background:#fef2f2; color:#b91c1c; }
/* owner */
.imt-td-ownerbar{ display:flex; align-items:center; gap:12px; background:#F4F6F9; border-radius:12px; padding:12px 14px; }
.imt-td-ownerbar.is-open{ background:rgba(200,255,0,.08); border:1px solid rgba(200,255,0,.3); }
.imt-td-avatar{ width:42px; height:42px; border-radius:50%; background:var(--teal); color:#fff; display:grid; place-items:center; font-family:var(--sans); font-weight:700; font-size:17px; flex:none; }
.imt-td-ownermeta{ display:flex; flex-direction:column; gap:2px; flex:1; min-width:0; }
.imt-td-uid{ font-family:var(--mono); font-size:11px; letter-spacing:1px; color:var(--teal); }
.imt-td-uidsub{ font-size:11.5px; color:var(--muted); }
.imt-td-lockmini{ color:var(--muted); } .imt-td-lockmini svg{ width:18px; height:18px; }
.imt-td-locked{ text-align:center; padding:8px 0; }
.imt-td-lockic{ font-size:24px; display:block; margin-bottom:8px; color:var(--muted); } .imt-td-lockic svg{ width:26px; height:26px; }
.imt-td-locked p{ font-size:12.5px; color:var(--slate); line-height:1.5; margin:0 0 12px; }
.imt-td-pillstatus{ display:inline-block; font-family:var(--sans); font-weight:600; font-size:11px; padding:4px 12px; border-radius:999px; margin-bottom:8px; }
.imt-td-pillstatus.is-pending{ background:#fef9c3; color:#a16207; } .imt-td-pillstatus.is-rejected{ background:#fee2e2; color:#b91c1c; }
.imt-td-admin{ margin-top:14px; border-top:1px solid var(--line2); padding-top:12px; }
.imt-td-admin__hd{ font-family:var(--mono); font-size:10px; letter-spacing:.5px; text-transform:uppercase; color:var(--teal); margin-bottom:10px; }
.imt-td-admin__row{ display:flex; align-items:center; justify-content:space-between; gap:10px; background:#f9fafb; border-radius:10px; padding:9px 11px; margin-bottom:8px; }
.imt-td-admin__nm{ font-size:12px; font-weight:600; color:var(--ink); } .imt-td-admin__em{ font-size:11px; color:var(--muted); }
.imt-td-admin__act{ display:flex; gap:6px; }
.imt-td-ap,.imt-td-rj{ width:30px; height:30px; border-radius:8px; border:none; cursor:pointer; font-weight:700; }
.imt-td-ap{ background:var(--lime); color:#0A2A2E; } .imt-td-rj{ background:#fee2e2; color:#b91c1c; }
/* lightbox */
.imt-td-lb{ position:fixed; inset:0; background:rgba(0,0,0,.88); display:none; align-items:center; justify-content:center; z-index:99999; padding:30px; }
.imt-td-lb.open{ display:flex; } .imt-td-lb img{ max-width:92%; max-height:92%; border-radius:10px; }
.imt-td-lb__x{ position:absolute; top:20px; right:26px; color:#fff; font-size:30px; cursor:pointer; }
/* responsive */
@media (max-width:980px){
  .imt-td-body{ grid-template-columns:1fr; }
  .imt-td-side{ position:static; }
}
@media (max-width:600px){
  .imt-td-kvgrid{ grid-template-columns:1fr; }
  .imt-td-kv.full{ grid-column:auto; }
  .imt-td-mainimg{ height:230px; }
  .imt-td-hero{ padding:30px 16px 26px; }
  .imt-td-body{ padding:18px 14px 48px; }
}
</style>

<script>
(function(){
  var root=document.querySelector('.imt-td'); if(!root) return;
  window.imtTdSetMain=function(el,src){ var m=document.getElementById('imtTdMain'); if(m){ m.style.backgroundImage="url('"+src+"')"; m.setAttribute('onclick',"imtTdLb('"+src+"')"); } root.querySelectorAll('.imt-td-thumb').forEach(function(t){t.classList.remove('is-active');}); el.classList.add('is-active'); };
  window.imtTdLb=function(src){ var i=document.getElementById('imtTdLbImg'); if(i){ i.src=src; document.getElementById('imtTdLb').classList.add('open'); } };
  window.imtTdCloseLb=function(){ document.getElementById('imtTdLb').classList.remove('open'); };
  document.addEventListener('keydown',function(e){ if(e.key==='Escape') imtTdCloseLb(); });

  var enq=document.getElementById('ihtEnqForm');
  if(enq){ enq.addEventListener('submit',function(e){
    e.preventDefault();
    var btn=document.getElementById('ihtSubmitBtn'); btn.disabled=true; var orig=btn.innerHTML; btn.textContent='Sending…';
    var fd=new FormData(this); fd.append('action','ih_public_machine_enquiry');
    fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){ if(d&&d.success){ document.getElementById('ihtOk').style.display='block'; document.getElementById('ihtErr').style.display='none'; enq.reset(); } else { document.getElementById('ihtErr').style.display='block'; } })
    .catch(function(){ document.getElementById('ihtErr').style.display='block'; })
    .finally(function(){ btn.disabled=false; btn.innerHTML=orig; });
  }); }

  var ihocAjaxUrl='<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
  var ihocNonce='<?php echo esc_js( $oc_nonce ); ?>';
  var ihocListingId=<?php echo (int) $listing_id; ?>;
  var ihocListingType='<?php echo esc_js( $listing_type ); ?>';
  var ihocOwnerId=<?php echo (int) $owner_id; ?>;
  var ihocAdminNonce='<?php echo esc_js( wp_create_nonce( 'ih_admin_approve_contact' ) ); ?>';
  window.ihocSendRequest=function(){
    var btn=document.getElementById('ihocReqBtn'); if(!btn) return; btn.disabled=true; btn.innerHTML='⏳ Sending…';
    var fd=new FormData(); fd.append('action','ih_listing_contact_request'); fd.append('nonce',ihocNonce); fd.append('listing_id',ihocListingId); fd.append('listing_type',ihocListingType); fd.append('owner_id',ihocOwnerId);
    fetch(ihocAjaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      var ok=document.getElementById('ihocMsgOk'), err=document.getElementById('ihocMsgErr');
      if(d&&d.success){ if(ok){ok.textContent='✅ '+((d.data&&d.data.message)||'Request sent! Admin will review shortly.');ok.style.display='block';} if(btn){btn.textContent='⏳ Request Pending';} }
      else { if(err){err.textContent='⚠ '+((d&&d.data)||'Something went wrong.');err.style.display='block';} if(btn){btn.disabled=false;btn.innerHTML='Request Contact Details';} }
    }).catch(function(){ var err=document.getElementById('ihocMsgErr'); if(err){err.textContent='⚠ Network error. Please try again.';err.style.display='block';} if(btn){btn.disabled=false;} });
  };
  window.ihocAdminDecide=function(id,decision,btn){
    btn.disabled=true; btn.textContent='…';
    var fd=new FormData(); fd.append('action','ih_listing_contact_approve'); fd.append('nonce',ihocAdminNonce); fd.append('request_id',id); fd.append('decision',decision);
    fetch(ihocAjaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){ if(d&&d.success){ window.location.reload(); } else { alert('Error: '+((d&&d.data)||'Could not update.')); btn.disabled=false; } }).catch(function(){ alert('Network error.'); btn.disabled=false; });
  };
})();
</script>
