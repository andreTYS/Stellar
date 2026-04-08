<?php
// ============================================================
// INNOVA-STEAM — Header / Sidebar compartido
// Vars esperadas: $pageTitle (string), $activeNav (string)
// ============================================================
if (!isset($pageTitle)) $pageTitle = 'INNOVA-STEAM';
if (!isset($activeNav)) $activeNav = '';

$user    = currentUser();
$rol     = currentRole();
$initials = strtoupper(substr($user['nombre'] ?? 'U', 0, 1) . substr($user['apellido'] ?? '', 0, 1));

// Menú según rol
$navItems = match ($rol) {
    'estudiante' => [
        ['href' => BASE_URL . '/estudiante/index.php',       'icon' => 'home',         'label' => 'Inicio',       'key' => 'inicio'],
        ['href' => BASE_URL . '/estudiante/portafolio.php',  'icon' => 'folder',        'label' => 'Portafolio',   'key' => 'portafolio'],
        ['href' => BASE_URL . '/estudiante/certificados.php','icon' => 'award',         'label' => 'Certificados', 'key' => 'certificados'],
    ],
    'practicante' => [
        ['href' => BASE_URL . '/practicante/index.php',      'icon' => 'layout-dashboard','label' => 'Dashboard',  'key' => 'dashboard'],
        ['href' => BASE_URL . '/practicante/sesiones.php',   'icon' => 'calendar',       'label' => 'Sesiones',    'key' => 'sesiones'],
        ['href' => BASE_URL . '/practicante/informe.php',    'icon' => 'file-text',      'label' => 'Informe',     'key' => 'informe'],
    ],
    'docente' => [
        ['href' => BASE_URL . '/docente/index.php',          'icon' => 'layout-dashboard','label' => 'Dashboard',  'key' => 'dashboard'],
        ['href' => BASE_URL . '/docente/estudiantes.php',    'icon' => 'users',           'label' => 'Estudiantes','key' => 'estudiantes'],
        ['href' => BASE_URL . '/docente/portafolios.php',    'icon' => 'folder',          'label' => 'Portafolios','key' => 'portafolios'],
        ['href' => BASE_URL . '/docente/reportes.php',       'icon' => 'bar-chart-2',     'label' => 'Reportes',   'key' => 'reportes'],
    ],
    'admin_colegio' => [
        ['href' => BASE_URL . '/admin_colegio/index.php',    'icon' => 'layout-dashboard','label' => 'Dashboard',  'key' => 'dashboard'],
        ['href' => BASE_URL . '/admin_colegio/aulas.php',    'icon' => 'layers',          'label' => 'Aulas',      'key' => 'aulas'],
        ['href' => BASE_URL . '/admin_colegio/modulos.php',  'icon' => 'book-open',       'label' => 'Módulos',    'key' => 'modulos'],
        ['href' => BASE_URL . '/admin_colegio/reporte_dre.php','icon'=>'download',         'label' => 'Reporte DRE','key' => 'reporte_dre'],
    ],
    'admin' => [
        ['href' => BASE_URL . '/admin/index.php',            'icon' => 'layout-dashboard','label' => 'Dashboard',  'key' => 'dashboard'],
        ['href' => BASE_URL . '/admin/colegios.php',         'icon' => 'building-2',      'label' => 'Colegios',   'key' => 'colegios'],
        ['href' => BASE_URL . '/admin/usuarios.php',         'icon' => 'users',           'label' => 'Usuarios',   'key' => 'usuarios'],
        ['href' => BASE_URL . '/admin/modulos.php',          'icon' => 'book-open',       'label' => 'Módulos',    'key' => 'modulos'],
        ['href' => BASE_URL . '/admin/reportes.php',         'icon' => 'bar-chart-2',     'label' => 'Reportes',   'key' => 'reportes'],
    ],
    default => [],
};

$rolLabel = match ($rol) {
    'admin'         => 'Administrador',
    'admin_colegio' => 'Director',
    'docente'       => 'Docente',
    'practicante'   => 'Practicante',
    'estudiante'    => 'Estudiante',
    default         => $rol,
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= sanitize($pageTitle) ?> — INNOVA-STEAM</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/stellar.css" />
  <?php if (isset($extraCss)): foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= BASE_URL . '/assets/css/' . $css ?>" />
  <?php endforeach; endif; ?>
  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js" defer></script>
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99;backdrop-filter:blur(2px)"
     onclick="document.getElementById('sidebar').classList.remove('open');this.style.display='none'"></div>

<!-- Sidebar toggle (mobile) -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Menú">
  <i data-lucide="menu" style="width:20px;height:20px"></i>
</button>

<div class="layout">
  <!-- ── Sidebar ── -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="nav-brand">
        INNOVA-STEAM
        <span>Plataforma Educativa</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($navItems as $item): ?>
      <a href="<?= $item['href'] ?>"
         class="nav-item <?= $activeNav === $item['key'] ? 'active' : '' ?>">
        <i data-lucide="<?= $item['icon'] ?>" style="width:16px;height:16px;flex-shrink:0"></i>
        <?= $item['label'] ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="user-avatar"><?= $initials ?></div>
        <div class="user-info">
          <div class="user-name"><?= sanitize(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) ?></div>
          <div class="user-role"><?= $rolLabel ?></div>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/logout.php" class="nav-item mt-8" style="color:var(--danger)">
        <i data-lucide="log-out" style="width:16px;height:16px"></i>
        Cerrar sesión
      </a>
    </div>
  </aside>

  <!-- ── Main content ── -->
  <div class="main-content">

    <?php
    // Flash messages
    $flash = getFlash();
    if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> flash-auto-close mb-16">
      <?= sanitize($flash['message']) ?>
    </div>
    <?php endif; ?>
