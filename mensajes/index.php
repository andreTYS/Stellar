<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$uid  = currentUserId();
$user = currentUser();
$pdo  = getDB();

// Graceful if table doesn't exist yet
$tableOk = false;
try { $pdo->query('SELECT 1 FROM mensajes LIMIT 1'); $tableOk = true; }
catch (\PDOException $e) {}

$tab      = $_GET['tab'] ?? 'recibidos';
$mensajes = [];

if ($tableOk) {
    // Mark opened message as read
    $open = (int)($_GET['id'] ?? 0);
    if ($open > 0) {
        $pdo->prepare('UPDATE mensajes SET leido=1 WHERE id=? AND destinatario_id=?')->execute([$open, $uid]);
    }

    if ($tab === 'recibidos') {
        $stmt = $pdo->prepare("
            SELECT m.*, u.nombre AS rem_nombre, u.apellido AS rem_apellido
            FROM mensajes m
            JOIN usuarios u ON u.id = m.remitente_id
            WHERE m.destinatario_id=? AND m.archivado_dest=0
            ORDER BY m.created_at DESC
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT m.*, u.nombre AS rem_nombre, u.apellido AS rem_apellido
            FROM mensajes m
            JOIN usuarios u ON u.id = m.destinatario_id
            WHERE m.remitente_id=? AND m.archivado_rem=0
            ORDER BY m.created_at DESC
        ");
    }
    $stmt->execute([$uid]);
    $mensajes = $stmt->fetchAll();

    // Opened message detail
    $detalle = null;
    if ($open > 0) {
        $stmtD = $pdo->prepare("
            SELECT m.*,
                   ur.nombre AS rem_nombre, ur.apellido AS rem_apellido,
                   ud.nombre AS dest_nombre, ud.apellido AS dest_apellido
            FROM mensajes m
            JOIN usuarios ur ON ur.id = m.remitente_id
            JOIN usuarios ud ON ud.id = m.destinatario_id
            WHERE m.id=? AND (m.remitente_id=? OR m.destinatario_id=?)
        ");
        $stmtD->execute([$open, $uid, $uid]);
        $detalle = $stmtD->fetch();
    }
}

// Unread count
$unread = 0;
if ($tableOk) {
    $stmtU = $pdo->prepare('SELECT COUNT(*) FROM mensajes WHERE destinatario_id=? AND leido=0 AND archivado_dest=0');
    $stmtU->execute([$uid]);
    $unread = (int)$stmtU->fetchColumn();
}

$pageTitle = 'Mensajes';
$activeNav = 'mensajes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">Mensajes</h1>
    <p class="page-subtitle"><?= $unread > 0 ? "$unread sin leer" : 'Bandeja de entrada' ?></p>
  </div>
  <a href="<?= BASE_URL ?>/mensajes/nuevo.php" class="btn btn-primary">
    <i data-lucide="pencil" style="width:16px;height:16px"></i>
    Nuevo mensaje
  </a>
</div>

<?php if (!$tableOk): ?>
<div class="alert alert-warning">
  La tabla de mensajes aún no existe. Ejecuta la migración SQL:<br>
  <code style="font-size:12px">mysql -u root innovasteam &lt; migrations/001_notificaciones_mensajes.sql</code>
</div>
<?php else: ?>

<div class="sidebar-layout">

  <!-- Sidebar tabs -->
  <div>
    <div class="card" style="padding:8px">
      <a href="?tab=recibidos" class="nav-item <?= $tab === 'recibidos' ? 'active' : '' ?>" style="border-radius:8px">
        <i data-lucide="inbox" style="width:16px;height:16px"></i>
        <span>Recibidos</span>
        <?php if ($unread > 0): ?>
        <span class="nav-badge" style="<?= $tab === 'recibidos' ? '' : 'background:var(--danger);color:#fff' ?>"><?= $unread ?></span>
        <?php endif; ?>
      </a>
      <a href="?tab=enviados" class="nav-item <?= $tab === 'enviados' ? 'active' : '' ?>" style="border-radius:8px">
        <i data-lucide="send" style="width:16px;height:16px"></i>
        <span>Enviados</span>
      </a>
    </div>
    <a href="<?= BASE_URL ?>/mensajes/nuevo.php" class="btn btn-primary btn-block mt-12" style="justify-content:center">
      <i data-lucide="pencil" style="width:15px;height:15px"></i>
      Redactar
    </a>
  </div>

  <!-- Message list + detail -->
  <div>
    <?php if (isset($detalle) && $detalle): ?>
    <!-- Detail view -->
    <div class="card mb-16">
      <div class="card-header">
        <div>
          <h2 class="card-title"><?= sanitize($detalle['asunto']) ?></h2>
          <p class="card-subtitle">
            De: <?= sanitize($detalle['rem_nombre'] . ' ' . $detalle['rem_apellido']) ?>
            · Para: <?= sanitize($detalle['dest_nombre'] . ' ' . $detalle['dest_apellido']) ?>
            · <?= date('d/m/Y H:i', strtotime($detalle['created_at'])) ?>
          </p>
        </div>
        <a href="?tab=<?= $tab ?>" class="btn btn-ghost btn-sm">
          <i data-lucide="arrow-left" style="width:14px;height:14px"></i>
          Volver
        </a>
      </div>
      <div style="padding:8px 0;font-size:14px;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap"><?= nl2br(sanitize($detalle['cuerpo'])) ?></div>
      <?php if ($detalle['destinatario_id'] == $uid): ?>
      <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--bg-border)">
        <a href="<?= BASE_URL ?>/mensajes/nuevo.php?reply=<?= (int)$detalle['id'] ?>" class="btn btn-ghost btn-sm">
          <i data-lucide="reply" style="width:14px;height:14px"></i>
          Responder
        </a>
      </div>
      <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- List view -->
    <?php if (empty($mensajes)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i data-lucide="inbox" style="width:40px;height:40px;opacity:.4"></i></div>
      <h3><?= $tab === 'recibidos' ? 'Sin mensajes recibidos' : 'Sin mensajes enviados' ?></h3>
      <p>Los mensajes que recibas aparecerán aquí.</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:0">
      <?php foreach ($mensajes as $m):
        $isUnread = !$m['leido'] && $tab === 'recibidos';
        $nombre = $tab === 'recibidos'
          ? sanitize($m['rem_nombre'] . ' ' . $m['rem_apellido'])
          : sanitize($m['rem_nombre'] . ' ' . $m['rem_apellido']);
      ?>
      <a href="?tab=<?= $tab ?>&id=<?= (int)$m['id'] ?>"
         style="display:flex;align-items:flex-start;gap:14px;padding:14px 16px;border-bottom:1px solid var(--bg-border);text-decoration:none;background:<?= $isUnread ? 'var(--accent-light)' : 'transparent' ?>;transition:background .15s"
         onmouseover="this.style.background='var(--bg-hover)'"
         onmouseout="this.style.background='<?= $isUnread ? 'var(--accent-light)' : 'transparent' ?>'">
        <div style="width:36px;height:36px;border-radius:50%;background:var(--accent);color:#fff;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;text-transform:uppercase">
          <?= strtoupper(mb_substr($m['rem_nombre'], 0, 1)) ?>
        </div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px">
            <span style="font-weight:<?= $isUnread ? '700' : '600' ?>;font-size:13px;color:var(--text-primary)"><?= $nombre ?></span>
            <?php if ($isUnread): ?>
            <span style="width:7px;height:7px;border-radius:50%;background:var(--accent);flex-shrink:0"></span>
            <?php endif; ?>
            <span style="margin-left:auto;font-size:11px;color:var(--text-muted);white-space:nowrap"><?= date('d/m/Y', strtotime($m['created_at'])) ?></span>
          </div>
          <div style="font-size:13px;color:var(--text-secondary);font-weight:<?= $isUnread ? '600' : '400' ?>"><?= sanitize($m['asunto']) ?></div>
          <div style="font-size:12px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:400px;margin-top:2px">
            <?= sanitize(mb_substr(strip_tags($m['cuerpo']), 0, 80)) ?>…
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
