<?php

namespace App\Providers;

use App\Support\Actualizaciones\Descarga;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Native\Desktop\Events\AutoUpdater\DownloadProgress;
use Native\Desktop\Events\AutoUpdater\Error as ErrorDeActualizacion;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->prepareDesktopRuntime();

        $this->escucharDescargasDeActualizacion();

        // El nombre del negocio se aplica al identificar la panadería, no
        // acá: en el arranque todavía no se sabe de qué base leerlo.

        Paginator::useBootstrapFive();

        // Rate limiting: máx 5 intentos de login por minuto por IP+email
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->string('email')->lower().'|'.$request->ip()
            );
        });
    }

    /**
     * La app de escritorio avisa el avance de la descarga por eventos de
     * Electron. Se guarda en caché para que el modal lo consulte por polling;
     * cruzar el puente de eventos hasta el JavaScript de la vista es mucho más
     * frágil que una lectura cada 800 ms.
     */
    private function escucharDescargasDeActualizacion(): void
    {
        Event::listen(DownloadProgress::class, fn (DownloadProgress $evento) => Descarga::progreso($evento));
        Event::listen(UpdateDownloaded::class, fn (UpdateDownloaded $evento) => Descarga::lista($evento));
        Event::listen(ErrorDeActualizacion::class, fn (ErrorDeActualizacion $evento) => Descarga::error($evento));
    }

    /**
     * Los directorios no desaparecen mientras el proceso vive, y el proceso
     * PHP de la app de escritorio atiende todas las peticiones de la sesión:
     * basta con crearlos en la primera petición.
     */
    private static bool $directoriosPreparados = false;

    private function prepareDesktopRuntime(): void
    {
        if (self::$directoriosPreparados || ! config('nativephp-internal.running')) {
            return;
        }

        self::$directoriosPreparados = true;

        $directories = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];

        foreach ($directories as $directory) {
            try {
                File::ensureDirectoryExists($directory);
            } catch (\Throwable $exception) {
                Log::error('No se pudo preparar una carpeta de trabajo local.', [
                    'directory' => $directory,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
