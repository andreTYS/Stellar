<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear') {
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

// Toggle active — vía POST: cambiar estado con un GET permitía
// desactivar a cualquier usuario con una simple etiqueta <img>.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $objetivo = (int)($_POST['usuario_id'] ?? 0);

    // Un admin no puede desactivarse a sí mismo y quedar fuera.
    if ($objetivo > 0 && $objetivo !== currentUserId()) {
        $pdo->prepare('UPDATE usuarios SET activo = NOT activo WHERE id = ?')->execute([$objetivo]);
    } elseif ($objetivo === currentUserId()) {
        setFlash('error', 'No puedes desactivar tu propia cuenta.');
    }

    redirect(BASE_URL . '/admin/usuarios.php?' . http_build_query([
        'q'    => $_POST['q']    ?? '',
        'rol'  => $_POST['rol']  ?? '',
        'page' => $_POST['page'] ?? 1,
    ]));
}

$rolFilter = $_GET['rol'] ?? '';
$q         = $_GET['q']   ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 50;
$offset    = ($page - 1) * $perPage;

$where  = '1=1';
$params = [];
if ($rolFilter) { $where .= ' AND u.rol=?'; $params[] = $rolFilter; }
if ($q) {
    $where .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ? OR u.codigo_acceso LIKE ?)';
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}

$totalFiltered = (int)$pdo->prepare("SELECT COUNT(*) FROM usuarios u WHERE $where")->execute($params)
    ? (int)$pdo->query("SELECT COUNT(*) FROM (SELECT u.id FROM usuarios u WHERE $where " . (!empty($params) ? '' : '') . " LIMIT 999999) sub")->fetchColumn()
    : 0;

// Re-count properly via prepared statement
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios u WHERE $where");
$cntStmt->execute($params);
$totalFiltered = (int)$cntStmt->fetchColumn();
$totalPages    = max(1, (int)ceil($totalFiltered / $perPage));

$usuariosStmt = $pdo->prepare("
    SELECT u.*, col.nombre as colegio_nombre,
           (SELECT COUNT(*) FROM progreso_estudiante pe WHERE pe.estudiante_id=u.id AND pe.completado=1) as completados
    FROM usuarios u
    LEFT JOIN colegios col ON col.id=u.colegio_id
    WHERE $where
    ORDER BY u.rol, u.apellido, u.nombre
    LIMIT $perPage OFFSET $offset
");
$usuariosStmt->execute($params);
$usuarios = $usuariosStmt->fetchAll();

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
    <div class="card-header" style="padding:14px 16px">
      <span style="font-size:13px;color:var(--text-muted)">
        <?= number_format($totalFiltered) ?> usuarios
        <?= $totalPages > 1 ? "· página $page de $totalPages" : '' ?>
      </span>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Email / Código</th>
            <th>Rol</th>
            <th>Colegio</th>
            <th style="text-align:center">Completados</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rColors = ['admin'=>'var(--coral)','admin_colegio'=>'var(--gold)','docente'=>'var(--blue)','practicante'=>'var(--green)','estudiante'=>'var(--purple)'];
          foreach ($usuarios as $u):
          ?>
          <tr style="opacity:<?= $u['activo']?1:0.5 ?>">
            <td style="font-weight:600"><?= sanitize($u['apellido'].', '.$u['nombre']) ?></td>
            <td style="font-size:12px;color:var(--text-secondary);font-family:monospace">
              <?= sanitize($u['email'] ?? $u['codigo_acceso'] ?? '—') ?>
            </td>
            <td>
              <span style="background:<?= $rColors[$u['rol']] ?>22;color:<?= $rColors[$u['rol']] ?>;border:1px solid <?= $rColors[$u['rol']] ?>44;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700">
                <?= $u['rol'] ?>
              </span>
            </td>
            <td style="color:var(--text-secondary);font-size:13px"><?= sanitize($u['colegio_nombre'] ?? '—') ?></td>
            <td style="text-align:center;font-weight:700;color:<?= $u['rol']==='estudiante' ? 'var(--accent)' : 'var(--text-muted)' ?>">
              <?= $u['rol']==='estudiante' ? $u['completados'] : '—' ?>
            </td>
            <td><span style="color:<?= $u['activo']?'var(--success)':'var(--danger)' ?>;font-size:12px"><?= $u['activo']?'Activo':'Inactivo' ?></span></td>
            <td>
              <?php if ((int)$u['id'] === currentUserId()): ?>
                <span style="color:var(--text-muted);font-size:12px">Tu cuenta</span>
              <?php else: ?>
              <form method="POST" style="display:inline" data-no-loading
                    onsubmit="return confirm('¿Cambiar estado del usuario?')">
                <?= csrfField() ?>
                <input type="hidden" name="action"     value="toggle"/>
                <input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>"/>
                <input type="hidden" name="q"          value="<?= sanitize($q) ?>"/>
                <input type="hidden" name="rol"        value="<?= sanitize($rolFilter) ?>"/>
                <input type="hidden" name="page"       value="<?= (int)$page ?>"/>
                <button type="submit"
                        style="background:none;border:none;padding:0;cursor:pointer;color:var(--text-secondary);font-size:12px;font-family:inherit">
                  <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                </button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <!-- Pagination -->
    <div style="display:flex;align-items:center;justify-content:center;gap:6px;padding:16px">
      <?php
      $baseUrl = '?' . http_build_query(array_filter(['q'=>$q,'rol'=>$rolFilter]));
      for ($p = 1; $p <= $totalPages; $p++):
        $active = $p === $page;
      ?>
      <a href="<?= $baseUrl ?>&page=<?= $p ?>"
         style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;font-size:13px;font-weight:<?= $active?700:500 ?>;text-decoration:none;background:<?= $active?'var(--accent)':'var(--bg-card-alt)' ?>;color:<?= $active?'#fff':'var(--text-secondary)' ?>;border:1px solid <?= $active?'var(--accent)':'var(--bg-border)' ?>">
        <?= $p ?>
      </a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
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
      <?= csrfField() ?>
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
