-- =============================================================================
-- INNOVA-STEAM Educational Platform
-- Database Schema + Seed Data
-- Project: Primary Schools, Moquegua, Peru
-- Generated: 2026-04-08
-- =============================================================================
-- NOTE: The password_hash below is bcrypt for 'password' (Laravel default test hash).
-- To generate the correct hash for '1234', run in PHP:
--   echo password_hash('1234', PASSWORD_BCRYPT);
-- Then replace all occurrences of the hash below.
-- =============================================================================

-- ---------------------------------------------------------------------------
-- 1. DATABASE CREATION
-- ---------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS innovasteam
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE innovasteam;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- 2. DROP TABLES (reverse FK order)
-- ---------------------------------------------------------------------------
-- Tablas que añaden las migraciones 001/004/005/008. Van primero porque
-- todas apuntan a usuarios: sin borrarlas, reejecutar este archivo sobre
-- una base ya migrada falla con error 1451 al llegar a DROP usuarios, y
-- se queda a medias.
DROP TABLE IF EXISTS api_tokens;
DROP TABLE IF EXISTS login_intentos;
DROP TABLE IF EXISTS apoderado_estudiante;
DROP TABLE IF EXISTS usuario_logros;
DROP TABLE IF EXISTS logros;
DROP TABLE IF EXISTS simulador_sesiones;
DROP TABLE IF EXISTS capitulos_progreso;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS mensajes;

DROP TABLE IF EXISTS asistencia;
DROP TABLE IF EXISTS sesiones;
DROP TABLE IF EXISTS certificados;
DROP TABLE IF EXISTS entregables;
DROP TABLE IF EXISTS quiz_respuestas;
DROP TABLE IF EXISTS progreso_estudiante;
DROP TABLE IF EXISTS aula_modulos;
DROP TABLE IF EXISTS quiz_preguntas;
DROP TABLE IF EXISTS modulo_pasos;
DROP TABLE IF EXISTS modulos;
DROP TABLE IF EXISTS cursos;
DROP TABLE IF EXISTS estudiante_aula;
DROP TABLE IF EXISTS practicante_aula;
DROP TABLE IF EXISTS aulas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS colegios;

-- ---------------------------------------------------------------------------
-- 3. CREATE TABLES
-- ---------------------------------------------------------------------------

-- colegios
CREATE TABLE colegios (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(200)  NOT NULL,
  distrito    VARCHAR(100)  NOT NULL,
  ugel_codigo VARCHAR(30)   NOT NULL,
  director    VARCHAR(150),
  telefono    VARCHAR(30),
  activo      TINYINT(1)    NOT NULL DEFAULT 1,
  created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- usuarios
CREATE TABLE usuarios (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(100) NOT NULL,
  apellido        VARCHAR(100) NOT NULL,
  email           VARCHAR(180) UNIQUE,
  codigo_acceso   VARCHAR(30)  UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  rol             ENUM('admin','admin_colegio','docente','practicante','estudiante') NOT NULL,
  colegio_id      INT UNSIGNED,
  universidad     VARCHAR(200),
  telefono_tutor  VARCHAR(30),
  activo          TINYINT(1)   NOT NULL DEFAULT 1,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_colegio FOREIGN KEY (colegio_id) REFERENCES colegios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- aulas
CREATE TABLE aulas (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  colegio_id    INT UNSIGNED NOT NULL,
  grado         ENUM('5to','6to') NOT NULL,
  seccion       VARCHAR(10)  NOT NULL,
  anio_escolar  YEAR         NOT NULL,
  docente_id    INT UNSIGNED,
  CONSTRAINT fk_aulas_colegio  FOREIGN KEY (colegio_id) REFERENCES colegios(id)  ON DELETE CASCADE,
  CONSTRAINT fk_aulas_docente  FOREIGN KEY (docente_id) REFERENCES usuarios(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- practicante_aula
CREATE TABLE practicante_aula (
  practicante_id INT UNSIGNED NOT NULL,
  aula_id        INT UNSIGNED NOT NULL,
  asignado_en    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (practicante_id, aula_id),
  CONSTRAINT fk_pa_practicante FOREIGN KEY (practicante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_pa_aula        FOREIGN KEY (aula_id)        REFERENCES aulas(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- estudiante_aula
CREATE TABLE estudiante_aula (
  estudiante_id INT UNSIGNED NOT NULL,
  aula_id       INT UNSIGNED NOT NULL,
  inscrito_en   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (estudiante_id, aula_id),
  CONSTRAINT fk_ea_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_ea_aula       FOREIGN KEY (aula_id)       REFERENCES aulas(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- cursos
CREATE TABLE cursos (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre              VARCHAR(100) NOT NULL,
  slug                VARCHAR(80)  NOT NULL UNIQUE,
  color_hex           VARCHAR(10),
  icono               VARCHAR(30),
  descripcion         TEXT,
  competencias_cneb   TEXT,
  activo              TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- modulos
CREATE TABLE modulos (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  curso_id            INT UNSIGNED NOT NULL,
  titulo              VARCHAR(200) NOT NULL,
  descripcion         TEXT,
  grado_ciclo         ENUM('ciclo_v','ambos') NOT NULL DEFAULT 'ciclo_v',
  minutos_estimados   INT          NOT NULL DEFAULT 45,
  orden               INT          NOT NULL DEFAULT 0,
  activo              TINYINT(1)   NOT NULL DEFAULT 1,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_modulos_curso FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- modulo_pasos
CREATE TABLE modulo_pasos (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  modulo_id    INT UNSIGNED NOT NULL,
  numero_paso  TINYINT      NOT NULL,
  tipo         ENUM('historia','actividad','quiz','entregable') NOT NULL,
  contenido    JSON         NOT NULL,
  UNIQUE KEY uk_modulo_paso (modulo_id, numero_paso),
  CONSTRAINT fk_pasos_modulo FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- quiz_preguntas
CREATE TABLE quiz_preguntas (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paso_id  INT UNSIGNED NOT NULL,
  texto    TEXT         NOT NULL,
  opciones JSON         NOT NULL,
  orden    INT          NOT NULL DEFAULT 0,
  CONSTRAINT fk_quiz_paso FOREIGN KEY (paso_id) REFERENCES modulo_pasos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- aula_modulos
CREATE TABLE aula_modulos (
  aula_id           INT UNSIGNED NOT NULL,
  modulo_id         INT UNSIGNED NOT NULL,
  fecha_planificada DATE,
  asignado_por      INT UNSIGNED,
  PRIMARY KEY (aula_id, modulo_id),
  CONSTRAINT fk_am_aula         FOREIGN KEY (aula_id)      REFERENCES aulas(id)    ON DELETE CASCADE,
  CONSTRAINT fk_am_modulo       FOREIGN KEY (modulo_id)    REFERENCES modulos(id)  ON DELETE CASCADE,
  CONSTRAINT fk_am_asignado_por FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- progreso_estudiante
CREATE TABLE progreso_estudiante (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estudiante_id   INT UNSIGNED NOT NULL,
  modulo_id       INT UNSIGNED NOT NULL,
  paso_actual     TINYINT      NOT NULL DEFAULT 1,
  estrellas_quiz  TINYINT      NOT NULL DEFAULT 0,
  intentos_quiz   TINYINT      NOT NULL DEFAULT 0,
  completado      TINYINT(1)   NOT NULL DEFAULT 0,
  completado_en   TIMESTAMP    NULL,
  UNIQUE KEY uk_progreso (estudiante_id, modulo_id),
  CONSTRAINT fk_prog_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_prog_modulo     FOREIGN KEY (modulo_id)     REFERENCES modulos(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- quiz_respuestas
CREATE TABLE quiz_respuestas (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estudiante_id   INT UNSIGNED NOT NULL,
  pregunta_id     INT UNSIGNED NOT NULL,
  opcion_elegida  INT,
  es_correcta     TINYINT(1),
  respondido_en   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_qr_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios(id)       ON DELETE CASCADE,
  CONSTRAINT fk_qr_pregunta   FOREIGN KEY (pregunta_id)   REFERENCES quiz_preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- entregables
CREATE TABLE entregables (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estudiante_id       INT UNSIGNED NOT NULL,
  modulo_id           INT UNSIGNED NOT NULL,
  formato             ENUM('dibujo_cientifico','mural_digital','cuento_ilustrado','prototipo','ficha','otro') NOT NULL,
  archivo_url         VARCHAR(500),
  comentario_docente  TEXT,
  comentado_por       INT UNSIGNED NULL,
  calificacion        TINYINT,
  subido_en           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ent_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_ent_modulo     FOREIGN KEY (modulo_id)     REFERENCES modulos(id)  ON DELETE CASCADE,
  CONSTRAINT fk_ent_comentado  FOREIGN KEY (comentado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sesiones
CREATE TABLE sesiones (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  practicante_id   INT UNSIGNED NOT NULL,
  aula_id          INT UNSIGNED NOT NULL,
  modulo_id        INT UNSIGNED NOT NULL,
  fecha_sesion     DATE         NOT NULL,
  asistentes       INT          NOT NULL DEFAULT 0,
  notas            TEXT,
  fotos_actividad  JSON,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ses_practicante FOREIGN KEY (practicante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_ses_aula        FOREIGN KEY (aula_id)        REFERENCES aulas(id)    ON DELETE CASCADE,
  CONSTRAINT fk_ses_modulo      FOREIGN KEY (modulo_id)      REFERENCES modulos(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- asistencia
CREATE TABLE asistencia (
  sesion_id     INT UNSIGNED NOT NULL,
  estudiante_id INT UNSIGNED NOT NULL,
  presente      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (sesion_id, estudiante_id),
  CONSTRAINT fk_asis_sesion     FOREIGN KEY (sesion_id)     REFERENCES sesiones(id) ON DELETE CASCADE,
  CONSTRAINT fk_asis_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- certificados
CREATE TABLE certificados (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estudiante_id INT UNSIGNED NOT NULL,
  modulo_id     INT UNSIGNED NOT NULL,
  pdf_url       VARCHAR(500),
  emitido_en    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cert (estudiante_id, modulo_id),
  CONSTRAINT fk_cert_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_cert_modulo     FOREIGN KEY (modulo_id)     REFERENCES modulos(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
-- 4. SEED DATA
-- ===========================================================================

-- ---------------------------------------------------------------------------
-- colegios
-- ---------------------------------------------------------------------------
INSERT INTO colegios (nombre, distrito, ugel_codigo, director, telefono) VALUES
('GUE Mariscal Nieto', 'Moquegua', 'UGEL-MQ-001', 'Prof. Roberto Quispe Mamani', '053-462100');

-- ---------------------------------------------------------------------------
-- cursos  (id 1-5 in insertion order)
-- ---------------------------------------------------------------------------
INSERT INTO cursos (nombre, slug, color_hex, icono, descripcion) VALUES
('Matemática',   'matematica',   '#f5c842', 'calculator',    'Números, operaciones, estadística y geometría desde el entorno moqueguano'),
('Comunicación', 'comunicacion', '#4a9eff', 'book-open',     'Lectura, escritura y expresión oral conectadas con la cultura local'),
('Arte',         'arte',         '#3ecf8e', 'palette',       'Creación artística, apreciación cultural y expresión visual'),
('Ingeniería',   'ingenieria',   '#a78bfa', 'cog',           'Diseño, construcción y resolución de problemas tecnológicos'),
('Inglés',       'ingles',       '#ff6b6b', 'globe',         'Comunicación básica en inglés conectada con el mundo global');

-- ---------------------------------------------------------------------------
-- usuarios  (password_hash = bcrypt of 'password' — replace with hash of '1234')
-- id: 1=admin, 2=admin_colegio, 3=docente, 4=practicante, 5=estudiante
-- ---------------------------------------------------------------------------
INSERT INTO usuarios (nombre, apellido, email, codigo_acceso, password_hash, rol, colegio_id) VALUES
('Admin',   'Sistema', 'admin@innovasteam.edu.pe',        NULL,      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',         NULL),
('Carlos',  'Vargas',  'admin_col@innovasteam.edu.pe',    NULL,      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin_colegio', 1),
('María',   'Flores',  'docente@innovasteam.edu.pe',      NULL,      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'docente',       1),
('Jorge',   'Quispe',  'practicante@innovasteam.edu.pe',  NULL,      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'practicante',   1),
('Sofía',   'Mamani',  NULL,                              'EST-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante',    1);

-- ---------------------------------------------------------------------------
-- aulas  (id=1)
-- ---------------------------------------------------------------------------
INSERT INTO aulas (colegio_id, grado, seccion, anio_escolar, docente_id) VALUES
(1, '5to', 'A', 2026, 3);

-- ---------------------------------------------------------------------------
-- practicante_aula / estudiante_aula
-- ---------------------------------------------------------------------------
INSERT INTO practicante_aula (practicante_id, aula_id) VALUES (4, 1);
INSERT INTO estudiante_aula  (estudiante_id,  aula_id) VALUES (5, 1);

-- ---------------------------------------------------------------------------
-- modulos  (15 modules, 3 per course; id 1-15 in insertion order)
-- ---------------------------------------------------------------------------
INSERT INTO modulos (curso_id, titulo, descripcion, orden, minutos_estimados) VALUES
-- Matemática (curso_id=1)
(1, 'El mercado de Moquegua',    'Don Aurelio no sabe si su precio es justo. Aprende a registrar y comparar precios con gráficos.',      1, 45),
(1, 'Construyendo terrazas',     'Las terrazas incas usaban fracciones para irrigar. Mide y divide áreas en fracciones.',                2, 45),
(1, 'El aguaymanto y los kilos', 'Productores de Torata calculan su ganancia. Multiplicación con precios reales.',                       3, 45),
-- Comunicación (curso_id=2)
(2, 'El río Moquegua habla',     'Sofía escucha al río contarle su historia. Aprende a escribir cartas narrativas.',                     1, 45),
(2, 'Leyendas de Torata',        'El abuelo Aurelio narra la leyenda del volcán. Reescribe y crea tu propia leyenda.',                   2, 45),
(2, 'Noticias de mi barrio',     'El periódico escolar de 5to A necesita redactores. Aprende a escribir noticias.',                      3, 45),
-- Arte (curso_id=3)
(3, 'Colores de Moquegua',       'El pintor Víctor quiere capturar el desierto. Mezcla colores primarios para crear paletas.',           1, 45),
(3, 'Mural del barrio',          'La junta vecinal pide un mural para la plaza. Diseña y crea arte colectivo.',                          2, 45),
(3, 'Tejidos y patrones',        'Las mamás del mercado tejen patrones andinos. Reproduce y crea patrones geométricos.',                 3, 45),
-- Ingeniería (curso_id=4)
(4, 'Puentes con papel',         'El puente de la comunidad de Elena necesita reparación. Diseña un puente resistente de papel.',        1, 45),
(4, 'Filtro de agua',            'El pozo del pueblo tiene sedimentos. Construye un filtro de agua natural.',                            2, 45),
(4, 'Torre sísmica',             'Moquegua tiene sismos frecuentes. Construye una torre que resista temblores.',                         3, 45),
-- Inglés (curso_id=5)
(5, 'My Moquegua',               'Tim, un turista, llega a Moquegua y no entiende nada. Crea una tarjeta de bienvenida en inglés.',     1, 45),
(5, 'The market',                'Sara visits the Moquegua market. Practica diálogos de compra y venta en inglés.',                     2, 45),
(5, 'Nature around us',          'Los colores de la naturaleza en inglés. Aprende vocabulario de colores y naturaleza bilingüe.',        3, 45);


-- ===========================================================================
-- 5. modulo_pasos — 4 pasos × 15 módulos = 60 filas
-- ===========================================================================

-- ---- Módulo 1: El mercado de Moquegua (modulo_id=1) ----------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(1, 1, 'historia',
 '{"narrativa":"Don Aurelio Condori lleva más de treinta años vendiendo papas, cebollas y limones en el mercado central de Moquegua. Cada mañana llega con su carretilla antes del amanecer, cuando las calles todavía huelen a neblina y tierra mojada. Pero últimamente algo lo preocupa: sus vecinos de puesto parecen vender más que él, aunque ofrecen los mismos productos. ¿Será que sus precios no son los correctos? Un día, su nieta Valeria llega de visita y le pregunta: ¿Abuelo, cuánto cuesta el kilo de papa aquí? Y él responde: Dos soles con cincuenta. Valeria saca su cuaderno y empieza a anotar los precios de todos los puestos del mercado. Al terminar le muestra a Don Aurelio una lista con números y le dice: Abuelo, con un gráfico de barras puedo mostrarte quién vende más barato y quién más caro. Don Aurelio la mira sorprendido. No sabía que los números podían contar esa historia. Juntos descubren que hay tres puestos que venden la papa más barata, y que la cebolla en el puesto de la señora Rosa cuesta el doble que en otros mercados del distrito. Don Aurelio entiende entonces que registrar y comparar precios es una herramienta poderosa para tomar decisiones justas, tanto para los vendedores como para las familias que compran cada semana.","pregunta_disparadora":"¿Cómo podrías ayudar a Don Aurelio a saber si sus precios son justos comparados con los de otros vendedores del mercado?"}'),
(1, 2, 'actividad',
 '{"materiales":["Hoja de papel o cuaderno","Lápiz o lapicero","Regla","Lápices de colores (opcional)"],"instrucciones":["Visita un mercado de tu barrio o imagina uno con al menos 5 productos típicos de Moquegua (papa, cebolla, limón, aguaymanto, queso).","Registra el nombre del producto y su precio por kilo o por unidad en una tabla de dos columnas: Producto | Precio.","Dibuja un gráfico de barras donde el eje horizontal tenga los nombres de los productos y el eje vertical los precios en soles.","Colorea cada barra con un color diferente y ponle título a tu gráfico: Precios en el mercado de Moquegua.","Escribe debajo del gráfico: ¿Cuál es el producto más caro? ¿Cuál es el más barato? ¿Por qué crees que hay esa diferencia?"],"minutos":20}'),
(1, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(1, 4, 'entregable',
 '{"consigna":"Presenta tu tabla de 5 productos con sus precios y el gráfico de barras que dibujaste. Asegúrate de que el gráfico tenga título, etiquetas en los ejes y que cada barra esté claramente diferenciada.","formatos":["dibujo_cientifico","ficha"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ---- Módulo 2: Construyendo terrazas (modulo_id=2) -----------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(2, 1, 'historia',
 '{"narrativa":"En las laderas de los cerros que rodean Moquegua, si uno mira con atención, puede ver rastros de antiguas terrazas construidas por los pueblos que vivieron aquí hace cientos de años. Pedro, un estudiante de quinto grado, va de excursión con su clase al cerro Baúl y queda fascinado con esas plataformas escalonadas de piedra. Su profesora le explica que los antiguos agricultores las llamaban andenes y que las usaban para cultivar en las laderas sin que la tierra se erosionara. Pero lo más sorprendente es lo que viene después: la profesora le muestra que para dividir el agua de irrigación entre los andenes, los ingenieros del pasado usaban fracciones. Si tenían un canal con cierta cantidad de agua, la distribuían en partes iguales entre cada terraza. Pedro no puede creerlo: ¿Las fracciones existen desde antes de que yo naciera? La profesora sonríe y le dice que las matemáticas no se inventaron en los libros, sino que nacieron de la necesidad de resolver problemas reales como regar campos, construir casas y comerciar productos. Esa tarde Pedro llega a su casa y le cuenta todo a su mamá, quien le dice que su bisabuelo también cultivaba en andenes cerca de Torata. Pedro decide dibujar sus propias terrazas y aprender a dividir áreas usando fracciones, como los ingenieros de antes.","pregunta_disparadora":"¿Cómo usarías fracciones para dividir de manera justa el agua de riego entre cuatro terrazas de diferente tamaño?"}'),
(2, 2, 'actividad',
 '{"materiales":["Papel cuadriculado","Lápices de colores","Regla","Lápiz"],"instrucciones":["Dibuja una ladera de cerro con al menos 4 terrazas escalonadas usando papel cuadriculado. Cada terraza debe tener una forma rectangular.","Decide el área total de tu ladera (por ejemplo, 24 cuadraditos) y divídela en fracciones entre las terrazas: 1/4 para la terraza de arriba, 1/4 para la segunda, 1/2 para las dos de abajo.","Colorea cada terraza con un color diferente y escribe la fracción que le corresponde dentro de ella.","Suma todas las fracciones y verifica que den 1 (el total de la ladera).","Escribe al lado de tu dibujo: ¿Qué fracción del total corresponde a las terrazas superiores? ¿Y a las inferiores?"],"minutos":20}'),
(2, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(2, 4, 'entregable',
 '{"consigna":"Entrega tu dibujo de las terrazas con las fracciones marcadas en cada una, la suma verificada y las respuestas a las preguntas de reflexión.","formatos":["dibujo_cientifico","ficha"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ---- Módulo 3: El aguaymanto y los kilos (modulo_id=3) -------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(3, 1, 'historia',
 '{"narrativa":"En las chacras de Torata, el aguaymanto crece entre neblina y sol. Es una fruta pequeña, amarilla y dulce que los agricultores venden en el mercado de Moquegua cada semana. Doña Carmen tiene una de esas chacras y este año tuvo una buena cosecha: logró recoger más bolsas de las que esperaba. Pero cuando fue al mercado a vender, no sabía cuánto dinero iba a ganar en total. Su hijo Erick, que cursa quinto de primaria, le dijo: Mamá, si sabemos cuántos kilos tienes y cuánto vale cada kilo, puedo calcular cuánto ganarás. Doña Carmen lo miró con orgullo y le entregó su libreta de apuntes. Erick abrió la libreta y encontró los datos: 8 kilos de aguaymanto a 4 soles cada kilo, 5 kilos de paprika a 6 soles cada kilo, y 3 kilos de orégano a 7 soles cada kilo. Se sentó bajo la sombra de un árbol y empezó a multiplicar con cuidado. Al terminar, le anunció a su mamá el total que iba a ganar ese día. Doña Carmen no podía creer lo rápido que Erick había resuelto algo que a ella le tomaba mucho más tiempo. Erick entendió ese día que la multiplicación no es solo una operación de la escuela, sino una herramienta real que ayuda a las familias a conocer sus ganancias y tomar decisiones sobre su trabajo.","pregunta_disparadora":"Si Doña Carmen tiene 12 kilos de aguaymanto y cada kilo vale 4.50 soles, ¿cuánto dinero recibirá en total? ¿Cómo lo calcularías?"}'),
(3, 2, 'actividad',
 '{"materiales":["Hoja de papel o cuaderno","Lápiz"],"instrucciones":["Imagina que eres agricultor o agricultora en Torata. Elige 4 productos que cultivas (puedes usar: aguaymanto, papa, orégano, paprika, ají, aceituna).","Para cada producto, anota: cantidad en kilos y precio por kilo. Inventa números reales y razonables.","Multiplica cantidad × precio para cada producto y escribe el resultado en la columna Ganancia.","Suma todas las ganancias para obtener el total del día de mercado.","Responde: ¿Cuál producto te dio más ganancia? ¿Por qué? ¿Qué cambiarías si quisieras ganar más?"],"minutos":20}'),
(3, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(3, 4, 'entregable',
 '{"consigna":"Entrega tu tabla de productos con cantidades, precios y ganancias calculadas, más las respuestas a las preguntas de reflexión.","formatos":["ficha","otro"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ---- Módulo 4: El río Moquegua habla (modulo_id=4) -----------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(4, 1, 'historia',
 '{"narrativa":"Una tarde de otoño, Sofía caminaba sola por las orillas del río Moquegua cuando escuchó algo extraño: un murmullo que no era el viento ni el agua. Se acercó más y de repente le pareció que el río le hablaba. Yo fui limpio, Sofía, le dijo la voz del río. Recuerdo cuando los niños se bañaban en mis aguas y las mujeres lavaban su ropa en mis orillas. Ahora cargó con tristeza porque la gente tira basura en mis orillas y las fábricas me llenan de colores que no son míos. Sofía se sentó en una piedra y escuchó con atención. El río le contó que venía desde las alturas de Puno, que cruzaba valles de olivos y viñas, y que llegaba al mar después de un largo viaje. Le habló de los pájaros que ya no se posaban en sus orillas, de los peces que habían desaparecido y de las familias que antes dependían de su agua para regar sus cultivos. Sofía sacó su cuaderno y empezó a escribir todo lo que el río le contaba. Al llegar a casa pensó: ¿Qué pasaría si el río pudiera escribirle una carta a la comunidad? ¿Qué les diría? ¿Qué les pediría? Esa noche Sofía aprendió que escribir una carta es una forma de dar voz a quien no puede hablar.","pregunta_disparadora":"Si el río Moquegua pudiera escribirte una carta, ¿qué crees que te diría? ¿Qué problemas contaría y qué te pediría que hicieras?"}'),
(4, 2, 'actividad',
 '{"materiales":["Papel o cuaderno","Lápiz o lapicero"],"instrucciones":["Escribe una carta del río Moquegua dirigida a los habitantes de la ciudad. La carta debe tener: saludo inicial, al menos dos párrafos donde el río cuente su situación y sus recuerdos, una petición clara a la comunidad, y una despedida.","Usa la primera persona (Yo, el río, les escribo...) para que la carta suene como si el río hablara de verdad.","Incluye al menos 3 detalles específicos sobre Moquegua: lugares, plantas, animales o tradiciones relacionadas con el río.","Revisa la ortografía y la puntuación antes de entregar.","Dale un título creativo a tu carta."],"minutos":20}'),
(4, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(4, 4, 'entregable',
 '{"consigna":"Entrega tu carta escrita del río Moquegua a la comunidad. Debe tener todos los elementos de una carta formal: saludo, cuerpo con al menos dos párrafos, petición y despedida.","formatos":["cuento_ilustrado","ficha"],"instrucciones":"Sube una foto de tu carta manuscrita terminada. Máx 5 MB."}');

-- ---- Módulo 5: Leyendas de Torata (modulo_id=5) --------------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(5, 1, 'historia',
 '{"narrativa":"El abuelo Aurelio tiene ochenta y dos años y una voz como el trueno de verano. Cada vez que su nieto Miguel lo visita en su casa de Torata, le pide que cuente la leyenda del volcán. El abuelo se acomoda en su silla de madera, cierra los ojos un momento y comienza: Dicen los antiguos que hace muchísimos años, cuando Moquegua era todavía muy joven, el volcán Ticsani se enamoró de una pastora llamada Urpicha. Ella cuidaba sus rebaños en las faldas del cerro y cantaba tan lindo que el volcán temblaba de emoción cada vez que la escuchaba. Un día, unos mensajeros del sol le dijeron a Urpicha que debía irse lejos, a vivir entre las nubes. Ella lloró tanto que sus lágrimas formaron el río que hoy riega nuestros campos. Y el volcán, al verla partir, soltó una columna de humo que todavía hoy se puede ver en los días claros. Miguel escucha con la boca abierta. Le pregunta al abuelo si eso es verdad. El abuelo sonríe y dice: Las leyendas no son falsas ni verdaderas, niño. Son la manera en que los pueblos explican el mundo y guardan su historia. Miguel toma su cuaderno y decide que también él quiere contar una leyenda, a su manera.","pregunta_disparadora":"¿Qué elementos hacen que una leyenda sea diferente a un cuento de ficción o a una noticia? ¿Por qué los pueblos crean leyendas para explicar la naturaleza?"}'),
(5, 2, 'actividad',
 '{"materiales":["Cuaderno o papel","Lápiz o lapicero","Lápices de colores (opcional para ilustrar)"],"instrucciones":["Lee nuevamente la leyenda del volcán Ticsani que contó el abuelo Aurelio.","Reescríbela con tus propias palabras en al menos 10 oraciones. Puedes cambiar algunos detalles, pero mantén los personajes principales.","Luego crea tu propia leyenda moqueguana. Elige un elemento de la naturaleza de la región (el cerro Baúl, el desierto, los olivos, el río) y explica su origen con una historia fantástica.","Tu leyenda debe tener: personajes (humanos, naturaleza o seres mágicos), un problema o situación especial, y un final que explique algo del mundo real.","Si quieres, ilustra tu leyenda con un dibujo."],"minutos":20}'),
(5, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(5, 4, 'entregable',
 '{"consigna":"Entrega tu leyenda moqueguana propia, escrita a mano o en computadora. Debe tener título, personajes identificables, un hecho sobrenatural y un final explicativo.","formatos":["cuento_ilustrado","ficha"],"instrucciones":"Sube una foto o archivo de tu leyenda terminada. Máx 5 MB."}');

-- ---- Módulo 6: Noticias de mi barrio (modulo_id=6) -----------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(6, 1, 'historia',
 '{"narrativa":"El aula de 5to A de la GUE Mariscal Nieto tiene un proyecto especial este año: publicar el primer periódico escolar del colegio. La profesora Flores les explica a sus alumnos que las noticias no solo ocurren en Lima o en las grandes ciudades: cada barrio, cada mercado y cada familia tiene historias que merecen ser contadas. Lucía, una estudiante curiosa y observadora, decide que quiere escribir sobre el nuevo parque que acaban de inaugurar en su barrio. Pero cuando intenta redactar la nota, se da cuenta de que no sabe por dónde empezar. ¿Cuándo fue inaugurado? ¿Quién lo construyó? ¿Por qué era necesario? Su compañero Rodrigo le dice que una buena noticia siempre responde seis preguntas: ¿Qué pasó?, ¿Quién lo hizo?, ¿Cuándo?, ¿Dónde?, ¿Cómo? y ¿Por qué? Lucía anota esas preguntas en su cuaderno y regresa al parque con su libreta. Habla con los vecinos, con el trabajador que plantó los árboles y con los niños que ya juegan ahí. Al llegar a clase, tiene toda la información que necesita. La profesora le dice que ahora viene la parte más importante: organizar esa información de manera clara, con un titular llamativo, una introducción y los detalles más importantes al principio. Lucía descubre que escribir noticias es como resolver un rompecabezas de palabras.","pregunta_disparadora":"¿Qué historia importante de tu barrio o colegio merece ser contada en un periódico? ¿Cómo la describirías en pocas palabras para que llame la atención?"}'),
(6, 2, 'actividad',
 '{"materiales":["Cuaderno o papel","Lápiz o lapicero"],"instrucciones":["Elige un hecho real o imaginario que haya ocurrido en tu barrio, colegio o familia recientemente (puede ser una mejora, un evento, un personaje interesante).","Responde las seis preguntas del periodismo: ¿Qué?, ¿Quién?, ¿Cuándo?, ¿Dónde?, ¿Cómo?, ¿Por qué?","Escribe un titular llamativo de no más de 10 palabras que resuma la noticia.","Redacta la nota informativa en al menos tres párrafos: introducción (lo más importante), desarrollo (detalles) y cierre (consecuencias o reflexión).","Revisa que la nota esté en tercera persona y no contenga opiniones personales."],"minutos":20}'),
(6, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(6, 4, 'entregable',
 '{"consigna":"Entrega tu nota periodística completa con titular, respuestas a las seis preguntas y los tres párrafos redactados correctamente.","formatos":["ficha","otro"],"instrucciones":"Sube una foto de tu nota periodística terminada. Máx 5 MB."}');


-- ---- Módulo 7: Colores de Moquegua (modulo_id=7) -------------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(7, 1, 'historia',
 '{"narrativa":"Víctor Quispe es un pintor moqueguano que lleva años intentando capturar en sus lienzos los colores del desierto de Moquegua. No es un desierto cualquiera: en las mañanas el suelo es ocre y violeta, al mediodía se vuelve blanco y dorado, y al atardecer los cerros se tiñen de naranja encendido y rojo oscuro. Víctor mezcla pinturas en su taller, que está lleno de frascos de colores primarios: rojo, azul y amarillo. Sabe que con esos tres colores puede crear casi cualquier tono que vea en la naturaleza, pero a veces los colores no le salen como quiere. Un día, su sobrina Daniela de diez años lo visita y le pregunta: Tío, ¿cómo haces el color anaranjado del cerro? Víctor le explica que el naranja se obtiene mezclando rojo y amarillo, pero la cantidad de cada uno determina si el resultado es más rojizo o más amarillento. Daniela queda fascinada. Le pregunta por el morado de las sombras, por el café de la tierra y por el gris verdoso de los cactus. Víctor le muestra cada mezcla con paciencia y Daniela anota todo en su cuaderno. Esa tarde aprende que los colores primarios son como un idioma: con pocas palabras se pueden construir infinitas historias. Y el desierto de Moquegua, con toda su paleta cambiante, es el mejor libro de colores que existe.","pregunta_disparadora":"¿Qué colores usarías para pintar un atardecer en el desierto de Moquegua? ¿Qué colores primarios mezclarías para obtener esos tonos?"}'),
(7, 2, 'actividad',
 '{"materiales":["Pinturas o crayolas de colores primarios (rojo, azul, amarillo)","Papel blanco","Pincel o palillo mezclador","Paleta o plato pequeño"],"instrucciones":["Observa el cielo, la tierra o una fotografía del desierto de Moquegua. Identifica al menos 5 colores que ves.","Mezcla colores primarios para obtener cada uno de esos colores. Por ejemplo: rojo + amarillo = naranja, azul + amarillo = verde, rojo + azul = violeta.","Pinta cuadros de muestra de cada color que obtuviste y escribe al lado qué colores mezclaste y en qué proporción (más rojo que amarillo, partes iguales, etc.).","Crea una paleta de colores del desierto moqueguano con al menos 6 tonos diferentes.","Pinta una pequeña escena del desierto usando solo los colores de tu paleta."],"minutos":20}'),
(7, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(7, 4, 'entregable',
 '{"consigna":"Entrega tu paleta de colores del desierto moqueguano con los colores mezclados, las fórmulas de mezcla anotadas y la pequeña escena pintada.","formatos":["dibujo_cientifico","mural_digital"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ---- Módulo 8: Mural del barrio (modulo_id=8) ----------------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(8, 1, 'historia',
 '{"narrativa":"La junta vecinal del barrio San Antonio de Moquegua se reunió una tarde para hablar de la plaza del barrio. Las paredes de la placita estaban grises y desanimadas, y los vecinos querían hacer algo para cambiarlas. Alguien propuso contratar a un artista, pero el presupuesto era muy limitado. Entonces la señora Elena, directora de la escuela vecina, tuvo una idea: ¿Y si son los propios niños del barrio quienes pintan el mural? Los vecinos dudaron al principio, pero ella les explicó que en muchas ciudades del mundo los murales comunitarios son hechos por jóvenes artistas que expresan la historia y los sueños de su comunidad. La propuesta fue aprobada. Al día siguiente, la señora Elena visitó el aula de 5to A con la gran noticia: van a diseñar el mural de la plaza San Antonio. Los estudiantes estallaron de emoción. Pero diseñar un mural colectivo no es fácil: hay que ponerse de acuerdo en el tema, en los colores, en quién dibuja qué parte y cómo las piezas encajan en el conjunto. Carlos, el más callado de la clase, levantó la mano y dijo: Primero necesitamos saber de qué trata el mural. ¿Qué historia queremos contar? Ese fue el mejor inicio posible para el proyecto más grande que 5to A había enfrentado.","pregunta_disparadora":"Si pudieras pintar un mural en una pared de tu barrio, ¿qué historia o imagen moqueguana escogerías? ¿Por qué eso es importante para tu comunidad?"}'),
(8, 2, 'actividad',
 '{"materiales":["Papel grande (puede ser varias hojas pegadas)","Lápices de colores, crayolas o temperas","Lápiz para borrador"],"instrucciones":["Elige un tema para tu mural: puede ser la historia de Moquegua, la naturaleza local, los oficios del mercado, las fiestas tradicionales, etc.","Dibuja en papel grande el diseño de tu mural dividido en al menos 3 secciones o escenas relacionadas entre sí.","Usa colores que representen a Moquegua: los ocres del desierto, el verde de los olivos, el azul del cielo limpio.","Agrega al menos un texto corto dentro del mural: una frase, el nombre del barrio o una palabra en quechua.","Comparte tu diseño con tus compañeros y explica por qué elegiste ese tema y esos colores."],"minutos":25}'),
(8, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(8, 4, 'entregable',
 '{"consigna":"Entrega el diseño de tu mural en papel grande con al menos 3 secciones temáticas, colores representativos de Moquegua y un texto incluido.","formatos":["mural_digital","dibujo_cientifico"],"instrucciones":"Sube una foto de tu diseño de mural terminado. Máx 5 MB."}');

-- ---- Módulo 9: Tejidos y patrones (modulo_id=9) --------------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(9, 1, 'historia',
 '{"narrativa":"En el mercado artesanal de Moquegua, doña Gregoria y su hermana Rosa llegan cada sábado con sus tejidos. Las dos aprendieron a tejer de su madre, quien aprendió de la suya, en una cadena de conocimiento que se pierde en el tiempo. Sus tejidos tienen patrones que no son simples adornos: cada figura tiene un significado. Los rombos representan los ojos de la tierra, las líneas en zigzag son los rayos del trueno y los cuadrados concéntricos simbolizan las terrazas de cultivo. Un niño que pasaba por el mercado se detuvo frente a los tejidos y le preguntó a doña Gregoria: ¿Cómo sabe qué hilo va en cada lugar? La tejedora sonrió y le dijo: Los patrones tienen una lógica, igual que las matemáticas. Si repites la misma secuencia de colores e hilos, el patrón aparece solo. El niño se quedó fascinado. Nunca había pensado que tejer fuera algo matemático. Doña Gregoria le mostró cómo un patrón de tres colores que se repite cada cinco hilos crea una franja decorativa que puede extenderse infinitamente. El niño sacó su cuaderno y empezó a copiar el patrón en papel cuadriculado, coloreando cada celda como si fuera un hilo. Doña Gregoria lo observó y dijo: Ahora eres un tejedor de papel. Y así comenzó el aprendizaje.","pregunta_disparadora":"¿Qué patrones geométricos puedes observar en los tejidos andinos? ¿Cómo crees que los tejedores los crean sin equivocarse?"}'),
(9, 2, 'actividad',
 '{"materiales":["Papel cuadriculado","Lápices de colores (mínimo 3 colores)","Lápiz"],"instrucciones":["Observa los patrones andinos que te muestra tu profesor o busca ejemplos de tejidos moqueguanos.","Reproduce en papel cuadriculado al menos 2 patrones geométricos andinos: uno con rombos y uno con líneas en zigzag. Cada cuadradito del papel representa un hilo.","Crea tu propio patrón original usando al menos 3 colores y una secuencia que se repita mínimo 4 veces.","Escribe debajo de cada patrón cuál es la unidad que se repite (la secuencia básica).","Une los tres patrones en una tira decorativa horizontal y coloréala completamente."],"minutos":20}'),
(9, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(9, 4, 'entregable',
 '{"consigna":"Entrega tu hoja de patrones con los dos patrones andinos reproducidos y tu patrón original, todos correctamente coloreados y con la unidad de repetición identificada.","formatos":["dibujo_cientifico","ficha"],"instrucciones":"Sube una foto de tu hoja de patrones terminada. Máx 5 MB."}');

-- ---- Módulo 10: Puentes con papel (modulo_id=10) -------------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(10, 1, 'historia',
 '{"narrativa":"La comunidad de Quinistaquillas, a pocos kilómetros de Moquegua, tiene un problema serio: el pequeño puente que conecta su barrio con la escuela está dañado después de las últimas lluvias. Elena, una niña de diez años que vive ahí, tiene que dar una vuelta de veinte minutos para llegar a clases porque el puente ya no es seguro. Un día, su tío Germán, que estudia ingeniería en la universidad, le explica que los puentes funcionan gracias a la distribución del peso: cuando pisamos un puente, la fuerza que ejercemos no cae en un solo punto sino que se distribuye a lo largo de la estructura. Elena le pregunta: ¿Y cómo saben los ingenieros cuánto peso puede soportar un puente antes de construirlo? Germán le dice que los ingenieros hacen pruebas con modelos pequeños antes de construir el puente real. Usan materiales baratos para experimentar con diferentes diseños y medir cuál soporta más peso. Elena se entusiasma y decide hacer su propio experimento en casa con materiales simples. Esa tarde aprende algo fundamental: que la ingeniería no comienza con máquinas ni con computadoras, sino con una pregunta: ¿Cómo puedo resolver este problema? Y con la valentía de intentarlo aunque no salga perfecto a la primera.","pregunta_disparadora":"¿Qué forma crees que hace más resistente a un puente: plano, en arco o con triángulos de soporte? ¿Por qué?"}'),
(10, 2, 'actividad',
 '{"materiales":["5 hojas de papel bond","Cinta adhesiva","Tijeras","Monedas o pesas pequeñas","Regla","2 pilas de libros o sillas como apoyos"],"instrucciones":["Diseña y construye un puente de papel que conecte dos apoyos separados 20 cm. Solo puedes usar papel y cinta adhesiva.","Primero dibuja tu diseño antes de construir: ¿Usarás papel plano, doblado en acordeón, enrollado en tubos o con forma de arco?","Construye tu puente y colócalo entre los dos apoyos.","Prueba cuántas monedas o cuánto peso soporta tu puente antes de colapsar. Registra el resultado.","Modifica tu diseño para hacerlo más resistente y prueba de nuevo. Compara los resultados de ambos intentos."],"minutos":25}'),
(10, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(10, 4, 'entregable',
 '{"consigna":"Entrega una foto de tu prototipo de puente con el registro del peso soportado en ambos intentos y una descripción de los cambios que hiciste en el segundo diseño.","formatos":["prototipo","ficha"],"instrucciones":"Sube una foto de tu puente terminado con el peso soportado. Máx 5 MB."}');

-- ---- Módulo 11: Filtro de agua (modulo_id=11) ----------------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(11, 1, 'historia',
 '{"narrativa":"En la comunidad de Tumilaca, cerca de Moquegua, el agua que llega al pozo del pueblo a veces trae tierra, arena y partículas que la hacen turbia y peligrosa para beber. Don Marcelo, el presidente de la junta comunal, llevaba meses buscando una solución sin gastar mucho dinero. Un día, su hija Luciana llegó de la escuela muy emocionada: Papá, la profesora nos enseñó que se puede limpiar el agua con materiales naturales. Don Marcelo la escuchó con escepticismo, pero le dio permiso de intentarlo. Luciana recordó lo que había aprendido: el agua sucia pasa por capas de diferentes materiales, y cada capa atrapa partículas más pequeñas. La piedra grande retiene los objetos más grandes, la arena fina atrapa las partículas medianas y el algodón filtra las más pequeñas. No elimina los gérmenes, pero clarifica el agua de manera visible. Don Marcelo observó cómo su hija construía el filtro con una botella plástica cortada, y cuando vio el agua salir más clara al otro lado, quedó impresionado. Ese experimento le enseñó dos cosas: que la ciencia no siempre necesita equipos caros, y que a veces los que más saben de soluciones creativas son los niños que todavía hacen preguntas sin miedo.","pregunta_disparadora":"¿Por qué crees que diferentes capas de materiales pueden limpiar el agua mejor que una sola capa? ¿Qué tipo de material atraparía las partículas más pequeñas?"}'),
(11, 2, 'actividad',
 '{"materiales":["Botella de plástico grande cortada por la mitad","Arena fina limpia","Piedras pequeñas","Algodón o tela fina","Agua turbia (con tierra o arena)","Vaso o recipiente para recoger el agua filtrada"],"instrucciones":["Corta la botella plástica por la mitad. La mitad superior (con la boca hacia abajo) será el filtro; la mitad inferior recogerá el agua filtrada.","Coloca las capas en este orden desde abajo hacia arriba (recuerda que el agua baja): algodón, luego arena fina, luego piedras pequeñas.","Vierte lentamente el agua turbia sobre las piedras y observa qué sucede.","Compara el agua que entra y el agua que sale. Escribe o dibuja las diferencias que observas.","Responde: ¿El agua quedó completamente limpia? ¿Qué crees que le faltaría para ser apta para beber?"],"minutos":25}'),
(11, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(11, 4, 'entregable',
 '{"consigna":"Entrega una foto de tu filtro construido junto con un registro escrito donde compares el agua antes y después del filtrado, y tus conclusiones sobre la efectividad del filtro.","formatos":["prototipo","ficha"],"instrucciones":"Sube una foto de tu filtro en funcionamiento. Máx 5 MB."}');

-- ---- Módulo 12: Torre sísmica (modulo_id=12) -----------------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(12, 1, 'historia',
 '{"narrativa":"Moquegua es una ciudad que conoce bien los terremotos. El más recordado ocurrió en el año 2001, cuando un sismo de gran magnitud afectó a miles de familias y derrumbó edificios que no habían sido construidos para resistir ese tipo de movimiento. Desde entonces, la región ha aprendido mucho sobre construcción sismorresistente. Andrés es un estudiante de quinto grado cuyo padre trabaja en construcción. Un día, su padre le explica que los ingenieros diseñan los edificios para que no sean rígidos sino flexibles: si la estructura puede moverse un poco sin romperse, puede sobrevivir un sismo. Andrés se queda pensando en eso y le pregunta: ¿Qué es más resistente a los temblores, algo rígido o algo flexible? Su padre le dice: Haz el experimento tú mismo. Andrés decide construir dos torres pequeñas: una con material rígido y otra con material flexible, y las sacude para ver cuál aguanta más. Descubre que la torre flexible se tambalea pero no cae, mientras que la rígida cae al primer golpe fuerte. Esa tarde aprende que en ingeniería, la resistencia no siempre significa dureza: a veces significa la capacidad de ceder un poco para no romperse del todo. Es una lección que va mucho más allá de los puentes y las torres.","pregunta_disparadora":"¿Por qué crees que una estructura flexible puede ser más resistente a los sismos que una estructura completamente rígida?"}'),
(12, 2, 'actividad',
 '{"materiales":["Palitos de helado o palillos de madera","Plastilina o goma de mascar","Regla","Superficie plana para sacudir"],"instrucciones":["Diseña y construye una torre de al menos 20 cm de altura usando palitos de helado y plastilina como uniones.","Piensa en cómo harás la base: ¿será ancha o angosta? ¿Usarás triángulos o cuadrados en la estructura?","Construye tu torre y mídela con la regla.","Simula un sismo sacudiendo suavemente la superficie donde está la torre. Aumenta gradualmente la intensidad.","Registra cuánto sacudido aguanta antes de deformarse o caer. Modifica el diseño si es necesario e intenta de nuevo."],"minutos":25}'),
(12, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(12, 4, 'entregable',
 '{"consigna":"Entrega una foto de tu torre sísmica construida con la descripción del diseño (por qué elegiste esa forma de base y estructura) y el resultado de la prueba de sacudido.","formatos":["prototipo","ficha"],"instrucciones":"Sube una foto de tu torre con su descripción. Máx 5 MB."}');


-- ---- Módulo 13: My Moquegua (modulo_id=13) -------------------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(13, 1, 'historia',
 '{"narrativa":"Tim is a young tourist from Canada who has arrived in Moquegua for the first time. He is very excited because his grandfather once told him about the beautiful deserts and olive groves of southern Peru. But when Tim walks through the streets near the central market, he realizes he does not understand anything people are saying, and the signs are all in Spanish. He feels a little lost. A girl named Valeria sees him looking confused in front of a shop and decides to help him. She speaks a little English from school and tries to say: Hello! Welcome to Moquegua! Tim smiles immediately. Valeria shows him the market, the church, and the main plaza. She teaches him a few words in Spanish: mercado means market, cerro means hill, and gracias means thank you. Tim teaches her a few English words in return. By the end of the afternoon, they have both learned something new. Tim writes in his travel notebook: The people here are very friendly and this city is beautiful. I want to learn more Spanish. And Valeria writes in her school notebook: Today I spoke English with a real tourist. It was exciting. Hablar inglés es importante para conectarse con el mundo. / Speaking English is important to connect with the world.","pregunta_disparadora":"If a tourist arrived in your neighborhood and did not speak Spanish, what 5 phrases in English would be most useful to welcome them and help them?"}'),
(13, 2, 'actividad',
 '{"materiales":["Tarjeta de cartulina o papel grueso","Lápices de colores","Lápiz o lapicero"],"instrucciones":["Crea una tarjeta de bienvenida bilingüe (español e inglés) para un turista que llega a Moquegua.","La tarjeta debe incluir al menos 5 frases útiles en inglés con su traducción al español. Ejemplos: Welcome to Moquegua! / ¡Bienvenido a Moquegua!, The market is nearby. / El mercado está cerca.","Dibuja o decora la tarjeta con elementos representativos de Moquegua (el cerro Baúl, olivos, el desierto, el mercado).","Escribe el nombre de la ciudad en inglés y en español en la parte frontal de la tarjeta.","Practica leer las frases en voz alta con un compañero, uno haciendo de turista y otro de anfitrión."],"minutos":20}'),
(13, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(13, 4, 'entregable',
 '{"consigna":"Sube una foto de tu tarjeta de bienvenida bilingüe con las 5 frases en inglés y español y la decoración moqueguana.","formatos":["ficha","otro"],"instrucciones":"Sube una foto de tu tarjeta terminada. Máx 5 MB."}');

-- ---- Módulo 14: The market (modulo_id=14) --------------------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(14, 1, 'historia',
 '{"narrativa":"Sara is a ten-year-old girl who lives near the central market of Moquegua. Every Saturday she goes with her mom to buy vegetables, fruits, and sometimes a special treat. One day, Sara decides to practice her English at the market. She knows that some tourists visit the market to buy local products like olives, aguaymanto, and handmade crafts. Sara prepares a small notebook with useful English phrases. When she arrives at the market she sees a tourist couple looking confused at the fruit stand. Sara takes a deep breath and approaches them: Good morning! Can I help you? The tourists smile and one of them asks: How much is this? pointing at a bag of aguaymanto. Sara looks at the price sign and answers: It is five soles. That is about one dollar and fifty cents. The tourists buy two bags and thank Sara warmly. The seller, doña Rosa, looks at Sara with pride and says in Spanish: You just did business in two languages! Sara laughs and writes in her notebook: Today I learned that market vocabulary in English is very useful. Numbers, prices, and polite expressions can open many doors. / Hoy aprendí que el vocabulario del mercado en inglés es muy útil. Los números, los precios y las expresiones amables pueden abrir muchas puertas.","pregunta_disparadora":"¿Qué palabras y frases en inglés necesitarías saber para comprar y vender productos en un mercado? Piensa en al menos 6."}'),
(14, 2, 'actividad',
 '{"materiales":["Cuaderno o papel","Lápiz o lapicero"],"instrucciones":["Con un compañero, practica un diálogo de compra y venta en inglés en el mercado. Un estudiante es el comprador (buyer) y otro es el vendedor (seller).","El diálogo debe tener al menos 6 intercambios e incluir: saludo, pregunta por el precio (How much is it?), respuesta con precio en inglés, negociación o comentario, pago y despedida.","Usen productos típicos de Moquegua: olives (aceitunas), aguaymanto, oregano (orégano), cheese (queso), peppers (ajíes).","Escriban el diálogo en sus cuadernos antes de actuarlo.","Luego intercambien roles y repitan el diálogo con un producto diferente."],"minutos":20}'),
(14, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(14, 4, 'entregable',
 '{"consigna":"Entrega tu diálogo escrito de compra y venta en inglés con al menos 6 intercambios, incluyendo saludo, precios, negociación y despedida.","formatos":["ficha","otro"],"instrucciones":"Sube una foto de tu diálogo escrito. Máx 5 MB."}');

-- ---- Módulo 15: Nature around us (modulo_id=15) --------------------------
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(15, 1, 'historia',
 '{"narrativa":"Una mañana de primavera, la profesora de inglés entró al aula con una caja llena de tarjetas de colores. Cada tarjeta tenía escrita una palabra en inglés y un dibujo. Rosa, uno de los estudiantes, tomó una tarjeta verde con un árbol dibujado. La profesora dijo: Green. Tree. Green tree. Y luego preguntó: ¿Qué objeto de color verde tienen en casa o en el barrio? Los estudiantes empezaron a levantar la mano: plantas, puertas pintadas, uniformes escolares. La profesora entonces les pidió que miraran por la ventana. Afuera, los cerros de Moquegua eran marrones y ocres, los eucaliptos eran verdes, el cielo era azul y las flores del jardín eran amarillas y rojas. Brown hills. Green trees. Blue sky. Yellow flowers. Red flowers. Los estudiantes empezaron a repetir con entusiasmo. Lo más sorprendente fue cuando la profesora explicó que en inglés los colores y la naturaleza están muy conectados: el color naranja viene de la fruta orange, el color rosa viene de la flor rose, y el color violeta viene de la planta violet. Aprender colores en inglés es también aprender a ver la naturaleza con otros ojos. Y en Moquegua, con sus desiertos dorados, sus valles verdes y su cielo sin nubes, hay toda una paleta esperando ser nombrada en dos idiomas.","pregunta_disparadora":"¿Cuántos colores de la naturaleza que conoces en español puedes decir también en inglés? Haz una lista mental antes de empezar."}'),
(15, 2, 'actividad',
 '{"materiales":["Ficha impresa o papel","Lápices de colores","Lápiz o lapicero"],"instrucciones":["Dibuja o completa una ficha bilingüe de colores y naturaleza. Para cada color escribe: el nombre en inglés, el nombre en español, y un objeto de la naturaleza moqueguana de ese color.","Ejemplo: Red / Rojo / Flor de buganvilia. Yellow / Amarillo / Aguaymanto. Brown / Marrón / Cerro. Green / Verde / Olivo. Blue / Azul / Cielo. Orange / Naranja / Atardecer.","Incluye al menos 8 colores en tu ficha.","Dibuja al lado de cada entrada un pequeño objeto o elemento de la naturaleza de ese color.","Practica pronunciando los colores en inglés en voz alta con tu compañero de banco."],"minutos":20}'),
(15, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(15, 4, 'entregable',
 '{"consigna":"Entrega tu ficha bilingüe de colores y naturaleza con al menos 8 colores en inglés y español, cada uno con su objeto de la naturaleza moqueguana y su dibujo.","formatos":["ficha","dibujo_cientifico"],"instrucciones":"Sube una foto de tu ficha terminada. Máx 5 MB."}');

-- ===========================================================================
-- 6. quiz_preguntas — 3 preguntas × 15 módulos = 45 filas
-- ===========================================================================

-- ---- Módulo 1: El mercado de Moquegua ------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=1 AND numero_paso=3),
 '¿Qué es un gráfico de barras?',
 '[{"texto":"Una tabla de multiplicar","correcta":false},{"texto":"Una representación visual de datos con barras rectangulares","correcta":true},{"texto":"Un tipo de calculadora","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=1 AND numero_paso=3),
 'En la tabla de precios de Don Aurelio, la papa cuesta 2.50 soles y la cebolla cuesta 1.80 soles. ¿Cuál es el producto más caro?',
 '[{"texto":"La cebolla","correcta":false},{"texto":"Cuestan lo mismo","correcta":false},{"texto":"La papa","correcta":true}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=1 AND numero_paso=3),
 '¿Para qué sirve comparar precios en el mercado?',
 '[{"texto":"Para confundir a los vendedores","correcta":false},{"texto":"Para tomar decisiones de compra más inteligentes y justas","correcta":true},{"texto":"Para decorar el cuaderno con números","correcta":false}]',
 3);

-- ---- Módulo 2: Construyendo terrazas -------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=2 AND numero_paso=3),
 '¿Qué fracción representa la mitad de una terraza?',
 '[{"texto":"1/4","correcta":false},{"texto":"1/3","correcta":false},{"texto":"1/2","correcta":true}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=2 AND numero_paso=3),
 'Si divides una ladera en 4 terrazas iguales, ¿qué fracción corresponde a cada una?',
 '[{"texto":"1/4","correcta":true},{"texto":"1/3","correcta":false},{"texto":"1/2","correcta":false}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=2 AND numero_paso=3),
 '¿Cuánto es 1/4 + 1/4 + 1/2?',
 '[{"texto":"1 entero (toda la ladera)","correcta":true},{"texto":"1/2","correcta":false},{"texto":"2 enteros","correcta":false}]',
 3);

-- ---- Módulo 3: El aguaymanto y los kilos ---------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=3 AND numero_paso=3),
 'Doña Carmen tiene 8 kilos de aguaymanto a 4 soles el kilo. ¿Cuánto ganará en total?',
 '[{"texto":"32 soles","correcta":true},{"texto":"12 soles","correcta":false},{"texto":"28 soles","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=3 AND numero_paso=3),
 '¿Cuál es la operación correcta para calcular la ganancia de un producto?',
 '[{"texto":"Cantidad + Precio","correcta":false},{"texto":"Cantidad × Precio","correcta":true},{"texto":"Cantidad - Precio","correcta":false}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=3 AND numero_paso=3),
 'Si Erick vende 5 kilos de orégano a 7 soles cada kilo y 3 kilos de paprika a 6 soles cada kilo, ¿cuánto gana en total?',
 '[{"texto":"53 soles","correcta":true},{"texto":"35 soles","correcta":false},{"texto":"41 soles","correcta":false}]',
 3);

-- ---- Módulo 4: El río Moquegua habla ------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=4 AND numero_paso=3),
 '¿Cuál es el elemento que NO debe faltar en una carta formal?',
 '[{"texto":"Dibujos y colores decorativos","correcta":false},{"texto":"Saludo, cuerpo del mensaje y despedida","correcta":true},{"texto":"El número de páginas en el encabezado","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=4 AND numero_paso=3),
 'En la carta del río Moquegua, ¿desde qué punto de vista debe estar escrita?',
 '[{"texto":"En tercera persona (él, ella)","correcta":false},{"texto":"En primera persona (yo, el río)","correcta":true},{"texto":"Sin usar ningún pronombre","correcta":false}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=4 AND numero_paso=3),
 '¿Para qué sirve escribir una carta narrativa desde el punto de vista de un elemento de la naturaleza?',
 '[{"texto":"Para practicar caligrafía bonita","correcta":false},{"texto":"Para dar voz a quien no puede hablar y crear empatía con el medioambiente","correcta":true},{"texto":"Para aprender a copiar textos del libro","correcta":false}]',
 3);

-- ---- Módulo 5: Leyendas de Torata ----------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=5 AND numero_paso=3),
 '¿Cuál es la función principal de una leyenda en una cultura?',
 '[{"texto":"Entretener solamente sin otro propósito","correcta":false},{"texto":"Explicar fenómenos naturales y preservar la historia de un pueblo","correcta":true},{"texto":"Enseñar matemáticas de forma indirecta","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=5 AND numero_paso=3),
 'En la leyenda del volcán Ticsani, ¿qué formaron las lágrimas de Urpicha al partir?',
 '[{"texto":"El lago Titicaca","correcta":false},{"texto":"El río que riega los campos de Moquegua","correcta":true},{"texto":"Las terrazas de cultivo de los cerros","correcta":false}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=5 AND numero_paso=3),
 '¿Qué diferencia a una leyenda de una noticia periodística?',
 '[{"texto":"La leyenda tiene hechos verificables y la noticia no","correcta":false},{"texto":"La leyenda mezcla lo real con lo fantástico; la noticia reporta hechos verificables","correcta":true},{"texto":"No hay diferencia entre ambas","correcta":false}]',
 3);

-- ---- Módulo 6: Noticias de mi barrio -------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=6 AND numero_paso=3),
 '¿Cuáles son las seis preguntas básicas del periodismo?',
 '[{"texto":"Cuándo, dónde, por qué, para quién, cuánto y cómo","correcta":false},{"texto":"Qué, quién, cuándo, dónde, cómo y por qué","correcta":true},{"texto":"Qué, quién, cuántos, dónde, para qué y opinión","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=6 AND numero_paso=3),
 '¿En qué persona gramatical debe estar escrita una nota periodística?',
 '[{"texto":"Primera persona (yo opino que...)","correcta":false},{"texto":"Segunda persona (tú debes saber...)","correcta":false},{"texto":"Tercera persona (el alcalde inauguró...)","correcta":true}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=6 AND numero_paso=3),
 '¿Qué debe contener el primer párrafo de una nota periodística?',
 '[{"texto":"La información más importante resumida","correcta":true},{"texto":"Los detalles secundarios y anécdotas","correcta":false},{"texto":"La opinión personal del periodista","correcta":false}]',
 3);

-- ---- Módulo 7: Colores de Moquegua ----------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=7 AND numero_paso=3),
 '¿Cuáles son los tres colores primarios?',
 '[{"texto":"Verde, naranja y violeta","correcta":false},{"texto":"Rojo, azul y amarillo","correcta":true},{"texto":"Blanco, negro y gris","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=7 AND numero_paso=3),
 '¿Qué colores se mezclan para obtener el color naranja?',
 '[{"texto":"Azul y amarillo","correcta":false},{"texto":"Rojo y azul","correcta":false},{"texto":"Rojo y amarillo","correcta":true}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=7 AND numero_paso=3),
 'El pintor Víctor quiere pintar el color del cielo de Moquegua al mediodía. ¿Qué colores usaría para obtener un azul celeste más claro?',
 '[{"texto":"Azul puro sin mezclar nada","correcta":false},{"texto":"Azul mezclado con una pequeña cantidad de blanco","correcta":true},{"texto":"Azul mezclado con negro","correcta":false}]',
 3);

-- ---- Módulo 8: Mural del barrio -------------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=8 AND numero_paso=3),
 '¿Qué característica define a un mural comunitario?',
 '[{"texto":"Que sea pintado solo por artistas profesionales","correcta":false},{"texto":"Que esté en un espacio público y refleje la identidad o historia de una comunidad","correcta":true},{"texto":"Que use únicamente colores primarios sin mezclar","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=8 AND numero_paso=3),
 '¿Por qué es importante planificar un mural antes de empezar a pintar?',
 '[{"texto":"Para que salga perfectamente igual al primer intento","correcta":false},{"texto":"Para coordinar el tema, los colores y la distribución del espacio entre todos los participantes","correcta":true},{"texto":"Porque la pintura se seca muy rápido y no hay tiempo de pensar","correcta":false}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=8 AND numero_paso=3),
 '¿Qué elemento puede enriquecer un mural además de las imágenes?',
 '[{"texto":"Fórmulas matemáticas complicadas","correcta":false},{"texto":"Texto con frases, nombres de lugares o palabras en quechua","correcta":true},{"texto":"Números de teléfono y direcciones","correcta":false}]',
 3);

-- ---- Módulo 9: Tejidos y patrones ----------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=9 AND numero_paso=3),
 '¿Qué es un patrón en el arte textil andino?',
 '[{"texto":"Un error que se repite sin querer en el tejido","correcta":false},{"texto":"Una secuencia de colores o formas que se repite de manera ordenada","correcta":true},{"texto":"Un dibujo que solo aparece una vez en el tejido","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=9 AND numero_paso=3),
 'Doña Gregoria usa una secuencia de 3 colores que se repite cada 5 hilos. Si el tejido tiene 25 hilos de ancho, ¿cuántas veces se repite la secuencia?',
 '[{"texto":"3 veces","correcta":false},{"texto":"5 veces","correcta":true},{"texto":"10 veces","correcta":false}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=9 AND numero_paso=3),
 '¿Qué figura geométrica representan los rombos en los tejidos andinos según la leyenda textil?',
 '[{"texto":"Los rayos del trueno","correcta":false},{"texto":"Los ojos de la tierra","correcta":true},{"texto":"Las terrazas de cultivo","correcta":false}]',
 3);

-- ---- Módulo 10: Puentes con papel ----------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=10 AND numero_paso=3),
 '¿Por qué la forma triangular es tan usada en la construcción de puentes?',
 '[{"texto":"Porque es la figura geométrica más fácil de dibujar","correcta":false},{"texto":"Porque el triángulo distribuye el peso de forma eficiente y es muy rígido","correcta":true},{"texto":"Porque usa menos material que otras formas","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=10 AND numero_paso=3),
 'En el experimento de puentes de papel, ¿cuál técnica haría el papel más resistente?',
 '[{"texto":"Dejar el papel completamente plano sin doblar","correcta":false},{"texto":"Doblar el papel en forma de acordeón o enrollarlo en tubos","correcta":true},{"texto":"Mojar el papel antes de colocarlo","correcta":false}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=10 AND numero_paso=3),
 '¿Qué significa que un ingeniero haga un prototipo antes de construir la versión final?',
 '[{"texto":"Que copió el diseño de otro ingeniero","correcta":false},{"texto":"Que construye un modelo pequeño para probar y mejorar el diseño antes de la construcción real","correcta":true},{"texto":"Que el proyecto fracasó y hay que empezar de nuevo","correcta":false}]',
 3);

-- ---- Módulo 11: Filtro de agua -------------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=11 AND numero_paso=3),
 '¿Cuál es la función de la capa de arena fina en un filtro de agua casero?',
 '[{"texto":"Darle color al agua filtrada","correcta":false},{"texto":"Retener partículas medianas que las piedras no atraparon","correcta":true},{"texto":"Agregar minerales beneficiosos al agua","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=11 AND numero_paso=3),
 '¿El filtro casero de arena, piedras y algodón hace el agua segura para beber directamente?',
 '[{"texto":"Sí, el agua queda completamente purificada","correcta":false},{"texto":"No, solo clarifica el agua pero no elimina gérmenes ni bacterias","correcta":true},{"texto":"Depende del color del recipiente que se use","correcta":false}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=11 AND numero_paso=3),
 '¿En qué orden deben colocarse las capas del filtro (de abajo hacia arriba)?',
 '[{"texto":"Piedras, arena, algodón","correcta":false},{"texto":"Algodón, arena fina, piedras pequeñas","correcta":true},{"texto":"Arena, algodón, piedras","correcta":false}]',
 3);

-- ---- Módulo 12: Torre sísmica -------------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=12 AND numero_paso=3),
 '¿Por qué una base ancha hace más estable a una torre frente a los sismos?',
 '[{"texto":"Porque pesa más y aplasta el suelo","correcta":false},{"texto":"Porque distribuye mejor el peso y reduce el riesgo de volcamiento","correcta":true},{"texto":"Porque usa más material de construcción","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=12 AND numero_paso=3),
 '¿Qué característica de una estructura la hace más resistente a los movimientos sísmicos?',
 '[{"texto":"Ser completamente rígida sin ningún movimiento","correcta":false},{"texto":"Tener cierta flexibilidad para absorber la energía del sismo sin romperse","correcta":true},{"texto":"Ser lo más alta posible para estar lejos del suelo","correcta":false}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=12 AND numero_paso=3),
 'Moquegua sufrió un terremoto destructivo en el año:',
 '[{"texto":"1970","correcta":false},{"texto":"2001","correcta":true},{"texto":"2015","correcta":false}]',
 3);

-- ---- Módulo 13: My Moquegua (inglés) ------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=13 AND numero_paso=3),
 'How do you say "Bienvenido a Moquegua" in English?',
 '[{"texto":"Goodbye Moquegua","correcta":false},{"texto":"Welcome to Moquegua","correcta":true},{"texto":"Hello, I am Moquegua","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=13 AND numero_paso=3),
 'What does "market" mean in Spanish?',
 '[{"texto":"Cerro","correcta":false},{"texto":"Plaza","correcta":false},{"texto":"Mercado","correcta":true}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=13 AND numero_paso=3),
 'Which phrase is the correct way to greet someone in English?',
 '[{"texto":"Good morning! / Buenos días","correcta":true},{"texto":"Thank you! / Gracias (as a greeting)","correcta":false},{"texto":"Goodbye! / Adiós (as an initial greeting)","correcta":false}]',
 3);

-- ---- Módulo 14: The market (inglés) -------------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=14 AND numero_paso=3),
 'How do you ask for the price of something in English?',
 '[{"texto":"Where is it?","correcta":false},{"texto":"How much is it?","correcta":true},{"texto":"What is your name?","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=14 AND numero_paso=3),
 'If aguaymanto costs "five soles", how do you say the number five in English?',
 '[{"texto":"Four","correcta":false},{"texto":"Six","correcta":false},{"texto":"Five","correcta":true}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=14 AND numero_paso=3),
 'In the market dialogue, what does "I would like to buy..." mean in Spanish?',
 '[{"texto":"No quiero comprar...","correcta":false},{"texto":"Quisiera comprar...","correcta":true},{"texto":"Ya compré...","correcta":false}]',
 3);

-- ---- Módulo 15: Nature around us (inglés) --------------------------------
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id=15 AND numero_paso=3),
 'What color is the aguaymanto fruit in English?',
 '[{"texto":"Red / Rojo","correcta":false},{"texto":"Yellow / Amarillo","correcta":true},{"texto":"Blue / Azul","correcta":false}]',
 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=15 AND numero_paso=3),
 'How do you say "árbol" in English?',
 '[{"texto":"Flower","correcta":false},{"texto":"River","correcta":false},{"texto":"Tree","correcta":true}]',
 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=15 AND numero_paso=3),
 'What is the English word for the color "marrón" (the color of the hills of Moquegua)?',
 '[{"texto":"Green","correcta":false},{"texto":"Brown","correcta":true},{"texto":"Purple","correcta":false}]',
 3);


-- ===========================================================================
-- 7. aula_modulos — 15 módulos asignados al aula 1, fechas semanales
-- ===========================================================================
INSERT INTO aula_modulos (aula_id, modulo_id, fecha_planificada, asignado_por) VALUES
(1,  1, '2026-04-14', 1),
(1,  2, '2026-04-21', 1),
(1,  3, '2026-04-28', 1),
(1,  4, '2026-05-05', 1),
(1,  5, '2026-05-12', 1),
(1,  6, '2026-05-19', 1),
(1,  7, '2026-05-26', 1),
(1,  8, '2026-06-02', 1),
(1,  9, '2026-06-09', 1),
(1, 10, '2026-06-16', 1),
(1, 11, '2026-06-23', 1),
(1, 12, '2026-06-30', 1),
(1, 13, '2026-07-07', 1),
(1, 14, '2026-07-14', 1),
(1, 15, '2026-07-21', 1);

-- ===========================================================================
-- Notificaciones & Mensajes (added in migration 001)
-- ===========================================================================

CREATE TABLE IF NOT EXISTS notificaciones (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id  INT UNSIGNED NOT NULL,
  tipo        ENUM('modulo','mensaje','sistema','alerta') NOT NULL DEFAULT 'sistema',
  titulo      VARCHAR(200) NOT NULL,
  mensaje     TEXT,
  url         VARCHAR(500),
  leida       TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_usuario (usuario_id),
  INDEX idx_leida   (usuario_id, leida),
  CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mensajes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  remitente_id    INT UNSIGNED NOT NULL,
  destinatario_id INT UNSIGNED NOT NULL,
  asunto          VARCHAR(300) NOT NULL,
  cuerpo          TEXT NOT NULL,
  leido           TINYINT(1) NOT NULL DEFAULT 0,
  archivado_rem   TINYINT(1) NOT NULL DEFAULT 0,
  archivado_dest  TINYINT(1) NOT NULL DEFAULT 0,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_dest  (destinatario_id, leido),
  INDEX idx_remit (remitente_id),
  CONSTRAINT fk_msg_rem  FOREIGN KEY (remitente_id)    REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_dest FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
-- End of schema.sql
-- ===========================================================================
