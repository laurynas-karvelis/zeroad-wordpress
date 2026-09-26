<?php

if (!defined("ABSPATH")) {
    exit();
}

$zeroadActivePage = "zeroad-cache-config";
?>
<div class="wrap zeroad-admin">
    <header class="zeroad-page-header">
        <span class="zeroad-page-icon dashicons dashicons-performance" aria-hidden="true"></span>
        <div>
    <h1><?php esc_html_e("Page cache setup", "zero-ad-network"); ?></h1>
    <p class="zeroad-intro"><?php esc_html_e("Configure your cache layers, then verify your setup.", "zero-ad-network"); ?></p>
        </div>
    </header>
    <?php include __DIR__ . "/admin-navigation.php"; ?>
    <div class="zeroad-content">
    <div class="notice notice-warning inline">
        <h2><?php esc_html_e("Token requests must reach WordPress", "zero-ad-network"); ?></h2>
        <p><?php esc_html_e("At every page cache, bypass both cache reads and writes when Better-Web-Token is present, and forward that header unchanged to WordPress. This includes invalid tokens. Only verified tokens unlock Freedom; a cookie or variant header never proves access.", "zero-ad-network"); ?></p>
        <p><?php esc_html_e("The plugin marks token responses private and no-store and signals compatible WordPress caches not to store them. It cannot stop a cache hit served before WordPress loads. If your host cannot bypass token requests, disable its full-page cache on participating pages. Static assets and object caching can remain enabled.", "zero-ad-network"); ?></p>
    </div>

    <details class="zeroad-panel zeroad-disclosure" open><summary><?php esc_html_e("WordPress page caches", "zero-ad-network"); ?></summary>
    <p><?php esc_html_e("For a PHP-based page cache, replace the existing WP_CACHE definition in wp-config.php with the following, before WordPress loads. Do not add a second definition. This skips advanced-cache.php for token requests; it does not bypass web-server rewrites or a CDN.", "zero-ad-network"); ?></p>
<pre><code>$freedomTokenRequest = array_key_exists('HTTP_BETTER_WEB_TOKEN', $_SERVER);

define('WP_CACHE', !$freedomTokenRequest);

if ($freedomTokenRequest &amp;&amp; !defined('DONOTCACHEPAGE')) {
    define('DONOTCACHEPAGE', true);
}</code></pre>
    <table class="widefat striped">
        <thead><tr><th scope="col"><?php esc_html_e("Cache", "zero-ad-network"); ?></th><th scope="col"><?php esc_html_e("Required setup", "zero-ad-network"); ?></th></tr></thead>
        <tbody>
            <tr><td>WP Super Cache</td><td><?php esc_html_e("Use PHP/Simple delivery with the early bypass above. Expert/rewrite delivery also needs a server-level exclusion before serving cached files.", "zero-ad-network"); ?></td></tr>
            <tr><td>WP Rocket / W3 Total Cache / WP Fastest Cache</td><td><?php esc_html_e("Use the early PHP bypass wherever advanced-cache.php is used. Any direct cached-file delivery must also exclude token requests at the server. Plugin activation alone does not configure that exclusion.", "zero-ad-network"); ?></td></tr>
            <tr><td>LiteSpeed Cache</td><td><?php esc_html_e("Configure a server-level token-header exclusion before cache lookup. The plugin also sends LiteSpeed no-cache signals for responses that reach WordPress.", "zero-ad-network"); ?></td></tr>
            <tr><td>Apache / CloudFront / managed hosting</td><td><?php esc_html_e("Ask the host to bypass cache lookup and storage on token requests and preserve the header. If the cache cannot do this, disable HTML caching for participating pages. Do not select a cached subscriber page using a client-supplied cookie or header.", "zero-ad-network"); ?></td></tr>
        </tbody>
    </table>

    </details>
    <details class="zeroad-panel zeroad-disclosure"><summary>Nginx</summary>
    <p><?php esc_html_e("Add this map in the http context. Add the matching directives to your existing proxy or FastCGI location, preserving all other cache exclusions. A value of 0 is still treated as a supplied, invalid token.", "zero-ad-network"); ?></p>
<pre><code>map $http_better_web_token $freedom_skip_cache {
    ""      0;
    default 1;
}

# In an existing proxy_cache location:
proxy_cache_bypass $freedom_skip_cache;
proxy_no_cache $freedom_skip_cache;
proxy_set_header Better-Web-Token $http_better_web_token;

# Or in an existing fastcgi_cache location:
fastcgi_cache_bypass $freedom_skip_cache;
fastcgi_no_cache $freedom_skip_cache;
fastcgi_param HTTP_BETTER_WEB_TOKEN $http_better_web_token;</code></pre>
    <p><?php esc_html_e("Nginx treats an empty header like an absent header here. Neither grants access. Non-empty tokens always bypass. Do not override the origin's private/no-store response headers.", "zero-ad-network"); ?></p>

    </details>
    <details class="zeroad-panel zeroad-disclosure"><summary>Varnish</summary>
    <p><?php esc_html_e("Place this before any cache lookup or early return in vcl_recv. Preserve the header on the backend request.", "zero-ad-network"); ?></p>
<pre><code>if (req.http.Better-Web-Token) {
    return (pass);
}</code></pre>

    </details>
    <details class="zeroad-panel zeroad-disclosure"><summary>Cloudflare</summary>
    <p><?php esc_html_e("Create a Cache Rule matching the expression below and set Cache eligibility to Bypass cache. Ensure later rules, Workers, and origin overrides do not re-enable caching or remove the token header.", "zero-ad-network"); ?></p>
<pre><code>http.request.headers.truncated or has_key(http.request.headers, "better-web-token")</code></pre>

    </details>
    <section class="zeroad-panel">
    <h2><?php esc_html_e("Verify your installation", "zero-ad-network"); ?></h2>
    <ol>
        <li><?php esc_html_e("Clear page caches after configuration or included-content changes. Warm a public page without a token, then visit the same URL with a valid hostname-bound token and no cookies. It must reach WordPress and unlock only included content.", "zero-ad-network"); ?></li>
        <li><?php esc_html_e("Repeat the valid request: it must still bypass the page cache, with Cache-Control: private, no-store. Return without the token: the ordinary protected page must remain intact.", "zero-ad-network"); ?></li>
        <li><?php esc_html_e("Try missing, invalid, expired, and wrong-host tokens, including forged zeroad_variant=subscriber1 cookies and X-ZeroAd-Variant headers. They must never unlock content. Ordinary WordPress membership permissions still apply.", "zero-ad-network"); ?></li>
        <li><?php esc_html_e("Check each cache layer's logs or status headers. A MISS alone is not proof of bypass: the response might still be stored. Test again after warming the cache.", "zero-ad-network"); ?></li>
    </ol>
    <p><?php esc_html_e("Verification-result caching (APCu) is separate from HTML caching. Expired tokens are rejected subject to the SDK's 60-second clock tolerance. Cancellation or account closure cannot revoke an already-signed offline token before its expiry. The plugin does not create subscriber cache cookies or shared subscriber pages.", "zero-ad-network"); ?></p>
    <p><a href="https://zeroad.network/docs/site-integration/remove-ads/wordpress"><?php esc_html_e("WordPress integration guide", "zero-ad-network"); ?></a></p>
    </section>
    </div>
</div>
