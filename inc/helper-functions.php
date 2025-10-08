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
