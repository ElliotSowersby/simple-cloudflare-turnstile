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