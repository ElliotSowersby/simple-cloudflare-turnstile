<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( get_option( 'cfturnstile_breakdance' ) ) {

	/**
	 * Breakdance form slugs, mapped to the form name used as the widget action and in Analytics.
	 *
	 * Every Breakdance form element (Form Builder, Login, Register, Forgot Password) submits to
	 * admin-ajax.php as "breakdance_form_{slug}", and its slug is in the form's data-options.
	 *
	 * @return array
	 */
	function cfturnstile_breakdance_forms() {
		return array(
			'custom'          => 'breakdance-form',
			'login'           => 'breakdance-login',
			'register'        => 'breakdance-register',
			'forgot_password' => 'breakdance-forgot-password',
		);
	}

	/**
	 * Whether this request renders the Breakdance builder rather than the front end.
	 *
	 * @return bool
	 */
	function cfturnstile_breakdance_is_builder() {
		if ( function_exists( '\Breakdance\isRequestFromBuilderIframe' ) && \Breakdance\isRequestFromBuilderIframe() ) {
			return true;
		}
		// The builder renders elements through its own admin-ajax action.
		if ( function_exists( '\Breakdance\isRequestFromBuilderSsr' ) && \Breakdance\isRequestFromBuilderSsr() ) {
			return true;
		}
		return is_admin() && ! wp_doing_ajax();
	}

	/**
	 * Load the scripts when a Breakdance form is rendered.
	 *
	 * Fired from Breakdance's form template, so it covers forms in popups, global blocks and
	 * header/footer templates too. The widget itself is added by breakdance-forms.js: Breakdance
	 * caches identical Twig renders within a request, so markup printed here would not be unique
	 * per form. Nothing may be echoed here, as it would end up inside the form markup.
	 */
	add_action( 'breakdance_form_start', 'cfturnstile_breakdance_form_rendered' );
	function cfturnstile_breakdance_form_rendered() {
		if ( cfturnstile_breakdance_is_builder() || wp_script_is( 'cfturnstile-breakdance-forms', 'enqueued' ) ) {
			return;
		}
		if ( cfturnstile_whitelisted() || apply_filters( 'cfturnstile_widget_disable', false ) ) {
			return;
		}
		// Too late for an enqueue to reach the page.
		if ( did_action( 'wp_print_footer_scripts' ) ) {
			return;
		}
		cfturnstile_breakdance_enqueue_scripts();
	}

	function cfturnstile_breakdance_enqueue_scripts() {

		// Determine failsafe mode (keeps UI and backend in sync)
		$failsafe_mode = '';
		if ( get_option( 'cfturnstile_failover' ) && function_exists( 'cfturnstile_is_cloudflare_down' ) && cfturnstile_is_cloudflare_down() ) {
			$failsafe_mode = get_option( 'cfturnstile_failsafe_type', 'allow' );
			if ( $failsafe_mode !== 'recaptcha' && $failsafe_mode !== 'allow' ) {
				$failsafe_mode = 'allow';
			}
			if ( $failsafe_mode === 'recaptcha' && ! wp_script_is( 'cfturnstile-recaptcha', 'enqueued' ) ) {
				$defer = get_option( 'cfturnstile_defer_scripts', 1 ) ? array( 'strategy' => 'defer' ) : array();
				wp_enqueue_script( 'cfturnstile-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, $defer );
			}
		}

		// Enqueue Turnstile API script (only when not in failsafe UI mode)
		$deps = array();
		if ( $failsafe_mode === '' ) {
			if ( ! wp_script_is( 'cfturnstile', 'enqueued' ) ) {
				$defer = get_option( 'cfturnstile_defer_scripts', 1 ) ? array( 'strategy' => 'defer' ) : array();
				cfturnstile_register_api( $defer );
				wp_enqueue_script( 'cfturnstile' );
			}
			$deps[] = 'cfturnstile';
		}

		// Load the interaction-only label helper if needed and make it a dependency
		$label_enable = get_option( 'cfturnstile_widget_label_enable', 0 ) ? true : false;
		if ( $label_enable && get_option( 'cfturnstile_appearance', 'always' ) === 'interaction-only' ) {
			if ( ! wp_script_is( 'cfturnstile-label-js', 'enqueued' ) ) {
				wp_enqueue_script( 'cfturnstile-label-js', plugins_url( 'simple-cloudflare-turnstile/js/interaction-label.js' ), array(), '1.1', true );
			}
			$deps[] = 'cfturnstile-label-js';
		}

		wp_enqueue_script(
			'cfturnstile-breakdance-forms',
			plugins_url( 'simple-cloudflare-turnstile/js/integrations/breakdance-forms.js' ),
			$deps,
			'1.1',
			true
		);

		// Resolve widget label text
		$label_text = get_option( 'cfturnstile_widget_label_text' );
		$label_text = is_string( $label_text ) ? trim( $label_text ) : '';
		if ( $label_text === '' ) {
			$label_text = __( 'Let us know you are human:', 'simple-cloudflare-turnstile' );
		} else {
			$label_text = wp_strip_all_tags( $label_text );
		}

		$position = get_option( 'cfturnstile_breakdance_pos', 'before' );
		if ( ! in_array( $position, array( 'before', 'after' ), true ) ) {
			$position = 'before';
		}
		$align = get_option( 'cfturnstile_breakdance_align', 'left' );
		if ( ! in_array( $align, array( 'left', 'center', 'right' ), true ) ) {
			$align = 'left';
		}
		$language = sanitize_text_field( get_option( 'cfturnstile_language' ) );

		// Pass settings to JavaScript
		wp_localize_script( 'cfturnstile-breakdance-forms', 'cfturnstileBreakdanceSettings', array(
			'sitekey'          => sanitize_text_field( get_option( 'cfturnstile_key' ) ),
			'position'         => $position,
			'align'            => $align,
			'theme'            => sanitize_text_field( get_option( 'cfturnstile_theme' ) ),
			'language'         => $language ? $language : 'auto',
			'size'             => sanitize_text_field( get_option( 'cfturnstile_size', 'normal' ) ),
			'appearance'       => sanitize_text_field( get_option( 'cfturnstile_appearance', 'always' ) ),
			'mode'             => $failsafe_mode ? $failsafe_mode : 'turnstile',
			'recaptchaSiteKey' => sanitize_text_field( get_option( 'cfturnstile_recaptcha_site_key' ) ),
			'disableSubmit'    => get_option( 'cfturnstile_disable_button' ) ? true : false,
			'labelEnable'      => $label_enable,
			'labelText'        => $label_text,
			'actions'          => cfturnstile_breakdance_forms(),
		) );
	}

	/**
	 * Leave Breakdance forms out of the generic post-submit reset. breakdance-forms.js resets the
	 * widget once Breakdance has the response, which the generic timer can't see.
	 */
	add_filter( 'cfturnstile_token_refresh_skip_forms', 'cfturnstile_breakdance_refresh_skip' );
	function cfturnstile_breakdance_refresh_skip( $selectors ) {
		return trim( (string) $selectors . ', form.breakdance-form', ', ' );
	}

	/**
	 * Breakdance Forms Check
	 *
	 * Breakdance registers its form handlers on these admin-ajax actions at priority 10, with no
	 * other route to them. Hooking ahead of it rejects a missing or invalid token before
	 * Breakdance's own spam checks and before any of the form's actions run (emails, webhooks,
	 * creating the user). Its form script shows data.message from a non-2xx response.
	 */
	foreach ( array_keys( cfturnstile_breakdance_forms() ) as $cfturnstile_breakdance_slug ) {
		add_action( 'wp_ajax_breakdance_form_' . $cfturnstile_breakdance_slug, 'cfturnstile_breakdance_check', 1 );
		add_action( 'wp_ajax_nopriv_breakdance_form_' . $cfturnstile_breakdance_slug, 'cfturnstile_breakdance_check', 1 );
	}
	unset( $cfturnstile_breakdance_slug );

	function cfturnstile_breakdance_check() {
		$slug = preg_replace( '/^wp_ajax_(nopriv_)?breakdance_form_/', '', current_action() );
		$forms = cfturnstile_breakdance_forms();
		if ( ! isset( $forms[ $slug ] ) ) {
			return;
		}

		$error = array(
			'type'    => 'error',
			// Breakdance inserts the message as HTML.
			'message' => esc_html( cfturnstile_failed_message() ),
		);

		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
			wp_send_json_error( $error, 400 );
		}

		$check = cfturnstile_check( '', $forms[ $slug ] );
		if ( empty( $check['success'] ) ) {
			wp_send_json_error( $error, 400 );
		}

		cfturnstile_breakdance_verified( true );
	}

	/**
	 * Whether a Breakdance form submission has passed the check in this request.
	 *
	 * @param bool $set Pass true to record a passed check.
	 * @return bool
	 */
	function cfturnstile_breakdance_verified( $set = false ) {
		static $verified = false;
		if ( $set ) {
			$verified = true;
		}
		return $verified;
	}

	/**
	 * The Breakdance Login and Register forms run wp_signon() and the registration_errors filter,
	 * which would verify the already spent token a second time in the global WordPress checks.
	 * Only skipped once cfturnstile_breakdance_check() has passed for this request.
	 */
	add_filter( 'cfturnstile_wp_login_checks', 'cfturnstile_breakdance_skip_wp_checks' );
	add_filter( 'cfturnstile_wp_register_checks', 'cfturnstile_breakdance_skip_wp_checks' );
	function cfturnstile_breakdance_skip_wp_checks( $skip ) {
		return cfturnstile_breakdance_verified() ? true : $skip;
	}

}
