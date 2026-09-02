-- ================================================================
-- INNOVA-STEAM Migration 008 — Tokens de API para clientes móviles
--
-- La web se autentica con la cookie de sesión de PHP. Una app no
-- puede: no tiene cookies persistentes fiables y requireLogin()
-- le respondería con un 302 a login.php en vez de un error.
--
-- Recordatorio: usuarios.id es INT UNSIGNED. La FK debe declararse
-- UNSIGNED o MySQL la rechaza con errno 150 (ver migraciones 004/005).
-- ================================================================

CREATE TABLE IF NOT EXISTS api_tokens (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id   INT UNSIGNED NOT NULL,

    -- Se guarda el hash, nunca el token. Si alguien lee esta tabla no
    -- puede suplantar a nadie: el valor original solo existe en el
    -- dispositivo. SHA-256 basta porque el token ya es aleatorio de
    -- 256 bits — no hay nada que adivinar por fuerza bruta.
    token_hash   CHAR(64)     NOT NULL,

    -- Para que el usuario pueda reconocer y cerrar sesiones concretas.
    dispositivo  VARCHAR(120) NULL,

    creado_en    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_en    TIMESTAMP    NOT NULL,
    ultimo_uso   TIMESTAMP    NULL DEFAULT NULL,

    UNIQUE KEY uk_token (token_hash),
    INDEX idx_tok_usuario (usuario_id, expira_en),
    CONSTRAINT fk_tok_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
