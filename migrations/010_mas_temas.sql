-- ================================================================
-- INNOVA-STEAM Migration 010 — Más temas
--
--   a) Curso nuevo: Ciencia y Astronomía (3 módulos)
--   b) Un módulo más en cada uno de los cinco cursos originales
--
-- El catálogo pasa de 15 a 23 módulos. Cada módulo trae sus cuatro
-- pasos (historia, actividad, quiz, entregable) y tres preguntas.
--
-- Ciencia hacía falta de verdad: el asistente de estudio ya declara
-- que responde de ciencia y astronomía, y StellarScribe forma parte
-- del proyecto, pero no había ni un módulo donde aterrizara.
--
-- Los módulos entran al catálogo general. Quién los ve y cuándo lo
-- decide el docente desde su aula; esta migración no asigna nada.
-- ================================================================

SET NAMES utf8mb4;

-- ── a) Claves que hacen re-ejecutable esta carga ─────────────────
-- Sin ellas, correr el archivo dos veces duplica cada módulo y cada
-- pregunta. modulo_pasos ya tenía la suya (uk_modulo_paso).
-- De paso impiden que dos módulos del mismo curso se llamen igual,
-- que es un error de captura fácil de cometer desde el editor.
CREATE UNIQUE INDEX IF NOT EXISTS uk_modulo_curso_titulo
    ON modulos (curso_id, titulo);

CREATE UNIQUE INDEX IF NOT EXISTS uk_quiz_paso_orden
    ON quiz_preguntas (paso_id, orden);

-- ── b) Curso nuevo ───────────────────────────────────────────────
INSERT IGNORE INTO cursos (nombre, slug, color_hex, icono, descripcion) VALUES
('Ciencia',  'ciencia',  '#22d3ee', 'telescope',
 'Observación del cielo, agua y suelo del valle: indagar, medir y registrar lo que pasa alrededor');

-- ── Identificadores de curso ─────────────────────────────────────
-- Por slug, no por id: en una base ya en uso los id no tienen por qué
-- coincidir con el orden de schema.sql.
SET @c_ciencia = (SELECT id FROM cursos WHERE slug = 'ciencia');
SET @c_mat     = (SELECT id FROM cursos WHERE slug = 'matematica');
SET @c_com     = (SELECT id FROM cursos WHERE slug = 'comunicacion');
SET @c_arte    = (SELECT id FROM cursos WHERE slug = 'arte');
SET @c_ing     = (SELECT id FROM cursos WHERE slug = 'ingenieria');
SET @c_eng     = (SELECT id FROM cursos WHERE slug = 'ingles');

-- ── Módulos ──────────────────────────────────────────────────────
INSERT IGNORE INTO modulos (curso_id, titulo, descripcion, orden, minutos_estimados) VALUES
-- Ciencia
(@c_ciencia, 'El cielo de Moquegua',   'Nayeli descubre que su cielo es de los más limpios del país. Observa, registra y aprende a leer las constelaciones del sur.', 1, 45),
(@c_ciencia, 'El viaje del agua',      'El agua del caño hizo un recorrido largo antes de llegar. Sigue el ciclo del agua desde el nevado hasta el valle.',        2, 45),
(@c_ciencia, 'La tierra que se mueve', 'Moquegua tiembla porque dos placas chocan bajo el mar. Entiende por qué ocurre y qué hacer cuando pasa.',                3, 45),
-- Un módulo más por curso
(@c_mat,  'Descuentos en la feria',   'La feria de Ilo llena de carteles con porcentajes. Calcula rebajas y descubre cuáles son de verdad.',            4, 45),
(@c_com,  'La voz de los abuelos',    'Lo que los mayores recuerdan no está en ningún libro. Prepara una entrevista y conviértela en un relato.',       4, 45),
(@c_arte, 'Un afiche para el agua',   'El colegio necesita un cartel que se lea desde lejos. Compón imagen y texto para convencer.',                    4, 45),
(@c_ing,  'Cocina solar',             'En Moquegua sobra sol. Construye una caja que cocine sin gas ni leña y mide cuánto calienta.',                    4, 45),
(@c_eng,  'Where is the plaza?',      'Tim se perdió camino a la plaza de armas. Aprende a dar y pedir direcciones en inglés.',                         4, 45);

-- ── Identificadores de módulo ────────────────────────────────────
SET @m_cielo    = (SELECT id FROM modulos WHERE curso_id = @c_ciencia AND titulo = 'El cielo de Moquegua');
SET @m_agua     = (SELECT id FROM modulos WHERE curso_id = @c_ciencia AND titulo = 'El viaje del agua');
SET @m_tierra   = (SELECT id FROM modulos WHERE curso_id = @c_ciencia AND titulo = 'La tierra que se mueve');
SET @m_feria    = (SELECT id FROM modulos WHERE curso_id = @c_mat     AND titulo = 'Descuentos en la feria');
SET @m_abuelos  = (SELECT id FROM modulos WHERE curso_id = @c_com     AND titulo = 'La voz de los abuelos');
SET @m_afiche   = (SELECT id FROM modulos WHERE curso_id = @c_arte    AND titulo = 'Un afiche para el agua');
SET @m_cocina   = (SELECT id FROM modulos WHERE curso_id = @c_ing     AND titulo = 'Cocina solar');
SET @m_plaza    = (SELECT id FROM modulos WHERE curso_id = @c_eng     AND titulo = 'Where is the plaza?');

-- ===========================================================================
-- Pasos — 4 por módulo
-- ===========================================================================

-- ---- El cielo de Moquegua ------------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_cielo, 1, 'historia',
 '{"narrativa":"Nayeli vive en Carumas y siempre pensó que el cielo era igual en todas partes. Una noche de julio, su tío llegó de Lima a pasar las fiestas y, al salir al patio, se quedó mudo mirando hacia arriba. Nayeli le preguntó qué le pasaba. El tío le respondió que en Lima nunca había visto tantas estrellas juntas, que allá el cielo se ve anaranjado y vacío por las luces de la ciudad. Esa noche Nayeli miró su propio cielo como si fuera la primera vez. Vio la banda blanca de la Vía Láctea cruzando de un extremo al otro, vio la Cruz del Sur inclinada sobre el cerro, y vio manchas oscuras dentro de la banda blanca. El tío le explicó que esas manchas son nubes de polvo que tapan la luz de las estrellas de atrás, y que los pueblos andinos las nombraron hace siglos: una de ellas es la Yacana, la llama oscura. Nayeli entendió esa noche que vivía en uno de los mejores lugares del Perú para observar el cielo, y que hasta entonces no lo había aprovechado ni una sola vez. Al día siguiente empezó una bitácora de observación: fecha, hora, dirección y dibujo de lo que veía.","pregunta_disparadora":"¿Por qué desde Moquegua se ven muchas más estrellas que desde una ciudad grande, si el cielo es el mismo para todos?"}'),
(@m_cielo, 2, 'actividad',
 '{"materiales":["Cuaderno o bitácora","Lápiz","Regla","Brújula o aplicación de brújula (opcional)","Una noche despejada"],"instrucciones":["Elige un lugar oscuro y seguro, lejos de focos directos. Espera diez minutos sin mirar pantallas: los ojos necesitan ese tiempo para adaptarse a la oscuridad.","Anota en tu bitácora la fecha, la hora y hacia dónde estás mirando (norte, sur, este u oeste).","Dibuja lo que ves en un recuadro: marca las estrellas más brillantes con puntos grandes y las débiles con puntos pequeños. Une con líneas las que formen una figura que reconozcas.","Busca la Cruz del Sur: cuatro estrellas en forma de cruz inclinada. Dibújala y anota sobre qué cerro o construcción la ves.","Cuenta cuántas estrellas alcanzas a distinguir dentro de tu recuadro y anota el número. Repite la observación otra noche a la misma hora y compara: ¿ves las mismas? ¿están en el mismo sitio?"],"minutos":25}'),
(@m_cielo, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_cielo, 4, 'entregable',
 '{"consigna":"Entrega tu bitácora con al menos dos observaciones en noches distintas: fecha, hora, dirección, dibujo del cielo y el número de estrellas contadas. Añade dos o tres líneas explicando qué cambió entre una noche y otra.","formatos":["dibujo_cientifico","ficha"],"instrucciones":"Sube una foto de tu bitácora. Máx 5 MB."}');

-- ---- El viaje del agua ---------------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_agua, 1, 'historia',
 '{"narrativa":"Mateo abrió el caño de su casa en Samegua para llenar un vaso y su hermana menor le hizo una pregunta que lo dejó pensando: ¿de dónde sale esa agua? Mateo respondió lo primero que se le ocurrió, que del caño, pero se dio cuenta de que no tenía idea. Esa tarde le preguntó a su papá, que trabaja regando los campos de palto. Su papá le contó que el agua que sale del caño empezó su viaje muy lejos y muy arriba, en las alturas de Carumas y Pasto Grande, donde cae nieve en la época de lluvias. Cuando el sol calienta, esa nieve se derrite y baja por las quebradas hasta juntarse en el río. El río recorre el valle, riega los olivos y las viñas, y una parte se guarda en la represa para los meses secos. Mateo preguntó de dónde salió la nieve, y su papá le dijo que del mar: el sol evapora el agua del océano, el viento empuja el vapor hasta la cordillera, y allá arriba el frío lo convierte en nieve. Mateo se quedó callado un rato. El agua de su vaso había estado en el mar, en una nube y en un nevado antes de llegar a su mano, y algún día volvería al mar otra vez. Nada se pierde: todo da la vuelta.","pregunta_disparadora":"El agua que bebes hoy estuvo antes en otros lugares. ¿En cuáles, y en qué estado se encontraba en cada uno?"}'),
(@m_agua, 2, 'actividad',
 '{"materiales":["Papel o cartulina","Lápices de colores","Un vaso de vidrio","Agua caliente","Un plato","Cubos de hielo"],"instrucciones":["Experimento: llena un vaso hasta la mitad con agua caliente, tápalo con un plato y pon los cubos de hielo encima del plato. Espera cinco minutos y observa la cara de abajo del plato.","Anota qué apareció ahí y explica con tus palabras por qué: ¿qué hizo el calor con el agua y qué hizo el frío con el vapor?","Dibuja el ciclo del agua de Moquegua en una hoja completa. Debe incluir, en este orden: el océano en Ilo, el sol evaporando, las nubes empujadas por el viento, la nieve en las alturas, el deshielo, el río, la represa, los cultivos del valle y el regreso al mar.","Rotula cada etapa con el nombre del cambio de estado que ocurre: evaporación, condensación, solidificación, fusión.","Escribe abajo: ¿qué pasaría en el valle si un año no cayera nieve en las alturas?"],"minutos":25}'),
(@m_agua, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_agua, 4, 'entregable',
 '{"consigna":"Entrega tu dibujo del ciclo del agua con las nueve etapas rotuladas y los cambios de estado nombrados, más la observación del experimento del vaso y tu respuesta sobre el año sin nieve.","formatos":["dibujo_cientifico","ficha"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ---- La tierra que se mueve ----------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_tierra, 1, 'historia',
 '{"narrativa":"La señora Julia enseña en un colegio de Moquegua y cada año, cuando toca el simulacro de sismo, cuenta la misma historia a sus estudiantes. En junio de 2001 ella tenía veinte años y estaba en la cocina de su casa. Primero escuchó un ruido bajo, como un camión pesado que se acercaba. Después el piso empezó a moverse y no paró. Las tazas se cayeron, la puerta se trabó, y cuando pudo salir a la calle vio que varias casas de adobe del barrio se habían venido abajo. Sus estudiantes siempre le preguntan lo mismo: ¿por qué tiembla tanto acá? Ella les explica que frente a la costa peruana, bajo el mar, hay dos piezas gigantes de la corteza terrestre que se empujan una contra otra sin parar. La placa de Nazca se mete lentamente debajo de la placa Sudamericana, unos pocos centímetros cada año. Ese movimiento acumula fuerza durante décadas hasta que la roca no aguanta más y se rompe de golpe. Esa ruptura es el sismo. Y como Moquegua está justo encima de esa zona de choque, los temblores son parte de vivir aquí. Por eso, dice la señora Julia, el simulacro no es un juego ni una pérdida de tiempo: es lo único que se puede practicar antes de que pase de verdad.","pregunta_disparadora":"Si los sismos en la costa del Perú no se pueden evitar ni predecir con exactitud, ¿qué sí se puede preparar con anticipación?"}'),
(@m_tierra, 2, 'actividad',
 '{"materiales":["Papel o cuaderno","Lápices de colores","Regla","Dos hojas de cartulina o cartón para el modelo"],"instrucciones":["Modelo de placas: toma dos pedazos de cartón y empújalos uno contra otro sobre la mesa hasta que uno se monte sobre el otro. Repite empujando hasta que se doblen. Anota qué observaste en la zona donde se tocan.","Dibuja un corte del suelo peruano visto de lado: el océano Pacífico a la izquierda, la placa de Nazca metiéndose por debajo, la placa Sudamericana encima, y la cordillera y Moquegua arriba. Marca con una estrella el punto donde ocurre la ruptura.","Explica con tus palabras, en tres o cuatro líneas, por qué el choque de las placas levantó la cordillera de los Andes.","Recorre tu casa o tu aula y dibuja un plano sencillo. Marca con verde las zonas seguras durante un sismo (columnas, mesas resistentes, salidas despejadas) y con rojo las peligrosas (ventanas, estantes altos, objetos colgados).","Haz la lista de lo que llevaría tu mochila de emergencia. Al menos seis elementos, y al lado de cada uno escribe para qué sirve."],"minutos":25}'),
(@m_tierra, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_tierra, 4, 'entregable',
 '{"consigna":"Entrega tu corte del suelo peruano con las dos placas y la ruptura marcada, el plano de tu casa o aula con las zonas verdes y rojas, y la lista de tu mochila de emergencia con la utilidad de cada elemento.","formatos":["dibujo_cientifico","ficha"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ---- Descuentos en la feria ----------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_feria, 1, 'historia',
 '{"narrativa":"En la feria de Ilo, los sábados, los puestos se llenan de carteles de colores: cincuenta por ciento de descuento, lleve tres y pague dos, oferta del día. Ana fue con su mamá a comprar zapatillas para el colegio y encontró dos puestos que vendían el mismo modelo. El primer puesto tenía un cartel enorme que decía cuarenta por ciento de descuento, y el precio original era ciento veinte soles. El segundo puesto no tenía cartel, pero el vendedor ofrecía las mismas zapatillas a setenta y cinco soles directos. La mamá de Ana se fue derecho al puesto del cartel grande, convencida de que ahí estaba la ganga. Ana la detuvo y le pidió un minuto. Sacó su cuaderno y calculó: el cuarenta por ciento de ciento veinte es cuarenta y ocho, y ciento veinte menos cuarenta y ocho da setenta y dos soles. Le mostró la cuenta a su mamá: el puesto del cartel salía tres soles más barato, pero no por lo que gritaba el cartel, sino por el precio de partida. En otro puesto vieron polos con setenta por ciento de descuento sobre un precio original que estaba escrito con letra chiquita y era altísimo. Ana calculó también ese, y descubrió que quedaba más caro que el polo sin oferta del puesto de al lado. Su mamá la miró y le dijo que de ahora en adelante ella se encargaba de las cuentas.","pregunta_disparadora":"Un cartel dice setenta por ciento de descuento y otro puesto vende lo mismo sin descuento. ¿Cómo sabes cuál te conviene de verdad?"}'),
(@m_feria, 2, 'actividad',
 '{"materiales":["Cuaderno","Lápiz","Calculadora (solo para verificar al final)"],"instrucciones":["Arma una tabla de seis productos que se vendan en una feria de tu zona, con cuatro columnas: Producto, Precio original, Descuento en porcentaje, Precio final.","Inventa precios y descuentos razonables. Usa al menos un diez por ciento, un veinticinco por ciento y un cincuenta por ciento.","Calcula a mano el descuento de cada producto: multiplica el precio original por el porcentaje y divide entre cien. Ese resultado es lo que te rebajan.","Resta el descuento al precio original para obtener el precio final y complétalo en la tabla. Recién ahí verifica con la calculadora.","Plantea el caso de Ana: mismo producto en dos puestos, uno con descuento sobre un precio alto y otro con precio directo. Calcula ambos y escribe cuál conviene y por qué."],"minutos":20}'),
(@m_feria, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_feria, 4, 'entregable',
 '{"consigna":"Entrega tu tabla de seis productos con los descuentos calculados a mano y los precios finales, más la comparación de los dos puestos con tu conclusión de cuál conviene.","formatos":["ficha","otro"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ---- La voz de los abuelos -----------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_abuelos, 1, 'historia',
 '{"narrativa":"A Renzo le mandaron de tarea averiguar cómo era su distrito hace cincuenta años. Buscó en internet y encontró tres párrafos y dos fotos borrosas. Frustrado, se lo comentó a su abuela Zoila mientras almorzaban. Ella lo escuchó y le dijo, sin darle importancia, que ella llegó a Moquegua en 1968 y que se acordaba de todo. Renzo levantó la cabeza. Esa tarde sacó el celular, apretó grabar y le hizo la primera pregunta. Su abuela le contó que la avenida principal era de tierra, que el agua se traía en baldes desde una pileta común, y que cuando llegó la primera línea de luz al barrio la gente salió a la calle a mirar los focos encendidos como si fuera una fiesta. Le contó también del terremoto, de la vez que el río creció y se llevó un puente, y de cómo se hacía el pan en horno de barro los domingos. Renzo grabó cuarenta minutos. Cuando fue a pasarlo al cuaderno se dio cuenta de que no podía escribir todo tal cual: había repeticiones, frases cortadas, cosas dichas dos veces. Tuvo que elegir, ordenar y darle forma sin cambiar lo que su abuela quiso decir. Ese fue el trabajo más difícil, y también el que hizo que su tarea no se pareciera a ninguna otra de la clase.","pregunta_disparadora":"¿Qué cosas sabe una persona mayor de tu familia o de tu barrio que no vas a encontrar escritas en ningún libro ni en internet?"}'),
(@m_abuelos, 2, 'actividad',
 '{"materiales":["Cuaderno","Lápiz o lapicero","Grabadora o celular (opcional)"],"instrucciones":["Elige a una persona mayor de tu familia o de tu barrio y pídele permiso para entrevistarla. Explícale para qué es.","Antes de la entrevista, escribe seis preguntas abiertas: las que empiezan con cómo, por qué o qué recuerda. Evita las que se contestan con sí o no, porque cortan la conversación.","Haz la entrevista. Escucha sin interrumpir y anota o graba. Si algo te sorprende, pregunta más sobre eso aunque no estuviera en tu lista.","Convierte la entrevista en un relato de dos o tres párrafos escrito en tercera persona. Incluye al menos dos frases textuales entre comillas, tal como las dijo la persona.","Ponle un título al relato y termínalo con una línea que explique por qué esa historia merece ser recordada."],"minutos":25}'),
(@m_abuelos, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_abuelos, 4, 'entregable',
 '{"consigna":"Entrega tus seis preguntas, el relato de dos o tres párrafos con al menos dos citas textuales entre comillas, el título y la línea final sobre por qué esa historia importa.","formatos":["cuento_ilustrado","ficha"],"instrucciones":"Sube una foto o el archivo de tu relato. Máx 5 MB."}');

-- ---- Un afiche para el agua ----------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_afiche, 1, 'historia',
 '{"narrativa":"El colegio de Milagros organizó una campaña para cuidar el agua y pidió a cada aula un afiche para pegar en los pasillos. Milagros se esforzó muchísimo: hizo un dibujo detallado de un río con peces, montañas al fondo, un sol con rayos y, abajo, un texto de cinco líneas explicando el problema del agua en el valle. Le quedó precioso de cerca. El día que colgaron todos los afiches en el pasillo, Milagros se paró al otro extremo para verlo desde lejos y sintió un golpe en el estómago: el suyo se veía como una mancha de colores. No se entendía nada. En cambio, a tres metros de distancia se leía perfecto el de su compañero Iván, que era mucho más simple: fondo azul liso, una gota grande blanca en el centro y cuatro palabras enormes. Milagros fue a preguntarle cómo lo había hecho y él le respondió que su hermano estudia diseño y le dio tres reglas: una sola idea por afiche, el texto principal tan grande que se lea desde el fondo del pasillo, y colores que contrasten fuerte entre sí. Milagros rehízo el suyo esa noche. Quitó el sol, quitó las montañas, dejó el río convertido en una sola línea y cambió las cinco líneas de texto por tres palabras. Al día siguiente, el suyo también se leía desde el otro extremo.","pregunta_disparadora":"¿Por qué un afiche con mucho detalle y mucho texto puede comunicar menos que uno simple con tres palabras?"}'),
(@m_afiche, 2, 'actividad',
 '{"materiales":["Cartulina o papel grande (A3 si es posible)","Témperas, plumones gruesos o lápices de colores","Regla","Lápiz"],"instrucciones":["Elige una sola idea que quieras que la gente recuerde sobre el cuidado del agua. Escríbela en una frase y no la cambies mientras diseñas.","Reduce esa frase a un lema de tres a cinco palabras. Debe caber en una línea y entenderse sin explicación.","Antes de dibujar en la cartulina, haz tres bocetos pequeños distintos en una hoja aparte. Prueba en cada uno una posición diferente del lema y de la imagen. Elige el mejor.","Dibuja el afiche final. El lema debe ocupar como mínimo la cuarta parte de la cartulina, y la imagen debe ser una sola figura reconocible, sin fondo cargado.","Elige dos colores que contrasten fuerte entre sí para el lema y el fondo. Cuélgalo y aléjate tres metros: si no se lee, agranda el texto o cambia el color hasta que se lea."],"minutos":25}'),
(@m_afiche, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_afiche, 4, 'entregable',
 '{"consigna":"Entrega una foto de tu afiche terminado tomada desde tres metros de distancia, junto con los tres bocetos previos y una línea explicando por qué elegiste ese diseño y esos dos colores.","formatos":["mural_digital","dibujo_cientifico"],"instrucciones":"Sube la foto de tu afiche y los bocetos. Máx 5 MB."}');

-- ---- Cocina solar --------------------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_cocina, 1, 'historia',
 '{"narrativa":"En la comunidad donde vive Elmer, arriba de Torata, el gas llega en balón y cuesta caro, y la leña se acabó hace años en los cerros cercanos. Su mamá cocina temprano y a veces racionan el fuego. Un día llegó al colegio un practicante de la universidad con una caja rara: cartón por fuera, papel de aluminio por dentro y un vidrio encima. Dijo que era una cocina solar y que en Moquegua funcionaba mejor que en casi cualquier otro sitio del Perú, porque el cielo está despejado casi todo el año y el sol pega fuerte. Elmer no le creyó nada. El practicante puso una ollita con agua dentro de la caja, la orientó hacia el sol y les dijo que volvieran en una hora. Elmer se quedó cerca, mirando de reojo. A los cuarenta minutos metió el dedo y lo sacó de golpe: el agua estaba caliente de verdad. El practicante les explicó por qué: el aluminio refleja los rayos hacia adentro y los concentra, el vidrio deja entrar la luz pero no deja salir el calor, y el color negro del fondo absorbe en vez de rebotar. Tres ideas simples, ningún combustible. Esa tarde Elmer se llevó cartón del basurero de la tienda y empezó a construir la suya para probar si podía calentar el agua del desayuno de su hermana.","pregunta_disparadora":"Si el sol calienta todo por igual, ¿por qué dentro de una caja con aluminio y vidrio el agua se calienta mucho más que afuera?"}'),
(@m_cocina, 2, 'actividad',
 '{"materiales":["Una caja de cartón mediana","Papel de aluminio","Cinta adhesiva o goma","Un pedazo de plástico transparente o vidrio que cubra la abertura","Papel o pintura negra","Un vaso o recipiente pequeño","Agua","Termómetro o el reverso de la mano para comparar","Reloj"],"instrucciones":["Forra todo el interior de la caja con papel de aluminio, con el lado brillante hacia adentro y lo más liso posible. El aluminio arrugado dispersa la luz en lugar de concentrarla.","Cubre el fondo de la caja con papel o pintura de color negro. El negro absorbe la luz; el blanco la rebota.","Recorta una de las tapas y déjala levantada en ángulo, forrada de aluminio, para que funcione como reflector y mande más luz hacia adentro.","Tapa la abertura con el plástico transparente o el vidrio, bien sellado en los bordes. Esto atrapa el calor dentro.","Pon el vaso con agua adentro, orienta la caja hacia el sol y anota la temperatura o la sensación al tacto cada diez minutos durante una hora. Deja fuera de la caja, al sol directo, un segundo vaso igual: es tu control, y sin él no puedes saber cuánto aportó tu diseño."],"minutos":30}'),
(@m_cocina, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_cocina, 4, 'entregable',
 '{"consigna":"Entrega una foto de tu cocina solar armada y la tabla de mediciones cada diez minutos de los dos vasos, el de adentro y el de control. Termina con dos o tres líneas: ¿cuál se calentó más y a qué lo atribuyes?","formatos":["prototipo","ficha"],"instrucciones":"Sube la foto del prototipo y tu tabla de mediciones. Máx 5 MB."}');

-- ---- Where is the plaza? -------------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_plaza, 1, 'historia',
 '{"narrativa":"Tim volvió a Moquegua, esta vez con dos amigos. Bajaron del bus en el terminal y quisieron ir caminando hasta la plaza de armas, pero se equivocaron de calle y terminaron dando vueltas por el mercado. Tim se acercó a un señor y le preguntó en inglés dónde quedaba la plaza. El señor entendió la palabra plaza y respondió con la mano: por allá. Tim caminó por allá, y por allá había tres calles. Volvió a perderse. Al rato pasó Camila, una estudiante de quinto grado que iba a su casa con el uniforme puesto. Tim la vio y probó otra vez. Camila entendió la pregunta pero se quedó trabada: sabía decir left y right, pero no sabía cómo decirle que caminara dos cuadras y doblara en la esquina del banco. Terminó llevándolos hasta la plaza caminando, que funcionó, aunque le dio vergüenza no haber podido explicarlo. Esa noche buscó cómo se dicen las indicaciones en inglés y anotó las que le habrían servido: go straight, turn left, turn right, two blocks, next to, in front of, on the corner. Se dio cuenta de que con menos de diez expresiones podía haber guiado a Tim sin moverse del sitio. Al día siguiente le enseñó la lista a toda su clase.","pregunta_disparadora":"Si alguien te pregunta en inglés cómo llegar a tu colegio desde la plaza, ¿qué expresiones necesitas saber para explicárselo sin acompañarlo?"}'),
(@m_plaza, 2, 'actividad',
 '{"materiales":["Hoja de papel","Lápiz","Regla","Lápices de colores"],"instrucciones":["Dibuja un plano sencillo de cuatro calles que se crucen, formando manzanas. Ubica en él seis lugares y rotúlalos en inglés: the school, the market, the church, the park, the hospital, the bus station.","Escribe la lista de expresiones que vas a usar, con su traducción: go straight (sigue derecho), turn left (dobla a la izquierda), turn right (dobla a la derecha), two blocks (dos cuadras), next to (al lado de), in front of (frente a), on the corner (en la esquina), between (entre).","Escribe tres rutas completas en inglés, cada una de cuatro a seis pasos. Por ejemplo: Go straight two blocks. Turn right. The market is next to the church.","Marca cada ruta en el plano con un color distinto y con flechas, para verificar que tus indicaciones llevan de verdad al destino.","Practica en voz alta con un compañero: uno lee la ruta en inglés y el otro la sigue con el dedo en el plano sin ver el texto. Si llega al lugar correcto, tu ruta está bien escrita."],"minutos":25}'),
(@m_plaza, 3, 'quiz',
 '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_plaza, 4, 'entregable',
 '{"consigna":"Entrega tu plano con los seis lugares rotulados en inglés, las tres rutas escritas en inglés y marcadas con colores distintos, y la lista de expresiones con su traducción.","formatos":["ficha","dibujo_cientifico"],"instrucciones":"Sube una foto de tu plano y tus rutas. Máx 5 MB."}');

-- ===========================================================================
-- Preguntas — 3 por módulo
-- ===========================================================================

INSERT IGNORE INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
-- El cielo de Moquegua
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_cielo AND numero_paso = 3),
 '¿Por qué desde una ciudad grande se ven menos estrellas que desde Carumas?',
 '[{"texto":"Porque en la ciudad hay menos estrellas en el cielo","correcta":false},{"texto":"Porque la luz artificial de la ciudad ilumina el aire y tapa las estrellas débiles","correcta":true},{"texto":"Porque las estrellas se alejan cuando hay muchas personas","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_cielo AND numero_paso = 3),
 'Las manchas oscuras dentro de la Vía Láctea, que los pueblos andinos nombraron como la Yacana, son:',
 '[{"texto":"Agujeros vacíos donde no existe nada","correcta":false},{"texto":"Nubes de polvo que bloquean la luz de las estrellas que están detrás","correcta":true},{"texto":"Sombras que proyecta la Luna sobre el cielo","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_cielo AND numero_paso = 3),
 'Antes de observar el cielo conviene esperar unos diez minutos sin mirar pantallas. ¿Por qué?',
 '[{"texto":"Porque el ojo necesita ese tiempo para adaptarse a la oscuridad y ver estrellas débiles","correcta":true},{"texto":"Porque las estrellas aparecen recién a esa hora","correcta":false},{"texto":"Porque el celular espanta a las estrellas","correcta":false}]', 3),

-- El viaje del agua
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_agua AND numero_paso = 3),
 'En el experimento del vaso, aparecen gotas debajo del plato frío. ¿Cómo se llama ese cambio de estado?',
 '[{"texto":"Evaporación","correcta":false},{"texto":"Condensación","correcta":true},{"texto":"Solidificación","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_agua AND numero_paso = 3),
 '¿Cuál es el orden correcto del viaje del agua que llega al valle de Moquegua?',
 '[{"texto":"Río, nieve, mar, nube","correcta":false},{"texto":"Nube, río, mar, nieve","correcta":false},{"texto":"Mar, nube, nieve en las alturas, deshielo, río, valle","correcta":true}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_agua AND numero_paso = 3),
 'Si un año casi no cae nieve en las alturas, ¿qué es lo más probable que ocurra meses después en el valle?',
 '[{"texto":"Nada, porque el agua del caño no depende de la nieve","correcta":false},{"texto":"Que baje menos agua por el río y falte para regar los cultivos","correcta":true},{"texto":"Que el río crezca más de lo normal","correcta":false}]', 3),

-- La tierra que se mueve
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_tierra AND numero_paso = 3),
 '¿Por qué tiembla con frecuencia en la costa sur del Perú?',
 '[{"texto":"Porque la placa de Nazca se mete debajo de la placa Sudamericana y acumula fuerza hasta romperse","correcta":true},{"texto":"Porque el mar golpea muy fuerte contra los acantilados","correcta":false},{"texto":"Porque hay muchos cerros altos","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_tierra AND numero_paso = 3),
 'Durante un sismo, ¿cuál de estos lugares es el MENOS seguro dentro de un aula?',
 '[{"texto":"Al lado de una columna","correcta":false},{"texto":"Junto a una ventana grande y debajo de un estante alto","correcta":true},{"texto":"Debajo de una mesa resistente","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_tierra AND numero_paso = 3),
 'Los sismos no se pueden predecir con día y hora exactos. Entonces, ¿qué sentido tiene el simulacro?',
 '[{"texto":"Ninguno, es solo para perder clases","correcta":false},{"texto":"Sirve para practicar antes qué hacer, porque durante el sismo no hay tiempo de pensarlo","correcta":true},{"texto":"Sirve para que el sismo ocurra más tarde","correcta":false}]', 3),

-- Descuentos en la feria
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_feria AND numero_paso = 3),
 'Un producto cuesta 120 soles y tiene 40 por ciento de descuento. ¿Cuánto pagas?',
 '[{"texto":"48 soles","correcta":false},{"texto":"72 soles","correcta":true},{"texto":"80 soles","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_feria AND numero_paso = 3),
 'Para calcular el 25 por ciento de 80 soles, ¿qué haces?',
 '[{"texto":"Multiplico 80 por 25 y divido entre 100, y da 20","correcta":true},{"texto":"Resto 25 a 80, y da 55","correcta":false},{"texto":"Divido 80 entre 25, y da 3.2","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_feria AND numero_paso = 3),
 'Un polo con 70 por ciento de descuento sale a 45 soles y otro sin descuento cuesta 35 soles. ¿Cuál conviene?',
 '[{"texto":"El del descuento, porque el porcentaje es más alto","correcta":false},{"texto":"El de 35 soles, porque lo que importa es el precio final, no el tamaño del descuento","correcta":true},{"texto":"Cuestan lo mismo","correcta":false}]', 3),

-- La voz de los abuelos
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_abuelos AND numero_paso = 3),
 '¿Cuál de estas es una pregunta abierta, de las que sirven en una entrevista?',
 '[{"texto":"¿Usted vivía acá en 1968?","correcta":false},{"texto":"¿Cómo era el barrio cuando usted llegó?","correcta":true},{"texto":"¿Le gustaba Moquegua, sí o no?","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_abuelos AND numero_paso = 3),
 'Al pasar una entrevista grabada a un relato escrito, ¿qué está permitido hacer?',
 '[{"texto":"Ordenar y quitar repeticiones, sin cambiar lo que la persona quiso decir","correcta":true},{"texto":"Inventar detalles para que la historia quede más emocionante","correcta":false},{"texto":"Copiar todo palabra por palabra, incluidas las frases cortadas","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_abuelos AND numero_paso = 3),
 '¿Para qué sirven las comillas en tu relato?',
 '[{"texto":"Para decorar el texto","correcta":false},{"texto":"Para marcar las palabras exactas que dijo la persona entrevistada","correcta":true},{"texto":"Para señalar las partes que inventaste","correcta":false}]', 3),

-- Un afiche para el agua
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_afiche AND numero_paso = 3),
 'Un afiche se cuelga en un pasillo y se mira desde lejos. ¿Qué lo hace funcionar mejor?',
 '[{"texto":"Muchos detalles pequeños y un texto largo que lo explique todo","correcta":false},{"texto":"Una sola idea, texto grande y colores que contrasten","correcta":true},{"texto":"Muchos colores suaves y parecidos entre sí","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_afiche AND numero_paso = 3),
 '¿Por qué conviene hacer tres bocetos pequeños antes de dibujar el afiche final?',
 '[{"texto":"Para gastar más papel","correcta":false},{"texto":"Para probar varias composiciones rápido y elegir la mejor antes de invertir tiempo","correcta":true},{"texto":"Porque el profesor pide tres trabajos","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_afiche AND numero_paso = 3),
 '¿Cuál de estas combinaciones de colores se lee mejor a tres metros de distancia?',
 '[{"texto":"Amarillo claro sobre blanco","correcta":false},{"texto":"Blanco sobre azul oscuro","correcta":true},{"texto":"Gris sobre celeste","correcta":false}]', 3),

-- Cocina solar
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_cocina AND numero_paso = 3),
 '¿Para qué sirve el papel de aluminio dentro de la caja?',
 '[{"texto":"Para reflejar los rayos del sol hacia adentro y concentrarlos","correcta":true},{"texto":"Para que la caja pese menos","correcta":false},{"texto":"Para absorber el calor y guardarlo","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_cocina AND numero_paso = 3),
 'El fondo de la cocina solar se pinta de negro y no de blanco. ¿Por qué?',
 '[{"texto":"Porque el negro se ve mejor en las fotos","correcta":false},{"texto":"Porque el negro absorbe la luz y la convierte en calor, mientras el blanco la rebota","correcta":true},{"texto":"Porque el blanco se ensucia rápido","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_cocina AND numero_paso = 3),
 '¿Para qué se deja un segundo vaso con agua al sol, fuera de la caja?',
 '[{"texto":"Para tener agua de repuesto por si se derrama","correcta":false},{"texto":"Para comparar y saber cuánto calentó la caja más allá del sol directo","correcta":true},{"texto":"Para que la caja no se sobrecaliente","correcta":false}]', 3),

-- Where is the plaza?
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_plaza AND numero_paso = 3),
 'What does "Go straight two blocks and turn right" mean?',
 '[{"texto":"Sigue derecho dos cuadras y dobla a la derecha","correcta":true},{"texto":"Regresa dos cuadras y dobla a la izquierda","correcta":false},{"texto":"Cruza dos calles y sigue de frente","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_plaza AND numero_paso = 3),
 'El mercado está justo al lado de la iglesia. ¿Cómo lo dices en inglés?',
 '[{"texto":"The market is in front of the church","correcta":false},{"texto":"The market is next to the church","correcta":true},{"texto":"The market is between the church","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id = @m_plaza AND numero_paso = 3),
 'Un turista te pregunta: "Excuse me, where is the bus station?". ¿Cuál respuesta le sirve más?',
 '[{"texto":"Señalarle con la mano y decir: over there","correcta":false},{"texto":"Turn left on the corner and go straight three blocks. It is in front of the park","correcta":true},{"texto":"Yes, the bus station","correcta":false}]', 3);
