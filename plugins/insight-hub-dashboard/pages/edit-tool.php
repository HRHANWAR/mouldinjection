<?php defined( 'ABSPATH' ) || exit;

$tool_id = intval($_GET['tool_id'] ?? 0);
if (!$tool_id) {
    wp_redirect(admin_url('admin.php?page=ih-tools'));
    exit;
}

global $wpdb;
$t = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ih_tools WHERE id=%d", $tool_id), ARRAY_A);
if (!$t) {
    wp_redirect(admin_url('admin.php?page=ih-tools'));
    exit;
}

$saved = isset($_GET['saved']);
ob_start();
?>

<?php if ($saved) : ?>
<div id="successModal" class="ih-modal-overlay" style="display:flex">
  <div class="ih-modal" style="text-align:center;padding:40px 32px;max-width:420px">
    <div style="width:120px;height:120px;background:#f0f7f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
      <svg viewBox="0 0 80 80" fill="none" width="80" height="80">
        <rect x="10" y="20" width="52" height="48" rx="4" fill="#2d4a3e" opacity=".9"/>
        <path d="M30 44l6 6 14-14" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="56" cy="56" r="14" fill="#22c55e"/>
        <path d="M50 56l4 4 8-8" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <h2 style="font-size:20px;font-weight:700;margin:0 0 24px">Tool Updated Successfully</h2>
    <a href="<?php echo admin_url('admin.php?page=ih-tools'); ?>" class="ih-btn ih-btn-primary" style="padding:12px 32px;font-size:14px">
      ✓ Done
    </a>
  </div>
</div>
<?php endif; ?>

<div class="ih-page-header">
  <div style="display:flex;align-items:center;gap:12px">
    <a href="<?php echo admin_url('admin.php?page=ih-tools'); ?>" class="ih-back-btn">‹‹</a>
    <div>
      <h2 class="ih-page-title">Edit Tool</h2>
      <p class="ih-page-sub">Update tool listing details below.</p>
    </div>
  </div>
</div>

<form method="POST" enctype="multipart/form-data" class="ih-form-page" data-ih-nonce-refresh data-ih-nonce-action="ih_edit_tool" data-ih-nonce-field="ih_nonce_field">
  <?php wp_nonce_field('ih_edit_tool','ih_nonce_field'); ?>
  <input type="hidden" name="ih_tool_edit_submit" value="1">
  <input type="hidden" name="tool_id" value="<?php echo $tool_id; ?>">

  <!-- Part Information -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Part Information</h3>
    <div class="ih-form-grid-2">
      <label class="ih-label">Part Name
        <input type="text" name="part_name" class="ih-input" value="<?php echo esc_attr($t['part_name']); ?>" placeholder="eg. angle, airbug">
      </label>
      <label class="ih-label">Part Dimensions (L × W × H mm)
        <input type="text" name="part_dimensions" class="ih-input" value="<?php echo esc_attr($t['part_dimensions']); ?>" placeholder="mm">
      </label>
    </div>
    <label class="ih-label" style="margin-top:12px">Part Function / Description
      <textarea name="part_description" class="ih-input" rows="3"><?php echo esc_textarea($t['part_description']); ?></textarea>
    </label>
    <div class="ih-form-grid-2" style="margin-top:12px">
      <label class="ih-label">Part Weight (grams)
        <input type="text" name="part_weight" class="ih-input" value="<?php echo esc_attr($t['part_weight']); ?>">
      </label>
      <label class="ih-label">Number of Cavities (if known)
        <input type="text" name="num_cavities" class="ih-input" value="<?php echo esc_attr($t['num_cavities']); ?>">
      </label>
      <label class="ih-label">Projected Area (cm²)
        <input type="text" name="projected_area" class="ih-input" value="<?php echo esc_attr($t['projected_area'] ?? ''); ?>">
        <small class="ih-help">Total projected area of all cavities — with cavity pressure gives a precise required clamp tonnage.</small>
      </label>
      <label class="ih-label">Cavity Pressure (bar)
        <input type="text" name="cavity_pressure" class="ih-input" value="<?php echo esc_attr($t['cavity_pressure'] ?? ''); ?>">
        <small class="ih-help">In-cavity melt pressure (bar). Leave blank to use the tonnage heuristic.</small>
      </label>
      <label class="ih-label">Owner
        <input type="text" name="owner_name" class="ih-input" value="<?php echo esc_attr($t['owner_name']); ?>">
      </label>
      <label class="ih-label">Location
        <input type="text" name="location" class="ih-input" value="<?php echo esc_attr($t['location']); ?>">
      </label>
      <label class="ih-label">Material Grade
        <input type="text" name="material_grade" class="ih-input" value="<?php echo esc_attr($t['material_grade']); ?>">
      </label>
      <label class="ih-label">Colour
        <input type="text" name="colour" class="ih-input" value="<?php echo esc_attr($t['colour']); ?>">
      </label>
    </div>
  </div>

  <!-- Mould Specifications -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Mould Specifications</h3>
    <div class="ih-form-grid-3">
      <label class="ih-label">Mould Type
        <input type="text" name="mould_type" class="ih-input" value="<?php echo esc_attr($t['mould_type']); ?>">
      </label>
      <label class="ih-label">Mould Material
        <input type="text" name="mould_material" class="ih-input" value="<?php echo esc_attr($t['mould_material']); ?>">
      </label>
      <label class="ih-label">Condition
        <input type="text" name="mould_condition" class="ih-input" value="<?php echo esc_attr($t['mould_condition']); ?>">
      </label>
      <label class="ih-label">Number of Cavities
        <input type="number" name="num_cavities_spec" class="ih-input" value="<?php echo esc_attr($t['num_cavities_spec']); ?>">
      </label>
      <label class="ih-label">Ejector Type
        <input type="text" name="ejector_type" class="ih-input" value="<?php echo esc_attr($t['ejector_type']); ?>">
      </label>
      <label class="ih-label">Nozzle Type
        <input type="text" name="nozzle_type" class="ih-input" value="<?php echo esc_attr($t['nozzle_type']); ?>">
      </label>
      <label class="ih-label">Runner Type
        <input type="text" name="runner_type" class="ih-input" value="<?php echo esc_attr($t['runner_type'] ?? ''); ?>" placeholder="Hot Runner">
      </label>
      <label class="ih-label">Gate Type
        <input type="text" name="gate_type" class="ih-input" value="<?php echo esc_attr($t['gate_type'] ?? ''); ?>" placeholder="Submarine / Tunnel Gate">
      </label>
      <label class="ih-label">Clamp &amp; Drive Type
        <input type="text" name="clamp_drive_type" class="ih-input" value="<?php echo esc_attr($t['clamp_drive_type'] ?? ''); ?>" placeholder="Hydraulic">
      </label>
      <label class="ih-label">Toggle Clamp Type
        <input type="text" name="toggle_clamp_type" class="ih-input" value="<?php echo esc_attr($t['toggle_clamp_type'] ?? ''); ?>" placeholder="Double 5-point">
      </label>
      <label class="ih-label">Surface Finish
        <input type="text" name="surface_finish" class="ih-input" value="<?php echo esc_attr($t['surface_finish'] ?? ''); ?>" placeholder="Polished">
      </label>
      <label class="ih-label">Mould Weight (kg)
        <input type="text" name="mould_weight" class="ih-input" value="<?php echo esc_attr($t['mould_weight'] ?? ''); ?>" placeholder="850">
      </label>
      <label class="ih-label">Mould Dimensions (L × W × H mm)
        <input type="text" name="mould_dimensions" class="ih-input" value="<?php echo esc_attr($t['mould_dimensions'] ?? ''); ?>" placeholder="600 × 500 × 450">
      </label>
      <label class="ih-label">Mould Location
        <input type="text" name="mould_location" class="ih-input" value="<?php echo esc_attr($t['mould_location'] ?? ''); ?>" placeholder="Warehouse 3, Manchester">
      </label>
      <label class="ih-label">Injection Stages
        <input type="text" name="injection_stages" class="ih-input" value="<?php echo esc_attr($t['injection_stages'] ?? ''); ?>" placeholder="1st Fill → V-P switchover → 2nd Pack &amp; hold">
      </label>
    </div>
  </div>

  <!-- Production Info -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Production Info</h3>
    <div class="ih-form-grid-3">
      <label class="ih-label">Annual Volume
        <input type="text" name="annual_volume" class="ih-input" value="<?php echo esc_attr($t['annual_volume']); ?>">
      </label>
      <label class="ih-label">Cycle Time
        <input type="text" name="cycle_time" class="ih-input" value="<?php echo esc_attr($t['cycle_time']); ?>">
      </label>
      <label class="ih-label">Min Order Qty
        <input type="text" name="min_order_qty" class="ih-input" value="<?php echo esc_attr($t['min_order_qty']); ?>">
      </label>
      <label class="ih-label">Material
        <input type="text" name="material" class="ih-input" value="<?php echo esc_attr($t['material']); ?>">
      </label>
      <label class="ih-label">Clamping Required
        <input type="text" name="clamping_required" class="ih-input" value="<?php echo esc_attr($t['clamping_required']); ?>">
      </label>
      <label class="ih-label">Compatible Specs
        <input type="text" name="compatible_specs" class="ih-input" value="<?php echo esc_attr($t['compatible_specs']); ?>">
      </label>
      <label class="ih-label">Required Quantity
        <input type="text" name="required_qty" class="ih-input" value="<?php echo esc_attr($t['required_qty'] ?? ''); ?>" placeholder="100,000 / yr">
      </label>
      <label class="ih-label">Packaging Requirements
        <input type="text" name="packaging" class="ih-input" value="<?php echo esc_attr($t['packaging'] ?? ''); ?>" placeholder="Bagged, 500/carton">
      </label>
      <label class="ih-label">Draft Angle
        <input type="text" name="draft_angle" class="ih-input" value="<?php echo esc_attr($t['draft_angle'] ?? ''); ?>" placeholder="1.5°">
      </label>
      <label class="ih-label">Material Supplied
        <?php $ms = $t['material_supplied'] ?? ''; ?>
        <select name="material_supplied" class="ih-input">
          <option value="" <?php selected($ms,''); ?>>—</option>
          <option value="Yes — supplied" <?php selected($ms,'Yes — supplied'); ?>>Yes — supplied</option>
          <option value="No — customer supplies" <?php selected($ms,'No — customer supplies'); ?>>No — customer supplies</option>
          <?php if ($ms !== '' && !in_array($ms,['Yes — supplied','No — customer supplies'],true)) : ?><option value="<?php echo esc_attr($ms); ?>" selected><?php echo esc_html($ms); ?></option><?php endif; ?>
        </select>
      </label>
    </div>
  </div>

  <!-- Tooling Features -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Tooling Features &amp; Requirements</h3>
    <div class="ih-label" style="margin-bottom:12px">Achievable Tolerance
      <div class="ih-checkbox-group" style="margin-top:8px">
        <label class="ih-checkbox-label"><input type="checkbox" name="tolerance_abs" value="1" <?php checked($t['tolerance_abs'],1); ?>> ABS</label>
        <label class="ih-checkbox-label"><input type="checkbox" name="tolerance_pp"  value="1" <?php checked($t['tolerance_pp'], 1); ?>> Polypropylene (PP)</label>
        <label class="ih-checkbox-label"><input type="checkbox" name="tolerance_pe"  value="1" <?php checked($t['tolerance_pe'], 1); ?>> Polyethylene (PE)</label>
      </div>
    </div>
    <div class="ih-form-grid-4">
      <label class="ih-label">Water Cooled Chiller
        <select name="water_cooled" class="ih-input">
          <option <?php selected($t['water_cooled'],'Yes'); ?>>Yes</option>
          <option <?php selected($t['water_cooled'],'No');  ?>>No</option>
        </select>
      </label>
      <label class="ih-label">Suck Pump
        <select name="suck_pump" class="ih-input">
          <option <?php selected($t['suck_pump'],'No');  ?>>No</option>
          <option <?php selected($t['suck_pump'],'Yes'); ?>>Yes</option>
        </select>
      </label>
      <label class="ih-label">Food Grade
        <select name="food_grade" class="ih-input">
          <option <?php selected($t['food_grade'],'No');  ?>>No</option>
          <option <?php selected($t['food_grade'],'Yes'); ?>>Yes</option>
        </select>
      </label>
      <label class="ih-label">Medical Grade
        <select name="medical_grade" class="ih-input">
          <option <?php selected($t['medical_grade'],'Yes'); ?>>Yes</option>
          <option <?php selected($t['medical_grade'],'No');  ?>>No</option>
        </select>
      </label>
      <label class="ih-label">In-Mould Labelling (IML)
        <select name="iml" class="ih-input">
          <option <?php selected($t['iml'] ?? 'No','No');  ?>>No</option>
          <option <?php selected($t['iml'] ?? '','Yes'); ?>>Yes</option>
        </select>
      </label>
      <label class="ih-label">Automation / Robot Cell
        <select name="automation" class="ih-input">
          <option <?php selected($t['automation'] ?? 'No','No');  ?>>No</option>
          <option <?php selected($t['automation'] ?? '','Yes'); ?>>Yes</option>
        </select>
      </label>
    </div>
    <div class="ih-form-grid-3" style="margin-top:16px">
      <label class="ih-label">Tolerance
        <input type="text" name="tolerance" class="ih-input" value="<?php echo esc_attr($t['tolerance'] ?? ''); ?>" placeholder="± 0.1 mm">
      </label>
      <label class="ih-label">Required Clamp Force / Tonnage (T)
        <input type="text" name="clamp_force" class="ih-input" value="<?php echo esc_attr($t['clamp_force'] ?? ''); ?>" placeholder="120">
      </label>
      <label class="ih-label">Shot Weight (g)
        <input type="text" name="shot_weight" class="ih-input" value="<?php echo esc_attr($t['shot_weight'] ?? ''); ?>" placeholder="210">
      </label>
      <label class="ih-label">Tie-Bar Spacing (L × W mm)
        <input type="text" name="tie_bar" class="ih-input" value="<?php echo esc_attr($t['tie_bar'] ?? ''); ?>" placeholder="460 × 460">
      </label>
      <label class="ih-label">Opening Stroke / Daylight (mm)
        <input type="text" name="opening_stroke" class="ih-input" value="<?php echo esc_attr($t['opening_stroke'] ?? ''); ?>" placeholder="420">
      </label>
      <label class="ih-label">Hot Runner Zones
        <input type="text" name="hot_runner_zones" class="ih-input" value="<?php echo esc_attr($t['hot_runner_zones'] ?? ''); ?>" placeholder="8">
      </label>
      <label class="ih-label">Hot Runner Controller
        <?php $hrc = $t['hot_runner_controller'] ?? ''; ?>
        <select name="hot_runner_controller" class="ih-input">
          <option value="" <?php selected($hrc,''); ?>>—</option>
          <option value="Required (not included)" <?php selected($hrc,'Required (not included)'); ?>>Required (not included)</option>
          <option value="Not Required" <?php selected($hrc,'Not Required'); ?>>Not Required</option>
          <?php if ($hrc !== '' && !in_array($hrc,['Required (not included)','Not Required'],true)) : ?><option value="<?php echo esc_attr($hrc); ?>" selected><?php echo esc_html($hrc); ?></option><?php endif; ?>
        </select>
      </label>
    </div>
    <div class="ih-form-grid-2" style="margin-top:16px">
      <label class="ih-label">Listing Date
        <input type="date" name="listing_date" class="ih-input" value="<?php echo esc_attr($t['listing_date']); ?>">
      </label>
      <label class="ih-label">Expiry Date
        <input type="date" name="expiry_date" class="ih-input" value="<?php echo esc_attr($t['expiry_date']); ?>">
      </label>
    </div>
  </div>

  <!-- Current Images -->
  <?php if (!empty($t['image_1']) || !empty($t['image_2']) || !empty($t['image_3'])) : ?>
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Current Images</h3>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <?php for ($i=1;$i<=3;$i++) : ?>
        <?php if (!empty($t["image_{$i}"])) : ?>
          <img src="<?php echo esc_url($t["image_{$i}"]); ?>" style="width:120px;height:90px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
        <?php endif; ?>
      <?php endfor; ?>
    </div>
    <p style="font-size:12px;color:#9ca3af;margin-top:8px;">Upload new images below to replace current ones.</p>
  </div>
  <?php endif; ?>

  <!-- Upload New Images -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Update Images (optional)</h3>
    <div class="ih-upload-main">
      <div class="ih-upload-icon">📷</div>
      <div class="ih-upload-label">Upload Main Image</div>
      <input type="file" name="image_1" accept="image/*" class="ih-file-input">
    </div>
    <div class="ih-upload-row" style="margin-top:12px">
      <label class="ih-upload-thumb"><span>+ Upload</span><input type="file" name="image_2" accept="image/*" class="ih-file-secondary" style="display:none"></label>
      <label class="ih-upload-thumb"><span>+ Upload</span><input type="file" name="image_3" accept="image/*" class="ih-file-secondary" style="display:none"></label>
    </div>
  </div>

  <div style="text-align:center;padding-bottom:40px">
    <button type="submit" class="ih-btn ih-btn-primary" style="padding:14px 48px;font-size:15px">
      💾 Update Tool
    </button>
  </div>
</form>

<?php
$content = ob_get_clean();
$title   = 'Edit Tool';
include IH_DIR . 'pages/layout.php';
