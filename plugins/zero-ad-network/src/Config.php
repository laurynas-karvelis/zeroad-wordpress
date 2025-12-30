<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\Token\Site;

/**
 * Config - Main plugin coordinator
 *
 * This class coordinates the different components:
 * - Settings: Handles WordPress settings API
 * - AdminPages: Renders admin pages
 * - Renderer: Handles frontend token processing
 */
class Config
{
  private static $instance = null;

  private $options;
  private $settings;
  private $adminPages;
  private $renderer;
  private $siteCache = null;

  private function __construct()
  {
    $this->options = get_option(Settings::OPT_KEY, Settings::getDefaults());

    $this->settings = new Settings($this->options);
    $this->adminPages = new AdminPages($this->options);
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

  /**
   * Run the plugin
   */
  public function run(): void
  {
    // Admin hooks
    add_action("admin_menu", [$this, "addAdminPages"]);
    add_action("admin_init", [$this->settings, "register"]);
    add_action("admin_enqueue_scripts", [$this, "enqueueAdminAssets"]);
    add_action("admin_notices", [$this, "maybeShowWelcomeNotice"]);

    // Initialize Site instance for token verification
    add_action("init", [$this, "initializeSite"], 1);

    // Hook to update options after save
    add_action("update_option_" . Settings::OPT_KEY, [$this, "onOptionsUpdate"], 10, 2);

    // Register renderer (handles token verification and feature toggling)
    $this->renderer->run();
  }

  /**
   * Initialize Site instance with configuration
   */
  public function initializeSite(): void
  {
    try {
      // Refresh options in case they were updated
      $this->options = get_option(Settings::OPT_KEY, $this->options);
      $this->updateComponents();

      // Only create Site instance if plugin is fully configured
      if (
        !empty($this->options["enabled"]) &&
        !empty($this->options["client_id"]) &&
        !empty($this->options["features"]) &&
        count($this->options["features"]) > 0
      ) {
        // Reuse cached Site instance if available
        if ($this->siteCache === null) {
          // Prepare cache config
          $cacheConfig = null;
          if (!empty($this->options["cache_enabled"])) {
            $cacheConfig = [
              "ttl" => intval($this->options["cache_ttl"] ?? 5),
              "prefix" => $this->options["cache_prefix"] ?? "zeroad:"
            ];
          }

          $this->siteCache = new Site([
            "clientId" => $this->options["client_id"],
            "features" => $this->options["features"],
            "cacheConfig" => $cacheConfig
          ]);

          $this->debugLog(
            "Site instance created with Client ID: " .
              substr($this->options["client_id"], 0, 8) .
              "..." .
              ($cacheConfig ? " (APCu caching enabled)" : " (caching disabled)")
          );
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
          printf(esc_html__("Configuration error: %s", "zero-ad-network"), esc_html($e->getMessage()));
          echo ' <a href="' . esc_url(admin_url("admin.php?page=zeroad-token")) . '">';
          esc_html_e("Check settings →", "zero-ad-network");
          echo "</a></p></div>";
        });
      }
    }
  }

  /**
   * Handle options update
   */
  public function onOptionsUpdate($old_value, $new_value): void
  {
    // Clear cached Site instance when options change
    $this->siteCache = null;
    $this->options = $new_value;
    $this->updateComponents();

    // Reinitialize if init already happened
    if (did_action("init")) {
      $this->initializeSite();
    }
  }

  /**
   * Update all components with new options
   */
  private function updateComponents(): void
  {
    $this->adminPages->setOptions($this->options);
    $this->renderer->options($this->options);
  }

  /**
   * Add admin menu pages
   */
  public function addAdminPages(): void
  {
    add_menu_page(
      __("Zero Ad Network", "zero-ad-network"),
      __("Zero Ad Network", "zero-ad-network"),
      "manage_options",
      "zeroad-token",
      [$this->adminPages, "renderSettingsPage"],
      "dashicons-admin-site-alt3",
      80
    );

    add_submenu_page(
      "zeroad-token",
      __("Settings", "zero-ad-network"),
      __("Settings", "zero-ad-network"),
      "manage_options",
      "zeroad-token",
      [$this->adminPages, "renderSettingsPage"]
    );

    add_submenu_page(
      "zeroad-token",
      __("Cache Configuration", "zero-ad-network"),
      __("Cache Configuration", "zero-ad-network"),
      "manage_options",
      "zeroad-cache-config",
      [$this->adminPages, "renderCacheConfigPage"]
    );

    add_submenu_page(
      "zeroad-token",
      __("About", "zero-ad-network"),
      __("About", "zero-ad-network"),
      "manage_options",
      "zeroad-about",
      [$this->adminPages, "renderAboutPage"]
    );
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
      error_log("[Zero Ad Network] " . $message);
    }
  }
}
