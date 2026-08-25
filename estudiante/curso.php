<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('estudiante');

$estudianteId = currentUserId();

// ── Fetch course by ID ────────────────────────────────────────
$cursoId = (int)($_GET['id'] ?? 0);
if ($cursoId <= 0) {
    redirect(BASE_URL . '/estudiante/index.php');
}

$stmt = getDB()->prepare('SELECT * FROM cursos WHERE id = ? AND activo = 1');
$stmt->execute([$cursoId]);
$curso = $stmt->fetch();

if (!$curso) {
    redirect(BASE_URL . '/estudiante/index.php');
}

// ── Fetch modules + progress ──────────────────────────────────
$modulos = getModulosByCurso($cursoId);

$modulosData = [];
foreach ($modulos as $index => $modulo) {
    $progreso    = getProgreso($estudianteId, $modulo['id']);
    $completado  = !empty($progreso['completado']);
    $enProgreso  = !empty($progreso) && !$completado;

    // Unlock logic: first module is always unlocked; subsequent ones need the previous completed
    if ($index === 0) {
        $desbloqueado = true;
    } else {
        $prevProgreso = getProgreso($estudianteId, $modulos[$index - 1]['id']);
        $desbloqueado = !empty($prevProgreso['completado']);
    }

    $modulosData[] = [
        'modulo'       => $modulo,
        'progreso'     => $progreso,
        'completado'   => $completado,
        'en_progreso'  => $enProgreso,
        'desbloqueado' => $desbloqueado,
        'numero'       => $index + 1,
    ];
}

$completadosCount = count(array_filter($modulosData, fn($m) => $m['completado']));
$totalCount       = count($modulos);
$porcentaje       = $totalCount > 0 ? round(($completadosCount / $totalCount) * 100) : 0;
$color            = sanitize($curso['color_hex'] ?? '#4a9eff');
$initial          = strtoupper(mb_substr(trim($curso['nombre'] ?? 'C'), 0, 1));

// Aula ranking for this course
$ranking = [];
try {
    $pdo = getDB();
    // Find student's aula
    $aulaRow = $pdo->prepare('SELECT ea.aula_id FROM estudiante_aula ea WHERE ea.estudiante_id=? LIMIT 1');
    $aulaRow->execute([$estudianteId]);
    $aulaId = $aulaRow->fetchColumn();
    if ($aulaId) {
        $stRank = $pdo->prepare("
            SELECT u.id, u.nombre, u.apellido,
                   COALESCE(SUM(pe.estrellas_quiz),0) as estrellas,
                   COUNT(CASE WHEN pe.completado=1 THEN 1 END) as completados
            FROM estudiante_aula ea
            JOIN usuarios u ON u.id=ea.estudiante_id
            LEFT JOIN progreso_estudiante pe ON pe.estudiante_id=u.id
                AND pe.modulo_id IN (SELECT id FROM modulos WHERE curso_id=?)
            WHERE ea.aula_id=?
            GROUP BY u.id ORDER BY estrellas DESC, completados DESC LIMIT 10
        ");
        $stRank->execute([$cursoId, $aulaId]);
        $ranking = $stRank->fetchAll();
    }
} catch (\Throwable $e) {}

// ── View ──────────────────────────────────────────────────────
$pageTitle = sanitize($curso['nombre']);
$activeNav = 'inicio';
include __DIR__ . '/../includes/header.php';
?>

<!-- Back link -->
<div class="mb-20">
  <a href="<?= BASE_URL ?>/estudiante/index.php" class="btn btn-ghost btn-sm">
    <i data-lucide="arrow-left" style="width:14px;height:14px"></i>
    Volver a Mis Cursos
  </a>
</div>

<!-- Course hero card -->
<div class="card mb-28"
     style="border-color:<?= $color ?>40;background:linear-gradient(135deg,<?= $color ?>0d,var(--bg-surface))">
  <div class="course-hero-inner">
    <div style="width:72px;height:72px;border-radius:16px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-family:'Syne',sans-serif;font-weight:800;font-size:36px;color:#fff;user-select:none"><?= htmlspecialchars($initial) ?></div>
    <div class="flex-1">
      <h1 class="page-title mb-8" style="color:<?= $color ?>"><?= sanitize($curso['nombre']) ?></h1>
      <?php if (!empty($curso['descripcion'])): ?>
        <p class="page-subtitle mb-16"><?= sanitize($curso['descripcion']) ?></p>
      <?php endif; ?>

      <!-- Progress bar -->
      <div class="progress-track" style="height:8px">
        <div class="progress-fill"
             style="width:<?= $porcentaje ?>%;background:<?= $color ?>"></div>
      </div>
      <div class="progress-label mt-8">
        <span><?= (int)$completadosCount ?> de <?= (int)$totalCount ?> módulos completados</span>
        <span style="color:<?= $color ?>;font-weight:700"><?= $porcentaje ?>%</span>
      </div>
    </div>
  </div>
</div>

<!-- Module list -->
<div class="mb-20">
  <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700">Módulos del curso</h2>
</div>

<?php if (empty($modulosData)): ?>
  <div class="empty-state">
    <div class="empty-state-svg"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></div>
    <h3>Sin módulos disponibles</h3>
    <p>Este curso no tiene módulos publicados aún.</p>
  </div>
<?php else: ?>
<div class="module-list">
  <?php foreach ($modulosData as $item):
    $modulo      = $item['modulo'];
    $desbloqueado = $item['desbloqueado'];
    $completado  = $item['completado'];
    $enProgreso  = $item['en_progreso'];

    if ($completado) {
        $statusClass  = 'completed';
        $statusBadge  = '<span class="badge badge-success">Completado ✓</span>';
        $numberStyle  = "background:var(--success);color:#fff";
    } elseif ($enProgreso) {
        $statusClass  = '';
        $statusBadge  = '<span class="badge badge-info">En progreso</span>';
        $numberStyle  = "background:{$color}30;color:{$color}";
    } elseif ($desbloqueado) {
        $statusClass  = '';
        $statusBadge  = '<span class="badge badge-muted">Disponible</span>';
        $numberStyle  = "background:var(--bg-elevated);color:var(--text-secondary)";
    } else {
        $statusClass  = 'locked';
        $statusBadge  = '<span class="badge badge-muted"><i data-lucide="lock" style="width:10px;height:10px"></i> Bloqueado</span>';
        $numberStyle  = "background:var(--bg-elevated);color:var(--text-muted)";
    }

    $duracion = !empty($modulo['minutos_estimados']) ? (int)$modulo['minutos_estimados'] . ' min' : '';
  ?>

  <?php if ($desbloqueado): ?>
  <a href="<?= BASE_URL ?>/estudiante/modulo.php?id=<?= (int)$modulo['id'] ?>"
     class="module-item <?= $statusClass ?>"
     style="--accent-color:<?= $color ?>">
  <?php else: ?>
  <div class="module-item <?= $statusClass ?>">
  <?php endif; ?>

    <!-- Number badge -->
    <div class="module-number" style="<?= $numberStyle ?>">
      <?php if ($completado): ?>
        <i data-lucide="check" style="width:16px;height:16px"></i>
      <?php elseif (!$desbloqueado): ?>
        <i data-lucide="lock" style="width:14px;height:14px"></i>
      <?php else: ?>
        <?= (int)$item['numero'] ?>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="module-info">
      <div class="module-title"><?= sanitize($modulo['titulo']) ?></div>
      <div class="module-meta">
        <?php if ($duracion): ?>
          <span><i data-lucide="clock" style="width:11px;height:11px;vertical-align:middle"></i> <?= $duracion ?></span>
        <?php endif; ?>
        <span>4 pasos</span>
        <?php if (!empty($modulo['dificultad'])): ?>
          <span><?= sanitize($modulo['dificultad']) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Status -->
    <div class="module-status"><?= $statusBadge ?></div>

    <?php if ($desbloqueado && !$completado): ?>
      <i data-lucide="chevron-right" style="width:16px;height:16px;color:var(--text-muted);flex-shrink:0"></i>
    <?php endif; ?>

  <?php if ($desbloqueado): ?>
  </a>
  <?php else: ?>
  </div>
  <?php endif; ?>

  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($ranking)): ?>
<!-- Aula ranking for this course -->
<div class="card" style="margin-top:28px;border-color:<?= $color ?>33">
  <div class="card-header">
    <div>
      <h2 class="card-title" style="display:flex;align-items:center;gap:8px">
        <i data-lucide="trophy" style="width:16px;height:16px;color:var(--gold)"></i>
        Ranking de mi aula
      </h2>
      <p class="card-subtitle">Posición en <?= sanitize($curso['nombre']) ?></p>
    </div>
  </div>
  <div style="display:flex;flex-direction:column;gap:6px">
    <?php foreach ($ranking as $i => $est):
      $isMe = $est['id'] == $estudianteId;
      $medal = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '#' . ($i+1) };
    ?>
    <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;background:<?= $isMe ? $color.'18' : 'var(--bg-elevated)' ?>;border:1px solid <?= $isMe ? $color.'44' : 'transparent' ?>">
      <span style="width:28px;text-align:center;font-size:<?= $i<3?'16':'12' ?>px;font-weight:700;flex-shrink:0"><?= $medal ?></span>
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:<?= $isMe?'700':'500' ?>;color:<?= $isMe ? $color : 'var(--text-primary)' ?>;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= sanitize($est['nombre'] . ' ' . $est['apellido']) ?><?= $isMe ? ' (tú)' : '' ?>
        </div>
        <div style="font-size:10px;color:var(--text-muted)"><?= (int)$est['completados'] ?> módulos completados</div>
      </div>
      <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
        <span style="font-size:14px;font-weight:700;color:var(--gold)"><?= (int)$est['estrellas'] ?></span>
        <i data-lucide="star" style="width:13px;height:13px;color:var(--gold);fill:var(--gold)"></i>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
