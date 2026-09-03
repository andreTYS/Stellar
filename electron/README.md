# INNOVA-STEAM de escritorio

Envoltorio de Electron: abre la misma plataforma web en una ventana propia,
con menú nativo y sin barra de direcciones. **No lleva el servidor dentro** —
necesita XAMPP corriendo en la misma máquina, o apuntar a un servidor remoto.

## Requisitos

- **Node.js 18 o superior** — https://nodejs.org (instalador LTS de Windows)
- **XAMPP** con Apache y MySQL arrancados
- La plataforma cargando en `http://localhost/innovasteam`

Comprueba lo último en el navegador antes de seguir. Si la web no abre,
la aplicación de escritorio tampoco lo hará.

## Probarla

```bat
cd electron
npm install
npm start
```

`npm install` tarda unos minutos la primera vez: descarga Electron, que son
unos 200 MB.

### Apuntar a otro servidor

Por omisión abre `http://localhost/innovasteam`. Para usar otro:

```bat
set INNOVA_URL=http://192.168.1.42/innovasteam
npm start
```

En el VPS sería la URL pública con HTTPS.

## Generar el instalador de Windows

```bat
npm run build:win
```

Deja en `dist\` un instalador `.exe` que se puede repartir a los colegios.
Incluye las dos arquitecturas (64 y 32 bits).

Los otros dos objetivos, `build:mac` y `build:linux`, solo funcionan del todo
ejecutándose en ese sistema.

### Sobre el icono

El repositorio no incluye `electron/assets/`, así que la aplicación usa el
icono por omisión de Electron. Para poner el propio, crea esa carpeta con:

- `icon.ico` para Windows — 256×256, formato ICO real
- `icon.png` para Linux — 512×512
- `icon.icns` para macOS

Y añade en `package.json`, dentro de `build`, la línea `"icon"` en cada
plataforma. Antes las rutas estaban puestas pero los archivos no existían,
y `electron-builder` fallaba al compilar.

## Qué hace y qué no

Es una ventana sobre la web, no una aplicación independiente:

- **Sí** — menú nativo con accesos a StellarScribe y simuladores, zoom,
  pantalla completa, y una pantalla de error clara cuando el servidor no
  responde, con botón de reintentar.
- **No** — no funciona sin el servidor. Si el colegio se queda sin la máquina
  que corre XAMPP, la aplicación no muestra nada.

Para uso realmente sin conexión, lo indicado es la app de Flutter con base de
datos local (ver `movil/`), no este envoltorio.
