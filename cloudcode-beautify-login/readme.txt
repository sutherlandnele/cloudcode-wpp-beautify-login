=== Cloudcode Beautify Login ===
Contributors: cloudcodepng
Tags: login, custom-login, login-page, security, branding
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hide the default WordPress login URL and beautify the login page with custom CSS, button text, colors, and custom account links.

== Description ==

Cloudcode Beautify Login helps site administrators create a branded WordPress login experience.

The plugin provides three main features:

* Replace the public WordPress login URL with a custom login slug.
* Style the WordPress login screen with custom CSS and branding settings.
* Replace the Register and Lost your password links with custom frontend URLs.

This version is based on the last working v1.0.2 logout-fix code path. It keeps the working login routing and logout behavior, removes the old logo/background URL fields, and adds Register and Lost your password URL fields.

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
* Includes simple emergency recovery instructions.

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

== Frequently Asked Questions ==

= Does this plugin rename wp-login.php or wp-admin? =

No. The plugin does not rename or modify WordPress core files.

= Can logged-in administrators still use /wp-admin? =

Yes. Logged-in users can access `/wp-admin` normally.

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

== Changelog ==

= 1.0.4 =
* Automatically updates the Cloudcode Beautify Login .htaccess rewrite block when the custom login slug changes.
* Places the custom login rewrite rule before the normal WordPress rewrite block so Apache reaches it before WordPress' catch-all rule.
* Removes any old Cloudcode Beautify Login .htaccess block before writing the new slug.
* Keeps the working v1.0.3 login, logout, Register URL, Lost Password URL, and Custom CSS behavior.


= 1.0.3 =
* Built from the last working v1.0.2 logout-fix code.
* Removed the logo image URL field from the settings page.
* Removed the background image URL field from the settings page.
* Added a custom Register link URL field.
* Added a custom Lost your password link URL field.
* Register and Lost your password links can now point to frontend account pages.
* Kept the working custom login routing from v1.0.2.
* Kept the logout fix from v1.0.2.
* Kept full Custom CSS support for logo and background styling.

= 1.0.2 =
* Fixed logout handling through the custom login URL.

= 1.0.1 =
* Improved custom login action handling.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.4 =
Recommended update. Fixes custom login slug changes by updating the .htaccess rewrite rule automatically.


= 1.0.3 =
Adds custom Register and Lost your password URL fields while keeping the last working v1.0.2 login and logout behavior.
