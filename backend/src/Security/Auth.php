<?php
declare(strict_types=1);

namespace V7Shield\Security;

use PDO;
use V7Shield\Config;

final class Auth
{
    public const MIN_PASSWORD_LENGTH = 12;

    /** @param (\Closure(): PDO)|null $pdo lazy database connection */
    public function __construct(private readonly Config $config, private readonly ?\Closure $pdo = null)
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

    public function mustChangePassword(): bool
    {
        return !empty($_SESSION['v7_must_change']);
    }

    public function isDatabaseUser(): bool
    {
        return ($_SESSION['v7_source'] ?? '') === 'db';
    }

    public function hasUsers(): bool
    {
        if (count($this->config->get('users', [])) > 0) {
            return true;
        }
        try {
            return (int) $this->db()?->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return string|null error message, null on success */
    public function attempt(string $username, string $password): ?string
    {
        $max = (int) $this->config->get('security.max_login_attempts', 5);
        $lockedUntil = (int) ($_SESSION['v7_lock_until'] ?? 0);
        if ($lockedUntil > time()) {
            return 'Too many failed attempts. Try again later.';
        }

        $source = 'config';
        $mustChange = false;
        $users = $this->config->get('users', []);
        $hash = is_string($users[$username] ?? null) ? $users[$username] : null;
        if ($hash === null) {
            $row = $this->findDbUser($username);
            if ($row !== null) {
                $source = 'db';
                $hash = (string) $row['password_hash'];
                $mustChange = (bool) $row['must_change_password'];
            }
        }
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

        if ($source === 'db') {
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $this->updateHash($username, password_hash($password, PASSWORD_DEFAULT), $mustChange);
            }
            $this->db()?->prepare('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE username = ?')->execute([$username]);
        }
        session_regenerate_id(true);
        $_SESSION = ['v7_user' => $username, 'v7_seen' => time(), 'v7_source' => $source, 'v7_must_change' => $mustChange];
        return null;
    }

    public function changePassword(string $current, string $new, string $confirm): ?string
    {
        $username = $this->user();
        if ($username === null || !$this->isDatabaseUser()) {
            return 'This account is managed in config.local.php; change its hash there.';
        }
        $row = $this->findDbUser($username);
        if ($row === null || !password_verify($current, (string) $row['password_hash'])) {
            usleep(400000);
            return 'Current password is incorrect.';
        }
        if ($new !== $confirm) {
            return 'The new passwords do not match.';
        }
        if (\V7Shield\Text::length($new) < self::MIN_PASSWORD_LENGTH) {
            return 'Use at least ' . self::MIN_PASSWORD_LENGTH . ' characters.';
        }
        if (hash_equals($current, $new)) {
            return 'Choose a password different from the current one.';
        }
        $this->updateHash($username, password_hash($new, PASSWORD_DEFAULT), false);
        session_regenerate_id(true);
        $_SESSION['v7_must_change'] = false;
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

    private function findDbUser(string $username): ?array
    {
        try {
            $st = $this->db()?->prepare('SELECT username, password_hash, must_change_password FROM users WHERE username = ?');
            if ($st === null) {
                return null;
            }
            $st->execute([$username]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            error_log('[V7-Shield] User lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    private function updateHash(string $username, string $hash, bool $mustChange): void
    {
        $this->db()?->prepare('UPDATE users SET password_hash = ?, must_change_password = ? WHERE username = ?')
            ->execute([$hash, (int) $mustChange, $username]);
    }

    private function db(): ?PDO
    {
        return $this->pdo ? ($this->pdo)() : null;
    }
}
