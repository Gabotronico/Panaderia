<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Rules\NombreUnico;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-categorias')->only(['index', 'show']);
        $this->middleware('permission:crear-categorias')->only(['create', 'store']);
        $this->middleware('permission:editar-categorias')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-categorias')->only(['destroy']);
    }

    public function index()
    {
        $categorias = Categoria::orderBy('nombre')->paginate(10);
        return view('categorias.index', compact('categorias'));
    }

    public function create()
    {
        return view('categorias.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:100', new NombreUnico(
                'categorias',
                null,
                'Ya existe la categoría ":existente".'
            )],
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $datos = $request->all();
        $datos['nombre'] = NombreUnico::limpiar($request->nombre);

        Categoria::create($datos);

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    public function show(Categoria $categoria)
    {
        return view('categorias.show', compact('categoria'));
    }

    public function edit(Categoria $categoria)
    {
        return view('categorias.edit', compact('categoria'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:100', new NombreUnico(
                'categorias',
                $categoria->id,
                'Ya existe otra categoría llamada ":existente".'
            )],
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $datos = $request->all();
        $datos['nombre'] = NombreUnico::limpiar($request->nombre);

        $categoria->update($datos);

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(Categoria $categoria)
    {
        try {
            $categoria->delete();
            return redirect()->route('categorias.index')
                ->with('success', 'Categoría eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('categorias.index')
                ->with('error', 'No se puede eliminar la categoría porque tiene productos asociados.');
        }
    }
}