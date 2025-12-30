<?php

if (!defined("WP_UNINSTALL_PLUGIN")) {
  exit();
}

function zeroad_uninstall_cleanup()
{
  delete_option("zeroad_token_options");

  delete_transient("zeroad_site_instance");
  delete_transient("zeroad_cache_variant");

  $users = get_users(["fields" => "ID"]);
  foreach ($users as $user_id) {
    delete_user_meta($user_id, "zeroad_welcome_dismissed");
  }

  wp_cache_flush();
}

function zeroad_uninstall_multisite_cleanup()
{
  $blog_ids = get_sites(["fields" => "ids"]);

  foreach ($blog_ids as $blog_id) {
    switch_to_blog($blog_id);
    zeroad_uninstall_cleanup();
    restore_current_blog();
  }
}

if (is_multisite()) {
  zeroad_uninstall_multisite_cleanup();
} else {
  zeroad_uninstall_cleanup();
}
