<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlmacenController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-almacenes')->only(['index', 'show']);
        $this->middleware('permission:crear-almacenes')->only(['create', 'store']);
        $this->middleware('permission:editar-almacenes')->only(['edit', 'update', 'asignarCajero', 'desasignarCajero', 'distribuirStock']);
        $this->middleware('permission:eliminar-almacenes')->only(['destroy']);
    }

    public function index()
    {
        $almacenes = Almacen::withCount('cajeros')->orderBy('nombre')->get();
        return view('almacenes.index', compact('almacenes'));
    }

    public function create()
    {
        return view('almacenes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:100|unique:almacenes,nombre',
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ]);

        Almacen::create($request->only('nombre', 'descripcion', 'activo'));

        return redirect()->route('almacenes.index')
            ->with('success', 'Almacén creado exitosamente.');
    }

    public function show(Almacen $almacen)
    {
        $almacen->load(['cajeros', 'productos' => fn($q) => $q->orderBy('nombre')]);

        // Cajeros sin almacén asignado (para el selector de asignación)
        $cajerosSinAlmacen = User::role('Cajero')
            ->whereNull('almacen_id')
            ->orderBy('name')
            ->get();

        return view('almacenes.show', compact('almacen', 'cajerosSinAlmacen'));
    }

    public function edit(Almacen $almacen)
    {
        return view('almacenes.edit', compact('almacen'));
    }

    public function update(Request $request, Almacen $almacen)
    {
        $request->validate([
            'nombre'      => 'required|string|max:100|unique:almacenes,nombre,' . $almacen->id,
            'descripcion' => 'nullable|string',
            'activo'      => 'boolean',
        ]);

        $almacen->update($request->only('nombre', 'descripcion', 'activo'));

        return redirect()->route('almacenes.show', $almacen)
            ->with('success', 'Almacén actualizado exitosamente.');
    }

    public function destroy(Almacen $almacen)
    {
        // Liberar cajeros antes de eliminar
        User::where('almacen_id', $almacen->id)->update(['almacen_id' => null]);
        $almacen->delete();

        return redirect()->route('almacenes.index')
            ->with('success', 'Almacén eliminado.');
    }

    // Asignar un cajero a este almacén
    public function asignarCajero(Request $request, Almacen $almacen)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->update(['almacen_id' => $almacen->id]);

        return redirect()->route('almacenes.show', $almacen)
            ->with('success', "Cajero \"{$user->name}\" asignado al almacén.");
    }

    // Quitar cajero del almacén
    public function desasignarCajero(Almacen $almacen, User $user)
    {
        $user->update(['almacen_id' => null]);

        return redirect()->route('almacenes.show', $almacen)
            ->with('success', "Cajero \"{$user->name}\" removido del almacén.");
    }

    // Retornar stock del almacén a la bodega global
    public function retornarBodega(Request $request, Almacen $almacen, Producto $producto)
    {
        $request->validate([
            'cantidad' => 'required|integer|min:1',
        ]);

        $cantidad     = (int) $request->cantidad;
        $stockAlmacen = $almacen->stockProducto($producto->id);

        if ($stockAlmacen < $cantidad) {
            return redirect()->route('almacenes.show', $almacen)
                ->with('error', "Stock insuficiente en este almacén. Disponible: {$stockAlmacen} unidades.");
        }

        DB::transaction(function () use ($almacen, $producto, $cantidad, $stockAlmacen) {
            $almacen->productos()->syncWithoutDetaching([
                $producto->id => ['stock' => $stockAlmacen - $cantidad],
            ]);
            $producto->increment('stock', $cantidad);
        });

        return redirect()->route('almacenes.show', $almacen)
            ->with('success', "{$cantidad} unidades de \"{$producto->nombre}\" retornadas a bodega.");
    }

    // Formulario para distribuir/ajustar stock de un producto en este almacén
    public function distribuirStock(Request $request, Almacen $almacen)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'stock'       => 'required|integer|min:0',
        ]);

        $producto     = Producto::findOrFail($request->producto_id);
        $stockAnterior = $almacen->stockProducto($producto->id);
        $diferencia   = $request->stock - $stockAnterior;

        // Verificar que el stock global sea suficiente si se quiere aumentar
        if ($diferencia > 0 && $producto->stock < $diferencia) {
            return redirect()->route('almacenes.show', $almacen)
                ->with('error', "No hay suficiente stock global de \"{$producto->nombre}\" para asignar esa cantidad.");
        }

        DB::transaction(function () use ($almacen, $producto, $request, $diferencia) {
            // Upsert en almacen_producto
            $almacen->productos()->syncWithoutDetaching([
                $producto->id => ['stock' => $request->stock],
            ]);

            // Ajustar stock global del producto
            $producto->decrement('stock', $diferencia);
        });

        return redirect()->route('almacenes.show', $almacen)
            ->with('success', "Stock de \"{$producto->nombre}\" actualizado en este almacén.");
    }
}
