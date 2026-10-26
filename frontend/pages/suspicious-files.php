<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('suspicious-files');
page_header('Suspicious Files', 'Files matching heuristics that need a human decision. Suspicious does not mean malicious.', export_buttons('suspicious-table'));
echo demo_notice();
$rows = array_values(array_filter($demo['findings'], fn($r) => !in_array($r['category'], ['Account', 'Configuration', 'Persistence', 'Vulnerable component'], true)));
card_open('Files awaiting review', 'file-earmark-break');
data_table('suspicious-table', [
    'resource' => ['File', fn($r) => '<code class="trunc" title="' . e($r['resource']) . '">' . e($r['resource']) . '</code>'],
    'project' => ['Project'],
    'category' => ['Reason'],
    'severity' => ['Severity', fn($r) => sev_badge($r['severity'])],
    'confidence' => ['Confidence'],
    'rule' => ['Rule', fn($r) => '<span class="tag">' . e($r['rule']) . '</span>'],
    'status' => ['Review', fn($r) => status_badge($r['status'])],
    '_act' => ['', fn($r) => '<a class="btn btn-ghost btn-sm" href="' . e(page_url('malware-analysis')) . '">' . icon('search') . ' Analyze</a>'],
], $rows, ['severity' => ['Critical', 'High', 'Medium', 'Low'], 'status' => ['New', 'In Review', 'Resolved', 'False Positive']]);
card_close();
?>
<div class="grid grid-3">
  <?php card_open('Common reasons', 'question-diamond'); ?>
    <?php check_list([['Signature mismatch', 'Warning', 'File content does not match its extension.'], ['Obfuscation', 'Warning', 'Long encoded strings or packed code.'], ['Unexpected executable', 'Warning', 'Scripts in media/upload folders.']]); ?>
  <?php card_close(); ?>
  <?php card_open('Before you act', 'hand-index'); ?>
    <p class="muted">Minified JavaScript, licence-check code in commercial plugins and caching plugins often trigger heuristics. Verify with the vendor package before removing anything.</p>
  <?php card_close(); ?>
  <?php card_open('Reduce noise', 'funnel'); ?>
    <p class="muted">Add scoped exclusions in <a href="<?= e(page_url('settings')) ?>#exclusions">Settings › Exclusions</a> for files you have verified as legitimate.</p>
  <?php card_close(); ?>
</div>
<?php render_footer();
