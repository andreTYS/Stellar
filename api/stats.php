<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$pdo  = getDB();
$type = $_GET['type'] ?? '';

if ($type === 'completions_by_course') {
    $stmt = $pdo->query(
        'SELECT c.nombre, c.color_hex, COUNT(pe.id) as total
         FROM cursos c
         LEFT JOIN modulos m ON m.curso_id = c.id AND m.activo = 1
         LEFT JOIN progreso_estudiante pe ON pe.modulo_id = m.id AND pe.completado = 1
         WHERE c.activo = 1
         GROUP BY c.id, c.nombre, c.color_hex
         ORDER BY c.id'
    );
    $rows = $stmt->fetchAll();
    echo json_encode([
        'labels' => array_column($rows, 'nombre'),
        'values' => array_map(fn($r) => (int)$r['total'], $rows),
        'colors' => array_column($rows, 'color_hex'),
    ]);
    exit;
}

if ($type === 'daily_activity') {
    $stmt = $pdo->query(
        'SELECT DATE(completado_en) as dia, COUNT(*) as total
         FROM progreso_estudiante
         WHERE completado = 1 AND completado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP BY DATE(completado_en)
         ORDER BY dia ASC'
    );
    $rows = $stmt->fetchAll();
    // Fill in missing days with 0
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-{$i} days"));
        $data[$day] = 0;
    }
    foreach ($rows as $r) {
        $data[$r['dia']] = (int)$r['total'];
    }
    echo json_encode([
        'labels' => array_map(fn($d) => date('d/m', strtotime($d)), array_keys($data)),
        'values' => array_values($data),
    ]);
    exit;
}

if ($type === 'students_by_course') {
    $stmt = $pdo->query(
        'SELECT c.nombre, c.color_hex,
                COUNT(DISTINCT pe.estudiante_id) as estudiantes
         FROM cursos c
         LEFT JOIN modulos m ON m.curso_id = c.id
         LEFT JOIN progreso_estudiante pe ON pe.modulo_id = m.id
         WHERE c.activo = 1
         GROUP BY c.id, c.nombre, c.color_hex
         ORDER BY estudiantes DESC'
    );
    $rows = $stmt->fetchAll();
    echo json_encode([
        'labels' => array_column($rows, 'nombre'),
        'values' => array_map(fn($r) => (int)$r['estudiantes'], $rows),
        'colors' => array_column($rows, 'color_hex'),
    ]);
    exit;
}

echo json_encode(['error' => 'tipo desconocido']);
