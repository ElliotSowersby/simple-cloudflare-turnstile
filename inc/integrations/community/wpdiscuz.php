<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if(get_option('cfturnstile_comment')) {

  add_action('wpdiscuz_before_comments','cfturnstile_field_wpdiscuz_script');
  function cfturnstile_field_wpdiscuz_script() {
      do_action("cfturnstile_enqueue_scripts");
  }

  add_action('wpdiscuz_submit_button_before','cfturnstile_field_wpdiscuz', 10, 3);
  function cfturnstile_field_wpdiscuz( $currentUser, $uniqueId, $isMainForm ) {
      $uniqueId = sanitize_text_field($uniqueId);
      $turnstilecode = '<div id="cf-turnstile-wpd-'.esc_html($uniqueId).'" class="wpdiscuz-cfturnstile" style="margin-left: -2px; margin-top: 10px; margin-bottom: 10px; display: inline-flex;"></div><div style="clear: both;"></div>';
    	$appearance = esc_attr(get_option('cfturnstile_appearance', 'always'));
      ?>
      <script>
      jQuery(document).ready(function() {
          <?php if($uniqueId == "0_0") { ?>
          jQuery('.wpd_main_comm_form .wpd-form-col-right .wc-field-submit').before('<?php echo $turnstilecode; ?>');
          <?php } else { ?>
          jQuery('#wpd-comm-<?php echo esc_html($uniqueId); ?> .wpd-form-col-right .wc-field-submit').before('<?php echo $turnstilecode; ?>');
          <?php } ?>
          turnstile.remove('#cf-turnstile-wpd-<?php echo esc_html($uniqueId); ?>');
          turnstile.render('#cf-turnstile-wpd-<?php echo esc_html($uniqueId); ?>', {
            sitekey: '<?php echo sanitize_text_field( get_option('cfturnstile_key') ); ?>',
            appearance: '<?php echo sanitize_text_field($appearance); ?>',
            action: 'wpdiscuz-comment',
            <?php if(get_option('cfturnstile_disable_button')) { ?>
            callback: function(token) {
              jQuery('#wpd-field-submit-<?php echo esc_html($uniqueId); ?>').css('pointer-events', 'auto');
              jQuery('#wpd-field-submit-<?php echo esc_html($uniqueId); ?>').css('opacity', '1');
            },
            <?php } ?>
          });
      });
      jQuery( document ).ready(function() {
        jQuery( '#wpd-field-submit-<?php echo esc_html($uniqueId); ?>' ).click(function(){
        if (document.getElementById('cf-turnstile-wpd-<?php echo esc_html($uniqueId); ?>')) {
          setTimeout(function() {
            turnstile.reset('#cf-turnstile-wpd-<?php echo esc_html($uniqueId); ?>');
          }, 2000);
        }
        });
      });
      </script>
      <?php if(get_option('cfturnstile_disable_button')) { ?>
    	<style>#wpd-field-submit-<?php echo esc_html($uniqueId); ?> { pointer-events: none; opacity: 0.5; }</style>
      <?php } ?>
      <?php
  }

  add_action("wpdiscuz_before_comment_post",'cfturnstile_wpdiscuz_check', 10);
  function cfturnstile_wpdiscuz_check() {
    $check = cfturnstile_check();
    $success = $check['success'];
    if($success != true) {
      wp_die( cfturnstile_failed_message() );
    }
  }

}
