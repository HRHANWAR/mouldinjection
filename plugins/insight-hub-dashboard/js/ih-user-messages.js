/* User messaging engine — see MESSAGING-DEV-SPEC.md */

(function () {

  'use strict';

  var C = window.IHCHAT; if (!C) return;

  if (C.role === 'admin') return;

  var ST = window.IHChatStates || {};

  var lastId = C.lastId || 0;

  var msgs = document.getElementById('ihcMsgs');

  var pending = [];

  var tempSeq = 0;

  var newMsgs = null;

  function repairLayout() {
    var ihc = document.querySelector('.ih-user-messages-page .ihc') || document.querySelector('.ihc');
    var area = document.getElementById('ihcArea') || document.querySelector('.ihc-area');
    var p3 = document.querySelector('.ihc-p3');
    if (ihc && area && p3 && p3.parentElement === ihc) {
      area.appendChild(p3);
    }
  }

  function initReactionsHint() {
    var hint = document.getElementById('ihMsgReactionsHint');
    var btn = document.getElementById('ihMsgReactionsHintDismiss');
    if (!hint) return;
    try {
      if (!localStorage.getItem('ih_um_reactions_hint_dismissed')) {
        hint.classList.remove('ihc-hide');
      }
    } catch (e) {
      hint.classList.remove('ihc-hide');
    }
    if (btn) {
      btn.addEventListener('click', function () {
        hint.classList.add('ihc-hide');
        try { localStorage.setItem('ih_um_reactions_hint_dismissed', '1'); } catch (e2) {}
      });
    }
  }

  function closeMenus(except) {
    var att = $('#ihcAttachMenu');
    var emo = $('#ihcEmoji');
    if (att && att !== except) {
      att.classList.remove('open');
      att.setAttribute('aria-hidden', 'true');
      var ab = $('#ihcAttachBtn');
      if (ab) ab.setAttribute('aria-expanded', 'false');
    }
    if (emo && emo !== except) {
      emo.classList.remove('open');
      var eb = $('#ihcEmojiBtn');
      if (eb) eb.setAttribute('aria-expanded', 'false');
    }
  }



  function $(s, r) { return (r || document).querySelector(s); }

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

  function isSystemMessage(text) { return /^(?:\u2795\s*)?Admin added .+ to the chat\.?$/i.test(String(text || '').trim()); }

  function isAdminNotice(text) { text = String(text || ''); return /submitted for approval/i.test(text) || /Reject from the listing/i.test(text); }

  function isRequestMarker(text) { return String(text || '').indexOf('IH_REQUEST_DATA') !== -1; }

  function isSystemLike(text) { return !!text && (isSystemMessage(text) || isAdminNotice(text) || isRequestMarker(text)); }

  function parseRequestMarker(text) {
    text = String(text || '');
    if (text.indexOf('IH_REQUEST_DATA') === -1) return null;
    var data = {};
    var m = text.match(/IH_REQUEST_DATA[:\-]?\s*(\{[\s\S]*\})/);
    if (m) { try { data = JSON.parse(m[1]) || {}; } catch (e) { data = {}; } }
    var typeRaw = String(data.type || '');
    var ltype = /tool/i.test(typeRaw) ? 'tool' : 'machine';
    /* Owner identity fields in the payload are intentionally never read. */
    return {
      id: parseInt(data.id, 10) || 0,
      listing_id: parseInt(data.listing_id, 10) || 0,
      listing_type: ltype
    };
  }

  function listingRef(type, id) {
    id = parseInt(id, 10) || 0;
    if (id <= 0) return '';
    var pad = ('00000' + id).slice(-5);
    return (type === 'tool' ? 'TL-' : 'MCH-') + pad;
  }

  function requestMsgHTML(m) {
    var rm = parseRequestMarker(m.message);
    if (!rm) return '';
    var ref = listingRef(rm.listing_type, rm.listing_id);
    var year = new Date().getFullYear();
    var reqRef = 'REQ-' + year + '-' + ('0000' + rm.id).slice(-4);
    var action = ref
      ? ('View access to ' + (rm.listing_type === 'tool' ? 'tool ' : 'machine ') + ref)
      : 'Contact access request';
    /* New/live request markers are always Pending until an admin acts. */
    var status = m.request_status ? String(m.request_status) : 'Pending';
    var statusKey = status.toLowerCase();
    return '<div class="ih-msg-system-row" data-id="' + (m.id || '') + '" data-msg-type="request">'
      + '<div class="ihc-reqmsg" role="note" aria-label="Access request">'
      + '<span class="ihc-reqmsg-ico" aria-hidden="true">'
      + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>'
      + '</span><div class="tx"><b>' + esc('Your request · ' + reqRef) + '</b><span>' + esc(action) + '</span></div>'
      + '<span class="ihc-statuspill ' + esc(statusKey) + '">' + esc(status) + '</span></div></div>';
  }

  function systemLabel(text) {
    text = String(text || '').trim();
    var m = text.match(/^(?:\u2795\s*)?Admin added (.+?) to the chat\.?$/i);
    if (m) return 'Admin added ' + m[1].trim() + ' to the chat';
    return text.replace(/^\u2795\s*/, '');
  }

  function cleanNotice(text) {
    var out = [];
    String(text || '').trim().split(/\r\n|\r|\n/).forEach(function (ln) {
      ln = ln.trim();
      if (!ln || /Approve or|Reject from the listing/i.test(ln)) return;
      out.push(ln);
    });
    return out;
  }

  function systemRowHTML(m) {
    return '<div class="ih-msg-system-row" data-id="' + (m.id || '') + '" data-msg-type="system">'
      + '<div class="ih-msg-system-pill"><span class="ih-msg-system-icon" aria-hidden="true">'
      + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>'
      + '</span><span>' + esc(systemLabel(m.message)) + '</span></div></div>';
  }

  function noticeRowHTML(m) {
    var body = cleanNotice(m.message).map(function (ln, i) {
      return i === 0 ? '<b>' + esc(ln) + '</b>' : '<span>' + esc(ln) + '</span>';
    }).join('');
    return '<div class="ih-msg-system-row" data-id="' + (m.id || '') + '" data-msg-type="notice">'
      + '<div class="ih-msg-notice"><span class="ih-msg-notice-ico" aria-hidden="true">'
      + '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>'
      + '</span><div class="tx">' + body + '</div></div></div>';
  }

  function api(action, params, method) {

    var url = C.ajax + '?action=' + action;

    var opt = { method: method || 'GET' };

    if (method === 'POST') { opt.body = params; } else { url += '&nonce=' + C.nonce + (params ? '&' + params : ''); }

    return fetch(url, opt).then(function (r) { return r.json(); });

  }

  function mine(from_me) { return from_me == 0; }



  function outboundStatus(m, me) {

    if (!me || !ST.resolveStatus) return null;

    return ST.resolveStatus(m);

  }



  function bubbleHTML(m) {

    if (isSystemMessage(m.message)) return systemRowHTML(m);

    if (isRequestMarker(m.message)) return requestMsgHTML(m);

    if (isAdminNotice(m.message)) return noticeRowHTML(m);

    var me = mine(m.from_me);

    var status = outboundStatus(m, me);

    var inner = '';

    if (m.attachment_url) {

      var t = m.attachment_type || 'file';

      if (t === 'image') inner += '<a class="ihc-att-img" href="' + esc(m.attachment_url) + '" target="_blank"><img src="' + esc(m.attachment_url) + '" alt=""></a>';

      else inner += '<a class="ihc-att-file" href="' + esc(m.attachment_url) + '" target="_blank" download>'

        + '<span class="fi">' + (t === 'video' ? icon('video') : icon('file')) + '</span>'

        + '<span><b class="fn">' + esc(m.attachment_name || 'Attachment') + '</b><span class="fs">' + (t === 'video' ? 'Video' : 'File') + (m.attachment_size ? ' · ' + fmtSize(m.attachment_size) : '') + '</span></span></a>';

    }

    if (m.message) inner += '<div class="bt">' + esc(m.message) + '</div>';

    inner += '<div class="ihc-meta">' + esc(m.time || '') + (status && ST.metaStatusHTML ? ST.metaStatusHTML(status) : '') + '</div>';

    var rowCls = 'ihc-row ' + (me ? 'me' : 'them') + (status && ST.rowClass ? ST.rowClass(status) : '');

    var attrs = ' data-id="' + (m.id || '') + '"';

    if (m._tempId) attrs += ' data-temp-id="' + esc(m._tempId) + '"';

    if (status === 'failed' && m.message) attrs += ' data-retry-text="' + esc(m.message) + '"';

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

          var icon = meta.querySelector('.ihc-status-icon');

          meta.textContent = m.time;

          if (icon) meta.appendChild(icon);

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

    if (!isSystemLike(m.message)) bindReactions(m);

    if (m.id && m.id > lastId) lastId = m.id;

    if (newMsgs) newMsgs.onMessageAdded(m, scroll !== false && newMsgs.shouldAutoScroll());

    else if (scroll !== false) msgs.scrollTop = msgs.scrollHeight;

  }



  function fmtSize(b) { b = +b; return b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }



  function buildSendForm(text) {

    var fd = new FormData();

    fd.append('action', C.sendAction); fd.append('nonce', C.nonce); fd.append('message', text);

    fd.append('thread_id', C.activeThread || 0);

    if (pending[0]) { fd.append('attachment', pending[0].file); fd.append('attachment_type', pending[0].type); }

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

    return fetch(C.ajax, { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (res) {

      if (res && res.success && res.data) {

        if (res.data.thread_id) C.activeThread = res.data.thread_id;

        upsert({

          id: res.data.id,

          _tempId: tempId,

          from_me: 0,

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

        from_me: 0,

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

    var ta = $('#ihcInput'); var text = (ta.value || '').trim();

    if (!text && !pending.length) return;

    var tempId = 't' + (++tempSeq);

    var temp = {

      _tempId: tempId,

      _sending: true,

      from_me: 0,

      message: text,

      time: 'now',

      delivery_status: 'sending',

      attachment_url: pending[0] ? pending[0].url : '',

      attachment_type: pending[0] ? pending[0].type : '',

      attachment_name: pending[0] ? pending[0].file.name : ''

    };

    upsert(temp);

    ta.value = ''; ta.style.height = ''; clearPreviews();

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

  function getParams() { return 'thread_id=' + (C.activeThread || 0) + '&after_id=' + lastId; }

  function poll() {

    if (window.ihSocketLive) return;

    api(C.getAction, getParams()).then(function (res) {

      if (!res || !res.success) return;

      var arr = (res.data && res.data.messages) || [];

      var typing = res.data && res.data.typing;

      if (typeof typing !== 'undefined') showTyping(!!typing);

      arr.forEach(function (m) {

        upsert(m);

        if (!mine(m.from_me)) notify(m);

      });

      syncOutboundStatuses(res.data && res.data.outbound_statuses);

      if (arr.some(function (m) { return !mine(m.from_me); })) markRead();

    }).catch(function () {});

  }

  function markRead() {}



  if (window.ihSocket) {

    try {

      window.ihSocket.addEventListener('message', function (e) {

        var d = JSON.parse(e.data || '{}');

        if (d.type === 'message') { window.ihSocketLive = true; upsert({ id: d.id, from_me: d.from_me, message: d.message, time: d.time, delivery_status: d.delivery_status, attachment_url: d.attachment_url, attachment_type: d.attachment_type, attachment_name: d.attachment_name }); if (!mine(d.from_me)) notify(d); }

        else if (d.type === 'status_update') setPresence(d.is_online, d.last_seen);

        else if (d.type === 'typing') showTyping(d.is_typing);

      });

    } catch (e) {}

  }



  function setPresence(online, lastSeen) {

    document.querySelectorAll('[data-presence]').forEach(function (el) {

      el.classList.toggle('off', !online);

      var txt = el.querySelector('[data-presence-text]');

      if (txt) txt.textContent = online ? 'Admin online · replies in ~5 min' : ('Last seen ' + (lastSeen || 'recently'));

    });

    document.querySelectorAll('.ihc-online').forEach(function (d) { d.classList.toggle('off', !online); });

  }

  function heartbeat() {

    api('ih_heartbeat', '').then(function (res) {

      if (res && res.success && res.data) setPresence(!!res.data.online, res.data.last_seen);

    }).catch(function () {});

  }



  var typingTO;

  function onType() {

    clearTimeout(typingTO);

    api('ih_typing', 'thread_id=' + (C.activeThread || 0) + '&typing=1').catch(function () {});

    typingTO = setTimeout(function () { api('ih_typing', 'thread_id=' + (C.activeThread || 0) + '&typing=0').catch(function () {}); }, 4000);

  }

  function showTyping(on) { var t = $('#ihcTyping'); if (t) t.classList.toggle('ihc-hide', !on); if (on && msgs && (!newMsgs || newMsgs.shouldAutoScroll())) msgs.scrollTop = msgs.scrollHeight; }



  function stage(file, type) {

    if (file.size > 25 * 1024 * 1024) { toast('Too large', file.name + ' exceeds 25MB'); return; }

    var url = URL.createObjectURL(file); pending = [{ file: file, url: url, type: type }];

    var wrap = $('#ihcPreviews'); wrap.innerHTML = '<div class="ihc-prev">' +

      (type === 'image' ? '<img src="' + url + '">' : '<span class="fi">' + icon(type === 'video' ? 'video' : 'file') + '</span>') +

      '<span>' + esc(file.name) + '</span><span class="x" data-clearprev>' + icon('x') + '</span></div>';

  }

  function clearPreviews() { pending = []; var w = $('#ihcPreviews'); if (w) w.innerHTML = ''; }



  function notify(m) {

    toast(m.sender_name || 'MouldHub Support', m.message || 'Sent an attachment');

    if (document.hidden && window.Notification && Notification.permission === 'granted') {

      new Notification(m.sender_name || 'New message', { body: (m.message || 'Attachment'), icon: C.icon || '' });

    }

  }

  function toast(title, body) {

    var box = $('#ihcToasts'); if (!box) return;

    var el = document.createElement('div'); el.className = 'ihc-toast';

    el.innerHTML = '<span class="ihc-ava" style="width:34px;height:34px;font-size:12px">' + esc((title || '?').slice(0, 1)) + '</span><span class="tx"><b>' + esc(title) + '</b><span>' + esc(body) + '</span></span>';

    box.appendChild(el); setTimeout(function () { el.remove(); }, 5000);

  }



  function filterThreads(q) {

    q = (q || '').toLowerCase();

    document.querySelectorAll('.ihc-thread').forEach(function (el) {

      var txt = (el.textContent || '').toLowerCase();

      el.style.display = !q || txt.indexOf(q) !== -1 ? '' : 'none';

    });

  }



  function icon(n) {

    var m = {

      file: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>',

      video: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M10 9l5 3-5 3z" fill="currentColor"/></svg>',

      x: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>'

    }; return m[n] || '';

  }



  document.addEventListener('click', function (e) {

    var t = e.target;

    if (t.closest('#ihcSend')) { send(); }

    else if (t.closest('[data-retry-msg]')) { retryRow(t.closest('.ihc-row')); }

    else if (t.closest('#ihcAttachBtn')) {
      var menu = $('#ihcAttachMenu'), aBtn = $('#ihcAttachBtn');
      if (menu) {
        var openAtt = !menu.classList.contains('open');
        if (openAtt) closeMenus(menu);
        menu.classList.toggle('open', openAtt);
        menu.setAttribute('aria-hidden', openAtt ? 'false' : 'true');
        if (aBtn) aBtn.setAttribute('aria-expanded', openAtt ? 'true' : 'false');
        if (openAtt) { var firstItem = menu.querySelector('button'); if (firstItem) firstItem.focus(); }
      }
    }

    else if (t.closest('#ihcImageBtn')) { var fimg = $('#ihcFileImage'); if (fimg) fimg.click(); closeMenus(); }

    else if (t.closest('[data-pick="image"]')) { $('#ihcFileImage').click(); closeMenus(); }

    else if (t.closest('[data-pick="file"]')) { $('#ihcFileDoc').click(); closeMenus(); }

    else if (t.closest('[data-pick="video"]')) { $('#ihcFileVideo').click(); closeMenus(); }

    else if (t.closest('#ihcEmojiBtn')) {
      var em = $('#ihcEmoji'), eBtn = $('#ihcEmojiBtn');
      if (em) {
        var openEmo = !em.classList.contains('open');
        if (openEmo) closeMenus(em);
        em.classList.toggle('open', openEmo);
        if (eBtn) eBtn.setAttribute('aria-expanded', openEmo ? 'true' : 'false');
      }
    }

    else if (t.closest('.ihc-emoji span')) { var ta = $('#ihcInput'); ta.value += t.textContent; ta.focus(); }

    else if (t.closest('[data-clearprev]')) { clearPreviews(); }

    else if (t.closest('#ihcInfoBtn')) { $('.ihc-p3').classList.toggle('open'); }

    else if (t.closest('[data-enter-chat]')) { document.querySelector('.ihc-area').classList.add('in-chat'); }

    else if (t.closest('#ihcBack')) { document.querySelector('.ihc-area').classList.remove('in-chat'); }

    else if (t.closest('[data-quickmsg]')) {

      var btn = t.closest('[data-quickmsg]');

      var ta2 = $('#ihcInput');

      if (ta2) { ta2.value = btn.getAttribute('data-quickmsg') || ''; send(); }

    }

    else if (!t.closest('#ihcAttachBtn') && !t.closest('#ihcImageBtn') && !t.closest('#ihcEmojiBtn') && !t.closest('#ihcAttachMenu') && !t.closest('#ihcEmoji')) { closeMenus(); }

  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' && e.key !== 'Esc') return;
    var att = $('#ihcAttachMenu'), emo = $('#ihcEmoji');
    var attOpen = att && att.classList.contains('open');
    var emoOpen = emo && emo.classList.contains('open');
    if (!attOpen && !emoOpen) return;
    closeMenus();
    var focusBtn = attOpen ? $('#ihcAttachBtn') : $('#ihcEmojiBtn');
    if (focusBtn) focusBtn.focus();
  });



  var searchIn = $('#ihcThreadSearch');

  if (searchIn) searchIn.addEventListener('input', function () { filterThreads(searchIn.value); });



  var fi = $('#ihcFileImage'); if (fi) fi.addEventListener('change', function () { if (fi.files[0]) stage(fi.files[0], 'image'); fi.value = ''; });

  var fd2 = $('#ihcFileDoc'); if (fd2) fd2.addEventListener('change', function () { if (fd2.files[0]) stage(fd2.files[0], 'file'); fd2.value = ''; });

  var fv = $('#ihcFileVideo'); if (fv) fv.addEventListener('change', function () { if (fv.files[0]) stage(fv.files[0], 'video'); fv.value = ''; });

  var ta = $('#ihcInput');

  if (ta) {

    ta.addEventListener('input', function () { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 120) + 'px'; onType(); });

    ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } });

  }



  function setReqType(type) {
    var hidden = $('#ihcReqType');
    if (hidden) hidden.value = type;
    document.querySelectorAll('.ihc-seg-btn').forEach(function (b) {
      var on = b.getAttribute('data-type') === type;
      b.classList.toggle('on', on);
      b.setAttribute('aria-selected', on ? 'true' : 'false');
    });
  }

  function reqType() { var h = $('#ihcReqType'); return h && h.value === 'machine' ? 'machine' : 'tool'; }

  function parseListing(raw) {
    raw = (raw || '').trim().toUpperCase();
    if (!raw) return null;
    var type = reqType();
    if (/^TL[-\s]?/.test(raw) || /^TOOL[-\s]?/.test(raw)) type = 'tool';
    else if (/^(MCH|MC|MACHINE)[-\s]?/.test(raw)) type = 'machine';
    var digits = raw.replace(/\D/g, '');
    var id = parseInt(digits, 10);
    if (!id) return null;
    return { id: id, type: type };
  }

  function submitContactRequest() {
    var input = $('#ihcReqId');
    var btn = $('#ihcReqSubmit');
    if (!input) return;
    var parsed = parseListing(input.value);
    if (!parsed) {
      toast('Invalid ID', 'Enter a listing ID such as TL-00231 or MCH-00114.');
      input.focus();
      return;
    }
    if (btn) btn.disabled = true;
    setReqType(parsed.type);
    var fd = new FormData();
    fd.append('action', C.reqAction || 'ih_submit_request');
    fd.append('nonce', C.reqNonce || '');
    fd.append('listing_id', parsed.id);
    fd.append('listing_type', parsed.type);
    fd.append('email', C.meEmail || '');
    fd.append('name', C.meName || '');
    fetch(C.ajax, { method: 'POST', body: fd }).then(function (r) { return r.json(); }).then(function (res) {
      if (btn) btn.disabled = false;
      if (res && res.success) {
        input.value = '';
        if (res.data && res.data.existing) {
          toast('Already requested', 'You already have a pending request for this listing.');
        } else {
          toast('Request sent', 'The admin will review your contact request shortly.');
        }
        setTimeout(function () { window.location.reload(); }, 1100);
      } else {
        toast('Could not send', (res && res.data && res.data.message) || 'Please check the ID and try again.');
      }
    }).catch(function () {
      if (btn) btn.disabled = false;
      toast('Could not send', 'Network error — please try again.');
    });
  }

  var reqForm = $('#ihcReqForm');
  if (reqForm) {
    reqForm.addEventListener('submit', function (e) { e.preventDefault(); submitContactRequest(); });
    reqForm.addEventListener('click', function (e) {
      var seg = e.target.closest('.ihc-seg-btn');
      if (seg) { e.preventDefault(); setReqType(seg.getAttribute('data-type')); }
    });
  }

  repairLayout();
  initReactionsHint();

  if (window.innerWidth <= 760 && C.activeThread) {

    var area = document.querySelector('.ihc-area');

    if (area) area.classList.add('in-chat');

  }



  if (window.Notification && Notification.permission === 'default') { try { Notification.requestPermission(); } catch (e) {} }

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

  setInterval(poll, 3000);

  setInterval(heartbeat, 30000); heartbeat();

})();

