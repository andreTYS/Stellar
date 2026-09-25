#!/usr/bin/env bash
# ============================================================
# INNOVA-STEAM — Instalar o reinstalar la base de datos
#
#   ./herramientas/instalar.sh            (pide confirmación)
#   ./herramientas/instalar.sh --si       (sin preguntar, para CI)
#
# Ejecuta, en este orden y no en otro:
#   1. schema.sql        crea la base y las tablas de origen
#   2. migrations/*.sql  en orden numérico
#   3. seed_data.sql     los datos de demostración
#
# BORRA la base «innovasteam» entera. No lo ejecutes sobre un colegio
# en marcha: schema.sql empieza con una tanda de DROP TABLE.
#
# Se para en el primer error en vez de seguir: una migración que falla
# a la mitad deja la base en un estado que después nadie sabe leer.
# ============================================================
set -euo pipefail

cd "$(dirname "$0")/.."

MYSQL=${MYSQL:-mysql}
USUARIO=${DB_USER:-root}
CLAVE=${DB_PASS:-}

args=(-u"$USUARIO" --default-character-set=utf8mb4)
[[ -n "$CLAVE" ]] && args+=(-p"$CLAVE")

if [[ "${1:-}" != "--si" ]]; then
  echo "Esto BORRA la base de datos «innovasteam» y la vuelve a crear."
  read -r -p "¿Seguir? [escribe: si] " respuesta
  [[ "$respuesta" == "si" ]] || { echo "Cancelado."; exit 1; }
fi

# Se tira la base entera antes de empezar, en vez de fiarse de la
# tanda de DROP TABLE de schema.sql. Esa lista hay que ir ampliándola a
# mano cada vez que una migración añade una tabla, y cuando se queda
# corta el archivo muere a media ejecución con un error 1451 de clave
# foránea, dejando media docena de tablas ya borradas. Esto no se puede
# quedar corto.
echo "0/3  borrando la base anterior"
"$MYSQL" "${args[@]}" -e "DROP DATABASE IF EXISTS innovasteam;"

echo "1/3  schema.sql"
"$MYSQL" "${args[@]}" < schema.sql

echo "2/3  migraciones"
for f in migrations/[0-9]*.sql; do
  printf '     %-48s' "$(basename "$f")"
  if "$MYSQL" "${args[@]}" innovasteam < "$f" 2>/tmp/innova_migracion_error.txt; then
    echo "ok"
  else
    echo "FALLA"
    head -5 /tmp/innova_migracion_error.txt
    exit 1
  fi
done

echo "3/3  seed_data.sql"
"$MYSQL" "${args[@]}" innovasteam < seed_data.sql

echo
echo "Listo. Entra con cualquiera de estas cuentas y la contraseña «password»:"
echo "  admin@innovasteam.edu.pe        administrador de la plataforma"
echo "  admin_col@innovasteam.edu.pe    director de colegio"
echo "  docente@innovasteam.edu.pe      docente"
echo "  practicante@innovasteam.edu.pe  practicante"
echo "  apoderado@innovasteam.edu.pe    apoderado"
echo "  EST-001                         estudiante (entra con código, no con correo)"
