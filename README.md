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
mysql -u root -p v7shield < backend/database/schema.sql   # tables + default admin user

# 2. Configuration (git-ignored)
cp backend/config/config.example.php backend/config/config.local.php
chmod 600 backend/config/config.local.php
# edit database credentials and 'targets' (the directories that may be scanned)

# 3. Run (multiple workers so status polling works while a scan runs)
PHP_CLI_SERVER_WORKERS=4 php -S localhost:8000 -t frontend
# open http://localhost:8000/ and sign in
```

## Default login

The schema creates one administrator account:

| Username | Password |
|---|---|
| `admin` | `Admin@12345` |

- At first sign-in you're **forced to change this password**. Until you do, every page redirects to *Change password* and the API returns `403`. The new password must be at least 12 characters.
- You can change the password again any time from the account menu → **Change password**.
- Re-running `schema.sql` is safe: it never resets an existing `admin` (`INSERT IGNORE`).
- Change the default password before exposing the dashboard on any network.

**Forgot the password?** Reset it to the default (and force a change at next login):

```bash
HASH=$(php -r 'echo password_hash("Admin@12345", PASSWORD_DEFAULT);')
mysql -u root -p v7shield -e "UPDATE users SET password_hash='$HASH', must_change_password=1 WHERE username='admin';"
```

**More users:** insert another row the same way, or add `username => hash` to `users` in `config.local.php` (create the hash with `php backend/bin/hash-password.php`). Config users take precedence and are changed in the file, not in the UI.

See [backend/README.md](backend/README.md) for configuration, the scanning model, security notes, limitations and tests, and [frontend/README.md](frontend/README.md) for the dashboard.
