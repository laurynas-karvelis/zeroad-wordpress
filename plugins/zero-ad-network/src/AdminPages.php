<?php

declare(strict_types=1);

namespace ZeroAd\WP;

if (!defined("ABSPATH")) {
    exit();
}

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
        }

        $options = $this->options;
        $hasPublisher = preg_match(Settings::PUBLISHER_ID_PATTERN, $options["publisher_id"] ?? "") === 1;
        $enabled = $hasPublisher && !empty($options["enabled"]);
        $apcuAvailable = \ZeroAd\Token\ApcuResultCache::isSupported();

        include ZEROAD_PLUGIN_DIR . "templates/admin-settings.php";
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
