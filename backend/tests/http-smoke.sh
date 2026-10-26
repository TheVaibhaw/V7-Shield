#!/usr/bin/env bash
# End-to-end HTTP checks against a running V7-Shield frontend.
#   BASE=http://127.0.0.1:8000 V7_USER=admin V7_PASS=... V7_TARGET=my-site backend/tests/http-smoke.sh
# Requires: curl, php (for JSON parsing). Starts real scans on V7_TARGET.
set -u
BASE=${BASE:?set BASE}; USER=${V7_USER:?}; PASS=${V7_PASS:?}; TARGET=${V7_TARGET:?}
JAR=$(mktemp); trap 'rm -f "$JAR"' EXIT
pass=0; fail=0
ok()   { echo "  PASS $1"; pass=$((pass+1)); }
bad()  { echo "  FAIL $1 — $2"; fail=$((fail+1)); }
expect() { [ "$2" = "$3" ] && ok "$1" || bad "$1" "expected $3, got $2"; }
code() { curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" "$@"; }
json() { php -r '$d=json_decode(stream_get_contents(STDIN),true); $k=explode(".",$argv[1]); foreach($k as $p){$d=is_array($d)?($d[$p]??null):null;} echo is_scalar($d)?$d:json_encode($d);' "$1"; }
csrf_from() { grep -o 'name="csrf" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//; s/"//'; }

echo "Router and headers (requires backend/dev-router.php, as used by start.php)"
for p in README.md .htaccess includes/helpers.php config/app.php "pages/../includes/helpers.php" "%2e%2e/backend/config/config.example.php"; do
  expect "internal file /$p is not served" "$(code "$BASE/$p")" 404
done
expect "static asset served" "$(code "$BASE/assets/css/style.css")" 200
expect "vendored icon font served" "$(code "$BASE/assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2")" 200
H=$(curl -s -D - -o /dev/null "$BASE/login.php")
echo "$H" | grep -qi "^content-security-policy: default-src 'self'" && ok "CSP header present" || bad "CSP header present" "missing"
echo "$H" | grep -qi "^x-frame-options: DENY" && ok "X-Frame-Options DENY" || bad "X-Frame-Options" "missing"
echo "$H" | grep -qi "httponly" && echo "$H" | grep -qi "samesite=strict" && ok "session cookie HttpOnly + SameSite=Strict" || bad "cookie flags" "-"

echo "Brute-force throttling (fresh session per attempt, so the lock is server-side)"
FAKE="nobody-$RANDOM$RANDOM"
for i in 1 2 3 4 5; do
  J2=$(mktemp); T2=$(curl -s -c "$J2" -b "$J2" "$BASE/login.php" | csrf_from)
  curl -s -o /dev/null -c "$J2" -b "$J2" -X POST --data-urlencode "csrf=$T2" -d "username=$FAKE&password=wrong$i" "$BASE/login.php"; rm -f "$J2"
done
J2=$(mktemp); T2=$(curl -s -c "$J2" -b "$J2" "$BASE/login.php" | csrf_from)
curl -s -c "$J2" -b "$J2" -X POST --data-urlencode "csrf=$T2" -d "username=$FAKE&password=x" "$BASE/login.php" | grep -q "Too many failed attempts" \
  && ok "6th attempt is throttled even with a new session" || bad "throttling" "not throttled"
rm -f "$J2"

echo "Authentication"
expect "API without session returns 401" "$(code "$BASE/api.php?action=summary")" 401
expect "page without session redirects" "$(code "$BASE/pages/findings.php")" 302
T=$(curl -s -b "$JAR" -c "$JAR" "$BASE/login.php" | csrf_from)
expect "login without CSRF token rejected (stays on page)" "$(code -X POST -d "username=$USER&password=$PASS" "$BASE/login.php")" 200
expect "wrong password rejected" "$(code -X POST --data-urlencode "csrf=$T" -d "username=$USER&password=wrong" "$BASE/login.php")" 200
expect "valid login redirects" "$(code -X POST --data-urlencode "csrf=$T" --data-urlencode "username=$USER" --data-urlencode "password=$PASS" "$BASE/login.php")" 302
expect "dashboard loads after login" "$(code "$BASE/dashboard.php")" 200
CSRF=$(curl -s -b "$JAR" -c "$JAR" "$BASE/pages/scans.php" | csrf_from)

echo "API input validation"
expect "unknown action 404" "$(code "$BASE/api.php?action=../../etc/passwd")" 404
expect "GET on start rejected (405)" "$(code "$BASE/api.php?action=start")" 405
expect "start without CSRF rejected (403)" "$(code -X POST -d "target=$TARGET" "$BASE/api.php?action=start")" 403
expect "start with wrong CSRF rejected (403)" "$(code -X POST -H 'X-CSRF-Token: deadbeef' -d "target=$TARGET" "$BASE/api.php?action=start")" 403
for t in "../../etc" "/etc" "%2e%2e%2f" "unknown-target"; do
  expect "path/unknown target '$t' rejected (422)" "$(code -X POST -H "X-CSRF-Token: $CSRF" --data "target=$t" "$BASE/api.php?action=start")" 422
done
expect "path parameter is ignored (no such input)" "$(code -X POST -H "X-CSRF-Token: $CSRF" -d "path=/etc&target=" "$BASE/api.php?action=start")" 422
expect "non-numeric scan id 404" "$(code "$BASE/api.php?action=status&id=abc")" 404

echo "Scan lifecycle"
R=$(curl -s -b "$JAR" -c "$JAR" -X POST -H "X-CSRF-Token: $CSRF" -d "target=$TARGET" "$BASE/api.php?action=start")
ID=$(echo "$R" | json id)
[ -n "$ID" ] && [ "$ID" != "null" ] && ok "scan created (#$ID)" || bad "scan created" "$R"
expect "duplicate start while queued rejected (409)" "$(code -X POST -H "X-CSRF-Token: $CSRF" -d "target=$TARGET" "$BASE/api.php?action=start")" 409
expect "status is queued" "$(curl -s -b "$JAR" "$BASE/api.php?action=status&id=$ID" | json status)" queued
expect "run without CSRF rejected" "$(code -X POST "$BASE/api.php?action=run&id=$ID")" 403
curl -s -b "$JAR" -X POST -H "X-CSRF-Token: $CSRF" "$BASE/api.php?action=run&id=$ID" > /tmp/v7-run-$$ &
RUNPID=$!
sleep 0.3
S=$(curl -s -m 5 -b "$JAR" "$BASE/api.php?action=status&id=$ID" | json status)
case "$S" in queued|running|completed) ok "status poll answers while scan runs ($S)";; *) bad "status poll during run" "$S";; esac
wait $RUNPID
expect "run returns ran=true" "$(json ran < /tmp/v7-run-$$)" 1
rm -f /tmp/v7-run-$$
expect "status is completed" "$(curl -s -b "$JAR" "$BASE/api.php?action=status&id=$ID" | json status)" completed
expect "repeated run is a no-op" "$(curl -s -b "$JAR" -X POST -H "X-CSRF-Token: $CSRF" "$BASE/api.php?action=run&id=$ID" | json ran)" ""
F=$(curl -s -b "$JAR" "$BASE/api.php?action=findings&scan_id=$ID")
[ "$(echo "$F" | json total)" -gt 0 ] 2>/dev/null && ok "findings returned ($(echo "$F" | json total))" || bad "findings returned" "$F"
echo "$F" | grep -q '"classification":"suspicious"' && ok "findings classified as suspicious" || bad "classification" "-"
echo "$F" | grep -q '"file_path":"/' && bad "no absolute paths in findings" "found one" || ok "no absolute paths in findings"
expect "history lists the scan" "$(curl -s -b "$JAR" "$BASE/api.php?action=history" | json items.0.id)" "$ID"
expect "summary latest is the scan" "$(curl -s -b "$JAR" "$BASE/api.php?action=summary" | json latest.id)" "$ID"
for p in dashboard.php pages/scans.php pages/findings.php "pages/findings.php?scan_id=$ID&severity=critical" pages/scan-history.php "pages/scan.php?id=$ID" pages/settings.php; do
  expect "page $p renders" "$(code "$BASE/$p")" 200
done
expect "scan page for unknown id renders error" "$(curl -s -b "$JAR" "$BASE/pages/scan.php?id=99999999" | grep -c 'Scan not found')" 1
expect "backend config not reachable" "$(code "$BASE/../backend/config/config.example.php")" 404
expect "includes not served or executed" "$(code "$BASE/includes/helpers.php")" 404

echo "Logout"
LT=$(curl -s -b "$JAR" "$BASE/pages/settings.php" | csrf_from)
expect "logout via GET does not log out" "$(code "$BASE/logout.php"; code "$BASE/dashboard.php")" "302200"
code -X POST --data-urlencode "csrf=$LT" "$BASE/logout.php" > /dev/null
expect "after logout API is 401" "$(code "$BASE/api.php?action=summary")" 401

echo; echo "$pass passed, $fail failed"; [ $fail -eq 0 ]
