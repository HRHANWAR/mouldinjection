/* IH Dynamic Menu — WP admin menu + legacy mobile toggle fallback */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function addMobileToggle() {
    /* Figma shell uses header logo + bottom nav — never inject floating hamburger */
    if (document.querySelector('.ih-wrap.ih-figma-nav') || document.querySelector('.ih-shell.ih-figma-dashboard')) return;
    if (document.body.classList.contains('ih-site-nav-active') || document.body.classList.contains('ih-site-nav-admin')) return;
    if (document.getElementById('ihSiteNavLogoBtn') || document.getElementById('ihNavClipTab') || document.getElementById('ihSiteNavBellBtn')) return;
    if (!document.getElementById('adminmenuwrap') && !document.getElementById('ihSidebar')) return;

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ih-mobile-menu-toggle';
    btn.setAttribute('aria-label', 'Open dashboard menu');
    btn.innerHTML = '<span></span>';
    btn.addEventListener('click', function () {
      var sidebar = document.getElementById('ihSidebar');
      var overlay = document.getElementById('ihOverlay');
      if (sidebar) {
        var open = sidebar.classList.toggle('mobile-open');
        sidebar.classList.toggle('expanded', open);
        document.body.classList.toggle('ih-user-menu-open', open);
        if (overlay) {
          overlay.style.display = open ? 'block' : 'none';
          overlay.classList.toggle('show', open);
        }
        return;
      }
      document.body.classList.toggle('ih-admin-menu-open');
    });
    document.body.appendChild(btn);

    document.addEventListener('click', function (event) {
      var sidebar = document.getElementById('ihSidebar');
      if (document.body.classList.contains('ih-user-menu-open')) {
        if (event.target.closest('#ihSidebar') || event.target.closest('.ih-mobile-menu-toggle') || event.target.closest('.ih-hamburger')) return;
        if (sidebar) sidebar.classList.remove('mobile-open', 'expanded');
        document.body.classList.remove('ih-user-menu-open');
        var overlay = document.getElementById('ihOverlay');
        if (overlay) {
          overlay.classList.remove('show');
          overlay.style.display = 'none';
        }
        return;
      }
      if (!document.body.classList.contains('ih-admin-menu-open')) return;
      if (event.target.closest('#adminmenuwrap') || event.target.closest('.ih-mobile-menu-toggle')) return;
      document.body.classList.remove('ih-admin-menu-open');
    });
  }

  ready(function () {
    document.body.classList.add('ih-menu-enhanced');
    addMobileToggle();
  });
})();
