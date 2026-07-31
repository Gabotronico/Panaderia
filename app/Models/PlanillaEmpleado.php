<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanillaEmpleado extends Model
{
    protected $table = 'planilla_empleado';

    protected $fillable = [
        'planilla_id', 'empleado_id',
        'dias_trabajados', 'dias_ausentes', 'dias_tardanza', 'dias_medio',
        'horas_extra', 'monto_horas_extra', 'adelantos_descontados',
        'salario_bruto', 'total_neto',
    ];

    protected $casts = [
        'horas_extra'           => 'decimal:2',
        'monto_horas_extra'     => 'decimal:2',
        'adelantos_descontados' => 'decimal:2',
        'salario_bruto'         => 'decimal:2',
        'total_neto'            => 'decimal:2',
    ];

    public function planilla()
    {
        return $this->belongsTo(Planilla::class);
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}
