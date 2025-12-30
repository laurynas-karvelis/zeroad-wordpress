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

  // Delete user meta using WordPress API
  $users = get_users(["fields" => "ID"]);
  foreach ($users as $user_id) {
    delete_user_meta($user_id, "zeroad_welcome_dismissed");
  }

  // Clear any cached data
  wp_cache_flush();
}

/**
 * Multisite cleanup
 */
function zeroad_uninstall_multisite_cleanup()
{
  // Get all blog IDs using WordPress function
  $blog_ids = get_sites(["fields" => "ids"]);

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
