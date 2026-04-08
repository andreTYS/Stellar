<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin('docente');

$user   = currentUser();
$pdo    = getDB();
$aulaId = (int)($_GET['aula_id'] ?? 0);

// Verify this aula belongs to this docente
$aula = $pdo->prepare("SELECT a.*, col.nombre as colegio_nombre FROM aulas a JOIN colegios col ON col.id=a.colegio_id WHERE a.id=? AND a.docente_id=?");
$aula->execute([$aulaId, $user['id']]);
$aula = $aula->fetch();
if (!$aula) { redirect('/innovasteam/docente/index.php'); }

// Students with progress
$estudiantes = $pdo->prepare("
    SELECT u.id, u.nombre, u.apellido, u.codigo_acceso,
           COUNT(pe.id) as modulos_iniciados,
           SUM(pe.completado) as modulos_completados,
           COALESCE(SUM(pe.estrellas_quiz), 0) as estrellas_total
    FROM estudiante_aula ea
    JOIN usuarios u ON u.id = ea.estudiante_id
    LEFT JOIN progreso_estudiante pe ON pe.estudiante_id = u.id
    WHERE ea.aula_id = ?
    GROUP BY u.id, u.nombre, u.apellido, u.codigo_acceso
    ORDER BY u.apellido, u.nombre
");
$estudiantes->execute([$aulaId]);
$estudiantes = $estudiantes->fetchAll();

$pageTitle = 'Estudiantes — ' . $aula['grado'] . ' ' . $aula['seccion'];
$activeNav = 'dashboard';
require_once '../includes/header.php';
?>

<div class="page-content">
  <div style="margin-bottom:24px;">
    <a href="index.php" style="color:var(--text-secondary); font-size:13px; text-decoration:none;">← Volver al dashboard</a>
  </div>

  <div class="card" style="margin-bottom:24px; padding:20px 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <div>
        <h1 style="font-family:'Syne',sans-serif; font-size:22px; font-weight:800; color:var(--text-primary);">
          <?= $aula['grado'] ?> &ldquo;<?= sanitize($aula['seccion']) ?>&rdquo;
        </h1>
        <p style="color:var(--text-secondary); font-size:13px;"><?= sanitize($aula['colegio_nombre']) ?> · <?= count($estudiantes) ?> estudiantes</p>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Estudiante</th>
            <th>Código</th>
            <th>Módulos iniciados</th>
            <th>Completados</th>
            <th>⭐ Estrellas</th>
            <th>Portafolio</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($estudiantes as $est): ?>
          <tr>
            <td>
              <div style="font-weight:600;"><?= sanitize($est['apellido'].', '.$est['nombre']) ?></div>
            </td>
            <td style="font-family:monospace; font-size:13px; color:var(--text-secondary);"><?= sanitize($est['codigo_acceso'] ?? '—') ?></td>
            <td style="color:var(--blue); font-weight:700;"><?= $est['modulos_iniciados'] ?></td>
            <td style="color:var(--green); font-weight:700;"><?= $est['modulos_completados'] ?></td>
            <td style="color:var(--gold); font-weight:700;">
              <?= str_repeat('★', min($est['estrellas_total'], 5)) ?>
              <span style="color:var(--text-secondary); font-size:12px;"><?= $est['estrellas_total'] ?>pts</span>
            </td>
            <td>
              <a href="portafolios.php?estudiante_id=<?= $est['id'] ?>" style="color:var(--blue); font-size:13px; text-decoration:none;">Ver →</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
