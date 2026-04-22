=== Zero Ad Network ===
Contributors: zeroadnetwork
Tags: monetization, revenue, access-control, ad-blocker, ad-free
Requires PHP: 7.2
Requires at least: 4.9
Tested up to: 6.9.4
Stable tag: 0.14.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Get paid by providing ad-free, clean web experience to Zero Ad Network users.

== Description ==

Zero Ad Network is a publisher monetization plugin for WordPress. It works alongside the Zero Ad Network browser extension — when a subscriber visits your site, the plugin verifies their token and automatically applies their plan benefits.

**This is the site owner side of the platform.** Your visitors install the browser extension and subscribe. You install this plugin, enter your Client ID, and start earning.

= What Your Site Gets =

Subscribers on the **Clean Web** plan ($6/month) get an ad-free experience on your site — advertisements, cookie consent banners, marketing popups, and non-functional third-party trackers are automatically suppressed.

Subscribers on the **One Pass** plan ($12/month) get access to content you've placed behind a paywall or membership plugin — the plugin removes those restrictions for them automatically.

The **Freedom** plan ($18/month) includes both.

You only need to enable the features your site actually has. If you don't run ads, just enable One Pass. If you don't have a paywall, just enable Clean Web. Features only apply when there's overlap between what you offer and what the subscriber's plan includes.

= How You Get Paid =

At the end of each month, Zero Ad Network distributes subscriber revenue across all partner sites based on how much time subscribers actually spent on each site. No impressions, no clicks — just time spent on your content.

The more engaging your site, the more you earn.

= How It Works Technically =

The subscriber's browser extension sends a signed `X-Better-Web-Hello` request header with a cryptographic token on every page load. The plugin verifies the token using an ED25519 public key — no outbound API calls, no round trips. Validation happens entirely on your server in ~2ms, or ~0.2ms with APCu caching enabled.

The plugin outputs an `X-Better-Web-Welcome` identifier (via HTTP response header or HTML meta tag) so the extension knows your site is a partner.

= Supported Plugin Compatibility =

The plugin integrates with many popular WordPress ad, paywall, and membership plugins to apply subscriber benefits automatically. See the [full compatibility list](https://docs.zeroad.network/site-integration/backend-module/wordpress) on the developer portal.

= No Conflict With Your Existing Setup =

Benefits only apply to verified Zero Ad Network subscribers. All other visitors see your site exactly as normal — ads, paywalls, and everything else remain in place for non-subscribers.

= Get Started =

1. [Sign up at zeroad.network](https://zeroad.network) to register your site and get your Client ID
2. Install and activate this plugin
3. Enter your Client ID and select which features to enable
4. Start earning from subscribers who visit your site

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/zero-ad-network` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. [Sign up at zeroad.network](https://zeroad.network) to register your site and get your Client ID
5. Enter your Client ID and select which features to enable
6. Start earning from subscribers who visit your site

== Frequently Asked Questions ==

= What is Zero Ad Network? =

It's a web platform that allows site owners to open additional revenue stream by allowing Zero Ad Network web users to access their site without ads, cookie consent screens, marketing popups and more.
Partnering with Zero Ad Network allows your site to:
- Generate a new revenue stream by:
  - Providing a clean, unobstructed user experience
  - Removing paywalls and enabling free access to your base subscription plan
  - Or both combined
- Contribute to a truly joyful, user-friendly internet experience

= What does the plugin do? =

The plugin, once site supported features and our user requested features match, will perform feature specific actions before loading site's pages.
On sites with "Clean Web" site feature enabled, the plugin will attempt to disable advertisements, disable cookie consent screens, annoying marketing popups, 3rd party non-functional trackers of many known and supported ad serving WordPress plugins.
For the sites that have "One Pass" site feature enabled, the plugin will attempt to disable any paywall plugins and/or allow access to content hidden behind subscriptions.

= How do I onboard? =

Signing up is easy:
1. Sign up with Zero Ad Network at https://zeroad.network/login.
2. Register your site to receive your unique Client ID obtained at https://zeroad.network/publisher/sites/add.
3. Copy/paste your unique site's `Client ID` value, select site features you want to enable, enable the plugin, all done in the plugin's Main config page.
4. By the end of each month if our users will visit your site, you will get paid a share of their paid subscription money.

= Where can I get more information about the program? =

You can visit our homepage at https://zeroad.network. Read more about the program itself at https://docs.zeroad.network.

== Changelog ==
0.14.0:
- Initial public release
