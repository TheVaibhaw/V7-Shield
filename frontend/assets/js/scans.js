/* V7-Shield — live scan status. Shows only real, persisted job data from the API. */
(() => {
  'use strict';
  const { $, toast } = window.V7 || {};
  if (!$) return;

  const api = document.body.dataset.api;
  const csrf = document.body.dataset.csrf;

  // Prevent double submits of the start form.
  const startForm = $('[data-start-form]');
  startForm && startForm.addEventListener('submit', () => {
    const b = $('button[type="submit"]', startForm);
    b.disabled = true;
    b.innerHTML = '<span class="spinner"></span> Creating scan…';
  });

  const live = $('#scan-live');
  if (!live || !['queued', 'running'].includes(live.dataset.status)) return;
  const id = live.dataset.scanId;
  const msg = $('[data-live="message"]', live);
  const fmt = (n) => Number(n).toLocaleString();

  async function request(action, opts = {}) {
    const res = await fetch(`${api}?action=${action}&id=${encodeURIComponent(id)}`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', ...(opts.method === 'POST' ? { 'X-CSRF-Token': csrf } : {}) },
      ...opts,
    });
    const data = await res.json().catch(() => ({ error: 'Unexpected response from server.' }));
    if (!res.ok) throw new Error(data.error || `Request failed (${res.status}).`);
    return data;
  }

  function render(s) {
    const cards = live.querySelectorAll('.stat-value');
    if (cards.length >= 4) {
      cards[0].textContent = fmt(s.files_scanned);
      cards[1].textContent = fmt(s.files_skipped);
      cards[2].textContent = fmt(s.findings_count);
      cards[3].textContent = fmt(s.errors_count);
    }
    if (msg && s.status === 'running') msg.textContent = `Scanning… ${fmt(s.files_scanned)} files processed so far.`;
  }

  let timer = null;
  let failures = 0;
  async function poll() {
    try {
      const s = await request('status');
      failures = 0;
      render(s);
      if (!['queued', 'running'].includes(s.status)) {
        clearInterval(timer);
        toast(`Scan ${s.status}.`, s.status === 'completed' ? 'success' : 'error');
        setTimeout(() => location.reload(), 600);
      }
    } catch (e) {
      if (++failures >= 5) { clearInterval(timer); msg && (msg.textContent = `Status updates stopped: ${e.message} Reload to retry.`); }
    }
  }
  timer = setInterval(poll, 1500);

  // Inline execution: this request runs the scan; polling continues in parallel.
  if (live.dataset.autorun === '1') {
    request('run', { method: 'POST' }).then(() => poll()).catch((e) => {
      toast(e.message, 'error');
      msg && (msg.textContent = e.message);
    });
  }
})();
