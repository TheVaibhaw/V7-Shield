<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
render_header('projects');
page_header('Websites & Projects', 'Sites and directories V7-Shield monitors.', '<button type="button" class="btn btn-primary" data-modal-open="project-modal">' . icon('plus-lg') . ' Add project</button>');
echo demo_notice('Adding, editing and removing projects only changes this browser view. Nothing is persisted.');
card_open('Projects', 'globe2');
data_table('projects-table', [
    'name' => ['Project', fn($r) => '<strong>' . e($r['name']) . '</strong>'],
    'type' => ['Type'],
    'location' => ['URL / path', fn($r) => '<code class="trunc" title="' . e($r['location']) . '">' . e($r['location']) . '</code>'],
    'last_scan' => ['Last scan'],
    'status' => ['Status', fn($r) => status_badge($r['status'])],
    'findings' => ['Findings'],
    '_act' => ['', fn($r) => detail_button($r['name'], $r)
        . ' <button type="button" class="btn btn-ghost btn-sm" data-modal-open="project-modal" aria-label="Edit ' . e($r['name']) . '">' . icon('pencil') . '</button>'
        . ' <button type="button" class="btn btn-ghost btn-sm" aria-label="Remove ' . e($r['name']) . '" data-confirm="Remove “' . e($r['name']) . '” from V7-Shield? Scan history would be kept. (Demo — view only.)" data-confirm-ok="Remove" data-remove-row data-demo-toast="Project removed from view (demo).">' . icon('trash') . '</button>'],
], $demo['projects'], ['type' => ['WordPress', 'Joomla', 'Custom PHP', 'Directory'], 'status' => ['Healthy', 'Needs Attention', 'At Risk']]);
card_close();
?>
<div class="modal" id="project-modal" role="dialog" aria-modal="true" aria-labelledby="project-title" hidden>
  <form class="modal-box wide" data-validate data-success="Project details are valid (Demo Mode — not saved).">
    <h2 id="project-title">Project details</h2>
    <p class="muted">Paths must be inside the scanner's allowed root, configured on the backend.</p>
    <div class="form-grid">
      <div class="field"><label for="p-name">Name</label><input id="p-name" type="text" required minlength="3" maxlength="80"><span class="error">3–80 characters.</span></div>
      <div class="field"><label for="p-type">Type</label><select id="p-type" required><option value="">Select…</option><option>WordPress</option><option>Joomla</option><option>Custom PHP</option><option>Directory</option></select><span class="error">Choose a type.</span></div>
      <div class="field"><label for="p-url">Site URL (optional)</label><input id="p-url" type="url" placeholder="https://example.com"><span class="error">Enter a valid URL starting with http(s)://</span></div>
      <div class="field"><label for="p-path">Server path</label><input id="p-path" type="text" required placeholder="/var/www/example" data-pattern="^/[A-Za-z0-9_\-./]+$"><span class="error">Absolute path, no spaces or special characters.</span></div>
      <div class="field full"><label for="p-notes">Notes</label><textarea id="p-notes" maxlength="500"></textarea></div>
    </div>
    <div class="modal-actions" style="justify-content:flex-end"><button type="button" class="btn btn-ghost" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save project</button></div>
  </form>
</div>
<?php render_footer();
