<?php
if (!defined('ABSPATH')) {
	exit;
}

if (get_option("cfturnstile_mailpoet")) {

    // Add Turnstile to MailPoet
    function cfturnstile_field_mailpoet( $formHtml ) {

        wp_enqueue_script('cfturnstile-mailpoet', plugins_url('simple-cloudflare-turnstile/js/integrations/mailpoet.js'), '', '1.0', true);

        $uniqueId = wp_rand();

        ob_start();
        cfturnstile_field_show('.mailpoet_submit', 'turnstileMailpoetCallback', 'mailpoet-' . $uniqueId, '-mailpoet');
        $turnstile = ob_get_clean();

        $formHtml = preg_replace( '/(<input[^>]*class="mailpoet_submit"[^>]*>)/', $turnstile . '$1', $formHtml );

        return $formHtml;

    }
    add_filter( 'mailpoet_form_widget_post_process', 'cfturnstile_field_mailpoet' );

    // Check Mailpoet Submission
    add_action('mailpoet_subscription_before_subscribe', 'cfturnstile_mailpoet_check', 10, 3);
    function cfturnstile_mailpoet_check($data, $segmentIds, $form) {

        $error_message = cfturnstile_failed_message();

        $token = isset($_POST['data']['cf-turnstile-response']) ? sanitize_text_field($_POST['data']['cf-turnstile-response']) : '';

        if (cfturnstile_whitelisted()) {
            return;
        }

        if (empty($token)) {
            throw new \MailPoet\UnexpectedValueException($error_message);
        }

        $check = cfturnstile_check($token);
        $success = $check['success'];
        if ($success != true) {
            throw new \MailPoet\UnexpectedValueException($error_message);
        }

    }

}