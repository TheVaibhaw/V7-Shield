<?php
/**
 * Shared bootstrap: loads config, helpers and demo data for every page.
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$config = require APP_ROOT . '/config/app.php';
require APP_ROOT . '/includes/helpers.php';
$demo = require APP_ROOT . '/data/demo-data.php';

set_exception_handler(static function (Throwable $e): void {
    error_log('[V7-Shield] ' . $e->getMessage());
    http_response_code(500);
    echo '<h1>Something went wrong</h1><p>The page could not be rendered. Check the server error log.</p>';
});
