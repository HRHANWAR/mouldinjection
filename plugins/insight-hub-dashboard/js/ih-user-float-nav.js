(function () {
  'use strict';

  var SHAKE_INTERVAL_MS = 5000;
  var COLOR_CYCLE_MS = 2000;
  var COLOR_CYCLE_REDUCED_MS = 8000;
  var SHAKE_CLASS = 'is-shake';
  var OPEN_CLASS = 'is-open';
  var CYCLE_COLORS = ['#5347ce', '#4896fe', '#16a34a', '#f59e0b', '#ef4444', '#887cfd'];

  function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function isDesktop() {
    return window.matchMedia('(min-width: 769px)').matches;
  }

  function initFloatNav() {
    var root = document.getElementById('ihFloatNav');
    var toggle = document.getElementById('ihFloatNavToggle');
    var panel = document.getElementById('ihFloatNavPanel');
    if (!root || !toggle || !panel || root.getAttribute('data-ih-float-nav-init') === '1') {
      return;
    }
    root.setAttribute('data-ih-float-nav-init', '1');

    var shakeTimer = null;
    var shakeTimeout = null;
    var colorCycleTimer = null;
    var colorCycleIndex = 0;
    var hoverOpenTimer = null;
    var pinnedOpen = false;

    function clearHoverOpenTimer() {
      if (hoverOpenTimer) {
        clearTimeout(hoverOpenTimer);
        hoverOpenTimer = null;
      }
    }

    function syncLogoBtnState(isOpen) {
      var logoBtn = document.getElementById('ihSiteNavLogoBtn');
      if (!logoBtn) {
        return;
      }
      logoBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      logoBtn.classList.toggle('is-open', isOpen);
      logoBtn.setAttribute(
        'aria-label',
        isOpen ? 'Close navigation menu' : 'Open navigation menu'
      );
    }

    function setOpen(isOpen, options) {
      options = options || {};
      var nextPinned = options.pinned;

      if (typeof nextPinned === 'boolean') {
        pinnedOpen = nextPinned;
      } else if (!isOpen) {
        pinnedOpen = false;
      }

      root.classList.toggle(OPEN_CLASS, isOpen);
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      toggle.setAttribute(
        'aria-label',
        isOpen
          ? (toggle.getAttribute('data-label-close') || 'Close workspace menu')
          : (toggle.getAttribute('data-label-open') || 'Open workspace menu')
      );
      if (isOpen) {
        panel.removeAttribute('hidden');
        stopShake();
        stopColorCycle();
      } else {
        panel.setAttribute('hidden', '');
        if (!prefersReducedMotion()) {
          startShake();
        }
        startColorCycle();
      }
      syncLogoBtnState(isOpen);
    }

    function isOpen() {
      return root.classList.contains(OPEN_CLASS);
    }

    function triggerShake() {
      if (isOpen() || prefersReducedMotion() || document.hidden) {
        return;
      }
      root.classList.remove(SHAKE_CLASS);
      void root.offsetWidth;
      root.classList.add(SHAKE_CLASS);
      if (shakeTimeout) {
        clearTimeout(shakeTimeout);
      }
      shakeTimeout = setTimeout(function () {
        root.classList.remove(SHAKE_CLASS);
      }, 600);
    }

    function startShake() {
      stopShake();
      if (prefersReducedMotion()) {
        return;
      }
      shakeTimer = setInterval(triggerShake, SHAKE_INTERVAL_MS);
    }

    function stopShake() {
      if (shakeTimer) {
        clearInterval(shakeTimer);
        shakeTimer = null;
      }
      root.classList.remove(SHAKE_CLASS);
    }

    function applyCycleColor() {
      root.style.setProperty(
        '--ih-float-cycle-accent',
        CYCLE_COLORS[colorCycleIndex % CYCLE_COLORS.length]
      );
      colorCycleIndex += 1;
    }

    function colorCycleInterval() {
      return prefersReducedMotion() ? COLOR_CYCLE_REDUCED_MS : COLOR_CYCLE_MS;
    }

    function startColorCycle() {
      stopColorCycle();
      if (isOpen() || document.hidden) {
        return;
      }
      applyCycleColor();
      colorCycleTimer = setInterval(function () {
        if (!isOpen() && !document.hidden) {
          applyCycleColor();
        }
      }, colorCycleInterval());
    }

    function stopColorCycle() {
      if (colorCycleTimer) {
        clearInterval(colorCycleTimer);
        colorCycleTimer = null;
      }
      root.style.removeProperty('--ih-float-cycle-accent');
    }

    function closePanel() {
      clearHoverOpenTimer();
      setOpen(false, { pinned: false });
    }

    function togglePanel() {
      clearHoverOpenTimer();
      var nextOpen = !isOpen();
      setOpen(nextOpen, { pinned: nextOpen });
    }

    toggle.setAttribute('data-label-open', toggle.getAttribute('aria-label') || 'Open workspace menu');
    toggle.setAttribute('data-label-close', 'Close workspace menu');

    toggle.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      togglePanel();
    });

    toggle.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        event.stopPropagation();
        togglePanel();
      }
    });

    document.addEventListener('click', function (event) {
      if (!isOpen()) {
        return;
      }
      if (root.contains(event.target)) {
        return;
      }
      closePanel();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && isOpen()) {
        closePanel();
        toggle.focus();
      }
    });

    root.addEventListener('focusin', function () {
      stopShake();
    });

    root.addEventListener('focusout', function () {
      if (!isOpen() && !prefersReducedMotion()) {
        startShake();
      }
    });

    root.addEventListener('mouseenter', function () {
      if (!isDesktop()) {
        return;
      }
      clearHoverOpenTimer();
      hoverOpenTimer = setTimeout(function () {
        hoverOpenTimer = null;
        if (!isOpen()) {
          setOpen(true, { pinned: false });
        }
      }, 180);
    });

    root.addEventListener('mouseleave', function () {
      if (!isDesktop()) {
        return;
      }
      clearHoverOpenTimer();
      if (isOpen() && !pinnedOpen) {
        setOpen(false, { pinned: false });
      }
    });

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        stopShake();
        stopColorCycle();
      } else if (!isOpen()) {
        startShake();
        startColorCycle();
      }
    });

    var motionMq = window.matchMedia('(prefers-reduced-motion: reduce)');
    function onMotionChange(e) {
      if (e.matches) {
        stopShake();
      } else if (!isOpen()) {
        startShake();
      }
      if (!isOpen()) {
        startColorCycle();
      }
    }
    if (motionMq.addEventListener) {
      motionMq.addEventListener('change', onMotionChange);
    } else if (motionMq.addListener) {
      motionMq.addListener(onMotionChange);
    }

    var desktopMq = window.matchMedia('(min-width: 769px)');
    function onDesktopChange(e) {
      clearHoverOpenTimer();
      if (!e.matches && isOpen()) {
        closePanel();
      }
    }
    if (desktopMq.addEventListener) {
      desktopMq.addEventListener('change', onDesktopChange);
    } else if (desktopMq.addListener) {
      desktopMq.addListener(onDesktopChange);
    }

    panel.querySelectorAll('.ih-float-nav__item').forEach(function (link) {
      link.addEventListener('click', function () {
        if (!isDesktop()) {
          closePanel();
        }
      });
    });

    window.ihFloatNav = {
      isOpen: isOpen,
      setOpen: function (open) {
        clearHoverOpenTimer();
        setOpen(!!open, { pinned: !!open });
      },
      toggle: togglePanel,
      close: closePanel,
    };

    setOpen(false, { pinned: false });
    if (!prefersReducedMotion()) {
      setTimeout(triggerShake, 1200);
      startShake();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFloatNav);
  } else {
    initFloatNav();
  }
})();
