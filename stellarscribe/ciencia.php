<?php
// ============================================================
// StellarScribe — Biblioteca de ciencia
//
// Artículos cortos que responden UNA pregunta, al estilo del Space
// Place de la NASA. No hay orden obligatorio ni nada que desbloquear:
// se entra por curiosidad, que es como se lee la ciencia cuando nadie
// te obliga.
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$usuarioId = currentUserId();
$pdo       = getDB();

// Filtro por tema. Vacío = todos.
$temaSlug = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)($_GET['tema'] ?? '')));

$temas = $pdo->query(
    'SELECT * FROM ciencia_temas ORDER BY orden'
)->fetchAll();

// Los artículos y, de paso, si este usuario ya los leyó. Va en una sola
// consulta: son cinco temas y dieciocho artículos, pedirlos por tema
// serían seis viajes a la base para pintar una página.
$sql = 'SELECT a.*, t.slug AS tema_slug, t.nombre AS tema_nombre,
               t.color_hex, t.icono,
               l.leido_en
          FROM ciencia_articulos a
          JOIN ciencia_temas t ON t.id = a.tema_id
     LEFT JOIN ciencia_leidos l ON l.articulo_id = a.id AND l.usuario_id = ?
         WHERE a.activo = 1';
$params = [$usuarioId];

if ($temaSlug !== '') {
    $sql .= ' AND t.slug = ?';
    $params[] = $temaSlug;
}
$sql .= ' ORDER BY t.orden, a.orden';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articulos = $stmt->fetchAll();

// Agrupados por tema para pintarlos en secciones.
$porTema = [];
foreach ($articulos as $a) {
    $porTema[$a['tema_slug']]['tema'] = [
        'nombre' => $a['tema_nombre'],
        'color'  => $a['color_hex'],
        'icono'  => $a['icono'],
    ];
    $porTema[$a['tema_slug']]['items'][] = $a;
}

$totalBiblioteca = (int)$pdo->query('SELECT COUNT(*) FROM ciencia_articulos WHERE activo = 1')->fetchColumn();
$stmtLeidos = $pdo->prepare('SELECT COUNT(*) FROM ciencia_leidos WHERE usuario_id = ?');
$stmtLeidos->execute([$usuarioId]);
$totalLeidos = (int)$stmtLeidos->fetchColumn();

$pageTitle = 'Biblioteca de ciencia';
$activeNav = 'stellarscribe';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">Biblioteca de ciencia</h1>
    <p class="page-subtitle">
      Preguntas cortas con respuesta clara. Entra por la que te dé curiosidad.
    </p>
  </div>
  <a href="<?= BASE_URL ?>/stellarscribe/portal.php" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:15px;height:15px"></i>
    Volver a StellarScribe
  </a>
</div>

<!-- Progreso de lectura -->
<div class="card" style="margin-bottom:22px;padding:18px 22px">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap">
    <div>
      <div style="font-size:13px;color:var(--text-secondary)">Has leído</div>
      <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:var(--text-primary)">
        <?= $totalLeidos ?> <span style="color:var(--text-muted);font-weight:400">de <?= $totalBiblioteca ?></span>
      </div>
    </div>
    <div style="flex:1;min-width:180px;max-width:420px">
      <div style="height:8px;background:var(--bg-elevated);border-radius:99px;overflow:hidden">
        <div style="height:100%;width:<?= $totalBiblioteca > 0 ? round($totalLeidos / $totalBiblioteca * 100) : 0 ?>%;background:var(--accent);border-radius:99px"></div>
      </div>
    </div>
  </div>
</div>

<!-- Filtros por tema -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px">
  <a href="?" class="chip-tema <?= $temaSlug === '' ? 'activo' : '' ?>">Todos</a>
  <?php foreach ($temas as $t): ?>
  <a href="?tema=<?= urlencode($t['slug']) ?>"
     class="chip-tema <?= $temaSlug === $t['slug'] ? 'activo' : '' ?>"
     style="--chip:<?= htmlspecialchars($t['color_hex'], ENT_QUOTES) ?>">
    <?= iconoCurso($t['icono'], 14) ?> <?= sanitize($t['nombre']) ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (empty($porTema)): ?>
  <div class="empty-state">
    <div class="empty-icon"><i data-lucide="telescope" style="width:48px;height:48px;opacity:.4"></i></div>
    <h3>Todavía no hay artículos en este tema</h3>
    <p>Prueba con otro tema de la lista.</p>
  </div>
<?php endif; ?>

<?php foreach ($porTema as $slug => $grupo):
  $color = htmlspecialchars($grupo['tema']['color'], ENT_QUOTES);
?>
<section style="margin-bottom:34px">
  <div style="display:flex;align-items:center;gap:10px;padding-bottom:12px;margin-bottom:16px;border-bottom:2px solid <?= $color ?>33">
    <span style="color:<?= $color ?>"><?= iconoCurso($grupo['tema']['icono'], 22) ?></span>
    <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:<?= $color ?>">
      <?= sanitize($grupo['tema']['nombre']) ?>
    </h2>
    <span class="badge badge-muted" style="margin-left:auto"><?= count($grupo['items']) ?></span>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px">
    <?php foreach ($grupo['items'] as $a):
      $leido = !empty($a['leido_en']);
    ?>
    <a href="<?= BASE_URL ?>/stellarscribe/articulo.php?a=<?= urlencode($a['slug']) ?>"
       class="tarjeta-art" style="--acento:<?= $color ?>">
      <?php if ($leido): ?>
      <span class="marca-leido" title="Ya lo leíste">
        <i data-lucide="check" style="width:12px;height:12px"></i>
      </span>
      <?php endif; ?>

      <h3><?= sanitize($a['pregunta']) ?></h3>
      <p><?= sanitize(mb_strimwidth($a['respuesta_corta'], 0, 120, '…')) ?></p>

      <div class="pie-art">
        <span><i data-lucide="clock" style="width:11px;height:11px"></i> <?= (int)$a['minutos_lectura'] ?> min</span>
        <?php if ($a['nivel'] === 'secundaria'): ?>
        <span class="badge badge-muted" style="font-size:10px">Secundaria</span>
        <?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<style>
.chip-tema {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 14px; border-radius: 99px;
  font-size: 12.5px; font-weight: 600; text-decoration: none;
  border: 1.5px solid var(--bg-border);
  background: var(--bg-elevated); color: var(--text-secondary);
  transition: border-color .15s, color .15s;
}
.chip-tema:hover  { border-color: var(--chip, var(--accent)); color: var(--chip, var(--accent)); }
.chip-tema.activo { background: var(--chip, var(--accent)); border-color: var(--chip, var(--accent)); color: #fff; }

.tarjeta-art {
  position: relative;
  display: flex; flex-direction: column;
  background: var(--bg-surface);
  border: 1px solid var(--bg-border);
  border-left: 3px solid var(--acento);
  border-radius: 12px;
  padding: 16px 18px 14px;
  text-decoration: none;
  transition: transform .18s, border-color .18s, box-shadow .18s;
}
.tarjeta-art:hover {
  transform: translateY(-2px);
  border-color: var(--acento);
  box-shadow: 0 6px 18px rgba(0,0,0,.09);
}
.tarjeta-art h3 {
  font-size: 15px; font-weight: 600; line-height: 1.35;
  color: var(--text-primary); margin-bottom: 7px; padding-right: 22px;
}
.tarjeta-art p {
  font-size: 12.5px; line-height: 1.5; color: var(--text-secondary);
  margin-bottom: 12px; flex: 1;
}
.pie-art {
  display: flex; align-items: center; gap: 10px;
  font-size: 11px; color: var(--text-muted);
}
.pie-art span { display: inline-flex; align-items: center; gap: 4px; }

.marca-leido {
  position: absolute; top: 14px; right: 14px;
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--green); color: #fff;
  display: flex; align-items: center; justify-content: center;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
