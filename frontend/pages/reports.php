<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('reports');
page_header('Security Reports', 'Build, preview and export security reports for stakeholders.',
    '<button type="button" class="btn btn-ghost" onclick="window.print()">' . icon('printer') . ' Print preview</button>'
    . '<button type="button" class="btn btn-primary" data-export="report-findings" data-format="csv">' . icon('download') . ' Export (demo CSV)</button>');
echo demo_notice('Reports are generated from sample data. PDF generation will be provided by the backend.');
$f = $demo['findings'];
$bySev = array_count_values(array_column($f, 'severity'));
$total = count($f);
$colors = ['Critical' => '#d42b4a', 'High' => '#c95a12', 'Medium' => '#b86e00', 'Low' => '#2f6ae0'];
$affected = array_count_values(array_column($f, 'project'));
arsort($affected);
$history = [
    ['name' => 'Monthly summary — September', 'scope' => 'All projects', 'format' => 'PDF', 'created' => '2026-10-01 08:00', 'by' => 'Scheduler'],
    ['name' => 'Billing API monthly', 'scope' => 'Billing API', 'format' => 'PDF', 'created' => '2026-10-26 07:50', 'by' => 'Demo Admin'],
    ['name' => 'Northwind incident notes', 'scope' => 'Northwind Store', 'format' => 'CSV', 'created' => '2026-10-25 15:10', 'by' => 'Security Analyst'],
    ['name' => 'Quarterly executive summary', 'scope' => 'All projects', 'format' => 'PDF', 'created' => '2026-10-02 09:00', 'by' => 'Demo Admin'],
];
?>
<div class="grid" style="grid-template-columns:minmax(0,320px) minmax(0,1fr)" id="report-layout">
  <form class="card" data-validate data-success="Report options applied to preview (Demo Mode).">
    <header class="card-head"><h2><?= icon('sliders') ?> Report builder</h2></header>
    <div class="card-body" style="display:grid;gap:14px">
      <div class="field"><label for="r-type">Template</label><select id="r-type"><option>Executive summary</option><option>Technical findings</option><option>Compliance checklist</option></select></div>
      <div class="field"><label for="r-proj">Projects</label><select id="r-proj"><option>All projects</option><?php foreach ($demo['projects'] as $p): ?><option><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="r-from">From</label><input id="r-from" type="date" value="2026-10-01" required><span class="error">Required.</span></div>
      <div class="field"><label for="r-to">To</label><input id="r-to" type="date" value="2026-10-26" required><span class="error">Required.</span></div>
      <label class="switch"><span><strong>Include evidence</strong><small>Escaped code excerpts.</small></span><input type="checkbox" checked aria-label="Include evidence"></label>
      <label class="switch"><span><strong>Include recommendations</strong></span><input type="checkbox" checked aria-label="Include recommendations"></label>
      <button class="btn btn-primary" type="submit"><?= icon('arrow-repeat') ?> Update preview</button>
    </div>
  </form>
  <section class="card">
    <header class="card-head"><h2><?= icon('file-earmark-richtext') ?> Preview</h2><span class="pill-demo"><?= icon('cone-striped') ?> Sample report</span></header>
    <div class="card-body">
      <article class="report-preview">
        <div class="rp-head"><div><h3>V7-Shield Security Report</h3><small>Executive summary · All projects · 2026-10-01 → 2026-10-26</small></div><img src="<?= e(asset('images/v7-shield-logo.svg')) ?>" alt="" width="44" height="44"></div>
        <p><strong>DEMO REPORT — sample data, not the result of a real scan.</strong></p>
        <p>V7-Shield identified <strong><?= $total ?></strong> findings across <strong><?= count($affected) ?></strong> projects. Two critical issues require immediate investigation: a possible webshell indicator in an uploads directory and a PHP payload disguised as an image.</p>
        <h4>Severity distribution</h4>
        <div class="sev-bars">
          <?php foreach ($colors as $sev => $c): $n = $bySev[$sev] ?? 0; ?>
            <div class="sev-bar"><span><?= e($sev) ?></span><div class="track"><span style="width:<?= round($n / max(1, $total) * 100) ?>%;background:<?= e($c) ?>"></span></div><strong><?= $n ?></strong></div>
          <?php endforeach; ?>
        </div>
        <h4 style="margin-top:16px">Affected projects</h4>
        <table><thead><tr><th>Project</th><th>Findings</th></tr></thead><tbody>
          <?php foreach ($affected as $p => $n): ?><tr><td><?= e($p) ?></td><td><?= (int) $n ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <h4>Top findings</h4>
        <table id="report-findings"><thead><tr><th>Severity</th><th>Finding</th><th>Project</th><th>Status</th></tr></thead><tbody>
          <?php foreach (array_slice($f, 0, 6) as $r): ?><tr><td><?= e($r['severity']) ?></td><td><?= e($r['title']) ?></td><td><?= e($r['project']) ?></td><td><?= e($r['status']) ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <h4>Recommendations</h4>
        <ol><li>Investigate and contain the two critical findings.</li><li>Block PHP execution in upload directories.</li><li>Review administrator accounts and enforce 2FA.</li><li>Update outdated plugins/extensions after staging tests.</li></ol>
      </article>
    </div>
  </section>
</div>
<?php card_open('Report history', 'clock-history');
data_table('report-history', [
    'name' => ['Report', fn($r) => '<strong>' . e($r['name']) . '</strong>'], 'scope' => ['Scope'], 'format' => ['Format'], 'created' => ['Created'], 'by' => ['By'],
    '_act' => ['', fn($r) => demo_action('Download', 'download', 'Stored reports will be served by the backend (Demo Mode).', 'btn-ghost btn-sm')],
], $history, ['format' => ['PDF', 'CSV']]);
card_close(); ?>
<style>@media (max-width:1024px){#report-layout{grid-template-columns:minmax(0,1fr)!important}}
@media print{.sidebar,.topbar,.app-footer,.page-head,.notice,#report-layout>form,.card-head,.card:not(:has(.report-preview)){display:none!important}.app-main{margin:0!important}.card{border:0;box-shadow:none}}</style>
<?php render_footer();
