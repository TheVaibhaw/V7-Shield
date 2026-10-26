<?php
/**
 * Centralized DEMO data. Nothing here comes from a real scan.
 * Replace each section with backend API responses once the backend exists
 * (see README "Backend integration").
 */
declare(strict_types=1);

defined('APP_ROOT') || exit;

$projects = [
    ['id' => 'p1', 'name' => 'Acme Marketing Site', 'type' => 'WordPress', 'location' => 'https://acme.example.com', 'last_scan' => '2026-10-26 08:42', 'status' => 'Needs Attention', 'findings' => 7],
    ['id' => 'p2', 'name' => 'Northwind Store', 'type' => 'WordPress', 'location' => 'https://shop.northwind.example', 'last_scan' => '2026-10-25 22:10', 'status' => 'At Risk', 'findings' => 12],
    ['id' => 'p3', 'name' => 'City Library Portal', 'type' => 'Joomla', 'location' => 'https://library.example.org', 'last_scan' => '2026-10-25 18:03', 'status' => 'Healthy', 'findings' => 1],
    ['id' => 'p4', 'name' => 'Partner Intranet', 'type' => 'Joomla', 'location' => 'https://intranet.example.net', 'last_scan' => '2026-10-24 09:30', 'status' => 'Needs Attention', 'findings' => 4],
    ['id' => 'p5', 'name' => 'Billing API', 'type' => 'Custom PHP', 'location' => '/var/www/example/billing-api', 'last_scan' => '2026-10-26 07:15', 'status' => 'Healthy', 'findings' => 0],
    ['id' => 'p6', 'name' => 'Legacy CRM', 'type' => 'Custom PHP', 'location' => '/var/www/example/legacy-crm', 'last_scan' => '2026-10-23 14:55', 'status' => 'At Risk', 'findings' => 9],
    ['id' => 'p7', 'name' => 'Uploads Directory', 'type' => 'Directory', 'location' => '/var/www/example/shared/uploads', 'last_scan' => '2026-10-26 06:00', 'status' => 'Needs Attention', 'findings' => 3],
];

$scans = [
    ['name' => 'Nightly full scan', 'project' => 'Northwind Store', 'type' => 'Full', 'status' => 'Running', 'progress' => 64, 'files' => 18342, 'findings' => 5, 'started' => '2026-10-26 09:02', 'duration' => '14m 20s', 'phase' => 'Pattern matching (plugins)'],
    ['name' => 'Uploads quick check', 'project' => 'Uploads Directory', 'type' => 'Quick', 'status' => 'Running', 'progress' => 31, 'files' => 2210, 'findings' => 1, 'started' => '2026-10-26 09:10', 'duration' => '3m 05s', 'phase' => 'Extension/signature checks'],
    ['name' => 'Core integrity', 'project' => 'Acme Marketing Site', 'type' => 'Integrity', 'status' => 'Queued', 'progress' => 0, 'files' => 0, 'findings' => 0, 'started' => '2026-10-26 09:30', 'duration' => '—', 'phase' => 'Waiting for worker'],
    ['name' => 'Scheduled full scan', 'project' => 'Acme Marketing Site', 'type' => 'Full', 'status' => 'Completed', 'progress' => 100, 'files' => 12874, 'findings' => 7, 'started' => '2026-10-26 08:20', 'duration' => '22m 11s', 'phase' => 'Done'],
    ['name' => 'Billing API code review', 'project' => 'Billing API', 'type' => 'PHP Patterns', 'status' => 'Completed', 'progress' => 100, 'files' => 1432, 'findings' => 0, 'started' => '2026-10-26 07:05', 'duration' => '4m 48s', 'phase' => 'Done'],
    ['name' => 'Database read-only checks', 'project' => 'Northwind Store', 'type' => 'Database', 'status' => 'Failed', 'progress' => 42, 'files' => 0, 'findings' => 0, 'started' => '2026-10-25 23:40', 'duration' => '1m 12s', 'phase' => 'Connection timed out'],
    ['name' => 'Library full scan', 'project' => 'City Library Portal', 'type' => 'Full', 'status' => 'Completed', 'progress' => 100, 'files' => 9821, 'findings' => 1, 'started' => '2026-10-25 17:40', 'duration' => '23m 02s', 'phase' => 'Done'],
    ['name' => 'CRM webshell sweep', 'project' => 'Legacy CRM', 'type' => 'Quick', 'status' => 'Cancelled', 'progress' => 18, 'files' => 640, 'findings' => 2, 'started' => '2026-10-24 11:20', 'duration' => '2m 40s', 'phase' => 'Cancelled by user'],
    ['name' => 'Intranet extensions', 'project' => 'Partner Intranet', 'type' => 'CMS', 'status' => 'Completed', 'progress' => 100, 'files' => 6402, 'findings' => 4, 'started' => '2026-10-24 09:02', 'duration' => '28m 15s', 'phase' => 'Done'],
    ['name' => 'Weekly full scan', 'project' => 'Legacy CRM', 'type' => 'Full', 'status' => 'Completed', 'progress' => 100, 'files' => 7315, 'findings' => 9, 'started' => '2026-10-23 14:20', 'duration' => '35m 09s', 'phase' => 'Done'],
];

$findingTemplates = [
    ['Critical', 'Possible webshell indicator', 'wp-content/uploads/2026/10/cache-img.php', 'Northwind Store', 'Webshell'],
    ['Critical', 'PHP file disguised as image', 'shared/uploads/avatar_492.jpg', 'Uploads Directory', 'Signature mismatch'],
    ['High', 'Obfuscated base64 + eval pattern', 'wp-content/plugins/seo-helper/inc/loader.php', 'Acme Marketing Site', 'Obfuscation'],
    ['High', 'Unexpected administrator account', 'wp_users: user "support_tmp"', 'Northwind Store', 'Account'],
    ['High', 'Modified core file', 'libraries/src/Application/SiteApplication.php', 'Partner Intranet', 'Core integrity'],
    ['High', 'Dangerous function with request input', 'modules/export/run.php', 'Legacy CRM', 'Dangerous function'],
    ['Medium', 'Outdated plugin with known advisories', 'wp-content/plugins/contact-form-x (v2.1.0)', 'Acme Marketing Site', 'Vulnerable component'],
    ['Medium', 'Debug mode enabled in production', 'wp-config.php (WP_DEBUG)', 'Northwind Store', 'Configuration'],
    ['Medium', 'Executable file in uploads folder', 'images/tmp/run.phtml', 'Partner Intranet', 'Unexpected executable'],
    ['Medium', 'Long encoded string in template', 'templates/cassiopeia/index.php', 'City Library Portal', 'Obfuscation'],
    ['Low', 'Directory listing may be enabled', '/shared/uploads/', 'Uploads Directory', 'Configuration'],
    ['Low', 'World-writable configuration file', 'config/settings.ini', 'Legacy CRM', 'Permissions'],
    ['Low', 'Backup archive in web root', 'backup-2025.zip', 'Legacy CRM', 'Exposure'],
    ['Medium', 'Suspicious cron entry referencing remote URL', 'wp_options: cron', 'Northwind Store', 'Persistence'],
    ['High', 'Hidden iframe injection pattern', 'wp-content/themes/storefront-child/footer.php', 'Northwind Store', 'Injection'],
    ['Low', 'Readme exposes CMS version', 'readme.html', 'Acme Marketing Site', 'Exposure'],
];
$reviewStates = ['New', 'In Review', 'Resolved', 'False Positive', 'New', 'In Review'];
$findings = [];
foreach ($findingTemplates as $i => [$sev, $title, $file, $project, $cat]) {
    $findings[] = [
        'id' => sprintf('F-%04d', 1180 - $i),
        'severity' => $sev, 'title' => $title, 'resource' => $file, 'project' => $project,
        'category' => $cat, 'detected' => date('Y-m-d H:i', strtotime('2026-10-26 09:00') - $i * 9700),
        'status' => $reviewStates[$i % count($reviewStates)],
        'confidence' => [92, 88, 81, 76, 70, 64][$i % 6] . '%',
        'rule' => 'V7-' . strtoupper(substr(md5($title), 0, 6)),
    ];
}

$events = [
    ['time' => '2026-10-26 09:10', 'category' => 'Scan', 'severity' => 'Info', 'source' => 'Scheduler', 'project' => 'Uploads Directory', 'title' => 'Scan started', 'desc' => 'Quick scan started by schedule "Hourly uploads".'],
    ['time' => '2026-10-26 09:04', 'category' => 'Detection', 'severity' => 'Critical', 'source' => 'Scanner', 'project' => 'Northwind Store', 'title' => 'Suspicious pattern detected', 'desc' => 'Webshell indicator rule matched in an uploads file.'],
    ['time' => '2026-10-26 09:02', 'category' => 'Scan', 'severity' => 'Info', 'source' => 'Admin', 'project' => 'Northwind Store', 'title' => 'Scan started', 'desc' => 'Full scan started manually by admin.'],
    ['time' => '2026-10-26 08:42', 'category' => 'Scan', 'severity' => 'Info', 'source' => 'Scheduler', 'project' => 'Acme Marketing Site', 'title' => 'Scan completed', 'desc' => '12,874 files scanned, 7 findings.'],
    ['time' => '2026-10-26 07:50', 'category' => 'Report', 'severity' => 'Info', 'source' => 'Admin', 'project' => 'Billing API', 'title' => 'Report generated', 'desc' => 'Monthly summary report exported as PDF (demo).'],
    ['time' => '2026-10-26 06:00', 'category' => 'Integrity', 'severity' => 'Medium', 'source' => 'Integrity', 'project' => 'Uploads Directory', 'title' => 'File changes detected', 'desc' => '14 files added since the last baseline comparison.'],
    ['time' => '2026-10-25 23:41', 'category' => 'Scan', 'severity' => 'High', 'source' => 'Scanner', 'project' => 'Northwind Store', 'title' => 'Scan failed', 'desc' => 'Database checks failed: connection timed out.'],
    ['time' => '2026-10-25 18:05', 'category' => 'Integrity', 'severity' => 'Info', 'source' => 'Admin', 'project' => 'City Library Portal', 'title' => 'Baseline updated', 'desc' => 'Integrity baseline re-created after Joomla update.'],
    ['time' => '2026-10-25 14:22', 'category' => 'Account', 'severity' => 'High', 'source' => 'Scanner', 'project' => 'Northwind Store', 'title' => 'New administrator account', 'desc' => 'Administrator "support_tmp" not present in previous scan.'],
    ['time' => '2026-10-25 10:10', 'category' => 'Settings', 'severity' => 'Info', 'source' => 'Admin', 'project' => '—', 'title' => 'Exclusions changed', 'desc' => 'Added "node_modules/" to global exclusions.'],
    ['time' => '2026-10-24 11:23', 'category' => 'Scan', 'severity' => 'Low', 'source' => 'Admin', 'project' => 'Legacy CRM', 'title' => 'Scan cancelled', 'desc' => 'Quick scan cancelled after 640 files.'],
    ['time' => '2026-10-24 09:31', 'category' => 'Detection', 'severity' => 'High', 'source' => 'Scanner', 'project' => 'Partner Intranet', 'title' => 'Core file modified', 'desc' => 'Hash mismatch against Joomla 5.x core checksums.'],
];

$integrity = [
    'summary' => ['added' => 23, 'modified' => 41, 'deleted' => 6, 'hash_changed' => 47, 'baseline' => 'Active', 'baseline_date' => '2026-10-01 02:00', 'last_check' => '2026-10-26 06:00'],
    'changes' => [
        ['change' => 'Added', 'file' => 'shared/uploads/avatar_492.jpg', 'project' => 'Uploads Directory', 'size' => '18.2 KB', 'hash' => 'a3f9…c71e', 'when' => '2026-10-26 05:48'],
        ['change' => 'Modified', 'file' => 'wp-content/themes/storefront-child/footer.php', 'project' => 'Northwind Store', 'size' => '6.1 KB', 'hash' => '0be4…19d2', 'when' => '2026-10-25 21:13'],
        ['change' => 'Added', 'file' => 'wp-content/uploads/2026/10/cache-img.php', 'project' => 'Northwind Store', 'size' => '2.4 KB', 'hash' => 'e81c…7a40', 'when' => '2026-10-25 20:57'],
        ['change' => 'Modified', 'file' => 'libraries/src/Application/SiteApplication.php', 'project' => 'Partner Intranet', 'size' => '41.7 KB', 'hash' => '7d22…b0aa', 'when' => '2026-10-24 03:12'],
        ['change' => 'Deleted', 'file' => 'administrator/logs/error.php', 'project' => 'Partner Intranet', 'size' => '—', 'hash' => '—', 'when' => '2026-10-24 03:11'],
        ['change' => 'Modified', 'file' => 'wp-content/plugins/seo-helper/inc/loader.php', 'project' => 'Acme Marketing Site', 'size' => '9.8 KB', 'hash' => '55aa…e013', 'when' => '2026-10-23 16:40'],
        ['change' => 'Added', 'file' => 'images/tmp/run.phtml', 'project' => 'Partner Intranet', 'size' => '1.1 KB', 'hash' => 'c0de…f00d', 'when' => '2026-10-23 02:05'],
        ['change' => 'Modified', 'file' => 'config/settings.ini', 'project' => 'Legacy CRM', 'size' => '0.9 KB', 'hash' => '91b3…4c2e', 'when' => '2026-10-22 12:00'],
        ['change' => 'Deleted', 'file' => 'wp-content/plugins/hello.php', 'project' => 'Acme Marketing Site', 'size' => '—', 'hash' => '—', 'when' => '2026-10-21 09:44'],
    ],
];

$quarantine = [
    ['id' => 'Q-0031', 'file' => 'wp-content/uploads/2026/10/cache-img.php', 'project' => 'Northwind Store', 'reason' => 'Webshell indicator (rule V7-WS-014)', 'hash' => 'e81c4b…(demo)…7a40', 'when' => '2026-10-26 09:05', 'status' => 'Pending'],
    ['id' => 'Q-0030', 'file' => 'shared/uploads/avatar_492.jpg', 'project' => 'Uploads Directory', 'reason' => 'PHP code inside JPEG extension', 'hash' => 'a3f90d…(demo)…c71e', 'when' => '2026-10-26 06:02', 'status' => 'Reviewing'],
    ['id' => 'Q-0027', 'file' => 'images/tmp/run.phtml', 'project' => 'Partner Intranet', 'reason' => 'Executable in media directory', 'hash' => 'c0de11…(demo)…f00d', 'when' => '2026-10-23 03:00', 'status' => 'Pending'],
    ['id' => 'Q-0022', 'file' => 'modules/legacy/shell_helper.php', 'project' => 'Legacy CRM', 'reason' => 'Dangerous function chain', 'hash' => '4be7aa…(demo)…0912', 'when' => '2026-10-19 13:40', 'status' => 'Restored'],
];

$notifications = [
    ['id' => 1, 'severity' => 'Critical', 'category' => 'Detection', 'title' => 'Critical finding on Northwind Store', 'body' => 'Possible webshell indicator in uploads.', 'time' => '12 min ago', 'read' => false],
    ['id' => 2, 'severity' => 'High', 'category' => 'Scan', 'title' => 'Database checks failed', 'body' => 'Read-only database scan timed out.', 'time' => '9 h ago', 'read' => false],
    ['id' => 3, 'severity' => 'Medium', 'category' => 'Integrity', 'title' => '14 new files in uploads', 'body' => 'Compare with baseline to review.', 'time' => '3 h ago', 'read' => false],
    ['id' => 4, 'severity' => 'Info', 'category' => 'Report', 'title' => 'Monthly report ready', 'body' => 'Billing API summary was generated.', 'time' => '1 h ago', 'read' => true],
    ['id' => 5, 'severity' => 'Info', 'category' => 'Scan', 'title' => 'Scan completed', 'body' => 'Acme Marketing Site — 7 findings.', 'time' => '27 min ago', 'read' => true],
    ['id' => 6, 'severity' => 'High', 'category' => 'Account', 'title' => 'New administrator detected', 'body' => '"support_tmp" on Northwind Store.', 'time' => '19 h ago', 'read' => false],
];

// Deterministic chart series for the threat activity chart.
$series = [];
foreach (['24h' => 24, '7d' => 7, '30d' => 30, '90d' => 90] as $range => $points) {
    mt_srand(crc32($range));
    $labels = [];
    $rows = ['Critical' => [], 'High' => [], 'Medium' => [], 'Low' => []];
    $end = strtotime('2026-10-26 09:00');
    for ($i = $points - 1; $i >= 0; $i--) {
        $labels[] = $range === '24h' ? date('H:00', $end - $i * 3600) : date('M j', $end - $i * 86400);
        $scale = $range === '24h' ? 1 : 3;
        $rows['Critical'][] = mt_rand(0, 1 * $scale);
        $rows['High'][] = mt_rand(0, 2 * $scale);
        $rows['Medium'][] = mt_rand(1, 3 * $scale);
        $rows['Low'][] = mt_rand(1, 4 * $scale);
    }
    $series[$range] = ['labels' => $labels, 'data' => $rows];
}

return [
    'user' => ['name' => 'Demo Admin', 'email' => 'admin@example.com', 'role' => 'Administrator'],
    'score' => ['value' => 86, 'label' => 'Needs Attention', 'critical' => 2, 'high' => 5, 'warnings' => 9, 'passed' => 412],
    'metrics' => [
        ['file-earmark-code', 'Total files scanned', '58,416', 'cyan', '+4.2%', 'Files read across all projects in the last 30 days.'],
        ['bug', 'Threats detected', '16', 'red', '+3', 'Findings at High or Critical severity awaiting review.'],
        ['file-earmark-break', 'Suspicious files', '9', 'amber', '-2', 'Files matching heuristics that need a human decision.'],
        ['exclamation-octagon', 'Critical findings', '2', 'red', '+1', 'Issues that should be investigated immediately.'],
        ['fingerprint', 'Integrity changes', '70', 'blue', '+18', 'Added, modified or deleted files versus baseline.'],
        ['shield-check', 'Protected projects', '7', 'green', '+1', 'Projects configured for scheduled scanning.'],
        ['database-check', 'Database checks', '38', 'blue', '+6', 'Read-only database checks executed.'],
        ['check2-circle', 'Last successful scan', '08:42', 'green', '', 'Acme Marketing Site, today.'],
    ],
    'projects' => $projects,
    'scans' => $scans,
    'findings' => $findings,
    'events' => $events,
    'integrity' => $integrity,
    'quarantine' => $quarantine,
    'notifications' => $notifications,
    'series' => $series,
];
