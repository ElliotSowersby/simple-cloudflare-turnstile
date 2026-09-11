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

// Get turnstile field: Woo Account Details
function cfturnstile_field_woo_account() {
	$unique_id = wp_rand();
	cfturnstile_field_show('.woocommerce-EditAccountForm button[name=save_account_details], .woocommerce-EditAccountForm input[name=save_account_details]', 'turnstileWooAccountCallback', 'woocommerce-account-' . $unique_id, '-woo-account-' . $unique_id, 'sct-woocommerce-account');
}

/**
 * Whether the checkout widget has already been handled on this request.
 * Shared so the fallback renderers can tell if the configured position ran.
 *
 * @param bool $mark Set the flag.
 * @return bool
 */
function cfturnstile_checkout_widget_rendered( $mark = false ) {
	static $rendered = false;
	if ( $mark ) {
		$rendered = true;
	}
	return $rendered;
}

/**
 * Whether this request is a WooCommerce lost password submission.
 *
 * Mirrors WC_Form_Handler::process_lost_password(): it runs on every front-end URL, accepts
 * _wpnonce as a fallback, and reads from $_REQUEST. Testing for the nonce field's presence
 * alone was bypassable by omitting it, and the nonce must be verified, not just present.
 *
 * @return bool
 */
function cfturnstile_is_woo_lost_password_request() {
	if ( ! isset( $_POST['wc_reset_password'], $_POST['user_login'] ) ) {
		return false;
	}

	if ( isset( $_REQUEST['woocommerce-lost-password-nonce'] ) ) {
		$nonce = $_REQUEST['woocommerce-lost-password-nonce'];
	} elseif ( isset( $_REQUEST['_wpnonce'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
	} else {
		return false;
	}

	return (bool) wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'lost_password' );
}

/**
 * Whether this is the order-pay or order-received endpoint, which must not get the checkout widget.
 *
 * @return bool
 */
function cfturnstile_is_checkout_endpoint_page() {
	if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
		return false;
	}
	return ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) );
}

/**
 * Whether the checkout template is being rendered as a throwaway fragment, not the submitted form.
 *
 * Divi's Checkout modules each render the whole checkout template. All but Payment Info swap in a
 * stripped form-checkout.php (a bare <form> WooCommerce never submits) that still fires the
 * order review hooks. Each module attaches its swap_template filter only for its own render, so
 * that filter's presence identifies a partial render. Payment Info renders the real form.
 *
 * @return bool
 */
function cfturnstile_is_partial_checkout_render() {
	$partial = false;

	$divi_partial_modules = array(
		'ET_Builder_Module_Woocommerce_Checkout_Billing',
		'ET_Builder_Module_Woocommerce_Checkout_Shipping',
		'ET_Builder_Module_Woocommerce_Checkout_Additional_Info',
		'ET_Builder_Module_Woocommerce_Checkout_Order_Details',
	);
	foreach ( $divi_partial_modules as $module ) {
		if ( false !== has_filter( 'wc_get_template', array( $module, 'swap_template' ) ) ) {
			$partial = true;
			break;
		}
	}

	return (bool) apply_filters( 'cfturnstile_is_partial_checkout_render', $partial );
}

// Get turnstile field: Woo Checkout
function cfturnstile_field_checkout() {
	if(is_wc_endpoint_url('order-received')) {
		return;
	}

	if ( cfturnstile_checkout_widget_rendered() ) {
		return;
	}
	cfturnstile_checkout_widget_rendered( true );

	$guest_only = esc_attr( get_option('cfturnstile_guest_only') );
	if( !$guest_only || ($guest_only && !is_user_logged_in()) ) {
		// Buffer so the "after payment" spacer is only output when there is a widget to space.
		ob_start();
		cfturnstile_field_show('', '', 'woocommerce-checkout', '-woo-checkout');
		$field = ob_get_clean();

		if ( '' === trim( $field ) ) {
			return;
		}

		if(get_option('cfturnstile_woo_checkout_pos') == "afterpay") {
			echo "<br/>";
		}
		echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup built and escaped by cfturnstile_field_show().
	}
}

// Render after checkout block
function cfturnstile_render_post_block($block_content) {
	if ( cfturnstile_is_checkout_endpoint_page() ) {
		return $block_content;
	}
	// Keep the block content; returning only the widget removed the payment block.
	return $block_content . cfturnstile_get_checkout_field();
}

// Render before checkout block
function cfturnstile_render_pre_block($block_content) {
	if ( cfturnstile_is_checkout_endpoint_page() ) {
		return $block_content;
	}
	return cfturnstile_get_checkout_field() . $block_content;
}

/**
 * The checkout widget markup as a string. Empty once already handled on this request.
 *
 * @return string
 */
function cfturnstile_get_checkout_field() {
	ob_start();
	cfturnstile_field_checkout();
	return ob_get_clean();
}

/**
 * Check if the current request is a non-checkout WooCommerce AJAX call.
 *
 * @return bool True if the request is a wc-ajax call that is not placing an order.
 */
function cfturnstile_is_non_checkout_ajax() {
	// Never skip once an order is being placed. process_checkout() is reachable without
	// wc-ajax=checkout, so a stray wc-ajax value must not exempt a real order from the check.
	if ( did_action( 'woocommerce_before_checkout_process' ) ) {
		return false;
	}

	$wc_ajax = isset( $_GET['wc-ajax'] ) ? sanitize_text_field( wp_unslash( $_GET['wc-ajax'] ) ) : '';
	if ( $wc_ajax && $wc_ajax !== 'checkout' ) {
		return true;
	}
	return false;
}

/**
 * Whether a gateway halted this checkout to run 3DS before resubmitting the same form.
 * The resubmission carries the same single-use token, so the verified flag must survive.
 *
 * @return bool
 */
function cfturnstile_woo_checkout_deferred_by_gateway() {
	$markers = apply_filters( 'cfturnstile_woo_deferred_checkout_markers', array(
		'globalpayments_gpapi_checkout_validated', // GlobalPayments GPAPI, 3DS enabled.
	) );

	if ( ! is_array( $markers ) || empty( $markers ) || ! function_exists( 'wc_get_notices' ) || ! function_exists( 'WC' ) || ! WC()->session ) {
		return false;
	}

	$notices = wc_get_notices( 'error' );
	if ( empty( $notices ) ) {
		return false;
	}

	foreach ( $notices as $notice ) {
		// WooCommerce 3.9+ stores notices as arrays, older versions as plain strings.
		$message = ( is_array( $notice ) && isset( $notice['notice'] ) ) ? $notice['notice'] : $notice;
		if ( ! is_string( $message ) ) {
			continue;
		}
		foreach ( $markers as $marker ) {
			if ( $marker && false !== strpos( $message, $marker ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Whether a checkout verification flag has already been disposed of on this request.
 *
 * Lets the shutdown fallback tell whether the normal clear hook ran, so it never undoes a
 * deliberate extension.
 *
 * @param string $key  Verification key, e.g. 'cfturnstile_checkout_checked'.
 * @param bool   $mark Set the flag.
 * @return bool
 */
function cfturnstile_checkout_clear_handled( $key, $mark = false ) {
	static $handled = array();
	if ( $mark ) {
		$handled[ $key ] = true;
	}
	return ! empty( $handled[ $key ] );
}

/**
 * Last-resort clear for the classic checkout pass.
 *
 * The pass is normally cleared on woocommerce_after_checkout_validation. A request that dies
 * before that hook left the flag set for the rest of its TTL, and the flag is what lets a token
 * skip re-verification, so the same token stayed replayable until it expired.
 *
 * Only registered by the request that set the flag, so a 3DS resubmission carrying an
 * already-verified token cannot lose its extended pass here.
 */
function cfturnstile_woo_checkout_shutdown_clear() {
	if ( cfturnstile_checkout_clear_handled( 'cfturnstile_checkout_checked' ) ) {
		return;
	}
	cfturnstile_clear_verified( 'cfturnstile_checkout_checked' );
}

/**
 * Last-resort clear for the block checkout pass. Same reasoning as the classic one above.
 */
function cfturnstile_woo_block_checkout_shutdown_clear() {
	if ( cfturnstile_checkout_clear_handled( 'cfturnstile_block_checkout_checked' ) ) {
		return;
	}
	global $cfturnstile_block_checkout_token;
	if ( ! empty( $cfturnstile_block_checkout_token ) ) {
		cfturnstile_clear_verified( 'cfturnstile_block_checkout_checked', $cfturnstile_block_checkout_token );
	}
}

// Woo Checkout Check
if(get_option('cfturnstile_woo_checkout')) {
	// WooCommerce Checkout
	// CheckoutWC: Only hook when CheckoutWC templates are enabled
	$cfturnstile_cfw_checkout = ( function_exists( 'cfw_templates_disabled' ) && ! cfw_templates_disabled() );
	if($cfturnstile_cfw_checkout) {
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

	// Fallback: if the configured position's hook or block never rendered (older block markup,
	// removed block, custom theme template), place the widget anyway. Otherwise the order is
	// rejected for a missing token with no widget on screen. Both fallbacks run after every
	// position hook and no-op once the widget has been handled.
	add_filter( 'render_block_woocommerce/checkout', 'cfturnstile_render_block_checkout_fallback', 9999, 1 );
	function cfturnstile_render_block_checkout_fallback( $block_content ) {
		if ( cfturnstile_checkout_widget_rendered() || ! is_string( $block_content ) || '' === $block_content ) {
			return $block_content;
		}
		if ( cfturnstile_is_checkout_endpoint_page() ) {
			return $block_content;
		}

		$widget = cfturnstile_get_checkout_field();
		if ( '' === trim( $widget ) ) {
			return $block_content;
		}

		// Above the Place Order button, as a sibling of the actions block.
		if ( preg_match( '/<div[^>]*wp-block-woocommerce-checkout-actions-block[^>]*>/i', $block_content, $match, PREG_OFFSET_CAPTURE ) ) {
			$at = $match[0][1];
			return substr( $block_content, 0, $at ) . $widget . substr( $block_content, $at );
		}

		// The configured block is missing from the saved checkout markup. WooCommerce still shows it:
		// every inner block with lock.default.remove renders client-side when absent, appended after
		// the fields block's saved children. So the last slot inside the fields block is directly
		// above those, which is where every remaining position wants the widget anyway.
		$fields = cfturnstile_block_checkout_fields_end( $block_content );
		if ( false !== $fields ) {
			return substr( $block_content, 0, $fields ) . $widget . substr( $block_content, $fields );
		}

		// No fields block either: append after the checkout. The block checkout reads the token by id.
		return $block_content . $widget;
	}

	/**
	 * Offset of the closing tag of the checkout fields block, so the widget can be placed as its
	 * last child. Appending after the whole checkout block instead drops the widget outside the
	 * block's React root, where the theme lays it out on its own away from the form.
	 *
	 * @param string $block_content Rendered woocommerce/checkout block.
	 * @return int|false Offset of the fields block's closing </div>, or false if not found.
	 */
	function cfturnstile_block_checkout_fields_end( $block_content ) {
		if ( ! preg_match( '/<div[^>]*wp-block-woocommerce-checkout-fields-block[^>]*>/i', $block_content, $match, PREG_OFFSET_CAPTURE ) ) {
			return false;
		}

		// Walk from the opening tag to its matching close, so nested blocks are skipped.
		$offset = $match[0][1] + strlen( $match[0][0] );
		$depth  = 1;
		while ( $depth > 0 && preg_match( '/<(\/?)div\b[^>]*>/i', $block_content, $tag, PREG_OFFSET_CAPTURE, $offset ) ) {
			$depth += ( '/' === $tag[1][0] ) ? -1 : 1;
			$offset = $tag[0][1] + strlen( $tag[0][0] );
			if ( 0 === $depth ) {
				return $tag[0][1];
			}
		}

		return false;
	}

	// Classic checkout fallbacks. Priority 9999 runs after woocommerce_checkout_payment (20), so
	// the configured position wins when it fired. Skipped during partial renders of the template
	// (see cfturnstile_is_partial_checkout_render()): rendering there put the widget in a form that
	// is never submitted and spent the flag, so the real form got nothing and every order failed.
	if ( ! $cfturnstile_cfw_checkout ) {
		add_action( 'woocommerce_checkout_order_review', 'cfturnstile_field_checkout_fallback', 9999 );
		add_action( 'woocommerce_checkout_after_order_review', 'cfturnstile_field_checkout_fallback', 9999 );
		function cfturnstile_field_checkout_fallback() {
			if ( cfturnstile_checkout_widget_rendered() || cfturnstile_is_checkout_endpoint_page() ) {
				return;
			}
			if ( cfturnstile_is_partial_checkout_render() ) {
				return;
			}
			cfturnstile_field_checkout();
		}
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

		// Skip gateway pre-validation wc-ajax calls that are not placing an order, to preserve the token.
		if ( cfturnstile_is_non_checkout_ajax() ) {
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

		// Check if guest only enabled
		$guest = esc_attr( get_option('cfturnstile_guest_only') );
		// Check — always require a fresh Turnstile token (tokens are single-use).
		if( !$skip && (!$guest || ( $guest && !is_user_logged_in() )) ) {
			// If this token already passed verification, skip re-check.
			if ( cfturnstile_get_verified( 'cfturnstile_checkout_checked' ) ) {
				$cfturnstile_wc_checkout_ran = true;
				return;
			}
			$check = cfturnstile_check();
			$success = $check['success'];
			if($success != true) {
				wc_add_notice( cfturnstile_failed_message(), 'error');
			} else {
				cfturnstile_set_verified( 'cfturnstile_checkout_checked', '', 120 );
				add_action( 'shutdown', 'cfturnstile_woo_checkout_shutdown_clear' );
			}
			$cfturnstile_wc_checkout_ran = true;
		}
	}
	add_action('woocommerce_store_api_checkout_update_order_from_request', 'cfturnstile_woo_checkout_block_check', 10, 2);
	function cfturnstile_woo_checkout_block_check($order, $request) {
		// Prevent duplicate execution within a single request.
		static $cfturnstile_wc_block_checkout_ran = false;
		if ( $cfturnstile_wc_block_checkout_ran ) {
			return;
		}

		// No wc-ajax skip on this REST route: honouring it would be a bypass. The POST guard limits
		// the check to the request that places the order, not the PATCH that updates a draft.

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

			// Check if guest only enabled
			$guest = esc_attr( get_option('cfturnstile_guest_only') );
			// Check — always require a fresh Turnstile token (tokens are single-use).
			if( !$skip && (!$guest || ( $guest && !is_user_logged_in() )) ) {
				$extensions = $request->get_param( 'extensions' );
				$token = ( is_array( $extensions ) && isset( $extensions['simple-cloudflare-turnstile']['token'] ) ) ? $extensions['simple-cloudflare-turnstile']['token'] : '';

				if ( empty( $token ) ) {
					throw new \Exception( cfturnstile_failed_message() );
				}

				// Store token so the cleanup callback can access it.
				global $cfturnstile_block_checkout_token;
				$cfturnstile_block_checkout_token = $token;

				// If this token already passed verification, skip re-check.
				if ( cfturnstile_get_verified( 'cfturnstile_block_checkout_checked', $token ) ) {
					$cfturnstile_wc_block_checkout_ran = true;
					return;
				}
				
				$check = cfturnstile_check( $token );
				$success = $check['success'];
				$cfturnstile_wc_block_checkout_ran = true;
				if($success != true) {
					throw new \Exception( cfturnstile_failed_message() );
				} else {
					cfturnstile_set_verified( 'cfturnstile_block_checkout_checked', $token, 120 );
					add_action( 'shutdown', 'cfturnstile_woo_block_checkout_shutdown_clear' );
				}
			}
		}
	}

	// Clear checkout verification transients after all validation hooks have run
	add_action('woocommerce_after_checkout_validation', 'cfturnstile_woo_checkout_clear_transient', 9999);
	function cfturnstile_woo_checkout_clear_transient() {
		cfturnstile_checkout_clear_handled( 'cfturnstile_checkout_checked', true );

		$deadline_key = cfturnstile_transient_key( 'cfturnstile_checkout_deferred_until' );

		// Keep an existing pass alive while a gateway runs 3DS and resubmits the same form. Only
		// extend a pass, never grant one: the marker is added even when the challenge failed.
		if ( cfturnstile_get_verified( 'cfturnstile_checkout_checked' ) && cfturnstile_woo_checkout_deferred_by_gateway() ) {
			$expire = (int) apply_filters( 'cfturnstile_woo_deferred_checkout_expiry', 900 );
			if ( $expire > 0 && $deadline_key ) {
				// Fixed on the first deferral so repeated markers cannot extend it forever.
				$deadline = (int) get_transient( $deadline_key );
				if ( ! $deadline ) {
					$deadline = time() + $expire;
					set_transient( $deadline_key, $deadline, $expire );
				}
				$remaining = $deadline - time();
				if ( $remaining > 0 ) {
					cfturnstile_set_verified( 'cfturnstile_checkout_checked', '', $remaining );
					return;
				}
			}
		}

		if ( $deadline_key ) {
			delete_transient( $deadline_key );
		}
		cfturnstile_clear_verified( 'cfturnstile_checkout_checked' );
	}

	// Block checkout: clear the transient after the order is processed
	add_action('woocommerce_store_api_checkout_order_processed', 'cfturnstile_woo_block_checkout_clear_transient', 9999);
	function cfturnstile_woo_block_checkout_clear_transient() {
		cfturnstile_checkout_clear_handled( 'cfturnstile_block_checkout_checked', true );

		global $cfturnstile_block_checkout_token;
		if ( ! empty( $cfturnstile_block_checkout_token ) ) {
			cfturnstile_clear_verified( 'cfturnstile_block_checkout_checked', $cfturnstile_block_checkout_token );
		}
	}

	add_action('woocommerce_loaded', 'cfturnstile_register_endpoint_data', 20);
	function cfturnstile_register_endpoint_data() {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => 'checkout',
				'namespace'       => 'simple-cloudflare-turnstile',
				'schema_callback' => function() {
					return array(
						'token' => array(
							'description'       => __( 'Turnstile token.', 'simple-cloudflare-turnstile' ),
							'type'              => 'string',
							'context'           => array( 'view', 'edit' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					);
				},
			)
		);
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
	add_action('woocommerce_login_form','cfturnstile_field_woo_login');

	/**
	 * Send the app authorization login (/wc-auth/v1/login/) to wp-login.php.
	 *
	 * Its template has no wp_head/wp_footer or widget hooks, so the challenge can never be solved
	 * there. Exempting the path would be a bypass, since process_login() runs on every URL.
	 */
	add_action( 'woocommerce_auth_page_header', 'cfturnstile_woo_auth_login_redirect', 1 );
	function cfturnstile_woo_auth_login_redirect() {
		if ( is_user_logged_in() ) {
			return;
		}
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		// Relative path only; never trust the Host header.
		$redirect = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		wp_safe_redirect( wp_login_url( $redirect ) );
		exit;
	}
	if(!get_option('cfturnstile_login')) {
		add_action('authenticate', 'cfturnstile_woo_login_check', 21, 1);
		function cfturnstile_woo_login_check($user) {

			// Check skip
			if(!isset($user->ID)) { return $user; }
			if(!isset($_POST['woocommerce-login-nonce'])) { return $user; } // Skip if not WooCommerce login
			if(defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST) { return $user; } // Skip XMLRPC
			if(defined( 'REST_REQUEST' ) && REST_REQUEST) { return $user; } // Skip REST API
			if(is_wp_error($user) && isset($user->errors['empty_username']) && isset($user->errors['empty_password']) ) {return $user; } // Skip Errors

			// Check if already validated (cache-friendly, no PHP session)
			if( cfturnstile_get_verified( 'cfturnstile_login_checked_' . $user->ID ) ) {
				return $user;
			}

			// Check Turnstile
			$check = cfturnstile_check();
			$success = $check['success'];
			if($success != true) {
				$user = new WP_Error( 'cfturnstile_error', cfturnstile_failed_message() );
			} else {
				cfturnstile_set_verified( 'cfturnstile_login_checked_' . $user->ID, '', 300 );
			}
			
			return $user;
			
		}
	}
}

// WP login check to skip when Woo login is disabled
add_filter( 'cfturnstile_wp_login_checks', 'cfturnstile_woo_skip_wp_login_check', 10, 1 );
function cfturnstile_woo_skip_wp_login_check( $skip ) {
	// If the WooCommerce login integration is disabled but a Woo login form is submitted,
	// skip the global WordPress login Turnstile check.
	if (
		! get_option( 'cfturnstile_woo_login' )
		&& isset( $_POST['login'], $_POST['username'], $_POST['password'] )
		&& isset( $_POST['woocommerce-login-nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-login-nonce'] ) ), 'woocommerce-login' )
	) {
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
		if(function_exists('is_checkout') && is_checkout()) { return; } // Skip if on checkout page, to avoid conflicts with the checkout integration
		$check = cfturnstile_check();
		$success = isset( $check['success'] ) ? $check['success'] : false;
		if($success != true) {
			$validation_errors->add( 'cfturnstile_error', cfturnstile_failed_message() );
		}
	}
}

// Woo Reset Check
if(get_option('cfturnstile_woo_reset')) {
	add_action('woocommerce_lostpassword_form','cfturnstile_field_woo_reset');
	add_action('lostpassword_post','cfturnstile_woo_reset_check', 10, 1);
	function cfturnstile_woo_reset_check($validation_errors) {
		if ( ! cfturnstile_is_woo_lost_password_request() ) {
			return;
		}

		$check = cfturnstile_check();
		$success = isset( $check['success'] ) ? $check['success'] : false;
		if($success != true) {
			$validation_errors->add( 'cfturnstile_error', cfturnstile_failed_message() );
		}
	}
}

// Woo Account Details Check
if(get_option('cfturnstile_woo_account')) {
	add_action('woocommerce_edit_account_form','cfturnstile_field_woo_account');
	add_action('woocommerce_save_account_details_errors', 'cfturnstile_woo_account_check', 10, 1);
	function cfturnstile_woo_account_check($validation_errors) {
		if ( ! is_wp_error( $validation_errors ) ) {
			return;
		}

		if ( empty( $_POST['save-account-details-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['save-account-details-nonce'] ) ), 'save_account_details' ) ) {
			return;
		}

		$check = cfturnstile_check();
		$success = isset( $check['success'] ) ? $check['success'] : false;
		if($success != true) {
			$validation_errors->add( 'cfturnstile_error', cfturnstile_failed_message() );
		}
	}
}

// Check if WooCommerce block checkout page
function cfturnstile_is_block_based_checkout() {
    if ( function_exists('is_checkout') && is_checkout() && !isset($_GET['pay_for_order']) ) {
        $checkout_page_id = wc_get_page_id( 'checkout' );
        if ( $checkout_page_id && has_block( 'woocommerce/checkout', get_post( $checkout_page_id )->post_content ) ) {
            return true;
        }
    }
    return false;
}