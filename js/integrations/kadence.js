jQuery(document).ready(function ($) {
    if ($('.kb-submit-field').length > 0) {
        $('.kb-submit-field').before(cfTurnstileVars.field);
        
        const turnstileItem = document.getElementById('cf-turnstile-kadence');
        if (turnstileItem) {
            turnstile.render(turnstileItem, { sitekey: turnstileItem.dataset.sitekey });
        }
    }
});
jQuery(document).on('click', '.kb-submit-field .kb-button', function() {
    setTimeout(function() {
        const turnstileItem = document.getElementById('cf-turnstile-kadence');
        if (turnstileItem) {
            turnstile.remove(turnstileItem);
            turnstile.render(turnstileItem, { sitekey: turnstileItem.dataset.field });
        }
    }, 5000);
});