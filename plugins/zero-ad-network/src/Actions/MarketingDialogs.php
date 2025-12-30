<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\WP\Actions\Action;

class MarketingDialogs extends Action
{
  public static function enabled(array $ctx): bool
  {
    return !empty($ctx["HIDE_MARKETING_DIALOGS"]);
  }

  public static function run(): void
  {
    // Disable popup/modal plugins
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

      // Depicter – Popup & Slider Builder
      ["depicter", "depicter_", ["depicter"]],

      // FooBox Image Lightbox
      ["foobox-image-lightbox", "Foobox_", []],

      // MailOptin - Lite
      ["mailoptin", "MailOptin", ["mo-mailchimp-interests", "posts-loop", "mo-optin-form-wrapper"]],

      // Poptin
      ["poptin", "POPTIN_", ["poptin-form"]],

      // Popup Builder
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

      // Icegram Express
      [
        "email-subscribers",
        "Email_Subscribers_",
        ["email-subscribers", "email-subscribers-advanced-form", "email-subscribers-form"]
      ],

      // Convert Pro
      ["convertpro", "Convert_Plug", []],

      // Newsletter Popup
      ["newsletter-popup", "NLP_", []],

      // Ninja Popups
      ["ninja-popups", "NinjaPopups", []]
    ]);

    // Remove email subscription actions
    parent::removeActions([
      ["wp_footer", "addModalPopupHtmlToWpFooter", 10],
      ["wp_enqueue_scripts", "email_subscription_popup_load_styles_and_js", 10],
      ["wp_ajax_getEmailTemplate", "getEmailTemplate", 10],
      ["widgets_init", "nksnewslettersubscriberSet", 10],
      ["wp_ajax_store_email", "store_email_callback", 10],
      ["wp_ajax_nopriv_store_email", "store_email_callback", 10]
    ]);

    // Enqueue CSS to hide popups
    if (!is_admin()) {
      wp_enqueue_style(
        "zero-ad-marketing-dialogs",
        ZEROAD_PLUGIN_URL . "assets/css/marketing-dialogs.css",
        [],
        ZEROAD_VERSION
      );
    }
  }

  public static function outputBufferCallback(string $html): string
  {
    // Remove popup scripts and modal elements from HTML
    return parent::runReplacements($html, [
      // Remove popup/modal scripts (limit backtracking)
      '#<script[^>]{0,500}(src=[\'"][^\'"]{0,500}(optinmonster|popup-maker|thrive|hubspot|wpforms-popup|convertflow|mailchimp|newsletter)[^\'"]{0,200}[\'"])[^>]{0,200}>(?:(?!</script>).){0,10000}</script>#is',

      // Remove popup containers (limit size)
      '#<(div|section|aside)[^>]{0,300}(class|id)\s*=\s*["\'][^"\']{0,200}(popup|modal|marketing|optin|om-popup|pum|thrive-leads|hustle|newsletter|subscribe)[^"\']{0,200}["\'][^>]{0,300}>(?:(?!</\1>).){0,10000}</\1>#is',

      // Remove popup overlays
      '#<div[^>]{0,300}(class|id)\s*=\s*["\'][^"\']{0,200}(overlay|backdrop|modal-backdrop)[^"\']{0,200}["\'][^>]{0,300}>(?:(?!</div>).){0,5000}</div>#is',

      // Remove HubSpot chat/conversations widget
      '#<script[^>]{0,500}src=[\'"][^\'"]{0,500}hubspot[^\'"]{0,200}[\'"][^>]{0,200}>(?:(?!</script>).){0,2000}</script>#is'
    ]);
  }

  /**
   * Register plugin-specific overrides
   *
   * @param array $ctx Token context
   */
  public static function registerPluginOverrides(array $ctx): void
  {
    // OptinMonster
    if (class_exists("OMAPI")) {
      add_filter("optin_monster_api_is_enabled", "__return_false", 999);
    }

    // Popup Maker
    if (function_exists("pum_is_popup_enabled")) {
      add_filter("pum_popup_is_loadable", "__return_false", 999);
    }

    // Hustle
    if (class_exists("Hustle_Module_Model")) {
      add_filter("hustle_show_module", "__return_false", 999);
    }

    // Convert Pro / Convert Plus
    if (function_exists("cp_v2_popup_enabled")) {
      add_filter("cp_show_popup", "__return_false", 999);
    }

    // Thrive Leads
    if (class_exists("Thrive_Leads")) {
      add_filter("tve_leads_should_display", "__return_false", 999);
    }
  }
}
