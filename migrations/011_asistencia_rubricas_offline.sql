-- ================================================================
-- INNOVA-STEAM Migration 011
--
--   a) Arreglos de esquema para escrituras que fallaban siempre
--   b) Rúbricas de calificación
--   c) Secundaria (ciclo VI y VII)
--   d) Idempotencia para la cola de envíos sin conexión
--   e) Tope propio del asistente para docentes
--   f) Varias historias en StellarScribe
-- ================================================================

SET NAMES utf8mb4;

-- ── a) quiz_respuestas ───────────────────────────────────────────
-- api/quiz.php insertaba modulo_id, que no existía: cada respuesta de
-- quiz moría con un 500. La columna se añade porque el código la
-- necesita y porque sin ella hay que cruzar dos tablas para saber de
-- qué módulo es una respuesta.
ALTER TABLE quiz_respuestas
    ADD COLUMN IF NOT EXISTS modulo_id INT UNSIGNED NULL AFTER estudiante_id;

-- Rellena las filas que ya existían, subiendo por paso → módulo.
UPDATE quiz_respuestas r
  JOIN quiz_preguntas q ON q.id = r.pregunta_id
  JOIN modulo_pasos   p ON p.id = q.paso_id
   SET r.modulo_id = p.modulo_id
 WHERE r.modulo_id IS NULL;

-- El INSERT usa ON DUPLICATE KEY para permitir reintentar un quiz.
-- Sin esta clave no había duplicado que detectar y cada reintento
-- habría dejado una fila más. También es lo que hace idempotente el
-- reenvío desde la cola sin conexión.
CREATE UNIQUE INDEX IF NOT EXISTS uk_resp_est_pregunta
    ON quiz_respuestas (estudiante_id, pregunta_id);

-- ── f) StellarScribe con varias historias ────────────────────────
-- El progreso se guardaba por capítulo sin decir de qué historia.
-- Con dos historias, el capítulo 1 de una pisaría al de la otra.
ALTER TABLE capitulos_progreso
    ADD COLUMN IF NOT EXISTS historia_slug VARCHAR(40) NOT NULL DEFAULT 'aldrin' AFTER usuario_id;

-- La clave única pasa a incluir la historia. El orden importa: uk_cap
-- empieza por usuario_id y es la que sostiene la clave foránea, así que
-- hay que crear la nueva ANTES de borrar la vieja o MariaDB rechaza el
-- DROP con "needed in a foreign key constraint".
CREATE UNIQUE INDEX IF NOT EXISTS uk_cap_historia
    ON capitulos_progreso (usuario_id, historia_slug, capitulo_slug);

-- Se borra la anterior solo si sigue estando, para poder repetir la
-- migración sin que falle.
SET @hay_uk = (SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'capitulos_progreso'
                  AND INDEX_NAME = 'uk_cap');
SET @sql = IF(@hay_uk > 0, 'ALTER TABLE capitulos_progreso DROP INDEX uk_cap', 'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── c) Secundaria ────────────────────────────────────────────────
-- Los 23 módulos del catálogo son de ciclo V (5.º y 6.º de primaria).
-- El ENUM no admitía otra cosa, así que no había forma de cargar
-- contenido de secundaria aunque se escribiera.
ALTER TABLE modulos
    MODIFY COLUMN grado_ciclo
        ENUM('ciclo_v','ciclo_vi','ciclo_vii','ambos') NOT NULL DEFAULT 'ciclo_v';

-- ── c bis) Grado y nivel de las aulas ────────────────────────────
-- aulas.grado era ENUM('5to','6to') y el formulario insertaba un entero
-- validado entre 1 y 6: la conversión nunca casaba y crear un aula
-- fallaba con CUALQUIER valor. El seed metía '1ro Secundaria', que
-- tampoco está en el ENUM, así que las cinco aulas de demostración
-- quedaron con el grado vacío.
--
-- Al entrar secundaria hace falta además distinguir 5.º de primaria de
-- 5.º de secundaria, que antes no se podían representar a la vez.
ALTER TABLE aulas
    ADD COLUMN IF NOT EXISTS nivel ENUM('primaria','secundaria')
        NOT NULL DEFAULT 'primaria' AFTER colegio_id;

ALTER TABLE aulas
    MODIFY COLUMN grado ENUM('1ro','2do','3ro','4to','5to','6to') NOT NULL DEFAULT '5to';

-- Las aulas que quedaron con el grado vacío por el fallo del ENUM: en
-- el seed todas eran de secundaria y decían el grado en el texto.
UPDATE aulas SET grado = '5to' WHERE grado = '' OR grado IS NULL;

-- ── b) Rúbricas ──────────────────────────────────────────────────
-- Hasta ahora el docente ponía un número suelto de 1 a 3 en el
-- entregable. Con criterios, dos docentes califican lo mismo con la
-- misma vara y el estudiante ve en qué falló.
CREATE TABLE IF NOT EXISTS rubrica_criterios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    modulo_id   INT UNSIGNED NOT NULL,
    orden       TINYINT      NOT NULL DEFAULT 1,
    nombre      VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NULL,
    -- Puntaje máximo del criterio. Con 4 criterios de 3 puntos, el
    -- entregable vale 12; el porcentaje se calcula sobre esa suma.
    puntos_max  TINYINT UNSIGNED NOT NULL DEFAULT 3,
    UNIQUE KEY uk_crit_modulo_orden (modulo_id, orden),
    CONSTRAINT fk_crit_modulo FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entregable_criterios (
    entregable_id INT UNSIGNED NOT NULL,
    criterio_id   INT UNSIGNED NOT NULL,
    puntos        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    comentario    VARCHAR(255) NULL,
    PRIMARY KEY (entregable_id, criterio_id),
    CONSTRAINT fk_ec_entregable FOREIGN KEY (entregable_id) REFERENCES entregables(id) ON DELETE CASCADE,
    CONSTRAINT fk_ec_criterio   FOREIGN KEY (criterio_id)   REFERENCES rubrica_criterios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── d) Idempotencia de la cola sin conexión ──────────────────────
-- El navegador reintenta un envío encolado sin saber si el primero
-- llegó. Con la clave del cliente, el segundo intento no duplica.
ALTER TABLE entregables
    ADD COLUMN IF NOT EXISTS cliente_uuid CHAR(36) NULL AFTER id;

CREATE UNIQUE INDEX IF NOT EXISTS uk_ent_cliente
    ON entregables (cliente_uuid);

-- ── e) Asistente para docentes ───────────────────────────────────
-- Tope aparte del de estudiantes: son menos personas pero cada
-- consulta de planificación es más larga y cara.
ALTER TABLE colegios
    ADD COLUMN IF NOT EXISTS chatbot_tope_dia_docente SMALLINT UNSIGNED NOT NULL DEFAULT 15;

-- ── Índice para las consultas de asistencia ──────────────────────
-- El docente pide la asistencia de su aula por rango de fechas, que
-- viven en sesiones.
CREATE INDEX IF NOT EXISTS idx_ses_aula_fecha
    ON sesiones (aula_id, fecha_sesion);
