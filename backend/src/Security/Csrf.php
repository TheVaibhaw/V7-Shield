<?php
declare(strict_types=1);

namespace V7Shield\Security;

/** Per-session synchroniser token. */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['v7_csrf']) || !is_string($_SESSION['v7_csrf'])) {
            $_SESSION['v7_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['v7_csrf'];
    }

    public static function valid(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['v7_csrf']) && hash_equals((string) $_SESSION['v7_csrf'], $token);
    }
}
