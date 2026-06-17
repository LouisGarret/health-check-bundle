<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Dto;

final readonly class HealthCheckResult implements \JsonSerializable
{
    public function __construct(
        public HealthStatus $status,
        public ?string $error = null,
    ) {
    }

    public static function ok(): self
    {
        return new self(HealthStatus::Ok);
    }

    public static function ko(string $error): self
    {
        return new self(HealthStatus::Ko, $error);
    }

    /**
     * @return array{status: HealthStatus, error?: string}
     */
    public function jsonSerialize(): array
    {
        if ($this->error === null) {
            return ['status' => $this->status];
        }

        return ['status' => $this->status, 'error' => $this->error];
    }
}
