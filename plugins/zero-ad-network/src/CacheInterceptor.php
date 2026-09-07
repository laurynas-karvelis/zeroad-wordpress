<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
    exit();
}

/**
 * Keeps the subscriber and regular versions of a page in separate cache entries.
 *
 * A subscriber sees a cleaned page; everyone else sees the standard one. If a cache serves one to the
 * other, the whole thing breaks - a subscriber sees ads, or worse, a non-subscriber is served a page
 * with the paywall stripped. Two mechanisms keep them apart:
 *
 *  - A `zeroad_variant` cookie and a matching `Vary: X-ZeroAd-Variant` / `X-ZeroAd-Variant` header, so a
 *    CDN, reverse proxy, or WordPress page-cache plugin has a real value to key on. The cookie is the
 *    important half: page-cache plugins vary on cookies, and it is only ever sent when it changes, so a
 *    steady-state response stays cacheable.
 *
 * Note on full-page caches: a plugin like WP Super Cache can serve a hit from its `advanced-cache.php`
 * layer before WordPress (and therefore this plugin) loads. On that path we cannot verify the token or
 * emit anything, so the split relies entirely on the cookie the plugin is configured to vary on. The
 * first request from a new subscriber may be served the standard cached page until the cookie is seeded
 * on a request that does reach PHP; from then on the correct variant is served. See the Cache
 * Configuration admin page for per-cache setup.
 */
class CacheInterceptor
{
    /** The cookie carrying the variant, so caches that key on cookies can tell the two versions apart. */
    private const COOKIE_NAME = "zeroad_variant";

    /**
     * The cache variant for this visitor. With a single Freedom plan a page has exactly two shapes -
     * the clean subscriber version and the standard one - so the variant is just which of the two.
     */
    private static function buildVariantString(bool $isSubscriber): string
    {
        return $isSubscriber ? "subscriber1" : "subscriber0";
    }

    public static function registerPluginOverrides(bool $isSubscriber): void
    {
        $variant = self::buildVariantString($isSubscriber);

        // Emit the variant header/cookie for edge, proxy and page caches to key on.
        add_action(
            "send_headers",
            function () use ($variant) {
                self::sendVariantSignals($variant);
            },
            1
        );

        // Page-cache plugins: fold the variant into their cache key (generation side).
        self::registerWPSuperCache($variant);
        self::registerWPRocket($variant);
        self::registerW3TotalCache($variant);
        self::registerLiteSpeedCache($variant);
        self::registerWPFastestCache($variant);
    }

    private static function sendVariantSignals(string $variant): void
    {
        if (headers_sent()) {
            return;
        }

        header("X-ZeroAd-Variant: {$variant}", false);
        header("Vary: X-ZeroAd-Variant", false);

        // Only (re)set the cookie when it would actually change, so a response whose variant already
        // matches carries no `Set-Cookie` and stays cacheable.
        $current = isset($_COOKIE[self::COOKIE_NAME]) ? $_COOKIE[self::COOKIE_NAME] : null;
        if ($current === $variant) {
            return;
        }

        $secure = function_exists("is_ssl") ? is_ssl() : false;
        $path = defined("COOKIEPATH") && COOKIEPATH !== "" ? COOKIEPATH : "/";
        $domain = defined("COOKIE_DOMAIN") ? COOKIE_DOMAIN : "";

        // Session cookie, HTTP-only: the browser sends it on later navigations (so the cache can vary on
        // it) but scripts cannot read it. Positional signature keeps PHP 7.2 support.
        setcookie(self::COOKIE_NAME, $variant, 0, $path, $domain, $secure, true);
        $_COOKIE[self::COOKIE_NAME] = $variant;
    }

    private static function registerWPSuperCache(string $variant): void
    {
        if (!function_exists("wp_cache_get_cookies_values")) {
            return;
        }

        add_filter(
            "wp_cache_get_cookies_values",
            function ($cookies) use ($variant) {
                return $cookies . $variant;
            },
            10,
            1
        );
    }

    private static function registerWPRocket(string $variant): void
    {
        if (!function_exists("get_rocket_option")) {
            return;
        }

        // WP Rocket varies its cache on the cookies named here; the cookie set above supplies the value.
        add_filter(
            "rocket_cache_dynamic_cookies",
            function ($cookies) use ($variant) {
                $cookies[] = self::COOKIE_NAME;
                return $cookies;
            },
            10,
            1
        );
    }

    private static function registerW3TotalCache(string $variant): void
    {
        if (!function_exists("w3tc_add_action")) {
            return;
        }

        add_filter(
            "w3tc_pagecache_cache_key",
            function ($cache_key) use ($variant) {
                return $cache_key . "_" . $variant;
            },
            10,
            1
        );
    }

    private static function registerLiteSpeedCache(string $variant): void
    {
        if (!class_exists("LiteSpeed\\Core")) {
            return;
        }

        add_filter(
            "litespeed_vary_name",
            function ($vary_name) use ($variant) {
                return $vary_name . "_" . $variant;
            },
            10,
            1
        );
    }

    private static function registerWPFastestCache(string $variant): void
    {
        if (!class_exists("WpFastestCache")) {
            return;
        }

        add_filter(
            "wpfc_cache_path",
            function ($path) use ($variant) {
                return $path . "_" . $variant;
            },
            10,
            1
        );
    }
}
