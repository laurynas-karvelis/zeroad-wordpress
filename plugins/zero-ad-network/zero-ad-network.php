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
 * Domain Path:       /languages
 *
 * @package ZeroAdNetwork
 */

// Exit if accessed directly.
if (!defined("ABSPATH")) {
  exit();
}

// Define plugin constants.
define("ZEROAD_VERSION", "0.14.0");
define("ZEROAD_PLUGIN_FILE", __FILE__);
define("ZEROAD_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("ZEROAD_PLUGIN_URL", plugin_dir_url(__FILE__));
define("ZEROAD_PLUGIN_BASENAME", plugin_basename(__FILE__));
define("ZEROAD_TEXT_DOMAIN", "zero-ad-network");

define("ZEROAD_DEFAULT_CACHE_TTL", 10);

/**
 * Improved autoloader with conflict prevention and error handling.
 *
 * @param string $class The class name to load.
 * @return bool True if class was loaded, false otherwise.
 */
function zeroad_autoloader($class)
{
  // Check if class already exists.
  if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
    return true;
  }

  $namespaces = [
    "ZeroAd\\WP\\" => ZEROAD_PLUGIN_DIR . "src/",
    "ZeroAd\\Token\\" => ZEROAD_PLUGIN_DIR . "vendor/zeroad.network/token/src/"
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
      return true;
    }
  }

  return false;
}

// Register autoloader if SPL is available.
if (function_exists("spl_autoload_register")) {
  spl_autoload_register("zeroad_autoloader", true, false);
}

/**
 * Activation hook - Check system requirements and set up plugin.
 */
register_activation_hook(__FILE__, function () {
  // Check PHP version.
  if (version_compare(PHP_VERSION, "7.2.0", "<")) {
    deactivate_plugins(ZEROAD_PLUGIN_BASENAME);
    wp_die(
      esc_html__("Zero Ad Network requires PHP 7.2 or higher. Please upgrade your PHP version.", ZEROAD_TEXT_DOMAIN),
      esc_html__("Plugin Activation Error", ZEROAD_TEXT_DOMAIN),
      ["back_link" => true]
    );
  }

  // Check sodium extension.
  if (!extension_loaded("sodium")) {
    deactivate_plugins(ZEROAD_PLUGIN_BASENAME);
    wp_die(
      esc_html__(
        "Zero Ad Network requires the Sodium PHP extension. Please install/enable libsodium (included in PHP 7.2+).",
        ZEROAD_TEXT_DOMAIN
      ),
      esc_html__("Plugin Activation Error", ZEROAD_TEXT_DOMAIN),
      ["back_link" => true]
    );
  }

  // Set default options (non-autoloaded for performance).
  $default_options = [
    "enabled" => false,
    "client_id" => "",
    "features" => [],
    "output_method" => "header",
    "cache_enabled" => true,
    "cache_ttl" => ZEROAD_DEFAULT_CACHE_TTL,
    "cache_prefix" => "zeroad:"
  ];

  if (!get_option(\ZeroAd\WP\Settings::OPTION_KEY)) {
    add_option(\ZeroAd\WP\Settings::OPTION_KEY, $default_options, "", "no"); // Not autoloaded.
  }

  // Clear any existing caches.
  wp_cache_flush();
});

/**
 * Deactivation hook - Clean up temporary data only.
 * Permanent data cleanup is handled in uninstall.php.
 */
register_deactivation_hook(__FILE__, function () {
  // Only clean up transients, not permanent settings.
  delete_transient("zeroad_site_instance");
  delete_transient("zeroad_cache_variant");
});

/**
 * Initialize the plugin.
 */
add_action("plugins_loaded", function () {
  // Load text domain for translations.
  // Note: WordPress.org automatically handles translations, but we load for local development.
  load_plugin_textdomain(ZEROAD_TEXT_DOMAIN, false, dirname(ZEROAD_PLUGIN_BASENAME) . "/languages");

  // Initialize the main config.
  try {
    \ZeroAd\WP\Config::instance()->run();
  } catch (\InvalidArgumentException $e) {
    zeroad_error_log("Configuration error: " . $e->getMessage());
    zeroad_show_admin_notice("error", "Configuration error: " . $e->getMessage());
  } catch (\RuntimeException $e) {
    zeroad_error_log("Runtime error: " . $e->getMessage());
    zeroad_show_admin_notice("error", "Runtime error: " . $e->getMessage());
  } catch (\Exception $e) {
    zeroad_error_log("Unexpected error: " . $e->getMessage());
    zeroad_show_admin_notice("error", "Unexpected error: " . $e->getMessage());
  }
});

/**
 * Helper function to show admin notices.
 *
 * @param string $type    Notice type: 'error', 'warning', 'success', 'info'.
 * @param string $message The message to display.
 */
function zeroad_show_admin_notice($type, $message)
{
  add_action("admin_notices", function () use ($type, $message) {
    if (current_user_can("manage_options")) {
      printf(
        '<div class="notice notice-%s"><p><strong>%s</strong> %s</p></div>',
        esc_attr($type),
        esc_html__("Zero Ad Network:", ZEROAD_TEXT_DOMAIN),
        esc_html($message)
      );
    }
  });
}
