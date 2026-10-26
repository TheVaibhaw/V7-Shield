<?php
/** Print a password hash for config.local.php `users`. Usage: php backend/bin/hash-password.php */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}
fwrite(STDOUT, 'Password: ');
shell_exec('stty -echo 2>/dev/null');
$pw = rtrim((string) fgets(STDIN), "\r\n");
shell_exec('stty echo 2>/dev/null');
fwrite(STDOUT, PHP_EOL);
if (strlen($pw) < 12) {
    fwrite(STDERR, "Use at least 12 characters.\n");
    exit(1);
}
echo password_hash($pw, PASSWORD_DEFAULT), PHP_EOL;
