<?php
// ============================================================
// INNOVA-STEAM — API: Marcar paso como completado
// POST JSON { modulo_id, paso }
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

$moduloId = (int)($data['modulo_id'] ?? 0);
$paso     = (int)($data['paso']      ?? 0);   // 1–4
$user     = currentUser();
$userId   = currentUserId();

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

    // Award achievements
    try {
        $totalComp = (int)$pdo->prepare('SELECT COUNT(*) FROM progreso_estudiante WHERE estudiante_id=? AND completado=1')
            ->execute([$userId]) ? $pdo->query("SELECT COUNT(*) FROM progreso_estudiante WHERE estudiante_id=$userId AND completado=1")->fetchColumn() : 0;
        $slugs = ['primer-modulo'];
        if ($totalComp >= 5)  $slugs[] = 'explorador-aprendiz';
        if ($totalComp >= 10) $slugs[] = 'maestro-steam';
        foreach ($slugs as $slug) {
            $lid = $pdo->query("SELECT id FROM logros WHERE slug='$slug'")->fetchColumn();
            if ($lid) $pdo->prepare('INSERT IGNORE INTO usuario_logros (usuario_id,logro_id,ganado_en) VALUES (?,?,NOW())')->execute([$userId,$lid]);
        }
    } catch (\Throwable $e) {}
}

// ── Return current state ──────────────────────────────────────
echo json_encode([
    'ok'             => true,
    'siguiente_paso' => $siguientePaso,
    'completado'     => $completado,
    'cert_id'        => $certId ?? 0,
]);
