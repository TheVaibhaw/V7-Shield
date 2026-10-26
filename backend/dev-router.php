<?php
declare(strict_types=1);

$path = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));

$entryPoints = ['/', '/index.php', '/dashboard.php', '/login.php', '/logout.php', '/change-password.php', '/api.php'];
$allowed = !str_contains($path, "\0") && !str_contains($path, '..') && (
    in_array($path, $entryPoints, true)
    || preg_match('#^/pages/[a-z0-9-]+\.php$#', $path) === 1
    || preg_match('#^/assets/[A-Za-z0-9_./-]+\.(?:css|js|svg|png|jpg|jpeg|gif|ico|webp|woff2?)$#', $path) === 1
);

if ($allowed && ($path === '/' || is_file(dirname(__DIR__) . '/frontend' . $path))) {
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
header('X-Content-Type-Options: nosniff');
echo "Not found\n";
return true;
