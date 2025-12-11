<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\Token\Site;
use ZeroAd\Token\Constants;

class Config
{
  private static $instance;

  private $renderer;
  private $cacheInterceptor;

  public const OPT_KEY = "zeroad_token_options";
  private $options;

  private function __construct()
  {
    $this->options = get_option(self::OPT_KEY, [
      "client_id" => "",
      "features" => [],
      "output_method" => "header"
    ]);

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

  public function run(): void
  {
    add_action("admin_notices", [$this, "maybeShowSodiumNotice"]);
    add_action("admin_menu", [$this, "addAdminPage"]);
    add_action("admin_init", [$this, "registerSettings"]);

    // Register renderer actions
    $this->renderer->run();

    // Early init
    add_action("init", [$this, "initializeSite"], 1);
  }

  public function initializeSite(): void
  {
    try {
      if (
        !empty($this->options["client_id"]) &&
        !empty($this->options["features"]) &&
        count($this->options["features"])
      ) {
        $this->renderer->options($this->options);
        $this->renderer->site(
          new Site([
            "clientId" => $this->options["client_id"],
            "features" => $this->options["features"]
          ])
        );
      }
    } catch (\Throwable $e) {
      error_log("ZeroAd Token: new Site() failed: " . $e->getMessage());
    }
  }

  public function maybeShowSodiumNotice(): void
  {
    if (!extension_loaded("sodium")) {
      if (!current_user_can("manage_options")) {
        return;
      }
      echo '<div class="notice notice-error"><p>ZeroAd Token plugin: PHP extension <strong>sodium</strong> is not enabled. Install/enable libsodium (PHP >= 7.2) and reload.</p></div>';
    }
  }

  public function registerSettings(): void
  {
    register_setting(self::OPT_KEY, self::OPT_KEY, [$this, "validateOptions"]);

    add_settings_section(
      "zeroad_main",
      "Settings",
      function () {
        echo "<p>Settings for Zero Ad Network integrated site behavior</p>";
      },
      self::OPT_KEY
    );

    add_settings_field(
      "client_id",
      "Your site Client ID value",
      function () {
        $opts = get_option(self::OPT_KEY, []);
        $v = isset($opts["client_id"]) ? esc_attr($opts["client_id"]) : "";
        echo "<input type='text' name='" . self::OPT_KEY . "[client_id]' value='{$v}' style='width:60%'/>";
        echo "<p class='description'>Your site Client ID value that was provided during site registration</p>";
      },
      self::OPT_KEY,
      "zeroad_main"
    );

    add_settings_field(
      "features",
      "Select site Features to enable",
      function () {
        $opts = get_option(self::OPT_KEY, []);
        $v = isset($opts["features"]) ? $opts["features"] : [];
        $features = Constants::FEATURES;

        foreach ($features as $key => $value) {
          $checked = in_array($value, $v) ? "checked" : "";
          echo '<label style="display:block;">';
          echo sprintf(
            '<input type="checkbox" name="%s[features][]" value="%s" %s /> %s',
            self::OPT_KEY,
            esc_attr($value),
            $checked,
            esc_html($key)
          );
          echo "</label>";
        }
        echo "<p class='description'>At least one site feature needs to be selected</p>";
      },
      self::OPT_KEY,
      "zeroad_main"
    );

    add_settings_field(
      "output_method",
      "Output Method",
      function () {
        $opts = get_option(self::OPT_KEY, []);
        $v = $opts["output_method"] ?? "header";
        ?>
            <select name="<?php echo self::OPT_KEY; ?>[output_method]">
                <option value="header" <?php selected($v, "header"); ?>>HTTP Header</option>
                <option value="meta" <?php selected($v, "meta"); ?>>HTML Meta Tag</option>
            </select>
            <p class="description">Choose how the "X-Better-Web-Welcome" value should be output: as an HTTP response header or as a meta tag in the page.</p>
    
      <?php
      },
      self::OPT_KEY,
      "zeroad_main"
    );
  }

  public function validateOptions($input)
  {
    $out = [];
    $out["client_id"] = isset($input["client_id"]) ? sanitize_text_field($input["client_id"]) : "";
    $out["output_method"] = in_array($input["output_method"] ?? "", ["header", "meta"], true)
      ? $input["output_method"]
      : "header";

    if (isset($input["features"])) {
      // Cast values to integer
      foreach ($input["features"] as $key => $value) {
        $input["features"][$key] = (int) $value;
      }
    }

    // Filter only allowed keys
    $out["features"] = array_intersect($input["features"] ?? [], array_values(Constants::FEATURES));

    // Validation: must have at least one selected
    if (empty($out["features"])) {
      add_settings_error("features", "features_error", "You must select at least one feature.", "error");
    }

    $this->options = $out;

    if (did_action("init")) {
      $this->initializeSite();
    }

    return $out;
  }

  public function addAdminPage(): void
  {
    add_options_page("Zero Ad Network", "Zero Ad Network", "manage_options", "zeroad-token", [
      $this,
      "renderAdminPage"
    ]);
  }

  public function renderAdminPage(): void
  {
    if (!current_user_can("manage_options")) {
      wp_die("Unauthorized");
    } ?>
        <div class="wrap">
            <h1>Zero Ad Network</h1>
            <hr>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPT_KEY);
                do_settings_sections(self::OPT_KEY);
                submit_button();?>
            </form>
            <h2>Cache configuration</h2>
            <p>The plugin uses a deterministic <strong>X-ZeroAd-Variant</strong> header to vary cached pages based on feature flags.</p>
            <p>This means:</p>
            <ul>
                <li>Edge caches (Varnish, nginx, CDN) should be configured to vary on the <code>X-ZeroAd-Variant</code> header.</li>
                <li>The variant string format is: <code>clean_web{0|1}-one_pass{0|1}</code>.</li>
            </ul>
            <p>Example: <code>X-ZeroAd-Variant: clean_web1-one_pass0</code></p>
            <p>Benefits:</p>
            <ul>
                <li>Reduces cache explosion — one cached page per combination of feature flags, not per user token.</li>
                <li>Stateless approach — all decisions are made server-side based on the signed token.</li>
                <li>Works seamlessly with your existing caching infrastructure.</li>
            </ul>
            <p>See the plugin README for sample Varnish / Nginx / CDN snippets to handle <code>X-ZeroAd-Variant</code> based caching.</p>
        </div>
  <?php
  }
}
