/* ============================================================================
 * im-listing-detail.js — public Machine Detail interactivity (Chart.js build).
 * Enqueued ONLY on the Machine Detail template, AFTER chart.umd.min.js +
 * chartjs-plugin-dragdata.min.js (registered via WP script deps). Reads
 * window.IMD_DATA (localized in functions.php).
 *
 * Contents:
 *   - Gallery thumbnail cross-fade
 *   - Count-up numbers + completeness meter (IntersectionObserver)
 *   - Chart.js capacity gauges (270° doughnuts) + production-cycle doughnut
 *   - Chart.js capability radar (6 axes) — machine fixed, requirement DRAGGABLE
 *     via chartjs-plugin-dragdata; live recompute of match badge + meets/gaps
 *     box; keyboard-accessible number steppers as a drag equivalent
 *   - Interactive spec popovers (vs typical listed presses + percentile)
 *   - Pure functions matchScore(listing, req) and fits(listing, mould)  (§15b)
 *   - Favourite (Save) toggle + Share + Request-details modal + requirement save
 * All animation respects prefers-reduced-motion.
 * ========================================================================== */
(function () {
	'use strict';

	var DATA = window.IMD_DATA || {};
	var page = document.getElementById('imdPage');
	if (!page) { return; }

	var REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var HAS_CHART = (typeof window.Chart !== 'undefined');
	var listing = DATA.listing || {};
	var axes = Array.isArray(DATA.axes) ? DATA.axes : [];
	var specStats = DATA.specStats || {};
	var req = DATA.requirement || null;   // saved job profile (or null)
	/* Canonical automation levels (mirrors the Add Machine form). Each label maps
	 * to the normalised 0..1 score used by the radar's automation axis. */
	var AUTOMATION_OPTIONS = Array.isArray(DATA.automationOptions) && DATA.automationOptions.length
		? DATA.automationOptions
		: [{ label: 'Manual', value: 0 }, { label: 'Semi-automated', value: 0.5 }, { label: 'Fully automated', value: 0.85 }];

	/* Normalised automation score -> nearest canonical wording (display only). */
	function automationWord(norm) {
		norm = clamp01(num(norm));
		var best = AUTOMATION_OPTIONS[0], bestD = Infinity;
		AUTOMATION_OPTIONS.forEach(function (o) {
			var d = Math.abs(num(o.value) - norm);
			if (d < bestD) { bestD = d; best = o; }
		});
		return best ? best.label : '';
	}

	function num(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }
	function clamp01(v) { return Math.max(0, Math.min(1, v)); }
	function $(sel, root) { return (root || document).querySelector(sel); }
	function $all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
	function debounce(fn, ms) {
		var t;
		return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); };
	}
	function cssVar(name, fallback) {
		var v = getComputedStyle(page).getPropertyValue(name);
		return (v && v.trim()) ? v.trim() : fallback;
	}
	function fmt(n) {
		n = num(n);
		var r = (n === Math.round(n)) ? n : Math.round(n * 10) / 10;
		return r.toLocaleString();
	}

	var COLORS = {
		brand: cssVar('--imd-brand', '#5347ce'),
		accent: cssVar('--imd-accent', '#887cfd'),
		accentSoft: cssVar('--imd-accent-soft', '#ece9ff'),
		success: cssVar('--imd-success', '#16a34a'),
		warning: cssVar('--imd-warning', '#f59e0b'),
		track: cssVar('--imd-surface-2', '#f4f6fb'),
		border: cssVar('--imd-border', '#e6e9f2'),
		ink: cssVar('--imd-ink', '#1a1c2b'),
		muted: cssVar('--imd-muted', '#9aa0b4'),
		teal: '#11b4a6'
	};

	var GAUGE_COLORS = { clamp: COLORS.brand, shot: COLORS.accent, screw: '#4896fe', util: COLORS.success };

	if (HAS_CHART) {
		window.Chart.defaults.font.family = "'Montserrat',-apple-system,system-ui,sans-serif";
		window.Chart.defaults.color = COLORS.muted;
		if (REDUCED) { window.Chart.defaults.animation = false; }
	}

	/* Shared tooltip styling for the charts (native Chart.js tooltip). */
	function tooltipStyle() {
		return {
			backgroundColor: '#fff',
			titleColor: COLORS.ink,
			bodyColor: cssVar('--imd-text-2', '#6b7185'),
			borderColor: COLORS.border,
			borderWidth: 1,
			padding: 12,
			cornerRadius: 12,
			displayColors: false,
			titleFont: { weight: '700', size: 12 },
			bodyFont: { size: 12 }
		};
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * Gallery
	 * ─────────────────────────────────────────────────────────────────── */
	var galleryIndex = 0;     // shared with the lightbox
	var gallerySrcs = [];
	(function gallery() {
		var cover = document.getElementById('imdCover');
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

	/* ── Image lightbox (Esc to close, focus-trapped, prev/next, reduced-motion) ── */
	(function lightbox() {
		var box = document.getElementById('imdLightbox');
		var btn = document.getElementById('imdCoverBtn');
		var img = document.getElementById('imdLightboxImg');
		if (!box || !btn || !img || !gallerySrcs.length) { return; }
		var countEl = document.getElementById('imdLbCount');
		var prevBtn = document.getElementById('imdLbPrev');
		var nextBtn = document.getElementById('imdLbNext');
		var returnFocus = null;

		function show(i) {
			galleryIndex = (i + gallerySrcs.length) % gallerySrcs.length;
			img.src = gallerySrcs[galleryIndex];
			if (countEl) { countEl.textContent = (galleryIndex + 1) + ' / ' + gallerySrcs.length; }
		}
		function focusables() {
			return $all('button:not([disabled])', box).filter(function (el) { return el.offsetWidth || el.offsetHeight || el.getClientRects().length; });
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

	/* ─────────────────────────────────────────────────────────────────────
	 * Count-up animation
	 * ─────────────────────────────────────────────────────────────────── */
	function countUp(el) {
		var raw = el.getAttribute('data-to') || '0';
		var target = parseFloat(raw);
		if (isNaN(target)) { el.textContent = raw; return; }
		var decimals = (raw.indexOf('.') !== -1) ? (raw.split('.')[1].length) : 0;
		if (REDUCED || target === 0) { el.textContent = target.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }); return; }
		var start = null, dur = 900;
		function step(ts) {
			if (start === null) { start = ts; }
			var p = Math.min(1, (ts - start) / dur);
			var eased = 1 - Math.pow(1 - p, 3);
			var val = target * eased;
			el.textContent = val.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
			if (p < 1) { requestAnimationFrame(step); }
			else { el.textContent = target.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }); }
		}
		requestAnimationFrame(step);
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * Capacity gauges (Chart.js 270° doughnuts)
	 * ─────────────────────────────────────────────────────────────────── */
	function buildGauges() {
		if (!HAS_CHART) { return; }
		$all('.imd-gauge').forEach(function (g) {
			var canvas = $('canvas', g);
			if (!canvas) { return; }
			var key = g.getAttribute('data-gauge');
			var val = num(g.getAttribute('data-value'));
			var max = num(g.getAttribute('data-max')) || 1;
			var unit = g.getAttribute('data-unit') || '';
			var label = g.getAttribute('data-label') || '';
			var pct = clamp01(val / max) * 100;
			var col = GAUGE_COLORS[key] || COLORS.brand;
			new window.Chart(canvas, {
				type: 'doughnut',
				data: {
					labels: [label, ''],
					datasets: [{
						data: [pct, 100 - pct],
						backgroundColor: [col, COLORS.track],
						borderWidth: 0,
						circumference: 270,
						rotation: -135
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					cutout: '74%',
					animation: REDUCED ? false : { animateRotate: true, duration: 900 },
					plugins: {
						legend: { display: false },
						tooltip: Object.assign(tooltipStyle(), {
							callbacks: {
								label: function () {
									return fmt(val) + ' ' + unit + ' · ' + Math.round(pct) + '% of ' + fmt(max) + ' ' + unit;
								},
								title: function () { return label; }
							},
							filter: function (item) { return item.dataIndex === 0; }
						})
					}
				}
			});
		});
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * Production cycle doughnut + derived throughput
	 * ─────────────────────────────────────────────────────────────────── */
	function buildCycle() {
		var canvas = document.getElementById('imdCycleChart');
		var tp = throughput(listing);
		var cycle = tp.cycle, split = tp.split, secs = tp.secs;
		var pd = document.getElementById('imdPerDay');
		var py = document.getElementById('imdPerYear');
		if (pd) { pd.setAttribute('data-to', String(tp.perDay)); }
		if (py) {
			if (tp.perYear >= 1e6) { py.textContent = '~ ' + (tp.perYear / 1e6).toFixed(1) + ' M'; }
			else if (tp.perYear > 0) { py.textContent = '~ ' + tp.perYear.toLocaleString(); }
			else { py.textContent = '—'; }
		}

		if (!HAS_CHART || !canvas) { return; }
		var stages = ['Clamp', 'Inject', 'Cool', 'Eject'];
		new window.Chart(canvas, {
			type: 'doughnut',
			data: {
				labels: stages,
				datasets: [{
					data: split.map(function (s) { return s * 100; }),
					backgroundColor: [COLORS.brand, COLORS.accent, '#4896fe', '#e07a16'],
					borderColor: '#fff',
					borderWidth: 2
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '72%',
				animation: REDUCED ? false : { animateRotate: true, duration: 900 },
				plugins: {
					legend: { display: false },
					tooltip: Object.assign(tooltipStyle(), {
						callbacks: {
							title: function (items) { return items[0].label; },
							label: function (item) {
								var pct = Math.round(split[item.dataIndex] * 100);
								return (cycle > 0 ? secs[item.dataIndex] + ' s · ' : '') + pct + '% of cycle';
							}
						}
					})
				}
			}
		});
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * PURE FUNCTIONS — matchScore + fits  (§15b weights) — preserved
	 * ─────────────────────────────────────────────────────────────────── */
	/* Effective required clamp tonnage for a job. When the job supplies part geometry
	 * — projected_area(cm²) × cavity_pressure(bar) — we derive tonnage from physics
	 * (see clampForceTonnes), which is far more reliable than a hand-typed figure.
	 * Falls back to the manually entered required tonnage (R.tonnage) when either
	 * field is empty, so legacy job profiles keep working unchanged. projected_area is
	 * the TOTAL across all cavities, hence cavities = 1 in the formula. */
	function effectiveTonnage(R) {
		var derived = clampForceTonnes(R.projectedArea, R.cavityPressure, 1, 1.1);
		return derived > 0 ? derived : num(R.tonnage);
	}

	function matchScore(L, R) {
		var parts = [];
		var score = 0;
		var clamp = num(L.clamp), shot = num(L.shot), monthly = num(L.monthly);
		var rTon = effectiveTonnage(R), rShot = num(R.shot), rVol = num(R.volume);

		var clampOk = rTon > 0 ? clamp >= rTon : false;
		if (clampOk) { score += 25; }
		parts.push({ key: 'clamp', label: 'Clamp force meets requirement', ok: clampOk, weight: 25, skip: rTon <= 0 });

		var shotOk = rShot > 0 ? shot >= rShot : false;
		if (shotOk) { score += 20; }
		parts.push({ key: 'shot', label: 'Shot size covers your part', ok: shotOk, weight: 20, skip: rShot <= 0 });

		/* Normalise material codes/labels so the requirement (code, e.g. "ABS") matches
		 * a machine entry whether it is stored as a code ("ABS"), an alias ("PC/ABS"), or
		 * a full label ("Polypropylene (PP)"). Strip everything but A–Z/0–9 before compare. */
		var normMat = function (s) { return String(s).toUpperCase().replace(/[^A-Z0-9]/g, ''); };
		var mats = (L.materials || []).map(normMat).filter(Boolean);
		var rMat = normMat(R.material);
		var matOk = rMat ? mats.some(function (m) { return m === rMat || m.indexOf(rMat) !== -1 || rMat.indexOf(m) !== -1; }) : false;
		if (matOk) { score += 20; }
		parts.push({ key: 'material', label: 'Runs your material' + (rMat ? ' (' + R.material + ')' : ''), ok: matOk, weight: 20, skip: !rMat });

		var rangeOk = (rTon > 0 && clamp > 0) ? (rTon <= clamp && rTon >= clamp * 0.3) : false;
		if (rangeOk) { score += 15; }
		parts.push({ key: 'range', label: 'Right-sized (not over/under tonnage)', ok: rangeOk, weight: 15, skip: rTon <= 0 || clamp <= 0 });

		var volOk = rVol > 0 ? (monthly > 0 ? monthly >= rVol : false) : false;
		if (volOk) { score += 10; }
		parts.push({ key: 'volume', label: 'Monthly output covers volume', ok: volOk, weight: 10, skip: rVol <= 0 });

		var locPts = locationPoints(L.location, R.location);
		score += locPts;
		parts.push({ key: 'location', label: 'Location proximity', ok: locPts >= 7, partial: locPts > 0 && locPts < 7, weight: 10, skip: !R.location });

		score = Math.round(Math.max(0, Math.min(100, score)));
		var label = score >= 80 ? 'Strong match' : (score >= 60 ? 'Good match' : 'Partial match');
		return { score: score, label: label, parts: parts };
	}

	function locationPoints(a, b) {
		a = (a || '').toLowerCase(); b = (b || '').toLowerCase();
		if (!a || !b) { return 0; }
		var ta = a.split(/[^a-z]+/).filter(Boolean);
		var tb = b.split(/[^a-z]+/).filter(Boolean);
		if (!ta.length || !tb.length) { return 0; }
		if (ta[0] && ta[0] === tb[0]) { return 10; }
		var shared = ta.some(function (x) { return tb.indexOf(x) !== -1; });
		return shared ? 5 : 0;
	}

	function fits(L, mould) {
		var rows = [];
		var tieX = num(L.tieBarX), tieY = num(L.tieBarY);
		var mhMin = num(L.mhMin), mhMax = num(L.mhMax), shot = num(L.shot);
		var ml = num(mould.L), mw = num(mould.W), mh = num(mould.H), pw = num(mould.weight);

		if (ml > 0 && mw > 0 && (tieX > 0 || tieY > 0)) {
			var a = tieX || tieY, b = tieY || tieX;
			var ok = (ml <= a && mw <= b) || (ml <= b && mw <= a);
			rows.push({ key: 'footprint', ok: ok, label: 'Mould footprint fits between tie-bars', reason: ok ? (ml + '×' + mw + ' mm ≤ ' + a + '×' + b + ' mm') : (ml + '×' + mw + ' mm exceeds ' + a + '×' + b + ' mm tie-bar space') });
		}
		if (mh > 0 && (mhMin > 0 || mhMax > 0)) {
			var okH = (mhMin <= 0 || mh >= mhMin) && (mhMax <= 0 || mh <= mhMax);
			rows.push({ key: 'height', ok: okH, label: 'Shut height within mould-height range', reason: okH ? (mh + ' mm within ' + (mhMin || 0) + '–' + (mhMax || '∞') + ' mm') : (mh + ' mm outside ' + (mhMin || 0) + '–' + (mhMax || '∞') + ' mm') });
		}
		if (pw > 0 && shot > 0) {
			var lo = shot * 0.2, hi = shot * 0.8;
			var okW = pw >= lo && pw <= hi;
			rows.push({ key: 'weight', ok: okW, label: 'Part weight in safe shot window', reason: okW ? (pw + ' g within ' + Math.round(lo) + '–' + Math.round(hi) + ' g (20–80%)') : (pw + ' g outside ' + Math.round(lo) + '–' + Math.round(hi) + ' g (20–80% of shot)') });
		}
		/* Clamp tonnage — only when the mould supplies projected_area(cm²) + cavity
		 * pressure(bar). required = projected_area × cavity_pressure ÷ 981 (total
		 * projected area, so cavities = 1). Omitted entirely when either input is
		 * absent, so the checker's existing behaviour is unchanged (graceful fallback). */
		var clamp = num(L.clamp);
		var reqTon = clampForceTonnes(mould.projectedArea, mould.cavityPressure, 1, 1.1);
		if (reqTon > 0 && clamp > 0) {
			var okT = clamp >= reqTon;
			rows.push({ key: 'tonnage', ok: okT, label: 'Clamp tonnage covers the part', reason: okT ? ('~' + reqTon + ' T required ≤ ' + clamp + ' T clamp') : ('~' + reqTon + ' T required exceeds ' + clamp + ' T clamp') });
		}
		return rows;
	}

	/* ── Production / throughput math (pure) ──────────────────────────────
	 * Standard moulding throughput:
	 *   parts/hour  = 3600 / cycle_time(s) × cavities
	 *   parts/day   = parts/hour × operating_hours × utilisation
	 *   parts/year  = parts/day × WORKING_DAYS (≈ 250 production days)
	 * Cavity count comes from the captured `cavities` field (L.cavities); when it is
	 * empty/0 we fall back to 1 cavity so throughput stays sane.
	 * The cycle phase split (clamp/inject/cool/eject) follows typical proportions;
	 * cooling dominates (~50%). */
	var WORKING_DAYS = 250;
	var CYCLE_SPLIT = [0.12, 0.18, 0.5, 0.2];
	function throughput(L) {
		var cycle = num(L.cycle);
		var hours = num(L.ophours) || 8;
		var util = num(L.util) ? clamp01(num(L.util) / 100) : 0.85;
		var cavities = num(L.cavities) > 0 ? num(L.cavities) : 1;
		var perDay = 0, perYear = 0;
		if (cycle > 0) {
			perDay = Math.floor((3600 / cycle) * cavities * hours * util);
			perYear = perDay * WORKING_DAYS;
		}
		var secs = CYCLE_SPLIT.map(function (s) { return cycle > 0 ? Math.round(cycle * s * 10) / 10 : s * 100; });
		return { perDay: perDay, perYear: perYear, cycle: cycle, hours: hours, util: util, cavities: cavities, split: CYCLE_SPLIT, secs: secs };
	}

	/* Required clamp tonnage from part geometry (pure):
	 *   tonnes = projected_area(cm²) × cavity_pressure(bar) × cavities × safety
	 *            ÷ 981   (1 tonne-force ≈ 981 N·... ; bar·cm² → N via ×10, ÷9.81 kN/T → ÷981)
	 * Projected area + cavity pressure are now captured on the job profile / listing
	 * (v2.7.15). When supplied, matchScore() + fits() prefer this physics-based estimate
	 * over the hand-typed required tonnage; callers pass cavities = 1 because the captured
	 * projected_area is the TOTAL across all cavities. Returns 0 when area is empty so
	 * callers fall back to the existing heuristic. */
	function clampForceTonnes(projectedAreaCm2, cavityPressureBar, cavities, safety) {
		var a = num(projectedAreaCm2), p = num(cavityPressureBar) || 350;
		var c = num(cavities) > 0 ? num(cavities) : 1, s = num(safety) > 0 ? num(safety) : 1.1;
		if (a <= 0) { return 0; }
		return Math.round((a * p * c * s) / 981);
	}

	/* Healthy tonnage window for a given machine clamp force (30–100%). */
	function requiredTonnageRange(clamp) {
		clamp = num(clamp);
		return { min: Math.round(clamp * 0.3), max: Math.round(clamp) };
	}

	window.IMD = { matchScore: matchScore, fits: fits, throughput: throughput, clampForceTonnes: clampForceTonnes, requiredTonnageRange: requiredTonnageRange };

	/* ─────────────────────────────────────────────────────────────────────
	 * Match for your job — score donut + checklist + inline requirement grid
	 * Drives the same saved profile (ih_save_requirement) the radar uses.
	 * ─────────────────────────────────────────────────────────────────── */
	var matchChart = null;
	var MATCH_LABELS = { clamp: 'Clamp force', shot: 'Shot size', material: 'Material', range: 'Required tonnage', volume: 'Monthly volume', location: 'Location' };

	function kfmt(n) { n = num(n); return n >= 1000 ? (Math.round(n / 100) / 10) + 'k' : String(Math.round(n)); }

	/* Build the requirement object for matchScore from the inline grid, falling
	 * back to the saved profile. Radar-only axes (screw/pressure) are not scored. */
	function reqMatch() {
		function v(id) { var el = document.getElementById(id); return el ? String(el.value).trim() : ''; }
		var nums = (v('imdRiMould').match(/-?\d+(?:\.\d+)?/g)) || [];
		return {
			tonnage: v('imdRiTonnage') || (req && req.tonnage) || '',
			shot: (req && req.shot) || '',
			material: v('imdRiMaterial') || (req && req.material) || '',
			volume: v('imdRiVolume') || (req && req.volume) || '',
			location: (req && req.location) || '',
			partWeight: v('imdRiPartWt') || (req && req.partWeight) || '',
			mouldL: nums[0] !== undefined ? nums[0] : ((req && req.mouldL) || ''),
			mouldW: nums[1] !== undefined ? nums[1] : ((req && req.mouldW) || ''),
			mouldH: nums[2] !== undefined ? nums[2] : ((req && req.mouldH) || ''),
			/* Clamp-tonnage inputs (cm² / bar) — drive a physics-based required tonnage. */
			projectedArea: v('imdRiProjArea') || (req && req.projectedArea) || '',
			cavityPressure: v('imdRiCavPress') || (req && req.cavityPressure) || ''
		};
	}

	function matchDetail(p, R) {
		var c = num(listing.clamp), s = num(listing.shot), mo = num(listing.monthly);
		var rTon = effectiveTonnage(R);
		switch (p.key) {
			case 'clamp':    return p.skip ? 'add required tonnage' : (p.ok ? (c + ' T ≥ your ' + rTon + ' T') : ('you need ' + rTon + ' T · machine is ' + c + ' T'));
			case 'shot':     return p.skip ? 'add a shot/part size' : (p.ok ? (s + ' mm ≥ your ' + num(R.shot) + ' mm') : ('you need ' + num(R.shot) + ' mm · machine is ' + s + ' mm'));
			case 'material': return p.skip ? 'add your material' : (p.ok ? (R.material + ' supported') : (R.material + ' not listed'));
			case 'range':    return p.skip ? 'add required tonnage' : (p.ok ? 'within machine range' : 'over/under machine tonnage');
			case 'volume':   return p.skip ? 'add a monthly volume' : (p.ok ? (kfmt(mo) + ' ≥ your ' + kfmt(R.volume)) : ('you need ' + kfmt(R.volume) + ' · machine ' + kfmt(mo)));
			case 'location': return p.skip ? 'add your location' : ((listing.location || '—') + (p.ok ? ' · near you' : (p.partial ? ' · same region' : ' · different region')));
		}
		return '';
	}

	function buildMatch() {
		var canvas = document.getElementById('imdMatchChart');
		if (!HAS_CHART || !canvas) { return; }
		matchChart = new window.Chart(canvas, {
			type: 'doughnut',
			data: { labels: ['Match', ''], datasets: [{ data: [0, 100], backgroundColor: [COLORS.brand, COLORS.track], borderWidth: 0 }] },
			options: {
				responsive: true, maintainAspectRatio: false, cutout: '76%',
				animation: REDUCED ? false : { animateRotate: true, duration: 900 },
				plugins: { legend: { display: false }, tooltip: { enabled: false } }
			}
		});
	}

	function renderMatch() {
		var pctEl = document.getElementById('imdMatchPct');
		var pillEl = document.getElementById('imdMatchPill');
		var checksEl = document.getElementById('imdMatchChecks');
		if (!checksEl) { return; }
		var R = reqMatch();
		var hasReq = !!(effectiveTonnage(R) || num(R.shot) || (R.material && R.material.length) || num(R.volume) || (R.location && R.location.length));
		var res = matchScore(listing, R);
		var score = hasReq ? res.score : 0;

		if (matchChart) {
			var col = score >= 60 ? COLORS.brand : COLORS.warning;
			matchChart.data.datasets[0].data = [score, Math.max(0, 100 - score)];
			matchChart.data.datasets[0].backgroundColor = [col, COLORS.track];
			matchChart.update(REDUCED ? 'none' : undefined);
		}
		if (pctEl) { pctEl.textContent = hasReq ? String(res.score) : '—'; }

		if (pillEl) {
			if (!hasReq) { pillEl.textContent = 'Set your job to score'; pillEl.setAttribute('data-state', 'none'); }
			else {
				var met = res.parts.filter(function (p) { return p.ok; }).length;
				pillEl.textContent = res.label + ' · ' + met + ' of ' + res.parts.length;
				pillEl.setAttribute('data-state', res.score >= 80 ? 'full' : (res.score >= 60 ? 'good' : 'partial'));
			}
		}

		checksEl.innerHTML = '';
		if (!hasReq) {
			checksEl.innerHTML = '<li class="imd-empty-hint">Add your requirement below to score this machine against your job.</li>';
			return;
		}
		res.parts.forEach(function (p) {
			var li = document.createElement('li');
			var state = p.skip ? 'is-skip' : (p.ok ? 'is-ok' : (p.partial ? 'is-partial' : 'is-no'));
			var ic = (p.ok) ? checkIcon() : (p.skip || p.partial ? dashIcon() : crossIcon());
			li.className = 'imd-mcheck ' + state;
			li.innerHTML = ic + '<span><b>' + MATCH_LABELS[p.key] + '</b> ' + matchDetail(p, R) + '</span>';
			checksEl.appendChild(li);
		});
	}

	/* L1: reflect the draggable requirement polygon into the scored requirement model
	 * so the top "Match for your job" donut tracks radar drags (debounced, no reload).
	 * The scored axes are clamp→tonnage, shot→shot and throughput→volume. */
	var _matchSyncTimer = null;
	function syncRadarToMatch() {
		if (!reqVal) { return; }
		var inlineMap = { clamp: 'imdRiTonnage', throughput: 'imdRiVolume' };
		Object.keys(inlineMap).forEach(function (key) {
			if (reqVal[key] == null) { return; }
			var el = document.getElementById(inlineMap[key]);
			if (el) { el.value = Math.round(reqVal[key]); }
		});
		req = req || {};
		if (reqVal.clamp != null) { req.tonnage = Math.round(reqVal.clamp); }
		if (reqVal.shot != null) { req.shot = Math.round(reqVal.shot); }
		if (reqVal.throughput != null) { req.volume = Math.round(reqVal.throughput); }
		if (reqVal.automation != null) { req.automation = reqVal.automation; }
		if (_matchSyncTimer) { clearTimeout(_matchSyncTimer); }
		_matchSyncTimer = setTimeout(renderMatch, 120);
	}

	function dashIcon() { return '<span class="imd-bd-ic is-neutral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 12h12"/></svg></span>'; }

	/* ─────────────────────────────────────────────────────────────────────
	 * Capability radar — the two-way control (§20)
	 *   requirement[axisKey] holds the raw requirement value per axis.
	 *   machine[axisKey]     is fixed from the listing.
	 * ─────────────────────────────────────────────────────────────────── */
	var radarChart = null;
	var reqVal = {};      // axisKey -> raw requirement value
	var touched = false;  // has the user set a requirement (drag/stepper/saved)?
	var MARGIN = 0.02;    // normalised tolerance for "meets"

	function axisByKey(k) { for (var i = 0; i < axes.length; i++) { if (axes[i].key === k) { return axes[i]; } } return null; }
	function normAxis(ax, v) { return clamp01(num(v) / (ax.max || 1)); }

	function reqFromProfile() {
		// Map the saved requirement profile fields onto the 6 axes.
		if (!req) { return false; }
		var any = false;
		axes.forEach(function (ax) {
			var src = req[ax.req];
			if (src !== undefined && src !== null && src !== '' && num(src) > 0) {
				reqVal[ax.key] = num(src);
				any = true;
			}
		});
		return any;
	}

	function initRequirement() {
		var seeded = reqFromProfile();
		touched = seeded;
		axes.forEach(function (ax) {
			if (reqVal[ax.key] === undefined) {
				// default at/below the machine so there are NO gaps until the user pushes a need up
				reqVal[ax.key] = (num(ax.machine) > 0 ? ax.machine * 0.7 : 0);
			}
		});
	}

	function buildRadar() {
		var canvas = document.getElementById('imdRadarChart');
		if (!HAS_CHART || !canvas || !axes.length) { return; }

		var machineData = axes.map(function (ax) { return normAxis(ax, ax.machine); });
		var reqData = axes.map(function (ax) { return normAxis(ax, reqVal[ax.key]); });

		radarChart = new window.Chart(canvas, {
			type: 'radar',
			data: {
				labels: axes.map(function (ax) { return ax.label; }),
				datasets: [
					{
						label: 'Machine',
						data: machineData,
						fill: true,
						backgroundColor: 'rgba(83,71,206,0.17)',
						borderColor: COLORS.brand,
						pointBackgroundColor: COLORS.brand,
						pointRadius: 3,
						borderWidth: 2,
						dragData: false
					},
					{
						label: 'Your requirement',
						data: reqData,
						fill: true,
						backgroundColor: 'rgba(17,180,166,0.10)',
						borderColor: COLORS.teal,
						borderDash: [5, 4],
						pointBackgroundColor: '#fff',
						pointBorderColor: COLORS.teal,
						pointBorderWidth: 2,
						pointRadius: 5,
						pointHoverRadius: 7,
						borderWidth: 2
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				animation: REDUCED ? false : { duration: 700 },
				scales: {
					r: {
						min: 0, max: 1,
						ticks: { display: false, stepSize: 0.25 },
						grid: { color: COLORS.border },
						angleLines: { color: COLORS.border },
						pointLabels: { color: cssVar('--imd-text-2', '#6b7185'), font: { size: 12, weight: '600' } }
					}
				},
				plugins: {
					legend: { display: false },
					tooltip: Object.assign(tooltipStyle(), {
						callbacks: {
							title: function (items) { return axes[items[0].dataIndex].label; },
							label: function (item) {
								var ax = axes[item.dataIndex];
								var raw = item.datasetIndex === 0 ? ax.machine : reqVal[ax.key];
								var who = item.datasetIndex === 0 ? 'machine' : 'you';
								return fmtAxis(ax, raw) + ' (' + who + ')';
							}
						}
					}),
					dragData: {
						round: 3,
						/* L4: the on-canvas tooltip gets clipped inside the radar box, so it is
						 * disabled. The live value is surfaced instead in the matching stepper
						 * field (syncStepper) and the gap panel (renderOutcome) below the chart. */
						showTooltip: false,
						dragX: false,
						onDragStart: function (e, datasetIndex) {
							if (datasetIndex !== 1) { return false; } // only the requirement polygon is draggable
						},
						onDrag: function (e, datasetIndex, index, value) {
							if (datasetIndex !== 1) { return false; }
							var v = clamp01(value);
							radarChart.data.datasets[1].data[index] = v;
							var ax = axes[index];
							reqVal[ax.key] = v * (ax.max || 1);
							touched = true;
							syncStepper(ax.key);
							renderOutcome();
							syncRadarToMatch(); // L1: keep the top "Match for your job" donut in sync
						},
						onDragEnd: function (e, datasetIndex) {
							if (datasetIndex !== 1) { return; }
							renderMatch();
							persistRequirementDebounced();
						}
					}
				}
			}
		});
	}

	function fmtAxis(ax, raw) {
		if (ax.key === 'automation') {
			return automationWord(raw);
		}
		return fmt(raw) + (ax.unit ? ' ' + ax.unit : '');
	}

	/* ── Number-input steppers (keyboard equivalent to dragging) ─────────── */
	function buildSteppers() {
		var grid = document.getElementById('imdAxisCtlGrid');
		if (!grid || !axes.length) { return; }
		grid.innerHTML = '';
		axes.forEach(function (ax) {
			var wrap = document.createElement('div');
			wrap.className = 'imd-field imd-axisctl__field';
			var id = 'imdAxis_' + ax.key;
			if (ax.key === 'automation') {
				/* Wording select (Manual / Semi-automated / Fully automated) — maps to
				 * the normalised 0..1 automation axis. No raw numbers shown to the user. */
				var sel = automationWord(reqVal[ax.key]);
				var opts = AUTOMATION_OPTIONS.map(function (o) {
					return '<option value="' + num(o.value) + '"' + (o.label === sel ? ' selected' : '') + '>' + o.label + '</option>';
				}).join('');
				wrap.innerHTML = '<label for="' + id + '">' + ax.label + '</label>' +
					'<select id="' + id + '" data-axis="' + ax.key + '" class="imd-axisctl__select">' + opts + '</select>';
			} else {
				var step = ax.max >= 1000 ? 1000 : (ax.max >= 100 ? 5 : 1);
				var val = Math.round(reqVal[ax.key]);
				wrap.innerHTML = '<label for="' + id + '">' + ax.label + (ax.unit ? ' (' + ax.unit + ')' : '') + '</label>' +
					'<input type="number" id="' + id + '" data-axis="' + ax.key + '" min="0" max="' + ax.max + '" step="' + step + '" value="' + val + '" inputmode="decimal">';
			}
			grid.appendChild(wrap);
		});
		$all('[data-axis]', grid).forEach(function (inp) {
			var evt = (inp.tagName === 'SELECT') ? 'change' : 'input';
			inp.addEventListener(evt, function () {
				var key = inp.getAttribute('data-axis');
				var ax = axisByKey(key);
				if (!ax) { return; }
				var v = num(inp.value);
				if (key === 'automation') { v = clamp01(v); }
				else { v = Math.max(0, Math.min(ax.max, v)); }
				reqVal[key] = v;
				touched = true;
				if (radarChart) {
					var idx = axes.indexOf(ax);
					radarChart.data.datasets[1].data[idx] = normAxis(ax, v);
					radarChart.update(REDUCED ? 'none' : undefined);
				}
				renderOutcome();
				persistRequirementDebounced();
			});
		});
	}

	function syncStepper(key) {
		var ax = axisByKey(key);
		var inp = document.getElementById('imdAxis_' + key);
		if (!ax || !inp) { return; }
		if (key === 'automation') {
			var word = automationWord(reqVal[key]);
			var match = AUTOMATION_OPTIONS.filter(function (o) { return o.label === word; })[0];
			inp.value = match ? String(num(match.value)) : inp.value;
		} else {
			inp.value = Math.round(reqVal[key]);
		}
	}

	/* ── Outcome: match badge + meets/gaps box ───────────────────────────── */
	function renderOutcome() {
		var box = document.getElementById('imdOutcome');
		var head = document.getElementById('imdOutcomeHead');
		var listEl = document.getElementById('imdOutcomeList');
		var still = document.getElementById('imdOutcomeStill');
		if (!box || !listEl) { return; }

		var gaps = [], exceeds = [], meetsLabels = [];
		axes.forEach(function (ax) {
			var mN = normAxis(ax, ax.machine);
			var rN = normAxis(ax, reqVal[ax.key]);
			if (rN > mN + MARGIN) {
				gaps.push(ax);
			} else {
				meetsLabels.push(ax.label);
				if (mN > rN + MARGIN) { exceeds.push(ax); }
			}
		});

		listEl.innerHTML = '';
		if (gaps.length === 0) {
			box.className = 'imd-outcome is-meets';
			head.textContent = touched ? 'Meets every need — try pushing a point past the machine' : 'Exceeds your needs on';
			var src = exceeds.length ? exceeds : axes;
			src.forEach(function (ax) {
				var li = document.createElement('li');
				li.className = 'is-ok';
				li.innerHTML = checkIcon() + '<span><b>' + ax.label + '</b> ' + fmtAxis(ax, ax.machine) + (touched ? ' · you need ' + fmtAxis(ax, reqVal[ax.key]) : '') + '</span>';
				listEl.appendChild(li);
			});
			if (still) { still.hidden = true; still.textContent = ''; }
		} else {
			box.className = 'imd-outcome is-gaps';
			head.textContent = 'Gaps for your job';
			gaps.forEach(function (ax) {
				var li = document.createElement('li');
				li.className = 'is-no';
				li.innerHTML = crossIcon() + '<span><b>' + ax.label + '</b> you need ' + fmtAxis(ax, reqVal[ax.key]) + ' · machine is ' + fmtAxis(ax, ax.machine) + '</span>';
				listEl.appendChild(li);
			});
			if (still) {
				if (meetsLabels.length) {
					still.hidden = false;
					still.innerHTML = '<span class="imd-outcome__ok">✓</span> Still meets ' + joinLabels(meetsLabels);
				} else { still.hidden = true; }
			}
		}
	}

	function joinLabels(arr) {
		arr = arr.map(function (s) { return s.toLowerCase(); });
		if (arr.length === 1) { return arr[0]; }
		return arr.slice(0, -1).join(', ') + ' & ' + arr[arr.length - 1];
	}
	function checkIcon() { return '<span class="imd-bd-ic is-yes"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-9"/></svg></span>'; }
	function crossIcon() { return '<span class="imd-bd-ic is-no"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></span>'; }

	/* ─────────────────────────────────────────────────────────────────────
	 * Interactive spec popovers (vs typical listed presses + percentile)
	 * ─────────────────────────────────────────────────────────────────── */
	(function specPopovers() {
		var pop = document.getElementById('imdPop');
		if (!pop) { return; }
		/* Portal the popover to <body> so it can never be clipped by a section's
		 * overflow or trapped beneath a chart canvas's stacking context. It is
		 * positioned in document coordinates (scrollY/scrollX), so body is the
		 * correct offset parent. */
		if (pop.parentNode !== document.body) { document.body.appendChild(pop); }
		var labelEl = document.getElementById('imdPopLabel');
		var valueEl = document.getElementById('imdPopValue');
		var textEl = document.getElementById('imdPopText');
		var vsEl = document.getElementById('imdPopVs');
		var fillEl = document.getElementById('imdPopFill');
		var dotEl = document.getElementById('imdPopDot');
		var minEl = document.getElementById('imdPopMin');
		var maxEl = document.getElementById('imdPopMax');
		var tagEl = document.getElementById('imdPopTag');
		var current = null;

		function tierLabel(pct) {
			if (pct >= 80) { return { t: 'Top 20% — high capacity', cls: 'is-high' }; }
			if (pct >= 60) { return { t: 'Upper range', cls: 'is-mid' }; }
			if (pct >= 40) { return { t: 'Mid range', cls: 'is-mid' }; }
			if (pct >= 20) { return { t: 'Lower range', cls: 'is-low' }; }
			return { t: 'Entry range', cls: 'is-low' };
		}

		function fill(btn) {
			labelEl.textContent = btn.getAttribute('data-pop-title') || '';
			valueEl.textContent = btn.getAttribute('data-pop-value') || '';
			textEl.textContent = btn.getAttribute('data-pop-text') || '';
			var key = btn.getAttribute('data-spec');
			var s = specStats[key];
			if (s && s.count >= 4 && s.max > s.min) {
				vsEl.style.display = '';
				var posPct = clamp01((s.value - s.min) / (s.max - s.min)) * 100;
				fillEl.style.width = posPct.toFixed(1) + '%';
				dotEl.style.left = posPct.toFixed(1) + '%';
				minEl.textContent = fmt(s.min);
				maxEl.textContent = fmt(s.max);
				var tl = tierLabel(s.pct);
				tagEl.textContent = '✓ ' + tl.t;
				tagEl.className = 'imd-pop__tag ' + tl.cls;
				tagEl.style.display = '';
			} else {
				vsEl.style.display = 'none';
				tagEl.textContent = 'Limited comparison data';
				tagEl.className = 'imd-pop__tag is-low';
				tagEl.style.display = '';
			}
		}

		function position(btn) {
			var r = btn.getBoundingClientRect();
			pop.hidden = false;
			var pw = pop.offsetWidth, ph = pop.offsetHeight;
			var top = window.scrollY + r.bottom + 8;
			var left = window.scrollX + r.left;
			if (left + pw > window.scrollX + document.documentElement.clientWidth - 12) {
				left = window.scrollX + document.documentElement.clientWidth - pw - 12;
			}
			if (left < window.scrollX + 12) { left = window.scrollX + 12; }
			// flip above if near bottom edge
			if (r.bottom + ph + 16 > window.innerHeight && r.top - ph - 8 > 0) {
				top = window.scrollY + r.top - ph - 8;
			}
			pop.style.top = top + 'px';
			pop.style.left = left + 'px';
		}

		function open(btn) {
			if (current === btn && !pop.hidden) { return; }
			current = btn;
			fill(btn);
			position(btn);
			requestAnimationFrame(function () { pop.classList.add('is-open'); });
			btn.setAttribute('aria-describedby', 'imdPop');
		}
		function close() {
			pop.classList.remove('is-open');
			if (current) { current.removeAttribute('aria-describedby'); }
			current = null;
			if (REDUCED) { pop.hidden = true; }
			else { setTimeout(function () { if (!current) { pop.hidden = true; } }, 150); }
		}

		$all('.imd-spec').forEach(function (btn) {
			btn.addEventListener('mouseenter', function () { open(btn); });
			btn.addEventListener('mouseleave', function () { setTimeout(function () { if (!pop.matches(':hover')) { close(); } }, 80); });
			btn.addEventListener('focus', function () { open(btn); });
			btn.addEventListener('blur', close);
			btn.addEventListener('click', function (e) { e.preventDefault(); if (current === btn && !pop.hidden) { close(); } else { open(btn); } });
		});
		pop.addEventListener('mouseleave', close);
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
		document.addEventListener('click', function (e) {
			if (current && !e.target.closest('.imd-spec') && !e.target.closest('#imdPop')) { close(); }
		});
		window.addEventListener('scroll', function () { if (current) { position(current); } }, { passive: true });
	})();

	/* ─────────────────────────────────────────────────────────────────────
	 * Fit checker (kept) — uses pure fits()
	 * ─────────────────────────────────────────────────────────────────── */
	function getMould() {
		return {
			L: $('#imdFitL') ? $('#imdFitL').value : '',
			W: $('#imdFitW') ? $('#imdFitW').value : '',
			H: $('#imdFitH') ? $('#imdFitH').value : '',
			weight: $('#imdFitWt') ? $('#imdFitWt').value : '',
			/* Clamp-tonnage inputs (cm² / bar) — fall back to the saved job profile. */
			projectedArea: $('#imdFitProjArea') ? $('#imdFitProjArea').value : ((req && req.projectedArea) || ''),
			cavityPressure: $('#imdFitCavPress') ? $('#imdFitCavPress').value : ((req && req.cavityPressure) || '')
		};
	}
	function renderFit() {
		var rowsEl = document.getElementById('imdFitRows');
		if (!rowsEl) { return; }
		var mould = getMould();
		var any = num(mould.L) || num(mould.W) || num(mould.H) || num(mould.weight) || num(mould.projectedArea) || num(mould.cavityPressure);
		if (!any) {
			rowsEl.innerHTML = '<li class="imd-empty-hint">Enter your mould footprint, shut height and part weight to check fit against this machine\'s tie-bar spacing, mould-height range and shot capacity.</li>';
			return;
		}
		var rows = fits(listing, mould);
		if (!rows.length) { rowsEl.innerHTML = '<li class="imd-empty-hint">Add more mould dimensions to check fit.</li>'; return; }
		rowsEl.innerHTML = '';
		rows.forEach(function (r) {
			var li = document.createElement('li');
			li.innerHTML = (r.ok ? checkIcon() : crossIcon()) + '<span><b>' + r.label + '</b><br><span style="color:var(--imd-muted);font-size:12px">' + r.reason + '</span></span>';
			rowsEl.appendChild(li);
		});
	}

	/* Prefill fit + requirement forms from saved profile */
	function prefillForms() {
		if (!req) { return; }
		var map = {
			imdReqTonnage: req.tonnage, imdReqShot: req.shot, imdReqMaterial: req.material,
			imdReqVolume: req.volume, imdReqMouldL: req.mouldL, imdReqMouldW: req.mouldW,
			imdReqMouldH: req.mouldH, imdReqPartWt: req.partWeight, imdReqLocation: req.location,
			imdFitL: req.mouldL, imdFitW: req.mouldW, imdFitH: req.mouldH, imdFitWt: req.partWeight,
			imdReqProjArea: req.projectedArea, imdReqCavPress: req.cavityPressure,
			imdRiProjArea: req.projectedArea, imdRiCavPress: req.cavityPressure,
			imdFitProjArea: req.projectedArea, imdFitCavPress: req.cavityPressure
		};
		Object.keys(map).forEach(function (id) {
			var el = document.getElementById(id);
			if (el && map[id] !== undefined && map[id] !== null && map[id] !== '') { el.value = map[id]; }
		});

		// Inline match grid
		var setVal = function (id, v) { var el = document.getElementById(id); if (el && v !== undefined && v !== null && v !== '') { el.value = v; } };
		setVal('imdRiTonnage', req.tonnage);
		setVal('imdRiVolume', req.volume);
		setVal('imdRiPartWt', req.partWeight);
		var sel = document.getElementById('imdRiMaterial');
		if (sel && req.material) {
			var found = $all('option', sel).some(function (o) { return o.value.toUpperCase() === String(req.material).toUpperCase(); });
			if (!found) { var o = document.createElement('option'); o.value = req.material; o.textContent = req.material; sel.appendChild(o); }
			sel.value = found ? $all('option', sel).filter(function (x) { return x.value.toUpperCase() === String(req.material).toUpperCase(); })[0].value : req.material;
		}
		var mouldEl = document.getElementById('imdRiMould');
		if (mouldEl && (req.mouldL || req.mouldW || req.mouldH)) {
			var parts = [req.mouldL, req.mouldW, req.mouldH].filter(function (x) { return x !== undefined && x !== null && x !== ''; });
			if (parts.length) { mouldEl.value = parts.join(' × '); }
		}
	}

	/* Map a saved/collected profile onto the radar requirement polygon. */
	function applyProfileToRadar(profile) {
		['clamp', 'shot', 'throughput'].forEach(function (k) {
			var ax = axisByKey(k);
			if (!ax) { return; }
			var src = profile[ax.req];
			if (src !== undefined && src !== null && src !== '' && num(src) > 0) {
				reqVal[k] = num(src); touched = true;
				if (radarChart) { radarChart.data.datasets[1].data[axes.indexOf(ax)] = normAxis(ax, reqVal[k]); }
				syncStepper(k);
			}
		});
		if (radarChart) { radarChart.update(REDUCED ? 'none' : undefined); }
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * IntersectionObserver — count-ups + meter on first view
	 * ─────────────────────────────────────────────────────────────────── */
	function revealEl(el) {
		$all('.imd-count', el).forEach(countUp);
		var meter = $('.imd-meter__fill', el);
		if (meter) { meter.classList.add('is-animated'); }
	}
	(function observe() {
		var targets = $all('.imd-sec, .imd-summary');
		if (!('IntersectionObserver' in window)) { targets.forEach(revealEl); return; }
		var io = new IntersectionObserver(function (entries, obs) {
			entries.forEach(function (e) { if (e.isIntersecting) { revealEl(e.target); obs.unobserve(e.target); } });
		}, { threshold: 0.2 });
		targets.forEach(function (t) { io.observe(t); });
	})();

	/* ── Boot the charts + controls ──────────────────────────────────────── */
	initRequirement();
	buildGauges();
	buildCycle();
	buildRadar();
	buildSteppers();
	buildMatch();
	renderOutcome();
	prefillForms();
	renderMatch();
	renderFit();

	var deFit = debounce(renderFit, 250);
	$all('#imdFitForm input').forEach(function (i) { i.addEventListener('input', deFit); });

	/* Fit panel toggle (Check your mould) */
	(function fitToggle() {
		var btn = document.getElementById('imdFitCheck');
		var panel = document.getElementById('imdFitPanel');
		if (!btn || !panel) { return; }
		btn.addEventListener('click', function () {
			var open = panel.hasAttribute('hidden');
			if (open) { panel.removeAttribute('hidden'); } else { panel.setAttribute('hidden', ''); }
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) { var f = panel.querySelector('input'); if (f) { f.focus(); } }
		});
	})();

	/* Print / spec-sheet (window.print + scoped @media print) */
	(function printSheet() {
		var btn = document.getElementById('imdPrint');
		if (!btn) { return; }
		btn.addEventListener('click', function () { window.print(); });
	})();

	/* Inline requirement grid → recompute match (+ persist when logged in) */
	(function inlineReq() {
		var btn = document.getElementById('imdReqUpdate');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			var R = reqMatch();
			var profile = {
				tonnage: R.tonnage, shot: R.shot, material: R.material, volume: R.volume, location: R.location,
				mouldL: R.mouldL, mouldW: R.mouldW, mouldH: R.mouldH, partWeight: R.partWeight,
				projectedArea: R.projectedArea, cavityPressure: R.cavityPressure,
				screw: reqVal.screw !== undefined ? reqVal.screw : '',
				pressure: reqVal.pressure !== undefined ? reqVal.pressure : '',
				throughput: reqVal.throughput !== undefined ? reqVal.throughput : '',
				automation: reqVal.automation !== undefined ? reqVal.automation : ''
			};
			req = Object.assign({}, req || {}, profile);
			renderMatch();
			applyProfileToRadar(profile);
			renderOutcome();
			// sync hidden fit inputs from the mould field
			if ($('#imdFitL') && profile.mouldL) { $('#imdFitL').value = profile.mouldL; }
			if ($('#imdFitW') && profile.mouldW) { $('#imdFitW').value = profile.mouldW; }
			if ($('#imdFitH') && profile.mouldH) { $('#imdFitH').value = profile.mouldH; }
			if ($('#imdFitWt') && profile.partWeight) { $('#imdFitWt').value = profile.partWeight; }
			renderFit();
			var fb = document.getElementById('imdReqInlineFb');
			if (DATA.loggedIn) { saveProfile(profile, fb, btn); }
			else if (fb) { fb.className = 'imd-match__fb is-ok'; fb.textContent = 'Match updated — log in to save it to your profile.'; }
		});
	})();

	/* Collapsible "Your requirement" panel toggled by the Edit requirement button.
	 * Initial state is rendered server-side (aria-expanded / hidden / .is-open) to
	 * avoid a load flash: collapsed once a profile is saved, expanded otherwise.
	 * The donut + checklist stay visible; only the data-entry grid collapses. */
	(function reqCollapse() {
		var toggle = document.getElementById('imdEditReq');
		var panel = document.getElementById('imdReqPanel');
		if (!toggle || !panel) { return; }
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var hideTimer = null;
		function onEnd(e) {
			if (e && e.target !== panel) { return; }
			if (!panel.classList.contains('is-open')) { panel.hidden = true; }
			panel.removeEventListener('transitionend', onEnd);
		}
		function setOpen(open, focus) {
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			panel.setAttribute('aria-hidden', open ? 'false' : 'true');
			if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
			if (open) {
				panel.hidden = false;
				if (reduce) { panel.classList.add('is-open'); }
				else { void panel.offsetHeight; panel.classList.add('is-open'); } // reflow → animate
				if (focus) { var f = panel.querySelector('input,select,textarea,button'); if (f) { f.focus(); } }
			} else {
				panel.classList.remove('is-open');
				if (reduce) { panel.hidden = true; }
				else {
					panel.addEventListener('transitionend', onEnd);
					hideTimer = setTimeout(function () { if (!panel.classList.contains('is-open')) { panel.hidden = true; } }, 420);
				}
			}
		}
		toggle.addEventListener('click', function () {
			setOpen(toggle.getAttribute('aria-expanded') !== 'true', true);
		});
	})();

	/* ─────────────────────────────────────────────────────────────────────
	 * Favourite (Save) toggle + Share
	 * ─────────────────────────────────────────────────────────────────── */
	(function fav() {
		var btn = document.getElementById('imdFav');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			if (!DATA.loggedIn) { window.location.href = DATA.loginUrl; return; }
			var fd = new FormData();
			fd.append('action', 'ih_toggle_wishlist');
			fd.append('nonce', DATA.nonce);
			fd.append('listing_id', btn.getAttribute('data-id'));
			fd.append('listing_type', 'machine');
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
		var btn = document.getElementById('imdShare');
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

	/* ─────────────────────────────────────────────────────────────────────
	 * Modal helpers
	 * ─────────────────────────────────────────────────────────────────── */
	var modalReturnFocus = null;   // element to restore focus to on close

	function modalFocusable(modal) {
		return $all('a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])', modal)
			.filter(function (el) { return el.offsetWidth || el.offsetHeight || el.getClientRects().length; });
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

	/* ─────────────────────────────────────────────────────────────────────
	 * Request details flow -> ih_request_listing_details
	 * ─────────────────────────────────────────────────────────────────── */
	var reqModal = document.getElementById('imdModal');
	function triggerRequest() {
		if (!DATA.loggedIn) { window.location.href = DATA.loginUrl; return; }
		if (reqModal) { openModal(reqModal); }
	}
	var rb = document.getElementById('imdRequestBtn');
	var rbm = document.getElementById('imdRequestBtnMobile');
	if (rb) { rb.addEventListener('click', triggerRequest); }
	if (rbm) { rbm.addEventListener('click', triggerRequest); }

	var confirmBtn = document.getElementById('imdModalConfirm');
	if (confirmBtn) {
		confirmBtn.addEventListener('click', function () {
			var fb = document.getElementById('imdModalFeedback');
			var msgEl = document.getElementById('imdReqMessage');
			confirmBtn.disabled = true;
			var span = confirmBtn.querySelector('span');
			var orig = span ? span.textContent : '';
			if (span) { span.textContent = 'Sending…'; }
			var fd = new FormData();
			fd.append('action', 'ih_request_listing_details');
			fd.append('nonce', DATA.nonce);
			fd.append('listing_id', DATA.listingId);
			fd.append('listing_type', 'machine');
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
		var cta = document.getElementById('imdContact');
		if (cta) {
			var btnEl = document.getElementById('imdRequestBtn');
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

	/* ─────────────────────────────────────────────────────────────────────
	 * Requirement profile -> ih_save_requirement
	 * ─────────────────────────────────────────────────────────────────── */
	var reqProfileModal = document.getElementById('imdReqModal');
	function openReqProfile() {
		if (!DATA.loggedIn) { window.location.href = DATA.loginUrl; return; }
		openModal(reqProfileModal);
	}
	/* "Edit job profile" (gated owner/contact area) opens the modal editor. The
	   top-of-card "Edit requirement" button instead toggles the inline panel
	   (see reqCollapse below), so it is intentionally NOT wired to the modal. */
	(function () {
		var el = document.getElementById('imdReqOpenBtn');
		if (el) { el.addEventListener('click', openReqProfile); }
	})();

	function collectProfile() {
		function val(id) { var el = document.getElementById(id); return el ? el.value : ''; }
		var profile = {
			tonnage: val('imdReqTonnage'), shot: val('imdReqShot'), material: val('imdReqMaterial'),
			volume: val('imdReqVolume'), mouldL: val('imdReqMouldL'), mouldW: val('imdReqMouldW'),
			mouldH: val('imdReqMouldH'), partWeight: val('imdReqPartWt'), location: val('imdReqLocation'),
			projectedArea: val('imdReqProjArea') || val('imdRiProjArea'), cavityPressure: val('imdReqCavPress') || val('imdRiCavPress')
		};
		// fold in the radar axes (screw / pressure / throughput / automation + clamp/shot via radar)
		profile.screw = reqVal.screw !== undefined ? reqVal.screw : '';
		profile.pressure = reqVal.pressure !== undefined ? reqVal.pressure : '';
		profile.throughput = reqVal.throughput !== undefined ? reqVal.throughput : '';
		profile.automation = reqVal.automation !== undefined ? reqVal.automation : '';
		// clamp/shot/volume kept in sync from the radar if the user dragged them
		if (touched) {
			if (reqVal.clamp !== undefined) { profile.tonnage = reqVal.clamp; }
			if (reqVal.shot !== undefined) { profile.shot = reqVal.shot; }
			if (reqVal.throughput !== undefined && !profile.volume) { profile.volume = reqVal.throughput; }
		}
		return profile;
	}

	function saveProfile(profile, fb, btn) {
		if (btn) { btn.disabled = true; }
		var fd = new FormData();
		fd.append('action', 'ih_save_requirement');
		fd.append('nonce', DATA.nonce);
		Object.keys(profile).forEach(function (k) { fd.append(k, profile[k]); });
		return fetch(DATA.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (d) {
				if (d && d.success) {
					req = (d.data && d.data.requirement) ? d.data.requirement : profile;
					if (fb) { fb.className = (fb.className.replace(/\bis-(ok|err)\b/g, '')) + ' is-ok'; fb.textContent = '✓ Saved'; }
					renderMatch();
				} else {
					if (fb) { fb.className = (fb.className.replace(/\bis-(ok|err)\b/g, '')) + ' is-err'; fb.textContent = '⚠ ' + ((d && d.data && (d.data.message || d.data)) || 'Could not save.'); }
					if (d && d.data && d.data.login_required) { window.location.href = DATA.loginUrl; }
				}
			})
			.catch(function () { if (fb) { fb.className = (fb.className.replace(/\bis-(ok|err)\b/g, '')) + ' is-err'; fb.textContent = '⚠ Network error.'; } })
			.finally(function () { if (btn) { btn.disabled = false; } });
	}

	var persistRequirementDebounced = debounce(function () {
		if (!DATA.loggedIn) { return; }
		var fb = document.getElementById('imdAxisCtlFb');
		saveProfile(collectProfile(), fb, null);
	}, 700);

	var reqSaveInline = document.getElementById('imdReqSaveInline');
	if (reqSaveInline) {
		reqSaveInline.addEventListener('click', function () {
			if (!DATA.loggedIn) { window.location.href = DATA.loginUrl; return; }
			saveProfile(collectProfile(), document.getElementById('imdAxisCtlFb'), reqSaveInline);
		});
	}

	var reqSave = document.getElementById('imdReqSave');
	if (reqSave) {
		reqSave.addEventListener('click', function () {
			var fb = document.getElementById('imdReqSaveFeedback');
			var profile = collectProfile();
			saveProfile(profile, fb, reqSave).then(function () {
				// sync fit form + radar from the new material/volume/mould values
				if ($('#imdFitL') && profile.mouldL) { $('#imdFitL').value = profile.mouldL; }
				if ($('#imdFitW') && profile.mouldW) { $('#imdFitW').value = profile.mouldW; }
				if ($('#imdFitH') && profile.mouldH) { $('#imdFitH').value = profile.mouldH; }
				if ($('#imdFitWt') && profile.partWeight) { $('#imdFitWt').value = profile.partWeight; }
				// re-map clamp/shot/volume onto radar
				['clamp', 'shot', 'throughput'].forEach(function (k) {
					var ax = axisByKey(k);
					var srcKey = ax ? ax.req : null;
					if (ax && srcKey && profile[srcKey] && num(profile[srcKey]) > 0) {
						reqVal[k] = num(profile[srcKey]); touched = true;
						if (radarChart) { radarChart.data.datasets[1].data[axes.indexOf(ax)] = normAxis(ax, reqVal[k]); }
						syncStepper(k);
					}
				});
				if (radarChart) { radarChart.update(REDUCED ? 'none' : undefined); }
				renderOutcome();
				renderFit();
				setTimeout(function () { closeModal(reqProfileModal); }, 700);
			});
		});
	}

	/* ─────────────────────────────────────────────────────────────────────
	 * Owner / admin approve · deny -> ih_owner_request_action
	 * ─────────────────────────────────────────────────────────────────── */
	(function ownerActions() {
		var wrap = document.getElementById('imdOwnerReqs');
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

	/* ─────────────────────────────────────────────────────────────────────
	 * Remove listing. Admin -> ih_delete_machine (manage_options gated). Owner
	 * (data-owner="1") -> ih_owner_delete_machine (ownership gated). Both confirm
	 * before the destructive call and are server-side capability/ownership checked.
	 * ─────────────────────────────────────────────────────────────────── */
	(function removeListing() {
		var btn = document.getElementById('imdRemoveBtn');
		if (!btn) { return; }
		var isOwner = btn.getAttribute('data-owner') === '1';
		var action = isOwner ? 'ih_owner_delete_machine' : 'ih_delete_machine';
		btn.addEventListener('click', function () {
			if (!window.confirm('Remove this machine listing permanently? This cannot be undone.')) { return; }
			btn.disabled = true;
			var fd = new FormData();
			fd.append('action', action);
			fd.append('nonce', DATA.nonce);
			fd.append('id', btn.getAttribute('data-id'));
			fetch(DATA.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (d) {
					if (d && d.success) { window.location.href = isOwner ? DATA.dashboardUrl || '/machines/' : '/machines/'; }
					else { btn.disabled = false; window.alert((d && d.data && (d.data.message || d.data)) || 'Could not remove the listing.'); }
				})
				.catch(function () { btn.disabled = false; window.alert('Network error. Please try again.'); });
		});
	})();

})();

/* ───────────────────────────────────────────────────────────────────────────
 * C1: clear the fixed site header so the breadcrumb + gallery are never hidden.
 *
 * Robustness notes (why this is a standalone IIFE, not nested in the main one):
 *  - The site header (header.fixed) is a floating card with a top gap and TWO
 *    in-flow rows on desktop (lime top bar + white nav row). The nav row ships
 *    as display:none and is switched on by header.php's inline script, so a too-
 *    early measurement would capture only the top row. We therefore re-measure
 *    on fonts-ready, load, resize and a couple of delays.
 *  - On mobile/tablet (<1024px) the nav row is a TOGGLE overlay, not in-flow, so
 *    we measure ONLY the lime top bar there (otherwise an open menu would inflate
 *    the offset). On desktop we measure the full header (both rows).
 *  - The measured value is published as the CSS var --im-header-h, which every
 *    .imd-page padding rule consumes (see im-machine-detail.css) so JS always
 *    wins over the per-breakpoint fallbacks regardless of cascade order.
 *  - This runs OUTSIDE the main detail IIFE so a runtime error elsewhere can
 *    never prevent the offset from being applied.
 * ─────────────────────────────────────────────────────────────────────────── */
(function navOffset() {
	if (!document.getElementById('imdPage')) { return; }
	var GAP = 14;

	function headerBottom() {
		var header = document.querySelector('header.fixed') || document.querySelector('header');
		if (!header) { return 0; }
		var measured;
		if (window.innerWidth < 1024) {
			// Mobile/tablet: only the lime top bar is in-flow; the nav is a toggle overlay.
			var topbar = header.querySelector('.im-public-header-top') || header;
			measured = topbar.getBoundingClientRect().bottom;
		} else {
			// Desktop: the whole header (top bar + nav row) is fixed chrome to clear.
			measured = header.getBoundingClientRect().bottom;
		}
		// Also clear the WP admin bar when present (also fixed at the top).
		var ab = document.getElementById('wpadminbar');
		if (ab) {
			var abb = ab.getBoundingClientRect().bottom;
			if (abb > measured) { measured = abb; }
		}
		return measured;
	}

	function apply() {
		var px = headerBottom();
		if (px > 0) {
			document.documentElement.style.setProperty('--im-header-h', Math.round(px + GAP) + 'px');
		}
	}

	apply();
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', apply);
	}
	window.addEventListener('load', apply);
	window.addEventListener('orientationchange', apply);
	var t = null;
	window.addEventListener('resize', function () { if (t) { clearTimeout(t); } t = setTimeout(apply, 120); });
	// Header height can settle after webfonts + header.php's menu script run.
	if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
		document.fonts.ready.then(apply);
	}
	setTimeout(apply, 150);
	setTimeout(apply, 400);
	setTimeout(apply, 900);
})();
