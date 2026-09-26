=== Zero Ad Network ===
Contributors: zeroadnetwork
Tags: monetization, revenue, access-control, ad-blocker, ad-free
Requires PHP: 7.2
Requires at least: 4.9
Tested up to: 6.9
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Get paid by providing an ad-free, clean web experience to Zero Ad Network subscribers.

== Description ==

Zero Ad Network is a publisher monetization plugin for WordPress. It works alongside the Zero Ad Network browser extension — when a subscriber visits your site, the plugin verifies their token and applies supported integrations to provide the subscriber experience.

**This is the site owner side of the platform.** Your visitors install the browser extension and subscribe. You install this plugin, enter your Publisher ID, and configure the subscriber experience. Earnings depend on funded subscriber attention and allocation preferences.

= What Your Site Gets =

Zero Ad Network subscribers are on a single plan, **Freedom**. For verified subscribers, the plugin works with supported plugins to:

- Suppresses advertisements
- Removes cookie consent banners
- Hides marketing popups and newsletter dialogs
- Opts the visitor out of non-functional third-party trackers
- Grants reading access to explicitly included posts and pages through Paid Memberships Pro or WP-Members

All subscribers receive the clean browsing benefits. If you sell access, include your base subscription content or a custom selection of paid content or features. Select included posts and pages in the editor’s Freedom access box. Higher tiers can remain restricted. Other paywall plugins and custom functionality require a custom integration. Regular visitors keep their normal access.

= How You Get Paid =

Funding comes from subscription payments actually received, after payment-processing fees and excluding tax. Discounts reduce this amount. Each payment is spread across the calendar months its billing period covers.

For each subscriber, monthly funding is allocated by measured time on participating websites and creator content during paid coverage. Creator allocation preferences and publisher exclusions adjust those shares. Withheld amounts go to the non-excluded website publishers the subscriber visited, in proportion to website time; if there are none, they enter the shared pool. You keep 70% of your allocated share after the 30% platform fee. There is no fixed payment per visit, minute, or website. Test access earns nothing.

Funding left unallocated, including funding from subscribers with no qualifying activity, enters the shared pool. It is divided equally per eligible publisher account with an observed or active integration, not per website. Pool earnings depend on remaining funds and rounding. Exclusion from direct subscriber allocations does not exclude an otherwise eligible publisher from the pool.

Earnings from all your sites and creator integrations accumulate in your publisher account before payout setup. Once unpaid earnings reach $30, Stripe Express onboarding becomes available. Monthly processing transfers eligible balances to your connected Stripe account after onboarding is complete and payouts are enabled. Smaller balances carry forward; reaching $30 does not trigger an immediate transfer. The platform fee is not deducted again at transfer. Bank withdrawals are separate, and Zero Ad Network does not initiate them. See [how earnings work](https://zeroad.network/docs/monetization).

= How It Works Technically =

After recognizing your website, the subscriber’s browser extension sends `Better-Web-Token` on eligible HTTPS page and media requests. The first visit may need a reload after discovery. The plugin verifies the token using an ED25519 public key — no outbound API calls, no round trips. The token is bound to your hostname, so a token harvested on another site cannot be replayed against yours. Verification uses two Ed25519 checks through the Sodium extension. APCu can reuse verification verdicts across requests.

The plugin outputs a `Better-Web-Publisher` identifier (via HTTP response header or HTML meta tag) so the extension knows your site is a partner and can credit visits to you.

Verified verdicts can be cached across requests with the [APCu extension](https://www.php.net/manual/en/book.apcu.php), shared across the whole PHP-FPM pool. When APCu is not present the plugin falls back to per-request verification automatically.

Configure every CDN, proxy, and page cache to bypass reads and writes for requests carrying `Better-Web-Token`, and forward the header to WordPress. The plugin cannot verify a request served by an upstream cache. Test ordinary and subscriber responses with warm caches; verification-result caching is separate from page caching.

= Supported Plugin Compatibility =

The plugin targets advertising, cookie banner, and popup integrations through plugin hooks, script removal, and CSS. These rules do not certify every third-party plugin version. Check your installed versions and theme with a subscriber visit; custom markup and plugin updates can need additional integration.

Examples include:

- Advertising: Ads For WP, Ad Inserter, Advanced Ads, WP Quads, AdRotate, and Google Site Kit's AdSense filters.
- Cookie banners: Cookiebot, Real Cookie Banner, Complianz, CookieYes, Cookie Notice, and GDPR by Trew Knowledge.
- Marketing popups: Popup Maker, Popup Maker WP, OptinMonster, MailOptin Lite, Hustle, and Thrive Leads. Popup Maker WP is targeted through `SGPM` callbacks; Thrive Leads uses a conditional display-filter override.
- Included content: Paid Memberships Pro and WP-Members, through content-specific hooks for explicitly selected posts and pages. Other paywalls and custom functionality require a custom adapter. The plugin does not create site subscriptions, grant purchases, or bypass post passwords.

Raptive/AdThrive is not confirmed support. Its existing rule checks the `cmb2` text domain, which belongs to a separate custom-fields toolkit and does not reliably identify an active Raptive integration.

Convert Pro has a suppression rule and a conditional popup override. Convert Pro and Convert Plus are separate products; the shared override does not establish full support for both.

See the [complete supported plugins reference](https://zeroad.network/docs/site-integration/remove-ads/wordpress#supported-plugins-reference) for the targeted integrations and their detection conditions. Its identifiers are text domains, callbacks, or filters, not necessarily WordPress.org download slugs.

= No Conflict With Your Existing Setup =

Benefits only apply to verified Zero Ad Network subscribers. All other visitors see your site exactly as normal — ads, paywalls, and everything else remain in place for non-subscribers.

= Get Started =

1. [Sign up at zeroad.network](https://zeroad.network) and copy your account’s Publisher ID from the dashboard
2. Install and activate this plugin
3. Enter your Publisher ID and enable the plugin
4. Select included paid posts and pages in their Freedom access box, verify access, and clear page caches
5. Check your publisher dashboard for monthly earnings from funded subscriber attention and any shared-pool allocation

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/zero-ad-network` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. [Sign up at zeroad.network](https://zeroad.network) and copy your account’s Publisher ID from the dashboard
4. Enter your Publisher ID and enable the plugin on the plugin's settings page
5. If you sell access, select included posts and pages, verify access, and clear page caches
6. Check your publisher dashboard for monthly earnings from funded subscriber attention and any shared-pool allocation

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
