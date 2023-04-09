/* Blocksy */
document.addEventListener('DOMContentLoaded', function() {
    var headerAccount = document.querySelectorAll('.ct-header-account');
    headerAccount.forEach(function(element) {
        element.addEventListener('click', function() {
            turnstile.remove(".ct-account-panel #cf-turnstile-woo-register");
            setTimeout(function() {
                turnstile.render(".ct-account-panel #loginform .cf-turnstile");
                turnstile.render(".ct-account-panel #registerform .cf-turnstile");
            }, 500);
        });
    });
});