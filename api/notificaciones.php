<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$pdo    = getDB();
$uid    = currentUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Graceful: if table doesn't exist yet, return empty
try { $pdo->query('SELECT 1 FROM notificaciones LIMIT 1'); }
catch (\PDOException $e) { echo json_encode(['items' => [], 'unread' => 0]); exit; }

if ($action === 'list') {
    $stmt = $pdo->prepare(
        'SELECT id, tipo, titulo, mensaje, url, leida, created_at
         FROM notificaciones WHERE usuario_id=? ORDER BY created_at DESC LIMIT 20'
    );
    $stmt->execute([$uid]);
    $items = $stmt->fetchAll();

    $unread = (int)$pdo->prepare('SELECT COUNT(*) FROM notificaciones WHERE usuario_id=? AND leida=0')
                       ->execute([$uid]) ? (int)$pdo->query("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=$uid AND leida=0")->fetchColumn() : 0;

    echo json_encode(['items' => $items, 'unread' => $unread]);
    exit;
}

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('UPDATE notificaciones SET leida=1 WHERE id=? AND usuario_id=?')->execute([$id, $uid]);
    } else {
        $pdo->prepare('UPDATE notificaciones SET leida=1 WHERE usuario_id=?')->execute([$uid]);
    }
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['error' => 'acción desconocida']);
