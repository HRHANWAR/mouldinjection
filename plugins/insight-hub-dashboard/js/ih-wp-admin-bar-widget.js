(function () {
  'use strict';

  var HIDE_MS = 10000;

  function qs(sel) {
    return document.querySelector(sel);
  }

  function initWpAdminBarWidget() {
    var btn = qs('#ihWpAdminBarToggle');
    var bar = qs('#wpadminbar');
    if (!btn || !bar || !document.body.classList.contains('ih-site-nav-admin')) {
      return;
    }

    var hideTimer = null;

    function hideBar() {
      document.body.classList.remove('ih-wp-admin-bar-visible');
      btn.classList.remove('is-active');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Show WordPress toolbar');
      if (hideTimer) {
        clearTimeout(hideTimer);
        hideTimer = null;
      }
    }

    function scheduleHide() {
      if (hideTimer) clearTimeout(hideTimer);
      hideTimer = window.setTimeout(hideBar, HIDE_MS);
    }

    function showBar() {
      document.body.classList.add('ih-wp-admin-bar-visible');
      btn.classList.add('is-active');
      btn.setAttribute('aria-expanded', 'true');
      btn.setAttribute('aria-label', 'WordPress toolbar open');
      scheduleHide();
    }

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (document.body.classList.contains('ih-wp-admin-bar-visible')) {
        hideBar();
      } else {
        showBar();
      }
    });

    bar.addEventListener('mouseenter', function () {
      if (hideTimer) clearTimeout(hideTimer);
    });

    bar.addEventListener('mouseleave', function () {
      if (document.body.classList.contains('ih-wp-admin-bar-visible')) {
        scheduleHide();
      }
    });

    bar.addEventListener('focusin', function () {
      if (hideTimer) clearTimeout(hideTimer);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && document.body.classList.contains('ih-wp-admin-bar-visible')) {
        hideBar();
      }
    });
  }

  if (document.readyState !== 'loading') {
    initWpAdminBarWidget();
  } else {
    document.addEventListener('DOMContentLoaded', initWpAdminBarWidget);
  }
})();
