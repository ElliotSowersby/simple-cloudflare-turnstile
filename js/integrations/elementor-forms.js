function cfturnstile_init_elementor_forms() {
  var settings = window.cfturnstileElementorSettings || {};
  var sitekey = settings.sitekey || '';
  var position = settings.position || 'before';
  var mode = settings.mode || 'turnstile';
  var recaptchaSiteKey = settings.recaptchaSiteKey || '';
  var disableSubmit = settings.disableSubmit || false;
  
  if (!window._cft_elementor_idx) { window._cft_elementor_idx = 0; }
  var elementorForms = document.querySelectorAll('.elementor-form:not(.cft-processed)');
  elementorForms.forEach(function(form) {
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
        submitButton.style.pointerEvents = 'none';
        submitButton.style.opacity = '0.5';
      }

      var turnstileDiv = document.createElement('div');
      turnstileDiv.className = 'elementor-turnstile-field cf-turnstile';
      turnstileDiv.id = 'cf-turnstile-elementor-fallback-' + index;
      turnstileDiv.style.cssText = 'display: block; margin: 10px 0 15px 0; width: 100%;';
      
      if (position === 'after') {
        submitButton.parentNode.insertBefore(turnstileDiv, submitButton.nextSibling);
      } else if (position === 'afterform') {
        form.appendChild(turnstileDiv);
      } else {
        submitButton.parentNode.insertBefore(turnstileDiv, submitButton);
      }
      
      turnstile.render('#cf-turnstile-elementor-fallback-' + index, {
        sitekey: sitekey,
        theme: settings.theme || 'auto',
        callback: function(token) {
          // Re-enable submit button when Turnstile is complete
          if (disableSubmit && submitButton) {
            submitButton.style.pointerEvents = 'auto';
            submitButton.style.opacity = '1';
          }
          if (typeof turnstileElementorCallback === 'function') {
            turnstileElementorCallback(token);
          }
        },
        'error-callback': function() {
          if (disableSubmit && submitButton) {
            submitButton.style.pointerEvents = 'none';
            submitButton.style.opacity = '0.5';
          }
        }
      });
      
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

// Handle form submit button clicks for re-rendering
document.addEventListener('click', function(event) {
  var submitBtn = event.target.closest('.elementor-form button[type="submit"]');
  if (submitBtn) {
    var settings = window.cfturnstileElementorSettings || {};
    var mode = settings.mode || 'turnstile';
    if (mode !== 'turnstile' || !window.turnstile) {
      return;
    }
    var submittedForm = submitBtn.closest('.elementor-form');
    
    setTimeout(function() {
      if (!window.turnstile || !submittedForm) {
        return;
      }
      var widget = submittedForm.querySelector('.cf-turnstile');
      if (widget) {
        turnstile.remove(widget);
        turnstile.render(widget, {
          sitekey: cfturnstileElementorSettings.sitekey,
          callback: 'turnstileCallback',
          theme: cfturnstileElementorSettings.theme || 'auto',
          'error-callback': function() {
            var submitBtn = submittedForm.querySelector('button[type="submit"]');
            var disableSubmit = cfturnstileElementorSettings.disableSubmit || false;
            if (disableSubmit && submitBtn) {
              submitBtn.style.pointerEvents = 'none';
              submitBtn.style.opacity = '0.5';
            }
          }
        });
      }
    }, 2000);
  }
});

// Intercept form submission to ensure Turnstile is completed
document.addEventListener('submit', function(event) {
  var form = event.target;
  if (form && form.classList && form.classList.contains('elementor-form')) {
    var settings = window.cfturnstileElementorSettings || {};
    var mode = settings.mode || 'turnstile';
    if (mode !== 'turnstile' || !window.turnstile) {
      return;
    }
    
    var widget = form.querySelector('.cf-turnstile');
    if (widget) {
      var responseInput = form.querySelector('[name="cf-turnstile-response"]');
      if (!responseInput || !responseInput.value) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
    }
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

      // Disable submit button if option is enabled
      if (disableSubmit && submitButton) {
        submitButton.style.pointerEvents = 'none';
        submitButton.style.opacity = '0.5';
      }
      
      turnstile.remove(widget);
      turnstile.render(widget, {
        sitekey: cfturnstileElementorSettings.sitekey,
        callback: function(token) {
          // Re-enable submit button when Turnstile is complete
          if (disableSubmit && submitButton) {
            submitButton.style.pointerEvents = 'auto';
            submitButton.style.opacity = '1';
          }
          if (typeof turnstileElementorCallback === 'function') {
            turnstileElementorCallback(token);
          }
        },
        'error-callback': function() {
          if (disableSubmit && submitButton) {
            submitButton.style.pointerEvents = 'none';
            submitButton.style.opacity = '0.5';
          }
        },
        theme: cfturnstileElementorSettings.theme || 'auto'
      });
    });
  }, 500);
});