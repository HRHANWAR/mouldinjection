<?php defined( 'ABSPATH' ) || exit;

// ── Get machine ID from URL
$machine_id = intval( $_GET['machine_id'] ?? 0 );
$machine    = $machine_id ? ih_db_machine( $machine_id ) : null;
$is_demo    = false;

// ── Fallback demo data ONLY when no real machine was found
if ( ! $machine ) {
    $is_demo = true;
    $machine = [
        'id' => 0, 'title' => 'Engel Victory 150', 'brand' => 'Engel',
        'machine_type' => 'Hydraulic', 'year_manufacture' => '2021', 'identical_count' => 2,
        'clamping_force' => '150T', 'shot_size' => '30g – 120g', 'screw_diameter' => '45mm',
        'max_injection_pressure' => '2,200 bar', 'tie_bar_spacing' => '470 × 470 mm',
        'max_mould_height' => '500mm', 'min_mould_height' => '200mm',
        'max_part_weight' => '110g', 'max_part_dimensions' => '300 × 250 × 150 mm',
        'tolerance' => '±0.05 mm', 'batch_size' => 'Medium (5,000–50,000)', 'min_order_qty' => '500',
        'max_monthly_output' => '120,000 units', 'avg_cycle_time' => '18 seconds',
        'operating_hours' => '16h/day', 'utilization' => '65%', 'location' => 'Manchester, UK',
        'automation_level' => 'Semi-automated', 'robot_integration' => 'Yes', 'multi_cavity' => 'Yes',
        'certifications' => 'ISO 9001', 'qc_tools' => 'CMM, Vision systems', 'tolerance_consistency' => 'High',
        'overmoulding' => 'No', 'insert_moulding' => 'Yes', 'iml' => 'No', 'gas_assisted' => 'No', 'thin_wall' => 'Yes',
        'materials_pp' => 1, 'materials_abs' => 1, 'materials_pe' => 1,
        'materials_pa' => 0, 'materials_pc' => 0, 'materials_peek' => 0,
        'engineering_grade' => 'No', 'recycled_materials' => 'Yes',
        'listing_date' => '2026-03-12', 'expiry_date' => '2026-09-12',
        'image_1' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=700&q=80',
        'image_2' => '', 'image_3' => '',
        'available' => 1, 'owner_id' => 0,
    ];
}

// ── Value helpers: show EXACTLY what the user entered, never fake data
$mval = function( $key ) use ( $machine ) {
    $v = trim( (string) ( $machine[ $key ] ?? '' ) );
    return $v !== '' ? $v : null;
};
$mshow = function( $key ) use ( $mval ) {
    $v = $mval( $key );
    return $v !== null ? esc_html( $v ) : '<span class="ih-mdet-empty">Not specified</span>';
};
$yn_state = function( $key ) use ( $mval ) {
    $v = strtolower( (string) $mval( $key ) );
    if ( in_array( $v, [ 'yes', '1', 'true', 'on' ], true ) ) return 'yes';
    if ( in_array( $v, [ 'no', '0', 'false' ], true ) )       return 'no';
    return 'unset';
};

// ── Owner info
$owner_user   = ! empty( $machine['owner_id'] ) ? get_userdata( intval( $machine['owner_id'] ) ) : null;
$owner_name   = $owner_user ? $owner_user->display_name : ( $is_demo ? 'Demo Owner' : 'Platform listing' );
$owner_avatar = $owner_user ? get_avatar_url( $owner_user->ID, [ 'size' => 48 ] ) : '';

// ── Related machines (exclude current)
$all_machines     = function_exists( 'ih_db_machines' ) ? ih_db_machines( 8 ) : [];
$related_machines = array_slice(
    array_values( array_filter( (array) $all_machines, fn( $m ) => intval( $m['id'] ) !== intval( $machine['id'] ) ) ),
    0, 3
);

// ── Dates exactly as entered
$fmt_listing = $mval( 'listing_date' ) ? date_i18n( 'd M Y', strtotime( $machine['listing_date'] ) ) : null;
$fmt_expiry  = $mval( 'expiry_date' )  ? date_i18n( 'd M Y', strtotime( $machine['expiry_date'] ) )  : null;

// ── Materials: shared map (same data set as the Add Tool listing pages)
$materials_map = function_exists( 'ih_machine_materials_map' ) ? ih_machine_materials_map() : [];
$materials     = function_exists( 'ih_machine_materials' ) ? ih_machine_materials( $machine ) : [];
$mat_labels    = [];
foreach ( $materials_map as $mat ) $mat_labels[ $mat['code'] ] = $mat['label'];

// ── Live marketplace stats (dynamic, from DB)
$mk = function_exists( 'ih_marketplace_stats' ) ? ih_marketplace_stats() : [ 'machines' => 0, 'tools' => 0, 'available' => 0, 'locations' => 0, 'types' => [], 'materials' => [], 'clamp_min' => null, 'clamp_max' => null ];
$clamp_range = ( $mk['clamp_min'] !== null && $mk['clamp_max'] !== null )
    ? ( $mk['clamp_min'] === $mk['clamp_max'] ? $mk['clamp_min'] . 'T' : $mk['clamp_min'] . '–' . $mk['clamp_max'] . 'T' )
    : '—';

// Machine types donut data (palette from the Figma widget set)
$type_palette = [ '#2563EB', '#06B6D4', '#16A34A', '#F59E0B', '#DC2626', '#7C3AED' ];
$types_total  = array_sum( $mk['types'] ) ?: 0;

// Material demand bars
$mat_demand_max = max( 1, $mk['materials'] ? max( $mk['materials'] ) : 1 );

// ── Utilization % (numeric part of the user's value)
$util_pct = min( 100, intval( preg_replace( '/\D/', '', $machine['utilization'] ?? '0' ) ) );

// ── Images: only those the user uploaded
$imgs = array_values( array_filter( [ $mval( 'image_1' ), $mval( 'image_2' ), $mval( 'image_3' ) ] ) );

// ── Spec completeness (technical data quality meter)
$spec_fields = [ 'clamping_force','shot_size','screw_diameter','max_injection_pressure','tie_bar_spacing','max_mould_height','min_mould_height','max_part_weight','max_part_dimensions','tolerance','batch_size','min_order_qty','max_monthly_output','avg_cycle_time','operating_hours','utilization','location','automation_level','certifications','qc_tools','tolerance_consistency' ];
$filled      = count( array_filter( $spec_fields, fn( $f ) => $mval( $f ) !== null ) );
$spec_pct    = $spec_fields ? intval( round( 100 * $filled / count( $spec_fields ) ) ) : 0;

$subtitle_parts = [];
if ( $mval( 'brand' ) )            $subtitle_parts[] = 'Brand: ' . $machine['brand'];
if ( $mval( 'year_manufacture' ) ) $subtitle_parts[] = 'Year of Manufacture: ' . $machine['year_manufacture'];
if ( intval( $machine['identical_count'] ?? 0 ) > 1 ) $subtitle_parts[] = intval( $machine['identical_count'] ) . ' identical units available';

$nonce = wp_create_nonce( 'ih_nonce' );
$listing_status_meta = function_exists( 'ih_listing_status_meta' ) ? ih_listing_status_meta( $machine ) : array( 'key' => ! empty( $machine['available'] ) ? 'available' : 'pending', 'label' => ! empty( $machine['available'] ) ? 'Available Now' : 'Pending Review', 'class' => ! empty( $machine['available'] ) ? 'is-available' : 'is-pending' );
$listing_statuses    = function_exists( 'ih_listing_statuses' ) ? ih_listing_statuses() : array();
$can_manage_listing_status = current_user_can( 'manage_options' ) && ! $is_demo && ! empty( $machine['id'] );

ob_start();
?>

<style>
/* ══ Machine Detail — technical modern ══ */
.ih-mdet-breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--ih-muted);margin-bottom:18px;}
.ih-mdet-breadcrumb a{color:var(--ih-muted);text-decoration:none;transition:color .15s;}
.ih-mdet-breadcrumb a:hover{color:var(--ih-primary);}
.ih-mdet-breadcrumb svg{width:13px;height:13px;flex-shrink:0;}
.ih-mdet-breadcrumb span{color:var(--ih-primary);font-weight:600;}

.ih-mdet-demo-note{background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:12.5px;font-weight:600;border-radius:10px;padding:10px 14px;margin-bottom:16px;display:flex;gap:8px;align-items:center;}

/* Top wrap */
.ih-mdet-wrap{display:grid;grid-template-columns:1.05fr 1fr;gap:24px;margin-bottom:28px;align-items:start;}
@media(max-width:980px){.ih-mdet-wrap{grid-template-columns:1fr;}}

/* Gallery */
.ih-mdet-main-wrap{position:relative;border-radius:16px;overflow:hidden;background:var(--ih-secondary);}
.ih-mdet-main-img{width:100%;height:330px;object-fit:cover;display:block;transition:opacity .25s ease;}
.ih-mdet-main-ph{width:100%;height:330px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--ih-muted);font-size:12px;font-weight:600;}
.ih-mdet-img-id{position:absolute;top:14px;right:14px;background:rgba(0,25,28,.72);color:#c8ff00;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:10.5px;font-weight:600;letter-spacing:.06em;padding:5px 10px;border-radius:8px;}
.ih-mdet-img-avail{position:absolute;top:14px;left:14px;}
.ih-mdet-thumbs{display:flex;gap:10px;margin-top:12px;}
.ih-mdet-thumb{width:110px;height:74px;object-fit:cover;border-radius:10px;cursor:pointer;border:2px solid transparent;transition:border-color .2s,transform .15s;flex-shrink:0;}
.ih-mdet-thumb:hover{transform:translateY(-2px);}
.ih-mdet-thumb.active,.ih-mdet-thumb:hover{border-color:var(--ih-primary);}
.ih-mdet-thumb-ph{width:110px;height:74px;border-radius:10px;background:var(--ih-secondary);border:2px dashed var(--ih-border);display:flex;align-items:center;justify-content:center;color:var(--ih-muted);font-size:11px;}

/* Dark tech strip under gallery */
.ih-mdet-strip{display:grid;grid-template-columns:repeat(4,1fr);background:#00191c;border-radius:12px;margin-top:12px;overflow:hidden;}
.ih-mdet-strip-cell{padding:13px 10px;text-align:center;border-right:1px solid #1f4346;}
.ih-mdet-strip-cell:last-child{border-right:none;}
.ih-mdet-strip-label{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:9px;letter-spacing:.12em;color:#7e938a;margin-bottom:3px;text-transform:uppercase;}
.ih-mdet-strip-val{font-size:14.5px;font-weight:800;color:#c8ff00;white-space:nowrap;}
.ih-mdet-strip-val.is-empty{color:#43615c;font-weight:600;font-size:12px;}

/* Info panel */
.ih-mdet-top-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.ih-mdet-listed{display:flex;align-items:center;gap:9px;}
.ih-mdet-listed img,.ih-mdet-listed .ih-mdet-avatar-fb{width:38px;height:38px;border-radius:50%;object-fit:cover;}
.ih-mdet-avatar-fb{background:var(--ih-secondary);display:flex;align-items:center;justify-content:center;color:var(--ih-primary);font-weight:800;font-size:14px;}
.ih-mdet-listed-label{font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--ih-muted);font-family:ui-monospace,Menlo,Consolas,monospace;}
.ih-mdet-listed-name{font-size:13px;font-weight:700;color:var(--ih-primary);}

.ih-mdet-badges{display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;}
.ih-mdet-badge-avail{background:var(--ih-accent);color:var(--ih-accent-fg);font-size:11.5px;font-weight:700;padding:5px 14px;border-radius:999px;}
.ih-mdet-badge-type{background:var(--ih-secondary);color:var(--ih-primary);font-size:11.5px;font-weight:600;padding:5px 14px;border-radius:999px;border:1px solid var(--ih-border);}
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

.ih-mdet-title{font-size:26px;font-weight:800;color:var(--ih-primary);margin:0 0 4px;line-height:1.2;}
.ih-mdet-subtitle{font-size:12.5px;color:var(--ih-muted);margin:0 0 14px;}

/* Listing / expiry dates */
.ih-mdet-dates{display:grid;grid-template-columns:1fr 1fr;border:1px solid var(--ih-border);border-radius:12px;overflow:hidden;margin-bottom:14px;}
.ih-mdet-date-cell{padding:10px 14px;}
.ih-mdet-date-cell:first-child{border-right:1px solid var(--ih-border);}
.ih-mdet-date-label{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:9px;letter-spacing:.1em;color:var(--ih-muted);text-transform:uppercase;margin-bottom:2px;}
.ih-mdet-date-val{font-size:13.5px;font-weight:700;}

/* Key spec tiles */
.ih-mdet-spec-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;}
.ih-mdet-spec-cell{padding:12px 14px;background:var(--ih-secondary);border:1px solid var(--ih-border);border-radius:11px;}
.ih-mdet-spec-label{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:9px;letter-spacing:.1em;text-transform:uppercase;color:var(--ih-muted);margin-bottom:2px;}
.ih-mdet-spec-val{font-size:16px;font-weight:800;color:var(--ih-primary);}
.ih-mdet-empty{color:#9aa9a1;font-weight:500;font-style:italic;font-size:.9em;}

/* Materials pills */
.ih-mdet-mat-row{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;}
.ih-mdet-mat-pill{background:var(--ih-accent);color:var(--ih-accent-fg);font-size:11.5px;font-weight:700;padding:4px 14px;border-radius:999px;}

/* Meta rows */
.ih-mdet-meta{display:flex;flex-direction:column;gap:7px;margin-bottom:16px;}
.ih-mdet-meta-row{display:flex;align-items:center;gap:7px;font-size:13px;color:var(--ih-muted);}
.ih-mdet-meta-row svg{width:15px;height:15px;flex-shrink:0;}
.ih-mdet-util-bar{flex:1;height:5px;background:var(--ih-border);border-radius:99px;overflow:hidden;margin-left:4px;}
.ih-mdet-util-fill{height:100%;background:var(--ih-primary);border-radius:99px;width:0;transition:width .9s cubic-bezier(.2,.7,.3,1);}

/* Spec completeness meter */
.ih-mdet-quality{display:flex;align-items:center;gap:10px;background:var(--ih-secondary);border:1px solid var(--ih-border);border-radius:11px;padding:10px 14px;margin-bottom:16px;}
.ih-mdet-quality-bar{flex:1;height:6px;background:var(--ih-border);border-radius:99px;overflow:hidden;}
.ih-mdet-quality-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,#2d4a3e,#22c55e);width:0;transition:width 1s cubic-bezier(.2,.7,.3,1);}
.ih-mdet-quality-pct{font-size:13px;font-weight:800;color:var(--ih-primary);min-width:38px;text-align:right;}
.ih-mdet-quality-label{font-size:11px;color:var(--ih-muted);font-weight:600;}

.ih-mdet-actions{display:flex;gap:10px;flex-wrap:wrap;}

/* ── Full Specs ── */
.ih-mdet-specs-title{display:flex;align-items:center;gap:9px;font-size:20px;font-weight:800;color:var(--ih-primary);margin:0 0 16px;}
.ih-mdet-specs-title::before{content:'';width:4px;height:18px;border-radius:2px;background:var(--ih-accent);}
.ih-mdet-specs-2col{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.ih-mdet-specs-3col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px;}
@media(max-width:860px){.ih-mdet-specs-2col,.ih-mdet-specs-3col{grid-template-columns:1fr;}}

.ih-mdet-spec-card{background:var(--ih-card);border:1px solid var(--ih-border);border-radius:14px;padding:22px;box-shadow:var(--ih-shadow);opacity:1;transform:none;}
.ih-mdet-spec-card.ih-mdet-reveal-pending{opacity:0;transform:translateY(10px);transition:opacity .45s ease,transform .45s ease;}
.ih-mdet-spec-card.ih-mdet-reveal-pending.in-view{opacity:1;transform:none;}
.ih-mdet-spec-card-title{font-size:15px;font-weight:700;color:var(--ih-primary);margin:0 0 14px;}
.ih-mdet-spec-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--ih-border);font-size:13px;gap:12px;}
.ih-mdet-spec-row:last-child{border-bottom:none;padding-bottom:0;}
.ih-mdet-spec-row-label{color:var(--ih-muted);flex-shrink:0;}
.ih-mdet-spec-row-val{font-weight:600;text-align:right;}

/* Yes/No/unset badges */
.ih-yn-yes{color:#16a34a;display:inline-flex;align-items:center;gap:4px;font-weight:700;}
.ih-yn-no{color:#dc2626;display:inline-flex;align-items:center;gap:4px;font-weight:700;}
.ih-yn-unset{color:#9aa9a1;font-style:italic;font-weight:500;font-size:.92em;}

/* ── Marketplace stats (dynamic widgets) ── */
.ih-mkt{margin-bottom:28px;}
.ih-mkt-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px;}
.ih-mkt-tile{background:var(--ih-card);border:1px solid var(--ih-border);border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:13px;box-shadow:var(--ih-shadow);opacity:1;transform:none;}
.ih-mkt-tile.ih-mdet-reveal-pending{opacity:0;transform:translateY(10px);transition:opacity .45s ease,transform .45s ease;}
.ih-mkt-tile.ih-mdet-reveal-pending.in-view{opacity:1;transform:none;}
.ih-mkt-ico{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ih-mkt-ico svg{width:20px;height:20px;}
.ih-mkt-ico.blue{background:#dbeafe;color:#2563eb;}
.ih-mkt-ico.cyan{background:#cffafe;color:#0891b2;}
.ih-mkt-ico.green{background:#dcfce7;color:#16a34a;}
.ih-mkt-ico.amber{background:#fef3c7;color:#d97706;}
.ih-mkt-num{font-size:24px;font-weight:800;color:var(--ih-primary);line-height:1.1;}
.ih-mkt-lbl{font-size:11px;font-weight:600;color:var(--ih-muted);margin-top:2px;}
.ih-mkt-widgets{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;}
.ih-mkt-card{background:var(--ih-card);border:1px solid var(--ih-border);border-radius:14px;padding:20px;box-shadow:var(--ih-shadow);opacity:1;transform:none;}
.ih-mkt-card.ih-mdet-reveal-pending{opacity:0;transform:translateY(10px);transition:opacity .45s ease,transform .45s ease;}
.ih-mkt-card.ih-mdet-reveal-pending.in-view{opacity:1;transform:none;}
.ih-mkt-card-title{font-size:14px;font-weight:700;color:var(--ih-primary);margin:0 0 14px;display:flex;align-items:center;gap:8px;}
.ih-mkt-card-title svg{width:15px;height:15px;}
/* Donut */
.ih-mkt-donut-wrap{display:flex;align-items:center;gap:18px;}
.ih-mkt-donut{position:relative;width:120px;height:120px;flex-shrink:0;}
.ih-mkt-donut svg{transform:rotate(-90deg);}
.ih-mkt-donut circle{fill:none;stroke-width:14;stroke-linecap:butt;transition:stroke-dasharray 1s cubic-bezier(.2,.7,.3,1);}
.ih-mkt-donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.ih-mkt-donut-num{font-size:21px;font-weight:800;color:var(--ih-primary);line-height:1;}
.ih-mkt-donut-cap{font-size:9.5px;color:var(--ih-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
.ih-mkt-legend{display:flex;flex-direction:column;gap:7px;min-width:0;}
.ih-mkt-legend-row{display:flex;align-items:center;gap:7px;font-size:12px;color:var(--ih-muted);}
.ih-mkt-legend-dot{width:9px;height:9px;border-radius:3px;flex-shrink:0;}
.ih-mkt-legend-val{margin-left:auto;font-weight:800;color:var(--ih-primary);}
/* Split bar (machines vs tools) */
.ih-mkt-split-bar{display:flex;height:14px;border-radius:99px;overflow:hidden;margin:10px 0 12px;background:var(--ih-border);}
.ih-mkt-split-a{background:#2563eb;width:0;transition:width 1s cubic-bezier(.2,.7,.3,1);}
.ih-mkt-split-b{background:#06b6d4;width:0;transition:width 1s cubic-bezier(.2,.7,.3,1);}
/* Material demand bars */
.ih-mkt-bar-row{display:grid;grid-template-columns:46px 1fr 26px;align-items:center;gap:9px;margin-bottom:9px;font-size:11.5px;}
.ih-mkt-bar-name{font-weight:700;color:var(--ih-primary);}
.ih-mkt-bar-track{height:8px;background:var(--ih-secondary);border-radius:99px;overflow:hidden;}
.ih-mkt-bar-fill{height:100%;border-radius:99px;width:0;transition:width .9s cubic-bezier(.2,.7,.3,1);}
.ih-mkt-bar-row.is-mine .ih-mkt-bar-name{color:#16a34a;}
.ih-mkt-bar-val{font-weight:800;color:var(--ih-muted);text-align:right;}
@media(max-width:1080px){.ih-mkt-grid{grid-template-columns:repeat(2,1fr);}.ih-mkt-widgets{grid-template-columns:1fr;}}
@media(max-width:520px){.ih-mkt-grid{grid-template-columns:1fr 1fr;}.ih-mkt-tile{flex-direction:column;align-items:flex-start;gap:9px;}.ih-mkt-donut-wrap{flex-direction:column;align-items:flex-start;}}

/* Related */
.ih-mdet-related-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
.ih-mdet-related-title{display:flex;align-items:center;gap:9px;font-size:18px;font-weight:800;color:var(--ih-primary);margin:0;}
.ih-mdet-related-title::before{content:'';width:4px;height:16px;border-radius:2px;background:var(--ih-accent);}

/* Copy spec sheet button feedback */
.ih-mdet-copy.copied{background:#16a34a !important;color:#fff !important;border-color:#16a34a !important;}

/* Modals (remove / removed) */
.ih-remove-overlay,.ih-removed-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;display:none;align-items:center;justify-content:center;}
.ih-remove-overlay.show,.ih-removed-overlay.show{display:flex;}
.ih-remove-modal,.ih-removed-modal{background:#fff;border-radius:20px;padding:40px 36px;text-align:center;width:440px;max-width:96vw;box-shadow:0 24px 60px rgba(0,0,0,.18);}
.ih-remove-icon,.ih-removed-icon{font-size:56px;margin-bottom:14px;display:block;}
.ih-remove-title,.ih-removed-title{font-size:18px;font-weight:800;margin:0 0 26px;color:#1a2e22;}
.ih-removed-title{margin:0;}
.ih-remove-btns{display:flex;gap:14px;justify-content:center;}

/* detail menu */
.ih-mdet-menu{position:relative;}
.ih-mdet-menu-btn{width:34px;height:34px;border-radius:50%;background:var(--ih-secondary);border:1px solid var(--ih-border);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:18px;color:#555;transition:background .15s;}
.ih-mdet-menu-btn:hover{background:var(--ih-border);}

@media(max-width:640px){
  .ih-mdet-spec-grid{grid-template-columns:1fr 1fr;}
  .ih-mdet-strip{grid-template-columns:repeat(2,1fr);}
  .ih-mdet-strip-cell:nth-child(2){border-right:none;}
  .ih-mdet-strip-cell:nth-child(-n+2){border-bottom:1px solid #1f4346;}
  .ih-mdet-main-img,.ih-mdet-main-ph{height:220px;}
}
</style>

<!-- ── Confirm Remove Modal ── -->
<div class="ih-remove-overlay" id="removeOverlay">
  <div class="ih-remove-modal">
    <span class="ih-remove-icon">🗑️</span>
    <p class="ih-remove-title">Remove this listing?</p>
    <div class="ih-remove-btns">
      <button onclick="document.getElementById('removeOverlay').classList.remove('show')"
              style="display:inline-flex;align-items:center;gap:6px;padding:11px 32px;border-radius:999px;border:2px solid #1a2e22;background:#fff;color:#1a2e22;font-size:14px;font-weight:700;cursor:pointer;">
        ✕ No
      </button>
      <button id="confirmRemoveBtn"
              style="display:inline-flex;align-items:center;gap:6px;padding:11px 32px;border-radius:999px;border:none;background:#ef4444;color:#fff;font-size:14px;font-weight:700;cursor:pointer;">
        ✓ Yes
      </button>
    </div>
  </div>
</div>

<!-- ── Removed Successfully Modal ── -->
<div class="ih-removed-overlay" id="removedOverlay">
  <div class="ih-removed-modal">
    <span class="ih-removed-icon">🗑️</span>
    <p class="ih-removed-title">Removed Successfully</p>
  </div>
</div>

<!-- Breadcrumb -->
<div class="ih-mdet-breadcrumb">
  <a href="<?php echo admin_url('admin.php?page=ih-dashboard'); ?>">Dashboard</a>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
  <a href="<?php echo admin_url('admin.php?page=ih-machines'); ?>">Machines</a>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
  <span><?php echo esc_html($machine['title']); ?></span>
</div>

<?php if ($is_demo): ?>
<div class="ih-mdet-demo-note">
  ⚠️ No machine found for this ID — showing demo data. Real listings display exactly what the owner submitted.
</div>
<?php endif; ?>

<!-- ── Top section ── -->
<div class="ih-mdet-wrap">

  <!-- Left: Gallery + tech strip -->
  <div>
    <div class="ih-mdet-main-wrap">
      <?php if (!empty($imgs)): ?>
        <img id="mainMachImg" src="<?php echo esc_url($imgs[0]); ?>" alt="<?php echo esc_attr($machine['title']); ?>" class="ih-mdet-main-img">
      <?php else: ?>
        <div class="ih-mdet-main-ph">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="42" height="42"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 16-5-5L5 20"/></svg>
          No images uploaded for this machine
        </div>
      <?php endif; ?>
      <span id="ihMachineImageStatus" class="ih-mdet-img-avail ih-listing-status-badge <?php echo esc_attr( $listing_status_meta['class'] ); ?>" style="position:absolute;"><?php echo esc_html( $listing_status_meta['label'] ); ?></span>
      <span class="ih-mdet-img-id">MCH-<?php echo str_pad(intval($machine['id']), 5, '0', STR_PAD_LEFT); ?></span>
    </div>

    <div class="ih-mdet-thumbs">
      <?php foreach ($imgs as $idx => $img): ?>
        <img src="<?php echo esc_url($img); ?>"
             class="ih-mdet-thumb<?php echo $idx===0?' active':''; ?>"
             data-full="<?php echo esc_url($img); ?>"
             alt="Thumb <?php echo $idx+1; ?>">
      <?php endforeach; ?>
      <?php for ($p=count($imgs); $p<3; $p++): ?>
        <div class="ih-mdet-thumb-ph">No image</div>
      <?php endfor; ?>
    </div>

    <!-- Dark tech strip: user-entered key specs -->
    <div class="ih-mdet-strip">
      <?php
      $strip = [
          'CLAMP'    => $mval('clamping_force'),
          'SHOT'     => $mval('shot_size'),
          'SCREW'    => $mval('screw_diameter'),
          'PRESSURE' => $mval('max_injection_pressure'),
      ];
      foreach ($strip as $sl => $sv): ?>
      <div class="ih-mdet-strip-cell">
        <div class="ih-mdet-strip-label"><?php echo esc_html($sl); ?></div>
        <div class="ih-mdet-strip-val<?php echo $sv===null?' is-empty':''; ?>"><?php echo $sv!==null ? esc_html($sv) : 'n/a'; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right: Info -->
  <div class="ih-card" style="padding:22px;">

    <!-- Listed by + menu -->
    <div class="ih-mdet-top-row">
      <div class="ih-mdet-listed">
        <?php if ($owner_avatar): ?>
          <img src="<?php echo esc_url($owner_avatar); ?>" alt="">
        <?php else: ?>
          <span class="ih-mdet-avatar-fb"><?php echo esc_html(strtoupper(mb_substr($owner_name,0,1))); ?></span>
        <?php endif; ?>
        <div>
          <div class="ih-mdet-listed-label">Listed by</div>
          <div class="ih-mdet-listed-name"><?php echo esc_html($owner_name); ?></div>
        </div>
      </div>
      <div class="ih-mdet-menu">
        <button class="ih-mdet-menu-btn" onclick="this.nextElementSibling.classList.toggle('hidden')" type="button">⋮</button>
        <div class="ih-dropdown hidden" style="right:0;min-width:170px;">
          <a href="<?php echo admin_url('admin.php?page=ih-machines'); ?>" class="ih-dropdown-item">📋 Owner Listings</a>
          <a href="<?php echo admin_url('admin.php?page=ih-users'); ?>" class="ih-dropdown-item">👤 Owner Details</a>
          <button class="ih-dropdown-item ih-danger" type="button" id="removeBtnTop">🗑 Remove Listing</button>
          <button class="ih-dropdown-item" type="button" style="color:#ef4444;"
                  onclick="ihBlockMachineOwner(<?php echo intval($machine['owner_id']); ?>)">🚫 Block Owner</button>
        </div>
      </div>
    </div>

    <!-- Badges -->
    <div class="ih-mdet-badges">
      <span id="ihMachineStatusBadge" class="ih-listing-status-badge <?php echo esc_attr( $listing_status_meta['class'] ); ?>"><?php echo esc_html( $listing_status_meta['label'] ); ?></span>
      <?php if ($mval('machine_type')): ?>
        <span class="ih-mdet-badge-type"><?php echo esc_html($machine['machine_type']); ?></span>
      <?php endif; ?>
      <?php if ($mval('brand')): ?>
        <span class="ih-mdet-badge-type"><?php echo esc_html($machine['brand']); ?></span>
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
      <span class="ih-listing-status-note">This changes the machine listing itself. Request approval stays separate in Requests.</span>
    </div>
    <?php endif; ?>

    <!-- Title -->
    <h1 class="ih-mdet-title"><?php echo esc_html($machine['title']); ?></h1>
    <?php if ($subtitle_parts): ?>
      <p class="ih-mdet-subtitle"><?php echo esc_html(implode(' · ', $subtitle_parts)); ?></p>
    <?php endif; ?>

    <!-- Listing / Expiry dates -->
    <div class="ih-mdet-dates">
      <div class="ih-mdet-date-cell">
        <div class="ih-mdet-date-label">Listing Date</div>
        <div class="ih-mdet-date-val"><?php echo $fmt_listing ? esc_html($fmt_listing) : '<span class="ih-mdet-empty">—</span>'; ?></div>
      </div>
      <div class="ih-mdet-date-cell">
        <div class="ih-mdet-date-label">Expiry Date</div>
        <div class="ih-mdet-date-val"><?php echo $fmt_expiry ? esc_html($fmt_expiry) : '<span class="ih-mdet-empty">—</span>'; ?></div>
      </div>
    </div>

    <!-- Key spec tiles -->
    <div class="ih-mdet-spec-grid">
      <?php
      $key_specs = [
          'Max Part Weight' => 'max_part_weight',
          'Avg Cycle Time'  => 'avg_cycle_time',
          'Monthly Output'  => 'max_monthly_output',
          'Min Order Qty'   => 'min_order_qty',
      ];
      foreach ($key_specs as $kl => $kf): ?>
      <div class="ih-mdet-spec-cell">
        <div class="ih-mdet-spec-label"><?php echo esc_html($kl); ?></div>
        <div class="ih-mdet-spec-val"><?php echo $mshow($kf); ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Spec completeness -->
    <div class="ih-mdet-quality" title="<?php echo esc_attr($filled . ' of ' . count($spec_fields) . ' technical fields provided by the owner'); ?>">
      <span class="ih-mdet-quality-label">Spec completeness</span>
      <div class="ih-mdet-quality-bar"><div class="ih-mdet-quality-fill" data-pct="<?php echo $spec_pct; ?>"></div></div>
      <span class="ih-mdet-quality-pct"><?php echo $spec_pct; ?>%</span>
    </div>

    <!-- Supported Materials -->
    <?php if ($materials): ?>
    <div style="margin-bottom:14px;">
      <div class="ih-mdet-spec-label" style="margin-bottom:7px;">Supported Materials</div>
      <div class="ih-mdet-mat-row" style="margin-bottom:0;">
        <?php foreach ($materials as $mat): ?>
          <span class="ih-mdet-mat-pill" title="<?php echo esc_attr($mat_labels[$mat] ?? $mat); ?>"><?php echo esc_html($mat); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Location / Operating / Utilization -->
    <div class="ih-mdet-meta">
      <?php if ($mval('location')): ?>
      <div class="ih-mdet-meta-row">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <?php echo esc_html($machine['location']); ?>
      </div>
      <?php endif; ?>
      <?php if ($mval('operating_hours')): ?>
      <div class="ih-mdet-meta-row">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Operating: <?php echo esc_html($machine['operating_hours']); ?>
      </div>
      <?php endif; ?>
      <?php if ($mval('utilization')): ?>
      <div class="ih-mdet-meta-row">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Utilization: <?php echo esc_html($machine['utilization']); ?>
        <?php if ($util_pct > 0): ?>
          <div class="ih-mdet-util-bar"><div class="ih-mdet-util-fill" data-pct="<?php echo $util_pct; ?>"></div></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="ih-mdet-actions">
      <a href="<?php echo admin_url('admin.php?page=ih-messages'); ?>" class="ih-btn ih-btn-primary" style="padding:11px 26px;font-size:14px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Send Message
      </a>
      <button type="button" class="ih-btn ih-btn-outline ih-mdet-copy" id="copySpecBtn" style="padding:11px 22px;font-size:14px;cursor:pointer;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        Copy Spec Sheet
      </button>
    </div>

  </div><!-- /.ih-card -->
</div><!-- /.ih-mdet-wrap -->

<!-- ── Full Specifications ── -->
<h2 class="ih-mdet-specs-title">Full Specifications</h2>

<div class="ih-mdet-specs-2col">

  <div class="ih-mdet-spec-card">
    <div class="ih-mdet-spec-card-title">Core Processing Specs</div>
    <?php
    $core = [
        'Clamping Force'         => 'clamping_force',
        'Shot Size'              => 'shot_size',
        'Screw Diameter'         => 'screw_diameter',
        'Max Injection Pressure' => 'max_injection_pressure',
        'Tie Bar Spacing'        => 'tie_bar_spacing',
        'Max Mould Height'       => 'max_mould_height',
        'Min Mould Height'       => 'min_mould_height',
    ];
    foreach ($core as $l => $f): ?>
      <div class="ih-mdet-spec-row" data-spec="<?php echo esc_attr($l); ?>">
        <span class="ih-mdet-spec-row-label"><?php echo esc_html($l); ?></span>
        <span class="ih-mdet-spec-row-val"><?php echo $mshow($f); ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="ih-mdet-spec-card">
    <div class="ih-mdet-spec-card-title">Part Capability</div>
    <?php
    $part = [
        'Max Part Weight'      => 'max_part_weight',
        'Max Part Dimensions'  => 'max_part_dimensions',
        'Achievable Tolerance' => 'tolerance',
    ];
    foreach ($part as $l => $f): ?>
      <div class="ih-mdet-spec-row" data-spec="<?php echo esc_attr($l); ?>">
        <span class="ih-mdet-spec-row-label"><?php echo esc_html($l); ?></span>
        <span class="ih-mdet-spec-row-val"><?php echo $mshow($f); ?></span>
      </div>
    <?php endforeach; ?>

    <div class="ih-mdet-spec-card-title" style="margin-top:20px;">Production Capability</div>
    <?php
    $prod = [
        'Batch Size'         => 'batch_size',
        'Min Order Qty'      => 'min_order_qty',
        'Max Monthly Output' => 'max_monthly_output',
        'Avg Cycle Time'     => 'avg_cycle_time',
        'Operating Hours'    => 'operating_hours',
        'Utilization'        => 'utilization',
    ];
    foreach ($prod as $l => $f): ?>
      <div class="ih-mdet-spec-row" data-spec="<?php echo esc_attr($l); ?>">
        <span class="ih-mdet-spec-row-label"><?php echo esc_html($l); ?></span>
        <span class="ih-mdet-spec-row-val"><?php echo $mshow($f); ?></span>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<div class="ih-mdet-specs-3col">

  <!-- Automation & Quality -->
  <div class="ih-mdet-spec-card">
    <div class="ih-mdet-spec-card-title">Automation &amp; Features</div>
    <?php
    $yn_icon_yes = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
    $yn_icon_no  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';

    $render_yn = function( $field ) use ( $yn_state, $yn_icon_yes, $yn_icon_no ) {
        $state = $yn_state( $field );
        if ( $state === 'yes' ) return '<span class="ih-yn-yes">' . $yn_icon_yes . ' Yes</span>';
        if ( $state === 'no' )  return '<span class="ih-yn-no">'  . $yn_icon_no  . ' No</span>';
        return '<span class="ih-yn-unset">Not specified</span>';
    };
    ?>
    <div class="ih-mdet-spec-row" data-spec="Automation Level">
      <span class="ih-mdet-spec-row-label">Automation Level</span>
      <span class="ih-mdet-spec-row-val"><?php echo $mshow('automation_level'); ?></span>
    </div>
    <div class="ih-mdet-spec-row" data-spec="Robot Integration">
      <span class="ih-mdet-spec-row-label">Robot Integration</span>
      <?php echo $render_yn('robot_integration'); ?>
    </div>
    <div class="ih-mdet-spec-row" data-spec="Multi-Cavity Support">
      <span class="ih-mdet-spec-row-label">Multi-Cavity Support</span>
      <?php echo $render_yn('multi_cavity'); ?>
    </div>

    <div class="ih-mdet-spec-card-title" style="margin-top:18px;">Quality &amp; Compliance</div>
    <?php
    $qual = [
        'Certifications'        => 'certifications',
        'QC Tools'              => 'qc_tools',
        'Tolerance Consistency' => 'tolerance_consistency',
    ];
    foreach ($qual as $l => $f): ?>
      <div class="ih-mdet-spec-row" data-spec="<?php echo esc_attr($l); ?>">
        <span class="ih-mdet-spec-row-label"><?php echo esc_html($l); ?></span>
        <span class="ih-mdet-spec-row-val"><?php echo $mshow($f); ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Advanced Capabilities -->
  <div class="ih-mdet-spec-card">
    <div class="ih-mdet-spec-card-title">Advanced Capabilities</div>
    <?php
    $adv = [
        'Overmoulding'            => 'overmoulding',
        'Insert Moulding'         => 'insert_moulding',
        'In-Mold Labelling (IML)' => 'iml',
        'Gas-Assisted Moulding'   => 'gas_assisted',
        'Thin-Wall Moulding'      => 'thin_wall',
    ];
    foreach ($adv as $l => $f): ?>
      <div class="ih-mdet-spec-row" data-spec="<?php echo esc_attr($l); ?>">
        <span class="ih-mdet-spec-row-label"><?php echo esc_html($l); ?></span>
        <?php echo $render_yn($f); ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Materials -->
  <div class="ih-mdet-spec-card">
    <div class="ih-mdet-spec-card-title">Materials</div>
    <?php if ($materials): ?>
    <div class="ih-mdet-mat-row" style="margin-bottom:16px;">
      <?php foreach ($materials as $mat): ?>
        <span class="ih-mdet-mat-pill" title="<?php echo esc_attr($mat_labels[$mat] ?? $mat); ?>"><?php echo esc_html($mat); ?></span>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="font-size:12.5px;color:var(--ih-muted);margin:0 0 14px;font-style:italic;">No materials selected by the owner.</p>
    <?php endif; ?>
    <div class="ih-mdet-spec-row" data-spec="Engineering Grade Materials">
      <span class="ih-mdet-spec-row-label">Engineering Grade Materials</span>
      <?php echo $render_yn('engineering_grade'); ?>
    </div>
    <div class="ih-mdet-spec-row" data-spec="Recycled Materials Supported">
      <span class="ih-mdet-spec-row-label">Recycled Materials Supported</span>
      <?php echo $render_yn('recycled_materials'); ?>
    </div>
  </div>

</div>

<!-- ── Marketplace stats (live, dynamic) ── -->
<h2 class="ih-mdet-specs-title">Marketplace Stats</h2>
<div class="ih-mkt">

  <!-- Stat tiles -->
  <div class="ih-mkt-grid">
    <div class="ih-mkt-tile">
      <div class="ih-mkt-ico blue">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4Z"/><path d="M3.3 7 12 12l8.7-5"/><path d="M12 22V12"/></svg>
      </div>
      <div>
        <div class="ih-mkt-num" data-count="<?php echo intval($mk['machines']); ?>">0</div>
        <div class="ih-mkt-lbl">Machines Listed</div>
      </div>
    </div>
    <div class="ih-mkt-tile">
      <div class="ih-mkt-ico green">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <div>
        <div class="ih-mkt-num" data-count="<?php echo intval($mk['available']); ?>">0</div>
        <div class="ih-mkt-lbl">Available Now</div>
      </div>
    </div>
    <div class="ih-mkt-tile">
      <div class="ih-mkt-ico cyan">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      </div>
      <div>
        <div class="ih-mkt-num" data-count="<?php echo intval($mk['locations']); ?>">0</div>
        <div class="ih-mkt-lbl">Locations</div>
      </div>
    </div>
    <div class="ih-mkt-tile">
      <div class="ih-mkt-ico amber">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.7 6.3 1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76a1 1 0 0 0 0 1.4z"/></svg>
      </div>
      <div>
        <div class="ih-mkt-num" style="font-size:19px;"><?php echo esc_html($clamp_range); ?></div>
        <div class="ih-mkt-lbl">Clamp Range</div>
      </div>
    </div>
  </div>

  <!-- Widgets row -->
  <div class="ih-mkt-widgets">

    <!-- Donut: machine types -->
    <div class="ih-mkt-card">
      <div class="ih-mkt-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
        Machine Types
      </div>
      <?php if ($types_total > 0): ?>
      <div class="ih-mkt-donut-wrap">
        <div class="ih-mkt-donut">
          <svg width="120" height="120" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="46" stroke="#eef2f0"></circle>
            <?php
            $offset = 0; $i = 0; $circ = 2 * M_PI * 46;
            foreach ($mk['types'] as $tname => $tcount):
                $frac  = $tcount / $types_total;
                $color = $type_palette[$i % count($type_palette)];
            ?>
            <circle cx="60" cy="60" r="46"
                    stroke="<?php echo $color; ?>"
                    class="ih-mkt-donut-seg"
                    data-frac="<?php echo esc_attr($frac); ?>"
                    stroke-dasharray="0 <?php echo $circ; ?>"
                    stroke-dashoffset="<?php echo -$offset * $circ; ?>"></circle>
            <?php $offset += $frac; $i++; endforeach; ?>
          </svg>
          <div class="ih-mkt-donut-center">
            <div class="ih-mkt-donut-num" data-count="<?php echo intval($types_total); ?>">0</div>
            <div class="ih-mkt-donut-cap">Typed</div>
          </div>
        </div>
        <div class="ih-mkt-legend">
          <?php $i = 0; foreach ($mk['types'] as $tname => $tcount): ?>
          <div class="ih-mkt-legend-row">
            <span class="ih-mkt-legend-dot" style="background:<?php echo $type_palette[$i % count($type_palette)]; ?>"></span>
            <?php echo esc_html($tname); ?>
            <span class="ih-mkt-legend-val"><?php echo intval($tcount); ?></span>
          </div>
          <?php $i++; endforeach; ?>
        </div>
      </div>
      <?php else: ?>
      <p style="font-size:12px;color:var(--ih-muted);font-style:italic;margin:0;">No machine types recorded yet.</p>
      <?php endif; ?>
    </div>

    <!-- Split: machines vs tools -->
    <div class="ih-mkt-card">
      <div class="ih-mkt-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M12 3v18"/></svg>
        Machines vs Tools
      </div>
      <div style="font-size:12px;color:var(--ih-muted);"><?php echo intval($mk['machines'] + $mk['tools']); ?> active listings</div>
      <?php
      $split_total = max(1, intval($mk['machines']) + intval($mk['tools']));
      $pct_m = round(100 * intval($mk['machines']) / $split_total);
      $pct_t = 100 - $pct_m;
      ?>
      <div class="ih-mkt-split-bar">
        <div class="ih-mkt-split-a" data-pct="<?php echo $pct_m; ?>"></div>
        <div class="ih-mkt-split-b" data-pct="<?php echo $pct_t; ?>"></div>
      </div>
      <div class="ih-mkt-legend" style="flex-direction:row;gap:18px;">
        <div class="ih-mkt-legend-row"><span class="ih-mkt-legend-dot" style="background:#2563eb"></span>Machines <span class="ih-mkt-legend-val" data-count="<?php echo intval($mk['machines']); ?>">0</span></div>
        <div class="ih-mkt-legend-row"><span class="ih-mkt-legend-dot" style="background:#06b6d4"></span>Tools <span class="ih-mkt-legend-val" data-count="<?php echo intval($mk['tools']); ?>">0</span></div>
      </div>
    </div>

    <!-- Material capability across marketplace -->
    <div class="ih-mkt-card">
      <div class="ih-mkt-card-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round"><path d="M3 3v18h18"/><rect x="7" y="10" width="3" height="8" rx="1" fill="#16a34a" stroke="none"/><rect x="12" y="6" width="3" height="12" rx="1" fill="#86efac" stroke="none"/><rect x="17" y="13" width="3" height="5" rx="1" fill="#16a34a" stroke="none"/></svg>
        Material Capability
      </div>
      <?php
      $mat_palette = ['ABS'=>'#2563EB','PP'=>'#06B6D4','PE'=>'#16A34A','PA'=>'#F59E0B','PC'=>'#7C3AED','PEEK'=>'#DC2626'];
      foreach ($mk['materials'] as $code => $cnt):
          $mine = in_array($code, $materials, true);
      ?>
      <div class="ih-mkt-bar-row<?php echo $mine ? ' is-mine' : ''; ?>" title="<?php echo esc_attr(($mat_labels[$code] ?? $code) . ' — ' . $cnt . ' machines' . ($mine ? ' (this machine supports it)' : '')); ?>">
        <span class="ih-mkt-bar-name"><?php echo esc_html($code); ?><?php echo $mine ? ' ✓' : ''; ?></span>
        <div class="ih-mkt-bar-track">
          <div class="ih-mkt-bar-fill" style="background:<?php echo $mat_palette[$code] ?? '#2563EB'; ?>" data-pct="<?php echo intval(round(100 * $cnt / $mat_demand_max)); ?>"></div>
        </div>
        <span class="ih-mkt-bar-val" data-count="<?php echo intval($cnt); ?>">0</span>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>

<!-- ── Related machines ── -->
<?php if (!empty($related_machines)): ?>
<div>
  <div class="ih-mdet-related-head">
    <h2 class="ih-mdet-related-title">Listed Machines</h2>
    <a href="<?php echo admin_url('admin.php?page=ih-machines'); ?>" class="ih-link">View All</a>
  </div>
  <div class="ih-listing-grid">
    <?php foreach ($related_machines as $m):
      include __DIR__ . '/partials/machine-card.php';
    endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
var ihMachineId   = <?php echo intval($machine['id']); ?>;
var ihMachNonce   = '<?php echo esc_js($nonce); ?>';
var ihMachAjax    = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
var ihMachinesUrl = '<?php echo esc_js(admin_url('admin.php?page=ih-machines')); ?>';

/* ── Gallery: thumb switching ── */
document.querySelectorAll('.ih-mdet-thumb').forEach(function(th){
  th.addEventListener('click', function(){
    var main = document.getElementById('mainMachImg');
    if (!main) return;
    main.style.opacity = 0;
    setTimeout(function(){ main.src = th.dataset.full; main.style.opacity = 1; }, 180);
    document.querySelectorAll('.ih-mdet-thumb').forEach(function(t){ t.classList.remove('active'); });
    th.classList.add('active');
  });
});

/* ── Animated bars (utilization + spec completeness) ── */
window.addEventListener('load', function(){
  document.querySelectorAll('.ih-mdet-util-fill,.ih-mdet-quality-fill').forEach(function(bar){
    requestAnimationFrame(function(){ bar.style.width = (parseInt(bar.dataset.pct,10) || 0) + '%'; });
  });
});

document.querySelectorAll('[data-listing-status-panel] .ih-listing-status-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    var fd = new FormData();
    fd.append('action', 'ih_update_listing_status');
    fd.append('nonce', ihMachNonce);
    fd.append('listing_id', ihMachineId);
    fd.append('listing_type', 'machine');
    fd.append('status', btn.dataset.status || 'pending');
    btn.disabled = true;
    fetch(ihMachAjax, { method:'POST', body:fd })
      .then(function(r){ return r.json(); })
      .then(function(res){
        if (!res || !res.success) throw new Error((res && res.data && res.data.message) || 'Status update failed');
        document.querySelectorAll('[data-listing-status-panel] .ih-listing-status-btn').forEach(function(b){ b.classList.remove('active'); });
        btn.classList.add('active');
        [document.getElementById('ihMachineStatusBadge'), document.getElementById('ihMachineImageStatus')].forEach(function(badge){
          if (!badge) return;
          badge.className = 'ih-listing-status-badge ' + res.data.className + (badge.id === 'ihMachineImageStatus' ? ' ih-mdet-img-avail' : '');
          badge.textContent = res.data.label;
        });
      })
      .catch(function(err){ alert(err.message || 'Status update failed'); })
      .finally(function(){ btn.disabled = false; });
  });
});

/* ── Reveal on scroll: spec cards + marketplace tiles/widgets ── */
(function(){
  var cards = document.querySelectorAll('.ih-mdet-spec-card,.ih-mkt-tile,.ih-mkt-card');
  function revealCard(card) {
    card.classList.add('in-view');
    if (typeof ihMktAnimate === 'function') {
      ihMktAnimate(card);
    }
  }
  function isVisibleNow(el) {
    var rect = el.getBoundingClientRect();
    return rect.top < (window.innerHeight || document.documentElement.clientHeight) && rect.bottom > 0;
  }
  if (!('IntersectionObserver' in window)) {
    cards.forEach(revealCard);
    return;
  }
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if (e.isIntersecting) {
        revealCard(e.target);
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.05, rootMargin: '0px 0px 48px 0px' });
  cards.forEach(function(c){
    if (isVisibleNow(c)) {
      revealCard(c);
      return;
    }
    c.classList.add('ih-mdet-reveal-pending');
    io.observe(c);
  });
})();

/* ── Marketplace widgets: count-up numbers, donut + bar animation ── */
function ihMktCountUp(el){
  var target = parseInt(el.dataset.count, 10) || 0;
  var dur = 800, start = null;
  function step(ts){
    if (!start) start = ts;
    var p = Math.min(1, (ts - start) / dur);
    el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString();
    if (p < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}
function ihMktAnimate(scope){
  scope.querySelectorAll('[data-count]').forEach(function(el){
    if (el.dataset.counted) return;
    el.dataset.counted = '1';
    ihMktCountUp(el);
  });
  scope.querySelectorAll('.ih-mkt-donut-seg').forEach(function(seg){
    var frac = parseFloat(seg.dataset.frac) || 0, circ = 2 * Math.PI * 46;
    requestAnimationFrame(function(){ seg.style.strokeDasharray = (frac * circ) + ' ' + (circ - frac * circ); });
  });
  scope.querySelectorAll('.ih-mkt-split-a,.ih-mkt-split-b,.ih-mkt-bar-fill').forEach(function(bar){
    requestAnimationFrame(function(){ bar.style.width = (parseInt(bar.dataset.pct, 10) || 0) + '%'; });
  });
}

/* ── Copy spec sheet: plain-text dump of exactly what is displayed ── */
document.getElementById('copySpecBtn')?.addEventListener('click', function(){
  var btn   = this;
  var lines = ['<?php echo esc_js($machine['title']); ?> — Machine Spec Sheet', ''];
  document.querySelectorAll('.ih-mdet-spec-row').forEach(function(row){
    var label = row.querySelector('.ih-mdet-spec-row-label');
    var val   = row.children[1];
    if (label && val) lines.push(label.textContent.trim() + ': ' + val.textContent.trim());
  });
  navigator.clipboard.writeText(lines.join('\n')).then(function(){
    btn.classList.add('copied');
    var original = btn.innerHTML;
    btn.innerHTML = '✓ Copied';
    setTimeout(function(){ btn.classList.remove('copied'); btn.innerHTML = original; }, 1600);
  });
});

/* ── Remove flow ── */
function openRemoveModal() {
  document.getElementById('removeOverlay').classList.add('show');
  document.querySelectorAll('.ih-dropdown').forEach(function(d){ d.classList.add('hidden'); });
}
document.getElementById('removeBtnTop')?.addEventListener('click', openRemoveModal);

document.getElementById('confirmRemoveBtn')?.addEventListener('click', function() {
  document.getElementById('removeOverlay').classList.remove('show');
  document.getElementById('removedOverlay').classList.add('show');
  var fd = new FormData();
  fd.append('action', 'ih_delete_machine');
  fd.append('nonce',  ihMachNonce);
  fd.append('id',     ihMachineId);
  fetch(ihMachAjax, {method:'POST', body:fd})
  .then(function(r){ return r.json(); })
  .then(function() {
    setTimeout(function() { window.location.href = ihMachinesUrl; }, 1600);
  });
});

/* ── Block owner ── */
function ihBlockMachineOwner(uid) {
  if (!uid) { alert('Owner not registered in system.'); return; }
  if (!confirm('Block this owner?')) return;
  var fd = new FormData();
  fd.append('action','ih_toggle_block'); fd.append('nonce', ihMachNonce);
  fd.append('user_id', uid); fd.append('block_action','block');
  fetch(ihMachAjax, {method:'POST', body:fd})
  .then(function(r){ return r.json(); })
  .then(function(d){ if (d.success) { alert('Owner blocked.'); location.href = ihMachinesUrl; } });
}

/* ── Close dropdowns on outside click ── */
document.addEventListener('click', function(e) {
  if (!e.target.closest('.ih-mdet-menu') && !e.target.closest('.ih-row-menu') && !e.target.closest('.ih-card-menu')) {
    document.querySelectorAll('.ih-dropdown').forEach(function(d){ d.classList.add('hidden'); });
  }
});
</script>

<?php
$content = ob_get_clean();
$title   = esc_html($machine['title']) . ' — Machine Details';
include IH_DIR . 'pages/layout.php';
