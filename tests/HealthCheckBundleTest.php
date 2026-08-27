<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests;

use Lgarret\HealthCheckBundle\HealthCheckBundle;
use PHPUnit\Framework\TestCase;

final class HealthCheckBundleTest extends TestCase
{
    /**
     * "@HealthCheckBundle/..." resources are resolved against getPath(), so it must
     * point at the package root where config/ lives, not at src/.
     */
    public function testGetPathResolvesToThePackageRoot(): void
    {
        $bundle = new HealthCheckBundle();

        self::assertFileExists($bundle->getPath() . '/config/routes.php');
        self::assertFileExists($bundle->getPath() . '/config/services.php');
    }
}
