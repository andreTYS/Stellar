<?php
// ============================================================
// INNOVA-STEAM — Certificados del estudiante
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('estudiante');

$estudianteId = currentUserId();
$user         = currentUser();

// ── Query: certificates with module + course + quiz stars ────
$stmt = getDB()->prepare(
    'SELECT cert.*,
            m.titulo     AS modulo_titulo,
            m.curso_id,
            c.nombre     AS curso_nombre,
            c.color_hex,
            c.icono,
            pe.estrellas_quiz
     FROM certificados cert
     JOIN modulos m           ON m.id = cert.modulo_id
     JOIN cursos  c           ON c.id = m.curso_id
     JOIN progreso_estudiante pe
          ON pe.estudiante_id = cert.estudiante_id
         AND pe.modulo_id     = cert.modulo_id
     WHERE cert.estudiante_id = ?
     ORDER BY cert.emitido_en DESC'
);
$stmt->execute([$estudianteId]);
$certs = $stmt->fetchAll();

$totalEstrellas = array_sum(array_column($certs, 'estrellas_quiz'));
$totalCerts     = count($certs);

// ── View ─────────────────────────────────────────────────────
$pageTitle = 'Mis Certificados';
$activeNav = 'certificados';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page header -->
<div class="page-action-header">
  <div>
    <h1 class="page-title">Mis Certificados</h1>
    <p class="page-subtitle">
      <?= $totalCerts ?> certificado<?= $totalCerts !== 1 ? 's' : '' ?> obtenido<?= $totalCerts !== 1 ? 's' : '' ?>
    </p>
  </div>

  <?php if ($totalEstrellas > 0): ?>
  <!-- Total stars badge -->
  <div class="stat-card flex items-center gap-12" style="padding:14px 20px;">
    <div style="color:var(--gold)"><i data-lucide="star" style="width:28px;height:28px"></i></div>
    <div>
      <div class="stat-value" style="color:var(--gold);font-size:26px;"><?= $totalEstrellas ?></div>
      <div class="stat-label">estrellas ganadas</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Empty state -->
<?php if (empty($certs)): ?>
<div class="empty-state">
  <div style="width:72px;height:72px;border-radius:50%;background:var(--bg-elevated);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:var(--text-muted)"><i data-lucide="graduation-cap" style="width:36px;height:36px"></i></div>
  <h3>¡Aún no tienes certificados!</h3>
  <p>Completa los 4 pasos de cualquier módulo — historia, actividad, quiz y entregable — para recibir tu primer certificado.</p>
  <a href="<?= BASE_URL ?>/estudiante/index.php" class="btn btn-primary" style="margin-top:24px;text-decoration:none;">
    <i data-lucide="book-open" style="width:15px;height:15px"></i>
    Empezar a aprender
  </a>
</div>

<?php else: ?>
<!-- Certificates grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;" class="mb-32">
  <?php foreach ($certs as $cert):
    $color    = htmlspecialchars($cert['color_hex'], ENT_QUOTES, 'UTF-8');
    $icono    = htmlspecialchars($cert['icono'], ENT_QUOTES, 'UTF-8');
    $estrellas = (int)$cert['estrellas_quiz'];
    $hasPdf   = !empty($cert['pdf_url']);
  ?>
  <div style="background:var(--bg-surface);border:1px solid var(--bg-border);border-radius:16px;overflow:hidden;display:flex;flex-direction:column;transition:transform .25s,box-shadow .25s,border-color .25s;"
       onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 40px <?= $color ?>2e';this.style.borderColor='<?= $color ?>55'"
       onmouseout="this.style.transform='';this.style.boxShadow='';this.style.borderColor='var(--bg-border)'">

    <!-- Gradient header -->
    <div style="background:linear-gradient(135deg,<?= $color ?>44 0%,<?= $color ?>18 60%,transparent 100%);padding:28px 24px;text-align:center;border-bottom:1px solid <?= $color ?>22;position:relative;overflow:hidden;">
      <!-- Decorative ring -->
      <div style="position:absolute;top:-30px;right:-30px;width:100px;height:100px;border-radius:50%;background:<?= $color ?>12;pointer-events:none;"></div>
      <div style="position:absolute;bottom:-20px;left:-20px;width:70px;height:70px;border-radius:50%;background:<?= $color ?>0e;pointer-events:none;"></div>

      <div style="width:64px;height:64px;border-radius:16px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#fff;position:relative;">
        <i data-lucide="award" style="width:30px;height:30px"></i>
      </div>
      <span style="background:<?= $color ?>30;color:<?= $color ?>;border:1px solid <?= $color ?>55;padding:3px 14px;border-radius:20px;font-size:11px;font-weight:700;position:relative;">
        <?= sanitize($cert['curso_nombre']) ?>
      </span>
    </div>

    <!-- Card body -->
    <div style="padding:20px 22px;flex:1;display:flex;flex-direction:column;gap:12px;">
      <!-- Module title -->
      <h3 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--text-primary);line-height:1.3;">
        <?= sanitize($cert['modulo_titulo']) ?>
      </h3>

      <!-- Stars earned -->
      <div class="flex items-center gap-8">
        <div class="stars-row" style="font-size:22px;">
          <?= estrellasHtml($estrellas) ?>
        </div>
        <span style="font-size:12px;color:var(--text-muted);">
          <?= $estrellas ?>/3 estrellas
        </span>
      </div>

      <!-- Date issued -->
      <p style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:5px;">
        <i data-lucide="calendar-check" style="width:12px;height:12px;color:var(--text-muted)"></i>
        Emitido el <?= formatDate($cert['emitido_en']) ?>
      </p>

      <!-- Download button -->
      <div style="margin-top:auto;padding-top:8px;">
        <a href="<?= BASE_URL ?>/estudiante/certificado_ver.php?cert_id=<?= (int)$cert['id'] ?>"
           target="_blank" rel="noopener"
           class="btn btn-primary btn-block" style="text-decoration:none;">
          <i data-lucide="award" style="width:15px;height:15px"></i>
          Ver certificado
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Encouragement banner -->
<?php
// Count total modules across all active courses to compute progress
$totalModulos = (int)getDB()
    ->query('SELECT COUNT(*) FROM modulos WHERE activo = 1')
    ->fetchColumn();
$remaining = max(0, $totalModulos - $totalCerts);
?>

<?php if ($remaining === 0 && $totalCerts > 0): ?>
<!-- All done! Trophy banner -->
<div class="card" style="text-align:center;padding:40px 24px;border:2px solid var(--gold)44;background:linear-gradient(135deg,rgba(245,200,66,.07),transparent);">
  <div style="width:72px;height:72px;border-radius:50%;background:rgba(245,200,66,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
    <i data-lucide="trophy" style="width:36px;height:36px;color:var(--gold)"></i>
  </div>
  <h2 style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:var(--gold);margin-bottom:8px;">
    ¡Completaste todos los módulos!
  </h2>
  <p style="color:var(--text-secondary);font-size:15px;">
    Eres un verdadero campeón INNOVA-STEAM. ¡Felicitaciones por tu dedicación!
  </p>
</div>

<?php else: ?>
<!-- Keep going banner -->
<div class="card flex items-center gap-16" style="border:1px solid var(--bg-border);flex-wrap:wrap;">
  <div style="width:52px;height:52px;border-radius:50%;background:rgba(74,158,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
    <i data-lucide="rocket" style="width:24px;height:24px;color:var(--blue)"></i>
  </div>
  <div class="flex-1">
    <p style="font-weight:700;font-family:'Syne',sans-serif;font-size:15px;margin-bottom:4px;">
      ¡Sigue adelante!
    </p>
    <p style="color:var(--text-secondary);font-size:13px;">
      Tienes <?= $remaining ?> módulo<?= $remaining !== 1 ? 's' : '' ?> más por completar. ¡Cada certificado cuenta!
    </p>
  </div>
  <a href="<?= BASE_URL ?>/estudiante/index.php" class="btn btn-ghost" style="text-decoration:none;flex-shrink:0;">
    Ver mis cursos
    <i data-lucide="arrow-right" style="width:14px;height:14px"></i>
  </a>
</div>
<?php endif; ?>

<?php endif; // empty($certs) ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
