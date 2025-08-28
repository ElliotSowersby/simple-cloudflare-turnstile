document.addEventListener('DOMContentLoaded', function() {
  // Get plugin settings from localized script
  var settings = window.cfturnstileElementorSettings || {};
  var sitekey = settings.sitekey || '';
  var position = settings.position || 'before';
  
  // Check for Elementor forms without Turnstile and add them
  var elementorForms = document.querySelectorAll('.elementor-form:not(.cft-processed)');
  elementorForms.forEach(function(form, index) {
    if (form.querySelector('.cf-turnstile')) {
      form.classList.add('cft-processed');
      return; // Already has Turnstile
    }
    
    var submitButton = form.querySelector('button[type="submit"]');
    if (submitButton && window.turnstile && sitekey) {
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
      
      // Render Turnstile
      turnstile.render('#cf-turnstile-elementor-fallback-' + index, {
        sitekey: sitekey,
        theme: settings.theme || 'auto',
        callback: function(token) {
          if (typeof turnstileElementorCallback === 'function') {
            turnstileElementorCallback(token);
          }
        }
      });
      
      form.classList.add('cft-processed');
    }
  });
});

// Form submission handler reset Turnstile on click submit utton
jQuery(".elementor-form button[type='submit']").on('click', function(event) {
    var submittedForm = jQuery(this).closest('.elementor-form');
    setTimeout(function() {
        // Remove existing Turnstile instances
        turnstile.remove('.elementor-form .cf-turnstile');
        // Re-render Turnstile
        turnstile.render('.elementor-form .cf-turnstile', {
            sitekey: cfturnstileElementorSettings.sitekey,
            callback: 'turnstileCallback',
            theme: cfturnstileElementorSettings.theme || 'auto'
        });
    }, 2000);
});

// Handle Elementor popup forms
jQuery(document).ready(function($) {
    $(document).on('elementor/popup/show', function(event, id, instance) {
        setTimeout(function() {
            var popupTurnstile = $('.elementor-popup-modal .cf-turnstile');
            if (!popupTurnstile.length) {
                return;
            }

            $('.cf-turnstile-failed-text').hide(); 
            
            // Remove existing turnstile instances in popup
            turnstile.remove('.elementor-popup-modal .cf-turnstile');
            
            // Render new turnstile instance
            turnstile.render('.elementor-popup-modal .cf-turnstile', {
                sitekey: cfturnstileElementorSettings.sitekey,
                callback: 'turnstileCallback',
                theme: cfturnstileElementorSettings.theme || 'auto'
            });

            // Set the top and bottom margins for the Turnstile widget to 0
            $('.elementor-popup-modal .cf-turnstile').css({
                'margin-top': '-5px',
                'margin-bottom': '20px'
            });

        }, 1000);
    });
});