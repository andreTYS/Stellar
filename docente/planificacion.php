<?php
// ============================================================
// INNOVA-STEAM — Planificación del aula (docente)
//
// El plan del aula existía, pero solo lo podía tocar el director: el
// dashboard del docente decía "10 módulos planificados" y no había
// forma de cambiarlos desde ahí. Quien da la clase es quien sabe si
// esta semana toca terrazas o el mercado.
//
// Frente a la pantalla del director, aquí se planifica como se planifica
// de verdad: se marcan varios módulos de una vez y se reparten por
// semanas desde una fecha de inicio, en lugar de abrir un diálogo por
// módulo. Y solo salen los módulos del ciclo del aula: a 5.º de
// primaria no se le ofrece contenido de 4.º de secundaria.
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('docente');

$pdo      = getDB();
$docente  = currentUserId();
$aulas    = getAulasByDocente($docente);
$aulaId   = (int)($_GET['aula_id'] ?? 0);

// Sin aula elegida, la primera suya. Con una ajena en la URL, ninguna.
if ($aulaId && !aulaEsDelDocente($aulaId, $docente)) {
    setFlash('error', 'Ese aula no es tuya.');
    redirect(BASE_URL . '/docente/planificacion.php');
}
if (!$aulaId && $aulas) $aulaId = (int)$aulas[0]['id'];

$aula = null;
foreach ($aulas as $a) {
    if ((int)$a['id'] === $aulaId) { $aula = $a; break; }
}

// ── Acciones ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $destino = (int)($_POST['aula_id'] ?? 0);

    // El aula del formulario se comprueba igual que la de la URL: quien
    // manda el POST no tiene por qué ser el formulario que servimos.
    if (!aulaEsDelDocente($destino, $docente)) {
        setFlash('error', 'Ese aula no es tuya.');
        redirect(BASE_URL . '/docente/planificacion.php');
    }

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'planificar') {
        $ids     = array_map('intval', (array)($_POST['modulos'] ?? []));
        $ids     = array_values(array_filter($ids, static fn($i) => $i > 0));
        $inicio  = trim((string)($_POST['fecha_inicio'] ?? ''));
        $cadencia = (int)($_POST['cadencia'] ?? 7);   // 0 = sin fechas

        if (!$ids) {
            setFlash('error', 'No marcaste ningún módulo.');
            redirect(BASE_URL . '/docente/planificacion.php?aula_id=' . $destino);
        }

        // La fecha se valida aquí: un "2026-13-40" llegaría a la base y
        // se guardaría como 0000-00-00 sin quejarse.
        $fecha = null;
        if ($cadencia > 0 && $inicio !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $inicio);
            if ($d && $d->format('Y-m-d') === $inicio) $fecha = $d;
        }

        $ins = $pdo->prepare(
            'INSERT INTO aula_modulos (aula_id, modulo_id, fecha_planificada, asignado_por)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE fecha_planificada = VALUES(fecha_planificada)'
        );
        foreach ($ids as $i => $mid) {
            $cuando = null;
            if ($fecha) {
                $cuando = (clone $fecha)->modify('+' . ($i * $cadencia) . ' days')->format('Y-m-d');
            }
            $ins->execute([$destino, $mid, $cuando, $docente]);
        }
        setFlash('success', count($ids) . ' módulos en el plan del aula.');
        redirect(BASE_URL . '/docente/planificacion.php?aula_id=' . $destino);
    }

    if ($accion === 'fecha') {
        $mid   = (int)($_POST['modulo_id'] ?? 0);
        $valor = trim((string)($_POST['fecha_planificada'] ?? ''));
        $d     = DateTime::createFromFormat('Y-m-d', $valor);
        $ok    = $d && $d->format('Y-m-d') === $valor;

        $pdo->prepare('UPDATE aula_modulos SET fecha_planificada = ? WHERE aula_id = ? AND modulo_id = ?')
            ->execute([$ok ? $valor : null, $destino, $mid]);
        redirect(BASE_URL . '/docente/planificacion.php?aula_id=' . $destino);
    }

    if ($accion === 'quitar') {
        $pdo->prepare('DELETE FROM aula_modulos WHERE aula_id = ? AND modulo_id = ?')
            ->execute([$destino, (int)($_POST['modulo_id'] ?? 0)]);
        redirect(BASE_URL . '/docente/planificacion.php?aula_id=' . $destino);
    }
}

// ── Datos de la pantalla ─────────────────────────────────────
$plan = [];
if ($aulaId) {
    $st = $pdo->prepare(
        'SELECT am.modulo_id, am.fecha_planificada,
                m.titulo, m.minutos_estimados,
                c.nombre AS curso, c.color_hex, c.icono,
                COUNT(DISTINCT CASE WHEN pe.completado = 1 THEN pe.estudiante_id END) AS completados
           FROM aula_modulos am
           JOIN modulos m ON m.id = am.modulo_id
           JOIN cursos  c ON c.id = m.curso_id
      LEFT JOIN estudiante_aula ea ON ea.aula_id = am.aula_id
      LEFT JOIN progreso_estudiante pe
                 ON pe.modulo_id = am.modulo_id AND pe.estudiante_id = ea.estudiante_id
          WHERE am.aula_id = ?
       GROUP BY am.modulo_id, am.fecha_planificada, m.titulo, m.minutos_estimados,
                c.nombre, c.color_hex, c.icono
       ORDER BY am.fecha_planificada IS NULL, am.fecha_planificada, c.id'
    );
    $st->execute([$aulaId]);
    $plan = $st->fetchAll();
}

$totalEst = 0;
if ($aulaId) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM estudiante_aula WHERE aula_id = ?');
    $st->execute([$aulaId]);
    $totalEst = (int)$st->fetchColumn();
}

// Lo que se puede añadir: del ciclo del aula y todavía sin planificar.
$yaEnPlan   = array_map('intval', array_column($plan, 'modulo_id'));
$disponibles = [];
if ($aulaId) {
    $ciclo = cicloDeAula($aula);
    $st = $pdo->prepare(
        "SELECT m.id, m.titulo, m.minutos_estimados, m.orden,
                c.id AS curso_id, c.nombre AS curso, c.color_hex, c.icono
           FROM modulos m JOIN cursos c ON c.id = m.curso_id
          WHERE m.activo = 1 AND m.grado_ciclo IN (?, 'ambos')
       ORDER BY c.id, m.orden"
    );
    $st->execute([$ciclo]);
    foreach ($st->fetchAll() as $m) {
        if (in_array((int)$m['id'], $yaEnPlan, true)) continue;
        $disponibles[$m['curso']][] = $m;
    }
}

$pageTitle = 'Planificación del aula';
$activeNav = 'planificacion';
require_once __DIR__ . '/../includes/header.php';

$etq = 'display:block;font-size:12px;color:var(--text-secondary);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em';
$cmp = 'background:var(--bg-elevated);border:1px solid var(--bg-border);color:var(--text-primary);border-radius:8px;padding:9px 11px;font-size:13px';
?>

<div class="page-content">

  <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px">
    <div>
      <h1 style="font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:var(--text-primary);margin-bottom:4px">Planificación del aula</h1>
      <p style="color:var(--text-muted);font-size:13.5px">
        Qué módulo toca y cuándo. Tus estudiantes lo ven en su lista de cursos.
      </p>
    </div>
    <?php if (count($aulas) > 1): ?>
    <form method="GET" style="display:flex;align-items:center;gap:8px">
      <label for="aula_id" style="font-size:13px;color:var(--text-secondary)">Aula</label>
      <select id="aula_id" name="aula_id" onchange="this.form.submit()" style="<?= $cmp ?>">
        <?php foreach ($aulas as $a): ?>
          <option value="<?= (int)$a['id'] ?>" <?= (int)$a['id'] === $aulaId ? 'selected' : '' ?>>
            <?= sanitize(aulaLabel($a)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <?php endif; ?>
  </div>

  <?php if (!$aulaId): ?>
    <div class="empty-state">
      <div class="empty-icon"><i data-lucide="calendar" style="width:40px;height:40px;opacity:.4"></i></div>
      <p class="empty-state-title">Todavía no tienes aulas</p>
      <p class="empty-state-desc">Cuando la dirección del colegio te asigne un aula, podrás planificarla aquí.</p>
    </div>
  <?php else: ?>

  <!-- ── El plan ────────────────────────────────────────── -->
  <div class="card" style="margin-bottom:22px">
    <div class="card-header">
      <h2 class="card-title"><?= sanitize(aulaLabel($aula)) ?> · <?= count($plan) ?> módulos en el plan</h2>
      <span style="font-size:12.5px;color:var(--text-muted)"><?= $totalEst ?> estudiantes</span>
    </div>

    <?php if (!$plan): ?>
      <div style="padding:32px;text-align:center;color:var(--text-secondary);font-size:14px">
        El aula no tiene plan todavía. Marca abajo los módulos del trimestre.
      </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Módulo</th><th>Curso</th><th>Avance del aula</th><th style="width:190px">Fecha</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($plan as $p):
            $comp = (int)$p['completados'];
            $pct  = $totalEst > 0 ? (int)round($comp / $totalEst * 100) : 0;
          ?>
          <tr>
            <td style="font-weight:600">
              <?= sanitize($p['titulo']) ?>
              <div style="font-size:11.5px;color:var(--text-muted);font-weight:400"><?= (int)$p['minutos_estimados'] ?> min</div>
            </td>
            <td>
              <span class="chip-curso" style="background:<?= sanitize($p['color_hex']) ?>22;color:<?= sanitize($p['color_hex']) ?>;border:1px solid <?= sanitize($p['color_hex']) ?>44">
                <?= iconoCurso($p['icono']) ?> <?= sanitize($p['curso']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:9px">
                <div class="progress-track" style="width:90px">
                  <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= sanitize($p['color_hex']) ?>"></div>
                </div>
                <span style="font-size:12px;color:var(--text-secondary);font-variant-numeric:tabular-nums"><?= $comp ?>/<?= $totalEst ?></span>
              </div>
            </td>
            <td>
              <?php /* La fecha se mueve sola al guardar: un docente cambia
                       fechas mucho más de lo que quita módulos. */ ?>
              <form method="POST" data-no-loading style="display:flex;gap:6px;align-items:center">
                <?= csrfField() ?>
                <input type="hidden" name="accion"  value="fecha">
                <input type="hidden" name="aula_id" value="<?= (int)$aulaId ?>">
                <input type="hidden" name="modulo_id" value="<?= (int)$p['modulo_id'] ?>">
                <label class="sr-only" for="f<?= (int)$p['modulo_id'] ?>">Fecha de <?= sanitize($p['titulo']) ?></label>
                <input id="f<?= (int)$p['modulo_id'] ?>" type="date" name="fecha_planificada"
                       value="<?= sanitize((string)($p['fecha_planificada'] ?? '')) ?>"
                       style="<?= $cmp ?>;padding:6px 8px;font-size:12.5px">
                <button type="submit" style="background:none;border:none;padding:4px;cursor:pointer;color:var(--accent)" title="Guardar la fecha" aria-label="Guardar la fecha">
                  <i data-lucide="check" style="width:15px;height:15px"></i>
                </button>
              </form>
            </td>
            <td>
              <form method="POST" data-no-loading style="display:inline"
                    onsubmit="return confirm('¿Quitar «<?= sanitize($p['titulo']) ?>» del plan?')">
                <?= csrfField() ?>
                <input type="hidden" name="accion"    value="quitar">
                <input type="hidden" name="aula_id"   value="<?= (int)$aulaId ?>">
                <input type="hidden" name="modulo_id" value="<?= (int)$p['modulo_id'] ?>">
                <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;color:var(--danger);font-size:12px;font-family:inherit">Quitar</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Añadir al plan ─────────────────────────────────── -->
  <?php if ($disponibles): ?>
  <form method="POST" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="accion"  value="planificar">
    <input type="hidden" name="aula_id" value="<?= (int)$aulaId ?>">

    <div class="card-header">
      <h2 class="card-title">Añadir al plan</h2>
      <span style="font-size:12.5px;color:var(--text-muted)">
        Solo los módulos de <?= sanitize(cicloEtiqueta(cicloDeAula($aula))) ?>
      </span>
    </div>

    <div style="padding:18px">
      <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:flex-end;margin-bottom:20px;padding-bottom:18px;border-bottom:1px solid var(--bg-border)">
        <div>
          <label for="fecha_inicio" style="<?= $etq ?>">Empezar el</label>
          <input id="fecha_inicio" type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>" style="<?= $cmp ?>">
        </div>
        <div>
          <label for="cadencia" style="<?= $etq ?>">Y luego</label>
          <select id="cadencia" name="cadencia" style="<?= $cmp ?>">
            <option value="7">uno por semana</option>
            <option value="14">uno cada dos semanas</option>
            <option value="3">dos por semana</option>
            <option value="0">sin fechas, solo al plan</option>
          </select>
        </div>
        <button type="submit" class="btn-primary" style="padding:10px 18px;font-size:13px">Añadir los marcados</button>
      </div>

      <?php foreach ($disponibles as $curso => $modulos):
        $color = $modulos[0]['color_hex'];
      ?>
      <div style="margin-bottom:18px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:9px">
          <span class="chip-curso" style="background:<?= sanitize($color) ?>22;color:<?= sanitize($color) ?>;border:1px solid <?= sanitize($color) ?>44">
            <?= iconoCurso($modulos[0]['icono']) ?> <?= sanitize($curso) ?>
          </span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:8px">
          <?php foreach ($modulos as $m): ?>
          <label style="display:flex;align-items:flex-start;gap:9px;padding:10px 12px;border:1px solid var(--bg-border);border-radius:10px;cursor:pointer;font-size:13px;background:var(--bg-card)">
            <input type="checkbox" name="modulos[]" value="<?= (int)$m['id'] ?>" style="margin-top:2px;flex-shrink:0">
            <span>
              <span style="font-weight:600;color:var(--text-primary)"><?= sanitize($m['titulo']) ?></span>
              <span style="display:block;font-size:11.5px;color:var(--text-muted)"><?= (int)$m['minutos_estimados'] ?> min</span>
            </span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </form>
  <?php else: ?>
  <div class="card" style="padding:28px;text-align:center;color:var(--text-secondary);font-size:14px">
    Ya tienes en el plan todos los módulos de <?= sanitize(cicloEtiqueta(cicloDeAula($aula))) ?>.
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
