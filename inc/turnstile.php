<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Create turnstile field template.
 *
 * @param int $button_id
 * @param string $callback
 * @param string $form_name
 * @param string $unique_id
 * @param string $class
 */
function cfturnstile_field_show($button_id = '', $callback = '', $form_name = '', $unique_id = '', $class = '') {
	// Hook to not show
	$hide = apply_filters('cfturnstile_widget_disable', false);
	if($hide) {
		return;
	}
	// Check if whitelisted
	if(!cfturnstile_whitelisted()) {
		// If failsafe enabled and Cloudflare appears down, either hide Turnstile or render reCAPTCHA via helper
		if ( get_option('cfturnstile_failover') && cfturnstile_is_cloudflare_down() ) {
			$failsafe_type = get_option('cfturnstile_failsafe_type', 'allow');
			if ( $failsafe_type === 'recaptcha' ) {
				cfturnstile_render_recaptcha_widget($unique_id);
				return;
			}
			// Failsafe set to allow: do not render Turnstile, but post a marker so validation can pass
			cfturnstile_render_allow_failsafe_marker();
			return;
		}
		// Show Turnstile
		do_action("cfturnstile_enqueue_scripts");
		do_action("cfturnstile_before_field", esc_attr($unique_id));
		if ( get_option('cfturnstile_widget_label_enable', 0) ) {
			$label_text = get_option('cfturnstile_widget_label_text');
			$label_text = is_string($label_text) ? trim($label_text) : '';
			if ($label_text === '') {
				$label_text = __('Let us know you are human:', 'simple-cloudflare-turnstile');
			} else {
				$label_text = wp_strip_all_tags($label_text);
			}
			?>
			<p class="cfturnstile-widget-label" style="font-size: 14px; margin: 0 0 6px 0;"><small><?php echo esc_html($label_text); ?></small></p>
			<?php
		}
		$key = sanitize_text_field(get_option('cfturnstile_key'));
		$theme = sanitize_text_field(get_option('cfturnstile_theme'));
		$language = sanitize_text_field(get_option('cfturnstile_language'));
		$appearance = sanitize_text_field(get_option('cfturnstile_appearance', 'always'));
		$cfturnstile_size = sanitize_text_field(get_option('cfturnstile_size'), 'normal');
		$refresh_timeout = sanitize_text_field(get_option('cfturnstile_refresh_timeout', 'auto'));
			if(!$language) { $language = 'auto'; }
			if(!$refresh_timeout) { $refresh_timeout = 'auto'; }
		?>
		<div id="cf-turnstile<?php echo esc_attr($unique_id); ?>"
		class="cf-turnstile<?php if($class) { echo " " . esc_attr($class); } ?>" <?php if (get_option('cfturnstile_disable_button')) { ?>data-callback="<?php echo esc_attr($callback); ?>"<?php } ?>
		data-sitekey="<?php echo esc_attr($key); ?>"
		data-theme="<?php echo esc_attr($theme); ?>"
		data-language="<?php echo esc_attr($language); ?>"
		data-size="<?php echo esc_attr($cfturnstile_size); ?>"
		data-retry="auto" data-retry-interval="1000"
		data-refresh-expired="auto"
		data-refresh-timeout="<?php echo esc_attr($refresh_timeout); ?>"
		data-action="<?php echo esc_attr($form_name); ?>"
		data-callback="<?php echo esc_attr($callback); ?>"
		<?php if(get_option('cfturnstile_failure_message_enable')) { ?>
		data-error-callback="cfturnstileErrorCallback"
		<?php } ?>
		data-appearance="<?php echo esc_attr($appearance); ?>"></div>
		<?php
		do_action("cfturnstile_after_field", esc_attr($unique_id), $button_id);
	}
}

/**
 * Add Styles Below Turnstile if Disable Submit Enabled
 *
 * @return bool
 */
add_action('cfturnstile_after_field', 'cfturnstile_disable_button_styles', 10, 2);
function cfturnstile_disable_button_styles($unique_id, $button_id) {
	if ($button_id && get_option('cfturnstile_disable_button')) {
		?>
		<style><?php echo esc_html($button_id); ?> { pointer-events: none; opacity: 0.5; }</style>
		<?php
	}
}

/**
 * Add a line break if Turnstile is always showing
 *
 * @return bool
 */
add_action('cfturnstile_after_field', 'cfturnstile_always_br', 15, 1);
function cfturnstile_always_br($unique_id) {
	if(!get_option('cfturnstile_appearance') || get_option('cfturnstile_appearance') == 'always') {
		?>
		<br class="cf-turnstile-br cf-turnstile-br<?php echo esc_attr($unique_id); ?>">
		<?php
	} else {
		?>
		<style>#cf-turnstile<?php echo esc_html($unique_id); ?> iframe { margin-bottom: 15px; }</style>
		<?php
	}
}

/**
 * Extra Styles if WP Admin
 *
 * @return bool
 */
add_action('cfturnstile_after_field', 'cfturnstile_admin_styles', 20, 1);
function cfturnstile_admin_styles($unique_id) {
	if(defined('DOING_AJAX') || is_admin()) {
		return;
	}
	$is_checkout = (function_exists('is_checkout') && is_checkout()) ? true : false;
	if ((!is_page() && !is_single() && !$is_checkout) || strpos($_SERVER['PHP_SELF'], 'wp-login.php') !== false) {
		?>
		<style>#cf-turnstile<?php echo esc_html($unique_id); ?> { margin-left: -15px; }</style>
		<?php
	}
}

/**
 * Show custom failed message after Turnstile if failed
 *
 * @return bool
 */
add_action('cfturnstile_after_field', 'cfturnstile_failed_text', 5, 1);
function cfturnstile_failed_text($unique_id) {
	if(function_exists('cfturnstile_is_block_based_checkout') && cfturnstile_is_block_based_checkout()) {
		return;
	}
	if(get_option('cfturnstile_failure_message_enable')) {
	$failed_text = get_option('cfturnstile_failure_message');
	if(!$failed_text) { $failed_text = esc_html__('Failed to verify you are human. Please contact us if you are having issues.', 'simple-cloudflare-turnstile'); }
	$failed_text = str_replace("'", "\'", $failed_text);
	$failed_text = str_replace('"', '\"', $failed_text);
	?>
	<div class="cf-turnstile-failed-text cf-turnstile-failed-text<?php echo esc_attr($unique_id); ?>"></div>
	<script>
	function cfturnstileErrorCallback() {
		var cfTurnstileFailedText = document.querySelector('.cf-turnstile-failed-text<?php echo esc_html($unique_id); ?>');
		cfTurnstileFailedText.innerHTML = '<p><i><?php echo wp_kses_post($failed_text); ?></i></p>';
	}
	function cfturnstileCallback() {
		var cfTurnstileFailedText = document.querySelector('.cf-turnstile-failed-text<?php echo esc_html($unique_id); ?>');
		cfTurnstileFailedText.innerHTML = '';
	}
	</script>
	<?php
	}
}

/**
 * Render Turnstile (Explicitly)
 */
add_action("cfturnstile_after_field", "cfturnstile_force_render", 10, 1);
function cfturnstile_force_render($unique_id = '') {
	if(function_exists('cfturnstile_is_block_based_checkout') && cfturnstile_is_block_based_checkout()) {
		return;
	}
	$unique_id = sanitize_text_field($unique_id);
	$key = sanitize_text_field(get_option('cfturnstile_key'));
	if($unique_id) {
	?>
	<script>document.addEventListener("DOMContentLoaded", function() { setTimeout(function(){ var e=document.getElementById("cf-turnstile<?php echo esc_html($unique_id); ?>"); if(e&&!e.innerHTML.trim()){turnstile.render(e, {sitekey:"<?php echo esc_html($key); ?>"});} }, 200); });</script>
	<?php
	}
}

/**
 * Checks Turnstile Captcha POST is Valid
 *
 * @param string $postdata
 * @return bool
 */
function cfturnstile_check($postdata = "") {

	$results = array();

	// Check if whitelisted
	if(cfturnstile_whitelisted()) {
		$results['success'] = true;
		return $results;
	}

	// Hook to allow custom skip
	$skip = apply_filters('cfturnstile_widget_disable', false);
	if($skip) {
		$results['success'] = true;
		return $results;
	}

	// Check if POST data is empty
	if (empty($postdata) && isset($_POST['cf-turnstile-response'])) {
		$postdata = sanitize_text_field($_POST['cf-turnstile-response']);
	}

	// If failsafe is present, handle it early
	if ( get_option('cfturnstile_failover') && isset($_POST['cfturnstile_failsafe']) && cfturnstile_is_cloudflare_down() ) {
		$failsafe_flag = sanitize_text_field($_POST['cfturnstile_failsafe']);
		$failsafe_type = get_option('cfturnstile_failsafe_type', 'allow');
		if ( $failsafe_flag === 'recaptcha' && $failsafe_type === 'recaptcha' ) {
			return cfturnstile_verify_recaptcha();
		}
		if ( $failsafe_flag === 'allow' && $failsafe_type === 'allow' ) {
			return array('success' => true);
		}
	}

	// Get Turnstile Keys from Settings
	$key = sanitize_text_field(get_option('cfturnstile_key'));
	$secret = sanitize_text_field(get_option('cfturnstile_secret'));

	if ($key && $secret) {

		$headers = array(
			'body' => [
				'secret' => $secret,
				'response' => $postdata,
				'remoteip' => cfturnstile_get_ip(),
			]
		);
		$verify = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $headers);

		// Failover if Cloudflare is down (centralized handler)
		$handled = cfturnstile_handle_failover_backend($verify);
		if ( $handled !== null ) {
			return $handled;
		}

		$verify = wp_remote_retrieve_body($verify);
		$response = json_decode($verify);

		if ( ! is_object( $response ) ) {
			$results['success'] = false;
			return $results;
		}

		if($response->success) {
			$results['success'] = $response->success;
		} else {
			$results['success'] = false;
		}

		foreach ( $response as $key => $val ) {
			if ( 'error-codes' === $key ) {
				foreach ( $val as $key => $error_val ) {
					$results['error_code'] = $error_val;
					if ( 'invalid-input-secret' === $error_val ) {
						// Rate-limit: only process once per 5 minutes to avoid repeated DB writes on high-traffic sites.
						if ( false === get_transient( 'cfturnstile_invalid_secret_throttle' ) ) {
							set_transient( 'cfturnstile_invalid_secret_throttle', 1, 5 * MINUTE_IN_SECONDS );
							$already_flagged = ( 'no' === get_option( 'cfturnstile_soft_tested' ) );
							update_option( 'cfturnstile_invalid_secret_notice', '1' );
							update_option( 'cfturnstile_soft_tested', 'no' );
							if ( ! $already_flagged ) {
								$admin_email  = get_option( 'admin_email' );
								$site_name    = get_bloginfo( 'name' );
								$settings_url = admin_url( 'options-general.php?page=cfturnstile' );
								$subject      = sprintf(
									/* translators: %s: Site name. */
									__( '[%s] Cloudflare Turnstile: Invalid Secret Key Detected', 'simple-cloudflare-turnstile' ),
									$site_name
								);
								$message = sprintf(
									/* translators: 1: Site name, 2: Settings page URL. */
									__( "Cloudflare has reported that the Turnstile secret key on %1\$s is invalid (error: invalid-input-secret).\n\nTurnstile is still active on your forms, but verifications may be failing until the key is corrected.\n\nPlease check your API keys on the settings page:\n%2\$s", 'simple-cloudflare-turnstile' ),
									$site_name,
									$settings_url
								);
								wp_mail( $admin_email, $subject, $message );
							}
						}
					}
				}
			}
		}

		do_action('cfturnstile_after_check', $response, $results);

		return $results;

	} else {

		return array( 'success' => false );

	}
	
}

/* 
 * Add Turnstile check to a "cfturnstile_log" option
 */
add_action('cfturnstile_after_check', 'cfturnstile_log', 10, 2);
function cfturnstile_log($response, $results) {
	if(get_option('cfturnstile_log_enable')) {
		// Get log
		$cfturnstile_log = get_option('cfturnstile_log');
		if(!$cfturnstile_log) {
			$cfturnstile_log = array();
		}
		// If $results['error_code'] is not set, set it to empty
		if(!isset($results['error_code'])) {
			$results['error_code'] = '';
		}
		// Get Values
		$error_code = $results['error_code'];
		// Success Yes or No
		if($response->success) {
			$success = true;
		} else {
			$success = false;
		}
		// Add to log
		$cfturnstile_log[] = array(
			'date' => date('Y-m-d H:i:s'),
			'success' => $success,
			'error' => $error_code,
			'ip' => cfturnstile_get_ip(),
			'page' => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
		);
		// Max 50
		if(count($cfturnstile_log) > 50) {
			array_shift($cfturnstile_log);
		}
		// Update log
		update_option('cfturnstile_log', $cfturnstile_log);
	}
}

/**
 * Check if form should show Turnstile
 */
function cfturnstile_form_disable($id, $option) {
	if(!empty(get_option($option)) && get_option($option)) {
		$disabled = preg_replace('/\s+/', '', get_option($option));
		$disabled = explode (",",$disabled);
		if(in_array($id, $disabled)) return true;
	}
	return false;
}

/**
 * Create shortcode to display Turnstile widget
 */
add_shortcode('simple-turnstile', 'cfturnstile_shortcode');
add_action('cfturnstile_display_widget', 'cfturnstile_shortcode', 10, 0);
function cfturnstile_shortcode() {
	ob_start();
	echo cfturnstile_field_show('', '');
	$thecontent = ob_get_contents();
	ob_end_clean();
	wp_reset_postdata();
	$thecontent = trim(preg_replace('/\s+/', ' ', $thecontent));
	return $thecontent;
}