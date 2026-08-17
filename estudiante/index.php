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
    $modulos     = getModulosByCurso($curso['id']);
    $total       = count($modulos);
    $completados = getModulosCompletadosByCurso($estudianteId, $curso['id']);
    $porcentaje  = $total > 0 ? round(($completados / $total) * 100) : 0;

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

<style>
  /* ── Student dashboard extras ─────────────────────────────── */

  /* Greeting heading — override default h1 size for welcome context */
  .student-greeting {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.6rem;
    color: var(--text-primary);
    letter-spacing: -0.02em;
    line-height: 1.2;
  }

  /* Stat card icon sizing for Lucide icons */
  .stat-icon [data-lucide] {
    width: 20px;
    height: 20px;
  }

  /* Course card header: large initial letter */
  .course-initial {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 48px;
    color: rgba(255,255,255,.90);
    line-height: 1;
    position: relative;
    z-index: 1;
    user-select: none;
  }

  /* Ensure course card hover works nicely */
  .course-card:hover .course-initial {
    color: #ffffff;
  }

  /* Courses section header */
  .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
  }

  .section-title {
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
  }

  .section-count {
    font-size: 12px;
    color: var(--text-muted);
  }

  /* Empty state icon wrapper */
  .empty-state-svg {
    width: 52px;
    height: 52px;
    margin: 0 auto 16px;
    opacity: 0.3;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .empty-state-svg svg {
    width: 52px;
    height: 52px;
    stroke: var(--text-muted);
    stroke-width: 1.5;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  /* mb helpers */
  .mb-24 { margin-bottom: 24px; }
  .mb-32 { margin-bottom: 32px; }
</style>

<!-- ── Page header ─────────────────────────────────────────── -->
<div class="page-header">
  <div class="student-greeting">Hola, <?= sanitize($user['nombre'] ?? 'Estudiante') ?></div>
  <p class="page-subtitle">Continua tu aventura de aprendizaje STEAM</p>
</div>

<!-- ── Stats row ──────────────────────────────────────────── -->
<div class="stats-grid mb-32">

  <div class="stat-card">
    <div class="stat-icon amber">
      <i data-lucide="star"></i>
    </div>
    <div class="stat-value" style="color:#f59e0b"><?= (int)$estrellasTotal ?></div>
    <div class="stat-label">Estrellas ganadas</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <i data-lucide="check-circle"></i>
    </div>
    <div class="stat-value" style="color:#2dca73"><?= (int)$modulosCompletadosTotal ?></div>
    <div class="stat-label">Modulos completados</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">
      <i data-lucide="book-open"></i>
    </div>
    <div class="stat-value" style="color:#4361ee"><?= (int)$modulosTotalGlobal ?></div>
    <div class="stat-label">Modulos en total</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(99,102,241,.12);color:#6366f1">
      <i data-lucide="layers"></i>
    </div>
    <div class="stat-value" style="color:#6366f1"><?= count($cursos) ?></div>
    <div class="stat-label">Cursos disponibles</div>
  </div>

</div>

<!-- ── Courses section ─────────────────────────────────────── -->
<div class="section-header">
  <h2 class="section-title">Mis Cursos</h2>
  <span class="section-count"><?= count($cursos) ?> cursos disponibles</span>
</div>

<?php if (empty($cursosData)): ?>
  <div class="empty-state">
    <div class="empty-state-svg">
      <svg viewBox="0 0 24 24">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
    </div>
    <p class="empty-state-title">No hay cursos disponibles</p>
    <p class="empty-state-desc">Pronto se habilitaran cursos para ti.</p>
  </div>

<?php else: ?>
<div class="courses-grid">
  <?php foreach ($cursosData as $item):
    $curso      = $item['curso'];
    $color      = sanitize($curso['color_hex'] ?? '#4361ee');
    $porcentaje = (int)$item['porcentaje'];
    // Derive initial letter from course name (first non-space char, uppercase)
    $initial    = strtoupper(mb_substr(trim($curso['nombre'] ?? 'C'), 0, 1));
  ?>
  <a href="<?= BASE_URL ?>/estudiante/curso.php?id=<?= (int)$curso['id'] ?>"
     class="course-card"
     style="--color:<?= $color ?>;text-decoration:none">

    <!-- Colored header with initial letter -->
    <div class="course-card-header"
         style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>cc)">
      <span class="course-initial"><?= htmlspecialchars($initial) ?></span>
    </div>

    <!-- Card body -->
    <div class="course-card-body">
      <div class="course-card-name"><?= sanitize($curso['nombre']) ?></div>

      <?php if (!empty($curso['descripcion'])): ?>
        <p style="font-size:13px;color:var(--text-muted);line-height:1.5;margin-bottom:14px;
                  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
          <?= sanitize($curso['descripcion']) ?>
        </p>
      <?php endif; ?>

      <!-- Progress bar -->
      <div class="progress-track" style="margin-bottom:8px">
        <div class="progress-fill"
             style="width:<?= $porcentaje ?>%;background:<?= $color ?>"></div>
      </div>
      <div class="progress-label">
        <span><?= (int)$item['completados'] ?> / <?= (int)$item['total'] ?> modulos</span>
        <span style="color:<?= $color ?>;font-weight:600"><?= $porcentaje ?>%</span>
      </div>
    </div>

  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
