<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\AltaPanaderia;
use App\Support\Respaldos\Respaldo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Panel del superadministrador: alta de panaderías, sus administradores y
 * la carga de respaldos.
 */
class PanaderiaController extends Controller
{
    public function index(): View
    {
        return view('central.panaderias.index', [
            'panaderias' => Tenant::query()->with('domains')->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function create(): View
    {
        return view('central.panaderias.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'id' => strtolower(trim((string) $request->input('id'))),
            'dominios' => $this->dominios($request->input('dominios_texto')),
        ]);

        $admin = AltaPanaderia::reglasAdministrador();

        $validado = $request->validate([
            'id' => AltaPanaderia::reglasId(),
            'negocio' => ['required', 'string', 'min:2', 'max:100'],
            'dominios' => ['array'],
            'dominios.*' => AltaPanaderia::reglasDominio(),
            'modo' => ['required', 'in:administrador,respaldo'],
            'nombre' => ['exclude_unless:modo,administrador', ...$admin['nombre']],
            'email' => ['exclude_unless:modo,administrador', ...$admin['email']],
            'password' => ['exclude_unless:modo,administrador', 'confirmed', ...$admin['password']],
            'respaldo' => ['exclude_unless:modo,respaldo', 'required', 'file', 'max:524288'],
        ], AltaPanaderia::mensajes());

        $esRespaldo = $validado['modo'] === 'respaldo';

        if ($esRespaldo && strtolower($request->file('respaldo')->getClientOriginalExtension()) !== 'zip') {
            return back()->withInput()->withErrors(['respaldo' => 'El respaldo tiene que ser un archivo .zip.']);
        }

        try {
            $tenant = AltaPanaderia::crear(
                $validado['id'],
                $validado['negocio'],
                $validado['dominios'],
                $esRespaldo ? null : [
                    'nombre' => $validado['nombre'],
                    'email' => $validado['email'],
                    'password' => $validado['password'],
                ],
                $esRespaldo ? $request->file('respaldo')->getRealPath() : null,
            );
        } catch (\Throwable $exception) {
            Log::error('No se pudo dar de alta la panadería.', ['id' => $validado['id'], 'exception' => $exception]);

            return back()->withInput()->with('error', 'No se pudo crear la panadería: '.$exception->getMessage());
        }

        return redirect()->route('central.panaderias.show', $tenant)
            ->with('success', "Panadería «{$validado['negocio']}» creada.");
    }

    public function show(Tenant $panaderia): View
    {
        return view('central.panaderias.show', [
            'panaderia' => $panaderia->load('domains'),
            'administradores' => AltaPanaderia::administradores($panaderia),
        ]);
    }

    /**
     * Crea un administrador o restablece la contraseña de uno existente
     * (mismo correo). Es la forma de devolverle el acceso a un cliente.
     */
    public function administrador(Request $request, Tenant $panaderia): RedirectResponse
    {
        $reglas = AltaPanaderia::reglasAdministrador();
        $reglas['password'][] = 'confirmed';
        $validado = $request->validate($reglas);

        AltaPanaderia::guardarAdministrador($panaderia, $validado['nombre'], $validado['email'], $validado['password']);

        return redirect()->route('central.panaderias.show', $panaderia)
            ->with('success', "Administrador {$validado['email']} guardado. Ya puede ingresar con la contraseña indicada.");
    }

    public function importar(Request $request, Tenant $panaderia): RedirectResponse
    {
        $request->validate([
            'respaldo' => ['required', 'file', 'max:524288'],
            'confirmacion' => ['accepted'],
        ], [
            'confirmacion.accepted' => 'Confirmá que se reemplazan todos los datos de la panadería.',
        ]);

        if (strtolower($request->file('respaldo')->getClientOriginalExtension()) !== 'zip') {
            return back()->withErrors(['respaldo' => 'El respaldo tiene que ser un archivo .zip.']);
        }

        try {
            $copiadas = $panaderia->run(fn () => Respaldo::restaurar($request->file('respaldo')->getRealPath()));
        } catch (\Throwable $exception) {
            Log::error('No se pudo importar el respaldo.', ['id' => $panaderia->getTenantKey(), 'exception' => $exception]);

            return back()->with('error', 'No se pudo importar el respaldo: '.$exception->getMessage());
        }

        return redirect()->route('central.panaderias.show', $panaderia)->with('success',
            'Respaldo importado ('.number_format(array_sum($copiadas)).' registros). '
            .'Los usuarios de la panadería ahora son los del respaldo.'
        );
    }

    public function destroy(Request $request, Tenant $panaderia): RedirectResponse
    {
        $id = $panaderia->getTenantKey();

        $request->validate([
            'confirmacion' => ['required', 'in:'.$id],
        ], [
            'confirmacion.in' => 'Escribí el id exacto de la panadería para confirmar.',
        ]);

        AltaPanaderia::eliminar($panaderia);

        return redirect()->route('central.panaderias.index')
            ->with('success', "Se eliminó la panadería «{$id}» con todos sus datos.");
    }

    /** @return list<string> */
    private function dominios(?string $texto): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (string $dominio) => strtolower(trim($dominio)),
            preg_split('/[\s,]+/', (string) $texto) ?: []
        ))));
    }
}
