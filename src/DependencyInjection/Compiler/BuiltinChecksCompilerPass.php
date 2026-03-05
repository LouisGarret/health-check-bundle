<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\DependencyInjection\Compiler;

use Doctrine\DBAL\Connection;
use Lgarret\HealthCheckBundle\Check\Builtin\AssetMapperCheck;
use Lgarret\HealthCheckBundle\Check\Builtin\BuiltinCheck;
use Lgarret\HealthCheckBundle\Check\Builtin\DoctrineCheck;
use Symfony\Component\AssetMapper\AssetMapper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuiltinChecksCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->registerDoctrineChecks($container);
        $this->registerAssetMapperCheck($container);
    }

    private function isCheckEnabled(ContainerBuilder $container, BuiltinCheck $check): bool
    {
        $parameter = 'health_check.checks.' . $check->value;

        return $container->hasParameter($parameter) && (bool) $container->getParameter($parameter);
    }

    private function registerDoctrineChecks(ContainerBuilder $container): void
    {
        if (!$this->isCheckEnabled($container, BuiltinCheck::Doctrine)) {
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
            $container->register('health_check.' . BuiltinCheck::Doctrine->value . '.' . $name, DoctrineCheck::class)
                ->setArgument('$connection', new Reference($serviceId))
                ->setArgument('$connectionName', $name)
                ->addTag('health_check.check');
        }
    }

    private function registerAssetMapperCheck(ContainerBuilder $container): void
    {
        if (!$this->isCheckEnabled($container, BuiltinCheck::AssetMapper)) {
            return;
        }

        if (!class_exists(AssetMapper::class)) {
            return;
        }

        if (!$container->hasDefinition('asset_mapper')) {
            return;
        }

        /** @var string $projectDir */
        $projectDir = $container->getParameter('kernel.project_dir');
        $manifestPath = $projectDir . '/public/assets/manifest.json';

        $container->register('health_check.' . BuiltinCheck::AssetMapper->value, AssetMapperCheck::class)
            ->setArgument('$manifestPath', $manifestPath)
            ->addTag('health_check.check');
    }
}
