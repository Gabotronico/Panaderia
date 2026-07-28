<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            \App\Http\Middleware\DesktopReliability::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureApplicationIsInstalled::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'admin' => \App\Http\Middleware\EsAdministrador::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            $request = request();

            Log::warning('La sesión local expiró o perdió su token de seguridad.', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
            ]);

            try {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } catch (\Throwable $exception) {
                Log::error('No se pudo regenerar la sesión local.', [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }

            try {
                $target = \App\Models\User::query()->exists()
                    ? 'login'
                    : 'setup.create';
            } catch (\Throwable) {
                $target = 'setup.create';
            }

            return redirect()->route($target)->with(
                'error',
                'La sesión local se reinició. Por favor, inténtelo nuevamente.'
            );
        });
    })->create();
