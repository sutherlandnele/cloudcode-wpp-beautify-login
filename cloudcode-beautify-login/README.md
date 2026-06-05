# Cloudcode Beautify Login

## Important v1.0.4 Fix

This release fixes the issue where changing the custom login slug from `beautify-login` to another value, such as `naik-login`, could show a 404 page.

The plugin now automatically updates the Cloudcode Beautify Login block in `.htaccess` when the login slug changes.

The generated block looks like this:

```apache
# BEGIN Cloudcode Beautify Login
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^naik-login/?$ wp-login.php [QSA,L]
</IfModule>
# END Cloudcode Beautify Login
```

The block is written **before** the normal WordPress rewrite block so Apache reaches the custom login rule before WordPress' catch-all `index.php` rule.


Cloudcode Beautify Login hides the default WordPress login URL and beautifies the WordPress login page using custom text, colors, links, and CSS.

## Important Note for v1.0.3

This release is built from the **last working v1.0.2 logout-fix plugin**.

It keeps the working login routing and logout behavior from v1.0.2, then only adds the requested changes:

- Removes the old **Logo image URL** field.
- Removes the old **Background image URL** field.
- Adds a **Register link URL** field.
- Adds a **Lost your password link URL** field.
- Keeps the full **Custom login CSS** textarea so logo/background styling can still be done through CSS.

## Features

- Custom login URL slug.
- Blocks unauthenticated access to `/wp-login.php`.
- Blocks unauthenticated access to `/wp-admin`.
- Allows logged-in users to use `/wp-admin`.
- Custom Register link URL.
- Custom Lost your password link URL.
- Custom login button text.
- Primary brand color.
- Login page title and message.
- Full Custom CSS textarea.

## Kumul Pride Example Settings

```text
New login URL slug: baka-go
Redirect blocked login attempts to: 404
Register link URL: https://kumulpride.com/account/#rg
Lost your password link URL: https://kumulpride.com/reset-password
Login button text: Sign In
Primary brand color: #ff7555
Login page title: Welcome to Kumul Pride
Login page message: Sign in to manage your Kumul Pride account.
```

## Installation

1. Upload and activate the plugin.
2. Go to **Settings → Cloudcode Beautify Login**.
3. Configure the settings.
4. Save changes.
5. Test the custom login URL in an incognito/private browser.

## Testing Checklist

Test these URLs:

```text
https://example.com/your-login-slug/
https://example.com/wp-login.php
https://example.com/wp-admin
```

The custom login URL should load the login page.

The default login URL and unauthenticated `/wp-admin` should redirect away.

After login, `/wp-admin` should work normally.

## Emergency Recovery

Rename this folder:

```text
wp-content/plugins/cloudcode-beautify-login
```

to:

```text
wp-content/plugins/cloudcode-beautify-login-disabled
```

Then log in normally through `/wp-login.php`.

## License

GPLv2 or later.


## Changelog

### 1.0.4

- Automatically updates the `.htaccess` rewrite rule when the custom login slug changes.
- Places the Cloudcode Beautify Login rewrite block before the normal WordPress rewrite block.
- Removes old Cloudcode Beautify Login rewrite blocks before writing the new one.
- Keeps the working v1.0.3 login, logout, Register URL, Lost Password URL, and Custom CSS behavior.
