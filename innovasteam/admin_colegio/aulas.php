<?php
// ============================================================
// INNOVA-STEAM — Gestión de Aulas (admin_colegio)
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('admin_colegio');

$user   = currentUser();
$userId = currentUserId();
$pdo    = getDB();

// ── Resolve colegio ───────────────────────────────────────────
$stmtC = $pdo->prepare(
    'SELECT * FROM colegios WHERE director_id = ? AND activo = 1 LIMIT 1'
);
$stmtC->execute([$userId]);
$colegio = $stmtC->fetch();

if (!$colegio) {
    $stmtFb = $pdo->prepare('SELECT colegio_id FROM usuarios WHERE id = ? LIMIT 1');
    $stmtFb->execute([$userId]);
    $colegioId = (int)($stmtFb->fetchColumn() ?: 0);
    if ($colegioId) {
        $stmtC2 = $pdo->prepare('SELECT * FROM colegios WHERE id = ? LIMIT 1');
        $stmtC2->execute([$colegioId]);
        $colegio = $stmtC2->fetch();
    }
}

$colegioId = $colegio ? (int)$colegio['id'] : 0;

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Create aula ───────────────────────────────────────────
    if ($action === 'create_aula') {
        $grado      = (int)($_POST['grado']        ?? 0);
        $seccion    = strtoupper(trim($_POST['seccion']    ?? ''));
        $anio       = (int)($_POST['anio_escolar'] ?? date('Y'));
        $docenteId  = (int)($_POST['docente_id']  ?? 0) ?: null;

        $errors = [];
        if ($grado < 1 || $grado > 6)   $errors[] = 'El grado debe estar entre 1 y 6.';
        if (empty($seccion))             $errors[] = 'La sección es requerida.';
        if (strlen($seccion) > 5)        $errors[] = 'La sección es demasiado larga.';
        if ($anio < 2020 || $anio > 2035) $errors[] = 'El año escolar no es válido.';

        if (empty($errors)) {
            try {
                $pdo->prepare(
                    'INSERT INTO aulas (colegio_id, grado, seccion, anio_escolar, docente_id, creado_en)
                     VALUES (?, ?, ?, ?, ?, NOW())'
                )->execute([$colegioId, $grado, $seccion, $anio, $docenteId]);
                setFlash('success', "Aula {$grado}° {$seccion} creada correctamente.");
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    setFlash('error', 'Ya existe un aula con ese grado, sección y año escolar.');
                } else {
                    setFlash('error', 'Error al crear el aula: ' . $e->getMessage());
                }
            }
        } else {
            setFlash('error', implode(' ', $errors));
        }
        redirect(BASE_URL . '/admin_colegio/aulas.php');
    }

    // ── Assign practicante ────────────────────────────────────
    if ($action === 'assign_practicante') {
        $aulaId       = (int)($_POST['aula_id']       ?? 0);
        $practicanteId = (int)($_POST['practicante_id'] ?? 0);

        if (!$aulaId || !$practicanteId) {
            setFlash('error', 'Datos incompletos para la asignación.');
            redirect(BASE_URL . '/admin_colegio/aulas.php');
        }

        // Verify aula belongs to this school
        $stmtOwn = $pdo->prepare('SELECT id FROM aulas WHERE id = ? AND colegio_id = ?');
        $stmtOwn->execute([$aulaId, $colegioId]);
        if (!$stmtOwn->fetch()) {
            setFlash('error', 'El aula no pertenece a tu colegio.');
            redirect(BASE_URL . '/admin_colegio/aulas.php');
        }

        try {
            $pdo->prepare(
                'INSERT INTO practicante_aula (practicante_id, aula_id, asignado_en)
                 VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE asignado_en = NOW()'
            )->execute([$practicanteId, $aulaId]);
            setFlash('success', 'Practicante asignado al aula correctamente.');
        } catch (PDOException $e) {
            setFlash('error', 'Error al asignar practicante: ' . $e->getMessage());
        }
        redirect(BASE_URL . '/admin_colegio/aulas.php');
    }

    // ── Remove practicante ────────────────────────────────────
    if ($action === 'remove_practicante') {
        $aulaId       = (int)($_POST['aula_id']       ?? 0);
        $practicanteId = (int)($_POST['practicante_id'] ?? 0);

        $stmtOwn = $pdo->prepare('SELECT id FROM aulas WHERE id = ? AND colegio_id = ?');
        $stmtOwn->execute([$aulaId, $colegioId]);
        if ($stmtOwn->fetch() && $aulaId && $practicanteId) {
            $pdo->prepare(
                'DELETE FROM practicante_aula WHERE practicante_id = ? AND aula_id = ?'
            )->execute([$practicanteId, $aulaId]);
            setFlash('success', 'Practicante removido del aula.');
        } else {
            setFlash('error', 'No se pudo remover el practicante.');
        }
        redirect(BASE_URL . '/admin_colegio/aulas.php');
    }
}

// ── GET data ──────────────────────────────────────────────────

// Aulas with docente + practicantes + student count
$stmtAulas = $pdo->prepare(
    'SELECT
        a.id,
        a.grado,
        a.seccion,
        a.anio_escolar,
        a.docente_id,
        CONCAT(COALESCE(ud.nombre,\'\'), \' \', COALESCE(ud.apellido,\'\')) AS docente_nombre,
        (SELECT COUNT(*) FROM estudiante_aula ea WHERE ea.aula_id = a.id) AS total_estudiantes
     FROM aulas a
     LEFT JOIN usuarios ud ON ud.id = a.docente_id
     WHERE a.colegio_id = ?
     ORDER BY a.grado, a.seccion'
);
$stmtAulas->execute([$colegioId]);
$aulas = $stmtAulas->fetchAll();

// Load practicantes for each aula
$aulasPracticantes = [];
if (!empty($aulas)) {
    $aulaIds    = array_column($aulas, 'id');
    $inMarks    = implode(',', array_fill(0, count($aulaIds), '?'));
    $stmtPA     = $pdo->prepare(
        "SELECT pa.aula_id, pa.practicante_id,
                CONCAT(u.nombre, ' ', u.apellido) AS nombre,
                u.email
         FROM practicante_aula pa
         JOIN usuarios u ON u.id = pa.practicante_id
         WHERE pa.aula_id IN ({$inMarks})"
    );
    $stmtPA->execute($aulaIds);
    foreach ($stmtPA->fetchAll() as $row) {
        $aulasPracticantes[(int)$row['aula_id']][] = $row;
    }
}

// Docentes de este colegio for select
$stmtDocentes = $pdo->prepare(
    'SELECT id, nombre, apellido, email
     FROM usuarios
     WHERE rol = \'docente\' AND activo = 1
     ORDER BY apellido, nombre'
);
$stmtDocentes->execute();
$docentes = $stmtDocentes->fetchAll();

// Available practicantes (global pool — can be assigned to multiple aulas)
$stmtPract = $pdo->prepare(
    'SELECT id, nombre, apellido, email
     FROM usuarios
     WHERE rol = \'practicante\' AND activo = 1
     ORDER BY apellido, nombre'
);
$stmtPract->execute();
$practicantes = $stmtPract->fetchAll();

// Pre-select aula for assign modal (from query string)
$assignAulaId = (int)($_GET['id'] ?? 0);
$openAssign   = isset($_GET['action']) && $_GET['action'] === 'assign' && $assignAulaId > 0;

// ── Page setup ────────────────────────────────────────────────
$pageTitle = 'Gestión de Aulas';
$activeNav = 'aulas';
$extraCss  = ['sidebar.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header flex justify-between items-center">
  <div>
    <h1 class="page-title">Aulas</h1>
    <p class="page-subtitle">Crea y gestiona las aulas de tu colegio</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('modalNuevaAula')">
    <i data-lucide="plus" style="width:16px;height:16px"></i>
    Nueva aula
  </button>
</div>

<!-- ── Aulas list ───────────────────────────────────────────── -->
<?php if (empty($aulas)): ?>
<div class="card">
  <div class="empty-state">
    <div class="empty-icon">
      <i data-lucide="layers" style="width:48px;height:48px;opacity:.3"></i>
    </div>
    <h3>No hay aulas registradas</h3>
    <p>Empieza creando la primera aula para tu colegio.</p>
    <button class="btn btn-primary mt-16" onclick="openModal('modalNuevaAula')">
      <i data-lucide="plus" style="width:16px;height:16px"></i>
      Nueva aula
    </button>
  </div>
</div>
<?php else: ?>

<div style="display:flex;flex-direction:column;gap:16px">
  <?php foreach ($aulas as $aula):
    $aulaId = (int)$aula['id'];
    $pracsAula = $aulasPracticantes[$aulaId] ?? [];
  ?>
  <div class="card" style="padding:20px">
    <div style="display:flex;align-items:flex-start;gap:20px">

      <!-- Grade badge -->
      <div style="
          width:52px;height:52px;border-radius:14px;
          background:linear-gradient(135deg,var(--blue),var(--purple));
          display:flex;align-items:center;justify-content:center;
          flex-shrink:0;font-family:'Syne',sans-serif;font-weight:800;font-size:18px;color:#fff">
        <?= (int)$aula['grado'] ?>°
      </div>

      <!-- Info -->
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
          <h3 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700">
            <?= (int)$aula['grado'] ?>° <?= sanitize($aula['seccion']) ?>
          </h3>
          <span class="badge badge-muted"><?= (int)$aula['anio_escolar'] ?></span>
          <span class="badge">
            <i data-lucide="users" style="width:12px;height:12px"></i>
            <?= (int)$aula['total_estudiantes'] ?> estudiantes
          </span>
        </div>

        <!-- Docente -->
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px;font-size:13px;color:var(--text-secondary)">
          <i data-lucide="user" style="width:14px;height:14px"></i>
          <span>Docente:
            <strong style="color:var(--text-primary)">
              <?= trim($aula['docente_nombre']) ?: 'Sin asignar' ?>
            </strong>
          </span>
        </div>

        <!-- Practicantes -->
        <div style="margin-bottom:12px">
          <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:6px">
            Practicantes asignados
          </div>
          <?php if (empty($pracsAula)): ?>
            <span class="badge badge-warning">Sin practicante asignado</span>
          <?php else: ?>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
              <?php foreach ($pracsAula as $p): ?>
              <div style="
                  display:flex;align-items:center;gap:8px;
                  background:var(--bg-elevated);border:1px solid var(--bg-border);
                  border-radius:8px;padding:6px 10px;font-size:13px">
                <span><?= sanitize($p['nombre']) ?></span>
                <!-- Remove practicante form -->
                <form method="post" action="" style="display:inline"
                      onsubmit="return confirm('¿Remover a <?= sanitize(addslashes($p['nombre'])) ?> de esta aula?')">
                  <input type="hidden" name="action"         value="remove_practicante">
                  <input type="hidden" name="aula_id"        value="<?= $aulaId ?>">
                  <input type="hidden" name="practicante_id" value="<?= (int)$p['practicante_id'] ?>">
                  <button type="submit" class="btn-icon"
                          style="background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;display:flex"
                          title="Remover practicante">
                    <i data-lucide="x" style="width:14px;height:14px"></i>
                  </button>
                </form>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Actions -->
      <div style="display:flex;gap:8px;flex-shrink:0">
        <button class="btn btn-ghost btn-sm"
                onclick="openAssignModal(<?= $aulaId ?>, '<?= (int)$aula['grado'] ?>° <?= sanitize(addslashes($aula['seccion'])) ?>')">
          <i data-lucide="user-plus" style="width:14px;height:14px"></i>
          Asignar practicante
        </button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>


<!-- ══════════════════════════════════════════════════════════
     Modal: Nueva Aula
     ══════════════════════════════════════════════════════════ -->
<div id="modalNuevaAula" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalNuevaAulaTitle">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('modalNuevaAula')" aria-label="Cerrar">
      <i data-lucide="x" style="width:20px;height:20px"></i>
    </button>

    <h2 id="modalNuevaAulaTitle" class="card-title mb-20">Nueva aula</h2>

    <form method="post" action="">
      <input type="hidden" name="action" value="create_aula">

      <div class="form-group">
        <label class="form-label" for="grado">Grado *</label>
        <select name="grado" id="grado" class="form-control" required>
          <option value="">— Selecciona —</option>
          <?php for ($g = 1; $g <= 6; $g++): ?>
          <option value="<?= $g ?>"><?= $g ?>° grado</option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="seccion">Sección *</label>
        <input type="text" name="seccion" id="seccion" class="form-control"
               placeholder="Ej: A, B, C" maxlength="5" required
               style="text-transform:uppercase">
      </div>

      <div class="form-group">
        <label class="form-label" for="anio_escolar">Año escolar *</label>
        <select name="anio_escolar" id="anio_escolar" class="form-control" required>
          <?php
          $anioActual = (int)date('Y');
          for ($y = $anioActual - 1; $y <= $anioActual + 1; $y++):
          ?>
          <option value="<?= $y ?>" <?= $y === $anioActual ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="docente_id">Docente asignado</label>
        <select name="docente_id" id="docente_id" class="form-control">
          <option value="">— Sin asignar —</option>
          <?php foreach ($docentes as $d): ?>
          <option value="<?= (int)$d['id'] ?>">
            <?= sanitize($d['nombre'] . ' ' . $d['apellido']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:24px">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalNuevaAula')">
          Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="plus" style="width:16px;height:16px"></i>
          Crear aula
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     Modal: Asignar Practicante
     ══════════════════════════════════════════════════════════ -->
<div id="modalAsignar" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalAsignarTitle">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('modalAsignar')" aria-label="Cerrar">
      <i data-lucide="x" style="width:20px;height:20px"></i>
    </button>

    <h2 id="modalAsignarTitle" class="card-title mb-4">Asignar practicante</h2>
    <p id="modalAsignarSubtitle" class="text-muted text-sm mb-20"></p>

    <form method="post" action="">
      <input type="hidden" name="action"  value="assign_practicante">
      <input type="hidden" name="aula_id" id="assignAulaId" value="">

      <div class="form-group">
        <label class="form-label" for="practicante_id">Practicante *</label>
        <?php if (empty($practicantes)): ?>
          <p class="text-muted text-sm" style="padding:12px;background:var(--bg-elevated);border-radius:8px">
            No hay practicantes disponibles en el sistema.
            <a href="<?= BASE_URL ?>/admin/usuarios.php" style="color:var(--blue)">
              Gestionar usuarios →
            </a>
          </p>
        <?php else: ?>
        <select name="practicante_id" id="practicante_id" class="form-control" required>
          <option value="">— Selecciona un practicante —</option>
          <?php foreach ($practicantes as $p): ?>
          <option value="<?= (int)$p['id'] ?>">
            <?= sanitize($p['nombre'] . ' ' . $p['apellido']) ?>
            <?php if (!empty($p['email'])): ?>
              · <?= sanitize($p['email']) ?>
            <?php endif; ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
      </div>

      <?php if (!empty($practicantes)): ?>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:24px">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalAsignar')">
          Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="user-check" style="width:16px;height:16px"></i>
          Confirmar asignación
        </button>
      </div>
      <?php else: ?>
      <div style="text-align:right;margin-top:16px">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalAsignar')">Cerrar</button>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>


<?php
$openAssignJs = $openAssign
    ? "openAssignModal({$assignAulaId}, '');"
    : '';

$inlineJs = <<<JS
function openModal(id) {
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}

function openAssignModal(aulaId, aulaLabel) {
  document.getElementById('assignAulaId').value = aulaId;
  document.getElementById('modalAsignarSubtitle').textContent =
    aulaLabel ? 'Aula: ' + aulaLabel : '';
  openModal('modalAsignar');
}

// Close modal on backdrop click
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) {
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    }
  });
});

// Uppercase seccion input
var seccionInput = document.getElementById('seccion');
if (seccionInput) {
  seccionInput.addEventListener('input', function() {
    var pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });
}

// Auto-open assign modal from URL param
{$openAssignJs}
JS;
?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
