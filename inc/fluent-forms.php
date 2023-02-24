<?php
if (!defined('ABSPATH')) {
	exit;
}

if (get_option('cfturnstile_fluent')) {

	// Get turnstile field: Fluent Forms
	add_action('fluentform_render_item_submit_button', 'cfturnstile_field_fluent_form', 10, 2);
	function cfturnstile_field_fluent_form($item, $form)
	{
		if (!cfturnstile_form_disable($form->id, 'cfturnstile_fluent_disable')) {
			$unique_id = mt_rand();
			cfturnstile_field_show('.fluentform .ff-btn-submit', 'turnstileFluentCallback', 'fluent-form-' . $form->id, '-fluent-' . $unique_id);
		}
	}

	// Fluent Forms Check
	add_action('fluentform_before_insert_submission', 'cfturnstile_fluent_check', 10, 3);
	function cfturnstile_fluent_check($insertData, $data, $form)
	{
		if (!cfturnstile_form_disable($form->id, 'cfturnstile_fluent_disable')) {
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
