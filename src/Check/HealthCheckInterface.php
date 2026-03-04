<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Check;

use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;

interface HealthCheckInterface
{
    public function getName(): string;

    public function check(): HealthCheckResult;
}
