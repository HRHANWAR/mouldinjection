<?php
/**
 * Add Tool — Technical Listing Form (redesign)
 * ---------------------------------------------------------------------------
 * Self-contained body for the `ih-user-add-tool` admin page (or any front page).
 * Markup + scoped CSS (all classes prefixed `imt-at`) + dependency-free JS.
 *
 * HOW TO WIRE IT IN
 *   The page is registered by a plugin (slug `ih-user-add-tool`) that is NOT in
 *   this theme. In that plugin's page-render callback, replace the existing
 *   <form> markup with ONE of these:
 *
 *     // If this file lives in the active theme (it does):
 *     get_template_part( 'add-tool-form' );
 *
 *     // …or, from anywhere:
 *     include get_theme_file_path( 'add-tool-form.php' );
 *
 *   This file renders its own hero/title, so remove the plugin's default
 *   <h1>Add Tool</h1> heading to avoid a duplicate.
 *
 * FIELD NAMES
 *   Existing columns (read by page-tool-detail.php) keep their exact names so a
 *   handler keyed on them keeps working:
 *     title, part_name, part_dimensions, part_weight, num_cavities, material,
 *     material_grade, colour, location, owner_name, part_description,
 *     mould_type, mould_material, mould_condition, num_cavities_spec,
 *     ejector_type, nozzle_type, clamping_required, compatible_specs,
 *     annual_volume, cycle_time, min_order_qty, water_cooled, suck_pump,
 *     food_grade, medical_grade, tolerance_pp, tolerance_abs, tolerance_pe,
 *     listing_date, expiry_date, image_1, image_2, image_3
 *   NEW PDF fields (need columns/handling added server-side to persist):
 *     tolerance, runner_type, gate_type, construction, mould_weight,
 *     mould_dimensions, required_qty, packaging, material_supplied,
 *     clamp_force, shot_weight, tie_bar, opening_stroke,
 *     hot_runner_controller, hot_runner_zones, iml, automation, materials[]
 * ---------------------------------------------------------------------------
 */
if ( ! function_exists( 'imt_at_e' ) ) {
	function imt_at_e( $s ) { return function_exists( 'esc_attr' ) ? esc_attr( $s ) : htmlspecialchars( (string) $s, ENT_QUOTES ); }
}
$imt_at_machine_url = function_exists( 'admin_url' ) ? esc_url( admin_url( 'admin.php?page=ih-user-add-machine' ) ) : '#';
$imt_at_today       = date( 'Y-m-d' );
$imt_at_expiry      = date( 'Y-m-d', strtotime( '+30 days' ) );
?>
<div class="imt-at">

<!-- icon sprite -->
<svg class="imt-at-sprite" aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden">
  <symbol id="ic-box" viewBox="0 0 24 24"><path d="M12 2 3 7v10l9 5 9-5V7z"/><path d="M3 7l9 5 9-5"/><path d="M12 12v10"/></symbol>
  <symbol id="ic-layers" viewBox="0 0 24 24"><path d="M12 3 2 9l10 6 10-6z"/><path d="M2 15l10 6 10-6"/></symbol>
  <symbol id="ic-gauge" viewBox="0 0 24 24"><path d="M3 18a9 9 0 1 1 18 0"/><path d="M12 13l4-3"/><circle cx="12" cy="13" r="1.4"/></symbol>
  <symbol id="ic-sliders" viewBox="0 0 24 24"><path d="M4 6h9"/><path d="M17 6h3"/><circle cx="15" cy="6" r="2"/><path d="M4 18h3"/><path d="M11 18h9"/><circle cx="9" cy="18" r="2"/><path d="M4 12h5"/><path d="M13 12h7"/><circle cx="11" cy="12" r="2"/></symbol>
  <symbol id="ic-droplet" viewBox="0 0 24 24"><path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/></symbol>
  <symbol id="ic-image" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 16-5-5L5 20"/></symbol>
  <symbol id="ic-calendar" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></symbol>
  <symbol id="ic-ruler" viewBox="0 0 24 24"><path d="M3 8l5-5 13 13-5 5z"/><path d="M8 7l1.5 1.5M11 10l1.5 1.5M14 13l1.5 1.5"/></symbol>
  <symbol id="ic-scale" viewBox="0 0 24 24"><path d="M7 10a5 5 0 0 1 10 0"/><rect x="4" y="10" width="16" height="10" rx="2"/></symbol>
  <symbol id="ic-hash" viewBox="0 0 24 24"><path d="M5 9h14M5 15h14M9 4 7 20M17 4l-2 16"/></symbol>
  <symbol id="ic-pin" viewBox="0 0 24 24"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></symbol>
  <symbol id="ic-palette" viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 0 18c1.4 0 2-1 2-2 0-1.4 1-2 2-2h2a3 3 0 0 0 3-3 8 8 0 0 0-11-9z"/><circle cx="7.5" cy="11" r="1"/><circle cx="12" cy="7.5" r="1"/><circle cx="16" cy="11" r="1"/></symbol>
  <symbol id="ic-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
  <symbol id="ic-bolt" viewBox="0 0 24 24"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></symbol>
  <symbol id="ic-thermo" viewBox="0 0 24 24"><path d="M14 14V5a2 2 0 0 0-4 0v9a4 4 0 1 0 4 0z"/></symbol>
  <symbol id="ic-shield" viewBox="0 0 24 24"><path d="M12 3 5 6v6c0 4 3 6.5 7 9 4-2.5 7-5 7-9V6z"/><path d="m9 12 2 2 4-4"/></symbol>
  <symbol id="ic-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></symbol>
  <symbol id="ic-tag" viewBox="0 0 24 24"><path d="M3 3h8l10 10-8 8L3 11z"/><circle cx="8" cy="8" r="1.5"/></symbol>
  <symbol id="ic-arrow" viewBox="0 0 24 24"><path d="M7 17 17 7M17 7H9M17 7v8"/></symbol>
  <symbol id="ic-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
  <symbol id="ic-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></symbol>
  <symbol id="ic-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol>
  <symbol id="ic-beaker" viewBox="0 0 24 24"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3"/><path d="M7.5 15h9"/></symbol>
  <symbol id="ic-package" viewBox="0 0 24 24"><path d="m12 2 9 5v10l-9 5-9-5V7z"/><path d="M3.3 7 12 12l8.7-5M12 22V12"/></symbol>
  <symbol id="ic-check" viewBox="0 0 24 24"><path d="m5 12 5 5 9-11"/></symbol>
  <symbol id="ic-text" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h10"/></symbol>
  <symbol id="ic-cog" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.2"/><path d="M19.4 13a7.5 7.5 0 0 0 0-2l2-1.5-2-3.4-2.4 1a7.5 7.5 0 0 0-1.7-1L14.9 3H10l-.4 2.6a7.5 7.5 0 0 0-1.7 1l-2.4-1-2 3.4 2 1.5a7.5 7.5 0 0 0 0 2l-2 1.5 2 3.4 2.4-1a7.5 7.5 0 0 0 1.7 1L10 21h4.9l.4-2.6a7.5 7.5 0 0 0 1.7-1l2.4 1 2-3.4z"/></symbol>
  <symbol id="ic-funnel" viewBox="0 0 24 24"><path d="M3 5h18l-7 8v6l-4 2v-8z"/></symbol>
  <symbol id="ic-flame" viewBox="0 0 24 24"><path d="M12 3c1 3 4 4 4 8a4 4 0 0 1-8 0c0-2 1-3 1-3s3 1 3-5z"/></symbol>
  <symbol id="ic-trash" viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></symbol>
  <symbol id="ic-upload" viewBox="0 0 24 24"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M5 20h14"/></symbol>
  <symbol id="ic-doc" viewBox="0 0 24 24"><path d="M6 2h8l4 4v16H6z"/><path d="M14 2v4h4M9 13h6M9 17h6"/></symbol>
  <symbol id="ic-spark" viewBox="0 0 24 24"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18"/></symbol>
  <symbol id="ic-wrench" viewBox="0 0 24 24"><path d="M14.5 6.5a4 4 0 0 0-5.3 5.3L3 18l3 3 6.2-6.2a4 4 0 0 0 5.3-5.3l-2.3 2.3-2-2z"/></symbol>
</svg>

<!-- ============ HERO ============ -->
<header class="imt-at-hero">
  <div class="imt-at-hero__grid" aria-hidden="true"></div>
  <div class="imt-at-hero__glow" aria-hidden="true"></div>
  <div class="imt-at-hero__inner">
    <div class="imt-at-hero__top">
      <div>
        <span class="imt-at-eyebrow"><span class="imt-at-eyebrow__dot"></span> TOOL LISTING &middot; NEW SUBMISSION</span>
        <h1 class="imt-at-h1">Create <span class="imt-at-h1__accent">Tool Listing</span></h1>
        <p class="imt-at-lead">Add your mould tool details below. Listings are reviewed by an admin and go live once approved &mdash; the data you enter here is exactly what buyers see on the listing page.</p>
      </div>
      <div class="imt-at-hero__actions">
        <span class="imt-at-statuschip"><span class="imt-at-statuschip__dot"></span> DRAFT &middot; AUTOSAVE ON</span>
        <a class="imt-at-mch" href="<?php echo $imt_at_machine_url; ?>"><svg class="ico"><use href="#ic-plus"/></svg> Add Machine</a>
      </div>
    </div>

    <!-- step rail -->
    <nav class="imt-at-steps" aria-label="Form sections">
      <a class="imt-at-step is-active" data-target="sec-part"><span class="imt-at-step__ic"><svg class="ico"><use href="#ic-box"/></svg></span><span class="imt-at-step__t">Part</span></a>
      <a class="imt-at-step" data-target="sec-mould"><span class="imt-at-step__ic"><svg class="ico"><use href="#ic-layers"/></svg></span><span class="imt-at-step__t">Mould</span></a>
      <a class="imt-at-step" data-target="sec-prod"><span class="imt-at-step__ic"><svg class="ico"><use href="#ic-gauge"/></svg></span><span class="imt-at-step__t">Production</span></a>
      <a class="imt-at-step" data-target="sec-feat"><span class="imt-at-step__ic"><svg class="ico"><use href="#ic-sliders"/></svg></span><span class="imt-at-step__t">Features</span></a>
      <a class="imt-at-step" data-target="sec-mat"><span class="imt-at-step__ic"><svg class="ico"><use href="#ic-droplet"/></svg></span><span class="imt-at-step__t">Materials</span></a>
      <a class="imt-at-step" data-target="sec-media"><span class="imt-at-step__ic"><svg class="ico"><use href="#ic-image"/></svg></span><span class="imt-at-step__t">Media</span></a>
      <a class="imt-at-step" data-target="sec-listing"><span class="imt-at-step__ic"><svg class="ico"><use href="#ic-calendar"/></svg></span><span class="imt-at-step__t">Listing</span></a>
    </nav>
  </div>
</header>

<!-- ============ BODY ============ -->
<div class="imt-at-body">

  <!-- side nav -->
  <aside class="imt-at-nav">
    <div class="imt-at-nav__cap">SECTIONS</div>
    <a class="imt-at-navitem is-active" data-target="sec-part">
      <span class="imt-at-navitem__ic"><svg class="ico"><use href="#ic-box"/></svg></span>
      <span class="imt-at-navitem__txt">Part Information</span>
      <span class="imt-at-navitem__count" data-count-for="sec-part">0/12</span>
    </a>
    <a class="imt-at-navitem" data-target="sec-mould">
      <span class="imt-at-navitem__ic"><svg class="ico"><use href="#ic-layers"/></svg></span>
      <span class="imt-at-navitem__txt">Mould Specifications</span>
      <span class="imt-at-navitem__count" data-count-for="sec-mould">0/11</span>
    </a>
    <a class="imt-at-navitem" data-target="sec-prod">
      <span class="imt-at-navitem__ic"><svg class="ico"><use href="#ic-gauge"/></svg></span>
      <span class="imt-at-navitem__txt">Production Info</span>
      <span class="imt-at-navitem__count" data-count-for="sec-prod">0/8</span>
    </a>
    <a class="imt-at-navitem" data-target="sec-feat">
      <span class="imt-at-navitem__ic"><svg class="ico"><use href="#ic-sliders"/></svg></span>
      <span class="imt-at-navitem__txt">Features &amp; Requirements</span>
      <span class="imt-at-navitem__count" data-count-for="sec-feat">0/6</span>
    </a>
    <a class="imt-at-navitem" data-target="sec-mat">
      <span class="imt-at-navitem__ic"><svg class="ico"><use href="#ic-droplet"/></svg></span>
      <span class="imt-at-navitem__txt">Materials</span>
      <span class="imt-at-navitem__count" data-count-for="sec-mat">0/1</span>
    </a>
    <a class="imt-at-navitem" data-target="sec-media">
      <span class="imt-at-navitem__ic"><svg class="ico"><use href="#ic-image"/></svg></span>
      <span class="imt-at-navitem__txt">Media</span>
      <span class="imt-at-navitem__count" data-count-for="sec-media">0/3</span>
    </a>
    <a class="imt-at-navitem" data-target="sec-listing">
      <span class="imt-at-navitem__ic"><svg class="ico"><use href="#ic-calendar"/></svg></span>
      <span class="imt-at-navitem__txt">Listing Details</span>
      <span class="imt-at-navitem__count" data-count-for="sec-listing">0/3</span>
    </a>

    <div class="imt-at-nav__tip">
      <svg class="ico"><use href="#ic-info"/></svg>
      <span>Fields marked <em>*</em> are required. Everything else strengthens your listing &amp; match quality.</span>
    </div>
  </aside>

  <!-- form -->
  <form class="imt-at-main" method="post" enctype="multipart/form-data" action="" novalidate>
    <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'ih_user_add_tool', 'ih_user_nonce' ); } ?>
    <input type="hidden" name="ih_user_tool_submit" value="1">

    <!-- ===== PART INFORMATION ===== -->
    <section id="sec-part" class="imt-at-card">
      <div class="imt-at-card__head">
        <span class="imt-at-ic"><svg class="ico"><use href="#ic-box"/></svg></span>
        <div class="imt-at-card__ht">
          <h2>Part Information</h2>
          <p>The moulded component this tool produces.</p>
        </div>
        <span class="imt-at-card__idx">01</span>
      </div>
      <div class="imt-at-grid">

        <div class="imt-at-field s6">
          <label class="imt-at-lbl" for="f_title"><svg class="ico"><use href="#ic-box"/></svg> Tool Title <em>*</em></label>
          <input data-count data-req id="f_title" name="title" type="text" placeholder="e.g. Medical Device Housing Mould">
          <p class="imt-at-hint"><svg class="ico"><use href="#ic-info"/></svg> Shown as the listing headline.</p>
        </div>

        <div class="imt-at-field s6">
          <label class="imt-at-lbl" for="f_part_name"><svg class="ico"><use href="#ic-tag"/></svg> Part Name</label>
          <input data-count id="f_part_name" name="part_name" type="text" placeholder="e.g. Inhaler Body">
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_part_dimensions"><svg class="ico"><use href="#ic-ruler"/></svg> Part Dimensions</label>
          <div class="imt-at-control"><input data-count id="f_part_dimensions" name="part_dimensions" type="text" placeholder="120 &times; 80 &times; 35"><span class="imt-at-unit">mm</span></div>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_part_weight"><svg class="ico"><use href="#ic-scale"/></svg> Part Weight</label>
          <div class="imt-at-control"><input data-count id="f_part_weight" name="part_weight" type="number" min="0" step="0.1" placeholder="38"><span class="imt-at-unit">g</span></div>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_num_cavities"><svg class="ico"><use href="#ic-hash"/></svg> Number of Cavities</label>
          <input data-count id="f_num_cavities" name="num_cavities" type="number" min="1" step="1" placeholder="4">
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_material"><svg class="ico"><use href="#ic-beaker"/></svg> Primary Material</label>
          <select data-count id="f_material" name="material">
            <option value="">Select material&hellip;</option>
            <option>PP &mdash; Polypropylene</option>
            <option>ABS</option>
            <option>PE &mdash; Polyethylene</option>
            <option>HDPE</option>
            <option>PC &mdash; Polycarbonate</option>
            <option>PA6 / Nylon</option>
            <option>POM / Acetal</option>
            <option>PMMA / Acrylic</option>
            <option>TPE / TPU</option>
            <option>PVC</option>
            <option>PS</option>
            <option>Other</option>
          </select>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_material_grade"><svg class="ico"><use href="#ic-doc"/></svg> Material Grade</label>
          <input data-count id="f_material_grade" name="material_grade" type="text" placeholder="e.g. Medical 1234">
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_colour"><svg class="ico"><use href="#ic-palette"/></svg> Colour</label>
          <input data-count id="f_colour" name="colour" type="text" placeholder="e.g. Signal White (RAL 9003)">
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_tolerance"><svg class="ico"><use href="#ic-spark"/></svg> Tolerance</label>
          <div class="imt-at-control"><input data-count id="f_tolerance" name="tolerance" type="text" placeholder="&plusmn;0.05"><span class="imt-at-unit">mm</span></div>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_location"><svg class="ico"><use href="#ic-pin"/></svg> Location <em>*</em></label>
          <input data-count data-req id="f_location" name="location" type="text" placeholder="Manchester, UK">
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_owner"><svg class="ico"><use href="#ic-user"/></svg> Owner / Company</label>
          <input data-count id="f_owner" name="owner_name" type="text" placeholder="Precision Mould Co.">
        </div>

        <div class="imt-at-field s6">
          <label class="imt-at-lbl" for="f_surface_finish"><svg class="ico"><use href="#ic-spark"/></svg> Surface Finish</label>
          <input data-count id="f_surface_finish" name="surface_finish" type="text" placeholder="e.g. SPI A2 / Gloss">
        </div>

        <div class="imt-at-field s6">
          <label class="imt-at-lbl" for="f_draft_angle"><svg class="ico"><use href="#ic-ruler"/></svg> Draft Angle</label>
          <div class="imt-at-control"><input data-count id="f_draft_angle" name="draft_angle" type="text" placeholder="e.g. 1.5"><span class="imt-at-unit">&deg;</span></div>
        </div>

        <div class="imt-at-field s12">
          <label class="imt-at-lbl" for="f_part_description"><svg class="ico"><use href="#ic-text"/></svg> Part Function / Description</label>
          <textarea data-count id="f_part_description" name="part_description" rows="3" placeholder="Brief description of the part and its intended function, end market, and any critical features."></textarea>
        </div>

      </div>
    </section>

    <!-- ===== MOULD SPECIFICATIONS ===== -->
    <section id="sec-mould" class="imt-at-card">
      <div class="imt-at-card__head">
        <span class="imt-at-ic"><svg class="ico"><use href="#ic-layers"/></svg></span>
        <div class="imt-at-card__ht">
          <h2>Mould Specifications</h2>
          <p>The construction and configuration of the tool itself.</p>
        </div>
        <span class="imt-at-card__idx">02</span>
      </div>
      <div class="imt-at-grid">

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_mould_type"><svg class="ico"><use href="#ic-layers"/></svg> Mould Type</label>
          <select data-count id="f_mould_type" name="mould_type">
            <option value="">Select type&hellip;</option>
            <option>Single Cavity</option><option>Multi-Cavity</option><option>Family Mould</option>
            <option>Stack Mould</option><option>2K / Overmould</option><option>Insert Mould</option>
            <option>Unscrewing</option>
          </select>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_mould_material"><svg class="ico"><use href="#ic-doc"/></svg> Mould Material</label>
          <select data-count id="f_mould_material" name="mould_material">
            <option value="">Select steel&hellip;</option>
            <option>P20</option><option>H13</option><option>S136</option><option>420 SS</option>
            <option>1.2738 / 718</option><option>NAK80</option><option>Aluminium</option>
            <option>Beryllium Copper</option><option>Other</option>
          </select>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_mould_condition"><svg class="ico"><use href="#ic-shield"/></svg> Tool Condition</label>
          <select data-count id="f_mould_condition" name="mould_condition">
            <option value="">Select condition&hellip;</option>
            <option>New</option><option>Excellent</option><option>Good</option>
            <option>Used &mdash; Refurbished</option><option>Used &mdash; As Is</option>
          </select>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_num_cavities_spec"><svg class="ico"><use href="#ic-hash"/></svg> No. of Cavities (spec)</label>
          <input data-count id="f_num_cavities_spec" name="num_cavities_spec" type="number" min="1" step="1" placeholder="4">
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_runner_type"><svg class="ico"><use href="#ic-funnel"/></svg> Runner Type</label>
          <select data-count id="f_runner_type" name="runner_type">
            <option value="">Select runner&hellip;</option>
            <option>Hot Runner</option><option>Cold Runner</option><option>Insulated Runner</option>
            <option>Hot / Cold Hybrid</option>
          </select>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_gate_type"><svg class="ico"><use href="#ic-funnel"/></svg> Gate Type</label>
          <select data-count id="f_gate_type" name="gate_type">
            <option value="">Select gate&hellip;</option>
            <option>Edge</option><option>Submarine</option><option>Hot Tip</option><option>Valve</option>
            <option>Pin / 3-Plate</option><option>Fan</option><option>Tab</option><option>Direct / Sprue</option>
          </select>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_ejector_type"><svg class="ico"><use href="#ic-cog"/></svg> Ejection Type</label>
          <select data-count id="f_ejector_type" name="ejector_type">
            <option value="">Select ejection&hellip;</option>
            <option>Ejector Pins</option><option>Stripper Plate</option><option>Sleeve</option>
            <option>Blade</option><option>Air</option><option>Two-Stage</option>
          </select>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_nozzle_type"><svg class="ico"><use href="#ic-wrench"/></svg> Nozzle Type</label>
          <select data-count id="f_nozzle_type" name="nozzle_type">
            <option value="">Select nozzle&hellip;</option>
            <option>Open</option><option>Shut-off</option><option>Valve Gate</option><option>Extended</option>
          </select>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_construction"><svg class="ico"><use href="#ic-cog"/></svg> Construction</label>
          <select data-count id="f_construction" name="construction">
            <option value="">Select construction&hellip;</option>
            <option>Fully Hardened</option><option>Pre-Hardened</option>
            <option>Hardened Inserts</option><option>Soft (Prototype)</option>
          </select>
        </div>

        <div class="imt-at-field s6">
          <label class="imt-at-lbl" for="f_mould_weight"><svg class="ico"><use href="#ic-scale"/></svg> Mould Weight</label>
          <div class="imt-at-control"><input data-count id="f_mould_weight" name="mould_weight" type="text" placeholder="850"><span class="imt-at-unit">kg</span></div>
        </div>

        <div class="imt-at-field s6">
          <label class="imt-at-lbl" for="f_mould_dimensions"><svg class="ico"><use href="#ic-ruler"/></svg> Mould Dimensions</label>
          <div class="imt-at-control"><input data-count id="f_mould_dimensions" name="mould_dimensions" type="text" placeholder="496 &times; 396 &times; 450"><span class="imt-at-unit">mm</span></div>
        </div>

        <div class="imt-at-field s6">
          <label class="imt-at-lbl" for="f_tool_life"><svg class="ico"><use href="#ic-clock"/></svg> Tool Life (Guaranteed Shots)</label>
          <input data-count id="f_tool_life" name="tool_life" type="text" placeholder="e.g. 1,000,000">
        </div>

      </div>
    </section>

    <!-- ===== PRODUCTION INFO ===== -->
    <section id="sec-prod" class="imt-at-card">
      <div class="imt-at-card__head">
        <span class="imt-at-ic"><svg class="ico"><use href="#ic-gauge"/></svg></span>
        <div class="imt-at-card__ht">
          <h2>Production Information</h2>
          <p>Volumes, cycle and supply terms.</p>
        </div>
        <span class="imt-at-card__idx">03</span>
      </div>
      <div class="imt-at-grid">

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_required_qty"><svg class="ico"><use href="#ic-hash"/></svg> Required Quantity</label>
          <input data-count id="f_required_qty" name="required_qty" type="text" placeholder="e.g. 250,000">
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_annual_volume"><svg class="ico"><use href="#ic-gauge"/></svg> Annual Volume</label>
          <input data-count id="f_annual_volume" name="annual_volume" type="text" placeholder="e.g. 500,000 / yr">
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_min_order_qty"><svg class="ico"><use href="#ic-package"/></svg> Min Order Qty</label>
          <input data-count id="f_min_order_qty" name="min_order_qty" type="text" placeholder="e.g. 10,000">
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_cycle_time"><svg class="ico"><use href="#ic-clock"/></svg> Cycle Time</label>
          <div class="imt-at-control"><input data-count id="f_cycle_time" name="cycle_time" type="text" placeholder="32"><span class="imt-at-unit">sec</span></div>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_packaging"><svg class="ico"><use href="#ic-package"/></svg> Packaging</label>
          <input data-count id="f_packaging" name="packaging" type="text" placeholder="e.g. Bulk / Cartons">
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_material_supplied"><svg class="ico"><use href="#ic-beaker"/></svg> Material Supplied?</label>
          <select data-count id="f_material_supplied" name="material_supplied">
            <option value="">Select&hellip;</option>
            <option>Yes &mdash; supplied</option><option>No &mdash; customer supplies</option><option>Negotiable</option>
          </select>
        </div>

        <div class="imt-at-field s12">
          <label class="imt-at-lbl" for="f_compatible_specs"><svg class="ico"><use href="#ic-doc"/></svg> Compatible Machine Specs</label>
          <input data-count id="f_compatible_specs" name="compatible_specs" type="text" placeholder="e.g. 120T clamping, 90mm screw, 560&times;560 tie-bar">
        </div>

      </div>
    </section>

    <!-- ===== FEATURES & REQUIREMENTS ===== -->
    <section id="sec-feat" class="imt-at-card">
      <div class="imt-at-card__head">
        <span class="imt-at-ic"><svg class="ico"><use href="#ic-sliders"/></svg></span>
        <div class="imt-at-card__ht">
          <h2>Tooling Features &amp; Requirements</h2>
          <p>Press requirements and compliance capabilities.</p>
        </div>
        <span class="imt-at-card__idx">04</span>
      </div>
      <div class="imt-at-grid">

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_clamp_force"><svg class="ico"><use href="#ic-bolt"/></svg> Clamp Force</label>
          <div class="imt-at-control"><input data-count id="f_clamp_force" name="clamp_force" type="text" placeholder="120"><span class="imt-at-unit">T</span></div>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_shot_weight"><svg class="ico"><use href="#ic-droplet"/></svg> Shot Weight</label>
          <div class="imt-at-control"><input data-count id="f_shot_weight" name="shot_weight" type="text" placeholder="180"><span class="imt-at-unit">g</span></div>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_tie_bar"><svg class="ico"><use href="#ic-ruler"/></svg> Tie-Bar Distance</label>
          <div class="imt-at-control"><input data-count id="f_tie_bar" name="tie_bar" type="text" placeholder="460 &times; 460"><span class="imt-at-unit">mm</span></div>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_opening_stroke"><svg class="ico"><use href="#ic-sliders"/></svg> Opening Stroke</label>
          <div class="imt-at-control"><input data-count id="f_opening_stroke" name="opening_stroke" type="text" placeholder="350"><span class="imt-at-unit">mm</span></div>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_hot_runner_controller"><svg class="ico"><use href="#ic-flame"/></svg> Hot Runner Controller</label>
          <select data-count id="f_hot_runner_controller" name="hot_runner_controller">
            <option value="">Select&hellip;</option>
            <option>Included</option><option>Required (not included)</option><option>Not Required</option>
          </select>
        </div>

        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_hot_runner_zones"><svg class="ico"><use href="#ic-grid"/></svg> Hot Runner Zones</label>
          <input data-count id="f_hot_runner_zones" name="hot_runner_zones" type="number" min="0" step="1" placeholder="8">
        </div>

        <div class="imt-at-field s12">
          <span class="imt-at-grouplbl"><svg class="ico"><use href="#ic-shield"/></svg> Compliance &amp; Capabilities</span>
          <div class="imt-at-toggles">
            <label class="imt-at-toggle">
              <span class="imt-at-toggle__ic"><svg class="ico"><use href="#ic-thermo"/></svg></span>
              <span class="imt-at-toggle__txt"><strong>Water Cooled Chiller</strong><small>Conformal / baffle cooling</small></span>
              <input type="hidden" name="water_cooled" value="No"><input type="checkbox" name="water_cooled" value="Yes"><span class="imt-at-switch"></span>
            </label>
            <label class="imt-at-toggle">
              <span class="imt-at-toggle__ic"><svg class="ico"><use href="#ic-gauge"/></svg></span>
              <span class="imt-at-toggle__txt"><strong>Suck Pump</strong><small>Decompression / suck-back</small></span>
              <input type="hidden" name="suck_pump" value="No"><input type="checkbox" name="suck_pump" value="Yes"><span class="imt-at-switch"></span>
            </label>
            <label class="imt-at-toggle">
              <span class="imt-at-toggle__ic"><svg class="ico"><use href="#ic-shield"/></svg></span>
              <span class="imt-at-toggle__txt"><strong>Food Grade</strong><small>FDA / EU 10/2011</small></span>
              <input type="hidden" name="food_grade" value="No"><input type="checkbox" name="food_grade" value="Yes"><span class="imt-at-switch"></span>
            </label>
            <label class="imt-at-toggle">
              <span class="imt-at-toggle__ic"><svg class="ico"><use href="#ic-shield"/></svg></span>
              <span class="imt-at-toggle__txt"><strong>Medical Grade</strong><small>ISO 13485 / cleanroom</small></span>
              <input type="hidden" name="medical_grade" value="No"><input type="checkbox" name="medical_grade" value="Yes"><span class="imt-at-switch"></span>
            </label>
            <label class="imt-at-toggle">
              <span class="imt-at-toggle__ic"><svg class="ico"><use href="#ic-tag"/></svg></span>
              <span class="imt-at-toggle__txt"><strong>In-Mould Labelling</strong><small>IML capable</small></span>
              <input type="hidden" name="iml" value="No"><input type="checkbox" name="iml" value="Yes"><span class="imt-at-switch"></span>
            </label>
            <label class="imt-at-toggle">
              <span class="imt-at-toggle__ic"><svg class="ico"><use href="#ic-cog"/></svg></span>
              <span class="imt-at-toggle__txt"><strong>Automation Ready</strong><small>Robot take-out / EOAT</small></span>
              <input type="hidden" name="automation" value="No"><input type="checkbox" name="automation" value="Yes"><span class="imt-at-switch"></span>
            </label>
          </div>
        </div>

      </div>
    </section>

    <!-- ===== MATERIALS ===== -->
    <section id="sec-mat" class="imt-at-card">
      <div class="imt-at-card__head">
        <span class="imt-at-ic"><svg class="ico"><use href="#ic-droplet"/></svg></span>
        <div class="imt-at-card__ht">
          <h2>Compatible Materials</h2>
          <p>Select every polymer this tool can run.</p>
        </div>
        <span class="imt-at-card__idx">05</span>
      </div>
      <div class="imt-at-grid">
        <div class="imt-at-field s12" data-count data-group="chips">

          <div class="imt-at-matgrp">
            <span class="imt-at-grouplbl"><span class="imt-at-matbar"></span> COMMODITY</span>
            <div class="imt-at-chips">
              <label class="imt-at-chip"><input type="checkbox" name="tolerance_pp" value="1" checked><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PP</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="tolerance_pe" value="1"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PE</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="HDPE"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">HDPE</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="LDPE"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">LDPE</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PS"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PS</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="HIPS"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">HIPS</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PVC"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PVC</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PET"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PET</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="SAN"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">SAN</span></label>
            </div>
          </div>

          <div class="imt-at-matgrp">
            <span class="imt-at-grouplbl"><span class="imt-at-matbar"></span> ENGINEERING</span>
            <div class="imt-at-chips">
              <label class="imt-at-chip"><input type="checkbox" name="tolerance_abs" value="1" checked><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">ABS</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PC"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PC</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PC/ABS"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PC/ABS</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PA6"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PA6</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PA66"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PA66</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PA11"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PA11</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PA12"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PA12</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="POM"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">POM (Acetal)</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PMMA"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PMMA (Acrylic)</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PBT"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PBT</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="ASA"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">ASA</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PPO/PPE"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PPO/PPE</span></label>
            </div>
          </div>

          <div class="imt-at-matgrp">
            <span class="imt-at-grouplbl"><span class="imt-at-matbar"></span> ELASTOMERS / FLEXIBLE</span>
            <div class="imt-at-chips">
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="TPE"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">TPE</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="TPU"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">TPU</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="TPV"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">TPV</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="TPO"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">TPO</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="LSR"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">LSR (Silicone)</span></label>
            </div>
          </div>

          <div class="imt-at-matgrp">
            <span class="imt-at-grouplbl"><span class="imt-at-matbar"></span> HIGH-PERFORMANCE</span>
            <div class="imt-at-chips">
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PEEK"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PEEK</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PEI"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PEI (Ultem)</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PPS"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PPS</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PSU"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PSU</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PPSU"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PPSU</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="LCP"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">LCP</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="PTFE"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">PTFE</span></label>
            </div>
          </div>

          <div class="imt-at-matgrp">
            <span class="imt-at-grouplbl"><span class="imt-at-matbar"></span> THERMOSETS (SPECIALIST)</span>
            <div class="imt-at-chips">
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="Epoxy"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">Epoxy</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="Phenolic"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">Phenolic</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="BMC"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">BMC</span></label>
            </div>
          </div>

          <div class="imt-at-matgrp">
            <span class="imt-at-grouplbl"><span class="imt-at-matbar"></span> GRADE MODIFIERS</span>
            <div class="imt-at-chips">
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="Glass-filled (GF)"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">Glass-filled (GF)</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="Carbon-fibre (CF)"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">Carbon-fibre (CF)</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="Mineral-filled"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">Mineral-filled</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="Flame-retardant (FR)"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">Flame-retardant (FR)</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="UV-stabilised"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">UV-stabilised</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="Food-contact"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">Food-contact</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="Medical-grade"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">Medical-grade</span></label>
              <label class="imt-at-chip"><input type="checkbox" name="materials[]" value="Anti-static / Conductive"><span class="imt-at-chip__box"><svg class="ico"><use href="#ic-check"/></svg></span><span class="imt-at-chip__t">Anti-static / Conductive</span></label>
            </div>
          </div>

          <p class="imt-at-hint"><svg class="ico"><use href="#ic-info"/></svg> PP, ABS &amp; PE map to the existing listing filters; all other grades are saved to <code>materials[]</code> tags.</p>
        </div>
      </div>
    </section>

    <!-- ===== MEDIA ===== -->
    <section id="sec-media" class="imt-at-card">
      <div class="imt-at-card__head">
        <span class="imt-at-ic"><svg class="ico"><use href="#ic-image"/></svg></span>
        <div class="imt-at-card__ht">
          <h2>Media</h2>
          <p>Up to 3 images. First image becomes the listing cover.</p>
        </div>
        <span class="imt-at-card__idx">06</span>
      </div>
      <div class="imt-at-grid">
        <div class="imt-at-field s12">
          <div class="imt-at-uploads">
            <label class="imt-at-drop" data-count>
              <input type="file" name="image_1" accept="image/*" hidden>
              <span class="imt-at-drop__ph">
                <svg class="ico"><use href="#ic-upload"/></svg>
                <strong>Cover image</strong>
                <small>Click or drop &middot; JPG/PNG</small>
              </span>
              <button type="button" class="imt-at-drop__rm" title="Remove"><svg class="ico"><use href="#ic-trash"/></svg></button>
            </label>
            <label class="imt-at-drop" data-count>
              <input type="file" name="image_2" accept="image/*" hidden>
              <span class="imt-at-drop__ph">
                <svg class="ico"><use href="#ic-upload"/></svg>
                <strong>Image 2</strong>
                <small>Click or drop</small>
              </span>
              <button type="button" class="imt-at-drop__rm" title="Remove"><svg class="ico"><use href="#ic-trash"/></svg></button>
            </label>
            <label class="imt-at-drop" data-count>
              <input type="file" name="image_3" accept="image/*" hidden>
              <span class="imt-at-drop__ph">
                <svg class="ico"><use href="#ic-upload"/></svg>
                <strong>Image 3</strong>
                <small>Click or drop</small>
              </span>
              <button type="button" class="imt-at-drop__rm" title="Remove"><svg class="ico"><use href="#ic-trash"/></svg></button>
            </label>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== LISTING DETAILS ===== -->
    <section id="sec-listing" class="imt-at-card">
      <div class="imt-at-card__head">
        <span class="imt-at-ic"><svg class="ico"><use href="#ic-calendar"/></svg></span>
        <div class="imt-at-card__ht">
          <h2>Listing Details</h2>
          <p>Visibility window for this listing.</p>
        </div>
        <span class="imt-at-card__idx">07</span>
      </div>
      <div class="imt-at-grid">
        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_listing_date"><svg class="ico"><use href="#ic-calendar"/></svg> Listing Date</label>
          <input data-count id="f_listing_date" name="listing_date" type="date" value="<?php echo imt_at_e( $imt_at_today ); ?>">
        </div>
        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_expiry_date"><svg class="ico"><use href="#ic-calendar"/></svg> Expiry Date</label>
          <input data-count id="f_expiry_date" name="expiry_date" type="date" value="<?php echo imt_at_e( $imt_at_expiry ); ?>">
        </div>
        <div class="imt-at-field s4">
          <label class="imt-at-lbl" for="f_tool_ref"><svg class="ico"><use href="#ic-tag"/></svg> Tool Reference</label>
          <input id="f_tool_ref" name="tool_ref" type="text" value="Auto-generated on submit" readonly>
        </div>
      </div>
    </section>

    <!-- ===== STICKY ACTION BAR ===== -->
    <div class="imt-at-actionbar">
      <div class="imt-at-ab__left">
        <div class="imt-at-ab__ring">
          <svg viewBox="0 0 44 44"><circle class="bg" cx="22" cy="22" r="18"/><circle class="fg" cx="22" cy="22" r="18"/></svg>
          <span class="imt-at-ab__pct" id="atPct">0%</span>
        </div>
        <div class="imt-at-ab__meta">
          <strong>Listing completeness</strong>
          <span id="atReq"><b>0</b> of 2 required fields complete</span>
        </div>
      </div>
      <div class="imt-at-ab__actions">
        <button type="submit" name="save_draft" value="1" class="imt-at-btn ghost"><svg class="ico"><use href="#ic-doc"/></svg> Save draft</button>
        <button type="submit" class="imt-at-btn lime">Submit Tool Listing <svg class="ico"><use href="#ic-arrow"/></svg></button>
      </div>
    </div>

  </form>
</div>

<div class="imt-at-toast" id="atToast"></div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Montserrat:wght@500;600;700;800&family=Poppins:wght@400;500;600&display=swap');

.imt-at{
  --bg:#EEF1F5; --card:#ffffff; --line:#E4E8EF; --line-2:#EDF0F5;
  --ink:#16242B; --slate:#4F6068; --muted:#8593A0; --micro:#7C8A95;
  --teal:#0F3D43; --teal-2:#12343A; --teal-deep:#001A1E;
  --lime:#C8FF00; --lime-deep:#7E9A0A; --req:#DB5A3C;
  --mono:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,monospace;
  --sans:'Montserrat',system-ui,sans-serif;
  --body:'Poppins',system-ui,sans-serif;
  background:var(--bg); color:var(--ink); font-family:var(--body);
  -webkit-font-smoothing:antialiased; line-height:1.55;
}
.imt-at *{ box-sizing:border-box; }
.imt-at .ico{ width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; flex:none; }

/* ---------- HERO ---------- */
.imt-at-hero{
  position:relative; overflow:hidden; isolation:isolate;
  background:linear-gradient(120deg,var(--teal-deep) 0%,#0C2A2E 52%,#12343A 100%);
  color:#fff; padding:40px 28px 0;
}
.imt-at-hero__grid{ position:absolute; inset:0; z-index:0; pointer-events:none;
  background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);
  background-size:108px 108px; -webkit-mask-image:linear-gradient(180deg,#000,rgba(0,0,0,.35)); mask-image:linear-gradient(180deg,#000,rgba(0,0,0,.35)); }
.imt-at-hero__glow{ position:absolute; top:-200px; right:-120px; width:680px; height:520px; z-index:0; pointer-events:none;
  background:radial-gradient(closest-side,rgba(200,255,0,.16),rgba(200,255,0,0)); filter:blur(16px); }
.imt-at-hero__inner{ position:relative; z-index:1; max-width:1180px; margin:0 auto; }
.imt-at-hero__top{ display:flex; align-items:flex-start; justify-content:space-between; gap:24px; flex-wrap:wrap; }
.imt-at-eyebrow{ display:inline-flex; align-items:center; gap:8px; font-family:var(--mono); font-size:11.5px; letter-spacing:1.4px; color:#DCEBC4; }
.imt-at-eyebrow__dot{ width:7px; height:7px; border-radius:50%; background:var(--lime); box-shadow:0 0 7px rgba(200,255,0,.85); }
.imt-at-h1{ font-family:var(--sans); font-weight:800; font-size:clamp(26px,3.4vw,38px); letter-spacing:-.01em; margin:12px 0 10px; color:#fff; }
.imt-at-h1__accent{ color:var(--lime); }
.imt-at-lead{ max-width:620px; font-size:14.5px; line-height:1.6; color:#AEBEC0; margin:0 0 4px; }
.imt-at-hero__actions{ display:flex; flex-direction:column; align-items:flex-end; gap:12px; }
.imt-at-statuschip{ display:inline-flex; align-items:center; gap:8px; font-family:var(--mono); font-size:10.5px; letter-spacing:.8px; color:#CFE6A8; background:rgba(200,255,0,.08); border:1px solid rgba(200,255,0,.25); padding:6px 12px; border-radius:999px; }
.imt-at-statuschip__dot{ width:7px; height:7px; border-radius:50%; background:var(--lime); animation:atpulse 2s infinite; }
@keyframes atpulse{ 0%,100%{ opacity:1 } 50%{ opacity:.35 } }
.imt-at-mch{ display:inline-flex; align-items:center; gap:8px; font-family:var(--sans); font-weight:600; font-size:13.5px; color:#fff; text-decoration:none; padding:9px 16px; border-radius:999px; border:1px solid rgba(255,255,255,.22); transition:background .2s; }
.imt-at-mch:hover{ background:rgba(255,255,255,.08); color:#fff; }

/* step rail */
.imt-at-steps{ position:relative; z-index:1; display:flex; gap:6px; margin-top:26px; padding:14px 0 0; overflow-x:auto; }
.imt-at-steps::before{ content:""; position:absolute; left:18px; right:18px; top:34px; height:2px; background:rgba(255,255,255,.10); z-index:-1; }
.imt-at-step{ flex:1 0 auto; display:flex; flex-direction:column; align-items:center; gap:7px; cursor:pointer; text-decoration:none; min-width:74px; padding-bottom:14px; border-bottom:2px solid transparent; transition:border-color .2s; }
.imt-at-step__ic{ width:40px; height:40px; border-radius:11px; display:grid; place-items:center; background:#0B2529; border:1px solid rgba(255,255,255,.12); color:#9FB0A6; transition:all .2s; }
.imt-at-step__ic .ico{ width:18px; height:18px; }
.imt-at-step__t{ font-family:var(--mono); font-size:11px; letter-spacing:.4px; color:#8FA0A6; transition:color .2s; }
.imt-at-step.is-active .imt-at-step__ic{ background:var(--lime); border-color:var(--lime); color:#0A2A2E; box-shadow:0 6px 16px -6px rgba(200,255,0,.6); }
.imt-at-step.is-active .imt-at-step__t{ color:#fff; }
.imt-at-step.is-done .imt-at-step__ic{ background:rgba(200,255,0,.14); border-color:rgba(200,255,0,.4); color:var(--lime); }

/* ---------- BODY LAYOUT ---------- */
.imt-at-body{ max-width:1180px; margin:0 auto; padding:28px; display:grid; grid-template-columns:248px 1fr; gap:28px; align-items:start; }

/* side nav */
.imt-at-nav{ position:sticky; top:18px; background:var(--card); border:1px solid var(--line); border-radius:16px; padding:14px; display:flex; flex-direction:column; gap:3px; box-shadow:0 1px 2px rgba(16,40,50,.04); }
.imt-at-nav__cap{ font-family:var(--mono); font-size:10.5px; letter-spacing:1.4px; color:var(--micro); padding:6px 10px 10px; }
.imt-at-navitem{ display:flex; align-items:center; gap:11px; padding:10px 11px; border-radius:11px; cursor:pointer; text-decoration:none; color:var(--slate); position:relative; transition:background .15s,color .15s; }
.imt-at-navitem:hover{ background:#F4F6F9; }
.imt-at-navitem__ic{ width:30px; height:30px; border-radius:9px; display:grid; place-items:center; background:#F0F3F7; color:var(--teal); transition:all .15s; }
.imt-at-navitem__ic .ico{ width:16px; height:16px; }
.imt-at-navitem__txt{ flex:1; font-size:13.2px; font-weight:500; color:var(--ink); }
.imt-at-navitem__count{ font-family:var(--mono); font-size:10.5px; color:var(--muted); background:#F0F3F7; border-radius:6px; padding:3px 7px; }
.imt-at-navitem.is-active{ background:var(--teal); }
.imt-at-navitem.is-active .imt-at-navitem__txt{ color:#fff; }
.imt-at-navitem.is-active .imt-at-navitem__ic{ background:var(--lime); color:#0A2A2E; }
.imt-at-navitem.is-active .imt-at-navitem__count{ background:rgba(255,255,255,.16); color:#EAF2D6; }
.imt-at-navitem.is-complete .imt-at-navitem__count{ background:rgba(126,154,10,.14); color:var(--lime-deep); }
.imt-at-navitem.is-active.is-complete .imt-at-navitem__count{ background:var(--lime); color:#0A2A2E; }
.imt-at-nav__tip{ display:flex; gap:9px; align-items:flex-start; margin-top:8px; padding:12px; border-radius:11px; background:#F4F6F9; color:var(--slate); font-size:11.6px; line-height:1.5; }
.imt-at-nav__tip .ico{ color:var(--teal); margin-top:1px; }
.imt-at-nav__tip em{ color:var(--req); font-style:normal; font-weight:700; }

/* ---------- CARDS ---------- */
.imt-at-main{ display:flex; flex-direction:column; gap:20px; min-width:0; }
.imt-at-card{ background:var(--card); border:1px solid var(--line); border-radius:18px; padding:24px 26px 26px; scroll-margin-top:18px; box-shadow:0 1px 2px rgba(16,40,50,.04); }
.imt-at-card__head{ display:flex; align-items:center; gap:14px; padding-bottom:18px; margin-bottom:20px; border-bottom:1px solid var(--line-2); }
.imt-at-ic{ width:42px; height:42px; border-radius:12px; display:grid; place-items:center; background:linear-gradient(140deg,var(--teal) 0%,var(--teal-2) 100%); color:var(--lime); flex:none; box-shadow:0 6px 14px -6px rgba(15,61,67,.5); }
.imt-at-ic .ico{ width:20px; height:20px; }
.imt-at-card__ht{ flex:1; min-width:0; }
.imt-at-card__ht h2{ font-family:var(--sans); font-weight:700; font-size:18px; margin:0; letter-spacing:-.01em; color:var(--ink); }
.imt-at-card__ht p{ margin:3px 0 0; font-size:12.8px; color:var(--muted); }
.imt-at-card__idx{ font-family:var(--mono); font-size:12px; color:#C2CCD4; letter-spacing:1px; }

/* grid */
.imt-at-grid{ display:grid; grid-template-columns:repeat(12,1fr); gap:16px 18px; }
.imt-at-field.s12{ grid-column:span 12; } .imt-at-field.s6{ grid-column:span 6; }
.imt-at-field.s4{ grid-column:span 4; } .imt-at-field.s3{ grid-column:span 3; }

/* fields */
.imt-at-field{ display:flex; flex-direction:column; gap:7px; min-width:0; }
.imt-at-lbl{ display:flex; align-items:center; gap:7px; font-family:var(--mono); font-size:10.8px; font-weight:500; letter-spacing:.7px; text-transform:uppercase; color:var(--micro); }
.imt-at-lbl .ico{ width:13px; height:13px; color:var(--teal); }
.imt-at-lbl em{ color:var(--req); font-style:normal; font-weight:700; margin-left:-2px; }
.imt-at-field input[type=text], .imt-at-field input[type=number], .imt-at-field input[type=date], .imt-at-field select, .imt-at-field textarea{
  width:100%; font-family:var(--body); font-size:14px; color:var(--ink); background:#fff;
  border:1.5px solid #D9DFE8; border-radius:11px; padding:11px 13px; outline:none; transition:border-color .15s,box-shadow .15s; line-height:1.4;
}
.imt-at-field textarea{ resize:vertical; min-height:84px; line-height:1.55; }
.imt-at-field input::placeholder, .imt-at-field textarea::placeholder{ color:#A7B1BB; }
.imt-at-field input:focus, .imt-at-field select:focus, .imt-at-field textarea:focus{ border-color:var(--teal); box-shadow:0 0 0 4px rgba(15,61,67,.10); }
.imt-at-field select{ appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%235C6B72' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 5l4 4 4-4'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 13px center; padding-right:34px; cursor:pointer; }
.imt-at-field input[readonly]{ background:#F4F6F9; color:var(--muted); cursor:not-allowed; }
.imt-at-control{ position:relative; }
.imt-at-control input{ padding-right:46px; }
.imt-at-unit{ position:absolute; right:13px; top:50%; transform:translateY(-50%); font-family:var(--mono); font-size:11.5px; color:var(--muted); pointer-events:none; }
.imt-at-hint{ display:flex; align-items:center; gap:6px; margin:1px 0 0; font-size:11.4px; color:var(--muted); }
.imt-at-hint .ico{ width:13px; height:13px; color:#B6C0C9; }
.imt-at-field.is-error input, .imt-at-field.is-error textarea, .imt-at-field.is-error select{ border-color:var(--req); box-shadow:0 0 0 4px rgba(219,90,60,.12); }

/* group label */
.imt-at-grouplbl{ display:flex; align-items:center; gap:7px; font-family:var(--mono); font-size:10.8px; font-weight:500; letter-spacing:.7px; text-transform:uppercase; color:var(--micro); margin-bottom:11px; }
.imt-at-grouplbl .ico{ width:13px; height:13px; color:var(--teal); }

/* toggles */
.imt-at-toggles{ display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.imt-at-toggle{ display:flex; align-items:center; gap:12px; padding:13px 14px; border:1.5px solid #E2E7EE; border-radius:13px; cursor:pointer; transition:border-color .15s,background .15s; background:#FCFDFE; }
.imt-at-toggle:hover{ border-color:#CDD6DF; }
.imt-at-toggle__ic{ width:34px; height:34px; border-radius:10px; display:grid; place-items:center; background:#F0F3F7; color:var(--teal); flex:none; transition:all .15s; }
.imt-at-toggle__ic .ico{ width:17px; height:17px; }
.imt-at-toggle__txt{ flex:1; display:flex; flex-direction:column; line-height:1.25; min-width:0; }
.imt-at-toggle__txt strong{ font-size:13px; font-weight:600; color:var(--ink); }
.imt-at-toggle__txt small{ font-size:11px; color:var(--muted); }
.imt-at-toggle input{ position:absolute; opacity:0; width:0; height:0; }
.imt-at-switch{ position:relative; width:42px; height:24px; border-radius:999px; background:#D4DBE3; flex:none; transition:background .2s; }
.imt-at-switch::after{ content:""; position:absolute; top:2px; left:2px; width:20px; height:20px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.25); transition:transform .2s; }
.imt-at-toggle input:checked ~ .imt-at-switch{ background:var(--lime); }
.imt-at-toggle input:checked ~ .imt-at-switch::after{ transform:translateX(18px); }
.imt-at-toggle.is-on{ border-color:var(--lime); background:rgba(200,255,0,.07); }
.imt-at-toggle.is-on .imt-at-toggle__ic{ background:var(--teal); color:var(--lime); }

/* material chips */
.imt-at-chips{ display:flex; flex-wrap:wrap; gap:9px; }
.imt-at-matgrp{ margin-top:16px; }
.imt-at-matgrp:first-child{ margin-top:0; }
.imt-at-matbar{ display:inline-block; width:3px; height:11px; border-radius:2px; background:var(--lime); flex:none; }
.imt-at-field code{ font-family:var(--mono); font-size:11px; background:#EEF1F5; padding:1px 5px; border-radius:5px; color:var(--teal); }
.imt-at-chip{ position:relative; display:inline-flex; align-items:center; gap:8px; padding:9px 14px 9px 11px; border:1.5px solid #DCE2EA; border-radius:999px; cursor:pointer; font-size:13px; font-weight:500; color:var(--slate); transition:all .15s; user-select:none; }
.imt-at-chip:hover{ border-color:#C2CCD6; }
.imt-at-chip input{ position:absolute; opacity:0; width:0; height:0; }
.imt-at-chip__box{ width:18px; height:18px; border-radius:6px; border:1.5px solid #CAD3DC; display:grid; place-items:center; color:transparent; transition:all .15s; }
.imt-at-chip__box .ico{ width:12px; height:12px; stroke-width:2.6; }
.imt-at-chip.is-on{ border-color:var(--teal); background:var(--teal); color:#fff; }
.imt-at-chip.is-on .imt-at-chip__box{ background:var(--lime); border-color:var(--lime); color:#0A2A2E; }

/* uploads */
.imt-at-uploads{ display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
.imt-at-drop{ position:relative; aspect-ratio:4/3; border:1.5px dashed #C7D0DA; border-radius:14px; display:grid; place-items:center; cursor:pointer; background:#FAFBFD; overflow:hidden; transition:border-color .15s,background .15s; }
.imt-at-drop:hover{ border-color:var(--teal); background:#F4F8F8; }
.imt-at-drop.is-drag{ border-color:var(--lime); background:rgba(200,255,0,.08); }
.imt-at-drop__ph{ display:flex; flex-direction:column; align-items:center; gap:5px; text-align:center; color:var(--muted); padding:10px; }
.imt-at-drop__ph .ico{ width:24px; height:24px; color:var(--teal); margin-bottom:2px; }
.imt-at-drop__ph strong{ font-size:12.5px; font-weight:600; color:var(--slate); }
.imt-at-drop__ph small{ font-size:10.8px; }
.imt-at-drop__rm{ position:absolute; top:8px; right:8px; width:28px; height:28px; border-radius:8px; border:none; background:rgba(0,0,0,.55); color:#fff; display:none; place-items:center; cursor:pointer; }
.imt-at-drop__rm .ico{ width:15px; height:15px; }
.imt-at-drop.is-filled{ border-style:solid; border-color:var(--line); background-size:cover; background-position:center; }
.imt-at-drop.is-filled .imt-at-drop__ph{ display:none; }
.imt-at-drop.is-filled .imt-at-drop__rm{ display:grid; }

/* action bar */
.imt-at-actionbar{ position:sticky; bottom:14px; margin-top:4px; display:flex; align-items:center; justify-content:space-between; gap:18px; flex-wrap:wrap;
  background:rgba(255,255,255,.92); -webkit-backdrop-filter:blur(10px); backdrop-filter:blur(10px); border:1px solid var(--line); border-radius:16px; padding:14px 18px; box-shadow:0 14px 34px -12px rgba(16,40,50,.28); }
.imt-at-ab__left{ display:flex; align-items:center; gap:14px; }
.imt-at-ab__ring{ position:relative; width:46px; height:46px; flex:none; }
.imt-at-ab__ring svg{ width:46px; height:46px; transform:rotate(-90deg); }
.imt-at-ab__ring circle{ fill:none; stroke-width:4; }
.imt-at-ab__ring .bg{ stroke:#E6EAF0; }
.imt-at-ab__ring .fg{ stroke:var(--lime); stroke-linecap:round; stroke-dasharray:113; stroke-dashoffset:113; transition:stroke-dashoffset .5s ease; }
.imt-at-ab__pct{ position:absolute; inset:0; display:grid; place-items:center; font-family:var(--mono); font-size:11px; font-weight:700; color:var(--teal); }
.imt-at-ab__meta{ display:flex; flex-direction:column; line-height:1.35; }
.imt-at-ab__meta strong{ font-size:13.5px; font-weight:600; color:var(--ink); }
.imt-at-ab__meta span{ font-size:12px; color:var(--muted); }
.imt-at-ab__meta b{ color:var(--teal); font-weight:700; }
.imt-at-ab__actions{ display:flex; gap:10px; }
.imt-at-btn{ display:inline-flex; align-items:center; gap:8px; font-family:var(--sans); font-weight:600; font-size:14px; padding:12px 20px; border-radius:11px; border:none; cursor:pointer; transition:transform .12s,box-shadow .2s,background .2s; }
.imt-at-btn .ico{ width:17px; height:17px; }
.imt-at-btn.ghost{ background:#fff; color:var(--teal); border:1.5px solid #D5DCE4; }
.imt-at-btn.ghost:hover{ background:#F4F6F9; }
.imt-at-btn.lime{ background:var(--lime); color:#0A2A2E; box-shadow:0 8px 20px -6px rgba(200,255,0,.5); }
.imt-at-btn.lime:hover{ transform:translateY(-1px); box-shadow:0 12px 26px -6px rgba(200,255,0,.6); }

/* toast */
.imt-at-toast{ position:fixed; left:50%; bottom:28px; transform:translateX(-50%) translateY(20px); background:var(--teal); color:#fff; font-size:13.5px; font-weight:500; padding:13px 20px; border-radius:12px; box-shadow:0 16px 40px -10px rgba(0,0,0,.4); opacity:0; pointer-events:none; transition:all .3s; z-index:99999; display:flex; align-items:center; gap:9px; }
.imt-at-toast .ico{ color:var(--lime); }
.imt-at-toast.show{ opacity:1; transform:translateX(-50%) translateY(0); }

/* ---------- RESPONSIVE ---------- */
@media (max-width:980px){
  .imt-at-body{ grid-template-columns:1fr; }
  .imt-at-nav{ position:static; flex-direction:row; flex-wrap:wrap; }
  .imt-at-nav__cap{ width:100%; padding-bottom:4px; }
  .imt-at-navitem{ flex:1 1 auto; }
  .imt-at-navitem__count{ display:none; }
  .imt-at-nav__tip{ display:none; }
  .imt-at-toggles{ grid-template-columns:repeat(2,1fr); }
}
@media (max-width:680px){
  .imt-at-field.s6, .imt-at-field.s4, .imt-at-field.s3{ grid-column:span 12; }
  .imt-at-toggles{ grid-template-columns:1fr; }
  .imt-at-uploads{ grid-template-columns:1fr; }
  .imt-at-hero__top{ flex-direction:column; }
  .imt-at-hero__actions{ flex-direction:row; align-items:center; }
  .imt-at-actionbar{ flex-direction:column; align-items:stretch; }
  .imt-at-ab__actions{ justify-content:stretch; }
  .imt-at-ab__actions .imt-at-btn{ flex:1; justify-content:center; }
  .imt-at-navitem__txt{ font-size:12px; }
}
/* submit loading / disabled state (prevents double submit) */
.imt-at-actionbar.is-submitting .imt-at-btn{ pointer-events:none; opacity:.72; }
.imt-at-btn.is-loading{ color:transparent!important; position:relative; }
.imt-at-btn.is-loading .ico{ visibility:hidden; }
.imt-at-btn.is-loading::after{ content:""; position:absolute; top:50%; left:50%; width:16px; height:16px; margin:-8px 0 0 -8px; border:2px solid rgba(20,47,50,.3); border-top-color:#142F32; border-radius:50%; animation:imtatspin .6s linear infinite; }
@keyframes imtatspin{ to{ transform:rotate(360deg); } }
</style>

<script>
(function(){
  var root = document.querySelector('.imt-at');
  if(!root || root.dataset.bound) return; root.dataset.bound = '1';
  var form = root.querySelector('.imt-at-main');
  var sections = Array.prototype.slice.call(root.querySelectorAll('.imt-at-card'));
  var navItems = Array.prototype.slice.call(root.querySelectorAll('.imt-at-navitem'));
  var steps = Array.prototype.slice.call(root.querySelectorAll('.imt-at-step'));

  function bindScroll(els){
    els.forEach(function(el){
      el.addEventListener('click', function(e){
        e.preventDefault();
        var t = document.getElementById(el.getAttribute('data-target'));
        if(t) window.scrollTo({ top: t.getBoundingClientRect().top + window.pageYOffset - 14, behavior:'smooth' });
      });
    });
  }
  bindScroll(navItems); bindScroll(steps);

  function setActive(id){
    navItems.forEach(function(n){ n.classList.toggle('is-active', n.getAttribute('data-target')===id); });
    steps.forEach(function(s){ s.classList.toggle('is-active', s.getAttribute('data-target')===id); });
  }
  if('IntersectionObserver' in window){
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){ if(en.isIntersecting) setActive(en.target.id); });
    }, { rootMargin:'-30% 0px -60% 0px', threshold:0 });
    sections.forEach(function(s){ io.observe(s); });
  }

  function isFilled(el){
    if(el.getAttribute('data-group')==='chips'){ return !!el.querySelector('input:checked'); }
    if(el.classList.contains('imt-at-drop')){ return el.classList.contains('is-filled'); }
    var tag = el.tagName.toLowerCase();
    if(tag==='input' || tag==='select' || tag==='textarea'){ return String(el.value||'').trim()!==''; }
    return false;
  }
  function recount(){
    var totalReq = 0, doneReq = 0;
    sections.forEach(function(sec){
      var fields = Array.prototype.slice.call(sec.querySelectorAll('[data-count]'));
      var filled = 0;
      fields.forEach(function(f){ if(isFilled(f)) filled++; });
      var badge = root.querySelector('[data-count-for="'+sec.id+'"]');
      if(badge){ badge.textContent = filled + '/' + fields.length; }
      var nav = navItems.filter(function(n){ return n.getAttribute('data-target')===sec.id; })[0];
      if(nav){ nav.classList.toggle('is-complete', fields.length>0 && filled===fields.length); }
      var st = steps.filter(function(s){ return s.getAttribute('data-target')===sec.id; })[0];
      if(st){ st.classList.toggle('is-done', fields.length>0 && filled===fields.length); }
      sec.querySelectorAll('[data-req]').forEach(function(r){ totalReq++; if(isFilled(r)) doneReq++; });
    });
    var pct = totalReq ? Math.round((doneReq/totalReq)*100) : 0;
    var pctEl = root.querySelector('#atPct'); if(pctEl) pctEl.textContent = pct + '%';
    var fg = root.querySelector('.imt-at-ab__ring .fg'); if(fg) fg.style.strokeDashoffset = String(113 - (113*pct/100));
    var req = root.querySelector('#atReq b'); if(req) req.textContent = doneReq;
  }
  form.addEventListener('input', recount);
  form.addEventListener('change', recount);

  /* toggle / chip active classes (no :has() dependency) */
  root.querySelectorAll('.imt-at-toggle input[type=checkbox]').forEach(function(cb){
    var sync = function(){ cb.closest('.imt-at-toggle').classList.toggle('is-on', cb.checked); };
    cb.addEventListener('change', sync); sync();
  });
  root.querySelectorAll('.imt-at-chip input[type=checkbox]').forEach(function(cb){
    var sync = function(){ cb.closest('.imt-at-chip').classList.toggle('is-on', cb.checked); };
    cb.addEventListener('change', sync); sync();
  });

  /* image dropzones */
  root.querySelectorAll('.imt-at-drop').forEach(function(drop){
    var input = drop.querySelector('input[type=file]');
    var rm = drop.querySelector('.imt-at-drop__rm');
    function show(file){
      if(!file) return;
      var url = URL.createObjectURL(file);
      drop.style.backgroundImage = 'url('+url+')';
      drop.classList.add('is-filled'); recount();
    }
    input.addEventListener('change', function(){ if(input.files && input.files[0]) show(input.files[0]); });
    rm.addEventListener('click', function(e){
      e.preventDefault(); e.stopPropagation();
      input.value=''; drop.style.backgroundImage=''; drop.classList.remove('is-filled'); recount();
    });
    drop.addEventListener('dragover', function(e){ e.preventDefault(); drop.classList.add('is-drag'); });
    drop.addEventListener('dragleave', function(){ drop.classList.remove('is-drag'); });
    drop.addEventListener('drop', function(e){
      e.preventDefault(); drop.classList.remove('is-drag');
      if(e.dataTransfer.files && e.dataTransfer.files[0]){ input.files = e.dataTransfer.files; show(e.dataTransfer.files[0]); }
    });
  });

  /* toast */
  var toast = root.querySelector('#atToast');
  function showToast(msg){
    toast.innerHTML = '<svg class="ico"><use href="#ic-check"/></svg>' + msg;
    toast.classList.add('show'); clearTimeout(toast._t);
    toast._t = setTimeout(function(){ toast.classList.remove('show'); }, 3200);
  }

  /* validate required on submit; show loading state; guard double submit; allow normal POST when valid */
  var submitting = false;
  form.addEventListener('submit', function(e){
    if(submitting){ e.preventDefault(); return; }
    var isDraft = e.submitter && e.submitter.name === 'save_draft'; /* drafts skip validation */
    if(!isDraft){
      var firstBad = null;
      root.querySelectorAll('[data-req]').forEach(function(r){
        var field = r.closest('.imt-at-field');
        var ok = isFilled(r);
        if(field) field.classList.toggle('is-error', !ok);
        if(!ok && !firstBad) firstBad = field;
      });
      if(firstBad){
        e.preventDefault();
        window.scrollTo({ top: firstBad.getBoundingClientRect().top + window.pageYOffset - 90, behavior:'smooth' });
        showToast('Please complete the required fields.');
        return;
      }
    }
    /* valid (or draft): enter submitting state so the row can't be double-posted */
    submitting = true;
    var bar = root.querySelector('.imt-at-actionbar');
    if(bar) bar.classList.add('is-submitting');
    if(e.submitter){ e.submitter.classList.add('is-loading'); e.submitter.setAttribute('aria-busy','true'); }
  });

  recount();
})();
</script>
