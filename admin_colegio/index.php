<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin_colegio');

$user   = currentUser();
$userId = currentUserId();
$pdo    = getDB();

// ── Resolve colegio ───────────────────────────────────────────
// El vínculo director→colegio vive en usuarios.colegio_id. La tabla
// colegios nunca tuvo director_id: su campo 'director' es el nombre en
// texto, no una clave foránea. La consulta que lo buscaba reventaba la
// página entera antes de llegar a la vía correcta, que ya estaba
// escrita justo debajo como respaldo.
$colegio = null;

if (!$colegio) {
    $stmtFb = $pdo->prepare('SELECT colegio_id FROM usuarios WHERE id = ? LIMIT 1');
    $stmtFb->execute([$userId]);
    $colegioId = (int)($stmtFb->fetchColumn() ?: 0);
    if ($colegioId) {
        $stmtC2 = $pdo->prepare('SELECT * FROM colegios WHERE id = ? LIMIT 1');
        $stmtC2->execute([$colegioId]);
        $colegio = $stmtC2->fetch();
    }
}

$colegioId     = $colegio ? (int)$colegio['id']   : 0;
$colegioNombre = $colegio ? sanitize($colegio['nombre']) : 'Mi Colegio';

// ── Stats ─────────────────────────────────────────────────────
$stmtEst = $pdo->prepare('SELECT COUNT(DISTINCT ea.estudiante_id) FROM estudiante_aula ea JOIN aulas a ON a.id=ea.aula_id WHERE a.colegio_id=?');
$stmtEst->execute([$colegioId]);
$totalEstudiantes = (int)$stmtEst->fetchColumn();

$stmtModSemana = $pdo->prepare('SELECT COUNT(*) FROM progreso_estudiante pe JOIN estudiante_aula ea ON ea.estudiante_id=pe.estudiante_id JOIN aulas a ON a.id=ea.aula_id WHERE a.colegio_id=? AND pe.completado=1 AND pe.completado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$stmtModSemana->execute([$colegioId]);
$modulosSemana = (int)$stmtModSemana->fetchColumn();

$stmtModTotal = $pdo->prepare('SELECT COUNT(*) FROM progreso_estudiante pe JOIN estudiante_aula ea ON ea.estudiante_id=pe.estudiante_id JOIN aulas a ON a.id=ea.aula_id WHERE a.colegio_id=? AND pe.completado=1');
$stmtModTotal->execute([$colegioId]);
$modulosTotal = (int)$stmtModTotal->fetchColumn();

$stmtPract = $pdo->prepare('SELECT COUNT(DISTINCT pa.practicante_id) FROM practicante_aula pa JOIN aulas a ON a.id=pa.aula_id WHERE a.colegio_id=?');
$stmtPract->execute([$colegioId]);
$totalPracticantes = (int)$stmtPract->fetchColumn();

$stmtAulas = $pdo->prepare('SELECT COUNT(*) FROM aulas WHERE colegio_id=?');
$stmtAulas->execute([$colegioId]);
$totalAulas = (int)$stmtAulas->fetchColumn();

// ── Aula cards data ───────────────────────────────────────────
$stmtAulaList = $pdo->prepare("
    SELECT a.id, a.grado, a.seccion,
           CONCAT(ud.nombre,' ',ud.apellido) AS docente_nombre,
           GROUP_CONCAT(DISTINCT CONCAT(up.nombre,' ',up.apellido) SEPARATOR ', ') AS practicante_nombres,
           (SELECT COUNT(*) FROM estudiante_aula ea2 WHERE ea2.aula_id=a.id) AS total_est,
           (SELECT COUNT(DISTINCT pe.estudiante_id)
            FROM progreso_estudiante pe
            JOIN estudiante_aula ea3 ON ea3.estudiante_id=pe.estudiante_id
            WHERE ea3.aula_id=a.id AND pe.completado=1) AS est_con_modulo
    FROM aulas a
    LEFT JOIN usuarios ud ON ud.id=a.docente_id
    LEFT JOIN practicante_aula pa ON pa.aula_id=a.id
    LEFT JOIN usuarios up ON up.id=pa.practicante_id
    WHERE a.colegio_id=?
    GROUP BY a.id, a.grado, a.seccion, ud.nombre, ud.apellido
    ORDER BY a.grado, a.seccion
");
$stmtAulaList->execute([$colegioId]);
$aulas = $stmtAulaList->fetchAll();

$pageTitle = 'Dashboard Institucional';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title"><?= $colegioNombre ?></h1>
    <p class="page-subtitle">Panel institucional · <?= date('d/m/Y') ?></p>
  </div>
  <a href="<?= BASE_URL ?>/admin_colegio/reporte_dre.php" class="btn btn-primary">
    <i data-lucide="download" style="width:16px;height:16px"></i>
    Reporte DRE
  </a>
</div>

<!-- Stats -->
<div class="stats-grid mb-32">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">
      <i data-lucide="users" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--blue)"><?= $totalEstudiantes ?></div>
    <div class="stat-label">Estudiantes matriculados</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
      <i data-lucide="trending-up" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= $modulosSemana ?></div>
    <div class="stat-label">Completados esta semana</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">
      <i data-lucide="check-circle-2" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--gold)"><?= $modulosTotal ?></div>
    <div class="stat-label">Total módulos completados</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
      <i data-lucide="layers" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--purple)"><?= $totalAulas ?></div>
    <div class="stat-label">Aulas activas</div>
  </div>
</div>

<!-- Chart + aulas side by side -->
<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;margin-bottom:24px;align-items:start">

  <!-- Doughnut chart -->
  <div class="card" style="text-align:center">
    <div class="card-header" style="justify-content:center">
      <div>
        <h2 class="card-title">Progreso global</h2>
        <p class="card-subtitle">Estudiantes con módulo completado</p>
      </div>
    </div>
    <div style="position:relative;height:180px;margin-bottom:12px">
      <canvas id="chartProgreso"></canvas>
    </div>
    <div style="font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:var(--accent)" id="pctLabel">
      <?php
        $pctGlobal = $totalEstudiantes > 0
          ? round(($pdo->prepare("SELECT COUNT(DISTINCT pe.estudiante_id) FROM progreso_estudiante pe JOIN estudiante_aula ea ON ea.estudiante_id=pe.estudiante_id JOIN aulas a ON a.id=ea.aula_id WHERE a.colegio_id=? AND pe.completado=1") && true ? 0 : 0) / $totalEstudiantes * 100)
          : 0;
        // Re-query cleanly
        $stmtConMod = $pdo->prepare('SELECT COUNT(DISTINCT pe.estudiante_id) FROM progreso_estudiante pe JOIN estudiante_aula ea ON ea.estudiante_id=pe.estudiante_id JOIN aulas a ON a.id=ea.aula_id WHERE a.colegio_id=? AND pe.completado=1');
        $stmtConMod->execute([$colegioId]);
        $estConMod = (int)$stmtConMod->fetchColumn();
        $pctGlobal = $totalEstudiantes > 0 ? round($estConMod / $totalEstudiantes * 100) : 0;
        echo $pctGlobal . '%';
      ?>
    </div>
    <p style="font-size:12px;color:var(--text-muted);margin-top:4px"><?= $estConMod ?> de <?= $totalEstudiantes ?> estudiantes</p>
    <div style="display:flex;justify-content:center;gap:16px;margin-top:12px;font-size:12px">
      <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:50%;background:var(--accent);display:inline-block"></span>Con módulo</span>
      <span style="display:flex;align-items:center;gap:5px"><span style="width:10px;height:10px;border-radius:50%;background:var(--bg-border);display:inline-block"></span>Sin módulo</span>
    </div>
  </div>

  <!-- Aulas cards -->
  <div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <h2 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--text-primary)">Aulas del colegio</h2>
      <a href="<?= BASE_URL ?>/admin_colegio/aulas.php" class="btn btn-ghost btn-sm">
        <i data-lucide="settings" style="width:14px;height:14px"></i>
        Gestionar
      </a>
    </div>

    <?php if (empty($aulas)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i data-lucide="inbox" style="width:40px;height:40px;opacity:.4"></i></div>
      <h3>Sin aulas registradas</h3>
      <p>Crea la primera aula para comenzar.</p>
      <a href="<?= BASE_URL ?>/admin_colegio/aulas.php" class="btn btn-primary mt-16">
        <i data-lucide="plus" style="width:16px;height:16px"></i>
        Nueva aula
      </a>
    </div>
    <?php else: ?>
    <div class="aulas-grid-3">
      <?php foreach ($aulas as $aula):
        $total  = (int)$aula['total_est'];
        $conMod = (int)$aula['est_con_modulo'];
        $pct    = $total > 0 ? round($conMod / $total * 100) : 0;
        $clr    = $pct >= 80 ? 'var(--green)' : ($pct >= 40 ? 'var(--gold)' : 'var(--blue)');
      ?>
      <div class="aula-card">
        <div class="aula-card-header">
          <div>
            <div class="aula-card-title"><?= (int)$aula['grado'] ?>° &ldquo;<?= sanitize($aula['seccion']) ?>&rdquo;</div>
            <div class="aula-card-sub">
              <?php if ($aula['docente_nombre']): ?>
                <i data-lucide="user" style="width:11px;height:11px"></i> <?= sanitize($aula['docente_nombre']) ?>
              <?php else: ?>
                <span style="color:var(--danger)">Sin docente</span>
              <?php endif; ?>
            </div>
          </div>
          <span style="font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:<?= $clr ?>"><?= $pct ?>%</span>
        </div>
        <div class="metric-pair" style="margin-bottom:14px">
          <div class="metric-item">
            <div class="metric-val" style="color:var(--blue)"><?= $total ?></div>
            <div class="metric-lbl">estudiantes</div>
          </div>
          <div class="metric-item">
            <div class="metric-val" style="color:var(--green)"><?= $conMod ?></div>
            <div class="metric-lbl">con módulo</div>
          </div>
          <div class="metric-item">
            <div class="metric-val" style="color:var(--purple)"><?= $aula['practicante_nombres'] ? count(explode(',', $aula['practicante_nombres'])) : 0 ?></div>
            <div class="metric-lbl">practicantes</div>
          </div>
        </div>
        <div class="progress-track" style="margin-bottom:10px">
          <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>"></div>
        </div>
        <div style="display:flex;gap:6px">
          <a href="<?= BASE_URL ?>/admin_colegio/aulas.php?id=<?= (int)$aula['id'] ?>" class="btn btn-ghost btn-sm" style="flex:1;justify-content:center">
            <i data-lucide="eye" style="width:13px;height:13px"></i> Ver
          </a>
          <a href="<?= BASE_URL ?>/admin_colegio/modulos.php" class="btn btn-ghost btn-sm" style="flex:1;justify-content:center">
            <i data-lucide="book-open" style="width:13px;height:13px"></i> Módulos
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof Chart === 'undefined') return;
  const conMod = <?= (int)$estConMod ?>;
  const sinMod = <?= max(0, (int)$totalEstudiantes - (int)$estConMod) ?>;
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  new Chart(document.getElementById('chartProgreso'), {
    type: 'doughnut',
    data: {
      labels: ['Con módulo', 'Sin módulo'],
      datasets: [{
        data: [conMod, sinMod],
        backgroundColor: ['rgba(67,97,238,.85)', isDark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.08)'],
        borderColor: ['#4361ee', 'transparent'],
        borderWidth: [2, 0],
        hoverOffset: 4,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { callbacks: {
        label: ctx => ` ${ctx.label}: ${ctx.raw} estudiantes`
      }}},
      cutout: '72%',
    }
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
