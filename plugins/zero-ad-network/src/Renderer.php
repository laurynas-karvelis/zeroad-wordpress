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

  const FEATURE_ACTION_CLASSES = [
    Advertisements::class,
    CookieConsent::class,
    MarketingDialogs::class,
    ContentPaywalls::class,
    SubscriptionAccess::class
  ];

  /**
   * Set the Site instance
   */
  public function site(?Site $site): void
  {
    $this->site = $site;
  }

  /**
   * Set plugin options
   */
  public function options(array $options): void
  {
    $this->options = $options;
  }

  /**
   * Register WordPress hooks
   */
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

  /**
   * Send the server header if configured to do so
   */
  public function maybeSendHeader(): void
  {
    if (!isset($this->site) || is_admin() || ($this->options["output_method"] ?? "header") !== "header") {
      return;
    }

    // Check if headers already sent
    if (headers_sent()) {
      $this->debugLog("Headers already sent, cannot add server header");
      return;
    }

    header("{$this->site->SERVER_HEADER_NAME}: {$this->site->SERVER_HEADER_VALUE}", true);
    $this->debugLog("Server header sent: {$this->site->SERVER_HEADER_NAME}");
  }

  /**
   * Inject meta tag if configured to do so
   */
  public function maybeInjectMetaTag(): void
  {
    if (!isset($this->site) || is_admin() || ($this->options["output_method"] ?? "header") !== "meta") {
      return;
    }

    $name = esc_attr($this->site->SERVER_HEADER_NAME);
    $value = esc_attr($this->site->SERVER_HEADER_VALUE);

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables already escaped above
    echo sprintf('<meta name="%s" content="%s" data-zeroad="server-identifier" />' . "\n", $name, $value);

    $this->debugLog("Meta tag injected: {$name}");
  }

  /**
   * Parse and validate the client token from the request header
   */
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
        $this->debugLog("No client token header found: {$headerName}");
        return;
      }

      // Parse and verify the signed token
      $this->tokenContext = $this->site->parseClientToken($headerValue);

      // Make token context available globally
      $GLOBALS["zeroad_token_context"] = $this->tokenContext;

      $this->debugLog("Client token parsed successfully: " . json_encode($this->tokenContext));
    } catch (\Throwable $e) {
      $this->debugLog("Token parsing failed: " . $e->getMessage());

      // Set empty context so we know parsing was attempted
      $this->tokenContext = [];
      $GLOBALS["zeroad_token_context"] = [];
    }
  }

  /**
   * Register plugin-specific overrides (e.g., membership plugins, cache plugins)
   */
  public function registerPluginOverrides(): void
  {
    if (empty($this->tokenContext) || is_admin()) {
      return;
    }

    foreach (self::FEATURE_ACTION_CLASSES as $Class) {
      if ($Class::enabled($this->tokenContext)) {
        if (method_exists($Class, "registerPluginOverrides")) {
          $Class::registerPluginOverrides($this->tokenContext);
          $this->debugLog("Registered plugin overrides for: " . $Class);
        }
      }
    }

    // Register cache interceptor
    CacheInterceptor::registerPluginOverrides($this->tokenContext);
  }

  /**
   * Toggle features based on parsed token context
   */
  public function maybeToggleFeatures(): void
  {
    if (empty($this->tokenContext) || is_admin()) {
      return;
    }

    $this->enabledFeatureClasses = [];

    foreach (self::FEATURE_ACTION_CLASSES as $Class) {
      if ($Class::enabled($this->tokenContext)) {
        $this->enabledFeatureClasses[] = $Class;
        $Class::run();

        $this->debugLog("Feature enabled: " . $Class);
      }
    }

    // Store enabled features globally for use in output buffer
    $GLOBALS["zeroad_enabled_features"] = $this->enabledFeatureClasses;
  }

  /**
   * Start output buffering to post-process HTML
   */
  public function maybeStartOutputBuffer(): void
  {
    // Only buffer if token parsing happened
    if (!isset($this->tokenContext) || is_admin()) {
      return;
    }

    // Skip buffering for AJAX and JSON requests
    if (wp_doing_ajax() || $this->isJsonRequest()) {
      $this->debugLog("Skipping output buffer for AJAX/JSON request");
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

    $this->debugLog("Output buffer started at level {$this->bufferLevel}");
  }

  /**
   * End output buffering and flush
   */
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

    $this->debugLog("Output buffer ended");
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
        $this->debugLog("Output buffer callback failed for {$Class}: " . $e->getMessage());
      }
    }

    $processTime = round((microtime(true) - $startTime) * 1000, 2);
    $newLength = strlen($html);
    $reduction = $originalLength - $newLength;

    $this->debugLog(
      "HTML processed in {$processTime}ms. Size change: {$reduction} bytes (" .
        round(($reduction / $originalLength) * 100, 2) .
        "%)"
    );

    return $html;
  }

  /**
   * Get a server header value safely
   */
  private function getServerHeader(string $name): ?string
  {
    // Convert header name to server var format (e.g., X-Better-Web => HTTP_X_BETTER_WEB)
    $serverKey = "HTTP_" . str_replace("-", "_", strtoupper($name));

    if (!isset($_SERVER[$serverKey])) {
      return null;
    }

    // Sanitize and return
    return sanitize_text_field(wp_unslash($_SERVER[$serverKey]));
  }

  /**
   * Check if current request expects JSON response
   */
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

  /**
   * Debug logging helper
   */
  private function debugLog(string $message): void
  {
    if (
      !empty($this->options["debug_mode"]) &&
      defined("WP_DEBUG") &&
      WP_DEBUG &&
      defined("WP_DEBUG_LOG") &&
      WP_DEBUG_LOG
    ) {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Conditional debug logging
      error_log("[Zero Ad Network - Renderer] " . $message);
    }
  }
}
