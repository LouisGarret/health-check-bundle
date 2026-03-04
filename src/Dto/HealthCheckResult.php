<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Dto;

final readonly class HealthCheckResult
{
    public function __construct(
        public bool $success,
        public ?string $error = null,
    ) {
    }

    public static function ok(): self
    {
        return new self(success: true);
    }

    public static function ko(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
