/**
 * Add Machine listing form — stepper, listing strength, live preview, uploads.
 */
(function () {
	'use strict';

	var root = document.getElementById('ihAddMachineRoot');
	if (!root || root.dataset.bound) {
		return;
	}
	root.dataset.bound = '1';

	var form = document.getElementById('ihAddMachineForm');
	if (!form) {
		return;
	}
	root.classList.add('is-enhanced');

	var sections = Array.prototype.slice.call(root.querySelectorAll('.ih-am-section[id]'));
	var steps = Array.prototype.slice.call(root.querySelectorAll('.ih-am-step'));
	var strengthPct = document.getElementById('ihAmStrengthPct');
	var strengthLabel = document.getElementById('ihAmStrengthLabel');
	var strengthFg = root.querySelector('.ih-am-strength__fg');
	var strengthImgTip = document.getElementById('ihAmStrengthImgTip');
	var mobileProgressPct = document.getElementById('ihAmMobileProgressPct');
	var mobileProgressFill = document.getElementById('ihAmMobileProgressFill');
	var mobileStepLabel = document.getElementById('ihAmMobileStepLabel');
	var mobileStrengthTip = document.getElementById('ihAmMobileStrengthTip');
	var accordions = Array.prototype.slice.call(root.querySelectorAll('.ih-am-accordion[data-mobile-step]'));
	var mobileSections = accordions.slice();
	var certHidden = document.getElementById('ihAmCertificationsHidden');
	var formToast = document.getElementById('ihAmFormToast');
	var lastSaved = document.getElementById('ihAmLastSaved');
	var strengthLive = document.getElementById('ihAmStrengthLive');
	var strengthItems = {
		identity: document.getElementById('ihAmStrengthIdentity'),
		specs: document.getElementById('ihAmStrengthSpecs'),
		production: document.getElementById('ihAmStrengthProduction'),
		certs: document.getElementById('ihAmStrengthCerts'),
		images: document.getElementById('ihAmStrengthImgTip')
	};
	var listingDateInput = document.getElementById('ih_am_listing_date');
	var expiryDateInput = document.getElementById('ih_am_expiry_date');
	var imagesError = document.getElementById('ih_am_images_error');
	var toastTimer = null;
	var overlayPairs = [];
	var overlayFrame = null;

	var mobileStepNames = {
		'ih-am-sec-identity': 'Identity',
		'ih-am-sec-specs': 'Specs',
		'ih-am-sec-capability': 'Capability',
		'ih-am-sec-production': 'Production',
		'ih-am-sec-quality': 'Quality',
		'ih-am-sec-images': 'Images'
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
		var body = acc.querySelector('.ih-am-accordion__body');
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

	function syncCertifications() {
		var certs = [];
		form.querySelectorAll('[data-strength-cert]:checked').forEach(function (cb) {
			certs.push(cb.value);
		});
		if (certHidden) {
			certHidden.value = certs.join(', ');
		}
	}

	function showToast(message, type) {
		if (!formToast) {
			return;
		}
		window.clearTimeout(toastTimer);
		formToast.textContent = message;
		formToast.className = 'ih-am-toast' + (type ? ' is-' + type : '');
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
		var dot = item.querySelector('.ih-am-strength__dot');
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
			var check = acc.querySelector('.ih-am-accordion__check');
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
		var section = picker && picker.closest('.ih-am-section');
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
			var drop = el.closest('.ih-am-drop');
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
		if (el.classList && el.classList.contains('ih-am-drop')) {
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
			var label = input.closest('.ih-am-drop');
			if (label && label.classList.contains('is-filled')) {
				count++;
			}
		});
		return count;
	}

	function calcStrength() {
		syncCertifications();

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
		var identityDone = fieldHasValue('title') && fieldHasValue('location');
		var specsDone = fieldHasValue('clamping_force') && fieldHasValue('shot_size') && fieldHasValue('tie_bar_spacing');
		var productionDone = fieldHasValue('batch_size') || fieldHasValue('min_order_qty') || fieldHasValue('max_monthly_output') || fieldHasValue('avg_cycle_time') || fieldHasValue('operating_hours') || fieldHasValue('utilization');
		var certsDone = checkedAny('[data-strength-cert]') || fieldHasValue('qc_tools');

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
			var imageSection = document.getElementById('ih-am-sec-images');
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
			var wrap = field.closest('.ih-am-field');
			if (wrap) {
				wrap.classList.remove('is-error');
				var error = wrap.querySelector('.ih-am-field-error');
				if (error) {
					error.hidden = true;
				}
			}
		});
	});

	root.querySelectorAll('.ih-am-toggle-row .ih-am-toggle-cb').forEach(function (cb) {
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

	root.querySelectorAll('.ih-am-check input[type="checkbox"][data-strength]').forEach(function (cb) {
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
			previewTitle.textContent = (titleInput && titleInput.value.trim()) || 'Your machine title';
		}
		if (previewType) {
			previewType.textContent = (typeInput && typeInput.value) || 'Clamp drive';
		}
		if (previewLoc) {
			previewLoc.textContent = (locInput && locInput.value.trim()) || 'Location';
		}
		if (previewUtil) {
			var util = utilInput && utilInput.value.trim();
			if (util) {
				previewUtil.textContent = 'Utilization ' + (util.indexOf('%') !== -1 ? util : util + '%');
			} else {
				previewUtil.textContent = 'Utilization —';
			}
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
	var gradePicker = document.getElementById('ihAmGradePicker');
	var gradeDropdown = document.getElementById('ihAmGradeDropdown');
	var gradeList = document.getElementById('ihAmGradeList');
	var gradeBrowse = document.getElementById('ihAmGradeBrowse');
	var gradeAdd = document.getElementById('ihAmGradeAdd');
	var gradeChips = document.getElementById('ihAmGradeChips');
	var gradeSearch = document.getElementById('ih_am_material_grade');
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

	function syncGradeInput() {
		if (gradeSearch) {
			gradeSearch.value = selectedGrades.join(', ');
			gradeSearch.dispatchEvent(new Event('input', { bubbles: true }));
		}
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
			chip.className = 'ih-am-grade__chip';
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

	function renderGradeOptions(filter) {
		if (!gradeList) {
			return;
		}
		var q = (filter || '').toLowerCase();
		var pending = [];
		gradeList.innerHTML = '';
		allGrades.forEach(function (grade) {
			if (selectedGrades.indexOf(grade) !== -1) {
				return;
			}
			if (q && grade.toLowerCase().indexOf(q) === -1) {
				return;
			}
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'ih-am-grade__opt';
			btn.textContent = grade;
			btn.addEventListener('click', function () {
				var picked = btn.classList.toggle('is-picked');
				var found = pending.indexOf(grade);
				if (picked && found === -1) {
					pending.push(grade);
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

	/* ── Materials chip selector ── */
	var materialsPicker = document.getElementById('ihAmMaterialsPicker');
	var materialsControl = document.getElementById('ihAmMaterialsControl');
	var materialsDropdown = document.getElementById('ihAmMaterialsDropdown');
	var materialsChips = document.getElementById('ihAmMaterialsChips');
	var materialsHidden = document.getElementById('ihAmMaterialsHidden');
	var materialsStrength = document.getElementById('ihAmMaterialsStrength');
	var materialsSearch = document.getElementById('ih_am_material_search');
	var materialsCustomInput = document.getElementById('ih_am_material_grade');
	var materialsAddCustom = document.getElementById('ihAmMaterialsAddCustom');
	var materialOptions = materialsPicker ? Array.prototype.slice.call(materialsPicker.querySelectorAll('[data-material-option]')) : [];
	var materialGroups = materialsPicker ? Array.prototype.slice.call(materialsPicker.querySelectorAll('[data-material-group]')) : [];
	var customMaterials = [];
	var materialSelectionOrder = [];
	var activeMaterialDrag = null;

	function materialDisplayLabel(cb) {
		return cb.getAttribute('data-material-label') || cb.value || '';
	}

	function selectedMaterialOptions() {
		return materialOptions.filter(function (cb) {
			return cb.checked;
		});
	}

	function materialOptionByValue(value) {
		var normalized = normalizeMaterial(value).toLowerCase();
		return materialOptions.filter(function (cb) {
			return normalizeMaterial(cb.value).toLowerCase() === normalized;
		})[0] || null;
	}

	function normalizeMaterial(value) {
		return String(value || '').trim().replace(/\s+/g, ' ');
	}

	function materialValueKey(value) {
		return normalizeMaterial(value).toLowerCase();
	}

	function materialValueInList(list, value) {
		var key = materialValueKey(value);
		return list.some(function (item) {
			return materialValueKey(item) === key;
		});
	}

	function addMaterialToOrder(value) {
		value = normalizeMaterial(value);
		if (value && !materialValueInList(materialSelectionOrder, value)) {
			materialSelectionOrder.push(value);
		}
	}

	function removeMaterialFromOrder(value) {
		var key = materialValueKey(value);
		materialSelectionOrder = materialSelectionOrder.filter(function (item) {
			return materialValueKey(item) !== key;
		});
	}

	function selectedMaterialValues() {
		var selectedStandard = selectedMaterialOptions().map(function (cb) {
			return cb.value;
		});
		var selected = [];

		materialSelectionOrder.forEach(function (value) {
			var standard = materialOptionByValue(value);
			if ((standard && standard.checked) || materialValueInList(customMaterials, value)) {
				if (!materialValueInList(selected, value)) {
					selected.push(value);
				}
			}
		});
		selectedStandard.forEach(function (value) {
			if (!materialValueInList(selected, value)) {
				selected.push(value);
			}
		});
		customMaterials.forEach(function (value) {
			if (!materialValueInList(selected, value)) {
				selected.push(value);
			}
		});
		materialSelectionOrder = selected.slice();
		return selected;
	}

	function customMaterialsInSelectionOrder() {
		var values = selectedMaterialValues().filter(function (value) {
			return !materialOptionByValue(value) && materialValueInList(customMaterials, value);
		});
		return values;
	}

	function syncMaterialHiddenInputs(values) {
		if (!materialsHidden) {
			return;
		}
		materialsHidden.innerHTML = '';
		values.forEach(function (value) {
			var input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'materials[]';
			input.value = value;
			materialsHidden.appendChild(input);
		});
	}

	function materialOptionMatches(cb, query) {
		var q = normalizeMaterial(query).toLowerCase();
		if (!q) {
			return true;
		}
		return (
			String(cb.value || '').toLowerCase().indexOf(q) !== -1 ||
			materialDisplayLabel(cb).toLowerCase().indexOf(q) !== -1 ||
			String(cb.closest('.ih-am-materials__option').textContent || '').toLowerCase().indexOf(q) !== -1
		);
	}

	function filterMaterialOptions() {
		var query = materialsSearch ? materialsSearch.value : '';
		materialOptions.forEach(function (cb) {
			var label = cb.closest('.ih-am-materials__option');
			if (label) {
				label.hidden = !materialOptionMatches(cb, query);
			}
		});
		materialGroups.forEach(function (group) {
			var visibleOptions = Array.prototype.slice.call(group.querySelectorAll('.ih-am-materials__option')).filter(function (option) {
				return !option.hidden;
			});
			group.hidden = visibleOptions.length === 0;
		});
	}

	function setMaterialsDropdown(open) {
		if (!materialsDropdown) {
			return;
		}
		materialsDropdown.hidden = !open;
		if (materialsPicker) {
			materialsPicker.classList.toggle('is-open', open);
		}
		if (materialsControl) {
			materialsControl.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
		if (open) {
			filterMaterialOptions();
		}
		setOverlayState(materialsPicker, materialsDropdown, open, open ? materialsSearch : null);
	}

	function syncMaterialsPicker() {
		if (!materialsChips) {
			return;
		}
		var selected = selectedMaterialValues();
		var orderedCustom = customMaterialsInSelectionOrder();
		materialsChips.innerHTML = '';

		if (materialsStrength) {
			materialsStrength.value = selected.join(', ');
		}

		if (materialsCustomInput) {
			materialsCustomInput.value = orderedCustom.join(', ');
		}
		customMaterials = orderedCustom.slice();
		syncMaterialHiddenInputs(selected);

		materialOptions.forEach(function (cb) {
			var label = cb.closest('.ih-am-materials__option');
			if (label) {
				label.classList.toggle('is-selected', cb.checked);
			}
		});

		selected.forEach(function (material, idx) {
			var cb = materialOptionByValue(material);
			var chip = document.createElement('span');
			var handle = document.createElement('button');
			var labelText = document.createElement('span');
			var moveEarlier = document.createElement('button');
			var moveLater = document.createElement('button');
			var remove = document.createElement('button');
			var displayLabel = cb ? materialDisplayLabel(cb) : material;

			chip.className = 'ih-am-materials__chip' + (cb ? '' : ' ih-am-materials__chip--custom');
			chip.setAttribute('data-material-chip', material);
			chip.setAttribute('draggable', 'false');
			handle.type = 'button';
			handle.className = 'ih-am-materials__drag';
			handle.setAttribute('aria-label', 'Drag to reorder ' + displayLabel);
			handle.textContent = '::';
			labelText.className = 'ih-am-materials__chip-label';
			labelText.textContent = displayLabel;
			moveEarlier.type = 'button';
			moveEarlier.className = 'ih-am-materials__move ih-am-materials__move--earlier';
			moveEarlier.disabled = idx === 0;
			moveEarlier.setAttribute('aria-label', 'Move ' + displayLabel + ' earlier');
			moveEarlier.textContent = '<';
			moveLater.type = 'button';
			moveLater.className = 'ih-am-materials__move ih-am-materials__move--later';
			moveLater.disabled = idx === selected.length - 1;
			moveLater.setAttribute('aria-label', 'Move ' + displayLabel + ' later');
			moveLater.textContent = '>';
			remove.type = 'button';
			remove.className = 'ih-am-materials__remove';
			remove.setAttribute('aria-label', 'Remove ' + displayLabel);
			remove.textContent = '\u00d7';
			chip.addEventListener('click', function (e) {
				e.stopPropagation();
			});
			handle.addEventListener('pointerdown', function (e) {
				startMaterialDrag(e, material, chip);
			});
			moveEarlier.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				moveMaterial(material, -1);
			});
			moveLater.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				moveMaterial(material, 1);
			});
			remove.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				if (cb) {
					cb.checked = false;
					removeMaterialFromOrder(material);
					cb.dispatchEvent(new Event('change', { bubbles: true }));
					return;
				}
				customMaterials = customMaterials.filter(function (item) {
					return materialValueKey(item) !== materialValueKey(material);
				});
				removeMaterialFromOrder(material);
				syncMaterialsPicker();
				calcStrength();
			});

			chip.appendChild(handle);
			chip.appendChild(labelText);
			chip.appendChild(moveEarlier);
			chip.appendChild(moveLater);
			chip.appendChild(remove);
			materialsChips.appendChild(chip);
		});

		if (!selected.length) {
			var placeholder = document.createElement('span');
			placeholder.className = 'ih-am-materials__placeholder';
			placeholder.textContent = '+ Add materials...';
			materialsChips.appendChild(placeholder);
		}
	}

	function moveMaterial(material, direction) {
		var values = selectedMaterialValues();
		var index = values.findIndex(function (value) {
			return materialValueKey(value) === materialValueKey(material);
		});
		var nextIndex = index + direction;
		if (index < 0 || nextIndex < 0 || nextIndex >= values.length) {
			return;
		}
		values.splice(index, 1);
		values.splice(nextIndex, 0, material);
		materialSelectionOrder = values;
		syncMaterialsPicker();
	}

	function moveMaterialToIndex(material, index) {
		var values = selectedMaterialValues();
		var current = values.findIndex(function (value) {
			return materialValueKey(value) === materialValueKey(material);
		});
		if (current < 0) {
			return;
		}
		var item = values.splice(current, 1)[0];
		if (index > current) {
			index -= 1;
		}
		index = Math.max(0, Math.min(index, values.length));
		values.splice(index, 0, item);
		materialSelectionOrder = values;
		syncMaterialsPicker();
		var dragged = Array.prototype.slice.call(materialsChips.querySelectorAll('[data-material-chip]')).filter(function (chip) {
			return materialValueKey(chip.getAttribute('data-material-chip')) === materialValueKey(item);
		})[0];
		if (dragged) {
			dragged.classList.add('is-dragging');
		}
	}

	function startMaterialDrag(e, material, chip) {
		if (e.button !== undefined && e.button !== 0) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		activeMaterialDrag = {
			value: material,
			pointerId: e.pointerId
		};
		chip.classList.add('is-dragging');
		document.body.classList.add('ih-am-materials-dragging');
		document.addEventListener('pointermove', onMaterialDragMove);
		document.addEventListener('pointerup', endMaterialDrag);
		document.addEventListener('pointercancel', endMaterialDrag);
	}

	function onMaterialDragMove(e) {
		if (!activeMaterialDrag || e.pointerId !== activeMaterialDrag.pointerId) {
			return;
		}
		var target = document.elementFromPoint(e.clientX, e.clientY);
		var targetChip = target && target.closest ? target.closest('[data-material-chip]') : null;
		if (!targetChip || !materialsChips.contains(targetChip)) {
			return;
		}
		var targetValue = targetChip.getAttribute('data-material-chip');
		if (!targetValue || materialValueKey(targetValue) === materialValueKey(activeMaterialDrag.value)) {
			return;
		}
		var chips = Array.prototype.slice.call(materialsChips.querySelectorAll('[data-material-chip]'));
		var targetIndex = chips.indexOf(targetChip);
		var rect = targetChip.getBoundingClientRect();
		var verticalIntent = Math.abs(e.clientY - (rect.top + rect.height / 2)) > rect.height / 3;
		var insertAfter = verticalIntent ? e.clientY > rect.top + rect.height / 2 : e.clientX > rect.left + rect.width / 2;
		moveMaterialToIndex(activeMaterialDrag.value, targetIndex + (insertAfter ? 1 : 0));
	}

	function endMaterialDrag(e) {
		if (!activeMaterialDrag || (e && e.pointerId !== activeMaterialDrag.pointerId)) {
			return;
		}
		activeMaterialDrag = null;
		document.body.classList.remove('ih-am-materials-dragging');
		document.removeEventListener('pointermove', onMaterialDragMove);
		document.removeEventListener('pointerup', endMaterialDrag);
		document.removeEventListener('pointercancel', endMaterialDrag);
		Array.prototype.slice.call(materialsChips.querySelectorAll('.is-dragging')).forEach(function (chip) {
			chip.classList.remove('is-dragging');
		});
	}

	function addCustomMaterial() {
		if (!materialsSearch) {
			return;
		}
		var value = normalizeMaterial(materialsSearch.value);
		if (!value) {
			return;
		}
		var duplicateOption = materialOptions.filter(function (cb) {
			return cb.value.toLowerCase() === value.toLowerCase() || materialDisplayLabel(cb).toLowerCase() === value.toLowerCase();
		})[0];
		if (duplicateOption) {
			duplicateOption.checked = true;
			addMaterialToOrder(duplicateOption.value);
			materialsSearch.value = '';
			filterMaterialOptions();
			syncMaterialsPicker();
			calcStrength();
			return;
		}
		var duplicateCustom = customMaterials.some(function (material) {
			return material.toLowerCase() === value.toLowerCase();
		});
		if (!duplicateCustom) {
			customMaterials.push(value);
			addMaterialToOrder(value);
			materialsSearch.value = '';
			filterMaterialOptions();
			syncMaterialsPicker();
			calcStrength();
		}
	}

	if (materialsPicker && materialsDropdown && materialsControl) {
		registerOverlay(materialsPicker, materialsDropdown);
		setMaterialsDropdown(false);
		if (materialsCustomInput && materialsCustomInput.value.trim()) {
			customMaterials = materialsCustomInput.value.split(',').map(normalizeMaterial).filter(Boolean);
		}
		syncMaterialsPicker();

		materialsControl.addEventListener('click', function () {
			var willOpen = materialsDropdown.hidden;
			setMaterialsDropdown(willOpen);
			if (willOpen && materialsSearch) {
				materialsSearch.focus();
			}
		});

		materialsControl.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				setMaterialsDropdown(true);
				if (materialsSearch) {
					materialsSearch.focus();
				}
			}
			if (e.key === 'Escape') {
				setMaterialsDropdown(false);
			}
		});

		materialOptions.forEach(function (cb) {
			cb.addEventListener('change', function () {
				if (cb.checked) {
					addMaterialToOrder(cb.value);
				} else {
					removeMaterialFromOrder(cb.value);
				}
				syncMaterialsPicker();
				calcStrength();
			});
			cb.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') {
					setMaterialsDropdown(false);
					materialsControl.focus();
				}
			});
		});

		if (materialsSearch) {
			materialsSearch.addEventListener('input', filterMaterialOptions);
			materialsSearch.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					addCustomMaterial();
				}
				if (e.key === 'Escape') {
					setMaterialsDropdown(false);
					materialsControl.focus();
				}
			});
		}

		if (materialsAddCustom) {
			materialsAddCustom.addEventListener('click', addCustomMaterial);
		}

		document.addEventListener('click', function (e) {
			if (!materialsPicker.contains(e.target)) {
				setMaterialsDropdown(false);
			}
		});
	}

	/* ── Certification / QC chip selectors ── */
	function initCheckboxChipSelect(config) {
		var picker = document.getElementById(config.pickerId);
		var control = document.getElementById(config.controlId);
		var dropdown = document.getElementById(config.dropdownId);
		var chips = document.getElementById(config.chipsId);
		if (!picker || !control || !dropdown || !chips) {
			return;
		}
		var options = Array.prototype.slice.call(picker.querySelectorAll('[data-chip-option]'));

		function setOpen(open) {
			dropdown.hidden = !open;
			picker.classList.toggle('is-open', open);
			control.setAttribute('aria-expanded', open ? 'true' : 'false');
			setOverlayState(picker, dropdown, open, null);
		}

		function render() {
			chips.innerHTML = '';
			var selected = options.filter(function (cb) {
				return cb.checked;
			});
			options.forEach(function (cb) {
				var label = cb.closest('.ih-am-chipselect__option');
				if (label) {
					label.classList.toggle('is-selected', cb.checked);
				}
			});
			selected.forEach(function (cb) {
				var labelText = cb.getAttribute('data-chip-label') || cb.value;
				var chip = document.createElement('span');
				var remove = document.createElement('button');
				chip.className = 'ih-am-chipselect__chip';
				remove.type = 'button';
				remove.className = 'ih-am-chipselect__remove';
				remove.setAttribute('aria-label', 'Remove ' + labelText);
				remove.textContent = '\u00d7';
				remove.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					cb.checked = false;
					cb.dispatchEvent(new Event('change', { bubbles: true }));
				});
				chip.appendChild(document.createTextNode(labelText + ' '));
				chip.appendChild(remove);
				chips.appendChild(chip);
			});
			if (!selected.length) {
				var placeholder = document.createElement('span');
				placeholder.className = 'ih-am-chipselect__placeholder';
				placeholder.textContent = '+ Add certifications...';
				chips.appendChild(placeholder);
			}
			syncCertifications();
		}

		control.addEventListener('click', function () {
			setOpen(dropdown.hidden);
		});
		control.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
				e.preventDefault();
				setOpen(true);
				window.requestAnimationFrame(function () {
					var first = dropdown.querySelector('input, button');
					if (first) {
						first.focus();
					}
				});
			}
			if (e.key === 'Escape') {
				setOpen(false);
				control.focus();
			}
		});
		options.forEach(function (cb) {
			cb.addEventListener('change', function () {
				render();
				calcStrength();
			});
			cb.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') {
					setOpen(false);
					control.focus();
				}
			});
		});
		document.addEventListener('click', function (e) {
			if (!picker.contains(e.target)) {
				setOpen(false);
			}
		});
		registerOverlay(picker, dropdown);
		setOpen(false);
		render();
	}

	initCheckboxChipSelect({
		pickerId: 'ihAmCertPicker',
		controlId: 'ihAmCertControl',
		dropdownId: 'ihAmCertDropdown',
		chipsId: 'ihAmCertChips'
	});

	(function initQcChipSelect() {
		var picker = document.getElementById('ihAmQcPicker');
		var control = document.getElementById('ihAmQcControl');
		var dropdown = document.getElementById('ihAmQcDropdown');
		var input = document.getElementById('ih_am_qc');
		var chips = document.getElementById('ihAmQcChips');
		if (!picker || !control || !dropdown || !input || !chips) {
			return;
		}
		var optionButtons = Array.prototype.slice.call(picker.querySelectorAll('[data-qc-option]'));
		var selected = [];

		function normalize(value) {
			return String(value || '').trim().replace(/\s+/g, ' ');
		}

		function parseInput() {
			selected = input.value.split(',').map(normalize).filter(Boolean);
		}

		function syncInput() {
			input.value = selected.join(', ');
			input.dispatchEvent(new Event('input', { bubbles: true }));
		}

		function setOpen(open) {
			dropdown.hidden = !open;
			picker.classList.toggle('is-open', open);
			control.setAttribute('aria-expanded', open ? 'true' : 'false');
			setOverlayState(picker, dropdown, open, open ? input : null);
		}

		function render() {
			chips.innerHTML = '';
			optionButtons.forEach(function (btn) {
				btn.classList.toggle('is-selected', selected.indexOf(btn.getAttribute('data-qc-option')) !== -1);
			});
			selected.forEach(function (tool, idx) {
				var chip = document.createElement('span');
				var remove = document.createElement('button');
				chip.className = 'ih-am-chipselect__chip';
				remove.type = 'button';
				remove.className = 'ih-am-chipselect__remove';
				remove.setAttribute('aria-label', 'Remove ' + tool);
				remove.textContent = '\u00d7';
				remove.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					selected.splice(idx, 1);
					syncInput();
					render();
					calcStrength();
				});
				chip.appendChild(document.createTextNode(tool + ' '));
				chip.appendChild(remove);
				chips.appendChild(chip);
			});
		}

		function addTool(value) {
			var tool = normalize(value);
			if (!tool) {
				return;
			}
			if (!selected.some(function (item) { return item.toLowerCase() === tool.toLowerCase(); })) {
				selected.push(tool);
			}
			input.value = '';
			syncInput();
			render();
			calcStrength();
		}

		parseInput();
		render();
		control.addEventListener('click', function () {
			setOpen(true);
			input.focus();
		});
		control.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
				e.preventDefault();
				setOpen(true);
				input.focus();
			}
			if (e.key === 'Escape') {
				setOpen(false);
				control.focus();
			}
		});
		input.addEventListener('focus', function () {
			setOpen(true);
		});
		input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ',') {
				e.preventDefault();
				addTool(input.value);
			}
			if (e.key === 'Escape') {
				setOpen(false);
				control.focus();
			}
		});
		input.addEventListener('blur', function () {
			if (input.value.trim()) {
				addTool(input.value);
			}
		});
		optionButtons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				addTool(btn.getAttribute('data-qc-option'));
			});
			btn.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') {
					setOpen(false);
					control.focus();
				}
			});
		});
		document.addEventListener('click', function (e) {
			if (!picker.contains(e.target)) {
				setOpen(false);
			}
		});
		registerOverlay(picker, dropdown);
		setOpen(false);
	})();

	var uploadDrops = Array.prototype.slice.call(root.querySelectorAll('.ih-am-drop'));

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
		var removeBtn = drop.querySelector('.ih-am-drop__remove');
		var coverBtn = drop.querySelector('.ih-am-drop__cover');
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
		var removeBtn = drop.querySelector('.ih-am-drop__remove');
		if (!input) {
			return;
		}

		['prev', 'next'].forEach(function (direction) {
			var move = document.createElement('button');
			move.type = 'button';
			move.className = 'ih-am-drop__move ih-am-drop__move--' + direction;
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

		var coverBtn = drop.querySelector('.ih-am-drop__cover');
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
		var wrap = field.closest('.ih-am-field') || field.closest('.ih-am-section');
		var errorId = field.getAttribute('aria-describedby');
		var errorEl = null;
		if (errorId) {
			errorId.split(/\s+/).some(function (id) {
				var candidate = document.getElementById(id);
				if (candidate && candidate.classList.contains('ih-am-field-error')) {
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

	function validateFormBeforeSubmit() {
		var firstBad = null;
		form.querySelectorAll('[data-vital]').forEach(function (field) {
			var bad = setFieldError(field, isFilled(field) ? '' : (field.name === 'location' ? 'Enter the machine location.' : 'Enter a machine brand or title.'));
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
		} else if (listingDate && expiryDate < listingDate) {
			dateMessage = 'Expiry date must be on or after the listing date.';
		}
		var dateBad = setFieldError(expiryDateInput, dateMessage);
		if (dateBad && !firstBad) {
			firstBad = dateBad;
		}

		if (imagesError) {
			var hasImage = imageCount() > 0;
			imagesError.hidden = hasImage;
			var imageSection = document.getElementById('ih-am-sec-images');
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
		var badSection = target.closest && target.closest('.ih-am-section');
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
			if (expiryDate && expiryDate >= todayDate() && (!listingDate || expiryDate >= listingDate)) {
				setFieldError(expiryDateInput, '');
			}
		});
	});

	var submitting = false;
	form.addEventListener('submit', function (e) {
		if (submitting) {
			e.preventDefault();
			return;
		}

		syncCertifications();

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
	syncMobileAccordions();
})();
