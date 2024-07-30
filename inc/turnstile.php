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
		// Show Turnstile
		do_action("cfturnstile_enqueue_scripts");
		do_action("cfturnstile_before_field", esc_attr($unique_id));
		$key = sanitize_text_field(get_option('cfturnstile_key'));
		$theme = sanitize_text_field(get_option('cfturnstile_theme'));
		$language = sanitize_text_field(get_option('cfturnstile_language'));
		$appearance = sanitize_text_field(get_option('cfturnstile_appearance', 'always'));
			if(!$language) { $language = 'auto'; }
		?>
		<div id="cf-turnstile<?php echo esc_attr($unique_id); ?>"
		class="cf-turnstile<?php if($class) { echo " " . esc_attr($class); } ?>" <?php if (get_option('cfturnstile_disable_button')) { ?>data-callback="<?php echo esc_attr($callback); ?>"<?php } ?>
		data-sitekey="<?php echo esc_attr($key); ?>"
		data-theme="<?php echo esc_attr($theme); ?>"
		data-language="<?php echo esc_attr($language); ?>"
		data-retry="auto" data-retry-interval="1000"
		data-action="<?php echo esc_attr($form_name); ?>"
		<?php if(get_option('cfturnstile_failure_message_enable')) { ?>
		data-callback="cfturnstileCallback"
		data-error-callback="cfturnstileErrorCallback"
		<?php } ?>
		data-appearance="<?php echo esc_attr($appearance); ?>"></div>
		<?php
		do_action("cfturnstile_after_field", esc_attr($unique_id));
	}
}


/**
 * Add Styles Below Turnstile if Disable Submit Enabled
 *
 * @return bool
 */
add_action('cfturnstile_after_field', 'cfturnstile_disable_button_styles', 10, 1);
function cfturnstile_disable_button_styles($button_id) {
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
	if(get_option('cfturnstile_failure_message_enable')) {
	$failed_text = get_option('cfturnstile_failure_message');
	if(!$failed_text) { $failed_text = esc_html__('Failed to verify you are human. Please contact us if you are having issues.', 'simple-cloudflare-turnstile'); }
	?>
	<div class="cf-turnstile-failed-text<?php echo esc_attr($unique_id); ?>"></div>
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
	$unique_id = sanitize_text_field($unique_id);
	$key = sanitize_text_field(get_option('cfturnstile_key'));
	if($unique_id) {
	?>
	<script>document.addEventListener("DOMContentLoaded",(function(){var e=document.getElementById("cf-turnstile<?php echo esc_html($unique_id); ?>");e&&!e.innerHTML.trim()&&turnstile.render("#cf-turnstile<?php echo esc_html($unique_id); ?>",{sitekey:"<?php echo esc_html($key); ?>"})}));</script>
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

	// Get Turnstile Keys from Settings
	$key = sanitize_text_field(get_option('cfturnstile_key'));
	$secret = sanitize_text_field(get_option('cfturnstile_secret'));

	if ($key && $secret) {

		$headers = array(
			'body' => [
				'secret' => $secret,
				'response' => $postdata
			]
		);
		$verify = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $headers);
		$verify = wp_remote_retrieve_body($verify);
		$response = json_decode($verify);

		if($response->success) {
			$results['success'] = $response->success;
		} else {
			$results['success'] = false;
		}

		foreach ($response as $key => $val) {
			if ($key == 'error-codes') {
				foreach ($val as $key => $error_val) {
					$results['error_code'] = $error_val;
					if($error_val == 'invalid-input-secret') {
						update_option('cfturnstile_tested', 'no'); // Disable if invalid secret
					}
				}
			}
		}

		return $results;

	} else {

		return false;

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
function cfturnstile_shortcode() {
	ob_start();
	echo cfturnstile_field_show('', '');
	$thecontent = ob_get_contents();
	ob_end_clean();
	wp_reset_postdata();
	$thecontent = trim(preg_replace('/\s+/', ' ', $thecontent));
	return $thecontent;
}