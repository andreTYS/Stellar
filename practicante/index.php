<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('practicante');

$uid  = currentUserId();
$user = currentUser();

// ── Data ──────────────────────────────────────────────────────
$aulas   = getAulasByPracticante($uid);
$sesiones = getSesionesByPracticante($uid, 5);

// Sesiones esta semana
$db = getDB();
$stmtSemana = $db->prepare(
    'SELECT COUNT(*) FROM sesiones
     WHERE practicante_id = ?
       AND fecha_sesion >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
);
$stmtSemana->execute([$uid]);
$sesionesSemana = (int)$stmtSemana->fetchColumn();

// Total estudiantes (distinct across all aulas)
$totalEstudiantes = 0;
foreach ($aulas as $aula) {
    $stmtEst = $db->prepare('SELECT COUNT(*) FROM estudiante_aula WHERE aula_id = ?');
    $stmtEst->execute([$aula['id']]);
    $totalEstudiantes += (int)$stmtEst->fetchColumn();
}

// ── View ──────────────────────────────────────────────────────
$pageTitle = 'Dashboard Practicante';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header flex justify-between items-center">
  <div>
    <h1 class="page-title">¡Hola, <?= sanitize($user['nombre'] ?? 'Practicante') ?>!</h1>
    <p class="page-subtitle">Gestiona tus aulas y registra el progreso de las sesiones</p>
  </div>
  <a href="<?= BASE_URL ?>/practicante/asistencia.php" class="btn btn-primary">
    <i data-lucide="plus" style="width:16px;height:16px"></i>
    Nueva sesión
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
      <i data-lucide="calendar-check" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= $sesionesSemana ?></div>
    <div class="stat-label">Sesiones esta semana</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
      <i data-lucide="users" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--purple)"><?= $totalEstudiantes ?></div>
    <div class="stat-label">Estudiantes totales</div>
  </div>
</div>

<!-- Mis Aulas -->
<div class="card mb-24">
  <div class="card-header flex justify-between items-center">
    <h2 class="card-title">Mis Aulas</h2>
    <span class="badge"><?= count($aulas) ?> aulas</span>
  </div>

  <?php if (empty($aulas)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i data-lucide="inbox" style="width:40px;height:40px;opacity:.4"></i></div>
      <h3>Sin aulas asignadas</h3>
      <p>Contacta al director de tu colegio para que te asigne un aula.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Colegio</th>
          <th>Grado</th>
          <th>Sección</th>
          <th>Estudiantes</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($aulas as $aula):
          $stmtN = $db->prepare('SELECT COUNT(*) FROM estudiante_aula WHERE aula_id = ?');
          $stmtN->execute([$aula['id']]);
          $nEst = (int)$stmtN->fetchColumn();
        ?>
        <tr>
          <td><?= sanitize($aula['colegio_nombre'] ?? '') ?></td>
          <td><?= sanitize((string)($aula['grado'] ?? '')) ?>°</td>
          <td><?= sanitize($aula['seccion'] ?? '') ?></td>
          <td>
            <span class="badge"><?= $nEst ?> estudiantes</span>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/practicante/asistencia.php?aula_id=<?= (int)$aula['id'] ?>"
               class="btn btn-primary btn-sm">
              <i data-lucide="clipboard-check" style="width:14px;height:14px"></i>
              Registrar sesión
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Sesiones recientes -->
<div class="card">
  <div class="card-header flex justify-between items-center">
    <h2 class="card-title">Sesiones recientes</h2>
    <a href="<?= BASE_URL ?>/practicante/sesiones.php" class="btn btn-ghost btn-sm">
      Ver todo
      <i data-lucide="arrow-right" style="width:14px;height:14px"></i>
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
          <td>
            <span class="text-muted text-sm"><?= sanitize(formatDate($s['fecha_sesion'])) ?></span>
          </td>
          <td><?= sanitize($s['colegio_nombre'] ?? '') ?></td>
          <td><?= sanitize((string)($s['grado'] ?? '')) ?>° <?= sanitize($s['seccion'] ?? '') ?></td>
          <td><?= sanitize($s['modulo_titulo'] ?? '') ?></td>
          <td>
            <span class="badge badge-success"><?= (int)($s['asistentes'] ?? 0) ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
