<?php

namespace App\Http\Middleware;

use App\Models\CorteCaja;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el registro de ventas mientras el usuario no tenga una caja abierta.
 *
 * Una venta sin corte queda fuera de todo cierre: no se puede arquear contra
 * lo que hay en el cajón ni aparece en el resumen del turno. Antes se podía
 * vender sin haber abierto caja y esa plata quedaba huérfana.
 *
 * El corte es por usuario, así que cada cajero necesita el suyo; el
 * administrador también, porque su venta directa entra al mismo circuito.
 */
class RequiereCajaAbierta
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && CorteCaja::abiertaDe($user->id)) {
            return $next($request);
        }

        return redirect()->route('cortes.create')->with('error',
            'Para registrar una venta primero tenés que abrir la caja. '
            . 'Indicá con cuánto efectivo empezás el turno y volvé a intentarlo.'
        );
    }
}
