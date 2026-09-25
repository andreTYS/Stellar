# INNOVA-STEAM

Plataforma educativa STEAM para colegios de **Moquegua, Perú**.

Los 35 módulos no empiezan por una definición: empiezan por una persona
del valle y un problema real —un precio en el mercado, un recibo de luz,
un andén que hay que regar— y llegan a la matemática, a la ciencia y a la
ingeniería desde ahí. Alrededor del contenido está lo que un colegio
necesita para usarlo de verdad: aulas, asistencia, rúbricas, portafolios,
certificados y reportes para la DRE.

## Cómo está hecho

| | |
|---|---|
| Servidor | PHP 8.1+ con PDO, sin framework |
| Base de datos | MariaDB 10.6+ / MySQL 8 |
| Interfaz | HTML + CSS con tokens de diseño, Alpine.js, Chart.js, iconos Lucide |
| Dependencias del navegador | servidas desde `assets/vendor/`, nada de CDN |
| App móvil | Flutter 3.27 (`movil/`) |
| App de escritorio | Electron (`electron/`) |
| Sitio público | estático, generado con `herramientas/construir_sitio.php` |

No hay build para la parte web: los archivos PHP se sirven tal cual.

## Instalación

Hace falta PHP 8.1 o superior con `pdo_mysql`, y MariaDB o MySQL.

```bash
git clone https://github.com/andreTYS/Stellar.git innovasteam
cd innovasteam

# 1. Base de datos (schema + migraciones + datos de demostración)
./herramientas/instalar.sh

# 2. Ajustes propios de la instalación
cp includes/config.local.example.php includes/config.local.php
#    y edita ahí las credenciales de la base

# 3. A andar
php -S localhost:8000 -t ..
```

La plataforma queda en <http://localhost:8000/innovasteam/>, porque
`BASE_URL` vale `/innovasteam`. En XAMPP, pon el proyecto en
`htdocs/innovasteam/`; con `php -S`, sirve el directorio padre y
llama a la carpeta `innovasteam`.

`herramientas/instalar.sh` **borra y recrea** la base `innovasteam`.
Ejecuta `schema.sql`, después `migrations/*.sql` en orden numérico y
por último `seed_data.sql`; ese orden importa y el script se para en el
primer error en vez de dejar la base a medias.

### Cuentas de demostración

Todas con la contraseña `password`:

| Rol | Entra con |
|---|---|
| Administrador de la plataforma | `admin@innovasteam.edu.pe` |
| Director de colegio | `admin_col@innovasteam.edu.pe` |
| Docente | `docente@innovasteam.edu.pe` |
| Practicante | `practicante@innovasteam.edu.pe` |
| Apoderado | `apoderado@innovasteam.edu.pe` |
| Estudiante | `EST-001` *(código de acceso, no correo)* |

Los estudiantes entran con un **código**, no con un correo: en primaria
la mayoría no tiene cuenta de correo, y pedirles una era pedirles que no
entraran.

## Qué hay dentro

```
admin/           administración de la plataforma (colegios, usuarios, catálogo)
admin_colegio/   el director de un colegio: aulas, planificación, reporte DRE
docente/         aulas, estudiantes, portafolios, asistencia, planificación
practicante/     sesiones y asistencia desde el aula
estudiante/      cursos, módulos, portafolio, logros, certificados
apoderado/       seguimiento de los hijos
stellarscribe/   historias, simuladores y la biblioteca de ciencia
api/             endpoints JSON (sesión web o token Bearer para la app)
includes/        configuración, funciones compartidas, cabecera y pie
migrations/      cambios de esquema, en orden numérico
herramientas/    instalador y generador del sitio público
movil/ electron/ apps de móvil y escritorio
docs/            cómo funciona cada parte por dentro
```

## Los seis roles

| Rol | Para qué |
|---|---|
| `admin` | Toda la plataforma: colegios, cuentas, catálogo de módulos |
| `admin_colegio` | Su colegio: aulas, docentes, planificación, reporte para la DRE |
| `docente` | Sus aulas: planificación, asistencia, portafolios y calificación con rúbrica |
| `practicante` | Pasa asistencia y registra sesiones, también desde el celular |
| `estudiante` | Estudia los módulos, sube entregables, gana logros y certificados |
| `apoderado` | Ve el avance de sus hijos |

Cada rol ve solo lo suyo, y eso se comprueba en el servidor: un docente
solo abre los portafolios de sus aulas, un director solo planifica las
aulas de su colegio.

## Para seguir leyendo

- [`docs/contenido.md`](docs/contenido.md) — cómo está armado el catálogo y cómo se añade un módulo
- [`docs/stellarscribe.md`](docs/stellarscribe.md) — las historias y los simuladores
- [`docs/ciencia.md`](docs/ciencia.md) — la biblioteca de artículos de ciencia
- [`docs/asistente.md`](docs/asistente.md) — el asistente de estudio y cómo se guardan sus claves
- [`docs/sin-conexion.md`](docs/sin-conexion.md) — la cola de envíos cuando se cae internet
- [`docs/github-pages.md`](docs/github-pages.md) — el sitio público estático

## Licencia y datos

Los datos que maneja la plataforma son de menores de edad. Antes de
poner en marcha un colegio, lee cómo se guardan las claves del asistente
(`docs/asistente.md`): `CHATBOT_CLAVE_MAESTRA` vive en
`includes/config.local.php`, que está fuera del repositorio, y sin esa
clave la plataforma se niega a guardar una API key en vez de guardarla
en claro.
