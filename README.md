# HealthCheckBundle

[![CI](https://github.com/LouisGarret/health-check-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/LouisGarret/health-check-bundle/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/lgarret/health-check-bundle/v)](https://packagist.org/packages/lgarret/health-check-bundle)
[![License](https://poser.pugx.org/lgarret/health-check-bundle/license)](https://packagist.org/packages/lgarret/health-check-bundle)

A Symfony bundle providing a `/health` endpoint to monitor your application and its dependencies.

## Installation

```bash
composer require lgarret/health-check-bundle
```

A Flex recipe is [awaiting review](https://github.com/symfony/recipes-contrib/pull/2035)
in `symfony/recipes-contrib`. Once it is merged, `composer require` asks whether to
execute it — answer `y` and everything below is configured for you.

Flex skips contrib recipes in non-interactive installs (CI, Docker images built with
`--no-interaction`). Projects that rely on them there should opt in once:

```bash
composer config extra.symfony.allow-contrib true
```

Until the recipe is merged, set the bundle up by hand.

### Manual setup

#### Register the bundle

```php
// config/bundles.php
return [
    // ...
    Lgarret\HealthCheckBundle\HealthCheckBundle::class => ['all' => true],
];
```

#### Import routes

```yaml
# config/routes/health_check.yaml
health_check:
    resource: .
    type: health_check
```

This is the form the recipe installs: it goes through the bundle's route loader
and works on every released version. Importing the file directly is equivalent,
but requires 1.2.1 or later:

```yaml
health_check:
    resource: '@HealthCheckBundle/config/routes.php'
```

#### Allow public access

If you use the Symfony SecurityBundle, make sure the health route is publicly accessible:

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/health$, roles: PUBLIC_ACCESS }
        # ...
```

## Configuration

```yaml
# config/packages/health_check.yaml
health_check:
    path: '/health'                           # Route path (default: /health)
    secret: '%env(HEALTH_CHECK_SECRET)%'      # Optional — token to access detailed results
    header: 'X-Health-Token'                  # Optional — custom header name (default: Authorization)
    timeout: 5                                # Optional — max seconds per check (default: 5)
    cache:
        enabled: true                         # Optional — cache check results (default: true)
        ttl: 300                              # Optional — cache duration in seconds (default: 300)
    checks:
        doctrine: true                        # Optional — auto-register Doctrine DBAL checks (default: true)
        asset_mapper: true                    # Optional — auto-register AssetMapper check (default: true)
```

| Option            | Type      | Default         | Description                                                                   |
|-------------------|-----------|-----------------|-------------------------------------------------------------------------------|
| `path`            | `string`  | `/health`       | URL path for the health check endpoint.                                       |
| `secret`          | `string?` | `null`          | Token expected in the configured header. If `null`, details are never exposed. |
| `header`          | `string`  | `Authorization` | Name of the HTTP header used to send the secret token.                        |
| `timeout`         | `int`     | `5`             | Maximum execution time in seconds for each individual check.                  |
| `cache.enabled`   | `bool`    | `true`          | Enable caching of health check results.                                       |
| `cache.ttl`       | `int`     | `300`           | Cache TTL in seconds (5 minutes by default).                                  |
| `checks.doctrine` | `bool`    | `true`          | Auto-register Doctrine DBAL checks (one per connection) if `doctrine/dbal` is installed. |
| `checks.asset_mapper` | `bool` | `true`       | Auto-register AssetMapper check if `symfony/asset-mapper` is installed. |

## Usage

### `GET /health`

**Without auth header** (or without a configured secret):

```
GET /health
→ 200 {"status": "ok"}
→ 503 {"status": "ko"}
```

**With a valid auth header**:

```
GET /health
X-Health-Token: my-secret-token

→ 200 {"status": "ok", "checks": {"database": {"status": "ok"}, "redis": {"status": "ok"}}}
→ 503 {"status": "ko", "checks": {"database": {"status": "ok"}, "redis": {"status": "ko", "error": "Connection refused"}}}
```

### Console command

Run checks from the command line:

```bash
bin/console health:check
```

```
Health Check
============

 ---------- -------- --------------------
  Check      Status   Error
 ---------- -------- --------------------
  database   ✓ OK
  redis      ✗ KO     Connection refused
 ---------- -------- --------------------

 [ERROR] 1 of 2 check(s) failed.
```

### Built-in checks

The bundle ships with built-in checks that are **automatically registered** when the corresponding packages are installed:

| Check          | Package required              | What it does                                       |
|----------------|-------------------------------|----------------------------------------------------|
| `doctrine`     | `doctrine/dbal`               | Runs `SELECT 1` on each configured DBAL connection |
| `asset_mapper` | `symfony/asset-mapper`        | Checks that `manifest.json` exists (assets compiled) |

Built-in checks are enabled by default and auto-detected via `class_exists()` and service availability. One check is registered per Doctrine connection (e.g. `doctrine_default`, `doctrine_analytics`). The asset mapper check verifies that `public/assets/manifest.json` exists (i.e. `asset-map:compile` has been run). You can disable them in your configuration:

```yaml
health_check:
    checks:
        doctrine: false
        asset_mapper: false
```

### Creating a custom check

Implement `HealthCheckInterface` — the service will be automatically discovered and registered:

```php
<?php

namespace App\Check;

use Lgarret\HealthCheckBundle\Check\HealthCheckInterface;
use Lgarret\HealthCheckBundle\Dto\HealthCheckResult;
use Doctrine\DBAL\Connection;

class DatabaseHealthCheck implements HealthCheckInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'database';
    }

    public function check(): HealthCheckResult
    {
        try {
            $this->connection->executeQuery('SELECT 1');
            return HealthCheckResult::ok();
        } catch (\Throwable $e) {
            return HealthCheckResult::ko($e->getMessage());
        }
    }
}
```

### Querying a remote health check endpoint

`HealthCheckClient` lets you query the `/health` endpoint of another Symfony application (e.g. a different site running this same bundle) and get back a typed result instead of raw JSON. It requires `symfony/http-client`:

```bash
composer require symfony/http-client
```

```php
use Lgarret\HealthCheckBundle\Client\HealthCheckClient;

class StatusController
{
    public function __construct(private readonly HealthCheckClient $healthCheckClient)
    {
    }

    public function __invoke(): Response
    {
        $result = $this->healthCheckClient->check(
            url: 'https://other-site.example.com/health',
            secret: 'their-secret-token',
            header: 'X-Health-Token', // optional, defaults to "Authorization"
            timeout: 3.0,             // optional, defaults to 5 seconds
        );

        if (!$result->reachable) {
            // $result->error contains the network/parsing error (timeout, connection refused, invalid response, ...)
        }

        // $result->report is a HealthCheckReport (status + per-check HealthCheckResult), like the local one
    }
}
```

`HealthCheckClient::check()` returns a `RemoteHealthCheckResult`:

| Property     | Type                    | Description                                                                 |
|--------------|-------------------------|------------------------------------------------------------------------------|
| `reachable`  | `bool`                  | `false` if the request failed at the network level or the response couldn't be parsed |
| `report`     | `?HealthCheckReport`    | The remote health report (`status` + `checks`), present only when `reachable` is `true` |
| `error`      | `?string`               | Error message, present only when `reachable` is `false`                     |

If no `secret` (or an empty string) is provided, the remote endpoint will only return its global `status` (no `checks` detail), matching the unauthenticated response documented above.

`HealthCheckClient` is only registered as a service when `symfony/http-client` is installed (auto-detected, like the built-in checks). If it isn't installed, autowiring `HealthCheckClient` fails with Symfony's standard "service not found" error at compile time, rather than a runtime exception.

## Flex recipe

The recipe sources live in `recipe/lgarret/health-check-bundle/<version>/`, in the
layout [`symfony/recipes-contrib`](https://github.com/symfony/recipes-contrib) expects,
and are submitted there verbatim. Flex only ever fetches recipes from that repository —
never from inside an installed package — so changes here take effect through a pull
request, not a release.

## Development

```bash
composer install
vendor/bin/phpunit          # Run tests
vendor/bin/phpstan analyse  # Static analysis (level max)
```

## License

MIT
