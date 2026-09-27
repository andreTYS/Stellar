# Poner la plataforma en línea como demostración

GitHub Pages **no sirve** para esto. Pages solo entrega archivos; la
plataforma necesita PHP y MySQL. Lo que hay en
`andretys.github.io/Stellar` es la cara pública —portada, historias y
los 18 artículos de ciencia— y no más.

Para enseñar la plataforma entera hay tres caminos. El segundo es el
que conviene si quieres un enlace que mandar antes de una reunión.

## 1. Tu propia laptop, sin internet

Lo más barato y lo más fiable dentro de un colegio.

```bash
./local.sh          # arranca base y servidor
./local.sh --reset  # la deja como recién instalada
```

Queda en `http://localhost:8000/innovasteam`. En Windows, `local.bat`.

- **A favor:** cero costo, funciona con el wifi del colegio caído, que
  es exactamente el escenario del que te van a preguntar.
- **En contra:** no puedes mandar un enlace. Y si la laptop falla, la
  reunión se acabó.

Llévalo siempre como respaldo aunque tengas el demo en línea.

## 2. Hosting compartido con PHP y MySQL

Entre 15 y 30 soles al mes. Sirve cualquiera que ofrezca **PHP 8.1 o
superior**, **MySQL o MariaDB** y **cron**. Esas tres, y ya.

### Qué subir

Todo el proyecto menos lo que no hace falta en el servidor:

Puedes dejar fuera `movil/`, `electron/`, `_site/` y `.git/`: no se usan
en el servidor. **Sí** tienen que ir `schema.sql`, `seed_data.sql` y la
carpeta `migrations/`, porque son los que usa el reinicio diario.

### Los cuatro pasos

**1. La base de datos.** En el panel del hosting, crea una base y un
usuario. Te va a dar un nombre con prefijo, tipo `u123456_demo`. Apunta
nombre, usuario y contraseña.

**2. La configuración.** Copia el ejemplo y edítalo:

```bash
cp includes/config.local.example.php includes/config.local.php
```

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456_demo');   // el nombre que te dio el panel
define('DB_USER', 'u123456_demo');
define('DB_PASS', '...');

// '' si el proyecto está en la raíz del dominio.
// '/innovasteam' si está en una subcarpeta.
define('BASE_URL', '');

// Bloquea cambiar contraseñas y desactivar cuentas de prueba.
define('MODO_DEMO', true);
```

**3. Llenar la base.** Por SSH si lo tienes:

```bash
php herramientas/reiniciar_demo.php
```

Si el hosting no da SSH, define también un token y llámalo por web una
vez:

```php
define('DEMO_TOKEN_REINICIO', '...64 caracteres al azar...');
```

```
https://tudominio.com/herramientas/reiniciar_demo.php?token=...
```

Genera el token con `php -r "echo bin2hex(random_bytes(32));"`. Sin ese
token definido la vía web está cerrada: es un script que borra la base
entera.

**4. El cron.** Una vez al día, de madrugada:

```
0 4 * * *  /usr/bin/php /home/usuario/public_html/herramientas/reiniciar_demo.php
```

Esto es lo que hace que el demo sobreviva. Cualquiera que entre con las
cuentas de prueba puede calificar mal, borrar un aula o subir lo que
sea; a las cuatro de la mañana vuelve a estar entero.

### Comprobar que quedó bien

Entra con `EST-001` y la contraseña `password`. Si ves los cursos con
sus colores y «Esta semana en tu aula», está listo.

## 3. VPS

Solo si vas a poner colegios de verdad, no para un demo. Más control,
más trabajo, y nada de esto cambia.

---

## Lo que hay que saber del modo demostración

Con `MODO_DEMO` activado se bloquean dos cosas, y solo dos:

- Cambiar la contraseña desde el perfil.
- Desactivar cuentas desde la administración.

No es por seguridad, es por que el demo siga en pie: son cuentas
compartidas, y el primero que cambiara una contraseña dejaría fuera a
todos los demás hasta la madrugada. Todo lo demás —crear aulas,
calificar, pasar asistencia, importar usuarios— se puede tocar, porque
es justo lo que quieres que toquen.

## Lo que NO debe salir a internet así

- **Un colegio de verdad no va en el mismo servidor que el demo.** Las
  cuentas de prueba tienen la contraseña escrita en la pantalla de
  entrada.
- **`MODO_DEMO` fuera** en una instalación real, o el colegio no podrá
  cambiar sus propias contraseñas.
- **`reiniciar_demo.php` fuera** de una instalación real. Borra la base.
  Bórralo del servidor o quítale el token.
- **`CHATBOT_CLAVE_MAESTRA`** solo en `config.local.php`, que está
  fuera del repositorio. Si falta, la plataforma se niega a guardar
  claves de API en vez de guardarlas en claro.
