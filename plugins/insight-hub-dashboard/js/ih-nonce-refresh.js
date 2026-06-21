/**
 * ih-nonce-refresh.js — keep long-open add/edit forms saveable.
 *
 * A nonce-protected form (add/edit machine + tool, admin + owner) left open past a
 * WordPress nonce tick (~12h) would otherwise fail its save with a silent
 * "Security check failed" and lose the user's input. This shared helper:
 *
 *   1. PROACTIVELY refreshes the in-page nonce field on an interval + when the tab
 *      regains focus, so the field is (almost) always current.
 *   2. On submit, transparently refreshes the nonce ONCE and retries the submit a
 *      single time (the forms post synchronously, so we refresh -> update the hidden
 *      field -> native re-submit). If the refresh fails (e.g. the user logged out)
 *      it surfaces a clear "session expired" banner instead of silently losing data.
 *
 * Forms opt in with: <form data-ih-nonce-refresh
 *                           data-ih-nonce-action="ih_user_add_machine"
 *                           data-ih-nonce-field="ih_user_nonce"> ...
 * The refresh endpoint (ih_refresh_nonce) is authenticated via the logged-in cookie.
 */
(function () {
	'use strict';

	var CFG = window.IH_NONCE_REFRESH || {};
	var ajaxUrl = CFG.ajaxUrl || (window.ajaxurl || '');
	if (!ajaxUrl) { return; }

	var forms = Array.prototype.slice.call(document.querySelectorAll('form[data-ih-nonce-refresh]'));
	if (!forms.length) { return; }

	var REFRESH_MS = 10 * 60 * 1000; // refresh well within a nonce tick

	function nonceField(form) {
		var name = form.getAttribute('data-ih-nonce-field') || '';
		if (!name) { return null; }
		return form.querySelector('input[name="' + name + '"]');
	}

	/* POST ih_refresh_nonce for this form's action; on success write the fresh
	 * value into the hidden nonce field. Resolves true on success, false otherwise. */
	function refreshForm(form) {
		var action = form.getAttribute('data-ih-nonce-action') || 'ih_nonce';
		var fd = new FormData();
		fd.append('action', 'ih_refresh_nonce');
		fd.append('nonce_action', action);
		return fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json().catch(function () { return null; }); })
			.then(function (d) {
				if (d && d.success && d.data && d.data.nonce) {
					var field = nonceField(form);
					if (field) { field.value = d.data.nonce; }
					return true;
				}
				return false;
			})
			.catch(function () { return false; });
	}

	function showExpired(form) {
		var id = 'ihNonceExpired';
		var banner = document.getElementById(id);
		if (!banner) {
			banner = document.createElement('div');
			banner.id = id;
			banner.setAttribute('role', 'alert');
			banner.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:99999;max-width:92vw;background:#7f1d1d;color:#fff;padding:12px 16px;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.25);font:500 14px/1.4 system-ui,sans-serif;';
			document.body.appendChild(banner);
		}
		banner.textContent = 'Your session expired — please refresh the page or log in again. Your entries are still here, so reload in a new tab to sign back in before saving.';
		// Restore any loading/submitting visual state so the user can act again.
		form.classList.remove('is-submitting');
		Array.prototype.slice.call(form.querySelectorAll('[aria-busy="true"], .is-loading')).forEach(function (el) {
			el.classList.remove('is-loading');
			el.removeAttribute('aria-busy');
			el.disabled = false;
		});
	}

	forms.forEach(function (form) {
		// 2) Transparent refresh-and-retry on submit (single attempt).
		form.addEventListener('submit', function (e) {
			// Another handler (client validation) already blocked it — respect that.
			if (e.defaultPrevented) { return; }
			// Let native HTML5 validation surface first; don't intercept invalid forms.
			if (typeof form.checkValidity === 'function' && !form.checkValidity()) { return; }
			// Our own re-submit pass — let it through.
			if (form._ihNonceOK) { form._ihNonceOK = false; return; }

			var submitter = e.submitter || (document.activeElement && form.contains(document.activeElement) ? document.activeElement : null);
			e.preventDefault();

			refreshForm(form).then(function (ok) {
				if (!ok) { showExpired(form); return; }
				// Preserve the activating submit button (e.g. name="save_draft") that a
				// native form.submit() would otherwise drop.
				if (submitter && submitter.name) {
					var prior = form.querySelector('input[type="hidden"][data-ih-submitter]');
					if (prior) { prior.parentNode.removeChild(prior); }
					var h = document.createElement('input');
					h.type = 'hidden';
					h.name = submitter.name;
					h.value = submitter.value || '1';
					h.setAttribute('data-ih-submitter', '1');
					form.appendChild(h);
				}
				form._ihNonceOK = true;
				form.submit(); // native submit: no listeners, no re-validation loop
			});
		});
	});

	// 1) Proactive background refresh keeps the field fresh without user action.
	function refreshAll() { forms.forEach(refreshForm); }
	var timer = window.setInterval(refreshAll, REFRESH_MS);
	var lastVisible = Date.now();
	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState !== 'visible') { return; }
		if (Date.now() - lastVisible > 60 * 1000) { refreshAll(); }
		lastVisible = Date.now();
	});
	window.addEventListener('pagehide', function () { window.clearInterval(timer); });
})();
