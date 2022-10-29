<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_formidable')) {

	// Get turnstile field: Formidable Forms
	add_action('frm_submit_button_html','cfturnstile_field_formidable_form', 10, 2);
	function cfturnstile_field_formidable_form($button, $args) {
		return cfturnstile_field_show('.frm_forms .frm_button_submit', 'turnstileFormidableCallback') . $button;
	}

	// Formidable Forms Check
	add_action('frm_validate_entry', 'cfturnstile_formidable_check', 10, 2);
	function cfturnstile_formidable_check($errors, $values){
    $check = cfturnstile_check();
    $success = $check['success'];
    if($success != true) {
			$errors['cfturnstile_error'] = cfturnstile_failed_message();
		}
    return $errors;
	}

}
