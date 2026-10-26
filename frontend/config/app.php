<?php
declare(strict_types=1);

return [
    'name'      => 'V7-Shield',
    'tagline'   => 'Scan. Detect. Protect.',
    'version'   => '0.2.0-mvp',
    'repo_url'  => 'https://github.com/',
    'backend_path' => dirname(__DIR__, 2) . '/backend',

    'nav' => [
        'Overview' => [
            'dashboard'    => ['Dashboard', 'grid-1x2', 'dashboard.php'],
        ],
        'Malware Scanning' => [
            'scans'        => ['Start Malware Scan', 'play-circle', 'pages/scans.php'],
            'findings'     => ['Malware Findings', 'bug', 'pages/findings.php'],
            'scan-history' => ['Scan History', 'clock-history', 'pages/scan-history.php'],
        ],
        'Administration' => [
            'settings'     => ['Settings', 'gear', 'pages/settings.php'],
        ],
    ],
    'hidden' => [
        'scan' => ['Scan Details', 'activity', 'pages/scan.php', 'Malware Scanning'],
    ],
    'coming_soon' => ['Quarantine', 'File Integrity', 'Scheduled Scans', 'CMS Checks', 'Reports'],
];
