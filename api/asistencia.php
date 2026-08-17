<?php
// ============================================================
// INNOVA-STEAM — API: Registrar sesión y asistencia
// POST JSON {
//   aula_id, modulo_id, fecha_sesion, notas,
//   asistentes: [estudiante_id, ...]
// }
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

requireLogin('practicante');
header('Content-Type: application/json; charset=utf-8');

// ── Only POST ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'método no permitido']);
    exit;
}

// ── Input ─────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['ok' => false, 'error' => 'payload inválido']);
    exit;
}

$aulaId      = (int)($data['aula_id']      ?? 0);
$moduloId    = (int)($data['modulo_id']    ?? 0);
$fechaSesion = trim($data['fecha_sesion']  ?? '');
$asistentes  = $data['asistentes']         ?? [];  // array of estudiante_id ints
$notas       = trim($data['notas']         ?? '');
$userId      = currentUserId();

// ── Validation ────────────────────────────────────────────────
$errors = [];

if (!$aulaId)   $errors[] = 'aula_id requerido';
if (!$moduloId) $errors[] = 'modulo_id requerido';

if (empty($fechaSesion)) {
    $errors[] = 'fecha_sesion requerida';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSesion)) {
    $errors[] = 'fecha_sesion debe tener formato YYYY-MM-DD';
} elseif (strtotime($fechaSesion) === false) {
    $errors[] = 'fecha_sesion inválida';
}

if (!is_array($asistentes)) {
    $errors[] = 'asistentes debe ser un arreglo';
}

if (!empty($errors)) {
    echo json_encode(['ok' => false, 'error' => implode('; ', $errors)]);
    exit;
}

// Sanitize asistentes to ints > 0
$asistentesIds = array_values(array_filter(
    array_map('intval', $asistentes),
    fn($id) => $id > 0
));

$pdo = getDB();

// ── Verify practicante owns this aula ─────────────────────────
$stmtOwn = $pdo->prepare(
    'SELECT COUNT(*) FROM practicante_aula
     WHERE practicante_id = ? AND aula_id = ?'
);
$stmtOwn->execute([$userId, $aulaId]);
if ((int)$stmtOwn->fetchColumn() === 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'no tienes acceso a esta aula']);
    exit;
}

// ── Verify module exists ──────────────────────────────────────
$stmtMod = $pdo->prepare('SELECT id FROM modulos WHERE id = ? AND activo = 1');
$stmtMod->execute([$moduloId]);
if (!$stmtMod->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'módulo no encontrado']);
    exit;
}

// ── Insert sesion ─────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    $pdo->prepare(
        'INSERT INTO sesiones
            (practicante_id, aula_id, modulo_id, fecha_sesion, asistentes, notas, creado_en)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    )->execute([
        $userId,
        $aulaId,
        $moduloId,
        $fechaSesion,
        count($asistentesIds),
        $notas !== '' ? $notas : null,
    ]);

    $sesionId = (int)$pdo->lastInsertId();

    // ── Bulk insert asistencia ────────────────────────────────
    if (!empty($asistentesIds)) {
        // Build a single multi-row INSERT for efficiency
        $placeholders = implode(', ', array_fill(0, count($asistentesIds), '(?, ?, 1, NOW())'));
        $params = [];
        foreach ($asistentesIds as $estId) {
            $params[] = $sesionId;
            $params[] = $estId;
        }

        $pdo->prepare(
            "INSERT INTO asistencia (sesion_id, estudiante_id, presente, registrado_en)
             VALUES {$placeholders}
             ON DUPLICATE KEY UPDATE presente = 1, registrado_en = NOW()"
        )->execute($params);
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'error' => 'error al guardar: ' . $e->getMessage()]);
    exit;
}

// ── Response ──────────────────────────────────────────────────
echo json_encode([
    'ok'              => true,
    'sesion_id'       => $sesionId,
    'asistentes'      => count($asistentesIds),
    'fecha_sesion'    => $fechaSesion,
]);
