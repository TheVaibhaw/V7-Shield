<?php defined('APP_ROOT') || exit; /** @var array $item @var string $pageTitle */ ?>
<nav class="crumbs" aria-label="Breadcrumb">
  <ol>
    <li><a href="<?= e(url('dashboard.php')) ?>"><?= icon('house') ?><span class="sr-only">Home</span></a></li>
    <li><?= e($item['group']) ?></li>
    <li aria-current="page"><?= e($pageTitle) ?></li>
  </ol>
</nav>
