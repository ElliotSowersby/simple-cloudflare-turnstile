<?php
if (!defined('ABSPATH')) {
	exit;
}

// Create custom plugin settings menu
add_action('admin_menu', 'cfturnstile_create_menu');
function cfturnstile_create_menu() {
    add_submenu_page(
        'options-general.php', // Parent slug
        'Cloudflare Turnstile', // Page title
        'Cloudflare Turnstile', // Menu title
        'manage_options', // Capability
        'cfturnstile', // Menu slug
        'cfturnstile_settings_page' // Callback function
    );
}

// Keys Updated
add_action('update_option_cfturnstile_key', 'cfturnstile_keys_updated', 10);
add_action('update_option_cfturnstile_secret', 'cfturnstile_keys_updated', 10);
function cfturnstile_keys_updated() {
	update_option('cfturnstile_tested', 'no');
}

// Admin test form to check Turnstile response
function cfturnstile_admin_test() {
?>
	<form action="" method="POST" class="cfturnstile-settings">
		<?php
		if (!empty(get_option('cfturnstile_key')) && !empty(get_option('cfturnstile_secret'))) {
			$check = cfturnstile_check();
			$success = '';
			$error = '';
			if (isset($check['success'])) $success = $check['success'];
			if (isset($check['error_code'])) $error = $check['error_code'];
			if ($success != true) {
				echo '<div style="padding: 20px 20px 25px 20px; margin: 20px 0 28px 0; background: #fff; border-radius: 20px; max-width: 500px; border: 2px solid #d5d5d5;">';
				echo '<p style="font-weight: 600; font-size: 19px; margin-top: 0; margin-bottom: 0;">' . esc_html__('Almost done...', 'simple-cloudflare-turnstile') . '</p>';
			}
			if (!isset($_POST['cf-turnstile-response'])) {
				echo '<p>'
					. '<span style="color: red; font-weight: bold;">' . esc_html__('API keys have been updated. Please test the Turnstile API response below.', 'simple-cloudflare-turnstile') . '</span>'
					. '<br/>'
					. esc_html__('Turnstile will not be added to any forms until the test is successfully complete.', 'simple-cloudflare-turnstile')
					. '</p>';
			} else {
				if ($success == true) {
					update_option('cfturnstile_tested', 'yes');
				} else {
					if ($error == "missing-input-response") {
						echo '<p style="font-weight: bold; color: red;">' . cfturnstile_failed_message() . '</p>';
					} else {
						echo '<p style="font-weight: bold; color: red;">' . esc_html__('Failed! There is an error with your API settings. Please check & update them.', 'simple-cloudflare-turnstile') . '</p>';
					}
				}
				if ($error) {
					echo '<p style="font-weight: bold;">' . esc_html__('Error message:', 'simple-cloudflare-turnstile') . " " . cfturnstile_error_message($error) . '</p>';
				}
			}
			if ($success != true) {
				echo '<div style="margin-left: 0px;">';
				echo cfturnstile_field_show('', '', 'admin-test', 'admin-test');
				echo '</div><div style="margin-bottom: -20px;"></div>';
				echo '<button type="submit" style="margin-top: 10px; padding: 7px 10px; background: #1c781c; color: #fff; font-weight: bold; border: 1px solid #176017; border-radius: 4px; cursor: pointer;">
				' . esc_html__('TEST RESPONSE', 'simple-cloudflare-turnstile') . ' <span class="dashicons dashicons-arrow-right-alt"></span>
				</button>';
				echo '</div>';
			}
		}
		?>
	</form>
<?php
}

// Show Settings Page
function cfturnstile_settings_page() {
?>
	<div class="sct-wrap wrap">

		<h1 style="font-weight: bold;"><?php echo esc_html__('Simple CAPTCHA Alternative with Cloudflare Turnstile', 'simple-cloudflare-turnstile'); ?></h1>

		<p style="margin-bottom: 0;"><?php echo esc_html__('Easily add the free CAPTCHA service called "Cloudflare Turnstile" to your WordPress forms to help prevent spam.', 'simple-cloudflare-turnstile'); ?> <a href="https://www.cloudflare.com/en-gb/products/turnstile/" target="_blank"><?php echo esc_html__('Learn more.', 'simple-cloudflare-turnstile'); ?></a>

		<div class="sct-admin-promo-top">

			<p>
				<a href="https://elliotsowersby.com/blog/setup-guide-turnstile/?utm_source=simplecloudflareturnstile&utm_medium=settings-guide" title="View our Turnstile plugin setup guide." target="_blank"><?php echo esc_html__('View setup guide', 'simple-cloudflare-turnstile'); ?><span class="dashicons dashicons-external" style="margin-left: 2px; text-decoration: none;"></span></a> &nbsp;&#x2022;&nbsp; <?php echo esc_html__('Like this plugin?', 'simple-cloudflare-turnstile'); ?> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank" title="<?php echo esc_html__('Review on WordPress.org', 'simple-cloudflare-turnstile'); ?>"><?php echo esc_html__('Please submit a review', 'simple-cloudflare-turnstile'); ?></a> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank" title="<?php echo esc_html__('Review on WordPress.org', 'simple-cloudflare-turnstile'); ?>" style="text-decoration: none;">
					<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span>
				</a>
			</p>

		</div>

		<?php
		if (empty(get_option('cfturnstile_tested')) || get_option('cfturnstile_tested') != 'yes') {
			echo cfturnstile_admin_test();
		}
		?>

		<form method="post" action="options.php" class="cfturnstile-settings">

			<?php settings_fields('cfturnstile-settings-group'); ?>
			<?php do_settings_sections('cfturnstile-settings-group'); ?>

			<hr style="margin: 20px 0 0 0;">

			<table class="form-table">

				<tr valign="top">
					<th scope="row" style="padding-bottom: 0;">

						<p style="font-size: 15px; font-size: 19px; margin-top: 0;"><?php echo esc_html__('API Key Settings:', 'simple-cloudflare-turnstile'); ?></p>

						<?php
						// wp-config.php override info
						$cf_const_site   = ( defined('CF_TURNSTILE_SITE_KEY') && CF_TURNSTILE_SITE_KEY );
						$cf_const_secret = ( defined('CF_TURNSTILE_SECRET_KEY') && CF_TURNSTILE_SECRET_KEY );
						?>

						<?php
						if ( !$cf_const_site && !$cf_const_secret ) {
							if (get_option('cfturnstile_tested') == 'yes') {
								echo '<p style=" font-weight: bold; color: #1e8c1e;"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Success! Turnstile is working correctly with your API keys.', 'simple-cloudflare-turnstile') . '</p>';
							}
						}
						?>

						<p style="margin-bottom: 2px;"><?php echo esc_html__('You can get your site key and secret key from here:', 'simple-cloudflare-turnstile'); ?> <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">https://dash.cloudflare.com/?to=/:account/turnstile</a></p>

						<?php
						if ( $cf_const_site || $cf_const_secret ) {
							$which = array();
							if ( $cf_const_site ) { $which[] = esc_html__('Site Key', 'simple-cloudflare-turnstile'); }
							if ( $cf_const_secret ) { $which[] = esc_html__('Secret Key', 'simple-cloudflare-turnstile'); }
							$which_text = implode(' & ', $which);
							printf(
								'<p style="margin: 15px 0 0 0; font-weight:600; color:#1e8c1e;"><span class="dashicons dashicons-lock"></span> %s</p>',
								esc_html__('Using keys defined in wp-config.php. Be sure to test your forms to confirm they are working.', 'simple-cloudflare-turnstile')
							);
						}
						?>



					</th>
				</tr>

			</table>

			<table class="form-table">

				<tr valign="top">
					<th scope="row"><?php echo esc_html__('Site Key', 'simple-cloudflare-turnstile'); ?></th>
					<td>
						<?php $cf_const_site = ( defined('CF_TURNSTILE_SITE_KEY') && CF_TURNSTILE_SITE_KEY ); ?>
						<?php if ($cf_const_site) : ?>
							<?php
							$cf_key_placeholder = esc_attr(get_option('cfturnstile_key'));
							?>
							<p>
								<?php echo esc_html($cf_key_placeholder); ?>
							</p>
							<input type="hidden" name="cfturnstile_key" value="" />
						<?php else : ?>
							<input type="text" style="width: 240px;" name="cfturnstile_key" value="<?php echo esc_attr(get_option('cfturnstile_key')); ?>" />
						<?php endif; ?>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php echo esc_html__('Secret Key', 'simple-cloudflare-turnstile'); ?></th>
					<td>
						<?php $cf_const_secret = ( defined('CF_TURNSTILE_SECRET_KEY') && CF_TURNSTILE_SECRET_KEY ); ?>
						<?php if ($cf_const_secret) : ?>
							<?php // Replace the last 20 characters of key with 20 asterisks
							$cf_secret = esc_attr(get_option('cfturnstile_secret'));
							$cf_secret_placeholder = substr($cf_secret, 0, 20) . '********************';
							?>
							<p>
								<?php echo esc_html($cf_secret_placeholder); ?>
							</p>
							<input type="hidden" name="cfturnstile_secret" value="" />
						<?php else : ?>
							<input type="text" style="width: 240px;" name="cfturnstile_secret" value="<?php echo esc_attr(get_option('cfturnstile_secret')); ?>" />
						<?php endif; ?>
					</td>
				</tr>

			</table>
			
			<?php if(!$cf_const_site || !$cf_const_secret) { ?>
			<?php
			$cf_site_opt = get_option('cfturnstile_key');
			$site_snippet_val = $cf_site_opt ? $cf_site_opt : 'your-site-key';
			$cf_secret_opt = get_option('cfturnstile_secret');
			$secret_snippet_val = $cf_secret_opt ? $cf_secret_opt : 'your-secret-key';
			?>
			<div style="max-width: 760px; margin: 6px 0 24px 0;">
				<a href="#" class="sct-wpconfig-toggle" style="color:#2271b1; text-decoration:none; font-size:13px;">
					<?php echo esc_html__('Optional: Define keys in wp-config.php', 'simple-cloudflare-turnstile'); ?>
					<span class="dashicons dashicons-arrow-down-alt2"
					style="vertical-align: text-bottom; font-size: 12px; display: inline-block; margin-bottom: -7px; width: 15px;"></span>
				</a>
				<div class="sct-wpconfig-details" style="display:none; margin-top:6px;">
					<p>
						<?php echo esc_html__('You can optionally define your API keys in your wp-config.php file so the keys are not stored in the database.', 'simple-cloudflare-turnstile'); ?>
					</p>
					<p>
						<?php echo esc_html__('To do this, add the following lines to your wp-config.php file before the line that says "/* That\'s all, stop editing! Happy publishing. */":', 'simple-cloudflare-turnstile'); ?>
					</p>
					<span style="background: #ffffff; border:1px solid #e1e1e1; padding:10px; display:inline-block;">
					define('CF_TURNSTILE_SITE_KEY', '<?php echo esc_html($site_snippet_val); ?>');
					<br/>
					define('CF_TURNSTILE_SECRET_KEY', '<?php echo esc_html($secret_snippet_val); ?>');
					</span>
					<p>
						<?php echo esc_html__('Warning: This is not required. Only do this if you are comfortable editing wp-config.php. If you define the keys here, they will override the settings above.', 'simple-cloudflare-turnstile'); ?>
					</p>
				</div>
			</div>
			<?php } ?>

			<hr style="margin: 20px 0 10px 0;">

			<table class="form-table">

				<tr valign="top">
					<th scope="row" style="font-size: 19px; padding-bottom: 5px;"><?php echo esc_html__('General Settings:', 'simple-cloudflare-turnstile'); ?></th>
				</tr>

				<tr valign="top">
					<th scope="row"><?php echo esc_html__('Theme', 'simple-cloudflare-turnstile'); ?></th>
					<td>
						<select name="cfturnstile_theme">
							<option value="light" <?php if (!get_option('cfturnstile_theme') || get_option('cfturnstile_theme') == "light") { ?>selected<?php } ?>>
								<?php esc_html_e('Light', 'simple-cloudflare-turnstile'); ?>
							</option>
							<option value="dark" <?php if (get_option('cfturnstile_theme') == "dark") { ?>selected<?php } ?>>
								<?php esc_html_e('Dark', 'simple-cloudflare-turnstile'); ?>
							</option>
							<option value="auto" <?php if (get_option('cfturnstile_theme') == "auto") { ?>selected<?php } ?>>
								<?php esc_html_e('Auto', 'simple-cloudflare-turnstile'); ?>
							</option>
						</select>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php echo esc_html__('Language', 'simple-cloudflare-turnstile'); ?></th>
					<td>
						<select name="cfturnstile_language">
						<?php
						$languages = array(
							'auto'   => esc_html__( 'Auto Detect', 'simple-cloudflare-turnstile' ),
							'ar-eg'  => esc_html__( 'Arabic (Egypt)', 'simple-cloudflare-turnstile' ),
							'bg-bg'  => esc_html__( 'Bulgarian (Bulgaria)', 'simple-cloudflare-turnstile' ),
							'zh-cn'  => esc_html__( 'Chinese (Simplified, China)', 'simple-cloudflare-turnstile' ),
							'zh-tw'  => esc_html__( 'Chinese (Traditional, Taiwan)', 'simple-cloudflare-turnstile' ),
							'hr-hr'  => esc_html__( 'Croatian (Croatia)', 'simple-cloudflare-turnstile' ),
							'cs-cz'  => esc_html__( 'Czech (Czech Republic)', 'simple-cloudflare-turnstile' ),
							'da-dk'  => esc_html__( 'Danish (Denmark)', 'simple-cloudflare-turnstile' ),
							'nl-nl'  => esc_html__( 'Dutch (Netherlands)', 'simple-cloudflare-turnstile' ),
							'en-us'  => esc_html__( 'English (United States)', 'simple-cloudflare-turnstile' ),
							'fa-ir'  => esc_html__( 'Farsi (Iran)', 'simple-cloudflare-turnstile' ),
							'fi-fi'  => esc_html__( 'Finnish (Finland)', 'simple-cloudflare-turnstile' ),
							'fr-fr'  => esc_html__( 'French (France)', 'simple-cloudflare-turnstile' ),
							'de-de'  => esc_html__( 'German (Germany)', 'simple-cloudflare-turnstile' ),
							'el-gr'  => esc_html__( 'Greek (Greece)', 'simple-cloudflare-turnstile' ),
							'he-il'  => esc_html__( 'Hebrew (Israel)', 'simple-cloudflare-turnstile' ),
							'hi-in'  => esc_html__( 'Hindi (India)', 'simple-cloudflare-turnstile' ),
							'hu-hu'  => esc_html__( 'Hungarian (Hungary)', 'simple-cloudflare-turnstile' ),
							'id-id'  => esc_html__( 'Indonesian (Indonesia)', 'simple-cloudflare-turnstile' ),
							'it-it'  => esc_html__( 'Italian (Italy)', 'simple-cloudflare-turnstile' ),
							'ja-jp'  => esc_html__( 'Japanese (Japan)', 'simple-cloudflare-turnstile' ),
							'tlh'    => esc_html__( 'Klingon (Qo’noS)', 'simple-cloudflare-turnstile' ),
							'ko-kr'  => esc_html__( 'Korean (Korea)', 'simple-cloudflare-turnstile' ),
							'lt-lt'  => esc_html__( 'Lithuanian (Lithuania)', 'simple-cloudflare-turnstile' ),
							'ms-my'  => esc_html__( 'Malay (Malaysia)', 'simple-cloudflare-turnstile' ),
							'nb-no'  => esc_html__( 'Norwegian Bokmål (Norway)', 'simple-cloudflare-turnstile' ),
							'pl-pl'  => esc_html__( 'Polish (Poland)', 'simple-cloudflare-turnstile' ),
							'pt-br'  => esc_html__( 'Portuguese (Brazil)', 'simple-cloudflare-turnstile' ),
							'ro-ro'  => esc_html__( 'Romanian (Romania)', 'simple-cloudflare-turnstile' ),
							'ru-ru'  => esc_html__( 'Russian (Russia)', 'simple-cloudflare-turnstile' ),
							'sr-ba'  => esc_html__( 'Serbian (Bosnia and Herzegovina)', 'simple-cloudflare-turnstile' ),
							'sk-sk'  => esc_html__( 'Slovak (Slovakia)', 'simple-cloudflare-turnstile' ),
							'sl-si'  => esc_html__( 'Slovenian (Slovenia)', 'simple-cloudflare-turnstile' ),
							'es-es'  => esc_html__( 'Spanish (Spain)', 'simple-cloudflare-turnstile' ),
							'sv-se'  => esc_html__( 'Swedish (Sweden)', 'simple-cloudflare-turnstile' ),
							'tl-ph'  => esc_html__( 'Tagalog (Philippines)', 'simple-cloudflare-turnstile' ),
							'th-th'  => esc_html__( 'Thai (Thailand)', 'simple-cloudflare-turnstile' ),
							'tr-tr'  => esc_html__( 'Turkish (Turkey)', 'simple-cloudflare-turnstile' ),
							'uk-ua'  => esc_html__( 'Ukrainian (Ukraine)', 'simple-cloudflare-turnstile' ),
							'vi-vn'  => esc_html__( 'Vietnamese (Vietnam)', 'simple-cloudflare-turnstile' ),
						);
						$auto = $languages['auto'];
						unset($languages['auto']);
						asort($languages);
						$languages = array_merge(array('auto' => $auto), $languages);
						foreach ($languages as $code => $name) {
							$selected = '';
							if(get_option('cfturnstile_language') == $code) { $selected = 'selected'; }
							?>
								<option value="<?php echo esc_attr($code); ?>" <?php echo esc_attr($selected); ?>>
									<?php echo esc_html($name); ?>
								</option>
							<?php
						}
						?>
						</select>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<?php echo esc_html__('Disable Submit Button', 'simple-cloudflare-turnstile'); ?>
					</th>
					<td><input type="checkbox" name="cfturnstile_disable_button" <?php if (get_option('cfturnstile_disable_button')) { ?>checked<?php } ?>>
						<i style="font-size: 10px;"><?php echo esc_html__('When enabled, the user will not be able to click submit until the Turnstile challenge is completed.', 'simple-cloudflare-turnstile'); ?></i>
					</td>
				</tr>

			</table>

			<button type="button" class="sct-accordion" id="sct-accordion-whitelist"><?php echo esc_html__('Advanced Settings', 'simple-cloudflare-turnstile'); ?></button>
			<div class="sct-panel">

				<p style="margin: 0 0 15px 0; padding-bottom: 20px; border-bottom: 1px solid #f3f3f3;">
					<?php echo esc_html__('These settings are for more advanced customisation. If you are not sure about these, they do not need to be changed.', 'simple-cloudflare-turnstile'); ?>
				</p>

				<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

					<tr>
						<th scope="row" colspan="2" style="text-align: center; color: #8c8c8c;">
							<?php echo esc_html__('Widget Customization', 'simple-cloudflare-turnstile'); ?>
						</th>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_html__('Widget Size', 'simple-cloudflare-turnstile'); ?></th>
						<td>
							<select name="cfturnstile_size" style="width: 100%;">
								<option value="normal" <?php if (!get_option('cfturnstile_size') || get_option('cfturnstile_size') == "normal") { ?>selected<?php } ?>>
									<?php esc_html_e('Normal (300px)', 'simple-cloudflare-turnstile'); ?>
								</option>
								<option value="flexible" <?php if (get_option('cfturnstile_size') == "flexible") { ?>selected<?php } ?>>
									<?php esc_html_e('Flexible (100%)', 'simple-cloudflare-turnstile'); ?>
								</option>
								<option value="compact" <?php if (get_option('cfturnstile_size') == "compact") { ?>selected<?php } ?>>
									<?php esc_html_e('Compact (150px)', 'simple-cloudflare-turnstile'); ?>
								</option>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_html__('Appearance Mode', 'simple-cloudflare-turnstile'); ?></th>
						<td>
							<select name="cfturnstile_appearance" style="width: 100%;">
							<?php
							$appearances = array(
								'always' => esc_html__( 'Always', 'simple-cloudflare-turnstile' ),
								// 'execute' => esc_html__( 'Execute', 'simple-cloudflare-turnstile' ), // Not really needed
								'interaction-only' => esc_html__( 'Interaction Only', 'simple-cloudflare-turnstile' ),
							);
							foreach ($appearances as $code => $name) {
								$selected = '';
								if(get_option('cfturnstile_appearance') == $code) { $selected = 'selected'; }
								?>
									<option value="<?php echo esc_attr($code); ?>" <?php echo esc_attr($selected); ?>>
										<?php echo esc_html($name); ?>
									</option>
								<?php
							}
							?>
							</select>
							<br/><br/>
							<div class="wcu-appearance-always" style="display: none;"><i style="font-size: 10px;"><?php echo esc_html__( 'Turnstile Widget is always displayed for all visitors.', 'simple-cloudflare-turnstile' ); ?></i></div>
							<div class="wcu-appearance-execute" style="display: none;"><i style="font-size: 10px;"><?php echo esc_html__( 'Turnstile Widget is only displayed after the challenge begins.', 'simple-cloudflare-turnstile' ); ?></i></div>
							<div class="wcu-appearance-interaction-only" style="display: none;"><i style="font-size: 10px;"><?php echo esc_html__( 'Turnstile Widget is only displayed in cases where an interaction is required. This essentially makes it "invisible" for most valid users.', 'simple-cloudflare-turnstile' ); ?></i></div>
						</td>
					</tr>

					<tr>
						<th scope="row" colspan="2" style="text-align: center; color: #8c8c8c;">
							<?php echo esc_html__('Custom Messages', 'simple-cloudflare-turnstile'); ?>
						</th>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_html__('Custom Error Message', 'simple-cloudflare-turnstile'); ?></th>
						<td>
							<textarea type="text" style="width: 202px; margin-bottom: 5px;" name="cfturnstile_error_message"
							placeholder="<?php echo cfturnstile_failed_message(1); ?>"
							/><?php if(get_option('cfturnstile_error_message')) { echo esc_html(get_option('cfturnstile_error_message')); } ?></textarea>
							<br /><i style="font-size: 10px;"><?php echo esc_html__('Shown if the form is submitted without completing the Turnstile challenge. Leave blank to use the default message (localized):', 'simple-cloudflare-turnstile') . ' "' . cfturnstile_failed_message(1) . '"'; ?></i>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php echo esc_html__('Extra Failure Message', 'simple-cloudflare-turnstile'); ?>
						</th>
						<td>
							<input type="checkbox" name="cfturnstile_failure_message_enable" <?php if (get_option('cfturnstile_failure_message_enable', 0)) { ?>checked<?php } ?>>
						</td>
					</tr>
					<tr valign="top" class="cfturnstile-failure-message" style="border: 0;">
						<th scope="row" style="padding-top: 0px;">
							<i style="font-size: 10px;">
								<?php echo esc_html__('HTML Markup Allowed.', 'simple-cloudflare-turnstile'); ?>
							</i>
						</th>
						<td style="padding-top: 0px;">
							<textarea type="text" style="width: 202px; margin-bottom: 5px;" name="cfturnstile_failure_message" rows="3"
							placeholder="<?php echo esc_html__('Failed to verify you are human. Please contact us if you are having issues.', 'simple-cloudflare-turnstile'); ?>"/>
							<?php if(get_option('cfturnstile_failure_message')) { echo esc_html(get_option('cfturnstile_failure_message')); } ?></textarea>
							<i style="font-size: 10px;"><?php echo esc_html__('This will show a message below the Turnstile widget if they receive the "Failure!" response. Useful to give instructions in the *very rare* case a valid user is being flagged as spam.', 'simple-cloudflare-turnstile'); ?></i>
							<br/><br/>
							<i style="font-size: 10px;"><?php echo esc_html__('Currently it is not possible to edit the actual "Failure!" message shown on the widget.', 'simple-cloudflare-turnstile'); ?></i>
						</td>
					</tr>

					<tr>
						<th scope="row" colspan="2" style="text-align: center; color: #8c8c8c;">
							<?php echo esc_html__('Performance', 'simple-cloudflare-turnstile'); ?>
						</th>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php echo esc_html__('Defer Scripts', 'simple-cloudflare-turnstile'); ?>
						</th>
						<td><input style="margin: 5px 0 20px 10px;" type="checkbox" name="cfturnstile_defer_scripts" <?php if (get_option('cfturnstile_defer_scripts', 1)) { ?>checked<?php } ?>>
						<i style="font-size: 10px;"><?php echo esc_html__('When enabled, the javascript files loaded by the plugin will be deferred. You can disable this if it causes any issues with your other optimisations.', 'simple-cloudflare-turnstile'); ?></i>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php echo esc_html__('Performance Plugin Compatibility', 'simple-cloudflare-turnstile'); ?>
						</th>
						<td>
							<input style="margin: 5px 0 20px 10px;" type="checkbox" name="cfturnstile_perf_compat" <?php if ( get_option('cfturnstile_perf_compat', 1) ) { ?>checked<?php } ?>>
							<i style="font-size: 10px;"><?php echo esc_html__('Adds better compatibility with popular performance/optimization plugins (e.g. WP Rocket, LiteSpeed Cache, Autoptimize, Perfmatters, SG Optimizer) to prevent their JS optimizations from breaking Turnstile. Disable only if this causes issues.', 'simple-cloudflare-turnstile'); ?></i>
						</td>
					</tr>
				</table>

			</div>

			<button type="button" class="sct-accordion" id="sct-accordion-whitelist"><?php echo esc_html__('Whitelist Settings', 'simple-cloudflare-turnstile'); ?></button>
			<div class="sct-panel">

				<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

					<tr valign="top">
						<th scope="row">
							<?php echo esc_html__('Logged In Users', 'simple-cloudflare-turnstile'); ?>
						</th>
						<td><input style="margin-top: 5px;" type="checkbox" name="cfturnstile_whitelist_users" <?php if (get_option('cfturnstile_whitelist_users')) { ?>checked<?php } ?>>
							<i style="font-size: 10px;"><?php echo esc_html__('When enabled, logged in users will not see the Turnstile challenge.', 'simple-cloudflare-turnstile'); ?></i>
						</td>
					</tr>
					
					<tr valign="top">
						<th scope="row"><?php echo esc_html__('IP Addresses', 'simple-cloudflare-turnstile'); ?></th>
						<td>
							<textarea style="width: 240px;" name="cfturnstile_whitelist_ips"><?php echo sanitize_textarea_field(get_option('cfturnstile_whitelist_ips')); ?></textarea>
							<br /><i style="font-size: 10px;"><?php echo esc_html__('One per line. Wildcards are not supported. All visitors with listed IP addresses will not see the Turnstile challenge. Warning: If an attacker knows one of the whitelisted IP addresses, they might be able to spoof that address to bypass Turnstile.', 'simple-cloudflare-turnstile'); ?></i>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php echo esc_html__('User Agents', 'simple-cloudflare-turnstile'); ?></th>
						<td>
							<textarea style="width: 240px;" name="cfturnstile_whitelist_agents"><?php echo sanitize_textarea_field(get_option('cfturnstile_whitelist_agents')); ?></textarea>
							<br /><i style="font-size: 10px;"><?php echo esc_html__('One per line.  All visitors with listed User Agents will not see the Turnstile challenge. Warning: If an attacker knows one of the whitelisted User Agents, they might be able to spoof that User Agent to bypass Turnstile.', 'simple-cloudflare-turnstile'); ?></i>
						</td>
					</tr>

				</table>

			</div>

			<hr style="margin: 40px 0 10px 0;">

			<div class="sct-integrations">

			<table class="form-table" style="margin-bottom: -35px;">

				<tr valign="top">
					<th scope="row">
						<span style="font-size: 19px;"><?php echo esc_html__('Enable Turnstile on your forms:', 'simple-cloudflare-turnstile'); ?></span>
						<p><?php echo esc_html__('Select the dropdown for each integration, and choose when specific forms you want to enable Turnstile on.', 'simple-cloudflare-turnstile'); ?></p>
					</th>
				</tr>

			</table>

			<button type="button" class="sct-accordion" id="sct-accordion-wordpress"><?php echo esc_html__('Default WordPress Forms', 'simple-cloudflare-turnstile'); ?></button>
			<div class="sct-panel">

				<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

					<tr valign="top">
						<th scope="row">
							<?php echo esc_html__('WordPress Login', 'simple-cloudflare-turnstile'); ?> <a href="#" class="cfturnstile_toggle_login" style="font-size: 10px; text-decoration: none; color: #333;">&#9660;</a>
							<span id="cfturnstile_login_only_option" style="display: none;" title="<?php echo esc_html__('Enable this option to only enable on default WordPress login form at wp-login.php', 'simple-cloudflare-turnstile'); ?>">
							<br/><br/>
								<label style="float: left; margin: -5px 10px 0px 0; font-weight: 600; font-size: 10px;" for="cfturnstile_login_only"><?php echo esc_html__('Only enable on default wp-login.php page', 'simple-cloudflare-turnstile'); ?></label>
								<input style="float: left; transform: scale(0.75); margin-top: -7px; margin-left: -5px;"
								type="checkbox" name="cfturnstile_login_only" <?php if (get_option('cfturnstile_login_only')) { ?>checked<?php } ?>>
							</span>
						</th>
						<td><input type="checkbox" name="cfturnstile_login" <?php if (get_option('cfturnstile_login')) { ?>checked<?php } ?>></td>
					</tr>
					<script>
					jQuery(document).ready(function() {
						jQuery('.cfturnstile_toggle_login').click(function(e) {
							e.preventDefault();
							jQuery('#cfturnstile_login_only_option').toggle();
						});
					});
					</script>

					<tr valign="top">
						<th scope="row">
							<?php echo esc_html__('WordPress Register', 'simple-cloudflare-turnstile'); ?> <a href="#" class="cfturnstile_toggle_register" style="font-size: 10px; text-decoration: none; color: #333;">&#9660;</a>
							<span id="cfturnstile_register_only_option" style="display: none;" title="<?php echo esc_html__('Enable this option to only enable on default WordPress register form at wp-login.php?action=register', 'simple-cloudflare-turnstile'); ?>">
							<br/><br/>
								<label style="float: left; margin: -5px 10px 0px 0; font-weight: 600; font-size: 10px;" for="cfturnstile_register_only"><?php echo esc_html__('Only enable on default wp-login.php page', 'simple-cloudflare-turnstile'); ?></label>
								<input style="float: left; transform: scale(0.75); margin-top: -7px; margin-left: -5px;"
								type="checkbox" name="cfturnstile_register_only" <?php if (get_option('cfturnstile_register_only')) { ?>checked<?php } ?>>
							</span>
						</th>
						<td><input type="checkbox" name="cfturnstile_register" <?php if (get_option('cfturnstile_register')) { ?>checked<?php } ?>></td>
					</tr>
					<script>
					jQuery(document).ready(function() {
						jQuery('.cfturnstile_toggle_register').click(function(e) {
							e.preventDefault();
							jQuery('#cfturnstile_register_only_option').toggle();
						});
					});
					</script>

					<tr valign="top">
						<th scope="row">
							<?php echo esc_html__('WordPress Reset Password', 'simple-cloudflare-turnstile'); ?>
						</th>
						<td><input type="checkbox" name="cfturnstile_reset" <?php if (get_option('cfturnstile_reset')) { ?>checked<?php } ?>></td>
					</tr>

					<tr valign="top" style="border: 0;">
						<th scope="row">
							<?php echo esc_html__('WordPress Comment', 'simple-cloudflare-turnstile'); ?> <a href="#" class="cfturnstile_toggle_comments" style="font-size: 10px; text-decoration: none; color: #333;">&#9660;</a>
							<span id="cfturnstile_ajax_comments_option" style="display: none;" title="<?php echo esc_html__('Enable this if you are using an AJAX based comments form plugin or theme.', 'simple-cloudflare-turnstile'); ?>">
							<br/><br/>
								<label style="float: left; margin: -5px 10px 0px 0; font-weight: 600; font-size: 10px;" for="cfturnstile_ajax_comments"><?php echo esc_html__('AJAX comments form?', 'simple-cloudflare-turnstile'); ?></label>
								<input style="float: left; transform: scale(0.75); margin-top: -7px; margin-left: -5px;"
								type="checkbox" name="cfturnstile_ajax_comments"
								<?php if(!cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php') && !cft_is_plugin_active('wp-ajaxify-comments/wp-ajaxify-comments.php')) { ?>
								<?php if (get_option('cfturnstile_ajax_comments')) { ?>checked<?php } ?>>
								<?php } else { ?>checked disabled<?php } ?>
							</span>
						</th>
						<td>
							<input type="checkbox" name="cfturnstile_comment" <?php if (get_option('cfturnstile_comment')) { ?>checked<?php } ?>>
							<?php if (cft_is_plugin_active('jetpack/jetpack.php')) { ?>
								<br /><i style="font-size: 10px;"><?php echo esc_html__('Due to Jetpack limitations, this does NOT currently work with Jetpack comments form enabled.', 'simple-cloudflare-turnstile'); ?></i>
							<?php } ?>
							<?php if (cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php')) { ?>
								<i style="font-size: 11px;"><?php echo esc_html__('Compatible with wpDiscuz!', 'simple-cloudflare-turnstile'); ?></i>
							<?php } ?>
						</td>
					</tr>
					<script>
						jQuery(document).ready(function() {
							jQuery('.cfturnstile_toggle_comments').click(function(e) {
								e.preventDefault();
								jQuery('#cfturnstile_ajax_comments_option').toggle();
							});
						});
					</script>

				</table>

			</div>

			<?php $not_installed = array(); ?>

			<?php // WooCommerce
			if (cft_is_plugin_active('woocommerce/woocommerce.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('WooCommerce Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('WooCommerce Login', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_woo_login" <?php if (get_option('cfturnstile_woo_login')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('WooCommerce Register', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_woo_register" <?php if (get_option('cfturnstile_woo_register')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('WooCommerce Reset Password', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_woo_reset" <?php if (get_option('cfturnstile_woo_reset')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('WooCommerce Checkout', 'simple-cloudflare-turnstile'); ?>
								<br /><br />
								- <?php echo esc_html__('Guest Checkout Only', 'simple-cloudflare-turnstile'); ?>
								<br /><br />
								- <?php echo esc_html__('Widget Location', 'simple-cloudflare-turnstile'); ?>
								<br/><br/>
							</th>
							<td>
								<input style="margin-top: 5px;" type="checkbox" name="cfturnstile_woo_checkout" <?php if (get_option('cfturnstile_woo_checkout')) { ?>checked<?php } ?>>
								<br /><br />
								<input style="margin-top: 5px;" type="checkbox" name="cfturnstile_guest_only" <?php if (get_option('cfturnstile_guest_only')) { ?>checked<?php } ?>>
								<br /><br />
								<select name="cfturnstile_woo_checkout_pos">
									<option value="beforepay" <?php if (!get_option('cfturnstile_woo_checkout_pos') || get_option('cfturnstile_woo_checkout_pos') == "beforepay") { ?>selected<?php } ?>>
										<?php esc_html_e('Before Payment', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="afterpay" <?php if (get_option('cfturnstile_woo_checkout_pos') == "afterpay") { ?>selected<?php } ?>>
										<?php esc_html_e('After Payment', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="beforesubmit" <?php if (get_option('cfturnstile_woo_checkout_pos') == "beforesubmit") { ?>selected<?php } ?>>
										<?php esc_html_e('Before Pay Button', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="beforebilling" <?php if (get_option('cfturnstile_woo_checkout_pos') == "beforebilling") { ?>selected<?php } ?>>
										<?php esc_html_e('Before Billing', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="afterbilling" <?php if (get_option('cfturnstile_woo_checkout_pos') == "afterbilling") { ?>selected<?php } ?>>
										<?php esc_html_e('After Billing', 'simple-cloudflare-turnstile'); ?>
									</option>
								</select>
							</td>
						</tr>

						<tr valign="top" style="border-bottom: 1px solid #f3f3f3;">
							<th scope="row">
								<?php echo esc_html__('WooCommerce Pay for Order', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_woo_checkout_pay" <?php if (get_option('cfturnstile_woo_checkout_pay')) { ?>checked<?php } ?>></td>
						</tr>

						<?php
						// Visual-only WooPayments Express indicators (not saved).
						$wcpay_active   = function_exists('cft_is_plugin_active') ? cft_is_plugin_active('woocommerce-payments/woocommerce-payments.php') : false;
						$wcstripe_active = function_exists('cft_is_plugin_active') ? cft_is_plugin_active('woocommerce-gateway-stripe/woocommerce-gateway-stripe.php') : false;
						if ( $wcpay_active ) {
						?>
						<tr valign="top">
						<td colspan="2"
						style="padding: 20px 0 0 0;">
							<i style="font-size: 10px;">
								<?php echo esc_html__('Note: Currently Turnstile is not able to perform spam checks for some "Express Checkout" payment methods (e.g. PayPal, Google Pay, Apple Pay, Amazon Pay) and will skip them automatically.', 'simple-cloudflare-turnstile'); ?>
							</i>
							<br/>
						</td>
						<?php } ?>

					</table>

					<?php if ( class_exists( 'WooCommerce' ) ) { ?>

						<?php $available_gateways = WC()->payment_gateways->get_available_payment_gateways(); ?>

						<?php if(!empty($available_gateways)) { ?>

							<br/>

							<p style="font-weight: 600;">
								<?php echo esc_html__('Payment Methods to Skip', 'simple-cloudflare-turnstile'); ?> <a href="#" class="cfturnstile_toggle_skip_methods" style="font-size: 10px; text-decoration: none; color: #333;">&#9660;</a>
							</p>
							<script>
								jQuery(document).ready(function() {
									jQuery('.cfturnstile_toggle_skip_methods').click(function(e) {
										e.preventDefault();
										jQuery('#toggleContentSkipMethods').toggle();
									});
								});
							</script>

							<div id="toggleContentSkipMethods" style="display: none;"> <!-- Initially hidden -->
							
								<i style="font-size: 10px;">
									<?php echo esc_html__("If selected below, Turnstile check will not be run for that specific payment method.", 'simple-cloudflare-turnstile'); ?>
								</i>

								<?php
								$selected_payment_methods = get_option('cfturnstile_selected_payment_methods', array());
								if(!$selected_payment_methods) $selected_payment_methods = array();
								if(!empty($available_gateways)) { ?>
								<div style="margin-top: 10px; max-width: 200px;">
								<?php foreach ( $available_gateways as $gateway ) : ?>
								<p>
									<input type="checkbox" name="cfturnstile_selected_payment_methods[]" style="float: none; margin-top: 2px;"
									value="<?php echo esc_attr( $gateway->id ); ?>" <?php echo in_array( $gateway->id, $selected_payment_methods, true ) ? 'checked' : ''; ?> >
									<label><?php echo esc_html( $gateway->get_title() ); ?></label>
								</p>
								<?php endforeach; ?>
								</div>
								<?php } ?>

								<?php
								// Visual-only WooPayments Express indicators (not saved).
								$wcpay_active   = function_exists('cft_is_plugin_active') ? cft_is_plugin_active('woocommerce-payments/woocommerce-payments.php') : false;
								if ( $wcpay_active ) {
									$wcpay_settings = get_option( 'woocommerce_woocommerce_payments_settings', array() );
									$payment_request_enabled = false;
									// Older/newer keys that may indicate payment request buttons are enabled.
									if ( isset( $wcpay_settings['payment_request'] ) ) {
										$payment_request_enabled = ( 'yes' === $wcpay_settings['payment_request'] );
									} elseif ( isset( $wcpay_settings['payment_request_enabled'] ) ) {
										$payment_request_enabled = ( 'yes' === $wcpay_settings['payment_request_enabled'] );
									}
									$upe_ids = array();
									if ( isset( $wcpay_settings['enabled_payment_method_ids'] ) ) {
										$upe_ids = (array) $wcpay_settings['enabled_payment_method_ids'];
									} elseif ( isset( $wcpay_settings['upe_enabled_payment_method_ids'] ) ) {
										$upe_ids = (array) $wcpay_settings['upe_enabled_payment_method_ids'];
									}
									$link_enabled = in_array( 'link', $upe_ids, true );
									?>
									<hr style="margin: 12px 0 12px 0; border: 0; border-top: 1px solid #f3f3f3;" />
									<p style="font-weight: 600; margin: 8px 0 4px 0;">
										<?php echo esc_html__( 'WooPayments Express', 'simple-cloudflare-turnstile' ); ?>
									</p>
									<div style="max-width: 200px;">
										<p>
											<input type="checkbox" disabled <?php checked( $payment_request_enabled ); ?>
											style="float: none; margin-top: 2px;" />
											<label><?php echo esc_html__( 'Apple Pay', 'simple-cloudflare-turnstile' ); ?></label>
										</p>
										<p>
											<input type="checkbox" disabled <?php checked( $payment_request_enabled ); ?>
											style="float: none; margin-top: 2px;" />
											<label><?php echo esc_html__( 'Google Pay', 'simple-cloudflare-turnstile' ); ?></label>
										</p>
										<p>
											<input type="checkbox" disabled <?php checked( $link_enabled ); ?>
											style="float: none; margin-top: 2px;" />
											<label><?php echo esc_html__( 'Link by Stripe', 'simple-cloudflare-turnstile' ); ?></label>
										</p>
									</div>
								<?php } ?>

							</div>

					<?php } ?>

				<?php } ?>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . esc_html__('WooCommerce', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // EDD
			if (cft_is_plugin_active('easy-digital-downloads/easy-digital-downloads.php') || cft_is_plugin_active('easy-digital-downloads-pro/easy-digital-downloads.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Easy Digital Downloads', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('EDD Checkout', 'simple-cloudflare-turnstile'); ?>
								<br /><br />
								- <?php echo esc_html__('Guest Checkout Only', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td>
								<input type="checkbox" name="cfturnstile_edd_checkout" <?php if (get_option('cfturnstile_edd_checkout')) { ?>checked<?php } ?>>
								<br /><br />
								<input type="checkbox" name="cfturnstile_edd_guest_only" <?php if (get_option('cfturnstile_edd_guest_only')) { ?>checked<?php } ?>>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('EDD Login', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_edd_login" <?php if (get_option('cfturnstile_edd_login')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('EDD Register', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_edd_register" <?php if (get_option('cfturnstile_edd_register')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/easy-digital-downloads/" target="_blank">' . esc_html__('Easy Digital Downloads', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Paid Memberships PRO
			if (cft_is_plugin_active('paid-memberships-pro/paid-memberships-pro.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Paid Memberships Pro', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Checkout / Registration', 'simple-cloudflare-turnstile'); ?>
								<br /><br />
								- <?php echo esc_html__('Guest Checkout Only', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td>
								<input type="checkbox" name="cfturnstile_pmp_checkout" <?php if (get_option('cfturnstile_pmp_checkout')) { ?>checked<?php } ?>>
								<br /><br />
								<input type="checkbox" name="cfturnstile_pmp_guest_only" <?php if (get_option('cfturnstile_pmp_guest_only')) { ?>checked<?php } ?>>
							</td>
						</tr>

						<script>
						jQuery(document).ready(function(){
							jQuery("input[name='cfturnstile_login']").change(function(){
								if(jQuery("input[name='cfturnstile_login']").is(':checked')){
									jQuery('#cfturnstile_pmp_login').prop('checked', true);
								} else {
									jQuery('#cfturnstile_pmp_login').prop('checked', false);
								}
							});
						});
						</script>
						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Login Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type='checkbox' name='cfturnstile_pmp_login' id='cfturnstile_pmp_login' <?php if (get_option('cfturnstile_login')) { ?>checked<?php } ?>
							title='<?php echo esc_html__('Edit via "WordPress Login" option in the "Default WordPress Forms" settings.', 'simple-cloudflare-turnstile'); ?>' disabled></td>
						</tr>

						<!-- Lost Password -->
						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Lost Password Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type='checkbox' name='cfturnstile_wpuf_reset' id='cfturnstile_wpuf_reset'
							title='<?php echo esc_html__('Currently Turnstile can not be implemented on the lost password form when PMP is installed.', 'simple-cloudflare-turnstile'); ?>'
							disabled></td>
						</tr>
						<!-- Set name="cfturnstile_reset" to disabled and unchecked -->
						<script>
						jQuery(document).ready(function(){
							jQuery("input[name='cfturnstile_reset']").prop('disabled', true);
							jQuery("input[name='cfturnstile_reset']").prop('checked', false);
							jQuery("input[name='cfturnstile_reset']").attr('title', '<?php echo esc_html__('Currently Turnstile can not be implemented on the lost password form when PMP is installed.', 'simple-cloudflare-turnstile'); ?>');
						});
						</script>
						<!-- Show X inside checkbox -->
						<style>
						#cfturnstile_wpuf_reset:after, input[name='cfturnstile_reset']:after {
							content: "X";
							color: #333;
							font-weight: bold;
							font-size: 15px;
							position: absolute;
							margin-left: -5px;
							margin-top: 7px;
						}
						</style>
						
					</table>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://en-gb.wordpress.org/plugins/paid-memberships-pro/" target="_blank">' . esc_html__('Paid Memberships PRO', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Contact Form 7
			if (cft_is_plugin_active('contact-form-7/wp-contact-form-7.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Contact Form 7', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Enable on all CF7 Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_cf7_all" <?php if (get_option('cfturnstile_cf7_all')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<br />

					<?php echo esc_html__('To add Turnstile to individual Contact Form 7 forms, simply add this shortcode to any of your forms (in the form editor):', 'simple-cloudflare-turnstile'); ?>
					<br /><span style="color: red; font-weight: bold;">[cf7-simple-turnstile]</span>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">' . esc_html__('Contact Form 7', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // WPForms
			if (cft_is_plugin_active('wpforms-lite/wpforms.php') || cft_is_plugin_active('wpforms/wpforms.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('WPForms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Enable on all WPForms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_wpforms" <?php if (get_option('cfturnstile_wpforms')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo esc_html__('When enabled, Turnstile will be added before/after the submit button, on ALL your forms created with WPForms.', 'simple-cloudflare-turnstile'); ?>
					<?php echo esc_html__('Note: WPForms has an option to configure Turnstile on its own Settings page "CAPTCHA" tab. You should only enable it in one place, either here -OR- in those settings.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -15px;">

						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Widget Location', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<select name="cfturnstile_wpforms_pos">
									<option value="before" <?php if (!get_option('cfturnstile_wpforms_pos') || get_option('cfturnstile_wpforms_pos') == "before") { ?>selected<?php } ?>>
										<?php esc_html_e('Before Button', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="after" <?php if (get_option('cfturnstile_wpforms_pos') == "after") { ?>selected<?php } ?>>
										<?php esc_html_e('After Button', 'simple-cloudflare-turnstile'); ?>
									</option>
								</select>
							</td>
						</tr>

					</table>

					<table class="form-table" style="margin-bottom: -10px;">
						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Disabled Form IDs', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<input type="text" name="cfturnstile_wpforms_disable" value="<?php echo esc_html(get_option('cfturnstile_wpforms_disable')); ?>" />
							</td>
						</tr>
					</table>
					<i style="font-size: 10px;">
						<?php echo sprintf(__('If you want to DISABLE the Turnstile widget on certain forms, enter the %s ID in this field.', 'simple-cloudflare-turnstile'), 'WPForms Form'); ?>
						<?php echo esc_html__('Separate each ID with a comma, for example: 5,10', 'simple-cloudflare-turnstile'); ?>
					</i>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">' . esc_html__('WPForms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Gravity Forms
			if (cft_is_plugin_active('gravityforms/gravityforms.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Gravity Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Enable on all Gravity Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_gravity" <?php if (get_option('cfturnstile_gravity')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo esc_html__('When enabled, Turnstile will be added before/after the submit button, on ALL your forms created with Gravity Forms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -15px;">

						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Widget Location', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<select name="cfturnstile_gravity_pos">
									<option value="before" <?php if (!get_option('cfturnstile_gravity_pos') || get_option('cfturnstile_gravity_pos') == "before") { ?>selected<?php } ?>>
										<?php esc_html_e('Before Button', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="after" <?php if (get_option('cfturnstile_gravity_pos') == "after") { ?>selected<?php } ?>>
										<?php esc_html_e('After Button', 'simple-cloudflare-turnstile'); ?>
									</option>
								</select>
							</td>
						</tr>

					</table>

					<table class="form-table" style="margin-bottom: -10px;">
						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Disabled Form IDs', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<input type="text" name="cfturnstile_gravity_disable" value="<?php echo esc_html(get_option('cfturnstile_gravity_disable')); ?>" />
							</td>
						</tr>
					</table>
					<i style="font-size: 10px;">
						<?php echo sprintf(__('If you want to DISABLE the Turnstile widget on certain forms, enter the %s ID in this field.', 'simple-cloudflare-turnstile'), 'Gravity Form'); ?>
						<?php echo esc_html__('Separate each ID with a comma, for example: 5,10', 'simple-cloudflare-turnstile'); ?>
					</i>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://www.gravityforms.com/?utm_source=simplecloudflareturnstile" target="_blank">' . esc_html__('Gravity Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Fluent Forms
			if (cft_is_plugin_active('fluentform/fluentform.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Fluent Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Enable on all Fluent Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_fluent" <?php if (get_option('cfturnstile_fluent')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo esc_html__('When enabled, Turnstile will be added above the submit button, on ALL your forms created with Fluent Forms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -10px;">
						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Disabled Form IDs', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<input type="text" name="cfturnstile_fluent_disable" value="<?php echo esc_html(get_option('cfturnstile_fluent_disable')); ?>" />
							</td>
						</tr>
					</table>
					<i style="font-size: 10px;">
						<?php echo sprintf(__('If you want to DISABLE the Turnstile widget on certain forms, enter the %s ID in this field.', 'simple-cloudflare-turnstile'), 'Fluent Form'); ?>
						<?php echo esc_html__('Separate each ID with a comma, for example: 5,10', 'simple-cloudflare-turnstile'); ?>
					</i>

				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/fluentform/" target="_blank">' . esc_html__('Fluent Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Jetpack Forms
			if (cft_is_plugin_active('jetpack/jetpack.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Jetpack Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Enable on all Jetpack Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_jetpack" <?php if (get_option('cfturnstile_jetpack')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo esc_html__('When enabled, Turnstile will be added after the submit button, on ALL your forms created with Jetpack Forms.', 'simple-cloudflare-turnstile'); ?>
				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/jetpack/" target="_blank">' . esc_html__('Jetpack Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Formidable Forms
			if (cft_is_plugin_active('formidable/formidable.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Formidable Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Enable on all Formidable Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_formidable" <?php if (get_option('cfturnstile_formidable')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo esc_html__('When enabled, Turnstile will be added above the submit button, on ALL your forms created with Formidable Forms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -15px;">

						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Widget Location', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<select name="cfturnstile_formidable_pos">
									<option value="before" <?php if (!get_option('cfturnstile_formidable_pos') || get_option('cfturnstile_formidable_pos') == "before") { ?>selected<?php } ?>>
										<?php esc_html_e('Before Button', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="after" <?php if (get_option('cfturnstile_formidable_pos') == "after") { ?>selected<?php } ?>>
										<?php esc_html_e('After Button', 'simple-cloudflare-turnstile'); ?>
									</option>
								</select>
							</td>
						</tr>

					</table>
				
					<table class="form-table" style="margin-bottom: -10px;">
						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Disabled Form IDs', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<input type="text" name="cfturnstile_formidable_disable" value="<?php echo esc_html(get_option('cfturnstile_formidable_disable')); ?>" />
							</td>
						</tr>
					</table>
					<i style="font-size: 10px;">
						<?php echo sprintf(__('If you want to DISABLE the Turnstile widget on certain forms, enter the %s ID in this field.', 'simple-cloudflare-turnstile'), 'Formidable Form'); ?>
						<?php echo esc_html__('Separate each ID with a comma, for example: 5,10', 'simple-cloudflare-turnstile'); ?>
					</i>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/formidable/" target="_blank">' . esc_html__('Formidable', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>
			
			<?php // Forminator Forms
			if (cft_is_plugin_active('forminator/forminator.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Forminator Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Enable on all Forminator Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_forminator" <?php if (get_option('cfturnstile_forminator')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo esc_html__('When enabled, Turnstile will be added above the submit button, on ALL your forms created with Forminator Forms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -15px;">

						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Widget Location', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<select name="cfturnstile_forminator_pos">
									<option value="before" <?php if (!get_option('cfturnstile_forminator_pos') || get_option('cfturnstile_forminator_pos') == "before") { ?>selected<?php } ?>>
										<?php esc_html_e('Before Button', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="after" <?php if (get_option('cfturnstile_forminator_pos') == "after") { ?>selected<?php } ?>>
										<?php esc_html_e('After Button', 'simple-cloudflare-turnstile'); ?>
									</option>
								</select>
							</td>
						</tr>

					</table>

					<table class="form-table" style="margin-bottom: -10px;">
						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Disabled Form IDs', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<input type="text" name="cfturnstile_forminator_disable" value="<?php echo esc_html(get_option('cfturnstile_forminator_disable')); ?>" />
							</td>
						</tr>
					</table>
					<i style="font-size: 10px;">
						<?php echo sprintf(__('If you want to DISABLE the Turnstile widget on certain forms, enter the %s ID in this field.', 'simple-cloudflare-turnstile'), 'Forminator Form'); ?>
						<?php echo esc_html__('Separate each ID with a comma, for example: 5,10', 'simple-cloudflare-turnstile'); ?>
					</i>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/forminator/" target="_blank">' . esc_html__('Forminator', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // WS Form
			if (cft_is_plugin_active('ws-form/ws-form.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('WS Form', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<p>
						<?php echo esc_html__('Currently WS Form is not supported by this plugin, however their plugin does have its own Turnstile addon.', 'simple-cloudflare-turnstile'); ?>
						<a href="https://wsform.com/knowledgebase/turnstile/?utm_source=simplecloudflareturnstile" target="_blank"><?php echo esc_html__('Click here for more information.', 'simple-cloudflare-turnstile'); ?></a>
					</p>

				</div>
			<?php
			}
			?>

			<?php // Ninja Forms
			if (cft_is_plugin_active('ninja-forms/ninja-forms.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Ninja Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<p>
						<?php echo esc_html__('Currently Ninja Forms is not supported by this plugin.', 'simple-cloudflare-turnstile'); ?>
					</p>

				</div>
			<?php
			}
			?>

			<?php // Elementor Forms
			if ( cft_is_plugin_active('elementor-pro/elementor-pro.php') || cft_is_plugin_active('pro-elements/pro-elements.php') ) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Elementor Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Enable on all Elementor Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_elementor" <?php if (get_option('cfturnstile_elementor')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo esc_html__('When enabled, Turnstile will be added above the submit button, on ALL your forms created with Elementor Pro Forms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -15px;">

						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Widget Location', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<select name="cfturnstile_elementor_pos">
									<option value="before" <?php if (!get_option('cfturnstile_elementor_pos') || get_option('cfturnstile_elementor_pos') == "before") { ?>selected<?php } ?>>
										<?php esc_html_e('Before Button', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="after" <?php if (get_option('cfturnstile_elementor_pos') == "after") { ?>selected<?php } ?>>
										<?php esc_html_e('After Button', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="afterform" <?php if (get_option('cfturnstile_elementor_pos') == "afterform") { ?>selected<?php } ?>>
										<?php esc_html_e('After Form', 'simple-cloudflare-turnstile'); ?>
									</option>
								</select>
							</td>
						</tr>

					</table>

					<table class="form-table" style="margin-bottom: -10px;">
						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Integration Method', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<select name="cfturnstile_elementor_method" id="cfturnstile_elementor_method"
								style="width: 100%; min-width: 200px; max-width: 400px;">
									<option value="element" <?php if (!get_option('cfturnstile_elementor_method') || get_option('cfturnstile_elementor_method') == "element") { ?>selected<?php } ?>>
										<?php esc_html_e('Load Scripts Via Element', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="global" <?php if (get_option('cfturnstile_elementor_method') == "global") { ?>selected<?php } ?>>
										<?php esc_html_e('Load Scripts Globally', 'simple-cloudflare-turnstile'); ?>
									</option>
								</select>
								<br/>
								<div id="cfturnstile-elementor-method-element" class="cfturnstile-elementor-method-description"
								style="margin-top: 8px; font-size: 10px; font-style: italic; display: inline-block; <?php if (get_option('cfturnstile_elementor_method') == "global") { ?>display: none;<?php } ?>">
									<?php esc_html_e('(Recommended) Scripts are loaded when each form element is rendered. This is the ideal method but may not work with some caching. Have issues? Try disabling Element caching.', 'simple-cloudflare-turnstile'); ?>
								</div>
								<div id="cfturnstile-elementor-method-global" class="cfturnstile-elementor-method-description"
								style="margin-top: 8px; font-size: 10px; font-style: italic; display: inline-block; <?php if (!get_option('cfturnstile_elementor_method') || get_option('cfturnstile_elementor_method') == "element") { ?>display: none;<?php } ?>">
									<?php esc_html_e('Scripts are loaded globally on all Elementor pages. This method works better with some caching but may load scripts on pages without forms.', 'simple-cloudflare-turnstile'); ?>
									<?php esc_html_e('Instead of using this option, try disabling Element caching first.', 'simple-cloudflare-turnstile'); ?>

								</div>
                                
								<!-- Elementor Global Method: Scope options -->
								<div id="cfturnstile-elementor-global-controls" style="margin-top:12px; <?php if (!get_option('cfturnstile_elementor_method') || get_option('cfturnstile_elementor_method') == 'element') { ?>display:none;<?php } ?>">
									<?php 
									$scope = get_option('cfturnstile_elementor_global_scope', '');
									if ($scope === '') { $scope = 'all'; }
									?>
									<label for="cfturnstile_elementor_global_scope" style="display:block; margin-bottom:6px;">
										<?php echo esc_html__('Load scripts on:', 'simple-cloudflare-turnstile'); ?>
									</label>
									<select name="cfturnstile_elementor_global_scope" id="cfturnstile_elementor_global_scope" style="width:100%; max-width:400px;">
										<option value="all" <?php if ($scope === 'all') { ?>selected<?php } ?>><?php echo esc_html__('All Elementor pages', 'simple-cloudflare-turnstile'); ?></option>
										<option value="autodetect" <?php if ($scope === 'autodetect') { ?>selected<?php } ?>><?php echo esc_html__('Autodetect pages with forms', 'simple-cloudflare-turnstile'); ?></option>
										<option value="specific" <?php if ($scope === 'specific') { ?>selected<?php } ?>><?php echo esc_html__('Enter specific page IDs', 'simple-cloudflare-turnstile'); ?></option>
									</select>
									<div class="cfturnstile-elementor-scope-description" id="cfturnstile-elementor-scope-all" style="margin-top:6px; font-size:10px; font-style: italic; <?php if ($scope !== 'all') { ?>display:none;<?php } ?>">
										<?php echo esc_html__('Loads scripts on all Elementor pages. Use this if caching breaks the element-based method. May load scripts on pages without forms.', 'simple-cloudflare-turnstile'); ?>
									</div>
									<div class="cfturnstile-elementor-scope-description" id="cfturnstile-elementor-scope-autodetect" style="margin-top:6px; font-size:10px; font-style: italic; <?php if ($scope !== 'autodetect') { ?>display:none;<?php } ?>">
										<?php echo esc_html__('Loads scripts only on pages that include an Elementor Form or Login widget. Popups or global templates may not be detected.', 'simple-cloudflare-turnstile'); ?>
									</div>
									<div class="cfturnstile-elementor-scope-description" id="cfturnstile-elementor-scope-specific" style="margin-top:6px; font-size:10px; font-style: italic; <?php if ($scope !== 'specific') { ?>display:none;<?php } ?>">
										<?php echo esc_html__('Loads scripts only on the page IDs you enter below. Leave the list empty to load nowhere.', 'simple-cloudflare-turnstile'); ?>
									</div>

									<div id="cfturnstile-elementor-global-pages-wrap" class="cfturnstile-elementor-global-pages" style="<?php if ($scope !== 'specific') { ?>display:none;<?php } ?>">
										<label for="cfturnstile_elementor_global_pages" style="display:block; margin-bottom:4px; font-size:12px;">
											<?php echo esc_html__('Page IDs (comma-separated)', 'simple-cloudflare-turnstile'); ?>:
										</label>
										<input type="text" name="cfturnstile_elementor_global_pages" id="cfturnstile_elementor_global_pages" value="<?php echo esc_attr( get_option('cfturnstile_elementor_global_pages', '') ); ?>" placeholder="e.g. 12,34,56" style="width:100%; max-width:400px;">
									</div>
								</div>
							</td>
						</tr>
					</table>

					<!-- Notice to disable element caching -->
					<div>
						<p style="font-style: italic;">
							<span class="dashicons dashicons-warning" style="margin-top: 2px;"></span> <?php echo wp_kses_post( sprintf( 
								__('If the Turnstile widget is not showing, disable <a href="%s" target="_blank">Element Caching</a> for the Elementor form on your page, or try switching Integration Method.', 'simple-cloudflare-turnstile'),
								'https://elementor.com/help/element-caching-help/' ) ); ?>
							</p>
					</div>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://elementor.com/features/form-builder/?utm_source=simplecloudflareturnstile" target="_blank">' . esc_html__('Elementor Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>
	
			<?php // Mailchimp for WordPress
			if (cft_is_plugin_active('mailchimp-for-wp/mailchimp-for-wp.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('MC4WP: Mailchimp for WordPress', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<?php echo esc_html__('To add Turnstile to Mailchimp for WordPress, simply add this shortcode to any of your forms (in the form editor):', 'simple-cloudflare-turnstile'); ?>
					<br /><span style="color: red; font-weight: bold;">[mc4wp-simple-turnstile]</span>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/mailchimp-for-wp/" target="_blank">' . esc_html__('Mailchimp for WordPress', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // MailPoet
			if (cft_is_plugin_active('mailpoet/mailpoet.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('MailPoet', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Enable on all MailPoet Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_mailpoet" <?php if (get_option('cfturnstile_mailpoet')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo esc_html__('When enabled, Turnstile will be added above the submit button, on ALL your forms created with MailPoet.', 'simple-cloudflare-turnstile'); ?>

				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/mailpoet/" target="_blank">' . esc_html__('MailPoet', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Kadence Forms
			if (cft_is_plugin_active('kadence-blocks/kadence-blocks.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Kadence Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Enable on all Kadence Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_kadence" <?php if (get_option('cfturnstile_kadence')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo esc_html__('When enabled, Turnstile will be added above the submit button, on ALL your forms created with Kadence Forms.', 'simple-cloudflare-turnstile'); ?>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/kadence-blocks/" target="_blank">' . esc_html__('Kadence Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // BuddyPress
			if (cft_is_plugin_active('buddypress/bp-loader.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('BuddyPress', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('BuddyPress Register', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_bp_register" <?php if (get_option('cfturnstile_bp_register')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/buddypress/" target="_blank">' . esc_html__('BuddyPress', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // bbPress
			if (cft_is_plugin_active('bbpress/bbpress.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('bbPress', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('bbPress Create Topic', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_bbpress_create" <?php if (get_option('cfturnstile_bbpress_create')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('bbPress Reply', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_bbpress_reply" <?php if (get_option('cfturnstile_bbpress_reply')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php echo esc_html__('Alignment', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<select name="cfturnstile_bbpress_align">
									<option value="left" <?php if (!get_option('cfturnstile_bbpress_align') || get_option('cfturnstile_bbpress_align') == "left") { ?>selected<?php } ?>>
										<?php esc_html_e('Left', 'simple-cloudflare-turnstile'); ?>
									</option>
									<option value="right" <?php if (get_option('cfturnstile_bbpress_align') == "right") { ?>selected<?php } ?>>
										<?php esc_html_e('Right', 'simple-cloudflare-turnstile'); ?>
									</option>
								</select>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Guest Users Only', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_bbpress_guest_only" <?php if (get_option('cfturnstile_bbpress_guest_only')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/bbpress/" target="_blank">' . esc_html__('bbPress', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Ultimate Member
			if (cft_is_plugin_active('ultimate-member/ultimate-member.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('Ultimate Member', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('UM Login Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_um_login" <?php if (get_option('cfturnstile_um_login')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('UM Register Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_um_register" <?php if (get_option('cfturnstile_um_register')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('UM Password Reset Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_um_password" <?php if (get_option('cfturnstile_um_password')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/ultimate-member/" target="_blank">' . esc_html__('Ultimate Member', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // MemberPress
			if (cft_is_plugin_active('memberpress/memberpress.php')) { 

				if(get_option('cfturnstile_mepr_product_ids')) {
				  $LimitedToProductIDs = get_option('cfturnstile_mepr_product_ids');
				  $ProductsNeedingCaptcha = explode("\n", str_replace("\r", "", $LimitedToProductIDs));
				}
				?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('MemberPress', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<script>
						jQuery(document).ready(function(){
							jQuery("input[name='cfturnstile_login']").change(function(){
								if(jQuery("input[name='cfturnstile_login']").is(':checked')){
									jQuery('#cfturnstile_mepr_login').prop('checked', true);
								} else {
									jQuery('#cfturnstile_mepr_login').prop('checked', false);
								}
							});
						});
						</script>
						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Login Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type='checkbox' name='cfturnstile_mepr_login' id='cfturnstile_mepr_login' <?php if (get_option('cfturnstile_login')) { ?>checked<?php } ?>
							title='<?php echo esc_html__('Edit via "WordPress Login" option in the "Default WordPress Forms" settings.', 'simple-cloudflare-turnstile'); ?>' disabled></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Registration/Checkout Forms', 'simple-cloudflare-turnstile'); 
								if(get_option('cfturnstile_mepr_product_ids')) {
									?>
								<br><span style="font-weight:400;font-size:12px;"><span style="color:#d1242f;"><?php echo esc_html__('Limited to:', 'simple-cloudflare-turnstile'); ?></span> <?php echo implode(', ' , $ProductsNeedingCaptcha); ?></span>
								<?php
								}
								?>
							</th>
							<td><input type='checkbox' name='cfturnstile_mepr_register' id='cfturnstile_mepr_register' <?php if (get_option('cfturnstile_mepr_register')) { ?>checked<?php } ?>></td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('ONLY enable for these Membership IDs:', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<textarea style="width: 240px;" name="cfturnstile_mepr_product_ids"><?php echo sanitize_textarea_field(get_option('cfturnstile_mepr_product_ids')); ?></textarea>
								<br /><i style="font-size: 10px;"><?php echo esc_html__('(Optional) One per line. For Membership products that are not on this list, no Turnstile challenge will be loaded or enforced.', 'simple-cloudflare-turnstile'); ?></i>
							</td>
						</tr>

					</table>

				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://memberpress.com/?utm_source=simplecloudflareturnstile" target="_blank">' . esc_html__('MemberPress', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // WP-Members
			if (cft_is_plugin_active('wp-members/wp-members.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('WP-Members', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<p>
							<?php echo esc_html__('Turnstile is supported for WP-Members Login and Registration forms. Enable for these forms in the "Default WordPress Forms" settings.', 'simple-cloudflare-turnstile'); ?>
						</p><br/>

					</table>

				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/wp-members/" target="_blank">' . esc_html__('WP-Members', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // WP User Frontend
			if (cft_is_plugin_active('wp-user-frontend/wpuf.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('WP User Frontend', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<script>
						jQuery(document).ready(function(){
							jQuery("input[name='cfturnstile_login']").change(function(){
								if(jQuery("input[name='cfturnstile_login']").is(':checked')){
									jQuery('#cfturnstile_wpuf_login').prop('checked', true);
								} else {
									jQuery('#cfturnstile_wpuf_login').prop('checked', false);
								}
							});
						});
						</script>
						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Login Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type='checkbox' name='cfturnstile_wpuf_login' id='cfturnstile_wpuf_login' <?php if (get_option('cfturnstile_login')) { ?>checked<?php } ?>
							title='<?php echo esc_html__('Edit via "WordPress Login" option in the "Default WordPress Forms" settings.', 'simple-cloudflare-turnstile'); ?>' disabled></td>
						</tr>

						<script>
						jQuery(document).ready(function(){
							jQuery("input[name='cfturnstile_reset']").change(function(){
								if(jQuery("input[name='cfturnstile_reset']").is(':checked')){
									jQuery('#cfturnstile_wpuf_reset').prop('checked', true);
								} else {
									jQuery('#cfturnstile_wpuf_reset').prop('checked', false);
								}
							});
						});
						</script>
						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Reset Password Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type='checkbox' name='cfturnstile_wpuf_reset' id='cfturnstile_wpuf_reset' <?php if (get_option('cfturnstile_reset')) { ?>checked<?php } ?>
							title='<?php echo esc_html__('Edit via "WordPress Reset Password" option in the "Default WordPress Forms" settings.', 'simple-cloudflare-turnstile'); ?>' disabled></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Register Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_wpuf_register" <?php if (get_option('cfturnstile_wpuf_register')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('Post Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_wpuf_forms" <?php if (get_option('cfturnstile_wpuf_forms')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/wp-user-frontend/" target="_blank">' . esc_html__('WP User Frontend', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // WP User Manager
			if (cft_is_plugin_active('wp-user-manager/wp-user-manager.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('WP User Manager', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<p>
						<?php echo esc_html__('Turnstile is supported for WP User Manager Login, Registration and Reset Password forms. Enable for these forms in the "Default WordPress Forms" settings.', 'simple-cloudflare-turnstile'); ?>
					</p>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/wp-user-manager/" target="_blank">' . esc_html__('WP User Manager', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // wpDiscuz
			if (!cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php')) {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/wpdiscuz/" target="_blank">' . esc_html__('wpDiscuz', 'simple-cloudflare-turnstile') . '</a>');
			} ?>

			<?php
			// Output Custom Settings
			do_action('cfturnstile-settings-section');
			$not_installed = apply_filters('cfturnstile-settings-not-installed', $not_installed);
			?>

			<?php // List of plugins not installed
			if (!empty($not_installed)) { ?>
				<br />

				<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

					<tr valign="top">
						<th scope="row">
							<span style="font-size: 19px;"><?php echo esc_html__('Other Integrations', 'simple-cloudflare-turnstile'); ?></span>
							<p>
								
								<?php echo esc_html__('You can also enable Turnstile on', 'simple-cloudflare-turnstile') . " ";
								$last_plugin = end($not_installed);
								foreach ($not_installed as $not_plugin) {
									if ($not_plugin == $last_plugin && count($not_installed) > 1) echo 'and ';
									echo $not_plugin;
									if ($not_plugin != $last_plugin) {
										echo ', ';
									} else {
										echo '.';
									}
								}
								?>

								<?php echo esc_html__('Simply install the plugin and new settings will appear above.', 'simple-cloudflare-turnstile'); ?>

							</p>
						</th>
					</tr>

				</table>

			<?php } ?>

			</div>

			<?php submit_button(); ?>

			<div style="font-size: 10px; margin-top: 15px;">
				<!-- Delete Options on Uninstall (Always keep this option last) -->
				<input type="checkbox" name="cfturnstile_uninstall_remove" <?php if (get_option('cfturnstile_uninstall_remove')) { ?>checked<?php } ?> style="transform: scale(0.7); margin: -2px 0 0 0;">
				<?php echo esc_html__('Delete all of this plugins saved options when the plugin is deleted via plugins page.', 'simple-cloudflare-turnstile'); ?>
			</div>

			<div style="font-size: 10px; margin-top: 15px;">
				<!-- Enable Logging -->
				<input type="checkbox" name="cfturnstile_log_enable" <?php if (get_option('cfturnstile_log_enable')) { ?>checked<?php } ?> style="transform: scale(0.7); margin: -2px 0 0 0;">
				<?php echo esc_html__('Enable debug logging of Turnstile form submission events.', 'simple-cloudflare-turnstile'); ?>
			</div>
			
		</form>

		<!-- Export/Import Settings (Accordion) -->
		<button type="button" class="sct-accordion" id="sct-accordion-export-import"><?php echo esc_html__('Export / Import Settings', 'simple-cloudflare-turnstile'); ?></button>
		<div class="sct-panel">
			<p style="margin: 0 0 15px 0; border-bottom: 1px solid #f3f3f3; padding-bottom: 15px;">
				<?php echo esc_html__('Export all plugin settings to a JSON file, or import from a JSON file exported from this plugin.', 'simple-cloudflare-turnstile'); ?>
			</p>
			<div style="display:flex; gap:20px; flex-wrap:wrap;">
				<div style="flex:1; min-width:280px;">
					<h3 style="margin:8px 0; font-size:14px;">&ZeroWidthSpace;<?php echo esc_html__('Export Settings', 'simple-cloudflare-turnstile'); ?></h3>
					<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
						<input type="hidden" name="action" value="cfturnstile_export_settings" />
						<?php wp_nonce_field('cfturnstile_export_settings'); ?>
						<label style="display:block; font-size:12px; margin:6px 0;">
							<input type="checkbox" name="include_keys" value="1" style="float: none;">
							<?php echo esc_html__('Include API keys (sensitive)', 'simple-cloudflare-turnstile'); ?>
						</label>
						<?php submit_button( esc_html__('Download JSON', 'simple-cloudflare-turnstile'), 'secondary', 'submit', false ); ?>
					</form>
				</div>

				<div style="flex:1; min-width:280px;">
					<h3 style="margin:8px 0; font-size:14px;">&ZeroWidthSpace;<?php echo esc_html__('Import Settings', 'simple-cloudflare-turnstile'); ?></h3>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
						<input type="hidden" name="action" value="cfturnstile_import_settings" />
						<?php wp_nonce_field('cfturnstile_import_settings'); ?>
						<input type="file" name="cfturnstile_import_file" accept="application/json,.json" />
						<br/>
						<?php submit_button( esc_html__('Import JSON', 'simple-cloudflare-turnstile'), 'primary', 'submit', false ); ?>
						<p style="font-size:11px; margin-top:6px;">
							<?php echo esc_html__('Note: Site/Secret keys defined in wp-config.php will not be overwritten by import.', 'simple-cloudflare-turnstile'); ?>
						</p>
					</form>
				</div>
			</div>
		</div>

		<?php if(get_option('cfturnstile_log_enable')) { ?>
		<br/><button type="button" class="sct-accordion" id="sct-accordion-whitelist"><?php echo esc_html__('Turnstile Debug Log', 'simple-cloudflare-turnstile'); ?></button>
			<div class="sct-panel">

				<?php
				$cfturnstile_log = get_option('cfturnstile_log');
				/* 	$cfturnstile_log[] = array(
					'date' => date('Y-m-d H:i:s'),
					'success' => $success,
					'error' => $errors,
					'ip' => $_SERVER['REMOTE_ADDR'],
					'page' => $_SERVER['REQUEST_URI'],
				);
				*/
				if ($cfturnstile_log) {
				echo '<div style="max-height: 200px; overflow: auto; border: 1px solid #ddd; padding: 0px;">';
					echo '<table>';
						echo '<tr valign="top">';
						echo '<td>';
						echo '<table class="widefat">';
						echo '<thead>';
						echo '<tr>';
						echo '<th>' . esc_html__('Date', 'simple-cloudflare-turnstile') . '</th>';
						echo '<th>' . esc_html__('Success', 'simple-cloudflare-turnstile') . '</th>';
						echo '<th>' . esc_html__('Response', 'simple-cloudflare-turnstile') . '</th>';
						echo '<th>' . esc_html__('Info', 'simple-cloudflare-turnstile') . '</th>';
						echo '</tr>';
						echo '</thead>';
						echo '<tbody>';
						$cfturnstile_log = array_reverse($cfturnstile_log);
						foreach ($cfturnstile_log as $log) {
							echo '<tr>';
							$log['date'] = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['date']));
							echo '<td>' . esc_html($log['date']) . '</td>';
							echo '<td>' . ($log['success'] ? '<span style="color: green;">Yes</span>' : '<span style="color: red;">No</span>') . '</td>';
							echo '<td>';
							if(!$log['success']) {
								$error_val = $log['error'];
								echo esc_html($error_val);
							} else {
								echo '<span>' . esc_html__('Success', 'simple-cloudflare-turnstile') . '</span>';
							}
							echo '</td>';
							echo '<td>';
							echo '<strong>' . esc_html__('IP:', 'simple-cloudflare-turnstile') . '</strong> ' . esc_html($log['ip']) . '<br />';
							echo '<strong>' . esc_html__('URL:', 'simple-cloudflare-turnstile') . '</strong> ' . esc_html($log['page']);
							echo '</td>';
						}
						echo '</tr>';
						echo '</tbody>';
						echo '</table>';
						echo '</td>';
						echo '</tr>';
					echo '</table>';
				echo '</div>';
				// Error code meanings
				echo '<div style="margin-top: 20px; font-size: 9px;">';
				echo '<strong><u>' . esc_html__('Error Codes', 'simple-cloudflare-turnstile') . '</strong></u><br />';
				echo '- <strong>missing-input-response:</strong> ' . cfturnstile_error_message('missing-input-response') . esc_html__(' (Visitor submitted form when Turnstile was not successfully completed.)', 'simple-cloudflare-turnstile') . '<br />';
				echo '- <strong>missing-input-secret:</strong> ' . cfturnstile_error_message('missing-input-secret') . '<br />';
				echo '- <strong>invalid-input-secret:</strong> ' . cfturnstile_error_message('invalid-input-secret') . '<br />';
				echo '- <strong>invalid-input-response:</strong> ' . cfturnstile_error_message('invalid-input-response') . '<br />';
				echo '- <strong>bad-request:</strong> ' . cfturnstile_error_message('bad-request') . '<br />';
				echo '- <strong>timeout-or-duplicate:</strong> ' . cfturnstile_error_message('timeout-or-duplicate') . '<br />';
				echo '- <strong>internal-error:</strong> ' . cfturnstile_error_message('internal-error') . '<br />';
				echo '</div>';
				} else {
					echo '<p>' . esc_html__('No events logged yet.', 'simple-cloudflare-turnstile') . '</p>';
				}
				?>
			</div>
		<?php } else {
			if(get_option('cfturnstile_log')) {
				delete_option('cfturnstile_log');
			}
		}
		?>

	</div>

		<div class="sct-admin-promo-area" style="margin-top: 15px;">

			<div class="sct-admin-promo">

				<p style="font-weight: bold;">
					<?php echo esc_html__('Help and Resources:', 'simple-cloudflare-turnstile'); ?>
				</p>

				<p>
					<?php echo esc_html__('100% free plugin by', 'simple-cloudflare-turnstile'); ?> <a href="https://elliotsowersby.com/?utm_source=simplecloudflareturnstile&utm_medium=promo" target="_blank"> Elliot Sowersby</a> (<a href="https://relywp.com/?utm_campaign=simple-turnstile-plugin&utm_source=plugin-settings&utm_medium=promo" target="_blank">RelyWP</a>)
				</p>

				<p>
					- <?php echo esc_html__('Not sure how to use this plugin?', 'simple-cloudflare-turnstile'); ?>
					<a href="https://elliotsowersby.com/blog/setup-guide-turnstile/?utm_source=simplecloudflareturnstile&utm_medium=settings-sidebar-guide" title="View our Turnstile plugin setup guide." target="_blank"><?php echo esc_html__('View setup guide', 'simple-cloudflare-turnstile'); ?></a>
				</p>

				<p>- <?php echo esc_html__('Need help? Have a suggestion?', 'simple-cloudflare-turnstile'); ?> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/#new-topic-0" target="_blank" title="<?php echo esc_html__('Create a support topic', 'simple-cloudflare-turnstile'); ?>."><?php echo esc_html__('Create a support topic', 'simple-cloudflare-turnstile'); ?></a></p>

				<p style="font-size: 12px;">
					<a href="https://translate.wordpress.org/projects/wp-plugins/simple-cloudflare-turnstile/" target="_blank"><?php echo esc_html__('Translate into your language', 'simple-cloudflare-turnstile'); ?></a>
					-
					<a href="https://github.com/ElliotSowersby/simple-cloudflare-turnstile" target="_blank"><?php echo esc_html__('View on GitHub', 'simple-cloudflare-turnstile'); ?></a>
				</p>

			</div>

			<div class="sct-admin-promo sct-support" style="margin-top: 15px;">

				<p style="font-weight: bold;">
					<?php echo esc_html__('Support The Plugin', 'simple-cloudflare-turnstile'); ?>:
				</p>

				<p class="sct-support-intro">
					<?php echo sprintf( wp_kses_post( __( 'Thanks to the donors and <a href="%s" target="_blank">sponsors</a> that help support my free plugins to keep them 100%% free, maintained and supported.', 'simple-cloudflare-turnstile' ) ), 'https://www.github.com/sponsors/ElliotSowersby/' ); ?>
				</p>

				<div class="sct-support-item">
					<div class="sct-support-text">
						<strong style="color: #057322;"><?php echo esc_html__('Leave a Review:', 'simple-cloudflare-turnstile'); ?></strong>
						<?php echo sprintf( wp_kses_post( __( 'If you found this plugin useful, please submit a positive review on <a href="%s" target="_blank">WordPress.org</a>.', 'simple-cloudflare-turnstile' ) ), 'https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/?filter=5#new-post' ); ?>
						<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span>
					</div>
					<a class="button button-primary sct-support-btn" href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/?filter=5#new-post" target="_blank">
						<?php echo esc_html__('Review', 'simple-cloudflare-turnstile'); ?>
						<i class="dashicons dashicons-external" style="font-size: 14px; line-height: 22px;"></i>
					</a>
				</div>

				<div class="sct-support-item">
					<div class="sct-support-text">
						<strong style="color: #057322;"><?php echo esc_html__('Donate:', 'simple-cloudflare-turnstile'); ?></strong>
						<?php echo esc_html__('Consider making a small donation to the developer to help support the plugin.', 'simple-cloudflare-turnstile'); ?>
					</div>
					<a class="button button-primary sct-support-btn" href="https://www.paypal.com/donate/?hosted_button_id=RX28BBH7L5XDS" target="_blank">
						<?php echo esc_html__('Donate', 'simple-cloudflare-turnstile'); ?>
						<i class="dashicons dashicons-external" style="font-size: 14px; line-height: 22px;"></i>
					</a>
				</div>

				<div class="sct-support-item">
					<div class="sct-support-text">
						<strong style="color: #057322;"><?php echo esc_html__('Sponsor:', 'simple-cloudflare-turnstile'); ?></strong>
						<?php echo esc_html__('Help fund ongoing development, maintenance, and support of this 100% free plugin, and be listed as a sponsor.', 'simple-cloudflare-turnstile'); ?>
					</div>
					<a class="button button-primary sct-support-btn" href="https://www.github.com/sponsors/ElliotSowersby/" target="_blank">
						<?php echo esc_html__('Sponsor', 'simple-cloudflare-turnstile'); ?>
						<i class="dashicons dashicons-external" style="font-size: 14px; line-height: 22px;"></i>
					</a>
				</div>

			</div>

		</div>

<?php } ?>