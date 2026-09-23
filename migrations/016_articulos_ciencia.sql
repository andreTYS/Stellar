-- ================================================================
-- INNOVA-STEAM Migration 016 — Artículos de la biblioteca de ciencia
--
-- Dieciocho artículos con el formato del Space Place de la NASA: el
-- título ES la pregunta, la respuesta cabe en una frase, y luego se
-- explica en párrafos cortos. Cada uno lleva un diagrama que explica
-- de verdad y algo que se puede hacer con lo que hay en casa.
--
-- Donde encaja de forma natural, la pregunta se ancla en Moquegua: por
-- qué el agua hierve antes en Carumas, por qué julio es invierno aquí
-- y diciembre verano, por qué el sol quema tanto a 3 000 metros. No es
-- adorno local: son preguntas que un estudiante de aquí ya se hizo.
-- ================================================================

SET NAMES utf8mb4;

SET @t_sol   = (SELECT id FROM ciencia_temas WHERE slug = 'sol');
SET @t_tie   = (SELECT id FROM ciencia_temas WHERE slug = 'tierra');
SET @t_cie   = (SELECT id FROM ciencia_temas WHERE slug = 'cielo');
SET @t_sis   = (SELECT id FROM ciencia_temas WHERE slug = 'sistema');
SET @t_cot   = (SELECT id FROM ciencia_temas WHERE slug = 'cotidiana');

-- ── El Sol ───────────────────────────────────────────────────────

INSERT IGNORE INTO ciencia_articulos
 (tema_id, slug, pregunta, respuesta_corta, cuerpo, diagrama, dato_curioso, actividad, nivel, minutos_lectura, orden) VALUES

(@t_sol, 'que-es-el-sol', '¿Qué es el Sol?',
 'El Sol es una estrella: una bola gigante de gas tan caliente y tan apretada por su propio peso que en el centro fabrica luz.',
 '["Desde aquí se ve como un disco pequeño y amarillo, pero eso es solo porque está lejísimos. Dentro del Sol cabría un millón de Tierras.",
   "No es una bola de fuego. El fuego necesita algo que arda y oxígeno, y en el Sol no hay ninguna de las dos cosas. Lo que pasa en su centro se llama fusión: la presión es tan bestial que los núcleos de hidrógeno se pegan entre sí y forman helio, y en ese pegarse sobra energía. Esa energía es la luz que te llega a la cara.",
   "El Sol tiene capas, como una cebolla. En el centro está el núcleo, a unos quince millones de grados. Más afuera la energía sube poco a poco hasta la fotosfera, que es la superficie que vemos, a unos cinco mil quinientos grados. Y todavía más afuera está la corona, que solo se ve durante un eclipse.",
   "Lo más raro es el tiempo: la energía que se fabrica hoy en el núcleo tarda decenas de miles de años en salir a la superficie. La luz que te llega ahora empezó su camino antes de que existieran las ciudades."]',
 'sol-capas',
 'El Sol convierte cada segundo unos cuatro millones de toneladas de su propia masa en energía. Lleva haciéndolo unos 4 600 millones de años y le queda combustible para otro tanto.',
 '{"titulo":"Mide el Sol sin mirarlo","materiales":["Una caja de zapatos o un tubo de cartón","Papel aluminio","Una aguja","Una hoja blanca","Regla"],"pasos":["Tapa un extremo del tubo con papel aluminio y hazle un agujerito con la aguja.","Tapa el otro extremo con la hoja blanca.","De espaldas al Sol, apunta el agujero hacia él. En la hoja blanca aparecerá un disco de luz: es la imagen del Sol.","NUNCA mires al Sol por el tubo ni a simple vista. Solo miras la hoja.","Mide el disco y mide el largo del tubo. El Sol es unas 107 veces más ancho de lo que parece en esa proporción."]}',
 'ambos', 4, 1),

(@t_sol, 'que-es-una-mancha-solar', '¿Qué es una mancha solar?',
 'Es una zona de la superficie del Sol que está más fría que el resto, porque un nudo de campo magnético le frena el calor que sube desde abajo.',
 '["En la superficie del Sol el gas caliente sube, se enfría un poco y vuelve a bajar, como el agua hirviendo en una olla. Ese movimiento trae calor desde el interior sin parar.",
   "A veces el campo magnético del Sol se retuerce y sale a la superficie formando arcos. Donde eso pasa, el gas no puede circular con libertad y sube menos calor. Esa zona queda a unos 3 800 grados, mientras el resto está a 5 500.",
   "Y sigue siendo altísimo. Una mancha solar se ve oscura solo por contraste: si pudieras recortarla y ponerla sola en el cielo, brillaría más que la Luna llena.",
   "Las manchas no son fijas. Aparecen, duran de días a semanas y desaparecen. Y el número total sube y baja siguiendo un ciclo de unos once años."]',
 'mancha-solar',
 'Una mancha solar mediana es más grande que toda la Tierra. Las más grandes se pueden ver sin telescopio, con un filtro solar adecuado, como un punto oscuro en el disco.',
 '{"titulo":"Cuenta manchas como los astrónomos","materiales":["El tubo del artículo anterior, o unos binoculares con filtro solar","Cuaderno","Lápiz"],"pasos":["Proyecta la imagen del Sol sobre una hoja blanca. Nunca mires directo.","Dibuja el círculo del Sol y marca los puntos oscuros que veas.","Anota la fecha y cuántas manchas contaste.","Repite cada día despejado durante dos semanas.","Compara: las manchas se habrán movido de un lado al otro. Eso es el Sol girando sobre sí mismo, y tarda unos 27 días en dar la vuelta."]}',
 'ambos', 3, 2),

(@t_sol, 'tormenta-solar', '¿Qué es una tormenta solar y por qué me importa?',
 'Es una nube enorme de partículas que el Sol lanza al espacio; cuando choca con el campo magnético de la Tierra puede apagar radios, desviar el GPS y hasta dañar la red eléctrica.',
 '["Cuando el campo magnético retorcido de una mancha se rompe de golpe, suelta muchísima energía. A eso se le llama fulguración, y su luz llega a la Tierra en ocho minutos.",
   "A veces, junto con la fulguración, el Sol suelta también una nube gigantesca de gas magnetizado. Esa nube no viaja a la velocidad de la luz: tarda entre uno y tres días en llegar. Y ese retraso es una suerte, porque da tiempo de avisar.",
   "Cuando llega, empuja y sacude el campo magnético de la Tierra. La radio de onda corta deja de funcionar porque la capa alta de la atmósfera en la que rebota se altera. El GPS empieza a marcar posiciones equivocadas. Y en la red eléctrica pueden aparecer corrientes que recalientan los transformadores.",
   "El campo magnético de la Tierra nos protege de la mayor parte. Sin él, la vida en la superficie sería muy distinta."]',
 'tormenta-solar',
 'En 1989 una tormenta geomagnética dejó sin luz a la provincia de Quebec, en Canadá: seis millones de personas durante nueve horas, en pleno invierno.',
 '{"titulo":"Revisa el estado del Sol hoy","materiales":["El portal de StellarScribe de esta plataforma","Cuaderno"],"pasos":["Entra al portal y anota tres cifras: manchas solares, velocidad del viento solar e índice Kp.","El índice Kp va de 0 a 9 y mide cuánto se está sacudiendo el campo magnético. Por encima de 5 ya se considera tormenta.","Repite la anotación cada día durante dos semanas.","Haz un gráfico de líneas con el Kp. ¿Hubo algún pico? ¿Coincidió con más manchas los días anteriores?"]}',
 'ambos', 4, 3),

(@t_sol, 'sol-quema-mas-altura', '¿Por qué el sol quema más en la sierra que en la playa?',
 'Porque arriba hay menos aire encima de ti, y el aire es justamente lo que frena la radiación ultravioleta.',
 '["Sobre tu cabeza hay una columna de aire que llega hasta el espacio. Ese aire absorbe parte de la radiación del Sol antes de que te alcance, sobre todo la ultravioleta, que es la que quema la piel.",
   "En Ilo, al nivel del mar, esa columna es la más gruesa posible. En Carumas, a unos 3 000 metros, te has saltado buena parte de ella: hay menos aire por encima y llega más radiación.",
   "La cuenta aproximada es que la radiación ultravioleta sube entre un 10 y un 12 por ciento por cada mil metros de altura. A 3 000 metros eso significa alrededor de un tercio más que al nivel del mar.",
   "Y hay una trampa: en la sierra suele hacer fresco, así que el cuerpo no avisa. Uno no siente calor y se quema igual. Por eso las quemaduras solares serias son más frecuentes arriba que en la playa."]',
 'uv-altitud',
 'La nieve refleja hasta el 80 por ciento de la radiación ultravioleta que le llega. Por eso en la puna uno puede quemarse por debajo del mentón y hasta dentro de la nariz.',
 '{"titulo":"Detecta lo invisible con papel fotosensible","materiales":["Cartulina de color intenso (roja o azul oscuro)","Tijeras","Objetos pequeños: llave, moneda, hoja de planta","Un día despejado"],"pasos":["Corta dos rectángulos iguales de cartulina.","Pon unos objetos encima de cada uno, dejando sombras bien marcadas.","Deja uno al sol directo y el otro a la sombra durante tres o cuatro horas.","Retira los objetos y compara. El que estuvo al sol tendrá la silueta marcada: la luz decoloró el resto.","Si puedes, repite el experimento otro día con el cielo nublado y compara cuánto tarda."]}',
 'ambos', 3, 4),

-- ── La Tierra ────────────────────────────────────────────────────

(@t_tie, 'dia-y-noche', '¿Por qué hay día y noche?',
 'Porque la Tierra gira sobre sí misma: siempre tiene una mitad iluminada por el Sol y otra a oscuras, y cada punto va pasando por las dos.',
 '["El Sol no se mueve alrededor nuestro, aunque lo parezca. Lo que se mueve es la Tierra, que da una vuelta completa sobre su propio eje cada 24 horas.",
   "Imagina una pelota iluminada por una linterna en un cuarto oscuro. Media pelota está iluminada y media no, siempre. Si haces girar la pelota, un punto cualquiera va entrando y saliendo de la luz. Eso es exactamente el día y la noche.",
   "La línea que separa el día de la noche se mueve sobre la superficie. Cuando esa línea pasa por Moquegua al atardecer, en ese mismo momento está amaneciendo en otro lugar del mundo.",
   "Por eso el Sol parece salir por el este: la Tierra gira hacia el este, y al girar vamos entrando en la zona iluminada por ese lado."]',
 'dia-noche',
 'La Tierra gira a unos 1 670 kilómetros por hora en el ecuador. No lo notamos porque todo gira con nosotros: el aire, el mar y hasta el edificio en el que estás.',
 '{"titulo":"El día y la noche con una linterna","materiales":["Una pelota o naranja","Una linterna","Un plumón o una calcomanía","Un cuarto que se pueda oscurecer"],"pasos":["Marca un punto en la pelota con el plumón: ese eres tú, en Moquegua.","Oscurece el cuarto y apunta la linterna a la pelota sin moverla. Es el Sol.","Gira la pelota despacio hacia tu izquierda y sigue tu punto con la vista.","Anota en qué momento tu punto entra en la luz, cuándo está justo en el medio y cuándo sale.","Eso es amanecer, mediodía y atardecer. Comprueba que el punto entra siempre por el mismo lado."]}',
 'ambos', 3, 1),

(@t_tie, 'invierno-en-julio', '¿Por qué en el Perú hace frío en julio y calor en enero?',
 'Porque la Tierra tiene el eje inclinado. En julio el hemisferio sur, donde estamos, queda apuntando hacia el lado contrario al Sol, y recibe su luz más de costado.',
 '["Casi todos piensan que las estaciones son porque la Tierra se acerca o se aleja del Sol. Es falso, y hay una prueba fácil de recordar: la Tierra está en su punto MÁS CERCANO al Sol a comienzos de enero, que es justo cuando aquí hace más calor y en Europa más frío.",
   "Lo que manda es la inclinación. El eje de la Tierra está torcido unos 23 grados y medio, y apunta siempre hacia el mismo lado del espacio mientras damos la vuelta al Sol.",
   "En diciembre el hemisferio sur queda inclinado hacia el Sol: la luz cae más de frente, se reparte en menos superficie y calienta más. Además el día dura más horas. Eso es el verano.",
   "En junio y julio pasa lo contrario: la luz llega más de costado, se reparte en más superficie y calienta menos, y el día es más corto. Eso es el invierno. Al mismo tiempo, en el hemisferio norte están en verano: por eso las postales de Navidad con nieve nunca se parecieron a nuestras Navidades."]',
 'estaciones',
 'La diferencia de distancia al Sol entre enero y julio es de unos cinco millones de kilómetros, que suena a muchísimo pero es apenas un 3 por ciento. El efecto de la inclinación es muchísimo mayor.',
 '{"titulo":"Demuestra que es la inclinación","materiales":["Una linterna","Una hoja cuadriculada","Un libro para inclinar la hoja","Lápiz"],"pasos":["Apoya la hoja en la mesa y alumbra de frente desde arriba, a un palmo de distancia. Marca con el lápiz el borde del círculo de luz y cuenta cuántos cuadraditos abarca.","Ahora inclina la hoja unos 45 grados con el libro y alumbra desde el mismo sitio y la misma distancia.","Vuelve a marcar el borde y cuenta los cuadraditos.","La misma luz ahora se reparte entre muchos más cuadraditos: por eso calienta menos cada uno.","Eso es exactamente lo que le pasa a Moquegua en julio."]}',
 'ambos', 4, 2),

(@t_tie, 'por-que-tiembla', '¿Por qué tiembla tanto en la costa del Perú?',
 'Porque justo frente a nuestra costa, bajo el mar, una placa de la corteza terrestre se está metiendo debajo de otra, y esa presión se libera de golpe.',
 '["La capa exterior de la Tierra no es una sola pieza: está partida en placas que se mueven muy despacio, unos centímetros al año, como uñas creciendo.",
   "Frente al Perú, la placa de Nazca se mete por debajo de la placa Sudamericana. No entra suave: se traba. Y mientras está trabada, la presión se va acumulando durante décadas.",
   "Cuando la roca ya no aguanta, se rompe de golpe y todo ese movimiento acumulado se libera en segundos. Eso es un sismo. Y como Moquegua está encima de esa zona de choque, los temblores son parte de vivir aquí.",
   "Ese mismo empujón, sostenido durante millones de años, es lo que levantó los Andes. La cordillera que ves desde tu ventana es el resultado de dos placas empujándose."]',
 'placas',
 'El sismo de junio de 2001 frente a la costa de Arequipa tuvo magnitud 8.4 y se sintió con fuerza en Moquegua y Tacna. Fue uno de los más grandes registrados en el Perú.',
 '{"titulo":"Provoca tu propio sismo","materiales":["Dos tablas o dos cartones gruesos","Una mesa","Arena, harina o azúcar","Piezas pequeñas para hacer casitas"],"pasos":["Pon las dos tablas juntas sobre la mesa, tocándose por un borde.","Encima esparce una capa de arena y construye casitas con las piezas.","Empuja las dos tablas una contra la otra, despacio pero sin parar.","Verás que al principio no pasa nada: la presión se acumula. Después se mueven de golpe y las casitas se caen.","Prueba otra vez construyendo las casitas más bajas y más anchas. ¿Aguantan más?"]}',
 'ambos', 4, 3),

(@t_tie, 'agua-hierve-antes', '¿Por qué el agua hierve antes en Carumas que en Ilo?',
 'Porque arriba hay menos aire empujando hacia abajo sobre el agua, y al agua le cuesta menos escaparse en forma de vapor.',
 '["Hervir no es lo mismo que calentarse. El agua hierve cuando sus moléculas tienen suficiente energía para escaparse al aire formando burbujas de vapor dentro del líquido.",
   "Pero para escaparse tienen que vencer el peso del aire que hay encima. A eso le llamamos presión atmosférica: toda la columna de aire que llega hasta el espacio, apretando hacia abajo.",
   "Al nivel del mar esa columna es la más pesada posible, y el agua necesita llegar a 100 grados para vencerla. En Carumas, a 3 000 metros, hay mucho menos aire encima y le basta con unos 90 grados.",
   "Eso tiene una consecuencia práctica que cualquiera en la sierra conoce: el agua hierve antes, pero la comida tarda MÁS en cocinarse. Porque lo que cocina no es la burbuja: es la temperatura. Y 90 grados cocinan más lento que 100."]',
 'presion-altitud',
 'Por eso en la sierra se usa la olla a presión: al cerrarla herméticamente, el vapor no puede escapar y sube la presión adentro. El agua vuelve a hervir a más de 100 grados y la comida se cocina rápido.',
 '{"titulo":"Compara dos cocciones","materiales":["Una olla","Agua","Dos papas del mismo tamaño","Un reloj","Un termómetro de cocina, si tienes"],"pasos":["Pon a hervir agua y anota el momento exacto en que empiezan las burbujas. Si tienes termómetro, anota la temperatura.","Echa una papa y mide cuánto tarda en estar cocida, pinchándola cada cinco minutos.","Si puedes, pide a alguien de otra altitud que haga lo mismo y comparen los tiempos.","Anota la altitud de tu casa. En Moquegua ciudad son unos 1 400 metros; en Carumas, más de 3 000.","Escribe tu conclusión: ¿a qué altura tarda más en cocinarse y por qué?"]}',
 'ambos', 4, 4),

-- ── El cielo de noche ────────────────────────────────────────────

(@t_cie, 'fases-de-la-luna', '¿Por qué la Luna cambia de forma?',
 'No cambia de forma. La Luna siempre tiene media cara iluminada por el Sol; lo que cambia es cuánto de esa mitad podemos ver desde aquí.',
 '["La Luna no brilla por sí misma: refleja la luz del Sol. Y como es una bola, el Sol siempre le ilumina exactamente la mitad, igual que a la Tierra.",
   "Lo que cambia es nuestra posición. Mientras la Luna da la vuelta a la Tierra, vamos viendo esa mitad iluminada desde distintos ángulos.",
   "Cuando la Luna está entre el Sol y nosotros, su cara iluminada mira hacia el otro lado y no vemos nada: es luna nueva. Cuando está al otro lado, vemos la mitad iluminada completa: es luna llena. En medio vemos porciones, y de ahí los cuartos y las medialunas.",
   "El ciclo completo dura unos 29 días y medio. Por eso los meses del calendario duran más o menos eso: vienen de contar lunas."]',
 'fases-luna',
 'La Luna siempre nos muestra la misma cara. Tarda exactamente lo mismo en girar sobre sí misma que en dar la vuelta a la Tierra, así que la cara oculta no se vio nunca hasta que una sonda la fotografió en 1959.',
 '{"titulo":"Las fases con una naranja","materiales":["Una naranja o pelota","Una lámpara sin pantalla","Un cuarto oscuro"],"pasos":["Pon la lámpara encendida en el centro del cuarto: es el Sol. Tú eres la Tierra.","Sostén la naranja con el brazo estirado y ponte de espaldas a la lámpara. Verás la naranja completamente iluminada: luna llena.","Gira despacio sobre ti mismo sin bajar el brazo, siguiendo la naranja con la vista.","Verás aparecer el cuarto menguante, la luna nueva y el creciente, en ese orden.","Fíjate en lo importante: la naranja nunca cambió. Solo cambió desde dónde la mirabas."]}',
 'ambos', 3, 1),

(@t_cie, 'via-lactea', '¿Qué es esa franja blanca que cruza el cielo?',
 'Es nuestra propia galaxia vista desde dentro. Vivimos en un disco de estrellas y, al mirar a lo largo del disco, vemos tantas juntas que se funden en una banda.',
 '["La Vía Láctea es la galaxia donde vivimos: unos cien mil millones de estrellas girando juntas en forma de disco con brazos en espiral.",
   "Nosotros estamos dentro, en uno de los brazos, a unos 26 mil años luz del centro. No podemos verla desde afuera, igual que no puedes ver tu casa entera estando en la sala.",
   "Cuando miramos hacia arriba en una dirección cualquiera, vemos pocas estrellas. Pero cuando miramos a lo largo del disco, hay tantísimas una detrás de otra que el ojo ya no las separa: se ven como un río blanco.",
   "Desde Moquegua se ve especialmente bien, sobre todo en invierno y lejos de las luces de la ciudad. En muchas ciudades del mundo ya no se ve en absoluto."]',
 'via-lactea',
 'La luz que ves del centro de la galaxia salió hace unos 26 mil años, cuando en la Tierra todavía no existía la agricultura.',
 '{"titulo":"Mide cuánta luz te roba la ciudad","materiales":["Cuaderno","Una noche despejada sin luna","Paciencia: 15 minutos sin mirar pantallas"],"pasos":["Sal a un lugar oscuro y espera quince minutos para que tus ojos se adapten. Es el paso que todos se saltan y el que más importa.","Busca la franja de la Vía Láctea y anota si la ves, si la ves apenas o si no la ves.","Cuenta cuántas estrellas distingues dentro de un recuadro que armes con los dedos.","Repite el conteo en otro lugar: cerca de un poste de luz, en la plaza, en el campo.","Compara los números. La diferencia es la contaminación lumínica, y es reversible: basta con apuntar las luces hacia abajo."]}',
 'ambos', 3, 2),

(@t_cie, 'constelaciones-oscuras', '¿Los antiguos andinos veían las mismas constelaciones que nosotros?',
 'No. Además de unir estrellas con líneas, los pueblos andinos dieron nombre a las manchas OSCURAS de la Vía Láctea: la llama, el zorro, el sapo, la serpiente.',
 '["Las constelaciones que salen en los libros vienen casi todas de Grecia y de Mesopotamia: se hacen uniendo estrellas brillantes con líneas imaginarias, como un juego de puntos.",
   "En los Andes se hizo algo distinto y muy poco común en el mundo: se miró el espacio ENTRE las estrellas. Dentro de la franja de la Vía Láctea hay manchas oscuras bien marcadas, y a esas se les puso nombre.",
   "La más conocida es la Yacana, la llama, con sus dos ojos brillantes. También están el Atoq, el zorro; el Hanpatu, el sapo; y el Machacuay, la serpiente. Se ven a simple vista y siguen ahí cada noche despejada.",
   "Y no son huecos vacíos. Son nubes de polvo y gas frío que tapan la luz de las estrellas que hay detrás. Los astrónomos las llaman nebulosas oscuras, y son justamente las guarderías donde se están formando estrellas nuevas."]',
 'constelaciones-oscuras',
 'La Yacana se ve mejor entre abril y julio. En el calendario agrícola andino, la posición de estas figuras servía para decidir cuándo sembrar y cuándo cosechar.',
 '{"titulo":"Dibuja el cielo con los dos métodos","materiales":["Cuaderno y lápiz","Una noche despejada, mejor entre abril y julio","Un lugar oscuro"],"pasos":["Deja que tus ojos se adapten quince minutos.","Primero busca la Cruz del Sur, que se hace uniendo cuatro estrellas. Dibújala.","Ahora haz lo contrario: mira la franja de la Vía Láctea y dibuja solo las manchas oscuras, sin marcar ninguna estrella.","Intenta reconocer la llama: es alargada y tiene dos estrellas brillantes juntas donde estarían los ojos.","Pregunta a alguien mayor de tu familia si conoce esos nombres. Muchas veces la respuesta sorprende."]}',
 'ambos', 4, 3),

(@t_cie, 'estrellas-titilan', '¿Por qué las estrellas titilan y los planetas no?',
 'Porque una estrella está tan lejos que se ve como un punto, y cualquier movimiento del aire desvía ese único rayo. Un planeta se ve como un disco diminuto, y los desvíos se compensan entre sí.',
 '["La luz que nos llega de una estrella atraviesa toda la atmósfera antes de entrar en tu ojo. Y la atmósfera no está quieta: hay capas de aire a distintas temperaturas moviéndose sin parar.",
   "Cada vez que la luz cruza de una capa a otra se desvía un poquito. Como la estrella es un punto perfecto, todo su brillo depende de ese único rayo: si se desvía, la ves desplazarse y cambiar de brillo. Eso es el titileo.",
   "Un planeta está muchísimo más cerca, así que no es un punto: es un disco pequeñísimo. De él llegan muchos rayos a la vez desde puntos ligeramente distintos. Unos se desvían hacia un lado, otros hacia el otro, y el promedio se mantiene estable.",
   "Por eso hay un truco de campo que funciona siempre: si parpadea, es estrella; si tiene luz firme, es planeta."]',
 'titilar',
 'Cuanto más cerca del horizonte, más titila una estrella: su luz tiene que atravesar mucho más aire en diagonal que si estuviera justo encima de tu cabeza.',
 '{"titulo":"Encuentra un planeta esta noche","materiales":["Una noche despejada","Cuaderno"],"pasos":["Sal cuando ya esté oscuro y busca los puntos más brillantes del cielo.","Míralos con atención un minuto entero cada uno. Anota cuáles parpadean y cuáles no.","Los que tienen luz firme y no parpadean son casi seguro planetas: Venus, Júpiter, Marte o Saturno.","Comprueba tu respuesta otra noche: los planetas se habrán movido respecto a las estrellas de alrededor. De ahí viene la palabra planeta, que en griego significa errante.","Fíjate también en el color: Marte tira a anaranjado y Venus es blanco intenso."]}',
 'ambos', 3, 4),

-- ── El sistema solar ─────────────────────────────────────────────

(@t_sis, 'que-hay-sistema-solar', '¿Qué hay en el sistema solar?',
 'Una estrella, el Sol, y todo lo que gira a su alrededor: ocho planetas, sus lunas, millones de asteroides y miles de millones de cometas.',
 '["Casi todo el material del sistema solar está en el Sol: se lleva el 99,8 por ciento de toda la masa. Los planetas, las lunas y todo lo demás se reparten lo que sobró.",
   "Los cuatro primeros planetas —Mercurio, Venus, la Tierra y Marte— son rocosos, pequeños y están relativamente cerca del Sol. Después viene un cinturón de asteroides, y luego los cuatro gigantes: Júpiter, Saturno, Urano y Neptuno, hechos sobre todo de gas y hielo.",
   "Todo gira en el mismo sentido y casi en el mismo plano, como los surcos de un disco. Eso no es casualidad: es la huella de la nube de gas y polvo que giraba y de la que se formó todo hace unos 4 600 millones de años.",
   "Y sigue habiendo cosas más allá de Neptuno: el cinturón de Kuiper, donde está Plutón, y muchísimo más lejos una nube de cometas que envuelve todo el sistema."]',
 'sistema-solar',
 'Si el Sol fuera una pelota de fútbol, la Tierra sería una bolita de un milímetro a unos 26 metros de distancia, y Neptuno estaría a casi 800 metros. Los dibujos nunca están a escala porque no cabrían.',
 '{"titulo":"El sistema solar a escala en la calle","materiales":["Una pelota de fútbol","Papel y tijeras","Una cinta métrica o pasos contados","Una calle o cancha larga"],"pasos":["Pon la pelota en un extremo: es el Sol.","Recorta bolitas de papel del tamaño correcto: Mercurio y Marte casi invisibles, la Tierra de 1 mm, Júpiter de 1 cm.","Camina y coloca cada planeta a su distancia: Mercurio a 10 pasos, Venus a 19, la Tierra a 26, Marte a 40, Júpiter a 135, Saturno a 250, Urano a 500 y Neptuno a 780.","Cuando llegues a Neptuno, date la vuelta y busca la pelota. Casi no se ve.","Esa sensación es el dato más importante del sistema solar: está casi todo vacío."]}',
 'ambos', 4, 1),

(@t_sis, 'pluton-planeta', '¿Por qué Plutón dejó de ser planeta?',
 'Porque se encontraron muchos otros cuerpos parecidos en su misma zona, y hubo que decidir: o eran todos planetas, o ninguno.',
 '["Cuando Plutón se descubrió, en 1930, parecía un cuerpo único y solitario allá afuera. Durante setenta años fue el noveno planeta y así lo aprendieron varias generaciones.",
   "Pero a partir de los años noventa los telescopios empezaron a encontrar más objetos en esa misma región, el cinturón de Kuiper. Y en 2005 apareció Eris, con una masa parecida a la de Plutón.",
   "Ahí surgió el problema: si Plutón era planeta, Eris también, y detrás venían decenas de candidatos. La lista de planetas iba a crecer sin final. En 2006 los astrónomos acordaron una definición con tres condiciones: girar alrededor del Sol, ser lo bastante grande como para que su gravedad lo vuelva redondo, y haber despejado su órbita de otros cuerpos.",
   "Plutón cumple las dos primeras pero no la tercera: comparte su zona con miles de objetos. Así que pasó a llamarse planeta enano. No se hizo más pequeño ni desapareció: cambió el nombre de la casilla, porque aprendimos más."]',
 'pluton',
 'La nave New Horizons pasó junto a Plutón en 2015 y descubrió montañas de hielo de agua de más de tres kilómetros de alto y una enorme llanura con forma de corazón. Resultó mucho más interesante de lo que nadie esperaba.',
 '{"titulo":"Discutan la definición","materiales":["Cuaderno","Un compañero o la familia"],"pasos":["Escribe las tres condiciones que debe cumplir un planeta.","Aplícaselas a la Tierra, a Júpiter, a Plutón, a la Luna y a Ceres, el mayor asteroide.","Anota cuáles cumplen y cuáles no, y por qué.","Ahora discute: ¿te parece justa la tercera condición? ¿Qué pasaría si la quitáramos?","Escribe tu propia definición de planeta en una frase y defiéndela con un argumento. En ciencia las definiciones también se discuten."]}',
 'secundaria', 4, 2),

(@t_sis, 'que-es-un-eclipse', '¿Qué es un eclipse?',
 'Es la sombra de un cuerpo cayendo sobre otro: en el eclipse de Sol la Luna nos tapa el Sol, y en el de Luna es la Tierra la que le tapa la luz a la Luna.',
 '["Para que haya eclipse tienen que alinearse tres cuerpos: el Sol, la Tierra y la Luna. Y eso no pasa todos los meses, porque la órbita de la Luna está un poco inclinada respecto a la de la Tierra: casi siempre pasa por encima o por debajo.",
   "En el eclipse de Sol, la Luna se cruza entre el Sol y nosotros y proyecta su sombra sobre una franja estrecha de la Tierra. Si estás dentro de esa franja, ves el Sol taparse por completo y aparecer la corona. Fuera de ella solo lo ves mordido.",
   "En el eclipse de Luna es al revés: la Tierra se pone en medio y su sombra cae sobre la Luna. Este se ve desde todo el lado nocturno del planeta a la vez, y por eso es mucho más común presenciarlo.",
   "Y hay un detalle precioso: durante el eclipse la Luna no se pone negra, sino rojiza. Es porque la atmósfera de la Tierra desvía algo de luz solar hacia ella, y deja pasar sobre todo la roja. Estás viendo, proyectados en la Luna, todos los amaneceres y atardeceres de la Tierra a la vez."]',
 'eclipse',
 'Es pura casualidad que podamos ver eclipses totales de Sol: la Luna es unas 400 veces más pequeña que el Sol, pero está unas 400 veces más cerca, así que en el cielo se ven casi del mismo tamaño.',
 '{"titulo":"Un eclipse en la mesa","materiales":["Una linterna potente","Una pelota grande y una pequeña","Una regla","Un cuarto oscuro"],"pasos":["La linterna es el Sol, la pelota grande la Tierra y la pequeña la Luna.","Pon la pelota pequeña entre la linterna y la grande. Busca la sombra que cae sobre la grande: ese es el eclipse de Sol.","Fíjate en lo pequeña que es esa sombra. Por eso un eclipse total solo se ve desde una franja estrecha.","Ahora pon la pelota grande en medio. La pequeña queda a oscuras: ese es el eclipse de Luna.","Prueba a subir y bajar la pelota pequeña. Verás que casi siempre la sombra no da. Por eso no hay eclipse cada mes."]}',
 'ambos', 4, 3),

-- ── Ciencia de todos los días ────────────────────────────────────

(@t_cot, 'cielo-azul', '¿Por qué el cielo es azul y el atardecer es rojo?',
 'Porque el aire dispersa mucho más la luz azul que la roja. De día ves el azul rebotado por todo el cielo; al atardecer la luz cruza tanto aire que el azul se pierde por el camino.',
 '["La luz del Sol parece blanca pero es una mezcla de todos los colores. Cuando entra en la atmósfera, choca con las moléculas de aire y se dispersa: sale rebotada en todas direcciones.",
   "Pero no todos los colores rebotan igual. Los de onda corta, como el azul y el violeta, se dispersan muchísimo más que los de onda larga, como el rojo. El azul rebota tantas veces que acaba llegándote desde todas partes del cielo, y por eso lo ves azul.",
   "Al atardecer el Sol está bajo y su luz tiene que atravesar mucho más aire en diagonal para llegar a ti. En ese recorrido tan largo el azul se dispersa tanto que se pierde antes de llegar, y lo que queda es lo que menos se dispersa: el naranja y el rojo.",
   "Y si te preguntas por el violeta, que se dispersa aún más que el azul: se dispersa tanto que buena parte se pierde arriba, y además nuestros ojos son mucho menos sensibles a él."]',
 'cielo-azul',
 'En la Luna no hay atmósfera, así que no hay nada que disperse la luz. El cielo lunar es negro incluso de día, con el Sol brillando.',
 '{"titulo":"Fabrica un atardecer en un vaso","materiales":["Un vaso alto de vidrio transparente","Agua","Un poco de leche","Una linterna potente o la del celular","Un cuarto oscuro"],"pasos":["Llena el vaso de agua y añade media cucharadita de leche. Remueve.","En el cuarto oscuro, alumbra el vaso desde un lado con la linterna pegada al vidrio.","Mira el vaso DESDE ARRIBA y de costado: el agua se ve azulada. Esa es la luz azul dispersada por las partículas de la leche.","Ahora ponte al otro lado y mira la linterna A TRAVÉS del vaso: la verás anaranjada.","Añade más leche y vuelve a mirar: el naranja se hace más intenso, igual que un atardecer con más polvo en el aire."]}',
 'ambos', 4, 1),

(@t_cot, 'de-que-estan-hechas-las-estrellas', '¿Cómo sabemos de qué están hechas las estrellas si nadie ha ido?',
 'Por su luz. Al separarla en colores aparecen rayas oscuras, y cada elemento químico deja un patrón de rayas distinto, como una huella digital.',
 '["Si haces pasar la luz de una estrella por un prisma, se abre en todos sus colores. Pero el arcoíris que sale no es continuo: tiene rayas oscuras finísimas en posiciones muy concretas.",
   "Esas rayas son colores que faltan. Los átomos de las capas exteriores de la estrella se tragaron justo esos colores, porque cada tipo de átomo solo puede absorber ciertas energías muy precisas.",
   "Y aquí está lo bueno: el patrón de rayas de cada elemento es siempre el mismo, aquí y a mil años luz. El hidrógeno deja sus rayas en un sitio, el sodio en otro, el hierro en otro. Se comparan con las que se miden en un laboratorio y se sabe qué hay allá.",
   "Esa técnica se llama espectroscopía y es una de las herramientas más potentes de la ciencia. Con ella también sabemos a qué velocidad se aleja una galaxia y si un planeta lejano tiene atmósfera."]',
 'espectro',
 'El helio se descubrió en el Sol antes que en la Tierra. En 1868 apareció una raya que no correspondía a ningún elemento conocido y le pusieron helio, por Helios, el sol en griego. En la Tierra no se encontró hasta 27 años después.',
 '{"titulo":"Separa la luz en casa","materiales":["Un CD o DVD viejo","Una caja de cartón pequeña","Cutter o tijeras","Cinta"],"pasos":["Haz una ranura fina y recta en un extremo de la caja: por ahí entrará la luz.","Haz un agujero para mirar en otra cara de la caja, en ángulo.","Pega el CD dentro, con la cara brillante hacia la ranura.","Apunta la ranura a distintas luces y mira por el agujero: verás el espectro.","Compara la luz del Sol reflejada en una pared, un foco normal y uno LED o fluorescente. El del Sol es continuo; los LED y fluorescentes muestran rayas o huecos. Dibuja lo que ves en cada caso."]}',
 'secundaria', 4, 2),

(@t_cot, 'globo-helio', '¿Por qué un globo de helio sube y uno de aire no?',
 'Porque el helio pesa menos que el aire que el globo aparta. Todo lo que pesa menos que el aire que desaloja, sube.',
 '["Estamos dentro de un océano de aire, y el aire pesa. Un metro cúbico de aire pesa alrededor de 1,2 kilos, aunque no lo notes.",
   "Cuando metes cualquier cosa en el aire, esa cosa aparta un volumen de aire. El aire de alrededor empuja hacia arriba con una fuerza igual al peso del aire apartado. Eso es el empuje, y funciona igual en el agua.",
   "Si lo que metiste pesa MÁS que el aire que apartó, gana el peso y cae. Si pesa MENOS, gana el empuje y sube. El helio pesa unas siete veces menos que el aire, así que un globo de helio sube. Un globo inflado con tu propio aliento pesa lo mismo que el aire que aparta, más el peso del plástico: cae.",
   "Y esto explica algo que se ve en Moquegua: un globo de helio no sube para siempre. A medida que asciende, el aire de alrededor es cada vez menos denso, hasta que el empuje ya no le gana al peso. Ahí se queda."]',
 'flotabilidad',
 'Los globos meteorológicos se inflan flojos a propósito. Al subir, la presión exterior baja y el globo se hincha cada vez más, hasta reventar a unos 30 kilómetros de altura, después de haber enviado datos todo el camino.',
 '{"titulo":"Pesa el aire","materiales":["Una regla o palo de un metro","Hilo","Dos globos iguales","Cinta"],"pasos":["Ata un hilo al centro exacto de la regla y cuélgala de algún sitio. Ajusta hasta que quede horizontal: es una balanza.","Cuelga un globo desinflado en cada extremo, a la misma distancia del centro. Debe quedar equilibrada.","Ahora infla uno de los dos globos y vuelve a colgarlo en el mismo sitio.","La balanza se inclinará hacia el globo inflado: el aire que metiste pesa.","Si la balanza no se mueve, revisa que los hilos estén a la misma distancia del centro. Es el error más común."]}',
 'ambos', 3, 3);
