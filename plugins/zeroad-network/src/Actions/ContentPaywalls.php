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
    parent::addFilters([
      // Content Control
      ["content_control/protection_is_disabled", "__return_true", 999],

      // Leaky Paywall
      ["leaky_paywall_current_user_can_view_all_content", "__return_true", 999],

      // MemberFul
      ["memberful_wp_protect_content", "__return_false", 999],

      // Membership For WooCommerce
      ["woocommerce_is_purchasable", "__return_false", 999]
    ]);

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
      ["flexible-subscriptions", "WPDesk\FlexibleSubscriptions", []],

      // SureCart
      ["surecart", "SureCart", ["sc_form", "sc_line_item", "sc_buy_button"]]
    ]);
  }

  public static function outputBufferCallback(string $html): string
  {
    // Work on the HTML safely — operate on body content only if present
    $bodyStart = stripos($html, "<body");
    if ($bodyStart === false) {
      // fallback: work globally
      $workHtml = $html;
    } else {
      $workHtml = $html;
    }

    // Remove paywall overlays and un-hide hidden content as best-effort

    // Remove overlay elements by class/id names used by common paywall plugins
    $workHtml = preg_replace(
      '#<(div|aside|section)[^>]*(class|id)\s*=\s*["\'][^"\']*(paywall|pay-wall|leaky-paywall|memberpress|mepr|pmpro|paywall-overlay|paywall-layer|restricted-content|subscription-required)[^"\']*["\'][^>]*>.*?</(div|aside|section)>#is',
      "",
      $workHtml
    );

    // Remove scripts from paywall providers (search for paywall in src)
    $workHtml = preg_replace('#<script[^>]*(src=[\'"][^\'"]*paywall[^\'"]*[\'"])[^>]*>.*?</script>#is', "", $workHtml);

    // Unhide elements with inline styles display:none and data attributes often used to hide content
    $workHtml = preg_replace_callback(
      "#<(div|section|article|p|span)[^>]*>#is",
      function ($m) {
        $tag = $m[0];
        // remove style="display:none" or style="visibility:hidden" only if tag contains paywall-related attributes or class names
        $new = preg_replace('/\sstyle\s*=\s*(["\'])(?:(?!\1).)*\bdisplay\s*:\s*none;?(?:(?!\1).)*\1/i', "", $tag);
        $new = preg_replace('/\sstyle\s*=\s*(["\'])(?:(?!\1).)*\bvisibility\s*:\s*hidden;?(?:(?!\1).)*\1/i', "", $new);
        return $new;
      },
      $workHtml
    );

    // Inject CSS to force show content that may have been hidden via classes
    $showCss =
      "<style data-zeroad> .paywall, .paywall-hidden, .restricted, .subscription-required, .mepr-access-restricted { display: block !important; visibility: visible !important; height: auto !important; max-height: none !important; }</style>";
    $workHtml = parent::injectIntoHead($workHtml, $showCss);

    return $workHtml;
  }
}
