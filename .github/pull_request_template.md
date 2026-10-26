## What does this PR do?

<!-- A short description. Link the issue it fixes, e.g. "Fixes #12". -->

## How was it tested?

<!-- Commands you ran, PHP versions, and screenshots for UI changes (desktop + mobile). -->

## Checklist

- [ ] `php -l` passes on the files I changed
- [ ] `php backend/tests/run.php` passes
- [ ] New rules or bug fixes come with a test in `backend/tests/run.php`
- [ ] The scanner stays read-only and no malware samples are committed
- [ ] Output is escaped, queries are prepared, and state-changing requests have CSRF protection
- [ ] No inline `<script>` or `on…=` handlers (CSP)
- [ ] Works on PHP 8.1
- [ ] Docs are updated if behaviour or configuration changed
