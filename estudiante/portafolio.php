<?php
// ============================================================
// INNOVA-STEAM — Portafolio del estudiante
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('estudiante');

$estudianteId = currentUserId();
$user         = currentUser();

// ── Query: all entregables with module + course data ─────────
$stmt = getDB()->prepare(
    'SELECT e.*,
            m.titulo  AS modulo_titulo,
            m.curso_id,
            c.nombre  AS curso_nombre,
            c.color_hex,
            c.icono
     FROM entregables e
     JOIN modulos m ON m.id = e.modulo_id
     JOIN cursos  c ON c.id = m.curso_id
     WHERE e.estudiante_id = ?
     ORDER BY c.id, e.subido_en DESC'
);
$stmt->execute([$estudianteId]);
$todos = $stmt->fetchAll();

// ── Group by course ──────────────────────────────────────────
$porCurso = [];
foreach ($todos as $ent) {
    $key = (int)$ent['curso_id'];
    if (!isset($porCurso[$key])) {
        $porCurso[$key] = [
            'nombre'    => $ent['curso_nombre'],
            'color'     => $ent['color_hex'],
            'icono'     => $ent['icono'],
            'items'     => [],
        ];
    }
    $porCurso[$key]['items'][] = $ent;
}

// ── View ─────────────────────────────────────────────────────
$pageTitle = 'Mi Portafolio';
$activeNav = 'portafolio';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page header -->
<div class="page-action-header">
  <div>
    <h1 class="page-title">Mi Portafolio</h1>
    <p class="page-subtitle">
      <?= count($todos) ?> trabajo<?= count($todos) !== 1 ? 's' : '' ?> enviado<?= count($todos) !== 1 ? 's' : '' ?>
      <?= !empty($porCurso) ? ' en ' . count($porCurso) . ' curso' . (count($porCurso) !== 1 ? 's' : '') : '' ?>
    </p>
  </div>
  <?php if (!empty($todos)): ?>
  <div class="stat-card" style="min-width:120px;text-align:center;padding:16px 20px;">
    <div class="stat-value" style="color:var(--purple);font-size:26px;"><?= count($todos) ?></div>
    <div class="stat-label">Trabajos</div>
  </div>
  <?php endif; ?>
</div>

<!-- Empty state -->
<?php if (empty($todos)): ?>
<div class="empty-state">
  <div class="empty-icon">🌱</div>
  <h3>¡Aún no tienes trabajos aquí!</h3>
  <p>Completa el paso de entregable en cualquier módulo para ver tus creaciones.</p>
  <a href="<?= BASE_URL ?>/estudiante/index.php" class="btn btn-primary" style="margin-top:20px;text-decoration:none;">
    <i data-lucide="book-open" style="width:15px;height:15px"></i>
    Explorar cursos
  </a>
</div>

<?php else: ?>
<!-- Courses with entregables -->
<?php foreach ($porCurso as $cursoId => $curso):
  $color = htmlspecialchars($curso['color'], ENT_QUOTES, 'UTF-8');
  $icono = htmlspecialchars($curso['icono'], ENT_QUOTES, 'UTF-8');
?>
<section class="mb-32">

  <!-- Course section header -->
  <div class="flex items-center gap-12 mb-16" style="padding-bottom:14px;border-bottom:2px solid <?= $color ?>33;">
    <span style="font-size:28px;flex-shrink:0;"><?= $icono ?></span>
    <div class="flex-1">
      <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:<?= $color ?>;">
        <?= sanitize($curso['nombre']) ?>
      </h2>
    </div>
    <span class="badge badge-muted"><?= count($curso['items']) ?> entregable<?= count($curso['items']) !== 1 ? 's' : '' ?></span>
  </div>

  <!-- Entregables grid -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
    <?php foreach ($curso['items'] as $ent):
      $hasImage   = !empty($ent['archivo_url']);
      $hasComment = !empty($ent['comentario_docente']);
      $calific    = (int)($ent['calificacion'] ?? 0);
      $formato    = formatoLabel($ent['formato'] ?? '');
    ?>
    <div style="background:var(--bg-surface);border:1px solid var(--bg-border);border-radius:14px;overflow:hidden;display:flex;flex-direction:column;transition:border-color .2s,transform .2s;"
         onmouseover="this.style.borderColor='<?= $color ?>66';this.style.transform='translateY(-2px)'"
         onmouseout="this.style.borderColor='var(--bg-border)';this.style.transform=''">

      <!-- Thumbnail -->
      <?php if ($hasImage): ?>
      <a href="<?= sanitize($ent['archivo_url']) ?>" target="_blank" rel="noopener"
         style="display:block;overflow:hidden;flex-shrink:0;">
        <img src="<?= sanitize($ent['archivo_url']) ?>" alt="Trabajo"
             style="width:100%;height:160px;object-fit:cover;display:block;transition:transform .3s;"
             onmouseover="this.style.transform='scale(1.04)'"
             onmouseout="this.style.transform=''">
      </a>
      <?php else: ?>
      <div style="height:120px;background:linear-gradient(135deg,<?= $color ?>22,<?= $color ?>11);display:flex;align-items:center;justify-content:center;font-size:44px;flex-shrink:0;">
        📄
      </div>
      <?php endif; ?>

      <!-- Card body -->
      <div style="padding:14px 16px;flex:1;display:flex;flex-direction:column;">
        <!-- Module name -->
        <p style="font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:var(--text-primary);margin-bottom:6px;line-height:1.3;">
          <?= sanitize($ent['modulo_titulo']) ?>
        </p>

        <!-- Meta row -->
        <div class="flex gap-8 mb-12" style="flex-wrap:wrap;">
          <span style="background:<?= $color ?>18;color:<?= $color ?>;border:1px solid <?= $color ?>33;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;">
            <?= sanitize($formato) ?>
          </span>
          <span style="font-size:11px;color:var(--text-muted);display:flex;align-items:center;gap:3px;">
            <i data-lucide="calendar" style="width:11px;height:11px"></i>
            <?= formatDate($ent['subido_en']) ?>
          </span>
        </div>

        <!-- Docente comment -->
        <?php if ($hasComment): ?>
        <div style="background:var(--bg-elevated);border-left:3px solid <?= $color ?>;border-radius:0 8px 8px 0;padding:10px 12px;margin-top:auto;">
          <p style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Docente:</p>
          <p style="font-size:13px;color:var(--text-primary);line-height:1.5;"><?= sanitize($ent['comentario_docente']) ?></p>
          <?php if ($calific > 0): ?>
          <div class="stars-row mt-8" style="font-size:16px;">
            <?= estrellasHtml($calific) ?>
          </div>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <p style="font-size:12px;color:var(--text-muted);font-style:italic;margin-top:auto;padding-top:8px;display:flex;align-items:center;gap:5px;">
          <i data-lucide="clock" style="width:12px;height:12px"></i>
          Esperando retroalimentación del docente…
        </p>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</section>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
