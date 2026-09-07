<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroAd\Token\Publisher;
use ZeroAd\Token\Tests\Fixtures\Authority;
use ZeroAd\WP\Renderer;

/**
 * Exercises the request-time wiring against the real token SDK: a minted token in, the right internal
 * context out. This is the heart of the plugin - if it drifts, subscribers stop getting their benefits
 * or non-subscribers start getting them.
 */
class RendererTest extends TestCase
{
    private const HOSTNAME = "example.com";
    private const PUBLISHER_ID = "zapub_7Fq2xR9nKdW3mB6tYp1sVzAe";

    /** @var Authority */
    private $authority;

    protected function setUp(): void
    {
        zeroad_test_reset();
        $this->authority = Authority::create();
    }

    private function publisher(): Publisher
    {
        return Publisher::create([
            "publisherId" => self::PUBLISHER_ID,
            "hostnames" => self::HOSTNAME,
            "publicKey" => $this->authority->publicKey,
            "cache" => false,
        ]);
    }

    private function verify(?string $token, string $host = self::HOSTNAME): void
    {
        if ($token !== null) {
            $_SERVER["HTTP_BETTER_WEB_TOKEN"] = $token;
        }
        $_SERVER["HTTP_HOST"] = $host;

        $renderer = new Renderer();
        $renderer->setOptions(["output_method" => "header"]);
        $renderer->setPublisher($this->publisher());
        $renderer->verifyToken();
    }

    private function context()
    {
        return $GLOBALS["zeroad_token_context"] ?? null;
    }

    public function testAValidTokenYieldsTheFullSubscriberContext(): void
    {
        $this->verify($this->authority->mintToken(self::HOSTNAME));
        $this->assertSame(Renderer::SUBSCRIBER_CONTEXT, $this->context());
    }

    public function testEveryActionRecognisesTheSubscriberContext(): void
    {
        $this->verify($this->authority->mintToken(self::HOSTNAME));
        foreach (Renderer::getFeatureActionClasses() as $Class) {
            $this->assertTrue($Class::enabled($this->context()), "$Class should be enabled for a subscriber");
        }
    }

    public function testNoTokenMeansAnEmptyContext(): void
    {
        $this->verify(null);
        $this->assertSame([], $this->context());
    }

    public function testATokenReplayedFromAnotherHostIsRejected(): void
    {
        // Bound to another site, then presented here - the hostname signature won't match.
        $this->verify($this->authority->mintToken("harvested-elsewhere.example"));
        $this->assertSame([], $this->context());
    }

    public function testAnExpiredTokenIsRejected(): void
    {
        // Well past the SDK's default 60s clock-skew tolerance.
        $this->verify($this->authority->mintToken(self::HOSTNAME, ["expiresAt" => time() - 3600]));
        $this->assertSame([], $this->context());
    }

    public function testAForgedTokenIsRejected(): void
    {
        // Minted by a different authority than the publisher trusts.
        $forged = Authority::create()->mintToken(self::HOSTNAME);
        $this->verify($forged);
        $this->assertSame([], $this->context());
    }

    public function testGarbageInTheHeaderIsRejectedNotFatal(): void
    {
        $this->verify(str_repeat("x", 5000));
        $this->assertSame([], $this->context());
    }

    public function testVerificationIsSkippedInAdminContext(): void
    {
        $GLOBALS["__is_admin"] = true;
        $this->verify($this->authority->mintToken(self::HOSTNAME));
        $GLOBALS["__is_admin"] = false;

        // verifyToken() bails before touching the global in admin, so it must not appear as a subscriber.
        $this->assertNotSame(Renderer::SUBSCRIBER_CONTEXT, $this->context());
    }
}
