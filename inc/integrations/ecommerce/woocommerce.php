<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get turnstile field: Woo Login
function cfturnstile_field_woo_login() {
	if(empty(get_option('cfturnstile_tested')) || get_option('cfturnstile_tested') == 'yes') {
		$unique_id = wp_rand();
		cfturnstile_field_show('.woocommerce-form-login__submit', 'turnstileWooLoginCallback', 'woocommerce-login-' . $unique_id, '-woo-login-' . $unique_id, 'sct-woocommerce-login');
	}
}

// Get turnstile field: Woo Register
function cfturnstile_field_woo_register() {
	$unique_id = wp_rand();
	cfturnstile_field_show('.woocommerce-form-register__submit', 'turnstileWooRegisterCallback', 'woocommerce-register-' . $unique_id, '-woo-register-' . $unique_id, 'sct-woocommerce-register');
}

// Get turnstile field: Woo Reset
function cfturnstile_field_woo_reset() {
	$unique_id = wp_rand();
	cfturnstile_field_show('.woocommerce-ResetPassword .button', 'turnstileWooResetCallback', 'woocommerce-reset-' . $unique_id, '-woo-reset-' . $unique_id, 'sct-woocommerce-reset');
}

// Get turnstile field: Woo Checkout
function cfturnstile_field_checkout() {
	$checkout_page_id = get_option('woocommerce_checkout_page_id');
	$checkout_page_content = get_post_field('post_content', $checkout_page_id);
	if (strpos($checkout_page_content, 'wp:woocommerce/checkout') !== false) {
		return;
	}
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
	// WooCommerce Checkout
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
	// CheckoutWC
	add_action('cfw_after_cart_summary_totals', 'cfturnstile_field_checkout', 10);
	// Check Turnstile
	add_action('woocommerce_checkout_process', 'cfturnstile_woo_checkout_check');
	function cfturnstile_woo_checkout_check() {

		// Skip if WooCommerce Checkout block is used
		$checkout_page_id = get_option('woocommerce_checkout_page_id');
		if($checkout_page_id) {
			$checkout_page_content = get_post_field('post_content', $checkout_page_id);
			if (strpos($checkout_page_content, 'wp:woocommerce/checkout') !== false) {
				return;
			}
		}

		// Skip if Turnstile disabled for payment method
		$skip = 0;
		if ( isset( $_POST['payment_method'] ) ) {
			$chosen_payment_method = sanitize_text_field( $_POST['payment_method'] );
			// Retrieve the selected payment methods from the cfturnstile_selected_payment_methods option
			$selected_payment_methods = get_option('cfturnstile_selected_payment_methods', array());
			if(is_array($selected_payment_methods)) {
				// Check if the chosen payment method is in the selected payment methods array
				if ( in_array( $chosen_payment_method, $selected_payment_methods, true ) ) {
					$skip = 1;
				}
			}
		}

		// Start session
		if (!session_id()) { session_start(); }
		// Check if already validated
		if(isset($_SESSION['cfturnstile_checkout_checked']) && wp_verify_nonce( sanitize_text_field($_SESSION['cfturnstile_checkout_checked']), 'cfturnstile_checkout_check' )) {
			return;
		}

		// Check if guest only enabled
		$guest = esc_attr( get_option('cfturnstile_guest_only') );
		// Check
		if( !$skip && (!$guest || ( $guest && !is_user_logged_in() )) ) {
			$check = cfturnstile_check();
			$success = $check['success'];
			if($success != true) {
				wc_add_notice( cfturnstile_failed_message(), 'error');
			} else {
				$nonce = wp_create_nonce( 'cfturnstile_checkout_check' );
				$_SESSION['cfturnstile_checkout_checked'] = $nonce;
			}
		}
	}
}
// On payment complete clear session
add_action('woocommerce_checkout_order_processed', 'cfturnstile_woo_checkout_clear', 10, 1);
function cfturnstile_woo_checkout_clear($order_id) {
	if(isset($_SESSION['cfturnstile_checkout_checked'])) { unset($_SESSION['cfturnstile_checkout_checked']); }
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
	add_action('woocommerce_login_form','cfturnstile_field_woo_login');
	if(!get_option('cfturnstile_login')) {
		add_action('authenticate', 'cfturnstile_woo_login_check', 21, 1);
		function cfturnstile_woo_login_check($user) {

			// Check skip
			if(!isset($user->ID)) { return $user; }
			if(!isset($_POST['woocommerce-login-nonce'])) { return $user; } // Skip if not WooCommerce login
			if(defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST) { return $user; } // Skip XMLRPC
			if(defined( 'REST_REQUEST' ) && REST_REQUEST) { return $user; } // Skip REST API
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
		// Clear session on login
		add_action('wp_login', 'cfturnstile_woo_login_clear', 10, 2);
		function cfturnstile_woo_login_clear($user_login, $user) {
			if(isset($_SESSION['cfturnstile_login_checked'])) { unset($_SESSION['cfturnstile_login_checked']); }
		}
	}
}

// Woo Register Check
if(get_option('cfturnstile_woo_register')) {
	add_action('woocommerce_register_form','cfturnstile_field_woo_register');
	if(!is_admin()) { // Prevents admin registration from failing
		add_action('woocommerce_register_post', 'cfturnstile_woo_register_check', 10, 3);
	}
	function cfturnstile_woo_register_check($username, $email, $validation_errors) {
		if(defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST) { return; } // Skip XMLRPC
		if(defined( 'REST_REQUEST' ) && REST_REQUEST) { return; } // Skip REST API
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