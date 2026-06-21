<?php defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( admin_url('admin.php?page=ih-user-dashboard') ) );
    exit;
}
if ( current_user_can('administrator') ) {
    wp_redirect( admin_url('admin.php?page=ih-dashboard') );
    exit;
}

$tool_id = intval( $_GET['tool_id'] ?? 0 );
$user_id  = get_current_user_id();
global $wpdb;

$t = $tool_id ? $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ih_tools WHERE id=%d", $tool_id
), ARRAY_A ) : null;

if ( $t && (int)$t['owner_id'] === 0 ) {
    $wpdb->update( $wpdb->prefix . 'ih_tools', ['owner_id' => $user_id], ['id' => $tool_id] );
    $t['owner_id'] = $user_id;
}
if ( ! $t || (int)$t['owner_id'] !== $user_id ) {
    wp_redirect( admin_url('admin.php?page=ih-user-dashboard') ); exit;
}

$saved    = isset($_GET['saved']) && $_GET['saved'] == '1';
$error    = isset($_GET['ih_error']) ? sanitize_text_field(urldecode($_GET['ih_error'])) : '';
$tool_ref = 'TL-' . str_pad( $tool_id, 5, '0', STR_PAD_LEFT );

$user         = wp_get_current_user();
$email_hash   = md5( strtolower( trim( $user->user_email ) ) );
$gravatar_url = 'https://www.gravatar.com/avatar/' . $email_hash . '?s=72&d=404';
$fallback_url = 'https://ui-avatars.com/api/?name='.rawurlencode($user->display_name ?: 'U').'&background=142F32&color=C8FF00&size=72&bold=true&rounded=true&length=2';
$notifs = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND status='Approved' ORDER BY id DESC LIMIT 10", $user_id
), ARRAY_A ) ?: [];
$notif_count = count($notifs);

$search_keywords = [
    'ABS','Polypropylene (PP)','Polyethylene (PE)','Nylon (PA)','Polycarbonate (PC)',
    'Multi-Cavity','Single Cavity','H13 Steel','P20 Steel','Aluminium',
    'Stripper Plate','Ejector Pin','Hot Runner','Cold Runner',
    'ISO 9001','Medical Grade','Food Grade','Water Cooled',
    'Manchester','London','Birmingham','Leeds','Glasgow',
    'Cycle Time','Annual Volume','Min Order','Clamping Force',
];
$search_keywords_json = json_encode($search_keywords);

$form_fields = [
    ['label'=>'Tool Title',      'field'=>'title'],
    ['label'=>'Part Name',       'field'=>'part_name'],
    ['label'=>'Part Dimensions', 'field'=>'part_dimensions'],
    ['label'=>'Location',        'field'=>'location'],
    ['label'=>'Material Grade',  'field'=>'material_grade'],
    ['label'=>'Mould Type',      'field'=>'mould_type'],
    ['label'=>'Annual Volume',   'field'=>'annual_volume'],
    ['label'=>'Cycle Time',      'field'=>'cycle_time'],
];
$form_fields_json = json_encode($form_fields);

function ih_tool_checked($val){ return ($val === 'Yes') ? 'checked' : ''; }
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Tool — Injection Moulding</title>
<?php wp_head(); ?>
<style>
.ih-banner{background:linear-gradient(135deg,#0d2219 0%,#142F32 55%,#1a3d30 100%);border-radius:16px;padding:28px 30px;color:#fff;margin-bottom:14px;position:relative;overflow:hidden;}
.ih-banner::before{content:'';position:absolute;width:260px;height:260px;background:rgba(200,255,0,.05);border-radius:50%;top:-130px;right:-70px;pointer-events:none;}
.ih-banner-title{font-size:22px;font-weight:800;margin-bottom:6px;position:relative;z-index:2;letter-spacing:-.4px;}
.ih-banner-title span{color:#C8FF00;}
.ih-banner-sub{font-size:13px;opacity:.75;line-height:1.65;margin-bottom:18px;position:relative;z-index:2;max-width:480px;}
.ih-banner-actions{display:flex;gap:9px;flex-wrap:wrap;position:relative;z-index:2;}
.ih-btn-white{background:#fff;color:#142F32;padding:8px 20px;border-radius:50px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:.14s;}
.ih-btn-white:hover{background:#C8FF00;}
.ih-btn-outline-w{background:rgba(200,255,0,.12);color:#C8FF00;border:1px solid rgba(200,255,0,.3);padding:8px 20px;border-radius:50px;font-size:12px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:.14s;}
.ih-btn-outline-w:hover{background:rgba(200,255,0,.22);}
.ih-form-section{background:#fff;border:1px solid #e2eae5;border-radius:14px;margin-bottom:10px;overflow:hidden;}
.ih-form-section-title{display:flex;align-items:center;gap:9px;padding:12px 22px 11px;background:#fafcfb;border-bottom:1px solid #eaf0ec;font-size:10.5px;font-weight:800;color:#142F32;text-transform:uppercase;letter-spacing:.9px;}
.ih-form-section-title::before{content:'';width:3px;height:13px;background:#142F32;border-radius:2px;flex-shrink:0;}
.ih-row{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid #f0f5f2;}
.ih-row:last-child{border-bottom:none;}
.ih-row.cols-3{grid-template-columns:1fr 1fr 1fr;}
.ih-row.cols-4{grid-template-columns:1fr 1fr 1fr 1fr;}
.ih-cell{padding:13px 22px 14px;border-right:1px solid #f0f5f2;display:flex;flex-direction:column;gap:5px;min-width:0;}
.ih-cell:last-child{border-right:none;}
.ih-cell-label{font-size:10px;font-weight:700;color:#9ab8a8;text-transform:uppercase;letter-spacing:.55px;line-height:1;}
.ih-cell-label .req{color:#e05252;}
.ih-input{width:100%;border:none;border-bottom:1.5px solid #d4e2da;border-radius:0;padding:6px 0 8px;font-size:13px;font-family:inherit;font-weight:500;color:#1a2e26;background:transparent;outline:none;-webkit-appearance:none;appearance:none;transition:border-color .15s;}
.ih-input:focus{border-bottom-color:#142F32;}
.ih-input:hover:not(:focus){border-bottom-color:#9fc4b0;}
.ih-input::placeholder{color:#c0d5c8;font-size:12.5px;font-weight:400;}
textarea.ih-input{resize:vertical;min-height:72px;border:1.5px solid #d4e2da;border-radius:6px;padding:8px 10px;}
textarea.ih-input:focus{border-color:#142F32;}
select.ih-input{cursor:pointer;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23142F32' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 2px center;padding-right:18px;background-color:transparent;}
.ih-pill-row{display:flex;flex-wrap:wrap;gap:7px;padding:13px 22px 14px;border-bottom:1px solid #f0f5f2;align-items:center;}
.ih-pill-row:last-child{border-bottom:none;}
.ih-pill-head{font-size:10px;font-weight:700;color:#9ab8a8;text-transform:uppercase;letter-spacing:.55px;margin-right:6px;white-space:nowrap;}
.ih-pill{display:flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;cursor:pointer;color:#3d5a4a;background:#f4f8f5;border:1px solid #d4e2da;padding:5px 12px;border-radius:7px;transition:all .13s;user-select:none;}
.ih-pill:hover{border-color:#142F32;color:#142F32;background:#eaf3ed;}
.ih-pill:has(input:checked){background:#142F32;color:#C8FF00;border-color:#142F32;}
.ih-pill input{width:12px;height:12px;accent-color:#C8FF00;flex-shrink:0;cursor:pointer;}
.ih-toggle-row{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid #f0f5f2;}
.ih-toggle-cell{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-right:1px solid #f0f5f2;gap:16px;transition:background .15s;min-height:62px;}
.ih-toggle-cell:nth-child(3n){border-right:none;}
.ih-toggle-label{font-size:13px;font-weight:600;color:#4a6657;line-height:1.3;}
.ih-toggle-switch{position:relative;display:inline-flex;align-items:center;cursor:pointer;flex-shrink:0;}
.ih-toggle-switch input{display:none;}
.ih-toggle-slider{width:52px;height:28px;background:#c8d9d0;border-radius:50px;transition:background .2s;position:relative;box-shadow:inset 0 1px 3px rgba(0,0,0,.08);}
.ih-toggle-slider::after{content:'';position:absolute;left:3px;top:3px;width:22px;height:22px;border-radius:50%;background:#fff;transition:left .2s cubic-bezier(.4,0,.2,1);box-shadow:0 2px 6px rgba(0,0,0,.22);}
.ih-toggle-cb:checked + .ih-toggle-slider{background:#142F32;}
.ih-toggle-cb:checked + .ih-toggle-slider::after{left:27px;box-shadow:0 2px 6px rgba(20,47,50,.35);}
.ih-toggle-cell:has(.ih-toggle-cb:checked){background:#f0fbf2;}
.ih-toggle-cell:has(.ih-toggle-cb:checked) .ih-toggle-label{color:#142F32;font-weight:700;}
.ih-upload-row{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid #f0f5f2;}
.ih-upload-cell{border-right:1px solid #f0f5f2;padding:18px 22px;}
.ih-upload-cell:last-child{border-right:none;}
.ih-upload-box{border:2px dashed #c0d6c8;border-radius:11px;height:108px;width:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;background:#f8fbf9;position:relative;overflow:hidden;transition:border-color .17s,background .17s;gap:6px;}
.ih-upload-box:hover{border-color:#142F32;background:#eef5f0;}
.ih-upload-box.has-img{border-color:#C8FF00;border-style:solid;}
.ih-upload-box.has-img svg,.ih-upload-box.has-img span{display:none;}
.ih-upload-box span{font-size:11px;font-weight:600;color:#8fb0a0;}
.ih-submit-btn{width:100%;background:#142F32;color:#C8FF00;padding:14px;border:none;border-radius:11px;font-size:14px;font-weight:800;cursor:pointer;font-family:inherit;letter-spacing:.3px;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .14s,transform .1s,box-shadow .14s;box-shadow:0 4px 16px rgba(20,47,50,.2);}
.ih-submit-btn:hover{background:#0d2219;box-shadow:0 6px 22px rgba(20,47,50,.28);transform:translateY(-1px);}
.ih-submit-btn:active{transform:translateY(0);}
.ih-success-box{text-align:center;padding:68px 20px;background:#fff;border-radius:14px;border:1px solid #e2eae5;}
.ih-error-box{background:#fee2e2;color:#dc2626;padding:12px 16px;border-radius:9px;margin-bottom:12px;font-size:13px;font-weight:500;}

@media(max-width:768px){
    .ih-page-header{padding:10px 14px;}
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
}
@media(max-width:480px){
    .ih-toggle-row{grid-template-columns:1fr;}
    .ih-toggle-cell{border-right:none!important;}
    .ih-upload-row{grid-template-columns:1fr;}
    .ih-upload-cell{border-right:none!important;}
    .ih-pill{font-size:11px;padding:4px 10px;}
}
</style>
</head>
<body>
<?php include IH_DIR . 'pages/partials/ih-user-shell-start.php'; ?>

<?php include IH_DIR . 'pages/partials/ih-user-shell-header.php'; ?>

<div class="ih-body">

  <main class="ih-main">
    <div class="ih-page-header">
      <div>
        <div class="ih-page-title">Edit Tool</div>
        <div class="ih-page-sub"><?php echo esc_html($t['title']);?><span class="ih-tool-ref"><?php echo esc_html($tool_ref);?></span></div>
      </div>
      <a href="<?php echo admin_url('admin.php?page=ih-user-dashboard');?>" style="display:inline-flex;align-items:center;gap:6px;background:#f0f7f3;color:#374151;padding:8px 16px;border-radius:20px;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;border:1px solid #d4e2da;">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>My Listings
      </a>
    </div>

    <div class="ih-content">

    <?php if($saved):?>
    <div class="ih-success-box">
      <div style="width:76px;height:76px;background:#C8FF00;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#142F32" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
      <h3 style="font-size:21px;font-weight:800;margin-bottom:8px;color:#142F32;letter-spacing:-.4px;">Tool Updated!</h3>
      <p style="color:#6b7280;margin-bottom:26px;font-size:14px;">Your listing has been updated successfully.</p>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <a href="<?php echo admin_url('admin.php?page=ih-user-edit-tool&tool_id='.$tool_id);?>" style="background:#142F32;color:#C8FF00;padding:10px 24px;border-radius:50px;text-decoration:none;font-size:13px;font-weight:700;">Edit Again</a>
        <a href="<?php echo admin_url('admin.php?page=ih-user-dashboard');?>" style="background:#f0f7f3;color:#374151;padding:10px 24px;border-radius:50px;text-decoration:none;font-size:13px;font-weight:700;border:1px solid #d4e2da;">My Listings</a>
      </div>
    </div>
    <?php else:?>

    <?php if($error):?><div class="ih-error-box"><?php echo esc_html($error);?></div><?php endif;?>

    <div class="ih-banner">
      <div class="ih-banner-title">Edit Tool <span>Listing</span></div>
      <div class="ih-banner-sub">Update your tool details below. Changes will be saved immediately to your listing.</div>
      <div class="ih-banner-actions">
        <a href="<?php echo admin_url('admin.php?page=ih-user-dashboard');?>" class="ih-btn-white"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>My Listings</a>
        <a href="<?php echo admin_url('admin.php?page=ih-user-add-tool');?>" class="ih-btn-outline-w"><img src="<?php echo get_template_directory_uri();?>/assets/images/user-tools.png" alt="" style="width:15px;height:15px;">Add New Tool</a>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="ihEditToolForm" data-ih-nonce-refresh data-ih-nonce-action="ih_user_edit_tool" data-ih-nonce-field="ih_user_nonce">
      <?php wp_nonce_field('ih_user_edit_tool','ih_user_nonce');?>
      <input type="hidden" name="ih_user_tool_edit_submit" value="1">
      <input type="hidden" name="tool_id" value="<?php echo $tool_id;?>">

      <!-- Part Information -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Part Information</h3>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Tool Title <span class="req">*</span></span><input type="text" name="title" class="ih-input" required value="<?php echo esc_attr($t['title']);?>" placeholder="e.g. Medical Device Housing Mould"></div>
          <div class="ih-cell"><span class="ih-cell-label">Part Name</span><input type="text" name="part_name" class="ih-input" value="<?php echo esc_attr($t['part_name']);?>" placeholder="e.g. angle, airbug"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Part Dimensions (L × W × H mm)</span><input type="text" name="part_dimensions" class="ih-input" value="<?php echo esc_attr($t['part_dimensions']);?>" placeholder="e.g. 300 × 200 × 150 mm"></div>
          <div class="ih-cell"><span class="ih-cell-label">Part Weight (grams)</span><input type="text" name="part_weight" class="ih-input" value="<?php echo esc_attr($t['part_weight']);?>" placeholder="e.g. 28g"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Number of Cavities</span><input type="text" name="num_cavities" class="ih-input" value="<?php echo esc_attr($t['num_cavities']);?>" placeholder="Multi-cavity / 4 cavities"></div>
          <div class="ih-cell"><span class="ih-cell-label">Owner</span><input type="text" name="owner_name" class="ih-input" value="<?php echo esc_attr($t['owner_name']);?>" placeholder="Precision Mould Co."></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Projected Area (cm²)</span><input type="text" name="projected_area" class="ih-input" value="<?php echo esc_attr($t['projected_area'] ?? '');?>" placeholder="e.g. 180"><span class="ih-cell-hint">Total projected area of all cavities — with cavity pressure gives a precise required clamp tonnage.</span></div>
          <div class="ih-cell"><span class="ih-cell-label">Cavity Pressure (bar)</span><input type="text" name="cavity_pressure" class="ih-input" value="<?php echo esc_attr($t['cavity_pressure'] ?? '');?>" placeholder="e.g. 350"><span class="ih-cell-hint">In-cavity melt pressure (bar). Leave blank to use the tonnage heuristic.</span></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Location <span class="req">*</span></span><input type="text" name="location" class="ih-input" required value="<?php echo esc_attr($t['location']);?>" placeholder="Manchester, UK"></div>
          <div class="ih-cell"><span class="ih-cell-label">Material Grade</span><input type="text" name="material_grade" class="ih-input" value="<?php echo esc_attr($t['material_grade']);?>" placeholder="Makrolon 2458"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Colour</span><input type="text" name="colour" class="ih-input" value="<?php echo esc_attr($t['colour']);?>" placeholder="Custom (RAL 9003)"></div>
          <div class="ih-cell"></div>
        </div>
        <div style="padding:13px 22px 14px;border-top:1px solid #f0f5f2;">
          <span class="ih-cell-label" style="display:block;margin-bottom:8px;">Part Function / Description</span>
          <textarea name="part_description" class="ih-input" rows="3" placeholder="Brief description of the part…"><?php echo esc_textarea($t['part_description']);?></textarea>
        </div>
      </div>

      <!-- Mould Specifications -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Mould Specifications</h3>
        <div class="ih-row cols-3">
          <div class="ih-cell"><span class="ih-cell-label">Mould Type</span><input type="text" name="mould_type" class="ih-input" value="<?php echo esc_attr($t['mould_type']);?>" placeholder="Multi-Cavity"></div>
          <div class="ih-cell"><span class="ih-cell-label">Mould Material</span><input type="text" name="mould_material" class="ih-input" value="<?php echo esc_attr($t['mould_material']);?>" placeholder="H13 Steel"></div>
          <div class="ih-cell"><span class="ih-cell-label">Condition</span><input type="text" name="mould_condition" class="ih-input" value="<?php echo esc_attr($t['mould_condition']);?>" placeholder="New"></div>
        </div>
        <div class="ih-row cols-3">
          <div class="ih-cell"><span class="ih-cell-label">Number of Cavities (spec)</span><input type="number" name="num_cavities_spec" class="ih-input" value="<?php echo esc_attr($t['num_cavities_spec']?:4);?>" placeholder="4"></div>
          <div class="ih-cell"><span class="ih-cell-label">Ejector Type</span><input type="text" name="ejector_type" class="ih-input" value="<?php echo esc_attr($t['ejector_type']);?>" placeholder="Stripper Plate"></div>
          <div class="ih-cell"><span class="ih-cell-label">Nozzle Type</span><input type="text" name="nozzle_type" class="ih-input" value="<?php echo esc_attr($t['nozzle_type']);?>" placeholder="Pin with shut-off arm"></div>
        </div>
        <div class="ih-row cols-3">
          <div class="ih-cell"><span class="ih-cell-label">Runner Type</span><input type="text" name="runner_type" class="ih-input" value="<?php echo esc_attr($t['runner_type']??'');?>" placeholder="Hot Runner"></div>
          <div class="ih-cell"><span class="ih-cell-label">Gate Type</span><input type="text" name="gate_type" class="ih-input" value="<?php echo esc_attr($t['gate_type']??'');?>" placeholder="Submarine / Tunnel Gate"></div>
          <div class="ih-cell"><span class="ih-cell-label">Clamp &amp; Drive Type</span><input type="text" name="clamp_drive_type" class="ih-input" value="<?php echo esc_attr($t['clamp_drive_type']??'');?>" placeholder="Hydraulic"></div>
        </div>
        <div class="ih-row cols-3">
          <div class="ih-cell"><span class="ih-cell-label">Toggle Clamp Type</span><input type="text" name="toggle_clamp_type" class="ih-input" value="<?php echo esc_attr($t['toggle_clamp_type']??'');?>" placeholder="Double 5-point"></div>
          <div class="ih-cell"><span class="ih-cell-label">Surface Finish</span><input type="text" name="surface_finish" class="ih-input" value="<?php echo esc_attr($t['surface_finish']??'');?>" placeholder="Polished"></div>
          <div class="ih-cell"><span class="ih-cell-label">Mould Weight (kg)</span><input type="text" name="mould_weight" class="ih-input" value="<?php echo esc_attr($t['mould_weight']??'');?>" placeholder="850"></div>
        </div>
        <div class="ih-row cols-3">
          <div class="ih-cell"><span class="ih-cell-label">Mould Dimensions (L × W × H mm)</span><input type="text" name="mould_dimensions" class="ih-input" value="<?php echo esc_attr($t['mould_dimensions']??'');?>" placeholder="600 × 500 × 450"></div>
          <div class="ih-cell"><span class="ih-cell-label">Mould Location</span><input type="text" name="mould_location" class="ih-input" value="<?php echo esc_attr($t['mould_location']??'');?>" placeholder="Warehouse 3, Manchester"></div>
          <div class="ih-cell"><span class="ih-cell-label">Injection Stages</span><input type="text" name="injection_stages" class="ih-input" value="<?php echo esc_attr($t['injection_stages']??'');?>" placeholder="1st Fill → V-P switchover → 2nd Pack &amp; hold"></div>
        </div>
      </div>

      <!-- Production Info -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Production Info</h3>
        <div class="ih-row cols-3">
          <div class="ih-cell"><span class="ih-cell-label">Annual Volume</span><input type="text" name="annual_volume" class="ih-input" value="<?php echo esc_attr($t['annual_volume']);?>" placeholder="50,000+"></div>
          <div class="ih-cell"><span class="ih-cell-label">Cycle Time</span><input type="text" name="cycle_time" class="ih-input" value="<?php echo esc_attr($t['cycle_time']);?>" placeholder="24 seconds"></div>
          <div class="ih-cell"><span class="ih-cell-label">Min Order Qty</span><input type="text" name="min_order_qty" class="ih-input" value="<?php echo esc_attr($t['min_order_qty']);?>" placeholder="5,000"></div>
        </div>
        <div class="ih-row cols-3">
          <div class="ih-cell"><span class="ih-cell-label">Material</span><input type="text" name="material" class="ih-input" value="<?php echo esc_attr($t['material']);?>" placeholder="PC"></div>
          <div class="ih-cell"><span class="ih-cell-label">Clamping Required</span><input type="text" name="clamping_required" class="ih-input" value="<?php echo esc_attr($t['clamping_required']);?>" placeholder="70T"></div>
          <div class="ih-cell"><span class="ih-cell-label">Compatible Specs</span><input type="text" name="compatible_specs" class="ih-input" value="<?php echo esc_attr($t['compatible_specs']);?>" placeholder="70T+ clamping"></div>
        </div>
        <div class="ih-row cols-3">
          <div class="ih-cell"><span class="ih-cell-label">Required Quantity</span><input type="text" name="required_qty" class="ih-input" value="<?php echo esc_attr($t['required_qty']??'');?>" placeholder="100,000 / yr"></div>
          <div class="ih-cell"><span class="ih-cell-label">Packaging Requirements</span><input type="text" name="packaging" class="ih-input" value="<?php echo esc_attr($t['packaging']??'');?>" placeholder="Bagged, 500/carton"></div>
          <div class="ih-cell"><span class="ih-cell-label">Draft Angle</span><input type="text" name="draft_angle" class="ih-input" value="<?php echo esc_attr($t['draft_angle']??'');?>" placeholder="1.5°"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Material Supplied</span>
            <select name="material_supplied" class="ih-input">
              <?php $ms=$t['material_supplied']??''; ?>
              <option value="" <?php selected($ms,'');?>>—</option>
              <option value="Yes — supplied" <?php selected($ms,'Yes — supplied');?>>Yes — supplied</option>
              <option value="No — customer supplies" <?php selected($ms,'No — customer supplies');?>>No — customer supplies</option>
              <?php if($ms!=='' && !in_array($ms,['Yes — supplied','No — customer supplies'],true)):?><option value="<?php echo esc_attr($ms);?>" selected><?php echo esc_html($ms);?></option><?php endif;?>
            </select>
          </div>
          <div class="ih-cell"></div>
        </div>
      </div>

      <!-- Tooling Features -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Tooling Features &amp; Requirements</h3>
        <div class="ih-pill-row">
          <span class="ih-pill-head">Materials</span>
          <label class="ih-pill"><input type="checkbox" name="tolerance_abs" value="1" <?php checked($t['tolerance_abs'],1);?>> ABS</label>
          <label class="ih-pill"><input type="checkbox" name="tolerance_pp"  value="1" <?php checked($t['tolerance_pp'], 1);?>> Polypropylene (PP)</label>
          <label class="ih-pill"><input type="checkbox" name="tolerance_pe"  value="1" <?php checked($t['tolerance_pe'], 1);?>> Polyethylene (PE)</label>
        </div>
        <div class="ih-toggle-row">
          <div class="ih-toggle-cell">
            <span class="ih-toggle-label">Water Cooled Chiller</span>
            <label class="ih-toggle-switch">
              <input type="checkbox" name="water_cooled" value="Yes" class="ih-toggle-cb" <?php echo ih_tool_checked($t['water_cooled']);?>>
              <span class="ih-toggle-slider"></span>
            </label>
          </div>
          <div class="ih-toggle-cell">
            <span class="ih-toggle-label">Suck Pump</span>
            <label class="ih-toggle-switch">
              <input type="checkbox" name="suck_pump" value="Yes" class="ih-toggle-cb" <?php echo ih_tool_checked($t['suck_pump']);?>>
              <span class="ih-toggle-slider"></span>
            </label>
          </div>
          <div class="ih-toggle-cell">
            <span class="ih-toggle-label">Food Grade</span>
            <label class="ih-toggle-switch">
              <input type="checkbox" name="food_grade" value="Yes" class="ih-toggle-cb" <?php echo ih_tool_checked($t['food_grade']);?>>
              <span class="ih-toggle-slider"></span>
            </label>
          </div>
          <div class="ih-toggle-cell">
            <span class="ih-toggle-label">Medical Grade</span>
            <label class="ih-toggle-switch">
              <input type="checkbox" name="medical_grade" value="Yes" class="ih-toggle-cb" <?php echo ih_tool_checked($t['medical_grade']);?>>
              <span class="ih-toggle-slider"></span>
            </label>
          </div>
          <div class="ih-toggle-cell">
            <span class="ih-toggle-label">In-Mould Labelling (IML)</span>
            <label class="ih-toggle-switch">
              <input type="checkbox" name="iml" value="Yes" class="ih-toggle-cb" <?php echo ih_tool_checked($t['iml']??'');?>>
              <span class="ih-toggle-slider"></span>
            </label>
          </div>
          <div class="ih-toggle-cell">
            <span class="ih-toggle-label">Automation / Robot Cell</span>
            <label class="ih-toggle-switch">
              <input type="checkbox" name="automation" value="Yes" class="ih-toggle-cb" <?php echo ih_tool_checked($t['automation']??'');?>>
              <span class="ih-toggle-slider"></span>
            </label>
          </div>
        </div>
        <div class="ih-row cols-3" style="border-top:1px solid #f0f5f2;">
          <div class="ih-cell"><span class="ih-cell-label">Tolerance</span><input type="text" name="tolerance" class="ih-input" value="<?php echo esc_attr($t['tolerance']??'');?>" placeholder="± 0.1 mm"></div>
          <div class="ih-cell"><span class="ih-cell-label">Required Clamp Force / Tonnage (T)</span><input type="text" name="clamp_force" class="ih-input" value="<?php echo esc_attr($t['clamp_force']??'');?>" placeholder="120"></div>
          <div class="ih-cell"><span class="ih-cell-label">Shot Weight (g)</span><input type="text" name="shot_weight" class="ih-input" value="<?php echo esc_attr($t['shot_weight']??'');?>" placeholder="210"></div>
        </div>
        <div class="ih-row cols-3">
          <div class="ih-cell"><span class="ih-cell-label">Tie-Bar Spacing (L × W mm)</span><input type="text" name="tie_bar" class="ih-input" value="<?php echo esc_attr($t['tie_bar']??'');?>" placeholder="460 × 460"></div>
          <div class="ih-cell"><span class="ih-cell-label">Opening Stroke / Daylight (mm)</span><input type="text" name="opening_stroke" class="ih-input" value="<?php echo esc_attr($t['opening_stroke']??'');?>" placeholder="420"></div>
          <div class="ih-cell"><span class="ih-cell-label">Hot Runner Zones</span><input type="text" name="hot_runner_zones" class="ih-input" value="<?php echo esc_attr($t['hot_runner_zones']??'');?>" placeholder="8"></div>
        </div>
        <div class="ih-row">
          <div class="ih-cell"><span class="ih-cell-label">Hot Runner Controller</span>
            <select name="hot_runner_controller" class="ih-input">
              <?php $hrc=$t['hot_runner_controller']??''; ?>
              <option value="" <?php selected($hrc,'');?>>—</option>
              <option value="Required (not included)" <?php selected($hrc,'Required (not included)');?>>Required (not included)</option>
              <option value="Not Required" <?php selected($hrc,'Not Required');?>>Not Required</option>
              <?php if($hrc!=='' && !in_array($hrc,['Required (not included)','Not Required'],true)):?><option value="<?php echo esc_attr($hrc);?>" selected><?php echo esc_html($hrc);?></option><?php endif;?>
            </select>
          </div>
          <div class="ih-cell"></div>
        </div>
        <div class="ih-row" style="border-top:1px solid #f0f5f2;">
          <div class="ih-cell"><span class="ih-cell-label">Listing Date</span><input type="date" name="listing_date" class="ih-input" value="<?php echo esc_attr($t['listing_date']);?>"></div>
          <div class="ih-cell"><span class="ih-cell-label">Expiry Date</span><input type="date" name="expiry_date" class="ih-input" value="<?php echo esc_attr($t['expiry_date']);?>"></div>
        </div>
      </div>

      <!-- Current Images -->
      <?php if(!empty($t['image_1'])||!empty($t['image_2'])||!empty($t['image_3'])):?>
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Current Images</h3>
        <div class="ih-upload-row">
          <?php for($i=1;$i<=3;$i++): if(!empty($t["image_{$i}"])):?>
          <div class="ih-upload-cell">
            <div class="ih-upload-box has-img" style="background-image:url('<?php echo esc_url($t["image_{$i}"]);?>');background-size:cover;background-position:center;">
              <span style="display:flex!important;position:absolute;top:7px;left:7px;background:rgba(20,47,50,.75);color:#C8FF00;font-size:9px;font-weight:800;padding:2px 8px;border-radius:20px;font-family:'DM Mono',monospace;letter-spacing:.4px;">IMG <?php echo $i;?></span>
            </div>
          </div>
          <?php else:?>
          <div class="ih-upload-cell"><div class="ih-upload-box" style="opacity:.3;cursor:default;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9ab8a8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg><span>No image</span></div></div>
          <?php endif; endfor;?>
        </div>
        <p style="padding:8px 22px 14px;font-size:12px;color:#9ca3af;">Upload new images below to replace existing ones.</p>
      </div>
      <?php endif;?>

      <!-- Update Images -->
      <div class="ih-form-section">
        <h3 class="ih-form-section-title">Update Images <span style="font-size:10px;font-weight:400;color:#9ab8a8;text-transform:none;letter-spacing:0;">(optional – replaces existing)</span></h3>
        <div class="ih-upload-row">
          <?php for($i=1;$i<=3;$i++):?>
          <div class="ih-upload-cell">
            <label id="ih-edit-img-<?php echo $i;?>" class="ih-upload-box">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b8d0c0" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              <span>Image <?php echo $i;?></span>
              <input type="file" name="image_<?php echo $i;?>" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;" onchange="ihPreviewImg(event,'ih-edit-img-<?php echo $i;?>')">
            </label>
          </div>
          <?php endfor;?>
        </div>
      </div>

      <div style="padding-bottom:28px;">
        <button type="submit" class="ih-submit-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Save Changes
        </button>
      </div>
    </form>

    <?php endif;?>
    </div>
  </main>
</div>
</div>

<script>
var IH_KEYWORDS=<?php echo $search_keywords_json;?>;
var IH_FIELDS=<?php echo $form_fields_json;?>;
var _ddIdx=-1,_ddItems=[];
function ihSearchFocus(){ihBuildDropdown('');document.getElementById('ihSearchDropdown').classList.add('open');}
function ihSearchOnInput(val){val=val.trim();var c=document.getElementById('ihSearchClear'),k=document.getElementById('ihSearchKbd');if(c)c.classList.toggle('visible',val.length>0);if(k)k.style.display=val.length>0?'none':'flex';ihBuildDropdown(val);document.getElementById('ihSearchDropdown').classList.add('open');_ddIdx=-1;}
function ihBuildDropdown(q){var list=document.getElementById('ihDdList');if(!list)return;list.innerHTML='';_ddItems=[];q=q.toLowerCase();var kwM=IH_KEYWORDS.filter(function(k){return!q||k.toLowerCase().includes(q);}).slice(0,q?7:9);if(kwM.length){var s=document.createElement('div');s.className='ih-search-dd-section';s.textContent=q?'Matching keywords':'Available keywords';list.appendChild(s);kwM.forEach(function(kw){var it=document.createElement('div');it.className='ih-search-dd-item';it.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>'+ihHL(kw,q);it.addEventListener('mousedown',function(e){e.preventDefault();ihSelectKw(kw);});list.appendChild(it);_ddItems.push(it);});}if(q){var fM=IH_FIELDS.filter(function(f){return f.label.toLowerCase().includes(q);}).slice(0,4);if(fM.length){var d=document.createElement('div');d.className='ih-search-dd-section';d.textContent='Jump to field';list.appendChild(d);fM.forEach(function(f){var it=document.createElement('div');it.className='ih-search-dd-item';it.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>'+ihHL(f.label,q);it.addEventListener('mousedown',function(e){e.preventDefault();ihJumpTo(f.field);});list.appendChild(it);_ddItems.push(it);});}}if(!_ddItems.length)list.innerHTML='<div style="padding:12px 14px;font-size:12px;color:#9ca3af;">No matches found</div>';}
function ihHL(text,q){if(!q)return'<span>'+text+'</span>';var re=new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')','gi');return'<span>'+text.replace(re,'<mark>$1</mark>')+'</span>';}
function ihSelectKw(kw){var i=document.getElementById('ihSearchInput');if(i)i.value=kw;ihSearchOnInput(kw);ihJumpToInputWithValue(kw);setTimeout(ihCloseDropdown,200);}
function ihJumpTo(fn){var el=document.querySelector('[name="'+fn+'"]');if(el){el.closest('.ih-form-section')?.scrollIntoView({behavior:'smooth',block:'center'});setTimeout(function(){el.focus();el.select&&el.select();},400);}ihCloseDropdown();ihClearSearch();}
function ihJumpToInputWithValue(kw){kw=kw.toLowerCase();var f=Array.from(document.querySelectorAll('.ih-input')).find(function(el){return(el.placeholder&&el.placeholder.toLowerCase().includes(kw))||(el.name&&el.name.toLowerCase().includes(kw));});if(f){f.closest('.ih-form-section')?.scrollIntoView({behavior:'smooth',block:'center'});setTimeout(function(){f.focus();},400);}}
function ihSearchKeyDown(e){var dd=document.getElementById('ihSearchDropdown');if(!dd.classList.contains('open'))return;if(e.key==='ArrowDown'){e.preventDefault();_ddIdx=Math.min(_ddIdx+1,_ddItems.length-1);ihUpdA();}else if(e.key==='ArrowUp'){e.preventDefault();_ddIdx=Math.max(_ddIdx-1,0);ihUpdA();}else if(e.key==='Enter'){e.preventDefault();if(_ddIdx>=0&&_ddItems[_ddIdx])_ddItems[_ddIdx].dispatchEvent(new MouseEvent('mousedown'));}else if(e.key==='Escape'){ihClearSearch();ihCloseDropdown();document.getElementById('ihSearchInput').blur();}}
function ihUpdA(){_ddItems.forEach(function(it,i){it.classList.toggle('active',i===_ddIdx);});}
function ihClearSearch(){var i=document.getElementById('ihSearchInput');if(i)i.value='';var c=document.getElementById('ihSearchClear'),k=document.getElementById('ihSearchKbd');if(c)c.classList.remove('visible');if(k)k.style.display='flex';ihCloseDropdown();}
function ihCloseDropdown(){var dd=document.getElementById('ihSearchDropdown');if(dd)dd.classList.remove('open');_ddIdx=-1;}
document.addEventListener('click',function(e){if(!e.target.closest('#ihSearchOuter'))ihCloseDropdown();if(!e.target.closest('#ihNotifWrap')){var b=document.getElementById('ihNotifBox');if(b)b.style.display='none';}if(!e.target.closest('#ihAccountWrap')){var b=document.getElementById('ihAccountBox');if(b)b.style.display='none';}});
document.addEventListener('keydown',function(e){if((e.metaKey||e.ctrlKey)&&e.key==='k'){e.preventDefault();var i=document.getElementById('ihSearchInput');if(i){i.focus();i.select&&i.select();}}});function ihToggleNotif(e){e.stopPropagation();var b=document.getElementById('ihNotifBox');b.style.display=b.style.display==='block'?'none':'block';var d=document.getElementById('ihNotifDot');if(d)d.remove();}
function ihToggleAccount(e){e.stopPropagation();var b=document.getElementById('ihAccountBox');b.style.display=b.style.display==='block'?'none':'block';}
function ihPreviewImg(e,id){var file=e.target.files[0];if(!file)return;var label=document.getElementById(id);if(!label)return;var r=new FileReader();r.onload=function(ev){label.style.backgroundImage='url('+ev.target.result+')';label.style.backgroundSize='cover';label.style.backgroundPosition='center';label.querySelectorAll('svg,span').forEach(function(el){el.style.display='none';});label.style.borderColor='#C8FF00';label.style.borderStyle='solid';};r.readAsDataURL(file);}
document.addEventListener('DOMContentLoaded',function(){var p=document.querySelector('.ih-profile-dropdown summary'),s=document.querySelector('.ih-sidebar');if(p&&s)p.addEventListener('click',function(){if(window.innerWidth<=768||!s.classList.contains('expanded'))s.classList.add('expanded');});});
(function(){var btn=document.createElement('button');btn.innerHTML='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>';btn.style.cssText='width:36px;height:36px;border-radius:10px;display:none;align-items:center;justify-content:center;color:#6b7280;cursor:pointer;border:none;background:transparent;flex-shrink:0;';btn.id='ihMobileSearchBtn';document.querySelector('.ih-header-right').insertBefore(btn,document.querySelector('.ih-header-right').firstChild);var bar=document.createElement('div');bar.id='ihMobileSearchBar';bar.style.cssText='display:none;position:fixed;top:0;left:0;right:0;height:52px;background:#fff;border-bottom:1px solid #dde8e2;z-index:9999;align-items:center;padding:0 14px;gap:10px;';bar.innerHTML='<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#7a9e8e" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg><input id="ihMobileSearchInput" type="text" placeholder="Search specs…" style="flex:1;border:none;outline:none;font-size:13px;font-family:inherit;color:#1a2e26;font-weight:500;" oninput="ihSearchOnInput(this.value)"><button onclick="ihClearSearch();document.getElementById(\'ihMobileSearchBar\').style.display=\'none\';" style="border:none;background:none;cursor:pointer;font-size:17px;color:#9ca3af;padding:4px;">✕</button>';document.body.appendChild(bar);btn.addEventListener('click',function(){bar.style.display='flex';setTimeout(function(){document.getElementById('ihMobileSearchInput').focus();},50);});function cw(){btn.style.display=window.innerWidth<=768?'flex':'none';}cw();window.addEventListener('resize',cw);})();
</script>
<?php wp_footer();?>
</body>
</html>