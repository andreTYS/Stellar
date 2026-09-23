# La biblioteca de ciencia

StellarScribe tenía dos historias y dos simuladores: se leían una vez y ya. Le
faltaba lo que hace útil a un sitio como el **Space Place de la NASA**:
artículos cortos que responden **una** pregunta, con un dibujo que explique de
verdad y algo que el estudiante pueda hacer en casa.

18 artículos en 5 temas. Se entra por curiosidad: no hay orden obligatorio ni
nada que desbloquear.

| Tema | Artículos |
|---|---|
| El Sol | Qué es el Sol · Manchas solares · Tormentas solares · Por qué quema más en la sierra |
| La Tierra | Día y noche · Por qué julio es invierno aquí · Por qué tiembla · Por qué el agua hierve antes en Carumas |
| El cielo de noche | Fases de la Luna · La Vía Láctea · Las constelaciones oscuras andinas · Por qué titilan las estrellas |
| El sistema solar | Qué hay en él · Por qué Plutón dejó de ser planeta · Qué es un eclipse |
| Ciencia de todos los días | Por qué el cielo es azul · De qué están hechas las estrellas · Por qué sube un globo de helio |

## La estructura, siempre la misma

1. **La pregunta** es el título. Es lo que hace que se entre a leer.
2. **La respuesta en una frase**, antes de cualquier explicación. Quien solo lee
   eso ya se lleva algo cierto.
3. **El diagrama**, que en varios artículos *es* la explicación.
4. **La explicación** en párrafos cortos.
5. **Un dato curioso**.
6. **Hazlo tú**: una actividad con lo que hay en casa.

Que sea siempre igual no es pereza: el estudiante aprende dónde está cada cosa y
deja de tener que averiguarlo en cada artículo.

## Los diagramas

Los 18 se dibujan a mano en SVG, en `stellarscribe/diagramas.php`. No son
imágenes sueltas por tres razones: se pueden corregir, escalan sin verse
borrosos y pesan casi nada, que importa con la conexión de un aula.

Todos comparten placa oscura y `viewBox="0 0 400 230"`, así que ocupan el mismo
hueco y la lectura no da saltos.

Para añadir uno: escribe `function diag_mi_slug(): string` devolviendo
`diagMarco('descripción accesible', '<svg interno>')`, y pon `mi-slug` en la
columna `diagrama` del artículo. Si el slug no existe, el artículo se muestra
sin dibujo en lugar de romperse.

**La corrección importa más que el dibujo.** Dos ejemplos de este trabajo:

- El diagrama de las estaciones tenía la mitad nocturna de la Tierra de junio
  mirando *hacia* el Sol. Imposible, y justo en el artículo que existe para
  corregir una idea equivocada.
- El de la radiación ultravioleta mostraba 1 de 4 rayos frente a 4 de 4: eso es
  un aumento del 300 %, y el real a 3 000 m ronda el 35 %. Un diagrama que
  exagera enseña mal aunque la idea de fondo sea correcta.

## Marcar como leído

`api/ciencia_leido.php` registra la lectura. El artículo lo llama **a los 25
segundos**, no al abrir, y el reloj se pausa si el estudiante cambia de pestaña.
Abrir y salir no es leer; si contara, los logros no dirían nada.

Tres logros: **Curioso** (3 artículos), **Lector de estrellas** (10) y **Mente
inquieta** (todos). Solo se anuncia el que se acaba de ganar.

No se califica. Leer por curiosidad no debería dar nota.

## Añadir artículos

Van en la base, en `ciencia_articulos`. El `cuerpo` es una lista JSON de
párrafos y la `actividad` es `{titulo, materiales:[], pasos:[]}`. Mira
`migrations/016_articulos_ciencia.sql` como plantilla.

Comprobaciones antes de dar por bueno un artículo nuevo:

```sql
-- artículos sin cuerpo suficiente
SELECT slug FROM ciencia_articulos WHERE JSON_LENGTH(cuerpo) < 2;

-- actividades incompletas
SELECT slug FROM ciencia_articulos
 WHERE actividad IS NOT NULL
   AND (JSON_VALUE(actividad,'$.titulo') IS NULL OR JSON_LENGTH(actividad,'$.pasos') IS NULL);
```

Y que el diagrama exista de verdad:

```bash
mysql -u root innovasteam -N -B -e "SELECT diagrama FROM ciencia_articulos WHERE diagrama IS NOT NULL" \
  | while read d; do php -r "require 'stellarscribe/diagramas.php';
      if (diagramaCiencia('$d') === null) echo \"SIN DIAGRAMA: $d\n\";"; done
```
