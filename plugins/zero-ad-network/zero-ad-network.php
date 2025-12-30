<?php

/**
 * Plugin Name:       Zero Ad Network
 * Plugin URI:        https://zeroad.network
 * Description:       An HTTP-header-based "access / entitlement token" plugin for Zero Ad Network partnering sites using WordPress.
 * Version:           0.14.0
 * Requires at least: 4.9
 * Requires PHP:      7.2
 * Author:            Explosive Brains Ltd.
 * License:           Apache 2.0
 * License URI:       https://www.apache.org/licenses/LICENSE-2.0.txt
 * Text Domain:       zero-ad-network
 */

if (!defined("ABSPATH")) {
  exit();
}

// Define plugin constants
define("ZERO_AD_NETWORK_VERSION", "0.14.0");
define("ZERO_AD_NETWORK_PLUGIN_FILE", __FILE__);
define("ZERO_AD_NETWORK_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("ZERO_AD_NETWORK_PLUGIN_URL", plugin_dir_url(__FILE__));
define("ZERO_AD_NETWORK_PLUGIN_BASENAME", plugin_basename(__FILE__));

// Autoloader
if (function_exists("spl_autoload_register")) {
  spl_autoload_register(function ($class) {
    $namespaces = [
      "ZeroAd\\WP\\" => ZERO_AD_NETWORK_PLUGIN_DIR . "src/",
      "ZeroAd\\Token\\" => ZERO_AD_NETWORK_PLUGIN_DIR . "vendor/zeroad.network/token/src/"
    ];

    foreach ($namespaces as $prefix => $base_dir) {
      $len = strlen($prefix);
      if (strncmp($prefix, $class, $len) !== 0) {
        continue;
      }

      $relative_class = substr($class, $len);
      $file = $base_dir . str_replace("\\", "/", $relative_class) . ".php";

      if (file_exists($file)) {
        require_once $file;
        return;
      }
    }
  });
}

/**
 * Activation hook - Check system requirements
 */
register_activation_hook(__FILE__, function () {
  // Check PHP version
  if (version_compare(PHP_VERSION, "7.2.0", "<")) {
    deactivate_plugins(ZERO_AD_NETWORK_PLUGIN_BASENAME);
    wp_die(
      esc_html__("Zero Ad Network requires PHP 7.2 or higher. Please upgrade your PHP version.", "zero-ad-network"),
      esc_html__("Plugin Activation Error", "zero-ad-network"),
      ["back_link" => true]
    );
  }

  // Check sodium extension
  if (!extension_loaded("sodium")) {
    deactivate_plugins(ZERO_AD_NETWORK_PLUGIN_BASENAME);
    wp_die(
      esc_html__(
        "Zero Ad Network requires the Sodium PHP extension. Please install/enable libsodium (included in PHP 7.2+).",
        "zero-ad-network"
      ),
      esc_html__("Plugin Activation Error", "zero-ad-network"),
      ["back_link" => true]
    );
  }

  // Set default options
  $default_options = [
    "enabled" => false,
    "client_id" => "",
    "features" => [],
    "output_method" => "header",
    "debug_mode" => false,
    "cache_enabled" => true,
    "cache_ttl" => 5,
    "cache_prefix" => "zeroad:"
  ];

  if (!get_option(\ZeroAd\WP\Settings::OPT_KEY)) {
    add_option(\ZeroAd\WP\Settings::OPT_KEY, $default_options);
  }

  // Log activation
  if (defined("WP_DEBUG") && WP_DEBUG && defined("WP_DEBUG_LOG") && WP_DEBUG_LOG) {
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Conditional debug logging
    error_log("Zero Ad Network plugin activated successfully");
  }
});

/**
 * Deactivation hook - Cleanup
 */
register_deactivation_hook(__FILE__, function () {
  // Clean up transients
  delete_transient("zeroad_site_instance");
  delete_transient("zeroad_cache_variant");

  // Log deactivation
  if (defined("WP_DEBUG") && WP_DEBUG && defined("WP_DEBUG_LOG") && WP_DEBUG_LOG) {
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Conditional debug logging
    error_log("Zero Ad Network plugin deactivated");
  }
});

/**
 * Initialize the plugin
 */
add_action("plugins_loaded", function () {
  // Note: load_plugin_textdomain() is not needed for plugins hosted on WordPress.org
  // WordPress automatically loads translations from translate.wordpress.org

  // Initialize the main config
  try {
    \ZeroAd\WP\Config::instance()->run();
  } catch (\Throwable $e) {
    if (defined("WP_DEBUG") && WP_DEBUG && defined("WP_DEBUG_LOG") && WP_DEBUG_LOG) {
      // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Conditional debug logging
      error_log("Zero Ad Network initialization error: " . $e->getMessage());
    }

    add_action("admin_notices", function () use ($e) {
      if (current_user_can("manage_options")) {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__("Zero Ad Network failed to initialize: ", "zero-ad-network");
        echo esc_html($e->getMessage());
        echo "</p></div>";
      }
    });
  }
});
