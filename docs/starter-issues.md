# Starter issues

Ready-to-file issues for new contributors. Maintainers can copy each one into GitHub (**New issue → Open a blank issue**, or use `gh issue create`) and apply the labels shown.

Want to work on one that isn't filed yet? Open it yourself and say you're taking it.

---

## 1. Add rule: `call_user_func` / `array_map` with request input
**Labels:** `good first issue`, `detection-rule`

Backdoors often call a function chosen by the request, e.g. `call_user_func($_GET['f'], $_GET['a'])` or `array_map($_POST['f'], [...])`. V7-EXEC-003 only catches `$_GET['f'](...)`.

- Add `V7-EXEC-007` to `backend/config/rules.php`, covering `call_user_func(_array)?`, `array_map`, `array_filter` and `usort` whose first argument comes from a superglobal.
- Add a positive fixture and a clean look-alike (e.g. `call_user_func([$this, 'run'])`) to `backend/tests/run.php`.

## 2. Add rule: `extract()` on request data
**Labels:** `good first issue`, `detection-rule`

`extract($_REQUEST)` lets an attacker overwrite any variable. Add `V7-EXEC-008` (medium severity), with tests.

## 3. Add rule: JavaScript skimmer pattern (`atob` + `eval`)
**Labels:** `help wanted`, `detection-rule`

Card skimmers injected into `.js` files often look like `eval(atob('...'))` or `new Function(atob(...))`.

- Rules currently only run on PHP files and on files that contain a PHP tag. This needs a way for a rule to declare its own extensions, e.g. `'extensions' => ['js']`.
- Implement that in `Scanner::scanFile` / `RuleEngine`, then add the rule and tests.

## 4. Clean look-alike tests for every existing rule
**Labels:** `good first issue`, `tests`

For each rule in `rules.php`, add one realistic **legitimate** snippet that must **not** match. For example, a normal `include __DIR__ . '/x.php';` for V7-RCE-001, or a short base64 image for V7-OBF-003. This guards against false-positive regressions.

## 5. Show local time in the findings "Scan" selector
**Labels:** `good first issue`, `ui`

`frontend/pages/findings.php` prints `finished_at` as raw UTC in the `<option>` text. Everywhere else, timestamps are converted to the viewer's local time by `app.js` (`time[data-utc]`).

Make the selector consistent: either format the time server-side with a clear "UTC" label, or update the option text in `app.js`.

## 6. Export findings as CSV
**Labels:** `good first issue`, `enhancement`

Add a "Download CSV" button to the findings page that exports the current filtered findings: rule, severity, confidence, file, line, SHA-256 and evidence.

- Implement it as a GET API action (`action=findings_csv`) that requires a session.
- Neutralise spreadsheet formula injection: prefix cells that start with `=`, `+`, `-` or `@` with `'`.

## 7. Filter skipped files by reason on the scan details page
**Labels:** `good first issue`, `ui`

`pages/scan.php` lists skipped files and shows the reason counts as tags. Make each tag a link that filters the table to that reason (e.g. `?id=5&reason=oversized`). Validate `reason` against the known list.

## 8. Human-readable output for the CLI scanner
**Labels:** `good first issue`, `cli`

`php backend/bin/scan.php` prints JSON only. Add `--format=table`, which prints a compact table (severity, rule, file:line) and a summary line. Keep JSON as the default.

## 9. systemd unit and cron examples for worker mode
**Labels:** `good first issue`, `documentation`

Add `docs/deploy-worker.md` with a tested `v7shield-worker.service` (running `php backend/bin/worker.php --loop` as a dedicated read-only user) and the cron alternative.

## 10. Docker Compose for local development
**Labels:** `help wanted`, `enhancement`

Add `docker-compose.yml` with PHP 8.3 and MySQL 8 services that:
- runs `php start.php` non-interactively, using environment variables for the database
- mounts a sample target directory read-only

Document it in the README.

## 11. Hindi translation of the README
**Labels:** `good first issue`, `documentation`, `translation`

Add `docs/README.hi.md` and link it from the top of `README.md`.

## 12. Light theme polish
**Labels:** `good first issue`, `ui`

Switch to the light theme (topbar moon/sun button) and check every page at 375px and 1440px. Fix contrast issues: muted text, badges, chart legend. Attach before/after screenshots.

---

## Labels to create

| Label | Colour | Description |
|---|---|---|
| `good first issue` | `#7057ff` | Good for newcomers |
| `help wanted` | `#008672` | Extra attention is needed |
| `detection-rule` | `#d93f0b` | New or improved scanner rule |
| `false-positive` | `#fbca04` | A rule flags legitimate code |
| `ui` | `#1d76db` | Dashboard / frontend |
| `cli` | `#5319e7` | Command-line tools |
| `tests` | `#0e8a16` | Test coverage |
| `documentation` | `#0075ca` | Docs |
| `translation` | `#c5def5` | Translations |
| `triage` | `#ededed` | Needs a maintainer's first look |
