/* ============================================================================
 * im-tool-detail.js — public Tool/Mould Detail interactivity (no-chart build).
 * Mirrors the Machine Detail behaviour (im-listing-detail.js) but for TOOLS and
 * with self-drawn SVG widgets instead of Chart.js (it is NOT enqueued here):
 *   - Gallery cross-fade + lightbox (focus-trapped, Esc/arrows)
 *   - Count-up + completeness meter on IntersectionObserver
 *   - Tooling calculator   (projected area / clamp / shot / press band)   ── §A
 *   - Machine-fit map       (projected area vs clamp tonnage, SVG scatter) ── §B
 *   - Production cycle ring  (SVG donut + throughput)                       ── §D
 * The Cost & order calculator (§C) now lives in the plugin's material-pricing.js
 * (REST resin pricing + platform fees); this script no longer owns cost logic.
 *   - Fav / Share / Print / Request modal / owner approve-deny / remove
 * Calculators seed from the listing's REAL specs (the #imtCalc data-seed JSON
 * + the seeded input values rendered server-side) and recompute "what-if"
 * outputs live. No live price feed exists — costs are clearly ESTIMATEs.
 * All animation respects prefers-reduced-motion. Reads window.TLD_DATA.
 * ========================================================================== */
(function () {
	'use strict';

	var DATA = window.TLD_DATA || {};
	var page = document.getElementById('imdPage');
	if (!page) { return; }

	var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var SVGNS = 'http://www.w3.org/2000/svg';

	function num(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }
	function $(sel, root) { return (root || document).querySelector(sel); }
	function $all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
	function el(id) { return document.getElementById(id); }
	function setText(id, v) { var e = el(id); if (e) { e.textContent = v; } }
	function nextPress(t) {
		var sizes = [50, 80, 100, 150, 200, 250, 300, 400, 500, 650, 800, 1000, 1300, 1600, 2000];
		for (var i = 0; i < sizes.length; i++) { if (sizes[i] >= t) { return sizes[i]; } }
		return Math.ceil(t / 100) * 100;
	}

	/* ── Gallery thumbnail cross-fade ─────────────────────────────────────── */
	var galleryIndex = 0;
	var gallerySrcs = [];
	(function gallery() {
		var cover = el('imdCover');
		var thumbs = $all('.imd-thumb');
		if (!cover) { return; }
		gallerySrcs = thumbs.length
			? thumbs.map(function (t) { return t.getAttribute('data-src'); }).filter(Boolean)
			: (cover.getAttribute('src') ? [cover.getAttribute('src')] : []);
		thumbs.forEach(function (t, i) {
			t.addEventListener('click', function () {
				var src = t.getAttribute('data-src');
				galleryIndex = i;
				if (!src || cover.src === src) { return; }
				if (REDUCED) { cover.src = src; }
				else { cover.style.opacity = '0'; setTimeout(function () { cover.src = src; cover.style.opacity = '1'; }, 180); }
				thumbs.forEach(function (o) { o.classList.remove('is-active'); o.setAttribute('aria-pressed', 'false'); });
				t.classList.add('is-active');
				t.setAttribute('aria-pressed', 'true');
			});
		});
	})();

	/* ── Image lightbox (Esc to close, focus-trapped, prev/next) ──────────── */
	(function lightbox() {
		var box = el('imdLightbox');
		var btn = el('imdCoverBtn');
		var img = el('imdLightboxImg');
		if (!box || !btn || !img || !gallerySrcs.length) { return; }
		var countEl = el('imdLbCount');
		var prevBtn = el('imdLbPrev');
		var nextBtn = el('imdLbNext');
		var returnFocus = null;

		function show(i) {
			galleryIndex = (i + gallerySrcs.length) % gallerySrcs.length;
			img.src = gallerySrcs[galleryIndex];
			if (countEl) { countEl.textContent = (galleryIndex + 1) + ' / ' + gallerySrcs.length; }
		}
		function focusables() {
			return $all('button:not([disabled])', box).filter(function (e) { return e.offsetWidth || e.offsetHeight || e.getClientRects().length; });
		}
		function open() {
			returnFocus = (document.activeElement && document.activeElement.focus) ? document.activeElement : null;
			show(galleryIndex);
			box.hidden = false;
			requestAnimationFrame(function () { box.classList.add('is-open'); });
			var f = box.querySelector('.imd-lightbox__x') || focusables()[0];
			if (f) { f.focus(); }
		}
		function close() {
			box.classList.remove('is-open');
			if (REDUCED) { box.hidden = true; } else { setTimeout(function () { box.hidden = true; }, 220); }
			if (returnFocus && returnFocus.focus) { returnFocus.focus(); }
			returnFocus = null;
		}

		btn.addEventListener('click', function () { galleryIndex = num(btn.getAttribute('data-index')) || galleryIndex; open(); });
		if (prevBtn) { prevBtn.addEventListener('click', function () { show(galleryIndex - 1); }); }
		if (nextBtn) { nextBtn.addEventListener('click', function () { show(galleryIndex + 1); }); }
		box.addEventListener('click', function (e) { if (e.target.closest('[data-lbclose]')) { close(); } });
		document.addEventListener('keydown', function (e) {
			if (box.hidden) { return; }
			if (e.key === 'Escape') { e.preventDefault(); close(); return; }
			if (e.key === 'ArrowLeft' && gallerySrcs.length > 1) { e.preventDefault(); show(galleryIndex - 1); return; }
			if (e.key === 'ArrowRight' && gallerySrcs.length > 1) { e.preventDefault(); show(galleryIndex + 1); return; }
			if (e.key !== 'Tab') { return; }
			var f = focusables();
			if (!f.length) { return; }
			var first = f[0], last = f[f.length - 1];
			if (!box.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
			else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
		});
	})();

	/* ── Count-up numbers + completeness meter (IntersectionObserver) ─────── */
	function countUp(e) {
		var raw = e.getAttribute('data-to') || '0';
		var target = parseFloat(raw);
		if (isNaN(target)) { e.textContent = raw; return; }
		var decimals = (raw.indexOf('.') !== -1) ? (raw.split('.')[1].length) : 0;
		if (REDUCED || target === 0) { e.textContent = target.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }); return; }
		var start = null, dur = 900;
		function step(ts) {
			if (start === null) { start = ts; }
			var p = Math.min(1, (ts - start) / dur);
			var eased = 1 - Math.pow(1 - p, 3);
			e.textContent = (target * eased).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
			if (p < 1) { requestAnimationFrame(step); }
			else { e.textContent = target.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }); }
		}
		requestAnimationFrame(step);
	}
	function revealEl(e) {
		$all('.imd-count', e).forEach(countUp);
		var meter = $('.imd-meter__fill', e);
		if (meter) { meter.classList.add('is-animated'); }
	}
	(function observe() {
		var targets = $all('.imd-sec, .imd-summary');
		if (!('IntersectionObserver' in window)) { targets.forEach(revealEl); return; }
		var io = new IntersectionObserver(function (entries, obs) {
			entries.forEach(function (e) { if (e.isIntersecting) { revealEl(e.target); obs.unobserve(e.target); } });
		}, { threshold: 0.18 });
		targets.forEach(function (t) { io.observe(t); });
	})();

	/* ═══════════════════════════════════════════════════════════════════════
	 * Engineering / cost estimators — seeded from the listing's real specs.
	 * ═════════════════════════════════════════════════════════════════════ */
	var SEED = {};
	(function readSeed() {
		var host = el('imtCalc');
		if (!host) { return; }
		try { SEED = JSON.parse(host.getAttribute('data-seed') || '{}') || {}; } catch (e) { SEED = {}; }
	})();

	/* Selected material clamp factor (kp) + density from the calculator dropdown. */
	function matFactors() {
		var sel = el('imtMat');
		var kp = 0.30, density = 1.0;
		if (sel && sel.options[sel.selectedIndex]) {
			var o = sel.options[sel.selectedIndex];
			kp = num(o.getAttribute('data-kp')) || 0.30;
			density = num(o.getAttribute('data-density')) || 1.0;
		}
		return { kp: kp, density: density };
	}

	var calcOut = { area: 0, clamp: 0, totalShot: 0 };

	/* ── §A Tooling calculator ────────────────────────────────────────────── */
	function computeCalc() {
		if (!el('imtCalc')) { return; }
		var L = num(el('imtPartL') && el('imtPartL').value);
		var W = num(el('imtPartW') && el('imtPartW').value);
		var D = num(el('imtPartD') && el('imtPartD').value);
		var wall = num(el('imtWall') && el('imtWall').value) || 2.0;
		var cav = Math.max(1, num(el('imtCav') && el('imtCav').value) || 1);
		var f = matFactors();

		/* Projected area per cavity (cm²) from the bounding box; if no part
		 * geometry is entered, fall back to the listing's stored projected_area. */
		var areaPer = (L > 0 && W > 0) ? (L * W) / 100 : 0;
		if (areaPer <= 0 && num(SEED.projArea) > 0) { areaPer = num(SEED.projArea) / cav; }
		var areaTotal = areaPer * cav;

		var clamp = Math.ceil(areaTotal * f.kp * 1.2);                 // T, incl. 1.2 safety
		var shotPer = areaPer * (wall / 10) * f.density;               // g/cavity (flat-part)
		var totalShot = (shotPer * cav) * 1.05;                        // + 5% cushion/runner
		var minOpen = D > 0 ? Math.round(D * 2 + 50) : 0;              // daylight to eject
		var pressLo = Math.round(clamp * 1.1 / 10) * 10;
		var pressHi = Math.round(clamp * 1.25 / 10) * 10;
		var shotCap = num(SEED.shotWeight) > 0 ? num(SEED.shotWeight) : 150;
		var shotUse = shotCap > 0 ? Math.round(totalShot / shotCap * 100) : 0;

		setText('imtProjArea', Math.round(areaTotal).toLocaleString());
		setText('imtReqClamp', clamp ? clamp.toLocaleString() : '—');
		setText('imtShotCav', shotPer > 0 ? Math.round(shotPer) : '—');
		setText('imtTotalShot', totalShot > 0 ? Math.round(totalShot) : '—');
		setText('imtMinOpen', minOpen || '—');
		setText('imtPressLo', pressLo || '—');
		setText('imtPressHi', pressHi || '—');
		setText('imtShotUse', shotUse || '—');
		setText('imtShotUseRef', '% of ' + Math.round(shotCap) + 'g');

		calcOut = { area: areaTotal, clamp: clamp, totalShot: totalShot, minOpen: minOpen };
		drawFitMap();
		renderMachineReq();
	}

	/* ── §B Machine-fit map (SVG scatter / quadrant) ──────────────────────── */
	function drawFitMap() {
		var host = el('imtFitMap');
		if (!host) { return; }
		var f = matFactors();
		var area = calcOut.area, clamp = calcOut.clamp;
		var pressTon = nextPress(clamp || 50);
		var maxArea = (f.kp * 1.2) > 0 ? pressTon / (f.kp * 1.2) : area * 1.5;   // envelope width

		var W = 680, H = 320, padL = 56, padB = 40, padT = 16, padR = 16;
		var plotW = W - padL - padR, plotH = H - padT - padB;
		var xMax = Math.max(maxArea, area) * 1.18 || 100;
		var yMax = Math.max(pressTon, clamp) * 1.2 || 100;
		function px(x) { return padL + (x / xMax) * plotW; }
		function py(y) { return padT + plotH - (y / yMax) * plotH; }

		var inside = (area <= maxArea && clamp <= pressTon && clamp > 0);
		var s = '<svg viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="xMidYMid meet" role="presentation">';
		// gridlines
		var i;
		for (i = 1; i <= 4; i++) {
			var gx = padL + (plotW / 4) * i, gy = padT + (plotH / 4) * i;
			s += '<line x1="' + gx + '" y1="' + padT + '" x2="' + gx + '" y2="' + (padT + plotH) + '" stroke="#e6e9f2" stroke-width="1" stroke-dasharray="3 4"/>';
			s += '<line x1="' + padL + '" y1="' + gy + '" x2="' + (padL + plotW) + '" y2="' + gy + '" stroke="#e6e9f2" stroke-width="1" stroke-dasharray="3 4"/>';
		}
		// envelope rectangle (0,0)->(maxArea, pressTon)
		var ex = px(0), ey = py(pressTon), ew = px(maxArea) - px(0), eh = py(0) - py(pressTon);
		s += '<rect x="' + ex + '" y="' + ey + '" width="' + Math.max(0, ew) + '" height="' + Math.max(0, eh) + '" fill="rgba(22,163,74,.12)" stroke="#16a34a" stroke-width="1.5" stroke-dasharray="6 4" rx="4"/>';
		s += '<text x="' + (ex + 8) + '" y="' + (ey + 16) + '" font-family="IBM Plex Mono,monospace" font-size="11" fill="#15803d" font-weight="600">Your press · ' + pressTon + ' T</text>';
		// axes
		s += '<line x1="' + padL + '" y1="' + padT + '" x2="' + padL + '" y2="' + (padT + plotH) + '" stroke="#9aa0b4" stroke-width="1.5"/>';
		s += '<line x1="' + padL + '" y1="' + (padT + plotH) + '" x2="' + (padL + plotW) + '" y2="' + (padT + plotH) + '" stroke="#9aa0b4" stroke-width="1.5"/>';
		// axis ticks
		for (i = 0; i <= 4; i++) {
			var tvx = Math.round(xMax / 4 * i), tvy = Math.round(yMax / 4 * i);
			s += '<text x="' + px(tvx) + '" y="' + (padT + plotH + 16) + '" font-size="10" fill="#9aa0b4" text-anchor="middle" font-family="IBM Plex Mono,monospace">' + tvx + '</text>';
			s += '<text x="' + (padL - 8) + '" y="' + (py(tvy) + 3) + '" font-size="10" fill="#9aa0b4" text-anchor="end" font-family="IBM Plex Mono,monospace">' + tvy + '</text>';
		}
		// axis labels
		s += '<text x="' + (padL + plotW / 2) + '" y="' + (H - 4) + '" font-size="10.5" fill="#6b7185" text-anchor="middle" font-family="IBM Plex Mono,monospace" letter-spacing="0.5">PROJECTED AREA (cm²)</text>';
		s += '<text transform="translate(13 ' + (padT + plotH / 2) + ') rotate(-90)" font-size="10.5" fill="#6b7185" text-anchor="middle" font-family="IBM Plex Mono,monospace" letter-spacing="0.5">CLAMP TONNAGE (T)</text>';
		// tool point
		if (area > 0 && clamp > 0) {
			var cx = px(area), cy = py(clamp);
			var col = inside ? '#5347ce' : '#ef4444';
			s += '<circle cx="' + cx + '" cy="' + cy + '" r="9" fill="' + col + '" fill-opacity="0.18"/>';
			s += '<circle cx="' + cx + '" cy="' + cy + '" r="5" fill="' + col + '"/>';
			s += '<text x="' + (cx + 11) + '" y="' + (cy - 8) + '" font-size="11" fill="' + col + '" font-weight="700" font-family="IBM Plex Mono,monospace">This tool · ' + Math.round(area) + ' cm² · ' + clamp + ' T</text>';
		}
		s += '</svg>';
		host.innerHTML = s;

		var note = el('imtFitNote');
		if (note) {
			if (area > 0 && clamp > 0) {
				if (inside) {
					var head = Math.round((pressTon - clamp) / pressTon * 100);
					note.className = 'imt-note imt-note--ok';
					note.innerHTML = '<b>Sits inside the ' + pressTon + ' T envelope</b> — this press runs it with ~' + head + '% tonnage headroom.' +
						'<span class="imt-note__sub">Part needs ~' + clamp + ' T; press delivers ' + pressTon + ' T. Projected area ' + Math.round(area) + ' cm².</span>';
				} else {
					note.className = 'imt-note imt-note--warn';
					note.innerHTML = '<b>Needs a larger press</b> — ~' + clamp + ' T required for ' + Math.round(area) + ' cm². Step up beyond the ' + pressTon + ' T envelope.';
				}
			} else {
				note.className = 'imt-note imt-note--hint';
				note.textContent = 'Enter part dimensions in the calculator above to plot this tool against a press envelope.';
			}
		}
	}

	/* Machine-requirement check chips.
	 * These sit in the "Tool features & requirements" section and represent the
	 * tool's ACTUAL machine requirements, so they must match the owner's LISTED
	 * specs: shot weight (g), opening stroke / daylight (mm), clamp force (T) and
	 * tie-bar. We seed each chip from the entered value (SEED.*) and fall back to
	 * the calculator's live engineering estimate ONLY when a listed value is
	 * absent — never overwriting or contradicting the listed figure. Units stay
	 * g (shot) and mm (daylight) throughout. */
	function renderMachineReq() {
		var wrap = el('imtMachineReq');
		if (!wrap) { return; }
		var chips = [];
		var daylight = num(SEED.opening) > 0 ? num(SEED.opening) : calcOut.minOpen;
		if (daylight > 0) { chips.push('Daylight ≥ ' + Math.round(daylight) + ' mm'); }
		if (SEED.tieBar) { chips.push('Tie-bar ≥ ' + SEED.tieBar); }
		var shot = num(SEED.shotWeight) > 0 ? num(SEED.shotWeight) : calcOut.totalShot;
		if (shot > 0) { chips.push('Shot ≥ ' + Math.round(shot) + ' g'); }
		var clamp = num(SEED.clampForce) > 0 ? num(SEED.clampForce) : calcOut.clamp;
		if (clamp > 0) { chips.push('Clamp ≥ ' + Math.round(clamp) + ' T'); }
		var check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-9"/></svg>';
		wrap.innerHTML = chips.map(function (c) { return '<span class="imd-chip imd-chip--ok">' + check + c + '</span>'; }).join('');
	}

	/* ── §C Cost & order calculator ───────────────────────────────────────────
	 * MOVED to the plugin's js/material-pricing.js, which binds to the
	 * [data-ih-cost-calc] root + data-pf hooks on this page and prices off the
	 * REST resin-pricing backend (incl. the platform service charge / transaction
	 * fee → grand total). This module no longer owns any cost/order logic. */

	/* ── §D Production cycle ring (SVG donut) + throughput ─────────────────── */
	var CYCLE_SPLIT = [0.12, 0.18, 0.5, 0.2];
	var CYCLE_COLORS = ['#5347ce', '#0fd6c2', '#4896fe', '#e07a16'];
	function donut(svg, segments) {
		var r = 46, cx = 60, cy = 60, C = 2 * Math.PI * r;
		var out = '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="#f4f6fb" stroke-width="14"/>';
		var offset = 0;
		segments.forEach(function (frac, i) {
			var len = Math.max(0, frac) * C;
			out += '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '" fill="none" stroke="' + CYCLE_COLORS[i] +
				'" stroke-width="14" stroke-dasharray="' + len + ' ' + (C - len) + '" stroke-dashoffset="' + (-offset) + '"/>';
			offset += len;
		});
		svg.innerHTML = out;
	}
	function computeCycle() {
		if (!el('imtCycle')) { return; }
		/* Cycle time now lives in the (plugin-owned) cost calculator; seed this
		 * standalone throughput ring from the listing's real cycle spec instead. */
		var cycle = num(SEED.cycle) || 30;
		var cav = Math.max(1, num(el('imtCav') && el('imtCav').value) || num(SEED.cavities) || 1);
		var hours = 16, util = 0.72;
		var svg = el('imtCycleSvg');
		if (svg) { donut(svg, CYCLE_SPLIT); }
		setText('imtCycleSecs', Math.round(cycle));
		var pph = cycle > 0 ? (3600 / cycle * cav) : 0;
		var perDay = Math.round(pph * hours * util);
		var perYear = perDay * 250;
		setText('imtPerDay', perDay > 0 ? perDay.toLocaleString() : '—');
		if (perYear >= 1e6) { setText('imtPerYear', '~ ' + (Math.round(perYear / 1e5) / 10) + ' M'); }
		else if (perYear > 0) { setText('imtPerYear', '~ ' + perYear.toLocaleString()); }
		else { setText('imtPerYear', '—'); }
	}

	/* ── Wiring: recompute on input; explicit buttons recompute everything ───
	 * Cost/order calculator wiring lives in material-pricing.js now; this only
	 * drives the Tooling calculator (§A), Machine-fit map (§B) and cycle ring (§D). */
	function recomputeAll() { computeCalc(); computeCycle(); }

	$all('#imtCalc input, #imtCalc select').forEach(function (i) {
		i.addEventListener('input', function () { computeCalc(); computeCycle(); });
		i.addEventListener('change', function () { computeCalc(); computeCycle(); });
	});
	(function buttons() {
		var c = el('imtCalcBtn'); if (c) { c.addEventListener('click', recomputeAll); }
		var r = el('imtCycleReset'); if (r) { r.addEventListener('click', computeCycle); }
	})();

	/* Boot the estimators with the seeded values. */
	recomputeAll();

	/* ── Favourite (Save) toggle + Share + Print ──────────────────────────── */
	(function fav() {
		var btn = el('imdFav');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			if (!DATA.loggedIn) { window.location.href = DATA.loginUrl; return; }
			var fd = new FormData();
			fd.append('action', 'ih_toggle_wishlist');
			fd.append('nonce', DATA.nonce);
			fd.append('listing_id', btn.getAttribute('data-id'));
			fd.append('listing_type', 'tool');
			fd.append('listing_title', btn.getAttribute('data-title') || '');
			fd.append('listing_image', btn.getAttribute('data-image') || '');
			btn.disabled = true;
			fetch(DATA.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (d) {
					if (d && d.success) {
						var on = !!(d.data && d.data.saved);
						btn.classList.toggle('is-on', on);
						btn.setAttribute('aria-pressed', on ? 'true' : 'false');
						var lbl = btn.querySelector('span');
						if (lbl) { lbl.textContent = on ? 'Saved' : 'Save'; }
					}
				})
				.catch(function () {})
				.finally(function () { btn.disabled = false; });
		});
	})();

	(function share() {
		var btn = el('imdShare');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			var url = btn.getAttribute('data-url') || window.location.href;
			var title = btn.getAttribute('data-title') || document.title;
			if (navigator.share) {
				navigator.share({ title: title, url: url }).catch(function () {});
			} else if (navigator.clipboard) {
				navigator.clipboard.writeText(url).then(function () {
					var lbl = btn.querySelector('span'); if (!lbl) { return; }
					var orig = lbl.textContent; lbl.textContent = 'Link copied'; setTimeout(function () { lbl.textContent = orig; }, 1400);
				}).catch(function () {});
			}
		});
	})();

	(function printSheet() {
		$all('#imdPrint, #imdPrintTop').forEach(function (btn) {
			btn.addEventListener('click', function () { window.print(); });
		});
	})();

	/* ── Modal helpers (shared with request modal) ────────────────────────── */
	var modalReturnFocus = null;
	function modalFocusable(modal) {
		return $all('a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])', modal)
			.filter(function (e) { return e.offsetWidth || e.offsetHeight || e.getClientRects().length; });
	}
	function openModal(modal) {
		if (!modal) { return; }
		modalReturnFocus = (document.activeElement && typeof document.activeElement.focus === 'function') ? document.activeElement : null;
		modal.hidden = false;
		requestAnimationFrame(function () { modal.classList.add('is-open'); });
		var target = modal.querySelector('input, textarea, select') || modalFocusable(modal)[0];
		if (target) { target.focus(); }
	}
	function closeModal(modal) {
		if (!modal) { return; }
		modal.classList.remove('is-open');
		if (REDUCED) { modal.hidden = true; } else { setTimeout(function () { modal.hidden = true; }, 220); }
		if (modalReturnFocus && typeof modalReturnFocus.focus === 'function') { modalReturnFocus.focus(); }
		modalReturnFocus = null;
	}
	$all('.imd-modal').forEach(function (modal) {
		modal.addEventListener('click', function (e) { if (e.target.closest('[data-close]')) { closeModal(modal); } });
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') { $all('.imd-modal.is-open').forEach(closeModal); return; }
		if (e.key !== 'Tab') { return; }
		var modal = document.querySelector('.imd-modal.is-open');
		if (!modal) { return; }
		var f = modalFocusable(modal);
		if (!f.length) { return; }
		var first = f[0], last = f[f.length - 1];
		if (!modal.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
		else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
		else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
	});

	/* ── Request details flow -> ih_request_listing_details (listing_type=tool) ── */
	var reqModal = el('imdModal');
	function triggerRequest() {
		if (!DATA.loggedIn) { window.location.href = DATA.loginUrl; return; }
		if (reqModal) { openModal(reqModal); }
	}
	var rb = el('imdRequestBtn');
	var rbm = el('imdRequestBtnMobile');
	if (rb) { rb.addEventListener('click', triggerRequest); }
	if (rbm) { rbm.addEventListener('click', triggerRequest); }

	var confirmBtn = el('imdModalConfirm');
	if (confirmBtn) {
		confirmBtn.addEventListener('click', function () {
			var fb = el('imdModalFeedback');
			var msgEl = el('imdReqMessage');
			confirmBtn.disabled = true;
			var span = confirmBtn.querySelector('span');
			var orig = span ? span.textContent : '';
			if (span) { span.textContent = 'Sending…'; }
			var fd = new FormData();
			fd.append('action', 'ih_request_listing_details');
			fd.append('nonce', DATA.nonce);
			fd.append('listing_id', DATA.listingId);
			fd.append('listing_type', 'tool');
			if (msgEl) { fd.append('message', msgEl.value || ''); }
			fetch(DATA.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (d) {
					if (d && d.success) {
						if (fb) { fb.className = 'imd-modal__feedback is-ok'; fb.textContent = '✓ ' + ((d.data && d.data.message) || 'Request sent · awaiting approval'); }
						updateRequestedState();
						setTimeout(function () { closeModal(reqModal); }, 1100);
					} else {
						if (fb) { fb.className = 'imd-modal__feedback is-err'; fb.textContent = '⚠ ' + ((d && d.data && (d.data.message || d.data)) || 'Something went wrong.'); }
						if (d && d.data && d.data.login_required) { window.location.href = DATA.loginUrl; }
						confirmBtn.disabled = false;
						if (span) { span.textContent = orig; }
					}
				})
				.catch(function () {
					if (fb) { fb.className = 'imd-modal__feedback is-err'; fb.textContent = '⚠ Network error. Please try again.'; }
					confirmBtn.disabled = false;
					if (span) { span.textContent = orig; }
				});
		});
	}

	function updateRequestedState() {
		var cta = el('imdContact');
		if (cta) {
			var btnEl = el('imdRequestBtn');
			if (btnEl) {
				var pend = document.createElement('div');
				pend.className = 'imd-cta__pending';
				pend.id = 'imdReqState';
				pend.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/></svg> <span>Request sent · awaiting approval</span>';
				btnEl.parentNode.replaceChild(pend, btnEl);
			}
		}
		if (rbm) {
			var clone = rbm.cloneNode(false);
			clone.className = 'imd-btn imd-btn--primary is-static';
			clone.textContent = 'Awaiting approval';
			clone.disabled = true;
			if (rbm.parentNode) { rbm.parentNode.replaceChild(clone, rbm); }
		}
	}

	/* ── Owner / admin approve · deny -> ih_owner_request_action ───────────── */
	(function ownerActions() {
		var wrap = el('imdOwnerReqs');
		if (!wrap) { return; }
		wrap.addEventListener('click', function (e) {
			var btn = e.target.closest('.imd-oreq__act');
			if (!btn) { return; }
			var reqId = btn.getAttribute('data-req');
			var doAct = btn.getAttribute('data-do');
			var row = btn.closest('.imd-oreq');
			$all('.imd-oreq__act', row).forEach(function (b) { b.disabled = true; });
			var fd = new FormData();
			fd.append('action', 'ih_owner_request_action');
			fd.append('nonce', DATA.nonce);
			fd.append('request_id', reqId);
			fd.append('do', doAct);
			fetch(DATA.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (d) {
					if (d && d.success) {
						row.innerHTML = '<p class="imd-oreq__done">' + (doAct === 'approve' ? '✓ Approved' : '✕ Denied') + '</p>';
					} else {
						$all('.imd-oreq__act', row).forEach(function (b) { b.disabled = false; });
						var p = document.createElement('p');
						p.className = 'imd-oreq__err';
						p.textContent = (d && d.data && (d.data.message || d.data)) || 'Action failed.';
						row.appendChild(p);
					}
				})
				.catch(function () { $all('.imd-oreq__act', row).forEach(function (b) { b.disabled = false; }); });
		});
	})();

	/* ── Remove listing. Admin -> ih_delete_tool. Owner -> ih_owner_delete_tool ── */
	(function removeListing() {
		var btn = el('imdRemoveBtn');
		if (!btn) { return; }
		var isOwner = btn.getAttribute('data-owner') === '1';
		var action = isOwner ? 'ih_owner_delete_tool' : 'ih_delete_tool';
		btn.addEventListener('click', function () {
			if (!window.confirm('Remove this tool listing permanently? This cannot be undone.')) { return; }
			btn.disabled = true;
			var fd = new FormData();
			fd.append('action', action);
			fd.append('nonce', DATA.nonce);
			fd.append('id', btn.getAttribute('data-id'));
			fd.append('tool_id', btn.getAttribute('data-id'));
			fetch(DATA.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (d) {
					if (d && d.success) { window.location.href = isOwner ? (DATA.dashboardUrl || '/tools/') : (DATA.browseUrl || '/tools/'); }
					else { btn.disabled = false; window.alert((d && d.data && (d.data.message || d.data)) || 'Could not remove the listing.'); }
				})
				.catch(function () { btn.disabled = false; window.alert('Network error. Please try again.'); });
		});
	})();

})();

/* Clear the fixed site header so the breadcrumb + gallery are never hidden.
 * Mirrors the navOffset routine from im-listing-detail.js (standalone IIFE so a
 * runtime error elsewhere can never prevent the offset from being applied). */
(function navOffset() {
	if (!document.getElementById('imdPage')) { return; }
	var GAP = 14;
	function headerBottom() {
		var header = document.querySelector('header.fixed') || document.querySelector('header');
		if (!header) { return 0; }
		var measured;
		if (window.innerWidth < 1024) {
			var topbar = header.querySelector('.im-public-header-top') || header;
			measured = topbar.getBoundingClientRect().bottom;
		} else {
			measured = header.getBoundingClientRect().bottom;
		}
		var ab = document.getElementById('wpadminbar');
		if (ab) {
			var abb = ab.getBoundingClientRect().bottom;
			if (abb > measured) { measured = abb; }
		}
		return measured;
	}
	function apply() {
		var px = headerBottom();
		if (px > 0) { document.documentElement.style.setProperty('--im-header-h', Math.round(px + GAP) + 'px'); }
	}
	apply();
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', apply); }
	window.addEventListener('load', apply);
	window.addEventListener('orientationchange', apply);
	var t = null;
	window.addEventListener('resize', function () { if (t) { clearTimeout(t); } t = setTimeout(apply, 120); });
	if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
		document.fonts.ready.then(apply);
	}
	setTimeout(apply, 150);
	setTimeout(apply, 400);
	setTimeout(apply, 900);
})();
