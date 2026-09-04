<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/chatbot.php';
requireLogin('admin');

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}

// ── Asistente: clave de API por colegio ──────────────────────────
// Cada colegio paga su propio consumo, así que cada uno trae su clave.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'chatbot') {
    $colegioId = (int)($_POST['colegio_id'] ?? 0);
    $clave     = trim((string)($_POST['api_key'] ?? ''));
    $modelo    = trim((string)($_POST['modelo'] ?? '')) ?: CHATBOT_MODELO_DEFECTO;
    $tope      = max(1, min(500, (int)($_POST['tope_dia'] ?? 30)));
    $activo    = isset($_POST['activo']) ? 1 : 0;

    if ($colegioId <= 0) {
        $msg = 'error:Colegio no válido.';
    } elseif (($_POST['quitar'] ?? '') === '1') {
        $pdo->prepare('UPDATE colegios SET chatbot_clave=NULL, chatbot_pista=NULL, chatbot_activo=0 WHERE id=?')
            ->execute([$colegioId]);
        $msg = 'success:Clave eliminada. El asistente queda desactivado para ese colegio.';
    } elseif ($clave !== '') {
        // Sin secreto maestro no se guarda nada: es preferible negarse a
        // dejar una clave de API en claro dentro de la base.
        if (chatbotClaveMaestra() === null) {
            $msg = 'error:Falta CHATBOT_CLAVE_MAESTRA en includes/config.local.php. '
                 . 'Sin ese secreto la clave se guardaría sin cifrar, así que no se guarda.';
        } else {
            $cifrada = chatbotCifrar($clave);
            $pista   = substr($clave, -4);
            $pdo->prepare(
                'UPDATE colegios SET chatbot_clave=?, chatbot_pista=?, chatbot_modelo=?,
                        chatbot_tope_dia=?, chatbot_activo=? WHERE id=?'
            )->execute([$cifrada, $pista, $modelo, $tope, $activo, $colegioId]);
            $msg = 'success:Clave guardada y cifrada.';
        }
    } else {
        // Sin clave nueva: solo se actualizan los ajustes.
        $pdo->prepare(
            'UPDATE colegios SET chatbot_modelo=?, chatbot_tope_dia=?, chatbot_activo=? WHERE id=?'
        )->execute([$modelo, $tope, $activo, $colegioId]);
        $msg = 'success:Ajustes del asistente actualizados.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') !== 'chatbot') {
    $nombre  = sanitize($_POST['nombre']);
    $distrito = sanitize($_POST['distrito']);
    $ugel    = sanitize($_POST['ugel_codigo'] ?? '');
    $director = sanitize($_POST['director'] ?? '');
    $telefono = sanitize($_POST['telefono'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($id) {
        $pdo->prepare("UPDATE colegios SET nombre=?,distrito=?,ugel_codigo=?,director=?,telefono=? WHERE id=?")
            ->execute([$nombre,$distrito,$ugel,$director,$telefono,$id]);
        $msg = 'success:Colegio actualizado.';
    } else {
        $pdo->prepare("INSERT INTO colegios (nombre,distrito,ugel_codigo,director,telefono) VALUES (?,?,?,?,?)")
            ->execute([$nombre,$distrito,$ugel,$director,$telefono]);
        $msg = 'success:Colegio creado.';
    }
}

$colegios = $pdo->query("
    SELECT col.*,
           COUNT(DISTINCT u.id)  as total_usuarios,
           SUM(u.rol='estudiante' AND u.activo=1) as total_est,
           SUM(u.rol='docente'    AND u.activo=1) as total_doc,
           SUM(u.rol='practicante' AND u.activo=1) as total_prac,
           (SELECT COUNT(*) FROM aulas a WHERE a.colegio_id=col.id) as total_aulas,
           (SELECT COUNT(*) FROM progreso_estudiante pe
            JOIN usuarios ue ON ue.id=pe.estudiante_id AND ue.colegio_id=col.id
            WHERE pe.completado=1) as total_completaciones
    FROM colegios col
    LEFT JOIN usuarios u ON u.colegio_id=col.id
    WHERE col.activo=1
    GROUP BY col.id
    ORDER BY col.nombre
")->fetchAll();

$editData = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM colegios WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch();
}

[$msgType,$msgText] = $msg ? explode(':', $msg, 2) : ['',''];
$pageTitle = 'Colegios';
$activeNav = 'colegios';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">Colegios</h1>
    <p class="page-subtitle"><?= count($colegios) ?> instituciones registradas</p>
  </div>
</div>

<div class="page-content">
  <?php if ($msgText): ?>
  <div style="background:<?=$msgType==='success'?'#22c55e22':'#ef444422'?>; border:1px solid <?=$msgType==='success'?'#22c55e44':'#ef444444'?>; color:<?=$msgType==='success'?'var(--success)':'var(--danger)'?>; border-radius:10px; padding:12px 16px; margin-bottom:20px;">
    <?= sanitize($msgText) ?>
  </div>
  <?php endif; ?>

  <div style="display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start;">
    <!-- List -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Colegios registrados</h2>
        <span style="color:var(--text-secondary); font-size:13px;"><?= count($colegios) ?> colegios</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Distrito</th>
              <th style="text-align:center">Aulas</th>
              <th style="text-align:center">Est.</th>
              <th style="text-align:center">Doc.</th>
              <th style="text-align:center">Complet.</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($colegios as $col): ?>
            <tr>
              <td>
                <div style="font-weight:600;font-size:13px"><?= sanitize($col['nombre']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= sanitize($col['director'] ?? '—') ?></div>
              </td>
              <td style="color:var(--text-secondary);font-size:13px"><?= sanitize($col['distrito']) ?></td>
              <td style="text-align:center;color:var(--gold);font-weight:700"><?= $col['total_aulas'] ?></td>
              <td style="text-align:center;color:var(--accent);font-weight:700"><?= $col['total_est'] ?></td>
              <td style="text-align:center;color:var(--blue);font-weight:700"><?= $col['total_doc'] ?></td>
              <td style="text-align:center;color:var(--green);font-weight:700"><?= $col['total_completaciones'] ?></td>
              <td><a href="?edit=<?= $col['id'] ?>" style="color:var(--accent);font-size:12px;text-decoration:none">Editar</a></td>
            </tr>
            <tr>
              <td colspan="7" style="background:var(--bg-elevated);padding:14px 16px">
                <?php
                  $tieneClave = !empty($col['chatbot_pista']);
                  $encendido  = !empty($col['chatbot_activo']);
                ?>
                <form method="POST" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
                  <?= csrfField() ?>
                  <input type="hidden" name="accion" value="chatbot">
                  <input type="hidden" name="colegio_id" value="<?= (int)$col['id'] ?>">

                  <div style="flex:2;min-width:210px">
                    <label for="key-<?= (int)$col['id'] ?>"
                           style="display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:4px">
                      Clave de API del asistente
                      <?php if ($tieneClave): ?>
                        <span style="color:var(--success);text-transform:none;letter-spacing:0">
                          · guardada, termina en <?= sanitize($col['chatbot_pista']) ?>
                        </span>
                      <?php endif; ?>
                    </label>
                    <input id="key-<?= (int)$col['id'] ?>" type="password" name="api_key"
                           autocomplete="off" spellcheck="false"
                           placeholder="<?= $tieneClave ? 'Dejar vacío para conservar la actual' : 'sk-ant-…' ?>"
                           style="width:100%;background:var(--bg-surface);border:1px solid var(--bg-border);color:var(--text-primary);border-radius:8px;padding:7px 10px;font-size:12px;font-family:monospace">
                  </div>

                  <div style="min-width:150px">
                    <label for="mod-<?= (int)$col['id'] ?>"
                           style="display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:4px">Modelo</label>
                    <select id="mod-<?= (int)$col['id'] ?>" name="modelo"
                            style="width:100%;background:var(--bg-surface);border:1px solid var(--bg-border);color:var(--text-primary);border-radius:8px;padding:7px;font-size:12px">
                      <?php
                      // Opus 5 por defecto; los otros dos, más baratos, por
                      // si un colegio prefiere gastar menos por consulta.
                      $modelos = [
                        'claude-opus-5'   => 'Opus 5 (recomendado)',
                        'claude-sonnet-5' => 'Sonnet 5',
                        'claude-haiku-4-5'=> 'Haiku 4.5 (más barato)',
                      ];
                      $actual = $col['chatbot_modelo'] ?? CHATBOT_MODELO_DEFECTO;
                      foreach ($modelos as $v => $etiqueta): ?>
                        <option value="<?= $v ?>" <?= $v === $actual ? 'selected' : '' ?>><?= $etiqueta ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div style="width:110px">
                    <label for="tope-<?= (int)$col['id'] ?>"
                           style="display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:4px">Tope/día</label>
                    <input id="tope-<?= (int)$col['id'] ?>" type="number" name="tope_dia" min="1" max="500"
                           value="<?= (int)($col['chatbot_tope_dia'] ?? 30) ?>"
                           style="width:100%;background:var(--bg-surface);border:1px solid var(--bg-border);color:var(--text-primary);border-radius:8px;padding:7px 10px;font-size:12px">
                  </div>

                  <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-secondary);padding-bottom:8px">
                    <input type="checkbox" name="activo" value="1" <?= $encendido ? 'checked' : '' ?>>
                    Activo
                  </label>

                  <button type="submit" class="btn-primary" style="padding:7px 14px;font-size:12px">Guardar</button>

                  <?php if ($tieneClave): ?>
                  <button type="submit" name="quitar" value="1"
                          onclick="return confirm('¿Eliminar la clave de este colegio? El asistente dejará de funcionar para sus estudiantes.')"
                          style="background:none;border:1px solid var(--danger);color:var(--danger);border-radius:8px;padding:7px 12px;font-size:12px;cursor:pointer">
                    Quitar clave
                  </button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Form -->
    <div class="card">
      <h2 class="card-title" style="margin-bottom:20px;"><?= $editData ? 'Editar colegio' : 'Nuevo colegio' ?></h2>
      <form method="POST" style="display:flex; flex-direction:column; gap:14px;">
      <?= csrfField() ?>
        <?php if ($editData): ?>
          <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>
        <?php
        $fields = [
          ['nombre','Nombre del colegio','required'],
          ['distrito','Distrito','required'],
          ['ugel_codigo','Código UGEL',''],
          ['director','Director(a)',''],
          ['telefono','Teléfono',''],
        ];
        foreach ($fields as [$fname,$flabel,$req]):
          $val = $editData[$fname] ?? '';
        ?>
        <div>
          <label style="display:block; font-size:12px; color:var(--text-secondary); margin-bottom:4px; text-transform:uppercase; letter-spacing:.05em;"><?= $flabel ?></label>
          <input type="text" name="<?= $fname ?>" value="<?= sanitize($val) ?>" <?= $req ?>
            style="width:100%; background:var(--bg-elevated); border:1px solid var(--bg-border); color:var(--text-primary); border-radius:8px; padding:10px 12px; font-size:13px;">
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-primary" style="margin-top:8px; padding:12px;">
          <?= $editData ? 'Actualizar' : 'Crear colegio' ?>
        </button>
        <?php if ($editData): ?>
          <a href="colegios.php" class="btn-ghost" style="text-align:center; text-decoration:none; padding:10px;">Cancelar</a>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
