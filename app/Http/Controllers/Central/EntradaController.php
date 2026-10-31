<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\TokenEntrada;
use App\Support\Identidad\Cuentas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Entrada al sistema: una sola cuenta para una o varias panaderías.
 *
 * La persona se valida acá (correo y contraseña en la base central) y recibe un
 * pase de un solo uso para abrir la sesión en la panadería elegida. Si tiene
 * una sola, no se le pregunta nada.
 */
class EntradaController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('entrada.persona')) {
            return redirect()->route('entrada.elegir');
        }

        return view('central.entrar');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $persona = Persona::deCorreo($datos['email']);

        if ($persona === null || ! Hash::check($datos['password'], $persona->password)) {
            // El mismo mensaje exista o no la cuenta: no se enumeran correos.
            throw ValidationException::withMessages([
                'email' => 'Correo o contraseña incorrectos.',
            ]);
        }

        $panaderias = Cuentas::panaderias($persona);

        if ($panaderias === []) {
            throw ValidationException::withMessages([
                'email' => 'Esa cuenta no tiene acceso a ninguna panadería. Pedile a INC Group que la habilite.',
            ]);
        }

        $request->session()->put('entrada.persona', $persona->id);

        if (count($panaderias) === 1) {
            return $this->pasar($request, $persona, $panaderias[0]);
        }

        return redirect()->route('entrada.elegir');
    }

    public function elegir(Request $request): View|RedirectResponse
    {
        $persona = $this->personaDeSesion($request);

        if ($persona === null) {
            return redirect()->route('entrada.crear');
        }

        return view('central.elegir', [
            'persona' => $persona,
            'panaderias' => Cuentas::panaderias($persona),
        ]);
    }

    public function seleccionar(Request $request): RedirectResponse
    {
        $persona = $this->personaDeSesion($request);

        if ($persona === null) {
            return redirect()->route('entrada.crear');
        }

        $datos = $request->validate([
            'tenant' => ['required', 'string', 'max:40'],
        ]);

        $elegida = collect(Cuentas::panaderias($persona))->firstWhere('tenant_id', $datos['tenant']);

        if ($elegida === null) {
            throw ValidationException::withMessages([
                'tenant' => 'Esa panadería no está en tu cuenta.',
            ]);
        }

        if (! $elegida['entra']) {
            throw ValidationException::withMessages([
                'tenant' => 'El acceso de esa panadería está suspendido. Comunicate con INC Group.',
            ]);
        }

        return $this->pasar($request, $persona, $elegida);
    }

    public function salir(Request $request): RedirectResponse
    {
        $request->session()->forget('entrada.persona');

        return redirect()->route('entrada.crear');
    }

    private function personaDeSesion(Request $request): ?Persona
    {
        $id = $request->session()->get('entrada.persona');

        return $id === null ? null : Persona::query()->find($id);
    }

    /**
     * @param  array{tenant_id: string, user_id: int}  $panaderia
     */
    private function pasar(Request $request, Persona $persona, array $panaderia): RedirectResponse
    {
        $crudo = Str::random(64);

        TokenEntrada::query()->create([
            'token' => hash('sha256', $crudo),
            'persona_id' => $persona->id,
            'tenant_id' => $panaderia['tenant_id'],
            'user_id' => $panaderia['user_id'],
            'expires_at' => now()->addSeconds(120),
        ]);

        $request->session()->forget('entrada.persona');

        // Recuerda la última panadería para que la próxima visita a la raíz
        // entre directo (si la sesión sigue viva) en lugar de pedir el login.
        return redirect()
            ->away(route('entrada.token', [
                'tenant' => $panaderia['tenant_id'],
                'token' => $crudo,
            ]))
            ->withCookie(cookie(
                'obrador_ultima_panaderia',
                (string) $panaderia['tenant_id'],
                60 * 24 * 365,
            ));
    }
}
