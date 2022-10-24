<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// create custom plugin settings menu
add_action('admin_menu', 'cfturnstile_create_menu');
function cfturnstile_create_menu() {
	//create new top-level menu
	add_submenu_page( 'options-general.php', 'Cloudflare Turnstile', 'Cloudflare Turnstile', 'manage_options', __FILE__, 'cfturnstile_settings_page' );
	//call register settings function
	add_action( 'admin_init', 'cfturnstile_register_settings' );
}

// Register Settings
function cfturnstile_register_settings() {
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_setup' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_key' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_secret' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_theme' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_script' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_disable_button' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_login' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_register' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_reset' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_comment' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_woo_checkout' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_guest_only' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_woo_login' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_woo_register' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_woo_reset' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_bp_register' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_wpforms' );
  register_setting( 'cfturnstile-settings-group', 'cfturnstile_gravity' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_fluent' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_bbpress_create' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_bbpress_reply' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_bbpress_guest_only' );
	register_setting( 'cfturnstile-settings-group', 'cfturnstile_bbpress_align' );
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
	<form action="" method="POST">
	<?php
	if(!empty(get_option('cfturnstile_key')) && !empty(get_option('cfturnstile_secret'))) {
		$check = cfturnstile_check();
		$success = '';
		$error = '';
		if(isset($check['success'])) $success = $check['success'];
		if(isset($check['error_code'])) $error = $check['error_code'];
		echo '<br/><div style="padding: 20px 20px 25px 20px; background: #fff; border-radius: 20px; max-width: 500px; border: 2px solid #d5d5d5;">';
		if($success != true) {
			echo '<p style="font-weight: 600; font-size: 19px; margin-top: 0; margin-bottom: 0;">' . __( 'Almost done...', 'simple-cloudflare-turnstile' ) . '</p>';
		}
		if(!isset($_POST['cf-turnstile-response'])) {
			echo '<p>'
			. '<span style="color: red; font-weight: bold;">' . __( 'API keys have been updated. Please test the Turnstile API response below.', 'simple-cloudflare-turnstile' ) . '</span>'
			. '<br/>'
			. __( 'Turnstile will not be added to any login forms until the test is successfully complete.', 'simple-cloudflare-turnstile' )
			. '</p>';
		} else {
			if($success == true) {
				echo '<p style="font-weight: bold; color: green; margin-top: -2px; margin-bottom: -4px;"><span class="dashicons dashicons-yes-alt"></span> ' . __( 'Success! Turnstile seems to be working correctly with your API keys.', 'simple-cloudflare-turnstile' ) . '</p>';
				update_option('cfturnstile_tested', 'yes');
			} else {
				if($error == "missing-input-response") {
					echo '<p style="font-weight: bold; color: red;">' . esc_html__( 'Please verify that you are human.', 'simple-cloudflare-turnstile' ) . '</p>';
				} else {
					echo '<p style="font-weight: bold; color: red;">' . esc_html__( 'Failed! There is an error with your API settings. Please check & update them.', 'simple-cloudflare-turnstile' ) . '</p>';
				}
			}
			if($error) {
				echo '<p style="font-weight: bold;">' . esc_html__( 'Error message:', 'simple-cloudflare-turnstile' ) . " " . cfturnstile_error_message($error) . '</p>';
			}
		}
		if($success != true) {
			echo '<div style="margin-left: 15px;">';
			echo cfturnstile_field_show('', '');
			echo '</div><div style="margin-bottom: -20px;"></div>';
			echo '<button type="submit" style="margin-top: 10px; padding: 7px 10px; background: #1c781c; color: #fff; font-size: 15px; font-weight: bold; border: 1px solid #176017; border-radius: 4px; cursor: pointer;">
			'.__( 'TEST API RESPONSE', 'simple-cloudflare-turnstile' ).' <span class="dashicons dashicons-arrow-right-alt"></span>
			</button>';
		}
		echo '</div>';
	}
	?>
	</form>
	<?php
}

// Show Settings Page
function cfturnstile_settings_page() {
?>
<div class="wrap">

<h1><?php echo __( 'Simple Cloudflare Turnstile', 'simple-cloudflare-turnstile' ); ?></h1>

<p style="margin-bottom: 0;"><?php echo __( 'Easily add the new "Cloudflare Turnstile" to your WordPress forms to help prevent spam.', 'simple-cloudflare-turnstile' ); ?> <?php echo __( 'Learn more:', 'simple-cloudflare-turnstile' ); ?> <a href="https://www.cloudflare.com/en-gb/products/turnstile/" target="_blank">https://www.cloudflare.com/en-gb/products/turnstile/</a></p>

<?php
if(empty(get_option('cfturnstile_tested')) || get_option('cfturnstile_tested') != 'yes') {
	echo cfturnstile_admin_test();
} else {
	echo '<p style="font-weight: bold; color: green;"><span class="dashicons dashicons-yes-alt"></span> ' . __( 'Success! Turnstile seems to be working correctly with your API keys.', 'simple-cloudflare-turnstile' ) . '</p>';
} ?>

<form method="post" action="options.php">

    <?php settings_fields( 'cfturnstile-settings-group' ); ?>
    <?php do_settings_sections( 'cfturnstile-settings-group' ); ?>

    <table class="form-table">

		<tr valign="top">
			<th scope="row" style="padding-bottom: 0;">
			<p style="font-size: 19px; margin-top: 0;"><?php echo __( 'API Key Settings', 'simple-cloudflare-turnstile' ); ?></p>
			<p style="margin-bottom: 2px;"><?php echo __( 'You can get your site key and secret from here:', 'simple-cloudflare-turnstile' ); ?> <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">https://dash.cloudflare.com/?to=/:account/turnstile</a></p>
			</th>
		</tr>

	</table>

	<table class="form-table">

        <tr valign="top">
        <th scope="row"><?php echo __( 'Site Key', 'simple-cloudflare-turnstile' ); ?></th>
        <td><input type="text" name="cfturnstile_key" value="<?php echo sanitize_text_field( get_option('cfturnstile_key') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row"><?php echo __( 'Site Secret', 'simple-cloudflare-turnstile' ); ?></th>
        <td><input type="text" name="cfturnstile_secret" value="<?php echo sanitize_text_field( get_option('cfturnstile_secret') ); ?>" /></td>
        </tr>

	</table>

	<table class="form-table">

		<tr valign="top">
			<th scope="row" style="font-size: 19px; padding-bottom: 5px;"><?php echo __( 'General Settings', 'simple-cloudflare-turnstile' ); ?></th>
		</tr>

		<tr valign="top">
			<th scope="row"><?php echo __( 'Theme', 'simple-cloudflare-turnstile' ); ?></th>
			<td>
				<select name="cfturnstile_theme">
					<option value="light"<?php if(!get_option('cfturnstile_theme') || get_option('cfturnstile_theme') == "light") { ?>selected<?php } ?>>
						<?php esc_html_e( 'Light', 'simple-cloudflare-turnstile' ); ?>
					</option>
					<option value="dark"<?php if(get_option('cfturnstile_theme') == "dark") { ?>selected<?php } ?>>
						<?php esc_html_e( 'Dark', 'simple-cloudflare-turnstile' ); ?>
					</option>
					<option value="auto"<?php if(get_option('cfturnstile_theme') == "auto") { ?>selected<?php } ?>>
						<?php esc_html_e( 'Auto', 'simple-cloudflare-turnstile' ); ?>
					</option>
				</select>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">
			<?php echo __( 'Disable Submit Button', 'simple-cloudflare-turnstile' ); ?>
			</th>
			<td><input type="checkbox" name="cfturnstile_disable_button" <?php if(get_option('cfturnstile_disable_button')) { ?>checked<?php } ?>>
			<i style="font-size: 10px;"><?php echo __( 'When enabled, the user will not be able to click submit until the Turnstile challenge is completed.', 'simple-cloudflare-turnstile' ); ?></i></td>
		</tr>

	</table>

	<table class="form-table" style="margin-bottom: -35px;">

		<tr valign="top">
			<th scope="row">
			<span style="font-size: 19px;"><?php echo __( 'Form Integrations', 'simple-cloudflare-turnstile' ); ?></span>
			<p><?php echo __( 'Select the dropdown for each integration, and choose when specific forms you want to enable Turnstile on.', 'simple-cloudflare-turnstile' ); ?></p>
			</th>
		</tr>

	</table>

	<button type="button" class="sct-accordion" id="sct-accordion-wordpress"><?php echo __( 'Default WordPress Forms', 'simple-cloudflare-turnstile' ); ?></button>
	<div class="sct-panel">

	<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

		<tr valign="top">
			<th scope="row">
			<?php echo __( 'WordPress Login', 'simple-cloudflare-turnstile' ); ?>
			</th>
			<td><input type="checkbox" name="cfturnstile_login" <?php if(get_option('cfturnstile_login')) { ?>checked<?php } ?>></td>
		</tr>

		<tr valign="top">
			<th scope="row">
			<?php echo __( 'WordPress Register', 'simple-cloudflare-turnstile' ); ?>
			</th>
			<td><input type="checkbox" name="cfturnstile_register" <?php if(get_option('cfturnstile_register')) { ?>checked<?php } ?>></td>
		</tr>

		<tr valign="top">
			<th scope="row">
			<?php echo __( 'WordPress Reset Password', 'simple-cloudflare-turnstile' ); ?>
			</th>
			<td><input type="checkbox" name="cfturnstile_reset" <?php if(get_option('cfturnstile_reset')) { ?>checked<?php } ?>></td>
		</tr>

		<tr valign="top">
			<th scope="row">
			<?php echo __( 'WordPress Comment', 'simple-cloudflare-turnstile' ); ?>
			</th>
			<td><input type="checkbox" name="cfturnstile_comment" <?php if(get_option('cfturnstile_comment')) { ?>checked<?php } ?>></td>
		</tr>

	</table>

	</div>

	<button type="button" class="sct-accordion"><?php echo __( 'WooCommerce Forms', 'simple-cloudflare-turnstile' ); ?></button>
	<div class="sct-panel">

		<?php if ( class_exists( 'WooCommerce' ) ) { ?>

		<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

			<tr valign="top">
				<th scope="row">
					<?php echo __( 'WooCommerce Checkout', 'simple-cloudflare-turnstile' ); ?>
					<br/><br/>
					<?php echo __( 'Guest Checkout Only', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td>
					<input type="checkbox" name="cfturnstile_woo_checkout" <?php if(get_option('cfturnstile_woo_checkout')) { ?>checked<?php } ?>>
					<br/><br/>
					<input type="checkbox" name="cfturnstile_guest_only" <?php if(get_option('cfturnstile_guest_only')) { ?>checked<?php } ?>>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">
				<?php echo __( 'WooCommerce Login', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td><input type="checkbox" name="cfturnstile_woo_login" <?php if(get_option('cfturnstile_woo_login')) { ?>checked<?php } ?>></td>
			</tr>

			<tr valign="top">
				<th scope="row">
				<?php echo __( 'WooCommerce Register', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td><input type="checkbox" name="cfturnstile_woo_register" <?php if(get_option('cfturnstile_woo_register')) { ?>checked<?php } ?>></td>
			</tr>

			<tr valign="top">
				<th scope="row">
				<?php echo __( 'WooCommerce Reset Password', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td><input type="checkbox" name="cfturnstile_woo_reset" <?php if(get_option('cfturnstile_woo_reset')) { ?>checked<?php } ?>></td>
			</tr>

		</table>

		<?php } else { ?>

			<span class="dashicons dashicons-warning"></span>
			<?php echo sprintf( __( 'Install %s to customise these settings.', 'simple-cloudflare-turnstile' ),
			'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>'); ?>

		<?php } ?>

    </div>

	<button type="button" class="sct-accordion"><?php echo __( 'Contact Form 7', 'simple-cloudflare-turnstile' ); ?></button>
	<div class="sct-panel">

		<?php if ( in_array( 'contact-form-7/wp-contact-form-7.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>

			<?php echo __( 'To add Turnstile to Contact Form 7, simply add this shortcode to any of your forms (in the form editor):', 'simple-cloudflare-turnstile' ); ?>
			<br/><span style="color: red; font-size: 15px; font-weight: bold;">[cf7-simple-turnstile]</span>

		<?php } else { ?>

			<span class="dashicons dashicons-warning"></span>
			<?php echo sprintf( __( 'Install %s to customise these settings.', 'simple-cloudflare-turnstile' ),
			'<a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7</a>'); ?>

		<?php } ?>

    </div>

	<button type="button" class="sct-accordion"><?php echo __( 'WPForms', 'simple-cloudflare-turnstile' ); ?></button>
	<div class="sct-panel">
		<?php if ( in_array( 'wpforms-lite/wpforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || in_array( 'wpforms/wpforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>

		<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

			<tr valign="top">
				<th scope="row">
				<?php echo __( 'Enable on all WPForms', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td><input type="checkbox" name="cfturnstile_wpforms" <?php if(get_option('cfturnstile_wpforms')) { ?>checked<?php } ?>></td>
			</tr>

		</table>

		<?php echo __( 'When enabled, Turnstile will be added above the submit button, on ALL your forms created with WPForms.', 'simple-cloudflare-turnstile' ); ?>

		<?php } else { ?>

			<span class="dashicons dashicons-warning"></span>
			<?php echo sprintf( __( 'Install %s to customise these settings.', 'simple-cloudflare-turnstile' ),
			'<a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">WPForms</a>'); ?>

		<?php } ?>
	</div>

  <button type="button" class="sct-accordion"><?php echo __( 'Gravity Forms', 'simple-cloudflare-turnstile' ); ?></button>
	<div class="sct-panel">
		<?php if ( in_array( 'gravityforms/gravityforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>

		<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

			<tr valign="top">
				<th scope="row">
				<?php echo __( 'Enable on all Gravity Forms', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td><input type="checkbox" name="cfturnstile_gravity" <?php if(get_option('cfturnstile_gravity')) { ?>checked<?php } ?>></td>
			</tr>

		</table>

		<?php echo __( 'When enabled, Turnstile will be added above the submit button, on ALL your forms created with Gravity Forms.', 'simple-cloudflare-turnstile' ); ?>

		<?php } else { ?>

			<span class="dashicons dashicons-warning"></span>
			<?php echo sprintf( __( 'Install %s to customise these settings.', 'simple-cloudflare-turnstile' ),
			'<a href="https://www.gravityforms.com/" target="_blank">Gravity Forms</a>'); ?>

		<?php } ?>
	</div>

	<button type="button" class="sct-accordion"><?php echo __( 'Fluent Forms', 'simple-cloudflare-turnstile' ); ?></button>
	<div class="sct-panel">
		<?php if ( in_array( 'fluentform/fluentform.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>

		<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

			<tr valign="top">
				<th scope="row">
				<?php echo __( 'Enable on all Fluent Forms', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td><input type="checkbox" name="cfturnstile_fluent" <?php if(get_option('cfturnstile_fluent')) { ?>checked<?php } ?>></td>
			</tr>

		</table>

		<?php echo __( 'When enabled, Turnstile will be added above the submit button, on ALL your forms created with Fluent Forms.', 'simple-cloudflare-turnstile' ); ?>

		<?php } else { ?>

			<span class="dashicons dashicons-warning"></span>
			<?php echo sprintf( __( 'Install %s to customise these settings.', 'simple-cloudflare-turnstile' ),
			'<a href="https://wordpress.org/plugins/fluent-smtp/" target="_blank">Fluent Forms</a>'); ?>

		<?php } ?>
	</div>

	<button type="button" class="sct-accordion"><?php echo __( 'MC4WP: Mailchimp for WordPress', 'simple-cloudflare-turnstile' ); ?></button>
	<div class="sct-panel">

		<?php if ( in_array( 'mailchimp-for-wp/mailchimp-for-wp.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>

			<?php echo __( 'To add Turnstile to Mailchimp for WordPress, simply add this shortcode to any of your forms (in the form editor):', 'simple-cloudflare-turnstile' ); ?>
			<br/><span style="color: red; font-size: 15px; font-weight: bold;">[mc4wp-simple-turnstile]</span>

		<?php } else { ?>

			<span class="dashicons dashicons-warning"></span>
			<?php echo sprintf( __( 'Install %s to customise these settings.', 'simple-cloudflare-turnstile' ),
			'<a href="https://wordpress.org/plugins/mailchimp-for-wp/" target="_blank">Mailchimp for WordPress</a>'); ?>

		<?php } ?>

	</div>

	<button type="button" class="sct-accordion"><?php echo __( 'BuddyPress', 'simple-cloudflare-turnstile' ); ?></button>
	<div class="sct-panel">
		<?php if ( in_array( 'buddypress/bp-loader.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>

		<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

			<tr valign="top">
				<th scope="row">
				<?php echo __( 'BuddyPress Register', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td><input type="checkbox" name="cfturnstile_bp_register" <?php if(get_option('cfturnstile_bp_register')) { ?>checked<?php } ?>></td>
			</tr>

		</table>

		<?php } else { ?>

			<span class="dashicons dashicons-warning"></span>
			<?php echo sprintf( __( 'Install %s to customise these settings.', 'simple-cloudflare-turnstile' ),
			'<a href="https://wordpress.org/plugins/buddypress/" target="_blank">BuddyPress</a>'); ?>

		<?php } ?>
	</div>

	<button type="button" class="sct-accordion"><?php echo __( 'bbPress', 'simple-cloudflare-turnstile' ); ?></button>
	<div class="sct-panel">
		<?php if ( in_array( 'bbpress/bbpress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>

		<table class="form-table" style="margin-top: -15px; margin-bottom: -10px;">

			<tr valign="top">
				<th scope="row">
				<?php echo __( 'bbPress Create Topic', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td><input type="checkbox" name="cfturnstile_bbpress_create" <?php if(get_option('cfturnstile_bbpress_create')) { ?>checked<?php } ?>></td>
			</tr>

			<tr valign="top">
				<th scope="row">
				<?php echo __( 'bbPress Reply', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td><input type="checkbox" name="cfturnstile_bbpress_reply" <?php if(get_option('cfturnstile_bbpress_reply')) { ?>checked<?php } ?>></td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php echo __( 'Alignment', 'simple-cloudflare-turnstile' ); ?></th>
				<td>
					<select name="cfturnstile_bbpress_align">
						<option value="left"<?php if(!get_option('cfturnstile_bbpress_align') || get_option('cfturnstile_bbpress_align') == "left") { ?>selected<?php } ?>>
							<?php esc_html_e( 'Left', 'simple-cloudflare-turnstile' ); ?>
						</option>
						<option value="right"<?php if(get_option('cfturnstile_bbpress_align') == "right") { ?>selected<?php } ?>>
							<?php esc_html_e( 'Right', 'simple-cloudflare-turnstile' ); ?>
						</option>
					</select>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">
				<?php echo __( 'Guest Users Only', 'simple-cloudflare-turnstile' ); ?>
				</th>
				<td><input type="checkbox" name="cfturnstile_bbpress_guest_only" <?php if(get_option('cfturnstile_bbpress_guest_only')) { ?>checked<?php } ?>></td>
			</tr>

		</table>

		<?php } else { ?>

			<span class="dashicons dashicons-warning"></span>
			<?php echo sprintf( __( 'Install %s to customise these settings.', 'simple-cloudflare-turnstile' ),
			'<a href="https://wordpress.org/plugins/bbpress/" target="_blank">BBPress</a>'); ?>

		<?php } ?>
	</div>

    <?php submit_button(); ?>

</form>

<div class="sct-admin-promo">

<p style="font-size: 15px; font-weight: bold;"><?php echo __( '100% free plugin developed by', 'simple-cloudflare-turnstile' ); ?> <a href="https://twitter.com/ElliotVS" target="_blank" title="@ElliotVS on Twitter">Elliot Sowersby</a> (<a href="https://www.relywp.com/?utm_source=sct" target="_blank" title="RelyWP - WordPress Maintenance & Support">RelyWP</a>) 🙌</p>

<p style="font-size: 15px;"><?php echo __( 'Find this plugin useful?', 'simple-cloudflare-turnstile' ); ?> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank"><?php echo __( 'Please submit a review', 'simple-cloudflare-turnstile' ); ?></a> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post" target="_blank">⭐️⭐️⭐️⭐️⭐️</a></p>

<p style="font-size: 15px;"><?php echo __( 'Need help? Have a suggestion?', 'simple-cloudflare-turnstile' ); ?> <a href="https://wordpress.org/support/plugin/simple-cloudflare-turnstile" target="_blank"><?php echo __( 'Create a new support topic.', 'simple-cloudflare-turnstile' ); ?></a></p>

</div>

</div>

<?php } ?>
