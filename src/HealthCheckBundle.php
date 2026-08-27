<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle;

use Lgarret\HealthCheckBundle\DependencyInjection\Compiler\BuiltinChecksCompilerPass;
use Lgarret\HealthCheckBundle\DependencyInjection\Compiler\HealthCheckClientCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class HealthCheckBundle extends Bundle
{
    /**
     * The bundle class lives in src/, but config/ sits at the package root,
     * so "@HealthCheckBundle/config/routes.php" must resolve from there.
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new BuiltinChecksCompilerPass());
        $container->addCompilerPass(new HealthCheckClientCompilerPass());
    }
}
