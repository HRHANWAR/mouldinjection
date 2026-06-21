/* Outbound message delivery states — Figma 229:2199 */
(function (w) {
  'use strict';

  var icons = {
    clock: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    tick: '<svg width="14" height="10" viewBox="0 0 24 18" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M1 9l5 5L17 3"/></svg>',
    tickDouble: '<svg width="16" height="10" viewBox="0 0 28 18" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M1 9l5 5L17 3"/><path d="M7 14L23 3"/></svg>',
    tickRead: '<svg width="16" height="10" viewBox="0 0 28 18" fill="none" stroke="#17C7C7" stroke-width="2.4" aria-hidden="true"><path d="M1 9l5 5L17 3"/><path d="M7 14L23 3"/></svg>',
    alert: '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 2 20h20L12 2zm0 6v5m0 3h.01"/></svg>',
    retry: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>'
  };

  function resolveStatus(m) {
    if (m._failed || m.delivery_status === 'failed' || m.status === 'failed') return 'failed';
    if (m._sending || m.delivery_status === 'sending') return 'sending';
    if (m.read_at) return 'read';
    var s = String(m.delivery_status || m.status || '').toLowerCase();
    if (s === 'read' || m.is_read === 1 || m.is_read === true) return 'read';
    if (s === 'delivered') return 'delivered';
    if (s === 'sent') return 'sent';
    if (s === 'failed') return 'failed';
    if (m.id) return 'sent';
    return 'sending';
  }

  function statusIcon(status) {
    if (status === 'sending') return icons.clock;
    if (status === 'sent') return icons.tick;
    if (status === 'delivered') return icons.tickDouble;
    if (status === 'read') return icons.tickRead;
    return '';
  }

  function rowClass(status) {
    return status ? ' ih-msg--' + status : '';
  }

  function metaStatusHTML(status) {
    if (!status || status === 'failed') return '';
    var icon = statusIcon(status);
    if (!icon) return '';
    return '<span class="ihc-status-icon ihc-status-icon--' + status + '" aria-hidden="true">' + icon + '</span>';
  }

  function failRowHTML() {
    return '<div class="ihc-fail">'
      + '<span class="ihc-fail-pill">' + icons.alert + '</span>'
      + '<span class="ihc-fail-txt">Not delivered</span>'
      + '<span class="ihc-fail-ico">' + icons.retry + '</span>'
      + '<button type="button" class="ihc-fail-retry" data-retry-msg>Tap to retry</button>'
      + '</div>';
  }

  function applyRowState(row, status) {
    if (!row) return;
    row.classList.remove('ih-msg--sending', 'ih-msg--sent', 'ih-msg--delivered', 'ih-msg--read', 'ih-msg--failed');
    if (status) row.classList.add('ih-msg--' + status);
    var meta = row.querySelector('.ihc-meta');
    if (meta) {
      var old = meta.querySelector('.ihc-status-icon');
      if (old) old.remove();
      if (status && status !== 'failed') {
        meta.insertAdjacentHTML('beforeend', metaStatusHTML(status));
      }
    }
    var col = row.querySelector('.ihc-msg-wrap') || row.querySelector('.ihc-col');
    if (!col) return;
    var fail = col.querySelector('.ihc-fail');
    if (status === 'failed') {
      if (!fail) col.insertAdjacentHTML('beforeend', failRowHTML());
    } else if (fail) {
      fail.remove();
    }
  }

  w.IHChatStates = {
    resolveStatus: resolveStatus,
    statusIcon: statusIcon,
    rowClass: rowClass,
    metaStatusHTML: metaStatusHTML,
    failRowHTML: failRowHTML,
    applyRowState: applyRowState
  };
})(window);
