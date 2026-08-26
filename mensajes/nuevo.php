<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$uid  = currentUserId();
$rol  = currentRole();
$pdo  = getDB();

$tableOk = false;
try { $pdo->query('SELECT 1 FROM mensajes LIMIT 1'); $tableOk = true; }
catch (\PDOException $e) {}

$errors  = [];
$success = false;

// Pre-fill reply
$reply = null;
$replyId = (int)($_GET['reply'] ?? 0);
if ($replyId > 0 && $tableOk) {
    $stmtR = $pdo->prepare('SELECT * FROM mensajes WHERE id=? AND destinatario_id=?');
    $stmtR->execute([$replyId, $uid]);
    $reply = $stmtR->fetch();
}

// Load recipients (role-appropriate)
$destinatarios = [];
if ($tableOk) {
    $allowedRoles = match($rol) {
        'admin'         => ['admin', 'admin_colegio', 'docente', 'practicante'],
        'admin_colegio' => ['admin', 'docente', 'practicante'],
        'docente'       => ['admin', 'admin_colegio', 'practicante', 'estudiante'],
        'practicante'   => ['admin', 'admin_colegio', 'docente', 'estudiante'],
        'estudiante'    => ['docente', 'practicante'],
        default         => [],
    };
    if (!empty($allowedRoles)) {
        $in = implode(',', array_fill(0, count($allowedRoles), '?'));
        $stmt = $pdo->prepare("SELECT id, nombre, apellido, rol FROM usuarios WHERE rol IN ($in) AND activo=1 AND id!=? ORDER BY rol, apellido");
        $stmt->execute([...$allowedRoles, $uid]);
        $destinatarios = $stmt->fetchAll();
    }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tableOk) {
    verifyCsrf();
    $destId = (int)($_POST['destinatario_id'] ?? 0);
    $asunto = trim($_POST['asunto'] ?? '');
    $cuerpo = trim($_POST['cuerpo'] ?? '');

    if ($destId === 0)   $errors[] = 'Selecciona un destinatario.';
    if ($asunto === '')  $errors[] = 'El asunto es requerido.';
    if ($cuerpo === '')  $errors[] = 'Escribe el mensaje.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO mensajes (remitente_id, destinatario_id, asunto, cuerpo) VALUES (?,?,?,?)');
        $stmt->execute([$uid, $destId, $asunto, $cuerpo]);

        // Create notification for recipient
        try {
            $nombre = trim(($pdo->query("SELECT CONCAT(nombre,' ',apellido) FROM usuarios WHERE id=$uid")->fetchColumn()));
            $pdo->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, url) VALUES (?,?,?,?,?)")
                ->execute([$destId, 'mensaje', 'Nuevo mensaje de ' . $nombre, mb_substr($asunto, 0, 100), BASE_URL . '/mensajes/?id=' . $pdo->lastInsertId()]);
        } catch (\PDOException $e) {}

        $success = true;
    }
}

$rolLabel = fn($r) => match($r) {
    'admin' => 'Admin', 'admin_colegio' => 'Director', 'docente' => 'Docente',
    'practicante' => 'Practicante', 'estudiante' => 'Estudiante', default => $r,
};

$pageTitle = 'Nuevo mensaje';
$activeNav = 'mensajes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">Redactar mensaje</h1>
    <p class="page-subtitle">Envía un mensaje interno a otro usuario</p>
  </div>
  <a href="<?= BASE_URL ?>/mensajes/" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:16px;height:16px"></i>
    Volver
  </a>
</div>

<?php if ($success): ?>
<div class="alert alert-success flash-auto-close mb-24">Mensaje enviado correctamente.</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-24">
  <?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$tableOk): ?>
<div class="alert alert-warning">Tabla de mensajes no encontrada. Ejecuta la migración primero.</div>
<?php else: ?>

<div class="card" style="max-width:680px">
  <form method="POST" action="">
    <?= csrfField() ?>
    <div class="form-group">
      <label class="form-label">Para</label>
      <select name="destinatario_id" class="form-control" required>
        <option value="">Selecciona destinatario…</option>
        <?php
        // ?para=N preselecciona un destinatario. Lo usan los atajos
        // "Escribirle" del panel del docente. Solo surte efecto si el
        // usuario ya está en la lista permitida, así que no amplía a
        // quién se le puede escribir.
        $paraId = (int)($_GET['para'] ?? 0);
        foreach ($destinatarios as $d):
          $presel = (($reply && $d['id'] == $reply['remitente_id'])
                     || ($paraId && (int)$d['id'] === $paraId)) ? 'selected' : '';
        ?>
        <option value="<?= (int)$d['id'] ?>" <?= $presel ?>>
          <?= sanitize($d['nombre'] . ' ' . $d['apellido']) ?> — <?= $rolLabel($d['rol']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Asunto</label>
      <input type="text" name="asunto" class="form-control" required
             value="<?= $reply ? sanitize('Re: ' . $reply['asunto']) : '' ?>"
             placeholder="Escribe el asunto del mensaje">
    </div>
    <div class="form-group">
      <label class="form-label">Mensaje</label>
      <textarea name="cuerpo" class="form-control" rows="8" required
                placeholder="Escribe tu mensaje aquí…"
                style="resize:vertical"></textarea>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <a href="<?= BASE_URL ?>/mensajes/" class="btn btn-ghost">Cancelar</a>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="send" style="width:15px;height:15px"></i>
        Enviar mensaje
      </button>
    </div>
  </form>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
