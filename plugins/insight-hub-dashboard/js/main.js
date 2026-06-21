/* Insight Hub Dashboard v2.0 — main.js */
(function ($) {
  "use strict";

  /* ══════════════════════════════════════
     TOAST NOTIFICATION
  ══════════════════════════════════════ */
  function toast(msg, type) {
    var el = document.getElementById('ih-toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'ih-toast';
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.style.background = type === 'error' ? '#ef4444' : '#1f3d2e';
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(function () { el.classList.remove('show'); }, 3000);
  }
  function ihToast(msg, type) { toast(msg, type); }

  /* ══════════════════════════════════════
     SUCCESS MODAL
  ══════════════════════════════════════ */
  function showSuccessModal(msg) {
    var id = 'ihSuccessOverlay';
    var el = document.getElementById(id);
    if (!el) {
      document.body.insertAdjacentHTML('beforeend',
        '<div id="' + id + '" style="position:fixed;inset:0;background:rgba(15,23,42,.5);-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);z-index:999999;display:flex;align-items:center;justify-content:center;">' +
          '<div style="background:#fff;border-radius:24px;padding:36px 32px 28px;text-align:center;min-width:300px;box-shadow:0 24px 60px rgba(15,23,42,.18);animation:ihModalPop .22s cubic-bezier(.34,1.56,.64,1);">' +
            '<div style="width:64px;height:64px;border-radius:999px;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;">✓</div>' +
            '<p id="ihSuccessMsg" style="margin:0;font-size:16px;font-weight:700;color:#111827;"></p>' +
          '</div>' +
        '</div>'
      );
      el = document.getElementById(id);
    }
    document.getElementById('ihSuccessMsg').textContent = msg;
    el.style.display = 'flex';
    setTimeout(function () { el.style.display = 'none'; }, 2000);
  }

  /* ══════════════════════════════════════
     GLOBAL REMOVE LISTING MODAL
  ══════════════════════════════════════ */
  function showRemoveListingModal(type, onConfirm) {
    var overlay = document.getElementById('globalRemoveOverlay');
    if (!overlay) {
      document.body.insertAdjacentHTML('beforeend',
        '<div class="ih-remove-overlay" id="globalRemoveOverlay">' +
          '<div class="ih-remove-modal">' +
            '<div class="ih-remove-icon">🗑️</div>' +
            '<p class="ih-remove-title" id="globalRemoveTitle">Are you sure you want to remove this ' + type + ' Listing?</p>' +
            '<div class="ih-remove-btns">' +
              '<button class="ih-btn" id="globalRemoveCancel">✕ No</button>' +
              '<button class="ih-btn" id="globalRemoveConfirm">✓ Yes</button>' +
            '</div>' +
          '</div>' +
        '</div>'
      );
      overlay = document.getElementById('globalRemoveOverlay');
    } else {
      var titleEl = document.getElementById('globalRemoveTitle');
      if (titleEl) titleEl.textContent = 'Are you sure you want to remove this ' + type + ' Listing?';
    }

    // Show
    overlay.classList.add('show');

    // Handlers — replace to avoid stacking
    var cancelBtn  = document.getElementById('globalRemoveCancel');
    var confirmBtn = document.getElementById('globalRemoveConfirm');

    var newCancel  = cancelBtn.cloneNode(true);
    var newConfirm = confirmBtn.cloneNode(true);
    cancelBtn.parentNode.replaceChild(newCancel, cancelBtn);
    confirmBtn.parentNode.replaceChild(newConfirm, confirmBtn);

    newCancel.addEventListener('click', function () {
      overlay.classList.remove('show');
    });
    newConfirm.addEventListener('click', function () {
      overlay.classList.remove('show');
      if (onConfirm) onConfirm();
    });

    // Close on backdrop click
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.classList.remove('show');
    }, { once: true });
  }

  /* ══════════════════════════════════════
     DROPDOWN MENUS
  ══════════════════════════════════════ */
  function initDropdowns() {
    document.addEventListener('click', function (e) {

      // 1. Toggle 3-dot menu
      var dotBtn = e.target.closest('.ih-card-menu-btn, .ih-icon-btn-sm');
      if (dotBtn && !dotBtn.closest('#ihUsersRedesign') && !dotBtn.hasAttribute('data-row-menu') && !dotBtn.closest('#ihReqTable')) {
        e.stopPropagation();
        var menuWrap = dotBtn.closest('.ih-card-menu');
        var dropdown = menuWrap ? menuWrap.querySelector('.ih-dropdown') : dotBtn.nextElementSibling;
        var isOpen   = dropdown && !dropdown.classList.contains('hidden');
        document.querySelectorAll('.ih-listing-card.is-menu-open, .ih-dash-listing-card.is-menu-open').forEach(function (c) { c.classList.remove('is-menu-open'); });
        document.querySelectorAll('.ih-dropdown').forEach(function (d) { d.classList.add('hidden'); });
        if (dropdown && !isOpen) {
          dropdown.classList.remove('hidden');
          var card = dotBtn.closest('.ih-listing-card, .ih-dash-listing-card');
          if (card) card.classList.add('is-menu-open');
          if (menuWrap && typeof window.ihPositionCardDropdown === 'function') {
            window.ihPositionCardDropdown(dotBtn, dropdown);
          } else if (menuWrap) {
            var rect = dotBtn.getBoundingClientRect();
            var w = dropdown.offsetWidth || 180;
            dropdown.style.position = 'fixed';
            dropdown.style.zIndex = '100050';
            dropdown.style.top = Math.round(rect.bottom + 6) + 'px';
            dropdown.style.left = Math.round(Math.min(rect.right - w, window.innerWidth - w - 8)) + 'px';
          }
        }
        return;
      }

      // 2. Delete Machine
      var btn = e.target.closest('.ih-delete-machine');
      if (btn) {
        e.stopPropagation();
        document.querySelectorAll('.ih-dropdown').forEach(function (d) { d.classList.add('hidden'); });
        var machineId   = btn.dataset.id;
        var machineCard = btn.closest('.ih-listing-card') || btn.closest('.ih-machine-listing-shell');
        showRemoveListingModal('Machine', function () {
          jQuery.post(ihAjax.url, {
            action: 'ih_delete_machine',
            nonce:  ihAjax.nonce,
            id:     machineId
          }, function (res) {
            if (res && res.success) {
              if (machineCard) {
                machineCard.style.transition = 'opacity .3s, transform .3s';
                machineCard.style.opacity = '0';
                machineCard.style.transform = 'scale(.96)';
                setTimeout(function () { machineCard.remove(); }, 320);
              }
              showSuccessModal('Removed Successfully');
            } else {
              toast((res && res.data && res.data.message) ? res.data.message : 'Delete failed', 'error');
            }
          }).fail(function () { toast('Request failed', 'error'); });
        });
        return;
      }

      // 3. Delete Tool
      var tbtn = e.target.closest('.ih-delete-tool');
      if (tbtn) {
        e.stopPropagation();
        document.querySelectorAll('.ih-dropdown').forEach(function (d) { d.classList.add('hidden'); });
        var toolId   = tbtn.dataset.id;
        var toolCard = tbtn.closest('.ih-listing-card') || tbtn.closest('.ih-machine-listing-shell');
        showRemoveListingModal('Tool', function () {
          jQuery.post(ihAjax.url, {
            action: 'ih_delete_tool',
            nonce:  ihAjax.nonce,
            id:     toolId
          }, function (res) {
            if (res && res.success) {
              if (toolCard) {
                toolCard.style.transition = 'opacity .3s, transform .3s';
                toolCard.style.opacity = '0';
                toolCard.style.transform = 'scale(.96)';
                setTimeout(function () { toolCard.remove(); }, 320);
              }
              showSuccessModal('Removed Successfully');
            } else {
              toast((res && res.data && res.data.message) ? res.data.message : 'Delete failed', 'error');
            }
          }).fail(function () { toast('Request failed', 'error'); });
        });
        return;
      }

      // 4. Dropdown item click
      var dropItem = e.target.closest('.ih-dropdown-item');
      if (dropItem && !dropItem.classList.contains('ih-delete-machine') && !dropItem.classList.contains('ih-delete-tool')) {
        document.querySelectorAll('.ih-dropdown').forEach(function (d) { d.classList.add('hidden'); });
        return;
      }

      // 5. Outside click
      if (!e.target.closest('.ih-dropdown') && !e.target.closest('.ih-card-menu-btn, .ih-icon-btn-sm, .ih-req-menu-btn, [data-row-menu]')) {
        document.querySelectorAll('.ih-dropdown').forEach(function (d) { d.classList.add('hidden'); });
      }
    });
  }

  /* ══════════════════════════════════════
     MESSAGES
  ══════════════════════════════════════ */
  function initMessages() {
    var ts = document.getElementById('threadSearch');
    if (ts) {
      ts.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.ih-thread-item').forEach(function (item) {
          item.style.display = (item.dataset.name || '').indexOf(q) !== -1 ? '' : 'none';
        });
      });
    }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.ih-block-thread-btn');
      if (!btn) return;
      jQuery.post(ihAjax.url, {
        action:    'ih_block_thread',
        nonce:     ihAjax.nonce,
        thread_id: btn.dataset.id
      }, function (res) {
        if (res.success) {
          toast(res.data.blocked ? '✓ User blocked' : '✓ User unblocked');
          setTimeout(function () { location.reload(); }, 800);
        }
      });
    });

    var msgs = document.getElementById('chatMessages');
    if (msgs) msgs.scrollTop = msgs.scrollHeight;
  }

  function appendMsg(text, fromMe, time) {
    var container = document.getElementById('chatMessages');
    if (!container) return;
    var div = document.createElement('div');
    div.className = 'ih-msg ' + (fromMe ? 'ih-msg-me' : 'ih-msg-them');
    div.innerHTML =
      '<div class="ih-msg-bubble">' + escHtml(text) +
      '<div class="ih-msg-time">' + escHtml(time) + '</div></div>' +
      (fromMe ? '<div class="ih-msg-avatar-me">Y</div>' : '');
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ══════════════════════════════════════
     CHARTS (Chart.js)
  ══════════════════════════════════════ */
  function initCharts() {
    var barCtx = document.getElementById('ih-bar-chart');
    var pieCtx = document.getElementById('ih-pie-chart');

    if (barCtx && typeof Chart !== 'undefined' && Chart.getChart(barCtx)) return;
    if (pieCtx && typeof Chart !== 'undefined' && Chart.getChart(pieCtx)) return;

    if (barCtx && typeof Chart !== 'undefined') {
      var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      var values = (window.ihBarData && window.ihBarData.length === 12) ? window.ihBarData : [0,0,0,0,0,0,0,0,0,0,0,0];
      var colors = (window.ihBarColors && window.ihBarColors.length === 12)
        ? window.ihBarColors
        : values.map(function (_, i) { return i === (new Date()).getMonth() ? '#1f3d2e' : '#c8e88e'; });
      new Chart(barCtx, {
        type: 'bar',
        data: { labels: window.ihBarLabels || months, datasets: [{ data: values, backgroundColor: colors, borderRadius: 6, borderSkipped: false }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { stepSize: 1, precision: 0 } }, x: { grid: { display: false } } } }
      });
    }

    if (pieCtx && typeof Chart !== 'undefined') {
      var pieValues = (window.ihPieData && window.ihPieData.length === 3) ? window.ihPieData : [0, 0, 0];
      var hasData   = pieValues.reduce(function (a, b) { return a + b; }, 0) > 0;
      new Chart(pieCtx, {
        type: 'doughnut',
        data: { labels: ['Approved','Pending','Rejected'], datasets: [{
          data:            hasData ? pieValues : [1, 0, 0],
          backgroundColor: hasData ? ['#22c55e','#c8e88e','#ef4444'] : ['#e5e7eb','#e5e7eb','#e5e7eb'],
          borderWidth: 0
        }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false } } }
      });
    }
  }

  window.ihUpdateRequestAnalytics = function (stats) {
    if (!stats) return;
    var approved = parseInt(stats.approved || 0, 10) || 0;
    var pending  = parseInt(stats.pending  || 0, 10) || 0;
    var rejected = parseInt(stats.rejected || 0, 10) || 0;
    var total    = parseInt(stats.total    || 0, 10);
    if (total <= 0) total = approved + pending + rejected;

    var apct, ppct, rpct;
    if (typeof stats.approved_pct !== 'undefined') {
      apct = parseInt(stats.approved_pct || 0, 10) || 0;
      ppct = parseInt(stats.pending_pct  || 0, 10) || 0;
      rpct = parseInt(stats.rejected_pct || 0, 10) || 0;
    } else {
      var denom = total > 0 ? total : 0;
      apct = denom ? Math.round(approved / denom * 100) : 0;
      ppct = denom ? Math.round(pending  / denom * 100) : 0;
      rpct = Math.max(0, 100 - apct - ppct);
    }

    var pctEl = document.getElementById('ih-donut-pct') || document.querySelector('.ih-donut-pct');
    if (pctEl) pctEl.textContent = apct + '%';

    var subEl = document.getElementById('ih-donut-sub') || document.querySelector('.ih-donut-sub');
    if (subEl) {
      if (stats.month) {
        var mnames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var mi = (parseInt(stats.month, 10) || 0) - 1;
        if (mi >= 0 && mi < 12) subEl.textContent = 'Requests Approved (' + mnames[mi] + (stats.year ? ' ' + stats.year : '') + ')';
      } else {
        subEl.textContent = 'Requests Approved';
      }
    }

    var aEl = document.querySelector('[data-ih-legend="Approved"]');
    var pEl = document.querySelector('[data-ih-legend="Pending"]');
    var rEl = document.querySelector('[data-ih-legend="Rejected"]');
    if (aEl) aEl.textContent = 'Approved ' + approved + ' (' + apct + '%)';
    if (pEl) pEl.textContent = 'Pending '  + pending  + ' (' + ppct + '%)';
    if (rEl) rEl.textContent = 'Rejected ' + rejected + ' (' + rpct + '%)';

    if (window.ihCharts && window.ihCharts.pie) {
      window.ihCharts.pie.data.datasets[0].data = [approved, pending, rejected];
      window.ihCharts.pie.update();
    }
  };

  window.ihRefreshRequestAnalytics = function (opts) {
    if (typeof jQuery === 'undefined' || !window.ihAjax) return;
    opts = opts || {};
    if (opts.year)  window.ihAnalyticsYear  = opts.year;
    if (opts.month) window.ihAnalyticsMonth = opts.month;
    var payload = { action: 'ih_get_request_analytics', nonce: window.ihAjax.nonce };
    if (opts.year)  payload.year  = opts.year;
    if (opts.month) payload.month = opts.month;
    jQuery.post(window.ihAjax.url, payload, function (res) {
      if (res && res.success && res.data) window.ihUpdateRequestAnalytics(res.data);
      else ihToast('Could not load analytics', 'error');
    }).fail(function () { ihToast('Analytics request failed', 'error'); });
  };

  window.ihDeleteRequest = function (reqId) {
    reqId = parseInt(reqId || 0, 10);
    if (!reqId) return;
    showRemoveListingModal('Request', function () {
      if (!window.ihAjax) return;
      var fd = new FormData();
      fd.append('action', 'ih_delete_request');
      fd.append('nonce', window.ihAjax.nonce);
      fd.append('request_id', String(reqId));
      fetch(window.ihAjax.url, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.success) {
            var row = document.querySelector('tr[data-req-id="' + reqId + '"]');
            if (row) {
              row.style.transition = 'opacity .3s';
              row.style.opacity = '0';
              setTimeout(function () { row.remove(); }, 320);
            }
            showSuccessModal('Removed Successfully');
          }
        });
    });
  };

  /* ══════════════════════════════════════
     FILTER REQUESTS TABLE
  ══════════════════════════════════════ */
  function ihFilterRequestTable(status) {
    var rows = document.querySelectorAll('#requestsTable tbody tr');
    if (!rows.length) return;
    var found = 0;
    rows.forEach(function (tr) {
      var badge     = tr.querySelector('.ih-badge');
      var rowStatus = badge ? badge.textContent.trim() : '';
      if (!status || rowStatus === status) { tr.style.display = ''; found++; }
      else tr.style.display = 'none';
    });
    document.querySelectorAll('.ih-status-filter-btn').forEach(function (btn) {
      btn.classList.toggle('active', btn.dataset.status === status);
    });
    ihToast('Showing ' + found + ' ' + status + ' requests');
  }

  /* ══════════════════════════════════════
     UPLOAD PREVIEW
  ══════════════════════════════════════ */
  function initUploadPreview() {
    document.querySelectorAll('.ih-file-secondary').forEach(function (input) {
      input.addEventListener('change', function () {
        var thumb = this.closest('.ih-upload-thumb');
        if (this.files && this.files[0]) {
          var reader = new FileReader();
          reader.onload = function (e) {
            thumb.style.backgroundImage    = 'url(' + e.target.result + ')';
            thumb.style.backgroundSize     = 'cover';
            thumb.style.backgroundPosition = 'center';
            thumb.querySelector('span').style.display = 'none';
          };
          reader.readAsDataURL(this.files[0]);
        }
      });
    });

    var mainUp = document.querySelector('.ih-file-input');
    if (mainUp) {
      mainUp.addEventListener('change', function () {
        var zone = this.closest('.ih-upload-main');
        if (this.files && this.files[0]) {
          var reader = new FileReader();
          reader.onload = function (e) {
            zone.style.backgroundImage    = 'url(' + e.target.result + ')';
            zone.style.backgroundSize     = 'cover';
            zone.style.backgroundPosition = 'center';
            zone.querySelector('.ih-upload-icon')  && (zone.querySelector('.ih-upload-icon').style.display  = 'none');
            zone.querySelector('.ih-upload-label') && (zone.querySelector('.ih-upload-label').style.display = 'none');
          };
          reader.readAsDataURL(this.files[0]);
        }
      });
    }
  }

  /* ══════════════════════════════════════
     TOGGLE SWITCH
  ══════════════════════════════════════ */
  function initToggles() {
    document.querySelectorAll('.ih-toggle-cb').forEach(function (cb) {
      var hidden = cb.previousElementSibling;
      if (hidden && hidden.type === 'hidden') {
        cb.addEventListener('change', function () { hidden.value = cb.checked ? 'Yes' : 'No'; });
      }
    });
  }

  /* ══════════════════════════════════════
     SUCCESS MODAL (WordPress page modal)
  ══════════════════════════════════════ */
  function initSuccessModal() {
    var modal = document.getElementById('successModal');
    if (modal) {
      modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });
    }
  }

  /* ══════════════════════════════════════
     MOBILE SIDEBAR
  ══════════════════════════════════════ */
  function initMobileSidebar() {
    if (document.querySelector('.ih-wrap.ih-figma-nav') || document.querySelector('.ih-shell.ih-figma-dashboard')) {
      return;
    }
    var hamburger = document.getElementById('ihHamburger');
    var sidebar   = document.getElementById('ihSidebar');
    var overlay   = document.getElementById('ihOverlay');
    var closeBtn  = document.getElementById('ihSidebarClose');
    if (!hamburger || !sidebar) return;

    function open()  { sidebar.classList.add('open');    overlay && overlay.classList.add('active');    hamburger.classList.add('open');    document.body.style.overflow = 'hidden'; }
    function close() { sidebar.classList.remove('open'); overlay && overlay.classList.remove('active'); hamburger.classList.remove('open'); document.body.style.overflow = '';       }

    hamburger.addEventListener('click', function () { sidebar.classList.contains('open') ? close() : open(); });
    if (overlay)  overlay.addEventListener('click', close);
    if (closeBtn) closeBtn.addEventListener('click', close);
    sidebar.querySelectorAll('.ih-nav-item').forEach(function (link) {
      link.addEventListener('click', function () { if (window.innerWidth <= 768) close(); });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  }

  /* ══════════════════════════════════════
     MODAL POP ANIMATION (global)
  ══════════════════════════════════════ */
  var modalStyle = document.createElement('style');
  modalStyle.textContent = '@keyframes ihModalPop { from { opacity:0; transform:scale(.92) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }';
  document.head.appendChild(modalStyle);

  /* ══════════════════════════════════════
     INIT
  ══════════════════════════════════════ */
  window.initUsersPage     = function () {};
  window.initMachineSearch = function () {};
  window.initToolSearch    = function () {};
  window.showRemoveListingModal = showRemoveListingModal;
  window.showSuccessModal       = showSuccessModal;

  $(function () {
    initDropdowns();
    initUsersPage();
    initMachineSearch();
    initToolSearch();
    initMessages();
    initCharts();
    initUploadPreview();
    initToggles();
    initSuccessModal();
    initMobileSidebar();
  });

}(jQuery));