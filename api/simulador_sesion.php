<?php
// INNOVA-STEAM — API: update simulator session duration (called via sendBeacon)
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) { echo '{"ok":false}'; exit; }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

verifyCsrfJson(is_array($data) ? $data : null);
$id   = (int)($data['id']       ?? 0);
$dur  = (int)($data['duracion'] ?? 0);
$uid  = currentUserId();

if (!$id || $dur < 1) { echo '{"ok":false,"error":"invalid"}'; exit; }

try {
    $pdo = getDB();
    $pdo->prepare('UPDATE simulador_sesiones SET duracion_seg=?, completado=IF(?>=60,1,0) WHERE id=? AND usuario_id=?')
        ->execute([$dur, $dur, $id, $uid]);

    // ── Logros ───────────────────────────────────────────────
    // Aquí solo se concedía "primer simulador". Los otros dos que la
    // tabla promete —"Astrónomo Amateur: 5+ minutos explorando el
    // Sistema Solar" y "Meteorólogo Espacial: completaste el simulador
    // de Clima Espacial"— no se concedían en ningún sitio de la
    // plataforma: salían bloqueados para todo el mundo, para siempre.
    if ($dur >= 10) {
        concederLogro($uid, 'primer-sim');
    }

    // Cuál de los dos simuladores era esta sesión.
    $stmtSim = $pdo->prepare('SELECT simulador FROM simulador_sesiones WHERE id = ? AND usuario_id = ?');
    $stmtSim->execute([$id, $uid]);
    $simulador = (string)($stmtSim->fetchColumn() ?: '');

    if ($simulador === 'sistema-solar') {
        // Cinco minutos sumando todas las visitas, no de una sentada:
        // un niño de diez años no aguanta cinco minutos seguidos en una
        // pantalla sin tocar nada, y tampoco haría falta.
        $stmtTot = $pdo->prepare(
            'SELECT COALESCE(SUM(duracion_seg),0) FROM simulador_sesiones
              WHERE usuario_id = ? AND simulador = ?'
        );
        $stmtTot->execute([$uid, 'sistema-solar']);
        if ((int)$stmtTot->fetchColumn() >= 300) {
            concederLogro($uid, 'sim-solar-5min');
        }
    } elseif ($simulador === 'clima-espacial' && $dur >= 60) {
        // Un minuto es lo que la propia tabla considera "completado".
        concederLogro($uid, 'sim-clima');
    }

    echo '{"ok":true}';
} catch (\PDOException $e) {
    echo '{"ok":false}';
}
