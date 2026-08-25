<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin');

$pdo      = getDB();
$moduloId = (int)($_GET['modulo_id'] ?? 0);
if ($moduloId <= 0) redirect(BASE_URL . '/admin/modulos.php');

$modulo = $pdo->prepare('SELECT m.*, c.nombre as curso_nombre, c.color_hex FROM modulos m JOIN cursos c ON c.id=m.curso_id WHERE m.id=?');
$modulo->execute([$moduloId]);
$modulo = $modulo->fetch();
if (!$modulo) redirect(BASE_URL . '/admin/modulos.php');

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_paso') {
        $pasoId    = (int)$_POST['paso_id'];
        $contenido = trim($_POST['contenido'] ?? '{}');
        // Validate JSON
        $decoded = json_decode($contenido, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $pdo->prepare('UPDATE modulo_pasos SET contenido=? WHERE id=? AND modulo_id=?')
                ->execute([json_encode($decoded, JSON_UNESCAPED_UNICODE), $pasoId, $moduloId]);
            setFlash('success', 'Paso guardado correctamente.');
        } else {
            setFlash('error', 'JSON inválido: ' . json_last_error_msg());
        }
        redirect(BASE_URL . '/admin/modulo_pasos.php?modulo_id=' . $moduloId);
    }

    if ($action === 'create_paso') {
        $numero = (int)$_POST['numero_paso'];
        $tipo   = $_POST['tipo'] ?? 'historia';
        $tipos_validos = ['historia', 'actividad', 'quiz', 'entregable'];
        if (!in_array($tipo, $tipos_validos, true)) $tipo = 'historia';
        $defaults = [
            'historia'   => ['titulo' => '', 'texto' => '', 'imagen_url' => ''],
            'actividad'  => ['instruccion' => '', 'materiales' => []],
            'quiz'       => ['descripcion' => '', 'preguntas' => []],
            'entregable' => ['instruccion' => '', 'tipo_archivo' => 'imagen'],
        ];
        $contenido = json_encode($defaults[$tipo], JSON_UNESCAPED_UNICODE);
        try {
            $pdo->prepare('INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES (?,?,?,?)')
                ->execute([$moduloId, $numero, $tipo, $contenido]);
            setFlash('success', 'Paso creado.');
        } catch (\Throwable $e) {
            setFlash('error', 'Ya existe un paso con ese número.');
        }
        redirect(BASE_URL . '/admin/modulo_pasos.php?modulo_id=' . $moduloId);
    }
}

$pasos = $pdo->prepare('SELECT * FROM modulo_pasos WHERE modulo_id=? ORDER BY numero_paso');
$pasos->execute([$moduloId]);
$pasos = $pasos->fetchAll();

$color = htmlspecialchars($modulo['color_hex'] ?? '#4361ee', ENT_QUOTES);

$tiposIcon = [
    'historia'   => 'book-open',
    'actividad'  => 'zap',
    'quiz'       => 'help-circle',
    'entregable' => 'upload',
];
$tiposLabel = [
    'historia'   => 'Historia',
    'actividad'  => 'Actividad',
    'quiz'       => 'Quiz',
    'entregable' => 'Entregable',
];

$numerosExistentes = array_column($pasos, 'numero_paso');

$pageTitle = 'Pasos del módulo';
$activeNav = 'modulos';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="mb-20">
  <a href="<?= BASE_URL ?>/admin/modulos.php" class="btn btn-ghost btn-sm">
    <i data-lucide="arrow-left" style="width:14px;height:14px"></i>
    Volver a Módulos
  </a>
</div>

<!-- Module header -->
<div class="card mb-28" style="border-color:<?= $color ?>40;background:linear-gradient(135deg,<?= $color ?>0d,var(--bg-surface))">
  <div style="display:flex;align-items:center;gap:16px">
    <div style="width:56px;height:56px;border-radius:14px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i data-lucide="book-open" style="width:24px;height:24px;color:#fff"></i>
    </div>
    <div>
      <h1 class="page-title mb-4" style="color:<?= $color ?>"><?= sanitize($modulo['titulo']) ?></h1>
      <p class="page-subtitle"><?= sanitize($modulo['curso_nombre']) ?> · <?= count($pasos) ?>/4 pasos configurados</p>
    </div>
  </div>
</div>

<!-- Add paso form (only if < 4) -->
<?php if (count($pasos) < 4): ?>
<details style="background:var(--bg-surface);border:1px solid var(--bg-border);border-radius:14px;padding:0;margin-bottom:24px;overflow:hidden">
  <summary style="padding:14px 20px;cursor:pointer;font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:var(--text-primary);display:flex;align-items:center;gap:8px;list-style:none">
    <i data-lucide="plus-circle" style="width:16px;height:16px;color:var(--accent)"></i>
    Agregar paso
  </summary>
  <div style="padding:0 20px 20px;border-top:1px solid var(--bg-border)">
    <form method="POST" style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap;align-items:flex-end">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create_paso">
      <div class="form-group" style="flex:0 0 120px">
        <label class="form-label">Número de paso</label>
        <select name="numero_paso" class="form-control">
          <?php for ($n=1; $n<=4; $n++): if (!in_array($n, $numerosExistentes)): ?>
          <option value="<?= $n ?>"><?= $n ?></option>
          <?php endif; endfor; ?>
        </select>
      </div>
      <div class="form-group" style="flex:0 0 180px">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-control">
          <option value="historia">Historia</option>
          <option value="actividad">Actividad</option>
          <option value="quiz">Quiz</option>
          <option value="entregable">Entregable</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="plus" style="width:15px;height:15px"></i>
        Crear paso
      </button>
    </form>
  </div>
</details>
<?php endif; ?>

<!-- Pasos list -->
<?php if (empty($pasos)): ?>
<div class="empty-state">
  <div class="empty-icon"><i data-lucide="file-x" style="width:40px;height:40px;opacity:.4"></i></div>
  <h3>Sin pasos configurados</h3>
  <p>Agrega hasta 4 pasos para este módulo.</p>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:20px">
  <?php foreach ($pasos as $paso):
    $icon  = $tiposIcon[$paso['tipo']] ?? 'circle';
    $label = $tiposLabel[$paso['tipo']] ?? $paso['tipo'];
    $contenidoPretty = json_encode(json_decode($paso['contenido'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  ?>
  <div class="card">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
      <div style="width:40px;height:40px;border-radius:10px;background:<?= $color ?>22;color:<?= $color ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i data-lucide="<?= $icon ?>" style="width:18px;height:18px"></i>
      </div>
      <div>
        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:15px;color:var(--text-primary)">
          Paso <?= (int)$paso['numero_paso'] ?> — <?= $label ?>
        </div>
        <div style="font-size:12px;color:var(--text-muted)">Edita el JSON de contenido abajo</div>
      </div>
      <div style="margin-left:auto">
        <span style="background:<?= $color ?>18;color:<?= $color ?>;border:1px solid <?= $color ?>33;border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700"><?= $label ?></span>
      </div>
    </div>

    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="save_paso">
      <input type="hidden" name="paso_id" value="<?= (int)$paso['id'] ?>">
      <div class="form-group">
        <label class="form-label" style="display:flex;align-items:center;gap:6px">
          <i data-lucide="code-2" style="width:13px;height:13px"></i>
          Contenido JSON
        </label>
        <textarea name="contenido" rows="10"
                  class="form-control"
                  style="font-family:'Courier New',monospace;font-size:13px;resize:vertical;tab-size:2"
                  spellcheck="false"><?= htmlspecialchars($contenidoPretty, ENT_QUOTES) ?></textarea>
        <div style="font-size:11px;color:var(--text-muted);margin-top:6px">
          <?php if ($paso['tipo'] === 'historia'): ?>
          Campos: <code>titulo</code>, <code>texto</code> (HTML), <code>imagen_url</code>
          <?php elseif ($paso['tipo'] === 'actividad'): ?>
          Campos: <code>instruccion</code>, <code>materiales</code> (array de strings)
          <?php elseif ($paso['tipo'] === 'quiz'): ?>
          Campos: <code>descripcion</code>, <code>preguntas</code> (array con <code>pregunta</code>, <code>opciones</code>, <code>correcta</code>)
          <?php elseif ($paso['tipo'] === 'entregable'): ?>
          Campos: <code>instruccion</code>, <code>tipo_archivo</code> (imagen/pdf/link)
          <?php endif; ?>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;margin-top:12px">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save" style="width:15px;height:15px"></i>
          Guardar paso
        </button>
      </div>
    </form>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
