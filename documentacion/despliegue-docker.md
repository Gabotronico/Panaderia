# Despliegue de Obrador con Docker

Obrador corre en dos contenedores, definidos en `compose.yaml`:

| Servicio | Qué hace |
|---|---|
| `app` | Laravel con PHP 8.3 + nginx, en `127.0.0.1:8094`. Al arrancar migra la base central y la de cada panadería. |
| `db` | MariaDB 11.4. Una base central (`obrador_central`) y una por panadería (`obrador_<id>`). |

El HTTPS lo pone un Caddy delante:

- **Servidor compartido** (el VPS de INC Group): el Caddy del sistema, que ya publica los otros proyectos. Es el caso de producción, descrito abajo.
- **VPS dedicado a Obrador**: un Caddy propio en contenedor, sumando `compose.caddy.yaml` (ver al final).

Datos persistentes (volúmenes de Docker): `obrador_db` (bases) y `obrador_storage` (imágenes, respaldos).

## 1. DNS

Un solo registro **A** en la zona de `incgroupbo.com`, hacia la IP del VPS (66.94.116.41):

| Nombre | Para qué |
|---|---|
| `obrador` | `obrador.incgroupbo.com`: portada, panel `/panel` y todas las panaderías (`/luna`, `/sol`, …) |

Cada panadería vive bajo su id en ese mismo dominio, así que dar de alta una nueva no requiere tocar el DNS.

## 2. Primer despliegue

```bash
git clone -b feature/multitenant https://github.com/Gabotronico/Panaderia.git /opt/obrador
cd /opt/obrador

cp .env.produccion.example .env.produccion
nano .env.produccion
```

Completar en `.env.produccion`:

- `APP_URL=https://obrador.incgroupbo.com` y `CENTRAL_DOMAINS=obrador.incgroupbo.com`
- `DB_PASSWORD` y `MARIADB_PASSWORD`: la **misma** contraseña
- `MARIADB_ROOT_PASSWORD`: otra contraseña, distinta
- `APP_KEY`: generarla con
  `docker compose build app && docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show`

Levantar y crear el primer superadministrador:

```bash
docker compose up -d --build
docker compose logs -f app        # esperar "ready to handle connections", salir con Ctrl+C
docker compose exec app php artisan obrador:superadmin-crear tu@correo.com
```

## 3. Publicar con el Caddy del sistema

Agregar al final de `/etc/caddy/Caddyfile` el bloque de `docker/caddy/obrador-compartido.caddy` (el sitio `obrador.incgroupbo.com`).

```bash
cp /etc/caddy/Caddyfile /etc/caddy/Caddyfile.bak-$(date +%Y%m%d-%H%M)
nano /etc/caddy/Caddyfile
caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
systemctl reload caddy
```

`reload` aplica los cambios sin cortar los otros sitios. Entrar a `https://obrador.incgroupbo.com/panel`; el primer acceso tarda unos segundos, mientras Caddy obtiene el certificado.

## 4. Dar de alta panaderías

Desde **https://obrador.incgroupbo.com/panel → Nueva panadería** hay dos opciones:

- **Con un administrador nuevo:** nombre, id (su dirección será `obrador.incgroupbo.com/id`), y correo y contraseña del administrador.
- **Con los datos de un respaldo:** el `.zip` que genera la aplicación de escritorio (Sistema → Respaldos). Trae ventas, inventario, planillas y usuarios. Si el cliente no recuerda su contraseña, en el detalle de la panadería se usa **Crear o restablecer administrador**.

Los ids `panel`, `build`, `storage`, `up` y algunos más están reservados: son rutas del sistema.

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

## VPS dedicado a Obrador

Sin otro proxy en los puertos 80/443, Obrador puede traer su propio Caddy
(`docker/caddy/Caddyfile`), que publica el dominio de `CENTRAL_DOMAINS`:

```bash
# en .env.produccion, además: ACME_EMAIL=correo para Let's Encrypt
docker compose -f compose.yaml -f compose.caddy.yaml up -d --build
```

Para cualquier otro comando de compose en este modo hay que repetir los dos `-f`.
