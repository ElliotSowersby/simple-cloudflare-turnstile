<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Get Cloudflare Turnstile site key.
 * Checks for wp-config.php constant first, then falls back to database option.
 *
 * @return string
 */
function cfturnstile_get_site_key() {
	$key = defined('CFTURNSTILE_SITE_KEY') && CFTURNSTILE_SITE_KEY !== ''
		? CFTURNSTILE_SITE_KEY
		: get_option('cfturnstile_key');
	return sanitize_text_field($key);
}

/**
 * Get Cloudflare Turnstile secret key.
 * Checks for wp-config.php constant first, then falls back to database option.
 *
 * @return string
 */
function cfturnstile_get_secret_key() {
	$secret = defined('CFTURNSTILE_SECRET_KEY') && CFTURNSTILE_SECRET_KEY !== ''
		? CFTURNSTILE_SECRET_KEY
		: get_option('cfturnstile_secret');
	return sanitize_text_field($secret);
}

/**
 * Get Turnstile option value with wp-config.php constant support.
 * Priority: wp-config.php constants > database options > fallback default
 *
 * Special handling: whitelist_ips and whitelist_agents allow empty string constants
 * for security (locks down the whitelist completely via wp-config.php).
 *
 * @param string $option_name The option name (with or without 'cfturnstile_' prefix)
 * @param mixed $default Default value if option doesn't exist
 * @return mixed Option value from constant, database or default
 */
function cfturnstile_get_option($option_name, $default = false) {
	if (strpos($option_name, 'cfturnstile_') !== 0) {
		$option_name = 'cfturnstile_' . $option_name;
	}

	$constant_name = strtoupper($option_name);

	// Check wp-config.php constant
	if (defined($constant_name)) {
		$constant_value = constant($constant_name);

		if (is_bool($constant_value)) {
			return $constant_value;
		}
		$security_fields = array('cfturnstile_whitelist_ips', 'cfturnstile_whitelist_agents');
		if (in_array($option_name, $security_fields) && $constant_value === '') {
			return '';
		}

		if ($constant_value !== '' && $constant_value !== null) {
			if (is_numeric($constant_value)) {
				return $constant_value;
			}

			$textarea_fields = array('cfturnstile_whitelist_ips', 'cfturnstile_whitelist_agents', 'cfturnstile_failure_message', 'cfturnstile_error_message');
			if (in_array($option_name, $textarea_fields)) {
				return sanitize_textarea_field($constant_value);
			}

			return sanitize_text_field($constant_value);
		}
	}

	// Check database option
	$db_value = get_option($option_name, null);
	if ($db_value !== null && $db_value !== false) {
		return $db_value;
	}

	// Final fallback
	return $default;
}

/**
 * Check if a Turnstile option is defined as a wp-config.php constant.
 * Used to determine if admin UI fields should be disabled.
 *
 * @param string $option_name The option name (with or without 'cfturnstile_' prefix)
 * @return bool True if constant is defined and not empty (or empty for whitelist fields)
 */
function cfturnstile_is_constant_defined($option_name) {
	if (strpos($option_name, 'cfturnstile_') !== 0) {
		$option_name = 'cfturnstile_' . $option_name;
	}

	$constant_name = strtoupper($option_name);

	if (defined($constant_name)) {
		$constant_value = constant($constant_name);
		$security_fields = array('cfturnstile_whitelist_ips', 'cfturnstile_whitelist_agents');
		if (in_array($option_name, $security_fields) && $constant_value === '') {
			return true;
		}

		// Consider constant as "defined" if it's not empty/null
		if (is_bool($constant_value) || ($constant_value !== '' && $constant_value !== null)) {
			return true;
		}
	}

	return false;
}

/**
 * Get disabled attribute if option is controlled by wp-config.php constant.
 * Returns 'disabled' or empty string for use in form field attributes.
 *
 * @param string $option_name The option name (with or without 'cfturnstile_' prefix)
 * @return string 'disabled' if constant is defined, empty string otherwise
 */
function cfturnstile_disabled_attr($option_name) {
	return cfturnstile_is_constant_defined($option_name) ? 'disabled' : '';
}

/**
 * Display notice if option is controlled by wp-config.php constant.
 * Shows a small italicized message below form fields.
 *
 * @param string $option_name The option name (with or without 'cfturnstile_' prefix)
 * @return void
 */
function cfturnstile_show_constant_notice($option_name) {
	if (cfturnstile_is_constant_defined($option_name)) {
		echo '<br><i style="font-size: 10px; color: #666;">' .
		esc_html__('Controlled by wp-config.php constant', 'simple-cloudflare-turnstile') .
		'</i>';
	}
}

/**
 * Get the appropriate test status based on key source.
 * Uses network-wide test status when constants are defined in multisite,
 * otherwise uses per-site option.
 *
 * @return string 'yes', 'no', or empty
 */
function cfturnstile_get_test_status() {
	if (defined('CFTURNSTILE_SITE_KEY') && CFTURNSTILE_SITE_KEY !== '' &&
		defined('CFTURNSTILE_SECRET_KEY') && CFTURNSTILE_SECRET_KEY !== '') {

		if (is_multisite()) {
			return get_site_option('cfturnstile_tested_network', '');
		} else {
			return get_option('cfturnstile_tested', '');
		}
	}

	return get_option('cfturnstile_tested', '');
}

/**
 * Update test status based on key source.
 * Updates network-wide status when constants are defined in multisite,
 * otherwise updates per-site option.
 *
 * @param string $status 'yes' or 'no'
 */
function cfturnstile_update_test_status($status) {
	if (defined('CFTURNSTILE_SITE_KEY') && CFTURNSTILE_SITE_KEY !== '' &&
		defined('CFTURNSTILE_SECRET_KEY') && CFTURNSTILE_SECRET_KEY !== '') {

		if (is_multisite()) {
			update_site_option('cfturnstile_tested_network', $status);
		} else {
			update_option('cfturnstile_tested', $status);
		}
	} else {
		update_option('cfturnstile_tested', $status);
	}
}
