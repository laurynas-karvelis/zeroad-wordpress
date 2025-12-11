<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\Token\Site;
use ZeroAd\Token\Constants;

use ZeroAd\WP\Config;
use ZeroAd\WP\Actions\Advertisements;
use ZeroAd\WP\Actions\ContentPaywalls;
use ZeroAd\WP\Actions\CookieConsent;
use ZeroAd\WP\Actions\MarketingDialogs;
use ZeroAd\WP\Actions\SubscriptionAccess;

class Renderer
{
  private $options;
  private $site;
  private $tokenContext;

  const FEATURE_ACTION_CLASSES = [
    Advertisements::class,
    CookieConsent::class,
    MarketingDialogs::class,
    ContentPaywalls::class,
    SubscriptionAccess::class
  ];

  public function site(Site $site)
  {
    $this->site = $site;
  }

  public function options(array $options)
  {
    $this->options = $options;
  }

  public function run(): void
  {
    add_action("wp_head", [$this, "maybeInjectMetaTag"]);
    add_action("send_headers", [$this, "maybeSendHeader"], 20);

    add_action("init", [$this, "parseClientToken"], 2);
    add_action("init", [$this, "registerPluginOverrides"], 3);
    add_action("template_redirect", [$this, "maybeToggleFeatures"], 2);

    // Start output buffering after template redirect so we can post-process HTML
    add_action("template_redirect", [$this, "maybeStartOutputBuffer"], 5);
    // add_filter("the_content", [$this, "prependTokenContextToContent"]); // For debug purposes only
  }

  // public function prependTokenContextToContent($content)
  // {
  //   if (!empty($this->tokenContext)) {
  //     $debug = '<pre style="background:#eee;padding:8px;">' . esc_html(print_r($this->tokenContext, true)) . "</pre>";
  //     $content = $debug . $content;
  //   }

  //   return $content;
  // }

  public function maybeSendHeader(): void
  {
    if (!isset($this->site) || is_admin() || ($this->options["output_method"] ?? "header") !== "header") {
      return;
    }

    header("{$this->site->SERVER_HEADER_NAME}: {$this->site->SERVER_HEADER_VALUE}");
  }

  public function maybeInjectMetaTag(): void
  {
    if (!isset($this->site) || is_admin() || ($this->options["output_method"] ?? "header") !== "meta") {
      return;
    }
    $value = esc_attr($this->site->SERVER_HEADER_VALUE);
    echo "<meta name='" . esc_html($this->site->SERVER_HEADER_NAME) . "' content='" . esc_html($value) . "' />\n";
  }

  public function parseClientToken(): void
  {
    if (!isset($this->site) || is_admin()) {
      return;
    }

    try {
      // Parse & verify the signed token
      $headerValue = !empty($_SERVER[$this->site->CLIENT_HEADER_NAME])
        ? sanitize_text_field(wp_unslash($_SERVER[$this->site->CLIENT_HEADER_NAME]))
        : null;
      $this->tokenContext = $this->site->parseClientToken($headerValue);

      // Make `tokenContext` available globally to everyone
      $GLOBALS["zeroad_token_context"] = $this->tokenContext;
    } catch (\Throwable $e) {
      // give up
    }
  }

  public function maybeToggleFeatures(): void
  {
    if (empty($this->tokenContext) || is_admin()) {
      return;
    }

    foreach (self::FEATURE_ACTION_CLASSES as $Class) {
      if ($Class::enabled($this->tokenContext)) {
        $Class::run();
      }
    }
  }

  public function maybeStartOutputBuffer(): void
  {
    // Only buffer if token parsing happened
    if (!isset($this->tokenContext) || is_admin()) {
      return;
    }

    // Only buffer for front-end HTML responses
    if (wp_doing_ajax() || wp_is_json_request()) {
      return;
    }

    ob_start([$this, "outputBufferCallback"]);
  }

  /**
   * Output buffer callback — modify HTML before sending to client.
   */
  public function outputBufferCallback(string $html): string
  {
    if (empty($this->tokenContext)) {
      return $html;
    }

    foreach (self::FEATURE_ACTION_CLASSES as $Class) {
      if ($Class::enabled($this->tokenContext)) {
        $html = $Class::outputBufferCallback($html);
      }
    }

    // Return processed HTML
    return $html;
  }

  public function registerPluginOverrides(): void
  {
    if (empty($this->tokenContext) || is_admin()) {
      return;
    }

    foreach (self::FEATURE_ACTION_CLASSES as $Class) {
      if ($Class::enabled($this->tokenContext)) {
        if (method_exists($Class, "registerPluginOverrides")) {
          $Class::registerPluginOverrides();
        }
      }
    }

    CacheInterceptor::registerPluginOverrides($this->tokenContext);
  }
}

if (!function_exists("wp_is_json_request")) {
  function wp_is_json_request(): bool
  {
    // Check if the request is for REST API
    if (defined("REST_REQUEST") && REST_REQUEST) {
      return true;
    }

    // Check headers
    $accept = isset($_SERVER["HTTP_ACCEPT"]) ? sanitize_text_field(wp_unslash($_SERVER["HTTP_ACCEPT"])) : "";
    $content_type = isset($_SERVER["CONTENT_TYPE"]) ? sanitize_text_field(wp_unslash($_SERVER["CONTENT_TYPE"])) : "";

    if (strpos($accept, "application/json") !== false || strpos($content_type, "application/json") !== false) {
      return true;
    }

    return false;
  }
}
