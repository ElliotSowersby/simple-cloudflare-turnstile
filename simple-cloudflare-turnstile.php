<?php
/**
 * Plugin Name: Simple Cloudflare Turnstile
 * Description: Easily add Cloudflare Turnstile to your WordPress forms. The user-friendly, privacy-preserving CAPTCHA alternative.
 * Version: 1.23.3
 * Author: Elliot Sowersby, RelyWP
 * Author URI: https://www.relywp.com
 * License: GPLv3 or later
 * Text Domain: simple-cloudflare-turnstile
 *
 * WC requires at least: 3.4
 * WC tested up to: 8.1.1
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
		$settings = array('settings' => '<a href="options-general.php?page=cfturnstile">' . esc_html__('Settings', 'simple-cloudflare-turnstile') . '</a>');
		$actions = array_merge($settings, $actions);
	}
	return $actions;
}

/**
 * Create turnstile field template.
 *
 * @param int $button_id
 * @param string $callback
 */
function cfturnstile_field_show($button_id = '', $callback = '', $form_name = '', $unique_id = '', $class = '') {
	if(!cfturnstile_whitelisted()) {
		do_action("cfturnstile_enqueue_scripts");
		do_action("cfturnstile_before_field", esc_attr($unique_id));
		$key = sanitize_text_field(get_option('cfturnstile_key'));
		$theme = sanitize_text_field(get_option('cfturnstile_theme'));
		$language = sanitize_text_field(get_option('cfturnstile_language'));
		$appearance = sanitize_text_field(get_option('cfturnstile_appearance', 'always'));
			if(!$language) { $language = 'auto'; }
		$is_checkout = (function_exists('is_checkout') && is_checkout()) ? true : false;
		?>
		<div id="cf-turnstile<?php echo esc_attr($unique_id); ?>"
		class="cf-turnstile<?php if($class) { echo " " . esc_attr($class); } ?>" <?php if (get_option('cfturnstile_disable_button')) { ?>data-callback="<?php echo esc_attr($callback); ?>"<?php } ?>
		data-sitekey="<?php echo esc_attr($key); ?>"
		data-theme="<?php echo esc_attr($theme); ?>"
		data-language="<?php echo esc_attr($language); ?>"
		data-retry="auto" data-retry-interval="1000"
		data-action="<?php echo esc_attr($form_name); ?>"
		data-appearance="<?php echo esc_attr($appearance); ?>"></div>
		<?php if ($button_id && get_option('cfturnstile_disable_button')) { ?>
		<style><?php echo esc_html($button_id); ?> { pointer-events: none; opacity: 0.5; }</style>
		<?php } ?>
		<?php if($appearance == 'always') { ?>
		<br class="cf-turnstile-br cf-turnstile-br<?php echo esc_attr($unique_id); ?>">
		<?php } else { ?>
		<style>#cf-turnstile<?php echo esc_html($unique_id); ?> iframe { margin-bottom: 15px; }</style>
		<?php } ?>
		<?php
		if ((!is_page() && !is_single() && !$is_checkout) || strpos($_SERVER['PHP_SELF'], 'wp-login.php') !== false) {
			?>
			<style>#cf-turnstile<?php echo esc_html($unique_id); ?> { margin-left: -15px; }</style>
			<?php
		}
		do_action("cfturnstile_after_field", esc_attr($unique_id));
	}
}

/**
 * Enqueue admin scripts
 */
function cfturnstile_admin_script_enqueue() {
	if (isset($_GET['page']) && $_GET['page'] == 'cfturnstile') {
		wp_enqueue_script('cfturnstile-admin-js', plugins_url('/js/admin-scripts.js', __FILE__), '', '2.8', true);
		wp_enqueue_style('cfturnstile-admin-css', plugins_url('/css/admin-style.css', __FILE__), array(), '2.9');
		wp_enqueue_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit", array(), '', array('strategy' => 'defer'));
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
		return esc_html__('Please verify that you are human.', 'simple-cloudflare-turnstile');
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
			return esc_html__('The secret parameter was not passed.', 'simple-cloudflare-turnstile');
		case 'invalid-input-secret':
			return esc_html__('The secret parameter was invalid or did not exist.', 'simple-cloudflare-turnstile');
		case 'missing-input-response':
			return esc_html__('The response parameter was not passed.', 'simple-cloudflare-turnstile');
		case 'invalid-input-response':
			return esc_html__('The response parameter is invalid or has expired.', 'simple-cloudflare-turnstile');
		case 'bad-request':
			return esc_html__('The request was rejected because it was malformed.', 'simple-cloudflare-turnstile');
		case 'timeout-or-duplicate':
			return esc_html__('The response parameter has already been validated before.', 'simple-cloudflare-turnstile');
		case 'internal-error':
			return esc_html__('An internal error happened while validating the response. The request can be retried.', 'simple-cloudflare-turnstile');
		default:
			return esc_html__('There was an error with Turnstile response. Please check your keys are correct.', 'simple-cloudflare-turnstile');
	}
}

/**
 * Display error notice if Turnstile is not showing on forms
 */
add_action('admin_notices', 'cfturnstile_tested_notice');
function cfturnstile_tested_notice() {
	if(!isset($_GET['page']) || $_GET['page'] != 'cfturnstile') {
		if (!empty(get_option('cfturnstile_key')) && !empty(get_option('cfturnstile_secret'))) {
			// Get the option from the database
			$cfturnstile_tested = get_option('cfturnstile_tested');
			
			// If the option is 'no', display the error notice
			if ($cfturnstile_tested === 'no') {
				echo '<div class="notice notice-error is-dismissible">';
				echo sprintf(
					__('<p>Cloudflare Turnstile is not currently showing on your forms. Please test the API response on the <a href="%s">settings page</a>.</p>', 'simple-cloudflare-turnstile'),
					admin_url('options-general.php?page=cfturnstile')
				);
				echo '</div>';
			}
		}
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
		wp_enqueue_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit", array(), null, array('strategy' => 'defer'));
		/* Disable Button */
		if (get_option('cfturnstile_disable_button')) { wp_enqueue_script('cfturnstile-js', plugins_url('/js/disable-submit.js', __FILE__), '', '4.0', array('strategy' => 'defer')); }
		/* WooCommerce */
		if (cft_is_plugin_active('woocommerce/woocommerce.php')) { wp_enqueue_script('cfturnstile-woo-js', plugins_url('/js/integrations/woocommerce.js', __FILE__), array('jquery'), '1.2', array('strategy' => 'defer')); }
		/* WPDiscuz */
		if(cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php')) { wp_enqueue_style('cfturnstile-css', plugins_url('/css/cfturnstile.css', __FILE__), array(), '1.2'); }
		/* Blocksy */
		if ('blocksy' === $current_theme->get('TextDomain')) { wp_enqueue_script('cfturnstile-blocksy-js', plugins_url('/js/integrations/blocksy.js', __FILE__), array(), '1.0', false); }
	}

	/**
	 * Add data-cfasync="false" to Turnstile script tag
	 */
	function add_data_attribute($tag, $handle) {
		if ('cfturnstile' === $handle) {
			$tag = str_replace("src='", "data-cfasync='false' src='", $tag);
		}
		return $tag;
	}
	add_filter('script_loader_tag', 'add_data_attribute', 10, 2);
	
	/**
	 * Force Render Turnstile (Explicitly). This only runs if it failed to load implicitly.
	 */
	add_action("cfturnstile_after_field", "cfturnstile_force_render", 10, 1);
	function cfturnstile_force_render($unique_id = '') {
		$unique_id = sanitize_text_field($unique_id);
		if($unique_id) {
		?>
		<script>document.addEventListener("DOMContentLoaded",(function(){var e=document.getElementById("cf-turnstile<?php echo esc_html($unique_id); ?>");setTimeout((function(){e&&(turnstile.render("#cf-turnstile<?php echo esc_html($unique_id); ?>",{sitekey:"<?php echo esc_html(get_option('cfturnstile_key')); ?>"}))}),100)}));</script>
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

		if(cfturnstile_whitelisted()) {
			$results['success'] = true;
			return $results;
		}

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

	// Include Whitelist
	include(plugin_dir_path(__FILE__) . 'inc/whitelist.php');

	// Include Integrations
	if(empty(get_option('cfturnstile_tested')) || get_option('cfturnstile_tested') == 'yes') {

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
		if ( cft_is_plugin_active('elementor-pro/elementor-pro.php') ) {
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

}
