<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
    exit();
}

use ZeroAd\Token\Publisher;
use ZeroAd\WP\Actions\Advertisements;
use ZeroAd\WP\Actions\ContentPaywalls;
use ZeroAd\WP\Actions\CookieConsent;
use ZeroAd\WP\Actions\MarketingDialogs;
use ZeroAd\WP\Actions\SubscriptionAccess;

class Renderer
{
    private $options;

    /** @var Publisher|null */
    private $publisher;

    /** @var bool Whether this visitor holds a live Zero Ad Network (Freedom) subscription. */
    private $isSubscriber = false;

    /** @var array<string,bool> The action flags for this request, all on for a subscriber, empty otherwise. */
    private $tokenContext = [];

    private $bufferStarted = false;
    private $bufferLevel = 0;
    private $enabledFeatureClasses = [];

    /**
     * The single Freedom plan entitles a subscriber to the whole clean experience at once, so every
     * action runs for a subscriber. This is the internal context the {@see Actions\Action} classes read.
     */
    public const SUBSCRIBER_CONTEXT = [
        "HIDE_ADVERTISEMENTS" => true,
        "HIDE_COOKIE_CONSENT_SCREEN" => true,
        "HIDE_MARKETING_DIALOGS" => true,
        "DISABLE_NON_FUNCTIONAL_TRACKING" => true,
        "DISABLE_CONTENT_PAYWALL" => true,
        "ENABLE_SUBSCRIPTION_ACCESS" => true,
    ];

    public static function getFeatureActionClasses(): array
    {
        return [
            Advertisements::class,
            CookieConsent::class,
            MarketingDialogs::class,
            ContentPaywalls::class,
            SubscriptionAccess::class,
        ];
    }

    public function setPublisher(?Publisher $publisher): void
    {
        $this->publisher = $publisher;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function run(): void
    {
        // Announce participation to the extension.
        add_action("send_headers", [$this, "maybeSendHeader"], 20);
        add_action("wp_head", [$this, "maybeInjectMetaTag"], 1);

        // Verify the incoming subscriber token.
        add_action("init", [$this, "verifyToken"], 2);

        // Advertise the cache variant so page-cache plugins and CDNs keep subscriber and regular
        // versions apart. Runs for every visitor, subscriber or not.
        add_action("init", [$this, "registerCacheVariant"], 3);

        // Register plugin-specific overrides for subscribers.
        add_action("init", [$this, "registerPluginOverrides"], 4);

        // Toggle actions based on the verdict.
        add_action("template_redirect", [$this, "maybeToggleFeatures"], 2);

        // Start output buffering for HTML post-processing.
        add_action("template_redirect", [$this, "maybeStartOutputBuffer"], 5);
    }

    public function maybeSendHeader(): void
    {
        if ($this->publisher === null || is_admin() || ($this->options["output_method"] ?? "header") !== "header") {
            return;
        }

        if (headers_sent()) {
            return;
        }

        header("{$this->publisher->headerName}: {$this->publisher->headerValue}", true);
    }

    public function maybeInjectMetaTag(): void
    {
        if ($this->publisher === null || is_admin() || ($this->options["output_method"] ?? "header") !== "meta") {
            return;
        }

        $name = esc_attr($this->publisher->headerName);
        $value = esc_attr($this->publisher->headerValue);

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $name and $value are escaped above
        echo sprintf('<meta name="%s" content="%s" data-zeroad="publisher-identifier" />' . "\n", $name, $value);
    }

    public function verifyToken(): void
    {
        if ($this->publisher === null || is_admin()) {
            return;
        }

        try {
            $token = $this->getServerValue($this->publisher->tokenHeaderServerKey);
            $hostname = $this->getServerValue("HTTP_HOST");

            $result = $this->publisher->verify($token, $hostname);

            $this->isSubscriber = $result->subscriber;
            $this->tokenContext = $result->subscriber ? self::SUBSCRIBER_CONTEXT : [];
        } catch (\Throwable $e) {
            // A verification failure is a non-subscriber, never a fatal page error.
            $this->isSubscriber = false;
            $this->tokenContext = [];
        }

        // Actions that hook WordPress filters (e.g. password-protection bypass) read the verdict from a
        // global, since they run inside callbacks that receive no context of their own.
        $GLOBALS["zeroad_token_context"] = $this->tokenContext;
    }

    public function registerCacheVariant(): void
    {
        if ($this->publisher === null || !$this->isPageRequest()) {
            return;
        }

        CacheInterceptor::registerPluginOverrides($this->isSubscriber);
    }

    public function registerPluginOverrides(): void
    {
        if (empty($this->tokenContext) || !$this->isPageRequest()) {
            return;
        }

        foreach (self::getFeatureActionClasses() as $Class) {
            if ($Class::enabled($this->tokenContext)) {
                $Class::registerPluginOverrides($this->tokenContext);
            }
        }
    }

    public function maybeToggleFeatures(): void
    {
        if (empty($this->tokenContext) || !$this->isPageRequest()) {
            return;
        }

        $this->enabledFeatureClasses = [];

        foreach (self::getFeatureActionClasses() as $Class) {
            if ($Class::enabled($this->tokenContext)) {
                $this->enabledFeatureClasses[] = $Class;
                $Class::run();
            }
        }
    }

    public function maybeStartOutputBuffer(): void
    {
        if (empty($this->tokenContext) || !$this->isPageRequest()) {
            return;
        }

        if ($this->bufferStarted) {
            return;
        }

        $this->bufferLevel = ob_get_level();
        $this->bufferStarted = true;

        ob_start([$this, "outputBufferCallback"]);
        add_action("shutdown", [$this, "endBuffer"], 999);
    }

    public function endBuffer(): void
    {
        if (!$this->bufferStarted) {
            return;
        }

        $this->bufferStarted = false;

        while (ob_get_level() > $this->bufferLevel) {
            ob_end_flush();
        }
    }

    /** Output buffer callback - modify HTML before it is sent to the visitor. */
    public function outputBufferCallback(string $html): string
    {
        if (empty($this->tokenContext)) {
            return $html;
        }

        foreach ($this->enabledFeatureClasses as $Class) {
            try {
                $html = $Class::outputBufferCallback($html);
            } catch (\Throwable $e) {
                // A single misbehaving replacement must not take the page down.
            }
        }

        return $html;
    }

    /**
     * A normal front-end page render - the only place applying the clean experience makes sense.
     * Excludes wp-admin, AJAX, the REST API and any request negotiating JSON, so we never disable
     * plugins or rewrite output for a machine-readable response.
     */
    private function isPageRequest(): bool
    {
        return !is_admin() && !wp_doing_ajax() && !$this->isJsonRequest();
    }

    private function getServerValue(string $key): ?string
    {
        if (!isset($_SERVER[$key]) || !is_string($_SERVER[$key])) {
            return null;
        }

        return sanitize_text_field(wp_unslash($_SERVER[$key]));
    }

    private function isJsonRequest(): bool
    {
        if (defined("REST_REQUEST") && REST_REQUEST) {
            return true;
        }

        $accept = $this->getServerValue("HTTP_ACCEPT") ?? "";
        if (stripos($accept, "application/json") !== false) {
            return true;
        }

        $contentType = $this->getServerValue("HTTP_CONTENT_TYPE") ?? "";
        if (stripos($contentType, "application/json") !== false) {
            return true;
        }

        return false;
    }
}
