<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('docente');

$user = currentUser();
$pdo  = getDB();

$aulas = $pdo->prepare("
    SELECT a.*, col.nombre as colegio_nombre,
           (SELECT COUNT(*) FROM estudiante_aula ea WHERE ea.aula_id = a.id) as total_estudiantes,
           (SELECT COUNT(*) FROM progreso_estudiante pe
            JOIN estudiante_aula ea2 ON ea2.estudiante_id = pe.estudiante_id
            WHERE ea2.aula_id = a.id AND pe.completado = 1) as modulos_completados
    FROM aulas a
    JOIN colegios col ON col.id = a.colegio_id
    WHERE a.docente_id = ?
");
$aulas->execute([$user['id']]);
$aulas = $aulas->fetchAll();

$modulosRecientes = $pdo->prepare("
    SELECT m.titulo, c.nombre as curso_nombre, c.color_hex,
           COUNT(DISTINCT pe.estudiante_id) as completados,
           (SELECT COUNT(*) FROM estudiante_aula ea WHERE ea.aula_id = am.aula_id) as total,
           am.fecha_planificada
    FROM aula_modulos am
    JOIN modulos m ON m.id = am.modulo_id
    JOIN cursos c ON c.id = m.curso_id
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id = am.modulo_id AND pe.completado = 1
    WHERE am.aula_id IN (SELECT id FROM aulas WHERE docente_id = ?)
    GROUP BY am.aula_id, am.modulo_id, m.titulo, c.nombre, c.color_hex, am.fecha_planificada
    ORDER BY am.fecha_planificada DESC
    LIMIT 10
");
$modulosRecientes->execute([$user['id']]);
$modulosRecientes = $modulosRecientes->fetchAll();

$totalEstudiantes = array_sum(array_column($aulas, 'total_estudiantes'));
$totalCompletados = array_sum(array_column($aulas, 'modulos_completados'));

$pageTitle = 'Dashboard Docente';
$activeNav = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page header -->
<div class="page-header flex justify-between items-center">
  <div>
    <h1 class="page-title">¡Hola, <?= sanitize($user['nombre'] ?? 'Docente') ?>!</h1>
    <p class="page-subtitle">Resumen de tus aulas y progreso de estudiantes</p>
  </div>
  <a href="<?= BASE_URL ?>/docente/reportes.php" class="btn btn-primary">
    <i data-lucide="bar-chart-2" style="width:16px;height:16px"></i>
    Ver reportes
  </a>
</div>

<!-- Stats -->
<div class="stats-grid mb-32">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">
      <i data-lucide="layers" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--blue)"><?= count($aulas) ?></div>
    <div class="stat-label">Mis aulas</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
      <i data-lucide="users" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= $totalEstudiantes ?></div>
    <div class="stat-label">Estudiantes totales</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
      <i data-lucide="check-circle-2" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--purple)"><?= $totalCompletados ?></div>
    <div class="stat-label">Módulos completados</div>
  </div>
</div>

<!-- Chart row -->
<div class="card mb-24">
  <div class="card-header">
    <div>
      <h2 class="card-title">Progreso por curso</h2>
      <p class="card-subtitle">Estudiantes activos por área</p>
    </div>
  </div>
  <div class="chart-container" style="height:200px">
    <canvas id="chartDocente"></canvas>
  </div>
</div>

<!-- Mis aulas -->
<div class="card mb-24">
  <div class="card-header">
    <div>
      <h2 class="card-title">Mis aulas</h2>
      <p class="card-subtitle">Progreso global por aula</p>
    </div>
    <a href="<?= BASE_URL ?>/docente/estudiantes.php" class="btn btn-ghost btn-sm">
      Ver estudiantes
      <i data-lucide="arrow-right" style="width:14px;height:14px"></i>
    </a>
  </div>

  <?php if (empty($aulas)): ?>
  <div class="empty-state">
    <div class="empty-state-svg"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
    <p class="empty-state-title">Sin aulas asignadas</p>
    <p class="empty-state-desc">Contacta al director para que te asigne un aula.</p>
  </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
    <?php foreach ($aulas as $aula):
      $n     = (int)$aula['total_estudiantes'];
      $comp  = (int)$aula['modulos_completados'];
    ?>
    <div style="background:var(--bg-muted);border:1px solid var(--bg-border);border-radius:var(--radius);padding:20px">
      <div style="font-family:'Syne',sans-serif;font-size:17px;font-weight:800;color:var(--text-primary);margin-bottom:2px">
        <?= (int)$aula['grado'] ?>° &ldquo;<?= sanitize($aula['seccion']) ?>&rdquo;
      </div>
      <div style="font-size:12px;color:var(--text-muted);margin-bottom:18px"><?= sanitize($aula['colegio_nombre']) ?></div>
      <div style="display:flex;justify-content:space-between;margin-bottom:14px">
        <div>
          <div style="font-size:22px;font-weight:800;color:var(--blue)"><?= $n ?></div>
          <div style="font-size:11px;color:var(--text-muted)">estudiantes</div>
        </div>
        <div>
          <div style="font-size:22px;font-weight:800;color:var(--green)"><?= $comp ?></div>
          <div style="font-size:11px;color:var(--text-muted)">completados</div>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/docente/estudiantes.php?aula_id=<?= (int)$aula['id'] ?>"
         class="btn btn-ghost btn-sm btn-block" style="text-decoration:none;justify-content:center">
        Ver estudiantes
        <i data-lucide="arrow-right" style="width:13px;height:13px"></i>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Progreso de módulos -->
<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">Módulos recientes</h2>
      <p class="card-subtitle">Completación de los últimos 10 módulos planificados</p>
    </div>
    <a href="<?= BASE_URL ?>/docente/portafolios.php" class="btn btn-ghost btn-sm">
      Portafolios
      <i data-lucide="arrow-right" style="width:14px;height:14px"></i>
    </a>
  </div>

  <?php if (empty($modulosRecientes)): ?>
  <div class="empty-state">
    <div class="empty-state-svg"><svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
    <p class="empty-state-title">Sin módulos planificados</p>
    <p class="empty-state-desc">Los módulos aparecen aquí cuando el director los asigne a tus aulas.</p>
  </div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Módulo</th>
          <th>Curso</th>
          <th>Progreso</th>
          <th>Fecha planificada</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($modulosRecientes as $m):
          $pct = $m['total'] > 0 ? round($m['completados'] / $m['total'] * 100) : 0;
          $clr = $m['color_hex'];
        ?>
        <tr>
          <td style="font-weight:600"><?= sanitize($m['titulo']) ?></td>
          <td>
            <span style="background:<?= $clr ?>22;color:<?= $clr ?>;border:1px solid <?= $clr ?>44;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700">
              <?= sanitize($m['curso_nombre']) ?>
            </span>
          </td>
          <td style="min-width:160px">
            <div style="display:flex;align-items:center;gap:10px">
              <div class="progress-bar-wrap">
                <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>"></div>
              </div>
              <span style="font-size:12px;color:var(--text-secondary);white-space:nowrap;min-width:42px">
                <?= $m['completados'] ?>/<?= $m['total'] ?>
              </span>
            </div>
          </td>
          <td style="color:var(--text-muted);font-size:13px">
            <?= $m['fecha_planificada'] ? date('d/m/Y', strtotime($m['fecha_planificada'])) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.color = '#8898aa';
  try {
    const r = await fetch('<?= BASE_URL ?>/api/stats.php?type=students_by_course');
    const d = await r.json();
    new Chart(document.getElementById('chartDocente'), {
      type: 'doughnut',
      data: {
        labels: d.labels,
        datasets: [{
          data: d.values,
          backgroundColor: d.colors.map(c => c + 'dd'),
          borderColor: d.colors,
          borderWidth: 2,
          hoverOffset: 6,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { position: 'right', labels: { padding: 16, font: { size: 12 } } }
        },
        cutout: '60%',
      }
    });
  } catch(e) {}
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
