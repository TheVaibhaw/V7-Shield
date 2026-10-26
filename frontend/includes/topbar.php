<?php defined('APP_ROOT') || exit; /** @var array $config @var array $demo @var array $item @var string $pageTitle */
$unread = count(array_filter($demo['notifications'], fn($n) => !$n['read'])); ?>
<header class="topbar">
  <button class="icon-btn menu-btn" type="button" data-drawer-open aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><?= icon('list') ?></button>
  <div class="tb-title">
    <?php require APP_ROOT . '/includes/breadcrumbs.php'; ?>
    <strong><?= e($pageTitle) ?></strong>
  </div>
  <form class="tb-search" role="search" data-global-search>
    <?= icon('search') ?>
    <label class="sr-only" for="global-search">Search pages</label>
    <input id="global-search" type="search" placeholder="Search pages, findings…" autocomplete="off" list="nav-pages">
    <kbd>/</kbd>
    <datalist id="nav-pages">
      <?php foreach ($config['nav'] as $items) foreach ($items as [$t, , $p]): ?><option value="<?= e($t) ?>" data-url="<?= e(url($p)) ?>"></option><?php endforeach; ?>
    </datalist>
  </form>
  <div class="tb-actions">
    <button class="icon-btn search-toggle" type="button" data-search-toggle aria-label="Search"><?= icon('search') ?></button>
    <label class="select-field tb-project"><span class="sr-only">Project</span>
      <select data-project-select>
        <option value="">All projects</option>
        <?php foreach ($demo['projects'] as $p): ?><option value="<?= e($p['id']) ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <button class="icon-btn" type="button" data-theme-toggle aria-label="Toggle light/dark theme"><?= icon('moon-stars', 'theme-dark') ?><?= icon('sun', 'theme-light') ?></button>
    <div class="dropdown">
      <button class="icon-btn" type="button" data-dropdown aria-haspopup="true" aria-expanded="false" aria-label="Notifications, <?= $unread ?> unread">
        <?= icon('bell') ?><?php if ($unread): ?><span class="badge-dot" data-unread-badge><?= $unread ?></span><?php endif; ?>
      </button>
      <div class="dropdown-menu dd-notify" role="menu">
        <div class="dd-head"><strong>Notifications</strong><button type="button" class="link-btn" data-mark-all-read>Mark all read</button></div>
        <?php foreach (array_slice($demo['notifications'], 0, 4) as $n): ?>
          <a role="menuitem" class="dd-note<?= $n['read'] ? '' : ' unread' ?>" href="<?= e(page_url('notifications')) ?>">
            <span class="dot sev-<?= severity_class($n['severity']) ?>"></span>
            <span><strong><?= e($n['title']) ?></strong><small><?= e($n['time']) ?></small></span>
          </a>
        <?php endforeach; ?>
        <a class="dd-foot" href="<?= e(page_url('notifications')) ?>">View all notifications</a>
      </div>
    </div>
    <div class="dropdown">
      <button class="avatar-btn" type="button" data-dropdown aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
        <span class="avatar">DA</span><span class="avatar-name"><?= e($demo['user']['name']) ?></span><?= icon('chevron-down') ?>
      </button>
      <div class="dropdown-menu" role="menu">
        <div class="dd-head"><span><strong><?= e($demo['user']['name']) ?></strong><small class="muted"><?= e($demo['user']['email']) ?></small></span></div>
        <a role="menuitem" href="<?= e(page_url('settings')) ?>#account"><?= icon('person') ?> Profile</a>
        <a role="menuitem" href="<?= e(page_url('settings')) ?>"><?= icon('gear') ?> Settings</a>
        <a role="menuitem" href="<?= e(page_url('help')) ?>"><?= icon('question-circle') ?> Help</a>
        <button role="menuitem" type="button" data-demo-toast="Sign-out requires backend authentication (not implemented in Demo Mode)."><?= icon('box-arrow-right') ?> Sign out</button>
      </div>
    </div>
    <a class="btn btn-primary tb-scan" href="<?= e(page_url('scans')) ?>"><?= icon('play-fill') ?><span>Start Scan</span></a>
  </div>
</header>
