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
        // Las claves tienen que ser las que lee estudiante/modulo.php.
        // Antes no lo eran —'texto' en vez de 'narrativa', 'instruccion'
        // en vez de 'instrucciones'—, así que un paso creado desde aquí
        // llegaba al estudiante vacío o a medias, y sin ningún error que
        // lo delatara.
        //
        // Los valores de ejemplo enseñan la forma esperada; se sustituyen
        // al redactar el paso.
        $defaults = [
            'historia'   => [
                'narrativa'            => '',
                'pregunta_disparadora' => '',
                'conceptos_clave'      => [],
                'dato_curioso'         => '',
            ],
            'actividad'  => [
                'materiales'    => [],
                'instrucciones' => [],
                'minutos'       => 20,
            ],
            // Las preguntas del quiz viven en la tabla quiz_preguntas,
            // no en este JSON.
            'quiz'       => [
                'info' => 'Ver tabla quiz_preguntas para las preguntas de este paso',
            ],
            'entregable' => [
                'consigna'      => '',
                'formatos'      => ['ficha'],
                'instrucciones' => 'Sube una foto de tu trabajo terminado. Máx 5 MB.',
            ],
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
<?php $puedeReordenar = count($pasos) > 1; ?>
<?php if ($puedeReordenar): ?>
<div class="reordenar-ayuda" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;font-size:12.5px;color:var(--text-muted)">
  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
       stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M8 18 12 22 16 18"/><path d="M8 6 12 2 16 6"/><path d="M12 2v20"/></svg>
  <span>Arrastra la cabecera de un paso para reordenarlo, o usa las flechas <strong>↑ ↓</strong> (funcionan con teclado y en tablet). El orden se guarda solo.</span>
</div>
<?php endif; ?>
<div id="pasos-lista"
     class="pasos-lista"
     data-modulo-id="<?= (int)$moduloId ?>"
     data-endpoint="<?= BASE_URL ?>/api/paso_orden.php"
     data-csrf="<?= htmlspecialchars(csrfToken(), ENT_QUOTES) ?>"
     style="display:flex;flex-direction:column;gap:20px">
  <?php foreach ($pasos as $i => $paso):
    $icon  = $tiposIcon[$paso['tipo']] ?? 'circle';
    $label = $tiposLabel[$paso['tipo']] ?? $paso['tipo'];
    $contenidoPretty = json_encode(json_decode($paso['contenido'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  ?>
  <div class="card paso-card" data-paso-id="<?= (int)$paso['id'] ?>">
    <div class="paso-cabecera" <?= $puedeReordenar ? 'draggable="true" data-arrastre' : '' ?>
         style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
      <?php if ($puedeReordenar): ?>
      <?php /* SVG en línea: los iconos de Lucide vienen de un CDN y estas
               páginas deben funcionar sin internet (colegios sin conexión). */ ?>
      <span class="paso-asa" aria-hidden="true" data-tip="Arrastra para reordenar">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
          <circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/>
          <circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/>
          <circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/>
        </svg>
      </span>
      <?php endif; ?>
      <div style="width:40px;height:40px;border-radius:10px;background:<?= $color ?>22;color:<?= $color ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i data-lucide="<?= $icon ?>" style="width:18px;height:18px"></i>
      </div>
      <div>
        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:15px;color:var(--text-primary)">
          Paso <span class="paso-num"><?= (int)$paso['numero_paso'] ?></span> — <?= $label ?>
        </div>
        <div style="font-size:12px;color:var(--text-muted)">Edita el JSON de contenido abajo</div>
      </div>
      <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
        <?php if ($puedeReordenar): ?>
        <div class="paso-mover" role="group" aria-label="Cambiar el orden de este paso">
          <button type="button" class="paso-btn-mover" data-mover="-1"
                  aria-label="Subir el paso <?= sanitize($label) ?>" data-tip="Subir"
                  <?= $i === 0 ? 'disabled' : '' ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
          </button>
          <button type="button" class="paso-btn-mover" data-mover="1"
                  aria-label="Bajar el paso <?= sanitize($label) ?>" data-tip="Bajar"
                  <?= $i === count($pasos) - 1 ? 'disabled' : '' ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
          </button>
        </div>
        <?php endif; ?>
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

<?php
// ── Reordenar pasos: arrastrar y soltar + flechas (teclado/tablet) ──
// La configuración viaja en los data-* del contenedor, así que este
// bloque no necesita interpolar PHP (nowdoc).
$inlineJs = <<<'JS'
(function () {
  const lista = document.getElementById('pasos-lista');
  if (!lista) return;

  const endpoint = lista.dataset.endpoint;
  const csrf     = lista.dataset.csrf;
  const moduloId = parseInt(lista.dataset.moduloId, 10);

  const avisar   = (msg, tipo) => {
    if (typeof showToast === 'function') showToast(msg, tipo);
  };
  const tarjetas = () => Array.from(lista.querySelectorAll('.paso-card'));
  const idsAhora = () => tarjetas().map(t => parseInt(t.dataset.pasoId, 10));

  // Renumera los títulos (1..N) y activa/desactiva las flechas.
  function renumerar() {
    const items = tarjetas();
    items.forEach((t, i) => {
      const num = t.querySelector('.paso-num');
      if (num) num.textContent = String(i + 1);
      const arriba = t.querySelector('[data-mover="-1"]');
      const abajo  = t.querySelector('[data-mover="1"]');
      if (arriba) arriba.disabled = (i === 0);
      if (abajo)  abajo.disabled  = (i === items.length - 1);
    });
  }

  // Vuelve a un orden anterior (por ids) cuando el guardado falla.
  function restaurar(orden) {
    if (!orden) return;
    orden.forEach(id => {
      const t = lista.querySelector('.paso-card[data-paso-id="' + id + '"]');
      if (t) lista.appendChild(t);
    });
    renumerar();
  }

  function destacar(tarjeta) {
    if (!tarjeta) return;
    tarjeta.classList.remove('paso-movido');
    void tarjeta.offsetWidth;               // reinicia la animación
    tarjeta.classList.add('paso-movido');
    setTimeout(() => tarjeta.classList.remove('paso-movido'), 800);
  }

  let guardando = false;

  async function guardarOrden(ordenPrevio) {
    if (guardando) return;
    guardando = true;
    lista.classList.add('orden-guardando');
    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ modulo_id: moduloId, orden: idsAhora() })
      });
      let data = null;
      try { data = await res.json(); } catch (e) { /* respuesta no JSON */ }
      if (!res.ok || !data || !data.ok) {
        throw new Error((data && data.error) || ('Error ' + res.status));
      }
      avisar('Orden de los pasos actualizado.', 'success');
    } catch (err) {
      restaurar(ordenPrevio);
      avisar('No se pudo guardar el orden: ' + err.message, 'error');
    } finally {
      guardando = false;
      lista.classList.remove('orden-guardando');
    }
  }

  // ── Flechas subir / bajar (accesible con teclado y en pantallas táctiles) ──
  lista.addEventListener('click', e => {
    const btn = e.target.closest('[data-mover]');
    if (!btn || btn.disabled || guardando) return;

    const tarjeta = btn.closest('.paso-card');
    const dir     = parseInt(btn.dataset.mover, 10);
    const items   = tarjetas();
    const desde   = items.indexOf(tarjeta);
    const hasta   = desde + dir;
    if (desde < 0 || hasta < 0 || hasta >= items.length) return;

    const previo = idsAhora();
    if (dir < 0) lista.insertBefore(tarjeta, items[hasta]);
    else         lista.insertBefore(items[hasta], tarjeta);
    renumerar();
    destacar(tarjeta);

    // Mantiene el foco visible tras mover la tarjeta.
    let foco = tarjeta.querySelector('[data-mover="' + dir + '"]');
    if (!foco || foco.disabled) foco = tarjeta.querySelector('[data-mover="' + (-dir) + '"]');
    if (foco) foco.focus();

    guardarOrden(previo);
  });

  // ── Arrastrar y soltar nativo (HTML5) ──
  let arrastrada = null;
  let ordenPrevioArrastre = null;

  lista.addEventListener('dragstart', e => {
    const origen = e.target.closest('[data-arrastre]');
    if (!origen || guardando) return;
    arrastrada = origen.closest('.paso-card');
    if (!arrastrada) return;

    ordenPrevioArrastre = idsAhora();
    e.dataTransfer.effectAllowed = 'move';
    // Algunos navegadores exigen datos para iniciar el arrastre.
    try { e.dataTransfer.setData('text/plain', arrastrada.dataset.pasoId); } catch (err) {}
    if (e.dataTransfer.setDragImage) {
      const r = arrastrada.getBoundingClientRect();
      e.dataTransfer.setDragImage(arrastrada, e.clientX - r.left, e.clientY - r.top);
    }
    // En el mismo tick el navegador aún no capturó la imagen de arrastre.
    requestAnimationFrame(() => { if (arrastrada) arrastrada.classList.add('arrastrando'); });
    lista.classList.add('reordenando');
  });

  lista.addEventListener('dragover', e => {
    if (!arrastrada) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';

    // Inserta la tarjeta antes de la primera cuya mitad superior quede
    // por debajo del cursor; si no hay ninguna, va al final.
    const referencia = tarjetas()
      .filter(t => t !== arrastrada)
      .find(t => {
        const r = t.getBoundingClientRect();
        return e.clientY < r.top + r.height / 2;
      });

    if (referencia) {
      if (referencia.previousElementSibling !== arrastrada) {
        lista.insertBefore(arrastrada, referencia);
        renumerar();
      }
    } else if (lista.lastElementChild !== arrastrada) {
      lista.appendChild(arrastrada);
      renumerar();
    }
  });

  lista.addEventListener('drop', e => {
    if (!arrastrada) return;
    e.preventDefault();   // evita que el navegador abra el texto soltado
  });

  lista.addEventListener('dragend', e => {
    if (!arrastrada) return;
    const tarjeta = arrastrada;
    const previo  = ordenPrevioArrastre;
    arrastrada = null;
    ordenPrevioArrastre = null;
    tarjeta.classList.remove('arrastrando');
    lista.classList.remove('reordenando');

    // Escape o soltar fuera de la lista: se descarta la previsualización.
    const cancelado = e.dataTransfer && e.dataTransfer.dropEffect === 'none';
    if (cancelado) { restaurar(previo); return; }

    renumerar();
    if (!previo || previo.join(',') === idsAhora().join(',')) return;
    destacar(tarjeta);
    guardarOrden(previo);
  });

  renumerar();
})();
JS;
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
