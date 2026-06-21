/* ih-redesign.js — v2.6.9 dashboard interactivity.

   count-up KPI values, sparkline + bar grow-in, donut/bar hover tooltips,

   analytics period filters, visual tab toggles. No dependencies. Scoped to .ih-rd dashboards. */

(function () {

  'use strict';

  function ready(fn){ document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn); }



  var tip;

  function ensureTip(){

    if (tip) return tip;

    tip = document.createElement('div');

    tip.className = 'ih-rd-tooltip';

    document.body.appendChild(tip);

    return tip;

  }

  function showTip(html, x, y){

    var t = ensureTip();

    t.innerHTML = html;

    var gap = 14;
    var pad = 8;
    var left = x + gap;
    var top = y - 10;

    t.style.left = left + 'px';
    t.style.top = top + 'px';

    t.style.opacity = '1';

    var rect = t.getBoundingClientRect();
    if (rect.right > window.innerWidth - pad) {
      left = Math.max(pad, x - rect.width - gap);
    }
    if (rect.bottom > window.innerHeight - pad) {
      top = Math.max(pad, window.innerHeight - rect.height - pad);
    }
    if (top < pad) top = pad;

    t.style.left = left + 'px';
    t.style.top = top + 'px';

  }

  function hideTip(){ if (tip) tip.style.opacity = '0'; }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
    });
  }

  function padCount(n){ return String(n).padStart(2, '0'); }

  function barGradient(v, max, isCurrent){
    if (isCurrent) return 'linear-gradient(180deg,#887cfd,#5347ce)';
    if (v >= max * 0.8) return 'linear-gradient(180deg,#43c46a,#16553a)';
    return 'linear-gradient(180deg,#5fa0c4,#1f4a62)';
  }

  function buildBarsHtml(labels, counts, currentIdx){
    var max = Math.max.apply(null, counts.concat([0])) || 1;
    var html = '<div class="ih-bars">';
    counts.forEach(function(v, i){
      var h = Math.round(16 + (v / max) * 150);
      var cur = i === currentIdx;
      html += '<div class="col"><div class="bar" style="height:' + h + 'px;background:' + barGradient(v, max, cur) + '" data-count="' + v + '"></div><span class="m">' + (labels[i] || '') + '</span></div>';
    });
    html += '</div>';
    return html;
  }

  function animateBars(chart){
    if (!chart) return;
    chart.querySelectorAll('.col').forEach(function(col, i){
      var bar = col.querySelector('.bar');
      if (!bar) return;
      var h = bar.style.height;
      bar.style.height = '0';
      setTimeout(function(){
        bar.style.transition = 'height .6s cubic-bezier(.2,.8,.2,1)';
        bar.style.height = h;
      }, 80 + i * 35);
    });
  }

  function bindBarChart(chart){
    if (!chart) return;
    chart.querySelectorAll('.col').forEach(function(col){
      var bar = col.querySelector('.bar');
      col.addEventListener('mouseenter', function(){
        chart.querySelectorAll('.col').forEach(function(c){ c.classList.remove('is-active'); });
        col.classList.add('is-active');
      });
      col.addEventListener('mousemove', function(e){
        var m = col.querySelector('.m');
        var n = bar ? (bar.getAttribute('data-count') || '0') : '0';
        showTip('<b>' + (m ? m.textContent : '') + '</b> · ' + n + ' requests', e.clientX, e.clientY);
      });
      col.addEventListener('mouseleave', function(){
        col.classList.remove('is-active');
        hideTip();
      });
    });
    chart.addEventListener('mouseleave', function(){
      chart.querySelectorAll('.col').forEach(function(c){ c.classList.remove('is-active'); });
    });
  }

  function sparkTipHtml(bar) {
    var metric = bar.getAttribute('data-metric') || 'Metric';
    var period = bar.getAttribute('data-period') || 'Period';
    var value = bar.getAttribute('data-value') || '0';
    var unit = bar.getAttribute('data-unit') || metric;

    return '<div class="ih-rd-spark-tip"><b>' + escapeHtml(metric) + '</b><span>' + escapeHtml(period) + '</span><strong>' + escapeHtml(value) + '</strong><em>' + escapeHtml(unit) + '</em></div>';
  }

  function activateSparkBar(bar, x, y) {
    if (!bar) return;
    var spark = bar.closest ? bar.closest('.ih-spark') : null;
    if (spark) {
      spark.querySelectorAll('[data-ih-spark-bar]').forEach(function (b) {
        b.classList.toggle('is-active', b === bar);
      });
    }

    if (typeof x !== 'number' || typeof y !== 'number') {
      var rect = bar.getBoundingClientRect();
      x = rect.left + (rect.width / 2);
      y = rect.top;
    }

    showTip(sparkTipHtml(bar), x, y);
  }

  function clearSparkBar(bar) {
    var spark = bar && bar.closest ? bar.closest('.ih-spark') : null;
    if (spark) {
      spark.querySelectorAll('[data-ih-spark-bar]').forEach(function (b) {
        b.classList.remove('is-active');
      });
    }
    hideTip();
  }

  function bindSparkline(spark) {
    if (!spark || spark.dataset.sparkBound) return;
    spark.dataset.sparkBound = '1';

    spark.querySelectorAll('[data-ih-spark-bar]').forEach(function (bar) {
      bar.addEventListener('mouseenter', function (e) {
        activateSparkBar(bar, e.clientX, e.clientY);
      });
      bar.addEventListener('mousemove', function (e) {
        activateSparkBar(bar, e.clientX, e.clientY);
      });
      bar.addEventListener('mouseleave', function () {
        clearSparkBar(bar);
      });
      bar.addEventListener('focus', function () {
        activateSparkBar(bar);
      });
      bar.addEventListener('blur', function () {
        clearSparkBar(bar);
      });
      bar.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        activateSparkBar(bar);
      });
      bar.addEventListener('touchstart', function (e) {
        var touch = e.touches && e.touches[0];
        if (!touch) return;
        e.preventDefault();
        e.stopPropagation();
        activateSparkBar(bar, touch.clientX, touch.clientY);
      }, { passive: false });
    });

    document.addEventListener('click', function (e) {
      if (!spark.contains(e.target)) {
        spark.querySelectorAll('[data-ih-spark-bar]').forEach(function (b) {
          b.classList.remove('is-active');
        });
      }
    });
  }

  function donutSvgHtml(segs, r){
    r = r || 58;
    var c = 2 * Math.PI * r;
    var off = 0;
    var out = '<circle class="ih-donut-track" cx="75" cy="75" r="' + r + '" stroke="#e8eef0" stroke-width="16" fill="none"/>';
    segs.forEach(function(s, i){
      var len = Math.max(0, s.v) * c;
      out += '<circle class="ih-donut-seg" data-seg="' + i + '" cx="75" cy="75" r="' + r + '" fill="none" stroke="' + s.c + '" stroke-width="16" stroke-linecap="round" stroke-dasharray="' + len.toFixed(1) + ' ' + c.toFixed(1) + '" stroke-dashoffset="' + (-off).toFixed(1) + '"/>';
      off += len;
    });
    return out;
  }

  function statusSegments(status){
    var approved = status.approved || 0;
    var pending = status.pending || 0;
    var completed = status.completed || 0;
    var rejected = status.rejected || 0;
    var total = Math.max(1, approved + pending + completed + rejected);
    var success = approved + completed;
    return {
      total: total,
      successPct: Math.round((success / total) * 100),
      segs: [
        { v: approved / total, c: '#22c55e', key: 'approved', count: approved },
        { v: pending / total, c: '#f59e0b', key: 'pending', count: pending },
        { v: completed / total, c: '#3b82f6', key: 'completed', count: completed },
        { v: rejected / total, c: '#ef4444', key: 'rejected', count: rejected }
      ]
    };
  }

  function bindDonutWrap(wrap){
    if (!wrap || wrap.dataset.donutBound) return;
    wrap.dataset.donutBound = '1';

    wrap.addEventListener('mouseover', function(e){
      var seg = e.target.closest ? e.target.closest('circle.ih-donut-seg') : null;
      if (seg) {
        var idx = parseInt(seg.getAttribute('data-seg'), 10);
        if (!isNaN(idx)) highlightDonut(wrap, idx);
        return;
      }
      var lg = e.target.closest ? e.target.closest('.ih-legend .lg') : null;
      if (lg) {
        var legs = wrap.querySelectorAll('.ih-legend .lg');
        for (var i = 0; i < legs.length; i++) {
          if (legs[i] === lg) { highlightDonut(wrap, i); break; }
        }
      }
    });

    wrap.addEventListener('mousemove', function(e){
      var seg = e.target.closest ? e.target.closest('circle.ih-donut-seg') : null;
      var lg = e.target.closest ? e.target.closest('.ih-legend .lg') : null;
      if (!seg && !lg) return;
      var legs = wrap.querySelectorAll('.ih-legend .lg');
      var idx = -1;
      if (seg) idx = parseInt(seg.getAttribute('data-seg'), 10);
      else if (lg) {
        for (var i = 0; i < legs.length; i++) {
          if (legs[i] === lg) { idx = i; break; }
        }
      }
      if (idx < 0) return;
      var active = seg || legs[idx];
      var color = seg ? seg.getAttribute('stroke') : '#16a34a';
      var label = legs[idx] ? legs[idx].textContent.trim() : 'Segment';
      showTip('<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + color + ';margin-right:6px"></span>' + label, e.clientX, e.clientY);
    });

    wrap.addEventListener('mouseleave', function(){
      highlightDonut(wrap, null);
      hideTip();
    });
  }



  function highlightDonut(wrap, activeIdx) {

    if (!wrap) return;

    var donut = wrap.querySelector('.ih-donut');

    if (!donut) return;

    var segs = wrap.querySelectorAll('svg circle.ih-donut-seg');

    var legs = wrap.querySelectorAll('.ih-legend .lg');

    donut.classList.add('is-interactive');

    segs.forEach(function (c, i) {

      var on = activeIdx === null || i === activeIdx;

      c.classList.toggle('is-active', activeIdx !== null && i === activeIdx);

      c.classList.toggle('is-dim', activeIdx !== null && i !== activeIdx);

    });

    legs.forEach(function (lg, i) {

      lg.classList.toggle('is-active', activeIdx !== null && i === activeIdx);

    });

  }



  ready(function () {

    var roots = document.querySelectorAll('.ih-rd');

    if (!roots.length) return;



    // 1) count-up stat values

    function countUp(el){

      var raw = el.textContent.trim();

      var target = parseInt(raw.replace(/\D/g, ''), 10);

      if (isNaN(target)) return;

      var pad = /^0\d/.test(raw) ? raw.replace(/\D/g, '').length : 0;

      var dur = 800, t0 = performance.now();

      (function step(t){

        var p = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - p, 3);

        var v = String(Math.round(e * target));

        el.textContent = pad ? v.padStart(pad, '0') : v;

        if (p < 1) requestAnimationFrame(step);

      })(t0);

    }

    var vals = document.querySelectorAll('.ih-rd .ih-stat-value');

    var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function (ents) {

      ents.forEach(function (e) { if (e.isIntersecting) { countUp(e.target); io.unobserve(e.target); } });

    }, { threshold: .4 }) : null;

    vals.forEach(function (v) { io ? io.observe(v) : countUp(v); });



    // 2) sparkline grow-in

    document.querySelectorAll('.ih-rd .ih-spark').forEach(function (s) {

      [].slice.call(s.children).forEach(function (b, i) {

        var h = b.style.height; b.style.height = '0';

        setTimeout(function () { b.style.transition = 'height .5s cubic-bezier(.2,.8,.2,1)'; b.style.height = h; }, 120 + i * 40);

      });

      bindSparkline(s);

    });



    // 3) bar chart grow-in + interactive hover

    document.querySelectorAll('.ih-rd .ih-bars').forEach(function (chart) {
      animateBars(chart);
      bindBarChart(chart);
    });

    // 4) donut segment + legend interactivity

    document.querySelectorAll('.ih-rd .ih-donut-wrap').forEach(function (wrap) {
      bindDonutWrap(wrap);
    });

    // 5) analytics period filters (week / month / year)

    document.querySelectorAll('.ih-rd .ih-analytics-grid').forEach(function (grid) {
      var dataEl = grid.querySelector('#ih-analytics-data');
      if (!dataEl) return;
      var datasets;
      try { datasets = JSON.parse(dataEl.textContent); } catch (e) { return; }

      var barsHost = grid.querySelector('.ih-analytics-bars-host');
      var titleEl = grid.querySelector('.ih-analytics-title');
      var subEl = grid.querySelector('.ih-analytics-sub');
      var statusSubEl = grid.querySelector('.ih-status-sub');
      var donutWrap = grid.querySelector('.ih-analytics-status .ih-donut-wrap');
      var donutSvg = donutWrap ? donutWrap.querySelector('.ih-donut svg') : null;
      var donutPct = donutWrap ? donutWrap.querySelector('.ih-donut-pct') : null;
      var legendCounts = donutWrap ? donutWrap.querySelectorAll('.ih-legend-count') : [];
      var filters = grid.querySelectorAll('.ih-period-filter');

      function applyPeriod(period) {
        var data = datasets[period];
        if (!data || !barsHost) return;

        if (barsHost.firstChild) barsHost.removeChild(barsHost.firstChild);
        barsHost.insertAdjacentHTML('beforeend', buildBarsHtml(data.labels || [], data.counts || [], data.current_idx));
        var chart = barsHost.querySelector('.ih-bars');
        animateBars(chart);
        bindBarChart(chart);

        if (titleEl && data.title) titleEl.textContent = data.title;
        if (subEl && data.subtitle) subEl.textContent = data.subtitle;
        if (statusSubEl && data.status_sub) statusSubEl.textContent = data.status_sub;

        var donut = statusSegments(data.status || {});
        if (donutSvg) donutSvg.innerHTML = donutSvgHtml(donut.segs, parseInt(donutWrap.getAttribute('data-donut-r') || '58', 10));
        if (donutPct) donutPct.textContent = donut.successPct + '%';
        if (legendCounts.length === 4) {
          donut.segs.forEach(function (seg, i) {
            if (legendCounts[i]) legendCounts[i].textContent = padCount(seg.count);
          });
        }
      }

      filters.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var period = btn.getAttribute('data-period');
          if (!period || !datasets[period]) return;
          filters.forEach(function (x) { x.classList.remove('is-active'); });
          btn.classList.add('is-active');
          applyPeriod(period);
        });
      });
    });

    // 6) visual tab toggles

    document.querySelectorAll('.ih-rd .ih-tabs').forEach(function (group) {

      group.querySelectorAll('.ih-tab').forEach(function (t) {

        t.addEventListener('click', function () {

          group.querySelectorAll('.ih-tab').forEach(function (x) { x.classList.remove('active'); });

          t.classList.add('active');

          var filter = t.getAttribute('data-filter');

          var host = group.parentElement;

          var grid = host ? host.querySelector('.ih-grid-cards') : null;

          if (!grid || !filter) return;

          grid.querySelectorAll('[data-kind]').forEach(function (card) {

            var k = card.getAttribute('data-kind');
            var st = card.getAttribute('data-status') || '';

            var show = filter === 'all'
              || k === filter
              || (filter === 'pending' && (st === 'pending' || card.getAttribute('data-pending') === '1'))
              || (filter === 'approved' && st === 'available');

            card.style.display = show ? '' : 'none';

          });

        });

      });

    });

  });

})();

