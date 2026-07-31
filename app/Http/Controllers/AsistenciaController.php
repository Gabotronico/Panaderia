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

        $empleados = Empleado::with('cargo')->where('activo', true)->orderBy('apellido')->get();

        return view('rrhh.asistencias.index', compact('asistencias', 'empleados', 'fecha', 'empleado_id'));
    }

    public function registrar(Request $request)
    {
        $fecha     = $request->input('fecha', now()->toDateString());
        $esDomingo = \Carbon\Carbon::parse($fecha)->dayOfWeek === 0;
        $empleados = Empleado::with('cargo')->where('activo', true)->orderBy('apellido')->get();

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
            'asistencias.*.observaciones'    => 'nullable|string|max:200',
        ]);

        // Se cargan de una vez para no consultar el horario empleado por
        // empleado dentro del bucle.
        $empleados = Empleado::whereIn('id', array_keys($request->asistencias))->get()->keyBy('id');

        DB::transaction(function () use ($request, $empleados) {
            foreach ($request->asistencias as $empleadoId => $datos) {
                $marcaje = $this->resolverMarcaje($empleados->get($empleadoId), $datos);

                Asistencia::updateOrCreate(
                    ['empleado_id' => $empleadoId, 'fecha' => $request->fecha],
                    [
                        'estado'           => $marcaje['estado'],
                        'hora_entrada'     => $datos['hora_entrada'] ?? null,
                        'hora_salida'      => $datos['hora_salida'] ?? null,
                        'minutos_tardanza' => $marcaje['minutos_tardanza'],
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
            'observaciones'    => 'nullable|string|max:200',
        ]);

        $marcaje = $this->resolverMarcaje($asistencia->empleado, $request->all());

        $asistencia->update([
            'estado'           => $marcaje['estado'],
            'hora_entrada'     => $request->hora_entrada,
            'hora_salida'      => $request->hora_salida,
            'minutos_tardanza' => $marcaje['minutos_tardanza'],
            'observaciones'    => $request->observaciones,
            'user_id'          => Auth::id(),
        ]);

        return redirect()->route('asistencias.index')
            ->with('success', 'Asistencia actualizada.');
    }

    /**
     * Decide el estado y los minutos de atraso de un registro de asistencia.
     *
     * Si el empleado tiene horario, el atraso sale de comparar la hora de
     * entrada contra ese horario y no se acepta a mano: el dato que llega del
     * formulario es solo una vista previa. Sin horario no hay referencia, así
     * que se respeta lo que cargó el encargado.
     *
     * Un atraso detectado sobre un día marcado como "presente" cambia el
     * estado a "tardanza"; de otro modo el estado diría una cosa y los
     * minutos otra. Los demás estados (ausente, feriado, licencia…) se
     * respetan tal cual porque describen situaciones que el horario no ve.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado: string, minutos_tardanza: int}
     */
    private function resolverMarcaje(?Empleado $empleado, array $datos): array
    {
        $estado = $datos['estado'];

        if (!$empleado?->tiene_horario) {
            return [
                'estado'           => $estado,
                'minutos_tardanza' => $estado === 'tardanza' ? (int) ($datos['minutos_tardanza'] ?? 0) : 0,
            ];
        }

        // Solo los días efectivamente trabajados se comparan contra el horario.
        if (!\in_array($estado, Asistencia::ESTADOS_TRABAJADOS, true)) {
            return ['estado' => $estado, 'minutos_tardanza' => 0];
        }

        $tardanza = $empleado->calcularTardanza($datos['hora_entrada'] ?? null);

        if ($tardanza > 0 && $estado === 'presente') {
            $estado = 'tardanza';
        }

        return ['estado' => $estado, 'minutos_tardanza' => $tardanza];
    }
}
