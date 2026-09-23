<?php
// ============================================================
// StellarScribe — Un artículo de la biblioteca
//
// Estructura fija, la misma en los dieciocho: la pregunta, la
// respuesta en una frase, el diagrama, la explicación, un dato
// curioso y algo que hacer con lo que hay en casa.
//
// Que sea siempre igual no es pereza: el estudiante aprende dónde
// está cada cosa y deja de tener que averiguarlo en cada artículo.
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/diagramas.php';
requireLogin();

$usuarioId = currentUserId();
$pdo       = getDB();

$slug = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)($_GET['a'] ?? '')));
if ($slug === '') redirect(BASE_URL . '/stellarscribe/ciencia.php');

$stmt = $pdo->prepare(
    'SELECT a.*, t.slug AS tema_slug, t.nombre AS tema_nombre, t.color_hex, t.icono
       FROM ciencia_articulos a
       JOIN ciencia_temas t ON t.id = a.tema_id
      WHERE a.slug = ? AND a.activo = 1'
);
$stmt->execute([$slug]);
$art = $stmt->fetch();
if (!$art) redirect(BASE_URL . '/stellarscribe/ciencia.php');

$cuerpo    = json_decode((string)$art['cuerpo'], true) ?: [];
$actividad = $art['actividad'] ? (json_decode((string)$art['actividad'], true) ?: []) : [];
$color     = htmlspecialchars($art['color_hex'], ENT_QUOTES);
$diagrama  = diagramaCiencia($art['diagrama']);

// ¿Ya lo había leído?
$stmtL = $pdo->prepare('SELECT 1 FROM ciencia_leidos WHERE usuario_id = ? AND articulo_id = ?');
$stmtL->execute([$usuarioId, (int)$art['id']]);
$yaLeido = (bool)$stmtL->fetchColumn();

// Anterior y siguiente dentro del mismo tema, para poder seguir leyendo
// sin volver al índice.
$stmtNav = $pdo->prepare(
    'SELECT slug, pregunta, orden FROM ciencia_articulos
      WHERE tema_id = ? AND activo = 1 ORDER BY orden'
);
$stmtNav->execute([(int)$art['tema_id']]);
$hermanos = $stmtNav->fetchAll();

$anterior = $siguiente = null;
foreach ($hermanos as $i => $h) {
    if ($h['slug'] === $art['slug']) {
        $anterior  = $hermanos[$i - 1] ?? null;
        $siguiente = $hermanos[$i + 1] ?? null;
        break;
    }
}

$pageTitle = $art['pregunta'];
$activeNav = 'stellarscribe';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:18px">
  <a href="<?= BASE_URL ?>/stellarscribe/ciencia.php?tema=<?= urlencode($art['tema_slug']) ?>"
     style="display:inline-flex;align-items:center;gap:6px;color:<?= $color ?>;font-size:13px;font-weight:600;text-decoration:none">
    <i data-lucide="arrow-left" style="width:14px;height:14px"></i>
    <?= sanitize($art['tema_nombre']) ?>
  </a>
</div>

<article class="articulo">

  <h1><?= sanitize($art['pregunta']) ?></h1>

  <div class="meta">
    <span><i data-lucide="clock" style="width:12px;height:12px"></i> <?= (int)$art['minutos_lectura'] ?> min de lectura</span>
    <span id="estado-leido" class="leido <?= $yaLeido ? '' : 'oculto' ?>">
      <i data-lucide="check-circle-2" style="width:12px;height:12px"></i> Leído
    </span>
  </div>

  <!-- La respuesta va ANTES de la explicación: quien solo lee esto ya
       se lleva algo cierto. -->
  <p class="respuesta"><?= sanitize($art['respuesta_corta']) ?></p>

  <?php if ($diagrama): ?>
  <figure class="diagrama">
    <?= $diagrama ?>
  </figure>
  <?php endif; ?>

  <?php foreach ($cuerpo as $parrafo): ?>
  <p class="cuerpo"><?= sanitize((string)$parrafo) ?></p>
  <?php endforeach; ?>

  <?php if (!empty($art['dato_curioso'])): ?>
  <aside class="dato">
    <span class="rotulo">Dato curioso</span>
    <p><?= sanitize($art['dato_curioso']) ?></p>
  </aside>
  <?php endif; ?>

  <?php if (!empty($actividad['titulo'])): ?>
  <section class="actividad">
    <span class="rotulo">Hazlo tú</span>
    <h2><?= sanitize($actividad['titulo']) ?></h2>

    <?php if (!empty($actividad['materiales'])): ?>
    <p class="materiales">
      <strong>Necesitas:</strong>
      <?= sanitize(implode(' · ', array_map('strval', $actividad['materiales']))) ?>
    </p>
    <?php endif; ?>

    <ol>
      <?php foreach (($actividad['pasos'] ?? []) as $paso): ?>
      <li><?= sanitize((string)$paso) ?></li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php endif; ?>

  <nav class="nav-articulos">
    <?php if ($anterior): ?>
    <a href="?a=<?= urlencode($anterior['slug']) ?>" class="nav-art anterior">
      <span>Anterior</span>
      <strong><?= sanitize($anterior['pregunta']) ?></strong>
    </a>
    <?php else: ?><span></span><?php endif; ?>

    <?php if ($siguiente): ?>
    <a href="?a=<?= urlencode($siguiente['slug']) ?>" class="nav-art siguiente">
      <span>Siguiente</span>
      <strong><?= sanitize($siguiente['pregunta']) ?></strong>
    </a>
    <?php endif; ?>
  </nav>
</article>

<style>
.articulo { max-width: 680px; margin: 0 auto 40px; }

.articulo h1 {
  font-family: 'Syne', sans-serif;
  font-size: clamp(24px, 4.5vw, 34px);
  font-weight: 800;
  line-height: 1.18;
  color: var(--text-primary);
  margin-bottom: 12px;
}

.articulo .meta {
  display: flex; align-items: center; gap: 14px;
  font-size: 12px; color: var(--text-muted);
  margin-bottom: 24px;
}
.articulo .meta span { display: inline-flex; align-items: center; gap: 5px; }
.articulo .meta .leido { color: var(--green); font-weight: 600; }
.articulo .meta .oculto { display: none; }

/* La respuesta corta: lo primero y lo más visible. */
.articulo .respuesta {
  font-size: clamp(17px, 2.6vw, 20px);
  line-height: 1.55;
  font-weight: 500;
  color: var(--text-primary);
  border-left: 3px solid <?= $color ?>;
  padding-left: 18px;
  margin-bottom: 28px;
}

.articulo .diagrama {
  margin: 0 0 28px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--bg-border);
}

.articulo .cuerpo {
  font-size: 16px;
  line-height: 1.72;
  color: var(--text-primary);
  margin-bottom: 18px;
}

.rotulo {
  display: block;
  font-size: 10.5px; font-weight: 700;
  letter-spacing: .13em; text-transform: uppercase;
  margin-bottom: 8px;
}

.articulo .dato {
  background: var(--bg-elevated);
  border-radius: 12px;
  padding: 18px 20px;
  margin: 28px 0;
}
.articulo .dato .rotulo { color: var(--gold); }
.articulo .dato p { font-size: 14.5px; line-height: 1.6; color: var(--text-primary); }

.articulo .actividad {
  border: 1.5px solid <?= $color ?>44;
  border-radius: 12px;
  padding: 22px 24px;
  margin: 32px 0;
}
.articulo .actividad .rotulo { color: <?= $color ?>; }
.articulo .actividad h2 {
  font-family: 'Syne', sans-serif;
  font-size: 19px; font-weight: 800;
  color: var(--text-primary); margin-bottom: 12px;
}
.articulo .actividad .materiales {
  font-size: 13.5px; line-height: 1.6;
  color: var(--text-secondary); margin-bottom: 16px;
}
.articulo .actividad ol { margin: 0; padding-left: 20px; }
.articulo .actividad li {
  font-size: 14.5px; line-height: 1.62;
  color: var(--text-primary); margin-bottom: 10px;
}

.nav-articulos {
  display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
  margin-top: 36px; padding-top: 24px;
  border-top: 1px solid var(--bg-border);
}
.nav-art {
  display: block; padding: 14px 16px;
  border: 1px solid var(--bg-border); border-radius: 10px;
  text-decoration: none; transition: border-color .15s;
}
.nav-art:hover { border-color: <?= $color ?>; }
.nav-art span {
  display: block; font-size: 10.5px; font-weight: 700;
  letter-spacing: .1em; text-transform: uppercase;
  color: var(--text-muted); margin-bottom: 4px;
}
.nav-art strong {
  display: block; font-size: 13.5px; font-weight: 600;
  line-height: 1.35; color: var(--text-primary);
}
.nav-art.siguiente { text-align: right; }

@media (max-width: 40rem) {
  .nav-articulos { grid-template-columns: 1fr; }
  .nav-art.siguiente { text-align: left; }
}
</style>

<script>
// Se marca como leído a los 25 segundos, no al abrir. Abrir y salir no
// es leer, y si contara, el logro de lectura no significaría nada.
(function () {
  <?php if ($yaLeido): ?>return;<?php endif; ?>

  const marcar = () => {
    fetch('<?= BASE_URL ?>/api/ciencia_leido.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
      body: JSON.stringify({ articulo_id: <?= (int)$art['id'] ?>, csrf_token: window.CSRF_TOKEN || '' }),
    }).then(r => r.json()).then(d => {
      if (!d.ok) return;
      const el = document.getElementById('estado-leido');
      if (el) el.classList.remove('oculto');
      if (d.logro) {
        // Un logro nuevo merece que te enteres en el momento.
        const aviso = document.createElement('div');
        aviso.className = 'aviso-logro';
        aviso.textContent = '¡Logro desbloqueado: ' + d.logro + '!';
        document.body.appendChild(aviso);
        setTimeout(() => aviso.remove(), 6000);
      }
    }).catch(() => { /* sin conexión: se marcará la próxima vez */ });
  };

  let temporizador = setTimeout(marcar, 25000);

  // Si cambia de pestaña, el reloj se pausa: no cuenta como lectura
  // tener el artículo abierto de fondo.
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      clearTimeout(temporizador);
    } else {
      temporizador = setTimeout(marcar, 25000);
    }
  });
})();
</script>

<style>
.aviso-logro {
  position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%);
  background: var(--gold); color: #1a1300;
  padding: 11px 20px; border-radius: 99px;
  font-size: 13.5px; font-weight: 700;
  box-shadow: 0 8px 24px rgba(0,0,0,.25);
  z-index: 80;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
