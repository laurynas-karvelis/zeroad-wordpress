<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\WP\Actions\Action;

class Advertisements extends Action
{
  public static function enabled(array $ctx): bool
  {
    return !empty($ctx["HIDE_ADVERTISEMENTS"]);
  }

  public static function run(): void
  {
    // Disable ad plugins
    self::disablePlugins([
      // Ads For WP
      ["ads-for-wp", "adsforwp_", ["adsforwp", "adsforwp-group"]],

      // Ad Inserter
      [
        "ad-inserter",
        "ai_",
        ["ai_pre_do_shortcode_tag", "ai_clean_url", "ai_load_textdomain_mofile", "ai_plugin_action_links"]
      ],

      // Advanced Ads
      ["advanced-ads", "Advanced_Ads_", ["the_ad", "the_ad_group", "the_ad_placement"]],

      // WP Quads
      ["quick-adsense-reloaded", "quads_", ["quads_ad", "quads", "the_ad", "the_ad_placement"]],

      // AdRotate
      ["adrotate", "adrotate_", ["adrotate"]],

      // Raptive Ads
      ["cmb2", "AdThrive_Ads", ["adthrive-in-post-video-player"]],

      // Advanced Ads – Google AdSense In-feed Placement
      ["advanced-ads-adsense-in-feed", "AdvancedAds", []],

      // AdWidget
      [null, "AdWidget_", []],

      // WP AdCenter
      ["wpadcenter", "Wpadcenter", ["wpadcenter_ad", "wpadcenter_adgroup", "wpadcenter_random_ad"]],

      // Corner Ad
      ["corner-ad", "corner_ad_", ["corner-ad"]],

      // Ad Injection
      ["ad-injection", "AdInjection", []],

      // Simple Ads Manager
      ["simple-ads-manager", "Sam_", []],

      // BuddyPress Ads
      ["buddypress-ads", "BP_Ads", []]
    ]);

    // Block Google Site Kit ads
    if (has_filter("googlesitekit_adsense_tag_blocked")) {
      add_filter("googlesitekit_adsense_tag_blocked", "__return_true", 999);
      add_filter("googlesitekit_adsense_tag_amp_blocked", "__return_true", 999);
    }

    // Enqueue CSS to hide ad containers
    if (!is_admin()) {
      wp_enqueue_style(
        "zero-ad-advertisements",
        ZEROAD_PLUGIN_URL . "assets/css/advertisements.css",
        [],
        ZEROAD_VERSION
      );
    }
  }

  public static function outputBufferCallback(string $html): string
  {
    // Remove ad scripts and elements from HTML
    return parent::runReplacements($html, [
      // Remove ad scripts (limit backtracking with quantifiers)
      "#<script[^>]{0,500}(adsbygoogle|googlesyndication|doubleclick|adservice|adrotate|advanced-ads|adinsert|ad-inserter)[^>]{0,200}>.*?</script>#is",

      // Remove ad iframes (limit backtracking)
      '#<iframe[^>]{0,500}src=[\'"][^\'"]{0,500}(ads|doubleclick|googlesyndication)[^\'"]{0,200}[\'"][^>]{0,200}>.*?</iframe>#is',

      // Remove ad containers by class/id (limit size to 5000 chars)
      '#<(div|section|aside)[^>]{0,300}(class|id)\s*=\s*["\'][^"\']{0,200}(\bads?\b|\badvert|\bad-inserter|\badvanced-ads\b)[^"\']{0,200}["\'][^>]{0,200}>(?:(?!</\1>).){0,5000}</\1>#is',

      // Remove Google AdSense ins elements
      "#<ins[^>]{0,300}(class|id)[^>]{0,200}adsbygoogle[^>]{0,200}>(?:(?!</ins>).){0,2000}</ins>#is",

      // Remove ad-related meta tags
      '#<meta[^>]{0,300}(name|property)=["\']?[^"\']{0,100}(ad|advertisement|adsense)[^"\']{0,100}["\']?[^>]{0,200}>#is'
    ]);
  }

  public static function registerPluginOverrides(array $ctx): void
  {
    // AdsForWP
    if (class_exists("Adsforwp_Output_Functions")) {
      remove_filter("the_content", ["Adsforwp_Output_Functions", "adsforwp_display_ads"], 10);
    }

    // Advanced Ads
    if (class_exists("Advanced_Ads")) {
      add_filter("advanced-ads-can-display", "__return_false", 999);
    }

    // Ad Inserter
    if (defined("AD_INSERTER_NAME")) {
      add_filter("ai_block_insertion_enabled", "__return_false", 999);
    }
  }
}
