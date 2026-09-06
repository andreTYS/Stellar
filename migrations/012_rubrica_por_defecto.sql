-- ================================================================
-- INNOVA-STEAM Migration 012 — Rúbrica por defecto
--
-- Cuatro criterios para cada módulo del catálogo, de 3 puntos cada uno
-- (12 en total). El docente puede cambiarlos módulo a módulo desde el
-- editor; esto es solo para que la rúbrica sirva desde el primer día en
-- vez de obligar a escribir 23 rúbricas antes de calificar nada.
--
-- Los criterios están redactados para trabajos STEAM de escolares: lo
-- que se evalúa es el razonamiento y la evidencia, no la pulcritud.
-- ================================================================

SET NAMES utf8mb4;

-- INSERT ... SELECT sobre modulos: cada módulo recibe los cuatro. La
-- clave única (modulo_id, orden) hace que repetir la migración no
-- duplique nada, y que un módulo con rúbrica propia no se pise.
INSERT IGNORE INTO rubrica_criterios (modulo_id, orden, nombre, descripcion, puntos_max)
SELECT m.id, 1, 'Responde a la consigna',
       'El trabajo hace lo que pedía el entregable, completo y sin partes en blanco.', 3
  FROM modulos m;

INSERT IGNORE INTO rubrica_criterios (modulo_id, orden, nombre, descripcion, puntos_max)
SELECT m.id, 2, 'Explica el procedimiento',
       'Se entiende cómo llegó al resultado, no solo cuál es el resultado.', 3
  FROM modulos m;

INSERT IGNORE INTO rubrica_criterios (modulo_id, orden, nombre, descripcion, puntos_max)
SELECT m.id, 3, 'Usa datos o evidencia',
       'Apoya lo que afirma en lo que midió, observó o registró.', 3
  FROM modulos m;

INSERT IGNORE INTO rubrica_criterios (modulo_id, orden, nombre, descripcion, puntos_max)
SELECT m.id, 4, 'Se puede leer y seguir',
       'Orden, letra legible y rótulos donde hacen falta.', 3
  FROM modulos m;
