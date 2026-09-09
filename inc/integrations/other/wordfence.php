<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wordfence Login Security compatibility.
 *
 * Wordfence 9.0.0 added passkey (WebAuthn) login. After it has cryptographically verified
 * the passkey assertion, it deliberately runs the login through the normal WordPress
 * authenticate chain via wp_authenticate() so that other plugins can participate. That
 * request is a dedicated admin-ajax call (wordfence_ls_finish_passkey_login) built from the
 * passkey assertion alone - it never carries a Turnstile token - so the global WordPress
 * login check rejected every passkey login with "missing-input-response".
 *
 * A passkey assertion is a phishing-resistant, device-bound proof that cannot be produced by
 * a bot, so a bot challenge adds nothing to it. Skip the Turnstile login check only while
 * Wordfence holds a verified passkey authentication for the current request. That state is
 * request-scoped PHP memory set by Wordfence itself after verification succeeds; it cannot
 * be set from request parameters, so it cannot be used to bypass the check on a password login.
 *
 * @see WordfenceLS\Controller_Passkey::authenticate_finished_login()
 * @see WordfenceLS\Controller_Passkey::has_verified_authentication()
 */
add_filter( 'cfturnstile_wp_login_checks', 'cfturnstile_wordfence_skip_wp_login_check', 10, 1 );
function cfturnstile_wordfence_skip_wp_login_check( $skip ) {

	if ( true === $skip ) {
		return $skip;
	}

	if (
		class_exists( '\WordfenceLS\Controller_Passkey' )
		&& method_exists( '\WordfenceLS\Controller_Passkey', 'shared' )
		&& method_exists( '\WordfenceLS\Controller_Passkey', 'has_verified_authentication' )
	) {
		$passkey = \WordfenceLS\Controller_Passkey::shared();
		if ( $passkey && $passkey->has_verified_authentication() ) {
			return true;
		}
	}

	return $skip;
}
