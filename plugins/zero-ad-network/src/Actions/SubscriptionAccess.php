<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\WP\Actions\Action;

/**
 * SubscriptionAccess - ONE_PASS Feature Implementation
 *
 * This class handles the ONE_PASS feature ($12/month subscription tier) which allows
 * Zero Ad Network subscribers to access paywalled content and basic subscription features
 * on partner sites WITHOUT needing to create an account or purchase a separate subscription.
 *
 * CRITICAL SECURITY NOTE:
 * This class MUST NOT grant WordPress admin or editor capabilities. It should ONLY:
 * 1. Bypass content paywalls (allow reading protected posts)
 * 2. Disable membership plugin restrictions for viewing content
 * 3. Remove subscription requirement overlays
 *
 * It should NEVER allow:
 * - Creating/editing/deleting posts
 * - Managing WordPress settings
 * - Installing plugins
 * - Any admin-level operations
 */
class SubscriptionAccess extends Action
{
  public static function enabled(array $ctx): bool
  {
    return !empty($ctx["ENABLE_SUBSCRIPTION_ACCESS"]);
  }

  public static function run(): void
  {
    // Hook into WordPress content access checks
    // These hooks allow us to bypass paywalls without granting system capabilities
    parent::addFilters([
      // WordPress core password-protected posts
      ["post_password_required", [self::class, "bypassPasswordProtection"], 999, 2],

      // Membership plugin filters
      ["content_control/protection_is_disabled", "__return_true", 999],
      ["leaky_paywall_current_user_can_view_all_content", "__return_true", 999],
      ["memberful_wp_protect_content", "__return_false", 999]
    ]);

    // Disable subscription/paywall plugins
    parent::disablePlugins([
      // Paid Member Subscriptions
      ["paid-member-subscriptions", "Paid_Member_", ["pms-wpb-register"]],

      // WPSubscription
      ["wp_subscription", "SpringDevs\\Subscription", []],

      // Subscriptions For WooCommerce
      ["subscriptions-for-woocommerce", "wps_sfw_", ["wps-subscription-dashboard"]]
    ]);
  }

  /**
   * Bypass WordPress password protection for Zero Ad subscribers
   *
   * This allows ONE_PASS subscribers to read password-protected posts
   * without entering the password. Does NOT grant any editing capabilities.
   *
   * @param bool $required Whether password is required
   * @param WP_Post $post The post object
   * @return bool False to bypass password requirement
   */
  public static function bypassPasswordProtection($required, $post)
  {
    // Get token context
    $tokenContext = $GLOBALS["zeroad_token_context"] ?? null;

    // Only bypass if valid ONE_PASS token
    if (empty($tokenContext) || empty($tokenContext["ENABLE_SUBSCRIPTION_ACCESS"])) {
      return $required; // Not a subscriber, keep password protection
    }

    return false; // Bypass password requirement
  }

  /**
   * Output buffer callback - remove paywall overlays from HTML
   *
   * @param string $html The HTML content
   * @return string Modified HTML with paywalls removed
   */
  public static function outputBufferCallback(string $html): string
  {
    // Get token context
    $tokenContext = $GLOBALS["zeroad_token_context"] ?? null;

    // Only process if valid ONE_PASS token
    if (empty($tokenContext) || empty($tokenContext["ENABLE_SUBSCRIPTION_ACCESS"])) {
      return $html;
    }

    // Remove paywall overlays and subscription requirement notices
    return parent::runReplacements($html, [
      // Remove paywall overlay containers (limited size to prevent catastrophic backtracking)
      '#<(div|aside|section)[^>]{0,300}(class|id)\s*=\s*["\'][^"\']{0,200}(paywall|pay-wall|subscription-required|premium-content|locked-content|member-only)[^"\']{0,200}["\'][^>]{0,300}>(?:(?!</\1>).){0,8000}</\1>#is',

      // Remove blur/fade effects used by paywalls
      "#<style[^>]{0,200}>[^<]{0,500}(paywall|restricted|premium)[^<]{0,500}(blur|opacity|fade)[^<]{0,500}</style>#is",

      // Remove subscription prompts
      '#<div[^>]{0,300}(class|id)\s*=\s*["\'][^"\']{0,200}(subscribe|subscription|premium-access|membership-required)[^"\']{0,200}["\'][^>]{0,300}>(?:(?!</div>).){0,5000}</div>#is'
    ]);
  }

  /**
   * Register plugin-specific overrides for major membership/paywall plugins
   *
   * This method hooks into specific membership plugins to grant content access
   * to Zero Ad Network ONE_PASS subscribers.
   *
   * IMPORTANT: These hooks ONLY grant READ access to content, never admin capabilities.
   *
   * @param array $ctx Token context
   */
  public static function registerPluginOverrides(array $ctx): void
  {
    // Only register if ONE_PASS is enabled
    if (empty($ctx["ENABLE_SUBSCRIPTION_ACCESS"])) {
      return;
    }

    // Paid Memberships Pro
    if (function_exists("pmpro_has_membership_level")) {
      // Make subscriber appear to have membership level for content access checks
      add_filter(
        "pmpro_has_membership_level",
        function ($hasLevel, $user_id, $levels) {
          // Grant access to view membership content
          return true;
        },
        999,
        3
      );

      // Grant access to membership-protected posts
      add_filter(
        "pmpro_has_membership_access_filter",
        function ($hasAccess, $post, $user, $levels) {
          // Grant access to read the post
          return true;
        },
        999,
        4
      );
    }

    // MemberPress
    if (class_exists("MeprUser")) {
      // Prevent MemberPress from blocking content
      add_filter(
        "mepr-last-chance-to-block-content",
        function ($shouldBlock) {
          return false; // Don't block content for ONE_PASS subscribers
        },
        999,
        1
      );

      // Make subscriptions appear active for content access
      add_filter(
        "mepr_is_active_subscription",
        function ($isActive) {
          return true;
        },
        999,
        1
      );
    }

    // Restrict Content Pro
    if (function_exists("rcp_user_has_access")) {
      add_filter(
        "rcp_user_has_access",
        function ($hasAccess, $user_id, $post_id) {
          return true; // Grant access to view content
        },
        999,
        3
      );

      // Don't show restricted content messages
      add_filter("rcp_is_restricted_content", "__return_false", 999);
    }

    // WooCommerce Subscriptions
    if (class_exists("WC_Subscriptions")) {
      add_filter(
        "woocommerce_subscription_is_active",
        function ($isActive) {
          return true;
        },
        999,
        1
      );

      // Make products appear as if user has active subscription
      add_filter(
        "woocommerce_customer_has_subscription",
        function ($hasSubscription, $user_id, $product_id) {
          return true;
        },
        999,
        3
      );
    }

    // s2Member
    if (function_exists("c_ws_plugin__s2member_user_access")) {
      add_filter(
        "s2member_user_access",
        function ($hasAccess) {
          return true;
        },
        999,
        1
      );
    }

    // Simple Membership
    if (class_exists("SwpmAuth")) {
      add_filter(
        "swpm_check_if_valid_post",
        function ($isValid) {
          return true;
        },
        999,
        1
      );
    }

    // WP-Members
    if (function_exists("wpmem_is_blocked")) {
      add_filter("wpmem_block", "__return_false", 999);
    }

    // Leaky Paywall
    if (class_exists("Leaky_Paywall")) {
      add_filter("leaky_paywall_user_has_access", "__return_true", 999);
    }

    // Content Control
    if (function_exists("content_control")) {
      add_filter("content_control_user_can_view", "__return_true", 999);
    }

    // Generic authorizer (used by some custom membership setups)
    if (has_filter("authorizer_has_access")) {
      add_filter(
        "authorizer_has_access",
        function ($hasAccess) {
          return true;
        },
        999,
        1
      );
    }
  }
}
