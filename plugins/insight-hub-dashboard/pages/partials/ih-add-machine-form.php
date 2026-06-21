<?php
/**
 * Add Machine listing form — 6-step wizard (plugin partial).
 *
 * Expects (optional):
 *   $ih_am_mode       'user' | 'admin'
 *   $ih_am_user       WP_User
 *   $ih_am_dashboard  breadcrumb dashboard URL
 */
defined( 'ABSPATH' ) || exit;

$ih_am_mode = isset( $ih_am_mode ) ? $ih_am_mode : 'user';
$ih_am_user = isset( $ih_am_user ) && $ih_am_user instanceof WP_User ? $ih_am_user : wp_get_current_user();

$ih_am_is_user = ( $ih_am_mode === 'user' );
$ih_am_nonce_action = $ih_am_is_user ? 'ih_user_add_machine' : 'ih_add_machine';
$ih_am_nonce_name   = $ih_am_is_user ? 'ih_user_nonce' : 'ih_nonce_field';
$ih_am_submit_name  = $ih_am_is_user ? 'ih_user_machine_submit' : 'ih_machine_submit';

$ih_am_dashboard = isset( $ih_am_dashboard ) ? $ih_am_dashboard : (
	$ih_am_is_user
		? admin_url( 'admin.php?page=ih-user-dashboard' )
		: admin_url( 'admin.php?page=ih-dashboard' )
);

$ih_am_now_ts = current_time( 'timestamp' );
$ih_am_today  = current_time( 'Y-m-d' );
$ih_am_expiry = wp_date( 'Y-m-d', strtotime( '+3 months', $ih_am_now_ts ) );

$ih_am_ref_preview = 'MCH-#####';
$ih_am_date_label  = wp_date( 'D, j M', $ih_am_now_ts );
$ih_am_time_label  = wp_date( 'g:i A', $ih_am_now_ts );
$ih_am_avatar_initials = 'U';
if ( $ih_am_user->display_name ) {
	$parts = preg_split( '/\s+/', trim( $ih_am_user->display_name ) );
	if ( count( $parts ) >= 2 ) {
		$ih_am_avatar_initials = strtoupper( substr( $parts[0], 0, 1 ) . substr( $parts[1], 0, 1 ) );
	} else {
		$ih_am_avatar_initials = strtoupper( substr( $parts[0], 0, 2 ) );
	}
}

$ih_am_cert_options = array(
	'ISO 9001',
	'ISO 13485',
	'ISO 22000',
	'Medical',
	'Food grade',
	'TS 16949',
	'AS9100',
);

$ih_am_qc_options = array(
	'CMM',
	'Vision system',
	'In-process gauging',
	'First article inspection',
	'SPC',
	'PPAP',
);

$ih_am_cert_hints = array(
	'ISO 9001'  => 'Quality management',
	'ISO 13485' => 'Medical devices',
	'ISO 22000' => 'Food safety',
	'Medical'   => 'Medical grade ready',
	'Food grade' => 'Food contact ready',
	'TS 16949'  => 'Automotive quality',
	'AS9100'    => 'Aerospace quality',
);

$ih_am_advanced_caps = array(
	'overmoulding'     => 'Overmoulding',
	'insert_moulding'  => 'Insert Moulding',
	'iml'              => 'In-Mould Labelling',
	'gas_assisted'     => 'Gas-Assisted',
	'thin_wall'        => 'Thin-Wall Moulding',
);

$ih_am_advanced_hints = array(
	'overmoulding'    => 'Two-material overmould',
	'insert_moulding' => 'Metal / insert capable',
	'iml'             => 'IML ready',
	'gas_assisted'    => 'Gas-assist moulding',
	'thin_wall'       => 'High-speed thin-wall',
);

$ih_am_material_groups = function_exists( 'ih_machine_material_groups' ) ? ih_machine_material_groups() : array(
	'Commodity plastics' => array( 'PP', 'PE', 'HDPE', 'LDPE', 'LLDPE', 'PS', 'HIPS', 'PVC', 'PETG' ),
	'Engineering plastics' => array( 'ABS', 'PC', 'PC/ABS', 'ASA', 'SAN', 'PMMA', 'Nylon PA6', 'Nylon PA66', 'PA12', 'PA+GF', 'POM', 'Acetal', 'POM-C', 'POM-H', 'PBT', 'PBT+GF', 'PET', 'PPO/PPE', 'PP+GF' ),
	'Elastomers / flexible' => array( 'TPE', 'TPU' ),
	'High-performance plastics' => array( 'PEEK', 'PPS', 'PEI', 'LCP', 'PPA', 'PSU', 'PPSU' ),
	'Sustainable / recycled / bio' => array( 'PCR/Recycled resin', 'PLA/Bio-based' ),
);
$ih_am_materials = function_exists( 'ih_machine_material_options' ) ? ih_machine_material_options() : array_values( array_unique( array_merge( ...array_values( $ih_am_material_groups ) ) ) );
$ih_am_material_labels = array();
if ( function_exists( 'ih_machine_materials_map' ) ) {
	foreach ( ih_machine_materials_map() as $ih_am_material_def ) {
		$ih_am_material_labels[ $ih_am_material_def['code'] ] = $ih_am_material_def['label'];
	}
}
$ih_am_material_details = array(
	'ABS'        => array( 'label' => 'Acrylonitrile Butadiene Styrene', 'hint' => 'General purpose · Flame retardant · High impact' ),
	'PP'         => array( 'label' => 'Polypropylene', 'hint' => 'Homopolymer · Copolymer · Talc/Glass-filled · Food grade' ),
	'PE'         => array( 'label' => 'Polyethylene', 'hint' => 'General PE family · Flexible · Chemical resistant' ),
	'HDPE'       => array( 'label' => 'High-density Polyethylene', 'hint' => 'Containers · Caps · Chemical resistant' ),
	'LDPE'       => array( 'label' => 'Low-density Polyethylene', 'hint' => 'Flexible · Tough · Low-temperature impact' ),
	'LLDPE'      => array( 'label' => 'Linear low-density Polyethylene', 'hint' => 'Flexible · Film-grade · Impact resistant' ),
	'PC'         => array( 'label' => 'Polycarbonate', 'hint' => 'Clear · UV stable · Flame retardant' ),
	'PC/ABS'     => array( 'label' => 'Polycarbonate / ABS blend', 'hint' => 'Automotive grade · Flame retardant' ),
	'Nylon PA6'  => array( 'label' => 'Nylon 6', 'hint' => 'Natural · Black · Glass-filled · Wear resistant' ),
	'Nylon PA66' => array( 'label' => 'Nylon 66', 'hint' => 'Natural · Black · Glass-filled · Heat stabilised' ),
	'PA12'       => array( 'label' => 'Nylon 12', 'hint' => 'Low moisture uptake · Flexible · Chemical resistant' ),
	'POM'        => array( 'label' => 'Acetal / Delrin', 'hint' => 'Homopolymer · Copolymer · Low friction' ),
	'PBT'        => array( 'label' => 'Polybutylene Terephthalate', 'hint' => 'Electrical · Glass-filled · Flame retardant' ),
	'PET'        => array( 'label' => 'Polyethylene Terephthalate', 'hint' => 'Dimensional stability · Wear resistant' ),
	'PVC'        => array( 'label' => 'Polyvinyl Chloride', 'hint' => 'Rigid · Flexible · Chemical resistant' ),
	'TPU'        => array( 'label' => 'Thermoplastic Polyurethane', 'hint' => 'Elastic · Abrasion resistant · Soft-touch' ),
	'TPE'        => array( 'label' => 'Thermoplastic Elastomer', 'hint' => 'Flexible · Seals · Soft-touch grips' ),
	'PMMA'       => array( 'label' => 'Acrylic / PMMA', 'hint' => 'Optical clarity · Weather resistant' ),
	'ASA'        => array( 'label' => 'Acrylonitrile Styrene Acrylate', 'hint' => 'UV stable · Outdoor housings' ),
	'PS'         => array( 'label' => 'Polystyrene', 'hint' => 'Rigid · Commodity · Easy to mould' ),
	'SAN'        => array( 'label' => 'Styrene Acrylonitrile', 'hint' => 'Clear · Chemical resistant · Rigid' ),
	'PEEK'       => array( 'label' => 'Polyether Ether Ketone', 'hint' => 'High performance · High temperature' ),
	'PPS'        => array( 'label' => 'Polyphenylene Sulfide', 'hint' => 'Chemical resistant · High temperature' ),
	'PEI'        => array( 'label' => 'Polyetherimide', 'hint' => 'High heat · Flame resistant · Electrical' ),
	'LCP'        => array( 'label' => 'Liquid Crystal Polymer', 'hint' => 'Thin-wall · Electronics · High flow' ),
	'Acetal'     => array( 'label' => 'Acetal', 'hint' => 'Precision parts · Low friction' ),
	'HIPS'       => array( 'label' => 'High-impact Polystyrene', 'hint' => 'Impact modified · Low-cost housings' ),
	'PETG'       => array( 'label' => 'PETG', 'hint' => 'Clear · Tough · Chemical resistant' ),
	'PPO/PPE'    => array( 'label' => 'PPO / PPE', 'hint' => 'Heat resistant · Electrical · Low moisture' ),
	'PPA'        => array( 'label' => 'Polyphthalamide', 'hint' => 'High-heat nylon · Structural parts' ),
	'PA+GF'      => array( 'label' => 'Glass-filled Nylon', 'hint' => 'Reinforced · Structural · Heat stabilised' ),
	'PP+GF'      => array( 'label' => 'Glass-filled Polypropylene', 'hint' => 'Reinforced · Lightweight · Automotive' ),
	'PBT+GF'     => array( 'label' => 'Glass-filled PBT', 'hint' => 'Electrical connectors · Dimensionally stable' ),
	'POM-C'      => array( 'label' => 'POM copolymer', 'hint' => 'Low friction · Chemical resistant' ),
	'POM-H'      => array( 'label' => 'POM homopolymer', 'hint' => 'Stiff · Strong · Precision parts' ),
	'PSU'        => array( 'label' => 'Polysulfone', 'hint' => 'High heat · Steam resistant · Medical' ),
	'PPSU'       => array( 'label' => 'Polyphenylsulfone', 'hint' => 'Sterilisable · Tough · High temperature' ),
	'PCR/Recycled resin' => array( 'label' => 'PCR / Recycled resin', 'hint' => 'Post-consumer or recycled content · Sustainability' ),
	'PLA/Bio-based'      => array( 'label' => 'PLA / Bio-based resin', 'hint' => 'Renewable feedstock · Compostable grades where suitable' ),
);
?>
<div class="ih-am" id="ihAddMachineRoot">

<header class="ih-am-mhead ih-am-mobile-only" aria-label="Add a machine">
	<a href="<?php echo esc_url( $ih_am_dashboard ); ?>" class="ih-am-mhead__back" aria-label="Back to dashboard">
		<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
	</a>
	<div class="ih-am-mhead__copy">
		<h1 class="ih-am-mhead__title">Add a Machine</h1>
		<p class="ih-am-mhead__ref"><span data-preview-ref><?php echo esc_html( $ih_am_ref_preview ); ?></span></p>
	</div>
	<div class="ih-am-mhead__datetime" aria-label="Current date and time">
		<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
		<span class="ih-am-mhead__datetime-text">
			<strong><?php echo esc_html( $ih_am_date_label ); ?></strong>
			<small><?php echo esc_html( $ih_am_time_label ); ?></small>
		</span>
	</div>
	<div class="ih-am-mhead__avatar" aria-hidden="true"><?php echo esc_html( $ih_am_avatar_initials ); ?></div>
</header>

<div class="ih-am-mprogress ih-am-mobile-only" id="ihAmMobileProgress">
	<div class="ih-am-mprogress__row">
		<p class="ih-am-mprogress__label" id="ihAmMobileStepLabel">Step 1 of 6 — Identity</p>
		<span class="ih-am-mprogress__pct" id="ihAmMobileProgressPct">0%</span>
	</div>
	<div class="ih-am-mprogress__track" aria-hidden="true">
		<div class="ih-am-mprogress__fill" id="ihAmMobileProgressFill"></div>
	</div>
</div>

<header class="ih-am-pagehead ih-am-desktop-only">
	<nav class="ih-am-crumb" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( $ih_am_dashboard ); ?>">Dashboard</a>
		<span class="ih-am-crumb__sep" aria-hidden="true">/</span>
		<span class="ih-am-crumb__cur">New listing</span>
	</nav>
	<div class="ih-am-pagehead__row">
		<div class="ih-am-pagehead__copy">
			<h1 class="ih-am-title">Add a Machine listing</h1>
			<p class="ih-am-subtitle">List an injection press for approval — users see an anonymised card (MCH-#####).</p>
		</div>
		<div class="ih-am-pagehead__actions">
			<button type="submit" form="ihAddMachineForm" name="save_draft" value="1" class="ih-am-btn ih-am-btn--ghost">Save draft</button>
			<button type="submit" form="ihAddMachineForm" class="ih-am-btn ih-am-btn--primary">Publish listing</button>
		</div>
	</div>
</header>

<nav class="ih-am-stepper ih-am-desktop-only" aria-label="Form steps">
	<?php
	$ih_am_steps = array(
		array( 'id' => 'ih-am-sec-identity', 'label' => 'Identity', 'n' => 1 ),
		array( 'id' => 'ih-am-sec-specs', 'label' => 'Specs', 'n' => 2 ),
		array( 'id' => 'ih-am-sec-capability', 'label' => 'Capability', 'n' => 3 ),
		array( 'id' => 'ih-am-sec-production', 'label' => 'Production', 'n' => 4 ),
		array( 'id' => 'ih-am-sec-quality', 'label' => 'Quality', 'n' => 5 ),
		array( 'id' => 'ih-am-sec-images', 'label' => 'Images', 'n' => 6 ),
	);
	foreach ( $ih_am_steps as $i => $step ) :
		$active = $i === 0 ? ' is-active' : '';
		?>
	<button type="button" class="ih-am-step<?php echo esc_attr( $active ); ?>" data-target="<?php echo esc_attr( $step['id'] ); ?>">
		<span class="ih-am-step__num"><?php echo (int) $step['n']; ?></span>
		<span class="ih-am-step__label"><?php echo esc_html( $step['label'] ); ?></span>
	</button>
	<?php endforeach; ?>
</nav>

<div class="ih-am-layout">
	<div class="ih-am-form-col">
		<form method="POST" enctype="multipart/form-data" id="ihAddMachineForm" class="ih-am-form" novalidate data-ih-nonce-refresh data-ih-nonce-action="<?php echo esc_attr( $ih_am_nonce_action ); ?>" data-ih-nonce-field="<?php echo esc_attr( $ih_am_nonce_name ); ?>">
			<?php wp_nonce_field( $ih_am_nonce_action, $ih_am_nonce_name ); ?>
			<input type="hidden" name="<?php echo esc_attr( $ih_am_submit_name ); ?>" value="1">
			<div class="ih-am-toast" id="ihAmFormToast" role="status" aria-live="polite" hidden></div>

			<div class="ih-am-guide ih-am-guide--mobile ih-am-mobile-only">
				<button type="button" class="ih-am-guide__toggle" id="ihAmGuideToggleMobile" aria-expanded="false" aria-controls="ihAmGuidePanelMobile" data-guide-toggle>
					<span>Technical field guide</span>
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
				</button>
				<div class="ih-am-guide__panel" id="ihAmGuidePanelMobile" hidden>
					<ul class="ih-am-guide__list">
						<li><strong>Clamp data:</strong> enter tonnage, tie-bar, mould height, and clamp type so users can screen tools quickly.</li>
						<li><strong>Materials:</strong> pick polymer families and add grades like Makrolon 2458 or Zytel where known.</li>
						<li><strong>Photos:</strong> add the press plate, controller, nameplate, and a clean wide cover shot.</li>
					</ul>
				</div>
			</div>

			<!-- §1 Machine Identity -->
			<section id="ih-am-sec-identity" class="ih-am-section ih-am-accordion is-open" data-mobile-step="1" data-accordion-stat="">
				<button type="button" class="ih-am-section__head ih-am-accordion__head" data-accordion-toggle>
					<span class="ih-am-accordion__icon ih-am-accordion__icon--accent" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
					</span>
					<div class="ih-am-accordion__meta">
						<h2 class="ih-am-section__title">Machine Identity</h2>
						<p class="ih-am-accordion__sub">Brand, clamp setup and how many you run.</p>
					</div>
					<span class="ih-am-accordion__check ih-am-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-am-accordion__chev ih-am-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
					</span>
				</button>
				<div class="ih-am-accordion__body">
				<div class="ih-am-fields ih-am-fields--3 ih-am-fields--identity">
					<div class="ih-am-field ih-am-field--full ih-am-field--vital">
						<label class="ih-am-label" for="ih_am_title">Machine brand / title <span class="ih-am-star" aria-hidden="true">*</span></label>
						<input type="text" id="ih_am_title" name="title" class="ih-am-input" data-strength data-vital data-preview="title" placeholder="e.g. Engel Victory 120T" aria-describedby="ih_am_title_error" required>
						<small class="ih-am-field-error" id="ih_am_title_error" hidden>Enter a machine brand or title.</small>
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num ih-am-field--mobile-half">
						<label class="ih-am-label" for="ih_am_year">Year</label>
						<input type="number" id="ih_am_year" name="year_manufacture" class="ih-am-input" data-strength placeholder="e.g. 2019" min="1950" max="2099" step="1" inputmode="numeric">
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num ih-am-field--mobile-half">
						<label class="ih-am-label" for="ih_am_identical">Qty</label>
						<input type="number" id="ih_am_identical" name="identical_count" class="ih-am-input" data-strength min="1" step="1" value="1" placeholder="3" inputmode="numeric">
					</div>
					<div class="ih-am-field ih-am-field--md ih-am-field--full-mobile">
						<label class="ih-am-label" for="ih_am_clamp_drive">Clamp drive type</label>
						<select id="ih_am_clamp_drive" name="clamp_drive_type" class="ih-am-input" data-strength data-preview="type" aria-describedby="ih_am_clamp_drive_help">
							<option value="">Select drive…</option>
							<option value="Hydraulic">Hydraulic</option>
							<option value="Electric servo">Electric servo</option>
							<option value="Hybrid servo-hydraulic">Hybrid servo-hydraulic</option>
							<option value="Toggle electric">Toggle electric</option>
						</select>
						<small class="ih-am-help" id="ih_am_clamp_drive_help">Helps users understand energy use, repeatability, and maintenance profile.</small>
					</div>
					<div class="ih-am-field ih-am-field--md ih-am-field--full-mobile">
						<label class="ih-am-label" for="ih_am_toggle_clamp">Toggle clamp type</label>
						<select id="ih_am_toggle_clamp" name="toggle_clamp_type" class="ih-am-input" data-strength aria-describedby="ih_am_toggle_clamp_help">
							<option value="">Select clamp…</option>
							<option value="5-point toggle">5-point toggle</option>
							<option value="Direct hydraulic clamp">Direct hydraulic clamp</option>
							<option value="Two-platen">Two-platen</option>
							<option value="C-frame / vertical">C-frame / vertical</option>
							<option value="Not applicable">Not applicable</option>
						</select>
						<small class="ih-am-help" id="ih_am_toggle_clamp_help">Use "Not applicable" for presses without a toggle clamp mechanism.</small>
					</div>
					<div class="ih-am-field ih-am-field--md ih-am-field--vital ih-am-field--full-mobile ih-am-field--identity-location">
						<label class="ih-am-label" for="ih_am_location">Location <span class="ih-am-star" aria-hidden="true">*</span></label>
						<input type="text" id="ih_am_location" name="location" class="ih-am-input" data-strength data-vital data-preview="location" placeholder="e.g. Birmingham, UK" aria-describedby="ih_am_location_error" required>
						<small class="ih-am-field-error" id="ih_am_location_error" hidden>Enter the machine location.</small>
					</div>
					<input type="hidden" name="brand" value="">
				</div>

				</div>
			</section>

			<!-- §2 Specs -->
			<section id="ih-am-sec-specs" class="ih-am-section ih-am-accordion" data-mobile-step="2" data-accordion-stat="{filled} of {total}">
				<button type="button" class="ih-am-section__head ih-am-accordion__head" data-accordion-toggle>
					<span class="ih-am-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
					</span>
					<div class="ih-am-accordion__meta">
						<h2 class="ih-am-section__title">Core Processing Specs</h2>
						<p class="ih-am-accordion__sub">The numbers users match moulds against.</p>
						<p class="ih-am-accordion__stat ih-am-mobile-only" data-accordion-stat-label hidden></p>
					</div>
					<span class="ih-am-accordion__check ih-am-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-am-accordion__chev ih-am-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
					</span>
				</button>
				<div class="ih-am-accordion__body">
				<div class="ih-am-fields ih-am-fields--3">
					<div class="ih-am-field ih-am-field--md ih-am-field--num">
						<label class="ih-am-label" for="ih_am_clamping">Clamping force (ton)</label>
						<input type="number" id="ih_am_clamping" name="clamping_force" class="ih-am-input" data-strength placeholder="e.g. 150" min="0" step="1" inputmode="numeric" aria-describedby="ih_am_clamping_help">
						<small class="ih-am-help" id="ih_am_clamping_help">Use the rated clamp tonnage buyers match tools against.</small>
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_shot">Shot size (g)</label>
						<input type="number" id="ih_am_shot" name="shot_size" class="ih-am-input" data-strength placeholder="e.g. 120" min="0" step="0.1" inputmode="decimal" aria-describedby="ih_am_shot_help">
						<small class="ih-am-help" id="ih_am_shot_help">Approximate plastic shot capacity in grams.</small>
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_screw">Screw diameter (mm)</label>
						<input type="number" id="ih_am_screw" name="screw_diameter" class="ih-am-input" data-strength placeholder="45" min="0" step="0.1" inputmode="decimal">
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_pressure">Max injection pressure (bar)</label>
						<input type="number" id="ih_am_pressure" name="max_injection_pressure" class="ih-am-input" data-strength placeholder="2200" min="0" step="1" inputmode="numeric">
					</div>
					<div class="ih-am-field ih-am-field--md">
						<label class="ih-am-label" for="ih_am_tiebar">Tie-bar spacing (L × W mm)</label>
						<input type="text" id="ih_am_tiebar" name="tie_bar_spacing" class="ih-am-input" data-strength placeholder="e.g. 460 x 460" inputmode="text" aria-describedby="ih_am_tiebar_help">
						<small class="ih-am-help" id="ih_am_tiebar_help">Clear space between tie bars for mould fit checks.</small>
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_opening">Opening stroke (mm)</label>
						<input type="number" id="ih_am_opening" name="opening_stroke" class="ih-am-input" data-strength placeholder="420" min="0" step="1" inputmode="numeric">
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num ih-am-field--desktop-only">
						<label class="ih-am-label" for="ih_am_max_mould">Max mould height (mm)</label>
						<input type="number" id="ih_am_max_mould" name="max_mould_height" class="ih-am-input" data-strength placeholder="e.g. 550" min="0" step="1" inputmode="numeric" aria-describedby="ih_am_max_mould_help">
						<small class="ih-am-help" id="ih_am_max_mould_help">Largest mould height the clamp can safely accept.</small>
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num ih-am-field--desktop-only">
						<label class="ih-am-label" for="ih_am_min_mould">Min mould height (mm)</label>
						<input type="number" id="ih_am_min_mould" name="min_mould_height" class="ih-am-input" data-strength placeholder="e.g. 150" min="0" step="1" inputmode="numeric" aria-describedby="ih_am_min_mould_help">
						<small class="ih-am-help" id="ih_am_min_mould_help">Smallest mould height supported after daylight adjustment.</small>
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_proj_area">Projected area (cm²)</label>
						<input type="number" id="ih_am_proj_area" name="projected_area" class="ih-am-input" data-strength placeholder="e.g. 250" min="0" step="0.1" inputmode="decimal" aria-describedby="ih_am_proj_area_help">
						<small class="ih-am-help" id="ih_am_proj_area_help">Total projected area of the reference part/mould. Used with cavity pressure for a precise required clamp tonnage.</small>
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_cav_pressure">Cavity pressure (bar)</label>
						<input type="number" id="ih_am_cav_pressure" name="cavity_pressure" class="ih-am-input" data-strength placeholder="e.g. 350" min="0" step="1" inputmode="numeric" aria-describedby="ih_am_cav_pressure_help">
						<small class="ih-am-help" id="ih_am_cav_pressure_help">Typical in-cavity melt pressure (bar). Pairs with projected area to estimate required tonnage.</small>
					</div>
				</div>
				</div>
			</section>

			<!-- §3 Capability -->
			<section id="ih-am-sec-capability" class="ih-am-section ih-am-accordion" data-mobile-step="3" data-accordion-stat="{filled} of {total}">
				<button type="button" class="ih-am-section__head ih-am-accordion__head" data-accordion-toggle>
					<span class="ih-am-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
					</span>
					<div class="ih-am-accordion__meta">
						<h2 class="ih-am-section__title">Part Capability</h2>
						<p class="ih-am-accordion__sub">Max part weight &amp; dimensions</p>
						<p class="ih-am-accordion__stat ih-am-mobile-only" data-accordion-stat-label hidden></p>
					</div>
					<span class="ih-am-accordion__check ih-am-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-am-accordion__chev ih-am-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
					</span>
				</button>
				<div class="ih-am-accordion__body">
				<div class="ih-am-fields ih-am-fields--3">
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_max_weight">Max part weight (g)</label>
						<input type="number" id="ih_am_max_weight" name="max_part_weight" class="ih-am-input" data-strength placeholder="120" min="0" step="0.01" inputmode="decimal">
					</div>
					<div class="ih-am-field ih-am-field--full">
						<label class="ih-am-label" for="ih_am_max_dims">Max part dimensions (L × W × H mm)</label>
						<input type="text" id="ih_am_max_dims" name="max_part_dimensions" class="ih-am-input" data-strength placeholder="300 × 200 × 150" inputmode="text">
					</div>
					<div class="ih-am-field ih-am-field--full">
						<span class="ih-am-grouplbl">Achievable tolerance</span>
						<div class="ih-am-pills ih-am-pills--radio">
							<label class="ih-am-pill"><input type="radio" name="tolerance" value="±0.1 mm" data-strength> ±0.1 mm</label>
							<label class="ih-am-pill"><input type="radio" name="tolerance" value="±0.05 mm" data-strength> ±0.05 mm</label>
							<label class="ih-am-pill"><input type="radio" name="tolerance" value="±0.01 mm" data-strength> ±0.01 mm</label>
						</div>
					</div>
					<div class="ih-am-minihead">
						<span class="ih-am-minihead__icon" aria-hidden="true">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20"/><path d="M5 7h14"/><path d="M5 17h14"/></svg>
						</span>
						<div>
							<h3>Materials Supported</h3>
							<p>Polymers you can process — users filter on these.</p>
						</div>
					</div>
					<div class="ih-am-field ih-am-field--full ih-am-field--materials-picker">
						<label class="ih-am-label" for="ih_am_material_search">Supported materials</label>
						<div class="ih-am-materials" id="ihAmMaterialsPicker" data-materials-picker>
							<input type="hidden" id="ihAmMaterialsStrength" data-strength value="">
							<input type="hidden" id="ih_am_material_grade" name="material_grade" value="">
							<span class="ih-am-materials__hidden" id="ihAmMaterialsHidden" aria-hidden="true"></span>
							<div class="ih-am-materials__control" id="ihAmMaterialsControl" role="button" tabindex="0" aria-expanded="false" aria-controls="ihAmMaterialsDropdown" aria-describedby="ih_am_materials_help">
								<span class="ih-am-materials__chips" id="ihAmMaterialsChips" aria-live="polite">
									<span class="ih-am-materials__placeholder">+ Add materials...</span>
								</span>
								<span class="ih-am-materials__chev" aria-hidden="true">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
								</span>
							</div>
							<div class="ih-am-materials__dropdown" id="ihAmMaterialsDropdown">
								<div class="ih-am-materials__search-row">
									<input type="text" id="ih_am_material_search" class="ih-am-input ih-am-materials__search" placeholder="Search materials — ABS, PA66, PEEK…" autocomplete="off">
									<button type="button" class="ih-am-materials__add-custom" id="ihAmMaterialsAddCustom">Add custom</button>
								</div>
								<div class="ih-am-materials__list" role="group" aria-label="Available materials">
							<?php foreach ( $ih_am_material_groups as $ih_am_group_label => $ih_am_group_materials ) : ?>
									<div class="ih-am-materials__group" data-material-group>
										<div class="ih-am-materials__group-title"><?php echo esc_html( $ih_am_group_label ); ?></div>
							<?php foreach ( $ih_am_group_materials as $mat ) : ?>
									<?php
									$mat_label = isset( $ih_am_material_details[ $mat ]['label'] ) ? $ih_am_material_details[ $mat ]['label'] : ( isset( $ih_am_material_labels[ $mat ] ) ? $ih_am_material_labels[ $mat ] : $mat );
									$mat_hint  = isset( $ih_am_material_details[ $mat ]['hint'] ) ? $ih_am_material_details[ $mat ]['hint'] : 'Injection moulding material';
									?>
									<label class="ih-am-materials__option">
										<input type="checkbox" value="<?php echo esc_attr( $mat ); ?>" data-material-option data-material-label="<?php echo esc_attr( $mat_label ); ?>">
										<span class="ih-am-materials__code"><?php echo esc_html( $mat ); ?></span>
										<span class="ih-am-materials__copy">
											<span class="ih-am-materials__name"><?php echo esc_html( $mat_label ); ?></span>
											<span class="ih-am-materials__meta"><?php echo esc_html( $mat_hint ); ?></span>
										</span>
										<span class="ih-am-materials__check" aria-hidden="true"></span>
									</label>
							<?php endforeach; ?>
									</div>
							<?php endforeach; ?>
								</div>
								<p class="ih-am-materials__hint" id="ih_am_materials_help">Select supported polymers, add custom materials, then drag chips or use the arrow buttons to reorder them.</p>
							</div>
						</div>
					</div>
					<div class="ih-am-toggle-grid ih-am-toggle-grid--materials">
						<div class="ih-am-field ih-am-field--toggle">
							<label class="ih-am-toggle-row">
								<span class="ih-am-toggle-row__copy">
									<span class="ih-am-toggle-row__label">Engineering Grade</span>
									<span class="ih-am-toggle-row__hint">Filled / engineering polymers</span>
								</span>
								<input type="hidden" name="engineering_grade" value="No">
								<input type="checkbox" name="engineering_grade" value="Yes" class="ih-am-toggle-cb" data-strength>
								<span class="ih-am-switch" aria-hidden="true"></span>
							</label>
						</div>
						<div class="ih-am-field ih-am-field--toggle">
							<label class="ih-am-toggle-row">
								<span class="ih-am-toggle-row__copy">
									<span class="ih-am-toggle-row__label">Recycled Materials</span>
									<span class="ih-am-toggle-row__hint">Regrind / recycled resin</span>
								</span>
								<input type="hidden" name="recycled_materials" value="No">
								<input type="checkbox" name="recycled_materials" value="Yes" class="ih-am-toggle-cb" data-strength>
								<span class="ih-am-switch" aria-hidden="true"></span>
							</label>
						</div>
					</div>
				</div>
				</div>
			</section>

			<!-- §4 Production -->
			<section id="ih-am-sec-production" class="ih-am-section ih-am-accordion" data-mobile-step="4" data-accordion-stat="{filled} of {total}">
				<button type="button" class="ih-am-section__head ih-am-accordion__head" data-accordion-toggle>
					<span class="ih-am-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M12 12v4M10 14h4"/></svg>
					</span>
					<div class="ih-am-accordion__meta">
						<h2 class="ih-am-section__title">Production Capability</h2>
						<p class="ih-am-accordion__sub">Throughput, lead time and uptime.</p>
						<p class="ih-am-accordion__stat ih-am-mobile-only" data-accordion-stat-label hidden></p>
					</div>
					<span class="ih-am-accordion__check ih-am-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-am-accordion__chev ih-am-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
					</span>
				</button>
				<div class="ih-am-accordion__body">
				<div class="ih-am-fields ih-am-fields--3">
					<div class="ih-am-field ih-am-field--md">
						<label class="ih-am-label" for="ih_am_batch">Batch size</label>
						<select id="ih_am_batch" name="batch_size" class="ih-am-input" data-strength>
							<option value="">Select batch size…</option>
							<option value="Small (under 5,000)">Small (under 5,000)</option>
							<option value="Medium (5,000–50,000)">Medium (5,000–50,000)</option>
							<option value="Large (50,000+)">Large (50,000+)</option>
						</select>
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_moq">Min order qty</label>
						<input type="number" id="ih_am_moq" name="min_order_qty" class="ih-am-input" data-strength placeholder="500" min="0" step="1" inputmode="numeric">
					</div>
					<div class="ih-am-field ih-am-field--md ih-am-field--num">
						<label class="ih-am-label" for="ih_am_monthly">Max monthly output</label>
						<input type="number" id="ih_am_monthly" name="max_monthly_output" class="ih-am-input" data-strength placeholder="120000" min="0" step="1" inputmode="numeric">
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_cycle">Avg cycle time (s)</label>
						<input type="number" id="ih_am_cycle" name="avg_cycle_time" class="ih-am-input" data-strength placeholder="18" min="0" step="0.1" inputmode="decimal">
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_hours">Operating hours / day</label>
						<input type="number" id="ih_am_hours" name="operating_hours" class="ih-am-input" data-strength placeholder="16" min="0" max="24" step="0.5" inputmode="decimal">
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_util">Utilization %</label>
						<input type="number" id="ih_am_util" name="utilization" class="ih-am-input" data-strength data-preview="utilization" placeholder="65" min="0" max="100" step="1" inputmode="numeric">
					</div>
					<div class="ih-am-field ih-am-field--sm ih-am-field--num">
						<label class="ih-am-label" for="ih_am_cavities">Cavities (per mould)</label>
						<input type="number" id="ih_am_cavities" name="cavities" class="ih-am-input" data-strength placeholder="1" min="1" step="1" inputmode="numeric">
					</div>
				</div>
				</div>
			</section>

			<section class="ih-am-section ih-am-section--subcard ih-am-section--automation">
				<div class="ih-am-section__head">
					<span class="ih-am-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 17l6-6 4 4 6-8"/><path d="M14 7h6v6"/></svg>
					</span>
					<div class="ih-am-accordion__meta">
						<h2 class="ih-am-section__title">Automation &amp; Features</h2>
						<p class="ih-am-accordion__sub">Robotics, monitoring and add-ons.</p>
					</div>
				</div>
				<div class="ih-am-accordion__body">
				<div class="ih-am-fields ih-am-fields--2 ih-am-fields--automation">
					<div class="ih-am-field ih-am-field--md">
						<label class="ih-am-label" for="ih_am_automation">Automation level</label>
						<select id="ih_am_automation" name="automation_level" class="ih-am-input" data-strength aria-describedby="ih_am_automation_help">
							<option value="">Select level…</option>
							<option value="Manual">Manual</option>
							<option value="Semi-automated">Semi-automated</option>
							<option value="Fully automated">Fully automated</option>
						</select>
						<small class="ih-am-help" id="ih_am_automation_help">Describe handling, robot take-out, or lights-out readiness.</small>
					</div>
					<div class="ih-am-toggle-grid">
						<div class="ih-am-field ih-am-field--toggle">
							<label class="ih-am-toggle-row">
								<span class="ih-am-toggle-row__label">Robot integration</span>
								<input type="hidden" name="robot_integration" value="No">
								<input type="checkbox" name="robot_integration" value="Yes" class="ih-am-toggle-cb" data-strength>
								<span class="ih-am-switch" aria-hidden="true"></span>
							</label>
						</div>
						<div class="ih-am-field ih-am-field--toggle">
							<label class="ih-am-toggle-row">
								<span class="ih-am-toggle-row__label">Multi-cavity support</span>
								<input type="hidden" name="multi_cavity" value="No">
								<input type="checkbox" name="multi_cavity" value="Yes" class="ih-am-toggle-cb" data-strength>
								<span class="ih-am-switch" aria-hidden="true"></span>
							</label>
						</div>
					</div>
					<div class="ih-am-field ih-am-field--full">
						<label class="ih-am-label" for="ih_am_notes">Notes</label>
						<textarea id="ih_am_notes" name="notes" class="ih-am-input ih-am-textarea ih-am-textarea--compact" data-strength rows="2" placeholder="Shift patterns, secondary ops, or user notes…"></textarea>
					</div>
				</div>
				</div>
			</section>

			<section class="ih-am-section ih-am-section--subcard ih-am-section--advanced">
				<div class="ih-am-section__head">
					<span class="ih-am-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/></svg>
					</span>
					<div class="ih-am-accordion__meta">
						<h2 class="ih-am-section__title">Advanced Capabilities</h2>
						<p class="ih-am-accordion__sub">Specialist moulding processes you offer.</p>
					</div>
				</div>
				<div class="ih-am-accordion__body">
				<div class="ih-am-fields">
					<div class="ih-am-field ih-am-field--full">
						<div class="ih-am-checkgrid ih-am-checkgrid--switches">
							<?php foreach ( $ih_am_advanced_caps as $cap_name => $cap_label ) : ?>
							<label class="ih-am-check ih-am-switch-card">
								<input type="hidden" name="<?php echo esc_attr( $cap_name ); ?>" value="No">
								<input type="checkbox" name="<?php echo esc_attr( $cap_name ); ?>" value="Yes" class="ih-am-switch-card__input" data-strength>
								<span class="ih-am-switch-card__copy">
									<span class="ih-am-switch-card__label"><?php echo esc_html( $cap_label ); ?></span>
									<span class="ih-am-switch-card__hint"><?php echo esc_html( isset( $ih_am_advanced_hints[ $cap_name ] ) ? $ih_am_advanced_hints[ $cap_name ] : 'Special process capable' ); ?></span>
								</span>
								<span class="ih-am-card-switch" aria-hidden="true">
									<span class="ih-am-card-switch__off">
										<svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 5h6"/></svg>
									</span>
									<span class="ih-am-card-switch__knob">
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

			<!-- §5 Quality -->
			<section id="ih-am-sec-quality" class="ih-am-section ih-am-accordion" data-mobile-step="5" data-accordion-stat="{filled} of {total}">
				<button type="button" class="ih-am-section__head ih-am-accordion__head" data-accordion-toggle>
					<span class="ih-am-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
					</span>
					<div class="ih-am-accordion__meta">
						<h2 class="ih-am-section__title">Quality &amp; Compliance</h2>
						<p class="ih-am-accordion__sub">Certifications and inspection capability.</p>
						<p class="ih-am-accordion__stat ih-am-mobile-only" data-accordion-stat-label hidden></p>
					</div>
					<span class="ih-am-accordion__check ih-am-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-am-accordion__chev ih-am-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
					</span>
				</button>
				<div class="ih-am-accordion__body">
				<div class="ih-am-fields ih-am-fields--3">
					<div class="ih-am-field ih-am-field--full">
						<span class="ih-am-grouplbl">Certifications</span>
						<div class="ih-am-chipselect" id="ihAmCertPicker" data-chipselect="certifications" aria-describedby="ih_am_certs_help">
							<button type="button" class="ih-am-chipselect__control" id="ihAmCertControl" aria-expanded="false" aria-controls="ihAmCertDropdown">
								<span class="ih-am-chipselect__chips" id="ihAmCertChips" aria-live="polite">
									<span class="ih-am-chipselect__placeholder">+ Add certifications...</span>
								</span>
								<span class="ih-am-chipselect__chev" aria-hidden="true">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
								</span>
							</button>
							<div class="ih-am-chipselect__dropdown" id="ihAmCertDropdown" hidden>
								<div class="ih-am-chipselect__list" role="group" aria-label="Certification options">
									<?php foreach ( $ih_am_cert_options as $cert ) : ?>
									<label class="ih-am-chipselect__option">
										<input type="checkbox" name="certifications_list[]" value="<?php echo esc_attr( $cert ); ?>" data-strength-cert data-chip-option data-chip-label="<?php echo esc_attr( $cert ); ?>">
										<span class="ih-am-chipselect__copy">
											<span class="ih-am-chipselect__name"><?php echo esc_html( $cert ); ?></span>
											<span class="ih-am-chipselect__meta"><?php echo esc_html( isset( $ih_am_cert_hints[ $cert ] ) ? $ih_am_cert_hints[ $cert ] : 'Certification available' ); ?></span>
										</span>
										<span class="ih-am-chipselect__check" aria-hidden="true"></span>
									</label>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
						<small class="ih-am-help" id="ih_am_certs_help">Pick standards buyers filter on; they also sync to the certifications text field.</small>
						<input type="hidden" name="certifications" id="ihAmCertificationsHidden" value="">
					</div>
					<div class="ih-am-field ih-am-field--md ih-am-field--full-mobile">
						<label class="ih-am-label" for="ih_am_qc">QC tools</label>
						<div class="ih-am-chipselect" id="ihAmQcPicker" data-chipselect="qc" aria-describedby="ih_am_qc_help">
							<div class="ih-am-chipselect__control ih-am-chipselect__control--input" id="ihAmQcControl" role="button" tabindex="0" aria-expanded="false" aria-controls="ihAmQcDropdown">
								<span class="ih-am-chipselect__chips" id="ihAmQcChips" aria-live="polite"></span>
								<input type="text" id="ih_am_qc" name="qc_tools" class="ih-am-chipselect__input" data-strength placeholder="e.g. CMM, vision systems" autocomplete="off">
								<span class="ih-am-chipselect__chev" aria-hidden="true">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
								</span>
							</div>
							<div class="ih-am-chipselect__dropdown" id="ihAmQcDropdown" hidden>
								<div class="ih-am-chipselect__list" role="group" aria-label="QC tool options">
									<?php foreach ( $ih_am_qc_options as $qc ) : ?>
									<button type="button" class="ih-am-chipselect__option ih-am-chipselect__option--button" data-qc-option="<?php echo esc_attr( $qc ); ?>">
										<span class="ih-am-chipselect__copy">
											<span class="ih-am-chipselect__name"><?php echo esc_html( $qc ); ?></span>
										</span>
										<span class="ih-am-chipselect__check" aria-hidden="true"></span>
									</button>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
						<small class="ih-am-help" id="ih_am_qc_help">Add inspection equipment or quality documents available for this machine.</small>
					</div>
					<div class="ih-am-field ih-am-field--sm">
						<label class="ih-am-label" for="ih_am_tol_cons">Tolerance consistency</label>
						<select id="ih_am_tol_cons" name="tolerance_consistency" class="ih-am-input" data-strength>
							<option value="">Select…</option>
							<option value="High">High</option>
							<option value="Medium">Medium</option>
							<option value="Low">Low</option>
						</select>
					</div>
				</div>
				</div>
			</section>

			<section class="ih-am-section ih-am-section--subcard ih-am-section--availability">
				<div class="ih-am-section__head">
					<span class="ih-am-accordion__icon" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
					</span>
					<div class="ih-am-accordion__meta">
						<h2 class="ih-am-section__title">Listing &amp; Availability</h2>
						<p class="ih-am-accordion__sub">When this listing is live and visible.</p>
					</div>
				</div>
				<div class="ih-am-accordion__body">
				<div class="ih-am-fields">
					<div class="ih-am-field-pair ih-am-date-pair">
						<div class="ih-am-field ih-am-field--md">
							<label class="ih-am-label" for="ih_am_listing_date">Listing date</label>
							<input type="date" id="ih_am_listing_date" name="listing_date" class="ih-am-input" data-strength value="<?php echo esc_attr( $ih_am_today ); ?>" aria-describedby="ih_am_listing_date_help">
							<small class="ih-am-help" id="ih_am_listing_date_help">Defaults to today for new listings.</small>
						</div>
						<div class="ih-am-field ih-am-field--md">
							<label class="ih-am-label" for="ih_am_expiry_date">Expiry date</label>
							<input type="date" id="ih_am_expiry_date" name="expiry_date" class="ih-am-input" data-strength value="<?php echo esc_attr( $ih_am_expiry ); ?>" min="<?php echo esc_attr( $ih_am_today ); ?>" aria-describedby="ih_am_expiry_date_help ih_am_expiry_date_warning ih_am_expiry_date_error">
							<small class="ih-am-help" id="ih_am_expiry_date_help">Defaults to 90 days from today; extend it before the listing expires.</small>
							<p class="ih-am-warning" id="ih_am_expiry_date_warning">This listing will stop showing publicly after this date.</p>
							<small class="ih-am-field-error" id="ih_am_expiry_date_error" hidden>Choose an expiry date today or later, on or after the listing date.</small>
						</div>
					</div>
				</div>
				</div>
			</section>

			<!-- §6 Images -->
			<section id="ih-am-sec-images" class="ih-am-section ih-am-accordion" data-mobile-step="6" data-accordion-stat="">
				<button type="button" class="ih-am-section__head ih-am-accordion__head" data-accordion-toggle>
					<span class="ih-am-accordion__icon ih-am-accordion__icon--accent" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
					</span>
					<div class="ih-am-accordion__meta">
						<h2 class="ih-am-section__title">Upload Images</h2>
						<p class="ih-am-accordion__sub">Show the press clearly — up to 5 photos.</p>
					</div>
					<span class="ih-am-accordion__check ih-am-mobile-only" hidden aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</span>
					<span class="ih-am-accordion__chev ih-am-mobile-only" aria-hidden="true">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
					</span>
				</button>
				<div class="ih-am-accordion__body">
				<div class="ih-am-upload-cover">
					<label class="ih-am-drop ih-am-drop--cover" data-upload-slot="1">
						<input type="file" name="image_1" accept="image/png,image/jpeg,image/jpg,image/webp" hidden data-strength-image>
						<span class="ih-am-drop__inner">
							<span class="ih-am-drop__icon ih-am-desktop-only" aria-hidden="true">📷</span>
							<span class="ih-am-drop__icon ih-am-drop__icon--svg ih-am-mobile-only" aria-hidden="true">
								<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
							</span>
							<strong class="ih-am-desktop-only">Drop your cover image or click to upload</strong>
							<strong class="ih-am-mobile-only">Tap to upload cover</strong>
							<small>PNG, JPG up to 8MB<span class="ih-am-desktop-only"> · recommended 1200×800</span></small>
						</span>
						<span class="ih-am-drop__cover-badge">Cover image</span>
						<button type="button" class="ih-am-drop__remove" hidden aria-label="Remove image">&times;</button>
					</label>
				</div>
				<p class="ih-am-upload-hint" id="ih_am_images_help">Up to 5 photos. Drag, preview, remove, and move photos before submitting; Image 1 is stored as the cover.</p>
				<small class="ih-am-field-error" id="ih_am_images_error" hidden>Add at least one image before submitting.</small>
				<div class="ih-am-upload-row">
					<?php for ( $img_i = 2; $img_i <= 5; $img_i++ ) : ?>
					<label class="ih-am-drop ih-am-drop--thumb" data-upload-slot="<?php echo (int) $img_i; ?>">
						<input type="file" name="image_<?php echo (int) $img_i; ?>" accept="image/png,image/jpeg,image/jpg,image/webp" hidden<?php echo $img_i <= 3 ? ' data-strength-image' : ' data-strength-image-optional'; ?>>
						<span class="ih-am-drop__inner">
							<span class="ih-am-drop__plus">+</span>
							<small>Photo <?php echo (int) $img_i; ?></small>
						</span>
						<button type="button" class="ih-am-drop__cover" hidden>Make cover</button>
						<button type="button" class="ih-am-drop__remove" hidden aria-label="Remove image">&times;</button>
					</label>
					<?php endfor; ?>
				</div>
				</div>
			</section>

			<div class="ih-am-mbanner ih-am-mobile-only" role="note">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
				<div class="ih-am-mbanner__copy">
					<strong>Specs power the match engine</strong>
				<p>Clamping force &amp; tie-bar spacing decide mould compatibility.</p>
				</div>
			</div>

			<footer class="ih-am-footer ih-am-desktop-only">
				<p class="ih-am-footer__note">Goes live once an admin approves.</p>
				<div class="ih-am-footer__actions">
					<button type="submit" name="save_draft" value="1" class="ih-am-btn ih-am-btn--ghost">Save as draft</button>
					<button type="submit" class="ih-am-btn ih-am-btn--primary">Submit for approval</button>
				</div>
			</footer>
		</form>
	</div>

	<aside class="ih-am-sidebar ih-am-desktop-only" aria-label="Listing strength">
		<div class="ih-am-strength" id="ihAmStrength">
			<div class="ih-am-strength__head">
				<div class="ih-am-strength__ring">
					<svg viewBox="0 0 44 44" aria-hidden="true"><circle class="ih-am-strength__bg" cx="22" cy="22" r="18"/><circle class="ih-am-strength__fg" cx="22" cy="22" r="18"/></svg>
					<span class="ih-am-strength__pct" id="ihAmStrengthPct">0%</span>
				</div>
				<div class="ih-am-strength__copy">
					<span class="ih-am-rail-eyebrow">Listing strength</span>
					<p class="ih-am-strength__label" id="ihAmStrengthLabel">Getting started</p>
					<small>Add specs &amp; images to finish.</small>
				</div>
			</div>
			<ul class="ih-am-strength__tips">
				<li id="ihAmStrengthIdentity"><span class="ih-am-strength__dot" aria-hidden="true"></span>Machine identity</li>
				<li id="ihAmStrengthSpecs"><span class="ih-am-strength__dot" aria-hidden="true"></span>Core processing specs</li>
				<li id="ihAmStrengthProduction"><span class="ih-am-strength__dot" aria-hidden="true"></span>Production capability</li>
				<li id="ihAmStrengthCerts"><span class="ih-am-strength__dot" aria-hidden="true"></span>Certifications / QC</li>
				<li id="ihAmStrengthImgTip"><span class="ih-am-strength__dot" aria-hidden="true"></span>Cover image</li>
			</ul>
			<p class="ih-am-strength__live" id="ihAmStrengthLive" aria-live="polite">0% complete. Add required fields and a cover image.</p>
		</div>
		<div class="ih-am-preview" id="ihAmLivePreview">
			<div class="ih-am-preview__label">Live preview <span class="ih-am-preview__tag">User view</span></div>
			<article class="ih-am-preview__card">
				<div class="ih-am-preview__hero" aria-hidden="true">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
				</div>
				<div class="ih-am-preview__body">
					<div class="ih-am-preview__top">
						<span class="ih-am-preview__ref" data-preview-ref><?php echo esc_html( $ih_am_ref_preview ); ?></span>
						<span class="ih-am-preview__status">Draft</span>
					</div>
					<h3 class="ih-am-preview__title" data-preview-title>Your machine title</h3>
					<p class="ih-am-preview__meta">
						<span data-preview-type>Clamp drive</span>
						<span class="ih-am-preview__dot" aria-hidden="true">·</span>
						<span data-preview-location>Location</span>
					</p>
					<div class="ih-am-preview__bar" aria-hidden="true"><span></span></div>
					<p class="ih-am-preview__util" data-preview-util>Utilization —</p>
				</div>
			</article>
		</div>
		<div class="ih-am-rail-actions">
			<button type="submit" form="ihAddMachineForm" class="ih-am-btn ih-am-btn--primary">Submit for approval</button>
			<button type="submit" form="ihAddMachineForm" name="save_draft" value="1" class="ih-am-btn ih-am-btn--ghost">Save as draft</button>
			<small class="ih-am-last-saved" id="ihAmLastSaved" aria-live="polite">Not saved yet</small>
			<p>Goes live once an admin approves.</p>
		</div>
		<div class="ih-am-guide ih-am-guide--desktop">
			<button type="button" class="ih-am-guide__toggle" id="ihAmGuideToggleDesktop" aria-expanded="true" aria-controls="ihAmGuidePanelDesktop" data-guide-toggle>
				<span>Tool / mould guide</span>
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
			</button>
			<div class="ih-am-guide__panel" id="ihAmGuidePanelDesktop">
				<ul class="ih-am-guide__list">
					<li><strong>Multi shot:</strong> two part or substrate per shot.</li>
					<li><strong>Hot runner:</strong> runnerless, less waste.</li>
					<li><strong>Insert mould:</strong> metal inserts encapsulated.</li>
					<li><strong>Cold runner:</strong> simple runner/scrap.</li>
					<li><strong>Total shot:</strong> part + runner in one cycle.</li>
					<li><strong>Tool height:</strong> must fit between platens.</li>
				</ul>
			</div>
		</div>
	</aside>
</div>

<footer class="ih-am-mfooter ih-am-mobile-only" id="ihAmMobileFooter">
	<div class="ih-am-mfooter__time" aria-label="Current time">
		<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
		<span><?php echo esc_html( $ih_am_time_label ); ?></span>
	</div>
	<div class="ih-am-mfooter__strength">
		<span class="ih-am-mfooter__eyebrow">Listing strength</span>
		<strong class="ih-am-mfooter__tip" id="ihAmMobileStrengthTip">0% — getting started</strong>
	</div>
	<button type="submit" form="ihAddMachineForm" name="save_draft" value="1" class="ih-am-mfooter__draft ih-am-btn ih-am-btn--ghost">Save</button>
	<button type="submit" form="ihAddMachineForm" class="ih-am-mfooter__submit ih-am-btn ih-am-btn--primary">
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
		Submit
	</button>
</footer>

</div>
