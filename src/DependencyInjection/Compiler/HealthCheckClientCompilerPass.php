<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\DependencyInjection\Compiler;

use Lgarret\HealthCheckBundle\Client\HealthCheckClient;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class HealthCheckClientCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('http_client')) {
            return;
        }

        $container->register(HealthCheckClient::class, HealthCheckClient::class)
            ->setArgument('$httpClient', new Reference('http_client'));
    }
}
