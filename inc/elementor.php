<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_elementor')) {

  // Get turnstile field
  add_action('elementor-pro/forms/pre_render','cfturnstile_field_elementor_form', 10, 2);
  function cfturnstile_field_elementor_form($instance, $form) {
    do_action("cfturnstile_enqueue_scripts");
    $id = mt_rand();
    $turnstilediv = '<div id="cf-turnstile-em-'.$id.'" data-retry="auto" data-retry-interval="1000" style="margin-left: -2px; margin-top: 10px; margin-bottom: 10px;"></div><br/>';
  	?>
    <script>
    jQuery(document).ready(function() {
      if (jQuery('.elementor-form[name="<?php echo $instance['form_name']; ?>"]').length > 0) {
        <?php if(!empty(get_option('cfturnstile_elementor_pos')) && get_option('cfturnstile_elementor_pos') == "after") { ?>
          jQuery('.elementor-form[name="<?php echo $instance['form_name']; ?>"] button[type=submit]').after('<?php echo $turnstilediv; ?>');
        <?php } elseif(!empty(get_option('cfturnstile_elementor_pos')) && get_option('cfturnstile_elementor_pos') == "afterform") { ?>
          jQuery('.elementor-form[name="<?php echo $instance['form_name']; ?>"] .elementor-form-fields-wrapper').after('<?php echo $turnstilediv; ?>');
        <?php } else { ?>
          jQuery('.elementor-form[name="<?php echo $instance['form_name']; ?>"] button[type=submit]').before('<?php echo $turnstilediv; ?>');
        <?php } ?>
        if (jQuery('.elementor-form[name="<?php echo $instance['form_name']; ?>"] #cf-turnstile-em-<?php echo $id; ?> iframe').length <= 0) {
          setTimeout(function() {
            turnstile.render('.elementor-form[name="<?php echo $instance['form_name']; ?>"] #cf-turnstile-em-<?php echo $id; ?>', {
              sitekey: '<?php echo sanitize_text_field( get_option('cfturnstile_key') ); ?>',
              action: 'elementor-<?php echo str_replace(" ", "-", strtolower($instance['form_name']) ); ?>',
              <?php if(get_option('cfturnstile_disable_button')) { ?>
              callback: function(token) {
                jQuery('.elementor-form[name="<?php echo $instance['form_name']; ?>"] button[type=submit]').css('pointer-events', 'auto');
                jQuery('.elementor-form[name="<?php echo $instance['form_name']; ?>"] button[type=submit]').css('opacity', '1');
              },
              <?php } ?>
            });
          }, 50);
        }
      }
    });
    jQuery( document ).ready(function() {
      jQuery( ".elementor-form" ).on('submit', function() {
        if (document.getElementById('cf-turnstile-em-<?php echo $id; ?>')) {
          setTimeout(function() {
            turnstile.reset('#cf-turnstile-em-<?php echo $id; ?>');
          }, 2000);
        }
      });
    });
    </script>
    <?php if(get_option('cfturnstile_disable_button')) { ?>
  	<style>.elementor-form[name="<?php echo $instance['form_name']; ?>"] button[type=submit] { pointer-events: none; opacity: 0.5; }</style>
    <?php } ?>
    <?php
  }

  // Elementor Forms Check
  add_action('elementor_pro/forms/validation', 'cfturnstile_elementor_check', 10, 2);
  function cfturnstile_elementor_check($record, $ajax_handler){
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