<div align="center">

# Simple CAPTCHA with Cloudflare Turnstile

**Add Cloudflare Turnstile to WordPress, WooCommerce, Contact Forms & more.**

The user-friendly, privacy-preserving reCAPTCHA alternative. 100% free.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/simple-cloudflare-turnstile?style=flat-square&color=f38020)](https://wordpress.org/plugins/simple-cloudflare-turnstile/)
[![Active Installations](https://img.shields.io/wordpress/plugin/installs/simple-cloudflare-turnstile?style=flat-square&color=f38020)](https://wordpress.org/plugins/simple-cloudflare-turnstile/)
[![Rating](https://img.shields.io/wordpress/plugin/rating/simple-cloudflare-turnstile?style=flat-square&color=f38020)](https://wordpress.org/plugins/simple-cloudflare-turnstile/reviews/)
[![WordPress Tested Up To](https://img.shields.io/wordpress/plugin/tested/simple-cloudflare-turnstile?style=flat-square)](https://wordpress.org/plugins/simple-cloudflare-turnstile/)
[![License: GPLv3](https://img.shields.io/badge/License-GPLv3-blue.svg?style=flat-square)](https://www.gnu.org/licenses/gpl-3.0.html)
[![Last Commit](https://img.shields.io/github/last-commit/ElliotSowersby/simple-cloudflare-turnstile?style=flat-square)](https://github.com/ElliotSowersby/simple-cloudflare-turnstile/commits/master)

[Download on WordPress.org](https://wordpress.org/plugins/simple-cloudflare-turnstile/) &nbsp;·&nbsp;
[Setup Guide](https://elliotsowersby.com/blog/setup-guide-turnstile/?utm_source=simplecloudflareturnstile&utm_medium=readme-guide) &nbsp;·&nbsp;
[Support Forum](https://wordpress.org/support/plugin/simple-cloudflare-turnstile/) &nbsp;·&nbsp;
[Changelog](./readme.txt)

</div>

---

> [!NOTE]
> **This file is the developer-facing documentation.** For the product description, full FAQ and changelog aimed at site owners, see [the WordPress.org plugin page](https://wordpress.org/plugins/simple-cloudflare-turnstile/) or [`readme.txt`](./readme.txt).

## Contents

- [Overview](#overview)
- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Developer documentation](#developer-documentation)
  - [Filters](#filters)
  - [Actions](#actions)
  - [Shortcodes](#shortcodes)
  - [Helper functions](#helper-functions)
- [Architecture](#architecture)
- [Adding an integration](#adding-an-integration)
- [Contributing](#contributing)
- [Support](#support)
- [Security](#security)
- [License](#license)

---

## Overview

Turnstile is Cloudflare's free, privacy-preserving CAPTCHA alternative. This plugin wires it into WordPress core forms, WooCommerce, and 25+ third-party form, membership and community plugins — with no paid tier, no upsells and no additional data collection.

| | |
|---|---|
| **Requires WordPress** | 4.7+ |
| **Requires WooCommerce** | 3.4+ (optional) |
| **Current version** | See the [`Version:` header](./simple-cloudflare-turnstile.php) |
| **Text domain** | `simple-cloudflare-turnstile` |
| **Function prefix** | `cfturnstile_` |
| **License** | GPLv3 or later |

### Features

- **Widget appearance** — theme, language, size, and always-on vs. interaction-only modes.
- **Disable submit button** until the challenge completes.
- **Whitelisting** — skip Turnstile for logged-in users, IP addresses and CIDR ranges, or user agents.
- **Failsafe mode** — when Cloudflare is unreachable, either allow submissions through or fall back to reCAPTCHA.
- **Analytics** — pass/fail counts per form, in the admin.
- **Debug logging** — record submission events for troubleshooting.
- **Export / import** — move settings between sites.
- **Resource hints** — optional `preconnect` for faster widget loads.
- Works with WordPress Multisite and most 2FA plugins.

<details>
<summary><strong>Supported integrations, and where each one lives</strong></summary>

<br>

| Integration | Source file |
|---|---|
| WordPress login / registration / password reset / comments | [`inc/wordpress.php`](inc/wordpress.php) |
| WooCommerce (checkout, pay for order, account, login, register, reset) | [`inc/integrations/ecommerce/woocommerce.php`](inc/integrations/ecommerce/woocommerce.php) |
| Easy Digital Downloads | [`inc/integrations/ecommerce/edd.php`](inc/integrations/ecommerce/edd.php) |
| Paid Memberships Pro | [`inc/integrations/ecommerce/pmp.php`](inc/integrations/ecommerce/pmp.php) |
| Sunshine Photo Cart | [`inc/integrations/ecommerce/sunshine-photo-cart.php`](inc/integrations/ecommerce/sunshine-photo-cart.php) |
| Contact Form 7 | [`inc/integrations/forms/contact-form-7.php`](inc/integrations/forms/contact-form-7.php) |
| Fluent Forms | [`inc/integrations/forms/fluent-forms.php`](inc/integrations/forms/fluent-forms.php) |
| Formidable Forms | [`inc/integrations/forms/formidable.php`](inc/integrations/forms/formidable.php) |
| Forminator | [`inc/integrations/forms/forminator.php`](inc/integrations/forms/forminator.php) |
| Gravity Forms | [`inc/integrations/forms/gravity-forms.php`](inc/integrations/forms/gravity-forms.php) |
| Jetpack Forms | [`inc/integrations/forms/jetpack.php`](inc/integrations/forms/jetpack.php) |
| Kadence Forms | [`inc/integrations/forms/kadence.php`](inc/integrations/forms/kadence.php) |
| SureForms | [`inc/integrations/forms/sureforms.php`](inc/integrations/forms/sureforms.php) |
| WPForms | [`inc/integrations/forms/wpforms.php`](inc/integrations/forms/wpforms.php) |
| MemberPress | [`inc/integrations/membership/memberpress.php`](inc/integrations/membership/memberpress.php) |
| Simple Membership | [`inc/integrations/membership/simple-membership.php`](inc/integrations/membership/simple-membership.php) |
| Ultimate Member | [`inc/integrations/membership/ultimate-member.php`](inc/integrations/membership/ultimate-member.php) |
| WP-Members | [`inc/integrations/membership/wp-members.php`](inc/integrations/membership/wp-members.php) |
| WP User Manager | [`inc/integrations/membership/wp-user-manager.php`](inc/integrations/membership/wp-user-manager.php) |
| WP User Frontend | [`inc/integrations/membership/wpuf.php`](inc/integrations/membership/wpuf.php) |
| MailPoet | [`inc/integrations/newsletters/mailpoet.php`](inc/integrations/newsletters/mailpoet.php) |
| Mailchimp for WordPress | [`inc/integrations/newsletters/mc4wp.php`](inc/integrations/newsletters/mc4wp.php) |
| bbPress | [`inc/integrations/community/bbpress.php`](inc/integrations/community/bbpress.php) |
| BuddyPress | [`inc/integrations/community/buddypress.php`](inc/integrations/community/buddypress.php) |
| wpDiscuz | [`inc/integrations/community/wpdiscuz.php`](inc/integrations/community/wpdiscuz.php) |
| Elementor Pro Forms | [`inc/integrations/other/elementor.php`](inc/integrations/other/elementor.php) |
| Clean Login | [`inc/integrations/other/clean-login.php`](inc/integrations/other/clean-login.php) |
| FluentAuth | [`inc/integrations/other/fluent-auth.php`](inc/integrations/other/fluent-auth.php) |
| Wordfence (2FA co-existence) | [`inc/integrations/other/wordfence.php`](inc/integrations/other/wordfence.php) |
| Caching / optimisation plugin exclusions | [`inc/integrations/other/perf.php`](inc/integrations/other/perf.php) |

</details>

---

## Quick start

1. Generate a **Site Key** and **Secret Key** in your [Cloudflare dashboard](https://dash.cloudflare.com/?to=/:account/turnstile).
2. Enter both in **Settings → Cloudflare Turnstile**.
3. Tick the forms you want protected, then **Save Changes**.
4. Click **TEST API RESPONSE** to confirm the keys verify correctly.

> [!TIP]
> Step 4 is not optional in practice — an untested secret key is the most common cause of "the widget shows but submissions fail". A wrong secret surfaces as the `invalid-input-secret` error code, which also triggers an admin email notice.

Full walkthrough: [setup guide](https://elliotsowersby.com/blog/setup-guide-turnstile/?utm_source=simplecloudflareturnstile&utm_medium=readme-guide) · [video](https://www.youtube.com/watch?v=Yn8X_GsTFnU)

---

## Configuration

### Keys via `wp-config.php`

Keys can be defined as constants instead of being stored in the database — useful for version-controlled or multi-environment setups, where staging and production need different keys without touching the options table.

```php
define( 'CF_TURNSTILE_SITE_KEY',   'your-site-key' );
define( 'CF_TURNSTILE_SECRET_KEY', 'your-secret-key' );
```

When these are defined ([`inc/config-keys.php`](inc/config-keys.php)):

- They override the saved options **everywhere** the plugin reads them.
- Saving the settings page will **not** overwrite the stored database values — existing options are left intact, so removing the constants restores whatever was there before.

### Cloudflare test keys

For local development and automated browser tests, use Cloudflare's [dummy sitekeys and secret keys](https://developers.cloudflare.com/turnstile/troubleshooting/testing/) — the "always passes" pair lets forms submit without a real challenge on a non-public hostname.

---

## Developer documentation

Everything below is verified against the current source. File references point at the `apply_filters()` / `do_action()` call site.

### Filters

<details>
<summary><code>cfturnstile_widget_disable</code> — bypass Turnstile entirely</summary>

<br>

**Fired in:** [`inc/turnstile.php:17`](inc/turnstile.php#L17) (render) and [`inc/turnstile.php:234`](inc/turnstile.php#L234) (verification)

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$disable` | `bool` | `false` | Return `true` to disable Turnstile for this request. |

Returning `true` both suppresses the widget markup **and** makes `cfturnstile_check()` return success without contacting Cloudflare.

```php
// Skip Turnstile on a specific page only.
add_filter( 'cfturnstile_widget_disable', function ( $disable ) {
    return is_page( 'internal-request-form' ) ? true : $disable;
} );
```

> [!WARNING]
> This is a full bypass of the spam protection, not just a visual change. Gate it as narrowly as you can, and never on a condition an anonymous visitor controls (a query string, a cookie, a request header).

</details>

<details>
<summary><code>cfturnstile_whitelisted</code> — seed the whitelist decision</summary>

<br>

**Fired in:** [`inc/whitelist.php:15`](inc/whitelist.php#L15)

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$whitelisted` | `bool` | `false` | Starting value, before the built-in rules run. |

The filter runs **first**, then the configured rules (logged-in users, IP / CIDR list, user-agent list) run against the result. Those rules can only escalate the value to `true` — so returning `false` does **not** override a visitor who is whitelisted by settings, while returning `true` does force a whitelist.

The function short-circuits to `false` on the plugin's own settings screen, so this filter never fires there.

```php
// Whitelist requests coming from an internal reverse proxy.
add_filter( 'cfturnstile_whitelisted', function ( $whitelisted ) {
    return ! empty( $_SERVER['HTTP_X_INTERNAL_PROXY'] ) ? true : $whitelisted;
} );
```

</details>

<details>
<summary><code>cfturnstile_wp_login_checks</code> — skip the global WP login check</summary>

<br>

**Fired in:** [`inc/wordpress.php:59`](inc/wordpress.php#L59)

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$skip` | `bool` | `false` | Must return **exactly** `true` (strict `===` comparison). |

The WordPress login check hooks `authenticate` globally, which means it also fires for login forms owned by other plugins. Bundled integrations (WooCommerce, Ultimate Member, Simple Membership, FluentAuth, Wordfence, Sunshine) use this filter so their own form isn't verified twice — the second check would fail, because the single-use token has already been spent.

Use it when you render your own login form and call `cfturnstile_check()` yourself.

</details>

<details>
<summary><code>cfturnstile_wp_register_checks</code> — skip the global WP registration check</summary>

<br>

**Fired in:** [`inc/wordpress.php:199`](inc/wordpress.php#L199)

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$skip` | `bool` | `false` | Must return **exactly** `true` (strict `===` comparison). |

The registration counterpart of `cfturnstile_wp_login_checks`, hooked on `registration_errors`. Same reasoning: return `true` when your own integration has already verified the token for this submission.

</details>

<details>
<summary><code>cfturnstile_token_refresh_skip_forms</code> — exclude forms from the post-submit widget reset</summary>

<br>

**Fired in:** [`simple-cloudflare-turnstile.php:90`](simple-cloudflare-turnstile.php#L90)

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$selectors` | `string` | `'form.checkout, form.woocommerce-checkout'` | Comma-separated CSS selector list. |

After a form submits without leaving the page (AJAX / SPA forms), the plugin resets the widget so a retry gets a fresh token rather than re-posting a spent one. Forms matching these selectors are excluded.

Add any form that **resubmits itself** after async work — a reset mid-flight would swap the token out from under the pending submission.

```php
add_filter( 'cfturnstile_token_refresh_skip_forms', function ( $selectors ) {
    return $selectors . ', form.my-multi-step-form';
} );
```

</details>

<details>
<summary><code>cfturnstile_woo_deferred_checkout_markers</code> — recognise a 3DS deferral</summary>

<br>

**Fired in:** [`inc/integrations/ecommerce/woocommerce.php:196`](inc/integrations/ecommerce/woocommerce.php#L196)

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$markers` | `array` | `array( 'globalpayments_gpapi_checkout_validated' )` | WooCommerce notice markers. |

Some payment gateways halt checkout to run 3D Secure, then resubmit the same form carrying the same single-use token. When one of these markers is present in the Woo notices, the existing verified flag is kept alive instead of being cleared.

Add your gateway's marker if 3DS resubmission fails Turnstile verification on the second pass.

> [!NOTE]
> A marker only **extends** an existing pass — it never grants one. Markers are added even when the challenge failed.

</details>

<details>
<summary><code>cfturnstile_woo_deferred_checkout_expiry</code> — how long a deferred pass survives</summary>

<br>

**Fired in:** [`inc/integrations/ecommerce/woocommerce.php:524`](inc/integrations/ecommerce/woocommerce.php#L524)

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$seconds` | `int` | `900` | Lifetime, in seconds, of a verification held open for 3DS. |

The deadline is fixed at the **first** deferral, so repeated markers cannot extend it indefinitely.

</details>

<details>
<summary><code>cfturnstile_skip_on_express_pay</code> — skip verification for express checkout</summary>

<br>

**Fired in:** [`inc/integrations/ecommerce/woocommerce.php:473`](inc/integrations/ecommerce/woocommerce.php#L473)

| Parameter | Type | Description |
|---|---|---|
| `$skip` | `bool` | Defaults to `true` for WooPayments / Stripe when an express payment type is detected. |
| `$payment_method` | `string` | The gateway id, e.g. `woocommerce_payments`, `stripe`. |
| `$payment_data` | `array` | Raw payment data from the Store API request. |
| `$request` | `WP_REST_Request` | The Store API checkout request. |

Apple Pay / Google Pay sheets submit without ever rendering the checkout form, so there is no widget and no token to verify. This filter lets other gateways declare the same.

</details>

<details>
<summary><code>cfturnstile_is_partial_checkout_render</code> — mark a throwaway checkout render</summary>

<br>

**Fired in:** [`inc/integrations/ecommerce/woocommerce.php:110`](inc/integrations/ecommerce/woocommerce.php#L110)

| Parameter | Type | Description |
|---|---|---|
| `$partial` | `bool` | `true` when the current render is a fragment, not the real checkout form. |

Page builders can render the checkout template several times per page — Divi's WooCommerce modules each swap in their own partial. Only the real form should get a widget; the built-in detection covers the known Divi modules, and this filter extends it to other builders.

</details>

<details>
<summary><code>cfturnstile-settings-not-installed</code> — the "not installed" list on the settings page</summary>

<br>

**Fired in:** [`inc/admin/admin-options.php:2165`](inc/admin/admin-options.php#L2165)

| Parameter | Type | Description |
|---|---|---|
| `$not_installed` | `array` | HTML anchor strings for supported plugins that aren't active. |

Pairs with the `cfturnstile-settings-section` action for add-ons that register their own integration.

</details>

### Actions

<details>
<summary><code>cfturnstile_after_check</code> — inspect every verification result</summary>

<br>

**Fired in:** [`inc/turnstile.php:326`](inc/turnstile.php#L326), and three times in [`inc/failsafe.php`](inc/failsafe.php) for the reCAPTCHA fallback path.

| Parameter | Type | Description |
|---|---|---|
| `$response` | `stdClass` | Decoded siteverify body. On the failsafe path this is a minimal `(object) array( 'success' => bool )`. |
| `$results` | `array` | `[ 'success' => bool, 'error_code' => string ]` — `error_code` is only set on failure. |
| `$form_action` | `string` | The form identifier. **Only passed from `cfturnstile_check()`** — the failsafe path fires with two arguments. |

```php
add_action( 'cfturnstile_after_check', function ( $response, $results, $form_action = '' ) {
    if ( empty( $results['success'] ) ) {
        error_log( sprintf(
            'Turnstile failed on %s: %s',
            $form_action ? $form_action : 'unknown',
            isset( $results['error_code'] ) ? $results['error_code'] : 'no-code'
        ) );
    }
}, 10, 3 );
```

> [!IMPORTANT]
> Give `$form_action` a default value in your callback. Registering with `3` accepted args is safe, but the failsafe path supplies only two — a required third parameter would fatal there.

</details>

<details>
<summary><code>cfturnstile_before_field</code> / <code>cfturnstile_after_field</code> — wrap the widget markup</summary>

<br>

**Fired in:** [`inc/turnstile.php:36`](inc/turnstile.php#L36) and [`inc/turnstile.php:77`](inc/turnstile.php#L77), and around the reCAPTCHA fallback in [`inc/failsafe.php`](inc/failsafe.php).

| Hook | Parameters |
|---|---|
| `cfturnstile_before_field` | `string $unique_id` |
| `cfturnstile_after_field` | `string $unique_id`, `string $button_id` |

`$unique_id` is the suffix of the widget's DOM id (`cf-turnstile{$unique_id}`). `$button_id` is the CSS selector of the submit button, or an empty string when submit-button disabling doesn't apply.

The plugin attaches several of its own callbacks to `cfturnstile_after_field`, so mind the priorities:

| Priority | Callback | Purpose |
|---|---|---|
| `5` | `cfturnstile_failed_text` | Error message container |
| `10` | `cfturnstile_disable_button_styles` | Submit-button disabling |
| `10` | `cfturnstile_force_render` | Queues explicit widget rendering |
| `15` | `cfturnstile_always_br` | Spacing for always-on mode |
| `20` | `cfturnstile_admin_styles` | Admin / AJAX context styles |

</details>

<details>
<summary><code>cfturnstile_enqueue_scripts</code> — load the Turnstile assets</summary>

<br>

**Fired in:** [`inc/turnstile.php:35`](inc/turnstile.php#L35), [`inc/wordpress.php:254`](inc/wordpress.php#L254), [`inc/integrations/community/wpdiscuz.php:10`](inc/integrations/community/wpdiscuz.php#L10)

Takes no arguments. This is an action you **fire**, not one you usually hook: call `do_action( 'cfturnstile_enqueue_scripts' )` when you render widget markup by hand rather than through `cfturnstile_field_show()`, so the Cloudflare API script and plugin styles are enqueued.

</details>

<details>
<summary><code>cfturnstile_enqueue_scripts_custom</code> — add your own integration assets</summary>

<br>

**Fired in:** [`simple-cloudflare-turnstile.php:171`](simple-cloudflare-turnstile.php#L171), at the end of `cfturnstile_script_enqueue()`.

Takes no arguments. Hook here to enqueue integration JavaScript with the plugin's own handle as a dependency:

```php
add_action( 'cfturnstile_enqueue_scripts_custom', function () {
    wp_enqueue_script(
        'my-turnstile-integration',
        plugins_url( '/js/my-integration.js', __FILE__ ),
        array( 'cfturnstile' ),
        '1.0',
        array( 'in_footer' => true )
    );
} );
```

</details>

<details>
<summary><code>cfturnstile_wp_login_failed</code> — react to a failed login challenge</summary>

<br>

**Fired in:** [`inc/wordpress.php:76`](inc/wordpress.php#L76)

Takes no arguments. Fires after the WordPress login check fails, immediately before the `WP_Error` is returned. Useful for feeding your own rate-limiting or logging.

</details>

<details>
<summary><code>cfturnstile-settings-section</code> — output custom settings</summary>

<br>

**Fired in:** [`inc/admin/admin-options.php:2164`](inc/admin/admin-options.php#L2164)

Takes no arguments. Prints inside the settings form, so an add-on can render its own rows for a custom integration.

> [!WARNING]
> Any option your section renders must also be registered in [`inc/admin/register-settings.php`](inc/admin/register-settings.php). The save handler works from that list — a field the UI shows but the allowlist doesn't include gets wiped on every save.

</details>

### Shortcodes

| Shortcode | Attributes | Registered in | Notes |
|---|---|---|---|
| `[simple-turnstile]` | — | [`inc/turnstile.php:353`](inc/turnstile.php#L353) | Generic widget with a random unique id. |
| `[cf7-simple-turnstile]` | — | [`inc/integrations/forms/contact-form-7.php:7`](inc/integrations/forms/contact-form-7.php#L7) | Targets `.wpcf7-submit`. Renders nothing when the visitor is whitelisted, leaving CF7's markup untouched. |
| `[gravity-simple-turnstile]` | `id` **(required)** — the Gravity form id | [`inc/integrations/forms/gravity-forms.php:9`](inc/integrations/forms/gravity-forms.php#L9) | Targets `.gform_button`. |
| `[mc4wp-simple-turnstile]` | — | [`inc/integrations/newsletters/mc4wp.php:7`](inc/integrations/newsletters/mc4wp.php#L7) | Targets the MC4WP submit input. |

> [!IMPORTANT]
> The Contact Form 7 shortcode must be placed in the CF7 form template so that **CF7 itself** substitutes it during its own render. Running `do_shortcode()` over an already-assembled CF7 form instead will break form-tag handling.

### Helper functions

<details>
<summary><code>cfturnstile_field_show()</code> — print a widget</summary>

<br>

```php
cfturnstile_field_show(
    string $button_id = '',   // CSS selector of the submit button (enables submit-disabling)
    string $callback  = '',   // JS callback name invoked on successful challenge
    string $form_name = '',   // Becomes data-action; also the label used in analytics
    string $unique_id = '',   // Suffix for the widget DOM id — must be unique per page
    string $class     = ''    // Extra CSS classes on the widget container
): void
```

Defined in [`inc/turnstile.php:15`](inc/turnstile.php#L15). Prints markup directly and returns nothing. It handles whitelisting, the `cfturnstile_widget_disable` filter, failsafe rendering and script enqueuing internally — so calling it is normally all an integration needs to do.

Pass a genuinely unique `$unique_id` (the bundled integrations use `wp_rand()` or the form id). Duplicate DOM ids mean Cloudflare renders into the first match only.

</details>

<details>
<summary><code>cfturnstile_check()</code> — verify a submitted token</summary>

<br>

```php
cfturnstile_check(
    string $postdata    = '',  // Token; falls back to $_POST['cf-turnstile-response']
    string $form_action = ''   // Form identifier, passed on to cfturnstile_after_check
): array
```

Defined in [`inc/turnstile.php:223`](inc/turnstile.php#L223). Returns:

```php
array(
    'success'    => true|false,
    'error_code' => 'invalid-input-response', // present on failure only
);
```

Returns `success => true` early — without calling Cloudflare — when the visitor is whitelisted or `cfturnstile_widget_disable` returns `true`. Failsafe mode is handled inside, including the reCAPTCHA fallback.

```php
add_action( 'my_plugin_before_submit', function () {
    $check = cfturnstile_check( '', 'my-plugin-form' );
    if ( empty( $check['success'] ) ) {
        wp_die( esc_html( cfturnstile_failed_message() ) );
    }
} );
```

> [!CAUTION]
> Turnstile tokens are single-use. Calling `cfturnstile_check()` twice for the same submission will fail the second time — which is exactly why the `cfturnstile_wp_login_checks` / `cfturnstile_wp_register_checks` filters exist.

</details>

<details>
<summary>Other functions worth knowing</summary>

<br>

| Function | File | Returns |
|---|---|---|
| `cfturnstile_whitelisted()` | [`inc/whitelist.php`](inc/whitelist.php) | `bool` — whether Turnstile should be skipped for this visitor. |
| `cfturnstile_get_ip()` | [`inc/whitelist.php`](inc/whitelist.php) | `string` — visitor IP, preferring `CF-Connecting-IP`, rejecting private and reserved ranges. |
| `cfturnstile_failed_message()` | [`inc/errors.php`](inc/errors.php) | `string` — the configured error message, or the translated default. |
| `cfturnstile_error_message( $code )` | [`inc/errors.php`](inc/errors.php) | `string` — human-readable text for a Cloudflare error code. |
| `cfturnstile_is_cloudflare_down()` | [`inc/failsafe.php`](inc/failsafe.php) | `bool` — whether failsafe mode should engage. |

</details>

---

## Architecture

```
simple-cloudflare-turnstile.php   Plugin header, script enqueuing, token-refresh JS
uninstall.php                     Option cleanup on delete
├── inc/
│   ├── turnstile.php             Widget rendering + cfturnstile_check() — the core
│   ├── verification.php          Verification state helpers (set / get / clear verified)
│   ├── whitelist.php             Whitelist rules, IP resolution and CIDR matching
│   ├── failsafe.php              Cloudflare-down detection, allow-through / reCAPTCHA fallback
│   ├── errors.php                Error strings and Cloudflare error-code mapping
│   ├── analytics.php             Pass/fail tracking per form
│   ├── config-keys.php           wp-config.php constant overrides for the API keys
│   ├── admin/                    Settings page, tabs, registered options, export/import
│   └── integrations/             One file per third-party plugin, grouped by category
│       └── forms/ ecommerce/ membership/ newsletters/ community/ other/
├── js/
│   ├── disable-submit.js         Submit-button gating
│   ├── interaction-label.js      Interaction-only appearance label
│   └── integrations/             Per-plugin front-end glue (Woo, Elementor, MailPoet, Blocksy)
└── css/
```

### Request lifecycle

```
RENDER                                      VERIFY
──────                                      ──────
cfturnstile_field_show()                    cfturnstile_check( $token, $form )
  │                                           │
  ├─ filter cfturnstile_widget_disable ┐      ├─ cfturnstile_whitelisted() ────────┐
  ├─ cfturnstile_whitelisted() ────────┼─ no  ├─ filter cfturnstile_widget_disable ┼─ success
  ├─ failsafe? → reCAPTCHA widget      │  op  ├─ failsafe? → reCAPTCHA / allow     │
  ├─ do_action cfturnstile_enqueue_scripts    ├─ POST to Cloudflare siteverify
  ├─ do_action cfturnstile_before_field       ├─ map error codes
  ├─ <div class="cf-turnstile" …>             └─ do_action cfturnstile_after_check
  └─ do_action cfturnstile_after_field
```

### Things that bite

A few behaviours are non-obvious enough to be worth stating outright:

- **Tokens are single-use.** Any path that verifies twice fails the second time. This drives the login/register skip filters and the deferred-checkout handling.
- **WooCommerce form handlers hook globally.** Checkout and account handlers can fire on any URL, so gating on the request path or a query parameter is a bypass, not a safeguard — gate on WooCommerce state instead.
- **Optimisation plugins must not defer the plugin's own JS.** [`inc/integrations/other/perf.php`](inc/integrations/other/perf.php) un-delays these scripts for Autoptimize, WP Rocket, Perfmatters, LiteSpeed and SiteGround. Any new plugin JS that depends on jQuery must be safe to evaluate at that point.
- **Page builders re-render forms.** Divi renders the checkout once per WooCommerce module, and Elementor caches popup markup as an HTML string and re-parses it — both produce renders that look like a real form but aren't.

---

## Adding an integration

1. Create `inc/integrations/<category>/<plugin>.php` and include it from [`simple-cloudflare-turnstile.php`](simple-cloudflare-turnstile.php).
2. Wrap everything in the enabling option, e.g. `if ( get_option( 'cfturnstile_myplugin' ) ) { … }`.
3. **Render** — hook the plugin's "before submit button" equivalent and call `cfturnstile_field_show()` with a unique id, the submit selector, and a `$form_name` for analytics.
4. **Verify** — hook its validation filter, call `cfturnstile_check()`, and return `cfturnstile_failed_message()` as the error.
5. If the integration owns a login or registration form, return `true` from `cfturnstile_wp_login_checks` / `cfturnstile_wp_register_checks` while it's handling the request, so the global check doesn't spend the token first.
6. Register the new option in [`inc/admin/register-settings.php`](inc/admin/register-settings.php) **and** add the settings-page UI in [`inc/admin/admin-options.php`](inc/admin/admin-options.php) — both, or saving will wipe it.
7. Add the plugin to the "not installed" detection so the setting is explained when the plugin is absent.
8. Update the supported-forms list in [`readme.txt`](./readme.txt) and the table in this file.

---

## Contributing

Issues and pull requests are welcome. A few conventions to match:

- **Coding standards** — WordPress PHP coding standards; tabs for indentation; every function and option prefixed `cfturnstile_`.
- **Security** — escape on output (`esc_attr()`, `esc_html()`), sanitise on input, and check capabilities plus nonces on anything that writes.
- **i18n** — all user-facing strings through the `simple-cloudflare-turnstile` text domain. Translations are managed on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/simple-cloudflare-turnstile/), not in this repo.
- **No build step** — the plugin ships plain PHP, JS and CSS, with no compilation and no Composer or npm dependencies.
- **Backwards compatibility** — the plugin supports WordPress 4.7+, so avoid newer-only APIs.

Release checklist:

- [ ] Bump `Version:` in [`simple-cloudflare-turnstile.php`](simple-cloudflare-turnstile.php)
- [ ] Bump `Stable Tag:` in [`readme.txt`](./readme.txt)
- [ ] Add the changelog entry to [`readme.txt`](./readme.txt)
- [ ] Update `Tested up to:` if a new WordPress version has been verified
- [ ] Update `WC tested up to:` if a new WooCommerce version has been verified

---

## Support

The plugin is 100% free, with no paid tier, developed as a way to give back to the WordPress community.

- **Support requests:** the [WordPress.org support forum](https://wordpress.org/support/plugin/simple-cloudflare-turnstile/#new-topic-0) — the only place to get free support from the developer and the community. Please bear in mind that a response to every ticket can't be guaranteed.
- **Bug reports and feature requests:** [GitHub issues](https://github.com/ElliotSowersby/simple-cloudflare-turnstile/issues).
- **Turnstile itself:** the [Cloudflare Turnstile docs](https://developers.cloudflare.com/turnstile/) and [client-side error codes](https://developers.cloudflare.com/turnstile/troubleshooting/client-side-errors/error-codes/).

<details>
<summary><strong>Common questions</strong></summary>

<br>

**The widget isn't appearing.**
Work through the [setup guide](https://elliotsowersby.com/blog/setup-guide-turnstile/?utm_source=simplecloudflareturnstile&utm_medium=readme-guide) and run **TEST API RESPONSE** on the settings page. If the site uses a caching or optimisation plugin, confirm the Turnstile scripts aren't being deferred, combined or delayed.

**I see a 401 in the browser console.**
Safe to ignore — it's a request for a Private Access Token that the device or browser doesn't support yet. [Cloudflare's explanation](https://developers.cloudflare.com/turnstile/frequently-asked-questions/#i-am-seeing-a-401-error-in-your-console-during-a-turnstile-security-check-is-this-a-problem).

**Is this better for data privacy and GDPR?**
Cloudflare states that Turnstile "never looks for cookies (like a login cookie), or uses cookies to collect or store information of any kind", and that they never harvest data for ad retargeting. See [their announcement post](https://blog.cloudflare.com/turnstile-private-captcha-alternative/#ux-isn-t-the-only-big-problem-with-captcha-so-is-privacy), [GDPR compliance](https://www.cloudflare.com/en-gb/gdpr/introduction/) and [Data Processing Addendum](https://www.cloudflare.com/en-gb/cloudflare-customer-dpa/).

**Will more integrations be added?**
Likely, based on user feedback — suggest one via a [support topic](https://wordpress.org/support/plugin/simple-cloudflare-turnstile/#new-topic-0) or a GitHub issue.

**Is it really free?**
Yes. No paid version, no upsells, no additional data tracking. Cloudflare Turnstile is a free service too.

</details>

---

## Security

Please report security vulnerabilities through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/simple-cloudflare-turnstile), **not** as a public GitHub issue. The Patchstack team validate, triage and handle disclosure.

---

## License

Released under the [GPLv3 or later](https://www.gnu.org/licenses/gpl-3.0.html).

### Trademark notice

Cloudflare, the Cloudflare logo, and Cloudflare Workers are trademarks and/or registered trademarks of Cloudflare, Inc. in the United States and other jurisdictions. This plugin is not affiliated with, endorsed by, or sponsored by Cloudflare, Inc.

---

<div align="center">

Developed and maintained by [Elliot Sowersby](https://www.relywp.com) ([@ElliotSowersby](https://twitter.com/ElliotSowersby)).

If this plugin helps you, please consider [leaving a review](https://wordpress.org/support/plugin/simple-cloudflare-turnstile/reviews/#new-post) or [making a donation](https://elliotsowersby.com/donate/) — and a ⭐ here is always appreciated.

</div>
