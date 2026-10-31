<?php

namespace App\Http\Controllers;

use App\Models\TokenEntrada;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Abre la sesión en la panadería con el pase que emitió el login central.
 *
 * El token es de un solo uso, dura dos minutos y está atado a esta panadería:
 * el valor viaja en la URL y en la base solo queda su hash.
 */
class EntradaTokenController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $registro = TokenEntrada::query()
            ->where('token', hash('sha256', $token))
            ->where('tenant_id', (string) tenant('id'))
            ->first();

        if ($registro === null || ! $registro->vigente()) {
            abort(404);
        }

        // Consumo atómico: si llega dos veces, solo una gana.
        $marcado = TokenEntrada::query()
            ->whereKey($registro->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        if ($marcado !== 1) {
            abort(404);
        }

        $usuario = User::query()->find($registro->user_id);

        if ($usuario === null) {
            abort(404);
        }

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
