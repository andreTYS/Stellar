-- ================================================================
-- INNOVA-STEAM Migration 018 — Días en que cada quien entró
--
-- La tabla de logros promete "Constancia Sideral: 7 días seguidos
-- usando la plataforma" desde el principio, y no había forma de
-- concederlo: usuarios.ultimo_acceso guarda solo la última vez, así
-- que con esa columna no se distingue entre siete días seguidos y el
-- mismo día siete veces.
--
-- Una fila por usuario y día. La clave primaria compuesta hace que el
-- INSERT IGNORE del segundo acceso del día no escriba nada, así que se
-- puede llamar en cada inicio de sesión sin pensarlo.
-- ================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS dias_activos (
    usuario_id INT UNSIGNED NOT NULL,
    dia        DATE         NOT NULL,
    PRIMARY KEY (usuario_id, dia),
    CONSTRAINT fk_dias_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- El último acceso que ya conocemos cuenta como un día activo: sin
-- esto, a quien lleve meses entrando la racha le empezaría de cero.
INSERT IGNORE INTO dias_activos (usuario_id, dia)
SELECT id, DATE(ultimo_acceso) FROM usuarios WHERE ultimo_acceso IS NOT NULL;
