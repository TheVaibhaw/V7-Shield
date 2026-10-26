<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('export');
page_header('Export Center', 'Download data sets for spreadsheets, SIEM tools or archiving.');
echo demo_notice('Exports are generated in your browser from the sample data on this page.');
$sets = [
    ['findings', 'Threat detections', 'bug', $demo['findings'], ['id', 'severity', 'title', 'resource', 'project', 'category', 'status']],
    ['scans', 'Scan history', 'clock-history', $demo['scans'], ['name', 'project', 'type', 'status', 'files', 'findings', 'started']],
    ['events', 'Security events', 'broadcast', $demo['events'], ['time', 'category', 'severity', 'project', 'title']],
    ['changes', 'File changes', 'file-diff', $demo['integrity']['changes'], ['change', 'file', 'project', 'when']],
];
?>
<div class="grid grid-2">
  <?php foreach ($sets as [$id, $title, $ico, $rows, $cols]): ?>
    <section class="card">
      <header class="card-head"><h2><?= icon($ico) ?> <?= e($title) ?></h2><span class="muted"><?= count($rows) ?> rows</span></header>
      <div class="card-body">
        <p class="muted">Columns: <?php foreach ($cols as $c): ?><span class="tag"><?= e($c) ?></span><?php endforeach; ?></p>
        <table id="export-<?= e($id) ?>" hidden><thead><tr><?php foreach ($cols as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></thead><tbody>
          <?php foreach ($rows as $r): ?><tr><?php foreach ($cols as $c): ?><td><?= e($r[$c] ?? '') ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        </tbody></table>
        <div class="page-actions"><?= export_buttons('export-' . $id) ?></div>
      </div>
    </section>
  <?php endforeach; ?>
</div>
<?php card_open('Scheduled exports & integrations', 'plug'); check_list([
    ['Syslog / SIEM forwarding', 'Pending', 'Planned: forward events in CEF/JSON via backend.'],
    ['Webhook on Critical finding', 'Pending', 'Planned: configurable in Settings › Integrations.'],
    ['Weekly CSV to email', 'Pending', 'Requires backend mailer.'],
]); card_close(); render_footer();
