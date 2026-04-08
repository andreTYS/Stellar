<?php
require_once '../includes/config.php';
requireLogin('docente');

$user = currentUser();
$pdo  = getDB();

// Aulas del docente
$aulas = $pdo->prepare("
    SELECT a.*, col.nombre as colegio_nombre,
           (SELECT COUNT(*) FROM estudiante_aula ea WHERE ea.aula_id = a.id) as total_estudiantes,
           (SELECT COUNT(*) FROM progreso_estudiante pe
            JOIN estudiante_aula ea2 ON ea2.estudiante_id = pe.estudiante_id
            WHERE ea2.aula_id = a.id AND pe.completado = 1) as modulos_completados
    FROM aulas a
    JOIN colegios col ON col.id = a.colegio_id
    WHERE a.docente_id = ?
");
$aulas->execute([$user['id']]);
$aulas = $aulas->fetchAll();

// Módulos asignados a mis aulas
$modulosRecientes = $pdo->prepare("
    SELECT m.titulo, c.nombre as curso_nombre, c.color_hex,
           COUNT(DISTINCT pe.estudiante_id) as completados,
           (SELECT COUNT(*) FROM estudiante_aula ea WHERE ea.aula_id = am.aula_id) as total,
           am.fecha_planificada
    FROM aula_modulos am
    JOIN modulos m ON m.id = am.modulo_id
    JOIN cursos c ON c.id = m.curso_id
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id = am.modulo_id AND pe.completado = 1
    WHERE am.aula_id IN (SELECT id FROM aulas WHERE docente_id = ?)
    GROUP BY am.aula_id, am.modulo_id, m.titulo, c.nombre, c.color_hex, am.fecha_planificada
    ORDER BY am.fecha_planificada DESC
    LIMIT 10
");
$modulosRecientes->execute([$user['id']]);
$modulosRecientes = $modulosRecientes->fetchAll();

$totalEstudiantes = array_sum(array_column($aulas, 'total_estudiantes'));
$totalCompletados = array_sum(array_column($aulas, 'modulos_completados'));

$pageTitle = 'Dashboard Docente';
$activeNav = 'dashboard';
require_once '../includes/header.php';
?>

<div class="page-content">
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">🏫</div>
      <div class="stat-value" style="color:var(--blue);"><?= count($aulas) ?></div>
      <div class="stat-label">Mis aulas</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">👨‍🎓</div>
      <div class="stat-value" style="color:var(--green);"><?= $totalEstudiantes ?></div>
      <div class="stat-label">Estudiantes totales</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-value" style="color:var(--purple);"><?= $totalCompletados ?></div>
      <div class="stat-label">Módulos completados</div>
    </div>
  </div>

  <!-- Mis aulas -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header">
      <h2 class="card-title">Mis aulas</h2>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px;">
      <?php foreach ($aulas as $aula): ?>
      <div style="background:var(--bg-elevated); border:1px solid var(--bg-border); border-radius:12px; padding:20px;">
        <div style="font-family:'Syne',sans-serif; font-size:18px; font-weight:800; color:var(--text-primary); margin-bottom:4px;">
          <?= $aula['grado'] ?> &ldquo;<?= sanitize($aula['seccion']) ?>&rdquo;
        </div>
        <div style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;"><?= sanitize($aula['colegio_nombre']) ?></div>
        <div style="display:flex; justify-content:space-between;">
          <div>
            <div style="font-size:22px; font-weight:800; color:var(--blue);"><?= $aula['total_estudiantes'] ?></div>
            <div style="font-size:11px; color:var(--text-secondary);">estudiantes</div>
          </div>
          <div>
            <div style="font-size:22px; font-weight:800; color:var(--green);"><?= $aula['modulos_completados'] ?></div>
            <div style="font-size:11px; color:var(--text-secondary);">completados</div>
          </div>
        </div>
        <a href="estudiantes.php?aula_id=<?= $aula['id'] ?>" class="btn-ghost" style="display:block; text-align:center; margin-top:16px; padding:8px; font-size:13px; text-decoration:none;">
          Ver estudiantes →
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Progreso de módulos -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Progreso de módulos recientes</h2>
      <a href="portafolios.php" style="font-size:13px; color:var(--blue); text-decoration:none;">Ver portafolios →</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Módulo</th>
            <th>Curso</th>
            <th>Progreso</th>
            <th>Fecha planificada</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($modulosRecientes as $m): ?>
          <tr>
            <td style="font-weight:600;"><?= sanitize($m['titulo']) ?></td>
            <td>
              <span style="background:<?= $m['color_hex'] ?>22; color:<?= $m['color_hex'] ?>; border:1px solid <?= $m['color_hex'] ?>44; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;">
                <?= sanitize($m['curso_nombre']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex; align-items:center; gap:10px;">
                <div class="progress-track" style="flex:1; min-width:80px;">
                  <div class="progress-fill" style="width:<?= $m['total']>0?round($m['completados']/$m['total']*100):0 ?>%; background:<?= $m['color_hex'] ?>;"></div>
                </div>
                <span style="font-size:12px; color:var(--text-secondary); white-space:nowrap;">
                  <?= $m['completados'] ?>/<?= $m['total'] ?>
                </span>
              </div>
            </td>
            <td style="color:var(--text-secondary); font-size:13px;">
              <?= $m['fecha_planificada'] ? date('d/m/Y', strtotime($m['fecha_planificada'])) : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
