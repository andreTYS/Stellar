<?php
// ============================================================
// INNOVA-STEAM — Visor de módulo (4 pasos)
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('estudiante');

$user     = currentUser();
$estudianteId = currentUserId();
$pdo      = getDB();
$moduloId = (int)($_GET['id'] ?? 0);

if ($moduloId <= 0) {
    redirect(BASE_URL . '/estudiante/index.php');
}

// ── Fetch modulo + curso ─────────────────────────────────────
$stmtMod = $pdo->prepare(
    'SELECT m.*, c.nombre AS curso_nombre, c.color_hex, c.icono, c.id AS curso_id
     FROM modulos m
     JOIN cursos c ON c.id = m.curso_id
     WHERE m.id = ? AND m.activo = 1'
);
$stmtMod->execute([$moduloId]);
$modulo = $stmtMod->fetch();
if (!$modulo) {
    redirect(BASE_URL . '/estudiante/index.php');
}

$color      = htmlspecialchars($modulo['color_hex'] ?? '#4a9eff', ENT_QUOTES, 'UTF-8');
$lucideIcon = $modulo['icono'] ?? 'book-open';
$duracionMin = (int)($modulo['minutos_estimados'] ?? 0);

// ── Fetch 4 pasos (with JSON decoded contenido) ──────────────
$pasos = getPasosByModulo($moduloId);

// ── Fetch or create progreso ─────────────────────────────────
$progreso = getProgreso($estudianteId, $moduloId);
if (!$progreso) {
    upsertProgreso($estudianteId, $moduloId, 1);
    $progreso = getProgreso($estudianteId, $moduloId);
}
$pasoActual = (int)($progreso['paso_actual'] ?? 1);
$completado = !empty($progreso['completado']);
$estrellas  = (int)($progreso['estrellas_quiz'] ?? 0);

// ── Fetch quiz questions for the quiz paso ───────────────────
$quizPreguntas = [];
$quizPasoId    = 0;
foreach ($pasos as $p) {
    if ($p['tipo'] === 'quiz') {
        $quizPasoId    = (int)$p['id'];
        $quizPreguntas = getPreguntasByPaso($quizPasoId);
        break;
    }
}

// ── Fetch certificate if module is completed ─────────────────
$cert = null;
if ($completado) {
    $cStmt = $pdo->prepare('SELECT * FROM certificados WHERE estudiante_id = ? AND modulo_id = ?');
    $cStmt->execute([$estudianteId, $moduloId]);
    $cert = $cStmt->fetch();
}

// ── View ─────────────────────────────────────────────────────
$pageTitle = $modulo['titulo'];
$activeNav = 'inicio';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width:860px;">
  <!-- Back link -->
  <div class="mb-20">
    <a href="<?= BASE_URL ?>/estudiante/curso.php?id=<?= (int)$modulo['curso_id'] ?>" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left" style="width:14px;height:14px"></i>
      Volver al curso
    </a>
  </div>

  <!-- Module header + step progress -->
  <div class="card mb-24" style="border-top:4px solid <?= $color ?>;">
    <div class="flex items-center gap-16 mb-20">
      <span style="width:52px;height:52px;border-radius:14px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff">
        <i data-lucide="<?= sanitize($lucideIcon) ?>" style="width:26px;height:26px"></i>
      </span>
      <div class="flex-1">
        <span style="background:<?= $color ?>22;color:<?= $color ?>;border:1px solid <?= $color ?>44;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;display:inline-block;margin-bottom:6px;">
          <?= sanitize($modulo['curso_nombre']) ?>
        </span>
        <h1 style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--text-primary);">
          <?= sanitize($modulo['titulo']) ?>
        </h1>
      </div>
    </div>

    <!-- Step indicators -->
    <div style="display:flex;align-items:center;gap:0;">
      <?php
      $stepLabels = ['Historia','Actividad','Quiz','Entregable'];
      $stepIcons  = ['book-open','flask-conical','brain','upload'];
      for ($i = 1; $i <= 4; $i++):
        $isDone    = ($i < $pasoActual) || $completado;
        $isCurrent = ($i === $pasoActual) && !$completado;
        $circBg    = $isDone ? $color : ($isCurrent ? $color . 'bb' : 'var(--bg-border)');
        $circClr   = ($isDone || $isCurrent) ? '#fff' : 'var(--text-muted)';
        $lblClr    = ($isDone || $isCurrent) ? 'var(--text-primary)' : 'var(--text-muted)';
      ?>
      <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex:1;min-width:0;">
        <div style="width:36px;height:36px;border-radius:50%;background:<?= $circBg ?>;color:<?= $circClr ?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;transition:background .4s;flex-shrink:0;">
          <?= $isDone ? '✓' : $i ?>
        </div>
        <span style="font-size:10px;color:<?= $lblClr ?>;text-align:center;line-height:1.2;"><?= $stepLabels[$i-1] ?></span>
      </div>
      <?php if ($i < 4): ?>
      <div style="flex:2;height:2px;background:<?= ($i < $pasoActual || $completado) ? $color : 'var(--bg-border)' ?>;align-self:flex-start;margin-top:18px;transition:background .4s;"></div>
      <?php endif; ?>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Step content -->
  <?php if ($completado): ?>
  <!-- COMPLETED STATE -->
  <div class="card" style="text-align:center;padding:52px 24px;border:2px solid <?= $color ?>44;">
    <div style="width:80px;height:80px;border-radius:50%;background:<?= $color ?>18;border:2px solid <?= $color ?>44;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
      <i data-lucide="trophy" style="width:36px;height:36px;color:<?= $color ?>"></i>
    </div>
    <h2 style="font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:var(--text-primary);margin-bottom:12px;">¡Módulo completado!</h2>
    <div style="display:flex;gap:4px;justify-content:center;margin:12px 0;"><?= estrellasHtml($estrellas) ?></div>
    <p style="color:var(--text-secondary);font-size:15px;margin-bottom:28px;">
      Obtuviste <strong style="color:var(--gold);"><?= $estrellas ?> estrella<?= $estrellas!=1?'s':'' ?></strong> en el quiz.
    </p>
    <div class="flex gap-12" style="justify-content:center;flex-wrap:wrap;">
      <a href="<?= BASE_URL ?>/estudiante/index.php" class="btn btn-ghost">
        <i data-lucide="layout-grid" style="width:15px;height:15px"></i>
        Mis cursos
      </a>
      <a href="<?= BASE_URL ?>/estudiante/certificado_ver.php?modulo_id=<?= $moduloId ?>" target="_blank" class="btn btn-primary">
        <i data-lucide="award" style="width:15px;height:15px"></i>
        Ver certificado
      </a>
    </div>
  </div>

  <?php else: ?>
  <!-- ACTIVE STEPS — show only current paso -->
  <?php foreach ($pasos as $paso):
    if ((int)$paso['numero_paso'] !== $pasoActual) continue;
    // contenido is already decoded by getPasosByModulo()
    $contenido = is_array($paso['contenido']) ? $paso['contenido'] : [];
  ?>

  <!-- ══ PASO 1: HISTORIA ══ -->
  <?php if ($paso['tipo'] === 'historia'): ?>
  <div class="card" id="paso-historia">

    <!-- Header row with TTS button -->
    <div class="flex items-center gap-12 mb-16" style="flex-wrap:wrap;row-gap:10px;">
      <span style="background:<?= $color ?>22;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?= $color ?>"><i data-lucide="book-open" style="width:20px;height:20px"></i></span>
      <div class="flex-1">
        <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--text-primary);">La historia</h2>
        <p style="font-size:12px;color:var(--text-muted);margin-top:2px;">Lee con atención antes de continuar</p>
      </div>
      <?php
        $rawNarrativa = $contenido['narrativa'] ?? '';
        $wordCount    = str_word_count(strip_tags($rawNarrativa));
        $readMin      = max(1, (int)ceil($wordCount / 180));
      ?>
      <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
        <span style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:5px;">
          <i data-lucide="clock" style="width:13px;height:13px"></i>
          <?= $readMin ?> min de lectura
        </span>
        <button id="tts-btn" onclick="toggleTTS()" title="Escuchar narración"
                style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:<?= $color ?>18;border:1.5px solid <?= $color ?>44;border-radius:20px;color:<?= $color ?>;font-size:12px;font-weight:700;cursor:pointer;transition:all .2s;white-space:nowrap;">
          <i data-lucide="volume-2" style="width:14px;height:14px" id="tts-icon-vol"></i>
          <span id="tts-label">Escuchar</span>
          <span id="tts-wave" style="display:none;align-items:center;gap:2px;">
            <span style="width:3px;height:10px;background:currentColor;border-radius:2px;animation:wave 1s ease-in-out infinite"></span>
            <span style="width:3px;height:14px;background:currentColor;border-radius:2px;animation:wave 1s ease-in-out infinite .15s"></span>
            <span style="width:3px;height:8px;background:currentColor;border-radius:2px;animation:wave 1s ease-in-out infinite .3s"></span>
          </span>
        </button>
      </div>
    </div>

    <!-- Reading progress bar -->
    <div style="height:3px;background:var(--bg-border);border-radius:2px;margin-bottom:24px;overflow:hidden;">
      <div id="tts-progress" style="height:100%;background:<?= $color ?>;border-radius:2px;width:0%;transition:width .3s;"></div>
    </div>

    <!-- Main narrative -->
    <div id="historia-texto" style="font-size:15px;color:var(--text-primary);line-height:1.9;margin-bottom:0;">
      <?php
        // Split narrative into intro (first paragraph) and body (rest)
        $paragraphs = array_filter(array_map('trim', preg_split('/\n{2,}/', trim($rawNarrativa))));
        $paragraphs = array_values($paragraphs);
        $intro      = $paragraphs[0] ?? '';
        $body       = array_slice($paragraphs, 1);
      ?>
      <?php if ($intro): ?>
      <p style="font-size:16px;font-weight:600;color:var(--text-primary);line-height:1.8;margin-bottom:18px;padding:18px 20px;background:<?= $color ?>0c;border-radius:12px;border-left:4px solid <?= $color ?>;">
        <?= sanitize($intro) ?>
      </p>
      <?php endif; ?>

      <?php foreach ($body as $para): ?>
      <p style="margin-bottom:14px;line-height:1.9;"><?= sanitize($para) ?></p>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($contenido['conceptos_clave'])): ?>
    <!-- Key concepts -->
    <div style="margin:24px 0;">
      <p style="font-size:11px;font-weight:800;color:<?= $color ?>;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;display:flex;align-items:center;gap:6px;">
        <i data-lucide="bookmark" style="width:13px;height:13px"></i>
        Conceptos clave
      </p>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
        <?php foreach ((array)$contenido['conceptos_clave'] as $concepto): ?>
        <div style="background:var(--bg-elevated);border:1px solid var(--bg-border);border-radius:10px;padding:12px 16px;display:flex;align-items:flex-start;gap:10px;">
          <span style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;flex-shrink:0;margin-top:5px;"></span>
          <span style="font-size:13px;color:var(--text-primary);font-weight:500;line-height:1.5;"><?= sanitize($concepto) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($contenido['dato_curioso'])): ?>
    <!-- Fun fact -->
    <div style="background:linear-gradient(135deg,<?= $color ?>12,<?= $color ?>06);border:1px solid <?= $color ?>30;border-radius:12px;padding:16px 20px;margin:20px 0;display:flex;gap:14px;align-items:flex-start;">
      <span style="flex-shrink:0;color:var(--gold)"><i data-lucide="lightbulb" style="width:22px;height:22px"></i></span>
      <div>
        <p style="font-size:11px;font-weight:800;color:<?= $color ?>;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">Dato curioso</p>
        <p style="color:var(--text-primary);font-size:14px;line-height:1.7;"><?= sanitize($contenido['dato_curioso']) ?></p>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($contenido['pregunta_disparadora'])): ?>
    <!-- Thinking question -->
    <div style="background:<?= $color ?>15;border-left:4px solid <?= $color ?>;border-radius:0 12px 12px 0;padding:16px 20px;margin:20px 0 28px;">
      <p style="font-size:11px;font-weight:800;color:<?= $color ?>;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
        <i data-lucide="message-circle" style="width:13px;height:13px"></i>
        Pregunta para reflexionar
      </p>
      <p style="color:var(--text-primary);font-size:15px;line-height:1.7;font-weight:500;"><?= sanitize($contenido['pregunta_disparadora']) ?></p>
    </div>
    <?php endif; ?>

    <button onclick="avanzarPaso(<?= $moduloId ?>, <?= (int)$paso['numero_paso'] ?>)" class="btn btn-primary btn-lg">
      Continuar a la actividad
      <i data-lucide="arrow-right" style="width:16px;height:16px"></i>
    </button>
  </div>

  <!-- ══ PASO 2: ACTIVIDAD ══ -->
  <?php elseif ($paso['tipo'] === 'actividad'): ?>
  <div class="card" id="paso-actividad">
    <div class="flex items-center gap-12 mb-20">
      <span style="background:<?= $color ?>22;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?= $color ?>"><i data-lucide="flask-conical" style="width:20px;height:20px"></i></span>
      <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;">La actividad</h2>
    </div>

    <?php if (!empty($contenido['materiales'])): ?>
    <div class="mb-20">
      <p class="form-label">Materiales necesarios</p>
      <div style="display:flex;flex-wrap:wrap;gap:8px;">
        <?php foreach ((array)$contenido['materiales'] as $mat): ?>
        <span style="background:var(--bg-elevated);border:1px solid var(--bg-border);border-radius:20px;padding:5px 14px;font-size:13px;color:var(--text-primary);">
          <?= sanitize($mat) ?>
        </span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="mb-24">
      <p class="form-label">Instrucciones paso a paso</p>
      <ol style="list-style:none;display:flex;flex-direction:column;gap:12px;">
        <?php foreach ((array)($contenido['instrucciones'] ?? []) as $idx => $inst): ?>
        <li style="display:flex;gap:14px;align-items:flex-start;">
          <span style="background:<?= $color ?>;color:#fff;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;margin-top:1px;"><?= $idx + 1 ?></span>
          <span style="color:var(--text-primary);font-size:14px;line-height:1.7;"><?= sanitize($inst) ?></span>
        </li>
        <?php endforeach; ?>
      </ol>
    </div>

    <?php if (!empty($contenido['minutos'])): ?>
    <p style="color:var(--text-secondary);font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:6px;">
      <i data-lucide="clock" style="width:14px;height:14px"></i>
      Tiempo estimado: <?= (int)$contenido['minutos'] ?> minutos
    </p>
    <?php endif; ?>

    <button onclick="avanzarPaso(<?= $moduloId ?>, <?= (int)$paso['numero_paso'] ?>)" class="btn btn-primary btn-lg">
      Listo, ir al quiz
      <i data-lucide="arrow-right" style="width:16px;height:16px"></i>
    </button>
  </div>

  <!-- ══ PASO 3: QUIZ ══ -->
  <?php elseif ($paso['tipo'] === 'quiz'): ?>
  <div class="card" id="paso-quiz">
    <div class="flex items-center gap-12 mb-20">
      <span style="background:<?= $color ?>22;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?= $color ?>"><i data-lucide="brain" style="width:20px;height:20px"></i></span>
      <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;">Quiz</h2>
      <span style="margin-left:auto;font-size:13px;color:var(--text-muted);" id="quiz-counter">
        Pregunta <span id="q-num">1</span> de <?= count($quizPreguntas) ?>
      </span>
    </div>

    <!-- Quiz progress mini bar -->
    <div class="progress-track mb-20" style="height:4px;">
      <div class="progress-fill" id="quiz-progress-bar"
           style="width:<?= count($quizPreguntas) > 0 ? round(100/count($quizPreguntas)) : 100 ?>%;background:<?= $color ?>;transition:width .4s;"></div>
    </div>

    <div id="quiz-container">
      <?php foreach ($quizPreguntas as $qi => $preg):
        // $preg['opciones'] already decoded by getPreguntasByPaso()
        $opciones = is_array($preg['opciones']) ? $preg['opciones'] : [];
      ?>
      <div class="quiz-pregunta" id="q-<?= $qi ?>" <?= $qi > 0 ? 'style="display:none;"' : '' ?>>
        <p style="font-size:16px;font-weight:600;color:var(--text-primary);margin-bottom:20px;line-height:1.6;">
          <?= sanitize($preg['texto']) ?>
        </p>
        <div style="display:flex;flex-direction:column;gap:10px;" class="opciones-group" data-respondida="">
          <?php foreach ($opciones as $oi => $opc): ?>
          <button class="opcion-btn"
                  data-pregunta="<?= (int)$preg['id'] ?>"
                  data-opcion="<?= $oi ?>"
                  data-correcta="<?= !empty($opc['correcta']) ? '1' : '0' ?>"
                  style="text-align:left;padding:14px 18px;background:var(--bg-elevated);border:2px solid var(--bg-border);border-radius:12px;color:var(--text-primary);font-size:14px;cursor:pointer;transition:all .2s;line-height:1.5;">
            <?= sanitize($opc['texto']) ?>
          </button>
          <?php endforeach; ?>
        </div>
        <div id="feedback-<?= $qi ?>" style="display:none;margin-top:14px;padding:12px 16px;border-radius:10px;font-size:14px;line-height:1.5;"></div>
        <?php if ($qi < count($quizPreguntas) - 1): ?>
        <button class="btn btn-primary btn-siguiente-preg" data-next="<?= $qi + 1 ?>"
                data-total="<?= count($quizPreguntas) ?>"
                style="margin-top:18px;display:none;">
          Siguiente pregunta
          <i data-lucide="arrow-right" style="width:15px;height:15px"></i>
        </button>
        <?php else: ?>
        <button id="btn-ver-resultado" class="btn btn-primary"
                style="margin-top:18px;display:none;">
          Ver mi resultado
          <i data-lucide="star" style="width:15px;height:15px"></i>
        </button>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Result panel -->
    <div id="quiz-resultado" style="display:none;text-align:center;padding:28px 0;">
      <div id="resultado-estrellas" style="font-size:44px;margin-bottom:12px;letter-spacing:4px;"></div>
      <h3 style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;margin-bottom:8px;" id="resultado-titulo"></h3>
      <p id="resultado-texto" style="color:var(--text-secondary);font-size:15px;margin-bottom:24px;"></p>
      <button onclick="avanzarPaso(<?= $moduloId ?>, <?= (int)$paso['numero_paso'] ?>)" class="btn btn-primary btn-lg">
        Ir al entregable
        <i data-lucide="arrow-right" style="width:16px;height:16px"></i>
      </button>
    </div>
  </div>

  <!-- ══ PASO 4: ENTREGABLE ══ -->
  <?php elseif ($paso['tipo'] === 'entregable'): ?>
  <div class="card" id="paso-entregable">
    <div class="flex items-center gap-12 mb-20">
      <span style="background:<?= $color ?>22;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?= $color ?>"><i data-lucide="folder" style="width:20px;height:20px"></i></span>
      <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;">Tu entregable</h2>
    </div>

    <?php if (!empty($contenido['consigna'])): ?>
    <div style="background:var(--bg-elevated);border-radius:12px;padding:18px 20px;margin-bottom:24px;border:1px solid var(--bg-border);">
      <p style="color:var(--text-primary);font-size:15px;line-height:1.7;"><?= sanitize($contenido['consigna']) ?></p>
    </div>
    <?php endif; ?>

    <!-- Format selector -->
    <p class="form-label mb-12">Elige el formato de presentación</p>
    <?php
    $allFormats = [
      ['dibujo_cientifico', 'microscope',  'Dibujo científico'],
      ['mural_digital',     'image',       'Mural digital'],
      ['cuento_ilustrado',  'book-open',   'Cuento ilustrado'],
      ['prototipo',         'cog',         'Prototipo'],
    ];
    $allowedFormats = !empty($contenido['formatos']) ? (array)$contenido['formatos'] : array_column($allFormats, 0);
    ?>
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:24px;" id="format-grid">
      <?php foreach ($allFormats as [$fval, $ficon, $flabel]):
        $isEnabled = in_array($fval, $allowedFormats, true);
      ?>
      <button class="format-btn" data-format="<?= $fval ?>"
              <?= !$isEnabled ? 'disabled' : '' ?>
              style="padding:16px 12px;background:var(--bg-elevated);border:2px solid var(--bg-border);border-radius:12px;cursor:<?= $isEnabled?'pointer':'not-allowed' ?>;text-align:center;transition:all .2s;<?= !$isEnabled?'opacity:.4;':'' ?>">
        <div style="margin-bottom:8px;color:var(--text-secondary)"><i data-lucide="<?= $ficon ?>" style="width:26px;height:26px"></i></div>
        <div style="font-size:12px;color:var(--text-secondary);font-weight:600;"><?= $flabel ?></div>
      </button>
      <?php endforeach; ?>
    </div>

    <!-- Upload form -->
    <form id="form-entregable" enctype="multipart/form-data">
      <input type="hidden" name="modulo_id" value="<?= $moduloId ?>">
      <input type="hidden" name="formato" id="formato-seleccionado" value="">

      <div id="drop-zone" onclick="document.getElementById('archivo-input').click()"
           style="border:2px dashed var(--bg-border);border-radius:12px;padding:36px 24px;text-align:center;cursor:pointer;transition:border-color .2s;margin-bottom:16px;">
        <div style="margin-bottom:8px;color:var(--text-muted)"><i data-lucide="camera" style="width:36px;height:36px"></i></div>
        <p style="color:var(--text-secondary);font-size:14px;margin-bottom:4px;">Haz clic para seleccionar una foto de tu trabajo</p>
        <p style="color:var(--text-muted);font-size:12px;">JPG, PNG, WEBP — máx <?= MAX_UPLOAD_MB ?> MB</p>
        <input type="file" id="archivo-input" name="archivo" accept="image/*"
               style="display:none" onchange="previewImage(this)">
      </div>

      <div id="preview-container" style="display:none;margin-bottom:16px;">
        <img id="preview-img" src="" alt="Vista previa"
             style="width:100%;max-height:220px;object-fit:cover;border-radius:10px;border:1px solid var(--bg-border);">
        <p style="font-size:12px;color:var(--text-muted);margin-top:6px;">Vista previa — haz clic arriba para cambiar la imagen</p>
      </div>

      <div id="upload-error" class="alert alert-error" style="display:none;"></div>

      <button type="button" onclick="subirEntregable(<?= $moduloId ?>)" id="btn-subir"
              class="btn btn-success btn-lg btn-block" disabled>
        <i data-lucide="upload" style="width:16px;height:16px"></i>
        Subir y completar módulo
      </button>
    </form>
  </div>
  <?php endif; // tipos de paso ?>
  <?php endforeach; // pasos ?>
  <?php endif; // completado ?>
</div>

<!-- Celebration modal -->
<div class="modal-overlay" id="modal-celebracion">
  <div class="modal-box" style="text-align:center;padding:40px 32px;max-width:440px;">
    <div style="width:64px;height:64px;border-radius:50%;background:var(--accent-light,#eef2ff);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
      <i data-lucide="award" style="width:32px;height:32px;color:var(--accent,#4361ee)"></i>
    </div>
    <h2 style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;margin-bottom:8px;color:var(--text-primary,#1a1f36);">Modulo completado</h2>
    <p style="color:var(--text-secondary,#4e5d78);font-size:14px;margin-bottom:16px;">
      <?= sanitize($modulo['titulo']) ?>
    </p>
    <div id="modal-estrellas" style="font-size:36px;margin-bottom:24px;letter-spacing:6px;color:#f59e0b;"></div>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
      <a href="<?= BASE_URL ?>/estudiante/index.php" class="btn btn-ghost">
        <i data-lucide="layout-grid" style="width:14px;height:14px"></i>
        Mis cursos
      </a>
      <a href="<?= BASE_URL ?>/estudiante/certificado_ver.php?modulo_id=<?= $moduloId ?>" target="_blank" class="btn btn-primary modal-cert-btn">
        <i data-lucide="award" style="width:14px;height:14px"></i>
        Ver certificado
      </a>
    </div>
  </div>
</div>

<script>
// ══════════════════════════════════════════════
// Quiz Logic
// ══════════════════════════════════════════════
const accentColor = '<?= $color ?>';
let quizRespuestas  = [];
let quizCorrectas   = 0;
let totalPreguntas  = <?= count($quizPreguntas) ?>;

document.querySelectorAll('.opcion-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const group = this.closest('.opciones-group');
    if (group.dataset.respondida === '1') return;
    group.dataset.respondida = '1';

    const esCorrecta = this.dataset.correcta === '1';
    const pregId     = parseInt(this.dataset.pregunta);
    const opcion     = parseInt(this.dataset.opcion);

    if (esCorrecta) quizCorrectas++;

    // Highlight answers
    group.querySelectorAll('.opcion-btn').forEach(b => {
      b.style.pointerEvents = 'none';
      if (b.dataset.correcta === '1') {
        b.style.borderColor = 'var(--success)';
        b.style.background  = '#22c55e1a';
      }
    });
    if (!esCorrecta) {
      this.style.borderColor = 'var(--danger)';
      this.style.background  = '#ef44441a';
    }

    quizRespuestas.push({ pregunta_id: pregId, opcion_elegida: opcion });

    // Show feedback
    const qIdx = parseInt(this.closest('.quiz-pregunta').id.split('-')[1]);
    const fb   = document.getElementById('feedback-' + qIdx);
    fb.style.display    = 'block';
    fb.style.background = esCorrecta ? '#22c55e1a' : '#ef44441a';
    fb.style.borderLeft = '4px solid ' + (esCorrecta ? 'var(--success)' : 'var(--danger)');
    fb.style.color      = esCorrecta ? 'var(--success)' : 'var(--danger)';
    fb.textContent      = esCorrecta
      ? '¡Correcto!'
      : 'Incorrecto — la opción correcta está marcada en verde.';

    // Show next/result button
    const nextBtn = this.closest('.quiz-pregunta').querySelector('.btn-siguiente-preg, #btn-ver-resultado');
    if (nextBtn) nextBtn.style.display = 'inline-flex';
  });
});

document.querySelectorAll('.btn-siguiente-preg').forEach(btn => {
  btn.addEventListener('click', function () {
    const nextIdx = parseInt(this.dataset.next);
    const total   = parseInt(this.dataset.total);
    this.closest('.quiz-pregunta').style.display = 'none';
    document.getElementById('q-' + nextIdx).style.display = 'block';
    document.getElementById('q-num').textContent = nextIdx + 1;
    const bar = document.getElementById('quiz-progress-bar');
    if (bar) bar.style.width = Math.round(((nextIdx + 1) / total) * 100) + '%';
  });
});

document.getElementById('btn-ver-resultado')?.addEventListener('click', function () {
  this.disabled = true;

  // Calcular estrellas en el cliente inmediatamente
  const pct = totalPreguntas > 0 ? (quizCorrectas / totalPreguntas) : 0;
  const stars = pct >= 1 ? 3 : pct >= 0.6 ? 2 : 1;

  // Mostrar resultado sin esperar el API
  document.getElementById('quiz-container').style.display = 'none';
  const resultDiv = document.getElementById('quiz-resultado');
  resultDiv.style.display = 'block';

  const starSvgFilled = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="currentColor" style="color:var(--gold);display:inline-block"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
  const starSvgEmpty  = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--bg-border);display:inline-block"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
  const starStr = starSvgFilled.repeat(stars) + starSvgEmpty.repeat(Math.max(0, 3 - stars));
  document.getElementById('resultado-estrellas').innerHTML = starStr;
  document.getElementById('resultado-titulo').textContent =
    stars === 3 ? '¡Perfecto!' : stars === 2 ? '¡Muy bien!' : '¡Buen intento!';
  document.getElementById('resultado-texto').textContent =
    `Respondiste ${quizCorrectas} de ${totalPreguntas} preguntas correctamente.`;

  // Guardar en servidor en segundo plano (no bloquea el flujo)
  fetch('<?= BASE_URL ?>/api/quiz.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ modulo_id: <?= $moduloId ?>, respuestas: quizRespuestas })
  }).catch(() => {});
});

// ══════════════════════════════════════════════
// Entregable — format selector & upload
// ══════════════════════════════════════════════
document.querySelectorAll('.format-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    document.querySelectorAll('.format-btn').forEach(b => {
      b.style.borderColor = 'var(--bg-border)';
      b.style.background  = 'var(--bg-elevated)';
    });
    this.style.borderColor = accentColor;
    this.style.background  = accentColor + '22';
    document.getElementById('formato-seleccionado').value = this.dataset.format;
    checkCanSubmit();
  });
});

// Drag-over highlight
const dropZone = document.getElementById('drop-zone');
if (dropZone) {
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = accentColor; });
  dropZone.addEventListener('dragleave', ()  => { dropZone.style.borderColor = 'var(--bg-border)'; });
  dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.style.borderColor = 'var(--bg-border)';
    const files = e.dataTransfer.files;
    if (files.length) {
      document.getElementById('archivo-input').files = files;
      previewImage(document.getElementById('archivo-input'));
    }
  });
}

function previewImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('preview-img').src = e.target.result;
      document.getElementById('preview-container').style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
    checkCanSubmit();
  }
}

function checkCanSubmit() {
  const hasFile   = !!document.getElementById('archivo-input')?.files?.length;
  const hasFormat = document.getElementById('formato-seleccionado')?.value !== '';
  const btn = document.getElementById('btn-subir');
  if (btn) btn.disabled = !(hasFile && hasFormat);
}

async function subirEntregable(moduloId) {
  const errEl   = document.getElementById('upload-error');
  const btn     = document.getElementById('btn-subir');
  const archivo = document.getElementById('archivo-input')?.files?.[0];

  errEl.style.display = 'none';

  if (!archivo) {
    errEl.textContent   = 'Por favor selecciona una imagen de tu trabajo.';
    errEl.style.display = 'block';
    return;
  }
  if (!document.getElementById('formato-seleccionado')?.value) {
    errEl.textContent   = 'Por favor selecciona un formato de presentacion.';
    errEl.style.display = 'block';
    return;
  }
  if (archivo.size > <?= MAX_UPLOAD_BYTES ?>) {
    errEl.textContent   = `La imagen supera el limite de <?= MAX_UPLOAD_MB ?> MB.`;
    errEl.style.display = 'block';
    return;
  }

  btn.disabled  = true;
  btn.innerHTML = '<i data-lucide="loader" style="width:15px;height:15px;animation:spin 1s linear infinite"></i> Completando módulo...';

  // 1. Marcar módulo como completado (genera certificado en BD)
  await avanzarPaso(moduloId, 4, false);

  // 2. Mostrar modal de celebración con estrellas SVG
  const stars = Math.max(1, <?= $estrellas ?>);
  document.getElementById('modal-estrellas').innerHTML =
    starSvgFilled.repeat(stars) + starSvgEmpty.repeat(Math.max(0, 3 - stars));
  document.getElementById('modal-celebracion').classList.add('open');
  if (typeof lucide !== 'undefined') lucide.createIcons();

  // 4. Subir archivo en segundo plano (no bloquea el flujo)
  try {
    const fd = new FormData(document.getElementById('form-entregable'));
    fetch('<?= BASE_URL ?>/api/entregable.php', { method: 'POST', body: fd }).catch(() => {});
  } catch (e) {}
}

// ══════════════════════════════════════════════
// Progress API — advance step (returns parsed JSON)
// ══════════════════════════════════════════════
async function avanzarPaso(moduloId, paso, reload = true) {
  try {
    const r = await fetch('<?= BASE_URL ?>/api/progreso.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ modulo_id: moduloId, paso: paso })
    });
    const data = await r.json();
    if (reload) window.location.reload();
    return data;
  } catch (e) {
    if (reload) window.location.reload();
    return null;
  }
}

// Spin animation for loader
const style = document.createElement('style');
style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(style);

// ══════════════════════════════════════════════
// Text-to-Speech (Web Speech API)
// ══════════════════════════════════════════════
const synth = window.speechSynthesis;
let ttsUtterance = null;
let ttsSpeaking  = false;

function toggleTTS() {
  if (!('speechSynthesis' in window)) {
    alert('Tu navegador no soporta la lectura en voz alta. Prueba con Chrome o Edge.');
    return;
  }

  if (synth.speaking || synth.pending) {
    synth.cancel();
    setTTSState(false);
    return;
  }

  const el = document.getElementById('historia-texto');
  if (!el) return;

  const texto = el.innerText || el.textContent || '';
  if (!texto.trim()) return;

  ttsUtterance = new SpeechSynthesisUtterance(texto);
  ttsUtterance.lang  = 'es-ES';
  ttsUtterance.rate  = 0.88;
  ttsUtterance.pitch = 1.05;

  // Try to pick a Spanish voice
  const voices = synth.getVoices();
  const esVoice = voices.find(v => v.lang.startsWith('es')) || null;
  if (esVoice) ttsUtterance.voice = esVoice;

  ttsUtterance.onstart = () => setTTSState(true);
  ttsUtterance.onend   = () => setTTSState(false);
  ttsUtterance.onerror = () => setTTSState(false);

  // Boundary event for progress bar
  ttsUtterance.onboundary = (e) => {
    const pct = texto.length > 0 ? Math.round((e.charIndex / texto.length) * 100) : 0;
    const bar = document.getElementById('tts-progress');
    if (bar) bar.style.width = pct + '%';
  };

  synth.speak(ttsUtterance);
}

function setTTSState(speaking) {
  ttsSpeaking = speaking;
  const btn   = document.getElementById('tts-btn');
  const label = document.getElementById('tts-label');
  const wave  = document.getElementById('tts-wave');
  const volI  = document.getElementById('tts-icon-vol');
  const bar   = document.getElementById('tts-progress');

  if (speaking) {
    if (label) label.textContent = 'Detener';
    if (wave)  wave.style.display = 'flex';
    if (volI)  volI.style.display = 'none';
    if (bar)   bar.style.transition = 'none';
  } else {
    if (label) label.textContent = 'Escuchar';
    if (wave)  wave.style.display = 'none';
    if (volI)  volI.style.display = 'block';
    if (bar) { bar.style.width = '0%'; bar.style.transition = 'width .3s'; }
  }
}

// Load voices (some browsers load them async)
if ('speechSynthesis' in window) {
  window.speechSynthesis.onvoiceschanged = () => {};
}

// Cancel TTS when leaving page
window.addEventListener('beforeunload', () => {
  if (synth && synth.speaking) synth.cancel();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
