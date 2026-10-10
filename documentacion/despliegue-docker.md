# Despliegue de Obrador en Contabo con Docker

Tres contenedores, definidos en `compose.yaml`:

| Servicio | Qué hace |
|---|---|
| `caddy` | Recibe el tráfico en 80/443 y obtiene solo los certificados HTTPS de Let's Encrypt para el dominio principal, cada subdominio de panadería y sus dominios propios. |
| `app` | Laravel con PHP 8.3 + nginx. Al arrancar migra la base central y la de cada panadería. |
| `db` | MariaDB 11.4. Una base central (`obrador_central`) y una por panadería (`obrador_<id>`). |

Datos persistentes (volúmenes de Docker): `db` (bases), `storage` (imágenes, respaldos) y `caddy_data` (certificados).

## 1. Requisitos

- VPS de Contabo con Ubuntu 24.04 (con 4 GB de RAM alcanza para varias panaderías).
- Un dominio, por ejemplo `obrador.com`, con dos registros **A** apuntando a la IP del VPS:
  - `obrador.com`
  - `*.obrador.com` (comodín: cubre `luna.obrador.com`, `sol.obrador.com`, …)
- Si el DNS está en Cloudflare, ambos registros en **DNS only** (nube gris). Caddy valida los certificados por HTTP, y el proxy de Cloudflare lo interfiere.

## 2. Preparar el servidor

```bash
# Docker y el plugin compose
curl -fsSL https://get.docker.com | sh

# Firewall: SSH, HTTP y HTTPS
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 443/udp
ufw enable
```

## 3. Primer despliegue

```bash
git clone <url-del-repo> /opt/obrador
cd /opt/obrador
git checkout feature/multitenant

cp .env.produccion.example .env.produccion
nano .env.produccion
```

Completar en `.env.produccion`:

- `APP_URL=https://obrador.com` y `CENTRAL_DOMAINS=obrador.com`
- `ACME_EMAIL`: correo para los avisos de Let's Encrypt
- `DB_PASSWORD` y `MARIADB_PASSWORD`: la **misma** contraseña
- `MARIADB_ROOT_PASSWORD`: otra contraseña, distinta

Generar `APP_KEY` y pegarla en `.env.produccion`:

```bash
docker compose build app
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show
```

Levantar todo y crear el primer superadministrador:

```bash
docker compose up -d --build
docker compose logs -f app        # esperar "ready to handle connections", salir con Ctrl+C
docker compose exec app php artisan obrador:superadmin-crear tu@correo.com
```

Entrar a `https://obrador.com/panel`. El primer acceso a cada dominio tarda unos segundos, mientras Caddy obtiene el certificado.

## 4. Dar de alta panaderías

Desde **https://obrador.com/panel → Nueva panadería** hay dos opciones:

- **Con un administrador nuevo:** nombre, id (será `id.obrador.com`), y correo y contraseña del administrador.
- **Con los datos de un respaldo:** el `.zip` que genera la aplicación de escritorio (Sistema → Respaldos). Trae ventas, inventario, planillas y usuarios. Si el cliente no recuerda su contraseña, en el detalle de la panadería se usa **Crear o restablecer administrador**.

Un dominio propio (`panaderialuna.com`) se agrega al crear la panadería. Su dueño tiene que apuntarlo con un registro A a la IP del VPS.

## 5. Actualizar a una versión nueva

```bash
cd /opt/obrador
git pull
docker compose up -d --build
```

Al arrancar, `app` aplica las migraciones nuevas a la base central y a todas las panaderías.

## 6. Respaldos del servidor

Cada panadería puede descargar su respaldo desde la aplicación. Además conviene respaldar el servidor entero todos los días:

```bash
mkdir -p /root/respaldos
crontab -e
```

```cron
# 03:00 — todas las bases (central y panaderías), comprimidas; se guardan 14 días
0 3 * * * cd /opt/obrador && docker compose exec -T db sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --all-databases --single-transaction' | gzip > /root/respaldos/bases-$(date +\%F).sql.gz && find /root/respaldos -name 'bases-*' -mtime +14 -delete
# 03:30 — imágenes y respaldos guardados por la aplicación
30 3 * * * docker run --rm -v obrador_storage:/datos:ro -v /root/respaldos:/destino alpine tar czf /destino/storage-$(date +\%F).tar.gz -C /datos . && find /root/respaldos -name 'storage-*' -mtime +14 -delete
```

Copiar `/root/respaldos` fuera del VPS (otro servidor, almacenamiento en la nube): si el VPS se pierde, esos archivos se pierden con él.

## 7. Comandos útiles

```bash
docker compose ps                                   # estado de los contenedores
docker compose logs -f app                          # logs de la aplicación
docker compose exec app php artisan tenants:list    # panaderías registradas
docker compose exec app php artisan obrador:superadmin-crear tu@correo.com   # cambiar contraseña del panel
docker compose exec app php artisan obrador:tenant-importar luna /ruta/respaldo.zip  # importar por consola
```

Para importar un `.zip` por consola, primero hay que copiarlo dentro del contenedor:
`docker compose cp respaldo.zip app:/tmp/respaldo.zip`.
