# El sitio público en GitHub Pages

## Lo primero: qué NO se puede publicar ahí

GitHub Pages **solo sirve archivos**. No ejecuta PHP y no tiene base de datos.

La plataforma completa —login, aulas, módulos, calificación con rúbrica,
asistencia, portafolios, certificados, el asistente, la API móvil— necesita un
servidor con PHP 8 y MySQL o MariaDB. **Eso no corre en Pages y no hay forma de
que corra.** Va en el servidor del colegio o en su VPS.

## Lo que sí se publica

La cara pública del proyecto, que es justo lo que hace falta tener en una URL
para pasarle a un director:

| Sección | Qué es |
|---|---|
| Portada | Qué es INNOVA-STEAM, con enlaces a lo que sí se puede ver |
| Biblioteca de ciencia | Los 18 artículos con sus diagramas, sin cuenta |
| StellarScribe | Las dos historias interactivas, jugables enteras |
| Acceso | Qué incluye la plataforma completa y cómo instalarla |

Los simuladores **no** están: registran el tiempo de uso de cada estudiante, así
que necesitan servidor y cuenta. Publicarlos a medias sería peor que no
publicarlos.

## Cómo se genera

```bash
php herramientas/construir_sitio.php --base=/Stellar
```

Deja el sitio en `_site/`, que no se versiona: es un artefacto de compilación.

El parámetro `--base` importa. En un Pages de proyecto el sitio cuelga de
`https://<usuario>.github.io/<repo>/`, no de la raíz del dominio. Si la base no
coincide, se rompen todos los enlaces y las hojas de estilo. El flujo de
GitHub lo deriva solo del nombre del repositorio.

### Sin base de datos

En el runner de GitHub no hay MySQL. El generador lee entonces
`herramientas/contenido_ciencia.json`, un volcado de los artículos que se
actualiza **solo cuando se construye en una máquina que sí tiene base de
datos**.

Es decir: si añades artículos, ejecuta el generador en local una vez y commitea
el volcado actualizado. Si no, el sitio publicado seguirá mostrando los
anteriores.

## Cómo se despliega

`.github/workflows/pages.yml` construye y publica en cada push a `main` que
toque `herramientas/`, `stellarscribe/` o `assets/`.

**Antes de que funcione la primera vez hay que hacer dos cosas en GitHub, y no
se pueden hacer desde el código:**

1. **Settings → Pages → Source: GitHub Actions.** Si queda en *Deploy from a
   branch*, el flujo corre pero no publica nada.
2. **El flujo vive en `main`.** GitHub solo ejecuta workflows del branch por
   defecto, así que hasta que esto no esté fusionado en `main` no se despliega
   ni aparece el botón de ejecución manual.

Hecho eso, el sitio queda en `https://<usuario>.github.io/<repo>/`.

## Qué comprueba el flujo antes de publicar

Un generador que falle a medias publicaría un sitio vacío sin que nadie se
entere. Por eso el flujo se niega a desplegar si:

- falta la portada, la biblioteca o `historias.js`
- falta el `.nojekyll` (sin él, Jekyll se come las carpetas que empiezan por `_`)
- hay menos de 19 páginas de ciencia (18 artículos y el índice)
- quedó algún `<?php` sin resolver en un `.html`

## Una trampa que ya costó una vez

Las historias se generan **renderizando `historia.php` con PHP**, no recortando
el archivo con expresiones regulares. El primer intento hizo lo segundo y una
regex perezosa se llevó por delante mil líneas —desde un comentario de la
cabecera hasta el condicional del vídeo de intro, con todo el CSS y la pantalla
de inicio en medio—. La página se publicaba en blanco.

Dejar que PHP resuelva el PHP no tiene ese riesgo.
