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
    <p class="zeroad-intro"><?php esc_html_e("Make sure subscriber requests reach WordPress, then test your setup.", "zero-ad-network"); ?></p>
        </div>
    </header>
    <?php include __DIR__ . "/admin-navigation.php"; ?>
    <div class="zeroad-content">
    <div class="notice notice-warning inline">
        <h2><?php esc_html_e("Token requests must reach WordPress", "zero-ad-network"); ?></h2>
        <p><?php esc_html_e("At every page cache, skip both cache reads and writes when a request has a Better-Web-Token header, even an invalid one. Forward the header to WordPress unchanged. Only a verified token unlocks Freedom. A cookie or variant header never does.", "zero-ad-network"); ?></p>
        <p><?php esc_html_e("The plugin marks those responses private and no-store, and tells compatible WordPress caches not to store them. It can't stop a cached page served before WordPress loads. If your host can't skip token requests, turn off its full-page cache on participating pages. Static assets and object caching can stay on.", "zero-ad-network"); ?></p>
    </div>

    <details class="zeroad-panel zeroad-disclosure" open><summary><?php esc_html_e("WordPress page caches", "zero-ad-network"); ?></summary>
    <p><?php esc_html_e("For a PHP page cache, replace your existing WP_CACHE definition in wp-config.php with the code below. Don't add a second definition. This skips advanced-cache.php for token requests. It doesn't bypass web-server rewrites or a CDN.", "zero-ad-network"); ?></p>
<pre><code>$freedomTokenRequest = array_key_exists('HTTP_BETTER_WEB_TOKEN', $_SERVER);

define('WP_CACHE', !$freedomTokenRequest);

if ($freedomTokenRequest &amp;&amp; !defined('DONOTCACHEPAGE')) {
    define('DONOTCACHEPAGE', true);
}</code></pre>
    <table class="widefat striped">
        <thead><tr><th scope="col"><?php esc_html_e("Cache", "zero-ad-network"); ?></th><th scope="col"><?php esc_html_e("Required setup", "zero-ad-network"); ?></th></tr></thead>
        <tbody>
            <tr><td>WP Super Cache</td><td><?php esc_html_e("Use PHP/Simple delivery with the bypass above. Expert (rewrite) delivery also needs a server rule before cached files are served.", "zero-ad-network"); ?></td></tr>
            <tr><td>WP Rocket / W3 Total Cache / WP Fastest Cache</td><td><?php esc_html_e("Use the bypass above wherever advanced-cache.php runs. Direct cached-file delivery also needs a server rule for token requests. Activating the cache plugin doesn't set that up.", "zero-ad-network"); ?></td></tr>
            <tr><td>LiteSpeed Cache</td><td><?php esc_html_e("Add a server rule that excludes token requests before the cache lookup. The plugin also sends LiteSpeed no-cache signals for responses that reach WordPress.", "zero-ad-network"); ?></td></tr>
            <tr><td>Apache / CloudFront / managed hosting</td><td><?php esc_html_e("Ask your host to skip cache lookup and storage for token requests, and keep the header. If they can't, turn off HTML caching on participating pages. Never pick a cached subscriber page based on a cookie or header the visitor sends.", "zero-ad-network"); ?></td></tr>
        </tbody>
    </table>

    </details>
    <details class="zeroad-panel zeroad-disclosure"><summary>Nginx</summary>
    <p><?php esc_html_e("Add this map in the http block. Then add the matching lines to your existing proxy or FastCGI location, and keep your other cache exclusions. A value of 0 still counts as a supplied, invalid token.", "zero-ad-network"); ?></p>
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
    <p><?php esc_html_e("Nginx treats an empty header like a missing one here. Neither grants access. Any non-empty token skips the cache. Don't override the private and no-store headers WordPress sends.", "zero-ad-network"); ?></p>

    </details>
    <details class="zeroad-panel zeroad-disclosure"><summary>Varnish</summary>
    <p><?php esc_html_e("Put this in vcl_recv, before any cache lookup or early return. Keep the header on the backend request.", "zero-ad-network"); ?></p>
<pre><code>if (req.http.Better-Web-Token) {
    return (pass);
}</code></pre>

    </details>
    <details class="zeroad-panel zeroad-disclosure"><summary>Cloudflare</summary>
    <p><?php esc_html_e("Create a Cache Rule with the expression below, and set Cache eligibility to Bypass cache. Check that later rules, Workers and origin overrides don't turn caching back on or remove the token header.", "zero-ad-network"); ?></p>
<pre><code>http.request.headers.truncated or has_key(http.request.headers, "better-web-token")</code></pre>

    </details>
    <section class="zeroad-panel">
    <h2><?php esc_html_e("Test your setup", "zero-ad-network"); ?></h2>
    <p><?php esc_html_e("Clear all page caches first, and again whenever you change cache settings or included content. Then test the same URL:", "zero-ad-network"); ?></p>
    <ol>
        <li><?php esc_html_e("Load it without a token, to warm the cache.", "zero-ad-network"); ?></li>
        <li><?php esc_html_e("Load it with a valid token and no cookies, for example with Test in your browser. It must reach WordPress and unlock only included content.", "zero-ad-network"); ?></li>
        <li><?php esc_html_e("Repeat the token request. It must still skip the cache, with Cache-Control: private, no-store.", "zero-ad-network"); ?></li>
        <li><?php esc_html_e("Load it without the token again. The ordinary page, including any paywall, must be unchanged.", "zero-ad-network"); ?></li>
        <li><?php esc_html_e("Try invalid, expired and wrong-host tokens, a forged zeroad_variant=subscriber1 cookie and a forged X-ZeroAd-Variant header. None may unlock content. Ordinary WordPress membership permissions still apply.", "zero-ad-network"); ?></li>
        <li><?php esc_html_e("Check each cache layer's logs or status headers. A MISS alone isn't proof: the response might still be stored.", "zero-ad-network"); ?></li>
    </ol>
    <p><?php esc_html_e("APCu verification caching is separate from page caching. Expired tokens are rejected, give or take the SDK's 60-second clock tolerance. Cancelling a membership or closing an account can't revoke an already-signed token before it expires. The plugin never creates subscriber cache cookies or shared subscriber pages.", "zero-ad-network"); ?></p>
    <p><a href="https://zeroad.network/docs/site-integration/remove-ads/wordpress#page-caches-and-cdns"><?php esc_html_e("WordPress page-cache guide", "zero-ad-network"); ?></a></p>
    </section>
    </div>
</div>
