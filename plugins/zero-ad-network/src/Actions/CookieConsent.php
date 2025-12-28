<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\WP\Actions\Action;

// cspell:words CCPA  CNCB  Complianz Moove Pressidium  cacsp cmplz cookieadmin cookiebot cookieyes cybot optout  webtoffee wpconsent
class CookieConsent extends Action
{
  public static function enabled(array $ctx): bool
  {
    return !empty($ctx["HIDE_COOKIE_CONSENT_SCREEN"]) || !empty($ctx["DISABLE_NON_FUNCTIONAL_TRACKING"]);
  }

  public static function run(): void
  {
    // COOKIE CONSENT SCREENS
    self::disablePlugins([
      // Cookie Bot
      ["cookiebot", "cybot\cookiebot", ["cookie_declaration", "uc_embedding"]],

      // Beautiful and responsive cookie consent
      ["bar-cookie-consent", "nsc_bar_", ["cc_show_cookie_banner_nsc_bar"]],

      // Real Cookie Banner
      [
        "real-cookie-banner",
        "DevOwl\RealCookieBanner",
        ["rcb-consent", "rcb-consent-history-uuids", "rcb-consent-print-uuid", "rcb-cookie-policy", ""]
      ],

      // GDPR cookie consent
      ["gdpr-cookie-consent", "Gdpr_", ["wpl_data_request", "wpl_cookie_details", "youtube"]],

      // Pressidium Cookie Consent
      ["pressidium-cookie-consent", "Pressidium\WP\CookieConsent", []],

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
      ["uk-cookie-consent", "termly", []]
    ]);

    wp_enqueue_style(
      "zero-ad-cookie-consent",
      ZERO_AD_NETWORK_PLUGIN_URL . "assets/css/cookie-consent.css",
      [],
      ZERO_AD_NETWORK_PLUGIN_VERSION
    );
  }

  public static function outputBufferCallback(string $html): string
  {
    // Remove cookie banners/scripts
    return parent::runReplacements($html, [
      // Remove scripts containing 'cookie' or 'consent' in src or inline code variable names
      '#<script[^>]*(src=[\'"][^\'"]*(cookie|consent|gdpr|ccpa)[^\'"]*[\'"])[^>]*>.*?</script>#is',

      // Remove cookie banner elements by common ids/classes
      '#<(div|section|aside)[^>]*(id|class)\s*=\s*["\'][^"\']*(cookie|cookie-banner|cookie-consent|cc-window|cookie-modal|cc-banner|complianz|cookieyes)[^"\']*["\'][^>]*>.*?</(div|section|aside)>#is'
    ]);
  }
}
