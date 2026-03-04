<?php

declare(strict_types=1);

use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
use Lgarret\HealthCheckBundle\Command\HealthCacheClearCommand;
use Lgarret\HealthCheckBundle\Command\HealthCheckCommand;
use Lgarret\HealthCheckBundle\Controller\HealthCheckController;
use Lgarret\HealthCheckBundle\Routing\HealthCheckRouteLoader;
use Lgarret\HealthCheckBundle\Service\HealthCheckService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->instanceof(HealthCheckInterface::class)
        ->tag('health_check.check');

    $services->set(HealthCheckService::class)
        ->arg('$checks', tagged_iterator('health_check.check'))
        ->arg('$cache', service('cache.app')->nullOnInvalid())
        ->arg('$cacheEnabled', '%health_check.cache.enabled%')
        ->arg('$cacheTtl', '%health_check.cache.ttl%')
        ->arg('$timeout', '%health_check.timeout%');

    $services->set(HealthCheckController::class)
        ->arg('$healthCheckService', service(HealthCheckService::class))
        ->arg('$secret', '%health_check.secret%')
        ->arg('$header', '%health_check.header%')
        ->tag('controller.service_arguments');

    $services->set(HealthCheckRouteLoader::class)
        ->arg('$path', '%health_check.path%')
        ->tag('routing.loader');

    $services->set(HealthCheckCommand::class)
        ->arg('$healthCheckService', service(HealthCheckService::class))
        ->tag('console.command');

    $services->set(HealthCacheClearCommand::class)
        ->arg('$cache', service('cache.app')->nullOnInvalid())
        ->tag('console.command');
};
