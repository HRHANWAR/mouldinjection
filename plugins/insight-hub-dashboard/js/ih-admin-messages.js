/* Admin messaging engine — Figma 196:2199 */

(function () {

  'use strict';

  var C = window.IHCHAT;

  if (!C || C.role !== 'admin') return;



  var ST = window.IHChatStates || {};

  var lastId = C.lastId || 0;

  var msgs = document.getElementById('ihcMsgs');

  var pending = [];

  var activeFilter = 'all';

  var tempSeq = 0;

  var newMsgs = null;



  function $(s, r) { return (r || document).querySelector(s); }

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

  function msgText(m) { return m.message || m.text || ''; }



  function api(action, params, method) {

    var url = C.ajax + '?action=' + action;

    var opt = { method: method || 'GET', credentials: 'same-origin' };

    if (method === 'POST') {

      opt.body = params;

    } else {

      url += '&nonce=' + encodeURIComponent(C.nonce) + (params ? '&' + params : '');

    }

    return fetch(url, opt).then(function (r) { return r.json(); });

  }



  function apiPost(action, data) {

    var fd = data instanceof FormData ? data : new FormData();

    if (!(data instanceof FormData)) {

      Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });

    }

    fd.append('action', action);

    if (!fd.has('nonce')) fd.append('nonce', C.nonce);

    return fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });

  }



  function mine(from_me) { return from_me == 1; }



  function icon(n) {

    var m = {

      file: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>',

      video: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M10 9l5 3-5 3z" fill="currentColor"/></svg>',

      x: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>'

    };

    return m[n] || '';

  }



  function outboundStatus(m, me) {

    if (!me || !ST.resolveStatus) return null;

    return ST.resolveStatus(m);

  }



  function bubbleHTML(m) {

    var me = mine(m.from_me);

    var status = outboundStatus(m, me);

    var inner = '';

    if (m.attachment_url) {

      var t = m.attachment_type || 'file';

      if (t === 'image') {

        inner += '<a class="ihc-att-img" href="' + esc(m.attachment_url) + '" target="_blank" data-img><img src="' + esc(m.attachment_url) + '" alt=""></a>';

      } else {

        inner += '<a class="ihc-att-file" href="' + esc(m.attachment_url) + '" target="_blank" download>'

          + '<span class="fi">' + (t === 'video' ? icon('video') : icon('file')) + '</span>'

          + '<span><b class="fn">' + esc(m.attachment_name || 'Attachment') + '</b><span class="fs">' + (t === 'video' ? 'Video' : 'File') + '</span></span></a>';

      }

    }

    var text = msgText(m);

    if (text) inner += '<div class="bt">' + esc(text) + '</div>';

    inner += '<div class="ihc-meta">' + esc(m.time || '') + (status && ST.metaStatusHTML ? ST.metaStatusHTML(status) : '') + '</div>';

    var rowCls = 'ihc-row ' + (me ? 'me' : 'them') + (status && ST.rowClass ? ST.rowClass(status) : '');

    var attrs = ' data-id="' + (m.id || '') + '"';

    if (m._tempId) attrs += ' data-temp-id="' + esc(m._tempId) + '"';

    if (status === 'failed' && text) attrs += ' data-retry-text="' + esc(text) + '"';

    var fail = status === 'failed' && ST.failRowHTML ? ST.failRowHTML() : '';

    return '<div class="' + rowCls + '"' + attrs + '><div class="ihc-msg-wrap"><div class="ihc-bubble">' + inner + '</div>' + fail + '</div></div>';

  }



  function findRow(m) {

    if (!msgs) return null;

    if (m.id) {

      var byId = msgs.querySelector('[data-id="' + m.id + '"]');

      if (byId) return byId;

    }

    if (m._tempId) return msgs.querySelector('[data-temp-id="' + m._tempId + '"]');

    return null;

  }



  function bindReactions(m) {

    var R = window.IHMsgReactions;

    if (!R) return;

    var row = findRow(m);

    if (!row) return;

    if (m.reactions) R.setChips(row, m.reactions);

    R.bindRow(row);

  }



  function upsert(m, scroll) {

    if (!msgs) return;

    m.message = msgText(m);

    var me = mine(m.from_me);

    var status = outboundStatus(m, me);

    var existing = findRow(m);

    if (existing) {

      if (m.id) {

        existing.setAttribute('data-id', m.id);

        existing.removeAttribute('data-temp-id');

      }

      if (m.time) {

        var meta = existing.querySelector('.ihc-meta');

        if (meta) {

          var iconEl = meta.querySelector('.ihc-status-icon');

          meta.textContent = m.time;

          if (iconEl) meta.appendChild(iconEl);

        }

      }

      if (ST.applyRowState) ST.applyRowState(existing, status);

      if (m.reactions && window.IHMsgReactions) IHMsgReactions.setChips(existing, m.reactions);

      if (m.id && m.id > lastId) lastId = m.id;

      return;

    }

    if (m.id && msgs.querySelector('[data-id="' + m.id + '"]')) return;

    var typing = $('#ihcTyping');

    if (typing) typing.insertAdjacentHTML('beforebegin', bubbleHTML(m));

    else msgs.insertAdjacentHTML('beforeend', bubbleHTML(m));

    bindReactions(m);

    if (m.id && m.id > lastId) lastId = m.id;

    if (newMsgs) newMsgs.onMessageAdded(m, scroll !== false && newMsgs.shouldAutoScroll());

    else if (scroll !== false) msgs.scrollTop = msgs.scrollHeight;

  }



  function buildSendForm(text) {

    var fd = new FormData();

    fd.append('action', C.sendAction);

    fd.append('nonce', C.nonce);

    fd.append('message', text);

    fd.append('user_id', C.activeUid);

    fd.append('thread_id', C.activeThread || 0);

    if (pending[0]) fd.append('attachment', pending[0].file);

    return fd;

  }



  function sendPayload(text) {

    return {

      message: text,

      attachment_url: pending[0] ? pending[0].url : '',

      attachment_type: pending[0] ? pending[0].type : '',

      attachment_name: pending[0] ? pending[0].file.name : ''

    };

  }



  function postMessage(text, tempId) {

    var fd = buildSendForm(text);

    var payload = sendPayload(text);

    return fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (res) {

      if (res && res.success && res.data) {

        if (res.data.thread_id) C.activeThread = res.data.thread_id;

        upsert({

          id: res.data.id,

          _tempId: tempId,

          from_me: 1,

          message: res.data.message || text,

          time: res.data.time || 'now',

          delivery_status: res.data.delivery_status || 'sent',

          attachment_url: res.data.attachment_url || payload.attachment_url,

          attachment_type: res.data.attachment_type || payload.attachment_type,

          attachment_name: res.data.attachment_name || payload.attachment_name

        });

        if (res.data.id && res.data.id > lastId) lastId = res.data.id;

        return res;

      }

      throw new Error((res && res.data) || 'Send failed');

    }).catch(function () {

      upsert({

        _tempId: tempId,

        from_me: 1,

        message: text,

        time: 'now',

        _failed: true,

        attachment_url: payload.attachment_url,

        attachment_type: payload.attachment_type,

        attachment_name: payload.attachment_name

      });

      var row = findRow({ _tempId: tempId });

      if (row) row.setAttribute('data-retry-text', text);

    });

  }



  function send() {

    if (!C.activeUid) return;

    var ta = $('#ihcInput');

    var text = (ta.value || '').trim();

    if (!text && !pending.length) return;

    var tempId = 't' + (++tempSeq);

    upsert({

      _tempId: tempId,

      _sending: true,

      from_me: 1,

      message: text,

      time: 'now',

      delivery_status: 'sending',

      attachment_url: pending[0] ? pending[0].url : '',

      attachment_type: pending[0] ? pending[0].type : '',

      attachment_name: pending[0] ? pending[0].file.name : ''

    });

    ta.value = '';

    ta.style.height = '';

    clearPreviews();

    postMessage(text, tempId);

  }



  function retryRow(row) {

    if (!row) return;

    var text = row.getAttribute('data-retry-text') || (row.querySelector('.bt') || {}).textContent || '';

    text = (text || '').trim();

    if (!text) return;

    var tempId = row.getAttribute('data-temp-id') || ('r' + (++tempSeq));

    row.setAttribute('data-temp-id', tempId);

    row.removeAttribute('data-id');

    if (ST.applyRowState) ST.applyRowState(row, 'sending');

    var meta = row.querySelector('.ihc-meta');

    if (meta) {

      var tnode = meta.childNodes[0];

      if (tnode && tnode.nodeType === 3) tnode.textContent = 'now';

    }

    postMessage(text, tempId);

  }



  function syncOutboundStatuses(map) {
    if (!map || !msgs || !ST.applyRowState) return;
    Object.keys(map).forEach(function (id) {
      var row = msgs.querySelector('.ihc-row.me[data-id="' + id + '"]');
      if (row) ST.applyRowState(row, map[id]);
    });
  }

  function poll() {

    if (!C.activeUid || window.ihSocketLive) return;

    api(C.getAction, 'user_id=' + C.activeUid + '&after_id=' + lastId).then(function (res) {

      if (!res || !res.success) return;

      var arr = (res.data && res.data.messages) || [];

      var typing = res.data && res.data.typing;

      if (typeof typing !== 'undefined') showTyping(!!typing);

      arr.forEach(function (m) {

        m.message = msgText(m);

        upsert(m);

        if (!mine(m.from_me)) notify(m);

      });

      syncOutboundStatuses(res.data && res.data.outbound_statuses);

      if (arr.some(function (m) { return !mine(m.from_me); }) && (!newMsgs || newMsgs.isAtBottom())) markRead();

    }).catch(function () {});

  }



  function markRead() {

    if (!C.activeUid) return;

    apiPost('ih_mark_messages_read', { user_id: C.activeUid }).then(function () {
      document.querySelectorAll('.ihc-row.me[data-id]').forEach(function (row) {
        if (ST.applyRowState) ST.applyRowState(row, 'read');
      });
    }).catch(function () {});

  }



  if (window.ihSocket) {

    try {

      window.ihSocket.addEventListener('message', function (e) {

        var d = JSON.parse(e.data || '{}');

        if (d.type === 'message') {

          window.ihSocketLive = true;

          d.message = msgText(d);

          upsert(d);

          if (!mine(d.from_me)) notify(d);

        } else if (d.type === 'status_update') {

          setPresence(d.is_online, d.last_seen);

        } else if (d.type === 'typing') {

          showTyping(d.is_typing);

        }

      });

    } catch (e) {}

  }



  function setPresence(online, lastSeen) {

    document.querySelectorAll('[data-presence]').forEach(function (el) {

      el.classList.toggle('off', !online);

      var txt = el.querySelector('[data-presence-text]');

      if (txt) txt.textContent = online ? 'Online now' : ('Last seen ' + (lastSeen || 'recently'));

    });

    document.querySelectorAll('.ihc-online').forEach(function (d) { d.classList.toggle('off', !online); });

  }



  function heartbeat() {

    if (!C.activeUid) return;

    api('ih_heartbeat', 'with=' + C.activeUid).then(function (res) {

      if (res && res.success && res.data) setPresence(!!res.data.online, res.data.last_seen);

    }).catch(function () {});

  }



  var typingTO;

  function onType() {

    if (!C.activeThread) return;

    clearTimeout(typingTO);

    api('ih_typing', 'thread_id=' + C.activeThread + '&typing=1').catch(function () {});

    typingTO = setTimeout(function () {

      api('ih_typing', 'thread_id=' + C.activeThread + '&typing=0').catch(function () {});

    }, 4000);

  }



  function showTyping(on) {

    var t = $('#ihcTyping');

    if (t) t.classList.toggle('ihc-hide', !on);

    if (on && msgs && (!newMsgs || newMsgs.shouldAutoScroll())) msgs.scrollTop = msgs.scrollHeight;

  }



  function stage(file, type) {

    if (file.size > 25 * 1024 * 1024) { toast('Too large', file.name + ' exceeds 25MB'); return; }

    var url = URL.createObjectURL(file);

    pending = [{ file: file, url: url, type: type }];

    var wrap = $('#ihcPreviews');

    wrap.innerHTML = '<div class="ihc-prev">'

      + (type === 'image' ? '<img src="' + url + '">' : '<span class="fi">' + icon(type === 'video' ? 'video' : 'file') + '</span>')

      + '<span>' + esc(file.name) + '</span><span class="x" data-clearprev>' + icon('x') + '</span></div>';

  }



  function clearPreviews() { pending = []; var w = $('#ihcPreviews'); if (w) w.innerHTML = ''; }



  function notify(m) {

    toast(m.sender_name || 'New message', msgText(m) || 'Sent an attachment');

    if (document.hidden && window.Notification && Notification.permission === 'granted') {

      new Notification(m.sender_name || 'New message', { body: msgText(m) || 'Attachment' });

    }

  }



  function toast(title, body) {

    var box = $('#ihcToasts');

    if (!box) return;

    var el = document.createElement('div');

    el.className = 'ihc-toast';

    el.innerHTML = '<span class="ihc-ava" style="width:34px;height:34px;font-size:12px">' + esc((title || '?').slice(0, 1)) + '</span><span class="tx"><b>' + esc(title) + '</b><span>' + esc(body) + '</span></span>';

    box.appendChild(el);

    setTimeout(function () { el.remove(); }, 5000);

  }



  function reqAction(id, status, card) {

    apiPost('ih_update_request_status', { request_id: id, id: id, status: status }).then(function () {

      if (card) {

        var rb = card.querySelector('.rb');

        if (rb) rb.outerHTML = '<span class="ihc-statuspill ' + status.toLowerCase() + '">' + status + '</span>';

        card.classList.add('done');

      }

      toast('Request ' + status.toLowerCase(), 'Updated successfully');

      document.querySelectorAll('[data-req="' + id + '"]').forEach(function (c) {

        if (c !== card) {

          var rb2 = c.querySelector('.rb');

          if (rb2) rb2.outerHTML = '<span class="ihc-statuspill ' + status.toLowerCase() + '">' + status + '</span>';

          c.classList.add('done');

        }

      });

    }).catch(function () { toast('Error', 'Could not update request'); });

  }



  function blockUser(uid, block) {

    apiPost('ih_block_user', { user_id: uid, block: block ? 1 : 0 }).then(function (res) {

      if (res && res.success) {

        toast(block ? 'User blocked' : 'User unblocked', 'USR-' + uid);

        var btn = document.querySelector('[data-block="' + uid + '"]');

        if (btn) {

          btn.textContent = block ? 'Unblock user' : 'Block user';

          btn.classList.toggle('danger', block);

          btn.setAttribute('data-blocked', block ? '1' : '0');

        }

      }

    }).catch(function () {});

  }



  var inviteSelected = [];
  var inviteUsers = [];
  var inviteSearchTimer = null;
  var inviteLastFocus = null;

  function shortDisplayName(name) {
    var p = String(name || '').trim().split(/\s+/);
    if (!p[0]) return 'User';
    if (p.length === 1) return p[0];
    return p[0] + ' ' + p[p.length - 1].charAt(0) + '.';
  }

  function openInviteModal() {
    if (!C.activeThread) {
      toast('Invite', 'Select a conversation first');
      return;
    }
    var scrim = $('#ihInviteModalScrim');
    var modal = $('#ihInviteModal');
    inviteLastFocus = document.activeElement;
    inviteSelected = [];
    updateInviteSummary();
    if (scrim) { scrim.hidden = false; scrim.classList.add('open'); scrim.setAttribute('aria-hidden', 'false'); }
    if (modal) { modal.hidden = false; modal.classList.add('open'); }
    document.body.style.overflow = 'hidden';
    var search = $('#ihInviteSearch');
    if (search) search.value = '';
    loadInviteUsers('');
    setTimeout(function () { if (search) search.focus(); }, 50);
  }

  function closeInviteModal() {
    var scrim = $('#ihInviteModalScrim');
    var modal = $('#ihInviteModal');
    if (scrim) { scrim.classList.remove('open'); scrim.hidden = true; scrim.setAttribute('aria-hidden', 'true'); }
    if (modal) { modal.classList.remove('open'); modal.hidden = true; }
    document.body.style.overflow = '';
    inviteSelected = [];
    updateInviteSummary();
    if (inviteLastFocus && inviteLastFocus.focus) inviteLastFocus.focus();
  }

  function trapInviteFocus(e) {
    var modal = $('#ihInviteModal');
    if (!modal || !modal.classList.contains('open') || e.key !== 'Tab') return;
    var nodes = Array.prototype.slice.call(
      modal.querySelectorAll('button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])')
    );
    if (!nodes.length) return;
    var first = nodes[0];
    var last = nodes[nodes.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }

  function loadInviteUsers(q) {
    var loading = $('#ihInviteLoading');
    var empty = $('#ihInviteEmpty');
    if (!C.activeThread) return;
    if (loading) loading.hidden = false;
    if (empty) empty.hidden = true;
    api('ih_admin_invite_users', 'thread_id=' + (C.activeThread || 0) + '&search=' + encodeURIComponent(q || '')).then(function (res) {
      if (loading) loading.hidden = true;
      inviteUsers = (res && res.success && res.data && res.data.users) ? res.data.users : [];
      renderInviteUsers(q || '');
    }).catch(function () {
      if (loading) loading.hidden = true;
      inviteUsers = [];
      renderInviteUsers('');
    });
  }

  function renderInviteUsers(q) {
    var list = $('#ihInviteUserList');
    var empty = $('#ihInviteEmpty');
    if (!list) return;
    q = (q || '').toLowerCase();
    var rows = inviteUsers.filter(function (u) {
      if (!q) return true;
      var ref = (u.usr_id || ('usr-' + u.id)).toLowerCase();
      return (u.name || '').toLowerCase().indexOf(q) !== -1 || ref.indexOf(q) !== -1;
    });
    list.querySelectorAll('.ih-invite-user').forEach(function (n) { n.remove(); });
    if (!rows.length) {
      if (empty) empty.hidden = false;
      return;
    }
    if (empty) empty.hidden = true;
    rows.forEach(function (u) {
      var uid = parseInt(u.id, 10);
      var selected = inviteSelected.indexOf(uid) !== -1;
      var row = document.createElement('button');
      row.type = 'button';
      row.className = 'ih-invite-user' + (selected ? ' is-selected' : '');
      row.setAttribute('role', 'option');
      row.setAttribute('aria-selected', selected ? 'true' : 'false');
      row.setAttribute('data-invite-uid', uid);
      var status = u.online ? 'ONLINE' : 'OFFLINE';
      var ref = u.usr_id || ('USR-' + uid);
      row.innerHTML = '<span class="ih-invite-user__ava" style="background:' + esc(u.avatar_color || '#5347ce') + '">'
        + esc(u.initials || '?')
        + '<span class="ih-invite-user__dot' + (u.online ? '' : ' off') + '"></span></span>'
        + '<span class="ih-invite-user__tx"><span class="ih-invite-user__name">' + esc(u.name) + '</span>'
        + '<span class="ih-invite-user__meta' + (u.online ? ' is-online' : '') + '">' + esc(ref) + ' · ' + status + '</span></span>'
        + '<span class="ih-invite-user__check" aria-hidden="true"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12l5 5L20 7"/></svg></span>';
      list.appendChild(row);
    });
  }

  function updateInviteSummary() {
    var summary = $('#ihInviteSelectionSummary');
    var btn = $('#ihInviteAddBtn');
    var n = inviteSelected.length;
    if (!n) {
      if (summary) summary.textContent = '0 selected';
      if (btn) btn.disabled = true;
      return;
    }
    var names = inviteSelected.map(function (id) {
      var u = inviteUsers.find(function (x) { return parseInt(x.id, 10) === id; });
      return shortDisplayName(u ? u.name : ('User ' + id));
    });
    var label = n + ' selected · ' + names.join(', ');
    if (label.length > 56) label = n + ' selected · ' + names.slice(0, 2).join(', ') + (n > 2 ? '…' : '');
    if (summary) summary.textContent = label;
    if (btn) btn.disabled = false;
  }

  function toggleInviteUser(uid) {
    uid = parseInt(uid, 10);
    var idx = inviteSelected.indexOf(uid);
    if (idx === -1) inviteSelected.push(uid);
    else inviteSelected.splice(idx, 1);
    renderInviteUsers(($('#ihInviteSearch') || {}).value || '');
    updateInviteSummary();
  }

  function submitInvites() {
    if (!inviteSelected.length || !C.activeThread) return;
    var btn = $('#ihInviteAddBtn');
    if (btn) btn.disabled = true;
    var fd = new FormData();
    fd.append('action', 'ih_admin_invite_to_chat');
    fd.append('nonce', C.nonce);
    fd.append('thread_id', C.activeThread);
    inviteSelected.forEach(function (uid) { fd.append('user_ids[]', String(uid)); });
    fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) {
          toast('Error', (res && res.data && res.data.message) || 'Could not invite users');
          if (btn) btn.disabled = false;
          return;
        }
        (res.data.system_messages || []).forEach(function (sm) {
          sm.type = 'system';
          sm.message = sm.message || sm.text;
          upsert(sm);
        });
        closeInviteModal();
        toast('Invited', (res.data.invited || []).map(function (x) { return x.name; }).join(', ') || 'Users added');
        if (btn) btn.disabled = false;
      })
      .catch(function () {
        if (btn) btn.disabled = false;
        toast('Error', 'Could not invite users');
      });
  }

  function filterThreads(q) {

    q = (q || '').toLowerCase();

    document.querySelectorAll('#ihcThreads .ihc-thread').forEach(function (el) {

      var nameMatch = !q || (el.dataset.name || '').indexOf(q) !== -1 || (el.textContent || '').toLowerCase().indexOf(q) !== -1;

      var tabMatch = activeFilter === 'all'

        || (activeFilter === 'unread' && el.dataset.unread === '1')

        || (activeFilter === 'requests' && el.dataset.req === '1')

        || (activeFilter === 'support' && el.dataset.support === '1');

      el.style.display = nameMatch && tabMatch ? '' : 'none';

    });

  }



  document.addEventListener('click', function (e) {

    var t = e.target;

    if (t.closest('#ihcSend')) send();

    else if (t.closest('[data-retry-msg]')) retryRow(t.closest('.ihc-row'));

    else if (t.closest('#ihcAttachBtn')) { $('#ihcAttachMenu').classList.toggle('open'); $('#ihcEmoji').classList.remove('open'); }

    else if (t.closest('[data-pick="image"]')) { $('#ihcFileImage').click(); $('#ihcAttachMenu').classList.remove('open'); }

    else if (t.closest('[data-pick="file"]')) { $('#ihcFileDoc').click(); $('#ihcAttachMenu').classList.remove('open'); }

    else if (t.closest('[data-pick="video"]')) { $('#ihcFileVideo').click(); $('#ihcAttachMenu').classList.remove('open'); }

    else if (t.closest('#ihcEmojiBtn')) { $('#ihcEmoji').classList.toggle('open'); $('#ihcAttachMenu').classList.remove('open'); }

    else if (t.closest('.ihc-emoji span')) { var ta = $('#ihcInput'); ta.value += t.textContent; ta.focus(); }

    else if (t.closest('[data-clearprev]')) clearPreviews();

    else if (t.closest('.ihc-rbtn.ok')) {

      var c = t.closest('.ihc-reqcard') || t.closest('.ihc-pending-card');

      if (c && c.dataset.req) reqAction(c.dataset.req, 'Approved', c);

    } else if (t.closest('.ihc-rbtn.no')) {

      var c2 = t.closest('.ihc-reqcard') || t.closest('.ihc-pending-card');

      if (c2 && c2.dataset.req) reqAction(c2.dataset.req, 'Rejected', c2);

    } else if (t.closest('[data-approve-pending]')) {

      reqAction(t.closest('[data-approve-pending]').getAttribute('data-approve-pending'), 'Approved', document.querySelector('.ihc-pending-card'));

    } else if (t.closest('[data-block]')) {

      var bb = t.closest('[data-block]');

      var uid = bb.getAttribute('data-block');

      if (uid) blockUser(uid, bb.getAttribute('data-blocked') !== '1');

    } else if (t.closest('#ihcInviteBtn') || t.closest('#ihcInviteSidebarBtn')) openInviteModal();

    else if (t.closest('[data-invite-uid]')) toggleInviteUser(t.closest('[data-invite-uid]').getAttribute('data-invite-uid'));

    else if (t.closest('#ihInviteAddBtn')) submitInvites();

    else if (t.closest('#ihInviteModalClose') || t.closest('#ihInviteModalScrim')) closeInviteModal();

    else if (t.closest('#ihcInfoBtn')) { var p3 = $('.ihc-p3'); if (p3) p3.classList.toggle('open'); }

    else if (t.closest('[data-enter-chat]')) { var area = document.querySelector('.ihc-area'); if (area) area.classList.add('in-chat'); }

    else if (t.closest('#ihcBack')) { var area2 = document.querySelector('.ihc-area'); if (area2) area2.classList.remove('in-chat'); }

    else if (t.closest('.ihc-tab')) {

      document.querySelectorAll('.ihc-tab').forEach(function (x) { x.classList.remove('on'); });

      var tb = t.closest('.ihc-tab');

      tb.classList.add('on');

      activeFilter = tb.dataset.filter || 'all';

      filterThreads(($('#ihcThreadSearch') || {}).value || '');

    }

  });



  var searchIn = $('#ihcThreadSearch');

  if (searchIn) searchIn.addEventListener('input', function () { filterThreads(searchIn.value); });



  var inviteSearch = $('#ihInviteSearch');

  if (inviteSearch) {

    inviteSearch.addEventListener('input', function () {

      clearTimeout(inviteSearchTimer);

      var q = inviteSearch.value.trim();

      inviteSearchTimer = setTimeout(function () { loadInviteUsers(q); }, 300);

    });

  }



  document.addEventListener('keydown', function (e) {

    var modal = $('#ihInviteModal');

    if (modal && modal.classList.contains('open')) {

      trapInviteFocus(e);

      if (e.key === 'Escape') {

        e.preventDefault();

        closeInviteModal();

      }

    }

  });



  ['ihcFileImage', 'ihcFileDoc', 'ihcFileVideo'].forEach(function (id, i) {

    var el = document.getElementById(id);

    var types = ['image', 'file', 'video'];

    if (el) el.addEventListener('change', function () { if (el.files[0]) stage(el.files[0], types[i]); el.value = ''; });

  });



  var ta = $('#ihcInput');

  if (ta) {

    ta.addEventListener('input', function () { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 120) + 'px'; onType(); });

    ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } });

  }



  if (window.innerWidth <= 760 && C.activeUid) {

    var area = document.querySelector('.ihc-area');

    if (area) area.classList.add('in-chat');

  }



  if (window.Notification && Notification.permission === 'default') {

    try { Notification.requestPermission(); } catch (e) {}

  }



  if (window.IHChatNewMessages && msgs) {

    newMsgs = window.IHChatNewMessages.create({

      msgsEl: msgs,

      isIncoming: function (m) { return !mine(m.from_me); },

      markRead: markRead,

      unreadCount: C.unreadCount || 0,

      firstUnreadId: C.firstUnreadId || 0

    });

  } else if (msgs) {

    msgs.scrollTop = msgs.scrollHeight;

  }

  if (window.IHMsgReactions) IHMsgReactions.init({ ajax: C.ajax, nonce: C.nonce });

  if (C.activeUid) {

    setInterval(poll, 3000);

    setInterval(heartbeat, 30000);

    heartbeat();

    if (C.activeUid && !( ( C.unreadCount || 0 ) > 0 && ( C.firstUnreadId || 0 ) )) markRead();

  }

})();

