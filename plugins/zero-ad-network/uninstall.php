<?php
/**
 * Uninstall handler for Zero Ad Network
 *
 * This file is called when the plugin is uninstalled via WordPress admin.
 */

// Exit if accessed directly or not uninstalling
if (!defined("WP_UNINSTALL_PLUGIN")) {
  exit();
}

/**
 * Clean up plugin data
 */
function zeroad_uninstall_cleanup()
{
  // Delete plugin options
  delete_option("zeroad_token_options");

  // Delete any transients
  delete_transient("zeroad_site_instance");
  delete_transient("zeroad_cache_variant");

  // Clean up any other stored data
  global $wpdb;

  // Delete any custom user meta if added in future versions
  $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'zeroad_%'");

  // Log cleanup if debug is enabled
  if (defined("WP_DEBUG") && WP_DEBUG) {
    error_log("Zero Ad Network: Plugin data cleaned up during uninstall");
  }
}

/**
 * Multisite cleanup
 */
function zeroad_uninstall_multisite_cleanup()
{
  global $wpdb;

  // Get all blog IDs
  $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

  foreach ($blog_ids as $blog_id) {
    switch_to_blog($blog_id);
    zeroad_uninstall_cleanup();
    restore_current_blog();
  }
}

// Execute cleanup
if (is_multisite()) {
  zeroad_uninstall_multisite_cleanup();
} else {
  zeroad_uninstall_cleanup();
}
