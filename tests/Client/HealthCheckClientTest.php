<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Tests\Client;

use Lgarret\HealthCheckBundle\Client\HealthCheckClient;
use Lgarret\HealthCheckBundle\Dto\HealthStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HealthCheckClientTest extends TestCase
{
    public function testCheckReturnsDetailedReportWithSecret(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertSame('GET', $method);
            self::assertSame('https://example.com/health', $url);
            self::assertIsArray($options['headers']);
            self::assertContains('Authorization: my-secret', $options['headers']);

            return new MockResponse(
                '{"status":"ok","checks":{"database":{"status":"ok"}}}',
                ['http_code' => 200],
            );
        });

        $client = new HealthCheckClient($httpClient);
        $result = $client->check('https://example.com/health', secret: 'my-secret');

        self::assertTrue($result->reachable);
        self::assertNull($result->error);
        self::assertNotNull($result->report);
        self::assertSame(HealthStatus::Ok, $result->report->status);
        self::assertSame(HealthStatus::Ok, $result->report->checks['database']->status);
    }

    public function testCheckWithFailingRemoteCheck(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            '{"status":"ko","checks":{"redis":{"status":"ko","error":"Connection refused"}}}',
            ['http_code' => 503],
        ));

        $client = new HealthCheckClient($httpClient);
        $result = $client->check('https://example.com/health', secret: 'my-secret');

        self::assertTrue($result->reachable);
        self::assertNotNull($result->report);
        self::assertSame(HealthStatus::Ko, $result->report->status);
        self::assertSame(HealthStatus::Ko, $result->report->checks['redis']->status);
        self::assertSame('Connection refused', $result->report->checks['redis']->error);
    }

    public function testCheckWithoutSecretReturnsOnlyStatus(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertIsArray($options['headers']);
            self::assertSame([], array_filter($options['headers'], static fn (mixed $header): bool => \is_string($header) && str_starts_with($header, 'Authorization:')));

            return new MockResponse('{"status":"ok"}', ['http_code' => 200]);
        });

        $client = new HealthCheckClient($httpClient);
        $result = $client->check('https://example.com/health');

        self::assertTrue($result->reachable);
        self::assertNotNull($result->report);
        self::assertSame(HealthStatus::Ok, $result->report->status);
        self::assertSame([], $result->report->checks);
    }

    public function testCheckWithCustomHeader(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertIsArray($options['headers']);
            self::assertContains('X-Health-Token: my-secret', $options['headers']);

            return new MockResponse('{"status":"ok"}', ['http_code' => 200]);
        });

        $client = new HealthCheckClient($httpClient);
        $client->check('https://example.com/health', secret: 'my-secret', header: 'X-Health-Token');
    }

    public function testCheckWithTransportError(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['error' => 'Connection refused']));

        $client = new HealthCheckClient($httpClient);
        $result = $client->check('https://example.com/health');

        self::assertFalse($result->reachable);
        self::assertNull($result->report);
        self::assertSame('Connection refused', $result->error);
    }

    public function testCheckWithInvalidJsonPayload(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('not json', ['http_code' => 200]));

        $client = new HealthCheckClient($httpClient);
        $result = $client->check('https://example.com/health');

        self::assertFalse($result->reachable);
        self::assertNull($result->report);
        self::assertStringContainsString('invalid JSON payload', $result->error ?? '');
    }

    public function testCheckWithUnknownStatusValue(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('{"status":"unknown"}', ['http_code' => 200]));

        $client = new HealthCheckClient($httpClient);
        $result = $client->check('https://example.com/health');

        self::assertFalse($result->reachable);
        self::assertStringContainsString('unknown status', $result->error ?? '');
    }

    public function testCheckWithUppercaseStatusIsParsedCaseInsensitively(): void
    {
        $httpClient = new MockHttpClient(new MockResponse(
            '{"status":"OK","checks":{"database":{"status":"OK"}}}',
            ['http_code' => 200],
        ));

        $client = new HealthCheckClient($httpClient);
        $result = $client->check('https://example.com/health');

        self::assertTrue($result->reachable);
        self::assertNotNull($result->report);
        self::assertSame(HealthStatus::Ok, $result->report->status);
        self::assertSame(HealthStatus::Ok, $result->report->checks['database']->status);
    }

    public function testCheckWithNumericCheckNameIsPreserved(): void
    {
        // PHP itself coerces a purely-numeric array key (string or not) to int —
        // this test only guards against the entry being dropped entirely, not against
        // the key remaining a string, which PHP does not allow.
        $httpClient = new MockHttpClient(new MockResponse(
            '{"status":"ko","checks":{"42":{"status":"ko","error":"down"}}}',
            ['http_code' => 503],
        ));

        $client = new HealthCheckClient($httpClient);
        $result = $client->check('https://example.com/health');

        self::assertNotNull($result->report);
        self::assertCount(1, $result->report->checks);

        $name = array_key_first($result->report->checks);

        self::assertNotNull($name);
        self::assertSame(42, $name);

        $check = $result->report->checks[$name];
        self::assertSame(HealthStatus::Ko, $check->status);
        self::assertSame('down', $check->error);
    }

    public function testCheckWithEmptySecretIsTreatedAsNoSecret(): void
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::assertIsArray($options['headers']);
            self::assertSame([], array_filter($options['headers'], static fn (mixed $header): bool => \is_string($header) && str_starts_with($header, 'Authorization:')));

            return new MockResponse('{"status":"ok"}', ['http_code' => 200]);
        });

        $client = new HealthCheckClient($httpClient);
        $client->check('https://example.com/health', secret: '');
    }
}
