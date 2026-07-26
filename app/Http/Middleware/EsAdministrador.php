<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EsAdministrador
{
    /**
     * Restringe el acceso a usuarios con rol Administrador.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->esAdministrador()) {
            abort(403, 'Solo el administrador puede acceder a esta sección.');
        }

        return $next($request);
    }
}
