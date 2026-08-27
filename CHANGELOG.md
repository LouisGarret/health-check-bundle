# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- A real Symfony Flex recipe, served from a Flex endpoint published at
  `https://raw.githubusercontent.com/LouisGarret/health-check-bundle/main/flex/index.json`.
  Projects that register it in `extra.symfony.endpoint` now get `config/packages/health_check.yaml`,
  `config/routes/health_check.yaml` and the `config/bundles.php` entry on `composer require`,
  instead of falling back to Flex's auto-generated recipe
- `composer flex-endpoint` / `composer flex-endpoint-check` to regenerate and verify that endpoint;
  the check runs in CI

### Changed

- `recipe/` moved to the `recipe/lgarret/health-check-bundle/<version>/` layout used by
  `symfony/recipes-contrib`, and gained the `manifest.json` it was missing. The previous
  `recipe/config/` files were inert: Flex never reads recipes from inside an installed package
- The recipe imports routes through the bundle's route loader (`resource: .`, `type: health_check`),
  which works on every released version, rather than `@HealthCheckBundle/config/routes.php`,
  which needs 1.2.1 or later

## [1.2.1] - 2026-08-27

### Fixed

- `@HealthCheckBundle/config/routes.php` could not be resolved: the bundle inherited `Bundle::getPath()`, which points at `src/`, while `config/` lives at the package root. Importing the routes as documented in the README failed the router warmup with `Unable to find file "@HealthCheckBundle/config/routes.php"`, breaking `cache:clear` and every request

## [1.2.0] - 2026-06-18

### Added

- `HealthCheckClient` to query the `/health` endpoint of a remote application (with secret/header support) and get back a typed `RemoteHealthCheckResult` — auto-registered only when `symfony/http-client` is installed (suggested, optional), like the built-in checks

### Changed

- `HealthCheckService::runAll()` now returns a `HealthCheckReport` DTO instead of a plain array, and per-check results are typed `HealthCheckResult` instances instead of array shapes — **BREAKING**: `HealthCheckResult::$success` (`bool`) is replaced by `HealthCheckResult::$status` (`HealthStatus`). Custom checks that only use the `ok()` / `ko()` factories are unaffected; code reading `->success` directly must switch to `->status === HealthStatus::Ok`.

## [1.1.0] - 2026-05-30

### Added

- Support for Symfony 8.0 / 8.1 (`symfony/cache`, `symfony/console`, `symfony/framework-bundle` now allow `^8.0` alongside `^6.4` and `^7.0`)

### Changed

- Command tests no longer rely on the `Application::add()` method removed in Symfony 8, passing the command directly to `CommandTester` instead

## [1.0.3] - 2026-03-05

### Added

- `HealthStatus` string-backed enum (`ok` / `ko`) replacing hardcoded status strings
- Built-in `AssetMapperCheck` — auto-registered when `symfony/asset-mapper` is installed, verifies `manifest.json` exists
- Documentation for SecurityBundle `access_control` configuration

## [1.0.2] - 2026-03-05

### Added

- `HealthStatus` string-backed enum (`ok` / `ko`) replacing hardcoded status strings

## [1.0.1] - 2026-03-04

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
