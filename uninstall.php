<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Include helper functions
require_once(plugin_dir_path(__FILE__) . 'inc/helper-functions.php');

// Include the file containing the cfturnstile_settings_list() function
include(plugin_dir_path(__FILE__) . 'inc/admin/register-settings.php');

// Check if the "cfturnstile_uninstall_remove" option is true
if (cfturnstile_get_option('cfturnstile_uninstall_remove')) {
    // Get registered settings
    $settings = cfturnstile_settings_list();
    // Remove all registered settings
    foreach ($settings as $setting) {
        delete_option($setting);
    }
    // Remove the "cfturnstile_tested" option
    delete_option('cfturnstile_tested');
    // Remove the "cfturnstile_uninstall_remove" option itself
    delete_option('cfturnstile_uninstall_remove');

    // Remove network-wide options (multisite)
    if (is_multisite()) {
        delete_site_option('cfturnstile_tested_network');
        delete_site_option('cfturnstile_migration_version');
    }
}