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
 *
 * A shared flag rather than a static inside cfturnstile_field_checkout(), so the last-resort
 * renderers below can tell whether the configured widget position ever got a chance to run.
 * "Handled" rather than "output": the guest-only, whitelist and failsafe paths all decide not to
 * render anything, and none of them wants a second attempt afterwards.
 *
 * @param bool $mark Set the flag, rather than only reading it.
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
 * Mirrors what WC_Form_Handler::process_lost_password() itself requires before it calls
 * retrieve_password(), so callers cover every request WooCommerce will act on and no others.
 *
 * Presence of the woocommerce-lost-password-nonce field is deliberately not the test.
 * WooCommerce falls back to _wpnonce for pre-3.3.0 templates, and process_lost_password() runs
 * on wp_loaded for every front-end URL, so a request that simply omits that one field still
 * reaches retrieve_password(). Gating the check on the field being present therefore let the
 * challenge be skipped by dropping it, and the global WordPress check does not cover the gap
 * either, being scoped to wp-login.php. The nonce is verified rather than merely present, for
 * the same reason the login and checkout skips are.
 *
 * The nonce is read from $_REQUEST, not $_POST, because that is where WooCommerce reads it:
 * wc_get_var( $_REQUEST[...] ). Looking only in $_POST left the same bypass open one step
 * further along - moving a valid nonce into the query string still reaches retrieve_password(),
 * while this returned false and the challenge was skipped. Whatever nonce WooCommerce acts on
 * is the nonce this has to see.
 *
 * @return bool
 */
function cfturnstile_is_woo_lost_password_request() {
	if ( ! isset( $_POST['wc_reset_password'], $_POST['user_login'] ) ) {
		return false;
	}

	// Same source and order of preference as wc_get_var() in process_lost_password().
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
 * Whether this is a checkout endpoint that must not get the checkout widget.
 *
 * Pay for order has its own widget and check, behind the cfturnstile_woo_checkout_pay option, and
 * order received is a receipt with nothing left to verify.
 *
 * @return bool
 */
function cfturnstile_is_checkout_endpoint_page() {
	if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
		return false;
	}
	return ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) );
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
		// Buffer the widget so the "after payment" spacer is only emitted alongside something to
		// space. cfturnstile_field_show() renders nothing at all for a whitelisted visitor or
		// behind the cfturnstile_widget_disable filter, and a lone <br/> injected into the block
		// checkout is a visible gap with no widget under it.
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
	// The block itself has to be echoed back out with the widget. Capturing only the widget and
	// returning that replaces the block, which on the "After Payment" position dropped the whole
	// payment block - payment methods and all - out of the checkout.
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
 * The checkout widget markup, for the callers that need it as a string rather than echoed.
 *
 * @return string Empty when the widget has already been handled on this request.
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
	/*
	 * Never skip once an order is genuinely being placed.
	 *
	 * wc-ajax=checkout is only one of the ways WC_Checkout::process_checkout() is reached.
	 * WC_Form_Handler::checkout_action() runs it on wp_loaded for every front-end URL whenever
	 * woocommerce_checkout_place_order is posted, and WC_AJAX::checkout() runs it on
	 * admin-ajax.php. Neither looks at wc-ajax, so without this a request could carry any other
	 * wc-ajax value purely to exempt itself from the check and place the order unverified.
	 *
	 * woocommerce_before_checkout_process is fired from exactly one place - process_checkout(),
	 * immediately before woocommerce_checkout_process - so it is a reliable signal that this
	 * really is a checkout, while a gateway's own pre-validation AJAX call never sets it.
	 */
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
 * Check whether a payment gateway has halted this checkout request for its own two-phase flow.
 *
 * Some gateways run a two-stage checkout: they let WooCommerce validate the order, abort the
 * request with a marker error notice, perform tokenisation and 3DS in the browser, then resubmit
 * the very same form. Both stages post to wc-ajax=checkout, so they are not caught by
 * cfturnstile_is_non_checkout_ajax(). The Turnstile token is unchanged on the resubmission, and
 * Turnstile tokens are single-use, so the verification flag has to survive the first stage.
 *
 * @return bool True if the current request was halted for a gateway resubmission.
 */
function cfturnstile_woo_checkout_deferred_by_gateway() {
	// Marker notices added by gateways that abort checkout and resubmit the same form.
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

	/*
	 * Last resort: make sure the widget reaches the page even when the configured position could
	 * not render it.
	 *
	 * Every position above hangs off one specific hook or one specific inner block, and there are
	 * ordinary ways for that anchor not to exist. On the block checkout, a checkout page still
	 * holding an older iteration of the block has its inner blocks injected as plain markup at
	 * render time (see WooCommerce's Checkout::render()), so no render_block filter ever fires for
	 * them; a merchant can also remove a block in the editor, or use a customized template. On the
	 * classic checkout, the position hooks live in payment.php and form-billing.php, which themes
	 * replace freely.
	 *
	 * The failure is silent and total: no widget renders, but the checkout check still rejects the
	 * order for a missing token, so the shopper gets an error notice with nothing on screen to act
	 * on and no way through. Failing open instead is not an option - that would turn a broken
	 * template into a way to switch the check off - so render the widget somewhere dependable
	 * instead. Both fallbacks run after every position hook has had its turn, and no-op when one
	 * of them already handled the widget.
	 */
	add_filter( 'render_block_woocommerce/checkout', 'cfturnstile_render_block_checkout_fallback', 9999, 1 );
	function cfturnstile_render_block_checkout_fallback( $block_content ) {
		if ( cfturnstile_checkout_widget_rendered() || ! is_string( $block_content ) || '' === $block_content ) {
			return $block_content;
		}
		// The parent block also renders on the order-pay and order-received endpoints, where
		// WooCommerce hands the checkout shortcode back through it.
		if ( cfturnstile_is_checkout_endpoint_page() ) {
			return $block_content;
		}

		$widget = cfturnstile_get_checkout_field();

		// Nothing to place: whitelisted, guest-only against a logged-in shopper, or the widget is
		// switched off by filter. cfturnstile_field_checkout() emits nothing at all in those
		// cases, spacer included.
		if ( '' === trim( $widget ) ) {
			return $block_content;
		}

		// Directly above the Place order button, as a sibling of the actions block. That is the
		// same shape of insertion the position filters make, which React keeps through hydration.
		if ( preg_match( '/<div[^>]*wp-block-woocommerce-checkout-actions-block[^>]*>/i', $block_content, $match, PREG_OFFSET_CAPTURE ) ) {
			$at = $match[0][1];
			return substr( $block_content, 0, $at ) . $widget . substr( $block_content, $at );
		}

		// No actions block to anchor to, so append after the checkout. Out of the way visually,
		// but the block checkout reads the token from the widget by id and sends it as Store API
		// extension data, so it does not have to sit inside the form to work.
		return $block_content . $widget;
	}

	// Two anchors, because one template hook is not enough to rely on. Both sit inside the checkout
	// form, both run after every position hook has had its turn, and both no-op when one of them
	// already handled the widget. Left alone under CheckoutWC, which renders its own checkout and
	// has its own hook above.
	//
	// woocommerce_checkout_order_review is the hook that renders the order review at all, so it
	// fires wherever the payment section exists - including when the payment section is there but
	// woocommerce_review_order_after_payment is not. That is the ordinary way the "After Payment"
	// position comes up empty with no error to show for it: that hook is the very last line of
	// payment.php, so a theme copy of the template that stops at the closing div, or a checkout
	// plugin that rebuilds the payment area, drops it while leaving every earlier position hook
	// intact. Priority 9999 puts this after woocommerce_checkout_payment (priority 20), so the
	// configured position still wins whenever it did fire, and the widget lands inside
	// #order_review just below the payment section - the region the position itself aims for.
	//
	// woocommerce_checkout_after_order_review is in form-checkout.php, the outer template, and
	// covers the case where #order_review never rendered through the standard hook at all. It sits
	// outside #order_review, so a widget placed there also survives the fragment refreshes that
	// replace the order review.
	if ( ! $cfturnstile_cfw_checkout ) {
		add_action( 'woocommerce_checkout_order_review', 'cfturnstile_field_checkout_fallback', 9999 );
		add_action( 'woocommerce_checkout_after_order_review', 'cfturnstile_field_checkout_fallback', 9999 );
		function cfturnstile_field_checkout_fallback() {
			if ( cfturnstile_checkout_widget_rendered() || cfturnstile_is_checkout_endpoint_page() ) {
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

		// Skip wc-ajax requests that are not placing an order, to preserve the single-use token.
		//
		// In practice this only fires for a third party that runs the validation hooks outside
		// WC_Checkout::process_checkout() - a gateway pre-validating the form on its own wc-ajax
		// endpoint, say. WooCommerce core reaches both of this callback's hooks only from inside
		// process_checkout(), which fires woocommerce_before_checkout_process first, and
		// cfturnstile_is_non_checkout_ajax() refuses to skip once that has happened. A gateway
		// that instead resubmits the same form for 3DS is handled separately, by
		// cfturnstile_woo_checkout_deferred_by_gateway() below.
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

		// No wc-ajax skip here. This hook belongs to the Store API checkout route, and a wc-ajax
		// query var on a REST request means nothing - honouring it would let the check be skipped
		// by appending one to the request URL.
		//
		// The route fires it for more than order placement: when a draft order is already in the
		// session after a failed payment, the PATCH that updates that order fires it too (see
		// Checkout::get_route_update_response()). The POST guard below is what keeps the check to
		// the request that actually places the order, so the single-use token is not spent on a
		// form interaction that never reaches payment.

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
				}
			}
		}
	}

	// Clear checkout verification transients after all validation hooks have run
	add_action('woocommerce_after_checkout_validation', 'cfturnstile_woo_checkout_clear_transient', 9999);
	function cfturnstile_woo_checkout_clear_transient() {
		$deadline_key = cfturnstile_transient_key( 'cfturnstile_checkout_deferred_until' );

		// A gateway may have halted this request to run 3DS before resubmitting the same form with
		// the same token, so keep the pass alive for the resubmission. Checking verified first
		// matters: the marker is added even when the challenge failed, so extending an existing
		// pass is safe, but granting one here would be a bypass.
		if ( cfturnstile_get_verified( 'cfturnstile_checkout_checked' ) && cfturnstile_woo_checkout_deferred_by_gateway() ) {
			// 3DS can keep the customer busy well past the 120 seconds a single request needs.
			$expire = (int) apply_filters( 'cfturnstile_woo_deferred_checkout_expiry', 900 );
			if ( $expire > 0 && $deadline_key ) {
				// Fixed on the first deferral, so repeated markers cannot extend the pass forever.
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

		// Either the gateway resubmitted and the flow is over, or the deadline has passed.
		if ( $deadline_key ) {
			delete_transient( $deadline_key );
		}
		cfturnstile_clear_verified( 'cfturnstile_checkout_checked' );
	}

	// Block checkout: clear the transient after the order is processed
	add_action('woocommerce_store_api_checkout_order_processed', 'cfturnstile_woo_block_checkout_clear_transient', 9999);
	function cfturnstile_woo_block_checkout_clear_transient() {
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
	 * Send the WooCommerce app authorization login (/wc-auth/v1/login/) to wp-login.php.
	 *
	 * That endpoint posts a genuine woocommerce-login nonce and triggers wp_signon(), so the
	 * login Turnstile check correctly runs against it. But its template (auth/form-login.php)
	 * fires none of the hooks the widget renders on, and the page outputs no wp_head() or
	 * wp_footer(), so the Turnstile script never loads and the challenge can never be solved -
	 * leaving the merchant unable to authorize an app.
	 *
	 * Exempting the endpoint from the check is not an option: WC_Form_Handler::process_login()
	 * runs on wp_loaded for every front-end URL, so a path-based exemption could be used to
	 * bypass Turnstile by posting credentials to that path. Redirect to wp-login.php instead,
	 * where the widget renders; WooCommerce resumes the authorization flow on the way back.
	 */
	add_action( 'woocommerce_auth_page_header', 'cfturnstile_woo_auth_login_redirect', 1 );
	function cfturnstile_woo_auth_login_redirect() {
		// Only the logged-out login screen. The grant access screen renders when logged in,
		// and WooCommerce sends the user there itself once wp-login.php returns them.
		if ( is_user_logged_in() ) {
			return;
		}
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		// Relative path only - never trust the Host header to build the return URL.
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