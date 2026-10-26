# V7-Shield backend

Read-only malware heuristics engine plus job persistence for the dashboard. The library is included by the frontend from **outside the web root** and by CLI scripts.

```
backend/
├── bootstrap.php            autoloader + V7Shield\config()
├── config/
│   ├── config.example.php   defaults (committed, no secrets)
│   ├── config.local.php     your settings + secrets (git-ignored, create it)
│   └── rules.php            detection rules (editable)
├── database/schema.sql      MySQL/MariaDB schema
├── src/
│   ├── Scanner/             FileWalker, SignatureDetector, RuleEngine, Scanner
│   ├── Repository/          ScanRepository (PDO, prepared statements)
│   ├── Service/             ScanService (validation, rate limits, jobs), DatabaseSink
│   └── Security/            Auth (sessions), Csrf
├── bin/
│   ├── scan.php             one-off scan → JSON (no database)
│   ├── worker.php           processes queued jobs (worker mode)
│   └── hash-password.php    create password hashes for users
└── tests/
    ├── run.php              scanner, path-safety and database tests
    └── http-smoke.sh        end-to-end HTTP checks against a running instance
```

## Prerequisites

- PHP **8.2+** with `pdo_mysql` and `mbstring`
- MySQL 8 or MariaDB 10.6+
- The web server user must be able to **read** the target directories. It never needs write access to them.

## Configuration (`config/config.local.php`)

Only keys you set override the defaults in `config.example.php`. You can also point the `V7SHIELD_CONFIG` environment variable at a config file stored elsewhere (for example `/etc/v7shield/config.php`).

| Key | Meaning |
|---|---|
| `database.dsn/username/password` | PDO connection. Use a user limited to `SELECT, INSERT, UPDATE, DELETE` on the V7-Shield database. |
| `users` | `username => password_hash`. Generate hashes with `php backend/bin/hash-password.php`. |
| `targets` | `id => ['label' => ..., 'path' => '/abs/dir']`. **These are the only directories that can be scanned.** The browser only ever submits the id. |
| `scanner.max_file_size` / `max_depth` / `max_files` / `max_duration` | Hard limits per scan. Files over the limit are skipped and reported. Hitting the file or time limit ends the scan as "completed (limit reached)". |
| `scanner.exclusions` | `fnmatch` globs. A pattern without `/` matches the file name at any depth, and a trailing `/` matches directories only (`node_modules/`, `*.log`, `wp-content/cache/`). |
| `jobs.execution` | `inline` (default): the browser request that created the scan runs it, bounded by `max_duration`. `worker`: jobs wait for `bin/worker.php`. |
| `jobs.max_starts_per_hour` | Per-user rate limit. Only one queued or running scan is allowed per target. |
| `jobs.stale_after` | Running jobs with no progress heartbeat for this many seconds are marked failed. |

### Worker mode

Set `'jobs' => ['execution' => 'worker']`, then run the worker from cron or a service manager:

```bash
* * * * * www-data php /srv/v7shield/backend/bin/worker.php >> /var/log/v7shield-worker.log 2>&1
# or, long-running:  php backend/bin/worker.php --loop
```

Use worker mode on shared hosting with short request time limits, or for large sites. No other background service is needed.

## How scanning works

1. **Walk.** The walker iterates the target root without recursion and lists hidden files too.
   - It **never follows symbolic links**: symlinked files and directories are skipped and reported.
   - Every directory's `realpath` must stay inside the root.
   - Exclusions and the depth limit are applied, and devices, sockets and FIFOs are skipped.
2. **Read.** Each regular file at or under the size limit is read **as data**.
   - Empty, unreadable and oversized files are recorded as skipped, with a reason.
   - Nothing is executed, included, modified, moved or deleted.
3. **Built-in checks.**
   - `V7-SIG-001`: PHP open tag inside a non-PHP file (such as an image).
   - `V7-SIG-002`: extension and magic bytes don't match.
   - `V7-SIG-003`: ELF, PE or Mach-O executable.
   - `V7-FS-001`: alternative PHP extensions (`.phtml`, `.phar`, …).
4. **Rules.** The regex rules in `config/rules.php` run on PHP-family files and on any file containing a PHP tag.
   - Each rule has an id, category, severity, confidence and a list of known false positives.
   - If a rule hits PCRE's backtrack limit, the file is reported with reason `rule_error` instead of silently passing.
5. **Record.** For each finding the backend stores:
   - the rule id, severity, confidence and classification (always `suspicious`)
   - the file path relative to the target root, its size and SHA-256
   - the line number and match count
   - a short **truncated** evidence excerpt (about 160 characters, with long encoded runs shortened), so payloads and source are not copied into the database

### Rule set and known limitations

The 15 regex rules and 4 built-in checks cover:
- request input reaching `eval`, shell functions, `include` or file writes
- decode-and-evaluate chains and long encoded or escaped strings
- `chr()` assembly and `preg_replace /e`
- known webshell marker strings
- file-signature anomalies

The engine **will miss**:
- malware that doesn't match these patterns, or that is split or encrypted (including heavily obfuscated variants and ionCube/Zend-encoded files)
- malicious JavaScript, and malicious content stored in databases
- server-level compromises (cron jobs, `.htaccess` rewrites, binaries outside the target)
- anything excluded or over the limits

Common **false positives**:
- licence loaders and caching plugins (encoded or compressed data)
- embedded images and fonts (long base64)
- template engines that use `.phtml`
- security tools or docs that mention webshell names
- admin tools that pass sanitised input to CLI programs

Each rule lists its own false positives, and the Settings page shows them.

Add or tune rules in `config/rules.php`. Patterns use the `~` delimiter and are validated when the engine starts. Bump `version` when you change them, because each scan stores the rule version it used.

## Database

`database/schema.sql` creates three InnoDB tables:
- `scan_jobs`: status, timestamps, heartbeat, counts per severity, limit and error info, rule version, and the scope JSON (limits and exclusions, no paths)
- `scan_findings`: indexed by scan + severity, rule and SHA-256
- `scan_skips`: up to `max_skips_stored` entries per scan; the counts on the job are always exact

Deleting a job cascades to its findings and skips. All queries use PDO prepared statements, and finding and skip batches are inserted in transactions. Job claiming is atomic (`UPDATE … WHERE status='queued'`), and scan starts are serialised per target with `GET_LOCK`.

## API (served by `frontend/api.php`)

All actions need a signed-in session. POST actions also need the `X-CSRF-Token` header (the token is in `<body data-csrf>`).

| Method | Action | Parameters |
|---|---|---|
| GET | `summary` | |
| GET | `status` | `id` |
| GET | `findings` | `scan_id`, `page`, `severity`, `category`, `q` |
| GET | `history` | `page`, `status` |
| POST | `start` | `target` (configured id) → `201 {id}` |
| POST | `run` | `id`. Inline mode only, and only by the user who started the scan. Repeated calls are a no-op. |

Errors are returned as `{"error": "..."}` with a 4xx/5xx status. Internal details go to the PHP error log, never to the response. Responses contain no absolute paths, IP addresses or credentials.

## CLI

```bash
php backend/bin/scan.php --target=my-site        # JSON to stdout; exit 3 if findings
php backend/bin/scan.php --dir=/some/path        # operator-only; the web API never accepts paths
php backend/bin/worker.php [--loop]
```

## Security notes

- Deploy so that **only `frontend/` is the document root**. `backend/` and `config.local.php` must not be reachable over HTTP. (`.htaccess` deny files are included as a fallback for Apache.)
- Keep `config.local.php` out of git (it's in `.gitignore`) and readable only by the PHP user (`chmod 600`).
- Authentication uses sessions:
  - cookies are HttpOnly and SameSite=Strict, plus Secure over HTTPS
  - the session ID is regenerated on login, and sessions expire after a period of inactivity
  - logins are locked out per session after repeated failures
  - all users are administrators; there are no roles yet
- Serve the dashboard over **HTTPS** in any non-local environment, and consider adding IP allow-listing or HTTP auth in front of it.
- Run the PHP process as a user with **read-only** access to the targets.

## Tests

```bash
php backend/tests/run.php                       # scanner + path-safety tests (no DB)

# include database tests (use a dedicated DB whose name contains "test"; tables are emptied)
mysql -u root -p -e "CREATE DATABASE v7shield_test" && mysql -u root -p v7shield_test < backend/database/schema.sql
V7SHIELD_TEST_DSN='mysql:host=127.0.0.1;dbname=v7shield_test;charset=utf8mb4' \
V7SHIELD_TEST_USER=... V7SHIELD_TEST_PASS=... php backend/tests/run.php

# end-to-end against a running dashboard (starts real scans on the target)
BASE=http://localhost:8000 V7_USER=admin V7_PASS=... V7_TARGET=my-site backend/tests/http-smoke.sh
```

The fixtures are generated in a temp directory at runtime, so no malware-like samples are committed.
