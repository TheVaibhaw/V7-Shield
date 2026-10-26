<?php
/**
 * Operator CLI: run a scan without the database and print JSON.
 *   php backend/bin/scan.php --target=my-site
 *   php backend/bin/scan.php --dir=/path/to/project     (CLI only; the web API never accepts paths)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/bootstrap.php';

use V7Shield\Scanner\FileWalker;
use V7Shield\Scanner\ScanSink;
use V7Shield\Scanner\Scanner;

$opts = getopt('', ['target:', 'dir:', 'include-skips']);
$config = V7Shield\config();
try {
    if (isset($opts['target'])) {
        $path = V7Shield\Service\ScanService::resolveConfiguredTarget($config, (string) $opts['target'])['path'];
    } elseif (isset($opts['dir'])) {
        $path = (string) $opts['dir'];
    } else {
        fwrite(STDERR, "Usage: php backend/bin/scan.php --target=ID | --dir=PATH [--include-skips]\n");
        exit(2);
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(2);
}

$sink = new class implements ScanSink {
    public array $findings = [];
    public array $skips = [];
    public function finding(array $finding): void { $this->findings[] = $finding; }
    public function skipped(string $path, string $reason, string $detail): void { $this->skips[] = compact('path', 'reason', 'detail'); }
    public function progress(array $stats): void {}
};

try {
    $walker = new FileWalker($path, $config->get('scanner.exclusions'), (int) $config->get('scanner.max_depth'));
    $stats = Scanner::fromConfig($config)->scan($walker, $sink);
} catch (Throwable $e) {
    fwrite(STDERR, 'Scan failed: ' . $e->getMessage() . "\n");
    exit(1);
}
$out = ['stats' => $stats, 'findings' => $sink->findings];
if (isset($opts['include-skips'])) {
    $out['skipped'] = $sink->skips;
}
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), PHP_EOL;
exit($stats['findings'] > 0 ? 3 : 0);
