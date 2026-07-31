<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanillaEmpleado extends Model
{
    protected $table = 'planilla_empleado';

    protected $fillable = [
        'planilla_id', 'empleado_id',
        'dias_trabajados', 'dias_ausentes', 'dias_tardanza', 'dias_medio',
        'adelantos_descontados',
        'salario_bruto', 'total_neto',
    ];

    protected $casts = [
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
