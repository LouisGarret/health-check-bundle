<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Routing;

use Lgarret\HealthCheckBundle\Controller\HealthCheckController;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class HealthCheckRouteLoader extends Loader
{
    private bool $isLoaded = false;

    public function __construct(
        private readonly string $path = '/health',
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        if ($this->isLoaded) {
            throw new \RuntimeException('Do not add the "health_check" route loader twice.');
        }

        $routes = new RouteCollection();

        $routes->add('health_check', new Route(
            $this->path,
            ['_controller' => HealthCheckController::class],
            methods: ['GET'],
        ));

        $this->isLoaded = true;

        return $routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === 'health_check';
    }
}
