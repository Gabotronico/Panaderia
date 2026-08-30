<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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

        try {
            if (($nombreNegocio = AppSetting::nombreEnCache()) !== null) {
                config(['app.name' => $nombreNegocio]);
            }
        } catch (\Throwable) {
            // La base puede no existir todavía durante la instalación inicial.
        }

        Paginator::useBootstrapFive();

        // Rate limiting: máx 5 intentos de login por minuto por IP+email
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->string('email')->lower().'|'.$request->ip()
            );
        });
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
