/* Elementor Pro Popups */
jQuery(document).ready(function($) {
    $(document).on('elementor/popup/show', function(event, id, instance) {
        setTimeout(function() {
            if (!document.querySelector('.elementor-popup-modal .cf-turnstile')) {
                return;
            }
            $('.cf-turnstile-failed-text').hide();
            turnstile.remove('.elementor-popup-modal .cf-turnstile');
            turnstile.render('.elementor-popup-modal .cf-turnstile');
        }, 1000);
    });
});