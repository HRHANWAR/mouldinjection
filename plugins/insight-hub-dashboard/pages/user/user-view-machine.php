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

$machine_id = intval( $_GET['id'] ?? $_GET['machine_id'] ?? 0 );
$user_id    = get_current_user_id();
global $wpdb;

$m = $machine_id ? $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ih_machines WHERE id=%d", $machine_id
), ARRAY_A ) : null;

if ( ! $m ) {
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

/* ── Machine meta ── */
$machine_ref = 'MCH-' . str_pad( $machine_id, 5, '0', STR_PAD_LEFT );
$is_owner    = (int)$m['owner_id'] === $user_id;

if ( ! $is_owner && function_exists( 'ih_listing_is_expired' ) && ih_listing_is_expired( $m ) ) {
    echo '<script>location.href="' . esc_url( admin_url('admin.php?page=ih-user-dashboard') ) . '";</script>';
    exit;
}

$req_status = $wpdb->get_var( $wpdb->prepare(
    "SELECT status FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND listing_id=%d AND listing_type='machine' ORDER BY id DESC LIMIT 1",
    $user_id, $machine_id
));
$req_status = $req_status ?: 'Pending';
$listing_status_meta = function_exists( 'ih_listing_status_meta' ) ? ih_listing_status_meta( $m ) : array( 'label' => ! empty( $m['available'] ) ? 'Available Now' : 'Pending Review', 'class' => ! empty( $m['available'] ) ? 'is-available' : 'is-pending' );

$fmt_listing = !empty($m['listing_date']) ? date('d M Y', strtotime($m['listing_date'])) : '—';
$fmt_expiry  = !empty($m['expiry_date'])  ? date('d M Y', strtotime($m['expiry_date']))  : '—';

$materials = function_exists('ih_machine_materials') ? ih_machine_materials($m) : [];

$util_pct = intval( preg_replace('/\D/','',$m['utilization']??'0') );

$mval = function( $key ) use ( $m ) {
    $v = trim( (string) ( $m[ $key ] ?? '' ) );
    return $v !== '' ? $v : null;
};
$mshow = function( $key ) use ( $mval ) {
    $v = $mval( $key );
    return $v !== null ? esc_html( $v ) : '—';
};

$status_colors = [
    'Approved' => ['bg'=>'#dcfce7','color'=>'#15803d','icon'=>'✓'],
    'Pending'  => ['bg'=>'#fef3c7','color'=>'#92400e','icon'=>'⏱'],
    'Rejected' => ['bg'=>'#fee2e2','color'=>'#b91c1c','icon'=>'✕'],
];
$sc = $status_colors[$req_status] ?? $status_colors['Pending'];

$default_img = get_template_directory_uri() . '/assets/images/Machine-user.png';
$imgs = array_values(array_filter([$m['image_1']??'', $m['image_2']??'', $m['image_3']??'']));
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html($m['title']); ?> — Injection Moulding</title>
<?php wp_head(); ?>
<style>
.uvm-top{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;}
.uvm-main-img{width:100%;height:300px;object-fit:cover;border-radius:14px;display:block;background:#e5ede8;}
.uvm-thumbs{display:flex;gap:10px;margin-top:10px;}
.uvm-thumb{width:90px;height:66px;object-fit:cover;border-radius:8px;cursor:pointer;border:2px solid transparent;transition:.15s;flex-shrink:0;}
.uvm-thumb.active,.uvm-thumb:hover{border-color:#1f3d2e;}
.uvm-thumb-ph{width:90px;height:66px;border-radius:8px;background:#f3f4f6;border:2px dashed #d1d5db;display:flex;align-items:center;justify-content:center;font-size:10px;color:#9ca3af;flex-shrink:0;}

.uvm-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;}
.uvm-badge-avail{background:#4ade80;color:#14532d;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;}
.uvm-badge-avail.is-pending{background:#fef3c7;color:#92400e;}
.uvm-badge-avail.is-available{background:#dcfce7;color:#15803d;}
.uvm-badge-avail.is-completed{background:#dbeafe;color:#1d4ed8;}
.uvm-badge-avail.is-rejected{background:#fee2e2;color:#b91c1c;}
.uvm-badge-type{background:#1f3d2e;color:#fff;font-size:11px;font-weight:600;padding:4px 12px;border-radius:20px;}
.uvm-status-pill{font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;}

.uvm-ref{display:inline-flex;align-items:center;gap:4px;background:#e0f2fe;color:#0369a1;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;margin-bottom:12px;}
.uvm-title{font-size:20px;font-weight:800;color:#1f3d2e;margin:0 0 4px;line-height:1.2;}
.uvm-subtitle{font-size:12px;color:#9ca3af;margin:0 0 14px;}

.uvm-dates{display:grid;grid-template-columns:1fr 1fr;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:14px;}
.uvm-date-cell{padding:10px 14px;border-right:1px solid #e5e7eb;}
.uvm-date-cell:last-child{border-right:none;}
.uvm-date-label{font-size:10px;color:#9ca3af;margin-bottom:2px;}
.uvm-date-val{font-size:13px;font-weight:700;color:#1a1a1a;}

.uvm-spec-grid{display:grid;grid-template-columns:1fr 1fr;gap:0;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:14px;}
.uvm-spec-cell{padding:11px 14px;border-right:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;}
.uvm-spec-cell:nth-child(2n){border-right:none;}
.uvm-spec-cell:nth-last-child(-n+2){border-bottom:none;}
.uvm-spec-label{font-size:10px;color:#9ca3af;margin-bottom:2px;}
.uvm-spec-val{font-size:15px;font-weight:800;color:#1f3d2e;}

.uvm-mat-row{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;}
.uvm-mat-pill{background:#c8e88e;color:#1f3d2e;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;}

.uvm-meta{display:flex;flex-direction:column;gap:6px;margin-bottom:16px;}
.uvm-meta-row{display:flex;align-items:center;gap:7px;font-size:13px;color:#6b7280;}
.uvm-meta-row svg{width:14px;height:14px;flex-shrink:0;}
.uvm-util-bar{flex:1;height:5px;background:#e5e7eb;border-radius:99px;overflow:hidden;margin-left:4px;}
.uvm-util-fill{height:100%;background:#1f3d2e;border-radius:99px;}

.uvm-actions{display:flex;gap:10px;flex-wrap:wrap;}
.uvm-btn-green{display:inline-flex;align-items:center;gap:6px;background:#1f3d2e;color:#fff;padding:10px 22px;border-radius:50px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:opacity .15s;}
.uvm-btn-green:hover{opacity:.85;}
.uvm-btn-grey{display:inline-flex;align-items:center;gap:6px;background:#f3f4f6;color:#374151;padding:10px 22px;border-radius:50px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:background .15s;}
.uvm-btn-grey:hover{background:#e5e7eb;}
.uvm-btn-wa{display:inline-flex;align-items:center;gap:6px;background:#dcfce7;color:#15803d;padding:10px 22px;border-radius:50px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:opacity .15s;}
.uvm-btn-wa:hover{opacity:.85;}

/* Owner banner */
.uvm-owner-banner{background:linear-gradient(135deg,#183524,#1f3d2e);border-radius:16px;padding:22px 24px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
.uvm-owner-label{font-size:11px;opacity:.7;margin-bottom:4px;}
.uvm-owner-value{font-size:15px;font-weight:700;}

/* Full specs */
.uvm-specs-title{font-size:18px;font-weight:800;color:#1f3d2e;margin:0 0 16px;}
.uvm-specs-2col{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.uvm-specs-3col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px;}
.uvm-spec-card{background:#fff;border:1px solid #e5ede8;border-radius:14px;padding:20px;}
.uvm-spec-card-title{font-size:14px;font-weight:700;color:#1f3d2e;margin:0 0 14px;padding-bottom:12px;border-bottom:1px solid #f3f4f6;}
.uvm-spec-card-title2{font-size:13px;font-weight:700;color:#1f3d2e;margin:18px 0 10px;}
.uvm-spec-row{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #f9fafb;font-size:13px;gap:12px;}
.uvm-spec-row:last-child{border-bottom:none;padding-bottom:0;}
.uvm-spec-lbl{color:#9ca3af;flex-shrink:0;}
.uvm-spec-val{font-weight:600;text-align:right;}
.uvm-yn-yes{color:#16a34a;font-weight:600;display:inline-flex;align-items:center;gap:4px;}
.uvm-yn-no{color:#dc2626;font-weight:600;display:inline-flex;align-items:center;gap:4px;}

@media(max-width:900px){.uvm-top{grid-template-columns:1fr;}.uvm-specs-2col,.uvm-specs-3col{grid-template-columns:1fr;}}
@media(max-width:768px){
  .ih-page-header{padding:10px 14px;}.ih-page-title{font-size:17px;}.ih-content{padding:14px 14px 40px;}
  .uvm-spec-grid{grid-template-columns:1fr;}.uvm-dates{grid-template-columns:1fr;}
  .uvm-dates .uvm-date-cell{border-right:none;border-bottom:1px solid #e5e7eb;}
  .uvm-actions{flex-direction:column;}.uvm-btn-green,.uvm-btn-grey,.uvm-btn-wa{justify-content:center;}
}
</style>
</head>
<body>
<?php
$ih_shell_class = 'ih-shell ih-figma-dashboard is-user ih-shell--float-nav ih-rd ih-user';
include IH_DIR . 'pages/partials/ih-user-shell-start.php';
?>

<?php include IH_DIR . 'pages/partials/ih-user-shell-header.php'; ?>

<!-- BODY -->
<div class="ih-body">

  <main class="ih-main">
    <div class="ih-page-header">
      <div>
        <div class="ih-page-title">Machine Details</div>
        <div class="ih-page-sub">
          <?php echo esc_html($m['title']); ?> ·
          <span style="background:#e0f2fe;color:#0369a1;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;"><?php echo esc_html($machine_ref); ?></span>
        </div>
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
      <div class="uvm-owner-banner">
        <div>
          <div class="uvm-owner-label">Your Machine Listing Status</div>
          <div class="uvm-owner-value"><?php echo esc_html($req_status); ?> — <?php echo esc_html($machine_ref); ?></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <a href="<?php echo admin_url('admin.php?page=ih-user-edit-machine&machine_id='.$machine_id); ?>"
             style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);color:#fff;padding:9px 18px;border-radius:50px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid rgba(255,255,255,.3);">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit Machine
          </a>
          <!-- <?php if ($wa_link): ?>
          <a href="<?php echo esc_url($wa_link); ?>" target="_blank"
             style="display:inline-flex;align-items:center;gap:6px;background:#fff;color:#1f3d2e;padding:9px 18px;border-radius:50px;font-size:12px;font-weight:600;text-decoration:none;">
            💬 Contact Admin
          </a>
          <?php endif; ?> -->
        </div>
      </div>
      <?php endif; ?>

      <!-- Top: images + info -->
      <div class="uvm-top">

        <!-- Images -->
        <div>
          <?php if (!empty($imgs)): ?>
            <img id="uvmMainImg" src="<?php echo esc_url($imgs[0]); ?>" alt="<?php echo esc_attr($m['title']); ?>" class="uvm-main-img">
            <div class="uvm-thumbs">
              <?php foreach ($imgs as $idx => $img): ?>
              <img src="<?php echo esc_url($img); ?>"
                   class="uvm-thumb<?php echo $idx===0?' active':''; ?>"
                   onclick="document.getElementById('uvmMainImg').src=this.src;document.querySelectorAll('.uvm-thumb').forEach(t=>t.classList.remove('active'));this.classList.add('active')"
                   alt="">
              <?php endforeach; ?>
              <?php for ($p=count($imgs);$p<3;$p++): ?><div class="uvm-thumb-ph">No image</div><?php endfor; ?>
            </div>
          <?php else: ?>
            <!-- No image placeholder -->
            <div style="width:100%;height:300px;border-radius:14px;background:#f0f7f4;border:2px dashed #c8e88e;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;">
              <img src="<?php echo esc_url($default_img); ?>" style="width:48px;height:48px;opacity:.4;" alt="">
              <span style="font-size:13px;color:#9ca3af;font-weight:500;">No images uploaded</span>
            </div>
            <div class="uvm-thumbs">
              <?php for ($p=0;$p<3;$p++): ?><div class="uvm-thumb-ph">No image</div><?php endfor; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Info -->
        <div>
          <div class="uvm-badges">
            <span class="uvm-badge-avail <?php echo esc_attr( $listing_status_meta['class'] ); ?>"><?php echo esc_html( $listing_status_meta['label'] ); ?></span>
            <?php if ($m['machine_type']): ?>
            <span class="uvm-badge-type"><?php echo esc_html($m['machine_type']); ?></span>
            <?php endif; ?>
            <span class="uvm-status-pill" style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>;">
              <?php echo $sc['icon'] . ' ' . esc_html($req_status); ?>
            </span>
          </div>

          <span class="uvm-ref">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            <?php echo esc_html($machine_ref); ?>
          </span>

          <h1 class="uvm-title"><?php echo esc_html($m['title']); ?></h1>
          <p class="uvm-subtitle">
            <?php
            $sub = [];
            if ($m['year_manufacture']) $sub[] = 'Year: '.$m['year_manufacture'];
            if ($m['identical_count'] > 1) $sub[] = $m['identical_count'].' identical units';
            echo esc_html(implode(' · ', $sub));
            ?>
          </p>

          <!-- Dates -->
          <div class="uvm-dates">
            <div class="uvm-date-cell"><div class="uvm-date-label">Listing Date</div><div class="uvm-date-val"><?php echo esc_html($fmt_listing); ?></div></div>
            <div class="uvm-date-cell"><div class="uvm-date-label">Expiry Date</div><div class="uvm-date-val"><?php echo esc_html($fmt_expiry); ?></div></div>
          </div>

          <!-- 4 spec cells -->
          <div class="uvm-spec-grid">
            <div class="uvm-spec-cell"><div class="uvm-spec-label">Clamping Force</div><div class="uvm-spec-val"><?php echo $mshow('clamping_force'); ?></div></div>
            <div class="uvm-spec-cell"><div class="uvm-spec-label">Shot Size</div><div class="uvm-spec-val"><?php echo $mshow('shot_size'); ?></div></div>
            <div class="uvm-spec-cell"><div class="uvm-spec-label">Max Part Weight</div><div class="uvm-spec-val"><?php echo $mshow('max_part_weight'); ?></div></div>
            <div class="uvm-spec-cell"><div class="uvm-spec-label">Avg Cycle Time</div><div class="uvm-spec-val"><?php echo $mshow('avg_cycle_time'); ?></div></div>
          </div>

          <!-- Materials -->
          <?php if ($materials): ?>
          <div style="margin-bottom:14px;">
            <div style="font-size:11px;color:#9ca3af;margin-bottom:7px;font-weight:500;">Supported Materials</div>
            <div class="uvm-mat-row">
              <?php foreach ($materials as $mat): ?><span class="uvm-mat-pill"><?php echo esc_html($mat); ?></span><?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Meta -->
          <div class="uvm-meta">
            <?php if ($m['location']): ?>
            <div class="uvm-meta-row">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <?php echo esc_html($m['location']); ?>
            </div>
            <?php endif; ?>
            <?php if ($m['operating_hours']): ?>
            <div class="uvm-meta-row">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Operating: <?php echo esc_html($m['operating_hours']); ?>
            </div>
            <?php endif; ?>
            <?php if ($m['utilization']): ?>
            <div class="uvm-meta-row">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
              Utilization: <?php echo esc_html($m['utilization']); ?>
              <?php if ($util_pct > 0): ?>
              <div class="uvm-util-bar"><div class="uvm-util-fill" style="width:<?php echo $util_pct; ?>%"></div></div>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Actions -->
          <div class="uvm-actions">
            <?php if ($is_owner): ?>
            <a href="<?php echo admin_url('admin.php?page=ih-user-edit-machine&machine_id='.$machine_id); ?>" class="uvm-btn-green">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit Machine
            </a>
            <?php endif; ?>
            <!-- <?php if ($wa_link): ?>
            <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="uvm-btn-wa">
              <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a5.6 5.6 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
              Contact Admin
            </a>
            <?php else: ?>
            <a href="<?php echo admin_url('admin.php?page=ih-user-messages'); ?>" class="uvm-btn-green">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              Messages
            </a>
            <?php endif; ?> -->
            <a href="<?php echo admin_url('admin.php?page=ih-user-dashboard'); ?>" class="uvm-btn-grey">← My Listings</a>
          </div>
        </div>
      </div>

      <!-- Full Specs -->
      <h2 class="uvm-specs-title">Full Specifications</h2>

      <?php
      $yn_yes = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>';
      $yn_no  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
      ?>

      <div class="uvm-specs-2col">
        <!-- Core Processing -->
        <div class="uvm-spec-card">
          <div class="uvm-spec-card-title">Core Processing Specs</div>
          <?php foreach ([
            'Clamping Force'          => $mval('clamping_force'),
            'Shot Size'               => $mval('shot_size'),
            'Screw Diameter'          => $mval('screw_diameter'),
            'Max Injection Pressure'  => $mval('max_injection_pressure'),
            'Tie Bar Spacing'         => $mval('tie_bar_spacing'),
            'Opening Stroke'          => $mval('opening_stroke'),
            'Max Mould Height'        => $mval('max_mould_height'),
            'Min Mould Height'        => $mval('min_mould_height'),
          ] as $lbl => $val): ?>
          <div class="uvm-spec-row"><span class="uvm-spec-lbl"><?php echo esc_html( $lbl ); ?></span><span class="uvm-spec-val"><?php echo $val !== null ? esc_html( $val ) : '—'; ?></span></div>
          <?php endforeach; ?>
        </div>

        <!-- Part + Production -->
        <div class="uvm-spec-card">
          <div class="uvm-spec-card-title">Part Capability</div>
          <?php foreach ([
            'Max Part Weight'     => $m['max_part_weight']     ?: '—',
            'Max Part Dimensions' => $m['max_part_dimensions']  ?: '—',
            'Tolerance'           => $m['tolerance']            ?: '—',
          ] as $lbl => $val): ?>
          <div class="uvm-spec-row"><span class="uvm-spec-lbl"><?php echo esc_html($lbl); ?></span><span class="uvm-spec-val"><?php echo esc_html($val); ?></span></div>
          <?php endforeach; ?>

          <div class="uvm-spec-card-title2">Production Capability</div>
          <?php foreach ([
            'Batch Size'         => $m['batch_size']         ?: '—',
            'Min Order Qty'      => $m['min_order_qty']      ?: '—',
            'Max Monthly Output' => $m['max_monthly_output'] ?: '—',
            'Avg Cycle Time'     => $m['avg_cycle_time']     ?: '—',
          ] as $lbl => $val): ?>
          <div class="uvm-spec-row"><span class="uvm-spec-lbl"><?php echo esc_html($lbl); ?></span><span class="uvm-spec-val"><?php echo esc_html($val); ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="uvm-specs-3col">
        <!-- Automation -->
        <div class="uvm-spec-card">
          <div class="uvm-spec-card-title">Automation & Features</div>
          <div class="uvm-spec-row"><span class="uvm-spec-lbl">Automation Level</span><span class="uvm-spec-val"><?php echo esc_html($m['automation_level']?:'—'); ?></span></div>
          <?php foreach (['Robot Integration'=>$m['robot_integration'],'Multi Cavity'=>$m['multi_cavity']] as $lbl=>$val):
            $y = $val==='Yes'; ?>
          <div class="uvm-spec-row"><span class="uvm-spec-lbl"><?php echo esc_html($lbl); ?></span><span class="<?php echo $y?'uvm-yn-yes':'uvm-yn-no'; ?>"><?php echo $y?$yn_yes:$yn_no; ?> <?php echo esc_html($val?:'—'); ?></span></div>
          <?php endforeach; ?>

          <div class="uvm-spec-card-title2">Quality & Compliance</div>
          <?php foreach ([
            'Certifications'        => $m['certifications']        ?: '—',
            'QC Tools'              => $m['qc_tools']              ?: '—',
            'Tolerance Consistency' => $m['tolerance_consistency'] ?: '—',
          ] as $lbl => $val): ?>
          <div class="uvm-spec-row"><span class="uvm-spec-lbl"><?php echo esc_html($lbl); ?></span><span class="uvm-spec-val"><?php echo esc_html($val); ?></span></div>
          <?php endforeach; ?>
        </div>

        <!-- Advanced -->
        <div class="uvm-spec-card">
          <div class="uvm-spec-card-title">Advanced Capabilities</div>
          <?php foreach ([
            'Overmoulding'      => $m['overmoulding'],
            'Insert Moulding'   => $m['insert_moulding'],
            'IML'               => $m['iml'],
            'Gas-Assisted'      => $m['gas_assisted'],
            'Thin-Wall Moulding'=> $m['thin_wall'],
          ] as $lbl => $val):
            $y = $val==='Yes'; ?>
          <div class="uvm-spec-row"><span class="uvm-spec-lbl"><?php echo esc_html($lbl); ?></span><span class="<?php echo $y?'uvm-yn-yes':'uvm-yn-no'; ?>"><?php echo $y?$yn_yes:$yn_no; ?> <?php echo esc_html($val?:'No'); ?></span></div>
          <?php endforeach; ?>
        </div>

        <!-- Materials -->
        <div class="uvm-spec-card">
          <div class="uvm-spec-card-title">Materials</div>
          <?php if ($materials): ?>
          <div class="uvm-mat-row" style="margin-bottom:14px;">
            <?php foreach ($materials as $mat): ?><span class="uvm-mat-pill"><?php echo esc_html($mat); ?></span><?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php foreach ([
            'Engineering Grade' => $m['engineering_grade'] ?? 'No',
            'Recycled Materials'=> $m['recycled_materials'] ?? 'No',
          ] as $lbl => $val):
            $y = $val==='Yes'; ?>
          <div class="uvm-spec-row"><span class="uvm-spec-lbl"><?php echo esc_html($lbl); ?></span><span class="<?php echo $y?'uvm-yn-yes':'uvm-yn-no'; ?>"><?php echo $y?$yn_yes:$yn_no; ?> <?php echo esc_html($val); ?></span></div>
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
</script>
<?php wp_footer(); ?>
</body>
</html>