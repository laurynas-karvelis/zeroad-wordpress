<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroAd\WP\Config;

/**
 * Covers the two bits of real logic in Config: turning the plugin's cache settings into the token SDK's
 * cache options (seconds -> milliseconds, APCu when enabled), and deriving the hostnames to verify
 * against from the site URLs. Both are private, so we reach them by reflection on an un-constructed
 * instance - Config's constructor is all WordPress glue we don't want to run here.
 */
class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        zeroad_test_reset();
    }

    /** setAccessible() is required on PHP < 8.1 and a deprecated no-op after, so only call it where it matters. */
    private static function unlock($reflected): void
    {
        if (PHP_VERSION_ID < 80100) {
            $reflected->setAccessible(true);
        }
    }

    private function withOptions(array $options): Config
    {
        $config = (new ReflectionClass(Config::class))->newInstanceWithoutConstructor();
        $prop = new ReflectionProperty(Config::class, "options");
        self::unlock($prop);
        $prop->setValue($config, $options);
        return $config;
    }

    private function call(Config $config, string $method)
    {
        $ref = new ReflectionMethod(Config::class, $method);
        self::unlock($ref);
        return $ref->invoke($config);
    }

    public function testCacheDisabledYieldsNoCache(): void
    {
        $config = $this->withOptions(["cache_enabled" => 0]);
        $this->assertFalse($this->call($config, "resolveCacheOptions"));
    }

    public function testCacheEnabledUsesTheApcuAutoStoreAndConvertsTtlToMilliseconds(): void
    {
        $config = $this->withOptions(["cache_enabled" => 1, "cache_ttl" => 15, "cache_prefix" => "wp1:"]);
        $options = $this->call($config, "resolveCacheOptions");

        $this->assertSame("auto", $options["store"]);
        $this->assertSame(15000, $options["ttl"], "the UI is in seconds; the SDK expects milliseconds");
        $this->assertSame("wp1:", $options["prefix"]);
    }

    public function testCacheTtlFallsBackToTheDefaultWhenMissing(): void
    {
        $config = $this->withOptions(["cache_enabled" => 1]);
        $options = $this->call($config, "resolveCacheOptions");
        $this->assertSame(ZEROAD_DEFAULT_CACHE_TTL * 1000, $options["ttl"]);
    }

    public function testHostnamesComeFromTheSiteUrl(): void
    {
        $GLOBALS["__home_url"] = "https://example.com";
        $GLOBALS["__site_url"] = "https://example.com";

        $hosts = $this->call($this->withOptions([]), "resolveHostnames");
        $this->assertSame(["example.com"], $hosts);
    }

    public function testHostnamesIncludeADistinctSiteUrlHostWithoutDuplicates(): void
    {
        $GLOBALS["__home_url"] = "https://example.com";
        $GLOBALS["__site_url"] = "https://wp.example.com";

        $hosts = $this->call($this->withOptions([]), "resolveHostnames");
        $this->assertSame(["example.com", "wp.example.com"], $hosts);
    }
}
