<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_elementor')) {

  // Enqueue Turnstile script for Elementor forms
  add_action('elementor/widget/before_render_content', 'cfturnstile_elementor_before_render', 10, 1);
  function cfturnstile_elementor_before_render($widget) {
    if ('form' !== $widget->get_name() && 'login' !== $widget->get_name()) {
      return;
    }
    
    // Enqueue Turnstile API script
    if (!wp_script_is('cfturnstile', 'enqueued')) {
      $defer = get_option('cfturnstile_defer_scripts', 1) ? array('strategy' => 'defer') : array();
      wp_enqueue_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit", array(), null, $defer);
    }
    
    // Enqueue our custom Elementor integration script
    if (!wp_script_is('cfturnstile-elementor-forms', 'enqueued')) {
      wp_enqueue_script(
        'cfturnstile-elementor-forms',
        plugins_url('simple-cloudflare-turnstile/js/integrations/elementor-forms.js'),
        array('cfturnstile', 'jquery'),
        '1.0',
        true
      );
      
      // Pass settings to JavaScript
      wp_localize_script('cfturnstile-elementor-forms', 'cfturnstileElementorSettings', array(
        'sitekey' => get_option('cfturnstile_key'),
        'position' => get_option('cfturnstile_elementor_pos', 'before'),
        'theme' => get_option('cfturnstile_theme')
      ));
    }
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