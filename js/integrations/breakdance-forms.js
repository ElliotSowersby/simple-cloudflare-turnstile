// Breakdance form elements (Form Builder, Login, Register, Forgot Password). Third-party and comment
// forms rendered by Breakdance also carry .breakdance-form, but only its own forms have data-options.
var CFT_BREAKDANCE_FORM_SELECTOR = 'form.breakdance-form[data-options]';

function cfturnstile_breakdance_settings() {
  return window.cfturnstileBreakdanceSettings || {};
}

function cfturnstile_breakdance_set_submit(btn, enabled) {
  if (!btn) return;
  btn.style.pointerEvents = enabled ? 'auto' : 'none';
  btn.style.opacity     = enabled ? '1'    : '0.5';
}

function cfturnstile_breakdance_submit_button(form) {
  return form ? form.querySelector('.breakdance-form-button__submit, button[type="submit"]') : null;
}

/**
 * Widget action for a form, from its Breakdance slug. Empty for forms the server does not check.
 */
function cfturnstile_breakdance_action(form) {
  var actions = cfturnstile_breakdance_settings().actions || {};
  var slug = '';
  try {
    var options = JSON.parse(form.getAttribute('data-options') || '{}');
    slug = options && typeof options.slug === 'string' ? options.slug : '';
  } catch (e) {}
  return Object.prototype.hasOwnProperty.call(actions, slug) ? actions[slug] : '';
}

/**
 * Current Turnstile token for a form, whichever way it is available.
 */
function cfturnstile_breakdance_token(form) {
  var widget = form ? form.querySelector('.cf-turnstile') : null;
  if (!widget) return '';
  var token = '';
  if (window.turnstile) {
    try { token = turnstile.getResponse(widget) || ''; } catch (e) {}
  }
  if (!token) {
    var responseInput = form.querySelector('[name="cf-turnstile-response"]');
    token = responseInput ? (responseInput.value || '') : '';
  }
  return token;
}

function cfturnstile_breakdance_is_builder() {
  try {
    return !!(window.BreakdanceFrontend && BreakdanceFrontend.utils && BreakdanceFrontend.utils.isBuilder());
  } catch (e) {
    return false;
  }
}

/**
 * Wrapper for the widget, as a full width row of the form's 12 column grid. Horizontal forms flow
 * by column, so the row is pinned below the fields rather than added as one more column.
 */
function cfturnstile_breakdance_wrap(form) {
  var align = cfturnstile_breakdance_settings().align;
  var wrap = document.createElement('div');
  wrap.className = 'cfturnstile-breakdance-field';
  wrap.style.cssText = 'display: flex; flex-direction: column; align-items: ' + (align === 'center' ? 'center' : (align === 'right' ? 'flex-end' : 'flex-start')) + '; grid-column: 1 / -1; width: 100%;';
  if (form.classList.contains('breakdance-form--horizontal')) {
    wrap.style.gridRow = '2';
  }
  return wrap;
}

/**
 * Place the wrapper before or after the footer row holding the submit button. The footer is a
 * single flex row, so the widget is not put inside it.
 */
function cfturnstile_breakdance_insert(form, node) {
  var footer = form.querySelector('.breakdance-form-footer');
  if (!footer || !footer.parentNode) {
    form.appendChild(node);
  } else if (cfturnstile_breakdance_settings().position === 'after') {
    footer.parentNode.insertBefore(node, footer.nextSibling);
  } else {
    footer.parentNode.insertBefore(node, footer);
  }
}

function cfturnstile_breakdance_render_options(form, action) {
  var settings = cfturnstile_breakdance_settings();
  var disableSubmit = settings.disableSubmit || false;
  var submitButton = cfturnstile_breakdance_submit_button(form);
  return {
    sitekey: settings.sitekey,
    theme: settings.theme || 'auto',
    language: settings.language || 'auto',
    size: settings.size || 'normal',
    appearance: settings.appearance || 'always',
    action: action,
    retry: 'auto',
    'retry-interval': 1000,
    'refresh-expired': 'auto',
    callback: function(token) {
      if (disableSubmit) { cfturnstile_breakdance_set_submit(submitButton, true); }
      if (typeof turnstileBreakdanceCallback === 'function') {
        turnstileBreakdanceCallback(token);
      }
    },
    'error-callback': function() {
      if (disableSubmit) { cfturnstile_breakdance_set_submit(submitButton, false); }
    },
    'expired-callback': function() {
      if (disableSubmit) { cfturnstile_breakdance_set_submit(submitButton, false); }
    }
  };
}

/**
 * Follow the form's state classes, as Breakdance fires no events of its own.
 *
 * - is-loading is set for the length of the request: reset the widget once it is removed, so a
 *   retry gets a fresh token.
 * - breakdance-form--hidden is set on success when "Hide form on success" is on. Breakdance only
 *   hides its own fields, so hide the widget with them, and show it again when the form returns.
 */
function cfturnstile_breakdance_watch(form) {
  if (!window.MutationObserver || form._cfturnstileBreakdanceWatched) return;
  form._cfturnstileBreakdanceWatched = true;
  var loading = form.classList.contains('is-loading');
  new MutationObserver(function() {
    var now = form.classList.contains('is-loading');
    if (loading && !now) {
      cfturnstile_breakdance_reset(form);
    }
    loading = now;

    var hidden = form.classList.contains('breakdance-form--hidden');
    var wraps = form.querySelectorAll('.cfturnstile-breakdance-field');
    for (var i = 0; i < wraps.length; i++) {
      wraps[i].style.display = hidden ? 'none' : 'flex';
    }
  }).observe(form, { attributes: true, attributeFilter: ['class'] });
}

function cfturnstile_breakdance_reset(form) {
  var widget = form.querySelector('.cf-turnstile');
  if (!widget || !window.turnstile) return;
  if (cfturnstile_breakdance_settings().disableSubmit) {
    cfturnstile_breakdance_set_submit(cfturnstile_breakdance_submit_button(form), false);
  }
  try { turnstile.reset(widget); } catch (e) {}
}

/**
 * Add the widget (or the failsafe fields) to every Breakdance form not yet processed.
 *
 * @return {boolean} False while a form is still waiting for the Turnstile API to load.
 */
function cfturnstile_init_breakdance_forms() {
  var settings = cfturnstile_breakdance_settings();
  var mode = settings.mode || 'turnstile';
  var ready = true;

  if (cfturnstile_breakdance_is_builder()) return true;
  if (!window._cft_breakdance_idx) { window._cft_breakdance_idx = 0; }

  document.querySelectorAll(CFT_BREAKDANCE_FORM_SELECTOR + ':not(.cft-processed)').forEach(function(form) {
    var action = cfturnstile_breakdance_action(form);
    if (!action || form.querySelector('.cf-turnstile, .g-recaptcha, input[name="cfturnstile_failsafe"]')) {
      form.classList.add('cft-processed');
      return;
    }

    // Failsafe modes: post a marker, and render reCAPTCHA in its place if configured.
    if (mode === 'allow' || mode === 'recaptcha') {
      var marker = document.createElement('input');
      marker.type = 'hidden';
      marker.name = 'cfturnstile_failsafe';
      marker.value = mode;
      form.appendChild(marker);

      if (mode === 'recaptcha' && settings.recaptchaSiteKey) {
        var recaptchaWrap = cfturnstile_breakdance_wrap(form);
        var recaptchaDiv = document.createElement('div');
        recaptchaDiv.className = 'g-recaptcha';
        recaptchaDiv.setAttribute('data-sitekey', settings.recaptchaSiteKey);
        recaptchaWrap.appendChild(recaptchaDiv);
        cfturnstile_breakdance_insert(form, recaptchaWrap);
        cfturnstile_breakdance_watch(form);
        // Rendered automatically if the reCAPTCHA API has not loaded yet.
        if (window.grecaptcha && typeof grecaptcha.render === 'function') {
          try { grecaptcha.render(recaptchaDiv); } catch (e) {}
        }
      }

      form.classList.add('cft-processed');
      return;
    }

    if (!window.turnstile || typeof turnstile.render !== 'function' || !settings.sitekey) {
      ready = false;
      return;
    }

    var submitButton = cfturnstile_breakdance_submit_button(form);
    if (settings.disableSubmit) {
      cfturnstile_breakdance_set_submit(submitButton, false);
    }

    var wrap = cfturnstile_breakdance_wrap(form);

    if (settings.labelEnable && settings.labelText) {
      var labelEl = document.createElement('p');
      labelEl.className = 'cfturnstile-widget-label';
      labelEl.style.cssText = 'font-size: 14px; margin: 0 0 6px 0;';
      if ((settings.appearance || 'always') === 'interaction-only') {
        labelEl.className += ' cfturnstile-widget-label-interaction';
        labelEl.style.display = 'none';
      }
      var smallEl = document.createElement('small');
      smallEl.textContent = settings.labelText;
      labelEl.appendChild(smallEl);
      wrap.appendChild(labelEl);
    }

    var turnstileDiv = document.createElement('div');
    turnstileDiv.className = 'breakdance-turnstile-field cf-turnstile';
    turnstileDiv.id = 'cf-turnstile-breakdance-' + (window._cft_breakdance_idx++);
    wrap.appendChild(turnstileDiv);

    cfturnstile_breakdance_insert(form, wrap);

    turnstile.render(turnstileDiv, cfturnstile_breakdance_render_options(form, action));
    cfturnstile_breakdance_watch(form);

    if (settings.labelEnable && (settings.appearance || 'always') === 'interaction-only' && typeof window.cfturnstileInitInteractionLabels === 'function') {
      window.cfturnstileInitInteractionLabels();
    }

    form.classList.add('cft-processed');
  });

  return ready;
}

// Block submission until Turnstile is completed (client-side guard). Breakdance listens on the
// form itself, so this capture listener runs first. Server-side validation in
// cfturnstile_breakdance_check is the source of truth; this just avoids a wasted round-trip.
document.addEventListener('submit', function(event) {
  var form = event.target;
  if (!form || !form.matches || !form.matches(CFT_BREAKDANCE_FORM_SELECTOR)) return;
  if ((cfturnstile_breakdance_settings().mode || 'turnstile') !== 'turnstile' || !window.turnstile) return;

  var widget = form.querySelector('.cf-turnstile');
  if (!widget) return;
  if (!cfturnstile_breakdance_token(form)) {
    event.preventDefault();
    event.stopImmediatePropagation();
    try { widget.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
  }
}, true);

// "Delay JavaScript" in performance plugins can run this file after DOMContentLoaded, and the
// Turnstile API can still be loading (deferred), so retry until it is available.
(function() {
  var tries = 0;
  var timer = null;
  function run() {
    timer = null;
    if (!cfturnstile_init_breakdance_forms() && ++tries < 170) {
      timer = setTimeout(run, tries < 20 ? 100 : 2000);
    }
  }

  // Forms (popups included) are printed before footer scripts, so they can be processed now.
  run();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      if (!timer) run();
    });
  }

  // Pick up forms added later, such as by a posts loop's infinite scroll.
  if (window.MutationObserver) {
    var scheduled = null;
    new MutationObserver(function() {
      if (scheduled) return;
      scheduled = setTimeout(function() {
        scheduled = null;
        if (!timer) run();
      }, 100);
    }).observe(document.documentElement, { childList: true, subtree: true });
  }
})();
