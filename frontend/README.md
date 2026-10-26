# V7-Shield — Admin Panel (frontend)

**Scan. Detect. Protect.** — the Core PHP admin console for V7-Shield, an open-source security scanner for WordPress, Joomla, custom PHP applications, website files and supported databases.

> **Demo Mode.** This phase is frontend only. All statistics, findings and scans are sample data from `data/demo-data.php`. No file is read, scanned, quarantined or deleted.

## Requirements
- PHP **8.2+** (no extensions beyond the defaults)
- A browser with internet access for the CDN assets (Bootstrap Icons, Chart.js, Inter font)
- No Node.js, Composer or build step

## Run locally
From the repository root:

```bash
php -S localhost:8000 -t frontend
```

Then open <http://localhost:8000/>. It redirects to `dashboard.php`.

**Apache:** point a virtual host or sub-folder at `frontend/`. URLs are detected from the script path, so sub-folders such as `/V7-Shield/frontend/` work too. `.htaccess` disables directory listing and blocks `config/`, `data/` and `includes/`. (Those files also exit when they are requested directly.)

## Structure
```
frontend/
├── index.php              → redirects to dashboard.php
├── dashboard.php          → main overview
├── pages/                 → one file per navigation item (23 pages)
├── includes/
│   ├── bootstrap.php      → config + helpers + demo data, security headers
│   ├── helpers.php        → e(), layout, badges, cards, data_table(), column sets
│   ├── header.php sidebar.php topbar.php breadcrumbs.php footer.php
├── config/app.php         → app info + navigation / page allowlist
├── data/demo-data.php     → ALL sample data (single source)
└── assets/
    ├── css/style.css      → design tokens (dark/light), shell, components
    ├── css/dashboard.css  → dashboard and page components
    ├── css/responsive.css → breakpoints 1440/1200/1024/768/480/360
    ├── js/app.js          → sidebar/drawer, theme, dropdowns, tables, drawer, modals, toasts, validation, export
    ├── js/charts.js       → Chart.js helpers (re-theme on toggle)
    ├── js/dashboard.js    → threat activity chart and period filter
    ├── js/scans.js        → scan wizard summary + UI-only progress simulation
    └── images/v7-shield-logo.svg
```

## How the pages work
- Each page calls `render_header('<key>')` and `render_footer()`. The key must exist in `config/app.php` `nav`, which also drives the sidebar and breadcrumbs. No user-supplied path is ever included.
- Tables use `data_table()`. `app.js` adds search, filters, sorting and pagination, and the `?severity=critical`-style query parameters preset the filters.
- Buttons that need a backend show a "Demo Mode" toast. Destructive actions use a confirmation dialog and only change the current view.
- Exports (CSV/JSON) are built in the browser from the visible sample rows and labelled as demo.

## Current limitations
- No authentication, persistence or real scanning.
- Settings are not saved. Only the theme, sidebar state, nav groups and project selector are kept in `localStorage`.
- PDF reports use the browser's print function.
- CDN assets are needed. For offline use, copy them into `assets/vendor/` and update `includes/header.php` / `footer.php`.

## Backend integration plan
The planned `backend/` (not present yet) should expose a JSON API (`config/app.php` → `api_base`). The proposed endpoints are:

| Frontend data (`data/demo-data.php`) | Proposed endpoint |
|---|---|
| `score`, `metrics` | `GET /api/dashboard/summary` |
| `series` | `GET /api/findings/timeseries?range=7d` |
| `scans` | `GET /api/scans`, `POST /api/scans`, `POST /api/scans/{id}/cancel`, `GET /api/scans/{id}/progress` |
| `findings` | `GET /api/findings`, `PATCH /api/findings/{id}` (review state) |
| `integrity` | `GET /api/integrity`, `POST /api/integrity/baseline` |
| `quarantine` | `GET /api/quarantine`, `POST /api/quarantine/{id}/restore` (audited) |
| `events`, activity logs | `GET /api/events`, `GET /api/audit` |
| `projects` | CRUD `/api/projects` |
| `notifications` | `GET /api/notifications`, `POST /api/notifications/read` |

Swap each demo array for an API client call inside `data/`, keeping the same array shapes so the templates stay unchanged. Add CSRF tokens and session authentication when the forms start posting. Database credentials and secrets must stay in backend configuration only.

## Contributing
- Keep the code framework-free (Core PHP and vanilla JS).
- Escape output with `e()`.
- Register new pages in `config/app.php`.
- Run `find frontend -name '*.php' -exec php -l {} \;` before committing.
