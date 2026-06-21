/**
 * IH Resizable UI — chat height handle, fixed dropdown menus, table columns.
 * Persists UI size preferences to localStorage.
 */
(function () {
  'use strict';

  var STORAGE_PREFIX = 'ih_resize_';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  /* ── Fix 3-dot menus: fixed positioning so they never hide behind images ── */
  window.ihPositionCardDropdown = positionCardDropdown;

  function positionCardDropdown(btn, dropdown) {
    if (!btn || !dropdown) return;
    dropdown.style.position = 'fixed';
    dropdown.style.zIndex = '100050';
    var rect = btn.getBoundingClientRect();
    var w = dropdown.offsetWidth || 180;
    var left = rect.right - w;
    if (left < 8) left = 8;
    if (left + w > window.innerWidth - 8) left = window.innerWidth - w - 8;
    dropdown.style.top = Math.round(rect.bottom + 6) + 'px';
    dropdown.style.left = Math.round(left) + 'px';
    dropdown.style.right = 'auto';
  }

  function initCardMenus() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.ih-card-menu-btn');
      if (!btn) return;
      var menu = btn.closest('.ih-card-menu');
      if (!menu) return;
      var dropdown = menu.querySelector('.ih-dropdown');
      if (!dropdown) return;
      setTimeout(function () {
        if (!dropdown.classList.contains('hidden')) {
          var card = btn.closest('.ih-listing-card, .ih-dash-listing-card');
          if (card) card.classList.add('is-menu-open');
          positionCardDropdown(btn, dropdown);
        }
      }, 0);
    });

    document.addEventListener('click', function (e) {
      if (e.target.closest('.ih-card-menu')) return;
      document.querySelectorAll('.ih-listing-card.is-menu-open, .ih-dash-listing-card.is-menu-open').forEach(function (c) {
        c.classList.remove('is-menu-open');
      });
    });

    window.addEventListener('scroll', function () {
      document.querySelectorAll('.ih-card-menu .ih-dropdown:not(.hidden)').forEach(function (dd) {
        var btn = dd.parentElement && dd.parentElement.querySelector('.ih-card-menu-btn');
        if (btn) positionCardDropdown(btn, dd);
      });
    }, true);

    window.addEventListener('resize', function () {
      document.querySelectorAll('.ih-card-menu .ih-dropdown:not(.hidden)').forEach(function (dd) {
        var btn = dd.parentElement && dd.parentElement.querySelector('.ih-card-menu-btn');
        if (btn) positionCardDropdown(btn, dd);
      });
    });
  }

  /* ── Bottom-only chat height adjustment for images/docs ── */
  function initChatHeightHandles() {
    if (window.innerWidth <= 768) return;

    var targets = [
      { el: document.querySelector('.ih-messages-wrap'), key: 'admin_messages_h', min: 460, max: 875, fallback: 875 },
      { el: document.querySelector('#ihMsgLayout'), key: 'user_messages_h', min: 460, max: 875, fallback: 875 }
    ];

    targets.forEach(function (target) {
      var box = target.el;
      if (!box || box.dataset.ihHeightHandle === '1') return;
      box.dataset.ihHeightHandle = '1';
      box.classList.add('ih-chat-height-adjustable');

      var saved = parseInt(localStorage.getItem(STORAGE_PREFIX + target.key), 10);
      var height = saved || target.fallback;
      height = Math.max(target.min, Math.min(target.max, height));
      box.style.height = height + 'px';
      box.style.maxHeight = 'none';

      var handle = document.createElement('div');
      handle.className = 'ih-chat-height-handle';
      handle.setAttribute('role', 'separator');
      handle.setAttribute('aria-orientation', 'horizontal');
      handle.setAttribute('aria-label', 'Adjust chat height');
      box.appendChild(handle);

      var dragging = false;
      handle.addEventListener('pointerdown', function (event) {
        dragging = true;
        handle.classList.add('is-dragging');
        handle.setPointerCapture(event.pointerId);
        event.preventDefault();
      });

      handle.addEventListener('pointermove', function (event) {
        if (!dragging) return;
        var rect = box.getBoundingClientRect();
        var next = Math.max(target.min, Math.min(target.max, event.clientY - rect.top));
        box.style.height = Math.round(next) + 'px';
      });

      handle.addEventListener('pointerup', function (event) {
        dragging = false;
        handle.classList.remove('is-dragging');
        handle.releasePointerCapture(event.pointerId);
        localStorage.setItem(STORAGE_PREFIX + target.key, String(box.offsetHeight));
      });
    });
  }

  function setTableColWidth(table, idx, px, minPx) {
    var cols = table.querySelectorAll('colgroup col');
    var w = Math.round(px) + 'px';
    if (cols[idx]) {
      cols[idx].style.width = w;
      if (minPx) cols[idx].style.minWidth = Math.round(minPx) + 'px';
    }
    var headers = table.querySelectorAll('thead th');
    if (headers[idx]) {
      headers[idx].style.width = w;
      if (minPx) headers[idx].style.minWidth = Math.round(minPx) + 'px';
    }
  }

  var IH_REQ_TABLE_COLS = 8;
  var IH_REQ_DEFAULT_WIDTHS = [190, 178, 142, 166, 110, 90, 110, 240];
  var IH_REQ_MIN_WIDTHS = [140, 120, 110, 120, 96, 72, 96, 220];

  function applyReqTableDefaults(table) {
    for (var i = 0; i < IH_REQ_TABLE_COLS; i++) {
      setTableColWidth(table, i, IH_REQ_DEFAULT_WIDTHS[i], IH_REQ_MIN_WIDTHS[i]);
    }
  }

  function loadSavedColumnWidths(storageKey, colCount, requireVersion) {
    var saved = {};
    try {
      saved = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
    } catch (e) {
      saved = {};
    }
    if (requireVersion && saved._v !== colCount) return {};
    var widths = requireVersion ? {} : saved;
    if (requireVersion) {
      for (var i = 0; i < colCount; i++) {
        if (saved[i] && saved[i] >= 48) widths[i] = saved[i];
      }
    }
    return widths;
  }

  function initRequestTableColumns() {
    if (window.innerWidth <= 768) return;

    var tables = document.querySelectorAll('.ih-dashboard-request-table, .ih-message-request-table, #ihReqTable');
    tables.forEach(function (table, tableIndex) {
      if (!table || table.dataset.ihColumnResize === '1') return;

      var headers = table.querySelectorAll('thead th');
      if (headers.length < 2) return;

      table.dataset.ihColumnResize = '1';
      table.classList.add('ih-resizable-table');

      var tableKey = table.id || table.className || ('table-' + tableIndex);
      var colCount = headers.length;
      var storageKey = STORAGE_PREFIX + 'cols_' + location.pathname + '_' + location.search + '_' + tableKey;
      if (table.id === 'ihReqTable') {
        storageKey += '_v' + IH_REQ_TABLE_COLS;
        colCount = IH_REQ_TABLE_COLS;
        applyReqTableDefaults(table);
      }

      var requireVersion = table.id === 'ihReqTable';
      var saved = loadSavedColumnWidths(storageKey, colCount, requireVersion);
      if (table.id === 'ihReqTable') {
        for (var d = 0; d < IH_REQ_TABLE_COLS; d++) {
          if (saved[d]) {
            setTableColWidth(table, d, saved[d], IH_REQ_MIN_WIDTHS[d]);
          }
        }
        setTableColWidth(table, IH_REQ_TABLE_COLS - 1, Math.max(saved[IH_REQ_TABLE_COLS - 1] || IH_REQ_DEFAULT_WIDTHS[7], 220), 220);
      } else {
        headers.forEach(function (th, idx) {
          if (saved[idx]) setTableColWidth(table, idx, saved[idx]);
        });
      }

      headers.forEach(function (th, idx) {
        if (idx === headers.length - 1 || th.querySelector('.ih-column-resize-handle')) return;

        var handle = document.createElement('span');
        handle.className = 'ih-column-resize-handle';
        if (table.id === 'ihReqTable') handle.classList.add('ih-req-column-resize-handle');
        handle.setAttribute('role', 'separator');
        handle.setAttribute('aria-orientation', 'vertical');
        handle.setAttribute('aria-label', 'Resize column');
        th.appendChild(handle);

        var startX = 0;
        var startW = 0;
        var dragging = false;

        handle.addEventListener('pointerdown', function (event) {
          dragging = true;
          startX = event.clientX;
          startW = th.offsetWidth;
          handle.classList.add('is-dragging');
          document.body.classList.add('ih-col-resize-active');
          handle.setPointerCapture(event.pointerId);
          event.preventDefault();
        });

        handle.addEventListener('pointermove', function (event) {
          if (!dragging) return;
          var minW = 90;
          var maxW = 420;
          if (table.id === 'ihReqTable') {
            minW = IH_REQ_MIN_WIDTHS[idx] || 90;
            if (idx === IH_REQ_TABLE_COLS - 2) maxW = 180;
          }
          var next = Math.max(minW, Math.min(maxW, startW + (event.clientX - startX)));
          setTableColWidth(table, idx, next, minW);
        });

        handle.addEventListener('pointerup', function (event) {
          if (!dragging) return;
          dragging = false;
          handle.classList.remove('is-dragging');
          document.body.classList.remove('ih-col-resize-active');
          handle.releasePointerCapture(event.pointerId);
          saved[idx] = Math.round(th.offsetWidth);
          if (table.id === 'ihReqTable') saved._v = IH_REQ_TABLE_COLS;
          try {
            localStorage.setItem(storageKey, JSON.stringify(saved));
          } catch (e) { /* ignore */ }
        });

        handle.addEventListener('pointercancel', function () {
          dragging = false;
          handle.classList.remove('is-dragging');
          document.body.classList.remove('ih-col-resize-active');
        });
      });
    });
  }

  ready(function () {
    initCardMenus();
    initRequestTableColumns();
    initChatHeightHandles();
    setTimeout(initChatHeightHandles, 800);
    setTimeout(initRequestTableColumns, 800);
  });
})();
