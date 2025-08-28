/* Woo Checkout */
jQuery( document ).ready(function() {
    jQuery( document.body ).on( 'update_checkout updated_checkout applied_coupon_in_checkout removed_coupon_in_checkout', function() {
        if(jQuery('#cf-turnstile-woo-checkout').is(':empty')) {
            turnstileWooCheckoutReset();
        }
    });
    jQuery( document.body ).on( 'checkout_error', turnstileWooCheckoutReset);
});
function turnstileWooCheckoutReset() {
    if(document.getElementById('cf-turnstile-woo-checkout')) {
        turnstile.remove('#cf-turnstile-woo-checkout');
        turnstile.render('#cf-turnstile-woo-checkout');
    }
}
/* On click ".checkout .showlogin" link re-render */
jQuery('.showlogin').on('click', function() {
    turnstile.remove('.sct-woocommerce-login');
    turnstile.render('.sct-woocommerce-login');
});

/* Woo Checkout Block */
document.addEventListener('DOMContentLoaded', function() {
    if (typeof wp !== 'undefined' && wp.data && typeof turnstile !== 'undefined') {
        var unsubscribe = wp.data.subscribe(function() {
            if (document.getElementById('cf-turnstile-woo-checkout') && document.getElementById('cf-turnstile-woo-checkout').innerHTML.trim() !== '') {
                return;
            }
            const turnstileItem = document.getElementById('cf-turnstile-woo-checkout');
            if(turnstileItem) {
                turnstile.render(turnstileItem, {
                    sitekey: turnstileItem.dataset.sitekey,
                    callback: function(data) {
                        wp.data.dispatch('wc/store/checkout').__internalSetExtensionData('simple-cloudflare-turnstile', {
                            token: data
                        })
                    }
                });
                unsubscribe();
            }
        }, 'wc/store/cart');
    }
});