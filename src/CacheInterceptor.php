<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

class CacheInterceptor
{
  /**
   * Build the normalized variant string used as cache key.
   *
   * Example:
   * clean_web{0|1}-one_pass{0|1}
   */
  private static function buildVariantString(array $tokenContext): string
  {
    $flags = [
      "HIDE_ADVERTISEMENTS" => "clean_web",
      "ENABLE_SUBSCRIPTION_ACCESS" => "one_pass"
    ];

    $parts = [];

    foreach ($flags as $flag => $short) {
      $parts[] = $short . ($tokenContext[$flag] ? "1" : "0");
    }

    return implode("-", $parts);
  }

  public static function registerPluginOverrides(array $tokenContext): void
  {
    $variant = self::buildVariantString($tokenContext);

    // -------------------------------
    // WP Super Cache
    // -------------------------------
    if (function_exists("wp_cache_get_cookies_values")) {
      add_filter(
        "wp_cache_get_cookies_values",
        function ($cookies) use ($variant) {
          // Append variant to the cache key
          $cookies["zeroad_variant"] = $variant;
          return $cookies;
        },
        10,
        1
      );

      add_filter(
        "wpsc_cachedata",
        function ($data) use ($variant) {
          // Optionally append variant to cached HTML key (for better isolation)
          if (is_array($data) && isset($data["cachekey"])) {
            $data["cachekey"] .= "_" . $variant;
          }
          return $data;
        },
        10,
        1
      );
    }

    // -------------------------------
    // Cachify
    // -------------------------------
    if (defined("CACHIFY_VERSION")) {
      // Cachify does not have an explicit key filter, but we can hook into its "skip cache" filter
      add_filter("cachify_skip_cache", function ($skip) use ($variant) {
        // Ensure variant-specific pages are cached separately or forced fresh
        if (!empty($variant)) {
          // Returning false ensures it does cache, but you may customize per variant
          return false;
        }
        return $skip;
      });

      // Advanced: attempt to append variant to cache hash (for DB / object backends)
      add_filter(
        "cachify_minify_ignore_tags",
        function ($should_cache, $data, $backend, $cache_hash, $expires) use ($variant) {
          if (!empty($variant) && is_string($cache_hash)) {
            $cache_hash .= "_" . $variant;
          }
          return $should_cache;
        },
        10,
        5
      );
    }

    // -------------------------------
    // Generic fallback for unknown caching plugins
    // -------------------------------
    add_action(
      "template_redirect",
      function () use ($variant) {
        // Always emit the variant header, so edge/CDN caches can respect it
        header("X-ZeroAd-Variant: {$variant}", false);
        header("Vary: X-ZeroAd-Variant", false);
      },
      0
    );
  }
}
