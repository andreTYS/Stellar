<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$uid = currentUserId();
$pdo = getDB();

// Load NASA data
$apiDir = __DIR__ . '/../api/';
$weather = json_decode(file_get_contents($apiDir . 'current_space_weather.json'), true) ?? [];
$flares  = json_decode(file_get_contents($apiDir . 'basic_flare_data.json'),       true) ?? [];
$cmes    = json_decode(file_get_contents($apiDir . 'basic_cme_data.json'),          true) ?? [];

$kpNow      = (float)($weather['kp_index']      ?? 3.2);
$windSpeed  = (int)  ($weather['solar_wind_speed'] ?? 450);
$fluxNow    = (float)($weather['solar_flux_f107'] ?? 148);
$bFieldNow  = (float)($weather['magnetic_field_bt'] ?? 5.2);
$sunspotNow = (int)  ($weather['sunspot_number'] ?? 82);

// Log session
try {
    $pdo->prepare('INSERT INTO simulador_sesiones (usuario_id, simulador) VALUES (?,?)')
        ->execute([$uid, 'clima-espacial']);
    $sesionId = (int)$pdo->lastInsertId();
} catch (\PDOException $e) { $sesionId = 0; }

$pageTitle = 'Clima Espacial — Simulador';
$activeNav = 'stellarscribe';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.sim-grid   { display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:24px }
.sim-card   { background:#02040d;border:1px solid #1a2340;border-radius:16px;padding:20px }
.sim-h      { font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:#5c7ab0;text-transform:uppercase;letter-spacing:.06em;margin-bottom:16px }
.gauge-wrap { position:relative;width:160px;height:90px;margin:0 auto 12px }
.gauge-label{ text-align:center;font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:#e8f0fe }
.gauge-sub  { text-align:center;font-size:11px;color:#5c7ab0;margin-top:2px }
.bar-row    { display:flex;align-items:center;gap:10px;margin-bottom:10px }
.bar-label  { font-size:12px;color:#8898aa;width:80px;flex-shrink:0 }
.bar-track  { flex:1;height:8px;background:#0f1629;border-radius:99px;overflow:hidden }
.bar-fill   { height:100%;border-radius:99px;transition:width .4s }
.event-row  { display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid #0f1629 }
.event-row:last-child { border:none }
.ev-badge   { border-radius:6px;padding:2px 8px;font-size:10px;font-weight:800;flex-shrink:0;margin-top:2px }
.scenario-btn{
    width:100%;background:#0f1629;border:1px solid #1a2340;border-radius:10px;
    padding:10px 14px;text-align:left;cursor:pointer;margin-bottom:8px;
    color:#c7d9ff;font-size:13px;display:flex;align-items:center;gap:10px;
    transition:border-color .15s,background .15s;
}
.scenario-btn:hover{background:#131d35;border-color:#4361ee}
</style>

<div class="page-action-header">
  <div>
    <h1 class="page-title">🌌 Clima Espacial Interactivo</h1>
    <p class="page-subtitle">Datos reales NASA · explora escenarios de tormenta geomagnética</p>
  </div>
  <a href="<?= BASE_URL ?>/stellarscribe/simuladores/" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:16px;height:16px"></i>
    Simuladores
  </a>
</div>

<!-- Row 1: Gauges -->
<div class="sim-grid">

  <!-- Kp Gauge -->
  <div class="sim-card">
    <div class="sim-h">Índice Kp — Actividad Geomagnética</div>
    <div class="gauge-wrap">
      <canvas id="kp-gauge" width="320" height="180"></canvas>
    </div>
    <div class="gauge-label" id="kp-val"><?= $kpNow ?></div>
    <div class="gauge-sub" id="kp-desc">Calculando…</div>
    <div style="margin-top:16px">
      <label style="font-size:11px;color:#5c7ab0;display:block;margin-bottom:4px">Simula diferentes niveles de Kp</label>
      <input type="range" id="kp-slider" min="0" max="9" step="0.1" value="<?= $kpNow ?>"
             oninput="setKp(this.value)" style="width:100%;accent-color:#4361ee">
      <div style="display:flex;justify-content:space-between;font-size:10px;color:#5c7ab0;margin-top:2px">
        <span>0 Calma</span><span>4 Menor</span><span>7 Severa</span><span>9 Extrema</span>
      </div>
    </div>
  </div>

  <!-- Wind speed -->
  <div class="sim-card">
    <div class="sim-h">Viento Solar</div>
    <div style="text-align:center;padding:16px 0">
      <canvas id="wind-canvas" width="260" height="140" style="max-width:100%"></canvas>
    </div>
    <div class="gauge-label" id="wind-val"><?= $windSpeed ?> <span style="font-size:14px;font-weight:400;color:#5c7ab0">km/s</span></div>
    <div class="gauge-sub" id="wind-desc">Velocidad actual del viento solar</div>
    <div style="margin-top:16px">
      <input type="range" id="wind-slider" min="200" max="800" step="10" value="<?= $windSpeed ?>"
             oninput="setWind(this.value)" style="width:100%;accent-color:#3ecf8e">
      <div style="display:flex;justify-content:space-between;font-size:10px;color:#5c7ab0;margin-top:2px">
        <span>200 Lento</span><span>400 Normal</span><span>800 Extremo</span>
      </div>
    </div>
  </div>

  <!-- Aurora forecast -->
  <div class="sim-card">
    <div class="sim-h">Pronóstico de Aurora</div>
    <div id="aurora-vis" style="width:100%;height:120px;border-radius:12px;margin-bottom:14px;display:flex;align-items:center;justify-content:center;font-size:48px;transition:background .5s">
      🌌
    </div>
    <div id="aurora-label" style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:#e8f0fe;margin-bottom:6px"></div>
    <div id="aurora-text" style="font-size:13px;color:#8898aa;line-height:1.6"></div>
    <div style="margin-top:14px">
      <div class="bar-row"><span class="bar-label">Visible hasta</span><div class="bar-track"><div class="bar-fill" id="bar-lat" style="background:#3ecf8e"></div></div><span id="bar-lat-val" style="font-size:11px;color:#c7d9ff;width:36px;text-align:right"></span></div>
      <div class="bar-row"><span class="bar-label">Intensidad</span><div class="bar-track"><div class="bar-fill" id="bar-int" style="background:#4361ee"></div></div><span id="bar-int-val" style="font-size:11px;color:#c7d9ff;width:36px;text-align:right"></span></div>
    </div>
  </div>

</div>

<!-- Row 2: Events + Scenarios + Stats -->
<div class="sim-grid">

  <!-- Recent flares -->
  <div class="sim-card">
    <div class="sim-h">Llamaradas Solares Recientes</div>
    <?php
    $flareColors = ['X'=>'#ef4444','M'=>'#fb923c','C'=>'#f5c842','B'=>'#4a9eff'];
    foreach (array_slice($flares, 0, 6) as $f):
        $cls  = $f['class'] ?? 'C';
        $clr  = $flareColors[$cls[0]] ?? '#8898aa';
        $peak = substr($f['peak_time'] ?? '', 0, 10);
    ?>
    <div class="event-row">
      <span class="ev-badge" style="background:<?= $clr ?>22;color:<?= $clr ?>"><?= sanitize($cls) ?></span>
      <div>
        <div style="font-size:12px;font-weight:600;color:#c7d9ff"><?= sanitize($f['region'] ?? 'Región desconocida') ?></div>
        <div style="font-size:11px;color:#5c7ab0"><?= sanitize($peak) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- What-if scenarios -->
  <div class="sim-card">
    <div class="sim-h">Escenarios — ¿Qué pasaría si…?</div>
    <button class="scenario-btn" onclick="runScenario('carrington')">
      <span style="font-size:20px">⚡</span>
      <div>
        <div style="font-weight:700;color:#e8f0fe">Evento Carrington (1859)</div>
        <div style="font-size:11px;color:#5c7ab0">La tormenta geomagnética más potente registrada</div>
      </div>
    </button>
    <button class="scenario-btn" onclick="runScenario('halloween')">
      <span style="font-size:20px">🎃</span>
      <div>
        <div style="font-weight:700;color:#e8f0fe">Tormentas de Halloween 2003</div>
        <div style="font-size:11px;color:#5c7ab0">Llamaradas X17 y X28 que afectaron satélites</div>
      </div>
    </button>
    <button class="scenario-btn" onclick="runScenario('aurora-peru')">
      <span style="font-size:20px">🌄</span>
      <div>
        <div style="font-weight:700;color:#e8f0fe">Aurora sobre Perú</div>
        <div style="font-size:11px;color:#5c7ab0">¿Cuándo podría verse aurora en Lima?</div>
      </div>
    </button>
    <button class="scenario-btn" onclick="runScenario('calm')">
      <span style="font-size:20px">😴</span>
      <div>
        <div style="font-weight:700;color:#e8f0fe">Día de Calma Solar</div>
        <div style="font-size:11px;color:#5c7ab0">Viento solar mínimo, actividad baja</div>
      </div>
    </button>
    <div id="scenario-result" style="margin-top:12px;padding:12px;background:#0f1629;border-radius:10px;font-size:12px;color:#c7d9ff;line-height:1.7;display:none"></div>
  </div>

  <!-- Live metrics -->
  <div class="sim-card">
    <div class="sim-h">Parámetros Solares Actuales</div>
    <div class="bar-row">
      <span class="bar-label">Flujo F10.7</span>
      <div class="bar-track"><div class="bar-fill" style="width:<?= min(100, $fluxNow/3) ?>%;background:#f5c842"></div></div>
      <span style="font-size:11px;color:#c7d9ff;width:48px;text-align:right"><?= $fluxNow ?> SFU</span>
    </div>
    <div class="bar-row">
      <span class="bar-label">Campo Bt</span>
      <div class="bar-track"><div class="bar-fill" style="width:<?= min(100, $bFieldNow*5) ?>%;background:#a855f7"></div></div>
      <span style="font-size:11px;color:#c7d9ff;width:48px;text-align:right"><?= $bFieldNow ?> nT</span>
    </div>
    <div class="bar-row">
      <span class="bar-label">Manchas</span>
      <div class="bar-track"><div class="bar-fill" style="width:<?= min(100, $sunspotNow/2.5) ?>%;background:#fb923c"></div></div>
      <span style="font-size:11px;color:#c7d9ff;width:48px;text-align:right"><?= $sunspotNow ?></span>
    </div>

    <div style="margin-top:20px">
      <div class="sim-h" style="margin-bottom:10px">CMEs Recientes</div>
      <?php foreach (array_slice($cmes, 0, 3) as $cme):
          $speed = (int)($cme['speed_km_s'] ?? 0);
          $spd_pct = min(100, $speed/30);
      ?>
      <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:11px;color:#8898aa;margin-bottom:4px">
          <span><?= sanitize(substr($cme['time'] ?? '', 0, 10)) ?></span>
          <span style="color:#fb923c;font-weight:700"><?= $speed ?> km/s</span>
        </div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $spd_pct ?>%;background:linear-gradient(90deg,#fb923c,#ef4444)"></div></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #0f1629">
      <div style="font-size:11px;color:#5c7ab0;margin-bottom:6px">Tiempo en simulador</div>
      <div style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:#3ecf8e" id="sim-timer">0:00</div>
    </div>
  </div>

</div>

<script>
const SESION_ID = <?= $sesionId ?>;
const BASE = '<?= BASE_URL ?>';
const startTime = Date.now();

// ── Kp gauge ────────────────────────────────────────────────────
const kpCanvas = document.getElementById('kp-gauge');
const kpCtx = kpCanvas.getContext('2d');
let currentKp = <?= $kpNow ?>;

function kpColor(v) {
    if (v < 4) return '#3ecf8e';
    if (v < 5) return '#f5c842';
    if (v < 7) return '#fb923c';
    return '#ef4444';
}
function kpDesc(v) {
    if (v < 4)  return 'Calma — condiciones normales ✅';
    if (v < 5)  return 'Tormenta menor — leve impacto en GPS';
    if (v < 6)  return 'Tormenta moderada — aurora en altas latitudes';
    if (v < 7)  return 'Tormenta fuerte — posibles interrupciones HF';
    if (v < 8)  return 'Tormenta severa — aurora a latitudes medias';
    if (v < 9)  return 'Tormenta extrema — impacto en redes eléctricas';
    return '🚨 G5 EXTREMA — Evento histórico tipo Carrington';
}

function drawKpGauge(val) {
    const W = kpCanvas.width, H = kpCanvas.height;
    kpCtx.clearRect(0, 0, W, H);
    const cx = W/2, cy = H*0.85, r = W*0.38;
    const startA = Math.PI, endA = 2*Math.PI;

    // Track
    kpCtx.beginPath(); kpCtx.arc(cx, cy, r, startA, endA);
    kpCtx.strokeStyle = '#0f1629'; kpCtx.lineWidth = 18; kpCtx.stroke();

    // Gradient zones
    const zones = [[0,4,'#3ecf8e'],[4,6,'#f5c842'],[6,8,'#fb923c'],[8,9.9,'#ef4444']];
    zones.forEach(([a,b,clr]) => {
        kpCtx.beginPath();
        kpCtx.arc(cx, cy, r, startA + (a/9.9)*Math.PI, startA + (b/9.9)*Math.PI);
        kpCtx.strokeStyle = clr+'44'; kpCtx.lineWidth = 18; kpCtx.stroke();
    });

    // Active fill
    const fillEnd = startA + (Math.min(val,9.9)/9.9)*Math.PI;
    kpCtx.beginPath(); kpCtx.arc(cx, cy, r, startA, fillEnd);
    kpCtx.strokeStyle = kpColor(val); kpCtx.lineWidth = 18;
    kpCtx.lineCap = 'round'; kpCtx.stroke();

    // Needle
    const needleA = startA + (Math.min(val,9.9)/9.9)*Math.PI;
    kpCtx.beginPath();
    kpCtx.moveTo(cx, cy);
    kpCtx.lineTo(cx + Math.cos(needleA)*(r*0.9), cy + Math.sin(needleA)*(r*0.9));
    kpCtx.strokeStyle = '#fff'; kpCtx.lineWidth = 3; kpCtx.lineCap = 'round'; kpCtx.stroke();
    kpCtx.beginPath(); kpCtx.arc(cx, cy, 6, 0, Math.PI*2);
    kpCtx.fillStyle = '#fff'; kpCtx.fill();
}

function setKp(v) {
    currentKp = parseFloat(v);
    document.getElementById('kp-val').textContent = currentKp.toFixed(1);
    document.getElementById('kp-desc').textContent = kpDesc(currentKp);
    document.getElementById('kp-desc').style.color = kpColor(currentKp);
    drawKpGauge(currentKp);
    updateAurora(currentKp);
}
setKp(<?= $kpNow ?>);

// ── Wind canvas ──────────────────────────────────────────────────
const windCanvas = document.getElementById('wind-canvas');
const wctx = windCanvas.getContext('2d');
let windParticles = Array.from({length:30}, () => ({
    x: Math.random()*260, y: Math.random()*140,
    vx: Math.random()*3+1, vy:(Math.random()-.5)*0.5,
    a: Math.random()
}));
let windSpeed = <?= $windSpeed ?>;

function animateWind() {
    wctx.clearRect(0,0,260,140);
    wctx.fillStyle='#02040d'; wctx.fillRect(0,0,260,140);
    const speedFactor = windSpeed/400;
    windParticles.forEach(p => {
        p.x += p.vx * speedFactor;
        p.y += p.vy;
        if (p.x > 260) { p.x = 0; p.y = Math.random()*140; }
        const g = wctx.createLinearGradient(p.x-10,0,p.x,0);
        g.addColorStop(0,'transparent');
        g.addColorStop(1,`rgba(62,207,142,${p.a*0.7})`);
        wctx.strokeStyle = g; wctx.lineWidth = 1.5;
        wctx.beginPath(); wctx.moveTo(p.x-20*speedFactor, p.y); wctx.lineTo(p.x, p.y); wctx.stroke();
    });
    requestAnimationFrame(animateWind);
}
animateWind();

function setWind(v) {
    windSpeed = parseInt(v);
    document.getElementById('wind-val').innerHTML = windSpeed + ' <span style="font-size:14px;font-weight:400;color:#5c7ab0">km/s</span>';
    const desc = windSpeed < 350 ? 'Viento solar lento — calma interplanetaria' :
                 windSpeed < 500 ? 'Velocidad nominal — condiciones normales' :
                 windSpeed < 650 ? 'Viento rápido — flujo de agujero coronal' :
                 '⚠️ Viento extremo — CME en impacto';
    document.getElementById('wind-desc').textContent = desc;
}

// ── Aurora ───────────────────────────────────────────────────────
function updateAurora(kp) {
    const vis  = document.getElementById('aurora-vis');
    const lbl  = document.getElementById('aurora-label');
    const txt  = document.getElementById('aurora-text');
    const barLat = document.getElementById('bar-lat');
    const barInt = document.getElementById('bar-int');
    const barLatVal = document.getElementById('bar-lat-val');
    const barIntVal = document.getElementById('bar-int-val');

    let bgClr, emoji, label, desc, latPct, intPct, latLabel;
    if (kp < 4) {
        bgClr='linear-gradient(135deg,#0a1628,#0f2040)'; emoji='🌙'; label='Sin actividad';
        desc='Condiciones de calma. Aurora visible solo en el Ártico y Antártida.';
        latPct=10; intPct=10; latLabel='70°N';
    } else if (kp < 5) {
        bgClr='linear-gradient(135deg,#0a1a2e,#0d2a4a)'; emoji='🌌'; label='Aurora menor';
        desc='Aurora visible en latitudes altas (Noruega, Alaska). Kp en ascenso.';
        latPct=25; intPct=30; latLabel='65°N';
    } else if (kp < 6) {
        bgClr='linear-gradient(135deg,#0d2040,#1a3060)'; emoji='🟢'; label='Aurora moderada';
        desc='Aurora visible en Canadá, norte de Europa y partes de Rusia.';
        latPct=45; intPct=50; latLabel='55°N';
    } else if (kp < 7) {
        bgClr='linear-gradient(135deg,#1a1040,#2d1060)'; emoji='💚'; label='Aurora fuerte';
        desc='Aurora visible en latitudes medias: norte de EE.UU., Europa central.';
        latPct=65; intPct=70; latLabel='45°N';
    } else if (kp < 8) {
        bgClr='linear-gradient(135deg,#2d0a40,#4a1070)'; emoji='🟣'; label='Aurora severa';
        desc='Aurora visible desde los Alpes, norte de España. Colores intensos.';
        latPct=80; intPct=85; latLabel='35°N';
    } else {
        bgClr='linear-gradient(135deg,#3d0010,#600020)'; emoji='🔴'; label='🚨 Aurora EXTREMA G5';
        desc='¡Aurora potencialmente visible desde Perú (latitude -12°)! Evento histórico como 2003.';
        latPct=100; intPct=100; latLabel='Perú';
    }
    vis.style.background = bgClr;
    vis.textContent = emoji;
    lbl.textContent = label;
    txt.textContent = desc;
    barLat.style.width = latPct + '%';
    barInt.style.width = intPct + '%';
    barLatVal.textContent = latLabel;
    barIntVal.textContent = Math.round(intPct) + '%';
}
updateAurora(<?= $kpNow ?>);

// ── Scenarios ───────────────────────────────────────────────────
const SCENARIOS = {
    carrington: {
        kp:9, wind:900,
        text:'El evento Carrington del 1-2 septiembre 1859 fue la tormenta geomagnética más intensa registrada. Telegrafistas reportaron chispas eléctricas. Las auroras se vieron desde Cuba y Hawaii. Hoy colapsaría el 90% de los transformadores de alta tensión en EE.UU. — costo estimado: 2 billones de dólares.'
    },
    halloween: {
        kp:8.5, wind:800,
        text:'Las "Tormentas de Halloween" del 28-29 octubre 2003 incluyeron la llamarada X17 y la posiblemente X28 (la más intensa medida). La ESA reportó pérdidas de satélites. El GPS falló por horas. Curiosamente se formaron auroras en la Florida (latitud ~25°N) — equivalente aproximado al norte de Perú.'
    },
    'aurora-peru': {
        kp:9, wind:750,
        text:'Para que una aurora sea visible desde Lima (-12°S), el índice Kp debe alcanzar el nivel G5 (Kp≥9). Esto ha ocurrido ~4 veces en los últimos 25 años (2003, 2005, 2015, 2024). La aurora que se vio en Perú en mayo 2024 con Kp≈9 fue fotografiada desde Cusco y Arequipa.'
    },
    calm: {
        kp:1.5, wind:320,
        text:'En días de calma solar, el viento solar fluye a ~320-400 km/s con densidad baja. El índice Kp ronda 1-2. Las auroras se limitan al círculo polar ártico (>70° latitud). Los satélites y redes eléctricas operan normalmente. Estos días son útiles para calibrar instrumentos y actualizar modelos de predicción.'
    }
};

function runScenario(key) {
    const sc = SCENARIOS[key];
    document.getElementById('kp-slider').value = sc.kp;
    document.getElementById('wind-slider').value = sc.wind;
    setKp(sc.kp); setWind(sc.wind);
    const res = document.getElementById('scenario-result');
    res.style.display = 'block';
    res.textContent = sc.text;
}

// Timer
setInterval(() => {
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    document.getElementById('sim-timer').textContent =
        Math.floor(elapsed/60) + ':' + String(elapsed%60).padStart(2,'0');
}, 1000);

window.addEventListener('beforeunload', () => {
    const sec = Math.floor((Date.now() - startTime) / 1000);
    if (sec < 5 || !SESION_ID) return;
    navigator.sendBeacon(BASE + '/api/simulador_sesion.php',
        JSON.stringify({ id: SESION_ID, duracion: sec }));
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
