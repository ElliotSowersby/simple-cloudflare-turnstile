<?php
// Register Settings
function cfturnstile_register_settings() {
    $settings = cfturnstile_settings_list();
    foreach ($settings as $setting) {
        register_setting('cfturnstile-settings-group', $setting);
    }
}

// List of settings
function cfturnstile_settings_list() {
    return array(
        'cfturnstile_setup',
        'cfturnstile_key',
        'cfturnstile_secret',
        'cfturnstile_uninstall_remove',
        'cfturnstile_theme',
        'cfturnstile_disable_button',
        'cfturnstile_error_message',
        'cfturnstile_language',
        'cfturnstile_login',
        'cfturnstile_register',
        'cfturnstile_reset',
        'cfturnstile_comment',
        'cfturnstile_woo_checkout',
        'cfturnstile_selected_payment_methods',
        'cfturnstile_guest_only',
        'cfturnstile_woo_checkout_pos',
        'cfturnstile_woo_checkout_pay',
        'cfturnstile_woo_login',
        'cfturnstile_woo_register',
        'cfturnstile_woo_reset',
        'cfturnstile_edd_checkout',
        'cfturnstile_edd_guest_only',
        'cfturnstile_edd_login',
        'cfturnstile_edd_register',
        'cfturnstile_bp_register',
        'cfturnstile_cf7_all',
        'cfturnstile_wpforms',
        'cfturnstile_wpforms_pos',
        'cfturnstile_wpforms_disable',
        'cfturnstile_gravity',
        'cfturnstile_gravity_pos',
        'cfturnstile_gravity_disable',
        'cfturnstile_fluent',
        'cfturnstile_fluent_disable',
        'cfturnstile_formidable',
        'cfturnstile_formidable_pos',
        'cfturnstile_formidable_disable',
        'cfturnstile_forminator',
        'cfturnstile_forminator_pos',
        'cfturnstile_forminator_disable',
        'cfturnstile_elementor',
        'cfturnstile_elementor_pos',
        'cfturnstile_um_login',
        'cfturnstile_um_register',
        'cfturnstile_um_password',
        'cfturnstile_bbpress_create',
        'cfturnstile_bbpress_reply',
        'cfturnstile_bbpress_guest_only',
        'cfturnstile_bbpress_align'
    );
}

// Hook the cfturnstile_register_settings function to the admin_init action
add_action('admin_init', 'cfturnstile_register_settings');