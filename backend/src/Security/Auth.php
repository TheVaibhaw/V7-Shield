<?php
declare(strict_types=1);

namespace V7Shield\Security;

use V7Shield\Config;

final class Auth
{
    public function __construct(private readonly Config $config)
    {
    }

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        session_name((string) $this->config->get('security.session_name', 'v7shield_sid'));
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict']);
        ini_set('session.use_strict_mode', '1');
        session_start();

        $lifetime = (int) $this->config->get('security.session_lifetime', 3600);
        if (isset($_SESSION['v7_user'], $_SESSION['v7_seen']) && time() - (int) $_SESSION['v7_seen'] > $lifetime) {
            $this->logout();
            session_start();
        }
        if (isset($_SESSION['v7_user'])) {
            $_SESSION['v7_seen'] = time();
        }
    }

    public function user(): ?string
    {
        return isset($_SESSION['v7_user']) && is_string($_SESSION['v7_user']) ? $_SESSION['v7_user'] : null;
    }

    public function hasUsers(): bool
    {
        return count($this->config->get('users', [])) > 0;
    }

    /** @return string|null error message, null on success */
    public function attempt(string $username, string $password): ?string
    {
        $max = (int) $this->config->get('security.max_login_attempts', 5);
        $lockedUntil = (int) ($_SESSION['v7_lock_until'] ?? 0);
        if ($lockedUntil > time()) {
            return 'Too many failed attempts. Try again later.';
        }
        $users = $this->config->get('users', []);
        $hash = is_string($users[$username] ?? null) ? $users[$username] : null;
        $ok = password_verify($password, $hash ?? '$2y$10$2ZYNu5KkqeXDnASA4bXABuX0Dy0av1wSlgJ5LS667nPmsbvHADo1C') && $hash !== null;
        if (!$ok) {
            $_SESSION['v7_fails'] = (int) ($_SESSION['v7_fails'] ?? 0) + 1;
            if ($_SESSION['v7_fails'] >= $max) {
                $_SESSION['v7_lock_until'] = time() + 900;
                $_SESSION['v7_fails'] = 0;
            }
            usleep(400000);
            return 'Invalid username or password.';
        }
        session_regenerate_id(true);
        $_SESSION = ['v7_user' => $username, 'v7_seen' => time()];
        return null;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
            session_destroy();
        }
    }
}
