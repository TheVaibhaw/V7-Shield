<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
    . "font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; object-src 'none'; "
    . "base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cross-Origin-Opener-Policy: same-origin');

$config = require APP_ROOT . '/config/app.php';
require APP_ROOT . '/includes/helpers.php';

$backendBootstrap = $config['backend_path'] . '/bootstrap.php';
if (!is_file($backendBootstrap)) {
    http_response_code(500);
    exit('V7-Shield backend not found. Check backend_path in frontend/config/app.php.');
}
require $backendBootstrap;

$v7 = V7Shield\config();
$auth = new V7Shield\Security\Auth($v7, static fn() => V7Shield\Database::connect($v7));
$auth->startSession();
$auth->verifySession();

set_exception_handler(static function (Throwable $e): void {
    error_log('[V7-Shield] ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<h1>Something went wrong</h1><p>The page could not be rendered. Check the server error log.</p>';
});

if (!defined('V7_PUBLIC') && $auth->user() === null) {
    if (defined('V7_API')) {
        json_response(['error' => 'Authentication required.'], 401);
    }
    header('Location: ' . url('login.php'));
    exit;
}

if (!defined('V7_PUBLIC') && !defined('V7_PASSWORD_PAGE') && $auth->mustChangePassword()) {
    if (defined('V7_API')) {
        json_response(['error' => 'Password change required.'], 403);
    }
    header('Location: ' . url('change-password.php'));
    exit;
}
