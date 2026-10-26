<?php
declare(strict_types=1);

return [
    'name'      => 'V7-Shield',
    'tagline'   => 'Scan. Detect. Protect.',
    'version'   => '0.1.0-alpha',
    'demo_mode' => true,
    'repo_url'  => 'https://github.com/',
    'api_base'  => '/backend/api',
    'nav' => [
        'Overview' => [
            'dashboard'        => ['Dashboard', 'grid-1x2', 'dashboard.php'],
            'overview'         => ['Security Overview', 'shield-check', 'pages/security-overview.php'],
        ],
        'Scanning' => [
            'scans'            => ['Start New Scan', 'play-circle', 'pages/scans.php'],
            'active-scans'     => ['Active Scans', 'activity', 'pages/active-scans.php'],
            'scan-history'     => ['Scan History', 'clock-history', 'pages/scan-history.php'],
            'scheduled-scans'  => ['Scheduled Scans', 'calendar-week', 'pages/scheduled-scans.php'],
        ],
        'Threat Management' => [
            'findings'         => ['Threat Detections', 'bug', 'pages/findings.php'],
            'malware-analysis' => ['Malware Analysis', 'virus', 'pages/malware-analysis.php'],
            'suspicious-files' => ['Suspicious Files', 'file-earmark-break', 'pages/suspicious-files.php'],
            'quarantine'       => ['Quarantine', 'archive', 'pages/quarantine.php'],
        ],
        'Integrity and Monitoring' => [
            'file-integrity'   => ['File Integrity', 'fingerprint', 'pages/file-integrity.php'],
            'file-changes'     => ['File Changes', 'file-diff', 'pages/file-changes.php'],
            'security-events'  => ['Security Events', 'broadcast', 'pages/security-events.php'],
            'activity-logs'    => ['Activity Logs', 'journal-text', 'pages/activity-logs.php'],
        ],
        'Website Security' => [
            'projects'         => ['Websites & Projects', 'globe2', 'pages/projects.php'],
            'wordpress'        => ['WordPress Security', 'wordpress', 'pages/wordpress-security.php'],
            'joomla'           => ['Joomla Security', 'boxes', 'pages/joomla-security.php'],
            'php-security'     => ['PHP Application Security', 'filetype-php', 'pages/php-security.php'],
            'database'         => ['Database Security', 'database-lock', 'pages/database-security.php'],
        ],
        'Reports' => [
            'reports'          => ['Security Reports', 'file-earmark-bar-graph', 'pages/reports.php'],
            'export'           => ['Export Center', 'box-arrow-up-right', 'pages/export-center.php'],
        ],
        'Administration' => [
            'notifications'    => ['Notifications', 'bell', 'pages/notifications.php'],
            'settings'         => ['Settings', 'gear', 'pages/settings.php'],
            'help'             => ['Help & Documentation', 'question-circle', 'pages/help.php'],
        ],
    ],
];