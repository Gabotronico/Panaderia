<?php

namespace App\Http\Controllers;

use App\Models\Adelanto;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\Planilla;
use App\Models\PlanillaEmpleado;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
                    'dias_pagados'          => $detalle['dias_pagados'],
                    'adelantos_descontados' => $detalle['adelantos'],
                    'salario_bruto'         => $detalle['salario_bruto'],
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

        // A4 horizontal: al quitar las columnas de horas extra y descuentos
        // la tabla entra en la hoja estándar disponible para imprimir.
        $pdf = Pdf::loadView('rrhh.planillas.pdf', compact('planilla'))
                  ->setPaper('a4', 'landscape');

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

    /**
     * Marca la planilla como pagada y registra QUÉ DÍA salió la plata.
     *
     * La fecha de pago es la que define en qué mes pesa el sueldo, no el
     * período trabajado. Una semana de lunes 31/08 a sábado 05/09 se paga el
     * sábado, así que es costo de septiembre aunque haya empezado en agosto.
     *
     * Por defecto se propone el cierre del período (el sábado de pago), pero
     * el administrador puede corregirlo si la plata salió otro día.
     */
    public function pagar(Request $request, Planilla $planilla)
    {
        if ($planilla->estado !== 'cerrada') {
            return redirect()->back()->with('error', 'La planilla debe estar cerrada antes de marcarla como pagada.');
        }

        $request->validate([
            'fecha_pago' => [
                'nullable',
                'date',
                'after_or_equal:' . $planilla->periodo_inicio->toDateString(),
                'before_or_equal:' . now()->toDateString(),
            ],
        ], [
            'fecha_pago.after_or_equal'  => 'El pago no puede ser anterior al inicio del período ('
                                            . $planilla->periodo_inicio->format('d/m/Y') . ').',
            'fecha_pago.before_or_equal' => 'No se puede registrar un pago con fecha futura.',
        ]);

        $fechaPago = $request->filled('fecha_pago')
            ? Carbon::parse($request->fecha_pago)
            : $planilla->fecha_pago_sugerida;

        $planilla->update([
            'estado'     => 'pagada',
            'fecha_pago' => $fechaPago->toDateString(),
        ]);

        return redirect()->route('planillas.show', $planilla)->with('success',
            'Planilla marcada como pagada el ' . $fechaPago->format('d/m/Y')
            . '. El gasto se imputa a ' . ucfirst($fechaPago->translatedFormat('F \d\e Y')) . '.'
        );
    }

    /**
     * Carga a mano las horas extra de un empleado dentro de la planilla.
     *
     * El sistema no las deduce del horario: el administrador decide cuántas
     * reconoce y cuánta plata paga por ellas, porque el monto suele ser un
     * acuerdo y no el resultado de multiplicar la tarifa por las horas.
     *
     * Solo se pueden tocar mientras la planilla está en borrador: una vez
     * cerrada el monto ya se comunicó, y una vez pagada la plata salió.
     */
    public function horasExtra(Request $request, Planilla $planilla, PlanillaEmpleado $detalle)
    {
        if ($detalle->planilla_id !== $planilla->id) {
            abort(404);
        }

        if ($planilla->estado !== 'borrador') {
            return redirect()->route('planillas.show', $planilla)->with('error',
                'La planilla ya está ' . $planilla->estado . ': para cambiar las horas extra tiene que estar en borrador.'
            );
        }

        $request->validate([
            'horas_extra'         => 'required|numeric|min:0|max:400',
            'monto_horas_extra'   => 'required|numeric|min:0',
            'detalle_horas_extra' => 'nullable|string|max:255',
        ], [
            'horas_extra.max' => 'Son demasiadas horas para un solo período; revisá el dato.',
        ]);

        $horas = (float) $request->horas_extra;
        $monto = (float) $request->monto_horas_extra;

        // Pagar por horas que no se cargaron, o cargar horas que no se pagan,
        // deja la planilla diciendo una cosa y el PDF otra.
        if ($horas > 0 && $monto <= 0) {
            return redirect()->route('planillas.show', $planilla)->with('error',
                'Indicá cuánto se le paga por esas ' . rtrim(rtrim(number_format($horas, 2), '0'), '.') . ' horas extra.'
            );
        }

        if ($monto > 0 && $horas <= 0) {
            return redirect()->route('planillas.show', $planilla)->with('error',
                'Indicá cuántas horas extra corresponden a ese monto.'
            );
        }

        DB::transaction(function () use ($planilla, $detalle, $horas, $monto, $request) {
            $detalle->update([
                'horas_extra'         => $horas,
                'monto_horas_extra'   => $monto,
                'detalle_horas_extra' => $request->detalle_horas_extra,
            ]);

            $detalle->update(['total_neto' => $detalle->calcularNeto()]);

            $planilla->update([
                'total_general' => $planilla->detalles()->sum('total_neto'),
            ]);
        });

        $nombre = $detalle->empleado->nombre_completo;

        return redirect()->route('planillas.show', $planilla)->with('success',
            $monto > 0
                ? "Se cargaron {$request->horas_extra} horas extra a {$nombre} por Bs " . number_format($monto, 2) . '.'
                : "Se quitaron las horas extra de {$nombre}."
        );
    }

    /**
     * Elimina una planilla generada por error y deja todo como antes.
     *
     * Al generarse, la planilla "consume" los adelantos del período marcándolos
     * con su id. Si se borra sin liberarlos esa deuda desaparece: el empleado
     * dejaría de deber una plata que sí se le entregó. Por eso se devuelven a
     * pendientes de forma explícita, dentro de la misma transacción que el
     * borrado, en lugar de depender de la clave foránea.
     */
    public function destroy(Planilla $planilla)
    {
        $adelantosLiberados = Adelanto::where('planilla_id', $planilla->id)->count();
        $identificador      = $planilla->id;
        $periodo            = $planilla->periodo_inicio->format('d/m/Y') . ' al ' . $planilla->periodo_fin->format('d/m/Y');

        DB::transaction(function () use ($planilla) {
            Adelanto::where('planilla_id', $planilla->id)->update(['planilla_id' => null]);

            // Los detalles por empleado se van en cascada con la planilla.
            $planilla->delete();
        });

        $mensaje = "Se eliminó la planilla #{$identificador} ({$periodo}).";

        if ($adelantosLiberados > 0) {
            $mensaje .= " {$adelantosLiberados} adelanto(s) volvieron a quedar pendientes de descuento.";
        }

        return redirect()->route('planillas.index')->with('success', $mensaje);
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

        // Cada día aporta la parte que se le paga: completo si cumplió su
        // jornada, solo las horas trabajadas si no. Los días sin marcaje de
        // entrada y salida se pagan por estado. Ver Asistencia::fraccionDiaPagada().
        $diasEfectivos = round(
            $asistencias->sum(fn (Asistencia $a) => $a->fraccionDiaPagada($empleado)),
            4
        );

        // El sueldo sale del tipo de pago del empleado, no de la duración del
        // período. Si cumplió el ciclo completo se le paga el salario tal como
        // se registró, sin recomponerlo desde el valor diario redondeado.
        // Ver config/nomina.php y Empleado::sueldoPorDias().
        $salarioBruto = $empleado->sueldoPorDias($diasEfectivos);

        // Sin horas extra ni descuentos por tardanza, únicamente los
        // adelantos reducen el salario ganado por los días efectivos.
        $disponible = $salarioBruto;

        // Adelantos pendientes del período, del más antiguo al más reciente.
        // Los límites van como fecha sin hora por el mismo motivo que en
        // Asistencia::scopeEnPeriodo: si no, el primer día queda fuera.
        $pendientes = Adelanto::where('empleado_id', $empleado->id)
            ->whereNull('planilla_id')
            ->whereBetween('fecha', [
                $planilla->periodo_inicio->toDateString(),
                $planilla->periodo_fin->toDateString(),
            ])
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
            'dias_pagados'         => round($diasEfectivos, 2),
            'adelantos'            => $descontado,
            'adelantos_liquidados' => $liquidados,
            'saldo_arrastrado'     => $saldoArrastrado,
            'salario_bruto'        => $salarioBruto,
            'total_neto'           => $totalNeto,
        ];
    }
}
