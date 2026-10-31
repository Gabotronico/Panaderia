<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open()
            ->title(config('app.name'))
            ->width(1280)
            ->height(800)
            ->minWidth(1024)
            ->minHeight(700)
            ->rememberState()
            ->maximized()
            ->hideMenu();
        // Sin preventLeaveDomain: la ventana navega al sistema en el VPS.

        // La raíz local muestra la pantalla del código de panadería (o salta
        // directo al servidor si ya está configurado).
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'date.timezone' => 'America/La_Paz',
            'memory_limit' => '512M',
            'post_max_size' => '520M',
            'upload_max_filesize' => '512M',
            'max_execution_time' => '300',

            'opcache.enable' => '1',
            'opcache.enable_cli' => '1',
            'opcache.memory_consumption' => '192',
            'opcache.max_accelerated_files' => '20000',
            // En producción el código empaquetado nunca cambia: sin la revalidación
            // por petición se evita un stat() por cada archivo incluido.
            // En desarrollo (APP_ENV=local vía `native:run`) se mantiene en 1 para ver los cambios.
            'opcache.validate_timestamps' => env('APP_ENV') === 'production' ? '0' : '1',
            'opcache.revalidate_freq' => '2',
        ];
    }
}
