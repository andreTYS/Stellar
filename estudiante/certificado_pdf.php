<?php
// ============================================================
// INNOVA-STEAM — Certificado en PDF (generado en el servidor)
//
// Sustituye a la generación con html2canvas + jsPDF, que dependía
// de dos CDN y rasterizaba el certificado a JPEG. Aquí el texto sale
// vectorial, se puede seleccionar, y funciona sin conexión — que es
// el escenario real en varios colegios.
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin('estudiante');

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    exit('Falta instalar las dependencias. Ejecuta "composer install" en la raíz del proyecto.');
}
require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;

$estudianteId = currentUserId();
$certId       = (int)($_GET['cert_id'] ?? 0);

if ($certId <= 0) {
    redirect(BASE_URL . '/estudiante/certificados.php');
}

$pdo  = getDB();
$stmt = $pdo->prepare(
    'SELECT cert.*, m.titulo AS modulo_titulo, c.nombre AS curso_nombre, c.color_hex,
            u.nombre AS est_nombre, u.apellido AS est_apellido,
            COALESCE(pe.estrellas_quiz, 0) AS estrellas_quiz
     FROM certificados cert
     JOIN modulos  m ON m.id = cert.modulo_id
     JOIN cursos   c ON c.id = m.curso_id
     JOIN usuarios u ON u.id = cert.estudiante_id
     LEFT JOIN progreso_estudiante pe
            ON pe.estudiante_id = cert.estudiante_id AND pe.modulo_id = cert.modulo_id
     WHERE cert.id = ? AND cert.estudiante_id = ?'
);
$stmt->execute([$certId, $estudianteId]);
$cert = $stmt->fetch();

// El WHERE ya restringe al dueño: un id ajeno simplemente no devuelve fila.
if (!$cert) {
    redirect(BASE_URL . '/estudiante/certificados.php');
}

$codigo    = certificadoCodigo($pdo, (int)$cert['id']);
$color     = preg_match('/^#[0-9a-f]{6}$/i', $cert['color_hex'] ?? '') ? $cert['color_hex'] : '#4361ee';
$nombre    = htmlspecialchars(trim($cert['est_nombre'] . ' ' . $cert['est_apellido']), ENT_QUOTES, 'UTF-8');
$modulo    = htmlspecialchars($cert['modulo_titulo'], ENT_QUOTES, 'UTF-8');
$curso     = htmlspecialchars($cert['curso_nombre'], ENT_QUOTES, 'UTF-8');
$estrellas = max(0, min(3, (int)$cert['estrellas_quiz']));
$fecha     = formatDate($cert['emitido_en']);
$numero    = str_pad((string)$cert['id'], 6, '0', STR_PAD_LEFT);

// Dompdf no implementa flexbox ni grid, así que la maqueta va con
// tablas y posicionamiento absoluto. DejaVu Sans es la fuente que trae
// por defecto y cubre los acentos del español sin incrustar nada.
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<style>
  @page { margin: 0; }
  body  { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #1a1f36; }

  .band {
    background: {$color};
    padding: 46px 48px 38px;
    text-align: center;
    color: #ffffff;
  }
  .band .tag {
    display: inline-block;
    border: 1px solid #ffffff;
    border-radius: 14px;
    padding: 3px 16px;
    font-size: 10pt;
    font-weight: bold;
    margin-bottom: 14px;
  }
  .band h1     { font-size: 26pt; margin: 0 0 4px; font-weight: bold; }
  .band .sub   { font-size: 10pt; }

  .body { padding: 40px 48px 32px; }

  .label {
    font-size: 8pt;
    color: #8898aa;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 4px;
  }
  .name  { font-size: 22pt; font-weight: bold; margin: 0 0 26px; }

  table.fields { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  table.fields td { width: 50%; vertical-align: top; padding: 0 12px 18px 0; }
  .value { font-size: 11.5pt; font-weight: bold; }

  .rule { border-top: 1px solid #e4e8f0; margin: 12px 0 22px; }

  .seal { background: #f8f9fc; border-radius: 10px; padding: 16px 20px; }
  table.seal-row { width: 100%; border-collapse: collapse; }
  table.seal-row td { vertical-align: middle; }
  .mark {
    background: {$color};
    color: #ffffff;
    font-size: 11pt;
    font-weight: bold;
    padding: 9px 11px;
    border-radius: 7px;
  }
  .brand   { font-size: 11pt; font-weight: bold; }
  .brand-s { font-size: 8.5pt; color: #8898aa; }
  .verify  { text-align: right; font-size: 8.5pt; color: #8898aa; line-height: 1.5; }
  .code    { font-size: 10pt; font-weight: bold; color: #1a1f36; letter-spacing: 1px; }

  .stars      { font-size: 15pt; color: #f5c842; letter-spacing: 2px; }
  .stars-off  { color: #d8dee9; }
  .stars-note { font-size: 9pt; color: #8898aa; }

  .foot {
    text-align: center;
    font-size: 8pt;
    color: #a5b0c2;
    padding: 0 48px 26px;
  }
</style>
</head>
<body>

  <div class="band">
    <div class="tag">INNOVA-STEAM</div>
    <h1>Certificado de Logro</h1>
    <div class="sub">Educación STEAM — Moquegua, Perú</div>
  </div>

  <div class="body">
    <div class="label">Se certifica que el estudiante</div>
    <div class="name">{$nombre}</div>

    <table class="fields">
      <tr>
        <td>
          <div class="label">Módulo completado</div>
          <div class="value">{$modulo}</div>
        </td>
        <td>
          <div class="label">Área curricular</div>
          <div class="value">{$curso}</div>
        </td>
      </tr>
      <tr>
        <td>
          <div class="label">Estrellas obtenidas</div>
          <div class="stars">STARS_PLACEHOLDER
            <span class="stars-note">{$estrellas}/3</span>
          </div>
        </td>
        <td>
          <div class="label">Fecha de emisión</div>
          <div class="value">{$fecha}</div>
        </td>
      </tr>
    </table>

    <div class="rule"></div>

    <div class="seal">
      <table class="seal-row">
        <tr>
          <td width="46"><span class="mark">IS</span></td>
          <td>
            <div class="brand">INNOVA-STEAM</div>
            <div class="brand-s">Plataforma educativa oficial</div>
          </td>
          <td class="verify">
            Certificado N.° {$numero}<br/>
            <span class="code">{$codigo}</span>
          </td>
        </tr>
      </table>
    </div>
  </div>

  <div class="foot">
    Verifica la autenticidad de este certificado en innovasteam.pe/verificar con el código {$codigo}
  </div>

</body>
</html>
HTML;

// Las estrellas se arman aparte para no pelear con la interpolación
// del heredoc.
$starsMarkup = str_repeat('★', $estrellas)
             . '<span class="stars-off">' . str_repeat('★', 3 - $estrellas) . '</span> ';
$html = str_replace('STARS_PLACEHOLDER', $starsMarkup, $html);

$options = new Options();
$options->set('isRemoteEnabled', false);   // sin peticiones de red al renderizar
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("certificado-{$numero}.pdf", ['Attachment' => true]);
exit;
