<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Dto;

final readonly class RemoteHealthCheckResult
{
    public bool $reachable;

    private function __construct(
        public ?HealthCheckReport $report = null,
        public ?string $error = null,
    ) {
        $this->reachable = $report !== null;
    }

    public static function success(HealthCheckReport $report): self
    {
        return new self(report: $report);
    }

    public static function failure(string $error): self
    {
        return new self(error: $error);
    }
}
