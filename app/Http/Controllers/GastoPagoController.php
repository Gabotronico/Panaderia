<?php

namespace App\Http\Controllers;

use App\Models\GastoFijo;
use App\Models\GastoPago;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GastoPagoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function anual(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $gastosFijos = GastoFijo::orderBy('categoria')->orderBy('nombre')->get();

        $pagosAnio = GastoPago::where('periodo', 'like', $year . '-%')->get();
        $mesDe = fn (GastoPago $p) => (int) explode('-', $p->periodo)[1];

        // Matriz: gasto_fijo_id → mes (1-12) → resumen del mes. Un gasto
        // semanal tiene varios pagos en el mes; la celda los muestra juntos.
        $matriz = [];
        foreach ($gastosFijos as $gasto) {
            $matriz[$gasto->id] = $pagosAnio->where('gasto_fijo_id', $gasto->id)
                ->groupBy($mesDe)
                ->map(fn ($pagosDelMes) => $this->resumirMes($pagosDelMes));
        }

        $pagados = $pagosAnio->where('estado', 'pagado');

        // Totales por mes (solo pagados)
        $totalesMes = [];
        for ($m = 1; $m <= 12; $m++) {
            $totalesMes[$m] = (float) $pagados->filter(fn ($p) => $mesDe($p) === $m)->sum('monto_real');
        }

        // Totales por gasto fijo (suma anual pagada)
        $totalesGasto = $gastosFijos->mapWithKeys(fn ($gasto) => [
            $gasto->id => (float) $pagados->where('gasto_fijo_id', $gasto->id)->sum('monto_real'),
        ]);

        $totalAnio      = array_sum($totalesMes);
        $pendienteAnio  = (float) $pagosAnio->whereIn('estado', ['pendiente', 'vencido'])->sum('monto_estimado');

        return view('gastos.pagos.anual', compact(
            'gastosFijos', 'matriz', 'totalesMes', 'totalesGasto',
            'totalAnio', 'pendienteAnio', 'year'
        ));
    }

    public function index(Request $request)
    {
        $hoy    = now();
        $year   = (int) $request->input('year', $hoy->year);
        $month  = (int) $request->input('month', $hoy->month);
        $periodo = sprintf('%04d-%02d', $year, $month);

        // Marcar como vencidos los pendientes cuya fecha ya pasó
        GastoPago::where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', $hoy->toDateString())
            ->update(['estado' => 'vencido']);

        $pagos = GastoPago::with('gastoFijo')
            ->where('periodo', $periodo)
            ->orderBy('fecha_vencimiento')
            ->get();

        $resumen = [
            'pendiente' => $pagos->where('estado', 'pendiente')->sum('monto_estimado'),
            'vencido'   => $pagos->where('estado', 'vencido')->sum('monto_estimado'),
            'pagado'    => $pagos->where('estado', 'pagado')->sum('monto_real'),
            'total'     => $pagos->sum('monto_estimado'),
        ];

        $yaGenerado = $pagos->isNotEmpty();

        return view('gastos.pagos.index', compact('pagos', 'resumen', 'year', 'month', 'periodo', 'yaGenerado'));
    }

    public function generar(Request $request)
    {
        $request->validate([
            'year'  => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year   = (int) $request->year;
        $month  = (int) $request->month;
        $periodo = sprintf('%04d-%02d', $year, $month);

        $gastos  = GastoFijo::where('activo', true)->get();
        $creados = 0;

        $hoy = now()->toDateString();

        foreach ($gastos as $gasto) {
            // Uno por mes, o uno por semana si el gasto es semanal.
            foreach ($gasto->vencimientosEnMes($year, $month) as $fechaVence) {
                $fecha = $fechaVence->toDateString();

                // Un gasto semanal se distingue por la fecha; los demás, por
                // el mes: si ya se le ajustó el vencimiento a mano, generar
                // de nuevo no tiene que duplicarlo.
                $clave = $gasto->esSemanal()
                    ? ['gasto_fijo_id' => $gasto->id, 'fecha_vencimiento' => $fecha]
                    : ['gasto_fijo_id' => $gasto->id, 'periodo' => $periodo];

                $pago = GastoPago::firstOrCreate($clave, [
                    'periodo'           => $periodo,
                    'fecha_vencimiento' => $fecha,
                    'monto_estimado'    => $gasto->monto_estimado,
                    'estado'            => $fecha < $hoy ? 'vencido' : 'pendiente',
                ]);

                if ($pago->wasRecentlyCreated) {
                    $creados++;
                }
            }
        }

        return redirect()->route('gastos-pagos.index', compact('year', 'month'))
            ->with('success', "Se generaron {$creados} gasto(s) para " . Carbon::createFromDate($year, $month, 1)->locale('es')->isoFormat('MMMM [de] YYYY') . '.');
    }

    /**
     * Resume los pagos de un gasto en un mes para la matriz anual. Con uno
     * solo es ese pago; con varios (gasto semanal) el mes está "pagado"
     * recién cuando lo están todos, y "vencido" si alguno se pasó de fecha.
     */
    private function resumirMes($pagosDelMes): object
    {
        $pagados = $pagosDelMes->where('estado', 'pagado');

        return (object) [
            'estado' => match (true) {
                $pagados->count() === $pagosDelMes->count() => 'pagado',
                $pagosDelMes->contains('estado', 'vencido') => 'vencido',
                default                                     => 'pendiente',
            },
            'monto_real'     => (float) $pagados->sum('monto_real'),
            'monto_estimado' => (float) $pagosDelMes->sum('monto_estimado'),
            'cantidad'       => $pagosDelMes->count(),
            'pagados'        => $pagados->count(),
        ];
    }

    public function pagar(Request $request, GastoPago $gastoPago)
    {
        $request->validate([
            'monto_real'   => 'required|numeric|min:0.01',
            'fecha_pago'   => 'required|date',
            'referencia'   => 'nullable|string|max:100',
            'observaciones'=> 'nullable|string|max:200',
        ]);

        $gastoPago->update([
            'monto_real'    => $request->monto_real,
            'fecha_pago'    => $request->fecha_pago,
            'referencia'    => $request->referencia,
            'observaciones' => $request->observaciones,
            'estado'        => 'pagado',
            'user_id'       => Auth::id(),
        ]);

        [$year, $month] = explode('-', $gastoPago->periodo);

        return redirect()->route('gastos-pagos.index', compact('year', 'month'))
            ->with('success', "«{$gastoPago->gastoFijo->nombre}» marcado como pagado.");
    }

    /** Ajusta el monto esperado de un gasto ya generado (ej. llegó la factura de luz). */
    public function ajustar(Request $request, GastoPago $gastoPago)
    {
        if ($gastoPago->estado === 'pagado') {
            return redirect()->back()->with('error', 'No se puede ajustar un gasto ya pagado. Anula el pago primero.');
        }

        $request->validate([
            'monto_estimado'    => 'required|numeric|min:0.01',
            'fecha_vencimiento' => 'required|date',
        ]);

        $hoy = now()->toDateString();

        $gastoPago->update([
            'monto_estimado'    => $request->monto_estimado,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'estado'            => $request->fecha_vencimiento < $hoy ? 'vencido' : 'pendiente',
        ]);

        [$year, $month] = explode('-', $gastoPago->periodo);

        return redirect()->route('gastos-pagos.index', compact('year', 'month'))
            ->with('success', "Monto de «{$gastoPago->gastoFijo->nombre}» actualizado.");
    }

    public function destroy(GastoPago $gastoPago)
    {
        [$year, $month] = explode('-', $gastoPago->periodo);
        $gastoPago->delete();

        return redirect()->route('gastos-pagos.index', compact('year', 'month'))
            ->with('success', 'Gasto eliminado del mes.');
    }

    public function borrarMes(Request $request)
    {
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'required|integer',
        ]);

        $year   = (int) $request->year;
        $month  = (int) $request->month;
        $periodo = sprintf('%04d-%02d', $year, $month);

        $eliminados = GastoPago::where('periodo', $periodo)->delete();

        return redirect()->route('gastos-pagos.index', compact('year', 'month'))
            ->with('success', "Se eliminaron {$eliminados} gasto(s) generados para este mes.");
    }

    public function anularPago(GastoPago $gastoPago)
    {
        $hoy    = now()->toDateString();
        $estado = $gastoPago->getRawOriginal('fecha_vencimiento') < $hoy ? 'vencido' : 'pendiente';

        $gastoPago->update([
            'monto_real'    => null,
            'fecha_pago'    => null,
            'referencia'    => null,
            'estado'        => $estado,
            'user_id'       => null,
        ]);

        [$year, $month] = explode('-', $gastoPago->periodo);

        return redirect()->route('gastos-pagos.index', compact('year', 'month'))
            ->with('success', "Pago anulado — gasto vuelto a estado {$estado}.");
    }
}
