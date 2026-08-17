<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$pdo = getDB();

// Global stats
$stats = [
    'total_estudiantes'   => $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='estudiante' AND activo=1")->fetchColumn(),
    'modulos_completados' => $pdo->query("SELECT COUNT(*) FROM progreso_estudiante WHERE completado=1")->fetchColumn(),
    'entregables'         => $pdo->query("SELECT COUNT(*) FROM entregables")->fetchColumn(),
    'certificados'        => $pdo->query("SELECT COUNT(*) FROM certificados")->fetchColumn(),
];

// Progress per course
$porCurso = $pdo->query("
    SELECT c.nombre, c.color_hex, c.icono,
           COUNT(DISTINCT pe.id) as completaciones,
           COUNT(DISTINCT m.id) as modulos
    FROM cursos c
    LEFT JOIN modulos m ON m.curso_id=c.id
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id=m.id AND pe.completado=1
    GROUP BY c.id ORDER BY c.id
")->fetchAll();

// Progress per school
$porColegio = $pdo->query("
    SELECT col.nombre, col.distrito,
           COUNT(DISTINCT u.id) as estudiantes,
           COUNT(DISTINCT pe.id) as completaciones
    FROM colegios col
    LEFT JOIN usuarios u ON u.colegio_id=col.id AND u.rol='estudiante'
    LEFT JOIN progreso_estudiante pe ON pe.estudiante_id=u.id AND pe.completado=1
    WHERE col.activo=1
    GROUP BY col.id ORDER BY completaciones DESC
")->fetchAll();

$pageTitle = 'Reportes Globales';
$activeNav = 'reportes';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-content">
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)"><i data-lucide="graduation-cap" style="width:22px;height:22px"></i></div><div class="stat-value" style="color:var(--blue);"><?= $stats['total_estudiantes'] ?></div><div class="stat-label">Estudiantes activos</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)"><i data-lucide="check-circle-2" style="width:22px;height:22px"></i></div><div class="stat-value" style="color:var(--green);"><?= $stats['modulos_completados'] ?></div><div class="stat-label">Módulos completados</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)"><i data-lucide="folder" style="width:22px;height:22px"></i></div><div class="stat-value" style="color:var(--purple);"><?= $stats['entregables'] ?></div><div class="stat-label">Entregables subidos</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)"><i data-lucide="award" style="width:22px;height:22px"></i></div><div class="stat-value" style="color:var(--gold);"><?= $stats['certificados'] ?></div><div class="stat-label">Certificados emitidos</div></div>
  </div>

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">
    <!-- Por curso -->
    <div class="card">
      <div class="card-header"><h2 class="card-title">Completaciones por curso</h2></div>
      <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($porCurso as $c): ?>
        <div>
          <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
            <span style="font-size:13px; font-weight:600; color:var(--text-primary);"><?= $c['icono'] ?> <?= sanitize($c['nombre']) ?></span>
            <span style="font-size:13px; font-weight:700; color:<?= $c['color_hex'] ?>;"><?= $c['completaciones'] ?></span>
          </div>
          <div class="progress-track">
            <?php $max = max(1, max(array_column($porCurso, 'completaciones'))); ?>
            <div class="progress-fill" style="width:<?= round($c['completaciones']/$max*100) ?>%; background:<?= $c['color_hex'] ?>;"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Por colegio -->
    <div class="card">
      <div class="card-header"><h2 class="card-title">Rendimiento por colegio</h2></div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Colegio</th><th>Estudiantes</th><th>Completaciones</th></tr></thead>
          <tbody>
            <?php foreach ($porColegio as $col): ?>
            <tr>
              <td>
                <div style="font-size:13px; font-weight:600;"><?= sanitize($col['nombre']) ?></div>
                <div style="font-size:11px; color:var(--text-secondary);"><?= sanitize($col['distrito']) ?></div>
              </td>
              <td style="color:var(--blue); font-weight:700;"><?= $col['estudiantes'] ?></td>
              <td style="color:var(--green); font-weight:700;"><?= $col['completaciones'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div style="text-align:center;">
    <button onclick="window.print()" class="btn-ghost" style="padding:10px 24px;"><i data-lucide="printer" style="width:16px;height:16px"></i> Imprimir reporte global</button>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
