<?php
// INNOVA-STEAM — API: registrar progreso de capítulo StellarScribe
// POST JSON { capitulo, tiempo_seg, quiz_score? }
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) { echo '{"ok":false,"error":"not_logged_in"}'; exit; }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$capitulo  = (int)($data['capitulo']  ?? 0);
$tiempoSeg = (int)($data['tiempo_seg'] ?? 0);
$quizScore = isset($data['quiz_score']) ? (float)$data['quiz_score'] : null;
$uid       = currentUserId();

if ($capitulo < 1) { echo '{"ok":false,"error":"invalid_chapter"}'; exit; }

try {
    $pdo = getDB();
    $pdo->prepare(
        'INSERT INTO capitulos_progreso (usuario_id, capitulo_num, tiempo_seg, quiz_score, leido_en)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
           tiempo_seg  = GREATEST(tiempo_seg, VALUES(tiempo_seg)),
           quiz_score  = COALESCE(VALUES(quiz_score), quiz_score),
           leido_en    = NOW()'
    )->execute([$uid, $capitulo, $tiempoSeg, $quizScore]);

    // Award logro for reading all 4 chapters
    $total = (int)$pdo->query("SELECT COUNT(DISTINCT capitulo_num) FROM capitulos_progreso WHERE usuario_id=$uid")->fetchColumn();
    if ($total >= 4) {
        $lid = $pdo->query("SELECT id FROM logros WHERE slug='explorador-estelar'")->fetchColumn();
        if ($lid) $pdo->prepare('INSERT IGNORE INTO usuario_logros (usuario_id,logro_id,ganado_en) VALUES (?,?,NOW())')->execute([$uid,$lid]);
    }

    echo json_encode(['ok' => true, 'capitulos_leidos' => $total]);
} catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'db_error']);
}
