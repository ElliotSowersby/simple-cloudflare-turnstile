<?php
/**
 * Plugin Name: Simple Cloudflare Turnstile
 * Description: Easily add Cloudflare Turnstile to your WordPress forms. The user-friendly, privacy-preserving CAPTCHA alternative.
 * Version: 1.26.6
 * Author: Elliot Sowersby, RelyWP
 * Author URI: https://www.relywp.com
 * License: GPLv3 or later
 * Text Domain: simple-cloudflare-turnstile
 *
 * WC requires at least: 3.4
 * WC tested up to: 9.1.2
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
 * 
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
 * Enqueue admin scripts
 */
function cfturnstile_admin_script_enqueue() {
	if (isset($_GET['page']) && $_GET['page'] == 'cfturnstile') {
		$defer = get_option('cfturnstile_defer_scripts', 1) ? array('strategy' => 'defer') : array();
		wp_enqueue_script('cfturnstile-admin-js', plugins_url('/js/admin-scripts.js', __FILE__), '', '2.8', true);
		wp_enqueue_style('cfturnstile-admin-css', plugins_url('/css/admin-style.css', __FILE__), array(), '2.9');
		wp_enqueue_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit", array(), '', $defer);
	}
}
add_action('admin_enqueue_scripts', 'cfturnstile_admin_script_enqueue');

/**
 * Include Errors
 */
include(plugin_dir_path(__FILE__) . 'inc/errors.php');

/**
 * If keys are set, load Turnstile
 */
if (!empty(get_option('cfturnstile_key')) && !empty(get_option('cfturnstile_secret'))) {

	/**
	 * Enqueue turnstile scripts and styles
	 */
	add_action("cfturnstile_enqueue_scripts", "cfturnstile_script_enqueue");
	add_action("login_enqueue_scripts", "cfturnstile_script_enqueue");
	function cfturnstile_script_enqueue() {
		$current_theme = wp_get_theme();
		$defer = get_option('cfturnstile_defer_scripts', 1) ? array('strategy' => 'defer') : array();
		/* Turnstile */
		wp_enqueue_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit", array(), null, $defer);
		/* Disable Button */
		if (get_option('cfturnstile_disable_button')) { wp_enqueue_script('cfturnstile-js', plugins_url('/js/disable-submit.js', __FILE__), '', '5.0', $defer); }
		/* WooCommerce */
		if (cft_is_plugin_active('woocommerce/woocommerce.php')) { wp_enqueue_script('cfturnstile-woo-js', plugins_url('/js/integrations/woocommerce.js', __FILE__), array('jquery'), '1.2', $defer); }
		/* WPDiscuz */
		if(cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php')) { wp_enqueue_style('cfturnstile-css', plugins_url('/css/cfturnstile.css', __FILE__), array(), '1.2'); }
		/* Blocksy */
		if ('blocksy' === $current_theme->get('TextDomain')) { wp_enqueue_script('cfturnstile-blocksy-js', plugins_url('/js/integrations/blocksy.js', __FILE__), array(), '1.1', false); }
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
	 * Include Functions
	 */
	include(plugin_dir_path(__FILE__) . 'inc/turnstile.php');

	/**
	 * Include Whitelist
	 */
	include(plugin_dir_path(__FILE__) . 'inc/whitelist.php');

	/**
	 * Include Integrations
	 */
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

		// Include PMP
		if (cft_is_plugin_active('paid-memberships-pro/paid-memberships-pro.php')) {
			include(plugin_dir_path(__FILE__) . 'inc/integrations/ecommerce/pmp.php');
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

		// Clean Login
		if (cft_is_plugin_active('clean-login/clean-login.php')) {
			include(plugin_dir_path(__FILE__) . 'inc/integrations/other/clean-login.php');
		}

	}

}

/**
 * Compatible with HPOS
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );