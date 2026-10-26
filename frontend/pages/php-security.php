<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('php-security');
page_header('PHP Application Security', 'Heuristic analysis of custom PHP code and directories.', '<a class="btn btn-primary" href="' . e(page_url('scans')) . '?type=patterns">' . icon('play-fill') . ' Run pattern scan</a>');
echo demo_notice();
$cats = [
    ['Suspicious execution patterns', 'terminal', 3, 'Request input reaching shell or eval-style sinks.'],
    ['Possible webshell indicators', 'bug', 2, 'File managers, command runners, password-gated payloads.'],
    ['Code obfuscation', 'eye-slash', 4, 'Encoded blobs, string reversal, character-code assembly.'],
    ['Dangerous function patterns', 'exclamation-diamond', 6, 'exec, system, passthru, proc_open, assert with dynamic input.'],
    ['Unexpected executable files', 'file-earmark-x', 2, '.php/.phtml/.phar in media, cache or upload folders.'],
    ['Extension / signature mismatch', 'file-earmark-break', 1, 'File content does not match its declared extension.'],
];
?>
<div class="grid grid-3">
  <?php foreach ($cats as [$t, $ico, $n, $d]): ?>
    <section class="card"><div class="card-body">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px"><h3><?= icon($ico) ?> <?= e($t) ?></h3><span class="badge <?= $n > 3 ? 'sev-high' : 'sev-med' ?>"><?= (int) $n ?></span></div>
      <p class="muted" style="margin:8px 0 0;font-size:.82rem"><?= e($d) ?></p>
    </div></section>
  <?php endforeach; ?>
</div>
<?php card_open('Findings in PHP projects', 'filetype-php');
data_table('php-findings', finding_columns(), rows_where($demo['findings'], 'project', ['Legacy CRM', 'Billing API', 'Uploads Directory']), ['severity' => ['Critical', 'High', 'Medium', 'Low']]);
card_close(); ?>
<?php card_open('Detection rules overview', 'list-columns'); ?>
<div class="grid grid-2">
  <?php check_list([['V7-WS-* (webshell)', 'Active', '14 rules'], ['V7-OB-* (obfuscation)', 'Active', '9 rules'], ['V7-DF-* (dangerous functions)', 'Active', '22 rules']]); ?>
  <?php check_list([['V7-FS-* (file system)', 'Active', '11 rules'], ['V7-SM-* (signature mismatch)', 'Active', '6 rules'], ['Custom rules', 'Pending', 'Rule editor planned for backend phase']]); ?>
</div>
<?php card_close(); render_footer();
