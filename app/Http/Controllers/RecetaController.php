<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use App\Models\Insumo;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecetaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-recetas')->only(['index', 'show']);
        $this->middleware('permission:crear-recetas')->only(['create', 'store']);
        $this->middleware('permission:editar-recetas')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-recetas')->only(['destroy']);
        $this->middleware('permission:crear-recetas')->only(['formUsar', 'ejecutarUsar']);
    }

    public function index()
    {
        $recetas = Receta::withCount('insumos')->orderBy('nombre')->paginate(15);
        return view('recetas.index', compact('recetas'));
    }

    public function create()
    {
        $insumos = Insumo::where('activo', true)->orderBy('nombre')->get();
        return view('recetas.create', compact('insumos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'             => 'required|string|max:150|unique:recetas,nombre',
            'rendimiento'        => 'required|integer|min:1',
            'descripcion'        => 'nullable|string',
            'activo'             => 'boolean',
            'insumos'            => 'required|array|min:1',
            'insumos.*.id'       => 'required|exists:insumos,id',
            'insumos.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        $receta = Receta::create($request->only('nombre', 'rendimiento', 'descripcion', 'activo'));

        foreach ($request->insumos as $insumo) {
            if (!empty($insumo['id']) && !empty($insumo['cantidad'])) {
                $receta->insumos()->attach($insumo['id'], ['cantidad_necesaria' => $insumo['cantidad']]);
            }
        }

        return redirect()->route('recetas.index')->with('success', 'Receta creada exitosamente.');
    }

    public function show(Receta $receta)
    {
        $receta->load('insumos');
        return view('recetas.show', compact('receta'));
    }

    public function edit(Receta $receta)
    {
        $receta->load('insumos');
        $insumos = Insumo::where('activo', true)->orderBy('nombre')->get();
        return view('recetas.edit', compact('receta', 'insumos'));
    }

    public function update(Request $request, Receta $receta)
    {
        $request->validate([
            'nombre'             => 'required|string|max:150|unique:recetas,nombre,' . $receta->id,
            'rendimiento'        => 'required|integer|min:1',
            'descripcion'        => 'nullable|string',
            'activo'             => 'boolean',
            'insumos'            => 'required|array|min:1',
            'insumos.*.id'       => 'required|exists:insumos,id',
            'insumos.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        $receta->update($request->only('nombre', 'rendimiento', 'descripcion', 'activo'));

        $receta->insumos()->detach();
        foreach ($request->insumos as $insumo) {
            if (!empty($insumo['id']) && !empty($insumo['cantidad'])) {
                $receta->insumos()->attach($insumo['id'], ['cantidad_necesaria' => $insumo['cantidad']]);
            }
        }

        return redirect()->route('recetas.show', $receta)->with('success', 'Receta actualizada exitosamente.');
    }

    public function destroy(Receta $receta)
    {
        $receta->delete();
        return redirect()->route('recetas.index')->with('success', 'Receta eliminada exitosamente.');
    }

    // Formulario para usar la receta (producción)
    public function formUsar(Receta $receta)
    {
        $receta->load('insumos');
        $productos = Producto::where('activo', true)->orderBy('nombre')->get();
        return view('recetas.usar', compact('receta', 'productos'));
    }

    // Ejecutar la producción — stock va al producto global; desde Productos se distribuye a almacenes
    public function ejecutarUsar(Request $request, Receta $receta)
    {
        $request->validate([
            'producto_id'        => 'required|exists:productos,id',
            'tandas'             => 'required|integer|min:1',
            'cantidad_producida' => 'required|integer|min:1',
        ]);

        $receta->load('insumos');
        $producto           = Producto::findOrFail($request->producto_id);
        $tandas             = (int) $request->tandas;
        $cantidadProducida  = (int) $request->cantidad_producida;
        $estimado           = $receta->rendimiento * $tandas;

        // Verificar stock de insumos
        foreach ($receta->insumos as $insumo) {
            $necesario = $insumo->pivot->cantidad_necesaria * $tandas;
            if ($insumo->cantidad_stock < $necesario) {
                return redirect()->back()->with('error',
                    "Stock insuficiente de \"{$insumo->nombre}\". Necesitas {$necesario} {$insumo->unidad_medida}, tienes {$insumo->cantidad_stock}."
                );
            }
        }

        DB::transaction(function () use ($receta, $producto, $tandas, $cantidadProducida) {
            foreach ($receta->insumos as $insumo) {
                $insumo->decrement('cantidad_stock', $insumo->pivot->cantidad_necesaria * $tandas);
            }
            $producto->increment('stock', $cantidadProducida);
        });

        $nota = $cantidadProducida !== $estimado
            ? " (estimado: {$estimado}, diferencia: " . ($cantidadProducida - $estimado) . ")"
            : "";

        return redirect()->route('recetas.show', $receta)
            ->with('success', "Producción lista: {$cantidadProducida} unidades de \"{$producto->nombre}\" agregadas al stock{$nota}. Distribúyelas a los almacenes desde el módulo de Productos.");
    }

    // Endpoint AJAX: insumos de la receta
    public function insumos(Receta $receta)
    {
        $receta->load('insumos');
        return response()->json([
            'rendimiento' => $receta->rendimiento,
            'insumos'     => $receta->insumos->map(fn($i) => [
                'id'                 => $i->id,
                'nombre'             => $i->nombre,
                'unidad_medida'      => $i->unidad_medida,
                'cantidad_necesaria' => $i->pivot->cantidad_necesaria,
            ]),
        ]);
    }
}
