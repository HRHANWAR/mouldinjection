/* New Messages divider + scroll-to-bottom FAB — Figma Msg-NewMessages 230:2257 */
(function () {
  'use strict';

  var SCROLL_THRESHOLD = 48;
  var ARROW_UP = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>';
  var ARROW_DOWN = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 5v14M5 13l7 7 7-7"/></svg>';

  function messageRows(msgs) {
    return Array.prototype.slice.call(
      msgs.querySelectorAll('.ihc-row[data-id]:not([data-id=""]), .ih-msg-system-row[data-id]:not([data-id=""])')
    );
  }

  function maxMessageId(msgs) {
    var max = 0;
    messageRows(msgs).forEach(function (row) {
      var id = parseInt(row.getAttribute('data-id'), 10);
      if (id > max) max = id;
    });
    return max;
  }

  function prevMessageId(msgs, anchorId) {
    var prev = 0;
    messageRows(msgs).forEach(function (row) {
      var id = parseInt(row.getAttribute('data-id'), 10);
      if (id < anchorId && id > prev) prev = id;
    });
    return prev;
  }

  function NewMessagesUI(config) {
    this.msgs = config.msgsEl;
    this.isIncoming = config.isIncoming || function () { return false; };
    this.markRead = config.markRead || function () {};
    this.lastSeenId = config.lastSeenId || 0;
    this.unreadAnchorId = config.firstUnreadId || 0;
    this.openWithUnread = (config.unreadCount || 0) > 0 && this.unreadAnchorId > 0;
    this.divider = null;
    this.fab = null;
    this._scrollRaf = 0;
    if (!this.msgs) return;
    this._buildChrome();
    this._bindEvents();
    this._initScroll();
  }

  NewMessagesUI.prototype.isAtBottom = function () {
    if (!this.msgs) return true;
    return this.msgs.scrollHeight - this.msgs.scrollTop - this.msgs.clientHeight <= SCROLL_THRESHOLD;
  };

  NewMessagesUI.prototype.shouldAutoScroll = function () {
    return this.isAtBottom();
  };

  NewMessagesUI.prototype._syncLastSeen = function () {
    if (!this.msgs) return;
    this.lastSeenId = maxMessageId(this.msgs);
  };

  NewMessagesUI.prototype._newMessageCount = function () {
    var self = this;
    var count = 0;
    messageRows(this.msgs).forEach(function (row) {
      var id = parseInt(row.getAttribute('data-id'), 10);
      if (id > self.lastSeenId) count++;
    });
    return count;
  };

  NewMessagesUI.prototype._belowViewportCount = function () {
    if (!this.msgs) return 0;
    var edge = this.msgs.scrollTop + this.msgs.clientHeight - 8;
    var count = 0;
    messageRows(this.msgs).forEach(function (row) {
      if (row.offsetTop > edge) count++;
    });
    return count;
  };

  NewMessagesUI.prototype._firstNewRow = function () {
    var self = this;
    var first = null;
    messageRows(this.msgs).forEach(function (row) {
      var id = parseInt(row.getAttribute('data-id'), 10);
      if (id > self.lastSeenId && !first) first = row;
    });
    return first;
  };

  NewMessagesUI.prototype._placeDivider = function () {
    if (!this.divider) return;
    var first = this._firstNewRow();
    if (!first) {
      this.divider.classList.add('ihc-hide');
      if (this.divider.parentNode) this.divider.parentNode.removeChild(this.divider);
      return;
    }
    this.divider.classList.remove('ihc-hide');
    if (first.parentNode) first.parentNode.insertBefore(this.divider, first);
  };

  NewMessagesUI.prototype.update = function () {
    if (!this.msgs || !this.fab) return;
    var atBottom = this.isAtBottom();
    var newCount = this._newMessageCount();
    var belowCount = this._belowViewportCount();

    if (atBottom || newCount <= 0) {
      if (this.divider) {
        this.divider.classList.add('ihc-hide');
        if (this.divider.parentNode) this.divider.parentNode.removeChild(this.divider);
      }
      this.fab.classList.add('ihc-hide');
      if (atBottom && newCount === 0) this._syncLastSeen();
      return;
    }

    this._placeDivider();
    var text = this.divider && this.divider.querySelector('.ih-msg-new-divider__text');
    if (text) {
      text.textContent = newCount === 1 ? '1 NEW MESSAGE' : (newCount + ' NEW MESSAGES');
    }

    if (belowCount > 0) {
      this.fab.classList.remove('ihc-hide');
      var badge = this.fab.querySelector('.ih-msg-scroll-bottom__badge');
      if (badge) badge.textContent = String(belowCount);
    } else {
      this.fab.classList.add('ihc-hide');
    }
  };

  NewMessagesUI.prototype.scrollToBottom = function () {
    if (!this.msgs) return;
    this.msgs.scrollTop = this.msgs.scrollHeight;
    this._syncLastSeen();
    this.markRead();
    this.update();
  };

  NewMessagesUI.prototype.onMessageAdded = function (m, autoScroll) {
    if (!this.msgs) return;
    if (autoScroll !== false && this.isAtBottom()) {
      this.msgs.scrollTop = this.msgs.scrollHeight;
      this._syncLastSeen();
      this.markRead();
    } else if (m && m.id && this.isIncoming(m)) {
      /* keep lastSeenId — user is scrolled up */
    }
    this.update();
  };

  NewMessagesUI.prototype._buildChrome = function () {
    var pane = this.msgs.closest('.ihc-p2');
    if (!pane) return;

    this.divider = document.createElement('div');
    this.divider.className = 'ih-msg-new-divider ihc-hide';
    this.divider.id = 'ihMsgNewDivider';
    this.divider.setAttribute('role', 'separator');
    this.divider.innerHTML = ''
      + '<span class="ih-msg-new-divider__line" aria-hidden="true"></span>'
      + '<button type="button" class="ih-msg-new-divider__pill" data-ih-scroll-bottom>'
      + ARROW_UP
      + '<span class="ih-msg-new-divider__text"></span>'
      + '</button>'
      + '<span class="ih-msg-new-divider__line" aria-hidden="true"></span>';

    this.fab = document.createElement('div');
    this.fab.className = 'ih-msg-scroll-bottom ihc-hide';
    this.fab.id = 'ihMsgScrollBottom';
    this.fab.innerHTML = ''
      + '<span class="ih-msg-scroll-bottom__badge" aria-hidden="true"></span>'
      + '<button type="button" class="ih-msg-scroll-bottom__btn" data-ih-scroll-bottom aria-label="Scroll to latest messages">'
      + ARROW_DOWN
      + '</button>';

    pane.appendChild(this.fab);
  };

  NewMessagesUI.prototype._bindEvents = function () {
    var self = this;
    if (!this.msgs) return;

    this.msgs.addEventListener('scroll', function () {
      if (self._scrollRaf) return;
      self._scrollRaf = window.requestAnimationFrame(function () {
        self._scrollRaf = 0;
        if (self.isAtBottom()) {
          self._syncLastSeen();
          self.markRead();
        }
        self.update();
      });
    });

    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-ih-scroll-bottom]')) {
        e.preventDefault();
        self.scrollToBottom();
      }
    });
  };

  NewMessagesUI.prototype._initScroll = function () {
    if (!this.msgs) return;
    if (this.openWithUnread) {
      var row = this.msgs.querySelector('[data-id="' + this.unreadAnchorId + '"]');
      if (row) {
        this.lastSeenId = prevMessageId(this.msgs, this.unreadAnchorId);
        row.scrollIntoView({ block: 'start' });
      } else {
        this.msgs.scrollTop = this.msgs.scrollHeight;
        this._syncLastSeen();
      }
    } else {
      this.msgs.scrollTop = this.msgs.scrollHeight;
      this._syncLastSeen();
    }
    this.update();
  };

  window.IHChatNewMessages = {
    create: function (config) {
      return new NewMessagesUI(config || {});
    }
  };
})();
