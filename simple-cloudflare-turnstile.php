<?php
/**
 * Plugin Name: Simple CAPTCHA with Cloudflare Turnstile
 * Description: Easily add Cloudflare Turnstile to your WordPress forms. The user-friendly, privacy-preserving CAPTCHA alternative.
 * Version: 1.43.0
 * Author: Elliot Sowersby, RelyWP
 * Author URI: https://www.relywp.com
 * License: GPLv3 or later
 * Text Domain: simple-cloudflare-turnstile
 *
 * WC requires at least: 3.4
 * WC tested up to: 10.9
 **/

// Include Admin Files
include_once(plugin_dir_path(__FILE__) . 'inc/admin/admin-options.php');
include_once(plugin_dir_path(__FILE__) . 'inc/admin/register-settings.php');
include_once(plugin_dir_path(__FILE__) . 'inc/admin/analytics-tab.php');
include_once(plugin_dir_path(__FILE__) . 'inc/admin/export-tab.php');
include_once(plugin_dir_path(__FILE__) . 'inc/admin/export-import.php');
include_once(plugin_dir_path(__FILE__) . 'inc/config-keys.php');

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
 * URL of the Turnstile API script.
 *
 * @return string
 */
function cfturnstile_api_url() {
	return 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=cfturnstileOnload';
}

/**
 * Widget render queue, drained by Cloudflare's onload callback. Also exposes
 * window.cfturnstileOpts(), which maps a widget's data-*-callback attributes to functions.
 *
 * @return string
 */
function cfturnstile_api_bootstrap() {
	return '(function(w,d){var q=w.cfturnstileQueue=w.cfturnstileQueue||[];if(w.cfturnstileRender)return;var ready=false,hooked=false,ticks=0,timer=null;var cbs=["callback","error-callback","expired-callback","timeout-callback","unsupported-callback","before-interactive-callback","after-interactive-callback"];function opts(e){var p={};for(var i=0;i<cbs.length;i++){(function(n){var v=e.getAttribute("data-"+n);if(!v)return;p[n]=function(){var f=w[v];if(typeof f==="function")return f.apply(w,arguments);};})(cbs[i]);}return p;}w.cfturnstileOpts=function(e){if(typeof e==="string")e=d.querySelector(e);return e&&e.getAttribute?opts(e):{};};function one(id){var e=d.getElementById("cf-turnstile"+id);if(!e)return false;if(e.firstElementChild)return true;try{w.turnstile.render(e,opts(e));return true;}catch(_){return false;}}function drain(){if(!ready)return;for(var i=q.length-1;i>=0;i--){if(one(q[i]))q.splice(i,1);}if(q.length&&!hooked&&d.readyState==="loading"){hooked=true;d.addEventListener("DOMContentLoaded",function(){hooked=false;drain();});}}function watch(){timer=null;if(!q.length)return;if(!ready&&w.turnstile&&typeof w.turnstile.render==="function")ready=true;drain();if(q.length&&++ticks<170)timer=setTimeout(watch,ticks<20?100:2000);}w.cfturnstileRender=function(){drain();if(q.length&&!timer)timer=setTimeout(watch,100);};w.cfturnstileOnload=function(){ready=true;w.cfturnstileRender();};w.cfturnstileRender();})(window,document);';
}

/**
 * Reset a widget one second after its form submits without leaving the page (AJAX/SPA forms),
 * so a retry gets a fresh token instead of re-posting the spent one.
 *
 * Skipped while a 2FA prompt is open, when the token has already changed, and for forms that
 * resubmit themselves after async work (the WooCommerce checkout, where card gateways intercept
 * Place Order and woocommerce.js handles resets). Filterable via cfturnstile_token_refresh_skip_forms.
 *
 * @return string
 */
function cfturnstile_token_refresh() {
	$skip_forms = apply_filters( 'cfturnstile_token_refresh_skip_forms', 'form.checkout, form.woocommerce-checkout' );
	$skip_forms = esc_js( (string) $skip_forms );
	return '(function(w,d){if(w.cfturnstileRefresh)return;w.cfturnstileRefresh=1;var F="input[name=cf-turnstile-response]",S="' . $skip_forms . '",u=false;function go(){u=true;}w.addEventListener("pagehide",go);w.addEventListener("beforeunload",go);d.addEventListener("submit",function(e){var f=e.target,s=[];if(!f||!f.querySelectorAll)return;try{if(S&&f.matches&&f.matches(S))return;}catch(_){}f.querySelectorAll(".cf-turnstile").forEach(function(el){var i=el.querySelector(F);if(i&&i.value)s.push([el,i.value]);});if(!s.length)return;setTimeout(function(){if(u||!w.turnstile||d.querySelector("#wfls-prompt-overlay,#wfls-token,#fls_2fa_form,.fls_2fs"))return;s.forEach(function(x){var i=x[0].querySelector(F);if(i&&i.value===x[1]){try{w.turnstile.reset(x[0]);}catch(_){}}});},2000);},true);})(window,document);';
}

/**
 * Register the Turnstile API script with its bootstrap attached 'before', so the onload
 * callback exists before the API can call it. Attached even if another plugin registered the handle.
 *
 * @param array|bool $args Script args, as accepted by wp_register_script().
 */
function cfturnstile_register_api($args = array()) {
	static $bootstrapped = false;

	if ( ! wp_script_is('cfturnstile', 'registered') ) {
		wp_register_script('cfturnstile', cfturnstile_api_url(), array(), null, $args);
	}

	if ( ! $bootstrapped ) {
		$bootstrapped = true;
		wp_add_inline_script('cfturnstile', cfturnstile_api_bootstrap(), 'before');
		wp_add_inline_script('cfturnstile', cfturnstile_token_refresh(), 'before');
	}
}

/**
 * Enqueue admin scripts
 */
function cfturnstile_admin_script_enqueue() {
	if (isset($_GET['page']) && $_GET['page'] == 'cfturnstile') {
		$defer = get_option('cfturnstile_defer_scripts', 1) ? array('strategy' => 'defer') : array();
		wp_enqueue_script('cfturnstile-admin-js', plugins_url('/js/admin-scripts.js', __FILE__), '', '2.10', true);
		wp_enqueue_style('cfturnstile-admin-css', plugins_url('/css/admin-style.css', __FILE__), array(), '2.12');
		// Load Turnstile API without defer on the settings page for reliable admin test rendering
		cfturnstile_register_api(array());
		wp_enqueue_script('cfturnstile');
	}
}
add_action('admin_enqueue_scripts', 'cfturnstile_admin_script_enqueue');

// Include Errors
include_once(plugin_dir_path(__FILE__) . 'inc/errors.php');
// Resource hints for Turnstile
include_once(plugin_dir_path(__FILE__) . 'inc/integrations/other/resource-hints.php');

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
		// Get current theme
		$current_theme = wp_get_theme();
		// Load in the footer
		$script_args = array('in_footer' => true);
		// Defer loading if enabled in settings
		if ( get_option('cfturnstile_defer_scripts', 1) ) {
			$script_args['strategy'] = 'defer';
		}
		/* Turnstile */
		if ( !wp_script_is('cfturnstile', 'enqueued') ) {
			cfturnstile_register_api($script_args);
			wp_enqueue_script('cfturnstile');
		}
		/* Disable Button / Login Submit Block */
		if ( (get_option('cfturnstile_disable_button') || get_option('cfturnstile_login')) && !wp_script_is('cfturnstile-js', 'enqueued') ) { wp_enqueue_script('cfturnstile-js', plugins_url('/js/disable-submit.js', __FILE__), array('cfturnstile'), '5.3', $script_args); }
		/* Interaction Only / Execute Helper (toggles widget label and spacer when the widget is visible) */
		if ( get_option('cfturnstile_appearance', 'always') !== 'always' && !wp_script_is('cfturnstile-label-js', 'enqueued') ) { wp_enqueue_script('cfturnstile-label-js', plugins_url('/js/interaction-label.js', __FILE__), array(), '1.1', $script_args); }
		/* WooCommerce */
		if ( cft_is_plugin_active('woocommerce/woocommerce.php') && !wp_script_is('cfturnstile-woo-js', 'enqueued') ) { wp_enqueue_script('cfturnstile-woo-js', plugins_url('/js/integrations/woocommerce.js', __FILE__), array('jquery', 'cfturnstile', 'wp-data'), '2.0', $script_args); }
		/* WPDiscuz */
		if ( cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php') && !wp_style_is('cfturnstile-css', 'enqueued') ) { wp_enqueue_style('cfturnstile-css', plugins_url('/css/cfturnstile.css', __FILE__), array(), '1.2'); }
		/* Blocksy - match child themes too, whose style.css usually declares no text domain of its own */
		$is_blocksy = ( 'blocksy' === $current_theme->get('TextDomain') || 'blocksy' === $current_theme->get_template() );
		if ( $is_blocksy && !wp_script_is('cfturnstile-blocksy-js', 'enqueued') ) { wp_enqueue_script('cfturnstile-blocksy-js', plugins_url('/js/integrations/blocksy.js', __FILE__), array('cfturnstile'), '1.3', $script_args); }
		/* Custom Hook for Integrations */
		do_action("cfturnstile_enqueue_scripts_custom");
	}

	/**
	 * Add data-cfasync="false" to Turnstile script tag
	 */
	function cfturnstile_add_data_attribute($tag, $handle) {
		if ('cfturnstile' === $handle) {
			$tag = str_replace("src='", "data-cfasync='false' src='", $tag);
		}
		return $tag;
	}
	add_filter('script_loader_tag', 'cfturnstile_add_data_attribute', 10, 2);

	/**
	 * Add data-cfasync="false" to our inline scripts too, so Rocket Loader leaves them alone.
	 */
	function cfturnstile_add_inline_data_attribute($attributes) {
		if ( isset($attributes['id']) && 0 === strpos($attributes['id'], 'cfturnstile') ) {
			$attributes['data-cfasync'] = 'false';
		}
		return $attributes;
	}
	add_filter('wp_inline_script_attributes', 'cfturnstile_add_inline_data_attribute');

	/**
	 * Include Functions
	 */
	include_once(plugin_dir_path(__FILE__) . 'inc/failsafe.php');
	include_once(plugin_dir_path(__FILE__) . 'inc/verification.php');
	include_once(plugin_dir_path(__FILE__) . 'inc/turnstile.php');

	/**
	 * Include Whitelist
	 */
	include_once(plugin_dir_path(__FILE__) . 'inc/whitelist.php');

	/**
	 * Include Analytics
	 */
	include_once(plugin_dir_path(__FILE__) . 'inc/analytics.php');

	/**
	 * Include Integrations
	 */
	if(empty(get_option('cfturnstile_tested')) || get_option('cfturnstile_tested') == 'yes') {

		// Performance Plugins Compatibility
		if ( get_option('cfturnstile_perf_compat', 1) ) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/other/perf.php');
		}
		
		// Include WordPress
		include_once(plugin_dir_path(__FILE__) . 'inc/wordpress.php');

		// Include WooCommerce
		if (cft_is_plugin_active('woocommerce/woocommerce.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/ecommerce/woocommerce.php');
		}

		// Include EDD
		if (cft_is_plugin_active('easy-digital-downloads/easy-digital-downloads.php') || cft_is_plugin_active('easy-digital-downloads-pro/easy-digital-downloads.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/ecommerce/edd.php');
		}

		// Include PMP
		if (cft_is_plugin_active('paid-memberships-pro/paid-memberships-pro.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/ecommerce/pmp.php');
		}

		// Include Sunshine Photo Cart
		if (cft_is_plugin_active('sunshine-photo-cart/sunshine-photo-cart.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/ecommerce/sunshine-photo-cart.php');
		}

		// Include MC4WP
		if (cft_is_plugin_active('mailchimp-for-wp/mailchimp-for-wp.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/newsletters/mc4wp.php');
		}

		// Include MailPoet
		if (cft_is_plugin_active('mailpoet/mailpoet.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/newsletters/mailpoet.php');
		}
		
		// Include Contact Form 7
		if (cft_is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/forms/contact-form-7.php');
		}

		// Include WPForms
		if (cft_is_plugin_active('wpforms-lite/wpforms.php') || cft_is_plugin_active('wpforms/wpforms.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/forms/wpforms.php');
		}

		// Include Fluent Forms
		if (cft_is_plugin_active('fluentform/fluentform.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/forms/fluent-forms.php');
		}

		// Include SureForms
		if (cft_is_plugin_active('sureforms/sureforms.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/forms/sureforms.php');
		}

		// Include Formidable Forms
		if (cft_is_plugin_active('formidable/formidable.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/forms/formidable.php');
		}

		// Include Forminator Forms
		if (cft_is_plugin_active('forminator/forminator.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/forms/forminator.php');
		}

		// Include Gravity Forms
		if (cft_is_plugin_active('gravityforms/gravityforms.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/forms/gravity-forms.php');
		}

		// Include Buddypress
		if (cft_is_plugin_active('buddypress/bp-loader.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/community/buddypress.php');
		}

		// Include BBPress
		if (cft_is_plugin_active('bbpress/bbpress.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/community/bbpress.php');
		}

		// Include WPDiscuz
		if (cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/community/wpdiscuz.php');
		}

		// Include Elementor Forms
		if ( cft_is_plugin_active('elementor-pro/elementor-pro.php') || cft_is_plugin_active('pro-elements/pro-elements.php') ) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/other/elementor.php');
		}

		// Include Kadence
		if (cft_is_plugin_active('kadence-blocks/kadence-blocks.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/forms/kadence.php');
		}

		// Include Ultimate Member
		if (cft_is_plugin_active('ultimate-member/ultimate-member.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/membership/ultimate-member.php');
		}

		// Include MemberPress
		if (cft_is_plugin_active('memberpress/memberpress.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/membership/memberpress.php');
		}

		// Include WP-Members
		if (cft_is_plugin_active('wp-members/wp-members.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/membership/wp-members.php');
		}

		// Include WP User Frontend
		if (cft_is_plugin_active('wp-user-frontend/wpuf.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/membership/wpuf.php');
		}

		// WP User Manager
		if (cft_is_plugin_active('wp-user-manager/wp-user-manager.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/membership/wp-user-manager.php');
		}

		// Simple Membership
		if (cft_is_plugin_active('simple-membership/simple-wp-membership.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/membership/simple-membership.php');
		}

		// Clean Login
		if (cft_is_plugin_active('clean-login/clean-login.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/other/clean-login.php');
		}

		// Fluent Security (FluentAuth)
		if (cft_is_plugin_active('fluent-security/fluent-security.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/other/fluent-auth.php');
		}

		// Wordfence / Wordfence Login Security (passkey login)
		if (cft_is_plugin_active('wordfence/wordfence.php') || cft_is_plugin_active('wordfence-login-security/wordfence-login-security.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/other/wordfence.php');
		}

		// Jetpack Forms
		if (cft_is_plugin_active('jetpack/jetpack.php')) {
			include_once(plugin_dir_path(__FILE__) . 'inc/integrations/forms/jetpack.php');
		}
	}

}

/**
 * Compatible with HPOS
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'product_block_editor', __FILE__, true );
	}
});