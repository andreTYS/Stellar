<?php
if (!isset($pageTitle)) $pageTitle = 'INNOVA-STEAM';
if (!isset($activeNav)) $activeNav = '';

$user     = currentUser();
$rol      = currentRole();
$initials = strtoupper(substr($user['nombre'] ?? 'U', 0, 1) . substr($user['apellido'] ?? '', 0, 1));

$navItems = match ($rol) {
    'estudiante' => [
        ['href' => BASE_URL . '/estudiante/index.php',        'icon' => 'home',              'label' => 'Inicio',        'key' => 'inicio'],
        ['href' => BASE_URL . '/estudiante/portafolio.php',   'icon' => 'folder',            'label' => 'Portafolio',    'key' => 'portafolio'],
        ['href' => BASE_URL . '/estudiante/certificados.php', 'icon' => 'award',             'label' => 'Certificados',  'key' => 'certificados'],
    ],
    'practicante' => [
        ['href' => BASE_URL . '/practicante/index.php',       'icon' => 'layout-dashboard',  'label' => 'Dashboard',     'key' => 'dashboard'],
        ['href' => BASE_URL . '/practicante/sesiones.php',    'icon' => 'calendar-days',     'label' => 'Sesiones',      'key' => 'sesiones'],
        ['href' => BASE_URL . '/practicante/informe.php',     'icon' => 'file-text',         'label' => 'Informe',       'key' => 'informe'],
    ],
    'docente' => [
        ['href' => BASE_URL . '/docente/index.php',           'icon' => 'layout-dashboard',  'label' => 'Dashboard',     'key' => 'dashboard'],
        ['href' => BASE_URL . '/docente/estudiantes.php',     'icon' => 'users',             'label' => 'Estudiantes',   'key' => 'estudiantes'],
        ['href' => BASE_URL . '/docente/portafolios.php',     'icon' => 'folder-open',       'label' => 'Portafolios',   'key' => 'portafolios'],
        ['href' => BASE_URL . '/docente/reportes.php',        'icon' => 'bar-chart-2',       'label' => 'Reportes',      'key' => 'reportes'],
    ],
    'admin_colegio' => [
        ['href' => BASE_URL . '/admin_colegio/index.php',         'icon' => 'layout-dashboard', 'label' => 'Dashboard',   'key' => 'dashboard'],
        ['href' => BASE_URL . '/admin_colegio/aulas.php',         'icon' => 'layers',           'label' => 'Aulas',       'key' => 'aulas'],
        ['href' => BASE_URL . '/admin_colegio/modulos.php',       'icon' => 'book-open',        'label' => 'Modulos',     'key' => 'modulos'],
        ['href' => BASE_URL . '/admin_colegio/reporte_dre.php',   'icon' => 'download',         'label' => 'Reporte DRE', 'key' => 'reporte_dre'],
    ],
    'admin' => [
        ['href' => BASE_URL . '/admin/index.php',    'icon' => 'layout-dashboard', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['href' => BASE_URL . '/admin/colegios.php', 'icon' => 'building-2',       'label' => 'Colegios',  'key' => 'colegios'],
        ['href' => BASE_URL . '/admin/usuarios.php', 'icon' => 'users',            'label' => 'Usuarios',  'key' => 'usuarios'],
        ['href' => BASE_URL . '/admin/modulos.php',  'icon' => 'book-open',        'label' => 'Modulos',   'key' => 'modulos'],
        ['href' => BASE_URL . '/admin/reportes.php', 'icon' => 'bar-chart-2',      'label' => 'Reportes',  'key' => 'reportes'],
    ],
    default => [],
};

$rolLabel = match ($rol) {
    'admin'         => 'Administrador',
    'admin_colegio' => 'Director',
    'docente'       => 'Docente',
    'practicante'   => 'Practicante',
    'estudiante'    => 'Estudiante',
    default         => ucfirst($rol),
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= sanitize($pageTitle) ?> — INNOVA-STEAM</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/stellar.css"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/sidebar.css"/>
  <?php if (!empty($extraCss)): foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $css ?>"/>
  <?php endforeach; endif; ?>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <script>document.addEventListener('DOMContentLoaded',()=>{ if(typeof lucide!=='undefined') lucide.createIcons(); });</script>
</head>
<body>

<div id="sidebar-overlay" class="sidebar-overlay" onclick="closeSidebar()"></div>

<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
      <div class="brand-mark">IS</div>
      <div class="brand-text">
        <span class="brand-name">INNOVA-STEAM</span>
        <span class="brand-sub">Plataforma Educativa</span>
      </div>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">
      <span class="nav-section-label">Menu</span>
      <?php foreach ($navItems as $item): ?>
      <a href="<?= $item['href'] ?>"
         class="nav-item <?= $activeNav === $item['key'] ? 'active' : '' ?>">
        <i data-lucide="<?= $item['icon'] ?>"></i>
        <span><?= $item['label'] ?></span>
      </a>
      <?php endforeach; ?>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="user-avatar"><?= $initials ?></div>
        <div class="user-info">
          <p class="user-name"><?= sanitize(trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''))) ?></p>
          <p class="user-role"><?= $rolLabel ?></p>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/logout.php" class="nav-item nav-item-danger">
        <i data-lucide="log-out"></i>
        <span>Cerrar sesion</span>
      </a>
    </div>

  </aside>

  <!-- Main -->
  <div class="main-content" id="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <button class="topbar-toggle" onclick="toggleSidebar()" aria-label="Menu">
        <i data-lucide="menu"></i>
      </button>
      <h1 class="topbar-title"><?= sanitize($pageTitle) ?></h1>
      <div class="topbar-actions">
        <span class="topbar-role-badge"><?= $rolLabel ?></span>
      </div>
    </header>

    <!-- Flash -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> flash-banner">
      <?= sanitize($flash['message']) ?>
    </div>
    <?php endif; ?>

    <!-- Page content -->
    <main class="page-content">
