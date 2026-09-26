<?php

if (!defined("ABSPATH")) {
    exit();
}

$zeroadActivePage = "zeroad-config";
?>
<div class="wrap zeroad-admin">
    <header class="zeroad-page-header">
        <span class="zeroad-page-icon dashicons dashicons-admin-settings" aria-hidden="true"></span>
        <div>
    <h1><?php esc_html_e("Zero Ad Network", "zero-ad-network"); ?></h1>
    <p class="zeroad-intro"><?php esc_html_e("Connect your publisher account and configure the subscriber experience on this site.", "zero-ad-network"); ?></p>
        </div>
    </header>
    <?php include __DIR__ . "/admin-navigation.php"; ?>
    <?php settings_errors(); ?>
    <div class="zeroad-layout">
        <div class="zeroad-main">
            <section class="zeroad-panel zeroad-status-panel" aria-labelledby="zeroad-status-title">
                <h2 id="zeroad-status-title"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span> <?php esc_html_e("Site status", "zero-ad-network"); ?></h2>
                <p><strong class="<?php echo $enabled ? "zeroad-status zeroad-status-enabled" : "zeroad-status"; ?>"><?php echo esc_html($enabled ? __("Enabled", "zero-ad-network") : ($hasPublisher ? __("Disabled", "zero-ad-network") : __("Setup needed", "zero-ad-network"))); ?></strong></p>
                <p><?php echo esc_html($enabled
                    ? __("Your saved settings enable the integration. Verify a subscriber visit and your page cache before relying on it. This status does not confirm platform registration or earnings.", "zero-ad-network")
                    : __("Enter your Publisher ID, enable the integration, and save your settings to get started.", "zero-ad-network")); ?></p>
            </section>
            <form class="zeroad-panel zeroad-settings" method="post" action="options.php">
                <?php
                settings_fields(\ZeroAd\WP\Settings::OPTION_KEY);
                do_settings_sections(\ZeroAd\WP\Settings::OPTION_KEY);
                submit_button(__("Save settings", "zero-ad-network"));
                ?>
            </form>
        </div>
        <aside class="zeroad-sidebar" aria-label="<?php esc_attr_e("Setup help", "zero-ad-network"); ?>">
            <section class="zeroad-panel">
                <h2><span class="dashicons dashicons-list-view" aria-hidden="true"></span> <?php esc_html_e("Finish your setup", "zero-ad-network"); ?></h2>
                <ol class="zeroad-steps" role="list">
                    <li><a href="https://zeroad.network/dashboard"><?php esc_html_e("Get your Publisher ID", "zero-ad-network"); ?></a><p><?php esc_html_e("Use the same account ID across your sites. A paid subscription is not required.", "zero-ad-network"); ?></p></li>
                    <li><a href="<?php echo esc_url(admin_url("admin.php?page=zeroad-cache-config")); ?>"><?php esc_html_e("Configure page cache bypass", "zero-ad-network"); ?></a><p><?php esc_html_e("Requests with a subscriber token must reach WordPress.", "zero-ad-network"); ?></p></li>
                    <li><strong><?php esc_html_e("Choose included content", "zero-ad-network"); ?></strong><p><?php esc_html_e("If you sell access, use the Freedom access box in the post or page editor. Supported membership integrations are required.", "zero-ad-network"); ?></p></li>
                    <li><strong><?php esc_html_e("Test a subscriber visit", "zero-ad-network"); ?></strong><p><?php esc_html_e("Use Test in your browser from your publisher dashboard. Test access earns nothing.", "zero-ad-network"); ?></p></li>
                </ol>
            </section>
            <section class="zeroad-panel">
                <h2><span class="dashicons dashicons-performance" aria-hidden="true"></span> <?php esc_html_e("Verification cache", "zero-ad-network"); ?></h2>
                <p><?php echo esc_html($apcuAvailable && !empty($options["cache_enabled"])
                    ? __("APCu is available and enabled for verification results.", "zero-ad-network")
                    : __("Verification runs per request. APCu is optional and is not currently in use.", "zero-ad-network")); ?></p>
                <p class="description"><?php esc_html_e("This is separate from your page cache. Token requests must bypass page caching even when APCu is enabled.", "zero-ad-network"); ?></p>
            </section>
            <p><a href="https://zeroad.network/docs/site-integration/remove-ads/wordpress"><?php esc_html_e("WordPress integration guide", "zero-ad-network"); ?></a></p>
        </aside>
    </div>
</div>
