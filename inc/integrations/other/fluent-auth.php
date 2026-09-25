<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FluentAuth (Fluent Security) compatibility.
 *
 * @see FluentAuth\App\Hooks\Handlers\TwoFaHandler::verifyChallenge()
 * @see FluentAuth\App\Hooks\Handlers\TwoFaHandler::verify2FaEmailCode() (FluentAuth 2.x)
 * @see FluentAuth\App\Hooks\Handlers\MagicLoginHandler
 */
add_filter( 'cfturnstile_wp_login_checks', 'cfturnstile_fluentauth_skip_wp_login_check', 10, 1 );
function cfturnstile_fluentauth_skip_wp_login_check( $skip ) {

	if ( true === $skip ) {
		return $skip;
	}

	// FluentAuth 3.x: every second factor (email code, authenticator app, passkey) is answered
	// over admin-ajax, then the login is completed with wp_signon(). FluentAuth raises this flag
	// only around that wp_signon(), after the proof has been verified, and the first factor
	// already passed Turnstile. It is set in-process, so a request cannot fake it.
	if (
		method_exists( '\FluentAuth\App\Helpers\Helper', 'isTokenVerifiedLogin' )
		&& \FluentAuth\App\Helpers\Helper::isTokenVerifiedLogin()
	) {
		return true;
	}

	// Email two-factor (FluentAuth 2.x): FluentAuth verifies the emailed code, then re-authenticates via
	// wp_signon() over admin-ajax. Only trust this action during an AJAX request, so it
	// cannot be appended to a wp-login.php credential POST to bypass the Turnstile check.
	if (
		wp_doing_ajax()
		&& isset( $_REQUEST['action'] )
		&& 'fluent_auth_2fa_email' === sanitize_text_field( wp_unslash( $_REQUEST['action'] ) )
	) {
		return true;
	}

	// Magic login: the login-by-hash link is a GET request handled on 'init'. Requiring GET
	// means there is no credential POST to bypass (wp-login.php only processes logins on POST).
	if (
		isset( $_GET['fls_al'] )
		&& isset( $_SERVER['REQUEST_METHOD'] )
		&& 'GET' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) )
	) {
		return true;
	}

	return $skip;
}
