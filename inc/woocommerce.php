<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get turnstile field: Woo Login
function cfturnstile_field_woo_login() { cfturnstile_field_show('.woocommerce-form-login__submit', 'turnstileWooLoginCallback'); }

// Get turnstile field: Woo Register
function cfturnstile_field_woo_register() { cfturnstile_field_show('.woocommerce-form-register__submit', 'turnstileWooRegisterCallback'); }

// Get turnstile field: Woo Reset
function cfturnstile_field_woo_reset() {cfturnstile_field_show('.woocommerce-ResetPassword .button', 'turnstileWooResetCallback'); }

// Get turnstile field: Woo Checkout
function cfturnstile_field_checkout() {
	$guest_only = esc_attr( get_option('cfturnstile_guest_only') );
	if( !$guest_only || ($guest_only && !is_user_logged_in()) ) {
		cfturnstile_field_show('', '');
	}
}

// Woo Checkout Check
if(get_option('cfturnstile_woo_checkout')) {
	add_action('woocommerce_review_order_before_payment', 'cfturnstile_field_checkout', 10);
	add_action('woocommerce_checkout_process', 'cfturnstile_woo_checkout_check');
	function cfturnstile_woo_checkout_check() {
		$guest = esc_attr( get_option('cfturnstile_guest_only') );
		if( !$guest || ( $guest && !is_user_logged_in() ) ) {
			$check = cfturnstile_check();
			$success = $check['success'];
			if($success != true) {
				wc_add_notice( __( 'Please verify that you are human.', 'simple-cloudflare-turnstile' ), 'error');
			}
		}
	}
}

// Woo Login Check
if(get_option('cfturnstile_woo_login')) {
	if(empty(get_option('cfturnstile_tested')) || get_option('cfturnstile_tested') == 'yes') {
		add_action('woocommerce_login_form','cfturnstile_field_woo_login');
		add_action('wp_authenticate_user', 'cfturnstile_woo_login_check', 10, 1);
		function cfturnstile_woo_login_check($user){
			if(isset($_POST['woocommerce-login-nonce'])) {
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

// Woo Register Check
if(get_option('cfturnstile_woo_register')) {
	add_action('woocommerce_register_form','cfturnstile_field_woo_register');
	add_action('woocommerce_register_post', 'cfturnstile_woo_register_check', 10, 3);
	function cfturnstile_woo_register_check($username, $email, $validation_errors) {
		if(!is_checkout()) {
			$check = cfturnstile_check();
			$success = $check['success'];
			if($success != true) {
				$validation_errors->add( 'cfturnstile_error', __( 'Please verify that you are human.', 'simple-cloudflare-turnstile' ) );
			}
		}
	}
}

// Woo Reset Check
if(get_option('cfturnstile_woo_reset')) {
	add_action('woocommerce_lostpassword_form','cfturnstile_field_woo_reset');
	add_action('lostpassword_post','cfturnstile_woo_reset_check', 10, 1);
	function cfturnstile_woo_reset_check($validation_errors) {
		if(isset($_POST['woocommerce-lost-password-nonce'])) {
			$check = cfturnstile_check();
			$success = $check['success'];
			if($success != true) {
				$validation_errors->add( 'cfturnstile_error', __( 'Please verify that you are human.', 'simple-cloudflare-turnstile' ) );
			}
		}
	}
}
