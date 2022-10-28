<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_comment')) {

  add_action('wpdiscuz_submit_button_before','cfturnstile_field_wpdiscuz', 10, 3);
  function cfturnstile_field_wpdiscuz( $uniqueId, $currentUser, $form ) {
      echo '<div class="wpdiscuz-cfturnstile">';
      echo cfturnstile_field_show('.wc_comm_submit', 'turnstileWPDiscuzCallback');
      echo '</div><div style="clear: both;"></div><br/>';
  }

  add_action("wpdiscuz_before_comment_post",'cfturnstile_wpdiscuz_check', 10);
  function cfturnstile_wpdiscuz_check() {
    $check = cfturnstile_check();
    $success = $check['success'];
    if($success != true) {
      wp_die( __( 'Please verify that you are human.', 'simple-cloudflare-turnstile' ) );
    }
  }

}
