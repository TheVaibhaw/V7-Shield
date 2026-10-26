<?php defined('APP_ROOT') || exit; /** @var array $config @var array $item @var string $pageTitle @var V7Shield\Security\Auth $auth */
$user = (string) $auth->user(); ?>
<header class="topbar">
  <button class="icon-btn menu-btn" type="button" data-drawer-open aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><?= icon('list') ?></button>
  <div class="tb-title">
    <?php require APP_ROOT . '/includes/breadcrumbs.php'; ?>
    <strong><?= e($pageTitle) ?></strong>
  </div>
  <div class="tb-actions">
    <button class="icon-btn" type="button" data-theme-toggle aria-label="Toggle light/dark theme"><?= icon('moon-stars', 'theme-dark') ?><?= icon('sun', 'theme-light') ?></button>
    <div class="dropdown">
      <button class="avatar-btn" type="button" data-dropdown aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
        <span class="avatar"><?= e(strtoupper(substr($user, 0, 2))) ?></span><span class="avatar-name"><?= e($user) ?></span><?= icon('chevron-down') ?>
      </button>
      <div class="dropdown-menu" role="menu">
        <div class="dd-head"><span><strong><?= e($user) ?></strong><small class="muted">Administrator</small></span></div>
        <a role="menuitem" href="<?= e(page_url('settings')) ?>"><?= icon('gear') ?> Settings</a>
        <form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button role="menuitem" type="submit" class="dd-btn"><?= icon('box-arrow-right') ?> Sign out</button></form>
      </div>
    </div>
    <a class="btn btn-primary tb-scan" href="<?= e(page_url('scans')) ?>"><?= icon('play-fill') ?><span>Start Scan</span></a>
  </div>
</header>
