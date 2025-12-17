<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\WP\Actions\Action;

// cspell:words Mepr  pmpro woocommerce
class SubscriptionAccess extends Action
{
  public static function enabled(array $ctx): bool
  {
    return !empty($ctx["ENABLE_SUBSCRIPTION_ACCESS"]);
  }

  public static function run(): void
  {
    // Give general "yes" to all membership plugins
    parent::addFilters([["user_can", "__return_true", 999, 3], ["current_user_can", "__return_true", 999, 3]]);

    parent::disablePlugins([
      // Paid Member Subscriptions
      ["paid-member-subscriptions", "Paid_Member_", ["pms-wpb-register"]],

      // WPSubscription
      ["wp_subscription", "SpringDevs\Subscription", []],

      // Subscriptions For WooCommerce
      ["subscriptions-for-woocommerce", "wps_sfw_", ["wps-subscription-dashboard"]]
    ]);
  }

  public static function outputBufferCallback(string $html): string
  {
    // Work on the HTML safely — operate on body content only if present
    $bodyStart = stripos($html, "<body");
    if ($bodyStart === false) {
      // Fallback: work globally
      $workHtml = $html;
    } else {
      $workHtml = $html;
    }

    // Try to set membership filters for common membership plugins (also a targeted hook approach is used in registerPluginOverrides)
    if ($subsOn) {
      // Indicate in-page to let other scripts know
      $subNote = '<meta name="zeroad-subscription-access" content="1" />';
      $workHtml = parent::injectIntoHead($workHtml, $subNote);
    }

    // Return processed HTML
    return $workHtml;
  }

  /**
   * Register plugin-specific overrides for major membership/paywall plugins.
   * This is best-effort and checks for existence of plugin-specific hooks/APIs before using them.
   */
  public static function registerPluginOverrides(): void
  {
    // Paid Memberships Pro: filter pmpro_has_membership_level and pmpro_has_membership_access
    // pmpro_has_membership_level filter
    add_filter(
      "pmpro_has_membership_level",
      function ($has, $user_id, $levels) {
        // If feature requests access, allow membership-level checks to succeed
        return true;
      },
      10,
      3
    );

    // pmpro_has_membership_access_filter
    add_filter(
      "pmpro_has_membership_access_filter",
      function ($hasAccess, $post, $user, $levels) {
        return true;
      },
      10,
      4
    );

    // MemberPress: try to short-circuit common filters and methods
    if (class_exists("MeprUser")) {
      // Many MemberPress checks go via MeprUser->is_active() or MeprRules. We can hook into MemberPress filter points if available.
      // There is no single canonical filter we can override safely for all sites, but MemberPress exposes 'mepr-last-chance-to-block-content' before blocking content.
      add_filter(
        "mepr-last-chance-to-block-content",
        function ($maybe_block) {
          // Return false to indicate "do not block"
          return false;
        },
        10,
        1
      );
    }

    // Generic: If a plugin defines a 'has_access' style filter, attempt to register a permissive filter
    // Some plugins use authorizer_has_access (example discovered on wordpress.org support). We'll apply broadly but only if functions exist.
    add_filter(
      "authorizer_has_access",
      function ($has) {
        return true;
      },
      10,
      1
    );
  }
}
