<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('admin_colegio');

$user = currentUser();
$pdo  = getDB();
$mes  = (int)($_GET['mes'] ?? date('n'));
$anio = (int)($_GET['anio'] ?? date('Y'));

// School info
$colegio = $pdo->prepare("SELECT * FROM colegios WHERE id=?");
$colegio->execute([$user['colegio_id']]);
$colegio = $colegio->fetch();

// Stats
$totalEstudiantes = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE colegio_id=? AND rol='estudiante' AND activo=1");
$totalEstudiantes->execute([$user['colegio_id']]);
$totalEstudiantes = $totalEstudiantes->fetchColumn();

$modulosCompletados = $pdo->prepare("
    SELECT COUNT(*) FROM progreso_estudiante pe
    JOIN usuarios u ON u.id=pe.estudiante_id
    WHERE u.colegio_id=? AND pe.completado=1
    AND MONTH(pe.completado_en)=? AND YEAR(pe.completado_en)=?
");
$modulosCompletados->execute([$user['colegio_id'], $mes, $anio]);
$modulosCompletados = $modulosCompletados->fetchColumn();

$entregables = $pdo->prepare("
    SELECT COUNT(*) FROM entregables e
    JOIN usuarios u ON u.id=e.estudiante_id
    WHERE u.colegio_id=? AND MONTH(e.subido_en)=? AND YEAR(e.subido_en)=?
");
$entregables->execute([$user['colegio_id'], $mes, $anio]);
$entregables = $entregables->fetchColumn();

$sesiones = $pdo->prepare("
    SELECT COUNT(*) FROM sesiones s
    JOIN aulas a ON a.id=s.aula_id
    WHERE a.colegio_id=? AND MONTH(s.fecha_sesion)=? AND YEAR(s.fecha_sesion)=?
");
$sesiones->execute([$user['colegio_id'], $mes, $anio]);
$sesiones = $sesiones->fetchColumn();

// Progress per course
$porCurso = $pdo->prepare("
    SELECT c.nombre, c.color_hex, c.icono,
           COUNT(DISTINCT pe.estudiante_id) as estudiantes_avanzando,
           SUM(pe.completado) as completaciones
    FROM cursos c
    LEFT JOIN modulos m ON m.curso_id=c.id
    LEFT JOIN progreso_estudiante pe ON pe.modulo_id=m.id
    LEFT JOIN usuarios u ON u.id=pe.estudiante_id
    WHERE (u.colegio_id=? OR u.id IS NULL)
    GROUP BY c.id ORDER BY c.id
");
$porCurso->execute([$user['colegio_id']]);
$porCurso = $porCurso->fetchAll();

// Aulas with practicantes
$aulas = $pdo->prepare("
    SELECT a.grado, a.seccion,
           u_doc.nombre as docente_nombre, u_doc.apellido as docente_apellido,
           GROUP_CONCAT(DISTINCT CONCAT(u_prac.nombre,' ',u_prac.apellido) SEPARATOR ', ') as practicantes,
           COUNT(DISTINCT ea.estudiante_id) as estudiantes,
           SUM(pe.completado) as modulos_completados
    FROM aulas a
    LEFT JOIN usuarios u_doc ON u_doc.id=a.docente_id
    LEFT JOIN practicante_aula pa ON pa.aula_id=a.id
    LEFT JOIN usuarios u_prac ON u_prac.id=pa.practicante_id
    LEFT JOIN estudiante_aula ea ON ea.aula_id=a.id
    LEFT JOIN progreso_estudiante pe ON pe.estudiante_id=ea.estudiante_id AND pe.completado=1
    WHERE a.colegio_id=?
    GROUP BY a.id
");
$aulas->execute([$user['colegio_id']]);
$aulas = $aulas->fetchAll();

$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_dre_' . $meses[$mes] . '_' . $anio . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['REPORTE DRE/UGEL — INNOVA-STEAM']);
    fputcsv($out, ['Colegio', $colegio['nombre'] ?? '']);
    fputcsv($out, ['Período', $meses[$mes] . ' ' . $anio]);
    fputcsv($out, ['UGEL', $colegio['ugel_codigo'] ?? '']);
    fputcsv($out, []);
    fputcsv($out, ['Estudiantes inscritos', $totalEstudiantes]);
    fputcsv($out, ['Módulos completados', $modulosCompletados]);
    fputcsv($out, ['Entregables subidos', $entregables]);
    fputcsv($out, ['Sesiones realizadas', $sesiones]);
    fputcsv($out, []);
    fputcsv($out, ['Aula','Docente','Practicante(s)','Estudiantes','Módulos completados']);
    foreach ($aulas as $a) {
        fputcsv($out, [$a['grado'].'"'.$a['seccion'].'"', $a['docente_apellido'].', '.$a['docente_nombre'], $a['practicantes'] ?? '', $a['estudiantes'], $a['modulos_completados']]);
    }
    fclose($out);
    exit;
}

$pageTitle = 'Reporte DRE/UGEL';
$activeNav = 'reporte';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
  <!-- Controls -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
    <div>
      <h1 style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--text-primary);">Reporte DRE/UGEL</h1>
      <p style="color:var(--text-secondary);font-size:13px;"><?= sanitize($colegio['nombre'] ?? '') ?> · <?= $meses[$mes] ?> <?= $anio ?></p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <form method="GET" style="display:flex;gap:8px;">
        <select name="mes" style="background:var(--bg-elevated);border:1px solid var(--bg-border);color:var(--text-primary);border-radius:8px;padding:8px 12px;font-size:13px;">
          <?php for($i=1;$i<=12;$i++): ?>
          <option value="<?=$i?>" <?=$i==$mes?'selected':''?>><?=$meses[$i]?></option>
          <?php endfor; ?>
        </select>
        <select name="anio" style="background:var(--bg-elevated);border:1px solid var(--bg-border);color:var(--text-primary);border-radius:8px;padding:8px 12px;font-size:13px;">
          <?php for($y=2024;$y<=2027;$y++): ?><option value="<?=$y?>" <?=$y==$anio?'selected':''?>><?=$y?></option><?php endfor; ?>
        </select>
        <button type="submit" class="btn-primary" style="padding:8px 14px;font-size:13px;">Filtrar</button>
      </form>
      <a href="?mes=<?=$mes?>&anio=<?=$anio?>&export=csv" class="btn-ghost" style="padding:8px 14px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px"><i data-lucide="download" style="width:16px;height:16px"></i> CSV</a>
      <button onclick="window.print()" class="btn-ghost" style="padding:8px 14px;font-size:13px;"><i data-lucide="printer" style="width:16px;height:16px"></i> Imprimir</button>
    </div>
  </div>

  <!-- Institution header for report -->
  <div class="card" style="margin-bottom:24px;padding:20px 24px;border-top:4px solid var(--blue);">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <div>
        <p style="font-size:11px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">Institución Educativa</p>
        <p style="font-size:18px;font-weight:700;color:var(--text-primary);"><?= sanitize($colegio['nombre'] ?? '') ?></p>
        <p style="font-size:13px;color:var(--text-secondary);margin-top:2px;">Distrito: <?= sanitize($colegio['distrito'] ?? '') ?></p>
        <p style="font-size:13px;color:var(--text-secondary);">UGEL: <?= sanitize($colegio['ugel_codigo'] ?? '—') ?></p>
      </div>
      <div>
        <p style="font-size:11px;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">Período reportado</p>
        <p style="font-size:18px;font-weight:700;color:var(--text-primary);"><?= $meses[$mes] ?> <?= $anio ?></p>
        <p style="font-size:13px;color:var(--text-secondary);margin-top:2px;">Director(a): <?= sanitize($colegio['director'] ?? '—') ?></p>
        <p style="font-size:13px;color:var(--text-secondary);">Programa: INNOVA-STEAM</p>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card"><div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)"><i data-lucide="graduation-cap" style="width:22px;height:22px"></i></div><div class="stat-value" style="color:var(--blue);"><?= $totalEstudiantes ?></div><div class="stat-label">Estudiantes inscritos</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)"><i data-lucide="check-circle-2" style="width:22px;height:22px"></i></div><div class="stat-value" style="color:var(--green);"><?= $modulosCompletados ?></div><div class="stat-label">Módulos completados</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)"><i data-lucide="folder" style="width:22px;height:22px"></i></div><div class="stat-value" style="color:var(--purple);"><?= $entregables ?></div><div class="stat-label">Entregables</div></div>
    <div class="stat-card"><div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)"><i data-lucide="calendar" style="width:22px;height:22px"></i></div><div class="stat-value" style="color:var(--gold);"><?= $sesiones ?></div><div class="stat-label">Sesiones realizadas</div></div>
  </div>

  <!-- Progress per course -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2 class="card-title">Avance por área curricular</h2></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
      <?php foreach ($porCurso as $c): ?>
      <div style="background:var(--bg-elevated);border-radius:12px;padding:16px;border:1px solid var(--bg-border);text-align:center;">
        <div style="font-size:28px;margin-bottom:8px;"><?= $c['icono'] ?></div>
        <p style="font-size:13px;font-weight:600;color:<?= $c['color_hex'] ?>;margin-bottom:10px;"><?= sanitize($c['nombre']) ?></p>
        <p style="font-size:24px;font-weight:800;color:var(--text-primary);font-family:'Syne',sans-serif;"><?= $c['completaciones'] ?></p>
        <p style="font-size:11px;color:var(--text-secondary);">completaciones</p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Aulas table -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header"><h2 class="card-title">Detalle por aula</h2></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Aula</th><th>Docente titular</th><th>Practicante(s)</th><th>Estudiantes</th><th>Módulos completados</th></tr></thead>
        <tbody>
          <?php foreach ($aulas as $a): ?>
          <tr>
            <td style="font-weight:700;"><?= $a['grado'] ?> &ldquo;<?= sanitize($a['seccion']) ?>&rdquo;</td>
            <td style="color:var(--text-secondary);"><?= sanitize($a['docente_apellido'].', '.$a['docente_nombre']) ?></td>
            <td style="color:var(--text-secondary);font-size:13px;"><?= sanitize($a['practicantes'] ?? '—') ?></td>
            <td style="color:var(--blue);font-weight:700;"><?= $a['estudiantes'] ?></td>
            <td style="color:var(--green);font-weight:700;"><?= $a['modulos_completados'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Signatures -->
  <div class="card">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:40px;padding-top:40px;">
      <?php foreach(['Director(a) de la IE','Coordinador(a) INNOVA-STEAM','V°B° UGEL Moquegua'] as $firma): ?>
      <div style="border-top:1px solid var(--bg-border);padding-top:12px;text-align:center;">
        <p style="font-size:12px;color:var(--text-secondary);"><?= $firma ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <p style="font-size:11px;color:var(--text-muted);text-align:center;margin-top:20px;">
      Generado por INNOVA-STEAM — <?= date('d/m/Y H:i') ?>
    </p>
  </div>
</div>

<style>
@media print {
  .sidebar,.topbar,form,.btn-ghost,.btn-primary{display:none !important;}
  .main-content{margin-left:0 !important;}
  .card{border:1px solid #ccc !important;background:#fff !important;color:#000 !important;}
  .stat-value,.card-title{color:#000 !important;}
  body{background:#fff;}
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
