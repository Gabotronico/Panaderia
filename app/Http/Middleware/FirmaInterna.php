<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica la firma HMAC de las llamadas del panel central de INC Group.
 *
 * Firma: HMAC-SHA256(secreto, "<timestamp>.<METODO>.<ruta>.<sha256 del cuerpo>").
 * Llega en X-Inc-Timestamp y X-Inc-Signature. Sin el secreto configurado el
 * endpoint no existe: devuelve 503 en vez de aceptar cualquier cosa.
 */
class FirmaInterna
{
    public function handle(Request $request, Closure $next): Response
    {
        $secreto = (string) config('licencias.secreto');

        if ($secreto === '') {
            abort(503, 'Falta configurar LICENCIA_SECRETO.');
        }

        $timestamp = $request->header('X-Inc-Timestamp');
        $firma = $request->header('X-Inc-Signature');

        if (! is_string($timestamp) || ! is_string($firma) || ! ctype_digit($timestamp)) {
            abort(401, 'Faltan los encabezados de firma.');
        }

        $tolerancia = (int) config('licencias.tolerancia', 300);

        if (abs(time() - (int) $timestamp) > $tolerancia) {
            abort(401, 'La firma está fuera de la ventana de tiempo.');
        }

        $cuerpo = $request->getContent();
        $esperada = hash_hmac(
            'sha256',
            $timestamp.'.'.strtoupper($request->method()).'.'.$request->getPathInfo().'.'.hash('sha256', $cuerpo),
            $secreto,
        );

        if (! hash_equals($esperada, strtolower($firma))) {
            abort(401, 'La firma no coincide.');
        }

        return $next($request);
    }
}
