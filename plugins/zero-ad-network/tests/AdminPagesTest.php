<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroAd\WP\AdminPages;
use ZeroAd\WP\Settings;

// Minimal WordPress rendering helpers; settings persistence is tested separately.
function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES); }
function esc_html__($text, $domain = null) { return esc_html($text); }
function esc_html_e($text, $domain = null) { echo esc_html($text); }
function esc_attr_e($text, $domain = null) { echo esc_attr($text); }
function esc_url($url) { return esc_attr($url); }
function admin_url($path) { return '/wp-admin/' . $path; }
function settings_errors() {}
function settings_fields($key) { echo '<input type="hidden" name="option_page" value="' . esc_attr($key) . '">'; }
function do_settings_sections($key) {}
function submit_button($text) { echo '<button type="submit">' . esc_html($text) . '</button>'; }
function wp_die($message) { throw new RuntimeException($message); }
function checked($value, $expected) { if ($value === $expected) echo 'checked="checked"'; }

if (!defined('ZEROAD_PLUGIN_DIR')) {
    define('ZEROAD_PLUGIN_DIR', dirname(__DIR__) . '/');
}

class AdminPagesTest extends TestCase
{
    protected function setUp(): void
    {
        zeroad_test_reset();
        $GLOBALS['__can_edit_post'] = true;
    }

    private function render(string $method, array $options = []): string
    {
        $pages = new AdminPages(array_merge(Settings::getDefaults(), $options));
        ob_start();

        try {
            $pages->$method();

            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public function testSetupStateDoesNotClaimAnUnconfiguredSiteIsEnabled(): void
    {
        $html = $this->render('renderSettingsPage', ['enabled' => true]);

        $this->assertStringContainsString('Setup needed', $html);
        $this->assertStringContainsString('action="options.php"', $html);
        $this->assertStringContainsString('zeroad_token_options', $html);
        $this->assertStringContainsString('Test access earns nothing', $html);
    }

    public function testEnabledStateDescribesSavedSettingsWithoutClaimingVerification(): void
    {
        $html = $this->render('renderSettingsPage', [
            'enabled' => true,
            'publisher_id' => 'zapub_7Fq2xR9nKdW3mB6tYp1sVzAe',
        ]);

        $this->assertStringContainsString('>Enabled<', $html);
        $this->assertStringContainsString('does not confirm platform registration or earnings', $html);
    }

    public function testEachPageHasOneCurrentNavigationItem(): void
    {
        foreach (['renderSettingsPage', 'renderCacheConfigPage', 'renderAboutPage'] as $method) {
            $html = $this->render($method);

            $this->assertSame(1, substr_count($html, 'aria-current="page"'));
            $this->assertSame(1, substr_count($html, '<h1>'));
        }
    }

    public function testAdminPagesRequirePermission(): void
    {
        $GLOBALS['__can_edit_post'] = false;

        foreach (['renderSettingsPage', 'renderCacheConfigPage', 'renderAboutPage'] as $method) {
            try {
                $this->render($method);
                $this->fail('Expected a permission failure');
            } catch (RuntimeException $error) {
                $this->assertStringContainsString('permissions', $error->getMessage());
            }
        }
    }

    public function testCachePreferenceRemainsEditableWithoutApcu(): void
    {
        ob_start();
        (new Settings(Settings::getDefaults()))->renderCacheEnabled();
        $html = ob_get_clean();

        $this->assertStringContainsString('checked="checked"', $html);
        $this->assertStringNotContainsString('disabled', $html);
        $this->assertStringContainsString('aria-describedby="zeroad-cache_enabled-help"', $html);
        $this->assertStringContainsString('id="zeroad-cache_enabled-help"', $html);
    }
}
