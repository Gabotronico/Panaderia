<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
            if (Schema::hasTable('app_settings')) {
                config([
                    'app.name' => AppSetting::read('business_name', config('app.name')),
                ]);
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

    private function prepareDesktopRuntime(): void
    {
        if (! config('nativephp-internal.running')) {
            return;
        }

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
