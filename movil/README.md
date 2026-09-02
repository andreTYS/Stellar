# App móvil de INNOVA-STEAM

App Flutter para **estudiante** y **apoderado**. Los otros cuatro roles siguen
en la web, donde una pantalla grande ayuda a calificar y ver reportes.

El backend es el mismo PHP de siempre: la app no lo sustituye, lo consume.

## Antes de empezar

Levanta la plataforma en local desde la raíz del repositorio.

**Linux o macOS:**

```bash
./local.sh --reset
```

Deja la web en `http://localhost:8000/innovasteam`.

**Windows (con XAMPP):**

```bat
local.bat --reset
```

Deja la web en `http://localhost/innovasteam` — sin puerto, porque la
sirve Apache. Ten arrancados Apache y MySQL desde el panel de XAMPP antes
de ejecutarlo.

En ambos casos quedan cargados los seis roles de demostración.

## Arrancar la app

Requiere **Flutter 3.27 o superior** (Dart 3.6): el código usa
`Color.withValues`, que no existe en versiones anteriores.

```bash
cd movil
flutter pub get
flutter run --dart-define=API_URL=http://10.0.2.2:8000/innovasteam
```

### La URL depende de dónde corra la app

| Dónde | Linux/macOS (`local.sh`) | Windows (XAMPP) |
|---|---|---|
| Emulador de Android | `http://10.0.2.2:8000/innovasteam` | `http://10.0.2.2/innovasteam` |
| Simulador de iOS | `http://localhost:8000/innovasteam` | — |
| Móvil real por wifi | `http://IP-DE-TU-PC:8000/innovasteam` | `http://IP-DE-TU-PC/innovasteam` |

En Windows no lleva puerto porque Apache escucha en el 80.

`10.0.2.2` es la dirección con la que el emulador de Android ve al ordenador
anfitrión. Usar `localhost` ahí apunta al propio emulador y la app no conecta:
es el error más común la primera vez.

Para un móvil real, saca la IP con `hostname -I` (Linux) o `ipconfig`
(Windows), y comprueba que el cortafuegos deje pasar el puerto 8000.

### Tráfico sin cifrar en desarrollo

Android e iOS bloquean HTTP en claro por omisión. Para probar contra el
servidor local hace falta permitirlo:

- **Android** — en `android/app/src/main/AndroidManifest.xml`, dentro de
  `<application>`: `android:usesCleartextTraffic="true"`
- **iOS** — en `ios/Runner/Info.plist`, añadir `NSAppTransportSecurity` con
  `NSAllowsLocalNetworking` a `true`

Quita ambos antes de publicar: en producción el servidor va con HTTPS.

## Cuentas de prueba

Contraseña `password` en todas.

| Rol | Usuario | Qué se ve |
|---|---|---|
| Estudiante | `EST-001` | Cursos, progreso, módulos con bloqueo por avance |
| Apoderado | `apoderado@innovasteam.edu.pe` | Sus hijos, con módulos, estrellas y última conexión |

El estudiante entra con su **código**, no con correo. El campo acepta
cualquiera de los dos, igual que el formulario de la web.

## Cómo está organizado

```
lib/
  main.dart                     arranque, tema y reparto por rol
  api/cliente.dart              cliente HTTP, token y modelos
  pantallas/login.dart
  pantallas/inicio_estudiante.dart
  pantallas/hijos_apoderado.dart
```

## Cómo se conecta a los datos

La web usa la cookie de sesión de PHP. Una app no puede: el backend le
respondería con un 302 hacia `login.php` y recibiría HTML donde espera JSON.
Por eso hay una capa aparte, en `includes/api_auth.php`.

1. `POST /api/auth.php?accion=login` con usuario y contraseña.
2. El servidor devuelve un token de 256 bits. En la base solo queda su
   hash SHA-256: quien lea esa tabla no puede suplantar a nadie.
3. La app lo guarda y lo manda en cada petición como
   `Authorization: Bearer <token>`.
4. Si el servidor responde **401**, el token caducó o fue revocado. El
   cliente lo borra y vuelve al login; eso lo hace solo `ClienteApi`.

El login comparte el límite de intentos de la web —5 por cuenta, 40 por IP—
para que la API no sea una puerta sin protección.

### Endpoints

| Método y ruta | Rol | Devuelve |
|---|---|---|
| `POST /api/auth.php?accion=login` | — | token y datos del usuario |
| `GET /api/auth.php?accion=yo` | cualquiera | usuario del token |
| `POST /api/auth.php?accion=logout` | cualquiera | revoca ese token |
| `GET /api/movil.php?recurso=inicio` | estudiante | resumen y cursos |
| `GET /api/movil.php?recurso=curso&id=N` | estudiante | módulos del curso |
| `GET /api/movil.php?recurso=hijos` | apoderado | hijos vinculados |

Cada respuesta lleva `ok: true`, o `ok: false` con `error` y un código HTTP
que ya explica qué pasó. Los mensajes vienen listos para mostrarse tal cual,
incluido el del bloqueo por intentos fallidos.

### Probar la API sin la app

```bash
TOKEN=$(curl -s -X POST 'http://localhost:8000/innovasteam/api/auth.php?accion=login' \
  -H 'Content-Type: application/json' \
  -d '{"usuario":"EST-001","password":"password"}' \
  | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

curl -s 'http://localhost:8000/innovasteam/api/movil.php?recurso=inicio' \
  -H "Authorization: Bearer $TOKEN"
```

## Lo que todavía no hace

Es la base de la conexión de datos, no la app terminada:

- **Sin modo offline.** Cada pantalla pide los datos al servidor. Guardar una
  copia en SQLite y sincronizar al recuperar señal es el siguiente paso, y es
  el motivo de fondo para hacer la app: en un colegio con internet
  intermitente marca la diferencia entre usarla y no usarla.
- **Sin notificaciones push.** Hace falta Firebase Cloud Messaging y guardar
  el identificador del dispositivo junto al token.
- **Sin subir entregables.** Falta el endpoint de subida por token y la
  pantalla con la cámara.
- **El token va en `shared_preferences`.** Para producción conviene
  `flutter_secure_storage`, que usa el llavero del sistema.

## Nota sobre este código

El backend está verificado contra una instalación limpia: 13 pruebas del
contrato de la API pasan, incluido que un acceso sin token responde 401 en
JSON y no un 302 a HTML. **El código Dart no está compilado**: el entorno
donde se escribió no tenía Flutter ni Dart instalados, así que puede necesitar
algún ajuste en el primer `flutter run`.
