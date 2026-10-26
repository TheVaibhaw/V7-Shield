<?php
declare(strict_types=1);

namespace V7Shield;

const BACKEND_ROOT = __DIR__;
date_default_timezone_set('UTC');

spl_autoload_register(static function (string $class): void {
    $prefix = __NAMESPACE__ . '\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = BACKEND_ROOT . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

function config(): Config
{
    static $config = null;
    return $config ??= Config::load(BACKEND_ROOT . '/config');
}
