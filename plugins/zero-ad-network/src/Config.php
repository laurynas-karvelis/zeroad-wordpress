<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\Token\Site;

class Config
{
  private static $instance = null;
  private $options;
  private $settings;
  private $admin_pages;
  private $renderer;
  private $site_cache = null;

  private function __construct(?Settings $settings = null, ?AdminPages $admin_pages = null, ?Renderer $renderer = null)
  {
    $this->options = get_option(Settings::OPTION_KEY, Settings::getDefaults());

    // Support dependency injection for testing.
    $this->settings = $settings ?? new Settings($this->options);
    $this->admin_pages = $admin_pages ?? new AdminPages($this->options);
    $this->renderer = $renderer ?? new Renderer();
    $this->renderer->setOptions($this->options);
  }

  public static function instance(): Config
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function setInstance(?Config $instance): void
  {
    self::$instance = $instance;
  }

  public function run(): void
  {
    // Admin hooks.
    add_action("admin_menu", [$this, "addAdminPages"]);
    add_action("admin_init", [$this->settings, "register"]);
    add_action("admin_enqueue_scripts", [$this, "enqueueAdminAssets"]);
    add_action("admin_notices", [$this, "maybeShowWelcomeNotice"]);

    // AJAX handler with rate limiting.
    add_action("wp_ajax_zeroad_dismiss_welcome", [$this, "ajaxDismissWelcome"]);

    // Initialize Site instance.
    add_action("init", [$this, "initializeSite"], 1);

    // Hook to update options after save.
    add_action("update_option_" . Settings::OPTION_KEY, [$this, "onOptionsUpdate"], 10, 2);

    // Register renderer.
    $this->renderer->run();
  }

  public function initializeSite(): void
  {
    try {
      // Refresh options in case they were updated.
      $this->options = get_option(Settings::OPTION_KEY, $this->options);
      $this->updateComponents();

      // Only create Site instance if plugin is fully configured.
      if (
        !empty($this->options["enabled"]) &&
        !empty($this->options["client_id"]) &&
        !empty($this->options["features"]) &&
        count($this->options["features"]) > 0
      ) {
        if ($this->site_cache === null) {
          $cache_config = null;
          if (!empty($this->options["cache_enabled"])) {
            $cache_config = [
              "ttl" => intval($this->options["cache_ttl"] ?? ZEROAD_DEFAULT_CACHE_TTL),
              "prefix" => $this->options["cache_prefix"] ?? "zeroad:"
            ];
          }

          $this->site_cache = new Site([
            "clientId" => $this->options["client_id"],
            "features" => $this->options["features"],
            "cacheConfig" => $cache_config
          ]);
        }

        $this->renderer->setSite($this->site_cache);
      } else {
        $this->renderer->setSite(null);
      }
    } catch (\InvalidArgumentException $e) {
      $this->renderer->setSite(null);
      $this->showConfigError($e->getMessage());
    } catch (\RuntimeException $e) {
      $this->renderer->setSite(null);
      $this->showConfigError($e->getMessage());
    }
  }

  private function showConfigError(string $message): void
  {
    if (is_admin() && current_user_can("manage_options")) {
      add_action("admin_notices", function () use ($message) {
        printf(
          '<div class="notice notice-error"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
          esc_html__("Zero Ad Network:", "zero-ad-network"),
          /* translators: %s: Error message */
          sprintf(esc_html__("Configuration error: %s", "zero-ad-network"), esc_html($message)),
          esc_url(admin_url("admin.php?page=zeroad-token")),
          esc_html__("Check settings →", "zero-ad-network")
        );
      });
    }
  }

  public function onOptionsUpdate($old_value, $new_value): void
  {
    // Clear cached Site instance when options change.
    $this->site_cache = null;
    $this->options = $new_value;
    $this->updateComponents();

    // Reinitialize if init already happened.
    if (did_action("init")) {
      $this->initializeSite();
    }
  }

  private function updateComponents(): void
  {
    $this->admin_pages->setOptions($this->options);
    $this->renderer->setOptions($this->options);
  }

  public function addAdminPages(): void
  {
    add_menu_page(
      __("Zero Ad Network", "zero-ad-network"),
      __("Zero Ad Network", "zero-ad-network"),
      "manage_options",
      "zeroad-token",
      [$this->admin_pages, "renderSettingsPage"],
      "dashicons-admin-site-alt3",
      80
    );

    add_submenu_page(
      "zeroad-token",
      __("Settings", "zero-ad-network"),
      __("Settings", "zero-ad-network"),
      "manage_options",
      "zeroad-token",
      [$this->admin_pages, "renderSettingsPage"]
    );

    add_submenu_page(
      "zeroad-token",
      __("Cache Configuration", "zero-ad-network"),
      __("Cache Configuration", "zero-ad-network"),
      "manage_options",
      "zeroad-cache-config",
      [$this->admin_pages, "renderCacheConfigPage"]
    );

    add_submenu_page(
      "zeroad-token",
      __("About", "zero-ad-network"),
      __("About", "zero-ad-network"),
      "manage_options",
      "zeroad-about",
      [$this->admin_pages, "renderAboutPage"]
    );
  }

  public function maybeShowWelcomeNotice(): void
  {
    if (!current_user_can("manage_options")) {
      return;
    }

    $dismissed = get_user_meta(get_current_user_id(), "zeroad_welcome_dismissed", true);

    // Only show if plugin is not configured yet and not dismissed.
    if (empty($this->options["client_id"]) && !$dismissed) {
      $nonce = wp_create_nonce("zeroad_dismiss_welcome"); ?>
            <div class="notice notice-info is-dismissible zeroad-welcome-notice" data-dismiss-nonce="<?php echo esc_attr(
              $nonce
            ); ?>">
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
                            /* translators: %s: URL to Zero Ad Network registration page */
                            __(
                              'Register your site at <a href="%s" target="_blank" rel="noopener noreferrer">zeroad.network</a> to get your Client ID',
                              "zero-ad-network"
                            ),
                            ["a" => ["href" => [], "target" => [], "rel" => []]]
                          ),
                          esc_url("https://zeroad.network")
                        ); ?>
                    </li>
                    <li>
                        <?php printf(
                          wp_kses(
                            /* translators: %s: URL to plugin settings page */
                            __('Enter your Client ID in the <a href="%s">plugin settings</a>', "zero-ad-network"),
                            ["a" => ["href" => []]]
                          ),
                          esc_url(admin_url("admin.php?page=zeroad-token"))
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
                    <a href="https://docs.zeroad.network" target="_blank" rel="noopener noreferrer" class="button button-primary">
                        <?php esc_html_e("View Documentation", "zero-ad-network"); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url("admin.php?page=zeroad-token")); ?>" class="button">
                        <?php esc_html_e("Go to Settings", "zero-ad-network"); ?>
                    </a>
                </p>
            </div>
            <?php
    }
  }

  public function ajaxDismissWelcome(): void
  {
    // Check capability FIRST (before nonce).
    if (!current_user_can("manage_options")) {
      wp_send_json_error("Unauthorized");
      return;
    }

    // Rate limiting.
    if (!zeroad_check_rate_limit("dismiss_welcome", 10, 60)) {
      wp_send_json_error("Rate limit exceeded");
      return;
    }

    // Then check nonce.
    check_ajax_referer("zeroad_dismiss_welcome", "nonce");

    // Save dismissed state.
    update_user_meta(get_current_user_id(), "zeroad_welcome_dismissed", true);

    wp_send_json_success();
  }

  public function enqueueAdminAssets(string $hook_suffix): void
  {
    // Only load on our plugin pages.
    $our_pages = ["zeroad-token", "zeroad-cache-config", "zeroad-about"];
    $is_our_page = false;

    foreach ($our_pages as $page) {
      if (strpos($hook_suffix, $page) !== false) {
        $is_our_page = true;
        break;
      }
    }

    if (!$is_our_page) {
      return;
    }

    wp_enqueue_style("zeroad-admin-css", ZEROAD_PLUGIN_URL . "assets/css/admin.css", [], ZEROAD_VERSION);

    wp_enqueue_script(
      "zeroad-admin-js",
      ZEROAD_PLUGIN_URL . "assets/scripts/admin.js",
      ["jquery"],
      ZEROAD_VERSION,
      true
    );

    wp_localize_script("zeroad-admin-js", "zeroadAdmin", [
      "ajaxurl" => admin_url("admin-ajax.php"),
      "nonce" => wp_create_nonce("zeroad_admin")
    ]);
  }
}
