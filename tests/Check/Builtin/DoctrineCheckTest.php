<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Check\Builtin;

use Doctrine\DBAL\Connection;
use Lgarret\HealthCheckBundle\Check\Builtin\DoctrineCheck;
use PHPUnit\Framework\TestCase;

final class DoctrineCheckTest extends TestCase
{
    public function testGetName(): void
    {
        $connection = $this->createMock(Connection::class);
        $check = new DoctrineCheck($connection);

        self::assertSame('doctrine_default', $check->getName());
    }

    public function testGetNameWithCustomConnectionName(): void
    {
        $connection = $this->createMock(Connection::class);
        $check = new DoctrineCheck($connection, 'analytics');

        self::assertSame('doctrine_analytics', $check->getName());
    }

    public function testCheckOk(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('executeQuery')
            ->with('SELECT 1');

        $check = new DoctrineCheck($connection);
        $result = $check->check();

        self::assertTrue($result->success);
        self::assertNull($result->error);
    }

    public function testCheckKo(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('executeQuery')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $check = new DoctrineCheck($connection);
        $result = $check->check();

        self::assertFalse($result->success);
        self::assertSame('Connection refused', $result->error);
    }
}
