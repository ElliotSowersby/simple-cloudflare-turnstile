<?php
/**
* Plugin Name: Simple Cloudflare Turnstile
* Description: Easily add Cloudflare Turnstile to your WordPress and WooCommerce forms. The user-friendly, privacy-preserving CAPTCHA alternative.
* Version: 1.8.4
* Author: Elliot Sowersby, RelyWP
* Author URI: https://www.relywp.com
* License: GPLv3 or later
* Text Domain: simple-cloudflare-turnstile
*
* WC requires at least: 3.4
* WC tested up to: 7.0.0
**/

// Include Admin Options
include( plugin_dir_path( __FILE__ ) . 'inc/admin-options.php');

/**
 * On activate redirect to settings page
 */
register_activation_hook(__FILE__, function () {
  add_option('cfturnstile_do_activation_redirect', true);
	add_option('cfturnstile_tested', 'no');
});
add_action('admin_init', function () {
  if (get_option('cfturnstile_do_activation_redirect', false)) {
    delete_option('cfturnstile_do_activation_redirect');
    exit( wp_redirect("options-general.php?page=simple-cloudflare-turnstile%2Finc%2Fadmin-options.php") );
  }
});

/**
 * Plugin List - Settings Link
 *
 * @param array $actions
 * @param string $plugin_file
 * @return array
 */
add_filter( 'plugin_action_links', 'cfturnstile_settings_link_plugin', 10, 5 );
function cfturnstile_settings_link_plugin( $actions, $plugin_file ) {
  static $plugin;
  if (!isset($plugin))
    $plugin = plugin_basename(__FILE__);
  if ($plugin == $plugin_file) {
    $settings = array('settings' => '<a href="options-general.php?page=simple-cloudflare-turnstile%2Finc%2Fadmin-options.php">' . __( 'Settings', 'simple-cloudflare-turnstile' ) . '</a>');
    $actions = array_merge($settings, $actions);
  }
  return $actions;
}

/**
 * Enqueue admin scripts
 */
function cfturnstile_admin_script_enqueue() {
  wp_enqueue_script( 'cfturnstile-admin-js', plugins_url( '/js/admin-scripts.js', __FILE__ ), array('jquery'), '2.4', true);
  wp_enqueue_style( 'cfturnstile-admin-css', plugins_url( '/css/admin-style.css', __FILE__ ), array(), '2.4');
  wp_enqueue_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onloadTurnstileCallback", array(), '', 'true');
}
add_action( 'admin_enqueue_scripts', 'cfturnstile_admin_script_enqueue' );

/**
 * Create turnstile field template.
 *
 * @param int $button_id
 * @param string $callback
 */
function cfturnstile_field_show($button_id = '', $callback = '', $g = false) {
	$key = esc_attr( get_option('cfturnstile_key') );
	$theme = esc_attr( get_option('cfturnstile_theme') );
  do_action("cfturnstile_before_field");
	?>
	<div id="cf-turnstile" class="cf-turnstile" <?php if(get_option('cfturnstile_disable_button')) { ?>data-callback="<?php echo $callback; ?>"<?php } ?>
	data-sitekey="<?php echo sanitize_text_field($key); ?>"
	data-theme="<?php echo sanitize_text_field($theme); ?>"
	data-name="cf-turnstile"
	<?php if(!is_page()) { ?> style="margin-left: -15px;"<?php } ?>></div>
	<?php if($button_id && get_option('cfturnstile_disable_button')) { ?>
	<style><?php echo $button_id; ?> { pointer-events: none; opacity: 0.5; }</style><?php } ?>
	<br/>
	<?php
  do_action("cfturnstile_after_field");
}

if(!empty(get_option('cfturnstile_key')) && !empty(get_option('cfturnstile_secret'))) {

  /**
   * Enqueue turnstile scripts
   */
  function cfturnstile_script_enqueue() {
    if( !wp_script_is( 'cfturnstile-js', 'enqueued' ) ) {
  	   wp_enqueue_script( 'cfturnstile-js', plugins_url( '/js/cfturnstile.js', __FILE__ ), array('jquery'), '2.1', false);
    }
    if( !wp_script_is( 'cfturnstile', 'enqueued' ) ) {
  	   wp_enqueue_script("cfturnstile", "https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onloadTurnstileCallback", array(), '', 'true');
    }
  }
  add_action("wp_enqueue_scripts", "cfturnstile_script");
  function cfturnstile_script() {
  	if ( cfturnstile_check_page() ) {
  	   cfturnstile_script_enqueue();
  	}
  }
  add_action("login_enqueue_scripts", "cfturnstile_script_login");
  function cfturnstile_script_login() {
    cfturnstile_script_enqueue();
  }

  /**
   * Force Re-Render Turnstile
   */
  add_action("cfturnstile_after_field", "cfturnstile_force_render");
  function cfturnstile_force_render() {
    ?>
    <script>
    jQuery(document).ready(function() {
      turnstile.remove('#cf-turnstile');
      turnstile.render('#cf-turnstile', { sitekey: '<?php echo sanitize_text_field( get_option('cfturnstile_key') ); ?>', });
    });
    </script>
    <?php
  }

  /**
   * Check if page needs to load scripts
   *
   * @return bool
   */
	function cfturnstile_check_page() {
		global $post;
    if(!empty(get_option('cfturnstile_scripts')) && get_option('cfturnstile_scripts') == "custom") {
      return cfturnstile_check_page_custom();
    } else {
      if(!empty(get_option('cfturnstile_scripts')) && get_option('cfturnstile_scripts') == "autocustom" && cfturnstile_check_page_custom()) return true;
      if(!empty(get_option('cfturnstile_scripts')) && get_option('cfturnstile_scripts') == "all") return true;
  		if( is_single() && get_option('cfturnstile_comment') ) return true;
  		if( ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) && (is_checkout() || is_account_page()) ) return true;
  		if( is_single() || is_page() ) {
  			if( get_option('cfturnstile_bp_register') && !is_user_logged_in() ) return true;
  			if( has_shortcode( $post->post_content, 'contact-form-7') ) return true;
  			if( has_shortcode( $post->post_content, 'mc4wp_form') || has_block('mailchimp-for-wp/form') ) return true;
  			if( get_option('cfturnstile_wpforms') && ( has_shortcode( $post->post_content, 'wpforms') || has_block('wpforms/form-selector') ) ) return true;
  			if( get_option('cfturnstile_fluent') && ( has_shortcode( $post->post_content, 'fluentform') || has_block('fluentfom/guten-block') ) ) return true;
        if( get_option('cfturnstile_gravity') && ( has_shortcode( $post->post_content, 'gravityform') || has_block('gravityforms/form') ) ) return true;
      }
    }
    return false;
	}

  /**
   * Check if current page is one of the custom pages set in the settings
   *
   * @return bool
   */
  function cfturnstile_check_page_custom() {
		global $post;
    if( is_single() || is_page() ) {
      $pages = preg_replace('/\s+/', '', get_option('cfturnstile_scripts_custom'));
      $pages = explode (",",$pages);
      if(in_array($post->ID, $pages)) return true;
    }
    return false;
	}

  /**
   * Checks Turnstile Captcha POST is Valid
   *
   * @param string $postdata
   * @return bool
   */
	function cfturnstile_check($postdata = "") {

		$results = array();

		if(empty($postdata) && isset($_POST['cf-turnstile-response'])) {
			$postdata = sanitize_text_field( $_POST['cf-turnstile-response'] );
		}
		$key = sanitize_text_field( get_option('cfturnstile_key') );
		$secret = sanitize_text_field( get_option('cfturnstile_secret') );
		if($key && $secret) {
			$headers = array(
				'body' => [
					'secret' => $secret,
					'response' => $postdata
				]
			);
			$verify = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', $headers );
			$verify = wp_remote_retrieve_body( $verify );
			$response = json_decode($verify);

			$results['success'] = $response->success;

			foreach($response as $key => $val){
				if($key == 'error-codes')
				foreach($val as $key => $error_val){
					$results['error_code'] = $error_val;
				}
			}

			return $results;

		} else {

			return false;

		}
	}

  /**
   * Create shortcode to display Turnstile widget
   */
  add_shortcode('simple-turnstile', 'cfturnstile_shortcode');
  function cfturnstile_shortcode() {
  	ob_start();
  	echo cfturnstile_field_show('', '');
  	$thecontent = ob_get_contents();
  	ob_end_clean();
  	wp_reset_postdata();
  	$thecontent = trim(preg_replace('/\s+/', ' ', $thecontent));
  	return $thecontent;
  }

  /**
   * Gets the default Turnstile error message
   *
   * @param string $code
   * @return string
   */
	function cfturnstile_error_message($code) {
		switch ( $code ) {
			case 'missing-input-secret':
				return __( 'The secret parameter was not passed.', 'simple-cloudflare-turnstile' );
			case 'invalid-input-secret':
				return __( 'The secret parameter was invalid or did not exist.', 'simple-cloudflare-turnstile' );
			case 'missing-input-response':
				return __( 'The response parameter was not passed.', 'simple-cloudflare-turnstile' );
			case 'invalid-input-response':
				return __( 'The response parameter is invalid or has expired.', 'simple-cloudflare-turnstile' );
			case 'bad-request':
				return __( 'The request was rejected because it was malformed.', 'simple-cloudflare-turnstile' );
			case 'timeout-or-duplicate':
				return __( 'The response parameter has already been validated before.', 'simple-cloudflare-turnstile' );
			case 'internal-error':
				return __( 'An internal error happened while validating the response. The request can be retried.', 'simple-cloudflare-turnstile' );
			default:
				return __( 'There was an error with Turnstile response. Please check your keys are correct.', 'simple-cloudflare-turnstile' );
		}
	}

	// Include WordPress
	include( plugin_dir_path( __FILE__ ) . 'inc/wordpress.php');

	// Include WooCommerce
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		include( plugin_dir_path( __FILE__ ) . 'inc/woocommerce.php');
	}

	// Include Contact Form 7
	if ( in_array( 'contact-form-7/wp-contact-form-7.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		include( plugin_dir_path( __FILE__ ) . 'inc/contact-form-7.php');
	}

	// Include Buddypress
	if ( in_array( 'buddypress/bp-loader.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		include( plugin_dir_path( __FILE__ ) . 'inc/buddypress.php');
	}

	// Include MC4WP
	if ( in_array( 'mailchimp-for-wp/mailchimp-for-wp.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		include( plugin_dir_path( __FILE__ ) . 'inc/mc4wp.php');
	}

	// Include WPForms
	if ( in_array( 'wpforms-lite/wpforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || in_array( 'wpforms/wpforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		include( plugin_dir_path( __FILE__ ) . 'inc/wpforms.php');
	}

	// Fluent Forms
	if ( in_array( 'fluentform/fluentform.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		include( plugin_dir_path( __FILE__ ) . 'inc/fluent-forms.php');
	}

  // Gravity Forms
	if ( in_array( 'gravityforms/gravityforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		include( plugin_dir_path( __FILE__ ) . 'inc/gravity-forms.php');
	}

	// Include BBPress
	if ( in_array( 'bbpress/bbpress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		include( plugin_dir_path( __FILE__ ) . 'inc/bbpress.php');
	}

}
