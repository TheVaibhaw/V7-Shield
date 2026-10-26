/* V7-Shield — scan wizard summary and UI-only scan progress simulation.
 * IMPORTANT: nothing here scans files. Progress is a timer-driven animation. */
(() => {
  'use strict';
  const { $, $$, toast, escapeHtml } = window.V7 || {};
  if (!$) return;

  /* ---------- Start New Scan wizard ---------- */
  const form = $('#scan-form');
  if (form) {
    const summary = $('#scan-summary');
    const typeParam = new URLSearchParams(location.search).get('type');
    if (typeParam) { const r = $(`input[name="scan_type"][value="${CSS.escape(typeParam)}"]`, form); r && (r.checked = true); }

    const update = () => {
      const fd = new FormData(form);
      const project = form.project.options[form.project.selectedIndex];
      const checks = fd.getAll('checks');
      const items = [
        ['Project', project && project.value ? project.text : '— not selected —'],
        ['Scan type', ($(`input[name="scan_type"]:checked`, form) || {}).dataset?.label || '—'],
        ['Scope', fd.get('scope_path') || 'Entire project root'],
        ['Exclusions', (fd.get('exclusions') || '').split('\n').filter((s) => s.trim()).length + ' patterns'],
        ['Database checks', fd.get('db_checks') ? 'Yes (read-only)' : 'No'],
        ['CMS checks', checks.length ? checks.join(', ') : 'None'],
        ['Mode', fd.get('mode') === 'readonly' ? 'Read-only (recommended)' : 'Read-only + quarantine suggestions'],
        ['Max file size', (fd.get('max_size') || '—') + ' MB'],
      ];
      summary.innerHTML = items.map(([k, v]) => `<div><dt>${escapeHtml(k)}</dt><dd>${escapeHtml(v)}</dd></div>`).join('');
    };
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();

    form.addEventListener('v7:valid', () => {
      const out = $('#scan-sim');
      out.hidden = false;
      out.scrollIntoView({ behavior: 'smooth', block: 'center' });
      runSimulation(out, { files: 4200 + Math.floor(Math.random() * 3000) });
    });
  }

  /* ---------- Simulation ---------- */
  const PHASES = ['Building file list', 'Checking extensions & signatures', 'Pattern matching', 'Integrity comparison', 'Compiling results'];
  function runSimulation(root, { files, start = 0 }) {
    const bar = $('.progress span', root);
    const pct = $('[data-sim-pct]', root);
    const phase = $('[data-sim-phase]', root);
    const count = $('[data-sim-files]', root);
    const elapsed = $('[data-sim-elapsed]', root);
    const log = $('.scan-log', root);
    const status = $('[data-sim-status]', root);
    let p = start; let t = 0; let lastPhase = -1;
    if (root._timer) clearInterval(root._timer);
    root._paused = false;
    status && (status.innerHTML = '<span class="status st-run">Running (simulated)</span>');
    root._timer = setInterval(() => {
      if (root._paused) return;
      t += 1;
      p = Math.min(100, p + Math.random() * 6 + 1);
      const ph = Math.min(PHASES.length - 1, Math.floor(p / (100 / PHASES.length)));
      bar.style.width = p + '%';
      bar.parentElement.setAttribute('aria-valuenow', String(Math.round(p)));
      pct && (pct.textContent = Math.round(p) + '%');
      phase && (phase.textContent = PHASES[ph]);
      count && (count.textContent = Math.round((files * p) / 100).toLocaleString());
      elapsed && (elapsed.textContent = `${Math.floor(t / 60)}m ${String(t % 60).padStart(2, '0')}s`);
      if (log && ph !== lastPhase) { log.insertAdjacentHTML('beforeend', `<div>[sim ${new Date().toLocaleTimeString()}] ${escapeHtml(PHASES[ph])}…</div>`); log.scrollTop = log.scrollHeight; lastPhase = ph; }
      if (p >= 100) {
        clearInterval(root._timer);
        status && (status.innerHTML = '<span class="status st-ok">Simulation complete</span>');
        log && log.insertAdjacentHTML('beforeend', '<div>[sim] Done. No real files were scanned — connect the backend to run actual scans.</div>');
        toast('UI simulation finished. No real files were scanned.', 'success');
      }
    }, 600);
  }

  /* ---------- Active scans page controls ---------- */
  $$('[data-sim-card]').forEach((card) => {
    const start = parseInt(card.dataset.simStart || '0', 10);
    if (card.dataset.simAuto !== undefined) runSimulation(card, { files: parseInt(card.dataset.simFiles || '5000', 10), start });
  });
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-sim-action]');
    if (!b) return;
    const card = b.closest('[data-sim-card], #scan-sim');
    const act = b.dataset.simAction;
    const status = $('[data-sim-status]', card);
    if (act === 'pause') {
      card._paused = !card._paused;
      b.innerHTML = card._paused ? '<i class="bi bi-play-fill" aria-hidden="true"></i> Resume' : '<i class="bi bi-pause-fill" aria-hidden="true"></i> Pause';
      status && (status.innerHTML = card._paused ? '<span class="status st-queue">Paused (simulated)</span>' : '<span class="status st-run">Running (simulated)</span>');
    } else if (act === 'cancel') {
      window.V7.confirmDialog('Cancel this simulated scan? (Demo only — no real process is running.)', { title: 'Cancel scan', ok: 'Cancel scan' }).then((ok) => {
        if (!ok) return;
        clearInterval(card._timer);
        status && (status.innerHTML = '<span class="status st-neutral">Cancelled</span>');
        toast('Simulated scan cancelled.', 'info');
      });
    } else if (act === 'restart') {
      runSimulation(card, { files: parseInt(card.dataset.simFiles || '5000', 10) });
    }
  });
})();
