<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\Token\Site;
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
  private $bufferStarted = false;
  private $bufferLevel = 0;
  private $enabledFeatureClasses = [];

  public static function getFeatureActionClasses()
  {
    return [
      Advertisements::class,
      CookieConsent::class,
      MarketingDialogs::class,
      ContentPaywalls::class,
      SubscriptionAccess::class
    ];
  }

  public function setSite(?Site $site): void
  {
    $this->site = $site;
  }

  public function setOptions(array $options): void
  {
    $this->options = $options;
  }

  public function run(): void
  {
    // Output the server header/meta tag
    add_action("send_headers", [$this, "maybeSendHeader"], 20);
    add_action("wp_head", [$this, "maybeInjectMetaTag"], 1);

    // Parse incoming client token
    add_action("init", [$this, "parseClientToken"], 2);

    // Register plugin-specific overrides
    add_action("init", [$this, "registerPluginOverrides"], 3);

    // Toggle features based on token
    add_action("template_redirect", [$this, "maybeToggleFeatures"], 2);

    // Start output buffering for HTML post-processing
    add_action("template_redirect", [$this, "maybeStartOutputBuffer"], 5);
  }

  public function maybeSendHeader(): void
  {
    if (!isset($this->site) || is_admin() || ($this->options["output_method"] ?? "header") !== "header") {
      return;
    }

    // Check if headers already sent
    if (headers_sent()) {
      return;
    }

    header("{$this->site->SERVER_HEADER_NAME}: {$this->site->SERVER_HEADER_VALUE}", true);
  }

  public function maybeInjectMetaTag(): void
  {
    if (!isset($this->site) || is_admin() || ($this->options["output_method"] ?? "header") !== "meta") {
      return;
    }

    $name = esc_attr($this->site->SERVER_HEADER_NAME);
    $value = esc_attr($this->site->SERVER_HEADER_VALUE);

    echo sprintf('<meta name="%s" content="%s" data-zeroad="server-identifier" />' . "\n", $name, $value);
  }

  public function parseClientToken(): void
  {
    if (!isset($this->site) || is_admin()) {
      return;
    }

    try {
      // Get the client header value
      $headerName = $this->site->CLIENT_HEADER_NAME;
      $headerValue = $this->getServerHeader($headerName);

      if ($headerValue === null) {
        return;
      }

      // Parse and verify the signed token
      $this->tokenContext = $this->site->parseClientToken($headerValue);
    } catch (\Throwable $e) {
      // Set empty context so we know parsing was attempted
      $this->tokenContext = [];
    }
  }

  public function registerPluginOverrides(): void
  {
    if (empty($this->tokenContext) || is_admin()) {
      return;
    }

    foreach (self::get_feature_action_classes() as $Class) {
      if ($Class::enabled($this->tokenContext)) {
        if (method_exists($Class, "registerPluginOverrides")) {
          $Class::registerPluginOverrides($this->tokenContext);
        }
      }
    }

    // Register cache interceptor
    CacheInterceptor::registerPluginOverrides($this->tokenContext);
  }

  public function maybeToggleFeatures(): void
  {
    if (empty($this->tokenContext) || is_admin()) {
      return;
    }

    $this->enabledFeatureClasses = [];

    foreach (self::get_feature_action_classes() as $Class) {
      if ($Class::enabled($this->tokenContext)) {
        $this->enabledFeatureClasses[] = $Class;
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

    // Skip buffering for AJAX and JSON requests
    if (wp_doing_ajax() || $this->isJsonRequest()) {
      return;
    }

    // Avoid double-buffering
    if ($this->bufferStarted) {
      return;
    }

    // Remember the buffer level before we start
    $this->bufferLevel = ob_get_level();
    $this->bufferStarted = true;

    ob_start([$this, "outputBufferCallback"]);
    add_action("shutdown", [$this, "endBuffer"], 999); // Run late to ensure content is flushed
  }

  public function endBuffer(): void
  {
    if (!$this->bufferStarted) {
      return;
    }

    $this->bufferStarted = false;

    // Flush all buffers we started
    while (ob_get_level() > $this->bufferLevel) {
      ob_end_flush();
    }
  }

  /**
   * Output buffer callback - modify HTML before sending to client
   */
  public function outputBufferCallback(string $html): string
  {
    if (empty($this->tokenContext)) {
      return $html;
    }

    $startTime = microtime(true);
    $originalLength = strlen($html);

    // Use cached enabled features
    $enabledClasses = $this->enabledFeatureClasses;

    foreach ($enabledClasses as $Class) {
      try {
        $html = $Class::outputBufferCallback($html);
      } catch (\Throwable $e) {
        // Ignore
      }
    }

    $processTime = round((microtime(true) - $startTime) * 1000, 2);
    $newLength = strlen($html);
    $reduction = $originalLength - $newLength;

    return $html;
  }

  private function getServerHeader(string $name): ?string
  {
    $serverKey = "HTTP_" . str_replace("-", "_", strtoupper($name));

    if (!isset($_SERVER[$serverKey]) || !is_string($_SERVER[$serverKey])) {
      return null;
    }

    return sanitize_text_field(wp_unslash($_SERVER[$serverKey]));
  }

  private function isJsonRequest(): bool
  {
    // Check if REST API request
    if (defined("REST_REQUEST") && REST_REQUEST) {
      return true;
    }

    // Check Accept header
    $accept = $this->getServerHeader("Accept") ?? "";
    if (stripos($accept, "application/json") !== false) {
      return true;
    }

    // Check Content-Type header
    $contentType = $this->getServerHeader("Content-Type") ?? "";
    if (stripos($contentType, "application/json") !== false) {
      return true;
    }

    return false;
  }
}
