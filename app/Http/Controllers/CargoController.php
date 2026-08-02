<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Rules\NombreUnico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CargoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $cargos = Cargo::withCount('empleados')->orderBy('nombre')->get();
        return view('rrhh.cargos.index', compact('cargos'));
    }

    public function create()
    {
        return view('rrhh.cargos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => ['required', 'string', 'max:100', new NombreUnico(
                'cargos',
                null,
                'Ya existe el cargo ":existente".'
            )],
            'descripcion' => 'nullable|string|max:255',
        ]);

        Cargo::create([
            'nombre'      => NombreUnico::limpiar($request->nombre),
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('cargos.index')->with('success', 'Cargo creado exitosamente.');
    }

    public function edit(Cargo $cargo)
    {
        return view('rrhh.cargos.edit', compact('cargo'));
    }

    public function update(Request $request, Cargo $cargo)
    {
        $request->validate([
            'nombre'      => ['required', 'string', 'max:100', new NombreUnico(
                'cargos',
                $cargo->id,
                'Ya existe otro cargo llamado ":existente".'
            )],
            'descripcion' => 'nullable|string|max:255',
        ]);

        $cargo->update([
            'nombre'      => NombreUnico::limpiar($request->nombre),
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('cargos.index')->with('success', 'Cargo actualizado.');
    }

    public function destroy(Cargo $cargo)
    {
        if ($cargo->empleados()->exists()) {
            return redirect()->route('cargos.index')
                ->with('error', 'No se puede eliminar: hay empleados con este cargo.');
        }

        $cargo->delete();
        return redirect()->route('cargos.index')->with('success', 'Cargo eliminado.');
    }
}
