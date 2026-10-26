(() => {
  'use strict';

  const css = (v) => getComputedStyle(document.documentElement).getPropertyValue(v).trim();
  const SEV = [['Critical', '--red'], ['High', '--orange'], ['Medium', '--amber'], ['Low', '--blue']];
  const charts = [];

  function themeDefaults() {
    if (!window.Chart) return;
    Chart.defaults.color = css('--muted');
    Chart.defaults.borderColor = css('--border');
    Chart.defaults.font.family = css('--font') || 'Inter, sans-serif';
  }

  function tooltip() {
    return { backgroundColor: css('--surface-3'), titleColor: css('--text'), bodyColor: css('--text-2'), borderColor: css('--border-strong'), borderWidth: 1, padding: 10, boxPadding: 4, usePointStyle: true };
  }

  function severityArea(canvas, series) {
    themeDefaults();
    const ctx = canvas.getContext('2d');
    const datasets = SEV.map(([name, token]) => {
      const color = css(token);
      const grad = ctx.createLinearGradient(0, 0, 0, canvas.parentElement.clientHeight || 300);
      grad.addColorStop(0, color + '55'); grad.addColorStop(1, color + '00');
      return { label: name, data: series.data[name], borderColor: color, backgroundColor: grad, fill: true, tension: .35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 };
    });
    const chart = new Chart(canvas, {
      type: 'line',
      data: { labels: series.labels, datasets },
      options: {
        responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } }, tooltip: tooltip() },
        scales: {
          x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0 } },
          y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: css('--border') } },
        },
      },
    });
    chart.$kind = 'severity';
    charts.push(chart);
    return chart;
  }

  function doughnut(canvas, labels, values, tokens) {
    themeDefaults();
    const chart = new Chart(canvas, {
      type: 'doughnut',
      data: { labels, datasets: [{ data: values, backgroundColor: tokens.map(css), borderColor: css('--surface'), borderWidth: 3 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip: tooltip() } },
    });
    chart.$tokens = tokens;
    charts.push(chart);
    return chart;
  }

  function bars(canvas, labels, values, token = '--cyan') {
    themeDefaults();
    const chart = new Chart(canvas, {
      type: 'bar',
      data: { labels, datasets: [{ data: values, backgroundColor: css(token), borderRadius: 6, maxBarThickness: 28 }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: tooltip() }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } },
    });
    chart.$bar = token;
    charts.push(chart);
    return chart;
  }

  document.addEventListener('v7:theme', () => {
    themeDefaults();
    charts.forEach((c) => {
      c.options.plugins.tooltip = tooltip();
      if (c.options.scales?.y?.grid) c.options.scales.y.grid.color = css('--border');
      if (c.$tokens) { c.data.datasets[0].backgroundColor = c.$tokens.map(css); c.data.datasets[0].borderColor = css('--surface'); }
      if (c.$bar) c.data.datasets[0].backgroundColor = css(c.$bar);
      c.update();
    });
  });

  function initDeclarative() {
    if (!window.Chart) return;
    document.querySelectorAll('canvas[data-chart]').forEach((c) => {
      const labels = JSON.parse(c.dataset.labels || '[]');
      const values = JSON.parse(c.dataset.values || '[]');
      if (c.dataset.chart === 'doughnut') doughnut(c, labels, values, JSON.parse(c.dataset.colors || '[]'));
      else bars(c, labels, values, c.dataset.color || '--cyan');
    });
  }

  window.V7Charts = { severityArea, doughnut, bars };
  window.addEventListener('load', initDeclarative);
})();
