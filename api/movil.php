<?php
// ============================================================
// INNOVA-STEAM — Datos para la app móvil
//
//   GET /api/movil.php?recurso=inicio            (estudiante)
//   GET /api/movil.php?recurso=curso&id=N        (estudiante)
//   GET /api/movil.php?recurso=hijos             (apoderado)
//
// Todo devuelve JSON y exige Authorization: Bearer <token>.
//
// Cada recurso responde en UNA consulta por bloque, no por elemento:
// la app se usará con conexión mala y cada viaje extra se nota.
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_auth.php';

$recurso = $_GET['recurso'] ?? '';
$pdo     = getDB();

switch ($recurso) {

    // ── Pantalla de inicio del estudiante ────────────────────
    case 'inicio': {
        $u  = requireApiAuth('estudiante');
        $id = (int)$u['id'];

        $cursos = $pdo->prepare("
            SELECT c.id, c.nombre, c.descripcion, c.color_hex,
                   COUNT(DISTINCT m.id) AS total_modulos,
                   COUNT(DISTINCT CASE WHEN pe.completado = 1 THEN pe.modulo_id END) AS completados
              FROM cursos c
         LEFT JOIN modulos m  ON m.curso_id = c.id
         LEFT JOIN progreso_estudiante pe
                    ON pe.modulo_id = m.id AND pe.estudiante_id = ?
          GROUP BY c.id, c.nombre, c.descripcion, c.color_hex
          ORDER BY c.id
        ");
        $cursos->execute([$id]);
        $cursos = $cursos->fetchAll();

        $totalMod = array_sum(array_column($cursos, 'total_modulos'));
        $compMod  = array_sum(array_column($cursos, 'completados'));

        apiJson([
            'ok'      => true,
            'usuario' => apiUsuarioPublico($u),
            'resumen' => [
                'estrellas'           => (int)getEstrellasTotal($id),
                'modulos_totales'     => (int)$totalMod,
                'modulos_completados' => (int)$compMod,
                'progreso_pct'        => $totalMod > 0 ? (int)round($compMod / $totalMod * 100) : 0,
            ],
            'cursos'  => array_map(static function (array $c): array {
                $total = (int)$c['total_modulos'];
                $comp  = (int)$c['completados'];
                return [
                    'id'          => (int)$c['id'],
                    'nombre'      => $c['nombre'],
                    'descripcion' => $c['descripcion'],
                    'color'       => $c['color_hex'] ?: '#4361ee',
                    'modulos'     => $total,
                    'completados' => $comp,
                    'progreso_pct'=> $total > 0 ? (int)round($comp / $total * 100) : 0,
                ];
            }, $cursos),
        ]);
    }

    // ── Un curso con sus módulos ─────────────────────────────
    case 'curso': {
        $u       = requireApiAuth('estudiante');
        $id      = (int)$u['id'];
        $cursoId = (int)($_GET['id'] ?? 0);
        if ($cursoId <= 0) apiError('Falta el id del curso.', 422);

        $curso = $pdo->prepare('SELECT id, nombre, descripcion, color_hex FROM cursos WHERE id = ?');
        $curso->execute([$cursoId]);
        $curso = $curso->fetch();
        if (!$curso) apiError('Curso no encontrado.', 404);

        // Todo el progreso del curso de una vez, igual que en la web.
        $progreso = getProgresoPorCurso($id, $cursoId);
        $modulos  = getModulosByCurso($cursoId);

        $salida = [];
        $anteriorCompletado = true;   // el primero siempre se desbloquea
        foreach ($modulos as $i => $m) {
            $p          = $progreso[(int)$m['id']] ?? null;
            $completado = !empty($p['completado']);

            $salida[] = [
                'id'           => (int)$m['id'],
                'titulo'       => $m['titulo'],
                'numero'       => $i + 1,
                'completado'   => $completado,
                'en_progreso'  => $p !== null && !$completado,
                'desbloqueado' => $i === 0 ? true : $anteriorCompletado,
                'paso_actual'  => $p !== null ? (int)$p['paso_actual'] : 0,
                'estrellas'    => $p !== null ? (int)$p['estrellas_quiz'] : 0,
            ];
            $anteriorCompletado = $completado;
        }

        apiJson([
            'ok'    => true,
            'curso' => [
                'id'          => (int)$curso['id'],
                'nombre'      => $curso['nombre'],
                'descripcion' => $curso['descripcion'],
                'color'       => $curso['color_hex'] ?: '#4361ee',
            ],
            'modulos' => $salida,
        ]);
    }

    // ── Hijos vinculados a un apoderado ──────────────────────
    case 'hijos': {
        $u = requireApiAuth('apoderado');

        $hijos = $pdo->prepare("
            SELECT u.id, u.nombre, u.apellido, u.ultimo_acceso,
                   DATEDIFF(NOW(), u.ultimo_acceso) AS dias_sin_entrar,
                   ae.relacion,
                   COALESCE(pc.total, 0) AS modulos_completados,
                   COALESCE(pe.total, 0) AS estrellas,
                   COALESCE(ul.total, 0) AS logros
              FROM apoderado_estudiante ae
              JOIN usuarios u ON u.id = ae.estudiante_id
         LEFT JOIN (SELECT estudiante_id, COUNT(*) total
                      FROM progreso_estudiante WHERE completado = 1
                     GROUP BY estudiante_id) pc ON pc.estudiante_id = u.id
         LEFT JOIN (SELECT estudiante_id, SUM(estrellas_quiz) total
                      FROM progreso_estudiante GROUP BY estudiante_id) pe ON pe.estudiante_id = u.id
         LEFT JOIN (SELECT usuario_id, COUNT(*) total
                      FROM usuario_logros GROUP BY usuario_id) ul ON ul.usuario_id = u.id
             WHERE ae.apoderado_id = ? AND ae.activo = 1
          ORDER BY u.apellido, u.nombre
        ");
        $hijos->execute([(int)$u['id']]);

        apiJson([
            'ok'    => true,
            'hijos' => array_map(static function (array $h): array {
                return [
                    'id'          => (int)$h['id'],
                    'nombre'      => trim($h['nombre'] . ' ' . $h['apellido']),
                    'relacion'    => $h['relacion'],
                    'completados' => (int)$h['modulos_completados'],
                    'estrellas'   => (int)$h['estrellas'],
                    'logros'      => (int)$h['logros'],
                    // null = nunca ha entrado; la app decide cómo mostrarlo
                    'dias_sin_entrar' => $h['ultimo_acceso'] === null ? null : (int)$h['dias_sin_entrar'],
                ];
            }, $hijos->fetchAll()),
        ]);
    }

    default:
        apiError('Recurso no reconocido. Usa inicio, curso o hijos.', 404);
}
