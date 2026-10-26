# V7-Shield - Admin dashboard (frontend)

Core PHP dashboard for the V7-Shield scanner. It renders real, persisted scan data from the backend library (`../backend`, included server-side). There's no framework and no build step.

## MVP features

| Page | File | What it does |
|---|---|---|
| Dashboard | `dashboard.php` | Latest completed scan (counts, severity chart, categories), active/failed scans, recent scans. Shows empty states and setup hints when there's no data. |
| Start Malware Scan | `pages/scans.php` | Pick a **configured target id** and start a scan. Shows the limits and exclusions in effect. |
| Scan details | `pages/scan.php?id=N` | Live status (queued/running/completed/failed), real counts polled from the API, severity breakdown, skipped files. |
| Malware Findings | `pages/findings.php` | Findings for a scan, with severity/category/search filters, server-side paging and a detail drawer (evidence, SHA-256, false-positive notes). |
| Scan History | `pages/scan-history.php` | All scans, filterable by status. |
| Settings | `pages/settings.php` | Theme and sidebar (stored in the browser), system status, targets (labels only, never paths), limits, rule list. Scanner settings are read-only here. |

Also: `login.php`, `logout.php` (POST + CSRF), and `api.php` (JSON endpoints; see `backend/README.md`).

The sidebar lists planned features under **Coming soon** without links. Their earlier mock-ups are kept, unserved, in `../design/future-pages/`.

## Requirements and running

See the root [README](../README.md) for database and config setup. Then:

```bash
php start.php      # or: PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8000 -t frontend backend/dev-router.php
```

`PHP_CLI_SERVER_WORKERS` matters in inline execution mode: the request that runs the scan holds one worker while the page polls status on another.

For Apache or Nginx, make `frontend/` the document root and keep `backend/` outside it. If the backend lives elsewhere, set `backend_path` in `config/app.php`.

## Structure

```
frontend/
├── index.php, dashboard.php, login.php, logout.php, api.php
├── pages/            scans, scan, findings, scan-history, settings
├── includes/         bootstrap (auth gate), helpers, header/sidebar/topbar/footer
├── config/app.php    navigation = page allowlist, backend_path
└── assets/           css (tokens, components, responsive), js (app, charts, scans), logo
```

## Conventions

- Every page includes `includes/bootstrap.php`. It redirects to login (or returns 401 for the API) unless a session exists.
- Pages render only if their key is in `config/app.php` (`nav` or `hidden`). No user-supplied path is ever included.
- Dynamic output goes through `e()`. Forms carry `csrf_field()`, and API POSTs send `X-CSRF-Token`.
- Backend calls go through `try_backend()`, which turns failures into a safe message (for example "Database is unavailable") instead of a stack trace.
- Timestamps are stored in UTC and shown in the viewer's local time.

## Limitations

- One administrator role. There's no user management UI; users are added in the backend config.
- No quarantine, file integrity baselines, scheduling, CMS-specific checks, reports or notifications yet.
- Chart.js 4.4.1 and Bootstrap Icons 1.11.3 are self-hosted in `assets/vendor/` (MIT licences included). Only the Inter / JetBrains Mono fonts come from Google Fonts; without internet the UI falls back to system fonts.
