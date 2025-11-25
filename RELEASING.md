# Releasing `Amryami/assessments`

This package lives inside the main HR monorepo (`packages/assessments`). To publish a new version to Composer/Packagist follow the checklist below.

## Prerequisites

- You have maintainer access to the GitHub repository and the Packagist entry (configured with `packages/assessments/composer.json` as the package path).
- Local PHP 8.2+ with Composer installed.

## Release Steps

1. **Sync with the default branch**
   ```bash
   git checkout package_assessment
   git pull origin package_assessment
   git checkout main
   git pull origin main
   git checkout package_assessment
   git rebase main
   ```

2. **Update metadata**
   - Ensure `CHANGELOG.md` has an entry for the version you’re about to tag (e.g. `## [1.1.0] - YYYY-MM-DD`).
   - Update documentation if necessary (README, upgrade guide). No `version` field needs to be set in `composer.json`; tags drive the release.

3. **Run the package test suite**
   ```bash
   composer test --working-dir=packages/assessments
   ```
   (Or run `vendor/bin/pest` if you prefer Pest.)

4. **Commit the release changes**
   ```bash
   git add packages/assessments
   git commit -m "chore(assessments): prepare vX.Y.Z"
   ```

5. **Tag the version**
   ```bash
   git tag assessments-vX.Y.Z
   ```
   Use semantic versioning (`v1.0.0`, `v1.1.0`, etc.). Annotated tags (`-a`) are preferred.

6. **Push branch + tag**
   ```bash
   git push origin package_assessment
   git push origin assessments-vX.Y.Z
   ```

7. **Trigger Packagist**
   - If Packagist is configured with the GitHub webhook, the new tag will be pulled automatically.
   - Otherwise visit the package page on packagist.org and hit **Update**.

8. **Smoke test the published package**
   ```bash
   laravel new sandbox
   cd sandbox
   composer require Amryami/assessments:^X.Y
   php artisan vendor:publish --tag=assessments-config
   php artisan migrate
   ```

9. **Document any host-app steps**
   - Update the host repository’s `composer.json` constraint (`Amryami/assessments": "^X.Y"`).
   - Run `composer update Amryami/assessments` inside the host app.
   - Note any breaking changes in internal release notes / upgrade guide.

That’s it—each new git tag under `packages/assessments` now maps to an installable Composer release.
