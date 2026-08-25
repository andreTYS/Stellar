<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$pdo = getDB();

// Date filter
$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-30 days'));
$hasta = $_GET['hasta'] ?? date('Y-m-d');

// Main stats
$totalEst   = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='estudiante' AND activo=1")->fetchColumn();
$totalDoc   = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='docente' AND activo=1")->fetchColumn();
$modComp    = (int)$pdo->prepare("SELECT COUNT(*) FROM progreso_estudiante WHERE completado=1 AND DATE(completado_en) BETWEEN ? AND ?")->execute([$desde,$hasta]) ? (int)$pdo->query("SELECT COUNT(*) FROM progreso_estudiante WHERE completado=1 AND DATE(completado_en) BETWEEN '$desde' AND '$hasta'")->fetchColumn() : 0;
$totalComp  = (int)$pdo->query("SELECT COUNT(*) FROM progreso_estudiante WHERE completado=1")->fetchColumn();
$entregables = (int)$pdo->query("SELECT COUNT(*) FROM entregables")->fetchColumn();
$certs      = (int)$pdo->query("SELECT COUNT(*) FROM certificados")->fetchColumn();
$totalMod   = (int)$pdo->query("SELECT COUNT(*) FROM modulos WHERE activo=1")->fetchColumn();
$tasaComp   = $totalEst > 0 && $totalMod > 0
    ? round($totalComp / ($totalEst * $totalMod) * 100, 1) : 0;

// Per course
$porCurso = $pdo->query("
    SELECT c.nombre, c.color_hex, c.icono,
           COUNT(DISTINCT m.id) as modulos,
           COUNT(DISTINCT pe.id) as completaciones,
           COUNT(DISTINCT pe.estudiante_id) as estudiantes_activos
    FROM cursos c
    LEFT JOIN modulos m ON m.curso_id=c.id AND m.activo=1
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id=m.id AND pe.completado=1
    WHERE c.activo=1
    GROUP BY c.id ORDER BY completaciones DESC
")->fetchAll();

// Per school
$porColegio = $pdo->query("
    SELECT col.id, col.nombre, col.distrito,
           COUNT(DISTINCT u.id)  as estudiantes,
           COUNT(DISTINCT d.id)  as docentes,
           COUNT(DISTINCT pe.id) as completaciones,
           COUNT(DISTINCT m.id)  as modulos_disponibles
    FROM colegios col
    LEFT JOIN usuarios u  ON u.colegio_id=col.id  AND u.rol='estudiante' AND u.activo=1
    LEFT JOIN usuarios d  ON d.colegio_id=col.id  AND d.rol='docente'    AND d.activo=1
    LEFT JOIN aulas a     ON a.colegio_id=col.id
    LEFT JOIN aula_modulos am ON am.aula_id=a.id
    LEFT JOIN modulos m   ON m.id=am.modulo_id
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id=m.id AND pe.completado=1
    WHERE col.activo=1
    GROUP BY col.id ORDER BY completaciones DESC
")->fetchAll();

// Top students
$topEst = $pdo->query("
    SELECT u.nombre, u.apellido, col.nombre as colegio,
           COUNT(pe.id) as completados
    FROM usuarios u
    LEFT JOIN colegios col ON col.id=u.colegio_id
    LEFT JOIN progreso_estudiante pe ON pe.estudiante_id=u.id AND pe.completado=1
    WHERE u.rol='estudiante' AND u.activo=1
    GROUP BY u.id
    ORDER BY completados DESC LIMIT 10
")->fetchAll();

// Daily completions (last 14 days)
$dailyRows = $pdo->query("
    SELECT DATE(completado_en) as dia, COUNT(*) as total
    FROM progreso_estudiante
    WHERE completado=1 AND completado_en >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(completado_en) ORDER BY dia ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$dailyLabels = [];
$dailyValues = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $dailyLabels[] = date('d/m', strtotime($d));
    $dailyValues[] = $dailyRows[$d] ?? 0;
}

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_global_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
    fputcsv($out, ['Colegio','Distrito','Docentes','Estudiantes','Completaciones']);
    foreach ($porColegio as $col) {
        fputcsv($out, [$col['nombre'],$col['distrito'],$col['docentes'],$col['estudiantes'],$col['completaciones']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Curso','Módulos','Estudiantes activos','Completaciones']);
    foreach ($porCurso as $c) {
        fputcsv($out, [$c['nombre'],$c['modulos'],$c['estudiantes_activos'],$c['completaciones']]);
    }
    fclose($out);
    exit;
}

$pageTitle = 'Reportes Globales';
$activeNav = 'reportes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">Reportes globales</h1>
    <p class="page-subtitle">Análisis de actividad y rendimiento de la plataforma</p>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:8px;align-items:center">
      <input type="date" name="desde" value="<?= $desde ?>" class="form-control" style="width:140px;padding:7px 10px;font-size:12px">
      <span style="color:var(--text-muted);font-size:13px">→</span>
      <input type="date" name="hasta" value="<?= $hasta ?>" class="form-control" style="width:140px;padding:7px 10px;font-size:12px">
      <button type="submit" class="btn btn-ghost" style="padding:7px 14px;font-size:12px">Filtrar</button>
    </form>
    <a href="?desde=<?= $desde ?>&hasta=<?= $hasta ?>&export=csv" class="btn btn-ghost">
      <i data-lucide="download" style="width:15px;height:15px"></i>
      Exportar CSV
    </a>
    <button onclick="window.print()" class="btn btn-ghost">
      <i data-lucide="printer" style="width:15px;height:15px"></i>
      Imprimir
    </button>
  </div>
</div>

<!-- Stats row -->
<div class="stats-grid-6" style="--cols:3" >
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)"><i data-lucide="graduation-cap" style="width:22px;height:22px"></i></div>
    <div class="stat-value" style="color:var(--blue)"><?= number_format($totalEst) ?></div>
    <div class="stat-label">Estudiantes activos</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)"><i data-lucide="check-circle-2" style="width:22px;height:22px"></i></div>
    <div class="stat-value" style="color:var(--green)"><?= number_format($modComp) ?></div>
    <div class="stat-label">Completados (período)</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)"><i data-lucide="layers" style="width:22px;height:22px"></i></div>
    <div class="stat-value" style="color:var(--purple)"><?= number_format($totalComp) ?></div>
    <div class="stat-label">Total completaciones</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)"><i data-lucide="award" style="width:22px;height:22px"></i></div>
    <div class="stat-value" style="color:var(--gold)"><?= number_format($certs) ?></div>
    <div class="stat-label">Certificados emitidos</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(239,68,68,.12);color:var(--danger)"><i data-lucide="folder-open" style="width:22px;height:22px"></i></div>
    <div class="stat-value" style="color:var(--danger)"><?= number_format($entregables) ?></div>
    <div class="stat-label">Entregables subidos</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(67,97,238,.12);color:var(--accent)"><i data-lucide="trending-up" style="width:22px;height:22px"></i></div>
    <div class="stat-value" style="color:var(--accent)"><?= $tasaComp ?>%</div>
    <div class="stat-label">Tasa de completación</div>
  </div>
</div>

<!-- Charts row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
  <!-- Daily activity -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Actividad diaria (últimos 14 días)</h2>
    </div>
    <canvas id="chartDaily" height="180"></canvas>
  </div>
  <!-- By course -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Completaciones por curso</h2>
    </div>
    <canvas id="chartCursos" height="180"></canvas>
  </div>
</div>

<!-- School table + top students -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;margin-bottom:20px">

  <!-- Schools table -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Rendimiento por colegio</h2>
      <span style="font-size:12px;color:var(--text-muted)"><?= count($porColegio) ?> colegios</span>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Colegio</th>
            <th style="text-align:center">Doc.</th>
            <th style="text-align:center">Est.</th>
            <th style="text-align:center">Completaciones</th>
            <th>Progreso</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $maxComp = max(1, max(array_column($porColegio, 'completaciones')));
          foreach ($porColegio as $col):
            $pct = round($col['completaciones'] / $maxComp * 100);
          ?>
          <tr>
            <td>
              <div style="font-size:13px;font-weight:600;color:var(--text-primary)"><?= sanitize($col['nombre']) ?></div>
              <div style="font-size:11px;color:var(--text-muted)"><?= sanitize($col['distrito']) ?></div>
            </td>
            <td style="text-align:center;color:var(--blue);font-weight:700"><?= $col['docentes'] ?></td>
            <td style="text-align:center;color:var(--accent);font-weight:700"><?= $col['estudiantes'] ?></td>
            <td style="text-align:center;color:var(--green);font-weight:700"><?= $col['completaciones'] ?></td>
            <td style="min-width:100px">
              <div class="progress-track" style="height:6px">
                <div class="progress-fill" data-pct="<?= $pct ?>" style="background:var(--accent);width:0%;border-radius:4px;transition:width .8s ease"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top students -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Top 10 estudiantes</h2>
    </div>
    <div style="display:flex;flex-direction:column;gap:0">
      <?php foreach ($topEst as $i => $est): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--bg-border)">
        <span style="width:22px;height:22px;border-radius:50%;background:<?= $i===0?'var(--gold)':($i===1?'#94a3b8':($i===2?'#c97c3a':'var(--bg-card-alt)')) ?>;color:<?= $i<3?'#fff':'var(--text-muted)' ?>;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <?= $i+1 ?>
        </span>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= sanitize($est['nombre'].' '.$est['apellido']) ?>
          </div>
          <div style="font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= sanitize($est['colegio'] ?? '—') ?>
          </div>
        </div>
        <span style="font-size:13px;font-weight:700;color:var(--accent);flex-shrink:0"><?= $est['completados'] ?></span>
      </div>
      <?php endforeach; ?>
      <?php if (empty($topEst)): ?>
      <p style="color:var(--text-muted);font-size:13px;padding:16px 0;text-align:center">Sin datos aún</p>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Course details -->
<div class="card mb-16">
  <div class="card-header">
    <h2 class="card-title">Detalle por curso</h2>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Curso</th>
          <th style="text-align:center">Módulos</th>
          <th style="text-align:center">Estudiantes activos</th>
          <th style="text-align:center">Completaciones</th>
          <th>Barra</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $maxCurso = max(1, max(array_column($porCurso, 'completaciones')));
        foreach ($porCurso as $c):
          $pct = round($c['completaciones'] / $maxCurso * 100);
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <span style="font-size:18px"><?= $c['icono'] ?></span>
              <span style="font-weight:600;font-size:13px"><?= sanitize($c['nombre']) ?></span>
            </div>
          </td>
          <td style="text-align:center;color:var(--text-secondary)"><?= $c['modulos'] ?></td>
          <td style="text-align:center;color:var(--blue);font-weight:700"><?= $c['estudiantes_activos'] ?></td>
          <td style="text-align:center;color:<?= $c['color_hex'] ?>;font-weight:700"><?= $c['completaciones'] ?></td>
          <td>
            <div class="progress-track" style="height:6px">
              <div class="progress-fill" data-pct="<?= $pct ?>" style="background:<?= $c['color_hex'] ?>;width:0%;border-radius:4px;transition:width .8s ease"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof Chart === 'undefined') return;

  const dark = document.documentElement.getAttribute('data-theme') === 'dark';
  const grid  = dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
  const tick  = dark ? '#94a3b8' : '#64748b';

  // Daily activity line chart
  new Chart(document.getElementById('chartDaily'), {
    type: 'line',
    data: {
      labels: <?= json_encode($dailyLabels) ?>,
      datasets: [{
        label: 'Completaciones',
        data: <?= json_encode($dailyValues) ?>,
        borderColor: '#4361ee',
        backgroundColor: 'rgba(67,97,238,.12)',
        fill: true,
        tension: .4,
        pointRadius: 4,
        pointBackgroundColor: '#4361ee',
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: grid }, ticks: { color: tick, font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick, font: { size: 11 }, stepSize: 1 } }
      }
    }
  });

  // By course bar chart
  new Chart(document.getElementById('chartCursos'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_map(fn($c) => $c['nombre'], $porCurso)) ?>,
      datasets: [{
        label: 'Completaciones',
        data: <?= json_encode(array_column($porCurso, 'completaciones')) ?>,
        backgroundColor: <?= json_encode(array_map(fn($c) => $c['color_hex'].'cc', $porCurso)) ?>,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: tick, font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: grid }, ticks: { color: tick, font: { size: 11 }, stepSize: 1 } }
      }
    }
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
