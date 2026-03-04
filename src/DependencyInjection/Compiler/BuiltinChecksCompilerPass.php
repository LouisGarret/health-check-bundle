<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\DependencyInjection\Compiler;

use Doctrine\DBAL\Connection;
use Lgarret\HealthCheckBundle\Check\Builtin\DoctrineCheck;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuiltinChecksCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->registerDoctrineChecks($container);
    }

    private function registerDoctrineChecks(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('health_check.checks.doctrine') || !$container->getParameter('health_check.checks.doctrine')) {
            return;
        }

        if (!class_exists(Connection::class)) {
            return;
        }

        if (!$container->hasParameter('doctrine.connections')) {
            return;
        }

        /** @var array<string, string> $connections */
        $connections = $container->getParameter('doctrine.connections');

        foreach ($connections as $name => $serviceId) {
            $container->register('health_check.doctrine.' . $name, DoctrineCheck::class)
                ->setArgument('$connection', new Reference($serviceId))
                ->setArgument('$connectionName', $name)
                ->addTag('health_check.check');
        }
    }
}
