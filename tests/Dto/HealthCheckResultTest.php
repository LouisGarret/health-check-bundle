<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Dto;

use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;
use Lgarret\HealthCheckBundle\Dto\HealthStatus;
use PHPUnit\Framework\TestCase;

final class HealthCheckResultTest extends TestCase
{
    public function testOk(): void
    {
        $result = HealthCheckResult::ok();

        self::assertSame(HealthStatus::Ok, $result->status);
        self::assertNull($result->error);
    }

    public function testKo(): void
    {
        $result = HealthCheckResult::ko('Connection refused');

        self::assertSame(HealthStatus::Ko, $result->status);
        self::assertSame('Connection refused', $result->error);
    }

    public function testConstructor(): void
    {
        $result = new HealthCheckResult(status: HealthStatus::Ok, error: 'some warning');

        self::assertSame(HealthStatus::Ok, $result->status);
        self::assertSame('some warning', $result->error);
    }

    public function testJsonSerializeWithoutError(): void
    {
        $result = HealthCheckResult::ok();

        self::assertSame(['status' => HealthStatus::Ok], $result->jsonSerialize());
    }

    public function testJsonSerializeWithError(): void
    {
        $result = HealthCheckResult::ko('Connection refused');

        self::assertSame(
            ['status' => HealthStatus::Ko, 'error' => 'Connection refused'],
            $result->jsonSerialize(),
        );
    }
}
