<?php
/**
 * Plugin Name: Cloudcode Beautify Login
 * Plugin URI: https://www.cloudcode.com.pg/
 * Description: Hide the default WordPress login URL and beautify the WordPress login screen with a button text, colors, title, message, custom CSS, and configurable Register/Lost Password links. Automatically updates the .htaccess rewrite rule when the custom login slug changes.
 * Version: 1.0.4
 * Author: Cloudcode PNG Limited
 * Author URI: https://www.cloudcode.com.pg/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cloudcode-beautify-login
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Cloudcode_Beautify_Login {
    const VERSION = '1.0.4';

    const OPTION_LOGIN_SLUG      = 'cbuf_login_slug';
    const OPTION_REDIRECT_TO     = 'cbuf_redirect_to';
    const OPTION_REGISTER_URL    = 'cbuf_register_url';
    const OPTION_LOST_PASSWORD_URL = 'cbuf_lost_password_url';
    const OPTION_BUTTON_TEXT     = 'cbuf_button_text';
    const OPTION_PRIMARY_COLOR   = 'cbuf_primary_color';
    const OPTION_CARD_TITLE      = 'cbuf_card_title';
    const OPTION_CARD_MESSAGE    = 'cbuf_card_message';
    const OPTION_CUSTOM_CSS      = 'cbuf_custom_css';
    const OPTION_FLUSH_NEEDED    = 'cbuf_flush_needed';

    const DISABLE_CONSTANT = 'CLOUDCODE_BEAUTIFY_LOGIN_DISABLE';

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));

        if (defined(self::DISABLE_CONSTANT) && constant(self::DISABLE_CONSTANT)) {
            return;
        }

        add_action('init', array($this, 'add_login_rewrite_rule'), 1);
        add_action('init', array($this, 'maybe_flush_rewrite_rules'), 20);
        add_action('init', array($this, 'block_default_login_urls'), 1);

        add_filter('login_url', array($this, 'filter_login_url'), 10, 3);
        add_filter('logout_url', array($this, 'filter_logout_url'), 10, 2);
        add_filter('lostpassword_url', array($this, 'filter_lostpassword_url'), 10, 2);
        add_filter('register_url', array($this, 'filter_register_url'), 10, 1);
        add_filter('site_url', array($this, 'filter_site_url'), 10, 4);
        add_filter('network_site_url', array($this, 'filter_network_site_url'), 10, 3);

        add_action('login_enqueue_scripts', array($this, 'print_login_styles'));
        add_filter('login_headerurl', array($this, 'filter_login_header_url'));
        add_filter('login_headertext', array($this, 'filter_login_header_text'));
        add_filter('gettext', array($this, 'filter_login_button_text'), 20, 3);
    }

    public static function activate() {
        if (!get_option(self::OPTION_LOGIN_SLUG)) {
            update_option(self::OPTION_LOGIN_SLUG, 'beautify-login');
        }

        if (!get_option(self::OPTION_REDIRECT_TO)) {
            update_option(self::OPTION_REDIRECT_TO, 'home');
        }

        if (!get_option(self::OPTION_BUTTON_TEXT)) {
            update_option(self::OPTION_BUTTON_TEXT, 'Sign In');
        }

        if (!get_option(self::OPTION_PRIMARY_COLOR)) {
            update_option(self::OPTION_PRIMARY_COLOR, '#ff7555');
        }

        if (!get_option(self::OPTION_CARD_TITLE)) {
            update_option(self::OPTION_CARD_TITLE, 'Welcome Back');
        }

        if (!get_option(self::OPTION_CARD_MESSAGE)) {
            update_option(self::OPTION_CARD_MESSAGE, 'Sign in to continue to your account.');
        }

        self::static_add_login_rewrite_rule();
        self::static_update_htaccess_rule();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        self::static_remove_htaccess_rule();
        flush_rewrite_rules();
    }

    private static function static_get_login_slug() {
        $slug = get_option(self::OPTION_LOGIN_SLUG, 'beautify-login');
        $slug = sanitize_title((string) $slug);
        $slug = trim($slug, '/');

        return $slug !== '' ? $slug : 'beautify-login';
    }

    private static function static_add_login_rewrite_rule() {
        $slug = self::static_get_login_slug();
        add_rewrite_rule('^' . preg_quote($slug, '#') . '/?$', 'wp-login.php', 'top');
    }

    public function add_login_rewrite_rule() {
        self::static_add_login_rewrite_rule();
    }

    private static function static_get_htaccess_path() {
        if (!function_exists('get_home_path')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $home_path = get_home_path();

        if (empty($home_path)) {
            $home_path = ABSPATH;
        }

        return trailingslashit($home_path) . '.htaccess';
    }

    private static function static_get_htaccess_block() {
        $slug = self::static_get_login_slug();

        $lines = array(
            '# BEGIN Cloudcode Beautify Login',
            '<IfModule mod_rewrite.c>',
            'RewriteEngine On',
            'RewriteRule ^' . preg_quote($slug, '/') . '/?$ wp-login.php [QSA,L]',
            '</IfModule>',
            '# END Cloudcode Beautify Login',
        );

        return implode(PHP_EOL, $lines) . PHP_EOL . PHP_EOL;
    }

    private static function static_remove_htaccess_block_from_content($content) {
        return preg_replace(
            '/# BEGIN Cloudcode Beautify Login\s*.*?# END Cloudcode Beautify Login\s*/s',
            '',
            (string) $content
        );
    }

    public static function static_update_htaccess_rule() {
        $htaccess_file = self::static_get_htaccess_path();

        if (!file_exists($htaccess_file)) {
            @touch($htaccess_file);
        }

        if (!is_writable($htaccess_file)) {
            return false;
        }

        $content = file_get_contents($htaccess_file);

        if ($content === false) {
            $content = '';
        }

        $content = self::static_remove_htaccess_block_from_content($content);
        $block   = self::static_get_htaccess_block();

        /*
         * Put the custom login rule BEFORE the normal WordPress rewrite block.
         * If it is placed after WordPress' catch-all index.php rule, Apache may
         * never reach it and the custom login URL can show a 404 page.
         */
        if (strpos($content, '# BEGIN WordPress') !== false) {
            $content = preg_replace('/# BEGIN WordPress/', $block . '# BEGIN WordPress', $content, 1);
        } else {
            $content = $block . $content;
        }

        return file_put_contents($htaccess_file, $content, LOCK_EX) !== false;
    }

    public static function static_remove_htaccess_rule() {
        $htaccess_file = self::static_get_htaccess_path();

        if (!file_exists($htaccess_file) || !is_writable($htaccess_file)) {
            return false;
        }

        $content = file_get_contents($htaccess_file);

        if ($content === false) {
            return false;
        }

        $content = self::static_remove_htaccess_block_from_content($content);

        return file_put_contents($htaccess_file, $content, LOCK_EX) !== false;
    }

    public function update_htaccess_after_slug_change($old_value, $value, $option) {
        self::static_add_login_rewrite_rule();
        self::static_update_htaccess_rule();
        flush_rewrite_rules(false);
    }

    public function maybe_flush_rewrite_rules() {
        if (get_option(self::OPTION_FLUSH_NEEDED) === 'yes') {
            self::static_add_login_rewrite_rule();
            self::static_update_htaccess_rule();
            flush_rewrite_rules(false);
            delete_option(self::OPTION_FLUSH_NEEDED);
        }
    }

    public function register_settings() {
        register_setting('cbuf_settings', self::OPTION_LOGIN_SLUG, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_login_slug'),
            'default' => 'beautify-login',
        ));

        register_setting('cbuf_settings', self::OPTION_REDIRECT_TO, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_redirect_to'),
            'default' => 'home',
        ));

        register_setting('cbuf_settings', self::OPTION_REGISTER_URL, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_optional_url'),
            'default' => '',
        ));

        register_setting('cbuf_settings', self::OPTION_LOST_PASSWORD_URL, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_optional_url'),
            'default' => '',
        ));

        register_setting('cbuf_settings', self::OPTION_BUTTON_TEXT, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_short_text'),
            'default' => 'Sign In',
        ));

        register_setting('cbuf_settings', self::OPTION_PRIMARY_COLOR, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_color'),
            'default' => '#ff7555',
        ));

        register_setting('cbuf_settings', self::OPTION_CARD_TITLE, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_short_text'),
            'default' => 'Welcome Back',
        ));

        register_setting('cbuf_settings', self::OPTION_CARD_MESSAGE, array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Sign in to continue to your account.',
        ));

        register_setting('cbuf_settings', self::OPTION_CUSTOM_CSS, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_custom_css'),
            'default' => '',
        ));

        add_action('update_option_' . self::OPTION_LOGIN_SLUG, array($this, 'mark_rewrite_flush_needed'), 10, 3);
        add_action('update_option_' . self::OPTION_LOGIN_SLUG, array($this, 'update_htaccess_after_slug_change'), 20, 3);
    }

    public function add_plugin_action_links($links) {
        $settings_url = admin_url('options-general.php?page=cloudcode-beautify-login');
        $settings_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'cloudcode-beautify-login') . '</a>';
        array_unshift($links, $settings_link);

        return $links;
    }

    public function mark_rewrite_flush_needed($old_value, $value, $option) {
        update_option(self::OPTION_FLUSH_NEEDED, 'yes');
    }

    public function sanitize_short_text($value) {
        $value = sanitize_text_field((string) $value);
        return mb_substr($value, 0, 120);
    }

    public function sanitize_custom_css($value) {
        $value = (string) $value;
        $value = wp_strip_all_tags($value);
        $value = str_replace(array('<?php', '<?', '?>'), '', $value);
        return trim($value);
    }

    public function sanitize_color($value) {
        $value = sanitize_hex_color((string) $value);
        return $value ? $value : '#ff7555';
    }

    public function sanitize_optional_url($value) {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return esc_url_raw($value);
    }

    public function sanitize_login_slug($value) {
        $value = sanitize_title((string) $value);
        $value = trim($value, '/');

        $reserved = array(
            '', 'wp-admin', 'wp-login', 'wp-login-php', 'wp-content', 'wp-includes',
            'wp-json', 'xmlrpc', 'xmlrpc-php', 'admin', 'login', 'dashboard',
            'feed', 'comments', 'robots', 'favicon',
        );

        if (in_array($value, $reserved, true)) {
            add_settings_error(
                self::OPTION_LOGIN_SLUG,
                'invalid_login_slug',
                esc_html__('That login slug is reserved. Please choose something unique such as beautify-login, staff-entry, or site-console.', 'cloudcode-beautify-login'),
                'error'
            );

            return $this->get_login_slug();
        }

        return $value;
    }

    public function sanitize_redirect_to($value) {
        $value = sanitize_text_field((string) $value);
        $value = trim($value);

        return $value === '' ? 'home' : $value;
    }

    public function get_login_slug() {
        return self::static_get_login_slug();
    }

    public function get_login_url($args = array(), $scheme = 'login') {
        $url = home_url('/' . $this->get_login_slug() . '/', $scheme);

        if (!empty($args) && is_array($args)) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }

    public function get_blocked_redirect_url() {
        $redirect_to = get_option(self::OPTION_REDIRECT_TO, 'home');
        $redirect_to = trim((string) $redirect_to);

        if ($redirect_to === '' || $redirect_to === 'home') {
            return home_url('/');
        }

        if ($redirect_to === '404') {
            return home_url('/404');
        }

        if (filter_var($redirect_to, FILTER_VALIDATE_URL)) {
            return esc_url_raw($redirect_to);
        }

        $redirect_to = trim($redirect_to, '/');

        return home_url('/' . $redirect_to . '/');
    }

    private function current_relative_path() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = parse_url($request_uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';
        $path = trim($path, '/');

        $home_path = parse_url(home_url('/'), PHP_URL_PATH);
        $home_path = is_string($home_path) ? trim($home_path, '/') : '';

        if ($home_path !== '' && $path === $home_path) {
            $path = '';
        } elseif ($home_path !== '' && strpos($path, $home_path . '/') === 0) {
            $path = substr($path, strlen($home_path) + 1);
        }

        return trim($path, '/');
    }

    private function is_custom_login_request() {
        return $this->current_relative_path() === $this->get_login_slug();
    }


    public function load_custom_login_page() {
        if (!$this->is_custom_login_request()) {
            return;
        }

        $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash($_REQUEST['action'])) : 'login';

        /*
         * Handle logout directly instead of relying on a frontend rewrite.
         * This avoids blank screens on some hosts/themes when wp-login.php is loaded
         * through a custom URL with action=logout.
         */
        if ($action === 'logout') {
            $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';

            if (!wp_verify_nonce($nonce, 'log-out')) {
                wp_nonce_ays('log-out');
                exit;
            }

            wp_logout();

            $redirect_to = isset($_REQUEST['redirect_to'])
                ? esc_url_raw(wp_unslash($_REQUEST['redirect_to']))
                : home_url('/');

            wp_safe_redirect($redirect_to);
            exit;
        }

        /*
         * For login, lost password, reset password, registration, and related
         * WordPress login actions, let the normal WordPress login controller run.
         */
        global $pagenow;
        $pagenow = 'wp-login.php';

        $_SERVER['SCRIPT_NAME'] = '/wp-login.php';
        $_SERVER['PHP_SELF'] = '/wp-login.php';

        require_once ABSPATH . 'wp-login.php';
        exit;
    }

    public function block_default_login_urls() {
        if (is_user_logged_in()) {
            return;
        }

        $path = $this->current_relative_path();

        if ($path === 'wp-admin/admin-ajax.php') {
            return;
        }

        if ($path === 'wp-login.php') {
            wp_safe_redirect($this->get_blocked_redirect_url(), 302);
            exit;
        }

        if ($path === 'wp-admin' || strpos($path, 'wp-admin/') === 0) {
            wp_safe_redirect($this->get_blocked_redirect_url(), 302);
            exit;
        }
    }

    public function filter_login_url($login_url, $redirect, $force_reauth) {
        $args = array();

        if (!empty($redirect)) {
            $args['redirect_to'] = $redirect;
        }

        if ($force_reauth) {
            $args['reauth'] = '1';
        }

        return $this->get_login_url($args, 'login');
    }

    public function filter_logout_url($logout_url, $redirect) {
        $args = array('action' => 'logout');

        /*
         * Give logout a safe final destination. Without this, some installations
         * can appear to remain on the custom login endpoint after the user is
         * logged out.
         */
        $args['redirect_to'] = !empty($redirect) ? $redirect : home_url('/');

        return wp_nonce_url($this->get_login_url($args, 'login'), 'log-out');
    }

    public function filter_lostpassword_url($lostpassword_url, $redirect) {
        $custom_url = get_option(self::OPTION_LOST_PASSWORD_URL, '');

        if (!empty($custom_url)) {
            return esc_url($custom_url);
        }

        $args = array('action' => 'lostpassword');

        if (!empty($redirect)) {
            $args['redirect_to'] = $redirect;
        }

        return $this->get_login_url($args, 'login');
    }

    public function filter_register_url($register_url) {
        $custom_url = get_option(self::OPTION_REGISTER_URL, '');

        if (!empty($custom_url)) {
            return esc_url($custom_url);
        }

        return $this->get_login_url(array('action' => 'register'), 'login');
    }

    public function filter_site_url($url, $path, $scheme, $blog_id) {
        if (!is_string($path) || strpos($path, 'wp-login.php') === false) {
            return $url;
        }

        $parts = parse_url($url);
        $query = !empty($parts['query']) ? '?' . $parts['query'] : '';

        return home_url('/' . $this->get_login_slug() . '/' . $query, $scheme);
    }

    public function filter_network_site_url($url, $path, $scheme) {
        return $this->filter_site_url($url, $path, $scheme, null);
    }

    public function filter_login_header_url() {
        return home_url('/');
    }

    public function filter_login_header_text() {
        return get_bloginfo('name');
    }

    public function filter_login_button_text($translated_text, $text, $domain) {
        if ($text === 'Log In') {
            $button_text = get_option(self::OPTION_BUTTON_TEXT, 'Sign In');
            $button_text = $this->sanitize_short_text($button_text);

            return $button_text !== '' ? $button_text : $translated_text;
        }

        return $translated_text;
    }

    public function print_login_styles() {
        $primary_color  = esc_attr($this->sanitize_color(get_option(self::OPTION_PRIMARY_COLOR, '#ff7555')));
        $card_title     = $this->sanitize_short_text(get_option(self::OPTION_CARD_TITLE, 'Welcome Back'));
        $card_message   = sanitize_text_field(get_option(self::OPTION_CARD_MESSAGE, 'Sign in to continue to your account.'));
        $custom_css     = $this->sanitize_custom_css(get_option(self::OPTION_CUSTOM_CSS, ''));

        $has_logo = $logo_url !== '';
        ?>
        <style type="text/css" id="cloudcode-beautify-login-css">
            :root {
                --cbuf-primary: <?php echo $primary_color; ?>;
                --cbuf-primary-dark: <?php echo esc_attr($this->darken_hex_color($primary_color, 22)); ?>;
            }

            body.login {
                min-height: 100vh;
                background-color: #f6f7fb;
                background: linear-gradient(135deg, #fff7f3 0%, #ffffff 45%, #ffe8df 100%);
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            }

            body.login::before {
                content: "";
                position: fixed;
                inset: 0;
                background:
                    radial-gradient(circle at top left, rgba(255,255,255,0.88), rgba(255,255,255,0) 38%),
                    radial-gradient(circle at bottom right, rgba(0,0,0,0.12), rgba(0,0,0,0) 42%);
                pointer-events: none;
            }

            #login {
                position: relative;
                width: 420px;
                max-width: calc(100% - 32px);
                padding: 6vh 0 0;
                z-index: 1;
            }

            .login h1 {
                margin-bottom: 14px;
            }

            .login h1 a {
                background-image: none;
                text-indent: 0;
                width: auto;
                height: auto;
                color: #111827;
                font-size: 28px;
                font-weight: 800;
                line-height: 1.2;
                text-decoration: none;
                margin: 0 auto 18px;
            }

            .login h1 a::before {
                content: "<?php echo esc_js(get_bloginfo('name')); ?>";
            }

            .login h1::after {
                content: "<?php echo esc_js($card_title); ?>";
                display: block;
                color: #111827;
                font-size: 24px;
                font-weight: 800;
                line-height: 1.25;
                margin: 8px auto 0;
                text-align: center;
            }

            #loginform::before,
            #lostpasswordform::before,
            #registerform::before {
                content: "<?php echo esc_js($card_message); ?>";
                display: block;
                color: #4b5563;
                font-size: 14px;
                line-height: 1.5;
                margin: -4px 0 22px;
                text-align: center;
            }

            .login form {
                background: rgba(255,255,255,0.96);
                border: 1px solid rgba(229,231,235,0.85);
                border-radius: 22px;
                box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
                padding: 34px 34px 30px;
                backdrop-filter: blur(12px);
            }

            .login label {
                color: #1f2937;
                font-size: 14px;
                font-weight: 700;
            }

            .login form .input,
            .login input[type="text"],
            .login input[type="password"],
            .login input[type="email"] {
                border: 1px solid #d1d5db;
                border-radius: 12px;
                box-shadow: none;
                color: #111827;
                font-size: 16px;
                min-height: 46px;
                padding: 9px 12px;
                background: #f9fafb;
            }

            .login form .input:focus,
            .login input[type="text"]:focus,
            .login input[type="password"]:focus,
            .login input[type="email"]:focus {
                border-color: var(--cbuf-primary);
                box-shadow: 0 0 0 3px rgba(255, 117, 85, 0.18);
                outline: none;
            }

            .wp-core-ui .button-primary {
                background: var(--cbuf-primary);
                border-color: var(--cbuf-primary);
                border-radius: 12px;
                box-shadow: 0 10px 22px rgba(15, 23, 42, 0.16);
                color: #ffffff;
                font-size: 14px;
                font-weight: 800;
                letter-spacing: 0.02em;
                min-height: 44px;
                padding: 0 22px;
                text-transform: uppercase;
                text-shadow: none;
            }

            .wp-core-ui .button-primary:hover,
            .wp-core-ui .button-primary:focus {
                background: var(--cbuf-primary-dark);
                border-color: var(--cbuf-primary-dark);
                color: #ffffff;
            }

            .login #nav,
            .login #backtoblog,
            .login .privacy-policy-page-link {
                text-align: center;
            }

            .login #nav a,
            .login #backtoblog a,
            .login .privacy-policy-page-link a {
                color: #111827;
                font-weight: 700;
                text-decoration: none;
            }

            .login #nav a:hover,
            .login #backtoblog a:hover,
            .login .privacy-policy-page-link a:hover {
                color: var(--cbuf-primary);
                text-decoration: underline;
            }

            .login .message,
            .login .notice,
            .login .success {
                border-left-color: var(--cbuf-primary);
                border-radius: 10px;
            }

            .login #login_error {
                border-left-color: #dc2626;
                border-radius: 10px;
            }

            .login .language-switcher {
                display: none;
            }

            @media screen and (max-width: 480px) {
                #login { padding-top: 28px; }
                .login form { padding: 26px 22px 24px; }
            }

            <?php if ($custom_css !== '') : ?>
            /* Cloudcode Beautify Login custom CSS from settings. */
            <?php echo "\n" . $custom_css . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is stored by administrators and stripped of HTML/PHP tags on save. ?>
            <?php endif; ?>
        </style>
        <?php
    }

    private function darken_hex_color($hex, $percent) {
        $hex = sanitize_hex_color($hex);

        if (!$hex) {
            return '#e35f43';
        }

        $hex = ltrim($hex, '#');
        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));

        $factor = max(0, min(100, 100 - absint($percent))) / 100;

        $red = max(0, min(255, (int) round($red * $factor)));
        $green = max(0, min(255, (int) round($green * $factor)));
        $blue = max(0, min(255, (int) round($blue * $factor)));

        return sprintf('#%02x%02x%02x', $red, $green, $blue);
    }

    public function add_settings_page() {
        add_options_page(
            esc_html__('Cloudcode Beautify Login', 'cloudcode-beautify-login'),
            esc_html__('Cloudcode Beautify Login', 'cloudcode-beautify-login'),
            'manage_options',
            'cloudcode-beautify-login',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $login_url = esc_url($this->get_login_url());
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Cloudcode Beautify Login', 'cloudcode-beautify-login'); ?></h1>

            <p><?php echo esc_html__('Hide the default WordPress login URL and beautify the WordPress login screen with your own button text, colors, custom links, and optional custom CSS.', 'cloudcode-beautify-login'); ?></p>

            <div style="background:#fff;border-left:4px solid #2271b1;padding:14px 18px;margin:18px 0;max-width:900px;">
                <strong><?php echo esc_html__('Current login URL:', 'cloudcode-beautify-login'); ?></strong><br>
                <a href="<?php echo $login_url; ?>" target="_blank" rel="noopener"><?php echo $login_url; ?></a>
            </div>

            <div style="background:#fff;border-left:4px solid #46b450;padding:12px 18px;margin:18px 0;max-width:900px;">
                <strong><?php echo esc_html__('Rewrite rule:', 'cloudcode-beautify-login'); ?></strong>
                <?php echo esc_html__('When you save a new login slug, this plugin updates the Cloudcode Beautify Login block in your .htaccess file automatically, before the normal WordPress rewrite block.', 'cloudcode-beautify-login'); ?>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('cbuf_settings'); ?>

                <h2><?php echo esc_html__('Login URL Settings', 'cloudcode-beautify-login'); ?></h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_LOGIN_SLUG); ?>"><?php echo esc_html__('New login URL slug', 'cloudcode-beautify-login'); ?></label></th>
                        <td>
                            <code><?php echo esc_html(home_url('/')); ?></code>
                            <input name="<?php echo esc_attr(self::OPTION_LOGIN_SLUG); ?>" id="<?php echo esc_attr(self::OPTION_LOGIN_SLUG); ?>" type="text" value="<?php echo esc_attr($this->get_login_slug()); ?>" class="regular-text" placeholder="beautify-login" />
                            <p class="description"><?php echo esc_html__('Example: enter beautify-login to use yourdomain.com/beautify-login/. Avoid reserved slugs such as admin, login, wp-admin, or wp-login.', 'cloudcode-beautify-login'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_REDIRECT_TO); ?>"><?php echo esc_html__('Redirect blocked login attempts to', 'cloudcode-beautify-login'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_REDIRECT_TO); ?>" id="<?php echo esc_attr(self::OPTION_REDIRECT_TO); ?>" type="text" value="<?php echo esc_attr(get_option(self::OPTION_REDIRECT_TO, 'home')); ?>" class="regular-text" placeholder="home" />
                            <p class="description"><?php echo esc_html__('Use home for the homepage, 404 for a not-found style URL, or enter a page slug such as not-found.', 'cloudcode-beautify-login'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Login Page Links', 'cloudcode-beautify-login'); ?></h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_REGISTER_URL); ?>"><?php echo esc_html__('Register link URL', 'cloudcode-beautify-login'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_REGISTER_URL); ?>" id="<?php echo esc_attr(self::OPTION_REGISTER_URL); ?>" type="url" value="<?php echo esc_url(get_option(self::OPTION_REGISTER_URL, '')); ?>" class="large-text" placeholder="https://example.com/account/#rg" />
                            <p class="description"><?php echo esc_html__('Optional. When set, the Register link on the WordPress login screen will go to this URL. Leave blank to use the default WordPress registration action on your custom login URL.', 'cloudcode-beautify-login'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_LOST_PASSWORD_URL); ?>"><?php echo esc_html__('Lost your password link URL', 'cloudcode-beautify-login'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_LOST_PASSWORD_URL); ?>" id="<?php echo esc_attr(self::OPTION_LOST_PASSWORD_URL); ?>" type="url" value="<?php echo esc_url(get_option(self::OPTION_LOST_PASSWORD_URL, '')); ?>" class="large-text" placeholder="https://example.com/reset-password" />
                            <p class="description"><?php echo esc_html__('Optional. When set, the Lost your password link on the WordPress login screen will go to this URL. Leave blank to use the default WordPress lost password action on your custom login URL.', 'cloudcode-beautify-login'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Login Page Branding', 'cloudcode-beautify-login'); ?></h2>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_BUTTON_TEXT); ?>"><?php echo esc_html__('Login button text', 'cloudcode-beautify-login'); ?></label></th>
                        <td><input name="<?php echo esc_attr(self::OPTION_BUTTON_TEXT); ?>" id="<?php echo esc_attr(self::OPTION_BUTTON_TEXT); ?>" type="text" value="<?php echo esc_attr(get_option(self::OPTION_BUTTON_TEXT, 'Sign In')); ?>" class="regular-text" placeholder="Sign In" /></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_PRIMARY_COLOR); ?>"><?php echo esc_html__('Primary brand color', 'cloudcode-beautify-login'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_PRIMARY_COLOR); ?>" id="<?php echo esc_attr(self::OPTION_PRIMARY_COLOR); ?>" type="text" value="<?php echo esc_attr(get_option(self::OPTION_PRIMARY_COLOR, '#ff7555')); ?>" class="regular-text" placeholder="#ff7555" />
                            <p class="description"><?php echo esc_html__('Hex color used for the login button and highlights. Example: #ff7555.', 'cloudcode-beautify-login'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_CARD_TITLE); ?>"><?php echo esc_html__('Login page title', 'cloudcode-beautify-login'); ?></label></th>
                        <td><input name="<?php echo esc_attr(self::OPTION_CARD_TITLE); ?>" id="<?php echo esc_attr(self::OPTION_CARD_TITLE); ?>" type="text" value="<?php echo esc_attr(get_option(self::OPTION_CARD_TITLE, 'Welcome Back')); ?>" class="regular-text" placeholder="Welcome Back" /></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_CARD_MESSAGE); ?>"><?php echo esc_html__('Login page message', 'cloudcode-beautify-login'); ?></label></th>
                        <td><input name="<?php echo esc_attr(self::OPTION_CARD_MESSAGE); ?>" id="<?php echo esc_attr(self::OPTION_CARD_MESSAGE); ?>" type="text" value="<?php echo esc_attr(get_option(self::OPTION_CARD_MESSAGE, 'Sign in to continue to your account.')); ?>" class="large-text" placeholder="Sign in to continue to your account." /></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr(self::OPTION_CUSTOM_CSS); ?>"><?php echo esc_html__('Custom login CSS', 'cloudcode-beautify-login'); ?></label></th>
                        <td>
                            <textarea name="<?php echo esc_attr(self::OPTION_CUSTOM_CSS); ?>" id="<?php echo esc_attr(self::OPTION_CUSTOM_CSS); ?>" rows="18" class="large-text code" placeholder="body.login { background: #f8fafc; }&#10;.login form { border-radius: 20px; }&#10;.wp-core-ui .button-primary { text-transform: none; }"><?php echo esc_textarea(get_option(self::OPTION_CUSTOM_CSS, '')); ?></textarea>
                            <p class="description"><?php echo esc_html__('Optional. Paste complete CSS here to override the default login design. Do not include opening or closing style tags.', 'cloudcode-beautify-login'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(esc_html__('Save Beautify Login Settings', 'cloudcode-beautify-login')); ?>
            </form>

            <hr>

            <h2><?php echo esc_html__('Testing checklist', 'cloudcode-beautify-login'); ?></h2>
            <ol>
                <li><?php echo esc_html__('Open the new login URL in a private/incognito browser.', 'cloudcode-beautify-login'); ?></li>
                <li><?php echo esc_html__('Confirm that /wp-login.php redirects away.', 'cloudcode-beautify-login'); ?></li>
                <li><?php echo esc_html__('Confirm that unauthenticated /wp-admin redirects away.', 'cloudcode-beautify-login'); ?></li>
                <li><?php echo esc_html__('Log in using the new URL and confirm that /wp-admin works after login.', 'cloudcode-beautify-login'); ?></li>
            </ol>

            <h2><?php echo esc_html__('Emergency recovery', 'cloudcode-beautify-login'); ?></h2>
            <p><?php echo esc_html__('If you lock yourself out, rename this folder from your hosting File Manager, FTP, or SSH:', 'cloudcode-beautify-login'); ?></p>
            <p><code>wp-content/plugins/cloudcode-beautify-login</code></p>
            <p><?php echo esc_html__('Or temporarily add this line to wp-config.php:', 'cloudcode-beautify-login'); ?></p>
            <p><code>define('CLOUDCODE_BEAUTIFY_LOGIN_DISABLE', true);</code></p>
        </div>
        <?php
    }
}

register_activation_hook(__FILE__, array('Cloudcode_Beautify_Login', 'activate'));
register_deactivation_hook(__FILE__, array('Cloudcode_Beautify_Login', 'deactivate'));

Cloudcode_Beautify_Login::instance();
