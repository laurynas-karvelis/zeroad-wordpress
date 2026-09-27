<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
    exit();
}

class Settings
{
    const OPTION_KEY = "zeroad_token_options";

    /** A Publisher ID is the `zapub_` prefix followed by exactly 24 alphanumerics. */
    const PUBLISHER_ID_PATTERN = '/^zapub_[A-Za-z0-9]{24}$/';
    const PUBLISHER_ID_LENGTH = 30;

    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function register(): void
    {
        register_setting(self::OPTION_KEY, self::OPTION_KEY, [
            "sanitize_callback" => [$this, "validate"],
            "default" => self::getDefaults()
        ]);

        $this->registerMainSection();
        $this->registerCacheSection();
    }

    public static function getDefaults(): array
    {
        return [
            "enabled" => false,
            "publisher_id" => "",
            "output_method" => "header",
            "cache_enabled" => true,
            "cache_ttl" => ZEROAD_DEFAULT_CACHE_TTL,
            "cache_prefix" => "zeroad:"
        ];
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    private function registerMainSection(): void
    {
        add_settings_section(
            "zeroad_main",
            __("Configuration", "zero-ad-network"),
            [$this, "renderMainSectionDescription"],
            self::OPTION_KEY
        );

        $this->registerMainFields();
    }

    private function registerMainFields(): void
    {
        $fields = [
            "enabled" => ["renderEnabled", __("Enable Plugin", "zero-ad-network")],
            "publisher_id" => ["renderPublisherId", __("Publisher ID", "zero-ad-network")],
            "output_method" => ["renderOutputMethod", __("Publisher Header Method", "zero-ad-network")]
        ];

        foreach ($fields as $field => $entry) {
            add_settings_field($field, $entry[1], [$this, $entry[0]], self::OPTION_KEY, "zeroad_main", ["label_for" => "zeroad-" . $field]);
        }
    }

    private function registerCacheSection(): void
    {
        add_settings_section(
            "zeroad_cache",
            __("Performance & Caching", "zero-ad-network"),
            [$this, "renderCacheSectionDescription"],
            self::OPTION_KEY
        );

        $fields = [
            "cache_enabled" => ["renderCacheEnabled", __("Enable APCu Caching", "zero-ad-network")],
            "cache_ttl" => ["renderCacheTtl", __("Cache TTL (seconds)", "zero-ad-network")],
            "cache_prefix" => ["renderCachePrefix", __("Cache Key Prefix", "zero-ad-network")]
        ];

        foreach ($fields as $field => $entry) {
            add_settings_field($field, $entry[1], [$this, $entry[0]], self::OPTION_KEY, "zeroad_cache", ["label_for" => "zeroad-" . $field]);
        }
    }

    // ========================================================================
    // Section Descriptions
    // ========================================================================

    /**
     * Render main section description.
     */
    public function renderMainSectionDescription(): void
    {
        echo '<p class="description">';
        esc_html_e(
            "Connect this website to your Zero Ad Network publisher account. Verified subscribers get a clean page. If you sell access, include posts and pages with their Freedom access box, and check that your membership plugin opens them.",
            "zero-ad-network"
        );
        echo "</p>";
    }

    /**
     * Render cache section description.
     */
    public function renderCacheSectionDescription(): void
    {
        echo '<p class="description">';
        esc_html_e(
            "Reuse token verification results with APCu, so returning subscribers are checked faster. This does not cache your pages.",
            "zero-ad-network"
        );
        echo "</p>";

        $apcuAvailable = \ZeroAd\Token\ApcuResultCache::isSupported();

        echo '<p class="description"><strong>';
        echo esc_html($apcuAvailable
            ? __("APCu is available on this server.", "zero-ad-network")
            : __("APCu is unavailable. Verification still works; ask your host to enable it if needed.", "zero-ad-network"));
        echo "</strong></p>";
    }

    // ========================================================================
    // Field Renderers
    // ========================================================================

    /**
     * Render enabled field.
     */
    public function renderEnabled(): void
    {
        $enabled = !empty($this->options["enabled"]); ?>
        <label>
            <input type="checkbox"
                   id="zeroad-enabled"
                   aria-describedby="zeroad-enabled-help"
                   name="<?php echo esc_attr(self::OPTION_KEY); ?>[enabled]"
                   value="1"
                   <?php checked($enabled, true); ?>>
            <?php esc_html_e("Activate Zero Ad Network integration on this website", "zero-ad-network"); ?>
        </label>
        <p class="description" id="zeroad-enabled-help">
            <?php esc_html_e(
                "When on, the plugin checks each visitor's membership token, and gives verified subscribers a clean page.",
                "zero-ad-network"
            ); ?>
        </p>
        <?php
    }

    /**
     * Render publisher_id field.
     */
    public function renderPublisherId(): void
    {
        $value = $this->options["publisher_id"] ?? ""; ?>
        <input type="text"
               id="zeroad-publisher_id"
               aria-describedby="zeroad-publisher_id-help"
               name="<?php echo esc_attr(self::OPTION_KEY); ?>[publisher_id]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text code"
               maxlength="<?php echo esc_attr((string) self::PUBLISHER_ID_LENGTH); ?>"
               placeholder="<?php esc_attr_e("zapub_XXXXXXXXXXXXXXXXXXXXXXXX", "zero-ad-network"); ?>">
        <p class="description" id="zeroad-publisher_id-help">
            <?php printf(
                wp_kses(
                    /* translators: %s: URL to Zero Ad Network dashboard */
                    __(
                        'Copy it from <a href="%s" target="_blank" rel="noopener noreferrer">Websites &amp; creators</a> in your Zero Ad Network account. It starts with <code>zapub_</code>, and is the same for all your websites.',
                        "zero-ad-network"
                    ),
                    ["a" => ["href" => [], "target" => [], "rel" => []], "code" => []]
                ),
                esc_url("https://zeroad.network/sites#publisher-id")
            ); ?>
        </p>
        <?php
    }

    /**
     * Render output_method field.
     */
    public function renderOutputMethod(): void
    {
        $value = $this->options["output_method"] ?? "header"; ?>
        <select id="zeroad-output_method"
               aria-describedby="zeroad-output_method-help"
               name="<?php echo esc_attr(self::OPTION_KEY); ?>[output_method]">
            <option value="header" <?php selected($value, "header"); ?>>
                <?php esc_html_e("HTTP Response Header", "zero-ad-network"); ?>
            </option>
            <option value="meta" <?php selected($value, "meta"); ?>>
                <?php esc_html_e("HTML Meta Tag", "zero-ad-network"); ?>
            </option>
        </select>
        <p class="description" id="zeroad-output_method-help">
            <?php esc_html_e(
                'How the plugin announces your Publisher ID to the browser extension, as "Better-Web-Publisher". Choose HTML Meta Tag only if your host requires it.',
                "zero-ad-network"
            ); ?>
        </p>
        <?php
    }

    /**
     * Render cache_enabled field.
     */
    public function renderCacheEnabled(): void
    {
        $enabled = !empty($this->options["cache_enabled"]); ?>
        <label>
            <input type="checkbox" id="zeroad-cache_enabled"
                   aria-describedby="zeroad-cache_enabled-help"
                   name="<?php echo esc_attr(self::OPTION_KEY); ?>[cache_enabled]"
                   value="1" <?php checked($enabled, true); ?>>
            <?php esc_html_e("Reuse verification results with APCu when available", "zero-ad-network"); ?>
        </label>
        <p class="description" id="zeroad-cache_enabled-help"><?php esc_html_e("Takes effect when your host has APCu. Without it, tokens are verified on each request. This never caches pages.", "zero-ad-network"); ?></p>
        <?php
    }

    /**
     * Render cache_ttl field.
     */
    public function renderCacheTtl(): void
    {
        $value = intval($this->options["cache_ttl"] ?? ZEROAD_DEFAULT_CACHE_TTL); ?>
        <input type="number"
               id="zeroad-cache_ttl"
               aria-describedby="zeroad-cache_ttl-help"
               name="<?php echo esc_attr(self::OPTION_KEY); ?>[cache_ttl]"
               value="<?php echo esc_attr((string) $value); ?>"
               min="1"
               max="86400"
               step="1"
               class="small-text">
        <span><?php esc_html_e("seconds", "zero-ad-network"); ?></span>
        <p class="description" id="zeroad-cache_ttl-help">
            <?php esc_html_e(
                "How long to reuse a verification result. Default: 3600 seconds (1 hour). Maximum: 86400 seconds (24 hours). A cached result never outlives the token's expiry.",
                "zero-ad-network"
            ); ?>
        </p>
        <?php
    }

    /**
     * Render cache_prefix field.
     */
    public function renderCachePrefix(): void
    {
        $value = $this->options["cache_prefix"] ?? "zeroad:"; ?>
        <input type="text"
               id="zeroad-cache_prefix"
               aria-describedby="zeroad-cache_prefix-help"
               name="<?php echo esc_attr(self::OPTION_KEY); ?>[cache_prefix]"
               value="<?php echo esc_attr($value); ?>"
               maxlength="50"
               class="regular-text code"
               placeholder="zeroad:">
        <p class="description" id="zeroad-cache_prefix-help">
            <?php esc_html_e(
                "Keeps these cache keys apart from other plugins. Change it only if several WordPress websites share one APCu.",
                "zero-ad-network"
            ); ?>
            <br>
            <strong><?php esc_html_e("Example:", "zero-ad-network"); ?></strong>
            <code>zeroad:wp1:</code>, <code>zeroad:wp2:</code>
            <?php esc_html_e("for different websites", "zero-ad-network"); ?>
        </p>
        <?php
    }

    // ========================================================================
    // Validation
    // ========================================================================

    /**
     * Validate and sanitize settings input.
     *
     * @param array $input Raw input from settings form.
     * @return array Validated and sanitized output.
     */
    public function validate($input): array
    {
        $output = [];
        $errors = [];

        // Verify nonce (WordPress handles this automatically, but we document it).
        // The Settings API automatically verifies the nonce before calling this callback.

        // Enabled.
        $output["enabled"] = !empty($input["enabled"]) ? 1 : 0;

        // Publisher ID validation.
        $publisher_id = isset($input["publisher_id"]) ? trim(sanitize_text_field($input["publisher_id"])) : "";

        if (!empty($publisher_id)) {
            $publisher_id = $this->validatePublisherId($publisher_id, $errors);
        }

        $output["publisher_id"] = $publisher_id;

        // Require a Publisher ID once the plugin is switched on.
        if (empty($output["publisher_id"]) && !empty($output["enabled"])) {
            $errors[] = __(
                "Enter your Publisher ID from the Zero Ad Network dashboard before enabling the plugin.",
                "zero-ad-network"
            );
        }

        // Output method.
        $output["output_method"] = $this->validateOutputMethod($input["output_method"] ?? "header");

        // Cache settings.
        $output["cache_enabled"] = !empty($input["cache_enabled"]) ? 1 : 0;
        $output["cache_ttl"] = $this->validateCacheTtl($input["cache_ttl"] ?? ZEROAD_DEFAULT_CACHE_TTL);
        $output["cache_prefix"] = $this->validateCachePrefix($input["cache_prefix"] ?? "zeroad:");

        // Display errors.
        foreach ($errors as $error) {
            add_settings_error(self::OPTION_KEY, "validation_error_" . md5($error), $error, "error");
        }

        return $output;
    }

    private function validatePublisherId(string $publisher_id, array &$errors): string
    {
        // A Publisher ID is `zapub_` followed by exactly 24 alphanumerics - the exact shape the token
        // SDK's Publisher::create() accepts, checked here so a typo is a friendly settings error rather
        // than a caught exception and a silently inactive plugin.
        if (!preg_match(self::PUBLISHER_ID_PATTERN, $publisher_id)) {
            $errors[] = __(
                'Publisher ID must be "zapub_" followed by 24 letters or numbers. Copy it exactly from the Zero Ad Network dashboard.',
                "zero-ad-network"
            );

            return "";
        }

        return $publisher_id;
    }

    private function validateOutputMethod(string $method): string
    {
        $valid_methods = ["header", "meta"];

        return in_array($method, $valid_methods, true) ? $method : "header";
    }

    private function validateCacheTtl($ttl): int
    {
        $ttl = intval($ttl);

        return max(1, min(86400, $ttl));
    }

    private function validateCachePrefix(string $prefix): string
    {
        $prefix = trim(sanitize_text_field($prefix));

        if (empty($prefix)) {
            return "zeroad:";
        }

        // Only allow alphanumeric, underscore, hyphen, and colon.
        if (!preg_match('/^[a-z0-9_:-]+$/i', $prefix)) {
            add_settings_error(
                self::OPTION_KEY,
                "cache_prefix_invalid",
                __("Cache prefix contains invalid characters. Using default.", "zero-ad-network"),
                "warning"
            );

            return "zeroad:";
        }

        // Ensure it ends with colon.
        if (substr($prefix, -1) !== ":") {
            $prefix .= ":";
        }

        return $prefix;
    }
}
