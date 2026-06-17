<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Client;

use Lgarret\HealthCheckBundle\Dto\HealthCheckReport;
use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;
use Lgarret\HealthCheckBundle\Dto\HealthStatus;
use Lgarret\HealthCheckBundle\Dto\RemoteHealthCheckResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HealthCheckClient
{
    private const DEFAULT_TIMEOUT = 5.0;
    private const DEFAULT_HEADER = 'Authorization';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function check(string $url, ?string $secret = null, string $header = self::DEFAULT_HEADER, ?float $timeout = null): RemoteHealthCheckResult
    {
        $hasSecret = $secret !== null && $secret !== '';

        try {
            $response = $this->httpClient->request(Request::METHOD_GET, $url, [
                'headers' => $hasSecret ? [$header => $secret] : [],
                'timeout' => $timeout ?? self::DEFAULT_TIMEOUT,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (ExceptionInterface $e) {
            return RemoteHealthCheckResult::failure($e->getMessage());
        }

        $data = json_decode($content, true);

        if (!\is_array($data) || !\is_string($data['status'] ?? null)) {
            return RemoteHealthCheckResult::failure(\sprintf('Unexpected response (HTTP %d): invalid JSON payload', $statusCode));
        }

        $status = $this->parseStatus($data['status']);

        if ($status === null) {
            return RemoteHealthCheckResult::failure(\sprintf('Unexpected response (HTTP %d): unknown status "%s"', $statusCode, $data['status']));
        }

        return RemoteHealthCheckResult::success(new HealthCheckReport($status, $this->parseChecks($data['checks'] ?? null)));
    }

    /**
     * @return array<string, HealthCheckResult>
     */
    private function parseChecks(mixed $checksData): array
    {
        if (!\is_array($checksData)) {
            return [];
        }

        $checks = [];

        foreach ($checksData as $name => $checkData) {
            if (!\is_array($checkData) || !\is_string($checkData['status'] ?? null)) {
                continue;
            }

            $checkStatus = $this->parseStatus($checkData['status']);

            if ($checkStatus === null) {
                continue;
            }

            $error = \is_string($checkData['error'] ?? null) ? $checkData['error'] : 'Unknown error';

            $checks[(string) $name] = $checkStatus === HealthStatus::Ok
                ? HealthCheckResult::ok()
                : HealthCheckResult::ko($error);
        }

        return $checks;
    }

    private function parseStatus(string $status): ?HealthStatus
    {
        return HealthStatus::tryFrom(mb_strtolower($status));
    }
}
