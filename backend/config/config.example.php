<?php
declare(strict_types=1);

return [
    'database' => [
        'dsn'      => 'mysql:host=127.0.0.1;port=3306;dbname=v7shield;charset=utf8mb4',
        'username' => 'tpss',
        'password' => '1',
    ],

    'users' => [
        // 'admin' => '$2y$12$....',
    ],

    'targets' => [
        // 'my-site' => ['label' => 'My WordPress site', 'path' => '/var/www/my-site'],
    ],

    'scanner' => [
        'max_file_size'   => 5 * 1024 * 1024,
        'max_depth'       => 25,
        'max_files'       => 100000,
        'max_duration'    => 300,
        'exclusions'      => [
            '.git/', 'node_modules/', 'vendor/composer/', '*.log',
        ],
        'max_findings_per_file' => 20,
        'max_skips_stored'      => 2000,
    ],

    'jobs' => [
        'execution'          => 'inline',
        'max_starts_per_hour' => 10,
        'stale_after'        => 900,
    ],

    'security' => [
        'session_name'      => 'v7shield_sid',
        'session_lifetime'  => 3600,
        'max_login_attempts' => 5,
    ],
];
