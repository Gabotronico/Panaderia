<?php

namespace App\Http\Controllers;

use App\Models\GastoVariable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GastoVariableController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes  = (int) $request->input('mes', now()->month);

        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }

        $inicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fin    = $inicio->copy()->endOfMonth();

        $gastos = GastoVariable::with('user')
            ->enPeriodo($inicio, $fin)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $porCategoria = $gastos->groupBy('categoria')
            ->map(fn ($g) => (float) $g->sum('monto'))
            ->sortDesc();

        $total = (float) $gastos->sum('monto');

        return view('gastos.variables.index', compact(
            'gastos', 'porCategoria', 'total', 'inicio', 'mes', 'anio'
        ));
    }

    public function create()
    {
        return view('gastos.variables.create');
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $datos['user_id'] = Auth::id();

        GastoVariable::create($datos);

        return redirect()->route('gastos-variables.index', [
            'mes'  => Carbon::parse($datos['fecha'])->month,
            'anio' => Carbon::parse($datos['fecha'])->year,
        ])->with('success', 'Gasto variable registrado.');
    }

    public function edit(GastoVariable $gastosVariable)
    {
        return view('gastos.variables.edit', ['gasto' => $gastosVariable]);
    }

    public function update(Request $request, GastoVariable $gastosVariable)
    {
        $gastosVariable->update($this->validar($request));

        return redirect()->route('gastos-variables.index', [
            'mes'  => $gastosVariable->fecha->month,
            'anio' => $gastosVariable->fecha->year,
        ])->with('success', 'Gasto variable actualizado.');
    }

    public function destroy(GastoVariable $gastosVariable)
    {
        $concepto = $gastosVariable->concepto;
        $gastosVariable->delete();

        return redirect()->route('gastos-variables.index')
            ->with('success', "Se eliminó el gasto \"{$concepto}\".");
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        return $request->validate([
            'fecha'         => 'required|date',
            'concepto'      => 'required|string|max:150',
            'categoria'     => 'required|in:' . implode(',', array_keys(GastoVariable::CATEGORIAS)),
            'monto'         => 'required|numeric|min:0.01|max:9999999',
            'proveedor'     => 'nullable|string|max:150',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'monto.min' => 'El monto debe ser mayor a cero.',
        ]);
    }
}
