# Streaming Assessments Package

[![CI](https://github.com/streaming/assessments/actions/workflows/assessments-ci.yml/badge.svg)](https://github.com/streaming/assessments/actions/workflows/assessments-ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777bb3?logo=php)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Reusable Assessments (question bank + exams) module packaged for Laravel 12 projects.

## Requirements

- PHP 8.2+
- Laravel 12.x
- `spatie/laravel-package-tools` (pulled in automatically)

## Installation

### Packagist / Composer (recommended)

1. Require the package in your Laravel application:
   ```bash
   composer require streaming/assessments:^1.0.0-beta
   ```
2. Publish the configuration file so you can point the package to your Category & User models, guards, and route prefixes:
   ```bash
   php artisan vendor:publish --tag=assessments-config
   ```
3. Run the package migrations (automatically loaded once the service provider boots):
   ```bash
   php artisan migrate
   ```
4. Seed the default permissions / demo data if desired:
   ```bash
   php artisan db:seed --class="Streaming\\Assessments\\Database\\Seeders\\AssessmentsPermissionSeeder"
   ```

Routes, views, migrations, and config are auto-registered by the service provider. No manual service provider registration is required in Laravel 10+ thanks to package auto-discovery.

### Monorepo / path repository development

When hacking inside this monorepo you can keep using a local path repository so the host application picks up changes instantly:

```jsonc
// composer.json (host application)
"repositories": [
    {
        "type": "path",
        "url": "packages/assessments",
        "options": { "symlink": true }
    }
]
```

Then require the dev build:

```bash
composer require streaming/assessments:@dev --dev
```

This path workflow mirrors how GitHub Actions runs the package testbench and is handy before tagging a release for Packagist.

## Artisan Commands

| Command | Purpose |
| --- | --- |
| `assessments:backfill-schema-hash` | Computes `schema_hash` for questions/exams in batches. |
| `assessments:finalize-expired` | Auto-submits and grades expired in-progress attempts. |
| `assessments:rebuild-answer-usage` | Rebuilds aggregate usage metrics for answers/answer sets. |
| `assessments:reports` | Generates per-exam reporting snapshots (table/CSV/JSON, optional filters). |

## Configuration Highlights

- `assessments.enabled` / `assessments.admin_only` toggle the module surface.
- `assessments.models.category` / `assessments.models.user` (or env `ASSESSMENTS_MODEL_CATEGORY/USER`) must be set to the host app's Eloquent classes if the defaults cannot be auto-discovered by `Streaming\Assessments\Support\ModelResolver`.
- `assessments.middleware.*` controls the guard/middleware stack for admin / candidate web + API surfaces.
- `assessments.routes.*` adjusts path + name prefixes for dashboard and candidate endpoints so they can live under an existing `/dashboard` or `/api` namespace.
- `assessments.assembly.grace_seconds`, `assessments.exposure_*`, and `assessments.propagation_strict` tune the attempt lifecycle, exposure enforcement, and propagation safety checks.

See `config/assessments.php` (or the published copy) for full defaults and inline docs.

## Development Notes

- Host application classes under `app/Assessments` are thin wrappers that extend package controllers, services, and commands to preserve backwards compatibility while the module is extracted.
- Package controllers now consume custom FormRequest classes (`Streaming\\Assessments\\Support\\FormRequest`) which proxy through to any host base request when present (topics, exams, presets, answer sets UI/API, propagation, reviews, questions, candidate attempts), and API responses lean on dedicated resources (answer sets, attempt start, exam preview).
- Blade templates for admin + candidate UIs now live under `assessments::admin/*` and `assessments::assessments/candidate/*`; host views are pass-through includes for existing references.
- Temporary class aliases are defined in `src/helpers.php`; remove them once consumers switch fully to the `Streaming\Assessments\` namespace.
- Package migrations and seeders live in `database/{migrations,seeders}` under the package root. They are automatically loaded when running Artisan commands.
- Progress and sprint planning live in `co-pilot_assistance/assesment_package/*.md`.

## Testing

```
composer test --working-dir=packages/assessments
```

The package uses Orchestra Testbench with the `hr_test` database (see `phpunit.xml.dist`). Update the DB credentials there if your local environment differs.

## Release Process

We keep a small [RELEASING.md](RELEASING.md) alongside this README that documents how to:

1. Split / package the `packages/assessments` subtree for distribution.
2. Tag semantic versions and update `CHANGELOG.md`.
3. Trigger Packagist to pull the new tag.

Following those steps ensures consumers installing via Composer always receive a tested, tagged build with up-to-date documentation.

## Roadmap

The current sprint plan and backlog are tracked in:

- `co-pilot_assistance/assesment_package/phase1.md`
- `co-pilot_assistance/assesment_package/pending.md`
- `co-pilot_assistance/assesment_package/done.md`

Refer there for detailed migration status and upcoming tasks (Testbench coverage, request/resource abstractions, dynamic workflow integration, etc.).
