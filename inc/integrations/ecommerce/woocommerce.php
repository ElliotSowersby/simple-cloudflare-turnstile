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
	if(is_wc_endpoint_url('order-received')) {
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

// Render after checkout block
function cfturnstile_render_post_block($block_content) {
	ob_start();
	echo $block_content;
	cfturnstile_field_checkout();
	$block_content = ob_get_contents();
	ob_end_clean();
	return $block_content;
}

// Render before checkout block
function cfturnstile_render_pre_block($block_content) {
	ob_start();
	cfturnstile_field_checkout();
	echo $block_content;
	$block_content = ob_get_contents();
	ob_end_clean();
	return $block_content;
}

// Woo Checkout Check
if(get_option('cfturnstile_woo_checkout')) {
	// WooCommerce Checkout
	// CheckoutWC: Only hook when CheckoutWC templates are enabled
	if(function_exists( 'cfw_templates_disabled' ) && ! cfw_templates_disabled()) {
		add_action('cfw_checkout_before_payment_method_tab_nav', 'cfturnstile_field_checkout', 10);
	} elseif(empty(get_option('cfturnstile_woo_checkout_pos')) || get_option('cfturnstile_woo_checkout_pos') == "beforepay") {
		add_action('woocommerce_review_order_before_payment', 'cfturnstile_field_checkout', 10);
		add_filter('render_block_woocommerce/checkout-payment-block', 'cfturnstile_render_pre_block', 999, 1); // Before Payment block.
	} elseif(get_option('cfturnstile_woo_checkout_pos') == "afterpay") {
		add_action('woocommerce_review_order_after_payment', 'cfturnstile_field_checkout', 10);
		add_filter('render_block_woocommerce/checkout-payment-block', 'cfturnstile_render_post_block', 999, 1); // After Payment block.
	} elseif(get_option('cfturnstile_woo_checkout_pos') == "beforebilling") {
		add_action('woocommerce_before_checkout_billing_form', 'cfturnstile_field_checkout', 10);
		add_filter('render_block_woocommerce/checkout-contact-information-block', 'cfturnstile_render_pre_block', 999, 1); // Before Contact Information block.
	} elseif(get_option('cfturnstile_woo_checkout_pos') == "afterbilling") {
		add_action('woocommerce_after_checkout_billing_form', 'cfturnstile_field_checkout', 10);
		add_filter('render_block_woocommerce/checkout-shipping-methods-block', 'cfturnstile_render_pre_block', 999, 1); // Before Shipping Methods block.
	} elseif(get_option('cfturnstile_woo_checkout_pos') == "beforesubmit") {
		add_action('woocommerce_review_order_before_submit', 'cfturnstile_field_checkout', 10);
		add_filter('render_block_woocommerce/checkout-actions-block', 'cfturnstile_render_pre_block', 999, 1); // Before Actions block, not sure if this option is still supported.
	}

	// Check Turnstile
	add_action('woocommerce_checkout_process', 'cfturnstile_woo_checkout_check');
	add_action('woocommerce_after_checkout_validation', 'cfturnstile_woo_checkout_check');
	function cfturnstile_woo_checkout_check() {

		// Prevent duplicate execution within a single request.
		static $cfturnstile_wc_checkout_ran = false;
		if ( $cfturnstile_wc_checkout_ran ) {
			return;
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
				$cfturnstile_wc_checkout_ran = true; // Mark as executed
			}
		}
	}
	add_action('woocommerce_store_api_checkout_update_order_from_request', 'cfturnstile_woo_checkout_block_check', 10, 2);
	function cfturnstile_woo_checkout_block_check($order, $request) {
		// Skip if Turnstile disabled for payment method
		$skip = 0;
		if ( $request->get_method() === 'POST' ) {
			if ( $request->get_param('payment_method') !== null ) {
				$chosen_payment_method = sanitize_text_field( $request->get_param('payment_method') );
				// Retrieve the selected payment methods from the cfturnstile_selected_payment_methods option
			$selected_payment_methods = get_option('cfturnstile_selected_payment_methods', array());
			if(is_array($selected_payment_methods)) {
				// Check if the chosen payment method is in the selected payment methods array
					if ( in_array( $chosen_payment_method, $selected_payment_methods, true ) ) {
						$skip = 1;
					}
				}
			}

			// Additional skip: WooPayments Express or Stripe Express (Apple Pay / Google Pay / Link) on block checkout.
			if ( ! $skip ) {
				$payment_method = $request->get_param( 'payment_method' );
				$payment_data   = $request->get_param( 'payment_data' );
				$express_detected = false;
				if ( is_array( $payment_data ) ) {
					foreach ( $payment_data as $pd_item ) {
						if ( is_array( $pd_item ) && isset( $pd_item['key'] ) ) {
							$key   = $pd_item['key'];
							$value = isset( $pd_item['value'] ) ? $pd_item['value'] : '';
							if ( in_array( $key, array( 'express_payment_type', 'payment_request_type' ), true )
								&& ! empty( $value ) ) {
								$express_detected = true;
								break;
							}
						}
					}
				// Allow customization via filter, defaults to skip when WooPayments or Stripe express is detected.
				$skip_on_express = apply_filters( 'cfturnstile_skip_on_express_pay', ( ($payment_method === 'woocommerce_payments' || $payment_method === 'stripe') && $express_detected ), $payment_method, $payment_data, $request );
				if ( $skip_on_express ) {
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
				$extensions = $request->get_param( 'extensions' );
				if ( empty( $extensions ) ) {
					throw new \Exception( cfturnstile_failed_message() );
				}
				$value = $extensions[ 'simple-cloudflare-turnstile' ];
				if ( empty( $value ) ) {
					throw new \Exception( cfturnstile_failed_message() );
				}
				$token = $value['token'];	
				$check = cfturnstile_check( $token );
				$success = $check['success'];
				if($success != true) {
					throw new \Exception( cfturnstile_failed_message() );
				} else {
					$nonce = wp_create_nonce( 'cfturnstile_checkout_check' );
					$_SESSION['cfturnstile_checkout_checked'] = $nonce;
				}
			}
		}
	}

	add_action('woocommerce_loaded', 'cfturnstile_register_endpoint_data');
	function cfturnstile_register_endpoint_data() {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => 'checkout',
				'namespace'       => 'simple-cloudflare-turnstile',
				'schema_callback' => function() {
					return array(
						'token' => array(
							'description' => __( 'Turnstile token.', 'cfturnstile' ),
							'type'        => 'string',
							'context'     => array()
						),
					);
				},
			)
		);
	}
}
// On payment complete clear session
add_action('woocommerce_checkout_order_processed', 'cfturnstile_woo_checkout_clear', 10, 1);
add_action('woocommerce_store_api_checkout_order_processed', 'cfturnstile_woo_checkout_clear', 10, 1);
add_action('woocommerce_thankyou', 'cfturnstile_woo_checkout_clear', 10, 1);
function cfturnstile_woo_checkout_clear($order_id) {
	if(isset($_SESSION['cfturnstile_checkout_checked'])) { unset($_SESSION['cfturnstile_checkout_checked']); }
}

// Additional clears to prevent lingering validation across session changes
function cfturnstile_woo_clear_session() {
	if (!session_id()) { session_start(); }
	if (isset($_SESSION['cfturnstile_checkout_checked'])) { unset($_SESSION['cfturnstile_checkout_checked']); }
}
// Logout
add_action('wp_logout', 'cfturnstile_woo_clear_session', 10, 0);

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

// WP login check to skip when Woo login is disabled
add_filter( 'cfturnstile_wp_login_checks', 'cfturnstile_woo_skip_wp_login_check', 10, 1 );
function cfturnstile_woo_skip_wp_login_check( $skip ) {
	// If the WooCommerce login integration is disabled but a Woo login form is submitted,
	// skip the global WordPress login Turnstile check.
	if ( ! get_option( 'cfturnstile_woo_login' ) && isset( $_POST['woocommerce-login-nonce'] ) ) {
		return true;
	}
	return $skip;
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

// Check if WooCommerce block checkout page
function cfturnstile_is_block_based_checkout() {
    if ( is_checkout() && !isset($_GET['pay_for_order']) ) {
        $checkout_page_id = wc_get_page_id( 'checkout' );
        if ( $checkout_page_id && has_block( 'woocommerce/checkout', get_post( $checkout_page_id )->post_content ) ) {
            return true;
        }
    }
    return false;
}