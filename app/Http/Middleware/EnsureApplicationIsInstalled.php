<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationIsInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // Con sesión iniciada hay usuarios por definición; evitar las dos
        // consultas de abajo le quita latencia a cada clic de la jornada.
        // En el dominio central no hay panadería que configurar.
        if (! tenancy()->initialized || $request->routeIs('setup.*') || Auth::check() || ! Schema::hasTable('users')) {
            return $next($request);
        }

        if (! User::query()->exists()) {
            return redirect()->route('setup.create');
        }

        return $next($request);
    }
}
