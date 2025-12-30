<?php

declare(strict_types=1);

namespace ZeroAd\WP\Actions;

if (!defined("ABSPATH")) {
  exit();
}

/**
 * Base class for feature actions
 *
 * Each feature (Advertisements, CookieConsent, etc.) extends this class
 * and implements the enabled(), run(), and outputBufferCallback() methods.
 */
abstract class Action
{
  private static $plugins = null;
  private static $pluginsChecked = [];

  /**
   * Check if this action should be enabled based on token context
   *
   * @param array $ctx Token context with feature flags
   * @return bool True if action should run
   */
  abstract public static function enabled(array $ctx): bool;

  /**
   * Run the action - register hooks, disable plugins, enqueue assets
   */
  abstract public static function run(): void;

  /**
   * Process HTML output buffer to remove/modify elements
   *
   * @param string $html The HTML content to process
   * @return string Modified HTML content
   */
  abstract public static function outputBufferCallback(string $html): string;

  /**
   * Optional: Register plugin-specific overrides
   * Override this method if the action needs to hook into specific plugins
   *
   * @param array $ctx Token context with feature flags
   */
  public static function registerPluginOverrides(array $ctx): void
  {
    // Default: no overrides
  }

  /**
   * Inject a string into the <head> section (before </head>),
   * or at start if head tag not present.
   *
   * @param string $html The full HTML content
   * @param string $inject The string to inject
   * @return string Modified HTML
   */
  protected static function injectIntoHead(string $html, string $inject): string
  {
    if (stripos($html, "</head>") !== false) {
      return preg_replace("#</head>#i", $inject . "</head>", $html, 1);
    }

    // Fallback: prepend to document
    return $inject . $html;
  }

  /**
   * Add multiple WordPress filters at once
   *
   * @param array $list Array of [hook_name, callback, priority] arrays
   */
  protected static function addFilters(array $list): void
  {
    foreach ($list as $value) {
      if (!is_array($value) || count($value) < 2) {
        continue;
      }

      $name = $value[0];
      $fn = $value[1];
      $priority = $value[2] ?? 10;

      add_filter($name, $fn, $priority);
    }
  }

  /**
   * Remove multiple WordPress actions at once
   *
   * @param array $list Array of [hook_name, callback, priority] arrays
   */
  protected static function removeActions(array $list): void
  {
    foreach ($list as $value) {
      if (!is_array($value) || count($value) < 2) {
        continue;
      }

      $name = $value[0];
      $fn = $value[1];
      $priority = $value[2] ?? 10;

      remove_action($name, $fn, $priority);
    }
  }

  /**
   * Remove multiple shortcodes at once
   *
   * @param array $list Array of shortcode names
   */
  protected static function removeShortcodes(array $list): void
  {
    foreach ($list as $shortcode) {
      if (!empty($shortcode)) {
        remove_shortcode($shortcode);
      }
    }
  }

  /**
   * Disable multiple plugins by their text domain and prefix
   *
   * @param array $list Array of [textDomain, prefix, shortcodes] arrays
   */
  protected static function disablePlugins(array $list): void
  {
    foreach ($list as $rules) {
      if (!is_array($rules) || count($rules) < 2) {
        continue;
      }

      $textDomain = $rules[0] ?? null;
      $prefix = $rules[1] ?? "";
      $shortcodes = $rules[2] ?? [];

      self::disablePlugin($textDomain, $prefix, $shortcodes);
    }
  }

  /**
   * Disable a single plugin by removing its hooks and shortcodes
   *
   * @param string|null $textDomain Plugin text domain (null to skip check)
   * @param string $prefix Callback name prefix to match
   * @param array $shortcodes Array of shortcode names to remove
   */
  protected static function disablePlugin(?string $textDomain, string $prefix, array $shortcodes = []): void
  {
    // Check if plugin is active (skip if textDomain is null)
    $found = empty($textDomain) ? true : self::isPluginActiveByTextDomain($textDomain);

    if ($found) {
      self::removeCallbacksByPrefix($prefix);
      self::removeShortcodes($shortcodes);

      if (defined("WP_DEBUG") && WP_DEBUG) {
        error_log("[Zero Ad Network] Disabled plugin: {$textDomain} (prefix: {$prefix})");
      }
    }
  }

  /**
   * Run multiple regex replacements on HTML
   *
   * @param string $html The HTML to process
   * @param array $regexRules Array of regex patterns
   * @return string Modified HTML
   */
  protected static function runReplacements(string $html, array $regexRules = []): string
  {
    foreach ($regexRules as $regexRule) {
      // Use error suppression and check for errors
      $result = @preg_replace($regexRule, "", $html);

      // Check for regex errors
      if ($result === null) {
        $error = preg_last_error();
        $errorMessages = [
          PREG_NO_ERROR => "No error",
          PREG_INTERNAL_ERROR => "Internal error",
          PREG_BACKTRACK_LIMIT_ERROR => "Backtrack limit exceeded",
          PREG_RECURSION_LIMIT_ERROR => "Recursion limit exceeded",
          PREG_BAD_UTF8_ERROR => "Bad UTF8",
          PREG_BAD_UTF8_OFFSET_ERROR => "Bad UTF8 offset"
        ];

        $errorMsg = $errorMessages[$error] ?? "Unknown error";

        if (defined("WP_DEBUG") && WP_DEBUG) {
          error_log("[Zero Ad Network] Regex error: {$errorMsg} - Pattern: {$regexRule}");
        }

        continue;
      }

      $html = $result;
    }

    return $html;
  }

  /**
   * Remove all WordPress actions/filters whose callback name, method, or class
   * starts with a given prefix (case-insensitive).
   *
   * @param string $prefix String to match at the start of function/method/class name
   */
  protected static function removeCallbacksByPrefix(string $prefix): void
  {
    global $wp_filter;

    if (empty($wp_filter) || !is_array($wp_filter) || empty($prefix)) {
      return;
    }

    foreach ($wp_filter as $hook_name => $wp_hook) {
      // For WP 4.7+: each $wp_hook is a WP_Hook object
      if (is_object($wp_hook) && isset($wp_hook->callbacks)) {
        $priorities = $wp_hook->callbacks;
      } else {
        // Older WP versions used arrays
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
          $shouldRemove = false;

          // Case 1: Simple named function string
          if (is_string($func)) {
            if (stripos($func, $prefix) === 0) {
              $shouldRemove = true;
              remove_filter($hook_name, $func, $priority);
            }
          }
          // Case 2: Instance method array [object, method]
          elseif (is_array($func) && count($func) === 2 && is_object($func[0]) && isset($func[1])) {
            $class = get_class($func[0]);
            $method = $func[1];

            if (stripos($class, $prefix) === 0 || stripos($method, $prefix) === 0) {
              $shouldRemove = true;
              remove_filter($hook_name, [$func[0], $method], $priority);
            }
          }
          // Case 3: Static method array [ClassName, method]
          elseif (is_array($func) && count($func) === 2 && is_string($func[0]) && isset($func[1])) {
            $class = $func[0];
            $method = $func[1];

            if (stripos($class, $prefix) === 0 || stripos($method, $prefix) === 0) {
              $shouldRemove = true;
              remove_filter($hook_name, [$class, $method], $priority);
            }
          }
          // Case 4: Static method string "ClassName::method"
          elseif (is_string($func) && strpos($func, "::") !== false) {
            $parts = explode("::", $func, 2);
            if (count($parts) === 2) {
              [$class, $method] = $parts;

              if (stripos($class, $prefix) === 0 || stripos($method, $prefix) === 0) {
                $shouldRemove = true;
                remove_filter($hook_name, $func, $priority);
              }
            }
          }

          if ($shouldRemove && defined("WP_DEBUG") && WP_DEBUG) {
            error_log("[Zero Ad Network] Removed callback from {$hook_name}: " . json_encode($func));
          }
        }
      }
    }
  }

  /**
   * Check if a plugin is active by its text domain
   *
   * @param string $name Plugin text domain
   * @return bool True if plugin is active
   */
  protected static function isPluginActiveByTextDomain(string $name): bool
  {
    // Check cache first
    if (isset(self::$pluginsChecked[$name])) {
      return self::$pluginsChecked[$name];
    }

    // Load plugin functions if needed
    if (self::$plugins === null) {
      if (!function_exists("get_plugins")) {
        require_once ABSPATH . "wp-admin/includes/plugin.php";
      }

      self::$plugins = get_plugins();
    }

    $result = false;

    foreach (self::$plugins as $path => $data) {
      if (isset($data["TextDomain"]) && strtolower($data["TextDomain"]) === strtolower($name)) {
        // Check if this plugin file is active
        if (function_exists("is_plugin_active") && is_plugin_active($path)) {
          $result = true;
          break;
        }

        // Check network active
        if (function_exists("is_plugin_active_for_network") && is_plugin_active_for_network($path)) {
          $result = true;
          break;
        }

        // Found but not active
        break;
      }
    }

    // Cache the result
    self::$pluginsChecked[$name] = $result;

    return $result;
  }

  /**
   * Debug logging helper
   *
   * @param string $message Message to log
   */
  protected static function debugLog(string $message): void
  {
    $options = get_option(\ZeroAd\WP\Config::OPT_KEY, []);

    if (!empty($options["debug_mode"]) && defined("WP_DEBUG") && WP_DEBUG) {
      $calledClass = get_called_class();
      $className = substr($calledClass, strrpos($calledClass, "\\") + 1);
      error_log("[Zero Ad Network - {$className}] " . $message);
    }
  }
}
