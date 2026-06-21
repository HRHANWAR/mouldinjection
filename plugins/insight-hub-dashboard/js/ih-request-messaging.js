/**
 * ih-request-messaging.js — client for the requester ↔ owner listing messaging.
 *
 * Shared by the standalone inbox (plugin admin pages) and the listing-detail
 * thread panel (theme). Both surfaces embed one or more
 * `[data-rmsg-thread-view]` containers and an optional unread poller.
 *
 * Config is read from window.IH_RMSG:
 *   { ajaxUrl, nonce, meId, isAdmin, pollMs }
 *
 * Security note: message bodies are inserted with textContent only (never
 * innerHTML), so even though the server sanitizes + escapes, the client cannot
 * reintroduce markup injection.
 */
( function () {
	'use strict';

	var CFG = window.IH_RMSG || {};
	if ( ! CFG.ajaxUrl || ! CFG.nonce ) {
		return;
	}
	var POLL_MS = parseInt( CFG.pollMs, 10 ) || 12000;

	function post( action, data ) {
		var body = new URLSearchParams();
		body.append( 'action', action );
		body.append( 'nonce', CFG.nonce );
		Object.keys( data || {} ).forEach( function ( k ) {
			body.append( k, data[ k ] );
		} );
		return fetch( CFG.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} ).then( function ( r ) {
			return r.json().catch( function () {
				return { success: false, data: { message: 'Network error' } };
			} );
		} );
	}

	function get( action, params ) {
		var qs = new URLSearchParams();
		qs.append( 'action', action );
		qs.append( 'nonce', CFG.nonce );
		Object.keys( params || {} ).forEach( function ( k ) {
			qs.append( k, params[ k ] );
		} );
		return fetch( CFG.ajaxUrl + '?' + qs.toString(), {
			method: 'GET',
			credentials: 'same-origin'
		} ).then( function ( r ) {
			return r.json().catch( function () {
				return { success: false, data: { message: 'Network error' } };
			} );
		} );
	}

	function el( tag, cls, text ) {
		var n = document.createElement( tag );
		if ( cls ) {
			n.className = cls;
		}
		if ( text != null ) {
			n.textContent = text;
		}
		return n;
	}

	// Walk up from a node (possibly a text node / child) to the nearest element
	// matching the selector. Avoids relying on Element.closest on the event target.
	function closestSel( node, sel ) {
		var n = node && node.nodeType === 3 ? node.parentNode : node;
		while ( n && n.nodeType === 1 ) {
			if ( n.matches && n.matches( sel ) ) {
				return n;
			}
			n = n.parentNode;
		}
		return null;
	}

	function nowTime() {
		try {
			return new Date().toLocaleTimeString( [], { hour: 'numeric', minute: '2-digit' } );
		} catch ( e ) {
			return '';
		}
	}

	var CLOCK_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';

	// Outbound delivery-state glyph. Mirrors ih_rmsg_tick_html() on the server.
	function buildTick( status ) {
		var t = el( 'span', 'ih-rmsg-tick is-' + status );
		t.setAttribute( 'aria-hidden', 'true' );
		if ( status === 'sending' ) {
			t.innerHTML = CLOCK_SVG; // static markup, no user data
		} else if ( status === 'sent' ) {
			t.textContent = '✓';
		} else if ( status === 'failed' ) {
			t.textContent = '';
		} else {
			t.textContent = '✓✓'; // delivered + read (colour differs via CSS)
		}
		return t;
	}

	function buildRetryFooter() {
		var foot = el( 'div', 'ihmsg-retry' );
		foot.setAttribute( 'data-rmsg-retry', '' );
		foot.setAttribute( 'role', 'button' );
		foot.setAttribute( 'tabindex', '0' );
		foot.appendChild( el( 'span', 'ihmsg-retry__bar' ) );
		foot.appendChild( el( 'span', 'ihmsg-retry__label', 'Not delivered' ) );
		foot.appendChild( el( 'span', 'ihmsg-retry__action', '⟳ Tap to retry' ) );
		return foot;
	}

	// Build an attachment node (image thumb / video / file card). URLs come from
	// the server (wp_get_attachment_url) and are only ever assigned to .src/.href,
	// never injected as HTML.
	function buildAttachment( att ) {
		var url = att && att.url ? String( att.url ) : '';
		if ( ! url ) { return null; }
		var type = att.type || 'file';
		if ( type === 'image' ) {
			var a = el( 'a', 'ih-rmsg-att ih-rmsg-att--img' );
			a.href = url; a.target = '_blank'; a.rel = 'noopener noreferrer';
			var img = document.createElement( 'img' );
			img.src = url; img.loading = 'lazy'; img.alt = att.name || 'Image attachment';
			a.appendChild( img );
			if ( att.name ) { a.appendChild( el( 'span', 'ih-rmsg-att__cap', att.name ) ); }
			return a;
		}
		if ( type === 'video' ) {
			var v = document.createElement( 'video' );
			v.className = 'ih-rmsg-att ih-rmsg-att--video';
			v.src = url; v.controls = true; v.preload = 'metadata';
			return v;
		}
		var fa = el( 'a', 'ih-rmsg-att ih-rmsg-att--file' );
		fa.href = url; fa.target = '_blank'; fa.rel = 'noopener noreferrer'; fa.setAttribute( 'download', '' );
		var ico = el( 'span', 'ih-rmsg-att__ico' );
		ico.setAttribute( 'aria-hidden', 'true' );
		ico.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>';
		var tx = el( 'span', 'ih-rmsg-att__tx' );
		tx.appendChild( el( 'b', null, att.name || 'Attachment' ) );
		var ext = ( att.name || url ).split( '.' ).pop();
		tx.appendChild( el( 'em', null, ( ext ? ext.toUpperCase() : '' ) + ( att.size ? ' · ' + formatSize( att.size ) : '' ) ) );
		fa.appendChild( ico );
		fa.appendChild( tx );
		return fa;
	}

	function formatSize( bytes ) {
		bytes = parseInt( bytes, 10 ) || 0;
		if ( bytes <= 0 ) { return ''; }
		if ( bytes < 1024 ) { return bytes + ' B'; }
		if ( bytes < 1048576 ) { return Math.round( bytes / 1024 ) + ' KB'; }
		return ( Math.round( bytes / 104857.6 ) / 10 ) + ' MB';
	}

	function buildBubble( msg ) {
		// System rows (e.g. "Admin added X to the chat") — centred pill.
		if ( msg.system ) {
			var sys = el( 'div', 'ihmsg-sysline' );
			if ( msg.id ) { sys.setAttribute( 'data-id', msg.id ); }
			sys.appendChild( el( 'span', null, msg.body || '' ) );
			return sys;
		}
		var status = msg.status || ( msg.read ? 'read' : 'sent' );
		var hasAtt = msg.attachment && msg.attachment.url;
		var row = el( 'div', 'ih-rmsg-msg ' + ( msg.mine ? 'is-mine' : 'is-them' ) + ( msg.mine ? ' is-' + status : '' ) + ( hasAtt ? ' has-attachment' : '' ) );
		if ( msg.id ) { row.setAttribute( 'data-id', msg.id ); }
		if ( msg.day_key ) { row.setAttribute( 'data-daykey', msg.day_key ); }
		if ( msg.day_label ) { row.setAttribute( 'data-daylabel', msg.day_label ); }
		// Group sender label sits above the bubble with a per-sender accent colour.
		if ( ! msg.mine && msg.name && msg.is_group ) {
			var label = el( 'div', 'ih-rmsg-name', msg.name );
			var hue = ( ( parseInt( msg.sender_id, 10 ) || 0 ) * 47 + 210 ) % 360;
			label.style.color = 'hsl(' + hue + ' 64% 48%)';
			row.appendChild( label );
		}
		var bubble = el( 'div', 'ih-rmsg-bubble' );
		if ( hasAtt ) {
			var attNode = buildAttachment( msg.attachment );
			if ( attNode ) { bubble.appendChild( attNode ); }
		}
		if ( msg.body ) {
			bubble.appendChild( el( 'div', 'ih-rmsg-text', msg.body ) );
		}
		var meta = el( 'div', 'ih-rmsg-meta', msg.time || '' );
		if ( msg.mine ) {
			meta.appendChild( buildTick( status ) );
		}
		bubble.appendChild( meta );
		row.appendChild( bubble );
		if ( msg.mine && status === 'failed' ) {
			row.appendChild( buildRetryFooter() );
		}
		return row;
	}

	function ThreadView( root ) {
		this.root = root;
		this.requestId = parseInt( root.getAttribute( 'data-request-id' ), 10 ) || 0;
		this.list = root.querySelector( '[data-rmsg-list]' );
		this.form = root.querySelector( '[data-rmsg-form]' );
		this.input = root.querySelector( '[data-rmsg-input]' );
		this.sendBtn = root.querySelector( '[data-rmsg-send]' );
		this.errBox = root.querySelector( '[data-rmsg-error]' );
		this.loadMoreBtn = root.querySelector( '[data-rmsg-loadmore]' );
		this.emptyEl = root.querySelector( '[data-rmsg-empty]' );
		this.scrollBtn = root.querySelector( '[data-rmsg-scrolldown]' );
		this.scrollDot = root.querySelector( '[data-rmsg-scrolldown-dot]' );
		// Composer extras (attachments / emoji). Present only on the full inbox.
		this.chipsBox = root.querySelector( '[data-ihmsg-chips]' );
		this.fileAny = root.querySelector( '[data-ihmsg-file-any]' );
		this.fileImg = root.querySelector( '[data-ihmsg-file-img]' );
		this.attachBtn = root.querySelector( '[data-ihmsg-attach]' );
		this.imageBtn = root.querySelector( '[data-ihmsg-image]' );
		this.attachAllowed = this.form ? this.form.getAttribute( 'data-attach-allowed' ) === '1' : false;
		this.pendingFiles = [];
		this.lastId = 0;
		this.firstId = 0;
		this.sending = false;
		this.pollTimer = null;
		this.dividerEl = null;
		this.newCount = 0;
		this.tmpSeq = 0;
		this.isGroup = false;
		// Divider markers captured server-side before this page load marked the
		// thread read (the thread AJAX would otherwise report zero unread).
		this.seedFirstUnread = parseInt( root.getAttribute( 'data-seed-firstunread' ), 10 ) || 0;
		this.seedUnread = parseInt( root.getAttribute( 'data-seed-unread' ), 10 ) || 0;
		this.seedFromDom();
		this.bind();
		this.loadInitial();
	}

	ThreadView.prototype.seedFromDom = function () {
		// Seed cursors from any server-rendered bubbles.
		var bubbles = this.list ? this.list.querySelectorAll( '.ih-rmsg-msg[data-id]' ) : [];
		var i;
		for ( i = 0; i < bubbles.length; i++ ) {
			var id = parseInt( bubbles[ i ].getAttribute( 'data-id' ), 10 ) || 0;
			if ( id ) {
				if ( ! this.firstId || id < this.firstId ) {
					this.firstId = id;
				}
				if ( id > this.lastId ) {
					this.lastId = id;
				}
			}
		}
	};

	ThreadView.prototype.bind = function () {
		var self = this;
		if ( this.form ) {
			this.form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				self.send();
			} );
		}
		if ( this.input ) {
			this.input.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' && ! e.shiftKey ) {
					e.preventDefault();
					self.send();
				}
			} );
		}
		if ( this.loadMoreBtn ) {
			this.loadMoreBtn.addEventListener( 'click', function () {
				self.loadOlder();
			} );
		}
		if ( this.list ) {
			this.list.addEventListener( 'scroll', function () {
				self.updateScrollBtn();
			} );
			// Retry a failed outbound message (delegated; click + keyboard).
			this.list.addEventListener( 'click', function ( e ) {
				var foot = closestSel( e.target, '[data-rmsg-retry]' );
				if ( foot && self.list.contains( foot ) ) {
					self.retryFrom( foot );
				}
			} );
			this.list.addEventListener( 'keydown', function ( e ) {
				if ( e.key !== 'Enter' && e.key !== ' ' && e.key !== 'Spacebar' ) {
					return;
				}
				var foot = closestSel( e.target, '[data-rmsg-retry]' );
				if ( foot && self.list.contains( foot ) ) {
					e.preventDefault();
					self.retryFrom( foot );
				}
			} );
		}
		if ( this.scrollBtn ) {
			this.scrollBtn.addEventListener( 'click', function () {
				self.scrollToBottom();
				self.newCount = 0;
				if ( self.scrollDot ) { self.scrollDot.hidden = true; }
				self.clearDivider();
				self.scrollBtn.hidden = true;
			} );
		}
		this.bindComposer();
	};

	ThreadView.prototype.bindComposer = function () {
		var self = this;
		if ( this.attachBtn && this.fileAny ) {
			this.attachBtn.addEventListener( 'click', function () {
				if ( ! self.attachAllowed ) { return; }
				self.fileAny.click();
			} );
			this.fileAny.addEventListener( 'change', function () { self.queueFiles( self.fileAny.files ); self.fileAny.value = ''; } );
		}
		if ( this.imageBtn && this.fileImg ) {
			this.imageBtn.addEventListener( 'click', function () {
				if ( ! self.attachAllowed ) { return; }
				self.fileImg.click();
			} );
			this.fileImg.addEventListener( 'change', function () { self.queueFiles( self.fileImg.files ); self.fileImg.value = ''; } );
		}
		if ( this.chipsBox ) {
			this.chipsBox.addEventListener( 'click', function ( e ) {
				var x = closestSel( e.target, '[data-chip-x]' );
				if ( ! x ) { return; }
				var idx = parseInt( x.getAttribute( 'data-chip-x' ), 10 );
				if ( idx >= 0 ) { self.pendingFiles.splice( idx, 1 ); self.renderChips(); }
			} );
		}
		// Emoji picker (lightweight built-in set).
		var emojiWrap = this.root.querySelector( '[data-ihmsg-emoji]' );
		if ( emojiWrap ) {
			var emojiBtn = emojiWrap.querySelector( '[data-ihmsg-emoji-btn]' );
			var pop = emojiWrap.querySelector( '[data-ihmsg-emoji-pop]' );
			var SET = '😀 😁 😂 🤣 😊 😍 😘 😎 🤝 👍 👏 🙏 🔥 ✅ ❌ ⚠️ 💡 📎 📷 📁 🚀 ⏱️ 💬 🎉 ✨ 👀 🙌 💪 🤔 👋'.split( ' ' );
			if ( emojiBtn && pop ) {
				SET.forEach( function ( e ) {
					var b = el( 'button', null, e );
					b.type = 'button';
					b.addEventListener( 'click', function () { self.insertText( e ); pop.hidden = true; emojiBtn.setAttribute( 'aria-expanded', 'false' ); } );
					pop.appendChild( b );
				} );
				emojiBtn.addEventListener( 'click', function ( ev ) {
					ev.stopPropagation();
					pop.hidden = ! pop.hidden;
					emojiBtn.setAttribute( 'aria-expanded', pop.hidden ? 'false' : 'true' );
				} );
				document.addEventListener( 'click', function ( ev ) {
					if ( ! pop.hidden && ! emojiWrap.contains( ev.target ) ) { pop.hidden = true; emojiBtn.setAttribute( 'aria-expanded', 'false' ); }
				} );
			}
		}
	};

	ThreadView.prototype.insertText = function ( text ) {
		if ( ! this.input ) { return; }
		var s = this.input.selectionStart, e = this.input.selectionEnd, v = this.input.value;
		if ( typeof s === 'number' ) {
			this.input.value = v.slice( 0, s ) + text + v.slice( e );
			this.input.selectionStart = this.input.selectionEnd = s + text.length;
		} else {
			this.input.value = v + text;
		}
		this.input.focus();
	};

	ThreadView.prototype.queueFiles = function ( fileList ) {
		if ( ! fileList || ! fileList.length ) { return; }
		var i;
		for ( i = 0; i < fileList.length; i++ ) {
			this.pendingFiles.push( fileList[ i ] );
		}
		this.renderChips();
	};

	ThreadView.prototype.renderChips = function () {
		if ( ! this.chipsBox ) { return; }
		while ( this.chipsBox.firstChild ) { this.chipsBox.removeChild( this.chipsBox.firstChild ); }
		var self = this;
		this.pendingFiles.forEach( function ( f, idx ) {
			var chip = el( 'span', 'ihmsg-chipfile' );
			if ( /^image\//.test( f.type ) ) {
				var img = document.createElement( 'img' );
				img.className = 'ihmsg-chipfile__thumb';
				try { img.src = URL.createObjectURL( f ); } catch ( err ) {}
				chip.appendChild( img );
			}
			chip.appendChild( el( 'span', 'ihmsg-chipfile__name', f.name ) );
			var x = el( 'button', 'ihmsg-chipfile__x', '×' );
			x.type = 'button';
			x.setAttribute( 'data-chip-x', idx );
			x.setAttribute( 'aria-label', 'Remove ' + f.name );
			chip.appendChild( x );
			self.chipsBox.appendChild( chip );
		} );
		this.chipsBox.hidden = this.pendingFiles.length === 0;
	};

	ThreadView.prototype.scrollToBottom = function () {
		if ( this.list ) {
			this.list.scrollTop = this.list.scrollHeight;
		}
	};

	ThreadView.prototype.isAtBottom = function () {
		if ( ! this.list ) {
			return true;
		}
		return ( this.list.scrollHeight - this.list.scrollTop - this.list.clientHeight ) < 56;
	};

	ThreadView.prototype.updateScrollBtn = function () {
		if ( ! this.scrollBtn ) {
			return;
		}
		var atBottom = this.isAtBottom();
		this.scrollBtn.hidden = atBottom;
		if ( atBottom ) {
			this.newCount = 0;
			if ( this.scrollDot ) { this.scrollDot.hidden = true; }
			this.clearDivider();
		}
	};

	ThreadView.prototype.clearDivider = function () {
		if ( this.dividerEl && this.dividerEl.parentNode ) {
			this.dividerEl.parentNode.removeChild( this.dividerEl );
		}
		this.dividerEl = null;
	};

	// Rebuild "Today / Yesterday / date" dividers from the bubbles' day keys.
	ThreadView.prototype.relayoutDateDividers = function () {
		if ( ! this.list ) { return; }
		var old = this.list.querySelectorAll( '.ihmsg-datedivider' );
		var i;
		for ( i = 0; i < old.length; i++ ) { old[ i ].parentNode.removeChild( old[ i ] ); }
		var rows = this.list.querySelectorAll( '.ih-rmsg-msg[data-daykey]' );
		var lastDay = '';
		for ( i = 0; i < rows.length; i++ ) {
			var key = rows[ i ].getAttribute( 'data-daykey' );
			if ( key && key !== lastDay ) {
				var div = el( 'div', 'ihmsg-datedivider' );
				div.appendChild( el( 'span', null, rows[ i ].getAttribute( 'data-daylabel' ) || key ) );
				rows[ i ].parentNode.insertBefore( div, rows[ i ] );
				lastDay = key;
			}
		}
	};

	ThreadView.prototype.insertDivider = function ( beforeId, count ) {
		this.clearDivider();
		if ( ! beforeId || ! count || ! this.list ) {
			return;
		}
		var target = this.list.querySelector( '.ih-rmsg-msg[data-id="' + beforeId + '"]' );
		if ( ! target ) {
			return;
		}
		var div = el( 'div', 'ihmsg-newdivider' );
		div.setAttribute( 'data-rmsg-divider', '' );
		div.appendChild( el( 'span', 'ihmsg-newdivider__pill', '↑ ' + count + ' NEW MESSAGE' + ( count === 1 ? '' : 'S' ) ) );
		this.list.insertBefore( div, target );
		this.dividerEl = div;
	};

	ThreadView.prototype.scrollToDivider = function () {
		if ( this.dividerEl && this.list ) {
			// offsetTop is measured to the shared offset parent, so subtract the
			// list's own offset to get the divider's position within the scroller.
			var top = this.dividerEl.offsetTop - this.list.offsetTop;
			this.list.scrollTop = Math.max( 0, top - 12 );
		} else {
			this.scrollToBottom();
		}
	};

	ThreadView.prototype.showError = function ( msg ) {
		if ( this.errBox ) {
			this.errBox.textContent = msg || '';
			this.errBox.style.display = msg ? '' : 'none';
		}
	};

	ThreadView.prototype.appendMessages = function ( messages, prepend ) {
		if ( ! this.list || ! messages || ! messages.length ) {
			return;
		}
		if ( this.emptyEl ) {
			this.emptyEl.style.display = 'none';
		}
		var frag = document.createDocumentFragment();
		var i;
		for ( i = 0; i < messages.length; i++ ) {
			var m = messages[ i ];
			if ( m.id && this.root.querySelector( '[data-id="' + m.id + '"]' ) ) {
				continue; // de-dupe
			}
			m.is_group = this.isGroup;
			frag.appendChild( buildBubble( m ) );
			if ( typeof m.id === 'number' ) {
				if ( m.id > this.lastId ) {
					this.lastId = m.id;
				}
				if ( ! this.firstId || m.id < this.firstId ) {
					this.firstId = m.id;
				}
			}
		}
		if ( prepend ) {
			var anchor = this.loadMoreBtn && this.loadMoreBtn.parentNode === this.list ? this.loadMoreBtn.nextSibling : this.list.firstChild;
			this.list.insertBefore( frag, anchor );
		} else {
			this.list.appendChild( frag );
		}
	};

	ThreadView.prototype.loadInitial = function () {
		var self = this;
		if ( ! this.requestId ) {
			return;
		}
		get( 'ih_rmsg_thread', { request_id: this.requestId } ).then( function ( res ) {
			if ( ! res || ! res.success ) {
				self.showError( res && res.data ? res.data.message : 'Could not load conversation.' );
				return;
			}
			self.isGroup = !! res.data.is_group;
			// Replace any server-rendered seed bubbles (and dividers) to guarantee
			// canonical order, but keep non-message nodes (inline request cards).
			if ( self.list ) {
				var old = self.list.querySelectorAll( '.ih-rmsg-msg[data-id], .ihmsg-sysline[data-id], [data-rmsg-divider], .ihmsg-datedivider' );
				var k;
				for ( k = 0; k < old.length; k++ ) {
					old[ k ].parentNode.removeChild( old[ k ] );
				}
			}
			self.lastId = 0;
			self.firstId = 0;
			self.dividerEl = null;
			self.appendMessages( res.data.messages, false );
			self.relayoutDateDividers();
			if ( self.loadMoreBtn ) {
				self.loadMoreBtn.hidden = ! res.data.has_more;
			}
			if ( self.emptyEl && ( ! res.data.messages || ! res.data.messages.length ) ) {
				self.emptyEl.style.display = '';
			}
			// "N NEW MESSAGES" divider above the first unread, then land the viewport
			// on it (so the user sees where new starts) rather than the bottom.
			// The server-marked seed values take precedence (the AJAX re-fetch reports
			// zero because this page load already marked the thread read).
			var firstUnread = res.data.first_unread_id || self.seedFirstUnread;
			var unreadCount = res.data.unread_count || self.seedUnread;
			if ( firstUnread && unreadCount > 0 ) {
				self.insertDivider( firstUnread, unreadCount );
				self.scrollToDivider();
			} else {
				self.scrollToBottom();
			}
			self.updateScrollBtn();
			self.startPolling();
		} );
	};

	ThreadView.prototype.loadOlder = function () {
		var self = this;
		if ( ! this.requestId || ! this.firstId ) {
			return;
		}
		get( 'ih_rmsg_thread', { request_id: this.requestId, before_id: this.firstId } ).then( function ( res ) {
			if ( ! res || ! res.success ) {
				return;
			}
			var prevH = self.list ? self.list.scrollHeight : 0;
			self.appendMessages( res.data.messages, true );
			self.relayoutDateDividers();
			if ( self.loadMoreBtn ) {
				self.loadMoreBtn.hidden = ! res.data.has_more;
			}
			if ( self.list ) {
				self.list.scrollTop = self.list.scrollHeight - prevH;
			}
		} );
	};

	ThreadView.prototype.poll = function () {
		var self = this;
		if ( ! this.requestId ) {
			return;
		}
		get( 'ih_rmsg_thread', { request_id: this.requestId, after_id: this.lastId } ).then( function ( res ) {
			if ( ! res || ! res.success || ! res.data.messages || ! res.data.messages.length ) {
				return;
			}
			var wasBottom = self.isAtBottom();
			// Count inbound (not-mine, non-system) arrivals for the unread indicator.
			var inbound = 0, j;
			for ( j = 0; j < res.data.messages.length; j++ ) {
				if ( ! res.data.messages[ j ].mine && ! res.data.messages[ j ].system ) {
					inbound++;
				}
			}
			self.appendMessages( res.data.messages, false );
			self.relayoutDateDividers();
			if ( wasBottom ) {
				self.scrollToBottom();
			} else if ( inbound > 0 ) {
				// User is scrolled up — surface the floating button + unread dot.
				self.newCount += inbound;
				if ( self.scrollBtn ) { self.scrollBtn.hidden = false; }
				if ( self.scrollDot ) { self.scrollDot.hidden = false; }
			}
		} );
	};

	ThreadView.prototype.startPolling = function () {
		var self = this;
		if ( this.pollTimer ) {
			return;
		}
		this.pollTimer = setInterval( function () {
			if ( document.hidden ) {
				return;
			}
			self.poll();
		}, POLL_MS );
	};

	ThreadView.prototype.send = function () {
		if ( ! this.input ) {
			return;
		}
		var body = this.input.value.replace( /\s+$/, '' );
		// Attachments take priority: caption rides on the first file.
		if ( this.pendingFiles && this.pendingFiles.length ) {
			this.uploadPending( body.trim() );
			return;
		}
		if ( ! body.trim() ) {
			return;
		}
		this.showError( '' );
		// Optimistic bubble: render immediately in the "sending" (clock) state,
		// then resolve to "sent" or "failed" around the real ih_rmsg_send AJAX.
		var optimistic = buildBubble( {
			id: 'tmp-' + ( ++this.tmpSeq ),
			mine: true,
			body: body,
			time: nowTime(),
			status: 'sending',
			is_group: this.isGroup
		} );
		optimistic.setAttribute( 'data-mbody', body );
		if ( this.emptyEl ) {
			this.emptyEl.style.display = 'none';
		}
		if ( this.list ) {
			this.list.appendChild( optimistic );
		}
		this.clearDivider();
		this.scrollToBottom();
		this.updateScrollBtn();
		this.input.value = '';
		this.input.style.height = '';
		this.input.focus();
		this.doSend( body, optimistic );
	};

	// Fire the real send and reconcile the optimistic bubble with the result.
	ThreadView.prototype.doSend = function ( body, optimistic ) {
		var self = this;
		post( 'ih_rmsg_send', { request_id: this.requestId, body: body } ).then( function ( res ) {
			if ( ! res || ! res.success ) {
				self.markFailed( optimistic, res && res.data ? res.data.message : '' );
				return;
			}
			var id = parseInt( res.data.id, 10 ) || 0;
			// A concurrent poll may have already appended this persisted message;
			// if so just drop the optimistic placeholder to avoid a duplicate.
			var existing = id ? self.root.querySelector( '[data-id="' + id + '"]' ) : null;
			if ( existing ) {
				if ( optimistic.parentNode ) {
					optimistic.parentNode.removeChild( optimistic );
				}
			} else {
				res.data.is_group = self.isGroup;
				var real = buildBubble( res.data );
				if ( optimistic.parentNode ) {
					optimistic.parentNode.replaceChild( real, optimistic );
				} else if ( self.list ) {
					self.list.appendChild( real );
				}
			}
			if ( id > self.lastId ) { self.lastId = id; }
			if ( ! self.firstId || id < self.firstId ) { self.firstId = id; }
			self.scrollToBottom();
		} );
	};

	// Upload the queued attachments sequentially; the first carries any caption.
	ThreadView.prototype.uploadPending = function ( caption ) {
		var self = this;
		var files = this.pendingFiles.slice();
		this.pendingFiles = [];
		this.renderChips();
		this.input.value = '';
		this.input.style.height = '';
		this.showError( '' );
		var i = 0;
		function next() {
			if ( i >= files.length ) { return; }
			var f = files[ i ];
			var cap = ( i === 0 ) ? ( caption || '' ) : '';
			i++;
			self.uploadOne( f, cap, next );
		}
		next();
	};

	ThreadView.prototype.uploadOne = function ( file, caption, done ) {
		var self = this;
		var fd = new FormData();
		fd.append( 'action', 'ih_rmsg_upload' );
		fd.append( 'nonce', CFG.nonce );
		fd.append( 'request_id', this.requestId );
		fd.append( 'body', caption || '' );
		fd.append( 'attachment', file );
		// Optimistic "uploading" placeholder.
		var ph = buildBubble( { id: 'tmp-' + ( ++this.tmpSeq ), mine: true, body: caption || '', time: nowTime(), status: 'sending', is_group: this.isGroup } );
		var note = el( 'div', 'ih-rmsg-text', '📎 ' + file.name );
		ph.querySelector( '.ih-rmsg-bubble' ).insertBefore( note, ph.querySelector( '.ih-rmsg-text' ) || ph.querySelector( '.ih-rmsg-meta' ) );
		if ( this.emptyEl ) { this.emptyEl.style.display = 'none'; }
		if ( this.list ) { this.list.appendChild( ph ); }
		this.scrollToBottom();
		fetch( CFG.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd } )
			.then( function ( r ) { return r.json().catch( function () { return { success: false, data: { message: 'Upload failed' } }; } ); } )
			.then( function ( res ) {
				if ( ! res || ! res.success ) {
					self.markFailed( ph, res && res.data ? res.data.message : 'Upload failed' );
					if ( done ) { done(); }
					return;
				}
				res.data.is_group = self.isGroup;
				var real = buildBubble( res.data );
				if ( ph.parentNode ) { ph.parentNode.replaceChild( real, ph ); }
				else if ( self.list ) { self.list.appendChild( real ); }
				var id = parseInt( res.data.id, 10 ) || 0;
				if ( id > self.lastId ) { self.lastId = id; }
				if ( ! self.firstId || id < self.firstId ) { self.firstId = id; }
				self.relayoutDateDividers();
				self.scrollToBottom();
				if ( done ) { done(); }
			} );
	};

	// Turn an optimistic bubble into the red "Not delivered · ⟳ Tap to retry" state.
	ThreadView.prototype.markFailed = function ( row, message ) {
		row.className = 'ih-rmsg-msg is-mine is-failed';
		var tick = row.querySelector( '.ih-rmsg-tick' );
		if ( tick ) {
			tick.className = 'ih-rmsg-tick is-failed';
			tick.textContent = '';
		}
		if ( ! row.querySelector( '.ihmsg-retry' ) ) {
			row.appendChild( buildRetryFooter() );
		}
		if ( message ) {
			this.showError( message );
		}
	};

	// Re-send the message stored on a failed bubble.
	ThreadView.prototype.retryFrom = function ( foot ) {
		var row = closestSel( foot, '.ih-rmsg-msg' );
		if ( ! row ) {
			return;
		}
		var body = row.getAttribute( 'data-mbody' ) || '';
		if ( ! body ) {
			return;
		}
		this.showError( '' );
		row.className = 'ih-rmsg-msg is-mine is-sending';
		var existing = row.querySelector( '.ihmsg-retry' );
		if ( existing && existing.parentNode ) {
			existing.parentNode.removeChild( existing );
		}
		var tick = row.querySelector( '.ih-rmsg-tick' );
		if ( tick ) {
			tick.className = 'ih-rmsg-tick is-sending';
			tick.innerHTML = CLOCK_SVG;
		}
		this.doSend( body, row );
	};

	// Unread badge poller (menu + any [data-rmsg-badge]).
	function startUnreadPoller() {
		var badges = document.querySelectorAll( '[data-rmsg-badge]' );
		if ( ! badges.length ) {
			return;
		}
		function apply( data ) {
			var total = ( data && data.total ) || 0;
			var per = ( data && data.per_request ) || {};
			badges.forEach( function ( b ) {
				var rid = parseInt( b.getAttribute( 'data-rmsg-badge' ), 10 ) || 0;
				var n = rid ? ( per[ rid ] || 0 ) : total;
				if ( n > 0 ) {
					b.textContent = n > 99 ? '99+' : String( n );
					b.hidden = false;
					b.style.display = '';
				} else {
					b.hidden = true;
					b.style.display = 'none';
				}
			} );
		}
		function tick() {
			if ( document.hidden ) {
				return;
			}
			get( 'ih_rmsg_unread', {} ).then( function ( res ) {
				if ( res && res.success ) {
					apply( res.data );
				}
			} );
		}
		tick();
		setInterval( tick, POLL_MS );
	}

	/* ─────────────────────────────────────────────────────────────────────
	   Redesigned inbox behaviours — tabs, search, request form, approve/decline,
	   and mobile navigation. All wired to the real backend handlers.
	───────────────────────────────────────────────────────────────────── */
	function qs( sel, root ) { return ( root || document ).querySelector( sel ); }
	function qsa( sel, root ) { return Array.prototype.slice.call( ( root || document ).querySelectorAll( sel ) ); }

	function initTabsAndSearch( container ) {
		var tabs = qsa( '[data-ihmsg-tab]', container );
		var search = qs( '[data-ihmsg-search]', container );
		var threadsPane = qs( '[data-ihmsg-pane="threads"]', container );
		var requestsPane = qs( '[data-ihmsg-pane="requests"]', container );
		var rows = qsa( '[data-ihmsg-thread]', container );
		var labels = qsa( '.ihmsg-grouplabel', container );
		var activeTab = 'all';

		function apply() {
			var term = ( ( search && search.value ) || '' ).trim().toLowerCase();
			if ( requestsPane ) { requestsPane.hidden = ( activeTab !== 'requests' ); }
			if ( threadsPane ) { threadsPane.hidden = ( activeTab === 'requests' ); }
			rows.forEach( function ( row ) {
				var show = true;
				if ( activeTab === 'unread' && row.getAttribute( 'data-unread' ) !== '1' ) { show = false; }
				if ( show && term ) { show = ( row.getAttribute( 'data-name' ) || '' ).indexOf( term ) !== -1; }
				row.style.display = show ? '' : 'none';
			} );
			// Hide group labels that have no visible rows after them.
			labels.forEach( function ( lbl ) {
				var n = lbl.nextElementSibling;
				var any = false;
				while ( n && ! n.classList.contains( 'ihmsg-grouplabel' ) ) {
					if ( ( n.hasAttribute( 'data-ihmsg-thread' ) || n.classList.contains( 'ihmsg-thread' ) ) && n.style.display !== 'none' ) { any = true; break; }
					n = n.nextElementSibling;
				}
				lbl.style.display = ( activeTab === 'unread' && ! any ) ? 'none' : '';
			} );
		}

		tabs.forEach( function ( t ) {
			t.addEventListener( 'click', function () {
				tabs.forEach( function ( x ) { x.classList.remove( 'is-active' ); x.setAttribute( 'aria-selected', 'false' ); } );
				t.classList.add( 'is-active' );
				t.setAttribute( 'aria-selected', 'true' );
				activeTab = t.getAttribute( 'data-ihmsg-tab' ) || 'all';
				apply();
			} );
		} );
		if ( search ) { search.addEventListener( 'input', apply ); }
	}

	function updateActivity( stats ) {
		if ( ! stats ) { return; }
		[ 'sent', 'approved', 'pending' ].forEach( function ( k ) {
			var node = document.querySelector( '[data-ihmsg-stat="' + k + '"]' );
			if ( node && typeof stats[ k ] !== 'undefined' ) { node.textContent = stats[ k ]; }
		} );
	}

	function prependRequestRow( d ) {
		var list = document.querySelector( '[data-ihmsg-reqlist]' );
		if ( ! list ) { return; }
		var empty = list.querySelector( '.ihmsg-empty' );
		if ( empty ) { empty.parentNode.removeChild( empty ); }
		if ( list.querySelector( '[data-req-id="' + d.request_id + '"]' ) ) { return; }
		var row = el( 'div', 'ihmsg-reqrow' );
		row.setAttribute( 'data-req-id', d.request_id );
		var tx = el( 'div', 'ihmsg-reqrow__tx' );
		tx.appendChild( el( 'em', null, d.req_ref || '' ) );
		tx.appendChild( el( 'b', null, d.label || '' ) );
		row.appendChild( tx );
		row.appendChild( el( 'span', 'ihmsg-chip ihmsg-chip--' + String( d.status || 'pending' ).toLowerCase(), d.status || 'Pending' ) );
		list.insertBefore( row, list.firstChild );
	}

	function initRequestForm( container ) {
		var form = qs( '[data-ihmsg-reqform]', container );
		if ( ! form ) { return; }
		var typeOpts = qsa( '[data-ihmsg-type]', form );
		var typeVal = qs( '[data-ihmsg-type-value]', form );
		var refInput = qs( '[data-ihmsg-ref]', form );
		var msg = qs( '[data-ihmsg-reqmsg]', form );
		var submitBtn = qs( '[data-ihmsg-reqsubmit]', form );

		function showMsg( text, ok ) {
			if ( ! msg ) { return; }
			msg.textContent = text;
			msg.className = 'ihmsg-reqform__msg ' + ( ok ? 'is-ok' : 'is-no' );
			msg.hidden = false;
		}

		typeOpts.forEach( function ( opt ) {
			opt.addEventListener( 'click', function () {
				typeOpts.forEach( function ( x ) { x.classList.remove( 'is-active' ); x.setAttribute( 'aria-checked', 'false' ); } );
				opt.classList.add( 'is-active' );
				opt.setAttribute( 'aria-checked', 'true' );
				if ( typeVal ) { typeVal.value = opt.getAttribute( 'data-ihmsg-type' ) || 'tool'; }
				if ( refInput ) { refInput.focus(); }
			} );
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var ref = ( ( refInput && refInput.value ) || '' ).trim();
			if ( ! ref ) { showMsg( 'Enter a Tool / Machine ID, e.g. TL-00231.', false ); return; }
			var type = ( typeVal && typeVal.value ) || 'tool';
			if ( submitBtn ) { submitBtn.disabled = true; }
			post( 'ih_rmsg_request_contact', { ref: ref, listing_type: type } ).then( function ( res ) {
				if ( submitBtn ) { submitBtn.disabled = false; }
				if ( ! res || ! res.success ) {
					showMsg( res && res.data ? res.data.message : 'Could not send your request.', false );
					return;
				}
				var d = res.data;
				showMsg(
					d.existing
						? ( 'You already have a pending request for ' + d.listing_ref + '.' )
						: ( 'Request sent for ' + d.listing_ref + ' — an admin will review it shortly.' ),
					true
				);
				if ( refInput ) { refInput.value = ''; }
				updateActivity( d.stats );
				if ( ! d.existing ) { prependRequestRow( d ); }
			} );
		} );
	}

	function initRequestActions( container ) {
		if ( ! CFG.isAdmin ) { return; }
		container.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '[data-ihmsg-approve],[data-ihmsg-decline]' ) : null;
			if ( ! btn || ! container.contains( btn ) ) { return; }
			var rid = parseInt( btn.getAttribute( 'data-request-id' ), 10 ) || 0;
			if ( ! rid ) { return; }
			var approve = btn.hasAttribute( 'data-ihmsg-approve' );
			var card = btn.closest( '[data-ihmsg-reqcard]' );
			var actions = card ? card.querySelector( '[data-ihmsg-reqactions]' ) : null;
			var result = card ? card.querySelector( '[data-ihmsg-reqresult]' ) : null;
			if ( actions ) { qsa( 'button', actions ).forEach( function ( b ) { b.disabled = true; } ); }
			post( 'ih_update_request_status', { id: rid, status: approve ? 'approved' : 'rejected' } ).then( function ( res ) {
				if ( ! res || ! res.success ) {
					if ( actions ) { qsa( 'button', actions ).forEach( function ( b ) { b.disabled = false; } ); }
					if ( result ) {
						result.hidden = false;
						result.className = 'ihmsg-reqcard__result is-no';
						result.textContent = res && res.data ? res.data.message : 'Action failed.';
					}
					return;
				}
				if ( actions ) { actions.style.display = 'none'; }
				if ( result ) {
					result.hidden = false;
					result.className = 'ihmsg-reqcard__result ' + ( approve ? 'is-ok' : 'is-no' );
					result.textContent = approve ? '✓ Approved — the conversation is now unlocked.' : 'Request declined.';
				}
			} );
		} );
	}

	function initMobileNav( container ) {
		var back = qs( '[data-ihmsg-back]', container );
		if ( back ) {
			back.addEventListener( 'click', function () {
				try {
					var url = new URL( window.location.href );
					url.searchParams.delete( 'request' );
					window.location.href = url.toString();
				} catch ( err ) {
					window.history.back();
				}
			} );
		}
		var railToggle = qs( '[data-ihmsg-rail-toggle]', container );
		if ( railToggle ) {
			railToggle.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				container.classList.toggle( 'rail-open' );
			} );
		}
		container.addEventListener( 'click', function ( e ) {
			if ( ! container.classList.contains( 'rail-open' ) ) { return; }
			var rail = qs( '[data-ihmsg-rail-col]', container );
			if ( rail && ! rail.contains( e.target ) && ( ! railToggle || ! railToggle.contains( e.target ) ) ) {
				container.classList.remove( 'rail-open' );
			}
		} );
	}

	var THREAD_VIEWS = [];

	function findThreadView( requestId ) {
		var i;
		for ( i = 0; i < THREAD_VIEWS.length; i++ ) {
			if ( THREAD_VIEWS[ i ].requestId === requestId ) { return THREAD_VIEWS[ i ]; }
		}
		return null;
	}

	// Admin access-control switches (Profile / Listing / Attachments).
	function initAccessToggles( container ) {
		if ( ! CFG.isAdmin ) { return; }
		var wrap = qs( '[data-ihmsg-toggles]', container );
		if ( ! wrap ) { return; }
		var rid = parseInt( wrap.getAttribute( 'data-request-id' ), 10 ) || 0;
		qsa( '[data-ihmsg-toggle]', wrap ).forEach( function ( sw ) {
			sw.addEventListener( 'click', function () {
				if ( sw.getAttribute( 'aria-disabled' ) === 'true' ) { return; }
				var key = sw.getAttribute( 'data-ihmsg-toggle' );
				var next = sw.getAttribute( 'aria-checked' ) !== 'true';
				sw.setAttribute( 'aria-checked', next ? 'true' : 'false' );
				post( 'ih_rmsg_set_access', { request_id: rid, key: key, value: next ? 1 : 0 } ).then( function ( res ) {
					if ( ! res || ! res.success ) {
						sw.setAttribute( 'aria-checked', next ? 'false' : 'true' ); // revert
						return;
					}
					// Reflect the attachment toggle on the composer live.
					if ( key === 'allow_attachments' ) {
						var allow = !! ( res.data.settings && res.data.settings.allow_attachments );
						var view = findThreadView( rid );
						if ( view ) {
							view.attachAllowed = allow;
							[ view.attachBtn, view.imageBtn ].forEach( function ( b ) { if ( b ) { b.disabled = ! allow; } } );
							if ( view.form ) { view.form.setAttribute( 'data-attach-allowed', allow ? '1' : '0' ); }
						}
					}
				} );
			} );
		} );
	}

	function initOverflow( container ) {
		var wrap = qs( '[data-ihmsg-overflow]', container );
		if ( ! wrap ) { return; }
		var btn = qs( '[data-ihmsg-overflow-btn]', wrap );
		var menu = qs( '[data-ihmsg-overflow-menu]', wrap );
		if ( ! btn || ! menu ) { return; }
		btn.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			menu.hidden = ! menu.hidden;
			btn.setAttribute( 'aria-expanded', menu.hidden ? 'false' : 'true' );
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( ! menu.hidden && ! wrap.contains( e.target ) ) { menu.hidden = true; btn.setAttribute( 'aria-expanded', 'false' ); }
		} );
	}

	function initInviteModal( container ) {
		if ( ! CFG.isAdmin ) { return; }
		var modal = qs( '[data-ihmsg-invite-modal]', container );
		var openers = qsa( '[data-ihmsg-invite-open]', container );
		if ( ! modal || ! openers.length ) { return; }
		var search = qs( '[data-ihmsg-invite-search]', modal );
		var results = qs( '[data-ihmsg-invite-results]', modal );
		var msg = qs( '[data-ihmsg-invite-msg]', modal );
		var dialog = qs( '.ihmsg-modal__dialog', modal );
		var rid = 0, debounce = null, lastFocus = null;

		function showMsg( text, ok ) {
			if ( ! msg ) { return; }
			msg.textContent = text || '';
			msg.className = 'ihmsg-invite-msg ' + ( ok ? 'is-ok' : 'is-no' );
			msg.hidden = ! text;
		}
		function open( e ) {
			rid = parseInt( e.currentTarget.getAttribute( 'data-request-id' ), 10 ) || 0;
			lastFocus = e.currentTarget;
			modal.hidden = false;
			showMsg( '', true );
			if ( results ) { results.innerHTML = ''; }
			if ( search ) { search.value = ''; setTimeout( function () { search.focus(); }, 30 ); }
			doSearch( '' );
		}
		function close() {
			modal.hidden = true;
			if ( lastFocus && lastFocus.focus ) { lastFocus.focus(); }
		}
		function render( users ) {
			if ( ! results ) { return; }
			results.innerHTML = '';
			if ( ! users || ! users.length ) {
				results.appendChild( el( 'div', 'ihmsg-invite-empty', 'No matching users found.' ) );
				return;
			}
			users.forEach( function ( u ) {
				var row = el( 'div', 'ihmsg-invite-row' );
				var ini = el( 'span', 'ihmsg-ava ihmsg-ava--sm', u.initials || '' );
				ini.setAttribute( 'aria-hidden', 'true' );
				var tx = el( 'div', 'ihmsg-invite-row__tx' );
				tx.appendChild( el( 'b', null, u.name || '' ) );
				tx.appendChild( el( 'span', null, u.ref || '' ) );
				var add = el( 'button', 'ihmsg-btn ihmsg-btn--primary', 'Add' );
				add.type = 'button';
				add.addEventListener( 'click', function () {
					add.disabled = true;
					post( 'ih_rmsg_invite', { request_id: rid, user_id: u.id } ).then( function ( res ) {
						if ( ! res || ! res.success ) {
							add.disabled = false;
							showMsg( res && res.data ? res.data.message : 'Could not add user.', false );
							return;
						}
						row.parentNode && row.parentNode.removeChild( row );
						showMsg( ( res.data.invited ? res.data.invited.name : 'User' ) + ' added to the chat.', true );
						var view = findThreadView( rid );
						if ( view && res.data.system ) {
							res.data.system.system = true;
							view.appendMessages( [ res.data.system ], false );
							view.scrollToBottom();
						}
					} );
				} );
				row.appendChild( ini );
				row.appendChild( tx );
				row.appendChild( add );
				results.appendChild( row );
			} );
		}
		function doSearch( term ) {
			get( 'ih_rmsg_invite_search', { request_id: rid, search: term } ).then( function ( res ) {
				if ( res && res.success ) { render( res.data.users ); }
			} );
		}
		openers.forEach( function ( o ) { o.addEventListener( 'click', open ); } );
		qsa( '[data-ihmsg-invite-close]', modal ).forEach( function ( c ) { c.addEventListener( 'click', close ); } );
		if ( search ) {
			search.addEventListener( 'input', function () {
				clearTimeout( debounce );
				var term = search.value.trim();
				debounce = setTimeout( function () { doSearch( term ); }, 220 );
			} );
		}
		document.addEventListener( 'keydown', function ( e ) {
			if ( modal.hidden ) { return; }
			if ( e.key === 'Escape' ) { close(); }
			if ( e.key === 'Tab' && dialog ) {
				var f = dialog.querySelectorAll( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
				if ( ! f.length ) { return; }
				var first = f[ 0 ], last = f[ f.length - 1 ];
				if ( e.shiftKey && document.activeElement === first ) { e.preventDefault(); last.focus(); }
				else if ( ! e.shiftKey && document.activeElement === last ) { e.preventDefault(); first.focus(); }
			}
		} );
	}

	function initInbox() {
		var container = document.querySelector( '[data-ihmsg]' );
		if ( ! container ) { return; }
		initTabsAndSearch( container );
		initRequestForm( container );
		initRequestActions( container );
		initMobileNav( container );
		initAccessToggles( container );
		initOverflow( container );
		initInviteModal( container );
	}

	function init() {
		var views = document.querySelectorAll( '[data-rmsg-thread-view]' );
		var i;
		for ( i = 0; i < views.length; i++ ) {
			THREAD_VIEWS.push( new ThreadView( views[ i ] ) );
		}
		startUnreadPoller();
		initInbox();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
