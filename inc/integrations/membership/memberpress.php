<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// MemberPress Login
if(get_option('cfturnstile_login')) { add_action('mepr-login-form-before-submit','cfturnstile_field_mepr'); }
function cfturnstile_field_mepr() { cfturnstile_field_show('.mepr-submit', 'turnstileMEPRCallback', 'memberpress', '-' . wp_rand()); }

// MemberPress Register
if(get_option('cfturnstile_mepr_register')) { add_action('mepr-checkout-before-submit','cfturnstile_field_mepr_register', 10, 1); }
function cfturnstile_field_mepr_register($membership_ID) { 

  $LimitedToProductIDs = get_option('cfturnstile_mepr_product_ids');
  $ProductsNeedingCaptcha = explode("\n", str_replace("\r", "", $LimitedToProductIDs));

  // Only show Turnstile for those specific product ids
  if( in_array( $membership_ID, $ProductsNeedingCaptcha ) || empty($LimitedToProductIDs) ) {
    cfturnstile_field_show(
      '.mepr-submit', 
      'turnstileMEPRCallback', 
      'memberpress', 
      '-' . wp_rand()
    ); 
  }

}

// MemberPress Check
if(get_option('cfturnstile_mepr_register')) { add_filter( 'mepr-validate-signup', 'cfturnstile_mepr_check', 20, 1 ); }
function cfturnstile_mepr_check( $errors ) {

  $LimitedToProductIDs = get_option('cfturnstile_mepr_product_ids');
  $ProductsNeedingCaptcha = explode("\n", str_replace("\r", "", $LimitedToProductIDs));

  // Start session
  if (!session_id()) { session_start(); }

  // Check if already validated
  if(isset($_SESSION['cfturnstile_login_checked']) && wp_verify_nonce( sanitize_text_field($_SESSION['cfturnstile_login_checked']), 'cfturnstile_login_check' )) {
    unset($_SESSION['cfturnstile_login_checked']);
    return $errors;
  }

  // Whitelisted
  if(cfturnstile_whitelisted()) {
    return $errors;
  }

  // Suppress Turnstile on all non-specified product ids
  if( !in_array( $_POST['mepr_product_id'], $ProductsNeedingCaptcha ) && !empty($LimitedToProductIDs) ) {
    return $errors;
  }
  
  // Check Turnstile outcome
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

// Allow auto-login without check, during signup and password reset
function cfturnstile_mepr_allow_auto_login($auto_login) {
  if($auto_login) {
    remove_action('authenticate', 'cfturnstile_wp_login_check', 21, 1);
  }
  return $auto_login;
}
add_filter('mepr-auto-login', 'cfturnstile_mepr_allow_auto_login');