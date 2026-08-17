<?php
// ============================================================
// INNOVA-STEAM — Dashboard Institucional (admin_colegio)
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('admin_colegio');

$user     = currentUser();
$userId   = currentUserId();
$pdo      = getDB();

// ── Resolve colegio for this director ────────────────────────
$stmtColegio = $pdo->prepare(
    'SELECT * FROM colegios WHERE director_id = ? AND activo = 1 LIMIT 1'
);
$stmtColegio->execute([$userId]);
$colegio = $stmtColegio->fetch();

if (!$colegio) {
    // Fallback: try via usuarios.colegio_id
    $stmtFallback = $pdo->prepare('SELECT colegio_id FROM usuarios WHERE id = ? LIMIT 1');
    $stmtFallback->execute([$userId]);
    $colegioId = (int)($stmtFallback->fetchColumn() ?: 0);
    if ($colegioId) {
        $stmtC2 = $pdo->prepare('SELECT * FROM colegios WHERE id = ? LIMIT 1');
        $stmtC2->execute([$colegioId]);
        $colegio = $stmtC2->fetch();
    }
}

$colegioId     = $colegio ? (int)$colegio['id']   : 0;
$colegioNombre = $colegio ? sanitize($colegio['nombre']) : 'Mi Colegio';

// ── Stats ─────────────────────────────────────────────────────

// 1. Total estudiantes en el colegio
$stmtEst = $pdo->prepare(
    'SELECT COUNT(DISTINCT ea.estudiante_id)
     FROM estudiante_aula ea
     JOIN aulas a ON a.id = ea.aula_id
     WHERE a.colegio_id = ?'
);
$stmtEst->execute([$colegioId]);
$totalEstudiantes = (int)$stmtEst->fetchColumn();

// 2. Módulos completados esta semana
$stmtModSemana = $pdo->prepare(
    'SELECT COUNT(*)
     FROM progreso_estudiante pe
     JOIN estudiante_aula ea ON ea.estudiante_id = pe.estudiante_id
     JOIN aulas a ON a.id = ea.aula_id
     WHERE a.colegio_id = ?
       AND pe.completado = 1
       AND pe.completado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
);
$stmtModSemana->execute([$colegioId]);
$modulosSemana = (int)$stmtModSemana->fetchColumn();

// 3. Practicantes asignados
$stmtPract = $pdo->prepare(
    'SELECT COUNT(DISTINCT pa.practicante_id)
     FROM practicante_aula pa
     JOIN aulas a ON a.id = pa.aula_id
     WHERE a.colegio_id = ?'
);
$stmtPract->execute([$colegioId]);
$totalPracticantes = (int)$stmtPract->fetchColumn();

// 4. Aulas activas
$stmtAulas = $pdo->prepare(
    'SELECT COUNT(*) FROM aulas WHERE colegio_id = ?'
);
$stmtAulas->execute([$colegioId]);
$totalAulas = (int)$stmtAulas->fetchColumn();

// ── Aulas table with progress ─────────────────────────────────
$stmtAulaList = $pdo->prepare(
    'SELECT
        a.id,
        a.grado,
        a.seccion,
        a.anio_escolar,
        CONCAT(ud.nombre, \' \', ud.apellido) AS docente_nombre,
        GROUP_CONCAT(DISTINCT CONCAT(up.nombre, \' \', up.apellido) SEPARATOR \', \') AS practicante_nombres,
        (SELECT COUNT(*) FROM estudiante_aula ea2 WHERE ea2.aula_id = a.id) AS total_estudiantes,
        (SELECT COUNT(DISTINCT pe.estudiante_id)
         FROM progreso_estudiante pe
         JOIN estudiante_aula ea3 ON ea3.estudiante_id = pe.estudiante_id
         WHERE ea3.aula_id = a.id AND pe.completado = 1) AS estudiantes_con_modulo
     FROM aulas a
     LEFT JOIN usuarios ud ON ud.id = a.docente_id
     LEFT JOIN practicante_aula pa ON pa.aula_id = a.id
     LEFT JOIN usuarios up ON up.id = pa.practicante_id
     WHERE a.colegio_id = ?
     GROUP BY a.id, a.grado, a.seccion, a.anio_escolar, ud.nombre, ud.apellido
     ORDER BY a.grado, a.seccion'
);
$stmtAulaList->execute([$colegioId]);
$aulas = $stmtAulaList->fetchAll();

// ── Page setup ────────────────────────────────────────────────
$pageTitle = 'Dashboard Institucional';
$activeNav = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title"><?= $colegioNombre ?></h1>
    <p class="page-subtitle">Panel de gestión institucional · <?= date('d/m/Y') ?></p>
  </div>
  <a href="<?= BASE_URL ?>/admin_colegio/reporte_dre.php"
     class="btn btn-primary">
    <i data-lucide="download" style="width:16px;height:16px"></i>
    Descargar reporte
  </a>
</div>

<!-- ── Stats ───────────────────────────────────────────────── -->
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
      <i data-lucide="check-circle" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= $modulosSemana ?></div>
    <div class="stat-label">Módulos completados esta semana</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
      <i data-lucide="user-check" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--purple)"><?= $totalPracticantes ?></div>
    <div class="stat-label">Practicantes asignados</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">
      <i data-lucide="layers" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--gold)"><?= $totalAulas ?></div>
    <div class="stat-label">Aulas activas</div>
  </div>

</div>

<!-- ── Aulas table ──────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">Aulas del colegio</h2>
      <p class="card-subtitle">Progreso de estudiantes por aula</p>
    </div>
    <a href="<?= BASE_URL ?>/admin_colegio/aulas.php" class="btn btn-ghost btn-sm">
      <i data-lucide="settings" style="width:14px;height:14px"></i>
      Gestionar aulas
    </a>
  </div>

  <?php if (empty($aulas)): ?>
  <div class="empty-state">
    <div class="empty-icon">
      <i data-lucide="inbox" style="width:40px;height:40px;opacity:.4"></i>
    </div>
    <h3>Sin aulas registradas</h3>
    <p>Crea la primera aula para comenzar a registrar estudiantes y sesiones.</p>
    <a href="<?= BASE_URL ?>/admin_colegio/aulas.php" class="btn btn-primary mt-16">
      <i data-lucide="plus" style="width:16px;height:16px"></i>
      Nueva aula
    </a>
  </div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Grado</th>
          <th>Sección</th>
          <th>Docente</th>
          <th>Practicante(s)</th>
          <th>Estudiantes</th>
          <th>Progreso</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($aulas as $aula):
          $total    = (int)$aula['total_estudiantes'];
          $conMod   = (int)$aula['estudiantes_con_modulo'];
          $pct      = $total > 0 ? round($conMod / $total * 100) : 0;
          $colorPct = $pct >= 80 ? 'var(--success)' : ($pct >= 40 ? 'var(--warning)' : 'var(--blue)');
        ?>
        <tr>
          <td data-label="Grado">
            <span class="badge badge-info"><?= (int)$aula['grado'] ?>°</span>
          </td>
          <td data-label="Sección">
            <span style="font-weight:600"><?= sanitize($aula['seccion']) ?></span>
          </td>
          <td data-label="Docente">
            <?php if ($aula['docente_nombre']): ?>
              <span class="flex items-center gap-8">
                <i data-lucide="user" style="width:14px;height:14px;color:var(--text-muted)"></i>
                <?= sanitize($aula['docente_nombre']) ?>
              </span>
            <?php else: ?>
              <span class="text-muted text-sm">Sin asignar</span>
            <?php endif; ?>
          </td>
          <td data-label="Practicante(s)">
            <?php if ($aula['practicante_nombres']): ?>
              <span class="text-sm"><?= sanitize($aula['practicante_nombres']) ?></span>
            <?php else: ?>
              <span class="badge badge-warning">Sin practicante</span>
            <?php endif; ?>
          </td>
          <td data-label="Estudiantes">
            <span class="badge"><?= $total ?></span>
          </td>
          <td data-label="Progreso" style="min-width:140px">
            <div style="display:flex;align-items:center;gap:10px">
              <div class="progress-bar-wrap" style="flex:1">
                <div class="progress-bar-fill"
                     style="width:<?= $pct ?>%;background:<?= $colorPct ?>"></div>
              </div>
              <span style="font-size:12px;font-weight:600;color:<?= $colorPct ?>;min-width:32px">
                <?= $pct ?>%
              </span>
            </div>
            <div class="text-muted text-sm mt-4"><?= $conMod ?> de <?= $total ?> con módulo</div>
          </td>
          <td data-label="Acciones">
            <div style="display:flex;gap:6px">
              <a href="<?= BASE_URL ?>/admin_colegio/aulas.php?id=<?= (int)$aula['id'] ?>"
                 class="btn btn-ghost btn-sm"
                 title="Ver detalle de aula">
                <i data-lucide="eye" style="width:14px;height:14px"></i>
              </a>
              <a href="<?= BASE_URL ?>/admin_colegio/aulas.php?action=assign&id=<?= (int)$aula['id'] ?>"
                 class="btn btn-ghost btn-sm"
                 title="Asignar practicante">
                <i data-lucide="user-plus" style="width:14px;height:14px"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
