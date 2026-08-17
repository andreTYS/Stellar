<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin('practicante');

$user = currentUser();
$pdo  = getDB();
$mes  = (int)($_GET['mes'] ?? date('n'));
$anio = (int)($_GET['anio'] ?? date('Y'));

// Fetch aulas del practicante
$aulas = $pdo->prepare("
    SELECT a.*, col.nombre as colegio_nombre
    FROM aulas a
    JOIN practicante_aula pa ON pa.aula_id = a.id
    JOIN colegios col ON col.id = a.colegio_id
    WHERE pa.practicante_id = ?
");
$aulas->execute([$user['id']]);
$aulas = $aulas->fetchAll();

// Sesiones del mes
$sesiones = $pdo->prepare("
    SELECT s.*, a.grado, a.seccion, col.nombre as colegio_nombre,
           m.titulo as modulo_titulo, c.nombre as curso_nombre, c.color_hex
    FROM sesiones s
    JOIN aulas a ON a.id = s.aula_id
    JOIN colegios col ON col.id = a.colegio_id
    JOIN modulos m ON m.id = s.modulo_id
    JOIN cursos c ON c.id = m.curso_id
    WHERE s.practicante_id = ?
      AND MONTH(s.fecha_sesion) = ?
      AND YEAR(s.fecha_sesion) = ?
    ORDER BY s.fecha_sesion ASC
");
$sesiones->execute([$user['id'], $mes, $anio]);
$sesiones = $sesiones->fetchAll();

$totalAsistentes = array_sum(array_column($sesiones, 'asistentes'));
$meses = ['', 'Enero','Febrero','Marzo','Abril','Mayo','Junio',
           'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

$pageTitle = 'Informe Mensual';
$activeNav = 'informe';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content">
  <!-- Header -->
  <div class="card" style="margin-bottom:24px; background: linear-gradient(135deg, #12121a, #1a1a26);">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
      <div>
        <h1 style="font-family:'Syne',sans-serif; font-size:24px; font-weight:800; color:var(--text-primary); margin-bottom:6px;">
          Informe Mensual — <?= $meses[$mes] ?> <?= $anio ?>
        </h1>
        <p style="color:var(--text-secondary); font-size:14px;">
          <?= sanitize($user['nombre'].' '.$user['apellido']) ?> · Practicante
        </p>
      </div>
      <div style="display:flex; gap:10px;">
        <!-- Filtro de mes -->
        <form method="GET" style="display:flex; gap:8px;">
          <select name="mes" class="form-select" style="background:var(--bg-elevated); border:1px solid var(--bg-border); color:var(--text-primary); border-radius:8px; padding:8px 12px; font-size:13px;">
            <?php for($i=1;$i<=12;$i++): ?>
              <option value="<?=$i?>" <?=$i==$mes?'selected':''?>><?=$meses[$i]?></option>
            <?php endfor; ?>
          </select>
          <select name="anio" class="form-select" style="background:var(--bg-elevated); border:1px solid var(--bg-border); color:var(--text-primary); border-radius:8px; padding:8px 12px; font-size:13px;">
            <?php for($y=2024;$y<=2027;$y++): ?>
              <option value="<?=$y?>" <?=$y==$anio?'selected':''?>><?=$y?></option>
            <?php endfor; ?>
          </select>
          <button type="submit" class="btn-primary" style="padding:8px 16px; font-size:13px;">Filtrar</button>
        </form>
        <button onclick="window.print()" class="btn-ghost" style="padding:8px 16px; font-size:13px;">
          <i data-lucide="printer" style="width:16px;height:16px"></i> Imprimir
        </button>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(74,158,255,.12);color:var(--blue)"><i data-lucide="calendar" style="width:22px;height:22px"></i></div>
      <div class="stat-value" style="color:var(--blue);"><?= count($sesiones) ?></div>
      <div class="stat-label">Sesiones realizadas</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(62,207,142,.12);color:var(--green)"><i data-lucide="users" style="width:22px;height:22px"></i></div>
      <div class="stat-value" style="color:var(--green);"><?= $totalAsistentes ?></div>
      <div class="stat-label">Asistencias totales</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(167,139,250,.12);color:var(--purple)"><i data-lucide="building-2" style="width:22px;height:22px"></i></div>
      <div class="stat-value" style="color:var(--purple);"><?= count($aulas) ?></div>
      <div class="stat-label">Aulas atendidas</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(245,200,66,.12);color:var(--gold)"><i data-lucide="timer" style="width:22px;height:22px"></i></div>
      <div class="stat-value" style="color:var(--gold);"><?= count($sesiones) * 45 ?></div>
      <div class="stat-label">Minutos de práctica</div>
    </div>
  </div>

  <!-- Tabla de sesiones -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Detalle de sesiones</h2>
    </div>

    <?php if (empty($sesiones)): ?>
      <div style="text-align:center; padding:40px;">
        <div class="empty-icon"><i data-lucide="clipboard-list" style="width:56px;height:56px;opacity:.4"></i></div>
        <p style="color:var(--text-secondary);">No hay sesiones registradas en <?= $meses[$mes] ?> <?= $anio ?></p>
      </div>
    <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Institución / Aula</th>
              <th>Módulo</th>
              <th>Curso</th>
              <th>Asistentes</th>
              <th>Notas</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sesiones as $s): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($s['fecha_sesion'])) ?></td>
              <td>
                <div style="font-weight:600;"><?= sanitize($s['colegio_nombre']) ?></div>
                <div style="font-size:12px; color:var(--text-secondary);"><?= $s['grado'] ?> <?= $s['seccion'] ?></div>
              </td>
              <td><?= sanitize($s['modulo_titulo']) ?></td>
              <td>
                <span class="badge-curso" style="background:<?= $s['color_hex'] ?>22; color:<?= $s['color_hex'] ?>; border:1px solid <?= $s['color_hex'] ?>44; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;">
                  <?= sanitize($s['curso_nombre']) ?>
                </span>
              </td>
              <td style="font-weight:700; color:var(--blue);"><?= $s['asistentes'] ?></td>
              <td style="font-size:13px; color:var(--text-secondary); max-width:200px;"><?= sanitize($s['notas'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Firma -->
  <div class="card" style="margin-top:24px;">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:40px;">
      <div>
        <div style="border-top:1px solid var(--bg-border); padding-top:12px; margin-top:40px;">
          <p style="font-size:13px; color:var(--text-secondary);">Firma del practicante</p>
          <p style="font-weight:600; color:var(--text-primary);"><?= sanitize($user['nombre'].' '.$user['apellido']) ?></p>
        </div>
      </div>
      <div>
        <div style="border-top:1px solid var(--bg-border); padding-top:12px; margin-top:40px;">
          <p style="font-size:13px; color:var(--text-secondary);">Sello y firma del docente asesor</p>
        </div>
      </div>
    </div>
    <p style="font-size:11px; color:var(--text-muted); margin-top:20px; text-align:center;">
      Informe generado por INNOVA-STEAM · <?= date('d/m/Y H:i') ?>
    </p>
  </div>
</div>

<style>
@media print {
  .sidebar, .topbar, .btn-primary, .btn-ghost, form { display: none !important; }
  .main-content { margin-left: 0 !important; }
  .card { border: 1px solid #ccc !important; background: #fff !important; color: #000 !important; }
  .stat-value, .card-title { color: #000 !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
