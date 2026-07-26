<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request)
    {
        $fecha       = $request->input('fecha', now()->toDateString());
        $empleado_id = $request->input('empleado_id');

        $asistencias = Asistencia::with('empleado.cargo')
            ->when($fecha,       fn($q) => $q->whereDate('fecha', $fecha))
            ->when($empleado_id, fn($q) => $q->where('empleado_id', $empleado_id))
            ->orderBy('fecha', 'desc')
            ->paginate(20)
            ->withQueryString();

        $empleados = Empleado::where('activo', true)->orderBy('apellido')->get();

        return view('rrhh.asistencias.index', compact('asistencias', 'empleados', 'fecha', 'empleado_id'));
    }

    public function registrar(Request $request)
    {
        $fecha     = $request->input('fecha', now()->toDateString());
        $esDomingo = \Carbon\Carbon::parse($fecha)->dayOfWeek === 0;
        $empleados = Empleado::where('activo', true)->orderBy('apellido')->get();

        // Pre-cargar TODOS los campos de registros existentes para esa fecha
        // keyBy permite acceder como $registradas[$empleado_id]->hora_entrada etc.
        $registradas = Asistencia::whereDate('fecha', $fecha)
            ->get()
            ->keyBy('empleado_id');

        return view('rrhh.asistencias.registrar', compact('empleados', 'fecha', 'registradas', 'esDomingo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha'                          => 'required|date',
            'asistencias'                    => 'required|array',
            'asistencias.*.estado'           => 'required|in:presente,ausente,tardanza,medio_dia,feriado,licencia',
            'asistencias.*.hora_entrada'     => 'nullable|date_format:H:i',
            'asistencias.*.hora_salida'      => 'nullable|date_format:H:i',
            'asistencias.*.minutos_tardanza' => 'nullable|integer|min:0|max:480',
            'asistencias.*.horas_extra'      => 'nullable|numeric|min:0|max:12',
            'asistencias.*.observaciones'    => 'nullable|string|max:200',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->asistencias as $empleadoId => $datos) {
                Asistencia::updateOrCreate(
                    ['empleado_id' => $empleadoId, 'fecha' => $request->fecha],
                    [
                        'estado'           => $datos['estado'],
                        'hora_entrada'     => $datos['hora_entrada'] ?? null,
                        'hora_salida'      => $datos['hora_salida'] ?? null,
                        'minutos_tardanza' => ($datos['estado'] === 'tardanza') ? (int)($datos['minutos_tardanza'] ?? 0) : 0,
                        'horas_extra'      => $datos['horas_extra'] ?? 0,
                        'observaciones'    => $datos['observaciones'] ?? null,
                        'user_id'          => Auth::id(),
                    ]
                );
            }
        });

        return redirect()->route('asistencias.index', ['fecha' => $request->fecha])
            ->with('success', 'Asistencias guardadas para ' . $request->fecha . '.');
    }

    public function edit(Asistencia $asistencia)
    {
        return view('rrhh.asistencias.edit', compact('asistencia'));
    }

    public function update(Request $request, Asistencia $asistencia)
    {
        $request->validate([
            'estado'           => 'required|in:presente,ausente,tardanza,medio_dia,feriado,licencia',
            'hora_entrada'     => 'nullable|date_format:H:i',
            'hora_salida'      => 'nullable|date_format:H:i',
            'minutos_tardanza' => 'nullable|integer|min:0|max:480',
            'horas_extra'      => 'nullable|numeric|min:0|max:12',
            'observaciones'    => 'nullable|string|max:200',
        ]);

        $asistencia->update([
            'estado'           => $request->estado,
            'hora_entrada'     => $request->hora_entrada,
            'hora_salida'      => $request->hora_salida,
            'minutos_tardanza' => ($request->estado === 'tardanza') ? (int)($request->minutos_tardanza ?? 0) : 0,
            'horas_extra'      => $request->horas_extra ?? 0,
            'observaciones'    => $request->observaciones,
            'user_id'          => Auth::id(),
        ]);

        return redirect()->route('asistencias.index')
            ->with('success', 'Asistencia actualizada.');
    }
}
