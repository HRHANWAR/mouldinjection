<?php
/**
 * Insight Hub — Platform Settings (admin)
 * All platform options in one place: communication, notifications,
 * realtime messaging and default listing images.
 * Saved through the WordPress Settings API (options.php), so every value is
 * sanitised by the callbacks registered in insight-hub-dashboard.php.
 * URL: admin.php?page=ih-settings
 */
defined( 'ABSPATH' ) || exit;

$wa_num      = get_option( 'ih_whatsapp_number', '' );
$notify_mail = get_option( 'ih_admin_notify_email', '' );
$ws_url      = get_option( 'ih_ws_url', '' );
$def_machine = get_option( 'ih_default_machine_image', '' );
$def_tool    = get_option( 'ih_default_tool_image', '' );
$saved       = isset( $_GET['settings-updated'] );

ob_start();
?>
<div class="ih-set-page">

  <div class="ih-page-header">
    <div>
      <h2 class="ih-page-title">Platform Settings</h2>
      <p class="ih-page-sub">Configure communication, notifications, realtime messaging and listing defaults. Changes are logged to the Activity trail.</p>
    </div>
  </div>

  <?php if ( $saved ) : ?>
  <div class="ih-set-banner" id="ihSetBanner">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
    Settings saved successfully.
  </div>
  <?php endif; ?>

  <form method="POST" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="ih-set-form">
    <?php settings_fields( 'ih_settings_group' ); ?>

    <!-- ── Communication ── -->
    <section class="ih-card ih-set-card">
      <header class="ih-set-card-head">
        <span class="ih-set-ico" style="--ico:#15803d;">💬</span>
        <div>
          <h3>Communication</h3>
          <p>WhatsApp contact shared with users after their request is approved.</p>
        </div>
      </header>
      <div class="ih-set-fields">
        <label class="ih-set-field">
          <span class="ih-set-label">Admin WhatsApp number</span>
          <input type="text" name="ih_whatsapp_number" value="<?php echo esc_attr( $wa_num ); ?>"
                 placeholder="447900123456 — country code, no +" autocomplete="off">
          <span class="ih-set-hint">Approved users are redirected to this number. Digits only, including country code.</span>
        </label>
      </div>
    </section>

    <!-- ── Notifications ── -->
    <section class="ih-card ih-set-card">
      <header class="ih-set-card-head">
        <span class="ih-set-ico" style="--ico:#b45309;">🔔</span>
        <div>
          <h3>Notifications</h3>
          <p>Where new-request alert emails are delivered.</p>
        </div>
      </header>
      <div class="ih-set-fields">
        <label class="ih-set-field">
          <span class="ih-set-label">Admin notification email</span>
          <input type="email" name="ih_admin_notify_email" value="<?php echo esc_attr( $notify_mail ); ?>"
                 placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" autocomplete="off">
          <span class="ih-set-hint">Leave blank to use the WordPress site admin email (<?php echo esc_html( get_option( 'admin_email' ) ); ?>).</span>
        </label>
      </div>
    </section>

    <!-- ── Realtime messaging ── -->
    <section class="ih-card ih-set-card">
      <header class="ih-set-card-head">
        <span class="ih-set-ico" style="--ico:#0e7490;">⚡</span>
        <div>
          <h3>Realtime Messaging</h3>
          <p>WebSocket endpoint used by the live messages console.</p>
        </div>
      </header>
      <div class="ih-set-fields">
        <label class="ih-set-field">
          <span class="ih-set-label">WebSocket URL</span>
          <input type="url" name="ih_ws_url" value="<?php echo esc_attr( $ws_url ); ?>"
                 placeholder="<?php echo esc_attr( set_url_scheme( home_url( '/ws/' ), is_ssl() ? 'wss' : 'ws' ) ); ?>"
                 autocomplete="off">
          <span class="ih-set-hint">
            Suggested: <code><?php echo esc_html( set_url_scheme( home_url( '/ws/' ), is_ssl() ? 'wss' : 'ws' ) ); ?></code>
            — only set after Node relay is running (SSH: <code>server/install-cloudways.sh</code>). Leave blank to use polling; site works without WebSocket.
            See <code>server/DEPLOY.md</code>.
          </span>
        </label>
      </div>
    </section>

    <!-- ── Listing defaults ── -->
    <section class="ih-card ih-set-card">
      <header class="ih-set-card-head">
        <span class="ih-set-ico" style="--ico:#7c3aed;">🖼</span>
        <div>
          <h3>Listing Defaults</h3>
          <p>Fallback images used when a listing is created without photos.</p>
        </div>
      </header>
      <div class="ih-set-fields ih-set-fields-2col">
        <?php
        $pickers = [
            'machine' => [ 'Default machine image', $def_machine ],
            'tool'    => [ 'Default tool image',    $def_tool ],
        ];
        foreach ( $pickers as $type => $pk ) : ?>
        <div class="ih-set-field">
          <span class="ih-set-label"><?php echo esc_html( $pk[0] ); ?></span>
          <div class="ih-set-media">
            <div class="ih-set-media-preview" id="ih-<?php echo esc_attr( $type ); ?>-preview">
              <?php if ( $pk[1] ) : ?>
                <img src="<?php echo esc_url( $pk[1] ); ?>" alt="">
              <?php else : ?>
                <span>🖼</span>
              <?php endif; ?>
            </div>
            <div class="ih-set-media-actions">
              <input type="hidden" name="ih_default_<?php echo esc_attr( $type ); ?>_image"
                     id="ih_default_<?php echo esc_attr( $type ); ?>_image"
                     value="<?php echo esc_attr( $pk[1] ); ?>">
              <button type="button" class="ih-set-btn-secondary" onclick="ihOpenMediaPicker('<?php echo esc_js( $type ); ?>')">Choose image</button>
              <button type="button" class="ih-set-btn-ghost" onclick="ihClearImage('<?php echo esc_js( $type ); ?>')">Remove</button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <div class="ih-set-actions">
      <button type="submit" class="ih-set-btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save Settings
      </button>
    </div>
  </form>
</div>

<style>
.ih-set-page{display:flex;flex-direction:column;gap:18px;max-width:880px;}
.ih-page-title{font-size:24px;font-weight:800;color:#111827;margin:0;}
.ih-page-sub{font-size:13px;color:#6b8aa3;margin:4px 0 0;}
.ih-set-banner{display:flex;align-items:center;gap:9px;background:#dcfce7;border:1px solid #86efac;color:#15803d;font-size:13px;font-weight:700;border-radius:14px;padding:12px 16px;}
.ih-set-form{display:flex;flex-direction:column;gap:18px;}
.ih-set-card{background:#fff;border:1px solid #d9e7f7;border-radius:20px;overflow:hidden;}
.ih-set-card-head{display:flex;align-items:flex-start;gap:13px;padding:18px 20px;border-bottom:1px solid #edf2f7;}
.ih-set-ico{width:40px;height:40px;border-radius:13px;background:#f3f6fa;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.ih-set-card-head h3{margin:0;font-size:15px;font-weight:800;color:#111827;}
.ih-set-card-head p{margin:3px 0 0;font-size:12px;color:#6b8aa3;}
.ih-set-fields{display:flex;flex-direction:column;gap:16px;padding:18px 20px;}
.ih-set-fields-2col{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
.ih-set-field{display:flex;flex-direction:column;gap:6px;}
.ih-set-label{font-size:12px;font-weight:700;color:#46647c;text-transform:uppercase;letter-spacing:.04em;}
.ih-set-field input[type=text],.ih-set-field input[type=email],.ih-set-field input[type=url]{border:1px solid #d9e7f7;border-radius:12px;padding:10px 13px;font-size:13px;max-width:420px;}
.ih-set-field input:focus{outline:none;border-color:#1e5f8a;box-shadow:0 0 0 3px rgba(30,95,138,.12);}
.ih-set-hint{font-size:11px;color:#9ca3af;line-height:1.5;}
.ih-set-hint code{background:#f3f6fa;border-radius:5px;padding:1px 5px;font-size:10px;}
/* Media picker */
.ih-set-media{display:flex;align-items:center;gap:13px;}
.ih-set-media-preview{width:74px;height:74px;border-radius:14px;border:1px dashed #c4d7eb;background:#f7fbff;display:flex;align-items:center;justify-content:center;font-size:24px;overflow:hidden;flex-shrink:0;}
.ih-set-media-preview img{width:100%;height:100%;object-fit:cover;}
.ih-set-media-actions{display:flex;flex-direction:column;gap:7px;}
/* Buttons */
.ih-set-btn-primary{display:inline-flex;align-items:center;gap:9px;background:#1e5f8a;color:#fff;border:0;border-radius:999px;padding:12px 28px;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s,transform .15s;}
.ih-set-btn-primary:hover{background:#174c6f;transform:translateY(-1px);}
.ih-set-btn-secondary{background:#fff;border:1px solid #1e5f8a;color:#1e5f8a;border-radius:999px;padding:7px 16px;font-size:12px;font-weight:700;cursor:pointer;transition:background .15s;}
.ih-set-btn-secondary:hover{background:#f0f7ff;}
.ih-set-btn-ghost{background:none;border:0;color:#9ca3af;font-size:12px;font-weight:600;cursor:pointer;text-align:left;padding:0 4px;}
.ih-set-btn-ghost:hover{color:#b91c1c;}
.ih-set-actions{display:flex;justify-content:flex-end;}
/* ── Mobile ≤ 640px ── */
@media (max-width:640px){
  .ih-set-fields-2col{grid-template-columns:1fr;}
  .ih-set-actions{justify-content:stretch;}
  .ih-set-btn-primary{width:100%;justify-content:center;padding:14px 28px;}
  .ih-page-title{font-size:20px;}
  .ih-set-card-head{padding:14px 16px;gap:11px;}
  .ih-set-ico{width:34px;height:34px;border-radius:11px;font-size:15px;}
  .ih-set-fields{padding:14px 16px;}
  .ih-set-field input[type=text],.ih-set-field input[type=email],.ih-set-field input[type=url]{max-width:none;font-size:16px;padding:12px 13px;}
  .ih-set-media{align-items:flex-start;}
  .ih-set-media-preview{width:64px;height:64px;}
  .ih-set-btn-secondary{padding:10px 18px;}
}
</style>

<script>
/* WordPress Media Library picker — wp.media is enqueued for this page only */
function ihOpenMediaPicker(type) {
  if (typeof wp === 'undefined' || !wp.media) {
    window.alert('Media library is unavailable — please reload the page.');
    return;
  }
  var frame = wp.media({
    title: 'Select default ' + type + ' image',
    button: { text: 'Use this image' },
    multiple: false,
    library: { type: 'image' }
  });
  frame.on('select', function () {
    var att = frame.state().get('selection').first().toJSON();
    document.getElementById('ih_default_' + type + '_image').value = att.url;
    document.getElementById('ih-' + type + '-preview').innerHTML =
      '<img src="' + att.url + '" alt="">';
  });
  frame.open();
}

function ihClearImage(type) {
  document.getElementById('ih_default_' + type + '_image').value = '';
  document.getElementById('ih-' + type + '-preview').innerHTML = '<span>🖼</span>';
}

/* Auto-hide the saved banner */
(function () {
  var banner = document.getElementById('ihSetBanner');
  if (!banner) return;
  setTimeout(function () {
    banner.style.transition = 'opacity .5s';
    banner.style.opacity = '0';
    setTimeout(function () { banner.remove(); }, 550);
  }, 3500);
})();
</script>

<?php
$content = ob_get_clean();
$title   = 'Settings';
include IH_DIR . 'pages/layout.php';
