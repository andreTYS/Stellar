<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin('admin');

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    SELECT col.*, COUNT(DISTINCT u.id) as total_usuarios
    FROM colegios col
    LEFT JOIN usuarios u ON u.colegio_id=col.id AND u.activo=1
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
require_once '../includes/header.php';
?>
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
          <thead><tr><th>Nombre</th><th>Distrito</th><th>Director</th><th>Usuarios</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($colegios as $col): ?>
            <tr>
              <td style="font-weight:600;"><?= sanitize($col['nombre']) ?></td>
              <td style="color:var(--text-secondary);"><?= sanitize($col['distrito']) ?></td>
              <td style="color:var(--text-secondary);"><?= sanitize($col['director'] ?? '—') ?></td>
              <td style="color:var(--blue); font-weight:700;"><?= $col['total_usuarios'] ?></td>
              <td><a href="?edit=<?= $col['id'] ?>" style="color:var(--blue); font-size:12px; text-decoration:none;">Editar</a></td>
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
<?php require_once '../includes/footer.php'; ?>
