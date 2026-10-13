<?php

namespace App\Http\Controllers;

use App\Models\GastoFijo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GastoFijoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
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
        $data = $this->validar($request);

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
        $data = $this->validar($request);

        $data['activo'] = $request->boolean('activo');
        $gastosFijo->update($data);

        return redirect()->route('gastos-fijos.index')
            ->with('success', 'Gasto fijo actualizado.');
    }

    /**
     * Un gasto semanal vence un día de la semana (cada sábado); los demás,
     * un día del mes. Solo se exige el dato que corresponde a la frecuencia;
     * el otro queda como estaba, por si después se la cambia.
     */
    private function validar(Request $request): array
    {
        $data = $request->validate([
            'nombre'          => 'required|string|max:100',
            'categoria'       => ['required', Rule::in(array_keys(GastoFijo::CATEGORIAS))],
            'monto_estimado'  => 'required|numeric|min:0.01',
            'frecuencia'      => ['required', Rule::in(array_keys(GastoFijo::FRECUENCIAS))],
            'dia_semana'      => 'exclude_unless:frecuencia,semanal|required|integer|min:1|max:7',
            'dia_vencimiento' => 'exclude_if:frecuencia,semanal|required|integer|min:1|max:28',
            'mes_inicio'      => 'nullable|integer|min:1|max:12',
            'proveedor'       => 'nullable|string|max:100',
            'observaciones'   => 'nullable|string',
        ], [
            'dia_semana.required' => 'Indicá qué día de la semana se paga.',
        ]);

        if ($data['frecuencia'] !== 'semanal') {
            $data['dia_semana'] = null;
        }

        $data['mes_inicio'] ??= 1;

        return $data;
    }

    public function destroy(GastoFijo $gastosFijo)
    {
        $gastosFijo->delete();
        return redirect()->route('gastos-fijos.index')
            ->with('success', 'Gasto fijo eliminado.');
    }
}
