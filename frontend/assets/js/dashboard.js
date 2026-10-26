/* V7-Shield — dashboard behaviour: threat activity chart and period filter. */
(() => {
  'use strict';

  const canvas = document.getElementById('threat-chart');
  if (!canvas) return;

  const box = canvas.parentElement;
  const loading = box.querySelector('.chart-loading');
  const empty = box.querySelector('.chart-empty');
  const tabs = document.querySelectorAll('[data-chart-range] [data-range]');
  const periodSelect = document.querySelector('[data-period-select]');
  let series = {};
  try { series = JSON.parse(document.getElementById('threat-series').textContent); } catch { /* keep empty */ }
  let chart = null;

  function show(range) {
    tabs.forEach((t) => t.setAttribute('aria-selected', String(t.dataset.range === range)));
    if (periodSelect && series[range]) periodSelect.value = range;
    const data = series[range];
    const hasData = data && Object.values(data.data).some((arr) => arr.some((v) => v > 0));
    empty.hidden = !!hasData;
    canvas.style.visibility = hasData ? 'visible' : 'hidden';
    if (!hasData || !chart) return;
    chart.data.labels = data.labels;
    chart.data.datasets.forEach((ds) => { ds.data = data.data[ds.label]; });
    chart.update();
  }

  window.addEventListener('load', () => {
    if (!window.Chart || !window.V7Charts) {
      loading.innerHTML = '<span>Chart library could not be loaded (offline?). Data is still available in the tables below.</span>';
      return;
    }
    chart = V7Charts.severityArea(canvas, series['7d']);
    loading.hidden = true;
    show('7d');
  });

  tabs.forEach((t) => t.addEventListener('click', () => show(t.dataset.range)));
  periodSelect && periodSelect.addEventListener('change', () => show(periodSelect.value));
})();
