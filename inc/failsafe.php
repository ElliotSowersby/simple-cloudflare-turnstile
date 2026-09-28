<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Determine if Cloudflare Turnstile is down/unreachable.
 * Uses the public API script as a lightweight probe.
 *
 * @return bool
 */
function cfturnstile_is_cloudflare_down() {
    $cached = get_transient( 'cfturnstile_cf_status' );
    if ( $cached !== false ) {
        return $cached === 'down';
    }

    $resp    = wp_remote_get( 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=auto', array( 'timeout' => 5 ) );
    $is_down = is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) >= 500;

    set_transient( 'cfturnstile_cf_status', $is_down ? 'down' : 'up', 2 * MINUTE_IN_SECONDS );

    return $is_down;
}

/**
 * Render Google reCAPTCHA widget for failsafe mode and enqueue script.
 *
 * @param string $unique_id
 * @return void
 */
function cfturnstile_render_recaptcha_widget($unique_id = '') {
    $recaptcha_site_key = trim( (string) get_option('cfturnstile_recaptcha_site_key') );
    if ( empty($recaptcha_site_key) ) {
        return;
    }
    $defer = get_option('cfturnstile_defer_scripts', 1) ? array('strategy' => 'defer') : array();
    wp_enqueue_script('cfturnstile-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, $defer);
    do_action('cfturnstile_before_field', esc_attr($unique_id));
    ?>
    <input type="hidden" name="cfturnstile_failsafe" value="recaptcha" />
    <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>"></div>
    <?php
    // Pass empty $button_id to avoid disable-submit styling
    do_action('cfturnstile_after_field', esc_attr($unique_id), '');
}

/**
 * Render a hidden marker for failsafe "allow submissions" mode.
 * This is used by integrations that otherwise require a cf-turnstile-response.
 *
 * @return void
 */
function cfturnstile_render_allow_failsafe_marker() {
    echo '<input type="hidden" name="cfturnstile_failsafe" value="allow" />';
}

/**
 * Verify Google reCAPTCHA response (used for failsafe)
 *
 * @param string|null $response Optional. The reCAPTCHA response, for requests that don't POST it (e.g. the block checkout). Defaults to $_POST['g-recaptcha-response'].
 * @param string      $form_action Optional. The form identifier, passed on to cfturnstile_after_check.
 * @return array $results
 */
function cfturnstile_verify_recaptcha( $response = null, $form_action = '' ) {
    $results = array();

    $recaptcha_secret = trim( (string) get_option('cfturnstile_recaptcha_secret_key') );
    $recaptcha_response = '';
    if ( null !== $response ) {
        $recaptcha_response = sanitize_text_field( $response );
    } elseif ( isset($_POST['g-recaptcha-response']) ) {
        $recaptcha_response = sanitize_text_field( $_POST['g-recaptcha-response'] );
    }
    if ( empty($recaptcha_secret) || empty($recaptcha_response) ) {
        $results['success'] = false;
        $results['error_code'] = empty($recaptcha_secret) ? 'missing-input-secret' : 'missing-input-response';
        $recaptcha_resp_obj = (object) array( 'success' => false );
        do_action('cfturnstile_after_check', $recaptcha_resp_obj, $results, $form_action);
        return $results;
    }

    $recaptcha_req = array(
        'body' => array(
            'secret'   => $recaptcha_secret,
            'response' => $recaptcha_response,
            'remoteip' => cfturnstile_get_ip(),
        ),
    );
    $recaptcha_verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', $recaptcha_req);
    if ( is_wp_error( $recaptcha_verify ) ) {
        $results['success'] = false;
        $results['error_code'] = 'bad-request';
        $recaptcha_resp_obj = (object) array( 'success' => false );
        do_action('cfturnstile_after_check', $recaptcha_resp_obj, $results, $form_action);
        return $results;
    }
    $recaptcha_body = wp_remote_retrieve_body( $recaptcha_verify );
    $recaptcha_json = json_decode( $recaptcha_body );
    $recaptcha_success = ( isset($recaptcha_json->success) && $recaptcha_json->success );
    $results['success'] = $recaptcha_success ? true : false;
    if ( !$recaptcha_success && isset($recaptcha_json->{'error-codes'}) && is_array($recaptcha_json->{'error-codes'}) && !empty($recaptcha_json->{'error-codes'}) ) {
        $results['error_code'] = $recaptcha_json->{'error-codes'}[0];
    }
    $recaptcha_resp_obj = (object) array( 'success' => $recaptcha_success ? true : false );
    do_action('cfturnstile_after_check', $recaptcha_resp_obj, $results, $form_action);
    return $results;
}

/**
 * Backend failover handler: if Cloudflare siteverify failed, apply configured failsafe.
 *
 * @param WP_Error|array $verify The result from wp_remote_post to Cloudflare siteverify
 * @param string $form_action Optional. The form identifier, passed on to the reCAPTCHA check.
 * @return array|null Returns results array if handled by failsafe, or null to continue Turnstile path
 */
function cfturnstile_handle_failover_backend($verify, $form_action = '') {
    if ( ! get_option('cfturnstile_failover') ) {
        return null;
    }
    $is_error = is_wp_error( $verify );
    $code = $is_error ? 0 : wp_remote_retrieve_response_code( $verify );
    if ( $is_error || $code >= 500 ) {
        $type = get_option('cfturnstile_failsafe_type', 'allow');
        if ( $type === 'recaptcha' ) {
            return cfturnstile_verify_recaptcha( null, $form_action );
        }
        return array('success' => true);
    }
    return null;
}
