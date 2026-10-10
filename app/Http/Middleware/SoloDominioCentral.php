<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El panel de superadministración solo existe en el dominio central: desde el
 * subdominio de una panadería, /panel no es una página.
 */
class SoloDominioCentral
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(\in_array($request->getHost(), config('tenancy.central_domains'), true), 404);

        return $next($request);
    }
}
