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
if(get_option('cfturnstile_um_login')) { add_action( 'um_submit_form_errors_hook_login', 'cfturnstile_um_check', 5, 2 ); }
if(get_option('cfturnstile_um_register')) { add_action( 'um_submit_form_errors_hook__registration', 'cfturnstile_um_check', 20, 2 ); }
if(get_option('cfturnstile_um_password')) { add_action( 'um_reset_password_errors_hook', 'cfturnstile_um_check', 20, 2 ); }
function cfturnstile_um_check( $args, $form_data = array() ) {

  $is_login = cfturnstile_um_is_login_context( $form_data );
  $verified_key = cfturnstile_um_verified_key( $form_data );

  // Check if already validated (cache-friendly, no PHP session)
  // Login only remembers a pass for this request: UM checks the password after this (priority 10),
  // so a stored flag would let one token cover any number of password guesses.
  if ( $is_login ) {
    if ( ! empty( $GLOBALS['cfturnstile_um_login_passed'] ) ) {
      return;
    }
  } elseif ( cfturnstile_get_verified( $verified_key ) ) {
    return;
  }

  // Whitelisted
  if(cfturnstile_whitelisted()) {
    return;
  }

  // Check
  if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
    $check = cfturnstile_check('', 'ultimate-member');
    $success = $check['success'];
    if($success != true) {
      UM()->form()->add_error( 'cfturnstile', cfturnstile_failed_message() );
      if ( $is_login ) {
        cfturnstile_um_block_login_validation();
      }
    } else {
      if ( $is_login ) {
        $GLOBALS['cfturnstile_um_login_passed'] = true;
        $GLOBALS['cfturnstile_um_login_verified'] = true;
      } else {
        cfturnstile_set_verified( $verified_key );
      }
    }
  } else {
    UM()->form()->add_error( 'cfturnstile', cfturnstile_failed_message() );
    if ( $is_login ) {
      cfturnstile_um_block_login_validation();
    }
  }

}

function cfturnstile_um_block_login_validation() {
  remove_action( 'um_submit_form_errors_hook_login', 'um_submit_form_errors_hook_login' );
}

function cfturnstile_um_verified_key( $form_data = array() ) {
  $current_filter = current_filter();

  if ( cfturnstile_um_is_login_context( $form_data ) ) {
    return 'cfturnstile_um_login_checked';
  }

  if ( 'um_submit_form_errors_hook__registration' === $current_filter ) {
    return 'cfturnstile_um_register_checked';
  }

  if ( 'um_reset_password_errors_hook' === $current_filter ) {
    return 'cfturnstile_um_password_checked';
  }

  return 'cfturnstile_um_checked';
}

function cfturnstile_um_is_login_context( $form_data = array() ) {
  if ( 'um_submit_form_errors_hook_login' !== current_filter() ) {
    return false;
  }

  if ( is_array( $form_data ) && isset( $form_data['mode'] ) && 'login' !== $form_data['mode'] ) {
    return false;
  }

  return true;
}

// Skip the WP login check for the authenticate call UM makes while checking the password, once UM's own check has passed in this request.
add_filter( 'cfturnstile_wp_login_checks', 'cfturnstile_um_skip_wp_login_check', 10, 1 );
function cfturnstile_um_skip_wp_login_check( $skip ) {
  if ( $skip ) {
    return $skip;
  }

  if ( ! empty( $GLOBALS['cfturnstile_um_login_verified'] ) && get_option( 'cfturnstile_um_login' ) && doing_action( 'um_submit_form_errors_hook_login' ) ) {
    unset( $GLOBALS['cfturnstile_um_login_verified'] );
    return true;
  }

  return $skip;
}

// Get Error Message
function cfturnstile_um_error_message() {
  echo '<p style="color: red; font-weight: bold;">' . cfturnstile_failed_message() . '</p>';
}
