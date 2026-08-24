<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$uid = currentUserId();
$pdo = getDB();

// Session counts
$sesiones = [];
try {
    $stmt = $pdo->prepare('SELECT simulador, COUNT(*) as cnt, SUM(duracion_seg) as total_seg FROM simulador_sesiones WHERE usuario_id=? GROUP BY simulador');
    $stmt->execute([$uid]);
    foreach ($stmt->fetchAll() as $row) {
        $sesiones[$row['simulador']] = ['cnt' => (int)$row['cnt'], 'seg' => (int)$row['total_seg']];
    }
} catch (\PDOException $e) {}

$pageTitle = 'Simuladores';
$activeNav = 'stellarscribe';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title" style="display:flex;align-items:center;gap:10px"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg> Simuladores Interactivos</h1>
    <p class="page-subtitle">Explora el espacio con datos reales de la NASA</p>
  </div>
  <a href="<?= BASE_URL ?>/stellarscribe/portal.php" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:16px;height:16px"></i>
    StellarScribe
  </a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:32px">

  <?php
  $sims = [
    [
      'slug'  => 'sistema-solar',
      'href'  => BASE_URL . '/stellarscribe/simuladores/sistema-solar.php',
      'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fb923c" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>',
      'name'  => 'Sistema Solar',
      'desc'  => 'Explora las 8 órbitas planetarias con animación en tiempo real. Haz clic en cada planeta para que Aldrin te cuente sus secretos. Activa llamaradas solares y controla la velocidad orbital.',
      'tags'  => ['Astronomía','Física','Órbitas'],
      'clr'   => '#fb923c',
      'nivel' => 'Todos los niveles',
    ],
    [
      'slug'  => 'clima-espacial',
      'href'  => BASE_URL . '/stellarscribe/simuladores/clima-espacial.php',
      'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#4361ee" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9z"/></svg>',
      'name'  => 'Clima Espacial',
      'desc'  => 'Monitorea el índice Kp, viento solar y llamaradas en tiempo real con datos NASA. Simula tormentas geomagnéticas históricas y descubre cuándo una aurora podría ser visible desde Perú.',
      'tags'  => ['Datos NASA','CMEs','Auroras'],
      'clr'   => '#4361ee',
      'nivel' => 'Intermedio',
    ],
    [
      'slug'  => 'proximos',
      'href'  => '#',
      'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6a6 6 0 0 0 6 6"/><circle cx="18" cy="6" r="2" fill="#a855f7"/></svg>',
      'name'  => 'Más próximamente',
      'desc'  => 'Simulador de llamaradas solares por clase (X/M/C/B), formación de auroras boreales, viaje interplanetario y más. En desarrollo.',
      'tags'  => ['En desarrollo'],
      'clr'   => '#a855f7',
      'nivel' => 'Próximamente',
    ],
  ];
  foreach ($sims as $s):
    $uso  = $sesiones[$s['slug']] ?? null;
    $isLocked = $s['slug'] === 'proximos';
  ?>
  <div style="background:#02040d;border:1px solid <?= $isLocked ? '#1a2340' : $s['clr'] . '44' ?>;border-radius:20px;padding:24px;display:flex;flex-direction:column;gap:14px;<?= $isLocked ? 'opacity:.6' : 'transition:box-shadow .2s' ?>"
       <?= !$isLocked ? 'onmouseover="this.style.boxShadow=\'0 0 32px ' . $s['clr'] . '22\'" onmouseout="this.style.boxShadow=\'\'"' : '' ?>>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px">
      <div style="width:56px;height:56px;border-radius:14px;background:<?= $s['clr'] ?>22;display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0"><?= $s['icon'] ?></div>
      <?php if ($uso): ?>
      <div style="text-align:right">
        <div style="font-size:10px;color:#5c7ab0;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Tu uso</div>
        <div style="font-size:13px;font-weight:700;color:#3ecf8e"><?= $uso['cnt'] ?>× · <?= round($uso['seg']/60) ?>min</div>
      </div>
      <?php endif; ?>
    </div>
    <div>
      <div style="font-family:'Syne',sans-serif;font-size:19px;font-weight:800;color:#e8f0fe;margin-bottom:8px"><?= $s['name'] ?></div>
      <div style="font-size:13px;color:#8898aa;line-height:1.65"><?= $s['desc'] ?></div>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <?php foreach ($s['tags'] as $t): ?>
      <span style="background:<?= $s['clr'] ?>18;color:<?= $s['clr'] ?>;border-radius:99px;padding:2px 10px;font-size:11px;font-weight:600"><?= $t ?></span>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:8px;border-top:1px solid #0f1629">
      <span style="font-size:11px;color:#5c7ab0"><?= $s['nivel'] ?></span>
      <?php if (!$isLocked): ?>
      <a href="<?= $s['href'] ?>" class="btn btn-primary" style="font-size:13px;padding:8px 18px">
        Lanzar
        <i data-lucide="rocket" style="width:14px;height:14px"></i>
      </a>
      <?php else: ?>
      <span style="font-size:12px;color:#5c7ab0;display:flex;align-items:center;gap:4px"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> En desarrollo</span>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

</div>

<!-- Stats bar -->
<?php
$totalSim = array_sum(array_column($sesiones, 'cnt'));
$totalMin = round(array_sum(array_column($sesiones, 'seg')) / 60);
?>
<div style="background:#02040d;border:1px solid #1a2340;border-radius:14px;padding:16px 24px;display:flex;gap:32px;flex-wrap:wrap">
  <div><div style="font-size:11px;color:#5c7ab0;font-weight:600;text-transform:uppercase;letter-spacing:.05em">Sesiones totales</div><div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:#e8f0fe;margin-top:2px"><?= $totalSim ?></div></div>
  <div><div style="font-size:11px;color:#5c7ab0;font-weight:600;text-transform:uppercase;letter-spacing:.05em">Minutos explorando</div><div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:#3ecf8e;margin-top:2px"><?= $totalMin ?></div></div>
  <div style="margin-left:auto;align-self:center">
    <a href="<?= BASE_URL ?>/stellarscribe/portal.php" class="btn btn-ghost">Ver portal StellarScribe</a>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
