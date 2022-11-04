/* WP */
function turnstileWPCallback() {
    jQuery('#wp-submit').css('pointer-events', 'auto');
    jQuery('#wp-submit').css('opacity', '1');
}
function turnstileCommentCallback() {
    jQuery('.cf-turnstile-comment').css('pointer-events', 'auto');
    jQuery('.cf-turnstile-comment').css('opacity', '1');
}
/* Woo */
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
/ * CF7 */
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
/* MC4WP */
function turnstileMC4WPCallback() {
    jQuery('.mc4wp-form-fields input[type=submit]').css('pointer-events', 'auto');
    jQuery('.mc4wp-form-fields input[type=submit]').css('opacity', '1');
}
/* BuddyPress */
function turnstileBPCallback() {
    jQuery('#buddypress #signup-form .submit').css('pointer-events', 'auto');
    jQuery('#buddypress #signup-form .submit').css('opacity', '1');
}
/* WPForms */
function turnstileWPFCallback() {
    jQuery('.wpforms-submit').css('pointer-events', 'auto');
    jQuery('.wpforms-submit').css('opacity', '1');
}
/* Fluent Forms */
function turnstileFluentCallback() {
    jQuery('.fluentform .ff-btn-submit').css('pointer-events', 'auto');
    jQuery('.fluentform .ff-btn-submit').css('opacity', '1');
}
/* Formidable Forms */
function turnstileFormidableCallback() {
    jQuery('.frm_forms .frm_button_submit').css('pointer-events', 'auto');
    jQuery('.frm_forms .frm_button_submit').css('opacity', '1');
}
/* Gravity Forms */
function turnstileGravityCallback() {
    jQuery('.gform_button').css('pointer-events', 'auto');
    jQuery('.gform_button').css('opacity', '1');
}
/* wpDiscuz */
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
/* Elementor */
jQuery( document ).ready(function() {
	jQuery( ".elementor-form" ).on('submit', function() {
		if (document.getElementById('cf-turnstile')) {
      setTimeout(function() {
			     turnstile.reset('#cf-turnstile');
      }, 2000);
		}
	});
});
/* Ultimate Member */
function turnstileUMCallback() {
    jQuery('#um-submit-btn').css('pointer-events', 'auto');
    jQuery('#um-submit-btn').css('opacity', '1');
}
