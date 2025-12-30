<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
  exit();
}

use ZeroAd\Token\Constants;

/**
 * AdminPages - Handles admin page rendering
 */
class AdminPages
{
  private $options;

  public function __construct(array $options)
  {
    $this->options = $options;
  }

  /**
   * Update options reference
   */
  public function setOptions(array $options): void
  {
    $this->options = $options;
  }

  /**
   * Render main settings page
   */
  public function renderSettingsPage(): void
  {
    if (!current_user_can("manage_options")) {
      wp_die(esc_html__("You do not have sufficient permissions to access this page.", "zero-ad-network"));
    } ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors(Settings::OPTION_KEY); ?>
            
            <?php if (!empty($this->options["enabled"]) && !empty($this->options["client_id"])): ?>
                <div class="notice notice-success inline" style="margin: 20px 0;">
                    <p>
                        <strong><?php esc_html_e(
                          "✓ Your site is partnered with Zero Ad Network!",
                          "zero-ad-network"
                        ); ?></strong><br>
                        <?php esc_html_e(
                          "Subscribers will now enjoy an enhanced experience on your site, and you'll earn revenue based on their engagement time.",
                          "zero-ad-network"
                        ); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields(Settings::OPTION_KEY);
                do_settings_sections(Settings::OPTION_KEY);
                submit_button(__("Save Settings", "zero-ad-network"));
                ?>
            </form>
            
            <?php if (!empty($this->options["enabled"]) && !empty($this->options["client_id"])): ?>
                <?php $this->renderStatusTable(); ?>
            <?php endif; ?>
        </div>
        <?php
  }

  /**
   * Render integration status table
   */
  private function renderStatusTable(): void
  {
    ?>
        <hr style="margin: 30px 0;">
        
        <h2><?php esc_html_e("Integration Status", "zero-ad-network"); ?></h2>
        <table class="widefat striped" style="max-width: 800px;">
            <tbody>
                <tr>
                    <th style="width: 30%;"><?php esc_html_e("Plugin Status", "zero-ad-network"); ?></th>
                    <td>
                        <span style="color: #46b450; font-size: 18px;">●</span>
                        <strong><?php esc_html_e("Active & Partnered", "zero-ad-network"); ?></strong>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e("Client ID", "zero-ad-network"); ?></th>
                    <td>
                        <code style="font-size: 13px;"><?php echo esc_html($this->options["client_id"]); ?></code>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e("Welcome Header Method", "zero-ad-network"); ?></th>
                    <td>
                        <?php echo esc_html(
                          $this->options["output_method"] === "header"
                            ? __("HTTP Response Header", "zero-ad-network")
                            : __("HTML Meta Tag", "zero-ad-network")
                        ); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e("APCu Caching", "zero-ad-network"); ?></th>
                    <td>
                        <?php if (!empty($this->options["cache_enabled"])): ?>
                            <span style="color: #46b450;">✓</span>
                             /* translators: 1: Cache TTL in seconds, 2: Cache key prefix */ /* translators: 1: Cache TTL in seconds, 2: Cache key prefix */<?php
                          /* translators: 1: Cache TTL in seconds, 2: Cache key prefix */
                          printf(
                               esc_html__("Enabled (TTL: %1\$ds, Prefix: %2\$s)", "zero-ad-network"),
                               esc_html((string) ($this->options["cache_ttl"] ?? 5)),
                               esc_html($this->options["cache_prefix"] ?? "zeroad:")
                             ); ?>
                        <?php else: ?>
                            <span style="color: #dc3545;">✗</span>
                            <?php esc_html_e("Disabled", "zero-ad-network"); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e("Enabled Features", "zero-ad-network"); ?></th>
                    <td>
                        <?php
                        $featureNames = [
                          Constants::FEATURE["CLEAN_WEB"] => __("Clean Web ($6/month)", "zero-ad-network"),
                          Constants::FEATURE["ONE_PASS"] => __("One Pass ($12/month)", "zero-ad-network")
                        ];
                        $enabled = [];
                        foreach ($this->options["features"] as $feature) {
                          $enabled[] = $featureNames[$feature] ?? $feature;
                        }
                        echo esc_html(implode(", ", $enabled));
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e("Maximum Monthly Revenue", "zero-ad-network"); ?></th>
                    <td>
                        <strong>
                            <?php
                            $revenue = 0;
                            foreach ($this->options["features"] as $feature) {
                              if ($feature === Constants::FEATURE["CLEAN_WEB"]) {
                                $revenue += 6;
                              }
                              if ($feature === Constants::FEATURE["ONE_PASS"]) {
                                $revenue += 12;
                              }
                            }
                            printf(
                              /* translators: %s: Revenue amount per subscriber */
                              esc_html__("$%s per subscriber (based on engagement)", "zero-ad-network"),
                              esc_html(number_format($revenue, 0))
                            );
                            ?>
                        </strong>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <p class="description" style="margin-top: 15px; max-width: 800px;">
            <?php esc_html_e(
              "Revenue is distributed monthly based on the time subscribers spend on your site compared to all partner sites. The more engaging your content, the more you earn!",
              "zero-ad-network"
            ); ?>
        </p>
        <?php
  }

  /**
   * Render cache configuration page
   */
  public function renderCacheConfigPage(): void
  {
    if (!current_user_can("manage_options")) {
      wp_die(esc_html__("You do not have sufficient permissions to access this page.", "zero-ad-network"));
    }

    include ZEROAD_PLUGIN_DIR . "templates/admin-cache-config.php";
  }

  /**
   * Render about page
   */
  public function renderAboutPage(): void
  {
    if (!current_user_can("manage_options")) {
      wp_die(esc_html__("You do not have sufficient permissions to access this page.", "zero-ad-network"));
    }

    include ZEROAD_PLUGIN_DIR . "templates/admin-about.php";
  }
}
