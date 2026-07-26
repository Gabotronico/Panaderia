<?php

namespace App\Http\Controllers;

use App\Models\Adelanto;
use App\Models\Empleado;
use App\Models\Planilla;
use App\Models\PlanillaEmpleado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlanillaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $planillas = Planilla::with('user')
            ->withCount('detalles')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('rrhh.planillas.index', compact('planillas'));
    }

    public function create()
    {
        $totalMensuales = Empleado::where('activo', true)->where('tipo_pago', 'mensual')->count();
        $totalSemanales = Empleado::where('activo', true)->where('tipo_pago', 'semanal')->count();
        return view('rrhh.planillas.create', compact('totalMensuales', 'totalSemanales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo'           => 'required|in:mensual,semanal',
            'periodo_inicio' => 'required|date',
            'periodo_fin'    => 'required|date|after_or_equal:periodo_inicio',
            'observaciones'  => 'nullable|string',
        ]);

        // Solo incluir empleados cuyo tipo_pago coincide con el tipo de planilla
        $empleados = Empleado::where('activo', true)
                             ->where('tipo_pago', $request->tipo)
                             ->get();

        if ($empleados->isEmpty()) {
            return redirect()->back()->with('error',
                "No hay empleados activos con pago de tipo «{$request->tipo}». " .
                "Registra empleados con ese tipo de pago o genera una planilla del tipo correcto."
            );
        }

        DB::transaction(function () use ($request, $empleados) {
            $planilla = Planilla::create([
                'tipo'           => $request->tipo,
                'periodo_inicio' => $request->periodo_inicio,
                'periodo_fin'    => $request->periodo_fin,
                'estado'         => 'borrador',
                'observaciones'  => $request->observaciones,
                'user_id'        => Auth::id(),
            ]);

            $totalGeneral = 0;

            foreach ($empleados as $empleado) {
                $detalle = $this->calcularDetalle($empleado, $planilla);

                PlanillaEmpleado::create([
                    'planilla_id'           => $planilla->id,
                    'empleado_id'           => $empleado->id,
                    'dias_trabajados'       => $detalle['dias_trabajados'],
                    'dias_ausentes'         => $detalle['dias_ausentes'],
                    'dias_tardanza'         => $detalle['dias_tardanza'],
                    'dias_medio'            => $detalle['dias_medio'],
                    'horas_extra'           => $detalle['horas_extra'],
                    'monto_horas_extra'     => $detalle['monto_horas_extra'],
                    'adelantos_descontados' => $detalle['adelantos'],
                    'salario_bruto'         => $detalle['salario_bruto'],
                    'descuento_tardanzas'   => $detalle['descuento_tardanzas'],
                    'total_neto'            => $detalle['total_neto'],
                ]);

                // Solo se liquidan los adelantos que alcanzó a cubrir el sueldo.
                // Los que no entraron quedan pendientes para la siguiente planilla,
                // de modo que la deuda nunca se pierde.
                if ($detalle['adelantos_liquidados']) {
                    Adelanto::whereIn('id', $detalle['adelantos_liquidados'])
                        ->update(['planilla_id' => $planilla->id]);
                }

                $totalGeneral += $detalle['total_neto'];
            }

            $planilla->update(['total_general' => $totalGeneral]);
        });

        return redirect()->route('planillas.index')->with('success', 'Planilla generada correctamente.');
    }

    public function show(Planilla $planilla)
    {
        $planilla->load(['detalles.empleado.cargo', 'user']);
        return view('rrhh.planillas.show', compact('planilla'));
    }

    public function descargarPdf(Planilla $planilla)
    {
        $planilla->load(['detalles.empleado.cargo', 'user']);

        $pdf = Pdf::loadView('rrhh.planillas.pdf', compact('planilla'))
                  ->setPaper([0, 0, 1190, 842], 'landscape'); // A3 landscape

        $nombre = "planilla-{$planilla->id}-{$planilla->tipo}-{$planilla->periodo_inicio->format('Y-m-d')}.pdf";

        return $pdf->download($nombre);
    }

    public function cerrar(Planilla $planilla)
    {
        if ($planilla->estado !== 'borrador') {
            return redirect()->back()->with('error', 'La planilla ya fue cerrada o pagada.');
        }
        $planilla->update(['estado' => 'cerrada']);
        return redirect()->route('planillas.show', $planilla)->with('success', 'Planilla cerrada.');
    }

    public function pagar(Planilla $planilla)
    {
        if ($planilla->estado !== 'cerrada') {
            return redirect()->back()->with('error', 'La planilla debe estar cerrada antes de marcarla como pagada.');
        }
        $planilla->update(['estado' => 'pagada']);
        return redirect()->route('planillas.show', $planilla)->with('success', 'Planilla marcada como pagada.');
    }

    private function calcularDetalle(Empleado $empleado, Planilla $planilla): array
    {
        // Solo se cuentan asistencias de lunes a sábado — el domingo no se trabaja
        $asistencias = $empleado->asistencias()
            ->enPeriodo($planilla->periodo_inicio, $planilla->periodo_fin)
            ->laborables()
            ->get();

        $diasPresente    = $asistencias->where('estado', 'presente')->count();
        $diasTardanza    = $asistencias->where('estado', 'tardanza')->count();
        $diasAusente     = $asistencias->where('estado', 'ausente')->count();
        $diasMedio       = $asistencias->where('estado', 'medio_dia')->count();
        $minutosTardanza = $asistencias->where('estado', 'tardanza')->sum('minutos_tardanza');
        $horasExtra      = $asistencias->sum('horas_extra');

        // Días efectivos = presentes + tardanzas + medios×0.5
        $diasEfectivos = $diasPresente + $diasTardanza + ($diasMedio * 0.5);

        // El valor por día sale del tipo de pago del empleado, no de la duración
        // del período. Ver config/nomina.php y App\Models\Empleado.
        $valorDia   = $empleado->valor_dia;
        $tarifaHora = $empleado->tarifa_hora;

        $salarioBruto       = round($valorDia * $diasEfectivos, 2);
        $descuentoTardanzas = round(($minutosTardanza / 60) * $tarifaHora, 2);
        $montoHorasExtra    = round($horasExtra * $tarifaHora * (float) $empleado->factor_hora_extra, 2);

        // Lo que el empleado tiene disponible antes de descontar adelantos
        $disponible = round($salarioBruto + $montoHorasExtra - $descuentoTardanzas, 2);

        // Adelantos pendientes del período, del más antiguo al más reciente
        $pendientes = Adelanto::where('empleado_id', $empleado->id)
            ->whereNull('planilla_id')
            ->whereBetween('fecha', [$planilla->periodo_inicio, $planilla->periodo_fin])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        // Se descuentan de a uno mientras el sueldo alcance. El que no entra
        // queda pendiente para la próxima planilla en lugar de desaparecer.
        $descontado  = 0.0;
        $liquidados  = [];

        foreach ($pendientes as $adelanto) {
            $monto = (float) $adelanto->monto;
            if (round($descontado + $monto, 2) <= $disponible) {
                $descontado  = round($descontado + $monto, 2);
                $liquidados[] = $adelanto->id;
            }
        }

        $saldoArrastrado = round((float) $pendientes->sum('monto') - $descontado, 2);
        $totalNeto       = round($disponible - $descontado, 2);

        return [
            'dias_trabajados'      => $diasPresente + $diasTardanza,
            'dias_ausentes'        => $diasAusente,
            'dias_tardanza'        => $diasTardanza,
            'dias_medio'           => $diasMedio,
            'horas_extra'          => $horasExtra,
            'monto_horas_extra'    => $montoHorasExtra,
            'adelantos'            => $descontado,
            'adelantos_liquidados' => $liquidados,
            'saldo_arrastrado'     => $saldoArrastrado,
            'salario_bruto'        => $salarioBruto,
            'descuento_tardanzas'  => $descuentoTardanzas,
            'total_neto'           => $totalNeto,
        ];
    }
}
