<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

\App\Support\LegacyDesktopDataMigrator::migrate();

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
            'caja.abierta' => \App\Http\Middleware\RequiereCajaAbierta::class,
            // Licencia de la panadería (cupos, vigencia, suspensión).
            'licencia.vigente' => \App\Http\Middleware\VerificarLicenciaPanaderia::class,
        ]);

        // El panel central de INC Group llama al endpoint interno con firma
        // HMAC desde un servidor, sin sesión ni token CSRF.
        $middleware->validateCsrfTokens(except: ['interno/*']);

        // En el servidor la app corre detrás de un proxy (nginx del
        // contenedor, Cloudflare): sin esto no ve que la petición vino por
        // HTTPS y arma los enlaces y redirecciones con http://.
        $middleware->trustProxies(at: '*');

        // Cada lado tiene su propio login: el panel del dominio central y el
        // de cada panadería.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->routeIs('central.*')
            ? route('central.login')
            : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->routeIs('central.*')
            ? route('central.panaderias.index')
            : route('dashboard'));
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

            if (! tenancy()->initialized) {
                $target = 'central.login';
            } else {
                try {
                    $target = \App\Models\User::query()->exists()
                        ? 'login'
                        : 'setup.create';
                } catch (\Throwable) {
                    $target = 'setup.create';
                }
            }

            return redirect()->route($target)->with(
                'error',
                'La sesión se reinició. Por favor, inténtelo nuevamente.'
            );
        });
    })->create();
