<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\WP\Actions\Action;

// cspell:words Divi  Foobox  Icegram OMAPI Optin POPTIN  Popupaoc  SGPM  Supsystic convertflow hubspot mailoptin nksnewslettersubscriber nopriv  optinmonster  paoc  sgpb  thrv  wpforms wppopup
class MarketingDialogs extends Action
{
  public static function enabled(array $ctx): bool
  {
    return !empty($ctx["HIDE_MARKETING_DIALOGS"]);
  }

  public static function run(): void
  {
    // POPUPS
    parent::disablePlugins([
      // Popup Maker
      ["popup-maker", "Pum_", []],

      // OptinMonster
      ["optin-monster-api", "OMAPI", ["optin-monster", "optin-monster-shortcode", "optin-monster-inline"]],

      // Popup Maker WP
      [null, "SGPM", []],

      // Popup by Supsystic
      ["popup-by-supsystic", "popupPps", ["supsystic-show-popup", "embed"]],

      // PopupKit
      ["popup-builder-block", "PopupBuilderBlock", []],

      // Popup Anything - A Marketing Popup
      ["popup-anything-on-click", "Popupaoc_", ["paoc_details"]],

      // Advanced Popups
      ["advanced-popups", "ADP_", []],

      // Popup Box
      ["ays-popup-box", "Ays_Pb_", ["ays_pb"]],

      // Depicter — Popup & Slider Builder
      ["depicter", "depicter_", ["depicter"]],

      // FooBox Image Lightbox
      ["foobox-image-lightbox", "Foobox_", []],

      // MailOptin - Lite
      ["mailoptin", "MailOptin", ["mo-mailchimp-interests", "posts-loop", "mo-optin-form-wrapper", ""]],

      // Poptin
      ["poptin", "POPTIN_", ["poptin-form"]],

      // Popup Builder - Create highly converting, mobile friendly marketing popups.
      ["popup-builder", "sgpb", ["sg_popup"]],

      // Popups for Divi
      ["divi-popup", "pfd_", []],

      // Hustle
      ["hustle", "Hustle_", ["wd_hustle", "wd_hustle_cc", "wd_hustle_ss", "wd_hustle_unsubscribe"]],

      // WP Popups Lite
      [
        "wp-popups-lite",
        "WPPopups_",
        ["wppopup-template", "spu-facebook", "spu-facebook-page", "spu-twitter", "spu-close", "spu"]
      ],

      // Icegram Express - Email Subscribers, Newsletters and Marketing Automation Plugin
      [
        "email-subscribers",
        "Email_Subscribers_",
        ["email-subscribers", "email-subscribers-advanced-form", "email-subscribers-form"]
      ]
    ]);

    // Email Subscribe
    parent::removeActions([
      ["wp_footer", "addModalPopupHtmlToWpFooter"],
      ["wp_enqueue_scripts", "email_subscription_popup_load_styles_and_js"],
      ["wp_ajax_getEmailTemplate", "getEmailTemplate"],
      ["widgets_init", "nksnewslettersubscriberSet"],
      ["wp_ajax_store_email", "store_email_callback"],
      ["wp_ajax_nopriv_store_email", "store_email_callback"]
    ]);

    wp_enqueue_style(
      "zero-ad-marketing-dialogs",
      ZERO_AD_NETWORK_PLUGIN_URL . "assets/css/marketing-dialogs.css",
      [],
      ZERO_AD_NETWORK_PLUGIN_VERSION
    );
  }

  public static function outputBufferCallback(string $html): string
  {
    // Remove/hide popups and modal marketing elements
    return parent::runReplacements($html, [
      // Remove typical popup scripts (OptinMonster, popup-maker, hubspot popups, thrive leads)
      '#<script[^>]*(src=[\'"][^\'"]*(optinmonster|popup-maker|thrive|hubspot|wpforms-popup|convertflow)[^\'"]*[\'"])[^>]*>.*?</script>#is',

      // Remove popup elements and overlays by classes/id
      '#<(div|section)[^>]*(class|id)\s*=\s*["\'][^"\']*(popup|modal|marketing|optin|om-popup|pum|thrive-leads|hubspot-conversations)[^"\']*["\'][^>]*>.*?</(div|section)>#is'
    ]);

    return $workHtml;
  }
}
