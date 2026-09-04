# El catálogo de contenido

23 módulos repartidos en 6 cursos. Cada módulo son cuatro pasos —historia,
actividad, quiz, entregable— y tres preguntas de opción múltiple.

| Curso | Módulos |
|---|---|
| Matemática | El mercado de Moquegua · Construyendo terrazas · El aguaymanto y los kilos · Descuentos en la feria |
| Comunicación | El río Moquegua habla · Leyendas de Torata · Noticias de mi barrio · La voz de los abuelos |
| Arte | Colores de Moquegua · Mural del barrio · Tejidos y patrones · Un afiche para el agua |
| Ingeniería | Puentes con papel · Filtro de agua · Torre sísmica · Cocina solar |
| Inglés | My Moquegua · The market · Nature around us · Where is the plaza? |
| Ciencia | El cielo de Moquegua · El viaje del agua · La tierra que se mueve |

Los quince primeros vienen en `schema.sql`. Los ocho añadidos después, en
`migrations/010_mas_temas.sql`, que se puede volver a ejecutar sin duplicar
nada: las claves únicas `uk_modulo_curso_titulo` y `uk_quiz_paso_orden` lo
impiden.

Ciencia hacía falta de verdad. El asistente de estudio ya declaraba que
respondía de ciencia y astronomía, y StellarScribe forma parte del proyecto,
pero no había ni un módulo donde eso aterrizara.

## Cómo se añade un tema

Desde **Admin → Módulos**. Se crea el módulo, se entra a sus pasos y se
redacta cada uno. El editor guarda JSON y valida que sea válido antes de
escribir.

Los pasos nuevos nacen con las claves ya puestas; solo hay que rellenarlas.
Esas claves no son decorativas: si no son exactamente estas, el paso llega
al estudiante vacío y **sin ningún mensaje de error** —era el fallo que
tenía el editor hasta ahora—.

| Paso | Claves |
|---|---|
| historia | `narrativa`, `pregunta_disparadora`, `conceptos_clave` (lista, opcional), `dato_curioso` (opcional) |
| actividad | `materiales` (lista), `instrucciones` (lista), `minutos` |
| quiz | `info` — las preguntas van en la tabla `quiz_preguntas`, no aquí |
| entregable | `consigna`, `formatos` (lista), `instrucciones` |

`formatos` solo acepta los valores del ENUM de `entregables`:
`dibujo_cientifico`, `mural_digital`, `cuento_ilustrado`, `prototipo`,
`ficha`, `otro`.

Las preguntas del quiz se cargan por SQL. Cada opción es un objeto con
`texto` y `correcta`, y **exactamente una** debe ser verdadera:

```sql
INSERT INTO quiz_preguntas (paso_id, texto, opciones, orden) VALUES
((SELECT id FROM modulo_pasos WHERE modulo_id = 16 AND numero_paso = 3),
 '¿Por qué desde una ciudad grande se ven menos estrellas?',
 '[{"texto":"Porque hay menos estrellas allá","correcta":false},
   {"texto":"Porque la luz artificial tapa las estrellas débiles","correcta":true},
   {"texto":"Porque se alejan cuando hay gente","correcta":false}]',
 1);
```

## Cómo se revisa que un módulo esté bien cargado

Tres consultas que devuelven filas solo cuando algo está mal:

```sql
-- módulos a los que les faltan pasos
SELECT m.id, m.titulo, COUNT(p.id) pasos FROM modulos m
  LEFT JOIN modulo_pasos p ON p.modulo_id = m.id
 GROUP BY m.id HAVING pasos <> 4;

-- pasos de quiz sin sus tres preguntas
SELECT p.modulo_id, COUNT(q.id) n FROM modulo_pasos p
  LEFT JOIN quiz_preguntas q ON q.paso_id = p.id
 WHERE p.tipo = 'quiz' GROUP BY p.id HAVING n <> 3;

-- preguntas sin una única respuesta correcta
SELECT q.id, LEFT(q.texto, 50) FROM quiz_preguntas q
 WHERE (SELECT COUNT(*) FROM JSON_TABLE(q.opciones, '$[*]'
        COLUMNS(c BOOLEAN PATH '$.correcta')) t WHERE t.c = 1) <> 1;
```

## Quién ve qué

Los módulos entran al catálogo general y el estudiante los ve todos, en
orden, desbloqueándose uno tras otro. `aula_modulos` no controla el acceso:
sirve para que el docente planifique fechas en su aula.

Por eso la migración 010 no asigna nada a ninguna aula. Qué se trabaja y
cuándo lo decide el docente.
