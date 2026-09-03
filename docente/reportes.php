<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('docente');

$user = currentUser();
$pdo  = getDB();

// Aulas for this teacher
$aulas = $pdo->prepare("
    SELECT a.*, col.nombre as colegio_nombre,
           (SELECT COUNT(*) FROM estudiante_aula ea WHERE ea.aula_id=a.id) as total_est
    FROM aulas a JOIN colegios col ON col.id=a.colegio_id
    WHERE a.docente_id=?
");
$aulas->execute([$user['id']]);
$aulas = $aulas->fetchAll();

// Totals for header stats
$totalEst   = array_sum(array_column($aulas, 'total_est'));
$totalAulas = count($aulas);

// Per-aula data: course progress + student breakdown
$progresoCurso = [];
$progresoEst   = [];
$totalCompAll  = 0;

foreach ($aulas as $aula) {
    $aid = $aula['id'];

    // Progress per course
    $stmt = $pdo->prepare("
        SELECT c.id as cid, c.nombre as curso_nombre, c.color_hex,
               COUNT(DISTINCT m.id) as total_modulos,
               COUNT(DISTINCT pe.estudiante_id) as completadores
        FROM cursos c
        CROSS JOIN estudiante_aula ea
        LEFT JOIN modulos m ON m.curso_id=c.id AND m.activo=1
        LEFT JOIN progreso_estudiante pe ON pe.modulo_id=m.id AND pe.estudiante_id=ea.estudiante_id AND pe.completado=1
        WHERE ea.aula_id=? AND c.activo=1
        GROUP BY c.id ORDER BY c.id
    ");
    $stmt->execute([$aid]);
    $progresoCurso[$aid] = $stmt->fetchAll();

    // Per-student completions
    $stmt2 = $pdo->prepare("
        SELECT u.id, u.nombre, u.apellido,
               COUNT(pe.id) as completados,
               MAX(pe.completado_en) as ultimo
        FROM estudiante_aula ea
        JOIN usuarios u ON u.id=ea.estudiante_id
        LEFT JOIN progreso_estudiante pe ON pe.estudiante_id=u.id AND pe.completado=1
        WHERE ea.aula_id=?
        GROUP BY u.id ORDER BY completados DESC, u.apellido
    ");
    $stmt2->execute([$aid]);
    $progresoEst[$aid] = $stmt2->fetchAll();

    $totalCompAll += array_sum(array_column($progresoEst[$aid], 'completados'));
}

// Max modules (for % column in student table)
$maxMod = (int)$pdo->query("SELECT COUNT(*) FROM modulos WHERE activo=1")->fetchColumn() ?: 1;

// Recent sessions with photos across all aulas
$aulaIds = array_column($aulas, 'id');
$sesionesConFoto = [];
if ($aulaIds) {
    $in = implode(',', array_map('intval', $aulaIds));
    $sesionesConFoto = $pdo->query("
        SELECT s.id, s.fecha_sesion, s.notas, s.fotos_actividad, s.asistentes,
               a.grado, a.seccion, m.titulo as modulo_titulo
        FROM sesiones s
        JOIN aulas a ON a.id=s.aula_id
        LEFT JOIN modulos m ON m.id=s.modulo_id
        WHERE s.aula_id IN ($in) AND s.fotos_actividad IS NOT NULL
        ORDER BY s.fecha_sesion DESC LIMIT 20
    ")->fetchAll();
}


$pageTitle = 'Reportes del Aula';
$activeNav = 'reportes';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">Reportes del aula</h1>
    <p class="page-subtitle">Progreso de mis estudiantes por curso y módulo</p>
  </div>
  <button onclick="window.print()" class="btn btn-ghost">
    <i data-lucide="printer" style="width:15px;height:15px"></i>
    Imprimir
  </button>
</div>

<!-- Summary stats -->
<div class="stats-grid-6" style="--cols:3;margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(67,97,238,.12);color:var(--accent)">
      <i data-lucide="layers" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--accent)"><?= $totalAulas ?></div>
    <div class="stat-label">Mis aulas</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">
      <i data-lucide="graduation-cap" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--blue)"><?= $totalEst ?></div>
    <div class="stat-label">Total estudiantes</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
      <i data-lucide="check-circle-2" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= $totalCompAll ?></div>
    <div class="stat-label">Total completaciones</div>
  </div>
</div>

<?php if (empty($aulas)): ?>
<div class="empty-state">
  <div class="empty-icon"><i data-lucide="layers" style="width:40px;height:40px;opacity:.4"></i></div>
  <h3>Sin aulas asignadas</h3>
  <p>Contacta al administrador para que te asigne aulas.</p>
</div>
<?php endif; ?>

<?php foreach ($aulas as $aula):
  $aid = $aula['id'];
  $cursos = $progresoCurso[$aid];
  $ests   = $progresoEst[$aid];
  $chartId = 'chart-' . $aid;
  $maxComp  = max(1, $aula['total_est']);
?>
<div class="card mb-20">
  <div class="card-header">
    <div>
      <h2 class="card-title"><?= $aula['grado'] ?> &ldquo;<?= sanitize($aula['seccion']) ?>&rdquo;</h2>
      <p class="card-subtitle"><?= sanitize($aula['colegio_nombre']) ?> · <?= $aula['total_est'] ?> estudiantes</p>
    </div>
    <span style="font-size:12px;padding:4px 12px;border-radius:20px;background:var(--accent-light);color:var(--accent);font-weight:600"><?= date('Y') ?></span>
  </div>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

    <!-- Course progress bars + chart -->
    <div>
      <p style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);font-weight:700;margin-bottom:12px">Progreso por curso</p>
      <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px">
        <?php foreach ($cursos as $cp):
          $pct = $aula['total_est'] > 0 ? round($cp['completadores'] / $aula['total_est'] * 100) : 0;
        ?>
        <div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
            <span style="font-size:13px;font-weight:600;color:<?= $cp['color_hex'] ?>"><?= sanitize($cp['curso_nombre']) ?></span>
            <span style="font-size:12px;color:var(--text-muted)"><?= $cp['completadores'] ?>/<?= $aula['total_est'] ?> · <?= $pct ?>%</span>
          </div>
          <div class="progress-track">
            <div class="progress-fill" data-pct="<?= $pct ?>" style="background:<?= $cp['color_hex'] ?>;width:0%;transition:width .8s ease"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <canvas id="<?= $chartId ?>" height="160"
              data-labels='<?= json_encode(array_column($cursos, 'curso_nombre')) ?>'
              data-values='<?= json_encode(array_map(fn($c) => $aula['total_est']>0?round($c['completadores']/$aula['total_est']*100):0, $cursos)) ?>'
              data-colors='<?= json_encode(array_column($cursos, 'color_hex')) ?>'></canvas>
    </div>

    <!-- Student breakdown table -->
    <div>
      <p style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);font-weight:700;margin-bottom:12px">Progreso individual</p>
      <div style="display:flex;flex-direction:column;gap:6px;max-height:340px;overflow-y:auto">
        <?php foreach ($ests as $i => $est):
          $pct2 = round($est['completados'] / $maxMod * 100);
          $col2 = $pct2 >= 80 ? 'var(--green)' : ($pct2 >= 40 ? 'var(--gold)' : 'var(--text-muted)');
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:10px;background:var(--bg-elevated)">
          <span style="width:22px;text-align:center;font-size:11px;color:var(--text-muted);font-weight:700;flex-shrink:0"><?= $i+1 ?></span>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= sanitize($est['apellido'].', '.$est['nombre']) ?>
            </div>
            <?php if ($est['ultimo']): ?>
            <div style="font-size:10px;color:var(--text-muted)">Último: <?= date('d/m', strtotime($est['ultimo'])) ?></div>
            <?php endif; ?>
          </div>
          <span style="font-size:13px;font-weight:700;color:<?= $col2 ?>;flex-shrink:0"><?= $est['completados'] ?></span>
        </div>
        <?php endforeach; ?>
        <?php if (empty($ests)): ?>
        <p style="color:var(--text-muted);font-size:13px;text-align:center;padding:16px 0">Sin estudiantes en esta aula</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof Chart === 'undefined') return;
  const dark = document.documentElement.getAttribute('data-theme') === 'dark';
  const grid = dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
  const tick = dark ? '#94a3b8' : '#64748b';

  document.querySelectorAll('canvas[id^="chart-"]').forEach(canvas => {
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');
    const colors = JSON.parse(canvas.dataset.colors || '[]');
    if (!labels.length) return;
    new Chart(canvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: '% completación',
          data: values,
          backgroundColor: colors.map(c => c + 'cc'),
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { color: tick, font: { size: 11 } } },
          y: { beginAtZero: true, max: 100, grid: { color: grid },
               ticks: { color: tick, font: { size: 11 }, callback: v => v + '%' } }
        }
      }
    });
  });
});
</script>

<?php if (!empty($sesionesConFoto)): ?>
<div class="card" style="margin-top:20px">
  <div class="card-header">
    <div>
      <h2 class="card-title">Galería de sesiones</h2>
      <p class="card-subtitle">Fotos de actividades registradas por practicantes</p>
    </div>
    <span style="font-size:12px;color:var(--text-muted)"><?= count($sesionesConFoto) ?> sesiones con foto</span>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px">
    <?php foreach ($sesionesConFoto as $ses):
      $fotos = json_decode($ses['fotos_actividad'] ?? '[]', true);
      if (empty($fotos)) continue;
      foreach ($fotos as $fotoUrl):
    ?>
    <div style="border-radius:12px;overflow:hidden;background:var(--bg-elevated);border:1px solid var(--bg-border)">
      <a href="<?= sanitize($fotoUrl) ?>" target="_blank" rel="noopener">
        <img src="<?= sanitize($fotoUrl) ?>" alt="Sesión"
             style="width:100%;height:140px;object-fit:cover;display:block;transition:transform .3s"
             onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''">
      </a>
      <div style="padding:8px 10px">
        <div style="font-size:12px;font-weight:600;color:var(--text-primary)"><?= sanitize($ses['modulo_titulo'] ?? '') ?></div>
        <div style="font-size:10px;color:var(--text-muted);margin-top:2px">
          <?= (int)$ses['grado'] ?>° "<?= sanitize($ses['seccion']) ?>" · <?= date('d/m/Y', strtotime($ses['fecha_sesion'])) ?>
          · <?= (int)$ses['asistentes'] ?> asistentes
        </div>
        <?php if (!empty($ses['notas'])): ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;font-style:italic;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
          <?= sanitize($ses['notas']) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
