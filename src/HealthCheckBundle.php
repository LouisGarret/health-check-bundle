<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle;

use Lgarret\HealthCheckBundle\DependencyInjection\Compiler\BuiltinChecksCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class HealthCheckBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new BuiltinChecksCompilerPass());
    }
}
