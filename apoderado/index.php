<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('apoderado');

$apoderadoId = currentUserId();
$user        = currentUser();
$pdo         = getDB();

// Fetch linked students
$stmtEst = $pdo->prepare("
    SELECT u.id, u.nombre, u.apellido, u.email, u.ultimo_acceso,
           DATEDIFF(NOW(), u.ultimo_acceso) AS dias_sin_entrar,
           ae.relacion,
           COALESCE(pe_comp.total, 0) AS modulos_completados,
           COALESCE(pe_est.total, 0)  AS estrellas_total,
           ul.logros_ganados
    FROM apoderado_estudiante ae
    JOIN usuarios u ON u.id = ae.estudiante_id
    LEFT JOIN (
        SELECT estudiante_id, COUNT(*) as total FROM progreso_estudiante WHERE completado=1 GROUP BY estudiante_id
    ) pe_comp ON pe_comp.estudiante_id = u.id
    LEFT JOIN (
        SELECT estudiante_id, SUM(estrellas_quiz) as total FROM progreso_estudiante GROUP BY estudiante_id
    ) pe_est ON pe_est.estudiante_id = u.id
    LEFT JOIN (
        SELECT usuario_id, COUNT(*) as logros_ganados FROM usuario_logros GROUP BY usuario_id
    ) ul ON ul.usuario_id = u.id
    WHERE ae.apoderado_id = ? AND ae.activo = 1
    ORDER BY u.apellido, u.nombre
");
$stmtEst->execute([$apoderadoId]);
$estudiantes = $stmtEst->fetchAll();

$pageTitle = 'Panel Apoderado';
$activeNav = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Welcome -->
<div class="welcome-banner mb-32">
  <div class="welcome-text">
    <div class="welcome-title">¡Hola, <?= sanitize($user['nombre'] ?? 'Apoderado') ?>!</div>
    <div class="welcome-sub">Sigue el progreso académico de tu(s) hijo(s) en INNOVA-STEAM</div>
  </div>
</div>

<?php if (empty($estudiantes)): ?>
<div class="empty-state">
  <div class="empty-icon"><i data-lucide="users" style="width:40px;height:40px;opacity:.4"></i></div>
  <h3>Sin estudiantes vinculados</h3>
  <p>Contacta al director o administrador para vincular a tu hijo/a.</p>
</div>
<?php else: ?>

<!-- Summary stats -->
<div class="stats-grid mb-32" style="--cols:<?= min(count($estudiantes), 3) ?>">
  <?php foreach ($estudiantes as $est): ?>
  <div class="stat-card" style="border-color:var(--accent)22">
    <div class="stat-icon" style="background:var(--accent-light);color:var(--accent)">
      <i data-lucide="user" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="font-size:18px;color:var(--text-primary)"><?= sanitize($est['nombre'] . ' ' . $est['apellido']) ?></div>
    <div class="stat-label"><?= sanitize(ucfirst($est['relacion'] ?? 'estudiante')) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (count($estudiantes) > 1):
  // Con un solo hijo la comparación no dice nada; con varios es lo
  // primero que un apoderado quiere ver.
  $maxComp = max(1, max(array_map(fn($e) => (int)$e['modulos_completados'], $estudiantes)));
?>
<!-- Comparativa entre hijos -->
<div class="card mb-32">
  <div class="card-header">
    <div>
      <h2 class="card-title">Comparativa</h2>
      <p class="card-subtitle">Progreso de tus <?= count($estudiantes) ?> hijos, uno al lado del otro</p>
    </div>
  </div>

  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Estudiante</th>
          <th>Módulos completados</th>
          <th style="text-align:center">Estrellas</th>
          <th style="text-align:center">Logros</th>
          <th>Última conexión</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($estudiantes as $est):
          $comp = (int)$est['modulos_completados'];
          $dias = $est['ultimo_acceso'] === null ? null : (int)$est['dias_sin_entrar'];
        ?>
        <tr>
          <td style="font-weight:600;color:var(--text-primary)">
            <?= sanitize(trim($est['nombre'] . ' ' . $est['apellido'])) ?>
          </td>
          <td>
            <!-- Barra a escala común: comparar exige el mismo eje -->
            <div style="display:flex;align-items:center;gap:10px">
              <div class="progress-track" style="flex:1;min-width:90px">
                <div class="progress-fill" data-pct="<?= round($comp / $maxComp * 100) ?>"
                     style="width:<?= round($comp / $maxComp * 100) ?>%;background:var(--accent)"></div>
              </div>
              <span class="num" style="font-weight:700;color:var(--text-primary);min-width:20px"><?= $comp ?></span>
            </div>
          </td>
          <td style="text-align:center;font-weight:700;color:var(--gold)" class="num">
            <?= (int)$est['estrellas_total'] ?>
          </td>
          <td style="text-align:center;font-weight:700;color:var(--purple)" class="num">
            <?= (int)$est['logros_ganados'] ?>
          </td>
          <td style="font-size:12.5px">
            <?php if ($dias === null): ?>
              <span style="color:var(--danger);font-weight:600">Nunca ha entrado</span>
            <?php elseif ($dias >= 7): ?>
              <span style="color:var(--warning);font-weight:600">Hace <?= $dias ?> días</span>
            <?php elseif ($dias <= 1): ?>
              <span style="color:var(--success);font-weight:600">Hoy</span>
            <?php else: ?>
              <span style="color:var(--text-muted)">Hace <?= $dias ?> días</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Per-student detail cards -->
<?php foreach ($estudiantes as $est):
  // Fetch recent progress for this student
  $recentProg = $pdo->prepare("
      SELECT pe.completado_en, m.titulo as modulo_titulo, c.nombre as curso_nombre, c.color_hex,
             pe.estrellas_quiz, pe.completado
      FROM progreso_estudiante pe
      JOIN modulos m ON m.id = pe.modulo_id
      JOIN cursos c ON c.id = m.curso_id
      WHERE pe.estudiante_id = ? AND pe.completado = 1
      ORDER BY pe.completado_en DESC LIMIT 5
  ");
  $recentProg->execute([$est['id']]);
  $recientes = $recentProg->fetchAll();

  // Fetch logros
  $logros = $pdo->prepare("
      SELECT l.slug, l.nombre, l.icono, ul.obtenido_en
      FROM usuario_logros ul
      JOIN logros l ON l.id = ul.logro_id
      WHERE ul.usuario_id = ?
      ORDER BY ul.obtenido_en DESC LIMIT 6
  ");
  $logros->execute([$est['id']]);
  $logrosEst = $logros->fetchAll();
?>
<div class="card mb-24">
  <div class="card-header">
    <div>
      <h2 class="card-title" style="display:flex;align-items:center;gap:8px">
        <div style="width:36px;height:36px;border-radius:50%;background:var(--accent-light);color:var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:15px">
          <?= strtoupper(mb_substr($est['nombre'], 0, 1)) ?>
        </div>
        <?= sanitize($est['nombre'] . ' ' . $est['apellido']) ?>
      </h2>
      <p class="card-subtitle"><?= sanitize($est['email'] ?? '') ?></p>
    </div>
  </div>

  <!-- Mini stats -->
  <div class="stats-grid-6" style="--cols:3;margin-bottom:20px">
    <div style="background:var(--bg-elevated);border-radius:12px;padding:14px;text-align:center">
      <div style="font-size:22px;font-weight:800;color:var(--green);font-family:'Syne',sans-serif"><?= (int)$est['modulos_completados'] ?></div>
      <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Módulos completados</div>
    </div>
    <div style="background:var(--bg-elevated);border-radius:12px;padding:14px;text-align:center">
      <div style="font-size:22px;font-weight:800;color:var(--gold);font-family:'Syne',sans-serif"><?= (int)$est['estrellas_total'] ?></div>
      <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Estrellas ganadas</div>
    </div>
    <div style="background:var(--bg-elevated);border-radius:12px;padding:14px;text-align:center">
      <div style="font-size:22px;font-weight:800;color:var(--purple);font-family:'Syne',sans-serif"><?= (int)$est['logros_ganados'] ?></div>
      <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Logros obtenidos</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <!-- Recent completions -->
    <div>
      <p style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);font-weight:700;margin-bottom:10px">Módulos recientes</p>
      <?php if (empty($recientes)): ?>
        <p style="font-size:13px;color:var(--text-muted)">Sin actividad aún</p>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($recientes as $r): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:10px;background:var(--bg-elevated)">
          <div style="width:8px;height:8px;border-radius:50%;background:<?= $r['color_hex'] ?>;flex-shrink:0"></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:600;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= sanitize($r['modulo_titulo']) ?></div>
            <div style="font-size:10px;color:var(--text-muted)"><?= sanitize($r['curso_nombre']) ?></div>
          </div>
          <div style="display:flex;gap:2px;flex-shrink:0">
            <?php for ($i=0; $i<3; $i++): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="<?= $i < (int)$r['estrellas_quiz'] ? 'var(--gold)' : 'none' ?>" stroke="var(--gold)" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <?php endfor; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Logros -->
    <div>
      <p style="font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);font-weight:700;margin-bottom:10px">Últimos logros</p>
      <?php if (empty($logrosEst)): ?>
        <p style="font-size:13px;color:var(--text-muted)">Sin logros aún</p>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($logrosEst as $lg): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:10px;background:var(--bg-elevated)">
          <span style="font-size:20px;line-height:1"><?= $lg['icono'] ?></span>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:600;color:var(--text-primary)"><?= sanitize($lg['nombre']) ?></div>
            <div style="font-size:10px;color:var(--text-muted)"><?= $lg['obtenido_en'] ? date('d/m/Y', strtotime($lg['obtenido_en'])) : '' ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
