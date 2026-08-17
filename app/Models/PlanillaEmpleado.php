<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanillaEmpleado extends Model
{
    protected $table = 'planilla_empleado';

    protected $fillable = [
        'planilla_id', 'empleado_id',
        'dias_trabajados', 'dias_ausentes', 'dias_tardanza', 'dias_medio',
        'horas_extra', 'monto_horas_extra', 'detalle_horas_extra',
        'adelantos_descontados',
        'salario_bruto', 'total_neto',
    ];

    protected $casts = [
        'horas_extra'           => 'decimal:2',
        'monto_horas_extra'     => 'decimal:2',
        'adelantos_descontados' => 'decimal:2',
        'salario_bruto'         => 'decimal:2',
        'total_neto'            => 'decimal:2',
    ];

    /**
     * Neto a pagar: lo ganado por días trabajados, más lo que el administrador
     * haya decidido pagar por horas extra, menos los adelantos ya entregados.
     *
     * Las horas extra se suman DESPUÉS del descuento de adelantos y no agrandan
     * la bolsa de la que estos se descuentan: cuál adelanto entra en la planilla
     * se resolvió al generarla, y recalcularlo acá cambiaría de forma silenciosa
     * la deuda que el empleado arrastra al período siguiente.
     */
    public function calcularNeto(): float
    {
        return round(
            (float) $this->salario_bruto
            + (float) $this->monto_horas_extra
            - (float) $this->adelantos_descontados,
            2
        );
    }

    public function planilla()
    {
        return $this->belongsTo(Planilla::class);
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}
