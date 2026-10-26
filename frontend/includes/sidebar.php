<?php defined('APP_ROOT') || exit; /** @var array $config @var string $navKey */ ?>
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
        $hasActive = isset($items[$navKey]); ?>
      <div class="sb-group<?= $hasActive ? ' has-active' : '' ?>">
        <button type="button" class="sb-group-btn" aria-expanded="true" aria-controls="<?= e($gid) ?>">
          <span><?= e($group) ?></span><?= icon('chevron-down') ?>
        </button>
        <ul id="<?= e($gid) ?>">
          <?php foreach ($items as $key => [$title, $ico, $path]): $active = $key === $navKey; ?>
            <li><a href="<?= e(url($path)) ?>" class="sb-link<?= $active ? ' active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?> data-tip-right="<?= e($title) ?>">
              <?= icon($ico) ?><span><?= e($title) ?></span>
            </a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
    <?php if (!empty($config['coming_soon'])): ?>
      <div class="sb-group sb-soon">
        <button type="button" class="sb-group-btn" aria-expanded="true" aria-controls="nav-soon"><span>Coming soon</span><?= icon('chevron-down') ?></button>
        <ul id="nav-soon">
          <?php foreach ($config['coming_soon'] as $t): ?>
            <li><span class="sb-link disabled" aria-disabled="true" data-tip-right="<?= e($t) ?> (coming soon)"><?= icon('hourglass-split') ?><span><?= e($t) ?></span></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </nav>
  <div class="sb-foot">
    <div class="sb-version">
      <?= icon('shield-shaded') ?>
      <div><strong><?= e($config['name']) ?> v<?= e($config['version']) ?></strong><small>Read-only scanner · MVP</small></div>
    </div>
    <button type="button" class="sb-collapse-btn" data-sidebar-toggle aria-label="Collapse sidebar"><?= icon('layout-sidebar-inset') ?><span>Collapse</span></button>
  </div>
</aside>
<div class="drawer-backdrop" data-drawer-close hidden></div>
