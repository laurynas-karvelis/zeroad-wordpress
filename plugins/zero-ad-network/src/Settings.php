<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\Token\Constants;

/**
 * Settings - Handles WordPress settings registration and validation
 */
class Settings
{
  public const OPT_KEY = "zeroad_token_options";

  private $options;

  public function __construct(array $options)
  {
    $this->options = $options;
  }

  /**
   * Register all settings
   */
  public function register(): void
  {
    register_setting(self::OPT_KEY, self::OPT_KEY, [
      "sanitize_callback" => [$this, "validate"],
      "default" => $this->getDefaults()
    ]);

    $this->registerMainSection();
    $this->registerCacheSection();
  }

  /**
   * Get default options
   */
  public static function getDefaults(): array
  {
    return [
      "enabled" => false,
      "client_id" => "",
      "features" => [],
      "output_method" => "header",
      "debug_mode" => false,
      "cache_enabled" => true,
      "cache_ttl" => 5,
      "cache_prefix" => "zeroad:"
    ];
  }

  /**
   * Register main configuration section
   */
  private function registerMainSection(): void
  {
    add_settings_section(
      "zeroad_main",
      __("Configuration", "zero-ad-network"),
      function () {
        echo '<p class="description">';
        esc_html_e(
          "Configure your Zero Ad Network partnership settings. Subscribers with valid tokens will experience your site according to the features you enable.",
          "zero-ad-network"
        );
        echo "</p>";
      },
      self::OPT_KEY
    );

    add_settings_field(
      "enabled",
      __("Enable Plugin", "zero-ad-network"),
      [$this, "renderEnabled"],
      self::OPT_KEY,
      "zeroad_main"
    );
    add_settings_field(
      "client_id",
      __("Client ID", "zero-ad-network"),
      [$this, "renderClientId"],
      self::OPT_KEY,
      "zeroad_main"
    );
    add_settings_field(
      "features",
      __("Enabled Features", "zero-ad-network"),
      [$this, "renderFeatures"],
      self::OPT_KEY,
      "zeroad_main"
    );
    add_settings_field(
      "output_method",
      __("Welcome Header Method", "zero-ad-network"),
      [$this, "renderOutputMethod"],
      self::OPT_KEY,
      "zeroad_main"
    );
    add_settings_field(
      "debug_mode",
      __("Debug Mode", "zero-ad-network"),
      [$this, "renderDebugMode"],
      self::OPT_KEY,
      "zeroad_main"
    );
  }

  /**
   * Register cache configuration section
   */
  private function registerCacheSection(): void
  {
    add_settings_section(
      "zeroad_cache",
      __("Performance & Caching", "zero-ad-network"),
      function () {
        echo '<p class="description">';
        esc_html_e(
          "Configure APCu token caching for improved performance. When enabled, validated tokens are cached for faster subsequent requests.",
          "zero-ad-network"
        );
        echo "</p>";

        // Show APCu status
        $apcuAvailable = extension_loaded("apcu") && apcu_enabled();
        if (!$apcuAvailable) {
          echo '<div class="notice notice-warning inline" style="margin: 15px 0;">';
          echo "<p><strong>";
          esc_html_e("⚠️ APCu Extension Not Available", "zero-ad-network");
          echo "</strong><br>";
          esc_html_e(
            "The APCu PHP extension is not installed or enabled. Token caching will be disabled. Install APCu for 10x performance improvement.",
            "zero-ad-network"
          );
          echo "</p></div>";
        } else {
          echo '<div class="notice notice-success inline" style="margin: 15px 0;">';
          echo "<p><strong>";
          esc_html_e("✅ APCu Extension Available", "zero-ad-network");
          echo "</strong><br>";
          esc_html_e(
            "APCu is installed and ready. Enable caching below for ~10x faster token validation.",
            "zero-ad-network"
          );
          echo "</p></div>";
        }
      },
      self::OPT_KEY
    );

    add_settings_field(
      "cache_enabled",
      __("Enable APCu Caching", "zero-ad-network"),
      [$this, "renderCacheEnabled"],
      self::OPT_KEY,
      "zeroad_cache"
    );
    add_settings_field(
      "cache_ttl",
      __("Cache TTL (seconds)", "zero-ad-network"),
      [$this, "renderCacheTtl"],
      self::OPT_KEY,
      "zeroad_cache"
    );
    add_settings_field(
      "cache_prefix",
      __("Cache Key Prefix", "zero-ad-network"),
      [$this, "renderCachePrefix"],
      self::OPT_KEY,
      "zeroad_cache"
    );
  }

  // ==========================================================================
  // Field Renderers
  // ==========================================================================

  public function renderEnabled(): void
  {
    $enabled = !empty($this->options["enabled"]); ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr(self::OPT_KEY); ?>[enabled]"
                   value="1"
                   <?php checked($enabled); ?>>
            <?php esc_html_e("Activate Zero Ad Network integration on this site", "zero-ad-network"); ?>
        </label>
        <p class="description">
            <?php esc_html_e(
              "When enabled, the plugin will verify subscriber tokens and apply the configured features for Zero Ad Network subscribers.",
              "zero-ad-network"
            ); ?>
        </p>
        <?php
  }

  public function renderClientId(): void
  {
    $value = $this->options["client_id"] ?? ""; ?>
        <input type="text" 
               name="<?php echo esc_attr(self::OPT_KEY); ?>[client_id]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text code"
               placeholder="<?php esc_attr_e("abc123DEF456_ghi789-jkl", "zero-ad-network"); ?>">
        <p class="description">
            <?php printf(
              wp_kses(
                __(
                  'Your unique Client ID from the <a href="%s" target="_blank">Zero Ad Network dashboard</a>. This is used to identify your site and verify subscriber tokens.',
                  "zero-ad-network"
                ),
                ["a" => ["href" => [], "target" => []]]
              ),
              "https://zeroad.network/dashboard"
            ); ?>
        </p>
        <?php
  }

  public function renderFeatures(): void
  {
    $selectedFeatures = $this->options["features"] ?? [];
    $features = Constants::FEATURE;

    $featureDescriptions = [
      Constants::FEATURE["CLEAN_WEB"] => [
        "name" => __("Clean Web", "zero-ad-network"),
        "description" => __(
          "Hide advertisements, cookie consent screens, marketing dialogs, and disable non-functional tracking for Clean Web subscribers ($6/month plan).",
          "zero-ad-network"
        ),
        "revenue" => 6
      ],
      Constants::FEATURE["ONE_PASS"] => [
        "name" => __("One Pass", "zero-ad-network"),
        "description" => __(
          "Remove paywalls and grant free access to your base subscription plan for One Pass subscribers ($12/month plan).",
          "zero-ad-network"
        ),
        "revenue" => 12
      ]
    ];

    foreach ($features as $name => $value) {

      $info = $featureDescriptions[$value];
      $checked = in_array($value, $selectedFeatures, true);
      ?>
            <div class="zeroad-feature-box <?php echo $checked ? "selected" : ""; ?>">
                <label class="zeroad-feature-label">
                    <input type="checkbox" 
                           name="<?php echo esc_attr(self::OPT_KEY); ?>[features][]"
                           value="<?php echo esc_attr($value); ?>"
                           <?php checked($checked); ?>
                           data-revenue="<?php echo esc_attr($info["revenue"]); ?>">
                    <span class="zeroad-feature-name"><?php echo esc_html($info["name"]); ?></span>
                </label>
                <p class="zeroad-feature-description"><?php echo esc_html($info["description"]); ?></p>
                <p class="zeroad-feature-revenue">
                    <?php printf(
                      esc_html__("Earn up to $%d per subscriber per month (based on engagement)", "zero-ad-network"),
                      $info["revenue"]
                    ); ?>
                </p>
            </div>
        <?php
    }

    echo '<p class="description" style="margin-top: 15px;">';
    esc_html_e(
      "Select at least one feature. The Freedom plan ($18/month) includes both Clean Web and One Pass, providing the maximum revenue opportunity.",
      "zero-ad-network"
    );
    echo "</p>";
  }

  public function renderOutputMethod(): void
  {
    $value = $this->options["output_method"] ?? "header"; ?>
        <select name="<?php echo esc_attr(self::OPT_KEY); ?>[output_method]">
            <option value="header" <?php selected($value, "header"); ?>>
                <?php esc_html_e("HTTP Response Header", "zero-ad-network"); ?>
            </option>
            <option value="meta" <?php selected($value, "meta"); ?>>
                <?php esc_html_e("HTML Meta Tag", "zero-ad-network"); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e(
              'How to send the "X-Better-Web-Welcome" identifier to the subscriber\'s browser extension. HTTP header is recommended for better performance with page caching.',
              "zero-ad-network"
            ); ?>
        </p>
        <?php
  }

  public function renderDebugMode(): void
  {
    $enabled = !empty($this->options["debug_mode"]); ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr(self::OPT_KEY); ?>[debug_mode]"
                   value="1"
                   <?php checked($enabled); ?>>
            <?php esc_html_e("Enable detailed logging", "zero-ad-network"); ?>
        </label>
        <p class="description">
            <?php esc_html_e(
              "Logs token verification, feature activation, and plugin operations to the PHP error log. Only enable when troubleshooting issues.",
              "zero-ad-network"
            ); ?>
        </p>
        <?php
  }

  public function renderCacheEnabled(): void
  {
    $enabled = !empty($this->options["cache_enabled"]);
    $apcuAvailable = extension_loaded("apcu") && apcu_enabled();
    ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr(self::OPT_KEY); ?>[cache_enabled]"
                   value="1"
                   <?php checked($enabled); ?>
                   <?php disabled(!$apcuAvailable); ?>>
            <?php esc_html_e("Enable APCu token caching", "zero-ad-network"); ?>
        </label>
        <p class="description">
            <?php esc_html_e(
              "Caches validated tokens in APCu (shared memory) for faster subsequent requests. Improves performance by ~10x (2ms → 0.2ms per validation).",
              "zero-ad-network"
            ); ?>
            <br>
            <strong><?php esc_html_e("Performance Impact:", "zero-ad-network"); ?></strong>
            <?php esc_html_e(
              "Without cache: ~2ms per token validation. With cache: ~0.2ms (cache hit).",
              "zero-ad-network"
            ); ?>
        </p>
        <?php if (!$apcuAvailable): ?>
            <p class="description" style="color: #d63638;">
                <strong><?php esc_html_e("⚠️ APCu not available.", "zero-ad-network"); ?></strong>
                <?php esc_html_e(
                  "Install with: sudo apt-get install php-apcu or sudo pecl install apcu",
                  "zero-ad-network"
                ); ?>
            </p>
        <?php endif;
  }

  public function renderCacheTtl(): void
  {
    $value = intval($this->options["cache_ttl"] ?? 5); ?>
        <input type="number" 
               name="<?php echo esc_attr(self::OPT_KEY); ?>[cache_ttl]"
               value="<?php echo esc_attr($value); ?>"
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

  public function renderCachePrefix(): void
  {
    $value = $this->options["cache_prefix"] ?? "zeroad:"; ?>
        <input type="text" 
               name="<?php echo esc_attr(self::OPT_KEY); ?>[cache_prefix]"
               value="<?php echo esc_attr($value); ?>"
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

  // ==========================================================================
  // Validation
  // ==========================================================================

  public function validate($input): array
  {
    $out = [];
    $errors = [];

    // Enabled
    $out["enabled"] = !empty($input["enabled"]) ? 1 : 0;

    // Client ID validation
    $client_id = isset($input["client_id"]) ? trim(sanitize_text_field($input["client_id"])) : "";
    if (!empty($client_id)) {
      if (!preg_match("/^[A-Za-z0-9_-]+$/", $client_id)) {
        $errors[] = __(
          "Client ID contains invalid characters. It should only contain letters, numbers, hyphens (-), and underscores (_).",
          "zero-ad-network"
        );
        $client_id = "";
      } elseif (strlen($client_id) < 10) {
        $errors[] = __(
          "Client ID seems too short. Please verify you copied it correctly from the Zero Ad Network dashboard.",
          "zero-ad-network"
        );
      }
    }
    $out["client_id"] = $client_id;

    // Output method
    $output_method = $input["output_method"] ?? "header";
    $out["output_method"] = in_array($output_method, ["header", "meta"], true) ? $output_method : "header";

    // Debug mode
    $out["debug_mode"] = !empty($input["debug_mode"]) ? 1 : 0;

    // Cache settings
    $out["cache_enabled"] = !empty($input["cache_enabled"]) ? 1 : 0;

    $cache_ttl = isset($input["cache_ttl"]) ? intval($input["cache_ttl"]) : 5;
    $out["cache_ttl"] = max(1, min(60, $cache_ttl));

    $cache_prefix = isset($input["cache_prefix"]) ? trim(sanitize_text_field($input["cache_prefix"])) : "zeroad:";
    if (empty($cache_prefix)) {
      $cache_prefix = "zeroad:";
    }
    if (substr($cache_prefix, -1) !== ":") {
      $cache_prefix .= ":";
    }
    $out["cache_prefix"] = $cache_prefix;

    // Features validation
    $features = [];
    if (isset($input["features"]) && is_array($input["features"])) {
      foreach ($input["features"] as $feature) {
        $feature = (int) $feature;
        if (in_array($feature, array_values(Constants::FEATURE), true)) {
          $features[] = $feature;
        }
      }
    }

    if (empty($features) && !empty($out["enabled"])) {
      $errors[] = __(
        "You must select at least one feature (Clean Web or One Pass) when the plugin is enabled.",
        "zero-ad-network"
      );
    }

    $out["features"] = array_unique($features);

    // Display errors
    foreach ($errors as $error) {
      add_settings_error(self::OPT_KEY, "validation_error", $error, "error");
    }

    return $out;
  }
}
