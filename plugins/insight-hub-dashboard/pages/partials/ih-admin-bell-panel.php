<?php defined( 'ABSPATH' ) || exit; ?>
<div class="ih-site-nav-bell-wrap" id="ihBellWrap">
  <button class="ih-site-nav-icon-btn ih-site-nav-bell-btn" id="ihBellBtn" type="button" onclick="ihToggleBell(event)" aria-label="<?php esc_attr_e( 'Notifications', 'insight-hub-dashboard' ); ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17" aria-hidden="true">
      <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
      <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
    </svg>
    <span id="ihBellBadge" class="ih-site-nav-bell-badge" style="display:none;"></span>
  </button>

  <div id="ihBellPanel" class="ih-site-nav-bell-panel" style="display:none;">
    <div class="ih-site-nav-bell-panel__head">
      <span><?php esc_html_e( 'Notifications', 'insight-hub-dashboard' ); ?></span>
      <button type="button" onclick="ihMarkAllRead()"><?php esc_html_e( 'Mark all read', 'insight-hub-dashboard' ); ?></button>
    </div>
    <div id="ihBellList" class="ih-site-nav-bell-panel__list"></div>
    <div class="ih-site-nav-bell-panel__foot">
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=ih-messages' ) ); ?>" class="ih-btn ih-btn-outline ih-bell-view-all-btn"><?php esc_html_e( 'View all messages', 'insight-hub-dashboard' ); ?></a>
    </div>
  </div>
</div>

<style>
.ih-site-nav-bell-wrap { position: relative; flex-shrink: 0; }
.ih-site-nav-bell-badge {
  position: absolute; top: 3px; right: 3px;
  min-width: 17px; height: 17px; padding: 0 4px;
  background: #ef4444; color: #fff;
  font-size: 10px; font-weight: 700; line-height: 17px;
  border-radius: 999px; text-align: center; border: 2px solid #fff;
}
.ih-site-nav-bell-panel {
  position: absolute; top: calc(100% + 8px); right: 0;
  width: min(340px, calc(100vw - 32px));
  background: #fff; border: 1px solid #d9e7f7; border-radius: 20px;
  box-shadow: 0 16px 48px rgba(30, 95, 138, .16);
  z-index: 99999; overflow: hidden;
}
.ih-site-nav-bell-panel__head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px; border-bottom: 1px solid #f3f4f6;
  font-size: 14px; font-weight: 700; color: #111827;
}
.ih-site-nav-bell-panel__head button {
  background: none; border: none; font-size: 11px; color: #1e5f8a;
  font-weight: 600; cursor: pointer;
}
.ih-site-nav-bell-panel__list { max-height: 400px; overflow-y: auto; }
.ih-site-nav-bell-panel__foot {
  padding: 12px 16px; border-top: 1px solid #f3f4f6; text-align: center;
}
.ih-site-nav-bell-panel__foot .ih-bell-view-all-btn {
  display: inline-flex;
  width: 100%;
  justify-content: center;
  font-size: 12px;
  padding: 8px 14px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 600;
  color: #164b3f;
  border: 1px solid #d9e7f7;
  background: #fff;
}
.ih-site-nav-bell-panel__foot .ih-bell-view-all-btn:hover {
  border-color: #164b3f;
  background: #f7fbff;
}
.ih-notif-item {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 12px 16px; border-bottom: 1px solid #f9fafb;
  cursor: pointer; transition: background .15s;
  text-decoration: none; color: inherit;
}
.ih-notif-item:hover { background: #f7fbff; }
.ih-notif-item.unread { background: #f0f9ff; }
.ih-notif-icon {
  width: 36px; height: 36px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; font-size: 16px;
}
.ih-notif-icon.request  { background: #fce4ec; }
.ih-notif-icon.machine  { background: #e3f2fd; }
.ih-notif-icon.tool     { background: #fff8e1; }
.ih-notif-icon.message  { background: #e8f5e9; }
.ih-notif-icon.user     { background: #ede7f6; }
.ih-notif-body { flex: 1; min-width: 0; }
.ih-notif-title { font-size: 13px; font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ih-notif-body-text { font-size: 11px; color: #6b7280; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ih-notif-time { font-size: 10px; color: #9ca3af; margin-top: 4px; display: block; }
.ih-notif-del { background: none; border: none; cursor: pointer; color: #d1d5db; font-size: 16px; padding: 0 4px; flex-shrink: 0; line-height: 1; transition: color .15s; }
.ih-notif-del:hover { color: #ef4444; }
.ih-notif-empty { padding: 32px 20px; text-align: center; color: #9ca3af; font-size: 13px; }
@keyframes ihBellShake {
  0%, 100% { transform: rotate(0); }
  15% { transform: rotate(12deg); }
  30% { transform: rotate(-10deg); }
  45% { transform: rotate(8deg); }
  60% { transform: rotate(-6deg); }
  75% { transform: rotate(4deg); }
}
.ih-bell-ring svg { animation: ihBellShake .5s ease; }
</style>

<script>
(function(){
  var AJAX  = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
  var NONCE = '<?php echo esc_js( wp_create_nonce( 'ih_nonce' ) ); ?>';
  var _notifs = [];

  function timeAgo(dateStr) {
    var d = new Date(dateStr.replace(' ', 'T'));
    var diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60)   return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
  }

  var icons = { request:'📋', machine:'⚙️', tool:'🔧', message:'💬', user:'👤', info:'🔔' };
  function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  function render(list) {
    var el = document.getElementById('ihBellList');
    if (!el) return;
    if (!list.length) {
      el.innerHTML = '<div class="ih-notif-empty">🎉 All caught up!<br><small>No new notifications.</small></div>';
      return;
    }
    var h = '';
    list.forEach(function(n) {
      var icon = icons[n.type] || '🔔';
      var cls  = n.is_read == 0 ? 'ih-notif-item unread' : 'ih-notif-item';
      h += '<div class="'+cls+'" data-id="'+n.id+'" onclick="ihOpenNotif(this,\''+esc(n.link)+'\')">'
        + '<div class="ih-notif-icon '+esc(n.type)+'">'+icon+'</div>'
        + '<div class="ih-notif-body">'
        +   '<div class="ih-notif-title">'+esc(n.title)+'</div>'
        +   '<div class="ih-notif-body-text">'+esc(n.body)+'</div>'
        +   '<span class="ih-notif-time">'+timeAgo(n.created_at)+'</span>'
        + '</div>'
        + '<button class="ih-notif-del" title="Dismiss" onclick="ihDeleteNotif(event,'+n.id+')">×</button>'
        + '</div>';
    });
    el.innerHTML = h;
  }

  function setBadge(count) {
    var badge = document.getElementById('ihBellBadge');
    var btn   = document.getElementById('ihBellBtn');
    if (!badge) return;
    if (count > 0) {
      badge.style.display = 'block';
      badge.textContent   = count > 99 ? '99+' : count;
      if (btn) btn.classList.add('ih-bell-ring');
      setTimeout(function(){ if (btn) btn.classList.remove('ih-bell-ring'); }, 600);
    } else {
      badge.style.display = 'none';
    }
  }

  function fetchNotifs(silent) {
    var fd = new FormData();
    fd.append('action', 'ih_get_notifications');
    fd.append('nonce',  NONCE);
    fetch(AJAX, {method:'POST', body:fd})
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (!d || !d.success) return;
        _notifs = d.data.notifications || [];
        setBadge(d.data.unread || 0);
        if (!silent) render(_notifs);
      }).catch(function(){});
  }

  window.ihToggleBell = function(e) {
    e.stopPropagation();
    var panel = document.getElementById('ihBellPanel');
    if (!panel) return;
    var open = panel.style.display === 'block';
    panel.style.display = open ? 'none' : 'block';
    if (!open) { render(_notifs); fetchNotifs(false); }
  };

  window.ihOpenNotif = function(el, link) {
    var id = parseInt(el.getAttribute('data-id') || '0', 10);
    el.classList.remove('unread');
    var fd = new FormData();
    fd.append('action', 'ih_mark_single_notification_read');
    fd.append('nonce',  NONCE);
    fd.append('id',     id);
    fetch(AJAX, {method:'POST', body:fd})
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d && d.success) {
          setBadge(d.data.unread || 0);
          if (typeof fetchCounts === 'function') fetchCounts();
        }
      }).catch(function(){});
    if (link && link !== '#') {
      setTimeout(function(){ window.location.href = link; }, 150);
    }
  };

  window.ihMarkAllRead = function() {
    var fd = new FormData();
    fd.append('action','ih_mark_notifications_read');
    fd.append('nonce', NONCE);
    fetch(AJAX, {method:'POST', body:fd})
      .then(function(){ setBadge(0); fetchNotifs(false); })
      .catch(function(){});
  };

  window.ihDeleteNotif = function(e, id) {
    e.stopPropagation();
    var fd = new FormData();
    fd.append('action','ih_delete_notification');
    fd.append('nonce', NONCE);
    fd.append('id',    id);
    fetch(AJAX, {method:'POST', body:fd})
      .then(function(){ fetchNotifs(false); })
      .catch(function(){});
  };

  document.addEventListener('click', function(e) {
    var wrap = document.getElementById('ihBellWrap');
    if (wrap && !wrap.contains(e.target)) {
      var panel = document.getElementById('ihBellPanel');
      if (panel) panel.style.display = 'none';
    }
  });

  fetchNotifs(true);
  setInterval(function(){ fetchNotifs(true); }, 30000);
})();
</script>
