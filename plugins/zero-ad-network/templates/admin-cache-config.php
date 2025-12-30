<?php

if (!defined("ABSPATH")) {
  exit();
} ?>

<div class="wrap zeroad-cache-config">
    <h1><?php esc_html_e("Cache Configuration for Zero Ad Network", "zero-ad-network"); ?></h1>

    <div class="zeroad-info-box">
        <h3 class="zeroad-mt-0"><?php esc_html_e("Why Cache Configuration Matters", "zero-ad-network"); ?></h3>
        <p>
            <?php esc_html_e(
              "Zero Ad Network subscribers see different versions of your pages based on their subscription plan. Proper cache configuration ensures:",
              "zero-ad-network"
            ); ?>
        </p>
        <ul>
            <li><?php esc_html_e(
              "Clean Web subscribers don't see ads, cookie banners, or marketing popups",
              "zero-ad-network"
            ); ?></li>
            <li><?php esc_html_e("One Pass subscribers can access paywalled content", "zero-ad-network"); ?></li>
            <li><?php esc_html_e("Regular visitors see the standard version of your site", "zero-ad-network"); ?></li>
            <li><?php esc_html_e("Your server doesn't regenerate pages unnecessarily", "zero-ad-network"); ?></li>
        </ul>
    </div>

    <div class="zeroad-cache-explanation">
        <h2><?php esc_html_e("How It Works", "zero-ad-network"); ?></h2>
        
        <p class="description">
            <?php esc_html_e(
              "The Zero Ad Network plugin sends a special header called X-ZeroAd-Variant with every response. This header identifies which version of the page should be cached:",
              "zero-ad-network"
            ); ?>
        </p>

        <div class="zeroad-info-box">
            <h4 class="zeroad-mt-0"><?php esc_html_e("Cache Variant Examples:", "zero-ad-network"); ?></h4>
            <table class="widefat zeroad-variant-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e("Visitor Type", "zero-ad-network"); ?></th>
                        <th><?php esc_html_e("X-ZeroAd-Variant Header", "zero-ad-network"); ?></th>
                        <th><?php esc_html_e("What They See", "zero-ad-network"); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e("Regular Visitor", "zero-ad-network"); ?></strong></td>
                        <td><code>clean_web0-one_pass0</code></td>
                        <td><?php esc_html_e(
                          "Ads, cookie banners, paywalls (standard experience)",
                          "zero-ad-network"
                        ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e(
                          "Clean Web Subscriber ($6/mo)",
                          "zero-ad-network"
                        ); ?></strong></td>
                        <td><code>clean_web1-one_pass0</code></td>
                        <td><?php esc_html_e(
                          "No ads, no cookie banners, but paywalls still active",
                          "zero-ad-network"
                        ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e(
                          "One Pass Subscriber ($12/mo)",
                          "zero-ad-network"
                        ); ?></strong></td>
                        <td><code>clean_web0-one_pass1</code></td>
                        <td><?php esc_html_e(
                          "Paywalls removed, subscription content unlocked, but ads shown",
                          "zero-ad-network"
                        ); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e("Freedom Subscriber ($18/mo)", "zero-ad-network"); ?></strong></td>
                        <td><code>clean_web1-one_pass1</code></td>
                        <td><?php esc_html_e(
                          "No ads, no paywalls, full access (best experience)",
                          "zero-ad-network"
                        ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="description zeroad-mt-2">
            <strong><?php esc_html_e("Important:", "zero-ad-network"); ?></strong>
            <?php esc_html_e(
              "Your caching system (plugin, CDN, or server) must cache different versions of each page based on this X-ZeroAd-Variant header. Without proper configuration, all visitors might see the same cached version!",
              "zero-ad-network"
            ); ?>
        </p>
    </div>

    <hr class="zeroad-mt-3 zeroad-mb-3">

    <h2><?php esc_html_e("Configuration Examples", "zero-ad-network"); ?></h2>
    
    <p class="description zeroad-mb-2">
        <?php esc_html_e(
          "Choose the configuration that matches your setup. If you're not sure which caching system you use, check your plugins or contact your hosting provider.",
          "zero-ad-network"
        ); ?>
    </p>

    <!-- WordPress Cache Plugins -->
    <div class="za-accordion">
        
        <!-- WP Super Cache -->
        <div class="za-item">
            <div class="za-header"><?php esc_html_e("WP Super Cache", "zero-ad-network"); ?></div>
            <div class="za-body">
                <p><?php esc_html_e(
                  "WP Super Cache is automatically configured by our plugin. The X-ZeroAd-Variant value is added to the cache key.",
                  "zero-ad-network"
                ); ?></p>
                <p><strong><?php esc_html_e("✅ No manual configuration needed!", "zero-ad-network"); ?></strong></p>
                <p class="description">
                    <?php esc_html_e(
                      "Our plugin hooks into WP Super Cache's wp_cache_get_cookies_values filter to ensure proper variant caching.",
                      "zero-ad-network"
                    ); ?>
                </p>
            </div>
        </div>

        <!-- WP Rocket -->
        <div class="za-item">
            <div class="za-header"><?php esc_html_e("WP Rocket", "zero-ad-network"); ?></div>
            <div class="za-body">
                <p><?php esc_html_e("WP Rocket is automatically configured by our plugin.", "zero-ad-network"); ?></p>
                <p><strong><?php esc_html_e("✅ No manual configuration needed!", "zero-ad-network"); ?></strong></p>
                <p class="description">
                    <?php esc_html_e(
                      "Our plugin adds zeroad_variant to WP Rocket's dynamic cookie list for automatic cache variation.",
                      "zero-ad-network"
                    ); ?>
                </p>
            </div>
        </div>

        <!-- W3 Total Cache -->
        <div class="za-item">
            <div class="za-header"><?php esc_html_e("W3 Total Cache", "zero-ad-network"); ?></div>
            <div class="za-body">
                <p><?php esc_html_e(
                  "W3 Total Cache is automatically configured by our plugin.",
                  "zero-ad-network"
                ); ?></p>
                <p><strong><?php esc_html_e("✅ No manual configuration needed!", "zero-ad-network"); ?></strong></p>
                <p class="description">
                    <?php esc_html_e(
                      "Our plugin modifies the cache key using W3TC's filters to include the variant.",
                      "zero-ad-network"
                    ); ?>
                </p>
            </div>
        </div>

        <!-- LiteSpeed Cache -->
        <div class="za-item">
            <div class="za-header"><?php esc_html_e("LiteSpeed Cache", "zero-ad-network"); ?></div>
            <div class="za-body">
                <p><?php esc_html_e(
                  "LiteSpeed Cache is automatically configured by our plugin.",
                  "zero-ad-network"
                ); ?></p>
                <p><strong><?php esc_html_e("✅ No manual configuration needed!", "zero-ad-network"); ?></strong></p>
            </div>
        </div>

        <!-- Nginx -->
        <div class="za-item">
            <div class="za-header"><?php esc_html_e("Nginx (Server-Level Caching)", "zero-ad-network"); ?></div>
            <div class="za-body">
                <p><?php esc_html_e(
                  "If your hosting uses Nginx with fastcgi_cache or proxy_cache, add this configuration:",
                  "zero-ad-network"
                ); ?></p>
<pre><code class="language-nginx"># Nginx proxy_cache example
proxy_cache_path /var/cache/nginx keys_zone=pagecache:50m;

# Extract variant from header
map $http_x_zeroad_variant $zeroad_variant {
    default $http_x_zeroad_variant;
    "" "clean_web0-one_pass0";  # Default for non-subscribers
}

server {
    location / {
        # Pass variant to backend
        proxy_set_header X-ZeroAd-Variant $zeroad_variant;

        # Cache configuration
        proxy_cache pagecache;
        
        # IMPORTANT: Include variant in cache key
        proxy_cache_key "$scheme$host$request_uri::$zeroad_variant";
        
        proxy_pass http://backend;
    }
}</code></pre>
            </div>
        </div>

        <!-- Apache -->
        <div class="za-item">
            <div class="za-header"><?php esc_html_e("Apache (mod_cache)", "zero-ad-network"); ?></div>
            <div class="za-body">
                <p><?php esc_html_e("Add this to your Apache configuration or .htaccess:", "zero-ad-network"); ?></p>
<pre><code class="language-apacheconf"># Apache mod_cache configuration
CacheQuickHandler off
CacheEnable disk /

# Vary cache by X-ZeroAd-Variant header
CacheVaryByHeader X-ZeroAd-Variant

# Ensure header is preserved
Header always merge X-ZeroAd-Variant %{X-ZeroAd-Variant}e

# If using as reverse proxy:
ProxyPass / http://backend/
ProxyPassReverse / http://backend/
RequestHeader set X-ZeroAd-Variant %{X-ZeroAd-Variant}e</code></pre>
            </div>
        </div>

        <!-- Varnish -->
        <div class="za-item">
            <div class="za-header"><?php esc_html_e("Varnish Cache", "zero-ad-network"); ?></div>
            <div class="za-body">
                <p><?php esc_html_e("Add this to your Varnish VCL configuration:", "zero-ad-network"); ?></p>
<pre><code class="language-vcl"># Varnish VCL (4.x / 7.x)
vcl 4.0;

sub vcl_recv {
    # Extract variant header
    if (req.http.X-ZeroAd-Variant) {
        set req.http.X-ZeroAd-Variant = req.http.X-ZeroAd-Variant;
    } else {
        set req.http.X-ZeroAd-Variant = "clean_web0-one_pass0";
    }
}

sub vcl_hash {
    # Include variant in cache hash
    hash_data(req.http.X-ZeroAd-Variant);
}

sub vcl_backend_response {
    # Set Vary header
    set beresp.http.Vary = "X-ZeroAd-Variant";
}</code></pre>
            </div>
        </div>

        <!-- Cloudflare -->
        <div class="za-item">
            <div class="za-header"><?php esc_html_e("Cloudflare CDN", "zero-ad-network"); ?></div>
            <div class="za-body">
                <h4><?php esc_html_e("Configuration Steps:", "zero-ad-network"); ?></h4>
                <ol>
                    <li><?php esc_html_e("Log in to your Cloudflare dashboard", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Go to Caching → Cache Rules", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e("Create a new Cache Rule", "zero-ad-network"); ?></li>
                    <li><?php esc_html_e(
                      "Under 'Custom Cache Key', select 'Query String' → 'Include header'",
                      "zero-ad-network"
                    ); ?></li>
                    <li><?php esc_html_e("Add header: X-ZeroAd-Variant", "zero-ad-network"); ?></li>
                </ol>
                
                <p><strong><?php esc_html_e("Or use Cloudflare Workers:", "zero-ad-network"); ?></strong></p>
<pre><code class="language-javascript">addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
  const variant = request.headers.get('X-ZeroAd-Variant') || 'clean_web0-one_pass0';
  
  // Create cache key with variant
  const cacheKey = new Request(request.url + '?variant=' + variant, request);
  
  return fetch(request, {
    cf: {
      cacheKey: cacheKey
    }
  });
}</code></pre>
            </div>
        </div>

        <!-- AWS CloudFront -->
        <div class="za-item">
            <div class="za-header"><?php esc_html_e("AWS CloudFront (Lambda@Edge)", "zero-ad-network"); ?></div>
            <div class="za-body">
                <p><?php esc_html_e("Create a Lambda@Edge function for viewer requests:", "zero-ad-network"); ?></p>
<pre><code class="language-javascript">exports.handler = async (event) => {
  const request = event.Records[0].cf.request;
  
  // Get variant from header
  const variant = request.headers['x-zeroad-variant']
    ? request.headers['x-zeroad-variant'][0].value
    : 'clean_web0-one_pass0';
  
  // Add to cache key via query string
  request.querystring = request.querystring 
    ? request.querystring + '&variant=' + variant
    : 'variant=' + variant;
  
  return request;
};</code></pre>
            </div>
        </div>

    </div>

    <div class="zeroad-warning-box zeroad-mt-3">
        <h3 class="zeroad-mt-0"><?php esc_html_e("⚠️ Testing Your Cache Configuration", "zero-ad-network"); ?></h3>
        <p><?php esc_html_e(
          "After configuring your cache, test it to ensure it's working correctly:",
          "zero-ad-network"
        ); ?></p>
        <ol>
            <li><?php esc_html_e("Clear all caches (plugin cache, server cache, CDN cache)", "zero-ad-network"); ?></li>
            <li><?php esc_html_e(
              "Visit your site as a regular user (no Zero Ad subscription)",
              "zero-ad-network"
            ); ?></li>
            <li><?php esc_html_e("Check that ads and cookie banners appear normally", "zero-ad-network"); ?></li>
            <li><?php esc_html_e(
              "Install Zero Ad browser extension and subscribe to Clean Web",
              "zero-ad-network"
            ); ?></li>
            <li><?php esc_html_e("Visit your site again - ads and banners should be gone", "zero-ad-network"); ?></li>
            <li><?php esc_html_e(
              "Check browser dev tools → Network tab → Response Headers for X-ZeroAd-Variant",
              "zero-ad-network"
            ); ?></li>
        </ol>
        <p>
            <strong><?php esc_html_e("Need help?", "zero-ad-network"); ?></strong>
            <a href="https://docs.zeroad.network" target="_blank"><?php esc_html_e(
              "Visit our documentation →",
              "zero-ad-network"
            ); ?></a>
        </p>
    </div>
</div>