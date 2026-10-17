<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_elementor')) {

  /**
   * Elementor forms that should not get Turnstile, by the ID set in the editor:
   * "Form ID" under Additional Options on a classic Form widget, or "ID" under Settings
   * on an Atomic (Elementor 4) form (stored as _cssid).
   *
   * That ID is chosen by whoever builds the form and survives rebuilds, and one entry
   * covers every copy of the form, including every placement of an Elementor component.
   * Comes from the "Exclude Forms" setting and can be extended with the
   * cfturnstile_elementor_skip_forms filter. The same list drives both the widget
   * and the server-side check, so the two cannot disagree.
   *
   * @return string[]
   */
  function cfturnstile_elementor_skip_forms() {
    $ids = explode( ',', (string) get_option( 'cfturnstile_elementor_skip_forms', '' ) );
    $ids = (array) apply_filters( 'cfturnstile_elementor_skip_forms', $ids );
    $ids = array_map( function( $id ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $id ); }, $ids );
    return array_values( array_unique( array_filter( $ids ) ) );
  }

  /**
   * Is this editor ID on the skip list?
   *
   * @param string $id
   * @return bool
   */
  function cfturnstile_elementor_is_skipped_form( $id ) {
    return (string) $id !== '' && in_array( (string) $id, cfturnstile_elementor_skip_forms(), true );
  }

  /**
   * The ID set under Settings on an Atomic form (_cssid), looked up the way Elementor finds the form.
   *
   * The browser only posts the generated element ID. Reading the ID from the stored document rather
   * than from the request means a visitor cannot claim an excluded ID for a different form.
   * For a form inside a component, get_elements_data() returns the expanded placement, which
   * carries the ID of the form in the component.
   *
   * @param int    $post_id Document the form belongs to, as posted by Elementor.
   * @param string $form_id Element ID, as posted by Elementor.
   * @return string
   */
  function cfturnstile_elementor_atomic_css_id( $post_id, $form_id ) {
    if ( ! $post_id || $form_id === '' || ! class_exists( '\Elementor\Plugin' ) || ! class_exists( '\Elementor\Utils' ) ) {
      return '';
    }
    $document = \Elementor\Plugin::$instance->documents->get( $post_id );
    if ( ! $document ) {
      return '';
    }
    $element = \Elementor\Utils::find_element_recursive( $document->get_elements_data(), $form_id );
    if ( empty( $element ) ) {
      return '';
    }
    $instance = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $element );
    if ( $instance && method_exists( $instance, 'get_atomic_setting' ) ) {
      $css_id = $instance->get_atomic_setting( '_cssid' );
      return is_string( $css_id ) ? $css_id : '';
    }
    return '';
  }

  // Always load scripts globally (with scope controls).
  add_action('wp_enqueue_scripts', 'cfturnstile_elementor_enqueue_scripts', 99);

  function cfturnstile_elementor_enqueue_scripts( $ignore_scope = false ) {

    // Determine scope for global loading: 'all' | 'autodetect' | 'specific'
    if ( ! $ignore_scope ) {
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
        $has_form = $current_id && cfturnstile_elementor_page_has_form($current_id);
        // Also check if any Elementor popups with forms might be triggered on this page
        if ( !$has_form ) {
          $has_form = cfturnstile_elementor_any_popup_has_form($current_id);
        }
        if ( !$has_form ) {
          return; // No form detected
        }
      }
    }
    
    // Determine failsafe mode for Elementor (keeps UI and backend in sync)
    $failsafe_mode = '';
    if ( get_option('cfturnstile_failover') && function_exists('cfturnstile_is_cloudflare_down') && cfturnstile_is_cloudflare_down() ) {
      $failsafe_mode = get_option('cfturnstile_failsafe_type', 'allow');
      if ( $failsafe_mode !== 'recaptcha' && $failsafe_mode !== 'allow' ) {
        $failsafe_mode = 'allow';
      }
      if ( $failsafe_mode === 'recaptcha' ) {
        $defer = get_option('cfturnstile_defer_scripts', 1) ? array('strategy' => 'defer') : array();
        if (!wp_script_is('cfturnstile-recaptcha', 'enqueued')) {
          wp_enqueue_script('cfturnstile-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, $defer);
        }
      }
    }

    // Enqueue Turnstile API script (only when not in failsafe UI mode)
    if ( $failsafe_mode === '' && !wp_script_is('cfturnstile', 'enqueued')) {
      $defer = get_option('cfturnstile_defer_scripts', 1) ? array('strategy' => 'defer') : array();
      cfturnstile_register_api($defer);
      wp_enqueue_script('cfturnstile');
    }
    
    // Enqueue our custom Elementor integration script
    if (!wp_script_is('cfturnstile-elementor-forms', 'enqueued')) {
      $deps = array('jquery');
      if ( $failsafe_mode === '' ) {
        $deps[] = 'cfturnstile';
      }

      // Load the interaction-only label helper if needed and make it a dependency
      if ( get_option('cfturnstile_widget_label_enable', 0) && get_option('cfturnstile_appearance', 'always') === 'interaction-only' ) {
        if ( !wp_script_is('cfturnstile-label-js', 'enqueued') ) {
          wp_enqueue_script('cfturnstile-label-js', plugins_url('simple-cloudflare-turnstile/js/interaction-label.js'), array(), '1.0', true);
        }
        $deps[] = 'cfturnstile-label-js';
      }

      wp_enqueue_script(
        'cfturnstile-elementor-forms',
        plugins_url('simple-cloudflare-turnstile/js/integrations/elementor-forms.js'),
        $deps,
        '3.1',
        true
      );

      // Resolve widget label text
      $label_enable = get_option('cfturnstile_widget_label_enable', 0) ? true : false;
      $label_text = get_option('cfturnstile_widget_label_text');
      $label_text = is_string($label_text) ? trim($label_text) : '';
      if ($label_text === '') {
        $label_text = __('Let us know you are human:', 'simple-cloudflare-turnstile');
      } else {
        $label_text = wp_strip_all_tags($label_text);
      }

      // Pass settings to JavaScript
      wp_localize_script('cfturnstile-elementor-forms', 'cfturnstileElementorSettings', array(
        'sitekey' => get_option('cfturnstile_key'),
        'position' => get_option('cfturnstile_elementor_pos', 'before'),
        'align' => get_option('cfturnstile_elementor_align', 'left'),
        'theme' => get_option('cfturnstile_theme'),
        'size' => get_option('cfturnstile_size', 'normal'),
        'appearance' => get_option('cfturnstile_appearance', 'always'),
        'mode' => $failsafe_mode ? $failsafe_mode : 'turnstile',
        'recaptchaSiteKey' => get_option('cfturnstile_recaptcha_site_key'),
        'disableSubmit' => get_option('cfturnstile_disable_button') ? true : false,
        'labelEnable' => $label_enable,
        'labelText' => $label_text,
        'skipForms' => cfturnstile_elementor_skip_forms()
      ));
    }
  }

  /**
   * Detect if a given page contains Elementor Form or Login widgets.
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
        // Atomic (Elementor 4) forms are elements, not widgets - elType is the element type itself.
        if (isset($el['elType']) && $el['elType'] === 'e-form') return true;
        if (!empty($el['elements']) && is_array($el['elements'])){
          if (cfturnstile_elementor_elements_contain_form($el['elements'])) return true;
        }
      }
    }
    return false;
  }

  /**
   * Check if any published Elementor popup templates contain a form.
   * Also checks if the current page references any popup (via action triggers in Elementor data).
   * @param int $current_page_id
   * @return bool
   */
  function cfturnstile_elementor_any_popup_has_form($current_page_id = 0) {
    // Check if Elementor Pro popup post type exists
    if ( !post_type_exists('elementor_library') ) {
      return false;
    }

    // First, try to detect popup IDs referenced in the current page's Elementor data
    $popup_ids = array();
    if ( $current_page_id ) {
      $data = get_post_meta($current_page_id, '_elementor_data', true);
      if ( !empty($data) ) {
        $data_str = is_string($data) ? $data : wp_json_encode($data);
        // Elementor stores popup action triggers like "popup_id":"123"
        if ( preg_match_all('/\"popup_id\"\s*:\s*\"(\d+)\"/', $data_str, $matches) ) {
          $popup_ids = array_map('absint', $matches[1]);
        }
      }
    }

    // If we found specific popup references, only check those
    if ( !empty($popup_ids) ) {
      foreach ( $popup_ids as $popup_id ) {
        if ( cfturnstile_elementor_page_has_form($popup_id) ) {
          return true;
        }
      }
      return false;
    }

    // Fallback: query all published popup templates for forms
    $popups = get_posts(array(
      'post_type'      => 'elementor_library',
      'posts_per_page' => 50,
      'post_status'    => 'publish',
      'meta_query'     => array(
        array(
          'key'   => '_elementor_template_type',
          'value' => 'popup',
        ),
      ),
      'fields' => 'ids',
    ));
    if ( !empty($popups) ) {
      foreach ( $popups as $popup_id ) {
        if ( cfturnstile_elementor_page_has_form($popup_id) ) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Autodetect: also load scripts when a form is rendered from a template (shortcode, Template widget, Theme Builder).
   */
  add_filter('elementor/frontend/builder_content_data', 'cfturnstile_elementor_detect_rendered_form', 10, 2);
  function cfturnstile_elementor_detect_rendered_form($data, $post_id = 0){
    if ( is_admin() || did_action('wp_print_footer_scripts') || wp_script_is('cfturnstile-elementor-forms', 'enqueued') ) {
      return $data;
    }
    if ( get_option('cfturnstile_elementor_global_scope', '') !== 'autodetect' ) {
      return $data;
    }
    if ( is_array($data) && cfturnstile_elementor_elements_contain_form($data) ) {
      cfturnstile_elementor_enqueue_scripts(true);
    }
    return $data;
  }

  // Elementor Forms Check
  add_action('elementor_pro/forms/validation', 'cfturnstile_elementor_check', 10, 2);
  function cfturnstile_elementor_check($record, $ajax_handler){
    // "Form ID" from the editor, taken from the stored widget settings rather than the request.
    if ( cfturnstile_elementor_is_skipped_form( $record->get_form_settings('form_id') ) ) {
      return;
    }
    if(!cfturnstile_whitelisted()) {
      $error_message = cfturnstile_failed_message();
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
    $check = cfturnstile_check('', 'elementor-form');
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

  /**
   * Atomic (Elementor 4) Forms Check
   *
   * Atomic forms do not run through elementor_pro/forms/validation - they post to their own
   * admin-ajax action and build the payload in JavaScript. Hook the action ahead of Elementor's
   * own handler (priority 10) so a missing or invalid token is rejected before any of the
   * form's after-submit actions run, and so the plugin's own failure message is returned.
   *
   * The token is not part of Elementor's form_fields payload - elementor-forms.js appends it
   * to the outgoing FormData, so it arrives as a normal $_POST value.
   */
  add_action('wp_ajax_elementor_pro_atomic_forms_send_form', 'cfturnstile_elementor_atomic_check', 5);
  add_action('wp_ajax_nopriv_elementor_pro_atomic_forms_send_form', 'cfturnstile_elementor_atomic_check', 5);
  function cfturnstile_elementor_atomic_check(){
    // Only look the form up when there is something to match, so sites without exclusions pay nothing.
    if ( cfturnstile_elementor_skip_forms() ) {
      $form_id = isset($_POST['form_id']) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : '';
      $post_id = isset($_POST['post_id']) ? absint( $_POST['post_id'] ) : 0;
      if ( cfturnstile_elementor_is_skipped_form( cfturnstile_elementor_atomic_css_id( $post_id, $form_id ) ) ) {
        return;
      }
    }

    if ( 'POST' !== ( isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
      wp_send_json_error( array( 'message' => cfturnstile_failed_message() ) );
    }

    $check = cfturnstile_check('', 'elementor-atomic-form');

    if ( empty($check['success']) ) {
      wp_send_json_error( array( 'message' => cfturnstile_failed_message() ) );
    }
  }

}