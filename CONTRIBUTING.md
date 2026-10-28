# Contributing to V7-Shield

Thanks for helping! Bug fixes, new detection rules, false-positive fixes, docs, UI polish and translations are all welcome, and first-time contributors are encouraged.

## Ways to contribute

| You want to… | Start here |
|---|---|
| Report a bug | [Bug report](https://github.com/TheVaibhaw/V7-Shield/issues/new?template=bug_report.yml) |
| Suggest a detection rule | [Detection rule](https://github.com/TheVaibhaw/V7-Shield/issues/new?template=detection_rule.yml) |
| Report a false positive | [False positive](https://github.com/TheVaibhaw/V7-Shield/issues/new?template=false_positive.yml) |
| Propose a feature | [Feature request](https://github.com/TheVaibhaw/V7-Shield/issues/new?template=feature_request.yml) |
| Find something to work on | Issues labelled [`good first issue`](https://github.com/TheVaibhaw/V7-Shield/labels/good%20first%20issue) or [`help wanted`](https://github.com/TheVaibhaw/V7-Shield/labels/help%20wanted) |
| Report a vulnerability | **Privately**, as described in [SECURITY.md](SECURITY.md). Never in a public issue. |

## Development setup

Requirements: PHP 8.1+ with `pdo_mysql`, and MySQL 8 or MariaDB 10.6+.

```bash
git clone https://github.com/<your-username>/V7-Shield.git
cd V7-Shield
php start.php          # interactive first-run setup, then serves http://127.0.0.1:8000
```

`start.php` creates `backend/config/config.local.php`, which is git-ignored. **Never commit it.**

## Project rules

- **Core PHP only.** No frameworks, Composer packages or JS build steps. Vanilla JS and CSS.
- **PHP 8.1 compatible.** Avoid features newer than 8.1, such as readonly classes, typed class constants and `json_validate`. CI runs PHP 8.1 through 8.4.
- **The scanner stays read-only.** Never execute, include, modify, move or delete scanned files.
- **Escape all output** with `e()` in templates. Use prepared statements for every query, and add CSRF protection to every state-changing request.
- **No inline `<script>` or `on…=` handlers.** The Content-Security-Policy blocks them; put JS in `frontend/assets/js/`.
- **Never commit malware samples.** Build fixtures from string fragments at runtime, as `backend/tests/run.php` does.
- New pages must be registered in `frontend/config/app.php` (the page allowlist).

## Adding a detection rule

Rules live in `backend/config/rules.php`. Each one needs:

```php
[
    'id' => 'V7-EXEC-007',          // unique: V7-<CATEGORY>-<NNN>
    'name' => 'Short title',
    'category' => 'execution',      // execution | obfuscation | webshell | remote-code | file-signature
    'severity' => 'high',           // critical | high | medium | low
    'confidence' => 70,             // 0-100: how often a match is truly malicious
    'pattern' => '~...~i',          // PCRE, "~" delimiter, bounded quantifiers ({0,120}, not .*)
    'extensions' => ['js'],         // optional: run only on these file types (default: PHP files and files with a PHP tag)
    'description' => 'Why this matters.',
    'false_positives' => 'Known legitimate code that also matches.',
],
```

Checklist:
1. Use **bounded** quantifiers so the regex can't backtrack catastrophically on large files.
2. Add a positive fixture **and** a clean look-alike to `backend/tests/run.php`. The look-alike proves the rule doesn't fire on normal code.
3. Bump `version` at the top of `rules.php`.
4. Be honest in `false_positives`. Reviewers rely on it.

## Before you open a pull request

```bash
# syntax check
find frontend backend start.php -name '*.php' -not -path '*/vendor/*' -exec php -l {} \; | grep -v "No syntax errors"

# unit tests (no database needed)
php backend/tests/run.php

# optional: include database tests (DB name must contain "test")
V7SHIELD_TEST_DSN='mysql:host=127.0.0.1;dbname=v7shield_test;charset=utf8mb4' \
V7SHIELD_TEST_USER=... V7SHIELD_TEST_PASS=... php backend/tests/run.php

# optional: end-to-end, against a running `php start.php`
BASE=http://127.0.0.1:8000 V7_USER=... V7_PASS=... V7_TARGET=... backend/tests/http-smoke.sh
```

CI runs all of these on every pull request.

## Pull request guidelines

- Keep each PR focused on one fix or feature. Small PRs get reviewed faster.
- Branch from `main`, for example `fix/pager-clamp` or `rule/v7-exec-007`.
- Write a clear description: what changed, why, and how you tested it. The PR template will guide you.
- Update the docs (`README.md`, `backend/README.md`) when you change behaviour or configuration.
- For UI changes, include before/after screenshots at desktop and mobile widths.

Maintainers aim to respond to new PRs and issues within a few days.

## Commit messages

Use the imperative mood with a short summary line, for example:

```
Add V7-EXEC-007 rule for assert() with request input
Fix pager showing empty state on out-of-range page
```

## License

By contributing, you agree that your contributions are licensed under the [MIT License](LICENSE).
