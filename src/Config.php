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
      "enabled" => false,
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
      $this->renderer->options($this->options);

      if (
        !empty($this->options["enabled"]) &&
        !empty($this->options["client_id"]) &&
        !empty($this->options["features"]) &&
        count($this->options["features"])
      ) {
        $this->renderer->site(
          new Site([
            "clientId" => $this->options["client_id"],
            "features" => $this->options["features"]
          ])
        );
      } else {
        $this->renderer->site(null);
      }
    } catch (\Throwable $e) {
      // give up
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
      "enabled",
      "Plugin enabled",
      function () {
        $opts = get_option(self::OPT_KEY, []);
        $enabled = !empty($opts["enabled"]);
        ?>
        <input type="checkbox"
               name="<?php echo esc_attr(self::OPT_KEY); ?>[enabled]"
               value="1"
               <?php checked($enabled); ?>>
        <?php
      },
      self::OPT_KEY,
      "zeroad_main"
    );

    add_settings_field(
      "client_id",
      "Client ID",
      function () {
        $opts = get_option(self::OPT_KEY, []);
        $v = isset($opts["client_id"]) ? esc_attr($opts["client_id"]) : "";
        echo "<input type='text' name='" .
          esc_attr(self::OPT_KEY) .
          "[client_id]' value='" .
          esc_attr($v) .
          "' style='width:60%'/>";
        echo "<p class='description'>Your site Client ID value that was provided during site registration</p>";
      },
      self::OPT_KEY,
      "zeroad_main"
    );

    add_settings_field(
      "features",
      "Features to enable",
      function () {
        $opts = get_option(self::OPT_KEY, []);
        $v = isset($opts["features"]) ? $opts["features"] : [];
        $features = Constants::FEATURES;

        $descriptions = [
          Constants::FEATURES["CLEAN_WEB"] =>
            "Disable advertisements, cookie consent screens, 3rd party non-functional trackers, marketing popups.",
          Constants::FEATURES["ONE_PASS"] => "Disable content paywalls; Enable access to content behind subscriptions."
        ];

        foreach ($features as $key => $value) {
          $checked = in_array($value, $v) ? "checked" : "";
          echo '<label style="display:block;">';
          echo sprintf(
            '<input type="checkbox" name="%s[features][]" value="%s" %s /> %s - %s',
            esc_attr(self::OPT_KEY),
            esc_attr($value),
            esc_attr($checked),
            esc_html($key),
            esc_html($descriptions[$value])
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
            <select name="<?php echo esc_attr(self::OPT_KEY); ?>[output_method]">
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

    // Field `enabled`
    $out["enabled"] = isset($input["enabled"]) ? 1 : 0;

    // Field `client_id`
    $out["client_id"] = isset($input["client_id"]) ? sanitize_text_field($input["client_id"]) : "";

    // Field `output_method`
    $out["output_method"] = in_array($input["output_method"] ?? "", ["header", "meta"], true)
      ? $input["output_method"]
      : "header";

    // Field `features`
    if (isset($input["features"])) {
      foreach ($input["features"] as $key => $value) {
        $input["features"][$key] = (int) $value;
      }
    }

    $out["features"] = array_intersect($input["features"] ?? [], array_values(Constants::FEATURES));

    // Validation: must have at least one selected
    if (empty($out["features"])) {
      add_settings_error("features", "features_error", "You must select at least one feature.", "error");
    }

    // Validation complete
    $this->options = $out;

    if (did_action("init")) {
      $this->initializeSite();
    }

    return $out;
  }

  public function addAdminPage(): void
  {
    add_menu_page("Zero Ad Network", "Zero Ad Network", "manage_options", "zeroad-token", [$this, "renderAdminPage"]);

    add_submenu_page("zeroad-token", "Main Settings", "Main Settings", "manage_options", "zeroad-token", [
      $this,
      "renderAdminPage"
    ]);

    add_submenu_page(
      "zeroad-token",
      "Proxy / CDN Configs",
      "Proxy / CDN Configs",
      "manage_options",
      "zeroad-proxy-configs",
      [$this, "renderProxyConfigPage"]
    );
  }

  public function renderAdminPage(): void
  {
    if (!current_user_can("manage_options")) {
      wp_die("Unauthorized");

      if (function_exists("settings_errors")) {
        settings_errors(); // This prints WP admin notices for the current settings page
      }
    } ?>
    
        <div class="wrap">
            <h1>Zero Ad Network</h1>
            <?php settings_errors(); ?>
            <hr>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPT_KEY);
                do_settings_sections(self::OPT_KEY);
                submit_button();?>
            </form>
        </div>
  <?php
  }

  public function renderProxyConfigPage(): void
  {
    wp_enqueue_style(
      "zeroad-wordpress-prism-stylesheet",
      "https://cdnjs.cloudflare.com/ajax/libs/prism/1.30.0/themes/prism.min.css",
      [],
      "1.30.0"
    );
    wp_enqueue_script(
      "zeroad-wordpress-prism-min",
      "https://cdnjs.cloudflare.com/ajax/libs/prism/1.30.0/prism.min.js",
      [], // Dependencies (optional)
      "1.30.0", // Version number
      true // Load in footer
    );
    wp_enqueue_script(
      "zeroad-wordpress-prism-autoloader",
      "https://cdnjs.cloudflare.com/ajax/libs/prism/1.30.0/plugins/autoloader/prism-autoloader.min.js",
      [], // Dependencies (optional)
      "1.30.0", // Version number
      true // Load in footer
    );?>

<style>
    .za-accordion { margin-top: 25px; }
    .za-item { border: 1px solid #ccc; margin-bottom: 10px; border-radius: 4px; }
    .za-header {
        background: #f1f1f1;
        padding: 12px;
        cursor: pointer;
        font-weight: bold;
        user-select: none;
        border-radius: 4px 4px 0 0;
    }
    .za-body {
        display: none;
        padding: 15px;
        background: #fff;
    }
    .za-body.open { display: block; border-radius: 0 0 4px 4px; }

    pre {
        border: 1px solid #ddd;
        border-radius: 4px;
    }
</style>

<div class="wrap">
    <h1>Zero Ad Network – Proxy / CDN Cache Configuration Examples</h1>

    <p>
        The caching infrastructure must vary on the deterministic feature-flag header:
    </p>

<pre><code class="language-none">X-ZeroAd-Variant: clean_web{0|1}-one_pass{0|1}</code></pre>

    <div class="za-accordion">

        <!-- Apache -->
        <div class="za-item">
            <div class="za-header">Apache</div>
            <div class="za-body">
<pre><code class="language-apacheconf"># Apache (mod_cache) example
CacheQuickHandler off
CacheEnable disk /

# Vary cache by header
CacheVaryByHeader X-ZeroAd-Variant

# Ensure header is passed from backend
Header always merge X-ZeroAd-Variant %{X-ZeroAd-Variant}e

# If acting as a reverse proxy:
ProxyPass / http://backend/
ProxyPassReverse / http://backend/
RequestHeader set X-ZeroAd-Variant %{X-ZeroAd-Variant}e
</code></pre>
            </div>
        </div>

        <!-- Nginx -->
        <div class="za-item">
            <div class="za-header">Nginx</div>
            <div class="za-body">
<pre><code class="language-nginx"># Nginx proxy_cache example
proxy_cache_path /var/cache/nginx keys_zone=pagecache:50m;

map $http_x_zeroad_variant $variant {
    default $http_x_zeroad_variant;
}

server {
    location / {
        proxy_set_header X-ZeroAd-Variant $variant;

        proxy_cache pagecache;
        proxy_cache_key "$scheme$host$request_uri::$variant";

        proxy_pass http://backend;
    }
}
</code></pre>
            </div>
        </div>

        <!-- Caddy -->
        <div class="za-item">
            <div class="za-header">Caddy</div>
            <div class="za-body">
<pre><code class="language-caddyfile"># Caddy (with http.cache plugin)
:80 {
    route {
        header {
            defer
            +X-ZeroAd-Variant {header.X-ZeroAd-Variant}
        }

        cache {
            vary X-ZeroAd-Variant
        }

        reverse_proxy backend:80
    }
}
</code></pre>
            </div>
        </div>

        <!-- Varnish -->
        <div class="za-item">
            <div class="za-header">Varnish</div>
            <div class="za-body">
<pre><code class="language-vcl"># Varnish VCL (4.x / 7.x)
vcl_recv {
    if (req.http.X-ZeroAd-Variant) {
        set req.http.X-ZeroAd-Variant = req.http.X-ZeroAd-Variant;
    }

    # Include in hash
    hash_data(req.http.X-ZeroAd-Variant);
}

vcl_backend_response {
    # Make Vary explicit
    set beresp.http.Vary = "X-ZeroAd-Variant";
}
</code></pre>
            </div>
        </div>

        <!-- CDN Providers -->
        <div class="za-item">
            <div class="za-header">CDN Providers</div>
            <div class="za-body">

<h3>Cloudflare</h3>
<pre><code class="language-none"># Cache Rules → Custom Cache Key
Include header:
http.request.headers["X-ZeroAd-Variant"]
</code></pre>

<h3>Fastly</h3>
<pre><code class="language-vcl">sub vcl_hash {
    hash_data(req.http.X-ZeroAd-Variant);
}
</code></pre>

<h3>Akamai</h3>
<pre><code class="language-none"># Akamai Property Manager
Cache Key → Add Header:
X-ZeroAd-Variant
</code></pre>

<h3>AWS CloudFront (Lambda@Edge)</h3>
<pre><code class="language-javascript">exports.handler = async (event) => {
  const req = event.Records[0].cf.request;

  const variant = req.headers['x-zeroad-variant']
      ? req.headers['x-zeroad-variant'][0].value
      : 'clean_web0-one_pass0';

  req.headers['x-zeroad-variant'] = [
      { key: 'X-ZeroAd-Variant', value: variant }
  ];

  // Inject into cache key
  req.querystring = "variant=" + variant;

  return req;
};
</code></pre>

            </div>
        </div>

    </div>
</div>

<script>
document.querySelectorAll(".za-header").forEach(header => {
    header.addEventListener("click", () => {
        const body = header.nextElementSibling;
        body.classList.toggle("open");
    });
});
</script>

<?php
  }
}
