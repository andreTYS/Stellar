<?php
require_once '../includes/config.php';
requireLogin('admin');

$pdo = getDB();
$cursos = $pdo->query("
    SELECT c.*,
           COUNT(DISTINCT m.id) as total_modulos,
           COUNT(DISTINCT pe.id) as completaciones
    FROM cursos c
    LEFT JOIN modulos m ON m.curso_id=c.id AND m.activo=1
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id=m.id AND pe.completado=1
    WHERE c.activo=1
    GROUP BY c.id
    ORDER BY c.id
")->fetchAll();

$pageTitle = 'Cursos';
$activeNav = 'cursos';
require_once '../includes/header.php';
?>
<div class="page-content">
  <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:20px;">
    <?php foreach ($cursos as $c): ?>
    <div style="background:var(--bg-surface); border:1px solid var(--bg-border); border-radius:16px; overflow:hidden;">
      <div style="height:6px; background:<?= $c['color_hex'] ?>;"></div>
      <div style="padding:24px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
          <span style="font-size:32px;"><?= $c['icono'] ?></span>
          <div>
            <h3 style="font-family:'Syne',sans-serif; font-size:18px; font-weight:800; color:var(--text-primary);"><?= sanitize($c['nombre']) ?></h3>
            <p style="font-size:12px; color:<?= $c['color_hex'] ?>; font-weight:700;">/<?= $c['slug'] ?></p>
          </div>
        </div>
        <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px; line-height:1.5;"><?= sanitize($c['descripcion'] ?? '') ?></p>
        <div style="display:flex; gap:20px;">
          <div>
            <div style="font-size:24px; font-weight:800; color:<?= $c['color_hex'] ?>; font-family:'Syne',sans-serif;"><?= $c['total_modulos'] ?></div>
            <div style="font-size:11px; color:var(--text-secondary);">módulos</div>
          </div>
          <div>
            <div style="font-size:24px; font-weight:800; color:var(--green); font-family:'Syne',sans-serif;"><?= $c['completaciones'] ?></div>
            <div style="font-size:11px; color:var(--text-secondary);">completaciones</div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
