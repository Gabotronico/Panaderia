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
            ->hideMenu()
            ->preventLeaveDomain();
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
        ];
    }
}
