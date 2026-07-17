<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('docente');

$user        = currentUser();
$pdo         = getDB();
$estudianteId = (int)($_GET['estudiante_id'] ?? 0);

// Comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentar'])) {
    $entregableId = (int)$_POST['entregable_id'];
    $comentario   = sanitize($_POST['comentario']);
    $calificacion = (int)$_POST['calificacion'];
    $pdo->prepare("UPDATE entregables SET comentario_docente=?, calificacion=?, comentado_por=? WHERE id=?")
        ->execute([$comentario, $calificacion, $user['id'], $entregableId]);
    redirect('/innovasteam/docente/portafolios.php?estudiante_id='.$estudianteId.'&ok=1');
}

$entregables = [];
$estudiante  = null;

if ($estudianteId) {
    $stmt = $pdo->prepare("SELECT id, nombre, apellido FROM usuarios WHERE id=? AND rol='estudiante'");
    $stmt->execute([$estudianteId]);
    $estudiante = $stmt->fetch();

    if ($estudiante) {
        $stmt2 = $pdo->prepare("
            SELECT e.*, m.titulo as modulo_titulo, c.nombre as curso_nombre, c.color_hex, c.icono,
                   u.nombre as comentador_nombre
            FROM entregables e
            JOIN modulos m ON m.id = e.modulo_id
            JOIN cursos c ON c.id = m.curso_id
            LEFT JOIN usuarios u ON u.id = e.comentado_por
            WHERE e.estudiante_id = ?
            ORDER BY c.id, e.subido_en DESC
        ");
        $stmt2->execute([$estudianteId]);
        $entregables = $stmt2->fetchAll();
    }
}

$pageTitle = $estudiante ? 'Portafolio de '.$estudiante['nombre'] : 'Portafolios';
$activeNav = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
  <?php if (isset($_GET['ok'])): ?>
    <div style="background:#22c55e22; border:1px solid #22c55e44; color:#22c55e; border-radius:10px; padding:12px 16px; margin-bottom:20px; font-size:14px;">
      ✅ Comentario guardado correctamente.
    </div>
  <?php endif; ?>

  <?php if ($estudiante): ?>
    <div style="margin-bottom:20px;">
      <a href="index.php" style="color:var(--text-secondary); font-size:13px; text-decoration:none;">← Dashboard</a>
    </div>
    <div class="card" style="margin-bottom:24px;">
      <h1 style="font-family:'Syne',sans-serif; font-size:22px; font-weight:800; color:var(--text-primary);">
        📁 Portafolio de <?= sanitize($estudiante['nombre'].' '.$estudiante['apellido']) ?>
      </h1>
      <p style="color:var(--text-secondary); font-size:13px; margin-top:4px;"><?= count($entregables) ?> entregable(s)</p>
    </div>

    <?php if (empty($entregables)): ?>
      <div style="text-align:center; padding:60px;">
        <div style="font-size:56px; margin-bottom:16px;">📂</div>
        <p style="color:var(--text-secondary);">Este estudiante aún no ha subido entregables.</p>
      </div>
    <?php else: ?>
      <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px;">
        <?php foreach ($entregables as $ent): ?>
        <div class="card" style="padding:20px;">
          <!-- Course badge -->
          <span style="background:<?= $ent['color_hex'] ?>22; color:<?= $ent['color_hex'] ?>; border:1px solid <?= $ent['color_hex'] ?>44; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;">
            <?= $ent['icono'] ?> <?= sanitize($ent['curso_nombre']) ?>
          </span>
          <h3 style="font-size:15px; font-weight:600; color:var(--text-primary); margin:10px 0 4px;"><?= sanitize($ent['modulo_titulo']) ?></h3>
          <p style="font-size:11px; color:var(--text-secondary); margin-bottom:12px;">
            <?= sanitize($ent['formato']) ?> · <?= date('d/m/Y', strtotime($ent['subido_en'])) ?>
          </p>

          <!-- Thumbnail -->
          <?php if ($ent['archivo_url']): ?>
          <a href="<?= sanitize($ent['archivo_url']) ?>" target="_blank">
            <img src="<?= sanitize($ent['archivo_url']) ?>" alt="Entregable"
                 style="width:100%; height:140px; object-fit:cover; border-radius:10px; margin-bottom:12px; border:1px solid var(--bg-border);">
          </a>
          <?php endif; ?>

          <!-- Existing comment -->
          <?php if ($ent['comentario_docente']): ?>
          <div style="background:var(--bg-elevated); border-radius:8px; padding:10px; margin-bottom:12px;">
            <p style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">Tu comentario:</p>
            <p style="font-size:13px; color:var(--text-primary);"><?= sanitize($ent['comentario_docente']) ?></p>
            <?php if ($ent['calificacion']): ?>
            <p style="color:var(--gold); font-size:14px; margin-top:4px;"><?= str_repeat('★', $ent['calificacion']) ?></p>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Comment form -->
          <form method="POST">
            <input type="hidden" name="comentar" value="1">
            <input type="hidden" name="entregable_id" value="<?= $ent['id'] ?>">
            <textarea name="comentario" rows="2" placeholder="Escribe un comentario..."
              style="width:100%; background:var(--bg-elevated); border:1px solid var(--bg-border); border-radius:8px; padding:8px; color:var(--text-primary); font-size:13px; resize:vertical; margin-bottom:8px;"><?= sanitize($ent['comentario_docente'] ?? '') ?></textarea>
            <div style="display:flex; align-items:center; gap:10px;">
              <select name="calificacion" style="background:var(--bg-elevated); border:1px solid var(--bg-border); color:var(--text-primary); border-radius:6px; padding:6px;">
                <option value="0">Sin calificar</option>
                <option value="1" <?= $ent['calificacion']==1?'selected':'' ?>>★ 1</option>
                <option value="2" <?= $ent['calificacion']==2?'selected':'' ?>>★★ 2</option>
                <option value="3" <?= $ent['calificacion']==3?'selected':'' ?>>★★★ 3</option>
                <option value="4" <?= $ent['calificacion']==4?'selected':'' ?>>★★★★ 4</option>
                <option value="5" <?= $ent['calificacion']==5?'selected':'' ?>>★★★★★ 5</option>
              </select>
              <button type="submit" class="btn-primary" style="flex:1; padding:8px; font-size:13px;">Guardar</button>
            </div>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <div class="card">
      <p style="color:var(--text-secondary);">Selecciona un estudiante desde <a href="index.php" style="color:var(--blue);">el dashboard</a>.</p>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
