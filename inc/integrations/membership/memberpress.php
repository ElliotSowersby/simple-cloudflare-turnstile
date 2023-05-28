<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get turnstile field: Ultimate Member
if(get_option('cfturnstile_login')) { add_action('mepr-login-form-before-submit','cfturnstile_field_mepr'); }
if(get_option('cfturnstile_mepr_register')) { add_action('mepr-checkout-before-submit','cfturnstile_field_mepr'); }
function cfturnstile_field_mepr() { cfturnstile_field_show('.mepr-submit', 'turnstileMEPRCallback', 'memberpress', '-' . wp_rand()); }

// Ultimate Member Check
if(get_option('cfturnstile_mepr_register')) { add_action( 'mepr-validate-signup', 'cfturnstile_mepr_check', 20, 1 ); }

function cfturnstile_mepr_check( $errors ){

  // Check if already validated
  if(isset($_SESSION['cfturnstile_login_checked']) && wp_verify_nonce( sanitize_text_field($_SESSION['cfturnstile_login_checked']), 'cfturnstile_login_check' )) {
    return;
  }

  if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['cf-turnstile-response'] ) ) {
    $check = cfturnstile_check();
    $success = $check['success'];
    if($success != true) {
        $errors[] = cfturnstile_failed_message();
    } else {
      $nonce = wp_create_nonce( 'cfturnstile_login_check' );
      $_SESSION['cfturnstile_login_checked'] = $nonce;
    }
  } else {
    $errors[] = cfturnstile_failed_message();
  }

  return $errors;

}