<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('estudiante');

$uid = currentUserId();
$pdo = getDB();

// All logros with earned status
$logros = [];
try {
    $stmt = $pdo->prepare("
        SELECT l.*, ul.ganado_en
        FROM logros l
        LEFT JOIN usuario_logros ul ON ul.logro_id=l.id AND ul.usuario_id=?
        ORDER BY ul.ganado_en DESC, l.id ASC
    ");
    $stmt->execute([$uid]);
    $logros = $stmt->fetchAll();
} catch (\Throwable $e) {}

$ganados = count(array_filter($logros, fn($l) => !empty($l['ganado_en'])));
$total   = count($logros);

$pageTitle = 'Mis Logros';
$activeNav = 'logros';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">Mis Logros</h1>
    <p class="page-subtitle"><?= $ganados ?> de <?= $total ?> insignias desbloqueadas</p>
  </div>
  <a href="<?= BASE_URL ?>/estudiante/index.php" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:16px;height:16px"></i>
    Inicio
  </a>
</div>

<!-- Progress bar -->
<div style="background:var(--bg-surface);border:1px solid var(--bg-border);border-radius:14px;padding:20px 24px;margin-bottom:28px;display:flex;align-items:center;gap:20px">
  <div style="flex:1">
    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
      <span style="font-size:13px;font-weight:600;color:var(--text-primary)">Progreso de logros</span>
      <span style="font-size:13px;font-weight:700;color:var(--gold)"><?= $ganados ?> / <?= $total ?></span>
    </div>
    <div class="progress-track" style="height:8px">
      <div class="progress-fill" style="width:<?= $total>0?round($ganados/$total*100):0 ?>%;background:linear-gradient(90deg,var(--gold),#fb923c);border-radius:99px"></div>
    </div>
  </div>
  <div style="text-align:right;flex-shrink:0">
    <div style="font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:var(--gold)"><?= $total>0?round($ganados/$total*100):0 ?>%</div>
    <div style="font-size:11px;color:var(--text-muted)">completado</div>
  </div>
</div>

<?php if (empty($logros)): ?>
<div class="empty-state">
  <div class="empty-icon"><i data-lucide="award" style="width:48px;height:48px;opacity:.4"></i></div>
  <h3>Los logros están en camino</h3>
  <p>Completa módulos, quizzes y explora StellarScribe para ganar insignias.</p>
</div>
<?php else: ?>

<!-- Logros earned first, then locked -->
<?php
$earned = array_filter($logros, fn($l) => !empty($l['ganado_en']));
$locked = array_filter($logros, fn($l) => empty($l['ganado_en']));
?>

<?php if ($earned): ?>
<div style="margin-bottom:8px">
  <h2 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--text-primary);margin-bottom:14px;display:flex;align-items:center;gap:8px">
    <i data-lucide="star" style="width:16px;height:16px;color:var(--gold)"></i>
    Desbloqueados (<?= count($earned) ?>)
  </h2>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-bottom:32px">
    <?php foreach ($earned as $l): ?>
    <div style="background:linear-gradient(135deg,var(--bg-surface),var(--bg-elevated));border:1px solid var(--gold)44;border-radius:16px;padding:20px;text-align:center;position:relative;overflow:hidden">
      <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--gold),#fb923c)"></div>
      <div style="font-size:36px;margin-bottom:10px;line-height:1"><?= htmlspecialchars($l['icono'] ?? '🏅') ?></div>
      <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:var(--text-primary);margin-bottom:6px"><?= sanitize($l['nombre']) ?></div>
      <div style="font-size:12px;color:var(--text-muted);line-height:1.5;margin-bottom:10px"><?= sanitize($l['descripcion'] ?? '') ?></div>
      <div style="font-size:10px;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:.06em">
        Ganado <?= $l['ganado_en'] ? date('d/m/Y', strtotime($l['ganado_en'])) : '' ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($locked): ?>
<h2 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--text-muted);margin-bottom:14px;display:flex;align-items:center;gap:8px">
  <i data-lucide="lock" style="width:16px;height:16px"></i>
  Por desbloquear (<?= count($locked) ?>)
</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px">
  <?php foreach ($locked as $l): ?>
  <div style="background:var(--bg-surface);border:1px solid var(--bg-border);border-radius:16px;padding:20px;text-align:center;opacity:.55;filter:grayscale(1)">
    <div style="font-size:36px;margin-bottom:10px;line-height:1"><?= htmlspecialchars($l['icono'] ?? '🔒') ?></div>
    <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:var(--text-primary);margin-bottom:6px"><?= sanitize($l['nombre']) ?></div>
    <div style="font-size:12px;color:var(--text-muted);line-height:1.5;margin-bottom:10px"><?= sanitize($l['descripcion'] ?? '') ?></div>
    <div style="font-size:10px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em;display:flex;align-items:center;justify-content:center;gap:4px">
      <i data-lucide="lock" style="width:10px;height:10px"></i> Bloqueado
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
