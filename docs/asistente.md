# Asistente de estudio

Un chat acotado al temario, dentro de la plataforma. Cada colegio usa **su
propia clave de API**, que asigna el administrador general: el consumo se
factura a quien corresponde y ningún colegio puede gastar el saldo de otro.

Lo ven estudiantes y practicantes. Los demás roles no, porque cada consulta
gasta saldo y no lo necesitan para su trabajo.

## Puesta en marcha

### 1. Dependencias

```bash
composer install
```

Instala el SDK de Anthropic y Guzzle. **Guzzle hace falta de verdad**: el SDK
solo declara la interfaz PSR-18, y sin una implementación cada consulta falla
con "No PSR-18 HTTP client found".

### 2. Secreto maestro

Las claves de API se guardan cifradas. El secreto que las cifra vive fuera de
la base de datos, así que un volcado robado no sirve de nada por sí solo.

Genera uno:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Y ponlo en `includes/config.local.php` (ese archivo no se versiona):

```php
<?php
define('CHATBOT_CLAVE_MAESTRA', '<lo que salió del comando>');
```

Si falta, la plataforma **se niega a guardar claves** en vez de dejarlas en
claro. Guárdalo con el resto de secretos del servidor: si lo pierdes, hay que
volver a introducir la clave de cada colegio.

### 3. Migración

```bash
mysql -u root innovasteam < migrations/009_chatbot.sql
```

### 4. Clave de cada colegio

En **Admin → Colegios**, bajo cada institución:

| Campo | Qué es |
|---|---|
| Clave de API | La del colegio, de console.anthropic.com. Se cifra al guardar; después solo se ven los cuatro últimos caracteres |
| Modelo | Opus 5 por defecto. Sonnet 5 y Haiku 4.5 cuestan menos por consulta |
| Tope/día | Preguntas por estudiante y día. Por defecto 30 |
| Activo | Enciende o apaga el asistente para ese colegio |

Para cambiar solo los ajustes, deja el campo de la clave vacío: se conserva la
que ya estaba.

## Por qué el tope diario

Sin él, un aula entera puede agotar el saldo del colegio en una tarde. El tope
cuenta solo consultas respondidas; los intentos bloqueados no gastan nada, pero
quedan registrados.

## Qué responde y qué no

El acotamiento al temario está en el servidor, no en el navegador: un mensaje
del cliente no es de fiar. El asistente responde sobre matemática, comunicación,
arte, ingeniería, inglés, ciencia y astronomía —incluido StellarScribe— y sobre
cómo usar la plataforma. Ante cualquier otra cosa declina y propone una pregunta
de estudio.

Con ejercicios guía paso a paso en vez de dar el resultado. El objetivo es que
aprendan, no aprobar por ellos.

## Registro

Todas las consultas quedan en `chatbot_mensajes`: quién preguntó, qué preguntó,
qué se respondió y cuántos tokens costó. La plataforma la usan menores, así que
esto no es opcional.

Consumo por colegio en el mes:

```sql
SELECT c.nombre,
       COUNT(*) consultas,
       SUM(m.tokens_in)  tokens_entrada,
       SUM(m.tokens_out) tokens_salida
  FROM chatbot_mensajes m
  JOIN colegios c ON c.id = m.colegio_id
 WHERE m.estado = 'ok' AND m.creado_en >= DATE_FORMAT(NOW(), '%Y-%m-01')
 GROUP BY c.id ORDER BY consultas DESC;
```

## Endpoint

`POST /api/chat.php` — acepta sesión web (con token CSRF) o token Bearer de la
app móvil.

```json
{ "pregunta": "¿Qué es una fracción?", "historial": [], "modulo": "opcional" }
```

| Código | Cuándo |
|---|---|
| 200 | Respuesta, con `restantes` del día |
| 401 | Sin sesión ni token |
| 429 | Tope diario alcanzado |
| 503 | El colegio no tiene el asistente configurado |
| 502 | Fallo al llamar al modelo; el detalle va al log del servidor |

Los mensajes de error vienen listos para mostrarse al estudiante. El detalle
técnico nunca sale al cliente: se escribe con `error_log`.

## Estado

Probado de extremo a extremo salvo una respuesta correcta del modelo, que
necesita una clave de API válida. Sí está verificado: el cifrado (ida y vuelta,
detección de manipulación, negativa a guardar sin secreto), el tope diario, el
aislamiento por rol, el registro, y que una clave inválida produce un 401 de la
API traducido a un mensaje legible.
