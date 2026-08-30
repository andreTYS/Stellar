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

    // Achievement: primer simulador
    if ($dur >= 10) {
        $logro = $pdo->query("SELECT id FROM logros WHERE slug='primer-sim'")->fetchColumn();
        if ($logro) {
            try {
                $pdo->prepare('INSERT IGNORE INTO usuario_logros (usuario_id,logro_id) VALUES (?,?)')->execute([$uid, $logro]);
            } catch (\Throwable $e) {}
        }
    }

    echo '{"ok":true}';
} catch (\PDOException $e) {
    echo '{"ok":false}';
}
