<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_gravity')) {

  // Create shortcode
  add_shortcode('gravity-simple-turnstile', 'cfturnstile_gravity_shortcode');
  function cfturnstile_gravity_shortcode($atts) {
  	ob_start();
    $unique_id = mt_rand();
    echo '</div><div style="margin-top: 10px; margin-bottom: -25px; padding-bottom: 0;">';
  	echo cfturnstile_field_show('.gform_button', 'turnstileGravityCallback', 'gravity-form-' . $atts['id'], '-gf-' . $unique_id);
    echo "</div><div class='gform_page_footer top_label'>";
  	$thecontent = ob_get_contents();
  	ob_end_clean();
  	wp_reset_postdata();
  	$thecontent = trim(preg_replace('/\s+/', ' ', $thecontent));
  	return $thecontent;
  }

	// Get turnstile field: Gravity Forms
	add_action('gform_submit_button','cfturnstile_field_gravity_form', 10, 2);
	function cfturnstile_field_gravity_form($button, $form) {
    if(!cfturnstile_form_disable($form['id'], 'cfturnstile_gravity_disable')) {
      if(!empty(get_option('cfturnstile_gravity_pos')) && get_option('cfturnstile_gravity_pos') == "after") {
        return $button . do_shortcode('[gravity-simple-turnstile id="'.$form['id'].'"]');
      } else {
        return do_shortcode('[gravity-simple-turnstile id="'.$form['id'].'"]') . $button;
      }
    }
    return $button;
  }

  // Gravity Forms Check
	add_action('gform_pre_submission', 'cfturnstile_gravity_check', 10, 1);
	function cfturnstile_gravity_check($form){
    if(!cfturnstile_form_disable($form['id'], 'cfturnstile_gravity_disable')) {
  		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['cf-turnstile-response'] ) ) {
  			$check = cfturnstile_check();
  			$success = $check['success'];
  			if($success != true) {
          wp_die( '<p><strong>' . esc_html__( 'ERROR:', 'simple-cloudflare-turnstile' ) . '</strong> ' . cfturnstile_failed_message() . '</p>', 'simple-cloudflare-turnstile', array( 'response'  => 403, 'back_link' => 1, ) );
  			}
  		} else {
        wp_die( '<p><strong>' . esc_html__( 'ERROR:', 'simple-cloudflare-turnstile' ) . '</strong> ' . cfturnstile_failed_message() . '</p>', 'simple-cloudflare-turnstile', array( 'response'  => 403, 'back_link' => 1, ) );
  		}
    }
    return $form;
	}

}
