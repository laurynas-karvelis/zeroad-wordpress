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
            add_settings_field($field, $entry[1], [$this, $entry[0]], self::OPTION_KEY, "zeroad_main");
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
            add_settings_field($field, $entry[1], [$this, $entry[0]], self::OPTION_KEY, "zeroad_cache");
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
            "Configure your Zero Ad Network partnership settings. Subscribers with a valid token get the full ad-free, tracker-free, paywall-free experience on your site automatically.",
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
            "Configure APCu token caching for improved performance. When enabled, validated tokens are cached for faster subsequent requests.",
            "zero-ad-network"
        );
        echo "</p>";

        // Show APCu status.
        $apcu_available = extension_loaded("apcu") && apcu_enabled();
        $notice_class = $apcu_available ? "notice-success" : "notice-warning";
        $icon = $apcu_available ? "✅" : "⚠️";
        $title = $apcu_available
            ? __("APCu Extension Available", "zero-ad-network")
            : __("APCu Extension Not Available", "zero-ad-network");

        printf(
            '<div class="notice %s inline" style="margin: 15px 0;"><p><strong>%s %s</strong><br>',
            esc_attr($notice_class),
            esc_html($icon),
            esc_html($title)
        );

        if ($apcu_available) {
            esc_html_e(
                "APCu is installed and ready. Enable caching below to share verified tokens across PHP workers, so a returning subscriber's token isn't re-verified on every request.",
                "zero-ad-network"
            );
        } else {
            esc_html_e(
                "The APCu PHP extension is not installed or enabled, so verified tokens can't be shared across requests. Verification still runs per request (about 0.09ms) - installing APCu simply avoids repeating it.",
                "zero-ad-network"
            );
        }

        echo "</p></div>";
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
                   name="<?php echo esc_attr(self::OPTION_KEY); ?>[enabled]"
                   value="1"
                   <?php checked($enabled, true); ?>>
            <?php esc_html_e("Activate Zero Ad Network integration on this site", "zero-ad-network"); ?>
        </label>
        <p class="description">
            <?php esc_html_e(
                "When enabled, the plugin verifies each visitor's subscriber token and applies the ad-free, clean experience for Zero Ad Network subscribers.",
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
               name="<?php echo esc_attr(self::OPTION_KEY); ?>[publisher_id]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text code"
               maxlength="<?php echo esc_attr((string) self::PUBLISHER_ID_LENGTH); ?>"
               placeholder="<?php esc_attr_e("zapub_XXXXXXXXXXXXXXXXXXXXXXXX", "zero-ad-network"); ?>">
        <p class="description">
            <?php printf(
                wp_kses(
                    /* translators: %s: URL to Zero Ad Network dashboard */
                    __(
                        'Your unique Publisher ID from the <a href="%s" target="_blank" rel="noopener noreferrer">Zero Ad Network dashboard</a>. It starts with <code>zapub_</code> and identifies your site so visits are credited to you.',
                        "zero-ad-network"
                    ),
                    ["a" => ["href" => [], "target" => [], "rel" => []], "code" => []]
                ),
                esc_url("https://zeroad.network/dashboard")
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
        <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[output_method]">
            <option value="header" <?php selected($value, "header"); ?>>
                <?php esc_html_e("HTTP Response Header", "zero-ad-network"); ?>
            </option>
            <option value="meta" <?php selected($value, "meta"); ?>>
                <?php esc_html_e("HTML Meta Tag", "zero-ad-network"); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e(
                'How to send the "Better-Web-Publisher" identifier to the subscriber\'s browser extension. HTTP header is recommended for better performance with page caching.',
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
        $enabled = !empty($this->options["cache_enabled"]);
        $apcu_available = extension_loaded("apcu") && apcu_enabled();
        ?>
             <div class="zeroad-feature-box <?php echo $enabled ? "selected" : ""; ?>">
                <label class="zeroad-feature-label">
                    <input type="checkbox"
                            name="<?php echo esc_attr(self::OPTION_KEY); ?>[cache_enabled]"
                            value="1"
                            <?php checked($enabled, true); ?>
                            <?php disabled(!$apcu_available); ?>>
                    <span class="zeroad-feature-name"><?php esc_html_e(
                        "Enable APCu token caching",
                        "zero-ad-network"
                    ); ?></span>
                </label>
                <p class="zeroad-feature-description">
                    <?php esc_html_e(
                        "Caches verified tokens in APCu (shared memory) so a returning subscriber's token is reused across requests and PHP workers instead of being re-verified each time.",
                        "zero-ad-network"
                    ); ?>
                    <br>
                    <strong><?php esc_html_e("Performance Impact:", "zero-ad-network"); ?></strong>
                    <?php esc_html_e(
                        "A cold verification is about 0.09ms; an APCu cache hit is a shared-memory lookup, a few microseconds. Either way it is negligible - caching mainly avoids repeating the work under load.",
                        "zero-ad-network"
                    ); ?>
                </p>
        
            <?php if (!$apcu_available): ?>
                <p class="zeroad-feature-description" style="color: #d63638;">
                    <strong><?php esc_html_e("⚠️ APCu not available.", "zero-ad-network"); ?></strong>
                    <?php esc_html_e(
                        "Install with: sudo apt-get install php-apcu or sudo pecl install apcu",
                        "zero-ad-network"
                    ); ?>
                </p>
            <?php endif; ?>
            </div>
    <?php
    }

    /**
     * Render cache_ttl field.
     */
    public function renderCacheTtl(): void
    {
        $value = intval($this->options["cache_ttl"] ?? ZEROAD_DEFAULT_CACHE_TTL); ?>
        <input type="number"
               name="<?php echo esc_attr(self::OPTION_KEY); ?>[cache_ttl]"
               value="<?php echo esc_attr((string) $value); ?>"
               min="1"
               max="60"
               step="1"
               class="small-text">
        <span><?php esc_html_e("seconds", "zero-ad-network"); ?></span>
        <p class="description">
            <?php esc_html_e(
                "How long to cache validated tokens. Recommended: 5-10 seconds. Lower = more accurate token expiration checking. Higher = better performance.",
                "zero-ad-network"
            ); ?>
            <br>
            <strong><?php esc_html_e("Note:", "zero-ad-network"); ?></strong>
            <?php esc_html_e(
                "Tokens are automatically removed from cache when they expire, regardless of TTL setting.",
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
               name="<?php echo esc_attr(self::OPTION_KEY); ?>[cache_prefix]"
               value="<?php echo esc_attr($value); ?>"
               maxlength="50"
               class="regular-text code"
               placeholder="zeroad:">
        <p class="description">
            <?php esc_html_e(
                "Prefix for cache keys to avoid conflicts with other plugins. Change only if you have multiple WordPress installations sharing APCu.",
                "zero-ad-network"
            ); ?>
            <br>
            <strong><?php esc_html_e("Example:", "zero-ad-network"); ?></strong>
            <code>zeroad:wp1:</code>, <code>zeroad:wp2:</code>
            <?php esc_html_e("for different sites", "zero-ad-network"); ?>
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
        return max(1, min(60, $ttl));
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
