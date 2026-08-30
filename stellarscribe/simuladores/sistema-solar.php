<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
requireLogin();

$uid = currentUserId();
$pdo = getDB();

// Log simulator session start
try {
    $pdo->prepare('INSERT INTO simulador_sesiones (usuario_id, simulador) VALUES (?,?)')
        ->execute([$uid, 'sistema-solar']);
    $sesionId = (int)$pdo->lastInsertId();
} catch (\PDOException $e) { $sesionId = 0; }

$pageTitle = 'Sistema Solar — Simulador';
$activeNav = 'stellarscribe';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-action-header">
  <div>
    <h1 class="page-title">🌌 Sistema Solar Interactivo</h1>
    <p class="page-subtitle">Haz clic en cualquier planeta para aprender con Aldrin</p>
  </div>
  <a href="<?= BASE_URL ?>/stellarscribe/simuladores/" class="btn btn-ghost">
    <i data-lucide="arrow-left" style="width:16px;height:16px"></i>
    Simuladores
  </a>
</div>

<div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">

  <!-- Canvas -->
  <div style="flex:1;min-width:300px;background:#02040d;border-radius:16px;border:1px solid #1a2340;overflow:hidden;position:relative">
    <canvas id="ss-canvas" style="display:block;width:100%;cursor:crosshair"></canvas>

    <!-- Controls -->
    <div style="position:absolute;bottom:16px;left:16px;right:16px;display:flex;gap:8px;flex-wrap:wrap">
      <button id="btn-flare" onclick="triggerFlare()" style="background:rgba(251,146,60,.15);border:1px solid rgba(251,146,60,.4);color:#fb923c;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg> Llamarada Solar
      </button>
      <button onclick="toggleLabels()" style="background:rgba(67,97,238,.15);border:1px solid rgba(67,97,238,.4);color:#818cf8;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg> Etiquetas
      </button>
      <button onclick="toggleOrbits()" style="background:rgba(62,207,142,.15);border:1px solid rgba(62,207,142,.4);color:#3ecf8e;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg> Órbitas
      </button>
      <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
        <span style="font-size:11px;color:#5c7ab0">Velocidad</span>
        <input type="range" id="speed-slider" min="0.1" max="5" step="0.1" value="1" oninput="setSpeed(this.value)"
               style="width:80px;accent-color:#4361ee">
      </div>
    </div>
  </div>

  <!-- Info panel -->
  <div style="width:280px;display:flex;flex-direction:column;gap:12px">

    <!-- Aldrin card -->
    <div id="aldrin-card" style="background:#02040d;border:1px solid #1a2340;border-radius:16px;padding:20px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#4361ee,#3ecf8e);display:flex;align-items:center;justify-content:center;flex-shrink:0"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg></div>
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:14px;font-weight:800;color:#e8f0fe">Aldrin</div>
          <div style="font-size:11px;color:#5c7ab0">Guía Astronauta</div>
        </div>
      </div>
      <div id="aldrin-text" style="font-size:13px;color:#c7d9ff;line-height:1.7">
        ¡Bienvenido al Sistema Solar! Haz clic en cualquier planeta para descubrir sus secretos. El Sol está en el centro, con sus llamaradas de plasma.
      </div>
    </div>

    <!-- Planet stats -->
    <div id="planet-stats" style="background:#02040d;border:1px solid #1a2340;border-radius:16px;padding:20px;display:none">
      <div id="planet-icon" style="font-size:36px;text-align:center;margin-bottom:10px"></div>
      <div id="planet-name" style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:#e8f0fe;text-align:center;margin-bottom:16px"></div>
      <div id="planet-stats-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:8px"></div>
    </div>

    <!-- Session timer -->
    <div style="background:#02040d;border:1px solid #1a2340;border-radius:12px;padding:14px;display:flex;align-items:center;justify-content:space-between">
      <span style="font-size:12px;color:#5c7ab0">Tiempo explorando</span>
      <span id="timer" style="font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:#3ecf8e">0:00</span>
    </div>

  </div>
</div>

<script>
const SESION_ID = <?= $sesionId ?>;
const BASE = '<?= BASE_URL ?>';

// ── Planet data ─────────────────────────────────────────────────
const PLANETS = [
    { name:'Mercurio', icon:'🪨', color:'#b5b5b5', r:5,  orbit:70,  speed:4.74, tilt:0.03,
      stats:[['Diámetro','4,879 km'],['Distancia','57.9M km'],['Día','59 días Tierra'],['Temp','430°C día']],
      info:'Soy el planeta más pequeño y el más cercano al Sol. Mi superficie está llena de cráteres y sin atmósfera que me proteja.' },
    { name:'Venus',    icon:'🌡️', color:'#e8c56a', r:9,  orbit:110, speed:3.50, tilt:2.64,
      stats:[['Diámetro','12,104 km'],['Distancia','108M km'],['Día','243 días Tierra'],['Temp','465°C']],
      info:'Soy el planeta más caliente del sistema solar gracias a mi efecto invernadero extremo. Roto al revés comparado con la mayoría.' },
    { name:'Tierra',   icon:'🌍', color:'#4a90d9', r:9,  orbit:155, speed:2.98, tilt:0.41,
      stats:[['Diámetro','12,742 km'],['Distancia','150M km'],['Día','24 horas'],['Luna','1 luna']],
      info:'¡Mi hogar! El único planeta conocido con vida. Mi atmósfera y agua líquida me hacen único en el sistema solar.' },
    { name:'Marte',    icon:'🔴', color:'#c1440e', r:7,  orbit:205, speed:2.41, tilt:0.44,
      stats:[['Diámetro','6,779 km'],['Distancia','228M km'],['Día','24h 37min'],['Lunas','2 lunas']],
      info:'Soy el Planeta Rojo. Tengo el volcán más grande del sistema solar: el Olympus Mons, ¡3 veces más alto que el Everest!' },
    { name:'Júpiter',  icon:'🪐', color:'#c88b3a', r:22, orbit:270, speed:1.31, tilt:0.23,
      stats:[['Diámetro','139,820 km'],['Distancia','778M km'],['Día','9h 55min'],['Lunas','95 lunas']],
      info:'Soy el gigante del sistema solar. Mi Gran Mancha Roja es una tormenta que lleva más de 300 años. ¡Quedarías 1,300 Tierras en mí!' },
    { name:'Saturno',  icon:'🪐', color:'#e8d88a', r:18, orbit:340, speed:0.97, tilt:0.47,
      stats:[['Diámetro','116,460 km'],['Distancia','1,430M km'],['Día','10h 33min'],['Anillos','7 anillos']],
      info:'Soy famoso por mis espectaculares anillos de hielo y roca. ¡Soy tan poco denso que flotaría en el agua!' },
    { name:'Urano',    icon:'🔵', color:'#7de8e8', r:13, orbit:400, speed:0.68, tilt:1.71,
      stats:[['Diámetro','50,724 km'],['Distancia','2,870M km'],['Día','17h 14min'],['Temp','-224°C']],
      info:'Roto de lado — mi eje está inclinado 98 grados. Tengo anillos como Saturno, pero mucho más oscuros.' },
    { name:'Neptuno',  icon:'💙', color:'#4169e1', r:12, orbit:455, speed:0.54, tilt:0.49,
      stats:[['Diámetro','49,244 km'],['Distancia','4,500M km'],['Día','16h 6min'],['Viento','2,100 km/h']],
      info:'Soy el más lejano y tengo los vientos más fuertes del sistema solar. Mi luna Tritón orbita en dirección contraria.' },
];

// ── Canvas setup ────────────────────────────────────────────────
const canvas  = document.getElementById('ss-canvas');
const ctx     = canvas.getContext('2d');
let W, H, CX, CY;
let showLabels = true, showOrbits = true;
let speedMult  = 1;
let angles     = PLANETS.map(() => Math.random() * Math.PI * 2);
let flareProg  = 0;   // 0=none, >0=animating
let selectedPlanet = null;
let hovered = -1;
let startTime = Date.now();

function resize() {
    const rect = canvas.parentElement.getBoundingClientRect();
    W = rect.width;
    H = Math.min(W * 0.7, 540);
    canvas.width  = W * devicePixelRatio;
    canvas.height = H * devicePixelRatio;
    canvas.style.height = H + 'px';
    ctx.scale(devicePixelRatio, devicePixelRatio);
    CX = W / 2; CY = H / 2;
}

// ── Draw ────────────────────────────────────────────────────────
function draw(ts) {
    ctx.clearRect(0, 0, W, H);

    // Starfield
    ctx.fillStyle = '#02040d';
    ctx.fillRect(0, 0, W, H);
    drawStars();

    const dt = 0.016 * speedMult;

    // Orbit rings
    if (showOrbits) {
        PLANETS.forEach(p => {
            const scale = Math.min(W, H) / 960;
            const or = p.orbit * scale;
            ctx.beginPath();
            ctx.arc(CX, CY, or, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(67,97,238,.15)';
            ctx.lineWidth = 0.5;
            ctx.stroke();
        });
    }

    // Sun
    drawSun();

    // Flare effect
    if (flareProg > 0) {
        drawFlare();
        flareProg -= 0.015;
        if (flareProg < 0) flareProg = 0;
    }

    // Planets
    const scale = Math.min(W, H) / 960;
    PLANETS.forEach((p, i) => {
        angles[i] += p.speed * 0.001 * dt;
        const or = p.orbit * scale;
        const pr = p.r * scale;
        const x = CX + Math.cos(angles[i]) * or;
        const y = CY + Math.sin(angles[i]) * (or * (1 - p.tilt * 0.1));

        const isHov = i === hovered;
        const isSel = selectedPlanet && selectedPlanet.name === p.name;

        // Glow
        if (isHov || isSel) {
            const g = ctx.createRadialGradient(x, y, 0, x, y, pr * 3.5);
            g.addColorStop(0, p.color + '55');
            g.addColorStop(1, 'transparent');
            ctx.beginPath();
            ctx.arc(x, y, pr * 3.5, 0, Math.PI * 2);
            ctx.fillStyle = g;
            ctx.fill();
        }

        // Planet body
        const grad = ctx.createRadialGradient(x - pr*0.3, y - pr*0.3, 0, x, y, pr);
        grad.addColorStop(0, lighten(p.color, 40));
        grad.addColorStop(1, p.color);
        ctx.beginPath();
        ctx.arc(x, y, pr, 0, Math.PI * 2);
        ctx.fillStyle = grad;
        ctx.fill();

        // Rings for Saturn
        if (p.name === 'Saturno') {
            ctx.save();
            ctx.translate(x, y);
            ctx.scale(1, 0.3);
            ctx.beginPath();
            ctx.arc(0, 0, pr * 2.2, 0, Math.PI * 2);
            ctx.strokeStyle = 'rgba(232,216,138,.5)';
            ctx.lineWidth = pr * 0.5;
            ctx.stroke();
            ctx.restore();
        }

        // Labels
        if (showLabels) {
            ctx.font = `bold ${Math.max(9, 10 * scale)}px Inter,sans-serif`;
            ctx.fillStyle = isHov ? '#fff' : '#8898aa';
            ctx.textAlign = 'center';
            ctx.fillText(p.name, x, y - pr - 6);
        }
    });

    // Timer
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    document.getElementById('timer').textContent =
        Math.floor(elapsed/60) + ':' + String(elapsed%60).padStart(2,'0');

    requestAnimationFrame(draw);
}

// Tiny static star field drawn once then cached
let starBuf = null;
function drawStars() {
    if (!starBuf) {
        starBuf = document.createElement('canvas');
        starBuf.width = W; starBuf.height = H;
        const sc = starBuf.getContext('2d');
        for (let i = 0; i < 200; i++) {
            const x = Math.random() * W;
            const y = Math.random() * H;
            const r = Math.random() * 1.2;
            const a = Math.random() * 0.8 + 0.2;
            sc.beginPath(); sc.arc(x, y, r, 0, Math.PI*2);
            sc.fillStyle = `rgba(255,255,255,${a})`; sc.fill();
        }
    }
    ctx.drawImage(starBuf, 0, 0, W, H);
}

function drawSun() {
    const r = Math.min(W, H) / 960 * 30;
    // Outer glow
    const og = ctx.createRadialGradient(CX, CY, r*0.5, CX, CY, r*4);
    og.addColorStop(0, 'rgba(255,200,50,.25)');
    og.addColorStop(0.5, 'rgba(255,100,20,.08)');
    og.addColorStop(1, 'transparent');
    ctx.beginPath(); ctx.arc(CX, CY, r*4, 0, Math.PI*2);
    ctx.fillStyle = og; ctx.fill();
    // Sun body
    const sg = ctx.createRadialGradient(CX-r*0.2, CY-r*0.2, 0, CX, CY, r);
    sg.addColorStop(0, '#fff7a0');
    sg.addColorStop(0.4, '#ffd230');
    sg.addColorStop(1, '#e05c00');
    ctx.beginPath(); ctx.arc(CX, CY, r, 0, Math.PI*2);
    ctx.fillStyle = sg; ctx.fill();
    if (showLabels) {
        ctx.font = 'bold 11px Inter,sans-serif';
        ctx.fillStyle = '#ffd230';
        ctx.textAlign = 'center';
        ctx.fillText('Sol', CX, CY - r - 8);
    }
}

function drawFlare() {
    const r = Math.min(W, H) / 960 * 30;
    const beams = 12;
    for (let i = 0; i < beams; i++) {
        const angle = (i / beams) * Math.PI * 2;
        const len   = (r * 4 + r * 8 * flareProg) * (0.5 + Math.random() * 0.5);
        ctx.beginPath();
        ctx.moveTo(CX + Math.cos(angle)*r, CY + Math.sin(angle)*r);
        ctx.lineTo(CX + Math.cos(angle)*len, CY + Math.sin(angle)*len);
        ctx.strokeStyle = `rgba(255,${100+Math.floor(flareProg*155)},20,${flareProg*0.8})`;
        ctx.lineWidth = 2 * flareProg;
        ctx.stroke();
    }
    const flareG = ctx.createRadialGradient(CX, CY, 0, CX, CY, r*10*flareProg);
    flareG.addColorStop(0, `rgba(255,200,50,${flareProg*0.3})`);
    flareG.addColorStop(1, 'transparent');
    ctx.beginPath(); ctx.arc(CX, CY, r*10*flareProg, 0, Math.PI*2);
    ctx.fillStyle = flareG; ctx.fill();
}

function lighten(hex, amount) {
    let r = parseInt(hex.slice(1,3),16);
    let g = parseInt(hex.slice(3,5),16);
    let b = parseInt(hex.slice(5,7),16);
    r = Math.min(255, r + amount);
    g = Math.min(255, g + amount);
    b = Math.min(255, b + amount);
    return '#' + [r,g,b].map(v => v.toString(16).padStart(2,'0')).join('');
}

// ── Interaction ─────────────────────────────────────────────────
function getHoveredPlanet(mx, my) {
    const scale = Math.min(W, H) / 960;
    for (let i = PLANETS.length - 1; i >= 0; i--) {
        const p = PLANETS[i];
        const or = p.orbit * scale;
        const pr = p.r * scale;
        const x = CX + Math.cos(angles[i]) * or;
        const y = CY + Math.sin(angles[i]) * (or * (1 - p.tilt * 0.1));
        const dist = Math.hypot(mx - x, my - y);
        if (dist < pr + 8) return i;
    }
    return -1;
}

canvas.addEventListener('mousemove', e => {
    const rect = canvas.getBoundingClientRect();
    const mx = e.clientX - rect.left;
    const my = e.clientY - rect.top;
    hovered = getHoveredPlanet(mx, my);
    canvas.style.cursor = hovered >= 0 ? 'pointer' : 'crosshair';
});

canvas.addEventListener('click', e => {
    const rect = canvas.getBoundingClientRect();
    const mx = e.clientX - rect.left;
    const my = e.clientY - rect.top;
    const idx = getHoveredPlanet(mx, my);
    if (idx >= 0) selectPlanet(PLANETS[idx]);
});

canvas.addEventListener('mouseleave', () => { hovered = -1; });

function selectPlanet(p) {
    selectedPlanet = p;
    document.getElementById('aldrin-text').textContent = p.info;
    const statsDiv = document.getElementById('planet-stats');
    statsDiv.style.display = 'block';
    document.getElementById('planet-icon').textContent = p.icon;
    document.getElementById('planet-name').textContent = p.name;
    const grid = document.getElementById('planet-stats-grid');
    grid.innerHTML = p.stats.map(([k,v]) => `
        <div style="background:#0f1629;border-radius:8px;padding:8px 10px">
            <div style="font-size:10px;color:#5c7ab0;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:2px">${k}</div>
            <div style="font-size:13px;color:#c7d9ff;font-weight:600">${v}</div>
        </div>`).join('');
}

function triggerFlare() {
    flareProg = 1;
    document.getElementById('aldrin-text').textContent =
        '⚠️ ¡Llamarada solar clase X detectada! Las partículas de energía viajan a millones de km/h hacia la Tierra. Esto puede generar auroras boreales y afectar satélites.';
    selectedPlanet = null;
    document.getElementById('planet-stats').style.display = 'none';
}
function toggleLabels()  { showLabels = !showLabels; }
function toggleOrbits()  { showOrbits = !showOrbits; starBuf = null; }
function setSpeed(v)     { speedMult = parseFloat(v); }

// ── Resize & init ───────────────────────────────────────────────
window.addEventListener('resize', () => { starBuf = null; resize(); });
resize();
requestAnimationFrame(draw);

// Save session duration on unload
window.addEventListener('beforeunload', () => {
    const sec = Math.floor((Date.now() - startTime) / 1000);
    if (sec < 5 || !SESION_ID) return;
    navigator.sendBeacon(BASE + '/api/simulador_sesion.php',
        JSON.stringify({ id: SESION_ID, duracion: sec, csrf_token: window.CSRF_TOKEN || '' }));
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
