<?php
// ============================================================
// INNOVA-STEAM — API: Marcar paso como completado
// POST JSON { modulo_id, paso }
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

// ── Input ─────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Sesión web (con CSRF) o token Bearer de la app.
$user = requireAuthWebOApi(is_array($data) ? $data : null, 'estudiante');

if (!is_array($data)) {
    echo json_encode(['ok' => false, 'error' => 'payload inválido']);
    exit;
}

$moduloId = (int)($data['modulo_id'] ?? 0);
$paso     = (int)($data['paso']      ?? 0);   // 1–4
$userId   = (int)$user['id'];

if (!$moduloId || $paso < 1 || $paso > 4) {
    echo json_encode(['ok' => false, 'error' => 'datos inválidos']);
    exit;
}

$pdo = getDB();

// ── Verify the module exists ──────────────────────────────────
$stmtMod = $pdo->prepare('SELECT id FROM modulos WHERE id = ? AND activo = 1');
$stmtMod->execute([$moduloId]);
if (!$stmtMod->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'módulo no encontrado']);
    exit;
}

// ── Upsert progreso ───────────────────────────────────────────
// paso_actual stores the NEXT step the student should do (paso + 1).
// We use GREATEST so we never regress progress.
$siguientePaso = min($paso + 1, 4);

$pdo->prepare(
    'INSERT INTO progreso_estudiante (estudiante_id, modulo_id, paso_actual)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE paso_actual = GREATEST(paso_actual, ?)'
)->execute([$userId, $moduloId, $siguientePaso, $siguientePaso]);

// ── If paso 4 is completed: mark module done & emit certificate ─
$completado = false;
if ($paso >= 4) {
    $pdo->prepare(
        'UPDATE progreso_estudiante
         SET completado = 1, completado_en = NOW()
         WHERE estudiante_id = ? AND modulo_id = ? AND completado = 0'
    )->execute([$userId, $moduloId]);

    // Issue certificate (IGNORE prevents duplicates)
    $pdo->prepare(
        'INSERT IGNORE INTO certificados (estudiante_id, modulo_id, emitido_en)
         VALUES (?, ?, NOW())'
    )->execute([$userId, $moduloId]);

    // Return cert_id so the client can link directly to the certificate page
    $certRow = $pdo->prepare(
        'SELECT id FROM certificados WHERE estudiante_id = ? AND modulo_id = ?'
    );
    $certRow->execute([$userId, $moduloId]);
    $certId = (int)($certRow->fetchColumn() ?: 0);

    $completado = true;

    // ── Logros ───────────────────────────────────────────────
    // Esto pedía 'explorador-aprendiz' y 'maestro-steam', dos slugs que
    // no están en la tabla: la consulta devolvía false, el catch estaba
    // vacío y nadie se enteraba. El logro "Maestro STEAM" no se podía
    // ganar de ninguna manera. El de la tabla se llama 'todos-modulos'.
    $stmtComp = $pdo->prepare(
        'SELECT COUNT(*) FROM progreso_estudiante WHERE estudiante_id = ? AND completado = 1'
    );
    $stmtComp->execute([$userId]);
    $totalComp = (int)$stmtComp->fetchColumn();

    concederLogro($userId, 'primer-modulo');

    // "Maestro STEAM — completaste todos los módulos disponibles": los
    // de SU ciclo, que son los únicos que puede ver. Con un número fijo
    // (antes, 10) el de primaria lo ganaba sin terminar y el de
    // secundaria no lo ganaba nunca.
    $stmtTotal = $pdo->prepare(
        "SELECT COUNT(*) FROM modulos WHERE activo = 1 AND grado_ciclo IN (?, 'ambos')"
    );
    $stmtTotal->execute([cicloDeEstudiante($userId)]);
    $totalCiclo = (int)$stmtTotal->fetchColumn();

    if ($totalCiclo > 0 && $totalComp >= $totalCiclo) {
        concederLogro($userId, 'todos-modulos');
    }
}

// ── Return current state ──────────────────────────────────────
echo json_encode([
    'ok'             => true,
    'siguiente_paso' => $siguientePaso,
    'completado'     => $completado,
    'cert_id'        => $certId ?? 0,
]);
