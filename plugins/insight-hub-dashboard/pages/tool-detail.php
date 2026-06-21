<?php defined( 'ABSPATH' ) || exit;

// ── Get tool ID from URL
$tool_id = intval( $_GET['tool_id'] ?? 0 );
$tool    = $tool_id ? ih_db_tool( $tool_id ) : null;

// ── Fallback demo data if no tool found
if ( ! $tool ) {
    $tool = [
        'id'               => 0,
        'title'            => 'Medical Device Housing Mould',
        'part_name'        => 'Diagnostic Enclosure',
        'part_description' => 'High-precision housing for a handheld medical diagnostic device requiring tight tolerances and medical-grade materials.',
        'listing_date'     => '2026-03-12',
        'expiry_date'      => '2026-03-12',
        'num_cavities_spec'=> 4,
        'part_weight'      => '28',
        'material'         => 'PC',
        'clamping_required'=> '70T',
        'medical_grade'    => 'Yes',
        'food_grade'       => 'No',
        'water_cooled'     => 'Yes',
        'suck_pump'        => 'No',
        'location'         => 'Stored at supplier',
        'owner_name'       => 'Precision Mould Co.',
        'mould_type'       => 'Multi-Cavity',
        'mould_material'   => 'H13 Steel',
        'mould_condition'  => 'New',
        'num_cavities'     => '4',
        'ejector_type'     => 'Stripper Plate',
        'nozzle_type'      => 'Pin with shut-off arm',
        'part_dimensions'  => '120 × 80 × 35 mm',
        'material_grade'   => 'Makrolon 2458',
        'colour'           => 'Custom (RAL 9003 Signal White)',
        'annual_volume'    => '50,000+',
        'cycle_time'       => '24 seconds',
        'min_order_qty'    => '5,000',
        'compatible_specs' => '70T+ clamping, 30mm+ screw',
        'tolerance_abs'    => 1,
        'tolerance_pp'     => 1,
        'tolerance_pe'     => 1,
        'image_1'          => 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?w=700&q=80',
        'image_2'          => 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?w=200&q=80',
        'image_3'          => 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?w=200&q=80',
        'available'        => 1,
        'owner_id'         => 0,
    ];
}

// ── Owner info
$owner_user = $tool['owner_id'] ? get_userdata( intval($tool['owner_id']) ) : null;
$owner_name = $owner_user ? $owner_user->display_name : ( $tool['owner_name'] ?: 'Precision Mould Co.' );
$owner_avatar = $owner_user ? get_avatar_url($owner_user->ID,['size'=>40]) : 'https://ui-avatars.com/api/?name=O&size=80&background=1f3d2e&color=c8e88e&bold=true&rounded=true';

// ── Related tools (same page listing, max 3)
$related_tools = array_filter( ih_db_tools(6), fn($t) => intval($t['id']) !== intval($tool['id']) );
$related_tools = array_slice( array_values($related_tools), 0, 3 );

// ── Format dates
$fmt_listing = ! empty($tool['listing_date']) ? date('d M Y', strtotime($tool['listing_date'])) : '—';
$fmt_expiry  = ! empty($tool['expiry_date'])  ? date('d M Y', strtotime($tool['expiry_date']))  : '—';

// ── Tolerance materials
$tol_pills = [];
if ($tool['tolerance_pp'])  $tol_pills[] = 'PP';
if ($tool['tolerance_abs']) $tol_pills[] = 'ABS';
if ($tool['tolerance_pe'])  $tol_pills[] = 'PE';

$nonce = wp_create_nonce( 'ih_nonce' );
$listing_status_meta = function_exists( 'ih_listing_status_meta' ) ? ih_listing_status_meta( $tool ) : array( 'key' => ! empty( $tool['available'] ) ? 'available' : 'pending', 'label' => ! empty( $tool['available'] ) ? 'Available Now' : 'Pending Review', 'class' => ! empty( $tool['available'] ) ? 'is-available' : 'is-pending' );
$listing_statuses    = function_exists( 'ih_listing_statuses' ) ? ih_listing_statuses() : array();
$can_manage_listing_status = current_user_can( 'manage_options' ) && ! empty( $tool['id'] );

ob_start();
?>

<style>
/* ── Tool Detail Page Styles ── */
.ih-detail-breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: var(--ih-muted);
    margin-bottom: 20px;
}
.ih-detail-breadcrumb a { color: var(--ih-muted); text-decoration: none; }
.ih-detail-breadcrumb a:hover { color: var(--ih-primary); }
.ih-detail-breadcrumb span { color: var(--ih-primary); font-weight: 500; }
.ih-detail-breadcrumb svg { width:14px;height:14px; }

.ih-detail-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
    margin-bottom: 28px;
}
@media(max-width:900px){ .ih-detail-wrap { grid-template-columns: 1fr; } }

/* Left — Images */
.ih-detail-images {}
.ih-detail-main-img {
    width: 100%; height: 340px; object-fit: cover;
    border-radius: 14px; display: block;
    background: var(--ih-secondary);
}
.ih-detail-thumbs {
    display: flex; gap: 10px; margin-top: 12px;
}
.ih-detail-thumb {
    width: 110px; height: 76px; object-fit: cover;
    border-radius: 10px; cursor: pointer;
    border: 2px solid transparent;
    transition: border-color .2s;
    flex-shrink: 0;
}
.ih-detail-thumb.active,
.ih-detail-thumb:hover { border-color: var(--ih-primary); }
.ih-detail-thumb-placeholder {
    width: 110px; height: 76px; border-radius: 10px;
    background: var(--ih-secondary); border: 2px dashed var(--ih-border);
    display: flex; align-items: center; justify-content: center;
    color: var(--ih-muted); font-size: 11px;
}

/* Right — Info */
.ih-detail-info {}

.ih-detail-meta-top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 10px;
}
.ih-detail-listed-by {
    display: flex; align-items: center; gap: 8px;
}
.ih-detail-listed-by img { width: 32px; height: 32px; border-radius: 50%; }
.ih-detail-listed-by-text { font-size: 11px; color: var(--ih-muted); }
.ih-detail-listed-by-name { font-size: 13px; font-weight: 600; }

.ih-detail-badges { display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap; }
.ih-detail-badge-avail {
    background: var(--ih-accent); color: var(--ih-accent-fg);
    font-size: 11.5px; font-weight: 600;
    padding: 5px 14px; border-radius: 999px;
}
.ih-listing-status-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:999px;font-size:11.5px;font-weight:800;padding:5px 14px;border:1px solid transparent;}
.ih-listing-status-badge.is-available{background:#dcfce7;color:#15803d;border-color:#bbf7d0;}
.ih-listing-status-badge.is-pending{background:#fef9c3;color:#a16207;border-color:#fde68a;}
.ih-listing-status-badge.is-completed{background:#dbeafe;color:#1d4ed8;border-color:#bfdbfe;}
.ih-listing-status-badge.is-rejected{background:#fee2e2;color:#b91c1c;border-color:#fecaca;}
.ih-listing-status-panel{margin:0 0 14px;padding:12px;border:1px solid var(--ih-border);border-radius:14px;background:#f8fbff;}
.ih-listing-status-panel strong{display:block;margin-bottom:8px;color:var(--ih-primary);font-size:12px;text-transform:uppercase;letter-spacing:.06em;}
.ih-listing-status-actions{display:flex;gap:8px;flex-wrap:wrap;}
.ih-listing-status-btn{border:1px solid var(--ih-border);border-radius:999px;background:#fff;color:#334155;padding:7px 11px;font-size:12px;font-weight:700;cursor:pointer;transition:background .16s,border-color .16s,color .16s,transform .16s;}
.ih-listing-status-btn:hover{transform:translateY(-1px);border-color:#9bb7d0;background:#eef7ff;}
.ih-listing-status-btn.active{background:#164b3f;border-color:#164b3f;color:#fff;}
.ih-listing-status-note{display:block;margin-top:8px;color:#6b7280;font-size:11.5px;}
.ih-detail-badge-type {
    background: var(--ih-primary); color: #fff;
    font-size: 11.5px; font-weight: 600;
    padding: 5px 14px; border-radius: 999px;
}

.ih-detail-title {
    font-size: 22px; font-weight: 800;
    color: var(--ih-primary); margin: 0 0 4px;
    line-height: 1.2;
}
.ih-detail-subtitle {
    font-size: 13.5px; color: var(--ih-muted);
    margin: 0 0 8px;
}
.ih-detail-description {
    font-size: 13px; color: var(--ih-muted);
    line-height: 1.6; margin: 0 0 16px;
}

/* Dates row */
.ih-detail-dates {
    display: grid; grid-template-columns: 1fr 1fr;
    border: 1px solid var(--ih-border);
    border-radius: 10px; overflow: hidden;
    margin-bottom: 14px;
}
.ih-detail-date-cell {
    padding: 10px 14px;
    border-right: 1px solid var(--ih-border);
}
.ih-detail-date-cell:last-child { border-right: none; }
.ih-detail-date-label { font-size: 10.5px; color: var(--ih-muted); margin-bottom: 2px; }
.ih-detail-date-val   { font-size: 13px; font-weight: 700; }

/* Spec grid (green boxes) */
.ih-detail-spec-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 10px; margin-bottom: 14px;
}
.ih-detail-spec-box {
    background: rgba(200,232,142,.2);
    border: 1px solid rgba(200,232,142,.5);
    border-radius: 10px; padding: 10px 14px;
    display: flex; align-items: center; gap: 10px;
}
.ih-detail-spec-box svg { width: 18px; height: 18px; color: var(--ih-primary); flex-shrink:0; }
.ih-detail-spec-label { font-size: 10.5px; color: var(--ih-muted); }
.ih-detail-spec-val   { font-size: 13.5px; font-weight: 700; }

/* Tag pill */
.ih-detail-grade-pill {
    display: inline-flex; align-items: center;
    background: var(--ih-secondary);
    border: 1px solid var(--ih-border);
    color: var(--ih-primary);
    font-size: 11.5px; font-weight: 500;
    padding: 4px 12px; border-radius: 6px;
    margin-bottom: 12px;
}

/* Location / owner */
.ih-detail-location-row {
    display: flex; flex-direction: column; gap: 5px;
    margin-bottom: 18px; font-size: 13px; color: var(--ih-muted);
}
.ih-detail-location-row > div { display: flex; align-items: center; gap: 6px; }
.ih-detail-location-row svg { width: 14px; height: 14px; flex-shrink:0; }
.ih-detail-location-row strong { color: var(--ih-primary); }

/* Actions */
.ih-detail-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.ih-btn-download {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 22px; border-radius: 999px;
    font-size: 13.5px; font-weight: 600;
    background: var(--ih-accent); color: var(--ih-accent-fg);
    border: none; cursor: pointer; text-decoration: none;
    transition: opacity .15s;
}
.ih-btn-download:hover { opacity: .85; }

/* 3-dot menu (top right of info panel) */
.ih-detail-menu { position: relative; }
.ih-detail-menu-btn {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--ih-secondary); border: 1px solid var(--ih-border);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 18px; color: #555;
    transition: background .15s;
}
.ih-detail-menu-btn:hover { background: var(--ih-border); }

/* ── Full Specs section ── */
.ih-specs-section { margin-bottom: 28px; }
.ih-specs-section-title {
    font-size: 20px; font-weight: 800;
    color: var(--ih-primary); margin: 0 0 16px;
}
.ih-specs-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
}
@media(max-width:700px){ .ih-specs-grid { grid-template-columns: 1fr; } }

.ih-specs-card {
    background: var(--ih-card);
    border: 1px solid var(--ih-border);
    border-radius: 14px; padding: 22px;
    box-shadow: var(--ih-shadow);
}
.ih-specs-card-title {
    font-size: 15px; font-weight: 700;
    color: var(--ih-primary); margin: 0 0 16px;
}
.ih-specs-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0; border-bottom: 1px solid var(--ih-border);
    font-size: 13px;
    gap: 12px;
}
.ih-specs-row:last-child { border-bottom: none; padding-bottom: 0; }
.ih-specs-row-label { color: var(--ih-muted); flex-shrink: 0; }
.ih-specs-row-val   { font-weight: 600; text-align: right; }

/* Tooling pills */
.ih-tol-pills { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
.ih-tol-pill {
    background: var(--ih-accent); color: var(--ih-accent-fg);
    font-size: 11.5px; font-weight: 700;
    padding: 4px 14px; border-radius: 999px;
}

/* Yes / No badge */
.ih-yn-yes { color: #16a34a; display: flex; align-items: center; gap: 4px; font-weight: 600; }
.ih-yn-no  { color: #dc2626; display: flex; align-items: center; gap: 4px; font-weight: 600; }

/* ── Related tools ── */
.ih-related-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px;
}
.ih-related-title { font-size: 18px; font-weight: 800; color: var(--ih-primary); margin: 0; }
</style>

<!-- Breadcrumb -->
<div class="ih-detail-breadcrumb">
  <a href="<?php echo admin_url('admin.php?page=ih-tools'); ?>">Tools</a>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
  <span><?php echo esc_html($tool['title']); ?></span>
</div>

<!-- Top section: image + info -->
<div class="ih-detail-wrap">

  <!-- Left: Images -->
  <div class="ih-detail-images">
    <img id="mainToolImg"
         src="<?php echo esc_url($tool['image_1'] ?: 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?w=700&q=80'); ?>"
         alt="<?php echo esc_attr($tool['title']); ?>"
         class="ih-detail-main-img">
    <div class="ih-detail-thumbs">
      <?php
      $imgs = array_filter([$tool['image_1']??'', $tool['image_2']??'', $tool['image_3']??'']);
      $fb   = 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?w=200&q=80';
      if (empty($imgs)) $imgs = [$fb];
      foreach (array_values($imgs) as $idx => $img) : ?>
        <img src="<?php echo esc_url($img); ?>"
             class="ih-detail-thumb<?php echo $idx===0?' active':''; ?>"
             onclick="document.getElementById('mainToolImg').src=this.src;document.querySelectorAll('.ih-detail-thumb').forEach(t=>t.classList.remove('active'));this.classList.add('active')"
             alt="Thumb <?php echo $idx+1; ?>">
      <?php endforeach; ?>
      <?php for ($p=count($imgs);$p<3;$p++): ?>
        <div class="ih-detail-thumb-placeholder">No image</div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Right: Info -->
  <div class="ih-detail-info">

    <!-- Listed by + 3-dot menu -->
    <div class="ih-detail-meta-top">
      <div class="ih-detail-listed-by">
        <img src="<?php echo esc_url($owner_avatar); ?>" alt="">
        <div>
          <div class="ih-detail-listed-by-text">Listed by</div>
          <div class="ih-detail-listed-by-name"><?php echo esc_html($owner_name); ?></div>
        </div>
      </div>
      <div class="ih-detail-menu">
        <button class="ih-detail-menu-btn" onclick="this.nextElementSibling.classList.toggle('hidden')" type="button">⋮</button>
        <div class="ih-dropdown hidden" style="right:0;min-width:160px;">
          <a href="<?php echo admin_url('admin.php?page=ih-tools'); ?>" class="ih-dropdown-item">
            📋 Owner Listings
          </a>
          <a href="<?php echo admin_url('admin.php?page=ih-users'); ?>" class="ih-dropdown-item">
            👤 Owner Details
          </a>
          <button class="ih-dropdown-item ih-danger" id="removeBtnTop"
                  data-id="<?php echo intval($tool['id']); ?>" type="button">
            🗑 Remove Listing
          </button>
          <button class="ih-dropdown-item" style="color:#ef4444;" type="button"
                  onclick="ihBlockOwner(<?php echo intval($tool['owner_id']); ?>)">
            🚫 Blocked Owner
          </button>
        </div>
      </div>
    </div>

    <!-- Availability badges -->
    <div class="ih-detail-badges">
      <span id="ihToolStatusBadge" class="ih-listing-status-badge <?php echo esc_attr( $listing_status_meta['class'] ); ?>"><?php echo esc_html( $listing_status_meta['label'] ); ?></span>
      <?php if ($tool['mould_type']): ?>
        <span class="ih-detail-badge-type"><?php echo esc_html($tool['mould_type']); ?></span>
      <?php endif; ?>
    </div>

    <?php if ( $can_manage_listing_status && $listing_statuses ) : ?>
    <div class="ih-listing-status-panel" data-listing-status-panel>
      <strong>Listing lifecycle status</strong>
      <div class="ih-listing-status-actions">
        <?php foreach ( $listing_statuses as $status_key => $status_meta ) : ?>
          <button type="button"
                  class="ih-listing-status-btn<?php echo $status_key === $listing_status_meta['key'] ? ' active' : ''; ?>"
                  data-status="<?php echo esc_attr( $status_key ); ?>">
            <?php echo esc_html( $status_meta['label'] ); ?>
          </button>
        <?php endforeach; ?>
      </div>
      <span class="ih-listing-status-note">This changes the tool listing itself. Request approval stays separate in Requests.</span>
    </div>
    <?php endif; ?>

    <!-- Title + subtitle -->
    <h1 class="ih-detail-title"><?php echo esc_html($tool['title']); ?></h1>
    <?php if ($tool['part_name']): ?>
      <p class="ih-detail-subtitle"><?php echo esc_html($tool['part_name']); ?></p>
    <?php endif; ?>
    <?php if ($tool['part_description']): ?>
      <p class="ih-detail-description"><?php echo esc_html($tool['part_description']); ?></p>
    <?php endif; ?>

    <!-- Dates -->
    <div class="ih-detail-dates">
      <div class="ih-detail-date-cell">
        <div class="ih-detail-date-label">Listing Date</div>
        <div class="ih-detail-date-val"><?php echo esc_html($fmt_listing); ?></div>
      </div>
      <div class="ih-detail-date-cell">
        <div class="ih-detail-date-label">Expiry Date</div>
        <div class="ih-detail-date-val"><?php echo esc_html($fmt_expiry); ?></div>
      </div>
    </div>

    <!-- Spec boxes: Cavities / Part Weight / Material / Clamp Required -->
    <div class="ih-detail-spec-grid">
      <div class="ih-detail-spec-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        <div>
          <div class="ih-detail-spec-label">Cavities</div>
          <div class="ih-detail-spec-val"><?php echo esc_html($tool['num_cavities_spec'] ?? $tool['num_cavities'] ?? '4'); ?> Cavities</div>
        </div>
      </div>
      <div class="ih-detail-spec-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a3 3 0 0 0-3 3v1H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3V5a3 3 0 0 0-3-3z"/></svg>
        <div>
          <div class="ih-detail-spec-label">Part Weight</div>
          <div class="ih-detail-spec-val"><?php echo esc_html($tool['part_weight'] ?: '28'); ?>g</div>
        </div>
      </div>
      <div class="ih-detail-spec-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        <div>
          <div class="ih-detail-spec-label">Material</div>
          <div class="ih-detail-spec-val"><?php echo esc_html($tool['material'] ?: 'PC'); ?></div>
        </div>
      </div>
      <div class="ih-detail-spec-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>
        <div>
          <div class="ih-detail-spec-label">Clamp Required</div>
          <div class="ih-detail-spec-val"><?php echo esc_html($tool['clamping_required'] ?: '70T'); ?></div>
        </div>
      </div>
    </div>

    <!-- Medical Grade pill -->
    <?php if ($tool['medical_grade'] === 'Yes'): ?>
      <div class="ih-detail-grade-pill">Medical Grade</div>
    <?php endif; ?>

    <!-- Location + Owner -->
    <div class="ih-detail-location-row">
      <div>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Location: <?php echo esc_html($tool['location'] ?: 'Stored at supplier'); ?>
      </div>
      <div>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Owner: <strong><?php echo esc_html($owner_name); ?></strong>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="ih-detail-actions">
      <!-- ?php echo admin_url('admin.php?page=ih-messages'); ? -->
      <a href="<#>" class="ih-btn ih-btn-primary" style="padding:10px 22px;font-size:13.5px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Send Message
      </a>
      <!-- onclick="window.print();return false;" -->
      <a href="#" class="ih-btn-download">    
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download
      </a>
    </div>
  </div>
</div>

<!-- ── Full Specifications ── -->
<div class="ih-specs-section">
  <h2 class="ih-specs-section-title">Full Specifications</h2>

  <div class="ih-specs-grid">

    <!-- Mould Specifications -->
    <div class="ih-specs-card">
      <div class="ih-specs-card-title">Mould Specifications</div>
      <?php
      $mould_rows = [
          'Mould Type'         => $tool['mould_type']      ?: 'Multi-Cavity',
          'Mould Material'     => $tool['mould_material']  ?: 'H13 Steel',
          'Condition'          => $tool['mould_condition'] ?: 'New',
          'Number of Cavities' => ($tool['num_cavities_spec'] ?? $tool['num_cavities'] ?? '4'),
          'Ejector Type'       => $tool['ejector_type']   ?: 'Stripper Plate',
          'Nozzle Type'        => $tool['nozzle_type']     ?: 'Pin with shut-off arm',
      ];
      foreach ($mould_rows as $label => $val): ?>
        <div class="ih-specs-row">
          <span class="ih-specs-row-label"><?php echo esc_html($label); ?></span>
          <span class="ih-specs-row-val"><?php echo esc_html($val); ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Part Specifications -->
    <div class="ih-specs-card">
      <div class="ih-specs-card-title">Part Specifications</div>
      <?php
      $part_rows = [
          'Part Name'      => $tool['part_name']       ?: 'Diagnostic Enclosure',
          'Dimensions'     => $tool['part_dimensions']  ?: '120 × 80 × 35 mm',
          'Part Weight'    => ($tool['part_weight']?:'28').'g',
          'Material'       => $tool['material']         ?: 'PC',
          'Material Grade' => $tool['material_grade']  ?: 'Makrolon 2458',
          'Colour'         => $tool['colour']           ?: 'Custom (RAL 9003 Signal White)',
      ];
      foreach ($part_rows as $label => $val): ?>
        <div class="ih-specs-row">
          <span class="ih-specs-row-label"><?php echo esc_html($label); ?></span>
          <span class="ih-specs-row-val"><?php echo esc_html($val); ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Production Info -->
    <div class="ih-specs-card">
      <div class="ih-specs-card-title">Production Info</div>
      <?php
      $prod_rows = [
          'Annual Volume'     => $tool['annual_volume']     ?: '50,000+',
          'Cycle Time'        => $tool['cycle_time']         ?: '24 seconds',
          'Min Order Qty'     => $tool['min_order_qty']      ?: '5,000',
          'Material'          => $tool['material']           ?: 'PC',
          'Clamping Required' => $tool['clamping_required'] ?: '70T',
          'Compatible Specs'  => $tool['compatible_specs']  ?: '70T+ clamping, 30mm+ screw',
      ];
      foreach ($prod_rows as $label => $val): ?>
        <div class="ih-specs-row">
          <span class="ih-specs-row-label"><?php echo esc_html($label); ?></span>
          <span class="ih-specs-row-val"><?php echo esc_html($val); ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Tooling Features & Requirements -->
    <div class="ih-specs-card">
      <div class="ih-specs-card-title">Tooling Features &amp; Requirements</div>

      <!-- Tolerance pills -->
      <?php if ($tol_pills): ?>
      <div class="ih-tol-pills">
        <?php foreach ($tol_pills as $p): ?>
          <span class="ih-tol-pill"><?php echo esc_html($p); ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Yes/No rows -->
      <?php
      $tf_rows = [
          'Water Cooled Chiller' => $tool['water_cooled'] ?: 'Yes',
          'Suck Pump'            => $tool['suck_pump']    ?: 'No',
          'Food Grade'           => $tool['food_grade']   ?: 'No',
          'Medical Grade'        => $tool['medical_grade']?: 'Yes',
      ];
      foreach ($tf_rows as $label => $val):
        $is_yes = ($val === 'Yes');
        $icon_yes = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
        $icon_no  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
      ?>
        <div class="ih-specs-row">
          <span class="ih-specs-row-label"><?php echo esc_html($label); ?></span>
          <span class="<?php echo $is_yes ? 'ih-yn-yes' : 'ih-yn-no'; ?>">
            <?php echo $is_yes ? $icon_yes : $icon_no; ?>
            <?php echo esc_html($val); ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>

<!-- ── Listed Tools (related) ── -->
<?php if (!empty($related_tools)): ?>
<div>
  <div class="ih-related-head">
    <h2 class="ih-related-title">Listed Tools</h2>
    <a href="<?php echo admin_url('admin.php?page=ih-tools'); ?>" class="ih-link">View All</a>
  </div>
  <div class="ih-listing-grid">
    <?php foreach ($related_tools as $m) : ?>
      <?php include __DIR__ . '/partials/tool-card.php'; ?>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
var ihToolId    = <?php echo intval($tool['id']); ?>;
var ihToolNonce = '<?php echo esc_js($nonce); ?>';
var ihToolAjax  = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

document.querySelectorAll('[data-listing-status-panel] .ih-listing-status-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    var fd = new FormData();
    fd.append('action', 'ih_update_listing_status');
    fd.append('nonce', ihToolNonce);
    fd.append('listing_id', ihToolId);
    fd.append('listing_type', 'tool');
    fd.append('status', btn.dataset.status || 'pending');
    btn.disabled = true;
    fetch(ihToolAjax, { method:'POST', body:fd })
      .then(function(r){ return r.json(); })
      .then(function(res){
        if (!res || !res.success) throw new Error((res && res.data && res.data.message) || 'Status update failed');
        document.querySelectorAll('[data-listing-status-panel] .ih-listing-status-btn').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        var badge = document.getElementById('ihToolStatusBadge');
        if (badge) {
          badge.className = 'ih-listing-status-badge ' + res.data.className;
          badge.textContent = res.data.label;
        }
      })
      .catch(function(err){ alert(err.message || 'Status update failed'); })
      .finally(function(){ btn.disabled = false; });
  });
});

// Block owner
function ihBlockOwner(uid) {
    if (!uid) { alert('Owner not registered in system.'); return; }
    if (!confirm('Block this owner? Their listings will be hidden.')) return;
    var fd = new FormData();
    fd.append('action','ih_toggle_block'); fd.append('nonce', ihToolNonce);
    fd.append('user_id', uid); fd.append('block_action','block');
    fetch('<?php echo admin_url("admin-ajax.php"); ?>',{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
        if(d.success) { alert('Owner blocked.'); location.href='<?php echo admin_url("admin.php?page=ih-tools"); ?>'; }
    });
}

/* ── Open confirm modal ── */
function openRemoveModal() {
  document.getElementById('removeOverlay').classList.add('show');
  document.querySelectorAll('.ih-dropdown').forEach(d=>d.classList.add('hidden'));
}
document.getElementById('removeBtnTop')?.addEventListener('click', openRemoveModal);

/* ── Confirm Yes → AJAX delete → show "Removed" modal → redirect ── */
document.getElementById('confirmRemoveBtn')?.addEventListener('click', function() {
  var toolId = <?php echo intval($tool['id']); ?>;
  if(!toolId) return;

  document.getElementById('removeOverlay').classList.remove('show');
  document.getElementById('removedOverlay').classList.add('show');

  var fd = new FormData();
  fd.append('action', 'ih_delete_tool');
  fd.append('nonce',  ihToolNonce);
  fd.append('id',     toolId);
  
  fetch('<?php echo admin_url("admin-ajax.php"); ?>', {method:'POST', body:fd})
  .then(r => r.json())
  .then(() => {
    setTimeout(() => {
      window.location.href = '<?php echo admin_url("admin.php?page=ih-tools"); ?>';
    }, 1800);
  });
});

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.ih-detail-menu') && !e.target.closest('.ih-dropdown')) {
        document.querySelectorAll('.ih-detail-menu .ih-dropdown').forEach(d => d.classList.add('hidden'));
    }
});
</script>

<?php
$content = ob_get_clean();
$title   = esc_html($tool['title']);
include IH_DIR . 'pages/layout.php';
