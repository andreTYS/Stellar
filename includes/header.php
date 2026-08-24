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
        ['href' => BASE_URL . '/stellarscribe/portal.php',    'icon' => 'rocket',            'label' => 'StellarScribe', 'key' => 'stellarscribe'],
        ['href' => BASE_URL . '/mensajes/',                   'icon' => 'mail',              'label' => 'Mensajes',      'key' => 'mensajes'],
    ],
    'practicante' => [
        ['href' => BASE_URL . '/practicante/index.php',       'icon' => 'layout-dashboard',  'label' => 'Dashboard',     'key' => 'dashboard'],
        ['href' => BASE_URL . '/practicante/sesiones.php',    'icon' => 'calendar-days',     'label' => 'Sesiones',      'key' => 'sesiones'],
        ['href' => BASE_URL . '/practicante/informe.php',     'icon' => 'file-text',         'label' => 'Informe',       'key' => 'informe'],
        ['href' => BASE_URL . '/stellarscribe/portal.php',    'icon' => 'rocket',            'label' => 'StellarScribe', 'key' => 'stellarscribe'],
        ['href' => BASE_URL . '/mensajes/',                   'icon' => 'mail',              'label' => 'Mensajes',      'key' => 'mensajes'],
    ],
    'docente' => [
        ['href' => BASE_URL . '/docente/index.php',           'icon' => 'layout-dashboard',  'label' => 'Dashboard',     'key' => 'dashboard'],
        ['href' => BASE_URL . '/docente/estudiantes.php',     'icon' => 'users',             'label' => 'Estudiantes',   'key' => 'estudiantes'],
        ['href' => BASE_URL . '/docente/portafolios.php',     'icon' => 'folder-open',       'label' => 'Portafolios',   'key' => 'portafolios'],
        ['href' => BASE_URL . '/docente/reportes.php',        'icon' => 'bar-chart-2',       'label' => 'Reportes',      'key' => 'reportes'],
        ['href' => BASE_URL . '/stellarscribe/portal.php',    'icon' => 'rocket',            'label' => 'StellarScribe', 'key' => 'stellarscribe'],
        ['href' => BASE_URL . '/mensajes/',                   'icon' => 'mail',              'label' => 'Mensajes',      'key' => 'mensajes'],
    ],
    'admin_colegio' => [
        ['href' => BASE_URL . '/admin_colegio/index.php',         'icon' => 'layout-dashboard', 'label' => 'Dashboard',   'key' => 'dashboard'],
        ['href' => BASE_URL . '/admin_colegio/aulas.php',         'icon' => 'layers',           'label' => 'Aulas',       'key' => 'aulas'],
        ['href' => BASE_URL . '/admin_colegio/modulos.php',       'icon' => 'book-open',        'label' => 'Modulos',     'key' => 'modulos'],
        ['href' => BASE_URL . '/admin_colegio/reporte_dre.php',   'icon' => 'download',         'label' => 'Reporte DRE', 'key' => 'reporte_dre'],
        ['href' => BASE_URL . '/stellarscribe/portal.php',        'icon' => 'rocket',           'label' => 'StellarScribe','key' => 'stellarscribe'],
        ['href' => BASE_URL . '/mensajes/',                        'icon' => 'mail',             'label' => 'Mensajes',    'key' => 'mensajes'],
    ],
    'admin' => [
        ['href' => BASE_URL . '/admin/index.php',    'icon' => 'layout-dashboard', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['href' => BASE_URL . '/admin/colegios.php', 'icon' => 'building-2',       'label' => 'Colegios',  'key' => 'colegios'],
        ['href' => BASE_URL . '/admin/usuarios.php', 'icon' => 'users',            'label' => 'Usuarios',  'key' => 'usuarios'],
        ['href' => BASE_URL . '/admin/modulos.php',  'icon' => 'book-open',        'label' => 'Modulos',   'key' => 'modulos'],
        ['href' => BASE_URL . '/admin/reportes.php', 'icon' => 'bar-chart-2',      'label' => 'Reportes',  'key' => 'reportes'],
        ['href' => BASE_URL . '/stellarscribe/portal.php', 'icon' => 'rocket',          'label' => 'StellarScribe','key' => 'stellarscribe'],
        ['href' => BASE_URL . '/mensajes/',          'icon' => 'mail',             'label' => 'Mensajes',  'key' => 'mensajes'],
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
  <link rel="manifest" href="<?= BASE_URL ?>/manifest.json"/>
  <meta name="theme-color" content="#4361ee"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
  <!-- Prevent flash of wrong theme — must run before stylesheets -->
  <script>(function(){var t=localStorage.getItem('is-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
  <!-- PWA service worker -->
  <script>if('serviceWorker'in navigator)navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
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
  <!-- App JS -->
  <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
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
    <header class="topbar" x-data="{ userOpen: false, notifOpen: false, notifCount: 0, notifs: [] }"
            x-init="
              fetch('<?= BASE_URL ?>/api/notificaciones.php?action=list')
                .then(r=>r.json()).then(d=>{notifCount=d.unread||0;notifs=d.items||[]})
                .catch(()=>{});
            ">
      <button class="topbar-toggle topbar-menu-btn" onclick="toggleSidebar()" aria-label="Abrir menú">
        <i data-lucide="menu" style="width:18px;height:18px"></i>
      </button>
      <h1 class="topbar-title"><?= sanitize($pageTitle) ?></h1>

      <!-- Search button -->
      <button onclick="openSearch()" class="topbar-icon-btn" title="Buscar (Ctrl+K)" aria-label="Buscar" style="margin-left:auto">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </button>

      <!-- Notifications bell -->
      <div style="position:relative" x-data>
        <button @click="notifOpen = !notifOpen" @click.away="notifOpen = false"
                class="topbar-icon-btn" title="Notificaciones" style="position:relative">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span x-show="notifCount > 0"
                style="position:absolute;top:-3px;right:-3px;background:var(--danger);color:#fff;border-radius:99px;font-size:9px;font-weight:700;padding:1px 4px;min-width:16px;text-align:center;line-height:14px"
                x-text="notifCount > 9 ? '9+' : notifCount"></span>
        </button>
        <!-- Notifications dropdown -->
        <div x-show="notifOpen" x-cloak
             style="position:absolute;top:calc(100% + 8px);right:0;width:320px;background:var(--bg-card);border:1px solid var(--bg-border);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);z-index:300;overflow:hidden;display:none">
          <div style="padding:12px 16px;border-bottom:1px solid var(--bg-border);display:flex;align-items:center;justify-content:space-between">
            <span style="font-weight:700;font-size:14px;color:var(--text-primary)">Notificaciones</span>
            <button @click="
              fetch('<?= BASE_URL ?>/api/notificaciones.php?action=mark_read',{method:'POST',body:new URLSearchParams({action:'mark_read'})})
                .then(()=>{notifCount=0;notifs=notifs.map(n=>({...n,leida:1}))})
            " style="font-size:11px;color:var(--accent);background:none;border:none;cursor:pointer;font-weight:600">Marcar todo leído</button>
          </div>
          <div style="max-height:320px;overflow-y:auto">
            <template x-if="notifs.length === 0">
              <div style="padding:24px;text-align:center;font-size:13px;color:var(--text-muted)">Sin notificaciones</div>
            </template>
            <template x-for="n in notifs" :key="n.id">
              <a :href="n.url || '#'"
                 @click="fetch('<?= BASE_URL ?>/api/notificaciones.php?action=mark_read',{method:'POST',body:new URLSearchParams({action:'mark_read',id:n.id})}).then(()=>{if(!n.leida)notifCount=Math.max(0,notifCount-1);n.leida=1})"
                 style="display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-bottom:1px solid var(--bg-border);text-decoration:none;transition:background .1s"
                 :style="!n.leida ? 'background:var(--accent-light)' : ''"
                 onmouseover="this.style.background='var(--bg-hover)'"
                 onmouseout="this.style.background=''">
                <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-light);color:var(--accent);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg>
                </div>
                <div style="flex:1;min-width:0">
                  <div style="font-size:13px;font-weight:600;color:var(--text-primary)" x-text="n.titulo"></div>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px" x-text="n.mensaje"></div>
                </div>
              </a>
            </template>
          </div>
          <div style="padding:10px 16px;border-top:1px solid var(--bg-border)">
            <a href="<?= BASE_URL ?>/mensajes/" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:600">
              Ver todos los mensajes →
            </a>
          </div>
        </div>
      </div>

      <!-- Theme toggle -->
      <button id="theme-toggle" onclick="toggleTheme()" class="topbar-icon-btn" title="Modo oscuro/claro" aria-label="Cambiar tema">
        <svg class="theme-icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg class="theme-icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
      </button>

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
            <a href="<?= BASE_URL ?>/perfil.php"
               style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:13px;color:var(--text-secondary);text-decoration:none;transition:background .15s"
               onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">
              <i data-lucide="user" style="width:15px;height:15px"></i>
              Mi perfil
            </a>
            <a href="<?= BASE_URL ?>/mensajes/"
               style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:13px;color:var(--text-secondary);text-decoration:none;transition:background .15s"
               onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background=''">
              <i data-lucide="mail" style="width:15px;height:15px"></i>
              Mensajes
            </a>
            <div style="height:1px;background:var(--bg-border);margin:4px 0"></div>
            <a href="<?= BASE_URL ?>/logout.php"
               style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:13px;color:var(--danger);text-decoration:none;transition:background .15s"
               onmouseover="this.style.background='var(--danger-light)'" onmouseout="this.style.background=''">
              <i data-lucide="log-out" style="width:15px;height:15px"></i>
              Cerrar sesión
            </a>
          </div>
        </div>
      </div>
    </header>

    <!-- ── Search modal ──────────────────────────────────────── -->
    <div id="search-modal" style="display:none;position:fixed;inset:0;z-index:500;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);align-items:flex-start;justify-content:center;padding-top:80px"
         onclick="if(event.target===this)closeSearch()">
      <div style="width:100%;max-width:560px;background:var(--bg-card);border:1px solid var(--bg-border);border-radius:16px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.35)">
        <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid var(--bg-border)">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input id="search-input" type="text" placeholder="Buscar colegios, usuarios, módulos…"
                 style="flex:1;border:none;background:transparent;font-size:15px;color:var(--text-primary);outline:none"
                 oninput="doSearch(this.value)">
          <kbd style="font-size:11px;color:var(--text-muted);background:var(--bg-muted);border:1px solid var(--bg-border);border-radius:4px;padding:2px 6px">Esc</kbd>
        </div>
        <div id="search-results" style="max-height:380px;overflow-y:auto;padding:8px 0">
          <div style="padding:32px;text-align:center;font-size:13px;color:var(--text-muted)">Escribe para buscar…</div>
        </div>
      </div>
    </div>

    <!-- Flash -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> flash-banner">
      <?= sanitize($flash['message']) ?>
    </div>
    <?php endif; ?>

    <!-- Page content -->
    <main class="page-content">
