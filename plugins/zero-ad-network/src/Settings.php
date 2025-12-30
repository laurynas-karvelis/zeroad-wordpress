<?php
/**
 * Settings - Handles WordPress settings registration and validation.
 *
 * @package ZeroAdNetwork
 * @since 1.0.0
 */

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\Token\Constants;

/**
 * Class Settings
 *
 * Manages all plugin settings through the WordPress Settings API.
 */
class Settings
{
  /**
   * Option key in wp_options table.
   */
  const OPTION_KEY = "zeroad_token_options";

  /**
   * Maximum Client ID length.
   */
  const MAX_CLIENT_ID_LENGTH = 255;

  /**
   * Minimum Client ID length.
   */
  const MIN_CLIENT_ID_LENGTH = 10;

  /**
   * Plugin options.
   *
   * @var array
   */
  private $options;

  /**
   * Constructor.
   *
   * @param array $options Current options.
   */
  public function __construct(array $options)
  {
    $this->options = $options;
  }

  /**
   * Register all settings.
   */
  public function register(): void
  {
    register_setting(self::OPTION_KEY, self::OPTION_KEY, [
      "sanitize_callback" => [$this, "validate"],
      "default" => self::getDefaults()
    ]);

    $this->registerMainSection();
    $this->registerCacheSection();
  }

  /**
   * Get default options.
   *
   * @return array Default option values.
   */
  public static function getDefaults(): array
  {
    return [
      "enabled" => false,
      "client_id" => "",
      "features" => [],
      "output_method" => "header",
      "cache_enabled" => true,
      "cache_ttl" => ZEROAD_DEFAULT_CACHE_TTL,
      "cache_prefix" => "zeroad:"
    ];
  }

  /**
   * Register main configuration section.
   */
  private function registerMainSection(): void
  {
    add_settings_section(
      "zeroad_main",
      __("Configuration", ZEROAD_TEXT_DOMAIN),
      [$this, "renderMainSectionDescription"],
      self::OPTION_KEY
    );

    $this->registerMainFields();
  }

  /**
   * Register main section fields.
   */
  private function registerMainFields(): void
  {
    $fields = [
      "enabled" => ["renderEnabled", __("Enable Plugin", ZEROAD_TEXT_DOMAIN)],
      "client_id" => ["renderClientId", __("Client ID", ZEROAD_TEXT_DOMAIN)],
      "features" => ["renderFeatures", __("Enabled Features", ZEROAD_TEXT_DOMAIN)],
      "output_method" => ["renderOutputMethod", __("Welcome Header Method", ZEROAD_TEXT_DOMAIN)]
    ];

    foreach ($fields as $field => $entry) {
      add_settings_field($field, $entry[1], [$this, $entry[0]], self::OPTION_KEY, "zeroad_main");
    }
  }

  /**
   * Register cache configuration section.
   */
  private function registerCacheSection(): void
  {
    add_settings_section(
      "zeroad_cache",
      __("Performance & Caching", ZEROAD_TEXT_DOMAIN),
      [$this, "renderCacheSectionDescription"],
      self::OPTION_KEY
    );

    $fields = [
      "cache_enabled" => ["renderCacheEnabled", __("Enable APCu Caching", ZEROAD_TEXT_DOMAIN)],
      "cache_ttl" => ["renderCacheTtl", __("Cache TTL (seconds)", ZEROAD_TEXT_DOMAIN)],
      "cache_prefix" => ["renderCachePrefix", __("Cache Key Prefix", ZEROAD_TEXT_DOMAIN)]
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
      "Configure your Zero Ad Network partnership settings. Subscribers with valid tokens will experience your site according to the features you enable.",
      ZEROAD_TEXT_DOMAIN
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
      ZEROAD_TEXT_DOMAIN
    );
    echo "</p>";

    // Show APCu status.
    $apcu_available = extension_loaded("apcu") && apcu_enabled();
    $notice_class = $apcu_available ? "notice-success" : "notice-warning";
    $icon = $apcu_available ? "✅" : "⚠️";
    $title = $apcu_available
      ? __("APCu Extension Available", ZEROAD_TEXT_DOMAIN)
      : __("APCu Extension Not Available", ZEROAD_TEXT_DOMAIN);

    printf(
      '<div class="notice %s inline" style="margin: 15px 0;"><p><strong>%s %s</strong><br>',
      esc_attr($notice_class),
      esc_html($icon),
      esc_html($title)
    );

    if ($apcu_available) {
      esc_html_e(
        "APCu is installed and ready. Enable caching below for ~10x faster token validation.",
        ZEROAD_TEXT_DOMAIN
      );
    } else {
      esc_html_e(
        "The APCu PHP extension is not installed or enabled. Token caching will be disabled. Install APCu for 10x performance improvement.",
        ZEROAD_TEXT_DOMAIN
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
            <?php esc_html_e("Activate Zero Ad Network integration on this site", ZEROAD_TEXT_DOMAIN); ?>
        </label>
        <p class="description">
            <?php esc_html_e(
              "When enabled, the plugin will verify subscriber tokens and apply the configured features for Zero Ad Network subscribers.",
              ZEROAD_TEXT_DOMAIN
            ); ?>
        </p>
        <?php
  }

  /**
   * Render client_id field.
   */
  public function renderClientId(): void
  {
    $value = $this->options["client_id"] ?? ""; ?>
        <input type="text" 
               name="<?php echo esc_attr(self::OPTION_KEY); ?>[client_id]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text code"
               maxlength="<?php echo esc_attr((string) self::MAX_CLIENT_ID_LENGTH); ?>"
               placeholder="<?php esc_attr_e("abc123DEF456_ghi789-jkl", ZEROAD_TEXT_DOMAIN); ?>">
        <p class="description">
            <?php printf(
              /* translators: %s: URL to Zero Ad Network dashboard */
              wp_kses(
                __(
                  'Your unique Client ID from the <a href="%s" target="_blank" rel="noopener noreferrer">Zero Ad Network dashboard</a>. This is used to identify your site and verify subscriber tokens.',
                  ZEROAD_TEXT_DOMAIN
                ),
                ["a" => ["href" => [], "target" => [], "rel" => []]]
              ),
              esc_url("https://zeroad.network/dashboard")
            ); ?>
        </p>
        <?php
  }

  /**
   * Render features field.
   */
  public function renderFeatures(): void
  {
    $selected_features = $this->options["features"] ?? [];
    $features = Constants::FEATURE;

    $feature_descriptions = [
      Constants::FEATURE["CLEAN_WEB"] => [
        "name" => __("Clean Web", ZEROAD_TEXT_DOMAIN),
        "description" => __(
          'Hide advertisements, cookie consent screens, marketing dialogs, and disable non-functional tracking for Clean Web subscribers ($6/month plan).',
          ZEROAD_TEXT_DOMAIN
        ),
        "revenue" => 5
      ],
      Constants::FEATURE["ONE_PASS"] => [
        "name" => __("One Pass", ZEROAD_TEXT_DOMAIN),
        "description" => __(
          'Grant access to premium/member-only content for One Pass subscribers ($12/month plan) without requiring separate subscriptions.',
          ZEROAD_TEXT_DOMAIN
        ),
        "revenue" => 10
      ]
    ];

    foreach ($features as $key => $value) {

      if (!isset($feature_descriptions[$value])) {
        continue;
      }

      $info = $feature_descriptions[$value];
      $checked = in_array($value, $selected_features, true);
      ?>
            <div class="zeroad-feature-box <?php echo $checked ? "selected" : ""; ?>">
                <label class="zeroad-feature-label">
                    <input type="checkbox" 
                           name="<?php echo esc_attr(self::OPTION_KEY); ?>[features]array()"
                           value="<?php echo esc_attr((string) $value); ?>"
                           <?php checked($checked, true); ?>
                           data-revenue="<?php echo esc_attr((string) $info["revenue"]); ?>">
                    <span class="zeroad-feature-name"><?php echo esc_html($info["name"]); ?></span>
                </label>
                <p class="zeroad-feature-description"><?php echo esc_html($info["description"]); ?></p>
                <p class="zeroad-feature-revenue">
                    <?php printf(
                      /* translators: %d: Monthly revenue amount in dollars */
                      esc_html__('Earn up to $%d per subscriber per month (based on engagement)', ZEROAD_TEXT_DOMAIN),
                      esc_html((string) $info["revenue"])
                    ); ?>
                </p>
            </div>
            <?php
    }

    echo '<p class="description" style="margin-top: 15px;">';
    esc_html_e(
      'Select at least one feature. The Freedom plan ($18/month) includes both Clean Web and One Pass, providing the maximum revenue opportunity.',
      ZEROAD_TEXT_DOMAIN
    );
    echo "</p>";
  }

  /**
   * Render output_method field.
   */
  public function renderOutputMethod(): void
  {
    $value = $this->options["output_method"] ?? "header"; ?>
        <select name="<?php echo esc_attr(self::OPTION_KEY); ?>[output_method]">
            <option value="header" <?php selected($value, "header"); ?>>
                <?php esc_html_e("HTTP Response Header", ZEROAD_TEXT_DOMAIN); ?>
            </option>
            <option value="meta" <?php selected($value, "meta"); ?>>
                <?php esc_html_e("HTML Meta Tag", ZEROAD_TEXT_DOMAIN); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e(
              'How to send the "X-Better-Web-Welcome" identifier to the subscriber\'s browser extension. HTTP header is recommended for better performance with page caching.',
              ZEROAD_TEXT_DOMAIN
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
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr(self::OPTION_KEY); ?>[cache_enabled]"
                   value="1"
                   <?php checked($enabled, true); ?>
                   <?php disabled(!$apcu_available); ?>>
            <?php esc_html_e("Enable APCu token caching", ZEROAD_TEXT_DOMAIN); ?>
        </label>
        <p class="description">
            <?php esc_html_e(
              "Caches validated tokens in APCu (shared memory) for faster subsequent requests. Improves performance by ~10x (2ms → 0.2ms per validation).",
              ZEROAD_TEXT_DOMAIN
            ); ?>
            <br>
            <strong><?php esc_html_e("Performance Impact:", ZEROAD_TEXT_DOMAIN); ?></strong>
            <?php esc_html_e(
              "Without cache: ~2ms per token validation. With cache: ~0.2ms (cache hit).",
              ZEROAD_TEXT_DOMAIN
            ); ?>
        </p>
        <?php if (!$apcu_available): ?>
            <p class="description" style="color: #d63638;">
                <strong><?php esc_html_e("⚠️ APCu not available.", ZEROAD_TEXT_DOMAIN); ?></strong>
                <?php esc_html_e(
                  "Install with: sudo apt-get install php-apcu or sudo pecl install apcu",
                  ZEROAD_TEXT_DOMAIN
                ); ?>
            </p>
        <?php endif;
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
        <span><?php esc_html_e("seconds", ZEROAD_TEXT_DOMAIN); ?></span>
        <p class="description">
            <?php esc_html_e(
              "How long to cache validated tokens. Recommended: 5-10 seconds. Lower = more accurate token expiration checking. Higher = better performance.",
              ZEROAD_TEXT_DOMAIN
            ); ?>
            <br>
            <strong><?php esc_html_e("Note:", ZEROAD_TEXT_DOMAIN); ?></strong>
            <?php esc_html_e(
              "Tokens are automatically removed from cache when they expire, regardless of TTL setting.",
              ZEROAD_TEXT_DOMAIN
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
              ZEROAD_TEXT_DOMAIN
            ); ?>
            <br>
            <strong><?php esc_html_e("Example:", ZEROAD_TEXT_DOMAIN); ?></strong>
            <code>zeroad:wp1:</code>, <code>zeroad:wp2:</code>
            <?php esc_html_e("for different sites", ZEROAD_TEXT_DOMAIN); ?>
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

    // Client ID validation.
    $client_id = isset($input["client_id"]) ? trim(sanitize_text_field($input["client_id"])) : "";
    if (!empty($client_id)) {
      $client_id = $this->validateClientId($client_id, $errors);
    }
    $output["client_id"] = $client_id;

    // Output method.
    $output["output_method"] = $this->validateOutputMethod($input["output_method"] ?? "header");

    // Cache settings.
    $output["cache_enabled"] = !empty($input["cache_enabled"]) ? 1 : 0;
    $output["cache_ttl"] = $this->validateCacheTtl($input["cache_ttl"] ?? ZEROAD_DEFAULT_CACHE_TTL);
    $output["cache_prefix"] = $this->validateCachePrefix($input["cache_prefix"] ?? "zeroad:");

    // Features validation.
    $output["features"] = $this->validateFeatures($input["features"] ?? [], $output["enabled"], $errors);

    // Display errors.
    foreach ($errors as $error) {
      add_settings_error(self::OPTION_KEY, "validation_error", $error, "error");
    }

    return $output;
  }

  /**
   * Validate client ID.
   *
   * @param string $client_id The client ID to validate.
   * @param array  $errors    Array to collect errors.
   * @return string Validated client ID or empty string.
   */
  private function validateClientId(string $client_id, array &$errors): string
  {
    // Check length.
    if (strlen($client_id) < self::MIN_CLIENT_ID_LENGTH) {
      $errors[] = sprintf(
        /* translators: %d: minimum length */
        __(
          "Client ID is too short (minimum %d characters). Please verify you copied it correctly from the Zero Ad Network dashboard.",
          ZEROAD_TEXT_DOMAIN
        ),
        self::MIN_CLIENT_ID_LENGTH
      );
      return "";
    }

    if (strlen($client_id) > self::MAX_CLIENT_ID_LENGTH) {
      $errors[] = sprintf(
        /* translators: %d: maximum length */
        __("Client ID is too long (maximum %d characters).", ZEROAD_TEXT_DOMAIN),
        self::MAX_CLIENT_ID_LENGTH
      );
      return "";
    }

    // Check characters.
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $client_id)) {
      $errors[] = __(
        "Client ID contains invalid characters. It should only contain letters, numbers, hyphens (-), and underscores (_).",
        ZEROAD_TEXT_DOMAIN
      );
      return "";
    }

    return $client_id;
  }

  /**
   * Validate output method.
   *
   * @param string $method The output method.
   * @return string Validated output method.
   */
  private function validateOutputMethod(string $method): string
  {
    $valid_methods = ["header", "meta"];
    return in_array($method, $valid_methods, true) ? $method : "header";
  }

  /**
   * Validate cache TTL.
   *
   * @param mixed $ttl The TTL value.
   * @return int Validated TTL.
   */
  private function validateCacheTtl($ttl): int
  {
    $ttl = intval($ttl);
    return max(1, min(60, $ttl));
  }

  /**
   * Validate cache prefix.
   *
   * @param string $prefix The cache prefix.
   * @return string Validated cache prefix.
   */
  private function validateCachePrefix(string $prefix): string
  {
    $prefix = trim(sanitize_text_field($prefix));

    if (empty($prefix)) {
      return "zeroad:";
    }

    // Only allow alphanumeric, underscore, hyphen, and colon.
    if (!preg_match('/^[a-z0-9_-]+:?$/i', $prefix)) {
      add_settings_error(
        self::OPTION_KEY,
        "cache_prefix_invalid",
        __("Cache prefix contains invalid characters. Using default.", ZEROAD_TEXT_DOMAIN),
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

  /**
   * Validate features.
   *
   * @param mixed $features Array of selected features.
   * @param bool  $enabled  Whether plugin is enabled.
   * @param array $errors   Array to collect errors.
   * @return array Validated features.
   */
  private function validateFeatures($features, bool $enabled, array &$errors): array
  {
    if (!is_array($features)) {
      return [];
    }

    $valid_features = [];
    $allowed_features = array_values(Constants::FEATURE);

    foreach ($features as $feature) {
      $feature = (int) $feature;
      if (in_array($feature, $allowed_features, true)) {
        $valid_features[] = $feature;
      }
    }

    // Check if at least one feature is selected when enabled.
    if (empty($valid_features) && $enabled) {
      $errors[] = __(
        "You must select at least one feature (Clean Web or One Pass) when the plugin is enabled.",
        ZEROAD_TEXT_DOMAIN
      );
    }

    return array_unique($valid_features);
  }
}
