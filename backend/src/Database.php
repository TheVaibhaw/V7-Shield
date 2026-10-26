<?php
declare(strict_types=1);

namespace V7Shield;

use PDO;

/** PDO factory. Connection errors never expose the DSN or credentials. */
final class Database
{
    public static function connect(Config $config): PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }
        $db = $config->get('database');
        try {
            $pdo = new PDO((string) $db['dsn'], (string) $db['username'], (string) $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
            $pdo->exec("SET time_zone = '+00:00'");
        } catch (\PDOException $e) {
            error_log('[V7-Shield] Database connection failed: ' . $e->getMessage());
            throw new ServiceException('Database is unavailable. Check backend configuration.', 503);
        }
        return $pdo;
    }
}
