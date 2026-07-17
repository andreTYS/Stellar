<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$pdo = getDB();
$cursoId = (int)($_GET['curso'] ?? 0);

$cursos = $pdo->query("SELECT * FROM cursos WHERE activo=1 ORDER BY id")->fetchAll();

$where = $cursoId ? 'WHERE m.curso_id=?' : 'WHERE 1=1';
$params = $cursoId ? [$cursoId] : [];

$modulos = $pdo->prepare("
    SELECT m.*, c.nombre as curso_nombre, c.color_hex, c.icono,
           COUNT(DISTINCT mp.id) as pasos_count,
           COUNT(DISTINCT pe.id) as completaciones
    FROM modulos m
    JOIN cursos c ON c.id=m.curso_id
    LEFT JOIN modulo_pasos mp ON mp.modulo_id=m.id
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id=m.id AND pe.completado=1
    $where
    GROUP BY m.id
    ORDER BY m.curso_id, m.orden
");
$modulos->execute($params);
$modulos = $modulos->fetchAll();

$pageTitle = 'Módulos';
$activeNav = 'modulos';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-content">
  <!-- Course filter tabs -->
  <div style="display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap;">
    <a href="modulos.php" style="text-decoration:none;">
      <span style="display:inline-flex; align-items:center; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; background:<?= !$cursoId?'linear-gradient(135deg,var(--blue),var(--purple))':'var(--bg-elevated)' ?>; color:<?= !$cursoId?'#fff':'var(--text-secondary)' ?>; border:1px solid <?= !$cursoId?'transparent':'var(--bg-border)' ?>;">
        Todos
      </span>
    </a>
    <?php foreach ($cursos as $c): ?>
    <a href="modulos.php?curso=<?= $c['id'] ?>" style="text-decoration:none;">
      <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; background:<?= $cursoId==$c['id']?$c['color_hex'].'33':'var(--bg-elevated)' ?>; color:<?= $cursoId==$c['id']?$c['color_hex']:'var(--text-secondary)' ?>; border:1px solid <?= $cursoId==$c['id']?$c['color_hex'].'55':'var(--bg-border)' ?>;">
        <?= $c['icono'] ?> <?= sanitize($c['nombre']) ?>
      </span>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title"><?= count($modulos) ?> módulos</h2>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Módulo</th><th>Curso</th><th>Pasos</th><th>Grado</th><th>Duración</th><th>Completaciones</th></tr></thead>
        <tbody>
          <?php foreach ($modulos as $m): ?>
          <tr>
            <td>
              <div style="font-weight:600; color:var(--text-primary);"><?= sanitize($m['titulo']) ?></div>
              <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;"><?= sanitize(substr($m['descripcion'] ?? '', 0, 60)) ?>...</div>
            </td>
            <td>
              <span style="background:<?= $m['color_hex'] ?>22; color:<?= $m['color_hex'] ?>; border:1px solid <?= $m['color_hex'] ?>44; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;">
                <?= $m['icono'] ?> <?= sanitize($m['curso_nombre']) ?>
              </span>
            </td>
            <td style="color:var(--blue); font-weight:700; text-align:center;"><?= $m['pasos_count'] ?>/4</td>
            <td style="color:var(--text-secondary); font-size:13px;"><?= $m['grado_ciclo'] ?></td>
            <td style="color:var(--text-secondary); font-size:13px;"><?= $m['minutos_estimados'] ?> min</td>
            <td style="color:var(--green); font-weight:700; text-align:center;"><?= $m['completaciones'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
