#!/bin/sh
# Se ejecuta una sola vez, al crear el volumen de MariaDB.
#
# El usuario de la aplicación necesita crear y borrar una base por panadería
# (obrador_luna, obrador_sol...). En lugar de darle permisos globales, se le
# dan sobre las bases con el prefijo de las panaderías: la central
# (obrador_central) y las de cada una. En el patrón de GRANT "_" es comodín,
# por eso se escapa.
set -e

prefijo=$(printf '%s' "${TENANT_DB_PREFIX:-obrador_}" | sed 's/_/\\_/g')

mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" <<SQL
GRANT ALL PRIVILEGES ON \`${prefijo}%\`.* TO '${MARIADB_USER}'@'%';
FLUSH PRIVILEGES;
SQL
