-- ================================================================
-- INNOVA-STEAM Migration 015 — Biblioteca de ciencia
--
-- StellarScribe tenía dos historias y dos simuladores: se leía una vez
-- y ya. Le falta lo que hace útil a un sitio como el Space Place de la
-- NASA: artículos cortos que responden UNA pregunta, con un dibujo que
-- explique de verdad y algo que el estudiante pueda hacer en casa.
--
-- Cada artículo es autónomo. No hay que leerlos en orden ni haber
-- terminado nada antes: se entra por curiosidad, que es como funciona.
-- ================================================================

SET NAMES utf8mb4;

-- ── Temas ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciencia_temas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(40)  NOT NULL UNIQUE,
    nombre      VARCHAR(80)  NOT NULL,
    descripcion VARCHAR(255) NULL,
    -- Nombre del icono de Lucide, igual que en cursos.icono.
    icono       VARCHAR(40)  NOT NULL DEFAULT 'sparkles',
    color_hex   VARCHAR(10)  NOT NULL DEFAULT '#4361ee',
    orden       TINYINT      NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Artículos ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ciencia_articulos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tema_id         INT UNSIGNED NOT NULL,
    slug            VARCHAR(80)  NOT NULL UNIQUE,

    -- El título ES la pregunta. Es lo que hace que se entre a leer.
    pregunta        VARCHAR(160) NOT NULL,

    -- La respuesta en una frase, antes de cualquier explicación. Quien
    -- solo lee esto ya se lleva algo cierto.
    respuesta_corta VARCHAR(400) NOT NULL,

    -- Párrafos cortos, como lista JSON.
    cuerpo          JSON         NOT NULL,

    -- Slug del diagrama que dibuja stellarscribe/diagramas.php. El
    -- dibujo no es decoración: en varios artículos es la explicación.
    diagrama        VARCHAR(40)  NULL,

    dato_curioso    VARCHAR(400) NULL,

    -- {titulo, materiales:[], pasos:[]} — para hacerlo en casa o en
    -- clase con lo que hay.
    actividad       JSON         NULL,

    -- A quién va dirigido, para poder filtrar sin inventar un sistema
    -- de niveles nuevo.
    nivel           ENUM('primaria','secundaria','ambos') NOT NULL DEFAULT 'ambos',

    minutos_lectura TINYINT UNSIGNED NOT NULL DEFAULT 3,
    orden           TINYINT      NOT NULL DEFAULT 1,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,

    KEY idx_art_tema (tema_id, orden),
    CONSTRAINT fk_art_tema FOREIGN KEY (tema_id) REFERENCES ciencia_temas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Qué ha leído cada estudiante ─────────────────────────────────
-- Sirve para marcar lo leído en la biblioteca y para los logros. No
-- se califica: leer por curiosidad no debería dar nota.
CREATE TABLE IF NOT EXISTS ciencia_leidos (
    usuario_id  INT UNSIGNED NOT NULL,
    articulo_id INT UNSIGNED NOT NULL,
    leido_en    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, articulo_id),
    KEY idx_leidos_articulo (articulo_id),
    CONSTRAINT fk_leido_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)          ON DELETE CASCADE,
    CONSTRAINT fk_leido_articulo FOREIGN KEY (articulo_id) REFERENCES ciencia_articulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Logros de lectura ────────────────────────────────────────────
-- El ENUM de tipos no contemplaba la biblioteca.
ALTER TABLE logros
    MODIFY COLUMN tipo ENUM('historia','simulador','modulo','ciencia','especial')
        NULL DEFAULT 'historia';

INSERT IGNORE INTO logros (slug, nombre, descripcion, icono, tipo) VALUES
('ciencia-curioso',  'Curioso',            'Leíste 3 artículos de la biblioteca de ciencia',  '🔍', 'ciencia'),
('ciencia-lector',   'Lector de estrellas','Leíste 10 artículos de la biblioteca de ciencia', '📚', 'ciencia'),
('ciencia-completo', 'Mente inquieta',     'Leíste toda la biblioteca de ciencia',            '🧠', 'ciencia');

-- ── Temas ────────────────────────────────────────────────────────
INSERT IGNORE INTO ciencia_temas (slug, nombre, descripcion, icono, color_hex, orden) VALUES
('sol',      'El Sol',            'La estrella que nos alumbra, nos calienta y a veces nos apaga la radio.', 'sun',        '#f5a623', 1),
('tierra',   'La Tierra',         'El planeta donde vivimos, visto desde la ciencia y desde Moquegua.',      'globe-2',    '#3ecf8e', 2),
('cielo',    'El cielo de noche', 'Lo que se ve desde aquí cuando se apagan las luces.',                     'moon-star',  '#7c8cf8', 3),
('sistema',  'El sistema solar',  'Nuestros vecinos: planetas, lunas, cometas y lo que hay más allá.',       'orbit',      '#a78bfa', 4),
('cotidiana','Ciencia de todos los días', 'Por qué pasan cosas que ves todos los días y nunca preguntaste.', 'flask-conical', '#2f8fa8', 5);
