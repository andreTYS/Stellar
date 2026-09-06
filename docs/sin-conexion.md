# Trabajar sin conexión

En las aulas de Moquegua la señal se cae a media clase. Antes, un quiz
respondido o un entregable subido en ese momento se perdía **en silencio**: el
`fetch` fallaba, un `.catch(() => {})` se lo tragaba, y el estudiante veía su
modal de felicitación mientras su trabajo no llegaba a ninguna parte.

Ahora el envío se guarda en el navegador y se reintenta solo.

## Cómo funciona

`assets/js/cola.js` se carga en todas las páginas —no solo en la del módulo—
porque lo que quedó pendiente en la clase de ayer se reintenta al abrir
cualquier otra.

1. Al enviar, si no hay conexión o la petición falla, el envío se guarda en
   IndexedDB con un identificador propio (`cliente_uuid`).
2. Aparece un aviso abajo a la izquierda: «N envíos pendientes · se
   reintentarán solos». Sin ese aviso el estudiante creería que su trabajo se
   guardó.
3. Se reintenta al volver la conexión, al cargar cualquier página y cada dos
   minutos.
4. Cuando llega, se borra de la cola y el aviso desaparece.

Los archivos van completos: la foto del entregable se guarda como Blob en
IndexedDB y se sube tal cual cuando vuelve la señal.

## Por qué hace falta el identificador

El navegador reintenta sin saber si el primer intento llegó — pudo llegar y
haberse perdido solo la respuesta. Sin protección, el quiz sumaría un intento
que el estudiante no hizo y el entregable aparecería dos veces.

Con el `cliente_uuid`, el servidor guarda la respuesta que dio la primera vez
en `envios_idempotentes` y la repite tal cual. La segunda vez no vuelve a
ejecutar nada; la respuesta trae `"reintento": true`.

El uuid lo elige el cliente, así que se comprueba junto al usuario: nadie
puede leer el envío de otro adivinando un identificador.

## Qué se reintenta y qué no

| Situación | Qué pasa |
|---|---|
| Sin red, o la petición se corta | Se encola y se reintenta |
| 5xx, 429, 408 | Se reintenta: son transitorios |
| 403 | Se reintenta: casi siempre es el CSRF de una sesión caducada, que se arregla al volver a entrar |
| Otros 4xx | Se descarta: la petición es inválida y reintentarla no la arregla |
| Más de 50 intentos, o más de 7 días | Se descarta |

`api/progreso.php` no necesita nada de esto: su `INSERT … ON DUPLICATE KEY
UPDATE paso_actual = GREATEST(...)` ya es idempotente por construcción.

## Limpieza

`cron-resumen.php`, que ya corre semanalmente, borra los registros de
idempotencia de más de siete días. Pasado ese plazo el reintento no va a
llegar: el navegador se limpió o el dispositivo cambió de manos.

## Lo que sí se probó

Con el navegador real, cortando la red de tres formas:

- **Sin conexión** (`setOffline`): el envío se encola, aparece el aviso, y al
  restaurar la red llega a la base de datos.
- **Señal débil** (`navigator.onLine` sigue en `true` pero la petición se
  aborta): también se encola y se entrega al reintentar. Es el caso real más
  frecuente y el que un simple `navigator.onLine` no detecta.
- **Envío duplicado**: la segunda petición con el mismo `cliente_uuid`
  devuelve la misma respuesta con `"reintento": true`, y `intentos_quiz` sube
  una sola vez.
