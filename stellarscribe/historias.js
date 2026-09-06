// ============================================================
// StellarScribe — Historias
//
// El motor (index2.html) era una sola historia empotrada en el propio
// archivo. Aquí viven los datos, separados, para que se puedan contar
// varias con el mismo motor: se elige con ?historia=<slug>.
//
// Cada historia trae sus capítulos, su punto de decisión y su final.
// Para añadir una tercera no hay que tocar el motor.
// ============================================================
window.HISTORIAS = {

  aldrin: {
    titulo: "El Despertar de Aldrin",
    subtitulo: "Un astronauta, un gato y un planeta helado",
    capitulos: {
      1: {
        title: "Nivel 1: El Despertar",
        bg: "img/chap1-bg.jpg",
        leftChar: "img/aldrin.gif",
        rightChar: "img/char-aldrin.jpg",
        education: {
          title: "Tormentas Solares y Radiación Cósmica",
          points: [
            "Las tormentas solares son explosiones de partículas energéticas del Sol",
            "Pueden dañar sistemas electrónicos y de comunicación en el espacio",
            "La radiación cósmica puede alterar sistemas biológicos y tecnológicos",
            "Los escudos magnéticos protegen a las naves espaciales",
            "Las supernovas cercanas pueden generar radiación extrema"
          ]
        },
        paragraphs: [
          {type:'narr', text: "Cuando Aldrin abrió los ojos, la cabina estaba en silencio. El último recuerdo que tenía era el destello cegador que atravesó los escudos de la nave, seguido de un vacío absoluto. La tormenta solar había golpeado de lleno el casco de su nave; nunca había visto algo tan poderoso en sus años como astronauta."},
          {type:'narr', text: "A su lado, no estaba solo. Su compañero, un gato generado por IA, el gato blanco que siempre lo acompañaba en misiones de larga duración, lo miraba fijamente. Pero había algo distinto: sus ojos brillaban con una intensidad azulada, casi humanos con un destello sorprendente como el de una nebulosa, y en el aire vibraba una ligera descarga eléctrica."},
          {type:'dialog', who:'cat', text: "—Aldrin… ¡Despierta! —dijo el gato con una voz clara y profunda."},
          {type:'narr', text: "El astronauta, atónito, se incorporó lentamente y un poco impactado."},
          {type:'narr', text: "—¿Estoy… soñando? —murmuró Aldrin, con la voz rota por la confusión."},
          {type:'narr', text: "—No. Llevas 11 días inconsciente. La radiación de la nube cósmica alteró mi sistema pero me pude recuperar. Ahora pienso, hablo… y siento cosas que antes no podía; a diferencia de ti, aún conservas tu humanidad —explicó el gato con calma."},
          {type:'narr', text: "El gato levantó la pata izquierda, y un campo magnético chisporroteó, atrayendo un destornillador metálico que flotó hasta él."},
          {type:'dialog', who:'cat', text: "—Te ayudaré a reparar la nave. El oxígeno comienza a escasear y debemos encontrar una fuente de energía alterna para poner a volar esta cosa. Hemos perdido todo contacto con Houston."}
        ]
      },
      2: {
        title: "Nivel 2: El Dilema Energético",
        bg: "img/chap2-bg.jpg",
        leftChar: "img/escena2_1.png",
        rightChar: "img/escena2_2.gif",
        education: {
          title: "Electrólisis del Agua",
          points: [
            "La electrólisis divide moléculas de agua (H₂O) en hidrógeno (H₂) y oxígeno (O₂)",
            "Se necesita electricidad para romper enlaces químicos",
            "El hidrógeno es combustible eficiente y limpio",
            "Este proceso es clave para la exploración espacial futura",
            "En la Tierra, la electrólisis es un método conocido para obtener combustibles limpios"
          ]
        },
        paragraphs: [
          {type:'narr', text: "Tras revisar los sistemas de la nave, Aldrin comprendió la gravedad de la situación: los paneles solares apenas captaban energía. En la órbita de aquel planeta helado donde habían logrado aterrizar de emergencia, la luz de un sol era escasa y débil."},
          {type:'dialog', who:'cat', text: "—Si no encontramos una nueva fuente de energía, mi estimado Aldrin, quedaremos varados aquí para siempre —murmuró el gato."},
          {type:'narr', text: "El gato miró a Aldrin con determinación y comenzó a explicar: 'Te explicaré como si de un niño de escuela primaria se tratara. La energía está en todas partes. El hielo que cubre este planeta podría contener secretos que aún no imaginas. Si logramos dividir sus moléculas de agua, podemos liberar hidrógeno y oxígeno.'"},
          {type:'narr', text: "—Y el hidrógeno… es combustible en su estado más puro para una nave de la NASA. —Debemos fabricar una celda. Por electrólisis lograremos descomponer y dividir las moléculas."}
        ]
      },
      3: {
        title: "Nivel 3: Baterías Caseras",
        bg: "img/chap3-bg.gif",
        leftChar: "escena3.gif",
        rightChar: "escena3_1.jpg",
        education: {
          title: "Celdas Galvánicas (Baterías de Limón)",
          points: [
            "Una batería de limón funciona mediante reacciones químicas ácido-base",
            "El ácido cítrico del limón actúa como electrolito conductor",
            "Los metales diferentes (cobre y zinc) crean una diferencia de potencial",
            "Conectadas en serie, múltiples celdas aumentan el voltaje total",
            "La sal y el vinagre aumentan la conductividad del medio"
          ]
        },
        paragraphs: [
          {type:'narr', text: "Aldrin revisó el tablero de energía: la nave estaba en modo de emergencia, apenas alimentando el sistema de soporte vital. El gato tuvo una idea brillante."},
          {type:'dialog', who:'cat', text: "—No subestimes lo pequeño, Aldrin. La energía puede venir de algo tan simple como la química de los ácidos."},
          {type:'narr', text: "Con una descarga luminosa, el gato abrió un compartimiento auxiliar de la nave. Dentro había provisiones olvidadas: un cajón de limones deshidratados, paquetes de sal y frascos de vinagre."},
          {type:'narr', text: "Improvisaron un laboratorio. Aldrin cortó los limones, los empapó con vinagre y añadió sal en cada corte. Insertaron cobre y zinc reciclados y conectaron cables: una tenue corriente comenzó a fluir."},
          {type:'narr', text: "Encendiendo luces que parpadeaban como luciérnagas en la penumbra del cañón helado, lograron acumular energía suficiente para poner en marcha un módulo de electrólisis improvisado."}
        ]
      },
      4: {
        title: "Nivel 4: Señales en el Hielo",
        bg: "chap4-bg.jpg",
        leftChar: "monolith.gif",
        rightChar: "escena4.gif",
        education: {
          title: "Comunicaciones Espaciales",
          points: [
            "Las ondas electromagnéticas viajan a la velocidad de la luz",
            "Las antenas deben estar alineadas para recibir señales",
            "Los campos electromagnéticos pueden amplificar o modular señales",
            "La búsqueda de señales extraterrestres (SETI) es una ciencia real",
            "Las frecuencias pueden transportar información compleja"
          ]
        },
        paragraphs: [
          {type:'narr', text: "Con el banco de limones funcionando, lograron iniciar la electrólisis del hielo. Mientras tanto, reconstruían la antena con partes recicladas."},
          {type:'narr', text: "Al encender el módulo receptor, un fuerte zumbido recorrió la cabina. De pronto, entre las interferencias, emergió un pulso repetitivo: Bip… bip… bip…"},
          {type:'dialog', who:'aldrin', text: "—Eso… ¡eso no es la NASA! —exclamó Aldrin."},
          {type:'narr', text: "Siguiendo la dirección de la señal, descubrieron una estructura metálica enterrada bajo capas de hielo: un monolito cubierto de runas luminosas de color violeta, vibrando con la misma frecuencia detectada. El gato tocó la estructura con su garra. La señal cambió y una voz resonó en la mente de Aldrin: 'Aldrin…'."}
        ]
      }
    },

    decision: {
      titulo: "PUNTO DE DECISIÓN CRÍTICO",
      texto: "Aldrin y el gato se encuentran frente al misterioso monolito. ¿Qué camino decides tomar?",
      bg: "chap4-bg.jpg",
      img: "monolith.gif",
      opciones: [
        { id: "A", label: "OPCIÓN A — Investigar el Monolito" },
        { id: "B", label: "OPCIÓN B — Autosuficiencia" },
        { id: "C", label: "OPCIÓN C — Comunicación Pacífica" }
      ]
    },

    ramas: {
      A: [
        {type:'narr', text:'La curiosidad es más fuerte. Aldrin se acerca al monolito mientras el gato salta sobre la estructura, concentrando su poder electromagnético.'},
        {type:'narr', text:'En su interior, encuentran proyecciones holográficas de una antigua civilización que habitó este planeta hace eones.'},
        {type:'dialog', who:'cat', text:'—¡Esto es increíble! Con esta tecnología, no solo podemos regresar a casa... ¡podemos cambiar el futuro energético de la humanidad!'}
      ],
      B: [
        {type:'narr', text:'La prioridad es regresar a casa. Aldrin y el gato concentran sus esfuerzos en optimizar el banco de limones y la electrólisis del hielo.'},
        {type:'narr', text:'Después de varios días de trabajo arduo, logran llenar los tanques de hidrógeno de la nave y reparar las antenas utilizando componentes reciclados.'}
      ],
      C: [
        {type:'narr', text:'Aldrin y el gato configuran el equipo para enviar una respuesta a la señal. El gato canaliza su energía electromagnética para modular un mensaje de paz.'},
        {type:'dialog', who:'cat', text:'—Forastero… Has despertado a los Guardianes de Kael. Somos los custodios de este planeta.'}
      ]
    },

    final: {
      titulo: "REGRESO A CASA",
      bg: "img/bg_final.jpg",
      img: "img/final.gif",
      parrafos: [
        "Con la nave reparada y los sistemas operativos, Aldrin y su fiel compañero se preparan para el viaje de regreso a casa. La experiencia en el planeta helado los ha cambiado para siempre, demostrando que incluso en los confines del universo, la ciencia, el ingenio y la conexión con lo inesperado son las claves para superar cualquier adversidad.",
        "Luego de un tiempo, Aldrin pudo llegar a la órbita del planeta Tierra y logró enviar un mensaje a la NASA para que prepararan su aterrizaje. El gato ayudó en el aterrizaje de emergencia y, más tarde, estableció una alianza con los Guardianes."
      ],
      lecciones: [
        "Resiliencia ante la adversidad",
        "Ciencia aplicada en situaciones extremas",
        "Trabajo en equipo y cooperación",
        "Innovación y pensamiento creativo"
      ],
      cierre: "Final: regreso a casa. Gracias por acompañar a Aldrin y al gato."
    }
  },

  // ── Historia 2 ─────────────────────────────────────────────
  // La primera pasa en un planeta helado. Esta pasa aquí: el clima
  // espacial no es un tema de ciencia ficción, se nota en la radio, el
  // GPS y la red eléctrica de un valle como el de Moquegua. Enlaza con
  // los simuladores y con los datos de la NASA que ya trae el portal.
  tormenta: {
    titulo: "La noche sin señal",
    subtitulo: "Cuando el Sol apagó la radio de Carumas",
    capitulos: {
      1: {
        title: "Nivel 1: La mancha en el Sol",
        bg: "img/chap1-bg.jpg",
        leftChar: "images/imagen1.jpg",
        rightChar: "images/imagen2.jpg",
        education: {
          title: "El Sol no es una bola quieta",
          points: [
            "Las manchas solares son zonas más frías donde el campo magnético sale a la superficie",
            "Aparecen y desaparecen siguiendo un ciclo de unos 11 años",
            "Nunca se mira el Sol directamente: se proyecta su imagen sobre una hoja",
            "Cuantas más manchas hay, más probable es que ocurran fulguraciones",
            "Las fulguraciones se clasifican por su energía: B, C, M y X, de menor a mayor"
          ]
        },
        paragraphs: [
          {type:'narr', text: "Nayra vive en Carumas, a más de tres mil metros, donde el cielo está despejado casi todo el año. Su abuelo Aurelio tiene una radio de aficionado en el cuarto del fondo, un aparato viejo con perillas gastadas por el uso, y desde hace cuarenta años habla cada noche con gente de Puno, de Arequipa y a veces de mucho más lejos. Nayra creció escuchando esas conversaciones sin entenderlas del todo."},
          {type:'narr', text: "Una mañana, en el colegio, el profesor de ciencia sacó al patio un telescopio pequeño con un filtro y proyectó la imagen del Sol sobre una cartulina blanca. Les advirtió antes que a nadie se le ocurriera mirar por el ocular. Sobre el círculo brillante proyectado había tres manchas oscuras, agrupadas."},
          {type:'dialog', who:'cat', text: "—¿Y eso qué es? ¿Está sucio el telescopio? —preguntó Nayra."},
          {type:'narr', text: "El profesor le explicó que no: esas manchas están en el Sol. Son regiones donde el campo magnético se retuerce y sale a la superficie, y donde el gas está algo más frío que alrededor, por eso se ven oscuras. Le dijo que el Sol tiene un ciclo de unos once años y que ahora estaba en la parte agitada de ese ciclo."},
          {type:'narr', text: "—Cuando hay muchas manchas juntas como esas —añadió—, el campo magnético puede romperse de golpe y soltar una cantidad enorme de energía. A eso se le llama fulguración. Se clasifican por letras: B, C, M y X, de la más débil a la más fuerte."},
          {type:'narr', text: "Nayra copió las tres manchas en su cuaderno con la fecha. Esa noche se lo contó a su abuelo. Aurelio dejó la taza en la mesa, se quedó un momento callado y le dijo algo que ella no esperaba: que cuando hay muchas manchas, a él la radio se le pone rara."}
        ]
      },
      2: {
        title: "Nivel 2: Algo salió del Sol",
        bg: "img/chap2-bg.jpg",
        leftChar: "images/imagen2.jpg",
        rightChar: "images/imagen3.jpg",
        education: {
          title: "Eyecciones de masa coronal y magnetosfera",
          points: [
            "Una eyección de masa coronal lanza miles de millones de toneladas de gas magnetizado al espacio",
            "La luz de una fulguración llega en 8 minutos; la nube de partículas tarda de 1 a 3 días",
            "El campo magnético de la Tierra desvía la mayor parte de esas partículas",
            "Cuando la nube es intensa, sacude ese campo: es una tormenta geomagnética",
            "Las auroras son la señal visible de esa sacudida, cerca de los polos"
          ]
        },
        paragraphs: [
          {type:'narr', text: "Al día siguiente el profesor abrió en clase la página de un observatorio y les mostró una imagen del Sol tomada esa madrugada. Del borde salía una especie de nube en expansión, como humo que se aleja. Debajo decía: eyección de masa coronal."},
          {type:'narr', text: "Les explicó que una fulguración es un destello de luz y rayos X que llega en ocho minutos, porque viaja a la velocidad de la luz. Pero a veces, junto con la fulguración, el Sol se desprende de una nube gigantesca de gas magnetizado: miles de millones de toneladas lanzadas al espacio. Esa nube no viaja a la velocidad de la luz. Tarda entre uno y tres días en llegar hasta nosotros."},
          {type:'dialog', who:'cat', text: "—¿Y cuando llega qué pasa? —preguntó Nayra, que ya había sacado el cuaderno."},
          {type:'narr', text: "El profesor dibujó la Tierra en la pizarra y a su alrededor unas líneas curvas que la envolvían como un capullo. Le dijo que ese es el campo magnético del planeta, la magnetosfera, y que es lo que desvía la mayor parte de esas partículas. Sin él, la vida en la superficie sería muy distinta."},
          {type:'narr', text: "—Pero cuando la nube llega con fuerza y con el campo magnético orientado justo al revés que el nuestro, la magnetosfera se sacude entera. A eso lo llamamos tormenta geomagnética. Y esa sacudida se nota en cosas muy concretas de aquí abajo."},
          {type:'narr', text: "Nayra miró la fecha de la imagen y calculó. Si la nube había salido esa madrugada y tardaba entre uno y tres días, llegaría entre el jueves y el sábado. Anotó las dos fechas y las subrayó."}
        ]
      },
      3: {
        title: "Nivel 3: La noche sin señal",
        bg: "img/chap3-bg.gif",
        leftChar: "images/imagen3.jpg",
        rightChar: "images/imagen4.jpg",
        education: {
          title: "Qué se rompe en la Tierra",
          points: [
            "La radio de onda corta rebota en la ionosfera; una tormenta la altera y la señal se pierde",
            "El GPS calcula posición con el tiempo de viaje de la señal, que la ionosfera retrasa",
            "Las corrientes inducidas pueden sobrecalentar transformadores de la red eléctrica",
            "Los satélites en órbita sufren daños en su electrónica y aumentan su rozamiento",
            "En 1989 una tormenta dejó sin luz a Quebec, seis millones de personas, durante nueve horas"
          ]
        },
        paragraphs: [
          {type:'narr', text: "Llegó el viernes. A las nueve de la noche, Aurelio estaba en su radio como siempre, hablando con un amigo de Juliaca. La conversación se cortó a media frase. Aurelio movió el dial arriba y abajo. Nada. Solo un siseo largo, como el mar."},
          {type:'dialog', who:'cat', text: "—Abuelo, ¿se malogró? —Nayra se había asomado a la puerta."},
          {type:'narr', text: "—No —dijo Aurelio, y había algo raro en su voz—. El equipo está bien. Es la banda. Está muerta."},
          {type:'narr', text: "Nayra sacó su cuaderno y buscó las fechas subrayadas. Era una de ellas. Le explicó a su abuelo lo de la nube: la radio de onda corta funciona porque la señal rebota en la ionosfera, una capa alta de la atmósfera, y vuelve a bajar lejísimos. Cuando una tormenta altera esa capa, la señal ya no rebota: se pierde hacia arriba o se absorbe."},
          {type:'narr', text: "Media hora después subió un vecino golpeando la puerta. Su esposa estaba con dolores de parto adelantado y había que bajarla a Moquegua. El chofer de la camioneta dijo que el GPS del celular lo estaba mandando por una trocha que él sabía cortada, y que marcaba la posición a doscientos metros de donde estaban de verdad."},
          {type:'narr', text: "Nayra entendió en ese momento que lo que había copiado en su cuaderno no era un dato de examen. El GPS calcula dónde estás midiendo cuánto tarda en llegar la señal de los satélites, y la ionosfera revuelta retrasa esa señal de forma impredecible. Esa noche, en Carumas, el Sol había apagado la radio y torcido el mapa."}
        ]
      },
      4: {
        title: "Nivel 4: Predecir la tormenta",
        bg: "chap4-bg.jpg",
        leftChar: "images/imagen4.jpg",
        rightChar: "images/imagen1.jpg",
        education: {
          title: "Vigilar el clima espacial",
          points: [
            "Hay satélites entre el Sol y la Tierra que miden la nube antes de que llegue",
            "Dan entre 15 y 60 minutos de aviso real sobre su intensidad",
            "Las escalas van de G1 a G5 en tormentas geomagnéticas, R1 a R5 en apagones de radio",
            "Con aviso, un operador eléctrico puede reducir carga y proteger sus transformadores",
            "Los radioaficionados cambian de banda: lo que no pasa en una frecuencia sí pasa en otra"
          ]
        },
        paragraphs: [
          {type:'narr', text: "La camioneta bajó igual, con el chofer guiándose por el camino que conocía de memoria y sin mirar la pantalla. Llegaron. Todo salió bien. Pero Nayra no durmió pensando en lo mismo: se pudo haber sabido antes."},
          {type:'narr', text: "El lunes le llevó al profesor su cuaderno con las fechas, las manchas dibujadas y la hora exacta en que se cayó la banda. Él le enseñó entonces algo que ella no sabía que existía: hay satélites colocados entre el Sol y la Tierra, muy por delante de nosotros, que ven pasar la nube antes de que nos alcance y miden su velocidad y la orientación de su campo magnético."},
          {type:'dialog', who:'cat', text: "—¿Y cuánto tiempo de aviso dan? —preguntó Nayra."},
          {type:'narr', text: "—Entre quince y sesenta minutos con la medida buena, la de verdad. Y de uno a tres días con la estimación de cuándo llegará, desde que se ve salir la eyección."},
          {type:'narr', text: "Le explicó que existen escalas para comunicarlo: de G1 a G5 para las tormentas geomagnéticas y de R1 a R5 para los apagones de radio. Con un aviso de G4, un operador de red eléctrica puede reducir carga antes de que las corrientes inducidas recalienten un transformador. Y un radioaficionado como Aurelio puede cambiar a otra banda, porque lo que no pasa en una frecuencia a veces sí pasa en otra."},
          {type:'narr', text: "Nayra se quedó mirando el cuaderno. Tenía las manchas del martes, la nube del miércoles y la banda muerta del viernes. Tres páginas que, puestas en orden, contaban una sola historia. Levantó la cabeza y preguntó qué haría falta para que en Carumas alguien supiera eso con un día de anticipación."}
        ]
      }
    },

    decision: {
      titulo: "QUÉ HACER CON EL AVISO",
      texto: "Nayra convence al profesor de armar algo en el colegio para la próxima tormenta. Hay tiempo y ganas para una sola cosa bien hecha. ¿Cuál?",
      bg: "img/chap2-bg.jpg",
      img: "images/imagen4.jpg",
      opciones: [
        { id: "A", label: "OPCIÓN A — Una bitácora de observación solar" },
        { id: "B", label: "OPCIÓN B — Una red de radio con plan de bandas" },
        { id: "C", label: "OPCIÓN C — Un aviso para la posta y el municipio" }
      ]
    },

    ramas: {
      A: [
        {type:'narr', text: "Nayra elige mirar el Sol. Cada mañana despejada, el colegio proyecta la imagen solar sobre una cartulina y dos estudiantes de turno dibujan las manchas, las cuentan y anotan la fecha."},
        {type:'narr', text: "En tres meses tienen una serie con la que ya se ve algo: las semanas de muchas manchas coinciden con las semanas en que la radio del abuelo se pone difícil. Nadie se lo tuvo que contar; lo dedujeron de sus propios datos."},
        {type:'dialog', who:'cat', text: "—Esto ya no es una tarea —dijo el profesor mirando el cuaderno—. Esto es una serie de observación. Y es de ustedes."}
      ],
      B: [
        {type:'narr', text: "Nayra elige la radio. Con Aurelio arman una lista de siete casas del anexo que tienen equipo, con sus frecuencias, y un plan sencillo: si la banda de siempre se cae, todos suben a la siguiente acordada a horas fijas."},
        {type:'narr', text: "Lo prueban un sábado cualquiera simulando un corte. Funciona a la tercera. Aurelio, que llevaba cuarenta años hablando por esa radio, aprende de su nieta algo que no sabía: por qué unas frecuencias sobreviven a la tormenta y otras no."},
        {type:'dialog', who:'cat', text: "—Toda la vida cambiando de banda a tientas —dijo—. Ahora sé por qué funcionaba."}
      ],
      C: [
        {type:'narr', text: "Nayra elige avisar. Redacta una hoja de una sola cara: qué es una tormenta geomagnética, qué falla cuando ocurre —radio, GPS, red eléctrica—, y qué conviene hacer. La lleva a la posta y al municipio."},
        {type:'narr', text: "Al principio la miran con paciencia de adulto ocupado. Pero la enfermera de la posta lee la parte del GPS, se queda callada y le pide una copia para pegarla junto al teléfono. Ella también había bajado un paciente esa noche."},
        {type:'dialog', who:'cat', text: "—¿Y esto lo hiciste tú sola? —le preguntó—. Déjame dos copias más."}
      ]
    },

    final: {
      titulo: "EL CIELO QUE SÍ NOS TOCA",
      bg: "img/bg_final.jpg",
      img: "images/imagen1.jpg",
      parrafos: [
        "El Sol siguió con su ciclo, indiferente. Hubo más manchas, más fulguraciones y, meses después, otra tormenta. Esta vez en Carumas alguien lo sabía con un día de anticipación, y lo que la primera noche fue susto, la segunda fue solo una molestia.",
        "Nayra no detuvo una tormenta solar: eso no lo hace nadie. Lo que hizo fue más pequeño y más útil. Convirtió algo que parecía lejano y ajeno —una mancha en una estrella a 150 millones de kilómetros— en información que su comunidad podía usar la noche que hiciera falta.",
        "Su abuelo sigue hablando por radio cada noche. Ahora, cuando la banda se pone rara, no dice que el equipo está fallando. Dice que hay tormenta, y sube de frecuencia."
      ],
      lecciones: [
        "El clima espacial afecta la radio, el GPS y la red eléctrica",
        "Entre la fulguración y la nube hay días de diferencia: hay tiempo de avisar",
        "Observar y registrar con constancia convierte datos sueltos en conocimiento",
        "La ciencia sirve cuando llega a quien tiene que tomar una decisión"
      ],
      cierre: "Final: el cielo que sí nos toca. Gracias por acompañar a Nayra."
    }
  }
};
