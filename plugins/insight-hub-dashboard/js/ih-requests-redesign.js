/* ih-requests-redesign.js — Requests Control Centre filters + row/card AJAX actions */
(function () {
  'use strict';

  var root = document.getElementById('ihRequestsRedesign');
  var table = document.getElementById('ihReqTable');
  if (!root) return;

  var cfg = window.ihRequestsRedesign || {};
  var AJAX = cfg.ajaxUrl || (window.ihAjax && window.ihAjax.url) || '';
  var NONCE = cfg.nonce || (window.ihAjax && window.ihAjax.nonce) || '';
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var empty = document.getElementById('ihReqEmpty');
  var mobileEmpty = document.getElementById('ihReqMobileEmpty');
  var mobileResultsCount = document.getElementById('ihReqMobileResultsCount');
  var searchInput = document.getElementById('ihReqSearch');
  var mobileMq = window.matchMedia ? window.matchMedia('(max-width: 768px)') : null;
  var state = { status: 'all', type: '', q: '' };

  function tableRows() {
    return table ? Array.from(table.querySelectorAll('tbody tr')) : [];
  }

  function mobileCards() {
    return Array.from(root.querySelectorAll('.ih-req-mobile-card[data-id]'));
  }

  function requestItems() {
    return tableRows().concat(mobileCards());
  }

  function padKpi(el) {
    var target = parseInt(el.dataset.count, 10) || 0;
    var text = target < 10 ? ('0' + target) : String(target);
    if (reduceMotion || target === 0) {
      el.textContent = text;
      return;
    }
    var dur = 900;
    var t0 = null;
    function step(ts) {
      if (!t0) t0 = ts;
      var p = Math.min((ts - t0) / dur, 1);
      var val = Math.round(target * (1 - Math.pow(1 - p, 3)));
      el.textContent = val < 10 ? ('0' + val) : String(val);
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  document.querySelectorAll('.ih-req-kpi-num').forEach(padKpi);

  function updateMobilePlaceholder() {
    if (!searchInput) return;
    var onMobile = mobileMq && mobileMq.matches;
    searchInput.placeholder = onMobile
      ? 'Search name, ID, listing…'
      : 'Search name, ID, email, listing…';
  }

  if (mobileMq) {
    updateMobilePlaceholder();
    if (mobileMq.addEventListener) mobileMq.addEventListener('change', updateMobilePlaceholder);
    else if (mobileMq.addListener) mobileMq.addListener(updateMobilePlaceholder);
  }

  function updateCounts(visible) {
    var label = visible + ' request' + (visible === 1 ? '' : 's');
    if (mobileResultsCount) mobileResultsCount.textContent = label;
  }

  function applyFilters() {
    var visible = 0;
    var seen = new Set();
    requestItems().forEach(function (item) {
      var okStatus = state.status === 'all' || item.dataset.status === state.status;
      var okType = !state.type || item.dataset.type === state.type;
      var okSearch = !state.q || (item.dataset.search || '').indexOf(state.q) !== -1;
      var show = okStatus && okType && okSearch;
      item.style.display = show ? '' : 'none';
      var id = item.dataset.id;
      if (show && id && !seen.has(id)) {
        seen.add(id);
        visible++;
      }
    });
    if (empty) {
      empty.hidden = visible !== 0;
      if (visible === 0) empty.textContent = 'No requests match the current filters.';
    }
    if (mobileEmpty) {
      mobileEmpty.hidden = visible !== 0;
      if (visible === 0) mobileEmpty.textContent = 'No requests match the current filters.';
    }
    updateCounts(visible);
  }
  window.ihReqApplyFilters = applyFilters;

  document.querySelectorAll('#ihReqTabs .ih-req-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      document.querySelectorAll('#ihReqTabs .ih-req-tab').forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      state.status = tab.dataset.status;
      syncKpiHighlight();
      applyFilters();
    });
  });

  var typeSelect = document.getElementById('ihReqType');
  if (typeSelect) {
    typeSelect.addEventListener('change', function () {
      state.type = this.value;
      applyFilters();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      state.q = String(this.value || '').toLowerCase().trim();
      applyFilters();
    });
  }

  function syncKpiHighlight() {
    document.querySelectorAll('.ih-req-kpi').forEach(function (k) {
      k.classList.toggle('active', k.dataset.kpi === state.status || (state.status === 'all' && k.dataset.kpi === 'all'));
    });
  }

  document.querySelectorAll('.ih-req-kpi').forEach(function (kpi) {
    kpi.addEventListener('click', function () {
      var key = kpi.dataset.kpi;
      var status = (key === 'all' || key === 'month') ? 'all' : key;
      var tab = document.querySelector('#ihReqTabs .ih-req-tab[data-status="' + status + '"]');
      if (tab) tab.click();
    });
  });

  function itemsById(id) {
    return requestItems().filter(function (item) {
      return String(item.dataset.id) === String(id);
    });
  }

  function setStatusOnItems(items, status) {
    items.forEach(function (item) {
      item.dataset.status = status;
      var chip = item.querySelector('.ih-req-chip');
      if (chip) {
        chip.className = 'ih-req-chip is-' + status;
        chip.textContent = status.charAt(0).toUpperCase() + status.slice(1);
      }
      var wait = item.querySelector('.ih-req-wait');
      if (wait && status !== 'pending') wait.textContent = '—';
      item.querySelectorAll('.ih-req-btn').forEach(function (b) { b.removeAttribute('disabled'); });
    });
  }

  syncKpiHighlight();
  applyFilters();

  window.ihReqStatus = function (btn, status) {
    var item = btn.closest('tr, .ih-req-mobile-card');
    if (!item) return;
    var id = parseInt(item.dataset.id, 10);
    var items = itemsById(id);
    items.forEach(function (row) {
      row.querySelectorAll('.ih-req-btn').forEach(function (b) { b.setAttribute('disabled', ''); });
    });
    var fd = new FormData();
    fd.append('action', 'ih_update_request_status');
    fd.append('nonce', NONCE);
    fd.append('id', id);
    fd.append('status', status);
    fetch(AJAX, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.success) {
          items.forEach(function (row) {
            row.querySelectorAll('.ih-req-btn').forEach(function (b) { b.removeAttribute('disabled'); });
          });
          return;
        }
        setStatusOnItems(items, status);
        applyFilters();
      })
      .catch(function () {
        items.forEach(function (row) {
          row.querySelectorAll('.ih-req-btn').forEach(function (b) { b.removeAttribute('disabled'); });
        });
      });
  };

  window.ihReqDelete = function (btn) {
    if (!window.confirm('Delete this request permanently? This cannot be undone.')) return;
    var item = btn.closest('tr, .ih-req-mobile-card');
    if (!item) return;
    var id = parseInt(item.dataset.id, 10);
    var items = itemsById(id);
    var fd = new FormData();
    fd.append('action', 'ih_delete_request');
    fd.append('nonce', NONCE);
    fd.append('request_id', id);
    fetch(AJAX, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.success) return;
        items.forEach(function (row) { row.style.opacity = '0'; });
        setTimeout(function () {
          items.forEach(function (row) { row.remove(); });
          applyFilters();
        }, 300);
      })
      .catch(function () {});
  };
})();
