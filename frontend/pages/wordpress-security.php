<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('wordpress');
page_header('WordPress Security', 'Core integrity, plugins, themes, accounts and configuration for WordPress sites.', '<a class="btn btn-primary" href="' . e(page_url('scans')) . '?type=full">' . icon('play-fill') . ' Scan WordPress site</a>');
echo demo_notice();
$wp = rows_where($demo['findings'], 'project', ['Acme Marketing Site', 'Northwind Store']);
?>
<div class="stat-grid">
  <?= stat_card('wordpress', 'WordPress sites', '2', 'cyan') ?>
  <?= stat_card('plug', 'Plugins checked', '38', 'blue', '', '3 outdated, 1 with known advisories.') ?>
  <?= stat_card('palette', 'Themes checked', '6', 'green') ?>
  <?= stat_card('people', 'Admin accounts', '5', 'amber', '+1', 'One administrator was not present in the previous scan.') ?>
</div>
<div class="grid grid-2">
  <?php card_open('Core integrity', 'shield-check'); check_list([
      ['Acme Marketing Site — WordPress 6.6.x', 'Verified', 'All core files match official checksums.'],
      ['Northwind Store — WordPress 6.6.x', 'Warning', '1 unexpected file in wp-includes/.'],
      ['wp-admin/ & wp-includes/', 'Passed', 'No PHP files in unexpected locations.'],
  ]); card_close(); ?>
  <?php card_open('Configuration review', 'gear'); check_list([
      ['WP_DEBUG', 'Warning', 'Enabled on Northwind Store — disable in production.'],
      ['DISALLOW_FILE_EDIT', 'Warning', 'Not set — theme/plugin editor is available.'],
      ['Salts & keys', 'Passed', 'Unique keys present.'],
      ['XML-RPC', 'Pending', 'Review whether it is needed.'],
  ]); card_close(); ?>
</div>
<div class="grid grid-2">
  <?php card_open('Plugins', 'plug');
  data_table('wp-plugins', [
      'name' => ['Plugin', fn($r) => '<strong>' . e($r['name']) . '</strong>'], 'site' => ['Site'], 'version' => ['Version'],
      'status' => ['Status', fn($r) => status_badge($r['status'])],
  ], [
      ['name' => 'contact-form-x', 'site' => 'Acme', 'version' => '2.1.0 → 2.4.3', 'status' => 'Outdated'],
      ['name' => 'seo-helper', 'site' => 'Acme', 'version' => '1.8.2', 'status' => 'Needs Attention'],
      ['name' => 'woocommerce', 'site' => 'Northwind', 'version' => '9.x', 'status' => 'Healthy'],
      ['name' => 'cache-booster', 'site' => 'Northwind', 'version' => '3.0.1 → 3.1.0', 'status' => 'Outdated'],
      ['name' => 'forms-lite', 'site' => 'Northwind', 'version' => '5.2.0', 'status' => 'Healthy'],
  ], ['status' => ['Healthy', 'Outdated', 'Needs Attention']], 5);
  card_close(); ?>
  <?php card_open('Administrator accounts', 'people'); check_list([
      ['admin (Acme)', 'Verified', 'Last login 2026-10-25'],
      ['editor-lead (Acme)', 'Verified', 'Role: administrator'],
      ['support_tmp (Northwind)', 'Needs Attention', 'New since previous scan — confirm with owner.'],
      ['shopmanager (Northwind)', 'Verified', 'Last login 2026-10-24'],
  ]); ?>
  <h3 class="section-title">Themes</h3>
  <?php check_list([['storefront-child (Northwind)', 'Warning', 'footer.php modified; hidden iframe pattern.'], ['twentytwentyfive (Acme)', 'Healthy', 'Matches official package.']]); ?>
  <?php card_close(); ?>
</div>
<?php card_open('Suspicious PHP files & findings', 'bug');
data_table('wp-findings', finding_columns(), $wp, ['severity' => ['Critical', 'High', 'Medium', 'Low']], 6);
card_close();
card_open('Recent file changes', 'file-diff');
data_table('wp-changes', change_columns(), rows_where($demo['integrity']['changes'], 'project', ['Acme Marketing Site', 'Northwind Store']), [], 5);
card_close();
render_footer();
