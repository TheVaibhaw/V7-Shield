# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.2.0] - 2026-10-26

First public MVP.

### Added
- Read-only scanner engine with 15 regex rules and 4 built-in checks (webshell markers, request-to-sink patterns, obfuscation, PHP inside non-PHP files, extension/content mismatch, executables).
- Safe file walker: never follows symlinks, verifies inodes and real paths, enforces size/depth/file-count/time limits, and reports every skipped file.
- MySQL persistence for scan jobs, findings, skipped files, users and login attempts.
- Dashboard with Start Malware Scan, Malware Findings, Scan History, Scan details and Settings pages.
- Session authentication, a default `admin` account that must change its password (localhost-only until changed), password change, and server-side brute-force throttling.
- Same-origin JSON API with CSRF protection, rate limits and one active scan per target.
- Inline and CLI-worker execution modes.
- `php start.php` one-command local setup and run, with a safe router for PHP's built-in server.
- Strict Content-Security-Policy; Chart.js and Bootstrap Icons are self-hosted.
- Compatible with PHP 8.1 – 8.5; `mbstring` is optional.
- Unit, database and end-to-end HTTP test suites, plus GitHub Actions CI.

[Unreleased]: https://github.com/TheVaibhaw/V7-Shield/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/TheVaibhaw/V7-Shield/releases/tag/v0.2.0
