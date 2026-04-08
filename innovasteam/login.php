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
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <!-- Logo -->
    <div class="login-logo">
      <div style="font-size:40px;margin-bottom:8px">🚀</div>
      <div class="logo-title">INNOVA-STEAM</div>
      <div class="logo-sub">Plataforma Educativa · Moquegua, Perú</div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error mb-16"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <?php if ($queryError === 'acceso'): ?>
    <div class="alert alert-warning mb-16">No tienes acceso a esa sección.</div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">Email o código de acceso</label>
        <input
          type="text"
          name="email"
          class="form-control"
          placeholder="tu@email.com o EST-001"
          value="<?= sanitize($_POST['email'] ?? '') ?>"
          autocomplete="username"
          required
        />
      </div>

      <div class="form-group" style="margin-bottom:24px">
        <label class="form-label">Contraseña</label>
        <input
          type="password"
          name="password"
          class="form-control"
          placeholder="••••••••"
          autocomplete="current-password"
          required
        />
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">
        Ingresar a la plataforma
      </button>
    </form>

    <!-- Demo credentials -->
    <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--bg-border)">
      <p style="font-size:11px;color:var(--text-muted);text-align:center;margin-bottom:12px;text-transform:uppercase;letter-spacing:.06em">
        Cuentas demo (contraseña: 1234)
      </p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <?php
        $demos = [
          ['admin@innovasteam.edu.pe', 'Admin'],
          ['director@innovasteam.edu.pe', 'Director'],
          ['prac@innovasteam.edu.pe', 'Practicante'],
          ['EST-001', 'Estudiante'],
        ];
        foreach ($demos as [$cred, $label]):
        ?>
        <button
          type="button"
          onclick="document.querySelector('[name=email]').value='<?= $cred ?>'; document.querySelector('[name=password]').value='1234';"
          style="background:var(--bg-elevated);border:1px solid var(--bg-border);color:var(--text-secondary);border-radius:8px;padding:6px 8px;font-size:11px;cursor:pointer;transition:.15s"
          onmouseover="this.style.borderColor='var(--blue)'"
          onmouseout="this.style.borderColor='var(--bg-border)'"
        >
          <?= $label ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
