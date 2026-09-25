<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wordfence Login Security compatibility.
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
