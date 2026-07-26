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

        $pagosAnio = GastoPago::where('periodo', 'like', $year . '-%')
            ->get()
            ->groupBy('gasto_fijo_id');

        // Matriz: gasto_fijo_id → mes (1-12) → GastoPago|null
        $matriz = [];
        foreach ($gastosFijos as $gasto) {
            $porMes = $pagosAnio->get($gasto->id, collect())
                ->keyBy(fn($p) => (int) explode('-', $p->periodo)[1]);
            $matriz[$gasto->id] = $porMes;
        }

        // Totales por mes (solo pagados)
        $totalesMes = [];
        for ($m = 1; $m <= 12; $m++) {
            $totalesMes[$m] = $gastosFijos->sum(function ($gasto) use ($matriz, $m) {
                $pago = $matriz[$gasto->id]->get($m);
                return ($pago && $pago->estado === 'pagado') ? (float) $pago->monto_real : 0;
            });
        }

        // Totales por gasto fijo (suma anual pagada)
        $totalesGasto = $gastosFijos->mapWithKeys(function ($gasto) use ($matriz) {
            $total = collect($matriz[$gasto->id])->filter(fn($p) => $p->estado === 'pagado')->sum('monto_real');
            return [$gasto->id => $total];
        });

        $totalAnio      = array_sum($totalesMes);
        $pendienteAnio  = $gastosFijos->sum(function ($gasto) use ($matriz) {
            return collect($matriz[$gasto->id])
                ->whereIn('estado', ['pendiente', 'vencido'])
                ->sum('monto_estimado');
        });

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

        foreach ($gastos as $gasto) {
            if (!$gasto->seVenceEnMes($year, $month)) {
                continue;
            }

            // El día de vencimiento no puede exceder el último día del mes
            $ultimoDia   = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $diaVence    = min($gasto->dia_vencimiento, $ultimoDia);
            $fechaVence  = Carbon::createFromDate($year, $month, $diaVence);

            GastoPago::firstOrCreate(
                ['gasto_fijo_id' => $gasto->id, 'periodo' => $periodo],
                [
                    'fecha_vencimiento' => $fechaVence->toDateString(),
                    'monto_estimado'    => $gasto->monto_estimado,
                    'estado'            => $fechaVence->isPast() ? 'vencido' : 'pendiente',
                ]
            );

            $creados++;
        }

        return redirect()->route('gastos-pagos.index', compact('year', 'month'))
            ->with('success', "Se generaron {$creados} gasto(s) para " . Carbon::createFromDate($year, $month, 1)->locale('es')->isoFormat('MMMM [de] YYYY') . '.');
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
