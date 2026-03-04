<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\DependencyInjection;

use Lgarret\HealthCheckBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = $this->processConfiguration([]);

        self::assertSame('/health', $config['path']);
        self::assertNull($config['secret']);
        self::assertSame('Authorization', $config['header']);
        self::assertSame(5, $config['timeout']);
        self::assertTrue($config['cache']['enabled']);
        self::assertSame(300, $config['cache']['ttl']);
        self::assertTrue($config['checks']['doctrine']);
    }

    public function testCustomValues(): void
    {
        $config = $this->processConfiguration([
            'path' => '/healthz',
            'secret' => 'my-token',
            'header' => 'X-Health-Token',
            'timeout' => 10,
            'cache' => [
                'enabled' => false,
                'ttl' => 60,
            ],
            'checks' => [
                'doctrine' => false,
            ],
        ]);

        self::assertSame('/healthz', $config['path']);
        self::assertSame('my-token', $config['secret']);
        self::assertSame('X-Health-Token', $config['header']);
        self::assertSame(10, $config['timeout']);
        self::assertFalse($config['cache']['enabled']);
        self::assertSame(60, $config['cache']['ttl']);
        self::assertFalse($config['checks']['doctrine']);
    }

    /**
     * @param array<string, mixed> $configs
     *
     * @return array{path: string, secret: ?string, header: string, timeout: int, cache: array{enabled: bool, ttl: int}, checks: array{doctrine: bool}}
     */
    private function processConfiguration(array $configs): array
    {
        $processor = new Processor();

        /** @var array{path: string, secret: ?string, header: string, timeout: int, cache: array{enabled: bool, ttl: int}, checks: array{doctrine: bool}} $config */
        $config = $processor->processConfiguration(new Configuration(), [$configs]);

        return $config;
    }
}
