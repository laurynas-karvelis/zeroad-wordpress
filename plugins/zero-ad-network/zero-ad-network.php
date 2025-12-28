<?php

/**
 * Plugin Name:       Zero Ad Network
 * Plugin URI:        https://zeroad.network
 * Description:       An HTTP-header-based "access / entitlement token" plugin for Zero Ad Network partnering sites using WordPress.
 * Version:           0.13.13
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

if (function_exists("spl_autoload_register")) {
  spl_autoload_register(function ($class) {
    $namespaces = [
      "ZeroAd\\WP\\" => __DIR__ . "/src/",
      "ZeroAd\\Token\\" => __DIR__ . "/vendor/zeroad.network/token/src/"
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

  define("ZERO_AD_NETWORK_PLUGIN_VERSION", "0.13.13");
  define("ZERO_AD_NETWORK_PLUGIN_URL", plugin_dir_url(__FILE__));

  \ZeroAd\WP\Config::instance()->run();
}
