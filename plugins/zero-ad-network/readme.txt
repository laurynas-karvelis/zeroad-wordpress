=== Zero Ad Network ===
Contributors: laurynas-karvelis
Tags: middleware, ad-free, paywall, content-access, subscription
Requires at least: 4.9
Tested up to: 6.9
Stable tag: 0.13.12
License: Apache 2.0
License URI: https://www.apache.org/licenses/LICENSE-2.0.txt

Provide clean and limitless web experience to Zero Ad Network users and get paid.

== Description ==
An HTTP-header-based "access / entitlement token" plugin for Zero Ad Network partnering sites using WordPress.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/zeroad-wordpress` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

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
2. Register your site to receive your unique `X-Better-Web-Welcome` header https://zeroad.network/publisher/sites/add.
3. Copy/paste your unique site's `Client ID` value, select site features you want to enable, enable the plugin, all done in the plugin's Main config page.
4. By the end of each month if our users will visit your site, you will get paid a share of their paid subscription money.

= Where can I get more information about the program? =
You can visit our homepage at https://zeroad.network. Read more about the program itself at https://docs.zeroad.network.

== Screenshots ==

1. The main plugin settings page
2. Proxy / CDN Config examples page

== Changelog ==
0.13.12:
- Initial public release
== Upgrade Notice ==