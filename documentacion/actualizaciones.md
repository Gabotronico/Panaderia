# Descarga y actualización de la app de escritorio

El encabezado del sistema tiene un botón que cambia según dónde se esté usando:

| Dónde | Botón | Qué hace el modal |
|---|---|---|
| Navegador (web) | **Descargar** | Muestra la última versión publicada y el enlace al `.exe`. |
| App de escritorio | **Actualizar a X** | Descarga con progreso, bloquea el cierre y ofrece *Instalar ahora* o *Más tarde*. |

En el escritorio el botón **solo aparece si hay una versión más nueva** que la
instalada (o si ya hay una descarga terminada esperando).

## Dónde viven los instaladores

En el repo [`Rene-Marcelo-Ardaya/panaderia-releases`](https://github.com/Rene-Marcelo-Ardaya/panaderia-releases),
como **Releases** de GitHub. No se commitea ningún binario: la actualización
automática necesita el `latest.yml` que genera electron-builder, y eso solo
existe dentro de un release.

Ver el README de ese repo para el paso a paso de publicación.

## Cómo funciona

1. El servidor consulta la API pública de GitHub por el último release
   (`app/Support/Actualizaciones/Actualizaciones.php`), elige el asset
   `*-Setup-*.exe` y lo guarda en caché 10 minutos.
2. El encabezado (`resources/views/partials/actualizador.blade.php`, lógica en
   `resources/js/actualizador.js`) pide `/sistema/actualizaciones` y decide si
   muestra el botón.
3. En escritorio, "Descargar" llama a `Native\Desktop\Facades\AutoUpdater`; los
   eventos de Electron (`DownloadProgress`, `UpdateDownloaded`, `Error`) se
   guardan en caché (`app/Support/Actualizaciones/Descarga.php`) y el modal los
   lee por polling cada 800 ms.
4. Al terminar, *Instalar ahora* llama a `quitAndInstall()`: la app se cierra,
   se actualiza y vuelve a abrir. *Más tarde* deja la descarga hecha y el botón
   queda como *Instalar actualización*.

Mientras descarga, el modal no se puede cerrar (sin botón de cerrar, sin Esc,
sin clic afuera): el sistema queda bloqueado hasta que termina o falla.

## Variables

| Variable | Para qué |
|---|---|
| `NATIVEPHP_UPDATER_ENABLED=true` | Activa electron-updater en el build. Va en `.env` al compilar. |
| `GITHUB_OWNER` / `GITHUB_REPO` | Repo de releases. Tienen valores por defecto en `config/actualizaciones.php`. |
| `GITHUB_TOKEN` | **Solo para publicar** (`native:publish`). Se limpia del bundle. |
| `ACTUALIZACIONES_CACHE_MINUTOS` | Caché de la consulta (10 por defecto). |

La versión instalada sale de `NATIVEPHP_APP_VERSION`
(`config('nativephp.version')`). Hay que subirla en `.env`,
`config/nativephp.php` y `.env.example` antes de publicar.

## Publicar una versión

```powershell
# en la máquina Windows, desde el proyecto Panaderia
$env:GH_TOKEN = "<token con permiso repo>"
php artisan native:publish win --x64
```

`native:publish` compila, crea el release en `panaderia-releases` y sube el
instalador, el `latest.yml` y el blockmap. En unos minutos la web ya ofrece la
descarga nueva y las apps instaladas muestran *Actualizar*.

## Pruebas

`tests/Feature/ActualizacionTest.php`: última versión, misma versión, sin
release, guardias de escritorio (en el navegador descargar/instalar responden
404), el encabezado y el guardado del progreso.

La descarga real solo se puede probar con un build empaquetado: en el navegador
las rutas existen pero responden 404.
