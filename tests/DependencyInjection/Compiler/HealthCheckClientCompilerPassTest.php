<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\DependencyInjection\Compiler;

use Lgarret\HealthCheckBundle\Client\HealthCheckClient;
use Lgarret\HealthCheckBundle\DependencyInjection\Compiler\HealthCheckClientCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class HealthCheckClientCompilerPassTest extends TestCase
{
    public function testClientRegisteredWhenHttpClientAvailable(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('http_client', new Definition());

        $pass = new HealthCheckClientCompilerPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition(HealthCheckClient::class));

        $definition = $container->getDefinition(HealthCheckClient::class);
        $argument = $definition->getArgument('$httpClient');

        self::assertInstanceOf(Reference::class, $argument);
        self::assertSame('http_client', (string) $argument);
    }

    public function testClientNotRegisteredWhenHttpClientUnavailable(): void
    {
        $container = new ContainerBuilder();

        $pass = new HealthCheckClientCompilerPass();
        $pass->process($container);

        self::assertFalse($container->hasDefinition(HealthCheckClient::class));
    }
}
