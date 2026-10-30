# Entorno local de desarrollo con Docker

Sirve para correr Obrador completo en la máquina, sin instalar PHP, MySQL ni
Node: dos contenedores con lo mismo que producción más un Vite con recarga en
vivo, y una panadería de prueba ya cargada.

No toca la configuración de producción: usa `compose.local.yaml` y
`.env.local`, aparte de `compose.yaml` y `.env.produccion`.

## Arrancar

```bash
# Linux, macOS o Git Bash en Windows
sh docker/local/preparar.sh

# Windows (PowerShell)
docker/local/preparar.ps1
```

El script crea `.env.local` si falta, construye la imagen, levanta todo, espera
las migraciones y deja:

| Qué | Dónde | Cuenta |
|---|---|---|
| Panel del sistema (superadministrador) | <http://localhost:8094/panel> | `panel@obrador.local` / `Panel12345678` |
| Panadería de prueba `luna` (administrador) | <http://localhost:8094/luna> | `admin@luna.local` / `Luna123456` |
| La misma panadería (cajero) | <http://localhost:8094/luna> | `cajero@luna.local` / `Cajero12345` |

La panadería `luna` viene con un almacén *Mostrador*, seis productos con stock y
cuatro insumos, para que las pantallas no salgan vacías. Se puede volver a
correr el script las veces que haga falta: no duplica nada.

El comando de consola equivalente, si preferís hacerlo a mano:

```bash
docker compose -f compose.local.yaml up -d --build
docker compose -f compose.local.yaml exec app php artisan obrador:superadmin-crear \
    panel@obrador.local --nombre="Panel de Prueba" --password="Panel12345678"
docker compose -f compose.local.yaml exec app php artisan obrador:tenant-crear luna \
    --negocio="Panadería Luna" --admin-nombre="Administrador Luna" \
    --admin-email="admin@luna.local" --admin-password="Luna123456"
docker compose -f compose.local.yaml exec app php docker/local/sembrar-demo.php luna
```

## Qué hay levantado

| Servicio | Puerto | Para qué |
|---|---|---|
| `app` | <http://localhost:8094> | Laravel con PHP 8.3 + nginx. Al arrancar migra la base central y las de todas las panaderías. |
| `vite` | <http://localhost:5173> | Compila Tailwind y recarga el navegador al guardar CSS o JS. |
| `db` | `127.0.0.1:3308` | MariaDB 11.4: la base central `obrador_central` y una por panadería (`obrador_luna`, …). Usuario `obrador`, contraseña en `.env.local`. |

Cada panadería vive bajo su id en el mismo host (`localhost:8094/luna`), así
que no hace falta tocar el archivo `hosts` ni usar subdominios.

## Cómo trabaja la recarga en vivo

El código va **montado**, no copiado dentro de la imagen:

- **PHP y Blade** (`app/`, `routes/`, `config/`, `resources/views/`): al guardar
  y recargar el navegador ya está el cambio. `OBRADOR_CACHEAR=0` en `.env.local`
  evita que el arranque cachee configuración y vistas.
- **CSS y JS** (`resources/css`, `resources/js`): los sirve el contenedor de
  Vite con recarga automática, sin reconstruir nada. Lo que hace falta para
  que funcione dentro de Docker (escuchar en `0.0.0.0`, escribir
  `http://localhost:5173` en `public/hot` y permitir el origen `:8094`) está
  en `docker/local/vite.config.js`; la configuración de la raíz queda intacta
  para el build de producción.

Lo que **sí** pide reconstruir la imagen:

```bash
docker compose -f compose.local.yaml build app && docker compose -f compose.local.yaml up -d
```

- cambios en `composer.json` / `composer.lock` (el `vendor/` viene de la imagen,
  para no mezclar dependencias del Windows de cada uno con las del contenedor);
- cambios en el `Dockerfile` o en `docker/entrypoint.d/`.

Si instalás un paquete npm nuevo, borrá el volumen de dependencias para que el
contenedor de Vite lo vuelva a instalar:

```bash
docker compose -f compose.local.yaml down
docker volume rm obrador-local_vite_modules
docker compose -f compose.local.yaml up -d
```

## Comandos del día a día

```bash
docker compose -f compose.local.yaml ps                     # estado
docker compose -f compose.local.yaml logs -f app            # logs de Laravel
docker compose -f compose.local.yaml logs -f vite           # logs de Vite
docker compose -f compose.local.yaml exec app php artisan tenants:list
docker compose -f compose.local.yaml exec app php artisan tinker
docker compose -f compose.local.yaml exec db mariadb -uobrador -pobrador_local obrador_luna
```

Apagar y borrar:

```bash
docker compose -f compose.local.yaml down       # apaga, conserva bases y archivos
docker compose -f compose.local.yaml down -v    # además borra bases, storage y node_modules
```

`down -v` es también el camino cuando cambian las contraseñas de `.env.local`:
los scripts de `docker/mariadb/` solo corren al crear el volumen.

## Crear otra panadería

Desde el panel (<http://localhost:8094/panel>) o por consola:

```bash
docker compose -f compose.local.yaml exec app php artisan obrador:tenant-crear sol \
    --negocio="Panadería Sol" --admin-nombre="Admin Sol" \
    --admin-email="admin@sol.local" --admin-password="Sol123456"
```

Queda en <http://localhost:8094/sol>. Los ids `panel`, `build`, `storage`, `up`,
`login` y algunos más están reservados.

## Pruebas automáticas

La imagen se construye sin dependencias de desarrollo (`composer install
--no-dev`), así que `php artisan test` **no** corre dentro del contenedor. Las
pruebas se corren en la máquina, como siempre:

```bash
php artisan test
```

## Si algo no anda

**La página se ve sin estilos ni menús.** El servidor de Vite no está
corriendo, o quedó un `public/hot` viejo de una sesión anterior (Laravel cree
que Vite sigue ahí). Mirá `logs vite` y, si hace falta:

```bash
rm -f public/hot
docker compose -f compose.local.yaml restart vite
```

**Un puerto está ocupado.** 8094 (app), 5173 (Vite) y 3308 (base). Apagá lo que
lo esté usando o cambiá el puerto del host en `compose.local.yaml`.

**No entra ninguna contraseña.** Corré otra vez el script de preparación:
vuelve a fijar las contraseñas de las cuentas de prueba.

**Quiero empezar de cero.** `docker compose -f compose.local.yaml down -v` y
el script de preparación otra vez.

## Diferencias con producción

| | Local | Producción |
|---|---|---|
| Archivo de compose | `compose.local.yaml` | `compose.yaml` (+ `compose.caddy.yaml`) |
| Variables | `.env.local` | `.env.produccion` |
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | `production` / `false` |
| `BCRYPT_ROUNDS` | 4 (login instantáneo) | 12 |
| HTTPS y dominio | no, `http://localhost:8094` | Caddy y `obrador.incgroupbo.com` |
| Cookies seguras | no (`SESSION_SECURE_COOKIE=false`) | sí |

Ver [despliegue-docker.md](despliegue-docker.md) para el servidor.
