<?php
// ============================================================
// INNOVA-STEAM — Endpoint del asistente de estudio
//
//   POST /api/chat.php   {pregunta, historial?, modulo?}
//
// Acepta sesión web (con token CSRF) o token de la app móvil.
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_auth.php';
require_once __DIR__ . '/../includes/chatbot.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Usa POST.', 405);
}

$cuerpo = apiBody();

// Dos vías de acceso: la web usa la sesión de PHP con su token CSRF; la
// app móvil, el token Bearer. Se acepta cualquiera de las dos.
$usuario = null;
if (isLoggedIn()) {
    verifyCsrfJson($cuerpo);
    $usuario = currentUser();
    $usuario['id']  = currentUserId();
    $usuario['rol'] = currentRole();
} else {
    $usuario = requireApiAuth();   // responde 401 en JSON si no hay token
}

$usuarioId = (int)$usuario['id'];
$colegioId = isset($usuario['colegio_id']) ? (int)$usuario['colegio_id'] : null;

$pregunta = trim((string)($cuerpo['pregunta'] ?? ''));
if ($pregunta === '') {
    apiError('Escribe una pregunta.', 422);
}
if (mb_strlen($pregunta) > 2000) {
    apiError('La pregunta es demasiado larga. Resúmela un poco.', 422);
}

// ── Configuración del colegio ────────────────────────────────────
$config = chatbotConfigColegio($colegioId);
if ($config === null) {
    apiError('Tu colegio todavía no tiene el asistente activado.', 503,
             ['motivo' => 'sin_configurar']);
}

// ── Tope diario ──────────────────────────────────────────────────
// Sin esto, un aula entera puede agotar el saldo del colegio en una tarde.
$usadas = chatbotUsoHoy($usuarioId);
if ($usadas >= $config['tope_dia']) {
    chatbotRegistrar($usuarioId, $colegioId, $pregunta, null, 'tope');
    apiError(
        "Has llegado al límite de {$config['tope_dia']} preguntas por hoy. Vuelve mañana.",
        429,
        ['motivo' => 'tope_diario', 'usadas' => $usadas, 'tope' => $config['tope_dia']]
    );
}

// ── Consulta ─────────────────────────────────────────────────────
$historial = is_array($cuerpo['historial'] ?? null) ? $cuerpo['historial'] : [];
$modulo    = isset($cuerpo['modulo']) ? (string)$cuerpo['modulo'] : null;

$resultado = chatbotPreguntar($config, $usuario, $pregunta, $historial, $modulo);

if (!$resultado['ok']) {
    chatbotRegistrar($usuarioId, $colegioId, $pregunta, null, 'error');
    apiError($resultado['error'], 502);
}

chatbotRegistrar(
    $usuarioId, $colegioId, $pregunta, $resultado['texto'], 'ok',
    $resultado['tokens_in'], $resultado['tokens_out']
);

apiJson([
    'ok'        => true,
    'respuesta' => $resultado['texto'],
    'restantes' => max(0, $config['tope_dia'] - $usadas - 1),
]);
