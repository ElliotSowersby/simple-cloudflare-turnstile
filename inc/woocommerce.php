<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get turnstile field: Woo Login
function cfturnstile_field_woo_login() { cfturnstile_field_show('.woocommerce-form-login__submit', 'turnstileWooLoginCallback', 'woocommerce-login', '-woo-login'); }

// Get turnstile field: Woo Register
function cfturnstile_field_woo_register() { cfturnstile_field_show('.woocommerce-form-register__submit', 'turnstileWooRegisterCallback', 'woocommerce-register', '-woo-register'); }

// Get turnstile field: Woo Reset
function cfturnstile_field_woo_reset() {cfturnstile_field_show('.woocommerce-ResetPassword .button', 'turnstileWooResetCallback', 'woocommerce-reset', '-woo-reset'); }

// Get turnstile field: Woo Checkout
function cfturnstile_field_checkout() {
	$guest_only = esc_attr( get_option('cfturnstile_guest_only') );
	if( !$guest_only || ($guest_only && !is_user_logged_in()) ) {
		if(get_option('cfturnstile_woo_checkout_pos') == "afterpay") {
			echo "<br/>";
		}
		cfturnstile_field_show('', '', 'woocommerce-checkout', '-woo-checkout');
		?>
		<?php
	}
}

// Woo Checkout Check
if(get_option('cfturnstile_woo_checkout')) {
	if(empty(get_option('cfturnstile_woo_checkout_pos')) || get_option('cfturnstile_woo_checkout_pos') == "beforepay") {
		add_action('woocommerce_review_order_before_payment', 'cfturnstile_field_checkout', 10);
	} elseif(get_option('cfturnstile_woo_checkout_pos') == "afterpay") {
		add_action('woocommerce_review_order_after_payment', 'cfturnstile_field_checkout', 10);
	} elseif(get_option('cfturnstile_woo_checkout_pos') == "beforebilling") {
		add_action('woocommerce_before_checkout_billing_form', 'cfturnstile_field_checkout', 10);
	} elseif(get_option('cfturnstile_woo_checkout_pos') == "afterbilling") {
		add_action('woocommerce_after_checkout_billing_form', 'cfturnstile_field_checkout', 10);
	} elseif(get_option('cfturnstile_woo_checkout_pos') == "beforesubmit") {
		add_action('woocommerce_review_order_before_submit', 'cfturnstile_field_checkout', 10);
	}
	add_action('woocommerce_checkout_process', 'cfturnstile_woo_checkout_check');
	function cfturnstile_woo_checkout_check() {
		$guest = esc_attr( get_option('cfturnstile_guest_only') );
		if( !$guest || ( $guest && !is_user_logged_in() ) ) {
			$check = cfturnstile_check();
			$success = $check['success'];
			if($success != true) {
				wc_add_notice( cfturnstile_failed_message(), 'error');
			}
		}
	}
}

// Woo Checkout Pay Order Check
if(get_option('cfturnstile_woo_checkout_pay')) {
	add_action('woocommerce_pay_order_before_submit', 'cfturnstile_field_checkout', 10);
	add_action('woocommerce_before_pay_action', 'cfturnstile_woo_checkout_pay_check', 10, 2);
	function cfturnstile_woo_checkout_pay_check($order) {
		$check = cfturnstile_check();
		$success = $check['success'];
		if($success != true) {
			wc_add_notice( cfturnstile_failed_message(), 'error');
		}
	}
}

// Woo Login Check
if(get_option('cfturnstile_woo_login')) {
	if(empty(get_option('cfturnstile_tested')) || get_option('cfturnstile_tested') == 'yes') {
		add_action('woocommerce_login_form','cfturnstile_field_woo_login');
		add_action('authenticate', 'cfturnstile_woo_login_check', 21, 1);
		function cfturnstile_woo_login_check($user){
			if(isset($_POST['woocommerce-login-nonce'])) {
				$check = cfturnstile_check();
				$success = $check['success'];
				if($success != true) {
					$user = new WP_Error( 'cfturnstile_error', cfturnstile_failed_message() );
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
				$validation_errors->add( 'cfturnstile_error', cfturnstile_failed_message() );
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
				$validation_errors->add( 'cfturnstile_error', cfturnstile_failed_message() );
			}
		}
	}
}
