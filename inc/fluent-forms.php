<?php
if (!defined('ABSPATH')) {
	exit;
}

if (get_option('cfturnstile_fluent')) {

	// Check if form should show Turnstile
	function cfturnstile_fluent_form_disable($id)
	{
		if (!empty(get_option('cfturnstile_fluent_disable')) && get_option('cfturnstile_fluent_disable')) {
			$disabled = preg_replace('/\s+/', '', get_option('cfturnstile_fluent_disable'));
			$disabled = explode(",", $disabled);
			if (in_array($id, $disabled)) return true;
		}
		return false;
	}

	// Get turnstile field: Fluent Forms
	add_action('fluentform_render_item_submit_button', 'cfturnstile_field_fluent_form', 10, 2);
	function cfturnstile_field_fluent_form($item, $form)
	{
		if (!cfturnstile_fluent_form_disable($form->id)) {
			$unique_id = mt_rand();
			cfturnstile_field_show('.fluentform .ff-btn-submit', 'turnstileFluentCallback', '', '-gf-' . $unique_id);
		}
	}

	// Fluent Forms Check
	add_action('fluentform_before_insert_submission', 'cfturnstile_fluent_check', 10, 3);
	function cfturnstile_fluent_check($insertData, $data, $form)
	{
		if (!cfturnstile_fluent_form_disable($form->id)) {
			$postdata = $data['cf-turnstile-response'];
			$error_message = cfturnstile_failed_message();
			if (!empty($postdata)) {
				$check = cfturnstile_check($postdata);
				$success = $check['success'];
				if ($success != true) {
					wp_die($error_message, 'simple-cloudflare-turnstile');
				}
			} else {
				wp_die($error_message, 'simple-cloudflare-turnstile');
			}
		}
	}
}
