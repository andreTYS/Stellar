<?php
// INNOVA-STEAM — API: registrar progreso de capítulo StellarScribe
// POST JSON { historia?, capitulo, tiempo_seg, quiz_score? }
//
// Escribía en capitulo_num, quiz_score y leido_en, tres columnas que no
// existen: cada capítulo leído se perdía y el catch devolvía 'db_error'
// sin que nadie lo viera. Las columnas reales son capitulo_slug,
// puntos_quiz y completado_en.
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) { echo '{"ok":false,"error":"not_logged_in"}'; exit; }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

verifyCsrfJson(is_array($data) ? $data : null);

$capitulo  = (int)($data['capitulo']  ?? 0);
$tiempoSeg = (int)($data['tiempo_seg'] ?? 0);
$quizScore = isset($data['quiz_score']) ? (int)round((float)$data['quiz_score']) : null;

// Con dos historias hace falta saber de cuál es el capítulo; si no, el
// capítulo 1 de una pisa al de la otra. Las anteriores son de 'aldrin'.
$historia = strtolower(trim((string)($data['historia'] ?? 'aldrin')));
if (!preg_match('/^[a-z0-9_-]{1,40}$/', $historia)) $historia = 'aldrin';

$uid = currentUserId();

if ($capitulo < 1 || $capitulo > 99) { echo '{"ok":false,"error":"invalid_chapter"}'; exit; }

$slug = 'cap' . $capitulo;

try {
    $pdo = getDB();
    $pdo->prepare(
        'INSERT INTO capitulos_progreso
             (usuario_id, historia_slug, capitulo_slug, tiempo_seg, puntos_quiz, completado, completado_en)
         VALUES (?, ?, ?, ?, ?, 1, NOW())
         ON DUPLICATE KEY UPDATE
           tiempo_seg    = GREATEST(tiempo_seg, VALUES(tiempo_seg)),
           puntos_quiz   = GREATEST(puntos_quiz, COALESCE(VALUES(puntos_quiz), 0)),
           completado    = 1,
           completado_en = NOW()'
    )->execute([$uid, $historia, $slug, $tiempoSeg, $quizScore]);

    // Capítulos leídos de ESTA historia, para los logros de abajo.
    $stmt = $pdo->prepare(
        'SELECT COUNT(DISTINCT capitulo_slug) FROM capitulos_progreso
          WHERE usuario_id = ? AND historia_slug = ?'
    );
    $stmt->execute([$uid, $historia]);
    $total = (int)$stmt->fetchColumn();

    // Los slugs son 'primer-capitulo' y 'todos-capitulos'. El código
    // buscaba 'explorador-estelar', que es el NOMBRE del logro, no su
    // slug: la consulta no devolvía nada y nunca se otorgaba ninguno.
    $otorgar = [];
    if ($total >= 1) $otorgar[] = 'primer-capitulo';
    if ($total >= 4) $otorgar[] = 'todos-capitulos';

    if ($otorgar) {
        $sel = $pdo->prepare('SELECT id FROM logros WHERE slug = ?');
        $ins = $pdo->prepare('INSERT IGNORE INTO usuario_logros (usuario_id, logro_id, obtenido_en) VALUES (?,?,NOW())');
        foreach ($otorgar as $sl) {
            $sel->execute([$sl]);
            $lid = $sel->fetchColumn();
            if ($lid) $ins->execute([$uid, $lid]);
        }
    }

    echo json_encode(['ok' => true, 'historia' => $historia, 'capitulos_leidos' => $total]);
} catch (\Throwable $e) {
    // El detalle al log: antes se perdía entero y por eso el fallo de
    // columnas sobrevivió sin que nadie lo notara.
    error_log('api/capitulo_progreso: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el progreso.']);
}
