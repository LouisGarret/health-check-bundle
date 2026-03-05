<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Check\Builtin;

use Lgarret\HealthCheckBundle\Check\Builtin\AssetMapperCheck;
use PHPUnit\Framework\TestCase;

final class AssetMapperCheckTest extends TestCase
{
    public function testGetName(): void
    {
        $check = new AssetMapperCheck('/tmp/manifest.json');

        self::assertSame('asset_mapper', $check->getName());
    }

    public function testCheckReturnsOkWhenManifestExists(): void
    {
        $manifestPath = sys_get_temp_dir() . '/health_check_test_manifest.json';
        file_put_contents($manifestPath, '{}');

        try {
            $check = new AssetMapperCheck($manifestPath);
            $result = $check->check();

            self::assertTrue($result->success);
        } finally {
            unlink($manifestPath);
        }
    }

    public function testCheckReturnsKoWhenManifestMissing(): void
    {
        $check = new AssetMapperCheck('/tmp/nonexistent_manifest.json');
        $result = $check->check();

        self::assertFalse($result->success);
        self::assertNotNull($result->error);
        self::assertStringContainsString('asset-map:compile', $result->error);
    }
}
