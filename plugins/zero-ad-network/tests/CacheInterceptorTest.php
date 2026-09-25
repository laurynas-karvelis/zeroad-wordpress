<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroAd\WP\CacheInterceptor;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CacheInterceptorTest extends TestCase
{
    protected function setUp(): void
    {
        zeroad_test_reset();
    }

    private function sendHeaders(): array
    {
        CacheInterceptor::preventPageCaching();

        foreach ($GLOBALS["__wp_hooks"]["send_headers"] ?? [] as $callback) {
            $callback();
        }

        return array_column($GLOBALS["__sent_headers"], "header");
    }

    public function testOrdinaryPagesRemainCacheable(): void
    {
        $this->assertSame(["Vary: Better-Web-Token"], $this->sendHeaders());
        $this->assertFalse(defined("DONOTCACHEPAGE"));
        $this->assertSame([], $GLOBALS["__set_cookies"]);
    }

    public function testClientVariantClaimsDoNotSelectSubscriberContent(): void
    {
        $_COOKIE["zeroad_variant"] = "subscriber1";
        $_SERVER["HTTP_X_ZEROAD_VARIANT"] = "subscriber1";

        $this->assertSame(["Vary: Better-Web-Token"], $this->sendHeaders());
        $this->assertFalse(defined("DONOTCACHEPAGE"));
        $this->assertSame([], $GLOBALS["__set_cookies"]);
    }

    public function testTokenResponsesAreUncacheableAfterReachingWordPress(): void
    {
        $_SERVER["HTTP_BETTER_WEB_TOKEN"] = "invalid-token";
        $headers = $this->sendHeaders();

        $this->assertTrue(DONOTCACHEPAGE);
        $this->assertContains("Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0", $headers);
        $this->assertContains("CDN-Cache-Control: no-store", $headers);
        $this->assertContains("X-LiteSpeed-Cache-Control: no-cache", $headers);
        $this->assertSame([], $GLOBALS["__set_cookies"]);

        foreach ($headers as $header) {
            $this->assertStringNotContainsString("Variant", $header);
        }
    }

    public function testEmptyTokensStillDisablePageCaching(): void
    {
        $_SERVER["HTTP_BETTER_WEB_TOKEN"] = "";

        $this->assertNotEmpty($this->sendHeaders());
        $this->assertTrue(DONOTCACHEPAGE);
    }
}
