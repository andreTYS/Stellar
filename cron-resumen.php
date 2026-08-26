<?php
// ============================================================
// INNOVA-STEAM — Resumen semanal para apoderados
//
// Uso:  php cron-resumen.php [--dry-run]
// Cron: 0 18 * * 0  php /ruta/al/proyecto/cron-resumen.php
//
// Un apoderado solo ve el progreso de su hijo si entra a buscarlo, y
// rara vez lo hace. Este script empuja el resumen hacia él.
//
// Dos canales: la notificación in-app siempre se crea; el correo solo
// se envía si el apoderado tiene email Y el servidor tiene un MTA
// configurado. Sin MTA el script sigue funcionando — no se pierde el
// resumen, solo el envío.
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se ejecuta desde la línea de comandos.\n");
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$pdo    = getDB();

if ($dryRun) {
    echo "── Modo de prueba: no se escribe ni se envía nada ──\n\n";
}

// Apoderados activos con al menos un hijo vinculado.
$apoderados = $pdo->query("
    SELECT DISTINCT u.id, u.nombre, u.apellido, u.email
      FROM usuarios u
      JOIN apoderado_estudiante ae ON ae.apoderado_id = u.id AND ae.activo = 1
     WHERE u.rol = 'apoderado' AND u.activo = 1
")->fetchAll();

if (!$apoderados) {
    echo "No hay apoderados con hijos vinculados.\n";
    exit(0);
}

// Actividad de la semana de un estudiante concreto.
$stmtActividad = $pdo->prepare("
    SELECT u.nombre, u.apellido, u.ultimo_acceso,
           (SELECT COUNT(*) FROM progreso_estudiante pe
             WHERE pe.estudiante_id = u.id
               AND pe.completado = 1
               AND pe.completado_en > (NOW() - INTERVAL 7 DAY))          AS modulos_semana,
           (SELECT COALESCE(SUM(pe.estrellas_quiz), 0) FROM progreso_estudiante pe
             WHERE pe.estudiante_id = u.id)                              AS estrellas_total,
           (SELECT COUNT(*) FROM entregables e
             WHERE e.estudiante_id = u.id
               AND e.subido_en > (NOW() - INTERVAL 7 DAY))               AS entregas_semana,
           (SELECT COUNT(*) FROM entregables e
             WHERE e.estudiante_id = u.id
               AND e.calificacion IS NOT NULL
               AND e.calificacion > 0)                                   AS calificados
      FROM usuarios u
     WHERE u.id = ?
");

$stmtHijos = $pdo->prepare(
    'SELECT estudiante_id FROM apoderado_estudiante WHERE apoderado_id = ? AND activo = 1'
);

$enviados = 0;
$fallos   = 0;

foreach ($apoderados as $ap) {
    $stmtHijos->execute([$ap['id']]);
    $hijos = $stmtHijos->fetchAll(PDO::FETCH_COLUMN);

    $lineas    = [];
    $hayActividad = false;

    foreach ($hijos as $hijoId) {
        $stmtActividad->execute([$hijoId]);
        $h = $stmtActividad->fetch();
        if (!$h) continue;

        $nombre  = trim($h['nombre'] . ' ' . $h['apellido']);
        $modulos = (int)$h['modulos_semana'];
        $entregas = (int)$h['entregas_semana'];

        if ($modulos > 0 || $entregas > 0) {
            $hayActividad = true;
            $partes = [];
            if ($modulos > 0)  $partes[] = "$modulos módulo"   . ($modulos  === 1 ? '' : 's') . ' completado' . ($modulos === 1 ? '' : 's');
            if ($entregas > 0) $partes[] = "$entregas entrega" . ($entregas === 1 ? '' : 's');
            $lineas[] = "• {$nombre}: " . implode(' y ', $partes)
                      . '. Lleva ' . (int)$h['estrellas_total'] . ' estrellas en total.';
        } else {
            $dias = $h['ultimo_acceso'] === null
                ? null
                : (int)floor((time() - strtotime($h['ultimo_acceso'])) / 86400);
            $lineas[] = $dias === null
                ? "• {$nombre}: todavía no ha entrado a la plataforma."
                : "• {$nombre}: sin actividad esta semana (última conexión hace {$dias} día" . ($dias === 1 ? '' : 's') . ').';
        }
    }

    if (!$lineas) continue;

    $titulo  = $hayActividad ? 'Resumen semanal de tus hijos' : 'Tus hijos no han entrado esta semana';
    $cuerpo  = implode("\n", $lineas);

    echo "→ {$ap['nombre']} {$ap['apellido']}"
       . ($ap['email'] ? " <{$ap['email']}>" : ' (sin correo)') . "\n"
       . $cuerpo . "\n\n";

    if ($dryRun) { $enviados++; continue; }

    // 1) Notificación in-app — siempre.
    try {
        $pdo->prepare(
            'INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, url)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $ap['id'],
            'mensaje',
            $titulo,
            $cuerpo,
            BASE_URL . '/apoderado/index.php',
        ]);
    } catch (\Throwable $e) {
        echo "  ! No se pudo crear la notificación: {$e->getMessage()}\n";
        $fallos++;
        continue;
    }

    // 2) Correo — solo si hay dirección y el servidor puede enviarlo.
    if (!empty($ap['email'])) {
        $asunto  = '[INNOVA-STEAM] ' . $titulo;
        $mensaje = "Hola {$ap['nombre']},\n\n{$cuerpo}\n\n"
                 . "Puedes ver el detalle completo en la plataforma.\n\n"
                 . "— INNOVA-STEAM\n";
        $cabeceras = "From: INNOVA-STEAM <no-responder@innovasteam.pe>\r\n"
                   . "Content-Type: text/plain; charset=UTF-8\r\n";

        // mail() devuelve false sin MTA configurado. No es un error del
        // resumen: la notificación in-app ya quedó guardada.
        if (!@mail($ap['email'], $asunto, $mensaje, $cabeceras)) {
            echo "  · Correo no enviado (¿MTA sin configurar?). La notificación in-app sí se creó.\n";
        }
    }

    $enviados++;
}

echo "── Resúmenes procesados: {$enviados}"
   . ($fallos ? " · fallos: {$fallos}" : '') . " ──\n";
