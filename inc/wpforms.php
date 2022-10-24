<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_wpforms')) {

	// Get turnstile field: WP Forms
	add_action('wpforms_display_submit_before','cfturnstile_field_wpf_form');
	function cfturnstile_field_wpf_form() { cfturnstile_field_show('.wpforms-submit', 'turnstileWPFCallback'); }

	// WP Forms Check
	add_action('wpforms_process_before', 'cfturnstile_wpf_check', 10, 2);
	function cfturnstile_wpf_check($entry, $form_data){
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['cf-turnstile-response'] ) ) {
			$check = cfturnstile_check();
			$success = $check['success'];
			if($success != true) {
				wpforms()->process->errors[ $form_data[ 'id' ] ][ 'header' ] = esc_html__( 'Please verify that you are human.', 'simple-cloudflare-turnstile' );
			}
		} else {
			wpforms()->process->errors[ $form_data[ 'id' ] ][ 'header' ] = esc_html__( 'Please verify that you are human.', 'simple-cloudflare-turnstile' );
		}
	}

}
