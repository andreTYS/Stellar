<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$pdo = getDB();

$stats = [
    'colegios'            => $pdo->query("SELECT COUNT(*) FROM colegios WHERE activo=1")->fetchColumn(),
    'usuarios'            => $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1")->fetchColumn(),
    'estudiantes'         => $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='estudiante' AND activo=1")->fetchColumn(),
    'modulos_completados' => $pdo->query("SELECT COUNT(*) FROM progreso_estudiante WHERE completado=1")->fetchColumn(),
];

$colegiosRecientes = $pdo->query("
    SELECT col.*,
           COUNT(DISTINCT u.id) as total_estudiantes
    FROM colegios col
    LEFT JOIN usuarios u ON u.colegio_id=col.id AND u.rol='estudiante'
    WHERE col.activo=1
    GROUP BY col.id
    ORDER BY col.created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard Global';
$activeNav = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page header -->
<div class="page-action-header">
  <div>
    <h1 class="page-title">Panel de Administración</h1>
    <p class="page-subtitle">Vista global de la plataforma · <?= date('d/m/Y') ?></p>
  </div>
  <a href="<?= BASE_URL ?>/admin/reportes.php" class="btn btn-primary">
    <i data-lucide="bar-chart-2" style="width:16px;height:16px"></i>
    Ver reportes
  </a>
</div>

<!-- Stats -->
<div class="stats-grid mb-32">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">
      <i data-lucide="building-2" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--blue)"><?= (int)$stats['colegios'] ?></div>
    <div class="stat-label">Colegios activos</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
      <i data-lucide="users" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= (int)$stats['usuarios'] ?></div>
    <div class="stat-label">Usuarios totales</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
      <i data-lucide="graduation-cap" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--purple)"><?= (int)$stats['estudiantes'] ?></div>
    <div class="stat-label">Estudiantes</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">
      <i data-lucide="check-circle-2" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--gold)"><?= (int)$stats['modulos_completados'] ?></div>
    <div class="stat-label">Módulos completados</div>
  </div>
</div>

<!-- Charts row -->
<div class="charts-row">

  <!-- Completaciones por curso -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Completaciones por curso</h2>
        <p class="card-subtitle">Módulos completados totales</p>
      </div>
    </div>
    <div class="chart-container" style="height:220px">
      <canvas id="chartCursos"></canvas>
    </div>
  </div>

  <!-- Actividad últimos 7 días -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Actividad reciente</h2>
        <p class="card-subtitle">Módulos completados — últimos 7 días</p>
      </div>
    </div>
    <div class="chart-container" style="height:220px">
      <canvas id="chartActividad"></canvas>
    </div>
  </div>

</div>

<!-- Colegios table -->
<div class="card mb-24">
  <div class="card-header">
    <div>
      <h2 class="card-title">Colegios activos</h2>
      <p class="card-subtitle">Últimos colegios registrados en la plataforma</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/colegios.php" class="btn btn-primary btn-sm">
      <i data-lucide="settings" style="width:14px;height:14px"></i>
      Gestionar
    </a>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Colegio</th>
          <th>Distrito</th>
          <th>Director</th>
          <th>Estudiantes</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($colegiosRecientes)): ?>
        <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted);">Sin colegios registrados</td></tr>
        <?php else: ?>
        <?php foreach ($colegiosRecientes as $col): ?>
        <tr>
          <td style="font-weight:600"><?= sanitize($col['nombre']) ?></td>
          <td><?= sanitize($col['distrito']) ?></td>
          <td><?= sanitize($col['director'] ?? '—') ?></td>
          <td>
            <span class="badge" style="background:rgba(74,158,255,.12);color:var(--blue)">
              <?= (int)$col['total_estudiantes'] ?> est.
            </span>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/admin/colegios.php?edit=<?= (int)$col['id'] ?>"
               class="btn btn-ghost btn-sm">
              <i data-lucide="pencil" style="width:13px;height:13px"></i>
              Editar
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Quick links -->
<div class="mb-8">
  <h2 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--text-primary)">Acceso rápido</h2>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px">
  <?php
  $links = [
    ['href' => BASE_URL.'/admin/colegios.php',  'icon' => 'building-2',  'label' => 'Colegios',  'color' => 'var(--blue)'],
    ['href' => BASE_URL.'/admin/usuarios.php',  'icon' => 'users',       'label' => 'Usuarios',  'color' => 'var(--purple)'],
    ['href' => BASE_URL.'/admin/cursos.php',    'icon' => 'book-open',   'label' => 'Cursos',    'color' => 'var(--green)'],
    ['href' => BASE_URL.'/admin/modulos.php',   'icon' => 'layers',      'label' => 'Módulos',   'color' => 'var(--gold)'],
    ['href' => BASE_URL.'/admin/reportes.php',  'icon' => 'bar-chart-2', 'label' => 'Reportes',  'color' => 'var(--coral)'],
  ];
  foreach ($links as $l): ?>
  <a href="<?= $l['href'] ?>"
     class="card flex items-center gap-12"
     style="text-decoration:none;transition:border-color .2s,transform .15s;cursor:pointer"
     onmouseover="this.style.borderColor='<?= $l['color'] ?>55';this.style.transform='translateY(-2px)'"
     onmouseout="this.style.borderColor='var(--bg-border)';this.style.transform=''">
    <div style="width:38px;height:38px;border-radius:10px;background:<?= $l['color'] ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i data-lucide="<?= $l['icon'] ?>" style="width:18px;height:18px;color:<?= $l['color'] ?>"></i>
    </div>
    <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:var(--text-primary)"><?= $l['label'] ?></span>
  </a>
  <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const BASE = '<?= BASE_URL ?>';
  const fontColor = '#8898aa';

  // Chart defaults
  if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = fontColor;

    // Completaciones por curso
    try {
      const r1 = await fetch(BASE + '/api/stats.php?type=completions_by_course');
      const d1 = await r1.json();
      new Chart(document.getElementById('chartCursos'), {
        type: 'bar',
        data: {
          labels: d1.labels,
          datasets: [{
            label: 'Completados',
            data: d1.values,
            backgroundColor: d1.colors.map(c => c + 'cc'),
            borderColor: d1.colors,
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#e4e8f022' } },
            x: { grid: { display: false } }
          }
        }
      });
    } catch(e) {}

    // Actividad últimos 7 días
    try {
      const r2 = await fetch(BASE + '/api/stats.php?type=daily_activity');
      const d2 = await r2.json();
      new Chart(document.getElementById('chartActividad'), {
        type: 'line',
        data: {
          labels: d2.labels,
          datasets: [{
            label: 'Completaciones',
            data: d2.values,
            borderColor: '#4361ee',
            backgroundColor: 'rgba(67,97,238,.08)',
            fill: true,
            tension: .4,
            pointBackgroundColor: '#4361ee',
            pointRadius: 4,
            pointHoverRadius: 6,
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#e4e8f022' } },
            x: { grid: { display: false } }
          }
        }
      });
    } catch(e) {}
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
