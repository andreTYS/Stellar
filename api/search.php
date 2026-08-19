<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$q   = trim($_GET['q'] ?? '');
$rol = currentRole();

if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$pdo     = getDB();
$results = [];
$like    = "%$q%";

// ── Colegios (admin ve todos, director ve el suyo) ────────────
if (in_array($rol, ['admin', 'admin_colegio'])) {
    $stmt = $pdo->prepare(
        'SELECT id, nombre, distrito FROM colegios
         WHERE activo=1 AND (nombre LIKE ? OR distrito LIKE ?) LIMIT 5'
    );
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'type'  => 'Colegio',
            'icon'  => 'building-2',
            'label' => $row['nombre'],
            'sub'   => $row['distrito'],
            'url'   => BASE_URL . '/admin/colegios.php?edit=' . $row['id'],
        ];
    }
}

// ── Usuarios ──────────────────────────────────────────────────
if ($rol === 'admin') {
    $stmt = $pdo->prepare(
        'SELECT id, nombre, apellido, rol FROM usuarios
         WHERE activo=1 AND (nombre LIKE ? OR apellido LIKE ? OR email LIKE ?) LIMIT 6'
    );
    $stmt->execute([$like, $like, $like]);
    foreach ($stmt->fetchAll() as $row) {
        $rolEs = match($row['rol']) {
            'admin' => 'Administrador', 'admin_colegio' => 'Director',
            'docente' => 'Docente', 'practicante' => 'Practicante', 'estudiante' => 'Estudiante',
            default => $row['rol'],
        };
        $results[] = [
            'type'  => 'Usuario',
            'icon'  => 'user',
            'label' => trim($row['nombre'] . ' ' . $row['apellido']),
            'sub'   => $rolEs,
            'url'   => BASE_URL . '/admin/usuarios.php?edit=' . $row['id'],
        ];
    }
}

// ── Módulos ──────────────────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT m.id, m.titulo, c.nombre as curso_nombre, c.slug as curso_slug
     FROM modulos m JOIN cursos c ON c.id=m.curso_id
     WHERE m.activo=1 AND (m.titulo LIKE ? OR c.nombre LIKE ?) LIMIT 5'
);
$stmt->execute([$like, $like]);
foreach ($stmt->fetchAll() as $row) {
    $url = match($rol) {
        'estudiante'  => BASE_URL . '/estudiante/curso.php?slug=' . urlencode($row['curso_slug']),
        'docente'     => BASE_URL . '/docente/portafolios.php',
        'practicante' => BASE_URL . '/practicante/modulo.php?id=' . $row['id'],
        default       => BASE_URL . '/admin/modulos.php',
    };
    $results[] = [
        'type'  => 'Módulo',
        'icon'  => 'book-open',
        'label' => $row['titulo'],
        'sub'   => $row['curso_nombre'],
        'url'   => $url,
    ];
}

// ── Estudiantes (docente/practicante/admin_colegio) ───────────
if (in_array($rol, ['docente', 'practicante', 'admin_colegio', 'admin'])) {
    $stmt = $pdo->prepare(
        'SELECT u.id, u.nombre, u.apellido, c.nombre as colegio
         FROM usuarios u
         LEFT JOIN colegios c ON c.id=u.colegio_id
         WHERE u.rol="estudiante" AND u.activo=1
           AND (u.nombre LIKE ? OR u.apellido LIKE ?) LIMIT 5'
    );
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'type'  => 'Estudiante',
            'icon'  => 'graduation-cap',
            'label' => trim($row['nombre'] . ' ' . $row['apellido']),
            'sub'   => $row['colegio'] ?? '',
            'url'   => BASE_URL . '/admin/usuarios.php',
        ];
    }
}

echo json_encode(['results' => $results]);
