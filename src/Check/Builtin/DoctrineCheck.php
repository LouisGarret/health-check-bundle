<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Check\Builtin;

use Doctrine\DBAL\Connection;
use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;

final class DoctrineCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $connectionName = 'default',
    ) {
    }

    public function getName(): string
    {
        return 'doctrine_' . $this->connectionName;
    }

    public function check(): HealthCheckResult
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return HealthCheckResult::ok();
        } catch (\Throwable $e) {
            return HealthCheckResult::ko($e->getMessage());
        }
    }
}
