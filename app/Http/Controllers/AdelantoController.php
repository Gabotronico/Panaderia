<?php

namespace App\Http\Controllers;

use App\Models\Adelanto;
use Illuminate\Http\Request;

class AdelantoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Corrige un adelanto mal cargado.
     *
     * Solo mientras siga pendiente. Una vez que una planilla lo descontó, el
     * monto ya salió en un pago y quedó dentro de un total cerrado: cambiarlo
     * acá dejaría la planilla diciendo que descontó una cifra distinta de la
     * que figura en el adelanto. Para eso hay que eliminar la planilla, que
     * devuelve los adelantos a pendientes.
     */
    public function update(Request $request, Adelanto $adelanto)
    {
        if ($bloqueo = $this->yaDescontado($adelanto, 'modificar')) {
            return $bloqueo;
        }

        $request->validate([
            'monto'       => 'required|numeric|min:0.01',
            'fecha'       => 'required|date',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $empleado = $adelanto->empleado;
        $monto    = (float) $request->monto;

        // El tope se mide sin contar este adelanto: si no, corregir 100 por 120
        // chocaría contra su propio monto viejo.
        $otrosPendientes = (float) $empleado->adelantos()
            ->whereNull('planilla_id')
            ->where('id', '!=', $adelanto->id)
            ->sum('monto');

        $capacidad = round(max(0, (float) $empleado->salario_base - $otrosPendientes), 2);

        if ($monto > $capacidad) {
            return redirect()->route('empleados.show', $empleado)->with('error',
                'El adelanto de Bs ' . number_format($monto, 2) .
                " supera lo que {$empleado->nombre} puede recibir. " .
                'Su sueldo del ciclo es Bs ' . number_format((float) $empleado->salario_base, 2) .
                ($otrosPendientes > 0
                    ? ' y tiene otros Bs ' . number_format($otrosPendientes, 2) . ' sin descontar'
                    : '') .
                ', así que el máximo disponible es Bs ' . number_format($capacidad, 2) . '.'
            );
        }

        $anterior = (float) $adelanto->monto;

        $adelanto->update([
            'monto'       => $monto,
            'fecha'       => $request->fecha,
            'descripcion' => $request->descripcion,
        ]);

        $cambioDeMonto = round($anterior, 2) !== round($monto, 2)
            ? ' Pasó de Bs ' . number_format($anterior, 2) . ' a Bs ' . number_format($monto, 2) . '.'
            : '';

        return redirect()->route('empleados.show', $empleado)
            ->with('success', "Se actualizó el adelanto de {$empleado->nombre_completo}." . $cambioDeMonto);
    }

    /** Borra un adelanto cargado por error, siempre que ninguna planilla lo haya descontado. */
    public function destroy(Adelanto $adelanto)
    {
        if ($bloqueo = $this->yaDescontado($adelanto, 'eliminar')) {
            return $bloqueo;
        }

        $empleado = $adelanto->empleado;
        $monto    = number_format((float) $adelanto->monto, 2);
        $fecha    = $adelanto->fecha->format('d/m/Y');

        $adelanto->delete();

        return redirect()->route('empleados.show', $empleado)->with('success',
            "Se eliminó el adelanto de Bs {$monto} del {$fecha}. " .
            "{$empleado->nombre} ya no lo debe."
        );
    }

    /**
     * Corta la operación si el adelanto ya fue descontado en una planilla.
     *
     * @return \Illuminate\Http\RedirectResponse|null  null si se puede seguir.
     */
    private function yaDescontado(Adelanto $adelanto, string $accion)
    {
        if (!$adelanto->planilla_id) {
            return null;
        }

        return redirect()->route('empleados.show', $adelanto->empleado)->with('error',
            "No se puede {$accion}: este adelanto ya se descontó en la planilla #{$adelanto->planilla_id}. " .
            'Si el dato está mal, eliminá esa planilla —los adelantos vuelven a quedar pendientes— y corregilo.'
        );
    }
}
