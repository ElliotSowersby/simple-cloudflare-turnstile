<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_formidable')) {

  // Check if form should show Turnstile
  function cfturnstile_formidable_form_disable($id) {
    if(!empty(get_option('cfturnstile_formidable_disable')) && get_option('cfturnstile_formidable_disable')) {
      $disabled = preg_replace('/\s+/', '', get_option('cfturnstile_formidable_disable'));
      $disabled = explode (",",$disabled);
      if(in_array($id, $disabled)) return true;
    }
    return false;
  }

	// Get turnstile field: Formidable Forms
	add_action('frm_submit_button_html','cfturnstile_field_formidable_form', 10, 2);
	function cfturnstile_field_formidable_form($button, $args) {

    if(!cfturnstile_formidable_form_disable($args['form']->id)) {

      $unique_id = mt_rand();

    	ob_start();
      cfturnstile_field_show('.frm_forms .frm_button_submit', 'turnstileFormidableCallback', '', '-fmdble-' . $unique_id);
    	$cfturnstile = ob_get_contents();
    	ob_end_clean();
    	wp_reset_postdata();

      if(!empty(get_option('cfturnstile_formidable_pos')) && get_option('cfturnstile_formidable_pos') == "after") {
  		  return $button . $cfturnstile;
      } else {
        return $cfturnstile . $button;
      }

    } else {

      return $button;

    }

	}

	// Formidable Forms Check
	add_action('frm_validate_entry', 'cfturnstile_formidable_check', 10, 2);
	function cfturnstile_formidable_check($errors, $values){
    if(!cfturnstile_formidable_form_disable($values['form_id'])) {
      $check = cfturnstile_check();
      $success = $check['success'];
      if($success != true) {
  			$errors['cfturnstile_error'] = cfturnstile_failed_message();
  		}
    }
    return $errors;
	}

}
