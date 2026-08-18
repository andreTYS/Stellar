<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('estudiante');

$estudianteId   = currentUserId();
$user           = currentUser();
$pdo            = getDB();

// ── All courses + progress in ONE consolidated query ──────────
$cursosData = $pdo->prepare("
    SELECT c.id, c.nombre, c.descripcion, c.color_hex,
           COUNT(DISTINCT m.id)                          AS total_modulos,
           COUNT(DISTINCT CASE WHEN pe.completado=1 THEN pe.modulo_id END) AS completados
    FROM cursos c
    LEFT JOIN modulos m ON m.curso_id = c.id
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id = m.id AND pe.estudiante_id = ?
    GROUP BY c.id, c.nombre, c.descripcion, c.color_hex
    ORDER BY c.id
");
$cursosData->execute([$estudianteId]);
$cursosData = $cursosData->fetchAll();

$modulosTotalGlobal      = array_sum(array_column($cursosData, 'total_modulos'));
$modulosCompletadosTotal = array_sum(array_column($cursosData, 'completados'));
$pctGlobal               = $modulosTotalGlobal > 0 ? round($modulosCompletadosTotal / $modulosTotalGlobal * 100) : 0;

$estrellasTotal = getEstrellasTotal($estudianteId);

$pageTitle = 'Mis Cursos';
$activeNav = 'inicio';
include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome banner -->
<div class="welcome-banner mb-32">
  <div class="welcome-text">
    <div class="welcome-title">¡Hola, <?= sanitize($user['nombre'] ?? 'Estudiante') ?>!</div>
    <div class="welcome-sub">Continúa tu aventura de aprendizaje STEAM · <?= $pctGlobal ?>% de progreso global</div>
  </div>
  <div class="welcome-action">
    <a href="<?= BASE_URL ?>/estudiante/certificados.php" class="btn btn-primary">
      <i data-lucide="award" style="width:16px;height:16px"></i>
      Mis certificados
    </a>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid mb-32">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">
      <i data-lucide="star" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--gold)"><?= (int)$estrellasTotal ?></div>
    <div class="stat-label">Estrellas ganadas</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
      <i data-lucide="check-circle-2" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= (int)$modulosCompletadosTotal ?></div>
    <div class="stat-label">Módulos completados</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">
      <i data-lucide="book-open" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--blue)"><?= (int)$modulosTotalGlobal ?></div>
    <div class="stat-label">Módulos en total</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
      <i data-lucide="layers" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--purple)"><?= count($cursosData) ?></div>
    <div class="stat-label">Cursos disponibles</div>
  </div>
</div>

<!-- Courses heading -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:var(--text-primary)">Mis Cursos</h2>
  <span style="font-size:12px;color:var(--text-muted)"><?= count($cursosData) ?> cursos · <?= $pctGlobal ?>% completado</span>
</div>

<?php if (empty($cursosData)): ?>
  <div class="empty-state">
    <div class="empty-icon"><i data-lucide="book-open" style="width:40px;height:40px;opacity:.4"></i></div>
    <p class="empty-state-title">No hay cursos disponibles</p>
    <p class="empty-state-desc">Pronto se habilitarán cursos para ti.</p>
  </div>
<?php else: ?>
<div class="courses-grid">
  <?php foreach ($cursosData as $c):
    $color      = sanitize($c['color_hex'] ?? '#4361ee');
    $total      = (int)$c['total_modulos'];
    $comp       = (int)$c['completados'];
    $pct        = $total > 0 ? round($comp / $total * 100) : 0;
    $initial    = strtoupper(mb_substr(trim($c['nombre'] ?? 'C'), 0, 1));
  ?>
  <a href="<?= BASE_URL ?>/estudiante/curso.php?id=<?= (int)$c['id'] ?>"
     class="course-card" style="--color:<?= $color ?>;text-decoration:none">

    <div class="course-card-header" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>cc);position:relative;overflow:hidden">
      <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:48px;color:rgba(255,255,255,.9);line-height:1;position:relative;z-index:1;user-select:none"><?= htmlspecialchars($initial) ?></span>
      <?php if ($pct === 100): ?>
      <span style="position:absolute;top:12px;right:12px;background:rgba(255,255,255,.25);color:#fff;border-radius:99px;padding:3px 10px;font-size:11px;font-weight:700;display:flex;align-items:center;gap:4px;z-index:1">
        <i data-lucide="check-circle" style="width:12px;height:12px"></i> Completado
      </span>
      <?php endif; ?>
    </div>

    <div class="course-card-body">
      <div class="course-card-name"><?= sanitize($c['nombre']) ?></div>

      <?php if (!empty($c['descripcion'])): ?>
        <p style="font-size:13px;color:var(--text-muted);line-height:1.5;margin-bottom:14px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
          <?= sanitize($c['descripcion']) ?>
        </p>
      <?php endif; ?>

      <div class="progress-track" style="margin-bottom:8px">
        <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
      </div>
      <div class="progress-label">
        <span><?= $comp ?> / <?= $total ?> módulos</span>
        <span style="color:<?= $color ?>;font-weight:600"><?= $pct ?>%</span>
      </div>
    </div>

  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
