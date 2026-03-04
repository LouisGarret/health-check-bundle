<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Command;

use Lgarret\HealthCheckBundle\Command\HealthCacheClearCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\Cache\CacheInterface;

final class HealthCacheClearCommandTest extends TestCase
{
    public function testClearCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::once())
            ->method('delete')
            ->with('health_check_result')
            ->willReturn(true);

        $tester = $this->createCommandTester($cache);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Health check cache cleared', $tester->getDisplay());
    }

    public function testNoCacheConfigured(): void
    {
        $tester = $this->createCommandTester(null);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('No cache adapter configured', $tester->getDisplay());
    }

    private function createCommandTester(?CacheInterface $cache): CommandTester
    {
        $command = new HealthCacheClearCommand($cache);
        $application = new Application();
        $application->add($command);

        return new CommandTester($application->find('health:cache:clear'));
    }
}
