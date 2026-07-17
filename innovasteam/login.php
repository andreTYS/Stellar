<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Already logged in? Redirect
if (isLoggedIn()) redirect(dashboardUrl());

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrCodigo = trim($_POST['email'] ?? '');
    $password      = trim($_POST['password'] ?? '');

    if (empty($emailOrCodigo) || empty($password)) {
        $error = 'Completa todos los campos.';
    } else {
        $user = loginUser($emailOrCodigo, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['rol']     = $user['rol'];
            $_SESSION['usuario'] = $user;
            redirect(dashboardUrl());
        } else {
            $error = 'Credenciales incorrectas. Verifica tu email/código y contraseña.';
        }
    }
}

$queryError = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Iniciar sesión — INNOVA-STEAM</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet"/>
  <style>
    /* ── Design tokens ─────────────────────────────────────── */
    :root {
      --accent:     #4361ee;
      --accent-h:   #3451d1;
      --text-dark:  #1a1f36;
      --text-muted: #8898aa;
      --border:     #e4e8f0;
      --bg-input:   #ffffff;
      --bg-right:   #f0f2f8;
    }

    /* ── Reset ─────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      height: 100%;
      overflow: hidden;
      font-family: 'Inter', system-ui, sans-serif;
      -webkit-font-smoothing: antialiased;
    }

    /* ── Animated background ────────────────────────────────── */
    @keyframes gradientShift {
      0%   { background-position: 0% 50%; }
      50%  { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    @keyframes floatOrb1 {
      0%, 100% { transform: translate(0, 0) scale(1); }
      33%       { transform: translate(40px, -60px) scale(1.08); }
      66%       { transform: translate(-30px, 40px) scale(0.94); }
    }

    @keyframes floatOrb2 {
      0%, 100% { transform: translate(0, 0) scale(1); }
      40%       { transform: translate(-50px, 30px) scale(1.12); }
      75%       { transform: translate(30px, -40px) scale(0.92); }
    }

    @keyframes floatOrb3 {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50%       { transform: translate(20px, 50px) scale(1.06); }
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes shimmerBorder {
      0%   { background-position: 0% 50%; }
      100% { background-position: 200% 50%; }
    }

    /* ── Split layout ──────────────────────────────────────── */
    .login-split {
      display: flex;
      height: 100vh;
      width: 100%;
    }

    /* ──────────────────────────────────────────────────────── */
    /* LEFT PANEL                                               */
    /* ──────────────────────────────────────────────────────── */
    .login-left {
      width: 48%;
      flex-shrink: 0;
      background: #0d1024;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 52px 56px 44px;
      position: relative;
      overflow: hidden;
    }

    /* Animated gradient base layer */
    .login-left::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, #0d1024 0%, #141830 40%, #1a2050 70%, #0d1024 100%);
      background-size: 300% 300%;
      animation: gradientShift 12s ease infinite;
      z-index: 0;
    }

    /* Orb 1 — vivid blue */
    .orb-1 {
      position: absolute;
      top: -120px;
      left: -80px;
      width: 480px;
      height: 480px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(67,97,238,.42) 0%, rgba(67,97,238,.10) 50%, transparent 75%);
      animation: floatOrb1 14s ease-in-out infinite;
      z-index: 1;
      pointer-events: none;
    }

    /* Orb 2 — purple */
    .orb-2 {
      position: absolute;
      bottom: -80px;
      right: -60px;
      width: 420px;
      height: 420px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(139,92,246,.38) 0%, rgba(139,92,246,.08) 50%, transparent 72%);
      animation: floatOrb2 18s ease-in-out infinite;
      z-index: 1;
      pointer-events: none;
    }

    /* Orb 3 — teal accent */
    .orb-3 {
      position: absolute;
      top: 45%;
      left: 55%;
      width: 260px;
      height: 260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(6,182,212,.22) 0%, transparent 70%);
      animation: floatOrb3 10s ease-in-out infinite;
      z-index: 1;
      pointer-events: none;
    }

    /* Subtle grid pattern overlay */
    .left-grid {
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
      background-size: 40px 40px;
      z-index: 2;
      pointer-events: none;
    }

    /* ── Brand top row ─────────────────────────────────────── */
    .left-brand {
      display: flex;
      align-items: center;
      gap: 14px;
      position: relative;
      z-index: 10;
    }

    .logo-mark {
      width: 46px;
      height: 46px;
      background: linear-gradient(135deg, #4361ee 0%, #6366f1 100%);
      border-radius: 13px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 16px;
      color: #ffffff;
      letter-spacing: -0.02em;
      flex-shrink: 0;
      box-shadow: 0 4px 20px rgba(67,97,238,.45);
    }

    .brand-name {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 18px;
      color: #ffffff;
      letter-spacing: 0.02em;
    }

    /* ── Center content ────────────────────────────────────── */
    .left-center {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
      z-index: 10;
      padding: 40px 0;
    }

    .left-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(67,97,238,.18);
      border: 1px solid rgba(67,97,238,.35);
      border-radius: 99px;
      padding: 5px 14px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: #818cf8;
      margin-bottom: 22px;
      width: fit-content;
    }

    .left-eyebrow-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #4361ee;
      animation: pulse-dot 2s ease infinite;
    }

    @keyframes pulse-dot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50%       { opacity: .5; transform: scale(.8); }
    }

    .left-heading {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 40px;
      line-height: 1.12;
      color: #ffffff;
      margin-bottom: 16px;
      letter-spacing: -0.03em;
      animation: fadeInUp .7s ease both;
    }

    .left-heading span {
      background: linear-gradient(135deg, #818cf8, #38bdf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .left-subtitle {
      font-size: 14.5px;
      color: #7888a0;
      line-height: 1.65;
      max-width: 340px;
      margin-bottom: 40px;
      animation: fadeInUp .7s .1s ease both;
    }

    /* ── Stats row ─────────────────────────────────────────── */
    .stats-row {
      display: flex;
      gap: 28px;
      margin-bottom: 40px;
      animation: fadeInUp .7s .18s ease both;
    }

    .stat-pill {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .stat-pill-value {
      font-family: 'Syne', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: #ffffff;
      line-height: 1;
    }

    .stat-pill-label {
      font-size: 11px;
      color: #4e6080;
      letter-spacing: .04em;
    }

    .stat-divider {
      width: 1px;
      background: rgba(255,255,255,.07);
      align-self: stretch;
    }

    /* ── Feature list ──────────────────────────────────────── */
    .feature-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
      animation: fadeInUp .7s .26s ease both;
    }

    .feature-row {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .feature-icon-wrap {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: rgba(67,97,238,.14);
      border: 1px solid rgba(67,97,238,.28);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .feature-icon-wrap svg {
      width: 16px;
      height: 16px;
      stroke: #818cf8;
      stroke-width: 2;
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .feature-text {
      font-size: 13.5px;
      color: #a0b0c4;
      line-height: 1.4;
    }

    /* ── Left footer ───────────────────────────────────────── */
    .left-footer {
      font-size: 12px;
      color: #2a3550;
      position: relative;
      z-index: 10;
    }

    /* ──────────────────────────────────────────────────────── */
    /* RIGHT PANEL                                              */
    /* ──────────────────────────────────────────────────────── */
    .login-right {
      flex: 1;
      background: var(--bg-right);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 32px;
      overflow-y: auto;
      position: relative;
    }

    /* Subtle right-panel background decoration */
    .login-right::before {
      content: '';
      position: absolute;
      top: -200px;
      right: -200px;
      width: 600px;
      height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(67,97,238,.06) 0%, transparent 65%);
      pointer-events: none;
    }

    /* ── Form card — glassmorphism ──────────────────────────── */
    .form-card {
      width: 100%;
      max-width: 428px;
      background: rgba(255,255,255,.88);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 20px;
      border: 1px solid rgba(255,255,255,.9);
      padding: 44px 40px;
      box-shadow:
        0 8px 40px rgba(26,31,54,.10),
        0 2px 8px rgba(26,31,54,.05),
        inset 0 1px 0 rgba(255,255,255,.8);
      position: relative;
      animation: fadeInUp .6s .05s ease both;
    }

    /* Animated gradient border on the card */
    .form-card::before {
      content: '';
      position: absolute;
      inset: -1px;
      border-radius: 21px;
      background: linear-gradient(90deg, #4361ee, #8b5cf6, #38bdf8, #4361ee);
      background-size: 200% 100%;
      opacity: 0;
      transition: opacity .3s;
      z-index: -1;
      animation: shimmerBorder 3s linear infinite;
    }

    .form-card:focus-within::before {
      opacity: .45;
    }

    .form-heading {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 26px;
      color: var(--text-dark);
      margin-bottom: 6px;
      letter-spacing: -0.02em;
    }

    .form-subheading {
      font-size: 14px;
      color: var(--text-muted);
      margin-bottom: 30px;
    }

    /* ── Alerts ────────────────────────────────────────────── */
    .login-alert {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 12px 14px;
      border-radius: 10px;
      font-size: 13.5px;
      margin-bottom: 20px;
      line-height: 1.5;
    }

    .login-alert svg {
      width: 16px;
      height: 16px;
      flex-shrink: 0;
      margin-top: 1px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .login-alert-error {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #b91c1c;
    }

    .login-alert-warning {
      background: #fffbeb;
      border: 1px solid #fde68a;
      color: #92400e;
    }

    /* ── Field groups ──────────────────────────────────────── */
    .field-group {
      margin-bottom: 20px;
    }

    .field-label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 7px;
    }

    .field-input-wrap {
      position: relative;
    }

    .field-input {
      width: 100%;
      background: rgba(255,255,255,.7);
      border: 1.5px solid #dde3ef;
      border-radius: 11px;
      padding: 12px 14px;
      font-size: 14px;
      color: var(--text-dark);
      font-family: 'Inter', sans-serif;
      transition: border-color .2s, box-shadow .2s, background .2s;
      outline: none;
      appearance: none;
    }

    .field-input::placeholder {
      color: #bac3d0;
    }

    .field-input:focus {
      border-color: var(--accent);
      background: rgba(255,255,255,.95);
      box-shadow: 0 0 0 3px rgba(67,97,238,.14), 0 2px 8px rgba(67,97,238,.08);
    }

    .field-input.has-toggle {
      padding-right: 44px;
    }

    /* ── Password toggle ───────────────────────────────────── */
    .pwd-toggle {
      position: absolute;
      right: 0;
      top: 0;
      height: 100%;
      width: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      border: none;
      cursor: pointer;
      color: #bac3d0;
      transition: color .15s;
      border-radius: 0 11px 11px 0;
    }

    .pwd-toggle:hover {
      color: var(--text-muted);
    }

    .pwd-toggle svg {
      width: 16px;
      height: 16px;
      stroke: currentColor;
      stroke-width: 2;
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
      pointer-events: none;
    }

    /* ── Submit button ─────────────────────────────────────── */
    .btn-login {
      width: 100%;
      padding: 13px 20px;
      background: linear-gradient(135deg, #4361ee 0%, #6366f1 100%);
      color: #ffffff;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 15px;
      letter-spacing: 0.01em;
      border: none;
      border-radius: 11px;
      cursor: pointer;
      transition: opacity .15s, transform .1s, box-shadow .2s;
      margin-top: 4px;
      box-shadow: 0 4px 20px rgba(67,97,238,.30);
    }

    .btn-login:hover {
      opacity: .92;
      box-shadow: 0 6px 28px rgba(67,97,238,.42);
    }

    .btn-login:active {
      transform: scale(.98);
    }

    /* ── Demo credentials ──────────────────────────────────── */
    .demo-section {
      margin-top: 28px;
    }

    .demo-separator {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 14px;
    }

    .demo-separator-line {
      flex: 1;
      height: 1px;
      background: #dde3ef;
    }

    .demo-separator-text {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .07em;
      text-transform: uppercase;
      color: var(--text-muted);
      white-space: nowrap;
    }

    .demo-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }

    .demo-btn {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 2px;
      padding: 10px 12px;
      background: rgba(255,255,255,.6);
      border: 1.5px solid #dde3ef;
      border-radius: 10px;
      cursor: pointer;
      transition: border-color .15s, background .15s, box-shadow .15s;
      text-align: left;
    }

    .demo-btn:hover {
      background: rgba(255,255,255,.95);
      border-color: var(--accent);
      box-shadow: 0 2px 10px rgba(67,97,238,.12);
    }

    .demo-btn-role {
      font-family: 'Syne', sans-serif;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-dark);
    }

    .demo-btn-cred {
      font-size: 11px;
      color: var(--text-muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      width: 100%;
    }

    /* ── Responsive: mobile stacks ─────────────────────────── */
    @media (max-width: 768px) {
      html, body { overflow: auto; }

      .login-split {
        flex-direction: column;
        height: auto;
        min-height: 100vh;
      }

      .login-left {
        width: 100%;
        padding: 28px 24px 28px;
        min-height: auto;
      }

      .left-center,
      .left-footer { display: none; }

      .login-right {
        flex: none;
        padding: 32px 20px 52px;
      }

      .form-card {
        padding: 32px 24px;
        background: rgba(255,255,255,.96);
      }
    }
  </style>
</head>
<body>

<div class="login-split">

  <!-- ── LEFT PANEL ───────────────────────────────────────────── -->
  <aside class="login-left">
    <!-- Animated orbs -->
    <div class="orb-1"></div>
    <div class="orb-2"></div>
    <div class="orb-3"></div>
    <!-- Grid overlay -->
    <div class="left-grid"></div>

    <!-- Top: Brand -->
    <div class="left-brand">
      <div class="logo-mark">IS</div>
      <div class="brand-name">INNOVA-STEAM</div>
    </div>

    <!-- Middle: Heading + Features -->
    <div class="left-center">
      <div class="left-eyebrow">
        <span class="left-eyebrow-dot"></span>
        Plataforma Educativa
      </div>
      <h2 class="left-heading">Plataforma<br>Educativa <span>STEAM</span></h2>
      <p class="left-subtitle">Aprende ciencia, tecnología, ingeniería, arte y matemáticas con seguimiento en tiempo real.</p>

      <!-- Stats row -->
      <div class="stats-row">
        <div class="stat-pill">
          <span class="stat-pill-value">5</span>
          <span class="stat-pill-label">cursos</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-pill">
          <span class="stat-pill-value">15</span>
          <span class="stat-pill-label">módulos</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-pill">
          <span class="stat-pill-value">5</span>
          <span class="stat-pill-label">roles</span>
        </div>
      </div>

      <div class="feature-list">

        <div class="feature-row">
          <div class="feature-icon-wrap">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <span class="feature-text">5 cursos STEAM integrados con contenido interactivo</span>
        </div>

        <div class="feature-row">
          <div class="feature-icon-wrap">
            <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          </div>
          <span class="feature-text">Seguimiento de progreso en tiempo real</span>
        </div>

        <div class="feature-row">
          <div class="feature-icon-wrap">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
          </div>
          <span class="feature-text">Certificados digitales al completar módulos</span>
        </div>

        <div class="feature-row">
          <div class="feature-icon-wrap">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <span class="feature-text">Gestión multi-rol: admin, docentes y practicantes</span>
        </div>

      </div>
    </div>

    <!-- Bottom: Footer -->
    <div class="left-footer">GUE Mariscal Nieto &middot; 2026</div>

  </aside>

  <!-- ── RIGHT PANEL ──────────────────────────────────────────── -->
  <main class="login-right">
    <div class="form-card">

      <h1 class="form-heading">Iniciar sesión</h1>
      <p class="form-subheading">Ingresa tus credenciales para acceder</p>

      <?php if ($error): ?>
      <div class="login-alert login-alert-error" role="alert">
        <svg viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span><?= sanitize($error) ?></span>
      </div>
      <?php endif; ?>

      <?php if ($queryError === 'acceso'): ?>
      <div class="login-alert login-alert-warning" role="alert">
        <svg viewBox="0 0 24 24">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <span>No tienes acceso a esa secci&oacute;n.</span>
      </div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>

        <div class="field-group">
          <label class="field-label" for="login-email">Email o c&oacute;digo de acceso</label>
          <input
            id="login-email"
            type="text"
            name="email"
            class="field-input"
            placeholder="tu@email.com o EST001"
            value="<?= sanitize($_POST['email'] ?? '') ?>"
            autocomplete="username"
            required
          />
        </div>

        <div class="field-group" style="margin-bottom:26px">
          <label class="field-label" for="login-password">Contrase&ntilde;a</label>
          <div class="field-input-wrap">
            <input
              id="login-password"
              type="password"
              name="password"
              class="field-input has-toggle"
              placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
              autocomplete="current-password"
              required
            />
            <button
              type="button"
              class="pwd-toggle"
              id="pwd-toggle-btn"
              aria-label="Mostrar contrase&ntilde;a"
              onclick="togglePassword()"
            >
              <!-- Eye icon (password hidden) -->
              <svg id="icon-eye" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <!-- Eye-off icon (password shown) -->
              <svg id="icon-eye-off" viewBox="0 0 24 24" style="display:none">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login">
          Ingresar a la plataforma
        </button>

      </form>

      <!-- Demo credentials -->
      <div class="demo-section">
        <div class="demo-separator">
          <span class="demo-separator-line"></span>
          <span class="demo-separator-text">Cuentas de demo</span>
          <span class="demo-separator-line"></span>
        </div>
        <div class="demo-grid">
          <?php
          $demos = [
            ['admin@innovasteam.edu.pe',       'password', 'Admin',        'admin@innovasteam.edu.pe'],
            ['docente@innovasteam.edu.pe',      'password', 'Docente',      'docente@innovasteam.edu.pe'],
            ['practicante@innovasteam.edu.pe',  'password', 'Practicante',  'practicante@innovasteam.edu.pe'],
            ['EST-001',                         'password', 'Estudiante',   'EST-001'],
          ];
          foreach ($demos as [$cred, $pass, $label, $display]):
          ?>
          <button
            type="button"
            class="demo-btn"
            onclick="fillDemo('<?= htmlspecialchars($cred, ENT_QUOTES) ?>', '<?= htmlspecialchars($pass, ENT_QUOTES) ?>')"
          >
            <span class="demo-btn-role"><?= htmlspecialchars($label) ?></span>
            <span class="demo-btn-cred"><?= htmlspecialchars($display) ?></span>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </main>

</div>

<script>
  function togglePassword() {
    var input      = document.getElementById('login-password');
    var eyeIcon    = document.getElementById('icon-eye');
    var eyeOffIcon = document.getElementById('icon-eye-off');
    var btn        = document.getElementById('pwd-toggle-btn');
    if (input.type === 'password') {
      input.type = 'text';
      eyeIcon.style.display    = 'none';
      eyeOffIcon.style.display = '';
      btn.setAttribute('aria-label', 'Ocultar contraseña');
    } else {
      input.type = 'password';
      eyeIcon.style.display    = '';
      eyeOffIcon.style.display = 'none';
      btn.setAttribute('aria-label', 'Mostrar contraseña');
    }
  }

  function fillDemo(email, password) {
    document.getElementById('login-email').value = email;
    var pwdField = document.getElementById('login-password');
    pwdField.value = password;
    pwdField.type  = 'password';
    document.getElementById('icon-eye').style.display    = '';
    document.getElementById('icon-eye-off').style.display = 'none';
    document.getElementById('pwd-toggle-btn').setAttribute('aria-label', 'Mostrar contraseña');
  }
</script>

</body>
</html>
