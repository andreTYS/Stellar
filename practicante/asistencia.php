<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('practicante');

$uid = currentUserId();
$db  = getDB();

// ── POST handler ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aulaId    = (int)($_POST['aula_id']    ?? 0);
    $moduloId  = (int)($_POST['modulo_id']  ?? 0);
    $fecha     = $_POST['fecha_sesion'] ?? date('Y-m-d');
    $notas     = trim($_POST['notas'] ?? '');
    $presentes = $_POST['presentes'] ?? [];

    // Validate ownership
    $stmtCheck = $db->prepare(
        'SELECT COUNT(*) FROM practicante_aula WHERE practicante_id = ? AND aula_id = ?'
    );
    $stmtCheck->execute([$uid, $aulaId]);
    if ((int)$stmtCheck->fetchColumn() === 0) {
        setFlash('error', 'No tienes acceso a esa aula.');
        redirect(BASE_URL . '/practicante/index.php');
    }

    // Handle photo upload — stored as JSON array in fotos_actividad
    $fotosJson = null;
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fotoUrl = handleUpload($_FILES['foto'], 'sesiones');
        if ($fotoUrl) {
            $fotosJson = json_encode([$fotoUrl]);
        }
    }

    // Count attendees
    $asistentes = count($presentes);

    // Insert sesion
    $stmtSesion = $db->prepare(
        'INSERT INTO sesiones (practicante_id, aula_id, modulo_id, fecha_sesion, asistentes, notas, fotos_actividad)
         VALUES (?,?,?,?,?,?,?)'
    );
    $stmtSesion->execute([$uid, $aulaId, $moduloId, $fecha, $asistentes, $notas, $fotosJson]);
    $sesionId = (int)$db->lastInsertId();

    // Insert asistencia per student
    $stmtAsis = $db->prepare(
        'INSERT INTO asistencia (sesion_id, estudiante_id, presente) VALUES (?,?,?)'
    );
    // Load all students to mark absent/present
    $todosEst = getEstudiantesByAula($aulaId);
    foreach ($todosEst as $est) {
        $presente = in_array((string)$est['id'], array_map('strval', $presentes), true) ? 1 : 0;
        $stmtAsis->execute([$sesionId, $est['id'], $presente]);
    }

    setFlash('success', 'Sesión registrada correctamente con ' . $asistentes . ' estudiante(s) presente(s).');
    redirect(BASE_URL . '/practicante/sesiones.php');
}

// ── GET: load form data ───────────────────────────────────────
$aulaId   = (int)($_GET['aula_id']   ?? 0);
$moduloId = (int)($_GET['modulo_id'] ?? 0);

// Load all aulas del practicante for the select
$aulas = getAulasByPracticante($uid);

// If aula_id given, load its students and modules
$estudiantes = [];
$modulos     = [];
if ($aulaId > 0) {
    $estudiantes = getEstudiantesByAula($aulaId);
    $modulos     = getModulosByAula($aulaId);
}

// ── View ──────────────────────────────────────────────────────
$pageTitle = 'Registrar Sesión';
$activeNav = 'sesiones';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header flex justify-between items-center">
  <div>
    <h1 class="page-title">Registrar Sesión</h1>
    <p class="page-subtitle">Documenta la sesión de clase y toma asistencia</p>
  </div>
  <a href="<?= BASE_URL ?>/practicante/sesiones.php" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:16px;height:16px"></i>
    Volver
  </a>
</div>

<div class="card" style="max-width:780px">
  <div class="card-header">
    <h2 class="card-title">Datos de la sesión</h2>
  </div>

  <form method="post" action="<?= BASE_URL ?>/practicante/asistencia.php"
        enctype="multipart/form-data" id="formSesion">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

      <!-- Aula -->
      <div>
        <label class="form-label" for="aula_id">Aula</label>
        <select name="aula_id" id="aula_id" class="form-control" required
                onchange="this.form.submit()">
          <option value="">— Selecciona un aula —</option>
          <?php foreach ($aulas as $a): ?>
          <option value="<?= (int)$a['id'] ?>"
                  <?= $aulaId === (int)$a['id'] ? 'selected' : '' ?>>
            <?= sanitize($a['colegio_nombre'] . ' · ' . $a['grado'] . '° ' . $a['seccion']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Módulo -->
      <div>
        <label class="form-label" for="modulo_id">Módulo</label>
        <select name="modulo_id" id="modulo_id" class="form-control" required
                <?= empty($modulos) ? 'disabled' : '' ?>>
          <option value="">— Selecciona un módulo —</option>
          <?php foreach ($modulos as $m): ?>
          <option value="<?= (int)$m['id'] ?>"
                  <?= $moduloId === (int)$m['id'] ? 'selected' : '' ?>>
            <?= sanitize($m['titulo']) ?>
            <?php if (!empty($m['fecha_planificada'])): ?>
              (<?= sanitize(formatDate($m['fecha_planificada'])) ?>)
            <?php endif; ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php if ($aulaId > 0 && empty($modulos)): ?>
          <p class="text-muted text-sm mt-4">Esta aula no tiene módulos asignados.</p>
        <?php endif; ?>
      </div>

      <!-- Fecha -->
      <div>
        <label class="form-label" for="fecha_sesion">Fecha de la sesión</label>
        <input type="date" name="fecha_sesion" id="fecha_sesion" class="form-control"
               value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
      </div>

      <!-- Foto (optional) -->
      <div>
        <label class="form-label" for="foto">Foto de la sesión
          <span class="text-muted" style="font-weight:400">(opcional, máx. <?= MAX_UPLOAD_MB ?>MB)</span>
        </label>
        <input type="file" name="foto" id="foto" class="form-control"
               accept="image/jpeg,image/png,image/webp,image/gif">
      </div>
    </div>

    <!-- Notas -->
    <div style="margin-bottom:24px">
      <label class="form-label" for="notas">Notas de la sesión</label>
      <textarea name="notas" id="notas" class="form-control" rows="4"
                placeholder="Describe brevemente lo que ocurrió en la sesión, logros, dificultades..."></textarea>
    </div>

    <!-- Student attendance -->
    <?php if ($aulaId > 0 && !empty($estudiantes)): ?>
    <div style="margin-bottom:24px">
      <div class="flex justify-between items-center mb-16">
        <label class="form-label" style="margin:0">
          Lista de asistencia
          <span class="badge ml-8"><?= count($estudiantes) ?> estudiantes</span>
        </label>
        <div style="display:flex;gap:8px">
          <button type="button" class="btn btn-ghost btn-sm" onclick="toggleAll(true)">
            <i data-lucide="check-square" style="width:14px;height:14px"></i>
            Marcar todos
          </button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="toggleAll(false)">
            <i data-lucide="square" style="width:14px;height:14px"></i>
            Desmarcar todos
          </button>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px">
        <?php foreach ($estudiantes as $est): ?>
        <label class="attendance-item" style="
            display:flex;align-items:center;gap:10px;
            padding:10px 14px;
            background:var(--bg-surface);
            border:1px solid var(--border);
            border-radius:8px;
            cursor:pointer;
            transition:border-color .2s">
          <input type="checkbox" name="presentes[]" value="<?= (int)$est['id'] ?>"
                 class="attendance-check" checked
                 style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer">
          <div>
            <div style="font-size:14px;font-weight:500">
              <?= sanitize($est['apellido'] . ', ' . $est['nombre']) ?>
            </div>
            <?php if (!empty($est['codigo_acceso'])): ?>
              <div class="text-muted text-sm"><?= sanitize($est['codigo_acceso']) ?></div>
            <?php endif; ?>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <?php elseif ($aulaId > 0): ?>
    <div class="alert alert-warning mb-24">
      <i data-lucide="alert-triangle" style="width:16px;height:16px"></i>
      Esta aula no tiene estudiantes registrados.
    </div>
    <?php else: ?>
    <div class="alert mb-24" style="border-color:var(--border)">
      <i data-lucide="info" style="width:16px;height:16px;opacity:.6"></i>
      Selecciona un aula para ver la lista de estudiantes.
    </div>
    <?php endif; ?>

    <div class="flex justify-end gap-8">
      <a href="<?= BASE_URL ?>/practicante/index.php" class="btn btn-ghost">Cancelar</a>
      <button type="submit" class="btn btn-primary"
              <?= ($aulaId === 0 || empty($modulos)) ? 'disabled' : '' ?>>
        <i data-lucide="save" style="width:16px;height:16px"></i>
        Guardar sesión
      </button>
    </div>
  </form>
</div>

<?php
$inlineJs = <<<'JS'
function toggleAll(state) {
  document.querySelectorAll('.attendance-check').forEach(cb => cb.checked = state);
}
// Highlight checked items
document.querySelectorAll('.attendance-check').forEach(cb => {
  cb.addEventListener('change', function() {
    this.closest('.attendance-item').style.borderColor = this.checked ? 'var(--accent)' : 'var(--border)';
  });
  if (cb.checked) cb.closest('.attendance-item').style.borderColor = 'var(--accent)';
});
JS;
?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
