<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
header('Location: ' . url('dashboard.php'), true, 302);
exit;
