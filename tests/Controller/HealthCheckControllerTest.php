<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Controller;

use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
use Lgarret\HealthCheckBundle\Controller\HealthCheckController;
use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;
use Lgarret\HealthCheckBundle\Service\HealthCheckService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class HealthCheckControllerTest extends TestCase
{
    public function testReturnsOkStatusWithoutAuth(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);

        $controller = new HealthCheckController($service, secret: null);
        $response = $controller(new Request());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"status":"ok"}', $response->getContent());
    }

    public function testReturnsKoStatusWithoutAuth(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('redis', HealthCheckResult::ko('down')),
        ]);

        $controller = new HealthCheckController($service, secret: null);
        $response = $controller(new Request());

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('{"status":"ko"}', $response->getContent());
    }

    public function testReturnsDetailedResponseWithValidAuth(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);

        $controller = new HealthCheckController($service, secret: 'my-secret');
        $request = new Request();
        $request->headers->set('Authorization', 'my-secret');

        $response = $controller($request);

        self::assertSame(200, $response->getStatusCode());

        $content = $response->getContent();
        self::assertIsString($content);
        /** @var array{status: string, checks: array<string, array{status: string}>} $data */
        $data = json_decode($content, true);
        self::assertSame('ok', $data['status']);
        self::assertArrayHasKey('checks', $data);
        self::assertSame(['status' => 'ok'], $data['checks']['database']);
    }

    public function testReturnsSimpleResponseWithInvalidAuth(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);

        $controller = new HealthCheckController($service, secret: 'my-secret');
        $request = new Request();
        $request->headers->set('Authorization', 'wrong-secret');

        $response = $controller($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"status":"ok"}', $response->getContent());
    }

    public function testReturnsSimpleResponseWithNoAuthHeader(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);

        $controller = new HealthCheckController($service, secret: 'my-secret');
        $response = $controller(new Request());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"status":"ok"}', $response->getContent());
    }

    public function testCustomHeaderName(): void
    {
        $service = $this->buildService([
            $this->createHealthCheck('database', HealthCheckResult::ok()),
        ]);

        $controller = new HealthCheckController($service, secret: 'my-secret', header: 'X-Health-Token');
        $request = new Request();
        $request->headers->set('X-Health-Token', 'my-secret');

        $response = $controller($request);

        $content = $response->getContent();
        self::assertIsString($content);
        /** @var array<string, mixed> $data */
        $data = json_decode($content, true);
        self::assertArrayHasKey('checks', $data);
    }

    /**
     * @param HealthCheckInterface[] $checks
     */
    private function buildService(array $checks): HealthCheckService
    {
        return new HealthCheckService(checks: $checks, cacheEnabled: false);
    }

    private function createHealthCheck(string $name, HealthCheckResult $result): HealthCheckInterface
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn($name);
        $check->method('check')->willReturn($result);

        return $check;
    }
}
