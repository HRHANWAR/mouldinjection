<?php
/**
 * Insight Hub — Activity / Audit Log (admin)
 * Chronological trail of every logged platform event: requests, listings,
 * users, messages, settings and system actions.
 * URL: admin.php?page=ih-activity
 */
defined( 'ABSPATH' ) || exit;

global $wpdb;
$p     = $wpdb->prefix;
$table = $p . 'ih_activity_log';

$table_ok = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table );

/* ═══════════ DATA (paginated) ═══════════ */
$per_page = 40;
$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
$offset   = ( $paged - 1 ) * $per_page;

$total_rows = $table_ok ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) : 0;
$total_pages = max( 1, (int) ceil( $total_rows / $per_page ) );

$events = $table_ok ? ( $wpdb->get_results( $wpdb->prepare(
    "SELECT id, actor_id, type, message, created_at FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
    $per_page, $offset
), ARRAY_A ) ?: [] ) : [];

$type_counts = [];
if ( $table_ok ) {
    foreach ( $wpdb->get_results( "SELECT type, COUNT(*) n FROM {$table} GROUP BY type", ARRAY_A ) ?: [] as $tc ) {
        $type_counts[ $tc['type'] ] = (int) $tc['n'];
    }
}

$actor_cache = [];
foreach ( $events as &$e ) {
    $aid = (int) $e['actor_id'];
    if ( ! isset( $actor_cache[ $aid ] ) ) {
        if ( $aid > 0 ) {
            $au = get_userdata( $aid );
            $actor_cache[ $aid ] = $au ? $au->display_name : ( 'User #' . $aid );
        } else {
            $actor_cache[ $aid ] = 'Visitor / System';
        }
    }
    $e['actor'] = $actor_cache[ $aid ];

    $ts   = strtotime( $e['created_at'] );
    $diff = max( 0, current_time( 'timestamp' ) - $ts );
    if     ( $diff < MINUTE_IN_SECONDS ) $e['ago'] = 'just now';
    elseif ( $diff < HOUR_IN_SECONDS )   $e['ago'] = floor( $diff / MINUTE_IN_SECONDS ) . 'm ago';
    elseif ( $diff < DAY_IN_SECONDS )    $e['ago'] = floor( $diff / HOUR_IN_SECONDS ) . 'h ago';
    else                                 $e['ago'] = floor( $diff / DAY_IN_SECONDS ) . 'd ago';
    $e['stamp'] = date_i18n( 'd M Y · H:i', $ts );
}
unset( $e );

$type_meta = [
    'request'  => [ 'Requests', '#1e5f8a', '📋' ],
    'listing'  => [ 'Listings', '#b45309', '⚙️' ],
    'user'     => [ 'Users',    '#7c3aed', '👤' ],
    'message'  => [ 'Messages', '#15803d', '💬' ],
    'settings' => [ 'Settings', '#0e7490', '🛠' ],
    'system'   => [ 'System',   '#6b7280', '🖥' ],
];

ob_start();
?>
<div class="ih-rd ih-admin">
<div class="ih-act-page">

  <div class="ih-page-header ih-act-head">
    <div>
      <h2 class="ih-page-title">Activity Log</h2>
      <p class="ih-page-sub">Full audit trail — <span id="ihActTotal"><?php echo (int) $total_rows; ?></span> recorded events across requests, listings, users, messages and settings.</p>
    </div>
    <button type="button" class="ih-act-clear" id="ihActClear" <?php echo $total_rows ? '' : 'disabled'; ?>>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      Clear log
    </button>
  </div>

  <!-- Type filter chips -->
  <div class="ih-act-chips" id="ihActChips">
    <button class="ih-act-chip active" data-type="">All <em><?php echo (int) $total_rows; ?></em></button>
    <?php foreach ( $type_meta as $key => $tm ) : ?>
    <button class="ih-act-chip" data-type="<?php echo esc_attr( $key ); ?>" style="--chip:<?php echo esc_attr( $tm[1] ); ?>;">
      <?php echo esc_html( $tm[0] ); ?> <em><?php echo (int) ( $type_counts[ $key ] ?? 0 ); ?></em>
    </button>
    <?php endforeach; ?>
    <div class="ih-act-search-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" id="ihActSearch" placeholder="Search events…">
    </div>
  </div>

  <div class="ih-card ih-act-card">
    <?php if ( ! $table_ok ) : ?>
      <div class="ih-act-empty">The activity table has not been created yet — reload once and the schema migration will create it automatically.</div>
    <?php elseif ( ! $events ) : ?>
      <div class="ih-act-empty">No activity recorded yet. Events will appear here as the platform is used.</div>
    <?php else : ?>
      <ul class="ih-act-list" id="ihActList">
        <?php foreach ( $events as $e ) :
            $tm = $type_meta[ $e['type'] ] ?? $type_meta['system']; ?>
        <li class="ih-act-row" data-type="<?php echo esc_attr( $e['type'] ); ?>"
            data-search="<?php echo esc_attr( strtolower( $e['message'] . ' ' . $e['actor'] . ' ' . $e['type'] ) ); ?>">
          <span class="ih-act-ico" style="--chip:<?php echo esc_attr( $tm[1] ); ?>;"><?php echo $tm[2]; ?></span>
          <div class="ih-act-body">
            <p class="ih-act-msg"><?php echo esc_html( $e['message'] ); ?></p>
            <span class="ih-act-meta">
              <strong><?php echo esc_html( $e['actor'] ); ?></strong>
              · <span class="ih-act-type" style="--chip:<?php echo esc_attr( $tm[1] ); ?>;"><?php echo esc_html( $tm[0] ); ?></span>
              · <span title="<?php echo esc_attr( $e['stamp'] ); ?>"><?php echo esc_html( $e['ago'] ); ?></span>
            </span>
          </div>
          <span class="ih-act-stamp"><?php echo esc_html( $e['stamp'] ); ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <div class="ih-act-empty" id="ihActNoMatch" hidden>No events match the current filters.</div>

      <?php if ( $total_pages > 1 ) : ?>
      <div class="ih-act-pager">
        <?php
        $base = admin_url( 'admin.php?page=ih-activity' );
        if ( $paged > 1 ) {
            echo '<a class="ih-act-page-btn" href="' . esc_url( $base . '&paged=' . ( $paged - 1 ) ) . '">← Newer</a>';
        }
        echo '<span class="ih-act-page-info">Page ' . (int) $paged . ' of ' . (int) $total_pages . '</span>';
        if ( $paged < $total_pages ) {
            echo '<a class="ih-act-page-btn" href="' . esc_url( $base . '&paged=' . ( $paged + 1 ) ) . '">Older →</a>';
        }
        ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<style>
.ih-act-page{display:flex;flex-direction:column;gap:18px;}
.ih-page-title{font-size:24px;font-weight:800;color:#111827;margin:0;}
.ih-page-sub{font-size:13px;color:#6b8aa3;margin:4px 0 0;}
.ih-act-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap;}
.ih-act-clear{display:inline-flex;align-items:center;gap:7px;background:#fff;border:1px solid #fecaca;color:#b91c1c;font-size:12px;font-weight:700;border-radius:999px;padding:9px 16px;cursor:pointer;transition:background .15s;}
.ih-act-clear:hover{background:#fff1f2;}
.ih-act-clear[disabled]{opacity:.5;pointer-events:none;}
/* Chips */
.ih-act-chips{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.ih-act-chip{--chip:#1e5f8a;border:1px solid #d9e7f7;background:#fff;border-radius:999px;padding:7px 13px;font-size:12px;font-weight:700;color:#46647c;cursor:pointer;transition:border-color .15s,color .15s,background .15s;}
.ih-act-chip em{font-style:normal;font-family:'Roboto Mono',monospace;font-size:11px;color:#9ca3af;margin-left:3px;}
.ih-act-chip:hover{border-color:var(--chip);color:var(--chip);}
.ih-act-chip.active{background:var(--chip);border-color:var(--chip);color:#fff;}
.ih-act-chip.active em{color:rgba(255,255,255,.75);}
.ih-act-search-wrap{position:relative;margin-left:auto;min-width:220px;flex:0 1 280px;}
.ih-act-search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:#9ca3af;}
.ih-act-search-wrap input{width:100%;border:1px solid #d9e7f7;border-radius:12px;padding:9px 12px 9px 34px;font-size:13px;}
.ih-act-search-wrap input:focus{outline:none;border-color:#1e5f8a;box-shadow:0 0 0 3px rgba(30,95,138,.12);}
/* Card + list */
.ih-act-card{background:#fff;border:1px solid #d9e7f7;border-radius:20px;overflow:hidden;}
.ih-act-list{list-style:none;margin:0;padding:0;}
.ih-act-row{display:flex;align-items:flex-start;gap:13px;padding:14px 18px;border-bottom:1px solid #f3f6fa;transition:background .15s;}
.ih-act-row:hover{background:#f7fbff;}
.ih-act-row:last-child{border-bottom:0;}
.ih-act-ico{width:36px;height:36px;border-radius:12px;background:#f3f6fa;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
@supports (background:color-mix(in srgb,red 10%,#fff)){.ih-act-ico{background:color-mix(in srgb,var(--chip) 12%,#fff);}}
.ih-act-body{flex:1;min-width:0;}
.ih-act-msg{margin:0;font-size:13px;font-weight:600;color:#111827;line-height:1.45;}
.ih-act-meta{display:block;margin-top:3px;font-size:11px;color:#6b8aa3;}
.ih-act-meta strong{color:#46647c;font-weight:700;}
.ih-act-type{color:var(--chip);font-weight:700;}
.ih-act-stamp{font-family:'Roboto Mono',monospace;font-size:11px;color:#9ca3af;white-space:nowrap;flex-shrink:0;padding-top:2px;}
.ih-act-empty{padding:40px 20px;text-align:center;color:#6b8aa3;font-size:13px;}
/* Pager */
.ih-act-pager{display:flex;align-items:center;justify-content:center;gap:14px;padding:14px;border-top:1px solid #edf2f7;}
.ih-act-page-btn{font-size:12px;font-weight:700;color:#1e5f8a;text-decoration:none;border:1px solid #d9e7f7;border-radius:999px;padding:7px 15px;transition:background .15s;}
.ih-act-page-btn:hover{background:#f0f7ff;}
.ih-act-page-info{font-size:12px;color:#6b8aa3;font-family:'Roboto Mono',monospace;}
/* ── Tablet ≤ 900px: chips scroll in one row, search drops below ── */
@media (max-width:900px){
  .ih-act-chips{flex-wrap:nowrap;overflow-x:auto;padding-bottom:4px;-webkit-overflow-scrolling:touch;scrollbar-width:none;}
  .ih-act-chips::-webkit-scrollbar{display:none;}
  .ih-act-chip{white-space:nowrap;flex-shrink:0;}
  .ih-act-search-wrap{flex:0 0 220px;margin-left:0;}
}

/* ── Mobile ≤ 640px ── */
@media (max-width:640px){
  .ih-act-stamp{display:none;}
  .ih-act-chips{flex-wrap:wrap;overflow:visible;}
  .ih-act-search-wrap{flex:1 1 100%;}
  .ih-act-head{flex-direction:column;align-items:stretch;}
  .ih-act-clear{align-self:flex-start;}
  .ih-act-row{padding:12px 14px;gap:11px;}
  .ih-act-ico{width:32px;height:32px;border-radius:10px;font-size:14px;}
  .ih-act-msg{font-size:12.5px;}
  .ih-page-title{font-size:20px;}
  .ih-act-pager{flex-wrap:wrap;gap:10px;padding:12px;}
  .ih-act-page-btn{padding:9px 18px;}
}
</style>

<script>
(function () {
  'use strict';
  var state = { type: '', q: '' };
  var list = document.getElementById('ihActList');
  var noMatch = document.getElementById('ihActNoMatch');

  function applyFilters() {
    if (!list) return;
    var visible = 0;
    list.querySelectorAll('.ih-act-row').forEach(function (row) {
      var okType   = !state.type || row.dataset.type === state.type;
      var okSearch = !state.q || (row.dataset.search || '').indexOf(state.q) !== -1;
      var show = okType && okSearch;
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    if (noMatch) noMatch.hidden = visible !== 0;
  }

  document.querySelectorAll('#ihActChips .ih-act-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      document.querySelectorAll('#ihActChips .ih-act-chip').forEach(function (c) { c.classList.remove('active'); });
      chip.classList.add('active');
      state.type = chip.dataset.type;
      applyFilters();
    });
  });

  var search = document.getElementById('ihActSearch');
  if (search) {
    search.addEventListener('input', function () {
      state.q = String(this.value || '').toLowerCase().trim();
      applyFilters();
    });
  }

  var clearBtn = document.getElementById('ihActClear');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      if (!window.confirm('Clear the entire activity log? This cannot be undone.')) return;
      clearBtn.setAttribute('disabled', '');
      var fd = new FormData();
      fd.append('action', 'ih_clear_activity_log');
      fd.append('nonce', '<?php echo esc_js( wp_create_nonce( 'ih_nonce' ) ); ?>');
      fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.success) window.location.reload();
          else clearBtn.removeAttribute('disabled');
        })
        .catch(function () { clearBtn.removeAttribute('disabled'); });
    });
  }
})();
</script>

</div><!-- /.ih-rd.ih-admin -->

<?php
$content = ob_get_clean();
$title   = 'Activity';
include IH_DIR . 'pages/layout.php';
