<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

if (!defined("ABSPATH")) {
  exit();
}

abstract class Action
{
  private static $plugins;

  abstract public static function enabled(array $ctx): bool;
  abstract public static function run(): void;
  abstract public static function outputBufferCallback(string $html): string;

  /**
   * Inject a string into the <head> (before </head>), or at start if head not present.
   */
  protected static function injectIntoHead(string $html, string $inject): string
  {
    if (stripos($html, "</head>") !== false) {
      return preg_replace("#</head>#i", $inject . "</head>", $html, 1);
    }
    // Fallback: prepend to document
    return $inject . $html;
  }

  protected static function addFilters(array $list): void
  {
    foreach ($list as $value) {
      [$name, $fn, $priority] = $value;
      add_filter($name, $fn, $priority ?? null);
    }
  }

  protected static function removeActions(array $list): void
  {
    foreach ($list as $value) {
      [$name, $fn, $priority] = $value;
      remove_action($name, $fn, $priority);
    }
  }

  protected static function removeShortcodes(array $list): void
  {
    foreach ($list as $value) {
      remove_shortcode($value);
    }
  }

  protected static function disablePlugins(array $list): void
  {
    foreach ($list as $rules) {
      [$textDomain, $prefix, $shortcodes] = $rules;
      self::disablePlugin($textDomain, $prefix, $shortcodes);
    }
  }

  protected static function disablePlugin(?string $textDomain, string $prefix, array $shortcodes = []): void
  {
    $found = empty($textDomain) ? true : self::isPluginActiveByTextDomain($textDomain);

    if ($found) {
      self::removeCallbacksByPrefix($prefix);
      self::removeShortcodes($shortcodes);
    }
  }

  /**
   * Removes all WordPress actions/filters whose callback name, method, or class
   * starts with a given prefix.
   *
   * @param string $prefix String to match at the start of function/method/class name.
   */
  protected static function removeCallbacksByPrefix(string $prefix)
  {
    global $wp_filter;

    if (empty($wp_filter) || !is_array($wp_filter)) {
      return;
    }

    foreach ($wp_filter as $hook_name => $wp_hook) {
      // For WP 4.7+: each $wp_hook is a WP_Hook object whose ->callbacks is an array.
      if (is_object($wp_hook) && isset($wp_hook->callbacks)) {
        $priorities = $wp_hook->callbacks;
      } else {
        // Older WP versions used arrays.
        $priorities = $wp_hook;
      }

      if (empty($priorities) || !is_array($priorities)) {
        continue;
      }

      foreach ($priorities as $priority => $callbacks) {
        if (!is_array($callbacks)) {
          continue;
        }

        foreach ($callbacks as $id => $callback_data) {
          if (!isset($callback_data["function"])) {
            continue;
          }

          $func = $callback_data["function"];

          //
          // Case 1: Simple named function: "myplugin_do_something"
          //
          if (is_string($func)) {
            if (stripos($func, $prefix) === 0) {
              remove_filter($hook_name, $func, $priority);
            }
            continue;
          }

          //
          // Case 2: Instance method: [ $object, 'method_name' ]
          //
          if (is_array($func) && is_object($func[0]) && isset($func[1])) {
            $class = get_class($func[0]);
            $method = $func[1];

            if (stripos($class, $prefix) === 0 || stripos($method, $prefix) === 0) {
              remove_filter($hook_name, [$func[0], $method], $priority);
            }
            continue;
          }

          //
          // Case 3: Static method: [ 'ClassName', 'method_name' ]
          //
          if (is_array($func) && is_string($func[0]) && isset($func[1])) {
            $class = $func[0];
            $method = $func[1];

            if (stripos($class, $prefix) === 0 || stripos($method, $prefix) === 0) {
              remove_filter($hook_name, [$class, $method], $priority);
            }
            continue;
          }

          //
          // Case 4: Static method as string "ClassName::method"
          //
          if (is_string($func) && strpos($func, "::") !== false) {
            [$class, $method] = explode("::", $func, 2);

            if (stripos($class, $prefix) === 0 || stripos($method, $prefix) === 0) {
              remove_filter($hook_name, $func, $priority);
            }
          }
        }
      }
    }
  }

  protected static function isPluginActiveByTextDomain(string $name)
  {
    if (empty(self::$plugins)) {
      if (!function_exists("get_plugins")) {
        require_once ABSPATH . "wp-admin/includes/plugin.php";
      }

      if (!function_exists("is_plugin_active")) {
        require_once ABSPATH . "wp-admin/includes/plugin.php";
      }

      self::$plugins = get_plugins();
    }

    foreach (self::$plugins as $path => $data) {
      if (isset($data["TextDomain"]) && strtolower($data["TextDomain"]) === strtolower($name)) {
        // Now check if this plugin file is active

        // Single-site
        if (is_plugin_active($path)) {
          return true;
        }

        // Network active
        if (function_exists("is_plugin_active_for_network") && is_plugin_active_for_network($path)) {
          return true;
        }

        return false;
      }
    }

    return false; // No plugin matched this name
  }
}
