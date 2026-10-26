<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('database');
page_header('Database Security', 'Read-only checks for injected content and risky configuration in MySQL/MariaDB.');
echo demo_notice('Connection records are samples. Credentials are never stored in or sent to the frontend — they live in backend configuration only.');
$conns = [
    ['name' => 'northwind_wp', 'project' => 'Northwind Store', 'engine' => 'MariaDB 10.11', 'host' => 'db.internal (masked)', 'user' => 'v7_readonly', 'status' => 'Failed', 'last' => '2026-10-25 23:40'],
    ['name' => 'acme_wp', 'project' => 'Acme Marketing Site', 'engine' => 'MySQL 8.0', 'host' => 'localhost (masked)', 'user' => 'v7_readonly', 'status' => 'Connected', 'last' => '2026-10-26 08:40'],
    ['name' => 'library_joomla', 'project' => 'City Library Portal', 'engine' => 'MariaDB 10.6', 'host' => 'localhost (masked)', 'user' => 'v7_readonly', 'status' => 'Connected', 'last' => '2026-10-25 18:00'],
    ['name' => 'crm_legacy', 'project' => 'Legacy CRM', 'engine' => 'MySQL 5.7', 'host' => 'legacy-db (masked)', 'user' => 'v7_readonly', 'status' => 'Warning', 'last' => '2026-10-23 14:50'],
];
card_open('Connections', 'database');
data_table('db-conns', [
    'name' => ['Database', fn($r) => '<code>' . e($r['name']) . '</code>'], 'project' => ['Project'], 'engine' => ['Engine'], 'host' => ['Host'],
    'user' => ['User', fn($r) => '<code>' . e($r['user']) . '</code>'],
    'status' => ['Status', fn($r) => status_badge($r['status'])], 'last' => ['Last check'],
    '_act' => ['', fn($r) => detail_button($r['name'], $r) . ' ' . demo_action('Test', 'plug', 'Connection test runs on the backend only (Demo Mode).', 'btn-ghost btn-sm')],
], $conns, ['status' => ['Connected', 'Failed', 'Warning']]);
card_close();
?>
<div class="grid grid-2">
  <?php card_open('Read-only check status', 'check2-square'); check_list([
      ['Read-only user enforced', 'Passed', 'Only SELECT privileges granted to v7_readonly.'],
      ['Injected <script> in post content', 'Warning', '2 rows in northwind_wp.wp_posts (last successful run).'],
      ['Suspicious options / cron entries', 'Warning', 'Remote URL found in wp_options cron.'],
      ['Unexpected admin users', 'Needs Attention', '1 new administrator in wp_users.'],
  ]); card_close(); ?>
  <?php card_open('Configuration review', 'sliders'); check_list([
      ['TLS for remote connections', 'Warning', 'crm_legacy accepts unencrypted connections.'],
      ['Server version support', 'Outdated', 'MySQL 5.7 is end-of-life.'],
      ['Remote root login', 'Passed', 'Disabled.'],
      ['Default/anonymous accounts', 'Passed', 'None found.'],
  ]); card_close(); ?>
</div>
<?php card_open('Database event history', 'clock-history');
data_table('db-events', event_columns(), [
    ['time' => '2026-10-25 23:41', 'category' => 'Database', 'severity' => 'High', 'source' => 'Scanner', 'project' => 'Northwind Store', 'title' => 'Check failed', 'desc' => 'Connection timed out after 30s.'],
    ['time' => '2026-10-25 14:22', 'category' => 'Database', 'severity' => 'High', 'source' => 'Scanner', 'project' => 'Northwind Store', 'title' => 'New administrator row', 'desc' => 'wp_users row for "support_tmp".'],
    ['time' => '2026-10-24 02:10', 'category' => 'Database', 'severity' => 'Medium', 'source' => 'Scanner', 'project' => 'Northwind Store', 'title' => 'Script tag in content', 'desc' => '2 posts contain external script tags.'],
    ['time' => '2026-10-23 14:52', 'category' => 'Database', 'severity' => 'Low', 'source' => 'Scanner', 'project' => 'Legacy CRM', 'title' => 'EOL server version', 'desc' => 'MySQL 5.7 detected.'],
], ['severity' => ['High', 'Medium', 'Low']], 5);
card_close();
render_footer();
