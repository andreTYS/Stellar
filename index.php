<?php
require_once __DIR__ . '/includes/config.php';

// If already logged in, go straight to dashboard
if (isLoggedIn()) {
    redirect(dashboardUrl());
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>INNOVA-STEAM — Plataforma Educativa</title>
  <script>(function(){var t=localStorage.getItem('is-theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/stellar.css"/>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-page);
      color: var(--text-primary);
      line-height: 1.6;
    }

    /* ── Navbar ── */
    .lp-nav {
      position: sticky;
      top: 0;
      z-index: 100;
      background: var(--bg-surface);
      border-bottom: 1px solid var(--bg-border);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .lp-nav-inner {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 24px;
      height: 64px;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .lp-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }
    .lp-brand-mark {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--accent);
      color: #fff;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .lp-brand-name {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 17px;
      color: var(--text-primary);
    }
    .lp-nav-spacer { flex: 1; }
    .lp-nav-links {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .lp-nav-link {
      padding: 7px 14px;
      font-size: 14px;
      font-weight: 500;
      color: var(--text-secondary);
      text-decoration: none;
      border-radius: 8px;
      transition: background .15s, color .15s;
    }
    .lp-nav-link:hover {
      background: var(--bg-hover);
      color: var(--text-primary);
    }
    .lp-theme-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      background: transparent;
      border: 1px solid var(--bg-border);
      border-radius: 8px;
      color: var(--text-secondary);
      cursor: pointer;
      transition: background .15s, color .15s;
      margin-left: 4px;
    }
    .lp-theme-btn:hover { background: var(--bg-hover); color: var(--text-primary); }

    /* ── Hero ── */
    .lp-hero {
      max-width: 1100px;
      margin: 0 auto;
      padding: 96px 24px 80px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 64px;
      align-items: center;
    }
    .lp-hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      background: var(--accent-light);
      color: var(--accent);
      border-radius: 99px;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: .03em;
      margin-bottom: 20px;
    }
    .lp-hero-title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(32px, 5vw, 52px);
      font-weight: 800;
      line-height: 1.1;
      letter-spacing: -.02em;
      color: var(--text-primary);
      margin-bottom: 20px;
    }
    .lp-hero-title span {
      color: var(--accent);
    }
    .lp-hero-subtitle {
      font-size: 17px;
      color: var(--text-secondary);
      line-height: 1.65;
      margin-bottom: 36px;
      max-width: 480px;
    }
    .lp-hero-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
    }
    .lp-btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 13px 28px;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      transition: background .15s, transform .1s, box-shadow .15s;
      box-shadow: 0 4px 16px rgba(67,97,238,.35);
    }
    .lp-btn-primary:hover {
      background: var(--accent-hover);
      transform: translateY(-1px);
      box-shadow: 0 6px 24px rgba(67,97,238,.45);
    }
    .lp-btn-ghost {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 13px 24px;
      background: transparent;
      color: var(--text-secondary);
      border: 1px solid var(--bg-border);
      border-radius: 12px;
      font-size: 15px;
      font-weight: 500;
      text-decoration: none;
      cursor: pointer;
      transition: background .15s, color .15s, border-color .15s;
    }
    .lp-btn-ghost:hover {
      background: var(--bg-hover);
      color: var(--text-primary);
      border-color: var(--text-muted);
    }

    /* Hero visual */
    .lp-hero-visual {
      position: relative;
    }
    .lp-hero-card {
      background: var(--bg-card);
      border: 1px solid var(--bg-border);
      border-radius: 20px;
      padding: 28px;
      box-shadow: var(--shadow-lg);
    }
    .lp-hero-card-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
    }
    .lp-mini-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--accent);
      color: #fff;
      font-weight: 700;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .lp-card-stat-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-top: 20px;
    }
    .lp-card-stat {
      background: var(--bg-muted);
      border-radius: 12px;
      padding: 14px 16px;
    }
    .lp-card-stat-value {
      font-family: 'Syne', sans-serif;
      font-size: 24px;
      font-weight: 800;
      color: var(--accent);
    }
    .lp-card-stat-label {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 2px;
    }
    .lp-progress-list { display: flex; flex-direction: column; gap: 10px; }
    .lp-progress-item {}
    .lp-progress-label {
      display: flex;
      justify-content: space-between;
      font-size: 13px;
      color: var(--text-secondary);
      margin-bottom: 5px;
    }
    .lp-progress-track {
      height: 6px;
      background: var(--bg-muted);
      border-radius: 99px;
      overflow: hidden;
    }
    .lp-progress-fill {
      height: 100%;
      border-radius: 99px;
      background: var(--accent);
      transition: width 1s ease;
    }
    /* Floating badge */
    .lp-float-badge {
      position: absolute;
      bottom: -18px;
      left: -18px;
      background: var(--bg-card);
      border: 1px solid var(--bg-border);
      border-radius: 14px;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: var(--shadow);
      font-size: 13px;
      font-weight: 600;
      color: var(--text-primary);
    }
    .lp-float-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: rgba(62,207,142,.15);
      color: var(--green);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* ── Stats bar ── */
    .lp-statsbar {
      background: var(--bg-card);
      border-top: 1px solid var(--bg-border);
      border-bottom: 1px solid var(--bg-border);
    }
    .lp-statsbar-inner {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 24px;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      divide-x: 1px solid var(--bg-border);
    }
    .lp-stat {
      padding: 32px 24px;
      text-align: center;
      border-right: 1px solid var(--bg-border);
    }
    .lp-stat:last-child { border-right: none; }
    .lp-stat-value {
      font-family: 'Syne', sans-serif;
      font-size: 36px;
      font-weight: 800;
      color: var(--accent);
      line-height: 1;
    }
    .lp-stat-label {
      font-size: 13px;
      color: var(--text-muted);
      margin-top: 6px;
    }

    /* ── Sections ── */
    .lp-section {
      max-width: 1100px;
      margin: 0 auto;
      padding: 80px 24px;
    }
    .lp-section-header {
      text-align: center;
      margin-bottom: 52px;
    }
    .lp-section-label {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 12px;
    }
    .lp-section-title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(24px, 3.5vw, 36px);
      font-weight: 800;
      letter-spacing: -.02em;
      color: var(--text-primary);
      margin-bottom: 14px;
    }
    .lp-section-subtitle {
      font-size: 16px;
      color: var(--text-muted);
      max-width: 520px;
      margin: 0 auto;
    }

    /* ── Features grid ── */
    .lp-features {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }
    .lp-feature-card {
      background: var(--bg-card);
      border: 1px solid var(--bg-border);
      border-radius: 16px;
      padding: 28px;
      transition: transform .2s, box-shadow .2s, border-color .2s;
    }
    .lp-feature-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow);
      border-color: var(--accent-border);
    }
    .lp-feature-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
    }
    .lp-feature-title {
      font-family: 'Syne', sans-serif;
      font-size: 16px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 8px;
    }
    .lp-feature-desc {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.6;
    }

    /* ── Roles section ── */
    .lp-roles {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 16px;
    }
    .lp-role-card {
      background: var(--bg-card);
      border: 1px solid var(--bg-border);
      border-radius: 14px;
      padding: 20px 16px;
      text-align: center;
      transition: transform .2s, border-color .2s;
    }
    .lp-role-card:hover {
      transform: translateY(-2px);
      border-color: var(--accent-border);
    }
    .lp-role-icon {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 12px;
    }
    .lp-role-name {
      font-size: 13px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 4px;
    }
    .lp-role-desc {
      font-size: 11px;
      color: var(--text-muted);
      line-height: 1.4;
    }

    /* ── CTA section ── */
    .lp-cta {
      background: var(--accent);
      border-radius: 24px;
      padding: 64px 48px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .lp-cta::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse at 20% 50%, rgba(255,255,255,.12) 0%, transparent 60%),
                  radial-gradient(ellipse at 80% 20%, rgba(255,255,255,.08) 0%, transparent 50%);
    }
    .lp-cta-content { position: relative; z-index: 1; }
    .lp-cta-title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(24px, 4vw, 36px);
      font-weight: 800;
      color: #fff;
      margin-bottom: 12px;
      letter-spacing: -.02em;
    }
    .lp-cta-sub {
      font-size: 16px;
      color: rgba(255,255,255,.8);
      margin-bottom: 32px;
    }
    .lp-btn-white {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 13px 32px;
      background: #fff;
      color: var(--accent);
      border: none;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
      transition: transform .1s, box-shadow .15s;
      box-shadow: 0 4px 20px rgba(0,0,0,.2);
    }
    .lp-btn-white:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 32px rgba(0,0,0,.25);
    }

    /* ── Footer ── */
    .lp-footer {
      border-top: 1px solid var(--bg-border);
      background: var(--bg-surface);
    }
    .lp-footer-inner {
      max-width: 1100px;
      margin: 0 auto;
      padding: 40px 24px 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }
    .lp-footer-copy {
      font-size: 13px;
      color: var(--text-muted);
    }
    .lp-footer-links {
      display: flex;
      gap: 20px;
    }
    .lp-footer-link {
      font-size: 13px;
      color: var(--text-muted);
      text-decoration: none;
      transition: color .15s;
    }
    .lp-footer-link:hover { color: var(--text-primary); }

    /* ── Theme icon ── */
    html[data-theme="dark"] .theme-icon-moon { display: none !important; }
    html[data-theme="dark"] .theme-icon-sun  { display: block !important; }
    html:not([data-theme="dark"]) .theme-icon-sun  { display: none !important; }
    html:not([data-theme="dark"]) .theme-icon-moon { display: block !important; }

    /* ── Responsive ── */
    @media (max-width: 900px) {
      .lp-hero { grid-template-columns: 1fr; gap: 48px; padding-top: 64px; }
      .lp-hero-visual { display: none; }
      .lp-features { grid-template-columns: 1fr 1fr; }
      .lp-statsbar-inner { grid-template-columns: 1fr 1fr; }
      .lp-stat { border-right: none; border-bottom: 1px solid var(--bg-border); }
      .lp-stat:nth-child(odd) { border-right: 1px solid var(--bg-border); }
      .lp-stat:last-child { border-bottom: none; }
      .lp-roles { grid-template-columns: repeat(3, 1fr); }
      .lp-nav-links .lp-nav-link { display: none; }
    }
    @media (max-width: 600px) {
      .lp-features { grid-template-columns: 1fr; }
      .lp-roles { grid-template-columns: 1fr 1fr; }
      .lp-cta { padding: 40px 24px; }
      .lp-section { padding: 56px 16px; }
    }
  </style>
</head>
<body>

<!-- ── Navbar ───────────────────────────────────────────────── -->
<nav class="lp-nav">
  <div class="lp-nav-inner">
    <a class="lp-brand" href="#">
      <div class="lp-brand-mark">IS</div>
      <span class="lp-brand-name">INNOVA-STEAM</span>
    </a>
    <div class="lp-nav-spacer"></div>
    <div class="lp-nav-links">
      <a href="#caracteristicas" class="lp-nav-link">Características</a>
      <a href="#roles" class="lp-nav-link">Roles</a>
      <a href="#contacto" class="lp-nav-link">Contacto</a>
    </div>
    <button class="lp-theme-btn" onclick="toggleTheme()" title="Cambiar tema" aria-label="Cambiar tema">
      <svg class="theme-icon-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
      <svg class="theme-icon-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
    </button>
    <a href="<?= BASE_URL ?>/login.php" class="lp-btn-primary" style="margin-left:8px;padding:9px 20px;font-size:14px">
      Ingresar
    </a>
  </div>
</nav>

<!-- ── Hero ─────────────────────────────────────────────────── -->
<section class="lp-hero">
  <div class="lp-hero-text">
    <div class="lp-hero-badge">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
      Plataforma Educativa STEAM · Moquegua
    </div>
    <h1 class="lp-hero-title">
      Educación <span>STEAM</span><br/>para cada colegio
    </h1>
    <p class="lp-hero-subtitle">
      INNOVA-STEAM conecta colegios, docentes, practicantes y estudiantes en una sola plataforma. Gestión de aulas, portafolios, módulos y certificados en tiempo real.
    </p>
    <div class="lp-hero-actions">
      <a href="<?= BASE_URL ?>/login.php" class="lp-btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Ingresar a la plataforma
      </a>
      <a href="#caracteristicas" class="lp-btn-ghost">
        Conocer más
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </a>
    </div>
  </div>

  <!-- Hero visual card -->
  <div class="lp-hero-visual">
    <div class="lp-hero-card">
      <div class="lp-hero-card-header">
        <div class="lp-mini-avatar">ES</div>
        <div>
          <div style="font-size:13px;font-weight:600;color:var(--text-primary)">Estudiante · Aula 3B</div>
          <div style="font-size:12px;color:var(--text-muted)">I.E. Mariscal Nieto</div>
        </div>
        <span class="badge" style="margin-left:auto;background:rgba(62,207,142,.12);color:var(--green);font-size:11px">Activo</span>
      </div>

      <div class="lp-progress-list">
        <div class="lp-progress-item">
          <div class="lp-progress-label"><span>Módulo 1 — Pensamiento Computacional</span><span style="color:var(--green);font-weight:600">100%</span></div>
          <div class="lp-progress-track"><div class="lp-progress-fill" style="width:100%;background:var(--green)"></div></div>
        </div>
        <div class="lp-progress-item">
          <div class="lp-progress-label"><span>Módulo 2 — Robótica Básica</span><span style="color:var(--accent);font-weight:600">72%</span></div>
          <div class="lp-progress-track"><div class="lp-progress-fill" style="width:72%"></div></div>
        </div>
        <div class="lp-progress-item">
          <div class="lp-progress-label"><span>Módulo 3 — Diseño y Prototipado</span><span style="color:var(--text-muted);font-weight:600">30%</span></div>
          <div class="lp-progress-track"><div class="lp-progress-fill" style="width:30%;background:var(--purple)"></div></div>
        </div>
      </div>

      <div class="lp-card-stat-row">
        <div class="lp-card-stat">
          <div class="lp-card-stat-value">8</div>
          <div class="lp-card-stat-label">Entregables subidos</div>
        </div>
        <div class="lp-card-stat">
          <div class="lp-card-stat-value">2</div>
          <div class="lp-card-stat-label">Certificados ganados</div>
        </div>
      </div>
    </div>

    <div class="lp-float-badge">
      <div class="lp-float-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <div>
        <div style="font-size:12px;color:var(--text-muted);font-weight:500">Módulo completado</div>
        <div>Pensamiento Computacional</div>
      </div>
    </div>
  </div>
</section>

<!-- ── Stats bar ─────────────────────────────────────────────── -->
<div class="lp-statsbar">
  <div class="lp-statsbar-inner">
    <div class="lp-stat">
      <div class="lp-stat-value">12</div>
      <div class="lp-stat-label">Colegios activos</div>
    </div>
    <div class="lp-stat">
      <div class="lp-stat-value">347</div>
      <div class="lp-stat-label">Estudiantes registrados</div>
    </div>
    <div class="lp-stat">
      <div class="lp-stat-value">1,204</div>
      <div class="lp-stat-label">Módulos completados</div>
    </div>
    <div class="lp-stat">
      <div class="lp-stat-value">98%</div>
      <div class="lp-stat-label">Satisfacción docente</div>
    </div>
  </div>
</div>

<!-- ── Características ───────────────────────────────────────── -->
<section id="caracteristicas" class="lp-section">
  <div class="lp-section-header">
    <div class="lp-section-label">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      Características
    </div>
    <h2 class="lp-section-title">Todo lo que necesita tu institución</h2>
    <p class="lp-section-subtitle">Una plataforma integrada diseñada para el contexto educativo peruano y la metodología STEAM.</p>
  </div>

  <div class="lp-features">
    <div class="lp-feature-card">
      <div class="lp-feature-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
      </div>
      <div class="lp-feature-title">Módulos STEAM</div>
      <div class="lp-feature-desc">Contenidos estructurados por niveles con videos, cuestionarios y entregables. El estudiante avanza a su ritmo y registra su progreso automáticamente.</div>
    </div>
    <div class="lp-feature-card">
      <div class="lp-feature-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
      </div>
      <div class="lp-feature-title">Portafolio Digital</div>
      <div class="lp-feature-desc">Cada estudiante construye su portafolio con entregables por sesión. Docentes y practicantes lo revisan desde su panel en tiempo real.</div>
    </div>
    <div class="lp-feature-card">
      <div class="lp-feature-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
      </div>
      <div class="lp-feature-title">Certificados Automáticos</div>
      <div class="lp-feature-desc">Al completar un módulo el sistema genera y entrega el certificado con nombre, colegio y fecha. Descargable en PDF en cualquier momento.</div>
    </div>
    <div class="lp-feature-card">
      <div class="lp-feature-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div class="lp-feature-title">Gestión de Aulas</div>
      <div class="lp-feature-desc">El director del colegio crea aulas, asigna docentes y practicantes, y supervisa el avance de todos los grupos desde un solo panel.</div>
    </div>
    <div class="lp-feature-card">
      <div class="lp-feature-icon" style="background:rgba(251,99,64,.12);color:var(--coral)">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      </div>
      <div class="lp-feature-title">Reportes y Estadísticas</div>
      <div class="lp-feature-desc">Métricas de asistencia, avance por módulo y comparativas entre colegios. Exportables para informes a la DRE con un solo clic.</div>
    </div>
    <div class="lp-feature-card">
      <div class="lp-feature-icon" style="background:rgba(34,211,238,.12);color:var(--teal)">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <div class="lp-feature-title">Roles y Accesos</div>
      <div class="lp-feature-desc">Sistema de 5 roles con permisos diferenciados: Administrador, Director, Docente, Practicante y Estudiante. Cada uno ve solo lo que le corresponde.</div>
    </div>
  </div>
</section>

<!-- ── Roles ─────────────────────────────────────────────────── -->
<div style="background:var(--bg-card);border-top:1px solid var(--bg-border);border-bottom:1px solid var(--bg-border)">
  <section id="roles" class="lp-section" style="padding-top:64px;padding-bottom:64px">
    <div class="lp-section-header" style="margin-bottom:36px">
      <div class="lp-section-label">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Roles
      </div>
      <h2 class="lp-section-title">Una plataforma, cinco roles</h2>
      <p class="lp-section-subtitle">Cada actor del ecosistema educativo tiene su propio espacio y herramientas.</p>
    </div>
    <div class="lp-roles">
      <div class="lp-role-card">
        <div class="lp-role-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </div>
        <div class="lp-role-name">Administrador</div>
        <div class="lp-role-desc">Visión global, gestión de colegios y usuarios</div>
      </div>
      <div class="lp-role-card">
        <div class="lp-role-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        </div>
        <div class="lp-role-name">Director</div>
        <div class="lp-role-desc">Aulas, módulos y reportes DRE</div>
      </div>
      <div class="lp-role-card">
        <div class="lp-role-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="lp-role-name">Docente</div>
        <div class="lp-role-desc">Estudiantes, portafolios y reportes</div>
      </div>
      <div class="lp-role-card">
        <div class="lp-role-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        </div>
        <div class="lp-role-name">Practicante</div>
        <div class="lp-role-desc">Sesiones, asistencia e informes</div>
      </div>
      <div class="lp-role-card">
        <div class="lp-role-icon" style="background:rgba(34,211,238,.12);color:var(--teal)">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        </div>
        <div class="lp-role-name">Estudiante</div>
        <div class="lp-role-desc">Módulos, portafolio y certificados</div>
      </div>
    </div>
  </section>
</div>

<!-- ── CTA ───────────────────────────────────────────────────── -->
<section class="lp-section" id="contacto">
  <div class="lp-cta">
    <div class="lp-cta-content">
      <h2 class="lp-cta-title">¿Listo para empezar?</h2>
      <p class="lp-cta-sub">Accede a tu cuenta y lleva la educación STEAM al siguiente nivel.</p>
      <a href="<?= BASE_URL ?>/login.php" class="lp-btn-white">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Ingresar a la plataforma
      </a>
    </div>
  </div>
</section>

<!-- ── Footer ────────────────────────────────────────────────── -->
<footer class="lp-footer">
  <div class="lp-footer-inner">
    <div style="display:flex;align-items:center;gap:10px">
      <div class="lp-brand-mark" style="width:28px;height:28px;font-size:11px">IS</div>
      <span class="lp-footer-copy">© 2026 INNOVA-STEAM · Moquegua, Perú</span>
    </div>
    <div class="lp-footer-links">
      <a href="<?= BASE_URL ?>/login.php" class="lp-footer-link">Ingresar</a>
    </div>
  </div>
</footer>

<script>
function toggleTheme() {
  const curr = document.documentElement.getAttribute('data-theme') || 'light';
  const next = curr === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('is-theme', next);
}
document.addEventListener('DOMContentLoaded', () => {
  if (typeof lucide !== 'undefined') lucide.createIcons();
  // Animate progress bars
  document.querySelectorAll('.lp-progress-fill').forEach(b => {
    const w = b.style.width;
    b.style.width = '0';
    requestAnimationFrame(() => setTimeout(() => { b.style.width = w; }, 100));
  });
});
</script>
</body>
</html>
