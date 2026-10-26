# Security Policy

V7-Shield is a security tool, so we take vulnerabilities in it seriously.

## Reporting a vulnerability

**Please do not open a public issue for security problems.**

Report privately through GitHub instead: open the repository's **Security** tab and choose **Report a vulnerability** (<https://github.com/TheVaibhaw/V7-Shield/security/advisories/new>).

Please include:
- the affected version or commit
- steps to reproduce, or a proof of concept
- the impact you observed (what an attacker gains)
- any suggested fix

What to expect:
- an acknowledgement within **3 days**
- an initial assessment within **7 days**
- a fix or mitigation plan for confirmed issues, coordinated with you before anything is disclosed
- credit in the release notes, if you want it

## Scope

In scope:
- authentication, session and CSRF bypasses
- XSS and SQL injection
- path traversal or symlink escapes that let a scan read outside its configured target
- information disclosure (absolute paths, secrets, other users' data)
- denial of service through crafted files
- anything in `frontend/`, `backend/` or `start.php`

Out of scope:
- **detection gaps**, i.e. malware that V7-Shield doesn't detect. Please open a normal issue with the *Detection rule* template instead.
- findings that need an already-compromised server or admin account
- missing hardening headers that have no demonstrated impact
- the UI mock-ups in `design/future-pages/`, which are never served

## Supported versions

Security fixes go to the latest release on `main`.

## Safe testing

- Test only against your own installation.
- Never use real malware samples against systems you don't own.
- Build test fixtures at runtime the way `backend/tests/run.php` does, rather than committing malicious files.
