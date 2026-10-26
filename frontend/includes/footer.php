<?php defined('APP_ROOT') || exit; /** @var array $config */ ?>
<footer class="app-footer">
  <span>&copy; <?= date('Y') ?> <?= e($config['name']) ?> · Open-source, read-only malware scanner · v<?= e($config['version']) ?></span>
  <span>Heuristic findings are <strong>suspicious</strong> indicators for review, not confirmed malware.</span>
</footer>
</div>
</div>

<aside class="detail-drawer" id="detail-drawer" role="dialog" aria-modal="true" aria-labelledby="detail-title" hidden>
  <header><h2 id="detail-title">Details</h2><button type="button" class="icon-btn" data-detail-close aria-label="Close details"><?= icon('x-lg') ?></button></header>
  <div class="detail-body"></div>
  <footer>
    <button type="button" class="btn btn-ghost" data-detail-close>Close</button>
  </footer>
</aside>
<div class="overlay" id="overlay" hidden></div>

<div class="modal" id="confirm-modal" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-text" hidden>
  <div class="modal-box">
    <div class="modal-icon"><?= icon('exclamation-triangle') ?></div>
    <h2 id="confirm-title">Are you sure?</h2>
    <p id="confirm-text"></p>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" data-confirm-cancel>Cancel</button>
      <button type="button" class="btn btn-danger" data-confirm-ok>Confirm</button>
    </div>
  </div>
</div>

<div class="toasts" id="toasts" aria-live="polite" aria-atomic="false"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/charts.js')) ?>" defer></script>
<script src="<?= e(asset('js/scans.js')) ?>" defer></script>
</body>
</html>
