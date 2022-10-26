jQuery( document ).ready(function() {
	jQuery("#sct-accordion-wordpress").click();
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

jQuery( document ).ready(function() {
	jQuery('#cfturnstile_scripts').change(function(){
		jQuery('.section_cfturnstile_scripts_default').hide();
		jQuery('.section_cfturnstile_scripts_autocustom').hide();
		jQuery('.section_cfturnstile_scripts_custom').hide();
		jQuery('.section_cfturnstile_scripts_all').hide();
		if(jQuery(this).find(":selected").val() == "default") {
			jQuery('.section_cfturnstile_scripts_default').show();
		}
		if(jQuery(this).find(":selected").val() == "autocustom") {
			jQuery('.section_cfturnstile_scripts_autocustom').show();
		}
		if(jQuery(this).find(":selected").val() == "custom") {
			jQuery('.section_cfturnstile_scripts_custom').show();
		}
		if(jQuery(this).find(":selected").val() == "all") {
			jQuery('.section_cfturnstile_scripts_all').show();
		}
	});
	jQuery('#cfturnstile_scripts').trigger('change');
});
