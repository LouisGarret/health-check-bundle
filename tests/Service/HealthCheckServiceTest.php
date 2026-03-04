<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Service;

use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;
use Lgarret\HealthCheckBundle\Service\HealthCheckService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class HealthCheckServiceTest extends TestCase
{
    public function testRunAllWithNoChecks(): void
    {
        $service = new HealthCheckService(checks: []);

        $result = $service->runAll();

        self::assertSame('ok', $result['status']);
        self::assertSame([], $result['checks']);
    }

    public function testRunAllWithPassingCheck(): void
    {
        $check = $this->createHealthCheck('database', HealthCheckResult::ok());
        $service = new HealthCheckService(checks: [$check]);

        $result = $service->runAll();

        self::assertSame('ok', $result['status']);
        self::assertSame(['status' => 'ok'], $result['checks']['database']);
    }

    public function testRunAllWithFailingCheck(): void
    {
        $check = $this->createHealthCheck('redis', HealthCheckResult::ko('Connection refused'));
        $service = new HealthCheckService(checks: [$check]);

        $result = $service->runAll();

        self::assertSame('ko', $result['status']);
        self::assertSame('ko', $result['checks']['redis']['status']);
        self::assertSame('Connection refused', $result['checks']['redis']['error'] ?? null);
    }

    public function testRunAllWithMixedChecks(): void
    {
        $checks = [
            $this->createHealthCheck('database', HealthCheckResult::ok()),
            $this->createHealthCheck('redis', HealthCheckResult::ko('Timeout')),
        ];
        $service = new HealthCheckService(checks: $checks);

        $result = $service->runAll();

        self::assertSame('ko', $result['status']);
        self::assertSame('ok', $result['checks']['database']['status']);
        self::assertSame('ko', $result['checks']['redis']['status']);
    }

    public function testRunAllCatchesExceptions(): void
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn('broken');
        $check->method('check')->willThrowException(new \RuntimeException('Unexpected error'));

        $service = new HealthCheckService(checks: [$check]);

        $result = $service->runAll();

        self::assertSame('ko', $result['status']);
        self::assertSame('ko', $result['checks']['broken']['status']);
        self::assertSame('Unexpected error', $result['checks']['broken']['error'] ?? null);
    }

    public function testRunAllWithCacheEnabled(): void
    {
        $check = $this->createHealthCheck('database', HealthCheckResult::ok());

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::once())
            ->method('get')
            ->with('health_check_result', self::isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects(self::once())->method('expiresAfter')->with(300);

                return $callback($item);
            });

        $service = new HealthCheckService(
            checks: [$check],
            cache: $cache,
            cacheEnabled: true,
            cacheTtl: 300,
        );

        $result = $service->runAll();

        self::assertSame('ok', $result['status']);
    }

    public function testRunAllWithCacheDisabled(): void
    {
        $check = $this->createHealthCheck('database', HealthCheckResult::ok());

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::never())->method('get');

        $service = new HealthCheckService(
            checks: [$check],
            cache: $cache,
            cacheEnabled: false,
        );

        $result = $service->runAll();

        self::assertSame('ok', $result['status']);
    }

    public function testRunAllWithNullCache(): void
    {
        $check = $this->createHealthCheck('database', HealthCheckResult::ok());

        $service = new HealthCheckService(
            checks: [$check],
            cache: null,
            cacheEnabled: true,
        );

        $result = $service->runAll();

        self::assertSame('ok', $result['status']);
    }

    private function createHealthCheck(string $name, HealthCheckResult $result): HealthCheckInterface
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn($name);
        $check->method('check')->willReturn($result);

        return $check;
    }
}
