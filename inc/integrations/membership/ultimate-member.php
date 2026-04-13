<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get turnstile field: Ultimate Member
if(get_option('cfturnstile_um_login')) { add_action('um_after_login_fields','cfturnstile_field_um_login'); }
if(get_option('cfturnstile_um_register')) { add_action('um_after_register_fields','cfturnstile_field_um_register'); }
if(get_option('cfturnstile_um_password')) { add_action('um_after_password_reset_fields','cfturnstile_field_um_password'); }
function cfturnstile_field_um_login() { cfturnstile_field_show('#um-submit-btn', 'turnstileUMCallback', 'ultimate-member', '-um-login'); }
function cfturnstile_field_um_register() { cfturnstile_field_show('#um-submit-btn', 'turnstileUMCallback', 'ultimate-member', '-um-register'); }
function cfturnstile_field_um_password() { cfturnstile_field_show('#um-submit-btn', 'turnstileUMCallback', 'ultimate-member', '-um-password'); }

// Ultimate Member Check
if(get_option('cfturnstile_um_login')) { add_action( 'um_submit_form_errors_hook_login', 'cfturnstile_um_check', 20, 1 ); }
if(get_option('cfturnstile_um_register')) { add_action( 'um_submit_form_errors_hook__registration', 'cfturnstile_um_check', 20, 1 ); }
if(get_option('cfturnstile_um_password')) { add_action( 'um_reset_password_errors_hook', 'cfturnstile_um_check', 20, 1 ); }
function cfturnstile_um_check( $args ) {

  // Check if already validated (cache-friendly, no PHP session)
  if( cfturnstile_get_verified( 'cfturnstile_login_checked' ) ) {
    cfturnstile_clear_verified( 'cfturnstile_login_checked' );
    return;
  }

  // Whitelisted
  if(cfturnstile_whitelisted()) {
    return;
  }

  // Check
  if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
    $check = cfturnstile_check();
    $success = $check['success'];
    if($success != true) {
      UM()->form()->add_error( 'cfturnstile', cfturnstile_failed_message() );
    } else {
      cfturnstile_set_verified( 'cfturnstile_login_checked' );
  }
  } else {
    UM()->form()->add_error( 'cfturnstile', cfturnstile_failed_message() );
  }

}

// Get Error Message
function cfturnstile_um_error_message() {
  echo '<p style="color: red; font-weight: bold;">' . cfturnstile_failed_message() . '</p>';
}
// Clear verification flag on login
add_action('um_user_login', 'cfturnstile_um_login_clear', 10, 1);
function cfturnstile_um_login_clear($args) { 
	cfturnstile_clear_verified( 'cfturnstile_login_checked' );
}