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
            'observaciones' => 'nullable|string',
        ]);

        // Verificar que no exista otro corte abierto
        $corteAbierto = CorteCaja::where('user_id', Auth::id())
            ->where('estado', 'abierto')
            ->first();

        if ($corteAbierto) {
            return redirect()->route('cortes.index')
                ->with('error', 'Ya tienes un corte de caja abierto.');
        }

        $corte = CorteCaja::create([
            'user_id' => Auth::id(),
            'fecha_corte' => Carbon::today(),
            'hora_apertura' => Carbon::now()->format('H:i:s'),
            'monto_inicial' => $request->monto_inicial,
            'estado' => 'abierto',
            'observaciones' => $request->observaciones,
        ]);

        $corte->load('user.almacen');
        $this->notificarAdministradores(new AperturaCajaMail($corte));

        return redirect()->route('cortes.show', $corte->id)
            ->with('success', 'Corte de caja abierto exitosamente.');
    }

    public function show(CorteCaja $corte)
    {
        // Calcular ventas del corte si está abierto
        if ($corte->estado === 'abierto') {
            $ventasCorte = Venta::where('user_id', $corte->user_id)
                ->whereDate('created_at', $corte->fecha_corte)
                ->where('created_at', '>=', $corte->created_at)
                ->where('estado', 'completada')
                ->get();
            
            $totalVentas = $ventasCorte->sum('total');
        } else {
            $totalVentas = $corte->total_ventas;
            $ventasCorte = Venta::where('user_id', $corte->user_id)
                ->whereDate('created_at', $corte->fecha_corte)
                ->where('created_at', '>=', $corte->created_at)
                ->where('created_at', '<=', $corte->updated_at)
                ->where('estado', 'completada')
                ->get();
        }

        return view('cortes.show', compact('corte', 'ventasCorte', 'totalVentas'));
    }

    public function edit(CorteCaja $corte)
    {
        if ($corte->estado === 'cerrado') {
            return redirect()->route('cortes.index')
                ->with('error', 'No se puede editar un corte cerrado.');
        }

        if ($corte->user_id !== Auth::id()) {
            return redirect()->route('cortes.index')
                ->with('error', 'No tienes permiso para editar este corte.');
        }

        // Calcular total de ventas
        $totalVentas = Venta::where('user_id', $corte->user_id)
            ->whereDate('created_at', $corte->fecha_corte)
            ->where('created_at', '>=', $corte->created_at)
            ->where('estado', 'completada')
            ->sum('total');

        return view('cortes.edit', compact('corte', 'totalVentas'));
    }

    public function update(Request $request, CorteCaja $corte)
    {
        $request->validate([
            'total_efectivo' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        if ($corte->estado === 'cerrado') {
            return redirect()->route('cortes.index')
                ->with('error', 'No se puede modificar un corte cerrado.');
        }

        // Calcular total de ventas
        $totalVentas = Venta::where('user_id', $corte->user_id)
            ->whereDate('created_at', $corte->fecha_corte)
            ->where('created_at', '>=', $corte->created_at)
            ->where('estado', 'completada')
            ->sum('total');

           $montoFinal = $request->total_efectivo;
           // diferencia = efectivo contado - (monto inicial + ventas del turno)
           $diferencia = $montoFinal - ($corte->monto_inicial + $totalVentas);

        $corte->update([
            'hora_cierre' => Carbon::now()->format('H:i:s'),
            'total_ventas' => $totalVentas,
            'total_efectivo' => $request->total_efectivo,
            'monto_final' => $montoFinal,
            'diferencia' => $diferencia,
            'estado' => 'cerrado',
            'observaciones' => $request->observaciones,
        ]);

        $corte->load('user.almacen');
        $this->notificarAdministradores(new CierreCajaMail($corte));

        return redirect()->route('cortes.show', $corte->id)
            ->with('success', 'Corte de caja cerrado exitosamente.');
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
        if ($corte->estado === 'cerrado') {
            return redirect()->route('cortes.index')
                ->with('error', 'No se puede eliminar un corte cerrado.');
        }

        $corte->delete();
        
        return redirect()->route('cortes.index')
            ->with('success', 'Corte de caja eliminado exitosamente.');
    }
}