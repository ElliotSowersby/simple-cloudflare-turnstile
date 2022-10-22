=== Simple Cloudflare Turnstile — The new user-friendly alternative to CAPTCHA ===
Contributors: ElliotVS, RelyWP
Tags: cloudflare,turnstile,captcha,woocommerce,spam
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LQ59TEPA5PTHE&source=url
Requires at least: 4.7
Tested up to: 6.0.3
Stable Tag: 1.7.0
License: GPLv3 or later.
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Add Cloudflare Turnstile to WordPress, WooCommerce, Contact Forms & more. The user-friendly, privacy-preserving reCAPTCHA alternative.

== Description ==

Easily add Cloudflare Turnstile to all your WordPress website forms to protect them from spam!

A user-friendly, privacy-preserving reCAPTCHA alternative.

## Supported Forms ##

You can currently enable Turnstile on the following forms:

**WordPress**
- WordPress Login Form
- WordPress Registration Form
- WordPress Password Reset Form
- WordPress Comments Form

**WooCommerce**
- WooCommerce Checkout
- WooCommerce Login Form
- WooCommerce Registration Form
- WooCommerce Password Reset Form

**Contact Form Plugins**
- WPForms
- Fluent Forms
- Contact Form 7

**Other Integrations**
- Mailchimp for WordPress Forms
- BuddyPress Registration Form
- bbPress Create Topic & Reply Forms

## What is Cloudflare Turnstile? ##

Cloudflare Turnstile delivers frustration-free, CAPTCHA-free web experiences to website visitors.

Turnstile stops abuse and confirms visitors are real without the data privacy concerns or awful UX that CAPTCHA thrusts on users.

Learn more here: <a href="https://www.cloudflare.com/en-gb/products/turnstile/">https://www.cloudflare.com/en-gb/products/turnstile/</a>

## Getting Started ##

Simply generate your site "key" and "secret" in Cloudflare, and add these to the plugin settings.

Choose which forms you want it to show on, and set the theme to either dark or light.

A new Cloudflare Turnstile challenge will then be displayed on your selected forms to protect them from spam!

## Is it free to use? ##

Yes, this plugin is completely free with no paid version, and it doesn't track your data. Cloudflare Turnstile is also a completely free service!

## Languages ##

Currently translated in <a href="https://translate.wordpress.org/projects/wp-plugins/simple-cloudflare-turnstile/">3 languages</a>. Thank you to all the <a href="https://translate.wordpress.org/projects/wp-plugins/simple-cloudflare-turnstile/contributors/">contributers</a>! If you would like to help contribute translations, please <a href="https://translate.wordpress.org/projects/wp-plugins/simple-cloudflare-turnstile/">click here</a>.

== Installation ==

1. Upload 'simple-cloudflare-turnstile' to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Customise plugin settings in WordPress admin at Settings > Cloudflare Turnstile 

== Screenshots ==

1. Example Turnstile on the WP Login Page
2. Example Turnstile on the WP Register Page
3. Example Turnstile on the WP Comments Form
4. Example Turnstile on the WooCommerce My Account Page
5. Example Turnstile on the WooCommerce Checkout Page
6. Example Turnstile on Contact Form 7
7. Example Settings Page

== Changelog ==

= Version 1.7.0 - 22nd October 2022 =
- New: Added integration with "Fluent Forms" plugin. Simply enable it in the settings, and Turnstile will be added to all your forms.
- Tweak: Improved alignment of the Turnstile widget when displayed on Contact Form 7.
- Fix: Fixed a string that was missing localisation.

= Version 1.6.2 - 21st October 2022 =
- Fix: Fixed some strings that were missing localisation.
- Fix: Fixed "Cannot modify header information – headers already sent" error showing on some sites when activating plugins.

= Version 1.6.1 - 21st October 2022 =
- Tweak: Small tweaks to admin settings page styling.
- Fix: Fixed to the "Test API Response" step. It should now properly block Turnstile from loading on the login page until it's successfully tested (new activations only).

= Version 1.6.0 - 21st October 2022 =
- New: Added integration with "bbPress" create topic and reply forms.
- New: Added a new "Test API Response" step to the settings page, whenever the API keys are updated to make sure it's working. Turnstile will not work on your login forms until the test is successfully complete.
- Tweak: Changed the way the error message is shown for WordPress comments.
- Fix: Fixed issue with Turnstile verification not working correctly on checkout if "Create an account?" was selected.

= Version 1.5.1 - 20th October 2022 =
- Tweak: Removed the "Disable Submit Button" feature for the "WooCommerce Checkout" form button, to prevent issues with it sometimes not working.
- Fix: Fixed "Call to undefined function is_plugin_active()" error showing on some sites.
- Fix: Fixed styling/scripts not loading on admin page on first load.

= Version 1.5.0 - 20th October 2022 =
- New: Added integration with "WPForms" plugin. Simply enable it in the settings, and Turnstile will be added to all your forms.
- Tweak: Updated the design of admin settings page slightly.
- Fix: Fixed issue causing "Disable Submit Button" option to not work on some sites.

= Version 1.4.0 - 19th October 2022 =
- New: Added integration with "MC4WP: Mailchimp for WordPress". You can now add Turnstile to any MC4WP form. Just add the shortcode: [mc4wp-simple-turnstile]

= Version 1.3.0 - 18th October 2022 =
- New: Added integration with "BuddyPress" registration form.
- Other: Restructured some of the code.

= Version 1.2.2 - 18th October 2022 =
- Tweak: Upon submitting checkout form, if there is an error, it will now automatically reset Turnstile challenge token.

= Version 1.2.1 - 18th October 2022 =
- Tweak: Update so the the required scripts are only loaded on pages that need it.
- Tweak: Added an "Auto" option to the "Theme" setting.
- Tweak: Upon submitting contact form 7, it will now automatically reset Turnstile challenge token.
- Fix: Fixed some strings with wrong text domain, that could not be translated.
- Fix: Fixed settings link in plugins list not working.

= Version 1.2.0 - 17th October 2022 =
- New: Added integration with "Contact Form 7". You can now add Turnstile to any CF7 forms. Just add the shortcode: [cf7-simple-turnstile]

= Version 1.1.2 - 17th October 2022 =
- New: Added a "Disable Submit Button" option. When enabled, the submit button for all forms will be disabled until the Turnstile widget says "Success".
- Tweak: The turnstile script will now load correctly when using a custom wp-login URL.
- Tweak: Added a redirect to settings page on activation.
- Tweak: Hidden WooCommerce form settings if it is not installed or activated.

= Version 1.1.1 - 15th October 2022 =
- Fix: Fixed PHP error sometimes showing when WooCommerce is not installed.

= Version 1.1.0 - 15th October 2022 =
- New: Added option to enable Turnstile on WordPress comments form.

= Version 1.0.0 - 15th October 2022 =
- Plugin Released