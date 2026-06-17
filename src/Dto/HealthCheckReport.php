<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Dto;

final readonly class HealthCheckReport implements \JsonSerializable
{
    /**
     * @param array<string, HealthCheckResult> $checks
     */
    public function __construct(
        public HealthStatus $status,
        public array $checks,
    ) {
    }

    /**
     * @return array{status: HealthStatus, checks: array<string, HealthCheckResult>}
     */
    public function jsonSerialize(): array
    {
        return ['status' => $this->status, 'checks' => $this->checks];
    }
}
