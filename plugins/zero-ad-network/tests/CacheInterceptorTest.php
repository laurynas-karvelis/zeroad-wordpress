<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroAd\WP\CacheInterceptor;

/**
 * Guards the cache signalling. Getting this wrong means a cached subscriber page served to a regular
 * visitor (or vice versa), so the cookie/header must carry the right variant and stay cache-friendly.
 */
class CacheInterceptorTest extends TestCase
{
    protected function setUp(): void
    {
        zeroad_test_reset();
    }

    /** Runs registration and fires the send_headers callback it registered. */
    private function fireSendHeaders(bool $isSubscriber): void
    {
        CacheInterceptor::registerPluginOverrides($isSubscriber);

        $this->assertNotEmpty($GLOBALS["__wp_hooks"]["send_headers"] ?? [], "a send_headers callback must be registered");
        foreach ($GLOBALS["__wp_hooks"]["send_headers"] as $cb) {
            $cb();
        }
    }

    private function sentHeaders(): array
    {
        return array_column($GLOBALS["__sent_headers"], "header");
    }

    public function testEmitsTheSubscriberVariantHeaderAndVary(): void
    {
        $this->fireSendHeaders(true);
        $this->assertContains("X-ZeroAd-Variant: subscriber1", $this->sentHeaders());
        $this->assertContains("Vary: X-ZeroAd-Variant", $this->sentHeaders());
    }

    public function testEmitsTheRegularVariantForNonSubscribers(): void
    {
        $this->fireSendHeaders(false);
        $this->assertContains("X-ZeroAd-Variant: subscriber0", $this->sentHeaders());
    }

    public function testSetsTheVariantCookieWhenItIsAbsent(): void
    {
        $this->fireSendHeaders(true);

        $this->assertCount(1, $GLOBALS["__set_cookies"]);
        $cookie = $GLOBALS["__set_cookies"][0];
        $this->assertSame("zeroad_variant", $cookie["name"]);
        $this->assertSame("subscriber1", $cookie["value"]);
        $this->assertTrue($cookie["httponly"], "the variant cookie should be HTTP-only");
    }

    public function testDoesNotResetTheCookieWhenItAlreadyMatches(): void
    {
        // A steady-state response must carry no Set-Cookie, or it becomes uncacheable.
        $_COOKIE["zeroad_variant"] = "subscriber1";
        $this->fireSendHeaders(true);

        $this->assertSame([], $GLOBALS["__set_cookies"], "no Set-Cookie when the value is unchanged");
        $this->assertContains("X-ZeroAd-Variant: subscriber1", $this->sentHeaders(), "header is still emitted");
    }

    public function testUpdatesTheCookieWhenTheVariantChanges(): void
    {
        $_COOKIE["zeroad_variant"] = "subscriber0";
        $this->fireSendHeaders(true);

        $this->assertCount(1, $GLOBALS["__set_cookies"]);
        $this->assertSame("subscriber1", $GLOBALS["__set_cookies"][0]["value"]);
    }

    public function testMarksTheCookieSecureOverHttps(): void
    {
        $GLOBALS["__is_ssl"] = true;
        $this->fireSendHeaders(false);
        $GLOBALS["__is_ssl"] = false;

        $this->assertTrue($GLOBALS["__set_cookies"][0]["secure"]);
    }
}
