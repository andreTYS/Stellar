<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('docente');

$user = currentUser();
$pdo  = getDB();

// All classrooms for this teacher
$aulas = $pdo->prepare("
    SELECT a.*, col.nombre as colegio_nombre,
           (SELECT COUNT(*) FROM estudiante_aula ea WHERE ea.aula_id=a.id) as total_est
    FROM aulas a JOIN colegios col ON col.id=a.colegio_id
    WHERE a.docente_id=?
");
$aulas->execute([$user['id']]);
$aulas = $aulas->fetchAll();

// Progress per aula per course
$progresoCurso = [];
foreach ($aulas as $aula) {
    $stmt = $pdo->prepare("
        SELECT c.nombre as curso_nombre, c.color_hex,
               COUNT(DISTINCT pe.estudiante_id) as completadores,
               COUNT(DISTINCT ea.estudiante_id) as total
        FROM cursos c
        CROSS JOIN estudiante_aula ea
        LEFT JOIN modulos m ON m.curso_id = c.id
        LEFT JOIN progreso_estudiante pe ON pe.modulo_id=m.id AND pe.estudiante_id=ea.estudiante_id AND pe.completado=1
        WHERE ea.aula_id=?
        GROUP BY c.id, c.nombre, c.color_hex
    ");
    $stmt->execute([$aula['id']]);
    $progresoCurso[$aula['id']] = $stmt->fetchAll();
}

$pageTitle = 'Reportes del Aula';
$activeNav = 'reportes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
  <div class="card" style="margin-bottom:24px;">
    <h1 style="font-family:'Syne',sans-serif; font-size:22px; font-weight:800; color:var(--text-primary); margin-bottom:4px;">Reportes del aula</h1>
    <p style="color:var(--text-secondary); font-size:13px;">Progreso por curso para cada aula que coordinas</p>
  </div>

  <?php foreach ($aulas as $aula): ?>
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header">
      <h2 class="card-title">
        <?= $aula['grado'] ?> &ldquo;<?= sanitize($aula['seccion']) ?>&rdquo; — <?= sanitize($aula['colegio_nombre']) ?>
      </h2>
      <span style="font-size:13px; color:var(--text-secondary);"><?= $aula['total_est'] ?> estudiantes</span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:12px;">
      <?php foreach ($progresoCurso[$aula['id']] as $cp): ?>
      <?php $pct = $cp['total'] > 0 ? round($cp['completadores'] / $cp['total'] * 100) : 0; ?>
      <div style="background:var(--bg-elevated); border-radius:12px; padding:16px; border:1px solid var(--bg-border);">
        <p style="font-size:13px; font-weight:600; color:<?= $cp['color_hex'] ?>; margin-bottom:8px;"><?= sanitize($cp['curso_nombre']) ?></p>
        <div class="progress-track" style="margin-bottom:8px;">
          <div class="progress-fill" style="width:<?= $pct ?>%; background:<?= $cp['color_hex'] ?>;"></div>
        </div>
        <p style="font-size:12px; color:var(--text-secondary);"><?= $cp['completadores'] ?>/<?= $cp['total'] ?> · <?= $pct ?>%</p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <div style="text-align:center; margin-top:8px;">
    <button onclick="window.print()" class="btn-ghost" style="padding:10px 24px;">🖨️ Imprimir reporte</button>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
