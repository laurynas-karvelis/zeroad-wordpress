=== Zero Ad Network ===
Contributors: zeroadnetwork
Tags: monetization, content monetization, paid content, membership, ad-free
Requires PHP: 7.2
Requires at least: 4.9
Tested up to: 7.1
Stable tag: 1.0.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Earn revenue from Zero Ad Network subscribers by offering ad-free browsing and access to selected paid content.

== Description ==

Zero Ad Network connects your WordPress site to a subscriber-funded publisher network. Visitors use their Zero Ad Network subscription and browser extension. You receive a share of funding based on their measured attention while providing the subscriber experience on your site.

You need a Zero Ad Network publisher account and your Publisher ID. Site owners do not need to buy a subscription to participate or test their integration.

= What the plugin does =

For verified Freedom subscribers on front-end pages, the plugin:

* Suppresses ads from supported advertising integrations.
* Removes or hides supported cookie consent banners, marketing popups, and newsletter dialogs.
* Removes matching advertising, consent, and marketing scripts from page HTML.
* Grants reading access to included published posts and pages through Paid Memberships Pro or WP-Members.

The plugin combines WordPress hooks, HTML filtering, and CSS. Coverage depends on your theme, installed plugins, and their versions. Check the actual pages and scripts on your site; hiding a banner does not establish that all tracking has stopped.

Missing or invalid membership tokens do not enable subscriber benefits. Existing WordPress membership permissions continue to apply. Subscriber page modifications do not run on administration, AJAX, REST API, or JSON requests.

= Choose included paid content =

In the post or page editor, select **Freedom access > Include this content with Freedom**, then save. This grants verified subscribers reading access through the supported membership integrations. Offer your base subscription content or a custom selection of paid posts and pages; other content keeps its existing restrictions.

Private, draft, password-protected, and other post types are excluded. The plugin does not create site memberships, grant purchases, or provide subscription billing. Selecting content alone cannot unlock an unsupported paywall. Other paywalls and custom paid functionality need a custom integration; see the [WordPress integration guide](https://zeroad.network/docs/site-integration/remove-ads/wordpress#custom-included-access).

Clear page caches after changing included content. An already clean, unrestricted site can participate without adding a paywall.

= Supported integrations =

The plugin includes rules targeting these integrations, among others:

* Advertising: Ads For WP, Ad Inserter, Advanced Ads, WP Quads, AdRotate, and Google Site Kit's AdSense filters.
* Cookie banners: Cookiebot, Real Cookie Banner, Complianz, CookieYes, Cookie Notice, and GDPR by Trew Knowledge.
* Marketing popups: Popup Maker, Popup Maker WP, OptinMonster, MailOptin Lite, Hustle, and Thrive Leads.
* Included paid content: Paid Memberships Pro and WP-Members.

These are targeted integrations, not a guarantee of compatibility with every version or custom placement. See the [full compatibility reference](https://zeroad.network/docs/site-integration/remove-ads/wordpress#supported-plugins-reference) for detection conditions and limitations, including the unconfirmed Raptive/AdThrive rule and the distinction between Convert Pro and Convert Plus.

= Page caches and CDNs =

**Requests carrying `Better-Web-Token` must reach WordPress.** Configure every CDN, proxy, server cache, and WordPress page cache to bypass both cache reads and writes for those requests, and forward the header to WordPress.

Once the plugin runs, it marks token-bearing responses private and non-cacheable, including requests with invalid or empty tokens. Header presence alone never grants access. The plugin cannot change a response already served by an upstream cache or an early WordPress cache drop-in.

Open **Zero Ad Network > Cache Configuration** for setup guidance. The [page-cache guide](https://zeroad.network/docs/site-integration/remove-ads/wordpress#page-caches-and-cdns) includes cache-specific instructions and verification steps. If your host cannot bypass token requests, disable HTML page caching on participating pages.

= Publisher earnings =

Earnings depend on funded subscriber attention and allocation preferences, not a fixed payment per visit or minute. Test visits earn nothing. View earnings and manage payout setup in your Zero Ad Network account; the WordPress plugin does not collect payments or process payouts. See [how publisher earnings work](https://zeroad.network/docs/monetization) for allocation, fees, and transfer requirements.

== Installation ==

Requirements: WordPress 4.9 or later, PHP 7.2 or later with the Sodium extension, and an HTTPS website. APCu is optional.

1. Install and activate **Zero Ad Network** from the WordPress plugin directory, or upload the plugin ZIP through **Plugins > Add New Plugin > Upload Plugin**.
2. [Sign in or create an account](https://zeroad.network/login), then copy your **Publisher ID** from [Sites & creators](https://zeroad.network/sites#publisher-id).
3. In WordPress, open **Zero Ad Network > Settings**, paste your Publisher ID, select **Enable Plugin**, and save.
4. Leave **Publisher Header Method** set to **HTTP Response Header** unless your host requires the **HTML Meta Tag** option. Your WordPress Address and Site Address hostnames must match the public domains you serve.
5. If you sell access, choose included posts and pages using their **Freedom access** box and save them. Paid Memberships Pro, WP-Members, or a custom integration is needed to grant access.
6. Configure page-cache bypass as described above, then clear existing page caches.
7. Install the Zero Ad Network browser extension. In **Sites & creators**, enter a public page URL under **Ready? Verify your published page** and select **Verify & add**. This checks your published Publisher ID; separately test the subscriber experience below.

== Frequently Asked Questions ==

= How do I test without buying a subscription? =

After adding your website, open its details in **Sites & creators** and select **Test in your browser** with the Zero Ad Network extension installed. Keep Freedom turned on in the extension, then open or reload your site. Test access earns no revenue.

Check pages with ads, banners, and popups. Confirm included paid content opens while unselected, private, draft, and password-protected content keeps its restrictions. Turn Freedom off in the extension and reload to compare the ordinary experience; existing site memberships can still grant access independently.

Repeat with warm page caches: load an ordinary page first, test the subscriber visit, then return to the ordinary experience. Subscriber responses must bypass cache lookup and storage, and must not appear to other visitors.

= Why is my site not detected, or why do subscribers still see ads? =

Check that the plugin is enabled, your Publisher ID is correct, and your public page exposes `Better-Web-Publisher` through the selected response header or meta tag. Clear old cached pages after setup. Sites can also appear automatically after accepted subscriber activity is uploaded and processed.

If discovery succeeds but benefits do not appear, reload after discovery and check that `Better-Web-Token` reaches WordPress. Confirm your public hostname matches the WordPress configuration, check every page-cache layer, and consult the compatibility reference for the affected plugin. A successful Publisher ID check does not verify ad suppression or paid-content access.

= Does verification contact Zero Ad Network on every page load? =

No. The plugin verifies signed membership tokens locally using the bundled PHP SDK and Sodium. Tokens are checked against the request hostname; a token issued for another hostname does not grant access.

Under **Performance & Caching**, **Enable APCu Caching** allows verification results to be reused between requests when APCu is available. With caching enabled but APCu unavailable, the SDK uses memory for the current request. Turning the setting off disables verification-result caching. **Cache TTL (seconds)** controls the cache duration. This is separate from page caching and does not remove the need for token-request bypass.

= Does it block every tracker or replace my consent setup? =

No. It targets specific ad, consent, and marketing integrations and matching scripts. It does not provide a general tracking blocker or manage consent for your whole site. Some integrations override consent-status filters to return an accepted state. Review the subscriber page's actual scripts and network requests, especially where custom code depends on those filters. Configure unsupported tracking and custom placements separately.

= External service and privacy =

This plugin integrates with [Zero Ad Network](https://zeroad.network), which manages subscriber memberships, issues signed membership credentials, processes attention reports, and allocates publisher earnings. A publisher account is required to participate; site owners do not need a paid subscription. Visitors use their own subscription and browser extension.

The plugin makes no outbound API requests for verification or telemetry. When enabled and configured, it publishes your Publisher ID in a response header or meta tag for the extension to discover. Subscriber tokens contain no account ID, name, or email, but repeated use can be correlated on the same hostname during validity.

The subscriber's browser extension separately reports account-linked activity to Zero Ad Network, including Publisher ID, hostname, measured duration, views, and creator page URLs where applicable. This reporting is performed by the extension, not by this WordPress plugin. It is not anonymous. The plugin stores its settings, included-content selections, and welcome-notice dismissal in WordPress; optional APCu caching stores verification results on your server.

Review the service's [Terms of Use](https://zeroad.network/terms) and [Privacy Policy](https://zeroad.network/privacy) before participating.

= Where can I find setup help? =

See the [WordPress integration guide](https://zeroad.network/docs/site-integration/remove-ads/wordpress) for cache configuration, compatibility details, custom content access, and troubleshooting.

== Changelog ==

= 1.0.1 =

* Updated plugin directory tags and documentation for setup, supported integrations, included content, page-cache configuration, testing, and privacy.

= 1.0.0 =

First official stable release. Changes since 0.14.0:

* Updated the WordPress integration to use the stable Zero Ad Network PHP token SDK 1.0.0, with local subscriber verification bound to the request hostname. Invalid, expired, forged, and wrong-host tokens do not unlock subscriber benefits.
* Aligned subscriber benefits with the single Freedom plan and removed the previous per-feature settings. Verified subscribers receive the supported ad, cookie-banner, and marketing-popup suppression rules.
* Added a Freedom access box to the post and page editor. Paid Memberships Pro and WP-Members now grant reading access only to explicitly included published content; private, draft, password-protected, and unselected content stays protected.
* Removed broad paywall and membership overrides. The plugin no longer attempts to grant site memberships or purchases, or remove arbitrary paywall markup. Other paywalls require a custom integration.
* Reworked page-cache handling: requests carrying Better-Web-Token send no-store headers and signal supported page caches to bypass caching, including for empty or invalid tokens. Removed the previous variant-cookie approach. Upstream caches still require explicit bypass configuration.
* Connected verification caching to the SDK's automatic APCu support, with a fallback when APCu is unavailable, and corrected cache TTL unit conversion. Increased the default verification-cache TTL to one hour.
* Fixed plugin callback removal, including static-method callbacks, and refined HTML filtering to preserve unrelated markup and nested content.
* Limited subscriber page modifications to front-end page requests, excluding administration, AJAX, REST, and JSON requests, and fixed JSON content-type detection.
* Redesigned the settings, cache configuration, and About pages with clearer setup status, navigation, and guidance. Updated documentation for supported integrations, publisher earnings, external services, and privacy.
* Added automated regression coverage for token verification, content-access boundaries, cache behavior, settings validation, plugin actions, and administration pages.

== Upgrade Notice ==

= 1.0.0 =

First official stable release with the updated token SDK. Review your Publisher ID, select included posts and pages in the Freedom access box, configure all page caches to bypass requests carrying Better-Web-Token, and clear existing page caches. Broad paywall overrides have been removed.
