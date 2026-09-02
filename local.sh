#!/usr/bin/env bash
# ============================================================
# INNOVA-STEAM — Levantar el proyecto en local
#
#   ./local.sh              arranca base de datos y servidor web
#   ./local.sh --reset      recrea la base desde cero con datos demo
#   ./local.sh --parar      detiene lo que arrancó este script
#
# Deja la plataforma en http://localhost:8000/innovasteam
# ============================================================
set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCROOT="${TMPDIR:-/tmp}/innovasteam-docroot"
PUERTO="${PUERTO:-8000}"
BD="innovasteam"
DBUSER="${DBUSER:-root}"

rojo()  { printf '\033[31m%s\033[0m\n' "$*"; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }
info()  { printf '  %s\n' "$*"; }

# ── Parar ────────────────────────────────────────────────────
if [ "${1:-}" = "--parar" ]; then
  pkill -f "php -S 127.0.0.1:$PUERTO" 2>/dev/null && verde "Servidor web detenido." || info "No había servidor web."
  exit 0
fi

# ── Comprobaciones ───────────────────────────────────────────
command -v php   >/dev/null || { rojo "Falta PHP. Instala php-cli y php-mysql."; exit 1; }
command -v mysql >/dev/null || { rojo "Falta el cliente de MySQL/MariaDB."; exit 1; }

php -m | grep -qi pdo_mysql || {
  rojo "PHP no tiene la extensión pdo_mysql."
  info "Debian/Ubuntu:  sudo apt install php-mysql"
  exit 1
}

echo "── Base de datos ──"
if ! mysqladmin ping --silent 2>/dev/null; then
  info "El servidor de base de datos no responde; intentando arrancarlo…"
  (sudo service mariadb start 2>/dev/null || sudo service mysql start 2>/dev/null \
    || mariadbd-safe >/dev/null 2>&1 &) || true
  sleep 6
  mysqladmin ping --silent 2>/dev/null || { rojo "No se pudo arrancar MariaDB/MySQL."; exit 1; }
fi
verde "Base de datos en marcha."

# ── Esquema ──────────────────────────────────────────────────
existe=$(mysql -u "$DBUSER" -N -B -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$BD';" 2>/dev/null || echo 0)

if [ "${1:-}" = "--reset" ] || [ "$existe" -eq 0 ]; then
  [ "${1:-}" = "--reset" ] && info "Recreando la base desde cero…" || info "Base vacía; creándola…"
  mysql -u "$DBUSER" -e "DROP DATABASE IF EXISTS $BD; CREATE DATABASE $BD CHARACTER SET utf8mb4;"
  mysql -u "$DBUSER" "$BD" < "$RAIZ/schema.sql"

  # Las migraciones van ANTES del seed: la 005 añade 'apoderado' al ENUM
  # de rol y crea apoderado_estudiante, sin lo cual el apoderado de
  # demostración no se puede insertar. Son idempotentes.
  for f in "$RAIZ"/migrations/*.sql; do
    info "migración $(basename "$f")"
    mysql -u "$DBUSER" "$BD" < "$f"
  done

  mysql -u "$DBUSER" "$BD" < "$RAIZ/seed_data.sql"
  verde "Esquema, migraciones y datos de demostración cargados."
else
  # Aplicar migraciones nuevas sobre una base existente.
  for f in "$RAIZ"/migrations/*.sql; do
    mysql -u "$DBUSER" "$BD" < "$f" 2>/dev/null || true
  done
  info "Base existente ($existe tablas); migraciones al día."
fi

# ── Servidor web ─────────────────────────────────────────────
# BASE_URL vale '/innovasteam', así que la app tiene que servirse desde
# esa ruta: se monta un docroot con un enlace con ese nombre.
echo
echo "── Servidor web ──"
mkdir -p "$DOCROOT"
ln -sfn "$RAIZ" "$DOCROOT/innovasteam"

pkill -f "php -S 127.0.0.1:$PUERTO" 2>/dev/null || true
sleep 1

# Sin varios workers, el servidor embebido de PHP atiende de uno en uno
# y las peticiones simultáneas de la página fallan con la conexión cortada.
PHP_CLI_SERVER_WORKERS=6 nohup php -S "127.0.0.1:$PUERTO" -t "$DOCROOT" \
  > "${TMPDIR:-/tmp}/innovasteam-php.log" 2>&1 &

sleep 3
if curl -sf -o /dev/null "http://127.0.0.1:$PUERTO/innovasteam/login.php"; then
  verde "Servidor web en marcha."
else
  rojo "El servidor no respondió. Revisa ${TMPDIR:-/tmp}/innovasteam-php.log"
  exit 1
fi

# ── Resumen ──────────────────────────────────────────────────
IP=$(hostname -I 2>/dev/null | awk '{print $1}')
cat <<FIN

════════════════════════════════════════════════════
  Plataforma   http://localhost:$PUERTO/innovasteam
  API móvil    http://localhost:$PUERTO/innovasteam/api

  Cuentas de demostración (contraseña: password)
    admin@innovasteam.edu.pe        administrador
    admin_col@innovasteam.edu.pe    director
    docente@innovasteam.edu.pe      docente
    practicante@innovasteam.edu.pe  practicante
    EST-001                         estudiante
    apoderado@innovasteam.edu.pe    apoderado

  App Flutter — la URL depende de dónde corra:
    emulador Android   --dart-define=API_URL=http://10.0.2.2:$PUERTO/innovasteam
    simulador iOS      --dart-define=API_URL=http://localhost:$PUERTO/innovasteam
    móvil real         --dart-define=API_URL=http://${IP:-TU_IP}:$PUERTO/innovasteam

  Detener:  ./local.sh --parar
════════════════════════════════════════════════════
FIN
