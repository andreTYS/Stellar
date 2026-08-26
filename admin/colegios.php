<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $nombre  = sanitize($_POST['nombre']);
    $distrito = sanitize($_POST['distrito']);
    $ugel    = sanitize($_POST['ugel_codigo'] ?? '');
    $director = sanitize($_POST['director'] ?? '');
    $telefono = sanitize($_POST['telefono'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($id) {
        $pdo->prepare("UPDATE colegios SET nombre=?,distrito=?,ugel_codigo=?,director=?,telefono=? WHERE id=?")
            ->execute([$nombre,$distrito,$ugel,$director,$telefono,$id]);
        $msg = 'success:Colegio actualizado.';
    } else {
        $pdo->prepare("INSERT INTO colegios (nombre,distrito,ugel_codigo,director,telefono) VALUES (?,?,?,?,?)")
            ->execute([$nombre,$distrito,$ugel,$director,$telefono]);
        $msg = 'success:Colegio creado.';
    }
}

$colegios = $pdo->query("
    SELECT col.*,
           COUNT(DISTINCT u.id)  as total_usuarios,
           SUM(u.rol='estudiante' AND u.activo=1) as total_est,
           SUM(u.rol='docente'    AND u.activo=1) as total_doc,
           SUM(u.rol='practicante' AND u.activo=1) as total_prac,
           (SELECT COUNT(*) FROM aulas a WHERE a.colegio_id=col.id) as total_aulas,
           (SELECT COUNT(*) FROM progreso_estudiante pe
            JOIN usuarios ue ON ue.id=pe.estudiante_id AND ue.colegio_id=col.id
            WHERE pe.completado=1) as total_completaciones
    FROM colegios col
    LEFT JOIN usuarios u ON u.colegio_id=col.id
    WHERE col.activo=1
    GROUP BY col.id
    ORDER BY col.nombre
")->fetchAll();

$editData = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM colegios WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch();
}

[$msgType,$msgText] = $msg ? explode(':', $msg, 2) : ['',''];
$pageTitle = 'Colegios';
$activeNav = 'colegios';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">Colegios</h1>
    <p class="page-subtitle"><?= count($colegios) ?> instituciones registradas</p>
  </div>
</div>

<div class="page-content">
  <?php if ($msgText): ?>
  <div style="background:<?=$msgType==='success'?'#22c55e22':'#ef444422'?>; border:1px solid <?=$msgType==='success'?'#22c55e44':'#ef444444'?>; color:<?=$msgType==='success'?'var(--success)':'var(--danger)'?>; border-radius:10px; padding:12px 16px; margin-bottom:20px;">
    <?= sanitize($msgText) ?>
  </div>
  <?php endif; ?>

  <div style="display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start;">
    <!-- List -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Colegios registrados</h2>
        <span style="color:var(--text-secondary); font-size:13px;"><?= count($colegios) ?> colegios</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Distrito</th>
              <th style="text-align:center">Aulas</th>
              <th style="text-align:center">Est.</th>
              <th style="text-align:center">Doc.</th>
              <th style="text-align:center">Complet.</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($colegios as $col): ?>
            <tr>
              <td>
                <div style="font-weight:600;font-size:13px"><?= sanitize($col['nombre']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= sanitize($col['director'] ?? '—') ?></div>
              </td>
              <td style="color:var(--text-secondary);font-size:13px"><?= sanitize($col['distrito']) ?></td>
              <td style="text-align:center;color:var(--gold);font-weight:700"><?= $col['total_aulas'] ?></td>
              <td style="text-align:center;color:var(--accent);font-weight:700"><?= $col['total_est'] ?></td>
              <td style="text-align:center;color:var(--blue);font-weight:700"><?= $col['total_doc'] ?></td>
              <td style="text-align:center;color:var(--green);font-weight:700"><?= $col['total_completaciones'] ?></td>
              <td><a href="?edit=<?= $col['id'] ?>" style="color:var(--accent);font-size:12px;text-decoration:none">Editar</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Form -->
    <div class="card">
      <h2 class="card-title" style="margin-bottom:20px;"><?= $editData ? 'Editar colegio' : 'Nuevo colegio' ?></h2>
      <form method="POST" style="display:flex; flex-direction:column; gap:14px;">
      <?= csrfField() ?>
        <?php if ($editData): ?>
          <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>
        <?php
        $fields = [
          ['nombre','Nombre del colegio','required'],
          ['distrito','Distrito','required'],
          ['ugel_codigo','Código UGEL',''],
          ['director','Director(a)',''],
          ['telefono','Teléfono',''],
        ];
        foreach ($fields as [$fname,$flabel,$req]):
          $val = $editData[$fname] ?? '';
        ?>
        <div>
          <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px; text-transform:uppercase; letter-spacing:.05em;"><?= $flabel ?></label>
          <input type="text" name="<?= $fname ?>" value="<?= sanitize($val) ?>" <?= $req ?>
            style="width:100%; background:var(--bg-elevated); border:1px solid var(--bg-border); color:var(--text-primary); border-radius:8px; padding:10px 12px; font-size:13px;">
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-primary" style="margin-top:8px; padding:12px;">
          <?= $editData ? 'Actualizar' : 'Crear colegio' ?>
        </button>
        <?php if ($editData): ?>
          <a href="colegios.php" class="btn-ghost" style="text-align:center; text-decoration:none; padding:10px;">Cancelar</a>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
