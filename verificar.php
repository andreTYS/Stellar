<?php
// ============================================================
// INNOVA-STEAM — Verificación pública de certificados
//
// Accesible sin iniciar sesión: un colegio, una universidad o un
// empleador teclea el código impreso en el PDF y comprueba que el
// certificado existe. Solo se revela lo que ya aparece en el propio
// certificado — nunca el correo ni el código de acceso del estudiante.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));
$cert   = null;
$estado = '';   // '', 'ok', 'no-encontrado', 'formato'

if ($codigo !== '') {
    // Formato esperado: IS-XXXX-XXXX
    if (!preg_match('/^IS-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $codigo)) {
        $estado = 'formato';
    } else {
        try {
            $stmt = getDB()->prepare(
                'SELECT cert.id, cert.emitido_en,
                        m.titulo  AS modulo,
                        c.nombre  AS curso,
                        u.nombre  AS est_nombre,
                        u.apellido AS est_apellido,
                        col.nombre AS colegio
                   FROM certificados cert
                   JOIN modulos  m   ON m.id  = cert.modulo_id
                   JOIN cursos   c   ON c.id  = m.curso_id
                   JOIN usuarios u   ON u.id  = cert.estudiante_id
              LEFT JOIN colegios col ON col.id = u.colegio_id
                  WHERE cert.codigo_verificacion = ?'
            );
            $stmt->execute([$codigo]);
            $cert = $stmt->fetch();
            $estado = $cert ? 'ok' : 'no-encontrado';
        } catch (\Throwable $e) {
            $estado = 'no-encontrado';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Verificar certificado — INNOVA-STEAM</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet"/>
<style>
  :root{
    --bg:#f0f2f7; --card:#fff; --line:#e4e8f0;
    --ink:#1a1f36; --ink-2:#4e5d78; --ink-3:#8898aa;
    --accent:#4361ee; --ok:#2dca73; --bad:#f5365c;
  }
  @media (prefers-color-scheme:dark){:root:not([data-theme="light"]){
    --bg:#080c18; --card:#0f1629; --line:#1e2d4a;
    --ink:#e8f0fe; --ink-2:#c7d9ff; --ink-3:#5c7ab0;
    --accent:#7794ff; --ok:#3ed398; --bad:#ff6d8b;
  }}
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{
    background:var(--bg);color:var(--ink-2);
    font-family:'Inter',system-ui,sans-serif;font-size:15px;line-height:1.6;
    min-height:100vh;display:flex;align-items:flex-start;justify-content:center;
    padding:56px 20px;-webkit-font-smoothing:antialiased;
  }
  .wrap{width:100%;max-width:520px}
  .brand{display:flex;align-items:center;gap:10px;margin-bottom:28px;justify-content:center}
  .mark{
    width:38px;height:38px;border-radius:10px;background:var(--accent);
    display:grid;place-items:center;color:#fff;
    font-family:'Syne',sans-serif;font-weight:800;font-size:14px;
  }
  .brand-name{font-family:'Syne',sans-serif;font-weight:800;font-size:16px;color:var(--ink)}
  .brand-sub{font-size:11.5px;color:var(--ink-3)}

  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:28px}
  h1{font-family:'Syne',sans-serif;font-weight:800;font-size:21px;color:var(--ink);margin-bottom:6px}
  .lead{font-size:13.5px;color:var(--ink-3);margin-bottom:22px}

  form{display:flex;gap:8px;flex-wrap:wrap}
  input[type=text]{
    flex:1;min-width:200px;padding:11px 14px;border-radius:10px;
    border:1px solid var(--line);background:var(--bg);color:var(--ink);
    font-family:'Inter',sans-serif;font-size:14.5px;letter-spacing:.06em;
    text-transform:uppercase;
  }
  input[type=text]:focus{outline:2px solid var(--accent);outline-offset:1px;border-color:transparent}
  button{
    padding:11px 22px;border-radius:10px;border:none;cursor:pointer;
    background:var(--accent);color:#fff;font-weight:600;font-size:14px;
    font-family:'Inter',sans-serif;
  }
  button:hover{opacity:.9}

  .result{margin-top:24px;padding-top:22px;border-top:1px solid var(--line)}
  .badge{
    display:inline-flex;align-items:center;gap:7px;
    padding:5px 13px;border-radius:20px;font-size:12.5px;font-weight:600;margin-bottom:16px;
  }
  .badge.ok  {background:rgba(45,202,115,.12);color:var(--ok)}
  .badge.bad {background:rgba(245,54,92,.12);color:var(--bad)}
  .dot{width:7px;height:7px;border-radius:50%;background:currentColor}

  dl{display:grid;grid-template-columns:auto 1fr;gap:9px 18px;font-size:14px}
  dt{color:var(--ink-3);font-size:12px;text-transform:uppercase;letter-spacing:.05em;padding-top:2px}
  dd{color:var(--ink);font-weight:600}
  .msg{font-size:14px;color:var(--ink-2)}
  .foot{margin-top:22px;text-align:center;font-size:11.5px;color:var(--ink-3)}
</style>
</head>
<body>
<div class="wrap">

  <div class="brand">
    <div class="mark">IS</div>
    <div>
      <div class="brand-name">INNOVA-STEAM</div>
      <div class="brand-sub">Verificación de certificados</div>
    </div>
  </div>

  <div class="card">
    <h1>Verificar un certificado</h1>
    <p class="lead">Introduce el código que aparece al pie del certificado.</p>

    <form method="GET" action="">
      <input type="text" name="codigo" placeholder="IS-XXXX-XXXX"
             value="<?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?>"
             maxlength="12" autocomplete="off" spellcheck="false" autofocus/>
      <button type="submit">Verificar</button>
    </form>

    <?php if ($estado !== ''): ?>
    <div class="result">
      <?php if ($estado === 'ok'): ?>
        <div class="badge ok"><span class="dot"></span>Certificado auténtico</div>
        <dl>
          <dt>Estudiante</dt>
          <dd><?= sanitize(trim($cert['est_nombre'] . ' ' . $cert['est_apellido'])) ?></dd>
          <dt>Módulo</dt>
          <dd><?= sanitize($cert['modulo']) ?></dd>
          <dt>Área</dt>
          <dd><?= sanitize($cert['curso']) ?></dd>
          <?php if (!empty($cert['colegio'])): ?>
          <dt>Colegio</dt>
          <dd><?= sanitize($cert['colegio']) ?></dd>
          <?php endif; ?>
          <dt>Emitido</dt>
          <dd><?= formatDate($cert['emitido_en']) ?></dd>
          <dt>N.º</dt>
          <dd><?= str_pad((string)$cert['id'], 6, '0', STR_PAD_LEFT) ?></dd>
        </dl>
      <?php elseif ($estado === 'formato'): ?>
        <div class="badge bad"><span class="dot"></span>Código con formato inválido</div>
        <p class="msg">El código tiene la forma <strong>IS-XXXX-XXXX</strong>. Revisa que lo hayas copiado completo.</p>
      <?php else: ?>
        <div class="badge bad"><span class="dot"></span>No encontrado</div>
        <p class="msg">Ningún certificado emitido corresponde a ese código.</p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <p class="foot">INNOVA-STEAM · Plataforma educativa STEAM — Moquegua, Perú</p>
</div>
</body>
</html>
