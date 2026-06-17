<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Service;

use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
use Lgarret\HealthCheckBundle\Dto\HealthCheckReport;
use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;
use Lgarret\HealthCheckBundle\Dto\HealthStatus;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class HealthCheckService
{
    private const CACHE_KEY = 'health_check_result';

    /** @var iterable<HealthCheckInterface> */
    private iterable $checks;

    /** @param iterable<HealthCheckInterface> $checks */
    public function __construct(
        iterable $checks,
        private readonly ?CacheInterface $cache = null,
        private readonly bool $cacheEnabled = true,
        private readonly int $cacheTtl = 300,
        private readonly int $timeout = 5,
    ) {
        $this->checks = $checks;
    }

    public function runAll(): HealthCheckReport
    {
        if ($this->cacheEnabled && $this->cache !== null) {
            return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): HealthCheckReport {
                $item->expiresAfter($this->cacheTtl);

                return $this->executeChecks();
            });
        }

        return $this->executeChecks();
    }

    private function executeChecks(): HealthCheckReport
    {
        $globalStatus = HealthStatus::Ok;
        $checks = [];

        foreach ($this->checks as $check) {
            $outcome = $this->executeCheck($check);

            if ($outcome->status === HealthStatus::Ko) {
                $globalStatus = HealthStatus::Ko;
            }

            $checks[$check->getName()] = $outcome;
        }

        return new HealthCheckReport($globalStatus, $checks);
    }

    private function executeCheck(HealthCheckInterface $check): HealthCheckResult
    {
        $startTime = microtime(true);

        try {
            $result = $check->check();
            $elapsed = microtime(true) - $startTime;

            if ($elapsed > $this->timeout) {
                return HealthCheckResult::ko(\sprintf('Check timed out (%.1fs > %ds)', $elapsed, $this->timeout));
            }

            if ($result->status === HealthStatus::Ko) {
                return HealthCheckResult::ko($result->error ?? 'Unknown error');
            }

            return HealthCheckResult::ok();
        } catch (\Throwable $e) {
            return HealthCheckResult::ko($e->getMessage());
        }
    }
}
