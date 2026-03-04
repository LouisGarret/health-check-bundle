<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\DependencyInjection\Compiler;

use Lgarret\HealthCheckBundle\Check\Builtin\DoctrineCheck;
use Lgarret\HealthCheckBundle\DependencyInjection\Compiler\BuiltinChecksCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class BuiltinChecksCompilerPassTest extends TestCase
{
    public function testDoctrineChecksRegisteredForAllConnections(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('health_check.checks.doctrine', true);
        $container->setParameter('doctrine.connections', [
            'default' => 'doctrine.dbal.default_connection',
            'analytics' => 'doctrine.dbal.analytics_connection',
        ]);
        $container->setDefinition('doctrine.dbal.default_connection', new Definition());
        $container->setDefinition('doctrine.dbal.analytics_connection', new Definition());

        $pass = new BuiltinChecksCompilerPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('health_check.doctrine.default'));
        self::assertTrue($container->hasDefinition('health_check.doctrine.analytics'));
        self::assertTrue($container->getDefinition('health_check.doctrine.default')->hasTag('health_check.check'));
        self::assertTrue($container->getDefinition('health_check.doctrine.analytics')->hasTag('health_check.check'));
    }

    public function testDoctrineCheckNotRegisteredWhenDisabled(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('health_check.checks.doctrine', false);
        $container->setParameter('doctrine.connections', [
            'default' => 'doctrine.dbal.default_connection',
        ]);

        $pass = new BuiltinChecksCompilerPass();
        $pass->process($container);

        self::assertFalse($container->hasDefinition('health_check.doctrine.default'));
    }

    public function testDoctrineCheckNotRegisteredWhenNoDoctrineParameter(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('health_check.checks.doctrine', true);

        $pass = new BuiltinChecksCompilerPass();
        $pass->process($container);

        self::assertFalse($container->hasDefinition('health_check.doctrine.default'));
    }

    public function testSingleConnection(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('health_check.checks.doctrine', true);
        $container->setParameter('doctrine.connections', [
            'default' => 'doctrine.dbal.default_connection',
        ]);
        $container->setDefinition('doctrine.dbal.default_connection', new Definition());

        $pass = new BuiltinChecksCompilerPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('health_check.doctrine.default'));

        $definition = $container->getDefinition('health_check.doctrine.default');
        self::assertSame(DoctrineCheck::class, $definition->getClass());
    }
}
