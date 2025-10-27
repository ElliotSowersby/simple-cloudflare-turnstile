/* Admin Toggles */
document.addEventListener("DOMContentLoaded", function() {
	if(document.querySelector("#sct-accordion-wordpress") != null) {
		document.querySelector("#sct-accordion-wordpress").click();
	}
});
var acc = document.getElementsByClassName("sct-accordion");
var i;
for (i = 0; i < acc.length; i++) {
  acc[i].addEventListener("click", function() {
	this.classList.toggle("sct-active");
	var panel = this.nextElementSibling;
	if (panel.style.display === "block") {
	  panel.style.display = "none";
	} else {
	  panel.style.display = "block";
	}
  });
}
/* Appearance Mode Description */
document.addEventListener("DOMContentLoaded", function() {
    function updateDescription(selected) {
        // Hide all descriptions
        document.querySelectorAll('.wcu-appearance-always, .wcu-appearance-execute, .wcu-appearance-interaction-only')
            .forEach(element => element.style.display = 'none');

        // Show the relevant description
        document.querySelector('.wcu-appearance-' + selected).style.display = 'block';
    }

    // Update the description on page load
    const appearanceSelect = document.querySelector("select[name='cfturnstile_appearance']");
    updateDescription(appearanceSelect.value);

    // Handle the select change event
    appearanceSelect.addEventListener("change", function() {
        updateDescription(this.value);
    });
});
/* wp-config define keys toggle */
document.addEventListener('DOMContentLoaded', function() {
  var toggles = document.querySelectorAll('.sct-wpconfig-toggle');
  toggles.forEach(function(link){
    link.addEventListener('click', function(e){
      e.preventDefault();
      var details = this.nextElementSibling;
      if(!details) return;
      var isOpen = details.style.display === 'block';
      details.style.display = isOpen ? 'none' : 'block';
      var icon = this.querySelector('.dashicons');
      if(icon){
        if(isOpen){
          icon.classList.remove('dashicons-arrow-up-alt2');
          icon.classList.add('dashicons-arrow-down-alt2');
        } else {
          icon.classList.remove('dashicons-arrow-down-alt2');
          icon.classList.add('dashicons-arrow-up-alt2');
        }
      }
    });
  });
});

/* Elementor Integration Method Toggle */
document.addEventListener("DOMContentLoaded", function() {
    const elementorMethodSelect = document.getElementById('cfturnstile_elementor_method');
    if (elementorMethodSelect) {
    const globalControls = document.getElementById('cfturnstile-elementor-global-controls');
    const pagesWrap = document.getElementById('cfturnstile-elementor-global-pages-wrap');
    const scopeSelect = document.getElementById('cfturnstile_elementor_global_scope');

    const updateVisibility = () => {
      const selectedValue = elementorMethodSelect.value;
      // Hide all method descriptions
      document.querySelectorAll('.cfturnstile-elementor-method-description').forEach(function(div) {
        div.style.display = 'none';
      });
      // Show the selected method description
      const selectedDiv = document.getElementById('cfturnstile-elementor-method-' + selectedValue);
      if (selectedDiv) selectedDiv.style.display = 'inline-block';

      // Toggle global controls visibility
      if (globalControls) {
        globalControls.style.display = (selectedValue === 'global') ? '' : 'none';
      }

      // Toggle pages wrap based on scope select
      if (pagesWrap && scopeSelect) {
        pagesWrap.style.display = (scopeSelect.value === 'specific') ? '' : 'none';
      }

      // Toggle scope descriptions
      document.querySelectorAll('.cfturnstile-elementor-scope-description').forEach(function(div){
        div.style.display = 'none';
      });
      if (scopeSelect && selectedValue === 'global') {
        const scopeDiv = document.getElementById('cfturnstile-elementor-scope-' + scopeSelect.value);
        if (scopeDiv) scopeDiv.style.display = 'block';
      }
    };

    elementorMethodSelect.addEventListener('change', updateVisibility);
    if (scopeSelect) scopeSelect.addEventListener('change', updateVisibility);
    // Init on load
    updateVisibility();
    }
});

/* Failure Message Toggle */
document.addEventListener("DOMContentLoaded", function() {
  const failureMessages = document.querySelectorAll('.cfturnstile-failure-message');
  const toggleInput = document.querySelector('input[name="cfturnstile_failure_message_enable"]');

  // If elements aren't present on this page, safely exit
  if (!toggleInput || failureMessages.length === 0) return;

  // Helper to set visibility
  const setVisibility = (checked) => {
    failureMessages.forEach(el => {
      el.style.display = checked ? '' : 'none';
    });
  };

  // Initialize based on current state
  setVisibility(toggleInput.checked);

  // Update on change
  toggleInput.addEventListener('change', function() {
    setVisibility(this.checked);
  });
});