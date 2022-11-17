<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_wpforms')) {

  // Check if form should show Turnstile
  function cfturnstile_wpf_form_disable($id) {
    if(!empty(get_option('cfturnstile_wpforms_disable')) && get_option('cfturnstile_wpforms_disable')) {
      $disabled = preg_replace('/\s+/', '', get_option('cfturnstile_wpforms_disable'));
      $disabled = explode (",",$disabled);
      if(in_array($id, $disabled)) return true;
    }
    return false;
  }

	// Get turnstile field: WP Forms
  if(!empty(get_option('cfturnstile_wpforms_pos')) && get_option('cfturnstile_wpforms_pos') == "after") {
    add_action('wpforms_display_submit_after','cfturnstile_field_wpf_form', 10, 1);
  } else {
    add_action('wpforms_display_submit_before','cfturnstile_field_wpf_form', 10, 1);
  }
	function cfturnstile_field_wpf_form($form_data) {
    if(!cfturnstile_wpf_form_disable($form_data['id'])) {
      if(!empty(get_option('cfturnstile_wpforms_pos')) && get_option('cfturnstile_wpforms_pos') == "after") { echo "<br/><br/>"; }
      cfturnstile_field_show('.wpforms-submit', 'turnstileWPFCallback', '', '-wpf-' . $form_data['id']);
    } else {
      return;
    }
  }

	// WP Forms Check
	add_action('wpforms_process_before', 'cfturnstile_wpf_check', 10, 2);
	function cfturnstile_wpf_check($entry, $form_data){
    if(!cfturnstile_wpf_form_disable($form_data['id'])) {
  		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['cf-turnstile-response'] ) ) {
  			$check = cfturnstile_check();
  			$success = $check['success'];
  			if($success != true) {
  				wpforms()->process->errors[ $form_data[ 'id' ] ][ 'header' ] = cfturnstile_failed_message();
  			}
  		} else {
  			wpforms()->process->errors[ $form_data[ 'id' ] ][ 'header' ] = cfturnstile_failed_message();
  		}
    } else {
      return;
    }
	}

}
