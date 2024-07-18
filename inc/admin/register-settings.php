<?php
if (!defined('ABSPATH')) {
	exit;
}

/*
* Register settings
*/
function cfturnstile_register_settings() {
    $active_settings = cfturnstile_settings_list();
    foreach ($active_settings as $setting) {
        register_setting('cfturnstile-settings-group', $setting);
    }
}
add_action('admin_init', 'cfturnstile_register_settings');

/*
* Delete inactive settings
*/
function cfturnstile_delete_inactive_settings($value) {
    $all_settings = cfturnstile_settings_list(true);
    $active_settings = cfturnstile_settings_list();
    $inactive_settings = array_diff($all_settings, $active_settings);
    foreach ($inactive_settings as $setting) {
        delete_option($setting);
    }
    return $value;
}
add_action('sanitize_option_cfturnstile_uninstall_remove', 'cfturnstile_delete_inactive_settings');

/*
* Get registered settings
* @param bool $all - get all settings
* @return array
*/
function cfturnstile_settings_list($all = false) {

    $settings = array(
        'cfturnstile_setup',
        'cfturnstile_key',
        'cfturnstile_secret',
        'cfturnstile_theme',
        'cfturnstile_disable_button',
        'cfturnstile_error_message',
        'cfturnstile_defer_scripts',
        'cfturnstile_language',
        'cfturnstile_appearance',
        'cfturnstile_failure_message_enable',
        'cfturnstile_failure_message',
        'cfturnstile_login',
        'cfturnstile_login_only',
        'cfturnstile_register',
        'cfturnstile_register_only',
        'cfturnstile_reset',
        'cfturnstile_comment',
        'cfturnstile_ajax_comments',
        'cfturnstile_whitelist_users',
        'cfturnstile_whitelist_ips',
        'cfturnstile_whitelist_agents',
    );

    $integrations = array(
        'woocommerce/woocommerce.php' => array(
            'cfturnstile_woo_login',
            'cfturnstile_woo_register',
            'cfturnstile_woo_reset',
            'cfturnstile_woo_checkout',
            'cfturnstile_guest_only',
            'cfturnstile_woo_checkout_pos',
            'cfturnstile_selected_payment_methods',
            'cfturnstile_woo_checkout_pay',
        ),
        'easy-digital-downloads/easy-digital-downloads.php' => array(
            'cfturnstile_edd_checkout',
            'cfturnstile_edd_guest_only',
            'cfturnstile_edd_login',
            'cfturnstile_edd_register',
        ),
        'paid-memberships-pro/paid-memberships-pro.php' => array(
            'cfturnstile_pmp_checkout',
            'cfturnstile_pmp_guest_only',
            'cfturnstile_pmp_login',
            'cfturnstile_pmp_register',
        ),
        'contact-form-7/wp-contact-form-7.php' => array(
            'cfturnstile_cf7_all',
        ),
        'wpforms-lite/wpforms.php' => array(
            'cfturnstile_wpforms',
            'cfturnstile_wpforms_pos',
            'cfturnstile_wpforms_disable',
        ),
        'wpforms/wpforms.php' => array(
            'cfturnstile_wpforms',
            'cfturnstile_wpforms_pos',
            'cfturnstile_wpforms_disable',
        ),
        'fluentform/fluentform.php' => array(
            'cfturnstile_fluent',
            'cfturnstile_fluent_disable',
        ),
        'formidable/formidable.php' => array(
            'cfturnstile_formidable',
            'cfturnstile_formidable_pos',
            'cfturnstile_formidable_disable',
        ),
        'forminator/forminator.php' => array(
            'cfturnstile_forminator',
            'cfturnstile_forminator_pos',
            'cfturnstile_forminator_disable',
        ),
        'gravityforms/gravityforms.php' => array(
            'cfturnstile_gravity',
            'cfturnstile_gravity_pos',
            'cfturnstile_gravity_disable',
        ),
        'buddypress/bp-loader.php' => array(
            'cfturnstile_bp_register',
        ),
        'bbpress/bbpress.php' => array(
            'cfturnstile_bbpress_create',
            'cfturnstile_bbpress_reply',
            'cfturnstile_bbpress_guest_only',
            'cfturnstile_bbpress_align',
        ),
        'elementor-pro/elementor-pro.php' => array(
            'cfturnstile_elementor',
            'cfturnstile_elementor_pos',
        ),
        'ultimate-member/ultimate-member.php' => array(
            'cfturnstile_um_login',
            'cfturnstile_um_register',
            'cfturnstile_um_password',
        ),
        'memberpress/memberpress.php' => array(
            'cfturnstile_mepr_login',
            'cfturnstile_mepr_register',
            'cfturnstile_mepr_product_ids',
        ),
        'wp-user-frontend/wpuf.php' => array(
            'cfturnstile_wpuf_register',
            'cfturnstile_wpuf_forms',
        ),
    );

    foreach ($integrations as $plugin => $integration_settings) {
        if ($all || cft_is_plugin_active($plugin)) {
            $settings = array_merge($settings, $integration_settings);
        }
    }

    $settings[] = 'cfturnstile_uninstall_remove'; // Always last

    return $settings;
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