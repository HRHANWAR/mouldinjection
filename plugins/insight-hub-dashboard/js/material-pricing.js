/* ============================================================
   material-pricing.js — live resin price → cost & order calculator
   Vanilla JS, framework-free. Scope: the user cost calculator only.

   Flow (never talks to a pricing website directly):
     backend price DB  ──/ih/v1/material-price──▶  this module  ──▶  calculator

   The module:
     • populates Material / Grade / Region / Supplier selectors
     • fetches the working £/kg (quote price) for the selection
     • shows price source + last-updated + a stale/offline badge
     • supports a manual override (staff) with an audit reason
     • caches the last good price so a feed outage never blocks a quote
     • recomputes the calculator live (debounced)
   ============================================================ */
(function () {
  'use strict';

  var API = (window.IH_PRICING_API || '/wp-json/ih/v1');     // base path
  var CACHE_KEY = 'ih_price_cache_v1';
  var STALE_AFTER_DAYS = 7;                                   // price older than this = warn

  /* ---- PLATFORM REVENUE: the marketplace's cut on each order ----------
     These two add on top of the manufacturing subtotal and are how the
     site earns. Tune centrally (or override per deployment via globals). */
  var PLATFORM_CHARGE_PCT  = (window.IH_PLATFORM_CHARGE_PCT  != null ? window.IH_PLATFORM_CHARGE_PCT  : 5);  // service charge
  var TRANSACTION_FEE_PCT  = (window.IH_TRANSACTION_FEE_PCT  != null ? window.IH_TRANSACTION_FEE_PCT  : 2);  // payment/transaction fee

  /* ---------- 1. PURE CALCULATION (no DOM, easy to unit-test) ---------- */
  // All money in GBP. Returns every output the calculator shows.
  function calculateQuote(i) {
    var partWeightG    = num(i.partWeightG, 0);
    var runnerWeightG  = num(i.runnerWeightG, 0);
    var cavities       = Math.max(1, Math.round(num(i.cavities, 1)));
    var cycleTimeS     = num(i.cycleTimeSeconds, 0);
    var materialPerKg  = num(i.materialPricePerKg, 0);
    var machineRatePerH= num(i.machineRatePerHour, 0);
    var scrapPercent   = clamp(num(i.scrapPercent, 0), 0, 95);
    var orderQty       = Math.max(0, Math.round(num(i.orderQuantity, 0)));
    var toolingCost    = num(i.toolingCost, 0);

    // grams of material consumed per good part, incl. its share of the runner + scrap loss
    var gramsPerPart = (partWeightG + runnerWeightG / cavities) / (1 - scrapPercent / 100);
    var materialCostPerPart = (gramsPerPart / 1000) * materialPerKg;

    var processingCostPerPart = cycleTimeS > 0
      ? (machineRatePerH * (cycleTimeS / 3600)) / cavities
      : 0;

    var toolingCostPerPart = (toolingCost > 0 && orderQty > 0) ? toolingCost / orderQty : 0;

    var unitCost   = materialCostPerPart + processingCostPerPart + toolingCostPerPart;
    var orderSubtotal = unitCost * orderQty;            // manufacturing cost (what it costs to make)

    // --- platform revenue: service charge + transaction fee on the subtotal ---
    var chargePct = num(i.platformChargePct, PLATFORM_CHARGE_PCT);
    var feePct    = num(i.transactionFeePct, TRANSACTION_FEE_PCT);
    var serviceCharge   = orderSubtotal * (chargePct / 100);
    var transactionFee  = orderSubtotal * (feePct / 100);
    var platformRevenue = serviceCharge + transactionFee;     // what the website earns on this order
    var grandTotal      = orderSubtotal + platformRevenue;    // what the buyer pays

    var partsPerHour = cycleTimeS > 0 ? (3600 / cycleTimeS) * cavities : 0;
    var machineHours = partsPerHour > 0 ? orderQty / partsPerHour : 0;
    var runTimeDays  = machineHours / 24;

    return {
      materialCostPerPart: materialCostPerPart,
      processingCostPerPart: processingCostPerPart,
      toolingCostPerPart: toolingCostPerPart,
      unitCost: unitCost,
      orderSubtotal: orderSubtotal,
      orderTotal: orderSubtotal,        // back-compat alias (= subtotal, before platform fees)
      serviceCharge: serviceCharge,
      serviceChargePct: chargePct,
      transactionFee: transactionFee,
      transactionFeePct: feePct,
      platformRevenue: platformRevenue,
      grandTotal: grandTotal,
      partsPerHour: partsPerHour,
      machineHours: machineHours,
      runTimeDays: runTimeDays,
      materialPer1000: materialCostPerPart * 1000,
      gramsPerPart: gramsPerPart
    };
  }

  /* ---------- 2. INPUT VALIDATION (returns problems, never throws) ----- */
  function validateInputs(i) {
    var errs = [];
    if (num(i.partWeightG, 0) <= 0) errs.push('Part weight must be greater than 0 g.');
    if (Math.round(num(i.cavities, 1)) < 1) errs.push('Cavities must be at least 1.');
    if (num(i.cycleTimeSeconds, 0) <= 0) errs.push('Cycle time must be greater than 0 s.');
    if (num(i.materialPricePerKg, 0) <= 0) errs.push('Material price must be greater than 0.');
    if (num(i.materialPricePerKg, 0) > 500) errs.push('Material price looks too high (> £500/kg) — check the figure.');
    if (clamp(num(i.scrapPercent, 0), -1, 1000) < 0) errs.push('Scrap % cannot be negative.');
    if (num(i.scrapPercent, 0) >= 95) errs.push('Scrap % of 95+ is unrealistic.');
    if (Math.round(num(i.orderQuantity, 0)) < 1) errs.push('Order quantity must be at least 1.');
    return errs;
  }

  /* ---------- 3. PRICE FETCH (with cache + graceful fallback) ---------- */
  // Resolves to { pricePerKg, source, updatedAt, isOverride, stale, offline,
  //               sourceType, sourceName, calculationType, warning,
  //               isVerifiedLive, lastChecked, serverBadge }
  function fetchPrice(sel) {
    var qs = '?material=' + enc(sel.material) + '&region=' + enc(sel.region) + '&supplier=' + enc(sel.supplier);
    if (sel.grade) qs += '&grade=' + enc(sel.grade);
    return fetch(API + '/material-price' + qs, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (d) {
        var out = {
          pricePerKg: Number(d.price_used_per_kg),
          source: d.source || 'Price list',
          updatedAt: d.updated_at,
          isOverride: !!d.is_manual_override,
          stale: isStale(d.updated_at),
          offline: false,
          // 24h reference-system fields (graceful defaults for older backends)
          sourceType: d.source_type || (d.is_manual_override ? 'manual_override' : ''),
          sourceName: d.source_name || d.source || 'Price list',
          calculationType: d.calculation_type || '',
          warning: d.warning || '',
          isVerifiedLive: !!d.is_verified_live,
          lastChecked: d.last_checked || null,
          serverBadge: d.badge || null
        };
        if (!isFinite(out.pricePerKg) || out.pricePerKg <= 0) throw new Error('Bad price payload');
        cacheWrite(sel, out);                          // keep last good value
        return out;
      })
      .catch(function () {
        // FEED/NETWORK FAILURE → fall back to the last good cached price, flagged offline.
        var c = cacheRead(sel);
        if (c) { c.offline = true; c.stale = isStale(c.updatedAt); return c; }
        // No cache at all → return null so the UI lets the user type a price manually.
        return null;
      });
  }

  /* ---------- 4. DOM WIRING ------------------------------------------- */
  var root = document.querySelector('[data-ih-cost-calc]');
  if (!root) return;

  var els = {
    material: q('material'), grade: q('grade'), region: q('region'), supplier: q('supplier'),
    source: q('price-source'), priceKg: q('price-per-kg'), updated: q('price-updated'),
    badge: q('price-badge'),
    // 24h reference-system display hooks (optional — render only if present)
    sourceType: q('price-source-type'), lastChecked: q('price-last-checked'),
    warning: q('price-warning'), usePublicRef: q('use-public-ref'),
    overrideToggle: q('override-toggle'), overrideWrap: q('override-wrap'),
    overridePrice: q('override-price'), overrideReason: q('override-reason'), overrideSave: q('override-save'),
    // calculator inputs
    partWeight: q('part-weight'), runnerWeight: q('runner-weight'), cavities: q('cavities'),
    cycle: q('cycle-time'), machineRate: q('machine-rate'), scrap: q('scrap'),
    orderQty: q('order-qty'), tooling: q('tooling'),
    // outputs
    out: {
      material: q('out-material'), processing: q('out-processing'), unit: q('out-unit'),
      pph: q('out-pph'), total: q('out-total'), hours: q('out-hours'),
      days: q('out-days'), per1000: q('out-per1000'),
      // platform fees + grand total
      subtotal: q('out-subtotal'), charge: q('out-charge'), fee: q('out-fee'),
      grand: q('out-grand'), revenue: q('out-revenue')
    },
    errors: q('calc-errors')
  };

  var currentPrice = 0;

  function readInputs() {
    return {
      partWeightG: val(els.partWeight), runnerWeightG: val(els.runnerWeight),
      cavities: val(els.cavities), cycleTimeSeconds: val(els.cycle),
      materialPricePerKg: currentPrice, machineRatePerHour: val(els.machineRate),
      scrapPercent: val(els.scrap), orderQuantity: val(els.orderQty),
      toolingCost: val(els.tooling)
    };
  }

  function paint() {
    var inputs = readInputs();
    var errs = validateInputs(inputs);
    if (els.errors) { els.errors.textContent = errs.join(' '); els.errors.style.display = errs.length ? '' : 'none'; }
    var r = calculateQuote(inputs);
    set(els.out.material, money(r.materialCostPerPart, true));
    set(els.out.processing, money(r.processingCostPerPart, true));
    set(els.out.unit, money(r.unitCost, true));
    set(els.out.pph, Math.round(r.partsPerHour).toLocaleString('en-GB'));
    set(els.out.total, moneyShort(r.orderSubtotal));
    set(els.out.hours, Math.round(r.machineHours).toLocaleString('en-GB'));
    set(els.out.days, r.runTimeDays.toFixed(1));
    set(els.out.per1000, money(r.materialPer1000, false));
    // platform fees + grand total
    set(els.out.subtotal, moneyShort(r.orderSubtotal));
    set(els.out.charge, moneyShort(r.serviceCharge));
    set(els.out.fee, moneyShort(r.transactionFee));
    set(els.out.grand, moneyShort(r.grandTotal));
    set(els.out.revenue, moneyShort(r.platformRevenue));
  }
  var paintDebounced = debounce(paint, 150);

  function applyPrice(p) {
    if (!p) {                                          // no price available → manual entry mode
      set(els.source, 'No price on record — manual entry required');
      set(els.updated, '');
      set(els.sourceType, 'Manual required');
      set(els.lastChecked, '');
      showWarning('No price on record — enter a £/kg manually. The figure won\u2019t be saved.');
      badge('manual', 'Manual required');
      if (els.priceKg) { els.priceKg.removeAttribute('readonly'); els.priceKg.value = els.priceKg.value || ''; }
      return;
    }
    currentPrice = p.pricePerKg;
    if (els.priceKg) { els.priceKg.value = p.pricePerKg.toFixed(2); els.priceKg.setAttribute('readonly', 'readonly'); }
    set(els.source, p.sourceName + (p.isOverride ? ' \u00b7 manual override' : ''));
    set(els.updated, 'Updated ' + fmtDate(p.updatedAt));

    // Resolve the badge. Prefer the server's source_type mapping so the badge is
    // a single source of truth; fall back to client status for offline/stale.
    var b;
    if (p.offline) {
      b = { state: 'offline', label: 'Offline \u00b7 last known' };
    } else {
      b = badgeForSource(p.sourceType, p.isVerifiedLive) ||
          (p.serverBadge && p.serverBadge.state ? { state: p.serverBadge.state, label: p.serverBadge.label } : null) ||
          { state: 'manual', label: 'Manual' };
      // The server label wins when it matches the same state (keeps text in sync).
      if (p.serverBadge && p.serverBadge.label && p.serverBadge.state === b.state) b.label = p.serverBadge.label;
    }
    badge(b.state, b.label);

    // Friendly source-type line + last-checked + warning (all optional hooks).
    set(els.sourceType, b.label + (p.calculationType ? ' \u00b7 ' + p.calculationType : ''));
    set(els.lastChecked, p.lastChecked ? 'Checked ' + fmtDate(p.lastChecked) : '');

    // Compose the warning: server warning + client-side staleness note.
    var warn = p.warning || '';
    if (p.offline) warn = joinWarn('Showing the last known price (the backend was unreachable).', warn);
    else if (p.stale && p.sourceType !== 'monthly_index') warn = joinWarn(warn, 'Material reference price is older than ' + STALE_AFTER_DAYS + ' days.');
    showWarning(warn);

    paint();
  }

  /* Badge mapping — MIRRORS PHP IH_Material_Price_Utils::get_price_badge().
     CRITICAL: "Live" only for a verified licensed feed. Every public reference
     is explicitly NOT "Live". Returns null for an empty/unknown source type so
     the caller can fall back to the server-provided badge. */
  function badgeForSource(sourceType, isVerifiedLive) {
    switch (sourceType) {
      case 'live_feed':
        return isVerifiedLive ? { state: 'live', label: 'Live' } : { state: 'manual', label: 'Unverified feed' };
      case 'csv_imported': return { state: 'csv', label: 'CSV imported' };
      case 'manual_override': return { state: 'override', label: 'Manual override' };
      case 'public_market_reference': return { state: 'public', label: 'Public reference' };
      case 'monthly_index': return { state: 'index', label: 'Monthly index' };
      case 'delayed_public_reference': return { state: 'delayed', label: 'Delayed reference' };
      case 'news_reference': return { state: 'news', label: 'News reference' };
      case 'default_estimate': return { state: 'estimate', label: 'Default estimate' };
      case 'manual_required': return { state: 'manual', label: 'Manual required' };
      default: return null;
    }
  }

  function showWarning(text) {
    if (!els.warning) return;
    els.warning.textContent = text || '';
    els.warning.style.display = text ? '' : 'none';
  }
  function joinWarn(a, b) {
    a = (a || '').trim(); b = (b || '').trim();
    if (!a) return b; if (!b) return a; return a + ' ' + b;
  }

  function loadPrice() {
    var sel = { material: valStr(els.material), grade: valStr(els.grade), region: valStr(els.region), supplier: valStr(els.supplier) };
    if (!sel.material) return;
    badge('loading', 'Updating…');
    fetchPrice(sel).then(applyPrice);
  }

  // selector changes → refetch
  [els.material, els.grade, els.region, els.supplier].forEach(function (s) {
    if (s) s.addEventListener('change', loadPrice);
  });
  // numeric inputs → recompute
  [els.partWeight, els.runnerWeight, els.cavities, els.cycle, els.machineRate, els.scrap, els.orderQty, els.tooling]
    .forEach(function (e) { if (e) e.addEventListener('input', paintDebounced); });
  // allow editing the price only in manual mode
  if (els.priceKg) els.priceKg.addEventListener('input', function () {
    if (!els.priceKg.hasAttribute('readonly')) { currentPrice = num(els.priceKg.value, 0); paintDebounced(); }
  });

  /* ---------- 5. MANUAL OVERRIDE WORKFLOW (staff) --------------------- */
  if (els.overrideToggle) {
    els.overrideToggle.addEventListener('change', function () {
      var on = els.overrideToggle.checked;
      if (els.overrideWrap) els.overrideWrap.style.display = on ? '' : 'none';
      if (els.priceKg) on ? els.priceKg.removeAttribute('readonly') : els.priceKg.setAttribute('readonly', 'readonly');
    });
  }
  if (els.overrideSave) {
    els.overrideSave.addEventListener('click', function () {
      var price = num(els.overridePrice && els.overridePrice.value, 0);
      var reason = (els.overrideReason && els.overrideReason.value || '').trim();
      if (price <= 0) { alert('Enter a valid override price.'); return; }
      if (reason.length < 5) { alert('A short reason is required for the audit log.'); return; }
      var sel = { material: valStr(els.material), grade: valStr(els.grade), region: valStr(els.region), supplier: valStr(els.supplier) };
      fetch(API + '/material-price/override', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': (window.IH_NONCE || '') },
        body: JSON.stringify({ material: sel.material, grade: sel.grade, region: sel.region, supplier: sel.supplier, override_price_per_kg: price, reason: reason })
      })
        .then(function (r) { if (!r.ok) throw new Error('save failed'); return r.json(); })
        .then(function () { loadPrice(); })           // refetch so price_used reflects the override
        .catch(function () { alert('Could not save override. Check your permissions and try again.'); });
    });
  }

  /* ---------- 6. helpers ---------------------------------------------- */
  function q(name) { return root.querySelector('[data-pf="' + name + '"]'); }
  function val(e) { return e ? num(e.value, 0) : 0; }
  function valStr(e) { return e ? (e.value || '') : ''; }
  function set(e, v) { if (e) e.textContent = v; }
  function num(v, d) { var n = parseFloat(String(v).replace(/[, ]/g, '')); return isFinite(n) ? n : d; }
  function clamp(n, lo, hi) { return Math.min(hi, Math.max(lo, n)); }
  function enc(s) { return encodeURIComponent(s || ''); }
  function money(n, p) { return '£' + (n).toFixed(n < 1 ? 2 : (p ? 2 : 2)); }
  function moneyShort(n) {
    if (n >= 1e6) return '£' + (n / 1e6).toFixed(1) + 'M';
    if (n >= 1e3) return '£' + (n / 1e3).toFixed(1) + 'k';
    return '£' + n.toFixed(2);
  }
  function badge(kind, text) {
    if (!els.badge) return;
    els.badge.textContent = text;
    els.badge.className = 'ih-price-badge is-' + kind;   // style .is-live/.is-stale/.is-offline/.is-override/.is-manual/.is-loading
  }
  function isStale(iso) { if (!iso) return true; return (Date.now() - new Date(iso).getTime()) > STALE_AFTER_DAYS * 864e5; }
  function fmtDate(iso) { try { return new Date(iso).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); } catch (e) { return ''; } }
  function debounce(fn, ms) { var t; return function () { clearTimeout(t); t = setTimeout(fn, ms); }; }
  function cacheKeyFor(sel) { return CACHE_KEY + ':' + [sel.material, sel.grade, sel.region, sel.supplier].join('|'); }
  function cacheWrite(sel, v) { try { localStorage.setItem(cacheKeyFor(sel), JSON.stringify(v)); } catch (e) {} }
  function cacheRead(sel) { try { var s = localStorage.getItem(cacheKeyFor(sel)); return s ? JSON.parse(s) : null; } catch (e) { return null; } }

  /* ---------- 7. SELECTOR POPULATION (cascading) from /materials ------- */
  var CATALOGUE = [];   // raw rows: {material_family, material_name, grade, grade_code, region, supplier}

  function fillSelect(sel, values, current) {
    if (!sel) return;
    sel.innerHTML = '';
    values.forEach(function (v) {
      var o = document.createElement('option');
      o.value = v.value; o.textContent = v.label;
      if (current && v.value === current) o.selected = true;
      sel.appendChild(o);
    });
  }
  function uniq(arr) { return arr.filter(function (v, i) { return v != null && v !== '' && arr.indexOf(v) === i; }); }

  // material select uses grade_code as its value (that's what the API keys on)
  function cascade(keepGrade) {
    var mat = valStr(els.material);
    var rows = CATALOGUE.filter(function (r) { return r.grade_code === mat; });
    if (!rows.length) rows = CATALOGUE.slice();
    fillSelect(els.grade,    uniq(rows.map(function (r) { return r.grade; })).map(opt),    valStr(els.grade));
    fillSelect(els.region,   uniq(rows.map(function (r) { return r.region; })).map(opt),   valStr(els.region));
    fillSelect(els.supplier, uniq(rows.map(function (r) { return r.supplier; })).map(opt), valStr(els.supplier));
  }
  function opt(v) { return { value: v, label: v }; }

  function populateSelectors() {
    return fetch(API + '/materials', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : []; })
      .then(function (rows) {
        if (!Array.isArray(rows) || !rows.length) return false;
        CATALOGUE = rows;
        // de-dupe materials by grade_code for the Material dropdown
        var seen = {}, mats = [];
        rows.forEach(function (r) {
          if (r.grade_code && !seen[r.grade_code]) { seen[r.grade_code] = 1; mats.push({ value: r.grade_code, label: r.material_name || r.grade_code }); }
        });
        fillSelect(els.material, mats, valStr(els.material));
        cascade();
        return true;
      })
      .catch(function () { return false; });
  }

  // when material changes, recascade the dependent selectors before pricing
  if (els.material) els.material.addEventListener('change', function () { cascade(); });

  /* expose the pure functions for tests / other modules */
  window.IHCostCalc = { calculateQuote: calculateQuote, validateInputs: validateInputs, populateSelectors: populateSelectors, badgeForSource: badgeForSource };

  // initial load: build selectors from the catalogue, then price the default selection
  populateSelectors().then(function (ok) {
    if (ok || (els.material && els.material.value)) loadPrice(); else paint();
  });
})();
