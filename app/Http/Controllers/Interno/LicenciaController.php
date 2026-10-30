<?php

namespace App\Http\Controllers\Interno;

use App\Http\Controllers\Controller;
use App\Support\Licencias\Licencia;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Endpoint interno que usa el panel central de INC Group. No lo llama ningún
 * navegador: la firma HMAC del middleware es la que autoriza.
 */
class LicenciaController extends Controller
{
    /**
     * Panaderías con su consumo real (administradores, cajeros, productos y
     * ventas). El panel lo usa para mostrar los cupos usados contra los
     * contratados y para detectar panaderías sin licencia.
     */
    public function mostrar(): JsonResponse
    {
        return response()->json(['tenants' => Licencia::resumen()]);
    }

    /**
     * Aplica el estado canónico de una licencia. Es idempotente: si llega una
     * versión igual o menor que la guardada, responde 200 sin tocar nada.
     */
    public function sincronizar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'external_tenant_ref' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'status' => ['required', Rule::in(['pending', 'active', 'suspended', 'revoked', 'expired'])],
            'plan' => ['required', 'string', 'max:40'],
            'max_admins' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'max_cajeros' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'valid_until' => ['nullable', 'date'],
            'version' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $resultado = Licencia::sincronizar($datos);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'Esa panadería no existe en este servidor.'], 404);
        }

        return response()->json([
            'aplicada' => $resultado['aplicada'],
            'tenant' => $resultado['registro']->tenant_id,
            'version' => $resultado['registro']->version,
            'status' => Licencia::estado($resultado['registro']->tenant_id),
        ]);
    }
}
