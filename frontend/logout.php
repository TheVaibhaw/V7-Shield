<?php
declare(strict_types=1);

const V7_PUBLIC = true;
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && V7Shield\Security\Csrf::valid(str_param($_POST, 'csrf'))) {
    $auth->logout();
}
header('Location: ' . url('login.php'));
exit;
