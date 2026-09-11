// Elementor 4 "Atomic" forms are elements rather than widgets, so they carry no .elementor-form class.
var CFT_ATOMIC_FORM_SELECTOR = 'form[data-element_type="e-form"]';
var CFT_ATOMIC_AJAX_ACTION = 'elementor_pro_atomic_forms_send_form';

function cfturnstile_elementor_set_submit(btn, enabled) {
  if (!btn) return;
  btn.style.pointerEvents = enabled ? 'auto' : 'none';
  btn.style.opacity     = enabled ? '1'    : '0.5';
}

/**
 * Current Turnstile token for a form, whichever way it is available.
 */
function cfturnstile_elementor_token(form) {
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

/**
 * A form belonging to a popup document that has not been moved into a modal yet.
 *
 * Elementor Pro caches a popup's markup as an HTML string in its handler's onInit(), then rebuilds
 * the popup from that string on every open. Anything rendered into the form while it is still
 * inline gets baked into the cache as dead markup, so these forms are left to the popup/show
 * handler below.
 */
function cfturnstile_elementor_is_unshown_popup(form) {
  if (!form || !form.closest) return false;
  return !!form.closest('[data-elementor-type="popup"]') && !form.closest('.elementor-popup-modal');
}

/**
 * True when this container is backed by a widget Turnstile still knows about.
 *
 * A container rebuilt from Elementor's popup cache carries markup copied from an earlier render
 * that is no longer a live widget. turnstile.remove() only warns and returns on such a container
 * (it does not throw, so try/catch does not detect it) while turnstile.render() appends alongside
 * the copy - which is how a popup ends up with two widgets and two response inputs. getResponse()
 * does throw for an unknown container, so it is the reliable probe.
 */
function cfturnstile_elementor_widget_is_live(widget) {
  if (!widget || !window.turnstile) return false;
  try {
    turnstile.getResponse(widget);
    return true;
  } catch (e) {
    return false;
  }
}

function cfturnstile_init_elementor_forms() {
  var settings = window.cfturnstileElementorSettings || {};
  var sitekey = settings.sitekey || '';
  var position = settings.position || 'before';
  var mode = settings.mode || 'turnstile';
  var recaptchaSiteKey = settings.recaptchaSiteKey || '';
  var disableSubmit = settings.disableSubmit || false;
  var widgetSize = settings.size || 'normal';
  
  if (!window._cft_elementor_idx) { window._cft_elementor_idx = 0; }
  var elementorForms = document.querySelectorAll('.elementor-form:not(.cft-processed)');
  elementorForms.forEach(function(form) {
    if (cfturnstile_elementor_is_unshown_popup(form)) { return; }

    var index = window._cft_elementor_idx++;
    if (form.querySelector('.cf-turnstile') || form.querySelector('.g-recaptcha') || form.querySelector('input[name="cfturnstile_failsafe"]')) {
      form.classList.add('cft-processed');
      return;
    }
    
    var submitButton = form.querySelector('button[type="submit"]');

    // Failsafe modes: inject marker and optionally reCAPTCHA widget.
    if (submitButton && (mode === 'allow' || mode === 'recaptcha')) {
      var marker = document.createElement('input');
      marker.type = 'hidden';
      marker.name = 'cfturnstile_failsafe';
      marker.value = mode;
      form.appendChild(marker);

      if (mode === 'recaptcha' && recaptchaSiteKey) {
        var recaptchaDiv = document.createElement('div');
        recaptchaDiv.className = 'g-recaptcha';
        recaptchaDiv.setAttribute('data-sitekey', recaptchaSiteKey);
        recaptchaDiv.style.cssText = 'display: block; margin: 10px 0 15px 0; width: 100%;';

        if (position === 'after') {
          submitButton.parentNode.insertBefore(recaptchaDiv, submitButton.nextSibling);
        } else if (position === 'afterform') {
          form.appendChild(recaptchaDiv);
        } else {
          submitButton.parentNode.insertBefore(recaptchaDiv, submitButton);
        }
      }

      form.classList.add('cft-processed');
      return;
    }

    if (submitButton && window.turnstile && sitekey) {
      // Disable submit button if option is enabled
      if (disableSubmit) {
        cfturnstile_elementor_set_submit(submitButton, false);
      }

      var labelEl = null;
      if (settings.labelEnable && settings.labelText) {
        labelEl = document.createElement('p');
        labelEl.className = 'cfturnstile-widget-label';
        labelEl.style.cssText = 'font-size: 14px; margin: 0 0 6px 0; width: 100%;';
        if ((settings.appearance || 'always') === 'interaction-only') {
          labelEl.className += ' cfturnstile-widget-label-interaction';
          labelEl.style.display = 'none';
        }
        var smallEl = document.createElement('small');
        smallEl.textContent = settings.labelText;
        labelEl.appendChild(smallEl);
      }

      var turnstileDiv = document.createElement('div');
      turnstileDiv.className = 'elementor-turnstile-field cf-turnstile';
      turnstileDiv.id = 'cf-turnstile-elementor-fallback-' + index;
      turnstileDiv.style.cssText = 'display: block; margin: 10px 0 15px 0; width: 100%;';

      if (position === 'after') {
        if (labelEl) submitButton.parentNode.insertBefore(labelEl, submitButton.nextSibling);
        submitButton.parentNode.insertBefore(turnstileDiv, labelEl ? labelEl.nextSibling : submitButton.nextSibling);
      } else if (position === 'afterform') {
        if (labelEl) form.appendChild(labelEl);
        form.appendChild(turnstileDiv);
      } else {
        if (labelEl) submitButton.parentNode.insertBefore(labelEl, submitButton);
        submitButton.parentNode.insertBefore(turnstileDiv, submitButton);
      }
      
      turnstile.render('#cf-turnstile-elementor-fallback-' + index, {
        sitekey: sitekey,
        theme: settings.theme || 'auto',
        size: widgetSize,
        appearance: settings.appearance || 'always',
        callback: function(token) {
          // Re-enable submit button when Turnstile is complete
          if (disableSubmit && submitButton) {
            cfturnstile_elementor_set_submit(submitButton, true);
          }
          if (typeof turnstileElementorCallback === 'function') {
            turnstileElementorCallback(token);
          }
        },
        'error-callback': function() {
          if (disableSubmit && submitButton) { cfturnstile_elementor_set_submit(submitButton, false); }
        },
        'expired-callback': function() {
          if (disableSubmit && submitButton) { cfturnstile_elementor_set_submit(submitButton, false); }
        }
      });

      if (labelEl && labelEl.classList.contains('cfturnstile-widget-label-interaction') && typeof window.cfturnstileInitInteractionLabels === 'function') {
        window.cfturnstileInitInteractionLabels();
      }

      form.classList.add('cft-processed');
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  cfturnstile_init_elementor_forms();
});

// Listen to Elementor frontend init to handle cached elements
jQuery(window).on('elementor/frontend/init', function() {
  cfturnstile_init_elementor_forms();

  // Hook into Elementor's widget ready system for forms loaded dynamically (e.g. in popups)
  if (window.elementorFrontend && elementorFrontend.hooks) {
    elementorFrontend.hooks.addAction('frontend/element_ready/form.default', function($scope) {
      cfturnstile_init_elementor_forms();
    });
    elementorFrontend.hooks.addAction('frontend/element_ready/login.default', function($scope) {
      cfturnstile_init_elementor_forms();
    });
  }
});

// Re-render Turnstile only after Elementor reports a submit error (e.g. failed validation)
function cfturnstile_elementor_rerender(form) {
  if (!form || !window.turnstile) return;
  var settings = window.cfturnstileElementorSettings || {};
  var widget = form.querySelector('.cf-turnstile');
  if (!widget) return;
  var submitButton = form.querySelector('button[type="submit"]');
  var disableSubmit = settings.disableSubmit || false;
  if (disableSubmit && submitButton) { cfturnstile_elementor_set_submit(submitButton, false); }
  try { turnstile.remove(widget); } catch (e) {}
  turnstile.render(widget, {
    sitekey: settings.sitekey,
    theme: settings.theme || 'auto',
    size: settings.size || 'normal',
    appearance: settings.appearance || 'always',
    callback: function(token) {
      if (disableSubmit && submitButton) { cfturnstile_elementor_set_submit(submitButton, true); }
      if (typeof turnstileElementorCallback === 'function') {
        turnstileElementorCallback(token);
      }
    },
    'error-callback': function() {
      if (disableSubmit && submitButton) { cfturnstile_elementor_set_submit(submitButton, false); }
    },
    'expired-callback': function() {
      if (disableSubmit && submitButton) { cfturnstile_elementor_set_submit(submitButton, false); }
    }
  });
}

jQuery(document).on('submit_error submit_success', '.elementor-form', function() {
  var settings = window.cfturnstileElementorSettings || {};
  if ((settings.mode || 'turnstile') !== 'turnstile') return;
  cfturnstile_elementor_rerender(this);
});

// Block submission until Turnstile is completed (client-side guard).
// Server-side validation in cfturnstile_elementor_check is the source of truth;
// this just avoids a wasted round-trip and gives immediate feedback.
document.addEventListener('submit', function(event) {
  var form = event.target;
  if (!form.classList) return;
  var isClassic = form.classList.contains('elementor-form');
  var isAtomic = !isClassic && form.matches && form.matches(CFT_ATOMIC_FORM_SELECTOR);
  if (!isClassic && !isAtomic) return;
  var settings = window.cfturnstileElementorSettings || {};
  if ((settings.mode || 'turnstile') !== 'turnstile' || !window.turnstile) return;

  var widget = form.querySelector('.cf-turnstile');
  if (!widget) return;
  if (!cfturnstile_elementor_token(form)) {
    event.preventDefault();
    event.stopImmediatePropagation();
    try { widget.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
  }
}, true);

// Handle Elementor popup show events (jQuery event - must use jQuery to listen)
jQuery(document).on('elementor/popup/show', function(event, id, instance) {
  setTimeout(function() {
    // First, inject Turnstile into any unprocessed forms inside the popup
    cfturnstile_init_elementor_forms();

    var settings = window.cfturnstileElementorSettings || {};
    var mode = settings.mode || 'turnstile';
    var disableSubmit = settings.disableSubmit || false;
    if (mode !== 'turnstile' || !window.turnstile) {
      return;
    }

    // Re-render all Turnstile widgets inside popup modals (they need explicit render after DOM insertion)
    var popupTurnstiles = document.querySelectorAll('.elementor-popup-modal .cf-turnstile');
    popupTurnstiles.forEach(function(widget) {
      var failedText = widget.parentNode ? widget.parentNode.querySelector('.cf-turnstile-failed-text') : null;
      if (failedText) {
        failedText.style.display = 'none';
      }

      // Find the form and submit button for this widget
      var form = widget.closest('.elementor-form');
      var submitButton = form ? form.querySelector('button[type="submit"]') : null;

      // A widget Turnstile still owns is already rendered, and may hold a solved token that
      // re-rendering would throw away.
      if (cfturnstile_elementor_widget_is_live(widget)) {
        if (disableSubmit && submitButton) {
          cfturnstile_elementor_set_submit(submitButton, !!cfturnstile_elementor_token(form));
        }
        return;
      }

      // Disable submit button if option is enabled
      if (disableSubmit && submitButton) { cfturnstile_elementor_set_submit(submitButton, false); }

      // Orphan markup rebuilt from Elementor's popup cache: clear it, or render() appends a second
      // widget and the form posts two cf-turnstile-response inputs - the first one already spent.
      try { turnstile.remove(widget); } catch (e) {}
      widget.innerHTML = '';

      turnstile.render(widget, {
        sitekey: cfturnstileElementorSettings.sitekey,
        appearance: cfturnstileElementorSettings.appearance || 'always',
        callback: function(token) {
          // Re-enable submit button when Turnstile is complete
          if (disableSubmit && submitButton) { cfturnstile_elementor_set_submit(submitButton, true); }
          if (typeof turnstileElementorCallback === 'function') {
            turnstileElementorCallback(token);
          }
        },
        'error-callback': function() {
          if (disableSubmit && submitButton) { cfturnstile_elementor_set_submit(submitButton, false); }
        },
        'expired-callback': function() {
          if (disableSubmit && submitButton) { cfturnstile_elementor_set_submit(submitButton, false); }
        },
        theme: cfturnstileElementorSettings.theme || 'auto',
        size: cfturnstileElementorSettings.size || 'normal'
      });
    });
  }, 500);
});

// Release the widgets of a popup that is closing. Elementor throws the popup DOM away and rebuilds
// it from its cached string on the next open, so without this the widgets stay registered against
// detached nodes for the rest of the page life. Deferred so the widget does not blink out of the
// popup mid exit-animation - removing a detached container works just as well.
jQuery(document).on('elementor/popup/hide', function(event, id) {
  var modal = document.getElementById('elementor-popup-modal-' + id);
  if (!modal || !window.turnstile) return;
  var widgets = Array.prototype.slice.call(modal.querySelectorAll('.cf-turnstile'));
  if (!widgets.length) return;
  setTimeout(function() {
    widgets.forEach(function(widget) {
      try { turnstile.remove(widget); } catch (e) {}
    });
  }, 1000);
});

/* ---------------------------------------------------------------------------
 * Elementor Atomic Forms (Elementor 4 "e-form" elements)
 *
 * Atomic forms are submitted by an Alpine handler bound to the form, which posts
 * a FormData payload built only from the field widgets Elementor itself rendered
 * (input/textarea/select carrying data-interaction-id). A hidden token input
 * sitting inside the form is therefore never sent, so the token is appended to
 * the outgoing request instead - see cfturnstile_atomic_patch_fetch() below.
 * ------------------------------------------------------------------------ */

function cfturnstile_atomic_submit_button(form) {
  return form ? form.querySelector('button[type="submit"], input[type="submit"]') : null;
}

/**
 * Horizontal alignment of the widget inside its (column flex) wrapper.
 */
function cfturnstile_atomic_align(align) {
  if (align === 'center') return 'center';
  if (align === 'right') return 'flex-end';
  return 'flex-start';
}

/**
 * Place a node according to the configured widget position. Atomic forms are flex
 * containers, so the node is inserted as a full width row of its own.
 */
function cfturnstile_atomic_insert(form, node, position, submitButton) {
  if (position === 'afterform' || !submitButton || !submitButton.parentNode) {
    form.appendChild(node);
  } else if (position === 'after') {
    submitButton.parentNode.insertBefore(node, submitButton.nextSibling);
  } else {
    submitButton.parentNode.insertBefore(node, submitButton);
  }
}

function cfturnstile_atomic_render_options(form) {
  var settings = window.cfturnstileElementorSettings || {};
  var disableSubmit = settings.disableSubmit || false;
  var submitButton = cfturnstile_atomic_submit_button(form);
  return {
    sitekey: settings.sitekey,
    theme: settings.theme || 'auto',
    size: settings.size || 'normal',
    appearance: settings.appearance || 'always',
    action: 'elementor-atomic-form',
    callback: function(token) {
      if (disableSubmit) { cfturnstile_elementor_set_submit(submitButton, true); }
      if (typeof turnstileElementorCallback === 'function') {
        turnstileElementorCallback(token);
      }
    },
    'error-callback': function() {
      if (disableSubmit) { cfturnstile_elementor_set_submit(submitButton, false); }
    },
    'expired-callback': function() {
      if (disableSubmit) { cfturnstile_elementor_set_submit(submitButton, false); }
    }
  };
}

function cfturnstile_init_elementor_atomic_forms() {
  var settings = window.cfturnstileElementorSettings || {};
  var sitekey = settings.sitekey || '';
  var position = settings.position || 'before';
  var mode = settings.mode || 'turnstile';
  var recaptchaSiteKey = settings.recaptchaSiteKey || '';
  var disableSubmit = settings.disableSubmit || false;

  if (!window._cft_atomic_idx) { window._cft_atomic_idx = 0; }

  // Elementor re-renders form elements in place (editor edits, popups, Alpine refreshes),
  // which drops the injected widget. Release those forms so they are processed again.
  document.querySelectorAll(CFT_ATOMIC_FORM_SELECTOR + '.cft-processed').forEach(function(form) {
    if (!form.querySelector('.cf-turnstile, .g-recaptcha, input[name="cfturnstile_failsafe"]')) {
      form.classList.remove('cft-processed');
    }
  });

  document.querySelectorAll(CFT_ATOMIC_FORM_SELECTOR + ':not(.cft-processed)').forEach(function(form) {
    // Same popup caching trap as the classic forms above - wait until the popup is actually shown,
    // at which point the observer below picks the form up with its atomic render options intact.
    if (cfturnstile_elementor_is_unshown_popup(form)) { return; }

    if (form.querySelector('.cf-turnstile, .g-recaptcha, input[name="cfturnstile_failsafe"]')) {
      form.classList.add('cft-processed');
      return;
    }

    var submitButton = cfturnstile_atomic_submit_button(form);

    // Failsafe modes: post a marker, and render reCAPTCHA in its place if configured.
    if (mode === 'allow' || mode === 'recaptcha') {
      var marker = document.createElement('input');
      marker.type = 'hidden';
      marker.name = 'cfturnstile_failsafe';
      marker.value = mode;
      form.appendChild(marker);

      if (mode === 'recaptcha' && recaptchaSiteKey) {
        var recaptchaWrap = document.createElement('div');
        recaptchaWrap.className = 'cfturnstile-atomic-field';
        recaptchaWrap.style.cssText = 'display: flex; flex-direction: column; align-items: ' + cfturnstile_atomic_align(settings.align) + '; flex: 0 0 100%; width: 100%; margin: 10px 0 15px 0;';
        var recaptchaDiv = document.createElement('div');
        recaptchaDiv.className = 'g-recaptcha';
        recaptchaDiv.setAttribute('data-sitekey', recaptchaSiteKey);
        recaptchaWrap.appendChild(recaptchaDiv);
        cfturnstile_atomic_insert(form, recaptchaWrap, position, submitButton);
      }

      form.classList.add('cft-processed');
      return;
    }

    // The submit button is rendered as a child widget, so it can still be missing on an
    // early pass. Wait for the next pass rather than injecting into a half built form.
    if (!submitButton || !window.turnstile || !sitekey) return;

    if (disableSubmit) {
      cfturnstile_elementor_set_submit(submitButton, false);
    }

    var index = window._cft_atomic_idx++;

    var wrap = document.createElement('div');
    wrap.className = 'cfturnstile-atomic-field';
    wrap.style.cssText = 'display: flex; flex-direction: column; align-items: ' + cfturnstile_atomic_align(settings.align) + '; flex: 0 0 100%; width: 100%; margin: 10px 0 15px 0;';

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
    turnstileDiv.className = 'elementor-turnstile-field cf-turnstile';
    turnstileDiv.id = 'cf-turnstile-elementor-atomic-' + index;
    wrap.appendChild(turnstileDiv);

    cfturnstile_atomic_insert(form, wrap, position, submitButton);

    turnstile.render(turnstileDiv, cfturnstile_atomic_render_options(form));

    if (settings.labelEnable && (settings.appearance || 'always') === 'interaction-only' && typeof window.cfturnstileInitInteractionLabels === 'function') {
      window.cfturnstileInitInteractionLabels();
    }

    form.classList.add('cft-processed');
  });
}

/**
 * Reset a widget after its form has been submitted, so a retry gets a fresh token.
 * Elementor calls form.reset() on success, which empties the token input while the
 * widget still holds the spent token - the global refresh helper cannot detect that.
 */
function cfturnstile_atomic_reset(form) {
  if (!form || !window.turnstile) return;
  var widget = form.querySelector('.cf-turnstile');
  if (!widget) return;
  var settings = window.cfturnstileElementorSettings || {};
  if (settings.disableSubmit) {
    cfturnstile_elementor_set_submit(cfturnstile_atomic_submit_button(form), false);
  }
  try { turnstile.reset(widget); } catch (e) {}
}

/**
 * Attach the token to the atomic form request.
 *
 * Elementor builds the FormData itself and only includes its own field widgets, so
 * there is no markup based way to get the token to the server. The wrapper only
 * touches requests carrying the atomic form action and leaves everything else alone.
 */
function cfturnstile_atomic_patch_fetch() {
  if (window._cfturnstileAtomicFetchPatched) return;
  var originalFetch = window.fetch;
  if (typeof originalFetch !== 'function') return;
  window._cfturnstileAtomicFetchPatched = true;

  window.fetch = function(input, init) {
    var form = null;
    try {
      var body = init && init.body;
      if (body instanceof FormData && body.get('action') === CFT_ATOMIC_AJAX_ACTION) {
        var formId = body.get('form_id');
        if (formId && /^[A-Za-z0-9_-]+$/.test(formId)) {
          form = document.querySelector(CFT_ATOMIC_FORM_SELECTOR + '[data-id="' + formId + '"]');
        }
        if (form) {
          var token = cfturnstile_elementor_token(form);
          if (token) {
            body.set('cf-turnstile-response', token);
          }
          var failsafe = form.querySelector('input[name="cfturnstile_failsafe"]');
          if (failsafe && failsafe.value) {
            body.set('cfturnstile_failsafe', failsafe.value);
          }
          var recaptcha = form.querySelector('textarea[name="g-recaptcha-response"], input[name="g-recaptcha-response"]');
          if (recaptcha && recaptcha.value) {
            body.set('g-recaptcha-response', recaptcha.value);
          }
        }
      }
    } catch (e) {}

    // Callers reaching fetch from strict mode code (bundles, ES modules) invoke it bare, so
    // `this` arrives undefined. Current Chrome tolerates that, but engines that hold fetch to
    // its WebIDL receiver throw "Illegal invocation" - fall back to the global rather than
    // making that a property of whether this plugin happens to be active.
    var request = originalFetch.apply(this || window, arguments);

    if (form) {
      var reset = function() {
        // Let Elementor apply its own result handling (including form.reset()) first.
        setTimeout(function() { cfturnstile_atomic_reset(form); }, 500);
      };
      request.then(reset, reset);
    }

    return request;
  };
}

(function() {
  cfturnstile_atomic_patch_fetch();

  var scheduled = null;
  function schedule() {
    if (scheduled) return;
    scheduled = setTimeout(function() {
      scheduled = null;
      cfturnstile_init_elementor_atomic_forms();
    }, 100);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', schedule);
  } else {
    schedule();
  }

  // Atomic forms are mounted by Elementor's own handler system and can appear or be
  // re-rendered at any point (popups, editor edits, lazy loaded content), so watch the
  // document rather than hooking into a specific lifecycle event.
  if (window.MutationObserver) {
    new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true });
  }
})();
