<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_elementor')) {

  $method = get_option('cfturnstile_elementor_method', 'element');
  
  if ($method === 'global') {
    // Enqueue scripts globally for Elementor pages
    add_action('elementor/frontend/before_enqueue_scripts', 'cfturnstile_elementor_enqueue_scripts');
  } else {
    // Enqueue on widget render
    add_action('elementor/widget/before_render_content', 'cfturnstile_elementor_before_render', 10, 1);
  }
  
  function cfturnstile_elementor_enqueue_scripts() {

    // Determine scope for global loading: 'all' | 'autodetect' | 'specific'
    $scope = get_option('cfturnstile_elementor_global_scope', '');
    if ($scope === '') { $scope = 'all'; }

    // Always allow in Elementor preview/editor
    $is_builder = ( isset($_GET['elementor-preview']) || ( defined('ELEMENTOR_VERSION') && isset($_GET['action']) && $_GET['action'] === 'elementor' ) );

    if ($scope === 'specific' && !$is_builder) {
      $ids_raw = (string) get_option('cfturnstile_elementor_global_pages', '');
      $ids = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $ids_raw ) ) ) );
      $current_id = function_exists('get_queried_object_id') ? get_queried_object_id() : 0;
      if ( empty($ids) || !$current_id || !in_array( $current_id, $ids, true ) ) {
        return; // Not an allowed page
      }
    } elseif ($scope === 'autodetect' && !$is_builder) {
      $current_id = function_exists('get_queried_object_id') ? get_queried_object_id() : 0;
      if ( !$current_id || !cfturnstile_elementor_page_has_form($current_id) ) {
        return; // No form detected
      }
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
        '2.1',
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

  /**
   * Detect if a given page contains Elementor Form or Login widgets.
   * Best-effort: parses _elementor_data JSON for widgetType 'form' or 'login'.
   * @param int $post_id
   * @return bool
   */
  function cfturnstile_elementor_page_has_form($post_id){
    if (!$post_id) return false;
    $data = get_post_meta($post_id, '_elementor_data', true);
    if (empty($data)) return false;
    if (is_string($data)) {
      $json = json_decode($data, true);
    } else {
      $json = $data;
    }
    if (!is_array($json)) return false;
    return cfturnstile_elementor_elements_contain_form($json);
  }

  function cfturnstile_elementor_elements_contain_form($elements){
    foreach((array)$elements as $el){
      if (is_array($el)) {
        if (isset($el['elType']) && $el['elType'] === 'widget' && isset($el['widgetType'])){
          $wt = $el['widgetType'];
          if ($wt === 'form' || $wt === 'login') return true;
        }
        if (!empty($el['elements']) && is_array($el['elements'])){
          if (cfturnstile_elementor_elements_contain_form($el['elements'])) return true;
        }
      }
    }
    return false;
  }
  
  // Widget render method function
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
        '2.1',
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