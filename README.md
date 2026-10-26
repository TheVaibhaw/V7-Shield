# V7-Shield

**Scan. Detect. Protect.** — an open-source, **read-only** malware heuristics scanner for PHP websites (WordPress, Joomla, custom PHP), with a Core PHP admin dashboard.

> V7-Shield reports **suspicious indicators**, not confirmed malware. Its rule set is small and will not detect every threat. Treat findings as leads for a human investigation.

## Repository layout

| Path | Purpose |
|---|---|
| `frontend/` | PHP admin dashboard (public web root). Login, start scan, findings, history, settings. |
| `start.php` | One-command local setup + run (`php start.php`). |
| `backend/` | Scanner engine, rule set, MySQL persistence, auth/CSRF, CLI tools, tests. **Must not be web-accessible.** |
| `design/future-pages/` | UI mock-ups of planned features (not served, demo data only). |

Core PHP **8.1 – 8.5**, HTML, CSS and vanilla JavaScript only. No framework, no Composer, no build step.

## Run locally with one command

Requirements: PHP **8.1 or newer** (tested on 8.1, 8.3 and 8.5) with the `pdo_mysql` extension, and a running MySQL 8 / MariaDB 10.6+ server. `mbstring` is optional; a built-in fallback is used without it.

```bash
php start.php
```

Then open **http://127.0.0.1:8000/** and sign in as `admin` / `Admin@12345` (see [Default login](#default-login)).

The command:
1. Checks the PHP version and extensions.
2. Creates `backend/config/config.local.php` on first run, asking for the database host, name, user and password and (optionally) a directory to scan. It writes the file with `chmod 600`. If the file still has `CHANGE_ME` placeholders, it fills them in after saving a `.bak` copy.
3. Connects to the database. If the database or user doesn't exist yet, it offers to create them with a MySQL admin account (e.g. `root`). It grants only `SELECT, INSERT, UPDATE, DELETE`, and **the admin password is used once and never saved**.
4. Installs the schema only when tables are missing. Re-running never resets data or the admin password.
5. Starts the dashboard on `127.0.0.1:8000` with 4 PHP workers. Press `Ctrl+C` to stop.

Options:

| Command | Purpose |
|---|---|
| `php start.php --port=8080` | Use another port (it refuses to start if the port is taken) |
| `php start.php --setup-only` | Prepare config and database without starting the server |
| `php start.php --host=0.0.0.0` | Listen on all interfaces. Avoid this on shared networks. |

After the first run, `php start.php` just checks everything and starts the server without asking questions. To scan something, add a directory under `targets` in `backend/config/config.local.php`.

> `start.php` is for **local development**. On a real server, use Apache/Nginx with `frontend/` as the document root (see [backend/README.md](backend/README.md#security-notes)).

## Manual setup (alternative)

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
PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8000 -t frontend backend/dev-router.php
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

**Upgrading an existing install:** run `php start.php --setup-only` (or `mysql -u root -p v7shield < backend/database/schema.sql`). Either one creates any newly added tables, such as `login_attempts` for login throttling, and leaves your data alone. Until the table exists, throttling falls back to a per-session counter.
- Change the default password before exposing the dashboard on any network.

**Forgot the password?** Reset it to the default (and force a change at next login):

```bash
HASH=$(php -r 'echo password_hash("Admin@12345", PASSWORD_DEFAULT);')
mysql -u root -p v7shield -e "UPDATE users SET password_hash='$HASH', must_change_password=1 WHERE username='admin';"
```

**More users:** insert another row the same way, or add `username => hash` to `users` in `config.local.php` (create the hash with `php backend/bin/hash-password.php`). Config users take precedence and are changed in the file, not in the UI.

See [backend/README.md](backend/README.md) for configuration, the scanning model, security notes, limitations and tests, and [frontend/README.md](frontend/README.md) for the dashboard.
