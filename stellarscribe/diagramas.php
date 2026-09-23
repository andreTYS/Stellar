<?php
// ============================================================
// StellarScribe — Diagramas de la biblioteca de ciencia
//
// El dibujo no es adorno: en varios artículos ES la explicación.
// Por eso se dibujan a mano en SVG y no se usan imágenes sueltas:
// se pueden corregir, escalan sin verse borrosos y pesan nada, que
// importa cuando la conexión del aula es la que es.
//
// Todos comparten la misma placa oscura: son diagramas de cielo, se
// leen mejor sobre fondo oscuro, y así no cambian con el tema claro
// u oscuro del navegador.
// ============================================================

declare(strict_types=1);

const DIAG_FONDO  = '#0E1A24';
const DIAG_TEXTO  = '#E6EEF5';
const DIAG_TENUE  = '#8FA3B5';
const DIAG_LINEA  = '#3C5364';

/**
 * Devuelve el SVG de un diagrama, o null si el slug no existe.
 *
 * El viewBox es siempre 0 0 400 230 para que todos ocupen el mismo
 * hueco en la página y la lectura no dé saltos.
 */
function diagramaCiencia(?string $slug): ?string
{
    if ($slug === null || $slug === '') return null;
    $fn = 'diag_' . str_replace('-', '_', preg_replace('/[^a-z0-9-]/', '', $slug));
    return function_exists($fn) ? $fn() : null;
}

/** Envoltorio común: placa, tipografía y título accesible. */
function diagMarco(string $titulo, string $contenido): string
{
    $t = htmlspecialchars($titulo, ENT_QUOTES);
    return <<<SVG
<svg viewBox="0 0 400 230" role="img" aria-label="{$t}"
     style="width:100%;height:auto;display:block;font-family:Archivo,system-ui,sans-serif">
  <title>{$t}</title>
  <rect width="400" height="230" fill="#0E1A24"/>
  {$contenido}
</svg>
SVG;
}

// ── El Sol ───────────────────────────────────────────────────────

function diag_sol_capas(): string
{
    // Corte de un cuarto de esfera: así se ven las capas sin tener que
    // dibujar media estrella tapando la otra mitad.
    return diagMarco('Corte del Sol mostrando sus capas, del núcleo a la corona', <<<SVG
  <g>
    <circle cx="120" cy="150" r="128" fill="#2B1A08" opacity=".55"/>
    <circle cx="120" cy="150" r="112" fill="#7A3E06"/>
    <circle cx="120" cy="150" r="86"  fill="#B8620A"/>
    <circle cx="120" cy="150" r="58"  fill="#E8961B"/>
    <circle cx="120" cy="150" r="30"  fill="#FFF0B8"/>
  </g>
  <!-- Rótulos, de fuera hacia dentro -->
  <g stroke="#E6EEF5" stroke-width="1" opacity=".8">
    <line x1="215" y1="52"  x2="252" y2="42"/>
    <line x1="196" y1="88"  x2="252" y2="76"/>
    <line x1="176" y1="120" x2="252" y2="110"/>
    <line x1="158" y1="146" x2="252" y2="144"/>
    <line x1="140" y1="150" x2="252" y2="178"/>
  </g>
  <g fill="#E6EEF5" font-size="11">
    <text x="258" y="46">Corona</text>
    <text x="258" y="80">Cromosfera</text>
    <text x="258" y="114">Fotosfera <tspan fill="#8FA3B5">5 500 °C</tspan></text>
    <text x="258" y="148">Zona convectiva</text>
    <text x="258" y="182">Núcleo <tspan fill="#FFD166">15 000 000 °C</tspan></text>
  </g>
  <text x="18" y="24" fill="#8FA3B5" font-size="10.5">La luz tarda miles de años en salir del núcleo</text>
SVG);
}

function diag_mancha_solar(): string
{
    return diagMarco('Una mancha solar con su umbra oscura y los arcos de campo magnético', <<<SVG
  <rect x="0" y="96" width="400" height="134" fill="#E8961B"/>
  <!-- Granulación de la fotosfera -->
  <g fill="#FFB449" opacity=".45">
    <ellipse cx="45"  cy="140" rx="22" ry="12"/><ellipse cx="105" cy="178" rx="26" ry="13"/>
    <ellipse cx="300" cy="132" rx="24" ry="12"/><ellipse cx="355" cy="186" rx="20" ry="11"/>
    <ellipse cx="240" cy="205" rx="28" ry="12"/>
  </g>
  <!-- La mancha: penumbra alrededor, umbra al centro -->
  <ellipse cx="185" cy="158" rx="62" ry="34" fill="#8A4A08"/>
  <ellipse cx="185" cy="158" rx="34" ry="19" fill="#2E1704"/>
  <!-- Arcos de campo magnético saliendo y volviendo a entrar -->
  <g fill="none" stroke="#7DD3FC" stroke-width="1.6" opacity=".9">
    <path d="M150,150 C150,62 220,62 220,150"/>
    <path d="M160,154 C160,88 210,88 210,154"/>
    <path d="M138,148 C132,40 240,40 232,148"/>
  </g>
  <g fill="#E6EEF5" font-size="11">
    <text x="16" y="28">El campo magnético sale y vuelve a entrar</text>
    <text x="16" y="46" fill="#7DD3FC" font-size="10.5">y frena el calor que sube desde abajo</text>
    <text x="150" y="222" fill="#FFE3B0">≈ 3 800 °C</text>
    <text x="300" y="222" fill="#2E1704">≈ 5 500 °C</text>
  </g>
  <line x1="185" y1="176" x2="185" y2="212" stroke="#FFE3B0" stroke-width="1"/>
  <line x1="330" y1="150" x2="330" y2="212" stroke="#2E1704" stroke-width="1"/>
SVG);
}

function diag_tormenta_solar(): string
{
    return diagMarco('Una eyección solar viaja hasta la Tierra y deforma su campo magnético', <<<SVG
  <!-- Sol -->
  <circle cx="26" cy="115" r="46" fill="#E8961B"/>
  <circle cx="26" cy="115" r="30" fill="#FFD166"/>
  <!-- Nube de partículas en expansión -->
  <g fill="none" stroke="#FF8A5B" stroke-width="2" opacity=".85">
    <path d="M86,72  C130,88 130,142 86,158"/>
    <path d="M118,60 C172,82 172,148 118,170"/>
    <path d="M152,50 C216,78 216,152 152,180"/>
  </g>
  <!-- Magnetosfera: aplastada del lado del Sol, con cola al otro -->
  <path d="M300,44 C258,78 258,152 300,186 L300,186 C352,176 392,150 398,115 C392,80 352,54 300,44 Z"
        fill="none" stroke="#7DD3FC" stroke-width="1.8" opacity=".75"/>
  <path d="M312,62 C280,86 280,144 312,168" fill="none" stroke="#7DD3FC" stroke-width="1.4" opacity=".5"/>
  <!-- Tierra -->
  <circle cx="330" cy="115" r="20" fill="#2F7FBF"/>
  <path d="M316,108 q10,-6 18,0 q8,5 12,-2" fill="none" stroke="#3ecf8e" stroke-width="3"/>
  <g fill="#E6EEF5" font-size="11">
    <text x="16" y="24">Sol</text>
    <text x="150" y="212" fill="#FF8A5B">La nube tarda de 1 a 3 días</text>
    <text x="300" y="212">Tierra</text>
    <text x="150" y="30" fill="#FFD166" font-size="10.5">La luz del destello llega en 8 minutos</text>
  </g>
SVG);
}

function diag_uv_altitud(): string
{
    return diagMarco('A más altura hay menos aire encima y llega más radiación ultravioleta', <<<SVG
  <!-- Columna de aire al nivel del mar -->
  <rect x="34"  y="34" width="130" height="150" fill="#2F7FBF" opacity=".30"/>
  <rect x="236" y="34" width="130" height="86"  fill="#2F7FBF" opacity=".30"/>
  <!-- Suelo -->
  <rect x="34"  y="184" width="130" height="14" fill="#3C5364"/>
  <rect x="236" y="120" width="130" height="78" fill="#3C5364"/>
  <!-- Rayos: los que llegan y los que se frenan -->
  <g stroke="#C084FC" stroke-width="2">
    <line x1="58"  y1="34" x2="58"  y2="184"/><line x1="86"  y1="34" x2="86"  y2="184"/>
    <line x1="114" y1="34" x2="114" y2="184"/><line x1="142" y1="34" x2="142" y2="120"/>
    <line x1="260" y1="34" x2="260" y2="120"/><line x1="288" y1="34" x2="288" y2="120"/>
    <line x1="316" y1="34" x2="316" y2="120"/><line x1="344" y1="34" x2="344" y2="120"/>
  </g>
  <g fill="#E6EEF5" font-size="11">
    <text x="34"  y="24">Nivel del mar · Ilo</text>
    <text x="236" y="24">3 000 m · Carumas</text>
    <text x="34"  y="216" fill="#8FA3B5" font-size="10.5">3 de cada 4 llegan al suelo</text>
    <text x="236" y="216" fill="#C084FC" font-size="10.5">llegan los 4 · ~35 % más</text>
  </g>
SVG);
}

// ── La Tierra ────────────────────────────────────────────────────

function diag_dia_noche(): string
{
    return diagMarco('La Tierra gira: la mitad iluminada por el Sol es de día y la otra de noche', <<<SVG
  <!-- Luz del Sol, desde la izquierda y en rayos paralelos -->
  <g stroke="#FFD166" stroke-width="2" opacity=".85">
    <line x1="10" y1="60"  x2="128" y2="60"/><line x1="10" y1="92"  x2="128" y2="92"/>
    <line x1="10" y1="124" x2="128" y2="124"/><line x1="10" y1="156" x2="128" y2="156"/>
  </g>
  <text x="10" y="40" fill="#FFD166" font-size="11">Luz del Sol</text>
  <!-- Tierra: mitad iluminada, mitad en sombra -->
  <circle cx="230" cy="112" r="76" fill="#123449"/>
  <path d="M230,36 A76,76 0 0,0 230,188 Z" fill="#2F7FBF"/>
  <!-- Eje y sentido de giro -->
  <line x1="230" y1="18" x2="230" y2="206" stroke="#8FA3B5" stroke-width="1.5" stroke-dasharray="4 4"/>
  <path d="M256,42 a34,34 0 0,1 16,22" fill="none" stroke="#E6EEF5" stroke-width="1.8"/>
  <path d="M272,64 l-9,-3 l7,-6 Z" fill="#E6EEF5"/>
  <!-- Moquegua, entrando en la tarde -->
  <circle cx="284" cy="132" r="4.5" fill="#3ecf8e"/>
  <text x="294" y="136" fill="#3ecf8e" font-size="11">Moquegua</text>
  <g fill="#E6EEF5" font-size="11">
    <text x="176" y="212">Día</text>
    <text x="258" y="212" fill="#8FA3B5">Noche</text>
    <text x="250" y="30" fill="#8FA3B5" font-size="10.5">gira una vuelta cada 24 h</text>
  </g>
SVG);
}

function diag_estaciones(): string
{
    return diagMarco('La inclinación del eje, no la distancia, causa las estaciones', <<<SVG
  <ellipse cx="200" cy="118" rx="168" ry="74" fill="none" stroke="#3C5364" stroke-width="1.2" stroke-dasharray="5 5"/>
  <circle cx="200" cy="118" r="26" fill="#FFD166"/>
  <text x="186" y="122" fill="#7A4E00" font-size="11" font-weight="700">Sol</text>

  <!-- Diciembre: el hemisferio SUR se inclina hacia el Sol -->
  <g>
    <circle cx="46" cy="118" r="30" fill="#2F7FBF"/>
    <path d="M46,118 m0,-30 a30,30 0 0,0 0,60 z" fill="#123449" opacity=".45"/>
    <line x1="34" y1="94" x2="58" y2="142" stroke="#E6EEF5" stroke-width="2"/>
    <circle cx="52" cy="132" r="3.5" fill="#3ecf8e"/>
  </g>
  <text x="16" y="182" fill="#3ecf8e" font-size="11">Diciembre</text>
  <text x="16" y="197" fill="#8FA3B5" font-size="10">verano en Perú</text>

  <!-- Junio: el hemisferio SUR se inclina hacia el lado contrario -->
  <g>
    <circle cx="354" cy="118" r="30" fill="#2F7FBF"/>
    <path d="M354,118 m0,-30 a30,30 0 0,1 0,60 z" fill="#123449" opacity=".45"/>
    <line x1="342" y1="94" x2="366" y2="142" stroke="#E6EEF5" stroke-width="2"/>
    <circle cx="348" cy="136" r="3.5" fill="#7DD3FC"/>
  </g>
  <text x="300" y="182" fill="#7DD3FC" font-size="11">Junio</text>
  <text x="300" y="197" fill="#8FA3B5" font-size="10">invierno en Perú</text>

  <text x="112" y="24" fill="#E6EEF5" font-size="11">El eje apunta SIEMPRE al mismo lado</text>
  <text x="96" y="222" fill="#8FA3B5" font-size="10.5">La órbita es casi un círculo: la distancia casi no cambia</text>
SVG);
}

function diag_placas(): string
{
    return diagMarco('La placa de Nazca se hunde bajo la Sudamericana y levanta los Andes', <<<SVG
  <!-- Mar -->
  <rect x="0" y="86" width="150" height="40" fill="#2F7FBF" opacity=".75"/>
  <!-- Manto -->
  <rect x="0" y="126" width="400" height="104" fill="#5A2E12"/>
  <!-- Placa de Nazca, hundiéndose hacia la derecha -->
  <path d="M0,112 L150,126 L232,206 L276,206 L186,120 L0,98 Z" fill="#7E8A93"/>
  <!-- Placa Sudamericana con la cordillera -->
  <path d="M150,126 L210,126 L246,72 L282,104 L330,58 L400,92 L400,230 L150,230 Z" fill="#3F4D57"/>
  <!-- Foco del sismo, donde se rompe la roca -->
  <g transform="translate(212,168)">
    <path d="M0,-13 L4,-4 L13,0 L4,4 L0,13 L-4,4 L-13,0 L-4,-4 Z" fill="#FF6B6B"/>
  </g>
  <g fill="none" stroke="#FF6B6B" stroke-width="1.4" opacity=".7">
    <circle cx="212" cy="168" r="24"/><circle cx="212" cy="168" r="40"/>
  </g>
  <g fill="#E6EEF5" font-size="11">
    <text x="12" y="76">Océano Pacífico</text>
    <text x="14" y="150" fill="#C9D4DC">Placa de Nazca</text>
    <text x="286" y="142">Placa Sudamericana</text>
    <text x="300" y="48" fill="#8FA3B5" font-size="10.5">Andes</text>
    <text x="236" y="172" fill="#FF6B6B">foco del sismo</text>
  </g>
  <path d="M60,104 l34,0 m-8,-5 l8,5 l-8,5" stroke="#E6EEF5" stroke-width="1.6" fill="none"/>
  <path d="M340,112 l-34,0 m8,-5 l-8,5 l8,5" stroke="#E6EEF5" stroke-width="1.6" fill="none"/>
SVG);
}

function diag_presion_altitud(): string
{
    return diagMarco('A 3 000 metros hay menos aire encima y el agua hierve antes', <<<SVG
  <!-- Columnas de aire: la de la izquierda mucho más alta -->
  <g fill="#2F7FBF" opacity=".28">
    <rect x="40" y="26" width="120" height="132"/>
    <rect x="242" y="86" width="120" height="72"/>
  </g>
  <!-- Moléculas de aire: muchas abajo, pocas arriba -->
  <g fill="#7DD3FC">
    <circle cx="66" cy="44" r="2.4"/><circle cx="108" cy="58" r="2.4"/><circle cx="140" cy="38" r="2.4"/>
    <circle cx="78" cy="84" r="2.6"/><circle cx="122" cy="96" r="2.6"/><circle cx="56" cy="112" r="2.8"/>
    <circle cx="100" cy="126" r="2.8"/><circle cx="144" cy="118" r="2.8"/><circle cx="82" cy="142" r="3"/>
    <circle cx="126" cy="148" r="3"/>
    <circle cx="270" cy="104" r="2.6"/><circle cx="320" cy="116" r="2.6"/><circle cx="296" cy="140" r="2.8"/>
    <circle cx="344" cy="146" r="2.8"/>
  </g>
  <!-- Suelo y ollas -->
  <rect x="40" y="158" width="120" height="10" fill="#3C5364"/>
  <rect x="242" y="158" width="120" height="10" fill="#3C5364"/>
  <g fill="#8A98A4">
    <rect x="76" y="168" width="52" height="28" rx="3"/>
    <rect x="278" y="168" width="52" height="28" rx="3"/>
  </g>
  <g fill="#7DD3FC" opacity=".9">
    <rect x="80" y="174" width="44" height="18"/>
    <rect x="282" y="174" width="44" height="18"/>
  </g>
  <g fill="#E6EEF5" font-size="11">
    <text x="40"  y="20">Nivel del mar</text>
    <text x="242" y="76">3 000 m</text>
    <text x="72"  y="216" font-size="12.5" fill="#FFD166">hierve a 100 °C</text>
    <text x="268" y="216" font-size="12.5" fill="#FF8A5B">hierve a ~90 °C</text>
  </g>
SVG);
}

// ── El cielo de noche ────────────────────────────────────────────

function diag_fases_luna(): string
{
    // La clave del diagrama: la mitad iluminada SIEMPRE mira al Sol.
    // Lo que cambia es desde dónde la miramos nosotros.
    $lunas = '';
    $pos = [
        [200, 40], [270, 62], [300, 118], [270, 174],
        [200, 196], [130, 174], [100, 118], [130, 62],
    ];
    foreach ($pos as [$x, $y]) {
        // El lado iluminado es el que da a la izquierda, donde está el Sol.
        $lunas .= <<<L
  <g>
    <circle cx="{$x}" cy="{$y}" r="14" fill="#1B2A36"/>
    <path d="M{$x},{$y} m0,-14 a14,14 0 0,0 0,28 z" fill="#E9EEF2"/>
  </g>
L;
    }

    return diagMarco('La Luna siempre tiene media cara iluminada; lo que cambia es cuánto vemos', <<<SVG
  <g stroke="#FFD166" stroke-width="2" opacity=".8">
    <line x1="6" y1="70"  x2="66" y2="70"/><line x1="6" y1="118" x2="66" y2="118"/>
    <line x1="6" y1="166" x2="66" y2="166"/>
  </g>
  <text x="6" y="52" fill="#FFD166" font-size="11">Luz del Sol</text>

  <circle cx="200" cy="118" r="120" fill="none" stroke="#3C5364" stroke-width="1" stroke-dasharray="4 5"/>
  <circle cx="200" cy="118" r="22" fill="#2F7FBF"/>
  <text x="188" y="122" fill="#fff" font-size="10">Tierra</text>
  {$lunas}
  <g fill="#8FA3B5" font-size="10.5">
    <text x="176" y="20">Luna nueva</text>
    <text x="170" y="222" fill="#E9EEF2">Luna llena</text>
  </g>
SVG);
}

function diag_via_lactea(): string
{
    return diagMarco('Vivimos dentro de un disco de estrellas; al mirarlo de canto vemos una franja', <<<SVG
  <!-- La galaxia de canto -->
  <ellipse cx="200" cy="104" rx="176" ry="20" fill="#5A6FA8" opacity=".45"/>
  <ellipse cx="200" cy="104" rx="176" ry="10" fill="#9FB4E8" opacity=".55"/>
  <circle cx="200" cy="104" r="28" fill="#FFE9B0" opacity=".65"/>
  <g fill="#FFFFFF" opacity=".9">
    <circle cx="90"  cy="100" r="1.3"/><circle cx="134" cy="108" r="1.1"/><circle cx="270" cy="100" r="1.3"/>
    <circle cx="316" cy="107" r="1.1"/><circle cx="170" cy="99" r="1"/><circle cx="242" cy="110" r="1"/>
  </g>
  <!-- Nuestro Sol, en un brazo, no en el centro -->
  <circle cx="290" cy="104" r="4.5" fill="#FFD166"/>
  <text x="256" y="86" fill="#FFD166" font-size="11">Aquí estamos</text>
  <line x1="290" y1="99" x2="290" y2="88" stroke="#FFD166" stroke-width="1"/>
  <!-- Lo que se ve desde aquí -->
  <path d="M290,110 L120,190 M290,110 L392,168" stroke="#7DD3FC" stroke-width="1.2" stroke-dasharray="3 3"/>
  <rect x="120" y="182" width="272" height="20" rx="10" fill="#9FB4E8" opacity=".35"/>
  <text x="128" y="222" fill="#7DD3FC" font-size="11">Mirando a lo largo del disco vemos muchísimas estrellas juntas</text>
SVG);
}

function diag_constelaciones_oscuras(): string
{
    return diagMarco('En los Andes se nombraron las manchas oscuras de la Vía Láctea, no solo las estrellas', <<<SVG
  <!-- Franja de la Vía Láctea -->
  <path d="M40,214 C120,150 180,110 250,40 L316,40 C250,120 190,166 112,214 Z" fill="#9FB4E8" opacity=".30"/>
  <g fill="#FFFFFF">
    <circle cx="96"  cy="188" r="1.4"/><circle cx="140" cy="158" r="1.2"/><circle cx="188" cy="124" r="1.5"/>
    <circle cx="232" cy="92"  r="1.2"/><circle cx="268" cy="62"  r="1.4"/><circle cx="130" cy="196" r="1"/>
    <circle cx="204" cy="148" r="1"/> <circle cx="262" cy="110" r="1.1"/><circle cx="170" cy="176" r="1.1"/>
  </g>
  <!-- Nube de polvo: la llama oscura -->
  <path d="M150,182 C168,150 182,146 196,120 C206,100 222,96 230,74 C236,58 248,56 256,44
           C246,44 236,52 228,64 C218,80 204,84 196,104 C186,126 170,134 158,158 Z"
        fill="#0B1218" opacity=".92"/>
  <ellipse cx="252" cy="50" rx="10" ry="7" fill="#0B1218"/>
  <g fill="#E6EEF5" font-size="11">
    <text x="16" y="28">La Yacana, la llama oscura</text>
    <text x="16" y="46" fill="#8FA3B5" font-size="10.5">No son huecos vacíos: es polvo que tapa la luz de atrás</text>
  </g>
  <line x1="150" y1="170" x2="112" y2="140" stroke="#E6EEF5" stroke-width="1" opacity=".7"/>
  <text x="40" y="136" fill="#E6EEF5" font-size="10.5">ojos</text>
SVG);
}

function diag_titilar(): string
{
    return diagMarco('La estrella se ve como un punto y titila; el planeta se ve como un disco y no', <<<SVG
  <!-- Capas de aire en movimiento -->
  <g fill="#2F7FBF" opacity=".22">
    <rect x="0" y="120" width="400" height="26"/>
    <rect x="0" y="152" width="400" height="26"/>
    <rect x="0" y="184" width="400" height="26"/>
  </g>
  <!-- Estrella: un solo rayo que se desvía -->
  <circle cx="100" cy="36" r="4" fill="#FFFFFF"/>
  <text x="66" y="22" fill="#E6EEF5" font-size="11">Estrella</text>
  <path d="M100,42 L100,118 L112,146 L92,178 L106,210" fill="none" stroke="#FFFFFF" stroke-width="1.6"/>
  <!-- Planeta: muchos rayos, los desvíos se compensan -->
  <circle cx="300" cy="36" r="10" fill="#FFB449"/>
  <text x="272" y="18" fill="#E6EEF5" font-size="11">Planeta</text>
  <g fill="none" stroke="#FFB449" stroke-width="1.3" opacity=".9">
    <path d="M292,44 L292,118 L300,146 L286,178 L296,210"/>
    <path d="M300,46 L300,118 L292,146 L306,178 L300,210"/>
    <path d="M308,44 L308,118 L316,146 L302,178 L304,210"/>
  </g>
  <g fill="#8FA3B5" font-size="10.5">
    <text x="12" y="112">Atmósfera en movimiento</text>
    <text x="52" y="226" fill="#FFFFFF">parpadea</text>
    <text x="264" y="226" fill="#FFB449">brillo estable</text>
  </g>
SVG);
}

// ── El sistema solar ─────────────────────────────────────────────

function diag_sistema_solar(): string
{
    $planetas = [
        ['Mercurio', 78,  4,  '#A9A2A0'],
        ['Venus',    102, 6,  '#E0BE7A'],
        ['Tierra',   128, 6.5,'#2F7FBF'],
        ['Marte',    152, 5,  '#C1502E'],
        ['Júpiter',  214, 15, '#D8A672'],
        ['Saturno',  268, 12, '#E4CC96'],
        ['Urano',    314, 9,  '#8FD3E0'],
        ['Neptuno',  356, 9,  '#4A6CD4'],
    ];
    $svg = '';
    foreach ($planetas as [$n, $x, $r, $c]) {
        $ty = $r > 10 ? 150 : 146;
        $svg .= "<circle cx=\"{$x}\" cy=\"118\" r=\"{$r}\" fill=\"{$c}\"/>";
        $svg .= "<text x=\"{$x}\" y=\"" . ($ty + 42) . "\" fill=\"#8FA3B5\" font-size=\"9\" text-anchor=\"middle\">{$n}</text>";
    }
    // Anillos de Saturno
    $svg .= '<ellipse cx="268" cy="118" rx="20" ry="4" fill="none" stroke="#E4CC96" stroke-width="1.6"/>';
    // Cinturón de asteroides, entre Marte y Júpiter
    $ast = '';
    for ($i = 0; $i < 26; $i++) {
        $x = 166 + ($i % 13) * 2.6;
        $y = 100 + (($i * 37) % 36);
        $ast .= "<circle cx=\"{$x}\" cy=\"{$y}\" r=\"1.1\" fill=\"#8FA3B5\"/>";
    }

    return diagMarco('El Sol y los ocho planetas en orden, con el cinturón de asteroides', <<<SVG
  <circle cx="16" cy="118" r="44" fill="#FFD166"/>
  <text x="14" y="184" fill="#FFD166" font-size="10">Sol</text>
  <line x1="60" y1="118" x2="392" y2="118" stroke="#3C5364" stroke-width="1" stroke-dasharray="3 4"/>
  {$ast}
  {$svg}
  <text x="150" y="88" fill="#8FA3B5" font-size="9" text-anchor="middle">asteroides</text>
  <text x="200" y="24" fill="#E6EEF5" font-size="11" text-anchor="middle">Los tamaños y las distancias NO están a escala</text>
  <text x="200" y="40" fill="#8FA3B5" font-size="10" text-anchor="middle">A escala real, la Tierra sería un punto invisible aquí</text>
SVG);
}

function diag_pluton(): string
{
    $obj = '';
    $puntos = [[262,64],[298,84],[330,112],[344,150],[318,182],[276,196],[238,188],[214,160],[206,124],[226,88],[352,128],[290,204]];
    foreach ($puntos as [$x, $y]) {
        $obj .= "<circle cx=\"{$x}\" cy=\"{$y}\" r=\"2.6\" fill=\"#8FA3B5\"/>";
    }
    return diagMarco('Plutón comparte su zona con muchos objetos parecidos', <<<SVG
  <circle cx="60" cy="130" r="26" fill="#FFD166"/>
  <text x="46" y="176" fill="#FFD166" font-size="10">Sol</text>
  <circle cx="118" cy="130" r="5" fill="#2F7FBF"/>
  <text x="100" y="152" fill="#8FA3B5" font-size="9">Tierra</text>
  <!-- Una órbita despejada: no hay nada más en ella -->
  <ellipse cx="60" cy="130" rx="58" ry="52" fill="none" stroke="#2F7FBF" stroke-width="1.2"/>
  <!-- La zona de Plutón, llena de cuerpos parecidos -->
  <path d="M206,124 C206,60 344,60 352,128 C356,196 226,216 214,160" fill="none"
        stroke="#3C5364" stroke-width="1.2" stroke-dasharray="4 4"/>
  {$obj}
  <circle cx="272" cy="130" r="6" fill="#D9C7B0"/>
  <text x="252" y="120" fill="#D9C7B0" font-size="11">Plutón</text>
  <g fill="#E6EEF5" font-size="10.5">
    <text x="92" y="212">órbita despejada</text>
    <text x="238" y="220" fill="#8FA3B5">zona compartida con miles de cuerpos</text>
  </g>
SVG);
}

function diag_eclipse(): string
{
    return diagMarco('En un eclipse de Sol la Luna se interpone; en uno de Luna, la Tierra', <<<SVG
  <text x="12" y="20" fill="#E6EEF5" font-size="11">Eclipse de Sol</text>
  <circle cx="30" cy="62" r="20" fill="#FFD166"/>
  <circle cx="176" cy="62" r="7" fill="#C9C9C9"/>
  <circle cx="330" cy="62" r="24" fill="#2F7FBF"/>
  <path d="M30,44 L176,56 L330,44 M30,80 L176,68 L330,80" fill="none" stroke="#FFD166" stroke-width="1" opacity=".5"/>
  <path d="M176,56 L330,72 L330,52 Z" fill="#0B1218" opacity=".85"/>
  <text x="296" y="100" fill="#8FA3B5" font-size="9.5">sombra</text>

  <text x="12" y="140" fill="#E6EEF5" font-size="11">Eclipse de Luna</text>
  <circle cx="30" cy="184" r="20" fill="#FFD166"/>
  <circle cx="200" cy="184" r="24" fill="#2F7FBF"/>
  <circle cx="340" cy="184" r="7" fill="#8A5A4A"/>
  <path d="M30,166 L200,160 M30,202 L200,208" fill="none" stroke="#FFD166" stroke-width="1" opacity=".5"/>
  <path d="M200,160 L372,176 L372,192 L200,208 Z" fill="#0B1218" opacity=".8"/>
  <text x="300" y="216" fill="#8A5A4A" font-size="9.5">la Luna se ve rojiza</text>
SVG);
}

// ── Ciencia de todos los días ────────────────────────────────────

function diag_cielo_azul(): string
{
    return diagMarco('El aire dispersa más la luz azul que la roja', <<<SVG
  <circle cx="34" cy="46" r="22" fill="#FFD166"/>
  <text x="14" y="22" fill="#FFD166" font-size="11">Sol</text>
  <!-- Atmósfera -->
  <rect x="0" y="120" width="400" height="66" fill="#2F7FBF" opacity=".22"/>
  <rect x="0" y="186" width="400" height="44" fill="#3C5364"/>
  <!-- Rayo entrando -->
  <line x1="56" y1="58" x2="200" y2="150" stroke="#FFF3C4" stroke-width="2.4"/>
  <!-- Azul dispersado en todas direcciones -->
  <g stroke="#7DD3FC" stroke-width="1.6">
    <line x1="200" y1="150" x2="140" y2="112"/><line x1="200" y1="150" x2="248" y2="108"/>
    <line x1="200" y1="150" x2="150" y2="186"/><line x1="200" y1="150" x2="268" y2="176"/>
    <line x1="200" y1="150" x2="206" y2="104"/>
  </g>
  <!-- Rojo sigue derecho -->
  <line x1="200" y1="150" x2="356" y2="186" stroke="#FF8A5B" stroke-width="2.4"/>
  <g fill="#E6EEF5" font-size="11">
    <text x="236" y="98" fill="#7DD3FC">el azul rebota por todo el cielo</text>
    <text x="250" y="212" fill="#FF8A5B">el rojo sigue de largo</text>
  </g>
  <text x="14" y="140" fill="#8FA3B5" font-size="10">Atmósfera</text>
SVG);
}

function diag_espectro(): string
{
    // Las rayas oscuras son las que delatan cada elemento.
    $barras = '';
    $colores = ['#7B2FBE','#3F46D6','#2F9BD6','#3ECF8E','#E8D44D','#E88A2A','#D9483B'];
    foreach ($colores as $i => $c) {
        $x = 176 + $i * 30;
        $barras .= "<rect x=\"{$x}\" y=\"132\" width=\"30\" height=\"44\" fill=\"{$c}\"/>";
    }
    return diagMarco('Al separar la luz de una estrella aparecen rayas oscuras que delatan sus elementos', <<<SVG
  <circle cx="30" cy="70" r="20" fill="#FFF0B8"/>
  <text x="10" y="30" fill="#E6EEF5" font-size="11">Luz de una estrella</text>
  <line x1="50" y1="76" x2="120" y2="108" stroke="#FFF3C4" stroke-width="2.4"/>
  <!-- Prisma -->
  <path d="M120,150 L152,92 L184,150 Z" fill="none" stroke="#8FA3B5" stroke-width="1.8"/>
  <text x="118" y="168" fill="#8FA3B5" font-size="10">prisma</text>
  {$barras}
  <!-- Rayas de absorción -->
  <g fill="#0B1218">
    <rect x="212" y="132" width="3" height="44"/><rect x="254" y="132" width="2.5" height="44"/>
    <rect x="300" y="132" width="3" height="44"/><rect x="336" y="132" width="2" height="44"/>
    <rect x="368" y="132" width="3" height="44"/>
  </g>
  <text x="176" y="200" fill="#E6EEF5" font-size="11">Cada raya oscura es un elemento que se tragó esa luz</text>
  <text x="176" y="216" fill="#8FA3B5" font-size="10">El patrón es distinto para cada elemento, como una huella</text>
SVG);
}

function diag_flotabilidad(): string
{
    return diagMarco('El globo de helio sube porque pesa menos que el aire que desaloja', <<<SVG
  <rect x="0" y="0" width="400" height="230" fill="#0E1A24"/>
  <!-- Aire alrededor -->
  <g fill="#7DD3FC" opacity=".55">
    <circle cx="40" cy="40" r="2.2"/><circle cx="96" cy="26" r="2.2"/><circle cx="150" cy="52" r="2.2"/>
    <circle cx="250" cy="34" r="2.2"/><circle cx="320" cy="58" r="2.2"/><circle cx="368" cy="30" r="2.2"/>
    <circle cx="62" cy="120" r="2.2"/><circle cx="330" cy="130" r="2.2"/><circle cx="210" cy="28" r="2.2"/>
  </g>
  <!-- Globo de helio -->
  <ellipse cx="120" cy="104" rx="38" ry="44" fill="#E05B6E"/>
  <path d="M120,148 l-6,14 l12,0 z" fill="#E05B6E"/>
  <line x1="120" y1="162" x2="120" y2="206" stroke="#8FA3B5" stroke-width="1.4"/>
  <g fill="#FFFFFF" opacity=".85">
    <circle cx="106" cy="92" r="2"/><circle cx="132" cy="108" r="2"/><circle cx="118" cy="124" r="2"/>
  </g>
  <path d="M120,52 l0,-26 m-7,9 l7,-9 l7,9" stroke="#3ECF8E" stroke-width="2.4" fill="none"/>
  <text x="74" y="222" fill="#E6EEF5" font-size="11">Helio: ligero</text>

  <!-- Globo de aire -->
  <ellipse cx="290" cy="128" rx="38" ry="44" fill="#4A6CD4"/>
  <path d="M290,172 l-6,14 l12,0 z" fill="#4A6CD4"/>
  <g fill="#7DD3FC" opacity=".9">
    <circle cx="276" cy="116" r="2.2"/><circle cx="302" cy="132" r="2.2"/><circle cx="288" cy="148" r="2.2"/>
    <circle cx="298" cy="110" r="2.2"/><circle cx="278" cy="144" r="2.2"/>
  </g>
  <path d="M290,186 l0,22 m-7,-9 l7,9 l7,-9" stroke="#FF8A5B" stroke-width="2.4" fill="none"/>
  <text x="242" y="222" fill="#E6EEF5" font-size="11">Aire: pesa igual</text>
SVG);
}
