function cfturnstile_init_elementor_forms() {
  var settings = window.cfturnstileElementorSettings || {};
  var sitekey = settings.sitekey || '';
  var position = settings.position || 'before';
  
  var elementorForms = document.querySelectorAll('.elementor-form:not(.cft-processed)');
  elementorForms.forEach(function(form, index) {
    if (form.querySelector('.cf-turnstile')) {
      form.classList.add('cft-processed');
      return;
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
}

document.addEventListener('DOMContentLoaded', function() {
  cfturnstile_init_elementor_forms();
});

// Listen to Elementor frontend init to handle cached elements
document.addEventListener('elementor/frontend/init', function() {
  cfturnstile_init_elementor_forms();
});

// Handle form submit button clicks for re-rendering
document.addEventListener('click', function(event) {
  if (event.target.matches('.elementor-form button[type="submit"]')) {
    var submittedForm = event.target.closest('.elementor-form');
    
    setTimeout(function() {
      turnstile.remove('.elementor-form .cf-turnstile');
      turnstile.render('.elementor-form .cf-turnstile', {
        sitekey: cfturnstileElementorSettings.sitekey,
        callback: 'turnstileCallback',
        theme: cfturnstileElementorSettings.theme || 'auto'
      });
    }, 2000);
  }
});

// Handle Elementor popup show events
document.addEventListener('elementor/popup/show', function(event) {
  setTimeout(function() {
    var popupTurnstile = document.querySelector('.elementor-popup-modal .cf-turnstile');
    if (!popupTurnstile) {
      return;
    }

    var failedText = document.querySelector('.cf-turnstile-failed-text');
    if (failedText) {
      failedText.style.display = 'none';
    }
    
    turnstile.remove('.elementor-popup-modal .cf-turnstile');
    
    turnstile.render('.elementor-popup-modal .cf-turnstile', {
      sitekey: cfturnstileElementorSettings.sitekey,
      callback: 'turnstileCallback',
      theme: cfturnstileElementorSettings.theme || 'auto'
    });

    popupTurnstile.style.marginTop = '-5px';
    popupTurnstile.style.marginBottom = '20px';

  }, 1000);
});