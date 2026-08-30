<?php
// ============================================================
// INNOVA-STEAM — Configuración central
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'innovasteam');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'INNOVA-STEAM');
define('APP_VERSION', '2.0');
// Base URL — fixed for symlinked deployment under /srv/www/innovasteam
define('BASE_URL', '/innovasteam');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('MAX_UPLOAD_MB', 5);
define('MAX_UPLOAD_BYTES', MAX_UPLOAD_MB * 1024 * 1024);

// ── Base de datos ────────────────────────────────────────────
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;padding:20px;background:#1a0000;color:#ff6b6b;border:1px solid #ff6b6b;border-radius:8px;margin:20px">
                <strong>Error de base de datos:</strong><br>' . htmlspecialchars($e->getMessage()) . '
                <p style="color:#94a3b8;font-size:12px">Verifica que XAMPP esté corriendo y que la base de datos <code>innovasteam</code> exista.</p>
            </div>');
        }
    }
    return $pdo;
}

// ── Sesión ───────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Autenticación ────────────────────────────────────────────
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Requiere login. Si se pasan roles, verifica que el usuario tenga uno de ellos.
 */
function requireLogin(string ...$roles): void
{
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php');
    }
    if (!empty($roles) && !in_array($_SESSION['rol'], $roles, true)) {
        redirect(BASE_URL . '/login.php?error=acceso');
    }
    tocarUltimoAcceso();
}

/**
 * Refresca usuarios.ultimo_acceso mientras el usuario navega.
 *
 * Sin esto la columna solo se escribiría al iniciar sesión, y quien
 * deja la sesión abierta toda la semana aparecería como rezagado. Se
 * limita a una escritura cada 10 minutos por sesión: en un aula de
 * treinta estudiantes, una por página sería una escritura por clic.
 */
function tocarUltimoAcceso(): void
{
    $ahora = time();
    if (isset($_SESSION['ultimo_toque']) && ($ahora - $_SESSION['ultimo_toque']) < 600) {
        return;
    }
    $_SESSION['ultimo_toque'] = $ahora;

    try {
        getDB()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')
               ->execute([currentUserId()]);
    } catch (\Throwable $e) {
        // La columna llega con la migración 007; si no está, no pasa nada.
    }
}

function currentUser(): array
{
    return $_SESSION['usuario'] ?? [];
}

function currentRole(): string
{
    return $_SESSION['rol'] ?? '';
}

function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

// ── Helpers de navegación ────────────────────────────────────
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function dashboardUrl(): string
{
    return match ($_SESSION['rol'] ?? '') {
        'admin'          => BASE_URL . '/admin/index.php',
        'admin_colegio'  => BASE_URL . '/admin_colegio/index.php',
        'docente'        => BASE_URL . '/docente/index.php',
        'practicante'    => BASE_URL . '/practicante/index.php',
        'estudiante'     => BASE_URL . '/estudiante/index.php',
        'apoderado'      => BASE_URL . '/apoderado/index.php',
        default          => BASE_URL . '/login.php',
    };
}

// ── Sanitización ────────────────────────────────────────────
function sanitize(string $val): string
{
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function jsonDecode(string $json): mixed
{
    return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}

// ── Flash messages ───────────────────────────────────────────
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── CSRF protection ──────────────────────────────────────────
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Error de seguridad: token CSRF inválido. Recarga la página e intenta de nuevo.');
    }
}

/**
 * Igual que verifyCsrf(), pero para endpoints que reciben el cuerpo en
 * JSON, donde $_POST llega vacío. Responde en JSON en vez de con HTML.
 *
 * Acepta el token por cabecera o dentro del propio cuerpo. Lo segundo
 * hace falta para navigator.sendBeacon(), que no permite añadir
 * cabeceras: es la única vía de enviarlo desde ahí.
 *
 * @param array|null $data cuerpo JSON ya decodificado
 */
function verifyCsrfJson(?array $data = null): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN']
          ?? $data['csrf_token']
          ?? $_POST['csrf_token']
          ?? '';

    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'    => false,
            'error' => 'Token CSRF inválido. Recarga la página e intenta de nuevo.',
        ]);
        exit;
    }
}

// ── Upload de archivos ───────────────────────────────────────
function handleUpload(array $file, string $subfolder = 'entregables'): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_UPLOAD_BYTES) return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    if (!in_array($ext, $allowed, true)) return false;

    $dir = UPLOAD_DIR . $subfolder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = uniqid('', true) . '.' . $ext;
    $path = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $path)) return false;

    return UPLOAD_URL . $subfolder . '/' . $filename;
}
