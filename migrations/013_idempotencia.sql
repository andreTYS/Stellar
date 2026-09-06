-- ================================================================
-- INNOVA-STEAM Migration 013 — Idempotencia de envíos
--
-- La cola sin conexión reintenta un envío sin saber si el primero
-- llegó: puede haber llegado y haberse perdido solo la respuesta. Sin
-- esto, un quiz reenviado sumaría un intento de más y un entregable
-- reenviado se duplicaría.
--
-- El cliente manda un cliente_uuid con cada envío. Si ya está aquí, se
-- devuelve la respuesta guardada sin volver a ejecutar nada.
-- ================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS envios_idempotentes (
    cliente_uuid CHAR(36)     NOT NULL PRIMARY KEY,
    usuario_id   INT UNSIGNED NOT NULL,
    endpoint     VARCHAR(60)  NOT NULL,
    -- La respuesta que se dio la primera vez, tal cual, para poder
    -- repetirla sin recalcular.
    respuesta    TEXT         NULL,
    creado_en    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- El uuid es del cliente, así que se comprueba junto al usuario:
    -- nadie puede reclamar el envío de otro adivinando un uuid.
    KEY idx_idem_usuario (usuario_id, endpoint),
    -- Para la limpieza periódica de filas viejas.
    KEY idx_idem_creado (creado_en),
    CONSTRAINT fk_idem_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
