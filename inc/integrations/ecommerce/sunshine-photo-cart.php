<?php
/**
 * Sunshine Photo Cart Integration
 *
 * @package SimpleCloudflareTurnstile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display Turnstile field on Sunshine Photo Cart login form.
 */
function cfturnstile_field_sunshine_login() {
	cfturnstile_field_show( '', '', 'sunshine-login', '-sunshine-login', 'sct-sunshine-login' );
}

/**
 * Display Turnstile field on Sunshine Photo Cart registration form.
 */
function cfturnstile_field_sunshine_register() {
	cfturnstile_field_show( '', '', 'sunshine-register', '-sunshine-register', 'sct-sunshine-register' );
}

/**
 * Display Turnstile field on Sunshine Photo Cart lost password form.
 */
function cfturnstile_field_sunshine_reset() {
	cfturnstile_field_show( '', '', 'sunshine-reset', '-sunshine-reset', 'sct-sunshine-reset' );
}

/**
 * Display Turnstile field on Sunshine Photo Cart checkout form.
 */
function cfturnstile_field_sunshine_checkout() {
	$guest_only = esc_attr( get_option( 'cfturnstile_sunshine_guest_only' ) );
	if ( ! $guest_only || ( $guest_only && ! is_user_logged_in() ) ) {
		cfturnstile_field_show( '#sunshine--checkout--submit', 'turnstileSunshineCheckoutCallback', 'sunshine-checkout', '-sunshine-checkout', 'sct-sunshine-checkout' );
	}
}

/**
 * Enqueue Turnstile API script for AJAX-loaded modal forms.
 * Sunshine Photo Cart loads login/signup/reset forms via AJAX into modals,
 * so we need to ensure the Turnstile API is available before the modal loads.
 */
function cfturnstile_sunshine_enqueue_scripts() {
	// Only enqueue if at least one form integration is enabled.
	$login    = get_option( 'cfturnstile_sunshine_login' );
	$register = get_option( 'cfturnstile_sunshine_register' );
	$reset    = get_option( 'cfturnstile_sunshine_reset' );

	if ( ! $login && ! $register && ! $reset ) {
		return;
	}

	// Only load on Sunshine pages where modals might appear.
	if ( function_exists( 'is_sunshine' ) && ! is_sunshine() ) {
		return;
	}

	// Enqueue the Turnstile API with explicit render mode for manual control.
	if ( ! wp_script_is( 'cfturnstile', 'enqueued' ) ) {
		$defer = get_option( 'cfturnstile_defer_scripts' ) ? array( 'strategy' => 'defer' ) : array();
		wp_enqueue_script( 'cfturnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit', array(), null, $defer );
	}
}
add_action( 'wp_enqueue_scripts', 'cfturnstile_sunshine_enqueue_scripts', 10 );

/**
 * Add JavaScript to handle AJAX-loaded modal forms.
 * Sunshine Photo Cart loads login/signup/reset forms via AJAX into modals.
 */
function cfturnstile_sunshine_modal_scripts() {
	// Only add if at least one form integration is enabled.
	$login    = get_option( 'cfturnstile_sunshine_login' );
	$register = get_option( 'cfturnstile_sunshine_register' );
	$reset    = get_option( 'cfturnstile_sunshine_reset' );

	if ( ! $login && ! $register && ! $reset ) {
		return;
	}

	// Only load on Sunshine pages where modals might appear.
	if ( function_exists( 'is_sunshine' ) && ! is_sunshine() ) {
		return;
	}

	// Get the site key for explicit rendering.
	$key = esc_attr( get_option( 'cfturnstile_key' ) );
	if ( empty( $key ) ) {
		return;
	}
	?>
	<script type="text/javascript">
	(function($) {
		// Track which containers we've already rendered to prevent duplicates
		var sctSunshineRendered = {};

		// Function to render Turnstile widgets in Sunshine modals
		function sctSunshineRenderTurnstile() {
			if (typeof turnstile === 'undefined') {
				return;
			}

			// Find all Turnstile containers in the modal
			$('#sunshine--modal .cf-turnstile').each(function() {
				var $el = $(this);
				var elId = $el.attr('id');

				// Skip if no ID, already rendered, or already has content
				if (!elId || sctSunshineRendered[elId] || $el.find('iframe').length > 0 || $el.children().length > 0) {
					return;
				}

				// Mark as rendered before calling to prevent race conditions
				sctSunshineRendered[elId] = true;

				try {
					turnstile.render('#' + elId, {
						sitekey: '<?php echo esc_js( $key ); ?>'
					});
				} catch (e) {
					// Reset if render failed
					sctSunshineRendered[elId] = false;
				}
			});
		}

		// Listen for Sunshine modal events
		$(document).on('require_login login signup', function() {
			setTimeout(sctSunshineRenderTurnstile, 200);
		});

		// Also listen for ajaxComplete as a fallback
		$(document).ajaxComplete(function(event, xhr, settings) {
			if (settings.data && typeof settings.data === 'string' && settings.data.indexOf('sunshine_modal_display') !== -1) {
				setTimeout(sctSunshineRenderTurnstile, 200);
			}
		});

		// Clear tracking when modal closes
		$(document).on('click', '#sunshine--modal--overlay, #sunshine--modal--close', function() {
			sctSunshineRendered = {};
		});
	})(jQuery);
	</script>
	<?php
}
add_action( 'wp_footer', 'cfturnstile_sunshine_modal_scripts', 999 );

/**
 * Sunshine Photo Cart Login - Display hook.
 */
if ( get_option( 'cfturnstile_sunshine_login' ) ) {
	add_action( 'sunshine_login_form_before_submit', 'cfturnstile_field_sunshine_login' );

	// Validation filter - returns error message if validation fails.
	add_filter( 'sunshine_login_validation', 'cfturnstile_sunshine_login_check', 10, 2 );

	/**
	 * Validate Turnstile on Sunshine login form submission.
	 *
	 * @param string $error     Existing error message.
	 * @param array  $post_data Form POST data.
	 * @return string Error message if validation fails, empty string if success.
	 */
	function cfturnstile_sunshine_login_check( $error, $post_data ) {
		// Skip if already has an error.
		if ( ! empty( $error ) ) {
			return $error;
		}

		// Skip if whitelisted.
		if ( cfturnstile_whitelisted() ) {
			return $error;
		}

		$check = cfturnstile_check();
		if ( true !== $check['success'] ) {
			return cfturnstile_failed_message();
		}

		return $error;
	}
}

/**
 * Sunshine Photo Cart Registration - Display hook.
 */
if ( get_option( 'cfturnstile_sunshine_register' ) ) {
	add_action( 'sunshine_signup_form_before_submit', 'cfturnstile_field_sunshine_register' );

	// Validation filter - returns error message if validation fails.
	add_filter( 'sunshine_signup_validation', 'cfturnstile_sunshine_register_check', 10, 2 );

	/**
	 * Validate Turnstile on Sunshine registration form submission.
	 *
	 * @param string $error     Existing error message.
	 * @param array  $post_data Form POST data.
	 * @return string Error message if validation fails, empty string if success.
	 */
	function cfturnstile_sunshine_register_check( $error, $post_data ) {
		// Skip if already has an error.
		if ( ! empty( $error ) ) {
			return $error;
		}

		// Skip if whitelisted.
		if ( cfturnstile_whitelisted() ) {
			return $error;
		}

		$check = cfturnstile_check();
		if ( true !== $check['success'] ) {
			return cfturnstile_failed_message();
		}

		return $error;
	}
}

/**
 * Sunshine Photo Cart Lost Password - Display hook.
 */
if ( get_option( 'cfturnstile_sunshine_reset' ) ) {
	add_action( 'sunshine_lost_password_form_before_submit', 'cfturnstile_field_sunshine_reset' );

	// Validation filter - returns error message if validation fails.
	add_filter( 'sunshine_lost_password_validation', 'cfturnstile_sunshine_reset_check', 10, 2 );

	/**
	 * Validate Turnstile on Sunshine lost password form submission.
	 *
	 * @param string $error     Existing error message.
	 * @param array  $post_data Form POST data.
	 * @return string Error message if validation fails, empty string if success.
	 */
	function cfturnstile_sunshine_reset_check( $error, $post_data ) {
		// Skip if already has an error.
		if ( ! empty( $error ) ) {
			return $error;
		}

		// Skip if whitelisted.
		if ( cfturnstile_whitelisted() ) {
			return $error;
		}

		$check = cfturnstile_check();
		if ( true !== $check['success'] ) {
			return cfturnstile_failed_message();
		}

		return $error;
	}
}

/**
 * Sunshine Photo Cart Checkout - Display and validation hooks.
 */
if ( get_option( 'cfturnstile_sunshine_checkout' ) ) {
	add_action( 'sunshine_checkout_after_payment', 'cfturnstile_field_sunshine_checkout' );

	// Validation action - adds error to cart if validation fails.
	add_action( 'sunshine_checkout_validation', 'cfturnstile_sunshine_checkout_check', 10, 2 );

	/**
	 * Validate Turnstile on Sunshine checkout form submission.
	 *
	 * @param string $section Active checkout section.
	 * @param array  $data    Form POST data.
	 */
	function cfturnstile_sunshine_checkout_check( $section, $data ) {
		// Only validate on the payment section (final step).
		if ( 'payment' !== $section ) {
			return;
		}

		// Skip if whitelisted.
		if ( cfturnstile_whitelisted() ) {
			return;
		}

		// Check if guest only is enabled.
		$guest_only = esc_attr( get_option( 'cfturnstile_sunshine_guest_only' ) );
		if ( $guest_only && is_user_logged_in() ) {
			return;
		}

		// Start session to prevent duplicate checks.
		if ( ! session_id() ) {
			session_start();
		}

		// Check if already validated in this session.
		if ( isset( $_SESSION['cfturnstile_sunshine_checkout_checked'] ) && wp_verify_nonce( sanitize_text_field( $_SESSION['cfturnstile_sunshine_checkout_checked'] ), 'cfturnstile_sunshine_checkout_check' ) ) {
			return;
		}

		$check = cfturnstile_check();
		if ( true !== $check['success'] ) {
			// Add error to Sunshine cart.
			if ( function_exists( 'SPC' ) && isset( SPC()->cart ) ) {
				SPC()->cart->add_error( 'turnstile', cfturnstile_failed_message() );
			}
		} else {
			// Mark as validated.
			$nonce = wp_create_nonce( 'cfturnstile_sunshine_checkout_check' );
			$_SESSION['cfturnstile_sunshine_checkout_checked'] = $nonce;
		}
	}

	// Clear session on order completion.
	add_action( 'sunshine_checkout_init_order_success', 'cfturnstile_sunshine_checkout_clear', 10, 1 );

	/**
	 * Clear Turnstile session data after successful order.
	 *
	 * @param object $order The order object.
	 */
	function cfturnstile_sunshine_checkout_clear( $order ) {
		if ( isset( $_SESSION['cfturnstile_sunshine_checkout_checked'] ) ) {
			unset( $_SESSION['cfturnstile_sunshine_checkout_checked'] );
		}
	}

	// Clear session on logout.
	add_action( 'wp_logout', 'cfturnstile_sunshine_logout_clear', 10, 0 );

	/**
	 * Clear Turnstile session data on logout.
	 */
	function cfturnstile_sunshine_logout_clear() {
		if ( ! session_id() ) {
			session_start();
		}
		if ( isset( $_SESSION['cfturnstile_sunshine_checkout_checked'] ) ) {
			unset( $_SESSION['cfturnstile_sunshine_checkout_checked'] );
		}
	}
}
