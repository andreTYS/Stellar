<?php
// ============================================================
// INNOVA-STEAM × StellarScribe — Portal NASA
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = currentUser();

// ── Load NASA JSON data ──────────────────────────────────────
$dataDir    = __DIR__ . '/api/';
$weather    = json_decode(file_get_contents($dataDir . 'current_space_weather.json'), true) ?? [];
$flares     = json_decode(file_get_contents($dataDir . 'basic_flare_data.json'),      true) ?? [];
$cme        = json_decode(file_get_contents($dataDir . 'basic_cme_data.json'),        true) ?? [];
$sunspots   = json_decode(file_get_contents($dataDir . 'basic_sunspot_data.json'),    true) ?? [];
$eduContent = json_decode(file_get_contents($dataDir . 'advanced_educational_content.json'), true) ?? [];

// Last 5 flares
$recentFlares = array_slice($flares, 0, 5);
// Last 3 CME
$recentCme    = array_slice($cme,    0, 3);
// Latest sunspot count from data
$latestSunspot = end($sunspots);

// Aurora color
$auroraColor = match($weather['aurora_forecast'] ?? '') {
    'high','extreme' => '#3ecf8e',
    'moderate'       => '#f5c842',
    default          => '#8898aa',
};
$kp = round((float)($weather['kp_index'] ?? 0), 1);

$pageTitle = 'StellarScribe — NASA';
$activeNav = 'stellarscribe';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Hero banner ──────────────────────────────────────────── -->
<div style="background:linear-gradient(135deg,#0d1024 0%,#1a0e3a 50%,#0d1024 100%);border-radius:16px;padding:36px 40px;margin-bottom:28px;position:relative;overflow:hidden">
  <!-- Animated orbs -->
  <div style="position:absolute;top:-60px;right:-60px;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(67,97,238,.35) 0%,transparent 70%);pointer-events:none"></div>
  <div style="position:absolute;bottom:-40px;left:30%;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(139,92,246,.3) 0%,transparent 70%);pointer-events:none"></div>
  <!-- Grid -->
  <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:36px 36px;pointer-events:none;border-radius:16px"></div>

  <div style="position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap">
    <div>
      <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(67,97,238,.2);border:1px solid rgba(67,97,238,.4);border-radius:99px;padding:5px 14px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#818cf8;margin-bottom:14px">
        <span style="width:6px;height:6px;border-radius:50%;background:#4361ee;display:inline-block"></span>
        NASA Space Apps 2025 · Perú Es Clave
      </div>
      <h1 style="font-family:'Syne',sans-serif;font-weight:800;font-size:32px;color:#fff;margin-bottom:8px;letter-spacing:-.02em">
        🚀 StellarScribe
      </h1>
      <p style="font-size:15px;color:#7888a0;max-width:480px;line-height:1.6">
        Historia interactiva basada en datos científicos reales de la NASA.
        Aprende astronomía, clima espacial y misiones a través de la aventura de Aldrin.
      </p>
    </div>
    <div style="display:flex;gap:10px;flex-shrink:0">
      <a href="<?= BASE_URL ?>/stellarscribe/index.html" target="_blank"
         style="display:inline-flex;align-items:center;gap:8px;padding:11px 20px;background:rgba(67,97,238,.2);border:1.5px solid rgba(67,97,238,.45);border-radius:10px;color:#818cf8;font-weight:700;font-size:13px;text-decoration:none;transition:all .2s"
         onmouseover="this.style.background='rgba(67,97,238,.35)'" onmouseout="this.style.background='rgba(67,97,238,.2)'">
        <i data-lucide="play" style="width:15px;height:15px"></i>
        Capítulo 1
      </a>
      <a href="<?= BASE_URL ?>/stellarscribe/index2.html" target="_blank"
         style="display:inline-flex;align-items:center;gap:8px;padding:11px 20px;background:linear-gradient(135deg,#4361ee,#7c3aed);border:none;border-radius:10px;color:#fff;font-weight:700;font-size:13px;text-decoration:none;box-shadow:0 4px 20px rgba(67,97,238,.4);transition:opacity .2s"
         onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
        <i data-lucide="zap" style="width:15px;height:15px"></i>
        Capítulo 2 (Premium)
      </a>
    </div>
  </div>
</div>

<!-- ── Space weather stats ──────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:28px">

  <div class="stat-card" style="border-top:3px solid #f5c842">
    <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)">
      <i data-lucide="sun" style="width:20px;height:20px"></i>
    </div>
    <div class="stat-value" style="color:var(--gold)"><?= (int)($weather['sunspot_number'] ?? 0) ?></div>
    <div class="stat-label">Manchas solares</div>
  </div>

  <div class="stat-card" style="border-top:3px solid #4a9eff">
    <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)">
      <i data-lucide="wind" style="width:20px;height:20px"></i>
    </div>
    <div class="stat-value" style="color:var(--blue)"><?= round((float)($weather['solar_wind_speed'] ?? 0)) ?></div>
    <div class="stat-label">Viento solar (km/s)</div>
  </div>

  <div class="stat-card" style="border-top:3px solid <?= $auroraColor ?>">
    <div class="stat-icon" style="background:<?= $auroraColor ?>20;color:<?= $auroraColor ?>">
      <i data-lucide="activity" style="width:20px;height:20px"></i>
    </div>
    <div class="stat-value" style="color:<?= $auroraColor ?>;font-size:16px;padding-top:4px"><?= $kp ?></div>
    <div class="stat-label">Índice Kp</div>
    <div style="font-size:10px;margin-top:4px;padding:2px 8px;border-radius:99px;background:<?= $auroraColor ?>20;color:<?= $auroraColor ?>;display:inline-block;text-transform:capitalize"><?= htmlspecialchars($weather['aurora_forecast'] ?? '—') ?></div>
  </div>

  <div class="stat-card" style="border-top:3px solid #fb6340">
    <div class="stat-icon" style="background:rgba(251,99,64,.12);color:var(--coral)">
      <i data-lucide="radio" style="width:20px;height:20px"></i>
    </div>
    <div class="stat-value" style="color:var(--coral)"><?= round((float)($weather['solar_flux_10_7cm'] ?? 0)) ?></div>
    <div class="stat-label">Flujo solar 10.7cm</div>
  </div>

  <div class="stat-card" style="border-top:3px solid #a78bfa">
    <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)">
      <i data-lucide="magnet" style="width:20px;height:20px"></i>
    </div>
    <div class="stat-value" style="color:var(--purple)"><?= round((float)($weather['magnetic_field'] ?? 0), 1) ?></div>
    <div class="stat-label">Campo magnético (nT)</div>
  </div>

  <div class="stat-card" style="border-top:3px solid #3ecf8e">
    <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)">
      <i data-lucide="zap" style="width:20px;height:20px"></i>
    </div>
    <div class="stat-value" style="color:var(--green)"><?= isset($weather['current_solar_cycle']['cycle_number']) ? '#' . $weather['current_solar_cycle']['cycle_number'] : '—' ?></div>
    <div class="stat-label">Ciclo solar</div>
    <div style="font-size:10px;margin-top:4px;color:var(--text-muted)"><?= htmlspecialchars($weather['current_solar_cycle']['phase'] ?? '') ?></div>
  </div>

</div>

<!-- ── Main 2-column layout ─────────────────────────────────── -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

  <!-- Left: Chapters + CME ─────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:20px">

    <!-- Chapter launch cards -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title" style="font-size:15px">📖 Experiencias interactivas</h2>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

        <!-- Cap 1 -->
        <a href="<?= BASE_URL ?>/stellarscribe/index.html" target="_blank" style="text-decoration:none">
          <div style="background:var(--bg-elevated);border:1.5px solid var(--bg-border);border-radius:14px;overflow:hidden;transition:border-color .2s,transform .2s"
               onmouseover="this.style.borderColor='#4361ee88';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='var(--bg-border)';this.style.transform=''">
            <div style="height:120px;background:linear-gradient(135deg,#0d1024,#1a1f3c);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;position:relative;overflow:hidden">
              <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:24px 24px"></div>
              <span style="font-size:40px;position:relative">🌍</span>
              <span style="font-size:11px;color:#818cf8;font-weight:700;letter-spacing:.06em;position:relative">CAPÍTULO 1</span>
            </div>
            <div style="padding:14px 16px">
              <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:var(--text-primary);margin-bottom:4px">Perú Es Clave</div>
              <div style="font-size:12px;color:var(--text-muted);line-height:1.5">Descubre por qué el sol afecta la vida en la Tierra. Introducción al clima espacial.</div>
              <div style="margin-top:12px;display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#4361ee">
                <i data-lucide="play-circle" style="width:13px;height:13px"></i>
                Iniciar aventura
              </div>
            </div>
          </div>
        </a>

        <!-- Cap 2 -->
        <a href="<?= BASE_URL ?>/stellarscribe/index2.html" target="_blank" style="text-decoration:none">
          <div style="background:linear-gradient(135deg,#0d1024,#1a0e3a);border:1.5px solid rgba(139,92,246,.3);border-radius:14px;overflow:hidden;transition:border-color .2s,transform .2s"
               onmouseover="this.style.borderColor='rgba(139,92,246,.7)';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='rgba(139,92,246,.3)';this.style.transform=''">
            <div style="height:120px;background:linear-gradient(135deg,#0d1024,#2d1b4e);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;position:relative;overflow:hidden">
              <div style="position:absolute;top:-30px;right:-30px;width:150px;height:150px;border-radius:50%;background:radial-gradient(circle,rgba(139,92,246,.3) 0%,transparent 70%)"></div>
              <span style="font-size:40px;position:relative">🚀</span>
              <span style="font-size:11px;color:#c4b5fd;font-weight:700;letter-spacing:.06em;position:relative">CAPÍTULO 2 — PREMIUM</span>
            </div>
            <div style="padding:14px 16px">
              <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:#fff;margin-bottom:4px">Aldrin: Regreso a Casa</div>
              <div style="font-size:12px;color:#7888a0;line-height:1.5">Misión espacial completa con gráficos avanzados, mini-juegos y datos en tiempo real.</div>
              <div style="margin-top:12px;display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:#a78bfa">
                <i data-lucide="zap" style="width:13px;height:13px"></i>
                Experiencia premium
              </div>
            </div>
          </div>
        </a>

      </div>
    </div>

    <!-- CME Events -->
    <?php if (!empty($recentCme)): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="card-title" style="font-size:15px">☄️ Eyecciones de masa coronal (CME) recientes</h2>
        <span style="font-size:11px;padding:3px 10px;border-radius:99px;background:rgba(251,99,64,.1);color:var(--coral);font-weight:600">NOAA / NASA</span>
      </div>
      <div style="display:flex;flex-direction:column;gap:0">
        <?php foreach ($recentCme as $c): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid var(--bg-border)">
          <div style="width:40px;height:40px;border-radius:10px;background:rgba(251,99,64,.1);color:var(--coral);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px">☄️</div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:var(--text-primary)"><?= htmlspecialchars($c['event_id'] ?? 'CME') ?></div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
              Velocidad: <strong style="color:var(--text-secondary)"><?= isset($c['speed']) ? round($c['speed']) . ' km/s' : '—' ?></strong>
              <?php if (!empty($c['direction'])): ?> · <?= htmlspecialchars($c['direction']) ?><?php endif; ?>
            </div>
          </div>
          <div style="font-size:11px;color:var(--text-muted);flex-shrink:0;text-align:right">
            <?= !empty($c['start_time']) ? date('d/m H:i', strtotime($c['start_time'])) : '—' ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Right: Recent flares + education topics ─────────────── -->
  <div style="display:flex;flex-direction:column;gap:20px">

    <!-- Solar flares -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title" style="font-size:15px">🔥 Llamaradas solares</h2>
        <span style="font-size:11px;color:var(--text-muted)"><?= count($flares) ?> registros</span>
      </div>
      <div style="display:flex;flex-direction:column;gap:0">
        <?php foreach ($recentFlares as $f):
          $cls = $f['class_type'] ?? '';
          $clsLetter = $cls ? $cls[0] : 'B';
          $clsColor = match($clsLetter) {
            'X' => '#ef4444',
            'M' => '#fb6340',
            'C' => '#f5c842',
            default => '#4a9eff',
          };
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--bg-border)">
          <span style="width:32px;height:32px;border-radius:8px;background:<?= $clsColor ?>18;color:<?= $clsColor ?>;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0"><?= $clsLetter ?></span>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:700;color:var(--text-primary)"><?= htmlspecialchars($cls) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($f['source_location'] ?? '') ?> · AR<?= $f['active_region'] ?? '' ?></div>
          </div>
          <div style="font-size:10px;color:var(--text-muted);white-space:nowrap">
            <?= !empty($f['peak_time']) ? date('d/m H:i', strtotime($f['peak_time'])) : '—' ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div style="padding-top:12px;font-size:11px;color:var(--text-muted)">
        Clases: <span style="color:#ef4444">X</span> extrema · <span style="color:#fb6340">M</span> fuerte · <span style="color:#f5c842">C</span> media · <span style="color:#4a9eff">B</span> baja
      </div>
    </div>

    <!-- Education topics -->
    <?php if (!empty($eduContent['science_topics'])): ?>
    <div class="card">
      <div class="card-header">
        <h2 class="card-title" style="font-size:15px">🎓 Temas del currículo</h2>
      </div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach (array_slice($eduContent['science_topics'], 0, 6) as $topic):
          $icons = ['🌞','🌌','⚡','🛰️','🔬','📡'];
          static $ti = 0;
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--bg-elevated);border-radius:9px">
          <span style="font-size:18px;flex-shrink:0"><?= $icons[$ti % 6] ?></span>
          <div>
            <div style="font-size:12px;font-weight:600;color:var(--text-primary)"><?= htmlspecialchars($topic['topic'] ?? $topic) ?></div>
            <?php if (!empty($topic['subtopics'])): ?>
            <div style="font-size:11px;color:var(--text-muted);margin-top:1px"><?= htmlspecialchars(implode(' · ', array_slice($topic['subtopics'], 0, 2))) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php $ti++; endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- About card -->
    <div class="card" style="background:linear-gradient(135deg,#0d1024,#1a1f3c);border-color:rgba(67,97,238,.25)">
      <div style="font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:#818cf8;margin-bottom:10px;text-transform:uppercase;letter-spacing:.06em">Sobre StellarScribe</div>
      <p style="font-size:13px;color:#7888a0;line-height:1.6;margin-bottom:12px">
        Proyecto ganador del <strong style="color:#c4b5fd">NASA Space Apps Challenge 2025</strong> por el equipo peruano de Moquegua.
        Historia interactiva que enseña ciencias espaciales a través de la aventura del astronauta Aldrin.
      </p>
      <div style="display:flex;flex-direction:column;gap:6px">
        <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#7888a0">
          <i data-lucide="database" style="width:13px;height:13px;color:#818cf8"></i>
          Datos reales: NASA DONKI, NOAA, SDO/AIA
        </div>
        <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#7888a0">
          <i data-lucide="map-pin" style="width:13px;height:13px;color:#818cf8"></i>
          Equipo: GUE Mariscal Nieto, Moquegua, Perú
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
