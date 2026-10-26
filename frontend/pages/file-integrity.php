<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('file-integrity');
$i = $demo['integrity']['summary'];
page_header('File Integrity', 'Compare current file hashes against a trusted baseline to spot unexpected changes.',
    '<button type="button" class="btn btn-ghost" data-confirm="Re-create the baseline from the current files? Only do this when you trust the current state. (Demo — nothing is stored.)" data-confirm-ok="Create baseline (demo)" data-demo-toast="Baseline creation simulated (Demo Mode).">' . icon('bookmark-plus') . ' Create baseline</button>'
    . demo_action('Compare now', 'arrow-left-right', 'Integrity comparison requires the backend (Demo Mode).', 'btn-primary'));
echo demo_notice();
?>
<div class="notice notice-info"><?= icon('info-circle') ?><span>A changed file is <strong>not</strong> proof of malware. Plugin updates, deployments and cache regeneration all change hashes. Review changes against your deployment history.</span></div>
<div class="stat-grid">
  <?= stat_card('file-earmark-plus', 'Files added', (string) $i['added'], 'green', '', 'New files not present in the baseline.') ?>
  <?= stat_card('file-earmark-diff', 'Files modified', (string) $i['modified'], 'amber', '', 'Files whose content changed.') ?>
  <?= stat_card('file-earmark-minus', 'Files deleted', (string) $i['deleted'], 'red', '', 'Baseline files that no longer exist.') ?>
  <?= stat_card('hash', 'Changed hashes', (string) $i['hash_changed'], 'cyan', '', 'SHA-256 mismatches (modified + replaced).') ?>
</div>
<div class="grid grid-2">
  <?php card_open('Baseline', 'bookmark-check'); ?>
    <dl class="kv">
      <div><dt>Status</dt><dd><?= status_badge($i['baseline']) ?></dd></div>
      <div><dt>Created</dt><dd><?= e($i['baseline_date']) ?></dd></div>
      <div><dt>Last comparison</dt><dd><?= e($i['last_check']) ?></dd></div>
      <div><dt>Algorithm</dt><dd>SHA-256</dd></div>
      <div><dt>Files in baseline</dt><dd>58,233</dd></div>
    </dl>
  <?php card_close(); ?>
  <?php card_open('Baselines per project', 'collection'); ?>
    <?php check_list(array_map(fn($p) => [$p['name'], $p['status'] === 'At Risk' ? 'Drifted' : 'Verified', 'Baseline from 2026-10-01'], array_slice($demo['projects'], 0, 5))); ?>
  <?php card_close(); ?>
</div>
<?php card_open('Change history', 'file-diff', export_buttons('integrity-table'));
data_table('integrity-table', change_columns(), $demo['integrity']['changes'], ['change' => ['Added', 'Modified', 'Deleted']]);
card_close();
render_footer();
