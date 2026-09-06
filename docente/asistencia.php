<?php
// ============================================================
// INNOVA-STEAM — Asistencia del aula (docente)
//
// El practicante registraba la asistencia y nadie la leía nunca: la
// tabla se llenaba y ahí moría. Esta página es el otro extremo del
// circuito, y de aquí sale también el dato que ve el apoderado.
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('docente');

$user   = currentUser();
$pdo    = getDB();
$aulaId = (int)($_GET['aula_id'] ?? 0);

// Aulas del docente, para el selector.
$stmtAulas = $pdo->prepare(
    'SELECT a.*, c.nombre AS colegio_nombre
       FROM aulas a JOIN colegios c ON c.id = a.colegio_id
      WHERE a.docente_id = ? ORDER BY a.nivel, a.grado, a.seccion'
);
$stmtAulas->execute([$user['id']]);
$aulas = $stmtAulas->fetchAll();

// Sin aula elegida se toma la primera; si no tiene ninguna, no hay nada
// que mostrar.
if (!$aulaId && $aulas) $aulaId = (int)$aulas[0]['id'];

$aula = null;
foreach ($aulas as $a) {
    if ((int)$a['id'] === $aulaId) { $aula = $a; break; }
}

$estudiantes = [];
$sesiones    = [];
$totalSes    = 0;
$mediaAula   = null;

if ($aula) {
    // Una fila por estudiante: cuántas sesiones se registraron en su
    // aula y en cuántas estuvo. Las sesiones anteriores a que el
    // estudiante entrara al aula no cuentan, porque asistencia solo
    // tiene fila para quien ya estaba matriculado.
    $stmt = $pdo->prepare(
        "SELECT u.id, u.nombre, u.apellido, u.codigo_acceso,
                COUNT(asi.sesion_id)                          AS registradas,
                COALESCE(SUM(asi.presente), 0)                AS presentes
           FROM estudiante_aula ea
           JOIN usuarios u   ON u.id = ea.estudiante_id
           LEFT JOIN sesiones s   ON s.aula_id = ea.aula_id
           LEFT JOIN asistencia asi ON asi.sesion_id = s.id AND asi.estudiante_id = u.id
          WHERE ea.aula_id = ?
          GROUP BY u.id, u.nombre, u.apellido, u.codigo_acceso
          ORDER BY u.apellido, u.nombre"
    );
    $stmt->execute([$aulaId]);
    $estudiantes = $stmt->fetchAll();

    $stmtSes = $pdo->prepare(
        "SELECT s.id, s.fecha_sesion, s.notas,
                m.titulo AS modulo_titulo,
                CONCAT(up.nombre, ' ', up.apellido) AS practicante,
                COUNT(asi.estudiante_id)           AS registrados,
                COALESCE(SUM(asi.presente), 0)     AS presentes
           FROM sesiones s
           LEFT JOIN modulos  m  ON m.id  = s.modulo_id
           LEFT JOIN usuarios up ON up.id = s.practicante_id
           LEFT JOIN asistencia asi ON asi.sesion_id = s.id
          WHERE s.aula_id = ?
          GROUP BY s.id
          ORDER BY s.fecha_sesion DESC, s.id DESC
          LIMIT 30"
    );
    $stmtSes->execute([$aulaId]);
    $sesiones = $stmtSes->fetchAll();

    $stmtTot = $pdo->prepare('SELECT COUNT(*) FROM sesiones WHERE aula_id = ?');
    $stmtTot->execute([$aulaId]);
    $totalSes = (int)$stmtTot->fetchColumn();

    $reg = array_sum(array_map(fn($e) => (int)$e['registradas'], $estudiantes));
    $pre = array_sum(array_map(fn($e) => (int)$e['presentes'],   $estudiantes));
    $mediaAula = $reg > 0 ? (int)round($pre / $reg * 100) : null;
}

// ── Exportación CSV ───────────────────────────────────────────
// Se genera antes de imprimir nada; si no, las cabeceras ya salieron.
if ($aula && ($_GET['export'] ?? '') === 'csv') {
    $nombre = 'asistencia_' . preg_replace('/[^a-z0-9]+/i', '_', aulaLabel($aula)) . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');

    $out = fopen('php://output', 'w');
    // Excel en Windows abre el CSV en la codificación del sistema y
    // parte las tildes; el BOM le dice que es UTF-8.
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Estudiante', 'Codigo', 'Sesiones registradas', 'Presente', 'Faltas', 'Porcentaje']);
    foreach ($estudiantes as $e) {
        $r = (int)$e['registradas'];
        $p = (int)$e['presentes'];
        fputcsv($out, [
            $e['apellido'] . ', ' . $e['nombre'],
            $e['codigo_acceso'] ?? '',
            $r, $p, $r - $p,
            $r > 0 ? round($p / $r * 100) . '%' : '',
        ]);
    }
    fclose($out);
    exit;
}

$pageTitle = 'Asistencia';
$activeNav = 'asistencia';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">

  <?php if (!$aulas): ?>
    <div class="card" style="padding:32px; text-align:center;">
      <p style="color:var(--text-secondary)">Todavía no tienes ningún aula asignada.</p>
    </div>
  <?php else: ?>

  <div class="card" style="margin-bottom:24px; padding:20px 24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
      <div>
        <h1 style="font-family:'Syne',sans-serif; font-size:22px; font-weight:800; color:var(--text-primary);">
          Asistencia — <?= sanitize(aulaLabel($aula)) ?>
        </h1>
        <p style="color:var(--text-secondary); font-size:13px;">
          <?= sanitize($aula['colegio_nombre']) ?> ·
          <?php // "sesión" pierde la tilde en plural: no vale con pegarle "es".
          ?><?= $totalSes ?> <?= $totalSes === 1 ? 'sesión registrada' : 'sesiones registradas' ?>
          <?php if ($mediaAula !== null): ?> · media del aula <strong><?= $mediaAula ?>%</strong><?php endif; ?>
        </p>
      </div>

      <div style="display:flex; gap:10px; align-items:center;">
        <?php if (count($aulas) > 1): ?>
        <form method="get" data-no-loading>
          <label class="sr-only" for="aula_id">Aula</label>
          <select name="aula_id" id="aula_id" class="form-control" onchange="this.form.submit()">
            <?php foreach ($aulas as $a): ?>
            <option value="<?= (int)$a['id'] ?>" <?= (int)$a['id'] === $aulaId ? 'selected' : '' ?>>
              <?= sanitize(aulaLabel($a)) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </form>
        <?php endif; ?>

        <?php if ($estudiantes): ?>
        <a class="btn btn-secondary btn-sm" href="?aula_id=<?= $aulaId ?>&amp;export=csv">
          <i data-lucide="download" style="width:14px;height:14px"></i> CSV
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($totalSes === 0): ?>
    <div class="card" style="padding:32px; text-align:center;">
      <p style="color:var(--text-secondary)">
        Todavía no hay sesiones registradas en esta aula. Las registra el
        practicante desde su panel.
      </p>
    </div>
  <?php else: ?>

  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2 class="card-title">Por estudiante</h2></div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Estudiante</th>
            <th>Código</th>
            <th>Sesiones</th>
            <th>Presente</th>
            <th>Faltas</th>
            <th>Asistencia</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($estudiantes as $e):
            $r   = (int)$e['registradas'];
            $p   = (int)$e['presentes'];
            $pct = $r > 0 ? (int)round($p / $r * 100) : null;
            // Por debajo del 70% en el Perú se considera riesgo de
            // pérdida de año; el color lo hace visible sin leer números.
            $color = $pct === null ? 'var(--text-muted)'
                   : ($pct >= 85 ? 'var(--green)' : ($pct >= 70 ? 'var(--gold)' : 'var(--danger)'));
          ?>
          <tr>
            <td style="font-weight:600;"><?= sanitize($e['apellido'] . ', ' . $e['nombre']) ?></td>
            <td style="font-family:monospace; font-size:13px; color:var(--text-secondary);"><?= sanitize($e['codigo_acceso'] ?? '—') ?></td>
            <td style="font-variant-numeric:tabular-nums;"><?= $r ?></td>
            <td style="font-variant-numeric:tabular-nums; color:var(--green); font-weight:700;"><?= $p ?></td>
            <td style="font-variant-numeric:tabular-nums; color:<?= $r - $p > 0 ? 'var(--danger)' : 'var(--text-muted)' ?>;"><?= $r - $p ?></td>
            <td style="font-weight:700; font-variant-numeric:tabular-nums; color:<?= $color ?>;">
              <?= $pct === null ? '—' : $pct . '%' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2 class="card-title">Últimas sesiones</h2></div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Módulo</th>
            <th>Registró</th>
            <th>Presentes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sesiones as $s): ?>
          <tr>
            <td style="white-space:nowrap;"><?= sanitize(formatDate($s['fecha_sesion'])) ?></td>
            <td><?= sanitize($s['modulo_titulo'] ?? '—') ?></td>
            <td style="color:var(--text-secondary); font-size:13px;"><?= sanitize($s['practicante'] ?? '—') ?></td>
            <td style="font-variant-numeric:tabular-nums;">
              <strong><?= (int)$s['presentes'] ?></strong>
              <span style="color:var(--text-muted)">/ <?= (int)$s['registrados'] ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
