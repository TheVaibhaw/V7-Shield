<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use V7Shield\Service\ScanService;

$scanId = int_param($_GET, 'scan_id', 0, 1);
$page = int_param($_GET, 'page', 1, 1, 100000);
$filters = ScanService::cleanFilters($_GET);

[$data, $error] = try_backend(function (ScanService $svc) use (&$scanId, $filters, $page) {
    $recent = $svc->history(1, 'completed', 50)['items'];
    if ($scanId === 0 && $recent) {
        $scanId = $recent[0]['id'];
    }
    return [
        'recent' => $recent,
        'job' => $scanId ? $svc->status($scanId) : null,
        'list' => $scanId ? $svc->findings($scanId, $filters, $page) : null,
    ];
});

render_header('findings');
page_header('Malware Findings', 'Suspicious indicators reported by the rule engine. A finding is not proof of malware — verify each one.');

if ($error) {
    echo notice('danger', e($error), 'exclamation-octagon');
    render_footer();
    return;
}
if (!$data['job']) {
    echo '<section class="card"><div class="card-body">' . empty_state('search', 'No completed scans yet.', '<a class="btn btn-primary" href="' . e(page_url('scans')) . '">' . icon('play-fill') . ' Start a scan</a>') . '</div></section>';
    render_footer();
    return;
}
$job = $data['job'];
$list = $data['list'];
$query = array_filter(['scan_id' => $job['id']] + $filters);
?>
<?php card_open('Scan #' . $job['id'] . ' · ' . $job['target_label'], 'bug', '<a class="link-arrow" href="' . e(page_url('scan', ['id' => $job['id']])) . '">Scan details ' . icon('arrow-right') . '</a>'); ?>
  <form method="get" class="filter-bar">
    <div class="field"><label for="f-scan">Scan</label>
      <select id="f-scan" name="scan_id">
        <?php $listed = false; foreach ($data['recent'] as $r): $listed = $listed || $r['id'] === $job['id']; ?>
          <option value="<?= (int) $r['id'] ?>"<?= $r['id'] === $job['id'] ? ' selected' : '' ?>>#<?= (int) $r['id'] ?> · <?= e($r['target_label']) ?> · <?= e(substr((string) $r['finished_at'], 0, 16)) ?> UTC (<?= (int) $r['findings_count'] ?>)</option>
        <?php endforeach; ?>
        <?php if (!$listed): ?><option value="<?= (int) $job['id'] ?>" selected>#<?= (int) $job['id'] ?> · <?= e($job['target_label']) ?> (<?= e($job['status']) ?>)</option><?php endif; ?>
      </select>
    </div>
    <div class="field"><label for="f-sev">Severity</label>
      <select id="f-sev" name="severity"><option value="">All</option>
        <?php foreach (ScanService::SEVERITIES as $s): ?><option value="<?= e($s) ?>"<?= ($filters['severity'] ?? '') === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?>
      </select></div>
    <div class="field"><label for="f-cat">Category</label>
      <select id="f-cat" name="category"><option value="">All</option>
        <?php foreach (ScanService::CATEGORIES as $c): ?><option value="<?= e($c) ?>"<?= ($filters['category'] ?? '') === $c ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
      </select></div>
    <div class="field grow"><label for="f-q">Search file / rule</label><input id="f-q" type="search" name="q" maxlength="100" value="<?= e($filters['q'] ?? '') ?>" placeholder="e.g. uploads/ or V7-EXEC"></div>
    <button class="btn btn-primary" type="submit"><?= icon('funnel') ?> Apply</button>
    <?php if ($filters): ?><a class="btn btn-ghost" href="?scan_id=<?= (int) $job['id'] ?>">Reset</a><?php endif; ?>
  </form>

  <?php if ($job['status'] !== 'completed'): ?>
    <?= notice($job['status'] === 'failed' ? 'danger' : 'info', 'This scan is <strong>' . e($job['status']) . '</strong>; results may be incomplete.') ?>
  <?php endif; ?>

  <?php if (!$list['items']): ?>
    <?= empty_state($filters ? 'funnel' : 'shield-check', $filters ? 'No findings match these filters.' : 'No rule matched in this scan. That does not guarantee the files are clean.') ?>
  <?php else: ?>
    <div class="table-wrap"><table class="data-table" id="findings-table" data-per-page="100">
      <thead><tr>
        <th scope="col" data-sort="sev" tabindex="0">Severity</th><th scope="col" data-sort="rule" tabindex="0">Rule</th>
        <th scope="col" data-sort="file" tabindex="0">File</th><th scope="col" data-sort="line" tabindex="0">Line</th>
        <th scope="col" data-sort="conf" tabindex="0">Confidence</th><th scope="col">Evidence</th><th scope="col"></th>
      </tr></thead><tbody>
      <?php foreach ($list['items'] as $f): ?>
        <tr>
          <td data-label="Severity" data-value="<?= V7Shield\Scanner\Scanner::SEVERITY_RANK[$f['severity']] ?>"><?= sev_badge($f['severity']) ?></td>
          <td data-label="Rule" data-value="<?= e($f['rule_id']) ?>"><strong><?= e($f['rule_name']) ?></strong><small class="muted"><?= e($f['rule_id']) ?> · <?= e($f['category']) ?></small></td>
          <td data-label="File" data-value="<?= e($f['file_path']) ?>"><code class="trunc" title="<?= e($f['file_path']) ?>"><?= e($f['file_path']) ?></code></td>
          <td data-label="Line" data-value="<?= (int) $f['line'] ?>"><?= (int) $f['line'] ?></td>
          <td data-label="Confidence" data-value="<?= (int) $f['confidence'] ?>"><?= (int) $f['confidence'] ?>%</td>
          <td data-label="Evidence" data-value=""><span class="evidence-inline" title="<?= e($f['evidence']) ?>"><?= e($f['evidence']) ?></span></td>
          <td data-label="" data-value=""><?= detail_button($f['rule_name'], [
              'Classification' => 'Suspicious (heuristic match, not confirmed malware)',
              'Rule' => $f['rule_id'] . ' — ' . $f['rule_name'], 'Category' => $f['category'],
              'Severity' => ucfirst($f['severity']), 'Confidence' => $f['confidence'] . '%',
              'File (relative to target)' => $f['file_path'], 'Line' => $f['line'], 'Matches in file' => $f['match_count'],
              'File size' => fmt_bytes($f['file_size']), 'SHA-256' => $f['file_sha256'],
              'Evidence (truncated excerpt)' => $f['evidence'], 'Why it matters' => $f['description'],
              'Known false positives' => $f['false_positives'],
              'Next steps' => 'Compare the file with a trusted copy (vendor package, backup or version control) and check access logs before removing anything.',
          ]) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    <?= pager($list['page'], $list['pages'], $list['total'], $query) ?>
  <?php endif; ?>
<?php card_close(); render_footer();
