<?php
// ============================================================
// INNOVA-STEAM — Devolver el demo a su estado inicial
//
//   php herramientas/reiniciar_demo.php
//
// Pensado para una demostración pública: cualquiera que entre con las
// cuentas de prueba puede calificar, borrar un aula o subir cualquier
// cosa. Esto lo deja todo como recién instalado, para que el siguiente
// que abra el enlace lo vea entero.
//
// Ponlo en el cron del hosting una vez al día:
//
//     0 4 * * *  /usr/bin/php /ruta/al/proyecto/herramientas/reiniciar_demo.php
//
// Si el hosting no tiene cron pero sí PHP, se puede llamar por web
// definiendo DEMO_TOKEN_REINICIO en includes/config.local.php y
// abriendo reiniciar_demo.php?token=... . Sin ese token definido la
// vía web está cerrada: es un script que BORRA la base entera.
//
// NO lo dejes instalado en un colegio de verdad.
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

$porWeb = PHP_SAPI !== 'cli';

if ($porWeb) {
    header('Content-Type: text/plain; charset=utf-8');

    $esperado = defined('DEMO_TOKEN_REINICIO') ? (string)DEMO_TOKEN_REINICIO : '';
    $recibido = (string)($_GET['token'] ?? '');

    // hash_equals y no ==: comparar tal cual deja medir el tiempo y
    // adivinar el token carácter a carácter.
    if ($esperado === '' || !hash_equals($esperado, $recibido)) {
        http_response_code(403);
        exit("No.\n");
    }
}

$raiz = dirname(__DIR__);
$t0   = microtime(true);

function paso(string $texto): void
{
    echo $texto, "\n";
    if (PHP_SAPI !== 'cli') { @ob_flush(); @flush(); }
}

/**
 * Quita del SQL las sentencias que fijan el nombre de la base.
 *
 * Los archivos dicen «CREATE DATABASE innovasteam» y «USE innovasteam»,
 * y en un hosting compartido la base ya existe, se llama otra cosa y no
 * te dejan crear ninguna. Se quitan esas dos y el resto entra en la
 * base a la que ya estamos conectados.
 *
 * Solo esas dos: los correos del tipo admin@innovasteam.edu.pe que hay
 * repartidos por el seed se quedan como están.
 */
function sinNombreDeBase(string $sql): string
{
    $sql = preg_replace('/^\s*CREATE\s+DATABASE\b.*?;/ims', '', $sql) ?? $sql;
    return preg_replace('/^\s*USE\s+`?\w+`?\s*;/im', '', $sql) ?? $sql;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // ── 1. Vaciar ────────────────────────────────────────────
    // Se listan las tablas de information_schema en vez de escribir la
    // lista a mano. Una lista escrita a mano se queda corta en cuanto
    // alguien añade una migración, y entonces el DROP muere a media
    // ejecución por una clave foránea. Ya pasó dos veces.
    $tablas = $pdo->query(
        'SELECT table_name FROM information_schema.tables
          WHERE table_schema = DATABASE() AND table_type = "BASE TABLE"'
    )->fetchAll(PDO::FETCH_COLUMN);

    if ($tablas) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tablas as $t) {
            $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '', $t) . '`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    paso(sprintf('1/4  %d tablas borradas', count($tablas)));

    // ── 2. Esquema y migraciones ─────────────────────────────
    $archivos = array_merge(
        [$raiz . '/schema.sql'],
        glob($raiz . '/migrations/[0-9]*.sql') ?: []
    );
    sort($archivos, SORT_NATURAL);
    // schema.sql primero, aunque el orden natural lo pusiera en otro sitio.
    usort($archivos, static fn($a, $b) => (int)(basename($a) !== 'schema.sql')
                                        <=> (int)(basename($b) !== 'schema.sql'));

    foreach ($archivos as $f) {
        $pdo->exec(sinNombreDeBase((string)file_get_contents($f)));
    }
    paso(sprintf('2/4  esquema y %d migraciones aplicadas', count($archivos) - 1));

    // ── 3. Datos de demostración ─────────────────────────────
    $pdo->exec(sinNombreDeBase((string)file_get_contents($raiz . '/seed_data.sql')));
    $usuarios = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    paso(sprintf('3/4  datos de demostración: %d usuarios', $usuarios));

    // ── 4. Archivos que subió la gente ───────────────────────
    // Las fotos de entregables se acumulan visita tras visita. Solo se
    // tocan las de esa carpeta, y solo las que son imágenes.
    $borrados = 0;
    foreach (glob($raiz . '/uploads/entregables/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [] as $img) {
        if (is_file($img) && @unlink($img)) $borrados++;
    }
    paso(sprintf('4/4  %d archivos subidos eliminados', $borrados));

    paso(sprintf("\nDemo reiniciado en %.1f s.", microtime(true) - $t0));

} catch (Throwable $e) {
    // El mensaje de la base puede llevar credenciales o nombres internos:
    // al log entero, a la pantalla solo lo justo.
    error_log('reiniciar_demo: ' . $e->getMessage());
    if ($porWeb) {
        http_response_code(500);
        exit("No se pudo reiniciar. Revisa el log del servidor.\n");
    }
    fwrite(STDERR, 'FALLÓ: ' . $e->getMessage() . "\n");
    exit(1);
}
