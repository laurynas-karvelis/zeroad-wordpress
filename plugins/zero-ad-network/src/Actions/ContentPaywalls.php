<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

use ZeroAd\WP\Actions\Action;

if (!defined("ABSPATH")) {
  exit();
}

class ContentPaywalls extends Action
{
  public static function enabled(array $ctx): bool
  {
    return !empty($ctx["DISABLE_CONTENT_PAYWALL"]);
  }

  public static function run(): void
  {
    // Enqueue CSS to handle paywall elements for subscribers
    add_action("wp_enqueue_scripts", function () {
      if (!is_admin()) {
        wp_enqueue_style(
          "zero-ad-content-paywalls",
          ZEROAD_PLUGIN_URL . "assets/css/content-paywalls.css",
          [],
          ZEROAD_VERSION
        );
      }
    });

    // Add filters to bypass common paywall plugins
    parent::addFilters([
      // Content Control
      ["content_control/protection_is_disabled", "__return_true", 999],

      // Leaky Paywall
      ["leaky_paywall_current_user_can_view_all_content", "__return_true", 999],

      // MemberFul
      ["memberful_wp_protect_content", "__return_false", 999],

      // Membership For WooCommerce - disable purchasability check
      ["woocommerce_is_purchasable", "__return_true", 999]
    ]);

    // Disable paywall plugins
    parent::disablePlugins([
      // Protected Video
      ["protected-video", "Protected_Video_", ["protected_video"]],

      // s2Member Framework
      ["s2member", "c_ws_plugin", []],

      // Secure Copy Content Protection
      ["secure-copy-content-protection", "Secure_Copy_Content_Protection", ["ays_block", "ays_block_subscribe"]],

      // Steady for WordPress
      ["steady-wp", "Steady_WP", []],

      // Pay For Post with WooCommerce
      ["wc_pay_per_post", "Woocommerce_Pay_Per_Post_", ["wc-pay-for-post-status"]],

      // ProfilePress
      ["wp-user-avatar", "ProfilePress", []],

      // Zlick
      ["zlick-paywall", "zp_", ["zlick_payment_widget", "zp_placeholder"]],

      // Easy Digital Downloads
      [
        "easy-digital-downloads",
        "edd_",
        ["purchase_link", "download_history", "purchase_history", "download_checkout", "download_cart"]
      ],

      // Flexible Subscriptions
      ["flexible-subscriptions", "WPDesk\\FlexibleSubscriptions", []],

      // SureCart
      ["surecart", "SureCart", ["sc_form", "sc_line_item", "sc_buy_button"]],

      // Restrict Content
      ["restrict-content", "RCP_", []],

      // Simple Membership
      ["simple-membership", "SwpmAuth", []],

      // WP-Members
      ["wp-members", "wpmem", []]
    ]);
  }

  public static function outputBufferCallback(string $html): string
  {
    // Remove paywall overlays and related elements from HTML
    return parent::runReplacements($html, [
      // Remove paywall overlay containers (limit size to prevent catastrophic backtracking)
      '#<(div|aside|section)[^>]{0,300}(class|id)\s*=\s*["\'][^"\']{0,200}(paywall|pay-wall|leaky-paywall|memberpress|mepr|pmpro|paywall-overlay|paywall-layer|restricted-content|subscription-required|premium-content|locked-content)[^"\']{0,200}["\'][^>]{0,300}>(?:(?!</\1>).){0,8000}</\1>#is',

      // Remove paywall scripts
      '#<script[^>]{0,500}(src=[\'"][^\'"]{0,500}paywall[^\'"]{0,200}[\'"])[^>]{0,200}>(?:(?!</script>).){0,5000}</script>#is',

      // Remove blur/fade effects commonly used for paywalls
      "#<style[^>]{0,200}>[^<]{0,500}(paywall|restricted|premium)[^<]{0,500}(blur|opacity|fade)[^<]{0,500}</style>#is",

      // Remove subscription prompts
      '#<div[^>]{0,300}(class|id)\s*=\s*["\'][^"\']{0,200}(subscribe|subscription|premium-access|membership-required)[^"\']{0,200}["\'][^>]{0,300}>(?:(?!</div>).){0,5000}</div>#is'
    ]);
  }

  /**
   * Register plugin-specific overrides
   *
   * @param array $ctx Token context
   */
  public static function registerPluginOverrides(array $ctx): void
  {
    // Leaky Paywall
    if (class_exists("Leaky_Paywall")) {
      add_filter("leaky_paywall_user_has_access", "__return_true", 999);
    }

    // Content Control
    if (function_exists("content_control")) {
      add_filter("content_control_user_can_view", "__return_true", 999);
    }

    // Restrict Content Pro
    if (function_exists("rcp_is_restricted")) {
      add_filter("rcp_is_restricted_content", "__return_false", 999);
    }

    // Simple Membership
    if (class_exists("SwpmAuth")) {
      add_filter("swpm_check_if_valid_post", "__return_true", 999);
    }

    // WP-Members
    if (function_exists("wpmem_is_blocked")) {
      add_filter("wpmem_block", "__return_false", 999);
    }
  }
}
