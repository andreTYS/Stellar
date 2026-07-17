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
  <style>[x-cloak] { display: none !important; }</style>
  <!-- Alpine.js v3 -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <!-- Lucide icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
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
    <header class="topbar" x-data="{ userOpen: false }">
      <button class="topbar-toggle" onclick="toggleSidebar()" aria-label="Menu">
        <i data-lucide="menu"></i>
      </button>
      <h1 class="topbar-title"><?= sanitize($pageTitle) ?></h1>
      <div class="topbar-actions" style="position:relative">
        <!-- User avatar + dropdown -->
        <button @click="userOpen = !userOpen" @click.away="userOpen = false"
                class="topbar-user-btn" style="display:flex;align-items:center;gap:8px;background:transparent;border:none;cursor:pointer;padding:4px 8px;border-radius:8px;transition:background .15s"
                :style="userOpen ? 'background:var(--bg-hover)' : ''">
          <div class="user-avatar" style="width:32px;height:32px;font-size:13px"><?= $initials ?></div>
          <span style="font-size:13px;font-weight:600;color:var(--text-primary);max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($user['nombre'] ?? '') ?></span>
          <i data-lucide="chevron-down" style="width:14px;height:14px;color:var(--text-muted);transition:transform .2s" :style="userOpen ? 'transform:rotate(180deg)' : ''"></i>
        </button>
        <!-- Dropdown -->
        <div x-show="userOpen" x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="position:absolute;top:calc(100% + 8px);right:0;min-width:200px;background:var(--bg-card);border:1px solid var(--bg-border);border-radius:12px;box-shadow:0 8px 32px rgba(26,31,54,.12);z-index:200;overflow:hidden;display:none"
             x-cloak>
          <div style="padding:12px 16px;border-bottom:1px solid var(--bg-border)">
            <div style="font-weight:700;font-size:14px;color:var(--text-primary)"><?= sanitize(trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''))) ?></div>
            <div style="font-size:12px;color:var(--text-muted)"><?= $rolLabel ?></div>
          </div>
          <div style="padding:6px">
            <a href="<?= BASE_URL ?>/logout.php" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:13px;color:var(--danger);text-decoration:none;transition:background .15s" onmouseover="this.style.background='var(--danger-light)'" onmouseout="this.style.background=''">
              <i data-lucide="log-out" style="width:15px;height:15px"></i>
              Cerrar sesión
            </a>
          </div>
        </div>
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
