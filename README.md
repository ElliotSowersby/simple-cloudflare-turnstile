<div align="center">

# Simple CAPTCHA Alternative with Cloudflare Turnstile

Add Cloudflare Turnstile to WordPress, WooCommerce, Contact Forms & more. A user-friendly, privacy‑preserving reCAPTCHA alternative. 100% free.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/simple-cloudflare-turnstile?style=flat-square)](https://wordpress.org/plugins/simple-cloudflare-turnstile/)
[![WordPress Tested Up To](https://img.shields.io/wordpress/plugin/tested/simple-cloudflare-turnstile?style=flat-square)](https://wordpress.org/plugins/simple-cloudflare-turnstile/)
[![Active Installations](https://img.shields.io/wordpress/plugin/installs/simple-cloudflare-turnstile?style=flat-square)](https://wordpress.org/plugins/simple-cloudflare-turnstile/)
[![Rating](https://img.shields.io/wordpress/plugin/rating/simple-cloudflare-turnstile?style=flat-square)](https://wordpress.org/plugins/simple-cloudflare-turnstile/)
[![License: GPLv3](https://img.shields.io/badge/License-GPLv3-blue.svg?style=flat-square)](LICENSE)
[![Last Commit](https://img.shields.io/github/last-commit/ElliotSowersby/simple-cloudflare-turnstile?style=flat-square)](https://github.com/ElliotSowersby/simple-cloudflare-turnstile/commits/master)

</div>

> Cloudflare, the Cloudflare logo, and Cloudflare Workers are trademarks and/or registered trademarks of Cloudflare, Inc. in the United States and other jurisdictions. This project is not affiliated with, endorsed, or sponsored by Cloudflare, Inc.

---

## ✨ Overview

This plugin lets you easily place an **accessible, privacy‑friendly Cloudflare Turnstile challenge** on your WordPress login, registration, password reset, comments, WooCommerce flows, and many popular form builders—without the UX friction of traditional CAPTCHAs.

Turnstile helps block bots and spam while avoiding intrusive data harvesting. Everything here is **100% free**: no upsells, no telemetry, no hidden tracking.

---

## ✅ Key Features

- One-click enable for dozens of core & third‑party forms
- Supports the new Cloudflare Turnstile **Appearance Modes** (Always / Interaction Only)
- Choose **Theme**, **Language**, **Widget Size**
- **Disable Submit Button** until challenge is passed
- **Custom error & extra failure messages**
- **Whitelist**: Logged-in users, IP addresses, User Agents
- **Per‑integration toggles & widget placement** controls
- **Automatic script defer & smart loading** (only where needed)
- **Multisite compatible**
- **Developer friendly** (filters, constants, extensible structure)
- No invasive tracking – just works.

---

## 🧩 Supported Integrations

### Core & Platform
- WordPress: Login, Register, Password Reset, Comments
- WooCommerce: Checkout, Pay for Order, Login, Registration, Password Reset

### Form Builders
- WPForms
- Fluent Forms
- Contact Form 7
- Gravity Forms
- Formidable Forms
- Forminator Forms
- Jetpack Forms
- Kadence Forms
- Elementor Pro Forms

### Membership / Accounts
- MemberPress
- Ultimate Member
- WP-Members
- WP User Frontend
- WP User Manager
- Paid Memberships Pro

### Community / Discussion
- BuddyPress Registration
- bbPress Create Topic & Reply
- wpDiscuz Comments
- Blocksy theme login modal

### E‑Commerce & Digital
- Easy Digital Downloads
- CheckoutWC & Flux Checkout compatibility

### Email & Marketing
- Mailchimp for WordPress (MC4WP)
- MailPoet

### Other
- Clean Login plugin

> More may be added—feel free to open a feature request.

---

## 🚀 Quick Start

1. Install from WordPress.org or copy this repo into `wp-content/plugins/simple-cloudflare-turnstile`.
2. Activate the plugin in WordPress.
3. Go to: **Settings → Cloudflare Turnstile**.
4. In your Cloudflare dashboard generate a **Site Key** & **Secret Key** under Turnstile.
5. Paste the keys, save, then click **TEST API RESPONSE**.
6. Enable the forms you want protected.
7. Confirm the widget appears—done ✅

### Defining Keys via wp-config.php (Optional)
Add constants to lock keys (prevents changes in admin):

```php
// In wp-config.php
define( 'CF_TURNSTILE_SITE_KEY',   'your_site_key_here' );
define( 'CF_TURNSTILE_SECRET_KEY', 'your_secret_key_here' );
```

If defined, the settings UI will treat them as read-only.

---

## ⚙️ Configuration Highlights

| Setting | Purpose |
|---------|---------|
| Theme / Size | Match UI aesthetics (light, dark, auto; normal / compact) |
| Language | Force a specific locale or auto-detect |
| Appearance Mode | Only show on interaction (less visual noise) |
| Disable Submit Button | Blocks form submission until success token acquired |
| Custom / Extra Failure Messages | Improve clarity for end users |
| Whitelist (Users, IPs, User Agents) | Skip Turnstile for trusted actors |
| Payment Method Skips (WooCommerce) | Allow Express / Smart buttons without challenge |
| Widget Location (per integration) | Before / after buttons & custom placements |
| Debug Log | View submission events for troubleshooting |

---

## 🧪 Developer Notes

- Hooks & filters available (see inline code comments) such as:
  - `cfturnstile_widget_disable`
  - `cfturnstile_cf7_button_types`
- Unique widget IDs generated for multi-form pages & popups.
- Scripts auto‑defer and won't duplicate load across integrations.

Feel free to submit PRs that add safe, broadly useful extensibility.

---

## ❓ FAQ (Selected)

**Is it really free?**  
Yes. Both the plugin and Cloudflare Turnstile service are free.

**GDPR friendly?**  
Cloudflare states it does not harvest data for ad targeting and avoids persistent tracking cookies for Turnstile.

**401 console error?**  
Harmless—it’s a Private Access Token request for unsupported environments.

**Challenge not showing?**  
Make sure keys are correct and you completed the Test API Response step. Then clear caches / performance plugins.

**Security reports?**  
Disclose via the Patchstack VDP: https://patchstack.com/database/vdp/simple-cloudflare-turnstile

See the full FAQ in the original [`readme.txt`](./readme.txt).

---

## 🛡️ Security

We take security seriously. If you discover a vulnerability, please use the Patchstack link above instead of opening a public issue. PRs should reference responsible disclosure where applicable.

---

## 🤝 Contributing

1. Fork & create a feature branch
2. Make focused changes (one logical change per PR)
3. Follow WordPress coding standards (escaping, sanitization, i18n)
4. Update docs / inline comments where relevant
5. Open a PR describing motivation & testing steps

Bug reports & enhancement suggestions are welcome via GitHub Issues or the WordPress.org support forum.

---

## ❤️ Sponsors & Support

This plugin is maintained to give back to the WordPress community. Your support helps keep it actively improved.

### Ways to Support

- GitHub Sponsor
- One‑time donation via **PayPal**: https://www.paypal.com/donate/?hosted_button_id=RX28BBH7L5XDS
- Star this repository ⭐
- Leave a positive review on WordPress.org
- Contribute translations: https://translate.wordpress.org/projects/wp-plugins/simple-cloudflare-turnstile/

### Sponsor Recognition

<a href="#">RelyWP</a> - WordPress Maintenance Services and WooCommerce Plugins

---

## 🌍 Translations

Currently available in multiple languages thanks to volunteer contributors. Help translate:  
https://translate.wordpress.org/projects/wp-plugins/simple-cloudflare-turnstile/

---

## 📦 Changelog

For the complete changelog see [`readme.txt`](./readme.txt#L120) (WordPress.org format). GitHub tags may lag slightly behind the Stable Tag.

---

## 📄 License

Released under the **GNU General Public License v3.0 or later**. See [LICENSE](https://www.gnu.org/licenses/gpl-3.0.html).

---

## 🙌 Credits

Developed & maintained by [@ElliotSowersby](https://twitter.com/ElliotSowersby) (ElliotVS) with community contributions.

Thanks to every translator, tester, and user providing feedback.

---

## 🔗 Useful Links

- WordPress.org Plugin: https://wordpress.org/plugins/simple-cloudflare-turnstile/
- Cloudflare Turnstile: https://www.cloudflare.com/products/turnstile/
- Setup Guide: https://relywp.com/blog/how-to-add-cloudflare-turnstile-to-wordpress/
- Support Forum: https://wordpress.org/support/plugin/simple-cloudflare-turnstile/
- Security Disclosure: https://patchstack.com/database/vdp/simple-cloudflare-turnstile

---

If this saves you time or blocks spam today, consider starring the repo—it helps others discover it.
