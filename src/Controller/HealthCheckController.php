<?php

declare(strict_types=1);

namespace Lgarret\HealthCheckBundle\Controller;

use Lgarret\HealthCheckBundle\Service\HealthCheckService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class HealthCheckController
{
    public function __construct(
        private readonly HealthCheckService $healthCheckService,
        private readonly ?string $secret,
        private readonly string $header = 'Authorization',
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->healthCheckService->runAll();
        $statusCode = $result['status'] === 'ok' ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        if ($this->isAuthorized($request)) {
            $body = $result;
        } else {
            $body = ['status' => $result['status']];
        }

        return new JsonResponse($body, $statusCode);
    }

    private function isAuthorized(Request $request): bool
    {
        if ($this->secret === null) {
            return false;
        }

        $authorization = $request->headers->get($this->header);

        if ($authorization === null) {
            return false;
        }

        return hash_equals($this->secret, $authorization);
    }
}
