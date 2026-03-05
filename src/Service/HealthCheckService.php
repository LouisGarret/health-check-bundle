<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Service;

use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
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

    /**
     * @return array{
     *     status: HealthStatus,
     *     checks: array<string, array{status: HealthStatus, error?: string}>
     * }
     */
    public function runAll(): array
    {
        if ($this->cacheEnabled && $this->cache !== null) {
            /** @var array{status: HealthStatus, checks: array<string, array{status: HealthStatus, error?: string}>} $cached */
            $cached = $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
                $item->expiresAfter($this->cacheTtl);

                return $this->executeChecks();
            });

            return $cached;
        }

        return $this->executeChecks();
    }

    /**
     * @return array{
     *     status: HealthStatus,
     *     checks: array<string, array{status: HealthStatus, error?: string}>
     * }
     */
    private function executeChecks(): array
    {
        $globalStatus = HealthStatus::Ok;
        $checks = [];

        foreach ($this->checks as $check) {
            $checkData = $this->executeCheck($check);

            if ($checkData['status'] === HealthStatus::Ko) {
                $globalStatus = HealthStatus::Ko;
            }

            $checks[$check->getName()] = $checkData;
        }

        return [
            'status' => $globalStatus,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: HealthStatus, error?: string}
     */
    private function executeCheck(HealthCheckInterface $check): array
    {
        $startTime = microtime(true);

        try {
            $result = $check->check();
            $elapsed = microtime(true) - $startTime;

            if ($elapsed > $this->timeout) {
                return ['status' => HealthStatus::Ko, 'error' => \sprintf('Check timed out (%.1fs > %ds)', $elapsed, $this->timeout)];
            }

            $checkData = ['status' => $result->success ? HealthStatus::Ok : HealthStatus::Ko];

            if (!$result->success) {
                $checkData['error'] = $result->error ?? 'Unknown error';
            }

            return $checkData;
        } catch (\Throwable $e) {
            return ['status' => HealthStatus::Ko, 'error' => $e->getMessage()];
        }
    }
}
