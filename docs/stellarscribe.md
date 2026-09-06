# StellarScribe — historias

Dos historias interactivas, un solo motor.

| Slug | Historia | De qué va |
|---|---|---|
| `aldrin` | El Despertar de Aldrin | Un astronauta varado en un planeta helado. Tormentas solares, electrólisis, celdas galvánicas y comunicaciones |
| `tormenta` | La noche sin señal | Una tormenta geomagnética apaga la radio y tuerce el GPS en Carumas. Manchas solares, eyecciones de masa coronal, magnetosfera y vigilancia del clima espacial |

Se abren en `stellarscribe/historia.php?historia=<slug>`. Un slug desconocido
cae en `aldrin`.

## Por qué la segunda pasa en Moquegua

La primera transcurre en otro planeta, y funciona. Pero deja al clima espacial
en el terreno de la ciencia ficción. La segunda ocurre en Carumas: la misma
física —el Sol, la magnetosfera, la ionosfera— apagando la radio de un abuelo
radioaficionado y desviando el GPS de una camioneta la noche de una emergencia.

Enlaza con lo que la plataforma ya tiene: el portal muestra manchas solares,
índice Kp y clasificación de fulguraciones en datos reales de la NASA, y hay un
simulador de clima espacial. La historia le da un motivo a mirar esas cifras.

## Cómo añadir una tercera

Todo el contenido vive en `stellarscribe/historias.js`. **El motor no se
toca.** Se añade una entrada al objeto `HISTORIAS`:

```js
mi_historia: {
  titulo: "…",
  subtitulo: "…",
  capitulos: {
    1: {
      title: "Nivel 1: …",
      bg: "img/…",              // fondo de la escena
      leftChar: "…", rightChar: "…",   // se alternan al avanzar
      education: { title: "…", points: ["…", "…"] },
      paragraphs: [
        { type: 'narr',   text: "…" },
        { type: 'dialog', who: 'cat', text: "—…" }
      ]
    },
    // 2, 3, 4…
  },
  decision: { titulo, texto, bg, img, opciones: [{id:'A', label:'…'}, …] },
  ramas:    { A: [ …párrafos… ], B: […], C: […] },
  final:    { titulo, bg, img, parrafos: [], lecciones: [], cierre: "…" }
}
```

El número de capítulos sale de `Object.keys(capitulos).length`, así que la
barra de progreso y el contador se ajustan solos. Las opciones de decisión y
las ramas se emparejan por `id`.

Después basta con enlazarla desde `portal.php` y desde
`stellarscribe/index.html`.

Comprueba que las imágenes que uses existan de verdad: las rutas van tal cual
al HTML y un archivo que falta solo se ve como un hueco.

## Progreso

Cada capítulo terminado se registra en `capitulos_progreso` con su
`historia_slug`, así que las dos historias no se pisan: el capítulo 1 de una es
distinto del capítulo 1 de la otra. Completar cuatro capítulos de una misma
historia otorga los logros «Explorador Estelar» y «Guardián del Cosmos».

## Dos cosas que estaban rotas

**El progreso no se guardaba nunca.** La página era `index2.html`, un archivo
estático, y `navigator.sendBeacon` no puede añadir cabeceras, así que no
mandaba token CSRF y `api/capitulo_progreso.php` respondía 403 a cada capítulo.
Por eso ahora es `historia.php`: siendo PHP puede escribir el token en el
cuerpo, que es justo para lo que `verifyCsrfJson()` lo acepta.

**Y aunque hubiera pasado el CSRF, tampoco habría escrito nada**: el endpoint
insertaba en `capitulo_num`, `quiz_score` y `leido_en`, tres columnas que no
existen (arreglado en la migración 011 y su commit).
