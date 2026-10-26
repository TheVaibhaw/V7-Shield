<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

const ROOT = __DIR__;
const SCHEMA_FILE = ROOT . '/backend/database/schema.sql';
const TABLES = ['scan_jobs', 'scan_findings', 'scan_skips', 'users'];
define('CONFIG_FILE', getenv('V7SHIELD_CONFIG') ?: ROOT . '/backend/config/config.local.php');

$opts = getopt('', ['port:', 'host:', 'setup-only', 'help']);
if (isset($opts['help'])) {
    echo "Usage: php start.php [--port=8000] [--host=127.0.0.1] [--setup-only]\n";
    exit(0);
}
$host = (string) ($opts['host'] ?? '127.0.0.1');
$port = (int) ($opts['port'] ?? 8000);
if (!filter_var($host, FILTER_VALIDATE_IP) && $host !== 'localhost') {
    fail('--host must be an IP address or localhost.');
}
if ($port < 1024 || $port > 65535) {
    fail('--port must be between 1024 and 65535.');
}

function say(string $msg): void { fwrite(STDOUT, $msg . PHP_EOL); }
function step(string $msg): void { say("\n\033[36m▸ $msg\033[0m"); }
function ok(string $msg): void { say("  \033[32m✓\033[0m $msg"); }
function warn(string $msg): void { say("  \033[33m!\033[0m $msg"); }
function fail(string $msg): never { fwrite(STDERR, "\n\033[31m✗ $msg\033[0m\n"); exit(1); }

function interactive(): bool
{
    return function_exists('stream_isatty') ? stream_isatty(STDIN) : true;
}

function ask(string $question, string $default = '', bool $secret = false): string
{
    if (!interactive()) {
        return $default;
    }
    fwrite(STDOUT, "  $question" . ($default !== '' && !$secret ? " [$default]" : '') . ': ');
    $unix = DIRECTORY_SEPARATOR === '/';
    if ($secret && $unix) {
        shell_exec('stty -echo 2>/dev/null');
    }
    $answer = fgets(STDIN);
    if ($secret && $unix) {
        shell_exec('stty echo 2>/dev/null');
        fwrite(STDOUT, PHP_EOL);
    }
    $answer = $answer === false ? '' : trim($answer);
    return $answer === '' ? $default : $answer;
}

function confirm(string $question, bool $default = true): bool
{
    $a = strtolower(ask($question . ($default ? ' (Y/n)' : ' (y/N)')));
    return $a === '' ? $default : in_array($a, ['y', 'yes'], true);
}

function valid_identifier(string $s): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_]{1,64}$/', $s);
}

/** @return array{0:?PDO,1:?string} connection and error message */
function connect(string $dsn, string $user, string $pass): array
{
    try {
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        return [$pdo, null];
    } catch (PDOException $e) {
        return [null, $e->getMessage()];
    }
}

function parse_dsn(string $dsn): array
{
    $parts = [];
    foreach (explode(';', preg_replace('/^mysql:/', '', $dsn)) as $kv) {
        if (str_contains($kv, '=')) {
            [$k, $v] = explode('=', $kv, 2);
            $parts[trim($k)] = trim($v);
        }
    }
    return $parts;
}

function server_dsn(array $p): string
{
    return isset($p['unix_socket'])
        ? 'mysql:unix_socket=' . $p['unix_socket'] . ';charset=utf8mb4'
        : 'mysql:host=' . ($p['host'] ?? '127.0.0.1') . ';port=' . ($p['port'] ?? '3306') . ';charset=utf8mb4';
}

function missing_tables(PDO $pdo): array
{
    $have = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    return array_values(array_diff(TABLES, $have));
}

function apply_schema(PDO $pdo): void
{
    $sql = (string) file_get_contents(SCHEMA_FILE);
    $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
        $pdo->exec($statement);
    }
}

step('Checking requirements');
if (PHP_VERSION_ID < 80200) {
    fail('PHP 8.2 or newer is required (found ' . PHP_VERSION . ').');
}
ok('PHP ' . PHP_VERSION);
foreach (['pdo_mysql', 'mbstring'] as $ext) {
    extension_loaded($ext) ? ok("extension $ext") : fail("PHP extension '$ext' is missing (e.g. sudo apt install php-mysql php-mbstring).");
}
foreach ([SCHEMA_FILE, ROOT . '/frontend/index.php', ROOT . '/backend/bootstrap.php'] as $f) {
    is_file($f) || fail('Missing file: ' . substr($f, strlen(ROOT) + 1));
}

step('Configuration');
$dbDefaults = ['host' => 'localhost', 'port' => '3306', 'name' => 'v7shield', 'user' => 'v7shield_app'];

if (!is_file(CONFIG_FILE)) {
    interactive() || fail('backend/config/config.local.php is missing. Run this command in a terminal to create it.');
    say('  No config found — creating backend/config/config.local.php');
    $dbHost = ask('MySQL host', $dbDefaults['host']);
    $dbPort = ask('MySQL port', $dbDefaults['port']);
    $dbName = ask('Database name', $dbDefaults['name']);
    $dbUser = ask('Database user for V7-Shield', $dbDefaults['user']);
    $dbPass = ask('Password for that user (new or existing; input hidden)', '', true);
    if (!valid_identifier($dbName) || !valid_identifier($dbUser)) {
        fail('Database name and user may only contain letters, digits and underscores.');
    }
    if ($dbPass === '') {
        $dbPass = bin2hex(random_bytes(12));
        ok('Generated a random database password (stored only in config.local.php)');
    }
    $targets = [];
    $dir = ask('Directory to scan (absolute path, blank to skip)');
    if ($dir !== '') {
        $real = realpath($dir);
        if ($real === false || !is_dir($real)) {
            warn("'$dir' is not a directory — skipped. Add targets later in config.local.php.");
        } else {
            $targets['my-site'] = ['label' => basename($real), 'path' => $real];
        }
    }
    $config = [
        'database' => [
            'dsn' => "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
            'username' => $dbUser,
            'password' => $dbPass,
        ],
        'users' => [],
        'targets' => $targets,
    ];
    $php = "<?php\n// Local settings and secrets. Git-ignored — never commit this file.\n// Created by start.php on " . gmdate('Y-m-d H:i') . " UTC.\ndeclare(strict_types=1);\n\nreturn "
        . var_export($config, true) . ";\n";
    file_put_contents(CONFIG_FILE, $php, LOCK_EX) !== false || fail('Could not write config.local.php.');
    @chmod(CONFIG_FILE, 0600);
    ok('Created backend/config/config.local.php (permissions 600)');
} else {
    ok('Using existing backend/config/config.local.php');
    $raw = (string) file_get_contents(CONFIG_FILE);
    if (str_contains($raw, 'CHANGE_ME_DB_USER') || str_contains($raw, 'CHANGE_ME_DB_PASSWORD')) {
        interactive() || fail('config.local.php still has CHANGE_ME placeholders. Edit it or run this command in a terminal.');
        say('  Database credentials are still placeholders — let\'s fill them in.');
        $dbUser = ask('Database user for V7-Shield', $dbDefaults['user']);
        $dbPass = ask('Password for that user (new or existing; input hidden)', '', true);
        valid_identifier($dbUser) || fail('Database user may only contain letters, digits and underscores.');
        if ($dbPass === '') {
            $dbPass = bin2hex(random_bytes(12));
            ok('Generated a random database password');
        }
        copy(CONFIG_FILE, CONFIG_FILE . '.bak');
        @chmod(CONFIG_FILE . '.bak', 0600);
        $raw = str_replace(["'CHANGE_ME_DB_USER'", "'CHANGE_ME_DB_PASSWORD'"], [var_export($dbUser, true), var_export($dbPass, true)], $raw);
        file_put_contents(CONFIG_FILE, $raw, LOCK_EX);
        ok('Filled in credentials (previous file saved as config.local.php.bak)');
    }
}

require ROOT . '/backend/bootstrap.php';
$cfg = V7Shield\config();
$db = $cfg->get('database');
$dsnParts = parse_dsn((string) $db['dsn']);
$dbName = (string) ($dsnParts['dbname'] ?? '');
$dbUser = (string) $db['username'];
valid_identifier($dbName) || fail('Could not read a valid dbname from database.dsn in config.local.php.');

step('Database');
[$pdo, $err] = connect((string) $db['dsn'], $dbUser, (string) $db['password']);
$admin = null;
$adminPdo = function () use (&$admin, $dsnParts): PDO {
    if ($admin instanceof PDO) {
        return $admin;
    }
    interactive() || fail('MySQL admin access is needed for setup. Run this command in a terminal.');
    say('  A MySQL account that can create databases/users is needed once (it is not saved).');
    for ($try = 0; $try < 3; $try++) {
        $u = ask('MySQL admin user', 'root');
        $p = ask("Password for '$u' (input hidden)", '', true);
        [$admin, $e] = connect(server_dsn($dsnParts), $u, $p);
        if ($admin) {
            return $admin;
        }
        warn('Login failed: ' . preg_replace('/\s+/', ' ', (string) $e));
    }
    fail("Could not log in as a MySQL admin. On Ubuntu, root often uses socket auth; create an admin with:\n"
        . "  sudo mysql -e \"CREATE USER 'dbadmin'@'localhost' IDENTIFIED BY '...'; GRANT ALL ON *.* TO 'dbadmin'@'localhost' WITH GRANT OPTION;\"");
};

if ($pdo === null) {
    warn('Cannot connect as ' . $dbUser . ': ' . preg_replace('/\s+/', ' ', (string) $err));
    if (!confirm('Create the database and user now using a MySQL admin account?')) {
        fail('Database not reachable. Fix database settings in backend/config/config.local.php and run again.');
    }
    $a = $adminPdo();
    $a->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    foreach (['localhost', '127.0.0.1'] as $h) {
        $acct = $a->quote($dbUser) . '@' . $a->quote($h);
        $a->exec("CREATE USER IF NOT EXISTS $acct IDENTIFIED BY " . $a->quote((string) $db['password']));
        $a->exec("ALTER USER $acct IDENTIFIED BY " . $a->quote((string) $db['password']));
        $a->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON `$dbName`.* TO $acct");
    }
    ok("Database `$dbName` and user '$dbUser' ready (least privilege: SELECT, INSERT, UPDATE, DELETE)");
    [$pdo, $err] = connect((string) $db['dsn'], $dbUser, (string) $db['password']);
    $pdo || fail('Still cannot connect: ' . $err);
}
ok("Connected to `$dbName` as $dbUser");

$missing = missing_tables($pdo);
if ($missing) {
    say('  Missing tables: ' . implode(', ', $missing));
    try {
        apply_schema($pdo);
    } catch (PDOException) {
        $a = $adminPdo();
        $a->exec("USE `$dbName`");
        apply_schema($a);
    }
    $missing = missing_tables($pdo);
    $missing && fail('Schema could not be applied; still missing: ' . implode(', ', $missing));
    ok('Schema installed (includes default admin user)');
} else {
    ok('All tables present');
}

$admins = $pdo->query("SELECT must_change_password FROM users WHERE username = 'admin'")->fetchColumn();
$admin = null;

step('Ready');
$targets = $cfg->targetLabels();
$targets ? ok(count($targets) . ' scan target(s): ' . implode(', ', array_keys($targets)))
         : warn("No scan targets yet. Add one to 'targets' in backend/config/config.local.php to start scanning.");
if ($admins !== false && (int) $admins === 1) {
    ok('Default login: admin / Admin@12345 (you will be asked to change it)');
} elseif ($admins !== false) {
    ok("Login with user 'admin' and the password you set");
}

if (isset($opts['setup-only'])) {
    say("\nSetup complete. Start later with: php start.php");
    exit(0);
}

$probe = @fsockopen($host === 'localhost' ? '127.0.0.1' : $host, $port, $errno, $errstr, 0.5);
if ($probe) {
    fclose($probe);
    fail("Port $port is already in use. Stop the other server or run: php start.php --port=" . ($port + 1));
}

$url = "http://$host:$port/";
say("\n\033[32mV7-Shield is running at $url\033[0m  (Ctrl+C to stop)\n");
$cmd = escapeshellarg(PHP_BINARY) . ' -S ' . escapeshellarg("$host:$port") . ' -t ' . escapeshellarg(ROOT . '/frontend');
if (DIRECTORY_SEPARATOR === '/') {
    putenv('PHP_CLI_SERVER_WORKERS=4');
}
passthru($cmd, $code);
exit($code);
