<?php
declare(strict_types=1);

const V7_PUBLIC = true;
require __DIR__ . '/includes/bootstrap.php';

use V7Shield\Security\Csrf;

if ($auth->user() !== null) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::valid(str_param($_POST, 'csrf'))) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = V7Shield\Text::cut(trim((string) ($_POST['username'] ?? '')), 64);
        $password = (string) ($_POST['password'] ?? '');
        $error = $auth->attempt($username, $password);
        if ($error === null) {
            header('Location: ' . url('dashboard.php'));
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · <?= e($config['name']) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('images/v7-shield-logo.svg')) ?>">
<script>try{var t=localStorage.getItem('v7-theme');if(t)document.documentElement.dataset.theme=t;}catch(e){}</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<main class="auth-wrap">
  <form class="card auth-card" method="post" autocomplete="on">
    <div class="brand">
      <img src="<?= e(asset('images/v7-shield-logo.svg')) ?>" alt="" width="44" height="44">
      <span class="brand-text"><strong>V7<span>-Shield</span></strong><small><?= e($config['tagline']) ?></small></span>
    </div>
    <?php if (!$auth->hasUsers()): ?>
      <?= notice('danger', '<strong>No users configured.</strong> Add a user to <code>backend/config/config.local.php</code> — see the README (<code>php backend/bin/hash-password.php</code>).', 'person-x') ?>
    <?php endif; ?>
    <?php if ($error): ?><?= notice('danger', e($error), 'exclamation-octagon') ?><?php endif; ?>
    <?= csrf_field() ?>
    <div class="field" style="margin-top:14px"><label for="username">Username</label><input id="username" name="username" type="text" required maxlength="64" autocomplete="username" autofocus></div>
    <div class="field" style="margin-top:12px"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
    <button class="btn btn-primary btn-lg" type="submit" style="width:100%;margin-top:18px"><?= icon('box-arrow-in-right') ?> Sign in</button>
  </form>
</main>
</body>
</html>
