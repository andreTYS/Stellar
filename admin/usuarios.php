<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$pdo = getDB();
$msg = '';

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'crear') {
    $nombre    = sanitize($_POST['nombre']);
    $apellido  = sanitize($_POST['apellido']);
    $email     = sanitize($_POST['email'] ?? '');
    $codigo    = sanitize($_POST['codigo_acceso'] ?? '');
    $rol       = $_POST['rol'];
    $colegio   = (int)($_POST['colegio_id'] ?? 0) ?: null;
    $password  = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        $pdo->prepare("INSERT INTO usuarios (nombre,apellido,email,codigo_acceso,password_hash,rol,colegio_id) VALUES (?,?,?,?,?,?,?)")
            ->execute([$nombre,$apellido,$email ?: null,$codigo ?: null,$password,$rol,$colegio]);
        $msg = 'success:Usuario creado correctamente.';
    } catch (Exception $e) {
        $msg = 'error:Error al crear usuario: '.$e->getMessage();
    }
}

// Toggle active
if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE usuarios SET activo = NOT activo WHERE id=?")->execute([(int)$_GET['toggle']]);
    redirect('/innovasteam/admin/usuarios.php');
}

$rolFilter = $_GET['rol'] ?? '';
$q = $_GET['q'] ?? '';

$where = '1=1';
$params = [];
if ($rolFilter) { $where .= ' AND u.rol=?'; $params[] = $rolFilter; }
if ($q) { $where .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }

$usuarios = $pdo->prepare("
    SELECT u.*, col.nombre as colegio_nombre
    FROM usuarios u
    LEFT JOIN colegios col ON col.id=u.colegio_id
    WHERE $where
    ORDER BY u.rol, u.apellido, u.nombre
    LIMIT 100
");
$usuarios->execute($params);
$usuarios = $usuarios->fetchAll();

$colegios = $pdo->query("SELECT id, nombre FROM colegios WHERE activo=1 ORDER BY nombre")->fetchAll();

[$msgType,$msgText] = $msg ? explode(':', $msg, 2) : ['',''];

$pageTitle = 'Gestión de Usuarios';
$activeNav = 'usuarios';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
  <?php if ($msgText): ?>
  <div style="background:<?=$msgType==='success'?'#22c55e22':'#ef444422'?>; border:1px solid <?=$msgType==='success'?'#22c55e44':'#ef444444'?>; color:<?=$msgType==='success'?'var(--success)':'var(--danger)'?>; border-radius:10px; padding:12px 16px; margin-bottom:20px; font-size:14px;">
    <?= sanitize($msgText) ?>
  </div>
  <?php endif; ?>

  <div style="display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap;">
    <!-- Search -->
    <form method="GET" style="display:flex; gap:8px; flex:1; min-width:200px;">
      <input type="search" name="q" value="<?= sanitize($q) ?>" placeholder="Buscar por nombre o email..."
        style="flex:1; background:var(--bg-elevated); border:1px solid var(--bg-border); color:var(--text-primary); border-radius:8px; padding:8px 12px; font-size:13px;">
      <select name="rol" style="background:var(--bg-elevated); border:1px solid var(--bg-border); color:var(--text-primary); border-radius:8px; padding:8px; font-size:13px;">
        <option value="">Todos los roles</option>
        <?php foreach(['admin','admin_colegio','docente','practicante','estudiante'] as $r): ?>
          <option value="<?=$r?>" <?=$r===$rolFilter?'selected':''?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-primary" style="padding:8px 14px; font-size:13px;">Buscar</button>
    </form>
    <button onclick="document.getElementById('modal-crear').style.display='flex'" class="btn-primary" style="padding:8px 16px; font-size:13px;">
      + Nuevo usuario
    </button>
  </div>

  <div class="card">
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Nombre</th><th>Email / Código</th><th>Rol</th><th>Colegio</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
          <tr style="opacity:<?= $u['activo']?1:0.5 ?>">
            <td style="font-weight:600;"><?= sanitize($u['apellido'].', '.$u['nombre']) ?></td>
            <td style="font-size:12px; color:var(--text-secondary); font-family:monospace;">
              <?= sanitize($u['email'] ?? $u['codigo_acceso'] ?? '—') ?>
            </td>
            <td>
              <?php $rColors = ['admin'=>'var(--coral)','admin_colegio'=>'var(--gold)','docente'=>'var(--blue)','practicante'=>'var(--green)','estudiante'=>'var(--purple)']; ?>
              <span style="background:<?= $rColors[$u['rol']] ?>22; color:<?= $rColors[$u['rol']] ?>; border:1px solid <?= $rColors[$u['rol']] ?>44; padding:2px 10px; border-radius:20px; font-size:11px; font-weight:700;">
                <?= $u['rol'] ?>
              </span>
            </td>
            <td style="color:var(--text-secondary); font-size:13px;"><?= sanitize($u['colegio_nombre'] ?? '—') ?></td>
            <td><span style="color:<?= $u['activo']?'var(--success)':'var(--danger)' ?>"><?= $u['activo']?'Activo':'Inactivo' ?></span></td>
            <td>
              <a href="?toggle=<?= $u['id'] ?>" style="color:var(--text-secondary); font-size:12px; text-decoration:none;" onclick="return confirm('¿Cambiar estado del usuario?')">
                <?= $u['activo']?'Desactivar':'Activar' ?>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal crear usuario -->
<div id="modal-crear" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:200; align-items:center; justify-content:center;">
  <div style="background:var(--bg-surface); border:1px solid var(--bg-border); border-radius:16px; padding:32px; width:100%; max-width:480px; max-height:90vh; overflow-y:auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
      <h2 style="font-family:'Syne',sans-serif; font-size:18px; font-weight:700; color:var(--text-primary);">Nuevo usuario</h2>
      <button onclick="document.getElementById('modal-crear').style.display='none'" style="background:none; border:none; color:var(--text-secondary); cursor:pointer; font-size:20px;"><i data-lucide="x" style="width:18px;height:18px"></i></button>
    </div>
    <form method="POST" style="display:flex; flex-direction:column; gap:14px;">
      <input type="hidden" name="action" value="crear">
      <?php foreach([['nombre','Nombre'],['apellido','Apellido'],['email','Email'],['codigo_acceso','Código de acceso (estudiantes)'],['password','Contraseña']] as [$fname,$flabel]): ?>
      <div>
        <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px; text-transform:uppercase; letter-spacing:.05em;"><?= $flabel ?></label>
        <input type="<?= $fname==='password'?'password':'text' ?>" name="<?= $fname ?>" <?= in_array($fname,['nombre','apellido','password'])?'required':'' ?>
          style="width:100%; background:var(--bg-elevated); border:1px solid var(--bg-border); color:var(--text-primary); border-radius:8px; padding:10px 12px; font-size:13px;">
      </div>
      <?php endforeach; ?>
      <div>
        <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px; text-transform:uppercase; letter-spacing:.05em;">Rol</label>
        <select name="rol" required style="width:100%; background:var(--bg-elevated); border:1px solid var(--bg-border); color:var(--text-primary); border-radius:8px; padding:10px;">
          <?php foreach(['admin','admin_colegio','docente','practicante','estudiante'] as $r): ?>
            <option value="<?=$r?>"><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px; text-transform:uppercase; letter-spacing:.05em;">Colegio</label>
        <select name="colegio_id" style="width:100%; background:var(--bg-elevated); border:1px solid var(--bg-border); color:var(--text-primary); border-radius:8px; padding:10px;">
          <option value="">Sin colegio</option>
          <?php foreach($colegios as $c): ?>
            <option value="<?= $c['id'] ?>"><?= sanitize($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn-primary" style="margin-top:8px; padding:12px;">Crear usuario</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
