<?php
// ============================================================
// INNOVA-STEAM — API: Procesar respuestas de quiz
// POST JSON { modulo_id, respuestas: [{pregunta_id, opcion_elegida}] }
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

requireLogin('estudiante');
header('Content-Type: application/json; charset=utf-8');

// ── Input ─────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['ok' => false, 'error' => 'payload inválido']);
    exit;
}

$moduloId  = (int)($data['modulo_id'] ?? 0);
$respuestas = $data['respuestas'] ?? [];
$userId    = currentUserId();

if (!$moduloId || !is_array($respuestas) || empty($respuestas)) {
    echo json_encode(['ok' => false, 'error' => 'datos inválidos']);
    exit;
}

$pdo = getDB();

// ── Verify module exists ──────────────────────────────────────
$stmtMod = $pdo->prepare('SELECT id FROM modulos WHERE id = ? AND activo = 1');
$stmtMod->execute([$moduloId]);
if (!$stmtMod->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'módulo no encontrado']);
    exit;
}

// ── Fetch all quiz questions for this module ──────────────────
// quiz_preguntas is linked to pasos, pasos to modulo
$stmtPregs = $pdo->prepare(
    'SELECT qp.id, qp.opciones
     FROM quiz_preguntas qp
     JOIN modulo_pasos mp ON mp.id = qp.paso_id
     WHERE mp.modulo_id = ?'
);
$stmtPregs->execute([$moduloId]);
$preguntasRaw = $stmtPregs->fetchAll();

// Index by question id for quick lookup
$preguntasMap = [];
foreach ($preguntasRaw as $p) {
    $opciones = is_string($p['opciones']) ? json_decode($p['opciones'], true) : $p['opciones'];
    $preguntasMap[(int)$p['id']] = $opciones ?? [];
}

// ── Evaluate each answer ──────────────────────────────────────
$correctas = 0;
$total     = 0;
$detalle   = [];

$stmtInsert = $pdo->prepare(
    'INSERT INTO quiz_respuestas
        (estudiante_id, modulo_id, pregunta_id, opcion_elegida, es_correcta, respondido_en)
     VALUES (?, ?, ?, ?, ?, NOW())
     ON DUPLICATE KEY UPDATE
        opcion_elegida = ?,
        es_correcta    = ?,
        respondido_en  = NOW()'
);

foreach ($respuestas as $resp) {
    $preguntaId    = (int)($resp['pregunta_id']   ?? 0);
    $opcionElegida = (int)($resp['opcion_elegida'] ?? -1);

    if (!$preguntaId || !isset($preguntasMap[$preguntaId])) {
        continue;
    }

    $total++;
    $opciones  = $preguntasMap[$preguntaId];
    $esCorrecta = false;

    if (isset($opciones[$opcionElegida]) && !empty($opciones[$opcionElegida]['correcta'])) {
        $esCorrecta = true;
        $correctas++;
    }

    $stmtInsert->execute([
        $userId,
        $moduloId,
        $preguntaId,
        $opcionElegida,
        (int)$esCorrecta,
        $opcionElegida,
        (int)$esCorrecta,
    ]);

    $detalle[] = [
        'pregunta_id' => $preguntaId,
        'correcta'    => $esCorrecta,
    ];
}

// ── Calculate stars (min 1 if at least 1 correct, never 0 stars for attempt) ─
if ($correctas === $total && $total > 0) {
    $estrellas = 3;
} elseif ($correctas >= 2) {
    $estrellas = 2;
} elseif ($correctas >= 1) {
    $estrellas = 1;
} else {
    $estrellas = 1; // minimum 1 star for attempting
}

// ── Update progreso_estudiante with quiz results ──────────────
$pdo->prepare(
    'INSERT INTO progreso_estudiante
        (estudiante_id, modulo_id, estrellas_quiz, intentos_quiz)
     VALUES (?, ?, ?, 1)
     ON DUPLICATE KEY UPDATE
        estrellas_quiz = GREATEST(estrellas_quiz, ?),
        intentos_quiz  = intentos_quiz + 1'
)->execute([$userId, $moduloId, $estrellas, $estrellas]);

// ── Response ──────────────────────────────────────────────────
echo json_encode([
    'ok'        => true,
    'correctas' => $correctas,
    'total'     => $total,
    'estrellas' => $estrellas,
    'detalle'   => $detalle,
]);
