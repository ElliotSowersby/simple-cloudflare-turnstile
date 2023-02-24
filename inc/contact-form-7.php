<?php
if (!defined('ABSPATH')) {
	exit;
}

// Create shortcode
add_shortcode('cf7-simple-turnstile', 'cfturnstile_cf7_shortcode');
add_filter('wpcf7_form_elements', 'do_shortcode');
function cfturnstile_cf7_shortcode() {
	ob_start();
	$id = mt_rand();
	echo '<div class="cf7-cf-turnstile" style="margin-top: -10px; margin-bottom: -15px;">';
	echo cfturnstile_field_show('.wpcf7-submit', 'turnstileCF7Callback', 'contact-form-7', '-cf7-' . $id);
	?>
	<script>
		document.addEventListener("DOMContentLoaded", function() {
			document.querySelectorAll('.wpcf7-form').forEach(function(el) {
				el.addEventListener('submit', function() {
					if (document.getElementById('cf-turnstile-cf7-<?php echo $id; ?>')) {
						setTimeout(function() {
							turnstile.reset('#cf-turnstile-cf7-<?php echo $id; ?>');
						}, 4000);
					}
				});
			});
		});
	</script>
	<?php
	echo '</div>';
	$thecontent = ob_get_contents();
	ob_end_clean();
	wp_reset_postdata();
	$thecontent = trim(preg_replace('/\s+/', ' ', $thecontent));
	return $thecontent;
}

// Add Turnstile to all CF7 forms at once.
if ((!empty(get_option('cfturnstile_cf7_all')) && get_option('cfturnstile_cf7_all'))) {
	add_action('wpcf7_form_elements', 'cfturnstile_field_cf7', 10, 1);
	function cfturnstile_field_cf7($content) {
		$cfturnstile_key = sanitize_text_field(get_option('cfturnstile_key'));
		if (false === strpos($content, $cfturnstile_key)) {
			$button_types = array(
				'<input type="submit"',
				'<input class="wpcf7-form-control has-spinner wpcf7-submit" type="submit"',
				'<input class="wpcf7-form-control wpcf7-submit" type="submit"',
				'<input class="wpcf7-submit" type="submit"',
			);
			foreach($button_types as $button_type) {
				if (false !== strpos($content, $button_type)) {
					$content = str_replace($button_type, cfturnstile_cf7_shortcode() . '<br/>' . $button_type, $content);
				}
			}
			return $content;
		} else {
			return $content;
		}
	}
}

// Validate form submission
add_filter('wpcf7_validate', 'cfturnstile_cf7_verify_recaptcha', 20, 2);
function cfturnstile_cf7_verify_recaptcha($result) {

	if (!class_exists('WPCF7_Submission')) {
		return $result;
	}

	$_wpcf7 = !empty($_POST['_wpcf7']) ? absint($_POST['_wpcf7']) : 0;
	if (empty($_wpcf7)) {
		return $result;
	}

	$post = WPCF7_Submission::get_instance();

	if (!empty($post)) {

		$data = $post->get_posted_data();

		$cf7_text = do_shortcode('[contact-form-7 id="' . $_wpcf7 . '"]');
		$cfturnstile_key = sanitize_text_field(get_option('cfturnstile_key'));
		if ((empty(get_option('cfturnstile_cf7_all')) || !get_option('cfturnstile_cf7_all'))
			&& false === strpos($cf7_text, $cfturnstile_key)
		) {
			return $result;
		}

		$message = cfturnstile_failed_message();

		if (empty($data['cf-turnstile-response'])) {
			$result->invalidate(array('type' => 'captcha', 'name' => 'cf-turnstile'), $message);
			return $result;
		}

		$check = cfturnstile_check();
		$success = $check['success'];
		if ($success != true) {
			$result->invalidate(array('type' => 'captcha', 'name' => 'cf-turnstile'), $message);
			return $result;
		}
		
	}

	return $result;
}

// Add form tag
add_action('wpcf7_init', 'cfturnstile_cf7_add_form_tag_button', 10, 0);
function cfturnstile_cf7_add_form_tag_button() {
	wpcf7_add_form_tag('cf7-simple-turnstile', 'cfturnstile_cf7_shortcode');
}

// Form tag generator
add_action('wpcf7_admin_init', 'cfturnstile_cf7_add_tag_generator_button', 55, 0);
function cfturnstile_cf7_add_tag_generator_button() {
	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add('cf7-simple-turnstile', __('cloudflare turnstile', 'contact-form-7'), 'cfturnstile_cf7_tag_generator_button', '');
}

// Insert tag form
function cfturnstile_cf7_tag_generator_button($contact_form, $args = '') {
	$args = wp_parse_args($args, array());
	?>
	<div class="insert-box">
		<input type="text" name="cf7-simple-turnstile" class="tag code" readonly="readonly" onfocus="this.select()" />
		<div class="submitbox">
			<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'contact-form-7')); ?>" />
		</div>
	</div>
<?php
}
