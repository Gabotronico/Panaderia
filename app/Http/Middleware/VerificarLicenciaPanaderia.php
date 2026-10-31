<?php

namespace App\Http\Middleware;

use App\Support\Licencias\Licencia;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica la licencia de la panadería a cada petición de su grupo de rutas.
 *
 * - sin licencia o pendiente: pasa (una panadería recién creada tiene que
 *   poder configurarse mientras el panel central le asigna el plan);
 * - vencida: solo lectura, para que puedan consultar y exportar sus datos;
 * - suspendida o revocada: no entra nadie.
 *
 * Entrar y salir de la sesión nunca se bloquea: si no, nadie podría ni ver el
 * aviso.
 */
class VerificarLicenciaPanaderia
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenancy()->initialized) {
            return $next($request);
        }

        $tenantId = (string) tenant('id');
        $estado = Licencia::estado($tenantId);

        if (in_array($estado, ['sin_licencia', 'pending', 'active'], true)) {
            return $next($request);
        }

        if ($request->routeIs('login', 'logout', 'setup.*')) {
            return $next($request);
        }

        if ($estado === 'expired') {
            if ($request->isMethodSafe()) {
                return $next($request);
            }

            return back()->with(
                'error',
                'La licencia está vencida: por ahora la panadería es solo de consulta. '
                .'Pedí la renovación para volver a registrar ventas y cambios.',
            );
        }

        // suspended | revoked
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'El acceso de esta panadería está suspendido.',
            ], 403);
        }

        return response()->view('licencia.bloqueada', [
            'negocio' => (string) (tenant('negocio') ?: $tenantId),
            'estado' => $estado,
        ], 403);
    }
}
