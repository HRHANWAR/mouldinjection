/**
 * Add Machine listing form — stepper, listing strength, live preview, uploads.
 */
(function () {
	'use strict';

	var root = document.getElementById('ihAddToolRoot');
	if (!root || root.dataset.bound) {
		return;
	}
	root.dataset.bound = '1';

	var form = document.getElementById('ihAddToolForm');
	if (!form) {
		return;
	}
	root.classList.add('is-enhanced');

	var sections = Array.prototype.slice.call(root.querySelectorAll('.ih-at-section[id]'));
	var steps = Array.prototype.slice.call(root.querySelectorAll('.ih-at-step'));
	var strengthPct = document.getElementById('ihAtStrengthPct');
	var strengthLabel = document.getElementById('ihAtStrengthLabel');
	var strengthFg = root.querySelector('.ih-at-strength__fg');
	var strengthImgTip = document.getElementById('ihAtStrengthImgTip');
	var mobileProgressPct = document.getElementById('ihAtMobileProgressPct');
	var mobileProgressFill = document.getElementById('ihAtMobileProgressFill');
	var mobileStepLabel = document.getElementById('ihAtMobileStepLabel');
	var mobileStrengthTip = document.getElementById('ihAtMobileStrengthTip');
	var accordions = Array.prototype.slice.call(root.querySelectorAll('.ih-at-accordion[data-mobile-step]'));
	var mobileSections = accordions.slice();
	var formToast = document.getElementById('ihAtFormToast');
	var lastSaved = document.getElementById('ihAtLastSaved');
	var strengthLive = document.getElementById('ihAtStrengthLive');
	var strengthItems = {
		identity: document.getElementById('ihAtStrengthPart'),
		specs: document.getElementById('ihAtStrengthMould'),
		production: document.getElementById('ihAtStrengthProduction'),
		certs: document.getElementById('ihAtStrengthFeatures'),
		images: document.getElementById('ihAtStrengthImgTip')
	};
	var listingDateInput = document.getElementById('ih_at_listing_date');
	var expiryDateInput = document.getElementById('ih_at_expiry_date');
	var expirySoon = document.getElementById('ih_at_expiry_date_soon');
	var imagesError = document.getElementById('ih_at_images_error');
	var polyChip = document.getElementById('ihAtPolyChip');
	var polyChipText = document.getElementById('ihAtPolyChipText');
	var toastTimer = null;
	var overlayPairs = [];
	var overlayFrame = null;

	var mobileStepNames = {
		'ih-at-sec-part': 'Part info',
		'ih-at-sec-mould': 'Mould specs',
		'ih-at-sec-prod': 'Production',
		'ih-at-sec-feat': 'Features',
		'ih-at-sec-listing': 'Listing',
		'ih-at-sec-images': 'Images'
	};

	function isMobileLayout() {
		return window.matchMedia('(max-width: 768px)').matches;
	}

	function scrollToSection(id) {
		var el = document.getElementById(id);
		if (!el) {
			return;
		}
		var top = el.getBoundingClientRect().top + window.pageYOffset - 72;
		window.scrollTo({ top: top, behavior: 'smooth' });
	}

	steps.forEach(function (step) {
		step.addEventListener('click', function () {
			scrollToSection(step.getAttribute('data-target'));
		});
	});

	function setActiveStep(id) {
		steps.forEach(function (s) {
			s.classList.toggle('is-active', s.getAttribute('data-target') === id);
		});
		if (isMobileLayout() && mobileStepLabel) {
			var stepNum = 0;
			var stepName = mobileStepNames[id] || '';
			accordions.forEach(function (acc) {
				if (acc.id === id) {
					stepNum = parseInt(acc.getAttribute('data-mobile-step'), 10) || 0;
				}
			});
			if (stepNum && stepName) {
				mobileStepLabel.textContent = 'Step ' + stepNum + ' of 6 — ' + stepName;
			}
		}
	}

	accordions.forEach(function (acc) {
		var toggle = acc.querySelector('[data-accordion-toggle]');
		var body = acc.querySelector('.ih-at-accordion__body');
		if (!toggle) {
			return;
		}
		if (body && !body.id) {
			body.id = acc.id + '-body';
		}
		if (body) {
			toggle.setAttribute('aria-controls', body.id);
		}
		toggle.addEventListener('click', function (e) {
			if (!isMobileLayout()) {
				return;
			}
			e.preventDefault();
			var willOpen = !acc.classList.contains('is-open');
			if (willOpen) {
				accordions.forEach(function (other) {
					if (other !== acc) {
						other.classList.remove('is-open');
					}
				});
			}
			acc.classList.toggle('is-open', willOpen);
			toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
			if (willOpen) {
				setActiveStep(acc.id);
			}
		});
		toggle.setAttribute('aria-expanded', acc.classList.contains('is-open') ? 'true' : 'false');
	});

	function syncMobileAccordions() {
		if (!isMobileLayout()) {
			accordions.forEach(function (acc) {
				var toggle = acc.querySelector('[data-accordion-toggle]');
				acc.classList.add('is-open');
				if (toggle) {
					toggle.setAttribute('aria-expanded', 'true');
				}
			});
			return;
		}
		var open = accordions.filter(function (acc) {
			return acc.classList.contains('is-open');
		})[0] || accordions[0];
		accordions.forEach(function (acc) {
			var toggle = acc.querySelector('[data-accordion-toggle]');
			var isOpen = acc === open;
			acc.classList.toggle('is-open', isOpen);
			if (toggle) {
				toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			}
		});
		if (open) {
			setActiveStep(open.id);
		}
	}

	function showToast(message, type) {
		if (!formToast) {
			return;
		}
		window.clearTimeout(toastTimer);
		formToast.textContent = message;
		formToast.className = 'ih-at-toast' + (type ? ' is-' + type : '');
		formToast.hidden = false;
		toastTimer = window.setTimeout(function () {
			formToast.hidden = true;
		}, 4200);
	}

	function formatTime(date) {
		try {
			return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
		} catch (err) {
			return '';
		}
	}

	function setItemDone(item, done) {
		if (!item) {
			return;
		}
		item.classList.toggle('is-done', !!done);
		var dot = item.querySelector('.ih-at-strength__dot');
		if (dot) {
			dot.classList.toggle('is-done', !!done);
		}
	}

	function updateAccordionMeta() {
		accordions.forEach(function (acc) {
			var secFields = Array.prototype.slice.call(
				acc.querySelectorAll('[data-strength], [data-strength-image], [data-strength-image-optional], [data-strength-cert]')
			);
			if (!secFields.length) {
				return;
			}
			var secFilled = secFields.filter(isFilled).length;
			var isComplete = secFilled === secFields.length;
			var check = acc.querySelector('.ih-at-accordion__check');
			if (check) {
				check.hidden = !isComplete;
			}
			var statTpl = acc.getAttribute('data-accordion-stat');
			var statLabel = acc.querySelector('[data-accordion-stat-label]');
			if (statTpl && statLabel) {
				statLabel.textContent = statTpl
					.replace('{filled}', String(secFilled))
					.replace('{total}', String(secFields.length));
				statLabel.hidden = false;
			}
		});
	}

	function mobileStrengthHint(pct, imgCount) {
		if (pct >= 100) {
			return pct + '% — complete';
		}
		if (!imgCount) {
			return pct + '% — add a cover';
		}
		if (pct >= 70) {
			return pct + '% — nearly complete';
		}
		if (pct >= 40) {
			return pct + '% — add specs';
		}
		return pct + '% — getting started';
	}

	var scrollSpyObserver = null;
	var scrollSpyMode = null;

	function getScrollSpySections() {
		return isMobileLayout() ? mobileSections : sections;
	}

	function bindScrollSpy() {
		if (!('IntersectionObserver' in window)) {
			return;
		}
		var mode = isMobileLayout() ? 'mobile' : 'desktop';
		if (scrollSpyObserver && scrollSpyMode === mode) {
			return;
		}
		if (scrollSpyObserver) {
			scrollSpyObserver.disconnect();
		}
		scrollSpyMode = mode;
		scrollSpyObserver = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (en) {
					if (en.isIntersecting) {
						setActiveStep(en.target.id);
					}
				});
			},
			{ rootMargin: '-20% 0px -55% 0px', threshold: 0 }
		);
		getScrollSpySections().forEach(function (sec) {
			scrollSpyObserver.observe(sec);
		});
	}

	bindScrollSpy();

	var resizeTimer;
	window.addEventListener('resize', function () {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function () {
			bindScrollSpy();
			syncMobileAccordions();
			queueOverlayReposition(true);
		}, 150);
	});

	function registerOverlay(picker, dropdown) {
		if (!picker || !dropdown) {
			return;
		}
		if (overlayPairs.some(function (pair) { return pair.picker === picker && pair.dropdown === dropdown; })) {
			return;
		}
		overlayPairs.push({ picker: picker, dropdown: dropdown });
	}

	function positionOverlay(picker, dropdown) {
		if (!picker || !dropdown || dropdown.hidden) {
			return;
		}
		picker.classList.remove('is-drop-up');
		if (isMobileLayout()) {
			return;
		}
		var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
		var pickerRect = picker.getBoundingClientRect();
		var dropdownRect = dropdown.getBoundingClientRect();
		var gap = 20;
		var spaceBelow = viewportHeight - pickerRect.bottom - gap;
		var spaceAbove = pickerRect.top - gap;
		if (dropdownRect.height > spaceBelow && spaceAbove > spaceBelow) {
			picker.classList.add('is-drop-up');
		}
	}

	function ensureOverlayVisible(dropdown) {
		if (!dropdown || dropdown.hidden) {
			return;
		}
		var rect = dropdown.getBoundingClientRect();
		var navOffset = 24;
		if (isMobileLayout()) {
			navOffset = document.body.classList.contains('ih-has-site-nav') || document.body.classList.contains('ih-site-nav-active') ? 168 : 96;
		}
		var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
		var bottomLimit = viewportHeight - navOffset;
		if (rect.bottom > bottomLimit) {
			window.scrollBy({ top: rect.bottom - bottomLimit + 12, behavior: 'smooth' });
		} else if (rect.top < 16) {
			window.scrollBy({ top: rect.top - 28, behavior: 'smooth' });
		}
	}

	function setOverlayState(picker, dropdown, open, focusTarget) {
		var section = picker && picker.closest('.ih-at-section');
		if (section) {
			section.classList.toggle('is-overlay-open', !!open);
		}
		if (!open && picker) {
			picker.classList.remove('is-drop-up');
		}
		if (!open || !dropdown) {
			return;
		}
		window.requestAnimationFrame(function () {
			positionOverlay(picker, dropdown);
			ensureOverlayVisible(dropdown);
			if (focusTarget && focusTarget.focus) {
				focusTarget.focus();
			}
		});
	}

	function queueOverlayReposition(ensureVisible) {
		if (overlayFrame) {
			window.cancelAnimationFrame(overlayFrame);
		}
		overlayFrame = window.requestAnimationFrame(function () {
			overlayFrame = null;
			overlayPairs.forEach(function (pair) {
				if (!pair.dropdown.hidden) {
					positionOverlay(pair.picker, pair.dropdown);
					if (ensureVisible) {
						ensureOverlayVisible(pair.dropdown);
					}
				}
			});
		});
	}

	window.addEventListener('scroll', function () {
		queueOverlayReposition(false);
	}, true);

	function isFilled(el) {
		if (!el) {
			return false;
		}
		var type = (el.type || '').toLowerCase();
		if (type === 'file') {
			var drop = el.closest('.ih-at-drop');
			if (drop) {
				return drop.classList.contains('is-filled');
			}
			return !!(el.files && el.files.length);
		}
		if (type === 'checkbox' || type === 'radio') {
			if (type === 'radio') {
				var group = form.querySelectorAll('[name="' + el.name + '"]');
				for (var i = 0; i < group.length; i++) {
					if (group[i].checked) {
						return true;
					}
				}
				return false;
			}
			return el.checked;
		}
		if (el.classList && el.classList.contains('ih-at-drop')) {
			return el.classList.contains('is-filled');
		}
		return String(el.value || '').trim() !== '';
	}

	function fieldHasValue(name) {
		var field = form.querySelector('[name="' + name + '"]');
		return !!(field && isFilled(field));
	}

	function checkedAny(selector) {
		return !!form.querySelector(selector + ':checked');
	}

	function imageCount() {
		var count = 0;
		form.querySelectorAll('[data-strength-image], [data-strength-image-optional]').forEach(function (input) {
			var label = input.closest('.ih-at-drop');
			if (label && label.classList.contains('is-filled')) {
				count++;
			}
		});
		return count;
	}

	/* Reflect the §1 Material Grade picker into the right-rail summary chip.
	   Source of truth = the hidden materials[] inputs in #ihAtGradeMaterials,
	   which mirror the picker's selected grade chips. */
	function updatePolyChip() {
		if (!polyChip) {
			return;
		}
		var holder = document.getElementById('ihAtGradeMaterials');
		var count = holder ? holder.querySelectorAll('input[name="materials[]"]').length : 0;
		polyChip.classList.toggle('is-empty', count === 0);
		if (polyChipText) {
			polyChipText.textContent = count === 0
				? 'No polymers selected'
				: count + ' polymer' + (count === 1 ? '' : 's') + ' selected';
		}
	}

	function calcStrength() {
		var fields = Array.prototype.slice.call(form.querySelectorAll('[data-strength]'));
		var vital = Array.prototype.slice.call(form.querySelectorAll('[data-vital]'));
		var certFields = Array.prototype.slice.call(form.querySelectorAll('[data-strength-cert]'));
		var images = Array.prototype.slice.call(form.querySelectorAll('[data-strength-image]'));
		var optionalImages = Array.prototype.slice.call(form.querySelectorAll('[data-strength-image-optional]'));

		var filled = 0;
		var total = fields.length + certFields.length;

		fields.forEach(function (f) {
			if (isFilled(f)) {
				filled++;
			}
		});
		certFields.forEach(function (f) {
			if (isFilled(f)) {
				filled++;
			}
		});

		var imgCount = imageCount();

		var fieldPct = total ? (filled / total) * 85 : 0;
		var imgPct = Math.min(imgCount, 1) * 15;
		var pct = Math.round(fieldPct + imgPct);
		var identityDone = fieldHasValue('title') && fieldHasValue('location') && fieldHasValue('material_grade');
		var specsDone = fieldHasValue('mould_type') || fieldHasValue('mould_dimensions') || fieldHasValue('num_cavities_spec') || fieldHasValue('runner_type');
		var productionDone = fieldHasValue('required_qty') || fieldHasValue('annual_volume') || fieldHasValue('cycle_time') || fieldHasValue('min_order_qty') || fieldHasValue('packaging');
		var certsDone = fieldHasValue('clamp_force') || fieldHasValue('material_grade') || checkedAny('.ih-at-switch-card__input');

		if (strengthPct) {
			strengthPct.textContent = pct + '%';
		}
		if (strengthFg) {
			strengthFg.style.strokeDashoffset = String(113 - (113 * pct) / 100);
		}
		if (strengthLabel) {
			var label = 'Getting started';
			if (pct >= 100) {
				label = 'Complete';
			} else if (pct >= 70) {
				label = 'Nearly complete';
			} else if (pct >= 40) {
				label = 'Keep going';
			}
			strengthLabel.textContent = label;
		}
		if (strengthImgTip) {
			strengthImgTip.style.opacity = imgCount > 0 ? '0.5' : '1';
		}
		setItemDone(strengthItems.identity, identityDone);
		setItemDone(strengthItems.specs, specsDone);
		setItemDone(strengthItems.production, productionDone);
		setItemDone(strengthItems.certs, certsDone);
		setItemDone(strengthItems.images, imgCount > 0);
		if (imgCount > 0 && imagesError) {
			imagesError.hidden = true;
			var imageSection = document.getElementById('ih-at-sec-images');
			if (imageSection) {
				imageSection.classList.remove('is-error');
			}
		}
		if (strengthLive) {
			strengthLive.textContent = pct + '% complete. ' + (identityDone && imgCount > 0 ? 'Listing basics are ready.' : 'Add required fields and a cover image.');
		}
		if (mobileProgressPct) {
			mobileProgressPct.textContent = pct + '%';
		}
		if (mobileProgressFill) {
			mobileProgressFill.style.width = pct + '%';
		}
		if (mobileStrengthTip) {
			mobileStrengthTip.textContent = mobileStrengthHint(pct, imgCount);
		}

		updateAccordionMeta();
		updatePolyChip();

		sections.forEach(function (sec) {
			var secFields = Array.prototype.slice.call(sec.querySelectorAll('[data-strength], [data-strength-image], [data-strength-image-optional], [data-strength-cert]'));
			var secFilled = secFields.filter(isFilled).length;
			var stepBtn = steps.filter(function (s) {
				return s.getAttribute('data-target') === sec.id;
			})[0];
			if (stepBtn && secFields.length) {
				stepBtn.classList.toggle('is-done', secFilled === secFields.length);
			}
		});

		return { pct: pct };
	}

	form.addEventListener('input', calcStrength);
	form.addEventListener('change', calcStrength);

	form.querySelectorAll('[data-vital]').forEach(function (field) {
		field.addEventListener('input', function () {
			if (!isFilled(field)) {
				return;
			}
			field.removeAttribute('aria-invalid');
			var wrap = field.closest('.ih-at-field');
			if (wrap) {
				wrap.classList.remove('is-error');
				var error = wrap.querySelector('.ih-at-field-error');
				if (error) {
					error.hidden = true;
				}
			}
		});
	});

	root.querySelectorAll('.ih-at-toggle-row .ih-at-toggle-cb').forEach(function (cb) {
		var hidden = cb.parentElement && cb.parentElement.querySelector('input[type="hidden"][name="' + cb.name + '"]');
		if (!hidden) {
			return;
		}
		function syncHidden() {
			hidden.disabled = cb.checked;
		}
		cb.addEventListener('change', syncHidden);
		syncHidden();
	});

	root.querySelectorAll('.ih-at-check input[type="checkbox"][data-strength]').forEach(function (cb) {
		var hidden = cb.previousElementSibling;
		if (!hidden || hidden.type !== 'hidden' || hidden.name !== cb.name) {
			return;
		}
		function syncHidden() {
			hidden.disabled = cb.checked;
		}
		cb.addEventListener('change', syncHidden);
		syncHidden();
	});

	var previewTitle = root.querySelector('[data-preview-title]');
	var previewType = root.querySelector('[data-preview-type]');
	var previewLoc = root.querySelector('[data-preview-location]');
	var previewUtil = root.querySelector('[data-preview-util]');
	var titleInput = form.querySelector('[data-preview="title"]');
	var typeInput = form.querySelector('[data-preview="type"]');
	var locInput = form.querySelector('[data-preview="location"]');
	var utilInput = form.querySelector('[data-preview="utilization"]');

	function updatePreview() {
		if (previewTitle) {
			previewTitle.textContent = (titleInput && titleInput.value.trim()) || 'Your tool title';
		}
		if (previewType) {
			previewType.textContent = (typeInput && typeInput.value) || 'Mould type';
		}
		if (previewLoc) {
			previewLoc.textContent = (locInput && locInput.value.trim()) || 'Location';
		}
		if (previewUtil) {
			var cav = utilInput && utilInput.value.trim();
			previewUtil.textContent = cav ? cav + ' cavities' : 'Cavities —';
		}
	}

	form.addEventListener('input', updatePreview);
	form.addEventListener('change', updatePreview);

	root.querySelectorAll('[data-guide-toggle]').forEach(function (toggle) {
		var panel = document.getElementById(toggle.getAttribute('aria-controls'));
		if (!panel) {
			return;
		}
		toggle.addEventListener('click', function () {
			var willOpen = toggle.getAttribute('aria-expanded') !== 'true';
			toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
			panel.hidden = !willOpen;
		});
	});

	/* ── Material grade picker ── */
	var gradePicker = document.getElementById('ihAtGradePicker');
	var gradeDropdown = document.getElementById('ihAtGradeDropdown');
	var gradeList = document.getElementById('ihAtGradeList');
	var gradeBrowse = document.getElementById('ihAtGradeBrowse');
	var gradeAdd = document.getElementById('ihAtGradeAdd');
	var gradeChips = document.getElementById('ihAtGradeChips');
	var gradeSearch = document.getElementById('ih_at_material_grade');
	var gradeMaterials = document.getElementById('ihAtGradeMaterials');
	var selectedGrades = [];
	var allGrades = [];

	if (gradePicker) {
		try {
			allGrades = JSON.parse(gradePicker.getAttribute('data-grades') || '[]');
		} catch (err) {
			allGrades = [];
		}
	}

	function setGradeDropdown(open) {
		if (!gradeDropdown) {
			return;
		}
		gradeDropdown.hidden = !open;
		if (gradeBrowse) {
			gradeBrowse.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
		setOverlayState(gradePicker, gradeDropdown, open, open ? gradeList && gradeList.querySelector('button') : null);
	}

	/* Mirror the picker's selected grades into hidden materials[] inputs so the buyer-
	   filterable `materials` column stays populated now the §4 polymer grid is gone. */
	function syncGradeMaterials() {
		if (!gradeMaterials) {
			return;
		}
		gradeMaterials.innerHTML = '';
		selectedGrades.forEach(function (grade) {
			var input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'materials[]';
			input.value = grade;
			gradeMaterials.appendChild(input);
		});
	}

	function syncGradeInput() {
		if (gradeSearch) {
			gradeSearch.value = selectedGrades.join(', ');
			gradeSearch.dispatchEvent(new Event('input', { bubbles: true }));
		}
		syncGradeMaterials();
	}

	function currentGradeFilter() {
		if (!gradeSearch) {
			return '';
		}
		var value = gradeSearch.value || '';
		return selectedGrades.length && value === selectedGrades.join(', ') ? '' : value;
	}

	function renderGradeChips() {
		if (!gradeChips) {
			return;
		}
		gradeChips.innerHTML = '';
		selectedGrades.forEach(function (grade, idx) {
			var chip = document.createElement('span');
			var remove = document.createElement('button');
			chip.className = 'ih-at-grade__chip';
			remove.type = 'button';
			remove.setAttribute('aria-label', 'Remove ' + grade);
			remove.textContent = '\u00d7';
			remove.addEventListener('click', function () {
				selectedGrades.splice(idx, 1);
				syncGradeInput();
				renderGradeChips();
				renderGradeOptions(currentGradeFilter());
				calcStrength();
			});
			chip.appendChild(document.createTextNode(grade + ' '));
			chip.appendChild(remove);
			gradeChips.appendChild(chip);
		});
	}

	function gradeMeta(grade) {
		if (grade && typeof grade === 'object') {
			return { code: grade.code || grade.name || '', name: grade.name || grade.code || '', hint: grade.hint || '' };
		}
		return { code: String(grade), name: String(grade), hint: '' };
	}

	function renderGradeOptions(filter) {
		if (!gradeList) {
			return;
		}
		var q = (filter || '').toLowerCase();
		var pending = [];
		gradeList.innerHTML = '';
		allGrades.forEach(function (grade) {
			var meta = gradeMeta(grade);
			var code = meta.code;
			if (selectedGrades.indexOf(code) !== -1) {
				return;
			}
			if (q && (code + ' ' + meta.name + ' ' + meta.hint).toLowerCase().indexOf(q) === -1) {
				return;
			}
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'ih-at-grade__opt';
			var codeEl = document.createElement('span');
			codeEl.className = 'ih-at-grade__opt-code';
			codeEl.textContent = code;
			var copy = document.createElement('span');
			copy.className = 'ih-at-grade__opt-copy';
			var nameEl = document.createElement('span');
			nameEl.className = 'ih-at-grade__opt-name';
			nameEl.textContent = meta.name;
			copy.appendChild(nameEl);
			if (meta.hint) {
				var hintEl = document.createElement('span');
				hintEl.className = 'ih-at-grade__opt-meta';
				hintEl.textContent = meta.hint;
				copy.appendChild(hintEl);
			}
			var check = document.createElement('span');
			check.className = 'ih-at-grade__opt-check';
			check.setAttribute('aria-hidden', 'true');
			btn.appendChild(codeEl);
			btn.appendChild(copy);
			btn.appendChild(check);
			btn.addEventListener('click', function () {
				var picked = btn.classList.toggle('is-picked');
				var found = pending.indexOf(code);
				if (picked && found === -1) {
					pending.push(code);
				} else if (!picked && found !== -1) {
					pending.splice(found, 1);
				}
				if (gradeAdd) {
					gradeAdd.disabled = pending.length === 0;
					gradeAdd.textContent = 'Add selected (' + pending.length + ')';
					gradeAdd._pending = pending.slice();
				}
			});
			gradeList.appendChild(btn);
		});
		if (gradeAdd) {
			gradeAdd._pending = pending;
			gradeAdd.disabled = pending.length === 0;
			gradeAdd.textContent = 'Add selected (' + pending.length + ')';
		}
	}

	function addTypedGrade() {
		if (!gradeSearch) {
			return;
		}
		var val = gradeSearch.value.trim();
		if (val && selectedGrades.indexOf(val) === -1) {
			selectedGrades.push(val);
			gradeSearch.value = '';
			syncGradeInput();
			renderGradeChips();
			renderGradeOptions('');
			calcStrength();
		}
	}

	if (gradeBrowse && gradeDropdown) {
		registerOverlay(gradePicker, gradeDropdown);
		gradeBrowse.addEventListener('click', function () {
			var willOpen = gradeDropdown.hidden;
			setGradeDropdown(willOpen);
			if (willOpen) {
				renderGradeOptions('');
			}
		});
	}

	if (gradeSearch) {
		if (gradeSearch.value.trim()) {
			selectedGrades = gradeSearch.value.split(',').map(function (grade) {
				return grade.trim();
			}).filter(Boolean);
			renderGradeChips();
			syncGradeMaterials();
		}
		gradeSearch.addEventListener('focus', function () {
			setGradeDropdown(true);
			renderGradeOptions(currentGradeFilter());
		});
		gradeSearch.addEventListener('input', function () {
			setGradeDropdown(true);
			renderGradeOptions(currentGradeFilter());
		});
		gradeSearch.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				addTypedGrade();
			}
			if (e.key === 'Escape') {
				setGradeDropdown(false);
			}
		});
	}

	if (gradeAdd) {
		gradeAdd.addEventListener('click', function () {
			(gradeAdd._pending || []).forEach(function (grade) {
				if (selectedGrades.indexOf(grade) === -1) {
					selectedGrades.push(grade);
				}
			});
			syncGradeInput();
			renderGradeChips();
			renderGradeOptions('');
			setGradeDropdown(false);
			calcStrength();
		});
	}

	document.addEventListener('click', function (e) {
		if (gradePicker && !gradePicker.contains(e.target)) {
			setGradeDropdown(false);
		}
	});

	var uploadDrops = Array.prototype.slice.call(root.querySelectorAll('.ih-at-drop'));

	function setInputFile(input, file) {
		if (!input) {
			return;
		}
		input._ihFile = file || null;
		try {
			var dt = new DataTransfer();
			if (file) {
				dt.items.add(file);
			}
			input.files = dt.files;
		} catch (err) {
			if (!file) {
				input.value = '';
			}
		}
	}

	function renderDropPreview(drop, file) {
		var removeBtn = drop.querySelector('.ih-at-drop__remove');
		var coverBtn = drop.querySelector('.ih-at-drop__cover');
		var input = drop.querySelector('input[type="file"]');
		if (!file || !file.type.match(/^image\//)) {
			drop.style.backgroundImage = '';
			drop.classList.remove('is-filled');
			if (removeBtn) {
				removeBtn.hidden = true;
			}
			if (coverBtn) {
				coverBtn.hidden = true;
			}
			if (input) {
				setInputFile(input, null);
			}
			return;
		}
		var oldUrl = drop.getAttribute('data-preview-url');
		if (oldUrl) {
			URL.revokeObjectURL(oldUrl);
		}
		var url = URL.createObjectURL(file);
		drop.setAttribute('data-preview-url', url);
		drop.style.backgroundImage = 'url(' + url + ')';
		drop.classList.add('is-filled');
		if (removeBtn) {
			removeBtn.hidden = false;
		}
		if (coverBtn) {
			coverBtn.hidden = drop.getAttribute('data-upload-slot') === '1';
		}
		if (input) {
			setInputFile(input, file);
		}
	}

	function swapDropFiles(fromDrop, toDrop) {
		if (!fromDrop || !toDrop || fromDrop === toDrop) {
			return;
		}
		var fromInput = fromDrop.querySelector('input[type="file"]');
		var toInput = toDrop.querySelector('input[type="file"]');
		if (!fromInput || !toInput) {
			return;
		}
		var fromFile = fromInput._ihFile || (fromInput.files && fromInput.files[0]) || null;
		var toFile = toInput._ihFile || (toInput.files && toInput.files[0]) || null;
		renderDropPreview(toDrop, fromFile);
		renderDropPreview(fromDrop, toFile);
		calcStrength();
	}

	uploadDrops.forEach(function (drop, idx) {
		var input = drop.querySelector('input[type="file"]');
		var removeBtn = drop.querySelector('.ih-at-drop__remove');
		if (!input) {
			return;
		}

		['prev', 'next'].forEach(function (direction) {
			var move = document.createElement('button');
			move.type = 'button';
			move.className = 'ih-at-drop__move ih-at-drop__move--' + direction;
			move.setAttribute('aria-label', direction === 'prev' ? 'Move image earlier' : 'Move image later');
			move.textContent = direction === 'prev' ? '‹' : '›';
			move.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				swapDropFiles(drop, uploadDrops[direction === 'prev' ? idx - 1 : idx + 1]);
			});
			drop.appendChild(move);
		});

		function showPreview(file) {
			if (!file || !file.type.match(/^image\//)) {
				return;
			}
			renderDropPreview(drop, file);
			calcStrength();
		}

		input.addEventListener('change', function () {
			if (input.files && input.files[0]) {
				showPreview(input.files[0]);
			}
		});

		if (removeBtn) {
			removeBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				renderDropPreview(drop, null);
				calcStrength();
			});
		}

		var coverBtn = drop.querySelector('.ih-at-drop__cover');
		if (coverBtn) {
			coverBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				swapDropFiles(drop, uploadDrops[0]);
			});
		}

		drop.addEventListener('dragover', function (e) {
			e.preventDefault();
			drop.classList.add('is-drag');
		});
		drop.addEventListener('dragleave', function () {
			drop.classList.remove('is-drag');
		});
		drop.addEventListener('drop', function (e) {
			e.preventDefault();
			drop.classList.remove('is-drag');
			if (e.dataTransfer.files && e.dataTransfer.files[0]) {
				showPreview(e.dataTransfer.files[0]);
			}
		});
	});

	function setFieldError(field, message) {
		if (!field) {
			return null;
		}
		var wrap = field.closest('.ih-at-field') || field.closest('.ih-at-section');
		var errorId = field.getAttribute('aria-describedby');
		var errorEl = null;
		if (errorId) {
			errorId.split(/\s+/).some(function (id) {
				var candidate = document.getElementById(id);
				if (candidate && candidate.classList.contains('ih-at-field-error')) {
					errorEl = candidate;
					return true;
				}
				return false;
			});
		}
		field.setAttribute('aria-invalid', message ? 'true' : 'false');
		if (wrap) {
			wrap.classList.toggle('is-error', !!message);
		}
		if (errorEl) {
			errorEl.textContent = message || errorEl.textContent;
			errorEl.hidden = !message;
		}
		return message ? (wrap || field) : null;
	}

	function parseDateValue(input) {
		if (!input || !input.value) {
			return null;
		}
		var parts = input.value.split('-').map(function (part) {
			return parseInt(part, 10);
		});
		if (parts.length !== 3 || parts.some(isNaN)) {
			return null;
		}
		return new Date(parts[0], parts[1] - 1, parts[2]);
	}

	function todayDate() {
		var now = new Date();
		return new Date(now.getFullYear(), now.getMonth(), now.getDate());
	}

	/* Amber "expiring soon" notice when the listing window is short (≤14 days
	   from the start date, or today if no later start date is set). */
	function updateExpiryHints() {
		if (!expirySoon) {
			return;
		}
		var listingDate = parseDateValue(listingDateInput);
		var expiryDate = parseDateValue(expiryDateInput);
		var base = listingDate && listingDate > todayDate() ? listingDate : todayDate();
		var soon = false;
		if (expiryDate && expiryDate >= todayDate()) {
			var diffDays = Math.round((expiryDate.getTime() - base.getTime()) / 86400000);
			soon = diffDays >= 0 && diffDays <= 14;
		}
		expirySoon.hidden = !soon;
	}

	function validateFormBeforeSubmit() {
		var firstBad = null;
		form.querySelectorAll('[data-vital]').forEach(function (field) {
			var msg = 'Enter a tool title.';
			if (field.name === 'location') {
				msg = 'Enter the tool location.';
			} else if (field.name === 'material_grade') {
				msg = 'Add at least one material grade.';
			}
			var bad = setFieldError(field, isFilled(field) ? '' : msg);
			if (bad && !firstBad) {
				firstBad = bad;
			}
		});

		var listingDate = parseDateValue(listingDateInput);
		var expiryDate = parseDateValue(expiryDateInput);
		var dateMessage = '';
		if (!expiryDate) {
			dateMessage = 'Choose an expiry date.';
		} else if (expiryDate < todayDate()) {
			dateMessage = 'Expiry date must be today or later.';
		} else if (listingDate && expiryDate <= listingDate) {
			dateMessage = 'Expiry date must be after the start date.';
		}
		var dateBad = setFieldError(expiryDateInput, dateMessage);
		if (dateBad && !firstBad) {
			firstBad = dateBad;
		}

		if (imagesError) {
			var hasImage = imageCount() > 0;
			imagesError.hidden = hasImage;
			var imageSection = document.getElementById('ih-at-sec-images');
			if (imageSection) {
				imageSection.classList.toggle('is-error', !hasImage);
			}
			if (!hasImage && !firstBad) {
				firstBad = imageSection || imagesError;
			}
		}
		return firstBad;
	}

	function focusInvalidTarget(target) {
		if (!target) {
			return;
		}
		var badSection = target.closest && target.closest('.ih-at-section');
		if (badSection && isMobileLayout()) {
			accordions.forEach(function (other) {
				other.classList.remove('is-open');
			});
			badSection.classList.add('is-open');
			var toggle = badSection.querySelector('[data-accordion-toggle]');
			if (toggle) {
				toggle.setAttribute('aria-expanded', 'true');
			}
		}
		if (badSection) {
			scrollToSection(badSection.id);
		} else if (target.scrollIntoView) {
			target.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
		var field = target.querySelector && target.querySelector('input, select, textarea, button');
		if (field && field.focus) {
			window.setTimeout(function () {
				field.focus();
			}, 350);
		}
	}

	[listingDateInput, expiryDateInput].forEach(function (field) {
		if (!field) {
			return;
		}
		field.addEventListener('change', function () {
			var listingDate = parseDateValue(listingDateInput);
			var expiryDate = parseDateValue(expiryDateInput);
			if (expiryDate && expiryDate >= todayDate() && (!listingDate || expiryDate > listingDate)) {
				setFieldError(expiryDateInput, '');
			}
			updateExpiryHints();
		});
	});

	var submitting = false;
	form.addEventListener('submit', function (e) {
		if (submitting) {
			e.preventDefault();
			return;
		}

		var isDraft = e.submitter && e.submitter.name === 'save_draft';
		if (!isDraft) {
			var firstBad = validateFormBeforeSubmit();
			if (firstBad) {
				e.preventDefault();
				showToast('Please fix the highlighted fields before submitting.', 'error');
				focusInvalidTarget(firstBad);
				return;
			}
		} else {
			var now = new Date();
			if (lastSaved) {
				lastSaved.textContent = 'Saving draft at ' + formatTime(now);
			}
			showToast('Saving draft...', 'success');
		}

		submitting = true;
		form.classList.add('is-submitting');
		if (e.submitter) {
			e.submitter.classList.add('is-loading');
			e.submitter.setAttribute('aria-busy', 'true');
			if (isDraft) {
				e.submitter.dataset.originalText = e.submitter.textContent;
				e.submitter.textContent = 'Saving...';
			}
		}
	});

	calcStrength();
	updatePreview();
	updateExpiryHints();
	syncMobileAccordions();
})();
