(function () {

  'use strict';



  function qs(sel, ctx) {

    return (ctx || document).querySelector(sel);

  }



  function qsa(sel, ctx) {

    return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));

  }



  function ensureSiteNavBodyClass() {

    if (qs('.ih-site-bottom-nav')) {

      document.body.classList.add('ih-has-site-nav');

    }

  }

  ensureSiteNavBodyClass();



  if (qs('.ih-shell')) {

    document.body.classList.add('ih-user-portal');

  }



  function markCurrentSidebar() {

    var page = new URLSearchParams(window.location.search).get('page') || '';

    var hash = window.location.hash || '';

    qsa('.ih-sidebar .ih-nav-item[href]').forEach(function (link) {

      try {

        var url = new URL(link.href, window.location.origin);

        var hrefPage = url.searchParams.get('page') || '';

        var hrefHash = url.hash || '';

        var pageMatch = hrefPage && hrefPage === page;

        var hashMatch = hrefHash && hash && hrefHash === hash;

        if (pageMatch && (!hrefHash || hashMatch)) {

          qsa('.ih-sidebar .ih-nav-item.active').forEach(function (el) {

            el.classList.remove('active');

          });

          link.classList.add('active');

        }

      } catch (e) { /* ignore */ }

    });

    qsa('.ih-site-nav-item[data-nav-key]').forEach(function (link) {

      link.classList.remove('is-active');
      link.removeAttribute('aria-current');

    });

    qsa('.ih-site-nav-item[href]').forEach(function (link) {

      try {

        var url = new URL(link.href, window.location.origin);

        var hrefPage = url.searchParams.get('page') || '';

        var hrefHash = url.hash || '';

        if (hrefPage === page && (!hrefHash || hrefHash === hash)) {

          link.classList.add('is-active');
          link.setAttribute('aria-current', 'page');

        }

      } catch (e) { /* ignore */ }

    });

    if (page === 'ih-user-add-machine' || page === 'ih-user-add-tool') {

      var fab = qs('.ih-site-nav-fab');

      if (fab) {

        qsa('.ih-site-nav-item.is-active').forEach(function (el) {

          el.classList.remove('is-active');
          el.removeAttribute('aria-current');

        });

        fab.classList.add('is-active');

      }

    }

  }



  function initFabMenu() {

    var fab = qs('#ihSiteNavFab');

    var menu = qs('#ihSiteNavFabMenu');

    if (!fab || !menu) return;



    fab.addEventListener('click', function (e) {

      e.stopPropagation();

      var open = fab.getAttribute('aria-expanded') === 'true';

      fab.setAttribute('aria-expanded', open ? 'false' : 'true');

      menu.classList.toggle('hidden', open);

    });



    document.addEventListener('click', function (e) {

      if (!fab.contains(e.target) && !menu.contains(e.target)) {

        fab.setAttribute('aria-expanded', 'false');

        menu.classList.add('hidden');

      }

    });

  }



  var MOBILE_MQ = window.matchMedia('(max-width: 768px)');
  var scrollLockY = 0;

  function isMobile() {
    return MOBILE_MQ.matches;
  }

  function applyBodyScrollLock(locked) {
    if (locked) {
      scrollLockY = window.scrollY || document.documentElement.scrollTop || 0;
      document.body.classList.add('ih-nav-scroll-lock');
      document.body.style.position = 'fixed';
      document.body.style.top = '-' + scrollLockY + 'px';
      document.body.style.left = '0';
      document.body.style.right = '0';
      document.body.style.width = '100%';
      document.body.style.overflow = 'hidden';
    } else {
      document.body.classList.remove('ih-nav-scroll-lock');
      document.body.style.position = '';
      document.body.style.top = '';
      document.body.style.left = '';
      document.body.style.right = '';
      document.body.style.width = '';
      document.body.style.overflow = '';
      window.scrollTo(0, scrollLockY);
    }
  }

  function clearBodyScrollLock() {
    if (
      document.body.classList.contains('ih-nav-scroll-lock') ||
      document.body.style.position === 'fixed'
    ) {
      applyBodyScrollLock(false);
    } else {
      document.body.classList.remove('ih-nav-scroll-lock');
      document.body.style.overflow = '';
    }
  }

  function resetMobileShellState() {
    var sidebar = qs('#ihSidebar');
    var overlay = qs('#ihOverlay');
    var isFigmaAdmin = qs('.ih-wrap.ih-figma-nav.is-admin');

    if (sidebar) {
      sidebar.classList.remove('open', 'mobile-open', 'expanded');
    }
    if (overlay) {
      overlay.classList.remove('active', 'show');
      overlay.style.display = '';
    }
    /* Legacy hamburger — not used on Figma admin shell (logo toggles drawer). */
    if (!isFigmaAdmin) {
      var hamburger = qs('#ihHamburger');
      if (hamburger) {
        hamburger.classList.remove('open');
      }
    }

    document.body.classList.remove('ih-admin-menu-open', 'ih-user-menu-open', 'ih-figma-sidebar-open', 'ih-admin-drawer-open', 'ih-smart-menu-open', 'ih-mobile-drawer-open', 'ih-nav-scroll-lock');
    clearBodyScrollLock();

    var adminWrap = qs('.ih-wrap.ih-figma-nav.is-admin');
    if (adminWrap) {
      adminWrap.classList.remove('sb-collapsed');
    }

    var clipTab = qs('#ihNavClipTab');
    if (clipTab) {
      clipTab.setAttribute('aria-expanded', 'false');
      clipTab.classList.remove('is-open');
      clipTab.setAttribute('aria-label', 'Open navigation menu');
    }

    var logoBtn = qs('#ihSiteNavLogoBtn');
    if (logoBtn) {
      logoBtn.setAttribute('aria-expanded', 'false');
      logoBtn.classList.remove('is-open');
      logoBtn.setAttribute('aria-label', 'Open navigation menu');
    }

    var fab = qs('#ihSiteNavFab');
    var fabMenu = qs('#ihSiteNavFabMenu');
    if (fab) {
      fab.setAttribute('aria-expanded', 'false');
    }
    if (fabMenu) {
      fabMenu.classList.add('hidden');
    }

    var accountBtn = qs('#ihSiteNavAccountBtn');
    var accountMenu = qs('#ihSiteNavAccountMenu');
    if (accountBtn) {
      accountBtn.setAttribute('aria-expanded', 'false');
    }
    if (accountMenu) {
      accountMenu.classList.add('hidden');
    }

    if (window.ihFloatNav && typeof window.ihFloatNav.close === 'function') {
      window.ihFloatNav.close();
    }
  }

  window.ihResetMobileShell = resetMobileShellState;

  var mobileBreakpointHandlers = [];
  var mobileBreakpointListenerBound = false;

  function bindMobileBreakpointChange(handler) {
    mobileBreakpointHandlers.push(handler);
    if (mobileBreakpointListenerBound) {
      handler(MOBILE_MQ);
      return;
    }
    mobileBreakpointListenerBound = true;

    function dispatchBreakpointChange(e) {
      mobileBreakpointHandlers.forEach(function (fn) {
        fn(e);
      });
    }

    if (MOBILE_MQ.addEventListener) {
      MOBILE_MQ.addEventListener('change', dispatchBreakpointChange);
    } else if (MOBILE_MQ.addListener) {
      MOBILE_MQ.addListener(dispatchBreakpointChange);
    }
  }

  bindMobileBreakpointChange(function (e) {
    if (!e.matches) {
      resetMobileShellState();
    }
  });

  function initMobileMenuBtn() {

    var sidebar = qs('#ihSidebar');
    var floatNav = qs('#ihFloatNav');
    var floatToggle = qs('#ihFloatNavToggle');

    /* Float-nav user portal: no legacy sidebar — logo opens float widget */
    if (!sidebar && floatNav && floatToggle) {
      var floatLogoBtn = qs('#ihSiteNavLogoBtn');
      if (floatLogoBtn) {
        floatLogoBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          if (window.ihFloatNav && typeof window.ihFloatNav.toggle === 'function') {
            window.ihFloatNav.toggle();
          } else {
            floatToggle.click();
          }
        });
      }
      return;
    }

    if (!sidebar) return;

    var overlay = qs('#ihOverlay');
    var adminWrap = qs('.ih-wrap.ih-figma-nav.is-admin');
    var isAdmin = !!adminWrap;
    var logoBtn = qs('#ihSiteNavLogoBtn');
    var clipTab = qs('#ihNavClipTab');
    var menuBtn = isAdmin ? clipTab : logoBtn;



    var sidebarAutoCloseTimer = null;
    var SIDEBAR_AUTO_CLOSE_MS = 3000;

    function clearSidebarAutoClose() {
      if (sidebarAutoCloseTimer) {
        clearTimeout(sidebarAutoCloseTimer);
        sidebarAutoCloseTimer = null;
      }
    }

    function scheduleSidebarAutoClose() {
      clearSidebarAutoClose();
      sidebarAutoCloseTimer = setTimeout(function () {
        setSidebarOpen(false);
      }, SIDEBAR_AUTO_CLOSE_MS);
    }

    function clearMobileScrollLock() {
      clearBodyScrollLock();
    }

    function applyMobileScrollLock(locked) {
      if (locked) {
        applyBodyScrollLock(true);
      } else {
        clearBodyScrollLock();
      }
    }

    function syncAdminDrawerShell() {
      if (!isAdmin || !adminWrap) return;
      adminWrap.classList.remove('sb-collapsed');
      sidebar.classList.remove('mobile-open', 'expanded');
    }

    function isSidebarOpen() {
      if (isAdmin) {
        return sidebar.classList.contains('open');
      }
      if (isMobile()) {
        return sidebar.classList.contains('mobile-open');
      }
      var shell = qs('.ih-shell');
      return shell && !shell.classList.contains('sb-collapsed');
    }

    function updateMenuBtnState(open) {
      if (menuBtn) {
        menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuBtn.classList.toggle('is-open', open);
        menuBtn.setAttribute(
          'aria-label',
          open ? 'Close navigation menu' : 'Open navigation menu'
        );
      }
      document.body.classList.toggle('ih-smart-menu-open', isAdmin && open);
    }

    function setSidebarOpen(open) {

      if (isAdmin) {
        syncAdminDrawerShell();

        sidebar.classList.toggle('open', open);
        document.body.classList.toggle('ih-admin-drawer-open', open);
        document.body.classList.remove('ih-mobile-drawer-open', 'ih-figma-sidebar-open');

        if (overlay) {
          overlay.classList.toggle('active', open);
          overlay.style.display = open ? 'block' : 'none';
        }

        if (isMobile()) {
          applyMobileScrollLock(open);
        } else {
          clearMobileScrollLock();
        }

        if (open && isMobile()) {
          scheduleSidebarAutoClose();
        } else {
          clearSidebarAutoClose();
        }

        updateMenuBtnState(open);

      } else {

        if (isMobile()) {
          sidebar.classList.toggle('mobile-open', open);
          sidebar.classList.toggle('expanded', open);
          document.body.classList.toggle('ih-user-menu-open', open);
          applyMobileScrollLock(open);

          if (overlay) {
            overlay.classList.toggle('show', open);
            overlay.style.display = open ? 'block' : 'none';
          }

          if (open) {
            scheduleSidebarAutoClose();
          } else {
            clearSidebarAutoClose();
          }
        } else {
          clearMobileScrollLock();
          var shell = qs('.ih-shell');
          if (shell) {
            shell.classList.toggle('sb-collapsed', !open);
            try {
              localStorage.setItem('ih_user_sb', open ? '0' : '1');
            } catch (e) { /* ignore */ }
          }
        }

        updateMenuBtnState(open);

      }

    }



    function toggleSidebar() {
      setSidebarOpen(!isSidebarOpen());
    }



    if (menuBtn) {
      menuBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleSidebar();
      });
      updateMenuBtnState(isSidebarOpen());
    }

    if (isAdmin && clipTab && clipTab !== menuBtn) {
      clipTab.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        toggleSidebar();
      });
    }

    if (overlay) {

      overlay.addEventListener('click', function () {

        setSidebarOpen(false);

      });

    }



    var closeBtn = qs('#ihSidebarClose');

    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        setSidebarOpen(false);
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isSidebarOpen()) {
        setSidebarOpen(false);
      }
    });

    bindMobileBreakpointChange(function (e) {
      if (!e.matches) {
        updateMenuBtnState(false);
        return;
      }

      if (isAdmin) {
        syncAdminDrawerShell();
        clearMobileScrollLock();
        sidebar.classList.remove('open');
        document.body.classList.remove('ih-admin-drawer-open', 'ih-mobile-drawer-open', 'ih-figma-sidebar-open');
        if (overlay) {
          overlay.classList.remove('active');
          overlay.style.display = 'none';
        }
        clearSidebarAutoClose();
        updateMenuBtnState(false);
      }
    });

    syncAdminDrawerShell();

    qsa('.ih-sidebar .ih-nav-item').forEach(function (link) {

      link.addEventListener('click', function () {

        if (isAdmin || isMobile()) setSidebarOpen(false);

      });

    });

  }



  function initAdminHeaderDateTime() {
    var dayEl = qs('#ihSiteNavDay');
    var dateEl = qs('#ihSiteNavDateFull');
    var timeEl = qs('#ihSiteNavTime');
    if (!dayEl || !dateEl || !timeEl) return;

    function tick() {
      var now = new Date();
      dayEl.textContent = now.toLocaleDateString(undefined, { weekday: 'long' });
      dateEl.textContent = now.toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      });
      timeEl.textContent = now.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      });
    }

    tick();
    window.setInterval(tick, 1000);
  }

  function initBottomNavDateTime() {
    var dateEl = qs('#ihSiteBottomDate');
    var timeEl = qs('#ihSiteBottomTime');
    if (!dateEl || !timeEl) return;

    function tick() {
      var now = new Date();
      var dateText = now.toLocaleDateString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
      });
      var timeText = now.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
      });
      dateEl.textContent = dateText;
      timeEl.textContent = timeText;
      dateEl.setAttribute('datetime', now.toISOString().slice(0, 10));
      timeEl.setAttribute('datetime', now.toTimeString().slice(0, 5));
    }

    tick();
    window.setInterval(tick, 30000);
  }



  function initSmartMenuAttention() {
    var tab = qs('#ihNavClipTab');
    if (!tab) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var shakeMs = 5000;
    var shakeDurationMs = 600;

    function shouldShake() {
      return !tab.classList.contains('is-open')
        && !document.body.classList.contains('ih-admin-drawer-open');
    }

    function runShake() {
      if (!shouldShake()) return;
      tab.classList.add('ih-smart-menu-tab--shake');
      window.setTimeout(function () {
        tab.classList.remove('ih-smart-menu-tab--shake');
      }, shakeDurationMs);
    }

    window.setInterval(runShake, shakeMs);
  }



  function initAccountMenu() {

    var btn = qs('#ihSiteNavAccountBtn');

    var menu = qs('#ihSiteNavAccountMenu');

    if (!btn || !menu) return;



    function closeAccountMenu() {

      btn.setAttribute('aria-expanded', 'false');

      menu.classList.add('hidden');

    }



    function openAccountMenu() {

      btn.setAttribute('aria-expanded', 'true');

      menu.classList.remove('hidden');

      var fab = qs('#ihSiteNavFab');
      var fabMenu = qs('#ihSiteNavFabMenu');
      if (fab) {
        fab.setAttribute('aria-expanded', 'false');
      }
      if (fabMenu) {
        fabMenu.classList.add('hidden');
      }

    }



    btn.addEventListener('click', function (e) {

      e.stopPropagation();

      var open = btn.getAttribute('aria-expanded') === 'true';

      if (open) {

        closeAccountMenu();

      } else {

        openAccountMenu();

      }

    });



    btn.addEventListener('keydown', function (e) {

      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {

        e.preventDefault();

        if (btn.getAttribute('aria-expanded') !== 'true') {

          openAccountMenu();

        }

        var first = menu.querySelector('.ih-site-nav-account-link');

        if (first) first.focus();

      }

    });



    menu.addEventListener('keydown', function (e) {

      if (e.key === 'Escape') {

        closeAccountMenu();

        btn.focus();

      }

    });

    document.addEventListener('keydown', function (e) {

      if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') {

        closeAccountMenu();

      }

    });



    document.addEventListener('click', function (e) {

      if (!btn.contains(e.target) && !menu.contains(e.target)) {

        closeAccountMenu();

      }

    });

  }



  function initBellShortcut() {

    var btn = qs('#ihSiteNavBellBtn');

    if (!btn) return;

    btn.addEventListener('click', function (e) {

      var legacy = qs('#ihBellBtn') || qs('.ih-header-bell') || qs('#ihNotifWrap .ih-header-bell');

      if (legacy && !isMobile()) {
        e.preventDefault();

        legacy.click();

        return;

      }

      var navEl = qs('.ih-site-bottom-nav');
      var mode = navEl && navEl.getAttribute('data-ih-nav-mode');

      if (mode === 'user') {
        return;
      }

      e.preventDefault();

      var page = mode === 'admin' ? 'ih-messages' : 'ih-user-messages';

      if (window.location.href.indexOf(page) === -1) {

        var base = window.location.href.split('admin.php')[0] + 'admin.php';

        window.location.href = base + '?page=' + page;

      }

    });

  }



  function initHashAnchors() {

    var hash = window.location.hash;

    if (!hash) return;

    window.setTimeout(function () {

      var el = qs(hash);

      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });

    }, 400);

  }



  document.addEventListener('DOMContentLoaded', function () {

    ensureSiteNavBodyClass();

    markCurrentSidebar();

    initFabMenu();

    initMobileMenuBtn();

    initSmartMenuAttention();

    initAdminHeaderDateTime();
    initBottomNavDateTime();

    initBellShortcut();

    initAccountMenu();

    initHashAnchors();

  });

})();

