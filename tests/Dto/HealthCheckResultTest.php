<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Dto;

use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;
use PHPUnit\Framework\TestCase;

final class HealthCheckResultTest extends TestCase
{
    public function testOk(): void
    {
        $result = HealthCheckResult::ok();

        self::assertTrue($result->success);
        self::assertNull($result->error);
    }

    public function testKo(): void
    {
        $result = HealthCheckResult::ko('Connection refused');

        self::assertFalse($result->success);
        self::assertSame('Connection refused', $result->error);
    }

    public function testConstructor(): void
    {
        $result = new HealthCheckResult(success: true, error: 'some warning');

        self::assertTrue($result->success);
        self::assertSame('some warning', $result->error);
    }
}
