/* Woo Checkout */
( function () {

    var WIDGET_ID = 'cf-turnstile-woo-checkout';

    // Prefer the widget inside the checkout form, as Divi can leave stray copies
    function cfturnstileWooWidget() {
        return document.querySelector( 'form.checkout #' + WIDGET_ID ) || document.getElementById( WIDGET_ID );
    }

    function cfturnstileWooOnReady( fn ) {
        if ( document.readyState === 'loading' ) {
            document.addEventListener( 'DOMContentLoaded', fn );
        } else {
            fn();
        }
    }

    function cfturnstileWooClicked( e, selector ) {
        return !!( e.target && e.target.closest && e.target.closest( selector ) );
    }

    // Explicit render ignores data-*-callback, so pass the options in
    function cfturnstileWooOpts( target ) {
        return ( typeof window.cfturnstileOpts === 'function' ) ? window.cfturnstileOpts( target ) : {};
    }

    // Rendered but no token yet. Checks every input, as turnstile.remove() can leave an empty one behind
    function cfturnstileWooAwaitingToken( el ) {
        var inputs = el.querySelectorAll( 'input[name="cf-turnstile-response"]' );
        for ( var i = 0; i < inputs.length; i++ ) {
            if ( inputs[ i ].value ) {
                return false;
            }
        }
        return inputs.length > 0;
    }

    /* Give the classic checkout widget a fresh token */
    function turnstileWooCheckoutReset() {
        if ( typeof turnstile === 'undefined' ) {
            return;
        }

        var el = cfturnstileWooWidget();
        if ( !el ) {
            return;
        }

        // Empty container, render a new widget
        if ( !el.firstElementChild ) {
            try { turnstile.render( el, cfturnstileWooOpts( el ) ); } catch ( e ) {}
            return;
        }

        // No token yet, so nothing to clear
        if ( cfturnstileWooAwaitingToken( el ) ) {
            return;
        }

        // Otherwise reset in place
        try {
            turnstile.reset( el );
            return;
        } catch ( e ) {}

        // reset() failed, so rebuild it
        try { turnstile.remove( el ); } catch ( e ) {}
        try { turnstile.render( el, cfturnstileWooOpts( el ) ); } catch ( e ) {}
    }

    /* "Show login" toggle: rebuild the login widget once the panel is open */
    document.addEventListener( 'click', function ( e ) {
        if ( !cfturnstileWooClicked( e, '.showlogin, .show-login, .e-show-login, .woocommerce-form-login-toggle a' ) ) {
            return;
        }
        setTimeout( function () {
            if ( typeof turnstile === 'undefined' ) {
                return;
            }
            try { turnstile.remove( '.sct-woocommerce-login' ); } catch ( err ) {}
            try { turnstile.render( '.sct-woocommerce-login', cfturnstileWooOpts( '.sct-woocommerce-login' ) ); } catch ( err ) {}
        }, 250 );
    } );

    /* Classic checkout: keep the widget valid across fragment refreshes */
    function cfturnstileWooRun() {

        var $ = window.jQuery;

        $( document ).ready( function () {

            var attempted = false;

            function hasCheckoutError() {
                return !!document.querySelector( '.woocommerce-error' );
            }

            // Only reset after a real submission attempt, not on every checkout refresh.
            $( document.body ).on( 'submit', 'form.checkout', function () {
                attempted = true;
            } );

            // Error already showing on page load
            if ( hasCheckoutError() ) {
                setTimeout( turnstileWooCheckoutReset, 50 );
            }

            $( document.body ).on( 'update_checkout updated_checkout applied_coupon_in_checkout removed_coupon_in_checkout', function () {
                var el = cfturnstileWooWidget();

                // Container replaced or emptied, re-render
                if ( !el || !el.firstElementChild ) {
                    setTimeout( turnstileWooCheckoutReset, 300 );
                    return;
                }

                // Failed submit, clear the used token
                if ( attempted && hasCheckoutError() ) {
                    setTimeout( turnstileWooCheckoutReset, 300 );
                    attempted = false;
                }
            } );

            // Fired when the AJAX checkout submission comes back with an error.
            $( document.body ).on( 'checkout_error', function () {
                setTimeout( turnstileWooCheckoutReset, 500 );
                attempted = false;
            } );
        } );
    }

    /* Woo Checkout Block */
    function cfturnstileWooBlockRun() {

        function setExtensionData( token ) {
            var dispatch = wp.data.dispatch( 'wc/store/checkout' );
            if ( !dispatch ) {
                return;
            }
            if ( typeof dispatch.setExtensionData === 'function' ) {
                dispatch.setExtensionData( 'simple-cloudflare-turnstile', { token: token } );
            } else if ( typeof dispatch.__internalSetExtensionData === 'function' ) {
                dispatch.__internalSetExtensionData( 'simple-cloudflare-turnstile', { token: token } );
            }
        }

        var widgetId = null;
        var renderedEl = null;
        var submitted = false;

        function renderBlockWidget() {
            // API may still be loading, the subscription below retries
            if ( typeof turnstile === 'undefined' ) {
                return;
            }

            var el = cfturnstileWooWidget();
            if ( !el ) {
                return;
            }

            // Already rendered into this element, reset in place
            if ( el === renderedEl && el.firstElementChild ) {
                // No token yet, so nothing to clear
                if ( cfturnstileWooAwaitingToken( el ) ) {
                    return;
                }
                try {
                    turnstile.reset( el );
                    setExtensionData( '' );
                    return;
                } catch ( e ) {}
            }

            // Woo can swap the container for a copy, so drop the old widget by id
            if ( widgetId !== null ) {
                try { turnstile.remove( widgetId ); } catch ( e ) {}
                widgetId = null;
            }
            try { turnstile.remove( el ); } catch ( e ) {}

            try {
                widgetId = turnstile.render( el, {
                    sitekey: el.dataset.sitekey,
                    appearance: el.dataset.appearance || 'always',
                    callback: setExtensionData,
                    'expired-callback': function () { setExtensionData( '' ); }
                } );
                renderedEl = el;
            } catch ( e ) {}
        }

        // The order was sent but failed, possibly at the gateway after it (e.g. 3DS), so the token may be spent
        function resetAfterFailedSubmit() {
            var checkout = wp.data.select( 'wc/store/checkout' );
            if ( !checkout || typeof checkout.isProcessing !== 'function' || typeof checkout.hasError !== 'function' ) {
                return;
            }
            if ( checkout.isProcessing() ) {
                submitted = true;
                return;
            }
            // Gateway still working, e.g. 3DS
            if ( !submitted || ( checkout.isAfterProcessing() && !checkout.hasError() ) ) {
                return;
            }
            submitted = false;
            if ( checkout.hasError() || checkout.isIdle() ) {
                renderBlockWidget();
            }
        }

        // Render when the widget is missing or Woo replaced its container
        function onStoreChange() {
            var el = cfturnstileWooWidget();
            if ( el && ( el !== renderedEl || !el.firstElementChild ) ) {
                renderBlockWidget();
            }
            resetAfterFailedSubmit();
        }
        wp.data.subscribe( onStoreChange, 'wc/store/cart' );
        wp.data.subscribe( onStoreChange, 'wc/store/checkout' );

        renderBlockWidget();
    }

    // Run fn once ready() passes, as a delay-JS optimizer only loads our dependencies on the first interaction
    function cfturnstileWooWhen( ready, fn ) {
        if ( ready() ) {
            fn();
            return;
        }
        var tries = 0;
        var wait = setInterval( function () {
            if ( ready() ) {
                clearInterval( wait );
                fn();
            } else if ( ++tries > 6000 ) { // ~10 min safety cap
                clearInterval( wait );
            }
        }, 100 );
    }

    // Block checkout only, the classic checkout uses the same widget id
    cfturnstileWooOnReady( function () {
        if ( document.querySelector( '.wp-block-woocommerce-checkout, .wc-block-checkout' ) ) {
            cfturnstileWooWhen( function () {
                return typeof wp !== 'undefined' && !!wp.data && !!wp.data.select( 'wc/store/checkout' );
            }, cfturnstileWooBlockRun );
        }
    } );

    // Classic checkout: queue the widget if nothing else did, so it does not depend on Woo's jQuery events
    cfturnstileWooOnReady( function () {
        var el = document.querySelector( 'form.checkout #' + WIDGET_ID );
        var queue = window.cfturnstileQueue = window.cfturnstileQueue || [];
        if ( el && !el.firstElementChild && queue.indexOf( '-woo-checkout' ) === -1 ) {
            queue.push( '-woo-checkout' );
            if ( typeof window.cfturnstileRender === 'function' ) {
                window.cfturnstileRender();
            }
        }
    } );

    cfturnstileWooWhen( function () { return typeof window.jQuery !== 'undefined'; }, cfturnstileWooRun );

} )();
