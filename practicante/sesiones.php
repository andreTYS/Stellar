<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('practicante');

$uid = currentUserId();

// ── Data ──────────────────────────────────────────────────────
$sesiones = getSesionesByPracticante($uid, 50);

// ── View ──────────────────────────────────────────────────────
$pageTitle = 'Mis Sesiones';
$activeNav = 'sesiones';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">Mis Sesiones</h1>
    <p class="page-subtitle">Historial de sesiones registradas</p>
  </div>
  <a href="<?= BASE_URL ?>/practicante/asistencia.php" class="btn btn-primary">
    <i data-lucide="plus" style="width:16px;height:16px"></i>
    Nueva sesión
  </a>
</div>

<div class="card">
  <div class="card-header flex justify-between items-center">
    <h2 class="card-title">Historial</h2>
    <span class="badge"><?= count($sesiones) ?> sesiones</span>
  </div>

  <?php if (empty($sesiones)): ?>
    <div class="empty-state">
      <div class="empty-icon">
        <i data-lucide="calendar-x" style="width:40px;height:40px;opacity:.4"></i>
      </div>
      <h3>Sin sesiones registradas</h3>
      <p>Aún no has registrado ninguna sesión. ¡Empieza ahora!</p>
      <a href="<?= BASE_URL ?>/practicante/asistencia.php" class="btn btn-primary mt-16">
        <i data-lucide="plus" style="width:16px;height:16px"></i>
        Registrar primera sesión
      </a>
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
          <th>Notas</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sesiones as $s): ?>
        <tr>
          <td style="white-space:nowrap">
            <span style="font-size:13px;color:var(--text-muted)">
              <?= sanitize(formatDate($s['fecha_sesion'])) ?>
            </span>
          </td>
          <td><?= sanitize($s['colegio_nombre'] ?? '') ?></td>
          <td style="white-space:nowrap">
            <?= sanitize((string)($s['grado'] ?? '')) ?>° <?= sanitize($s['seccion'] ?? '') ?>
          </td>
          <td>
            <span style="font-weight:500"><?= sanitize($s['modulo_titulo'] ?? '') ?></span>
          </td>
          <td>
            <span class="badge badge-success"><?= (int)($s['asistentes'] ?? 0) ?></span>
          </td>
          <td style="max-width:220px">
            <?php if (!empty($s['notas'])): ?>
              <span class="text-muted text-sm" title="<?= sanitize($s['notas']) ?>">
                <?= sanitize(mb_strimwidth($s['notas'], 0, 60, '…')) ?>
              </span>
            <?php else: ?>
              <span class="text-muted text-sm">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
