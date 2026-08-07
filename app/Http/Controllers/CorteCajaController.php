<?php

namespace App\Http\Controllers;

use App\Mail\AperturaCajaMail;
use App\Mail\CierreCajaMail;
use App\Models\CorteCaja;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CorteCajaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-cortes')->only(['index', 'show']);
        $this->middleware('permission:crear-cortes')->only(['create', 'store']);
        $this->middleware('permission:editar-cortes')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-cortes')->only(['destroy']);
        $this->middleware('permission:editar-cortes-cerrados')->only(['editarCierre', 'actualizarCierre']);
    }

    public function index()
    {
        $cortes = CorteCaja::with('user')
            ->orderBy('fecha_corte', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        // Verificar si hay un corte abierto hoy
        $corteAbierto = CorteCaja::where('user_id', Auth::id())
            ->where('estado', 'abierto')
            ->whereDate('fecha_corte', Carbon::today())
            ->first();

        return view('cortes.index', compact('cortes', 'corteAbierto'));
    }

    public function create()
    {
        // Verificar si ya existe un corte abierto para el usuario
        $corteAbierto = CorteCaja::where('user_id', Auth::id())
            ->where('estado', 'abierto')
            ->first();

        if ($corteAbierto) {
            return redirect()->route('cortes.index')
                ->with('error', 'Ya tienes un corte de caja abierto.');
        }

        return view('cortes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'monto_inicial' => 'required|numeric|min:0',
            'fecha_corte'   => 'nullable|date|before_or_equal:today',
            'hora_apertura' => 'nullable|date_format:H:i',
            'observaciones' => 'nullable|string',
        ], [
            'fecha_corte.before_or_equal' => 'No se puede abrir una caja con fecha futura.',
        ]);

        // Verificar que no exista otro corte abierto
        $corteAbierto = CorteCaja::where('user_id', Auth::id())
            ->where('estado', 'abierto')
            ->first();

        if ($corteAbierto) {
            return redirect()->route('cortes.index')
                ->with('error', 'Ya tienes un corte de caja abierto.');
        }

        $fecha = $request->filled('fecha_corte')
            ? Carbon::parse($request->fecha_corte)->startOfDay()
            : Carbon::today();

        // Abrir una caja de un día pasado sirve para cargar en diferido lo que
        // se vendió, por ejemplo, el domingo. Se valida que no haya otra del
        // mismo día para el mismo cajero, o quedarían dos arqueos del turno.
        $yaExiste = CorteCaja::where('user_id', Auth::id())
            ->whereDate('fecha_corte', $fecha->toDateString())
            ->exists();

        if ($yaExiste) {
            return redirect()->back()->withInput()->with('error',
                'Ya existe un corte de caja tuyo para el ' . $fecha->format('d/m/Y') . '.'
            );
        }

        $horaApertura = $request->filled('hora_apertura')
            ? $request->hora_apertura . ':00'
            : Carbon::now()->format('H:i:s');

        $corte = CorteCaja::create([
            'user_id' => Auth::id(),
            'fecha_corte' => $fecha->toDateString(),
            'hora_apertura' => $horaApertura,
            'monto_inicial' => $request->monto_inicial,
            'estado' => 'abierto',
            'observaciones' => $request->observaciones,
        ]);

        // created_at marca el arranque del turno: ventasDelTurno() solo toma
        // ventas posteriores a ese instante. Si la caja es de un día pasado y
        // se dejara la hora de hoy, no encontraría ninguna venta de ese día.
        $corte->forceFill([
            'created_at' => $fecha->copy()->setTimeFromTimeString($horaApertura),
        ])->save();

        $corte->load('user.almacen');
        $this->notificarAdministradores(new AperturaCajaMail($corte));

        return redirect()->route('cortes.show', $corte->id)
            ->with('success', 'Corte de caja abierto exitosamente.');
    }

    public function show(CorteCaja $corte)
    {
        $ventasCorte = $this->ventasDelTurno($corte)->get();

        // Una caja abierta no tiene totales guardados todavía, así que se
        // calculan al vuelo. La cerrada muestra lo que quedó registrado en el
        // arqueo, que es el dato con valor contable.
        if ($corte->estado === 'abierto') {
            $totales = $this->desglosarPorPago($ventasCorte);
        } else {
            $totales = [
                'total'    => (float) $corte->total_ventas,
                'efectivo' => (float) $corte->ventas_efectivo,
                'qr'       => (float) $corte->ventas_qr,
            ];
        }

        $totalVentas = $totales['total'];

        // Mientras la caja sigue abierta, el cajero no ve el total de ventas:
        // ese dato le permitiría deducir cuánto debe entregar al cerrar.
        // Una vez cerrada, ya no hay nada que proteger y se muestra completo.
        $puedeVerEsperado = Auth::user()->esAdministrador() || $corte->estado === 'cerrado';

        $corte->load('user', 'cerradoPor');

        return view('cortes.show', compact('corte', 'ventasCorte', 'totalVentas', 'totales', 'puedeVerEsperado'));
    }

    public function edit(CorteCaja $corte)
    {
        if ($corte->estado === 'cerrado') {
            // Un corte cerrado ya no se "cierra" otra vez: se corrige, y eso
            // es potestad de administración por la vía de editarCierre().
            if (Auth::user()->can('editar-cortes-cerrados')) {
                return redirect()->route('cortes.cierre.editar', $corte->id);
            }

            return redirect()->route('cortes.index')
                ->with('error', 'No se puede editar un corte cerrado.');
        }

        // El cajero solo cierra su propia caja; el administrador puede cerrar
        // la de cualquiera (por ejemplo si el cajero se retiró sin hacerlo).
        if (!$this->puedeCerrar($corte)) {
            return redirect()->route('cortes.index')
                ->with('error', 'No tienes permiso para cerrar este corte.');
        }

        $totales     = $this->desglosarPorPago($this->ventasDelTurno($corte)->get());
        $totalVentas = $totales['total'];

        // El cajero cierra la caja a ciegas: no ve cuánto debería haber.
        // Así el arqueo refleja lo que realmente contó y no un número copiado.
        $puedeVerEsperado = Auth::user()->esAdministrador();

        return view('cortes.edit', compact('corte', 'totalVentas', 'totales', 'puedeVerEsperado'));
    }

    public function update(Request $request, CorteCaja $corte)
    {
        $validated = $request->validate([
            'total_efectivo' => 'required|numeric|min:0',
            'total_qr' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ], [
            'total_qr.required' => 'Registre el total cobrado por QR (0 si no hubo).',
        ]);

        if ($corte->estado === 'cerrado') {
            return redirect()->route('cortes.index')
                ->with('error', 'No se puede modificar un corte cerrado.');
        }

        if (!$this->puedeCerrar($corte)) {
            return redirect()->route('cortes.index')
                ->with('error', 'No tienes permiso para cerrar este corte.');
        }

        $totales = $this->desglosarPorPago($this->ventasDelTurno($corte)->get());

        $efectivoContado = (float) $validated['total_efectivo'];
        $qrContado       = (float) $validated['total_qr'];

        // El QR no entra al cajón, así que el efectivo esperado solo cuenta
        // las ventas cobradas en efectivo. Cada medio se arquea por separado.
        $diferencia   = round($efectivoContado - ($corte->monto_inicial + $totales['efectivo']), 2);
        $diferenciaQr = round($qrContado - $totales['qr'], 2);

        $corte->update([
            'hora_cierre' => Carbon::now()->format('H:i:s'),
            'total_ventas' => $totales['total'],
            'ventas_efectivo' => $totales['efectivo'],
            'ventas_qr' => $totales['qr'],
            'total_efectivo' => $efectivoContado,
            'total_qr' => $qrContado,
            'monto_final' => $efectivoContado,
            'diferencia' => $diferencia,
            'diferencia_qr' => $diferenciaQr,
            'estado' => 'cerrado',
            'cerrado_por' => Auth::id(),
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        $corte->load('user.almacen');
        $this->notificarAdministradores(new CierreCajaMail($corte));

        $mensaje = $corte->user_id === Auth::id()
            ? 'Corte de caja cerrado exitosamente.'
            : "Cerraste el corte de {$corte->user->name}. Queda registrado que el cierre lo hiciste vos.";

        return redirect()->route('cortes.show', $corte->id)->with('success', $mensaje);
    }

    /**
     * Formulario para corregir un arqueo ya cerrado. Reservado a quien tenga
     * 'editar-cortes-cerrados' — en la práctica, administración.
     */
    public function editarCierre(CorteCaja $corte)
    {
        if ($corte->estado !== 'cerrado') {
            return redirect()->route('cortes.edit', $corte->id);
        }

        $corte->load('user', 'cerradoPor');

        // Lo que las ventas dicen hoy, para contrastarlo con lo que quedó
        // congelado en el arqueo. Si no coinciden es que se anuló o registró
        // alguna venta después del cierre.
        $totalesActuales = $this->desglosarPorPago($this->ventasDelTurno($corte)->get());

        return view('cortes.editar-cierre', compact('corte', 'totalesActuales'));
    }

    /**
     * Aplica la corrección sobre un corte cerrado. El motivo es obligatorio y
     * se anexa a las observaciones: modificar el arqueo de otro tiene que
     * dejar rastro de quién lo hizo y por qué.
     */
    public function actualizarCierre(Request $request, CorteCaja $corte)
    {
        $validated = $request->validate([
            'total_efectivo' => 'required|numeric|min:0',
            'total_qr' => 'required|numeric|min:0',
            'recalcular_ventas' => 'nullable|boolean',
            'motivo' => 'required|string|min:5|max:500',
        ], [
            'motivo.required' => 'Indique el motivo de la corrección.',
            'motivo.min' => 'El motivo debe explicar el cambio (mínimo 5 caracteres).',
        ]);

        if ($corte->estado !== 'cerrado') {
            return redirect()->route('cortes.index')
                ->with('error', 'Este corte no está cerrado.');
        }

        $efectivoContado = (float) $validated['total_efectivo'];
        $qrContado       = (float) $validated['total_qr'];

        $ventasEfectivo = (float) $corte->ventas_efectivo;
        $ventasQr       = (float) $corte->ventas_qr;
        $totalVentas    = (float) $corte->total_ventas;

        // Opcional: volver a leer las ventas del turno. Sirve cuando se anuló
        // una venta después del cierre y el arqueo quedó contra un total viejo.
        if ($request->boolean('recalcular_ventas')) {
            $totales        = $this->desglosarPorPago($this->ventasDelTurno($corte)->get());
            $ventasEfectivo = $totales['efectivo'];
            $ventasQr       = $totales['qr'];
            $totalVentas    = $totales['total'];
        }

        $marca = Carbon::now()->format('d/m/Y H:i');
        $nota  = "[Corrección {$marca} por " . Auth::user()->name . "] " . $validated['motivo'];

        $corte->update([
            'total_ventas' => $totalVentas,
            'ventas_efectivo' => $ventasEfectivo,
            'ventas_qr' => $ventasQr,
            'total_efectivo' => $efectivoContado,
            'total_qr' => $qrContado,
            'monto_final' => $efectivoContado,
            'diferencia' => round($efectivoContado - ($corte->monto_inicial + $ventasEfectivo), 2),
            'diferencia_qr' => round($qrContado - $ventasQr, 2),
            'observaciones' => trim(($corte->observaciones ? $corte->observaciones . "\n" : '') . $nota),
        ]);

        return redirect()->route('cortes.show', $corte->id)
            ->with('success', "Cierre del corte #{$corte->id} corregido. La modificación quedó registrada en las observaciones.");
    }

    /**
     * Un corte lo puede cerrar su dueño o cualquier administrador.
     */
    private function puedeCerrar(CorteCaja $corte): bool
    {
        return $corte->user_id === Auth::id() || Auth::user()->esAdministrador();
    }

    /**
     * Ventas completadas que caen dentro del turno.
     *
     * El turno va desde la apertura hasta hora_cierre. Antes el límite
     * superior era updated_at, pero ese campo se mueve cada vez que se toca
     * el registro: al corregir un cierre la ventana se agrandaba sola y
     * empezaban a colarse ventas del turno siguiente.
     */
    private function ventasDelTurno(CorteCaja $corte)
    {
        $query = Venta::where('user_id', $corte->user_id)
            ->whereDate('created_at', $corte->fecha_corte)
            ->where('created_at', '>=', $corte->created_at)
            ->where('estado', 'completada');

        if ($corte->estado === 'cerrado' && $corte->hora_cierre) {
            $query->where(
                'created_at',
                '<=',
                $corte->fecha_corte->copy()->setTimeFromTimeString($corte->hora_cierre)
            );
        }

        return $query->orderBy('created_at');
    }

    /** Separa un conjunto de ventas en total, efectivo y QR. */
    private function desglosarPorPago($ventas): array
    {
        return [
            'total'    => (float) $ventas->sum('total'),
            'efectivo' => (float) $ventas->where('tipo_pago', 'efectivo')->sum('total'),
            'qr'       => (float) $ventas->where('tipo_pago', 'qr')->sum('total'),
        ];
    }

    private function notificarAdministradores(\Illuminate\Mail\Mailable $mail): void
    {
        $admins = User::role('Administrador')->whereNotNull('email')->get();
        foreach ($admins as $admin) {
            try {
                Mail::to($admin->email)->send($mail);
            } catch (\Throwable) {
                // No bloquear el flujo si el correo falla
            }
        }
    }

    public function destroy(CorteCaja $corte)
    {
        // Borrar un corte cerrado elimina el arqueo de un turno completo, así
        // que exige el permiso extra que solo tiene administración.
        if ($corte->estado === 'cerrado' && !Auth::user()->can('eliminar-cortes-cerrados')) {
            return redirect()->route('cortes.index')
                ->with('error', 'No se puede eliminar un corte cerrado.');
        }

        $eraCerrado = $corte->estado === 'cerrado';
        $cajero     = $corte->user->name;
        $id         = $corte->id;

        $corte->delete();

        $mensaje = $eraCerrado
            ? "Se eliminó el cierre de caja #{$id} de {$cajero}."
            : 'Corte de caja eliminado exitosamente.';

        return redirect()->route('cortes.index')->with('success', $mensaje);
    }
}