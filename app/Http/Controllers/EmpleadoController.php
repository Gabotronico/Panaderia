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
        $this->middleware(['auth', 'admin']);
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

        // Resumen del personal — se calcula sobre todos, no sobre la página filtrada
        $activos = Empleado::activos()->get();
        $resumen = [
            'total'         => $activos->count(),
            'mensuales'     => $activos->where('tipo_pago', 'mensual')->count(),
            'semanales'     => $activos->where('tipo_pago', 'semanal')->count(),
            'costo_mensual' => $activos->sum(fn($e) => $e->valor_mes),
            'adelantos'     => Adelanto::whereNull('planilla_id')->sum('monto'),
        ];

        return view('rrhh.empleados.index', compact('empleados', 'cargos', 'buscar', 'cargo', 'resumen'));
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
            // El horario habilita el cálculo automático de tardanza y horas
            // extra. Es opcional, pero si se define uno hacen falta los dos
            // extremos: sin salida no hay contra qué medir las extras.
            'hora_entrada'       => 'nullable|date_format:H:i|required_with:hora_salida',
            'hora_salida'        => 'nullable|date_format:H:i|required_with:hora_entrada',
            'minutos_tolerancia' => 'nullable|integer|min:0|max:120',
            'observaciones'    => 'nullable|string',
        ], [
            'hora_entrada.required_with' => 'Indique también la hora de entrada del horario.',
            'hora_salida.required_with'  => 'Indique también la hora de salida del horario.',
        ]);

        Empleado::create($this->conHorarioNormalizado($request));

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
            // El horario habilita el cálculo automático de tardanza y horas
            // extra. Es opcional, pero si se define uno hacen falta los dos
            // extremos: sin salida no hay contra qué medir las extras.
            'hora_entrada'       => 'nullable|date_format:H:i|required_with:hora_salida',
            'hora_salida'        => 'nullable|date_format:H:i|required_with:hora_entrada',
            'minutos_tolerancia' => 'nullable|integer|min:0|max:120',
            'observaciones'    => 'nullable|string',
        ], [
            'hora_entrada.required_with' => 'Indique también la hora de entrada del horario.',
            'hora_salida.required_with'  => 'Indique también la hora de salida del horario.',
        ]);

        $empleado->update($this->conHorarioNormalizado($request));

        return redirect()->route('empleados.show', $empleado)->with('success', 'Empleado actualizado.');
    }

    /**
     * Los inputs de tipo time y number llegan como cadena vacía cuando el
     * usuario los deja en blanco. Guardarlos así dejaría un '' en columnas
     * que deben quedar en null para que el sistema sepa que no hay horario.
     */
    private function conHorarioNormalizado(Request $request): array
    {
        $datos = $request->all();

        foreach (['hora_entrada', 'hora_salida', 'minutos_tolerancia'] as $campo) {
            if (($datos[$campo] ?? '') === '') {
                $datos[$campo] = null;
            }
        }

        return $datos;
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

        // Un adelanto que supere el sueldo del ciclo no se podría descontar
        // completo en la planilla y arrastraría deuda indefinidamente.
        $capacidad = $empleado->capacidad_adelanto;

        if ((float) $request->monto > $capacidad) {
            $pendiente = $empleado->adelantos_pendientes;

            return redirect()->route('empleados.show', $empleado)->with('error',
                "El adelanto de Bs " . number_format((float) $request->monto, 2) .
                " supera lo que {$empleado->nombre} puede recibir. " .
                "Su sueldo del ciclo es Bs " . number_format((float) $empleado->salario_base, 2) .
                ($pendiente > 0 ? " y ya tiene Bs " . number_format($pendiente, 2) . " en adelantos sin descontar" : "") .
                ", así que el máximo disponible es Bs " . number_format($capacidad, 2) . "."
            );
        }

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
