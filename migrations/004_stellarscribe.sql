-- ================================================================
-- INNOVA-STEAM Migration 004 — StellarScribe tracking layer
-- Run this after 001_notificaciones_mensajes.sql
-- ================================================================

-- Chapter-level progress (one row per student per chapter)
CREATE TABLE IF NOT EXISTS capitulos_progreso (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT UNSIGNED NOT NULL,
    capitulo_slug  VARCHAR(50)  NOT NULL,          -- 'cap1','cap2','cap3','cap4'
    iniciado_en    DATETIME     DEFAULT (NOW()),
    completado_en  DATETIME     NULL,
    tiempo_seg     INT          DEFAULT 0,          -- seconds spent reading
    puntos_quiz    TINYINT      DEFAULT 0,          -- 0-100
    intentos_quiz  TINYINT      DEFAULT 0,
    completado     TINYINT(1)   DEFAULT 0,
    UNIQUE KEY uk_cap (usuario_id, capitulo_slug),
    CONSTRAINT fk_cp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Simulator usage sessions
CREATE TABLE IF NOT EXISTS simulador_sesiones (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT UNSIGNED NOT NULL,
    simulador     VARCHAR(60) NOT NULL,             -- 'sistema-solar','clima-espacial'
    iniciado_en   DATETIME    DEFAULT (NOW()),
    duracion_seg  INT         DEFAULT 0,
    completado    TINYINT(1)  DEFAULT 0,
    CONSTRAINT fk_ss_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_ss_usuario (usuario_id),
    INDEX idx_ss_sim (simulador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Achievement catalogue
CREATE TABLE IF NOT EXISTS logros (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(60)  NOT NULL UNIQUE,
    nombre      VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255),
    icono       VARCHAR(10),                        -- emoji
    tipo        ENUM('historia','simulador','modulo','especial') DEFAULT 'historia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Achievements earned by users
CREATE TABLE IF NOT EXISTS usuario_logros (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    logro_id    INT      NOT NULL,
    obtenido_en DATETIME DEFAULT (NOW()),
    UNIQUE KEY uk_ul (usuario_id, logro_id),
    CONSTRAINT fk_ul_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_ul_logro   FOREIGN KEY (logro_id)   REFERENCES logros(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed achievements ─────────────────────────────────────────
INSERT IGNORE INTO logros (slug, nombre, descripcion, icono, tipo) VALUES
('primer-capitulo',     'Explorador Estelar',      'Completaste el primer capítulo de StellarScribe', '🌟', 'historia'),
('todos-capitulos',     'Guardián del Cosmos',     'Completaste los 4 capítulos de la historia',      '🚀', 'historia'),
('quiz-perfecto',       'Mente Científica',        'Obtuviste 100/100 en el quiz de un capítulo',     '🧠', 'historia'),
('primer-sim',         'Piloto Simulador',         'Usaste un simulador interactivo por primera vez', '🔭', 'simulador'),
('sim-solar-5min',     'Astrónomo Amateur',        'Pasaste 5+ minutos explorando el Sistema Solar',  '☀️', 'simulador'),
('sim-clima',          'Meteorólogo Espacial',     'Completaste el simulador de Clima Espacial',      '🌌', 'simulador'),
('primer-modulo',      'Ingeniero STEAM',          'Completaste tu primer módulo INNOVA-STEAM',       '📚', 'modulo'),
('todos-modulos',      'Maestro STEAM',            'Completaste todos los módulos disponibles',       '🏆', 'modulo'),
('madrugador',         'Observador Nocturno',      'Iniciaste sesión antes de las 6am',              '🌙', 'especial'),
('racha-7',            'Constancia Sideral',       '7 días seguidos usando la plataforma',            '⚡', 'especial');
