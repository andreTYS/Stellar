<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$userId = currentUserId();
$user   = currentUser();
$pdo    = getDB();

$errors  = [];
$success = '';

// ── Handle form submissions ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // Update profile info
    if ($action === 'profile') {
        $nombre   = trim($_POST['nombre']   ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $email    = trim($_POST['email']    ?? '');

        if ($nombre   === '') $errors[] = 'El nombre es requerido.';
        if ($apellido === '') $errors[] = 'El apellido es requerido.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';

        if (empty($errors)) {
            // Check email uniqueness
            if ($email !== '') {
                $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
                $stmtCheck->execute([$email, $userId]);
                if ($stmtCheck->fetch()) $errors[] = 'Ese email ya está en uso.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('UPDATE usuarios SET nombre=?, apellido=?, email=? WHERE id=?');
            $stmt->execute([$nombre, $apellido, $email ?: null, $userId]);
            // Refresh session
            $_SESSION['usuario']['nombre']   = $nombre;
            $_SESSION['usuario']['apellido'] = $apellido;
            $_SESSION['usuario']['email']    = $email;
            $success = 'perfil';
            $user = getUserById($userId);
        }
    }

    // Change password
    if ($action === 'password') {
        $actual   = $_POST['password_actual']   ?? '';
        $nueva    = $_POST['password_nueva']    ?? '';
        $confirma = $_POST['password_confirma'] ?? '';

        if ($actual === '')             $errors[] = 'Ingresa tu contraseña actual.';
        if (strlen($nueva) < 6)        $errors[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        if ($nueva !== $confirma)       $errors[] = 'Las contraseñas no coinciden.';

        if (empty($errors)) {
            $stmtHash = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
            $stmtHash->execute([$userId]);
            $hash = $stmtHash->fetchColumn();
            if (!password_verify($actual, $hash)) $errors[] = 'La contraseña actual es incorrecta.';
        }

        if (empty($errors)) {
            $newHash = password_hash($nueva, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE usuarios SET password_hash=? WHERE id=?')->execute([$newHash, $userId]);
            $success = 'password';
        }
    }
}

$initials = strtoupper(substr($user['nombre'] ?? 'U', 0, 1) . substr($user['apellido'] ?? '', 0, 1));

// Extra stats for students
$profileStats = null;
if (($user['rol'] ?? '') === 'estudiante') {
    try {
        $stEst = $pdo->query("SELECT COALESCE(SUM(estrellas_quiz),0) as estrellas, COUNT(CASE WHEN completado=1 THEN 1 END) as completados FROM progreso_estudiante WHERE estudiante_id=$userId");
        $row = $stEst->fetch();
        $logrosGanados = (int)$pdo->query("SELECT COUNT(*) FROM usuario_logros WHERE usuario_id=$userId")->fetchColumn();
        $profileStats = ['estrellas' => (int)$row['estrellas'], 'completados' => (int)$row['completados'], 'logros' => $logrosGanados];
    } catch (\Throwable $e) {}
}

$rolLabel = match($user['rol'] ?? '') {
    'admin'         => 'Administrador',
    'admin_colegio' => 'Director',
    'docente'       => 'Docente',
    'practicante'   => 'Practicante',
    'estudiante'    => 'Estudiante',
    default         => ucfirst($user['rol'] ?? ''),
};

$pageTitle = 'Mi Perfil';
$activeNav = '';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">Mi Perfil</h1>
    <p class="page-subtitle">Información personal y seguridad de tu cuenta</p>
  </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-24">
  <?php foreach ($errors as $e): ?>
  <div><?= sanitize($e) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php if ($success === 'perfil'): ?>
<div class="alert alert-success flash-auto-close mb-24">Perfil actualizado correctamente.</div>
<?php elseif ($success === 'password'): ?>
<div class="alert alert-success flash-auto-close mb-24">Contraseña actualizada correctamente.</div>
<?php endif; ?>

<div class="sidebar-layout">

  <!-- Avatar / Info panel -->
  <div>
    <div class="card" style="text-align:center;padding:32px 24px">
      <div style="width:72px;height:72px;border-radius:50%;background:var(--accent);color:#fff;font-family:'Syne',sans-serif;font-size:26px;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
        <?= $initials ?>
      </div>
      <div style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:var(--text-primary)">
        <?= sanitize(trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''))) ?>
      </div>
      <div style="font-size:13px;color:var(--text-muted);margin-top:4px"><?= $rolLabel ?></div>
      <?php if (!empty($user['email'])): ?>
      <div style="font-size:12px;color:var(--text-muted);margin-top:6px;display:flex;align-items:center;justify-content:center;gap:5px">
        <i data-lucide="mail" style="width:12px;height:12px"></i>
        <?= sanitize($user['email']) ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($user['codigo_acceso'])): ?>
      <div style="margin-top:14px">
        <span class="badge" style="background:var(--accent-light);color:var(--accent);font-size:12px">
          Código: <?= sanitize($user['codigo_acceso']) ?>
        </span>
      </div>
      <?php endif; ?>
      <?php if ($profileStats): ?>
      <div style="display:flex;justify-content:center;gap:16px;margin-top:20px;padding-top:16px;border-top:1px solid var(--bg-border)">
        <div style="text-align:center">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--gold)"><?= $profileStats['estrellas'] ?></div>
          <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Estrellas</div>
        </div>
        <div style="text-align:center">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--green)"><?= $profileStats['completados'] ?></div>
          <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Módulos</div>
        </div>
        <div style="text-align:center">
          <div style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:var(--purple)"><?= $profileStats['logros'] ?></div>
          <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Logros</div>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/estudiante/logros.php" style="display:inline-flex;align-items:center;gap:5px;margin-top:12px;font-size:12px;color:var(--accent);text-decoration:none;font-weight:600">
        <i data-lucide="trophy" style="width:12px;height:12px"></i>
        Ver mis logros
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Forms -->
  <div style="display:flex;flex-direction:column;gap:20px">

    <!-- Edit profile -->
    <div class="card">
      <div class="card-header">
        <div>
          <h2 class="card-title">Información personal</h2>
          <p class="card-subtitle">Actualiza tu nombre y correo electrónico</p>
        </div>
      </div>
      <form method="POST" action="" novalidate>
        <input type="hidden" name="action" value="profile">
        <?= csrfField() ?>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" required
                   value="<?= sanitize($user['nombre'] ?? '') ?>" placeholder="Tu nombre">
          </div>
          <div class="form-group">
            <label class="form-label">Apellido</label>
            <input type="text" name="apellido" class="form-control" required
                   value="<?= sanitize($user['apellido'] ?? '') ?>" placeholder="Tu apellido">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Correo electrónico</label>
          <input type="email" name="email" class="form-control"
                 value="<?= sanitize($user['email'] ?? '') ?>" placeholder="correo@ejemplo.com">
          <p class="form-hint">Opcional. Puedes usar tu código de acceso para iniciar sesión.</p>
        </div>
        <div style="display:flex;justify-content:flex-end">
          <button type="submit" class="btn btn-primary">
            <i data-lucide="save" style="width:15px;height:15px"></i>
            Guardar cambios
          </button>
        </div>
      </form>
    </div>

    <!-- Change password -->
    <div class="card">
      <div class="card-header">
        <div>
          <h2 class="card-title">Cambiar contraseña</h2>
          <p class="card-subtitle">Usa al menos 6 caracteres</p>
        </div>
      </div>
      <form method="POST" action="" novalidate>
        <input type="hidden" name="action" value="password">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label">Contraseña actual</label>
          <input type="password" name="password_actual" class="form-control" placeholder="••••••••">
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Nueva contraseña</label>
            <input type="password" name="password_nueva" class="form-control" placeholder="Mínimo 6 caracteres">
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar contraseña</label>
            <input type="password" name="password_confirma" class="form-control" placeholder="Repite la contraseña">
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end">
          <button type="submit" class="btn btn-primary">
            <i data-lucide="lock" style="width:15px;height:15px"></i>
            Actualizar contraseña
          </button>
        </div>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
