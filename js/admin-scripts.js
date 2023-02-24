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