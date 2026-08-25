<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('practicante');

$uid  = currentUserId();
$user = currentUser();
$db   = getDB();

// ── Aulas with student counts in ONE query ────────────────────
$stmtAulas = $db->prepare("
    SELECT a.id, a.grado, a.seccion, col.nombre AS colegio_nombre,
           COUNT(DISTINCT ea.estudiante_id) AS total_est
    FROM practicante_aula pa
    JOIN aulas a ON a.id = pa.aula_id
    JOIN colegios col ON col.id = a.colegio_id
    LEFT JOIN estudiante_aula ea ON ea.aula_id = a.id
    WHERE pa.practicante_id = ?
    GROUP BY a.id, a.grado, a.seccion, col.nombre
    ORDER BY col.nombre, a.grado, a.seccion
");
$stmtAulas->execute([$uid]);
$aulas = $stmtAulas->fetchAll();

$totalEstudiantes = array_sum(array_column($aulas, 'total_est'));

// ── Sessions ──────────────────────────────────────────────────
$stmtSemana = $db->prepare('SELECT COUNT(*) FROM sesiones WHERE practicante_id=? AND fecha_sesion >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
$stmtSemana->execute([$uid]);
$sesionesSemana = (int)$stmtSemana->fetchColumn();

$stmtTotal = $db->prepare('SELECT COUNT(*) FROM sesiones WHERE practicante_id=?');
$stmtTotal->execute([$uid]);
$sesionesTotal = (int)$stmtTotal->fetchColumn();

$sesiones = getSesionesByPracticante($uid, 5);

// Upcoming planned modules from aula_modulos
$stmtCal = $db->prepare("
    SELECT am.fecha_planificada, m.titulo as modulo_titulo, c.nombre as curso_nombre, c.color_hex,
           a.grado, a.seccion
    FROM aula_modulos am
    JOIN aulas a ON a.id = am.aula_id
    JOIN modulos m ON m.id = am.modulo_id
    JOIN cursos c ON c.id = m.curso_id
    JOIN practicante_aula pa ON pa.aula_id = a.id AND pa.practicante_id = ?
    WHERE am.fecha_planificada >= CURDATE()
    ORDER BY am.fecha_planificada ASC
    LIMIT 8
");
$stmtCal->execute([$uid]);
$proximasSesiones = $stmtCal->fetchAll();

$pageTitle = 'Dashboard Practicante';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome banner -->
<div class="welcome-banner mb-32">
  <div class="welcome-text">
    <div class="welcome-title">¡Hola, <?= sanitize($user['nombre'] ?? 'Practicante') ?>!</div>
    <div class="welcome-sub">Gestiona tus aulas y registra el progreso de cada sesión</div>
  </div>
  <div class="welcome-action">
    <a href="<?= BASE_URL ?>/practicante/asistencia.php" class="btn btn-primary">
      <i data-lucide="plus" style="width:16px;height:16px"></i>
      Nueva sesión
    </a>
  </div>
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
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
      <i data-lucide="users" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--purple)"><?= $totalEstudiantes ?></div>
    <div class="stat-label">Estudiantes totales</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
      <i data-lucide="calendar-check" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= $sesionesSemana ?></div>
    <div class="stat-label">Sesiones esta semana</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">
      <i data-lucide="clipboard-list" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--gold)"><?= $sesionesTotal ?></div>
    <div class="stat-label">Total sesiones registradas</div>
  </div>
</div>

<!-- Mis aulas (cards) -->
<div class="card mb-24">
  <div class="card-header">
    <div>
      <h2 class="card-title">Mis Aulas</h2>
      <p class="card-subtitle"><?= count($aulas) ?> aulas asignadas</p>
    </div>
  </div>

  <?php if (empty($aulas)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i data-lucide="inbox" style="width:40px;height:40px;opacity:.4"></i></div>
      <h3>Sin aulas asignadas</h3>
      <p>Contacta al director de tu colegio.</p>
    </div>
  <?php else: ?>
  <div class="aulas-grid-3" style="padding-bottom:4px">
    <?php foreach ($aulas as $aula): ?>
    <div class="aula-card">
      <div class="aula-card-header">
        <div>
          <div class="aula-card-title"><?= (int)$aula['grado'] ?>° &ldquo;<?= sanitize($aula['seccion']) ?>&rdquo;</div>
          <div class="aula-card-sub"><?= sanitize($aula['colegio_nombre']) ?></div>
        </div>
        <div class="activity-dot" style="background:rgba(74,158,255,.12);color:var(--blue)">
          <i data-lucide="users" style="width:14px;height:14px"></i>
        </div>
      </div>
      <div class="metric-pair" style="margin-bottom:16px">
        <div class="metric-item">
          <div class="metric-val" style="color:var(--blue)"><?= (int)$aula['total_est'] ?></div>
          <div class="metric-lbl">estudiantes</div>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/practicante/asistencia.php?aula_id=<?= (int)$aula['id'] ?>"
         class="btn btn-primary btn-sm btn-block" style="justify-content:center;text-decoration:none">
        <i data-lucide="clipboard-check" style="width:14px;height:14px"></i>
        Registrar sesión
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Sesiones recientes -->
<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">Sesiones recientes</h2>
      <p class="card-subtitle">Últimas 5 sesiones registradas</p>
    </div>
    <a href="<?= BASE_URL ?>/practicante/sesiones.php" class="btn btn-ghost btn-sm">
      Ver todo <i data-lucide="arrow-right" style="width:14px;height:14px"></i>
    </a>
  </div>

  <?php if (empty($sesiones)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i data-lucide="calendar-x" style="width:40px;height:40px;opacity:.4"></i></div>
      <h3>Sin sesiones registradas</h3>
      <p>Registra tu primera sesión seleccionando un aula arriba.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Colegio</th>
          <th>Aula</th>
          <th>Módulo</th>
          <th>Asistentes</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sesiones as $s): ?>
        <tr>
          <td><span style="font-size:12px;color:var(--text-muted)"><?= sanitize(formatDate($s['fecha_sesion'])) ?></span></td>
          <td><?= sanitize($s['colegio_nombre'] ?? '') ?></td>
          <td style="font-weight:600"><?= (int)($s['grado'] ?? 0) ?>° <?= sanitize($s['seccion'] ?? '') ?></td>
          <td><?= sanitize($s['modulo_titulo'] ?? '') ?></td>
          <td><span class="badge" style="background:rgba(62,207,142,.12);color:var(--green)"><?= (int)($s['asistentes'] ?? 0) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($proximasSesiones)): ?>
<!-- Upcoming sessions calendar -->
<div class="card mt-24">
  <div class="card-header">
    <div>
      <h2 class="card-title" style="display:flex;align-items:center;gap:8px">
        <i data-lucide="calendar-days" style="width:16px;height:16px;color:var(--accent)"></i>
        Próximas sesiones planificadas
      </h2>
      <p class="card-subtitle">Módulos con fecha asignada en tus aulas</p>
    </div>
  </div>
  <div style="display:flex;flex-direction:column;gap:8px">
    <?php foreach ($proximasSesiones as $ps):
      $dias = (int)ceil((strtotime($ps['fecha_planificada']) - time()) / 86400);
      $diaLabel = $dias === 0 ? 'Hoy' : ($dias === 1 ? 'Mañana' : 'En ' . $dias . ' días');
      $urgColor = $dias <= 1 ? 'var(--danger)' : ($dias <= 7 ? 'var(--gold)' : 'var(--text-muted)');
    ?>
    <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:10px;background:var(--bg-elevated)">
      <div style="width:48px;text-align:center;flex-shrink:0">
        <div style="font-size:18px;font-weight:800;font-family:'Syne',sans-serif;color:var(--accent);line-height:1"><?= date('d', strtotime($ps['fecha_planificada'])) ?></div>
        <div style="font-size:10px;font-weight:600;color:var(--text-muted);text-transform:uppercase"><?= date('M', strtotime($ps['fecha_planificada'])) ?></div>
      </div>
      <div style="width:1px;height:36px;background:var(--bg-border);flex-shrink:0"></div>
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= sanitize($ps['modulo_titulo']) ?></div>
        <div style="font-size:11px;color:var(--text-muted)"><?= (int)$ps['grado'] ?>° "<?= sanitize($ps['seccion']) ?>" · <span style="color:<?= $ps['color_hex'] ?>;font-weight:600"><?= sanitize($ps['curso_nombre']) ?></span></div>
      </div>
      <span style="font-size:11px;font-weight:700;color:<?= $urgColor ?>;flex-shrink:0"><?= $diaLabel ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
