<?php
// ============================================================
// INNOVA-STEAM — API: reordenar los pasos de un módulo
// POST JSON { modulo_id: int, orden: [id, id, ...] }
// El array `orden` debe contener EXACTAMENTE los ids de los pasos
// del módulo indicado; se renumera 1..N en una sola transacción.
// El token CSRF viaja en la cabecera X-CSRF-Token.
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

/** Devuelve la respuesta como JSON y corta la ejecución. */
function responderOrden(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderOrden(['ok' => false, 'error' => 'Método no permitido.'], 405);
}

// Solo el administrador puede reordenar pasos (igual que admin/modulo_pasos.php).
if (!isLoggedIn()) {
    responderOrden(['ok' => false, 'error' => 'Sesión expirada. Vuelve a iniciar sesión.'], 401);
}
if (currentRole() !== 'admin') {
    responderOrden(['ok' => false, 'error' => 'No tienes permiso para reordenar pasos.'], 403);
}

// Mismo helper que usan los formularios; acepta el token por cabecera.
verifyCsrf();

$data = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($data)) {
    responderOrden(['ok' => false, 'error' => 'Petición inválida.'], 400);
}

$moduloId = (int)($data['modulo_id'] ?? 0);
$orden    = $data['orden'] ?? null;

if ($moduloId <= 0 || !is_array($orden) || $orden === []) {
    responderOrden(['ok' => false, 'error' => 'Faltan datos para reordenar.'], 400);
}

// Normaliza a enteros positivos y verifica que no haya ids repetidos.
$ids = [];
foreach ($orden as $valor) {
    if (!is_int($valor) && !(is_string($valor) && ctype_digit($valor))) {
        responderOrden(['ok' => false, 'error' => 'Lista de pasos inválida.'], 400);
    }
    $ids[] = (int)$valor;
}
if (count($ids) !== count(array_unique($ids)) || min($ids) <= 0) {
    responderOrden(['ok' => false, 'error' => 'Lista de pasos inválida.'], 400);
}
// numero_paso es TINYINT: la numeración temporal negativa debe caber.
if (count($ids) > 100) {
    responderOrden(['ok' => false, 'error' => 'Demasiados pasos para reordenar.'], 400);
}

try {
    $pdo = getDB();

    // El módulo debe existir.
    $existeModulo = $pdo->prepare('SELECT 1 FROM modulos WHERE id=?');
    $existeModulo->execute([$moduloId]);
    if (!$existeModulo->fetchColumn()) {
        responderOrden(['ok' => false, 'error' => 'El módulo no existe.'], 404);
    }

    // Ids reales del módulo: rechaza ids de otro módulo, inexistentes o
    // listas incompletas (que dejarían huecos o números duplicados).
    $stmt = $pdo->prepare('SELECT id FROM modulo_pasos WHERE modulo_id=?');
    $stmt->execute([$moduloId]);
    $idsModulo = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    sort($idsModulo);
    $idsEnviados = $ids;
    sort($idsEnviados);
    if ($idsModulo !== $idsEnviados) {
        responderOrden([
            'ok'    => false,
            'error' => 'La lista no coincide con los pasos del módulo. Recarga la página.',
        ], 409);
    }

    $pdo->beginTransaction();

    // Existe un UNIQUE (modulo_id, numero_paso), así que se renumera en dos
    // fases: primero a valores negativos temporales y luego a 1..N.
    $update = $pdo->prepare('UPDATE modulo_pasos SET numero_paso=? WHERE id=? AND modulo_id=?');
    foreach ($ids as $i => $id) {
        $update->execute([-($i + 1), $id, $moduloId]);
    }
    foreach ($ids as $i => $id) {
        $update->execute([$i + 1, $id, $moduloId]);
    }

    $pdo->commit();

    $resultado = [];
    foreach ($ids as $i => $id) {
        $resultado[] = ['id' => $id, 'numero_paso' => $i + 1];
    }
    responderOrden(['ok' => true, 'orden' => $resultado]);
} catch (\Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    responderOrden(['ok' => false, 'error' => 'No se pudo guardar el orden.'], 500);
}
