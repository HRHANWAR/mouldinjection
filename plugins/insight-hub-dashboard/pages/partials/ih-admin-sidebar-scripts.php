<?php defined( 'ABSPATH' ) || exit; ?>
<script>
(function(){
  var AJAX  = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
  var NONCE = '<?php echo esc_js( wp_create_nonce( 'ih_nonce' ) ); ?>';
  var badge = document.getElementById('ihSidebarBadge');
  if (!badge) return;

  function updateBadge(count) {
    if (!badge) return;
    if (count > 0) {
      badge.textContent   = count > 99 ? '99+' : String(count);
      badge.style.display = 'inline-block';
    } else {
      badge.style.display = 'none';
    }
  }

  function fetchCounts() {
    var fd = new FormData();
    fd.append('action', 'ih_get_sidebar_counts');
    fd.append('nonce',  NONCE);
    fetch(AJAX, {method:'POST', body:fd})
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (!d || !d.success) return;
        updateBadge(parseInt(d.data.total || 0, 10));
        if (typeof setBadge === 'function') setBadge(parseInt(d.data.notifications || 0, 10));
      })
      .catch(function(){});
  }

  window.ihClearSidebarBadge = function() {
    updateBadge(0);
    var fd = new FormData();
    fd.append('action', 'ih_mark_notifications_read');
    fd.append('nonce',  NONCE);
    fetch(AJAX, {method:'POST', body:fd}).catch(function(){});
    var fd2 = new FormData();
    fd2.append('action', 'ih_mark_all_threads_read');
    fd2.append('nonce',  NONCE);
    fetch(AJAX, {method:'POST', body:fd2}).catch(function(){});
  };

  fetchCounts();
  setInterval(fetchCounts, 20000);
  if (window.location.href.indexOf('ih-messages') !== -1) updateBadge(0);
})();

(function(){
  var wrap = document.querySelector('.ih-wrap.ih-figma-nav.is-admin');
  if (!wrap) return;
  /* Figma admin: overlay drawer at all breakpoints; sb-collapsed must never apply */
  wrap.classList.remove('sb-collapsed');
  try { localStorage.setItem('ih_admin_sb', '0'); } catch (e) { /* ignore */ }
})();
</script>
