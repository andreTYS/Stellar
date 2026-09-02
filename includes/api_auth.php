<?php
// ============================================================
// INNOVA-STEAM — Autenticación de API por token (clientes móviles)
//
// La web sigue usando la sesión de PHP. Esta capa es para Flutter y
// para cualquier cliente que no sea un navegador.
//
// Diferencia clave con requireLogin(): aquí nunca se redirige. Un
// cliente móvil que recibe un 302 hacia login.php se encuentra HTML
// donde esperaba JSON y no puede distinguir "no autenticado" de
// "error del servidor".
// ============================================================

require_once __DIR__ . '/config.php';

const API_TOKEN_DIAS = 30;   // vigencia de un token

/**
 * Responde en JSON y termina. Todas las salidas de la API pasan por aquí
 * para que el cliente siempre reciba la misma forma.
 */
function apiJson(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function apiError(string $mensaje, int $codigo = 400, array $extra = []): never
{
    apiJson(['ok' => false, 'error' => $mensaje] + $extra, $codigo);
}

/**
 * Lee el cuerpo JSON de la petición.
 */
function apiBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return [];
    $datos = json_decode($raw, true);
    return is_array($datos) ? $datos : [];
}

/**
 * Extrae el token de la cabecera Authorization.
 *
 * Apache no siempre expone esa cabecera a PHP: con mod_php se pierde
 * salvo que se reenvíe explícitamente, y tras una regla de rewrite
 * aparece como REDIRECT_HTTP_AUTHORIZATION. Se prueban las tres vías
 * para no depender de la configuración del servidor.
 */
function apiTokenDeCabecera(): ?string
{
    $cabecera = $_SERVER['HTTP_AUTHORIZATION']
             ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
             ?? '';

    if ($cabecera === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $nombre => $valor) {
            if (strcasecmp($nombre, 'Authorization') === 0) {
                $cabecera = $valor;
                break;
            }
        }
    }

    if (preg_match('/^Bearer\s+(.+)$/i', trim($cabecera), $m)) {
        return trim($m[1]);
    }
    return null;
}

/**
 * Crea un token para un usuario y devuelve el valor en claro.
 * Es la única vez que ese valor existe: en la base solo queda el hash.
 */
function apiCrearToken(int $usuarioId, ?string $dispositivo = null): array
{
    $token = bin2hex(random_bytes(32));           // 256 bits
    $hash  = hash('sha256', $token);
    $expira = (new DateTimeImmutable())->modify('+' . API_TOKEN_DIAS . ' days');

    getDB()->prepare(
        'INSERT INTO api_tokens (usuario_id, token_hash, dispositivo, expira_en)
         VALUES (?, ?, ?, ?)'
    )->execute([
        $usuarioId,
        $hash,
        $dispositivo !== null ? mb_substr($dispositivo, 0, 120) : null,
        $expira->format('Y-m-d H:i:s'),
    ]);

    return ['token' => $token, 'expira_en' => $expira->format(DATE_ATOM)];
}

/**
 * Devuelve el usuario dueño de un token válido, o null.
 */
function apiUsuarioPorToken(?string $token): ?array
{
    if ($token === null || $token === '') return null;

    $stmt = getDB()->prepare(
        'SELECT u.*, t.id AS token_id
           FROM api_tokens t
           JOIN usuarios  u ON u.id = t.usuario_id
          WHERE t.token_hash = ?
            AND t.expira_en  > NOW()
            AND u.activo = 1
          LIMIT 1'
    );
    $stmt->execute([hash('sha256', $token)]);
    $usuario = $stmt->fetch();
    if (!$usuario) return null;

    // Rastro de uso, con el mismo límite de una escritura cada 10
    // minutos que usa la web: una app que sondea cada minuto no debe
    // generar una escritura por petición.
    try {
        getDB()->prepare(
            'UPDATE api_tokens SET ultimo_uso = NOW()
              WHERE id = ? AND (ultimo_uso IS NULL OR ultimo_uso < NOW() - INTERVAL 10 MINUTE)'
        )->execute([$usuario['token_id']]);

        getDB()->prepare(
            'UPDATE usuarios SET ultimo_acceso = NOW()
              WHERE id = ? AND (ultimo_acceso IS NULL OR ultimo_acceso < NOW() - INTERVAL 10 MINUTE)'
        )->execute([$usuario['id']]);
    } catch (\Throwable $e) {}

    return $usuario;
}

/**
 * Exige un token válido. Si se pasan roles, además comprueba el rol.
 * Devuelve el usuario autenticado.
 *
 * @return array datos del usuario
 */
function requireApiAuth(string ...$roles): array
{
    $usuario = apiUsuarioPorToken(apiTokenDeCabecera());

    if (!$usuario) {
        // 401 significa "vuelve a autenticarte": es la señal que le
        // dice al cliente que borre el token guardado.
        apiError('Token ausente, inválido o caducado.', 401);
    }

    if (!empty($roles) && !in_array($usuario['rol'], $roles, true)) {
        apiError('Tu rol no tiene acceso a este recurso.', 403);
    }

    return $usuario;
}

/**
 * Datos públicos de un usuario. Nunca expone el hash de contraseña.
 */
function apiUsuarioPublico(array $u): array
{
    return [
        'id'        => (int)$u['id'],
        'nombre'    => $u['nombre'],
        'apellido'  => $u['apellido'],
        'rol'       => $u['rol'],
        'email'     => $u['email'],
        'codigo'    => $u['codigo_acceso'],
        'colegio_id'=> $u['colegio_id'] !== null ? (int)$u['colegio_id'] : null,
    ];
}
