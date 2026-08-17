<?php
// ============================================================
// INNOVA-STEAM — Guía del módulo para el practicante (solo lectura)
// GET ?id=MODULE_ID
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('practicante');

// ── Fetch module ──────────────────────────────────────────────
$moduloId = (int)($_GET['id'] ?? 0);
if (!$moduloId) {
    redirect(BASE_URL . '/practicante/index.php');
}

$modulo = getModuloById($moduloId);
if (!$modulo) {
    setFlash('error', 'Módulo no encontrado.');
    redirect(BASE_URL . '/practicante/index.php');
}

// ── Fetch all pasos ───────────────────────────────────────────
$pasos = getPasosByModulo($moduloId);

// For each paso fetch quiz questions (if tipo = quiz)
$quizPorPaso = [];
foreach ($pasos as $paso) {
    if (($paso['tipo'] ?? '') === 'quiz') {
        $quizPorPaso[(int)$paso['id']] = getPreguntasByPaso((int)$paso['id']);
    }
}

// ── Accent color from course ──────────────────────────────────
$accentColor = !empty($modulo['color_hex']) ? $modulo['color_hex'] : '#4a9eff';

// Paso type labels
$tipoLabel = [
    'video'        => 'Video',
    'lectura'      => 'Lectura',
    'actividad'    => 'Actividad',
    'quiz'         => 'Quiz',
    'entregable'   => 'Entregable',
    'introduccion' => 'Introducción',
    'cierre'       => 'Cierre',
];

$tipoIcon = [
    'video'        => 'play-circle',
    'lectura'      => 'book-open',
    'actividad'    => 'zap',
    'quiz'         => 'help-circle',
    'entregable'   => 'upload',
    'introduccion' => 'flag',
    'cierre'       => 'check-circle',
];

// ── Page setup ────────────────────────────────────────────────
$pageTitle = 'Guía: ' . ($modulo['titulo'] ?? 'Módulo');
$activeNav = 'sesiones';
$extraCss  = ['sidebar.css', 'module.css'];
include __DIR__ . '/../includes/header.php';
?>

<!-- Print button (outside flow, visible on screen only) -->
<div class="no-print" style="
    position:fixed;bottom:28px;right:28px;z-index:300">
  <button onclick="window.print()" class="btn btn-primary" style="box-shadow:0 8px 24px rgba(74,158,255,.4)">
    <i data-lucide="printer" style="width:16px;height:16px"></i>
    Imprimir guía
  </button>
</div>

<!-- ── Page header ──────────────────────────────────────────── -->
<div class="page-action-header no-print">
  <div>
    <a href="<?= BASE_URL ?>/practicante/index.php"
       class="flex items-center gap-8 text-muted text-sm mb-8"
       style="color:var(--text-muted);text-decoration:none;transition:color .15s"
       onmouseover="this.style.color='var(--text-primary)'"
       onmouseout="this.style.color='var(--text-muted)'">
      <i data-lucide="arrow-left" style="width:14px;height:14px"></i>
      Volver al dashboard
    </a>
    <h1 class="page-title">Guía del facilitador</h1>
    <p class="page-subtitle">Vista completa del módulo · solo lectura</p>
  </div>
</div>

<!-- ── Module hero card ─────────────────────────────────────── -->
<div class="card mb-24" style="
    border-color:<?= $accentColor ?>40;
    background: linear-gradient(135deg, var(--bg-surface), var(--bg-elevated));
    position:relative;overflow:hidden">

  <!-- Decorative accent blob -->
  <div style="
      position:absolute;top:-40px;right:-40px;
      width:200px;height:200px;
      background:<?= $accentColor ?>18;
      border-radius:50%;pointer-events:none"></div>

  <div style="position:relative;display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap">
    <!-- Icon -->
    <div style="
        width:64px;height:64px;border-radius:16px;
        background:<?= $accentColor ?>22;
        border:2px solid <?= $accentColor ?>40;
        display:flex;align-items:center;justify-content:center;
        flex-shrink:0">
      <i data-lucide="book-open" style="width:28px;height:28px;color:<?= $accentColor ?>"></i>
    </div>

    <div style="flex:1;min-width:200px">
      <!-- Course badge -->
      <div style="margin-bottom:8px">
        <span class="badge" style="
            background:<?= $accentColor ?>22;
            color:<?= $accentColor ?>;
            border-color:<?= $accentColor ?>44;
            font-size:11px">
          <?= sanitize($modulo['curso_nombre'] ?? 'Curso') ?>
        </span>
      </div>

      <h2 style="
          font-family:'Syne',sans-serif;font-size:22px;font-weight:800;
          color:var(--text-primary);margin-bottom:8px">
        <?= sanitize($modulo['titulo'] ?? '') ?>
      </h2>

      <?php if (!empty($modulo['descripcion'])): ?>
      <p style="color:var(--text-secondary);font-size:14px;line-height:1.6;margin-bottom:12px">
        <?= sanitize($modulo['descripcion']) ?>
      </p>
      <?php endif; ?>

      <!-- Meta chips -->
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <?php if (!empty($modulo['duracion_minutos'])): ?>
        <span style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary)">
          <i data-lucide="clock" style="width:14px;height:14px"></i>
          <?= (int)$modulo['duracion_minutos'] ?> min
        </span>
        <?php endif; ?>
        <span style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary)">
          <i data-lucide="layers" style="width:14px;height:14px"></i>
          <?= count($pasos) ?> paso<?= count($pasos) !== 1 ? 's' : '' ?>
        </span>
        <?php if (!empty($modulo['orden'])): ?>
        <span style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary)">
          <i data-lucide="hash" style="width:14px;height:14px"></i>
          Módulo <?= (int)$modulo['orden'] ?>
        </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Facilitator tip ───────────────────────────────────────── -->
<div class="no-print" style="
    background:rgba(74,158,255,.08);
    border:1px solid rgba(74,158,255,.25);
    border-radius:12px;padding:14px 18px;
    display:flex;align-items:center;gap:12px;
    margin-bottom:24px;font-size:13px;color:var(--text-secondary)">
  <i data-lucide="info" style="width:18px;height:18px;color:var(--blue);flex-shrink:0"></i>
  <span>
    Esta es la <strong style="color:var(--text-primary)">guía completa de preparación</strong>.
    Todos los pasos están expandidos para que puedas revisar el contenido antes de facilitar la sesión.
    Las respuestas correctas del quiz aparecen resaltadas en verde.
  </span>
</div>

<!-- ── Steps ────────────────────────────────────────────────── -->
<?php if (empty($pasos)): ?>
<div class="card">
  <div class="empty-state">
    <div class="empty-icon">
      <i data-lucide="file-question" style="width:48px;height:48px;opacity:.3"></i>
    </div>
    <h3>Este módulo no tiene pasos configurados</h3>
    <p>Contacta al administrador para agregar contenido.</p>
  </div>
</div>
<?php else: ?>

<?php foreach ($pasos as $index => $paso):
  $pasoId    = (int)$paso['id'];
  $tipo      = $paso['tipo']         ?? 'lectura';
  $titulo    = $paso['titulo']        ?? "Paso " . ($index + 1);
  $contenido = $paso['contenido']     ?? [];
  $numero    = (int)($paso['numero_paso'] ?? ($index + 1));
  $iconName  = $tipoIcon[$tipo]       ?? 'file-text';
  $tipoText  = $tipoLabel[$tipo]      ?? ucfirst($tipo);

  // For quiz pasos
  $preguntas = $quizPorPaso[$pasoId] ?? [];
?>

<div class="card mb-16 paso-card" id="paso-<?= $numero ?>" style="
    border-left: 4px solid <?= $accentColor ?>;
    page-break-inside: avoid">

  <!-- ── Paso header ─────────────────────────────────────────── -->
  <div class="card-header" style="margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:14px">
      <!-- Step number -->
      <div style="
          width:40px;height:40px;border-radius:12px;
          background:<?= $accentColor ?>22;
          border:2px solid <?= $accentColor ?>44;
          display:flex;align-items:center;justify-content:center;
          font-family:'Syne',sans-serif;font-weight:800;font-size:16px;
          color:<?= $accentColor ?>;flex-shrink:0">
        <?= $numero ?>
      </div>
      <div>
        <h3 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:3px">
          <?= sanitize($titulo) ?>
        </h3>
        <span style="
            display:inline-flex;align-items:center;gap:5px;
            font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;
            color:var(--text-muted)">
          <i data-lucide="<?= $iconName ?>" style="width:12px;height:12px"></i>
          <?= sanitize($tipoText) ?>
        </span>
      </div>
    </div>

    <!-- Print step indicator -->
    <div class="print-only" style="display:none;font-size:12px;color:#666">
      Paso <?= $numero ?> de <?= count($pasos) ?>
    </div>
  </div>

  <!-- ── Content body ─────────────────────────────────────────── -->
  <div style="padding-left: 54px">

    <?php if ($tipo === 'quiz' && !empty($preguntas)): ?>
    <!-- QUIZ paso ───────────────────────────────────────────── -->
    <div>
      <?php if (!empty($contenido['instrucciones'])): ?>
      <p style="color:var(--text-secondary);margin-bottom:16px;font-size:14px">
        <?= sanitize($contenido['instrucciones']) ?>
      </p>
      <?php endif; ?>

      <div style="display:flex;flex-direction:column;gap:20px">
        <?php foreach ($preguntas as $qi => $pregunta):
          $opciones  = $pregunta['opciones'] ?? [];
          $pregTexto = $pregunta['pregunta']  ?? $pregunta['texto'] ?? '';
        ?>
        <div style="
            background:var(--bg-elevated);
            border:1px solid var(--bg-border);
            border-radius:12px;padding:18px">

          <!-- Question text -->
          <div style="font-weight:600;font-size:14px;margin-bottom:12px;display:flex;gap:10px">
            <span style="
                color:<?= $accentColor ?>;
                font-family:'Syne',sans-serif;font-weight:800;
                min-width:22px">
              <?= $qi + 1 ?>.
            </span>
            <?= sanitize($pregTexto) ?>
          </div>

          <!-- Options -->
          <div style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($opciones as $oi => $opcion):
              $textoOpcion = is_array($opcion) ? ($opcion['texto'] ?? '') : (string)$opcion;
              $esCorrecta  = is_array($opcion) && !empty($opcion['correcta']);
            ?>
            <div style="
                display:flex;align-items:center;gap:10px;
                padding:10px 14px;border-radius:8px;
                border:1px solid <?= $esCorrecta ? 'var(--success)' : 'var(--bg-border)' ?>;
                background:<?= $esCorrecta ? 'rgba(34,197,94,.1)' : 'var(--bg-surface)' ?>;
                font-size:13px">

              <!-- Option letter -->
              <span style="
                  width:24px;height:24px;border-radius:6px;flex-shrink:0;
                  display:flex;align-items:center;justify-content:center;
                  font-weight:700;font-size:12px;
                  background:<?= $esCorrecta ? 'var(--success)' : 'var(--bg-elevated)' ?>;
                  color:<?= $esCorrecta ? '#fff' : 'var(--text-secondary)' ?>">
                <?= chr(65 + $oi) ?>
              </span>

              <span style="color:<?= $esCorrecta ? 'var(--text-primary)' : 'var(--text-secondary)' ?>;flex:1">
                <?= sanitize($textoOpcion) ?>
              </span>

              <?php if ($esCorrecta): ?>
              <span style="
                  display:flex;align-items:center;gap:4px;
                  font-size:11px;font-weight:700;text-transform:uppercase;
                  color:var(--success);letter-spacing:.05em">
                <i data-lucide="check" style="width:12px;height:12px"></i>
                Correcta
              </span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if (!empty($pregunta['explicacion'])): ?>
          <div style="
              margin-top:12px;padding:10px 14px;
              background:rgba(74,158,255,.08);
              border-left:3px solid var(--blue);
              border-radius:0 8px 8px 0;font-size:13px;
              color:var(--text-secondary)">
            <strong style="color:var(--blue)">Explicación:</strong>
            <?= sanitize($pregunta['explicacion']) ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php elseif ($tipo === 'video'): ?>
    <!-- VIDEO paso ─────────────────────────────────────────── -->
    <?php if (!empty($contenido['url'])): ?>
    <div style="margin-bottom:14px">
      <a href="<?= sanitize($contenido['url']) ?>"
         target="_blank" rel="noopener noreferrer"
         class="btn btn-ghost btn-sm no-print">
        <i data-lucide="external-link" style="width:14px;height:14px"></i>
        Abrir video
      </a>
      <div class="print-only" style="display:none;font-size:13px;color:#444;margin-top:8px">
        URL: <?= sanitize($contenido['url']) ?>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($contenido['descripcion'])): ?>
    <p style="color:var(--text-secondary);font-size:14px;line-height:1.7">
      <?= nl2br(sanitize($contenido['descripcion'])) ?>
    </p>
    <?php endif; ?>

    <?php elseif ($tipo === 'entregable'): ?>
    <!-- ENTREGABLE paso ────────────────────────────────────── -->
    <div style="
        background:var(--bg-elevated);border:1px dashed var(--bg-border);
        border-radius:12px;padding:20px;text-align:center">
      <i data-lucide="upload" style="width:32px;height:32px;color:var(--text-muted);margin-bottom:10px"></i>
      <p style="color:var(--text-secondary);font-size:14px;margin-bottom:8px">
        Los estudiantes deben subir su entregable en este paso.
      </p>
      <?php if (!empty($contenido['instrucciones'])): ?>
      <p style="font-size:13px;color:var(--text-muted)">
        <?= sanitize($contenido['instrucciones']) ?>
      </p>
      <?php endif; ?>
      <?php if (!empty($contenido['formatos'])): ?>
      <div style="margin-top:12px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
        <?php foreach ((array)$contenido['formatos'] as $fmt): ?>
        <span class="badge badge-muted"><?= sanitize($fmt) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- DEFAULT / LECTURA / ACTIVIDAD / INTRO / CIERRE ─────── -->
    <?php if (!empty($contenido['texto'])): ?>
    <div style="
        font-size:14px;line-height:1.8;color:var(--text-secondary);
        white-space:pre-wrap">
      <?= nl2br(sanitize($contenido['texto'])) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($contenido['descripcion'])): ?>
    <div style="
        font-size:14px;line-height:1.8;color:var(--text-secondary);
        white-space:pre-wrap;margin-top:8px">
      <?= nl2br(sanitize($contenido['descripcion'])) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($contenido['materiales'])): ?>
    <div style="margin-top:16px">
      <div style="
          font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
          color:var(--text-muted);margin-bottom:8px">
        Materiales necesarios
      </div>
      <ul style="list-style:none;display:flex;flex-direction:column;gap:6px">
        <?php foreach ((array)$contenido['materiales'] as $mat): ?>
        <li style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary)">
          <i data-lucide="check-circle" style="width:14px;height:14px;color:var(--success);flex-shrink:0"></i>
          <?= sanitize((string)$mat) ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($contenido['pasos_actividad'])): ?>
    <div style="margin-top:16px">
      <div style="
          font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
          color:var(--text-muted);margin-bottom:8px">
        Instrucciones para los estudiantes
      </div>
      <ol style="padding-left:20px;display:flex;flex-direction:column;gap:8px">
        <?php foreach ((array)$contenido['pasos_actividad'] as $instruccion): ?>
        <li style="font-size:13px;color:var(--text-secondary);line-height:1.6">
          <?= sanitize((string)$instruccion) ?>
        </li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>

    <?php if (!empty($contenido['nota_facilitador'])): ?>
    <div style="
        margin-top:16px;padding:12px 16px;
        background:rgba(245,200,66,.08);
        border-left:3px solid var(--gold);
        border-radius:0 8px 8px 0;font-size:13px">
      <div style="
          font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
          color:var(--gold);margin-bottom:4px">
        Nota del facilitador
      </div>
      <span style="color:var(--text-secondary)">
        <?= nl2br(sanitize($contenido['nota_facilitador'])) ?>
      </span>
    </div>
    <?php endif; ?>
    <?php endif; // end tipo switch ?>

  </div><!-- /padding-left -->
</div><!-- /paso-card -->

<?php endforeach; ?>
<?php endif; // end pasos ?>

<!-- ── Bottom actions ────────────────────────────────────────── -->
<div class="flex justify-between items-center mt-24 no-print">
  <a href="<?= BASE_URL ?>/practicante/index.php" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:16px;height:16px"></i>
    Volver al dashboard
  </a>
  <button onclick="window.print()" class="btn btn-primary">
    <i data-lucide="printer" style="width:16px;height:16px"></i>
    Imprimir guía
  </button>
</div>


<?php
$inlineJs = <<<'JS'
// Highlight correct answers visually on load (already done server-side, but
// ensure Lucide icons are re-rendered after dynamic content if needed)
document.addEventListener('DOMContentLoaded', function() {
  if (typeof lucide !== 'undefined') lucide.createIcons();
});
JS;
?>

<style>
/* Print-specific overrides */
@media print {
  .no-print { display: none !important; }
  .print-only { display: block !important; }

  body { background: #fff !important; color: #000 !important; }

  .paso-card {
    border: 1px solid #ccc !important;
    border-left: 4px solid #333 !important;
    margin-bottom: 20px !important;
    break-inside: avoid;
  }

  .card {
    border: 1px solid #ccc !important;
    background: #fff !important;
  }

  h2, h3, .card-title {
    color: #000 !important;
    font-family: Georgia, serif !important;
  }

  .badge {
    border: 1px solid #ccc !important;
    color: #333 !important;
    background: #f5f5f5 !important;
  }

  a[href]::after {
    content: ' (' attr(href) ')';
    font-size: 10px;
    color: #666;
  }

  a.btn[href]::after { content: ''; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
