<?php
// ============================================================
// INNOVA-STEAM — Idempotencia de envíos
//
// Un navegador sin conexión encola el envío y lo reintenta más tarde,
// pero no sabe si el primer intento llegó: pudo llegar y perderse solo
// la respuesta. Si se reejecuta, el quiz suma un intento de más y el
// entregable se duplica.
//
// Con un identificador que pone el cliente, el segundo intento devuelve
// la respuesta del primero sin volver a hacer nada.
// ============================================================

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Normaliza el uuid que llega del cliente. Devuelve null si no tiene
 * forma de UUID: así un valor cualquiera no ocupa la tabla ni sirve
 * para colisionar a propósito con la clave de otro.
 */
function idemUuid(mixed $valor): ?string
{
    if (!is_string($valor)) return null;
    $valor = strtolower(trim($valor));
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $valor)
        ? $valor
        : null;
}

/**
 * Respuesta ya dada a este envío, o null si es la primera vez.
 *
 * Se comprueba también el usuario: el uuid lo elige el cliente, así que
 * sin esto alguien podría leer la respuesta de otro adivinando uno.
 */
function idemRespuestaPrevia(?string $uuid, int $usuarioId, string $endpoint): ?array
{
    if ($uuid === null) return null;

    try {
        $stmt = getDB()->prepare(
            'SELECT respuesta FROM envios_idempotentes
              WHERE cliente_uuid = ? AND usuario_id = ? AND endpoint = ?'
        );
        $stmt->execute([$uuid, $usuarioId, $endpoint]);
        $fila = $stmt->fetch();
    } catch (\Throwable $e) {
        return null;   // migración 013 sin aplicar: se sigue sin idempotencia
    }

    if (!$fila) return null;

    $datos = json_decode((string)$fila['respuesta'], true);
    return is_array($datos) ? $datos : ['ok' => true];
}

/**
 * Guarda la respuesta para que un reintento la repita.
 *
 * Nunca hace fallar la petición: si esto no se puede escribir, el envío
 * ya se procesó y lo peor que pasa es que un reintento lo repita.
 */
function idemGuardar(?string $uuid, int $usuarioId, string $endpoint, array $respuesta): void
{
    if ($uuid === null) return;

    try {
        getDB()->prepare(
            'INSERT INTO envios_idempotentes (cliente_uuid, usuario_id, endpoint, respuesta)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE respuesta = VALUES(respuesta)'
        )->execute([
            $uuid, $usuarioId, $endpoint,
            json_encode($respuesta, JSON_UNESCAPED_UNICODE),
        ]);
    } catch (\Throwable $e) {
        error_log('idempotencia: ' . $e->getMessage());
    }
}

/**
 * Borra los registros viejos. Un envío encolado que no se ha reintentado
 * en una semana ya no va a reintentarse: el navegador se limpió o el
 * dispositivo cambió de manos.
 *
 * Se llama desde cron-resumen.php, que ya corre semanalmente.
 */
function idemLimpiar(int $dias = 7): int
{
    try {
        $stmt = getDB()->prepare(
            'DELETE FROM envios_idempotentes WHERE creado_en < DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([$dias]);
        return $stmt->rowCount();
    } catch (\Throwable $e) {
        return 0;
    }
}
