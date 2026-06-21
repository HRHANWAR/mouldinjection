(function () {
  'use strict';

  function qs(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }

  var MOBILE_MQ = window.matchMedia('(max-width: 768px)');

  function setMenuOpen(btn, menu, open) {
    if (!btn || !menu) return;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    menu.classList.toggle('open', open);
  }

  function setSidebarToggleState(open) {
    var toggles = document.querySelectorAll('.ih-hamburger[aria-controls="ihSidebar"]');
    toggles.forEach(function (btn) {
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  function initHeaderMenus() {
    var notifBtn = qs('#ihNotifBtn');
    var notifMenu = qs('#ihNotifBox');
    var accountBtn = qs('#ihAccountBtn');
    var accountMenu = qs('#ihAccountBox');

    if (notifBtn && notifMenu) {
      notifBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        var open = notifBtn.getAttribute('aria-expanded') !== 'true';
        setMenuOpen(accountBtn, accountMenu, false);
        setMenuOpen(notifBtn, notifMenu, open);
      });
    }

    if (accountBtn && accountMenu) {
      function focusFirstAccountLink() {
        var first = accountMenu.querySelector('.ih-account-link');
        if (first) first.focus();
      }

      accountBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        var open = accountBtn.getAttribute('aria-expanded') !== 'true';
        setMenuOpen(notifBtn, notifMenu, false);
        setMenuOpen(accountBtn, accountMenu, open);
      });

      accountBtn.addEventListener('keydown', function (event) {
        if (event.key !== 'ArrowDown' && event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        setMenuOpen(notifBtn, notifMenu, false);
        setMenuOpen(accountBtn, accountMenu, true);
        focusFirstAccountLink();
      });

      accountMenu.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        setMenuOpen(accountBtn, accountMenu, false);
        accountBtn.focus();
      });
    }

    document.addEventListener('click', function (event) {
      if (notifMenu && notifBtn && !notifMenu.contains(event.target) && !notifBtn.contains(event.target)) {
        setMenuOpen(notifBtn, notifMenu, false);
      }
      if (accountMenu && accountBtn && !accountMenu.contains(event.target) && !accountBtn.contains(event.target)) {
        setMenuOpen(accountBtn, accountMenu, false);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      setMenuOpen(notifBtn, notifMenu, false);
      setMenuOpen(accountBtn, accountMenu, false);
    });
  }

  window.ihOpenSidebar = function () {
    var s = qs('#ihSidebar');
    var o = qs('#ihOverlay');
    if (!s) return;
    s.classList.add('mobile-open', 'expanded');
    setSidebarToggleState(true);
    document.body.classList.add('ih-user-menu-open');
    if (MOBILE_MQ.matches) {
      document.body.style.overflow = 'hidden';
    }
    if (o) {
      o.style.display = 'block';
      requestAnimationFrame(function () { o.classList.add('show'); });
    }
  };

  window.ihCloseSidebar = function () {
    var s = qs('#ihSidebar');
    var o = qs('#ihOverlay');
    if (s) s.classList.remove('mobile-open', 'expanded');
    setSidebarToggleState(false);
    document.body.classList.remove('ih-user-menu-open');
    document.body.style.overflow = '';
    if (o) {
      o.classList.remove('show');
      setTimeout(function () { o.style.display = 'none'; }, 250);
    }
  };

  window.ihToggleSidebar = function () {
    var shell = qs('.ih-shell');
    if (shell && window.matchMedia('(min-width: 769px)').matches) {
      shell.classList.toggle('sb-collapsed');
      try {
        localStorage.setItem('ih_user_sb', shell.classList.contains('sb-collapsed') ? '1' : '0');
      } catch (e) { /* ignore */ }
      return;
    }
    var s = qs('#ihSidebar');
    if (s && s.classList.contains('mobile-open')) {
      ihCloseSidebar();
    } else {
      ihOpenSidebar();
    }
  };

  document.addEventListener('DOMContentLoaded', function () {
    var shell = qs('.ih-shell');
    if (!shell) return;
    initHeaderMenus();
    try {
      if (localStorage.getItem('ih_user_sb') === '1' && window.matchMedia('(min-width: 769px)').matches) {
        shell.classList.add('sb-collapsed');
      }
    } catch (e) { /* ignore */ }

    function onBreakpointChange(e) {
      if (!e.matches && typeof window.ihResetMobileShell === 'function') {
        window.ihResetMobileShell();
      }
    }

    if (MOBILE_MQ.addEventListener) {
      MOBILE_MQ.addEventListener('change', onBreakpointChange);
    } else if (MOBILE_MQ.addListener) {
      MOBILE_MQ.addListener(onBreakpointChange);
    }
  });
})();
