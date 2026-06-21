<?php
/**
 * Add Tool listing form — 6-step wizard (plugin partial).
 *
 * Tool analogue of pages/partials/ih-add-machine-form.php — mirrors its
 * architecture, polish and interactions (6-step wizard, live preview,
 * listing-strength meter, sticky actions, grade picker, switch cards,
 * 5-image upload with cover selection, inline validation) for the
 * `ih_tools` data model.
 *
 * Expects (optional):
 *   $ih_at_mode       'user' | 'admin'
 *   $ih_at_user       WP_User
 *   $ih_at_dashboard  breadcrumb dashboard URL
 */
defined( 'ABSPATH' ) || exit;

$ih_at_mode = isset( $ih_at_mode ) ? $ih_at_mode : 'user';
$ih_at_user = isset( $ih_at_user ) && $ih_at_user instanceof WP_User ? $ih_at_user : wp_get_current_user();

$ih_at_is_user = ( $ih_at_mode === 'user' );
$ih_at_nonce_action = $ih_at_is_user ? 'ih_user_add_tool' : 'ih_add_tool';
$ih_at_nonce_name   = $ih_at_is_user ? 'ih_user_nonce' : 'ih_nonce_field';
$ih_at_submit_name  = $ih_at_is_user ? 'ih_user_tool_submit' : 'ih_tool_submit';

$ih_at_dashboard = isset( $ih_at_dashboard ) ? $ih_at_dashboard : (
	$ih_at_is_user
		? admin_url( 'admin.php?page=ih-user-dashboard' )
		: admin_url( 'admin.php?page=ih-dashboard' )
);

$ih_at_company = get_user_meta( $ih_at_user->ID, 'company', true ) ?: ( $ih_at_user->display_name ?: '' );
$ih_at_owner_ref = function_exists( 'ih_user_ref' ) ? ih_user_ref( $ih_at_user->ID ) : 'USR-' . $ih_at_user->ID;
$ih_at_owner_label = $ih_at_company ? $ih_at_company . ' · ' . $ih_at_owner_ref : $ih_at_owner_ref;

$ih_at_now_ts = current_time( 'timestamp' );
$ih_at_today  = current_time( 'Y-m-d' );
$ih_at_expiry = wp_date( 'Y-m-d', strtotime( '+3 months', $ih_at_now_ts ) );

$ih_at_ref_preview = 'TL-#####';
$ih_at_date_label  = wp_date( 'D, j M', $ih_at_now_ts );
$ih_at_time_label  = wp_date( 'g:i A', $ih_at_now_ts );
$ih_at_avatar_initials = 'U';
if ( $ih_at_user->display_name ) {
	$parts = preg_split( '/\s+/', trim( $ih_at_user->display_name ) );
	if ( count( $parts ) >= 2 ) {
		$ih_at_avatar_initials = strtoupper( substr( $parts[0], 0, 1 ) . substr( $parts[1], 0, 1 ) );
	} else {
		$ih_at_avatar_initials = strtoupper( substr( $parts[0], 0, 2 ) );
	}
}

/* Material grades — code + plain-language name + grade hint, rendered as rich
   rows in the §1 grade picker (mirrors the Add Machine materials picker UX). */
$ih_at_grades = array(
	array( 'code' => 'ABS',        'name' => 'Acrylonitrile Butadiene Styrene', 'hint' => 'General purpose · Flame retardant · High impact' ),
	array( 'code' => 'PP',         'name' => 'Polypropylene',                    'hint' => 'Homopolymer · Copolymer · Talc/Glass-filled · Food grade' ),
	array( 'code' => 'PC',         'name' => 'Polycarbonate',                    'hint' => 'Clear · UV stable · Flame retardant' ),
	array( 'code' => 'PC/ABS',     'name' => 'Polycarbonate / ABS blend',        'hint' => 'Automotive grade · Flame retardant' ),
	array( 'code' => 'PA66',       'name' => 'Nylon 66',                         'hint' => 'Natural · Black · Glass-filled · Heat stabilised' ),
	array( 'code' => 'PA6',        'name' => 'Nylon 6',                          'hint' => 'Natural · Black · Glass-filled · Wear resistant' ),
	array( 'code' => 'POM',        'name' => 'Acetal / Delrin',                  'hint' => 'Homopolymer · Copolymer · Low friction' ),
	array( 'code' => 'PBT',        'name' => 'Polybutylene Terephthalate',       'hint' => 'Electrical · Glass-filled · Flame retardant' ),
	array( 'code' => 'PEEK',       'name' => 'Polyether Ether Ketone',           'hint' => 'High performance · High temperature' ),
	array( 'code' => 'HDPE',       'name' => 'High-density Polyethylene',        'hint' => 'Containers · Caps · Chemical resistant' ),
	array( 'code' => 'LDPE',       'name' => 'Low-density Polyethylene',         'hint' => 'Flexible · Tough · Low-temperature impact' ),
	array( 'code' => 'PET',        'name' => 'Polyethylene Terephthalate',       'hint' => 'Dimensional stability · Wear resistant' ),
	array( 'code' => 'PVC',        'name' => 'Polyvinyl Chloride',               'hint' => 'Rigid · Flexible · Chemical resistant' ),
	array( 'code' => 'TPU',        'name' => 'Thermoplastic Polyurethane',       'hint' => 'Elastic · Abrasion resistant · Soft-touch' ),
	array( 'code' => 'TPE',        'name' => 'Thermoplastic Elastomer',          'hint' => 'Flexible · Seals · Soft-touch grips' ),
	array( 'code' => 'PMMA',       'name' => 'Acrylic / PMMA',                   'hint' => 'Optical clarity · Weather resistant' ),
	array( 'code' => 'ASA',        'name' => 'Acrylonitrile Styrene Acrylate',   'hint' => 'UV stable · Outdoor housings' ),
	array( 'code' => 'PS',         'name' => 'Polystyrene',                      'hint' => 'Rigid · Commodity · Easy to mould' ),
	array( 'code' => 'SAN',        'name' => 'Styrene Acrylonitrile',            'hint' => 'Clear · Chemical resistant · Rigid' ),
	array( 'code' => 'PEI',        'name' => 'Polyetherimide',                   'hint' => 'High heat · Flame resistant · Electrical' ),
	array( 'code' => 'PPS',        'name' => 'Polyphenylene Sulfide',            'hint' => 'Chemical resistant · High temperature' ),
);

/* Capabilities (§4 switch cards) — stored Yes/No on their own columns. */
$ih_at_capabilities = array(
	array( 'name' => 'water_cooled',  'label' => 'Water-Cooled Chiller', 'hint' => 'Chiller available on site' ),
	array( 'name' => 'suck_pump',     'label' => 'Suck Pump',            'hint' => 'Vacuum / suck-back fitted' ),
	array( 'name' => 'food_grade',    'label' => 'Food Grade',           'hint' => 'Food-contact compliant' ),
	array( 'name' => 'medical_grade', 'label' => 'Medical Grade',        'hint' => 'Cleanroom / medical capable' ),
	array( 'name' => 'iml',           'label' => 'IML',                  'hint' => 'In-mould labelling ready' ),
	array( 'name' => 'automation',    'label' => 'Automation',           'hint' => 'Robot / pick-and-place fitted' ),
);
?>
<div class="ih-at" id="ihAddToolRoot">

<header class="ih-at-mhead ih-at-mobile-only" aria-label="Add a tool">
	<a href="<?php echo esc_url( $ih_at_dashboard ); ?>" class="ih-at-mhead__back" aria-label="Back to dashboard">
		<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
	</a>
	<div class="ih-at-mhead__copy">
		<h1 class="ih-at-mhead__title">Add a Tool</h1>
		<p class="ih-at-mhead__ref"><span data-preview-ref><?php echo esc_html( $ih_at_ref_preview ); ?></span></p>
	</div>
	<div class="ih-at-mhead__datetime" aria-label="Current date and time">
		<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
		<span class="ih-at-mhead__datetime-text">
			<strong><?php echo esc_html( $ih_at_date_label ); ?></strong>
			<small><?php echo esc_html( $ih_at_time_label ); ?></small>
		</span>
	</div>
	<div class="ih-at-mhead__avatar" aria-hidden="true"><?php echo esc_html( $ih_at_avatar_initials ); ?></div>
</header>

<div class="ih-at-mprogress ih-at-mobile-only" id="ihAtMobileProgress">
	<div class="ih-at-mprogress__row">
		<p class="ih-at-mprogress__label" id="ihAtMobileStepLabel">Step 1 of 6 — Part info</p>
		<span class="ih-at-mprogress__pct" id="ihAtMobileProgressPct">0%</span>
	</div>
	<div class="ih-at-mprogress__track" aria-hidden="true">
		<div class="ih-at-mprogress__fill" id="ihAtMobileProgressFill"></div>
	</div>
</div>

<header class="ih-at-pagehead ih-at-desktop-only">
	<nav class="ih-at-crumb" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( $ih_at_dashboard ); ?>">Dashboard</a>
		<span class="ih-at-crumb__sep" aria-hidden="true">/</span>
		<span class="ih-at-crumb__cur">New listing</span>
	</nav>
	<div class="ih-at-pagehead__row">
		<div class="ih-at-pagehead__copy">
			<h1 class="ih-at-title">Add a Tool listing</h1>
			<p class="ih-at-subtitle">List a mould or tool for approval — users see an anonymised card (TL-#####).</p>
		</div>
		<div class="ih-at-pagehead__actions">
			<button type="submit" form="ihAddToolForm" name="save_draft" value="1" class="ih-at-btn ih-at-btn--ghost">Save draft</button>
			<button type="submit" form="ihAddToolForm" class="ih-at-btn ih-at-btn--primary">Publish listing</button>
		</div>
	</div>
</header>

<nav class="ih-at-stepper ih-at-desktop-only" aria-label="Form steps">
	<?php
	$ih_at_steps = array(
		array( 'id' => 'ih-at-sec-part', 'label' => 'Part info', 'n' => 1 ),
		array( 'id' => 'ih-at-sec-mould', 'label' => 'Mould specs', 'n' => 2 ),
		array( 'id' => 'ih-at-sec-prod', 'label' => 'Production', 'n' => 3 ),
		array( 'id' => 'ih-at-sec-feat', 'label' => 'Features', 'n' => 4 ),
		array( 'id' => 'ih-at-sec-listing', 'label' => 'Listing', 'n' => 5 ),
		array( 'id' => 'ih-at-sec-images', 'label' => 'Images', 'n' => 6 ),
	);
	foreach ( $ih_at_steps as $i => $step ) :
		$active = $i === 0 ? ' is-active' : '';
		?>
	<button type="button" class="ih-at-step<?php echo esc_attr( $active ); ?>" data-target="<?php echo esc_attr( $step['id'] ); ?>">
		<span class="ih-at-step__num"><?php echo (int) $step['n']; ?></span>
		<span class="ih-at-step__label"><?php echo esc_html( $step['label'] ); ?></span>
	</button>
	<?php endforeach; ?>
</nav>

<div class="ih-at-layout">
	<div class="ih-at-form-col">
		<form method="POST" enctype="multipart/form-data" id="ihAddToolForm" class="ih-at-form" novalidate data-ih-nonce-refresh data-ih-nonce-action="<?php echo esc_attr( $ih_at_nonce_action ); ?>" data-ih-nonce-field="<?php echo esc_attr( $ih_at_nonce_name ); ?>">
			<?php wp_nonce_field( $ih_at_nonce_action, $ih_at_nonce_name ); ?>
			<input type="hidden" name="<?php echo esc_attr( $ih_at_submit_name ); ?>" value="1">
			<div class="ih-at-toast" id="ihAtFormToast" role="status" aria-live="polite" hidden></div>

			<div class="ih-at-guide ih-at-guide--mobile ih-at-mobile-only">
				<button type="button" class="ih-at-guide__toggle" id="ihAtGuideToggleMobile" aria-expanded="false" aria-controls="ihAtGuidePanelMobile" data-guide-toggle>
					<span>Tool / mould guide</span>
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
				</button>
				<div class="ih-at-guide__panel" id="ihAtGuidePanelMobile" hidden>
					<ul class="ih-at-guide__list">
						<li><strong>Part data:</strong> grade, dimensions, weight and cavities let buyers screen the mould fast.</li>
						<li><strong>Mould specs:</strong> type, runner, gate and clamp drive decide press compatibility.</li>
						<li><strong>Photos:</strong> add the cavity, core, cooling layout and a clean wide cover shot.</li>
					</ul>
				</div>
			</div>

			<!-- §1 Part Information -->
			<section id="ih-at-sec-part" class="ih-at-section ih-at-accordion is-open" data-mobile-step="1" data-accordion-stat="">
				<button type="button" class="ih-at-section__head ih-at-accordion__head" data-accordion-toggle>
					<span class="ih-at-accordion__icon ih-at-accordion__icon--accent" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
					</span>
					<div class="ih-at-accordion__meta">
						<h2 class="ih-at-section__title">Part Information</h2>
						<p class="ih-at-accordion__sub">The basics buyers search by.</p>
						<p class="ih-at-accordion__stat ih-at-mobile-only" data-accordion-stat-label hidden></p>
					</div>
					<span class="ih-at-accordion__check ih-at-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-at-accordion__chev ih-at-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
					</span>
				</button>
				<div class="ih-at-accordion__body">
				<div class="ih-at-fields ih-at-fields--3">
					<div class="ih-at-field ih-at-field--full ih-at-field--vital">
						<label class="ih-at-label" for="ih_at_title">Tool title <span class="ih-at-star" aria-hidden="true">★</span></label>
						<input type="text" id="ih_at_title" name="title" class="ih-at-input" data-strength data-vital data-preview="title" placeholder="e.g. 4-Cavity ABS Housing Mould" aria-describedby="ih_at_title_error" required>
						<small class="ih-at-field-error" id="ih_at_title_error" hidden>Enter a tool title.</small>
					</div>
					<div class="ih-at-field ih-at-field--full ih-at-field--vital">
						<label class="ih-at-label" for="ih_at_material_grade">Material grade <span class="ih-at-star" aria-hidden="true">★</span></label>
						<div class="ih-at-grade" id="ihAtGradePicker" data-grades="<?php echo esc_attr( wp_json_encode( $ih_at_grades ) ); ?>">
							<div class="ih-at-grade__input-wrap">
								<input type="text" id="ih_at_material_grade" name="material_grade" class="ih-at-input ih-at-grade__search" data-strength data-vital placeholder="Search grade — ABS, PA66, PEEK…" autocomplete="off" aria-describedby="ih_at_material_grade_error">
								<button type="button" class="ih-at-grade__browse" id="ihAtGradeBrowse" aria-expanded="false">Browse grades</button>
							</div>
							<div class="ih-at-grade__dropdown" id="ihAtGradeDropdown" hidden>
								<div class="ih-at-grade__list" id="ihAtGradeList"></div>
								<p class="ih-at-grade__more">+ <?php echo max( 0, count( $ih_at_grades ) - 8 ); ?> more · or type a custom value</p>
								<button type="button" class="ih-at-grade__add" id="ihAtGradeAdd" disabled>Add selected (0)</button>
							</div>
							<div class="ih-at-grade__chips" id="ihAtGradeChips" aria-live="polite"></div>
							<div class="ih-at-grade__materials" id="ihAtGradeMaterials" hidden></div>
						</div>
						<small class="ih-at-field-error" id="ih_at_material_grade_error" hidden>Add at least one material grade.</small>
					</div>
					<div class="ih-at-field ih-at-field--full">
						<label class="ih-at-label" for="ih_at_part_dimensions">Part dimensions (L × W × H mm)</label>
						<input type="text" id="ih_at_part_dimensions" name="part_dimensions" class="ih-at-input" data-strength placeholder="300 × 200 × 150" inputmode="text">
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_part_weight">Part weight (g)</label>
						<input type="number" id="ih_at_part_weight" name="part_weight" class="ih-at-input" data-strength placeholder="28" min="0" step="0.01" inputmode="decimal">
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_num_cavities">Number of cavities</label>
						<input type="number" id="ih_at_num_cavities" name="num_cavities" class="ih-at-input" data-strength placeholder="4" min="1" step="1" inputmode="numeric">
					</div>
					<div class="ih-at-field ih-at-field--sm">
						<label class="ih-at-label" for="ih_at_colour">Colour (RAL code)</label>
						<input type="text" id="ih_at_colour" name="colour" class="ih-at-input" data-strength placeholder="RAL 9003" maxlength="40">
					</div>
					<div class="ih-at-field ih-at-field--md">
						<span class="ih-at-grouplbl">Tolerance</span>
						<div class="ih-at-pills ih-at-pills--radio">
							<label class="ih-at-pill"><input type="radio" name="tolerance" value="± 0.1 mm" data-strength> ± 0.1 mm</label>
							<label class="ih-at-pill"><input type="radio" name="tolerance" value="± 0.2 mm" data-strength> ± 0.2 mm</label>
							<label class="ih-at-pill"><input type="radio" name="tolerance" value="± 0.5 mm" data-strength> ± 0.5 mm</label>
						</div>
					</div>
					<div class="ih-at-field ih-at-field--md ih-at-field--vital">
						<label class="ih-at-label" for="ih_at_location">Location <span class="ih-at-star" aria-hidden="true">★</span></label>
						<input type="text" id="ih_at_location" name="location" class="ih-at-input" data-strength data-vital data-preview="location" placeholder="e.g. Manchester, UK" aria-describedby="ih_at_location_error" required>
						<small class="ih-at-field-error" id="ih_at_location_error" hidden>Enter the tool location.</small>
					</div>
					<div class="ih-at-field ih-at-field--md ih-at-field--desktop-only">
						<label class="ih-at-label" for="ih_at_owner">Owner (from profile)</label>
						<input type="text" id="ih_at_owner" name="owner_name" class="ih-at-input ih-at-input--readonly" value="<?php echo esc_attr( $ih_at_owner_label ); ?>" readonly aria-describedby="ih_at_owner_help">
						<small class="ih-at-help" id="ih_at_owner_help">Anonymised — buyers only see your TL-ID until you approve a request.</small>
					</div>
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_surface_finish">Surface finish</label>
						<select id="ih_at_surface_finish" name="surface_finish" class="ih-at-input" data-strength>
							<option value="">Select finish…</option>
							<option value="Textured">Textured</option>
							<option value="Polished">Polished</option>
							<option value="Matte">Matte</option>
							<option value="Gloss">Gloss</option>
							<option value="As machined">As machined</option>
						</select>
					</div>
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_part_name">Part name</label>
						<input type="text" id="ih_at_part_name" name="part_name" class="ih-at-input" data-strength placeholder="e.g. Diagnostic housing">
					</div>
					<div class="ih-at-field ih-at-field--full">
						<label class="ih-at-label" for="ih_at_part_description">Part function / description</label>
						<textarea id="ih_at_part_description" name="part_description" class="ih-at-input ih-at-textarea" data-strength rows="4" placeholder="Describe the part, end market and critical features — cooling zones, manifolds, ejector layout…"></textarea>
					</div>
				</div>
				</div>
			</section>

			<!-- §2 Mould Specifications -->
			<section id="ih-at-sec-mould" class="ih-at-section ih-at-accordion" data-mobile-step="2" data-accordion-stat="{filled} of {total}">
				<button type="button" class="ih-at-section__head ih-at-accordion__head" data-accordion-toggle>
					<span class="ih-at-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
					</span>
					<div class="ih-at-accordion__meta">
						<h2 class="ih-at-section__title">Mould Specifications</h2>
						<p class="ih-at-accordion__sub">Type, runner, clamp and condition.</p>
						<p class="ih-at-accordion__stat ih-at-mobile-only" data-accordion-stat-label hidden></p>
					</div>
					<span class="ih-at-accordion__check ih-at-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-at-accordion__chev ih-at-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
					</span>
				</button>
				<div class="ih-at-accordion__body">
				<div class="ih-at-fields ih-at-fields--3">
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_mould_weight">Mould weight (kg)</label>
						<input type="number" id="ih_at_mould_weight" name="mould_weight" class="ih-at-input" data-strength placeholder="850" min="0" step="0.1" inputmode="decimal">
					</div>
					<div class="ih-at-field ih-at-field--full">
						<label class="ih-at-label" for="ih_at_mould_dimensions">Mould dimensions (L × W × H mm)</label>
						<input type="text" id="ih_at_mould_dimensions" name="mould_dimensions" class="ih-at-input" data-strength placeholder="600 × 500 × 450" inputmode="text">
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_num_cavities_spec">Number of cavities</label>
						<input type="number" id="ih_at_num_cavities_spec" name="num_cavities_spec" class="ih-at-input" data-strength data-preview="utilization" min="1" step="1" placeholder="4" inputmode="numeric">
					</div>
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_mould_type">Mould type</label>
						<select id="ih_at_mould_type" name="mould_type" class="ih-at-input" data-strength data-preview="type">
							<option value="">Select type…</option>
							<option value="Single-Cavity Mould">Single-Cavity Mould</option>
							<option value="Multi-Cavity Mould">Multi-Cavity Mould</option>
							<option value="Family Mould">Family Mould</option>
							<option value="Insert Mould">Insert Mould</option>
							<option value="Stack Mould">Stack Mould</option>
							<option value="Overmould">Overmould</option>
							<option value="2K / Multi-shot">2K / Multi-shot</option>
						</select>
					</div>
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_runner_type">Runner type</label>
						<select id="ih_at_runner_type" name="runner_type" class="ih-at-input" data-strength>
							<option value="">Select runner…</option>
							<option value="Hot Runner">Hot Runner</option>
							<option value="Cold Runner">Cold Runner</option>
							<option value="Insulated Runner">Insulated Runner</option>
							<option value="Semi-Hot Runner">Semi-Hot Runner</option>
						</select>
					</div>
					<div class="ih-at-field ih-at-field--md ih-at-field--full-mobile">
						<label class="ih-at-label" for="ih_at_clamp_drive_type">Clamp &amp; drive type</label>
						<select id="ih_at_clamp_drive_type" name="clamp_drive_type" class="ih-at-input" data-strength aria-describedby="ih_at_clamp_drive_help">
							<option value="">Select drive…</option>
							<option value="Hydraulic">Hydraulic</option>
							<option value="Toggle">Toggle</option>
							<option value="Hybrid">Hybrid</option>
							<option value="All-electric">All-electric</option>
							<option value="Two-platen">Two-platen</option>
						</select>
						<small class="ih-at-help" id="ih_at_clamp_drive_help">The press drive the mould was designed for — helps buyers match energy use and repeatability.</small>
					</div>
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_ejector_type">Ejection type</label>
						<select id="ih_at_ejector_type" name="ejector_type" class="ih-at-input" data-strength>
							<option value="">Select ejection…</option>
							<option value="Ejector Pin">Ejector Pin</option>
							<option value="Ejector Plate">Ejector Plate</option>
							<option value="Stripper Plate">Stripper Plate</option>
							<option value="Air Eject">Air Eject</option>
							<option value="Hydraulic Ejector">Hydraulic Ejector</option>
						</select>
					</div>
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_gate_type">Gate type</label>
						<select id="ih_at_gate_type" name="gate_type" class="ih-at-input" data-strength>
							<option value="">Select gate…</option>
							<option value="Submarine / Tunnel Gate">Submarine / Tunnel Gate</option>
							<option value="Edge Gate">Edge Gate</option>
							<option value="Direct Sprue">Direct Sprue</option>
							<option value="Hot Tip">Hot Tip</option>
							<option value="Valve Gate">Valve Gate</option>
							<option value="Pin Gate">Pin Gate</option>
						</select>
					</div>
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_mould_condition">Tool condition</label>
						<select id="ih_at_mould_condition" name="mould_condition" class="ih-at-input" data-strength>
							<option value="">Select condition…</option>
							<option value="New">New</option>
							<option value="Used — Production Ready">Used — Production Ready</option>
							<option value="Used — Refurbishment Needed">Used — Refurbishment Needed</option>
							<option value="For Parts / Scrap">For Parts / Scrap</option>
						</select>
					</div>
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_nozzle_type">Nozzle type</label>
						<select id="ih_at_nozzle_type" name="nozzle_type" class="ih-at-input" data-strength>
							<option value="">Select nozzle…</option>
							<option value="Hot Runner Nozzle">Hot Runner Nozzle</option>
							<option value="Open Nozzle">Open Nozzle</option>
							<option value="Shut-Off Nozzle">Shut-Off Nozzle</option>
							<option value="Valve Gate Nozzle">Valve Gate Nozzle</option>
						</select>
					</div>
					<div class="ih-at-field ih-at-field--md ih-at-field--full-mobile">
						<label class="ih-at-label" for="ih_at_toggle_clamp_type">Toggle clamp type</label>
						<select id="ih_at_toggle_clamp_type" name="toggle_clamp_type" class="ih-at-input" data-strength aria-describedby="ih_at_toggle_clamp_help">
							<option value="">Select clamp…</option>
							<option value="Single toggle">Single toggle</option>
							<option value="Double 4-point">Double 4-point</option>
							<option value="Double 5-point">Double 5-point</option>
							<option value="Not applicable">Not applicable</option>
						</select>
						<small class="ih-at-help" id="ih_at_toggle_clamp_help">5-point toggles give higher clamp multiplication; use "Not applicable" for direct-hydraulic presses.</small>
					</div>
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_mould_material">Mould material</label>
						<input type="text" id="ih_at_mould_material" name="mould_material" class="ih-at-input" data-strength placeholder="e.g. P20 / H13 Steel">
					</div>
					<div class="ih-at-field ih-at-field--md ih-at-field--full-mobile">
						<label class="ih-at-label" for="ih_at_injection_stages">Injection stages</label>
						<input type="text" id="ih_at_injection_stages" name="injection_stages" class="ih-at-input" data-strength placeholder="e.g. 1st Fill → V-P switchover → 2nd Pack &amp; hold" aria-describedby="ih_at_injection_stages_help">
						<small class="ih-at-help" id="ih_at_injection_stages_help">Describe the injection profile: 1st Fill / V-P switchover / 2nd Pack &amp; hold.</small>
					</div>
					<div class="ih-at-field ih-at-field--md ih-at-field--full-mobile">
						<label class="ih-at-label" for="ih_at_compatible_specs">Compatible press specs</label>
						<input type="text" id="ih_at_compatible_specs" name="compatible_specs" class="ih-at-input" data-strength placeholder="e.g. ≥120T press, 40mm+ screw">
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_proj_area">Projected area (cm²)</label>
						<input type="number" id="ih_at_proj_area" name="projected_area" class="ih-at-input" data-strength placeholder="e.g. 180" min="0" step="0.1" inputmode="decimal" aria-describedby="ih_at_proj_area_help">
						<small class="ih-at-help" id="ih_at_proj_area_help">Total projected area of all cavities. With cavity pressure this gives a precise required clamp tonnage.</small>
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_cav_pressure">Cavity pressure (bar)</label>
						<input type="number" id="ih_at_cav_pressure" name="cavity_pressure" class="ih-at-input" data-strength placeholder="e.g. 350" min="0" step="1" inputmode="numeric" aria-describedby="ih_at_cav_pressure_help">
						<small class="ih-at-help" id="ih_at_cav_pressure_help">In-cavity melt pressure (bar). Pairs with projected area to estimate required tonnage.</small>
					</div>
					<div class="ih-at-field ih-at-field--md ih-at-field--desktop-only">
						<label class="ih-at-label" for="ih_at_mould_location">Mould location</label>
						<input type="text" id="ih_at_mould_location" name="mould_location" class="ih-at-input" data-strength placeholder="Warehouse 3, Manchester">
					</div>
				</div>
				</div>
			</section>

			<!-- §3 Production -->
			<section id="ih-at-sec-prod" class="ih-at-section ih-at-accordion" data-mobile-step="3" data-accordion-stat="{filled} of {total}">
				<button type="button" class="ih-at-section__head ih-at-accordion__head" data-accordion-toggle>
					<span class="ih-at-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
					</span>
					<div class="ih-at-accordion__meta">
						<h2 class="ih-at-section__title">Production Information</h2>
						<p class="ih-at-accordion__sub">Volumes &amp; commercial terms.</p>
						<p class="ih-at-accordion__stat ih-at-mobile-only" data-accordion-stat-label hidden></p>
					</div>
					<span class="ih-at-accordion__check ih-at-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-at-accordion__chev ih-at-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
					</span>
				</button>
				<div class="ih-at-accordion__body">
				<div class="ih-at-fields ih-at-fields--3">
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_required_qty">Required production quantity</label>
						<input type="text" id="ih_at_required_qty" name="required_qty" class="ih-at-input" data-strength placeholder="100,000 / yr" inputmode="text">
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_annual_volume">Estimated annual volume</label>
						<input type="number" id="ih_at_annual_volume" name="annual_volume" class="ih-at-input" data-strength placeholder="250000" min="0" step="1" inputmode="numeric">
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_cycle_time">Target cycle time (s)</label>
						<input type="number" id="ih_at_cycle_time" name="cycle_time" class="ih-at-input" data-strength placeholder="28" min="0" step="0.1" inputmode="decimal">
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_min_order_qty">Min order qty</label>
						<input type="number" id="ih_at_min_order_qty" name="min_order_qty" class="ih-at-input" data-strength placeholder="5000" min="0" step="1" inputmode="numeric">
					</div>
					<div class="ih-at-field ih-at-field--sm">
						<label class="ih-at-label" for="ih_at_draft_angle">Draft angle</label>
						<input type="text" id="ih_at_draft_angle" name="draft_angle" class="ih-at-input" data-strength placeholder="1.5°">
					</div>
					<div class="ih-at-field ih-at-field--full">
						<label class="ih-at-label" for="ih_at_packaging">Packaging requirements</label>
						<input type="text" id="ih_at_packaging" name="packaging" class="ih-at-input" data-strength placeholder="Bagged, 500/carton">
					</div>
					<div class="ih-at-toggle-grid">
						<div class="ih-at-field ih-at-field--toggle">
							<label class="ih-at-toggle-row">
								<span class="ih-at-toggle-row__copy">
									<span class="ih-at-toggle-row__label">Material supplied by customer?</span>
									<span class="ih-at-toggle-row__hint">Customer supplies the resin</span>
								</span>
								<input type="checkbox" name="material_supplied" value="Yes — supplied" class="ih-at-toggle-cb" data-strength>
								<span class="ih-at-switch" aria-hidden="true"></span>
							</label>
						</div>
					</div>
				</div>
				</div>
			</section>

			<!-- §4 Tool Features & Requirements -->
			<section id="ih-at-sec-feat" class="ih-at-section ih-at-accordion" data-mobile-step="4" data-accordion-stat="{filled} of {total}">
				<button type="button" class="ih-at-section__head ih-at-accordion__head" data-accordion-toggle>
					<span class="ih-at-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
					</span>
					<div class="ih-at-accordion__meta">
						<h2 class="ih-at-section__title">Tool Features &amp; Requirements</h2>
						<p class="ih-at-accordion__sub">Clamp force &amp; capabilities.</p>
						<p class="ih-at-accordion__stat ih-at-mobile-only" data-accordion-stat-label hidden></p>
					</div>
					<span class="ih-at-accordion__check ih-at-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-at-accordion__chev ih-at-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
					</span>
				</button>
				<div class="ih-at-accordion__body">
				<div class="ih-at-fields ih-at-fields--3">
					<div class="ih-at-field ih-at-field--md ih-at-field--num">
						<label class="ih-at-label" for="ih_at_clamp_force">Required clamp force / tonnage (T)</label>
						<input type="number" id="ih_at_clamp_force" name="clamp_force" class="ih-at-input" data-strength placeholder="120" min="0" step="1" inputmode="numeric">
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_shot_weight">Shot weight (g)</label>
						<input type="number" id="ih_at_shot_weight" name="shot_weight" class="ih-at-input" data-strength placeholder="210" min="0" step="0.01" inputmode="decimal">
					</div>
					<div class="ih-at-field ih-at-field--md">
						<label class="ih-at-label" for="ih_at_tie_bar">Tie-bar spacing (L × W mm)</label>
						<input type="text" id="ih_at_tie_bar" name="tie_bar" class="ih-at-input" data-strength placeholder="460 × 460" inputmode="text">
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_opening_stroke">Opening stroke / daylight (mm)</label>
						<input type="number" id="ih_at_opening_stroke" name="opening_stroke" class="ih-at-input" data-strength placeholder="420" min="0" step="1" inputmode="numeric">
					</div>
					<div class="ih-at-field ih-at-field--sm ih-at-field--num">
						<label class="ih-at-label" for="ih_at_hot_runner_zones">Hot runner zones</label>
						<input type="number" id="ih_at_hot_runner_zones" name="hot_runner_zones" class="ih-at-input" data-strength min="0" step="1" placeholder="8" inputmode="numeric">
					</div>
					<div class="ih-at-toggle-grid">
						<div class="ih-at-field ih-at-field--toggle">
							<label class="ih-at-toggle-row">
								<span class="ih-at-toggle-row__copy">
									<span class="ih-at-toggle-row__label">Hot runner controller required?</span>
									<span class="ih-at-toggle-row__hint">Controller not included</span>
								</span>
								<input type="checkbox" name="hot_runner_controller" value="Required (not included)" class="ih-at-toggle-cb" data-strength>
								<span class="ih-at-switch" aria-hidden="true"></span>
							</label>
						</div>
					</div>
					<div class="ih-at-minihead">
						<span class="ih-at-minihead__icon" aria-hidden="true">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 17l6-6 4 4 6-8"/><path d="M14 7h6v6"/></svg>
						</span>
						<div>
							<h3>Capabilities</h3>
							<p>Extra equipment or compliance this tool supports.</p>
						</div>
					</div>
					<div class="ih-at-field ih-at-field--full">
						<div class="ih-at-checkgrid ih-at-checkgrid--switches">
							<?php foreach ( $ih_at_capabilities as $cap ) : ?>
							<label class="ih-at-check ih-at-switch-card">
								<input type="hidden" name="<?php echo esc_attr( $cap['name'] ); ?>" value="No">
								<input type="checkbox" name="<?php echo esc_attr( $cap['name'] ); ?>" value="Yes" class="ih-at-switch-card__input" data-strength>
								<span class="ih-at-switch-card__copy">
									<span class="ih-at-switch-card__label"><?php echo esc_html( $cap['label'] ); ?></span>
									<span class="ih-at-switch-card__hint"><?php echo esc_html( $cap['hint'] ); ?></span>
								</span>
								<span class="ih-at-card-switch" aria-hidden="true">
									<span class="ih-at-card-switch__off">
										<svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 5h6"/></svg>
									</span>
									<span class="ih-at-card-switch__knob">
										<svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.5 6.2 5 8.7 9.5 3.5"/></svg>
									</span>
								</span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				</div>
			</section>

			<!-- §5 Listing & Availability -->
			<section id="ih-at-sec-listing" class="ih-at-section ih-at-accordion" data-mobile-step="5" data-accordion-stat="">
				<button type="button" class="ih-at-section__head ih-at-accordion__head" data-accordion-toggle>
					<span class="ih-at-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
					</span>
					<div class="ih-at-accordion__meta">
						<h2 class="ih-at-section__title">Listing &amp; Availability</h2>
						<p class="ih-at-accordion__sub">When this listing is live and visible.</p>
					</div>
					<span class="ih-at-accordion__check ih-at-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-at-accordion__chev ih-at-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
					</span>
				</button>
				<div class="ih-at-accordion__body">
				<div class="ih-at-fields">
					<div class="ih-at-field-pair ih-at-date-pair">
						<div class="ih-at-field ih-at-field--md">
							<label class="ih-at-label" for="ih_at_listing_date">Start date</label>
							<input type="date" id="ih_at_listing_date" name="listing_date" class="ih-at-input" data-strength value="<?php echo esc_attr( $ih_at_today ); ?>" aria-describedby="ih_at_listing_date_help">
							<small class="ih-at-help" id="ih_at_listing_date_help">Defaults to today for new listings.</small>
						</div>
						<div class="ih-at-field ih-at-field--md">
							<label class="ih-at-label" for="ih_at_expiry_date">Expiry date</label>
							<input type="date" id="ih_at_expiry_date" name="expiry_date" class="ih-at-input" data-strength value="<?php echo esc_attr( $ih_at_expiry ); ?>" min="<?php echo esc_attr( $ih_at_today ); ?>" aria-describedby="ih_at_expiry_date_help ih_at_expiry_date_warning ih_at_expiry_date_soon ih_at_expiry_date_error">
							<small class="ih-at-help" id="ih_at_expiry_date_help">Defaults to 90 days from today; extend it before the listing expires.</small>
							<p class="ih-at-warning" id="ih_at_expiry_date_warning">This listing stops showing publicly after this date.</p>
							<p class="ih-at-warning ih-at-warning--soon" id="ih_at_expiry_date_soon" role="status" aria-live="polite" hidden>Expiring soon — this listing runs for under two weeks. Extend the date to keep it visible longer.</p>
							<small class="ih-at-field-error" id="ih_at_expiry_date_error" hidden>Choose an expiry date today or later, after the start date.</small>
						</div>
					</div>
				</div>
				</div>
			</section>

			<!-- §6 Images -->
			<section id="ih-at-sec-images" class="ih-at-section ih-at-accordion" data-mobile-step="6" data-accordion-stat="">
				<button type="button" class="ih-at-section__head ih-at-accordion__head" data-accordion-toggle>
					<span class="ih-at-accordion__icon ih-at-accordion__icon--accent" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
					</span>
					<div class="ih-at-accordion__meta">
						<h2 class="ih-at-section__title">Upload Images</h2>
						<p class="ih-at-accordion__sub">Show the mould clearly — up to 5 photos.</p>
					</div>
					<span class="ih-at-accordion__check ih-at-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-at-accordion__chev ih-at-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
					</span>
				</button>
				<div class="ih-at-accordion__body">
				<div class="ih-at-upload-cover">
					<label class="ih-at-drop ih-at-drop--cover" data-upload-slot="1">
						<input type="file" name="image_1" accept="image/png,image/jpeg,image/jpg,image/webp" hidden data-strength-image>
						<span class="ih-at-drop__inner">
							<span class="ih-at-drop__icon ih-at-desktop-only" aria-hidden="true">📷</span>
							<span class="ih-at-drop__icon ih-at-drop__icon--svg ih-at-mobile-only" aria-hidden="true">
								<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
							</span>
							<strong class="ih-at-desktop-only">Drop your cover image or click to upload</strong>
							<strong class="ih-at-mobile-only">Tap to upload cover</strong>
							<small>PNG, JPG up to 8MB<span class="ih-at-desktop-only"> · recommended 1200×800</span></small>
						</span>
						<span class="ih-at-drop__cover-badge">Cover image</span>
						<button type="button" class="ih-at-drop__remove" hidden aria-label="Remove image">&times;</button>
					</label>
				</div>
				<p class="ih-at-upload-hint" id="ih_at_images_help">Up to 5 photos. Drag, preview, remove and reorder before submitting; Image 1 is stored as the cover.</p>
				<small class="ih-at-field-error" id="ih_at_images_error" hidden>Add at least one image before submitting.</small>
				<div class="ih-at-upload-row">
					<?php for ( $img_i = 2; $img_i <= 5; $img_i++ ) : ?>
					<label class="ih-at-drop ih-at-drop--thumb" data-upload-slot="<?php echo (int) $img_i; ?>">
						<input type="file" name="image_<?php echo (int) $img_i; ?>" accept="image/png,image/jpeg,image/jpg,image/webp" hidden<?php echo $img_i <= 3 ? ' data-strength-image' : ' data-strength-image-optional'; ?>>
						<span class="ih-at-drop__inner">
							<span class="ih-at-drop__plus">+</span>
							<small>Photo <?php echo (int) $img_i; ?></small>
						</span>
						<button type="button" class="ih-at-drop__cover" hidden>Make cover</button>
						<button type="button" class="ih-at-drop__remove" hidden aria-label="Remove image">&times;</button>
					</label>
					<?php endfor; ?>
				</div>
				</div>
			</section>

			<div class="ih-at-mbanner ih-at-mobile-only" role="note">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
				<div class="ih-at-mbanner__copy">
					<strong>Anonymised by default</strong>
					<p>Buyers see TL-IDs only until you approve a request.</p>
				</div>
			</div>

			<footer class="ih-at-footer ih-at-desktop-only">
				<p class="ih-at-footer__note">Goes live once an admin approves.</p>
				<div class="ih-at-footer__actions">
					<button type="submit" name="save_draft" value="1" class="ih-at-btn ih-at-btn--ghost">Save as draft</button>
					<button type="submit" class="ih-at-btn ih-at-btn--primary">Submit for approval</button>
				</div>
			</footer>
		</form>
	</div>

	<aside class="ih-at-sidebar ih-at-desktop-only" aria-label="Listing strength">
		<div class="ih-at-strength" id="ihAtStrength">
			<div class="ih-at-strength__head">
				<div class="ih-at-strength__ring">
					<svg viewBox="0 0 44 44" aria-hidden="true"><circle class="ih-at-strength__bg" cx="22" cy="22" r="18"/><circle class="ih-at-strength__fg" cx="22" cy="22" r="18"/></svg>
					<span class="ih-at-strength__pct" id="ihAtStrengthPct">0%</span>
				</div>
				<div class="ih-at-strength__copy">
					<span class="ih-at-rail-eyebrow">Listing strength</span>
					<p class="ih-at-strength__label" id="ihAtStrengthLabel">Getting started</p>
					<small>Add part &amp; mould specs to finish.</small>
				</div>
			</div>
			<ul class="ih-at-strength__tips">
				<li id="ihAtStrengthPart"><span class="ih-at-strength__dot" aria-hidden="true"></span>Part info <span class="ih-at-star" aria-hidden="true">★</span></li>
				<li id="ihAtStrengthMould"><span class="ih-at-strength__dot" aria-hidden="true"></span>Mould specifications</li>
				<li id="ihAtStrengthProduction"><span class="ih-at-strength__dot" aria-hidden="true"></span>Production information</li>
				<li id="ihAtStrengthFeatures"><span class="ih-at-strength__dot" aria-hidden="true"></span>Features &amp; materials</li>
				<li id="ihAtStrengthImgTip"><span class="ih-at-strength__dot" aria-hidden="true"></span>Cover image</li>
			</ul>
			<p class="ih-at-polychip is-empty" id="ihAtPolyChip" aria-live="polite">
				<span class="ih-at-polychip__dot" aria-hidden="true"></span>
				<span class="ih-at-polychip__text" id="ihAtPolyChipText">No polymers selected</span>
			</p>
			<p class="ih-at-strength__live" id="ihAtStrengthLive" aria-live="polite">0% complete. Add required fields and a cover image.</p>
		</div>
		<div class="ih-at-preview" id="ihAtLivePreview">
			<div class="ih-at-preview__label">Live preview <span class="ih-at-preview__tag">Buyer view</span></div>
			<article class="ih-at-preview__card">
				<div class="ih-at-preview__hero" aria-hidden="true">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
				</div>
				<div class="ih-at-preview__body">
					<div class="ih-at-preview__top">
						<span class="ih-at-preview__ref" data-preview-ref><?php echo esc_html( $ih_at_ref_preview ); ?></span>
						<span class="ih-at-preview__status">Draft</span>
					</div>
					<h3 class="ih-at-preview__title" data-preview-title>Your tool title</h3>
					<p class="ih-at-preview__meta">
						<span data-preview-type>Mould type</span>
						<span class="ih-at-preview__dot" aria-hidden="true">·</span>
						<span data-preview-location>Location</span>
					</p>
					<div class="ih-at-preview__bar" aria-hidden="true"><span></span></div>
					<p class="ih-at-preview__util" data-preview-util>Cavities —</p>
				</div>
			</article>
			<p class="ih-at-preview__note">Anonymised by default — buyers see TL-IDs only; your name stays private until you approve a request.</p>
		</div>
		<div class="ih-at-rail-actions">
			<button type="submit" form="ihAddToolForm" class="ih-at-btn ih-at-btn--primary">Submit for approval</button>
			<button type="submit" form="ihAddToolForm" name="save_draft" value="1" class="ih-at-btn ih-at-btn--ghost">Save as draft</button>
			<small class="ih-at-last-saved" id="ihAtLastSaved" aria-live="polite">Not saved yet</small>
			<p>Goes live once an admin approves.</p>
		</div>
		<div class="ih-at-guide ih-at-guide--desktop">
			<button type="button" class="ih-at-guide__toggle" id="ihAtGuideToggleDesktop" aria-expanded="true" aria-controls="ihAtGuidePanelDesktop" data-guide-toggle>
				<span>Tool / mould guide</span>
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
			</button>
			<div class="ih-at-guide__panel" id="ihAtGuidePanelDesktop">
				<ul class="ih-at-guide__list">
					<li><strong>Multi-cavity:</strong> more parts per shot, higher output.</li>
					<li><strong>Hot runner:</strong> runnerless, less waste.</li>
					<li><strong>Insert mould:</strong> metal inserts encapsulated.</li>
					<li><strong>Cold runner:</strong> simple runner / scrap.</li>
					<li><strong>Toggle clamp:</strong> 5-point gives higher clamp force.</li>
					<li><strong>Tie-bar spacing:</strong> mould must fit between bars.</li>
				</ul>
			</div>
		</div>
	</aside>
</div>

<footer class="ih-at-mfooter ih-at-mobile-only" id="ihAtMobileFooter">
	<div class="ih-at-mfooter__time" aria-label="Current time">
		<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
		<span><?php echo esc_html( $ih_at_time_label ); ?></span>
	</div>
	<div class="ih-at-mfooter__strength">
		<span class="ih-at-mfooter__eyebrow">Listing strength</span>
		<strong class="ih-at-mfooter__tip" id="ihAtMobileStrengthTip">0% — getting started</strong>
	</div>
	<button type="submit" form="ihAddToolForm" name="save_draft" value="1" class="ih-at-mfooter__draft ih-at-btn ih-at-btn--ghost">Save</button>
	<button type="submit" form="ihAddToolForm" class="ih-at-mfooter__submit ih-at-btn ih-at-btn--primary">
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
		Submit
	</button>
</footer>

</div>
