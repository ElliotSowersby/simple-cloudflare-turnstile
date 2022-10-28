function turnstileWPCallback() {
    jQuery('#wp-submit').css('pointer-events', 'auto');
    jQuery('#wp-submit').css('opacity', '1');
}
function turnstileCommentCallback() {
    jQuery('.cf-turnstile-comment').css('pointer-events', 'auto');
    jQuery('.cf-turnstile-comment').css('opacity', '1');
}
function turnstileWooLoginCallback() {
    jQuery('.woocommerce-form-login__submit').css('pointer-events', 'auto');
    jQuery('.woocommerce-form-login__submit').css('opacity', '1');
}
function turnstileWooRegisterCallback() {
    jQuery('.woocommerce-form-register__submit').css('pointer-events', 'auto');
    jQuery('.woocommerce-form-register__submit').css('opacity', '1');
}
function turnstileWooResetCallback() {
    jQuery('.woocommerce-ResetPassword .button').css('pointer-events', 'auto');
    jQuery('.woocommerce-ResetPassword .button').css('opacity', '1');
}
function turnstileCheckoutCallback() {

}
jQuery( document ).ready(function() {
	jQuery( document.body ).on( 'checkout_error', function(){
		if (document.getElementById('cf-turnstile')) {
			turnstile.reset('#cf-turnstile');
		}
	});
});
function turnstileCF7Callback() {
    jQuery('.wpcf7-submit').css('pointer-events', 'auto');
    jQuery('.wpcf7-submit').css('opacity', '1');
}
jQuery( document ).ready(function() {
	jQuery( ".wpcf7-form" ).on('submit', function() {
		if (document.getElementById('cf-turnstile')) {
			turnstile.reset('#cf-turnstile');
		}
	});
});
function turnstileMC4WPCallback() {
    jQuery('.mc4wp-form-fields input[type=submit]').css('pointer-events', 'auto');
    jQuery('.mc4wp-form-fields input[type=submit]').css('opacity', '1');
}
function turnstileBPCallback() {
    jQuery('#buddypress #signup-form .submit').css('pointer-events', 'auto');
    jQuery('#buddypress #signup-form .submit').css('opacity', '1');
}
function turnstileWPFCallback() {
    jQuery('.wpforms-submit').css('pointer-events', 'auto');
    jQuery('.wpforms-submit').css('opacity', '1');
}
function turnstileFluentCallback() {
    jQuery('.fluentform .ff-btn-submit').css('pointer-events', 'auto');
    jQuery('.fluentform .ff-btn-submit').css('opacity', '1');
}
function turnstileGravityCallback() {
    jQuery('.gform_button').css('pointer-events', 'auto');
    jQuery('.gform_button').css('opacity', '1');
}
function turnstileWPDiscuzCallback() {
    jQuery('.wc_comm_submit').css('pointer-events', 'auto');
    jQuery('.wc_comm_submit').css('opacity', '1');
}
jQuery( document ).ready(function() {
	jQuery( '.wc_comm_submit' ).click(function(){
		if (document.getElementById('cf-turnstile')) {
      setTimeout(function() {
			     turnstile.reset('#cf-turnstile');
      }, 2000);
		}
	});
});
