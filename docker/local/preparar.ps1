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
#   docker/local/preparar.ps1            (PowerShell)
#   sh docker/local/preparar.sh          (Linux, macOS o Git Bash)

$ErrorActionPreference = 'Stop'

# Los comandos que fallan a propósito (la panadería ya existe) se evalúan a
# mano con $LASTEXITCODE; que PowerShell no corte el script por ellos.
if (Get-Variable PSNativeCommandUseErrorActionPreference -ErrorAction SilentlyContinue) {
    $PSNativeCommandUseErrorActionPreference = $false
}

function dc {
    & docker compose -f compose.local.yaml @args
}

Set-Location (Join-Path $PSScriptRoot '..\..')

$panaderia = 'luna'

if (-not (Test-Path '.env.local')) {
    Copy-Item '.env.local.example' '.env.local'
    Write-Host 'Creado .env.local a partir de .env.local.example.'
}

Write-Host '-> Construyendo y levantando los contenedores (la primera vez tarda varios minutos)...'
dc up -d --build
if ($LASTEXITCODE -ne 0) { throw 'No se pudieron levantar los contenedores.' }

Write-Host '-> Esperando a que la aplicación termine de migrar...'
$listo = $false
for ($intento = 1; $intento -le 90; $intento++) {
    try {
        $respuesta = Invoke-WebRequest -Uri 'http://localhost:8094/up' -UseBasicParsing -TimeoutSec 5
        if ($respuesta.StatusCode -eq 200) { $listo = $true; break }
    } catch {
        Start-Sleep -Seconds 2
    }
}

if (-not $listo) {
    throw 'La aplicación no respondió. Mirá los logs con: docker compose -f compose.local.yaml logs app'
}

# Si el correo ya existe, el comando solo le cambia la contraseña.
dc exec -T app php artisan obrador:superadmin-crear panel@obrador.local --nombre="Panel de Prueba" --password="Panel12345678"
if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear el superadministrador.' }

# El alta es la que crea la base de la panadería y su primer administrador.
# Si ya existe, avisa; no hay nada más que hacer.
# El nombre sin tilde es a propósito: PowerShell 5.1 manda los acentos mal y
# quedarían guardados como "PanaderÃ­a". El nombre visible con tilde lo deja
# después sembrar-demo.php, que está en UTF-8.
$alta = dc exec -T app php artisan obrador:tenant-crear $panaderia --negocio="Panaderia Luna" --admin-nombre="Administrador Luna" --admin-email="admin@luna.local" --admin-password="Luna123456" 2>&1 | Out-String

# Se compara sin acentos a propósito: PowerShell 5.1 lee la salida del
# contenedor (UTF-8) con la página de códigos de la consola y las tildes
# llegan rotas. "Ya hay una panader" y "creada en la base" son inequívocos.
if ($alta -match 'Ya hay una panader') {
    Write-Host "La panadería `"$panaderia`" ya existía."
} elseif ($alta -match 'creada en la base') {
    Write-Host $alta.Trim()
} else {
    throw "No se pudo crear la panadería:`n$alta"
}

Write-Host '-> Cargando los datos de prueba...'
dc exec -T app php docker/local/sembrar-demo.php $panaderia
if ($LASTEXITCODE -ne 0) { throw 'No se pudieron cargar los datos de prueba.' }

Write-Host @"

Listo.

  Panel del sistema   http://localhost:8094/panel
                      panel@obrador.local / Panel12345678

  Panadería           http://localhost:8094/$panaderia
                      admin@luna.local / Luna123456          (administrador)
                      cajero@luna.local / Cajero12345        (cajero)

  Base de datos       127.0.0.1:3308  (usuario obrador, ver .env.local)
  Vite (recarga)      http://localhost:5173

  Logs                docker compose -f compose.local.yaml logs -f app
  Apagar              docker compose -f compose.local.yaml down
  Borrar todo         docker compose -f compose.local.yaml down -v

"@

# Los comandos nativos de arriba dejan $LASTEXITCODE con lo último que corra;
# el script termina bien solo si todo lo anterior pasó sus verificaciones.
exit 0
