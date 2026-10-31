#!/bin/sh
# Deja listo el entorno local de desarrollo de Obrador:
#
#   1. crea .env.local a partir de .env.local.example si falta;
#   2. construye y levanta app, base de datos y Vite (compose.local.yaml);
#   3. espera a que la app termine de migrar;
#   4. crea el superadministrador del panel y la panadería de prueba "luna"
#      con su administrador, su cajero, productos e insumos.
#
# Se puede correr las veces que haga falta: no duplica nada.
#
#   sh docker/local/preparar.sh          (Linux, macOS o Git Bash en Windows)
#   docker/local/preparar.ps1            (PowerShell)
set -e

cd "$(dirname "$0")/../.."

COMPOSE="docker compose -f compose.local.yaml"
PANADERIA=luna

if [ ! -f .env.local ]; then
    cp .env.local.example .env.local
    echo "Creado .env.local a partir de .env.local.example."
fi

echo "→ Construyendo y levantando los contenedores (la primera vez tarda varios minutos)..."
$COMPOSE up -d --build

echo "→ Esperando a que la aplicación termine de migrar..."
intentos=0
until curl -fsS http://localhost:8094/up >/dev/null 2>&1; do
    intentos=$((intentos + 1))
    if [ "$intentos" -gt 90 ]; then
        echo "La aplicación no respondió. Mirá los logs con:" >&2
        echo "  docker compose -f compose.local.yaml logs app" >&2
        exit 1
    fi
    sleep 2
done

# Si el correo ya existe, el comando solo le cambia la contraseña.
$COMPOSE exec -T app php artisan obrador:superadmin-crear panel@obrador.local \
    --nombre="Panel de Prueba" --password="Panel12345678"

# El alta es la que crea la base de la panadería y su primer administrador.
# Si ya existe, avisa; no hay nada más que hacer.
# El nombre sin tilde es a propósito: acá solo se crea. El nombre visible con
# acento lo deja después sembrar-demo.php, que está en UTF-8.
alta=$(
    $COMPOSE exec -T app php artisan obrador:tenant-crear "$PANADERIA" \
        --negocio="Panaderia Luna" \
        --admin-nombre="Administrador Luna" \
        --admin-email="admin@luna.local" \
        --admin-password="Luna123456" 2>&1
) || true

case "$alta" in
    # Sin tildes: si la salida llega con otra codificación, igual coincide.
    *"Ya hay una panader"*) echo "La panadería \"$PANADERIA\" ya existía." ;;
    *"creada en la base"*) echo "$alta" ;;
    *) echo "$alta" >&2; exit 1 ;;
esac

echo "→ Cargando los datos de prueba..."
$COMPOSE exec -T app php docker/local/sembrar-demo.php "$PANADERIA"

cat <<FIN

Listo.

  Panel del sistema   http://localhost:8094/panel
                      panel@obrador.local / Panel12345678

  Panadería           http://localhost:8094/$PANADERIA
                      admin@luna.local / Luna123456          (administrador)
                      cajero@luna.local / Cajero12345        (cajero)

  Base de datos       127.0.0.1:3308  (usuario obrador, ver .env.local)
  Vite (recarga)      http://localhost:5173

  Logs                docker compose -f compose.local.yaml logs -f app
  Apagar              docker compose -f compose.local.yaml down
  Borrar todo         docker compose -f compose.local.yaml down -v

FIN
