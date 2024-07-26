<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_elementor')) {

  // Add Turnstile field to Elementor login form
  add_filter('elementor/widget/render_content', 'cfturnstile_elementor_login_form', 10, 2);
  function cfturnstile_elementor_login_form($content, $widget) {

    // Check if the widget is an Elementor contact form
    if ('form' !== $widget->get_name() && 'login' !== $widget->get_name()) {
      return $content;
    }

    // Use a static array to track the processed form widgets' IDs
    static $processed_forms = [];
    $widget_id = $widget->get_id();
    // If the Turnstile field is already added to this form, return the content
    if (in_array($widget_id, $processed_forms)) {
      return $content;
    }
    // Add the widget ID to the processed_forms array
    $processed_forms[] = $widget_id;

    // Start output buffering to capture the output of cfturnstile_field_show
    ob_start();
    $margin = "";
    $unique_id = wp_rand();
    if(get_option('cfturnstile_elementor_pos') == "after" || get_option('cfturnstile_elementor_pos') == "afterform") {
      $margin = " margin-top: 12px;";
    }
    echo "<div class='elementor-turnstile-field' style='display: block;margin-top: 10px;width: 100%;".$margin."'>";
    cfturnstile_field_show('', 'turnstileElementorCallback', 'elementor-' . $unique_id, '-elementor-' . $unique_id);
    echo "</div><br/>";
    $recaptcha_field = ob_get_clean();

    // Find the submit button in the form
    $submit_button_pattern = '/(<button[^>]*type="submit"[^>]*>.*?<\/button>)/is';
    $matches = [];
    preg_match($submit_button_pattern, $content, $matches);
    
    if (!empty($matches[0])) {
        $submit_button = $matches[0];
        if(get_option('cfturnstile_elementor_pos') == "afterform") {
          $content = str_replace('</form>', $recaptcha_field . '</form>', $content);
        } elseif(get_option('cfturnstile_elementor_pos') == "after") {
          $content = str_replace($submit_button, $submit_button . $recaptcha_field, $content);
        } else {
          $content = str_replace($submit_button, $recaptcha_field . $submit_button, $content);
        }
    } else {
        // If submit button is not found, insert the Turnstile field before the form closing tag
        $content = str_replace('</form>', $recaptcha_field . '</form>', $content);
    }

    return $content;
    
  }

  // Reset Turnstile field on Elementor form submit
  add_action('elementor-pro/forms/pre_render','cfturnstile_field_elementor_form_submit', 10, 2);
  function cfturnstile_field_elementor_form_submit($instance, $form) {
    if(!wp_script_is('cfturnstile', 'enqueued')) {
      $defer = get_option('cfturnstile_defer_scripts', 1) ? array('strategy' => 'defer') : array();
      wp_enqueue_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit", array(), null, $defer);
    }
  	?>
    <script>
    jQuery(document).ready(function() {
      jQuery(".elementor-form").on('submit', function() {
        var submittedForm = jQuery(this);
        setTimeout(function() {
          var turnstileElement = submittedForm.find('.cf-turnstile');
          if (turnstileElement.length > 0) {
            var uniqueId = 'cf-turnstile-elementor-' + new Date().getTime();
            turnstileElement.attr('id', uniqueId);
            turnstile.reset('#' + uniqueId);
          }
        }, 2500);
      });
    });
    </script>
    <?php if(get_option('cfturnstile_disable_button')) { ?>
  	<style>.elementor-form[name="<?php echo esc_html($instance['form_name']); ?>"] button[type=submit] { pointer-events: none; opacity: 0.5; }</style>
    <?php } ?>
    <?php
  }

  // Elementor Forms Check
  add_action('elementor_pro/forms/validation', 'cfturnstile_elementor_check', 10, 2);
  function cfturnstile_elementor_check($record, $ajax_handler){
    if(!cfturnstile_whitelisted()) {
      $error_message = cfturnstile_failed_message();
      if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['cf-turnstile-response'] ) ) {
        $check = cfturnstile_check();
        $success = $check['success'];
        if($success != true) {
          $ajax_handler->add_error_message( $error_message );
          $ajax_handler->add_error( '', '' );
          $ajax_handler->is_success = false;
        }
      } else {
        $ajax_handler->add_error_message( $error_message );
        $ajax_handler->add_error( '', '' );
        $ajax_handler->is_success = false;
      }
    }
  }

}