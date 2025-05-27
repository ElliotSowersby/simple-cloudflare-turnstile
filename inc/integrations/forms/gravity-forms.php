<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_gravity')) {

  // Create shortcode
  add_shortcode('gravity-simple-turnstile', 'cfturnstile_gravity_shortcode');
  function cfturnstile_gravity_shortcode($atts) {
  	ob_start();
    $unique_id = wp_rand();
    $form_id = sanitize_text_field(esc_html($atts['id']));
    echo '<div class="gf-turnstile-container">';
  	echo cfturnstile_field_show('.gform_button', 'turnstileGravityCallback', 'gravity-form-' . esc_html($form_id), '-gf-' . esc_html($form_id));
    echo "</div>";
    ?>
    <style>
    .gf-turnstile-container { width: 100%; }
    .gform_footer.top_label { display: flex; flex-wrap: wrap; }
    </style>
    <script>document.addEventListener("DOMContentLoaded", function() {document.addEventListener('gform/post_render', function handlePostRender(event) {if (event.detail.formId !== <?php echo $form_id; ?>) {return;}gform.utils.addAsyncFilter('gform/submission/pre_submission', async function handlePreSubmission(data) {document.addEventListener('gform/post_render', function rerenderTurnstile(event) {if (event.detail.formId !== <?php echo $form_id; ?>) {return;}const turnstileElement = document.getElementById('cf-turnstile-gf-<?php echo esc_html($form_id); ?>');if (turnstileElement) {turnstile.remove('#cf-turnstile-gf-<?php echo esc_html($form_id); ?>');turnstile.render('#cf-turnstile-gf-<?php echo esc_html($form_id); ?>');}document.removeEventListener('gform/post_render', rerenderTurnstile);});gform.utils.removeFilter('gform/submission/pre_submission', handlePreSubmission);return data;});document.removeEventListener('gform/post_render', handlePostRender);});});</script>
    <?php
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
  add_action('gform_validation', 'cfturnstile_gravity_check', 10, 4);

  function cfturnstile_gravity_check($validation_result)
  {
    $form = $validation_result['form'];
    // if whitelisted or form is disabled, return
    if (cfturnstile_whitelisted() || cfturnstile_form_disable($form['id'], 'cfturnstile_gravity_disable')) {
      return $validation_result;
    }
    
    // if not a POST request or no cf-turnstile-response, return
    if ('POST' !== $_SERVER['REQUEST_METHOD'] || !isset($_POST['cf-turnstile-response'])) {
      $_SESSION['cf-turnstile-response'] = cfturnstile_failed_message();
      $validation_result['is_valid'] = false;
      add_filter('gform_validation_message_' . $form['id'], 'cfturnstile_gravity_validation_message', 10, 2);
      return $validation_result;
    }

    $check = cfturnstile_check();
    $success = $check['success'];
    // if check fails, return error
    if ($success != true) {
      $_SESSION['cf-turnstile-response'] = cfturnstile_failed_message();
      $validation_result['is_valid'] = false;
      add_filter('gform_validation_message_' . $form['id'], 'cfturnstile_gravity_validation_message', 10, 2);

      return $validation_result;
    }
  
    return $validation_result;
  }

  function cfturnstile_gravity_validation_message($message, $form)
  {
    if (isset($_SESSION['cf-turnstile-response'])) {
      $error = $_SESSION['cf-turnstile-response'];
      unset($_SESSION['cf-turnstile-response']);

      $message = '<div class="gform_validation_errors" id="gform_' . $form['id'] . '_validation_container">
      <h2 class="gform_submission_error hide_summary"><span class="gform-icon gform-icon--close"></span>
      ' . esc_html($error) . '
      </h2>
      </div>';
    }

    return $message;
  }

}