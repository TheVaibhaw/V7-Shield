# V7-Shield

**Scan. Detect. Protect.** — an open-source, **read-only** malware heuristics scanner for PHP websites (WordPress, Joomla, custom PHP), with a Core PHP admin dashboard.

> V7-Shield reports **suspicious indicators**, not confirmed malware. Its rule set is small and will not detect every threat. Treat findings as leads for a human investigation.

## Repository layout

| Path | Purpose |
|---|---|
| `frontend/` | PHP admin dashboard (public web root). Login, start scan, findings, history, settings. |
| `backend/` | Scanner engine, rule set, MySQL persistence, auth/CSRF, CLI tools, tests. **Must not be web-accessible.** |
| `design/future-pages/` | UI mock-ups of planned features (not served, demo data only). |

Core PHP 8.2+, HTML, CSS and vanilla JavaScript only. No framework, no Composer, no build step.

## Quick start (local)

```bash
# 1. Database (MySQL 8 / MariaDB 10.6+)
mysql -u root -p -e "CREATE DATABASE v7shield CHARACTER SET utf8mb4;
  CREATE USER 'v7shield_app'@'localhost' IDENTIFIED BY 'choose-a-strong-password';
  GRANT SELECT, INSERT, UPDATE, DELETE ON v7shield.* TO 'v7shield_app'@'localhost';"
mysql -u root -p v7shield < backend/database/schema.sql

# 2. Configuration (git-ignored)
cp backend/config/config.example.php backend/config/config.local.php
chmod 600 backend/config/config.local.php
php backend/bin/hash-password.php          # paste the hash into 'users' => ['admin' => '...']
# edit database credentials and 'targets' (the directories that may be scanned)

# 3. Run (multiple workers so status polling works while a scan runs)
PHP_CLI_SERVER_WORKERS=4 php -S localhost:8000 -t frontend
# open http://localhost:8000/ and sign in
```

See [backend/README.md](backend/README.md) for configuration, the scanning model, security notes, limitations and tests, and [frontend/README.md](frontend/README.md) for the dashboard.
