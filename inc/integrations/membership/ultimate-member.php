<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get turnstile field: Ultimate Member
if(get_option('cfturnstile_um_login')) { add_action('um_after_login_fields','cfturnstile_field_um'); }
if(get_option('cfturnstile_um_register')) { add_action('um_after_register_fields','cfturnstile_field_um'); }
if(get_option('cfturnstile_um_password')) { add_action('um_after_password_reset_fields','cfturnstile_field_um'); }
function cfturnstile_field_um() { cfturnstile_field_show('#um-submit-btn', 'turnstileUMCallback', 'ultimate-member', '-' . wp_rand()); }

// Ultimate Member Check
if(get_option('cfturnstile_um_login')) { add_action( 'um_submit_form_errors_hook_login', 'cfturnstile_um_check', 20, 1 ); }
if(get_option('cfturnstile_um_register')) { add_action( 'um_submit_form_errors_hook__registration', 'cfturnstile_um_check', 20, 1 ); }
if(get_option('cfturnstile_um_password')) { add_action( 'um_reset_password_errors_hook', 'cfturnstile_um_check', 20, 1 ); }
function cfturnstile_um_check( $args ) {

  // Check if already validated
  if(isset($_SESSION['cfturnstile_login_checked']) && wp_verify_nonce( sanitize_text_field($_SESSION['cfturnstile_login_checked']), 'cfturnstile_login_check' )) {
    unset($_SESSION['cfturnstile_login_checked']);
    return;
  }

  // Whitelisted
  if(cfturnstile_whitelisted()) {
    return;
  }

  // Check
  if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['cf-turnstile-response'] ) ) {
    $check = cfturnstile_check();
    $success = $check['success'];
    if($success != true) {
      UM()->form()->add_error( 'cfturnstile', cfturnstile_failed_message() );
    } else {
      $nonce = wp_create_nonce( 'cfturnstile_login_check' );
      $_SESSION['cfturnstile_login_checked'] = $nonce;
  }
  } else {
    UM()->form()->add_error( 'cfturnstile', cfturnstile_failed_message() );
  }

}

// Get Error Message
function cfturnstile_um_error_message() {
  echo '<p style="color: red; font-weight: bold;">' . cfturnstile_failed_message() . '</p>';
}
// Clear session on login
add_action('um_user_login', 'cfturnstile_um_login_clear', 10, 1);
function cfturnstile_um_login_clear($args) { 
	if(isset($_SESSION['cfturnstile_login_checked'])) { unset($_SESSION['cfturnstile_login_checked']); }
}