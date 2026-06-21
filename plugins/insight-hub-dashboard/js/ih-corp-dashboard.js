/* ih-corp-dashboard.js — Corporation Workspace dashboard interactivity */

(function () {
  'use strict';

  function ready(fn) {
    document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    var root = document.getElementById('ihCorpDash');
    if (!root) return;

    /* In-page listing search */
    var searchInput = document.getElementById('ihCorpSearch');
    if (searchInput) {
      var searchGrids = root.querySelectorAll('.ih-corp-listing-grid, .ih-corp-browse-grid');
      searchInput.addEventListener('input', function () {
        var q = searchInput.value.trim().toLowerCase();
        searchGrids.forEach(function (grid) {
          grid.querySelectorAll('.ih-listing-card').forEach(function (card) {
            var title = (card.getAttribute('data-title') || card.textContent || '').toLowerCase();
            var kind = card.getAttribute('data-kind') || '';
            var ref = card.querySelector('.ih-ref');
            var refTxt = ref ? ref.textContent.toLowerCase() : '';
            var match = !q || title.indexOf(q) !== -1 || kind.indexOf(q) !== -1 || refTxt.indexOf(q) !== -1;
            card.classList.toggle('is-search-hidden', !match);
          });
        });
      });
    }

    /* My Listings filter tabs */
    var listingTabs = root.querySelector('.ih-corp-listing-tabs');
    if (listingTabs) {
      var listingGrid = root.querySelector('.ih-corp-listing-grid');
      listingTabs.querySelectorAll('.ih-tab').forEach(function (tab) {
        tab.setAttribute('aria-pressed', tab.classList.contains('active') ? 'true' : 'false');
        tab.addEventListener('click', function () {
          listingTabs.querySelectorAll('.ih-tab').forEach(function (x) {
            x.classList.remove('active', 'is-active');
            x.setAttribute('aria-pressed', 'false');
          });
          tab.classList.add('active', 'is-active');
          tab.setAttribute('aria-pressed', 'true');

          var filter = tab.getAttribute('data-filter') || 'all';
          if (!listingGrid) return;
          listingGrid.querySelectorAll('.ih-listing-card').forEach(function (card) {
            var kind = card.getAttribute('data-kind') || '';
            var status = card.getAttribute('data-status') || '';
            var pending = card.getAttribute('data-pending') === '1';
            var match = filter === 'all' ||
              kind === filter ||
              status === filter ||
              (filter === 'approved' && status === 'available') ||
              (filter === 'pending' && pending);

            card.classList.toggle('is-filter-hidden', !match);
            card.setAttribute('aria-hidden', match ? 'false' : 'true');
          });
        });
      });
    }

    /* Browse section filter tabs */
    var browseTabs = root.querySelector('.ih-corp-browse-tabs');
    if (browseTabs) {
      var browseGrid = root.querySelector('.ih-corp-browse-grid');
      browseTabs.querySelectorAll('.ih-tab').forEach(function (tab) {
        tab.setAttribute('aria-pressed', tab.classList.contains('active') ? 'true' : 'false');
        tab.addEventListener('click', function () {
          browseTabs.querySelectorAll('.ih-tab').forEach(function (x) {
            x.classList.remove('active', 'is-active');
            x.setAttribute('aria-pressed', 'false');
          });
          tab.classList.add('active', 'is-active');
          tab.setAttribute('aria-pressed', 'true');
          var filter = tab.getAttribute('data-browse-filter') || 'all';
          if (!browseGrid) return;
          browseGrid.querySelectorAll('[data-kind]').forEach(function (card) {
            var k = card.getAttribute('data-kind');
            var match = filter === 'all' || k === filter;
            card.classList.toggle('is-filter-hidden', !match);
            card.setAttribute('aria-hidden', match ? 'false' : 'true');
          });
        });
      });
    }

    /* Quick-action deep-link to listing tabs */
    root.querySelectorAll('.ih-corp-quick[data-filter]').forEach(function (link) {
      link.addEventListener('click', function (e) {
        var filter = link.getAttribute('data-filter');
        var tabs = root.querySelector('.ih-corp-listing-tabs');
        if (!filter || !tabs) return;
        var target = tabs.querySelector('.ih-tab[data-filter="' + filter + '"]');
        if (target) {
          e.preventDefault();
          target.click();
          var listings = document.getElementById('ihMyListings');
          if (listings) listings.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });

    /* Nav pill active state on scroll */
    var pills = root.querySelectorAll('.ih-corp-pill[href^="#"]');
    var sections = [];
    pills.forEach(function (pill) {
      var id = (pill.getAttribute('href') || '').slice(1);
      var el = id ? document.getElementById(id) : null;
      if (el) sections.push({ pill: pill, el: el });
    });
    if (sections.length && 'IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          pills.forEach(function (p) { p.classList.remove('is-active'); });
          sections.forEach(function (s) {
            if (s.el === entry.target) s.pill.classList.add('is-active');
          });
        });
      }, { rootMargin: '-20% 0px -60% 0px', threshold: 0 });
      sections.forEach(function (s) { io.observe(s.el); });
    }

    /* Mobile footer clock */
    var clockEl = document.getElementById('ihCorpFooterClock');
    if (clockEl) {
      function pad(n) { return n < 10 ? '0' + n : String(n); }
      function tickClock() {
        var now = new Date();
        var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var h = now.getHours();
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        clockEl.textContent = days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' · ' + pad(h) + ':' + pad(now.getMinutes()) + ' ' + ampm;
        clockEl.setAttribute('datetime', now.toISOString());
      }
      tickClock();
      setInterval(tickClock, 60000);
    }
  });
})();
