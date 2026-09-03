@echo off
REM ============================================================
REM  Prepara la app Flutter para poder ejecutarla.
REM
REM  El repositorio solo trae el codigo Dart (lib/ y pubspec.yaml).
REM  Las carpetas android/ e ios/ las genera Flutter y no se versionan,
REM  asi que hay que crearlas una vez en cada maquina.
REM
REM  Uso:   cd movil
REM         preparar.bat
REM ============================================================
setlocal enabledelayedexpansion
cd /d "%~dp0"

echo.
echo ==== Preparando la app movil ====
echo.

where flutter >nul 2>&1
if errorlevel 1 (
  echo [ERROR] Flutter no esta instalado o no esta en el PATH.
  echo         Descargalo de https://docs.flutter.dev/get-started/install/windows
  echo         Comprueba luego con:  flutter --version
  goto :fin
)

for /f "tokens=*" %%v in ('flutter --version 2^>nul ^| findstr /i "Flutter"') do (
  echo   %%v
  goto :seguir
)
:seguir

REM ---- 1. Generar la carpeta android/ ------------------------
if exist "android" (
  echo   [ok] La carpeta android/ ya existe.
) else (
  echo   Generando android/ ...
  flutter create --platforms=android --project-name innovasteam .
  if errorlevel 1 (
    echo [ERROR] flutter create fallo.
    goto :fin
  )
  echo   [ok] Carpeta android/ creada.
)

REM ---- 2. Restaurar el codigo por si flutter create lo piso ---
REM flutter create puede sobrescribir lib/main.dart con su plantilla.
REM Como el codigo esta en git, se recupera sin perder nada.
git rev-parse --is-inside-work-tree >nul 2>&1
if not errorlevel 1 (
  git checkout -- lib 2>nul
  if not errorlevel 1 echo   [ok] Codigo de lib/ verificado contra git.
)

REM ---- 3. Permitir HTTP en desarrollo -------------------------
REM Android bloquea el trafico sin cifrar desde Android 9. Sin esto la
REM app no conecta con el servidor local y falla sin explicar por que.
set "MANIFEST=android\app\src\main\AndroidManifest.xml"
if exist "%MANIFEST%" (
  set "PS=%TEMP%\is_manifest.ps1"
  > "!PS!" echo $f = '%MANIFEST%'
  >>"!PS!" echo $c = Get-Content $f -Raw
  >>"!PS!" echo if ($c -notmatch 'usesCleartextTraffic') {
  >>"!PS!" echo     $c = $c -replace '(?m)^(\s*)(^<application)', "`$1`$2`r`n`$1    android:usesCleartextTraffic=`"true`""
  >>"!PS!" echo     Set-Content -Path $f -Value $c -NoNewline -Encoding UTF8
  >>"!PS!" echo     Write-Output 'PARCHEADO'
  >>"!PS!" echo } else { Write-Output 'YA_ESTABA' }
  for /f %%r in ('powershell -NoProfile -ExecutionPolicy Bypass -File "!PS!" 2^>nul') do set "RES=%%r"
  del "!PS!" >nul 2>&1
  if /I "!RES!"=="PARCHEADO" echo   [ok] HTTP permitido en desarrollo ^(usesCleartextTraffic^).
  if /I "!RES!"=="YA_ESTABA" echo   [ok] HTTP ya estaba permitido.
  if "!RES!"=="" (
    echo   [aviso] No se pudo parchear el manifiesto automaticamente.
    echo           Abre %MANIFEST% y dentro de ^<application^> anade:
    echo               android:usesCleartextTraffic="true"
  )
)

REM ---- 4. Dependencias ---------------------------------------
echo   Descargando dependencias ...
flutter pub get
if errorlevel 1 (
  echo [ERROR] flutter pub get fallo.
  goto :fin
)

echo.
echo ============================================================
echo   Listo. Arranca un emulador de Android y ejecuta:
echo.
echo     flutter run --dart-define=API_URL=http://10.0.2.2/innovasteam
echo.
echo   Para un movil real conectado por USB, usa la IP de este PC
echo   ^(mirala con ipconfig^):
echo.
echo     flutter run --dart-define=API_URL=http://TU_IP/innovasteam
echo.
echo   Comprueba antes que http://localhost/innovasteam abre en el
echo   navegador: si la web no responde, la app tampoco lo hara.
echo ============================================================

:fin
echo.
endlocal
pause
