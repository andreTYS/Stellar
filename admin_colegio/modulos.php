<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin_colegio');

$user   = currentUser();
$pdo    = getDB();
$aulaId = (int)($_GET['aula_id'] ?? 0);

// Asignar módulo a aula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar'])) {
    $aId    = (int)$_POST['aula_id'];
    $mId    = (int)$_POST['modulo_id'];
    $fecha  = $_POST['fecha_planificada'] ?? null;
    try {
        $pdo->prepare("INSERT IGNORE INTO aula_modulos (aula_id, modulo_id, fecha_planificada, asignado_por) VALUES (?,?,?,?)")
            ->execute([$aId, $mId, $fecha ?: null, $user['id']]);
    } catch (Exception $e) {}
    redirect('/innovasteam/admin_colegio/modulos.php?aula_id='.$aId.'&ok=1');
}

// Desasignar
if (isset($_GET['quitar_modulo'], $_GET['aula'])) {
    $pdo->prepare("DELETE FROM aula_modulos WHERE aula_id=? AND modulo_id=?")
        ->execute([(int)$_GET['aula'], (int)$_GET['quitar_modulo']]);
    redirect('/innovasteam/admin_colegio/modulos.php?aula_id='.$_GET['aula']);
}

// Aulas del colegio
$aulas = $pdo->prepare("SELECT * FROM aulas WHERE colegio_id=? ORDER BY grado, seccion");
$aulas->execute([$user['colegio_id']]);
$aulas = $aulas->fetchAll();

// Módulos asignados al aula seleccionada
$asignados = [];
if ($aulaId) {
    $stmt = $pdo->prepare("
        SELECT am.*, m.titulo, m.minutos_estimados, c.nombre as curso_nombre, c.color_hex, c.icono,
               COUNT(DISTINCT pe.estudiante_id) as completados,
               (SELECT COUNT(*) FROM estudiante_aula ea WHERE ea.aula_id=am.aula_id) as total_est
        FROM aula_modulos am
        JOIN modulos m ON m.id=am.modulo_id
        JOIN cursos c ON c.id=m.curso_id
        LEFT JOIN progreso_estudiante pe ON pe.modulo_id=am.modulo_id AND pe.completado=1
        WHERE am.aula_id=?
        GROUP BY am.aula_id, am.modulo_id
        ORDER BY c.id, am.fecha_planificada
    ");
    $stmt->execute([$aulaId]);
    $asignados = $stmt->fetchAll();
}

// All modules (for assign form)
$todosModulos = $pdo->query("
    SELECT m.*, c.nombre as curso_nombre, c.color_hex
    FROM modulos m JOIN cursos c ON c.id=m.curso_id
    WHERE m.activo=1 ORDER BY c.id, m.orden
")->fetchAll();

$asignadosIds = array_column($asignados, 'modulo_id');

$pageTitle = 'Asignación de Módulos';
$activeNav = 'modulos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
  <?php if (isset($_GET['ok'])): ?>
  <div style="background:#22c55e22;border:1px solid #22c55e44;color:var(--success);border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:14px;">
    Módulo asignado correctamente.
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:24px;align-items:start;">
    <!-- Aula selector -->
    <div class="card">
      <h2 class="card-title" style="margin-bottom:16px;">Seleccionar aula</h2>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <?php foreach ($aulas as $aula): ?>
        <a href="modulos.php?aula_id=<?= $aula['id'] ?>"
           style="padding:10px 14px;border-radius:10px;text-decoration:none;font-size:14px;
                  background:<?= $aulaId==$aula['id']?'var(--bg-elevated)':'transparent' ?>;
                  color:<?= $aulaId==$aula['id']?'var(--text-primary)':'var(--text-secondary)' ?>;
                  border:1px solid <?= $aulaId==$aula['id']?'var(--blue)':'transparent' ?>;
                  transition:all .15s;">
          <?= $aula['grado'] ?> &ldquo;<?= sanitize($aula['seccion']) ?>&rdquo;
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Content -->
    <div>
      <?php if (!$aulaId): ?>
        <div class="card" style="text-align:center;padding:48px;">
          <div class="empty-icon"><i data-lucide="book-open" style="width:48px;height:48px;opacity:.4"></i></div>
          <p style="color:var(--text-secondary);">Selecciona un aula para gestionar sus módulos.</p>
        </div>
      <?php else: ?>

        <!-- Assigned modules -->
        <div class="card" style="margin-bottom:20px;">
          <div class="card-header">
            <h2 class="card-title">Módulos asignados (<?= count($asignados) ?>)</h2>
            <button onclick="document.getElementById('modal-asignar').style.display='flex'" class="btn-primary" style="padding:8px 14px;font-size:13px;">+ Asignar módulo</button>
          </div>
          <?php if (empty($asignados)): ?>
            <div style="text-align:center;padding:32px;">
              <p style="color:var(--text-secondary);font-size:14px;">No hay módulos asignados. Usa el botón de arriba para asignar.</p>
            </div>
          <?php else: ?>
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Módulo</th><th>Curso</th><th>Progreso</th><th>Fecha</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($asignados as $am): ?>
                <tr>
                  <td style="font-weight:600;"><?= sanitize($am['titulo']) ?></td>
                  <td>
                    <span style="background:<?= $am['color_hex'] ?>22;color:<?= $am['color_hex'] ?>;border:1px solid <?= $am['color_hex'] ?>44;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                      <?= $am['icono'] ?> <?= sanitize($am['curso_nombre']) ?>
                    </span>
                  </td>
                  <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                      <div class="progress-track" style="width:80px;">
                        <div class="progress-fill" style="width:<?= $am['total_est']>0?round($am['completados']/$am['total_est']*100):0 ?>%;background:<?= $am['color_hex'] ?>;"></div>
                      </div>
                      <span style="font-size:12px;color:var(--text-secondary);"><?= $am['completados'] ?>/<?= $am['total_est'] ?></span>
                    </div>
                  </td>
                  <td style="font-size:13px;color:var(--text-secondary);"><?= $am['fecha_planificada'] ? date('d/m/Y', strtotime($am['fecha_planificada'])) : '—' ?></td>
                  <td>
                    <a href="?aula_id=<?= $aulaId ?>&quitar_modulo=<?= $am['modulo_id'] ?>&aula=<?= $aulaId ?>"
                       style="color:var(--danger);font-size:12px;text-decoration:none;"
                       onclick="return confirm('¿Quitar este módulo del aula?')">Quitar</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal asignar -->
<div id="modal-asignar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--bg-surface);border:1px solid var(--bg-border);border-radius:16px;padding:28px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:var(--text-primary);">Asignar módulo</h2>
      <button onclick="document.getElementById('modal-asignar').style.display='none'" style="background:none;border:none;color:var(--text-secondary);cursor:pointer;font-size:20px;"><i data-lucide="x" style="width:18px;height:18px"></i></button>
    </div>
    <form method="POST" style="display:flex;flex-direction:column;gap:14px;">
      <input type="hidden" name="asignar" value="1">
      <input type="hidden" name="aula_id" value="<?= $aulaId ?>">
      <div>
        <label style="display:block;font-size:12px;color:var(--text-secondary);margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;">Módulo</label>
        <select name="modulo_id" required style="width:100%;background:var(--bg-elevated);border:1px solid var(--bg-border);color:var(--text-primary);border-radius:8px;padding:10px;font-size:13px;">
          <option value="">-- Selecciona un módulo --</option>
          <?php foreach ($todosModulos as $m):
            $yaAsignado = in_array($m['id'], $asignadosIds);
          ?>
          <option value="<?= $m['id'] ?>" <?= $yaAsignado?'disabled':'' ?>>
            <?= sanitize($m['curso_nombre'].' — '.$m['titulo']) ?><?= $yaAsignado?' (ya asignado)':'' ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:12px;color:var(--text-secondary);margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;">Fecha planificada</label>
        <input type="date" name="fecha_planificada" style="width:100%;background:var(--bg-elevated);border:1px solid var(--bg-border);color:var(--text-primary);border-radius:8px;padding:10px;font-size:13px;">
      </div>
      <button type="submit" class="btn-primary" style="padding:12px;">Asignar módulo</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
