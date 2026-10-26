<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('security-events');
page_header('Security Events', 'Security-relevant events from scans, detections, integrity checks and accounts.', export_buttons('events-table'));
echo demo_notice();
$ev = $demo['events'];
card_open('Event log', 'broadcast');
data_table('events-table', event_columns(), $ev, [
    'severity' => ['Critical', 'High', 'Medium', 'Low', 'Info'],
    'category' => array_values(array_unique(array_column($ev, 'category'))),
    'source' => array_values(array_unique(array_column($ev, 'source'))),
], 10);
card_close();
render_footer();
