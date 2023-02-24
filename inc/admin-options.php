<?php
if (!defined('ABSPATH')) {
	exit;
}

// create custom plugin settings menu
add_action('admin_menu', 'cfturnstile_create_menu');
function cfturnstile_create_menu() {
	//create new top-level menu
	add_submenu_page('options-general.php', 'Cloudflare Turnstile', 'Cloudflare Turnstile', 'manage_options', __FILE__, 'cfturnstile_settings_page');
	//call register settings function
	add_action('admin_init', 'cfturnstile_register_settings');
}

// Register Settings
function cfturnstile_register_settings() {
	register_setting('cfturnstile-settings-group', 'cfturnstile_setup');
	register_setting('cfturnstile-settings-group', 'cfturnstile_key');
	register_setting('cfturnstile-settings-group', 'cfturnstile_secret');
	register_setting('cfturnstile-settings-group', 'cfturnstile_theme');
	register_setting('cfturnstile-settings-group', 'cfturnstile_disable_button');
	register_setting('cfturnstile-settings-group', 'cfturnstile_error_message');
	register_setting('cfturnstile-settings-group', 'cfturnstile_login');
	register_setting('cfturnstile-settings-group', 'cfturnstile_register');
	register_setting('cfturnstile-settings-group', 'cfturnstile_reset');
	register_setting('cfturnstile-settings-group', 'cfturnstile_comment');
	register_setting('cfturnstile-settings-group', 'cfturnstile_woo_checkout');
	register_setting('cfturnstile-settings-group', 'cfturnstile_guest_only');
	register_setting('cfturnstile-settings-group', 'cfturnstile_woo_checkout_pos');
	register_setting('cfturnstile-settings-group', 'cfturnstile_woo_checkout_pay');
	register_setting('cfturnstile-settings-group', 'cfturnstile_woo_login');
	register_setting('cfturnstile-settings-group', 'cfturnstile_woo_register');
	register_setting('cfturnstile-settings-group', 'cfturnstile_woo_reset');
	register_setting('cfturnstile-settings-group', 'cfturnstile_bp_register');
	register_setting('cfturnstile-settings-group', 'cfturnstile_cf7_all');
	register_setting('cfturnstile-settings-group', 'cfturnstile_wpforms');
	register_setting('cfturnstile-settings-group', 'cfturnstile_wpforms_pos');
	register_setting('cfturnstile-settings-group', 'cfturnstile_wpforms_disable');
	register_setting('cfturnstile-settings-group', 'cfturnstile_gravity');
	register_setting('cfturnstile-settings-group', 'cfturnstile_gravity_pos');
	register_setting('cfturnstile-settings-group', 'cfturnstile_gravity_disable');
	register_setting('cfturnstile-settings-group', 'cfturnstile_fluent');
	register_setting('cfturnstile-settings-group', 'cfturnstile_fluent_disable');
	register_setting('cfturnstile-settings-group', 'cfturnstile_formidable');
	register_setting('cfturnstile-settings-group', 'cfturnstile_formidable_pos');
	register_setting('cfturnstile-settings-group', 'cfturnstile_formidable_disable');
	register_setting('cfturnstile-settings-group', 'cfturnstile_forminator');
	register_setting('cfturnstile-settings-group', 'cfturnstile_forminator_pos');
	register_setting('cfturnstile-settings-group', 'cfturnstile_forminator_disable');
	register_setting('cfturnstile-settings-group', 'cfturnstile_elementor');
	register_setting('cfturnstile-settings-group', 'cfturnstile_elementor_pos');
	register_setting('cfturnstile-settings-group', 'cfturnstile_um_login');
	register_setting('cfturnstile-settings-group', 'cfturnstile_um_register');
	register_setting('cfturnstile-settings-group', 'cfturnstile_um_password');
	register_setting('cfturnstile-settings-group', 'cfturnstile_bbpress_create');
	register_setting('cfturnstile-settings-group', 'cfturnstile_bbpress_reply');
	register_setting('cfturnstile-settings-group', 'cfturnstile_bbpress_guest_only');
	register_setting('cfturnstile-settings-group', 'cfturnstile_bbpress_align');
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
				echo '<br/><div style="padding: 20px 20px 25px 20px; background: #fff; border-radius: 20px; max-width: 500px; border: 2px solid #d5d5d5;">';
				echo '<p style="font-weight: 600; font-size: 19px; margin-top: 0; margin-bottom: 0;">' . __('Almost done...', 'simple-cloudflare-turnstile') . '</p>';
			}
			if (!isset($_POST['cf-turnstile-response'])) {
				echo '<p>'
					. '<span style="color: red; font-weight: bold;">' . __('API keys have been updated. Please test the Turnstile API response below.', 'simple-cloudflare-turnstile') . '</span>'
					. '<br/>'
					. __('Turnstile will not be added to any login forms until the test is successfully complete.', 'simple-cloudflare-turnstile')
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
				echo cfturnstile_field_show('', '', 'admin-test');
				echo '</div><div style="margin-bottom: -20px;"></div>';
				echo '<button type="submit" style="margin-top: 10px; padding: 7px 10px; background: #1c781c; color: #fff; font-size: 15px; font-weight: bold; border: 1px solid #176017; border-radius: 4px; cursor: pointer;">
				' . __('TEST API RESPONSE', 'simple-cloudflare-turnstile') . ' <span class="dashicons dashicons-arrow-right-alt"></span>
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
	<div class="wrap">

		<h1><?php echo __('Simple Cloudflare Turnstile', 'simple-cloudflare-turnstile'); ?></h1>

		<p style="margin-bottom: 0;"><?php echo __('Easily add the new "Cloudflare Turnstile" to your WordPress forms to help prevent spam.', 'simple-cloudflare-turnstile'); ?> <?php echo __('Learn more:', 'simple-cloudflare-turnstile'); ?> <a href="https://www.cloudflare.com/en-gb/products/turnstile/" target="_blank">https://www.cloudflare.com/en-gb/products/turnstile/</a></p>

		<div class="sct-admin-promo-top">

			<p>
				<?php echo __('Like this plugin?', 'simple-cloudflare-turnstile'); ?> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank" title="<?php echo __('Review on WordPress.org', 'simple-cloudflare-turnstile'); ?>"><?php echo __('Please submit a review', 'simple-cloudflare-turnstile'); ?></a> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank" title="<?php echo __('Review on WordPress.org', 'simple-cloudflare-turnstile'); ?>" style="text-decoration: none;">
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

						<p style="font-size: 19px; margin-top: 0;"><?php echo __('API Key Settings:', 'simple-cloudflare-turnstile'); ?></p>

						<?php
						if (get_option('cfturnstile_tested') == 'yes') {
							echo '<p style="font-weight: bold; color: green;"><span class="dashicons dashicons-yes-alt"></span> ' . __('Success! Turnstile seems to be working correctly with your API keys.', 'simple-cloudflare-turnstile') . '</p>';
						} ?>

						<p style="margin-bottom: 2px;"><?php echo __('You can get your site key and secret from here:', 'simple-cloudflare-turnstile'); ?> <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">https://dash.cloudflare.com/?to=/:account/turnstile</a></p>

					</th>
				</tr>

			</table>

			<table class="form-table">

				<tr valign="top">
					<th scope="row"><?php echo __('Site Key', 'simple-cloudflare-turnstile'); ?></th>
					<td><input type="text" style="width: 240px;" name="cfturnstile_key" value="<?php echo sanitize_text_field(get_option('cfturnstile_key')); ?>" /></td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php echo __('Secret Key', 'simple-cloudflare-turnstile'); ?></th>
					<td><input type="text" style="width: 240px;" name="cfturnstile_secret" value="<?php echo sanitize_text_field(get_option('cfturnstile_secret')); ?>" /></td>
				</tr>

			</table>

			<hr style="margin: 20px 0 10px 0;">

			<table class="form-table">

				<tr valign="top">
					<th scope="row" style="font-size: 19px; padding-bottom: 5px;"><?php echo __('General Settings:', 'simple-cloudflare-turnstile'); ?></th>
				</tr>

				<tr valign="top">
					<th scope="row"><?php echo __('Theme', 'simple-cloudflare-turnstile'); ?></th>
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
					<th scope="row"><?php echo __('Language', 'simple-cloudflare-turnstile'); ?></th>
					<td>
						<select name="cfturnstile_language">
						<?php
						$languages = array(
							'auto' => 'Auto Detect',
							'ar-eg' => 'Arabic',
							'de' => 'German',
							'en' => 'English',
							'es' => 'Spanish',
							'fa' => 'Persian',
							'fr' => 'French',
							'id' => 'Indonesian',
							'it' => 'Italian',
							'ja' => 'Japanese',
							'ko' => 'Korean',
							'nl' => 'Dutch',
							'pl' => 'Polish',
							'pt-br' => 'Portuguese (Brazil)',
							'ru' => 'Russian',
							'tr' => 'Turkish',
							'zh-cn' => 'Chinese (Simplified)',
							'zh-tw' => 'Chinese (Traditional)'
						);
						foreach ($languages as $code => $name) {
							$selected = '';
							if(get_option('cfturnstile_language')) { $selected = 'selected'; }
							?>
								<option value="<?php echo $code; ?>" <?php echo $selected; ?>>
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
						<?php echo __('Disable Submit Button', 'simple-cloudflare-turnstile'); ?>
					</th>
					<td><input type="checkbox" name="cfturnstile_disable_button" <?php if (get_option('cfturnstile_disable_button')) { ?>checked<?php } ?>>
						<i style="font-size: 10px;"><?php echo __('When enabled, the user will not be able to click submit until the Turnstile challenge is completed.', 'simple-cloudflare-turnstile'); ?></i>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php echo __('Custom Error Message', 'simple-cloudflare-turnstile'); ?></th>
					<td>
						<input type="text" style="width: 240px;" name="cfturnstile_error_message" value="<?php echo sanitize_text_field(get_option('cfturnstile_error_message')); ?>" placeholder="<?php echo cfturnstile_failed_message(1); ?>" />
						<br /><i style="font-size: 10px;"><?php echo __('Leave blank to use the default message (localized):', 'simple-cloudflare-turnstile') . ' "' . cfturnstile_failed_message(1) . '"'; ?></i>
					</td>
				</tr>

			</table>

			<hr style="margin: 20px 0 10px 0;">

			<table class="form-table" style="margin-bottom: -35px;">

				<tr valign="top">
					<th scope="row">
						<span style="font-size: 19px;"><?php echo __('Enable Turnstile on your forms:', 'simple-cloudflare-turnstile'); ?></span>
						<p><?php echo __('Select the dropdown for each integration, and choose when specific forms you want to enable Turnstile on.', 'simple-cloudflare-turnstile'); ?></p>
					</th>
				</tr>

			</table>

			<button type="button" class="sct-accordion" id="sct-accordion-wordpress"><?php echo __('Default WordPress Forms', 'simple-cloudflare-turnstile'); ?></button>
			<div class="sct-panel">

				<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

					<tr valign="top">
						<th scope="row">
							<?php echo __('WordPress Login', 'simple-cloudflare-turnstile'); ?>
						</th>
						<td><input type="checkbox" name="cfturnstile_login" <?php if (get_option('cfturnstile_login')) { ?>checked<?php } ?>></td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php echo __('WordPress Register', 'simple-cloudflare-turnstile'); ?>
						</th>
						<td><input type="checkbox" name="cfturnstile_register" <?php if (get_option('cfturnstile_register')) { ?>checked<?php } ?>></td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php echo __('WordPress Reset Password', 'simple-cloudflare-turnstile'); ?>
						</th>
						<td><input type="checkbox" name="cfturnstile_reset" <?php if (get_option('cfturnstile_reset')) { ?>checked<?php } ?>></td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php echo __('WordPress Comment', 'simple-cloudflare-turnstile'); ?>
						</th>
						<td>
							<input type="checkbox" name="cfturnstile_comment" <?php if (get_option('cfturnstile_comment')) { ?>checked<?php } ?>>
							<?php if (cft_is_plugin_active('jetpack/jetpack.php')) { ?>
								<br /><i style="font-size: 10px;"><?php echo __('Due to Jetpack limitations, this does NOT currently work with Jetpack comments form enabled.', 'simple-cloudflare-turnstile'); ?></i>
							<?php } ?>
							<?php if (cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php')) { ?>
								<i style="font-size: 9px;"><?php echo __('Compatible with wpDiscuz!', 'simple-cloudflare-turnstile'); ?> &#128077;</i>
							<?php } ?>
						</td>
					</tr>

				</table>

			</div>

			<?php $not_installed = array(); ?>

			<?php // WooCommerce
			if (cft_is_plugin_active('woocommerce/woocommerce.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('WooCommerce Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('WooCommerce Checkout', 'simple-cloudflare-turnstile'); ?>
								<br /><br />
								<?php echo __('Guest Checkout Only', 'simple-cloudflare-turnstile'); ?>
								<br /><br />
								<?php echo __('Widget Location', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td>
								<input type="checkbox" name="cfturnstile_woo_checkout" <?php if (get_option('cfturnstile_woo_checkout')) { ?>checked<?php } ?>>
								<br /><br />
								<input type="checkbox" name="cfturnstile_guest_only" <?php if (get_option('cfturnstile_guest_only')) { ?>checked<?php } ?>>
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
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo __('WooCommerce Pay for Order', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_woo_checkout_pay" <?php if (get_option('cfturnstile_woo_checkout_pay')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo __('WooCommerce Login', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_woo_login" <?php if (get_option('cfturnstile_woo_login')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo __('WooCommerce Register', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_woo_register" <?php if (get_option('cfturnstile_woo_register')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo __('WooCommerce Reset Password', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_woo_reset" <?php if (get_option('cfturnstile_woo_reset')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . __('WooCommerce', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Contact Form 7
			if (cft_is_plugin_active('contact-form-7/wp-contact-form-7.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('Contact Form 7', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('Enable on all CF7 Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_cf7_all" <?php if (get_option('cfturnstile_cf7_all')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<br />

					<?php echo __('To add Turnstile to individual Contact Form 7 forms, simply add this shortcode to any of your forms (in the form editor):', 'simple-cloudflare-turnstile'); ?>
					<br /><span style="color: red; font-size: 15px; font-weight: bold;">[cf7-simple-turnstile]</span>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">' . __('Contact Form 7', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // WPForms
			if (cft_is_plugin_active('wpforms-lite/wpforms.php') || cft_is_plugin_active('wpforms/wpforms.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('WPForms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('Enable on all WPForms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_wpforms" <?php if (get_option('cfturnstile_wpforms')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo __('When enabled, Turnstile will be added before/after the submit button, on ALL your forms created with WPForms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -15px;">

						<tr valign="top">
							<th scope="row"><?php echo __('Widget Location', 'simple-cloudflare-turnstile'); ?></th>
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
							<th scope="row"><?php echo __('Disabled Form IDs', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<input type="text" name="cfturnstile_wpforms_disable" value="<?php echo sanitize_text_field(get_option('cfturnstile_wpforms_disable')); ?>" />
							</td>
						</tr>
					</table>
					<i style="font-size: 10px;">
						<?php echo sprintf(__('If you want to DISABLE the Turnstile widget on certain forms, enter the %s ID in this field.', 'simple-cloudflare-turnstile'), 'WPForms Form'); ?>
						<?php echo __('Separate each ID with a comma, for example: 5,10,21', 'simple-cloudflare-turnstile'); ?>
					</i>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">' . __('WPForms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Gravity Forms
			if (cft_is_plugin_active('gravityforms/gravityforms.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('Gravity Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('Enable on all Gravity Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_gravity" <?php if (get_option('cfturnstile_gravity')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo __('When enabled, Turnstile will be added before/after the submit button, on ALL your forms created with Gravity Forms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -15px;">

						<tr valign="top">
							<th scope="row"><?php echo __('Widget Location', 'simple-cloudflare-turnstile'); ?></th>
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
							<th scope="row"><?php echo __('Disabled Form IDs', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<input type="text" name="cfturnstile_gravity_disable" value="<?php echo sanitize_text_field(get_option('cfturnstile_gravity_disable')); ?>" />
							</td>
						</tr>
					</table>
					<i style="font-size: 10px;">
						<?php echo sprintf(__('If you want to DISABLE the Turnstile widget on certain forms, enter the %s ID in this field.', 'simple-cloudflare-turnstile'), 'Gravity Form'); ?>
						<?php echo __('Separate each ID with a comma, for example: 5,10,21', 'simple-cloudflare-turnstile'); ?>
					</i>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://www.gravityforms.com/" target="_blank">' . __('Gravity Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Fluent Forms
			if (cft_is_plugin_active('fluentform/fluentform.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('Fluent Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('Enable on all Fluent Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_fluent" <?php if (get_option('cfturnstile_fluent')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo __('When enabled, Turnstile will be added above the submit button, on ALL your forms created with Fluent Forms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -10px;">
						<tr valign="top">
							<th scope="row"><?php echo __('Disabled Form IDs', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<input type="text" name="cfturnstile_fluent_disable" value="<?php echo sanitize_text_field(get_option('cfturnstile_fluent_disable')); ?>" />
							</td>
						</tr>
					</table>
					<i style="font-size: 10px;">
						<?php echo sprintf(__('If you want to DISABLE the Turnstile widget on certain forms, enter the %s ID in this field.', 'simple-cloudflare-turnstile'), 'Fluent Form'); ?>
						<?php echo __('Separate each ID with a comma, for example: 5,10,21', 'simple-cloudflare-turnstile'); ?>
					</i>

				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/fluentform/" target="_blank">' . __('Fluent Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Formidable Forms
			if (cft_is_plugin_active('formidable/formidable.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('Formidable Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('Enable on all Formidable Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_formidable" <?php if (get_option('cfturnstile_formidable')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo __('When enabled, Turnstile will be added above the submit button, on ALL your forms created with Formidable Forms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -15px;">

						<tr valign="top">
							<th scope="row"><?php echo __('Widget Location', 'simple-cloudflare-turnstile'); ?></th>
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
							<th scope="row"><?php echo __('Disabled Form IDs', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<input type="text" name="cfturnstile_formidable_disable" value="<?php echo sanitize_text_field(get_option('cfturnstile_formidable_disable')); ?>" />
							</td>
						</tr>
					</table>
					<i style="font-size: 10px;">
						<?php echo sprintf(__('If you want to DISABLE the Turnstile widget on certain forms, enter the %s ID in this field.', 'simple-cloudflare-turnstile'), 'Formidable Form'); ?>
						<?php echo __('Separate each ID with a comma, for example: 5,10,21', 'simple-cloudflare-turnstile'); ?>
					</i>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/formidable/" target="_blank">' . __('Formidable Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>
			
			<?php // Forminator Forms
			if (cft_is_plugin_active('forminator/forminator.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('Forminator Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('Enable on all Forminator Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_forminator" <?php if (get_option('cfturnstile_forminator')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo __('When enabled, Turnstile will be added above the submit button, on ALL your forms created with Forminator Forms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -15px;">

						<tr valign="top">
							<th scope="row"><?php echo __('Widget Location', 'simple-cloudflare-turnstile'); ?></th>
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
							<th scope="row"><?php echo __('Disabled Form IDs', 'simple-cloudflare-turnstile'); ?></th>
							<td>
								<input type="text" name="cfturnstile_forminator_disable" value="<?php echo sanitize_text_field(get_option('cfturnstile_forminator_disable')); ?>" />
							</td>
						</tr>
					</table>
					<i style="font-size: 10px;">
						<?php echo sprintf(__('If you want to DISABLE the Turnstile widget on certain forms, enter the %s ID in this field.', 'simple-cloudflare-turnstile'), 'Forminator Form'); ?>
						<?php echo __('Separate each ID with a comma, for example: 5,10,21', 'simple-cloudflare-turnstile'); ?>
					</i>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/forminator/" target="_blank">' . __('Forminator Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Elementor Forms
			if (
				cft_is_plugin_active('elementor/elementor.php')
				&& cft_is_plugin_active('elementor-pro/elementor-pro.php')
			) { ?>
				<button type="button" class="sct-accordion"><?php echo __('Elementor Forms', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('Enable on all Elementor Forms', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_elementor" <?php if (get_option('cfturnstile_elementor')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

					<?php echo __('When enabled, Turnstile will be added above the submit button, on ALL your forms created with Elementor Pro Forms.', 'simple-cloudflare-turnstile'); ?>

					<table class="form-table" style="margin-bottom: -15px;">

						<tr valign="top">
							<th scope="row"><?php echo __('Widget Location', 'simple-cloudflare-turnstile'); ?></th>
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
				array_push($not_installed, '<a href="https://elementor.com/features/form-builder/" target="_blank">' . __('Elementor Forms', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php if (cft_is_plugin_active('mailchimp-for-wp/mailchimp-for-wp.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('MC4WP: Mailchimp for WordPress', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<?php echo __('To add Turnstile to Mailchimp for WordPress, simply add this shortcode to any of your forms (in the form editor):', 'simple-cloudflare-turnstile'); ?>
					<br /><span style="color: red; font-size: 15px; font-weight: bold;">[mc4wp-simple-turnstile]</span>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/mailchimp-for-wp/" target="_blank">' . __('Mailchimp for WordPress', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // BuddyPress
			if (cft_is_plugin_active('buddypress/bp-loader.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('BuddyPress', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('BuddyPress Register', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_bp_register" <?php if (get_option('cfturnstile_bp_register')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

				</div>
			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/buddypress/" target="_blank">' . __('BuddyPress', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // bbPress
			if (cft_is_plugin_active('bbpress/bbpress.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('bbPress', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('bbPress Create Topic', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_bbpress_create" <?php if (get_option('cfturnstile_bbpress_create')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo __('bbPress Reply', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_bbpress_reply" <?php if (get_option('cfturnstile_bbpress_reply')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row"><?php echo __('Alignment', 'simple-cloudflare-turnstile'); ?></th>
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
								<?php echo __('Guest Users Only', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_bbpress_guest_only" <?php if (get_option('cfturnstile_bbpress_guest_only')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/bbpress/" target="_blank">' . __('bbPress', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // Ultimate Member
			if (cft_is_plugin_active('ultimate-member/ultimate-member.php')) { ?>
				<button type="button" class="sct-accordion"><?php echo __('Ultimate Member', 'simple-cloudflare-turnstile'); ?></button>
				<div class="sct-panel">

					<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

						<tr valign="top">
							<th scope="row">
								<?php echo __('UM Login Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_um_login" <?php if (get_option('cfturnstile_um_login')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo __('UM Register Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_um_register" <?php if (get_option('cfturnstile_um_register')) { ?>checked<?php } ?>></td>
						</tr>

						<tr valign="top">
							<th scope="row">
								<?php echo __('UM Password Reset Form', 'simple-cloudflare-turnstile'); ?>
							</th>
							<td><input type="checkbox" name="cfturnstile_um_password" <?php if (get_option('cfturnstile_um_password')) { ?>checked<?php } ?>></td>
						</tr>

					</table>

				</div>

			<?php
			} else {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/ultimate-member/" target="_blank">' . __('Ultimate Member', 'simple-cloudflare-turnstile') . '</a>');
			}
			?>

			<?php // wpDiscuz
			if (!cft_is_plugin_active('wpdiscuz/class.WpdiscuzCore.php')) {
				array_push($not_installed, '<a href="https://wordpress.org/plugins/wpdiscuz/" target="_blank">' . __('wpDiscuz', 'simple-cloudflare-turnstile') . '</a>');
			} ?>

			<?php // List of plugins not installed
			if (!empty($not_installed)) { ?>
				<br />

				<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

					<tr valign="top">
						<th scope="row">
							<span style="font-size: 19px;"><?php echo __('Other Integrations', 'simple-cloudflare-turnstile'); ?></span>
							<p>
								
								<?php echo __('You can also enable Turnstile on', 'simple-cloudflare-turnstile') . " ";
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

								<br />

								<?php echo __('Simply install/activate a plugin and the new settings dropdown will appear above.', 'simple-cloudflare-turnstile'); ?>

							</p>
						</th>
					</tr>

				</table>

			<?php } ?>

			<?php submit_button(); ?>

		</form>

		<hr style="margin: 20px 0 10px 0;"><br />

		<div class="sct-admin-promo">

			<p style="font-size: 15px; font-weight: bold;"><?php echo __('100% free plugin developed by', 'simple-cloudflare-turnstile'); ?> <a href="https://twitter.com/ElliotVS" target="_blank" title="@ElliotVS on Twitter"><span class="dashicons dashicons-twitter" style="margin-top: 5px; font-size: 15px; text-decoration: none;"></span>Elliot Sowersby</a> <a href="https://relywp.com/?utm_source=sct" target="_blank" title="RelyWP - WordPress Maintenance & Support"><span class="dashicons dashicons-admin-links" style="margin-top: 5px; font-size: 15px; text-decoration: none;"></span>RelyWP</a></p>

			<p style="font-size: 15px;">
				- <?php echo __('Like this plugin?', 'simple-cloudflare-turnstile'); ?> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank" title="<?php echo __('Review on WordPress.org', 'simple-cloudflare-turnstile'); ?>"><?php echo __('Please submit a review', 'simple-cloudflare-turnstile'); ?></a> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank" title="<?php echo __('Review on WordPress.org', 'simple-cloudflare-turnstile'); ?>" style="text-decoration: none;">
					<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span>
				</a></p>

			<p style="font-size: 15px;">- <?php echo __('Need help? Have a suggestion?', 'simple-cloudflare-turnstile'); ?> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile" target="_blank"><?php echo __('Create a support topic', 'simple-cloudflare-turnstile'); ?><span class="dashicons dashicons-external" style="font-size: 15px; margin-top: 5px; text-decoration: none;"></span></a></p>

			<p style="font-size: 15px;">
				- <?php echo __('Want to support the developer?', 'simple-cloudflare-turnstile'); ?> <?php echo __('Feel free to', 'simple-cloudflare-turnstile'); ?> <a href="https://www.paypal.com/donate/?hosted_button_id=RX28BBH7L5XDS" target="_blank"><?php echo __('Donate', 'simple-cloudflare-turnstile'); ?><span class="dashicons dashicons-external" style="font-size: 15px; margin-top: 5px; text-decoration: none;"></span></a>
			</p>

			<br />

			<p style="font-size: 12px;">
				<a href="https://translate.wordpress.org/projects/wp-plugins/simple-cloudflare-turnstile/" target="_blank"><?php echo __('Translate into your language', 'simple-cloudflare-turnstile'); ?><span class="dashicons dashicons-external" style="font-size: 15px; margin-top: 2px; text-decoration: none;"></span></a>
				<br />
				<a href="https://github.com/elliotvs/simple-cloudflare-turnstile" target="_blank"><?php echo __('View on GitHub', 'simple-cloudflare-turnstile'); ?><span class="dashicons dashicons-external" style="font-size: 15px; margin-top: 2px; text-decoration: none;"></span></a>
			</p>

		</div>

	</div>

<?php } ?>