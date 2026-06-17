<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Dto;

use Lgarret\HealthCheckBundle\Dto\HealthCheckReport;
use Lgarret\HealthCheckBundle\Dto\HealthStatus;
use Lgarret\HealthCheckBundle\Dto\RemoteHealthCheckResult;
use PHPUnit\Framework\TestCase;

final class RemoteHealthCheckResultTest extends TestCase
{
    public function testSuccess(): void
    {
        $report = new HealthCheckReport(HealthStatus::Ok, []);
        $result = RemoteHealthCheckResult::success($report);

        self::assertTrue($result->reachable);
        self::assertSame($report, $result->report);
        self::assertNull($result->error);
    }

    public function testReachableIsDerivedFromReportPresence(): void
    {
        $report = new HealthCheckReport(HealthStatus::Ok, []);

        self::assertTrue(RemoteHealthCheckResult::success($report)->reachable);
        self::assertFalse(RemoteHealthCheckResult::failure('Connection refused')->reachable);
    }

    public function testFailure(): void
    {
        $result = RemoteHealthCheckResult::failure('Connection refused');

        self::assertFalse($result->reachable);
        self::assertNull($result->report);
        self::assertSame('Connection refused', $result->error);
    }
}
