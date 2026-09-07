<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ZeroAd\WP\Settings;

class SettingsTest extends TestCase
{
    private const VALID_ID = "zapub_7Fq2xR9nKdW3mB6tYp1sVzAe";

    protected function setUp(): void
    {
        zeroad_test_reset();
    }

    private function validate(array $input): array
    {
        return (new Settings(Settings::getDefaults()))->validate($input);
    }

    private function errorMessages(): array
    {
        return array_column($GLOBALS["__settings_errors"], "message");
    }

    public function testAcceptsAWellFormedPublisherId(): void
    {
        $out = $this->validate(["enabled" => "1", "publisher_id" => self::VALID_ID]);
        $this->assertSame(self::VALID_ID, $out["publisher_id"]);
        $this->assertSame(1, $out["enabled"]);
        $this->assertEmpty($GLOBALS["__settings_errors"]);
    }

    public function testTrimsSurroundingWhitespaceFromThePublisherId(): void
    {
        $out = $this->validate(["publisher_id" => "  " . self::VALID_ID . "  "]);
        $this->assertSame(self::VALID_ID, $out["publisher_id"]);
    }

    /**
     * @dataProvider malformedIds
     */
    public function testRejectsAMalformedPublisherId(string $id): void
    {
        $out = $this->validate(["publisher_id" => $id]);
        $this->assertSame("", $out["publisher_id"]);
        $this->assertNotEmpty($this->errorMessages());
    }

    public function malformedIds(): array
    {
        return [
            "no prefix" => ["7Fq2xR9nKdW3mB6tYp1sVzAe"],
            "too short" => ["zapub_short"],
            "too long" => ["zapub_7Fq2xR9nKdW3mB6tYp1sVzAeEXTRA"],
            "wrong prefix" => ["pub_7Fq2xR9nKdW3mB6tYp1sVzAe0"],
            "illegal chars" => ["zapub_7Fq2xR9nKdW3mB6tYp1sV!Ae"],
        ];
    }

    public function testFlagsEnablingWithoutAPublisherId(): void
    {
        $out = $this->validate(["enabled" => "1", "publisher_id" => ""]);
        $this->assertSame("", $out["publisher_id"]);
        $this->assertSame(1, $out["enabled"]);
        $this->assertNotEmpty($this->errorMessages());
    }

    public function testAnEmptyPublisherIdIsFineWhileDisabled(): void
    {
        $out = $this->validate(["enabled" => "", "publisher_id" => ""]);
        $this->assertSame(0, $out["enabled"]);
        $this->assertEmpty($GLOBALS["__settings_errors"]);
    }

    /**
     * @dataProvider ttlClamping
     */
    public function testClampsCacheTtlIntoRange($input, int $expected): void
    {
        $out = $this->validate(["cache_ttl" => $input]);
        $this->assertSame($expected, $out["cache_ttl"]);
    }

    public function ttlClamping(): array
    {
        return [
            "below minimum" => [0, 1],
            "negative" => [-5, 1],
            "above maximum" => [999, 60],
            "within range" => [15, 15],
            "numeric string" => ["30", 30],
        ];
    }

    public function testNormalisesTheCachePrefix(): void
    {
        $this->assertSame("zeroad:", $this->validate(["cache_prefix" => "zeroad"])["cache_prefix"], "appends colon");
        $this->assertSame("zeroad:", $this->validate(["cache_prefix" => ""])["cache_prefix"], "empty falls back");
        $this->assertSame("wp1:", $this->validate(["cache_prefix" => "wp1:"])["cache_prefix"], "kept as-is");
    }

    public function testRejectsAnInvalidCachePrefix(): void
    {
        $out = $this->validate(["cache_prefix" => "bad prefix!"]);
        $this->assertSame("zeroad:", $out["cache_prefix"]);
    }

    public function testOutputMethodFallsBackToHeaderForUnknownValues(): void
    {
        $this->assertSame("meta", $this->validate(["output_method" => "meta"])["output_method"]);
        $this->assertSame("header", $this->validate(["output_method" => "nonsense"])["output_method"]);
    }

    public function testDefaultsDoNotCarryTheRemovedFeaturesKey(): void
    {
        $defaults = Settings::getDefaults();
        $this->assertArrayHasKey("publisher_id", $defaults);
        $this->assertArrayNotHasKey("features", $defaults);
        $this->assertArrayNotHasKey("client_id", $defaults);
    }
}
