<?php defined( 'ABSPATH' ) || exit;

$machine_id = intval($_GET['machine_id'] ?? 0);
if (!$machine_id) {
    wp_redirect(admin_url('admin.php?page=ih-machines'));
    exit;
}

global $wpdb;
$m = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ih_machines WHERE id=%d", $machine_id), ARRAY_A);
if (!$m) {
    wp_redirect(admin_url('admin.php?page=ih-machines'));
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
        <rect x="10" y="30" width="60" height="36" rx="4" fill="#1a3a2a" opacity=".08"/>
        <rect x="18" y="22" width="44" height="36" rx="4" fill="#2d4a3e"/>
        <circle cx="56" cy="54" r="14" fill="#22c55e"/>
        <path d="M50 54l4 4 8-8" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <h2 style="font-size:20px;font-weight:700;margin:0 0 24px">Machine Updated Successfully</h2>
    <a href="<?php echo admin_url('admin.php?page=ih-machines'); ?>" class="ih-btn ih-btn-primary" style="padding:12px 32px;font-size:14px">
      ✓ Done
    </a>
  </div>
</div>
<?php endif; ?>

<div class="ih-page-header">
  <div style="display:flex;align-items:center;gap:12px">
    <a href="<?php echo admin_url('admin.php?page=ih-machines'); ?>" class="ih-back-btn">‹‹</a>
    <div>
      <h2 class="ih-page-title">Edit Machine</h2>
      <p class="ih-page-sub">Update machine listing details below.</p>
    </div>
  </div>
</div>

<form method="POST" enctype="multipart/form-data" class="ih-form-page" data-ih-nonce-refresh data-ih-nonce-action="ih_edit_machine" data-ih-nonce-field="ih_nonce_field">
  <?php wp_nonce_field('ih_edit_machine','ih_nonce_field'); ?>
  <input type="hidden" name="ih_machine_edit_submit" value="1">
  <input type="hidden" name="machine_id" value="<?php echo $machine_id; ?>">

  <!-- Machine Identity -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Machine Identity</h3>
    <div class="ih-form-grid-2">
      <label class="ih-label">Machine Brand
        <input type="text" name="title" class="ih-input" value="<?php echo esc_attr($m['title']); ?>" placeholder="eg. Engel Victory" required>
      </label>
      <label class="ih-label">Year of Manufacture
        <input type="text" name="year_manufacture" class="ih-input" value="<?php echo esc_attr($m['year_manufacture']); ?>" placeholder="YYYY">
      </label>
      <label class="ih-label">Machine Type
        <select name="machine_type" class="ih-input">
          <option value="Hydraulic" <?php selected($m['machine_type'],'Hydraulic'); ?>>Hydraulic</option>
          <option value="Electric"  <?php selected($m['machine_type'],'Electric');  ?>>Electric</option>
          <option value="Hybrid"    <?php selected($m['machine_type'],'Hybrid');    ?>>Hybrid</option>
        </select>
      </label>
      <label class="ih-label">Number of Identical Machines
        <input type="number" name="identical_count" class="ih-input" value="<?php echo esc_attr($m['identical_count']); ?>" min="1">
      </label>
    </div>
  </div>

  <!-- Core Processing Specs -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Core Processing Specs</h3>
    <div class="ih-form-grid-2">
      <label class="ih-label">Clamping Force (Tons)
        <input type="text" name="clamping_force" class="ih-input" value="<?php echo esc_attr($m['clamping_force']); ?>">
      </label>
      <label class="ih-label">Shot Size (grams / cm³)
        <input type="text" name="shot_size" class="ih-input" value="<?php echo esc_attr($m['shot_size']); ?>">
      </label>
      <label class="ih-label">Screw Diameter (mm)
        <input type="text" name="screw_diameter" class="ih-input" value="<?php echo esc_attr($m['screw_diameter']); ?>">
      </label>
      <label class="ih-label">Max Injection Pressure (bar)
        <input type="text" name="max_injection_pressure" class="ih-input" value="<?php echo esc_attr($m['max_injection_pressure']); ?>">
      </label>
      <label class="ih-label">Tie Bar Spacing (mm)
        <input type="text" name="tie_bar_spacing" class="ih-input" value="<?php echo esc_attr($m['tie_bar_spacing']); ?>">
      </label>
      <label class="ih-label">Max Mould Height (mm)
        <input type="text" name="max_mould_height" class="ih-input" value="<?php echo esc_attr($m['max_mould_height']); ?>">
      </label>
      <label class="ih-label">Min Mould Height (mm)
        <input type="text" name="min_mould_height" class="ih-input" value="<?php echo esc_attr($m['min_mould_height']); ?>">
      </label>
      <label class="ih-label">Projected Area (cm²)
        <input type="text" name="projected_area" class="ih-input" value="<?php echo esc_attr($m['projected_area'] ?? ''); ?>">
        <small class="ih-help">Total projected area of the reference part/mould — combined with cavity pressure for a precise required clamp tonnage.</small>
      </label>
      <label class="ih-label">Cavity Pressure (bar)
        <input type="text" name="cavity_pressure" class="ih-input" value="<?php echo esc_attr($m['cavity_pressure'] ?? ''); ?>">
        <small class="ih-help">Typical in-cavity melt pressure (bar). Leave blank to use the tonnage heuristic.</small>
      </label>
      <label class="ih-label">Opening Stroke (mm)
        <input type="text" name="opening_stroke" class="ih-input" value="<?php echo esc_attr($m['opening_stroke'] ?? ''); ?>">
      </label>
      <label class="ih-label">Clamp Drive Type
        <input type="text" name="clamp_drive_type" class="ih-input" value="<?php echo esc_attr($m['clamp_drive_type'] ?? ''); ?>" placeholder="e.g. Direct hydraulic, Toggle">
      </label>
      <label class="ih-label">Toggle Clamp Type
        <input type="text" name="toggle_clamp_type" class="ih-input" value="<?php echo esc_attr($m['toggle_clamp_type'] ?? ''); ?>" placeholder="e.g. Double toggle">
      </label>
    </div>
  </div>

  <!-- Part Capability -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Part Capability</h3>
    <div class="ih-form-grid-2">
      <label class="ih-label">Max Part Weight (grams)
        <input type="text" name="max_part_weight" class="ih-input" value="<?php echo esc_attr($m['max_part_weight']); ?>">
      </label>
      <label class="ih-label">Max Part Dimensions (L × W × H)
        <input type="text" name="max_part_dimensions" class="ih-input" value="<?php echo esc_attr($m['max_part_dimensions']); ?>">
      </label>
    </div>
    <div class="ih-label" style="margin-top:16px">Achievable Tolerance
      <div class="ih-checkbox-group" style="margin-top:8px">
        <label class="ih-checkbox-label"><input type="radio" name="tolerance" value="±0.1 mm"  <?php checked($m['tolerance'],'±0.1 mm');  ?>> ±0.1 mm</label>
        <label class="ih-checkbox-label"><input type="radio" name="tolerance" value="±0.05 mm" <?php checked($m['tolerance'],'±0.05 mm'); ?>> ±0.05 mm</label>
        <label class="ih-checkbox-label"><input type="radio" name="tolerance" value="±0.01 mm" <?php checked($m['tolerance'],'±0.01 mm'); ?>> ±0.01 mm</label>
      </div>
    </div>
  </div>

  <!-- Materials Supported -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Materials Supported</h3>
    <?php
    /* Modern materials[] picker so the shared helper syncs the CSV column AND the
       legacy materials_* booleans. Pre-check from either source for back-compat. */
    $ih_csv = ( ! empty( $m['materials'] ) ) ? json_decode( (string) $m['materials'], true ) : array();
    if ( ! is_array( $ih_csv ) ) { $ih_csv = array(); }
    ?>
    <div class="ih-label">Select Materials
      <div class="ih-checkbox-group" style="margin-top:8px">
        <?php foreach (ih_machine_materials_map() as $mat_col => $mat_def):
          $ih_codes = array_merge( array( $mat_def['code'] ), $mat_def['aliases'] ?? array() );
          $ih_on    = ( ! empty( $m[ $mat_col ] ) ) || ( ! empty( array_intersect( $ih_codes, $ih_csv ) ) );
        ?>
          <label class="ih-checkbox-label"><input type="checkbox" name="materials[]" value="<?php echo esc_attr($mat_def['code']); ?>" <?php checked($ih_on, true); ?>> <?php echo esc_html($mat_def['label']); ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="ih-form-grid-2" style="margin-top:16px">
      <label class="ih-label">Material Grade
        <input type="text" name="material_grade" class="ih-input" value="<?php echo esc_attr($m['material_grade'] ?? ''); ?>">
      </label>
      <label class="ih-label">Engineering Grade Materials
        <select name="engineering_grade" class="ih-input">
          <option <?php selected($m['engineering_grade'],'Yes'); ?>>Yes</option>
          <option <?php selected($m['engineering_grade'],'No');  ?>>No</option>
        </select>
      </label>
      <label class="ih-label">Recycled Materials Supported
        <select name="recycled_materials" class="ih-input">
          <option <?php selected($m['recycled_materials'],'Yes'); ?>>Yes</option>
          <option <?php selected($m['recycled_materials'],'No');  ?>>No</option>
        </select>
      </label>
    </div>
  </div>

  <!-- Production Capability -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Production Capability</h3>
    <div class="ih-form-grid-4">
      <label class="ih-label">Batch Size
        <input type="text" name="batch_size" class="ih-input" value="<?php echo esc_attr($m['batch_size']); ?>">
      </label>
      <label class="ih-label">Min Order Qty
        <input type="text" name="min_order_qty" class="ih-input" value="<?php echo esc_attr($m['min_order_qty']); ?>">
      </label>
      <label class="ih-label">Max Monthly Output
        <input type="text" name="max_monthly_output" class="ih-input" value="<?php echo esc_attr($m['max_monthly_output']); ?>">
      </label>
      <label class="ih-label">Avg Cycle Time
        <input type="text" name="avg_cycle_time" class="ih-input" value="<?php echo esc_attr($m['avg_cycle_time']); ?>">
      </label>
    </div>
    <div class="ih-form-grid-3" style="margin-top:12px">
      <label class="ih-label">Operating Hours
        <input type="text" name="operating_hours" class="ih-input" value="<?php echo esc_attr($m['operating_hours']); ?>">
      </label>
      <label class="ih-label">Utilization
        <input type="text" name="utilization" class="ih-input" value="<?php echo esc_attr($m['utilization']); ?>">
      </label>
      <label class="ih-label">Location
        <input type="text" name="location" class="ih-input" value="<?php echo esc_attr($m['location']); ?>">
      </label>
      <label class="ih-label">Cavities (per mould)
        <input type="number" name="cavities" class="ih-input" value="<?php echo esc_attr($m['cavities'] ?? ''); ?>" min="1" step="1" placeholder="1">
      </label>
    </div>
  </div>

  <!-- Automation & Features -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Automation &amp; Features</h3>
    <div class="ih-form-grid-3">
      <label class="ih-label">Automation Level
        <input type="text" name="automation_level" class="ih-input" value="<?php echo esc_attr($m['automation_level']); ?>">
      </label>
      <label class="ih-label">Robot Integration
        <select name="robot_integration" class="ih-input">
          <option <?php selected($m['robot_integration'],'Yes'); ?>>Yes</option>
          <option <?php selected($m['robot_integration'],'No');  ?>>No</option>
        </select>
      </label>
      <label class="ih-label">Multi-Cavity Support
        <select name="multi_cavity" class="ih-input">
          <option <?php selected($m['multi_cavity'],'Yes'); ?>>Yes</option>
          <option <?php selected($m['multi_cavity'],'No');  ?>>No</option>
        </select>
      </label>
    </div>
  </div>

  <!-- Quality & Compliance -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Quality &amp; Compliance</h3>
    <div class="ih-form-grid-3">
      <label class="ih-label">Certifications
        <input type="text" name="certifications" class="ih-input" value="<?php echo esc_attr($m['certifications']); ?>">
      </label>
      <label class="ih-label">QC Tools
        <input type="text" name="qc_tools" class="ih-input" value="<?php echo esc_attr($m['qc_tools']); ?>">
      </label>
      <label class="ih-label">Tolerance Consistency
        <input type="text" name="tolerance_consistency" class="ih-input" value="<?php echo esc_attr($m['tolerance_consistency']); ?>">
      </label>
    </div>
  </div>

  <!-- Advanced Capabilities -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Advanced Capabilities</h3>
    <div class="ih-toggle-grid">
      <?php
      $toggles = [
        'overmoulding'   => 'Overmoulding',
        'insert_moulding'=> 'Insert Moulding',
        'iml'            => 'In-Mold Labelling (IML)',
        'gas_assisted'   => 'Gas-Assisted Moulding',
        'thin_wall'      => 'Thin-Wall Moulding',
      ];
      foreach ($toggles as $name => $label) :
        $is_yes = ($m[$name] === 'Yes');
      ?>
      <div class="ih-toggle-item">
        <div class="ih-toggle-label"><?php echo $label; ?></div>
        <label class="ih-toggle-switch">
          <input type="hidden" name="<?php echo $name; ?>" value="No">
          <input type="checkbox" name="<?php echo $name; ?>" value="Yes" class="ih-toggle-cb" <?php checked($is_yes, true); ?>>
          <span class="ih-toggle-slider"></span>
        </label>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="ih-form-grid-2" style="margin-top:20px">
      <label class="ih-label">Listing Date
        <input type="date" name="listing_date" class="ih-input" value="<?php echo esc_attr($m['listing_date']); ?>">
      </label>
      <label class="ih-label">Expiry Date
        <input type="date" name="expiry_date" class="ih-input" value="<?php echo esc_attr($m['expiry_date']); ?>">
      </label>
    </div>
  </div>

  <!-- Current Images -->
  <?php
  $ih_has_current = false;
  for ( $i = 1; $i <= 5; $i++ ) { if ( ! empty( $m["image_{$i}"] ) ) { $ih_has_current = true; break; } }
  ?>
  <?php if ($ih_has_current) : ?>
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Current Images</h3>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <?php for ($i=1;$i<=5;$i++) : ?>
        <?php if (!empty($m["image_{$i}"])) : ?>
          <img src="<?php echo esc_url($m["image_{$i}"]); ?>" style="width:120px;height:90px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
        <?php endif; ?>
      <?php endfor; ?>
    </div>
    <p style="font-size:12px;color:#9ca3af;margin-top:8px;">Upload new images below to replace current ones.</p>
  </div>
  <?php endif; ?>

  <!-- Upload New Images -->
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Update Images (optional, up to 5)</h3>
    <div class="ih-upload-main" id="uploadZone">
      <div class="ih-upload-icon">📷</div>
      <div class="ih-upload-label">Upload Main Image</div>
      <input type="file" name="image_1" accept="image/*" class="ih-file-input">
    </div>
    <div class="ih-upload-row" style="margin-top:12px">
      <?php for ($i=2;$i<=5;$i++) : ?>
      <label class="ih-upload-thumb"><span>+ Image <?php echo (int) $i; ?></span><input type="file" name="image_<?php echo (int) $i; ?>" accept="image/*" class="ih-file-secondary" style="display:none"></label>
      <?php endfor; ?>
    </div>
  </div>

  <div style="text-align:center;padding-bottom:40px">
    <button type="submit" class="ih-btn ih-btn-primary" style="padding:14px 48px;font-size:15px">
      💾 Update Machine
    </button>
  </div>
</form>

<?php if ( current_user_can( 'manage_options' ) ) : ?>
<!-- Admin-only internal notes. Saved by its own admin-gated handler so it NEVER
     triggers owner re-review. Private: shown to admin/owner on the listing, never public. -->
<form method="POST" class="ih-form-page" style="margin-top:18px;">
  <?php wp_nonce_field( 'ih_machine_internal_notes', 'ih_internal_notes_nonce' ); ?>
  <input type="hidden" name="ih_machine_internal_notes_submit" value="1">
  <input type="hidden" name="machine_id" value="<?php echo (int) $machine_id; ?>">
  <div class="ih-form-section">
    <h3 class="ih-form-section-title">Internal Notes (admin only)</h3>
    <div style="padding:16px 22px;">
      <?php if ( isset( $_GET['notes_saved'] ) ) : ?>
        <p style="color:#16a34a;font-size:13px;font-weight:600;margin:0 0 10px;">✓ Internal notes saved.</p>
      <?php endif; ?>
      <label for="ih_internal_notes" style="display:block;font-size:11px;font-weight:700;color:#9ab8a8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Private notes — visible to admin/owner on the listing, never shown publicly</label>
      <textarea id="ih_internal_notes" name="internal_notes" rows="4" class="ih-input" style="resize:vertical;width:100%;"><?php echo esc_textarea( (string) ( $m['internal_notes'] ?? '' ) ); ?></textarea>
    </div>
    <div style="text-align:center;padding:0 0 24px;">
      <button type="submit" class="ih-btn ih-btn-primary" style="padding:12px 40px;font-size:14px;">💾 Save internal notes</button>
    </div>
  </div>
</form>
<?php endif; ?>

<?php
$content = ob_get_clean();
$title   = 'Edit Machine';
include IH_DIR . 'pages/layout.php';