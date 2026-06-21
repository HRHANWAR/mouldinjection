<?php defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( admin_url('admin.php?page=ih-user-dashboard') ) );
    exit;
}
if ( current_user_can('administrator') ) {
    wp_redirect( admin_url('admin.php?page=ih-dashboard') );
    exit;
}

$machine_id = intval( $_GET['machine_id'] ?? 0 );
$user_id    = get_current_user_id();
global $wpdb;

$m = $machine_id ? $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ih_machines WHERE id=%d",
    $machine_id
), ARRAY_A ) : null;

if ( $m && (int)$m['owner_id'] === 0 ) {
    $wpdb->update( $wpdb->prefix . 'ih_machines', ['owner_id' => $user_id], ['id' => $machine_id] );
    $m['owner_id'] = $user_id;
}

if ( ! $m || ( (int)$m['owner_id'] !== $user_id ) ) {
    wp_redirect( admin_url('admin.php?page=ih-user-dashboard') );
    exit;
}

$saved       = isset($_GET['saved']) && $_GET['saved'] == '1';
$machine_ref = 'MCH-' . str_pad( $machine_id, 5, '0', STR_PAD_LEFT );
$error       = isset($_GET['ih_error']) ? sanitize_text_field(urldecode($_GET['ih_error'])) : '';

$user         = wp_get_current_user();
$email_hash   = md5( strtolower( trim( $user->user_email ) ) );
$gravatar_url = 'https://www.gravatar.com/avatar/' . $email_hash . '?s=72&d=404';
$fallback_url = 'https://ui-avatars.com/api/?name='.rawurlencode($user->display_name ?: 'U').'&background=142F32&color=C8FF00&size=72&bold=true&rounded=true&length=2';

$notifs = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND status='Approved' ORDER BY id DESC LIMIT 10", $user_id
), ARRAY_A ) ?: [];
$notif_count = count($notifs);

$search_keywords = [
    'Hydraulic','Electric','Hybrid',
    'ABS','Polypropylene (PP)','Polyethylene (PE)','Nylon (PA)','Polycarbonate (PC)','PEEK',
    'ISO 9001','ISO 13485','TS 16949',
    'Overmoulding','Insert Moulding','In-Mould Labelling','Gas-Assisted','Thin-Wall Moulding',
    'Robot Integration','Engineering Grade','Multi-Cavity','Automated','Semi-automated',
    'High Tolerance','±0.01 mm','±0.05 mm',
    'Manchester','London','Birmingham','Leeds','Glasgow',
    'Clamping Force','Shot Size','Screw Diameter','Cycle Time','Batch Size',
];
$search_keywords_json = json_encode($search_keywords);

$form_fields = [
    ['label'=>'Machine Brand / Title',        'field'=>'title'],
    ['label'=>'Year of Manufacture',           'field'=>'year_manufacture'],
    ['label'=>'Machine Type',                  'field'=>'machine_type'],
    ['label'=>'Clamping Force',                'field'=>'clamping_force'],
    ['label'=>'Shot Size',                     'field'=>'shot_size'],
    ['label'=>'Screw Diameter',                'field'=>'screw_diameter'],
    ['label'=>'Location',                      'field'=>'location'],
    ['label'=>'Certifications',                'field'=>'certifications'],
    ['label'=>'Automation Level',              'field'=>'automation_level'],
    ['label'=>'Batch Size',                    'field'=>'batch_size'],
];
$form_fields_json = json_encode($form_fields);

/* helper: is toggle checked? */
function ih_checked($val){ return ($val === 'Yes') ? 'checked' : ''; }
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Machine — Injection Moulding</title>
<?php wp_head(); ?>
<style>
/* ── BANNER ── */
.ih-banner{
    background:linear-gradient(135deg,#0d2219 0%,#142F32 55%,#1a3d30 100%);
    border-radius:16px;padding:28px 30px;color:#fff;
    margin-bottom:14px;position:relative;overflow:hidden;
}
.ih-banner::before{content:'';position:absolute;width:260px;height:260px;background:rgba(200,255,0,.05);border-radius:50%;top:-130px;right:-70px;pointer-events:none;}
.ih-banner-title{font-size:22px;font-weight:800;margin-bottom:6px;position:relative;z-index:2;letter-spacing:-.4px;}
.ih-banner-title span{color:#C8FF00;}
.ih-banner-sub{font-size:13px;opacity:.75;line-height:1.65;margin-bottom:18px;position:relative;z-index:2;max-width:480px;}
.ih-banner-actions{display:flex;gap:9px;flex-wrap:wrap;position:relative;z-index:2;}
.ih-btn-white{background:#fff;color:#142F32;padding:8px 20px;border-radius:50px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:.14s;}
.ih-btn-white:hover{background:#C8FF00;}
.ih-btn-outline-w{background:rgba(200,255,0,.12);color:#C8FF00;border:1px solid rgba(200,255,0,.3);padding:8px 20px;border-radius:50px;font-size:12px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:.14s;}
.ih-btn-outline-w:hover{background:rgba(200,255,0,.22);}

/* Machine ref badge */
.ih-mach-ref{display:inline-flex;align-items:center;gap:5px;background:#e0f2fe;color:#0369a1;font-size:10px;font-weight:700;padding:3px 9px;border-radius:20px;font-family:'DM Mono',monospace;letter-spacing:.4px;}

/* ── FORM SECTIONS ── */
.ih-form-section{background:#fff;border:1px solid #e2eae5;border-radius:14px;margin-bottom:10px;overflow:hidden;}
.ih-form-section-title{display:flex;align-items:center;gap:9px;padding:12px 22px 11px;background:#fafcfb;border-bottom:1px solid #eaf0ec;font-size:10.5px;font-weight:800;color:#142F32;text-transform:uppercase;letter-spacing:.9px;}
.ih-form-section-title::before{content:'';width:3px;height:13px;background:#142F32;border-radius:2px;flex-shrink:0;}

/* FIELD ROWS */
.ih-row{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid #f0f5f2;}
.ih-row:last-child{border-bottom:none;}
.ih-row.cols-3{grid-template-columns:1fr 1fr 1fr;}
.ih-row.cols-4{grid-template-columns:1fr 1fr 1fr 1fr;}
.ih-cell{padding:13px 22px 14px;border-right:1px solid #f0f5f2;display:flex;flex-direction:column;gap:5px;min-width:0;}
.ih-cell:last-child{border-right:none;}
.ih-cell-label{font-size:10px;font-weight:700;color:#9ab8a8;text-transform:uppercase;letter-spacing:.55px;line-height:1;display:flex;align-items:center;gap:3px;}
.ih-cell-label .req{color:#e05252;}

/* FLAT INPUT */
.ih-input{width:100%;border:none;border-bottom:1.5px solid #d4e2da;border-radius:0;padding:6px 0 8px;font-size:13px;font-family:inherit;font-weight:500;color:#1a2e26;background:transparent;outline:none;-webkit-appearance:none;appearance:none;transition:border-color .15s;}
.ih-input:focus{border-bottom-color:#142F32;}
.ih-input:hover:not(:focus){border-bottom-color:#9fc4b0;}
.ih-input:not(:placeholder-shown):not(:focus){color:#0d1f18;border-bottom-color:#86b8a0;}
.ih-input::placeholder{color:#c0d5c8;font-size:12.5px;font-weight:400;}
select.ih-input{cursor:pointer;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23142F32' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 2px center;padding-right:18px;background-color:transparent;}
input[type=number].ih-input{-moz-appearance:textfield;}
input[type=number].ih-input::-webkit-inner-spin-button,
input[type=number].ih-input::-webkit-outer-spin-button{-webkit-appearance:none;}
input[type=date].ih-input::-webkit-calendar-picker-indicator{opacity:.4;cursor:pointer;}

/* PILL checkboxes / radios */
.ih-pill-row{display:flex;flex-wrap:wrap;gap:7px;padding:13px 22px 14px;border-bottom:1px solid #f0f5f2;align-items:center;}
.ih-pill-row:last-child{border-bottom:none;}
.ih-pill-head{font-size:10px;font-weight:700;color:#9ab8a8;text-transform:uppercase;letter-spacing:.55px;margin-right:6px;white-space:nowrap;}
.ih-pill{display:flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;cursor:pointer;color:#3d5a4a;background:#f4f8f5;border:1px solid #d4e2da;padding:5px 12px;border-radius:7px;transition:all .13s;user-select:none;}
.ih-pill:hover{border-color:#142F32;color:#142F32;background:#eaf3ed;}
.ih-pill:has(input:checked){background:#142F32;color:#C8FF00;border-color:#142F32;}
.ih-pill input{width:12px;height:12px;accent-color:#C8FF00;flex-shrink:0;cursor:pointer;}
.ih-pill input[type=radio]{accent-color:#142F32;}

/* TOGGLE row */
.ih-toggle-row{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid #f0f5f2;}
.ih-toggle-cell{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-right:1px solid #f0f5f2;gap:16px;transition:background .15s;min-height:62px;}
.ih-toggle-cell:nth-child(3n){border-right:none;}
.ih-toggle-label{font-size:13px;font-weight:600;color:#4a6657;line-height:1.3;}
.ih-toggle-switch{position:relative;display:inline-flex;align-items:center;cursor:pointer;flex-shrink:0;}
.ih-toggle-switch input{display:none;}
.ih-toggle-slider{
    width:52px;height:28px;
    background:#c8d9d0;
    border-radius:50px;
    transition:background .2s;
    position:relative;
    box-shadow:inset 0 1px 3px rgba(0,0,0,.08);
}
.ih-toggle-slider::after{
    content:'';
    position:absolute;left:3px;top:3px;
    width:22px;height:22px;
    border-radius:50%;
    background:#fff;
    transition:left .2s cubic-bezier(.4,0,.2,1);
    box-shadow:0 2px 6px rgba(0,0,0,.22);
}
.ih-toggle-cb:checked + .ih-toggle-slider{background:#142F32;}
.ih-toggle-cb:checked + .ih-toggle-slider::after{left:27px;box-shadow:0 2px 6px rgba(20,47,50,.35);}
.ih-toggle-cell:has(.ih-toggle-cb:checked){background:#f0fbf2;}
.ih-toggle-cell:has(.ih-toggle-cb:checked) .ih-toggle-label{color:#142F32;font-weight:700;}

/* UPLOAD */
.ih-upload-row{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid #f0f5f2;}
.ih-upload-cell{border-right:1px solid #f0f5f2;padding:18px 22px;}
.ih-upload-cell:last-child{border-right:none;}
.ih-upload-box{border:2px dashed #c0d6c8;border-radius:11px;height:108px;width:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;background:#f8fbf9;position:relative;overflow:hidden;transition:border-color .17s,background .17s;gap:6px;}
.ih-upload-box:hover{border-color:#142F32;background:#eef5f0;}
.ih-upload-box span{font-size:11px;font-weight:600;color:#8fb0a0;}
/* Current image thumb inside upload box */
.ih-upload-box.has-img{border-color:#C8FF00;border-style:solid;}
.ih-upload-box.has-img svg,.ih-upload-box.has-img span{display:none;}

/* SUBMIT */
.ih-submit-btn{width:100%;background:#142F32;color:#C8FF00;padding:14px;border:none;border-radius:11px;font-size:14px;font-weight:800;cursor:pointer;font-family:inherit;letter-spacing:.3px;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .14s,transform .1s,box-shadow .14s;box-shadow:0 4px 16px rgba(20,47,50,.2);}
.ih-submit-btn:hover{background:#0d2219;box-shadow:0 6px 22px rgba(20,47,50,.28);transform:translateY(-1px);}
.ih-submit-btn:active{transform:translateY(0);}

/* SUCCESS / ERROR */
.ih-success-box{text-align:center;padding:68px 20px;background:#fff;border-radius:14px;border:1px solid #e2eae5;}
.ih-error-box{background:#fee2e2;color:#dc2626;padding:12px 16px;border-radius:9px;margin-bottom:12px;font-size:13px;font-weight:500;}

@media(max-width:768px){
    .ih-page-header{padding:10px 14px;gap:8px;}
    .ih-page-title{font-size:17px;}
    .ih-content{padding:10px 10px 36px;}
    .ih-row,.ih-row.cols-3,.ih-row.cols-4{grid-template-columns:1fr;}
    .ih-cell{border-right:none;padding:11px 16px;}
    .ih-toggle-row{grid-template-columns:1fr 1fr;}
    .ih-toggle-cell{border-right:none;border-bottom:1px solid #f0f5f2;}
    .ih-toggle-cell:nth-child(odd){border-right:1px solid #f0f5f2;}
    .ih-upload-row{grid-template-columns:1fr 1fr;}
    .ih-upload-cell{border-right:none;border-bottom:1px solid #f0f5f2;}
    .ih-upload-cell:nth-child(odd){border-right:1px solid #f0f5f2;}
    .ih-form-section-title{padding:11px 16px;}
    .ih-pill-row{padding:11px 16px;}
    .ih-banner{padding:20px 18px;border-radius:13px;}
    .ih-banner-title{font-size:18px;}
}
@media(max-width:480px){
    .ih-toggle-row{grid-template-columns:1fr;}
    .ih-toggle-cell{border-right:none!important;}
    .ih-upload-row{grid-template-columns:1fr;}
    .ih-upload-cell{border-right:none!important;}
    .ih-pill{font-size:11px;padding:4px 10px;}
    .ih-banner-actions{flex-direction:column;}
    .ih-btn-white,.ih-btn-outline-w{justify-content:center;}
}
</style>
</head>
<body>
<?php include IH_DIR . 'pages/partials/ih-user-shell-start.php'; ?>

<?php include IH_DIR . 'pages/partials/ih-user-shell-header.php'; ?>

<!-- BODY -->
<div class="ih-body">

  <main class="ih-main">
    <div class="ih-page-header">
      <div>
        <div class="ih-page-title">Edit Machine</div>
        <div class="ih-page-sub">
          <?php echo esc_html($m['title']); ?>
          <span class="ih-mach-ref"><?php echo esc_html($machine_ref); ?></span>
        </div>
      </div>
      <a href="<?php echo admin_url('admin.php?page=ih-user-dashboard'); ?>" style="display:inline-flex;align-items:center;gap:6px;background:#f0f7f3;color:#374151;padding:8px 16px;border-radius:20px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;border:1px solid #d4e2da;">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>My Listings
      </a>
    </div>

    <div class="ih-content">

    <?php if ($saved): ?>
    <div class="ih-success-box">
      <div style="width:76px;height:76px;background:#C8FF00;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#142F32" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
      <h3 style="font-size:21px;font-weight:800;margin-bottom:8px;color:#142F32;letter-spacing:-.4px;">Machine Updated!</h3>
      <p style="color:#6b7280;margin-bottom:26px;font-size:14px;">Your listing has been updated successfully.</p>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <a href="<?php echo admin_url('admin.php?page=ih-user-edit-machine&machine_id='.$machine_id); ?>" style="background:#142F32;color:#C8FF00;padding:10px 24px;border-radius:50px;text-decoration:none;font-size:13px;font-weight:700;">Edit Again</a>
        <a href="<?php echo admin_url('admin.php?page=ih-user-dashboard'); ?>" style="background:#f0f7f3;color:#374151;padding:10px 24px;border-radius:50px;text-decoration:none;font-size:13px;font-weight:700;border:1px solid #d4e2da;">My Listings</a>
      </div>
    </div>
    <?php else: ?>

    <?php if ($error): ?><div class="ih-error-box"><?php echo esc_html($error); ?></div><?php endif; ?>

    <!-- Banner -->
    <div class="ih-banner">
      <div class="ih-banner-title">Edit Machine <span>Listing</span></div>
      <div class="ih-banner-sub">Update your machine details below. Changes will be saved immediately to your listing.</div>
      <div class="ih-banner-actions">
        <a href="<?php echo admin_url('admin.php?page=ih-user-dashboard'); ?>" class="ih-btn-white"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>My Listings</a>
        <a href="<?php echo admin_url('admin.php?page=ih-user-add-machine'); ?>" class="ih-btn-outline-w"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/Machine-user.png" alt="" style="width:15px;height:15px;">Add New Machine</a>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="ihEditForm" data-ih-nonce-refresh data-ih-nonce-action="ih_user_edit_machine" data-ih-nonce-field="ih_user_nonce">
      <?php wp_nonce_field('ih_user_edit_machine', 'ih_user_nonce'); ?>
      <input type="hidden" name="ih_user_machine_edit_submit" value="1">
      <input type="hidden" name="machine_id" value="<?php echo $machine_id; ?>">

      <!-- Machine Identity -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Machine Identity</h3>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Machine Brand / Title <span class="req">*</span></span><input type="text" name="title" class="ih-input" required value="<?php echo esc_attr($m['title']); ?>" placeholder="e.g. Engel Victory 150"></div>
          <div class="ih-cell"><span class="ih-cell-label">Year of Manufacture</span><input type="text" name="year_manufacture" class="ih-input" value="<?php echo esc_attr($m['year_manufacture']); ?>" placeholder="e.g. 2019"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Machine Type</span><select name="machine_type" class="ih-input"><option value="Hydraulic" <?php selected($m['machine_type'],'Hydraulic'); ?>>Hydraulic</option><option value="Electric" <?php selected($m['machine_type'],'Electric'); ?>>Electric</option><option value="Hybrid" <?php selected($m['machine_type'],'Hybrid'); ?>>Hybrid</option></select></div>
          <div class="ih-cell"><span class="ih-cell-label">Number of Identical Machines</span><input type="number" name="identical_count" class="ih-input" min="1" value="<?php echo esc_attr($m['identical_count'] ?: 1); ?>" placeholder="1"></div>
        </div>
      </div>

      <!-- Core Processing Specs -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Core Processing Specs</h3>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Clamping Force (Tons)</span><input type="text" name="clamping_force" class="ih-input" value="<?php echo esc_attr($m['clamping_force']); ?>" placeholder="e.g. 150T"></div>
          <div class="ih-cell"><span class="ih-cell-label">Shot Size (grams / cm³)</span><input type="text" name="shot_size" class="ih-input" value="<?php echo esc_attr($m['shot_size']); ?>" placeholder="e.g. 30g–120g"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Screw Diameter (mm)</span><input type="text" name="screw_diameter" class="ih-input" value="<?php echo esc_attr($m['screw_diameter']); ?>" placeholder="mm"></div>
          <div class="ih-cell"><span class="ih-cell-label">Max Injection Pressure (bar)</span><input type="text" name="max_injection_pressure" class="ih-input" value="<?php echo esc_attr($m['max_injection_pressure']); ?>" placeholder="bar"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Tie Bar Spacing (mm)</span><input type="text" name="tie_bar_spacing" class="ih-input" value="<?php echo esc_attr($m['tie_bar_spacing']); ?>" placeholder="mm"></div>
          <div class="ih-cell"><span class="ih-cell-label">Max Mould Height (mm)</span><input type="text" name="max_mould_height" class="ih-input" value="<?php echo esc_attr($m['max_mould_height']); ?>" placeholder="mm"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Min Mould Height (mm)</span><input type="text" name="min_mould_height" class="ih-input" value="<?php echo esc_attr($m['min_mould_height']); ?>" placeholder="mm"></div>
          <div class="ih-cell"><span class="ih-cell-label">Projected Area (cm²)</span><input type="text" name="projected_area" class="ih-input" value="<?php echo esc_attr($m['projected_area'] ?? ''); ?>" placeholder="e.g. 250"><span class="ih-cell-hint">Total projected area of the reference part — with cavity pressure gives a precise required clamp tonnage.</span></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Cavity Pressure (bar)</span><input type="text" name="cavity_pressure" class="ih-input" value="<?php echo esc_attr($m['cavity_pressure'] ?? ''); ?>" placeholder="e.g. 350"><span class="ih-cell-hint">In-cavity melt pressure (bar). Leave blank to use the tonnage heuristic.</span></div>
          <div class="ih-cell"></div>
        </div>
      </div>

      <!-- Part Capability -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Part Capability</h3>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Max Part Weight (grams)</span><input type="text" name="max_part_weight" class="ih-input" value="<?php echo esc_attr($m['max_part_weight']); ?>" placeholder="e.g. 120g"></div>
          <div class="ih-cell"><span class="ih-cell-label">Max Part Dimensions (L × W × H)</span><input type="text" name="max_part_dimensions" class="ih-input" value="<?php echo esc_attr($m['max_part_dimensions']); ?>" placeholder="e.g. 300 × 200 × 150 mm"></div>
        </div>
        <div class="ih-pill-row">
          <span class="ih-pill-head">Achievable Tolerance</span>
          <label class="ih-pill"><input type="radio" name="tolerance" value="±0.1 mm"  <?php checked($m['tolerance'],'±0.1 mm');  ?>> ±0.1 mm</label>
          <label class="ih-pill"><input type="radio" name="tolerance" value="±0.05 mm" <?php checked($m['tolerance'],'±0.05 mm'); ?>> ±0.05 mm</label>
          <label class="ih-pill"><input type="radio" name="tolerance" value="±0.01 mm" <?php checked($m['tolerance'],'±0.01 mm'); ?>> ±0.01 mm</label>
        </div>
      </div>

      <!-- Materials -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Materials Supported</h3>
        <div class="ih-pill-row">
          <?php
          /* Modern materials[] picker so saving syncs BOTH the CSV column and the legacy
             materials_* booleans (via the shared sanitizer). Pre-check from either source. */
          $ih_csv = ( ! empty( $m['materials'] ) ) ? json_decode( (string) $m['materials'], true ) : array();
          if ( ! is_array( $ih_csv ) ) { $ih_csv = array(); }
          foreach (ih_machine_materials_map() as $mat_col => $mat_def):
            $ih_codes = array_merge( array( $mat_def['code'] ), $mat_def['aliases'] ?? array() );
            $ih_on    = ( ! empty( $m[ $mat_col ] ) ) || ( ! empty( array_intersect( $ih_codes, $ih_csv ) ) );
          ?>
            <label class="ih-pill"><input type="checkbox" name="materials[]" value="<?php echo esc_attr($mat_def['code']); ?>" <?php checked($ih_on, true); ?>> <?php echo esc_html($mat_def['label']); ?></label>
          <?php endforeach; ?>
        </div>
        <div class="ih-row" style="border-top:1px solid #f0f5f2;">
          <div class="ih-cell">
            <div class="ih-toggle-cell">
              <span class="ih-toggle-label">Engineering Grade Materials</span>
              <label class="ih-toggle-switch">
                <input type="hidden" name="engineering_grade" value="No">
                <input type="checkbox" name="engineering_grade" value="Yes" class="ih-toggle-cb" <?php echo ih_checked($m['engineering_grade'] ?? ''); ?>>
                <span class="ih-toggle-slider"></span>
              </label>
            </div>
          </div>
          <div class="ih-cell">
            <div class="ih-toggle-cell">
              <span class="ih-toggle-label">Recycled Materials</span>
              <label class="ih-toggle-switch">
                <input type="hidden" name="recycled_materials" value="No">
                <input type="checkbox" name="recycled_materials" value="Yes" class="ih-toggle-cb" <?php echo ih_checked($m['recycled_materials'] ?? ''); ?>>
                <span class="ih-toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Production Capability -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Production Capability</h3>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Batch Size</span><input type="text" name="batch_size" class="ih-input" value="<?php echo esc_attr($m['batch_size']); ?>" placeholder="Medium (5,000–50,000)"></div>
          <div class="ih-cell"><span class="ih-cell-label">Min Order Qty</span><input type="text" name="min_order_qty" class="ih-input" value="<?php echo esc_attr($m['min_order_qty']); ?>" placeholder="500"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Max Monthly Output</span><input type="text" name="max_monthly_output" class="ih-input" value="<?php echo esc_attr($m['max_monthly_output']); ?>" placeholder="120,000 units"></div>
          <div class="ih-cell"><span class="ih-cell-label">Avg Cycle Time</span><input type="text" name="avg_cycle_time" class="ih-input" value="<?php echo esc_attr($m['avg_cycle_time']); ?>" placeholder="18 seconds"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Operating Hours / day</span><input type="text" name="operating_hours" class="ih-input" value="<?php echo esc_attr($m['operating_hours']); ?>" placeholder="16h/day"></div>
          <div class="ih-cell"><span class="ih-cell-label">Utilization</span><input type="text" name="utilization" class="ih-input" value="<?php echo esc_attr($m['utilization']); ?>" placeholder="65%"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Cavities (per mould)</span><input type="number" name="cavities" class="ih-input" min="1" step="1" value="<?php echo esc_attr($m['cavities'] ?? ''); ?>" placeholder="1"></div>
          <div class="ih-cell"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Location <span class="req">*</span></span><input type="text" name="location" class="ih-input" required value="<?php echo esc_attr($m['location']); ?>" placeholder="Manchester, UK"></div>
          <div class="ih-cell"></div>
        </div>
      </div>

      <!-- Automation -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Automation and Features</h3>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Automation Level</span><input type="text" name="automation_level" class="ih-input" value="<?php echo esc_attr($m['automation_level']); ?>" placeholder="Semi-automated"></div>
          <div class="ih-cell">
            <div class="ih-toggle-cell">
              <span class="ih-toggle-label">Robot Integration</span>
              <label class="ih-toggle-switch">
                <input type="hidden" name="robot_integration" value="No">
                <input type="checkbox" name="robot_integration" value="Yes" class="ih-toggle-cb" <?php echo ih_checked($m['robot_integration'] ?? ''); ?>>
                <span class="ih-toggle-slider"></span>
              </label>
            </div>
          </div>
        </div>
        <div class="ih-row">
          <div class="ih-cell">
            <div class="ih-toggle-cell">
              <span class="ih-toggle-label">Multi-Cavity Support</span>
              <label class="ih-toggle-switch">
                <input type="hidden" name="multi_cavity" value="No">
                <input type="checkbox" name="multi_cavity" value="Yes" class="ih-toggle-cb" <?php echo ih_checked($m['multi_cavity'] ?? ''); ?>>
                <span class="ih-toggle-slider"></span>
              </label>
            </div>
          </div>
          <div class="ih-cell"></div>
        </div>
      </div>

      <!-- Quality -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Quality and Compliance</h3>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Certifications</span><input type="text" name="certifications" class="ih-input" value="<?php echo esc_attr($m['certifications']); ?>" placeholder="ISO 9001"></div>
          <div class="ih-cell"><span class="ih-cell-label">QC Tools</span><input type="text" name="qc_tools" class="ih-input" value="<?php echo esc_attr($m['qc_tools']); ?>" placeholder="CMM, Vision systems"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Tolerance Consistency</span><input type="text" name="tolerance_consistency" class="ih-input" value="<?php echo esc_attr($m['tolerance_consistency']); ?>" placeholder="High"></div>
          <div class="ih-cell"></div>
        </div>
      </div>

      <!-- Advanced Capabilities -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Advanced Capabilities</h3>
        <div class="ih-toggle-row">
          <?php foreach(['overmoulding'=>'Overmoulding','insert_moulding'=>'Insert Moulding','iml'=>'In-Mould Labelling','gas_assisted'=>'Gas-Assisted','thin_wall'=>'Thin-Wall Moulding'] as $fn=>$fl): ?>
          <div class="ih-toggle-cell">
            <span class="ih-toggle-label"><?php echo $fl; ?></span>
            <label class="ih-toggle-switch">
              <input type="hidden" name="<?php echo $fn; ?>" value="No">
              <input type="checkbox" name="<?php echo $fn; ?>" value="Yes" class="ih-toggle-cb" <?php echo ih_checked($m[$fn] ?? ''); ?>>
              <span class="ih-toggle-slider"></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="ih-row" style="border-top:1px solid #f0f5f2;">
          <div class="ih-cell"><span class="ih-cell-label">Listing Date</span><input type="date" name="listing_date" class="ih-input" value="<?php echo esc_attr($m['listing_date']); ?>"></div>
          <div class="ih-cell"><span class="ih-cell-label">Expiry Date</span><input type="date" name="expiry_date" class="ih-input" value="<?php echo esc_attr($m['expiry_date']); ?>"></div>
        </div>
      </div>

      <!-- Current Images (if any) -->
      <?php
      $ih_has_current = false;
      for ($ci=1;$ci<=5;$ci++){ if(!empty($m["image_{$ci}"])){ $ih_has_current = true; break; } }
      ?>
      <?php if ($ih_has_current): ?>
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Current Images</h3>
        <div class="ih-upload-row">
          <?php for($i=1;$i<=5;$i++): if(!empty($m["image_{$i}"])): ?>
          <div class="ih-upload-cell">
            <div class="ih-upload-box has-img" style="background-image:url('<?php echo esc_url($m["image_{$i}"]); ?>');background-size:cover;background-position:center;">
              <span style="display:flex!important;position:absolute;top:7px;left:7px;background:rgba(20,47,50,.75);color:#C8FF00;font-size:9px;font-weight:800;padding:2px 8px;border-radius:20px;font-family:'DM Mono',monospace;letter-spacing:.4px;">IMG <?php echo $i; ?></span>
            </div>
          </div>
          <?php else: ?>
          <div class="ih-upload-cell"><div class="ih-upload-box" style="opacity:.3;cursor:default;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9ab8a8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><span>No image</span></div></div>
          <?php endif; endfor; ?>
        </div>
        <p style="padding:8px 22px 14px;font-size:12px;color:#9ca3af;">Upload new images below to replace existing ones.</p>
      </div>
      <?php endif; ?>

      <!-- Upload Images -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Update Images <span style="font-size:10px;font-weight:400;color:#9ab8a8;text-transform:none;letter-spacing:0;">(optional, up to 5 – replaces existing)</span></h3>
        <div class="ih-upload-row">
          <?php for($i=1;$i<=5;$i++): ?>
          <div class="ih-upload-cell">
            <label id="ih-edit-img-<?php echo $i; ?>" class="ih-upload-box">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b8d0c0" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              <span>Image <?php echo $i; ?></span>
              <input type="file" name="image_<?php echo $i; ?>" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;" onchange="ihPreviewImg(event,'ih-edit-img-<?php echo $i; ?>')">
            </label>
          </div>
          <?php endfor; ?>
        </div>
      </div>

      <div style="padding-bottom:28px;">
        <button type="submit" class="ih-submit-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Save Changes
        </button>
      </div>
    </form>

    <?php endif; ?>

    </div>
  </main>
</div>
</div>

<script>
var IH_KEYWORDS = <?php echo $search_keywords_json; ?>;
var IH_FIELDS   = <?php echo $form_fields_json; ?>;
var _ddIdx=-1, _ddItems=[];

function ihSearchFocus(){ihBuildDropdown('');document.getElementById('ihSearchDropdown').classList.add('open');}
function ihSearchOnInput(val){val=val.trim();var c=document.getElementById('ihSearchClear'),k=document.getElementById('ihSearchKbd');if(c)c.classList.toggle('visible',val.length>0);if(k)k.style.display=val.length>0?'none':'flex';ihBuildDropdown(val);document.getElementById('ihSearchDropdown').classList.add('open');_ddIdx=-1;}
function ihBuildDropdown(q){
    var list=document.getElementById('ihDdList');if(!list)return;list.innerHTML='';_ddItems=[];q=q.toLowerCase();
    var kwM=IH_KEYWORDS.filter(function(k){return!q||k.toLowerCase().includes(q);}).slice(0,q?7:9);
    if(kwM.length){var s=document.createElement('div');s.className='ih-search-dd-section';s.textContent=q?'Matching keywords':'Available keywords';list.appendChild(s);kwM.forEach(function(kw){var it=document.createElement('div');it.className='ih-search-dd-item';it.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>'+ihHL(kw,q);it.addEventListener('mousedown',function(e){e.preventDefault();ihSelectKw(kw);});list.appendChild(it);_ddItems.push(it);});}
    if(q){var fM=IH_FIELDS.filter(function(f){return f.label.toLowerCase().includes(q);}).slice(0,4);if(fM.length){var d=document.createElement('div');d.className='ih-search-dd-section';d.textContent='Jump to field';list.appendChild(d);fM.forEach(function(f){var it=document.createElement('div');it.className='ih-search-dd-item';it.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>'+ihHL(f.label,q);it.addEventListener('mousedown',function(e){e.preventDefault();ihJumpTo(f.field);});list.appendChild(it);_ddItems.push(it);});}}
    if(!_ddItems.length)list.innerHTML='<div style="padding:12px 14px;font-size:12px;color:#9ca3af;">No matches found</div>';
}
function ihHL(text,q){if(!q)return'<span>'+text+'</span>';var re=new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','gi');return'<span>'+text.replace(re,'<mark>$1</mark>')+'</span>';}
function ihSelectKw(kw){var i=document.getElementById('ihSearchInput');if(i)i.value=kw;ihSearchOnInput(kw);ihJumpToInputWithValue(kw);setTimeout(ihCloseDropdown,200);}
function ihJumpTo(fn){var el=document.querySelector('[name="'+fn+'"]');if(el){el.closest('.ih-form-section')?.scrollIntoView({behavior:'smooth',block:'center'});setTimeout(function(){el.focus();el.select&&el.select();},400);}ihCloseDropdown();ihClearSearch();}
function ihJumpToInputWithValue(kw){kw=kw.toLowerCase();var f=Array.from(document.querySelectorAll('.ih-input')).find(function(el){return(el.placeholder&&el.placeholder.toLowerCase().includes(kw))||(el.name&&el.name.toLowerCase().includes(kw));});if(f){f.closest('.ih-form-section')?.scrollIntoView({behavior:'smooth',block:'center'});setTimeout(function(){f.focus();},400);}}
function ihSearchKeyDown(e){var dd=document.getElementById('ihSearchDropdown');if(!dd.classList.contains('open'))return;if(e.key==='ArrowDown'){e.preventDefault();_ddIdx=Math.min(_ddIdx+1,_ddItems.length-1);ihUpdA();}else if(e.key==='ArrowUp'){e.preventDefault();_ddIdx=Math.max(_ddIdx-1,0);ihUpdA();}else if(e.key==='Enter'){e.preventDefault();if(_ddIdx>=0&&_ddItems[_ddIdx])_ddItems[_ddIdx].dispatchEvent(new MouseEvent('mousedown'));}else if(e.key==='Escape'){ihClearSearch();ihCloseDropdown();document.getElementById('ihSearchInput').blur();}}
function ihUpdA(){_ddItems.forEach(function(it,i){it.classList.toggle('active',i===_ddIdx);});}
function ihClearSearch(){var i=document.getElementById('ihSearchInput');if(i)i.value='';var c=document.getElementById('ihSearchClear'),k=document.getElementById('ihSearchKbd');if(c)c.classList.remove('visible');if(k)k.style.display='flex';ihCloseDropdown();}
function ihCloseDropdown(){var dd=document.getElementById('ihSearchDropdown');if(dd)dd.classList.remove('open');_ddIdx=-1;}
document.addEventListener('click',function(e){if(!e.target.closest('#ihSearchOuter'))ihCloseDropdown();if(!e.target.closest('#ihNotifWrap')){var b=document.getElementById('ihNotifBox');if(b)b.style.display='none';}if(!e.target.closest('#ihAccountWrap')){var b=document.getElementById('ihAccountBox');if(b)b.style.display='none';}});
document.addEventListener('keydown',function(e){if((e.metaKey||e.ctrlKey)&&e.key==='k'){e.preventDefault();var i=document.getElementById('ihSearchInput');if(i){i.focus();i.select&&i.select();}}});
function ihToggleNotif(e){e.stopPropagation();var b=document.getElementById('ihNotifBox');b.style.display=b.style.display==='block'?'none':'block';var d=document.getElementById('ihNotifDot');if(d)d.remove();}
function ihToggleAccount(e){e.stopPropagation();var b=document.getElementById('ihAccountBox');b.style.display=b.style.display==='block'?'none':'block';}
function ihPreviewImg(e,id){var file=e.target.files[0];if(!file)return;var label=document.getElementById(id);if(!label)return;var r=new FileReader();r.onload=function(ev){label.style.backgroundImage='url('+ev.target.result+')';label.style.backgroundSize='cover';label.style.backgroundPosition='center';label.querySelectorAll('svg,span').forEach(function(el){el.style.display='none';});label.style.borderColor='#C8FF00';label.style.borderStyle='solid';};r.readAsDataURL(file);}
document.addEventListener('DOMContentLoaded',function(){var p=document.querySelector('.ih-profile-dropdown summary'),s=document.querySelector('.ih-sidebar');if(p&&s)p.addEventListener('click',function(){if(window.innerWidth<=768||!s.classList.contains('expanded'))s.classList.add('expanded');});});
(function(){var btn=document.createElement('button');btn.innerHTML='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>';btn.style.cssText='width:36px;height:36px;border-radius:10px;display:none;align-items:center;justify-content:center;color:#6b7280;cursor:pointer;border:none;background:transparent;flex-shrink:0;';btn.id='ihMobileSearchBtn';document.querySelector('.ih-header-right').insertBefore(btn,document.querySelector('.ih-header-right').firstChild);var bar=document.createElement('div');bar.id='ihMobileSearchBar';bar.style.cssText='display:none;position:fixed;top:0;left:0;right:0;height:52px;background:#fff;border-bottom:1px solid #dde8e2;z-index:9999;align-items:center;padding:0 14px;gap:10px;';bar.innerHTML='<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#7a9e8e" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg><input id="ihMobileSearchInput" type="text" placeholder="Search specs…" style="flex:1;border:none;outline:none;font-size:13px;font-family:inherit;color:#1a2e26;font-weight:500;" oninput="ihSearchOnInput(this.value)"><button onclick="ihClearSearch();document.getElementById(\'ihMobileSearchBar\').style.display=\'none\';" style="border:none;background:none;cursor:pointer;font-size:17px;color:#9ca3af;padding:4px;">✕</button>';document.body.appendChild(bar);btn.addEventListener('click',function(){bar.style.display='flex';setTimeout(function(){document.getElementById('ihMobileSearchInput').focus();},50);});function cw(){btn.style.display=window.innerWidth<=768?'flex':'none';}cw();window.addEventListener('resize',cw);})();
</script>
<?php wp_footer(); ?>
</body>
</html>