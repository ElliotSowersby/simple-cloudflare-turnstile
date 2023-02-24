<?php
if (!defined('ABSPATH')) {
	exit;
}

// Create Topic
if (get_option('cfturnstile_bbpress_create')) {

	// Get field
	add_action('bbp_theme_before_topic_form_submit_wrapper', 'cfturnstile_field_bbpress_create');
	function cfturnstile_field_bbpress_create() {
		$guest_only = get_option('cfturnstile_bbpress_guest_only');
		$align = get_option('cfturnstile_bbpress_align');
		if (!$guest_only || ($guest_only && !is_user_logged_in())) {
			cfturnstile_field_show('#bbp_topic_submit', 'turnstileBBPressCreateCallback', 'bbpress-create', '-bb-create');
			if ($align == "right") echo "<style>#bbpress-forums #cf-turnstile { float: right; }</style>";
		}
	}

	// Validate
	add_action('bbp_new_topic_pre_extras', 'cfturnstile_bbpress_register_check');
}

// Create Topic
if (get_option('cfturnstile_bbpress_reply')) {

	// Get field
	add_action('bbp_theme_before_reply_form_submit_wrapper', 'cfturnstile_field_bbpress_reply');
	function cfturnstile_field_bbpress_reply() {
		$guest_only = get_option('cfturnstile_bbpress_guest_only');
		$align = get_option('cfturnstile_bbpress_align');
		if (!$guest_only || ($guest_only && !is_user_logged_in())) {
			cfturnstile_field_show('#bbp_reply_submit', 'turnstileBBPressReplyCallback', 'bbpress-reply', '-bb-reply');
			if ($align == "right") echo "<style>#bbpress-forums .cf-turnstile { float: right; }</style>";
		}
	}

	// Validate
	add_action('bbp_new_reply_pre_extras', 'cfturnstile_bbpress_register_check');
}

// Validate Function
function cfturnstile_bbpress_register_check() {

	$guest_only = get_option('cfturnstile_bbpress_guest_only');
	if (!$guest_only || ($guest_only && !is_user_logged_in())) {

		if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['cf-turnstile-response'])) {
			$check = cfturnstile_check();
			$success = $check['success'];
			if ($success != true) {
				bbp_add_error('bbp_throw_error', cfturnstile_failed_message());
			}
		} else {
			bbp_add_error('bbp_throw_error', cfturnstile_failed_message());
		}
	}
}
