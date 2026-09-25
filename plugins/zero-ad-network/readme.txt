=== Zero Ad Network ===
Contributors: zeroadnetwork
Tags: monetization, revenue, access-control, ad-blocker, ad-free
Requires PHP: 7.2
Requires at least: 4.9
Tested up to: 6.9
Stable tag: 0.15.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Get paid by providing an ad-free, clean web experience to Zero Ad Network subscribers.

== Description ==

Zero Ad Network is a publisher monetization plugin for WordPress. It works alongside the Zero Ad Network browser extension — when a subscriber visits your site, the plugin verifies their token and applies supported integrations to provide the subscriber experience.

**This is the site owner side of the platform.** Your visitors install the browser extension and subscribe. You install this plugin, enter your Publisher ID, and start earning.

= What Your Site Gets =

Zero Ad Network subscribers are on a single plan, **Freedom**. For verified subscribers, the plugin works with supported plugins to:

- Suppresses advertisements
- Removes cookie consent banners
- Hides marketing popups and newsletter dialogs
- Opts the visitor out of non-functional third-party trackers
- Grants reading access to explicitly included posts and pages through Paid Memberships Pro or WP-Members

All subscribers receive the clean browsing benefits. If you sell access, include your base subscription content or a custom selection of paid content or features. Select included posts and pages in the editor’s Freedom access box. Higher tiers can remain restricted. Other paywall plugins and custom functionality require a custom integration. Regular visitors keep their normal access.

= How You Get Paid =

Earnings are calculated monthly for each subscriber using measured time and allocation preferences. Publishers share 70% of received revenue after processing fees and excluding tax; the platform retains 30%. Unallocated money enters a shared pool for eligible publishers.

Transfers to Stripe Express require a $30 accumulated balance and completed, eligible payout setup. Smaller balances carry forward. A transfer credits your Stripe account; bank withdrawals are separate. See [how earnings work](https://zeroad.network/docs/monetization).

= How It Works Technically =

After recognizing your website, the subscriber’s browser extension sends `Better-Web-Token` on eligible HTTPS page and media requests. The first visit may need a reload after discovery. The plugin verifies the token using an ED25519 public key — no outbound API calls, no round trips. The token is bound to your hostname, so a token harvested on another site cannot be replayed against yours. Verification uses two Ed25519 checks through the Sodium extension. APCu can reuse verification verdicts across requests.

The plugin outputs a `Better-Web-Publisher` identifier (via HTTP response header or HTML meta tag) so the extension knows your site is a partner and can credit visits to you.

Verified verdicts can be cached across requests with the [APCu extension](https://www.php.net/manual/en/book.apcu.php), shared across the whole PHP-FPM pool. When APCu is not present the plugin falls back to per-request verification automatically.

Configure every CDN, proxy, and page cache to bypass reads and writes for requests carrying `Better-Web-Token`, and forward the header to WordPress. The plugin cannot verify a request served by an upstream cache. Test ordinary and subscriber responses with warm caches; verification-result caching is separate from page caching.

= Supported Plugin Compatibility =

The plugin integrates with advertising and interruption plugins, plus content-specific hooks for Paid Memberships Pro and WP-Members. Other paywalls require a custom adapter. See the [integration guide](https://zeroad.network/docs/site-integration/remove-ads/wordpress).

= No Conflict With Your Existing Setup =

Benefits only apply to verified Zero Ad Network subscribers. All other visitors see your site exactly as normal — ads, paywalls, and everything else remain in place for non-subscribers.

= Get Started =

1. [Sign up at zeroad.network](https://zeroad.network) and copy your account’s Publisher ID from the dashboard
2. Install and activate this plugin
3. Enter your Publisher ID and enable the plugin
4. Select included paid posts and pages in their Freedom access box, verify access, and clear page caches
5. Start earning from subscribers who visit your site

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/zero-ad-network` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. [Sign up at zeroad.network](https://zeroad.network) and copy your account’s Publisher ID from the dashboard
4. Enter your Publisher ID and enable the plugin on the plugin's settings page
5. If you sell access, select included posts and pages, verify access, and clear page caches
6. Start earning from subscribers who visit your site

== Frequently Asked Questions ==

= What is Zero Ad Network? =

It's a web platform that lets site owners open an additional revenue stream by letting Zero Ad Network subscribers browse their site without ads, cookie consent screens, marketing popups, or non-essential third-party trackers, with access to your base subscription or custom included content.
Partnering with Zero Ad Network lets your site:
- Generate a new revenue stream by providing a clean, unobstructed experience and unlocking paywalled content for subscribers
- Contribute to a truly joyful, user-friendly internet experience

= What does the plugin do? =

When a verified Zero Ad Network subscriber loads one of your pages, the plugin applies the full Freedom experience before the page is sent: it disables advertisements, cookie consent screens, marketing popups, and non-functional third-party trackers from many known and supported WordPress plugins, and its scoped membership hooks unlock only the published posts and pages you explicitly include. Private, draft, password-protected, and unselected content stays protected. It does not create site subscriptions or grant purchases. Non-subscribers are unaffected.

= How do I onboard? =

Signing up is easy:
1. Sign up with Zero Ad Network at https://zeroad.network/login.
2. Copy your account’s Publisher ID from the dashboard; you do not need a paid subscription.
3. Enter the ID in the plugin settings, enable the plugin, configure included content and page-cache bypass, and verify the experience.
4. Add and verify the website from your dashboard, or let accepted subscriber activity discover it after upload and processing.
5. Use Test in your browser to check access without paying. Test activity earns nothing. Earnings and transfers follow the rules above.

= Where can I get more information about the program? =

You can visit our homepage at https://zeroad.network. Read more about the program itself at https://zeroad.network/docs.

== Changelog ==
Unreleased:
- Token-bearing requests require page-cache bypass and receive private/no-store responses.
- Removed subscriber variant cookies, headers, and cache-key overrides. Configure every upstream cache before enabling subscriber access.
- Publishers explicitly select included posts and pages using the Freedom access box.
- Paid Memberships Pro and WP-Members access applies only to selected published content.
- Removed blanket paywall/commerce overrides and password bypasses. Other membership plugins need custom adapters.
- Clear all page caches and verify included and excluded content when upgrading.

0.15.0:
- Rebuilt on the reworked Zero Ad Network token SDK: single Freedom plan, hostname-bound tokens, and the new `Better-Web-Publisher` / `Better-Web-Token` headers.
- Verified verdicts are now cached across requests via APCu (shared across the PHP-FPM pool), with automatic fallback to per-request verification.
- Configuration simplified to a single Publisher ID; the per-feature selection is gone as every subscriber now gets the full clean experience.

0.14.0:
- Initial public release
