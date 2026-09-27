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
    <p class="zeroad-intro"><?php esc_html_e("Connect this website to your publisher account, and set up the subscriber experience.", "zero-ad-network"); ?></p>
        </div>
    </header>
    <?php include __DIR__ . "/admin-navigation.php"; ?>
    <?php settings_errors(); ?>
    <div class="zeroad-layout">
        <div class="zeroad-main">
            <section class="zeroad-panel zeroad-status-panel" aria-labelledby="zeroad-status-title">
                <h2 id="zeroad-status-title"><span class="dashicons dashicons-admin-site-alt3" aria-hidden="true"></span> <?php esc_html_e("Website status", "zero-ad-network"); ?></h2>
                <p><strong class="<?php echo $enabled ? "zeroad-status zeroad-status-enabled" : "zeroad-status"; ?>"><?php echo esc_html($enabled ? __("Enabled", "zero-ad-network") : ($hasPublisher ? __("Disabled", "zero-ad-network") : __("Setup needed", "zero-ad-network"))); ?></strong></p>
                <p><?php echo esc_html($enabled
                    ? __("The plugin is on. Test a subscriber visit and your page cache before relying on it. This status does not confirm platform registration or earnings.", "zero-ad-network")
                    : __("To start, paste your Publisher ID, select Enable Plugin, and save.", "zero-ad-network")); ?></p>
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
                    <li><a href="https://zeroad.network/sites#publisher-id"><?php esc_html_e("Copy your Publisher ID", "zero-ad-network"); ?></a><p><?php esc_html_e("Use the same ID on all your websites. You don't need a paid membership.", "zero-ad-network"); ?></p></li>
                    <li><a href="<?php echo esc_url(admin_url("admin.php?page=zeroad-cache-config")); ?>"><?php esc_html_e("Configure page cache bypass", "zero-ad-network"); ?></a><p><?php esc_html_e("Subscriber requests must skip your page cache and reach WordPress.", "zero-ad-network"); ?></p></li>
                    <li><strong><?php esc_html_e("Choose included content", "zero-ad-network"); ?></strong><p><?php esc_html_e("If you sell access, check Freedom access in the post or page editor. Paid Memberships Pro or WP-Members must be active.", "zero-ad-network"); ?></p></li>
                    <li><strong><?php esc_html_e("Test a subscriber visit", "zero-ad-network"); ?></strong><p><?php esc_html_e("In Websites & creators, open your website and select Test in your browser. Test access earns nothing.", "zero-ad-network"); ?></p></li>
                </ol>
            </section>
            <section class="zeroad-panel">
                <h2><span class="dashicons dashicons-performance" aria-hidden="true"></span> <?php esc_html_e("Verification cache", "zero-ad-network"); ?></h2>
                <p><?php echo esc_html($apcuAvailable && !empty($options["cache_enabled"])
                    ? __("APCu is available and enabled for verification results.", "zero-ad-network")
                    : __("Tokens are verified on each request. APCu is optional, and not in use.", "zero-ad-network")); ?></p>
                <p class="description"><?php esc_html_e("This is separate from your page cache. Subscriber requests must still skip page caching.", "zero-ad-network"); ?></p>
            </section>
            <p><a href="https://zeroad.network/docs/site-integration/remove-ads/wordpress"><?php esc_html_e("WordPress integration guide", "zero-ad-network"); ?></a></p>
        </aside>
    </div>
</div>
