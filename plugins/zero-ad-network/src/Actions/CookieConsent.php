<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\WP\Actions\Action;

class CookieConsent extends Action
{
  public static function enabled(array $ctx): bool
  {
    return !empty($ctx["HIDE_COOKIE_CONSENT_SCREEN"]) || !empty($ctx["DISABLE_NON_FUNCTIONAL_TRACKING"]);
  }

  public static function run(): void
  {
    // Disable cookie consent plugins
    self::disablePlugins([
      // Cookie Bot
      ["cookiebot", "cybot\\cookiebot", ["cookie_declaration", "uc_embedding"]],

      // Beautiful and responsive cookie consent
      ["bar-cookie-consent", "nsc_bar_", ["cc_show_cookie_banner_nsc_bar"]],

      // Real Cookie Banner
      [
        "real-cookie-banner",
        "DevOwl\\RealCookieBanner",
        ["rcb-consent", "rcb-consent-history-uuids", "rcb-consent-print-uuid", "rcb-cookie-policy"]
      ],

      // GDPR cookie consent
      ["gdpr-cookie-consent", "Gdpr_", ["wpl_data_request", "wpl_cookie_details", "youtube"]],

      // Pressidium Cookie Consent
      ["pressidium-cookie-consent", "Pressidium\\WP\\CookieConsent", []],

      // WPConsent
      ["wpconsent-cookies-banner-privacy-suite", "WPConsent_", ["wpconsent_cookie_policy"]],

      // GDPR Cookie Compliance
      ["gdpr-cookie-compliance", "Moove_GDPR_", []],

      // Complianz | GDPR/CCPA Cookie Consent
      [
        "complianz-gdpr",
        "cmplz_",
        [
          "cmplz-document",
          "cmplz-consent-area",
          "cmplz-cookies",
          "cmplz-manage-consent",
          "cmplz-revoke-link",
          "cmplz-accept-link"
        ]
      ],

      // CookieYes | GDPR Cookie Consent
      [
        "cookie-law-info",
        "CookieYes",
        [
          "wt_cli_ccpa_optout",
          "delete_cookies",
          "cookie_audit",
          "cookie_accept",
          "cookie_reject",
          "cookie_settings",
          "cookie_link",
          "cookie_button",
          "cookie_after_accept",
          "user_consent_state",
          "webtoffee_powered_by",
          "cookie_close",
          "wt_cli_manage_consent",
          "cookie_accept_all"
        ]
      ],

      // Cookie Notice & Compliance for GDPR / CCPA
      ["cookie-notice", "Cookie_Notice", ["cookies_accepted", "cookies_revoke", "cookies_policy_link"]],

      // CookieAdmin - Cookie Consent Banner
      ["cookieadmin", "cookieadmin_", []],

      // Cookie Notice and Consent Banner
      ["cookie-notice-and-consent-banner", "CNCB_", ["revoke_consent"]],

      // Cookies and Content Security Policy
      ["cookies-and-content-security-policy", "cacsp_", []],

      // Termly - GDPR/CCPA Cookie Consent Banner
      ["uk-cookie-consent", "termly", []],

      // GDPR Cookie Consent
      ["gdpr", "GDPR_", []],

      // Cookie Consent Box by Supsystic
      ["gdpr-cookie-consent-by-supsystic", "supsystic", []]
    ]);

    // Enqueue CSS to hide cookie banners
    if (!is_admin()) {
      wp_enqueue_style(
        "zero-ad-cookie-consent",
        ZEROAD_PLUGIN_URL . "assets/css/cookie-consent.css",
        [],
        ZEROAD_VERSION
      );
    }
  }

  public static function outputBufferCallback(string $html): string
  {
    // Remove cookie consent banners and scripts from HTML
    return parent::runReplacements($html, [
      // Remove cookie/consent/GDPR/CCPA scripts (limit backtracking)
      '#<script[^>]{0,500}(src=[\'"][^\'"]{0,500}(cookie|consent|gdpr|ccpa)[^\'"]{0,200}[\'"]|[^>]{0,300}(cookie|consent|gdpr|ccpa))[^>]{0,200}>(?:(?!</script>).){0,10000}</script>#is',

      // Remove cookie banner containers (limit size)
      '#<(div|section|aside)[^>]{0,300}(id|class)\s*=\s*["\'][^"\']{0,200}(cookie|cookie-banner|cookie-consent|cc-window|cookie-modal|cc-banner|complianz|cookieyes|gdpr|ccpa)[^"\']{0,200}["\'][^>]{0,300}>(?:(?!</\1>).){0,5000}</\1>#is',

      // Remove Cookiebot scripts
      "#<script[^>]{0,500}id=['\"]Cookiebot['\"][^>]{0,200}>(?:(?!</script>).){0,5000}</script>#is",

      // Remove cookie consent meta tags
      '#<meta[^>]{0,300}name=["\']?[^"\']{0,100}(cookie|consent)[^"\']{0,100}["\']?[^>]{0,200}>#is'
    ]);
  }

  /**
   * Register plugin-specific overrides
   *
   * @param array $ctx Token context
   */
  public static function registerPluginOverrides(array $ctx): void
  {
    // Cookiebot
    if (function_exists("cookiebot_active")) {
      add_filter("cookiebot_active", "__return_false", 999);
    }

    // Complianz
    if (function_exists("cmplz_has_consent")) {
      add_filter("cmplz_has_consent", "__return_true", 999);
      add_filter("cmplz_show_banner", "__return_false", 999);
    }

    // Cookie Notice
    if (class_exists("Cookie_Notice")) {
      add_filter("cn_is_cookie_accepted", "__return_true", 999);
    }

    // Real Cookie Banner
    if (class_exists("DevOwl\\RealCookieBanner\\Core")) {
      add_filter("rcb/consent/created", "__return_true", 999);
    }
  }
}
