<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroAd\WP\Actions\Advertisements;
use ZeroAd\WP\Actions\ContentPaywalls;
use ZeroAd\WP\Actions\CookieConsent;
use ZeroAd\WP\Actions\MarketingDialogs;
use ZeroAd\WP\Actions\SubscriptionAccess;
use ZeroAd\WP\Renderer;

/**
 * Guards the HTML-cleaning actions. The most important thing here is a regression guard: the removal
 * patterns must actually compile and run. An earlier version used `(?:(?!</tag>).){0,10000}` patterns
 * that silently failed PCRE compilation, so `runReplacements` bailed and nothing was removed - these
 * tests fail loudly if that comes back.
 */
class ActionsTest extends TestCase
{
    protected function setUp(): void
    {
        zeroad_test_reset();
    }

    private function actionClasses(): array
    {
        return Renderer::getFeatureActionClasses();
    }

    public function testEnabledIsTrueForTheSubscriberContext(): void
    {
        foreach ($this->actionClasses() as $Class) {
            $this->assertTrue($Class::enabled(Renderer::SUBSCRIBER_CONTEXT), "$Class enabled for subscriber");
        }
    }

    public function testEnabledIsFalseForAnEmptyContext(): void
    {
        foreach ($this->actionClasses() as $Class) {
            $this->assertFalse($Class::enabled([]), "$Class disabled for non-subscriber");
        }
    }

    public function testOutputCallbacksNeverRaiseAPcreError(): void
    {
        // If a pattern fails to compile, preg_last_error() is non-zero after runReplacements. Prove every
        // action's patterns compile by running them and checking the error state.
        $html = "<html><head></head><body><p>content</p></body></html>";
        foreach ($this->actionClasses() as $Class) {
            $Class::outputBufferCallback($html);
            $this->assertSame(PREG_NO_ERROR, preg_last_error(), "$Class has a non-compiling pattern");
        }
    }

    public function testRemovesTheAdSenseScript(): void
    {
        $html = '<p>a</p><script src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script><p>b</p>';
        $out = Advertisements::outputBufferCallback($html);

        $this->assertStringNotContainsString("googlesyndication", $out);
        $this->assertStringContainsString("<p>a</p>", $out);
        $this->assertStringContainsString("<p>b</p>", $out);
    }

    public function testRemovesTheAdSenseInsBlock(): void
    {
        $html = '<ins class="adsbygoogle" data-ad-slot="1"></ins><p>keep</p>';
        $out = Advertisements::outputBufferCallback($html);

        $this->assertStringNotContainsString("adsbygoogle", $out);
        $this->assertStringContainsString("<p>keep</p>", $out);
    }

    public function testRemovesACookieConsentScript(): void
    {
        $html = '<script src="/assets/cookieconsent.min.js"></script><main>hi</main>';
        $out = CookieConsent::outputBufferCallback($html);

        $this->assertStringNotContainsString("cookieconsent.min.js", $out);
        $this->assertStringContainsString("<main>hi</main>", $out);
    }

    public function testLeavesNestedContainersIntact(): void
    {
        // The old container-removal regex corrupted this by stopping at the first </div>.
        $html = '<div class="ad-wrapper"><div class="inner">real <span>content</span></div></div>'
            . '<article><div class="post-body">the word ads appears here</div></article>';

        $this->assertSame($html, Advertisements::outputBufferCallback($html), "ad container markup must be untouched");
    }

    public function testKeepsLegitimateSubscribeAndModalMarkup(): void
    {
        $html = '<section class="newsletter-subscribe"><h2>Subscribe</h2></section>'
            . '<div class="modal" id="login-modal"><form>login</form></div>';

        $this->assertSame($html, MarketingDialogs::outputBufferCallback($html));
    }

    public function testDoesNotStripInnocentMetaTags(): void
    {
        $html = '<meta name="application-name" content="MyApp"><meta name="description" content="A site">';
        $this->assertSame($html, Advertisements::outputBufferCallback($html));
    }

    public function testPaywallActionsLeaveContentContainersInPlace(): void
    {
        $html = '<div class="paywall-overlay"><div class="premium-content">locked article body</div></div>';

        // Access is granted at the source (membership filters); the overlay markup is left for CSS, so
        // the article text must survive rather than being cut by a broken regex.
        foreach ([ContentPaywalls::class, SubscriptionAccess::class] as $Class) {
            $out = $Class::outputBufferCallback($html);
            $this->assertStringContainsString("locked article body", $out, "$Class must not eat article content");
        }
    }
}
