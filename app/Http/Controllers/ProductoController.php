<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Rules\NombreUnico;
use App\Models\Categoria;
use App\Models\Almacen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-productos')->only(['index', 'show']);
        $this->middleware('permission:crear-productos')->only(['create', 'store']);
        $this->middleware('permission:editar-productos')->only(['edit', 'update', 'transferirAlmacen']);
        $this->middleware('permission:eliminar-productos')->only(['destroy']);
    }

    public function index()
    {
        $productos  = Producto::with('categoria')->orderBy('nombre')->get();
        $categorias = Categoria::where('activo', true)->orderBy('nombre')->get();
        $almacenes  = Almacen::where('activo', true)->orderBy('nombre')->get();

        return view('productos.index', compact('productos', 'almacenes', 'categorias'));
    }

    public function create()
    {
        $categorias = Categoria::where('activo', true)->orderBy('nombre')->get();
        return view('productos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre'       => ['required', 'string', 'max:150', new NombreUnico(
                'productos',
                null,
                'Ya existe el producto ":existente". Si querés sumarle stock, registralo desde Producción en vez de crearlo otra vez.'
            )],
            'descripcion'  => 'nullable|string',
            'precio_venta' => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'imagen'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'activo'       => 'boolean',
        ]);

        $data = $request->except('imagen');
        $data['nombre'] = $this->limpiarNombre($request->nombre);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        Producto::create($data);

        return redirect()->route('productos.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    public function show(Producto $producto)
    {
        $producto->load('categoria', 'almacenes');
        $almacenes = Almacen::where('activo', true)->orderBy('nombre')->get();
        return view('productos.show', compact('producto', 'almacenes'));
    }

    public function edit(Producto $producto)
    {
        $categorias = Categoria::where('activo', true)->orderBy('nombre')->get();
        return view('productos.edit', compact('producto', 'categorias'));
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre'       => ['required', 'string', 'max:150', new NombreUnico(
                'productos',
                $producto->id,
                'Ya existe otro producto llamado ":existente".'
            )],
            'descripcion'  => 'nullable|string',
            'precio_venta' => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'imagen'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'activo'       => 'boolean',
        ]);

        $data = $request->except('imagen');
        $data['nombre'] = $this->limpiarNombre($request->nombre);

        if ($request->hasFile('imagen')) {
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto->update($data);

        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    // Transferir stock global a un almacén
    public function transferirAlmacen(Request $request, Producto $producto)
    {
        $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'cantidad'   => 'required|integer|min:1',
        ]);

        $almacen  = Almacen::findOrFail($request->almacen_id);
        $cantidad = (int) $request->cantidad;

        if ($producto->stock < $cantidad) {
            return redirect()->back()
                ->with('error', "Stock insuficiente. Disponible en bodega: {$producto->stock} unidades.");
        }

        DB::transaction(function () use ($producto, $almacen, $cantidad) {
            // Descontar del stock global
            $producto->decrement('stock', $cantidad);

            // Sumar al almacén (upsert)
            $stockActual = $almacen->stockProducto($producto->id);
            $almacen->productos()->syncWithoutDetaching([
                $producto->id => ['stock' => $stockActual + $cantidad],
            ]);
        });

        return redirect()->route('productos.show', $producto)
            ->with('success', "{$cantidad} unidades transferidas al almacén \"{$almacen->nombre}\".");
    }

    public function destroy(Producto $producto)
    {
        try {
            // Eliminar imagen si existe
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            
            $producto->delete();
            return redirect()->route('productos.index')
                ->with('success', 'Producto eliminado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('productos.index')
                ->with('error', 'No se puede eliminar el producto porque tiene ventas asociadas.');
        }
    }

    /**
     * Deja el nombre listo para guardar: sin espacios en los bordes y sin
     * repetidos en el medio, para que no convivan "Pan " y "Pan".
     *
     * Con /u, preg_replace devuelve null si el texto no es UTF-8 válido; el
     * respaldo sin Unicode evita guardar un nombre nulo y reventar el alta.
     */
    private function limpiarNombre(string $nombre): string
    {
        $nombre = trim($nombre);

        return preg_replace('/\s+/u', ' ', $nombre) ?? preg_replace('/\s+/', ' ', $nombre);
    }
}