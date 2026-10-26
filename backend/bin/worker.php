<?php
/**
 * Process queued scan jobs.
 *   php backend/bin/worker.php          # process all queued jobs, then exit (cron-friendly)
 *   php backend/bin/worker.php --loop   # keep polling every 5 seconds (systemd/supervisor)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/bootstrap.php';

use V7Shield\Database;
use V7Shield\Repository\ScanRepository;
use V7Shield\Service\ScanService;

$config = V7Shield\config();
$repo = new ScanRepository(Database::connect($config));
$service = new ScanService($config, $repo);
$loop = in_array('--loop', $argv, true);

do {
    $repo->failStale((int) $config->get('jobs.stale_after', 900));
    while (($id = $repo->nextQueuedId()) !== null) {
        $ran = $service->run($id);
        $job = $repo->find($id);
        printf("[%s] scan #%d %s (%s)\n", gmdate('c'), $id, $ran ? 'processed' : 'skipped', $job['status'] ?? '?');
    }
    if ($loop) {
        sleep(5);
    }
} while ($loop);
