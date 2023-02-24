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
      echo '<div class="wpdiscuz-cfturnstile">';
    	?>
      <script>
      jQuery(document).ready(function() {
        jQuery('#wpd-field-submit-<?php echo $uniqueId; ?>').before('<div id="cf-turnstile-wpd-<?php echo $uniqueId; ?>" style="margin-left: -2px; margin-top: 10px; margin-bottom: 10px;"></div><br/>');
          turnstile.remove('#cf-turnstile-wpd-<?php echo $uniqueId; ?>');
          turnstile.render('#cf-turnstile-wpd-<?php echo $uniqueId; ?>', {
            sitekey: '<?php echo sanitize_text_field( get_option('cfturnstile_key') ); ?>',
            action: 'wpdiscuz-comment',
            <?php if(get_option('cfturnstile_disable_button')) { ?>
            callback: function(token) {
              jQuery('#wpd-field-submit-<?php echo $uniqueId; ?>').css('pointer-events', 'auto');
              jQuery('#wpd-field-submit-<?php echo $uniqueId; ?>').css('opacity', '1');
            },
            <?php } ?>
          });
      });
      jQuery( document ).ready(function() {
        jQuery( '#wpd-field-submit-<?php echo $uniqueId; ?>' ).click(function(){
        if (document.getElementById('cf-turnstile-wpd-<?php echo $uniqueId; ?>')) {
          setTimeout(function() {
            turnstile.reset('#cf-turnstile-wpd-<?php echo $uniqueId; ?>');
          }, 2000);
        }
        });
      });
      </script>
      <?php if(get_option('cfturnstile_disable_button')) { ?>
    	<style>#wpd-field-submit-<?php echo $uniqueId; ?> { pointer-events: none; opacity: 0.5; }</style>
      <?php } ?>
      <?php
      echo '</div><div style="clear: both;"></div><br/>';
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
