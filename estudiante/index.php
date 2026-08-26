<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('estudiante');

$estudianteId   = currentUserId();
$user           = currentUser();
$pdo            = getDB();

// ── All courses + progress in ONE consolidated query ──────────
$cursosData = $pdo->prepare("
    SELECT c.id, c.nombre, c.descripcion, c.color_hex,
           COUNT(DISTINCT m.id)                          AS total_modulos,
           COUNT(DISTINCT CASE WHEN pe.completado=1 THEN pe.modulo_id END) AS completados
    FROM cursos c
    LEFT JOIN modulos m ON m.curso_id = c.id
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id = m.id AND pe.estudiante_id = ?
    GROUP BY c.id, c.nombre, c.descripcion, c.color_hex
    ORDER BY c.id
");
$cursosData->execute([$estudianteId]);
$cursosData = $cursosData->fetchAll();

$modulosTotalGlobal      = array_sum(array_column($cursosData, 'total_modulos'));
$modulosCompletadosTotal = array_sum(array_column($cursosData, 'completados'));
$pctGlobal               = $modulosTotalGlobal > 0 ? round($modulosCompletadosTotal / $modulosTotalGlobal * 100) : 0;

$estrellasTotal = getEstrellasTotal($estudianteId);

// StellarScribe activity
$stellarData = ['capitulos' => 0, 'seg' => 0, 'logros' => 0];
try {
    $stCap = $pdo->prepare('SELECT COUNT(*) FROM capitulos_progreso WHERE usuario_id=?');
    $stCap->execute([$estudianteId]);
    $stellarData['capitulos'] = (int)$stCap->fetchColumn();

    $stSim = $pdo->prepare('SELECT COALESCE(SUM(duracion_seg),0) FROM simulador_sesiones WHERE usuario_id=? AND completado=1');
    $stSim->execute([$estudianteId]);
    $stellarData['seg'] = (int)$stSim->fetchColumn();

    $stLog = $pdo->prepare('SELECT COUNT(*) FROM usuario_logros WHERE usuario_id=?');
    $stLog->execute([$estudianteId]);
    $stellarData['logros'] = (int)$stLog->fetchColumn();
} catch (\Throwable $e) {}

// ── Posición global en el aula ───────────────────────────────
// curso.php ya mostraba un ranking, pero acotado a un solo curso.
// Aquí interesa la posición general, que es la que el estudiante
// reconoce como "en qué lugar voy".
$ranking    = [];
$miPosicion = null;
try {
    $aulaRow = $pdo->prepare('SELECT aula_id FROM estudiante_aula WHERE estudiante_id = ? LIMIT 1');
    $aulaRow->execute([$estudianteId]);
    $aulaId = $aulaRow->fetchColumn();

    if ($aulaId) {
        $stRank = $pdo->prepare("
            SELECT u.id, u.nombre, u.apellido,
                   COALESCE(SUM(pe.estrellas_quiz), 0)          AS estrellas,
                   COUNT(CASE WHEN pe.completado = 1 THEN 1 END) AS completados
              FROM estudiante_aula ea
              JOIN usuarios u ON u.id = ea.estudiante_id AND u.activo = 1
         LEFT JOIN progreso_estudiante pe ON pe.estudiante_id = u.id
             WHERE ea.aula_id = ?
          GROUP BY u.id, u.nombre, u.apellido
          ORDER BY estrellas DESC, completados DESC, u.apellido ASC
        ");
        $stRank->execute([$aulaId]);
        $todos = $stRank->fetchAll();

        foreach ($todos as $i => $fila) {
            if ((int)$fila['id'] === $estudianteId) {
                $miPosicion = $i + 1;
                break;
            }
        }
        // Se muestran los 5 primeros; si el estudiante quedó fuera, se
        // añade su propia fila para que siempre se vea a sí mismo.
        $ranking = array_slice($todos, 0, 5);
        if ($miPosicion !== null && $miPosicion > 5) {
            $ranking[] = $todos[$miPosicion - 1] + ['_posicion' => $miPosicion];
        }
        $totalAula = count($todos);
    }
} catch (\Throwable $e) {}

$pageTitle = 'Mis Cursos';
$activeNav = 'inicio';
include __DIR__ . '/../includes/header.php';
?>

<!-- Welcome banner -->
<div class="welcome-banner mb-32">
  <div class="welcome-text">
    <div class="welcome-title">¡Hola, <?= sanitize($user['nombre'] ?? 'Estudiante') ?>!</div>
    <div class="welcome-sub">Continúa tu aventura de aprendizaje STEAM · <?= $pctGlobal ?>% de progreso global</div>
  </div>
  <div class="welcome-action" style="display:flex;align-items:center;gap:20px">
    <div class="ring ring-on-accent" data-pct="<?= $pctGlobal ?>" data-color="#fff"
         data-tip="Progreso global en todos tus cursos"
         style="--ring-size:74px;--ring-stroke:6px"></div>
    <a href="<?= BASE_URL ?>/estudiante/certificados.php" class="btn btn-primary">
      <i data-lucide="award" style="width:16px;height:16px"></i>
      Mis certificados
    </a>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid mb-32">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">
      <i data-lucide="star" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--gold)"><?= (int)$estrellasTotal ?></div>
    <div class="stat-label">Estrellas ganadas</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
      <i data-lucide="check-circle-2" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= (int)$modulosCompletadosTotal ?></div>
    <div class="stat-label">Módulos completados</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">
      <i data-lucide="book-open" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--blue)"><?= (int)$modulosTotalGlobal ?></div>
    <div class="stat-label">Módulos en total</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
      <i data-lucide="layers" style="width:22px;height:22px"></i>
    </div>
    <div class="stat-value" style="color:var(--purple)"><?= count($cursosData) ?></div>
    <div class="stat-label">Cursos disponibles</div>
  </div>
</div>

<?php if (!empty($ranking) && $miPosicion !== null): ?>
<!-- Posición en el aula -->
<div class="card mb-32">
  <div class="card-header">
    <div>
      <h2 class="card-title">Tu posición en el aula</h2>
      <p class="card-subtitle">
        Vas <?= $miPosicion ?>.º de <?= (int)($totalAula ?? count($ranking)) ?>
        · las estrellas se ganan en los quizzes
      </p>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:2px">
    <?php foreach ($ranking as $i => $r):
      $pos    = $r['_posicion'] ?? ($i + 1);
      $soyYo  = (int)$r['id'] === $estudianteId;
      $medalla = match ((int)$pos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => null };
      // Salto visual cuando la fila propia no es consecutiva con el top 5.
      $corte = isset($r['_posicion']) && $r['_posicion'] > 6;
    ?>
      <?php if ($corte): ?>
      <div style="text-align:center;color:var(--text-muted);font-size:16px;line-height:1;padding:2px 0">···</div>
      <?php endif; ?>
      <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:10px;
                  background:<?= $soyYo ? 'var(--accent-light)' : 'transparent' ?>">
        <span style="width:26px;text-align:center;font-weight:700;font-size:14px;
                     color:<?= $soyYo ? 'var(--accent)' : 'var(--text-muted)' ?>" class="num">
          <?= $medalla ?? $pos . '.º' ?>
        </span>
        <span style="flex:1;font-size:13.5px;font-weight:<?= $soyYo ? '700' : '500' ?>;
                     color:var(--text-primary);min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= sanitize(trim($r['nombre'] . ' ' . $r['apellido'])) ?><?= $soyYo ? ' (tú)' : '' ?>
        </span>
        <span style="font-size:12px;color:var(--text-muted)" class="num">
          <?= (int)$r['completados'] ?> mód.
        </span>
        <span style="display:flex;align-items:center;gap:4px;font-size:13px;font-weight:700;color:var(--gold)" class="num">
          <i data-lucide="star" style="width:14px;height:14px;fill:var(--gold)"></i>
          <?= (int)$r['estrellas'] ?>
        </span>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Courses heading -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <h2 style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:var(--text-primary)">Mis Cursos</h2>
  <span style="font-size:12px;color:var(--text-muted)"><?= count($cursosData) ?> cursos · <?= $pctGlobal ?>% completado</span>
</div>

<?php if ($stellarData['capitulos'] > 0 || $stellarData['seg'] > 0 || $stellarData['logros'] > 0): ?>
<!-- StellarScribe activity strip -->
<div style="margin-bottom:32px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
    <h2 style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:8px">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4361ee" stroke-width="2"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9"/><path d="M20 3v4M22 5h-4"/></svg>
      Tu actividad espacial
    </h2>
    <a href="<?= BASE_URL ?>/stellarscribe/portal.php" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:600">Ver StellarScribe →</a>
  </div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
    <div style="background:linear-gradient(135deg,#0d1b33,#1a2d4a);border:1px solid #1e3058;border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:12px">
      <div style="width:40px;height:40px;border-radius:10px;background:rgba(67,97,238,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7c9fff" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
      </div>
      <div>
        <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:#7c9fff;line-height:1"><?= $stellarData['capitulos'] ?></div>
        <div style="font-size:11px;color:#5c7ab0;margin-top:2px">Capítulos leídos</div>
      </div>
    </div>
    <div style="background:linear-gradient(135deg,#0d1b33,#1a2d4a);border:1px solid #1e3058;border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:12px">
      <div style="width:40px;height:40px;border-radius:10px;background:rgba(124,58,237,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
      </div>
      <div>
        <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:#a78bfa;line-height:1"><?= round($stellarData['seg'] / 60) ?></div>
        <div style="font-size:11px;color:#5c7ab0;margin-top:2px">Min. en simuladores</div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/estudiante/logros.php" style="background:linear-gradient(135deg,#0d1b33,#1a2d4a);border:1px solid #1e3058;border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:12px;text-decoration:none">
      <div style="width:40px;height:40px;border-radius:10px;background:rgba(245,158,11,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
      </div>
      <div>
        <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:#fbbf24;line-height:1"><?= $stellarData['logros'] ?></div>
        <div style="font-size:11px;color:#5c7ab0;margin-top:2px">Logros ganados →</div>
      </div>
    </a>
  </div>
</div>
<?php endif; ?>

<?php if (empty($cursosData)): ?>
  <div class="empty-state">
    <div class="empty-icon"><i data-lucide="book-open" style="width:40px;height:40px;opacity:.4"></i></div>
    <p class="empty-state-title">No hay cursos disponibles</p>
    <p class="empty-state-desc">Pronto se habilitarán cursos para ti.</p>
  </div>
<?php else: ?>
<div class="courses-grid">
  <?php foreach ($cursosData as $c):
    $color      = sanitize($c['color_hex'] ?? '#4361ee');
    $total      = (int)$c['total_modulos'];
    $comp       = (int)$c['completados'];
    $pct        = $total > 0 ? round($comp / $total * 100) : 0;
    $initial    = strtoupper(mb_substr(trim($c['nombre'] ?? 'C'), 0, 1));
  ?>
  <a href="<?= BASE_URL ?>/estudiante/curso.php?id=<?= (int)$c['id'] ?>"
     class="course-card" style="--color:<?= $color ?>;text-decoration:none">

    <div class="course-card-header" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>cc);position:relative;overflow:hidden">
      <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:48px;color:rgba(255,255,255,.9);line-height:1;position:relative;z-index:1;user-select:none"><?= htmlspecialchars($initial) ?></span>
      <?php if ($pct === 100): ?>
      <span style="position:absolute;top:12px;right:12px;background:rgba(255,255,255,.25);color:#fff;border-radius:99px;padding:3px 10px;font-size:11px;font-weight:700;display:flex;align-items:center;gap:4px;z-index:1">
        <i data-lucide="check-circle" style="width:12px;height:12px"></i> Completado
      </span>
      <?php endif; ?>
    </div>

    <div class="course-card-body">
      <div class="course-card-name"><?= sanitize($c['nombre']) ?></div>

      <?php if (!empty($c['descripcion'])): ?>
        <p style="font-size:13px;color:var(--text-muted);line-height:1.5;margin-bottom:14px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
          <?= sanitize($c['descripcion']) ?>
        </p>
      <?php endif; ?>

      <div class="progress-track" style="margin-bottom:8px">
        <div class="progress-fill" data-pct="<?= $pct ?>" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
      </div>
      <div class="progress-label">
        <span><?= $comp ?> / <?= $total ?> módulos</span>
        <span style="color:<?= $color ?>;font-weight:600"><?= $pct ?>%</span>
      </div>
    </div>

  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
