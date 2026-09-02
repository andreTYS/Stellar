@echo off
REM ============================================================
REM  INNOVA-STEAM - Levantar el proyecto en Windows (XAMPP)
REM
REM    local.bat            arranca y carga la base si hace falta
REM    local.bat --reset    recrea la base desde cero con datos demo
REM
REM  Deja la plataforma en http://localhost/innovasteam
REM ============================================================
setlocal enabledelayedexpansion

set "RAIZ=%~dp0"
if "%RAIZ:~-1%"=="\" set "RAIZ=%RAIZ:~0,-1%"

REM Cambia esta linea si instalaste XAMPP en otra carpeta.
if "%XAMPP%"=="" set "XAMPP=C:\xampp"

set "PHP=%XAMPP%\php\php.exe"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "HTDOCS=%XAMPP%\htdocs"
set "BD=innovasteam"

echo.
echo ==== INNOVA-STEAM ====
echo.

REM ---- Comprobaciones ----------------------------------------
if not exist "%PHP%" (
  echo [ERROR] No se encontro PHP en "%PHP%"
  echo         Instala XAMPP desde https://www.apachefriends.org
  echo         Si lo tienes en otra carpeta:  set XAMPP=D:\xampp  y vuelve a ejecutar.
  goto :fin
)
if not exist "%MYSQL%" (
  echo [ERROR] No se encontro MySQL en "%MYSQL%"
  goto :fin
)

REM ---- MySQL en marcha? --------------------------------------
"%MYSQL%" -u root -e "SELECT 1;" >nul 2>&1
if errorlevel 1 (
  echo [ERROR] MySQL no responde.
  echo         Abre el Panel de Control de XAMPP y pulsa Start en MySQL.
  echo         Deja tambien Apache arrancado.
  goto :fin
)
echo   [ok] MySQL responde.

REM ---- Enlazar el proyecto dentro de htdocs -------------------
REM BASE_URL vale '/innovasteam', asi que la app tiene que servirse
REM desde esa ruta. Un enlace de directorio evita copiar archivos.
if /I not "%RAIZ%"=="%HTDOCS%\innovasteam" (
  if not exist "%HTDOCS%\innovasteam" (
    mklink /J "%HTDOCS%\innovasteam" "%RAIZ%" >nul 2>&1
    if errorlevel 1 (
      echo [ERROR] No se pudo crear el enlace en htdocs.
      echo         Copia esta carpeta a %HTDOCS%\innovasteam y vuelve a ejecutar.
      goto :fin
    )
    echo   [ok] Enlace creado en %HTDOCS%\innovasteam
  ) else (
    echo   [ok] Ya existe %HTDOCS%\innovasteam
  )
)

REM ---- Base de datos -----------------------------------------
set "RESET=no"
if /I "%~1"=="--reset" set "RESET=si"

for /f %%c in ('"%MYSQL%" -u root -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='%BD%';" 2^>nul') do set "TABLAS=%%c"
if "%TABLAS%"=="" set "TABLAS=0"

if "%RESET%"=="si" goto :cargar
if "%TABLAS%"=="0" goto :cargar
echo   [ok] Base existente con %TABLAS% tablas.
goto :migrar

:cargar
if "%RESET%"=="si" (echo   Recreando la base desde cero...) else (echo   Base vacia; creandola...)
"%MYSQL%" -u root -e "DROP DATABASE IF EXISTS %BD%; CREATE DATABASE %BD% CHARACTER SET utf8mb4;"
"%MYSQL%" -u root %BD% < "%RAIZ%\schema.sql"
if errorlevel 1 echo   [aviso] schema.sql termino con errores.

:migrar
REM Las migraciones van ANTES del seed: la 005 anade 'apoderado' al ENUM
REM de rol, sin lo cual el apoderado de demostracion no se puede crear.
for %%f in ("%RAIZ%\migrations\*.sql") do (
  echo   migracion %%~nxf
  "%MYSQL%" -u root %BD% < "%%f" 2>nul
)

if "%TABLAS%"=="0" goto :seed
if "%RESET%"=="si" goto :seed
goto :listo

:seed
"%MYSQL%" -u root %BD% < "%RAIZ%\seed_data.sql"
if errorlevel 1 (echo   [aviso] seed_data.sql termino con errores.) else (echo   [ok] Datos de demostracion cargados.)

:listo
REM ---- Apache en marcha? -------------------------------------
REM curl viene con Windows 10 desde la version 1803.
set "APACHE=NO"
curl -s -o nul -f "http://localhost/innovasteam/login.php" >nul 2>&1
if not errorlevel 1 set "APACHE=SI"

echo.
echo ============================================================
if /I "%APACHE%"=="SI" (
  echo   Plataforma   http://localhost/innovasteam
  echo   API movil    http://localhost/innovasteam/api
) else (
  echo   [aviso] Apache no responde todavia.
  echo           Abre el Panel de XAMPP y pulsa Start en Apache.
  echo           Luego entra a  http://localhost/innovasteam
)
echo.
echo   Cuentas de demostracion ^(contrasena: password^)
echo     admin@innovasteam.edu.pe        administrador
echo     admin_col@innovasteam.edu.pe    director
echo     docente@innovasteam.edu.pe      docente
echo     practicante@innovasteam.edu.pe  practicante
echo     EST-001                         estudiante
echo     apoderado@innovasteam.edu.pe    apoderado
echo.
echo   App Flutter ^(emulador de Android^):
echo     flutter run --dart-define=API_URL=http://10.0.2.2/innovasteam
echo.
echo   Para un movil real, usa la IP de este PC ^(ipconfig^):
echo     flutter run --dart-define=API_URL=http://TU_IP/innovasteam
echo ============================================================

:fin
echo.
endlocal
pause
