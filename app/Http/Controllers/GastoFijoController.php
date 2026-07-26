<?php

namespace App\Http\Controllers;

use App\Models\GastoFijo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GastoFijoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->esAdministrador()) abort(403);
            return $next($request);
        });
    }

    public function index()
    {
        $gastos = GastoFijo::with('user')->orderBy('categoria')->orderBy('nombre')->get();
        return view('gastos.fijos.index', compact('gastos'));
    }

    public function create()
    {
        return view('gastos.fijos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'          => 'required|string|max:100',
            'categoria'       => 'required|in:alquiler,servicios,mantenimiento,impuestos,otro',
            'monto_estimado'  => 'required|numeric|min:0.01',
            'frecuencia'      => 'required|in:mensual,bimestral,trimestral,semestral,anual',
            'dia_vencimiento' => 'required|integer|min:1|max:28',
            'mes_inicio'      => 'required|integer|min:1|max:12',
            'proveedor'       => 'nullable|string|max:100',
            'observaciones'   => 'nullable|string',
        ]);

        GastoFijo::create(array_merge($data, ['user_id' => Auth::id()]));

        return redirect()->route('gastos-fijos.index')
            ->with('success', 'Gasto fijo registrado correctamente.');
    }

    public function edit(GastoFijo $gastosFijo)
    {
        return view('gastos.fijos.edit', ['gasto' => $gastosFijo]);
    }

    public function update(Request $request, GastoFijo $gastosFijo)
    {
        $data = $request->validate([
            'nombre'          => 'required|string|max:100',
            'categoria'       => 'required|in:alquiler,servicios,mantenimiento,impuestos,otro',
            'monto_estimado'  => 'required|numeric|min:0.01',
            'frecuencia'      => 'required|in:mensual,bimestral,trimestral,semestral,anual',
            'dia_vencimiento' => 'required|integer|min:1|max:28',
            'mes_inicio'      => 'required|integer|min:1|max:12',
            'proveedor'       => 'nullable|string|max:100',
            'observaciones'   => 'nullable|string',
            'activo'          => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo');
        $gastosFijo->update($data);

        return redirect()->route('gastos-fijos.index')
            ->with('success', 'Gasto fijo actualizado.');
    }

    public function destroy(GastoFijo $gastosFijo)
    {
        $gastosFijo->delete();
        return redirect()->route('gastos-fijos.index')
            ->with('success', 'Gasto fijo eliminado.');
    }
}
