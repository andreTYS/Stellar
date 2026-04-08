-- ============================================================
-- INNOVA-STEAM v2 — Schema + Seed Data
-- Base académica: Saborio-Taylor & Garcia-Borbon (2021)
-- Moquegua, Perú — Ciclo V (5.° y 6.° primaria)
-- ============================================================

CREATE DATABASE IF NOT EXISTS innovasteam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE innovasteam;
SET NAMES utf8mb4;

-- Drop en orden inverso de dependencias
DROP TABLE IF EXISTS certificados;
DROP TABLE IF EXISTS asistencia;
DROP TABLE IF EXISTS sesiones;
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

-- ============================================================
-- TABLAS
-- ============================================================

CREATE TABLE colegios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(200) NOT NULL,
  distrito   VARCHAR(100) NOT NULL,
  ugel_codigo VARCHAR(20),
  director   VARCHAR(150),
  telefono   VARCHAR(20),
  activo     TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE usuarios (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(100) NOT NULL,
  apellido        VARCHAR(100) NOT NULL,
  email           VARCHAR(150) UNIQUE,
  codigo_acceso   VARCHAR(20) UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  rol             ENUM('admin','admin_colegio','docente','practicante','estudiante') NOT NULL,
  colegio_id      INT,
  universidad     VARCHAR(150),
  telefono_tutor  VARCHAR(20),
  activo          TINYINT(1) DEFAULT 1,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (colegio_id) REFERENCES colegios(id)
) ENGINE=InnoDB;

CREATE TABLE aulas (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  colegio_id   INT NOT NULL,
  grado        ENUM('5to','6to') NOT NULL,
  seccion      VARCHAR(5) NOT NULL,
  anio_escolar YEAR NOT NULL,
  docente_id   INT,
  FOREIGN KEY (colegio_id) REFERENCES colegios(id),
  FOREIGN KEY (docente_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE practicante_aula (
  practicante_id INT NOT NULL,
  aula_id        INT NOT NULL,
  asignado_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (practicante_id, aula_id),
  FOREIGN KEY (practicante_id) REFERENCES usuarios(id),
  FOREIGN KEY (aula_id) REFERENCES aulas(id)
) ENGINE=InnoDB;

CREATE TABLE estudiante_aula (
  estudiante_id INT NOT NULL,
  aula_id       INT NOT NULL,
  inscrito_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (estudiante_id, aula_id),
  FOREIGN KEY (estudiante_id) REFERENCES usuarios(id),
  FOREIGN KEY (aula_id) REFERENCES aulas(id)
) ENGINE=InnoDB;

CREATE TABLE cursos (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(100) NOT NULL,
  slug             VARCHAR(50) NOT NULL UNIQUE,
  color_hex        VARCHAR(7) NOT NULL,
  icono            VARCHAR(10),
  descripcion      TEXT,
  competencias_cneb TEXT,
  activo           TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE modulos (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  curso_id          INT NOT NULL,
  titulo            VARCHAR(200) NOT NULL,
  descripcion       TEXT,
  grado_ciclo       ENUM('ciclo_v','ambos') DEFAULT 'ciclo_v',
  minutos_estimados INT DEFAULT 45,
  orden             INT DEFAULT 0,
  activo            TINYINT(1) DEFAULT 1,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (curso_id) REFERENCES cursos(id)
) ENGINE=InnoDB;

CREATE TABLE modulo_pasos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  modulo_id   INT NOT NULL,
  numero_paso TINYINT NOT NULL,
  tipo        ENUM('historia','actividad','quiz','entregable') NOT NULL,
  contenido   JSON NOT NULL,
  UNIQUE KEY unique_paso (modulo_id, numero_paso),
  FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_preguntas (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  paso_id  INT NOT NULL,
  texto    TEXT NOT NULL,
  opciones JSON NOT NULL,
  orden    INT DEFAULT 0,
  FOREIGN KEY (paso_id) REFERENCES modulo_pasos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE aula_modulos (
  aula_id           INT NOT NULL,
  modulo_id         INT NOT NULL,
  fecha_planificada DATE,
  asignado_por      INT,
  PRIMARY KEY (aula_id, modulo_id),
  FOREIGN KEY (aula_id)       REFERENCES aulas(id),
  FOREIGN KEY (modulo_id)     REFERENCES modulos(id),
  FOREIGN KEY (asignado_por)  REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE progreso_estudiante (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  estudiante_id   INT NOT NULL,
  modulo_id       INT NOT NULL,
  paso_actual     TINYINT DEFAULT 1,
  estrellas_quiz  TINYINT DEFAULT 0,
  intentos_quiz   TINYINT DEFAULT 0,
  completado      TINYINT(1) DEFAULT 0,
  completado_en   TIMESTAMP NULL,
  UNIQUE KEY (estudiante_id, modulo_id),
  FOREIGN KEY (estudiante_id) REFERENCES usuarios(id),
  FOREIGN KEY (modulo_id)     REFERENCES modulos(id)
) ENGINE=InnoDB;

CREATE TABLE quiz_respuestas (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  estudiante_id   INT NOT NULL,
  pregunta_id     INT NOT NULL,
  opcion_elegida  INT NOT NULL,
  es_correcta     TINYINT(1),
  respondido_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (estudiante_id) REFERENCES usuarios(id),
  FOREIGN KEY (pregunta_id)   REFERENCES quiz_preguntas(id)
) ENGINE=InnoDB;

CREATE TABLE entregables (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  estudiante_id      INT NOT NULL,
  modulo_id          INT NOT NULL,
  formato            ENUM('dibujo_cientifico','mural_digital','cuento_ilustrado','prototipo','ficha','otro') NOT NULL,
  archivo_url        VARCHAR(500) NOT NULL,
  comentario_docente TEXT,
  comentado_por      INT,
  calificacion       TINYINT,
  subido_en          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (estudiante_id)  REFERENCES usuarios(id),
  FOREIGN KEY (modulo_id)      REFERENCES modulos(id),
  FOREIGN KEY (comentado_por)  REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE sesiones (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  practicante_id  INT NOT NULL,
  aula_id         INT NOT NULL,
  modulo_id       INT NOT NULL,
  fecha_sesion    DATE NOT NULL,
  asistentes      INT DEFAULT 0,
  notas           TEXT,
  fotos_actividad JSON,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (practicante_id) REFERENCES usuarios(id),
  FOREIGN KEY (aula_id)        REFERENCES aulas(id),
  FOREIGN KEY (modulo_id)      REFERENCES modulos(id)
) ENGINE=InnoDB;

CREATE TABLE asistencia (
  sesion_id     INT NOT NULL,
  estudiante_id INT NOT NULL,
  presente      TINYINT(1) DEFAULT 1,
  PRIMARY KEY (sesion_id, estudiante_id),
  FOREIGN KEY (sesion_id)     REFERENCES sesiones(id),
  FOREIGN KEY (estudiante_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE certificados (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  estudiante_id INT NOT NULL,
  modulo_id     INT NOT NULL,
  pdf_url       VARCHAR(500),
  emitido_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (estudiante_id, modulo_id),
  FOREIGN KEY (estudiante_id) REFERENCES usuarios(id),
  FOREIGN KEY (modulo_id)     REFERENCES modulos(id)
) ENGINE=InnoDB;


-- ============================================================
-- SEED: Colegio, Cursos, Usuarios, Aula
-- ============================================================

INSERT INTO colegios (nombre, distrito, ugel_codigo, director, telefono) VALUES
('GUE Mariscal Nieto', 'Moquegua', 'UGEL-MQ-001', 'Prof. Roberto Quispe Mamani', '053-462100');

INSERT INTO cursos (nombre, slug, color_hex, icono, descripcion) VALUES
('Matemática',   'matematica',   '#f5c842', '📐', 'Números, operaciones, estadística y geometría desde el entorno moqueguano'),
('Comunicación', 'comunicacion', '#4a9eff', '📖', 'Lectura, escritura y expresión oral conectadas con la cultura local'),
('Arte',         'arte',         '#3ecf8e', '🎨', 'Creación artística, apreciación cultural y expresión visual'),
('Ingeniería',   'ingenieria',   '#a78bfa', '⚙️',  'Diseño, construcción y resolución de problemas tecnológicos'),
('Inglés',       'ingles',       '#ff6b6b', '🌍', 'Comunicación básica en inglés conectada con el mundo global');

-- Contraseña para todos: 'password' (bcrypt). En producción ejecutar: password_hash('1234', PASSWORD_BCRYPT)
INSERT INTO usuarios (nombre, apellido, email, codigo_acceso, password_hash, rol, colegio_id) VALUES
('Admin',    'Sistema',  'admin@innovasteam.edu.pe',       NULL,      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',         NULL),
('Carlos',   'Vargas',   'admincol@innovasteam.edu.pe',    NULL,      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin_colegio', 1),
('María',    'Flores',   'docente@innovasteam.edu.pe',     NULL,      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'docente',       1),
('Jorge',    'Quispe',   'practicante@innovasteam.edu.pe', NULL,      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'practicante',   1),
('Sofía',    'Mamani',   NULL,                              'EST-001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'estudiante',    1);

INSERT INTO aulas (colegio_id, grado, seccion, anio_escolar, docente_id) VALUES (1, '5to', 'A', 2026, 3);

INSERT INTO practicante_aula (practicante_id, aula_id) VALUES (4, 1);
INSERT INTO estudiante_aula  (estudiante_id,  aula_id) VALUES (5, 1);


-- ============================================================
-- SEED: 15 Módulos
-- ============================================================

INSERT INTO modulos (curso_id, titulo, descripcion, orden, minutos_estimados) VALUES
(1, 'El mercado de Moquegua',    'Registra y compara precios con gráficos de barras.',            1, 45),
(1, 'Construyendo terrazas',     'Mide y divide áreas en fracciones como los Incas.',             2, 45),
(1, 'El aguaymanto y los kilos', 'Multiplicación con precios reales de productos moqueguanos.',  3, 45),
(2, 'El río Moquegua habla',     'Escribe una carta narrativa desde la perspectiva del río.',    1, 45),
(2, 'Leyendas de Torata',        'Reescribe y crea tu propia leyenda moqueguana.',               2, 45),
(2, 'Noticias de mi barrio',     'Redacta noticias del periódico escolar.',                      3, 45),
(3, 'Colores de Moquegua',       'Mezcla colores primarios para capturar el desierto moqueguano.',1, 45),
(3, 'Mural del barrio',          'Diseña y crea arte colectivo para la comunidad.',              2, 45),
(3, 'Tejidos y patrones',        'Reproduce y crea patrones geométricos andinos.',               3, 45),
(4, 'Puentes con papel',         'Diseña un puente resistente con solo papel.',                  1, 45),
(4, 'Filtro de agua',            'Construye un filtro de agua natural.',                         2, 45),
(4, 'Torre sísmica',             'Construye una torre que resista los temblores de Moquegua.',  3, 45),
(5, 'My Moquegua',               'Crea una tarjeta de bienvenida bilingüe para turistas.',      1, 45),
(5, 'The market',                'Practica diálogos de compra y venta en inglés.',              2, 45),
(5, 'Nature around us',          'Aprende vocabulario de colores y naturaleza en inglés.',      3, 45);


-- ============================================================
-- SEED: modulo_pasos (60 filas — 4 por módulo)
-- ============================================================

-- Módulo 1: El mercado de Moquegua
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(1,1,'historia','{"narrativa":"Don Aurelio tiene un puesto en el mercado central de Moquegua donde vende papa, chuño, maíz morado y orégano de la región. Lleva 15 años vendiendo los mismos productos, pero últimamente sus vecinos del mercado le dicen que sus precios son más bajos que los de los otros puestos. El problema es que don Aurelio nunca ha llevado un registro de los precios. Cuando llega la mañana, fija sus precios de memoria sin comparar con nadie. Su hija Lucía, que estudia en la GUE Mariscal Nieto, le propone una idea: visitar cinco puestos del mercado, anotar los precios de un mismo producto y hacer un gráfico para comparar. Así don Aurelio podrá saber si sus precios son justos o si necesita ajustarlos para no perder clientes ni ganancias.","pregunta_disparadora":"¿Cómo crees que podría don Aurelio descubrir si sus precios son justos sin tener que preguntarle directamente a sus competidores?"}'),
(1,2,'actividad','{"materiales":["Hoja de papel cuadriculado o blanco","Lápiz y colores","Regla"],"instrucciones":["Piensa en un producto que se venda en tu barrio o mercado (puede ser papa, limón, naranja u otro).","Imagina o pregunta el precio de ese producto en 5 lugares diferentes (tiendas, mercado, bodega).","Anota los 5 precios en una tabla con dos columnas: Lugar y Precio en soles.","Dibuja un gráfico de barras: en el eje horizontal pon los lugares y en el vertical los precios.","Colorea cada barra con un color diferente y ponle título a tu gráfico."],"minutos":20}'),
(1,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 1"}'),
(1,4,'entregable','{"consigna":"Muestra la tabla de precios que registraste y el gráfico de barras que dibujaste.","formatos":["dibujo_cientifico","ficha"],"instrucciones":"Toma una foto clara de tu tabla y gráfico. Sube la imagen. Máx 5 MB."}');

-- Módulo 2: Construyendo terrazas
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(2,1,'historia','{"narrativa":"En las laderas de los cerros de Torata, cerca de Moquegua, todavía se pueden ver las antiguas terrazas que construyeron los incas hace más de 600 años. Estas terrazas no eran simples escalones: estaban diseñadas con gran precisión matemática para aprovechar el agua de riego de manera exacta. El ingeniero inca Huanca explicaba a los jóvenes constructores que cada terraza debía tener exactamente la mitad del ancho de la terraza inferior para que el agua fluyera correctamente. Si la terraza de abajo medía 4 metros, la siguiente debía medir 2 metros, es decir, un medio. Esta idea de dividir espacios en partes iguales es lo que hoy llamamos fracciones. El joven aprendiz Tupac aprendió que las fracciones no eran solo números en un papel, sino herramientas para construir un sistema de irrigación que alimentó a miles de personas durante siglos.","pregunta_disparadora":"¿Puedes imaginar cómo se usarían las fracciones para dividir un terreno en partes iguales para sembrar diferentes cultivos?"}'),
(2,2,'actividad','{"materiales":["Papel cuadriculado o blanco tamaño A4","Lápices de colores (mínimo 3)","Regla","Tijeras (opcional)"],"instrucciones":["Dibuja un rectángulo grande en tu hoja que represente un terreno de 8 cuadrados de ancho.","Divide el rectángulo en 4 terrazas horizontales iguales (cada una tendrá 2 cuadrados de alto).","Colorea cada terraza con un color diferente y escribe la fracción que representa: 1/4, 2/4, 3/4, 4/4.","En cada terraza escribe qué cultivo sembraría: maíz, papa, chuño u orégano.","Al costado de tu dibujo escribe: ¿cuánto es 1/2 del terreno total? ¿Y 1/4?"],"minutos":20}'),
(2,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 2"}'),
(2,4,'entregable','{"consigna":"Muestra tu dibujo de terrazas con las fracciones marcadas en cada nivel.","formatos":["dibujo_cientifico","mural_digital"],"instrucciones":"Fotografía tu dibujo bien iluminado. Asegúrate de que se vean los colores y los números de fracciones claramente. Máx 5 MB."}');

-- Módulo 3: El aguaymanto y los kilos
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(3,1,'historia','{"narrativa":"En el distrito de Torata, a 45 minutos de Moquegua, doña Carmen cultiva aguaymanto en sus pequeñas parcelas de tierra. El aguaymanto es una fruta deliciosa y nutritiva que cada vez tiene más demanda en los mercados de Lima y Arequipa. Este año doña Carmen cosechó 15 kilos de aguaymanto. En el mercado local el precio es de 8 soles el kilo, pero si vende directamente a un comprador de Lima, puede conseguir 12 soles el kilo. El problema es que doña Carmen nunca estudió más allá del tercer grado y le cuesta calcular rápidamente cuánto dinero ganaría en cada caso. Su sobrino Andrés, estudiante de quinto de primaria, decide ayudarla a hacer los cálculos usando la multiplicación para que ella pueda tomar la mejor decisión.","pregunta_disparadora":"¿Cómo puede Andrés ayudar a doña Carmen a calcular cuánto dinero ganará en cada opción de venta?"}'),
(3,2,'actividad','{"materiales":["Hoja de papel","Lápiz","Calculadora (opcional para verificar)"],"instrucciones":["Resuelve: Si doña Carmen tiene 15 kilos y el precio local es S/8 por kilo, ¿cuánto ganará en total?","Resuelve: Si vende al comprador de Lima a S/12 por kilo, ¿cuánto ganará en total?","Crea tu propio problema: elige otro producto de tu región (papa, orégano, palta) y un precio. Calcula la ganancia para 10 kilos y para 25 kilos.","Compara los resultados y escribe: ¿Cuál opción le conviene más a doña Carmen y por qué?","Dibuja una tabla con tus cálculos mostrando: Producto, Kilos, Precio por kilo, Total."],"minutos":20}'),
(3,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 3"}'),
(3,4,'entregable','{"consigna":"Muestra tu hoja de cálculos con los problemas de multiplicación resueltos y la tabla comparativa.","formatos":["ficha","dibujo_cientifico"],"instrucciones":"Toma una foto clara de tus cálculos escritos a mano. Máx 5 MB."}');

-- Módulo 4: El río Moquegua habla
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(4,1,'historia','{"narrativa":"El río Moquegua nace en las alturas de los Andes y recorre más de 60 kilómetros hasta llegar al mar. Durante siglos fue la fuente de vida de todas las comunidades que vivían a sus orillas: les daba agua para beber, para regar sus cultivos y para sus animales. Pero Sofía, una niña de quinto grado, nota que el río ya no es lo que era antes. El agua llega turbia y a veces huele mal. Los peces han desaparecido casi por completo. Una tarde, Sofía se sienta a la orilla y cierra los ojos. En su imaginación, el río le habla: le cuenta cómo era antes, cristalino y lleno de vida, y le describe cómo la basura, las aguas servidas y los residuos mineros lo han ido enfermando poco a poco. El río le pide a Sofía que cuente su historia para que la gente entienda y cambie.","pregunta_disparadora":"Si el río Moquegua pudiera escribirle una carta a la comunidad, ¿qué crees que le diría y qué le pediría?"}'),
(4,2,'actividad','{"materiales":["Papel","Lápiz o lapicero","Colores (opcional para ilustrar)"],"instrucciones":["Imagina que eres el río Moquegua y vas a escribirle una carta a la comunidad.","La carta debe tener: saludo, cuerpo (tu historia y tu situación actual) y despedida con una petición.","Escribe al menos 3 párrafos: uno sobre cómo eras antes, uno sobre cómo estás ahora y uno con lo que necesitas de la comunidad.","Usa palabras que expresen sentimientos: tristeza, esperanza, gratitud, preocupación.","Si quieres, dibuja un pequeño río al lado de tu carta."],"minutos":20}'),
(4,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 4"}'),
(4,4,'entregable','{"consigna":"Comparte la carta que escribiste desde la perspectiva del río Moquegua.","formatos":["cuento_ilustrado","ficha"],"instrucciones":"Fotografía tu carta escrita a mano. Asegúrate de que la letra sea legible. Máx 5 MB."}');

-- Módulo 5: Leyendas de Torata
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(5,1,'historia','{"narrativa":"El abuelo Aurelio tiene 78 años y es uno de los últimos guardianes de las leyendas de Torata. Sentado bajo un viejo eucalipto, con su sombrero de paja y su poncho de alpaca, cuenta la historia del volcán Ticsani. Según la leyenda, el Ticsani era un guerrero poderoso que se enamoró de la laguna Pasto Grande. Pero los dioses de las montañas, celosos de su felicidad, lo convirtieron en volcán para separarlo de la laguna para siempre. Por eso, dicen los ancianos, el Ticsani aún humea de vez en cuando: son los suspiros del guerrero que extraña a su amada. El abuelo Aurelio le dice a su nieta Valentina que estas historias no deben perderse, que cada generación tiene la responsabilidad de contarlas de nuevo y de darles vida con su propia voz.","pregunta_disparadora":"¿Qué elementos de la naturaleza de tu región podrías convertir en personajes de una leyenda?"}'),
(5,2,'actividad','{"materiales":["Papel","Lápiz","Colores (opcional)"],"instrucciones":["Lee la leyenda del abuelo Aurelio y escríbela con tus propias palabras (no copies, cuenta la historia como si se la contaras a un amigo).","Luego crea tu propia versión: cambia uno de los personajes o agrega un nuevo elemento a la historia.","Tu leyenda debe tener: título, presentación de los personajes, el problema o conflicto y cómo termina.","Incluye al menos un elemento de la naturaleza de Moquegua: el volcán, el río, el desierto, las terrazas.","Ilustra tu leyenda con un dibujo."],"minutos":20}'),
(5,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 5"}'),
(5,4,'entregable','{"consigna":"Muestra tu leyenda reescrita o creada, con su ilustración.","formatos":["cuento_ilustrado","mural_digital"],"instrucciones":"Fotografía tu texto e ilustración. Máx 5 MB."}');

-- Módulo 6: Noticias de mi barrio
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(6,1,'historia','{"narrativa":"La profesora Luciana tiene una idea emocionante para el quinto grado A de la GUE Mariscal Nieto: crear el primer periódico escolar del colegio, llamado Voz del Mariscal. Pero para eso necesita reporteros estudiantiles que sepan escribir noticias reales sobre lo que pasa en el barrio, en la escuela y en la ciudad. Miguel, que siempre está enterado de todo lo que ocurre en su barrio, es el primero en levantar la mano. La profesora le explica que una noticia no es cualquier texto: debe responder seis preguntas fundamentales llamadas las 6W en inglés (¿Quién? ¿Qué? ¿Cuándo? ¿Dónde? ¿Por qué? ¿Cómo?). Además debe tener un título llamativo, un primer párrafo que resuma todo y un cuerpo con los detalles. Miguel se da cuenta de que escribir noticias es como ser un detective que recoge pistas y las organiza para contar la verdad.","pregunta_disparadora":"¿Cuál es el evento más importante que ha ocurrido recientemente en tu barrio o escuela que merezca ser una noticia?"}'),
(6,2,'actividad','{"materiales":["Papel","Lápiz o lapicero"],"instrucciones":["Piensa en un evento real o imaginario que ocurrió en tu barrio, escuela o comunidad.","Responde las 6 preguntas: ¿Quién? ¿Qué pasó? ¿Cuándo? ¿Dónde? ¿Por qué? ¿Cómo?","Escribe un título llamativo para tu noticia (máximo 10 palabras).","Escribe el primer párrafo resumiendo los puntos más importantes en 2-3 oraciones.","Escribe el cuerpo de la noticia con más detalles en 2 párrafos adicionales.","Agrega un cierre con una conclusión o dato adicional."],"minutos":20}'),
(6,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 6"}'),
(6,4,'entregable','{"consigna":"Comparte la noticia que redactaste con su título y los 6 párrafos estructurados.","formatos":["ficha","cuento_ilustrado"],"instrucciones":"Fotografía tu noticia escrita. La letra debe ser legible. Máx 5 MB."}');


-- Módulo 7: Colores de Moquegua
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(7,1,'historia','{"narrativa":"Víctor Apaza es un pintor moqueguano que lleva 20 años capturando los paisajes de su región en lienzos y acuarelas. Sus cuadros muestran el desierto color ocre, las lomas verdes de invierno, el cielo azul intenso y las flores amarillas del huarango. Un día, una joven artista llamada Camila le pregunta cómo logra esos colores tan exactos. Víctor le revela su secreto: en realidad nunca usa directamente los colores del tubo. Siempre mezcla los colores primarios (rojo, azul y amarillo) para crear los colores exactos que ve en la naturaleza. El anaranjado del atardecer sobre el volcán Ubinas se logra mezclando rojo y amarillo. El morado de la flor de papa se logra con rojo y azul. Camila comprende que las mezclas de colores son como una receta de cocina: cambiando las proporciones se obtienen resultados completamente diferentes.","pregunta_disparadora":"¿Qué colores ves en el paisaje de tu comunidad o barrio que quisieras capturar en un dibujo?"}'),
(7,2,'actividad','{"materiales":["Pinturas o crayolas de colores primarios: rojo, azul y amarillo","Papel blanco","Pincel o palito para mezclar (si usa pintura)"],"instrucciones":["Mezcla rojo + amarillo para obtener naranja. Pinta una muestra en tu papel.","Mezcla azul + amarillo para obtener verde. Pinta una muestra.","Mezcla rojo + azul para obtener violeta. Pinta una muestra.","Ahora intenta mezclar con diferentes proporciones: más rojo que amarillo, o más azul que rojo. Observa cómo cambia el color.","Crea una paleta de 6 colores y nómbralos con colores de la naturaleza moqueguana: arena del desierto, cielo andino, flor de papa, chuño, volcán, laguna."],"minutos":20}'),
(7,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 7"}'),
(7,4,'entregable','{"consigna":"Muestra tu paleta de colores mezclados con los nombres inspirados en la naturaleza de Moquegua.","formatos":["dibujo_cientifico","mural_digital"],"instrucciones":"Fotografía tu paleta de colores con buena iluminación para que se vean las mezclas. Máx 5 MB."}');

-- Módulo 8: Mural del barrio
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(8,1,'historia','{"narrativa":"La junta vecinal del barrio San Antonio de Moquegua reunió a todos los vecinos un domingo por la tarde. El presidente de la junta, don Héctor, anunció que habían conseguido el permiso para pintar un mural en la pared más grande de la plaza del barrio. La idea era que el mural representara la identidad del barrio: sus tradiciones, su gente, su historia y sus sueños para el futuro. Pero nadie sabía por dónde empezar. Fue entonces cuando Valentina, una estudiante de sexto grado, propuso que los niños del barrio fueran los diseñadores del mural. Argumentó que ellos conocen el barrio desde adentro, desde los juegos en la calle, desde las conversaciones con los abuelos, desde los aromas del mercado y de las cocinas de las casas. Don Héctor estuvo de acuerdo y le encargó a Valentina organizar el proceso creativo.","pregunta_disparadora":"Si pudieras diseñar un mural para el lugar más importante de tu barrio, ¿qué elementos incluirías para representar tu comunidad?"}'),
(8,2,'actividad','{"materiales":["Papel grande (puede ser unión de varias hojas)","Lápices y colores","Cinta adhesiva para unir papeles"],"instrucciones":["Reúnete con 2 o 3 compañeros o trabaja solo. Decidan el tema del mural: identidad del barrio, naturaleza de Moquegua, sueños del futuro, etc.","Dividan el espacio del papel en secciones y asigna cada sección a un integrante (o planifica las secciones tú solo).","Boceta primero con lápiz los elementos principales: personas, paisajes, símbolos locales.","Colorea con los colores que representen mejor tu comunidad.","Escribe el título del mural en la parte superior y los nombres de los autores en la parte inferior."],"minutos":25}'),
(8,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 8"}'),
(8,4,'entregable','{"consigna":"Muestra tu diseño de mural colectivo con su título y los nombres de los autores.","formatos":["mural_digital","dibujo_cientifico"],"instrucciones":"Toma una foto del mural completo. Si es en papel grande, asegúrate de capturar toda la imagen. Máx 5 MB."}');

-- Módulo 9: Tejidos y patrones
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(9,1,'historia','{"narrativa":"Cada sábado en el mercado central de Moquegua, las manos de doña Esperanza se mueven sin descanso sobre su telar de madera. Sus dedos conocen de memoria los patrones que le enseñó su madre, y que su madre aprendió de su abuela, en una cadena de conocimiento que se remonta a los tiempos precolombinos. Los tejidos andinos no son solo decorativos: cada patrón tiene un significado. El rombo representa el ojo que todo lo ve y protege. La serpiente en zigzag simboliza el río y el agua. Los cuadrados alternados representan los campos de cultivo vistos desde las montañas. Cuando una turista le pregunta a doña Esperanza cuánto tiempo le toma memorizar un patrón nuevo, ella sonríe y responde: Los patrones no se memorizan, se sienten. Pero también se pueden dibujar en papel cuadriculado para aprenderlos mejor.","pregunta_disparadora":"¿Has visto patrones en la ropa, en los pisos o en los muros de tu barrio? ¿Puedes describir cómo están formados?"}'),
(9,2,'actividad','{"materiales":["Papel cuadriculado (o papel blanco con cuadrícula dibujada)","Lápices de colores (mínimo 3 colores)","Regla"],"instrucciones":["Observa el patrón del rombo: dibuja un rombo de 4x4 cuadrados y repítelo en fila, alternando colores.","Dibuja el patrón de la serpiente: una línea en zigzag de 2 cuadrados de alto que se repite horizontalmente.","Crea tu propio patrón: elige una figura geométrica simple (triángulo, cuadrado, L) y repítela para formar un patrón de al menos 3 repeticiones.","Colorea cada patrón con 2 colores que combinen bien.","Debajo de cada patrón escribe su nombre o inventa uno inspirado en la naturaleza de Moquegua."],"minutos":20}'),
(9,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 9"}'),
(9,4,'entregable','{"consigna":"Muestra tus tres patrones dibujados en papel cuadriculado con sus nombres.","formatos":["dibujo_cientifico","mural_digital"],"instrucciones":"Fotografía tu hoja de patrones con buena luz. Máx 5 MB."}');

-- Módulo 10: Puentes con papel
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(10,1,'historia','{"narrativa":"La comunidad de Elena, en las afueras de Moquegua, tiene un problema serio: el pequeño puente de madera que cruza el canal de riego está deteriorado. Las lluvias de los últimos años han debilitado sus bases y ya no es seguro para que los niños lo crucen camino a la escuela. Elena, que quiere ser ingeniera cuando sea grande, observa el puente con ojos distintos a los demás. No ve solo madera vieja: ve vigas, tensiones, el peso del agua debajo y el peso de las personas encima. Su profesor de ciencias le enseñó que los ingenieros prueban sus diseños con modelos a pequeña escala antes de construir el puente real. Así pueden descubrir los puntos débiles sin arriesgar vidas. Elena propone a su clase construir mini-puentes de papel para aprender qué formas estructurales son más resistentes.","pregunta_disparadora":"¿Qué forma crees que haría un puente de papel más resistente: plano, con arcos, o con vigas triangulares? ¿Por qué?"}'),
(10,2,'actividad','{"materiales":["5 hojas de papel bond A4","Cinta adhesiva o clips","Dos libros o cajas del mismo tamaño para los apoyos","Monedas o pesas pequeñas para la prueba de carga"],"instrucciones":["Coloca los dos libros separados por 15 centímetros. Este será el vano que debe cruzar tu puente.","Construye un primer puente simplemente poniendo una hoja plana sobre los libros. Prueba cuántas monedas aguanta.","Construye un segundo puente doblando la hoja en pliegues como acordeón (tablilla corrugada). Prueba el peso.","Construye un tercer puente usando varias hojas enrolladas como columnas. Prueba el peso.","Registra en una tabla: Diseño / Peso máximo soportado / Observaciones. Compara los tres diseños."],"minutos":25}'),
(10,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 10"}'),
(10,4,'entregable','{"consigna":"Muestra una foto de tu puente de papel más resistente junto con la tabla de resultados de tus pruebas.","formatos":["prototipo","dibujo_cientifico"],"instrucciones":"Fotografía tu mejor puente con el peso que soportó. Máx 5 MB."}');

-- Módulo 11: Filtro de agua
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(11,1,'historia','{"narrativa":"En la comunidad de Santa Rosa, a las afueras de Moquegua, el pozo del que todos se abastecen ha comenzado a tener agua turbia. No huele mal pero se ve sucia, con pequeñas partículas flotando. El señor Mamani, el fontanero del pueblo, explica que son sedimentos de la tierra que entran cuando llueve fuerte en las alturas. El agua no es necesariamente peligrosa, pero nadie quiere beberla así. Julio, un estudiante de sexto grado apasionado por las ciencias, recuerda que en su libro de ciencias leyó que las personas sin acceso a tecnología moderna pueden purificar el agua usando materiales naturales: arena, piedras y carbón vegetal. Propone construir un filtro casero como proyecto de clase para demostrar que la ciencia puede resolver problemas reales de la comunidad.","pregunta_disparadora":"¿Qué materiales naturales crees que podrían atrapar las partículas sucias del agua y por qué?"}'),
(11,2,'actividad','{"materiales":["Botella de plástico de 1.5L cortada por la mitad","Algodón o tela de tela","Arena fina lavada","Piedras pequeñas limpias","Agua turbia (con tierra o tierra disuelta)","Recipiente limpio para recoger el agua filtrada"],"instrucciones":["Voltea la parte superior de la botella boca abajo (como embudo) y ponla sobre la parte inferior.","Coloca una capa de algodón en el fondo del embudo para que no se escape la arena.","Agrega 3 cm de arena fina encima del algodón.","Agrega 3 cm de piedras pequeñas encima de la arena.","Vierte lentamente el agua turbia desde arriba y observa cómo el agua va saliendo más clara por abajo.","Anota: ¿Qué tan turbia entró? ¿Qué tan clara salió? ¿Cuánto tiempo tardó en filtrarse?"],"minutos":25}'),
(11,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 11"}'),
(11,4,'entregable','{"consigna":"Muestra una foto de tu filtro de agua armado y compara el agua antes y después del filtrado.","formatos":["prototipo","dibujo_cientifico"],"instrucciones":"Fotografía tu filtro con los vasos de agua antes y después. Máx 5 MB."}');

-- Módulo 12: Torre sísmica
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(12,1,'historia','{"narrativa":"Moquegua es una de las regiones sísmicas más activas del Perú. En el año 2001, un terremoto de magnitud 8.4 causó grandes daños en la ciudad y en los pueblos cercanos. El ingeniero Carlos Llerena, que participó en la reconstrucción de la ciudad, explica que los edificios que mejor resistieron el sismo tenían algo en común: usaban formas geométricas triangulares en su estructura interna. El triángulo, a diferencia del cuadrado, no se deforma cuando se aplica fuerza lateral. Esta es la razón por la que los ingenieros usan vigas triangulares, llamadas cerchas, en puentes y edificios. La estudiante Andrea, después de escuchar al ingeniero Llerena en una charla en su escuela, quiere comprobarlo por sí misma. Su plan: construir dos torres con palitos, una con cuadrados y otra con triángulos, y sacudirlas para ver cuál resiste mejor.","pregunta_disparadora":"¿Por qué crees que la forma triangular podría ser más resistente que la cuadrada ante los movimientos del suelo?"}'),
(12,2,'actividad','{"materiales":["Palitos de helado o fósforos (20 unidades)","Plastilina o masilla para unir","Regla","Superficie plana para probar la resistencia"],"instrucciones":["Construye una torre cuadrada: une 4 palitos formando un cuadrado en la base, luego añade otra capa de cuadrado encima (2 pisos). Une con plastilina en las esquinas.","Construye una torre triangular: une 3 palitos formando un triángulo en la base, luego agrega triángulos encima para subir 2 pisos.","Prueba la resistencia: sacude suavemente la superficie donde están las torres de lado a lado, simulando un sismo.","Observa: ¿cuál se deforma más? ¿Cuál colapsa primero?","Dibuja ambas torres y escribe tus conclusiones sobre cuál es más resistente y por qué."],"minutos":25}'),
(12,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 12"}'),
(12,4,'entregable','{"consigna":"Muestra una foto de tus dos torres (cuadrada y triangular) con tus conclusiones escritas sobre cuál fue más resistente.","formatos":["prototipo","dibujo_cientifico"],"instrucciones":"Fotografía las dos torres juntas. Máx 5 MB."}');

-- Módulo 13: My Moquegua
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(13,1,'historia','{"narrativa":"Tim is a tourist from Canada who just arrived in Moquegua for the first time. He has heard that Moquegua is famous for its wine, its beautiful churches and its delicious food. But when he arrives at the bus station, he feels lost. Nobody speaks English and he does not speak Spanish. He looks at the signs but cannot understand them. He wants to find the main square, eat something typical and visit the regional museum, but he does not know how to ask. Fortunately, a group of students from GUE Mariscal Nieto is doing a school project about tourism. They decide to help Tim by making a bilingual welcome card that explains the most important things about Moquegua in both English and Spanish. Tim is very happy and thanks them in English. The students realize that knowing basic English is not just a school subject: it is a real tool to connect with the world.","pregunta_disparadora":"If a tourist arrived in your neighborhood and did not speak Spanish, what 5 things would you want to tell them about your community?"}'),
(13,2,'actividad','{"materiales":["Tarjeta o papel grueso (puede ser media hoja A4 doblada)","Lápices de colores","Lapicero"],"instrucciones":["En la parte frontal de tu tarjeta: escribe WELCOME TO MOQUEGUA en grande y decórala con colores.","En el interior izquierdo: escribe 5 frases en inglés sobre Moquegua. Ejemplo: Moquegua is famous for its wine. / The weather is sunny and warm.","En el interior derecho: escribe la traducción en español de cada frase.","En la parte trasera: dibuja un mapa simple con la plaza principal, el mercado y el museo.","Firma tu tarjeta con tu nombre en inglés: Made by [tu nombre]."],"minutos":20}'),
(13,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 13"}'),
(13,4,'entregable','{"consigna":"Share a photo of your bilingual welcome card for tourists visiting Moquegua.","formatos":["ficha","mural_digital"],"instrucciones":"Take a clear photo of both sides of your card. Max 5 MB."}');

-- Módulo 14: The market
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(14,1,'historia','{"narrativa":"Sara is a student who loves learning English. One Saturday morning, she goes to the Moquegua central market with her mother. As they walk through the stalls, Sara imagines that the market is an English classroom. She starts practicing: How much is this? she asks a vendor in English, pointing at some tomatoes. The vendor, confused, answers in Spanish. Sara laughs and translates for her mother. Then she invents a game: she will practice a complete market conversation with her classmate Diego, taking turns being the buyer and the seller. Sara will be the buyer and Diego will be the seller. They decide to use real prices from the Moquegua market to make the practice as realistic as possible. Their teacher told them that the best way to learn a language is to use it in real situations, even if you make mistakes.","pregunta_disparadora":"What things do you usually buy at the market or store? How would you say them in English?"}'),
(14,2,'actividad','{"materiales":["Papel","Lápiz"],"instrucciones":["Learn these market phrases: How much is this? / It costs... soles. / I will take one kilo, please. / Here is your change. / Thank you!","With a partner (or alone, writing both roles), create a dialogue of at least 6 lines between a buyer and a seller at the Moquegua market.","Include at least 2 products with prices in soles.","Practice saying the dialogue out loud.","Write the dialogue on paper with the two characters clearly labeled: Buyer (Comprador) and Seller (Vendedor)."],"minutos":20}'),
(14,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 14"}'),
(14,4,'entregable','{"consigna":"Share a photo of your written market dialogue in English with at least 6 lines.","formatos":["ficha","cuento_ilustrado"],"instrucciones":"Write the dialogue clearly and take a photo. Max 5 MB."}');

-- Módulo 15: Nature around us
INSERT INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(15,1,'historia','{"narrativa":"Every morning, when Lucia walks to school in Moquegua, she notices the colors around her. The sky is a deep blue (azul) in the dry season. The desert sand is golden yellow (amarillo dorado). The cactus plants are dark green (verde oscuro). In winter, when it rains in the highlands, the hills turn bright green (verde brillante). The flowers of the huarango tree are yellow (amarillo). The volcanic rock on the mountains is dark gray (gris oscuro). Lucia thinks that nature is like a giant colorful painting. Her English teacher, Miss Rosa, tells the class that learning colors in English is one of the most useful vocabulary sets because colors appear everywhere: in clothes, in food, in art, in nature. Miss Rosa gives the class a challenge: create a bilingual color chart inspired by the nature of Moquegua, connecting each English color word with a real element from their region.","pregunta_disparadora":"What is your favorite color in nature around your home or school? How do you think it is called in English?"}'),
(15,2,'actividad','{"materiales":["Papel blanco o ficha","Lápices de colores","Lapicero"],"instrucciones":["Draw a table with 3 columns: Color in English / Color in Spanish / Example from nature in Moquegua.","Fill in at least 8 colors: red, blue, yellow, green, orange, purple, brown, white, gray, black.","For each color, write an example from Moquegua nature. Example: red = red chili pepper / ají rojo.","Color each row with the corresponding color.","At the bottom, write your favorite color in a sentence: My favorite color is ___ because ___."],"minutos":20}'),
(15,3,'quiz','{"info":"Ver tabla quiz_preguntas para modulo 15"}'),
(15,4,'entregable','{"consigna":"Share a photo of your bilingual color chart with examples from the nature of Moquegua.","formatos":["ficha","dibujo_cientifico"],"instrucciones":"Take a clear photo of your completed color chart. Max 5 MB."}');


-- ============================================================
-- SEED: quiz_preguntas (3 preguntas por módulo = 45 total)
-- Usamos subconsultas para obtener el paso_id correcto
-- ============================================================

-- Módulo 1: El mercado de Moquegua
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='El mercado de Moquegua' AND mp.tipo='quiz'),
 '¿Para qué sirve un gráfico de barras?',
 '[{"texto":"Para dibujar figuras geométricas","correcta":false},{"texto":"Para representar y comparar datos de forma visual","correcta":true},{"texto":"Para calcular el área de un terreno","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='El mercado de Moquegua' AND mp.tipo='quiz'),
 'Si el kilo de papa cuesta S/3 en un puesto y S/5 en otro, ¿cuánto más caro es el segundo?',
 '[{"texto":"S/1","correcta":false},{"texto":"S/2","correcta":true},{"texto":"S/3","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='El mercado de Moquegua' AND mp.tipo='quiz'),
 'En una tabla de precios, ¿qué representan las filas?',
 '[{"texto":"Los tipos de gráficos posibles","correcta":false},{"texto":"Cada producto o lugar con su precio correspondiente","correcta":true},{"texto":"Los colores del gráfico","correcta":false}]', 3);

-- Módulo 2: Construyendo terrazas
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Construyendo terrazas' AND mp.tipo='quiz'),
 '¿Qué fracción representa la mitad de un terreno?',
 '[{"texto":"1/4","correcta":false},{"texto":"1/3","correcta":false},{"texto":"1/2","correcta":true}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Construyendo terrazas' AND mp.tipo='quiz'),
 'Si una terraza mide 8 metros y se divide en 4 partes iguales, ¿cuánto mide cada parte?',
 '[{"texto":"4 metros","correcta":false},{"texto":"2 metros","correcta":true},{"texto":"3 metros","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Construyendo terrazas' AND mp.tipo='quiz'),
 '¿Cuál fracción es mayor: 1/2 o 1/4?',
 '[{"texto":"1/4","correcta":false},{"texto":"Son iguales","correcta":false},{"texto":"1/2","correcta":true}]', 3);

-- Módulo 3: El aguaymanto y los kilos
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='El aguaymanto y los kilos' AND mp.tipo='quiz'),
 'Doña Carmen tiene 15 kilos de aguaymanto a S/8 el kilo. ¿Cuánto gana en total?',
 '[{"texto":"S/100","correcta":false},{"texto":"S/120","correcta":true},{"texto":"S/108","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='El aguaymanto y los kilos' AND mp.tipo='quiz'),
 'Si el precio sube a S/12 por kilo, ¿cuánto gana doña Carmen por 15 kilos?',
 '[{"texto":"S/150","correcta":false},{"texto":"S/180","correcta":true},{"texto":"S/160","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='El aguaymanto y los kilos' AND mp.tipo='quiz'),
 '¿Cuánto más gana doña Carmen vendiendo a Lima que en el mercado local?',
 '[{"texto":"S/50","correcta":false},{"texto":"S/60","correcta":true},{"texto":"S/45","correcta":false}]', 3);

-- Módulo 4: El río Moquegua habla
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='El río Moquegua habla' AND mp.tipo='quiz'),
 '¿Cuál es el propósito principal de una carta narrativa?',
 '[{"texto":"Hacer una lista de compras","correcta":false},{"texto":"Contar una historia desde el punto de vista del narrador","correcta":true},{"texto":"Describir un proceso científico","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='El río Moquegua habla' AND mp.tipo='quiz'),
 '¿Qué elemento NO debe faltar en una carta?',
 '[{"texto":"Fórmulas matemáticas","correcta":false},{"texto":"Saludo, cuerpo y despedida","correcta":true},{"texto":"Diagramas y gráficos","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='El río Moquegua habla' AND mp.tipo='quiz'),
 '¿Qué significa escribir desde el punto de vista del río?',
 '[{"texto":"Escribir sobre peces y plantas","correcta":false},{"texto":"Hablar como si fueras el río, usando yo y mis sentimientos","correcta":true},{"texto":"Copiar información de un libro sobre ríos","correcta":false}]', 3);

-- Módulo 5: Leyendas de Torata
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Leyendas de Torata' AND mp.tipo='quiz'),
 '¿Cuál es la característica principal de una leyenda?',
 '[{"texto":"Es completamente inventada sin ninguna base real","correcta":false},{"texto":"Mezcla elementos reales con sobrenaturales y explica fenómenos naturales","correcta":true},{"texto":"Es un texto científico con datos comprobados","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Leyendas de Torata' AND mp.tipo='quiz'),
 'En la leyenda del Ticsani, ¿en qué fue convertido el guerrero?',
 '[{"texto":"En una laguna","correcta":false},{"texto":"En un árbol","correcta":false},{"texto":"En un volcán","correcta":true}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Leyendas de Torata' AND mp.tipo='quiz'),
 '¿Por qué es importante reescribir las leyendas con nuestras propias palabras?',
 '[{"texto":"Para cambiar la historia completamente","correcta":false},{"texto":"Para mantenerlas vivas y transmitirlas a las nuevas generaciones","correcta":true},{"texto":"Porque el original está equivocado","correcta":false}]', 3);

-- Módulo 6: Noticias de mi barrio
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Noticias de mi barrio' AND mp.tipo='quiz'),
 '¿Cuántas preguntas básicas debe responder una buena noticia?',
 '[{"texto":"3 preguntas","correcta":false},{"texto":"6 preguntas (¿Quién? ¿Qué? ¿Cuándo? ¿Dónde? ¿Por qué? ¿Cómo?)","correcta":true},{"texto":"10 preguntas","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Noticias de mi barrio' AND mp.tipo='quiz'),
 '¿Qué parte de la noticia resume los puntos más importantes?',
 '[{"texto":"El título únicamente","correcta":false},{"texto":"El primer párrafo o lead","correcta":true},{"texto":"La conclusión al final","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Noticias de mi barrio' AND mp.tipo='quiz'),
 '¿Qué diferencia a una noticia de una opinión?',
 '[{"texto":"La noticia es más larga","correcta":false},{"texto":"La noticia narra hechos reales y verificables","correcta":true},{"texto":"La noticia tiene más dibujos","correcta":false}]', 3);

-- Módulo 7: Colores de Moquegua
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Colores de Moquegua' AND mp.tipo='quiz'),
 '¿Cuáles son los tres colores primarios?',
 '[{"texto":"Verde, naranja y morado","correcta":false},{"texto":"Rojo, azul y amarillo","correcta":true},{"texto":"Blanco, negro y gris","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Colores de Moquegua' AND mp.tipo='quiz'),
 '¿Qué colores se mezclan para obtener el color naranja?',
 '[{"texto":"Azul y amarillo","correcta":false},{"texto":"Rojo y azul","correcta":false},{"texto":"Rojo y amarillo","correcta":true}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Colores de Moquegua' AND mp.tipo='quiz'),
 'Si mezclas más rojo que azul, ¿qué tono de morado obtienes?',
 '[{"texto":"Un morado más frío y azulado","correcta":false},{"texto":"Un morado más cálido, tirando a rojizo","correcta":true},{"texto":"Exactamente el mismo morado","correcta":false}]', 3);

-- Módulo 8: Mural del barrio
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Mural del barrio' AND mp.tipo='quiz'),
 '¿Cuál es la principal diferencia entre un mural y un cuadro?',
 '[{"texto":"El mural siempre es más pequeño","correcta":false},{"texto":"El mural se crea en espacios públicos y es visto por toda la comunidad","correcta":true},{"texto":"Los murales no pueden tener colores","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Mural del barrio' AND mp.tipo='quiz'),
 'En el arte colectivo, ¿qué es fundamental para que el trabajo quede unido?',
 '[{"texto":"Que solo una persona dibuje todo","correcta":false},{"texto":"Planificar juntos los elementos, colores y estilo antes de empezar","correcta":true},{"texto":"Usar únicamente colores oscuros","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Mural del barrio' AND mp.tipo='quiz'),
 '¿Qué debe mostrar un mural que representa la identidad de una comunidad?',
 '[{"texto":"Solo los edificios modernos","correcta":false},{"texto":"Las tradiciones, la gente, la historia y los sueños de la comunidad","correcta":true},{"texto":"Únicamente animales y plantas","correcta":false}]', 3);

-- Módulo 9: Tejidos y patrones
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Tejidos y patrones' AND mp.tipo='quiz'),
 '¿Qué es un patrón en el arte?',
 '[{"texto":"Un error en el diseño","correcta":false},{"texto":"Una figura o motivo que se repite de forma ordenada","correcta":true},{"texto":"Un tipo especial de pintura","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Tejidos y patrones' AND mp.tipo='quiz'),
 'En los tejidos andinos, ¿qué símbolo representa el agua y los ríos?',
 '[{"texto":"El rombo","correcta":false},{"texto":"El círculo","correcta":false},{"texto":"La serpiente o zigzag","correcta":true}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Tejidos y patrones' AND mp.tipo='quiz'),
 '¿Cuántas repeticiones mínimas necesita un patrón para que se note que es una secuencia?',
 '[{"texto":"Al menos 2 o 3 repeticiones","correcta":true},{"texto":"Solo 1 es suficiente","correcta":false},{"texto":"Mínimo 10 repeticiones","correcta":false}]', 3);

-- Módulo 10: Puentes con papel
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Puentes con papel' AND mp.tipo='quiz'),
 '¿Por qué los ingenieros construyen modelos a pequeña escala antes del puente real?',
 '[{"texto":"Porque es más barato que contratar obreros","correcta":false},{"texto":"Para probar el diseño y encontrar puntos débiles sin arriesgar vidas","correcta":true},{"texto":"Por tradición histórica","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Puentes con papel' AND mp.tipo='quiz'),
 '¿Qué forma estructural hace que un puente de papel sea más resistente?',
 '[{"texto":"Completamente plano sin dobleces","correcta":false},{"texto":"Enrollado o corrugado en pliegues como acordeón","correcta":true},{"texto":"Doblado en una sola vez por la mitad","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Puentes con papel' AND mp.tipo='quiz'),
 '¿Cómo se llama la fuerza que aplica el peso de las personas sobre un puente?',
 '[{"texto":"Fuerza de tensión","correcta":false},{"texto":"Carga o fuerza de compresión","correcta":true},{"texto":"Fuerza magnética","correcta":false}]', 3);

-- Módulo 11: Filtro de agua
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Filtro de agua' AND mp.tipo='quiz'),
 '¿Cuál es la función de la arena en un filtro de agua casero?',
 '[{"texto":"Dar sabor al agua","correcta":false},{"texto":"Atrapar partículas finas de suciedad y sedimentos","correcta":true},{"texto":"Hacer que el agua fluya más rápido","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Filtro de agua' AND mp.tipo='quiz'),
 '¿En qué orden deben colocarse los materiales en el filtro (de abajo hacia arriba)?',
 '[{"texto":"Piedras, arena, algodón","correcta":false},{"texto":"Algodón, arena, piedras","correcta":true},{"texto":"Arena, piedras, algodón","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Filtro de agua' AND mp.tipo='quiz'),
 '¿El filtro casero hace que el agua sea completamente segura para beber?',
 '[{"texto":"Sí, elimina todos los gérmenes y bacterias","correcta":false},{"texto":"No, solo elimina partículas visibles; necesita hervirse también","correcta":true},{"texto":"Solo funciona con agua de mar","correcta":false}]', 3);

-- Módulo 12: Torre sísmica
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Torre sísmica' AND mp.tipo='quiz'),
 '¿Por qué el triángulo es más resistente que el cuadrado ante fuerzas laterales?',
 '[{"texto":"Porque tiene más lados","correcta":false},{"texto":"Porque no se puede deformar sin cambiar el largo de sus lados","correcta":true},{"texto":"Porque es más pequeño","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Torre sísmica' AND mp.tipo='quiz'),
 '¿Cómo se llama la estructura triangular que usan los ingenieros en puentes y techos?',
 '[{"texto":"Columna","correcta":false},{"texto":"Cercha o viga triangulada","correcta":true},{"texto":"Arco","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Torre sísmica' AND mp.tipo='quiz'),
 '¿En qué año ocurrió el terremoto de magnitud 8.4 que afectó a Moquegua?',
 '[{"texto":"1970","correcta":false},{"texto":"2001","correcta":true},{"texto":"2010","correcta":false}]', 3);

-- Módulo 13: My Moquegua
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='My Moquegua' AND mp.tipo='quiz'),
 'How do you say "Bienvenido" in English?',
 '[{"texto":"Goodbye","correcta":false},{"texto":"Welcome","correcta":true},{"texto":"Thank you","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='My Moquegua' AND mp.tipo='quiz'),
 'Which sentence correctly introduces a city?',
 '[{"texto":"Moquegua is famous for its wine and sunny weather.","correcta":true},{"texto":"Moquegua are famous for its wine.","correcta":false},{"texto":"Moquegua am a city in Peru.","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='My Moquegua' AND mp.tipo='quiz'),
 'What does "bilingual" mean?',
 '[{"texto":"Speaking only one language","correcta":false},{"texto":"Written in two languages","correcta":true},{"texto":"A type of map","correcta":false}]', 3);

-- Módulo 14: The market
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='The market' AND mp.tipo='quiz'),
 'How do you ask for the price of something in English?',
 '[{"texto":"Where is the market?","correcta":false},{"texto":"How much is this?","correcta":true},{"texto":"What time is it?","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='The market' AND mp.tipo='quiz'),
 'A vendor says "It costs 5 soles." What does "costs" mean?',
 '[{"texto":"Cuesta / tiene un precio de","correcta":true},{"texto":"Pesa","correcta":false},{"texto":"Mide","correcta":false}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='The market' AND mp.tipo='quiz'),
 'How do you say "Aquí está tu cambio" in English?',
 '[{"texto":"Here is your change.","correcta":true},{"texto":"Here is your charge.","correcta":false},{"texto":"Here is your check.","correcta":false}]', 3);

-- Módulo 15: Nature around us
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Nature around us' AND mp.tipo='quiz'),
 'What color is the sky on a sunny day in Moquegua?',
 '[{"texto":"Green","correcta":false},{"texto":"Blue","correcta":true},{"texto":"Red","correcta":false}]', 1),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Nature around us' AND mp.tipo='quiz'),
 'How do you say "amarillo" in English?',
 '[{"texto":"Orange","correcta":false},{"texto":"Brown","correcta":false},{"texto":"Yellow","correcta":true}]', 2),
((SELECT mp.id FROM modulo_pasos mp JOIN modulos m ON m.id=mp.modulo_id WHERE m.titulo='Nature around us' AND mp.tipo='quiz'),
 'Which sentence uses colors in English correctly?',
 '[{"texto":"The cactus is green and the sand is golden yellow.","correcta":true},{"texto":"The cactus is verde and the sand is yellow.","correcta":false},{"texto":"The cactus is blue and the sand is green.","correcta":false}]', 3);

-- ============================================================
-- aula_modulos: asignar los 15 módulos al aula 1
-- ============================================================

INSERT INTO aula_modulos (aula_id, modulo_id, fecha_planificada, asignado_por) VALUES
(1, 1,  '2026-04-14', 1),
(1, 2,  '2026-04-21', 1),
(1, 3,  '2026-04-28', 1),
(1, 4,  '2026-05-05', 1),
(1, 5,  '2026-05-12', 1),
(1, 6,  '2026-05-19', 1),
(1, 7,  '2026-05-26', 1),
(1, 8,  '2026-06-02', 1),
(1, 9,  '2026-06-09', 1),
(1, 10, '2026-06-16', 1),
(1, 11, '2026-06-23', 1),
(1, 12, '2026-06-30', 1),
(1, 13, '2026-07-07', 1),
(1, 14, '2026-07-14', 1),
(1, 15, '2026-07-21', 1);

