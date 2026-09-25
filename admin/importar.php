<?php
// ============================================================
// INNOVA-STEAM — Importar usuarios desde un CSV
//
// Vive bajo /admin/ por el árbol de archivos, no por permisos: los
// permisos los pone requireLogin de abajo y entran también los
// directores, que son quienes tienen la lista del colegio en Excel.
//
// Tres pasos, y el segundo no escribe nada:
//   1. subir      → se lee el archivo
//   2. revisar    → se muestra qué se creará y qué se omitirá
//   3. importar   → se crean las cuentas y se entregan las claves
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/importacion.php';

requireLogin('admin', 'admin_colegio');

$pdo      = getDB();
$rolActor = $_SESSION['rol'];
$esAdmin  = $rolActor === 'admin';

// El colegio del director no se elige: es el suyo. Solo el admin de la
// plataforma puede importar a cualquiera.
$colegioPropio = null;
if (!$esAdmin) {
    $st = $pdo->prepare('SELECT colegio_id FROM usuarios WHERE id = ? LIMIT 1');
    $st->execute([currentUserId()]);
    $colegioPropio = (int)($st->fetchColumn() ?: 0) ?: null;
}

$colegios = $esAdmin
    ? $pdo->query('SELECT id, nombre FROM colegios WHERE activo = 1 ORDER BY nombre')->fetchAll()
    : [];

// Aulas donde se puede matricular de una vez a los estudiantes.
$aulasSql = 'SELECT a.id, a.nivel, a.grado, a.seccion, a.anio_escolar, c.nombre AS colegio
               FROM aulas a JOIN colegios c ON c.id = a.colegio_id';
if (!$esAdmin) {
    $st = $pdo->prepare($aulasSql . ' WHERE a.colegio_id = ? ORDER BY a.grado, a.seccion');
    $st->execute([$colegioPropio ?? 0]);
    $aulas = $st->fetchAll();
} else {
    $aulas = $pdo->query($aulasSql . ' ORDER BY c.nombre, a.grado, a.seccion')->fetchAll();
}
$aulasPermitidas = array_column($aulas, 'id');

$accion  = $_POST['accion'] ?? '';
$error   = '';
$plan    = null;
$resumen = null;
$avisos  = [];
$creados = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Por encima de post_max_size PHP descarta el cuerpo entero: $_POST y
    // $_FILES llegan vacíos y verifyCsrf diría "token inválido", que no es
    // lo que pasó. Se detecta antes y se dice la verdad.
    if (!$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        setFlash('error', 'El archivo supera el máximo que acepta el servidor ('
                        . ini_get('post_max_size') . '). Divídelo en dos.');
        redirect(BASE_URL . '/admin/importar.php');
    }
    verifyCsrf();
}

// ── Plantilla de ejemplo ─────────────────────────────────────
// Se sirve desde aquí y no como archivo suelto para que lleve siempre
// los mismos títulos que entiende el lector.
if (($_GET['plantilla'] ?? '') === '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="plantilla_usuarios.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // BOM: sin él Excel abre las tildes rotas
    fputcsv($out, ['nombre', 'apellido', 'email', 'codigo', 'rol', 'password'], ',', '"', '');
    fputcsv($out, ['Sofía', 'Mamani Quispe', '', '', 'estudiante', ''], ',', '"', '');
    fputcsv($out, ['Lucía', 'Quispe Pari', '', 'EST-200', 'estudiante', ''], ',', '"', '');
    fputcsv($out, ['María', 'Flores Ayala', 'mflores@colegio.edu.pe', '', 'docente', ''], ',', '"', '');
    fclose($out);
    exit;
}

// ── Credenciales de la última importación ────────────────────
// Solo desde la sesión de quien importó y solo durante media hora.
if ($accion === 'credenciales') {
    $guardado = $_SESSION['import_credenciales'] ?? null;
    if (!$guardado || (time() - (int)$guardado['ts']) > 1800) {
        unset($_SESSION['import_credenciales']);
        setFlash('error', 'Las credenciales ya no están disponibles. Vuelve a generarlas cambiando las contraseñas desde cada perfil.');
        redirect(BASE_URL . '/admin/importar.php');
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="credenciales_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Nombre', 'Apellido', 'Rol', 'Usuario', 'Contraseña'], ',', '"', '');
    foreach ($guardado['filas'] as $c) {
        fputcsv($out, [$c['nombre'], $c['apellido'], $c['rol'], $c['acceso'], $c['password']], ',', '"', '');
    }
    fclose($out);
    exit;
}

// ── Paso 1: leer y planificar ────────────────────────────────
if ($accion === 'previsualizar') {
    $archivo = $_FILES['archivo'] ?? null;

    if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = match ((int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE)) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo pesa más de lo que admite el servidor.',
            UPLOAD_ERR_NO_FILE                        => 'Elige un archivo CSV.',
            default                                   => 'No se pudo subir el archivo.',
        };
    } elseif ($archivo['size'] > 2 * 1024 * 1024) {
        $error = 'El archivo pesa más de 2 MB. Un CSV de mil estudiantes no llega a 100 KB: revisa que no sea un Excel renombrado.';
    } else {
        $leido = importLeerCsv((string)file_get_contents($archivo['tmp_name']));
        if ($leido['error']) {
            $error = $leido['error'];
        } else {
            $colegioId = $esAdmin ? ((int)($_POST['colegio_id'] ?? 0) ?: null) : $colegioPropio;
            $aulaId    = (int)($_POST['aula_id'] ?? 0);
            // El aula llega del formulario: se comprueba contra la lista
            // que este usuario puede ver, no contra la tabla entera.
            if ($aulaId > 0 && !in_array($aulaId, array_map('intval', $aulasPermitidas), true)) {
                $aulaId = 0;
            }

            $opciones = [
                'rol_defecto' => in_array($_POST['rol_defecto'] ?? '', importRolesPermitidos($rolActor), true)
                                 ? $_POST['rol_defecto'] : 'estudiante',
                'colegio_id'  => $colegioId,
                'aula_id'     => $aulaId,
                'rol_actor'   => $rolActor,
                'permitir_homonimos' => !empty($_POST['permitir_homonimos']),
            ];

            $p       = importPlanificar($pdo, $leido['filas'], $opciones);
            $plan    = $p['filas'];
            $resumen = $p['resumen'];
            $avisos  = $leido['columnas'];

            // Se guardan las filas crudas, no el plan: al confirmar se
            // vuelve a planificar contra la base, que puede haber
            // cambiado entre la revisión y el clic.
            $_SESSION['import_pendiente'] = ['filas' => $leido['filas'], 'opciones' => $opciones];
        }
    }
}

// ── Paso 2: crear las cuentas ────────────────────────────────
if ($accion === 'importar') {
    $pend = $_SESSION['import_pendiente'] ?? null;
    if (!$pend) {
        $error = 'Se perdió el archivo que estabas revisando. Vuelve a subirlo.';
    } else {
        $p   = importPlanificar($pdo, $pend['filas'], $pend['opciones']);
        $res = importEjecutar($pdo, $p['filas'], (int)($pend['opciones']['aula_id'] ?? 0));

        if ($res['error']) {
            $error   = $res['error'];
            $plan    = $p['filas'];
            $resumen = $p['resumen'];
        } else {
            $creados = $res['creados'];
            $resumen = $p['resumen'];
            unset($_SESSION['import_pendiente']);
            $_SESSION['import_credenciales'] = ['ts' => time(), 'filas' => $creados];
        }
    }
}

if ($accion === 'descartar') {
    unset($_SESSION['import_pendiente'], $_SESSION['import_credenciales']);
    redirect(BASE_URL . '/admin/importar.php');
}

$pageTitle = 'Importar usuarios';
$activeNav = 'importar';
require_once __DIR__ . '/../includes/header.php';

$etiquetaEstado = [
    'crear'  => ['Se creará',  'var(--success)'],
    'omitir' => ['Se omitirá', 'var(--warning)'],
    'error'  => ['Error',      'var(--danger)'],
];
?>

<div class="page-content">

  <div style="margin-bottom:24px">
    <h1 style="font-family:'Syne',sans-serif;font-size:24px;font-weight:700;color:var(--text-primary);margin-bottom:4px">Importar usuarios</h1>
    <p style="color:var(--text-muted);font-size:14px">
      Sube la lista del colegio en CSV y crea todas las cuentas de una vez.
    </p>
  </div>

  <?php if ($error): ?>
  <div style="background:var(--danger-light);border:1px solid var(--danger);color:var(--danger);border-radius:10px;padding:14px 16px;margin-bottom:20px;font-size:14px;line-height:1.5">
    <?= sanitize($error) ?>
  </div>
  <?php endif; ?>

  <?php if ($creados !== null): ?>
  <!-- ── Resultado ───────────────────────────────────────── -->
  <div class="card" style="margin-bottom:24px">
    <div class="card-header">
      <span style="display:inline-flex;align-items:center;gap:10px">
        <i data-lucide="check-circle" style="width:18px;height:18px;color:var(--success)"></i>
        <strong style="color:var(--text-primary)"><?= count($creados) ?> cuentas creadas</strong>
      </span>
    </div>
    <div style="padding:16px">
      <div style="background:var(--warning-light);border:1px solid var(--warning);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13px;color:var(--text-secondary);line-height:1.55">
        <strong style="color:var(--warning)">Descarga las contraseñas ahora.</strong>
        Están guardadas en claro solo durante esta media hora y solo para ti;
        después se pierden y habrá que cambiarlas una a una desde cada perfil.
      </div>

      <form method="POST" style="display:inline" data-no-loading>
        <?= csrfField() ?>
        <input type="hidden" name="accion" value="credenciales">
        <button type="submit" class="btn-primary" style="padding:10px 16px;font-size:13px;display:inline-flex;align-items:center;gap:8px">
          <i data-lucide="download" style="width:15px;height:15px"></i> Descargar credenciales (CSV)
        </button>
      </form>
      <a href="<?= BASE_URL ?>/admin/usuarios.php" style="margin-left:12px;font-size:13px;color:var(--accent);text-decoration:none;font-weight:600">Ver la lista de usuarios →</a>

      <div class="table-wrapper" style="margin-top:20px">
        <table>
          <thead><tr><th>Nombre</th><th>Rol</th><th>Usuario</th><th>Contraseña</th></tr></thead>
          <tbody>
            <?php foreach ($creados as $c): ?>
            <tr>
              <td style="font-weight:600"><?= sanitize($c['apellido'] . ', ' . $c['nombre']) ?></td>
              <td style="font-size:12px;color:var(--text-secondary)"><?= sanitize($c['rol']) ?></td>
              <td style="font-family:monospace;font-size:12.5px"><?= sanitize((string)$c['acceso']) ?></td>
              <td style="font-family:monospace;font-size:12.5px;color:var(--accent);font-weight:600"><?= sanitize($c['password']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php elseif ($plan !== null): ?>
  <!-- ── Revisión ────────────────────────────────────────── -->
  <div class="card" style="margin-bottom:24px">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div style="display:flex;gap:18px;flex-wrap:wrap;font-size:13px">
        <?php foreach ($etiquetaEstado as $clave => [$texto, $color]): ?>
        <span style="color:var(--text-secondary)">
          <strong style="color:<?= $color ?>;font-size:15px;font-variant-numeric:tabular-nums"><?= (int)$resumen[$clave] ?></strong>
          <?= $texto ?>
        </span>
        <?php endforeach; ?>
      </div>
      <div style="display:flex;gap:8px">
        <form method="POST" data-no-loading><?= csrfField() ?>
          <input type="hidden" name="accion" value="descartar">
          <button type="submit" style="background:none;border:1px solid var(--bg-border);color:var(--text-secondary);border-radius:8px;padding:9px 14px;font-size:13px;cursor:pointer;font-family:inherit">Descartar</button>
        </form>
        <?php if ((int)$resumen['crear'] > 0): ?>
        <form method="POST"><?= csrfField() ?>
          <input type="hidden" name="accion" value="importar">
          <button type="submit" class="btn-primary" style="padding:9px 16px;font-size:13px">
            Crear <?= (int)$resumen['crear'] ?> cuentas
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($avisos): ?>
    <div style="padding:12px 16px;border-bottom:1px solid var(--bg-border);font-size:12.5px;color:var(--text-muted)">
      Columnas que no se reconocieron y se ignoran: <?= sanitize(implode(', ', $avisos)) ?>.
    </div>
    <?php endif; ?>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width:60px">Línea</th>
            <th>Nombre</th>
            <th>Rol</th>
            <th>Correo / código</th>
            <th>Qué pasará</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($plan as $p):
            [$texto, $color] = $etiquetaEstado[$p['estado']];
          ?>
          <tr>
            <td style="font-variant-numeric:tabular-nums;color:var(--text-muted);font-size:12px"><?= (int)$p['_linea'] ?></td>
            <td style="font-weight:600"><?= sanitize(trim($p['apellido'] . ', ' . $p['nombre'], ', ')) ?: '—' ?></td>
            <td style="font-size:12px;color:var(--text-secondary)"><?= sanitize(ltrim((string)$p['rol'], '?')) ?></td>
            <td style="font-family:monospace;font-size:12px;color:var(--text-secondary)">
              <?= sanitize($p['email'] !== '' ? $p['email'] : ($p['codigo'] !== '' ? $p['codigo'] : '—')) ?>
            </td>
            <td style="font-size:12.5px">
              <span style="color:<?= $color ?>;font-weight:600"><?= $texto ?></span>
              <?php if ($p['motivo'] !== ''): ?>
                <span style="color:var(--text-muted)"> · <?= sanitize($p['motivo']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php else: ?>
  <?php
  // Quien importó y luego se fue a otra página seguiría necesitando las
  // claves para repartirlas. Se le vuelven a ofrecer mientras duren.
  $pendienteDeDescarga = $_SESSION['import_credenciales'] ?? null;
  if ($pendienteDeDescarga && (time() - (int)$pendienteDeDescarga['ts']) <= 1800):
    $restan = (int)ceil((1800 - (time() - (int)$pendienteDeDescarga['ts'])) / 60);
  ?>
  <div style="background:var(--warning-light);border:1px solid var(--warning);border-radius:12px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
    <span style="font-size:13px;color:var(--text-secondary);line-height:1.5">
      Todavía tienes las contraseñas de las <strong><?= count($pendienteDeDescarga['filas']) ?></strong>
      cuentas de tu última importación. Se pierden en <?= $restan ?> min.
    </span>
    <span style="display:flex;gap:8px">
      <form method="POST" data-no-loading><?= csrfField() ?>
        <input type="hidden" name="accion" value="credenciales">
        <button type="submit" class="btn-primary" style="padding:8px 14px;font-size:13px">Descargar credenciales</button>
      </form>
      <form method="POST" data-no-loading><?= csrfField() ?>
        <input type="hidden" name="accion" value="descartar">
        <button type="submit" style="background:none;border:1px solid var(--bg-border);color:var(--text-secondary);border-radius:8px;padding:8px 14px;font-size:13px;cursor:pointer;font-family:inherit">Olvidarlas</button>
      </form>
    </span>
  </div>
  <?php endif; ?>

  <!-- ── Subida ──────────────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:20px;align-items:start">

    <div class="card">
      <div class="card-header"><strong style="color:var(--text-primary)">1 · Sube el archivo</strong></div>
      <form method="POST" enctype="multipart/form-data" style="padding:18px;display:flex;flex-direction:column;gap:16px">
        <?= csrfField() ?>
        <input type="hidden" name="accion" value="previsualizar">
        <?php
        $lbl = 'display:block;font-size:12px;color:var(--text-secondary);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em';
        $inp = 'width:100%;background:var(--bg-elevated);border:1px solid var(--bg-border);color:var(--text-primary);border-radius:8px;padding:10px 12px;font-size:13px';
        ?>
        <div>
          <label for="archivo" style="<?= $lbl ?>">Archivo CSV</label>
          <input id="archivo" type="file" name="archivo" accept=".csv,text/csv,text/plain" required style="<?= $inp ?>">
        </div>

        <div>
          <label for="rol_defecto" style="<?= $lbl ?>">Rol de las filas sin columna «rol»</label>
          <select id="rol_defecto" name="rol_defecto" style="<?= $inp ?>">
            <?php foreach (importRolesPermitidos($rolActor) as $r): ?>
              <option value="<?= $r ?>" <?= $r === 'estudiante' ? 'selected' : '' ?>>
                <?= ucfirst(str_replace('_', ' ', $r)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($esAdmin): ?>
        <div>
          <label for="colegio_id" style="<?= $lbl ?>">Colegio</label>
          <select id="colegio_id" name="colegio_id" style="<?= $inp ?>">
            <option value="">Sin colegio</option>
            <?php foreach ($colegios as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= sanitize($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div>
          <label for="aula_id" style="<?= $lbl ?>">Matricular a los estudiantes en un aula (opcional)</label>
          <select id="aula_id" name="aula_id" style="<?= $inp ?>">
            <option value="0">No matricular</option>
            <?php foreach ($aulas as $a): ?>
              <option value="<?= (int)$a['id'] ?>">
                <?= sanitize(aulaLabel($a) . ($esAdmin ? ' · ' . $a['colegio'] : '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <label style="display:flex;align-items:flex-start;gap:10px;font-size:12.5px;color:var(--text-secondary);line-height:1.5;cursor:pointer">
          <input type="checkbox" name="permitir_homonimos" value="1" style="margin-top:2px;flex-shrink:0">
          <span>Crear también a quienes se llaman igual que alguien que ya está.
          Déjalo sin marcar salvo que sepas que hay tocayos: es lo que evita
          duplicar el aula entera al volver a subir la misma lista.</span>
        </label>

        <button type="submit" class="btn-primary" style="padding:12px;font-size:13.5px">
          Leer el archivo y revisar
        </button>
        <p style="font-size:12px;color:var(--text-muted);line-height:1.5;margin:0">
          Este paso no crea nada todavía: primero verás fila por fila qué se creará y qué se omitirá.
        </p>
      </form>
    </div>

    <div class="card">
      <div class="card-header"><strong style="color:var(--text-primary)">Cómo debe ser el archivo</strong></div>
      <div style="padding:18px;font-size:13px;color:var(--text-secondary);line-height:1.65">
        <p style="margin:0 0 12px">
          La primera fila son los títulos. Solo <strong>nombre</strong> y <strong>apellido</strong>
          son obligatorios; el resto se rellena solo.
        </p>
        <ul class="lista-prosa" style="margin:0 0 14px">
          <li><code>nombre</code>, <code>apellido</code> — también valen «nombres», «apellidos» o «apellido paterno» y «apellido materno» en dos columnas.</li>
          <li><code>email</code> — obligatorio para docentes, practicantes, apoderados y directores.</li>
          <li><code>codigo</code> — el código con el que entra el estudiante. Si falta, se genera uno (EST-001, EST-002…). También vale «DNI».</li>
          <li><code>rol</code> — si falta, se usa el elegido a la izquierda.</li>
          <li><code>password</code> — si falta, se genera una fácil de dictar y se te entrega al final.</li>
        </ul>
        <p style="margin:0 0 14px">
          Acepta coma o punto y coma, tildes de Excel y ñ. Las filas repetidas
          o que ya existen se omiten, no se duplican.
        </p>
        <a href="?plantilla=1" style="display:inline-flex;align-items:center;gap:8px;font-size:13px;color:var(--accent);text-decoration:none;font-weight:600">
          <i data-lucide="download" style="width:15px;height:15px"></i> Descargar plantilla de ejemplo
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
