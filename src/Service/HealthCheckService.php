<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Service;

use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
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
     *     status: 'ok'|'ko',
     *     checks: array<string, array{status: 'ok'|'ko', error?: string}>
     * }
     */
    public function runAll(): array
    {
        if ($this->cacheEnabled && $this->cache !== null) {
            /** @var array{status: 'ok'|'ko', checks: array<string, array{status: 'ok'|'ko', error?: string}>} $cached */
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
     *     status: 'ok'|'ko',
     *     checks: array<string, array{status: 'ok'|'ko', error?: string}>
     * }
     */
    private function executeChecks(): array
    {
        /** @var 'ok'|'ko' $globalStatus */
        $globalStatus = 'ok';
        $checks = [];

        foreach ($this->checks as $check) {
            $checkData = $this->executeCheck($check);

            if ($checkData['status'] === 'ko') {
                $globalStatus = 'ko';
            }

            $checks[$check->getName()] = $checkData;
        }

        return [
            'status' => $globalStatus,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: 'ok'|'ko', error?: string}
     */
    private function executeCheck(HealthCheckInterface $check): array
    {
        $startTime = microtime(true);

        try {
            $result = $check->check();
            $elapsed = microtime(true) - $startTime;

            if ($elapsed > $this->timeout) {
                return ['status' => 'ko', 'error' => \sprintf('Check timed out (%.1fs > %ds)', $elapsed, $this->timeout)];
            }

            $checkData = ['status' => $result->success ? 'ok' : 'ko'];

            if (!$result->success) {
                $checkData['error'] = $result->error ?? 'Unknown error';
            }

            return $checkData;
        } catch (\Throwable $e) {
            return ['status' => 'ko', 'error' => $e->getMessage()];
        }
    }
}
