<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\Token\Site;
use ZeroAd\Token\Constants;

/**
 * Config - Main configuration and admin interface for Zero Ad Network WordPress plugin
 *
 * This class manages:
 * - Plugin settings and options
 * - Admin UI for site owners to configure their Zero Ad Network integration
 * - Site instance creation with Client ID from Zero Ad Network dashboard
 * - Feature selection (CLEAN_WEB, ONE_PASS)
 */
class Config
{
  private static $instance = null;

  private $renderer;
  private $siteCache = null;

  public const OPT_KEY = "zeroad_token_options";
  private $options;

  private function __construct()
  {
    $this->options = get_option(self::OPT_KEY, [
      "enabled" => false,
      "client_id" => "",
      "features" => [],
      "output_method" => "header",
      "debug_mode" => false
    ]);

    $this->renderer = new Renderer();
    $this->renderer->options($this->options);
  }

  public static function instance(): Config
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function run(): void
  {
    // Admin hooks
    add_action("admin_menu", [$this, "addAdminPage"]);
    add_action("admin_init", [$this, "registerSettings"]);
    add_action("admin_enqueue_scripts", [$this, "enqueueAdminAssets"]);

    // Show welcome notice for new installations
    add_action("admin_notices", [$this, "maybeShowWelcomeNotice"]);

    // Register renderer (handles token verification and feature toggling)
    $this->renderer->run();

    // Early init - create Site instance for token verification
    add_action("init", [$this, "initializeSite"], 1);
  }

  /**
   * Initialize the Site object with Client ID from Zero Ad Network dashboard
   * This Site instance is used to:
   * 1. Send the "X-Better-Web-Welcome" header to identify as a partnered site
   * 2. Verify incoming subscriber tokens using ED25519 cryptographic verification
   */
  public function initializeSite(): void
  {
    try {
      // Refresh options in case they were updated
      $this->options = get_option(self::OPT_KEY, $this->options);
      $this->renderer->options($this->options);

      // Only create Site instance if plugin is fully configured
      if (
        !empty($this->options["enabled"]) &&
        !empty($this->options["client_id"]) &&
        !empty($this->options["features"]) &&
        count($this->options["features"]) > 0
      ) {
        // Reuse cached Site instance if available
        if ($this->siteCache === null) {
          $this->siteCache = new Site([
            "clientId" => $this->options["client_id"],
            "features" => $this->options["features"]
          ]);

          $this->debugLog("Site instance created with Client ID: " . substr($this->options["client_id"], 0, 8) . "...");
        }

        $this->renderer->site($this->siteCache);
      } else {
        $this->renderer->site(null);
        $this->debugLog("Site not initialized - plugin not fully configured");
      }
    } catch (\Throwable $e) {
      $this->renderer->site(null);
      $this->debugLog("Site initialization error: " . $e->getMessage());

      // Show admin notice for configuration errors
      if (is_admin() && current_user_can("manage_options")) {
        add_action("admin_notices", function () use ($e) {
          echo '<div class="notice notice-error"><p>';
          echo "<strong>" . esc_html__("Zero Ad Network:", "zero-ad-network") . "</strong> ";
          printf(
            /* translators: %s: Error message */
            esc_html__("Configuration error: %s", "zero-ad-network"),
            esc_html($e->getMessage())
          );
          echo ' <a href="' . esc_url(admin_url("admin.php?page=zeroad-token")) . '">';
          esc_html_e("Check settings →", "zero-ad-network");
          echo "</a></p></div>";
        });
      }
    }
  }

  /**
   * Show welcome notice for new installations
   */
  public function maybeShowWelcomeNotice(): void
  {
    if (!current_user_can("manage_options")) {
      return;
    }

    // Only show if plugin is not configured yet
    if (empty($this->options["client_id"]) && !get_transient("zeroad_welcome_dismissed")) { ?>
            <div class="notice notice-info is-dismissible" data-zeroad-notice="welcome">
                <h3><?php esc_html_e("Welcome to Zero Ad Network!", "zero-ad-network"); ?></h3>
                <p>
                    <?php esc_html_e(
                      "Thank you for installing Zero Ad Network! To start earning revenue from subscriber engagement:",
                      "zero-ad-network"
                    ); ?>
                </p>
                <ol>
                    <li>
                        <?php printf(
                          /* translators: %s: URL to Zero Ad Network dashboard */
                          wp_kses(
                            __(
                              'Register your site at <a href="%s" target="_blank">zeroad.network</a> to get your Client ID',
                              "zero-ad-network"
                            ),
                            ["a" => ["href" => [], "target" => []]]
                          ),
                          "https://zeroad.network"
                        ); ?>
                    </li>
                    <li>
                        <?php printf(
                          /* translators: %s: URL to plugin settings */
                          wp_kses(
                            __('Enter your Client ID in the <a href="%s">plugin settings</a>', "zero-ad-network"),
                            ["a" => ["href" => []]]
                          ),
                          admin_url("admin.php?page=zeroad-token")
                        ); ?>
                    </li>
                    <li><?php esc_html_e(
                      "Select which features to enable (Clean Web, One Pass, or both)",
                      "zero-ad-network"
                    ); ?></li>
                    <li><?php esc_html_e(
                      "Save settings and subscribers will enjoy an improved experience on your site while you earn revenue!",
                      "zero-ad-network"
                    ); ?></li>
                </ol>
                <p>
                    <a href="https://docs.zeroad.network" target="_blank" class="button button-primary">
                        <?php esc_html_e("View Documentation", "zero-ad-network"); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url("admin.php?page=zeroad-token")); ?>" class="button">
                        <?php esc_html_e("Go to Settings", "zero-ad-network"); ?>
                    </a>
                </p>
            </div>
            <?php }
  }

  /**
   * Register plugin settings
   */
  public function registerSettings(): void
  {
    register_setting(self::OPT_KEY, self::OPT_KEY, [
      "sanitize_callback" => [$this, "validateOptions"],
      "default" => [
        "enabled" => false,
        "client_id" => "",
        "features" => [],
        "output_method" => "header",
        "debug_mode" => false
      ]
    ]);

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

    // Plugin enabled
    add_settings_field(
      "enabled",
      __("Enable Plugin", "zero-ad-network"),
      [$this, "renderEnabledField"],
      self::OPT_KEY,
      "zeroad_main"
    );

    // Client ID
    add_settings_field(
      "client_id",
      __("Client ID", "zero-ad-network"),
      [$this, "renderClientIdField"],
      self::OPT_KEY,
      "zeroad_main"
    );

    // Features
    add_settings_field(
      "features",
      __("Enabled Features", "zero-ad-network"),
      [$this, "renderFeaturesField"],
      self::OPT_KEY,
      "zeroad_main"
    );

    // Output method
    add_settings_field(
      "output_method",
      __("Welcome Header Method", "zero-ad-network"),
      [$this, "renderOutputMethodField"],
      self::OPT_KEY,
      "zeroad_main"
    );

    // Debug mode
    add_settings_field(
      "debug_mode",
      __("Debug Mode", "zero-ad-network"),
      [$this, "renderDebugModeField"],
      self::OPT_KEY,
      "zeroad_main"
    );
  }

  /**
   * Render enabled field
   */
  public function renderEnabledField(): void
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

  /**
   * Render client ID field
   */
  public function renderClientIdField(): void
  {
    $value = $this->options["client_id"] ?? ""; ?>
        <input type="text" 
               name="<?php echo esc_attr(self::OPT_KEY); ?>[client_id]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text code"
               placeholder="<?php esc_attr_e("abc123DEF456_ghi789-jkl", "zero-ad-network"); ?>">
        <p class="description">
            <?php printf(
              /* translators: %s: URL to Zero Ad Network dashboard */
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

  /**
   * Render features field
   */
  public function renderFeaturesField(): void
  {
    $selectedFeatures = $this->options["features"] ?? [];
    $features = Constants::FEATURE;

    $featureDescriptions = [
      Constants::FEATURE["CLEAN_WEB"] => [
        "name" => __("Clean Web", "zero-ad-network"),
        "description" => __(
          "Subscribers enjoy your site without advertisements, cookie consent banners, marketing popups, or non-functional third-party trackers. You earn revenue based on their engagement time.",
          "zero-ad-network"
        ),
        "revenue" => __("$6 per subscriber per month (distributed based on time spent)", "zero-ad-network")
      ],
      Constants::FEATURE["ONE_PASS"] => [
        "name" => __("One Pass", "zero-ad-network"),
        "description" => __(
          "Subscribers get free access to your basic subscription content and paywalled articles. You earn revenue as if they were paying subscribers.",
          "zero-ad-network"
        ),
        "revenue" => __("$12 per subscriber per month (distributed based on time spent)", "zero-ad-network")
      ]
    ];

    echo '<div style="max-width: 600px;">';

    foreach ($features as $key => $value) {
      $checked = in_array($value, $selectedFeatures);
      $info = $featureDescriptions[$value] ?? [];

      echo '<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">';
      echo '<label style="display: block; font-weight: 600; margin-bottom: 8px;">';
      printf(
        '<input type="checkbox" name="%s[features][]" value="%s" %s style="margin-right: 8px;">',
        esc_attr(self::OPT_KEY),
        esc_attr($value),
        checked($checked, true, false)
      );
      echo esc_html($info["name"] ?? $key);
      echo "</label>";

      if (!empty($info["description"])) {
        echo '<p style="margin: 8px 0 8px 24px; color: #666;">' . esc_html($info["description"]) . "</p>";
      }

      if (!empty($info["revenue"])) {
        echo '<p style="margin: 4px 0 0 24px; font-size: 12px; color: #0073aa;"><strong>' .
          esc_html__("Revenue:", "zero-ad-network") .
          "</strong> " .
          esc_html($info["revenue"]) .
          "</p>";
      }

      echo "</div>";
    }

    echo "</div>";

    echo '<p class="description" style="margin-top: 15px;">';
    esc_html_e(
      "Select at least one feature. The Freedom plan ($18/month) includes both Clean Web and One Pass, providing the maximum revenue opportunity.",
      "zero-ad-network"
    );
    echo "</p>";
  }

  /**
   * Render output method field
   */
  public function renderOutputMethodField(): void
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

  /**
   * Render debug mode field
   */
  public function renderDebugModeField(): void
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

  /**
   * Validate and sanitize options
   */
  public function validateOptions($input)
  {
    $out = [];
    $errors = [];

    // Enabled
    $out["enabled"] = !empty($input["enabled"]) ? 1 : 0;

    // Client ID validation (base64url-safe: A-Z, a-z, 0-9, -, _)
    $client_id = isset($input["client_id"]) ? trim(sanitize_text_field($input["client_id"])) : "";

    if (!empty($client_id)) {
      if (!preg_match('/^[A-Za-z0-9_-]+$/', $client_id)) {
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

    // Update cached options
    $this->options = $out;

    // Reinitialize site if init already happened
    if (did_action("init")) {
      $this->siteCache = null;
      $this->initializeSite();
    }

    return $out;
  }

  /**
   * Add admin menu pages
   */
  public function addAdminPage(): void
  {
    add_menu_page(
      __("Zero Ad Network", "zero-ad-network"),
      __("Zero Ad Network", "zero-ad-network"),
      "manage_options",
      "zeroad-token",
      [$this, "renderAdminPage"],
      "dashicons-admin-site-alt3",
      80
    );

    add_submenu_page(
      "zeroad-token",
      __("Settings", "zero-ad-network"),
      __("Settings", "zero-ad-network"),
      "manage_options",
      "zeroad-token",
      [$this, "renderAdminPage"]
    );

    add_submenu_page(
      "zeroad-token",
      __("Cache Configuration", "zero-ad-network"),
      __("Cache Configuration", "zero-ad-network"),
      "manage_options",
      "zeroad-cache-config",
      [$this, "renderCacheConfigPage"]
    );

    add_submenu_page(
      "zeroad-token",
      __("About", "zero-ad-network"),
      __("About", "zero-ad-network"),
      "manage_options",
      "zeroad-about",
      [$this, "renderAboutPage"]
    );
  }

  /**
   * Render main settings page
   */
  public function renderAdminPage(): void
  {
    if (!current_user_can("manage_options")) {
      wp_die(esc_html__("You do not have sufficient permissions to access this page.", "zero-ad-network"));
    } ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors(self::OPT_KEY); ?>
            
            <?php if (!empty($this->options["enabled"]) && !empty($this->options["client_id"])): ?>
                <div class="notice notice-success inline" style="margin: 20px 0;">
                    <p>
                        <strong><?php esc_html_e(
                          "✓ Your site is partnered with Zero Ad Network!",
                          "zero-ad-network"
                        ); ?></strong><br>
                        <?php esc_html_e(
                          "Subscribers will now enjoy an enhanced experience on your site, and you'll earn revenue based on their engagement time.",
                          "zero-ad-network"
                        ); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPT_KEY);
                do_settings_sections(self::OPT_KEY);
                submit_button(__("Save Settings", "zero-ad-network"));
                ?>
            </form>
            
            <?php if (!empty($this->options["enabled"]) && !empty($this->options["client_id"])): ?>
                <hr style="margin: 30px 0;">
                
                <h2><?php esc_html_e("Integration Status", "zero-ad-network"); ?></h2>
                <table class="widefat striped" style="max-width: 800px;">
                    <tbody>
                        <tr>
                            <th style="width: 30%;"><?php esc_html_e("Plugin Status", "zero-ad-network"); ?></th>
                            <td>
                                <span style="color: #46b450; font-size: 18px;">●</span>
                                <strong><?php esc_html_e("Active & Partnered", "zero-ad-network"); ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e("Client ID", "zero-ad-network"); ?></th>
                            <td>
                                <code style="font-size: 13px;"><?php echo esc_html(
                                  $this->options["client_id"]
                                ); ?></code>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e("Welcome Header Method", "zero-ad-network"); ?></th>
                            <td>
                                <?php echo esc_html(
                                  $this->options["output_method"] === "header"
                                    ? __("HTTP Response Header", "zero-ad-network")
                                    : __("HTML Meta Tag", "zero-ad-network")
                                ); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e("Enabled Features", "zero-ad-network"); ?></th>
                            <td>
                                <?php
                                $featureNames = [
                                  Constants::FEATURE["CLEAN_WEB"] => __("Clean Web ($6/month)", "zero-ad-network"),
                                  Constants::FEATURE["ONE_PASS"] => __("One Pass ($12/month)", "zero-ad-network")
                                ];
                                $enabled = [];
                                foreach ($this->options["features"] as $feature) {
                                  $enabled[] = $featureNames[$feature] ?? $feature;
                                }
                                echo esc_html(implode(", ", $enabled));
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e("Maximum Monthly Revenue", "zero-ad-network"); ?></th>
                            <td>
                                <strong>
                                    <?php
                                    $revenue = 0;
                                    foreach ($this->options["features"] as $feature) {
                                      if ($feature === Constants::FEATURE["CLEAN_WEB"]) {
                                        $revenue += 6;
                                      }
                                      if ($feature === Constants::FEATURE["ONE_PASS"]) {
                                        $revenue += 12;
                                      }
                                    }
                                    printf(
                                      /* translators: %s: Revenue amount */
                                      esc_html__("$%s per subscriber (based on engagement)", "zero-ad-network"),
                                      number_format($revenue, 0)
                                    );
                                    ?>
                                </strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="description" style="margin-top: 15px; max-width: 800px;">
                    <?php esc_html_e(
                      "Revenue is distributed monthly based on the time subscribers spend on your site compared to all partner sites. The more engaging your content, the more you earn!",
                      "zero-ad-network"
                    ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
  }

  /**
   * Render cache configuration page
   */
  public function renderCacheConfigPage(): void
  {
    if (!current_user_can("manage_options")) {
      wp_die(esc_html__("You do not have sufficient permissions to access this page.", "zero-ad-network"));
    }

    // Include the cache config template
    include ZERO_AD_NETWORK_PLUGIN_DIR . "templates/admin-cache-config.php";
  }

  /**
   * Render about page
   */
  public function renderAboutPage(): void
  {
    if (!current_user_can("manage_options")) {
      wp_die(esc_html__("You do not have sufficient permissions to access this page.", "zero-ad-network"));
    }

    // Include the about template
    include ZERO_AD_NETWORK_PLUGIN_DIR . "templates/admin-about.php";
  }

  /**
   * Enqueue admin assets
   */
  public function enqueueAdminAssets($hook): void
  {
    if (strpos($hook, "zeroad") === false) {
      return;
    }

    wp_enqueue_style(
      "zero-ad-admin-css",
      ZERO_AD_NETWORK_PLUGIN_URL . "assets/css/admin.css",
      [],
      ZERO_AD_NETWORK_VERSION
    );

    wp_enqueue_script(
      "zero-ad-admin-js",
      ZERO_AD_NETWORK_PLUGIN_URL . "assets/scripts/admin.js",
      ["jquery"],
      ZERO_AD_NETWORK_VERSION,
      true
    );
  }

  /**
   * Debug logging helper
   */
  private function debugLog(string $message): void
  {
    if (!empty($this->options["debug_mode"]) && defined("WP_DEBUG") && WP_DEBUG) {
      error_log("[Zero Ad Network - Config] " . $message);
    }
  }
}
