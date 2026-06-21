/**
 * Message reactions — floating picker + summary chips (Figma Msg-Reactions).
 */
(function () {
  'use strict';

  var QUICK = ['👍', '❤️', '😂', '😮', '🙏', '✅'];
  var picker = null;
  var hideTimer = null;
  var activeRow = null;
  var longPressTimer = null;
  var cfg = {};

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  function isSystemRow(row) {
    if (!row) return true;
    if (row.getAttribute('data-msg-type') === 'system') return true;
    if (!row.getAttribute('data-id')) return true;
    if (row.classList.contains('ihc-hide')) return true;
    if (row.querySelector('.ihc-reqcard, .ihc-typing, .ihc-div')) return true;
    return false;
  }

  function chipsHTML(reactions) {
    if (!reactions || !reactions.length) return '';
    return reactions.map(function (r) {
      var mine = r.mine ? ' is-mine' : '';
      return '<button type="button" class="ih-msg-reaction-chip' + mine + '" data-emoji="' + esc(r.emoji) + '">'
        + '<span class="e">' + esc(r.emoji) + '</span>'
        + '<span class="n">' + esc(String(r.count || 0)) + '</span></button>';
    }).join('');
  }

  function ensureWrap(row) {
    var wrap = row.querySelector('.ihc-msg-wrap');
    if (wrap) return wrap;
    var bubble = row.querySelector('.ihc-bubble');
    if (!bubble) return null;
    wrap = document.createElement('div');
    wrap.className = 'ihc-msg-wrap';
    bubble.parentNode.insertBefore(wrap, bubble);
    wrap.appendChild(bubble);
    var chips = document.createElement('div');
    chips.className = 'ihc-reactions';
    wrap.appendChild(chips);
    return wrap;
  }

  function setChips(row, reactions) {
    var wrap = ensureWrap(row);
    if (!wrap) return;
    var box = wrap.querySelector('.ihc-reactions');
    if (!box) {
      box = document.createElement('div');
      box.className = 'ihc-reactions';
      wrap.appendChild(box);
    }
    box.innerHTML = chipsHTML(reactions);
    box.style.display = reactions && reactions.length ? '' : 'none';
  }

  function pickerHTML() {
    var emojis = QUICK.map(function (e) {
      return '<button type="button" class="ih-msg-reaction-picker__emo" data-emoji="' + esc(e) + '" aria-label="' + esc(e) + '">' + e + '</button>';
    }).join('');
    return '<div class="ih-msg-reaction-picker" role="toolbar" aria-label="React to message">'
      + emojis
      + '<button type="button" class="ih-msg-reaction-picker__more" data-more-emoji aria-label="More emojis">+</button>'
      + '</div>';
  }

  function ensurePicker() {
    if (picker) return picker;
    var root = document.querySelector('.ihc-msgs') || document.body;
    root.insertAdjacentHTML('beforeend', pickerHTML());
    picker = root.querySelector('.ih-msg-reaction-picker');
    return picker;
  }

  function positionPicker(row) {
    if (!picker || !row) return;
    var bubble = row.querySelector('.ihc-bubble');
    if (!bubble) return;
    var msgs = row.closest('.ihc-msgs');
    var br = bubble.getBoundingClientRect();
    var mr = msgs ? msgs.getBoundingClientRect() : { top: 0, left: 0 };
    picker.style.left = Math.max(8, br.left - mr.left + msgs.scrollLeft) + 'px';
    picker.style.top = Math.max(8, br.top - mr.top + msgs.scrollTop - picker.offsetHeight - 8) + 'px';
  }

  function showPicker(row) {
    if (isSystemRow(row)) return;
    clearTimeout(hideTimer);
    activeRow = row;
    ensurePicker();
    positionPicker(row);
    picker.classList.add('is-visible');
  }

  function hidePicker(delay) {
    clearTimeout(hideTimer);
    hideTimer = setTimeout(function () {
      if (picker) picker.classList.remove('is-visible');
      activeRow = null;
    }, delay == null ? 180 : delay);
  }

  function toggleReaction(messageId, emoji, row) {
    if (!messageId || !emoji) return;
    var fd = new FormData();
    fd.append('action', cfg.toggleAction || 'ih_toggle_message_reaction');
    fd.append('nonce', cfg.nonce);
    fd.append('message_id', String(messageId));
    fd.append('emoji', emoji);

    var prev = [];
    try {
      var chips = row && row.querySelectorAll('.ih-msg-reaction-chip');
      if (chips) {
        chips.forEach(function (c) {
          prev.push({
            emoji: c.getAttribute('data-emoji'),
            count: parseInt(c.querySelector('.n').textContent, 10) || 0,
            mine: c.classList.contains('is-mine')
          });
        });
      }
    } catch (e) {}

    var optimistic = prev.slice();
    var found = false;
    optimistic.forEach(function (r) {
      if (r.emoji === emoji) {
        found = true;
        if (r.mine) {
          r.count = Math.max(0, r.count - 1);
        } else {
          r.count += 1;
          r.mine = true;
        }
      }
    });
    if (!found) optimistic.push({ emoji: emoji, count: 1, mine: true });
    optimistic = optimistic.filter(function (r) { return r.count > 0; });
    if (row) setChips(row, optimistic);

    fetch(cfg.ajax, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.success && res.data && row) {
          setChips(row, res.data.reactions || []);
        }
      })
      .catch(function () {
        if (row) setChips(row, prev);
      });
  }

  function bindRow(row) {
    if (isSystemRow(row) || row._ihReactBound) return;
    row._ihReactBound = true;
    row.addEventListener('mouseenter', function () { showPicker(row); });
    row.addEventListener('mouseleave', function () { hidePicker(); });
    row.addEventListener('touchstart', function (e) {
      clearTimeout(longPressTimer);
      longPressTimer = setTimeout(function () {
        showPicker(row);
        e.preventDefault();
      }, 500);
    }, { passive: false });
    row.addEventListener('touchend', function () { clearTimeout(longPressTimer); });
    row.addEventListener('touchmove', function () { clearTimeout(longPressTimer); });
  }

  function scan(root) {
    (root || document).querySelectorAll('.ihc-row[data-id]').forEach(bindRow);
  }

  function init(config) {
    if (init._done) {
      cfg = Object.assign(cfg, config || {});
      scan(document);
      return;
    }
    init._done = true;
    cfg = config || {};
    ensurePicker();
    scan(document);

    document.addEventListener('click', function (e) {
      var emoBtn = e.target.closest('.ih-msg-reaction-picker__emo, .ih-msg-reaction-picker__more');
      if (emoBtn && picker && picker.classList.contains('is-visible') && activeRow) {
        e.preventDefault();
        e.stopPropagation();
        var emoji = emoBtn.getAttribute('data-emoji');
        if (emoBtn.hasAttribute('data-more-emoji')) {
          emoji = window.prompt('Pick an emoji', '✨');
          if (!emoji) return;
        }
        var mid = parseInt(activeRow.getAttribute('data-id'), 10);
        toggleReaction(mid, emoji.trim(), activeRow);
        hidePicker(0);
        return;
      }

      var chip = e.target.closest('.ih-msg-reaction-chip');
      if (chip) {
        var row = chip.closest('.ihc-row');
        var mid2 = row ? parseInt(row.getAttribute('data-id'), 10) : 0;
        toggleReaction(mid2, chip.getAttribute('data-emoji'), row);
        return;
      }

      if (picker && !e.target.closest('.ih-msg-reaction-picker') && !e.target.closest('.ihc-row')) {
        hidePicker(0);
      }
    });

    if (picker) {
      picker.addEventListener('mouseenter', function () { clearTimeout(hideTimer); });
      picker.addEventListener('mouseleave', function () { hidePicker(); });
    }
  }

  window.IHMsgReactions = {
    init: init,
    scan: scan,
    bindRow: bindRow,
    setChips: setChips,
    chipsHTML: chipsHTML,
    isSystemRow: isSystemRow,
    msgType: function (m) {
      if (m.type === 'system') return 'system';
      var text = String(m.message || m.text || '');
      if (/^(?:➕\s*)?Admin added .+ to the chat\.?$/i.test(text.trim())) return 'system';
      return 'chat';
    },
    QUICK: QUICK
  };
})();
