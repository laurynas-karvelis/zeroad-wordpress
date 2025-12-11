<?php

declare(strict_types=1);

namespace ZeroAd\WP\Features;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\WP\Features\Base;

class MarketingDialogs extends Base
{
  public static function intercept(array $ctx): bool
  {
    return !empty($ctx["HIDE_MARKETING_DIALOGS"]);
  }

  public static function toggle(): void
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

    // Remove/hide popups and modal marketing elements

    // Remove typical popup scripts (OptinMonster, popup-maker, hubspot popups, thrive leads)
    $workHtml = preg_replace(
      '#<script[^>]*(src=[\'"][^\'"]*(optinmonster|popup-maker|thrive|hubspot|wpforms-popup|convertflow)[^\'"]*[\'"])[^>]*>.*?</script>#is',
      "",
      $workHtml
    );
    // Remove popup elements and overlays by classes/id
    $workHtml = preg_replace(
      '#<(div|section)[^>]*(class|id)\s*=\s*["\'][^"\']*(popup|modal|marketing|optin|om-popup|pum|thrive-leads|hubspot-conversations)[^"\']*["\'][^>]*>.*?</(div|section)>#is',
      "",
      $workHtml
    );
    // hide by CSS if anything remains
    $hidePopCss =
      "<style data-zeroad> .popup, .modal, .marketing, .optin, .pum-overlay, .thrv-modal { display:none !important; visibility:hidden !important; }</style>";
    $workHtml = parent::injectIntoHead($workHtml, $hidePopCss);

    return $workHtml;
  }
}
