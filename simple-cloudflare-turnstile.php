<?php
/**
 * Plugin Name: Simple Cloudflare Turnstile
 * Description: Easily add Cloudflare Turnstile to your WordPress forms. The user-friendly, privacy-preserving CAPTCHA alternative.
 * Version: 1.21.0
 * Author: Elliot Sowersby, RelyWP
 * Author URI: https://www.relywp.com
 * License: GPLv3 or later
 * Text Domain: simple-cloudflare-turnstile
 *
 * WC requires at least: 3.4
 * WC tested up to: 7.8.0
 **/

// Include Admin Files
include(plugin_dir_path(__FILE__) . 'inc/admin/admin-options.php');
include(plugin_dir_path(__FILE__) . 'inc/admin/register-settings.php');

/**
 * On activate redirect to settings page
 */
register_activation_hook(__FILE__, function () {
	add_option('cfturnstile_do_activation_redirect', true);
	add_option('cfturnstile_tested', 'no');
});
add_action('admin_init', 'cfturnstile_settings_redirect');
function cfturnstile_settings_redirect() {
	if (get_option('cfturnstile_do_activation_redirect', false)) {
		delete_option('cfturnstile_do_activation_redirect');
		if(!is_multisite()) {
			exit(wp_redirect("options-general.php?page=cfturnstile"));
		}
	}
}

/**
 * Plugin List - Settings Link
 *
 * @param array $actions
 * @param string $plugin_file
 * @return array
 */
add_filter('plugin_action_links', 'cfturnstile_settings_link_plugin', 10, 5);
function cfturnstile_settings_link_plugin($actions, $plugin_file) {
	static $plugin;
	if (!isset($plugin))
		$plugin = plugin_basename(__FILE__);
	if ($plugin == $plugin_file) {
		$settings = array('settings' => '<a href="options-general.php?page=cfturnstile">' . __('Settings', 'simple-cloudflare-turnstile') . '</a>');
		$actions = array_merge($settings, $actions);
	}
	return $actions;
}

/**
 * Custom "is_plugin_active" function.
 *
 * @param string $plugin
 * @return bool
 */
if ( !function_exists( 'cft_is_plugin_active' ) ) {
	function cft_is_plugin_active( $plugin ) {
		return ( in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || ( function_exists( 'cft_is_plugin_active_for_network' ) && cft_is_plugin_active_for_network( $plugin ) ) );
	}
}

/**
 * Custom "is_plugin_active_for_network" function.
 *
 * @param string $plugin
 * @return bool
 */
if ( !function_exists( 'cft_is_plugin_active_for_network' ) ) {
	function cft_is_plugin_active_for_network( $plugin ) {
		if ( !is_multisite() ) {
			return false;
		}
		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( isset( $plugins[ $plugin ] ) ) {
			return true;
		}
		return false;
	}
}

/**
 * Create turnstile field template.
 *
 * @param int $button_id
 * @param string $callback
 */
function cfturnstile_field_show($button_id = '', $callback = '', $form_name = '', $unique_id = '') {
	do_action("cfturnstile_enqueue_scripts");
	do_action("cfturnstile_before_field", $unique_id);
	$key = esc_attr(get_option('cfturnstile_key'));
	$theme = esc_attr(get_option('cfturnstile_theme'));
	$language = esc_attr(get_option('cfturnstile_language'));
	$appearance = esc_attr(get_option('cfturnstile_appearance', 'always'));
		if(!$language) { $language = 'auto'; }
	$is_checkout = (function_exists('is_checkout') && is_checkout()) ? true : false;
	?>
	<div id="cf-turnstile<?php echo sanitize_text_field($unique_id); ?>"
	class="cf-turnstile" <?php if (get_option('cfturnstile_disable_button')) { ?>data-callback="<?php echo sanitize_text_field($callback); ?>"<?php } ?>
	data-sitekey="<?php echo sanitize_text_field($key); ?>"
	data-theme="<?php echo sanitize_text_field($theme); ?>"
	data-language="<?php echo sanitize_text_field($language); ?>"
	data-retry="auto" data-retry-interval="1000"
	data-action="<?php echo sanitize_text_field($form_name); ?>"
	data-appearance="<?php echo sanitize_text_field($appearance); ?>"
	style="<?php if (!is_page() && !is_single() && !$is_checkout) { ?>margin-left: -15px;<?php } else { ?>margin-left: -2px;<?php } ?>"></div>
	<?php if ($button_id && get_option('cfturnstile_disable_button')) { ?>
	<style><?php echo sanitize_text_field($button_id); ?> { pointer-events: none; opacity: 0.5; }</style>
	<?php } ?>
	<?php if($appearance == 'always') { ?>
	<br/>
	<?php } else { ?>
	<style>#cf-turnstile<?php echo sanitize_text_field($unique_id); ?> iframe { margin-bottom: 15px; }</style>
	<?php } ?>
	<?php
	do_action("cfturnstile_after_field", $unique_id);
}

/**
 * Enqueue admin scripts
 */
function cfturnstile_admin_script_enqueue() {
	if (isset($_GET['page']) && $_GET['page'] == 'cfturnstile') {
		wp_enqueue_script('cfturnstile-admin-js', plugins_url('/js/admin-scripts.js', __FILE__), '', '2.8', true);
		wp_enqueue_style('cfturnstile-admin-css', plugins_url('/css/admin-style.css', __FILE__), array(), '2.9');
		wp_enqueue_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js", array(), '', 'true');
	}
}
add_action('admin_enqueue_scripts', 'cfturnstile_admin_script_enqueue');

/**
 * Compatible with HPOS
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Gets the custom Turnstile failed message
 */
function cfturnstile_failed_message($default = "") {
	if (!$default && !empty(get_option('cfturnstile_error_message')) && get_option('cfturnstile_error_message')) {
		return sanitize_text_field(get_option('cfturnstile_error_message'));
	} else {
		return __('Please verify that you are human.', 'simple-cloudflare-turnstile');
	}
}

/**
 * Gets the official Turnstile error message
 *
 * @param string $code
 * @return string
 */
function cfturnstile_error_message($code) {
	switch ($code) {
		case 'missing-input-secret':
			return __('The secret parameter was not passed.', 'simple-cloudflare-turnstile');
		case 'invalid-input-secret':
			return __('The secret parameter was invalid or did not exist.', 'simple-cloudflare-turnstile');
		case 'missing-input-response':
			return __('The response parameter was not passed.', 'simple-cloudflare-turnstile');
		case 'invalid-input-response':
			return __('The response parameter is invalid or has expired.', 'simple-cloudflare-turnstile');
		case 'bad-request':
			return __('The request was rejected because it was malformed.', 'simple-cloudflare-turnstile');
		case 'timeout-or-duplicate':
			return __('The response parameter has already been validated before.', 'simple-cloudflare-turnstile');
		case 'internal-error':
			return __('An internal error happened while validating the response. The request can be retried.', 'simple-cloudflare-turnstile');
		default:
			return __('There was an error with Turnstile response. Please check your keys are correct.', 'simple-cloudflare-turnstile');
	}
}

if (!empty(get_option('cfturnstile_key')) && !empty(get_option('cfturnstile_secret'))) {

	/**
	 * Enqueue turnstile scripts and styles
	 */
	add_action("cfturnstile_enqueue_scripts", "cfturnstile_script_enqueue");
	add_action("login_enqueue_scripts", "cfturnstile_script_enqueue");
	function cfturnstile_script_enqueue() {
		$current_theme = wp_get_theme();
		/* Turnstile */
		wp_enqueue_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js", array(), null, 'true');
		/* Disable Button */
		if (get_option('cfturnstile_disable_button')) { wp_enqueue_script('cfturnstile-js', plugins_url('/js/disable-submit.js', __FILE__), '', '4.0', false); }
		/* WooCommerce */
		if (cft_is_plugin_active('woocommerce/woocommerce.php')) { wp_enqueue_script('cfturnstile-woo-js', plugins_url('/js/integrations/woocommerce.js', __FILE__), array('jquery'), '1.1', false); }
		/* WPDiscuz */
		if(cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php')) { wp_enqueue_style('cfturnstile-css', plugins_url('/css/cfturnstile.css', __FILE__), array(), '1.2'); }
		/* Blocksy */
		if ('blocksy' === $current_theme->get('TextDomain')) { wp_enqueue_script('cfturnstile-blocksy-js', plugins_url('/js/integrations/blocksy.js', __FILE__), array(), '1.0', false); }
	}

	/**
	 * Force Render Turnstile (Explicitly). This only runs if it failed to load implicitly.
	 */
	add_action("cfturnstile_after_field", "cfturnstile_force_render", 10, 1);
	function cfturnstile_force_render($unique_id = '') {
		$unique_id = sanitize_text_field($unique_id);
		if($unique_id) {
		?>
		<script>document.addEventListener("DOMContentLoaded",(function(){var e=document.getElementById("cf-turnstile<?php echo $unique_id; ?>");setTimeout((function(){e&&e.innerHTML.length<=1&&(turnstile.remove("#cf-turnstile<?php echo $unique_id; ?>"),turnstile.render("#cf-turnstile<?php echo $unique_id; ?>",{sitekey:"<?php echo sanitize_text_field(get_option('cfturnstile_key')); ?>"}))}),125)}));</script>
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

		if (empty($postdata) && isset($_POST['cf-turnstile-response'])) {
			$postdata = sanitize_text_field($_POST['cf-turnstile-response']);
		}

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

	// Performance Plugins Compatibility
	if (cft_is_plugin_active('sg-cachepress/sg-cachepress.php') || cft_is_plugin_active('litespeed-cache/litespeed-cache.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/other/perf.php');
	}

	// Include WordPress
	include(plugin_dir_path(__FILE__) . 'inc/wordpress.php');

	// Include WooCommerce
	if (cft_is_plugin_active('woocommerce/woocommerce.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/ecommerce/woocommerce.php');
	}

	// Include EDD
	if (cft_is_plugin_active('easy-digital-downloads/easy-digital-downloads.php') || cft_is_plugin_active('easy-digital-downloads-pro/easy-digital-downloads.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/ecommerce/edd.php');
	}

	// Include MC4WP
	if (cft_is_plugin_active('mailchimp-for-wp/mailchimp-for-wp.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/newsletters/mc4wp.php');
	}
	
	// Include Contact Form 7
	if (cft_is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/forms/contact-form-7.php');
	}

	// Include WPForms
	if (cft_is_plugin_active('wpforms-lite/wpforms.php') || cft_is_plugin_active('wpforms/wpforms.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/forms/wpforms.php');
	}

	// Include Fluent Forms
	if (cft_is_plugin_active('fluentform/fluentform.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/forms/fluent-forms.php');
	}

	// Include Formidable Forms
	if (cft_is_plugin_active('formidable/formidable.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/forms/formidable.php');
	}

	// Include Forminator Forms
	if (cft_is_plugin_active('forminator/forminator.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/forms/forminator.php');
	}

	// Include Gravity Forms
	if (cft_is_plugin_active('gravityforms/gravityforms.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/forms/gravity-forms.php');
	}

	// Include Buddypress
	if (cft_is_plugin_active('buddypress/bp-loader.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/community/buddypress.php');
	}

	// Include BBPress
	if (cft_is_plugin_active('bbpress/bbpress.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/community/bbpress.php');
	}

	// Include WPDiscuz
	if (cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/community/wpdiscuz.php');
	}

	// Include Elementor Forms
	if ( cft_is_plugin_active('elementor/elementor.php') && cft_is_plugin_active('elementor-pro/elementor-pro.php') ) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/other/elementor.php');
	}
	
	// Include Ultimate Member
	if (cft_is_plugin_active('ultimate-member/ultimate-member.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/membership/ultimate-member.php');
	}

	// Include MemberPress
	if (cft_is_plugin_active('memberpress/memberpress.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/membership/memberpress.php');
	}

	// Include WP-Members
	if (cft_is_plugin_active('wp-members/wp-members.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/membership/wp-members.php');
	}

	// Include WP User Frontend
	if (cft_is_plugin_active('wp-user-frontend/wpuf.php')) {
		include(plugin_dir_path(__FILE__) . 'inc/integrations/membership/wpuf.php');
	}

}
