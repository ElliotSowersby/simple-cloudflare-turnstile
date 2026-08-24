/**
 * Cloudflare Turnstile support for Elementor v4 (atomic) forms.
 *
 * Elementor v4 forms differ from the v3 Pro form widget in three ways that matter here:
 *
 *   1. Markup    - the form is `[data-element_type="e-form"]`, it does NOT carry the
 *                  `.elementor-form` class the v3 integration relies on.
 *   2. Submit    - submission is handled by Alpine (`x-on:submit`), which calls
 *                  preventDefault() and posts a hand-built FormData via fetch().
 *   3. Fields    - only `input|textarea|select[data-interaction-id]` elements are collected
 *                  into `form_fields[]`. A plain hidden input is therefore never sent.
 *
 * Because of (3) we tag Turnstile's own hidden response input with a `data-interaction-id`,
 * so the token travels inside `form_fields[]` without patching fetch() or Alpine. The PHP
 * side removes that entry again before Elementor stores or emails the submission.
 */
(function () {
	'use strict';

	var FORM_SELECTOR = '[data-element_type="e-form"]';
	// Elementor's own handler toggles exactly this set while a submission is in flight,
	// so we use the same selector for enabling/disabling and stay in sync with it.
	var SUBMIT_SELECTOR = 'button[type="submit"], input[type="submit"]';
	// Base class of the atomic submit-button widget. Preferred as the insertion anchor
	// because it identifies the real submit widget even when a nested component happens
	// to contain another submit button.
	var SUBMIT_ANCHOR = '.e-form-submit-button-base';
	var TOKEN_NAME = 'cf-turnstile-response';
	var FAILSAFE_NAME = 'cfturnstile_failsafe';
	var PROCESSED_CLASS = 'cft-atomic-processed';

	function getSettings() {
		return window.cfturnstileElementorSettings || {};
	}

	function setSubmitEnabled(form, enabled) {
		if (!form) {
			return;
		}
		form.querySelectorAll(SUBMIT_SELECTOR).forEach(function (button) {
			button.style.pointerEvents = enabled ? 'auto' : 'none';
			button.style.opacity = enabled ? '1' : '0.5';
		});
	}

	/**
	 * Make a hidden input visible to Elementor's field collector.
	 */
	function tagField(input, id) {
		if (input && !input.dataset.interactionId) {
			input.dataset.interactionId = id;
		}
	}

	/**
	 * Turnstile injects its own `input[name="cf-turnstile-response"]` on render, and replaces
	 * it on every reset. Re-tag it whenever we touch the widget.
	 */
	function tagTokenInput(form) {
		if (!form) {
			return;
		}
		form.querySelectorAll('input[name="' + TOKEN_NAME + '"]').forEach(function (input) {
			tagField(input, TOKEN_NAME);
		});
	}

	/**
	 * Apply the configured alignment. The Turnstile iframe sits in a block-level child that
	 * fills the container, so text-align has no effect; a flex container is what actually
	 * moves it.
	 */
	function applyAlign(node, align) {
		if (!node) {
			return;
		}
		var justify = 'flex-start';
		if (align === 'center') {
			justify = 'center';
		} else if (align === 'right') {
			justify = 'flex-end';
		}
		node.style.display = 'flex';
		node.style.justifyContent = justify;
	}

	function buildLabel(settings) {
		if (!settings.labelEnable || !settings.labelText) {
			return null;
		}
		var label = document.createElement('p');
		label.className = 'cfturnstile-widget-label';
		label.style.cssText = 'font-size: 14px; margin: 0 0 6px 0; width: 100%;';
		label.style.textAlign = ( settings.align === 'center' || settings.align === 'right' ) ? settings.align : 'left';
		if ((settings.appearance || 'always') === 'interaction-only') {
			label.className += ' cfturnstile-widget-label-interaction';
			label.style.display = 'none';
		}
		var small = document.createElement('small');
		small.textContent = settings.labelText;
		label.appendChild(small);
		return label;
	}

	/**
	 * Insert an element according to the configured position setting.
	 */
	/**
	 * The element the widget is positioned relative to.
	 */
	function findSubmitAnchor(form) {
		return form.querySelector(SUBMIT_ANCHOR) || form.querySelector(SUBMIT_SELECTOR);
	}

	function insertAtPosition(form, node, position) {
		var submitButton = findSubmitAnchor(form);
		if (position === 'afterform' || !submitButton || !submitButton.parentNode) {
			form.appendChild(node);
			return;
		}
		if (position === 'after') {
			submitButton.parentNode.insertBefore(node, submitButton.nextSibling);
			return;
		}
		submitButton.parentNode.insertBefore(node, submitButton);
	}

	function renderOptions(form, widget) {
		var settings = getSettings();
		var disableSubmit = settings.disableSubmit || false;

		return {
			sitekey: settings.sitekey,
			theme: settings.theme || 'auto',
			size: settings.size || 'normal',
			appearance: settings.appearance || 'always',
			callback: function (token) {
				tagTokenInput(form);
				if (disableSubmit) {
					setSubmitEnabled(form, true);
				}
				if (typeof window.turnstileElementorCallback === 'function') {
					window.turnstileElementorCallback(token);
				}
			},
			'error-callback': function () {
				if (disableSubmit) {
					setSubmitEnabled(form, false);
				}
			},
			'expired-callback': function () {
				if (disableSubmit) {
					setSubmitEnabled(form, false);
				}
			}
		};
	}

	/**
	 * Turnstile tokens are single use. Elementor flips the form class to
	 * `form-state-success` / `form-state-error` once the AJAX round-trip finishes, which is
	 * our cue to hand out a fresh token for any following attempt.
	 */
	function observeFormState(form, widget) {
		if (!window.MutationObserver || form._cftStateObserver) {
			return;
		}
		var observer = new MutationObserver(function () {
			if (!form.classList.contains('form-state-success') && !form.classList.contains('form-state-error')) {
				return;
			}
			if (!window.turnstile) {
				return;
			}
			var settings = getSettings();
			if (settings.disableSubmit) {
				setSubmitEnabled(form, false);
			}
			try {
				window.turnstile.reset(widget);
			} catch (e) {}
			tagTokenInput(form);
		});
		observer.observe(form, { attributes: true, attributeFilter: ['class'] });
		form._cftStateObserver = observer;
	}

	function getToken(form) {
		var widget = form.querySelector('.cf-turnstile');
		if (!widget) {
			return '';
		}
		if (window.turnstile) {
			try {
				var token = window.turnstile.getResponse(widget);
				if (token) {
					return token;
				}
			} catch (e) {}
		}
		var input = form.querySelector('input[name="' + TOKEN_NAME + '"]');
		return input ? input.value || '' : '';
	}

	function initForms() {
		var settings = getSettings();
		var sitekey = settings.sitekey || '';
		var mode = settings.mode || 'turnstile';
		var position = settings.position || 'before';

		if (!window._cft_atomic_idx) {
			window._cft_atomic_idx = 0;
		}

		document.querySelectorAll(FORM_SELECTOR).forEach(function (form) {
			if (form.classList.contains(PROCESSED_CLASS)) {
				tagTokenInput(form);
				return;
			}
			if (form.querySelector('.cf-turnstile') || form.querySelector('.g-recaptcha')) {
				form.classList.add(PROCESSED_CLASS);
				return;
			}

			// Failsafe modes: Cloudflare is unreachable, so fall back to a marker field and
			// optionally a reCAPTCHA widget. The marker is tagged so it reaches PHP too.
			if (mode === 'allow' || mode === 'recaptcha') {
				var marker = document.createElement('input');
				marker.type = 'hidden';
				marker.name = FAILSAFE_NAME;
				marker.value = mode;
				tagField(marker, FAILSAFE_NAME);
				form.appendChild(marker);

				if (mode === 'recaptcha' && settings.recaptchaSiteKey) {
					var recaptcha = document.createElement('div');
					recaptcha.className = 'g-recaptcha';
					recaptcha.setAttribute('data-sitekey', settings.recaptchaSiteKey);
					recaptcha.style.cssText = 'display: block; margin: 10px 0 15px 0; width: 100%;';
					applyAlign(recaptcha, settings.align);
					insertAtPosition(form, recaptcha, position);
				}

				form.classList.add(PROCESSED_CLASS);
				return;
			}

			if (!window.turnstile || !sitekey) {
				return; // API not ready yet; a later init pass will pick this form up.
			}

			var index = window._cft_atomic_idx++;
			var label = buildLabel(settings);
			var widget = document.createElement('div');
			widget.className = 'elementor-turnstile-field cf-turnstile';
			widget.id = 'cf-turnstile-elementor-atomic-' + index;
			widget.style.cssText = 'display: block; margin: 10px 0 15px 0; width: 100%;';
			applyAlign(widget, settings.align);

			if (label) {
				insertAtPosition(form, label, position);
			}
			if (label && position !== 'afterform' && label.parentNode) {
				label.parentNode.insertBefore(widget, label.nextSibling);
			} else {
				insertAtPosition(form, widget, position);
			}

			if (settings.disableSubmit) {
				setSubmitEnabled(form, false);
			}

			try {
				window.turnstile.render('#' + widget.id, renderOptions(form, widget));
			} catch (e) {
				return;
			}

			tagTokenInput(form);
			observeFormState(form, widget);

			if (label && label.classList.contains('cfturnstile-widget-label-interaction') &&
				typeof window.cfturnstileInitInteractionLabels === 'function') {
				window.cfturnstileInitInteractionLabels();
			}

			form.classList.add(PROCESSED_CLASS);
		});
	}

	/**
	 * Client-side guard. Alpine binds its submit handler on the form itself, so a listener
	 * registered on document in the capture phase runs first and can stop it. Server-side
	 * verification stays the source of truth; this only avoids a pointless round-trip.
	 */
	document.addEventListener('submit', function (event) {
		var form = event.target;
		if (!form || !form.matches || !form.matches(FORM_SELECTOR)) {
			return;
		}
		var settings = getSettings();
		if ((settings.mode || 'turnstile') !== 'turnstile') {
			return;
		}
		var widget = form.querySelector('.cf-turnstile');
		if (!widget) {
			return;
		}

		tagTokenInput(form);

		if (!getToken(form)) {
			event.preventDefault();
			event.stopImmediatePropagation();
			try {
				widget.scrollIntoView({ behavior: 'smooth', block: 'center' });
			} catch (e) {}
		}
	}, true);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initForms);
	} else {
		initForms();
	}

	// The Turnstile API script is deferred, so re-run once it is available.
	if (window.turnstile && typeof window.turnstile.ready === 'function') {
		window.turnstile.ready(initForms);
	} else {
		window.addEventListener('load', initForms);
	}

	// Elementor re-initialises Alpine trees for popups and dynamically rendered content.
	if (window.jQuery) {
		window.jQuery(window).on('elementor/frontend/init', initForms);
		window.jQuery(document).on('elementor/popup/show', function () {
			setTimeout(initForms, 500);
		});
	}
})();
