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

Earn from the time Zero Ad Network subscribers spend on your site. In return, they get your pages without ads, cookie banners or popups.

**In short**

* Subscribers pay one monthly membership, called Freedom, and use the Zero Ad Network browser extension.
* This plugin recognises them on your site, and cleans up your pages for them. Everyone else sees your site as usual.
* You earn from subscribers' measured time on your site. You don't need a paid membership to take part or to test.

You need a free Zero Ad Network account and your Publisher ID.

= What subscribers get =

On front-end pages, for verified Freedom subscribers, the plugin:

* removes ads from supported advertising plugins
* removes or hides supported cookie consent banners, marketing popups and newsletter dialogs
* removes matching advertising, consent and marketing scripts from the page HTML
* opens the posts and pages you include, through Paid Memberships Pro or WP-Members

Everyone else keeps your normal site. A missing or invalid membership token never unlocks anything. Existing WordPress membership permissions still apply. Admin, AJAX, REST API and JSON requests are never changed.

The plugin uses WordPress hooks, HTML filtering and CSS. Coverage depends on your theme, plugins and their versions. Check your real pages and scripts: hiding a banner doesn't prove all tracking has stopped.

= Include paid content =

Skip this if you don't sell access.

1. Edit a post or page you want subscribers to read.
2. In the **Freedom access** box, check **Include this content with Freedom**.
3. Save, then clear your page caches.

Offer your base subscription content, or a custom selection. Anything you don't select keeps its restrictions.

* Private, draft, password-protected and other post types are always excluded.
* The plugin doesn't create site memberships, grant purchases or bill anyone.
* Other paywalls and custom paid features need a custom integration. See [Custom included access](https://zeroad.network/docs/site-integration/remove-ads/wordpress#custom-included-access).

An already clean site, with no paywall, doesn't need to include anything.

= Supported plugins =

The plugin includes rules for these plugins, and more:

* **Advertising:** Ads For WP, Ad Inserter, Advanced Ads, WP Quads, AdRotate and Google Site Kit's AdSense filters.
* **Cookie banners:** Cookiebot, Real Cookie Banner, Complianz, CookieYes, Cookie Notice and GDPR by Trew Knowledge.
* **Marketing popups:** Popup Maker, Popup Maker WP, OptinMonster, MailOptin Lite, Hustle and Thrive Leads.
* **Included paid content:** Paid Memberships Pro and WP-Members.

These are targeted integrations, not a guarantee for every version or custom placement. The [full compatibility reference](https://zeroad.network/docs/site-integration/remove-ads/wordpress#supported-plugins-reference) lists detection rules and limits, including the unconfirmed Raptive/AdThrive rule and the difference between Convert Pro and Convert Plus.

= Page caches and CDNs =

**Requests carrying `Better-Web-Token` must reach WordPress.** Set every CDN, proxy, server cache and WordPress page cache to skip both cache reads and writes for those requests. Forward the header to WordPress.

Once the plugin runs, it marks those responses private and non-cacheable, even for invalid or empty tokens. The header alone never grants access. The plugin can't change a page that an upstream cache or an early cache drop-in already served.

Open **Zero Ad Network > Page cache setup** in WordPress for step-by-step rules. The [page-cache guide](https://zeroad.network/docs/site-integration/remove-ads/wordpress#page-caches-and-cdns) has the same rules and a test checklist. If your host can't skip token requests, turn off HTML page caching on participating pages.

= Earnings =

* Earnings follow funded subscriber time and each subscriber's funding preferences. There is no fixed payment per visit or minute.
* Test visits earn nothing.
* View earnings and set up payouts in your Zero Ad Network account. The plugin doesn't collect payments or process payouts.

See [how publisher earnings work](https://zeroad.network/docs/monetization) for the split, fees and transfer requirements.

== Installation ==

You need WordPress 4.9 or newer, PHP 7.2 or newer with the Sodium extension, and an HTTPS website. APCu is optional.

1. Install and activate **Zero Ad Network** from the plugin directory. Or upload the ZIP through **Plugins > Add New Plugin > Upload Plugin**.
2. [Sign in or create an account](https://zeroad.network/login). Copy your **Publisher ID** from [Sites & creators](https://zeroad.network/sites#publisher-id).
3. In WordPress, open **Zero Ad Network > Settings**. Paste your Publisher ID, select **Enable Plugin**, and save.
4. Leave **Publisher Header Method** set to **HTTP Response Header**. Choose **HTML Meta Tag** only if your host requires it.
5. If you sell access, include posts and pages with their **Freedom access** box.
6. Set up page-cache bypass, as described above. Then clear all page caches.
7. Install the Zero Ad Network browser extension. In **Sites & creators**, under **Ready? Verify your published page**, enter a public page address and select **Verify & add**.

Step 7 checks that your Publisher ID is published. Test the subscriber experience separately, as described in the FAQ.

Your WordPress Address and Site Address must match the public domains you serve. The plugin uses them as its allowed hostnames.

== Frequently Asked Questions ==

= How do I test without buying a membership? =

1. In **Sites & creators**, open your website and select **Test in your browser**. The Zero Ad Network extension must be installed.
2. Keep Freedom turned on in the extension, then open or reload your site.
3. Check pages with ads, banners and popups. They should be clean.
4. Open included paid content. It should open. Unselected, private, draft and password-protected content should stay protected.
5. Select **Turn Freedom off** in the extension, and reload. You should see your normal site.

Test visits earn nothing. A site membership you already have can still grant access on its own.

Then repeat with warm page caches. Load an ordinary page first, test the subscriber visit, then return to the ordinary page. Subscriber pages must never be shown to other visitors.

= My site isn't detected. What should I check? =

* The plugin is enabled, and the Publisher ID is correct.
* Your public pages include `Better-Web-Publisher`, as a response header or meta tag.
* Old cached pages are cleared.

Sites also appear on their own after a subscriber visits and their activity is uploaded and processed. This isn't instant.

= Subscribers still see ads or a paywall. What should I check? =

1. Reload the page. The first visit to a newly discovered site may not carry a token.
2. Check that `Better-Web-Token` reaches WordPress. A page cache or CDN is the usual cause.
3. Check that your public hostname matches your WordPress Address and Site Address.
4. Check the [compatibility reference](https://zeroad.network/docs/site-integration/remove-ads/wordpress#supported-plugins-reference) for the affected plugin.

A successful Publisher ID check doesn't test ad removal or paid-content access.

= Does verification contact Zero Ad Network on every page load? =

No. The plugin checks signed membership tokens locally, using the bundled PHP SDK and Sodium. A token made for another hostname never grants access.

Under **Performance & Caching**, **Enable APCu Caching** reuses verification results between requests when APCu is available. Without APCu, results last for the current request only. Turning the setting off turns off verification caching. **Cache TTL (seconds)** sets how long results are kept.

This is separate from page caching. Token requests must still skip your page cache.

= Does it block every tracker or replace my consent setup? =

No. It targets specific ad, consent and marketing plugins, and matching scripts. It isn't a general tracking blocker, and doesn't manage consent for your whole site.

Some integrations make consent-status filters return "accepted". Check the subscriber page's actual scripts and network requests, especially where custom code relies on those filters. Configure unsupported tracking and custom placements yourself.

= External service and privacy =

This plugin connects to [Zero Ad Network](https://zeroad.network). The service manages memberships, issues signed membership credentials, processes attention reports and calculates publisher earnings. You need a publisher account, but not a paid membership. Visitors use their own membership and browser extension.

What the plugin does:

* It makes no outbound requests for verification or telemetry.
* When enabled, it publishes your Publisher ID in a response header or meta tag, for the extension to find.
* It stores its settings, your included-content selections and the welcome-notice dismissal in WordPress. Optional APCu caching stores verification results on your server.

What the extension does, separately:

* It reports account-linked activity to Zero Ad Network: Publisher ID, hostname, measured time, views and, for creator pages, page URLs. This reporting is not anonymous.

Subscriber tokens contain no account ID, name or email. Repeated use can be correlated on the same hostname while a token is valid.

Read the service's [Terms of Use](https://zeroad.network/terms) and [Privacy Policy](https://zeroad.network/privacy) before taking part.

= Where can I find setup help? =

The [WordPress integration guide](https://zeroad.network/docs/site-integration/remove-ads/wordpress) covers setup, cache configuration, compatibility, custom content access and troubleshooting.

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
