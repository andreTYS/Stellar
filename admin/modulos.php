<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$pdo = getDB();
$cursoId = (int)($_GET['curso'] ?? 0);

// Handle create/edit module
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_modulo') {
        $titulo  = trim($_POST['titulo'] ?? '');
        $cid     = (int)($_POST['curso_id'] ?? 0);
        $orden   = (int)($_POST['orden'] ?? 1);
        $mins    = (int)($_POST['minutos_estimados'] ?? 30);
        $grado   = trim($_POST['grado_ciclo'] ?? '');
        $desc    = trim($_POST['descripcion'] ?? '');
        if ($titulo && $cid) {
            $pdo->prepare('INSERT INTO modulos (curso_id,titulo,descripcion,orden,minutos_estimados,grado_ciclo,activo) VALUES (?,?,?,?,?,?,1)')
                ->execute([$cid,$titulo,$desc,$orden,$mins,$grado]);
            setFlash('success','Módulo creado correctamente.');
        }
        redirect('/innovasteam/admin/modulos.php');
    }

    if ($action === 'toggle_modulo') {
        $mid = (int)($_POST['modulo_id'] ?? 0);
        if ($mid) $pdo->prepare('UPDATE modulos SET activo=IF(activo=1,0,1) WHERE id=?')->execute([$mid]);
        redirect('/innovasteam/admin/modulos.php');
    }
}

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

  <!-- Create module form -->
  <details style="background:var(--bg-surface);border:1px solid var(--bg-border);border-radius:14px;padding:0;margin-bottom:24px;overflow:hidden">
    <summary style="padding:16px 20px;cursor:pointer;font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:var(--text-primary);display:flex;align-items:center;gap:8px;list-style:none">
      <i data-lucide="plus-circle" style="width:16px;height:16px;color:var(--accent)"></i>
      Crear nuevo módulo
    </summary>
    <div style="padding:0 20px 20px;border-top:1px solid var(--bg-border)">
      <form method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create_modulo">
        <div class="form-group">
          <label class="form-label">Título del módulo</label>
          <input type="text" name="titulo" class="form-control" required placeholder="Ej: Introducción a la robótica">
        </div>
        <div class="form-group">
          <label class="form-label">Curso</label>
          <select name="curso_id" class="form-control" required>
            <option value="">Selecciona un curso…</option>
            <?php foreach ($cursos as $c): ?>
            <option value="<?= $c['id'] ?>"><?= sanitize($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Descripción breve</label>
          <input type="text" name="descripcion" class="form-control" placeholder="Una o dos oraciones">
        </div>
        <div class="form-group">
          <label class="form-label">Grado / Ciclo</label>
          <input type="text" name="grado_ciclo" class="form-control" placeholder="Ej: 5to - 6to primaria">
        </div>
        <div class="form-group">
          <label class="form-label">Orden</label>
          <input type="number" name="orden" class="form-control" value="1" min="1">
        </div>
        <div class="form-group">
          <label class="form-label">Duración estimada (min)</label>
          <input type="number" name="minutos_estimados" class="form-control" value="30" min="5">
        </div>
        <div style="grid-column:1/-1;display:flex;justify-content:flex-end">
          <button type="submit" class="btn btn-primary">
            <i data-lucide="plus" style="width:15px;height:15px"></i>
            Crear módulo
          </button>
        </div>
      </form>
    </div>
  </details>

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
        <thead><tr><th>Módulo</th><th>Curso</th><th>Pasos</th><th>Grado</th><th>Duración</th><th>Completaciones</th><th></th></tr></thead>
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
            <td>
              <form method="POST" style="display:inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_modulo">
                <input type="hidden" name="modulo_id" value="<?= $m['id'] ?>">
                <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:4px 10px;color:<?= $m['activo']?'var(--danger)':'var(--green)' ?>">
                  <?= $m['activo'] ? 'Desactivar' : 'Activar' ?>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
