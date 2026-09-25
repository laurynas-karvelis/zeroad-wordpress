<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

use ZeroAd\WP\IncludedContent;

if (!defined("ABSPATH")) {
    exit();
}

class SubscriptionAccess extends Action
{
    public static function enabled(array $ctx): bool
    {
        return !empty($ctx["ENABLE_SUBSCRIPTION_ACCESS"]);
    }

    public static function run(): void
    {
    }

    public static function outputBufferCallback(string $html): string
    {
        return $html;
    }

    public static function registerPluginOverrides(array $ctx): void
    {
        if (!self::enabled($ctx)) {
            return;
        }

        add_filter("pmpro_has_membership_access_filter", [self::class, "membershipAccess"], 999, 2);
        add_filter("wpmem_block", [self::class, "blockContent"], 999, 2);
    }

    public static function membershipAccess($hasAccess, $post)
    {
        return self::allowsPost($post) ? true : $hasAccess;
    }

    public static function blockContent($blocked, $args = [])
    {
        $postId = $args["post_id"] ?? 0;

        return $postId && self::allowsPost(get_post($postId)) ? false : $blocked;
    }

    public static function allowsPost($post): bool
    {
        $context = $GLOBALS["zeroad_token_context"] ?? [];

        if (!self::enabled($context) || is_admin() || wp_doing_ajax()) {
            return false;
        }

        if ((defined("REST_REQUEST") && REST_REQUEST) || (function_exists("wp_is_json_request") && wp_is_json_request())) {
            return false;
        }

        return IncludedContent::contains($post);
    }
}
