/* ih-requests-menu.js — collapse secondary request actions into a ⋯ row menu.
   Keeps Approve / Reject inline; moves Profile/Message/Listing + contact + Delete
   into a popover. Original onclick/href handlers preserved (nodes are moved). */
(function () {
  'use strict';
  function ready(fn) { document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn); }

  var ICON = {
    'is-profile': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8fb3a3" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'is-msg': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8fb3a3" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'is-listing': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8fb3a3" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    'is-email': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>',
    'is-call': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3 5.18 2 2 0 0 1 5 3h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.1 9.9a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
    'is-sms': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'is-wa': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"><path d="M21 11.5a8.5 8.5 0 0 1-12.5 7.5L3 21l1.9-5.7A8.5 8.5 0 1 1 21 11.5z"/></svg>',
    'is-del': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>'
  };
  function iconFor(el) { for (var k in ICON) { if (el.classList.contains(k)) return ICON[k]; } return ''; }

  var openMenu = null;
  var openTrigger = null;

  function closeOpen() {
    if (openMenu) {
      openMenu.classList.remove('open');
      openMenu.style.display = '';
      openMenu.style.visibility = '';
      openMenu.style.top = '';
      openMenu.style.left = '';
    }
    if (openTrigger) {
      openTrigger.classList.remove('is-active');
      openTrigger.setAttribute('aria-expanded', 'false');
    }
    openMenu = null;
    openTrigger = null;
  }

  function positionMenu(menu, trigger) {
    menu.style.visibility = 'hidden';
    menu.style.display = 'block';
    menu.classList.add('open');
    void menu.offsetWidth;
    var r = trigger.getBoundingClientRect();
    var left = Math.max(8, r.right - menu.offsetWidth);
    var top = r.bottom + 6;
    menu.style.top = top + 'px';
    menu.style.left = left + 'px';
    menu.style.visibility = '';
    var mr = menu.getBoundingClientRect();
    if (mr.bottom > window.innerHeight) {
      menu.style.top = Math.max(8, r.top - menu.offsetHeight - 6) + 'px';
    }
    if (mr.right > window.innerWidth) {
      menu.style.left = Math.max(8, window.innerWidth - menu.offsetWidth - 8) + 'px';
    }
  }

  ready(function () {
    var table = document.getElementById('ihReqTable');
    var mobileRoot = document.getElementById('ihReqMobileCards');
    if (!table && !mobileRoot) return;

    var menuId = 0;
    var actionRoots = [];
    if (table) {
      table.querySelectorAll('.ih-req-actions').forEach(function (el) { actionRoots.push(el); });
    }
    if (mobileRoot) {
      mobileRoot.querySelectorAll('.ih-req-actions').forEach(function (el) { actionRoots.push(el); });
    }

    actionRoots.forEach(function (actions) {
      if (actions.dataset.ihMenuReady === '1') return;

      var openGroup = actions.querySelector('.ih-req-action-group.is-open');
      var contactGroup = actions.querySelector('.ih-req-action-group.is-contact');
      var openBtns = openGroup ? [].slice.call(openGroup.querySelectorAll('.ih-req-btn')) : [];
      var contactBtns = contactGroup ? [].slice.call(contactGroup.querySelectorAll('.ih-req-btn')) : [];
      var secondary = openBtns.concat(contactBtns);

      var menu = document.createElement('div');
      menu.className = 'ih-req-menu';
      menu.setAttribute('role', 'menu');
      var id = 'ih-req-menu-' + (++menuId);
      menu.id = id;

      openBtns.forEach(function (btn) {
        btn.classList.remove('ih-req-btn');
        btn.setAttribute('role', 'menuitem');
        btn.insertAdjacentHTML('afterbegin', iconFor(btn));
        menu.appendChild(btn);
      });
      if (openBtns.length && contactBtns.length) {
        menu.appendChild(document.createElement('hr'));
      }
      contactBtns.forEach(function (btn) {
        if (btn.classList.contains('is-del')) {
          menu.appendChild(document.createElement('hr'));
        }
        btn.classList.remove('ih-req-btn');
        btn.setAttribute('role', 'menuitem');
        btn.insertAdjacentHTML('afterbegin', iconFor(btn));
        menu.appendChild(btn);
      });

      var trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'ih-req-menu-btn';
      trigger.setAttribute('aria-label', 'More actions');
      trigger.setAttribute('aria-haspopup', 'menu');
      trigger.setAttribute('aria-expanded', 'false');
      trigger.setAttribute('aria-controls', id);
      trigger.setAttribute('data-row-menu', '1');
      trigger.innerHTML = '&#8943;';
      actions.appendChild(trigger);
      document.body.appendChild(menu);

      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        var wasOpen = openMenu === menu;
        closeOpen();
        if (wasOpen) return;
        positionMenu(menu, trigger);
        openMenu = menu;
        openTrigger = trigger;
        trigger.classList.add('is-active');
        trigger.setAttribute('aria-expanded', 'true');
      });

      menu.addEventListener('click', function (e) {
        e.stopPropagation();
        if (!e.target.closest('.is-del')) closeOpen();
      });

      actions.classList.add('is-menu-ready');
      actions.dataset.ihMenuReady = '1';
    });

    document.addEventListener('click', function (e) {
      if (openMenu && !openMenu.contains(e.target) && (!openTrigger || !openTrigger.contains(e.target))) {
        closeOpen();
      }
    }, true);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeOpen();
    });

    window.addEventListener('scroll', closeOpen, true);

    window.addEventListener('resize', closeOpen);
  });
})();
