<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_forminator')) {

    // Check if form should show Turnstile
    function cfturnstile_forminator_form_disable($id) {
        if(!empty(get_option('cfturnstile_forminator_disable')) && get_option('cfturnstile_forminator_disable')) {
            $disabled = preg_replace('/\s+/', '', get_option('cfturnstile_forminator_disable'));
            $disabled = explode (",",$disabled);
            if(in_array($id, $disabled)) return true;
        }
        return false;
    }

	// Get turnstile field: Forminator Forms
	add_filter( 'forminator_render_form_submit_markup', 'cfturnstile_field_forminator_form', 10, 4 );
	function cfturnstile_field_forminator_form( $html, $form_id, $post_id, $nonce ) {

        if(!cfturnstile_forminator_form_disable($form_id)) {

            $unique_id = mt_rand();

            ob_start();
            cfturnstile_field_show('.forminator-button-submit', 'turnstileForminatorCallback', '', '-fmntr-' . $unique_id);
            ?>
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                document.querySelectorAll('.forminator-custom-form').forEach(function(el) {
                    el.addEventListener('submit', function() {
                        if (document.getElementById('cf-turnstile-fmntr-<?php echo $unique_id; ?>')) {
                            setTimeout(function() {
                                turnstile.reset('#cf-turnstile-fmntr-<?php echo $unique_id; ?>');
                            }, 4000);
                        }
                    });
                });
            });
            </script>
            <?php
            $cfturnstile = ob_get_contents();
            ob_end_clean();
            wp_reset_postdata();

            if(!empty(get_option('cfturnstile_forminator_pos')) && get_option('cfturnstile_forminator_pos') == "after") {
                return $html . $cfturnstile;
            } else {
                return $cfturnstile . $html;
            }

        } else {
            return $html;
        }

	}

	// Forminator Forms Check
	add_action('forminator_custom_form_submit_errors', 'cfturnstile_forminator_check', 10, 3);
	function cfturnstile_forminator_check($submit_errors, $form_id, $field_data_array){
        if(!cfturnstile_forminator_form_disable($form_id)) {
            $check = cfturnstile_check();
            $success = $check['success'];
            if($success != true) {
                $submit_errors[]['submit'] = cfturnstile_failed_message();
            }
        }
        return $submit_errors;
	}

}