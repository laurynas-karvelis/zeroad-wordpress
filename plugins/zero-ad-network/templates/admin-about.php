<?php

if (!defined("ABSPATH")) {
    exit();
}

$activePage = "zeroad-about";
?>
<div class="wrap zeroad-admin">
    <h1><?php esc_html_e("About Zero Ad Network", "zero-ad-network"); ?></h1>
    <p class="zeroad-intro"><?php esc_html_e("A subscriber-funded alternative to advertising, based on attention to participating content.", "zero-ad-network"); ?></p>
    <?php include __DIR__ . "/admin-navigation.php"; ?>
    <div class="zeroad-content">
        <section class="zeroad-panel">
            <h2><?php esc_html_e("What your subscribers receive", "zero-ad-network"); ?></h2>
            <p><?php esc_html_e("For verified Freedom subscribers, the plugin applies supported integrations to remove ads, non-essential trackers, cookie dialogs, and marketing popups. Other visitors keep their normal experience.", "zero-ad-network"); ?></p>
            <p><?php esc_html_e("If you sell access, include your base subscription content or a custom selection. Select posts and pages in the Freedom access box. Paid Memberships Pro and WP-Members are supported; other paywalls need a custom integration. Private, draft, password-protected, and unselected content stays protected.", "zero-ad-network"); ?></p>
            <a class="button button-secondary" href="<?php echo esc_url(admin_url("admin.php?page=zeroad-config")); ?>"><?php esc_html_e("Configure your site", "zero-ad-network"); ?></a>
        </section>
        <section class="zeroad-panel">
            <h2><?php esc_html_e("Where funding comes from", "zero-ad-network"); ?></h2>
            <p><?php esc_html_e("Funding comes from subscription payments actually received, after payment-processing fees and excluding tax. Each payment is spread across the calendar months its billing period covers. Discounts reduce the amount available to share.", "zero-ad-network"); ?></p>
        </section>
        <section class="zeroad-panel">
            <h2><?php esc_html_e("How Your Share Is Calculated", "zero-ad-network"); ?></h2>
            <p>
                <?php esc_html_e(
                    "For each subscriber, monthly funding is divided by their measured time on participating websites and creator content during paid coverage. Their creator allocation setting and publisher exclusions adjust those shares. Money withheld from creators or excluded publishers is redistributed to the non-excluded website publishers they visited, in proportion to website time; if there are none, it enters the shared pool.",
                    "zero-ad-network"
                ); ?>
            </p>
            <p>
                <strong><?php esc_html_e("Your Earnings:", "zero-ad-network"); ?></strong><br>
                <?php esc_html_e(
                    "You keep 70% of your allocated share after the 30% platform fee. Earnings are combined across your sites and creator integrations in your publisher account. There is no fixed payment per visit, minute, or website. Test access earns nothing.",
                    "zero-ad-network"
                ); ?>
            </p>
            <p>
                <?php esc_html_e(
                    "Funding left unallocated, including funding from subscribers with no qualifying activity, goes to the shared pool. It is divided equally per eligible publisher account with an observed or active integration, not per website. Pool earnings are not guaranteed. Exclusion from a subscriber’s direct allocations does not exclude an otherwise eligible publisher from the pool.",
                    "zero-ad-network"
                ); ?>
            </p>
            <p>
                <strong><?php esc_html_e("Transfers to Stripe:", "zero-ad-network"); ?></strong><br>
                <?php esc_html_e(
                    "Earnings accrue before payout setup. Once your publisher account has at least $30 in accumulated unpaid earnings, you can complete Stripe Express onboarding. Monthly processing transfers eligible balances to your connected Stripe account; smaller balances carry forward. Reaching $30 does not trigger an immediate payment. The platform fee is not deducted again at transfer. Bank withdrawals are separate, and Zero Ad Network does not initiate them.",
                    "zero-ad-network"
                ); ?>
            </p>
            <p><a href="https://zeroad.network/docs/monetization" target="_blank" rel="noopener noreferrer"><?php esc_html_e("Read how earnings and transfers work", "zero-ad-network"); ?></a></p>
        </section>
        <section class="zeroad-panel">
            <h2><?php esc_html_e("Verification and privacy", "zero-ad-network"); ?></h2>
            <p><?php esc_html_e("The extension discovers your Publisher ID and sends a signed, hostname-bound token on eligible requests. The plugin verifies it locally, without an API call. The first visit may need a reload after discovery.", "zero-ad-network"); ?></p>
            <p><?php esc_html_e("Tokens contain no account ID, name, or email. Reuse permits same-host correlation during validity. Issuance is authenticated, and the extension separately reports account-linked attention, including creator page URLs. This is not anonymous measurement.", "zero-ad-network"); ?></p>
            <p><?php esc_html_e("Offline verification cannot immediately revoke an issued token after cancellation or account closure. Tokens have their own expiry, subject to the SDK’s clock tolerance. Page caches must bypass token requests.", "zero-ad-network"); ?></p>
            <a href="<?php echo esc_url(admin_url("admin.php?page=zeroad-cache-config")); ?>"><?php esc_html_e("Review page cache setup", "zero-ad-network"); ?></a>
        </section>
    </div>
</div>
