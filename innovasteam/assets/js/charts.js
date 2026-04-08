// ============================================================
// INNOVA-STEAM — Charts (Chart.js wrappers)
// ============================================================

const Colors = {
  gold:   '#f5c842',
  blue:   '#4a9eff',
  green:  '#3ecf8e',
  purple: '#a78bfa',
  coral:  '#ff6b6b',
  muted:  '#2a2a3a',
  text:   '#94a3b8',
};

const ChartDefaults = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: {
        color: Colors.text,
        font: { family: "'Inter', sans-serif", size: 12 },
        boxWidth: 12,
      },
    },
    tooltip: {
      backgroundColor: '#12121a',
      borderColor: '#2a2a3a',
      borderWidth: 1,
      titleColor: '#ffffff',
      bodyColor: Colors.text,
      padding: 10,
    },
  },
  scales: {
    x: {
      grid:  { color: '#1a1a26', borderColor: '#2a2a3a' },
      ticks: { color: Colors.text, font: { size: 11 } },
    },
    y: {
      grid:  { color: '#1a1a26', borderColor: '#2a2a3a' },
      ticks: { color: Colors.text, font: { size: 11 } },
      beginAtZero: true,
    },
  },
};

// ── Progress radar chart (por curso) ─────────────────────────
function createRadarChart(canvasId, labels, data) {
  const ctx = document.getElementById(canvasId)?.getContext('2d');
  if (!ctx) return;

  return new Chart(ctx, {
    type: 'radar',
    data: {
      labels,
      datasets: [{
        label: 'Progreso',
        data,
        backgroundColor: 'rgba(74,158,255,.15)',
        borderColor:     Colors.blue,
        pointBackgroundColor: Colors.blue,
        pointRadius: 4,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, ...ChartDefaults.plugins },
      scales: {
        r: {
          min: 0, max: 100,
          grid:      { color: '#1a1a26' },
          angleLines: { color: '#2a2a3a' },
          ticks: {
            color: Colors.text,
            backdropColor: 'transparent',
            font: { size: 10 },
            stepSize: 25,
          },
          pointLabels: { color: Colors.text, font: { size: 11 } },
        },
      },
    },
  });
}

// ── Bar chart (módulos completados por semana) ───────────────
function createBarChart(canvasId, labels, datasets) {
  const ctx = document.getElementById(canvasId)?.getContext('2d');
  if (!ctx) return;

  return new Chart(ctx, {
    type: 'bar',
    data: { labels, datasets },
    options: {
      ...ChartDefaults,
      plugins: ChartDefaults.plugins,
      scales:  ChartDefaults.scales,
      borderRadius: 6,
    },
  });
}

// ── Doughnut chart (distribución cursos) ────────────────────
function createDoughnutChart(canvasId, labels, data) {
  const ctx = document.getElementById(canvasId)?.getContext('2d');
  if (!ctx) return;

  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: [Colors.gold, Colors.blue, Colors.green, Colors.purple, Colors.coral],
        borderColor: '#0a0a0f',
        borderWidth: 3,
        hoverOffset: 8,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: ChartDefaults.plugins,
    },
  });
}

// ── Line chart (progreso en el tiempo) ──────────────────────
function createLineChart(canvasId, labels, datasets) {
  const ctx = document.getElementById(canvasId)?.getContext('2d');
  if (!ctx) return;

  return new Chart(ctx, {
    type: 'line',
    data: { labels, datasets },
    options: {
      ...ChartDefaults,
      plugins: ChartDefaults.plugins,
      scales:  ChartDefaults.scales,
      tension: 0.4,
      fill: true,
    },
  });
}
