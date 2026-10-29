<?php
if(!defined('ABSPATH')) {
    exit;
}

// Clean Login: Login (logins go through wp_signon(), so the WordPress login check verifies them)
if(get_option('cfturnstile_login')) {
    add_action('cleanlogin_after_login_form', 'cfturnstile_field_login');
}

// Clean Login: Register (Clean Login calls wp_create_user(), so the WordPress register check never runs)
if(get_option('cfturnstile_register')) {
    add_action('cleanlogin_after_register_form', 'cfturnstile_field_register');
    add_filter('clean_login_valid_email', 'cfturnstile_clean_login_register_check', 10, 2);
    function cfturnstile_clean_login_register_check($invalid, $email) {
        // Runs just before wp_create_user(). Returning true rejects the registration.
        if($invalid) { return $invalid; }
        if(!isset($_POST['action']) || 'register' !== sanitize_text_field(wp_unslash($_POST['action']))) { return $invalid; }
        $check = cfturnstile_check('', 'wordpress-register');
        $success = isset($check['success']) ? $check['success'] : false;
        if($success != true) {
            return true;
        }
        return $invalid;
    }
}
