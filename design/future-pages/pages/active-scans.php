<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('active-scans');
$active = rows_where($demo['scans'], 'status', ['Running', 'Queued']);
page_header('Active Scans', 'Scans currently running or waiting for a worker.', '<a class="btn btn-primary" href="' . e(page_url('scans')) . '">' . icon('plus-lg') . ' New scan</a>');
echo demo_notice('Progress below is a UI simulation driven by a timer. No scan processes are running.');
?>
<div class="scan-cards">
  <?php foreach ($active as $s): $running = $s['status'] === 'Running'; ?>
    <section class="card scan-card" data-sim-card data-sim-files="<?= (int) ($s['files'] / max(1, $s['progress']) * 100) ?>" data-sim-start="<?= (int) $s['progress'] ?>"<?= $running ? ' data-sim-auto' : '' ?>>
      <div class="scan-card-head">
        <div><h3><?= e($s['name']) ?></h3><small class="muted"><?= e($s['project']) ?> · <?= e($s['type']) ?> scan</small></div>
        <span data-sim-status><?= status_badge($s['status']) ?></span>
      </div>
      <?= progress_bar((int) $s['progress'], $running ? 'running' : '') ?>
      <div class="muted" style="font-size:.8rem">Phase: <strong data-sim-phase><?= e($s['phase']) ?></strong> · <span data-sim-pct><?= (int) $s['progress'] ?>%</span></div>
      <div class="scan-card-meta">
        <div><small>Elapsed</small><strong data-sim-elapsed><?= e($s['duration']) ?></strong></div>
        <div><small>Items</small><strong data-sim-files><?= e(number_format($s['files'])) ?></strong></div>
        <div><small>Findings</small><strong><?= (int) $s['findings'] ?></strong></div>
      </div>
      <div class="scan-log" aria-live="polite"><div>[sim] Started <?= e($s['started']) ?></div></div>
      <div class="scan-card-actions" style="margin-top:12px">
        <?php if ($running): ?>
          <button type="button" class="btn btn-ghost btn-sm" data-sim-action="pause"><?= icon('pause-fill') ?> Pause</button>
        <?php else: ?>
          <button type="button" class="btn btn-ghost btn-sm" data-sim-action="restart"><?= icon('play-fill') ?> Start now</button>
        <?php endif; ?>
        <button type="button" class="btn btn-ghost btn-sm" data-sim-action="cancel"><?= icon('x-circle') ?> Cancel</button>
        <?= detail_button($s['name'], $s, 'Details') ?>
      </div>
    </section>
  <?php endforeach; ?>
</div>

<?php card_open('Worker status', 'cpu'); ?>
<div class="grid grid-3">
  <?php check_list([['Worker #1', 'Active', 'Northwind Store — nightly full scan'], ['Worker #2', 'Active', 'Uploads quick check']]); ?>
  <?php check_list([['Queue length', 'Queued', '1 scan waiting'], ['Concurrency limit', 'Healthy', '2 parallel scans (Settings › Scan defaults)']]); ?>
  <?php check_list([['Backend connection', 'Pending', 'Not configured — Demo Mode'], ['Last heartbeat', 'Pending', 'Requires backend API']]); ?>
</div>
<?php card_close(); render_footer();
