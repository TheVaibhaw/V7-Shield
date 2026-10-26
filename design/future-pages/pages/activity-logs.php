<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('activity-logs');
page_header('Activity Logs', 'Audit trail of user and system actions inside V7-Shield.', export_buttons('activity-table'));
echo demo_notice();
$logs = [
    ['time' => '2026-10-26 09:02', 'actor' => 'Demo Admin', 'action' => 'Started scan', 'target' => 'Northwind Store — Full', 'ip' => '192.0.2.10', 'result' => 'Success'],
    ['time' => '2026-10-26 07:50', 'actor' => 'Demo Admin', 'action' => 'Exported report', 'target' => 'Billing API monthly', 'ip' => '192.0.2.10', 'result' => 'Success'],
    ['time' => '2026-10-26 06:00', 'actor' => 'Scheduler', 'action' => 'Integrity comparison', 'target' => 'Uploads Directory', 'ip' => '—', 'result' => 'Success'],
    ['time' => '2026-10-25 23:40', 'actor' => 'Scheduler', 'action' => 'Database check', 'target' => 'Northwind Store', 'ip' => '—', 'result' => 'Failed'],
    ['time' => '2026-10-25 18:05', 'actor' => 'Security Analyst', 'action' => 'Updated baseline', 'target' => 'City Library Portal', 'ip' => '198.51.100.4', 'result' => 'Success'],
    ['time' => '2026-10-25 10:10', 'actor' => 'Demo Admin', 'action' => 'Changed settings', 'target' => 'Global exclusions', 'ip' => '192.0.2.10', 'result' => 'Success'],
    ['time' => '2026-10-24 16:32', 'actor' => 'Security Analyst', 'action' => 'Marked false positive', 'target' => 'F-1171', 'ip' => '198.51.100.4', 'result' => 'Success'],
    ['time' => '2026-10-24 11:23', 'actor' => 'Demo Admin', 'action' => 'Cancelled scan', 'target' => 'Legacy CRM — Quick', 'ip' => '192.0.2.10', 'result' => 'Success'],
    ['time' => '2026-10-23 09:12', 'actor' => 'unknown', 'action' => 'Failed sign-in', 'target' => 'Admin console', 'ip' => '203.0.113.77', 'result' => 'Failed'],
    ['time' => '2026-10-19 13:45', 'actor' => 'Security Analyst', 'action' => 'Restored file', 'target' => 'Q-0022', 'ip' => '198.51.100.4', 'result' => 'Success'],
];
card_open('Audit trail', 'journal-text');
data_table('activity-table', [
    'time' => ['Timestamp', fn($r) => '<span class="mono">' . e($r['time']) . '</span>'],
    'actor' => ['Actor'], 'action' => ['Action', fn($r) => '<strong>' . e($r['action']) . '</strong>'], 'target' => ['Target'],
    'ip' => ['IP (documentation range)', fn($r) => '<span class="mono">' . e($r['ip']) . '</span>'],
    'result' => ['Result', fn($r) => status_badge($r['result'] === 'Success' ? 'Completed' : 'Failed')],
], $logs, ['actor' => array_values(array_unique(array_column($logs, 'actor'))), 'result' => ['Success', 'Failed']], 10);
card_close();
render_footer();
