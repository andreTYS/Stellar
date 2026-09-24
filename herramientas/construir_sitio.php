<?php
/**
 * ============================================================
 * INNOVA-STEAM — Generador del sitio público estático
 *
 * GitHub Pages solo sirve archivos: no ejecuta PHP ni tiene base
 * de datos. La plataforma completa —login, aulas, calificación,
 * asistencia, el asistente— necesita un servidor de verdad y NO
 * puede vivir aquí.
 *
 * Lo que sí puede, y es justo lo que conviene tener público:
 *
 *   · La portada de presentación
 *   · Las dos historias de StellarScribe, jugables
 *   · Los dos simuladores, que ya funcionaban en el navegador
 *   · La biblioteca de ciencia: 18 artículos con sus diagramas
 *
 * Todo eso se lee sin cuenta y sin servidor. Es la cara pública
 * del proyecto: lo que se le pasa a un director por WhatsApp.
 *
 * Uso:  php herramientas/construir_sitio.php [--base=/Stellar]
 * ============================================================
 */

declare(strict_types=1);

// config.php arranca la sesión, y para cuando se incluye este script
// ya ha impreso su primera línea. Se arranca aquí, antes de nada.
if (session_status() === PHP_SESSION_NONE) @session_start();

$raiz   = dirname(__DIR__);
$salida = $raiz . '/_site';

// Ruta base en la que se publica. En GitHub Pages de proyecto el
// sitio cuelga de /<repo>, no de la raíz del dominio; si esto no
// coincide, todos los enlaces y los estilos se rompen.
$base = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base=')) $base = rtrim(substr($arg, 7), '/');
}
if ($base === '' && getenv('PAGES_BASE')) $base = rtrim((string)getenv('PAGES_BASE'), '/');

echo "Base pública: " . ($base === '' ? '(raíz)' : $base) . "\n";

// ── Utilidades ───────────────────────────────────────────────

function rmrf(string $ruta): void
{
    if (!file_exists($ruta)) return;
    if (is_file($ruta) || is_link($ruta)) { unlink($ruta); return; }
    foreach (scandir($ruta) as $e) {
        if ($e === '.' || $e === '..') continue;
        rmrf($ruta . '/' . $e);
    }
    rmdir($ruta);
}

function copiarDir(string $de, string $a): int
{
    if (!is_dir($de)) return 0;
    @mkdir($a, 0755, true);
    $n = 0;
    foreach (scandir($de) as $e) {
        if ($e === '.' || $e === '..') continue;
        $origen = $de . '/' . $e;
        $destino = $a . '/' . $e;
        if (is_dir($origen)) {
            $n += copiarDir($origen, $destino);
        } else {
            copy($origen, $destino);
            $n++;
        }
    }
    return $n;
}

function escribir(string $ruta, string $contenido): void
{
    @mkdir(dirname($ruta), 0755, true);
    file_put_contents($ruta, $contenido);
}

// ── Preparar la carpeta ──────────────────────────────────────

rmrf($salida);
mkdir($salida, 0755, true);

// Sin esto GitHub Pages pasa el sitio por Jekyll, que ignora todo
// archivo o carpeta que empiece por guion bajo.
escribir($salida . '/.nojekyll', '');

$copiados  = copiarDir($raiz . '/assets', $salida . '/assets');
$copiados += copiarDir($raiz . '/stellarscribe/img',    $salida . '/stellarscribe/img');
$copiados += copiarDir($raiz . '/stellarscribe/images', $salida . '/stellarscribe/images');
$copiados += copiarDir($raiz . '/stellarscribe/assets', $salida . '/stellarscribe/assets');
$copiados += copiarDir($raiz . '/stellarscribe/api',    $salida . '/stellarscribe/api');
// historias.js lleva los datos de las dos historias. Sin él el motor
// se queda sin nada que contar y la página muere con un mensaje de
// error: es el archivo más fácil de olvidar y el más imprescindible.
foreach (['chap4-bg.jpg','escena3.gif','escena3_1.jpg','escena4.gif','monolith.gif','historias.js'] as $suelto) {
    if (is_file($raiz . '/stellarscribe/' . $suelto)) {
        copy($raiz . '/stellarscribe/' . $suelto, $salida . '/stellarscribe/' . $suelto);
    }
}
echo "Recursos copiados: {$copiados}\n";

// ── Datos ────────────────────────────────────────────────────
// El sitio se genera con los datos reales si hay base de datos, y
// con las cifras del catálogo si no. Que falte la base no debe
// impedir publicar: en el runner de GitHub no habrá ninguna.

require_once $raiz . '/stellarscribe/diagramas.php';

$temas = $articulos = [];
$hayBase = false;
try {
    $pdo = new PDO('mysql:host=localhost;dbname=innovasteam;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $temas     = $pdo->query('SELECT * FROM ciencia_temas ORDER BY orden')->fetchAll();
    $articulos = $pdo->query(
        'SELECT a.*, t.slug AS tema_slug, t.nombre AS tema_nombre, t.color_hex, t.icono
           FROM ciencia_articulos a JOIN ciencia_temas t ON t.id = a.tema_id
          WHERE a.activo = 1 ORDER BY t.orden, a.orden'
    )->fetchAll();
    $hayBase = true;
} catch (\Throwable $e) {
    echo "AVISO: sin base de datos. Se usará el volcado de contenido.\n";
}

// Sin base de datos se lee el volcado que deja este mismo script la
// última vez que sí la hubo. Así el runner de GitHub puede construir
// el sitio sin levantar un MySQL.
$volcado = $raiz . '/herramientas/contenido_ciencia.json';
if ($hayBase) {
    escribir($volcado, json_encode(
        ['temas' => $temas, 'articulos' => $articulos],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ));
    echo "Volcado de contenido actualizado.\n";
} elseif (is_file($volcado)) {
    $d = json_decode((string)file_get_contents($volcado), true) ?: [];
    $temas     = $d['temas']     ?? [];
    $articulos = $d['articulos'] ?? [];
    echo "Contenido leído del volcado: " . count($articulos) . " artículos.\n";
}

if (!$articulos) {
    fwrite(STDERR, "ERROR: no hay artículos ni en la base ni en el volcado.\n");
    exit(1);
}

// ── Plantilla pública ────────────────────────────────────────
// Deliberadamente NO se reutiliza includes/header.php: ese trae la
// barra lateral de la aplicación, con enlaces a portafolio,
// certificados y mensajes que aquí no existirían. Un sitio público
// lleno de enlaces rotos es peor que no tener sitio.

function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function pagina(string $titulo, string $descripcion, string $cuerpo, string $base, string $prefijo = ''): string
{
    $t = esc($titulo);
    $d = esc($descripcion);
    $css = $base . '/assets/publico.css';
    $inicio = $base === '' ? './' : $base . '/';

    return <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$t}</title>
<meta name="description" content="{$d}">
<meta property="og:title" content="{$t}">
<meta property="og:description" content="{$d}">
<meta property="og:type" content="website">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,300;0,6..72,400;0,6..72,500;1,6..72,400&family=Archivo:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{$css}">
</head>
<body>
<header class="cabecera-sitio">
  <a class="marca" href="{$inicio}">
    <span class="marca-nombre">Innova&#8209;STEAM</span>
    <span class="marca-lugar">Moquegua</span>
  </a>
  <nav>
    <a href="{$base}/ciencia/">Biblioteca</a>
    <a href="{$base}/stellarscribe/">StellarScribe</a>
    <a href="{$base}/acceso.html">Acceso</a>
  </nav>
</header>

{$cuerpo}

<footer class="pie-sitio">
  <div>
    <strong>INNOVA-STEAM</strong> · Plataforma educativa STEAM para colegios de Moquegua
  </div>
  <p>
    Este sitio es la parte pública del proyecto: se lee sin cuenta y sin
    servidor. La plataforma completa —aulas, calificación con rúbrica,
    asistencia, portafolios y el asistente— corre en el servidor del colegio.
    <a href="{$base}/acceso.html">Cómo acceder</a>.
  </p>
</footer>
</body>
</html>
HTML;
}

// ── Hoja de estilos del sitio público ────────────────────────

escribir($salida . '/assets/publico.css', <<<'CSS'
/* Estilos del sitio público. Misma paleta del valle que el resto del
   material del proyecto: verde irrigado sobre el ocre del desierto,
   bajo el cielo limpio de Moquegua. */
:root {
  --noche:#0F1A20; --noche-2:#16242C; --claro:#E8EFE9; --claro-tenue:#93A6A0;
  --papel:#EFF1ED; --superficie:#FFFFFF; --borde:#D8DED4;
  --texto:#16211C; --texto-suave:#56655E; --texto-tenue:#86928B;
  --valle:#2E6B4C; --valle-vivo:#8FD6AC; --ocre:#92602B;
  --caja: min(100% - 2.5rem, 64rem);
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --papel:#0B1310; --superficie:#121C17; --borde:#22302A;
    --texto:#E6EDE7; --texto-suave:#A5B2AA; --texto-tenue:#77867E;
    --valle:#6FC291; --ocre:#D3A063;
  }
}
* { box-sizing:border-box; }
body {
  margin:0; background:var(--papel); color:var(--texto);
  font-family:'Archivo',system-ui,sans-serif; line-height:1.62;
  -webkit-font-smoothing:antialiased;
}
h1,h2,h3 { font-family:'Newsreader',Georgia,serif; font-weight:400; letter-spacing:-.012em; margin:0; text-wrap:balance; }
a { color:var(--valle); }
p { margin:0 0 1.1rem; }

.cabecera-sitio {
  display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
  width:var(--caja); margin-inline:auto; padding:1.15rem 0;
  border-bottom:1px solid var(--borde);
}
.marca { display:flex; align-items:baseline; gap:.6rem; text-decoration:none; color:var(--texto); }
.marca-nombre { font-weight:700; font-size:.98rem; letter-spacing:.11em; text-transform:uppercase; }
.marca-lugar { font-family:'Newsreader',serif; font-style:italic; color:var(--texto-tenue); }
.cabecera-sitio nav { display:flex; gap:1.15rem; flex-wrap:wrap; }
.cabecera-sitio nav a { font-size:.92rem; font-weight:500; text-decoration:none; color:var(--texto-suave); }
.cabecera-sitio nav a:hover { color:var(--valle); }

main { width:var(--caja); margin-inline:auto; padding:2.5rem 0 3.5rem; }

.intro h1 { font-size:clamp(1.9rem,4.6vw,2.9rem); line-height:1.14; max-width:20ch; margin-bottom:.9rem; }
.intro p { color:var(--texto-suave); max-width:52ch; font-size:1.05rem; }

.rotulo { display:block; font-size:.68rem; font-weight:700; letter-spacing:.15em;
  text-transform:uppercase; color:var(--texto-tenue); margin-bottom:.7rem; }

/* Biblioteca */
.tema { margin-top:2.75rem; }
.tema-cab { display:flex; align-items:center; gap:.65rem; padding-bottom:.7rem; margin-bottom:1.1rem; border-bottom:2px solid var(--borde); }
.tema-cab h2 { font-size:1.35rem; }
.tema-cab .cuenta { margin-left:auto; font-size:.78rem; color:var(--texto-tenue); }
.rejilla { display:grid; grid-template-columns:repeat(auto-fill,minmax(16.5rem,1fr)); gap:.9rem; }
.ficha {
  display:flex; flex-direction:column; background:var(--superficie);
  border:1px solid var(--borde); border-left:3px solid var(--acento,var(--valle));
  border-radius:10px; padding:1rem 1.1rem .9rem; text-decoration:none;
  transition:transform .16s, border-color .16s;
}
.ficha:hover { transform:translateY(-2px); border-color:var(--acento,var(--valle)); }
.ficha h3 { font-family:'Archivo',sans-serif; font-size:.98rem; font-weight:600; line-height:1.35; color:var(--texto); margin-bottom:.4rem; }
.ficha p { font-size:.85rem; color:var(--texto-suave); line-height:1.5; margin:0 0 .75rem; flex:1; }
.ficha .min { font-size:.72rem; color:var(--texto-tenue); }

/* Artículo */
.articulo { max-width:42rem; margin-inline:auto; }
.articulo .volver { display:inline-block; font-size:.85rem; font-weight:600; text-decoration:none; margin-bottom:1.1rem; }
.articulo h1 { font-size:clamp(1.65rem,4.2vw,2.35rem); line-height:1.18; margin-bottom:.6rem; }
.articulo .meta { font-size:.78rem; color:var(--texto-tenue); margin-bottom:1.5rem; }
.articulo .respuesta {
  font-size:clamp(1.05rem,2.5vw,1.2rem); line-height:1.55; font-weight:500;
  border-left:3px solid var(--acento,var(--valle)); padding-left:1.05rem; margin-bottom:1.75rem;
}
.articulo figure { margin:0 0 1.75rem; border:1px solid var(--borde); border-radius:10px; overflow:hidden; }
.articulo .cuerpo { font-size:1rem; line-height:1.72; margin-bottom:1.1rem; }
.dato { background:var(--superficie); border:1px solid var(--borde); border-radius:10px; padding:1.1rem 1.25rem; margin:1.75rem 0; }
.dato .rotulo { color:var(--ocre); }
.dato p { font-size:.92rem; margin:0; }
.actividad { border:1.5px solid color-mix(in srgb, var(--acento,var(--valle)) 45%, transparent); border-radius:10px; padding:1.3rem 1.45rem; margin:2rem 0; }
.actividad .rotulo { color:var(--acento,var(--valle)); }
.actividad h2 { font-family:'Archivo',sans-serif; font-size:1.08rem; font-weight:700; margin-bottom:.7rem; }
.actividad .materiales { font-size:.86rem; color:var(--texto-suave); margin-bottom:.9rem; }
.actividad ol { margin:0; padding-left:1.15rem; }
.actividad li { font-size:.93rem; line-height:1.62; margin-bottom:.55rem; }
.nav-art { display:grid; grid-template-columns:1fr 1fr; gap:.7rem; margin-top:2.25rem; padding-top:1.4rem; border-top:1px solid var(--borde); }
.nav-art a { display:block; padding:.85rem 1rem; border:1px solid var(--borde); border-radius:9px; text-decoration:none; }
.nav-art a:hover { border-color:var(--acento,var(--valle)); }
.nav-art span { display:block; font-size:.66rem; font-weight:700; letter-spacing:.11em; text-transform:uppercase; color:var(--texto-tenue); margin-bottom:.2rem; }
.nav-art strong { display:block; font-size:.86rem; font-weight:600; line-height:1.35; color:var(--texto); }
.nav-art .sig { text-align:right; }
@media (max-width:40rem){ .nav-art{grid-template-columns:1fr} .nav-art .sig{text-align:left} }

/* Tarjetas grandes de experiencia */
.experiencias { display:grid; grid-template-columns:repeat(auto-fit,minmax(17rem,1fr)); gap:1rem; margin-top:1.5rem; }
.exp {
  display:block; text-decoration:none; border-radius:12px; overflow:hidden;
  border:1px solid var(--borde); background:var(--superficie);
  transition:transform .16s, border-color .16s;
}
.exp:hover { transform:translateY(-2px); border-color:var(--valle); }
.exp .tapa { height:6.5rem; background:linear-gradient(135deg,#0F1A20,#1C3040); display:flex; align-items:center; justify-content:center; }
.exp .tapa span { color:var(--valle-vivo); font-size:.72rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase; }
.exp .txt { padding:1rem 1.15rem 1.15rem; }
.exp h3 { font-size:1.08rem; margin-bottom:.35rem; }
.exp p { font-size:.86rem; color:var(--texto-suave); margin:0; }

.aviso {
  border:1px solid var(--ocre); border-radius:8px; padding:1.1rem 1.25rem;
  margin:1.75rem 0; font-size:.93rem;
}
.aviso .rotulo { color:var(--ocre); }
.aviso p { color:var(--texto-suave); margin:0; }

.pie-sitio {
  border-top:1px solid var(--borde); margin-top:2rem;
  width:var(--caja); margin-inline:auto; padding:1.75rem 0 3rem;
  font-size:.85rem; color:var(--texto-tenue);
}
.pie-sitio strong { color:var(--texto-suave); }
.pie-sitio p { max-width:56ch; margin:.6rem 0 0; }
CSS);

// ── Biblioteca: índice ───────────────────────────────────────

$porTema = [];
foreach ($articulos as $a) {
    $porTema[$a['tema_slug']]['tema'] = $a;
    $porTema[$a['tema_slug']]['items'][] = $a;
}

$html = '<main><div class="intro">'
      . '<span class="rotulo">Biblioteca de ciencia</span>'
      . '<h1>Preguntas cortas con respuesta clara.</h1>'
      . '<p>Cada artículo responde una sola pregunta, trae un dibujo que la explica '
      . 'y algo que puedes hacer en casa. Entra por la que te dé curiosidad.</p>'
      . '</div>';

foreach ($porTema as $slug => $g) {
    $color = esc($g['tema']['color_hex']);
    $html .= '<section class="tema"><div class="tema-cab" style="border-bottom-color:' . $color . '55">'
           . '<h2 style="color:' . $color . '">' . esc($g['tema']['tema_nombre']) . '</h2>'
           . '<span class="cuenta">' . count($g['items']) . '</span></div><div class="rejilla">';
    foreach ($g['items'] as $a) {
        $html .= '<a class="ficha" style="--acento:' . $color . '" href="' . $base . '/ciencia/' . esc($a['slug']) . '.html">'
               . '<h3>' . esc($a['pregunta']) . '</h3>'
               . '<p>' . esc(mb_strimwidth($a['respuesta_corta'], 0, 118, '…')) . '</p>'
               . '<span class="min">' . (int)$a['minutos_lectura'] . ' min de lectura</span></a>';
    }
    $html .= '</div></section>';
}
$html .= '</main>';

escribir($salida . '/ciencia/index.html', pagina(
    'Biblioteca de ciencia — INNOVA-STEAM',
    'Dieciocho artículos de ciencia con diagramas y experimentos para hacer en casa, anclados en Moquegua.',
    $html, $base
));

// ── Biblioteca: un archivo por artículo ──────────────────────

$n = 0;
foreach ($articulos as $i => $a) {
    $color  = esc($a['color_hex']);
    $cuerpo = json_decode((string)$a['cuerpo'], true) ?: [];
    $act    = $a['actividad'] ? (json_decode((string)$a['actividad'], true) ?: []) : [];
    $diag   = diagramaCiencia($a['diagrama']);

    // Anterior y siguiente dentro del mismo tema.
    $hermanos = array_values(array_filter($articulos, fn($x) => $x['tema_slug'] === $a['tema_slug']));
    $pos = array_search($a['slug'], array_column($hermanos, 'slug'), true);
    $ant = $pos > 0 ? $hermanos[$pos - 1] : null;
    $sig = $hermanos[$pos + 1] ?? null;

    $c = '<main><article class="articulo" style="--acento:' . $color . '">'
       . '<a class="volver" style="color:' . $color . '" href="' . $base . '/ciencia/">&larr; '
       . esc($a['tema_nombre']) . '</a>'
       . '<h1>' . esc($a['pregunta']) . '</h1>'
       . '<div class="meta">' . (int)$a['minutos_lectura'] . ' min de lectura</div>'
       . '<p class="respuesta">' . esc($a['respuesta_corta']) . '</p>';

    if ($diag) $c .= '<figure>' . $diag . '</figure>';
    foreach ($cuerpo as $p) $c .= '<p class="cuerpo">' . esc((string)$p) . '</p>';

    if (!empty($a['dato_curioso'])) {
        $c .= '<aside class="dato"><span class="rotulo">Dato curioso</span><p>'
            . esc($a['dato_curioso']) . '</p></aside>';
    }

    if (!empty($act['titulo'])) {
        $c .= '<section class="actividad"><span class="rotulo">Hazlo tú</span>'
            . '<h2>' . esc($act['titulo']) . '</h2>';
        if (!empty($act['materiales'])) {
            $c .= '<p class="materiales"><strong>Necesitas:</strong> '
                . esc(implode(' · ', array_map('strval', $act['materiales']))) . '</p>';
        }
        $c .= '<ol>';
        foreach (($act['pasos'] ?? []) as $p) $c .= '<li>' . esc((string)$p) . '</li>';
        $c .= '</ol></section>';
    }

    $c .= '<nav class="nav-art">';
    $c .= $ant
        ? '<a href="' . $base . '/ciencia/' . esc($ant['slug']) . '.html"><span>Anterior</span><strong>' . esc($ant['pregunta']) . '</strong></a>'
        : '<span></span>';
    if ($sig) {
        $c .= '<a class="sig" href="' . $base . '/ciencia/' . esc($sig['slug']) . '.html"><span>Siguiente</span><strong>' . esc($sig['pregunta']) . '</strong></a>';
    }
    $c .= '</nav></article></main>';

    escribir($salida . '/ciencia/' . $a['slug'] . '.html', pagina(
        $a['pregunta'] . ' — INNOVA-STEAM',
        mb_strimwidth($a['respuesta_corta'], 0, 155, '…'),
        $c, $base
    ));
    $n++;
}
echo "Artículos generados: {$n}\n";

// ── StellarScribe: portada de experiencias ───────────────────

$exp = '<main><div class="intro">'
     . '<span class="rotulo">StellarScribe</span>'
     . '<h1>Dos historias y dos simuladores sobre el Sol.</h1>'
     . '<p>Proyecto presentado al NASA Space Apps Challenge 2025 por un equipo moqueguano. '
     . 'Funciona entero en el navegador: no hace falta cuenta.</p></div>'
     . '<div class="experiencias">'
     . '<a class="exp" href="' . $base . '/stellarscribe/aldrin.html"><div class="tapa"><span>Historia 1</span></div>'
     . '<div class="txt"><h3>El Despertar de Aldrin</h3><p>Un astronauta varado en un planeta helado. '
     . 'Tormentas solares, electrólisis y baterías caseras.</p></div></a>'
     . '<a class="exp" href="' . $base . '/stellarscribe/tormenta.html"><div class="tapa"><span>Historia 2</span></div>'
     . '<div class="txt"><h3>La noche sin señal</h3><p>Una tormenta solar apaga la radio y tuerce el GPS en Carumas. '
     . 'El clima espacial visto desde el valle.</p></div></a>'
     . '<a class="exp" href="' . $base . '/ciencia/"><div class="tapa"><span>Biblioteca</span></div>'
     . '<div class="txt"><h3>18 artículos de ciencia</h3><p>Por qué el agua hierve antes en Carumas, '
     . 'por qué julio es invierno aquí, las constelaciones oscuras andinas.</p></div></a>'
     . '</div>'
     . '<div class="aviso"><span class="rotulo">Sobre los simuladores</span>'
     . '<p>Los simuladores del sistema solar y del clima espacial forman parte de la plataforma '
     . 'y registran el tiempo de uso de cada estudiante, así que necesitan servidor y cuenta. '
     . 'No están en este sitio público.</p></div>'
     . '</main>';

escribir($salida . '/stellarscribe/index.html', pagina(
    'StellarScribe — INNOVA-STEAM',
    'Dos historias interactivas y una biblioteca de ciencia sobre el Sol, el clima espacial y el cielo de Moquegua.',
    $exp, $base
));

// ── StellarScribe: las dos historias ─────────────────────────
// El motor se RENDERIZA con PHP en vez de recortarlo con expresiones
// regulares. Un primer intento hizo lo segundo y una regex perezosa se
// llevó por delante mil líneas: desde un comentario de la cabecera
// hasta el condicional del vídeo de intro, con el CSS y la pantalla de
// inicio en medio. Dejar que PHP resuelva el PHP no tiene ese riesgo.

foreach (['aldrin' => 'El Despertar de Aldrin', 'tormenta' => 'La noche sin señal'] as $slug => $titulo) {
    $_GET['historia'] = $slug;

    ob_start();
    include $raiz . '/stellarscribe/historia.php';
    $h = (string)ob_get_clean();

    if ($h === '' || !str_contains($h, 'startScreen')) {
        fwrite(STDERR, "ERROR: la historia {$slug} no se renderizó completa.\n");
        exit(1);
    }

    // Lo que queda por quitar ya es JavaScript, no PHP: el envío de
    // progreso, que sin sesión no tiene a quién registrarse.
    $h = preg_replace(
        '/\s*\/\/ Track chapter progress.*?\{ once: true \}\);/s',
        "\n    // Sitio público: sin servidor no hay progreso que registrar.",
        $h
    );
    $h = preg_replace('/window\.CSRF_TOKEN\s*=\s*"[^"]*";/', 'window.CSRF_TOKEN = "";', $h);

    if (str_contains($h, '<?')) {
        fwrite(STDERR, "ERROR: quedó PHP sin resolver en la historia {$slug}.\n");
        exit(1);
    }
    if (str_contains($h, 'capitulo_progreso')) {
        fwrite(STDERR, "ERROR: la historia {$slug} sigue llamando al servidor.\n");
        exit(1);
    }

    escribir($salida . '/stellarscribe/' . $slug . '.html', $h);
}
if (!is_file($salida . '/stellarscribe/historias.js')) {
    fwrite(STDERR, "ERROR: falta historias.js; las historias no tendrían contenido.\n");
    exit(1);
}
echo "Historias generadas: 2\n";

// ── Portada del sitio ────────────────────────────────────────

$totalArt = count($articulos);
$portada = '<main><div class="intro">'
    . '<span class="rotulo">Plataforma educativa STEAM · Moquegua</span>'
    . '<h1>La ciencia que sus estudiantes ya tienen delante.</h1>'
    . '<p>INNOVA-STEAM es una plataforma STEAM para colegios donde cada módulo empieza '
    . 'con una persona del valle y un problema real: un precio en el mercado, un recibo '
    . 'de luz, un andén que hay que regar.</p></div>'

    . '<div class="experiencias">'
    . '<a class="exp" href="' . $base . '/ciencia/"><div class="tapa"><span>Para leer ahora</span></div>'
    . '<div class="txt"><h3>Biblioteca de ciencia</h3><p>' . $totalArt . ' artículos con diagramas '
    . 'y experimentos para hacer en casa. Sin cuenta.</p></div></a>'
    . '<a class="exp" href="' . $base . '/stellarscribe/"><div class="tapa"><span>Para jugar</span></div>'
    . '<div class="txt"><h3>StellarScribe</h3><p>Dos historias interactivas sobre el Sol y el clima '
    . 'espacial, del NASA Space Apps Challenge 2025.</p></div></a>'
    . '<a class="exp" href="' . $base . '/acceso.html"><div class="tapa"><span>Para colegios</span></div>'
    . '<div class="txt"><h3>La plataforma completa</h3><p>35 módulos, rúbricas, asistencia, '
    . 'portafolios y app móvil. Cómo acceder.</p></div></a>'
    . '</div>'

    . '<div class="aviso"><span class="rotulo">Qué es y qué no es este sitio</span>'
    . '<p>Esto es la cara pública del proyecto. La plataforma completa necesita un servidor '
    . 'con PHP y base de datos, y corre en el colegio o en su nube: aquí no hay login, '
    . 'ni aulas, ni calificación.</p></div>'
    . '</main>';

escribir($salida . '/index.html', pagina(
    'INNOVA-STEAM — Plataforma educativa STEAM para colegios de Moquegua',
    'Plataforma STEAM con 35 módulos anclados en problemas reales del valle, biblioteca de ciencia y StellarScribe.',
    $portada, $base
));

// ── Página de acceso ─────────────────────────────────────────

$acceso = '<main><div class="intro">'
    . '<span class="rotulo">Acceso</span>'
    . '<h1>La plataforma corre en el servidor del colegio.</h1>'
    . '<p>Este sitio público es solo una parte. Todo lo que necesita cuentas y datos '
    . '—estudiantes, aulas, calificación, asistencia, portafolios, el asistente— vive '
    . 'en una instalación propia de cada institución.</p></div>'

    . '<h2 style="margin:2.25rem 0 .9rem">Lo que incluye la plataforma completa</h2>'
    . '<ul style="padding-left:1.2rem;color:var(--texto-suave)">'
    . '<li style="margin-bottom:.5rem">35 módulos de 5.º de primaria a 5.º de secundaria, con sus cuatro pasos y su rúbrica</li>'
    . '<li style="margin-bottom:.5rem">Seis roles: del director al apoderado, en un solo circuito</li>'
    . '<li style="margin-bottom:.5rem">Asistencia desde el celular, visible para el docente y la familia</li>'
    . '<li style="margin-bottom:.5rem">Portafolio del estudiante y calificación con rúbrica de cuatro criterios</li>'
    . '<li style="margin-bottom:.5rem">Funciona sin internet estable: lo enviado sin señal se reintenta solo</li>'
    . '<li style="margin-bottom:.5rem">App móvil para estudiante, apoderado y practicante</li>'
    . '</ul>'

    . '<div class="aviso" style="border-color:var(--valle)"><span class="rotulo" style="color:var(--valle)">Cómo instalarla</span>'
    . '<p>El código es abierto y está en el repositorio del proyecto. Necesita PHP 8 y MySQL '
    . 'o MariaDB. El repositorio incluye los scripts de instalación para Linux y para Windows '
    . 'con XAMPP, y la documentación de puesta en marcha.</p></div>'

    . '<div class="aviso"><span class="rotulo">Para conversar sobre un piloto</span>'
    . '<p>La propuesta para colegios es un aula durante un bimestre, con licencia anual y '
    . 'usuarios ilimitados. Escriba al equipo del proyecto a través del repositorio.</p></div>'
    . '</main>';

escribir($salida . '/acceso.html', pagina(
    'Acceso a la plataforma — INNOVA-STEAM',
    'La plataforma completa corre en el servidor del colegio. Qué incluye y cómo instalarla.',
    $acceso, $base
));

// ── 404 ──────────────────────────────────────────────────────

escribir($salida . '/404.html', pagina(
    'Página no encontrada — INNOVA-STEAM',
    'Esa página no existe en el sitio público.',
    '<main><div class="intro"><span class="rotulo">Error 404</span>'
    . '<h1>Esa página no está aquí.</h1>'
    . '<p>Puede que estés buscando una parte de la plataforma que necesita servidor y cuenta. '
    . 'Prueba con la <a href="' . $base . '/ciencia/">biblioteca de ciencia</a> o con '
    . '<a href="' . $base . '/stellarscribe/">StellarScribe</a>.</p></div></main>',
    $base
));

// ── Resumen ──────────────────────────────────────────────────

$cuenta = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($salida));
foreach ($it as $f) if ($f->isFile()) $cuenta++;

echo "\nSitio generado en _site/ — {$cuenta} archivos\n";
