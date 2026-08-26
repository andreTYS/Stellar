-- ================================================================
-- INNOVA-STEAM Migration 006
--   a) Código de verificación en certificados
--   b) Índices que faltaban para las consultas de dashboard
--
-- Nota sobre tipos: usuarios.id es INT UNSIGNED. Toda columna que lo
-- referencie debe declararse UNSIGNED o MySQL rechaza la FK con
-- errno 150 (fue el fallo de las migraciones 004 y 005).
-- ================================================================

-- ── a) Verificación pública de certificados ──────────────────────
-- Permite comprobar la autenticidad de un certificado sin iniciar
-- sesión. Se rellena de forma perezosa al emitir o al abrir el PDF.
ALTER TABLE certificados
    ADD COLUMN IF NOT EXISTS codigo_verificacion VARCHAR(20) NULL AFTER modulo_id;

-- Índice único parcialmente poblado: los NULL no colisionan entre sí,
-- así que los certificados antiguos conviven hasta que se les asigna
-- un código.
CREATE UNIQUE INDEX IF NOT EXISTS uk_cert_codigo
    ON certificados (codigo_verificacion);

-- ── b) Índices para el dashboard ─────────────────────────────────
-- Las claves foráneas ya cubren los JOIN. Lo que falta son los
-- filtros por estado, que hoy obligan a recorrer todas las filas del
-- estudiante para contar cuántos módulos completó.
CREATE INDEX IF NOT EXISTS idx_prog_est_completado
    ON progreso_estudiante (estudiante_id, completado);

-- El docente lista entregables sin calificar de un módulo.
CREATE INDEX IF NOT EXISTS idx_ent_modulo_calif
    ON entregables (modulo_id, calificacion);

-- Detección de rezago: entregas recientes por estudiante.
CREATE INDEX IF NOT EXISTS idx_ent_est_fecha
    ON entregables (estudiante_id, subido_en);
