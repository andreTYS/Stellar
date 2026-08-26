-- ================================================================
-- INNOVA-STEAM Migration 007 — Seguridad y rastro de actividad
--
--   a) Registro de intentos de login (limita fuerza bruta)
--   b) usuarios.ultimo_acceso — no existía ninguna columna de
--      actividad, lo que hacía imposible detectar estudiantes
--      rezagados sin recorrer entregas y progreso a mano.
--   c) Índices para los filtros por estado que faltaban.
--
-- Recordatorio: usuarios.id es INT UNSIGNED. Toda FK hacia él debe
-- declararse UNSIGNED (ver el fallo errno 150 de las migraciones
-- 004 y 005).
-- ================================================================

-- ── a) Intentos de login ─────────────────────────────────────────
-- Se registra por identificador y por IP. No hay FK a usuarios a
-- propósito: también interesa registrar intentos contra cuentas que
-- no existen, que es justo la señal de un ataque por enumeración.
CREATE TABLE IF NOT EXISTS login_intentos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificador VARCHAR(120) NOT NULL,   -- email o codigo_acceso probado
    ip           VARBINARY(16) NULL,       -- inet6_aton(), NULL si no se pudo leer
    exito        TINYINT(1)   NOT NULL DEFAULT 0,
    intentado_en TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_li_ident (identificador, intentado_en),
    INDEX idx_li_ip    (ip, intentado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── b) Rastro de actividad ───────────────────────────────────────
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS ultimo_acceso TIMESTAMP NULL DEFAULT NULL AFTER activo;

-- Permite listar por colegio quién lleva días sin entrar.
CREATE INDEX IF NOT EXISTS idx_usr_rol_acceso
    ON usuarios (rol, ultimo_acceso);

-- Semilla: sin esto todos los usuarios existentes aparecerían como
-- rezagados el primer día. Se les da su fecha de alta como punto de
-- partida.
UPDATE usuarios
   SET ultimo_acceso = created_at
 WHERE ultimo_acceso IS NULL;

-- ── c) Índices de los filtros por estado ─────────────────────────
-- La serie de actividad de los últimos 7 días filtra por completado
-- y ordena por fecha (api/stats.php).
CREATE INDEX IF NOT EXISTS idx_prog_completado_fecha
    ON progreso_estudiante (completado, completado_en);

-- El calendario de sesiones filtra por fecha futura, y la PK
-- compuesta (aula_id, modulo_id) no sirve para ese WHERE.
CREATE INDEX IF NOT EXISTS idx_am_planificada
    ON aula_modulos (fecha_planificada);

-- Listado de entregables de un estudiante ordenado por fecha.
CREATE INDEX IF NOT EXISTS idx_ent_subido
    ON entregables (subido_en);
