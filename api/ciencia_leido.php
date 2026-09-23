<?php
// ============================================================
// INNOVA-STEAM — API: marcar un artículo de ciencia como leído
//
//   POST /api/ciencia_leido.php  {articulo_id}
//
// Lo llama el propio artículo a los 25 segundos de lectura. Abrir y
// salir no cuenta: si contara, los logros de lectura no dirían nada.
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Usa POST.', 405);
}

$cuerpo  = apiBody();
// Sesión web con su CSRF, o token Bearer desde la app.
$usuario = requireAuthWebOApi($cuerpo);

$usuarioId  = (int)$usuario['id'];
$articuloId = (int)($cuerpo['articulo_id'] ?? 0);

if ($articuloId <= 0) {
    apiError('Falta el artículo.', 422);
}

$pdo = getDB();

// Que el artículo exista y esté activo: si no, cualquiera podría
// inflar su contador de lectura con ids inventados.
$stmt = $pdo->prepare('SELECT id FROM ciencia_articulos WHERE id = ? AND activo = 1');
$stmt->execute([$articuloId]);
if (!$stmt->fetch()) {
    apiError('Ese artículo no existe.', 404);
}

try {
    $pdo->prepare(
        'INSERT IGNORE INTO ciencia_leidos (usuario_id, articulo_id) VALUES (?, ?)'
    )->execute([$usuarioId, $articuloId]);
} catch (\Throwable $e) {
    error_log('api/ciencia_leido: ' . $e->getMessage());
    apiError('No se pudo registrar la lectura.', 500);
}

// ── Logros ───────────────────────────────────────────────────────
$stmtN = $pdo->prepare('SELECT COUNT(*) FROM ciencia_leidos WHERE usuario_id = ?');
$stmtN->execute([$usuarioId]);
$leidos = (int)$stmtN->fetchColumn();

$total = (int)$pdo->query('SELECT COUNT(*) FROM ciencia_articulos WHERE activo = 1')->fetchColumn();

$candidatos = [];
if ($leidos >= 3)                     $candidatos[] = 'ciencia-curioso';
if ($leidos >= 10)                    $candidatos[] = 'ciencia-lector';
if ($total > 0 && $leidos >= $total)  $candidatos[] = 'ciencia-completo';

// Solo se anuncia el que se acaba de ganar, no los que ya tenía.
$nuevo = null;
if ($candidatos) {
    $sel = $pdo->prepare('SELECT id, nombre FROM logros WHERE slug = ?');
    $ins = $pdo->prepare('INSERT IGNORE INTO usuario_logros (usuario_id, logro_id, obtenido_en) VALUES (?,?,NOW())');
    foreach ($candidatos as $slug) {
        $sel->execute([$slug]);
        $logro = $sel->fetch();
        if (!$logro) continue;
        $ins->execute([$usuarioId, (int)$logro['id']]);
        if ($ins->rowCount() > 0) {
            $nuevo = $logro['nombre'];
        }
    }
}

apiJson([
    'ok'     => true,
    'leidos' => $leidos,
    'total'  => $total,
    'logro'  => $nuevo,
]);
