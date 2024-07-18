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
				echo '<div style="margin-left: 15px;">';
				echo cfturnstile_field_show('', '', 'admin-test', 'admin-test');
				echo '</div><div style="margin-bottom: -20px;"></div>';
				echo '<button type="submit" style="margin-top: 10px; padding: 7px 10px; background: #1c781c; color: #fff; font-size: 15px; font-weight: bold; border: 1px solid #176017; border-radius: 4px; cursor: pointer;">
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

		<h1 style="font-weight: bold;"><?php echo esc_html__('Simple Cloudflare Turnstile', 'simple-cloudflare-turnstile'); ?></h1>

		<p style="margin-bottom: 0;"><?php echo esc_html__('Easily add the free CAPTCHA service called "Cloudflare Turnstile" to your WordPress forms to help prevent spam.', 'simple-cloudflare-turnstile'); ?> <a href="https://www.cloudflare.com/en-gb/products/turnstile/" target="_blank"><?php echo esc_html__('Learn more.', 'simple-cloudflare-turnstile'); ?></a>

		<div class="sct-admin-promo-top">

			<p>
				<a href="https://relywp.com/blog/how-to-add-cloudflare-turnstile-to-wordpress/?utm_campaign=simple-turnstile-plugin&utm_source=plugin-settings&utm_medium=guide" title="View our Turnstile plugin setup guide." target="_blank"><?php echo esc_html__('View setup guide', 'simple-cloudflare-turnstile'); ?><span class="dashicons dashicons-external" style="margin-left: 2px; text-decoration: none;"></span></a> &nbsp;&#x2022;&nbsp; <?php echo esc_html__('Like this plugin?', 'simple-cloudflare-turnstile'); ?> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank" title="<?php echo esc_html__('Review on WordPress.org', 'simple-cloudflare-turnstile'); ?>"><?php echo esc_html__('Please submit a review', 'simple-cloudflare-turnstile'); ?></a> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank" title="<?php echo esc_html__('Review on WordPress.org', 'simple-cloudflare-turnstile'); ?>" style="text-decoration: none;">
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

						<p style="font-size: 19px; margin-top: 0;"><?php echo esc_html__('API Key Settings:', 'simple-cloudflare-turnstile'); ?></p>

						<?php
						if (get_option('cfturnstile_tested') == 'yes') {
							echo '<p style=" font-size: 15px; font-weight: bold; color: #1e8c1e;"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__('Success! Turnstile is working correctly with your API keys.', 'simple-cloudflare-turnstile') . '</p>';
						} ?>

						<p style="margin-bottom: 2px;"><?php echo esc_html__('You can get your site key and secret key from here:', 'simple-cloudflare-turnstile'); ?> <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">https://dash.cloudflare.com/?to=/:account/turnstile</a></p>

					</th>
				</tr>

			</table>

			<table class="form-table">

				<tr valign="top">
					<th scope="row"><?php echo esc_html__('Site Key', 'simple-cloudflare-turnstile'); ?></th>
					<td><input type="text" style="width: 240px;" name="cfturnstile_key" value="<?php echo esc_html(get_option('cfturnstile_key')); ?>" /></td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php echo esc_html__('Secret Key', 'simple-cloudflare-turnstile'); ?></th>
					<td><input type="text" style="width: 240px;" name="cfturnstile_secret" value="<?php echo esc_html(get_option('cfturnstile_secret')); ?>" /></td>
				</tr>

			</table>

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
							'auto' => esc_html__( 'Auto Detect', 'simple-cloudflare-turnstile' ),
							'ar-eg' => esc_html__( 'Arabic', 'simple-cloudflare-turnstile' ),
							'de' => esc_html__( 'German', 'simple-cloudflare-turnstile' ),
							'en' => esc_html__( 'English', 'simple-cloudflare-turnstile' ),
							'es' => esc_html__( 'Spanish', 'simple-cloudflare-turnstile' ),
							'fa' => esc_html__( 'Persian', 'simple-cloudflare-turnstile' ),
							'fr' => esc_html__( 'French', 'simple-cloudflare-turnstile' ),
							'id' => esc_html__( 'Indonesian', 'simple-cloudflare-turnstile' ),
							'it' => esc_html__( 'Italian', 'simple-cloudflare-turnstile' ),
							'ja' => esc_html__( 'Japanese', 'simple-cloudflare-turnstile' ),
							'ko' => esc_html__( 'Korean', 'simple-cloudflare-turnstile' ),
							'nl' => esc_html__( 'Dutch', 'simple-cloudflare-turnstile' ),
							'pl' => esc_html__( 'Polish', 'simple-cloudflare-turnstile' ),
							'pt-br' => esc_html__( 'Portuguese (Brazil)', 'simple-cloudflare-turnstile' ),
							'ru' => esc_html__( 'Russian', 'simple-cloudflare-turnstile' ),
							'tr' => esc_html__( 'Turkish', 'simple-cloudflare-turnstile' ),
							'zh-cn' => esc_html__( 'Chinese (Simplified)', 'simple-cloudflare-turnstile' ),
							'zh-tw' => esc_html__( 'Chinese (Traditional)', 'simple-cloudflare-turnstile' )
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
					<th scope="row"><?php echo esc_html__('Appearance Mode', 'simple-cloudflare-turnstile'); ?></th>
					<td>
						<select name="cfturnstile_appearance" style="max-width: 240px;">
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
						<div class="wcu-appearance-always" style="display: none;"><i style="font-size: 10px;"><?php echo esc_html__( 'Turnstile Widget is always displayed for all visitors.', 'simple-cloudflare-turnstile' ); ?></i></div>
						<div class="wcu-appearance-execute" style="display: none;"><i style="font-size: 10px;"><?php echo esc_html__( 'Turnstile Widget is only displayed after the challenge begins.', 'simple-cloudflare-turnstile' ); ?></i></div>
						<div class="wcu-appearance-interaction-only" style="display: none;"><i style="font-size: 10px;"><?php echo esc_html__( 'Turnstile Widget is only displayed in cases where an interaction is required. This essentially makes it "invisible" for most valid users.', 'simple-cloudflare-turnstile' ); ?></i></div>
					</td>
				</tr>
				<script>
					jQuery(document).ready(function($) {
						function updateDescription(selected) {
							// Hide all descriptions
							$('.wcu-appearance-always, .wcu-appearance-execute, .wcu-appearance-interaction-only').hide();

							// Show the relevant description
							$('.wcu-appearance-' + selected).show();
						}

						// Update the description on page load
						updateDescription($("select[name='cfturnstile_appearance']").val());

						// Handle the select change event
						$("select[name='cfturnstile_appearance']").change(function(){
							updateDescription($(this).val());
						});
					});
				</script>

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

				<p style="margin: 0 0 20px 0; padding-bottom: 20px; border-bottom: 1px solid #f3f3f3;">
					<?php echo esc_html__('These settings are for more advanced customisation. If you are not sure about these, they do not need to be changed.', 'simple-cloudflare-turnstile'); ?>
				</p>

				<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

					<tr valign="top">
						<th scope="row">
							<?php echo esc_html__('Defer Scripts', 'simple-cloudflare-turnstile'); ?>
						</th>
						<td><input style="margin: 5px 0 20px 10px;" type="checkbox" name="cfturnstile_defer_scripts" <?php if (get_option('cfturnstile_defer_scripts', 1)) { ?>checked<?php } ?>>
						<i style="font-size: 10px;"><?php echo esc_html__('When enabled, the javascript files loaded by the plugin will be deferred. You can disable this if it causes any issues with your other optimisations.', 'simple-cloudflare-turnstile'); ?></i>
					</td>

					<tr valign="top">
						<th scope="row"><?php echo esc_html__('Custom Error Message', 'simple-cloudflare-turnstile'); ?></th>
						<td>
							<textarea type="text" style="width: 202px; margin-bottom: 5px;" name="cfturnstile_error_message"
							placeholder="<?php echo cfturnstile_failed_message(1); ?>"
							/><?php if(get_option('cfturnstile_error_message')) { echo esc_html(get_option('cfturnstile_error_message')); } ?></textarea>
							<br /><i style="font-size: 10px;"><?php echo esc_html__('Shown if the form is submitted without completing the Turnstile challenge. Leave blank to use the default message (localized):', 'simple-cloudflare-turnstile') . ' "' . cfturnstile_failed_message(1) . '"'; ?></i>
						</td>
					</tr>

					<tr valign="top" style="border: 0;">
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
							placeholder="<?php echo esc_html__('Failed to verify you are human. Please contact us if you are having issues.', 'simple-cloudflare-turnstile'); ?>"
							/><?php if(get_option('cfturnstile_failure_message')) { echo esc_html(get_option('cfturnstile_failure_message')); } ?></textarea>
							<i style="font-size: 10px;"><?php echo esc_html__('This will show a message below the Turnstile widget if they receive the "Failure!" response. Useful to give instructions in the *very rare* case a valid user is being flagged as spam.', 'simple-cloudflare-turnstile'); ?></i>
							<br/><br/>
							<i style="font-size: 10px;"><?php echo esc_html__('Currently it is not possible to edit the actual "Failure!" message shown on the widget.', 'simple-cloudflare-turnstile'); ?></i>
						</td>
					</tr>
					<script>
					jQuery(document).ready(function() {
						jQuery('.cfturnstile-failure-message').hide();
						jQuery('input[name="cfturnstile_failure_message_enable"]').change(function() {
							if(jQuery(this).is(":checked")) {
								jQuery('.cfturnstile-failure-message').show();
							} else {
								jQuery('.cfturnstile-failure-message').hide();
							}
						});
						jQuery('input[name="cfturnstile_failure_message_enable"]').trigger('change');						
					});
					</script>

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

						<?php
						$checkout_page_id = get_option('woocommerce_checkout_page_id');
						$checkout_page_content = get_post_field('post_content', $checkout_page_id);
						if (strpos($checkout_page_content, 'wp:woocommerce/checkout') !== false) {
						?>

						<tr valign="top">
							<th scope="row">
								<?php echo esc_html__('WooCommerce Checkout', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td>
							<i style="font-size: 12px; color: red;"><?php echo esc_html__("Currently not compatible with the new 'block-based' checkout.", 'simple-cloudflare-turnstile'); ?></i>
							</td>
						</tr>

						<?php } else { ?>

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

						<?php } ?>

						<tr valign="top" style="border-bottom: 1px solid #f3f3f3;">
							<th scope="row">
								<?php echo esc_html__('WooCommerce Pay for Order', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_woo_checkout_pay" <?php if (get_option('cfturnstile_woo_checkout_pay')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php if ( class_exists( 'WooCommerce' ) ) { ?>

						<?php $available_gateways = WC()->payment_gateways->get_available_payment_gateways(); ?>

						<?php if(!empty($available_gateways)) { ?>

							<br/>

							<p style="font-size: 15px; font-weight: 600;">
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
									<br/>
									<?php echo esc_html__("Useful for 'Express Checkout' payment methods compatibility.", 'simple-cloudflare-turnstile'); ?>
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
					<br /><span style="color: red; font-size: 15px; font-weight: bold;">[cf7-simple-turnstile]</span>

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
				array_push($not_installed, '<a href="https://www.gravityforms.com/" target="_blank">' . esc_html__('Gravity Forms', 'simple-cloudflare-turnstile') . '</a>');
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
						<a href="https://wsform.com/knowledgebase/turnstile/" target="_blank"><?php echo esc_html__('Click here for more information.', 'simple-cloudflare-turnstile'); ?></a>
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
			if ( cft_is_plugin_active('elementor-pro/elementor-pro.php') ) { ?>
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

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://elementor.com/features/form-builder/" target="_blank">' . esc_html__('Elementor Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php if (cft_is_plugin_active('mailchimp-for-wp/mailchimp-for-wp.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo esc_html__('MC4WP: Mailchimp for WordPress', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<?php echo esc_html__('To add Turnstile to Mailchimp for WordPress, simply add this shortcode to any of your forms (in the form editor):', 'simple-cloudflare-turnstile'); ?>
					<br /><span style="color: red; font-size: 15px; font-weight: bold;">[mc4wp-simple-turnstile]</span>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/mailchimp-for-wp/" target="_blank">' . esc_html__('Mailchimp for WordPress', 'simple-cloudflare-turnstile') . '</a>');
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
				array_push($not_installed, '<a href="https://memberpress.com/" target="_blank">' . esc_html__('MemberPress', 'simple-cloudflare-turnstile') . '</a>');
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
			
		</form>

		<div class="sct-admin-promo">

			<p style="font-size: 15px; font-weight: bold;"><?php echo esc_html__('100% free plugin developed by', 'simple-cloudflare-turnstile'); ?> <a href="https://twitter.com/ElliotSowersby" target="_blank" title="@ElliotSowersby on Twitter"><span class="dashicons dashicons-twitter" style="margin-top: 5px; font-size: 15px; text-decoration: none;"></span>Elliot Sowersby</a> <a href="https://relywp.com/?utm_campaign=simple-turnstile-plugin&utm_source=plugin-settings&utm_medium=promo" target="_blank" title="RelyWP - WordPress Maintenance & Support"><span class="dashicons dashicons-admin-links" style="margin-top: 5px; font-size: 15px; text-decoration: none;"></span>RelyWP</a></p>

			<p style="font-size: 15px;">
				- <?php echo esc_html__('Like this plugin?', 'simple-cloudflare-turnstile'); ?> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank" title="<?php echo esc_html__('Review on WordPress.org', 'simple-cloudflare-turnstile'); ?>"><?php echo esc_html__('Please submit a review', 'simple-cloudflare-turnstile'); ?></a> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank" title="<?php echo esc_html__('Review on WordPress.org', 'simple-cloudflare-turnstile'); ?>" style="text-decoration: none;">
					<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span>
				</a></p>

			<p style="font-size: 15px;">- <?php echo esc_html__('Need help? Have a suggestion?', 'simple-cloudflare-turnstile'); ?> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/#new-topic-0" target="_blank"><?php echo esc_html__('Create a support topic', 'simple-cloudflare-turnstile'); ?><span class="dashicons dashicons-external" style="font-size: 15px; margin-top: 5px; text-decoration: none;"></span></a></p>

			<p style="font-size: 15px;">
				- <?php echo esc_html__('Want to support the plugin?', 'simple-cloudflare-turnstile'); ?> <?php echo esc_html__('Feel free to', 'simple-cloudflare-turnstile'); ?> <a href="https://www.paypal.com/donate/?hosted_button_id=RX28BBH7L5XDS" target="_blank"><?php echo esc_html__('Donate', 'simple-cloudflare-turnstile'); ?><span class="dashicons dashicons-external" style="font-size: 15px; margin-top: 5px; text-decoration: none;"></span></a>
			</p>

			<p style="font-size: 12px;">
				<a href="https://translate.wordpress.org/projects/wp-plugins/simple-cloudflare-turnstile/" target="_blank"><?php echo esc_html__('Translate into your language', 'simple-cloudflare-turnstile'); ?><span class="dashicons dashicons-external" style="font-size: 15px; margin-top: 2px; text-decoration: none;"></span></a>
				<br />
				<a href="https://github.com/ElliotSowersby/simple-cloudflare-turnstile" target="_blank"><?php echo esc_html__('View on GitHub', 'simple-cloudflare-turnstile'); ?><span class="dashicons dashicons-external" style="font-size: 15px; margin-top: 2px; text-decoration: none;"></span></a>
			</p>

		</div>

		<div class="sct-admin-promo" style="margin-top: 15px;">

			<p style="font-size: 15px;">
				<a href="https://relywp.com/plugins/?utm_campaign=simple-turnstile-plugin&utm_source=plugin-settings&utm_medium=promo" target="_blank">
					<?php echo esc_html__( 'View more plugins by RelyWP', 'simple-cloudflare-turnstile' ); ?><span class="dashicons dashicons-external"
					style="font-size: 15px; margin-top: 5px; text-decoration: none;"></span>
				</a>
			</p>

		</div>

<?php } ?>