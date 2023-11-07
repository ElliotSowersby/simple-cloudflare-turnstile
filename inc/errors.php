<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Display error notice if Turnstile is not showing on forms
 */
add_action('admin_notices', 'cfturnstile_tested_notice');
function cfturnstile_tested_notice() {
	if(!isset($_GET['page']) || $_GET['page'] != 'cfturnstile') {
		if (!empty(get_option('cfturnstile_key')) && !empty(get_option('cfturnstile_secret'))) {
			// Get the option from the database
			$cfturnstile_tested = get_option('cfturnstile_tested');
			
			// If the option is 'no', display the error notice
			if ($cfturnstile_tested === 'no') {
				echo '<div class="notice notice-error is-dismissible">';
				echo sprintf(
					__('<p>Cloudflare Turnstile is not currently showing on your forms. Please test the API response on the <a href="%s">settings page</a>.</p>', 'simple-cloudflare-turnstile'),
					admin_url('options-general.php?page=cfturnstile')
				);
				echo '</div>';
			}
		}
	}
}

/**
 * Gets the custom Turnstile failed message
 */
function cfturnstile_failed_message($default = "") {
	if (!$default && !empty(get_option('cfturnstile_error_message')) && get_option('cfturnstile_error_message')) {
		return sanitize_text_field(get_option('cfturnstile_error_message'));
	} else {
		return esc_html__('Please verify that you are human.', 'simple-cloudflare-turnstile');
	}
}

/**
 * Gets the official Turnstile error message
 *
 * @param string $code
 * @return string
 */
function cfturnstile_error_message($code) {
	switch ($code) {
		case 'missing-input-secret':
			return esc_html__('The secret parameter was not passed.', 'simple-cloudflare-turnstile');
		case 'invalid-input-secret':
			return esc_html__('The secret parameter was invalid or did not exist.', 'simple-cloudflare-turnstile');
		case 'missing-input-response':
			return esc_html__('The response parameter was not passed.', 'simple-cloudflare-turnstile');
		case 'invalid-input-response':
			return esc_html__('The response parameter is invalid or has expired.', 'simple-cloudflare-turnstile');
		case 'bad-request':
			return esc_html__('The request was rejected because it was malformed.', 'simple-cloudflare-turnstile');
		case 'timeout-or-duplicate':
			return esc_html__('The response parameter has already been validated before.', 'simple-cloudflare-turnstile');
		case 'internal-error':
			return esc_html__('An internal error happened while validating the response. The request can be retried.', 'simple-cloudflare-turnstile');
		default:
			return esc_html__('There was an error with Turnstile response. Please check your keys are correct.', 'simple-cloudflare-turnstile');
	}
}