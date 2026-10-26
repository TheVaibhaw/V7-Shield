<?php defined('APP_ROOT') || exit; /** @var array $config @var string $pageKey */ ?>
<aside class="sidebar" id="sidebar" aria-label="Main navigation">
  <div class="sb-brand">
    <a href="<?= e(url('dashboard.php')) ?>" class="brand">
      <img src="<?= e(asset('images/v7-shield-logo.svg')) ?>" alt="" width="36" height="36">
      <span class="brand-text"><strong>V7<span>-Shield</span></strong><small><?= e($config['tagline']) ?></small></span>
    </a>
    <button class="icon-btn sb-close" type="button" data-drawer-close aria-label="Close menu"><?= icon('x-lg') ?></button>
  </div>
  <nav class="sb-nav">
    <?php foreach ($config['nav'] as $group => $items):
        $gid = 'nav-' . preg_replace('/[^a-z]+/', '-', strtolower($group));
        $hasActive = isset($items[$pageKey]); ?>
      <div class="sb-group<?= $hasActive ? ' has-active' : '' ?>">
        <button type="button" class="sb-group-btn" aria-expanded="true" aria-controls="<?= e($gid) ?>">
          <span><?= e($group) ?></span><?= icon('chevron-down') ?>
        </button>
        <ul id="<?= e($gid) ?>">
          <?php foreach ($items as $key => [$title, $ico, $path]): $active = $key === $pageKey; ?>
            <li><a href="<?= e(url($path)) ?>" class="sb-link<?= $active ? ' active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?> data-tip-right="<?= e($title) ?>">
              <?= icon($ico) ?><span><?= e($title) ?></span>
              <?php if ($key === 'findings'): ?><em class="sb-count">16</em><?php elseif ($key === 'active-scans'): ?><em class="sb-count live">2</em><?php endif; ?>
            </a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  </nav>
  <div class="sb-foot">
    <div class="sb-version">
      <?= icon('shield-shaded') ?>
      <div><strong><?= e($config['name']) ?> v<?= e($config['version']) ?></strong><small><?= $config['demo_mode'] ? 'Demo Mode · frontend only' : 'Connected' ?></small></div>
    </div>
    <button type="button" class="sb-collapse-btn" data-sidebar-toggle aria-label="Collapse sidebar"><?= icon('layout-sidebar-inset') ?><span>Collapse</span></button>
  </div>
</aside>
<div class="drawer-backdrop" data-drawer-close hidden></div>
