<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Dto;

use Lgarret\HealthCheckBundle\Dto\HealthCheckReport;
use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;
use Lgarret\HealthCheckBundle\Dto\HealthStatus;
use PHPUnit\Framework\TestCase;

final class HealthCheckReportTest extends TestCase
{
    public function testConstructor(): void
    {
        $checks = ['database' => HealthCheckResult::ok()];
        $result = new HealthCheckReport(HealthStatus::Ok, $checks);

        self::assertSame(HealthStatus::Ok, $result->status);
        self::assertSame($checks, $result->checks);
    }

    public function testJsonSerialize(): void
    {
        $checks = ['database' => HealthCheckResult::ok(), 'redis' => HealthCheckResult::ko('down')];
        $result = new HealthCheckReport(HealthStatus::Ko, $checks);

        self::assertSame(
            ['status' => HealthStatus::Ko, 'checks' => $checks],
            $result->jsonSerialize(),
        );
    }
}
