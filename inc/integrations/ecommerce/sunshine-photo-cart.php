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
 * Whether Turnstile protects the Sunshine Photo Cart login forms.
 * Always on while "WordPress Login" covers every login form (not wp-login.php only),
 * as Sunshine's login runs wp_signon() and would otherwise fail the WP login check.
 *
 * @return bool
 */
function cfturnstile_sunshine_login_enabled() {
	if ( get_option( 'cfturnstile_sunshine_login' ) ) {
		return true;
	}
	return get_option( 'cfturnstile_login' ) && ! get_option( 'cfturnstile_login_only', 0 );
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
 * Display Turnstile field on Sunshine Photo Cart checkout form (payment step).
 */
function cfturnstile_field_sunshine_checkout() {
	$guest_only = esc_attr( get_option( 'cfturnstile_sunshine_guest_only' ) );
	if ( ! $guest_only || ( $guest_only && ! is_user_logged_in() ) ) {
		cfturnstile_field_show( '#sunshine--checkout--submit', 'turnstileSunshineCheckoutCallback', 'sunshine-checkout', '-sunshine-checkout', 'sct-sunshine-checkout' );
	}
}

/**
 * Enqueue Turnstile API script for AJAX-loaded forms.
 * Sunshine Photo Cart loads login/signup/reset forms via AJAX into modals,
 * and checkout sections via AJAX, so we need to ensure the Turnstile API
 * is available before the forms load.
 */
function cfturnstile_sunshine_enqueue_scripts() {
	$login    = cfturnstile_sunshine_login_enabled();
	$register = get_option( 'cfturnstile_sunshine_register' );
	$reset    = get_option( 'cfturnstile_sunshine_reset' );
	$checkout = get_option( 'cfturnstile_sunshine_checkout' );

	// Check if any integration is enabled.
	if ( ! $login && ! $register && ! $reset && ! $checkout ) {
		return;
	}

	// Only load on Sunshine pages.
	if ( function_exists( 'is_sunshine' ) && ! is_sunshine() ) {
		return;
	}

	// For checkout, check guest-only setting.
	if ( $checkout && ! $login && ! $register && ! $reset ) {
		$guest_only = esc_attr( get_option( 'cfturnstile_sunshine_guest_only' ) );
		if ( $guest_only && is_user_logged_in() ) {
			return;
		}
	}

	// Enqueue the Turnstile API with explicit render mode for manual control.
	if ( ! wp_script_is( 'cfturnstile', 'enqueued' ) ) {
		$defer = get_option( 'cfturnstile_defer_scripts' ) ? array( 'strategy' => 'defer' ) : array();
		cfturnstile_register_api( $defer );
		wp_enqueue_script( 'cfturnstile' );
	}
}
add_action( 'wp_enqueue_scripts', 'cfturnstile_sunshine_enqueue_scripts', 10 );

/**
 * Add JavaScript to handle AJAX-loaded modal forms.
 * Sunshine Photo Cart loads login/signup/reset forms via AJAX into modals.
 */
function cfturnstile_sunshine_modal_scripts() {
	// Only add if at least one form integration is enabled.
	$login    = cfturnstile_sunshine_login_enabled();
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
					// Explicit rendering ignores data-*-callback, so pass the real functions.
					var sctOpts = (typeof window.cfturnstileOpts === 'function') ? window.cfturnstileOpts('#' + elId) : {};
					sctOpts.sitekey = '<?php echo esc_js( $key ); ?>';
					turnstile.render('#' + elId, sctOpts);
				} catch (e) {
					// Reset if render failed
					sctSunshineRendered[elId] = false;
				}
			});
		}

		// Function to reset Turnstile widgets after form submission (tokens are single-use)
		function sctSunshineResetTurnstile() {
			if (typeof turnstile === 'undefined') {
				return;
			}

			$('#sunshine--modal .cf-turnstile').each(function() {
				var elId = $(this).attr('id');
				if (elId) {
					try {
						turnstile.reset('#' + elId);
					} catch (e) {
						// Ignore reset errors
					}
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

		// Reset Turnstile after form submissions (login, signup, reset password)
		// Tokens are single-use, so we need a fresh one for retry attempts
		$(document).ajaxComplete(function(event, xhr, settings) {
			if (settings.data && typeof settings.data === 'string') {
				if (settings.data.indexOf('sunshine_modal_login') !== -1 ||
					settings.data.indexOf('sunshine_modal_signup') !== -1 ||
					settings.data.indexOf('sunshine_modal_reset_password') !== -1) {
					setTimeout(sctSunshineResetTurnstile, 300);
				}
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
 * Skip WordPress global login Turnstile check inside Sunshine's own handlers.
 * Sunshine's forms call wp_signon(), and Turnstile tokens are single-use, so the
 * WP authenticate filter would try to verify an already-consumed token and fail.
 * Never skip on a posted Sunshine nonce: they are public, so a nonce alone would
 * skip the check on wp-login.php.
 *
 * Sunshine signup and checkout call wp_insert_user(), which doesn't run
 * registration_errors, so the WP register check needs no skip.
 */
add_filter( 'cfturnstile_wp_login_checks', 'cfturnstile_sunshine_skip_wp_login_check', 10, 1 );
function cfturnstile_sunshine_skip_wp_login_check( $skip ) {
	// Sunshine login forms (modal and page) accept any account's password, so only skip once the Sunshine Login check has passed in this request.
	if (
		! empty( $GLOBALS['cfturnstile_sunshine_login_verified'] )
		&& ( doing_action( 'wp_ajax_nopriv_sunshine_modal_login' ) || doing_action( 'wp_ajax_sunshine_modal_login' ) || doing_action( 'wp' ) )
	) {
		unset( $GLOBALS['cfturnstile_sunshine_login_verified'] );
		return true;
	}
	// Sunshine signup and checkout account creation only log in the account they have just created.
	if (
		doing_action( 'wp_ajax_nopriv_sunshine_modal_signup' )
		|| doing_action( 'wp_ajax_sunshine_modal_signup' )
		|| doing_action( 'sunshine_checkout_section_contact_process' )
	) {
		return true;
	}
	return $skip;
}

/**
 * Sunshine Photo Cart Login - Display hook.
 */
if ( cfturnstile_sunshine_login_enabled() ) {
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

		$check = cfturnstile_check( '', 'sunshine-login' );
		if ( true !== $check['success'] ) {
			return cfturnstile_failed_message();
		}

		// Let the wp_signon() that follows skip the WP login check (see cfturnstile_sunshine_skip_wp_login_check).
		$GLOBALS['cfturnstile_sunshine_login_verified'] = true;

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

		$check = cfturnstile_check( '', 'sunshine-register' );
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

		$check = cfturnstile_check( '', 'sunshine-reset' );
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
	add_action( 'sunshine_checkout_before_submit', 'cfturnstile_field_sunshine_checkout' );

	// Validation action - adds error to cart if validation fails.
	add_action( 'sunshine_checkout_validation', 'cfturnstile_sunshine_checkout_check', 10, 2 );

	/**
	 * Validate Turnstile on Sunshine checkout form submission.
	 *
	 * @param string $section Active checkout section.
	 * @param array  $data    Form POST data.
	 */
	function cfturnstile_sunshine_checkout_check( $section, $data ) {
		// Only validate on sections that have a Turnstile widget.
		if ( 'contact' !== $section && 'payment' !== $section ) {
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

		$check = cfturnstile_check( '', 'sunshine-checkout' );
		if ( true !== $check['success'] ) {
			if ( function_exists( 'SPC' ) && isset( SPC()->cart ) ) {
				SPC()->cart->add_error( cfturnstile_failed_message() );
				if ( 'contact' === $section ) {
					wp_send_json_error();
				}
			}
		}
	}

	// Clear on order completion
	add_action( 'sunshine_checkout_init_order_success', 'cfturnstile_sunshine_checkout_clear', 10, 1 );

	/**
	 * Clear Turnstile transient data after successful order.
	 *
	 * @param object $order The order object.
	 */
	function cfturnstile_sunshine_checkout_clear( $order ) {
		cfturnstile_clear_verified( 'cfturnstile_sunshine_checkout_checked' );
	}

	/**
	 * Add JavaScript to handle AJAX-loaded checkout sections.
	 * Sunshine Photo Cart reloads checkout sections via AJAX, so we need to
	 * render/reset Turnstile when the payment section loads.
	 */
	function cfturnstile_sunshine_checkout_scripts() {
		// Only load on checkout page.
		if ( ! function_exists( 'is_sunshine_page' ) || ! is_sunshine_page( 'checkout' ) ) {
			return;
		}

		// Check if guest only is enabled.
		$guest_only = esc_attr( get_option( 'cfturnstile_sunshine_guest_only' ) );
		if ( $guest_only && is_user_logged_in() ) {
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
			// Inject Turnstile widget container into the contact section.
			// The sunshine_checkout_after_contact PHP hook fires outside the
			// foreach loop so we must inject via JS instead.
			function sctSunshineCheckoutInjectContact() {
				var $btn = $('#sunshine--checkout--contact-button-step');
				if ($btn.length && !$btn.prev('.cf-turnstile').length) {
					$btn.before('<div id="sct-sunshine-checkout-contact" class="cf-turnstile" data-sitekey="<?php echo esc_js( $key ); ?>"></div>');
				}
			}

			// Function to render Turnstile widgets on checkout
			function sctSunshineCheckoutRenderTurnstile() {
				if (typeof turnstile === 'undefined') {
					return;
				}

				// Inject contact section widget if needed
				sctSunshineCheckoutInjectContact();

				$('#sunshine--checkout--form .cf-turnstile').each(function() {
					var $el = $(this);
					var elId = $el.attr('id');

					// Skip if no ID or already has content
					if (!elId || $el.find('iframe').length > 0 || $el.children().length > 0) {
						return;
					}

					try {
						// Explicit rendering ignores data-*-callback, so pass the real functions.
						var sctOpts = (typeof window.cfturnstileOpts === 'function') ? window.cfturnstileOpts('#' + elId) : {};
						sctOpts.sitekey = '<?php echo esc_js( $key ); ?>';
						turnstile.render('#' + elId, sctOpts);
					} catch (e) {
						// Render error
					}
				});
			}

			// Function to reset Turnstile widget on checkout
			function sctSunshineCheckoutResetTurnstile() {
				if (typeof turnstile === 'undefined') {
					return;
				}

				$('#sunshine--checkout--form .cf-turnstile').each(function() {
					var elId = $(this).attr('id');
					if (elId) {
						try {
							turnstile.reset('#' + elId);
						} catch (e) {
							// Ignore reset errors
						}
					}
				});
			}

			// Listen for checkout reload events (triggers after AJAX replaces checkout HTML)
			$(document).on('sunshine_reload_checkout', function(event, data) {
				setTimeout(sctSunshineCheckoutRenderTurnstile, 500);
			});

			// Also listen for payment method changes which may reload the form
			$(document).on('sunshine_checkout_payment_change', function() {
				setTimeout(sctSunshineCheckoutResetTurnstile, 300);
			});

			// Also listen for ajaxComplete as a fallback for checkout updates
			$(document).ajaxComplete(function(event, xhr, settings) {
				if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
					if (settings.data && typeof settings.data === 'string' && settings.data.indexOf('sunshine_checkout') !== -1) {
						setTimeout(sctSunshineCheckoutRenderTurnstile, 600);
					}
				}
			});

			// Poll until the Turnstile API is loaded, then render.
			// The API script may be deferred, so a single timeout is unreliable.
			var sctCheckoutPollCount = 0;
			function sctSunshineCheckoutPollRender() {
				if (typeof turnstile !== 'undefined') {
					sctSunshineCheckoutRenderTurnstile();
				} else if (sctCheckoutPollCount < 30) {
					sctCheckoutPollCount++;
					setTimeout(sctSunshineCheckoutPollRender, 200);
				}
			}

			$(document).ready(function() {
				sctSunshineCheckoutPollRender();
			});
		})(jQuery);
		</script>
		<?php
	}
	add_action( 'wp_footer', 'cfturnstile_sunshine_checkout_scripts', 999 );
}