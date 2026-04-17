/* Woo Checkout */
jQuery( document ).ready(function() {

    var cfturnstileWooCheckoutAttempted = false;

    // Track actual submission attempts so we don't reset on every checkout refresh.
    jQuery( document.body ).on( 'submit', 'form.checkout', function() {
        cfturnstileWooCheckoutAttempted = true;
    });

    // If the page loads with an existing checkout error force a reset so the next submission has a fresh token.
    if ( jQuery('.woocommerce-error, .woocommerce-NoticeGroup-checkout .woocommerce-error').length ) {
        setTimeout( turnstileWooCheckoutReset, 50 );
    }

    jQuery( document.body ).on( 'update_checkout updated_checkout applied_coupon_in_checkout removed_coupon_in_checkout', function() {
        // Re-render if the widget container was replaced/emptied or is missing entirely.
        var $widget = jQuery('#cf-turnstile-woo-checkout');
        if ( !$widget.length || $widget.is(':empty') ) {
            // Use longer delay to ensure DOM replacement is complete.
            setTimeout( turnstileWooCheckoutReset, 300 );
            return;
        }

        // After a failed submit, Woo will typically refresh the checkout fragments.
        // Reset here (only after an attempted submit) to avoid the stale/used token issue.
        if ( cfturnstileWooCheckoutAttempted && jQuery('.woocommerce-error, .woocommerce-NoticeGroup-checkout .woocommerce-error').length ) {
            setTimeout( turnstileWooCheckoutReset, 300 );
            cfturnstileWooCheckoutAttempted = false;
        }
    });

    // Woo triggers this when the AJAX checkout submission returns an error.
    jQuery( document.body ).on( 'checkout_error', function() {
        setTimeout( turnstileWooCheckoutReset, 500 );
        cfturnstileWooCheckoutAttempted = false;
    });
});
function turnstileWooCheckoutReset() {
    if ( typeof turnstile === 'undefined' ) {
        return;
    }

    var el = document.getElementById('cf-turnstile-woo-checkout');
    if ( !el ) {
        return;
    }

    // If Woo replaced the container and it's now empty, render a fresh widget.
    if ( !el.innerHTML || el.innerHTML.trim() === '' ) {
        try {
            turnstile.render(el);
        } catch (e) {
            try { turnstile.render('#cf-turnstile-woo-checkout'); } catch (e2) {}
        }
        return;
    }

    // Preferred: reset the existing widget (clears the used/expired token).
    try {
        if ( typeof turnstile.reset === 'function' ) {
            turnstile.reset(el);
            return;
        }
    } catch (e) {}

    // Fallback: remove + render.
    try {
        turnstile.remove(el);
    } catch (e) {
        try { turnstile.remove('#cf-turnstile-woo-checkout'); } catch (e2) {}
    }
    try {
        turnstile.render(el);
    } catch (e) {
        try { turnstile.render('#cf-turnstile-woo-checkout'); } catch (e2) {}
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
        
        function setTurnstileExtensionData(token) {
            var dispatch = wp.data.dispatch('wc/store/checkout');
            if (typeof dispatch.setExtensionData === 'function') {
                dispatch.setExtensionData('simple-cloudflare-turnstile', { token: token });
            } else if (typeof dispatch.__internalSetExtensionData === 'function') {
                dispatch.__internalSetExtensionData('simple-cloudflare-turnstile', { token: token });
            }
        }

        function cfturnstileWooBlockCheckoutRender() {

            var turnstileItem = document.getElementById('cf-turnstile-woo-checkout');
            if (!turnstileItem) return;

            // If already initialized, try reset to preserve state but clear token
            if (turnstileItem.getAttribute('data-sct-init') === 'true' && turnstileItem.hasChildNodes()) {
                try {
                    turnstile.reset(turnstileItem);
                    setTurnstileExtensionData('');
                    return;
                } catch (e) {}
            }

            // Ensure any existing widget (auto-rendered or otherwise) is removed
            try { turnstile.remove(turnstileItem); } catch (e) {}

            try {
                turnstile.render(turnstileItem, {
                    sitekey: turnstileItem.dataset.sitekey,
                    callback: setTurnstileExtensionData,
                    'expired-callback': function() { setTurnstileExtensionData(''); }
                });
                turnstileItem.setAttribute('data-sct-init', 'true');
            } catch (e) {}
        }

        // Re-render Turnstile after the Place order button is clicked
        var cfturnstileWooBlockClickTimer = null;
        jQuery(document.body).on('click', '.wc-block-components-checkout-place-order-button', function() {
            if (cfturnstileWooBlockClickTimer) {
                clearTimeout(cfturnstileWooBlockClickTimer);
            }
            cfturnstileWooBlockClickTimer = setTimeout(function() {
                cfturnstileWooBlockCheckoutRender();
            }, 2000);
        });

        // Render Turnstile when the checkout data is updated and the widget is not present or not initialized
        var unsubscribe = wp.data.subscribe(function() {
            const turnstileItem = document.getElementById('cf-turnstile-woo-checkout');
            if (turnstileItem && (turnstileItem.innerHTML.trim() === '' || turnstileItem.getAttribute('data-sct-init') !== 'true')) {
                cfturnstileWooBlockCheckoutRender();
            }
        }, 'wc/store/cart');
    }
});