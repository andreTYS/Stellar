<?php
// ============================================================
// INNOVA-STEAM — API de sesión para clientes móviles
//
//   POST   /api/auth.php?accion=login    {usuario, password, dispositivo}
//   GET    /api/auth.php?accion=yo       Authorization: Bearer <token>
//   POST   /api/auth.php?accion=logout   Authorization: Bearer <token>
//
// El login reutiliza loginUser() y el mismo límite de intentos que la
// web, para que no haya una puerta trasera sin protección.
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_auth.php';

$accion = $_GET['accion'] ?? '';

switch ($accion) {

    // ── Comprobar que la API responde ────────────────────────
    // No pide token: sirve para saber si el problema es de red o de
    // credenciales. Se puede abrir directamente en el navegador del
    // móvil o del emulador para descartar la app como causa.
    case 'ping':
        apiJson([
            'ok'        => true,
            'servicio'  => 'INNOVA-STEAM API',
            'version'   => APP_VERSION,
            'base_url'  => BASE_URL,
            'hora'      => date(DATE_ATOM),
        ]);

    // ── Iniciar sesión ───────────────────────────────────────
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('Usa POST para iniciar sesión.', 405);
        }

        $body        = apiBody();
        $identidad   = trim((string)($body['usuario'] ?? ''));
        $password    = (string)($body['password'] ?? '');
        $dispositivo = $body['dispositivo'] ?? null;
        $ip          = clientIp();

        if ($identidad === '' || $password === '') {
            apiError('Faltan el usuario o la contraseña.', 422);
        }

        // Mismo bloqueo que la web: 5 fallos por cuenta, 40 por IP.
        $espera = loginBloqueoRestante($identidad, $ip);
        if ($espera > 0) {
            apiError(
                "Demasiados intentos fallidos. Inténtalo de nuevo en {$espera} minuto"
                . ($espera === 1 ? '' : 's') . '.',
                429,
                ['reintentar_en_minutos' => $espera]
            );
        }

        $usuario = loginUser($identidad, $password);
        loginRegistrarIntento($identidad, $ip, (bool)$usuario);

        if (!$usuario) {
            apiError('Credenciales incorrectas.', 401);
        }

        $token = apiCrearToken((int)$usuario['id'], is_string($dispositivo) ? $dispositivo : null);

        apiJson([
            'ok'        => true,
            'token'     => $token['token'],
            'expira_en' => $token['expira_en'],
            'usuario'   => apiUsuarioPublico($usuario),
        ]);

    // ── Quién soy ────────────────────────────────────────────
    case 'yo':
        $usuario = requireApiAuth();
        apiJson(['ok' => true, 'usuario' => apiUsuarioPublico($usuario)]);

    // ── Cerrar sesión ────────────────────────────────────────
    case 'logout':
        $usuario = requireApiAuth();
        getDB()->prepare('DELETE FROM api_tokens WHERE id = ?')
               ->execute([$usuario['token_id']]);
        apiJson(['ok' => true]);

    default:
        apiError('Acción no reconocida. Usa login, yo o logout.', 404);
}
