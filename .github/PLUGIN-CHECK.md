# WordPress release checks

The build workflow runs the official [Plugin Check action](https://github.com/WordPress/plugin-check-action) against `build/zero-ad-network` before uploading or publishing it. Strict mode treats checker warnings as failures. Test files and PHPUnit's result cache are excluded from the release by `.distignore`.

## Last local review

On 26 September 2026, the release contents were checked with Plugin Check 2.1.0 on a disposable WordPress 7.1.2 installation using PHP 8.5.11 and a separate SQLite database.

- Static and runtime checks completed with no plugin errors or warnings.
- Settings, About & earnings, and Page cache setup rendered successfully while logged in as an administrator.
- Saving settings through the WordPress Settings API succeeded, including its nonce verification.
- No plugin PHP errors appeared in the WordPress debug log.
- The unit suite passed with 61 tests and 145 assertions.

WP-CLI emitted a PHP 8.5 deprecation from its bundled React Promise dependency, separate from the plugin check results. This local review did not exercise every supported WordPress/PHP version, real Stripe transfers, or every third-party plugin/cache integration.

## Repeating the check

Use a disposable WordPress installation with the production build installed and activated. Install and activate Plugin Check, then run from the WordPress root:

```sh
wp plugin check zero-ad-network --format=json --require=./wp-content/plugins/plugin-check/cli.php
```

The `--require` argument enables runtime checks. Inspect the results: WP-CLI can exit successfully even when it reports findings. The GitHub action processes those findings and fails the release in strict mode.

Also verify settings saving, ordinary and subscriber requests with warm page caches, and the integrations used on the target site. Plugin Check assists with the [official directory guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/); passing it does not replace the WordPress.org team's manual review.
