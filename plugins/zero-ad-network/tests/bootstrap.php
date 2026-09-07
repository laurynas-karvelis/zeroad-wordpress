<?php

/**
 * Test bootstrap: enough of WordPress to exercise the plugin's own logic in isolation, without a full
 * WordPress install. Real WP integration is out of scope here - these tests cover the code we wrote
 * (token verification wiring, settings validation, cache signalling, HTML cleaning), not WordPress.
 *
 * WP functions are stubbed in the global namespace, so the plugin's unqualified calls fall through to
 * them. A few PHP built-ins the plugin calls (setcookie/header/headers_sent) are shadowed inside the
 * ZeroAd\WP namespace instead, so tests can capture what would have been sent.
 */

declare(strict_types=1);

// --- Capturable shadows of PHP built-ins, scoped to the plugin's namespace --------------------------
namespace ZeroAd\WP {
    function headers_sent()
    {
        return false;
    }

    function header(string $header, bool $replace = true): void
    {
        $GLOBALS["__sent_headers"][] = ["header" => $header, "replace" => $replace];
    }

    function setcookie(string $name, string $value = "", $expires = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false): bool
    {
        $GLOBALS["__set_cookies"][] = compact("name", "value", "expires", "path", "domain", "secure", "httponly");
        return true;
    }
}

// --- WordPress function stubs + autoloading ---------------------------------------------------------
namespace {
    if (!defined("ABSPATH")) {
        define("ABSPATH", sys_get_temp_dir() . "/");
    }
    if (!defined("ZEROAD_DEFAULT_CACHE_TTL")) {
        define("ZEROAD_DEFAULT_CACHE_TTL", 10);
    }
    if (!defined("ZEROAD_PLUGIN_URL")) {
        define("ZEROAD_PLUGIN_URL", "https://example.com/wp-content/plugins/zero-ad-network/");
    }
    if (!defined("ZEROAD_VERSION")) {
        define("ZEROAD_VERSION", "test");
    }
    if (!defined("COOKIEPATH")) {
        define("COOKIEPATH", "/");
    }
    if (!defined("COOKIE_DOMAIN")) {
        define("COOKIE_DOMAIN", "");
    }

    // Reset all captured side effects; call from a test's setUp().
    function zeroad_test_reset(): void
    {
        $GLOBALS["__sent_headers"] = [];
        $GLOBALS["__set_cookies"] = [];
        $GLOBALS["__wp_hooks"] = [];
        $GLOBALS["__settings_errors"] = [];
        $_SERVER = array_diff_key($_SERVER, array_flip(["HTTP_BETTER_WEB_TOKEN", "HTTP_HOST", "HTTP_ACCEPT", "HTTP_CONTENT_TYPE"]));
        unset($GLOBALS["zeroad_token_context"]);
        $_COOKIE = [];
    }
    zeroad_test_reset();

    function add_action($hook, $cb, $priority = 10, $args = 1)
    {
        $GLOBALS["__wp_hooks"][$hook][] = $cb;
        return true;
    }
    function add_filter($hook, $cb, $priority = 10, $args = 1)
    {
        $GLOBALS["__wp_hooks"][$hook][] = $cb;
        return true;
    }
    function remove_filter($hook, $cb, $priority = 10)
    {
        return true;
    }
    function remove_action($hook, $cb, $priority = 10)
    {
        return true;
    }
    function remove_shortcode($tag)
    {
    }
    function has_filter($hook, $cb = false)
    {
        return false;
    }
    function do_action($hook, ...$args)
    {
    }

    function is_admin()
    {
        return !empty($GLOBALS["__is_admin"]);
    }
    function wp_doing_ajax()
    {
        return !empty($GLOBALS["__is_ajax"]);
    }
    function is_ssl()
    {
        return !empty($GLOBALS["__is_ssl"]);
    }
    function wp_enqueue_style(...$args)
    {
    }
    function wp_enqueue_script(...$args)
    {
    }

    function sanitize_text_field($value)
    {
        return is_string($value) ? trim(preg_replace('/[\r\n\t]+/', " ", $value)) : $value;
    }
    function wp_unslash($value)
    {
        return is_string($value) ? stripslashes($value) : $value;
    }
    function esc_attr($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES);
    }
    function __($text, $domain = null)
    {
        return $text;
    }

    function add_settings_error($setting, $code, $message, $type = "error")
    {
        $GLOBALS["__settings_errors"][] = compact("setting", "code", "message", "type");
    }

    function get_option($key, $default = false)
    {
        return $GLOBALS["__options"] ?? $default;
    }
    function home_url($path = "")
    {
        return ($GLOBALS["__home_url"] ?? "https://example.com") . $path;
    }
    function site_url($path = "")
    {
        return ($GLOBALS["__site_url"] ?? "https://example.com") . $path;
    }
    function wp_parse_url($url, $component = -1)
    {
        return \parse_url($url, $component);
    }

    // --- Autoloaders: plugin src, the token SDK (vendored or the sibling submodule), the fixture ----
    $pluginSrc = __DIR__ . "/../src/";
    $vendoredSdk = __DIR__ . "/../vendor/zeroad.network/token/src/";
    $siblingSdk = __DIR__ . "/../../../../zeroad-token-php/src/";
    $sdkSrc = is_dir($vendoredSdk) ? $vendoredSdk : $siblingSdk;
    $fixtures = __DIR__ . "/../../../../zeroad-token-php/tests/Fixtures/";

    spl_autoload_register(function ($class) use ($pluginSrc, $sdkSrc, $fixtures) {
        $maps = [
            "ZeroAd\\WP\\" => $pluginSrc,
            "ZeroAd\\Token\\Tests\\Fixtures\\" => $fixtures,
            "ZeroAd\\Token\\" => $sdkSrc,
        ];
        foreach ($maps as $prefix => $base) {
            if (strncmp($class, $prefix, strlen($prefix)) === 0) {
                $file = $base . str_replace("\\", "/", substr($class, strlen($prefix))) . ".php";
                if (is_file($file)) {
                    require $file;
                    return;
                }
            }
        }
    });

    if (!is_file($sdkSrc . "Publisher.php")) {
        fwrite(STDERR, "\nCannot find the zeroad.network/token SDK at {$sdkSrc}.\n"
            . "Run `composer install` in the plugin, or keep the zeroad-token-php submodule checked out.\n\n");
        exit(1);
    }
}
