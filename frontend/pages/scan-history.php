<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('scan-history');
page_header('Scan History', 'Every scan run across your projects, with results and duration.', export_buttons('history-table'));
echo demo_notice();
$history = $demo['scans'];
// Pad the history with older sample runs so pagination is meaningful.
foreach ($demo['projects'] as $i => $p) {
    $history[] = ['name' => 'Weekly full scan', 'project' => $p['name'], 'type' => 'Full', 'status' => $i % 4 === 3 ? 'Failed' : 'Completed', 'progress' => 100,
        'files' => 3000 + $i * 1711, 'findings' => ($i * 3) % 7, 'started' => date('Y-m-d H:i', strtotime('2026-10-19 02:00') + $i * 1800), 'duration' => (12 + $i * 3) . 'm 0' . $i . 's', 'phase' => 'Done'];
}
$projects = array_column($demo['projects'], 'name');
card_open('All scans', 'clock-history');
data_table('history-table', scan_columns(), $history, ['status' => ['Completed', 'Running', 'Queued', 'Failed', 'Cancelled'], 'type' => ['Full', 'Quick', 'Integrity', 'PHP Patterns', 'Database', 'CMS'], 'project' => $projects], 10);
card_close();
render_footer();
