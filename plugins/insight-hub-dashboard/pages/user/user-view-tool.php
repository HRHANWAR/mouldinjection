<?php
defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
    echo '<script>location.href="' . wp_login_url( admin_url('admin.php?page=ih-user-dashboard') ) . '";</script>';
    exit;
}
if ( current_user_can('administrator') ) {
    echo '<script>location.href="' . admin_url('admin.php?page=ih-dashboard') . '";</script>';
    exit;
}

$tool_id = intval( $_GET['id'] ?? $_GET['tool_id'] ?? 0 );
$user_id = get_current_user_id();
global $wpdb;

$t = $tool_id ? $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ih_tools WHERE id=%d", $tool_id
), ARRAY_A ) : null;

if ( ! $t ) {
    echo '<script>location.href="' . admin_url('admin.php?page=ih-user-dashboard') . '";</script>';
    exit;
}

/* ── Layout vars ── */
$user         = wp_get_current_user();
$wa_number    = function_exists('ih_get_admin_whatsapp_number') ? ih_get_admin_whatsapp_number() : '';
$wa_link      = $wa_number ? 'https://wa.me/'.$wa_number : '';
$email_hash   = md5( strtolower( trim( $user->user_email ) ) );
$gravatar_url = 'https://www.gravatar.com/avatar/' . $email_hash . '?s=72&d=404';
$fallback_url = 'https://ui-avatars.com/api/?name='.rawurlencode($user->display_name ?: 'U').'&background=1f3d2e&color=c8e88e&size=72&bold=true&rounded=true&length=2';
$notifs       = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND status='Approved' ORDER BY id DESC LIMIT 10", $user_id
), ARRAY_A ) ?: [];
$notif_count  = count($notifs);
$unread_msgs  = (int)$wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(unread) FROM {$wpdb->prefix}ih_threads WHERE user_id=%d", $user_id
));

/* ── Tool meta ── */
$tool_ref  = 'TL-' . str_pad( $tool_id, 5, '0', STR_PAD_LEFT );
$is_owner  = (int)$t['owner_id'] === $user_id;

$req_status = $wpdb->get_var( $wpdb->prepare(
    "SELECT status FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND listing_id=%d AND listing_type='tool' ORDER BY id DESC LIMIT 1",
    $user_id, $tool_id
));
$req_status = $req_status ?: 'Pending';
$listing_status_meta = function_exists( 'ih_listing_status_meta' ) ? ih_listing_status_meta( $t ) : array( 'label' => ! empty( $t['available'] ) ? 'Available Now' : 'Pending Review', 'class' => ! empty( $t['available'] ) ? 'is-available' : 'is-pending' );

$fmt_listing = !empty($t['listing_date']) ? date('d M Y', strtotime($t['listing_date'])) : '—';
$fmt_expiry  = !empty($t['expiry_date'])  ? date('d M Y', strtotime($t['expiry_date']))  : '—';

$tol_pills = [];
if (!empty($t['tolerance_abs'])) $tol_pills[] = 'ABS';
if (!empty($t['tolerance_pp']))  $tol_pills[] = 'PP';
if (!empty($t['tolerance_pe']))  $tol_pills[] = 'PE';

$status_colors = [
    'Approved' => ['bg'=>'#dcfce7','color'=>'#15803d','icon'=>'✓'],
    'Pending'  => ['bg'=>'#fef3c7','color'=>'#92400e','icon'=>'⏱'],
    'Rejected' => ['bg'=>'#fee2e2','color'=>'#b91c1c','icon'=>'✕'],
];
$sc = $status_colors[$req_status] ?? $status_colors['Pending'];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html($t['title']); ?> — Injection Moulding</title>
<?php wp_head(); ?>
<style>
.uvt-top{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;}
.uvt-main-img{width:100%;height:300px;object-fit:cover;border-radius:14px;display:block;background:#e5ede8;}
.uvt-thumbs{display:flex;gap:10px;margin-top:10px;}
.uvt-thumb{width:90px;height:66px;object-fit:cover;border-radius:8px;cursor:pointer;border:2px solid transparent;transition:.15s;flex-shrink:0;}
.uvt-thumb.active,.uvt-thumb:hover{border-color:#1f3d2e;}
.uvt-thumb-ph{width:90px;height:66px;border-radius:8px;background:#f3f4f6;border:2px dashed #d1d5db;display:flex;align-items:center;justify-content:center;font-size:10px;color:#9ca3af;flex-shrink:0;}

.uvt-info{}
.uvt-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;}
.uvt-badge-avail{background:#4ade80;color:#14532d;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;}
.uvt-badge-avail.is-pending{background:#fef3c7;color:#92400e;}
.uvt-badge-avail.is-available{background:#dcfce7;color:#15803d;}
.uvt-badge-avail.is-completed{background:#dbeafe;color:#1d4ed8;}
.uvt-badge-avail.is-rejected{background:#fee2e2;color:#b91c1c;}
.uvt-badge-type{background:#1f3d2e;color:#fff;font-size:11px;font-weight:600;padding:4px 12px;border-radius:20px;}
.uvt-status-pill{font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;}

.uvt-title{font-size:20px;font-weight:800;color:#1f3d2e;margin:0 0 4px;line-height:1.2;}
.uvt-subtitle{font-size:13px;color:#9ca3af;margin:0 0 10px;}
.uvt-desc{font-size:13px;color:#6b7280;line-height:1.6;margin:0 0 16px;}

.uvt-ref{display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;margin-bottom:12px;}

.uvt-dates{display:grid;grid-template-columns:1fr 1fr;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:14px;}
.uvt-date-cell{padding:10px 14px;border-right:1px solid #e5e7eb;}
.uvt-date-cell:last-child{border-right:none;}
.uvt-date-label{font-size:10px;color:#9ca3af;margin-bottom:2px;}
.uvt-date-val{font-size:13px;font-weight:700;color:#1a1a1a;}

.uvt-spec-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;}
.uvt-spec-box{background:rgba(200,232,142,.18);border:1px solid rgba(200,232,142,.5);border-radius:10px;padding:10px 13px;display:flex;align-items:center;gap:10px;}
.uvt-spec-box svg{width:17px;height:17px;color:#1f3d2e;flex-shrink:0;}
.uvt-spec-label{font-size:10px;color:#6b7280;}
.uvt-spec-val{font-size:13px;font-weight:700;color:#1a1a1a;}

.uvt-location-row{display:flex;flex-direction:column;gap:5px;margin-bottom:16px;font-size:13px;color:#6b7280;}
.uvt-location-row>div{display:flex;align-items:center;gap:6px;}
.uvt-location-row svg{width:13px;height:13px;flex-shrink:0;}

.uvt-actions{display:flex;gap:10px;flex-wrap:wrap;}
.uvt-btn-primary{display:inline-flex;align-items:center;gap:6px;background:#1f3d2e;color:#fff;padding:10px 22px;border-radius:50px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:opacity .15s;}
.uvt-btn-primary:hover{opacity:.85;}
.uvt-btn-secondary{display:inline-flex;align-items:center;gap:6px;background:#f3f4f6;color:#374151;padding:10px 22px;border-radius:50px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:background .15s;}
.uvt-btn-secondary:hover{background:#e5e7eb;}
.uvt-btn-wa{display:inline-flex;align-items:center;gap:6px;background:#dcfce7;color:#15803d;padding:10px 22px;border-radius:50px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:opacity .15s;}
.uvt-btn-wa:hover{opacity:.85;}

/* Full specs */
.uvt-specs-title{font-size:18px;font-weight:800;color:#1f3d2e;margin:0 0 16px;}
.uvt-specs-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:28px;}
.uvt-specs-card{background:#fff;border:1px solid #e5ede8;border-radius:14px;padding:20px;}
.uvt-specs-card-title{font-size:14px;font-weight:700;color:#1f3d2e;margin:0 0 14px;padding-bottom:12px;border-bottom:1px solid #f3f4f6;}
.uvt-specs-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #f9fafb;font-size:13px;gap:12px;}
.uvt-specs-row:last-child{border-bottom:none;padding-bottom:0;}
.uvt-specs-label{color:#9ca3af;flex-shrink:0;}
.uvt-specs-val{font-weight:600;text-align:right;}
.uvt-yn-yes{color:#16a34a;font-weight:600;display:flex;align-items:center;gap:4px;}
.uvt-yn-no{color:#dc2626;font-weight:600;display:flex;align-items:center;gap:4px;}
.uvt-tol-pills{display:flex;gap:6px;flex-wrap:wrap;padding:9px 0;border-bottom:1px solid #f9fafb;}
.uvt-tol-pill{background:#c8e88e;color:#1f3d2e;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;}

/* Owner card if is owner */
.uvt-owner-card{background:linear-gradient(135deg,#183524,#1f3d2e);border-radius:16px;padding:22px 24px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
.uvt-owner-label{font-size:11px;opacity:.7;margin-bottom:4px;}
.uvt-owner-value{font-size:15px;font-weight:700;}

@media(max-width:900px){.uvt-top{grid-template-columns:1fr;}.uvt-specs-grid{grid-template-columns:1fr;}}
@media(max-width:768px){
  .ih-page-header{padding:10px 14px;}.ih-page-title{font-size:17px;}.ih-content{padding:14px 14px 40px;}
  .uvt-spec-grid{grid-template-columns:1fr;}.uvt-dates{grid-template-columns:1fr;}.uvt-dates .uvt-date-cell{border-right:none;border-bottom:1px solid #e5e7eb;}
}
@media(max-width:480px){.uvt-top{gap:16px;}.uvt-actions{flex-direction:column;}.uvt-btn-primary,.uvt-btn-secondary,.uvt-btn-wa{justify-content:center;}}
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
        <div class="ih-page-title">Tool Details</div>
        <div class="ih-page-sub"><?php echo esc_html($t['title']); ?> · <span style="background:#fef3c7;color:#92400e;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;"><?php echo esc_html($tool_ref); ?></span></div>
      </div>
      <a href="<?php echo admin_url('admin.php?page=ih-user-dashboard'); ?>"
         style="display:inline-flex;align-items:center;gap:6px;background:#f3f4f6;color:#374151;padding:8px 16px;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        My Listings
      </a>
    </div>

    <div class="ih-content">

      <?php if ($is_owner): ?>
      <!-- Owner banner -->
      <div class="uvt-owner-card">
        <div>
          <div class="uvt-owner-label">Your Listing Status</div>
          <div class="uvt-owner-value"><?php echo esc_html($req_status); ?> — <?php echo esc_html($tool_ref); ?></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <a href="<?php echo admin_url('admin.php?page=ih-user-edit-tool&tool_id='.$tool_id); ?>"
             style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);color:#fff;padding:9px 18px;border-radius:50px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid rgba(255,255,255,.3);">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit Tool
          </a>
          <button type="button" id="uvtDuplicateBtn"
             data-tool-id="<?php echo (int) $tool_id; ?>"
             data-nonce="<?php echo esc_attr( wp_create_nonce('ih_nonce') ); ?>"
             data-ajax="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>"
             style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);color:#fff;padding:9px 18px;border-radius:50px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid rgba(255,255,255,.3);">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Duplicate this listing
          </button>
          <!-- <?php if ($wa_link): ?>
          <a href="<?php echo esc_url($wa_link); ?>" target="_blank"
             style="display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1f3d2e;padding:9px 18px;border-radius:50px;font-size:12px;font-weight:600;text-decoration:none;">
            💬 Contact Admin
          </a>
          <?php endif; ?> -->
        </div>
      </div>
      <?php endif; ?>

      <!-- Top: image + info -->
      <div class="uvt-top">

        <!-- Images -->
        <div>
          <?php $imgs = array_filter([$t['image_1']??'', $t['image_2']??'', $t['image_3']??'']); ?>
          <?php if (!empty($imgs)): ?>
            <img id="uvtMainImg" src="<?php echo esc_url(reset($imgs)); ?>" alt="<?php echo esc_attr($t['title']); ?>" class="uvt-main-img">
            <div class="uvt-thumbs">
              <?php foreach (array_values($imgs) as $idx => $img): ?>
              <img src="<?php echo esc_url($img); ?>"
                   class="uvt-thumb<?php echo $idx===0?' active':''; ?>"
                   onclick="document.getElementById('uvtMainImg').src=this.src;document.querySelectorAll('.uvt-thumb').forEach(t=>t.classList.remove('active'));this.classList.add('active')"
                   alt="">
              <?php endforeach; ?>
              <?php for ($p=count($imgs);$p<3;$p++): ?><div class="uvt-thumb-ph">No image</div><?php endfor; ?>
            </div>
          <?php else: ?>
            <div style="width:100%;height:300px;border-radius:14px;background:#f0f7f4;border:2px dashed #c8e88e;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;">
              <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/user-tools.png"
                   style="width:48px;height:48px;opacity:.4;" alt="">
              <span style="font-size:13px;color:#9ca3af;font-weight:500;">No images uploaded</span>
            </div>
            <div class="uvt-thumbs">
              <?php for ($p=0;$p<3;$p++): ?><div class="uvt-thumb-ph">No image</div><?php endfor; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="uvt-info">
          <div class="uvt-badges">
            <span class="uvt-badge-avail <?php echo esc_attr( $listing_status_meta['class'] ); ?>"><?php echo esc_html( $listing_status_meta['label'] ); ?></span>
            <?php if ($t['mould_type']): ?>
            <span class="uvt-badge-type"><?php echo esc_html($t['mould_type']); ?></span>
            <?php endif; ?>
            <span class="uvt-status-pill" style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>;">
              <?php echo $sc['icon'] . ' ' . esc_html($req_status); ?>
            </span>
          </div>

          <span class="uvt-ref">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            <?php echo esc_html($tool_ref); ?>
          </span>

          <h1 class="uvt-title"><?php echo esc_html($t['title']); ?></h1>
          <?php if ($t['part_name']): ?><p class="uvt-subtitle"><?php echo esc_html($t['part_name']); ?></p><?php endif; ?>
          <?php if ($t['part_description']): ?><p class="uvt-desc"><?php echo esc_html($t['part_description']); ?></p><?php endif; ?>

          <!-- Dates -->
          <div class="uvt-dates">
            <div class="uvt-date-cell">
              <div class="uvt-date-label">Listing Date</div>
              <div class="uvt-date-val"><?php echo esc_html($fmt_listing); ?></div>
            </div>
            <div class="uvt-date-cell">
              <div class="uvt-date-label">Expiry Date</div>
              <div class="uvt-date-val"><?php echo esc_html($fmt_expiry); ?></div>
            </div>
          </div>

          <!-- Spec boxes -->
          <div class="uvt-spec-grid">
            <div class="uvt-spec-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
              <div><div class="uvt-spec-label">Cavities</div><div class="uvt-spec-val"><?php echo esc_html($t['num_cavities_spec'] ?: $t['num_cavities'] ?: '4'); ?></div></div>
            </div>
            <div class="uvt-spec-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              <div><div class="uvt-spec-label">Material</div><div class="uvt-spec-val"><?php echo esc_html($t['material'] ?: 'PC'); ?></div></div>
            </div>
            <div class="uvt-spec-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>
              <div><div class="uvt-spec-label">Clamp Required</div><div class="uvt-spec-val"><?php echo esc_html($t['clamping_required'] ?: '70T'); ?></div></div>
            </div>
            <div class="uvt-spec-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a3 3 0 0 0-3 3v1H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3V5a3 3 0 0 0-3-3z"/></svg>
              <div><div class="uvt-spec-label">Part Weight</div><div class="uvt-spec-val"><?php echo esc_html($t['part_weight'] ? $t['part_weight'].'g' : '—'); ?></div></div>
            </div>
          </div>

          <!-- Location -->
          <div class="uvt-location-row">
            <div>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <?php echo esc_html($t['location'] ?: '—'); ?>
            </div>
            <?php if ($t['owner_name'] || $t['owner_id']): ?>
            <div>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <?php echo esc_html($t['owner_name'] ?: $user->display_name); ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Actions -->
          <div class="uvt-actions">
            <?php if ($is_owner): ?>
            <a href="<?php echo admin_url('admin.php?page=ih-user-edit-tool&tool_id='.$tool_id); ?>" class="uvt-btn-primary">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit Listing
            </a>
            <?php endif; ?>
            <!-- <?php if ($wa_link): ?>
            <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="uvt-btn-wa">
              <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a5.6 5.6 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
              Contact Admin
            </a>
            <?php else: ?>
            <a href="<?php echo admin_url('admin.php?page=ih-user-messages'); ?>" class="uvt-btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              Messages
            </a>
            <?php endif; ?> -->
            <a href="<?php echo admin_url('admin.php?page=ih-user-dashboard'); ?>" class="uvt-btn-secondary">
              ← My Listings
            </a>
          </div>
        </div>
      </div>

      <!-- Full Specs -->
      <h2 class="uvt-specs-title">Full Specifications</h2>
      <div class="uvt-specs-grid">

        <!-- Mould -->
        <div class="uvt-specs-card">
          <div class="uvt-specs-card-title">Mould Specifications</div>
          <?php foreach ([
            'Mould Type'         => $t['mould_type']       ?: '—',
            'Mould Material'     => $t['mould_material']   ?: '—',
            'Condition'          => $t['mould_condition']  ?: '—',
            'Number of Cavities' => $t['num_cavities_spec'] ?: $t['num_cavities'] ?: '—',
            'Ejector Type'       => $t['ejector_type']     ?: '—',
            'Nozzle Type'        => $t['nozzle_type']      ?: '—',
          ] as $lbl => $val): ?>
          <div class="uvt-specs-row">
            <span class="uvt-specs-label"><?php echo esc_html($lbl); ?></span>
            <span class="uvt-specs-val"><?php echo esc_html($val); ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Part -->
        <div class="uvt-specs-card">
          <div class="uvt-specs-card-title">Part Specifications</div>
          <?php foreach ([
            'Part Name'      => $t['part_name']       ?: '—',
            'Dimensions'     => $t['part_dimensions']  ?: '—',
            'Part Weight'    => $t['part_weight'] ? $t['part_weight'].'g' : '—',
            'Material'       => $t['material']         ?: '—',
            'Material Grade' => $t['material_grade']  ?: '—',
            'Colour'         => $t['colour']           ?: '—',
          ] as $lbl => $val): ?>
          <div class="uvt-specs-row">
            <span class="uvt-specs-label"><?php echo esc_html($lbl); ?></span>
            <span class="uvt-specs-val"><?php echo esc_html($val); ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Production -->
        <div class="uvt-specs-card">
          <div class="uvt-specs-card-title">Production Info</div>
          <?php foreach ([
            'Annual Volume'     => $t['annual_volume']     ?: '—',
            'Cycle Time'        => $t['cycle_time']        ?: '—',
            'Min Order Qty'     => $t['min_order_qty']     ?: '—',
            'Clamping Required' => $t['clamping_required'] ?: '—',
            'Compatible Specs'  => $t['compatible_specs']  ?: '—',
          ] as $lbl => $val): ?>
          <div class="uvt-specs-row">
            <span class="uvt-specs-label"><?php echo esc_html($lbl); ?></span>
            <span class="uvt-specs-val"><?php echo esc_html($val); ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Tooling Features -->
        <div class="uvt-specs-card">
          <div class="uvt-specs-card-title">Tooling Features</div>
          <?php if ($tol_pills): ?>
          <div class="uvt-tol-pills">
            <?php foreach ($tol_pills as $pill): ?><span class="uvt-tol-pill"><?php echo esc_html($pill); ?></span><?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php
          $yn_rows = [
            'Water Cooled' => $t['water_cooled'] ?? 'No',
            'Suck Pump'    => $t['suck_pump']    ?? 'No',
            'Food Grade'   => $t['food_grade']   ?? 'No',
            'Medical Grade'=> $t['medical_grade']?? 'No',
          ];
          $icon_yes = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>';
          $icon_no  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
          foreach ($yn_rows as $lbl => $val):
            $yes = $val === 'Yes';
          ?>
          <div class="uvt-specs-row">
            <span class="uvt-specs-label"><?php echo esc_html($lbl); ?></span>
            <span class="<?php echo $yes ? 'uvt-yn-yes' : 'uvt-yn-no'; ?>"><?php echo $yes ? $icon_yes : $icon_no; ?> <?php echo esc_html($val); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div><!-- /.ih-content -->
  </main>
</div>
</div>

<script>function ihToggleNotif(e){e.stopPropagation();var b=document.getElementById('ihNotifBox');b.style.display=b.style.display==='block'?'none':'block';var d=document.getElementById('ihNotifDot');if(d)d.remove();}
function ihToggleAccount(e){e.stopPropagation();var b=document.getElementById('ihAccountBox');b.style.display=b.style.display==='block'?'none':'block';}
document.addEventListener('click',function(e){
    if(!e.target.closest('#ihNotifWrap')){var b=document.getElementById('ihNotifBox');if(b)b.style.display='none';}
    if(!e.target.closest('#ihAccountWrap')){var b=document.getElementById('ihAccountBox');if(b)b.style.display='none';}
});
document.addEventListener('DOMContentLoaded',function(){
    var profileBtn=document.querySelector('.ih-profile-dropdown summary');
    var sidebar=document.querySelector('.ih-sidebar');
    if(profileBtn&&sidebar){profileBtn.addEventListener('click',function(){if(window.innerWidth<=768||!sidebar.classList.contains('expanded'))sidebar.classList.add('expanded');});}
});
(function(){
    var btn=document.getElementById('uvtDuplicateBtn');
    if(!btn)return;
    btn.addEventListener('click',function(){
        if(btn.dataset.busy)return;
        btn.dataset.busy='1';
        var original=btn.innerHTML;
        btn.style.opacity='0.7';
        btn.innerHTML='Duplicating…';
        var body=new URLSearchParams();
        body.append('action','ih_duplicate_tool');
        body.append('nonce',btn.dataset.nonce);
        body.append('tool_id',btn.dataset.toolId);
        fetch(btn.dataset.ajax,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
            .then(function(r){return r.json();})
            .then(function(res){
                if(res&&res.success&&res.data&&res.data.edit_url){
                    window.location.href=res.data.edit_url;
                    return;
                }
                alert((res&&res.data&&res.data.message)?res.data.message:'Could not duplicate this listing.');
                btn.innerHTML=original;btn.style.opacity='';btn.dataset.busy='';
            })
            .catch(function(){
                alert('Could not duplicate this listing. Please try again.');
                btn.innerHTML=original;btn.style.opacity='';btn.dataset.busy='';
            });
    });
})();
</script>
<?php wp_footer(); ?>
</body>
</html>