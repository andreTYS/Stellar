-- ================================================================
-- INNOVA-STEAM Migration 009 — Asistente por colegio
--
-- Cada colegio usa su propia clave de API, que asigna el administrador
-- general. Así el consumo se factura a quien corresponde y un colegio
-- no puede gastar el saldo de otro.
-- ================================================================

-- ── Configuración por colegio ────────────────────────────────────
ALTER TABLE colegios
    -- La clave va cifrada, nunca en claro: quien lea un volcado de la
    -- base no puede usarla. El descifrado necesita CHATBOT_CLAVE_MAESTRA,
    -- que vive fuera de la base de datos.
    ADD COLUMN IF NOT EXISTS chatbot_clave     VARBINARY(512) NULL,
    -- Se guardan los cuatro últimos caracteres para que el admin
    -- reconozca cuál puso sin volver a verla entera.
    ADD COLUMN IF NOT EXISTS chatbot_pista     VARCHAR(8)     NULL,
    ADD COLUMN IF NOT EXISTS chatbot_modelo    VARCHAR(60)    NOT NULL DEFAULT 'claude-opus-5',
    ADD COLUMN IF NOT EXISTS chatbot_activo    TINYINT(1)     NOT NULL DEFAULT 0,
    -- Tope de preguntas por estudiante y día. Evita que una clase entera
    -- agote el saldo del colegio en una tarde.
    ADD COLUMN IF NOT EXISTS chatbot_tope_dia  SMALLINT UNSIGNED NOT NULL DEFAULT 30;

-- ── Registro de conversaciones ───────────────────────────────────
-- La plataforma la usan menores, así que las conversaciones quedan
-- registradas y el docente de su aula puede revisarlas.
CREATE TABLE IF NOT EXISTS chatbot_mensajes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    colegio_id  INT UNSIGNED NULL,
    pregunta    TEXT         NOT NULL,
    respuesta   MEDIUMTEXT   NULL,
    -- 'ok', 'rechazado' (fuera de tema), 'error', 'tope'
    estado      VARCHAR(20)  NOT NULL DEFAULT 'ok',
    modulo_id   INT UNSIGNED NULL,
    tokens_in   INT UNSIGNED NOT NULL DEFAULT 0,
    tokens_out  INT UNSIGNED NOT NULL DEFAULT 0,
    creado_en   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_cb_usuario (usuario_id, creado_en),
    INDEX idx_cb_colegio (colegio_id, creado_en),
    CONSTRAINT fk_cb_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
