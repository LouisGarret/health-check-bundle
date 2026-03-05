<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Check\Builtin;

use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;

final class AssetMapperCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly string $manifestPath,
    ) {
    }

    public function getName(): string
    {
        return BuiltinCheck::AssetMapper->value;
    }

    public function check(): HealthCheckResult
    {
        if (!file_exists($this->manifestPath)) {
            return HealthCheckResult::ko(\sprintf('Manifest not found at %s. Run "asset-map:compile".', $this->manifestPath));
        }

        return HealthCheckResult::ok();
    }
}
