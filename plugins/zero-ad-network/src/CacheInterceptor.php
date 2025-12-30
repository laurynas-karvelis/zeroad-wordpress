<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

/**
 * CacheInterceptor - Ensures caching systems vary by feature flags
 *
 * This class registers cache key modifications for popular WordPress caching plugins
 * to ensure pages are cached separately based on the user's token context.
 */
class CacheInterceptor
{
  /**
   * Build the normalized variant string used as cache key suffix
   *
   * Example output: "clean_web1-one_pass0"
   *
   * @param array $tokenContext Parsed token context with feature flags
   * @return string Cache variant identifier
   */
  private static function buildVariantString(array $tokenContext): string
  {
    $flags = [
      "HIDE_ADVERTISEMENTS" => "clean_web",
      "ENABLE_SUBSCRIPTION_ACCESS" => "one_pass"
    ];

    $parts = [];

    foreach ($flags as $flag => $short) {
      $value = !empty($tokenContext[$flag]) ? "1" : "0";
      $parts[] = $short . $value;
    }

    return implode("-", $parts);
  }

  /**
   * Register cache plugin overrides to vary by token context
   *
   * @param array $tokenContext Parsed token context
   */
  public static function registerPluginOverrides(array $tokenContext): void
  {
    $variant = self::buildVariantString($tokenContext);

    self::debugLog("Cache variant: {$variant}");

    // Always emit variant header for edge/CDN caching
    add_action(
      "send_headers",
      function () use ($variant) {
        if (!headers_sent()) {
          header("X-ZeroAd-Variant: {$variant}", false);
          header("Vary: X-ZeroAd-Variant", false);
        }
      },
      1
    );

    // WP Super Cache
    self::registerWPSuperCache($variant);

    // Cachify
    self::registerCachify($variant);

    // WP Rocket
    self::registerWPRocket($variant);

    // W3 Total Cache
    self::registerW3TotalCache($variant);

    // LiteSpeed Cache
    self::registerLiteSpeedCache($variant);

    // WP Fastest Cache
    self::registerWPFastestCache($variant);
  }

  /**
   * WP Super Cache integration
   */
  private static function registerWPSuperCache(string $variant): void
  {
    if (!function_exists("wp_cache_get_cookies_values")) {
      return;
    }

    // Add variant to cache key
    add_filter(
      "wp_cache_get_cookies_values",
      function ($cookies) use ($variant) {
        $cookies["zeroad_variant"] = $variant;
        return $cookies;
      },
      10,
      1
    );

    // Modify cache data key
    add_filter(
      "wpsc_cachedata",
      function ($data) use ($variant) {
        if (is_array($data) && isset($data["cachekey"])) {
          $data["cachekey"] .= "_" . $variant;
        }
        return $data;
      },
      10,
      1
    );

    self::debugLog("Registered WP Super Cache variant");
  }

  /**
   * Cachify integration
   */
  private static function registerCachify(string $variant): void
  {
    if (!defined("CACHIFY_VERSION")) {
      return;
    }

    // Ensure variant-specific pages are cached separately
    add_filter(
      "cachify_skip_cache",
      function ($skip) use ($variant) {
        // Don't skip cache, but ensure proper variant is used
        return $skip;
      },
      10,
      1
    );

    self::debugLog("Registered Cachify variant");
  }

  /**
   * WP Rocket integration
   */
  private static function registerWPRocket(string $variant): void
  {
    if (!function_exists("get_rocket_option")) {
      return;
    }

    // Add variant to cache key
    add_filter(
      "rocket_cache_dynamic_cookies",
      function ($cookies) use ($variant) {
        $cookies[] = "zeroad_variant=" . $variant;
        return $cookies;
      },
      10,
      1
    );

    // Vary cache by custom cookie
    add_filter(
      "rocket_htaccess_mod_rewrite",
      function ($rules) use ($variant) {
        // WP Rocket will handle the cookie automatically
        return $rules;
      },
      10,
      1
    );

    self::debugLog("Registered WP Rocket variant");
  }

  /**
   * W3 Total Cache integration
   */
  private static function registerW3TotalCache(string $variant): void
  {
    if (!function_exists("w3tc_add_action")) {
      return;
    }

    // Add variant to cache groups
    add_filter(
      "w3tc_pagecache_cache_key",
      function ($cache_key) use ($variant) {
        return $cache_key . "_" . $variant;
      },
      10,
      1
    );

    self::debugLog("Registered W3 Total Cache variant");
  }

  /**
   * LiteSpeed Cache integration
   */
  private static function registerLiteSpeedCache(string $variant): void
  {
    if (!class_exists("LiteSpeed\\Core")) {
      return;
    }

    // Add variant to cache vary
    add_filter(
      "litespeed_vary_name",
      function ($vary_name) use ($variant) {
        return $vary_name . "_" . $variant;
      },
      10,
      1
    );

    self::debugLog("Registered LiteSpeed Cache variant");
  }

  /**
   * WP Fastest Cache integration
   */
  private static function registerWPFastestCache(string $variant): void
  {
    if (!class_exists("WpFastestCache")) {
      return;
    }

    // Add variant to cache path
    add_filter(
      "wpfc_cache_path",
      function ($path) use ($variant) {
        return $path . "_" . $variant;
      },
      10,
      1
    );

    self::debugLog("Registered WP Fastest Cache variant");
  }

  /**
   * Debug logging helper
   */
  private static function debugLog(string $message): void
  {
    $options = get_option(\ZeroAd\WP\Config::OPT_KEY, []);

    if (!empty($options["debug_mode"]) && defined("WP_DEBUG") && WP_DEBUG && defined("WP_DEBUG_LOG") && WP_DEBUG_LOG) {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Conditional debug logging
      error_log("[Zero Ad Network - CacheInterceptor] " . $message);
    }
  }
}
