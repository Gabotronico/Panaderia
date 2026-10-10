#!/bin/sh
# Preparación de Obrador en cada arranque del contenedor, antes de que nginx
# y PHP-FPM empiecen a atender:
#   1. migra la base central (panaderías, dominios, superadministradores);
#   2. migra la base de cada panadería, para que una actualización con
#      migraciones nuevas las alcance a todas;
#   3. cachea configuración, eventos y vistas.
# Las rutas no se cachean: las de las panaderías las registra stancl/tenancy
# al arrancar y quedarían duplicadas.
set -e

cd /var/www/html

if [ -z "$APP_KEY" ]; then
    echo "Falta APP_KEY en .env.produccion. Generala con: docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show" >&2
    exit 1
fi

php artisan migrate --force
php artisan tenants:migrate
php artisan config:cache
php artisan event:cache
php artisan view:cache
