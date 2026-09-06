-- ================================================================
-- INNOVA-STEAM Migration 014 — Contenido de secundaria
--
-- Doce módulos: uno de ciclo VI (1.º y 2.º) y uno de ciclo VII (3.º a
-- 5.º) por cada uno de los seis cursos. El catálogo pasa de 23 a 35.
--
-- Hasta ahora los 23 módulos eran todos de ciclo V, así que un aula de
-- secundaria entraba a la plataforma y encontraba contenido de quinto
-- de primaria. El seed ya creaba aulas de secundaria: el desajuste
-- estaba desde el principio.
--
-- Mismo enfoque que el resto del catálogo: una persona de Moquegua con
-- un problema real, y la matemática o la ciencia como la herramienta
-- que lo resuelve. Lo que cambia con la edad es la exigencia, no el
-- anclaje local.
-- ================================================================

SET NAMES utf8mb4;

SET @c_mat     = (SELECT id FROM cursos WHERE slug = 'matematica');
SET @c_com     = (SELECT id FROM cursos WHERE slug = 'comunicacion');
SET @c_arte    = (SELECT id FROM cursos WHERE slug = 'arte');
SET @c_ing     = (SELECT id FROM cursos WHERE slug = 'ingenieria');
SET @c_eng     = (SELECT id FROM cursos WHERE slug = 'ingles');
SET @c_ciencia = (SELECT id FROM cursos WHERE slug = 'ciencia');

-- ── Módulos ──────────────────────────────────────────────────────
INSERT IGNORE INTO modulos (curso_id, titulo, descripcion, orden, minutos_estimados, grado_ciclo) VALUES
-- Ciclo VI (1.º y 2.º de secundaria)
(@c_mat,     'El recibo de la luz',        'La factura subió y nadie sabe por qué. Lee la tarifa, modela el consumo con una función lineal y predice el mes que viene.', 5, 60, 'ciclo_vi'),
(@c_com,     'Lo que dice el titular',     'Tres medios cuentan la misma noticia de Moquegua de tres formas. Distingue hecho de opinión y detecta la carga de las palabras.',  5, 60, 'ciclo_vi'),
(@c_arte,    'El color de la protesta',    'Los murales del valle dicen cosas. Analiza símbolo, composición y contexto, y crea el tuyo con una postura propia.',          5, 60, 'ciclo_vi'),
(@c_ing,     'El brazo hidráulico',        'Mover algo pesado con dos jeringas y agua. Construye un brazo y mide la fuerza que ganas.',                                   5, 60, 'ciclo_vi'),
(@c_eng,     'My town, my rules',          'Escribe las normas de convivencia de tu colegio en inglés, usando must, mustnt y can.',                                       5, 60, 'ciclo_vi'),
(@c_ciencia, 'La huella del agua',         'Cada polo que usas costó litros que no viste. Calcula la huella hídrica de tu semana y de dónde sale.',                       4, 60, 'ciclo_vi'),
-- Ciclo VII (3.º, 4.º y 5.º de secundaria)
(@c_mat,     'Cuánto cuesta el agua',      'La tarifa de agua tiene tramos. Construye la función por tramos, grafícala y decide si conviene bajar el consumo.',           6, 60, 'ciclo_vii'),
(@c_com,     'El argumento y la falacia',  'Un debate real sobre la minería en Moquegua. Reconstruye argumentos, encuentra falacias y escribe una réplica.',              6, 60, 'ciclo_vii'),
(@c_arte,    'Documentar el valle',        'Una serie fotográfica sobre un oficio que se está perdiendo. Encuadre, secuencia y pie de foto.',                             6, 60, 'ciclo_vii'),
(@c_ing,     'El puente de la quebrada',   'Diseña una estructura con restricciones reales: presupuesto, materiales y carga. Justifica cada decisión.',                   6, 60, 'ciclo_vii'),
(@c_eng,     'Pitching Moquegua',          'Presenta un proyecto de tu comunidad en inglés, en tres minutos, ante alguien que no conoce el Perú.',                        6, 60, 'ciclo_vii'),
(@c_ciencia, 'Por qué tiembla y cuánto',   'De la escala de magnitud a la de intensidad. Interpreta un sismograma y evalúa la vulnerabilidad de tu casa.',                5, 60, 'ciclo_vii');

-- ── Identificadores ──────────────────────────────────────────────
SET @m_luz      = (SELECT id FROM modulos WHERE curso_id=@c_mat     AND titulo='El recibo de la luz');
SET @m_titular  = (SELECT id FROM modulos WHERE curso_id=@c_com     AND titulo='Lo que dice el titular');
SET @m_mural    = (SELECT id FROM modulos WHERE curso_id=@c_arte    AND titulo='El color de la protesta');
SET @m_brazo    = (SELECT id FROM modulos WHERE curso_id=@c_ing     AND titulo='El brazo hidráulico');
SET @m_rules    = (SELECT id FROM modulos WHERE curso_id=@c_eng     AND titulo='My town, my rules');
SET @m_huella   = (SELECT id FROM modulos WHERE curso_id=@c_ciencia AND titulo='La huella del agua');
SET @m_tarifa   = (SELECT id FROM modulos WHERE curso_id=@c_mat     AND titulo='Cuánto cuesta el agua');
SET @m_falacia  = (SELECT id FROM modulos WHERE curso_id=@c_com     AND titulo='El argumento y la falacia');
SET @m_foto     = (SELECT id FROM modulos WHERE curso_id=@c_arte    AND titulo='Documentar el valle');
SET @m_puente   = (SELECT id FROM modulos WHERE curso_id=@c_ing     AND titulo='El puente de la quebrada');
SET @m_pitch    = (SELECT id FROM modulos WHERE curso_id=@c_eng     AND titulo='Pitching Moquegua');
SET @m_sismo2   = (SELECT id FROM modulos WHERE curso_id=@c_ciencia AND titulo='Por qué tiembla y cuánto');

-- ===========================================================================
-- CICLO VI
-- ===========================================================================

-- ---- El recibo de la luz -------------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_luz, 1, 'historia',
 '{"narrativa":"El recibo de luz llegó a casa de Diana con 40 soles más que el mes anterior y su mamá lo dejó sobre la mesa sin decir nada. No habían comprado ningún aparato nuevo. Diana lo tomó y se puso a leerlo de verdad, línea por línea, algo que en su casa nadie hacía nunca. Encontró cosas que no entendía: cargo fijo, energía activa, alumbrado público, un número en kilovatios hora y un precio por cada uno. Le preguntó a su tío, que es electricista, y él le explicó algo que la dejó pensando: el recibo no es un precio, es una fórmula. Hay una parte que se paga siempre, uses lo que uses, y otra que crece según cuánto consumas. Diana anotó los recibos de los últimos seis meses en una tabla: mes, kilovatios hora y total pagado. Cuando puso los puntos en un plano vio que casi caían en una línea recta. Entendió entonces que si esa recta describía su casa, podía usarla para predecir cuánto pagarían el mes siguiente, y también para calcular cuánto ahorrarían si apagaban la terma media hora antes cada día. El recibo dejó de ser un papel que llega y se convirtió en algo que se puede leer, entender y discutir.","pregunta_disparadora":"Si el recibo tiene una parte fija y otra que depende del consumo, ¿cómo escribirías eso como una fórmula? ¿Qué representa cada número en tu casa?"}'),
(@m_luz, 2, 'actividad',
 '{"materiales":["Recibos de luz de tu casa (al menos 4 meses)","Papel cuadriculado o cuaderno","Regla","Calculadora","Lápices de colores"],"instrucciones":["Arma una tabla con los últimos 4 a 6 recibos: mes, consumo en kWh y total pagado en soles. Si no tienes recibos, usa estos: 85 kWh y 52 soles; 92 y 55.4; 110 y 64.4; 78 y 48.4.","Ubica los puntos en un plano cartesiano: el consumo en el eje horizontal, el total en el vertical. Usa una escala que aproveche toda la hoja.","Traza a mano la recta que mejor se ajuste a los puntos. No tiene que pasar por todos: tiene que dejar más o menos la misma cantidad de puntos arriba y abajo.","Calcula la pendiente tomando dos puntos de TU recta (no de los datos) y aplicando la diferencia de precios entre la diferencia de consumos. Ese número es el precio por kilovatio hora.","Prolonga la recta hasta cortar el eje vertical. Ese valor es el cargo fijo: lo que se paga aunque no se consuma nada.","Escribe tu modelo en la forma total = precio x consumo + fijo, con TUS números. Después predice el total para un consumo de 100 kWh y explica qué pasaría si el consumo bajara 20 kWh al mes durante un año."],"minutos":30}'),
(@m_luz, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_luz, 4, 'entregable',
 '{"consigna":"Entrega tu tabla de recibos, el plano con los puntos y tu recta trazada, el cálculo de la pendiente y del cargo fijo, tu fórmula escrita y la predicción para 100 kWh con el cálculo del ahorro anual.","formatos":["ficha","dibujo_cientifico"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ---- Lo que dice el titular ----------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_titular, 1, 'historia',
 '{"narrativa":"El mismo día, tres medios publicaron sobre el mismo hecho en Moquegua: un corte de agua de doce horas en varios barrios. El primero tituló: Corte programado de agua afectó a seis barrios durante doce horas. El segundo: Vecinos indignados por otro corte de agua sin aviso. El tercero: EPS realiza mantenimiento preventivo en la red de agua potable. Marcos, que cursa segundo de secundaria, los leyó los tres en el celular de su hermana y se quedó descolocado: ¿cuál era el verdadero? Su profesora le hizo una pregunta que le cambió la forma de leer: ¿cuál de los tres dice algo que se pueda comprobar? Marcos volvió a mirar. El primero decía cuántos barrios y cuántas horas: eso se puede verificar. El segundo decía indignados, que es una interpretación de quien escribe, y sin aviso, que sí se puede comprobar. El tercero decía preventivo, una palabra que hace parecer bueno lo que en los otros dos era un problema. Los tres partían del mismo hecho y los tres lo empujaban en una dirección distinta, sin mentir del todo en ninguno. Marcos entendió que un titular no solo informa: elige qué contar primero, qué palabra usar y a quién nombrar. Y que leer bien es notar esas elecciones antes de creerse la historia.","pregunta_disparadora":"¿Qué palabras de un titular se pueden comprobar y cuáles solo expresan la postura de quien escribe? Busca un ejemplo de cada una en un titular real de esta semana."}'),
(@m_titular, 2, 'actividad',
 '{"materiales":["Tres noticias sobre un mismo hecho (impresas, del celular o copiadas a mano)","Cuaderno","Resaltadores o lápices de dos colores"],"instrucciones":["Elige un hecho reciente de Moquegua o del Perú y consigue tres versiones de tres medios distintos. Copia los tres titulares en tu cuaderno.","Subraya de un color lo que sea comprobable (cifras, fechas, lugares, nombres) y de otro lo que sea valoración (adjetivos, verbos con carga, palabras que califican).","Haz una tabla de tres columnas, una por medio, con estas filas: a quién se nombra primero, qué palabra describe el hecho, qué se dice del responsable, qué NO aparece.","Escribe qué diferencias encontraste y qué efecto tiene cada elección en quien lee solo ese titular y no la nota completa.","Redacta tu propio titular sobre ese hecho: uno solo, de máximo quince palabras, que se sostenga únicamente con lo comprobable."],"minutos":30}'),
(@m_titular, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_titular, 4, 'entregable',
 '{"consigna":"Entrega los tres titulares con el subrayado de dos colores, la tabla comparativa completa, tu análisis del efecto de cada versión y tu propio titular solo con hechos comprobables.","formatos":["ficha","otro"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ---- El color de la protesta ---------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_mural, 1, 'historia',
 '{"narrativa":"En una pared larga de la avenida hay un mural que Rocío ve todos los días desde el micro y que nunca había mirado en serio. Un día el micro se quedó parado en el tráfico justo delante y tuvo tiempo de recorrerlo entero con la vista. Vio un río azul que empezaba ancho y terminaba en un hilo. Vio manos que salían de la tierra sosteniendo plantas de olivo. Vio, al fondo, unas siluetas de chimeneas grises que ella nunca había notado. Y vio, en una esquina, una fecha. Esa tarde le preguntó a su profesor de arte y él le contó que ese mural lo pintó un colectivo del valle hace años, durante un conflicto por el agua, y que cada elemento estaba puesto a propósito: el río que se adelgaza, el olivo que es el cultivo de la zona, el gris al fondo y no al frente. Rocío se dio cuenta de que había pasado años mirando sin ver. Un mural no es decoración: es alguien que tomó una posición y la puso donde todo el mundo pasa. Y quien lo mira puede estar de acuerdo o no, pero primero tiene que entender qué se está diciendo y con qué recursos se dice.","pregunta_disparadora":"En un mural, ¿por qué importa dónde está cada cosa y de qué tamaño es? Piensa en algo que veas todos los días y no hayas mirado de verdad."}'),
(@m_mural, 2, 'actividad',
 '{"materiales":["Una foto de un mural de tu ciudad (tomada por ti si es posible)","Cartulina o papel A3","Témperas, plumones o lápices de colores","Regla","Lápiz"],"instrucciones":["Analiza el mural que elegiste. Escribe: qué tema trata, qué elementos aparecen, cuál ocupa más espacio, cuál está más al centro y cuál al fondo, y qué colores dominan.","Explica en tres o cuatro líneas qué postura crees que sostiene su autor y en qué te basas. No vale decir que no dice nada: hasta eso sería una elección.","Elige un tema tuyo sobre el que tengas una postura y que le importe a tu comunidad. Escríbelo en una frase.","Boceta tu mural en pequeño tres veces, cambiando en cada uno qué elemento va al centro y cuál al fondo. Comprueba que cada versión dice algo distinto.","Dibuja la versión final en grande. Debe tener un elemento dominante, un plano de fondo y una paleta de tres colores como máximo, con al menos dos que contrasten. Escribe al reverso qué postura sostiene y con qué recursos."],"minutos":35}'),
(@m_mural, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_mural, 4, 'entregable',
 '{"consigna":"Entrega tu análisis del mural existente, los tres bocetos con la explicación de qué cambia en cada uno, y tu mural final con su nota al reverso sobre la postura y los recursos usados.","formatos":["mural_digital","dibujo_cientifico"],"instrucciones":"Sube una foto de tu mural y de tus bocetos. Máx 5 MB."}');

-- ---- El brazo hidráulico -------------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_brazo, 1, 'historia',
 '{"narrativa":"En el taller donde trabaja el papá de Iván arreglan camiones y hay una prensa hidráulica que a Iván siempre le pareció magia: su papá empuja una palanca sin esfuerzo aparente y la máquina dobla una plancha de acero. Un sábado le preguntó cómo era posible. Su papá se limpió las manos y le dijo que no era fuerza, era superficie. Le explicó que dentro hay aceite encerrado, y que cuando se aprieta un pistón pequeño, la presión se transmite igual a todo el líquido; si el otro pistón es mucho más ancho, esa misma presión actúa sobre mucha más área y produce mucha más fuerza. Lo que se gana en fuerza se pierde en distancia: el pistón grande se mueve poquísimo mientras el chico recorre un montón. Iván no terminaba de creérselo, así que su papá agarró dos jeringas de distinto tamaño, las unió con una manguerita y las llenó de agua. Le puso la chica en la mano y le dijo que empujara. Iván empujó y la grande subió una carga que él no habría levantado con esa fuerza. Ese día entendió que la máquina no inventa fuerza de la nada: la redistribuye, y hay una regla exacta que dice cuánto.","pregunta_disparadora":"Si la presión es la misma en todo el líquido, ¿por qué un pistón ancho empuja con más fuerza que uno delgado? ¿Qué se pierde a cambio?"}'),
(@m_brazo, 2, 'actividad',
 '{"materiales":["2 jeringas de distinto diámetro (por ejemplo 5 ml y 20 ml), sin aguja","Manguera fina o tubo de suero, 30 cm","Agua","Cartón grueso","Palitos de brocheta o baja lenguas","Silicona, cinta o goma","Regla","Objetos para pesar (monedas, piedras)"],"instrucciones":["Une las dos jeringas con la manguera y llénalas de agua sin dejar burbujas: una burbuja se comprime y arruina la transmisión de la presión.","Antes de construir nada, mide el diámetro interior de cada jeringa y calcula el área de cada pistón. Anota la razón entre las dos áreas: ese número es la ventaja mecánica que deberías obtener.","Construye un brazo articulado de cartón con dos segmentos y una base. Fija la jeringa grande de modo que su empuje levante el brazo.","Prueba cuánto peso levanta el brazo y con cuánta fuerza empujas tú. Compara el resultado con la ventaja que calculaste en el paso 2 y explica la diferencia si la hay.","Mide cuánto recorre el émbolo pequeño y cuánto el grande en el mismo movimiento. Comprueba que lo que ganas en fuerza lo pierdes en recorrido."],"minutos":40}'),
(@m_brazo, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_brazo, 4, 'entregable',
 '{"consigna":"Entrega una foto del brazo funcionando, los cálculos de área y de ventaja mecánica, las mediciones de recorrido de ambos émbolos y tu explicación de por qué el resultado real se parece o no al calculado.","formatos":["prototipo","ficha"],"instrucciones":"Sube la foto del prototipo y tus cálculos. Máx 5 MB."}');

-- ---- My town, my rules ---------------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_rules, 1, 'historia',
 '{"narrativa":"Al colegio de Alexandra llegó un grupo de estudiantes de intercambio y a su salón le tocó recibirlos. La tutora les pidió preparar un cartel con las normas de convivencia del colegio en inglés, para pegarlo en la puerta. Alexandra pensó que sería fácil: traducir palabra por palabra las normas que ya estaban en español. Empezó con No correr en los pasillos y escribió No run in the corridors. Su compañero le dijo que sonaba raro, como en una película mal doblada. Buscaron cómo se dicen de verdad las normas en inglés y encontraron algo que no habían visto en clase: hay una diferencia entre lo que está prohibido, lo que es obligatorio y lo que simplemente está permitido. Must significa que es obligatorio; mustnt, que está prohibido; can, que se puede; y should, que es lo recomendable aunque no obligatorio. Al revisar las normas del colegio con esos cuatro se dieron cuenta de algo incómodo: varias de las normas escritas en español no dejaban claro en cuál de las cuatro categorías caían. Terminaron el cartel en inglés y, de paso, propusieron reescribir tres normas en español porque decían menos de lo que parecía.","pregunta_disparadora":"¿Cuál es la diferencia entre you mustnt use your phone y you shouldnt use your phone? ¿Cuál de las dos usarías para una norma de tu colegio y por qué?"}'),
(@m_rules, 2, 'actividad',
 '{"materiales":["Cartulina o papel A3","Plumones","Cuaderno","Lápiz"],"instrucciones":["Anota en tu cuaderno seis normas reales de tu colegio o de tu casa, en español.","Clasifica cada una en obligatoria, prohibida, permitida o recomendable. Si alguna no encaja claramente en ninguna, márcala: esa norma está mal escrita.","Escribe las seis en inglés usando must, mustnt, can y should según la clasificación. Estructura: sujeto + modal + verbo en infinitivo sin to. Ejemplo: You must wear your uniform.","Añade a tres de ellas una razón con because: You mustnt run in the corridors because it is dangerous.","Diseña el cartel final con las seis normas en inglés, legible desde dos metros, con un icono simple al lado de cada una."],"minutos":30}'),
(@m_rules, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_rules, 4, 'entregable',
 '{"consigna":"Entrega tu lista de seis normas en español con su clasificación, las mismas seis escritas en inglés con el modal correcto, las tres razones con because y una foto del cartel terminado.","formatos":["ficha","mural_digital"],"instrucciones":"Sube una foto de tu trabajo y del cartel. Máx 5 MB."}');

-- ---- La huella del agua --------------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_huella, 1, 'historia',
 '{"narrativa":"En el colegio de Bruno hicieron una campaña de ahorro de agua y pusieron carteles sobre cerrar el caño al lavarse los dientes. A Bruno le pareció bien, hasta que en clase de ciencia la profesora escribió una cifra en la pizarra que nadie se creyó: un polo de algodón necesita alrededor de 2500 litros de agua para existir. La clase protestó. La profesora explicó de dónde sale: el algodón es una planta y hay que regarla durante meses; después hay que procesar la fibra, teñirla y lavarla varias veces. Toda esa agua no la ves nunca, no sale de tu caño, pero se usó igual, casi siempre en otro lugar del mundo. Se llama agua virtual, y cada producto arrastra la suya. Bruno hizo la cuenta de lo que llevaba puesto ese día y le salió más agua que la que su familia gasta en varios meses. No dejó de cerrar el caño al lavarse los dientes, pero entendió algo más grande: en un valle como el de Moquegua, donde el agua se reparte entre la ciudad, la agricultura y la minería, saber cuánta agua hay detrás de cada cosa cambia por completo qué decisiones parecen importantes.","pregunta_disparadora":"Si el agua de tu caño es solo una parte pequeña de la que consumes, ¿dónde está el resto? ¿Cómo lo averiguarías?"}'),
(@m_huella, 2, 'actividad',
 '{"materiales":["Cuaderno","Calculadora","Papel para el gráfico","Lápices de colores"],"instrucciones":["Registra durante tres días lo que consumes: comidas, bebidas y prendas de ropa que estrenes o uses. Anota cantidades aproximadas.","Usa estos valores de referencia en litros por unidad: 1 kg de arroz 2500; 1 kg de papa 290; 1 kg de carne de res 15000; 1 kg de pollo 4300; 1 huevo 200; 1 vaso de leche 250; 1 taza de café 130; 1 polo de algodón 2500; 1 jean 8000.","Calcula el total de agua virtual de tus tres días y divídelo entre 3 para tener tu promedio diario.","Compara ese número con el consumo directo de una persona en casa, que ronda los 150 litros al día. Escribe la razón entre ambos: cuántas veces más es.","Haz un gráfico de barras con tus cinco consumos de mayor huella. Después responde: si tuvieras que bajar tu huella un 20%, ¿qué cambiarías primero y por qué ese y no otro?"],"minutos":30}'),
(@m_huella, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_huella, 4, 'entregable',
 '{"consigna":"Entrega tu registro de tres días, la tabla de cálculo con los totales, tu promedio diario comparado con los 150 litros del consumo directo, el gráfico de barras y tu propuesta razonada para bajar la huella un 20%.","formatos":["ficha","dibujo_cientifico"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ===========================================================================
-- CICLO VII
-- ===========================================================================

-- ---- Cuánto cuesta el agua -----------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_tarifa, 1, 'historia',
 '{"narrativa":"La tía de Alonso administra un comedor popular y cada mes discute con la empresa de agua. Dice que un mes gastaron poco más que el anterior y el recibo subió mucho más de lo proporcional. Alonso, que está en cuarto de secundaria, se ofreció a revisarlo. Encontró que la tarifa no es un precio único: está partida en tramos. Hasta cierta cantidad de metros cúbicos se paga un precio por metro; pasando ese límite, cada metro adicional cuesta más caro; y hay un tercer tramo todavía más caro por encima. El comedor había cruzado el segundo límite. Alonso se dio cuenta de que eso no se puede modelar con una sola recta: hace falta una función definida por tramos, con una expresión distinta en cada intervalo. Cuando la graficó vio la forma exacta del problema: una línea que se quiebra y se empina en cada límite. Y vio también algo útil para su tía: justo antes de cada quiebre hay una zona donde consumir un poco menos ahorra mucho más de lo que parece. Le llevó la gráfica impresa y le marcó dónde estaba el comedor y a cuántos metros cúbicos del quiebre. Su tía cambió los horarios de lavado esa misma semana.","pregunta_disparadora":"¿Por qué una tarifa por tramos no se puede describir con una sola fórmula? ¿Qué pasa exactamente en el punto donde cambia el tramo?"}'),
(@m_tarifa, 2, 'actividad',
 '{"materiales":["Papel cuadriculado o milimetrado","Regla","Calculadora","Cuaderno"],"instrucciones":["Usa esta tarifa: de 0 a 10 m3, 1.30 soles por m3; de 10 a 25 m3, 2.10 por cada m3 que pase de 10; más de 25 m3, 4.60 por cada m3 que pase de 25. Cargo fijo de 5.20 soles siempre.","Escribe la función por tramos: una expresión para cada intervalo, indicando claramente los límites de cada uno.","Calcula el importe para 8, 10, 18, 25 y 40 m3. Muestra el desarrollo, no solo el resultado.","Grafica la función en el papel cuadriculado, de 0 a 45 m3. Marca con un punto los quiebres y comprueba que la gráfica no da saltos: el valor por la izquierda y por la derecha de cada quiebre debe coincidir.","Responde con cálculos: ¿cuánto ahorra un consumo que baja de 27 a 24 m3? ¿Y uno que baja de 20 a 17? Explica por qué los dos ahorros no son iguales aunque ambos bajen 3 m3."],"minutos":40}'),
(@m_tarifa, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_tarifa, 4, 'entregable',
 '{"consigna":"Entrega la función por tramos escrita con sus intervalos, los cinco importes con su desarrollo, la gráfica con los quiebres marcados y la comparación razonada de los dos ahorros de 3 m3.","formatos":["ficha","dibujo_cientifico"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ---- El argumento y la falacia -------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_falacia, 1, 'historia',
 '{"narrativa":"En la mesa de casa de Fernanda se discute de minería desde que ella tiene memoria. Su papá trabaja en una empresa contratista; su tía es agricultora en el valle. Las conversaciones siempre terminan mal. Un día, en clase de comunicación, les pidieron reconstruir los argumentos de un debate real, y Fernanda eligió justamente ese. Al ponerlo por escrito descubrió algo que en la mesa nunca se veía: casi ninguna de las frases que se decían era realmente un argumento. Su papá decía que quien está en contra no sabe lo que dice porque nunca ha pisado una mina; eso no responde a nada, ataca a la persona. Su tía decía que si se aprueba este proyecto, mañana no quedará una gota de agua en el valle; eso da por hecho un desenlace extremo sin mostrar el camino. Alguien dijo que todo el mundo sabe que la minería es progreso; eso apela a una mayoría que nadie ha contado. Cuando Fernanda separó lo que era afirmación, lo que era razón y lo que era solo carga emocional, quedaron muy pocas frases en pie, pero esas pocas sí se podían discutir de verdad. Llevó su cuadro a casa el domingo. La conversación no terminó de acuerdo, pero por primera vez terminó bien.","pregunta_disparadora":"¿Qué diferencia hay entre atacar una idea y atacar a quien la dice? Busca un ejemplo de cada uno en una discusión que hayas escuchado."}'),
(@m_falacia, 2, 'actividad',
 '{"materiales":["Cuaderno","Un texto de opinión, columna o transcripción de debate sobre un tema local","Lápiz y resaltador"],"instrucciones":["Elige un texto de opinión sobre un tema que se discuta en Moquegua: agua, minería, transporte o basura. Debe tener al menos cuatro párrafos.","Extrae la tesis: la afirmación central que el autor quiere que aceptes, en una sola frase con tus palabras.","Lista las razones que da para sostenerla. Para cada una anota qué evidencia aporta: dato, ejemplo, cita de experto, o ninguna.","Busca al menos dos falacias e identifícalas por su nombre: ataque a la persona, pendiente resbaladiza, apelación a la mayoría, falso dilema o generalización apresurada. Copia la frase exacta y explica en una línea por qué no sostiene lo que pretende.","Escribe una réplica de dos párrafos. En el primero, reconoce lo mejor del argumento contrario, lo más fuerte que dice. En el segundo, presenta tu posición con al menos una razón apoyada en evidencia. Sin adjetivos hacia el autor."],"minutos":40}'),
(@m_falacia, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_falacia, 4, 'entregable',
 '{"consigna":"Entrega la tesis del texto en una frase, la lista de razones con el tipo de evidencia de cada una, las dos falacias identificadas por su nombre con la frase citada y tu explicación, y tu réplica de dos párrafos.","formatos":["ficha","otro"],"instrucciones":"Sube una foto o el archivo de tu trabajo. Máx 5 MB."}');

-- ---- Documentar el valle -------------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_foto, 1, 'historia',
 '{"narrativa":"Don Emilio es uno de los últimos que hace canastas de carrizo en el valle. Tiene setenta y ocho años y sus hijos se fueron a Tacna. Cuando Alejandra le pidió tomarle unas fotos para un trabajo del colegio, él se rió y le dijo que no había nada que fotografiar, que era solo trabajo. Alejandra fue tres tardes. La primera tomó cuarenta fotos y ninguna servía: eran todas iguales, del señor sonriendo a la cámara, de frente y de lejos. Su profesora le dijo algo que la hizo volver: una serie no es un montón de fotos del mismo tema, es una secuencia que cuenta algo que una sola foto no puede contar. La segunda tarde Alejandra dejó de pedirle que posara. Fotografió las manos abriendo el carrizo. El montón de tiras mojadas. El gesto de la boca cuando calcula. El taller vacío al final del día. La tercera tarde consiguió la que le faltaba: la canasta terminada apoyada en la pared, junto a otras veinte que nadie ha venido a comprar. Cuando puso las seis fotos en orden entendió que estaba contando algo que don Emilio nunca le había dicho con palabras.","pregunta_disparadora":"¿Qué puede contar una serie de seis fotos que no puede contar la mejor foto suelta? Piensa en el orden, no solo en las imágenes."}'),
(@m_foto, 2, 'actividad',
 '{"materiales":["Celular con cámara","Cuaderno para las notas","Permiso de la persona que vas a fotografiar"],"instrucciones":["Elige un oficio, una actividad o un lugar de tu comunidad que esté cambiando o desapareciendo. Pide permiso explicando para qué es y qué vas a hacer con las fotos.","Antes de disparar, escribe en una frase qué quieres que entienda quien vea la serie. Esa frase manda sobre todo lo demás.","Toma al menos veinte fotos en dos visitas distintas. Varía la distancia a propósito: plano general del lugar, plano medio de la persona trabajando, y primer plano de las manos o de un detalle.","Selecciona seis. Deben incluir al menos un plano general, un plano medio y un primer plano; el resto lo eliges tú. Descartar es la parte difícil: si dudas entre dos parecidas, quédate con una.","Ordénalas en una secuencia y escribe un pie de foto para cada una: una frase que aporte algo que la imagen no dice, nunca que la repita. Cierra con un texto de cuatro o cinco líneas sobre por qué elegiste ese tema."],"minutos":45}'),
(@m_foto, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_foto, 4, 'entregable',
 '{"consigna":"Entrega tu serie de seis fotos en el orden elegido, con los seis pies de foto, la frase inicial de lo que querías contar y el texto de cierre. Indica qué plano es cada foto.","formatos":["mural_digital","otro"],"instrucciones":"Sube las fotos y el texto. Máx 5 MB."}');

-- ---- El puente de la quebrada --------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_puente, 1, 'historia',
 '{"narrativa":"La quebrada que separa el anexo de la carretera se cruza por un tablón. En época seca es una molestia; cuando llueve arriba y baja el agua, es un peligro real y los chicos no van al colegio dos o tres días. La comunidad juntó dinero y le pidió al ingeniero del municipio que hiciera algo. Él fue, midió, y en la asamblea dijo algo que a Katherine, que estaba escuchando desde atrás, le pareció más interesante que cualquier plano: que él no podía diseñar el mejor puente, sino el mejor puente posible con nueve mil soles, con los materiales que llegan hasta ahí en camioneta, y que aguante a una vaca cargada porque eso es lo que de verdad va a cruzar. Alguien propuso una estructura que había visto en internet. El ingeniero respondió que era buenísima y que necesitaba una grúa que no puede subir por ese camino. Katherine entendió esa tarde que la ingeniería no es elegir la solución más impresionante, sino la que sobrevive a todas las restricciones a la vez. Y que las restricciones —cuánto dinero, qué llega hasta ahí, qué tiene que aguantar— no son el obstáculo del problema: son el problema.","pregunta_disparadora":"¿Por qué un diseño excelente puede ser una mala solución? Piensa en una restricción de tu comunidad que descarte opciones que en otro sitio funcionarían."}'),
(@m_puente, 2, 'actividad',
 '{"materiales":["Palitos de madera, fideos secos o papel enrollado","Goma o silicona","Hilo","Regla","Balanza o botellas con agua medida para cargar","Cuaderno"],"instrucciones":["Fija tus restricciones antes de diseñar nada, por escrito: luz libre de 30 cm; presupuesto de 40 unidades, donde cada palito cuesta 1 y cada 10 cm de hilo cuesta 1; debe sostener al menos 500 g en el centro; solo puedes apoyarlo en los dos extremos.","Dibuja dos diseños distintos que respeten las restricciones. Uno debe usar triángulos; el otro, no. Calcula el costo de cada uno antes de construir.","Construye el que creas mejor y explica por qué lo elegiste, en términos de las restricciones y no de que se ve más bonito.","Cárgalo poco a poco hasta que falle. Anota la carga máxima que soportó y, sobre todo, DÓNDE falló: qué elemento cedió primero.","Calcula la eficiencia como carga soportada dividida entre costo. Después responde: con lo que aprendiste del punto donde falló, ¿qué cambiarías del diseño sin pasarte del presupuesto?"],"minutos":45}'),
(@m_puente, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_puente, 4, 'entregable',
 '{"consigna":"Entrega tus restricciones escritas, los dos diseños con su costo calculado, la justificación de tu elección, una foto del puente cargado, la carga máxima, dónde falló, la eficiencia y tu mejora propuesta dentro del presupuesto.","formatos":["prototipo","ficha"],"instrucciones":"Sube la foto del prototipo y tus cálculos. Máx 5 MB."}');

-- ---- Pitching Moquegua ---------------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_pitch, 1, 'historia',
 '{"narrativa":"A la feria de proyectos escolares de la región llegó una evaluadora de una fundación internacional y las presentaciones tenían que ser en inglés, tres minutos por equipo. El grupo de Diego había trabajado meses en un sistema de riego por goteo con botellas recicladas y estaban seguros de ganar. Cuando les tocó, Diego empezó leyendo del papel: Our project is about a system of irrigation that uses recycled bottles for the plants in the school garden... A los cuarenta segundos la evaluadora seguía sin saber para qué servía aquello ni por qué importaba. El equipo que ganó presentó algo más simple, pero empezó distinto: In my town, water arrives twice a week. Our garden used to die every summer. Then we built this. Diego entendió el golpe. No había perdido por su inglés, que era mejor que el del otro equipo. Había perdido porque empezó por la solución en vez de por el problema, y porque quien lo escuchaba no tenía ni idea de qué es vivir donde el agua llega dos veces por semana. Presentar no es traducir lo que hiciste: es hacer que alguien que no conoce tu mundo entienda por qué tu trabajo importa, y hacerlo en el tiempo que te dan.","pregunta_disparadora":"Si tienes tres minutos y quien escucha no conoce el Perú, ¿por dónde empiezas y qué dejas fuera?"}'),
(@m_pitch, 2, 'actividad',
 '{"materiales":["Cuaderno","Celular para grabarte","Cronómetro"],"instrucciones":["Elige un proyecto, iniciativa o problema de tu comunidad que conozcas bien.","Escribe el guion en inglés con esta estructura y estos tiempos: el problema en contexto, 45 segundos; qué hiciste o propones, 60; cómo funciona, 45; qué cambió o cambiaría, 30.","Empieza por el problema, nunca por la solución. La primera frase debe situar a alguien que no conoce Moquegua: In my town, ... o Every summer, ...","Revisa los tiempos verbales: pasado para lo que ya hiciste, presente para cómo funciona, condicional o futuro para lo que cambiaría. Marca en el guion cuál usas en cada parte.","Grábate y cronométrate. Si te pasas de tres minutos, no hables más rápido: corta contenido. Repite hasta que entres en tiempo hablando con calma, y anota qué cortaste y por qué."],"minutos":40}'),
(@m_pitch, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_pitch, 4, 'entregable',
 '{"consigna":"Entrega tu guion en inglés con las cuatro partes y sus tiempos marcados, la indicación del tiempo verbal usado en cada una, y el audio o video de tu presentación dentro de los tres minutos. Añade qué tuviste que cortar y por qué.","formatos":["otro","ficha"],"instrucciones":"Sube tu guion y la grabación. Máx 5 MB."}');

-- ---- Por qué tiembla y cuánto --------------------------------------------
INSERT IGNORE INTO modulo_pasos (modulo_id, numero_paso, tipo, contenido) VALUES
(@m_sismo2, 1, 'historia',
 '{"narrativa":"Después de un sismo, en el grupo de WhatsApp del barrio de Nicolás siempre pasa lo mismo: alguien dice que fue de 6, otro que de 4, otro que en su casa se movió todo y en la del vecino casi nada. Nicolás, que estudia quinto de secundaria, buscó el reporte oficial del IGP y encontró un solo número de magnitud y, además, un mapa con varios valores distintos según la zona. Su profesor le explicó la diferencia y le pareció obvia una vez dicha: la magnitud mide la energía que liberó la ruptura, y por eso es una sola para todo el sismo; la intensidad mide lo que se sintió y los daños que hubo en un lugar concreto, y por eso cambia de un sitio a otro. Un mismo sismo puede tener intensidad alta sobre suelo blando y baja sobre roca a la misma distancia. Nicolás miró entonces su propia casa con otros ojos: dos pisos de albañilería, el segundo levantado años después sin columnas nuevas, sobre relleno. Entendió que la magnitud no la decide nadie, pero la intensidad que sufre una casa depende en buena parte de cómo se construyó. Y que eso sí se puede evaluar, y en algunos casos corregir, antes de que ocurra.","pregunta_disparadora":"Dos casas a la misma distancia del epicentro sufren daños muy distintos. Si la magnitud fue la misma para ambas, ¿qué explica la diferencia?"}'),
(@m_sismo2, 2, 'actividad',
 '{"materiales":["Cuaderno","Regla","Calculadora","Acceso a un reporte sísmico del IGP (o los datos de ejemplo)","Cámara del celular (opcional)"],"instrucciones":["Explica con tus palabras y en una tabla la diferencia entre magnitud e intensidad: qué mide cada una, en qué escala, y si es única o varía según el lugar.","La escala de magnitud es logarítmica: cada punto entero multiplica la energía liberada por unas 32 veces. Calcula cuántas veces más energía libera un sismo de 7.0 que uno de 5.0, y explica por qué la diferencia no es de 2 en 32.","Busca un sismo reciente en el reporte del IGP y anota magnitud, profundidad, epicentro y las intensidades reportadas. Si no tienes acceso, usa: magnitud 6.2, profundidad 45 km, epicentro frente a Ilo, intensidades de IV a VI según la zona.","Recorre tu casa o tu colegio con una lista y evalúa: ¿los muros tienen columnas y vigas de amarre?, ¿hay un segundo piso añadido después?, ¿está sobre relleno o sobre terreno firme?, ¿hay objetos pesados sin asegurar en altura?, ¿las salidas están despejadas?","Escribe tres medidas concretas y realizables para reducir la vulnerabilidad que encontraste, ordenadas de la más barata a la más cara. Junto a cada una, qué riesgo reduce exactamente."],"minutos":40}'),
(@m_sismo2, 3, 'quiz', '{"info":"Ver tabla quiz_preguntas para las preguntas de este paso"}'),
(@m_sismo2, 4, 'entregable',
 '{"consigna":"Entrega tu tabla comparando magnitud e intensidad, el cálculo de energía entre 7.0 y 5.0 con su explicación, los datos del sismo analizado, la evaluación de tu casa o colegio punto por punto y las tres medidas ordenadas por costo con el riesgo que reduce cada una.","formatos":["ficha","dibujo_cientifico"],"instrucciones":"Sube una foto de tu trabajo terminado. Máx 5 MB."}');

-- ===========================================================================
-- Preguntas — 3 por módulo
-- ===========================================================================

INSERT IGNORE INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
-- El recibo de la luz
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_luz AND numero_paso=3),
 'En el modelo total = precio x consumo + fijo, ¿qué representa el "fijo"?',
 '[{"texto":"Lo que se paga aunque el consumo sea cero","correcta":true},{"texto":"El precio de cada kilovatio hora","correcta":false},{"texto":"El consumo máximo permitido","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_luz AND numero_paso=3),
 'Si al pasar de 80 a 100 kWh el recibo sube de 48 a 58 soles, ¿cuál es el precio por kWh?',
 '[{"texto":"0.50 soles","correcta":true},{"texto":"10 soles","correcta":false},{"texto":"0.58 soles","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_luz AND numero_paso=3),
 'Al trazar la recta de ajuste a mano, ¿qué se busca?',
 '[{"texto":"Que pase por todos los puntos sin excepción","correcta":false},{"texto":"Que deje aproximadamente la misma cantidad de puntos por encima y por debajo","correcta":true},{"texto":"Que pase por el primero y el último punto","correcta":false}]', 3),

-- Lo que dice el titular
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_titular AND numero_paso=3),
 '¿Cuál de estas partes de un titular es comprobable?',
 '[{"texto":"Vecinos indignados","correcta":false},{"texto":"Mantenimiento preventivo","correcta":false},{"texto":"Doce horas sin agua en seis barrios","correcta":true}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_titular AND numero_paso=3),
 'Dos medios cuentan el mismo hecho con titulares distintos y ninguno miente. ¿Cómo se explica?',
 '[{"texto":"Cada uno elige qué contar primero y con qué palabras, y eso orienta la lectura","correcta":true},{"texto":"Uno de los dos tiene que estar equivocado","correcta":false},{"texto":"Los hechos cambian según quién los mire","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_titular AND numero_paso=3),
 'En el análisis, ¿por qué se anota también "qué NO aparece" en cada versión?',
 '[{"texto":"Para llenar la tabla","correcta":false},{"texto":"Porque omitir al responsable o el motivo cambia lo que entiende quien lee, igual que decirlo","correcta":true},{"texto":"Porque lo que falta siempre es lo más importante","correcta":false}]', 3),

-- El color de la protesta
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_mural AND numero_paso=3),
 'En un mural, poner un elemento al centro y grande frente a ponerlo al fondo y pequeño:',
 '[{"texto":"Es lo mismo, solo cambia el espacio disponible","correcta":false},{"texto":"Cambia qué se lee como principal y qué como contexto","correcta":true},{"texto":"Solo importa si el mural es muy grande","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_mural AND numero_paso=3),
 'Un río pintado cada vez más delgado hacia un extremo del mural funciona como:',
 '[{"texto":"Un símbolo: representa una idea, la del agua que se agota","correcta":true},{"texto":"Un error de dibujo","correcta":false},{"texto":"Un recurso decorativo sin significado","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_mural AND numero_paso=3),
 '¿Por qué se pide limitar la paleta a tres colores como máximo?',
 '[{"texto":"Porque los murales solo admiten tres colores","correcta":false},{"texto":"Porque con pocos colores y buen contraste la imagen se lee de lejos y la idea principal no compite consigo misma","correcta":true},{"texto":"Para gastar menos pintura","correcta":false}]', 3),

-- El brazo hidráulico
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_brazo AND numero_paso=3),
 'En un sistema hidráulico cerrado, al empujar el pistón pequeño la presión:',
 '[{"texto":"Se transmite igual a todo el líquido","correcta":true},{"texto":"Se concentra solo cerca del pistón que empujas","correcta":false},{"texto":"Se pierde a lo largo de la manguera","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_brazo AND numero_paso=3),
 'El pistón grande tiene 4 veces el área del pequeño. Con la misma presión, la fuerza que ejerce es:',
 '[{"texto":"La misma","correcta":false},{"texto":"4 veces mayor","correcta":true},{"texto":"4 veces menor","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_brazo AND numero_paso=3),
 '¿Por qué una burbuja de aire en la manguera arruina el funcionamiento?',
 '[{"texto":"Porque el aire se comprime y absorbe el movimiento en vez de transmitirlo","correcta":true},{"texto":"Porque el aire pesa más que el agua","correcta":false},{"texto":"Porque tapa el paso del líquido","correcta":false}]', 3),

-- My town, my rules
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_rules AND numero_paso=3),
 '¿Cómo se dice "está prohibido correr en los pasillos"?',
 '[{"texto":"You can run in the corridors","correcta":false},{"texto":"You mustnt run in the corridors","correcta":true},{"texto":"You shouldnt run in the corridors","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_rules AND numero_paso=3),
 '"You should arrive ten minutes early" expresa:',
 '[{"texto":"Una obligación estricta","correcta":false},{"texto":"Una recomendación","correcta":true},{"texto":"Una prohibición","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_rules AND numero_paso=3),
 'Después de un modal como must, el verbo va:',
 '[{"texto":"En infinitivo sin to: You must wear","correcta":true},{"texto":"Con to: You must to wear","correcta":false},{"texto":"En gerundio: You must wearing","correcta":false}]', 3),

-- La huella del agua
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_huella AND numero_paso=3),
 '¿Qué es el agua virtual de un producto?',
 '[{"texto":"El agua que contiene el producto","correcta":false},{"texto":"El agua que se usó para producirlo, aunque no la veas ni salga de tu caño","correcta":true},{"texto":"El agua que se necesita para lavarlo","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_huella AND numero_paso=3),
 'Con los valores de referencia, ¿qué tiene mayor huella hídrica?',
 '[{"texto":"1 kg de papa","correcta":false},{"texto":"1 kg de carne de res","correcta":true},{"texto":"1 kg de arroz","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_huella AND numero_paso=3),
 'Si tu huella diaria da unos 3000 litros y el consumo directo en casa ronda los 150, ¿qué conclusión se sigue?',
 '[{"texto":"Cerrar el caño no sirve absolutamente de nada","correcta":false},{"texto":"La mayor parte de tu consumo está en lo que compras, no en tu caño, así que ahí están las decisiones de mayor efecto","correcta":true},{"texto":"Los datos deben estar mal, es imposible","correcta":false}]', 3),

-- Cuánto cuesta el agua
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_tarifa AND numero_paso=3),
 '¿Por qué la tarifa por tramos necesita una función definida por tramos?',
 '[{"texto":"Porque el precio por metro cúbico cambia según el intervalo de consumo","correcta":true},{"texto":"Porque el cargo fijo cambia cada mes","correcta":false},{"texto":"Porque la gráfica es una curva","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_tarifa AND numero_paso=3),
 'Con la tarifa del ejercicio, ¿cuánto se paga por consumir 18 m3?',
 '[{"texto":"5.20 + 13.00 + 16.80 = 35.00 soles","correcta":true},{"texto":"5.20 + 18 x 2.10 = 42.99 soles","correcta":false},{"texto":"18 x 1.30 = 23.40 soles","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_tarifa AND numero_paso=3),
 'Bajar de 27 a 24 m3 ahorra más que bajar de 20 a 17. ¿Por qué?',
 '[{"texto":"Porque los primeros metros cúbicos siempre cuestan más","correcta":false},{"texto":"Porque los metros que se dejan de consumir están en un tramo más caro","correcta":true},{"texto":"Porque el cargo fijo baja al consumir menos","correcta":false}]', 3),

-- El argumento y la falacia
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_falacia AND numero_paso=3),
 '"Quien está en contra no sabe lo que dice porque nunca ha pisado una mina" es:',
 '[{"texto":"Un ataque a la persona en lugar de a la idea","correcta":true},{"texto":"Un argumento con evidencia","correcta":false},{"texto":"Una generalización apresurada","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_falacia AND numero_paso=3),
 '"Si se aprueba esto, mañana no quedará una gota de agua en el valle" es:',
 '[{"texto":"Una pendiente resbaladiza: da por hecho un desenlace extremo sin mostrar los pasos","correcta":true},{"texto":"Un falso dilema","correcta":false},{"texto":"Una apelación a la mayoría","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_falacia AND numero_paso=3),
 '¿Por qué la réplica empieza reconociendo lo más fuerte del argumento contrario?',
 '[{"texto":"Por cortesía, aunque no aporte nada","correcta":false},{"texto":"Porque refutar la versión más fuerte, y no una caricatura, es lo que hace sólida tu propia posición","correcta":true},{"texto":"Para alargar el texto","correcta":false}]', 3),

-- Documentar el valle
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_foto AND numero_paso=3),
 '¿Qué distingue una serie fotográfica de un conjunto de fotos del mismo tema?',
 '[{"texto":"Que las fotos son de mejor calidad","correcta":false},{"texto":"Que el orden y la variedad de planos construyen algo que ninguna foto sola cuenta","correcta":true},{"texto":"Que son más de seis","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_foto AND numero_paso=3),
 'Un buen pie de foto:',
 '[{"texto":"Describe exactamente lo que ya se ve en la imagen","correcta":false},{"texto":"Aporta algo que la imagen no puede mostrar: quién, cuándo, por qué","correcta":true},{"texto":"Es siempre una sola palabra","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_foto AND numero_paso=3),
 'Antes de fotografiar a una persona en su trabajo, lo primero es:',
 '[{"texto":"Pedirle permiso explicando para qué son las fotos","correcta":true},{"texto":"Tomar las fotos y avisarle después","correcta":false},{"texto":"Pedirle que pose mirando a la cámara","correcta":false}]', 3),

-- El puente de la quebrada
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_puente AND numero_paso=3),
 'En ingeniería, las restricciones de presupuesto, materiales y carga son:',
 '[{"texto":"Obstáculos que impiden resolver bien el problema","correcta":false},{"texto":"Parte del problema: la solución válida es la que las cumple todas a la vez","correcta":true},{"texto":"Recomendaciones que se pueden ignorar si el diseño es bueno","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_puente AND numero_paso=3),
 '¿Por qué las estructuras trianguladas resisten más que las rectangulares con los mismos materiales?',
 '[{"texto":"Porque el triángulo no se deforma sin cambiar la longitud de sus lados; el rectángulo sí puede inclinarse","correcta":true},{"texto":"Porque usan más piezas","correcta":false},{"texto":"Porque pesan menos","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_puente AND numero_paso=3),
 'Anotar dónde falló el puente, y no solo cuánto aguantó, sirve para:',
 '[{"texto":"Saber qué elemento reforzar en la siguiente versión","correcta":true},{"texto":"Nada, el dato que importa es la carga máxima","correcta":false},{"texto":"Calcular el costo","correcta":false}]', 3),

-- Pitching Moquegua
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_pitch AND numero_paso=3),
 'Ante alguien que no conoce tu región, la presentación debe empezar por:',
 '[{"texto":"La solución que construiste","correcta":false},{"texto":"El problema en su contexto, para que se entienda por qué importa","correcta":true},{"texto":"Los nombres del equipo","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_pitch AND numero_paso=3),
 'Para contar cómo funciona tu proyecto ahora mismo, el tiempo verbal es:',
 '[{"texto":"Present simple: it collects, it works","correcta":true},{"texto":"Past simple: it collected, it worked","correcta":false},{"texto":"Future: it will collect","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_pitch AND numero_paso=3),
 'Te pasas del tiempo al ensayar. ¿Qué haces?',
 '[{"texto":"Hablar más rápido","correcta":false},{"texto":"Cortar contenido y quedarte con lo esencial","correcta":true},{"texto":"Pedir más tiempo el día de la presentación","correcta":false}]', 3),

-- Por qué tiembla y cuánto
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_sismo2 AND numero_paso=3),
 'La diferencia entre magnitud e intensidad es que:',
 '[{"texto":"La magnitud mide la energía liberada y es una sola; la intensidad mide los efectos y cambia según el lugar","correcta":true},{"texto":"Son dos nombres para lo mismo","correcta":false},{"texto":"La magnitud se mide después y la intensidad durante","correcta":false}]', 1),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_sismo2 AND numero_paso=3),
 'Si cada punto de magnitud multiplica la energía por unas 32 veces, un sismo de 7.0 frente a uno de 5.0 libera aproximadamente:',
 '[{"texto":"64 veces más","correcta":false},{"texto":"1024 veces más","correcta":true},{"texto":"El doble","correcta":false}]', 2),
((SELECT id FROM modulo_pasos WHERE modulo_id=@m_sismo2 AND numero_paso=3),
 'Dos casas a la misma distancia del epicentro sufren daños muy distintos. La causa más probable es:',
 '[{"texto":"El tipo de suelo y cómo está construida cada una","correcta":true},{"texto":"Que la magnitud fue distinta en cada casa","correcta":false},{"texto":"Que el sismo llegó antes a una que a otra","correcta":false}]', 3);

-- ── Rúbrica para los módulos nuevos ──────────────────────────────
-- Los mismos cuatro criterios de la migración 012. Se repite aquí
-- porque aquella ya se ejecutó y no volverá a correr: sin esto los doce
-- módulos de secundaria se calificarían con un número suelto.
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
