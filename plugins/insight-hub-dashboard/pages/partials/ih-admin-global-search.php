<?php defined( 'ABSPATH' ) || exit; ?>
<div class="ih-search-wrap ih-global-search-wrap ih-site-nav-header-search" style="position:relative;">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="ih-search-icon" aria-hidden="true">
    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
  </svg>
  <input
    type="search"
    id="ihGlobalSearch"
    placeholder="<?php esc_attr_e( 'Search…', 'insight-hub-dashboard' ); ?>"
    class="ih-search-input"
    autocomplete="off"
    aria-label="<?php esc_attr_e( 'Global search', 'insight-hub-dashboard' ); ?>"
  >
  <div id="ihSearchResults" class="ih-search-dropdown" style="display:none;"></div>
</div>

<style>
.ih-site-nav-header-search.ih-global-search-wrap { flex: 0 1 auto; min-width: 0; max-width: 200px; width: 168px; }
.ih-search-dropdown {
  position: absolute; top: calc(100% + 6px); left: 0; right: 0;
  background: #fff; border: 1px solid #d9e7f7; border-radius: 16px;
  box-shadow: 0 16px 40px rgba(30, 95, 138, .14);
  z-index: 99999; overflow: hidden; max-height: 420px; overflow-y: auto;
}
.ih-search-item {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 16px; text-decoration: none; color: #111827;
  border-bottom: 1px solid #f3f4f6; transition: background .15s; cursor: pointer;
}
.ih-search-item:last-child { border-bottom: none; }
.ih-search-item:hover { background: #f0f9ff; }
.ih-search-item-icon {
  width: 34px; height: 34px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; flex-shrink: 0; background: #f3f4f6;
}
.ih-search-item-icon.user    { background: #e8f5e9; }
.ih-search-item-icon.machine { background: #e3f2fd; }
.ih-search-item-icon.tool    { background: #fff8e1; }
.ih-search-item-icon.request { background: #fce4ec; }
.ih-search-item-body { min-width: 0; }
.ih-search-item-title { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ih-search-item-sub { font-size: 11px; color: #6b8aa3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
.ih-search-type-badge { margin-left: auto; flex-shrink: 0; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 999px; background: #eaf4ff; color: #1e5f8a; }
.ih-search-empty  { padding: 24px; text-align: center; color: #9ca3af; font-size: 13px; }
.ih-search-loading { padding: 16px; text-align: center; color: #9ca3af; font-size: 12px; }
</style>

<script>
(function(){
  var input    = document.getElementById('ihGlobalSearch');
  var dropdown = document.getElementById('ihSearchResults');
  if (!input || !dropdown) return;

  var timer;
  var ajaxUrl = (window.imDashboard && window.imDashboard.ajaxUrl)
             || (window.ihAjax    && window.ihAjax.url)
             || '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
  var nonce   = (window.ihAjax && window.ihAjax.nonce)
             || '<?php echo esc_js( wp_create_nonce( 'ih_nonce' ) ); ?>';

  input.addEventListener('input', function(){
    var q = this.value.trim();
    clearTimeout(timer);
    if (q.length < 2) { dropdown.style.display = 'none'; return; }
    dropdown.innerHTML = '<div class="ih-search-loading">🔍 Searching…</div>';
    dropdown.style.display = 'block';
    timer = setTimeout(function(){
      var fd = new FormData();
      fd.append('action', 'ih_global_search');
      fd.append('nonce',  nonce);
      fd.append('q',      q);
      fetch(ajaxUrl, {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
          if (!d || !d.success) { dropdown.innerHTML = '<div class="ih-search-empty">Error loading results.</div>'; return; }
          var items = d.data || [];
          if (!items.length) {
            dropdown.innerHTML = '<div class="ih-search-empty">No results found for "<strong>'+escH(q)+'</strong>"</div>';
            return;
          }
          var html = '';
          items.forEach(function(item){
            html += '<a class="ih-search-item" href="'+escH(item.url)+'">'
              + '<div class="ih-search-item-icon '+escH(item.type)+'">'+item.icon+'</div>'
              + '<div class="ih-search-item-body">'
              +   '<div class="ih-search-item-title">'+escH(item.title)+'</div>'
              +   '<div class="ih-search-item-sub">'+escH(item.sub)+'</div>'
              + '</div>'
              + '<span class="ih-search-type-badge">'+escH(item.type)+'</span>'
              + '</a>';
          });
          dropdown.innerHTML = html;
        })
        .catch(function(){ dropdown.innerHTML = '<div class="ih-search-empty">Search failed.</div>'; });
    }, 320);
  });

  document.addEventListener('click', function(e){
    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.style.display = 'none';
    }
  });

  input.addEventListener('keydown', function(e){
    var items = dropdown.querySelectorAll('.ih-search-item');
    if (!items.length) return;
    var focused = dropdown.querySelector('.ih-search-item:focus');
    var idx = Array.prototype.indexOf.call(items, focused);
    if (e.key === 'ArrowDown') { e.preventDefault(); (items[idx + 1] || items[0]).focus(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); (items[idx - 1] || items[items.length - 1]).focus(); }
    else if (e.key === 'Escape') { dropdown.style.display = 'none'; input.blur(); }
  });

  function escH(s){
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
})();
</script>
