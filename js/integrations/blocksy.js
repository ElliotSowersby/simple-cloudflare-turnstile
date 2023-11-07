/* Blocksy */
document.addEventListener('DOMContentLoaded', function() {
    var headerAccount = document.querySelectorAll('.ct-header-account');
    headerAccount.forEach(function(element) {
        element.addEventListener('click', function() {
            turnstile.remove(".ct-account-panel #cf-turnstile-woo-register");
            setTimeout(function() {
                if(document.querySelector(".ct-account-panel #loginform .cf-turnstile")) {
                    turnstile.reset(".ct-account-panel #loginform .cf-turnstile");
                }
                if(document.querySelector(".ct-account-panel #registerform .cf-turnstile")) {
                    turnstile.reset(".ct-account-panel #registerform .cf-turnstile");
                    turnstile.remove(".ct-account-panel #registerform .sct-woocommerce-register");
                }
            }, 500);
        });
    });
});