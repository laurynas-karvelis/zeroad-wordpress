<?php

declare(strict_types=1);

namespace ZeroAd\WP;

use ZeroAd\Token\Constants;

if (!defined("ABSPATH")) {
    exit();
}

class CacheInterceptor
{
    public static function preventPageCaching(): void
    {
        // Header presence requests verification, never access. Invalid and empty tokens also bypass.
        $hasToken = array_key_exists(Constants::TOKEN_HEADER_SERVER_KEY, $_SERVER);

        // Early cache hits must be bypassed by the host before WordPress loads this plugin.
        if ($hasToken && !defined("DONOTCACHEPAGE")) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Standard third-party cache bypass constant.
            define("DONOTCACHEPAGE", true);
        }

        add_action("send_headers", function () use ($hasToken) {
            if ($hasToken) {
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed's public cache-control hook.
                do_action("litespeed_control_set_nocache", "Freedom token verification");
            }

            if (headers_sent()) {
                return;
            }

            header("Vary: " . Constants::TOKEN_HEADER, false);

            if (!$hasToken) {
                return;
            }

            header("Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0", true);
            header("CDN-Cache-Control: no-store", true);
            header("X-LiteSpeed-Cache-Control: no-cache", true);
        }, PHP_INT_MAX);
    }
}
