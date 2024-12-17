/* Woo Checkout */
jQuery( document ).ready(function() {
    jQuery( document.body ).on( 'update_checkout updated_checkout applied_coupon_in_checkout removed_coupon_in_checkout', function() {
        if(jQuery('#cf-turnstile-woo-checkout iframe').length <= 0) {
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
if ( wp && wp.data ) {
    var unsubscribe = wp.data.subscribe(function() {
        const turnstileItem = document.getElementById('cf-turnstile-woo-checkout');
        if(turnstile) {
            turnstile.render(turnstileItem, {
                sitekey: turnstileItem.dataset.sitekey,
                callback: function(data) {
                    wp.data.dispatch('wc/store/checkout').__internalSetExtensionData('simple-cloudflare-turnstile', {
                        token: data
                    })
                }
            });

            turnstile.onEx
            unsubscribe();
        }
    }, 'wc/store/cart');
}