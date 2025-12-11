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
    // No ability to add a filter

    self::disablePlugins([
      // Ads For WP - https://github.com/ahmedkaludi/ads-for-wp
      ["ads-for-wp", "adsforwp_", ["adsforwp", "adsforwp-group"]],

      // Ad Inserter - https://github.com/wp-plugins/ad-inserter
      [
        "ad-inserter",
        "ai_",
        ["ai_pre_do_shortcode_tag", "ai_clean_url", "ai_load_textdomain_mofile", "ai_plugin_action_links"]
      ],

      // Advanced Ads - https://github.com/wp-plugins/advanced-ads
      ["advanced-ads", "Advanced_Ads_", ["the_ad", "the_ad_group", "the_ad_placement"]],

      // WP Quads - https://github.com/wpquads/quick-adsense-reloaded
      ["quick-adsense-reloaded", "quads_", ["quads_ad", "quads", "the_ad", "the_ad_placement"]],

      // AdRotate - https://github.com/wp-plugins/adrotate
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
      ["corner-ad", "corner_ad_", ["corner-ad"]]
    ]);

    // Google Site Kit Ads
    add_filter("googlesitekit_adsense_tag_blocked", "__return_true", 999);
    add_filter("googlesitekit_adsense_tag_amp_blocked", "__return_true", 999);
  }

  public static function registerPluginOverrides(): void
  {
    // AdsForWP
    if (class_exists("Adsforwp_Output_Functions")) {
      // Example: remove content filters
      // You need to replace 'adsforwp_insert_ads' and priority with the actual AdsforWP hook
      remove_filter("the_content", ["Adsforwp_Output_Functions", "adsforwp_display_ads"]);
    }
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

    // Remove/neutralize ad elements and common ad scripts

    // Remove typical ad iframe/script tags (adsbygoogle, googlesyndication, doubleclick, adrotate, advanced-ads)
    $workHtml = preg_replace(
      "#<script[^>]*(adsbygoogle|googlesyndication|doubleclick|adservice|adrotate|advanced-ads|adinsert|ad-inserter)[^>]*>.*?</script>#is",
      "",
      $workHtml
    );
    $workHtml = preg_replace(
      '#<iframe[^>]*src=[\'"][^\'"]*(ads|doubleclick|googlesyndication)[^\'"]*[\'"][^>]*>.*?</iframe>#is',
      "",
      $workHtml
    );
    // Remove elements with class/id containing 'ad', 'ads', 'advert'
    $workHtml = preg_replace(
      '#<(div|section|aside)[^>]*(class|id)\s*=\s*["\'][^"\']*(\bads?\b|\badvert|\bad-inserter|\badvanced-ads\b)[^"\']*["\'][^>]*>.*?</(div|section|aside)>#is',
      "",
      $workHtml
    );
    // Hide inline Google ad elements e.g. <ins class="adsbygoogle"> ... </ins>
    $workHtml = preg_replace("#<ins[^>]*(class|id)[^>]*adsbygoogle[^>]*>.*?</ins>#is", "", $workHtml);
    // Inline CSS: remove .ad related selectors by injecting a small style to hide anything left with ad-related classes/ids
    $hideAdCss =
      '<style data-zeroad> .ad, .ads, [id*="ad-"], [class*="ad-"], .advert, .advertisement, .advanced-ads { display:none !important; visibility:hidden !important; height:0 !important; }</style>';
    $workHtml = parent::injectIntoHead($workHtml, $hideAdCss);

    return $workHtml;
  }
}
