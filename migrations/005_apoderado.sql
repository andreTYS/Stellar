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

-- ── usuarios.rol debe aceptar 'apoderado' ────────────────────────
-- Esto NO es opcional: en schema.sql la columna es un ENUM, no un
-- VARCHAR. Sin este ALTER, insertar un apoderado guarda '' en modo
-- permisivo (o falla en modo estricto), y el usuario resultante no
-- puede iniciar sesión en ningún panel. El rol entero queda muerto.
ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM(
        'admin',
        'admin_colegio',
        'docente',
        'practicante',
        'estudiante',
        'apoderado'
    ) NOT NULL;
