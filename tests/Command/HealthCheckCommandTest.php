<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Command;

use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
use Lgarret\HealthCheckBundle\Command\HealthCheckCommand;
use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;
use Lgarret\HealthCheckBundle\Service\HealthCheckService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class HealthCheckCommandTest extends TestCase
{
    public function testAllChecksPassed(): void
    {
        $tester = $this->createCommandTester([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('All checks passed', $tester->getDisplay());
    }

    public function testSomeChecksFailed(): void
    {
        $tester = $this->createCommandTester([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
            $this->createHealthCheck('redis', HealthCheckResult::ko('Connection refused')),
        ]);
        $tester->execute([]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('1 of 2 check(s) failed', $tester->getDisplay());
        self::assertStringContainsString('Connection refused', $tester->getDisplay());
    }

    public function testNoChecksRegistered(): void
    {
        $tester = $this->createCommandTester([]);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('No health checks registered', $tester->getDisplay());
    }

    /**
     * @param HealthCheckInterface[] $checks
     */
    private function createCommandTester(array $checks): CommandTester
    {
        $service = new HealthCheckService(checks: $checks, cacheEnabled: false);
        $command = new HealthCheckCommand($service);
        $application = new Application();
        $application->add($command);

        return new CommandTester($application->find('health:check'));
    }

    private function createHealthCheck(string $name, HealthCheckResult $result): HealthCheckInterface
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn($name);
        $check->method('check')->willReturn($result);

        return $check;
    }
}
