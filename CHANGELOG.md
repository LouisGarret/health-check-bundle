# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `HealthCheckInterface` for creating custom health checks with auto-discovery
- `HealthCheckResult` DTO with `ok()` / `ko()` static factories
- `HealthCheckService` to run all registered checks with timeout and cache support
- `HealthCheckController` on configurable route (default `/health`)
- Auth-gated detailed response via configurable secret and header
- `health:check` console command
- `health:cache:clear` console command to invalidate cached results
- Built-in `DoctrineCheck` — auto-registered for each configured DBAL connection
- Configurable `checks.doctrine` to enable/disable built-in checks
- Compiler pass for safe auto-detection of built-in check dependencies
- Configurable route path, timeout, cache TTL
- Dynamic route loader (`HealthCheckRouteLoader`)
- PHPStan level max
- PHP CS Fixer with @PSR12 + @Symfony rules
- GitHub Actions CI (PHP 8.3 / 8.4 / 8.5)
- PHPUnit test suite with coverage
- Flex recipe sample files
