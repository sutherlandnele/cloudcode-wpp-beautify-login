=== Cloudcode Beautify Login ===
Contributors: cloudcodepng
Tags: login, custom-login, login-page, security, branding
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide the default WordPress login URL and beautify the login page with custom CSS, button text, colors, and custom account links.

== Description ==

Cloudcode Beautify Login helps site administrators create a branded WordPress login experience.

The plugin provides three main features:

* Replace the public WordPress login URL with a custom login slug.
* Style the WordPress login screen with custom CSS and branding settings.
* Replace the Register and Lost your password links with custom frontend URLs.

This release is based on the stable working v1.0.2 logout-fix code path. Version 1.0.3 removes the old logo/background URL fields, keeps the Custom login CSS textarea, and adds Register and Lost your password URL fields.

The plugin does not rename WordPress core files and does not modify the `wp-login.php` file or the `wp-admin` directory.

= Features =

* Set a custom login URL slug from the WordPress admin dashboard.
* Redirect unauthenticated direct requests to `/wp-login.php`.
* Redirect unauthenticated direct requests to `/wp-admin`.
* Allow logged-in users to access `/wp-admin` normally.
* Keep `wp-admin/admin-ajax.php` available for frontend functionality.
* Add a custom Register link URL.
* Add a custom Lost your password link URL.
* Customize the login button text.
* Customize the primary brand color.
* Add a login page title and short login page message.
* Add complete custom CSS through a textarea.
* Includes emergency recovery instructions.

= Important Security Note =

Changing the login URL can reduce automated login attempts against common WordPress login paths. It is not a complete WordPress security solution.

For stronger protection, use this plugin together with strong passwords, two-factor authentication, regular WordPress updates, regular backups, and other security controls.

= What this plugin does not do =

* It does not rename WordPress core files.
* It does not remove `wp-login.php` from WordPress.
* It does not replace strong passwords or two-factor authentication.
* It does not provide firewall, malware scanning, or brute-force protection.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/cloudcode-beautify-login` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings > Cloudcode Beautify Login**.
4. Enter a custom login slug, for example `beautify-login`.
5. Optionally enter a custom Register link URL and Lost your password link URL.
6. Configure the login button text, primary brand color, title, message, and custom CSS.
7. Save the settings.
8. Open a private or incognito browser window.
9. Visit your new login URL, for example `/beautify-login/`.
10. Confirm that the login page loads at the new URL.

== Frequently Asked Questions ==

= Does this plugin rename wp-login.php or wp-admin? =

No. The plugin does not rename or modify WordPress core files. It uses WordPress rewrite rules and filters to route the custom login URL to the normal WordPress login process.

= Can logged-in administrators still use /wp-admin? =

Yes. Logged-in users can access `/wp-admin` normally.

= What happens when a visitor goes directly to /wp-login.php? =

If the visitor is not logged in, the plugin redirects the request to the configured redirect destination.

= What happens when a visitor goes directly to /wp-admin? =

If the visitor is not logged in, the plugin redirects the request to the configured redirect destination. Logged-in users can access `/wp-admin` normally.

= Does the plugin block admin-ajax.php? =

No. The plugin keeps `wp-admin/admin-ajax.php` available because many themes, WooCommerce features, and frontend plugins rely on it.

= How do I set the Register link to a custom frontend page? =

Go to **Settings > Cloudcode Beautify Login** and enter your URL in the **Register link URL** field.

Example:

`https://example.com/account/#register`

= How do I set the Lost your password link to a custom frontend page? =

Go to **Settings > Cloudcode Beautify Login** and enter your URL in the **Lost your password link URL** field.

Example:

`https://example.com/reset-password`

= How do I add a logo or background image? =

Use the **Custom login CSS** textarea. Paste CSS that references your logo or background image URL. Do not include `<style>` tags.

= What happens if I forget the custom login URL? =

Disable the plugin by renaming its folder using your hosting file manager, FTP, or SSH.

Rename:

`wp-content/plugins/cloudcode-beautify-login`

to:

`wp-content/plugins/cloudcode-beautify-login-disabled`

This disables the plugin and restores the normal WordPress login behavior.

You can also temporarily add this line to `wp-config.php`:

`define('CLOUDCODE_BEAUTIFY_LOGIN_DISABLE', true);`

= Is this plugin a complete WordPress security solution? =

No. This plugin changes the public login URL and redirects default login paths. Use it as one layer of security together with strong passwords, two-factor authentication, regular updates, backups, and other security controls.

== Screenshots ==

1. Sample branded login page with background image, dark overlay, logo, and custom login form.
2. Settings page with custom login slug, Register link URL, Lost your password link URL, branding fields, and custom CSS textarea.

== Changelog ==

= 1.0.3 =
* Based on the last working v1.0.2 logout-fix code path.
* Removed the logo image URL field from the settings page.
* Removed the background image URL field from the settings page.
* Added a custom Register link URL field.
* Added a custom Lost your password link URL field.
* Register and Lost your password links can now point to frontend account pages.
* Kept full Custom CSS support for logo and background styling.
* Kept the custom login URL behavior.
* Kept the logout fix.

= 1.0.2 =
* Fixed logout handling through the custom login URL.

= 1.0.1 =
* Improved custom login action handling.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.3 =
Adds custom Register and Lost your password URL fields while keeping the stable v1.0.2 login and logout behavior.
