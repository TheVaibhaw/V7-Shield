<?php
declare(strict_types=1);

namespace V7Shield\Security;

use PDO;
use V7Shield\Config;
use V7Shield\Text;

final class Auth
{
    public const MIN_PASSWORD_LENGTH = 12;

    private const LOCK_WINDOW = 900;
    private const MAX_PER_IP = 20;
    private const MAX_PER_USER = 50;
    private const DUMMY_HASH = '$2y$10$2ZYNu5KkqeXDnASA4bXABuX0Dy0av1wSlgJ5LS667nPmsbvHADo1C';

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
        ini_set('session.use_only_cookies', '1');
        session_start();

        if (isset($_SESSION['v7_user'])) {
            $idle = (int) $this->config->get('security.session_lifetime', 3600);
            $maxAge = (int) $this->config->get('security.session_max_age', 28800);
            $now = time();
            if ($now - (int) ($_SESSION['v7_seen'] ?? 0) > $idle || $now - (int) ($_SESSION['v7_login_at'] ?? 0) > $maxAge) {
                $this->logout();
                session_start();
                return;
            }
            $_SESSION['v7_seen'] = $now;
        }
    }

    public function verifySession(): void
    {
        $user = $this->user();
        if ($user === null) {
            return;
        }
        $hash = $this->currentHash($user, (string) ($_SESSION['v7_source'] ?? ''));
        if ($hash === null || !hash_equals((string) ($_SESSION['v7_fp'] ?? ''), self::fingerprint($hash))) {
            $this->logout();
            session_start();
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

    public static function isLoopback(?string $ip): bool
    {
        return $ip === '127.0.0.1' || $ip === '::1' || (is_string($ip) && str_starts_with($ip, '127.'));
    }

    /** @return string|null error message, null on success */
    public function attempt(string $username, string $password, ?string $ip = null): ?string
    {
        $ip = Text::cut((string) $ip, 45);
        $username = Text::cut($username, 64);
        if ($this->isThrottled($username, $ip)) {
            return 'Too many failed attempts. Try again in 15 minutes.';
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
        $ok = password_verify($password, $hash ?? self::DUMMY_HASH) && $hash !== null;
        if (!$ok) {
            $this->recordFailure($username, $ip);
            usleep(random_int(300000, 600000));
            return 'Invalid username or password.';
        }
        if ($mustChange && !self::isLoopback($ip)) {
            return 'This account still uses its default password. Sign in from the server itself (localhost) and change it first.';
        }

        $this->clearFailures($username, $ip);
        if ($source === 'db') {
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $this->updateHash($username, $hash, $mustChange);
            }
            $this->db()?->prepare('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE username = ?')->execute([$username]);
        }
        session_regenerate_id(true);
        $now = time();
        $_SESSION = [
            'v7_user' => $username, 'v7_seen' => $now, 'v7_login_at' => $now, 'v7_source' => $source,
            'v7_must_change' => $mustChange, 'v7_fp' => self::fingerprint($hash),
        ];
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
        if (Text::length($new) < self::MIN_PASSWORD_LENGTH) {
            return 'Use at least ' . self::MIN_PASSWORD_LENGTH . ' characters.';
        }
        if (strlen($new) > 72) {
            return 'Use at most 72 bytes (bcrypt limit).';
        }
        if (hash_equals($current, $new)) {
            return 'Choose a password different from the current one.';
        }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $this->updateHash($username, $hash, false);
        session_regenerate_id(true);
        $_SESSION['v7_must_change'] = false;
        $_SESSION['v7_fp'] = self::fingerprint($hash);
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

    private static function fingerprint(string $hash): string
    {
        return substr(hash('sha256', $hash), 0, 32);
    }

    private function currentHash(string $user, string $source): ?string
    {
        if ($source === 'config') {
            $users = $this->config->get('users', []);
            return is_string($users[$user] ?? null) ? $users[$user] : null;
        }
        $row = $this->findDbUser($user);
        return $row === null ? null : (string) $row['password_hash'];
    }

    private function isThrottled(string $username, string $ip): bool
    {
        $maxPair = (int) $this->config->get('security.max_login_attempts', 5);
        if ((int) ($_SESSION['v7_lock_until'] ?? 0) > time()) {
            return true;
        }
        try {
            $db = $this->db();
            if ($db === null) {
                return false;
            }
            $st = $db->prepare('SELECT
                    SUM(username = ? AND ip = ?) AS pair_fails,
                    SUM(ip = ?) AS ip_fails,
                    SUM(username = ?) AS user_fails
                FROM login_attempts WHERE attempted_at > UTC_TIMESTAMP() - INTERVAL ? SECOND');
            $st->execute([$username, $ip, $ip, $username, self::LOCK_WINDOW]);
            $r = $st->fetch(PDO::FETCH_ASSOC) ?: [];
            return (int) ($r['pair_fails'] ?? 0) >= $maxPair
                || (int) ($r['ip_fails'] ?? 0) >= self::MAX_PER_IP
                || (int) ($r['user_fails'] ?? 0) >= self::MAX_PER_USER;
        } catch (\Throwable $e) {
            error_log('[V7-Shield] Login throttle unavailable: ' . $e->getMessage());
            return false;
        }
    }

    private function recordFailure(string $username, string $ip): void
    {
        $maxPair = (int) $this->config->get('security.max_login_attempts', 5);
        $_SESSION['v7_fails'] = (int) ($_SESSION['v7_fails'] ?? 0) + 1;
        if ($_SESSION['v7_fails'] >= $maxPair) {
            $_SESSION['v7_lock_until'] = time() + self::LOCK_WINDOW;
            $_SESSION['v7_fails'] = 0;
        }
        try {
            $db = $this->db();
            if ($db === null) {
                return;
            }
            $db->prepare('INSERT INTO login_attempts (username, ip, attempted_at) VALUES (?, ?, UTC_TIMESTAMP())')->execute([$username, $ip]);
            if (random_int(1, 50) === 1) {
                $db->exec('DELETE FROM login_attempts WHERE attempted_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
            }
        } catch (\Throwable $e) {
            error_log('[V7-Shield] Could not record login failure: ' . $e->getMessage());
        }
    }

    private function clearFailures(string $username, string $ip): void
    {
        unset($_SESSION['v7_fails'], $_SESSION['v7_lock_until']);
        try {
            $this->db()?->prepare('DELETE FROM login_attempts WHERE username = ? AND ip = ?')->execute([$username, $ip]);
        } catch (\Throwable) {
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
