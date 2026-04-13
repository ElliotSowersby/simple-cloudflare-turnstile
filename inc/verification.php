<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build a transient key from the Turnstile token in the current POST.
 *
 * @param string $key Verification key, e.g. 'cfturnstile_checkout_checked'.
 * @return string|false Transient key, or false if no token is present.
 */
function cfturnstile_transient_key( $key ) {
	$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( $_POST['cf-turnstile-response'] ) : '';
	if ( $token ) {
		return 'cft_' . substr( md5( $key . '_t' . $token ), 0, 20 );
	}

	return false;
}

/**
 * Store a verification flag tied to the current Turnstile token.
 *
 * Uses a short-lived transient keyed to the token so each token can only
 * be used once.  Turnstile tokens are single-use by design.
 *
 * @param string $key     Verification key, e.g. 'cfturnstile_checkout_checked'.
 * @param string $context Reserved for future use (default 'default').
 */
function cfturnstile_set_verified( $key, $context = 'default' ) {
	$transient_key = cfturnstile_transient_key( $key );
	if ( $transient_key ) {
		set_transient( $transient_key, 1, 20 );
	}
}

/**
 * Check whether a verification flag is set for the current Turnstile token.
 *
 * @param string $key     Verification key, e.g. 'cfturnstile_checkout_checked'.
 * @param string $context Reserved for future use (default 'default').
 * @return bool
 */
function cfturnstile_get_verified( $key, $context = 'default' ) {
	$transient_key = cfturnstile_transient_key( $key );
	if ( $transient_key ) {
		return (bool) get_transient( $transient_key );
	}

	return false;
}

/**
 * Clear a verification flag.
 *
 * @param string $key     Verification key.
 * @param string $context Reserved for future use (default 'default').
 */
function cfturnstile_clear_verified( $key, $context = 'default' ) {
	$transient_key = cfturnstile_transient_key( $key );
	if ( $transient_key ) {
		delete_transient( $transient_key );
	}
}
