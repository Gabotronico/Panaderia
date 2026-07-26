<?php

namespace App\Http\Controllers;

use App\Models\Adelanto;
use App\Models\Cargo;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpleadoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->esAdministrador()) abort(403);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $buscar = $request->input('buscar');
        $cargo  = $request->input('cargo_id');

        $empleados = Empleado::with('cargo')
            ->when($buscar, fn($q) => $q->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('apellido', 'like', "%{$buscar}%")
                  ->orWhere('ci', 'like', "%{$buscar}%");
            }))
            ->when($cargo, fn($q) => $q->where('cargo_id', $cargo))
            ->orderBy('apellido')
            ->paginate(15)
            ->withQueryString();

        $cargos = Cargo::orderBy('nombre')->get();

        return view('rrhh.empleados.index', compact('empleados', 'cargos', 'buscar', 'cargo'));
    }

    public function create()
    {
        $cargos = Cargo::orderBy('nombre')->get();
        return view('rrhh.empleados.create', compact('cargos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'ci'               => 'required|string|max:20|unique:empleados,ci',
            'telefono'         => 'nullable|string|max:20',
            'cargo_id'         => 'required|exists:cargos,id',
            'salario_base'     => 'required|numeric|min:0',
            'tipo_pago'        => 'required|in:mensual,semanal',
            'factor_hora_extra'=> 'required|numeric|min:1',
            'fecha_ingreso'    => 'required|date',
            'observaciones'    => 'nullable|string',
        ]);

        Empleado::create($request->all());

        return redirect()->route('empleados.index')->with('success', 'Empleado registrado exitosamente.');
    }

    public function show(Empleado $empleado)
    {
        $empleado->load('cargo');

        $asistencias = $empleado->asistencias()
            ->orderBy('fecha', 'desc')
            ->take(30)
            ->get();

        $adelantos = $empleado->adelantos()
            ->with('planilla', 'user')
            ->orderBy('fecha', 'desc')
            ->get();

        $planillaDetalles = $empleado->planillaDetalles()
            ->with('planilla')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $totalAdelantosPendientes = $empleado->adelantos()
            ->whereNull('planilla_id')
            ->sum('monto');

        return view('rrhh.empleados.show', compact(
            'empleado', 'asistencias', 'adelantos', 'planillaDetalles', 'totalAdelantosPendientes'
        ));
    }

    public function edit(Empleado $empleado)
    {
        $cargos = Cargo::orderBy('nombre')->get();
        return view('rrhh.empleados.edit', compact('empleado', 'cargos'));
    }

    public function update(Request $request, Empleado $empleado)
    {
        $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido'         => 'required|string|max:100',
            'ci'               => 'required|string|max:20|unique:empleados,ci,' . $empleado->id,
            'telefono'         => 'nullable|string|max:20',
            'cargo_id'         => 'required|exists:cargos,id',
            'salario_base'     => 'required|numeric|min:0',
            'tipo_pago'        => 'required|in:mensual,semanal',
            'factor_hora_extra'=> 'required|numeric|min:1',
            'fecha_ingreso'    => 'required|date',
            'observaciones'    => 'nullable|string',
        ]);

        $empleado->update($request->all());

        return redirect()->route('empleados.show', $empleado)->with('success', 'Empleado actualizado.');
    }

    public function destroy(Empleado $empleado)
    {
        $empleado->update(['activo' => false]);
        return redirect()->route('empleados.index')->with('success', 'Empleado desactivado.');
    }

    // Registrar adelanto desde ficha del empleado
    public function adelanto(Request $request, Empleado $empleado)
    {
        $request->validate([
            'monto'       => 'required|numeric|min:0.01',
            'fecha'       => 'required|date',
            'descripcion' => 'nullable|string|max:255',
        ]);

        Adelanto::create([
            'empleado_id' => $empleado->id,
            'monto'       => $request->monto,
            'fecha'       => $request->fecha,
            'descripcion' => $request->descripcion,
            'user_id'     => Auth::id(),
        ]);

        return redirect()->route('empleados.show', $empleado)
            ->with('success', "Adelanto de Bs {$request->monto} registrado para {$empleado->nombre_completo}.");
    }
}
