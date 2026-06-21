(function () {
  'use strict';

  function qs(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }

  function qsa(sel, ctx) {
    return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
  }

  function initListingTabs() {
    qsa('.ih-figma-listing-tabs').forEach(function (wrap) {
      var host = wrap.parentElement;
      var sectionCards = host ? qsa('[data-listing-kind]', host) : [];
      var grid = host && host.querySelector('.ih-dash-listing-grid, .ih-listing-grid');

      wrap.addEventListener('click', function (e) {
        var btn = e.target.closest('.ih-figma-listing-tab');
        if (!btn) return;
        var filter = btn.getAttribute('data-filter') || 'all';
        qsa('.ih-figma-listing-tab', wrap).forEach(function (t) {
          t.classList.toggle('is-active', t === btn);
          t.setAttribute('aria-pressed', t === btn ? 'true' : 'false');
        });

        if (sectionCards.length) {
          sectionCards.forEach(function (section) {
            var kind = section.getAttribute('data-listing-kind') || '';
            var show = filter === 'all' || kind === filter;
            section.classList.toggle('is-filter-hidden', !show);
            section.setAttribute('aria-hidden', show ? 'false' : 'true');
          });
          return;
        }

        if (!grid) return;
        qsa('[data-listing-kind]', grid).forEach(function (card) {
          var kind = card.getAttribute('data-listing-kind') || '';
          var show = filter === 'all' || kind === filter;
          card.classList.toggle('is-filter-hidden', !show);
          card.setAttribute('aria-hidden', show ? 'false' : 'true');
        });
      });
    });
  }

  function initApprovalActions() {
    document.addEventListener('click', function (event) {
      var btn = event.target.closest('.ih-figma-approval-btn[data-request-status]');
      if (!btn) return;
      ihDashReqStatus(btn, btn.getAttribute('data-request-status'));
    });
  }

  function bumpPendingPill(delta) {
    qsa('.ih-figma-pending-pill').forEach(function (pill) {
      var n = parseInt(pill.textContent, 10) || 0;
      n = Math.max(0, n + delta);
      if (n <= 0) {
        pill.remove();
        return;
      }
      pill.textContent = n + ' PENDING';
    });
  }

  function ihDashReqStatus(btn, status) {
    if (!btn || btn.disabled) return;
    var card = btn.closest('.ih-figma-approval-row, .ih-figma-approval-card');
    var reqId = parseInt(btn.getAttribute('data-req-id') || (card && card.getAttribute('data-req-id')) || '0', 10);
    if (!reqId || !window.ihAjax) return;

    var wasPending = card && !card.classList.contains('is-resolved');
    var list = card ? card.parentElement : null;
    btn.disabled = true;
    qsa('.ih-figma-approval-btn', card).forEach(function (b) {
      b.disabled = true;
    });

    var fd = new FormData();
    fd.append('action', 'ih_update_request_status');
    fd.append('nonce', window.ihAjax.nonce);
    fd.append('request_id', reqId);
    fd.append('status', status);

    fetch(window.ihAjax.url, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.success) {
          qsa('.ih-figma-approval-btn', card).forEach(function (b) {
            b.disabled = false;
          });
          return;
        }
        if (card) {
          card.classList.add('is-resolved');
          card.style.opacity = '0.45';
          if (wasPending) bumpPendingPill(-1);
          var reqIdAttr = card.getAttribute('data-req-id');
          if (reqIdAttr) {
            document.querySelectorAll('.ih-figma-approval-row[data-req-id="' + reqIdAttr + '"], .ih-figma-approval-card[data-req-id="' + reqIdAttr + '"]').forEach(function (el) {
              if (el !== card) el.remove();
            });
          }
          window.setTimeout(function () {
            card.style.transition = 'opacity .25s ease, max-height .3s ease, margin .3s ease, padding .3s ease';
            card.style.maxHeight = card.offsetHeight + 'px';
            card.style.overflow = 'hidden';
            requestAnimationFrame(function () {
              card.style.opacity = '0';
              card.style.maxHeight = '0';
              card.style.marginTop = '0';
              card.style.marginBottom = '0';
              card.style.paddingTop = '0';
              card.style.paddingBottom = '0';
            });
            window.setTimeout(function () {
              card.remove();
              if (list && (list.classList.contains('ih-figma-approval-list') || list.classList.contains('ih-dash-approval-mobile-cards')) && !list.querySelector('.ih-figma-approval-row, .ih-figma-approval-card')) {
                var empty = document.createElement('div');
                empty.className = 'ih-figma-approval-empty';
                empty.textContent = 'No pending approvals. New requests will appear here first.';
                list.appendChild(empty);
              }
            }, 320);
          }, 400);
        }
      })
      .catch(function () {
        qsa('.ih-figma-approval-btn', card).forEach(function (b) {
          b.disabled = false;
        });
      });
  }

  window.ihDashReqStatus = ihDashReqStatus;

  document.addEventListener('DOMContentLoaded', function () {
    initListingTabs();
    initApprovalActions();
  });
})();
