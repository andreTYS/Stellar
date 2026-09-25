-- ================================================================
-- INNOVA-STEAM Migration 017 — La paleta de los cursos
--
-- Los seis colores venían del arranque del proyecto: amarillo #f5c842,
-- azul #4a9eff, cian #22d3ee... todos muy saturados y de familias
-- distintas. Puestos en fila en "Mis Cursos" no parecían seis partes
-- de lo mismo, sino seis pegatinas.
--
-- Se pasan a los seis tonos de la paleta extendida de stellar.css, que
-- es la misma que usan las etiquetas y los gráficos. Así el color de
-- Matemática en la tarjeta es el mismo que en el reporte del docente.
--
-- Ninguno es verde: el verde significa "completado" en toda la
-- plataforma y un curso verde al 0% se leería como terminado.
-- ================================================================

-- Sin esto el cliente manda latin1 y ningún WHERE con tilde encuentra
-- nada: 'Matemática' no casa con 'Matemática' y el UPDATE dice "0 rows"
-- sin error ninguno.
SET NAMES utf8mb4;

UPDATE cursos SET color_hex = '#C9942F' WHERE nombre = 'Matemática';   -- oro
UPDATE cursos SET color_hex = '#3C82B4' WHERE nombre = 'Comunicación'; -- azul
UPDATE cursos SET color_hex = '#C4643F' WHERE nombre = 'Arte';         -- coral
UPDATE cursos SET color_hex = '#7C6BB0' WHERE nombre = 'Ingeniería';   -- morado
UPDATE cursos SET color_hex = '#A8516B' WHERE nombre = 'Inglés';       -- rosa
UPDATE cursos SET color_hex = '#23697E' WHERE nombre = 'Ciencia';      -- teal
