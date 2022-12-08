<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_forminator')) {

	// Get turnstile field: Forminator Forms
	add_filter( 'forminator_render_form_submit_markup', 'cfturnstile_field_forminator_form', 10, 4 );
	function cfturnstile_field_forminator_form( $html, $form_id, $post_id, $nonce ) {

        $unique_id = mt_rand();

        ob_start();
        cfturnstile_field_show('.forminator-button-submit', 'turnstileforminatorCallback', '', '-fmntr-' . $unique_id);
        $cfturnstile = ob_get_contents();
        ob_end_clean();
        wp_reset_postdata();

        if(!empty(get_option('cfturnstile_forminator_pos')) && get_option('cfturnstile_forminator_pos') == "after") {
            return $html . $cfturnstile;
        } else {
            return $cfturnstile . $html;
        }

	}

	// Forminator Forms Check
	add_action('forminator_custom_form_submit_errors', 'cfturnstile_forminator_check', 10, 3);
	function cfturnstile_forminator_check($submit_errors, $form_id, $field_data_array){
        $check = cfturnstile_check();
        $success = $check['success'];
        if($success != true) {
            $submit_errors[]['submit'] = cfturnstile_failed_message();
        }
        return $submit_errors;
	}

}