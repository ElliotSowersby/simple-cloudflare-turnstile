<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get turnstile field: Ultimate Member
if(get_option('cfturnstile_um_login')) { add_action('um_after_login_fields','cfturnstile_field_um'); }
if(get_option('cfturnstile_um_register')) { add_action('um_after_register_fields','cfturnstile_field_um'); }
if(get_option('cfturnstile_um_password')) { add_action('um_after_password_reset_fields','cfturnstile_field_um'); }
function cfturnstile_field_um() { cfturnstile_field_show('#um-submit-btn', 'turnstileUMCallback', 'ultimate-member', '-' . mt_rand()); }

// Ultimate Member Check
if(get_option('cfturnstile_um_login')) { add_action( 'um_submit_form_errors_hook_login', 'cfturnstile_um_check', 20, 1 ); }
if(get_option('cfturnstile_um_register')) { add_action( 'um_submit_form_errors_hook__registration', 'cfturnstile_um_check', 20, 1 ); }
if(get_option('cfturnstile_um_password')) { add_action( 'um_reset_password_errors_hook', 'cfturnstile_um_check', 20, 1 ); }
function cfturnstile_um_check( $args ){
  $mode = $args['mode'];
  if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['cf-turnstile-response'] ) ) {
    $check = cfturnstile_check();
    $success = $check['success'];
    if($success != true) {
      cfturnstile_um_error($mode);
    }
  } else {
    cfturnstile_um_error($mode);
  }
}

// Get Error Message
function cfturnstile_um_error_message() {
  echo '<p style="color: red; font-weight: bold;">' . cfturnstile_failed_message() . '</p>';
}

// Show Error Message
function cfturnstile_um_error($mode) {
  if ( $mode == 'login' ) {
    UM()->form()->add_error( 'cfturnstile', '' );
    add_action('um_after_login_fields','cfturnstile_um_error_message');
  }
  if ( $mode == 'register' ){
    UM()->form()->add_error( 'cfturnstile', '' );
    add_action('um_after_register_fields','cfturnstile_um_error_message');
  }
  if ( $mode == 'password' ){
    UM()->form()->add_error( 'cfturnstile', '' );
    add_action('um_after_password_reset_fields','cfturnstile_um_error_message');
  }
}
