# syntax=docker/dockerfile:1

# ── Assets: CSS/JS compilados con Vite ──────────────────────────────────────
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ── Aplicación: PHP-FPM + nginx (escucha en 8080) ──────────────────────────
FROM serversideup/php:8.3-fpm-nginx-bookworm-v3.6.0

USER root
# gd: imágenes dentro de los PDF de dompdf. intl: formatos de Laravel.
RUN install-php-extensions gd intl
COPY --chmod=755 docker/entrypoint.d/ /etc/entrypoint.d/

USER www-data
WORKDIR /var/www/html

# Primero solo las dependencias, para que la capa se reutilice mientras
# composer.lock no cambie.
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev --no-interaction

ENV PHP_OPCACHE_ENABLE=1 \
    PHP_DATE_TIMEZONE=America/La_Paz \
    # Respaldos de hasta 512 MB (BackupController) y altas que migran una
    # base entera.
    PHP_UPLOAD_MAX_FILE_SIZE=512M \
    PHP_POST_MAX_SIZE=520M \
    PHP_MAX_EXECUTION_TIME=300 \
    PHP_MEMORY_LIMIT=512M \
    # El arranque lo maneja docker/entrypoint.d/60-obrador.sh, que además
    # migra la base de cada panadería.
    AUTORUN_ENABLED=false
