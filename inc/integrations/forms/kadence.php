<?php
if (!defined('ABSPATH')) {
	exit;
}
if (get_option("cfturnstile_kadence")) {

    // Enqueue Turnstile JS for Kadence Blocks Form
    add_action('wp_enqueue_scripts', 'cfturnstile_enqueue_kadence_script');
    function cfturnstile_enqueue_kadence_script() {
        if (cfturnstile_whitelisted()) {
            return;
        }

        if (is_page() || is_single()) {
            $content = get_the_content();
            if (has_block('kadence/advanced-form', $content) || has_block('kadence/form', $content)) {
                
                // Enqueue the JavaScript file
                wp_enqueue_script('cfturnstile-kadence', plugins_url('simple-cloudflare-turnstile/js/integrations/kadence.js'), array('cfturnstile'), '1.0', true);

                $uniqueId = wp_rand();
                ob_start();
                cfturnstile_field_show('.kb-submit-field .kb-button', 'turnstileKadenceCallback', 'kdforms-' . $uniqueId, '-kadence');
                $recaptcha_field = ob_get_clean();
                // Remove line breaks and the failed text div
                $recaptcha_field = preg_replace('/<br.*?>/', '', $recaptcha_field);
                $recaptcha_field = preg_replace('/<div class="cf-turnstile-failed-text.*?<\/div>/', '', $recaptcha_field);
                // Pass the site key to the JavaScript file
                wp_localize_script('cfturnstile-kadence', 'cfTurnstileVars', [
                    'sitekey' => get_option('cfturnstile_key'),
                    'field' => $recaptcha_field
                ]);
            }
        }
    }

    // Kadence Blocks PRO Contact Form Submission Check
    add_action('kadence_blocks_form_verify_nonce', 'cfturnstile_kadence_check', 10, 1);
    function cfturnstile_kadence_check($nonce) {

        if (cfturnstile_whitelisted()) {
            return $nonce;
        }

        if (empty($_POST['cf-turnstile-response'])) {
            wp_die(__('Please verify that you are human.', 'simple-cloudflare-turnstile'));
        }

        $check = cfturnstile_check($_POST['cf-turnstile-response']);
        $success = $check['success'];
        if ($success != true) {
            wp_die(__('Please verify that you are human.', 'simple-cloudflare-turnstile'));
        }

        return $nonce;

    }
    
}