<?php
// ============================================================
// INNOVA-STEAM — API: Subir entregable (multipart/form-data)
// POST fields: archivo (file), modulo_id (int), formato (string)
// ============================================================
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

requireLogin('estudiante');
header('Content-Type: application/json; charset=utf-8');

// ── Only POST ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'método no permitido']);
    exit;
}

verifyCsrf();

// ── Input ─────────────────────────────────────────────────────
$moduloId = (int)($_POST['modulo_id'] ?? 0);
$formato  = trim($_POST['formato'] ?? '');
$userId   = currentUserId();

if (!$moduloId) {
    echo json_encode(['ok' => false, 'error' => 'modulo_id requerido']);
    exit;
}

// ── File validation ───────────────────────────────────────────
if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['ok' => false, 'error' => 'no se recibió ningún archivo']);
    exit;
}

$file = $_FILES['archivo'];

// Upload error check
if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'el archivo supera el límite del servidor',
        UPLOAD_ERR_FORM_SIZE  => 'el archivo supera el límite del formulario',
        UPLOAD_ERR_PARTIAL    => 'el archivo se subió de forma incompleta',
        UPLOAD_ERR_NO_TMP_DIR => 'carpeta temporal no disponible',
        UPLOAD_ERR_CANT_WRITE => 'error al escribir en disco',
        UPLOAD_ERR_EXTENSION  => 'extensión de PHP bloqueó la subida',
    ];
    $msg = $uploadErrors[$file['error']] ?? 'error desconocido al subir';
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

// Size check (max 5 MB)
if ($file['size'] > MAX_UPLOAD_BYTES) {
    $mb = round($file['size'] / 1048576, 1);
    echo json_encode([
        'ok'    => false,
        'error' => "el archivo pesa {$mb} MB; el máximo permitido es " . MAX_UPLOAD_MB . ' MB',
    ]);
    exit;
}

// MIME & extension whitelist — only images
$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/jpg'  => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];
$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeReal = $finfo->file($file['tmp_name']);

if (!in_array($ext, $allowedExts, true) || !isset($allowedMimes[$mimeReal])) {
    echo json_encode([
        'ok'    => false,
        'error' => 'tipo de archivo no permitido. Solo imágenes (JPG, PNG, WEBP, GIF)',
    ]);
    exit;
}

// Use real extension from MIME, not the user-supplied one
$safeExt = $allowedMimes[$mimeReal];

// ── Ensure upload directory exists ────────────────────────────
$uploadDir = UPLOAD_DIR . 'entregables/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    echo json_encode(['ok' => false, 'error' => 'no se pudo crear el directorio de subida']);
    exit;
}

// ── Build unique filename ─────────────────────────────────────
$filename = sprintf(
    '%d_%d_%s.%s',
    $userId,
    $moduloId,
    bin2hex(random_bytes(8)),
    $safeExt
);
$destPath = $uploadDir . $filename;

// ── Move file ─────────────────────────────────────────────────
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['ok' => false, 'error' => 'no se pudo guardar el archivo en el servidor']);
    exit;
}

$fileUrl = UPLOAD_URL . 'entregables/' . $filename;

// ── Verify module exists ──────────────────────────────────────
$pdo = getDB();
$stmtMod = $pdo->prepare('SELECT id FROM modulos WHERE id = ? AND activo = 1');
$stmtMod->execute([$moduloId]);
if (!$stmtMod->fetch()) {
    // Remove the file we just saved
    @unlink($destPath);
    echo json_encode(['ok' => false, 'error' => 'módulo no encontrado']);
    exit;
}

// ── Sanitize formato ──────────────────────────────────────────
$formatosValidos = ['dibujo_cientifico', 'mural_digital', 'cuento_ilustrado', 'prototipo', 'ficha', 'otro'];
if (!in_array($formato, $formatosValidos, true)) {
    $formato = 'otro';
}

// ── Insert into entregables ───────────────────────────────────
try {
    $pdo->prepare(
        'INSERT INTO entregables
            (estudiante_id, modulo_id, formato, archivo_url, subido_en)
         VALUES (?, ?, ?, ?, NOW())'
    )->execute([$userId, $moduloId, $formato, $fileUrl]);

    $entregableId = (int)$pdo->lastInsertId();
} catch (PDOException $e) {
    @unlink($destPath);
    echo json_encode(['ok' => false, 'error' => 'error al registrar el entregable en la base de datos']);
    exit;
}

// ── Success ───────────────────────────────────────────────────
echo json_encode([
    'ok'           => true,
    'url'          => $fileUrl,
    'entregable_id' => $entregableId,
    'formato'      => $formato,
]);
