#!/bin/bash
# ============================================================
# Arranque del contenedor de la plataforma.
#
# Espera a la base, la llena la primera vez, y cede el proceso a
# Apache con exec para que reciba las señales de Docker (sin exec, un
# «docker stop» tendría que esperar los diez segundos de gracia).
# ============================================================
set -e

esperar_base() {
  local intentos=40
  echo "Esperando a la base de datos..."
  until php -r '
      $h=getenv("DB_HOST")?:"localhost"; $n=getenv("DB_NAME"); 
      $u=getenv("DB_USER"); $p=getenv("DB_PASS");
      try { new PDO("mysql:host=$h;dbname=$n", $u, $p); exit(0); }
      catch (Throwable $e) { exit(1); }' 2>/dev/null; do
    intentos=$((intentos - 1))
    if [ "$intentos" -le 0 ]; then
      echo "La base no respondió. Revisa DB_HOST, DB_USER y DB_PASS." >&2
      exit 1
    fi
    sleep 2
  done
  echo "Base lista."
}

# config.local.php a partir de las variables de entorno, que es como se
# configura un contenedor. Si el archivo ya está montado desde fuera,
# no se toca.
if [ ! -f /var/www/html/includes/config.local.php ]; then
  cat > /var/www/html/includes/config.local.php <<PHP
<?php
// Generado por docker/arranque.sh a partir del entorno. No editar:
// se reescribe en cada arranque si no existe.
define('DB_HOST', '${DB_HOST:-db}');
define('DB_NAME', '${DB_NAME:-innovasteam}');
define('DB_USER', '${DB_USER:-innovasteam}');
define('DB_PASS', '${DB_PASS}');
define('BASE_URL', '${BASE_URL}');
define('MODO_DEMO', ${MODO_DEMO:-false});
PHP
  if [ -n "${CHATBOT_CLAVE_MAESTRA}" ]; then
    echo "define('CHATBOT_CLAVE_MAESTRA', '${CHATBOT_CLAVE_MAESTRA}');" \
      >> /var/www/html/includes/config.local.php
  fi
  if [ -n "${DEMO_TOKEN_REINICIO}" ]; then
    echo "define('DEMO_TOKEN_REINICIO', '${DEMO_TOKEN_REINICIO}');" \
      >> /var/www/html/includes/config.local.php
  fi
  chown www-data:www-data /var/www/html/includes/config.local.php
  chmod 640 /var/www/html/includes/config.local.php
fi

esperar_base

# Solo la primera vez: si ya hay usuarios, no se toca nada. Sin esta
# comprobación, cada reinicio del contenedor borraría el trabajo del
# colegio.
YA=$(php -r '
    require "/var/www/html/includes/config.php";
    try {
      $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
      echo (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    } catch (Throwable $e) { echo 0; }' 2>/dev/null || echo 0)

if [ "${YA:-0}" -eq 0 ]; then
  echo "Base vacía: instalando esquema y datos de demostración..."
  php /var/www/html/herramientas/reiniciar_demo.php
else
  echo "La base ya tiene $YA usuarios: no se toca."
fi

exec apache2-foreground
