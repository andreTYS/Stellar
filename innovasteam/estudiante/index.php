<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('estudiante');

$estudianteId = currentUserId();
$user         = currentUser();

// ── Data ─────────────────────────────────────────────────────
$cursos        = getAllCursos();
$estrellasTotal = getEstrellasTotal($estudianteId);

// Build course data with progress
$cursosData = [];
foreach ($cursos as $curso) {
    $modulos    = getModulosByCurso($curso['id']);
    $total      = count($modulos);
    $completados = getModulosCompletadosByCurso($estudianteId, $curso['id']);
    $porcentaje = $total > 0 ? round(($completados / $total) * 100) : 0;

    $cursosData[] = [
        'curso'      => $curso,
        'total'      => $total,
        'completados' => $completados,
        'porcentaje' => $porcentaje,
    ];
}

$modulosCompletadosTotal = array_sum(array_column($cursosData, 'completados'));
$modulosTotalGlobal      = array_sum(array_column($cursosData, 'total'));

// ── View ──────────────────────────────────────────────────────
$pageTitle = 'Mis Cursos';
$activeNav = 'inicio';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header flex justify-between items-center">
  <div>
    <h1 class="page-title">¡Hola, <?= sanitize($user['nombre'] ?? 'Estudiante') ?>! 👋</h1>
    <p class="page-subtitle">Continúa tu aventura de aprendizaje STEAM</p>
  </div>
</div>

<!-- Stats row -->
<div class="stats-grid mb-32">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">⭐</div>
    <div class="stat-value" style="color:var(--gold)"><?= (int)$estrellasTotal ?></div>
    <div class="stat-label">Estrellas ganadas</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)">✅</div>
    <div class="stat-value" style="color:var(--green)"><?= (int)$modulosCompletadosTotal ?></div>
    <div class="stat-label">Módulos completados</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">📚</div>
    <div class="stat-value" style="color:var(--blue)"><?= (int)$modulosTotalGlobal ?></div>
    <div class="stat-label">Módulos en total</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">🎓</div>
    <div class="stat-value" style="color:var(--purple)"><?= count($cursos) ?></div>
    <div class="stat-label">Cursos disponibles</div>
  </div>
</div>

<!-- Courses grid -->
<div class="mb-20 flex items-center justify-between">
  <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700">Mis Cursos</h2>
  <span class="text-muted text-sm"><?= count($cursos) ?> cursos disponibles</span>
</div>

<?php if (empty($cursosData)): ?>
  <div class="empty-state">
    <div class="empty-icon">📭</div>
    <h3>No hay cursos disponibles</h3>
    <p>Pronto se habilitarán cursos para ti.</p>
  </div>
<?php else: ?>
<div class="courses-grid">
  <?php foreach ($cursosData as $item):
    $curso      = $item['curso'];
    $color      = sanitize($curso['color_hex'] ?? '#4a9eff');
    $emoji      = sanitize($curso['emoji'] ?? '📘');
    $porcentaje = (int)$item['porcentaje'];
  ?>
  <a href="<?= BASE_URL ?>/estudiante/curso.php?id=<?= (int)$curso['id'] ?>"
     class="course-card"
     style="--color:<?= $color ?>;text-decoration:none">

    <!-- Colored header -->
    <div class="course-card-header"
         style="background:linear-gradient(135deg,<?= $color ?>30,<?= $color ?>10)">
      <span><?= $emoji ?></span>
    </div>

    <!-- Card body -->
    <div class="course-card-body">
      <div class="course-card-name"><?= sanitize($curso['nombre']) ?></div>

      <?php if (!empty($curso['descripcion'])): ?>
        <p class="text-muted text-sm mb-12" style="line-height:1.5">
          <?= sanitize(mb_strimwidth($curso['descripcion'], 0, 70, '…')) ?>
        </p>
      <?php endif; ?>

      <!-- Progress bar -->
      <div class="progress-track mb-12">
        <div class="progress-fill"
             style="width:<?= $porcentaje ?>%;background:<?= $color ?>"></div>
      </div>
      <div class="progress-label">
        <span><?= (int)$item['completados'] ?> / <?= (int)$item['total'] ?> módulos</span>
        <span style="color:<?= $color ?>;font-weight:600"><?= $porcentaje ?>%</span>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
