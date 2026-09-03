-- ============================================================
-- INNOVA-STEAM — Seed Data (Demo)
-- Contraseña de todas las cuentas demo: password
-- Hash bcrypt de 'password' (costo 10)
-- ============================================================
-- Ejecución: mysql -u root innovasteam < seed_data.sql
-- Es seguro ejecutar múltiples veces (INSERT IGNORE)
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ── Colegios ─────────────────────────────────────────────────
-- Las columnas son las de schema.sql: distrito, ugel_codigo y director.
-- Antes se insertaban 'provincia', 'region' y 'director_nombre', que no
-- existen; el archivo entero moría aquí y nada de lo que sigue llegaba
-- a cargarse nunca.
INSERT IGNORE INTO colegios (id, nombre, distrito, ugel_codigo, director, telefono, activo) VALUES
(1, 'GUE Mariscal Nieto',            'Moquegua', 'Mariscal Nieto', 'Prof. Roberto Cárdenas',  '053-462001', 1),
(2, 'IE San Antonio de Abad',        'Ilo',      'Ilo',            'Prof. Carmen Villanueva', '053-781234', 1),
(3, 'IE Jorge Basadre',              'Torata',   'Mariscal Nieto', 'Prof. Miguel Ramos',      '053-451122', 1),
(4, 'IE Nuestra Señora de Fátima',   'Moquegua', 'Mariscal Nieto', 'Prof. Ana Huanca',        '053-463300', 1),
(5, 'IE Simón Bolívar',              'Samegua',  'Mariscal Nieto', 'Prof. Luis Quispe',       '053-460888', 1);

-- ── Usuarios ─────────────────────────────────────────────────
-- Contraseña: password  →  hash bcrypt costo 10
INSERT IGNORE INTO usuarios (id, nombre, apellido, email, password_hash, rol, colegio_id, codigo_acceso, activo) VALUES
-- Administrador global
(1, 'Carlos',   'Administrador', 'admin@innovasteam.edu.pe',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin', NULL, NULL, 1),

-- Directores de colegio
(2, 'Roberto',  'Cárdenas',      'director1@innovasteam.edu.pe',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin_colegio', 1, NULL, 1),
(3, 'Carmen',   'Villanueva',    'director2@innovasteam.edu.pe',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin_colegio', 2, NULL, 1),

-- Docentes
(4, 'María',    'Torres',        'docente@innovasteam.edu.pe',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'docente', 1, NULL, 1),
(5, 'José',     'Mamani',        'jose.mamani@innovasteam.edu.pe',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'docente', 1, NULL, 1),
(6, 'Sandra',   'Cruz',          'sandra.cruz@innovasteam.edu.pe',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'docente', 2, NULL, 1),

-- Practicantes
-- schema.sql ya crea un practicante con practicante@innovasteam.edu.pe.
-- Repetir ese email hacía que INSERT IGNORE descartara esta fila en
-- silencio, y las dos filas de practicante_aula que la referencian
-- quedaban huérfanas.
(7, 'Diego',    'Flores',        'practicante2@innovasteam.edu.pe',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'practicante', 1, NULL, 1),
(8, 'Valeria',  'Quispe',        'valeria.quispe@innovasteam.edu.pe',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'practicante', 2, NULL, 1),

-- Estudiantes (código de acceso para login)
-- El código EST-001 ya lo usa el estudiante que crea schema.sql. Con el
-- código repetido esta fila se descartaba y dejaba huérfanas su matrícula
-- y sus cuatro filas de progreso. EST-016 queda fuera del rango 002-015
-- que ocupan el resto de estudiantes de este archivo.
(10, 'Lucía',   'Quispe Pari',   NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 1, 'EST-016', 1),
(11, 'Marco',   'Flores Huanca', NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 1, 'EST-002', 1),
(12, 'Ana',     'Torres Condori',NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 1, 'EST-003', 1),
(13, 'Pedro',   'Ramos Larico',  NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 1, 'EST-004', 1),
(14, 'Sofía',   'Mendoza Apaza', NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 1, 'EST-005', 1),
(15, 'Kevin',   'Calizaya Mena', NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 1, 'EST-006', 1),
(16, 'Camila',  'Vargas Soto',   NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 2, 'EST-007', 1),
(17, 'Rodrigo', 'Huanca Cruz',   NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 2, 'EST-008', 1),
(18, 'Daniela', 'Paz Mamani',    NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 2, 'EST-009', 1),
(19, 'Fabricio','Talavera Hug',  NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 3, 'EST-010', 1),
(20, 'Milagros','Condori Ilaque',NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 3, 'EST-011', 1),
(21, 'Sebastián','Llerena Tito', NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 1, 'EST-012', 1),
(22, 'Xiomara', 'Becerra Pozo',  NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 1, 'EST-013', 1),
(23, 'Josué',   'Arenas Lupaca', NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 2, 'EST-014', 1),
(24, 'Isabella', 'Roque Nina',   NULL,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'estudiante', 3, 'EST-015', 1);

-- ── Cursos ───────────────────────────────────────────────────
INSERT IGNORE INTO cursos (id, nombre, slug, descripcion, color_hex, icono, activo) VALUES
(1, 'Ciencias Naturales',  'ciencias',    'Explora el mundo natural a través de experimentos STEAM.',        '#4361ee', '🔬', 1),
(2, 'Tecnología y Código', 'tecnologia',  'Aprende programación, robótica y pensamiento computacional.',     '#3ecf8e', '💻', 1),
(3, 'Ingeniería Creativa', 'ingenieria',  'Diseña y construye soluciones a problemas reales.',               '#f5c842', '⚙️', 1),
(4, 'Arte Digital',        'arte',        'Combina creatividad artística con herramientas digitales.',        '#a78bfa', '🎨', 1),
(5, 'Matemáticas Aplicadas','matematicas','Relaciona las matemáticas con la vida cotidiana y la ciencia.',   '#fb6340', '📐', 1);

-- ── Módulos ──────────────────────────────────────────────────
INSERT IGNORE INTO modulos (id, curso_id, titulo, descripcion, orden, activo) VALUES
(1,  1, 'Introducción a la robótica',        'Primeros pasos con sensores y actuadores.',            1, 1),
(2,  1, 'Fuerzas y movimiento',              'Experimenta con fuerzas, masa y aceleración.',         2, 1),
(3,  1, 'El ecosistema tecnológico',         'Cómo la tecnología impacta el medio ambiente.',        3, 1),
(4,  2, 'Variables y algoritmos',            'Conceptos básicos de programación con Scratch.',       1, 1),
(5,  2, 'Bucles y condicionales',            'Control de flujo en la programación.',                 2, 1),
(6,  2, 'Mi primer proyecto web',            'HTML y CSS básico para crear una página personal.',    3, 1),
(7,  3, 'Diseño de puentes',                 'Principios de resistencia de materiales.',             1, 1),
(8,  3, 'Ingeniería eléctrica básica',       'Circuitos simples con LEDs y pilas.',                  2, 1),
(9,  4, 'Arte digital y diseño',             'Herramientas digitales para crear arte.',              1, 1),
(10, 5, 'Estadística con datos reales',      'Recopila, grafica e interpreta datos de tu entorno.',  1, 1);

-- ── Aulas ────────────────────────────────────────────────────
-- 'activo' no existe en aulas; 'anio_escolar' sí y es obligatoria.
-- Es el mismo desajuste que hacía fallar con error 500 a
-- admin/colegios.php y docente/reportes.php, que filtraban por a.activo.
INSERT IGNORE INTO aulas (id, colegio_id, docente_id, grado, seccion, anio_escolar) VALUES
(1, 1, 4, '1ro Secundaria', 'A', 2026),
(2, 1, 5, '2do Secundaria', 'B', 2026),
(3, 2, 6, '1ro Secundaria', 'A', 2026),
(4, 2, 6, '2do Secundaria', 'A', 2026),
(5, 3, 4, '1ro Secundaria', 'A', 2026);

-- ── Practicante-Aula ─────────────────────────────────────────
INSERT IGNORE INTO practicante_aula (practicante_id, aula_id) VALUES
(7, 1), (7, 2), (8, 3), (8, 4);

-- ── Estudiante-Aula ──────────────────────────────────────────
INSERT IGNORE INTO estudiante_aula (estudiante_id, aula_id) VALUES
(10, 1), (11, 1), (12, 1), (13, 1), (14, 1), (15, 1),
(21, 1), (22, 1),
(16, 3), (17, 3), (23, 3),
(18, 4),
(19, 5), (20, 5), (24, 5);

-- ── Aula-Módulos ─────────────────────────────────────────────
INSERT IGNORE INTO aula_modulos (aula_id, modulo_id, fecha_planificada) VALUES
(1, 1, '2026-07-10'), (1, 2, '2026-07-17'), (1, 4, '2026-07-24'), (1, 5, '2026-07-31'),
(2, 1, '2026-07-10'), (2, 3, '2026-07-17'), (2, 7, '2026-07-24'),
(3, 4, '2026-07-10'), (3, 6, '2026-07-17'), (3, 9, '2026-07-24'),
(4, 5, '2026-07-10'), (4, 10,'2026-07-17'),
(5, 2, '2026-07-10'), (5, 8, '2026-07-17');

-- ── Progreso estudiantes ─────────────────────────────────────
INSERT IGNORE INTO progreso_estudiante (estudiante_id, modulo_id, paso_actual, completado, completado_en, estrellas_quiz) VALUES
-- Sofía (EST-001) — es la cuenta con la que se entra a demostrar la
-- plataforma, y no tenía ni una fila: su panel salía entero a cero, sin
-- progreso, sin estrellas y sin posición en el aula. Se le da un avance
-- intermedio para que el ranking y las barras muestren algo real.
(5, 1, 5, 1, '2026-08-04 09:20:00', 3),
(5, 2, 5, 1, '2026-08-09 10:05:00', 2),
(5, 4, 5, 1, '2026-08-14 11:40:00', 2),
(5, 3, 3, 0, NULL, NULL),
-- Lucía — muy avanzada
(10, 1, 5, 1, '2026-08-01 09:12:00', 3),
(10, 2, 5, 1, '2026-08-05 10:30:00', 3),
(10, 4, 5, 1, '2026-08-10 08:45:00', 2),
(10, 5, 3, 0, NULL, NULL),
-- Marco
(11, 1, 5, 1, '2026-08-02 11:00:00', 2),
(11, 4, 5, 1, '2026-08-08 09:15:00', 3),
(11, 2, 2, 0, NULL, NULL),
-- Ana
(12, 1, 5, 1, '2026-08-03 08:30:00', 3),
(12, 2, 5, 1, '2026-08-07 10:00:00', 2),
(12, 4, 4, 0, NULL, NULL),
-- Pedro
(13, 1, 5, 1, '2026-08-04 13:00:00', 2),
(13, 4, 5, 1, '2026-08-09 14:30:00', 1),
-- Sofía
(14, 1, 5, 1, '2026-08-05 09:00:00', 3),
(14, 2, 3, 0, NULL, NULL),
-- Kevin
(15, 1, 5, 1, '2026-08-06 08:00:00', 1),
-- Camila (colegio 2)
(16, 4, 5, 1, '2026-08-03 10:00:00', 3),
(16, 6, 5, 1, '2026-08-10 11:00:00', 2),
(16, 9, 3, 0, NULL, NULL),
-- Rodrigo (colegio 2)
(17, 4, 5, 1, '2026-08-04 09:30:00', 2),
(17, 6, 2, 0, NULL, NULL),
-- Daniela (colegio 2)
(18, 5, 5, 1, '2026-08-07 10:15:00', 3),
(18, 10,3, 0, NULL, NULL),
-- Fabricio (colegio 3)
(19, 2, 5, 1, '2026-08-02 08:45:00', 2),
(19, 8, 5, 1, '2026-08-09 09:30:00', 3),
-- Milagros (colegio 3)
(20, 2, 5, 1, '2026-08-03 10:30:00', 1),
-- Sebastián
(21, 1, 5, 1, '2026-08-08 08:00:00', 2),
-- Xiomara
(22, 1, 2, 0, NULL, NULL),
-- Josué (colegio 2)
(23, 4, 5, 1, '2026-08-11 09:00:00', 2),
-- Isabella (colegio 3)
(24, 2, 4, 0, NULL, NULL);

-- ── Mensajes demo ────────────────────────────────────────────
INSERT IGNORE INTO mensajes (id, remitente_id, destinatario_id, asunto, cuerpo, leido, created_at) VALUES
(1, 10, 4,
 'Consulta sobre módulo de robótica',
 'Hola profesora, tengo una duda sobre la actividad 3 del módulo de robótica. ¿Podría explicarme cómo funciona el sensor de distancia? Gracias.',
 0, '2026-08-23 09:12:00'),
(2, 11, 4,
 'Re: Entregable semana 4',
 'Adjunto el informe que me solicitó sobre las fuerzas y el movimiento. Espero que esté bien presentado. Cualquier corrección con gusto la hago.',
 0, '2026-08-23 08:45:00'),
(3, 12, 4,
 'Solicitud de certificado',
 'Buenos días profesora, quisiera saber el estado de mi certificado del módulo de robótica que completé hace dos semanas. ¿Ya está disponible para descargar?',
 0, '2026-08-22 15:30:00'),
(4, 7, 4,
 'Reporte de sesión - Aula 1ro A',
 'Estimada docente, adjunto el reporte de la sesión de ayer con el 1ro A. Tuvimos buena participación, 6 de 8 estudiantes completaron la actividad de robótica.',
 1, '2026-08-21 18:00:00'),
(5, 4, 10,
 'Re: Consulta módulo robótica',
 'Hola Lucía, el sensor de distancia funciona mediante ultrasonido. En el paso 3 del módulo hay un video explicativo. Cualquier duda más escríbeme.',
 0, '2026-08-23 10:00:00'),
(6, 1, 4,
 'Reunión de coordinación STEAM',
 'Hola María, te invito a la reunión de coordinación del programa STEAM este viernes 25/08 a las 10:00 am. Por favor confirmar asistencia.',
 1, '2026-08-20 09:00:00'),
(7, 4, 1,
 'Re: Reunión de coordinación STEAM',
 'Confirmado, estaré presente el viernes. Prepararé el informe de avance de mis aulas para compartirlo.',
 1, '2026-08-20 10:30:00');

-- ── Notificaciones demo ───────────────────────────────────────
INSERT IGNORE INTO notificaciones (id, usuario_id, tipo, titulo, mensaje, url, leida, created_at) VALUES
(1, 4, 'mensaje', 'Nuevo mensaje de Lucía Quispe Pari',
 'Consulta sobre módulo de robótica',
 '/innovasteam/mensajes/?id=1', 0, '2026-08-23 09:12:00'),
(2, 4, 'mensaje', 'Nuevo mensaje de Marco Flores Huanca',
 'Re: Entregable semana 4',
 '/innovasteam/mensajes/?id=2', 0, '2026-08-23 08:45:00'),
(3, 4, 'mensaje', 'Nuevo mensaje de Ana Torres Condori',
 'Solicitud de certificado',
 '/innovasteam/mensajes/?id=3', 0, '2026-08-22 15:30:00'),
(4, 10, 'modulo', '¡Módulo completado!',
 'Completaste "Fuerzas y movimiento" con 3 estrellas',
 '/innovasteam/estudiante/curso.php?slug=ciencias', 0, '2026-08-05 10:30:00'),
(5, 10, 'mensaje', 'Nuevo mensaje de María Torres',
 'Re: Consulta módulo robótica',
 '/innovasteam/mensajes/?id=5', 0, '2026-08-23 10:00:00'),
(6, 1, 'sistema', 'Nuevo estudiante registrado',
 'Isabella Roque Nina (EST-015) se unió al colegio IE Jorge Basadre.',
 '/innovasteam/admin/usuarios.php', 1, '2026-08-15 08:00:00');

-- ── Entregables demo ─────────────────────────────────────────
INSERT IGNORE INTO entregables (id, estudiante_id, modulo_id, formato, archivo_url, comentario_docente, calificacion, subido_en) VALUES
(1, 10, 1, 'prototipo',
 NULL,
 'Excelente presentación del proyecto de robótica. La explicación del funcionamiento del sensor es muy clara.',
 3, '2026-08-01 09:30:00'),
(2, 10, 4, 'dibujo_cientifico',
 NULL,
 'Buen trabajo con el algoritmo. Podrías mejorar los comentarios en el código.',
 2, '2026-08-10 08:50:00'),
(3, 11, 1, 'ficha',
 NULL,
 'La ficha está bien completada. Faltó la sección de conclusiones.',
 2, '2026-08-02 11:15:00'),
(4, 12, 1, 'mural_digital',
 NULL,
 'El mural digital quedó muy creativo. Demuestra comprensión del tema.',
 3, '2026-08-03 08:45:00'),
(5, 16, 4, 'cuento_ilustrado',
 NULL,
 'El cuento ilustrado es muy original. Integra bien los conceptos de programación.',
 3, '2026-08-03 10:15:00');

-- ── Apoderado de demostración ────────────────────────────────
-- Requiere la migración 005, que añade 'apoderado' al ENUM de rol y
-- crea apoderado_estudiante. Por eso local.sh aplica las migraciones
-- antes que este archivo. Si la tabla aún no existe, este bloque no
-- llega a ejecutarse y el resto del seed queda igual de válido.
INSERT IGNORE INTO usuarios (id, nombre, apellido, email, password_hash, rol, colegio_id, activo) VALUES
(30, 'Juan', 'Mamani', 'apoderado@innovasteam.edu.pe',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'apoderado', 1, 1);

-- Se le vinculan los dos primeros estudiantes del aula 1 para que la
-- vista comparativa tenga algo que comparar.
INSERT IGNORE INTO apoderado_estudiante (apoderado_id, estudiante_id, relacion)
SELECT 30, ea.estudiante_id, 'padre'
  FROM estudiante_aula ea
 WHERE ea.aula_id = 1
 ORDER BY ea.estudiante_id
 LIMIT 2;

SET foreign_key_checks = 1;

-- ============================================================
-- CUENTAS DE ACCESO RÁPIDO — contraseña: password
-- ============================================================
-- Admin          admin@innovasteam.edu.pe
-- Director       admin_col@innovasteam.edu.pe
-- Docente        docente@innovasteam.edu.pe
-- Practicante    practicante@innovasteam.edu.pe
-- Estudiante     EST-001                        (entra con código)
-- Apoderado      apoderado@innovasteam.edu.pe
-- ============================================================
