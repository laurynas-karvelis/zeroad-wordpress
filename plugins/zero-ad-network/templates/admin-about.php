<?php

if (!defined("ABSPATH")) {
    exit();
}

$zeroadActivePage = "zeroad-about";
?>
<div class="wrap zeroad-admin">
    <header class="zeroad-page-header">
        <span class="zeroad-page-icon dashicons dashicons-info" aria-hidden="true"></span>
        <div>
    <h1><?php esc_html_e("About Zero Ad Network", "zero-ad-network"); ?></h1>
    <p class="zeroad-intro"><?php esc_html_e("Subscribers fund the sites they spend time on, instead of advertisers.", "zero-ad-network"); ?></p>
        </div>
    </header>
    <?php include __DIR__ . "/admin-navigation.php"; ?>
    <div class="zeroad-content zeroad-about-grid">
        <section class="zeroad-panel">
            <h2><?php esc_html_e("What subscribers get", "zero-ad-network"); ?></h2>
            <p><?php esc_html_e("For verified Freedom subscribers, the plugin removes ads, non-essential trackers, cookie dialogs and marketing popups, through supported integrations. Everyone else keeps your normal site.", "zero-ad-network"); ?></p>
            <p><?php esc_html_e("If you sell access, include your base subscription content or a custom selection. Use the Freedom access box on each post or page. Paid Memberships Pro and WP-Members are supported; other paywalls need a custom integration. Private, draft, password-protected and unselected content stays protected.", "zero-ad-network"); ?></p>
            <a class="button button-secondary" href="<?php echo esc_url(admin_url("admin.php?page=zeroad-config")); ?>"><?php esc_html_e("Configure your site", "zero-ad-network"); ?></a>
        </section>
        <section class="zeroad-panel">
            <h2><?php esc_html_e("Where funding comes from", "zero-ad-network"); ?></h2>
            <p><?php esc_html_e("Funding is what subscribers actually pay, after payment-processing fees and without tax. Discounts reduce it.", "zero-ad-network"); ?></p>
            <p><?php esc_html_e("Each payment is spread across the calendar months its billing period covers.", "zero-ad-network"); ?></p>
        </section>
        <section class="zeroad-panel zeroad-panel-wide">
            <h2><?php esc_html_e("How your share is calculated", "zero-ad-network"); ?></h2>
            <p>
                <strong><?php esc_html_e("Split by time:", "zero-ad-network"); ?></strong><br>
                <?php esc_html_e(
                    "Each month, a subscriber's funding is split by their measured time on participating websites and creator content. Their creator share setting and publisher exclusions adjust those shares. Money withheld from creators or excluded publishers goes to the other websites they visited, by time. If there are none, it goes to the shared pool.",
                    "zero-ad-network"
                ); ?>
            </p>
            <p>
                <strong><?php esc_html_e("What you keep:", "zero-ad-network"); ?></strong><br>
                <?php esc_html_e(
                    "70% of your share. The platform keeps 30%. Earnings are combined across all your sites and creator integrations. There is no fixed payment per visit, minute or website. Test access earns nothing.",
                    "zero-ad-network"
                ); ?>
            </p>
            <p>
                <strong><?php esc_html_e("Shared pool:", "zero-ad-network"); ?></strong><br>
                <?php esc_html_e(
                    "Unallocated funding, including from subscribers who visited no one, is split equally per eligible publisher account, not per website. An account with at least one Observed or Active integration is eligible. Pool earnings aren't guaranteed. A subscriber's exclusion doesn't remove an eligible publisher from the pool.",
                    "zero-ad-network"
                ); ?>
            </p>
            <p>
                <strong><?php esc_html_e("Transfers to Stripe:", "zero-ad-network"); ?></strong><br>
                <?php esc_html_e(
                    "Earnings build up before payout setup. Once you have at least $30 unpaid, you can complete Stripe Express onboarding. Each month, eligible balances are transferred to your Stripe account; smaller balances carry forward. Reaching $30 doesn't trigger an immediate payment, and the platform fee isn't deducted again. Bank withdrawals are separate, and Zero Ad Network doesn't start them.",
                    "zero-ad-network"
                ); ?>
            </p>
            <p><a href="https://zeroad.network/docs/monetization" target="_blank" rel="noopener noreferrer"><?php esc_html_e("Read how earnings and transfers work", "zero-ad-network"); ?></a></p>
        </section>
        <section class="zeroad-panel zeroad-panel-wide">
            <h2><?php esc_html_e("Verification and privacy", "zero-ad-network"); ?></h2>
            <p><?php esc_html_e("The extension finds your Publisher ID, then sends a signed token bound to your hostname. The plugin checks it locally, without an API call. A subscriber's first visit may need a reload.", "zero-ad-network"); ?></p>
            <p><?php esc_html_e("Tokens contain no account ID, name or email. Reusing a token allows correlation on the same host while it's valid. Issuing tokens requires sign-in, and the extension separately reports account-linked attention, including creator page URLs. This is not anonymous measurement.", "zero-ad-network"); ?></p>
            <p><?php esc_html_e("Cancelling a membership or closing an account can't revoke an issued token straight away. Tokens stop working when they expire, give or take the SDK's clock tolerance. Page caches must skip token requests.", "zero-ad-network"); ?></p>
            <a href="<?php echo esc_url(admin_url("admin.php?page=zeroad-cache-config")); ?>"><?php esc_html_e("Review page cache setup", "zero-ad-network"); ?></a>
        </section>
    </div>
</div>
