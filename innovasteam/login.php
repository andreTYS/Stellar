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
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <style>
    /* ── Design tokens ─────────────────────────────────────── */
    :root {
      --accent:     #4361ee;
      --accent-h:   #3451d1;
      --text-dark:  #1a1f36;
      --text-muted: #8898aa;
      --border:     #e4e8f0;
      --bg-input:   #ffffff;
      --bg-right:   #f8f9fc;
    }

    /* ── Reset ─────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      height: 100%;
      overflow: hidden;
      font-family: 'Inter', system-ui, sans-serif;
      -webkit-font-smoothing: antialiased;
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
      width: 45%;
      flex-shrink: 0;
      background: #1a1f36;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 52px 52px 40px;
      position: relative;
      overflow: hidden;
    }

    /* Subtle decorative radial glows */
    .login-left::before {
      content: '';
      position: absolute;
      top: -140px; left: -100px;
      width: 520px; height: 520px;
      background: radial-gradient(circle, rgba(67,97,238,.13) 0%, transparent 68%);
      pointer-events: none;
    }
    .login-left::after {
      content: '';
      position: absolute;
      bottom: -100px; right: -80px;
      width: 380px; height: 380px;
      background: radial-gradient(circle, rgba(99,102,241,.10) 0%, transparent 68%);
      pointer-events: none;
    }

    /* ── Brand top row ─────────────────────────────────────── */
    .left-brand {
      display: flex;
      align-items: center;
      gap: 14px;
      position: relative;
      z-index: 1;
    }

    .logo-mark {
      width: 44px;
      height: 44px;
      background: var(--accent);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 16px;
      color: #ffffff;
      letter-spacing: -0.02em;
      flex-shrink: 0;
    }

    .brand-name {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 18px;
      color: #ffffff;
      letter-spacing: 0.01em;
    }

    /* ── Center content ────────────────────────────────────── */
    .left-center {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
      z-index: 1;
      padding: 40px 0;
    }

    .left-heading {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 36px;
      line-height: 1.15;
      color: #ffffff;
      margin-bottom: 14px;
      letter-spacing: -0.02em;
    }

    .left-subtitle {
      font-size: 14.5px;
      color: #8898aa;
      line-height: 1.6;
      max-width: 300px;
      margin-bottom: 48px;
    }

    /* ── Feature list ──────────────────────────────────────── */
    .feature-list {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .feature-row {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .feature-icon-wrap {
      width: 36px;
      height: 36px;
      border-radius: 9px;
      background: rgba(67,97,238,.15);
      border: 1px solid rgba(67,97,238,.25);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .feature-icon-wrap svg {
      width: 16px;
      height: 16px;
      stroke: #4361ee;
      stroke-width: 2;
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .feature-text {
      font-size: 14px;
      color: #c4cdd6;
      line-height: 1.4;
    }

    /* ── Left footer ───────────────────────────────────────── */
    .left-footer {
      font-size: 12px;
      color: #3d4a5c;
      position: relative;
      z-index: 1;
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
    }

    /* ── Form card ─────────────────────────────────────────── */
    .form-card {
      width: 100%;
      max-width: 420px;
      background: #ffffff;
      border-radius: 16px;
      border: 1px solid var(--border);
      padding: 40px 36px;
      box-shadow: 0 4px 24px rgba(26,31,54,.06);
    }

    .form-heading {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 24px;
      color: var(--text-dark);
      margin-bottom: 6px;
      letter-spacing: -0.01em;
    }

    .form-subheading {
      font-size: 14px;
      color: var(--text-muted);
      margin-bottom: 28px;
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
      background: var(--bg-input);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      padding: 11px 14px;
      font-size: 14px;
      color: var(--text-dark);
      font-family: 'Inter', sans-serif;
      transition: border-color .15s, box-shadow .15s;
      outline: none;
      appearance: none;
    }

    .field-input::placeholder {
      color: #c0c8d4;
    }

    .field-input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(67,97,238,.12);
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
      width: 42px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      border: none;
      cursor: pointer;
      color: #c0c8d4;
      transition: color .15s;
      border-radius: 0 10px 10px 0;
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
      padding: 12px 20px;
      background: var(--accent);
      color: #ffffff;
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 15px;
      letter-spacing: 0.01em;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background .15s, transform .1s, box-shadow .15s;
      margin-top: 4px;
    }

    .btn-login:hover {
      background: var(--accent-h);
      box-shadow: 0 4px 20px rgba(67,97,238,.28);
    }

    .btn-login:active {
      transform: scale(.985);
    }

    /* ── Demo credentials ──────────────────────────────────── */
    .demo-section {
      margin-top: 28px;
    }

    .demo-separator {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }

    .demo-separator-line {
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .demo-separator-text {
      font-size: 11.5px;
      font-weight: 600;
      letter-spacing: .06em;
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
      background: var(--bg-right);
      border: 1.5px solid var(--border);
      border-radius: 9px;
      cursor: pointer;
      transition: border-color .15s, background .15s, box-shadow .15s;
      text-align: left;
    }

    .demo-btn:hover {
      background: #eef2ff;
      border-color: var(--accent);
      box-shadow: 0 2px 8px rgba(67,97,238,.10);
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
        padding: 28px 24px 24px;
        flex-direction: row;
        align-items: center;
        justify-content: center;
      }

      .login-left::before,
      .login-left::after { display: none; }

      .left-center,
      .left-footer { display: none; }

      .left-brand {
        flex-direction: row;
        align-items: center;
      }

      .left-heading { display: none; }

      .login-right {
        flex: none;
        padding: 32px 20px 52px;
        background: var(--bg-right);
      }

      .form-card {
        border: none;
        box-shadow: none;
        padding: 0;
        background: transparent;
      }
    }
  </style>
</head>
<body>

<div class="login-split">

  <!-- ── LEFT PANEL ───────────────────────────────────────────── -->
  <aside class="login-left">

    <!-- Top: Brand -->
    <div class="left-brand">
      <div class="logo-mark">IS</div>
      <div class="brand-name">INNOVA-STEAM</div>
    </div>

    <!-- Middle: Heading + Features -->
    <div class="left-center">
      <h2 class="left-heading">Aprende STEAM<br>a tu ritmo</h2>
      <p class="left-subtitle">Plataforma educativa para instituciones de Moquegua, Peru</p>

      <div class="feature-list">

        <div class="feature-row">
          <div class="feature-icon-wrap">
            <!-- Book icon -->
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <span class="feature-text">5 cursos STEAM integrados</span>
        </div>

        <div class="feature-row">
          <div class="feature-icon-wrap">
            <!-- Chart bar icon -->
            <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          </div>
          <span class="feature-text">Seguimiento de progreso en tiempo real</span>
        </div>

        <div class="feature-row">
          <div class="feature-icon-wrap">
            <!-- Award icon -->
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
          </div>
          <span class="feature-text">Certificados al completar modulos</span>
        </div>

        <div class="feature-row">
          <div class="feature-icon-wrap">
            <!-- Users icon -->
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <span class="feature-text">Gestion multi-rol para docentes y practicantes</span>
        </div>

      </div>
    </div>

    <!-- Bottom: Footer -->
    <div class="left-footer">GUE Mariscal Nieto &middot; 2026</div>

  </aside>

  <!-- ── RIGHT PANEL ──────────────────────────────────────────── -->
  <main class="login-right">
    <div class="form-card">

      <h1 class="form-heading">Iniciar sesion</h1>
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
