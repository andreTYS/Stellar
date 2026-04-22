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
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/stellar.css"/>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <style>
    /* ── Login page reset ─────────────────────────────────── */
    html, body {
      height: 100%;
      overflow: hidden;
    }

    body {
      background: #0d1117;
      font-family: 'Inter', system-ui, sans-serif;
    }

    /* ── Split screen wrapper ─────────────────────────────── */
    .login-split {
      display: flex;
      height: 100vh;
      width: 100%;
    }

    /* ── Left panel — Branding ────────────────────────────── */
    .login-left {
      width: 40%;
      flex-shrink: 0;
      background: #0d1117;
      border-right: 1px solid #1e2737;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-between;
      padding: 56px 48px;
      position: relative;
      overflow: hidden;
    }

    /* Subtle gradient orb behind content */
    .login-left::before {
      content: '';
      position: absolute;
      top: -120px;
      left: -80px;
      width: 480px;
      height: 480px;
      background: radial-gradient(circle, rgba(74,158,255,.10) 0%, transparent 70%);
      pointer-events: none;
    }

    .login-left::after {
      content: '';
      position: absolute;
      bottom: -80px;
      right: -60px;
      width: 320px;
      height: 320px;
      background: radial-gradient(circle, rgba(167,139,250,.07) 0%, transparent 70%);
      pointer-events: none;
    }

    .brand-center {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      position: relative;
      z-index: 1;
    }

    /* Logo mark — geometric diamond shape */
    .logo-mark {
      width: 56px;
      height: 56px;
      margin-bottom: 24px;
    }

    .brand-name {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 32px;
      letter-spacing: -0.02em;
      background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 10px;
      line-height: 1.1;
    }

    .brand-tagline {
      font-size: 13.5px;
      color: #8892a4;
      line-height: 1.5;
      max-width: 240px;
    }

    /* Feature bullets */
    .brand-features {
      display: flex;
      flex-direction: column;
      gap: 14px;
      margin-top: 44px;
      width: 100%;
      max-width: 260px;
    }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 12px;
      color: #8892a4;
      font-size: 13.5px;
    }

    .feature-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: #161c2c;
      border: 1px solid #2a3347;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      color: #4a9eff;
    }

    .feature-icon svg {
      width: 15px;
      height: 15px;
      stroke: #4a9eff;
      stroke-width: 2;
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .brand-footer {
      font-size: 12px;
      color: #3d4a5c;
      text-align: center;
      position: relative;
      z-index: 1;
    }

    /* ── Right panel — Form ───────────────────────────────── */
    .login-right {
      flex: 1;
      background: #111827;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 32px;
      overflow-y: auto;
    }

    .login-form-wrap {
      width: 100%;
      max-width: 400px;
    }

    /* Form header */
    .form-heading {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 26px;
      color: #e2e8f0;
      margin-bottom: 6px;
      letter-spacing: -0.01em;
    }

    .form-subheading {
      font-size: 14px;
      color: #8892a4;
      margin-bottom: 32px;
    }

    /* Alerts */
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
    }

    .login-alert-error {
      background: rgba(239,68,68,.08);
      border: 1px solid rgba(239,68,68,.25);
      color: #fca5a5;
    }

    .login-alert-warning {
      background: rgba(245,158,11,.08);
      border: 1px solid rgba(245,158,11,.25);
      color: #fcd34d;
    }

    /* Field groups */
    .field-group {
      margin-bottom: 20px;
    }

    .field-label {
      display: block;
      font-size: 13px;
      font-weight: 500;
      color: #c4cdd6;
      margin-bottom: 7px;
    }

    .field-input-wrap {
      position: relative;
    }

    .field-input {
      width: 100%;
      background: #161c2c;
      border: 1px solid #2a3347;
      border-radius: 10px;
      padding: 11px 14px;
      font-size: 14px;
      color: #e2e8f0;
      font-family: 'Inter', sans-serif;
      transition: border-color .15s, box-shadow .15s;
      outline: none;
    }

    .field-input::placeholder {
      color: #3d4a5c;
    }

    .field-input:focus {
      border-color: #4a9eff;
      box-shadow: 0 0 0 3px rgba(74,158,255,.12);
    }

    .field-input.has-toggle {
      padding-right: 44px;
    }

    /* Password toggle */
    .pwd-toggle {
      position: absolute;
      right: 0;
      top: 0;
      height: 100%;
      width: 42px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      border: none;
      cursor: pointer;
      color: #3d4a5c;
      transition: color .15s;
      border-radius: 0 10px 10px 0;
    }

    .pwd-toggle:hover {
      color: #8892a4;
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

    /* Submit button */
    .btn-login {
      width: 100%;
      padding: 12px 20px;
      background: #4a9eff;
      color: #ffffff;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 14.5px;
      letter-spacing: 0.01em;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background .15s, transform .1s, box-shadow .15s;
      margin-top: 4px;
    }

    .btn-login:hover {
      background: #3b8ee8;
      box-shadow: 0 4px 20px rgba(74,158,255,.30);
    }

    .btn-login:active {
      transform: scale(.985);
    }

    /* Demo credentials */
    .demo-section {
      margin-top: 32px;
      padding-top: 24px;
      border-top: 1px solid #1e2737;
    }

    .demo-label {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: .07em;
      text-transform: uppercase;
      color: #3d4a5c;
      text-align: center;
      margin-bottom: 14px;
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
      padding: 9px 12px;
      background: #161c2c;
      border: 1px solid #2a3347;
      border-radius: 9px;
      cursor: pointer;
      transition: border-color .15s, background .15s;
      text-align: left;
    }

    .demo-btn:hover {
      background: #1a2236;
      border-color: #4a9eff;
    }

    .demo-btn-role {
      font-family: 'Syne', sans-serif;
      font-size: 12px;
      font-weight: 700;
      color: #c4cdd6;
    }

    .demo-btn-cred {
      font-size: 10.5px;
      color: #3d4a5c;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      width: 100%;
    }

    /* ── Responsive — mobile stacks vertically ────────────── */
    @media (max-width: 768px) {
      html, body {
        overflow: auto;
      }

      .login-split {
        flex-direction: column;
        height: auto;
        min-height: 100vh;
      }

      .login-left {
        width: 100%;
        padding: 36px 24px 32px;
        border-right: none;
        border-bottom: 1px solid #1e2737;
      }

      .login-left::before,
      .login-left::after {
        display: none;
      }

      .brand-center {
        flex: none;
      }

      .brand-features {
        display: none;
      }

      .brand-footer {
        display: none;
      }

      .brand-name {
        font-size: 26px;
      }

      .login-right {
        flex: none;
        padding: 40px 24px 56px;
      }
    }
  </style>
</head>
<body>

<div class="login-split">

  <!-- ── Left Panel: Branding ─────────────────────────────── -->
  <aside class="login-left">
    <div class="brand-center">

      <!-- Abstract logo mark -->
      <svg class="logo-mark" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <defs>
          <linearGradient id="lg1" x1="0" y1="0" x2="56" y2="56" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#4a9eff"/>
            <stop offset="100%" stop-color="#a78bfa"/>
          </linearGradient>
        </defs>
        <!-- Outer hexagon-ish shape -->
        <path d="M28 4 L50 16 L50 40 L28 52 L6 40 L6 16 Z"
              stroke="url(#lg1)" stroke-width="1.5" fill="rgba(74,158,255,0.06)"/>
        <!-- Inner accent lines -->
        <path d="M28 12 L42 20 L42 36 L28 44 L14 36 L14 20 Z"
              stroke="url(#lg1)" stroke-width="1" fill="rgba(74,158,255,0.04)" opacity=".7"/>
        <!-- Center dot cluster -->
        <circle cx="28" cy="28" r="4" fill="url(#lg1)"/>
        <circle cx="20" cy="23" r="2" fill="#4a9eff" opacity=".5"/>
        <circle cx="36" cy="23" r="2" fill="#a78bfa" opacity=".5"/>
        <circle cx="28" cy="37" r="2" fill="#4a9eff" opacity=".4"/>
        <!-- Connecting lines from center -->
        <line x1="28" y1="28" x2="20" y2="23" stroke="#4a9eff" stroke-width="1" opacity=".4"/>
        <line x1="28" y1="28" x2="36" y2="23" stroke="#a78bfa" stroke-width="1" opacity=".4"/>
        <line x1="28" y1="28" x2="28" y2="37" stroke="#4a9eff" stroke-width="1" opacity=".35"/>
      </svg>

      <div class="brand-name">INNOVA-STEAM</div>
      <div class="brand-tagline">Plataforma educativa STEAM<br>para Moquegua</div>

      <!-- Feature bullets -->
      <div class="brand-features">

        <div class="feature-item">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
          </div>
          <span>5 cursos STEAM activos</span>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          </div>
          <span>Progreso personalizado</span>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
          </div>
          <span>Certificados digitales</span>
        </div>

      </div>
    </div>

    <div class="brand-footer">Moquegua, Per&uacute; &middot; 2026</div>
  </aside>

  <!-- ── Right Panel: Form ─────────────────────────────────── -->
  <main class="login-right">
    <div class="login-form-wrap">

      <h1 class="form-heading">Bienvenido</h1>
      <p class="form-subheading">Ingresa tus credenciales para continuar</p>

      <?php if ($error): ?>
      <div class="login-alert login-alert-error" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span><?= sanitize($error) ?></span>
      </div>
      <?php endif; ?>

      <?php if ($queryError === 'acceso'): ?>
      <div class="login-alert login-alert-warning" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

        <div class="field-group" style="margin-bottom:24px">
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
              <!-- Eye icon (visible when password hidden) -->
              <svg id="icon-eye" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <!-- Eye-off icon (visible when password shown) -->
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
        <p class="demo-label">Cuentas demo &mdash; contrase&ntilde;a: password</p>
        <div class="demo-grid">
          <?php
          $demos = [
            ['admin@innovasteam.pe',       'password', 'Administrador', 'admin@innovasteam.pe'],
            ['docente@innovasteam.pe',      'password', 'Docente',       'docente@innovasteam.pe'],
            ['practicante@innovasteam.pe',  'password', 'Practicante',   'practicante@innovasteam.pe'],
            ['EST001',                      'password', 'Estudiante',    'EST001'],
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
    var input = document.getElementById('login-password');
    var eyeIcon = document.getElementById('icon-eye');
    var eyeOffIcon = document.getElementById('icon-eye-off');
    var btn = document.getElementById('pwd-toggle-btn');
    if (input.type === 'password') {
      input.type = 'text';
      eyeIcon.style.display = 'none';
      eyeOffIcon.style.display = '';
      btn.setAttribute('aria-label', 'Ocultar contraseña');
    } else {
      input.type = 'password';
      eyeIcon.style.display = '';
      eyeOffIcon.style.display = 'none';
      btn.setAttribute('aria-label', 'Mostrar contraseña');
    }
  }

  function fillDemo(email, password) {
    document.getElementById('login-email').value = email;
    var pwdField = document.getElementById('login-password');
    pwdField.value = password;
    // Make sure password field is in hidden mode so the toggle is correct
    pwdField.type = 'password';
    document.getElementById('icon-eye').style.display = '';
    document.getElementById('icon-eye-off').style.display = 'none';
    document.getElementById('pwd-toggle-btn').setAttribute('aria-label', 'Mostrar contraseña');
  }
</script>

</body>
</html>
