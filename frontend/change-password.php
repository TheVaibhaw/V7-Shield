<?php
declare(strict_types=1);

const V7_PASSWORD_PAGE = true;
require __DIR__ . '/includes/bootstrap.php';

use V7Shield\Security\Auth;
use V7Shield\Security\Csrf;

$error = null;
$forced = $auth->mustChangePassword();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::valid(str_param($_POST, 'csrf'))) {
        $error = 'Your session expired. Please try again.';
    } else {
        $error = $auth->changePassword((string) ($_POST['current'] ?? ''), (string) ($_POST['new'] ?? ''), (string) ($_POST['confirm'] ?? ''));
        if ($error === null) {
            header('Location: ' . url('dashboard.php?password=changed'));
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
<title>Change password · <?= e($config['name']) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('images/v7-shield-logo.svg')) ?>">
<script>try{var t=localStorage.getItem('v7-theme');if(t)document.documentElement.dataset.theme=t;}catch(e){}</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<main class="auth-wrap">
  <form class="card auth-card" method="post" autocomplete="off">
    <div class="brand">
      <img src="<?= e(asset('images/v7-shield-logo.svg')) ?>" alt="" width="44" height="44">
      <span class="brand-text"><strong>V7<span>-Shield</span></strong><small>Change password</small></span>
    </div>
    <?php if ($forced): ?>
      <?= notice('warning', '<strong>Default password in use.</strong> Choose a new password to continue.', 'shield-exclamation') ?>
    <?php endif; ?>
    <?php if (!$auth->isDatabaseUser()): ?>
      <?= notice('info', 'This account is defined in <code>backend/config/config.local.php</code>. Generate a new hash with <code>php backend/bin/hash-password.php</code> and replace it there.') ?>
    <?php else: ?>
      <?php if ($error): ?><?= notice('danger', e($error), 'exclamation-octagon') ?><?php endif; ?>
      <?= csrf_field() ?>
      <p class="muted" style="margin-top:12px">Signed in as <strong><?= e($auth->user()) ?></strong></p>
      <div class="field" style="margin-top:12px"><label for="current">Current password</label><input id="current" name="current" type="password" required autocomplete="current-password" autofocus></div>
      <div class="field" style="margin-top:12px"><label for="new">New password</label><input id="new" name="new" type="password" required minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>" autocomplete="new-password"><small>At least <?= Auth::MIN_PASSWORD_LENGTH ?> characters.</small></div>
      <div class="field" style="margin-top:12px"><label for="confirm">Confirm new password</label><input id="confirm" name="confirm" type="password" required minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>" autocomplete="new-password"></div>
      <button class="btn btn-primary btn-lg" type="submit" style="width:100%;margin-top:18px"><?= icon('key') ?> Change password</button>
    <?php endif; ?>
  </form>
  <form method="post" action="<?= e(url('logout.php')) ?>" style="margin-top:-12px"><?= csrf_field() ?><button class="link-btn" type="submit">Sign out</button></form>
</main>
</body>
</html>
