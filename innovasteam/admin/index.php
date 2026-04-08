<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin('admin');

$pdo = getDB();

$stats = [
    'colegios'    => $pdo->query("SELECT COUNT(*) FROM colegios WHERE activo=1")->fetchColumn(),
    'usuarios'    => $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1")->fetchColumn(),
    'estudiantes' => $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='estudiante' AND activo=1")->fetchColumn(),
    'modulos_completados' => $pdo->query("SELECT COUNT(*) FROM progreso_estudiante WHERE completado=1")->fetchColumn(),
];

$colegiosRecientes = $pdo->query("
    SELECT col.*,
           COUNT(DISTINCT u.id) as total_estudiantes
    FROM colegios col
    LEFT JOIN usuarios u ON u.colegio_id=col.id AND u.rol='estudiante'
    WHERE col.activo=1
    GROUP BY col.id
    ORDER BY col.created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard Global';
$activeNav = 'dashboard';
require_once '../includes/header.php';
?>

<div class="page-content">
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">🏫</div>
      <div class="stat-value" style="color:var(--blue);"><?= $stats['colegios'] ?></div>
      <div class="stat-label">Colegios activos</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-value" style="color:var(--green);"><?= $stats['usuarios'] ?></div>
      <div class="stat-label">Usuarios totales</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">👨‍🎓</div>
      <div class="stat-value" style="color:var(--purple);"><?= $stats['estudiantes'] ?></div>
      <div class="stat-label">Estudiantes</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-value" style="color:var(--gold);"><?= $stats['modulos_completados'] ?></div>
      <div class="stat-label">Módulos completados</div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Colegios activos</h2>
      <a href="colegios.php" class="btn-primary" style="padding:8px 16px; font-size:13px; text-decoration:none;">Gestionar</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Colegio</th><th>Distrito</th><th>Director</th><th>Estudiantes</th><th>Acciones</th></tr>
        </thead>
        <tbody>
          <?php foreach ($colegiosRecientes as $col): ?>
          <tr>
            <td style="font-weight:600;"><?= sanitize($col['nombre']) ?></td>
            <td style="color:var(--text-secondary);"><?= sanitize($col['distrito']) ?></td>
            <td style="color:var(--text-secondary);"><?= sanitize($col['director'] ?? '—') ?></td>
            <td style="color:var(--blue); font-weight:700;"><?= $col['total_estudiantes'] ?></td>
            <td><a href="colegios.php?edit=<?= $col['id'] ?>" style="color:var(--blue); font-size:13px; text-decoration:none;">Editar</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Quick links -->
  <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; margin-top:24px;">
    <?php
    $links = [
      ['href'=>'colegios.php',  'icon'=>'🏫', 'label'=>'Colegios',  'color'=>'var(--blue)'],
      ['href'=>'usuarios.php',  'icon'=>'👥', 'label'=>'Usuarios',  'color'=>'var(--purple)'],
      ['href'=>'cursos.php',    'icon'=>'📚', 'label'=>'Cursos',    'color'=>'var(--green)'],
      ['href'=>'modulos.php',   'icon'=>'🧩', 'label'=>'Módulos',   'color'=>'var(--gold)'],
      ['href'=>'reportes.php',  'icon'=>'📊', 'label'=>'Reportes',  'color'=>'var(--coral)'],
    ];
    foreach ($links as $l): ?>
    <a href="<?= $l['href'] ?>" style="background:var(--bg-elevated); border:1px solid var(--bg-border); border-radius:12px; padding:20px; text-decoration:none; display:flex; flex-direction:column; gap:8px; transition:border-color .2s;" onmouseover="this.style.borderColor='<?= $l['color'] ?>'" onmouseout="this.style.borderColor='var(--bg-border)'">
      <span style="font-size:28px;"><?= $l['icon'] ?></span>
      <span style="font-family:'Syne',sans-serif; font-weight:700; color:var(--text-primary);"><?= $l['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
