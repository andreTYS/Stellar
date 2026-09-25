<?php
// ============================================================
// INNOVA-STEAM — Buscador global (Ctrl+K)
//
// Devuelve JSON: {"results": [{type, icon, label, sub, url}, ...]}
//
// Dos reglas que antes no se cumplían:
//
//   1. Cada quien busca dentro de lo suyo. Un docente que escribía dos
//      letras recibía estudiantes de todos los colegios de la
//      plataforma: nombres y apellidos de menores de otro centro.
//      Ahora el docente ve los de SUS aulas, el director los de SU
//      colegio y solo el admin lo ve todo.
//
//   2. Cada resultado va a una página que existe y que quien busca
//      puede abrir. Los módulos llevaban al estudiante a
//      curso.php?slug=…, que lee el id y no el slug, así que abría
//      el curso 0; y los usuarios a usuarios.php?edit=N, que esa
//      página no atiende.
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$q   = trim((string)($_GET['q'] ?? ''));
$rol = currentRole();
$uid = currentUserId();

if (mb_strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

// % y _ son comodines de LIKE: sin escaparlos, buscar "%" devolvía la
// plataforma entera y "a_a" casaba con cualquier cosa.
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q) . '%';

$pdo     = getDB();
$results = [];

/** Colegio al que pertenece quien busca (null para el admin global). */
$miColegio = null;
if ($rol !== 'admin') {
    $st = $pdo->prepare('SELECT colegio_id FROM usuarios WHERE id = ?');
    $st->execute([$uid]);
    $miColegio = (int)($st->fetchColumn() ?: 0) ?: null;
}

// ── Colegios ─────────────────────────────────────────────────
if ($rol === 'admin') {
    $st = $pdo->prepare(
        'SELECT id, nombre, distrito FROM colegios
          WHERE activo = 1 AND (nombre LIKE ? OR distrito LIKE ?) LIMIT 5'
    );
    $st->execute([$like, $like]);
    foreach ($st->fetchAll() as $r) {
        $results[] = [
            'type'  => 'Colegio', 'icon' => 'building-2',
            'label' => $r['nombre'], 'sub' => $r['distrito'] ?: '',
            'url'   => BASE_URL . '/admin/colegios.php?edit=' . (int)$r['id'],
        ];
    }
}

// ── Usuarios (solo el admin de plataforma) ───────────────────
if ($rol === 'admin') {
    $st = $pdo->prepare(
        'SELECT nombre, apellido, rol FROM usuarios
          WHERE activo = 1 AND (nombre LIKE ? OR apellido LIKE ? OR email LIKE ? OR codigo_acceso LIKE ?)
          ORDER BY apellido LIMIT 6'
    );
    $st->execute([$like, $like, $like, $like]);
    foreach ($st->fetchAll() as $r) {
        $nombre = trim($r['nombre'] . ' ' . $r['apellido']);
        $results[] = [
            'type'  => 'Usuario', 'icon' => 'user',
            'label' => $nombre,
            'sub'   => match ($r['rol']) {
                'admin'         => 'Administrador',
                'admin_colegio' => 'Director',
                'docente'       => 'Docente',
                'practicante'   => 'Practicante',
                'estudiante'    => 'Estudiante',
                'apoderado'     => 'Apoderado',
                default         => $r['rol'],
            },
            // usuarios.php no atiende ?edit=; el buscador de esa misma
            // página sí, y deja la fila delante de quien la buscaba.
            // Se busca por apellido: el buscador de esa página mira
            // nombre y apellido por separado, no la cadena completa.
            'url'   => BASE_URL . '/admin/usuarios.php?q=' . rawurlencode($r['apellido']),
        ];
    }
}

// ── Estudiantes, cada quien dentro de su ámbito ──────────────
if (in_array($rol, ['docente', 'practicante', 'admin_colegio'], true)) {
    if ($rol === 'admin_colegio') {
        $sql = 'SELECT DISTINCT u.id, u.nombre, u.apellido, u.codigo_acceso,
                       a.nivel, a.grado, a.seccion, a.anio_escolar
                  FROM usuarios u
             LEFT JOIN estudiante_aula ea ON ea.estudiante_id = u.id
             LEFT JOIN aulas a            ON a.id = ea.aula_id
                 WHERE u.rol = "estudiante" AND u.activo = 1
                   AND u.colegio_id = ?
                   AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.codigo_acceso LIKE ?)
                 ORDER BY u.apellido LIMIT 6';
        $params = [$miColegio ?? 0, $like, $like, $like];
    } else {
        // Solo las aulas que este docente o practicante tiene asignadas.
        $tabla  = $rol === 'docente' ? 'aulas a2' : 'practicante_aula pa';
        $enlace = $rol === 'docente'
            ? 'a2.id = ea.aula_id AND a2.docente_id = ?'
            : 'pa.aula_id = ea.aula_id AND pa.practicante_id = ?';
        $sql = "SELECT DISTINCT u.id, u.nombre, u.apellido, u.codigo_acceso,
                       a.nivel, a.grado, a.seccion, a.anio_escolar
                  FROM usuarios u
                  JOIN estudiante_aula ea ON ea.estudiante_id = u.id
                  JOIN $tabla ON $enlace
                  JOIN aulas a ON a.id = ea.aula_id
                 WHERE u.rol = 'estudiante' AND u.activo = 1
                   AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.codigo_acceso LIKE ?)
                 ORDER BY u.apellido LIMIT 6";
        $params = [$uid, $like, $like, $like];
    }

    $st = $pdo->prepare($sql);
    $st->execute($params);
    foreach ($st->fetchAll() as $r) {
        $aula = $r['grado'] !== null ? aulaLabel($r) : 'Sin aula';
        $results[] = [
            'type'  => 'Estudiante', 'icon' => 'graduation-cap',
            'label' => trim($r['apellido'] . ', ' . $r['nombre']),
            'sub'   => $aula . ($r['codigo_acceso'] ? ' · ' . $r['codigo_acceso'] : ''),
            // practicante/ no tiene estudiantes.php: su lista de
            // estudiantes es la hoja de asistencia.
            'url'   => match ($rol) {
                'admin_colegio' => BASE_URL . '/admin_colegio/aulas.php',
                'docente'       => BASE_URL . '/docente/estudiantes.php',
                default         => BASE_URL . '/practicante/asistencia.php',
            },
        ];
    }
}

// ── Módulos del catálogo ─────────────────────────────────────
// El apoderado no estudia módulos: para él no son un resultado útil.
if ($rol !== 'apoderado') {
    $st = $pdo->prepare(
        'SELECT m.id, m.titulo, m.curso_id, c.nombre AS curso
           FROM modulos m JOIN cursos c ON c.id = m.curso_id
          WHERE m.activo = 1 AND (m.titulo LIKE ? OR c.nombre LIKE ?)
          ORDER BY c.id, m.orden LIMIT 5'
    );
    $st->execute([$like, $like]);
    foreach ($st->fetchAll() as $r) {
        $results[] = [
            'type'  => 'Módulo', 'icon' => 'book-open',
            'label' => $r['titulo'], 'sub' => $r['curso'],
            'url'   => match ($rol) {
                // curso.php lee id, no slug: con slug abría el curso 0.
                'estudiante'  => BASE_URL . '/estudiante/curso.php?id=' . (int)$r['curso_id'],
                'docente'     => BASE_URL . '/docente/portafolios.php',
                'practicante' => BASE_URL . '/practicante/modulo.php?id=' . (int)$r['id'],
                default       => BASE_URL . '/admin/modulos.php',
            },
        ];
    }
}

// ── Biblioteca de ciencia ────────────────────────────────────
// Lo que de verdad busca un estudiante ("luna", "eclipse") vivía aquí y
// el buscador no lo miraba.
$st = $pdo->prepare(
    'SELECT a.slug, a.pregunta, t.nombre AS tema
       FROM ciencia_articulos a JOIN ciencia_temas t ON t.id = a.tema_id
      WHERE a.pregunta LIKE ? OR a.respuesta_corta LIKE ? OR t.nombre LIKE ?
      ORDER BY a.pregunta LIMIT 5'
);
$st->execute([$like, $like, $like]);
foreach ($st->fetchAll() as $r) {
    $results[] = [
        'type'  => 'Ciencia', 'icon' => 'telescope',
        'label' => $r['pregunta'], 'sub' => $r['tema'],
        'url'   => BASE_URL . '/stellarscribe/articulo.php?a=' . rawurlencode($r['slug']),
    ];
}

echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
