<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get turnstile field: WP
function cfturnstile_field_login() {
	if(isset($_SESSION['cfturnstile_login_checked'])) { unset($_SESSION['cfturnstile_login_checked']); }
	cfturnstile_field_show('#wp-submit', 'turnstileWPCallback', 'wordpress-login', '-' . wp_rand());
}
function cfturnstile_field_register() { cfturnstile_field_show('#wp-submit', 'turnstileWPCallback', 'wordpress-register', '-' . wp_rand()); }
function cfturnstile_field_reset() { cfturnstile_field_show('#wp-submit', 'turnstileWPCallback', 'wordpress-reset', '-' . wp_rand()); }

// WP Login Check
if(get_option('cfturnstile_login')) {
    if(empty(get_option('cfturnstile_tested')) || get_option('cfturnstile_tested') == 'yes') {
        add_action('login_form','cfturnstile_field_login');
        add_action('authenticate', 'cfturnstile_wp_login_check', 21, 1);
        function cfturnstile_wp_login_check($user) {

            // Check skip
			if(!isset($user->ID)) { return $user; }
            if(defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST) { return $user; } // Skip XMLRPC
			if(defined( 'REST_REQUEST' ) && REST_REQUEST) { return $user; } // Skip REST API
            if(isset($_POST['edd_login_nonce']) && wp_verify_nonce( sanitize_text_field($_POST['edd_login_nonce']), 'edd-login-nonce') ) { return $user; } // Skip EDD
            if(is_wp_error($user) && isset($user->errors['empty_username']) && isset($user->errors['empty_password']) ) {return $user; } // Skip Errors

            // Start session
            if (!session_id()) { session_start(); }

            // Check if already validated
            if(isset($_SESSION['cfturnstile_login_checked']) && wp_verify_nonce( sanitize_text_field($_SESSION['cfturnstile_login_checked']), 'cfturnstile_login_check' )) {
                return $user;
            }

            // Check Turnstile
            $check = cfturnstile_check();
            $success = $check['success'];
            if($success != true) {
                $user = new WP_Error( 'cfturnstile_error', cfturnstile_failed_message() );
            } else {
                $nonce = wp_create_nonce( 'cfturnstile_login_check' );
                $_SESSION['cfturnstile_login_checked'] = $nonce;
            }
            
            return $user;
            
        }
    }
	// Clear session on login
	add_action('wp_login', 'cfturnstile_wp_login_clear', 10, 2);
	function cfturnstile_wp_login_clear($user_login, $user) {
		if(isset($_SESSION['cfturnstile_login_checked'])) { unset($_SESSION['cfturnstile_login_checked']); }
	}
}

// WP Register Check
if(get_option('cfturnstile_register')) {
	add_action('register_form','cfturnstile_field_register');
	add_action('registration_errors', 'cfturnstile_wp_register_check', 10, 3);
	function cfturnstile_wp_register_check($errors, $sanitized_user_login, $user_email) {
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) { return $errors; } // Skip XMLRPC
		if(isset($_POST['woocommerce-register-nonce'])) { return $errors; } // Skip Woo
		$check = cfturnstile_check();
		$success = $check['success'];
		if($success != true) {
			$errors->add( 'cfturnstile_error', sprintf('<strong>%s</strong>: %s',__( 'ERROR', 'simple-cloudflare-turnstile' ), cfturnstile_failed_message() ) );
		}
		return $errors;
	}
}

// WP Reset Check
if(get_option('cfturnstile_reset')) {
  if(!is_admin()) {
  	add_action('lostpassword_form','cfturnstile_field_reset');
  	add_action('lostpassword_post','cfturnstile_wp_reset_check', 10, 1);
  	function cfturnstile_wp_reset_check($validation_errors) {
		if(stripos($_SERVER["SCRIPT_NAME"], strrchr(wp_login_url(), '/')) !== false) { // Check if WP login page
  			$check = cfturnstile_check();
  			$success = $check['success'];
  			if($success != true) {
  				$validation_errors->add( 'cfturnstile_error', cfturnstile_failed_message() );
  			}
  		}
  	}
  }
}

// WP Comment
if(get_option('cfturnstile_comment') && !cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php')) {
  if( !is_admin() || wp_doing_ajax() ) {
	add_action("comment_form_after", "cfturnstile_comment_form_after");
	function cfturnstile_comment_form_after() {
		if ( wp_doing_ajax() ) {
			wp_print_scripts('cfturnstile');
			wp_print_styles('cfturnstile-css');
		}
	}
  	add_action('comment_form_submit_button','cfturnstile_field_comment', 100, 2);
  	// Create and display the turnstile field for comments.
  	function cfturnstile_field_comment( $submit_button, $args ) {
		if(!cfturnstile_whitelisted()) {
			do_action("cfturnstile_enqueue_scripts");
			$unique_id = wp_rand();
			$key = esc_attr( get_option('cfturnstile_key') );
			$theme = esc_attr( get_option('cfturnstile_theme') );
			$language = esc_attr(get_option('cfturnstile_language'));
			$appearance = esc_attr(get_option('cfturnstile_appearance', 'always'));
			if(!$language) { $language = 'auto'; }
			$submit_before = '';
			$submit_after = '';
			$callback = '';
			if(get_option('cfturnstile_disable_button')) { $callback = 'turnstileCommentCallback'; }
			$submit_before .= '<span id="cf-turnstile-c-'.$unique_id.'" class="cf-turnstile" data-action="wordpress-comment" data-callback="'.$callback.'" data-sitekey="'.sanitize_text_field($key).'" data-theme="'.sanitize_text_field($theme).'" data-language="'.sanitize_text_field($language).'" data-appearance="'.sanitize_text_field($appearance).'" data-retry="auto" data-retry-interval="1000"></span><br/>';
			if(get_option('cfturnstile_disable_button')) {
				$submit_before .= '<span class="cf-turnstile-comment" style="pointer-events: none; opacity: 0.5;">';
				$submit_after .= "</span>";
			}
			$submit_after .= cfturnstile_force_render("-c-" . $unique_id);
			// Script to render turnstile when clicking reply
			$script = '<script type="text/javascript">document.addEventListener("DOMContentLoaded", function() { document.body.addEventListener("click", function(event) { if (event.target.matches(".comment-reply-link, #cancel-comment-reply-link")) { turnstile.reset(".comment-form .cf-turnstile"); } }); });</script>';
			// If ajax comments are enabled, we need to re-render the turnstile after the comment is submitted
			if(cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php') || cft_is_plugin_active('wp-ajaxify-comments/wp-ajaxify-comments.php') || get_option('cfturnstile_ajax_comments')) {
				$script .= '<script type="text/javascript">jQuery(document).ajaxComplete(function() { setTimeout(function() { turnstile.render("#cf-turnstile-c-'.$unique_id.'"); }, 1000); });</script>';
			}
			// Return button
			return $submit_before . $submit_button . $submit_after . $script;
		} else {
			return $submit_button;
		}
  	}
  	// Comment Validation
  	add_action('pre_comment_on_post','cfturnstile_wp_comment_check', 10, 1);
  	function cfturnstile_wp_comment_check($commentdata) {
		if(is_admin()) { return $commentdata; }
		if(!empty($_POST)) {
			$check = cfturnstile_check();
			$success = $check['success'];
			if($success != true) {
				wp_die( '<p><strong>' . esc_html__( 'ERROR:', 'simple-cloudflare-turnstile' ) . '</strong> ' . cfturnstile_failed_message() . '</p>', 'simple-cloudflare-turnstile', array( 'response'  => 403, 'back_link' => 1, ) );
			}
			return $commentdata;
      	}
  	}
  }
}
