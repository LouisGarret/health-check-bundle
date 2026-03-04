<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\DependencyInjection;

use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class HealthCheckExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        /** @var array{path: string, secret: ?string, header: string, timeout: int, cache: array{enabled: bool, ttl: int}, checks: array{doctrine: bool}} $config */
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('health_check.path', $config['path']);
        $container->setParameter('health_check.secret', $config['secret']);
        $container->setParameter('health_check.header', $config['header']);
        $container->setParameter('health_check.timeout', $config['timeout']);
        $container->setParameter('health_check.cache.enabled', $config['cache']['enabled']);
        $container->setParameter('health_check.cache.ttl', $config['cache']['ttl']);
        $container->setParameter('health_check.checks.doctrine', $config['checks']['doctrine']);

        $container->registerForAutoconfiguration(HealthCheckInterface::class)
            ->addTag('health_check.check');

        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.php');
    }
}
