<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$pdo = getDB();

// ── Current period stats ──────────────────────────────────────
$stats = [
    'colegios'     => (int)$pdo->query("SELECT COUNT(*) FROM colegios WHERE activo=1")->fetchColumn(),
    'usuarios'     => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1")->fetchColumn(),
    'estudiantes'  => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='estudiante' AND activo=1")->fetchColumn(),
    'docentes'     => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='docente' AND activo=1")->fetchColumn(),
    'practicantes' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='practicante' AND activo=1")->fetchColumn(),
    'completados'  => (int)$pdo->query("SELECT COUNT(*) FROM progreso_estudiante WHERE completado=1")->fetchColumn(),
];

// ── Trends vs. same-length window 30 days ago ────────────────
$prev = [
    'colegios'     => (int)$pdo->query("SELECT COUNT(*) FROM colegios WHERE activo=1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'usuarios'     => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'completados'  => (int)$pdo->query("SELECT COUNT(*) FROM progreso_estudiante WHERE completado=1 AND completado_en < DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
];

function trendPct(int $curr, int $prev): array {
    if ($prev === 0) return ['pct' => null, 'dir' => 'flat'];
    $pct = round(($curr - $prev) / $prev * 100);
    return ['pct' => abs($pct), 'dir' => $pct >= 0 ? 'up' : 'down'];
}
$tColegios    = trendPct($stats['colegios'],    $prev['colegios']);
$tUsuarios    = trendPct($stats['usuarios'],    $prev['usuarios']);
$tCompletados = trendPct($stats['completados'], $prev['completados']);

// ── Last 8 completions (activity feed) ───────────────────────
$actividad = $pdo->query("
    SELECT u.nombre, u.apellido, m.titulo AS modulo, c.nombre AS colegio,
           pe.completado_en
    FROM progreso_estudiante pe
    JOIN usuarios u ON u.id = pe.estudiante_id
    JOIN modulos m ON m.id = pe.modulo_id
    JOIN estudiante_aula ea ON ea.estudiante_id = pe.estudiante_id
    JOIN aulas a ON a.id = ea.aula_id
    JOIN colegios c ON c.id = a.colegio_id
    WHERE pe.completado = 1
    ORDER BY pe.completado_en DESC
    LIMIT 8
")->fetchAll();

// ── Extra metrics ────────────────────────────────────────────
$stats['esta_semana'] = (int)$pdo->query("SELECT COUNT(*) FROM progreso_estudiante WHERE completado=1 AND completado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// ── Top 5 modules ─────────────────────────────────────────────
$topModulos = $pdo->query("
    SELECT m.titulo, c.nombre as curso, c.color_hex, COUNT(pe.id) as completaciones
    FROM modulos m
    JOIN cursos c ON c.id=m.curso_id
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id=m.id AND pe.completado=1
    WHERE m.activo=1
    GROUP BY m.id ORDER BY completaciones DESC LIMIT 5
")->fetchAll();

// ── Colegios table ────────────────────────────────────────────
$colegiosRecientes = $pdo->query("
    SELECT col.*,
           COUNT(DISTINCT u.id)   AS total_estudiantes,
           COUNT(DISTINCT pe.id)  AS total_completados
    FROM colegios col
    LEFT JOIN aulas a ON a.colegio_id = col.id
    LEFT JOIN estudiante_aula ea ON ea.aula_id = a.id
    LEFT JOIN usuarios u ON u.id = ea.estudiante_id AND u.rol='estudiante'
    LEFT JOIN progreso_estudiante pe ON pe.estudiante_id = u.id AND pe.completado = 1
    WHERE col.activo = 1
    GROUP BY col.id
    ORDER BY col.created_at DESC
    LIMIT 6
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

<!-- Stats 6-col -->
<div class="stats-grid-6">

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">
      <i data-lucide="building-2" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--blue)"><?= $stats['colegios'] ?></div>
    <div class="stat-label">Colegios activos</div>
    <?php if ($tColegios['pct'] !== null): ?>
    <span class="stat-trend <?= $tColegios['dir'] ?>">
      <?php if ($tColegios['dir'] === 'up'): ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>
      <?php else: ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
      <?php endif; ?>
      <?= $tColegios['pct'] ?>% vs mes anterior
    </span>
    <?php endif; ?>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
      <i data-lucide="users" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= $stats['usuarios'] ?></div>
    <div class="stat-label">Usuarios activos</div>
    <?php if ($tUsuarios['pct'] !== null): ?>
    <span class="stat-trend <?= $tUsuarios['dir'] ?>">
      <?php if ($tUsuarios['dir'] === 'up'): ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>
      <?php else: ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
      <?php endif; ?>
      <?= $tUsuarios['pct'] ?>% vs mes anterior
    </span>
    <?php endif; ?>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
      <i data-lucide="graduation-cap" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--purple)"><?= $stats['estudiantes'] ?></div>
    <div class="stat-label">Estudiantes</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(34,211,238,.12);color:var(--teal)">
      <i data-lucide="user-check" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--teal)"><?= $stats['docentes'] ?></div>
    <div class="stat-label">Docentes</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(251,99,64,.12);color:var(--coral)">
      <i data-lucide="book-open" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--coral)"><?= $stats['practicantes'] ?></div>
    <div class="stat-label">Practicantes</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">
      <i data-lucide="check-circle-2" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--gold)"><?= $stats['completados'] ?></div>
    <div class="stat-label">Módulos completados</div>
    <?php if ($tCompletados['pct'] !== null): ?>
    <span class="stat-trend <?= $tCompletados['dir'] ?>">
      <?php if ($tCompletados['dir'] === 'up'): ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>
      <?php else: ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="6 9 12 15 18 9"/></svg>
      <?php endif; ?>
      <?= $tCompletados['pct'] ?>% vs mes anterior
    </span>
    <?php endif; ?>
  </div>

  <div class="stat-card" style="grid-column:span 1">
    <div class="stat-icon" style="background:rgba(6,182,212,.12);color:#06b6d4">
      <i data-lucide="zap" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:#06b6d4"><?= $stats['esta_semana'] ?></div>
    <div class="stat-label">Completaciones esta semana</div>
  </div>

</div>

<!-- Charts + activity -->
<div style="display:grid;grid-template-columns:1fr 1fr 300px;gap:20px;margin-bottom:24px">

  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Completaciones por curso</h2>
        <p class="card-subtitle">Módulos completados totales</p>
      </div>
    </div>
    <div class="chart-container" style="height:220px;position:relative" id="wrap1">
      <div class="chart-loading" id="load1">Cargando...</div>
      <canvas id="chartCursos" style="display:none"></canvas>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Actividad diaria</h2>
        <p class="card-subtitle">Completaciones — últimos 7 días</p>
      </div>
    </div>
    <div class="chart-container" style="height:220px;position:relative" id="wrap2">
      <div class="chart-loading" id="load2">Cargando...</div>
      <canvas id="chartActividad" style="display:none"></canvas>
    </div>
  </div>

  <!-- Activity feed -->
  <div class="card" style="min-width:0">
    <div class="card-header">
      <h2 class="card-title">Últimas completaciones</h2>
    </div>
    <?php if (empty($actividad)): ?>
    <p style="font-size:13px;color:var(--text-muted);padding:8px 0">Sin actividad reciente.</p>
    <?php else: ?>
    <div class="activity-feed">
      <?php foreach ($actividad as $a): ?>
      <div class="activity-item">
        <div class="activity-dot" style="background:rgba(62,207,142,.12);color:var(--green)">
          <i data-lucide="check-circle-2"></i>
        </div>
        <div class="activity-text">
          <div class="activity-title"><?= sanitize($a['nombre'] . ' ' . $a['apellido']) ?></div>
          <div class="activity-meta"><?= sanitize($a['modulo']) ?> · <?= sanitize($a['colegio']) ?></div>
        </div>
        <span style="font-size:10px;color:var(--text-muted);white-space:nowrap;flex-shrink:0">
          <?= isset($a['completado_en']) ? date('d/m H:i', strtotime($a['completado_en'])) : '' ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Colegios table -->
<div class="card mb-24">
  <div class="card-header">
    <div>
      <h2 class="card-title">Colegios activos</h2>
      <p class="card-subtitle">Últimos colegios · módulos completados por institución</p>
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
          <th>Estudiantes</th>
          <th>Completaciones</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($colegiosRecientes)): ?>
        <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted)">Sin colegios registrados</td></tr>
        <?php else: ?>
        <?php foreach ($colegiosRecientes as $col): ?>
        <tr>
          <td style="font-weight:600"><?= sanitize($col['nombre']) ?></td>
          <td style="color:var(--text-muted)"><?= sanitize($col['distrito']) ?></td>
          <td><span class="badge" style="background:rgba(74,158,255,.12);color:var(--blue)"><?= (int)$col['total_estudiantes'] ?> est.</span></td>
          <td>
            <span class="badge" style="background:rgba(62,207,142,.12);color:var(--green)">
              <i data-lucide="check" style="width:11px;height:11px"></i>
              <?= (int)$col['total_completados'] ?>
            </span>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/admin/colegios.php?edit=<?= (int)$col['id'] ?>" class="btn btn-ghost btn-sm">
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

<!-- Top módulos + Quick links -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">

  <!-- Top 5 modules -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Top módulos completados</h2>
    </div>
    <?php
    $maxTopMod = max(1, $topModulos[0]['completaciones'] ?? 1);
    foreach ($topModulos as $i => $tm):
      $pctMod = round($tm['completaciones'] / $maxTopMod * 100);
    ?>
    <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--bg-border)">
      <span style="width:20px;font-size:11px;font-weight:700;color:var(--text-muted);text-align:center;flex-shrink:0"><?= $i+1 ?></span>
      <div style="flex:1;min-width:0">
        <div style="font-size:12px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($tm['titulo']) ?></div>
        <div style="font-size:10px;color:<?= $tm['color_hex'] ?>;margin-top:2px"><?= sanitize($tm['curso']) ?></div>
        <div class="progress-track" style="height:4px;margin-top:5px">
          <div class="progress-fill" data-pct="<?= $pctMod ?>" style="background:<?= $tm['color_hex'] ?>;width:0%;transition:width .8s ease"></div>
        </div>
      </div>
      <span style="font-size:14px;font-weight:700;color:var(--accent);flex-shrink:0"><?= $tm['completaciones'] ?></span>
    </div>
    <?php endforeach; ?>
    <?php if (empty($topModulos)): ?>
    <p style="color:var(--text-muted);font-size:13px;padding:16px 0;text-align:center">Sin actividad registrada</p>
    <?php endif; ?>
  </div>

  <!-- Quick links -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Acceso rápido</h2>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <?php
    $links = [
      ['href' => BASE_URL.'/admin/colegios.php',  'icon' => 'building-2',  'label' => 'Colegios',  'color' => 'var(--blue)'],
      ['href' => BASE_URL.'/admin/usuarios.php',  'icon' => 'users',       'label' => 'Usuarios',  'color' => 'var(--purple)'],
      ['href' => BASE_URL.'/admin/cursos.php',    'icon' => 'book-open',   'label' => 'Cursos',    'color' => 'var(--green)'],
      ['href' => BASE_URL.'/admin/modulos.php',   'icon' => 'layers',      'label' => 'Módulos',   'color' => 'var(--gold)'],
      ['href' => BASE_URL.'/admin/reportes.php',  'icon' => 'bar-chart-2', 'label' => 'Reportes',  'color' => 'var(--coral)'],
      ['href' => BASE_URL.'/mensajes/',           'icon' => 'mail',        'label' => 'Mensajes',  'color' => 'var(--accent)'],
    ];
    foreach ($links as $l): ?>
    <a href="<?= $l['href'] ?>"
       style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid var(--bg-border);text-decoration:none;transition:border-color .15s,transform .15s;background:var(--bg-elevated)"
       onmouseover="this.style.borderColor='<?= $l['color'] ?>55';this.style.transform='translateY(-1px)'"
       onmouseout="this.style.borderColor='var(--bg-border)';this.style.transform=''">
      <div style="width:34px;height:34px;border-radius:9px;background:<?= $l['color'] ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i data-lucide="<?= $l['icon'] ?>" style="width:16px;height:16px;color:<?= $l['color'] ?>"></i>
      </div>
      <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:13px;color:var(--text-primary)"><?= $l['label'] ?></span>
    </a>
    <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- Old quick links hidden, replaced above -->
<div style="display:none">
<!-- Quick links -->
<div style="margin-bottom:10px">
  <h2 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--text-primary)">Acceso rápido</h2>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">
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
   class="card"
   style="display:flex;align-items:center;gap:12px;text-decoration:none;padding:16px;transition:border-color .2s,transform .15s"
   onmouseover="this.style.borderColor='<?= $l['color'] ?>44';this.style.transform='translateY(-2px)'"
   onmouseout="this.style.borderColor='';this.style.transform=''">
  <div style="width:38px;height:38px;border-radius:10px;background:<?= $l['color'] ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
    <i data-lucide="<?= $l['icon'] ?>" style="width:18px;height:18px;color:<?= $l['color'] ?>"></i>
  </div>
  <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:var(--text-primary)"><?= $l['label'] ?></span>
</a>
<?php endforeach; ?>
</div>
</div><!-- end hidden old quick links -->

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const BASE = '<?= BASE_URL ?>';
  // Read theme-aware colors
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
  const tickColor = isDark ? '#7a8aa8' : '#8898aa';

  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.color = tickColor;

  async function loadChart(url, canvasId, loadId, buildFn) {
    try {
      const r = await fetch(BASE + url);
      if (!r.ok) throw new Error();
      const d = await r.json();
      document.getElementById(loadId).remove();
      const canvas = document.getElementById(canvasId);
      canvas.style.display = '';
      buildFn(canvas, d, gridColor);
    } catch(e) {
      const el = document.getElementById(loadId);
      if (el) el.textContent = 'Sin datos disponibles';
    }
  }

  loadChart('/api/stats.php?type=completions_by_course', 'chartCursos', 'load1', (canvas, d, grid) => {
    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: d.labels,
        datasets: [{
          label: 'Completados',
          data: d.values,
          backgroundColor: d.colors.map(c => c + 'bb'),
          borderColor: d.colors,
          borderWidth: 2,
          borderRadius: 8,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1, color: tickColor }, grid: { color: grid } },
          x: { grid: { display: false }, ticks: { color: tickColor } }
        }
      }
    });
  });

  loadChart('/api/stats.php?type=daily_activity', 'chartActividad', 'load2', (canvas, d, grid) => {
    new Chart(canvas, {
      type: 'line',
      data: {
        labels: d.labels,
        datasets: [{
          label: 'Completaciones',
          data: d.values,
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
          y: { beginAtZero: true, ticks: { stepSize: 1, color: tickColor }, grid: { color: grid } },
          x: { grid: { display: false }, ticks: { color: tickColor } }
        }
      }
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
