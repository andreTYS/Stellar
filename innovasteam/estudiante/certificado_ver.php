<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('estudiante');

$estudianteId = currentUserId();
$certId       = (int)($_GET['cert_id']   ?? 0);
$moduloId     = (int)($_GET['modulo_id'] ?? 0);

$pdo = getDB();

// Look up by cert_id (from certificate list) OR by modulo_id (from completion modal)
if ($certId > 0) {
    $stmt = $pdo->prepare(
        'SELECT cert.*, m.titulo AS modulo_titulo, c.nombre AS curso_nombre, c.color_hex,
                u.nombre AS est_nombre, u.apellido AS est_apellido,
                COALESCE(pe.estrellas_quiz, 0) AS estrellas_quiz
         FROM certificados cert
         JOIN modulos m ON m.id = cert.modulo_id
         JOIN cursos  c ON c.id = m.curso_id
         JOIN usuarios u ON u.id = cert.estudiante_id
         LEFT JOIN progreso_estudiante pe ON pe.estudiante_id = cert.estudiante_id AND pe.modulo_id = cert.modulo_id
         WHERE cert.id = ? AND cert.estudiante_id = ?'
    );
    $stmt->execute([$certId, $estudianteId]);
} elseif ($moduloId > 0) {
    $stmt = $pdo->prepare(
        'SELECT cert.*, m.titulo AS modulo_titulo, c.nombre AS curso_nombre, c.color_hex,
                u.nombre AS est_nombre, u.apellido AS est_apellido,
                COALESCE(pe.estrellas_quiz, 0) AS estrellas_quiz
         FROM certificados cert
         JOIN modulos m ON m.id = cert.modulo_id
         JOIN cursos  c ON c.id = m.curso_id
         JOIN usuarios u ON u.id = cert.estudiante_id
         LEFT JOIN progreso_estudiante pe ON pe.estudiante_id = cert.estudiante_id AND pe.modulo_id = cert.modulo_id
         WHERE cert.estudiante_id = ? AND cert.modulo_id = ?'
    );
    $stmt->execute([$estudianteId, $moduloId]);
} else {
    redirect(BASE_URL . '/estudiante/certificados.php');
}

$cert = $stmt->fetch();
if (!$cert) redirect(BASE_URL . '/estudiante/certificados.php');

$color     = htmlspecialchars($cert['color_hex'] ?? '#4361ee', ENT_QUOTES, 'UTF-8');
$nombre    = sanitize($cert['est_nombre'] . ' ' . $cert['est_apellido']);
$modulo    = sanitize($cert['modulo_titulo']);
$curso     = sanitize($cert['curso_nombre']);
$estrellas = (int)$cert['estrellas_quiz'];
$fecha     = formatDate($cert['emitido_en']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Certificado — <?= $modulo ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet"/>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#f0f2f7;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:32px 16px;gap:20px;-webkit-font-smoothing:antialiased}
    .back-btn{align-self:flex-start;display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#fff;border:1px solid #e4e8f0;border-radius:10px;color:#4e5d78;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s}
    .back-btn:hover{border-color:<?= $color ?>;color:<?= $color ?>}
    .cert-wrap{width:100%;max-width:720px}
    .cert{background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,.12);position:relative}
    .cert-top{background:linear-gradient(135deg,<?= $color ?>ee 0%,<?= $color ?>99 100%);padding:48px 40px 36px;text-align:center;position:relative;overflow:hidden}
    .cert-top::before{content:'';position:absolute;top:-60px;right:-60px;width:240px;height:240px;border-radius:50%;background:rgba(255,255,255,.08)}
    .cert-top::after{content:'';position:absolute;bottom:-40px;left:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06)}
    .cert-badge{width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.2);border:3px solid rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;position:relative;z-index:1}
    .cert-badge svg{color:#fff}
    .cert-tag{display:inline-block;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);border-radius:20px;padding:4px 16px;font-size:12px;font-weight:700;color:#fff;margin-bottom:16px;position:relative;z-index:1}
    .cert-title{font-family:'Syne',sans-serif;font-size:32px;font-weight:800;color:#fff;line-height:1.2;position:relative;z-index:1;margin-bottom:4px}
    .cert-subtitle{font-size:14px;color:rgba(255,255,255,.8);position:relative;z-index:1}
    .cert-body{padding:40px}
    .cert-label{font-size:11px;font-weight:700;color:#8898aa;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}
    .cert-name{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:#1a1f36;margin-bottom:28px;line-height:1.2}
    .cert-divider{height:1px;background:#e4e8f0;margin:28px 0}
    .cert-row{display:flex;gap:24px;flex-wrap:wrap;margin-bottom:24px}
    .cert-field{flex:1;min-width:180px}
    .cert-field-val{font-size:15px;font-weight:600;color:#1a1f36}
    .stars{display:flex;gap:4px;align-items:center;margin-top:4px}
    .stars svg{width:22px;height:22px}
    .cert-seal{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;background:#f8f9fc;border-radius:14px;margin-top:4px;flex-wrap:wrap;gap:12px}
    .seal-brand{display:flex;align-items:center;gap:10px}
    .seal-mark{width:38px;height:38px;border-radius:10px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:13px;font-weight:800;color:#fff}
    .seal-name{font-family:'Syne',sans-serif;font-size:14px;font-weight:800;color:#1a1f36}
    .seal-sub{font-size:11px;color:#8898aa}
    .seal-verify{font-size:11px;color:#8898aa;display:flex;align-items:center;gap:5px}
    .print-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:<?= $color ?>;color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;font-family:'Inter',sans-serif}
    .print-btn:hover{opacity:.9;transform:translateY(-1px)}
    .actions{display:flex;gap:10px;justify-content:center;margin-top:4px;flex-wrap:wrap}
    @media print{body{background:#fff;padding:0}#no-print{display:none}.cert{box-shadow:none;border-radius:0}}
  </style>
</head>
<body>
  <a href="<?= BASE_URL ?>/estudiante/certificados.php" class="back-btn" id="no-print">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Volver a mis certificados
  </a>

  <div class="cert-wrap">
    <div class="cert">
      <!-- Top gradient band -->
      <div class="cert-top">
        <div class="cert-badge">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
        </div>
        <div class="cert-tag">INNOVA-STEAM</div>
        <h1 class="cert-title">Certificado de Logro</h1>
        <p class="cert-subtitle">Educación STEAM — Moquegua, Perú</p>
      </div>

      <!-- Body -->
      <div class="cert-body">
        <p class="cert-label">Se certifica que el estudiante</p>
        <h2 class="cert-name"><?= $nombre ?></h2>

        <div class="cert-row">
          <div class="cert-field">
            <div class="cert-label">Módulo completado</div>
            <div class="cert-field-val"><?= $modulo ?></div>
          </div>
          <div class="cert-field">
            <div class="cert-label">Área curricular</div>
            <div class="cert-field-val"><?= $curso ?></div>
          </div>
        </div>

        <div class="cert-row">
          <div class="cert-field">
            <div class="cert-label">Estrellas obtenidas</div>
            <div class="stars">
              <?= estrellasHtml($estrellas) ?>
              <span style="font-size:13px;color:#8898aa;margin-left:4px"><?= $estrellas ?>/3</span>
            </div>
          </div>
          <div class="cert-field">
            <div class="cert-label">Fecha de emisión</div>
            <div class="cert-field-val"><?= $fecha ?></div>
          </div>
        </div>

        <div class="cert-divider"></div>

        <div class="cert-seal">
          <div class="seal-brand">
            <div class="seal-mark">IS</div>
            <div>
              <div class="seal-name">INNOVA-STEAM</div>
              <div class="seal-sub">Plataforma educativa oficial</div>
            </div>
          </div>
          <div class="seal-verify">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Certificado N.° <?= str_pad($cert['id'], 6, '0', STR_PAD_LEFT) ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="actions" id="no-print" style="margin-top:20px">
      <button class="print-btn" onclick="window.print()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Imprimir / Guardar PDF
      </button>
    </div>
  </div>
</body>
</html>
