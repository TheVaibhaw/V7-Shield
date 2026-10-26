<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use V7Shield\Service\ScanService;

$page = int_param($_GET, 'page', 1, 1, 100000);
$status = str_param($_GET, 'status');
$status = in_array($status, ScanService::STATUSES, true) ? $status : null;
[$h, $error] = try_backend(fn(ScanService $svc) => $svc->history($page, $status));

render_header('scan-history');
page_header('Scan History', 'All scans with their status and results.', '<a class="btn btn-primary" href="' . e(page_url('scans')) . '">' . icon('play-fill') . ' New scan</a>');
if ($error) {
    echo notice('danger', e($error), 'exclamation-octagon');
    render_footer();
    return;
}
card_open('Scans', 'clock-history');
?>
<form method="get" class="filter-bar">
  <div class="field"><label for="h-status">Status</label>
    <select id="h-status" name="status" onchange="this.form.submit()"><option value="">All</option>
      <?php foreach (ScanService::STATUSES as $s): ?><option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
    </select></div>
  <noscript><button class="btn btn-ghost" type="submit">Filter</button></noscript>
</form>
<?php if (!$h['items']): ?>
  <?= empty_state('clock-history', $status ? 'No scans with this status.' : 'No scans have been run yet.', '<a class="btn btn-primary" href="' . e(page_url('scans')) . '">' . icon('play-fill') . ' Start a scan</a>') ?>
<?php else: ?>
  <div class="table-wrap"><table class="data-table" id="history-table" data-per-page="100">
    <thead><tr><th scope="col" data-sort="id" tabindex="0">#</th><th scope="col" data-sort="t" tabindex="0">Target</th><th scope="col" data-sort="s" tabindex="0">Status</th>
      <th scope="col" data-sort="c" tabindex="0">Created</th><th scope="col">Duration</th><th scope="col" data-sort="f" tabindex="0">Files</th>
      <th scope="col" data-sort="n" tabindex="0">Findings</th><th scope="col">By</th><th scope="col"></th></tr></thead>
    <tbody>
    <?php foreach ($h['items'] as $j): ?>
      <tr>
        <td data-label="#" data-value="<?= $j['id'] ?>"><?= $j['id'] ?></td>
        <td data-label="Target" data-value="<?= e($j['target_label']) ?>"><strong><?= e($j['target_label']) ?></strong><small class="muted"><?= e($j['target_id']) ?></small></td>
        <td data-label="Status" data-value="<?= e($j['status']) ?>"><?= scan_status_label($j) ?></td>
        <td data-label="Created" data-value="<?= e($j['created_at']) ?>"><?= fmt_dt($j['created_at']) ?></td>
        <td data-label="Duration" data-value=""><?= e(fmt_duration($j['started_at'], $j['finished_at'])) ?></td>
        <td data-label="Files" data-value="<?= $j['files_scanned'] ?>"><?= e(number_format($j['files_scanned'])) ?><?php if ($j['files_skipped']): ?><small class="muted"><?= e(number_format($j['files_skipped'])) ?> skipped</small><?php endif; ?></td>
        <td data-label="Findings" data-value="<?= $j['findings_count'] ?>"><?= severity_chips($j) ?></td>
        <td data-label="By" data-value="<?= e($j['requested_by']) ?>"><?= e($j['requested_by']) ?></td>
        <td data-label="" data-value=""><a class="btn btn-ghost btn-sm" href="<?= e(page_url('scan', ['id' => $j['id']])) ?>"><?= icon('eye') ?> View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?= pager($h['page'], $h['pages'], $h['total'], array_filter(['status' => $status])) ?>
<?php endif;
card_close();
render_footer();
