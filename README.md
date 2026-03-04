# HealthCheckBundle

[![CI](https://github.com/LouisGarret/health-check-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/LouisGarret/health-check-bundle/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/lgarret/health-check-bundle/v)](https://packagist.org/packages/lgarret/health-check-bundle)
[![License](https://poser.pugx.org/lgarret/health-check-bundle/license)](https://packagist.org/packages/lgarret/health-check-bundle)

A Symfony bundle providing a `/health` endpoint to monitor your application and its dependencies.

## Installation

```bash
composer require lgarret/health-check-bundle
```

### Register the bundle

```php
// config/bundles.php
return [
    // ...
    Lgarret\HealthCheckBundle\HealthCheckBundle::class => ['all' => true],
];
```

### Import routes

```yaml
# config/routes/health_check.yaml
health_check:
    resource: '@HealthCheckBundle/config/routes.php'
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

| Check      | Package required  | What it does                                       |
|------------|-------------------|----------------------------------------------------|
| `doctrine` | `doctrine/dbal`   | Runs `SELECT 1` on each configured DBAL connection |

Built-in checks are enabled by default and auto-detected via `class_exists()` and service availability. One check is registered per Doctrine connection (e.g. `doctrine_default`, `doctrine_analytics`). You can disable them in your configuration:

```yaml
health_check:
    checks:
        doctrine: false
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

## Flex recipe

Sample configuration files are available in the `recipe/` directory for use with Symfony Flex.

## Development

```bash
composer install
vendor/bin/phpunit          # Run tests
vendor/bin/phpstan analyse  # Static analysis (level max)
```

## License

MIT
