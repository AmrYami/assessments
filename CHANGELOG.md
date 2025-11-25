# Changelog

All notable changes to `Yami/assessments` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2025-02-28
### Added
- Candidate-facing web views and APIs (exam listing, preview, results) controlled via `ASSESSMENTS_ADMIN_ONLY`.
- Full attempt lifecycle services (start, autosave, submit, review) plus manual scoring workspace and reminder command.
- Reporting suite: UI coverage snapshot, CSV/JSON export, `assessments:reports` CLI, dashboard APIs, and 14-day attempt timelines backed by `ExamReportService`.
- Strict exposure enforcement, question explanations with optional exam toggle, and seeder data showcasing both.
- Documentation set: package README, Postman collection, upgrade guide, release checklist, and QA notes.
- GitHub Actions workflow `assessments-ci.yml` running the package PHPUnit suite.

### Changed
- Centralized schema migrations/seeders inside the package; host application now proxies controllers/views.
- Reporting controllers/tests refactored to leverage the shared service and new timeline data.
- Candidate result screen enhanced to display explanations when enabled.

### Fixed
- Guarded reporting routes and APIs share consistent permission checks; attempt exposure policy now surfaces clear validation messages.

### Removed
- Legacy inline reporting logic and per-controller aggregates in favour of `ExamReportService`.

## [Unreleased]
### Added
- Placeholder entry for future releases.

---

Guidelines:
- Each release section should include **Added**, **Changed**, **Fixed**, and **Removed** (omit if empty).
- Include migration/config instructions when necessary.
- Reference pull requests or issues using `[#123]` style if the repository uses GitHub issues.
