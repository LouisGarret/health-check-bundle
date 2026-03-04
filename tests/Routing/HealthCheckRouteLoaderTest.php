<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Routing;

use Lgarret\HealthCheckBundle\Controller\HealthCheckController;
use Lgarret\HealthCheckBundle\Routing\HealthCheckRouteLoader;
use PHPUnit\Framework\TestCase;

final class HealthCheckRouteLoaderTest extends TestCase
{
    public function testDefaultPath(): void
    {
        $loader = new HealthCheckRouteLoader();
        $routes = $loader->load('.', 'health_check');

        $route = $routes->get('health_check');
        self::assertNotNull($route);
        self::assertSame('/health', $route->getPath());
        self::assertSame(HealthCheckController::class, $route->getDefault('_controller'));
        self::assertSame(['GET'], $route->getMethods());
    }

    public function testCustomPath(): void
    {
        $loader = new HealthCheckRouteLoader(path: '/healthz');
        $routes = $loader->load('.', 'health_check');

        $route = $routes->get('health_check');
        self::assertNotNull($route);
        self::assertSame('/healthz', $route->getPath());
    }

    public function testSupportsHealthCheckType(): void
    {
        $loader = new HealthCheckRouteLoader();

        self::assertTrue($loader->supports('.', 'health_check'));
        self::assertFalse($loader->supports('.', 'annotation'));
        self::assertFalse($loader->supports('.', null));
    }

    public function testCannotLoadTwice(): void
    {
        $loader = new HealthCheckRouteLoader();
        $loader->load('.', 'health_check');

        $this->expectException(\RuntimeException::class);
        $loader->load('.', 'health_check');
    }
}
