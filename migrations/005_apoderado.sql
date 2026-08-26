-- ================================================================
-- INNOVA-STEAM Migration 005 — Rol Apoderado (parent/guardian)
-- ================================================================

-- Linking table: one apoderado can have multiple students
CREATE TABLE IF NOT EXISTS apoderado_estudiante (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    apoderado_id   INT UNSIGNED NOT NULL,
    estudiante_id  INT UNSIGNED NOT NULL,
    relacion       VARCHAR(60) DEFAULT 'apoderado',   -- padre, madre, tutor, etc.
    activo         TINYINT(1) DEFAULT 1,
    creado_en      DATETIME DEFAULT (NOW()),
    UNIQUE KEY uk_ap_est (apoderado_id, estudiante_id),
    CONSTRAINT fk_ae_apod  FOREIGN KEY (apoderado_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_ae_est   FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure 'apoderado' is a valid rol value
-- (usuarios.rol is VARCHAR so no ALTER needed on most setups)
