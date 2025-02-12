document.addEventListener("DOMContentLoaded", function() {
    jQuery("form.mailpoet_form").on("submit", function(event) {
        var form = jQuery(this);
        var tokenElem = document.querySelector("input[name='cf-turnstile-response']");
        if (tokenElem && tokenElem.value) {
            form.find("input[name='data[cf-turnstile-response]']").remove();
            form.append('<input type="hidden" name="data[cf-turnstile-response]" value="' + tokenElem.value + '">');
        }
    });
});