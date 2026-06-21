/**
 * IH Messages Console — Figma node 289:2
 * Builds the right-hand "CONTACT REQUESTS" rail from the live requests
 * table and moves it into the console grid. All status changes go
 * through the existing ihReqStatus() AJAX handler, so admin/user
 * message logic is unchanged.
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    var wrap = document.querySelector('.ih-messages-wrap');
    var table = document.getElementById('requestsTable');
    if (!wrap || wrap.querySelector('.ih-mc-rail')) return;

    /* ── Read live rows from the requests table ── */
    var rows = table ? Array.prototype.slice.call(table.querySelectorAll('tbody tr')) : [];
    var items = rows.map(function (tr) {
      var tds = tr.querySelectorAll('td');
      return {
        id: parseInt(tr.getAttribute('data-req-id') || '0', 10),
        status: String(tr.getAttribute('data-status') || 'pending').toLowerCase(),
        type: String(tr.getAttribute('data-type') || 'machine').toLowerCase(),
        name: tds[0] ? tds[0].textContent.trim() : '',
        email: tds[1] ? tds[1].textContent.trim() : '',
        date: tds[2] ? tds[2].textContent.trim() : '',
        location: tds[3] ? tds[3].textContent.trim() : '',
        row: tr
      };
    }).filter(function (i) { return i.id > 0; });

    /* ── KPI numbers drive the segmented bar ── */
    function kpi(idx) {
      var els = document.querySelectorAll('.ih-mc-kpis .ih-mc-kpi-num');
      return els[idx] ? parseInt(els[idx].textContent, 10) || 0 : 0;
    }
    var total = Math.max(1, kpi(0));
    var seg = {
      approved: Math.round(kpi(2) / total * 100),
      pending: Math.round(kpi(1) / total * 100),
      rejected: Math.round(kpi(3) / total * 100),
      completed: Math.round(kpi(4) / total * 100)
    };

    /* ── Build the rail ── */
    var rail = document.createElement('aside');
    rail.className = 'ih-mc-rail';
    rail.innerHTML =
      '<div class="ih-mc-rail-head">' +
        '<div class="ih-mc-rail-title"><span>Contact Requests</span><b>' + kpi(0) + '</b></div>' +
        '<div class="ih-mc-rail-segbar">' +
          '<i style="width:' + seg.approved + '%;background:#22c55e;"></i>' +
          '<i style="width:' + seg.pending + '%;background:#f5b83d;"></i>' +
          '<i style="width:' + seg.rejected + '%;background:#ef4444;"></i>' +
          '<i style="width:' + seg.completed + '%;background:#3b82f6;"></i>' +
        '</div>' +
        '<div class="ih-mc-rail-legend">' +
          '<span><i style="background:#22c55e;"></i>Approved</span>' +
          '<span><i style="background:#f5b83d;"></i>Pending</span>' +
          '<span><i style="background:#ef4444;"></i>Rejected</span>' +
          '<span><i style="background:#3b82f6;"></i>Done</span>' +
        '</div>' +
      '</div>' +
      '<div class="ih-mc-rail-queue-label">Queue · Newest First</div>' +
      '<div class="ih-mc-rail-list"></div>';

    var list = rail.querySelector('.ih-mc-rail-list');

    function esc(s) {
      var d = document.createElement('div');
      d.textContent = String(s == null ? '' : s);
      return d.innerHTML;
    }

    function chipClass(status) {
      if (status === 'approved') return 'is-approved';
      if (status === 'rejected') return 'is-rejected';
      if (status === 'completed') return 'is-completed';
      return 'is-pending';
    }

    function buildCard(item) {
      var card = document.createElement('div');
      card.className = 'ih-mc-qcard ' + (item.status === 'pending' ? 'is-pending' : '');
      card.setAttribute('data-rail-id', item.id);
      var meta = [item.type === 'tool' ? 'TL' : 'MCH', item.email || item.location].filter(Boolean).join(' · ');
      card.innerHTML =
        '<div class="ih-mc-qcard-top">' +
          '<span class="ih-mc-qcard-name">' + esc(item.name || 'Unknown user') + '</span>' +
          '<span class="ih-mc-qchip ' + chipClass(item.status) + '">' + esc(item.status) + '</span>' +
        '</div>' +
        '<div class="ih-mc-qcard-meta">' + esc(meta) + '</div>' +
        '<div class="ih-mc-qcard-date">' + esc(item.date) + '</div>' +
        (item.status === 'pending'
          ? '<div class="ih-mc-qcard-actions">' +
              '<button type="button" class="ih-mc-qbtn is-approve" data-act="Approved">✓ Approve</button>' +
              '<button type="button" class="ih-mc-qbtn is-reject" data-act="Rejected">✕ Reject</button>' +
              '<button type="button" class="ih-mc-qbtn is-view" data-act="view">View</button>' +
            '</div>'
          : '<div class="ih-mc-qcard-actions">' +
              '<button type="button" class="ih-mc-qbtn is-view" data-act="view">View</button>' +
            '</div>');
      card.addEventListener('click', function (e) {
        var btn = e.target.closest('.ih-mc-qbtn');
        if (!btn) return;
        var act = btn.getAttribute('data-act');
        if (act === 'view') {
          if (item.row) {
            item.row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            item.row.style.transition = 'background .3s';
            item.row.style.background = '#f3f9ee';
            setTimeout(function () { item.row.style.background = ''; }, 1600);
          }
          return;
        }
        /* Approve / Reject through the existing AJAX handler */
        var nonce = (window.ihAjax && window.ihAjax.nonce) || window.ihNonce || '';
        card.querySelectorAll('.ih-mc-qbtn').forEach(function (b) { b.setAttribute('disabled', 'disabled'); });
        if (typeof window.ihReqStatus === 'function') {
          window.ihReqStatus(item.id, act, nonce);
        } else {
          var fd = new FormData();
          fd.append('action', 'ih_update_request_status');
          fd.append('nonce', nonce);
          fd.append('id', String(item.id));
          fd.append('status', act);
          fetch((window.ihAjax && window.ihAjax.url) || ajaxurl, { method: 'POST', body: fd }).catch(function () {});
        }
        /* Optimistic card update */
        var chip = card.querySelector('.ih-mc-qchip');
        var st = act.toLowerCase();
        item.status = st;
        if (chip) { chip.className = 'ih-mc-qchip ' + chipClass(st); chip.textContent = st; }
        card.classList.remove('is-pending');
        var actions = card.querySelector('.ih-mc-qcard-actions');
        if (actions) actions.innerHTML = '<button type="button" class="ih-mc-qbtn is-view" data-act="view">View</button>';
      });
      return card;
    }

    /* Pending first (newest first within group), then the rest */
    var pending = items.filter(function (i) { return i.status === 'pending'; });
    var others = items.filter(function (i) { return i.status !== 'pending'; });
    var ordered = pending.concat(others).slice(0, 8);
    if (ordered.length === 0) {
      list.innerHTML = '<div class="ih-mc-rail-empty">No contact requests yet.<br>New requests will appear here.</div>';
    } else {
      ordered.forEach(function (i) { list.appendChild(buildCard(i)); });
    }

    /* ── Move the rail into the console grid ── */
    wrap.appendChild(rail);
    /* Neutralise the legacy inline height so the CSS grid height wins */
    wrap.style.removeProperty('height');
  });
})();
