<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get turnstile field: WP
function cfturnstile_field() { cfturnstile_field_show('#wp-submit', 'turnstileWPCallback'); }

// WP Login Check
if(get_option('cfturnstile_login')) {
	if(empty(get_option('cfturnstile_tested')) || get_option('cfturnstile_tested') == 'yes') {
		add_action('login_form','cfturnstile_field');
		add_action('wp_authenticate_user', 'cfturnstile_wp_login_check', 10, 1);
		function cfturnstile_wp_login_check($user){
			if ( $GLOBALS['pagenow'] === 'wp-login.php' ) {
				$check = cfturnstile_check();
				$success = $check['success'];
				if($success != true) {
					$user = new WP_Error( 'authentication_failed', __( 'Please verify that you are human.', 'simple-cloudflare-turnstile' ) );
				}
			}
			return $user;
		}
	}
}

// WP Register Check
if(get_option('cfturnstile_register')) {
	add_action('register_form','cfturnstile_field');
	add_action('registration_errors', 'cfturnstile_wp_register_check', 10, 3);
	function cfturnstile_wp_register_check($errors, $sanitized_user_login, $user_email) {
		$check = cfturnstile_check();
		$success = $check['success'];
		if($success != true) {
			$errors->add( 'cfturnstile_error', sprintf('<strong>%s</strong>: %s',__( 'ERROR', 'simple-cloudflare-turnstile' ), __( 'Please verify that you are human.', 'simple-cloudflare-turnstile' ) ) );
		}
		return $errors;
	}
}

// WP Reset Check
if(get_option('cfturnstile_reset')) {
  if(!is_admin()) {
  	add_action('lostpassword_form','cfturnstile_field');
  	add_action('lostpassword_post','cfturnstile_wp_reset_check', 10, 1);
  	function cfturnstile_wp_reset_check($validation_errors) {
  		if ( $GLOBALS['pagenow'] === 'wp-login.php' ) {
  			$check = cfturnstile_check();
  			$success = $check['success'];
  			if($success != true) {
  				$validation_errors->add( 'cfturnstile_error', __( 'Please verify that you are human.', 'simple-cloudflare-turnstile' ) );
  			}
  		}
  	}
  }
}

// WP Comment
if(get_option('cfturnstile_comment')) {
  if(!is_admin()) {
  	add_action('comment_form_submit_button','cfturnstile_field_comment', 100, 2);
  	// Create and display the turnstile field for comments.
  	function cfturnstile_field_comment( $submit_button, $args ) {
    		$key = esc_attr( get_option('cfturnstile_key') );
    		$theme = esc_attr( get_option('cfturnstile_theme') );
    		$submit_before = '';
    		$submit_after = '';
    		$callback = '';
    		if(get_option('cfturnstile_disable_button')) {
    			$callback = 'turnstileCommentCallback';
    		}
    		$submit_before .= '<div class="cf-turnstile" data-callback="'.$callback.'" data-sitekey="'.sanitize_text_field($key).'" data-theme="'.sanitize_text_field($theme).'"></div>';
    		if(get_option('cfturnstile_disable_button')) {
    			$submit_before .= '<div class="cf-turnstile-comment" style="pointer-events: none; opacity: 0.5;">';
    			$submit_after .= "</div>";
    		}
    		return $submit_before . $submit_button . $submit_after;
  	}
  	// Comment Validation
  	add_action('preprocess_comment','cfturnstile_wp_comment_check', 10, 1);
  	function cfturnstile_wp_comment_check($commentdata) {
  		$check = cfturnstile_check();
  		$success = $check['success'];
  		if($success != true) {
  			wp_die( '<p><strong>' . esc_html__( 'ERROR:', 'advanced-google-recaptcha' ) . '</strong> ' . esc_html__( 'Please verify that you are human.', 'simple-cloudflare-turnstile' ) . '</p>', 'simple-cloudflare-turnstile', array( 'response'  => 403, 'back_link' => 1, ) );
  		}
  		return $commentdata;
  	}
  }
}
